<?php

namespace Tests\Feature;

use App\Models\Angkatan;
use App\Models\Pendaftaran;
use App\Models\Peserta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PendaftaranPembayaranTest extends TestCase
{
    use RefreshDatabase;

    public function test_pendaftaran_baru_menyimpan_paket_program_dan_biaya(): void
    {
        Storage::fake('local');

        $angkatan = Angkatan::create([
            'nama' => 'Angkatan Test 2026',
            'kode' => 'ANGK-TEST-1',
            'tahun' => 2026,
            'status' => 'persiapan',
            'kuota' => 50,
            'kuota_putra' => 25,
            'kuota_putri' => 25,
        ]);

        $response = $this->post(route('pendaftaran.store'), [
            'tipe_pendaftar' => 'baru',
            'angkatan_id' => $angkatan->id,
            'paket_program' => '3_pekan',
            'nama' => 'Ahmad Santri',
            'nik' => '3208123456789012',
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Kuningan',
            'tanggal_lahir' => '2005-05-15',
            'kewarganegaraan' => 'WNI',
            'provinsi' => 'Jawa Barat',
            'kabupaten_kota' => 'Kab. Kuningan',
            'alamat' => 'Jl. Tahfizh No. 12',
            'no_hp' => '081234567890',
            'email' => 'ahmad@example.com',
            'nama_wali' => 'Bapak Santri',
            'no_hp_wali' => '081234567899',
            'ktp' => UploadedFile::fake()->create('ktp.jpg', 500, 'image/jpeg'),
            'persetujuan' => '1',
        ]);

        $response->assertRedirect(route('pendaftaran.sukses'));

        $this->assertDatabaseHas('pendaftaran', [
            'paket_program' => '3_pekan',
            'biaya_program' => 3250000,
            'biaya_pendaftaran' => 100000,
            'status_pembayaran_pendaftaran' => 'pending',
            'status_pembayaran_program' => 'pending',
        ]);
    }

    public function test_admin_bisa_validasi_pembayaran_pendaftaran_dan_program(): void
    {
        Permission::firstOrCreate(['name' => 'peserta.approve']);
        $admin = User::factory()->create();
        $admin->givePermissionTo('peserta.approve');

        $angkatan = Angkatan::create([
            'nama' => 'Angkatan Admin Test 2026',
            'kode' => 'ANGK-TEST-2',
            'tahun' => 2026,
            'status' => 'persiapan',
        ]);

        $peserta = Peserta::create([
            'nama' => 'Santri Test',
            'nik' => '3208123456789012',
            'jenis_kelamin' => 'L',
            'kewarganegaraan' => 'WNI',
            'kabupaten_kota' => 'Kuningan',
            'alamat' => 'Jl. Test',
            'no_hp' => '08123',
            'nama_wali' => 'Wali',
            'no_hp_wali' => '08123',
        ]);

        $pendaftaran = Pendaftaran::create([
            'peserta_id' => $peserta->id,
            'angkatan_id' => $angkatan->id,
            'kode_pendaftaran' => 'REG-TEST-001',
            'sumber_pendaftaran' => 'mandiri',
            'paket_program' => '1_bulan',
            'biaya_program' => 3750000,
            'biaya_pendaftaran' => 100000,
            'status_pembayaran_pendaftaran' => 'pending',
            'status_pembayaran_program' => 'pending',
            'didaftarkan_pada' => now(),
        ]);

        Livewire::actingAs($admin)
            ->test('pendaftaran-table')
            ->call('toggleBayarPendaftaran', $pendaftaran->id)
            ->call('ubahStatusBayarProgram', $pendaftaran->id, 'lunas');

        $pendaftaran->refresh();

        $this->assertEquals('lunas', $pendaftaran->status_pembayaran_pendaftaran);
        $this->assertEquals('lunas', $pendaftaran->status_pembayaran_program);
    }
}
