<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Angkatan extends Model
{
    use RecordsActivity, SoftDeletes;

    protected $table = 'angkatan';

    protected array $activityFields = ['nama', 'kode', 'tahun', 'kuota', 'kuota_putra', 'kuota_putri', 'status'];

    protected string $activityLabel = 'Angkatan';

    protected $fillable = [
        'nama', 'kode', 'tahun', 'tanggal_mulai', 'tanggal_selesai',
        'kuota', 'kuota_putra', 'kuota_putri', 'status', 'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'tahun' => 'integer',
            'kuota' => 'integer',
            'kuota_putra' => 'integer',
            'kuota_putri' => 'integer',
        ];
    }

    public function pendaftaran(): HasMany
    {
        return $this->hasMany(Pendaftaran::class);
    }

    public function halaqah(): HasMany
    {
        return $this->hasMany(Halaqah::class);
    }

    /**
     * Orang-orang yang terdaftar di angkatan ini, lewat tabel pendaftaran.
     */
    public function peserta(): HasManyThrough
    {
        return $this->hasManyThrough(
            Peserta::class,
            Pendaftaran::class,
            'angkatan_id',   // kolom di pendaftaran
            'id',            // kolom di peserta
            'id',            // kolom di angkatan
            'peserta_id'     // kolom di pendaftaran
        );
    }

    public function scopeBerjalan(Builder $query): Builder
    {
        return $query->where('status', 'berjalan');
    }

    public function getPesertaAktifCountAttribute(): int
    {
        return $this->attributes['peserta_aktif_count']
            ?? $this->pendaftaran()->whereIn('status_pendaftaran', ['menunggu', 'disetujui'])->where('status', 'aktif')->count();
    }

    public function getPesertaPutraAktifCountAttribute(): int
    {
        return $this->attributes['peserta_putra_aktif_count']
            ?? $this->pendaftaran()->whereIn('status_pendaftaran', ['menunggu', 'disetujui'])->where('status', 'aktif')->whereHas('peserta', fn ($q) => $q->where('jenis_kelamin', 'L'))->count();
    }

    public function getPesertaPutriAktifCountAttribute(): int
    {
        return $this->attributes['peserta_putri_aktif_count']
            ?? $this->pendaftaran()->whereIn('status_pendaftaran', ['menunggu', 'disetujui'])->where('status', 'aktif')->whereHas('peserta', fn ($q) => $q->where('jenis_kelamin', 'P'))->count();
    }

    /**
     * Sisa kuota peserta Putra (Laki-laki). Null bila tidak dibatasi.
     */
    public function getSisaKuotaPutraAttribute(): ?int
    {
        if ($this->kuota_putra === 0) {
            return null;
        }

        return max(0, $this->kuota_putra - $this->peserta_putra_aktif_count);
    }

    /**
     * Sisa kuota peserta Putri (Perempuan). Null bila tidak dibatasi.
     */
    public function getSisaKuotaPutriAttribute(): ?int
    {
        if ($this->kuota_putri === 0) {
            return null;
        }

        return max(0, $this->kuota_putri - $this->peserta_putri_aktif_count);
    }

    /**
     * Sisa kuota spesifik jenis kelamin ('L' / 'P'). Null bila tidak dibatasi.
     */
    public function sisaKuotaUntuk(string $jenisKelamin): ?int
    {
        $sisaGender = match ($jenisKelamin) {
            'L' => $this->sisa_kuota_putra,
            'P' => $this->sisa_kuota_putri,
            default => null,
        };

        $sisaTotal = $this->sisa_kuota;

        if ($sisaGender === null) {
            return $sisaTotal;
        }

        if ($sisaTotal === null) {
            return $sisaGender;
        }

        return min($sisaGender, $sisaTotal);
    }

    /**
     * Memeriksa apakah kuota untuk jenis kelamin tertentu sudah penuh.
     */
    public function isKuotaPenuhUntuk(string $jenisKelamin): bool
    {
        $sisa = $this->sisaKuotaUntuk($jenisKelamin);

        return $sisa !== null && $sisa <= 0;
    }

    /**
     * Sisa kuota total yang masih bisa diisi. Null bila kuota tidak dibatasi.
     */
    public function getSisaKuotaAttribute(): ?int
    {
        if ($this->kuota === 0) {
            return null;
        }

        return max(0, $this->kuota - $this->peserta_aktif_count);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'persiapan' => 'Persiapan',
            'berjalan' => 'Berjalan',
            'selesai' => 'Selesai',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'persiapan' => 'amber',
            'berjalan' => 'emerald',
            'selesai' => 'slate',
            default => 'slate',
        };
    }
}
