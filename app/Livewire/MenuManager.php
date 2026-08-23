<?php

namespace App\Livewire;

use App\Models\Menu;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Pengelola menu sidebar: susun bertingkat, urutkan lewat drag & drop,
 * dan kaitkan setiap menu dengan sebuah permission.
 */
class MenuManager extends Component
{
    /** Menu yang sedang dibuka di form; null berarti form tertutup. */
    public ?int $editingId = null;

    public bool $showForm = false;

    // Field form
    public ?int $parent_id = null;

    public string $title = '';

    public string $icon = '';

    public string $type = 'route';

    public string $route = '';

    public string $url = '';

    public string $target = '_self';

    public string $permission = '';

    public bool $is_active = true;

    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in(['route', 'url', 'header', 'divider'])],
            'parent_id' => ['nullable', 'exists:menus,id'],
            'icon' => ['nullable', 'string', 'max:50'],
            'route' => [Rule::requiredIf($this->type === 'route'), 'nullable', 'string', 'max:150'],
            'url' => [Rule::requiredIf($this->type === 'url'), 'nullable', 'string', 'max:255'],
            'target' => ['required', Rule::in(['_self', '_blank'])],
            'permission' => ['nullable', 'string', Rule::in(\App\Support\Rbac::allPermissions())],
        ];
    }

    protected function messages(): array
    {
        return [
            'title.required' => 'Judul menu wajib diisi.',
            'route.required' => 'Nama route wajib diisi untuk menu bertipe route.',
            'url.required' => 'URL wajib diisi untuk menu bertipe url.',
            'permission.in' => 'Permission tidak dikenal. Pilih dari daftar yang tersedia.',
        ];
    }

    public function openCreate(?int $parentId = null): void
    {
        $this->resetForm();
        $this->parent_id = $parentId;
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $menu = Menu::findOrFail($id);

        $this->editingId = $menu->id;
        $this->parent_id = $menu->parent_id;
        $this->title = $menu->title;
        $this->icon = (string) $menu->icon;
        $this->type = $menu->type;
        $this->route = (string) $menu->route;
        $this->url = (string) $menu->url;
        $this->target = $menu->target;
        $this->permission = (string) $menu->permission;
        $this->is_active = $menu->is_active;

        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'menu.update' : 'menu.create');

        $data = $this->validate();

        // Menu tidak boleh menjadi anak dari dirinya sendiri atau keturunannya.
        if ($this->editingId && $this->parent_id) {
            $menu = Menu::findOrFail($this->editingId);
            $candidateParent = Menu::find($this->parent_id);

            if ($this->parent_id === $this->editingId || ($candidateParent && $menu->isAncestorOf($candidateParent))) {
                $this->addError('parent_id', 'Menu tidak boleh ditempatkan di dalam dirinya sendiri.');

                return;
            }
        }

        // Peringatan, bukan penolakan: route boleh didaftarkan lebih dulu.
        $routeMissing = $data['type'] === 'route' && ! Route::has($data['route']);

        $payload = array_merge($data, [
            'is_active' => $this->is_active,
            'route' => $data['type'] === 'route' ? $data['route'] : null,
            'url' => $data['type'] === 'url' ? $data['url'] : null,
            'permission' => $data['permission'] !== '' ? $data['permission'] : null,
            'icon' => $data['icon'] !== '' ? $data['icon'] : null,
        ]);

        if ($this->editingId) {
            Menu::findOrFail($this->editingId)->update($payload);
            $message = 'Menu diperbarui.';
        } else {
            $payload['order'] = (Menu::where('parent_id', $this->parent_id)->max('order') ?? 0) + 1;
            Menu::create($payload);
            $message = 'Menu ditambahkan.';
        }

        if ($routeMissing) {
            $message .= " Catatan: route '{$data['route']}' belum ada, menu disembunyikan sampai route dibuat.";
        }

        $this->closeForm();
        $this->dispatch('notify', type: $routeMissing ? 'warning' : 'success', message: $message);
    }

    public function toggleActive(int $id): void
    {
        $this->authorize('menu.update');

        $menu = Menu::findOrFail($id);
        $menu->update(['is_active' => ! $menu->is_active]);

        $this->dispatch('notify', type: 'success',
            message: "Menu {$menu->title} ".($menu->is_active ? 'ditampilkan.' : 'disembunyikan.'));
    }

    public function delete(int $id): void
    {
        $this->authorize('menu.delete');

        $menu = Menu::withCount('children')->findOrFail($id);

        if ($menu->children_count > 0) {
            $this->dispatch('notify', type: 'error',
                message: "Menu '{$menu->title}' masih punya {$menu->children_count} submenu. Pindahkan atau hapus submenunya dulu.");

            return;
        }

        $title = $menu->title;
        $menu->delete();

        $this->dispatch('notify', type: 'success', message: "Menu '{$title}' dihapus.");
    }

    /**
     * Simpan urutan baru hasil drag & drop.
     *
     * @param  array<int, array{id: int, parent_id: int|null}>  $items
     */
    public function reorder(array $items): void
    {
        $this->authorize('menu.update');

        foreach ($items as $position => $item) {
            Menu::whereKey($item['id'])->update([
                'parent_id' => $item['parent_id'] ?: null,
                'order' => $position,
            ]);
        }

        Menu::flushCache();

        $this->dispatch('notify', type: 'success', message: 'Urutan menu disimpan.');
    }

    public function render(): View
    {
        return view('livewire.menu-manager', [
            'menus' => Menu::with('children.children')->roots()->orderBy('order')->get(),
            'parentOptions' => Menu::roots()->orderBy('order')->get(['id', 'title']),
            'permissions' => \App\Support\Rbac::allPermissions(),
            'availableRoutes' => collect(Route::getRoutes())
                ->map(fn ($r) => $r->getName())
                ->filter(fn ($name) => $name && ! str_starts_with($name, 'livewire')
                    && ! str_starts_with($name, 'storage') && ! str_contains($name, '.store')
                    && ! str_contains($name, '.update') && ! str_contains($name, '.destroy'))
                ->unique()->sort()->values(),
        ]);
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'parent_id', 'title', 'icon', 'type', 'route', 'url', 'target', 'permission']);
        $this->type = 'route';
        $this->target = '_self';
        $this->is_active = true;
        $this->resetValidation();
    }
}
