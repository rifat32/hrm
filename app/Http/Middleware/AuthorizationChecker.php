<?php

namespace App\Http\Middleware;

use App\Http\Utils\ModuleUtil;
use App\Models\BusinessSubscription;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;

class AuthorizationChecker
{
    use ModuleUtil;
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {

        $user = auth()->user();

        $business = $user->business;

        if(!empty($business) && $business->owner_id != $user->id){
            if($user->hasRole(("business_employee#" . $business->id))) {
            $moduleEnabled =  $this->isModuleEnabled("employee_login", false);
            if(!$moduleEnabled){
                return response(['message' => 'Module is not enabled'], 401);
            }

            }
        }


        if(empty($user->is_active)) {

            return response(['message' => 'User not active'], 401);
        }

        $accessRevocation = $user->accessRevocation;

        if(!empty($accessRevocation)) {

            if(!empty($accessRevocation->system_access_revoked_date)) {
                if(Carbon::parse($accessRevocation->system_access_revoked_date)) {
  return response(['message' => 'User access revoked active'], 401);
                }
            }


            if(!empty($accessRevocation->email_access_revoked)) {
                return response(['message' => 'User access revoked active'], 401);
            }

        }


        return $next($request);
    }

}
