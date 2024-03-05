<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;

class TerminalController extends Controller
{
    public function index()
    {
        Stripe::setApiKey(config('cashier.secret'));
        $stripe = new \Stripe\StripeClient('sk_test_51N0yC6BM4bHr9bNA7aRTfYVHGCXejiXlmY0Ll1vb4nm4Gd9jiscAXkF4sZGLwLvWLIatHlJi0F52xGqkAMsAbJnt007EhQbeBG');
        $products = $stripe->products->all(['expand' => ['data.default_price']])->toArray();





        return view(
            'terminal.index',
            [
                'products' => $products['data']
            ]
        );
    }
}
