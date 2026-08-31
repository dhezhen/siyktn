<?php

namespace App\Http\Controllers;

use App\Models\Angkatan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AngkatanController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:angkatan.view', only: ['index', 'show']),
            new Middleware('permission:angkatan.create', only: ['create', 'store']),
            new Middleware('permission:angkatan.update', only: ['edit', 'update']),
            new Middleware('permission:angkatan.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $angkatan = Angkatan::query()
            ->withCount([
                'pendaftaran as peserta_aktif_count' => fn ($q) => $q->whereIn('status_pendaftaran', ['menunggu', 'disetujui'])->where('status', 'aktif'),
                'pendaftaran as peserta_putra_aktif_count' => fn ($q) => $q->whereIn('status_pendaftaran', ['menunggu', 'disetujui'])->where('status', 'aktif')->whereHas('peserta', fn ($p) => $p->where('jenis_kelamin', 'L')),
                'pendaftaran as peserta_putri_aktif_count' => fn ($q) => $q->whereIn('status_pendaftaran', ['menunggu', 'disetujui'])->where('status', 'aktif')->whereHas('peserta', fn ($p) => $p->where('jenis_kelamin', 'P')),
            ])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($sub) => $sub
                ->where('nama', 'like', '%'.$request->string('q').'%')
                ->orWhere('kode', 'like', '%'.$request->string('q').'%')))
            ->orderByDesc('tahun')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('angkatan.index', ['angkatan' => $angkatan]);
    }

    public function show(Angkatan $angkatan): View
    {
        $angkatan->loadCount([
            'pendaftaran',
            'pendaftaran as peserta_aktif_count' => fn ($q) => $q->whereIn('status_pendaftaran', ['menunggu', 'disetujui'])->where('status', 'aktif'),
            'pendaftaran as peserta_putra_aktif_count' => fn ($q) => $q->whereIn('status_pendaftaran', ['menunggu', 'disetujui'])->where('status', 'aktif')->whereHas('peserta', fn ($p) => $p->where('jenis_kelamin', 'L')),
            'pendaftaran as peserta_putri_aktif_count' => fn ($q) => $q->whereIn('status_pendaftaran', ['menunggu', 'disetujui'])->where('status', 'aktif')->whereHas('peserta', fn ($p) => $p->where('jenis_kelamin', 'P')),
            'pendaftaran as peserta_lulus_count' => fn ($q) => $q->where('status', 'lulus'),
        ]);

        return view('angkatan.show', [
            'angkatan' => $angkatan,
            'pendaftaran' => $angkatan->pendaftaran()
                ->with('peserta')
                ->orderByRaw('nomor_induk IS NULL, nomor_induk')
                ->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('angkatan.form', ['angkatan' => new Angkatan(['tahun' => now()->year, 'status' => 'persiapan', 'kuota' => 0, 'kuota_putra' => 0, 'kuota_putri' => 0])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $angkatan = Angkatan::create($this->validated($request));

        return redirect()->route('angkatan.show', $angkatan)
            ->with('success', "Angkatan {$angkatan->nama} berhasil dibuat.");
    }

    public function edit(Angkatan $angkatan): View
    {
        return view('angkatan.form', ['angkatan' => $angkatan]);
    }

    public function update(Request $request, Angkatan $angkatan): RedirectResponse
    {
        $angkatan->update($this->validated($request, $angkatan));

        return redirect()->route('angkatan.index')
            ->with('success', "Angkatan {$angkatan->nama} berhasil diperbarui.");
    }

    public function destroy(Angkatan $angkatan): RedirectResponse
    {
        // Angkatan yang sudah punya peserta tidak boleh hilang begitu saja.
        if ($angkatan->pendaftaran()->exists()) {
            return back()->with('error',
                "Angkatan {$angkatan->nama} masih memiliki {$angkatan->pendaftaran()->count()} peserta. Pindahkan pesertanya terlebih dahulu.");
        }

        $nama = $angkatan->nama;
        $angkatan->delete();

        return redirect()->route('angkatan.index')->with('success', "Angkatan {$nama} dihapus.");
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?Angkatan $angkatan = null): array
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'kode' => [
                'required', 'string', 'max:20', 'regex:/^[A-Za-z0-9\-]+$/',
                Rule::unique('angkatan', 'kode')->ignore($angkatan?->id),
            ],
            'tahun' => ['required', 'integer', 'min:2000', 'max:2100'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'kuota' => ['required', 'integer', 'min:0', 'max:9999'],
            'kuota_putra' => ['required', 'integer', 'min:0', 'max:9999'],
            'kuota_putri' => ['required', 'integer', 'min:0', 'max:9999'],
            'status' => ['required', Rule::in(['persiapan', 'berjalan', 'selesai'])],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ], [
            'kode.regex' => 'Kode hanya boleh huruf, angka, dan tanda hubung. Contoh: AK-12.',
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.',
        ], [
            'nama' => 'nama angkatan',
            'kode' => 'kode',
            'tahun' => 'tahun',
            'tanggal_mulai' => 'tanggal mulai',
            'tanggal_selesai' => 'tanggal selesai',
            'kuota' => 'kuota total',
            'kuota_putra' => 'kuota putra',
            'kuota_putri' => 'kuota putri',
            'status' => 'status',
            'keterangan' => 'keterangan',
        ]);
    }
}
