<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Akun bawaan untuk pengembangan.
     * Ganti kata sandinya sebelum dipakai di server sungguhan.
     */
    public function run(): void
    {
        $accounts = [
            ['name' => 'Super Administrator', 'username' => 'superadmin', 'email' => 'superadmin@siyktn.test', 'role' => 'super-admin'],
            ['name' => 'Administrator', 'username' => 'admin', 'email' => 'admin@siyktn.test', 'role' => 'admin'],
            ['name' => 'Operator Data', 'username' => 'operator', 'email' => 'operator@siyktn.test', 'role' => 'operator'],
        ];

        foreach ($accounts as $account) {
            $user = User::updateOrCreate(
                ['username' => $account['username']],
                [
                    'name' => $account['name'],
                    'email' => $account['email'],
                    'password' => 'password',
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            $user->syncRoles([$account['role']]);
        }

        $this->command?->info('3 akun bawaan siap (kata sandi: password).');
    }
}
