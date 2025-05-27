<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * الـ middleware العام (Global HTTP middleware stack).
     *
     * @var array
     */
    protected $middleware = [
        \Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        \App\Http\Middleware\TrustProxies::class,
    ];

    /**
     * مجموعات Middleware للمسارات.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            // إذا كنت تستخدم CSRF
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api' => [
            'throttle:api', // يمكنك ضبط ذلك حسب حاجتك
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            'auth:sanctum', // ضمّن Sanctum للمسارات المحمية
        ],
    ];

    /**
     * الـ route middleware (تستخدم لتطبيقها على مسارات فردية).
     *
     * @var array
     */
    protected $routeMiddleware = [
        'auth'      => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'guest'     => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'throttle'  => \Illuminate\Routing\Middleware\ThrottleRequests::class,
    ];
}
