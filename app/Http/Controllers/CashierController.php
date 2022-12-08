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
            env('STANDART_PRICE_ID') => ['name'=>'standart', 'maxJoins'=>1],
            env('SILVER_PRICE_ID') => ['name'=>'silver', 'maxJoins'=>5],
            env('GOLDEN_PRICE_ID') => ['name'=>'golden', 'maxJoins'=>50],
            env('PLATINUM_PRICE_ID') => ['name'=>'platinum', 'maxJoins'=>-1],
        ];

        $newPlanCounter = 0;
        foreach ($plans as $planId => $plan) {
            if($request->plan == $plan['name']){

                $oldPlanCounter = 0;
                foreach ($plans as $oldPlanId => $oldPlan) {
                    if($user->subscribedToPlan($oldPlanId, 'default')){
                        break;
                    }
                    $oldPlanCounter++;
                }

                if($oldPlanCounter > $newPlanCounter){
                    $user->subscription('default')->swap($planId);
                }else if($oldPlanCounter == $newPlanCounter){
                    return response("User already on this plan", 400);
                }else{
                    //If old plan is cheaper
                    $user->subscription('default')->swapAndInvoice($planId);
                }
                return;
            }
            $newPlanCounter++;
        }
    }

    public function getIntent(){
        $user = Auth::user();
        $this->userCheck($user);

        $intent = $user->createSetupIntent();
        return $intent;
    }

    public function getPlanInfo(){
        $user = Auth::user();
        if(!$user){
            abort(401);
        }
        if($user->role === 'admin'){
            return json_encode(['admin'=>true]);
        }
        

        
        $plans = [
            env('STANDART_PRICE_ID') => ['name'=>'standart', 'maxJoins'=>1],
            env('SILVER_PRICE_ID') => ['name'=>'silver', 'maxJoins'=>5],
            env('GOLDEN_PRICE_ID') => ['name'=>'golden', 'maxJoins'=>50],
            env('PLATINUM_PRICE_ID') => ['name'=>'platinum', 'maxJoins'=>-1],
        ];
        foreach ($plans as $planId => $plan) {
            if($user->subscribedToPlan($planId, 'default')){
                return json_encode(['name'=>$plan['name'], 'availableJoins'=> $plan['maxJoins'] - UserController::getJoins($user, $plan['maxJoins'])]);
            }
        }
        Log::info('Unsubscribed user:'.$user->id);
        $user->newSubscription('default', env('STANDART_PRICE_ID'))->add();
        return json_encode(['name'=>'standart', 'availableJoins'=> UserController::getJoins($user, 1)]);
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
