<?php

namespace Database\Seeders;

use App\Support\Rbac;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Rbac::allPermissions() as $name) {
            Permission::findOrCreate($name, 'web');
        }

        // Bersihkan permission yang sudah dihapus dari katalog.
        Permission::query()
            ->whereNotIn('name', Rbac::allPermissions())
            ->delete();

        $this->command?->info('Permission tersinkron: '.count(Rbac::allPermissions()).' item.');
    }
}
