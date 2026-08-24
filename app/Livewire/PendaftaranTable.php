<?php

namespace App\Livewire;

use App\Models\Angkatan;
use App\Models\Pendaftaran;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Antrean peninjauan pendaftaran.
 *
 * Defaultnya hanya menampilkan yang belum ditinjau, karena itulah pekerjaan
 * yang menunggu petugas.
 */
class PendaftaranTable extends Component
{
    use WithPagination;

    #[Url(as: 'q', keep: false)]
    public string $search = '';

    #[Url]
    public string $status = 'menunggu';

    #[Url]
    public string $angkatan = '';

    #[Url]
    public string $sumber = '';

    #[Url]
    public string $riwayat = '';

    #[Url]
    public string $dari = '';

    #[Url]
    public string $sampai = '';

    public int $perPage = 15;

    public function updated($property): void
    {
        if (in_array($property, ['search', 'status', 'angkatan', 'sumber', 'riwayat', 'dari', 'sampai'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'angkatan', 'sumber', 'riwayat', 'dari', 'sampai']);
        $this->status = 'menunggu';
        $this->resetPage();
    }

    /**
     * Jumlah per status, dipakai untuk tab di atas daftar.
     *
     * @return array<string, int>
     */
    public function getJumlahProperty(): array
    {
        $hitungan = Pendaftaran::query()
            ->selectRaw('status_pendaftaran, count(*) as total')
            ->groupBy('status_pendaftaran')
            ->pluck('total', 'status_pendaftaran');

        return [
            'menunggu' => (int) ($hitungan['menunggu'] ?? 0),
            'disetujui' => (int) ($hitungan['disetujui'] ?? 0),
            'ditolak' => (int) ($hitungan['ditolak'] ?? 0),
        ];
    }

    public function pilihStatus(string $status): void
    {
        $this->status = $status;
        $this->resetPage();
    }

    public function render(): View
    {
        $pendaftaran = Pendaftaran::query()
            // Jumlah pendaftaran orangnya ikut dimuat, supaya view bisa menandai
            // pendaftaran ulang tanpa query tambahan per baris.
            ->with([
                'peserta' => fn ($q) => $q->withCount('pendaftaran'),
                'angkatan:id,nama,kode',
                'peninjau:id,name',
            ])
            ->when($this->status !== '', fn ($q) => $q->where('status_pendaftaran', $this->status))
            ->when($this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(fn ($sub) => $sub
                    ->where('kode_pendaftaran', 'like', $term)
                    ->orWhere('nomor_induk', 'like', $term)
                    ->orWhereHas('peserta', fn ($p) => $p
                        ->where('nama', 'like', $term)
                        ->orWhere('nik', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('no_hp', 'like', $term)));
            })
            ->when($this->angkatan !== '', fn ($q) => $q->where('angkatan_id', $this->angkatan))
            ->when($this->sumber !== '', fn ($q) => $q->where('sumber_pendaftaran', $this->sumber))
            ->when($this->riwayat === 'ulang', fn ($q) => $q->whereHas('peserta.pendaftaran', null, '>', 1))
            ->when($this->riwayat === 'baru', fn ($q) => $q->whereHas('peserta.pendaftaran', null, '=', 1))
            ->when($this->dari !== '', fn ($q) => $q->whereDate('didaftarkan_pada', '>=', $this->dari))
            ->when($this->sampai !== '', fn ($q) => $q->whereDate('didaftarkan_pada', '<=', $this->sampai))
            ->orderByDesc('didaftarkan_pada')
            ->orderByDesc('id')
            ->paginate($this->perPage);

        return view('livewire.pendaftaran-table', [
            'pendaftaran' => $pendaftaran,
            'daftarAngkatan' => Angkatan::orderByDesc('tahun')->get(['id', 'nama']),
        ]);
    }
}
