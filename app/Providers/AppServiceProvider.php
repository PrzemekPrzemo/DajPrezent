<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Tenancy\CurrentTenant;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CurrentTenant::class);
    }

    public function boot(): void
    {
        Model::unguard(false);
        Model::shouldBeStrict(! $this->app->isProduction());

        // Map domain models (App\Domain\<Module>\Models\Foo) to factories
        // under Database\Factories\FooFactory. Falls back to default
        // Laravel guessing for models that already live under App\Models.
        Factory::guessFactoryNamesUsing(static function (string $modelName): string {
            return 'Database\\Factories\\'.Str::afterLast($modelName, '\\').'Factory';
        });

        $this->configureRateLimiters();
    }

    /**
     * Anti-spam / brute-force limits for public endpoints.
     * Keyed by IP — for authenticated endpoints we mix in user id.
     */
    private function configureRateLimiters(): void
    {
        RateLimiter::for('auth', fn (Request $r) => Limit::perMinute(10)->by($r->ip() ?? 'unknown'));
        RateLimiter::for('signup', fn (Request $r) => Limit::perHour(10)->by($r->ip() ?? 'unknown'));
        RateLimiter::for('checkout', fn (Request $r) => Limit::perHour(20)->by(
            $r->user()?->getAuthIdentifier() ?? $r->ip() ?? 'unknown'
        ));
        // Reservation throttle keys on (IP, slug) so one party hammering
        // one list doesn't lock out reservations on a different list.
        RateLimiter::for('reservation', fn (Request $r) => Limit::perMinute(5)->by(
            ($r->ip() ?? 'unknown').':'.($r->route('slug') ?? '')
        ));
        // RSVP throttle — niech jedna rodzina nie wpisze 200 razy.
        RateLimiter::for('rsvp', fn (Request $r) => Limit::perHour(20)->by(
            ($r->ip() ?? 'unknown').':'.($r->route('slug') ?? '')
        ));
    }
}
