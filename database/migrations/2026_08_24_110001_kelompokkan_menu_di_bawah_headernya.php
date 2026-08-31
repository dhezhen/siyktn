<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Menjadikan pengelompokan menu benar-benar bertingkat.
 *
 * Sebelumnya header dan menu-menu di bawahnya sama-sama berada di level atas;
 * "kelompok" hanya kebetulan urutan. Akibatnya dua hal:
 *
 *  1. Di halaman Manajemen Menu semuanya tampak sejajar, sehingga petugas tidak
 *     punya cara jelas memindahkan menu antar kelompok.
 *  2. Menu baru dari seeder mendapat nomor urut yang bertabrakan dengan data
 *     lama, lalu mendarat di kelompok yang salah — itulah sebabnya "Muhaffizh"
 *     sempat muncul di bawah header "Pengaturan".
 *
 * Setelah migrasi ini setiap menu menjadi anak dari headernya, dan sidebar
 * merendernya sebagai kelompok yang bisa dibuka-tutup
 * (lihat partials/menu-item.blade.php).
 */
return new class extends Migration
{
    /**
     * Susunan yang seharusnya, sama persis dengan MenuSeeder.
     *
     * @var array<string, array<int, string>>
     */
    protected array $kelompok = [
        'Manajemen Pengguna' => ['user.index', 'role.index'],
        'Data Tahfidz' => ['pendaftaran.index', 'angkatan.index', 'peserta.index', 'muhaffizh.index'],
        'Modul Halaqah' => ['halaqah.index'],
        'Pengaturan' => ['menu.index', 'setting.edit', 'activity.index'],
    ];

    public function up(): void
    {
        $headerId = DB::table('menus')->where('type', 'header')->pluck('id', 'title');

        if ($headerId->isEmpty()) {
            return;
        }

        $anak = $this->kelompokAsal($headerId);

        /*
         | Menu inti dibuang dulu dari SELURUH kelompok asal, bukan hanya dari
         | kelompok yang sedang diproses. Kalau tidak, menu yang sempat nyasar
         | akan ditarik kembali ke kelompok lamanya saat giliran kelompok itu
         | tiba — persis nasib "Muhaffizh" yang tersangkut di "Pengaturan".
         */
        $ditentukanDaftar = [];

        foreach ($this->kelompok as $routes) {
            foreach ($routes as $route) {
                if ($id = DB::table('menus')->where('route', $route)->value('id')) {
                    $ditentukanDaftar[] = $id;
                }
            }
        }

        foreach ($anak as $idHeader => $ids) {
            $anak[$idHeader] = array_values(array_diff($ids, $ditentukanDaftar));
        }

        // Menu tambahan buatan petugas menyusul di belakang, tetap di kelompok
        // tempatnya semula.
        foreach ($this->kelompok as $judul => $routes) {
            if (! $headerId->has($judul)) {
                continue;
            }

            $id = $headerId->get($judul);
            $urut = 0;

            foreach ($routes as $route) {
                if ($idMenu = DB::table('menus')->where('route', $route)->value('id')) {
                    DB::table('menus')->where('id', $idMenu)
                        ->update(['parent_id' => $id, 'order' => $urut++]);
                }
            }

            foreach ($anak[$id] ?? [] as $idAnak) {
                DB::table('menus')->where('id', $idAnak)
                    ->update(['parent_id' => $id, 'order' => $urut++]);
            }
        }

        $this->urutkanUlangLevelAtas($headerId);
        $this->berikanIkonHeader();
    }

    /**
     * Header kini dirender sebagai tombol buka-tutup, bukan label kecil, jadi
     * ia butuh ikon. Yang sudah punya ikon sendiri tidak diganggu.
     */
    protected function berikanIkonHeader(): void
    {
        $ikon = [
            'Manajemen Pengguna' => 'users',
            'Data Tahfidz' => 'identification',
            'Modul Halaqah' => 'academic',
            'Pengaturan' => 'cog',
        ];

        foreach ($ikon as $judul => $nama) {
            DB::table('menus')
                ->where('type', 'header')
                ->where('title', $judul)
                ->whereNull('icon')
                ->update(['icon' => $nama]);
        }
    }

    /**
     * Kelompok setiap menu menurut susunan lama: sebuah menu dianggap milik
     * header terdekat di atasnya.
     *
     * @param  Collection<string, int>  $headerId
     * @return array<int, array<int, int>>
     */
    protected function kelompokAsal($headerId): array
    {
        $anak = [];
        $headerTerakhir = null;

        $barisAtas = DB::table('menus')
            ->whereNull('parent_id')
            ->orderBy('order')
            ->orderBy('id')
            ->get();

        foreach ($barisAtas as $menu) {
            if ($menu->type === 'header') {
                $headerTerakhir = $menu->id;

                continue;
            }

            // Divider dan menu sebelum header pertama tetap di level atas.
            if ($menu->type !== 'divider' && $headerTerakhir !== null) {
                $anak[$headerTerakhir][] = $menu->id;
            }
        }

        // Menu yang memang sudah bersarang sejak awal ikut diperhitungkan.
        foreach ($headerId as $id) {
            foreach (DB::table('menus')->where('parent_id', $id)->orderBy('order')->pluck('id') as $idAnak) {
                if (! in_array($idAnak, $anak[$id] ?? [], true)) {
                    $anak[$id][] = $idAnak;
                }
            }
        }

        return $anak;
    }

    /**
     * @param  Collection<string, int>  $headerId
     */
    protected function urutkanUlangLevelAtas($headerId): void
    {
        $urut = 0;

        // Menu lepas yang bukan header — mis. Dashboard — tetap di paling atas.
        $lepas = DB::table('menus')
            ->whereNull('parent_id')
            ->where('type', '!=', 'header')
            ->orderBy('order')
            ->orderBy('id')
            ->pluck('id');

        foreach ($lepas as $id) {
            DB::table('menus')->where('id', $id)->update(['order' => $urut++]);
        }

        foreach (array_keys($this->kelompok) as $judul) {
            if ($headerId->has($judul)) {
                DB::table('menus')->where('id', $headerId->get($judul))->update(['order' => $urut++]);
            }
        }

        // Header lain yang tidak dikenal daftar di atas menyusul di belakang.
        $sisa = DB::table('menus')
            ->whereNull('parent_id')
            ->where('type', 'header')
            ->whereNotIn('title', array_keys($this->kelompok))
            ->orderBy('order')
            ->pluck('id');

        foreach ($sisa as $id) {
            DB::table('menus')->where('id', $id)->update(['order' => $urut++]);
        }
    }

    /**
     * Kembalikan ke susunan datar: isi header naik lagi ke level atas,
     * ditaruh persis sesudah headernya.
     */
    public function down(): void
    {
        $urut = 0;

        $barisAtas = DB::table('menus')
            ->whereNull('parent_id')
            ->orderBy('order')
            ->orderBy('id')
            ->get();

        foreach ($barisAtas as $menu) {
            DB::table('menus')->where('id', $menu->id)->update(['order' => $urut++]);

            if ($menu->type !== 'header') {
                continue;
            }

            $anak = DB::table('menus')->where('parent_id', $menu->id)->orderBy('order')->pluck('id');

            foreach ($anak as $id) {
                DB::table('menus')->where('id', $id)->update(['parent_id' => null, 'order' => $urut++]);
            }
        }
    }
};
