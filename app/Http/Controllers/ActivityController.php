<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

class ActivityController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('permission:activity.view')];
    }

    public function index(Request $request): View
    {
        $activities = Activity::query()
            ->with('causer:id,name,username')
            ->when($request->filled('log'), fn ($q) => $q->where('log_name', $request->string('log')))
            ->when($request->filled('event'), fn ($q) => $q->where('event', $request->string('event')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where('description', 'like', $term);
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('activity.index', [
            'activities' => $activities,
            'logNames' => Activity::query()->distinct()->orderBy('log_name')->pluck('log_name'),
        ]);
    }
}
