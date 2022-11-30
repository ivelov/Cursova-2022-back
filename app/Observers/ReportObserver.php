<?php

namespace App\Observers;

use App\Http\Controllers\MailController;
use App\Http\Controllers\ZoomController;
use App\Models\Conferences;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ReportObserver
{

    /**
     * Handle the Report "deleted" event.
     *
     * @param  \App\Models\Report  $report
     * @return void
     */
    public function deleted(Report $report)
    {
        if(isset($report->meeting_id)){
            ZoomController::deleteMeeting($report->meeting_id);
        }
        if(isset($report->presentation)){
            Log::info('Trying to delete presentation');
            Storage::disk('public')->delete($report->presentation);
        }
    }

    public function creating(Report $report)
    {
        if($report->is_online){
            $conference = Conferences::findOrFail($report->conference_id);
            $meetingTime = date('Y-m-d H:i:s', strtotime($conference->date . ' ' . $report->start_time) - 600);
            $duration = intval((strtotime($report->end_time) - strtotime($report->start_time)) / 60) + 10;
            $meetingId = ZoomController::createMeeting($report->title, $meetingTime, $duration);
            Cache::forget('meetings');
            $report->meeting_id = $meetingId;
        }
        
    }

    public function updating(Report $report)
    {        
        if($report->isDirty('is_online')){
            if($report->is_online){
                $conference = Conferences::findOrFail($report->conference_id);
                $meetingTime = date('Y-m-d H:i:s', strtotime($conference->date . ' ' . $report->start_time) - 600);
                $duration = intval((strtotime($report->end_time) - strtotime($report->start_time)) / 60) + 10;
                $meetingId = ZoomController::createMeeting($report->title, $meetingTime, $duration);
                Cache::forget('meetings');
                $report->meeting_id = $meetingId;
            }else{
                if($report->meeting_id){
                    ZoomController::deleteMeeting($report->meeting_id);
                    $report->meeting_id = null;
                }
            }            
            
        }
        
    }

    public function updated(Report $report)
    {
        if($report->wasChanged('start_time') || $report->wasChanged('end_time')){
            MailController::reportTimeChange(User::findOrFail($report->user_id), $report->id, $report->conference_id);
        }
    }
}
