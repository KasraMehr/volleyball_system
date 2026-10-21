<?php

namespace App\Http\Controllers;

use App\Models\FeePlan;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class FinanceController extends Controller
{
    public function mine(Request $request): Response
    {
        $athleteIds = \App\Models\Athlete::query()->visibleTo($request->user())->pluck('id');
        $invoices = Invoice::query()->where('club_id', $request->user()->club_id)->whereIn('athlete_id', $athleteIds)
            ->with(['athlete.teams:id,name'])
            ->withSum(['payments as paid_amount' => fn (Builder $query) => $query->where('status', 'successful')], 'amount')
            ->orderByDesc('due_date')->get()->map(fn (Invoice $invoice) => $this->invoiceData($invoice));

        return Inertia::render('Finance/MyInvoices', [
            'invoices' => $invoices,
            'summary' => [
                'total' => $invoices->sum('payable'),
                'paid' => $invoices->sum('paid'),
                'outstanding' => $invoices->sum('outstanding'),
            ],
        ]);
    }

    public function index(Request $request): Response
    {
        $clubId = $request->user()->club_id;
        $invoices = Invoice::query()->where('club_id', $clubId)
            ->with(['athlete.teams:id,name'])
            ->withSum(['payments as paid_amount' => fn (Builder $query) => $query->where('status', 'successful')], 'amount')
            ->when($request->string('search')->toString(), fn (Builder $query, string $search) => $query->whereHas('athlete', fn (Builder $athlete) => $athlete->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%")->orWhere('membership_number', 'like', "%{$search}%")))
            ->when($request->integer('team'), fn (Builder $query, int $teamId) => $query->whereHas('athlete.teams', fn (Builder $team) => $team->whereKey($teamId)))
            ->when($request->string('status')->toString(), function (Builder $query, string $status): void {
                if ($status === 'overdue') {
                    $query->whereIn('status', ['unpaid', 'partial'])->whereDate('due_date', '<', today());
                } else {
                    $query->where('status', $status);
                }
            })
            ->orderByRaw("CASE WHEN status = 'paid' THEN 1 ELSE 0 END")
            ->orderBy('due_date')->paginate(12)->withQueryString()
            ->through(fn (Invoice $invoice) => $this->invoiceData($invoice));

        $base = Invoice::query()->where('club_id', $clubId);
        $charged = (clone $base)->sum(DB::raw('amount - discount'));
        $collected = Payment::query()->where('club_id', $clubId)->where('status', 'successful')->sum('amount');
        $manualCollected = Payment::query()->where('club_id', $clubId)->where('status', 'successful')->whereIn('method', ['cash', 'card_transfer', 'pos'])->sum('amount');
        $onlineCollected = Payment::query()->where('club_id', $clubId)->where('status', 'successful')->where('method', 'online')->sum('amount');

        return Inertia::render('Panels/Finance', [
            'invoices' => $invoices,
            'teams' => Team::query()->where('club_id', $clubId)->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['search', 'team', 'status']),
            'summary' => [
                'charged' => (int) $charged,
                'collected' => (int) $collected,
                'outstanding' => max(0, (int) $charged - (int) $collected),
                'collection_rate' => $charged ? (int) round(($collected / $charged) * 100) : 0,
                'overdue_count' => Invoice::query()->where('club_id', $clubId)->whereIn('status', ['unpaid', 'partial'])->whereDate('due_date', '<', today())->count(),
                'manual_collected' => (int) $manualCollected,
                'online_collected' => (int) $onlineCollected,
            ],
            'recentPayments' => Payment::query()->where('club_id', $clubId)->where('status', 'successful')->with(['athlete:id,first_name,last_name', 'invoice:id,title'])->latest('paid_at')->limit(6)->get()->map(fn (Payment $payment) => [
                'id' => $payment->id, 'athlete' => $payment->athlete->full_name, 'title' => $payment->invoice->title,
                'amount' => $payment->amount, 'method' => $payment->method, 'paid_at' => $payment->paid_at->format('Y-m-d H:i'),
            ]),
        ]);
    }

    public function issue(Request $request): RedirectResponse
    {
        $clubId = $request->user()->club_id;
        $data = $request->validate([
            'team_id' => ['required', Rule::exists('teams', 'id')->where('club_id', $clubId)],
            'title' => ['required', 'string', 'max:120'],
            'amount' => ['required', 'integer', 'min:1000', 'max:1000000000'],
            'due_date' => ['required', 'date'],
        ]);

        $count = DB::transaction(function () use ($clubId, $data): int {
            $plan = FeePlan::create([...$data, 'club_id' => $clubId, 'frequency' => 'monthly', 'is_active' => true]);
            $athletes = Team::query()->where('club_id', $clubId)->findOrFail($data['team_id'])->athletes()->where('athletes.is_active', true)->get();
            foreach ($athletes as $athlete) {
                Invoice::create([
                    'club_id' => $clubId, 'athlete_id' => $athlete->id, 'fee_plan_id' => $plan->id,
                    'title' => $data['title'], 'amount' => $data['amount'], 'discount' => 0,
                    'due_date' => $data['due_date'], 'status' => 'unpaid', 'payment_token' => Str::random(48),
                ]);
            }
            return $athletes->count();
        });

        return back()->with('success', "برای {$count} ورزشکار صورتحساب صادر شد.");
    }

    public function pay(Request $request, Invoice $invoice): RedirectResponse
    {
        abort_unless($invoice->club_id === $request->user()->club_id, 404);
        $outstanding = max(0, $invoice->payableAmount() - $invoice->paidAmount());
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1', 'max:'.$outstanding],
            'method' => ['required', 'in:cash,card_transfer,pos'],
            'reference_number' => ['nullable', 'string', 'max:80'],
            'paid_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $payment = DB::transaction(function () use ($request, $invoice, $data): Payment {
            $payment = Payment::create([
                ...$data, 'club_id' => $invoice->club_id, 'invoice_id' => $invoice->id,
                'athlete_id' => $invoice->athlete_id, 'received_by' => $request->user()->id, 'status' => 'successful',
            ]);
            $invoice->refreshStatus();
            return $payment;
        });

        return redirect()->route('finance.receipt', $payment)->with('success', 'پرداخت ثبت شد و رسید آماده است.');
    }

    public function receipt(Request $request, Payment $payment): Response
    {
        abort_unless($payment->club_id === $request->user()->club_id, 404);
        $payment->load(['club:id,name,phone', 'athlete:id,first_name,last_name,membership_number', 'invoice:id,title,amount,discount', 'receiver:id,name']);
        return Inertia::render('Finance/Receipt', ['payment' => [
            'id' => $payment->id, 'club' => $payment->club->name, 'club_phone' => $payment->club->phone,
            'athlete' => $payment->athlete->full_name, 'membership_number' => $payment->athlete->membership_number,
            'title' => $payment->invoice->title, 'amount' => $payment->amount, 'method' => $payment->method,
            'reference_number' => $payment->reference_number, 'paid_at' => $payment->paid_at->format('Y-m-d H:i'),
            'receiver' => $payment->receiver?->name,
        ]]);
    }

    private function invoiceData(Invoice $invoice): array
    {
        $paid = (int) ($invoice->paid_amount ?? 0);
        $payable = $invoice->payableAmount();
        $displayStatus = $invoice->status !== 'paid' && $invoice->due_date->isPast() ? 'overdue' : $invoice->status;
        return [
            'id' => $invoice->id, 'title' => $invoice->title, 'amount' => $invoice->amount, 'discount' => $invoice->discount,
            'payable' => $payable, 'paid' => $paid, 'outstanding' => max(0, $payable - $paid),
            'due_date' => $invoice->due_date->format('Y-m-d'), 'status' => $displayStatus, 'payment_token' => $invoice->payment_token,
            'athlete' => ['id' => $invoice->athlete->id, 'name' => $invoice->athlete->full_name, 'membership_number' => $invoice->athlete->membership_number],
            'team' => $invoice->athlete->teams->first()?->name,
        ];
    }
}
