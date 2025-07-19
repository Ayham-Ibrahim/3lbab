<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Console\Scheduling\Schedule;


if (!function_exists('handleApiExceptions')) {

    /**
     * Handles formatting API exceptions into a standardized JSON response.
     *
     * @param Throwable $e
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    function handleApiExceptions(Throwable $e, Request $request): \Illuminate\Http\JsonResponse
    {
        // Log the actual system error for developers
        Log::error(
            "Exception: " . get_class($e) . " - Message: " . $e->getMessage() . " - File: " . $e->getFile() . " - Line: " . $e->getLine()
        );
    
        // Use a match expression for a clean, declarative way to handle exceptions
        $responseDetails = match (get_class($e)) {
            ValidationException::class => [
                'statusCode' => 422,
                'message' => $e->getMessage(),
                // 'errors' key will be added below for type safety
            ],
    
            AuthenticationException::class => [
                'statusCode' => 401,
                'message' => 'Unauthenticated.',
            ],
    
            AuthorizationException::class => [
                'statusCode' => 403,
                'message' => 'This action is unauthorized.',
            ],
    
            NotFoundHttpException::class => [
                'statusCode' => 404,
                'message' => 'The requested resource was not found.'
            ],
    
            default => [
                'statusCode' => $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500,
                'message' => 'An unexpected error occurred. Please try again later.',
            ]
        };
    
        // Handle validation errors specifically to ensure type safety
        if ($e instanceof ValidationException) {
            $responseDetails['errors'] = $e->errors();
        }
    
        // Ensure status code is a valid client/server error code
        if ($responseDetails['statusCode'] < 400 || $responseDetails['statusCode'] >= 600) {
            $responseDetails['statusCode'] = 500;
        }
    
        $payload = [
            'status' => 'error',
            'message' => $responseDetails['message'],
        ];
    
        if (!empty($responseDetails['errors'])) {
            $payload['errors'] = $responseDetails['errors'];
        }
    
        return response()->json($payload, $responseDetails['statusCode']);
    }
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // $middleware->api(prepend: [
        //     \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        // ]);

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($e instanceof HttpResponseException) {
                return $e->getResponse();
            }

            if ($request->expectsJson() || $request->is('api/*')) {
                return handleApiExceptions($e, $request);
            }
        });
    })->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule) {
        // أضفنا اسم الاتصال 'database' بشكل صريح
        $schedule->command('queue:work database --stop-when-empty -v')->everyMinute();
    })->create();
