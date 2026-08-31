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
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pencatatan setoran hafalan, satuannya halaman.
 *
 * Dua hal yang paling berisiko dan dijaga di sini: pembatasan data supaya
 * muhaffizh tidak melihat halaqah orang lain, dan pemisahan "siapa menyimak"
 * dari "siapa mengetik" supaya muhaffizh tanpa akun tetap tercatat benar.
 */
class SetoranTest extends TestCase
{
    use RefreshDatabase;

    protected int $urutan = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PermissionSeeder::class, RoleSeeder::class]);
    }

    // ---------------------------------------------------------------- bantuan

    protected function pengguna(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    protected function muhaffizh(string $kode = 'MHF-001'): Muhaffizh
    {
        return Muhaffizh::create([
            'kode' => $kode,
            'nama' => 'Ustadz '.$kode,
            'jenis_kelamin' => 'L',
            'status' => 'aktif',
        ]);
    }

    protected function halaqah(?Muhaffizh $muhaffizh = null, string $kode = 'H-01'): Halaqah
    {
        $angkatan = Angkatan::firstOrCreate(
            ['kode' => 'AK-99'],
            ['nama' => 'Angkatan Uji', 'tahun' => 2026, 'kuota' => 30, 'status' => 'berjalan']
        );

        return Halaqah::create([
            'angkatan_id' => $angkatan->id,
            'muhaffizh_id' => $muhaffizh?->id,
            'kode' => $kode,
            'nama' => 'Halaqah '.$kode,
            'jenis_kelamin' => 'L',
            'kuota' => 10,
            'is_aktif' => true,
        ]);
    }

    protected function santri(Halaqah $halaqah): AnggotaHalaqah
    {
        $urut = ++$this->urutan;

        $peserta = Peserta::create([
            'nama' => 'Santri '.$urut,
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

    /**
     * Setoran sebagai bahan uji, dibuat langsung tanpa lewat HTTP.
     *
     * Sengaja tidak memakai POST: pesan sukses dari permintaan sebelumnya ikut
     * terbawa ke halaman berikutnya, sehingga nama santri bisa "terlihat" lewat
     * notifikasi dan membuat pemeriksaan pembatasan data jadi palsu.
     */
    protected function buatSetoran(AnggotaHalaqah $anggota, array $ubah = []): Setoran
    {
        return Setoran::create(array_merge([
            'anggota_halaqah_id' => $anggota->id,
            'muhaffizh_id' => $anggota->halaqah->muhaffizh_id,
            'tanggal' => now()->toDateString(),
            'jenis' => 'ziyadah',
            'jumlah_halaman' => 1.5,
            'kualitas' => 'jayyid',
        ], $ubah));
    }

    /**
     * @return array<string, mixed>
     */
    protected function isian(AnggotaHalaqah $anggota, array $ubah = []): array
    {
        return array_merge([
            'anggota_halaqah_id' => $anggota->id,
            'tanggal' => now()->toDateString(),
            'jenis' => 'ziyadah',
            'jumlah_halaman' => 1.5,
            'kualitas' => 'jayyid',
        ], $ubah);
    }

    // ------------------------------------------------------------- pencatatan

    public function test_setoran_tercatat_dalam_satuan_halaman(): void
    {
        $anggota = $this->santri($this->halaqah($this->muhaffizh()));

        $this->actingAs($this->pengguna('admin'))
            ->post(route('setoran.store'), $this->isian($anggota, ['jumlah_halaman' => 2.5]))
            ->assertRedirect();

        $this->assertSame('2.50', Setoran::firstOrFail()->jumlah_halaman);
    }

    public function test_halaman_harus_kelipatan_setengah(): void
    {
        $anggota = $this->santri($this->halaqah($this->muhaffizh()));

        $this->actingAs($this->pengguna('admin'))
            ->post(route('setoran.store'), $this->isian($anggota, ['jumlah_halaman' => 1.3]))
            ->assertSessionHasErrors('jumlah_halaman');

        $this->assertDatabaseCount('setoran', 0);
    }

    public function test_setoran_tidak_boleh_bertanggal_masa_depan(): void
    {
        $anggota = $this->santri($this->halaqah($this->muhaffizh()));

        $this->actingAs($this->pengguna('admin'))
            ->post(route('setoran.store'), $this->isian($anggota, [
                'tanggal' => now()->addDay()->toDateString(),
            ]))
            ->assertSessionHasErrors('tanggal');
    }

    public function test_penyimak_dan_pencatat_dipisah(): void
    {
        $muhaffizh = $this->muhaffizh();
        $anggota = $this->santri($this->halaqah($muhaffizh));

        // Muhaffizh ini sengaja tidak punya akun: setorannya dientri operator
        // dari kartu, persis keadaan di lapangan.
        $operator = $this->pengguna('operator');

        $this->actingAs($operator)->post(route('setoran.store'), $this->isian($anggota));

        $setoran = Setoran::firstOrFail();

        $this->assertSame($muhaffizh->id, $setoran->muhaffizh_id, 'Penyimak tetap muhaffizh pengampu.');
        $this->assertSame($operator->id, $setoran->dicatat_oleh, 'Pengentri tercatat terpisah.');
        $this->assertTrue($setoran->isDicatatPetugas());
    }

    public function test_penyimak_tidak_ikut_berubah_saat_pengampu_diganti(): void
    {
        $lama = $this->muhaffizh('MHF-001');
        $baru = $this->muhaffizh('MHF-002');
        $halaqah = $this->halaqah($lama);
        $anggota = $this->santri($halaqah);

        $this->actingAs($this->pengguna('admin'))->post(route('setoran.store'), $this->isian($anggota));

        $halaqah->update(['muhaffizh_id' => $baru->id]);

        $this->assertSame($lama->id, Setoran::firstOrFail()->muhaffizh_id,
            'Setoran lama tidak boleh berpindah atas nama pengampu baru.');
    }

    // -------------------------------------------------------------- pembatasan

    public function test_muhaffizh_hanya_melihat_halaqah_asuhannya(): void
    {
        $saya = $this->muhaffizh('MHF-001');
        $orangLain = $this->muhaffizh('MHF-002');

        $milikSaya = $this->halaqah($saya, 'H-01');
        $milikOrangLain = $this->halaqah($orangLain, 'H-02');

        $akun = $this->pengguna('muhaffizh');
        $saya->update(['user_id' => $akun->id]);

        $this->actingAs($akun)->get(route('halaqah.index'))
            ->assertOk()
            ->assertSee('Halaqah H-01')
            ->assertDontSee('Halaqah H-02');

        $this->actingAs($akun)->get(route('halaqah.show', $milikSaya))->assertOk();
        $this->actingAs($akun)->get(route('halaqah.show', $milikOrangLain))->assertForbidden();
    }

    public function test_muhaffizh_tidak_bisa_mencatat_untuk_santri_orang_lain(): void
    {
        $saya = $this->muhaffizh('MHF-001');
        $orangLain = $this->muhaffizh('MHF-002');
        $this->halaqah($saya, 'H-01');
        $anggotaOrangLain = $this->santri($this->halaqah($orangLain, 'H-02'));

        $akun = $this->pengguna('muhaffizh');
        $saya->update(['user_id' => $akun->id]);

        $this->actingAs($akun)
            ->post(route('setoran.store'), $this->isian($anggotaOrangLain))
            ->assertForbidden();

        $this->assertDatabaseCount('setoran', 0);
    }

    public function test_daftar_setoran_muhaffizh_hanya_berisi_halaqahnya(): void
    {
        $saya = $this->muhaffizh('MHF-001');
        $orangLain = $this->muhaffizh('MHF-002');

        $anggotaSaya = $this->santri($this->halaqah($saya, 'H-01'));
        $anggotaLain = $this->santri($this->halaqah($orangLain, 'H-02'));

        $this->buatSetoran($anggotaSaya);
        $this->buatSetoran($anggotaLain);

        $akun = $this->pengguna('muhaffizh');
        $saya->update(['user_id' => $akun->id]);

        $this->actingAs($akun)->get(route('setoran.index'))
            ->assertOk()
            ->assertSee($anggotaSaya->pendaftaran->peserta->nama)
            ->assertDontSee($anggotaLain->pendaftaran->peserta->nama);
    }

    public function test_ekspor_csv_muhaffizh_ikut_tersaring(): void
    {
        $saya = $this->muhaffizh('MHF-001');
        $orangLain = $this->muhaffizh('MHF-002');

        $anggotaSaya = $this->santri($this->halaqah($saya, 'H-01'));
        $anggotaLain = $this->santri($this->halaqah($orangLain, 'H-02'));

        $this->buatSetoran($anggotaSaya);
        $this->buatSetoran($anggotaLain);

        $akun = $this->pengguna('muhaffizh');
        $saya->update(['user_id' => $akun->id]);

        $isi = $this->actingAs($akun)->get(route('setoran.export'))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString($anggotaSaya->pendaftaran->peserta->nama, $isi);
        $this->assertStringNotContainsString($anggotaLain->pendaftaran->peserta->nama, $isi,
            'Ekspor tidak boleh jadi pintu belakang untuk data halaqah orang lain.');
    }

    public function test_dashboard_muhaffizh_menampilkan_angkanya_sendiri(): void
    {
        $saya = $this->muhaffizh('MHF-001');
        $orangLain = $this->muhaffizh('MHF-002');

        $this->santri($this->halaqah($saya, 'H-01'));
        $this->santri($this->halaqah($orangLain, 'H-02'));
        $this->santri($this->halaqah($orangLain, 'H-03'));

        $akun = $this->pengguna('muhaffizh');
        $saya->update(['user_id' => $akun->id]);

        $this->actingAs($akun)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Halaqah Saya')
            ->assertSee('Santri Binaan')
            // Angka seluruh pesantren bukan urusannya, dan "Santri Belum
            // Berhalaqah" adalah pekerjaan admin.
            ->assertDontSee('Halaqah Berjalan')
            ->assertDontSee('Santri Belum Berhalaqah');
    }

    public function test_dashboard_admin_tetap_menampilkan_angka_sistem(): void
    {
        $this->halaqah($this->muhaffizh('MHF-001'), 'H-01');

        $this->actingAs($this->pengguna('admin'))->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Halaqah Berjalan')
            ->assertSee('Santri Belum Berhalaqah')
            ->assertDontSee('Halaqah Saya');
    }

    public function test_muhaffizh_tidak_bisa_mengubah_setoran_halaqah_lain(): void
    {
        $saya = $this->muhaffizh('MHF-001');
        $orangLain = $this->muhaffizh('MHF-002');
        $this->halaqah($saya, 'H-01');
        $anggotaLain = $this->santri($this->halaqah($orangLain, 'H-02'));

        $this->actingAs($this->pengguna('admin'))->post(route('setoran.store'), $this->isian($anggotaLain));
        $setoran = Setoran::firstOrFail();

        $akun = $this->pengguna('muhaffizh');
        $saya->update(['user_id' => $akun->id]);

        $this->actingAs($akun)->get(route('setoran.edit', $setoran))->assertForbidden();
        $this->actingAs($akun)->delete(route('setoran.destroy', $setoran))->assertForbidden();
    }

    public function test_operator_melihat_seluruh_halaqah(): void
    {
        $this->halaqah($this->muhaffizh('MHF-001'), 'H-01');
        $this->halaqah($this->muhaffizh('MHF-002'), 'H-02');

        $this->actingAs($this->pengguna('operator'))->get(route('halaqah.index'))
            ->assertOk()
            ->assertSee('Halaqah H-01')
            ->assertSee('Halaqah H-02');
    }

    public function test_akun_tanpa_muhaffizh_dan_tanpa_izin_penuh_tidak_melihat_apa_pun(): void
    {
        $this->santri($this->halaqah($this->muhaffizh()));

        // Akun ber-role muhaffizh tetapi belum ditautkan ke data muhaffizh mana pun.
        $akun = $this->pengguna('muhaffizh');

        $this->actingAs($akun)->get(route('halaqah.index'))
            ->assertOk()
            ->assertSee('Belum ada halaqah');
    }

    // ------------------------------------------------------------------ rekap

    public function test_rekap_menjumlahkan_ziyadah_saja(): void
    {
        $anggota = $this->santri($this->halaqah($this->muhaffizh()));

        $this->buatSetoran($anggota, ['jumlah_halaman' => 2]);
        $this->buatSetoran($anggota, ['jumlah_halaman' => 3]);
        $this->buatSetoran($anggota, ['jenis' => 'murajaah', 'jumlah_halaman' => 10]);

        $this->assertSame(5.0, $anggota->totalZiyadah());

        $this->actingAs($this->pengguna('admin'))->get(route('setoran.index'))
            ->assertOk()
            ->assertSee('5 halaman')
            ->assertSee('0,3 juz');   // 5 / 20 halaman per juz
    }

    public function test_halaman_setoran_terbuka(): void
    {
        $halaqah = $this->halaqah($this->muhaffizh());
        $anggota = $this->santri($halaqah);
        $admin = $this->pengguna('admin');

        $this->actingAs($admin)->post(route('setoran.store'), $this->isian($anggota));
        $setoran = Setoran::firstOrFail();

        $this->actingAs($admin)->get(route('setoran.index'))->assertOk();
        $this->actingAs($admin)->get(route('setoran.create', ['halaqah_id' => $halaqah->id]))->assertOk();
        $this->actingAs($admin)->get(route('setoran.edit', $setoran))->assertOk();
        $this->actingAs($admin)->get(route('setoran.export'))->assertOk();
        $this->actingAs($admin)->get(route('halaqah.show', $halaqah))->assertOk();
    }
}
