<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActivityRequest;
use App\Models\Activity;

class ActivityController extends Controller
{
    public function index()
    {
        return view('activities.index', [
            'activities' => Activity::withCount('logs')->orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function create()
    {
        return view('activities.form', ['activity' => new Activity(['is_active' => true, 'sort_order' => Activity::max('sort_order') + 1])]);
    }

    public function store(ActivityRequest $request)
    {
        Activity::create($request->validated() + ['created_by' => $request->user()->id]);

        return redirect()->route('activities.index')->with('status', 'Activity added. It appears on the next daily checklist.');
    }

    public function edit(Activity $activity)
    {
        return view('activities.form', compact('activity'));
    }

    public function update(ActivityRequest $request, Activity $activity)
    {
        $activity->update($request->validated());

        return redirect()->route('activities.index')->with('status', 'Activity updated.');
    }

    public function destroy(Activity $activity)
    {
        // Soft delete: past logs and the audit trail keep their activity title.
        $activity->delete();

        return back()->with('status', 'Activity removed. Its history is kept.');
    }
}
