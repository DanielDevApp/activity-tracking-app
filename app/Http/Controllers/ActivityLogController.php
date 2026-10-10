<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateActivityLogRequest;
use App\Models\ActivityLog;
use App\Services\ActivityLogService;

class ActivityLogController extends Controller
{
    public function update(UpdateActivityLogRequest $request, ActivityLog $log, ActivityLogService $service)
    {
        $changed = $service->update($log, $request->user(), $request->input('status'), $request->input('remark'));

        return back()->with('status', $changed ? 'Update saved and recorded.' : 'Nothing changed, so nothing was recorded.');
    }
}
