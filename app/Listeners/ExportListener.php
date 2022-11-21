<?php

namespace App\Listeners;

use App\Events\ExportEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ExportListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\ExportEvent  $event
     * @return void
     */
    public function handle(ExportEvent $event)
    {
        return $event->resultCSV;
    }
}
