<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{

    public function created(User $user)
    {
        if($user->role != 'admin'){
            $user->createAsStripeCustomer();
            $user->newSubscription('default', env('STANDART_PRICE_ID'))->add();
        }
    }

    public function deleting(User $user)
    {
        if($user->role != 'admin'){
            $user->subscription('default')->cancelNow();
        }
    }

}
