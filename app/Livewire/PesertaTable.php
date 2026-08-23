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

    public int $perPage = 20;

    public function updated($property): void
    {
        if (in_array($property, ['search', 'angkatan', 'status', 'jenisKelamin'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'angkatan', 'status', 'jenisKelamin']);
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
                    ->orWhere('nama_wali', 'like', $term));
            })
            ->when($this->angkatan !== '', fn ($q) => $q->where('angkatan_id', $this->angkatan))
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->when($this->jenisKelamin !== '', fn ($q) => $q->where('jenis_kelamin', $this->jenisKelamin))
            ->orderBy('nomor_induk')
            ->paginate($this->perPage);

        return view('livewire.peserta-table', [
            'peserta' => $peserta,
            'daftarAngkatan' => Angkatan::orderByDesc('tahun')->get(['id', 'nama']),
        ]);
    }
}
