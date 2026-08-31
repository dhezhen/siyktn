<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Satu kali setoran hafalan, dicatat dalam satuan halaman.
 */
class Setoran extends Model
{
    use RecordsActivity, SoftDeletes;

    /**
     * Halaman per juz pada mushaf standar. Dipakai hanya untuk menerjemahkan
     * rekap halaman menjadi "setara juz" agar lebih mudah dibaca.
     */
    public const HALAMAN_PER_JUZ = 20;

    protected $table = 'setoran';

    protected array $activityFields = ['tanggal', 'jenis', 'jumlah_halaman', 'muhaffizh_id', 'kualitas'];

    protected string $activityLabel = 'Setoran';

    protected $fillable = [
        'anggota_halaqah_id', 'muhaffizh_id', 'dicatat_oleh', 'tanggal', 'jenis',
        'jumlah_halaman', 'juz', 'surah', 'ayat_dari', 'ayat_sampai', 'kualitas', 'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'jumlah_halaman' => 'decimal:2',
            'juz' => 'integer',
            'ayat_dari' => 'integer',
            'ayat_sampai' => 'integer',
        ];
    }

    public function anggotaHalaqah(): BelongsTo
    {
        return $this->belongsTo(AnggotaHalaqah::class);
    }

    public function muhaffizh(): BelongsTo
    {
        return $this->belongsTo(Muhaffizh::class);
    }

    public function pencatat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }

    public function scopeZiyadah(Builder $query): Builder
    {
        return $query->where('jenis', 'ziyadah');
    }

    public function scopeMurajaah(Builder $query): Builder
    {
        return $query->where('jenis', 'murajaah');
    }

    /**
     * Setoran milik halaqah tertentu, lewat keanggotaannya.
     */
    public function scopeUntukHalaqah(Builder $query, int $halaqahId): Builder
    {
        return $query->whereHas('anggotaHalaqah', fn ($q) => $q->where('halaqah_id', $halaqahId));
    }

    /**
     * Setoran seluruh halaqah yang diampu seorang muhaffizh.
     *
     * Memakai halaqah tempat setoran itu terjadi, bukan kolom muhaffizh_id,
     * supaya pengampu baru tetap bisa melihat riwayat halaqah yang diwariskan
     * kepadanya.
     */
    public function scopeDiampuOleh(Builder $query, int $muhaffizhId): Builder
    {
        return $query->whereHas('anggotaHalaqah.halaqah',
            fn ($q) => $q->where('muhaffizh_id', $muhaffizhId));
    }

    /**
     * Dicatat sendiri oleh muhaffizhnya, atau dientri petugas dari kartu?
     */
    public function isDicatatPetugas(): bool
    {
        return $this->muhaffizh_id !== null
            && $this->pencatat?->muhaffizh?->id !== $this->muhaffizh_id;
    }

    public function getJenisLabelAttribute(): string
    {
        return $this->jenis === 'ziyadah' ? 'Ziyadah' : 'Muraja\'ah';
    }

    public function getJenisColorAttribute(): string
    {
        return $this->jenis === 'ziyadah' ? 'emerald' : 'sky';
    }

    public function getKualitasLabelAttribute(): string
    {
        return match ($this->kualitas) {
            'mumtaz' => 'Mumtaz',
            'jayyid' => 'Jayyid',
            'maqbul' => 'Maqbul',
            'perlu_diulang' => 'Perlu Diulang',
            default => (string) $this->kualitas,
        };
    }

    public function getKualitasColorAttribute(): string
    {
        return match ($this->kualitas) {
            'mumtaz' => 'emerald',
            'jayyid' => 'sky',
            'maqbul' => 'amber',
            'perlu_diulang' => 'rose',
            default => 'slate',
        };
    }

    /**
     * Ringkasan bacaan, mis. "Al-Baqarah 1–16" atau "Juz 3".
     */
    public function getBacaanAttribute(): string
    {
        if ($this->surah) {
            $ayat = $this->ayat_dari
                ? ' '.$this->ayat_dari.($this->ayat_sampai ? '–'.$this->ayat_sampai : '')
                : '';

            return $this->surah.$ayat;
        }

        return $this->juz ? 'Juz '.$this->juz : '—';
    }

    /**
     * Halaman menjadi "setara juz", mis. 45 halaman jadi "2,3 juz".
     */
    public static function setaraJuz(float $halaman): string
    {
        return number_format($halaman / self::HALAMAN_PER_JUZ, 1, ',', '.').' juz';
    }

    protected function getActivityIdentity(): string
    {
        $nama = $this->anggotaHalaqah?->pendaftaran?->peserta?->nama ?? 'santri';

        return $nama.' ('.$this->tanggal?->format('d/m/Y').')';
    }
}
