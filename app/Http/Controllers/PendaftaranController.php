<?php

namespace App\Http\Controllers;

use App\Services\PendaftaranService;
use App\Support\KelayakanPendaftaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

use App\Models\Peserta;

/**
 * Formulir pendaftaran mandiri — dapat diakses tanpa login.
 */
class PendaftaranController extends Controller
{
    public function __construct(protected PendaftaranService $pendaftaran) {}

    public function create(): View
    {
        $angkatan = $this->pendaftaran->angkatanTerbuka();

        $quotaData = $angkatan->mapWithKeys(fn ($item) => [
            $item->id => [
                'id' => $item->id,
                'nama' => $item->nama,
                'sisa_total' => $item->sisa_kuota,
                'sisa_putra' => $item->sisa_kuota_putra,
                'sisa_putri' => $item->sisa_kuota_putri,
                'is_full_putra' => $item->isKuotaPenuhUntuk('L'),
                'is_full_putri' => $item->isKuotaPenuhUntuk('P'),
            ],
        ]);

        $programs = \App\Models\Program::aktif()->orderBy('durasi_hari')->get();

        return view('pendaftaran.create', [
            'angkatan' => $angkatan,
            'quotaData' => $quotaData,
            'programs' => $programs,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $angkatanTerbuka = $this->pendaftaran->angkatanTerbuka();

        $validPaketProgram = array_unique(array_merge(
            \App\Models\Program::pluck('kode')->toArray(),
            array_keys(\App\Models\Pendaftaran::PAKET_PROGRAM)
        ));

        if ($request->input('tipe_pendaftar') === 'alumni') {
            $dataAlumni = $request->validate([
                'angkatan_id' => ['required', Rule::in($angkatanTerbuka->pluck('id'))],
                'paket_program' => ['required', Rule::in($validPaketProgram)],
                'nik_alumni' => ['required', 'digits:16'],
                'tanggal_lahir_alumni' => ['required', 'date'],
                'persetujuan' => ['accepted'],
            ], [
                'nik_alumni.digits' => 'NIK harus terdiri dari 16 angka sesuai yang tertera di KTP/KK.',
                'angkatan_id.in' => 'Angkatan yang Anda pilih sudah tidak menerima pendaftaran.',
                'paket_program.required' => 'Silakan pilih Paket Program Karantina.',
                'persetujuan.accepted' => 'Anda harus menyetujui pernyataan kebenaran data.',
            ], [
                'angkatan_id' => 'angkatan',
                'paket_program' => 'paket program',
                'nik_alumni' => 'NIK',
                'tanggal_lahir_alumni' => 'tanggal lahir',
            ]);

            $peserta = Peserta::cariBerdasarkanNik($dataAlumni['nik_alumni']);

            if (! $peserta) {
                throw ValidationException::withMessages([
                    'nik_alumni' => 'NIK ini belum terdaftar di sistem kami. Jika Anda pendaftar baru, silakan gunakan formulir Pendaftar Baru.',
                ]);
            }

            if ($peserta->tanggal_lahir?->toDateString() !== $dataAlumni['tanggal_lahir_alumni']) {
                throw ValidationException::withMessages([
                    'tanggal_lahir_alumni' => 'Tanggal lahir tidak cocok dengan catatan NIK kami. Periksa kembali NIK dan tanggal lahir Anda.',
                ]);
            }

            $selectedAngkatan = $angkatanTerbuka->firstWhere('id', (int) $dataAlumni['angkatan_id']);
            if ($selectedAngkatan && $selectedAngkatan->isKuotaPenuhUntuk($peserta->jenis_kelamin)) {
                $labelGender = $peserta->jenis_kelamin_label;
                throw ValidationException::withMessages([
                    'angkatan_id' => "Kuota pendaftaran untuk {$labelGender} pada {$selectedAngkatan->nama} sudah penuh.",
                ]);
            }

            $kelayakan = KelayakanPendaftaran::periksa($dataAlumni['nik_alumni'], (int) $dataAlumni['angkatan_id']);

            if (! $kelayakan->boleh) {
                throw ValidationException::withMessages(['nik_alumni' => $kelayakan->alasan]);
            }

            $pendaftaran = $this->pendaftaran->daftarkan(
                dataPeserta: [
                    'nama' => $peserta->nama,
                    'nik' => $peserta->nik,
                    'jenis_kelamin' => $peserta->jenis_kelamin,
                    'tempat_lahir' => $peserta->tempat_lahir,
                    'tanggal_lahir' => $peserta->tanggal_lahir?->toDateString(),
                    'kewarganegaraan' => $peserta->kewarganegaraan,
                    'negara' => $peserta->negara,
                    'provinsi' => $peserta->provinsi,
                    'kabupaten_kota' => $peserta->kabupaten_kota,
                    'alamat' => $peserta->alamat,
                    'no_hp' => $peserta->no_hp,
                    'email' => $peserta->email,
                    'nama_wali' => $peserta->nama_wali,
                    'no_hp_wali' => $peserta->no_hp_wali,
                ],
                dataPendaftaran: [
                    'angkatan_id' => $dataAlumni['angkatan_id'],
                    'paket_program' => $dataAlumni['paket_program'],
                    'status_pendaftaran' => 'menunggu',
                    'status' => 'aktif',
                ],
                sumber: 'mandiri',
            );

            return redirect()
                ->route('pendaftaran.sukses')
                ->with('kode_pendaftaran', $pendaftaran->kode_pendaftaran)
                ->with('email_pendaftar', $peserta->email)
                ->with('pendaftaran_ulang', true);
        }

        $data = $request->validate([
            'angkatan_id' => ['required', Rule::in($angkatanTerbuka->pluck('id'))],
            'paket_program' => ['required', Rule::in($validPaketProgram)],
            'nama' => ['required', 'string', 'max:100'],
            'nik' => ['required', 'digits:16'],
            'jenis_kelamin' => ['required', Rule::in(['L', 'P'])],
            'tempat_lahir' => ['required', 'string', 'max:80'],
            'tanggal_lahir' => ['required', 'date', 'before:today'],
            'kewarganegaraan' => ['required', Rule::in(['WNI', 'WNA'])],
            'negara' => ['required_if:kewarganegaraan,WNA', 'nullable', 'string', 'max:100'],
            'provinsi' => ['required_if:kewarganegaraan,WNI', 'nullable', 'string', 'max:100'],
            'kabupaten_kota' => ['required', 'string', 'max:100'],
            'alamat' => ['required', 'string', 'max:500'],
            'no_hp' => ['required', 'string', 'max:25'],
            'email' => ['required', 'email', 'max:150'],
            'nama_wali' => ['required', 'string', 'max:100'],
            'no_hp_wali' => ['required', 'string', 'max:25'],
            'ktp' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
            'persetujuan' => ['accepted'],
        ], [
            'nik.digits' => 'NIK harus terdiri dari 16 angka sesuai yang tertera di KTP/KK.',
            'angkatan_id.in' => 'Angkatan yang Anda pilih sudah tidak menerima pendaftaran.',
            'ktp.mimes' => 'Berkas KTP/KK harus berupa gambar (JPG/PNG) atau PDF.',
            'ktp.max' => 'Ukuran berkas KTP/KK maksimal 2 MB.',
            'tanggal_lahir.before' => 'Tanggal lahir harus sebelum hari ini.',
            'provinsi.required_if' => 'Provinsi wajib dipilih untuk pendaftar WNI.',
            'negara.required_if' => 'Nama Negara asal wajib diisi untuk pendaftar WNA / Luar Negeri.',
            'kabupaten_kota.required' => 'Kabupaten / Kota wajib diisi.',
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
            'ktp' => 'berkas KTP/KK',
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
                'ktp' => 'Berkas KTP/KK wajib dilampirkan.',
            ]);
        }

        $dataPeserta = collect($data)
            ->except(['angkatan_id', 'paket_program', 'ktp', 'persetujuan'])
            ->put('ktp_path', $request->hasFile('ktp')
                ? $this->pendaftaran->simpanKtp($request->file('ktp'))
                : null)
            ->all();

        $pendaftaran = $this->pendaftaran->daftarkan(
            dataPeserta: $dataPeserta,
            dataPendaftaran: [
                'angkatan_id' => $data['angkatan_id'],
                'paket_program' => $data['paket_program'],
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
