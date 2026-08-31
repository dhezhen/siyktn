<?php

namespace App\Http\Controllers;

use App\Services\PendaftaranService;
use App\Support\KelayakanPendaftaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Formulir pendaftaran mandiri — dapat diakses tanpa login.
 */
class PendaftaranController extends Controller
{
    public function __construct(protected PendaftaranService $pendaftaran) {}

    public function create(): View
    {
        return view('pendaftaran.create', [
            'angkatan' => $this->pendaftaran->angkatanTerbuka(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $angkatanTerbuka = $this->pendaftaran->angkatanTerbuka();

        $data = $request->validate([
            'angkatan_id' => ['required', Rule::in($angkatanTerbuka->pluck('id'))],
            'nama' => ['required', 'string', 'max:100'],
            'nik' => ['required', 'digits:16'],
            'jenis_kelamin' => ['required', Rule::in(['L', 'P'])],
            'tempat_lahir' => ['required', 'string', 'max:80'],
            'tanggal_lahir' => ['required', 'date', 'before:today'],
            'alamat' => ['required', 'string', 'max:500'],
            'no_hp' => ['required', 'string', 'max:25'],
            'email' => ['required', 'email', 'max:150'],
            'nama_wali' => ['required', 'string', 'max:100'],
            'no_hp_wali' => ['required', 'string', 'max:25'],
            'ktp' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
            'persetujuan' => ['accepted'],
        ], [
            'nik.digits' => 'NIK harus terdiri dari 16 angka sesuai yang tertera di KTP.',
            'angkatan_id.in' => 'Angkatan yang Anda pilih sudah tidak menerima pendaftaran.',
            'ktp.mimes' => 'Berkas KTP harus berupa gambar (JPG/PNG) atau PDF.',
            'ktp.max' => 'Ukuran berkas KTP maksimal 2 MB.',
            'tanggal_lahir.before' => 'Tanggal lahir harus sebelum hari ini.',
            'persetujuan.accepted' => 'Anda harus menyetujui pernyataan kebenaran data.',
        ], [
            'angkatan_id' => 'angkatan',
            'nama' => 'nama lengkap',
            'nik' => 'NIK',
            'jenis_kelamin' => 'jenis kelamin',
            'tempat_lahir' => 'tempat lahir',
            'tanggal_lahir' => 'tanggal lahir',
            'alamat' => 'alamat',
            'no_hp' => 'nomor HP',
            'email' => 'email',
            'nama_wali' => 'nama wali',
            'no_hp_wali' => 'nomor HP wali',
            'ktp' => 'berkas KTP',
        ]);

        $selectedAngkatan = $angkatanTerbuka->firstWhere('id', (int) $data['angkatan_id']);
        if ($selectedAngkatan && $selectedAngkatan->isKuotaPenuhUntuk($data['jenis_kelamin'])) {
            $labelGender = $data['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan';
            throw ValidationException::withMessages([
                'angkatan_id' => "Kuota pendaftaran untuk {$labelGender} pada {$selectedAngkatan->nama} sudah penuh.",
            ]);
        }

        $kelayakan = KelayakanPendaftaran::periksa($data['nik'], (int) $data['angkatan_id']);

        if (! $kelayakan->boleh) {
            throw ValidationException::withMessages(['nik' => $kelayakan->alasan]);
        }

        $lama = $kelayakan->peserta;

        // Pengaman privasi: NIK saja tidak cukup untuk mengaku sebagai orang
        // yang sudah terdaftar. Tanggal lahirnya harus cocok, supaya formulir
        // publik ini tidak bisa dipakai memancing data orang lain.
        if ($lama && $lama->tanggal_lahir?->toDateString() !== $data['tanggal_lahir']) {
            throw ValidationException::withMessages([
                'tanggal_lahir' => 'Data yang Anda masukkan tidak cocok dengan catatan kami. '
                    .'Periksa kembali NIK dan tanggal lahir Anda, atau hubungi pihak lembaga.',
            ]);
        }

        // Pendaftar baru wajib melampirkan KTP. Pendaftar lama yang KTP-nya
        // sudah tersimpan boleh melewatinya.
        if (! $request->hasFile('ktp') && ! $lama?->ktp_path) {
            throw ValidationException::withMessages([
                'ktp' => 'Berkas KTP wajib dilampirkan.',
            ]);
        }

        $dataPeserta = collect($data)
            ->except(['angkatan_id', 'ktp', 'persetujuan'])
            ->put('ktp_path', $request->hasFile('ktp')
                ? $this->pendaftaran->simpanKtp($request->file('ktp'))
                : null)
            ->all();

        $pendaftaran = $this->pendaftaran->daftarkan(
            dataPeserta: $dataPeserta,
            dataPendaftaran: [
                'angkatan_id' => $data['angkatan_id'],
                'status_pendaftaran' => 'menunggu',
                'status' => 'aktif',
            ],
            sumber: 'mandiri',
        );

        return redirect()
            ->route('pendaftaran.sukses')
            ->with('kode_pendaftaran', $pendaftaran->kode_pendaftaran)
            ->with('email_pendaftar', $data['email'])
            ->with('pendaftaran_ulang', $kelayakan->pendaftaranUlang);
    }

    public function sukses(Request $request): View|RedirectResponse
    {
        // Halaman ini hanya bermakna tepat setelah formulir dikirim.
        if (! $request->session()->has('kode_pendaftaran')) {
            return redirect()->route('pendaftaran.create');
        }

        return view('pendaftaran.sukses', [
            'kode' => $request->session()->get('kode_pendaftaran'),
            'email' => $request->session()->get('email_pendaftar'),
            'ulang' => (bool) $request->session()->get('pendaftaran_ulang'),
        ]);
    }
}
