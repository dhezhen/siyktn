<?php

namespace App\Livewire;

use App\Models\Angkatan;
use App\Models\Peserta;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

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
    public string $sumber = '';

    public int $perPage = 20;

    public function updated($property): void
    {
        if (in_array($property, ['search', 'angkatan', 'status', 'jenisKelamin', 'statusPendaftaran', 'sumber'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'angkatan', 'status', 'jenisKelamin', 'statusPendaftaran', 'sumber']);
        $this->resetPage();
    }

    public function render(): View
    {
        $peserta = Peserta::query()
            ->with('angkatan:id,nama,kode')
            ->when($this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(fn ($sub) => $sub
                    ->where('nama', 'like', $term)
                    ->orWhere('nomor_induk', 'like', $term)
                    ->orWhere('no_hp', 'like', $term)
                    ->orWhere('nama_wali', 'like', $term)
                    ->orWhere('kode_pendaftaran', 'like', $term)
                    ->orWhere('nik', 'like', $term)
                    ->orWhere('email', 'like', $term));
            })
            ->when($this->angkatan !== '', fn ($q) => $q->where('angkatan_id', $this->angkatan))
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->when($this->jenisKelamin !== '', fn ($q) => $q->where('jenis_kelamin', $this->jenisKelamin))
            ->when($this->statusPendaftaran !== '', fn ($q) => $q->where('status_pendaftaran', $this->statusPendaftaran))
            ->when($this->sumber !== '', fn ($q) => $q->where('sumber_pendaftaran', $this->sumber))
            ->orderBy('nomor_induk')
            ->paginate($this->perPage);

        return view('livewire.peserta-table', [
            'peserta' => $peserta,
            'daftarAngkatan' => Angkatan::orderByDesc('tahun')->get(['id', 'nama']),
        ]);
    }
}
