<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Athlete;
use App\Models\AttendanceRecord;
use App\Models\Invoice;
use App\Models\Team;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return match ($request->user()->role) {
            UserRole::Coach => $this->coach($request),
            UserRole::Finance => app(FinanceController::class)->index($request),
            UserRole::Athlete, UserRole::Guardian => $this->athlete($request),
            default => $this->manager($request),
        };
    }

    public function manager(Request $request): Response
    {
        $clubId = $request->user()->club_id;
        $monthRecords = AttendanceRecord::query()->whereHas('trainingSession', fn (Builder $query) => $query->where('club_id', $clubId)->whereBetween('session_date', [now()->startOfMonth(), now()->endOfMonth()]))->where('status', '!=', 'unmarked');
        $recordCount = (clone $monthRecords)->count();
        $presentCount = (clone $monthRecords)->whereIn('status', ['present', 'late'])->count();

        $todaySessions = TrainingSession::query()->where('club_id', $clubId)->whereDate('session_date', today())
            ->with(['team:id,name,color', 'coach:id,name'])->withCount('attendanceRecords')->orderBy('starts_at')->get();

        $teamRates = Team::query()->where('club_id', $clubId)->where('is_active', true)
            ->withCount([
                'trainingSessions as marked_count' => fn (Builder $query) => $query->whereHas('attendanceRecords', fn (Builder $records) => $records->where('status', '!=', 'unmarked')),
            ])->get()->map(function (Team $team): array {
                $records = AttendanceRecord::query()->whereHas('trainingSession', fn (Builder $query) => $query->where('team_id', $team->id))->where('status', '!=', 'unmarked');
                $total = (clone $records)->count();
                $present = (clone $records)->whereIn('status', ['present', 'late'])->count();
                return ['name' => $team->name, 'rate' => $total ? (int) round(($present / $total) * 100) : 0];
            });

        return Inertia::render('Dashboard', [
            'stats' => [
                'athletes' => Athlete::query()->where('club_id', $clubId)->where('is_active', true)->count(),
                'teams' => Team::query()->where('club_id', $clubId)->where('is_active', true)->count(),
                'today_sessions' => $todaySessions->count(),
                'attendance_rate' => $recordCount ? (int) round(($presentCount / $recordCount) * 100) : 0,
            ],
            'todaySessions' => $todaySessions->map(fn (TrainingSession $session) => [
                'id' => $session->id, 'time' => substr($session->starts_at, 0, 5),
                'team' => $session->team->name, 'coach' => $session->coach?->name,
                'hall' => $session->location, 'athletes_count' => $session->attendance_records_count,
                'color' => $session->team->color,
            ]),
            'alerts' => [
                ['title' => TrainingSession::query()->where('club_id', $clubId)->where('status', 'scheduled')->whereDate('session_date', '<=', today())->count().' جلسه نیازمند ثبت حضور است', 'meta' => 'لیست بازیکنان هنوز کامل ثبت یا نهایی نشده است', 'level' => 'urgent', 'href' => '/attendance'],
                ['title' => Athlete::query()->where('club_id', $clubId)->whereDoesntHave('guardians')->count().' ورزشکار سرپرست متصل ندارند', 'meta' => 'اطلاعات تماس خانواده را در پرونده تکمیل کنید', 'level' => 'normal', 'href' => '/athletes'],
                ['title' => Invoice::query()->where('club_id', $clubId)->whereIn('status', ['unpaid', 'partial'])->whereDate('due_date', '<', today())->count().' صورتحساب سررسید گذشته است', 'meta' => 'مانده شهریه‌ها در پنل مالی قابل پیگیری است', 'level' => 'urgent', 'href' => '/finance'],
            ],
            'teamRates' => $teamRates,
        ]);
    }

    public function coach(Request $request): Response
    {
        $user = $request->user();
        $coach = $user->role === UserRole::ClubAdmin
            ? User::query()->where('club_id', $user->club_id)->where('role', UserRole::Coach)->orderBy('id')->firstOrFail()
            : $user;

        $teams = $coach->assignedTeams()
            ->withCount('athletes')
            ->orderByDesc('coach_assignments.is_head_coach')
            ->get();

        $athletes = Athlete::query()
            ->visibleTo($coach)
            ->with('teams:id,name')
            ->orderBy('last_name')
            ->limit(12)
            ->get()
            ->map(fn (Athlete $athlete) => [
                'id' => $athlete->id,
                'name' => $athlete->full_name,
                'number' => $athlete->jersey_number,
                'position' => $athlete->position,
                'team' => $athlete->teams->first()?->name,
            ]);

        return Inertia::render('Panels/Coach', [
            'coach' => ['name' => $coach->name],
            'teams' => $teams->map(fn ($team) => [
                'id' => $team->id,
                'name' => $team->name,
                'athletes_count' => $team->athletes_count,
                'is_head_coach' => (bool) $team->pivot->is_head_coach,
            ]),
            'athletes' => $athletes,
        ]);
    }

    public function athlete(Request $request): Response
    {
        $user = $request->user();
        $visibleAthletes = Athlete::query()->visibleTo($user)->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'photo_path']);
        $selectedId = $request->integer('athlete');
        $athlete = Athlete::query()->visibleTo($user)
            ->when($selectedId, fn (Builder $query) => $query->whereKey($selectedId))
            ->with(['teams:id,name,color', 'teams.coaches:id,name', 'latestAssessment'])
            ->first();
        if (! $athlete && $selectedId) {
            $athlete = Athlete::query()->visibleTo($user)
                ->with(['teams:id,name,color', 'teams.coaches:id,name', 'latestAssessment'])
                ->first();
        }
        $invoice = $athlete ? Invoice::query()->where('athlete_id', $athlete->id)
            ->withSum(['payments as paid_amount' => fn (Builder $query) => $query->where('status', 'successful')], 'amount')
            ->where('status', '!=', 'paid')->orderBy('due_date')->first() : null;
        $teamIds = $athlete?->teams->pluck('id') ?? collect();
        $nextSession = $athlete ? TrainingSession::query()->whereIn('team_id', $teamIds)->where('status', 'scheduled')->whereDate('session_date', '>=', today())->orderBy('session_date')->orderBy('starts_at')->first() : null;
        $attendance = $athlete?->attendanceRecords()->with('trainingSession:id,session_date')->where('status', '!=', 'unmarked')->latest('marked_at')->limit(12)->get() ?? collect();
        $attendanceTotal = $athlete?->attendanceRecords()->where('status', '!=', 'unmarked')->count() ?? 0;
        $attendancePresent = $athlete?->attendanceRecords()->whereIn('status', ['present', 'late'])->count() ?? 0;
        $previousAssessment = $athlete?->performanceAssessments()->when($athlete?->latestAssessment, fn (Builder $query) => $query->where('id', '!=', $athlete->latestAssessment->id))->latest('assessment_date')->first();

        return Inertia::render('Panels/Athlete', [
            'viewer' => ['mode' => $user->role->value, 'name' => $user->name],
            'profiles' => $visibleAthletes->map(fn (Athlete $profile) => ['id' => $profile->id, 'name' => $profile->full_name, 'photo_url' => $profile->photo_path ? Storage::disk('public')->url($profile->photo_path) : null]),
            'athlete' => $athlete ? [
                'id' => $athlete->id,
                'name' => $athlete->full_name,
                'initials' => mb_substr($athlete->first_name, 0, 1).mb_substr($athlete->last_name, 0, 1),
                'membership_number' => $athlete->membership_number,
                'jersey_number' => $athlete->jersey_number,
                'position' => $athlete->position,
                'team' => $athlete->teams->first()?->name,
                'is_active' => $athlete->is_active,
                'photo_url' => $athlete->photo_path ? Storage::disk('public')->url($athlete->photo_path) : null,
                'team_color' => $athlete->teams->first()?->color,
                'coach' => $athlete->teams->first()?->coaches->first()?->name,
                'attendance_rate' => $attendanceTotal ? (int) round(($attendancePresent / $attendanceTotal) * 100) : 0,
                'sessions_count' => $attendanceTotal,
                'streak' => $attendance->takeUntil(fn ($record) => ! in_array($record->status, ['present', 'late'], true))->count(),
                'recent_attendance' => $attendance->take(8)->reverse()->values()->map(fn ($record) => ['status' => $record->status, 'date' => $record->trainingSession?->session_date?->format('Y-m-d')]),
                'assessment' => $athlete->latestAssessment ? [
                    'date' => $athlete->latestAssessment->assessment_date->format('Y-m-d'),
                    'height_cm' => $athlete->latestAssessment->height_cm,
                    'vertical_jump_cm' => $athlete->latestAssessment->vertical_jump_cm,
                    'spike_reach_cm' => $athlete->latestAssessment->spike_reach_cm,
                    'block_reach_cm' => $athlete->latestAssessment->block_reach_cm,
                    'jump_change' => $athlete->latestAssessment->vertical_jump_cm !== null && $previousAssessment?->vertical_jump_cm !== null ? round($athlete->latestAssessment->vertical_jump_cm - $previousAssessment->vertical_jump_cm, 1) : null,
                    'coach_notes' => $athlete->latestAssessment->coach_notes,
                ] : null,
            ] : null,
            'nextSession' => $nextSession ? ['date' => $nextSession->session_date->format('Y-m-d'), 'starts_at' => substr($nextSession->starts_at, 0, 5), 'location' => $nextSession->location, 'title' => $nextSession->title] : null,
            'invoice' => $invoice ? [
                'title' => $invoice->title,
                'outstanding' => max(0, $invoice->payableAmount() - (int) ($invoice->paid_amount ?? 0)),
                'due_date' => $invoice->due_date->format('Y-m-d'),
            ] : null,
            'paymentHref' => $user->hasRole(UserRole::ClubAdmin) ? '/finance' : '/my-payments',
            'performanceHref' => $user->hasRole(UserRole::ClubAdmin) && $athlete ? '/assessments/'.$athlete->id : '/my-performance',
        ]);
    }
}
