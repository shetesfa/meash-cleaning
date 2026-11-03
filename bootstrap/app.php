<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(function (\Illuminate\Http\Request $request, \Throwable $e) {
            if ($request->is('api/*')) {
                return true;
            }
            return $request->expectsJson();
        });

        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'የግብዓት ማረጋገጫ ስህተት አጋጥሟል (Validation Error)',
                        'errors' => $e->errors(),
                    ], 422);
                }

                if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'እባክዎ መጀመሪያ ይግቡ (Unauthenticated)',
                    ], 401);
                }

                if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
                    $status = $e->getStatusCode();
                    $msg = $e->getMessage() ?: match ($status) {
                        401 => 'እባክዎ መጀመሪያ ይግቡ (Unauthenticated)',
                        403 => 'ይህንን እርምጃ ለመፈጸም ፍቃድ የለዎትም (Unauthorized)',
                        404 => 'የተጠየቀው መረጃ አልተገኘም (Not Found)',
                        429 => 'ተከታታይ ጥያቄዎችን በብዛት ልከዋል፤ እባክዎ ጥቂት ቆይተው እንደገና ይሞክሩ (Too Many Requests)',
                        default => 'ስህተት አጋጥሟል',
                    };
                    return response()->json([
                        'success' => false,
                        'message' => $msg,
                    ], $status);
                }

                // Production safety: never leak stack traces, SQL errors, or file paths
                \Illuminate\Support\Facades\Log::error('API Exception Caught: ' . $e->getMessage(), [
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'url' => $request->fullUrl(),
                    'ip' => $request->ip(),
                ]);

                $isDebug = config('app.debug', false);
                return response()->json([
                    'success' => false,
                    'message' => $isDebug ? $e->getMessage() : 'የውስጥ ሲስተም ስህተት አጋጥሟል። እባክዎ ቆይተው እንደገና ይሞክሩ።',
                ], 500);
            }
        });
    })->create();
