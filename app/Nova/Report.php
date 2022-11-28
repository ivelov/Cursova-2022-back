<?php

namespace App\Nova;

use App\Models\Conferences;
use App\Models\Report as ModelsReport;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\File;
use Laravel\Nova\Fields\Hidden;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laraning\NovaTimeField\TimeField;

class Report extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\Report::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'title';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'title',
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    {
        return [
            ID::make(__('ID'), 'id')->sortable(),

            Text::make('Title')
                ->sortable()
                ->rules('required', 'max:255'),

            Hidden::make('start_date', function () {
                if($this->conference){
                    return  $this->conference->date . ' ' . $this->start_time;
                }else{
                    return  date('Y-m-d H:i:s');
                }
            }),

            TimeField::make('Start time')
                ->sortable()
                ->rules('required'),
               
            TimeField::make('End time')
                ->sortable()
                ->rules('required', 'after:start_time', 'before:21:00'),
            
            Textarea::make('Description')->nullable(),

            File::make('Presentation')
                ->acceptedTypes('.pptx,.ppt')
                ->disk('public')
                ->path('presentations')
                ->nullable()
                ->rules('max:10000'),

            BelongsTo::make('User')
                ->rules('required'),

            BelongsTo::make('Conference')
                ->rules('required'),
                
            BelongsTo::make('Category')
                ->nullable(),

            Boolean::make('Online', 'is_online'),

            Number::make('Meeting Id')
                ->nullable()
                ->hideWhenCreating()
                ->hideWhenUpdating(),

        ];
    }

    protected static function afterValidation(NovaRequest $request, $validator)
    {
        $conference = Conferences::findOrFail($request->conference);
        $conferenceStartTime = strtotime($conference->time) + env('TIMEZONE_DIFF_SECONDS', 7200);
        $startTime = strtotime($request->start_time);
        $endTime = strtotime($request->end_time);
        
        if($startTime < $conferenceStartTime){
            $validator->errors()->add(
                'start_time',
                'Report start time must be after conference start time'
            );
        }
        if($endTime - $startTime < 300){
            $validator->errors()->add(
                'end_time',
                'Report minimal duration is 5 min.'
            );
        } else if($endTime - $startTime > 3600){
            $validator->errors()->add(
                'start_time',
                'Report maximal duration is 60 min.'
            );
        }

        if($request->editMode == 'create'){
            $reports = ModelsReport::where('conference_id', $request->conference)->get();
        }else{
            $reports = ModelsReport::where('conference_id', $request->conference)
            ->where('title', '<>', $request->title)
            ->get();
        }
        
        $startTimes = collect();
        $endTimes = collect();
        
        foreach ($reports as $report) {
            $startTimes->push(strtotime($report->start_time));
            $endTimes->push(strtotime($report->end_time));
        }
        $startTimes = $startTimes->sort();
        $endTimes = $endTimes->sort();
        


        $timesCount = $startTimes->count();
        $nearestConflict = false;
        //Check if report time conflicts with
        for ($i = 0; $i < $timesCount; $i++) {
            if($startTime > $startTimes[$i]){
                if($startTime < $endTimes[$i]){
                    $nearestConflict = $i;
                    break;
                }
            }else if($startTimes[$i] < $endTime){ //if another report in a report interval
                $nearestConflict = $i;
                break;
            }
            if($endTime > $startTimes[$i]){
                if($endTime < $endTimes[$i]){
                    $nearestConflict = $i;
                    break;
                }
            }
        }

        if($nearestConflict){

            $nearestAvailableStartTime = false;
            $nearestAvailableEndTime = false;
            for ($i = 0; $i < $timesCount; $i++) { 
                if($i == 0){
                    if($startTimes[$i] - $conferenceStartTime > 299){
                        $nearestAvailableStartTime = $conferenceStartTime;
                        $nearestAvailableEndTime = $startTimes[$i];
                    }
                }else if($i + 1 == $timesCount){
                    if(strtotime('21:00') - $endTimes[$i] > 299){
                        if(!$nearestAvailableStartTime){
                            $nearestAvailableStartTime = $endTimes[$i];
                            $nearestAvailableEndTime = strtotime('21:00');
                        }else if(abs($endTimes[$i] - $startTime) < abs($nearestAvailableStartTime - $startTime)){
                            $nearestAvailableStartTime = $endTimes[$i];
                            $nearestAvailableEndTime = strtotime('21:00');
                        }
                    }
                    break;
                }
                //if there is a long pause between reports
                if($startTimes[$i+1] - $endTimes[$i] > 299){
                    if(!$nearestAvailableStartTime){
                        $nearestAvailableStartTime = $endTimes[$i];
                        $nearestAvailableEndTime = $startTimes[$i+1];
                    }else if(abs($endTimes[$i] - $startTime) < abs($nearestAvailableStartTime - $startTime)){
                        $nearestAvailableStartTime = $endTimes[$i];
                        $nearestAvailableEndTime = $startTimes[$i+1];
                    }
                }
                
            }

            if(!$nearestAvailableStartTime){
                $validator->errors()->add(
                    'start_time',
                    'Sorry, the conference is busy all the time.'
                );
            }else{
                $validator->errors()->add(
                    'start_time',
                    'Report time conflicts with other reports. Nearest interval: '.date('H:i',$nearestAvailableStartTime).'-'.date('H:i',$nearestAvailableEndTime).'.'
                );
            }
            

        }
        
    }

    /**
     * Get the cards available for the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function cards(Request $request)
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function filters(Request $request)
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function lenses(Request $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function actions(Request $request)
    {
        return [];
    }
}
