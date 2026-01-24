<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function getLogs()
    {
        $logName = request('log_name');
        $activityLogs = Activity::with(['causer', 'subject'])
            ->when($logName, function ($query) use ($logName) {
                $query->where('log_name', $logName);
            })
            ->latest()
            ->paginate(20)
            ->withQueryString(); // pagination এ filter ধরে রাখবে
        return $activityLogs;  
    }
}
