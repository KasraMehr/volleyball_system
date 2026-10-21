<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $clubId = $request->user()->club_id;
        $query = ActivityLog::query()->where('club_id', $clubId)->with('user:id,name,role')
            ->when($request->integer('user'), fn (Builder $query, int $userId) => $query->where('user_id', $userId))
            ->when($request->string('action')->toString(), fn (Builder $query, string $action) => $query->where('action', $action))
            ->when($request->date('from'), fn (Builder $query, $from) => $query->whereDate('occurred_at', '>=', $from))
            ->when($request->date('to'), fn (Builder $query, $to) => $query->whereDate('occurred_at', '<=', $to));

        $logs = (clone $query)->latest('occurred_at')->paginate(20)->withQueryString()->through(fn (ActivityLog $log) => [
            'id' => $log->id, 'action' => $log->action, 'route_name' => $log->route_name,
            'description' => $log->description, 'method' => $log->method,
            'subject_type' => $log->subject_type, 'subject_id' => $log->subject_id,
            'ip_address' => $log->ip_address, 'occurred_at' => $log->occurred_at->format('Y-m-d H:i:s'),
            'user' => $log->user ? ['name' => $log->user->name, 'role' => $log->user->role->label()] : null,
        ]);

        return Inertia::render('ActivityLogs/Index', [
            'logs' => $logs,
            'users' => User::query()->where('club_id', $clubId)->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['user', 'action', 'from', 'to']),
            'actions' => [
                ['value' => 'create', 'label' => 'ایجاد'], ['value' => 'update', 'label' => 'ویرایش'],
                ['value' => 'status_change', 'label' => 'تغییر وضعیت'], ['value' => 'attendance', 'label' => 'حضور و غیاب'],
                ['value' => 'payment', 'label' => 'پرداخت'], ['value' => 'notification', 'label' => 'اعلان'],
                ['value' => 'login', 'label' => 'ورود'], ['value' => 'logout', 'label' => 'خروج'],
            ],
            'summary' => [
                'today' => ActivityLog::query()->where('club_id', $clubId)->whereDate('occurred_at', today())->count(),
                'actors' => ActivityLog::query()->where('club_id', $clubId)->whereDate('occurred_at', '>=', today()->subDays(30))->distinct()->count('user_id'),
                'sensitive' => ActivityLog::query()->where('club_id', $clubId)->whereIn('action', ['payment', 'status_change'])->whereDate('occurred_at', '>=', today()->subDays(30))->count(),
            ],
        ]);
    }
}
