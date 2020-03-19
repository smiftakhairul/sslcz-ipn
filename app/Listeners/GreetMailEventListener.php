<?php

namespace App\Listeners;

use App\Events\GreetMailEvent;
use App\Jobs\SendGreetMail;
use http\Env\Response;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class GreetMailEventListener
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
     * @param  GreetMailEvent  $event
     * @return void
     */
    public function handle(GreetMailEvent $event)
    {
//        var_dump($event->getData()); exit();
        dispatch(new SendGreetMail($event->getData()));
    }
}
