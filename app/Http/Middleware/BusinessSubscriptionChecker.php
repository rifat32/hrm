<?php

namespace App\Http\Middleware;

use App\Models\BusinessSubscription;
use Carbon\Carbon;
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

            // Check if there's no subscription
if (!$business->is_active) {
    return response()->json(["message" => "Business is not active."], 401);
}


            if ($business->is_self_registered_businesses) {



                    if(!empty($business->trail_end_date)) {
                        if(Carbon::parse($business->trail_end_date)->isPast() && !Carbon::parse($business->trail_end_date)->isToday()){
                            $latest_subscription = BusinessSubscription::where('business_id', $business->id)
                            ->where('service_plan_id', $business->service_plan_id)
                            ->latest() // Get the latest subscription
                            ->first();


// Check if there's no subscription
if (!$latest_subscription) {
    return response()->json(["message" => "Please subscribe to use the software."], 401);
}

// Check if the subscription has not yet started
if (Carbon::parse($latest_subscription->start_date)->isFuture()) {
    return response()->json(["message" => "Your subscription will start soon."], 401);
}


// Check if the subscription has expired
if(!empty($latest_subscription->end_date)) {
    if (Carbon::parse($latest_subscription->end_date)->isPast() && !Carbon::parse($latest_subscription->end_date)->isToday()) {
        return response()->json(["message" => "Your subscription has expired."], 401);
    }
}

                        }

                    }








            }



        }

        return $next($request);
    }
}
