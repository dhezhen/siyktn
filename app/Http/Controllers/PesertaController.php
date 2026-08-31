<?php

namespace App\Http\Controllers;

use App\Models\Angkatan;
use App\Models\Pendaftaran;
use App\Models\Peserta;
use App\Services\PendaftaranService;
use App\Support\KelayakanPendaftaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Mengelola peserta sebagai ORANG. Keikutsertaannya per angkatan ada di
 * model Pendaftaran, dan seorang peserta boleh punya banyak pendaftaran.
 */
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
        $peserta->load(['pendaftaran.angkatan', 'pendaftaran.peninjau:id,name']);

        return view('peserta.show', ['peserta' => $peserta]);
    }

    public function create(Request $request): View
    {
        $angkatan = Angkatan::orderByDesc('tahun')->get();

        return view('peserta.form', [
            'peserta' => new Peserta(['jenis_kelamin' => 'L', 'boleh_mendaftar_lagi' => true]),
            'angkatan' => $angkatan,
            'angkatanTerpilih' => $request->integer('angkatan_id')
                ?: $angkatan->firstWhere('status', 'berjalan')?->id,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $request->validate(
            ['angkatan_id' => ['required', 'exists:angkatan,id']],
            attributes: ['angkatan_id' => 'angkatan']
        );

        $angkatanId = (int) $request->input('angkatan_id');

        // Petugas pun tunduk pada aturan yang sama, supaya tidak ada dua baris
        // orang untuk satu NIK.
        $kelayakan = KelayakanPendaftaran::periksa($data['nik'] ?? null, $angkatanId);

        if (! $kelayakan->boleh) {
            throw ValidationException::withMessages(['nik' => $kelayakan->alasan]);
        }

        $data['ktp_path'] = $request->hasFile('ktp')
            ? $this->pendaftaran->simpanKtp($request->file('ktp'))
            : null;

        $data['foto'] = $request->hasFile('foto')
            ? $request->file('foto')->store('peserta', 'public')
            : null;

        // Peserta yang diinput petugas dianggap sudah terverifikasi, tetapi
        // tetap melewati alur yang sama agar emailnya ikut terkirim.
        $pendaftaran = $this->pendaftaran->daftarkan(
            dataPeserta: $data,
            dataPendaftaran: [
                'angkatan_id' => $angkatanId,
                'nomor_induk' => Pendaftaran::nomorIndukBerikutnya(Angkatan::findOrFail($angkatanId)),
                'status_pendaftaran' => 'disetujui',
                'status' => $request->input('status', 'aktif'),
                'tanggal_masuk' => $request->input('tanggal_masuk') ?: now()->toDateString(),
                'ditinjau_pada' => now(),
                'ditinjau_oleh' => Auth::id(),
            ],
            sumber: 'admin',
        );

        $catatan = $kelayakan->pendaftaranUlang
            ? ' Data orang yang sudah ada dipakai kembali (pendaftaran ulang).'
            : '';

        return redirect()->route('peserta.show', $pendaftaran->peserta)
            ->with('success',
                "Peserta {$pendaftaran->peserta->nama} ({$pendaftaran->nomor_induk}) berhasil ditambahkan.".$catatan);
    }

    public function edit(Peserta $peserta): View
    {
        return view('peserta.form', [
            'peserta' => $peserta,
            'angkatan' => Angkatan::orderByDesc('tahun')->get(),
            'angkatanTerpilih' => null,
        ]);
    }

    public function update(Request $request, Peserta $peserta): RedirectResponse
    {
        $data = $this->validated($request, $peserta);

        if ($request->hasFile('ktp')) {
            $this->pendaftaran->hapusKtp($peserta->ktp_path);
            $data['ktp_path'] = $this->pendaftaran->simpanKtp($request->file('ktp'));
        }

        if ($request->hasFile('foto')) {
            if ($peserta->foto) {
                Storage::disk('public')->delete($peserta->foto);
            }

            $data['foto'] = $request->file('foto')->store('peserta', 'public');
        }

        $peserta->update($data);

        return redirect()->route('peserta.show', $peserta)
            ->with('success', "Data {$peserta->nama} berhasil diperbarui.");
    }

    public function destroy(Peserta $peserta): RedirectResponse
    {
        $nama = $peserta->nama;
        $peserta->delete();

        return redirect()->route('peserta.index')
            ->with('success', "Peserta {$nama} dipindahkan ke daftar terhapus.");
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
                'Tempat Lahir', 'Tanggal Lahir', 'Usia', 'No HP', 'Email', 'Nama Wali', 'No HP Wali',
                'Tanggal Masuk', 'Status', 'Status Pendaftaran', 'Sumber', 'Didaftarkan Pada',
            ]);

            // Satu baris per pendaftaran, sehingga alumni yang ikut dua angkatan
            // muncul dua kali — memang begitu yang diharapkan di rekap.
            Pendaftaran::with(['peserta', 'angkatan:id,nama'])
                ->when($angkatanId, fn ($q) => $q->where('angkatan_id', $angkatanId))
                ->orderBy('angkatan_id')
                ->orderBy('nomor_induk')
                ->chunk(500, function ($rows) use ($handle) {
                    foreach ($rows as $daftar) {
                        $peserta = $daftar->peserta;

                        fputcsv($handle, [
                            $daftar->kode_pendaftaran,
                            $daftar->nomor_induk,
                            $peserta?->nama,
                            $peserta?->nik,
                            $peserta?->jenis_kelamin_label,
                            $daftar->angkatan?->nama,
                            $peserta?->tempat_lahir,
                            $peserta?->tanggal_lahir?->format('Y-m-d'),
                            $peserta?->tanggal_lahir?->age,
                            $peserta?->no_hp,
                            $peserta?->email,
                            $peserta?->nama_wali,
                            $peserta?->no_hp_wali,
                            $daftar->tanggal_masuk?->format('Y-m-d'),
                            $daftar->status,
                            $daftar->status_pendaftaran,
                            $daftar->sumber_pendaftaran,
                            $daftar->didaftarkan_pada?->format('Y-m-d H:i'),
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
        // Saat menambah, keunikan NIK diputuskan KelayakanPendaftaran — NIK yang
        // sudah ada boleh dipakai lagi bila orangnya memang berhak mendaftar
        // ulang. Saat mengubah, NIK tetap tidak boleh bertabrakan.
        $aturanNik = ['nullable', 'digits:16'];

        if ($peserta) {
            $aturanNik[] = Rule::unique('peserta', 'nik')->ignore($peserta->id);
        }

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'nik' => $aturanNik,
            'email' => ['nullable', 'email', 'max:150'],
            'jenis_kelamin' => ['required', Rule::in(['L', 'P'])],
            'tempat_lahir' => ['nullable', 'string', 'max:80'],
            'tanggal_lahir' => ['nullable', 'date', 'before:today'],
            'kewarganegaraan' => ['nullable', Rule::in(['WNI', 'WNA'])],
            'negara' => ['nullable', 'string', 'max:100'],
            'provinsi' => ['nullable', 'string', 'max:100'],
            'kabupaten_kota' => ['nullable', 'string', 'max:100'],
            'alamat' => ['nullable', 'string', 'max:500'],
            'no_hp' => ['nullable', 'string', 'max:25'],
            'nama_wali' => ['nullable', 'string', 'max:100'],
            'no_hp_wali' => ['nullable', 'string', 'max:25'],
            'boleh_mendaftar_lagi' => ['nullable', 'boolean'],
            'alasan_cekal' => ['nullable', 'string', 'max:500'],
            'foto' => ['nullable', 'image', 'max:2048'],
            'ktp' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ], [
            'nik.digits' => 'NIK harus terdiri dari 16 angka.',
            'nik.unique' => 'NIK ini sudah dipakai peserta lain.',
            'tanggal_lahir.before' => 'Tanggal lahir harus sebelum hari ini.',
        ], [
            'nama' => 'nama',
            'nik' => 'NIK',
            'email' => 'email',
            'jenis_kelamin' => 'jenis kelamin',
            'tempat_lahir' => 'tempat lahir',
            'tanggal_lahir' => 'tanggal lahir',
            'no_hp' => 'nomor HP',
            'nama_wali' => 'nama wali',
            'no_hp_wali' => 'nomor HP wali',
            'foto' => 'foto',
            'ktp' => 'berkas KTP/KK',
            'alasan_cekal' => 'alasan pencekalan',
        ]);

        // Berkas ditangani terpisah — jangan sampai objek file ikut disimpan
        // ke kolom teks.
        unset($data['foto'], $data['ktp']);

        $data['boleh_mendaftar_lagi'] = $request->boolean('boleh_mendaftar_lagi');

        return $data;
    }
}
