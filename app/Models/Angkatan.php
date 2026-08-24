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

    protected array $activityFields = ['nama', 'kode', 'tahun', 'kuota', 'status'];

    protected string $activityLabel = 'Angkatan';

    protected $fillable = [
        'nama', 'kode', 'tahun', 'tanggal_mulai', 'tanggal_selesai',
        'kuota', 'status', 'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'tahun' => 'integer',
            'kuota' => 'integer',
        ];
    }

    public function pendaftaran(): HasMany
    {
        return $this->hasMany(Pendaftaran::class);
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

    /**
     * Sisa kuota yang masih bisa diisi. Null bila kuota tidak dibatasi.
     */
    public function getSisaKuotaAttribute(): ?int
    {
        if ($this->kuota === 0) {
            return null;
        }

        return max(0, $this->kuota - $this->pendaftaran()->aktif()->count());
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
