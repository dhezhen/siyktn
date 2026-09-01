<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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

    public const PAKET_PROGRAM = [
        '1_bulan' => [
            'nama' => 'Karantina Tahfizh Program Hafal Quran Sebulan (30 Hari)',
            'biaya' => 3750000,
            'durasi' => '30 Hari',
        ],
        '3_pekan' => [
            'nama' => 'Karantina Tahfizh Al-Quran Program 3 Pekan',
            'biaya' => 3250000,
            'durasi' => '21 Hari',
        ],
        '2_pekan' => [
            'nama' => 'Karantina Tahfizh Al-Quran Program 2 Pekan',
            'biaya' => 2500000,
            'durasi' => '14 Hari',
        ],
        '1_pekan' => [
            'nama' => 'Karantina Tahfizh Al-Quran Program 1 Pekan',
            'biaya' => 2000000,
            'durasi' => '7 Hari',
        ],
        '3_bulan' => [
            'nama' => 'Karantina Tahfizh Al-Quran Program Mutqin (3 Bulan)',
            'biaya' => 10850000,
            'durasi' => '90 Hari',
        ],
    ];

    protected array $activityFields = [
        'nomor_induk', 'angkatan_id', 'status', 'status_pendaftaran', 'status_kehadiran',
        'status_pembayaran_pendaftaran', 'status_pembayaran_program',
    ];

    protected string $activityLabel = 'Pendaftaran';

    protected $fillable = [
        'peserta_id', 'angkatan_id', 'program_id', 'kode_pendaftaran', 'nomor_induk',
        'status_pendaftaran', 'sumber_pendaftaran', 'status', 'status_kehadiran',
        'waktu_kehadiran', 'diverifikasi_oleh', 'tanggal_masuk', 'tanggal_selesai',
        'didaftarkan_pada', 'ditinjau_pada', 'ditinjau_oleh', 'alasan_penolakan',
        'paket_program', 'biaya_program', 'biaya_pendaftaran',
        'status_pembayaran_pendaftaran', 'status_pembayaran_program',
        'bukti_pembayaran_path', 'catatan_pembayaran',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_masuk' => 'date',
            'tanggal_selesai' => 'date',
            'didaftarkan_pada' => 'datetime',
            'ditinjau_pada' => 'datetime',
            'waktu_kehadiran' => 'datetime',
            'biaya_program' => 'decimal:2',
            'biaya_pendaftaran' => 'decimal:2',
        ];
    }

    protected static function booted()
    {
        static::saving(function (Pendaftaran $pendaftaran) {
            if ($pendaftaran->isDirty('tanggal_masuk') && $pendaftaran->tanggal_masuk) {
                $pendaftaran->tanggal_selesai = $pendaftaran->tanggal_masuk->clone()->addDays($pendaftaran->getDurasiHari());
            }
        });
    }

    public function getDurasiHari(): int
    {
        if ($this->program) {
            return $this->program->durasi_hari;
        }

        $durasiString = self::PAKET_PROGRAM[$this->paket_program]['durasi'] ?? '30 Hari';
        return (int) filter_var($durasiString, FILTER_SANITIZE_NUMBER_INT) ?: 30;
    }

    public function getPaketProgramLabelAttribute(): string
    {
        if ($this->program) {
            return $this->program->nama;
        }

        return self::PAKET_PROGRAM[$this->paket_program]['nama'] ?? ($this->paket_program ?: 'Karantina Tahfizh Standard');
    }

    public function getFormattedBiayaProgramAttribute(): string
    {
        return 'Rp '.number_format((float) $this->biaya_program, 0, ',', '.');
    }

    public function getFormattedBiayaPendaftaranAttribute(): string
    {
        return 'Rp '.number_format((float) $this->biaya_pendaftaran, 0, ',', '.');
    }

    public function isPembayaranPendaftaranLunas(): bool
    {
        return in_array($this->status_pembayaran_pendaftaran, ['lunas', 'bebas_biaya'], true);
    }

    public function isPembayaranProgramLunas(): bool
    {
        return $this->status_pembayaran_program === 'lunas';
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
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

    public function verifikatorKehadiran(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
    }

    public function konfirmasiKehadiran(?int $userId = null): bool
    {
        return $this->update([
            'status_kehadiran' => 'hadir',
            'waktu_kehadiran' => now(),
            'diverifikasi_oleh' => $userId ?: auth()->id(),
        ]);
    }

    public function anggotaHalaqah(): HasMany
    {
        return $this->hasMany(AnggotaHalaqah::class);
    }

    /**
     * Keanggotaan halaqah yang sedang berjalan. Dijamin paling banyak satu
     * oleh indeks unik "kunci_aktif" di tabel anggota_halaqah.
     */
    public function keanggotaanAktif(): HasOne
    {
        return $this->hasOne(AnggotaHalaqah::class)->where('is_aktif', true);
    }

    /**
     * Santri yang sudah aktif tetapi belum ditempatkan di halaqah mana pun.
     */
    public function scopeBelumBerhalaqah(Builder $query): Builder
    {
        return $query->aktif()->whereDoesntHave('keanggotaanAktif');
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

        if ($terakhir) {
            $urutan = str_contains($terakhir, '.') 
                ? (int) Str::afterLast($terakhir, '.') 
                : (int) Str::afterLast($terakhir, '-');
            $urutan++;
        } else {
            $urutan = 1;
        }

        $batch = Str::after($angkatan->kode, '-'); 
        if (!$batch || !is_numeric($batch)) {
           preg_match('/\d+/', $angkatan->nama, $matches);
           $batch = $matches[0] ?? $angkatan->kode;
        }

        return sprintf('YKTN.%d.%s.%04d', $angkatan->tahun, $batch, $urutan);
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
