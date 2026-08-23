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

    protected array $activityFields = [
        'nomor_induk', 'nama', 'angkatan_id', 'status', 'no_hp', 'status_pendaftaran',
    ];

    protected string $activityLabel = 'Peserta';

    protected $fillable = [
        'kode_pendaftaran', 'angkatan_id', 'nomor_induk', 'nama', 'nik', 'jenis_kelamin',
        'tempat_lahir', 'tanggal_lahir', 'alamat', 'no_hp', 'email',
        'nama_wali', 'no_hp_wali', 'tanggal_masuk', 'status', 'foto', 'ktp_path', 'user_id',
        'status_pendaftaran', 'sumber_pendaftaran', 'didaftarkan_pada',
        'ditinjau_pada', 'ditinjau_oleh', 'alasan_penolakan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'tanggal_masuk' => 'date',
            'didaftarkan_pada' => 'datetime',
            'ditinjau_pada' => 'datetime',
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

    public function peninjau(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditinjau_oleh');
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('status', 'aktif');
    }

    public function scopeMenunggu(Builder $query): Builder
    {
        return $query->where('status_pendaftaran', 'menunggu');
    }

    public function scopeDisetujui(Builder $query): Builder
    {
        return $query->where('status_pendaftaran', 'disetujui');
    }

    public function scopeMandiri(Builder $query): Builder
    {
        return $query->where('sumber_pendaftaran', 'mandiri');
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

    public function isMenunggu(): bool
    {
        return $this->status_pendaftaran === 'menunggu';
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
     * Dipakai pendaftar untuk menanyakan status berkasnya.
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
}
