<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function edit(): View
    {
        return view('account.password');
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', Password::min(8), 'different:current_password'],
        ], [
            'current_password.current_password' => 'Le mot de passe actuel est incorrect.',
        ], [
            'current_password' => 'mot de passe actuel',
            'password'         => 'nouveau mot de passe',
        ]);

        $request->user()->update(['password' => $validated['password']]);
        ActivityLogger::log('auth.password', 'Changement de mot de passe');

        return back()->with('success', 'Mot de passe modifié.');
    }
}
