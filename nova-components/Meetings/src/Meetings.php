<?php

namespace Ivelovvm\Meetings;

use Laravel\Nova\Nova;
use Laravel\Nova\Tool;

class Meetings extends Tool
{
    /**
     * Perform any tasks that need to happen when the tool is booted.
     *
     * @return void
     */
    public function boot()
    {
        Nova::script('meetings', __DIR__.'/../dist/js/tool.js');
        Nova::style('meetings', __DIR__.'/../dist/css/tool.css');
    }

    /**
     * Build the view that renders the navigation links for the tool.
     *
     * @return \Illuminate\View\View
     */
    public function renderNavigation()
    {
        return view('meetings::navigation');
    }
}
