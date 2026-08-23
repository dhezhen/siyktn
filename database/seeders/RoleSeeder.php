<?php

namespace Database\Seeders;

use App\Support\Rbac;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (config('rbac.roles', []) as $name => $definition) {
            $role = Role::findOrCreate($name, 'web');

            $role->syncPermissions(Rbac::expand($definition['permissions'] ?? []));

            $this->command?->info("Role '{$name}': ".$role->permissions()->count().' permission.');
        }
    }
}
