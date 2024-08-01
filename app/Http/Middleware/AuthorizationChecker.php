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
                $this->isModuleEnabled("employee_login");
            }
        }


        if(empty($user->is_active)) {

            return response(['message' => 'User not active'], 403);
        }

        $accessRevocation = $user->accessRevocation;

        if(!empty($accessRevocation)) {

            if(!empty($accessRevocation->system_access_revoked_date)) {
                if(Carbon::parse($accessRevocation->system_access_revoked_date)) {
  return response(['message' => 'User access revoked active'], 403);
                }
            }


            if(!empty($accessRevocation->email_access_revoked)) {
                return response(['message' => 'User access revoked active'], 403);
            }



        }


        return $next($request);
    }
}
