<?php

namespace App\Listeners;

use App\Events\GreetSmsEvent;
use App\Jobs\SendGreetMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Jobs\SendGreetSms;

class GreetSmsEventListener
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
     * @param  GreetSmsEvent  $event
     * @return void
     */
    public function handle(GreetSmsEvent $event)
    {
//        dd($event->getData()['request_data']);
        dispatch(new SendGreetSms($event->getData()));
    }
}
