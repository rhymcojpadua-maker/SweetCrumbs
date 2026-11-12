<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    public function log(
        string $action,
        string $description,
        ?Model $subject = null,
        ?Shop $shop = null,
        array $properties = []
    ): ActivityLog {
        $request = request();

        return ActivityLog::create([
            'user_id' => Auth::id(),
            'shop_id' => $shop?->id,
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'properties' => $properties ?: null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}
