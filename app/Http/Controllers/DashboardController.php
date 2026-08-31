<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\MembatasiKeMuhaffizh;
use App\Models\AnggotaHalaqah;
use App\Models\Halaqah;
use App\Models\Menu;
use App\Models\Muhaffizh;
use App\Models\Pendaftaran;
use App\Models\Peserta;
use App\Models\Setoran;
use App\Models\User;
use App\Support\Grafik;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    use MembatasiKeMuhaffizh;

    public function __invoke(): View
    {
        return view('dashboard', [
            'stats' => $this->stats(),
            'sambutan' => $this->sambutan(),
            'pintasan' => $this->pintasan(),
            'activities' => $this->recentActivities(),
            'grafik' => $this->grafik(),
        ]);
    }

    /**
     * Data grafik, mengikuti pembatasan yang sama dengan sisa sistem: muhaffizh
     * melihat halaqah asuhannya, admin dan operator melihat seluruhnya.
     *
     * @return array<string, mixed>|null
     */
    protected function grafik(): ?array
    {
        $user = Auth::user();

        if (! $user->can('setoran.view')) {
            return null;
        }

        $lingkup = $this->lingkupMuhaffizh('setoran.view-all');
        $milikSendiri = $lingkup !== null;

        return [
            'milikSendiri' => $milikSendiri,
            'pekan' => $this->trenPekanan($lingkup),
            'peringkat' => $milikSendiri ? $this->hafalanSantri($lingkup) : $this->hafalanHalaqah(),
            'kualitas' => $this->sebaranKualitas($lingkup),
        ];
    }

    /**
     * Delapan pekan terakhir, dijumlahkan per pekan.
     *
     * @return array{labels: array<int, string>, ziyadah: array<int, float>, murajaah: array<int, float>}
     */
    protected function trenPekanan(?int $lingkup): array
    {
        $mulai = now()->startOfWeek()->subWeeks(7);

        $baris = Setoran::query()
            ->when($lingkup !== null, fn ($q) => $q->diampuOleh($lingkup))
            ->where('tanggal', '>=', $mulai->toDateString())
            ->get(['tanggal', 'jenis', 'jumlah_halaman']);

        $labels = [];
        $ziyadah = [];
        $murajaah = [];

        for ($i = 0; $i < 8; $i++) {
            $awal = (clone $mulai)->addWeeks($i);
            $akhir = (clone $awal)->endOfWeek();

            $sepekan = $baris->filter(fn ($s) => $s->tanggal->betweenIncluded($awal, $akhir));

            $labels[] = $awal->translatedFormat('d M');
            $ziyadah[] = (float) $sepekan->where('jenis', 'ziyadah')->sum('jumlah_halaman');
            $murajaah[] = (float) $sepekan->where('jenis', 'murajaah')->sum('jumlah_halaman');
        }

        return ['labels' => $labels, 'ziyadah' => $ziyadah, 'murajaah' => $murajaah];
    }

    /**
     * Sepuluh santri dengan ziyadah terbanyak di halaqah yang diampu.
     *
     * @return array<int, array{label: string, nilai: float, ket: string}>
     */
    protected function hafalanSantri(int $muhaffizhId): array
    {
        return AnggotaHalaqah::query()
            ->where('is_aktif', true)
            ->whereHas('halaqah', fn ($q) => $q->where('muhaffizh_id', $muhaffizhId)->where('is_aktif', true))
            ->with('pendaftaran.peserta:id,nama', 'halaqah:id,nama')
            ->withSum(['setoran as ziyadah' => fn ($q) => $q->where('jenis', 'ziyadah')], 'jumlah_halaman')
            ->get()
            ->sortByDesc('ziyadah')
            ->take(10)
            ->map(fn ($a) => [
                'label' => $a->pendaftaran?->peserta?->nama ?? 'Santri',
                'nilai' => (float) ($a->ziyadah ?? 0),
                'ket' => $a->halaqah?->nama ?? '',
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{label: string, nilai: float, ket: string}>
     */
    protected function hafalanHalaqah(): array
    {
        // Dijumlahkan sekali untuk seluruh halaqah, bukan satu kueri per baris.
        $total = Setoran::query()
            ->ziyadah()
            ->join('anggota_halaqah', 'anggota_halaqah.id', '=', 'setoran.anggota_halaqah_id')
            ->selectRaw('anggota_halaqah.halaqah_id, SUM(setoran.jumlah_halaman) as jumlah')
            ->groupBy('anggota_halaqah.halaqah_id')
            ->pluck('jumlah', 'halaqah_id');

        return Halaqah::query()
            ->aktif()
            ->with('muhaffizh:id,nama')
            ->get()
            ->map(fn ($h) => [
                'label' => $h->nama,
                'nilai' => (float) ($total[$h->id] ?? 0),
                'ket' => $h->muhaffizh?->nama ?? 'tanpa pengampu',
            ])
            ->sortByDesc('nilai')
            ->take(10)
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{label: string, nilai: int, warna: string}>
     */
    protected function sebaranKualitas(?int $lingkup): array
    {
        $hitung = Setoran::query()
            ->when($lingkup !== null, fn ($q) => $q->diampuOleh($lingkup))
            ->selectRaw('kualitas, count(*) as jumlah')
            ->groupBy('kualitas')
            ->pluck('jumlah', 'kualitas');

        $label = [
            'mumtaz' => 'Mumtaz',
            'jayyid' => 'Jayyid',
            'maqbul' => 'Maqbul',
            'perlu_diulang' => 'Perlu Diulang',
        ];

        return collect(Grafik::KUALITAS)
            ->map(fn ($warna, $kunci) => [
                'label' => $label[$kunci],
                'nilai' => (int) ($hitung[$kunci] ?? 0),
                'warna' => $warna,
            ])
            ->values()
            ->all();
    }

    /**
     * Kartu ringkasan. Hanya menampilkan angka yang boleh dilihat pengguna.
     *
     * @return array<int, array{label: string, value: int|string, icon: string}>
     */
    protected function stats(): array
    {
        $user = Auth::user();

        /*
         | Dashboard muhaffizh berisi bimbingannya saja — bukan angka pesantren
         | dengan satu dua kartu miliknya diselipkan. "Total Peserta 34" tidak
         | menjawab pertanyaan apa pun yang ia punya pagi itu.
         */
        $lingkup = $this->lingkupMuhaffizh('halaqah.view-all');

        if ($lingkup !== null) {
            return $this->statsMuhaffizh($lingkup);
        }

        $stats = [];

        if ($user->can('user.view')) {
            $stats[] = ['label' => 'Total Pengguna', 'value' => User::count(), 'icon' => 'users'];
            $stats[] = ['label' => 'Pengguna Aktif', 'value' => User::active()->count(), 'icon' => 'check-circle'];
        }

        if ($user->can('role.view')) {
            $stats[] = ['label' => 'Role', 'value' => Role::count(), 'icon' => 'shield'];
        }

        if ($user->can('menu.view')) {
            $stats[] = ['label' => 'Menu Aktif', 'value' => Menu::active()->count(), 'icon' => 'list'];
        }

        if ($user->can('peserta.view')) {
            $stats[] = ['label' => 'Total Peserta', 'value' => Peserta::count(), 'icon' => 'users'];
        }

        if ($user->can('peserta.approve')) {
            $stats[] = [
                'label' => 'Pendaftaran Menunggu',
                'value' => Pendaftaran::menunggu()->count(),
                'icon' => 'warning',
            ];
        }

        if ($user->can('muhaffizh.view')) {
            $stats[] = ['label' => 'Muhaffizh Aktif', 'value' => Muhaffizh::aktif()->count(), 'icon' => 'academic'];
        }

        if ($user->can('halaqah.view')) {
            $stats = array_merge($stats, $this->statsSeluruhSistem());
        }

        return $stats;
    }

    /**
     * Pintasan ke pekerjaan sehari-hari peran yang sedang masuk.
     *
     * @return array<int, array{label: string, url: string}>
     */
    protected function pintasan(): array
    {
        $user = Auth::user();
        $milikSendiri = $this->lingkupMuhaffizh('halaqah.view-all') !== null;

        $calon = [
            ['izin' => 'halaqah.view', 'route' => 'halaqah.index', 'label' => $milikSendiri ? 'Halaqah Saya' : 'Halaqah'],
            ['izin' => 'setoran.view', 'route' => 'setoran.index', 'label' => 'Setoran Hafalan'],
            ['izin' => 'peserta.approve', 'route' => 'pendaftaran.index', 'label' => 'Tinjau Pendaftaran'],
            ['izin' => 'peserta.view', 'route' => 'peserta.index', 'label' => 'Peserta'],
            ['izin' => 'user.view', 'route' => 'user.index', 'label' => 'Pengguna'],
        ];

        return collect($calon)
            ->filter(fn ($p) => $user->can($p['izin']) && Route::has($p['route']))
            ->take(3)
            ->map(fn ($p) => ['label' => $p['label'], 'url' => route($p['route'])])
            ->values()
            ->all();
    }

    /**
     * Keterangan di bawah judul, disesuaikan dengan peran pembacanya.
     */
    protected function sambutan(): string
    {
        $user = Auth::user();

        if ($this->lingkupMuhaffizh('halaqah.view-all') !== null) {
            return 'Ringkasan halaqah dan setoran yang Anda bimbing.';
        }

        if ($user->can('setoran.view')) {
            return 'Ringkasan hafalan dan kegiatan seluruh halaqah.';
        }

        if ($user->can('user.view')) {
            return 'Ringkasan singkat kondisi sistem.';
        }

        return 'Ringkasan data yang boleh Anda akses.';
    }

    /**
     * @return array<int, array{label: string, value: int|string, icon: string}>
     */
    protected function statsSeluruhSistem(): array
    {
        return [
            ['label' => 'Halaqah Berjalan', 'value' => Halaqah::aktif()->count(), 'icon' => 'book'],

            // Angka yang menuntut tindakan: santri aktif yang belum dibagi ke
            // halaqah mana pun.
            ['label' => 'Santri Belum Berhalaqah', 'value' => Pendaftaran::belumBerhalaqah()->count(), 'icon' => 'warning'],
        ];
    }

    /**
     * @return array<int, array{label: string, value: int|string, icon: string}>
     */
    protected function statsMuhaffizh(int $muhaffizhId): array
    {
        $halaqah = Halaqah::query()->where('muhaffizh_id', $muhaffizhId);

        $binaan = AnggotaHalaqah::query()
            ->where('is_aktif', true)
            ->whereHas('halaqah', fn ($q) => $q->where('muhaffizh_id', $muhaffizhId)->where('is_aktif', true))
            ->count();

        $stats = [
            ['label' => 'Halaqah Saya', 'value' => (clone $halaqah)->aktif()->count(), 'icon' => 'book'],
            ['label' => 'Santri Binaan', 'value' => $binaan, 'icon' => 'users'],
        ];

        if (Auth::user()->can('setoran.view')) {
            $pekanIni = Setoran::diampuOleh($muhaffizhId)
                ->whereBetween('tanggal', [now()->startOfWeek(), now()->endOfWeek()])
                ->count();

            $ziyadah = (float) Setoran::diampuOleh($muhaffizhId)->ziyadah()->sum('jumlah_halaman');

            $stats[] = ['label' => 'Setoran Pekan Ini', 'value' => $pekanIni, 'icon' => 'check-circle'];
            $stats[] = ['label' => 'Ziyadah Terkumpul', 'value' => Setoran::setaraJuz($ziyadah), 'icon' => 'identification'];
        }

        return $stats;
    }

    /**
     * @return Collection<int, Activity>
     */
    protected function recentActivities()
    {
        if (! Auth::user()->can('activity.view')) {
            return collect();
        }

        return Activity::with('causer:id,name')->latest()->limit(10)->get();
    }
}
