<?php

namespace Database\Seeders;

use App\Models\Angkatan;
use App\Models\Peserta;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Data contoh untuk pengembangan.
 *
 * Sengaja dilewati di lingkungan production agar database sungguhan
 * tidak ikut terisi data karangan.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('DemoDataSeeder dilewati di environment production.');

            return;
        }

        if (Angkatan::exists()) {
            $this->command?->info('Data contoh sudah ada, dilewati.');

            return;
        }

        $angkatanList = [
            ['nama' => 'Angkatan 10', 'kode' => 'AK-10', 'tahun' => 2024, 'status' => 'selesai', 'kuota' => 30],
            ['nama' => 'Angkatan 11', 'kode' => 'AK-11', 'tahun' => 2025, 'status' => 'berjalan', 'kuota' => 30],
            ['nama' => 'Angkatan 12', 'kode' => 'AK-12', 'tahun' => 2026, 'status' => 'persiapan', 'kuota' => 25],
        ];

        $namaDepan = ['Ahmad', 'Muhammad', 'Abdul', 'Siti', 'Nur', 'Fatimah', 'Aisyah', 'Umar', 'Ali', 'Zainab',
            'Ibrahim', 'Khadijah', 'Yusuf', 'Maryam', 'Hasan', 'Husein', 'Ridwan', 'Salma'];
        $namaBelakang = ['Hidayat', 'Ramadhan', 'Nugroho', 'Wijaya', 'Santoso', 'Kurniawan', 'Lestari',
            'Puspita', 'Anggraini', 'Setiawan', 'Firdaus', 'Rahmawati'];
        $kota = ['Kuningan', 'Cirebon', 'Bandung', 'Majalengka', 'Indramayu', 'Garut'];

        foreach ($angkatanList as $index => $data) {
            $angkatan = Angkatan::create(array_merge($data, [
                'tanggal_mulai' => now()->subMonths(18 - ($index * 8))->startOfMonth(),
                'tanggal_selesai' => $data['status'] === 'selesai'
                    ? now()->subMonths(6)->endOfMonth()
                    : null,
                'keterangan' => 'Data contoh untuk pengembangan.',
            ]));

            $jumlah = match ($data['status']) {
                'selesai' => 12,
                'berjalan' => 18,
                default => 4,
            };

            for ($i = 1; $i <= $jumlah; $i++) {
                $jenisKelamin = $i % 3 === 0 ? 'P' : 'L';

                Peserta::create([
                    'angkatan_id' => $angkatan->id,
                    'nomor_induk' => sprintf('%s-%04d', $angkatan->kode, $i),
                    'nama' => $namaDepan[array_rand($namaDepan)].' '.$namaBelakang[array_rand($namaBelakang)],
                    'jenis_kelamin' => $jenisKelamin,
                    'tempat_lahir' => $kota[array_rand($kota)],
                    'tanggal_lahir' => now()->subYears(rand(15, 22))->subDays(rand(0, 364)),
                    'alamat' => 'Jl. Contoh No. '.rand(1, 99).', '.$kota[array_rand($kota)],
                    'no_hp' => '08'.rand(1000000000, 9999999999),
                    'nama_wali' => $namaDepan[array_rand($namaDepan)].' '.$namaBelakang[array_rand($namaBelakang)],
                    'no_hp_wali' => '08'.rand(1000000000, 9999999999),
                    'tanggal_masuk' => $angkatan->tanggal_mulai,
                    'status' => $data['status'] === 'selesai'
                        ? (rand(1, 10) > 2 ? 'lulus' : 'keluar')
                        : 'aktif',
                ]);
            }
        }

        $this->command?->info(Angkatan::count().' angkatan dan '.Peserta::count().' peserta contoh dibuat.');
    }
}
