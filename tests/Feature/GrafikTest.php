<?php

namespace Tests\Feature;

use App\Models\AnggotaHalaqah;
use App\Models\Angkatan;
use App\Models\Halaqah;
use App\Models\Muhaffizh;
use App\Models\Pendaftaran;
use App\Models\Peserta;
use App\Models\Setoran;
use App\Models\User;
use App\Support\Grafik;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Grafik dashboard.
 *
 * Yang dijaga di sini bukan keindahannya, melainkan tiga hal yang diam-diam
 * bisa salah: angkanya ikut pembatasan data, geometrinya tidak keluar bingkai,
 * dan setiap nilai tetap terjangkau tanpa mengarahkan tetikus.
 */
class GrafikTest extends TestCase
{
    use RefreshDatabase;

    protected int $urutan = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PermissionSeeder::class, RoleSeeder::class]);
    }

    protected function pengguna(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    protected function halaqah(Muhaffizh $muhaffizh, string $kode): Halaqah
    {
        $angkatan = Angkatan::firstOrCreate(
            ['kode' => 'AK-99'],
            ['nama' => 'Angkatan Uji', 'tahun' => 2026, 'kuota' => 30, 'status' => 'berjalan']
        );

        return Halaqah::create([
            'angkatan_id' => $angkatan->id,
            'muhaffizh_id' => $muhaffizh->id,
            'kode' => $kode,
            'nama' => 'Halaqah '.$kode,
            'jenis_kelamin' => 'L',
            'kuota' => 10,
            'is_aktif' => true,
        ]);
    }

    protected function santri(Halaqah $halaqah, string $nama): AnggotaHalaqah
    {
        $urut = ++$this->urutan;

        $peserta = Peserta::create([
            'nama' => $nama,
            'jenis_kelamin' => 'L',
            'nik' => str_pad((string) $urut, 16, '3', STR_PAD_LEFT),
        ]);

        $pendaftaran = Pendaftaran::create([
            'peserta_id' => $peserta->id,
            'angkatan_id' => $halaqah->angkatan_id,
            'kode_pendaftaran' => sprintf('REG-2026-%04d', $urut),
            'nomor_induk' => sprintf('AK-99-%04d', $urut),
            'status_pendaftaran' => 'disetujui',
            'sumber_pendaftaran' => 'admin',
            'status' => 'aktif',
        ]);

        return AnggotaHalaqah::create([
            'halaqah_id' => $halaqah->id,
            'pendaftaran_id' => $pendaftaran->id,
            'tanggal_bergabung' => now()->subMonth()->toDateString(),
            'is_aktif' => true,
        ]);
    }

    protected function setoran(AnggotaHalaqah $anggota, float $halaman, string $jenis = 'ziyadah'): void
    {
        Setoran::create([
            'anggota_halaqah_id' => $anggota->id,
            'muhaffizh_id' => $anggota->halaqah->muhaffizh_id,
            'tanggal' => now()->subDays(2)->toDateString(),
            'jenis' => $jenis,
            'jumlah_halaman' => $halaman,
            'kualitas' => 'jayyid',
        ]);
    }

    public function test_grafik_muhaffizh_hanya_memuat_santri_bimbingannya(): void
    {
        $saya = Muhaffizh::create(['kode' => 'MHF-001', 'nama' => 'Ustadz Saya', 'jenis_kelamin' => 'L', 'status' => 'aktif']);
        $lain = Muhaffizh::create(['kode' => 'MHF-002', 'nama' => 'Ustadz Lain', 'jenis_kelamin' => 'L', 'status' => 'aktif']);

        $this->setoran($this->santri($this->halaqah($saya, 'H-01'), 'Santri Bimbingan Saya'), 4);
        $this->setoran($this->santri($this->halaqah($lain, 'H-02'), 'Santri Orang Lain'), 9);

        $akun = $this->pengguna('muhaffizh');
        $saya->update(['user_id' => $akun->id]);

        $html = $this->actingAs($akun)->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('Setoran Bimbingan Saya', $html);
        $this->assertStringContainsString('Santri Bimbingan Saya', $html);
        $this->assertStringNotContainsString('Santri Orang Lain', $html,
            'Grafik tidak boleh jadi celah untuk mengintip santri halaqah lain.');
    }

    public function test_grafik_admin_memuat_seluruh_halaqah(): void
    {
        $satu = Muhaffizh::create(['kode' => 'MHF-001', 'nama' => 'Ustadz Satu', 'jenis_kelamin' => 'L', 'status' => 'aktif']);
        $dua = Muhaffizh::create(['kode' => 'MHF-002', 'nama' => 'Ustadz Dua', 'jenis_kelamin' => 'L', 'status' => 'aktif']);

        $this->setoran($this->santri($this->halaqah($satu, 'H-01'), 'Santri A'), 4);
        $this->setoran($this->santri($this->halaqah($dua, 'H-02'), 'Santri B'), 9);

        $html = $this->actingAs($this->pengguna('admin'))->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('Setoran Seluruh Halaqah', $html);
        $this->assertStringContainsString('Halaqah H-01', $html);
        $this->assertStringContainsString('Halaqah H-02', $html);
    }

    public function test_geometri_svg_tetap_di_dalam_bingkai(): void
    {
        $muhaffizh = Muhaffizh::create(['kode' => 'MHF-001', 'nama' => 'Ustadz Uji', 'jenis_kelamin' => 'L', 'status' => 'aktif']);
        $halaqah = $this->halaqah($muhaffizh, 'H-01');

        // Nilai yang timpang: batang terpanjang harus tetap muat.
        $this->setoran($this->santri($halaqah, 'Santri Besar'), 60);
        $this->setoran($this->santri($halaqah, 'Santri Kecil'), 0.5);

        $html = $this->actingAs($this->pengguna('admin'))->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringNotContainsString('NaN', $html, 'Ada pembagian nol yang bocor ke atribut SVG.');

        preg_match_all('/<text x="(-?[\d.]+)"/', $html, $cocok);
        $posisi = array_map('floatval', $cocok[1]);

        $this->assertNotEmpty($posisi);
        $this->assertGreaterThanOrEqual(0, min($posisi), 'Ada teks yang keluar dari sisi kiri bingkai.');
        $this->assertLessThanOrEqual(720, max($posisi), 'Ada teks yang keluar dari sisi kanan bingkai.');
    }

    public function test_setiap_nilai_terjangkau_tanpa_hover(): void
    {
        $muhaffizh = Muhaffizh::create(['kode' => 'MHF-001', 'nama' => 'Ustadz Uji', 'jenis_kelamin' => 'L', 'status' => 'aktif']);
        $this->setoran($this->santri($this->halaqah($muhaffizh, 'H-01'), 'Santri A'), 3);

        $html = $this->actingAs($this->pengguna('admin'))->get(route('dashboard'))->assertOk()->getContent();

        // Grafik garis dan grafik batang masing-masing menyediakan tabel.
        $this->assertSame(2, substr_count($html, 'Lihat sebagai tabel'));

        // Sebaran kualitas memakai legenda berangka, karena dua warnanya berada
        // di bawah kontras 3:1 dan tidak boleh berdiri sendiri.
        $this->assertStringContainsString('Mumtaz', $html);
        $this->assertStringContainsString('Perlu Diulang', $html);
    }

    public function test_dashboard_tanpa_setoran_tidak_menampilkan_grafik_kosong(): void
    {
        $html = $this->actingAs($this->pengguna('admin'))->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('Belum ada setoran delapan pekan terakhir', $html);
    }

    public function test_pengguna_tanpa_izin_setoran_tidak_mendapat_grafik(): void
    {
        $html = $this->actingAs($this->pengguna('pengguna'))->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringNotContainsString('Setoran Seluruh Halaqah', $html);
        $this->assertStringNotContainsString('Sebaran Kualitas', $html);
    }

    public function test_batas_atas_sumbu_dibulatkan(): void
    {
        $this->assertSame(10.0, (float) Grafik::batasAtas(7.5));
        $this->assertSame(2.0, (float) Grafik::batasAtas(1.2));
        $this->assertSame(1.0, (float) Grafik::batasAtas(0));
        $this->assertSame(50.0, (float) Grafik::batasAtas(41));
    }

    public function test_angka_ditulis_gaya_indonesia(): void
    {
        $this->assertSame('1,5', Grafik::angka(1.5));
        $this->assertSame('12', Grafik::angka(12));
        $this->assertSame('1.240', Grafik::angka(1240));
    }
}
