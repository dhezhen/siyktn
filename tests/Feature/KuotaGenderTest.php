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

class KuotaGenderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PermissionSeeder::class, RoleSeeder::class]);
    }

    protected function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    public function test_angkatan_dapat_disimpan_dengan_kuota_gender(): void
    {
        $response = $this->actingAs($this->admin())->post(route('angkatan.store'), [
            'nama' => 'Angkatan Test Quota',
            'kode' => 'AK-Q1',
            'tahun' => 2026,
            'kuota' => 20,
            'kuota_putra' => 10,
            'kuota_putri' => 10,
            'status' => 'persiapan',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('angkatan', [
            'kode' => 'AK-Q1',
            'kuota' => 20,
            'kuota_putra' => 10,
            'kuota_putri' => 10,
        ]);
    }

    public function test_pendaftaran_memperhitungkan_kuota_putra_dan_putri(): void
    {
        $angkatan = Angkatan::create([
            'nama' => 'Angkatan Khusus',
            'kode' => 'AK-KH',
            'tahun' => 2026,
            'kuota' => 0,
            'kuota_putra' => 1,
            'kuota_putri' => 1,
            'status' => 'persiapan',
        ]);

        // Putra pertama mendaftar
        $respPutra1 = $this->post(route('pendaftaran.store'), [
            'angkatan_id' => $angkatan->id,
            'paket_program' => '3_pekan',
            'nama' => 'Ahmad Putra 1',
            'nik' => '3208010101010001',
            'jenis_kelamin' => 'L',
            'kewarganegaraan' => 'WNI',
            'negara' => 'Indonesia',
            'provinsi' => 'Jawa Barat',
            'kabupaten_kota' => 'Kab. Kuningan',
            'tempat_lahir' => 'Kuningan',
            'tanggal_lahir' => '2005-01-01',
            'alamat' => 'Alamat Putra 1',
            'no_hp' => '081234567891',
            'email' => 'putra1@test.id',
            'nama_wali' => 'Wali Putra 1',
            'no_hp_wali' => '081234567890',
            'ktp' => \Illuminate\Http\UploadedFile::fake()->create('ktp.jpg', 100),
            'persetujuan' => '1',
        ]);

        $respPutra1->assertRedirect(route('pendaftaran.sukses'));
        $this->assertEquals(0, $angkatan->fresh()->sisa_kuota_putra);

        // Putra kedua mendaftar -> Harus gagal karena kuota putra (1) sudah penuh
        $respPutra2 = $this->post(route('pendaftaran.store'), [
            'angkatan_id' => $angkatan->id,
            'paket_program' => '3_pekan',
            'nama' => 'Ali Putra 2',
            'nik' => '3208010101010002',
            'jenis_kelamin' => 'L',
            'kewarganegaraan' => 'WNI',
            'negara' => 'Indonesia',
            'provinsi' => 'Jawa Barat',
            'kabupaten_kota' => 'Kab. Kuningan',
            'tempat_lahir' => 'Kuningan',
            'tanggal_lahir' => '2005-02-02',
            'alamat' => 'Alamat Putra 2',
            'no_hp' => '081234567892',
            'email' => 'putra2@test.id',
            'nama_wali' => 'Wali Putra 2',
            'no_hp_wali' => '081234567890',
            'ktp' => \Illuminate\Http\UploadedFile::fake()->create('ktp.jpg', 100),
            'persetujuan' => '1',
        ]);

        $respPutra2->assertSessionHasErrors(['angkatan_id']);

        // Putri pertama mendaftar -> Harus berhasil karena kuota putri (1) masih sisa
        $respPutri1 = $this->post(route('pendaftaran.store'), [
            'angkatan_id' => $angkatan->id,
            'paket_program' => '3_pekan',
            'nama' => 'Siti Putri 1',
            'nik' => '3208010101010003',
            'jenis_kelamin' => 'P',
            'kewarganegaraan' => 'WNI',
            'negara' => 'Indonesia',
            'provinsi' => 'Jawa Barat',
            'kabupaten_kota' => 'Kota Cirebon',
            'tempat_lahir' => 'Cirebon',
            'tanggal_lahir' => '2005-03-03',
            'alamat' => 'Alamat Putri 1',
            'no_hp' => '081234567893',
            'email' => 'putri1@test.id',
            'nama_wali' => 'Wali Putri 1',
            'no_hp_wali' => '081234567890',
            'ktp' => \Illuminate\Http\UploadedFile::fake()->create('ktp.jpg', 100),
            'persetujuan' => '1',
        ]);

        $respPutri1->assertRedirect(route('pendaftaran.sukses'));
        $this->assertEquals(0, $angkatan->fresh()->sisa_kuota_putri);
    }
}
