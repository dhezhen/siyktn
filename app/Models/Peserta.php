<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Orang. Satu baris seumur hidup, dikenali dari NIK.
 *
 * Keikutsertaan pada sebuah angkatan ada di model Pendaftaran — satu peserta
 * boleh mendaftar berkali-kali (alumni yang ikut program lanjutan, pendaftar
 * yang sebelumnya ditolak, dan sebagainya).
 */
class Peserta extends Model
{
    use RecordsActivity, SoftDeletes;

    protected $table = 'peserta';

    protected array $activityFields = ['nama', 'nik', 'kewarganegaraan', 'negara', 'provinsi', 'kabupaten_kota', 'no_hp', 'email', 'boleh_mendaftar_lagi'];

    protected string $activityLabel = 'Peserta';

    protected $fillable = [
        'nama', 'nik', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir',
        'kewarganegaraan', 'negara', 'provinsi', 'kabupaten_kota',
        'alamat', 'no_hp', 'email', 'nama_wali', 'no_hp_wali',
        'foto', 'ktp_path', 'boleh_mendaftar_lagi', 'alasan_cekal', 'user_id',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'boleh_mendaftar_lagi' => 'boolean',
        ];
    }

    public function pendaftaran(): HasMany
    {
        return $this->hasMany(Pendaftaran::class)->latest('didaftarkan_pada');
    }

    /**
     * Pendaftaran terbaru — yang biasanya ingin dilihat di daftar peserta.
     */
    public function pendaftaranTerakhir(): HasOne
    {
        return $this->hasOne(Pendaftaran::class)->latestOfMany('didaftarkan_pada');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->whereHas('pendaftaran', fn ($q) => $q
            ->where('status_pendaftaran', 'disetujui')
            ->where('status', 'aktif'));
    }

    /**
     * Peserta yang pernah menyelesaikan minimal satu angkatan.
     */
    public function scopeAlumni(Builder $query): Builder
    {
        return $query->whereHas('pendaftaran', fn ($q) => $q->where('status', 'lulus'));
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

    public function getAlamatLengkapFormattedAttribute(): string
    {
        $bagian = [];

        if ($this->alamat) {
            $bagian[] = $this->alamat;
        }

        if ($this->kabupaten_kota) {
            $bagian[] = $this->kabupaten_kota;
        }

        if ($this->kewarganegaraan === 'WNA') {
            if ($this->negara && $this->negara !== 'Indonesia') {
                $bagian[] = $this->negara;
            }
        } else {
            if ($this->provinsi) {
                $bagian[] = $this->provinsi;
            }
            $bagian[] = 'Indonesia';
        }

        return implode(', ', array_filter($bagian));
    }

    public function isAlumni(): bool
    {
        return $this->pendaftaran()->where('status', 'lulus')->exists();
    }

    /**
     * Cari orang berdasarkan NIK. Dipakai saat menerima pendaftaran ulang.
     */
    public static function cariBerdasarkanNik(?string $nik): ?self
    {
        return $nik ? static::where('nik', $nik)->first() : null;
    }
}
