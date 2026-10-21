<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\AttendanceRecord;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PerformanceAssessment;
use App\Models\Team;
use App\Models\TrainingSession;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        [$from, $to] = $this->dateRange($request);
        $teams = $this->availableTeams($request);
        $teamIds = $this->selectedTeamIds($request, $teams);
        $user = $request->user();
        $canAttendance = $user->hasRole(UserRole::ClubAdmin, UserRole::Coach);
        $canFinance = $user->hasRole(UserRole::ClubAdmin, UserRole::Finance);
        $canPerformance = $user->hasRole(UserRole::ClubAdmin, UserRole::Coach);

        $attendance = $canAttendance ? $this->attendanceSummary($user->club_id, $teamIds, $from, $to) : null;
        $finance = $canFinance ? $this->financeSummary($user->club_id, $teamIds, $from, $to) : null;
        $performance = $canPerformance ? $this->performanceSummary($user->club_id, $teamIds, $from, $to) : null;

        return Inertia::render('Reports/Index', [
            'filters' => ['from' => $from->toDateString(), 'to' => $to->toDateString(), 'team' => $request->integer('team') ?: null],
            'teams' => $teams->map(fn (Team $team) => ['id' => $team->id, 'name' => $team->name]),
            'permissions' => ['attendance' => $canAttendance, 'finance' => $canFinance, 'performance' => $canPerformance],
            'summary' => ['attendance' => $attendance, 'finance' => $finance, 'performance' => $performance],
            'teamBreakdown' => $teams->filter(fn (Team $team) => in_array($team->id, $teamIds, true))->map(function (Team $team) use ($user, $from, $to, $canAttendance, $canFinance, $canPerformance): array {
                $attendance = $canAttendance ? $this->attendanceSummary($user->club_id, [$team->id], $from, $to) : null;
                $finance = $canFinance ? $this->financeSummary($user->club_id, [$team->id], $from, $to) : null;
                $performance = $canPerformance ? $this->performanceSummary($user->club_id, [$team->id], $from, $to) : null;

                return [
                    'id' => $team->id,
                    'name' => $team->name,
                    'athletes' => $team->athletes()->where('athletes.is_active', true)->count(),
                    'attendance_rate' => $attendance['rate'] ?? null,
                    'collected' => $finance['collected'] ?? null,
                    'outstanding' => $finance['outstanding'] ?? null,
                    'avg_jump' => $performance['avg_jump'] ?? null,
                    'assessments' => $performance['count'] ?? null,
                ];
            })->values(),
            'alerts' => [
                'unmarked_sessions' => $canAttendance ? TrainingSession::query()->where('club_id', $user->club_id)->whereIn('team_id', $teamIds)->whereBetween('session_date', [$from, $to])->where('status', 'scheduled')->whereDate('session_date', '<=', today())->count() : null,
                'overdue_invoices' => $canFinance ? $this->invoiceQuery($user->club_id, $teamIds)->whereIn('status', ['unpaid', 'partial'])->whereDate('due_date', '<', today())->count() : null,
                'stale_assessments' => $canPerformance ? $this->athleteScope($user->club_id, $teamIds)->where(fn (Builder $query) => $query->whereDoesntHave('performanceAssessments')->orWhereDoesntHave('performanceAssessments', fn (Builder $assessment) => $assessment->whereDate('assessment_date', '>=', today()->subDays(60))))->count() : null,
            ],
        ]);
    }

    public function export(Request $request, string $type): StreamedResponse
    {
        $report = $this->reportData($request, $type);
        $filename = "{$type}-report-".today()->toDateString().'.csv';

        return response()->streamDownload(function () use ($report): void {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, $report['headers']);
            foreach ($report['rows'] as $row) {
                fputcsv($stream, array_values($row));
            }
            fclose($stream);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function print(Request $request, string $type): Response
    {
        $report = $this->reportData($request, $type);

        return Inertia::render('Reports/Print', [
            ...$report,
            'club' => $request->user()->club?->name,
            'generatedAt' => now()->format('Y-m-d H:i'),
        ]);
    }

    private function reportData(Request $request, string $type): array
    {
        abort_unless(in_array($type, ['attendance', 'finance', 'performance'], true), 404);
        $user = $request->user();
        abort_if($type === 'finance' && ! $user->hasRole(UserRole::ClubAdmin, UserRole::Finance), 403);
        abort_if(in_array($type, ['attendance', 'performance'], true) && ! $user->hasRole(UserRole::ClubAdmin, UserRole::Coach), 403);

        [$from, $to] = $this->dateRange($request);
        $teamIds = $this->selectedTeamIds($request, $this->availableTeams($request));

        if ($type === 'attendance') {
            $headers = ['تاریخ', 'تیم', 'ورزشکار', 'وضعیت', 'ساعت ورود', 'یادداشت'];
            $statusLabels = ['present' => 'حاضر', 'late' => 'تاخیر', 'absent' => 'غایب', 'excused' => 'غیبت موجه', 'unmarked' => 'ثبت نشده'];
            $rows = AttendanceRecord::query()->with(['trainingSession.team:id,name', 'athlete:id,first_name,last_name'])
                ->whereHas('trainingSession', fn (Builder $query) => $query->where('club_id', $user->club_id)->whereIn('team_id', $teamIds)->whereBetween('session_date', [$from, $to]))
                ->get()->sortByDesc(fn (AttendanceRecord $record) => $record->trainingSession->session_date)
                ->map(fn (AttendanceRecord $record) => [
                    $record->trainingSession->session_date->format('Y-m-d'), $record->trainingSession->team->name,
                    $record->athlete->full_name, $statusLabels[$record->status] ?? $record->status,
                    $record->arrival_time ?: '—', $record->note ?: '—',
                ])->values();
            $title = 'گزارش حضور و غیاب';
        } elseif ($type === 'finance') {
            $headers = ['صورتحساب', 'ورزشکار', 'تیم', 'مبلغ قابل پرداخت', 'پرداخت شده', 'مانده', 'سررسید', 'وضعیت'];
            $rows = $this->invoiceQuery($user->club_id, $teamIds)->with(['athlete.teams:id,name'])
                ->withSum(['payments as paid_amount' => fn (Builder $query) => $query->where('status', 'successful')], 'amount')
                ->whereBetween('due_date', [$from, $to])->orderBy('due_date')->get()->map(function (Invoice $invoice): array {
                    $paid = (int) ($invoice->paid_amount ?? 0);
                    $payable = $invoice->payableAmount();
                    $status = $invoice->status !== 'paid' && $invoice->due_date->isPast() ? 'معوق' : match ($invoice->status) { 'paid' => 'تسویه', 'partial' => 'پرداخت ناقص', default => 'پرداخت نشده' };
                    return [$invoice->title, $invoice->athlete->full_name, $invoice->athlete->teams->first()?->name ?: '—', $payable, $paid, max(0, $payable - $paid), $invoice->due_date->format('Y-m-d'), $status];
                });
            $title = 'گزارش شهریه و پرداخت‌ها';
        } else {
            $headers = ['تاریخ', 'ورزشکار', 'تیم', 'قد', 'وزن', 'پرش عمودی', 'دسترسی اسپک', 'دسترسی دفاع', 'چابکی', 'ثبت‌کننده'];
            $rows = PerformanceAssessment::query()->with(['athlete.teams:id,name', 'recorder:id,name'])
                ->where('club_id', $user->club_id)->whereHas('athlete.teams', fn (Builder $query) => $query->whereIn('teams.id', $teamIds))
                ->whereBetween('assessment_date', [$from, $to])->orderByDesc('assessment_date')->get()->map(fn (PerformanceAssessment $assessment) => [
                    $assessment->assessment_date->format('Y-m-d'), $assessment->athlete->full_name, $assessment->athlete->teams->first()?->name ?: '—',
                    $assessment->height_cm ?: '—', $assessment->weight_kg ?: '—', $assessment->vertical_jump_cm ?: '—',
                    $assessment->spike_reach_cm ?: '—', $assessment->block_reach_cm ?: '—', $assessment->agility_seconds ?: '—', $assessment->recorder?->name ?: '—',
                ]);
            $title = 'گزارش رکوردهای ورزشی';
        }

        return ['type' => $type, 'title' => $title, 'headers' => $headers, 'rows' => $rows, 'range' => ['from' => $from->toDateString(), 'to' => $to->toDateString()]];
    }

    private function attendanceSummary(int $clubId, array $teamIds, Carbon $from, Carbon $to): array
    {
        $records = AttendanceRecord::query()->whereHas('trainingSession', fn (Builder $query) => $query->where('club_id', $clubId)->whereIn('team_id', $teamIds)->whereBetween('session_date', [$from, $to]));
        $marked = (clone $records)->where('status', '!=', 'unmarked')->count();
        $attended = (clone $records)->whereIn('status', ['present', 'late'])->count();
        return [
            'sessions' => TrainingSession::query()->where('club_id', $clubId)->whereIn('team_id', $teamIds)->whereBetween('session_date', [$from, $to])->count(),
            'marked' => $marked, 'present' => $attended,
            'absent' => (clone $records)->where('status', 'absent')->count(),
            'excused' => (clone $records)->where('status', 'excused')->count(),
            'rate' => $marked ? (int) round(($attended / $marked) * 100) : 0,
        ];
    }

    private function financeSummary(int $clubId, array $teamIds, Carbon $from, Carbon $to): array
    {
        $invoices = $this->invoiceQuery($clubId, $teamIds)->whereBetween('due_date', [$from, $to]);
        $charged = (int) (clone $invoices)->selectRaw('COALESCE(SUM(amount - discount), 0) AS total')->value('total');
        $collected = (int) Payment::query()->where('club_id', $clubId)->where('status', 'successful')->whereIn('athlete_id', $this->athleteScope($clubId, $teamIds)->select('athletes.id'))->whereBetween('paid_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])->sum('amount');
        $allCharged = (int) $this->invoiceQuery($clubId, $teamIds)->selectRaw('COALESCE(SUM(amount - discount), 0) AS total')->value('total');
        $allPaid = (int) Payment::query()->where('club_id', $clubId)->where('status', 'successful')->whereIn('athlete_id', $this->athleteScope($clubId, $teamIds)->select('athletes.id'))->sum('amount');
        return ['charged' => $charged, 'collected' => $collected, 'outstanding' => max(0, $allCharged - $allPaid), 'rate' => $charged ? (int) round(($collected / $charged) * 100) : 0];
    }

    private function performanceSummary(int $clubId, array $teamIds, Carbon $from, Carbon $to): array
    {
        $query = PerformanceAssessment::query()->where('club_id', $clubId)->whereHas('athlete.teams', fn (Builder $team) => $team->whereIn('teams.id', $teamIds))->whereBetween('assessment_date', [$from, $to]);
        return ['count' => (clone $query)->count(), 'athletes' => (clone $query)->distinct()->count('athlete_id'), 'avg_jump' => round((float) (clone $query)->avg('vertical_jump_cm'), 1), 'avg_spike' => round((float) (clone $query)->avg('spike_reach_cm'), 1)];
    }

    private function invoiceQuery(int $clubId, array $teamIds): Builder
    {
        return Invoice::query()->where('club_id', $clubId)->whereHas('athlete.teams', fn (Builder $query) => $query->whereIn('teams.id', $teamIds));
    }

    private function athleteScope(int $clubId, array $teamIds): Builder
    {
        return \App\Models\Athlete::query()->where('club_id', $clubId)->where('is_active', true)->whereHas('teams', fn (Builder $query) => $query->whereIn('teams.id', $teamIds));
    }

    private function availableTeams(Request $request): Collection
    {
        $user = $request->user();
        return Team::query()->where('club_id', $user->club_id)->where('is_active', true)
            ->when($user->hasRole(UserRole::Coach), fn (Builder $query) => $query->whereHas('coaches', fn (Builder $coach) => $coach->whereKey($user->id)))
            ->withCount('athletes')->orderBy('name')->get();
    }

    private function selectedTeamIds(Request $request, Collection $teams): array
    {
        $allowed = $teams->pluck('id')->map(fn ($id) => (int) $id);
        if ($request->integer('team')) {
            abort_unless($allowed->contains($request->integer('team')), 403);
            return [$request->integer('team')];
        }
        return $allowed->all();
    }

    private function dateRange(Request $request): array
    {
        $data = $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from']]);
        return [Carbon::parse($data['from'] ?? today()->startOfMonth()->toDateString()), Carbon::parse($data['to'] ?? today()->toDateString())];
    }
}
