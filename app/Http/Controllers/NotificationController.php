<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\ClubNotification;
use App\Models\Invoice;
use App\Models\Team;
use App\Models\TrainingSession;
use App\Models\User;
use App\Models\SmsMessage;
use App\Jobs\SendSmsMessage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $notifications = $user->clubNotifications()->with('sender:id,name')->latest('sent_at')->paginate(12)->withQueryString()
            ->through(fn (ClubNotification $notification) => $this->notificationData($notification));

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
            'summary' => [
                'unread' => $user->clubNotifications()->whereNull('read_at')->count(),
                'total' => $user->clubNotifications()->count(),
                'payment' => $user->clubNotifications()->where('type', 'payment_reminder')->count(),
            ],
            'permissions' => [
                'announce' => $user->hasRole(UserRole::ClubAdmin),
                'paymentReminder' => $user->hasRole(UserRole::ClubAdmin, UserRole::Finance),
                'trainingReminder' => $user->hasRole(UserRole::ClubAdmin, UserRole::Coach),
                'smsMonitor' => $user->hasRole(UserRole::ClubAdmin, UserRole::Finance),
            ],
            'teams' => $this->availableTeams($request)->map(fn (Team $team) => ['id' => $team->id, 'name' => $team->name]),
            'roles' => collect(UserRole::cases())->map(fn (UserRole $role) => ['value' => $role->value, 'label' => $role->label()]),
            'automation' => [
                'overdueInvoices' => Invoice::query()->where('club_id', $user->club_id)->whereIn('status', ['unpaid', 'partial'])->whereDate('due_date', '<', today())->count(),
                'upcomingSessions' => TrainingSession::query()->where('club_id', $user->club_id)->whereIn('team_id', $this->availableTeams($request)->pluck('id'))->where('status', 'scheduled')->whereBetween('session_date', [today(), today()->addDays(3)])->count(),
            ],
            'sms' => $user->hasRole(UserRole::ClubAdmin, UserRole::Finance) ? [
                'enabled' => config('sms.enabled'),
                'provider' => config('sms.default'),
                'queued' => SmsMessage::query()->where('club_id', $user->club_id)->where('status', 'queued')->count(),
                'sent' => SmsMessage::query()->where('club_id', $user->club_id)->where('status', 'sent')->count(),
                'failed' => SmsMessage::query()->where('club_id', $user->club_id)->where('status', 'failed')->count(),
                'recent' => SmsMessage::query()->where('club_id', $user->club_id)->with('user:id,name')->latest()->limit(8)->get()->map(fn (SmsMessage $sms) => [
                    'id' => $sms->id, 'recipient' => $sms->recipient, 'user' => $sms->user?->name, 'status' => $sms->status,
                    'attempts' => $sms->attempts, 'provider' => $sms->provider, 'created_at' => $sms->created_at->format('Y-m-d H:i'), 'error' => $sms->last_error,
                ]),
            ] : null,
        ]);
    }

    public function announce(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasRole(UserRole::ClubAdmin), 403);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'], 'message' => ['required', 'string', 'max:1000'],
            'audience' => ['required', 'in:all,role,team'],
            'role' => ['nullable', Rule::enum(UserRole::class)],
            'team_id' => ['nullable', Rule::exists('teams', 'id')->where('club_id', $request->user()->club_id)],
        ]);

        $recipients = User::query()->where('club_id', $request->user()->club_id)->where('is_active', true)
            ->when($data['audience'] === 'role', fn (Builder $query) => $query->where('role', $data['role']))
            ->when($data['audience'] === 'team', fn (Builder $query) => $query->whereIn('id', $this->teamRecipientIds((int) $data['team_id'])))
            ->pluck('id');
        $this->deliver($request, $recipients, 'announcement', $data['title'], $data['message'], null, ['audience' => $data['audience']]);
        return back()->with('success', "اعلان برای {$recipients->count()} کاربر ارسال شد.");
    }

    public function paymentReminders(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasRole(UserRole::ClubAdmin, UserRole::Finance), 403);
        $invoices = Invoice::query()->where('club_id', $request->user()->club_id)->whereIn('status', ['unpaid', 'partial'])->whereDate('due_date', '<', today())->with(['athlete.user', 'athlete.guardians'])->get();
        $count = 0;
        foreach ($invoices as $invoice) {
            $recipientIds = collect([$invoice->athlete->user_id])->merge($invoice->athlete->guardians->pluck('id'))->filter()->unique();
            if ($recipientIds->isEmpty()) { continue; }
            $remaining = max(0, $invoice->payableAmount() - $invoice->paidAmount());
            $this->deliver($request, $recipientIds, 'payment_reminder', 'یادآوری شهریه معوق', "صورتحساب {$invoice->title} دارای ".number_format($remaining).' تومان مانده است.', '/my-payments', ['invoice_id' => $invoice->id]);
            $count += $recipientIds->count();
        }
        return back()->with('success', "{$count} یادآوری شهریه در صندوق کاربران قرار گرفت.");
    }

    public function trainingReminders(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasRole(UserRole::ClubAdmin, UserRole::Coach), 403);
        $teamIds = $this->availableTeams($request)->pluck('id');
        $sessions = TrainingSession::query()->where('club_id', $request->user()->club_id)->whereIn('team_id', $teamIds)->where('status', 'scheduled')->whereBetween('session_date', [today(), today()->addDays(3)])->with(['team.athletes.user', 'team.athletes.guardians'])->get();
        $count = 0;
        foreach ($sessions as $session) {
            $recipientIds = $session->team->athletes->flatMap(fn ($athlete) => collect([$athlete->user_id])->merge($athlete->guardians->pluck('id')))->filter()->unique();
            $message = "تمرین {$session->team->name} در تاریخ {$session->session_date->format('Y-m-d')} ساعت ".substr($session->starts_at, 0, 5)." در {$session->location} برگزار می‌شود.";
            $this->deliver($request, $recipientIds, 'training_reminder', 'یادآوری جلسه تمرین', $message, null, ['session_id' => $session->id]);
            $count += $recipientIds->count();
        }
        return back()->with('success', "{$count} یادآوری تمرین در صندوق کاربران قرار گرفت.");
    }

    public function read(Request $request, ClubNotification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 404);
        $notification->update(['read_at' => $notification->read_at ?? now()]);
        return $notification->action_url ? redirect($notification->action_url) : back();
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->clubNotifications()->whereNull('read_at')->update(['read_at' => now()]);
        return back()->with('success', 'همه اعلان‌ها خوانده‌شده علامت خوردند.');
    }

    public function retrySms(Request $request, SmsMessage $sms): RedirectResponse
    {
        abort_unless($request->user()->hasRole(UserRole::ClubAdmin, UserRole::Finance) && $sms->club_id === $request->user()->club_id, 403);
        abort_unless($sms->status === 'failed', 422);
        $sms->update(['status' => 'queued', 'failed_at' => null, 'last_error' => null]);
        SendSmsMessage::dispatch($sms->id);
        return back()->with('success', 'پیامک دوباره در صف ارسال قرار گرفت.');
    }

    private function deliver(Request $request, Collection $recipients, string $type, string $title, string $message, ?string $actionUrl, array $metadata): void
    {
        $users = User::query()->whereIn('id', $recipients->unique())->get(['id', 'club_id', 'phone']);
        DB::transaction(function () use ($request, $users, $type, $title, $message, $actionUrl, $metadata): void {
            foreach ($users as $user) {
                $notification = ClubNotification::create(['club_id' => $request->user()->club_id, 'user_id' => $user->id, 'sender_id' => $request->user()->id, 'type' => $type, 'title' => $title, 'message' => $message, 'action_url' => $actionUrl, 'channel' => 'in_app', 'delivery_status' => 'delivered', 'metadata' => $metadata, 'sent_at' => now()]);
                if (! config('sms.enabled') || blank($user->phone)) continue;
                $sms = SmsMessage::create(['club_id' => $user->club_id, 'user_id' => $user->id, 'club_notification_id' => $notification->id, 'provider' => config('sms.default'), 'recipient' => $user->phone, 'message' => $title."\n".$message, 'status' => 'queued', 'queued_at' => now()]);
                SendSmsMessage::dispatch($sms->id)->afterCommit();
            }
        });
    }

    private function teamRecipientIds(int $teamId): Collection
    {
        $team = Team::query()->with(['coaches:id', 'athletes.user:id', 'athletes.guardians:id'])->findOrFail($teamId);
        return $team->coaches->pluck('id')->merge($team->athletes->pluck('user_id'))->merge($team->athletes->flatMap(fn ($athlete) => $athlete->guardians->pluck('id')))->filter()->unique()->values();
    }

    private function availableTeams(Request $request): Collection
    {
        $user = $request->user();
        return Team::query()->where('club_id', $user->club_id)->where('is_active', true)->when($user->hasRole(UserRole::Coach), fn (Builder $query) => $query->whereHas('coaches', fn (Builder $coach) => $coach->whereKey($user->id)))->orderBy('name')->get();
    }

    private function notificationData(ClubNotification $notification): array
    {
        return ['id' => $notification->id, 'type' => $notification->type, 'title' => $notification->title, 'message' => $notification->message, 'action_url' => $notification->action_url, 'sender' => $notification->sender?->name, 'sent_at' => $notification->sent_at?->format('Y-m-d H:i'), 'read' => (bool) $notification->read_at];
    }
}
