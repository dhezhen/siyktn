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

    #[Url]
    public string $sortField = 'didaftarkan_pada';

    #[Url]
    public string $sortDirection = 'desc';

    public int $perPage = 15;

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = $field === 'didaftarkan_pada' ? 'desc' : 'asc';
        }
        $this->resetPage();
    }

    public ?int $selectedPendaftaranId = null;
    public ?Pendaftaran $selectedPendaftaran = null;
    public string $editStatusPendaftaran = 'pending';
    public string $editStatusProgram = 'pending';
    public string $catatanPembayaran = '';

    public function bukaModalPembayaran(int $id): void
    {
        $this->selectedPendaftaranId = $id;
        $this->selectedPendaftaran = Pendaftaran::with(['peserta', 'angkatan', 'program'])->find($id);

        if ($this->selectedPendaftaran) {
            $this->editStatusPendaftaran = $this->selectedPendaftaran->status_pembayaran_pendaftaran ?: 'pending';
            $this->editStatusProgram = $this->selectedPendaftaran->status_pembayaran_program ?: 'pending';
            $this->catatanPembayaran = $this->selectedPendaftaran->catatan_pembayaran ?: '';
        }
    }

    public function tutupModalPembayaran(): void
    {
        $this->selectedPendaftaranId = null;
        $this->selectedPendaftaran = null;
    }

    public function simpanVerifikasiPembayaran(): void
    {
        if (! $this->selectedPendaftaranId) {
            return;
        }

        $pendaftaran = Pendaftaran::find($this->selectedPendaftaranId);
        if ($pendaftaran) {
            $pendaftaran->update([
                'status_pembayaran_pendaftaran' => $this->editStatusPendaftaran,
                'status_pembayaran_program' => $this->editStatusProgram,
                'catatan_pembayaran' => $this->catatanPembayaran,
            ]);

            $this->dispatch('play-sound', sound: 'success');
        }

        $this->tutupModalPembayaran();
    }

    public function quickLunasSemua(int $id): void
    {
        $pendaftaran = Pendaftaran::find($id);
        if ($pendaftaran) {
            $pendaftaran->update([
                'status_pembayaran_pendaftaran' => 'lunas',
                'status_pembayaran_program' => 'lunas',
            ]);

            $this->dispatch('play-sound', sound: 'success');
        }
    }

    public function toggleBayarPendaftaran(Pendaftaran $pendaftaran): void
    {
        $newStatus = $pendaftaran->status_pembayaran_pendaftaran === 'lunas' ? 'pending' : 'lunas';
        $pendaftaran->update(['status_pembayaran_pendaftaran' => $newStatus]);
        $statusLabel = $newStatus === 'lunas' ? 'Sudah Bayar' : 'Belum Bayar';
        $this->dispatch('play-sound', sound: 'success');
        $this->dispatch('swal', icon: 'success', title: 'Status Registrasi Diperbarui', text: "Biaya registrasi {$pendaftaran->peserta?->nama} diubah ke {$statusLabel}.");
    }

    public function toggleBayarProgram(Pendaftaran $pendaftaran): void
    {
        $newStatus = $pendaftaran->status_pembayaran_program === 'lunas' ? 'pending' : 'lunas';
        $pendaftaran->update(['status_pembayaran_program' => $newStatus]);
        $statusLabel = $newStatus === 'lunas' ? 'Sudah Bayar' : 'Belum Bayar';
        $this->dispatch('play-sound', sound: 'success');
        $this->dispatch('swal', icon: 'success', title: 'Status Program Diperbarui', text: "Biaya program {$pendaftaran->peserta?->nama} diubah ke {$statusLabel}.");
    }

    public function ubahStatusBayarProgram(Pendaftaran $pendaftaran, string $status): void
    {
        if (in_array($status, ['pending', 'dp_sebagian', 'lunas'], true)) {
            $pendaftaran->update(['status_pembayaran_program' => $status]);
            $this->dispatch('play-sound', sound: 'success');
        }
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'status', 'angkatan', 'sumber', 'riwayat', 'dari', 'sampai', 'sortField', 'sortDirection'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'angkatan', 'sumber', 'riwayat', 'dari', 'sampai', 'sortField', 'sortDirection']);
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

        $jumlah = [
            'menunggu' => (int) ($hitungan['menunggu'] ?? 0),
            'disetujui' => (int) ($hitungan['disetujui'] ?? 0),
            'ditolak' => (int) ($hitungan['ditolak'] ?? 0),
        ];

        if (auth()->user()?->hasRole('super-admin')) {
            $jumlah['sampah'] = (int) Pendaftaran::onlyTrashed()->count();
        }

        return $jumlah;
    }

    public function pilihStatus(string $status): void
    {
        $this->status = $status;
        $this->resetPage();
    }

    public function hapusPendaftaran(int $id): void
    {
        $pendaftaran = Pendaftaran::find($id);

        if (! $pendaftaran) {
            return;
        }

        if (! auth()->user()?->can('peserta.delete') && $pendaftaran->status_pendaftaran !== 'ditolak') {
            return;
        }

        $pendaftaran->delete();
        $this->dispatch('swal', icon: 'success', title: 'Berhasil Dihapus', text: 'Data pendaftaran dipindahkan ke tong sampah.');
    }

    public function pulihkanPendaftaran(int $id): void
    {
        if (! auth()->user()?->hasRole('super-admin')) {
            return;
        }

        $pendaftaran = Pendaftaran::onlyTrashed()->find($id);
        if ($pendaftaran) {
            $pendaftaran->restore();
            $this->dispatch('swal', icon: 'success', title: 'Dipulihkan', text: 'Data pendaftaran berhasil dipulihkan.');
        }
    }

    public function hapusPermanenPendaftaran(int $id): void
    {
        if (! auth()->user()?->hasRole('super-admin')) {
            return;
        }

        $pendaftaran = Pendaftaran::onlyTrashed()->find($id);
        if ($pendaftaran) {
            $pendaftaran->forceDelete();
            $this->dispatch('swal', icon: 'success', title: 'Dihapus Permanen', text: 'Data pendaftaran dihapus permanen dan tidak dapat dikembalikan.');
        }
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
            ->when($this->status === 'sampah' && auth()->user()?->hasRole('super-admin'), fn ($q) => $q->onlyTrashed())
            ->when($this->status !== '' && $this->status !== 'sampah', fn ($q) => $q->where('status_pendaftaran', $this->status))
            ->when($this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(fn ($sub) => $sub
                    ->where('kode_pendaftaran', 'like', $term)
                    ->orWhere('nomor_induk', 'like', $term)
                    ->orWhereHas('angkatan', fn ($a) => $a->where('nama', 'like', $term))
                    ->orWhereHas('peserta', fn ($p) => $p
                        ->where('nama', 'like', $term)
                        ->orWhere('nik', 'like', $term)
                        ->orWhere('kabupaten_kota', 'like', $term)
                        ->orWhere('provinsi', 'like', $term)
                        ->orWhere('negara', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('no_hp', 'like', $term)
                        ->orWhere('nama_wali', 'like', $term)
                        ->orWhere('no_hp_wali', 'like', $term)
                        ->orWhere('tempat_lahir', 'like', $term)));
            })
            ->when($this->angkatan !== '', fn ($q) => $q->where('angkatan_id', $this->angkatan))
            ->when($this->sumber !== '', fn ($q) => $q->where('sumber_pendaftaran', $this->sumber))
            ->when($this->riwayat === 'ulang', fn ($q) => $q->whereHas('peserta.pendaftaran', null, '>', 1))
            ->when($this->riwayat === 'baru', fn ($q) => $q->whereHas('peserta.pendaftaran', null, '=', 1))
            ->when($this->dari !== '', fn ($q) => $q->whereDate('didaftarkan_pada', '>=', $this->dari))
            ->when(in_array($this->sortField, ['nama', 'jenis_kelamin', 'kabupaten_kota'], true), function ($q) {
                $q->join('peserta', 'pendaftaran.peserta_id', '=', 'peserta.id')
                  ->select('pendaftaran.*')
                  ->orderBy('peserta.'.$this->sortField, $this->sortDirection === 'asc' ? 'asc' : 'desc');
            }, function ($q) {
                $q->orderBy($this->sortField === 'kode_pendaftaran' ? 'kode_pendaftaran' : 'didaftarkan_pada', $this->sortDirection === 'asc' ? 'asc' : 'desc');
            })
            ->paginate($this->perPage);

        return view('livewire.pendaftaran-table', [
            'pendaftaran' => $pendaftaran,
            'daftarAngkatan' => Angkatan::orderByDesc('tahun')->get(['id', 'nama']),
        ]);
    }
}
