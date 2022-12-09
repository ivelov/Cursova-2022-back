<?php

namespace Tests\Feature;

use App\Mail\MailConferenceDeleted;
use App\Mail\MailNewListener;
use App\Models\Conferences;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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

        //With wrong authorization
        $user = User::where('role','listener')->firstOrFail();
        $response = $this->actingAs($user)->json('POST', '/add', $data);
        $response->assertStatus(403);
        
        $user = User::where('role','announcer')->firstOrFail();
        
        //Without authorization
        $response = $this->json('POST', '/add', $data);
        $response->assertStatus(403);
        
        //With authorization
        $response = $this->actingAs($user)->json('POST', '/add', $data);
        $response->assertStatus(200);

        $conferenceId = $response->id;
        Log::info($conferenceId);

        Mail::fake();

        //Deletion without authorization
        $response = $this->json('POST', "/conferences/delete/$conferenceId");
        $response->assertStatus(403);

        //Deletion
        $response = $this->actingAs($user)->json('POST', "/conferences/delete/$conferenceId");
        $response->assertStatus(200);
        Mail::assertQueued(MailConferenceDeleted::class);

        //Deletion with wrong id
        $response = $this->actingAs($user)->json('POST', "/conferences/delete/$conferenceId");
        $response->assertStatus(400);
    }

    public function testUpdate()
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
        
        $user = User::findOrFail($conference->user_id);
        
        //Without authorization
        $response = $this->json('POST', "/conference/$conference->id/save", $data);
        $response->assertStatus(403);
        
        //With authorization
        $response = $this->actingAs($user)->json('POST', "/conference/$conference->id/save", $data);
        $response->assertStatus(200);
    }

    public function testJoinAsListener()
    {        
        $conference = Conferences::firstOrFail();

        Mail::fake();

        //With wrong authorization
        $user = User::where('role','announcer')->firstOrFail();
        $response = $this->actingAs($user)->json('POST', "/conference/$conference->id/join");
        $response->assertStatus(403);
        
        //Without authorization
        $response = $this->json('POST', "/conference/$conference->id/join");
        $response->assertStatus(401);
        
        //With authorization
        $user = User::where('role','listener')->firstOrFail();
        $response = $this->actingAs($user)->json('POST', "/conference/$conference->id/join");
        $response->assertStatus(200);

        Mail::assertQueued(MailNewListener::class);
    }*/
}
