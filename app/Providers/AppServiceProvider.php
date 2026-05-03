<?php

namespace App\Providers;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\User;
use App\Observers\AppointmentObserver;
use App\Observers\DoctorObserver;
use App\Observers\UserObserver;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Sk\SmartId\SmartIdClient;
use Sk\SmartId\Ssl\SslPinnedPublicKeyStore;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SmartIdClient::class, fn () => new SmartIdClient(
            config('smart-id.rp_uuid'),
            config('smart-id.rp_name'),
            config('smart-id.host_url'),
            SslPinnedPublicKeyStore::loadDemo(),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        User::observe(UserObserver::class);
        Doctor::observe(DoctorObserver::class);
        Appointment::observe(AppointmentObserver::class);
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
