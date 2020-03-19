<?php

namespace App\Http\Controllers;

use App\Events\GreetMailEvent;
use App\Mail\Greet;
use Illuminate\Http\Request;
use App\User;
use Illuminate\Support\Facades\Mail;
use App\Email;

class EmailController extends Controller
{
    public function show()
    {
        $sender = User::firstWhere('email', 's.m.iftakhairul@gmail.com');
        $users = User::where('email', '!=', $sender->email)->get();

        return view('email.show')->with([
            'sender' => $sender,
            'users' => $users
        ]);
    }

    public function send(Request $request)
    {
        $request->validate([
            'sender' => 'required|max:255',
            'recipient' => 'required|max:255',
            'subject' => 'required|min:10|max:255',
            'content' => 'required|min:200',
        ]);

//        Patch all data to DB
        Email::create($request->all());

//        Recipient selection
        if ($request->recipient == 'all') {
            $recipient = User::where('email', '!=', $request->sender)->get();
        } else {
            $recipient = $request->recipient;
        }

        event(new GreetMailEvent(['recipient' => $recipient, 'request_data' => $request->all()]));

        return redirect()->route('email.show')->with('success', 'Success! Mail has been sent.');
    }
}
