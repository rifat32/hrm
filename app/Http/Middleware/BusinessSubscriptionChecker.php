<?php

namespace App\Http\Middleware;

use App\Models\BusinessSubscription;
use Closure;
use Illuminate\Http\Request;

class BusinessSubscriptionChecker
{
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

        if ($user && $user->business) {
            $business = $user->business;

            if ($business->is_self_registered_businesses) {
                $latest_subscription = BusinessSubscription::where('business_id', $business->id)
                    ->where('service_plan_id', $business->service_plan_id)
                    ->latest() // Get the latest subscription
                    ->first();

                if (!$latest_subscription || $latest_subscription->end_date < now() || $latest_subscription->start_date > now()) {
                    if ($latest_subscription) {
                        return response()->json("Your subscription has expired.", 500);
                    } else {
                        return response()->json("Please subscribe to use the software.", 500);
                    }
                }
            }
        }

        return $next($request);
    }
}
