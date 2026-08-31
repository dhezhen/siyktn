<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\User;
use App\Support\Menu as MenuTree;
use Database\Seeders\MenuSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * Pengelompokan menu sidebar.
 *
 * Header sekarang benar-benar menjadi induk. Dua hal yang pernah rusak diam-diam
 * dan dijaga di sini: isi header tidak boleh hilang dari sidebar, dan menu baru
 * dari seeder tidak boleh mendarat di kelompok orang lain.
 */
class MenuTest extends TestCase
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

    public function test_seeder_menyusun_menu_di_bawah_headernya(): void
    {
        $this->seed(MenuSeeder::class);

        $kelompok = [
            'Manajemen Pengguna' => ['user.index', 'role.index'],
            'Data Tahfidz' => ['pendaftaran.index', 'angkatan.index', 'peserta.index', 'muhaffizh.index'],
            'Modul Halaqah' => ['halaqah.index', 'setoran.index'],
            'Pengaturan' => ['menu.index', 'setting.edit', 'activity.index'],
        ];

        foreach ($kelompok as $judul => $routes) {
            $header = Menu::where('title', $judul)->where('type', 'header')->firstOrFail();

            $this->assertNull($header->parent_id, "Header {$judul} harus berada di level atas.");
            $this->assertSame($routes, $header->children()->orderBy('order')->pluck('route')->all());
        }

        // Dashboard sengaja berdiri sendiri, di luar kelompok mana pun.
        $this->assertNull(Menu::where('route', 'dashboard')->value('parent_id'));
    }

    public function test_menu_baru_menyusul_di_akhir_kelompoknya_bukan_di_kelompok_lain(): void
    {
        $this->seed(MenuSeeder::class);

        // Meniru keadaan sungguhan: modul baru ditambahkan ke seeder, lalu
        // seeder dijalankan lagi di atas database yang sudah terisi.
        Menu::where('route', 'muhaffizh.index')->delete();

        $this->seed(MenuSeeder::class);

        $muhaffizh = Menu::where('route', 'muhaffizh.index')->firstOrFail();
        $dataTahfidz = Menu::where('title', 'Data Tahfidz')->where('type', 'header')->firstOrFail();

        $this->assertSame($dataTahfidz->id, $muhaffizh->parent_id,
            'Menu baru harus masuk ke kelompok yang didaftarkan seeder, bukan ke kelompok tetangga.');

        $this->assertSame('muhaffizh.index',
            $dataTahfidz->children()->orderBy('order')->pluck('route')->last(),
            'Menu baru ditaruh di akhir kelompoknya agar susunan yang sudah diatur petugas tidak bergeser.');
    }

    public function test_isi_header_ikut_tampil_di_sidebar(): void
    {
        $this->seed(MenuSeeder::class);

        Auth::login($this->admin());

        $html = view('partials.sidebar')->render();

        foreach (['dashboard', 'user.index', 'peserta.index', 'muhaffizh.index', 'halaqah.index', 'activity.index'] as $name) {
            $this->assertStringContainsString('href="'.route($name).'"', $html,
                "Menu {$name} hilang dari sidebar.");
        }

        $this->assertStringContainsString('Modul Halaqah', $html);
    }

    public function test_kelompok_dirender_sebagai_dropdown(): void
    {
        $this->seed(MenuSeeder::class);

        Auth::login($this->admin());

        $html = view('partials.sidebar')->render();

        // Empat header berisi menjadi empat tombol buka-tutup.
        $this->assertSame(4, substr_count($html, 'aria-expanded'),
            'Setiap header berisi harus dirender sebagai kelompok yang bisa dibuka-tutup.');

        // Dashboard berdiri sendiri, jadi tetap tautan biasa.
        $this->assertStringContainsString('href="'.route('dashboard').'"', $html);
    }

    public function test_header_tanpa_anak_tetap_berupa_label(): void
    {
        Menu::create(['title' => 'Kelompok Datar', 'type' => 'header', 'order' => 0, 'is_active' => true]);
        Menu::create(['title' => 'Peserta', 'type' => 'route', 'route' => 'peserta.index',
            'permission' => 'peserta.view', 'order' => 1, 'is_active' => true]);

        Auth::login($this->admin());

        $html = view('partials.sidebar')->render();

        $this->assertStringContainsString('Kelompok Datar', $html);
        $this->assertStringNotContainsString('aria-expanded', $html,
            'Header gaya lama tidak punya anak, jadi tidak boleh berubah jadi dropdown.');
    }

    public function test_header_ikut_hilang_saat_seluruh_isinya_tidak_boleh_dilihat(): void
    {
        $this->seed(MenuSeeder::class);

        // Role "pengguna" tidak punya satu pun permission modul halaqah.
        $user = User::factory()->create();
        $user->assignRole('pengguna');

        Auth::login($user);

        $judul = collect(MenuTree::items())->pluck('title');

        $this->assertFalse($judul->contains('Modul Halaqah'),
            'Judul kelompok tidak boleh tersisa tanpa isi.');
        $this->assertFalse($judul->contains('Manajemen Pengguna'));

        $html = view('partials.sidebar')->render();
        $this->assertStringNotContainsString('href="'.route('halaqah.index').'"', $html);
    }

    public function test_header_gaya_lama_yang_datar_tetap_bekerja(): void
    {
        // Susunan sebelum menu dikelompokkan: header dan isinya sama-sama
        // berada di level atas. Data seperti ini masih mungkin ada di sistem
        // yang menunya pernah diatur manual.
        Menu::create(['title' => 'Kelompok Datar', 'type' => 'header', 'order' => 0, 'is_active' => true]);
        Menu::create(['title' => 'Peserta', 'type' => 'route', 'route' => 'peserta.index',
            'permission' => 'peserta.view', 'order' => 1, 'is_active' => true]);

        Auth::login($this->admin());

        $judul = collect(MenuTree::items())->pluck('title');

        $this->assertTrue($judul->contains('Kelompok Datar'));
        $this->assertTrue($judul->contains('Peserta'));
    }

    public function test_header_datar_tanpa_isi_tetap_disembunyikan(): void
    {
        Menu::create(['title' => 'Kelompok Kosong', 'type' => 'header', 'order' => 0, 'is_active' => true]);

        Auth::login($this->admin());

        $this->assertFalse(collect(MenuTree::items())->pluck('title')->contains('Kelompok Kosong'));
    }

    public function test_menu_yang_routenya_belum_ada_disembunyikan(): void
    {
        Menu::create(['title' => 'Kelompok', 'type' => 'header', 'order' => 0, 'is_active' => true]);
        // Nama route yang sengaja tidak akan pernah ada, supaya tes ini tidak
        // ikut gugur begitu modul yang namanya dipakai benar-benar dibuat.
        Menu::create(['title' => 'Belum Dibuat', 'type' => 'route', 'route' => 'modul-khayalan.index',
            'order' => 1, 'is_active' => true]);

        Auth::login($this->admin());

        $this->assertFalse(collect(MenuTree::items())->pluck('title')->contains('Belum Dibuat'));
    }
}
