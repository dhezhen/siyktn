<?php

namespace App\Http\Controllers;

use App\Models\Pendaftaran;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KeuanganController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('keuangan.view');

        $query = Pendaftaran::query()
            ->with(['peserta:id,nama', 'angkatan:id,nama'])
            ->latest('didaftarkan_pada');

        if ($request->filled('status')) {
            $status = $request->input('status');
            $query->where(function ($q) use ($status) {
                $q->where('status_pembayaran_pendaftaran', $status)
                  ->orWhere('status_pembayaran_program', $status);
            });
        }
        
        $pendaftarans = $query->paginate(20)->withQueryString();

        // Calculate totals for recap
        $semuaPendaftaran = Pendaftaran::query()->get(['biaya_pendaftaran', 'biaya_program', 'status_pembayaran_pendaftaran', 'status_pembayaran_program']);
        
        $totalBiayaPendaftaran = $semuaPendaftaran->sum('biaya_pendaftaran');
        $totalBiayaProgram = $semuaPendaftaran->sum('biaya_program');
        $totalKewajiban = $totalBiayaPendaftaran + $totalBiayaProgram;
        
        $pemasukanPendaftaran = $semuaPendaftaran->whereIn('status_pembayaran_pendaftaran', ['lunas', 'bebas_biaya'])->sum('biaya_pendaftaran');
        $pemasukanProgram = $semuaPendaftaran->whereIn('status_pembayaran_program', ['lunas', 'bebas_biaya'])->sum('biaya_program');
        $totalPemasukan = $pemasukanPendaftaran + $pemasukanProgram;
        
        $totalTunggakan = $totalKewajiban - $totalPemasukan;

        return view('keuangan.index', [
            'pendaftarans' => $pendaftarans,
            'totalKewajiban' => $totalKewajiban,
            'totalPemasukan' => $totalPemasukan,
            'totalTunggakan' => $totalTunggakan,
            'jumlahLunas' => $semuaPendaftaran->filter(fn($p) => $p->status_pembayaran_pendaftaran === 'lunas' && $p->status_pembayaran_program === 'lunas')->count(),
            'jumlahPending' => $semuaPendaftaran->filter(fn($p) => $p->status_pembayaran_pendaftaran === 'pending' || $p->status_pembayaran_program === 'pending')->count(),
        ]);
    }

    public function update(Request $request, Pendaftaran $keuangan)
    {
        $this->authorize('keuangan.update');

        $validated = $request->validate([
            'status_pembayaran_pendaftaran' => 'required|string|in:pending,lunas,bebas_biaya',
            'status_pembayaran_program' => 'required|string|in:pending,lunas,bebas_biaya',
            'catatan_pembayaran' => 'nullable|string|max:1000',
        ]);

        $keuangan->update($validated);

        return back()->with('success', 'Status pembayaran berhasil diperbarui.');
    }
}
