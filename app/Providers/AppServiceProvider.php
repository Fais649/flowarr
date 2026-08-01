<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;
use Illuminate\Foundation\DevCommands;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        DevCommands::artisan('schedule:work', 'schedule');
        $this->app->singleton(FFProbe::class, fn () => FFProbe::create());
        $this->app->singleton(FFMpeg::class, fn () => FFMpeg::create());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
