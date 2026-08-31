<?php

namespace App\Http\Controllers;

use App\Models\Pendaftaran;
use App\Models\Peserta;
use App\Services\PendaftaranService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Peninjauan pendaftaran oleh petugas.
 */
class PendaftaranAdminController extends Controller implements HasMiddleware
{
    public function __construct(protected PendaftaranService $pendaftaran) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:peserta.approve', only: ['index', 'setujui', 'tolak', 'presensi', 'konfirmasiKehadiran']),
            new Middleware('permission:peserta.view', only: ['ktp']),
        ];
    }

    public function index(): View
    {
        return view('pendaftaran.index');
    }

    public function presensi(Request $request): View
    {
        return view('pendaftaran.presensi');
    }

    public function konfirmasiKehadiran(Request $request, ?Pendaftaran $pendaftaran = null): \Illuminate\Http\JsonResponse|RedirectResponse
    {
        $kode = $request->input('kode_pendaftaran') ?: $request->input('kode');

        if (! $pendaftaran && $kode) {
            $pendaftaran = Pendaftaran::where('kode_pendaftaran', $kode)
                ->orWhere('nomor_induk', $kode)
                ->first();
        }

        if (! $pendaftaran) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data pendaftaran dengan kode "'.$kode.'" tidak ditemukan.',
                ], 404);
            }

            return back()->with('error', 'Data pendaftaran tidak ditemukan.');
        }

        $peserta = $pendaftaran->peserta;
        $sudahHadir = $pendaftaran->status_kehadiran === 'hadir';

        if (! $sudahHadir) {
            $pendaftaran->konfirmasiKehadiran(Auth::id());
        }

        $pesan = $sudahHadir
            ? "Peserta {$peserta->nama} ({$pendaftaran->kode_pendaftaran}) sudah terkonfirmasi HADIR sebelumnya."
            : "✓ Presensi Berhasil! {$peserta->nama} ({$pendaftaran->kode_pendaftaran}) berhasil dikonfirmasi HADIR di lokasi.";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'already_present' => $sudahHadir,
                'message' => $pesan,
                'data' => [
                    'id' => $pendaftaran->id,
                    'kode_pendaftaran' => $pendaftaran->kode_pendaftaran,
                    'nomor_induk' => $pendaftaran->nomor_induk,
                    'nama' => $peserta->nama,
                    'jenis_kelamin' => $peserta->jenis_kelamin_label,
                    'angkatan' => $pendaftaran->angkatan?->nama,
                    'foto_url' => $peserta->foto_url,
                    'waktu_kehadiran' => $pendaftaran->fresh()->waktu_kehadiran?->translatedFormat('d M Y, H:i:s'),
                ],
            ]);
        }

        return back()->with($sudahHadir ? 'info' : 'success', $pesan);
    }

    public function setujui(Pendaftaran $pendaftaran): RedirectResponse
    {
        if (! $pendaftaran->isMenunggu()) {
            return back()->with('warning', 'Pendaftaran ini sudah pernah ditinjau.');
        }

        $this->pendaftaran->setujui($pendaftaran, Auth::user());
        $nama = $pendaftaran->peserta->nama;

        return back()->with('success',
            "Pendaftaran {$nama} disetujui. Nomor induk {$pendaftaran->fresh()->nomor_induk} sudah dikirim lewat email.");
    }

    public function tolak(Request $request, Pendaftaran $pendaftaran): RedirectResponse
    {
        if (! $pendaftaran->isMenunggu()) {
            return back()->with('warning', 'Pendaftaran ini sudah pernah ditinjau.');
        }

        $data = $request->validate(
            ['alasan_penolakan' => ['required', 'string', 'min:10', 'max:500']],
            [
                'alasan_penolakan.required' => 'Tuliskan alasan penolakan — alasan ini dikirim ke pendaftar.',
                'alasan_penolakan.min' => 'Alasan penolakan terlalu singkat, jelaskan minimal 10 karakter.',
            ],
            ['alasan_penolakan' => 'alasan penolakan']
        );

        $this->pendaftaran->tolak($pendaftaran, Auth::user(), $data['alasan_penolakan']);

        return back()->with('success',
            "Pendaftaran {$pendaftaran->peserta->nama} ditolak dan pemberitahuan sudah dikirim.");
    }

    /**
     * Menyajikan berkas KTP dari disk privat.
     *
     * Berkas ini tidak pernah berada di folder publik — satu-satunya jalan
     * membukanya adalah lewat route ini, yang dijaga permission peserta.view.
     */
    public function ktp(Peserta $peserta): StreamedResponse
    {
        abort_if(! $peserta->ktp_path || ! Storage::disk('local')->exists($peserta->ktp_path), 404,
            'Berkas KTP tidak ditemukan.');

        return Storage::disk('local')->response(
            $peserta->ktp_path,
            'ktp-'.$peserta->id.'.'.pathinfo($peserta->ktp_path, PATHINFO_EXTENSION),
            ['Content-Disposition' => 'inline']
        );
    }
}
