<?php

namespace App\Livewire;

use App\Models\Angkatan;
use App\Models\Pendaftaran;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;

class KeuanganTable extends Component
{
    use WithPagination;

    #[Url]
    public $search = '';

    #[Url]
    public $angkatan = '';

    #[Url]
    public $status = '';

    // Properti Form Edit
    public $editId = null;
    public $editStatusPendaftaran = '';
    public $editStatusProgram = '';
    public $editCatatan = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingAngkatan()
    {
        $this->resetPage();
    }

    public function updatingStatus()
    {
        $this->resetPage();
    }

    public function editStatus($id)
    {
        $pendaftaran = Pendaftaran::findOrFail($id);
        $this->editId = $pendaftaran->id;
        $this->editStatusPendaftaran = $pendaftaran->status_pembayaran_pendaftaran;
        $this->editStatusProgram = $pendaftaran->status_pembayaran_program;
        $this->editCatatan = $pendaftaran->catatan_pembayaran;
        
        $this->dispatch('open-modal', 'edit-modal');
    }

    public function simpanPerubahan()
    {
        if (!$this->editId) return;

        $pendaftaran = Pendaftaran::findOrFail($this->editId);
        
        $pendaftaran->update([
            'status_pembayaran_pendaftaran' => $this->editStatusPendaftaran,
            'status_pembayaran_program' => $this->editStatusProgram,
            'catatan_pembayaran' => $this->editCatatan,
        ]);

        $this->dispatch('close-modal', 'edit-modal');
        session()->flash('success', 'Status pembayaran berhasil diperbarui.');
        
        // Meminta browser memuat ulang halaman secara halus agar metrik/kartu ringkasan juga ikut terbarui
        $this->redirect(route('keuangan.index'), navigate: true);
    }

    public function render()
    {
        $query = Pendaftaran::query()
            ->with(['peserta:id,nama', 'angkatan:id,nama'])
            ->latest('didaftarkan_pada');

        if ($this->search) {
            $query->whereHas('peserta', function (Builder $q) {
                $q->where('nama', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->angkatan) {
            $query->where('angkatan_id', $this->angkatan);
        }

        if ($this->status) {
            $query->where(function (Builder $q) {
                $q->where('status_pembayaran_pendaftaran', $this->status)
                  ->orWhere('status_pembayaran_program', $this->status);
            });
        }

        return view('livewire.keuangan-table', [
            'pendaftarans' => $query->paginate(20),
            'angkatans' => Angkatan::latest()->get(['id', 'nama']),
        ]);
    }
}
