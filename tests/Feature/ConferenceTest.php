<?php

namespace Tests\Feature;

use App\Jobs\MailJob;
use App\Mail\MailConferenceDeleted;
use App\Models\Conferences;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class ConferenceTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;

    protected $userAnnouncer, $userListener, $userAdmin, $conference;

    protected function createUsers()
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
    }

    public function testCreate()
    {
        if(!$this->userAnnouncer){
            $this->createUsers();
        }

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
        $response = $this->actingAs($this->userListener)->json('POST', '/add', $data);
        $response->assertStatus(403);
        
        //With authorization
        $response = $this->actingAs($this->userAnnouncer)->json('POST', '/add', $data);
        $response->assertStatus(200);
    }

    public function testDelete()
    {
        if(!$this->userAnnouncer){
            $this->createUsers();
        }

        Bus::fake();

        $conference = Conferences::create([
            'title' => '1',
            'country' => 'usa',
            'latitude' => '0',
            'longitude' => '0',
            'date' => date('Y-m-d'),
            'time' => '8:00:00',
            'user_id' => $this->userAnnouncer->id,
            'category_id' => null
        ]);

        //Deletion with wrong authorization
        $response = $this->actingAs($this->userListener)->json('POST', "/conferences/delete/$conference->id");
        $response->assertStatus(403);

        //Deletion
        $response = $this->actingAs($this->userAdmin)->json('POST', "/conferences/delete/$conference->id");
        $response->assertStatus(200);
        Bus::assertDispatched(function (MailJob $job){
            return $job->mail::class === MailConferenceDeleted::class;
        });

        //Deletion with wrong id
        $response = $this->actingAs($this->userAdmin)->json('POST', "/conferences/delete/$conference->id");
        $response->assertStatus(404);
    }

    public function testUpdate()
    {
        if(!$this->userAnnouncer){
            $this->createUsers();
        }

        $data = [
            'title' => '1',
            'country' => 'usa',
            'latitude' => '0',
            'longitude' => '0',
            'date' => date('Y-m-d'),
            'time' => '8:00:00'
        ];

        $conference = Conferences::create([
            'title' => '1',
            'country' => 'usa',
            'latitude' => '0',
            'longitude' => '0',
            'date' => date('Y-m-d'),
            'time' => '8:00:00',
            'user_id' => $this->userAnnouncer->id,
            'category_id' => null
        ]);
        
        //With wrong authorization
        $response = $this->actingAs($this->userListener)->json('POST', "/conference/$conference->id/save", $data);
        $response->assertStatus(403);
        
        //With authorization
        $response = $this->actingAs($this->userAnnouncer)->json('POST', "/conference/$conference->id/save", $data);
        $response->assertStatus(200);
    }

    public function testJoinAsListener()
    {        
        if(!$this->userAnnouncer){
            $this->createUsers();
        }

        $conference = Conferences::create([
            'title' => '1',
            'country' => 'usa',
            'latitude' => '0',
            'longitude' => '0',
            'date' => date('Y-m-d'),
            'time' => '8:00:00',
            'user_id' => $this->userAnnouncer->id,
            'category_id' => null
        ]);

        //With wrong authorization
        $response = $this->actingAs($this->userAnnouncer)->json('POST', "/conference/$conference->id/join");
        $response->assertStatus(403);
        
        //With authorization
        $response = $this->actingAs($this->userListener)->json('POST', "/conference/$conference->id/join");
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

        $conferenceId1 = Conferences::create($data)->id;
        $tomorrow = date('Y-m-d', time()+86400);
        $data['date'] = $tomorrow;
        $data['title'] = '2';
        $conferenceId2 = Conferences::create($data)->id;

        $response = $this->actingAs($user)->json('POST', "/conferences/1", ['endDate' => date('Y-m-d')]);
        $response->assertStatus(200)->assertJson([
            'conferences' => [[
                'id' => $conferenceId1,
                'canEdit' => true,
                'title' => '1',
                'date' => date('Y-m-d'),
                'participant' => false,
            ]],
        ]);

        $response = $this->actingAs($user)->json('POST', "/conferences/1", ['startDate' => $tomorrow]);
        $response->assertStatus(200)->assertJson([
            'conferences' => [[
                'id' => $conferenceId2,
                'canEdit' => true,
                'title' => '2',
                'date' => $tomorrow,
                'participant' => false,
            ]],
        ]);
    }
}
