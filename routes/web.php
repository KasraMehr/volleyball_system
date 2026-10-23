<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AthleteController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\OnlinePaymentController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ClubSettingsController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\WorkoutPlanController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::get('/pay/{token}', [OnlinePaymentController::class, 'show'])->name('online-payment.show');
Route::post('/pay/{token}', [OnlinePaymentController::class, 'pay'])->name('online-payment.pay');
Route::get('/pay/{token}/callback', [OnlinePaymentController::class, 'callback'])->name('online-payment.callback');
Route::get('/pay/{token}/sandbox/{authority}', [OnlinePaymentController::class, 'sandbox'])->name('online-payment.sandbox');
Route::post('/pay/{token}/sandbox/{authority}', [OnlinePaymentController::class, 'sandboxComplete'])->name('online-payment.sandbox.complete');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/notifications/announce', [NotificationController::class, 'announce'])->middleware('role:club_admin')->name('notifications.announce');
    Route::post('/notifications/payment-reminders', [NotificationController::class, 'paymentReminders'])->middleware('role:club_admin,finance')->name('notifications.payment-reminders');
    Route::post('/notifications/training-reminders', [NotificationController::class, 'trainingReminders'])->middleware('role:club_admin,coach')->name('notifications.training-reminders');
    Route::post('/notifications/sms/{sms}/retry', [NotificationController::class, 'retrySms'])->middleware('role:club_admin,finance')->name('notifications.sms.retry');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/coach', [DashboardController::class, 'coach'])->middleware('role:club_admin,coach')->name('coach');
    Route::middleware('role:club_admin,finance')->group(function (): void {
        Route::get('/finance', [FinanceController::class, 'index'])->name('finance');
        Route::post('/finance/issues', [FinanceController::class, 'issue'])->name('finance.issue');
        Route::post('/finance/invoices/{invoice}/payments', [FinanceController::class, 'pay'])->name('finance.pay');
        Route::get('/finance/receipts/{payment}', [FinanceController::class, 'receipt'])->name('finance.receipt');
    });
    Route::get('/athlete', [DashboardController::class, 'athlete'])->middleware('role:club_admin,athlete,guardian')->name('athlete');
    Route::get('/my-payments', [FinanceController::class, 'mine'])->middleware('role:athlete,guardian')->name('finance.mine');
    Route::get('/my-performance', [AssessmentController::class, 'mine'])->middleware('role:athlete,guardian')->name('assessments.mine');
    Route::get('/my-training', [WorkoutPlanController::class, 'mine'])->middleware('role:athlete,guardian')->name('workouts.mine');
    Route::patch('/my-training/exercises/{exercise}', [WorkoutPlanController::class, 'complete'])->middleware('role:athlete')->name('workouts.complete');
    Route::get('/athletes', [AthleteController::class, 'index'])->middleware('role:club_admin,coach,finance')->name('athletes.index');
    Route::get('/athletes/{athlete}', [AthleteController::class, 'show'])->middleware('role:club_admin,coach,finance,athlete,guardian')->name('athletes.show');
    Route::post('/athletes/{athlete}/photo', [AthleteController::class, 'photo'])->middleware('role:club_admin,athlete,guardian')->name('athletes.photo');
    Route::middleware('role:club_admin,coach,finance')->group(function (): void {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export/{type}', [ReportController::class, 'export'])->name('reports.export');
        Route::get('/reports/print/{type}', [ReportController::class, 'print'])->name('reports.print');
    });
    Route::middleware('role:club_admin,coach')->group(function (): void {
        Route::get('/training-plans', [WorkoutPlanController::class, 'index'])->name('workouts.index');
        Route::post('/training-plans', [WorkoutPlanController::class, 'store'])->name('workouts.store');
        Route::patch('/training-plans/{plan}/status', [WorkoutPlanController::class, 'status'])->name('workouts.status');
        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');
        Route::get('/attendance/{session}', [AttendanceController::class, 'show'])->name('attendance.show');
        Route::put('/attendance/{session}/records', [AttendanceController::class, 'mark'])->name('attendance.mark');
        Route::patch('/attendance/{session}/complete', [AttendanceController::class, 'complete'])->name('attendance.complete');
        Route::get('/assessments', [AssessmentController::class, 'index'])->name('assessments.index');
        Route::get('/assessments/{athlete}', [AssessmentController::class, 'show'])->name('assessments.show');
        Route::post('/assessments/{athlete}', [AssessmentController::class, 'store'])->name('assessments.store');
    });

    Route::middleware('role:club_admin')->group(function (): void {
        Route::get('/activity-log', ActivityLogController::class)->name('activity-log.index');
        Route::get('/settings', [ClubSettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings/club', [ClubSettingsController::class, 'update'])->name('settings.club.update');
        Route::post('/settings/venues', [ClubSettingsController::class, 'storeVenue'])->name('settings.venues.store');
        Route::patch('/settings/venues/{venue}/toggle', [ClubSettingsController::class, 'toggleVenue'])->name('settings.venues.toggle');
        Route::post('/settings/seasons', [ClubSettingsController::class, 'storeSeason'])->name('settings.seasons.store');
        Route::patch('/settings/seasons/{season}/activate', [ClubSettingsController::class, 'activateSeason'])->name('settings.seasons.activate');
        Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
        Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
        Route::patch('/users/{user}/toggle', [UserManagementController::class, 'toggle'])->name('users.toggle');
        Route::post('/athletes', [AthleteController::class, 'store'])->name('athletes.store');
        Route::put('/athletes/{athlete}', [AthleteController::class, 'update'])->name('athletes.update');
        Route::patch('/athletes/{athlete}/toggle', [AthleteController::class, 'toggle'])->name('athletes.toggle');
        Route::get('/teams', [TeamController::class, 'index'])->name('teams.index');
        Route::post('/teams', [TeamController::class, 'store'])->name('teams.store');
        Route::put('/teams/{team}', [TeamController::class, 'update'])->name('teams.update');
        Route::patch('/teams/{team}/toggle', [TeamController::class, 'toggle'])->name('teams.toggle');
    });

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
