<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => fn () => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'role' => $request->user()->role->value,
                    'role_label' => $request->user()->role->label(),
                    'unread_notifications' => $request->user()->clubNotifications()->whereNull('read_at')->count(),
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
            ],
            'app' => [
                'name' => config('app.name'),
                'club' => fn () => $request->user()?->club?->name ?? 'هیئت والیبال استان',
            ],
        ];
    }
}
