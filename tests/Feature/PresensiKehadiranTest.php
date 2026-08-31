<?php

namespace Tests\Feature;

use App\Models\Angkatan;
use App\Models\Pendaftaran;
use App\Models\Peserta;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PresensiKehadiranTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PermissionSeeder::class, RoleSeeder::class]);
    }

    public function test_admin_bisa_konfirmasi_kehadiran_peserta(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $angkatan = Angkatan::create([
            'nama' => 'Angkatan Presensi',
            'kode' => 'AK-P1',
            'tahun' => 2026,
            'kuota' => 10,
            'status' => 'berjalan',
        ]);

        $peserta = Peserta::create([
            'nama' => 'Santri Presensi Test',
            'nik' => '3208010101017777',
            'jenis_kelamin' => 'L',
        ]);

        $pendaftaran = Pendaftaran::create([
            'peserta_id' => $peserta->id,
            'angkatan_id' => $angkatan->id,
            'kode_pendaftaran' => 'REG-2026-7777',
            'status_pendaftaran' => 'disetujui',
            'status_kehadiran' => 'belum_hadir',
        ]);

        $response = $this->actingAs($admin)
            ->post(route('pendaftaran.konfirmasi-kehadiran', $pendaftaran));

        $response->assertRedirect();

        $this->assertEquals('hadir', $pendaftaran->fresh()->status_kehadiran);
        $this->assertNotNull($pendaftaran->fresh()->waktu_kehadiran);
        $this->assertEquals($admin->id, $pendaftaran->fresh()->diverifikasi_oleh);
    }

    public function test_konfirmasi_kehadiran_via_ajax_scan_qr(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $angkatan = Angkatan::create([
            'nama' => 'Angkatan QR Test',
            'kode' => 'AK-P2',
            'tahun' => 2026,
            'kuota' => 10,
            'status' => 'berjalan',
        ]);

        $peserta = Peserta::create([
            'nama' => 'Santri QR Scan',
            'nik' => '3208010101018888',
            'jenis_kelamin' => 'P',
        ]);

        $pendaftaran = Pendaftaran::create([
            'peserta_id' => $peserta->id,
            'angkatan_id' => $angkatan->id,
            'kode_pendaftaran' => 'REG-2026-8888',
            'status_pendaftaran' => 'disetujui',
            'status_kehadiran' => 'belum_hadir',
        ]);

        $response = $this->actingAs($admin)
            ->postJson(route('pendaftaran.konfirmasi-kehadiran'), [
                'kode' => 'REG-2026-8888',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'kode_pendaftaran' => 'REG-2026-8888',
                    'nama' => 'Santri QR Scan',
                ],
            ]);

        $this->assertEquals('hadir', $pendaftaran->fresh()->status_kehadiran);
    }
}
