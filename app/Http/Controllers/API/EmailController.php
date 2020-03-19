<?php

namespace App\Http\Controllers\API;

use App\Email;
use App\Events\GreetMailEvent;
use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EmailController extends Controller
{
    public function send(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sender' => 'required|max:255',
            'recipient' => 'required|max:255',
            'subject' => 'required|min:10|max:255',
            'content' => 'required|min:200',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'validation' => $validator->errors()]);
        }

//        Patch all data to DB
        Email::create($request->all());

//        Recipient selection
        if ($request->recipient == 'all') {
            $recipient = User::where('email', '!=', $request->sender)->get();
        } else {
            $recipient = $request->recipient;
        }

        event(new GreetMailEvent(['recipient' => $recipient, 'request_data' => $request->all()]));

        return response()->json(['status' => 'success', 'message' => 'Mail sent successfully!']);
    }
}
