<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

/**
 * Tabel pengguna dengan pencarian, filter, dan paginasi sisi server.
 * Semua query dibatasi paginasi, jadi aman untuk data ribuan baris.
 */
class UserTable extends Component
{
    use WithPagination;

    #[Url(as: 'q', keep: false)]
    public string $search = '';

    #[Url]
    public string $role = '';

    #[Url]
    public string $status = '';

    #[Url]
    public bool $trashed = false;

    public int $perPage = 15;

    public function updated($property): void
    {
        // Setiap perubahan filter mengembalikan pembaca ke halaman pertama.
        if (in_array($property, ['search', 'role', 'status', 'trashed'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'role', 'status', 'trashed']);
        $this->resetPage();
    }

    /**
     * Aktifkan / nonaktifkan pengguna tanpa meninggalkan halaman.
     */
    public function toggleActive(int $userId): void
    {
        $this->authorize('user.update');

        $user = User::findOrFail($userId);

        if ($user->id === Auth::id()) {
            $this->dispatch('notify', type: 'error', message: 'Anda tidak bisa menonaktifkan akun sendiri.');

            return;
        }

        if ($user->is_super_admin && ! Auth::user()->is_super_admin) {
            $this->dispatch('notify', type: 'error', message: 'Hanya super admin yang boleh mengubah akun super admin.');

            return;
        }

        $user->update(['is_active' => ! $user->is_active]);

        $this->dispatch('notify',
            type: 'success',
            message: "Akun {$user->name} ".($user->is_active ? 'diaktifkan.' : 'dinonaktifkan.')
        );
    }

    public function render(): View
    {
        $users = User::query()
            ->with('roles:id,name')
            ->when($this->trashed, fn ($q) => $q->onlyTrashed())
            ->when($this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(fn ($sub) => $sub
                    ->where('name', 'like', $term)
                    ->orWhere('username', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone', 'like', $term));
            })
            ->when($this->role !== '', fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('name', $this->role)))
            ->when($this->status !== '', fn ($q) => $q->where('is_active', $this->status === 'aktif'))
            ->orderBy('name')
            ->paginate($this->perPage);

        return view('livewire.user-table', [
            'users' => $users,
            'roles' => Role::orderBy('name')->pluck('name'),
        ]);
    }
}
