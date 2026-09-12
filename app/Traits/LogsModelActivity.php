<?php

namespace App\Traits;

use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Trait for models that should log changes to the activity log.
 *
 * Models using this trait will record create, update and delete events
 * to the activity_log table for audit and tracing purposes.
 */
trait LogsModelActivity
{
    use LogsActivity;

    /**
     * Configure which attributes should be logged.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
