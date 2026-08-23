<?php

namespace App\Http\Controllers;

use App\Models\Angkatan;
use App\Models\Peserta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Services\PendaftaranService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PesertaController extends Controller implements HasMiddleware
{
    public function __construct(protected PendaftaranService $pendaftaran) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:peserta.view', only: ['index', 'show']),
            new Middleware('permission:peserta.create', only: ['create', 'store']),
            new Middleware('permission:peserta.update', only: ['edit', 'update']),
            new Middleware('permission:peserta.delete', only: ['destroy']),
            new Middleware('permission:peserta.export', only: ['export']),
        ];
    }

    public function index(): View
    {
        return view('peserta.index');
    }

    public function show(Peserta $peserta): View
    {
        $peserta->load(['angkatan', 'peninjau:id,name']);

        return view('peserta.show', ['peserta' => $peserta]);
    }

    public function create(Request $request): View
    {
        $angkatan = Angkatan::orderByDesc('tahun')->get();

        $peserta = new Peserta([
            'status' => 'aktif',
            'jenis_kelamin' => 'L',
            'tanggal_masuk' => now()->toDateString(),
            'angkatan_id' => $request->integer('angkatan_id') ?: $angkatan->firstWhere('status', 'berjalan')?->id,
        ]);

        return view('peserta.form', compact('peserta', 'angkatan'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        // Nomor induk boleh dikosongkan — sistem yang membuatkan.
        if (empty($data['nomor_induk'])) {
            $data['nomor_induk'] = Peserta::nomorIndukBerikutnya(Angkatan::findOrFail($data['angkatan_id']));
        }

        if ($request->hasFile('ktp')) {
            $data['ktp_path'] = $this->pendaftaran->simpanKtp($request->file('ktp'));
        }

        unset($data['foto'], $data['ktp']);

        // Peserta yang diinput petugas dianggap sudah terverifikasi, tetapi
        // tetap melewati alur yang sama agar emailnya ikut terkirim.
        $peserta = $this->pendaftaran->daftarkan(array_merge($data, [
            'status_pendaftaran' => 'disetujui',
            'ditinjau_pada' => now(),
            'ditinjau_oleh' => Auth::id(),
        ]), sumber: 'admin');

        $this->storeFoto($request, $peserta);

        return redirect()->route('peserta.index')
            ->with('success', "Peserta {$peserta->nama} ({$peserta->nomor_induk}) berhasil ditambahkan.");
    }

    public function edit(Peserta $peserta): View
    {
        return view('peserta.form', [
            'peserta' => $peserta,
            'angkatan' => Angkatan::orderByDesc('tahun')->get(),
        ]);
    }

    public function update(Request $request, Peserta $peserta): RedirectResponse
    {
        $data = $this->validated($request, $peserta);

        if ($request->hasFile('ktp')) {
            $this->pendaftaran->hapusKtp($peserta->ktp_path);
            $data['ktp_path'] = $this->pendaftaran->simpanKtp($request->file('ktp'));
        }

        // Berkas ditangani terpisah — jangan sampai objek file ikut disimpan
        // ke kolom teks.
        unset($data['foto'], $data['ktp']);

        $peserta->update($data);
        $this->storeFoto($request, $peserta);

        return redirect()->route('peserta.index')
            ->with('success', "Data {$peserta->nama} berhasil diperbarui.");
    }

    public function destroy(Peserta $peserta): RedirectResponse
    {
        $nama = $peserta->nama;
        $peserta->delete();

        return back()->with('success', "Peserta {$nama} dipindahkan ke daftar terhapus.");
    }

    public function export(Request $request): StreamedResponse
    {
        $angkatanId = $request->integer('angkatan_id');
        $filename = 'peserta-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($angkatanId) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'Kode Pendaftaran', 'Nomor Induk', 'Nama', 'NIK', 'Jenis Kelamin', 'Angkatan',
                'Tempat Lahir', 'Tanggal Lahir', 'No HP', 'Email', 'Nama Wali', 'No HP Wali',
                'Tanggal Masuk', 'Status', 'Status Pendaftaran', 'Sumber', 'Didaftarkan Pada',
            ]);

            Peserta::with('angkatan:id,nama')
                ->when($angkatanId, fn ($q) => $q->where('angkatan_id', $angkatanId))
                ->orderBy('nomor_induk')
                ->chunk(500, function ($rows) use ($handle) {
                    foreach ($rows as $peserta) {
                        fputcsv($handle, [
                            $peserta->kode_pendaftaran,
                            $peserta->nomor_induk,
                            $peserta->nama,
                            $peserta->nik,
                            $peserta->jenis_kelamin_label,
                            $peserta->angkatan?->nama,
                            $peserta->tempat_lahir,
                            $peserta->tanggal_lahir?->format('Y-m-d'),
                            $peserta->no_hp,
                            $peserta->email,
                            $peserta->nama_wali,
                            $peserta->no_hp_wali,
                            $peserta->tanggal_masuk?->format('Y-m-d'),
                            $peserta->status,
                            $peserta->status_pendaftaran,
                            $peserta->sumber_pendaftaran,
                            $peserta->didaftarkan_pada?->format('Y-m-d H:i'),
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?Peserta $peserta = null): array
    {
        return $request->validate([
            'angkatan_id' => ['required', 'exists:angkatan,id'],
            'nomor_induk' => [
                'nullable', 'string', 'max:30',
                Rule::unique('peserta', 'nomor_induk')->ignore($peserta?->id),
            ],
            'nama' => ['required', 'string', 'max:100'],
            'nik' => ['nullable', 'digits:16', Rule::unique('peserta', 'nik')->ignore($peserta?->id)],
            'email' => ['nullable', 'email', 'max:150'],
            'jenis_kelamin' => ['required', Rule::in(['L', 'P'])],
            'tempat_lahir' => ['nullable', 'string', 'max:80'],
            'tanggal_lahir' => ['nullable', 'date', 'before:today'],
            'alamat' => ['nullable', 'string', 'max:500'],
            'no_hp' => ['nullable', 'string', 'max:25'],
            'nama_wali' => ['nullable', 'string', 'max:100'],
            'no_hp_wali' => ['nullable', 'string', 'max:25'],
            'tanggal_masuk' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['aktif', 'lulus', 'keluar'])],
            'foto' => ['nullable', 'image', 'max:2048'],
            'ktp' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ], [
            'nik.digits' => 'NIK harus terdiri dari 16 angka.',
            'tanggal_lahir.before' => 'Tanggal lahir harus sebelum hari ini.',
        ], [
            'angkatan_id' => 'angkatan',
            'nomor_induk' => 'nomor induk',
            'nama' => 'nama',
            'jenis_kelamin' => 'jenis kelamin',
            'tempat_lahir' => 'tempat lahir',
            'tanggal_lahir' => 'tanggal lahir',
            'no_hp' => 'nomor HP',
            'nama_wali' => 'nama wali',
            'no_hp_wali' => 'nomor HP wali',
            'tanggal_masuk' => 'tanggal masuk',
            'foto' => 'foto',
            'nik' => 'NIK',
            'email' => 'email',
            'ktp' => 'berkas KTP',
        ]);
    }

    protected function storeFoto(Request $request, Peserta $peserta): void
    {
        if (! $request->hasFile('foto')) {
            return;
        }

        if ($peserta->foto) {
            Storage::disk('public')->delete($peserta->foto);
        }

        $peserta->update(['foto' => $request->file('foto')->store('peserta', 'public')]);
    }
}
