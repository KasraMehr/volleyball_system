<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Athlete;
use App\Models\ClubNotification;
use App\Models\Team;
use App\Models\WorkoutExercise;
use App\Models\WorkoutPlan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class WorkoutPlanController extends Controller
{
    public function index(Request $request): Response
    {
        $teams = $this->availableTeams($request);
        $plans = WorkoutPlan::query()->where('club_id', $request->user()->club_id)
            ->when($request->user()->hasRole(UserRole::Coach), fn (Builder $query) => $query->where('coach_id', $request->user()->id))
            ->with(['coach:id,name', 'team:id,name', 'athlete:id,first_name,last_name', 'exercises.completions'])
            ->latest()->get()->map(fn (WorkoutPlan $plan) => $this->planData($plan));

        return Inertia::render('WorkoutPlans/Index', [
            'plans' => $plans,
            'teams' => $teams->map(fn (Team $team) => ['id' => $team->id, 'name' => $team->name]),
            'athletes' => Athlete::query()->visibleTo($request->user())->with('teams:id,name')->where('is_active', true)->orderBy('last_name')->get()->map(fn (Athlete $athlete) => ['id' => $athlete->id, 'name' => $athlete->full_name, 'team_ids' => $athlete->teams->pluck('id')]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $teamIds = $this->availableTeams($request)->pluck('id')->all();
        $athleteIds = Athlete::query()->visibleTo($request->user())->pluck('id')->all();
        $data = $request->validate([
            'title' => ['required', 'string', 'max:140'], 'description' => ['nullable', 'string', 'max:1500'],
            'target_type' => ['required', 'in:team,athlete'],
            'team_id' => ['nullable', Rule::in($teamIds), 'required_if:target_type,team'],
            'athlete_id' => ['nullable', Rule::in($athleteIds), 'required_if:target_type,athlete'],
            'starts_on' => ['required', 'date'], 'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'status' => ['required', 'in:draft,active'],
            'exercises' => ['required', 'array', 'min:1', 'max:30'],
            'exercises.*.day_label' => ['nullable', 'string', 'max:40'], 'exercises.*.title' => ['required', 'string', 'max:140'],
            'exercises.*.category' => ['required', 'in:strength,jump,speed,serve,reception,spike,block,recovery'],
            'exercises.*.sets' => ['nullable', 'integer', 'between:1,20'], 'exercises.*.reps' => ['nullable', 'integer', 'between:1,200'],
            'exercises.*.duration_minutes' => ['nullable', 'integer', 'between:1,240'], 'exercises.*.rest_seconds' => ['nullable', 'integer', 'between:0,600'],
            'exercises.*.notes' => ['nullable', 'string', 'max:600'],
        ]);

        $plan = DB::transaction(function () use ($request, $data): WorkoutPlan {
            $plan = WorkoutPlan::create([
                'club_id' => $request->user()->club_id, 'coach_id' => $request->user()->id,
                'team_id' => $data['target_type'] === 'team' ? $data['team_id'] : null,
                'athlete_id' => $data['target_type'] === 'athlete' ? $data['athlete_id'] : null,
                'title' => $data['title'], 'description' => $data['description'] ?? null,
                'starts_on' => $data['starts_on'], 'ends_on' => $data['ends_on'], 'status' => $data['status'],
            ]);
            $plan->exercises()->createMany(collect($data['exercises'])->values()->map(fn (array $exercise, int $index) => [...$exercise, 'sort_order' => $index])->all());
            return $plan;
        });

        if ($plan->status === 'active') $this->notifyMembers($request, $plan);
        return back()->with('success', 'برنامه تمرینی ساخته شد و در پنل ورزشکار قرار گرفت.');
    }

    public function status(Request $request, WorkoutPlan $plan): RedirectResponse
    {
        $this->authorizePlan($request, $plan);
        $data = $request->validate(['status' => ['required', 'in:draft,active,completed']]);
        $wasActive = $plan->status === 'active';
        $plan->update($data);
        if (! $wasActive && $plan->status === 'active') $this->notifyMembers($request, $plan);
        return back()->with('success', 'وضعیت برنامه به‌روزرسانی شد.');
    }

    public function mine(Request $request): Response
    {
        $profiles = Athlete::query()->visibleTo($request->user())->with('teams:id')->orderBy('first_name')->get();
        $selected = $profiles->firstWhere('id', $request->integer('athlete')) ?? $profiles->first();
        $plans = collect();
        if ($selected) {
            $teamIds = $selected->teams->pluck('id');
            $plans = WorkoutPlan::query()->where('club_id', $request->user()->club_id)->where('status', 'active')
                ->where(fn (Builder $query) => $query->where('athlete_id', $selected->id)->orWhereIn('team_id', $teamIds))
                ->whereDate('ends_on', '>=', today())->with(['coach:id,name', 'team:id,name', 'exercises.completions' => fn ($query) => $query->where('athlete_id', $selected->id)])
                ->orderBy('starts_on')->get()->map(fn (WorkoutPlan $plan) => $this->planData($plan, $selected->id));
        }
        return Inertia::render('WorkoutPlans/Mine', [
            'viewer' => ['mode' => $request->user()->role->value],
            'profiles' => $profiles->map(fn (Athlete $athlete) => ['id' => $athlete->id, 'name' => $athlete->full_name, 'photo_url' => $athlete->photo_path ? Storage::disk('public')->url($athlete->photo_path) : null]),
            'athlete' => $selected ? ['id' => $selected->id, 'name' => $selected->full_name] : null,
            'plans' => $plans,
            'canComplete' => $request->user()->hasRole(UserRole::Athlete),
        ]);
    }

    public function complete(Request $request, WorkoutExercise $exercise): RedirectResponse
    {
        abort_unless($request->user()->hasRole(UserRole::Athlete), 403);
        $athlete = Athlete::query()->where('user_id', $request->user()->id)->firstOrFail();
        $plan = $exercise->plan()->firstOrFail();
        abort_unless($plan->status === 'active' && ($plan->athlete_id === $athlete->id || ($plan->team_id && $athlete->teams()->whereKey($plan->team_id)->exists())), 404);
        $data = $request->validate(['completed' => ['required', 'boolean'], 'effort' => ['nullable', 'integer', 'between:1,5'], 'note' => ['nullable', 'string', 'max:500']]);
        if ($data['completed']) {
            $exercise->completions()->updateOrCreate(['athlete_id' => $athlete->id], ['completed_at' => now(), 'effort' => $data['effort'] ?? null, 'note' => $data['note'] ?? null]);
        } else {
            $exercise->completions()->where('athlete_id', $athlete->id)->delete();
        }
        return back()->with('success', $data['completed'] ? 'تمرین انجام‌شده ثبت شد.' : 'تمرین به حالت انجام‌نشده برگشت.');
    }

    private function planData(WorkoutPlan $plan, ?int $athleteId = null): array
    {
        return ['id' => $plan->id, 'title' => $plan->title, 'description' => $plan->description, 'status' => $plan->status,
            'starts_on' => $plan->starts_on->format('Y-m-d'), 'ends_on' => $plan->ends_on->format('Y-m-d'), 'coach' => $plan->coach->name,
            'target' => $plan->athlete?->full_name ?? $plan->team?->name, 'target_type' => $plan->athlete_id ? 'athlete' : 'team',
            'exercises' => $plan->exercises->map(fn (WorkoutExercise $exercise) => ['id' => $exercise->id, 'day_label' => $exercise->day_label, 'title' => $exercise->title, 'category' => $exercise->category, 'sets' => $exercise->sets, 'reps' => $exercise->reps, 'duration_minutes' => $exercise->duration_minutes, 'rest_seconds' => $exercise->rest_seconds, 'notes' => $exercise->notes, 'completed' => $athleteId ? $exercise->completions->where('athlete_id', $athleteId)->isNotEmpty() : null, 'completion_count' => $exercise->completions->count()]),
        ];
    }

    private function availableTeams(Request $request)
    {
        return Team::query()->where('club_id', $request->user()->club_id)->where('is_active', true)
            ->when($request->user()->hasRole(UserRole::Coach), fn (Builder $query) => $query->whereHas('coaches', fn (Builder $coach) => $coach->whereKey($request->user()->id)))->orderBy('name')->get();
    }

    private function authorizePlan(Request $request, WorkoutPlan $plan): void
    {
        abort_unless($plan->club_id === $request->user()->club_id && ($request->user()->hasRole(UserRole::ClubAdmin) || $plan->coach_id === $request->user()->id), 404);
    }

    private function notifyMembers(Request $request, WorkoutPlan $plan): void
    {
        $athletes = $plan->athlete_id ? Athlete::query()->whereKey($plan->athlete_id)->get() : Team::find($plan->team_id)?->athletes()->get() ?? collect();
        $users = $athletes->flatMap(fn (Athlete $athlete) => collect([$athlete->user_id])->merge($athlete->guardians()->pluck('users.id')))->filter()->unique();
        foreach ($users as $userId) ClubNotification::create(['club_id' => $plan->club_id, 'user_id' => $userId, 'sender_id' => $request->user()->id, 'type' => 'training_plan', 'title' => 'برنامه تمرینی جدید', 'message' => $plan->title, 'action_url' => '/my-training', 'channel' => 'in_app', 'delivery_status' => 'delivered', 'metadata' => ['workout_plan_id' => $plan->id], 'sent_at' => now()]);
    }
}
