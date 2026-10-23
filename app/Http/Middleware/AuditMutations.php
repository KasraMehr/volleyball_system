<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AuditMutations
{
    public function handle(Request $request, Closure $next): Response
    {
        $actor = $request->user();
        $response = $next($request);
        $routeName = $request->route()?->getName();

        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)
            || $response->getStatusCode() >= 400
            || in_array($routeName, ['notifications.read', 'notifications.read-all'], true)) {
            return $response;
        }

        try {
            $actor ??= $request->user();
            $subject = collect($request->route()?->parameters() ?? [])->first(fn ($value) => $value instanceof Model);
            ActivityLog::create([
                'club_id' => $actor?->club_id,
                'user_id' => $actor?->id,
                'action' => $this->action($routeName, $request->method()),
                'route_name' => $routeName,
                'method' => $request->method(),
                'subject_type' => $subject ? class_basename($subject) : null,
                'subject_id' => $subject?->getKey(),
                'description' => $this->description($routeName),
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
                'context' => ['response_status' => $response->getStatusCode()],
                'occurred_at' => now(),
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }

        return $response;
    }

    private function action(?string $route, string $method): string
    {
        return match (true) {
            str_contains((string) $route, 'login') => 'login',
            str_contains((string) $route, 'logout') => 'logout',
            str_contains((string) $route, 'pay') || str_contains((string) $route, 'payment') => 'payment',
            str_contains((string) $route, 'attendance') => 'attendance',
            str_contains((string) $route, 'notification') => 'notification',
            str_contains((string) $route, 'toggle') || str_contains((string) $route, 'activate') || str_contains((string) $route, 'complete') => 'status_change',
            $method === 'POST' => 'create',
            in_array($method, ['PUT', 'PATCH'], true) => 'update',
            $method === 'DELETE' => 'delete',
            default => 'other',
        };
    }

    private function description(?string $route): string
    {
        return [
            'login.store' => 'ورود به سامانه', 'logout' => 'خروج از سامانه',
            'users.store' => 'ایجاد حساب کاربری', 'users.toggle' => 'تغییر وضعیت حساب کاربری',
            'teams.store' => 'ایجاد تیم جدید', 'teams.update' => 'ویرایش اطلاعات تیم', 'teams.toggle' => 'تغییر وضعیت تیم',
            'athletes.store' => 'ثبت ورزشکار جدید', 'athletes.update' => 'ویرایش پرونده ورزشکار', 'athletes.toggle' => 'تغییر وضعیت ورزشکار',
            'attendance.store' => 'ایجاد جلسه تمرین', 'attendance.mark' => 'ثبت حضور و غیاب', 'attendance.complete' => 'نهایی کردن جلسه تمرین',
            'finance.issue' => 'صدور گروهی شهریه', 'finance.pay' => 'ثبت دریافت شهریه', 'online-payment.pay' => 'پرداخت آنلاین شهریه',
            'assessments.store' => 'ثبت ارزیابی ورزشی',
            'settings.club.update' => 'ویرایش تنظیمات باشگاه', 'settings.venues.store' => 'ثبت سالن جدید', 'settings.venues.toggle' => 'تغییر وضعیت سالن', 'settings.seasons.store' => 'ثبت فصل ورزشی', 'settings.seasons.activate' => 'تغییر فصل جاری',
            'notifications.announce' => 'ارسال اطلاعیه باشگاه', 'notifications.payment-reminders' => 'تولید یادآوری شهریه', 'notifications.training-reminders' => 'تولید یادآوری تمرین',
            'notifications.sms.retry' => 'تلاش مجدد برای ارسال پیامک',
        ][$route] ?? 'انجام عملیات در سامانه';
    }
}
