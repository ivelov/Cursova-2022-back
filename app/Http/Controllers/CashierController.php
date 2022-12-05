<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CashierController extends Controller
{
    public function subscribe(Request $request){
        if(!$request->plan){
            return response("Plan is required", 400);
        }
 
        $user = Auth::user();
        $this->userCheck($user);

        if($request->payment){
            $user->updateDefaultPaymentMethod($request->payment);
        }
        
        $plans = [
            env('STANDART_PRICE_ID') => 'standart',
            env('SILVER_PRICE_ID') => 'silver',
            env('GOLDEN_PRICE_ID') => 'golden',
            env('PLATINUM_PRICE_ID') => 'platinum',
        ];

        $newPlan = 0;
        foreach ($plans as $planId => $planName) {
            if($request->plan == $planName){
                if ($user->subscribed('default')) {

                    $oldPlan = 0;
                    foreach ($plans as $oldPlanId => $oldPlanName) {
                        if($user->subscribedToPlan($oldPlanId, 'default')){
                            break;
                        }
                        $oldPlan++;
                    }
                    if($oldPlan > $newPlan){
                        $user->subscription('default')->swap($planId);
                    }else if($oldPlan == $newPlan){
                        return response("User already on this plan", 400);
                    }else{
                        //If old plan is cheaper
                        $user->subscription('default')->swapAndInvoice($planId);
                    }
                    
                }else{
                    $user->newSubscription('default', $planId)->add();
                }
                return;
            }
            $newPlan++;
        }
    }

    public function getIntent(){
        $user = Auth::user();
        $this->userCheck($user);

        $intent = $user->createSetupIntent();
        return $intent;
    }

    public function getPlan(){
        $user = Auth::user();
        $this->userCheck($user);

        $plans = [
            env('STANDART_PRICE_ID') => 'standart',
            env('SILVER_PRICE_ID') => 'silver',
            env('GOLDEN_PRICE_ID') => 'golden',
            env('PLATINUM_PRICE_ID') => 'platinum',
        ];
        foreach ($plans as $planId => $planName) {
            if($user->subscribedToPlan($planId, 'default')){
                return $planName;
            }
        }
        abort(401,'User not subscribed');
    }

    public function userCheck(\Illuminate\Contracts\Auth\Authenticatable $user){
        if(!$user){
            abort(401);
        }
        if($user->role === 'admin'){
            abort(401,'Admin is not allowed');
        }
    }

}
