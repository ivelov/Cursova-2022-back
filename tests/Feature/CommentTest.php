<?php

namespace Tests\Feature;

use App\Jobs\MailJob;
use App\Mail\MailNewComment;
use App\Models\Conferences;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;
    protected $userAnnouncer, $userListener, $userAdmin, $reportId;
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
        $this->reportId = Report::create([
            'title' => 'title',
            'description' => 'description',
            'start_time' => '8:00',
            'end_time' => '9:00',
            'conference_id' => $conference->id,
            'category_id' => null,
            'presentation' => null,
            'user_id' => $this->userAnnouncer->id,
            'meeting_id' => null,
        ])->id;

        $data = [
            'text' => 'text',
            'reportId' => 1
        ];

        //With wrong data
        $response = $this->actingAs($this->userAdmin)->json('POST', "/report/$this->reportId/addComment", ['text'=>'text']);
        $response->assertStatus(422);

        Bus::fake();

        //As listener
        $response = $this->actingAs($this->userListener)->json('POST', "/report/$this->reportId/addComment", $data);
        $response->assertStatus(200);
        Bus::assertDispatched(function (MailJob $job){
            return $job->mail::class === MailNewComment::class;
        });
        
        //As announcer
        $response = $this->actingAs($this->userAnnouncer)->json('POST', "/report/$this->reportId/addComment", $data);
        $response->assertStatus(200);
        Bus::assertDispatched(function (MailJob $job){
            return $job->mail::class === MailNewComment::class;
        });

        //As admin
        $response = $this->actingAs($this->userAdmin)->json('POST', "/report/$this->reportId/addComment", $data);
        $response->assertStatus(200);
        Bus::assertDispatched(function (MailJob $job){
            return $job->mail::class === MailNewComment::class;
        });

        $this->updateComment();
    }
*/
    public function updateComment()
    {
        $data=[
            'id' => 1,
            'text' => 'text2'
        ];

        //Wrong user
        $response = $this->actingAs($this->userAnnouncer)->json('POST', "/report/$this->reportId/updateComment", $data);
        $response->assertStatus(403);
        
        //Correct user
        $response = $this->actingAs($this->userListener)->json('POST', "/report/$this->reportId/updateComment", $data);
        $response->assertStatus(200);

        //As admin
        $response = $this->actingAs($this->userAdmin)->json('POST', "/report/$this->reportId/updateComment", $data);
        $response->assertStatus(200);
    }
}
