<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class OnlinePaymentController extends Controller
{
    public function show(string $token): Response
    {
        $invoice = Invoice::query()->where('payment_token', $token)->with(['club:id,name', 'athlete:id,first_name,last_name'])->firstOrFail();
        $paid = $invoice->paidAmount();
        return Inertia::render('Finance/OnlinePayment', [
            'invoice' => [
                'token' => $token, 'club' => $invoice->club->name, 'athlete' => $invoice->athlete->full_name,
                'title' => $invoice->title, 'amount' => $invoice->payableAmount(), 'paid' => $paid,
                'outstanding' => max(0, $invoice->payableAmount() - $paid), 'due_date' => $invoice->due_date->format('Y-m-d'),
                'is_paid' => $invoice->status === 'paid',
            ],
        ]);
    }

    public function pay(Request $request, string $token): RedirectResponse
    {
        $invoice = Invoice::query()->where('payment_token', $token)->firstOrFail();
        $outstanding = max(0, $invoice->payableAmount() - $invoice->paidAmount());
        abort_if($outstanding < 1, 422, 'این صورتحساب قبلاً تسویه شده است.');

        DB::transaction(function () use ($invoice, $outstanding): void {
            Payment::create([
                'club_id' => $invoice->club_id, 'invoice_id' => $invoice->id, 'athlete_id' => $invoice->athlete_id,
                'amount' => $outstanding, 'method' => 'online', 'status' => 'successful',
                'reference_number' => 'DEMO-'.now()->format('ymdHis').'-'.$invoice->id,
                'paid_at' => now(), 'notes' => 'پرداخت آزمایشی محیط دمو',
            ]);
            $invoice->refreshStatus();
        });

        return back()->with('success', 'پرداخت آزمایشی با موفقیت ثبت شد.');
    }
}
