<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('users.index', ['users' => User::orderBy('role')->orderBy('name')->paginate(20)]);
    }

    public function create(): View
    {
        return view('users.form', ['user' => new User(['role' => User::ROLE_AGENT])]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $user = User::create($request->validated() + ['is_active' => true]);
        ActivityLogger::log('user.create', "Création de l'utilisateur {$user->username} ({$user->role_label})");

        return redirect()->route('users.index')->with('success', 'Utilisateur créé.');
    }

    public function edit(User $user): View
    {
        return view('users.form', ['user' => $user]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        if (empty($data['password'])) {
            unset($data['password']);
        }

        if ($user->is($request->user()) && ($data['role'] ?? $user->role) !== User::ROLE_ADMIN) {
            return back()->with('error', 'Vous ne pouvez pas retirer votre propre rôle administrateur.');
        }

        $user->update($data);
        ActivityLogger::log('user.update', "Modification de l'utilisateur {$user->username}");

        return redirect()->route('users.index')->with('success', 'Utilisateur mis à jour.');
    }

    public function toggle(User $user): RedirectResponse
    {
        if ($user->is(auth()->user())) {
            return back()->with('error', 'Vous ne pouvez pas désactiver votre propre compte.');
        }

        $user->forceFill(['is_active' => ! $user->is_active, 'remember_token' => Str::random(60)])->save();

        if (! $user->is_active && config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete(); // déconnexion immédiate
        }

        $label = $user->is_active ? 'réactivé' : 'désactivé';
        ActivityLogger::log('user.toggle', "Utilisateur {$user->username} {$label}");

        return back()->with('success', "Utilisateur {$label}.");
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->is(auth()->user())) {
            return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        ActivityLogger::log('user.delete', "Suppression de l'utilisateur {$user->username}");
        $user->delete();

        return back()->with('success', 'Utilisateur supprimé.');
    }
}
