<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Peserta extends Model
{
    use RecordsActivity, SoftDeletes;

    protected $table = 'peserta';

    protected array $activityFields = ['nomor_induk', 'nama', 'angkatan_id', 'status', 'no_hp'];

    protected string $activityLabel = 'Peserta';

    protected $fillable = [
        'angkatan_id', 'nomor_induk', 'nama', 'jenis_kelamin',
        'tempat_lahir', 'tanggal_lahir', 'alamat', 'no_hp',
        'nama_wali', 'no_hp_wali', 'tanggal_masuk', 'status', 'foto', 'user_id',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'tanggal_masuk' => 'date',
        ];
    }

    public function angkatan(): BelongsTo
    {
        return $this->belongsTo(Angkatan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('status', 'aktif');
    }

    public function getFotoUrlAttribute(): ?string
    {
        if ($this->foto && Storage::disk('public')->exists($this->foto)) {
            return Storage::disk('public')->url($this->foto);
        }

        return null;
    }

    public function getInitialsAttribute(): string
    {
        $parts = preg_split('/\s+/', trim((string) $this->nama)) ?: [];

        return Str::upper(Str::substr($parts[0] ?? '?', 0, 1).Str::substr($parts[1] ?? '', 0, 1));
    }

    public function getJenisKelaminLabelAttribute(): string
    {
        return $this->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan';
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'aktif' => 'emerald',
            'lulus' => 'sky',
            'keluar' => 'rose',
            default => 'slate',
        };
    }

    /**
     * Nomor induk berikutnya untuk sebuah angkatan, mis. "AK-12-0007".
     */
    public static function nomorIndukBerikutnya(Angkatan $angkatan): string
    {
        $terakhir = static::withTrashed()
            ->where('angkatan_id', $angkatan->id)
            ->orderByDesc('nomor_induk')
            ->value('nomor_induk');

        $urutan = $terakhir ? ((int) Str::afterLast($terakhir, '-')) + 1 : 1;

        return sprintf('%s-%04d', $angkatan->kode, $urutan);
    }
}
