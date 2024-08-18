<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class ConfigureMailSettings
{
   /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = auth()->user();
        if (!empty($user)) {

            $business = $user->business;
            if ($business && $business->emailSettings) {

                $emailSettings = $business->emailSettings;

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

        return $next($request);
    }
}
