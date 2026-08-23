<?php

namespace App\Http\Controllers;

use App\Models\Angkatan;
use App\Services\PendaftaranService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

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
            'nik' => ['required', 'digits:16', 'unique:peserta,nik'],
            'jenis_kelamin' => ['required', Rule::in(['L', 'P'])],
            'tempat_lahir' => ['required', 'string', 'max:80'],
            'tanggal_lahir' => ['required', 'date', 'before:today'],
            'alamat' => ['required', 'string', 'max:500'],
            'no_hp' => ['required', 'string', 'max:25'],
            'email' => ['required', 'email', 'max:150'],
            'nama_wali' => ['required', 'string', 'max:100'],
            'no_hp_wali' => ['required', 'string', 'max:25'],
            'ktp' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
            'persetujuan' => ['accepted'],
        ], [
            'nik.digits' => 'NIK harus terdiri dari 16 angka sesuai yang tertera di KTP.',
            'nik.unique' => 'NIK ini sudah pernah didaftarkan. Hubungi kami bila Anda merasa ini keliru.',
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

        $data['ktp_path'] = $this->pendaftaran->simpanKtp($request->file('ktp'));
        unset($data['ktp'], $data['persetujuan']);

        $peserta = $this->pendaftaran->daftarkan(array_merge($data, [
            'status' => 'aktif',
            'status_pendaftaran' => 'menunggu',
        ]), sumber: 'mandiri');

        return redirect()
            ->route('pendaftaran.sukses')
            ->with('kode_pendaftaran', $peserta->kode_pendaftaran)
            ->with('email_pendaftar', $peserta->email);
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
        ]);
    }
}
