<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Settings\SettingsRepository;
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
        $this->configureMailFromSettings();
    }

    /**
     * Override Laravel mail config with values from app_settings table
     * (set by /admin/settings → SMTP). Falls back to .env when no DB
     * override is present, so a fresh install still boots.
     *
     * Run on every request — cheap (cached in SettingsRepository) and
     * lets admins rotate the SMTP password without restarting FPM.
     */
    private function configureMailFromSettings(): void
    {
        // Skip during migrations / fresh installs / test bootstrap when
        // the app_settings table doesn't exist yet. Querying it would
        // throw QueryException and crash every test that doesn't seed
        // the schema.
        try {
            $s = $this->app->make(SettingsRepository::class);

            $driver = (string) $s->get('mail.driver', config('mail.default', 'log'));
            config([
                'mail.default' => $driver,
                'mail.mailers.smtp.host' => (string) $s->get('mail.host', config('mail.mailers.smtp.host', '')),
                'mail.mailers.smtp.port' => (int) $s->get('mail.port', config('mail.mailers.smtp.port', 587)),
                'mail.mailers.smtp.encryption' => (string) $s->get('mail.encryption', 'tls') ?: null,
                'mail.mailers.smtp.username' => (string) $s->get('mail.username', config('mail.mailers.smtp.username', '')),
                'mail.mailers.smtp.password' => (string) $s->get('mail.password', config('mail.mailers.smtp.password', '')),
                'mail.from.address' => (string) $s->get('mail.from_address', config('mail.from.address', 'noreply@dajprezent.pl')),
                'mail.from.name' => (string) $s->get('mail.from_name', config('mail.from.name', 'DajPrezent.pl')),
            ]);
        } catch (\Throwable) {
            // Table missing / db unavailable / cache issue — boot anyway
            // so artisan migrate, factories, and bare tests keep working.
        }
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
