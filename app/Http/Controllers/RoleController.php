<?php

namespace App\Http\Controllers;

use App\Support\Rbac;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class RoleController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:role.view', only: ['index']),
            new Middleware('permission:role.create', only: ['create', 'store']),
            new Middleware('permission:role.update', only: ['edit', 'update']),
            new Middleware('permission:role.delete', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        $roles = Role::query()
            ->withCount(['users', 'permissions'])
            ->orderBy('name')
            ->get();

        return view('role.index', [
            'roles' => $roles,
            'descriptions' => collect(config('rbac.roles'))->map(fn ($r) => $r['description'] ?? null),
        ]);
    }

    public function create(): View
    {
        return view('role.form', [
            'role' => new Role,
            'matrix' => Rbac::matrix(),
            'assigned' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);
        $role->syncPermissions($this->sanitizePermissions($data['permissions'] ?? []));

        return redirect()->route('role.index')->with('success', "Role '{$role->name}' berhasil dibuat.");
    }

    public function edit(Role $role): View
    {
        return view('role.form', [
            'role' => $role,
            'matrix' => Rbac::matrix(),
            'assigned' => $role->permissions->pluck('name')->all(),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        if ($this->isSuperAdmin($role)) {
            return back()->with('error', 'Role super-admin tidak perlu diatur — aksesnya sudah penuh.');
        }

        $data = $this->validated($request, $role);

        $role->update(['name' => $data['name']]);
        $role->syncPermissions($this->sanitizePermissions($data['permissions'] ?? []));

        return redirect()->route('role.index')->with('success', "Role '{$role->name}' berhasil diperbarui.");
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($this->isSuperAdmin($role)) {
            return back()->with('error', 'Role super-admin tidak boleh dihapus.');
        }

        if ($role->users()->exists()) {
            return back()->with('error', "Role '{$role->name}' masih dipakai {$role->users()->count()} pengguna.");
        }

        $name = $role->name;
        $role->delete();

        return back()->with('success', "Role '{$name}' dihapus.");
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?Role $role = null): array
    {
        return $request->validate([
            'name' => [
                'required', 'string', 'max:50', 'regex:/^[a-z0-9\-]+$/',
                Rule::unique('roles', 'name')->ignore($role?->id),
            ],
            'permissions' => ['array'],
            'permissions.*' => ['string'],
        ], [
            'name.regex' => 'Nama role hanya boleh huruf kecil, angka, dan tanda hubung. Contoh: kepala-bidang.',
        ], [
            'name' => 'nama role',
            'permissions' => 'hak akses',
        ]);
    }

    /**
     * Buang permission yang tidak ada di katalog, supaya tidak ada data liar.
     *
     * @param  array<int, string>  $permissions
     * @return array<int, string>
     */
    protected function sanitizePermissions(array $permissions): array
    {
        return array_values(array_intersect($permissions, Rbac::allPermissions()));
    }

    protected function isSuperAdmin(Role $role): bool
    {
        return $role->name === config('permission.super_admin_role');
    }
}
