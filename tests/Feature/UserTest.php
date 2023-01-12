<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
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
            'date' => '2020-08-01',
            'country' => 'usa',
            'phone' => '+380551111111',
            'role' => 'listener',
        ]);
        $response->assertStatus(200);
    }

    public function testUpdate()
    {
        $user = User::create([
            'firstname' => '2',
            'lastname' => '2',
            'password' => Hash::make('333333'),
            'email' => '3@3.com',
            'birthdate' => '2020-08-01',
            'country' => 'usa',
            'phone' => '+380551111111',
            'role' => 'listener',
        ]);
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

    public function testPlanChange()
    {
        $user = User::create([
            'firstname' => '2',
            'lastname' => '2',
            'password' => '333333',
            'email' => '3@3.com',
            'birthdate' => '2020-08-01',
            'country' => 'usa',
            'phone' => '+380551111111',
            'role' => 'listener',
        ]);

        //Without payment
        $response = $this->actingAs($user)->json('POST', '/cashier/subscribe', [
            'plan' => 'standart'
        ]);

        $response->assertStatus(422);

        //Without plan
        $response = $this->actingAs($user)->json('POST', '/cashier/subscribe');

        $response->assertStatus(422);

        //With not existing plan
        $response = $this->actingAs($user)->json('POST', '/cashier/subscribe', [
            'plan' => 'standart2'
        ]);

        $response->assertStatus(422);
    }
}
