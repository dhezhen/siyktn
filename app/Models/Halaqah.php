<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Kelompok halaqah dalam satu angkatan, diampu seorang muhaffizh.
 */
class Halaqah extends Model
{
    use RecordsActivity, SoftDeletes;

    protected $table = 'halaqah';

    protected array $activityFields = ['nama', 'kode', 'muhaffizh_id', 'kuota', 'is_aktif'];

    protected string $activityLabel = 'Halaqah';

    protected $fillable = [
        'angkatan_id', 'muhaffizh_id', 'kode', 'nama', 'jenis_kelamin',
        'kuota', 'ruang', 'jadwal', 'is_aktif', 'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'is_aktif' => 'boolean',
            'kuota' => 'integer',
        ];
    }

    public function angkatan(): BelongsTo
    {
        return $this->belongsTo(Angkatan::class);
    }

    public function muhaffizh(): BelongsTo
    {
        return $this->belongsTo(Muhaffizh::class);
    }

    public function anggota(): HasMany
    {
        return $this->hasMany(AnggotaHalaqah::class);
    }

    /**
     * Hanya keanggotaan yang masih berjalan — yang sudah pindah tetap tersimpan
     * sebagai riwayat, tetapi tidak ikut dihitung sebagai santri binaan.
     */
    public function anggotaAktif(): HasMany
    {
        return $this->anggota()->where('is_aktif', true);
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('is_aktif', true);
    }

    /**
     * Sisa kursi. Null bila kuota tidak dibatasi.
     */
    public function getSisaKuotaAttribute(): ?int
    {
        if ($this->kuota === 0) {
            return null;
        }

        return max(0, $this->kuota - ($this->anggota_aktif_count ?? $this->anggotaAktif()->count()));
    }

    public function isPenuh(): bool
    {
        return $this->sisa_kuota === 0;
    }

    public function getJenisKelaminLabelAttribute(): string
    {
        return $this->jenis_kelamin === 'L' ? 'Ikhwan' : 'Akhwat';
    }

    protected function getActivityIdentity(): string
    {
        return (string) $this->nama;
    }
}
