<?php

namespace App\Http\Controllers;

use App\Http\Requests\SettingRequest;
use App\Services\ActivityLogger;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(SettingService $settings): View
    {
        return view('settings.index', ['s' => $settings->all(), 'days' => $settings->trainingDays()]);
    }

    public function update(SettingRequest $request, SettingService $settings): RedirectResponse
    {
        $days = collect($request->validated('training_days'))->map(fn ($d) => (int) $d)->unique()->sort()->implode(',');

        $settings->set([
            'cutoff_time'        => $request->validated('cutoff_time'),
            'absence_time'       => (string) $request->validated('absence_time'),
            'late_enabled'       => $request->boolean('late_enabled'),
            'departure_enabled'  => $request->boolean('departure_enabled'),
            'default_export'     => $request->validated('default_export'),
            'training_days'      => $days,
            'scan_reset_seconds' => (int) $request->validated('scan_reset_seconds'),
            'badge_validity'     => (string) $request->validated('badge_validity'),
            'badge_size'         => $request->validated('badge_size'),
        ]);

        ActivityLogger::log('settings.update', "Paramètres mis à jour (heure limite {$request->validated('cutoff_time')})");

        return back()->with('success', 'Paramètres enregistrés.');
    }
}
