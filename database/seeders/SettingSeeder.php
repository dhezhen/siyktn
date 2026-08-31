<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'app_name', 'value' => 'Karantina Tahfizh', 'label' => 'Nama Aplikasi', 'group' => 'umum', 'type' => 'text'],
            ['key' => 'app_short_name', 'value' => 'PKTQ', 'label' => 'Nama Singkat', 'group' => 'umum', 'type' => 'text'],
            ['key' => 'organization', 'value' => 'Pondok Pesantren Karantina Tahfizh Al-Qur\'an Nasional', 'label' => 'Nama Lembaga', 'group' => 'umum', 'type' => 'text'],
            ['key' => 'address', 'value' => '', 'label' => 'Alamat', 'group' => 'umum', 'type' => 'textarea'],
            ['key' => 'phone', 'value' => '', 'label' => 'Telepon', 'group' => 'kontak', 'type' => 'text'],
            ['key' => 'email', 'value' => '', 'label' => 'Email Resmi', 'group' => 'kontak', 'type' => 'text'],
            ['key' => 'logo', 'value' => null, 'label' => 'Logo', 'group' => 'tampilan', 'type' => 'image'],
            ['key' => 'active_period', 'value' => date('Y'), 'label' => 'Periode Aktif', 'group' => 'umum', 'type' => 'text'],
        ];

        foreach ($settings as $setting) {
            Setting::firstOrCreate(['key' => $setting['key']], $setting);
        }

        Setting::flush();

        $this->command?->info(count($settings).' pengaturan tersedia.');
    }
}
