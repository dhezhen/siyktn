<?php

namespace Tests\Feature;

use App\Models\Angkatan;
use App\Models\Peserta;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PendaftaranAlumniTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PermissionSeeder::class, RoleSeeder::class]);
    }

    public function test_pendaftaran_alumni_dengan_nik_dan_tanggal_lahir_berhasil(): void
    {
        $angkatanLama = Angkatan::create([
            'nama' => 'Angkatan 1 (Lama)',
            'kode' => 'AK-01',
            'tahun' => 2025,
            'kuota' => 10,
            'status' => 'selesai',
        ]);

        $angkatanBaru = Angkatan::create([
            'nama' => 'Angkatan 2 (Baru)',
            'kode' => 'AK-02',
            'tahun' => 2026,
            'kuota' => 10,
            'status' => 'persiapan',
        ]);

        $pesertaAlumni = Peserta::create([
            'nama' => 'Santri Alumni',
            'nik' => '3208010101019999',
            'jenis_kelamin' => 'L',
            'kewarganegaraan' => 'WNI',
            'negara' => 'Indonesia',
            'provinsi' => 'Jawa Barat',
            'kabupaten_kota' => 'Kab. Kuningan',
            'tempat_lahir' => 'Kuningan',
            'tanggal_lahir' => '2000-01-01',
            'alamat' => 'Jl. Alumni No. 1',
            'no_hp' => '081234567890',
            'email' => 'alumni@test.id',
            'nama_wali' => 'Wali Alumni',
            'no_hp_wali' => '081234567800',
        ]);

        // Pendaftaran kilat alumni
        $response = $this->post(route('pendaftaran.store'), [
            'tipe_pendaftar' => 'alumni',
            'angkatan_id' => $angkatanBaru->id,
            'paket_program' => '3_pekan',
            'nik' => '3208010101019999',
            'tanggal_lahir' => '2000-01-01',
            'persetujuan' => '1',
        ]);

        $response->assertRedirect(route('pendaftaran.sukses'));

        $this->assertDatabaseHas('pendaftaran', [
            'peserta_id' => $pesertaAlumni->id,
            'angkatan_id' => $angkatanBaru->id,
            'status_pendaftaran' => 'menunggu',
        ]);
    }

    public function test_pendaftaran_alumni_gagal_jika_tanggal_lahir_salah(): void
    {
        $angkatanBaru = Angkatan::create([
            'nama' => 'Angkatan 2 (Baru)',
            'kode' => 'AK-02',
            'tahun' => 2026,
            'kuota' => 10,
            'status' => 'persiapan',
        ]);

        Peserta::create([
            'nama' => 'Santri Alumni',
            'nik' => '3208010101019999',
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '2000-01-01',
        ]);

        $response = $this->post(route('pendaftaran.store'), [
            'tipe_pendaftar' => 'alumni',
            'angkatan_id' => $angkatanBaru->id,
            'paket_program' => '3_pekan',
            'nik' => '3208010101019999',
            'tanggal_lahir' => '1999-12-31', // Tanggal lahir salah
            'persetujuan' => '1',
        ]);

        $response->assertSessionHasErrors(['tanggal_lahir']);
    }

    public function test_pendaftaran_alumni_gagal_jika_nik_belum_terdaftar(): void
    {
        $angkatanBaru = Angkatan::create([
            'nama' => 'Angkatan 2 (Baru)',
            'kode' => 'AK-02',
            'tahun' => 2026,
            'kuota' => 10,
            'status' => 'persiapan',
        ]);

        $response = $this->post(route('pendaftaran.store'), [
            'tipe_pendaftar' => 'alumni',
            'angkatan_id' => $angkatanBaru->id,
            'paket_program' => '3_pekan',
            'nik' => '3208010101010000', // NIK tidak ada
            'tanggal_lahir' => '2000-01-01',
            'persetujuan' => '1',
        ]);

        $response->assertSessionHasErrors(['nik']);
    }
}
