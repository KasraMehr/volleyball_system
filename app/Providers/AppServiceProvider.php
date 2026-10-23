<?php

namespace App\Providers;

use App\Contracts\PaymentGateway;
use App\Contracts\SmsProvider;
use App\Services\Payments\SandboxPaymentGateway;
use App\Services\Sms\SandboxSmsProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PaymentGateway::class, SandboxPaymentGateway::class);
        $this->app->bind(SmsProvider::class, SandboxSmsProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
