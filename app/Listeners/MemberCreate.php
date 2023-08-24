<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Log;
use Statamic\Events\UserRegistered;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class MemberCreate
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(UserRegistered $event): void
    {

        $registeredUser = $event->user->model();
        $stripeCustomer = $registeredUser->createOrGetStripeCustomer();
        Log::info('Stripe customer created: ' . $stripeCustomer->id);
    }
}
