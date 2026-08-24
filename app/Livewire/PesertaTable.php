<?php

namespace App\Livewire;

use App\Models\Angkatan;
use App\Models\Peserta;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Daftar peserta sebagai ORANG — satu baris per orang, bukan per pendaftaran.
 */
class PesertaTable extends Component
{
    use WithPagination;

    #[Url(as: 'q', keep: false)]
    public string $search = '';

    #[Url]
    public string $angkatan = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $jenisKelamin = '';

    #[Url]
    public string $statusPendaftaran = '';

    #[Url]
    public string $riwayat = '';

    public int $perPage = 20;

    public function updated($property): void
    {
        if (in_array($property, ['search', 'angkatan', 'status', 'jenisKelamin', 'statusPendaftaran', 'riwayat'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'angkatan', 'status', 'jenisKelamin', 'statusPendaftaran', 'riwayat']);
        $this->resetPage();
    }

    public function render(): View
    {
        $peserta = Peserta::query()
            ->withCount('pendaftaran')
            ->with(['pendaftaranTerakhir.angkatan:id,nama,kode'])
            ->when($this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(fn ($sub) => $sub
                    ->where('nama', 'like', $term)
                    ->orWhere('nik', 'like', $term)
                    ->orWhere('no_hp', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('nama_wali', 'like', $term)
                    ->orWhereHas('pendaftaran', fn ($p) => $p
                        ->where('nomor_induk', 'like', $term)
                        ->orWhere('kode_pendaftaran', 'like', $term)));
            })
            ->when($this->angkatan !== '', fn ($q) => $q
                ->whereHas('pendaftaran', fn ($p) => $p->where('angkatan_id', $this->angkatan)))
            ->when($this->status !== '', fn ($q) => $q
                ->whereHas('pendaftaran', fn ($p) => $p->where('status', $this->status)))
            ->when($this->statusPendaftaran !== '', fn ($q) => $q
                ->whereHas('pendaftaran', fn ($p) => $p->where('status_pendaftaran', $this->statusPendaftaran)))
            ->when($this->jenisKelamin !== '', fn ($q) => $q->where('jenis_kelamin', $this->jenisKelamin))
            ->when($this->riwayat === 'ulang', fn ($q) => $q->has('pendaftaran', '>', 1))
            ->when($this->riwayat === 'alumni', fn ($q) => $q->alumni())
            ->when($this->riwayat === 'cekal', fn ($q) => $q->where('boleh_mendaftar_lagi', false))
            ->orderBy('nama')
            ->paginate($this->perPage);

        return view('livewire.peserta-table', [
            'peserta' => $peserta,
            'daftarAngkatan' => Angkatan::orderByDesc('tahun')->get(['id', 'nama']),
        ]);
    }
}
