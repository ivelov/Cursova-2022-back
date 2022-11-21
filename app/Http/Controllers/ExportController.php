<?php

namespace App\Http\Controllers;

use App\Jobs\ExportCommentsJob;
use App\Jobs\ExportConferencesJob;
use App\Jobs\ExportListenersJob;
use App\Jobs\ExportReportsJob;
use Illuminate\Support\Facades\Auth;

class ExportController extends Controller
{
    /**
     * Export conferences to csv
     *
     * @return bool
     */
    public function exportConferences()
    {
        $user = Auth::user();
        if (!$user){
            abort(403);
        }

        ExportConferencesJob::dispatch($user->id)->delay(now()->addSeconds(5));
        return true;
    }

    /**
     * Export reports of chosen conference to csv
     *
     * @return bool
     */
    public function exportReports($conferenceId)
    {
        $user = Auth::user();
        if (!$user){
            abort(403);
        }

        ExportReportsJob::dispatch($user->id, $conferenceId)->delay(now()->addSeconds(5));
        return true;
    }

    /**
     * Export listeners of chosen conference to csv
     *
     * @return bool
     */
    public function exportListeners($conferenceId)
    {
        $user = Auth::user();
        if (!$user){
            abort(403);
        }

        ExportListenersJob::dispatch($user->id, $conferenceId)->delay(now()->addSeconds(5));
        return true;
    }

    /**
     * Export listeners of chosen conference to csv
     *
     * @return bool
     */
    public function exportComments($reportId)
    {
        $user = Auth::user();
        if (!$user){
            abort(403);
        }

        ExportCommentsJob::dispatch($user->id, $reportId)->delay(now()->addSeconds(5));
        return true;
    }
}
