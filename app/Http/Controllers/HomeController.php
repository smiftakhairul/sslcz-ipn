<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
//        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('home');
    }



    public function test()
    {
        $response = Http::asForm()->post('https://sms.sslwireless.com/pushapi/server.php', [
            'user' => 'easymerchant',
            'pass' => '24A5U55d',
            'sid' => 'EASYMERCHANT',
            'sms' => [
                ['8801630132436', 'Hello Ikram, This is test message'],
                ['8801630132436', 'Hello User, This is test message'],
            ]
        ]);

//        all responses demo
        dd([
            'main' => $response,
            'json' => $response->json(),
            'status' => $response->status(),
            'ok' => $response->ok(),
            'successful' => $response->successful(),
            'server_error' => $response->serverError(),
            'client_error' => $response->clientError(),
            'headers' => $response->headers()
        ]);
    }
}
