<?php

namespace Tests\Feature;

use App\Models\Angkatan;
use App\Models\Peserta;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PendaftaranWilayahTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PermissionSeeder::class, RoleSeeder::class]);
    }

    public function test_pendaftaran_wni_dengan_provinsi_dan_kabupaten(): void
    {
        $angkatan = Angkatan::create([
            'nama' => 'Angkatan Wilayah Test',
            'kode' => 'AK-W1',
            'tahun' => 2026,
            'kuota' => 10,
            'status' => 'persiapan',
        ]);

        $response = $this->post(route('pendaftaran.store'), [
            'tipe_pendaftar' => 'baru',
            'angkatan_id' => $angkatan->id,
            'paket_program' => '3_pekan',
            'nama' => 'Ahmad WNI',
            'nik' => '3208010101010099',
            'jenis_kelamin' => 'L',
            'kewarganegaraan' => 'WNI',
            'negara' => 'Indonesia',
            'provinsi' => 'Jawa Barat',
            'kabupaten_kota' => 'Kab. Kuningan',
            'tempat_lahir' => 'Kuningan',
            'tanggal_lahir' => '2005-05-05',
            'alamat' => 'Jl. Merdeka No. 45',
            'no_hp' => '081234567899',
            'email' => 'wni@test.id',
            'nama_wali' => 'Wali WNI',
            'no_hp_wali' => '081234567800',
            'ktp' => UploadedFile::fake()->create('ktp.jpg', 100),
            'persetujuan' => '1',
        ]);

        $response->assertRedirect(route('pendaftaran.sukses'));

        $this->assertDatabaseHas('peserta', [
            'nik' => '3208010101010099',
            'kewarganegaraan' => 'WNI',
            'negara' => 'Indonesia',
            'provinsi' => 'Jawa Barat',
            'kabupaten_kota' => 'Kab. Kuningan',
        ]);
    }

    public function test_pendaftaran_wna_luar_negeri(): void
    {
        $angkatan = Angkatan::create([
            'nama' => 'Angkatan WNA Test',
            'kode' => 'AK-W2',
            'tahun' => 2026,
            'kuota' => 10,
            'status' => 'persiapan',
        ]);

        $response = $this->post(route('pendaftaran.store'), [
            'angkatan_id' => $angkatan->id,
            'paket_program' => '3_pekan',
            'nama' => 'Muhammad WNA',
            'nik' => '9908010101010099',
            'jenis_kelamin' => 'L',
            'kewarganegaraan' => 'WNA',
            'negara' => 'Malaysia',
            'kabupaten_kota' => 'Kuala Lumpur',
            'tempat_lahir' => 'Kuala Lumpur',
            'tanggal_lahir' => '2004-04-04',
            'alamat' => 'Jalan Ampang No 10',
            'no_hp' => '081234567888',
            'email' => 'wna@test.id',
            'nama_wali' => 'Wali WNA',
            'no_hp_wali' => '081234567800',
            'ktp' => UploadedFile::fake()->create('passport.pdf', 100),
            'persetujuan' => '1',
        ]);

        $response->assertRedirect(route('pendaftaran.sukses'));

        $peserta = Peserta::where('nik', '9908010101010099')->firstOrFail();

        $this->assertEquals('WNA', $peserta->kewarganegaraan);
        $this->assertEquals('Malaysia', $peserta->negara);
        $this->assertEquals('Kuala Lumpur', $peserta->kabupaten_kota);
        $this->assertStringContainsString('Malaysia', $peserta->alamat_lengkap_formatted);
    }
}
