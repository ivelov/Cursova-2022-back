<?php

namespace Tests\Feature;

use App\Jobs\MailJob;
use App\Mail\MailConferenceDeleted;
use App\Mail\MailNewListener;
use App\Models\Conferences;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ConferenceTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;

    /*public function testCreate()
    {
        $data = [
            'title' => '1',
            'country' => 'usa',
            'latitude' => '0',
            'longitude' => '0',
            'date' => date('Y-m-d'),
            'time' => '8:00:00'
        ];
        
        //Without authorization
        $response = $this->json('POST', '/add', $data);
        $response->assertStatus(403);
        
        //With wrong authorization
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
        $response = $this->json('POST', '/add', $data);
        $response->assertStatus(403);
        
        //With authorization
        $response = $this->json('POST', '/register', [
            'firstname' => '2',
            'lastname' => '2',
            'password' => '333333',
            'email' => '2@2.com',
            'date' => '2020-08-01',
            'country' => 'usa',
            'phone' => '+380551111111',
            'role' => 'announcer',
        ]);
        $response->assertStatus(200);
        $response = $this->json('POST', '/add', $data);
        $response->assertStatus(200);
        
        $this->deleteConference();
        $user = User::where('role','announcer')->firstOrFail();
        $response = $this->actingAs($user)->json('POST', '/add', $data);
        $response->assertStatus(200);
        $this->update();
        $this->joinAsListener();
    }*/

    public function deleteConference()
    {
        Bus::fake();

        //Deletion with wrong authorization
        $user = User::where('role','listener')->firstOrFail();
        $response = $this->actingAs($user)->json('POST', "/conferences/delete/1");
        $response->assertStatus(403);

        //Deletion
        $response = $this->json('POST', '/register', [
            'firstname' => '1',
            'lastname' => '1',
            'password' => '333333',
            'email' => '1@1.com',
            'date' => '2020-08-01',
            'country' => 'usa',
            'phone' => '+380551111111',
            'role' => 'admin',
        ]);
        $response->assertStatus(200);
        $response = $this->json('POST', "/conferences/delete/1");
        $response->assertStatus(200);
        Bus::assertDispatched(function (MailJob $job){
            return $job->mail::class === MailConferenceDeleted::class;
        });

        //Deletion with wrong id
        $response = $this->actingAs($user)->json('POST', "/conferences/delete/1");
        $response->assertStatus(403);
    }

    public function update()
    {
        $data = [
            'title' => '1',
            'country' => 'usa',
            'latitude' => '0',
            'longitude' => '0',
            'date' => date('Y-m-d'),
            'time' => '8:00:00'
        ];
        $conference = Conferences::firstOrFail();
        
        //With wrong authorization
        $user = User::where('role','listener')->firstOrFail();
        $response = $this->actingAs($user)->json('POST', "/conference/$conference->id/save", $data);
        $response->assertStatus(403);
        
        //With authorization
        $user = User::findOrFail($conference->user_id);
        $response = $this->actingAs($user)->json('POST', "/conference/$conference->id/save", $data);
        $response->assertStatus(200);
    }

    public function joinAsListener()
    {        
        $conference = Conferences::firstOrFail();

        //With wrong authorization
        $user = User::where('role','announcer')->firstOrFail();
        $response = $this->actingAs($user)->json('POST', "/conference/$conference->id/join");
        $response->assertStatus(403);
        
        //With authorization
        $user = User::where('role','listener')->firstOrFail();
        $response = $this->actingAs($user)->json('POST', "/conference/$conference->id/join");
        $response->assertStatus(200);
    }

    public function testSearch()
    {
        $user = User::create([
            'firstname' => '2',
            'lastname' => '2',
            'password' => '333333',
            'email' => '1@1.com',
            'birthdate' => '2020-08-01',
            'country' => 'usa',
            'phone' => '+380551111111',
            'role' => 'admin',
        ]);

        Conferences::create([
            'title' => '1',
            'country' => 'usa',
            'latitude' => '0',
            'longitude' => '0',
            'date' => date('Y-m-d'),
            'time' => '8:00:00',
            'user_id' => $user->id,
            'category_id' => null
        ]);

        $response = $this->json('POST', "/conferencesFind", ['searchText' => '1']);
        $response->assertStatus(200)->assertJson([
            [
                'title' => '1',
            ],
        ]);

        $response = $this->json('POST', "/conferencesFind", ['searchText' => '22']);
        $response->assertStatus(200)->assertExactJson([ ]);
    }

    public function testFilter()
    {
        $user = User::create([
            'firstname' => '2',
            'lastname' => '2',
            'password' => '333333',
            'email' => '1@1.com',
            'birthdate' => '2020-08-01',
            'country' => 'usa',
            'phone' => '+380551111111',
            'role' => 'announcer',
        ]);
        $data = [
            'title' => '1',
            'country' => 'usa',
            'latitude' => '0',
            'longitude' => '0',
            'date' => date('Y-m-d'),
            'time' => '8:00:00',
            'user_id' => $user->id,
            'category_id' => null
        ];

        Conferences::create($data);
        $tomorrow = date('Y-m-d', time()+86400);
        $data['date'] = $tomorrow;
        $data['title'] = '2';
        Conferences::create($data);

        $response = $this->actingAs($user)->json('POST', "/conferences/1", ['endDate' => date('Y-m-d')]);
        $response->assertStatus(200)->assertJson([
            'conferences' => [[
                'id' => 1,
                'canEdit' => true,
                'title' => '1',
                'date' => date('Y-m-d'),
                'participant' => false,
            ]],
        ]);

        $response = $this->actingAs($user)->json('POST', "/conferences/1", ['startDate' => $tomorrow]);
        $response->assertStatus(200)->assertJson([
            'conferences' => [[
                'id' => 2,
                'canEdit' => true,
                'title' => '2',
                'date' => $tomorrow,
                'participant' => false,
            ]],
        ]);
    }
}
