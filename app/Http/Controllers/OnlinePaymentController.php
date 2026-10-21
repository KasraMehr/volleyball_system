<?php

namespace App\Http\Controllers;

use App\Contracts\PaymentGateway;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentAttempt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class OnlinePaymentController extends Controller
{
    public function __construct(private readonly PaymentGateway $gateway) {}

    public function show(string $token): Response
    {
        $invoice = $this->invoice($token);
        return Inertia::render('Finance/OnlinePayment', ['invoice' => $this->invoiceData($invoice), 'gateway' => ['name' => $this->gateway->name(), 'sandbox' => $this->gateway->name() === 'sandbox']]);
    }

    public function pay(string $token): RedirectResponse
    {
        $invoice = $this->invoice($token);
        $outstanding = max(0, $invoice->payableAmount() - $invoice->paidAmount());
        abort_if($outstanding < 1, 422, 'این صورتحساب قبلاً تسویه شده است.');
        $attempt = PaymentAttempt::create(['club_id' => $invoice->club_id, 'invoice_id' => $invoice->id, 'gateway' => $this->gateway->name(), 'authority' => (string) Str::uuid(), 'amount' => $outstanding, 'status' => 'initiated', 'callback_url' => route('online-payment.callback', $token), 'expires_at' => now()->addMinutes(config('payments.attempt_ttl'))]);
        return redirect()->away($this->gateway->redirectUrl($attempt));
    }

    public function sandbox(string $token, string $authority): Response
    {
        abort_unless($this->gateway->name() === 'sandbox', 404);
        $invoice = $this->invoice($token);
        $attempt = $this->attempt($invoice, $authority);
        abort_if($attempt->status !== 'initiated' || $attempt->expires_at?->isPast(), 410, 'این درخواست پرداخت منقضی شده است.');
        return Inertia::render('Finance/SandboxGateway', ['invoice' => $this->invoiceData($invoice), 'attempt' => ['authority' => $attempt->authority, 'amount' => $attempt->amount]]);
    }

    public function sandboxComplete(string $token, string $authority, Request $request): RedirectResponse
    {
        $status = $request->validate(['status' => ['required', 'in:OK,CANCEL']])['status'];
        return redirect()->route('online-payment.callback', ['token' => $token, 'authority' => $authority, 'status' => $status]);
    }

    public function callback(Request $request, string $token): RedirectResponse
    {
        $invoice = $this->invoice($token);
        $attempt = $this->attempt($invoice, (string) $request->query('authority'));
        if ($attempt->status === 'successful') return redirect()->route('online-payment.show', $token)->with('success', 'این پرداخت قبلاً تأیید شده است.');
        if ($attempt->status !== 'initiated' || $attempt->expires_at?->isPast()) return redirect()->route('online-payment.show', $token)->with('error', 'درخواست پرداخت معتبر نیست یا منقضی شده است.');

        $verification = $this->gateway->verify($attempt, $request->query());
        if (! $verification->successful) {
            $attempt->update(['status' => 'cancelled', 'completed_at' => now(), 'metadata' => ['message' => $verification->message]]);
            return redirect()->route('online-payment.show', $token)->with('error', $verification->message ?? 'پرداخت انجام نشد.');
        }

        DB::transaction(function () use ($attempt, $invoice, $verification): void {
            $locked = PaymentAttempt::query()->lockForUpdate()->findOrFail($attempt->id);
            if ($locked->status === 'successful') return;
            Payment::create(['club_id' => $invoice->club_id, 'invoice_id' => $invoice->id, 'athlete_id' => $invoice->athlete_id, 'amount' => $locked->amount, 'method' => 'online', 'status' => 'successful', 'reference_number' => $verification->reference, 'paid_at' => now(), 'notes' => 'پرداخت تأییدشده توسط درگاه '.$locked->gateway]);
            $locked->update(['status' => 'successful', 'reference_number' => $verification->reference, 'completed_at' => now()]);
            $invoice->refreshStatus();
        });
        return redirect()->route('online-payment.show', $token)->with('success', 'پرداخت با موفقیت تأیید و ثبت شد.');
    }

    private function invoice(string $token): Invoice { return Invoice::query()->where('payment_token', $token)->with(['club:id,name', 'athlete:id,first_name,last_name'])->firstOrFail(); }
    private function attempt(Invoice $invoice, string $authority): PaymentAttempt { return $invoice->paymentAttempts()->where('authority', $authority)->firstOrFail(); }
    private function invoiceData(Invoice $invoice): array
    {
        $paid = $invoice->paidAmount();
        return ['token' => $invoice->payment_token, 'club' => $invoice->club->name, 'athlete' => $invoice->athlete->full_name, 'title' => $invoice->title, 'amount' => $invoice->payableAmount(), 'paid' => $paid, 'outstanding' => max(0, $invoice->payableAmount() - $paid), 'due_date' => $invoice->due_date->format('Y-m-d'), 'is_paid' => $invoice->status === 'paid'];
    }
}
