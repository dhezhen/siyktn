<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Keanggotaan satu pendaftaran (santri pada satu angkatan) di sebuah halaqah.
 */
class AnggotaHalaqah extends Model
{
    protected $table = 'anggota_halaqah';

    protected $fillable = [
        'halaqah_id', 'pendaftaran_id', 'tanggal_bergabung',
        'tanggal_keluar', 'is_aktif', 'alasan_pindah',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_bergabung' => 'date',
            'tanggal_keluar' => 'date',
            'is_aktif' => 'boolean',
        ];
    }

    /**
     * Kolom penjaga `kunci_aktif` tidak pernah diisi tangan — nilainya selalu
     * diturunkan dari is_aktif, supaya indeks uniknya tidak bisa dilewati
     * hanya karena satu pemanggil lupa mengisinya.
     */
    protected static function booted(): void
    {
        static::saving(function (self $anggota) {
            $anggota->kunci_aktif = $anggota->is_aktif ? $anggota->pendaftaran_id : null;
        });
    }

    public function halaqah(): BelongsTo
    {
        return $this->belongsTo(Halaqah::class);
    }

    public function pendaftaran(): BelongsTo
    {
        return $this->belongsTo(Pendaftaran::class);
    }

    public function setoran(): HasMany
    {
        return $this->hasMany(Setoran::class);
    }

    public function setoranTerakhir()
    {
        return $this->hasOne(Setoran::class)->ofMany([
            'tanggal' => 'max',
            'id' => 'max',
        ]);
    }

    /**
     * Total halaman hafalan baru selama keanggotaan ini.
     */
    public function totalZiyadah(): float
    {
        return (float) $this->setoran()->ziyadah()->sum('jumlah_halaman');
    }

    /**
     * Tutup keanggotaan ini tanpa menghapusnya, agar riwayat setoran tetap
     * bisa ditelusuri ke muhaffizh yang membimbing saat itu.
     */
    public function tutup(?string $alasan = null): void
    {
        $this->update([
            'is_aktif' => false,
            'tanggal_keluar' => now()->toDateString(),
            'alasan_pindah' => $alasan,
        ]);
    }
}
