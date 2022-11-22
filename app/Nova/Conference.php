<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Country;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

class Conference extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\Conferences::class;

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

            Country::make('Country')
                ->sortable()
                ->rules('required'),

            Number::make('Latitude')
                ->rules('max:90', 'min:-90')
                ->nullable()
                ->hideFromIndex()
                ->fillUsing(function ($request, $model, $attribute, $requestAttribute) {
                    if(is_nan(floatval($model->{$attribute}))){
                        $model->{$attribute} = 0;
                    }
                }),
                
            Number::make('Longitude')
                ->rules('max:180', 'min:-180')
                ->nullable()
                ->hideFromIndex()
                ->fillUsing(function ($request, $model, $attribute, $requestAttribute) {
                    if(is_nan(floatval($model->{$attribute}))){
                        $model->{$attribute} = 0;
                    }
                }),
            
            BelongsTo::make('User')
                ->rules('required'),
    
            BelongsTo::make('Category')
                ->nullable(),
            
            DateTime::make('Date Time')
                ->fillUsing(function ($request, $model, $attribute, $requestAttribute) {
                    $date = date_create($request->input($attribute));
                    $model->date = date_format($date, 'Y-m-d');
                    $model->time = date_format($date, 'H:i:s');
                    Log::info($model);
                })->default(function () {
                    return time()+600;
                })->rules('required', 'date', 'after:now')
                ->resolveUsing(function () {
                    return $this->date . ' ' . $this->time;
                }),

            DateTime::make('Created At')
                ->hideWhenCreating()
                ->hideWhenUpdating(),
                
            DateTime::make('Updated At')
                ->hideWhenCreating()
                ->hideWhenUpdating(),
        ];
    }

    protected static function afterValidation(NovaRequest $request, $validator)
    {

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