<?php

use Stripe\Plan;
use Stripe\Stripe;
use Illuminate\Support\Facades\Route;

use Statamic\Http\Middleware\AuthGuard;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
 */
Route::name('wcas.')->group(function () {

   Route::group(['prefix' => "_".config('statamic.routes.action')], function () {
      Route::group(['prefix' => 'auth', 'middleware' => [AuthGuard::class]], function () {
         Route::post('register', [UserController::class, 'register'])->name('register');
      });
   });

   Route::get('join', [UserController::class, 'join'])->name('join')->middleware('guest');
   Route::get('join/subscribe', [UserController::class, 'subscribe'])->name('subscribe')->middleware('needs-subscription');
   Route::post('join/subscribe', [UserController::class, 'doSubscribe'])->name('doSubscribe');
});


 Route::get('test', function () {
    // Set your secret key. Remember to switch to your live secret key in production.
   // See your keys here: https://dashboard.stripe.com/apikeys
   $stripe = new \Stripe\StripeClient(config('cashier.secret'));


   $session = $stripe->billingPortal->sessions->create([
      'customer' => Auth::user()->stripe_id,
    ]);

    return redirect($session->url);
 });

Route::name('account.')->group(function() {
   Route::group(['prefix' => 'account'], function () {
      Route::group(['middleware' => [AuthGuard::class]], function () {
         Route::get('billing', [UserController::class, 'billing'])->name('billing');
      });
   });
});
