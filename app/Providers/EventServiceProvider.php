<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Report;
use App\Observers\CategoryObserver;
use App\Observers\ReportObserver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        'App\Events\ExportEvent' => [
            'App\Listeners\ExportListener',
        ]
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        Category::observe(CategoryObserver::class);
        Report::observe(ReportObserver::class);
    }
}
