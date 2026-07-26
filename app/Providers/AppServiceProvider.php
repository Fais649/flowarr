<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;
use Illuminate\Console\Events\ArtisanStarting;
use Illuminate\Foundation\DevCommands;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
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

        $this->app->bind(FFProbe::class, function () {
            return FFProbe::create();
        });

        // If you also inject FFMpeg\FFMpeg elsewhere, bind it as well:
        $this->app->bind(FFMpeg::class, function () {
            return FFMpeg::create();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(ArtisanStarting::class, function () {
            DevCommands::artisan('queue:listen --tries=1 --timeout=0 --queue=transcode,subtitle,default', 'queue');
        });

        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
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
