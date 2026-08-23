<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Pencatatan aktivitas dengan pengaturan yang seragam untuk semua model.
 *
 * Model yang memakainya cukup mendefinisikan:
 *   protected array $activityFields = ['name', 'email'];   // kolom yang dicatat
 *   protected string $activityLabel = 'Pengguna';          // sebutan di log
 */
trait RecordsActivity
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly($this->activityFields ?? ['*'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName($this->activityLabel ?? class_basename($this));
    }

    /**
     * Kalimat log dalam bahasa Indonesia, mis. "Pengguna 'Budi' diubah".
     */
    public function getDescriptionForEvent(string $eventName): string
    {
        $verbs = [
            'created' => 'ditambahkan',
            'updated' => 'diubah',
            'deleted' => 'dihapus',
            'restored' => 'dipulihkan',
        ];

        $label = $this->activityLabel ?? class_basename($this);
        $identity = $this->getActivityIdentity();

        return trim("{$label} '{$identity}' ".($verbs[$eventName] ?? $eventName));
    }

    /**
     * Nama yang ditampilkan di log. Model boleh menimpa method ini.
     */
    protected function getActivityIdentity(): string
    {
        /** @var Model $this */
        foreach (['name', 'title', 'label', 'key'] as $column) {
            if (! empty($this->{$column})) {
                return (string) $this->{$column};
            }
        }

        return (string) $this->getKey();
    }
}
