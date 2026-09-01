<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\MembatasiKeMuhaffizh;
use App\Models\AnggotaHalaqah;
use App\Models\Angkatan;
use App\Models\Halaqah;
use App\Models\Muhaffizh;
use App\Models\Pendaftaran;
use App\Models\Setoran;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HalaqahController extends Controller implements HasMiddleware
{
    use MembatasiKeMuhaffizh;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:halaqah.view', only: ['index', 'show']),
            new Middleware('permission:halaqah.create', only: ['create', 'store']),
            new Middleware('permission:halaqah.delete', only: ['destroy']),
            // Menempatkan dan memindahkan santri adalah perubahan isi halaqah,
            // jadi ikut izin update — bukan izin tersendiri.
            new Middleware('permission:halaqah.update', only: ['edit', 'update', 'tempatkan', 'pindahkan', 'keluarkan']),
        ];
    }

    public function index(Request $request): View
    {
        $lingkup = $this->lingkupMuhaffizh('halaqah.view-all');

        $halaqah = Halaqah::query()
            ->with(['angkatan:id,nama,kode,tahun', 'muhaffizh:id,nama,kode'])
            ->withCount('anggotaAktif')
            // Muhaffizh hanya melihat halaqah asuhannya sendiri.
            ->when($lingkup !== null, fn ($q) => $q->where('muhaffizh_id', $lingkup))
            ->when($request->filled('angkatan_id'), fn ($q) => $q->where('angkatan_id', $request->integer('angkatan_id')))
            ->when($request->filled('muhaffizh_id'), fn ($q) => $q->where('muhaffizh_id', $request->integer('muhaffizh_id')))
            ->when($request->filled('jenis_kelamin'), fn ($q) => $q->where('jenis_kelamin', $request->string('jenis_kelamin')))
            ->when($request->filled('status'), fn ($q) => $q->where('is_aktif', $request->string('status')->toString() === 'aktif'))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(fn ($sub) => $sub->where('nama', 'like', $term)->orWhere('kode', 'like', $term));
            })
            ->orderByDesc('angkatan_id')
            ->orderBy('kode')
            ->paginate(15)
            ->withQueryString();

        return view('halaqah.index', [
            'halaqah' => $halaqah,
            'hanyaMilikSendiri' => $lingkup !== null,
            'daftarAngkatan' => Angkatan::orderByDesc('tahun')->get(['id', 'nama', 'kode']),
            'daftarMuhaffizh' => Muhaffizh::aktif()->orderBy('nama')->get(['id', 'nama', 'kode']),
        ]);
    }

    public function show(Halaqah $halaqah): View
    {
        $this->pastikanBolehDilihat($halaqah);

        $halaqah->load(['angkatan', 'muhaffizh'])->loadCount('anggotaAktif');

        $anggota = $halaqah->anggota()
            ->with(['pendaftaran.peserta:id,nama,jenis_kelamin,no_hp,foto', 'setoranTerakhir'])
            ->withSum(['setoran as ziyadah_halaman' => fn ($q) => $q->where('jenis', 'ziyadah')], 'jumlah_halaman')
            ->withCount('setoran')
            ->orderByDesc('tanggal_bergabung')
            ->get();

        return view('halaqah.show', [
            'halaqah' => $halaqah,
            'anggotaAktif' => $anggota->where('is_aktif', true),
            'riwayat' => $anggota->where('is_aktif', false),
            'calonSantri' => $this->calonSantri($halaqah),
            'halaqahTujuan' => $this->halaqahSepadan($halaqah),
            'setoranTerakhir' => Setoran::query()
                ->untukHalaqah($halaqah->id)
                ->with(['anggotaHalaqah.pendaftaran.peserta:id,nama', 'pencatat:id,name', 'muhaffizh:id,nama'])
                ->orderByDesc('tanggal')
                ->orderByDesc('id')
                ->limit(10)
                ->get(),
        ]);
    }

    public function laporan(Halaqah $halaqah): View
    {
        $this->pastikanBolehDilihat($halaqah);

        $halaqah->load(['angkatan', 'muhaffizh']);

        $anggota = $halaqah->anggotaAktif()
            ->with(['pendaftaran.peserta'])
            ->withSum(['setoran as ziyadah_halaman' => fn ($q) => $q->where('jenis', 'ziyadah')], 'jumlah_halaman')
            ->withSum(['setoran as murajaah_halaman' => fn ($q) => $q->where('jenis', 'murajaah')], 'jumlah_halaman')
            ->withCount('setoran')
            ->get();

        foreach ($anggota as $a) {
            $setorans = $a->setoran()->get(['kualitas']);
            $scoreTotal = 0;
            $count = 0;
            foreach ($setorans as $s) {
                $score = match ($s->kualitas) {
                    'mumtaz' => 4,
                    'jayyid_jiddan' => 3,
                    'jayyid' => 2,
                    default => 0,
                };
                if ($score > 0) {
                    $scoreTotal += $score;
                    $count++;
                }
            }
            $a->rata_rata_skor = $count > 0 ? $scoreTotal / $count : 0;
            $a->predikat = match (true) {
                $a->rata_rata_skor >= 3.5 => 'Mumtaz',
                $a->rata_rata_skor >= 2.5 => 'Jayyid Jiddan',
                $a->rata_rata_skor > 0 => 'Jayyid',
                default => '-',
            };
        }

        return view('halaqah.laporan', [
            'halaqah' => $halaqah,
            'anggota' => $anggota,
        ]);
    }

    public function eksporLaporan(Halaqah $halaqah): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $this->pastikanBolehDilihat($halaqah);

        $halaqah->load(['angkatan', 'muhaffizh']);

        $anggota = $halaqah->anggotaAktif()
            ->with(['pendaftaran.peserta'])
            ->withSum(['setoran as ziyadah_halaman' => fn ($q) => $q->where('jenis', 'ziyadah')], 'jumlah_halaman')
            ->withSum(['setoran as murajaah_halaman' => fn ($q) => $q->where('jenis', 'murajaah')], 'jumlah_halaman')
            ->withCount('setoran')
            ->get();

        foreach ($anggota as $a) {
            $setorans = $a->setoran()->get(['kualitas']);
            $scoreTotal = 0;
            $count = 0;
            foreach ($setorans as $s) {
                $score = match ($s->kualitas) {
                    'mumtaz' => 4,
                    'jayyid_jiddan' => 3,
                    'jayyid' => 2,
                    default => 0,
                };
                if ($score > 0) {
                    $scoreTotal += $score;
                    $count++;
                }
            }
            $a->rata_rata_skor = $count > 0 ? $scoreTotal / $count : 0;
            $a->predikat = match (true) {
                $a->rata_rata_skor >= 3.5 => 'Mumtaz',
                $a->rata_rata_skor >= 2.5 => 'Jayyid Jiddan',
                $a->rata_rata_skor > 0 => 'Jayyid',
                default => '-',
            };
        }

        $namaBerkas = 'laporan-syahadah-'.$halaqah->kode.'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($anggota) {
            $keluaran = fopen('php://output', 'w');

            // BOM untuk UTF-8 Excel
            fwrite($keluaran, "\xEF\xBB\xBF");
            fputcsv($keluaran, [
                'Nomor Induk / Nomor Syahadah', 'Nama Santri', 'Jenis Kelamin', 'Tempat Lahir', 'Tanggal Lahir',
                'Hafalan Baru (Ziyadah)', 'Setara Juz', 'Rata-rata Skor', 'Predikat'
            ]);

            foreach ($anggota as $item) {
                $peserta = $item->pendaftaran?->peserta;
                $ziyadah = (float) ($item->ziyadah_halaman ?? 0);
                
                fputcsv($keluaran, [
                    $item->pendaftaran?->nomor_induk ?: '-',
                    $peserta?->nama ?? '-',
                    $peserta?->jenis_kelamin_label ?? '-',
                    $peserta?->tempat_lahir ?? '-',
                    $peserta?->tanggal_lahir?->format('d/m/Y') ?? '-',
                    rtrim(rtrim(number_format($ziyadah, 1, ',', '.'), '0'), ',') . ' Halaman',
                    \App\Models\Setoran::setaraJuz($ziyadah),
                    number_format($item->rata_rata_skor, 2, ',', '.'),
                    $item->predikat,
                ]);
            }

            fclose($keluaran);
        }, $namaBerkas);
    }
    public function cetakSyahadah(AnggotaHalaqah $anggota): View
    {
        $halaqah = $anggota->halaqah;
        $this->pastikanBolehDilihat($halaqah);

        $anggota->load([
            'pendaftaran.peserta',
            'halaqah.angkatan',
            'halaqah.muhaffizh',
        ])->loadSum(['setoran as ziyadah_halaman' => fn ($q) => $q->where('jenis', 'ziyadah')], 'jumlah_halaman');

        $setorans = $anggota->setoran()->get(['kualitas']);
        $scoreTotal = 0;
        $count = 0;
        foreach ($setorans as $s) {
            $score = match ($s->kualitas) {
                'mumtaz' => 4,
                'jayyid_jiddan' => 3,
                'jayyid' => 2,
                default => 0,
            };
            if ($score > 0) {
                $scoreTotal += $score;
                $count++;
            }
        }
        $anggota->rata_rata_skor = $count > 0 ? $scoreTotal / $count : 0;
        $anggota->predikat = match (true) {
            $anggota->rata_rata_skor >= 3.5 => 'Mumtaz',
            $anggota->rata_rata_skor >= 2.5 => 'Jayyid Jiddan',
            $anggota->rata_rata_skor > 0 => 'Jayyid',
            default => '-',
        };
        $anggota->predikat_arab = match ($anggota->predikat) {
            'Mumtaz' => 'مُمْتَاز',
            'Jayyid Jiddan' => 'جَيِّد جِدًّا',
            'Jayyid' => 'جَيِّد',
            default => '-',
        };

        return view('halaqah.syahadah', [
            'anggota' => $anggota,
            'peserta' => $anggota->pendaftaran->peserta,
            'halaqah' => $halaqah,
        ]);
    }
    /**
     * Muhaffizh hanya boleh membuka halaqah asuhannya sendiri.
     */
    protected function pastikanBolehDilihat(Halaqah $halaqah): void
    {
        $lingkup = $this->lingkupMuhaffizh('halaqah.view-all');

        abort_if($lingkup !== null && $halaqah->muhaffizh_id !== $lingkup, 403,
            'Halaqah ini bukan asuhan Anda.');
    }

    public function create(Request $request): View
    {
        $angkatan = $this->angkatanTerbuka();

        return view('halaqah.form', [
            'halaqah' => new Halaqah([
                'angkatan_id' => $request->integer('angkatan_id') ?: $angkatan->first()?->id,
                'jenis_kelamin' => 'L',
                'kuota' => 15,
                'is_aktif' => true,
            ]),
            'daftarAngkatan' => $angkatan,
            'daftarMuhaffizh' => Muhaffizh::aktif()->orderBy('nama')->get(['id', 'nama', 'kode', 'jenis_kelamin']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $halaqah = Halaqah::create($this->validated($request));

        return redirect()->route('halaqah.show', $halaqah)
            ->with('success', 'Halaqah '.$halaqah->nama.' berhasil dibuat. Tempatkan santrinya di halaman ini.');
    }

    public function edit(Halaqah $halaqah): View
    {
        return view('halaqah.form', [
            'halaqah' => $halaqah,
            'daftarAngkatan' => $this->angkatanTerbuka($halaqah),
            'daftarMuhaffizh' => Muhaffizh::query()
                ->where(fn ($q) => $q->where('status', 'aktif')->orWhere('id', $halaqah->muhaffizh_id))
                ->orderBy('nama')
                ->get(['id', 'nama', 'kode', 'jenis_kelamin']),
        ]);
    }

    public function update(Request $request, Halaqah $halaqah): RedirectResponse
    {
        $data = $this->validated($request, $halaqah);

        // Kuota tidak boleh diturunkan di bawah jumlah santri yang sudah ada —
        // kalau dibiarkan, halaqahnya langsung kelebihan muatan tanpa ada yang
        // bisa dilakukan petugas selain memindahkan santri satu per satu.
        $terisi = $halaqah->anggotaAktif()->count();

        if ($data['kuota'] > 0 && $data['kuota'] < $terisi) {
            return back()->withInput()->with('error',
                'Kuota tidak bisa diisi '.$data['kuota'].' karena halaqah ini sudah berisi '.$terisi.
                ' santri. Pindahkan sebagian santrinya terlebih dahulu.');
        }

        $halaqah->update($data);

        return redirect()->route('halaqah.show', $halaqah)
            ->with('success', 'Halaqah '.$halaqah->nama.' berhasil diperbarui.');
    }

    public function destroy(Halaqah $halaqah): RedirectResponse
    {
        // Keanggotaan adalah riwayat pembimbingan; menghapus halaqahnya ikut
        // menghapus jejak itu lewat cascade. Nonaktifkan saja.
        if ($halaqah->anggota()->exists()) {
            return back()->with('error', 'Halaqah '.$halaqah->nama.' sudah pernah berisi santri sehingga tidak bisa dihapus. '.
                'Ubah statusnya menjadi nonaktif bila sudah tidak dipakai.');
        }

        $nama = $halaqah->nama;
        $halaqah->delete();

        return redirect()->route('halaqah.index')->with('success', 'Halaqah '.$nama.' dihapus.');
    }

    /**
     * Tempatkan satu atau beberapa santri sekaligus ke halaqah ini.
     */
    public function tempatkan(Request $request, Halaqah $halaqah): RedirectResponse
    {
        $data = $request->validate([
            'pendaftaran_id' => ['required', 'array', 'min:1'],
            'pendaftaran_id.*' => ['integer', 'distinct', 'exists:pendaftaran,id'],
            'tanggal_bergabung' => ['nullable', 'date'],
        ], [
            'pendaftaran_id.required' => 'Pilih dulu santri yang akan ditempatkan.',
        ], [
            'pendaftaran_id' => 'santri',
            'tanggal_bergabung' => 'tanggal bergabung',
        ]);

        if (! $halaqah->is_aktif) {
            return back()->with('error', 'Halaqah '.$halaqah->nama.' berstatus nonaktif, jadi tidak bisa menerima santri baru.');
        }

        // Disaring ulang di server: daftar di layar bisa saja sudah basi karena
        // petugas lain menempatkan santri yang sama beberapa detik sebelumnya.
        $layak = $this->calonSantri($halaqah)->whereIn('id', $data['pendaftaran_id']);
        $ditolak = count($data['pendaftaran_id']) - $layak->count();

        if ($layak->isEmpty()) {
            return back()->with('error', 'Tidak ada santri yang bisa ditempatkan. '.
                'Kemungkinan mereka sudah masuk halaqah lain lebih dulu.');
        }

        $sisa = $halaqah->sisa_kuota;

        if ($sisa !== null && $layak->count() > $sisa) {
            return back()->with('error', 'Kuota halaqah '.$halaqah->nama.' tinggal '.$sisa.' kursi, '.
                'sedangkan Anda memilih '.$layak->count().' santri.');
        }

        $tanggal = $data['tanggal_bergabung'] ?? now()->toDateString();

        DB::transaction(function () use ($layak, $halaqah, $tanggal) {
            foreach ($layak as $pendaftaran) {
                // updateOrCreate, bukan create: santri yang dulu pernah keluar
                // dari halaqah ini boleh kembali tanpa melanggar indeks unik
                // (halaqah_id, pendaftaran_id).
                AnggotaHalaqah::updateOrCreate(
                    ['halaqah_id' => $halaqah->id, 'pendaftaran_id' => $pendaftaran->id],
                    [
                        'tanggal_bergabung' => $tanggal,
                        'tanggal_keluar' => null,
                        'is_aktif' => true,
                        'alasan_pindah' => null,
                    ],
                );
            }
        });

        $pesan = $layak->count().' santri ditempatkan di '.$halaqah->nama.'.';

        if ($ditolak > 0) {
            $pesan .= ' '.$ditolak.' pilihan dilewati karena sudah tidak memenuhi syarat.';
        }

        return back()->with('success', $pesan);
    }

    /**
     * Pindahkan santri ke halaqah lain di angkatan yang sama.
     */
    public function pindahkan(Request $request, AnggotaHalaqah $anggota): RedirectResponse
    {
        $data = $request->validate([
            'halaqah_id' => ['required', 'integer', 'exists:halaqah,id', Rule::notIn([$anggota->halaqah_id])],
            'alasan_pindah' => ['nullable', 'string', 'max:255'],
        ], [
            'halaqah_id.not_in' => 'Santri sudah berada di halaqah tersebut.',
        ], [
            'halaqah_id' => 'halaqah tujuan',
            'alasan_pindah' => 'alasan pindah',
        ]);

        if (! $anggota->is_aktif) {
            return back()->with('error', 'Keanggotaan ini sudah ditutup, jadi tidak ada yang bisa dipindahkan.');
        }

        $asal = $anggota->halaqah;
        $tujuan = Halaqah::findOrFail($data['halaqah_id']);

        if ($galat = $this->alasanTidakBisaMenerima($tujuan, $anggota)) {
            return back()->with('error', $galat);
        }

        DB::transaction(function () use ($anggota, $tujuan, $data) {
            // Tutup dulu, baru buka yang baru: kolom penjaga kunci_aktif unik
            // se-tabel, jadi urutannya tidak boleh dibalik.
            $anggota->tutup($data['alasan_pindah'] ?? null);

            AnggotaHalaqah::updateOrCreate(
                ['halaqah_id' => $tujuan->id, 'pendaftaran_id' => $anggota->pendaftaran_id],
                [
                    'tanggal_bergabung' => now()->toDateString(),
                    'tanggal_keluar' => null,
                    'is_aktif' => true,
                    'alasan_pindah' => null,
                ],
            );
        });

        return back()->with('success', $this->namaSantri($anggota).' dipindahkan dari '.
            $asal->nama.' ke '.$tujuan->nama.'.');
    }

    /**
     * Keluarkan santri dari halaqah tanpa langsung menempatkannya di tempat lain.
     */
    public function keluarkan(Request $request, AnggotaHalaqah $anggota): RedirectResponse
    {
        $data = $request->validate([
            'alasan_pindah' => ['nullable', 'string', 'max:255'],
        ], [], ['alasan_pindah' => 'alasan']);

        if (! $anggota->is_aktif) {
            return back()->with('error', 'Santri ini sudah tidak aktif di halaqah tersebut.');
        }

        $anggota->tutup($data['alasan_pindah'] ?? null);

        return back()->with('success', $this->namaSantri($anggota).
            ' dikeluarkan dari halaqah. Riwayatnya tetap tersimpan.');
    }

    /**
     * Santri satu angkatan yang belum punya halaqah dan jenis kelaminnya cocok.
     *
     * @return Collection<int, Pendaftaran>
     */
    protected function calonSantri(Halaqah $halaqah): Collection
    {
        return Pendaftaran::query()
            ->belumBerhalaqah()
            ->where('angkatan_id', $halaqah->angkatan_id)
            ->whereHas('peserta', fn ($q) => $q->where('jenis_kelamin', $halaqah->jenis_kelamin))
            ->with('peserta:id,nama,jenis_kelamin,tempat_lahir')
            ->orderByRaw('nomor_induk IS NULL, nomor_induk')
            ->get();
    }

    /**
     * Halaqah lain yang boleh menerima santri dari halaqah ini.
     *
     * @return Collection<int, Halaqah>
     */
    protected function halaqahSepadan(Halaqah $halaqah): Collection
    {
        return Halaqah::query()
            ->aktif()
            ->where('angkatan_id', $halaqah->angkatan_id)
            ->where('jenis_kelamin', $halaqah->jenis_kelamin)
            ->where('id', '!=', $halaqah->id)
            ->withCount('anggotaAktif')
            ->orderBy('kode')
            ->get();
    }

    /**
     * Alasan halaqah tujuan menolak seorang santri, atau null bila boleh masuk.
     */
    protected function alasanTidakBisaMenerima(Halaqah $tujuan, AnggotaHalaqah $anggota): ?string
    {
        $anggota->loadMissing('pendaftaran.peserta');
        $pendaftaran = $anggota->pendaftaran;

        if (! $tujuan->is_aktif) {
            return 'Halaqah '.$tujuan->nama.' berstatus nonaktif.';
        }

        if ($tujuan->angkatan_id !== $pendaftaran->angkatan_id) {
            return 'Halaqah '.$tujuan->nama.' berada di angkatan yang berbeda.';
        }

        if ($tujuan->jenis_kelamin !== $pendaftaran->peserta?->jenis_kelamin) {
            return 'Halaqah '.$tujuan->nama.' adalah halaqah '.$tujuan->jenis_kelamin_label.'.';
        }

        if ($tujuan->isPenuh()) {
            return 'Halaqah '.$tujuan->nama.' sudah penuh.';
        }

        return null;
    }

    protected function namaSantri(AnggotaHalaqah $anggota): string
    {
        return $anggota->loadMissing('pendaftaran.peserta')->pendaftaran?->peserta?->nama ?? 'Santri';
    }

    /**
     * Angkatan yang masuk akal untuk punya halaqah — yang sudah selesai tidak
     * ditawarkan lagi, kecuali memang sedang dipakai halaqah yang diedit.
     *
     * @return Collection<int, Angkatan>
     */
    protected function angkatanTerbuka(?Halaqah $halaqah = null): Collection
    {
        return Angkatan::query()
            ->where(fn ($q) => $q->whereIn('status', ['persiapan', 'berjalan'])
                ->when($halaqah?->angkatan_id, fn ($sub, $id) => $sub->orWhere('id', $id)))
            ->orderByDesc('tahun')
            ->get(['id', 'nama', 'kode', 'status']);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?Halaqah $halaqah = null): array
    {
        $data = $request->validate([
            'angkatan_id' => ['required', 'exists:angkatan,id'],
            'muhaffizh_id' => ['nullable', 'exists:muhaffizh,id'],
            'kode' => [
                'required', 'string', 'max:30', 'regex:/^[A-Za-z0-9\-]+$/',
                Rule::unique('halaqah', 'kode')
                    ->where('angkatan_id', $request->integer('angkatan_id'))
                    ->ignore($halaqah?->id),
            ],
            'nama' => ['required', 'string', 'max:255'],
            'jenis_kelamin' => ['required', Rule::in(['L', 'P'])],
            'kuota' => ['required', 'integer', 'min:0', 'max:999'],
            'ruang' => ['nullable', 'string', 'max:60'],
            'jadwal' => ['nullable', 'string', 'max:120'],
            'is_aktif' => ['nullable', 'boolean'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ], [
            'kode.regex' => 'Kode hanya boleh huruf, angka, dan tanda hubung. Contoh: H-01.',
            'kode.unique' => 'Kode ini sudah dipakai halaqah lain di angkatan yang sama.',
        ], [
            'angkatan_id' => 'angkatan',
            'muhaffizh_id' => 'muhaffizh',
            'kode' => 'kode',
            'nama' => 'nama halaqah',
            'jenis_kelamin' => 'jenis kelamin',
            'kuota' => 'kuota',
            'ruang' => 'ruang',
            'jadwal' => 'jadwal',
            'keterangan' => 'keterangan',
        ]);

        $data['is_aktif'] = $request->boolean('is_aktif');

        if ($data['muhaffizh_id']) {
            $muhaffizh = Muhaffizh::find($data['muhaffizh_id']);
            if ($muhaffizh && $muhaffizh->jenis_kelamin !== $data['jenis_kelamin']) {
                $labelHalaqah = $data['jenis_kelamin'] === 'L' ? 'Ikhwan (Laki-laki)' : 'Akhwat (Perempuan)';
                $labelMuhaffizh = $muhaffizh->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan';
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'muhaffizh_id' => "Halaqah {$labelHalaqah} hanya dapat diampu oleh pengampu berkategori sama (Muhaffizh yang Anda pilih berjenis kelamin {$labelMuhaffizh}).",
                ]);
            }
        }

        return $data;
    }
}
