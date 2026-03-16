<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configurePasswordDefaults();
        $this->configureRateLimiting();
    }

    private function configurePasswordDefaults(): void
    {
        Password::defaults(function () {
            return Password::min(12)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised(config('security.password.check_pwned') ? 3 : 0);
        });
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $key = strtolower($request->input('email')).'|'.$request->ip();

            return Limit::perMinute(
                config('security.rate_limits.login.attempts', 5)
            )->by($key);
        });

        RateLimiter::for('api', function (Request $request) {
            $user = $request->user();

            if (! $user) {
                return Limit::perMinute(config('security.rate_limits.api_guest.attempts', 20))
                    ->by($request->ip());
            }

            $role = $user->role ?? 'client';
            $limit = config("security.rate_limits.api_{$role}.attempts", 60);

            return Limit::perMinute($limit)->by($user->id);
        });
    }
}
