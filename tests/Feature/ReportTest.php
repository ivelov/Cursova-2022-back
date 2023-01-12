<?php

namespace Tests\Feature;

use App\Jobs\MailJob;
use App\Mail\MailReportDeleted;
use App\Models\Conferences;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;

    protected $userAnnouncer, $userListener, $userAdmin, $reportId;

    protected function getReportId(){
        if($this->reportId){
            return $this->reportId;
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
        
        return $this->reportId;
    }

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

        $conference = Conferences::create([
            'title' => '1',
            'country' => 'usa',
            'latitude' => '0',
            'longitude' => '0',
            'date' => date('Y-m-d', time()+86400),
            'time' => '8:00:00',
            'user_id' => $this->userAnnouncer->id
        ]);

        $data = [
            'report' => json_encode([
                'title' => '1',
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
        
       /* $this->updateReport();
        $this->favorite();
        $this->searchReport();*/
    }

    public function testDelete()
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
        $reportId = Report::create([
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

        //As wrong user
        $response = $this->actingAs($this->userListener)->json('POST', "/reports/delete/$conference->id", ['reportId'=>$reportId]);
        $response->assertStatus(403);

        //As right user
        $response = $this->actingAs($this->userAnnouncer)->json('POST', "/reports/delete/$conference->id", ['reportId'=>$reportId]);
        $response->assertStatus(200);
                
        //With wrong id
        $response = $this->actingAs($this->userAnnouncer)->json('POST', "/reports/delete/$conference->id", ['reportId'=>50]);
        $response->assertStatus(404);

        Bus::fake();

        $reportId = Report::create([
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

        //As admin
        $response = $this->actingAs($this->userAdmin)->json('POST', "/reports/delete/$conference->id", ['reportId'=>$reportId]);
        $response->assertStatus(200);

        Bus::assertDispatched(function (MailJob $job){
            return $job->mail::class === MailReportDeleted::class;
        });
    }

    public function updateReport()
    {
        if(!$this->userAnnouncer){
            $this->createUsers();
        }

        $reportId = $this->getReportId();

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
        $response = $this->actingAs($this->userListener)->json('POST', "/report/$reportId/save", $data);
        $response->assertStatus(403);

        //As right user
        $response = $this->actingAs($this->userAnnouncer)->json('POST', "/report/$reportId/save", $data);
        $response->assertStatus(200);
                
        //As admin
        $response = $this->actingAs($this->userAdmin)->json('POST', "/report/$reportId/save", $data);
        $response->assertStatus(200);

        //With wrong id
        $response = $this->actingAs($this->userAdmin)->json('POST', "/report/50/save", $data);
        $response->assertStatus(404);
    }

    public function testFavorite()
    {
        if(!$this->userAnnouncer){
            $this->createUsers();
        }
        $reportId = $this->getReportId();

        //As listener
        $response = $this->actingAs($this->userListener)->json('POST', "/report/$reportId/favorite");
        $response->assertStatus(201);
        $response = $this->actingAs($this->userListener)->json('POST', "/report/$reportId/unfavorite");
        $response->assertStatus(200);

        //As announcer
        $response = $this->actingAs($this->userAnnouncer)->json('POST', "/report/$reportId/favorite");
        $response->assertStatus(201);
        $response = $this->actingAs($this->userAnnouncer)->json('POST', "/report/$reportId/unfavorite");
        $response->assertStatus(200);

        //As admin
        $response = $this->actingAs($this->userAdmin)->json('POST', "/report/$reportId/favorite");
        $response->assertStatus(201);
        $response = $this->actingAs($this->userAdmin)->json('POST', "/report/$reportId/unfavorite");
        $response->assertStatus(200);

        //With wrong id
        $response = $this->actingAs($this->userAdmin)->json('POST', "/report/50/favorite");
        $response->assertStatus(404);
        
    }
    
    public function searchReport()
    {
        $this->getReportId();

        $response = $this->json('POST', "/reportsFind", ['searchText' => '1']);
        $response->assertStatus(200)->assertJson([
            [
                'title' => '1',
            ],
        ]);

        $response = $this->json('POST', "/reportsFind", ['searchText' => 'vxcx']);
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

        $conference = Conferences::create([
            'title' => '1',
            'country' => 'usa',
            'latitude' => '0',
            'longitude' => '0',
            'date' => '2022-10-10',
            'time' => '8:00:00',
            'user_id' => $user->id,
            'category_id' => null
        ]);

        $reportId1 = Report::create([
            'title' => '1',
            'description' => 'description',
            'start_time' => '8:00',
            'end_time' => '9:00',
            'conference_id' => $conference->id,
            'category_id' => null,
            'presentation' => null,
            'user_id' => $user->id,
            'meeting_id' => null,
        ])->id;
        $reportId2 = Report::create([
            'title' => '2',
            'description' => 'description',
            'start_time' => '9:00',
            'end_time' => '10:00',
            'conference_id' => $conference->id,
            'category_id' => null,
            'presentation' => null,
            'user_id' => $user->id,
            'meeting_id' => null,
        ])->id;

        $response = $this->actingAs($user)->json('POST', "/reports/1", ['endTime' => '09:00']);
        $response->assertStatus(200)->assertJson([
            'reports' => [[
                'id' =>  $reportId1,
                'title' => '1',
                'startTime' => '08:00:00',
                'endTime' => '09:00:00',
            ]],
        ]);

        $response = $this->actingAs($user)->json('POST', "/reports/1", ['startTime' => '09:00']);
        $response->assertStatus(200)->assertJson([
            'reports' => [[
                'id' =>  $reportId2,
                'title' => '2',
                'startTime' => '09:00:00',
                'endTime' => '10:00:00',
            ]],
        ]);
    }
}
