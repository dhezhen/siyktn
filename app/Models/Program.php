<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Program extends Model
{
    use RecordsActivity, SoftDeletes;

    protected $table = 'program';

    protected array $activityFields = ['nama', 'kode', 'durasi_hari', 'biaya_program', 'biaya_pendaftaran', 'is_aktif'];

    protected string $activityLabel = 'Paket Program';

    protected $fillable = [
        'nama',
        'kode',
        'durasi_hari',
        'biaya_program',
        'biaya_pendaftaran',
        'is_aktif',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'durasi_hari' => 'integer',
            'biaya_program' => 'decimal:2',
            'biaya_pendaftaran' => 'decimal:2',
            'is_aktif' => 'boolean',
        ];
    }

    public function pendaftaran(): HasMany
    {
        return $this->hasMany(Pendaftaran::class);
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('is_aktif', true);
    }

    public function getFormattedBiayaProgramAttribute(): string
    {
        return 'Rp '.number_format((float) $this->biaya_program, 0, ',', '.');
    }

    public function getFormattedBiayaPendaftaranAttribute(): string
    {
        return 'Rp '.number_format((float) $this->biaya_pendaftaran, 0, ',', '.');
    }

    public static function kodeBerikutnya(): string
    {
        $max = static::withTrashed()->max('id') ?? 0;

        return sprintf('PROG-%02d', $max + 1);
    }
}
