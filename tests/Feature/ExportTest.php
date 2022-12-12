<?php

namespace Tests\Feature;

use App\Jobs\ExportCommentsJob;
use App\Jobs\ExportConferencesJob;
use App\Jobs\ExportListenersJob;
use App\Jobs\ExportReportsJob;
use App\Models\Comment;
use App\Models\Conferences;
use App\Models\Listener;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class ExportTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;
   
    public function testStart()
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
        $conferenceData = [
            'title' => '1',
            'country' => 'usa',
            'latitude' => '0',
            'longitude' => '0',
            'date' => date('Y-m-d'),
            'time' => '8:00:00',
            'user_id' => $user->id,
            'category_id' => null
        ];
        $conference = Conferences::create($conferenceData);
        

        Bus::fake();

        $response = $this->actingAs($user)->json('POST', "/export/conferences");
        $response->assertStatus(200);
        Bus::assertDispatched(ExportConferencesJob::class);


        $report = Report::create([
            'title' => 'title',
            'description' => 'description',
            'start_time' => '8:00',
            'end_time' => '9:00',
            'conference_id' => $conference->id,
            'category_id' => null,
            'presentation' => null,
            'user_id' => $user->id,
            'meeting_id' => null,
        ]);
        $response = $this->actingAs($user)->json('POST', "/export/conference/$conference->id/reports");
        $response->assertStatus(200);
        Bus::assertDispatched(ExportReportsJob::class);


        Listener::create([
            'user_id' => $user->id,
            'conference_id' => $conference->id
        ]);
        $response = $this->actingAs($user)->json('POST', "/export/conference/$conference->id/listeners");
        $response->assertStatus(200);
        Bus::assertDispatched(ExportListenersJob::class);


        Comment::create([
            'user_id' => $user->id,
            'report_id' => $report->id,
            'text' => 'tewxt',
        ]);
        $response = $this->actingAs($user)->json('POST', "/export/report/$report->id/comments");
        $response->assertStatus(200);
        Bus::assertDispatched(ExportCommentsJob::class);
    }
}
