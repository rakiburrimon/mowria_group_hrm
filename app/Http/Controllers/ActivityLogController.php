<?php

namespace App\Http\Controllers;

use App\DataTables\ActivityLogsDataTable;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    /**
     * Display the audit trail / activity log.
     */
    public function index(ActivityLogsDataTable $dataTable)
    {
        $logNames = Activity::query()
            ->select('log_name')
            ->distinct()
            ->pluck('log_name');

        return $dataTable->render('activity-logs.index', compact('logNames'));
    }

    /**
     * Return full details for a single activity log entry (modal JSON).
     */
    public function show(Activity $activity)
    {
        $activity->load(['causer', 'subject']);

        $subject = $activity->subject;
        $causer = $activity->causer;

        return response()->json([
            'id' => $activity->id,
            'log_name' => $activity->log_name,
            'description' => $activity->description,
            'event' => $activity->event,
            'subject_type' => $activity->subject_type,
            'subject_id' => $activity->subject_id,
            'subject_label' => $subject
                ? ($subject->full_name ?? $subject->name ?? $subject->title ?? class_basename($activity->subject_type) . ' #' . $activity->subject_id)
                : null,
            'causer_type' => $activity->causer_type,
            'causer_id' => $activity->causer_id,
            'causer_name' => $causer?->name,
            'causer_email' => $causer?->email,
            'properties' => $activity->properties?->toArray() ?? [],
            'batch_uuid' => $activity->batch_uuid,
            'created_at' => $activity->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $activity->updated_at?->format('Y-m-d H:i:s'),
        ]);
    }
}
