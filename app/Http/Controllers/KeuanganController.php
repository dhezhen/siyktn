<?php

namespace App\Http\Controllers;

use App\Models\Angkatan;
use App\Models\Pendaftaran;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KeuanganController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('keuangan.view');

        // Optimasi: Menghitung total melalui DB Aggregation tanpa load ke memory
        $totalBiayaPendaftaran = Pendaftaran::sum('biaya_pendaftaran');
        $totalBiayaProgram = Pendaftaran::sum('biaya_program');
        $totalKewajiban = $totalBiayaPendaftaran + $totalBiayaProgram;
        
        $pemasukanPendaftaran = Pendaftaran::whereIn('status_pembayaran_pendaftaran', ['lunas', 'bebas_biaya'])->sum('biaya_pendaftaran');
        $pemasukanProgram = Pendaftaran::whereIn('status_pembayaran_program', ['lunas', 'bebas_biaya'])->sum('biaya_program');
        $totalPemasukan = $pemasukanPendaftaran + $pemasukanProgram;
        
        $totalTunggakan = $totalKewajiban - $totalPemasukan;

        $jumlahLunas = Pendaftaran::where('status_pembayaran_pendaftaran', 'lunas')
            ->where('status_pembayaran_program', 'lunas')->count();
            
        $jumlahPending = Pendaftaran::where(function($q) {
            $q->where('status_pembayaran_pendaftaran', 'pending')
              ->orWhere('status_pembayaran_program', 'pending');
        })->count();

        return view('keuangan.index', [
            'totalKewajiban' => $totalKewajiban,
            'totalPemasukan' => $totalPemasukan,
            'totalTunggakan' => $totalTunggakan,
            'jumlahLunas' => $jumlahLunas,
            'jumlahPending' => $jumlahPending,
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
