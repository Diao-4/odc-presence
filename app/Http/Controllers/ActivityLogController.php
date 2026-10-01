<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = ActivityLog::with('user')
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('description', 'like', '%'.$request->input('q').'%')
                ->orWhere('action', 'like', '%'.$request->input('q').'%')))
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('logs.index', ['logs' => $logs]);
    }
}
