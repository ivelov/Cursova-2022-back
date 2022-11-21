<?php

namespace App\Observers;

use App\Http\Controllers\ZoomController;
use App\Models\Report;

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
    }

}
