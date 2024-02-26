<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Http\Controllers\WebhookController;
use Stripe\Event;

class CustomWebhookController extends WebhookController
{
    /**
     * Handle a Stripe webhook call.
     *
     * @param  Event  $event
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleStripeWebhook(Request $request)
    {
        // Retrieve the event data from the request body
        $payload = $request->all();

        // Log the entire payload for debugging purposes
        Log::info('Webhook Payload: ' . json_encode($payload));

        // Extract the event type
        $eventType = $payload['type'] ?? null;

        // Log the event type
        Log::info('Event Type: ' . $eventType);

        // Handle the event based on its type
        if ($eventType === 'charge.succeeded') {
            $this->handleChargeSucceeded($payload['data']['object']);
        }

        // Return a response to Stripe to acknowledge receipt of the webhook
        return response()->json(['message' => 'Webhook received']);
    }

    /**
     * Handle payment succeeded webhook from Stripe.
     *
     * @param  array  $paymentCharge
     * @return void
     */
    protected function handleChargeSucceeded($paymentCharge)
    {
        // Extract required data from payment charge
        $amount = $paymentCharge['amount'] ?? null;
        $currency = $paymentCharge['currency'] ?? null;
        $customerID = $paymentCharge['customer'] ?? null;
        // Add more fields as needed


        $user = User::where("stripe_id",$customerID)-> first();
        $userID = $user->id ?? null;

        // Log the extracted data
        Log::info('Amount: ' . $amount);
        Log::info('Currency: ' . $currency);
        Log::info('Customer ID: ' . $customerID);
        Log::info('User ID: ' . $userID);

        // Process the payment charge data as needed
        // For example, you can update the user's payment information in the database
    }
}
