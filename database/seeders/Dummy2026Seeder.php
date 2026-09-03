<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pendaftaran;
use App\Models\Peserta;
use App\Models\Angkatan;
use App\Models\Setoran;
use App\Models\AnggotaHalaqah;
use Carbon\Carbon;
use Illuminate\Support\Str;

class Dummy2026Seeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Membuat data dummy untuk tahun 2026...');

        $angkatan = Angkatan::first() ?? Angkatan::create([
            'nama' => 'Angkatan Dummy 2026',
            'kode' => 'DUMMY-26',
            'tahun' => 2026,
            'status' => 'berjalan',
            'kuota' => 100,
        ]);

        // Generate 60 peserta baru dengan tanggal pendaftaran tersebar di 2026
        for ($i = 1; $i <= 60; $i++) {
            $bulan = rand(1, 9); // Jan to Sep 2026
            $hari = rand(1, 28);
            $tanggalDaftar = Carbon::create(2026, $bulan, $hari);

            $jk = rand(0, 1) ? 'L' : 'P';
            $peserta = Peserta::create([
                'nik' => '32' . rand(10000000000000, 99999999999999),
                'nama' => 'Peserta Dummy ' . $i,
                'jenis_kelamin' => $jk,
                'tempat_lahir' => 'Jakarta',
                'tanggal_lahir' => Carbon::create(2000, rand(1,12), rand(1,28))->toDateString(),
                'alamat' => 'Jl. Dummy No. ' . $i,
                'no_hp' => '08' . rand(100000000, 999999999),
                'nama_wali' => 'Bapak ' . $i,
                'no_hp_wali' => '08' . rand(100000000, 999999999),
            ]);

            Pendaftaran::create([
                'peserta_id' => $peserta->id,
                'angkatan_id' => $angkatan->id,
                'kode_pendaftaran' => 'REG26' . Str::random(5),
                'didaftarkan_pada' => $tanggalDaftar->format('Y-m-d H:i:s'),
                'biaya_pendaftaran' => 250000,
                'biaya_program' => 1500000,
                'status_pembayaran_pendaftaran' => rand(0, 1) ? 'lunas' : 'pending',
                'status_pembayaran_program' => rand(0, 1) ? 'lunas' : 'pending',
            ]);
        }

        // Generate setoran palsu agar progress harian muncul
        $anggota = AnggotaHalaqah::with('halaqah')->where('is_aktif', true)->take(20)->get();
        if ($anggota->isNotEmpty()) {
            foreach ($anggota as $item) {
                // Setoran dari 1-3 hari terakhir
                for ($hari = 3; $hari >= 0; $hari--) {
                    $kualitasArr = ['mumtaz', 'jayyid', 'jayyid'];
                    Setoran::create([
                        'anggota_halaqah_id' => $item->id,
                        'muhaffizh_id' => $item->halaqah?->muhaffizh_id,
                        'tanggal' => now()->subDays($hari)->toDateString(),
                        'jenis' => 'ziyadah',
                        'jumlah_halaman' => rand(1, 3),
                        'juz' => rand(1, 30),
                        'surah' => 'Al-Baqarah',
                        'ayat_dari' => rand(1, 10),
                        'ayat_sampai' => rand(11, 20),
                        'kualitas' => $kualitasArr[array_rand($kualitasArr)],
                    ]);
                }
            }
        }

        $this->command->info('Selesai membuat data dummy 2026!');
    }
}
