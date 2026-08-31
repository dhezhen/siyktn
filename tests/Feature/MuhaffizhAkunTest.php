<?php

namespace Tests\Feature;

use App\Models\Muhaffizh;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Penautan akun login seorang muhaffizh.
 *
 * Inti aturannya: role mengikuti tautan akun, bukan diingat petugas. Dulu
 * mengisi user_id tidak memberi hak akses apa pun, sehingga muhaffizh yang
 * "sudah punya akun" bisa masuk tetapi disambut sidebar kosong.
 */
class MuhaffizhAkunTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PermissionSeeder::class, RoleSeeder::class]);
    }

    protected function petugas(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    protected function muhaffizh(array $atribut = []): Muhaffizh
    {
        return Muhaffizh::create(array_merge([
            'kode' => 'MHF-001',
            'nama' => 'Ustadz Fauzan Maulana',
            'jenis_kelamin' => 'L',
            'status' => 'aktif',
            'email' => 'fauzan@contoh.id',
        ], $atribut));
    }

    public function test_role_menempel_saat_akun_ditautkan(): void
    {
        $akun = User::factory()->create();
        $muhaffizh = $this->muhaffizh();

        $muhaffizh->update(['user_id' => $akun->id]);

        $this->assertTrue($akun->fresh()->hasRole(Muhaffizh::ROLE));
        $this->assertTrue($akun->fresh()->can('halaqah.view'));
    }

    public function test_role_dicabut_saat_tautan_dilepas(): void
    {
        $akun = User::factory()->create();
        $muhaffizh = $this->muhaffizh(['user_id' => $akun->id]);

        $this->assertTrue($akun->fresh()->hasRole(Muhaffizh::ROLE));

        $muhaffizh->update(['user_id' => null]);

        $this->assertFalse($akun->fresh()->hasRole(Muhaffizh::ROLE));
    }

    public function test_role_berpindah_saat_akun_diganti(): void
    {
        $lama = User::factory()->create();
        $baru = User::factory()->create();
        $muhaffizh = $this->muhaffizh(['user_id' => $lama->id]);

        $muhaffizh->update(['user_id' => $baru->id]);

        $this->assertFalse($lama->fresh()->hasRole(Muhaffizh::ROLE));
        $this->assertTrue($baru->fresh()->hasRole(Muhaffizh::ROLE));
    }

    public function test_role_lain_tidak_ikut_tercabut(): void
    {
        $akun = User::factory()->create();
        $akun->assignRole('operator');

        $muhaffizh = $this->muhaffizh(['user_id' => $akun->id]);
        $muhaffizh->update(['user_id' => null]);

        $this->assertTrue($akun->fresh()->hasRole('operator'),
            'Mencabut role muhaffizh tidak boleh menyentuh role lain milik akun itu.');
    }

    public function test_akun_super_admin_tidak_disentuh(): void
    {
        $akun = User::factory()->create();
        $akun->assignRole(config('permission.super_admin_role'));

        $this->muhaffizh(['user_id' => $akun->id]);

        $this->assertFalse($akun->fresh()->hasRole(Muhaffizh::ROLE),
            'Hak akses super admin tidak boleh diutak-atik dari layar muhaffizh.');
    }

    public function test_buatkan_akun_membuat_pengguna_lengkap_dengan_role(): void
    {
        $muhaffizh = $this->muhaffizh();

        $this->actingAs($this->petugas())
            ->post(route('muhaffizh.akun', $muhaffizh))
            ->assertSessionHas('success');

        $akun = $muhaffizh->fresh()->user;

        $this->assertNotNull($akun);
        $this->assertSame('fauzan@contoh.id', $akun->email);
        $this->assertSame('fauzan.maulana', $akun->username, 'Gelar dibuang dari username.');
        $this->assertTrue($akun->hasRole(Muhaffizh::ROLE));
        $this->assertTrue($akun->must_change_password);
        $this->assertTrue($akun->is_active);
    }

    public function test_username_tidak_bentrok(): void
    {
        User::factory()->create(['username' => 'fauzan.maulana']);

        $muhaffizh = $this->muhaffizh();

        $this->actingAs($this->petugas())->post(route('muhaffizh.akun', $muhaffizh));

        $this->assertSame('fauzan.maulana2', $muhaffizh->fresh()->user->username);
    }

    public function test_buatkan_akun_ditolak_tanpa_email(): void
    {
        $muhaffizh = $this->muhaffizh(['email' => null]);

        $this->actingAs($this->petugas())
            ->post(route('muhaffizh.akun', $muhaffizh))
            ->assertSessionHas('error');

        $this->assertNull($muhaffizh->fresh()->user_id);
        $this->assertSame(1, User::count(), 'Hanya akun petugas yang boleh ada.');
    }

    public function test_buatkan_akun_ditolak_bila_email_sudah_dipakai(): void
    {
        User::factory()->create(['email' => 'fauzan@contoh.id']);
        $muhaffizh = $this->muhaffizh();

        $this->actingAs($this->petugas())
            ->post(route('muhaffizh.akun', $muhaffizh))
            ->assertSessionHas('error');

        $this->assertNull($muhaffizh->fresh()->user_id);
    }

    public function test_buatkan_akun_ditolak_bila_sudah_punya_akun(): void
    {
        $akun = User::factory()->create();
        $muhaffizh = $this->muhaffizh(['user_id' => $akun->id]);

        $this->actingAs($this->petugas())
            ->post(route('muhaffizh.akun', $muhaffizh))
            ->assertSessionHas('error');

        $this->assertSame($akun->id, $muhaffizh->fresh()->user_id);
    }

    public function test_akun_berperan_lain_tidak_ditawarkan_di_form(): void
    {
        $admin = User::factory()->create(['name' => 'Akun Admin']);
        $admin->assignRole('admin');

        $polos = User::factory()->create(['name' => 'Akun Polos']);

        $nonaktif = User::factory()->create(['name' => 'Akun Nonaktif', 'is_active' => false]);

        $html = $this->actingAs($this->petugas())
            ->get(route('muhaffizh.create'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Akun Polos', $html);
        $this->assertStringNotContainsString('Akun Admin', $html);
        $this->assertStringNotContainsString('Akun Nonaktif', $html);
        $this->assertNotNull($nonaktif);
    }

    public function test_akun_yang_sedang_tertaut_tetap_muncul_di_form(): void
    {
        $akun = User::factory()->create(['name' => 'Akun Tertaut']);
        $muhaffizh = $this->muhaffizh(['user_id' => $akun->id]);

        // Setelah tertaut ia sudah ber-role muhaffizh, dan tetap harus terpilih
        // saat form dibuka lagi.
        $this->actingAs($this->petugas())
            ->get(route('muhaffizh.edit', $muhaffizh))
            ->assertOk()
            ->assertSee('Akun Tertaut');
    }

    public function test_petugas_tanpa_izin_pengguna_tidak_bisa_membuatkan_akun(): void
    {
        $muhaffizh = $this->muhaffizh();

        // Operator boleh melihat muhaffizh, tetapi tidak boleh menambah pengguna.
        $operator = User::factory()->create();
        $operator->assignRole('operator');

        $this->actingAs($operator)
            ->post(route('muhaffizh.akun', $muhaffizh))
            ->assertForbidden();

        $this->assertNull($muhaffizh->fresh()->user_id);
    }

    public function test_halaman_detail_menawarkan_tombol_sesuai_keadaan(): void
    {
        $petugas = $this->petugas();

        // Punya email, belum punya akun -> tombol tersedia.
        $siap = $this->muhaffizh();
        $this->actingAs($petugas)->get(route('muhaffizh.show', $siap))
            ->assertOk()
            ->assertSee('Buatkan Akun');

        // Tanpa email -> tombol diganti penjelasan apa yang harus diisi dulu.
        $tanpaEmail = $this->muhaffizh(['kode' => 'MHF-002', 'nama' => 'Ustadz Uji', 'email' => null]);
        $this->actingAs($petugas)->get(route('muhaffizh.show', $tanpaEmail))
            ->assertOk()
            ->assertDontSee('Buatkan Akun')
            ->assertSee('Isi dulu email muhaffizh ini');

        // Sudah punya akun -> beralih ke pengelolaan akun.
        $this->actingAs($petugas)->post(route('muhaffizh.akun', $siap));
        $this->actingAs($petugas)->get(route('muhaffizh.show', $siap))
            ->assertOk()
            ->assertSee('Kelola Akun')
            ->assertDontSee('Buatkan Akun');
    }

    public function test_muhaffizh_bisa_login_dan_melihat_menunya(): void
    {
        $muhaffizh = $this->muhaffizh();

        $this->actingAs($this->petugas())->post(route('muhaffizh.akun', $muhaffizh));

        $akun = $muhaffizh->fresh()->user;

        // Kata sandi sementara memaksa ganti dulu, jadi halaman lain dialihkan.
        $this->actingAs($akun)->get(route('halaqah.index'))->assertRedirect(route('password.change'));

        $akun->update(['must_change_password' => false]);

        $this->actingAs($akun)->get(route('halaqah.index'))->assertOk();
        $this->actingAs($akun)->get(route('user.index'))->assertForbidden();
    }
}
