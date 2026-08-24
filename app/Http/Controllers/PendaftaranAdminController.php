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
            new Middleware('permission:peserta.approve', only: ['index', 'setujui', 'tolak']),
            new Middleware('permission:peserta.view', only: ['ktp']),
        ];
    }

    public function index(): View
    {
        return view('pendaftaran.index');
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
