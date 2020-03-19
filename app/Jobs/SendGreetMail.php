<?php

namespace App\Jobs;

use App\Mail\Greet;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Util\Exception;

class SendGreetMail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            if ($this->getData()['request_data']['recipient'] == 'all') {
                foreach ($this->getData()['recipient'] as $recipient) {
                    Mail::to($recipient['email'])
                        ->send(new Greet($this->getData()['request_data']));
                }
            } else {
                Mail::to($this->getData()['recipient'])
                    ->send(new Greet($this->getData()['request_data']));
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
}
