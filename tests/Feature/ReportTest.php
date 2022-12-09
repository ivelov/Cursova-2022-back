<?php

namespace Tests\Feature;

use App\Mail\MailNewAnnouncer;
use App\Models\Conferences;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;

    /*public function testCreate()
    {       
        Mail::fake();
        Storage::fake();

        $conference = Conferences::firstOrFail();
        $data = [
            'title' => 'title',
            'description' => 'description',
            'start_time' => '8:00',
            'end_time' => '9:00',
            'presentation' => UploadedFile::fake()->create('presentation.pptx', 5000),
            'conference_id' => $conference->id,
            'category_id' => null,
            'type' => '.pptx',
            'isOnline' => false,
        ];
        
        //With wrong authorization
        $user = User::where('role','listener')->firstOrFail();
        $response = $this->actingAs($user)->json('POST', "addReport", $data);
        $response->assertStatus(403);
        
        //Without authorization
        $response = $this->json('POST', "addReport", $data);
        $response->assertStatus(403);
        
        //With authorization
        $user = User::where('role','announcer')->firstOrFail();
        $response = $this->actingAs($user)->json('POST', "addReport", $data);
        $response->assertStatus(200);

        Mail::assertQueued(MailNewAnnouncer::class);

        $reportId = $response->data;
        Log::info($reportId);

        //Deletion
        $response = $this->actingAs($user)->json('POST', "/reports/delete/$reportId", ['reportId'=>$reportId]);
        $response->assertStatus(200);

        //Deletion with wrong id
        $response = $this->actingAs($user)->json('POST', "/reports/delete/$reportId", ['reportId'=>$reportId]);
        $response->assertStatus(500);

        //Deletion as admin
        $user = User::where('role','admin')->firstOrFail();
        $response = $this->actingAs($user)->json('POST', "addReport", $data);
        $reportId = $response->data;
        $response = $this->actingAs($user)->json('POST', "/reports/delete/$reportId", ['reportId'=>$reportId]);
        $response->assertStatus(200);
        
    }*/
}
