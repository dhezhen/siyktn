<?php

namespace Tests\Feature;

use App\Models\Angkatan;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProgramCrudTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'program.view']);
        Permission::firstOrCreate(['name' => 'program.create']);
        Permission::firstOrCreate(['name' => 'program.update']);
        Permission::firstOrCreate(['name' => 'program.delete']);

        $this->admin = User::factory()->create();
        $this->admin->givePermissionTo(['program.view', 'program.create', 'program.update', 'program.delete']);
    }

    public function test_admin_dapat_melihat_daftar_program(): void
    {
        Program::create([
            'nama' => 'Program Test 1 Pekan',
            'kode' => 'PROG-TEST-1W',
            'durasi_hari' => 7,
            'biaya_program' => 2000000,
            'biaya_pendaftaran' => 100000,
            'is_aktif' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('program.index'));

        $response->assertStatus(200);
        $response->assertSee('Program Test 1 Pekan');
        $response->assertSee('PROG-TEST-1W');
    }

    public function test_admin_dapat_menambah_program_baru(): void
    {
        $response = $this->actingAs($this->admin)->post(route('program.store'), [
            'nama' => 'Program Executive Tahfizh',
            'kode' => 'PROG-EXEC',
            'durasi_hari' => 14,
            'biaya_program' => 5000000,
            'biaya_pendaftaran' => 150000,
            'is_aktif' => '1',
            'keterangan' => 'Program eksekutif fasilitas AC',
        ]);

        $response->assertRedirect(route('program.index'));

        $this->assertDatabaseHas('program', [
            'nama' => 'Program Executive Tahfizh',
            'kode' => 'PROG-EXEC',
            'biaya_program' => 5000000,
            'biaya_pendaftaran' => 150000,
            'is_aktif' => true,
        ]);
    }

    public function test_admin_dapat_mengubah_program(): void
    {
        $program = Program::create([
            'nama' => 'Program Lama',
            'kode' => 'PROG-OLD',
            'durasi_hari' => 30,
            'biaya_program' => 3000000,
            'biaya_pendaftaran' => 100000,
            'is_aktif' => true,
        ]);

        $response = $this->actingAs($this->admin)->put(route('program.update', $program), [
            'nama' => 'Program Baru Diubah',
            'kode' => 'PROG-OLD',
            'durasi_hari' => 30,
            'biaya_program' => 3500000,
            'biaya_pendaftaran' => 100000,
            'is_aktif' => '1',
        ]);

        $response->assertRedirect(route('program.index'));

        $this->assertDatabaseHas('program', [
            'id' => $program->id,
            'nama' => 'Program Baru Diubah',
            'biaya_program' => 3500000,
        ]);
    }

    public function test_admin_dapat_menghapus_program(): void
    {
        $program = Program::create([
            'nama' => 'Program Dihapus',
            'kode' => 'PROG-DEL',
            'durasi_hari' => 7,
            'biaya_program' => 1000000,
            'biaya_pendaftaran' => 100000,
            'is_aktif' => true,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('program.destroy', $program));

        $response->assertRedirect(route('program.index'));

        $this->assertSoftDeleted('program', [
            'id' => $program->id,
        ]);
    }

    public function test_pendaftaran_mandiri_menggunakan_master_program(): void
    {
        $program = Program::create([
            'nama' => 'Master Program 2 Pekan',
            'kode' => 'PROG-M2W',
            'durasi_hari' => 14,
            'biaya_program' => 2500000,
            'biaya_pendaftaran' => 100000,
            'is_aktif' => true,
        ]);

        $angkatan = Angkatan::create([
            'nama' => 'Angkatan 2026',
            'kode' => 'AK-2026',
            'tahun' => 2026,
            'status' => 'persiapan',
            'kuota' => 50,
        ]);

        $response = $this->post(route('pendaftaran.store'), [
            'tipe_pendaftar' => 'baru',
            'angkatan_id' => $angkatan->id,
            'paket_program' => 'PROG-M2W',
            'nama' => 'Santri Master',
            'nik' => '3208123456789099',
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Kuningan',
            'tanggal_lahir' => '2005-05-15',
            'kewarganegaraan' => 'WNI',
            'provinsi' => 'Jawa Barat',
            'kabupaten_kota' => 'Kab. Kuningan',
            'alamat' => 'Jl. Master',
            'no_hp' => '081234567890',
            'email' => 'master@example.com',
            'nama_wali' => 'Bapak Master',
            'no_hp_wali' => '081234567899',
            'ktp' => \Illuminate\Http\UploadedFile::fake()->create('ktp.jpg', 100),
            'persetujuan' => '1',
        ]);

        $response->assertRedirect(route('pendaftaran.sukses'));

        $this->assertDatabaseHas('pendaftaran', [
            'program_id' => $program->id,
            'biaya_program' => 2500000,
            'biaya_pendaftaran' => 100000,
        ]);
    }
}
