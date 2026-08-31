<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\MembatasiKeMuhaffizh;
use App\Models\AnggotaHalaqah;
use App\Models\Halaqah;
use App\Models\Setoran;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SetoranController extends Controller implements HasMiddleware
{
    use MembatasiKeMuhaffizh;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:setoran.view', only: ['index']),
            new Middleware('permission:setoran.create', only: ['create', 'store']),
            new Middleware('permission:setoran.update', only: ['edit', 'update']),
            new Middleware('permission:setoran.delete', only: ['destroy']),
            new Middleware('permission:setoran.export', only: ['export']),
        ];
    }

    public function index(Request $request): View
    {
        $setoran = $this->terlingkup()
            ->with([
                'anggotaHalaqah.pendaftaran.peserta:id,nama',
                'anggotaHalaqah.halaqah:id,nama,kode',
                'muhaffizh:id,nama',
                'pencatat:id,name',
            ])
            ->when($request->filled('halaqah_id'),
                fn ($q) => $q->untukHalaqah($request->integer('halaqah_id')))
            ->when($request->filled('jenis'), fn ($q) => $q->where('jenis', $request->string('jenis')))
            ->when($request->filled('dari'), fn ($q) => $q->whereDate('tanggal', '>=', $request->date('dari')))
            ->when($request->filled('sampai'), fn ($q) => $q->whereDate('tanggal', '<=', $request->date('sampai')))
            ->when($request->filled('q'), fn ($q) => $q->whereHas('anggotaHalaqah.pendaftaran.peserta',
                fn ($p) => $p->where('nama', 'like', '%'.$request->string('q').'%')))
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('setoran.index', [
            'setoran' => $setoran,
            'daftarHalaqah' => $this->halaqahTerlingkup()->get(['id', 'nama', 'kode', 'angkatan_id']),
            'rekap' => $this->rekapDaftar($request),
        ]);
    }

    public function create(Request $request): View
    {
        $halaqah = $this->halaqahTerlingkup()
            ->aktif()
            ->findOrFail($request->integer('halaqah_id'));

        return view('setoran.form', [
            'setoran' => new Setoran([
                'tanggal' => now()->toDateString(),
                'jenis' => 'ziyadah',
                'kualitas' => 'jayyid',
                'jumlah_halaman' => 1,
            ]),
            'halaqah' => $halaqah,
            'anggota' => $this->anggotaAktif($halaqah),
            'anggotaTerpilih' => $request->integer('anggota_halaqah_id') ?: null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $anggota = $this->anggotaBolehDisentuh($data['anggota_halaqah_id']);

        $setoran = Setoran::create($data + [
            // Penyimak diambil dari pengampu halaqah saat ini, lalu dibekukan
            // di barisnya sendiri agar pergantian pengampu tidak menulis ulang
            // sejarah.
            'muhaffizh_id' => $anggota->halaqah->muhaffizh_id,
            'dicatat_oleh' => Auth::id(),
        ]);

        return redirect()->route('halaqah.show', $anggota->halaqah_id)
            ->with('success', 'Setoran '.$this->namaSantri($anggota).' sebanyak '.
                $this->halaman($setoran->jumlah_halaman).' tercatat.');
    }

    public function edit(Setoran $setoran): View
    {
        $this->pastikanBolehDisentuh($setoran);

        $setoran->load('anggotaHalaqah.halaqah');
        $halaqah = $setoran->anggotaHalaqah->halaqah;

        return view('setoran.form', [
            'setoran' => $setoran,
            'halaqah' => $halaqah,
            'anggota' => $this->anggotaAktif($halaqah),
            'anggotaTerpilih' => $setoran->anggota_halaqah_id,
        ]);
    }

    public function update(Request $request, Setoran $setoran): RedirectResponse
    {
        $this->pastikanBolehDisentuh($setoran);

        $data = $this->validated($request);
        $this->anggotaBolehDisentuh($data['anggota_halaqah_id']);

        // muhaffizh_id sengaja tidak ikut diperbarui: penyimaknya tetap orang
        // yang menyimak saat itu, bukan pengampu hari ini.
        $setoran->update($data);

        return redirect()->route('setoran.index')
            ->with('success', 'Setoran diperbarui.');
    }

    public function destroy(Setoran $setoran): RedirectResponse
    {
        $this->pastikanBolehDisentuh($setoran);

        $setoran->delete();

        return back()->with('success', 'Setoran dihapus.');
    }

    public function export(Request $request): StreamedResponse
    {
        $namaBerkas = 'setoran-'.now()->format('Ymd-His').'.csv';
        $query = $this->terlingkup()
            ->with(['anggotaHalaqah.pendaftaran.peserta:id,nama', 'anggotaHalaqah.halaqah:id,nama', 'muhaffizh:id,nama', 'pencatat:id,name'])
            ->when($request->filled('halaqah_id'), fn ($q) => $q->untukHalaqah($request->integer('halaqah_id')))
            ->orderBy('tanggal');

        return response()->streamDownload(function () use ($query) {
            $keluaran = fopen('php://output', 'w');

            fwrite($keluaran, "\xEF\xBB\xBF");
            fputcsv($keluaran, [
                'Tanggal', 'Santri', 'Halaqah', 'Jenis', 'Halaman',
                'Bacaan', 'Kualitas', 'Penyimak', 'Dicatat Oleh', 'Catatan',
            ]);

            $query->chunk(200, function ($baris) use ($keluaran) {
                foreach ($baris as $item) {
                    fputcsv($keluaran, [
                        $item->tanggal?->format('Y-m-d'),
                        $item->anggotaHalaqah?->pendaftaran?->peserta?->nama,
                        $item->anggotaHalaqah?->halaqah?->nama,
                        $item->jenis_label,
                        $item->jumlah_halaman,
                        $item->bacaan,
                        $item->kualitas_label,
                        $item->muhaffizh?->nama,
                        $item->pencatat?->name,
                        $item->catatan,
                    ]);
                }
            });

            fclose($keluaran);
        }, $namaBerkas, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Setoran yang boleh dilihat user ini.
     */
    protected function terlingkup()
    {
        $lingkup = $this->lingkupMuhaffizh('setoran.view-all');

        return Setoran::query()->when($lingkup !== null, fn ($q) => $q->diampuOleh($lingkup));
    }

    protected function halaqahTerlingkup()
    {
        $lingkup = $this->lingkupMuhaffizh('halaqah.view-all');

        return Halaqah::query()
            // Kode halaqah hanya unik di dalam angkatannya, jadi "H-01" bisa
            // muncul berkali-kali. Tanpa nama angkatannya, dua pilihan di
            // dropdown terlihat persis sama dan tidak bisa dibedakan.
            ->with('angkatan:id,nama')
            ->when($lingkup !== null, fn ($q) => $q->where('muhaffizh_id', $lingkup))
            ->orderByDesc('angkatan_id')
            ->orderBy('kode');
    }

    /**
     * @return Collection<int, AnggotaHalaqah>
     */
    protected function anggotaAktif(Halaqah $halaqah)
    {
        return $halaqah->anggotaAktif()
            ->with('pendaftaran.peserta:id,nama')
            ->get()
            ->sortBy(fn (AnggotaHalaqah $a) => $a->pendaftaran?->peserta?->nama)
            ->values();
    }

    /**
     * Pastikan keanggotaan yang dituju memang berada di halaqah yang boleh
     * disentuh user ini — jangan percaya id yang dikirim formulir.
     */
    protected function anggotaBolehDisentuh(int $id): AnggotaHalaqah
    {
        $anggota = AnggotaHalaqah::with('halaqah')->findOrFail($id);
        $lingkup = $this->lingkupMuhaffizh('halaqah.view-all');

        abort_if($lingkup !== null && $anggota->halaqah?->muhaffizh_id !== $lingkup, 403,
            'Santri ini bukan asuhan Anda.');

        return $anggota;
    }

    protected function pastikanBolehDisentuh(Setoran $setoran): void
    {
        $lingkup = $this->lingkupMuhaffizh('setoran.view-all');

        if ($lingkup === null) {
            return;
        }

        $setoran->loadMissing('anggotaHalaqah.halaqah');

        abort_if($setoran->anggotaHalaqah?->halaqah?->muhaffizh_id !== $lingkup, 403,
            'Setoran ini bukan dari halaqah asuhan Anda.');
    }

    /**
     * Ringkasan angka untuk daftar yang sedang ditampilkan.
     *
     * @return array<string, string>
     */
    protected function rekapDaftar(Request $request): array
    {
        $dasar = fn () => $this->terlingkup()
            ->when($request->filled('halaqah_id'), fn ($q) => $q->untukHalaqah($request->integer('halaqah_id')))
            ->when($request->filled('dari'), fn ($q) => $q->whereDate('tanggal', '>=', $request->date('dari')))
            ->when($request->filled('sampai'), fn ($q) => $q->whereDate('tanggal', '<=', $request->date('sampai')));

        $ziyadah = (float) $dasar()->ziyadah()->sum('jumlah_halaman');
        $murajaah = (float) $dasar()->murajaah()->sum('jumlah_halaman');

        return [
            'Ziyadah' => $this->halaman($ziyadah).' · '.Setoran::setaraJuz($ziyadah),
            "Muraja'ah" => $this->halaman($murajaah),
            'Jumlah Setoran' => (string) $dasar()->count(),
        ];
    }

    protected function halaman(float|string $jumlah): string
    {
        return rtrim(rtrim(number_format((float) $jumlah, 1, ',', '.'), '0'), ',').' halaman';
    }

    protected function namaSantri(AnggotaHalaqah $anggota): string
    {
        return $anggota->loadMissing('pendaftaran.peserta')->pendaftaran?->peserta?->nama ?? 'santri';
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request): array
    {
        return $request->validate([
            'anggota_halaqah_id' => ['required', 'integer', 'exists:anggota_halaqah,id'],
            'tanggal' => ['required', 'date', 'before_or_equal:today'],
            'jenis' => ['required', Rule::in(['ziyadah', 'murajaah'])],
            // Kelipatan 0,5 halaman: setengah halaman lazim dipakai.
            'jumlah_halaman' => ['required', 'numeric', 'min:0.5', 'max:100', 'multiple_of:0.5'],
            'juz' => ['nullable', 'integer', 'min:1', 'max:30'],
            'surah' => ['nullable', 'string', 'max:60'],
            'ayat_dari' => ['nullable', 'integer', 'min:1', 'max:286'],
            'ayat_sampai' => ['nullable', 'integer', 'min:1', 'max:286', 'gte:ayat_dari'],
            'kualitas' => ['required', Rule::in(['mumtaz', 'jayyid', 'maqbul', 'perlu_diulang'])],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ], [
            'tanggal.before_or_equal' => 'Setoran tidak bisa dicatat untuk tanggal yang belum tiba.',
            'jumlah_halaman.multiple_of' => 'Jumlah halaman diisi kelipatan 0,5 — mis. 1, 1,5, atau 2.',
            'ayat_sampai.gte' => 'Ayat akhir tidak boleh lebih kecil dari ayat awal.',
        ], [
            'anggota_halaqah_id' => 'santri',
            'tanggal' => 'tanggal',
            'jenis' => 'jenis setoran',
            'jumlah_halaman' => 'jumlah halaman',
            'juz' => 'juz',
            'surah' => 'surah',
            'ayat_dari' => 'ayat awal',
            'ayat_sampai' => 'ayat akhir',
            'kualitas' => 'kualitas',
            'catatan' => 'catatan',
        ]);
    }
}
