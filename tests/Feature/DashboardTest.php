<?php

namespace Tests\Feature;

use App\Models\AnggotaHalaqah;
use App\Models\Angkatan;
use App\Models\Halaqah;
use App\Models\Muhaffizh;
use App\Models\Pendaftaran;
use App\Models\Peserta;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Dashboard menyesuaikan diri dengan peran pembacanya.
 *
 * Bukan sekadar menyembunyikan kartu: muhaffizh mendapat dashboard tentang
 * bimbingannya, bukan angka pesantren dengan satu dua kartu miliknya diselipkan.
 */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

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

    protected function muhaffizhBerakun(): User
    {
        $muhaffizh = Muhaffizh::create([
            'kode' => 'MHF-001', 'nama' => 'Ustadz Uji',
            'jenis_kelamin' => 'L', 'status' => 'aktif',
        ]);

        $angkatan = Angkatan::create([
            'nama' => 'Angkatan Uji', 'kode' => 'AK-99', 'tahun' => 2026,
            'kuota' => 30, 'status' => 'berjalan',
        ]);

        $halaqah = Halaqah::create([
            'angkatan_id' => $angkatan->id, 'muhaffizh_id' => $muhaffizh->id,
            'kode' => 'H-01', 'nama' => 'Halaqah Uji', 'jenis_kelamin' => 'L',
            'kuota' => 10, 'is_aktif' => true,
        ]);

        $peserta = Peserta::create(['nama' => 'Santri Uji', 'jenis_kelamin' => 'L', 'nik' => '3200000000000001']);

        $pendaftaran = Pendaftaran::create([
            'peserta_id' => $peserta->id, 'angkatan_id' => $angkatan->id,
            'kode_pendaftaran' => 'REG-2026-0001', 'nomor_induk' => 'AK-99-0001',
            'status_pendaftaran' => 'disetujui', 'sumber_pendaftaran' => 'admin', 'status' => 'aktif',
        ]);

        AnggotaHalaqah::create([
            'halaqah_id' => $halaqah->id, 'pendaftaran_id' => $pendaftaran->id,
            'tanggal_bergabung' => now()->subMonth()->toDateString(), 'is_aktif' => true,
        ]);

        $akun = $this->pengguna('muhaffizh');
        $muhaffizh->update(['user_id' => $akun->id]);

        return $akun;
    }

    public function test_muhaffizh_mendapat_angka_bimbingannya_saja(): void
    {
        $this->actingAs($this->muhaffizhBerakun())->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Ringkasan halaqah dan setoran yang Anda bimbing.')
            ->assertSee('Halaqah Saya')
            ->assertSee('Santri Binaan')
            ->assertSee('Setoran Pekan Ini')
            ->assertSee('Ziyadah Terkumpul')
            // Angka pesantren bukan urusannya.
            ->assertDontSee('Total Peserta')
            ->assertDontSee('Muhaffizh Aktif')
            ->assertDontSee('Halaqah Berjalan')
            ->assertDontSee('Santri Belum Berhalaqah');
    }

    public function test_admin_mendapat_angka_sistem(): void
    {
        $this->actingAs($this->pengguna('admin'))->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Total Pengguna')
            ->assertSee('Halaqah Berjalan')
            ->assertSee('Santri Belum Berhalaqah')
            ->assertDontSee('Halaqah Saya')
            ->assertDontSee('Ziyadah Terkumpul');
    }

    public function test_operator_tanpa_angka_manajemen_pengguna(): void
    {
        $this->actingAs($this->pengguna('operator'))->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Halaqah Berjalan')
            ->assertDontSee('Total Pengguna')
            ->assertDontSee('Menu Aktif');
    }

    public function test_kartu_aktivitas_hanya_untuk_yang_berhak(): void
    {
        $this->actingAs($this->pengguna('admin'))->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Aktivitas Terakhir');

        // Tanpa izin, kartunya tidak ditampilkan sama sekali — bukan ditampilkan
        // kosong, yang keliru terbaca sebagai "belum ada aktivitas".
        $this->actingAs($this->pengguna('operator'))->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Aktivitas Terakhir');
    }

    public function test_pintasan_mengikuti_izin(): void
    {
        $this->actingAs($this->muhaffizhBerakun())->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('halaqah.index'))
            ->assertSee(route('setoran.index'))
            ->assertDontSee(route('user.index'));

        $this->actingAs($this->pengguna('admin'))->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('halaqah.index'));
    }

    public function test_role_terbatas_tetap_mendapat_halaman_yang_utuh(): void
    {
        $this->actingAs($this->pengguna('pengguna'))->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Selamat Datang')
            ->assertDontSee('Aktivitas Terakhir')
            ->assertDontSee('Sebaran Kualitas');
    }
}
