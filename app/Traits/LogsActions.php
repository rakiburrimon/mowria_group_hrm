<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Trait for controllers and services that should log actions.
 *
 * Uses spatie/laravel-activitylog to record who performed an action,
 * what it affected, and any extra properties.
 */
trait LogsActions
{
    /**
     * Log an action performed by the current user.
     *
     * @param string $action Description of the action
     * @param Model|null $subject The model the action was performed on
     * @param array $properties Extra context to store with the log
     */
    protected function logAction(string $action, ?Model $subject = null, array $properties = []): void
    {
        $activity = activity()->causedBy(Auth::user());

        if ($subject) {
            $activity->performedOn($subject);
        }

        $activity->withProperties($properties)->log($action);
    }
}
