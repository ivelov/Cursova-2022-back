<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CashierController extends Controller
{
    public function subscribe(Request $request){
        if(!$request->payment){
            abort(400);
        }

        $user = Auth::user();
        if(!$user){
            abort(401);
        }

        $user->createOrGetStripeCustomer();
        $user->updateDefaultPaymentMethod($request->payment);
        $user->newSubscription('default', env('SILVER_PRICE_ID'))->create($request->payment);
    }

    public function getIntent(){
        $user = Auth::user();
        if(!$user){
            abort(401);
        }
        $user->createOrGetStripeCustomer();
        $intent = $user->createSetupIntent();
        return $intent;
    }

}
