<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;
    protected $userAnnouncer, $userListener, $userAdmin;
/*
    public function testCreate()
    {
        $this->userAnnouncer = User::create([
            'firstname' => '2',
            'lastname' => '2',
            'password' => '333333',
            'email' => '3@3.com',
            'birthdate' => '2020-08-01',
            'country' => 'usa',
            'phone' => '+380551111111',
            'role' => 'announcer',
        ]);
        $this->userListener = User::create([
            'firstname' => '2',
            'lastname' => '2',
            'password' => '333333',
            'email' => '2@2.com',
            'birthdate' => '2020-08-01',
            'country' => 'usa',
            'phone' => '+380551111111',
            'role' => 'listener',
        ]);
        $this->userAdmin = User::create([
            'firstname' => '2',
            'lastname' => '2',
            'password' => '333333',
            'email' => '1@1.com',
            'birthdate' => '2020-08-01',
            'country' => 'usa',
            'phone' => '+380551111111',
            'role' => 'admin',
        ]);

        //As wrong users
        $response = $this->actingAs($this->userListener)->json('POST', "/addCategory", ['title'=>'title']);
        $response->assertStatus(403);
        $response = $this->actingAs($this->userAnnouncer)->json('POST', "/addCategory", ['title'=>'title']);
        $response->assertStatus(403);

        //Without data
        $response = $this->actingAs($this->userAdmin)->json('POST', "/addCategory", ['title'=>null]);
        $response->assertStatus(400);

        //As admin
        $response = $this->actingAs($this->userAdmin)->json('POST', "/addCategory", ['title'=>'title']);
        $response->assertStatus(200);

        $this->updateCategory();
        $this->deleteCategory();
    }
*/
    public function updateCategory()
    {
        //As wrong users
        $response = $this->actingAs($this->userListener)->json('POST', "/category/1/save", ['title'=>'title']);
        $response->assertStatus(403);
        $response = $this->actingAs($this->userAnnouncer)->json('POST', "/category/1/save", ['title'=>'title']);
        $response->assertStatus(403);

        //With wrong id
        $response = $this->actingAs($this->userAdmin)->json('POST', "/category/3/save", ['title'=>'title']);
        $response->assertStatus(404);

        //Without data
        $response = $this->actingAs($this->userAdmin)->json('POST', "/category/1/save", ['title'=>null]);

        //As admin
        $response = $this->actingAs($this->userAdmin)->json('POST', "/category/1/save", ['title'=>'title']);
        $response->assertStatus(200);
    }

    public function deleteCategory()
    {
        //As wrong users
        $response = $this->actingAs($this->userListener)->json('POST', "/category/1/destroy");
        $response->assertStatus(403);
        $response = $this->actingAs($this->userAnnouncer)->json('POST', "/category/1/destroy");
        $response->assertStatus(403);

        //With wrong id
        $response = $this->actingAs($this->userAdmin)->json('POST', "/category/3/destroy");
        $response->assertStatus(404);

        //As admin
        $response = $this->actingAs($this->userAdmin)->json('POST', "/category/1/destroy");
        $response->assertStatus(200);
    }
}
