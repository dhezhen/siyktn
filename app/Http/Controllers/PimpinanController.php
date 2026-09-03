<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use App\Models\Pendaftaran;
use App\Models\Peserta;
use App\Models\Halaqah;
use App\Models\Muhaffizh;
use App\Models\Setoran;

class PimpinanController extends Controller
{
    public function index(): View
    {
        $this->authorize('pimpinan.view');
        return view('pimpinan.dashboard', $this->getDashboardData());
    }

    public function getDashboardData(): array
    {
        // ==========================
        // 1. Pilar Keuangan
        // ==========================
        $keuangan = DB::table('pendaftaran')
            ->selectRaw("
                SUM(biaya_pendaftaran) as total_biaya_pendaftaran,
                SUM(biaya_program) as total_biaya_program,
                SUM(CASE WHEN status_pembayaran_pendaftaran IN ('lunas', 'bebas_biaya') THEN biaya_pendaftaran ELSE 0 END) as pemasukan_pendaftaran,
                SUM(CASE WHEN status_pembayaran_program IN ('lunas', 'bebas_biaya') THEN biaya_program ELSE 0 END) as pemasukan_program
            ")
            ->first();

        $totalKewajiban = $keuangan->total_biaya_pendaftaran + $keuangan->total_biaya_program;
        $totalPemasukan = $keuangan->pemasukan_pendaftaran + $keuangan->pemasukan_program;
        $totalTunggakan = $totalKewajiban - $totalPemasukan;
        $persentaseKeuangan = $totalKewajiban > 0 ? round(($totalPemasukan / $totalKewajiban) * 100, 1) : 0;

        // ==========================
        // 2. Pilar Kepesertaan
        // ==========================
        $totalPeserta = Peserta::count();
        $genderPeserta = Peserta::select('jenis_kelamin', DB::raw('count(*) as total'))
            ->groupBy('jenis_kelamin')
            ->get();
            
        $ikhwan = $genderPeserta->where('jenis_kelamin', 'L')->first()->total ?? 0;
        $akhwat = $genderPeserta->where('jenis_kelamin', 'P')->first()->total ?? 0;

        $pendaftarTerbaru = Pendaftaran::with(['peserta:id,nama,jenis_kelamin', 'angkatan:id,nama'])
            ->latest('didaftarkan_pada')
            ->take(20)
            ->get()
            ->unique('peserta_id')
            ->take(5);

        // ==========================
        // 3. Pilar Operasional Akademik
        // ==========================
        $totalHalaqah = Halaqah::count();
        $totalMuhaffizh = Muhaffizh::count();
        $rataSantriPerGuru = $totalMuhaffizh > 0 ? round($totalPeserta / $totalMuhaffizh, 1) : 0;

        // ==========================
        // 4. Progress Harian Peserta
        // ==========================
        // Mengambil setoran hafalan terbaru hari ini atau beberapa hari terakhir
        $setoranTerbaru = Setoran::with(['anggotaHalaqah.pendaftaran.peserta:id,nama', 'anggotaHalaqah.halaqah.muhaffizh:id,nama'])
            ->latest('tanggal')
            ->latest('created_at')
            ->take(30)
            ->get()
            ->unique('anggota_halaqah_id')
            ->take(10);

        // Chart Pendaftaran 6 Bulan Terakhir
        $chartPendaftaran = Pendaftaran::select(
                DB::raw("DATE_FORMAT(didaftarkan_pada, '%Y-%m') as bulan"),
                DB::raw("COUNT(*) as total")
            )
            ->groupBy('bulan')
            ->orderBy('bulan', 'desc')
            ->take(6)
            ->get()
            ->reverse()
            ->values();

        // ==========================
        // 5. Demografi Lanjutan (Wilayah & Usia)
        // ==========================
        $chartWilayah = Peserta::select('tempat_lahir', DB::raw('count(*) as total'))
            ->whereNotNull('tempat_lahir')
            ->groupBy('tempat_lahir')
            ->orderByDesc('total')
            ->take(7)
            ->get();

        // Mengelompokkan usia
        $dataUsia = DB::table('peserta')
            ->selectRaw('
                SUM(CASE WHEN TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) < 17 THEN 1 ELSE 0 END) as usia_remaja,
                SUM(CASE WHEN TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) BETWEEN 17 AND 25 THEN 1 ELSE 0 END) as usia_pemuda,
                SUM(CASE WHEN TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) BETWEEN 26 AND 35 THEN 1 ELSE 0 END) as usia_dewasa,
                SUM(CASE WHEN TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) > 35 THEN 1 ELSE 0 END) as usia_tua
            ')
            ->first();

        // Beban Muhaffizh (Banyaknya santri aktif per pengajar)
        $bebanMuhaffizh = DB::table('anggota_halaqah')
            ->join('halaqah', 'anggota_halaqah.halaqah_id', '=', 'halaqah.id')
            ->join('muhaffizh', 'halaqah.muhaffizh_id', '=', 'muhaffizh.id')
            ->select('muhaffizh.nama', DB::raw('count(anggota_halaqah.id) as total_santri'))
            ->where('anggota_halaqah.is_aktif', true)
            ->groupBy('muhaffizh.nama')
            ->orderByDesc('total_santri')
            ->take(10)
            ->get();

        return [
            'totalKewajiban' => $totalKewajiban,
            'totalPemasukan' => $totalPemasukan,
            'totalTunggakan' => $totalTunggakan,
            'persentaseKeuangan' => $persentaseKeuangan,
            'totalPeserta' => $totalPeserta,
            'ikhwan' => $ikhwan,
            'akhwat' => $akhwat,
            'pendaftarTerbaru' => $pendaftarTerbaru,
            'totalHalaqah' => $totalHalaqah,
            'totalMuhaffizh' => $totalMuhaffizh,
            'rataSantriPerGuru' => $rataSantriPerGuru,
            'setoranTerbaru' => $setoranTerbaru,
            'chartPendaftaran' => $chartPendaftaran,
            'chartWilayah' => $chartWilayah,
            'dataUsia' => $dataUsia,
            'bebanMuhaffizh' => $bebanMuhaffizh,
        ];
    }
}
