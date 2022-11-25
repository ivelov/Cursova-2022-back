<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\Country;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Password;
use Laravel\Nova\Fields\Text;
use Bissolli\NovaPhoneField\PhoneNumber;
use Laravel\Nova\Fields\Date;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Hidden;

class ListenerUser extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\User::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'lastname';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id', 'firstname', 'lastname', 'email',
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
            ID::make()->sortable(),

            Text::make('Firstname')
                ->sortable()
                ->rules('required', 'max:255'),
            
            Text::make('Lastname')
                ->sortable()
                ->rules('required', 'max:255'),

            Text::make('Email')
                ->rules('required', 'email', 'max:254')
                ->creationRules('unique:users,email')
                ->updateRules('unique:users,email,{{resourceId}}'),

            Password::make('Password')
                ->onlyOnForms()
                ->creationRules('required', 'string', 'min:6')
                ->updateRules('nullable', 'string', 'min:6'),
                
            Country::make('Country')
                ->sortable()
                ->rules('required'),
            
            PhoneNumber::make('Phone'),

            Date::make('Birthdate')
                ->rules('required', 'date', 'before:now')
                ->resolveUsing(function () {
                    return $this->birthdate;
                }),


            Hidden::make('Role')->default('listener'),

            DateTime::make('Created At')
                ->hideWhenCreating()
                ->hideWhenUpdating(),
                
            DateTime::make('Updated At')
                ->hideWhenCreating()
                ->hideWhenUpdating(),
        ];
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
        return [
            new Filters\ListenerFilter
        ];
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
