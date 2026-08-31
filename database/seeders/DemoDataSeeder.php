<?php

namespace Database\Seeders;

use App\Models\AnggotaHalaqah;
use App\Models\Angkatan;
use App\Models\Halaqah;
use App\Models\Muhaffizh;
use App\Models\Pendaftaran;
use App\Models\Peserta;
use App\Models\Setoran;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Data contoh untuk pengembangan.
 *
 * Sengaja dilewati di lingkungan production agar database sungguhan
 * tidak ikut terisi data karangan.
 *
 * Tiap bagian punya penjaganya sendiri supaya modul baru tetap dapat data
 * contoh walau angkatan dan pesertanya sudah lebih dulu di-seed.
 */
class DemoDataSeeder extends Seeder
{
    /** @var array<int, string> */
    protected array $namaDepanL = ['Ahmad', 'Muhammad', 'Abdul', 'Umar', 'Ali', 'Ibrahim', 'Yusuf', 'Hasan', 'Husein', 'Ridwan'];

    /** @var array<int, string> */
    protected array $namaDepanP = ['Siti', 'Nur', 'Fatimah', 'Aisyah', 'Zainab', 'Khadijah', 'Maryam', 'Salma'];

    /** @var array<int, string> */
    protected array $namaBelakang = ['Hidayat', 'Ramadhan', 'Nugroho', 'Wijaya', 'Santoso', 'Kurniawan', 'Lestari',
        'Puspita', 'Anggraini', 'Setiawan', 'Firdaus', 'Rahmawati'];

    /** @var array<int, string> */
    protected array $kota = ['Kuningan', 'Cirebon', 'Bandung', 'Majalengka', 'Indramayu', 'Garut'];

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('DemoDataSeeder dilewati di environment production.');

            return;
        }

        $this->angkatanDanPeserta();
        $this->muhaffizhDanHalaqah();
        $this->akunMuhaffizhContoh();
        $this->setoranContoh();
    }

    /**
     * Riwayat setoran contoh untuk halaqah yang masih berjalan, supaya rekap
     * hafalan ada isinya saat dicoba.
     */
    protected function setoranContoh(): void
    {
        if (Setoran::exists()) {
            $this->command?->info('Setoran contoh sudah ada, dilewati.');

            return;
        }

        $anggota = AnggotaHalaqah::with('halaqah')->where('is_aktif', true)->get();

        if ($anggota->isEmpty()) {
            return;
        }

        $surah = ['Al-Baqarah', 'Ali Imran', 'An-Nisa', 'Al-Maidah', 'Al-Anam', 'Al-Araf'];
        $kualitas = ['mumtaz', 'jayyid', 'jayyid', 'maqbul', 'perlu_diulang'];
        $jumlah = 0;

        foreach ($anggota as $item) {
            // Sekitar tiga pekan setoran, lima hari sepekan.
            for ($hari = 21; $hari >= 1; $hari--) {
                $tanggal = now()->subDays($hari);

                if ($tanggal->isFriday()) {
                    continue;
                }

                Setoran::create([
                    'anggota_halaqah_id' => $item->id,
                    'muhaffizh_id' => $item->halaqah?->muhaffizh_id,
                    'dicatat_oleh' => null,
                    'tanggal' => $tanggal->toDateString(),
                    'jenis' => $hari % 4 === 0 ? 'murajaah' : 'ziyadah',
                    'jumlah_halaman' => [0.5, 1, 1, 1.5, 2][array_rand([0.5, 1, 1, 1.5, 2])],
                    'juz' => rand(1, 5),
                    'surah' => $surah[array_rand($surah)],
                    'ayat_dari' => rand(1, 40),
                    'ayat_sampai' => rand(41, 90),
                    'kualitas' => $kualitas[array_rand($kualitas)],
                ]);

                $jumlah++;
            }
        }

        $this->command?->info($jumlah.' setoran contoh dibuat.');
    }

    protected function angkatanDanPeserta(): void
    {
        if (Angkatan::exists()) {
            $this->command?->info('Angkatan dan peserta contoh sudah ada, dilewati.');

            return;
        }

        $angkatanList = [
            ['nama' => 'Angkatan 10', 'kode' => 'AK-10', 'tahun' => 2024, 'status' => 'selesai', 'kuota' => 30],
            ['nama' => 'Angkatan 11', 'kode' => 'AK-11', 'tahun' => 2025, 'status' => 'berjalan', 'kuota' => 30],
            ['nama' => 'Angkatan 12', 'kode' => 'AK-12', 'tahun' => 2026, 'status' => 'persiapan', 'kuota' => 25],
        ];

        // NIK contoh dibuat berurutan supaya tetap unik antar peserta.
        $nomorUrutNik = 1;

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

                $peserta = Peserta::create([
                    'nama' => $this->namaAcak($jenisKelamin),
                    'nik' => '32'.str_pad((string) $nomorUrutNik++, 14, '0', STR_PAD_LEFT),
                    'jenis_kelamin' => $jenisKelamin,
                    'tempat_lahir' => $this->kota[array_rand($this->kota)],
                    'tanggal_lahir' => now()->subYears(rand(15, 22))->subDays(rand(0, 364)),
                    'alamat' => 'Jl. Contoh No. '.rand(1, 99).', '.$this->kota[array_rand($this->kota)],
                    'no_hp' => '08'.rand(1000000000, 9999999999),
                    'nama_wali' => $this->namaAcak('L'),
                    'no_hp_wali' => '08'.rand(1000000000, 9999999999),
                ]);

                Pendaftaran::create([
                    'peserta_id' => $peserta->id,
                    'angkatan_id' => $angkatan->id,
                    'kode_pendaftaran' => sprintf('DEMO-%s-%04d', $angkatan->kode, $i),
                    'nomor_induk' => sprintf('%s-%04d', $angkatan->kode, $i),
                    'status_pendaftaran' => 'disetujui',
                    'sumber_pendaftaran' => 'admin',
                    'tanggal_masuk' => $angkatan->tanggal_mulai,
                    'didaftarkan_pada' => $angkatan->tanggal_mulai,
                    'ditinjau_pada' => $angkatan->tanggal_mulai,
                    'status' => $data['status'] === 'selesai'
                        ? (rand(1, 10) > 2 ? 'lulus' : 'keluar')
                        : 'aktif',
                ]);
            }
        }

        $this->command?->info(Angkatan::count().' angkatan, '.Peserta::count().' peserta, dan '
            .Pendaftaran::count().' pendaftaran contoh dibuat.');
    }

    protected function muhaffizhDanHalaqah(): void
    {
        if (Muhaffizh::exists()) {
            $this->command?->info('Muhaffizh dan halaqah contoh sudah ada, dilewati.');

            return;
        }

        $muhaffizh = collect([
            ['nama' => 'Ustadz Abdurrahman Hakim', 'jenis_kelamin' => 'L', 'sanad_riwayat' => "Hafsh 'an 'Ashim", 'pendidikan' => "S1 Ilmu Al-Qur'an"],
            ['nama' => 'Ustadz Fauzan Maulana', 'jenis_kelamin' => 'L', 'sanad_riwayat' => "Hafsh 'an 'Ashim", 'pendidikan' => 'S1 Pendidikan Agama Islam'],
            ['nama' => 'Ustadz Syamsul Arifin', 'jenis_kelamin' => 'L', 'sanad_riwayat' => "Qalun 'an Nafi'", 'pendidikan' => 'S2 Ulumul Qur\'an'],
            ['nama' => 'Ustadzah Halimah Zahra', 'jenis_kelamin' => 'P', 'sanad_riwayat' => "Hafsh 'an 'Ashim", 'pendidikan' => "S1 Ilmu Al-Qur'an"],
            ['nama' => 'Ustadzah Nailah Rahmani', 'jenis_kelamin' => 'P', 'sanad_riwayat' => "Hafsh 'an 'Ashim", 'pendidikan' => 'S1 Pendidikan Bahasa Arab'],
        ])->map(fn (array $data, int $urutan) => Muhaffizh::create(array_merge($data, [
            'kode' => sprintf('MHF-%03d', $urutan + 1),
            'no_hp' => '08'.rand(1000000000, 9999999999),
            'tanggal_bergabung' => now()->subYears(rand(1, 5))->startOfMonth(),
            'status' => 'aktif',
            'keterangan' => 'Data contoh untuk pengembangan.',
        ])));

        $namaHalaqah = ['Al-Fatih', 'An-Nur', 'Al-Furqan', 'Ar-Rahman', 'Al-Hikmah', 'Al-Bayan'];

        // Hanya angkatan yang sudah/sedang berjalan yang punya halaqah —
        // angkatan berstatus persiapan memang belum membagi kelompok.
        $angkatanList = Angkatan::whereIn('status', ['berjalan', 'selesai'])->orderBy('tahun')->get();

        $jumlahHalaqah = 0;
        $jumlahAnggota = 0;

        foreach ($angkatanList as $angkatan) {
            $sudahSelesai = $angkatan->status === 'selesai';
            $urutan = 0;

            // Dua halaqah ikhwan dan satu akhwat: jumlah santri contoh memang
            // lebih banyak laki-laki.
            foreach (['L', 'L', 'P'] as $jenisKelamin) {
                $urutan++;

                $pengampu = $muhaffizh->where('jenis_kelamin', $jenisKelamin)->values();

                $halaqah = Halaqah::create([
                    'angkatan_id' => $angkatan->id,
                    'muhaffizh_id' => $pengampu[($urutan - 1) % $pengampu->count()]->id,
                    'kode' => sprintf('H-%02d', $urutan),
                    'nama' => 'Halaqah '.$namaHalaqah[($urutan - 1) % count($namaHalaqah)],
                    'jenis_kelamin' => $jenisKelamin,
                    'kuota' => 8,
                    'ruang' => $jenisKelamin === 'L' ? 'Masjid Lantai 1' : 'Aula Putri',
                    'jadwal' => "Ba'da Shubuh & Ba'da Ashar",
                    'is_aktif' => ! $sudahSelesai,
                    'keterangan' => 'Data contoh untuk pengembangan.',
                ]);

                $jumlahHalaqah++;
                $jumlahAnggota += $this->isiHalaqah($halaqah, $angkatan, $sudahSelesai);
            }
        }

        $this->command?->info($muhaffizh->count().' muhaffizh, '.$jumlahHalaqah.' halaqah, dan '
            .$jumlahAnggota.' keanggotaan contoh dibuat.');
    }

    /**
     * Tempatkan santri yang belum berhalaqah ke halaqah ini sampai kuotanya
     * hampir penuh — sengaja disisakan satu kursi supaya panel "Tempatkan
     * Santri" di layar detail ada isinya saat dicoba.
     */
    protected function isiHalaqah(Halaqah $halaqah, Angkatan $angkatan, bool $sudahSelesai): int
    {
        $calon = Pendaftaran::query()
            ->where('angkatan_id', $angkatan->id)
            ->whereDoesntHave('anggotaHalaqah')
            ->whereHas('peserta', fn ($q) => $q->where('jenis_kelamin', $halaqah->jenis_kelamin))
            ->orderBy('nomor_induk')
            ->limit($halaqah->kuota - 1)
            ->get();

        foreach ($calon as $pendaftaran) {
            AnggotaHalaqah::create([
                'halaqah_id' => $halaqah->id,
                'pendaftaran_id' => $pendaftaran->id,
                'tanggal_bergabung' => $angkatan->tanggal_mulai,
                // Angkatan yang sudah selesai: keanggotaannya ikut ditutup,
                // sehingga tampil sebagai riwayat, bukan santri aktif.
                'tanggal_keluar' => $sudahSelesai ? $angkatan->tanggal_selesai : null,
                'is_aktif' => ! $sudahSelesai,
                'alasan_pindah' => $sudahSelesai ? 'Program angkatan berakhir.' : null,
            ]);
        }

        return $calon->count();
    }

    /**
     * Satu muhaffizh contoh diberi akun login supaya alurnya bisa dicoba tanpa
     * harus membuat akun manual. Rolenya menempel sendiri lewat Muhaffizh::booted().
     */
    protected function akunMuhaffizhContoh(): void
    {
        // Dipilih yang benar-benar mengampu halaqah berjalan; akun contoh yang
        // hanya kebagian halaqah nonaktif tidak ada gunanya untuk dicoba.
        $muhaffizh = Muhaffizh::whereHas('halaqah', fn ($q) => $q->where('is_aktif', true))
            ->orderBy('kode')
            ->first() ?? Muhaffizh::orderBy('kode')->first();

        if (! $muhaffizh || Muhaffizh::whereNotNull('user_id')->exists()) {
            return;
        }

        $akun = User::updateOrCreate(
            ['username' => 'muhaffizh'],
            [
                'name' => $muhaffizh->nama,
                'email' => 'muhaffizh@siyktn.test',
                'password' => 'password',
                'is_active' => true,
                'must_change_password' => false,
                'email_verified_at' => now(),
            ]
        );

        $muhaffizh->update([
            'email' => $akun->email,
            'user_id' => $akun->id,
        ]);

        $this->command?->info("Akun contoh muhaffizh: {$akun->username} (kata sandi: password).");
    }

    protected function namaAcak(string $jenisKelamin): string
    {
        $depan = $jenisKelamin === 'P' ? $this->namaDepanP : $this->namaDepanL;

        return $depan[array_rand($depan)].' '.$this->namaBelakang[array_rand($this->namaBelakang)];
    }
}
