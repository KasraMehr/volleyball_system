<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Athlete;
use App\Models\AttendanceRecord;
use App\Models\Team;
use App\Models\TrainingSession;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $teams = $this->availableTeams($request);
        $teamIds = $teams->pluck('id');

        $sessions = TrainingSession::query()
            ->where('club_id', $user->club_id)
            ->whereIn('team_id', $teamIds)
            ->with(['team:id,name,color', 'coach:id,name'])
            ->withCount([
                'attendanceRecords',
                'attendanceRecords as present_count' => fn (Builder $query) => $query->whereIn('status', ['present', 'late']),
                'attendanceRecords as absent_count' => fn (Builder $query) => $query->where('status', 'absent'),
                'attendanceRecords as unmarked_count' => fn (Builder $query) => $query->where('status', 'unmarked'),
            ])
            ->when($request->integer('team'), fn (Builder $query, int $teamId) => $query->where('team_id', $teamId))
            ->when($request->string('period')->toString(), function (Builder $query, string $period): void {
                match ($period) {
                    'today' => $query->whereDate('session_date', today()),
                    'upcoming' => $query->whereDate('session_date', '>=', today()),
                    'past' => $query->whereDate('session_date', '<', today()),
                    default => null,
                };
            })
            ->orderByDesc('session_date')->orderByDesc('starts_at')
            ->paginate(10)->withQueryString()
            ->through(fn (TrainingSession $session) => $this->sessionData($session));

        $monthSessions = TrainingSession::query()->where('club_id', $user->club_id)->whereIn('team_id', $teamIds)
            ->whereBetween('session_date', [now()->startOfMonth(), now()->endOfMonth()])->count();
        $monthRecords = AttendanceRecord::query()->whereHas('trainingSession', fn (Builder $query) => $query->where('club_id', $user->club_id)->whereIn('team_id', $teamIds)->whereBetween('session_date', [now()->startOfMonth(), now()->endOfMonth()]));
        $marked = (clone $monthRecords)->where('status', '!=', 'unmarked')->count();
        $present = (clone $monthRecords)->whereIn('status', ['present', 'late'])->count();

        $atRisk = Athlete::query()->visibleTo($user)
            ->whereHas('teams', fn (Builder $query) => $query->whereIn('teams.id', $teamIds))
            ->with('teams:id,name')
            ->withCount(['attendanceRecords as absent_count' => fn (Builder $query) => $query
                ->where('status', 'absent')
                ->whereHas('trainingSession', fn (Builder $session) => $session->whereDate('session_date', '>=', now()->subDays(30)))])
            ->orderByDesc('absent_count')->limit(5)->get()
            ->filter(fn (Athlete $athlete) => $athlete->absent_count > 0)
            ->values()->map(fn (Athlete $athlete) => [
                'id' => $athlete->id,
                'name' => $athlete->full_name,
                'team' => $athlete->teams->first()?->name,
                'absent_count' => $athlete->absent_count,
            ]);

        return Inertia::render('Attendance/Index', [
            'sessions' => $sessions,
            'teams' => $teams->map(fn (Team $team) => ['id' => $team->id, 'name' => $team->name, 'color' => $team->color]),
            'venues' => Venue::query()->where('club_id', $user->club_id)->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['team', 'period']),
            'summary' => [
                'month_sessions' => $monthSessions,
                'attendance_rate' => $marked ? (int) round(($present / $marked) * 100) : 0,
                'open_sessions' => TrainingSession::query()->where('club_id', $user->club_id)->whereIn('team_id', $teamIds)->where('status', 'scheduled')->whereDate('session_date', '<=', today())->count(),
            ],
            'atRisk' => $atRisk,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $teamIds = $this->availableTeams($request)->pluck('id')->all();
        $data = $request->validate([
            'team_id' => ['required', Rule::in($teamIds)],
            'title' => ['required', 'string', 'max:120'],
            'session_date' => ['required', 'date'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['nullable', 'date_format:H:i', 'after:starts_at'],
            'location' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $session = DB::transaction(function () use ($request, $data): TrainingSession {
            $session = TrainingSession::create([
                ...$data,
                'club_id' => $request->user()->club_id,
                'coach_id' => $request->user()->hasRole(UserRole::Coach) ? $request->user()->id : null,
                'status' => 'scheduled',
            ]);
            $athleteIds = Team::findOrFail($data['team_id'])->athletes()->where('athletes.is_active', true)->pluck('athletes.id');
            $session->attendanceRecords()->createMany($athleteIds->map(fn (int $id) => ['athlete_id' => $id, 'status' => 'unmarked'])->all());
            return $session;
        });

        return redirect()->route('attendance.show', $session)->with('success', 'جلسه ساخته شد؛ حضور بازیکنان را ثبت کنید.');
    }

    public function show(Request $request, TrainingSession $session): Response
    {
        $this->authorizeSession($request, $session);
        $session->load(['team:id,name,color', 'coach:id,name', 'attendanceRecords.athlete.teams:id,name']);

        return Inertia::render('Attendance/Show', [
            'session' => [
                ...$this->sessionData($session),
                'notes' => $session->notes,
                'records' => $session->attendanceRecords->sortBy(fn (AttendanceRecord $record) => $record->athlete->last_name)->values()->map(fn (AttendanceRecord $record) => [
                    'id' => $record->id,
                    'athlete_id' => $record->athlete_id,
                    'name' => $record->athlete->full_name,
                    'number' => $record->athlete->jersey_number,
                    'position' => $record->athlete->position,
                    'status' => $record->status,
                    'arrival_time' => $record->arrival_time,
                    'note' => $record->note,
                ]),
            ],
        ]);
    }

    public function mark(Request $request, TrainingSession $session): RedirectResponse
    {
        $this->authorizeSession($request, $session);
        abort_if($session->status === 'canceled', 422, 'جلسه لغوشده قابل ثبت نیست.');
        $data = $request->validate([
            'records' => ['required', 'array'],
            'records.*.athlete_id' => ['required', 'integer'],
            'records.*.status' => ['required', 'in:present,absent,late,excused,unmarked'],
            'records.*.arrival_time' => ['nullable', 'date_format:H:i'],
            'records.*.note' => ['nullable', 'string', 'max:250'],
        ]);

        $allowedAthleteIds = $session->attendanceRecords()->pluck('athlete_id');
        DB::transaction(function () use ($request, $session, $data, $allowedAthleteIds): void {
            foreach ($data['records'] as $record) {
                abort_unless($allowedAthleteIds->contains((int) $record['athlete_id']), 422);
                $session->attendanceRecords()->where('athlete_id', $record['athlete_id'])->update([
                    'status' => $record['status'],
                    'arrival_time' => $record['status'] === 'late' ? ($record['arrival_time'] ?? null) : null,
                    'note' => $record['note'] ?? null,
                    'marked_by' => $request->user()->id,
                    'marked_at' => now(),
                ]);
            }
        });
        return back()->with('success', 'حضور و غیاب ذخیره شد.');
    }

    public function complete(Request $request, TrainingSession $session): RedirectResponse
    {
        $this->authorizeSession($request, $session);
        abort_if($session->attendanceRecords()->where('status', 'unmarked')->exists(), 422, 'وضعیت همه ورزشکاران را مشخص کنید.');
        $session->update(['status' => 'completed']);
        return back()->with('success', 'جلسه بسته شد و در گزارش‌ها ثبت گردید.');
    }

    private function availableTeams(Request $request)
    {
        $user = $request->user();
        return Team::query()->where('club_id', $user->club_id)->where('is_active', true)
            ->when($user->hasRole(UserRole::Coach), fn (Builder $query) => $query->whereHas('coaches', fn (Builder $coach) => $coach->whereKey($user->id)))
            ->orderBy('name')->get();
    }

    private function authorizeSession(Request $request, TrainingSession $session): void
    {
        abort_unless($session->club_id === $request->user()->club_id && $this->availableTeams($request)->contains('id', $session->team_id), 404);
    }

    private function sessionData(TrainingSession $session): array
    {
        return [
            'id' => $session->id,
            'title' => $session->title,
            'session_date' => $session->session_date->format('Y-m-d'),
            'starts_at' => substr($session->starts_at, 0, 5),
            'ends_at' => $session->ends_at ? substr($session->ends_at, 0, 5) : null,
            'location' => $session->location,
            'status' => $session->status,
            'team' => ['id' => $session->team->id, 'name' => $session->team->name, 'color' => $session->team->color],
            'coach' => $session->coach?->name,
            'records_count' => $session->attendance_records_count ?? $session->attendanceRecords->count(),
            'present_count' => $session->present_count ?? $session->attendanceRecords->whereIn('status', ['present', 'late'])->count(),
            'absent_count' => $session->absent_count ?? $session->attendanceRecords->where('status', 'absent')->count(),
            'unmarked_count' => $session->unmarked_count ?? $session->attendanceRecords->where('status', 'unmarked')->count(),
        ];
    }
}
