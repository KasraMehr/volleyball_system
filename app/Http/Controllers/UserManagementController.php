<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class UserManagementController extends Controller
{
    public function index(Request $request): Response
    {
        $users = User::query()
            ->where('club_id', $request->user()->club_id)
            ->with('assignedTeams:id,name')
            ->when($request->string('search')->toString(), fn ($query, $search) => $query->where(function ($nested) use ($search): void {
                $nested->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            }))
            ->when($request->string('role')->toString(), fn ($query, $role) => $query->where('role', $role))
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role->value,
                'role_label' => $user->role->label(),
                'is_active' => $user->is_active,
                'can_toggle' => ! $user->is($request->user()),
                'teams' => $user->assignedTeams->pluck('name'),
            ]);

        return Inertia::render('Users/Index', [
            'users' => $users,
            'roles' => UserRole::options(),
            'teams' => Team::query()->where('club_id', $request->user()->club_id)->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['search', 'role']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:15'],
            'role' => ['required', 'in:'.implode(',', array_column(UserRole::options(), 'value'))],
            'password' => ['required', Password::min(8)],
            'team_ids' => ['array'],
            'team_ids.*' => ['integer', 'exists:teams,id'],
        ]);

        $user = User::create([
            ...collect($data)->except('team_ids')->all(),
            'club_id' => $request->user()->club_id,
            'is_active' => true,
        ]);

        if ($user->role === UserRole::Coach) {
            $allowedTeamIds = Team::query()->where('club_id', $request->user()->club_id)->whereKey($data['team_ids'] ?? [])->pluck('id');
            $user->assignedTeams()->sync($allowedTeamIds);
        }

        return back()->with('success', 'حساب کاربری با موفقیت ایجاد شد.');
    }

    public function toggle(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->club_id === $request->user()->club_id, 404);
        abort_if($user->is($request->user()), 422, 'امکان غیرفعال‌کردن حساب خودتان وجود ندارد.');

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('success', 'وضعیت حساب کاربری تغییر کرد.');
    }
}
