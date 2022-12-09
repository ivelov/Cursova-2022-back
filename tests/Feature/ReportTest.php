<?php

namespace Tests\Feature;

use App\Jobs\MailJob;
use App\Mail\MailReportDeleted;
use App\Models\Conferences;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;

    protected $userAnnouncer, $userListener, $userAdmin;

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
            'user_id' => 1
        ]);

        $data = [
            'report' => json_encode([
                'title' => 'title',
                'description' => 'description',
                'startTime' => '8:00',
                'endTime' => '9:00',
                'conferenceId' => $conference->id,
                'categoryId' => null,
                'isOnline' => false,
            ]),
        ];

        //With wrong authorization
        $response = $this->actingAs($this->userListener)->json('POST', "addReport", $data);
        $response->assertStatus(403);
        
        //With authorization
        $response = $this->actingAs($this->userAnnouncer)->json('POST', "addReport", $data);
        $response->assertStatus(200);

        //Creating second for deleting
        $response = $this->actingAs($this->userAnnouncer)->json('POST', "addReport", $data);
        $response->assertStatus(200);
        
        $this->updateReport();
        $this->favorite();
        $this->deleteReport();
    }

    public function deleteReport()
    {
        //As wrong user
        $response = $this->actingAs($this->userListener)->json('POST', "/reports/delete/1", ['reportId'=>1]);
        $response->assertStatus(403);

        //As right user
        $response = $this->actingAs($this->userAnnouncer)->json('POST', "/reports/delete/1", ['reportId'=>1]);
        $response->assertStatus(200);
                
        //With wrong id
        $response = $this->actingAs($this->userAnnouncer)->json('POST', "/reports/delete/1", ['reportId'=>1]);
        $response->assertStatus(404);

        Bus::fake();

        //As admin
        $response = $this->actingAs($this->userAdmin)->json('POST', "/reports/delete/1", ['reportId'=>2]);
        $response->assertStatus(200);

        Bus::assertDispatched(function (MailJob $job){
            return $job->mail::class === MailReportDeleted::class;
        });
    }

    public function updateReport()
    {
        $data = [
            'report' => json_encode([
                'title' => 'title2',
                'description' => 'description2',
                'startTime' => '8:00',
                'endTime' => '9:00',
                'categoryId' => null,
                'isOnline' => false,
            ]),
        ];

        //As wrong user
        $response = $this->actingAs($this->userListener)->json('POST', "/report/1/save", $data);
        $response->assertStatus(403);

        //As right user
        $response = $this->actingAs($this->userAnnouncer)->json('POST', "/report/1/save", $data);
        $response->assertStatus(200);
                
        //As admin
        $response = $this->actingAs($this->userAdmin)->json('POST', "/report/1/save", $data);
        $response->assertStatus(200);

        //With wrong id
        $response = $this->actingAs($this->userAdmin)->json('POST', "/report/3/save", $data);
        $response->assertStatus(404);
    }

    public function favorite()
    {
        //As listener
        $response = $this->actingAs($this->userListener)->json('POST', "/report/1/favorite");
        $response->assertStatus(201);
        $response = $this->actingAs($this->userListener)->json('POST', "/report/1/unfavorite");
        $response->assertStatus(200);

        //As announcer
        $response = $this->actingAs($this->userAnnouncer)->json('POST', "/report/1/favorite");
        $response->assertStatus(201);
        $response = $this->actingAs($this->userAnnouncer)->json('POST', "/report/1/unfavorite");
        $response->assertStatus(200);

        //As admin
        $response = $this->actingAs($this->userAdmin)->json('POST', "/report/1/favorite");
        $response->assertStatus(201);
        $response = $this->actingAs($this->userAdmin)->json('POST', "/report/1/unfavorite");
        $response->assertStatus(200);

        //With wrong id
        $response = $this->actingAs($this->userAdmin)->json('POST', "/report/3/favorite");
        $response->assertStatus(404);
        
    }
}
