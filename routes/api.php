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
Route::post('/forgot-password', 'Api\GeneralController@forgotPassword');
Route::group(['middleware' => ['api-access']], function () {
    Route::post('/details','Api\GeneralController@getUserDetails');
    Route::post('logout', 'Api\UserController@logout');
    Route::post('/edit-profile','Api\GeneralController@editUserProfile');
    Route::post('/block-user','Api\GeneralController@blockUser');
    Route::post('/users-list','Api\GeneralController@usersList');
    Route::post('/add-group','Api\GeneralController@addGroup');
    Route::post('/send-message','Api\ChatController@sendMessge');
    Route::post('/remove-members','Api\GeneralController@removeMembersFromGroup');
    Route::post('/edit-group-details','Api\GeneralController@editGroupDetails');
    Route::post('/message-list','Api\GeneralController@messageList');
    Route::post('/my-chats','Api\ChatController@myChats');
    Route::post('/change-password','Api\GeneralController@changePassword');
});
Route::get('/test' , function (){
    return "API is working";
});
