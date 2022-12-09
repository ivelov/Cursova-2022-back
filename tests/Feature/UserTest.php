<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;
    /*
    public function testRegister()
    {
        $response = $this->json('POST', '/register', [
            'firstname' => '3',
            'lastname' => '3',
            'password' => '333333',
            'email' => '3@3.com',
            'date' => '2020-08-01',
            'country' => 'usa',
            'phone' => '+380551111111',
            'role' => 'listener',
        ]);
        $response->assertStatus(200);

        $this->accountUpdate();
        //$this->login();
        $this->planChange();
    }*/

    /*public function login()
    {
        $response = $this->json('POST', '/login', [
            'password' => '333333',
            'email' => '3@3.com',
        ]);

        $response->assertStatus(200);
    }*/

    public function accountUpdate()
    {
        $user = User::where('email','3@3.com')->firstOrFail();

        //With authorization
        $response = $this->actingAs($user)->json('POST', '/account/save', [
            'firstname' => '3',
            'lastname' => '3',
            'password' => '333333',
            'currentPassword' => '333333',
            'email' => '3@3.com',
            'date' => '2020-08-01',
            'country' => 'usa',
            'phone' => '+380551111111',
        ]);

        $response->assertStatus(200);

        //With wrong request
        $response = $this->actingAs($user)->json('POST', '/account/save', [
            'firstname' => '3',
            'lastname' => '3',
            'password' => '333333',
            'currentPassword' => '555555',
            'email' => '3@3.com',
            'date' => '2020-08-01',
            'country' => 'usa',
            'phone' => '+380551111111',
        ]);

        $response->assertStatus(422);
    }

    public function planChange()
    {
        //Without payment
        $response = $this->json('POST', '/cashier/subscribe', [
            'plan' => 'standart'
        ]);

        $response->assertStatus(422);

        //Without plan
        $response = $this->json('POST', '/cashier/subscribe');

        $response->assertStatus(422);

        //With not existing plan
        $response = $this->json('POST', '/cashier/subscribe', [
            'plan' => 'standart2'
        ]);

        $response->assertStatus(422);
    }
}
