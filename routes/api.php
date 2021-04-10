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
Route::post('/register', 'Api\UserController@register');
Route::post('/login', 'Api\UserController@login');
Route::post('/socialMediaLogin', 'Api\UserController@socialMediaLogin');
Route::group(['middleware' => ['api-access']], function () {
    Route::post('/block-user','Api\GeneralController@blockUser');
    Route::post('/users-list','Api\GeneralController@usersList');
    Route::post('/add-group','Api\GeneralController@addGroup');
    Route::post('/send-message','Api\ChatController@sendMessge');
});
Route::get('/test' , function (){
    return "API is working";
});
