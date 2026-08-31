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

    #[Url]
    public string $sortField = 'nama';

    #[Url]
    public string $sortDirection = 'asc';

    public int $perPage = 20;

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'angkatan', 'status', 'jenisKelamin', 'statusPendaftaran', 'riwayat', 'sortField', 'sortDirection'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'angkatan', 'status', 'jenisKelamin', 'statusPendaftaran', 'riwayat', 'sortField', 'sortDirection']);
        $this->resetPage();
    }

    public function updateStatusPendaftaran(int $pendaftaranId, string $status): void
    {
        if (! auth()->user()->can('peserta.update')) {
            abort(403);
        }

        if (! in_array($status, ['aktif', 'lulus', 'keluar'], true)) {
            return;
        }

        $pendaftaran = \App\Models\Pendaftaran::find($pendaftaranId);
        if ($pendaftaran) {
            $pendaftaran->update(['status' => $status]);
        }
    }

    public function render(): View
    {
        $sortableFields = ['nama', 'jenis_kelamin', 'kabupaten_kota', 'provinsi', 'created_at'];
        $field = in_array($this->sortField, $sortableFields, true) ? $this->sortField : 'nama';
        $direction = $this->sortDirection === 'desc' ? 'desc' : 'asc';

        $peserta = Peserta::query()
            ->whereHas('pendaftaran', fn ($q) => $q->where('status_pendaftaran', 'disetujui'))
            ->withCount('pendaftaran')
            ->with(['pendaftaran' => function ($q) {
                if ($this->angkatan !== '') {
                    $q->where('angkatan_id', $this->angkatan);
                }
                if ($this->status !== '') {
                    $q->where('status', $this->status);
                }
                if ($this->statusPendaftaran !== '') {
                    $q->where('status_pendaftaran', $this->statusPendaftaran);
                }
                $q->latest('didaftarkan_pada');
            }, 'pendaftaran.angkatan:id,nama,kode'])
            ->when($this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(fn ($sub) => $sub
                    ->where('nama', 'like', $term)
                    ->orWhere('nik', 'like', $term)
                    ->orWhere('kabupaten_kota', 'like', $term)
                    ->orWhere('provinsi', 'like', $term)
                    ->orWhere('negara', 'like', $term)
                    ->orWhere('no_hp', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('nama_wali', 'like', $term)
                    ->orWhere('no_hp_wali', 'like', $term)
                    ->orWhere('tempat_lahir', 'like', $term)
                    ->orWhereHas('pendaftaran', fn ($p) => $p
                        ->where('nomor_induk', 'like', $term)
                        ->orWhere('kode_pendaftaran', 'like', $term)
                        ->orWhereHas('angkatan', fn ($a) => $a->where('nama', 'like', $term))));
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
            ->orderBy($field, $direction)
            ->paginate($this->perPage);

        return view('livewire.peserta-table', [
            'peserta' => $peserta,
            'daftarAngkatan' => Angkatan::orderByDesc('tahun')->get(['id', 'nama']),
        ]);
    }
}
