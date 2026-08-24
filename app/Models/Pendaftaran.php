<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Keikutsertaan satu peserta pada satu angkatan.
 *
 * Satu peserta boleh punya banyak baris di sini, tetapi hanya satu per
 * angkatan (dijaga unique(peserta_id, angkatan_id) di migrasi).
 */
class Pendaftaran extends Model
{
    use RecordsActivity, SoftDeletes;

    protected $table = 'pendaftaran';

    protected array $activityFields = [
        'nomor_induk', 'angkatan_id', 'status', 'status_pendaftaran',
    ];

    protected string $activityLabel = 'Pendaftaran';

    protected $fillable = [
        'peserta_id', 'angkatan_id', 'kode_pendaftaran', 'nomor_induk',
        'status_pendaftaran', 'sumber_pendaftaran', 'status', 'tanggal_masuk',
        'didaftarkan_pada', 'ditinjau_pada', 'ditinjau_oleh', 'alasan_penolakan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_masuk' => 'date',
            'didaftarkan_pada' => 'datetime',
            'ditinjau_pada' => 'datetime',
        ];
    }

    public function peserta(): BelongsTo
    {
        return $this->belongsTo(Peserta::class);
    }

    public function angkatan(): BelongsTo
    {
        return $this->belongsTo(Angkatan::class);
    }

    public function peninjau(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditinjau_oleh');
    }

    public function scopeMenunggu(Builder $query): Builder
    {
        return $query->where('status_pendaftaran', 'menunggu');
    }

    public function scopeDisetujui(Builder $query): Builder
    {
        return $query->where('status_pendaftaran', 'disetujui');
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('status_pendaftaran', 'disetujui')->where('status', 'aktif');
    }

    public function isMenunggu(): bool
    {
        return $this->status_pendaftaran === 'menunggu';
    }

    /**
     * Pendaftaran ulang: peserta ini sudah pernah mendaftar sebelumnya.
     */
    public function isPendaftaranUlang(): bool
    {
        return static::query()
            ->where('peserta_id', $this->peserta_id)
            ->where('id', '!=', $this->id)
            ->exists();
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'aktif' => 'Aktif',
            'lulus' => 'Lulus',
            'keluar' => 'Keluar',
            default => $this->status,
        };
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

    public function getStatusPendaftaranLabelAttribute(): string
    {
        return match ($this->status_pendaftaran) {
            'menunggu' => 'Menunggu Verifikasi',
            'disetujui' => 'Disetujui',
            'ditolak' => 'Ditolak',
            default => $this->status_pendaftaran,
        };
    }

    public function getStatusPendaftaranColorAttribute(): string
    {
        return match ($this->status_pendaftaran) {
            'menunggu' => 'amber',
            'disetujui' => 'emerald',
            'ditolak' => 'rose',
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
            ->whereNotNull('nomor_induk')
            ->orderByDesc('nomor_induk')
            ->value('nomor_induk');

        $urutan = $terakhir ? ((int) Str::afterLast($terakhir, '-')) + 1 : 1;

        return sprintf('%s-%04d', $angkatan->kode, $urutan);
    }

    /**
     * Kode tanda terima pendaftaran, mis. "REG-2026-0042".
     */
    public static function kodePendaftaranBerikutnya(): string
    {
        $tahun = now()->year;

        $terakhir = static::withTrashed()
            ->where('kode_pendaftaran', 'like', "REG-{$tahun}-%")
            ->orderByDesc('kode_pendaftaran')
            ->value('kode_pendaftaran');

        $urutan = $terakhir ? ((int) Str::afterLast($terakhir, '-')) + 1 : 1;

        return sprintf('REG-%d-%04d', $tahun, $urutan);
    }

    /**
     * Keterangan aktivitas memakai nama orangnya, bukan id pendaftaran.
     */
    protected function getActivityIdentity(): string
    {
        return $this->peserta?->nama ?? (string) $this->kode_pendaftaran;
    }
}
