<?php

namespace App\Providers;

use App\Models\Attendance;
use App\Models\Leave;
use App\Models\User;
use App\Observers\AttendanceObserver;
use App\Observers\LeaveObserver;
use App\Observers\UserObserver;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Console\ClientCommand;
use Laravel\Passport\Console\InstallCommand;
use Laravel\Passport\Console\KeysCommand;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
        $this->commands([
            InstallCommand::class,
            ClientCommand::class,
            KeysCommand::class,
        ]);
        User::observe(UserObserver::class);
        Attendance::observe(AttendanceObserver::class);

        Leave::observe(LeaveObserver::class);


        view()->composer('*', function () {
            if (auth()->check()) {
                $business = auth()->user()->business;
                $emailSettings = $business->emailSettings;

                if ($emailSettings) {
                    Config::set('mail.driver', $emailSettings->mail_driver);
                    Config::set('mail.host', $emailSettings->mail_host);
                    Config::set('mail.port', $emailSettings->mail_port);
                    Config::set('mail.username', $emailSettings->mail_username);
                    Config::set('mail.password', $emailSettings->mail_password);
                    Config::set('mail.encryption', $emailSettings->mail_encryption);
                    Config::set('mail.from.address', $emailSettings->mail_from_address);
                    Config::set('mail.from.name', $emailSettings->mail_from_name);
                }
            }
        });
    }
}
