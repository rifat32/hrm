<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\ServicePlan;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Stripe\Checkout\Session;
use Stripe\Stripe;


class SubscriptionController extends Controller
{
    public function redirectUserToStripe(Request $request) {
        $id = $request->id;

// Check if the string is at least 20 characters long to ensure it has enough characters to remove
if (strlen($id) >= 20) {
    // Remove the first ten characters and the last ten characters
    $trimmed_id = substr($id, 10, -10);
    // $trimmedId now contains the string with the first ten and last ten characters removed
}
else {
    throw new Exception("invalid id");
}
        $business = Business::findOrFail($trimmed_id);
        $user = User::findOrFail($business->owner_id);


        $service_plan = ServicePlan::where([
            "id" => $business->service_plan_id
        ])
        ->first();


        if(!$service_plan) {
            return response()->json([
                "message" => "no service plan found"
            ],404);
        }




        Stripe::setApiKey(config('services.stripe.secret'));

        if (empty($user->stripe_id)) {
            $stripe_customer = \Stripe\Customer::create([
                'email' => $user->email,
            ]);

            $user->stripe_id = $stripe_customer->id;
            $user->save();
        } 


        $session = Session::create([
            'payment_method_types' => ['card'],
            'metadata' => [
                'product_id' => '123',
                'product_description' => 'Your Service set up amount',
            ],
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => 'GBP',
                        'product_data' => [
                            'name' => 'Your Service set up amount',
                        ],
                        'unit_amount' => $service_plan->set_up_amount * 100 , // Amount in cents
                    ],
                    'quantity' => 1,
                ],
                [
                    'price_data' => [
                        'currency' => 'GBP',
                        'product_data' => [
                            'name' => 'Your Service monthly amount',
                        ],
                        'unit_amount' => $service_plan->price * 100, // Amount in cents
                        'recurring' => [
                            'interval' => 'month', // Recur monthly
                            'interval_count' => $service_plan->duration_months, // Adjusted duration
                        ],
                    ],
                    'quantity' => 1,
                ],
            ],
            'customer' => $user->stripe_id,
            'mode' => 'subscription',
            'success_url' => route('subscription.success_payment'),
            'cancel_url' => route('subscription.failed_payment'),
        ]);

        return redirect()->to($session->url);
    }



    public function stripePaymentSuccess (Request $request) {
        return redirect()->away(env("FRONT_END_URL") . '/auth/login');
    }
    public function stripePaymentFailed (Request $request) {
        return redirect()->away(env("FRONT_END_URL") . '/auth/login');
    }




}
