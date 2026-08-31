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
 * Aturan penempatan santri di halaqah — bagian modul yang paling mudah
 * rusak diam-diam, karena kesalahannya baru terasa saat rekap hafalan.
 */
class HalaqahTest extends TestCase
{
    use RefreshDatabase;

    protected int $urutan = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PermissionSeeder::class, RoleSeeder::class]);
    }

    public function test_santri_terpilih_ditempatkan_di_halaqah(): void
    {
        $angkatan = $this->angkatan();
        $halaqah = $this->halaqah($angkatan);
        $santri = $this->santri($angkatan);

        $this->actingAs($this->petugas())
            ->post(route('halaqah.anggota.store', $halaqah), ['pendaftaran_id' => [$santri->id]])
            ->assertRedirect();

        $this->assertDatabaseHas('anggota_halaqah', [
            'halaqah_id' => $halaqah->id,
            'pendaftaran_id' => $santri->id,
            'is_aktif' => true,
        ]);
    }

    public function test_santri_beda_jenis_kelamin_tidak_bisa_masuk(): void
    {
        $angkatan = $this->angkatan();
        $halaqahIkhwan = $this->halaqah($angkatan, ['jenis_kelamin' => 'L']);
        $akhwat = $this->santri($angkatan, 'P');

        $this->actingAs($this->petugas())
            ->post(route('halaqah.anggota.store', $halaqahIkhwan), ['pendaftaran_id' => [$akhwat->id]])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('anggota_halaqah', 0);
    }

    public function test_santri_angkatan_lain_tidak_bisa_masuk(): void
    {
        $angkatan = $this->angkatan();
        $angkatanLain = $this->angkatan('AK-98');
        $halaqah = $this->halaqah($angkatan);
        $santri = $this->santri($angkatanLain);

        $this->actingAs($this->petugas())
            ->post(route('halaqah.anggota.store', $halaqah), ['pendaftaran_id' => [$santri->id]])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('anggota_halaqah', 0);
    }

    public function test_penempatan_melebihi_kuota_ditolak_seluruhnya(): void
    {
        $angkatan = $this->angkatan();
        $halaqah = $this->halaqah($angkatan, ['kuota' => 2]);

        $tiga = collect([1, 2, 3])->map(fn () => $this->santri($angkatan)->id)->all();

        $this->actingAs($this->petugas())
            ->post(route('halaqah.anggota.store', $halaqah), ['pendaftaran_id' => $tiga])
            ->assertSessionHas('error');

        // Ditolak seluruhnya, bukan sebagian — supaya petugas tahu persis
        // siapa yang masuk dan siapa yang belum.
        $this->assertDatabaseCount('anggota_halaqah', 0);
    }

    public function test_kuota_nol_berarti_tidak_dibatasi(): void
    {
        $angkatan = $this->angkatan();
        $halaqah = $this->halaqah($angkatan, ['kuota' => 0]);

        $lima = collect(range(1, 5))->map(fn () => $this->santri($angkatan)->id)->all();

        $this->actingAs($this->petugas())
            ->post(route('halaqah.anggota.store', $halaqah), ['pendaftaran_id' => $lima])
            ->assertSessionHas('success');

        $this->assertDatabaseCount('anggota_halaqah', 5);
        $this->assertNull($halaqah->fresh()->sisa_kuota);
    }

    public function test_santri_yang_sudah_berhalaqah_tidak_ikut_ditempatkan_lagi(): void
    {
        $angkatan = $this->angkatan();
        $pertama = $this->halaqah($angkatan, ['kode' => 'H-01']);
        $kedua = $this->halaqah($angkatan, ['kode' => 'H-02', 'nama' => 'Halaqah Kedua']);
        $santri = $this->santri($angkatan);

        $petugas = $this->petugas();

        $this->actingAs($petugas)
            ->post(route('halaqah.anggota.store', $pertama), ['pendaftaran_id' => [$santri->id]]);

        $this->actingAs($petugas)
            ->post(route('halaqah.anggota.store', $kedua), ['pendaftaran_id' => [$santri->id]])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('anggota_halaqah', 1);
    }

    public function test_pindah_halaqah_menutup_keanggotaan_lama(): void
    {
        $angkatan = $this->angkatan();
        $asal = $this->halaqah($angkatan, ['kode' => 'H-01']);
        $tujuan = $this->halaqah($angkatan, ['kode' => 'H-02', 'nama' => 'Halaqah Tujuan']);
        $santri = $this->santri($angkatan);

        $petugas = $this->petugas();

        $this->actingAs($petugas)
            ->post(route('halaqah.anggota.store', $asal), ['pendaftaran_id' => [$santri->id]]);

        $anggota = AnggotaHalaqah::firstWhere('halaqah_id', $asal->id);

        $this->actingAs($petugas)
            ->put(route('halaqah.anggota.pindah', $anggota), [
                'halaqah_id' => $tujuan->id,
                'alasan_pindah' => 'Penyesuaian tingkat hafalan.',
            ])
            ->assertSessionHas('success');

        // Baris lama tetap ada sebagai riwayat, tetapi sudah ditutup.
        $this->assertDatabaseHas('anggota_halaqah', [
            'halaqah_id' => $asal->id,
            'is_aktif' => false,
            'alasan_pindah' => 'Penyesuaian tingkat hafalan.',
        ]);

        $this->assertDatabaseHas('anggota_halaqah', [
            'halaqah_id' => $tujuan->id,
            'is_aktif' => true,
        ]);

        $this->assertSame(1, $santri->anggotaHalaqah()->where('is_aktif', true)->count());
    }

    public function test_pindah_ke_halaqah_penuh_ditolak(): void
    {
        $angkatan = $this->angkatan();
        $asal = $this->halaqah($angkatan, ['kode' => 'H-01']);
        $tujuan = $this->halaqah($angkatan, ['kode' => 'H-02', 'nama' => 'Halaqah Penuh', 'kuota' => 1]);

        $petugas = $this->petugas();
        $santri = $this->santri($angkatan);
        $penghuni = $this->santri($angkatan);

        $this->actingAs($petugas)->post(route('halaqah.anggota.store', $asal), ['pendaftaran_id' => [$santri->id]]);
        $this->actingAs($petugas)->post(route('halaqah.anggota.store', $tujuan), ['pendaftaran_id' => [$penghuni->id]]);

        $anggota = AnggotaHalaqah::where('halaqah_id', $asal->id)->firstOrFail();

        $this->actingAs($petugas)
            ->put(route('halaqah.anggota.pindah', $anggota), ['halaqah_id' => $tujuan->id])
            ->assertSessionHas('error');

        $this->assertTrue($anggota->fresh()->is_aktif);
    }

    public function test_santri_yang_dikeluarkan_kembali_jadi_calon(): void
    {
        $angkatan = $this->angkatan();
        $halaqah = $this->halaqah($angkatan);
        $santri = $this->santri($angkatan);

        $petugas = $this->petugas();

        $this->actingAs($petugas)
            ->post(route('halaqah.anggota.store', $halaqah), ['pendaftaran_id' => [$santri->id]]);

        $anggota = AnggotaHalaqah::firstOrFail();

        $this->actingAs($petugas)
            ->delete(route('halaqah.anggota.keluar', $anggota), ['alasan_pindah' => 'Izin pulang.'])
            ->assertSessionHas('success');

        $this->assertFalse($anggota->fresh()->is_aktif);
        $this->assertNotNull($anggota->fresh()->tanggal_keluar);
        $this->assertTrue(Pendaftaran::belumBerhalaqah()->whereKey($santri->id)->exists());
    }

    public function test_santri_boleh_kembali_ke_halaqah_yang_pernah_ditinggalkan(): void
    {
        $angkatan = $this->angkatan();
        $halaqah = $this->halaqah($angkatan);
        $santri = $this->santri($angkatan);

        $petugas = $this->petugas();

        $this->actingAs($petugas)
            ->post(route('halaqah.anggota.store', $halaqah), ['pendaftaran_id' => [$santri->id]]);

        $this->actingAs($petugas)
            ->delete(route('halaqah.anggota.keluar', AnggotaHalaqah::firstOrFail()));

        // Indeks unik (halaqah_id, pendaftaran_id) tidak boleh menghalangi
        // santri yang kembali ke halaqah asalnya.
        $this->actingAs($petugas)
            ->post(route('halaqah.anggota.store', $halaqah), ['pendaftaran_id' => [$santri->id]])
            ->assertSessionHas('success');

        $this->assertDatabaseCount('anggota_halaqah', 1);
        $this->assertTrue(AnggotaHalaqah::firstOrFail()->is_aktif);
    }

    public function test_halaqah_nonaktif_menolak_santri_baru(): void
    {
        $angkatan = $this->angkatan();
        $halaqah = $this->halaqah($angkatan, ['is_aktif' => false]);
        $santri = $this->santri($angkatan);

        $this->actingAs($this->petugas())
            ->post(route('halaqah.anggota.store', $halaqah), ['pendaftaran_id' => [$santri->id]])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('anggota_halaqah', 0);
    }

    public function test_halaqah_yang_pernah_berisi_santri_tidak_bisa_dihapus(): void
    {
        $angkatan = $this->angkatan();
        $halaqah = $this->halaqah($angkatan);
        $santri = $this->santri($angkatan);

        $petugas = $this->petugas();

        $this->actingAs($petugas)
            ->post(route('halaqah.anggota.store', $halaqah), ['pendaftaran_id' => [$santri->id]]);

        $this->actingAs($petugas)
            ->delete(route('halaqah.destroy', $halaqah))
            ->assertSessionHas('error');

        $this->assertNotSoftDeleted($halaqah);
    }

    public function test_kuota_tidak_bisa_diturunkan_di_bawah_jumlah_santri(): void
    {
        $angkatan = $this->angkatan();
        $halaqah = $this->halaqah($angkatan, ['kuota' => 5]);
        $petugas = $this->petugas();

        $dua = collect([1, 2])->map(fn () => $this->santri($angkatan)->id)->all();

        $this->actingAs($petugas)
            ->post(route('halaqah.anggota.store', $halaqah), ['pendaftaran_id' => $dua]);

        $this->actingAs($petugas)
            ->put(route('halaqah.update', $halaqah), [
                'angkatan_id' => $angkatan->id,
                'kode' => $halaqah->kode,
                'nama' => $halaqah->nama,
                'jenis_kelamin' => 'L',
                'kuota' => 1,
                'is_aktif' => 1,
            ])
            ->assertSessionHas('error');

        $this->assertSame(5, $halaqah->fresh()->kuota);
    }

    public function test_kode_halaqah_boleh_sama_di_angkatan_berbeda(): void
    {
        $pertama = $this->angkatan('AK-97');
        $kedua = $this->angkatan('AK-96');

        $this->halaqah($pertama, ['kode' => 'H-01']);

        $this->actingAs($this->petugas())
            ->post(route('halaqah.store'), [
                'angkatan_id' => $kedua->id,
                'kode' => 'H-01',
                'nama' => 'Halaqah Angkatan Lain',
                'jenis_kelamin' => 'L',
                'kuota' => 10,
                'is_aktif' => 1,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(2, Halaqah::where('kode', 'H-01')->count());
    }

    public function test_pengguna_tanpa_izin_ditolak(): void
    {
        $angkatan = $this->angkatan();
        $halaqah = $this->halaqah($angkatan);

        $user = User::factory()->create();
        $user->assignRole('pengguna');

        $this->actingAs($user)->get(route('halaqah.index'))->assertForbidden();
        $this->actingAs($user)
            ->post(route('halaqah.anggota.store', $halaqah), ['pendaftaran_id' => [1]])
            ->assertForbidden();
    }

    public function test_muhaffizh_dengan_halaqah_tidak_bisa_dihapus(): void
    {
        $angkatan = $this->angkatan();
        $muhaffizh = Muhaffizh::create([
            'kode' => 'MHF-001',
            'nama' => 'Ustadz Uji',
            'jenis_kelamin' => 'L',
            'status' => 'aktif',
        ]);

        $this->halaqah($angkatan, ['muhaffizh_id' => $muhaffizh->id]);

        $this->actingAs($this->petugas())
            ->delete(route('muhaffizh.destroy', $muhaffizh))
            ->assertSessionHas('error');

        $this->assertNotSoftDeleted($muhaffizh);
    }

    public function test_kode_muhaffizh_berurut(): void
    {
        $this->assertSame('MHF-001', Muhaffizh::kodeBerikutnya());

        Muhaffizh::create([
            'kode' => 'MHF-001',
            'nama' => 'Ustadz Uji',
            'jenis_kelamin' => 'L',
            'status' => 'aktif',
        ]);

        $this->assertSame('MHF-002', Muhaffizh::kodeBerikutnya());
    }

    public function test_semua_halaman_modul_terbuka(): void
    {
        $angkatan = $this->angkatan();
        $muhaffizh = Muhaffizh::create([
            'kode' => 'MHF-001',
            'nama' => 'Ustadz Uji',
            'jenis_kelamin' => 'L',
            'status' => 'aktif',
        ]);
        $halaqah = $this->halaqah($angkatan, ['muhaffizh_id' => $muhaffizh->id]);
        $santri = $this->santri($angkatan);

        $petugas = $this->petugas();

        // Satu santri ditempatkan dan satu dikeluarkan, supaya tabel binaan,
        // panel penempatan, dan tabel riwayat sama-sama ada isinya saat dirender.
        $this->actingAs($petugas)
            ->post(route('halaqah.anggota.store', $halaqah), ['pendaftaran_id' => [$santri->id]]);
        $this->actingAs($petugas)
            ->delete(route('halaqah.anggota.keluar', AnggotaHalaqah::firstOrFail()));
        $this->actingAs($petugas)
            ->post(route('halaqah.anggota.store', $halaqah), ['pendaftaran_id' => [$this->santri($angkatan)->id]]);

        $halaman = [
            route('dashboard'),
            route('halaqah.index'),
            route('halaqah.create'),
            route('halaqah.show', $halaqah),
            route('halaqah.edit', $halaqah),
            route('muhaffizh.index'),
            route('muhaffizh.create'),
            route('muhaffizh.show', $muhaffizh),
            route('muhaffizh.edit', $muhaffizh),
        ];

        foreach ($halaman as $url) {
            $this->actingAs($petugas)->get($url)->assertOk();
        }

        $this->actingAs($petugas)->get(route('muhaffizh.export'))->assertOk();
    }

    // ---------------------------------------------------------------- bantuan

    protected function petugas(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    protected function angkatan(string $kode = 'AK-99'): Angkatan
    {
        return Angkatan::create([
            'nama' => 'Angkatan '.$kode,
            'kode' => $kode,
            'tahun' => 2026,
            'kuota' => 30,
            'status' => 'berjalan',
        ]);
    }

    /**
     * @param  array<string, mixed>  $atribut
     */
    protected function halaqah(Angkatan $angkatan, array $atribut = []): Halaqah
    {
        return Halaqah::create(array_merge([
            'angkatan_id' => $angkatan->id,
            'kode' => 'H-01',
            'nama' => 'Halaqah Uji',
            'jenis_kelamin' => 'L',
            'kuota' => 10,
            'is_aktif' => true,
        ], $atribut));
    }

    protected function santri(Angkatan $angkatan, string $jenisKelamin = 'L'): Pendaftaran
    {
        $urut = ++$this->urutan;

        $peserta = Peserta::create([
            'nama' => 'Santri '.$urut,
            'jenis_kelamin' => $jenisKelamin,
            'nik' => str_pad((string) $urut, 16, '3', STR_PAD_LEFT),
        ]);

        return Pendaftaran::create([
            'peserta_id' => $peserta->id,
            'angkatan_id' => $angkatan->id,
            'kode_pendaftaran' => sprintf('REG-2026-%04d', $urut),
            'nomor_induk' => sprintf('%s-%04d', $angkatan->kode, $urut),
            'status_pendaftaran' => 'disetujui',
            'sumber_pendaftaran' => 'admin',
            'status' => 'aktif',
        ]);
    }
}
