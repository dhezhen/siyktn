<?php

namespace Database\Seeders;

use App\Models\Program;
use Illuminate\Database\Seeder;

class ProgramSeeder extends Seeder
{
    public function run(): void
    {
        $programs = [
            [
                'nama' => 'Karantina Tahfizh Program Hafal Quran Sebulan (30 Hari)',
                'kode' => 'PROG-30D',
                'durasi_hari' => 30,
                'biaya_program' => 3750000,
                'biaya_pendaftaran' => 100000,
                'is_aktif' => true,
                'keterangan' => 'Program karantina tahfizh intensif durasi 1 bulan / 30 hari.',
            ],
            [
                'nama' => 'Karantina Tahfizh Al-Quran Program 3 Pekan',
                'kode' => 'PROG-3W',
                'durasi_hari' => 21,
                'biaya_program' => 3250000,
                'biaya_pendaftaran' => 100000,
                'is_aktif' => true,
                'keterangan' => 'Program karantina tahfizh intensif durasi 3 pekan / 21 hari.',
            ],
            [
                'nama' => 'Karantina Tahfizh Al-Quran Program 2 Pekan',
                'kode' => 'PROG-2W',
                'durasi_hari' => 14,
                'biaya_program' => 2500000,
                'biaya_pendaftaran' => 100000,
                'is_aktif' => true,
                'keterangan' => 'Program karantina tahfizh intensif durasi 2 pekan / 14 hari.',
            ],
            [
                'nama' => 'Karantina Tahfizh Al-Quran Program 1 Pekan',
                'kode' => 'PROG-1W',
                'durasi_hari' => 7,
                'biaya_program' => 2000000,
                'biaya_pendaftaran' => 100000,
                'is_aktif' => true,
                'keterangan' => 'Program karantina tahfizh intensif durasi 1 pekan / 7 hari.',
            ],
            [
                'nama' => 'Karantina Tahfizh Al-Quran Program Mutqin (3 Bulan)',
                'kode' => 'PROG-3M',
                'durasi_hari' => 90,
                'biaya_program' => 10850000,
                'biaya_pendaftaran' => 100000,
                'is_aktif' => true,
                'keterangan' => 'Program karantina mutqin pendalaman dan kelancaran hafalan 3 bulan.',
            ],
        ];

        foreach ($programs as $item) {
            Program::firstOrCreate(
                ['kode' => $item['kode']],
                $item
            );
        }
    }
}
