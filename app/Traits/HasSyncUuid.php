<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasSyncUuid
{
    /**
     * Boot the trait.
     */
    protected static function bootHasSyncUuid()
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Initialize the trait.
     */
    public function initializeHasSyncUuid()
    {
        if (!in_array('uuid', $this->fillable)) {
            $this->fillable[] = 'uuid';
        }
        if (!in_array('remote_id', $this->fillable)) {
            $this->fillable[] = 'remote_id';
        }
        if (!in_array('last_synced_at', $this->fillable)) {
            $this->fillable[] = 'last_synced_at';
        }

        $this->casts['last_synced_at'] = 'datetime';
    }

}
