<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:user.view', only: ['index']),
            new Middleware('permission:user.create', only: ['create', 'store']),
            new Middleware('permission:user.update', only: ['edit', 'update']),
            new Middleware('permission:user.delete', only: ['destroy', 'restore']),
            new Middleware('permission:user.reset-password', only: ['resetPassword']),
            new Middleware('permission:user.export', only: ['export']),
            new Middleware('permission:user.import', only: ['importForm', 'import']),
        ];
    }

    public function index(): View
    {
        return view('user.index');
    }

    public function create(): View
    {
        return view('user.form', [
            'user' => new User(['is_active' => true]),
            'roles' => Role::orderBy('name')->get(),
            'assigned' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $user = User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'is_active' => $request->boolean('is_active'),
            'must_change_password' => $request->boolean('must_change_password'),
        ]);

        $this->storeAvatar($request, $user);
        $user->syncRoles($this->allowedRoles($data['roles'] ?? []));

        return redirect()->route('user.index')
            ->with('success', "Pengguna {$user->name} berhasil ditambahkan.");
    }

    public function edit(User $user): View
    {
        $this->guardSuperAdmin($user);

        return view('user.form', [
            'user' => $user,
            'roles' => Role::orderBy('name')->get(),
            'assigned' => $user->roles->pluck('name')->all(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->guardSuperAdmin($user);

        $data = $this->validated($request, $user);

        $user->fill([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'must_change_password' => $request->boolean('must_change_password'),
        ]);

        // Jangan mengunci diri sendiri.
        if ($user->id !== Auth::id()) {
            $user->is_active = $request->boolean('is_active');
        }

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

        $this->storeAvatar($request, $user);

        if ($user->id !== Auth::id()) {
            $user->syncRoles($this->allowedRoles($data['roles'] ?? []));
        }

        return redirect()->route('user.index')
            ->with('success', "Data {$user->name} berhasil diperbarui.");
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->guardSuperAdmin($user);

        if ($user->id === Auth::id()) {
            return back()->with('error', 'Anda tidak bisa menghapus akun sendiri.');
        }

        $user->delete();

        return back()->with('success', "Pengguna {$user->name} dipindahkan ke daftar terhapus.");
    }

    public function restore(int $id): RedirectResponse
    {
        $user = User::onlyTrashed()->findOrFail($id);
        $user->restore();

        return back()->with('success', "Pengguna {$user->name} dipulihkan.");
    }

    /**
     * Reset kata sandi oleh admin: dibuat acak dan wajib diganti saat login.
     */
    public function resetPassword(User $user): RedirectResponse
    {
        $this->guardSuperAdmin($user);

        $newPassword = Str::password(10, symbols: false);

        $user->forceFill([
            'password' => $newPassword,
            'must_change_password' => true,
        ])->save();

        return back()->with('success',
            "Kata sandi {$user->name} direset menjadi: {$newPassword} — catat sekarang, tidak ditampilkan lagi.");
    }

    /**
     * Ekspor CSV secara streaming, aman untuk data besar.
     */
    public function export(): StreamedResponse
    {
        $filename = 'pengguna-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');

            // BOM agar Excel membaca UTF-8 dengan benar.
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Nama', 'Username', 'Email', 'No HP', 'Role', 'Status', 'Login Terakhir']);

            User::with('roles:id,name')->orderBy('name')->chunk(500, function ($users) use ($handle) {
                foreach ($users as $user) {
                    fputcsv($handle, [
                        $user->name,
                        $user->username,
                        $user->email,
                        $user->phone,
                        $user->roles->pluck('name')->join(', '),
                        $user->is_active ? 'Aktif' : 'Nonaktif',
                        $user->last_login_at?->format('Y-m-d H:i'),
                    ]);
                }
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function importForm(): View
    {
        return view('user.import');
    }

    /**
     * Impor CSV dengan kolom: nama, username, email, no_hp, role.
     * Baris yang gagal dilaporkan, baris yang berhasil tetap tersimpan.
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate(
            ['file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']],
            attributes: ['file' => 'berkas CSV']
        );

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = fgetcsv($handle);

        if ($header === false) {
            return back()->with('error', 'Berkas CSV kosong.');
        }

        // Buang BOM bila ada, lalu normalkan nama kolom.
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]);
        $header = array_map(fn ($h) => Str::slug(trim((string) $h), '_'), $header);

        $availableRoles = Role::pluck('name')->all();
        $created = 0;
        $failed = [];
        $line = 1;

        DB::transaction(function () use ($handle, $header, $availableRoles, &$created, &$failed, &$line) {
            while (($row = fgetcsv($handle)) !== false) {
                $line++;

                if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                    continue;
                }

                $data = array_combine($header, array_pad($row, count($header), null));

                $validator = validator($data, [
                    'nama' => ['required', 'string', 'max:100'],
                    'username' => ['required', 'string', 'max:50', 'unique:users,username'],
                    'email' => ['required', 'email', 'max:150', 'unique:users,email'],
                    'no_hp' => ['nullable', 'string', 'max:25'],
                    'role' => ['nullable', 'string', Rule::in($availableRoles)],
                ]);

                if ($validator->fails()) {
                    $failed[] = "Baris {$line}: ".$validator->errors()->first();

                    continue;
                }

                $user = User::create([
                    'name' => $data['nama'],
                    'username' => $data['username'],
                    'email' => $data['email'],
                    'phone' => $data['no_hp'] ?: null,
                    'password' => Str::password(12, symbols: false),
                    'is_active' => true,
                    'must_change_password' => true,
                ]);

                if (! empty($data['role'])) {
                    $user->syncRoles([$data['role']]);
                }

                $created++;
            }
        });

        fclose($handle);

        $message = "{$created} pengguna berhasil diimpor. Kata sandi dibuat acak dan wajib diganti saat login pertama.";

        if ($failed !== []) {
            return redirect()->route('user.index')
                ->with('success', $message)
                ->with('warning', count($failed).' baris dilewati: '.implode(' | ', array_slice($failed, 0, 5)));
        }

        return redirect()->route('user.index')->with('success', $message);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'username' => [
                'required', 'string', 'max:50', 'regex:/^[a-zA-Z0-9._-]+$/',
                Rule::unique('users', 'username')->ignore($user?->id),
            ],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user?->id)],
            'phone' => ['nullable', 'string', 'max:25'],
            'avatar' => ['nullable', 'image', 'max:2048'],
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Password::defaults()],
            'roles' => ['array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')],
        ], [
            'username.regex' => 'Username hanya boleh huruf, angka, titik, garis bawah, dan tanda hubung.',
        ], [
            'name' => 'nama',
            'username' => 'username',
            'email' => 'email',
            'phone' => 'nomor HP',
            'avatar' => 'foto profil',
            'password' => 'kata sandi',
            'roles' => 'role',
        ]);
    }

    protected function storeAvatar(Request $request, User $user): void
    {
        if (! $request->hasFile('avatar')) {
            return;
        }

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->update(['avatar' => $request->file('avatar')->store('avatars', 'public')]);
    }

    /**
     * Hanya super admin yang boleh memberikan role super-admin.
     *
     * @param  array<int, string>  $roles
     * @return array<int, string>
     */
    protected function allowedRoles(array $roles): array
    {
        if (Auth::user()->is_super_admin) {
            return $roles;
        }

        return array_values(array_diff($roles, [config('permission.super_admin_role')]));
    }

    /**
     * Akun super admin hanya boleh disentuh sesama super admin.
     */
    protected function guardSuperAdmin(User $user): void
    {
        abort_if(
            $user->is_super_admin && ! Auth::user()->is_super_admin,
            403,
            'Hanya super admin yang boleh mengelola akun super admin.'
        );
    }
}
