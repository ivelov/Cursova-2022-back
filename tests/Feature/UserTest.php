<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;
    
    public function testRegister()
    {
        $response = $this->json('POST', '/register', [
            'firstname' => '3',
            'lastname' => '3',
            'password' => '333333',
            'email' => '3@3.com',
            'date' => '08-01-2020',
            'country' => 'usa',
            'phone' => '+380551111111',
            'role' => 'listener',
        ]);

        $response->assertStatus(200);
    }

    public function testLogin()
    {
        $response = $this->json('POST', '/login', [
            'password' => '333333',
            'email' => '3@3.com',
        ]);

        $response->assertStatus(200);
    }

    public function testLogout()
    {
        $user = User::where('role','listener')->firstOrFail();
        $response = $this->actingAs($user)->json('POST', '/logout');

        $response->assertStatus(200);
    }

    public function testAccountUpdate()
    {
        $user = User::where('email','3@3.com')->firstOrFail();

        //With authorization
        $response = $this->actingAs($user)->json('POST', '/account/save', [
            'firstname' => '3',
            'lastname' => '3',
            'password' => '333333',
            'currentPassword' => '333333',
            'email' => '3@3.com',
            'date' => '08-01-2020',
            'country' => 'usa',
            'phone' => '+380551111111',
        ]);

        $response->assertStatus(200);

        //Without authorization
        $response = $this->json('POST', '/account/save', [
            'firstname' => '3',
            'lastname' => '3',
            'password' => '333333',
            'currentPassword' => '333333',
            'email' => '3@3.com',
            'date' => '08-01-2020',
            'country' => 'usa',
            'phone' => '+380551111111',
        ]);

        $response->assertStatus(401);
    }

    public function testPlanChange()
    {
        $user = User::where('role','listener')->firstOrFail();
        
        //With authorization
        $response = $this->actingAs($user)->json('POST', '/cashier/subscribe', [
            'plan' => 'standart'
        ]);

        $response->assertStatus(200);

        //Without authorization
        $response = $this->json('POST', '/cashier/subscribe', [
            'plan' => 'standart'
        ]);

        $response->assertStatus(401);

        //Without plan
        $response = $this->actingAs($user)->json('POST', '/cashier/subscribe');

        $response->assertStatus(400);

        //With not existing plan
        $response = $this->actingAs($user)->json('POST', '/cashier/subscribe', [
            'plan' => 'standart2'
        ]);

        $response->assertStatus(400);
    }
}
