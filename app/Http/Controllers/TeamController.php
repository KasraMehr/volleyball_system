<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    public function index(Request $request): Response
    {
        $clubId = $request->user()->club_id;
        $teams = Team::query()->where('club_id', $clubId)
            ->with(['coaches:id,name', 'athletes:id'])
            ->orderByDesc('is_active')->orderBy('name')->get()
            ->map(fn (Team $team) => [
                'id' => $team->id, 'name' => $team->name, 'code' => $team->code,
                'gender' => $team->gender, 'age_group' => $team->age_group, 'color' => $team->color,
                'capacity' => $team->capacity, 'is_active' => $team->is_active,
                'athletes_count' => $team->athletes->count(),
                'coaches' => $team->coaches->map(fn (User $coach) => ['id' => $coach->id, 'name' => $coach->name]),
            ]);

        return Inertia::render('Teams/Index', [
            'teams' => $teams,
            'coaches' => User::query()->where('club_id', $clubId)->where('role', UserRole::Coach->value)->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $team = Team::create([...collect($data)->except('coach_ids')->all(), 'club_id' => $request->user()->club_id, 'is_active' => true]);
        $team->coaches()->sync($data['coach_ids'] ?? []);
        return back()->with('success', 'تیم جدید ایجاد شد.');
    }

    public function update(Request $request, Team $team): RedirectResponse
    {
        abort_unless($team->club_id === $request->user()->club_id, 404);
        $data = $this->validated($request, $team);
        $team->update(collect($data)->except('coach_ids')->all());
        $team->coaches()->sync($data['coach_ids'] ?? []);
        return back()->with('success', 'اطلاعات تیم به‌روزرسانی شد.');
    }

    public function toggle(Request $request, Team $team): RedirectResponse
    {
        abort_unless($team->club_id === $request->user()->club_id, 404);
        $team->update(['is_active' => ! $team->is_active]);
        return back()->with('success', 'وضعیت تیم تغییر کرد.');
    }

    private function validated(Request $request, ?Team $team = null): array
    {
        $clubId = $request->user()->club_id;
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:30', Rule::unique('teams')->where('club_id', $clubId)->ignore($team)],
            'gender' => ['required', 'in:male,female,mixed'],
            'age_group' => ['nullable', 'string', 'max:50'],
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'capacity' => ['required', 'integer', 'between:1,100'],
            'coach_ids' => ['array'],
            'coach_ids.*' => ['integer', Rule::exists('users', 'id')->where(fn ($query) => $query->where('club_id', $clubId)->where('role', UserRole::Coach->value))],
        ]);
    }
}
