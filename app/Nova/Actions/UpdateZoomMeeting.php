<?php

namespace App\Nova\Actions;

use App\Http\Controllers\ZoomController;
use App\Models\Conferences;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;

class UpdateZoomMeeting extends Action
{
    use InteractsWithQueue, Queueable;

    /**
     * Perform the action on the given models.
     *
     * @param  \Laravel\Nova\Fields\ActionFields  $fields
     * @param  \Illuminate\Support\Collection  $models
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        foreach ($models as $model) {
            if($model->meeting_id){
                ZoomController::deleteMeeting($model->meeting_id);
                $conference = Conferences::findOrFail($model->conference_id);
                $meetingTime = date('Y-m-d H:i:s', strtotime($conference->date . ' ' . $model->start_time) - 600);
                $duration = intval((strtotime($model->end_time) - strtotime($model->start_time)) / 60) + 10;
                $meetingId = ZoomController::createMeeting($model->title, $meetingTime, $duration);
                Cache::forget('meetings');
                $model->meeting_id = $meetingId;
            }
        }
    }

    /**
     * Get the fields available on the action.
     *
     * @return array
     */
    public function fields()
    {
        return [];
    }
}
