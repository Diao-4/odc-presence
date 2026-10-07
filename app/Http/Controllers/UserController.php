<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\CohortContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request, CohortContext $cohorts): View
    {
        $role     = in_array($request->input('role'), [User::ROLE_ADMIN, User::ROLE_AGENT], true) ? $request->input('role') : null;
        $q        = trim((string) $request->input('q'));
        $cohortId = $request->integer('cohort_id') ?: null;

        $users = User::query()
            ->with('cohort')
            ->when($role, fn ($query, $r) => $query->where('role', $r))
            ->when($cohortId, fn ($query, $id) => $query->where('cohort_id', $id))
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('name', 'like', "%{$q}%")
                ->orWhere('username', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%")))
            ->orderBy('role')->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('users.index', [
            'users'  => $users,
            'role'   => $role,
            'counts' => User::selectRaw('role, COUNT(*) as aggregate')->groupBy('role')->pluck('aggregate', 'role'),
            'cohorts'  => $cohorts->choices(),
            'cohortId' => $cohortId,
        ]);
    }

    public function create(Request $request, CohortContext $cohorts): View
    {
        $role = $request->input('role') === User::ROLE_ADMIN ? User::ROLE_ADMIN : User::ROLE_AGENT;

        return view('users.form', [
            'user'    => new User(['role' => $role, 'cohort_id' => $request->integer('cohort_id') ?: $cohorts->current()?->id]),
            'cohorts' => $cohorts->choices(),
        ]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $user = User::create($request->userData() + ['is_active' => true]);
        $where = $user->cohort ? " ({$user->cohort->label})" : '';
        ActivityLogger::log('user.create', "Création de l'utilisateur {$user->username} ({$user->role_label}){$where}");

        return redirect()->route('users.index')->with('success', 'Compte '.mb_strtolower($user->role_label)." créé pour {$user->name}{$where}. Identifiant de connexion : « {$user->username} ».");
    }

    public function edit(User $user, CohortContext $cohorts): View
    {
        return view('users.form', ['user' => $user, 'cohorts' => $cohorts->choices()]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $data = $request->userData();
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
