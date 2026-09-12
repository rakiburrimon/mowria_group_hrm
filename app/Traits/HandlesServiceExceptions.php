<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

/**
 * Trait for controllers that call services.
 *
 * Wraps service calls in a consistent try/catch, logs the exception,
 * and returns an appropriate JSON or redirect response.
 */
trait HandlesServiceExceptions
{
    /**
     * Execute a service call inside a database transaction and handle
     * any thrown exception.
     *
     * All writes run atomically — on exception the transaction rolls back,
     * the error is logged, and an appropriate JSON or redirect response
     * is returned.
     *
     * @param callable $callback The service call to execute
     * @param string $errorMessage Fallback message for the response
     * @return mixed The callback result or an error response
     */
    protected function handleService(callable $callback, string $errorMessage = 'An error occurred'): mixed
    {
        try {
            return DB::transaction($callback);
        } catch (\Throwable $e) {
            Log::error($errorMessage, [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                ], 500);
            }

            return redirect()
                ->back()
                ->withErrors(['error' => $errorMessage])
                ->withInput();
        }
    }
}
