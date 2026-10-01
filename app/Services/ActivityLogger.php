<?php

namespace App\Services;

use App\Models\ActivityLog;

class ActivityLogger
{
    public static function log(string $action, ?string $description = null): void
    {
        ActivityLog::create([
            'user_id'     => auth()->id(),
            'action'      => $action,
            'description' => $description ? mb_substr($description, 0, 500) : null,
            'ip_address'  => request()->ip(),
        ]);
    }
}
