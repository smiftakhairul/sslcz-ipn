<?php

namespace App\Providers;

use App\Events\GreetMailEvent;
use App\Events\GreetSmsEvent;
use App\Listeners\GreetMailEventListener;
use App\Listeners\GreetSmsEventListener;
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
        GreetMailEvent::class => [
            GreetMailEventListener::class,
        ],
        GreetSmsEvent::class => [
            GreetSmsEventListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
