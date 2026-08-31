<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Pembimbing hafalan. Boleh mengampu lebih dari satu halaqah.
 */
class Muhaffizh extends Model
{
    use RecordsActivity, SoftDeletes;

    /**
     * Role yang otomatis mengikuti akun seorang muhaffizh.
     * Definisinya ada di config/rbac.php.
     */
    public const ROLE = 'muhaffizh';

    protected $table = 'muhaffizh';

    protected array $activityFields = ['nama', 'kode', 'status', 'user_id'];

    protected string $activityLabel = 'Muhaffizh';

    protected $fillable = [
        'user_id', 'kode', 'nama', 'jenis_kelamin', 'no_hp', 'email',
        'pendidikan', 'sanad_riwayat', 'tanggal_bergabung', 'status',
        'foto', 'keterangan',
    ];

    protected function casts(): array
    {
        return ['tanggal_bergabung' => 'date'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function halaqah(): HasMany
    {
        return $this->hasMany(Halaqah::class);
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('status', 'aktif');
    }

    /**
     * Jumlah santri yang sedang dibina di seluruh halaqah yang diampu.
     */
    public function getJumlahBinaanAttribute(): int
    {
        return AnggotaHalaqah::query()
            ->whereIn('halaqah_id', $this->halaqah()->select('id'))
            ->where('is_aktif', true)
            ->count();
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
        return $this->status === 'aktif' ? 'emerald' : 'slate';
    }

    /**
     * Kode berurut, mis. "MHF-004".
     */
    public static function kodeBerikutnya(): string
    {
        $terakhir = static::withTrashed()
            ->where('kode', 'like', 'MHF-%')
            ->orderByDesc('kode')
            ->value('kode');

        $urutan = $terakhir ? ((int) Str::afterLast($terakhir, '-')) + 1 : 1;

        return sprintf('MHF-%03d', $urutan);
    }

    protected function getActivityIdentity(): string
    {
        return (string) $this->nama;
    }

    /**
     * Role mengikuti penautan akun, bukan diingat petugas.
     *
     * Sebelumnya mengisi user_id sama sekali tidak memberi hak akses apa pun,
     * sehingga muhaffizh "yang sudah punya akun" bisa masuk tetapi disambut
     * sidebar kosong. Menaruhnya di sini — bukan di controller — membuat aturan
     * itu berlaku untuk semua jalur: form, seeder, maupun tinker.
     */
    protected static function booted(): void
    {
        // Sengaja dua event terpisah, bukan satu "saved": pada baris yang baru
        // dibuat wasChanged() tidak menandai apa pun, sehingga muhaffizh yang
        // langsung dibuat lengkap dengan user_id akan terlewat.
        static::created(fn (self $muhaffizh) => static::berikanRole($muhaffizh->user_id));

        static::updated(function (self $muhaffizh) {
            if (! $muhaffizh->wasChanged('user_id')) {
                return;
            }

            static::cabutRole($muhaffizh->getOriginal('user_id'));
            static::berikanRole($muhaffizh->user_id);
        });

        static::deleted(fn (self $muhaffizh) => static::cabutRole($muhaffizh->user_id));
        static::restored(fn (self $muhaffizh) => static::berikanRole($muhaffizh->user_id));
    }

    protected static function berikanRole(?int $userId): void
    {
        $user = static::akun($userId);

        if ($user && ! $user->hasRole(self::ROLE)) {
            $user->assignRole(self::ROLE);
        }
    }

    protected static function cabutRole(?int $userId): void
    {
        $user = static::akun($userId);

        if ($user && $user->hasRole(self::ROLE)) {
            $user->removeRole(self::ROLE);
        }
    }

    /**
     * Akun yang boleh disentuh penyesuaian role otomatis.
     *
     * Super admin dilewati: hak aksesnya tidak berasal dari daftar role dan
     * tidak boleh diutak-atik dari layar muhaffizh.
     */
    protected static function akun(?int $userId): ?User
    {
        if (! $userId || ! Role::where('name', self::ROLE)->where('guard_name', 'web')->exists()) {
            return null;
        }

        $user = User::find($userId);

        return $user && ! $user->is_super_admin ? $user : null;
    }
}
