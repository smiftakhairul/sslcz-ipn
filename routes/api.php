<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

//Route::middleware('api')->post('/email/send', 'API\EmailController@send')->name('api.email.send');

Route::prefix('v1')->group(function () {
    Route::post('/email/send', 'API\v1\EmailController@send')->name('api.v1.email.send');
    Route::post('/sms/send', 'API\v1\SmsController@send')->name('api.v1.email.send');

    /*fcm token and api send*/
    Route::post('/single/notify', 'API\v1\NotificationController@notify');
});
