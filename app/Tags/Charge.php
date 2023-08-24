<?php

namespace App\Tags;

use Exception;
use Stripe\Price;
use Stripe\Stripe;
use Statamic\Tags\Tags;

class Charge extends Tags
{
    public function __construct()
    {
        // Set the API key
        Stripe::setApiKey(config('cashier.secret'));
    }
    /**
     * The {{ charge }} tag.
     *
     * @return string|array
     */
    public function index()
    {
        //
    }

    /**
     * The {{ charge:price }} tag.
     *
     * @return string|array
     */
    public function price()
    {
        // Retrieve the plan ID from a parameter or any other source
        $planId = $this->params->get('plan');


        try {
            // Retrieve the price from Stripe based on the plan ID
            $price = Price::retrieve($planId);

            // Return the price amount in cents
            return [
                'price' => $price->unit_amount % 100 === 0 ? $price->unit_amount / 100 : number_format($price->unit_amount / 100, 2),
                'currency' => $price->currency,
                'name' => $price->nickname,
                'interval' => $price->recurring ? $price->recurring->interval : null,
                'id' => $price->id,
            ];

        } catch (Exception $e) {
            // Handle any errors that occur during the retrieval
            return $e->getMessage();
        }
    }
}
