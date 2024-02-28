<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Stripe\Checkout\Session;
use Stripe\Stripe;


class SubscriptionController extends Controller
{
    public function redirectUserToStripe(Request $request) {
        $user = User::findOrFail($request->id); // Assuming you want to find user with ID 1
        Stripe::setApiKey(config('services.stripe.secret'));

        if (empty($user->stripe_id)) {
            $stripe_customer = \Stripe\Customer::create([
                'email' => $user->email,
            ]);

            $user->stripe_id = $stripe_customer->id;
            $user->save();
        } else {
            $stripe_customer_id = $user->stripe_id;
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
                        'unit_amount' => 1000, // Amount in cents
                    ],
                    'quantity' => 1,
                ],
                [
                    'price_data' => [
                        'currency' => 'GBP',
                        'product_data' => [
                            'name' => 'Your Service monthly amount',
                        ],
                        'unit_amount' => 1000, // Amount in cents
                        'recurring' => [
                            'interval' => 'month', // Recur monthly
                        ],
                    ],
                    'quantity' => 1,
                ],
            ],
            'customer' => $stripe_customer_id ?? null,
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
