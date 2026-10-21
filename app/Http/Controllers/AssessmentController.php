<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Athlete;
use App\Models\PerformanceAssessment;
use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AssessmentController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $athletes = Athlete::query()->visibleTo($user)
            ->with(['teams:id,name', 'latestAssessment.recorder:id,name'])
            ->when($request->string('search')->toString(), fn (Builder $query, string $search) => $query->where(fn (Builder $nested) => $nested->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%")->orWhere('membership_number', 'like', "%{$search}%")))
            ->when($request->integer('team'), fn (Builder $query, int $teamId) => $query->whereHas('teams', fn (Builder $team) => $team->whereKey($teamId)))
            ->where('is_active', true)->orderBy('last_name')->paginate(12)->withQueryString()
            ->through(fn (Athlete $athlete) => $this->athleteRow($athlete));

        return Inertia::render('Assessments/Index', [
            'athletes' => $athletes,
            'teams' => $this->availableTeams($request)->map(fn (Team $team) => ['id' => $team->id, 'name' => $team->name]),
            'filters' => $request->only(['search', 'team']),
            'summary' => [
                'total' => Athlete::query()->visibleTo($user)->where('is_active', true)->count(),
                'assessed' => Athlete::query()->visibleTo($user)->whereHas('performanceAssessments')->count(),
                'outdated' => Athlete::query()->visibleTo($user)->where(fn (Builder $query) => $query->whereDoesntHave('performanceAssessments')->orWhereDoesntHave('performanceAssessments', fn (Builder $assessment) => $assessment->whereDate('assessment_date', '>=', now()->subDays(60))))->count(),
            ],
        ]);
    }

    public function show(Request $request, Athlete $athlete): Response
    {
        $this->authorizeVisible($request, $athlete);
        return $this->historyResponse($request, $athlete, 'Assessments/Show');
    }

    public function mine(Request $request): Response
    {
        $athlete = Athlete::query()->visibleTo($request->user())->with('teams:id,name')->first();
        if (! $athlete) {
            return Inertia::render('Assessments/Show', ['athlete' => null, 'assessments' => [], 'progress' => [], 'canRecord' => false]);
        }
        return $this->historyResponse($request, $athlete, 'Assessments/Show');
    }

    public function store(Request $request, Athlete $athlete): RedirectResponse
    {
        $this->authorizeVisible($request, $athlete);
        abort_unless($request->user()->hasRole(UserRole::ClubAdmin, UserRole::Coach), 403);
        $data = $request->validate([
            'assessment_date' => ['required', 'date', 'before_or_equal:today'],
            'height_cm' => ['nullable', 'numeric', 'between:80,250'],
            'weight_kg' => ['nullable', 'numeric', 'between:20,250'],
            'standing_reach_cm' => ['nullable', 'numeric', 'between:100,350'],
            'vertical_jump_cm' => ['nullable', 'numeric', 'between:0,150'],
            'spike_reach_cm' => ['nullable', 'numeric', 'between:120,400'],
            'block_reach_cm' => ['nullable', 'numeric', 'between:120,400'],
            'agility_seconds' => ['nullable', 'numeric', 'between:1,60'],
            'coach_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        PerformanceAssessment::create([...$data, 'club_id' => $request->user()->club_id, 'athlete_id' => $athlete->id, 'recorded_by' => $request->user()->id]);
        return back()->with('success', 'ارزیابی ورزشی ثبت شد و نمودار پیشرفت به‌روزرسانی گردید.');
    }

    private function historyResponse(Request $request, Athlete $athlete, string $page): Response
    {
        $athlete->load('teams:id,name');
        $assessments = $athlete->performanceAssessments()->with('recorder:id,name')->orderByDesc('assessment_date')->get();
        $latest = $assessments->first();
        $previous = $assessments->skip(1)->first();
        $metrics = ['height_cm', 'weight_kg', 'vertical_jump_cm', 'standing_reach_cm', 'spike_reach_cm', 'block_reach_cm', 'agility_seconds'];

        return Inertia::render($page, [
            'athlete' => ['id' => $athlete->id, 'name' => $athlete->full_name, 'membership_number' => $athlete->membership_number, 'team' => $athlete->teams->first()?->name, 'position' => $athlete->position, 'jersey_number' => $athlete->jersey_number],
            'assessments' => $assessments->map(fn (PerformanceAssessment $assessment) => $this->assessmentData($assessment)),
            'progress' => collect($metrics)->mapWithKeys(fn (string $metric) => [$metric => $latest?->{$metric} !== null && $previous?->{$metric} !== null ? round($latest->{$metric} - $previous->{$metric}, 1) : null]),
            'canRecord' => $request->user()->hasRole(UserRole::ClubAdmin, UserRole::Coach),
        ]);
    }

    private function athleteRow(Athlete $athlete): array
    {
        $latest = $athlete->latestAssessment;
        return [
            'id' => $athlete->id, 'name' => $athlete->full_name, 'membership_number' => $athlete->membership_number,
            'team' => $athlete->teams->first()?->name, 'position' => $athlete->position, 'number' => $athlete->jersey_number,
            'last_assessment' => $latest ? $this->assessmentData($latest) : null,
            'needs_assessment' => ! $latest || $latest->assessment_date->lt(now()->subDays(60)),
        ];
    }

    private function assessmentData(PerformanceAssessment $assessment): array
    {
        return [
            'id' => $assessment->id, 'date' => $assessment->assessment_date->format('Y-m-d'),
            'height_cm' => $assessment->height_cm, 'weight_kg' => $assessment->weight_kg,
            'standing_reach_cm' => $assessment->standing_reach_cm, 'vertical_jump_cm' => $assessment->vertical_jump_cm,
            'spike_reach_cm' => $assessment->spike_reach_cm, 'block_reach_cm' => $assessment->block_reach_cm,
            'agility_seconds' => $assessment->agility_seconds, 'coach_notes' => $assessment->coach_notes,
            'recorder' => $assessment->recorder?->name,
        ];
    }

    private function authorizeVisible(Request $request, Athlete $athlete): void
    {
        abort_unless(Athlete::query()->visibleTo($request->user())->whereKey($athlete)->exists(), 404);
    }

    private function availableTeams(Request $request)
    {
        $user = $request->user();
        return Team::query()->where('club_id', $user->club_id)->where('is_active', true)
            ->when($user->hasRole(UserRole::Coach), fn (Builder $query) => $query->whereHas('coaches', fn (Builder $coach) => $coach->whereKey($user->id)))
            ->orderBy('name')->get();
    }
}
