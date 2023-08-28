<?php

namespace App\Http\Controllers;

use Stripe\Exception\InvalidRequestException;
use Stripe\Stripe;
use Statamic\Facades\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Statamic\Http\Controllers\UserController as Controller;

class UserController extends Controller
{
    public function register(Request $request)
    {
        $origRequest = $request->duplicate();
        $blueprint = User::blueprint();
        $fields = $blueprint->fields();

        $blueprintKeys = $fields->all()->keys()->toArray();
        $blueprintKeys[] = 'password';
        $blueprintKeys[] = 'password_confirmation';

        foreach($request->all() as $key => $value) {
            if (!in_array($key, $blueprintKeys)) {
                $request->request->remove($key);
            }
        }
        // Add _redirect to the request
        $request->request->add(['_redirect' => '/join/subscribe']);

        $userRegister = parent::register($request);

        return $userRegister;

    }

    public function join()
    {
        return (new \Statamic\View\View)
            ->template('account.join')
            ->layout('layout');
    }

    public function subscribe()
    {
        return (new \Statamic\View\View)
            ->template('account.membership')
            ->layout('layout')
            ->with([
                'client_secret' => Auth::user()->createSetupIntent()->client_secret,
                'cardholder_name' => Auth::user()->name,
            ]);

    }

    public function doSubscribe(Request $request)
    {
        $user = Auth::user();

        // Get subscription plan from plan_id through Stripe API
        Stripe::setApiKey(config('cashier.secret'));
        $plan = \Stripe\Plan::retrieve($request->plan_id);
        $user->newSubscription($plan->nickname, $plan->id)->create($request->payment_method);

        return redirect('/account/billing');
    }

    public function billing()
    {
        $user = Auth::user();

        Stripe::setApiKey(config('cashier.secret'));
        $stripe = new \Stripe\StripeClient(config('cashier.secret'));

        $subscription = $user->subscriptions->first()->asStripeSubscription();

        $portal = $stripe->billingPortal->sessions->create([
            'customer' => $user->stripe_id,
            'return_url' => config('app.url').'/account/billing',
          ]);

        $subUpdate = $stripe->billingPortal->sessions->create([
            'customer' => $user->stripe_id,
            'return_url' => config('app.url').'/account/billing',
            'flow_data' => [
                'type' => 'subscription_update',
                'subscription_update' => ['subscription' => $subscription->id],
            ],

          ]);

        $cancelMembership = null;
        if($subscription->cancel_at == null) {

            $cancelMembership = $stripe->billingPortal->sessions->create([
                'customer' => $user->stripe_id,
                'return_url' => config('app.url').'/account/billing',
                'flow_data' => [
                    'type' => 'subscription_cancel',
                    'subscription_cancel' => ['subscription' => $subscription->id],
                ],
            ]);
        }

        $addPaymentMethod = $stripe->billingPortal->sessions->create([
            'customer' => $user->stripe_id,
            'return_url' => config('app.url').'/account/billing',
            'flow_data' => [
                'type' => 'payment_method_update',
            ],
          ]);

        $customer = $stripe->customers->retrieve($user->stripe_id);

        $default_payment_method_id = $customer->invoice_settings->default_payment_method;
        $paymentMethods = $user->paymentMethods();
        $paymentMethods->each(function($paymentMethod) use ($default_payment_method_id) {
            $paymentMethod->asStripePaymentMethod()->default = $paymentMethod->asStripePaymentMethod()->id == $default_payment_method_id;
        });

        $invoices = $user->invoices();

        return (new \Statamic\View\View)
            ->template('account.billing')
            ->layout('layout')
            ->with([
                'subscription' => $subscription->toArray(),
                'portal' => $portal->url,
                'subUpdate' => $subUpdate->url,
                'cancelMembership' => $cancelMembership,
                'addPaymentMethod' => $addPaymentMethod->url,
                'paymentMethods' => $paymentMethods,
                'invoices' => $invoices,
            ]);
    }
}