<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureContactsRateLimiting();
    }

    /**
     * Separate per-IP limits for reads, writes and mock calls. Rate limiting is abuse
     * protection only; the API has no authentication in the current scope.
     */
    private function configureContactsRateLimiting(): void
    {
        foreach (['read', 'write', 'calls'] as $operation) {
            RateLimiter::for("contacts-{$operation}", fn (Request $request): Limit => Limit::perMinute((int) config("contacts.rate_limits.{$operation}"))
                ->by((string) $request->ip())
                ->response(fn (Request $request, array $headers): JsonResponse => response()->json([
                    'message' => "Too many requests. Please try again in {$headers['Retry-After']} seconds.",
                ], 429, $headers)));
        }
    }
}
