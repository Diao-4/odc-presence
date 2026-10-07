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
        return view('settings.index', ['s' => $settings->all()]);
    }

    /** Paramètres communs à toutes les cohortes (les horaires sont réglés par cohorte). */
    public function update(SettingRequest $request, SettingService $settings): RedirectResponse
    {
        $settings->set([
            'late_enabled'       => $request->boolean('late_enabled'),
            'departure_enabled'  => $request->boolean('departure_enabled'),
            'default_export'     => $request->validated('default_export'),
            'scan_reset_seconds' => (int) $request->validated('scan_reset_seconds'),
            'badge_validity'     => (string) $request->validated('badge_validity'),
            'badge_size'         => $request->validated('badge_size'),
        ]);

        ActivityLogger::log('settings.update', 'Paramètres généraux mis à jour');

        return back()->with('success', 'Paramètres enregistrés.');
    }
}
