<?php
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__.'/../routes/web.php', commands: __DIR__.'/../routes/console.php', health: '/up')
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: ['lead-webhooks/*']);
        $middleware->alias(['session.limits' => \App\Http\Middleware\EnforceSessionLimits::class, 'active.lead.call' => \App\Http\Middleware\EnforceActiveLeadCall::class, 'permission' => \App\Http\Middleware\RequirePermission::class, 'crm.notifications' => \App\Http\Middleware\RecordCrmNotification::class, 'crm.audit' => \App\Http\Middleware\RecordCrmAudit::class]);
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('home'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {$exceptions->dontFlash(['api_key','password']);})
    ->create();
