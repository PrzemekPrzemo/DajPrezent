<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Tenancy\CurrentTenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
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
    }
}
