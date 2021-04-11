<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
// use App\Models\Crash;
use App\Models\UserProfile;;
use App\Models\DeviceToken;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Auth;
use Illuminate\Support\Str;
// use App\Http\Controllers\SendMailController;
// use Illuminate\Auth\Events\Verified;
use Helper;
use Newsletter;
use DB;
// use Illuminate\Foundation\Auth\SendsPasswordResetEmails;

class UserController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */    /*
     * /api/register
     */
    protected function register(Request $request){
        $responseData = array();
        $responseData['status'] = 0;
        $responseData['message'] = '';
        $responseData['data'] = (object) [];
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'username' => 'required',
                'gender' => 'required', // 1 = male , 2 = female
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,NULL,id,deleted_at,NULL'],
                'password' => ['required', 'string', 'min:8'],
                'confirm_password' => ['required', 'string', 'min:8'],
                "birth_date" => "required",
            ]);
            if ($validator->fails()) {
                $responseData['message'] = $validator->errors()->first();
                DB::rollback();
                return $this->commonResponse($responseData, 200);
            } else {
                $user_data = [
                    'email' => $request->input('email'),
                    'username' => $request->input('username'),
                    'password' => Hash::make($request->input('password')),
                    'status' => 1,
                    'gender' => $request->input('gender'),
                    'ip_address' => \Request::ip(),
                    'logo' => null,
                    "birth_date" => $request->input('birth_date'),
                ];
                $user = User::create($user_data);
                if ($user) {

                    DB::commit();
                    /*
                     * Successfully register
                     */
                    $result['id'] = $user->id;
                    $result['username'] = $user->username;
                    $result['email'] = $user->email;
                    $result['gender'] = $user->gender;
                    $result['birth_date'] = $user->birth_date;
                    
                    // Log::info('Register successfully send sendEmailVerificationNotification');
                    // $user->sendEmailVerificationNotification();

                    $responseData['status'] = 1;
                    $responseData['message'] = "Registration successfully";
                    $responseData['data'] = $result;
                    return $this->commonResponse($responseData, 201);
                } else {
                    DB::rollback();
                    Log::error('Error occur during registration, please try again.');
                    /*
                     * error in register
                     */
                    $responseData['status'] = 0;
                    $responseData['message'] = trans('auth.error_in_register');
                    return $this->commonResponse($responseData, 500);
                }
            }
        } catch (Exception $e){
            DB::rollback();
            $catchError = 'Error code: '.$e->getCode().PHP_EOL;
            $catchError .= 'Error file: '.$e->getFile().PHP_EOL;
            $catchError .= 'Error line: '.$e->getLine().PHP_EOL;
            $catchError .= 'Error message: '.$e->getMessage().PHP_EOL;
            Log::emergency($catchError);

            $code = ($e->getCode() != '')?$e->getCode():500;
            $responseData['message'] = trans('common.something_went_wrong');
            return $this->commonResponse($responseData, $code);
        }
    }

    /*
     * User login api
     * /api/register
     *
     * @param Request $request
     *
     * @return mix json
     */

    protected function login(Request $request){
        $responseData = array();
        $responseData['status'] = 0;
        $responseData['message'] = '';
        $responseData['data'] = (object) [];
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'email' => ['required', 'string', 'email', 'max:255'],
                'password' => ['required', 'string'],
                'device_type' => ['required', 'string'],
                'device_id' => ['required', 'string'],
                'device_name' => ['required', 'string'],
            ]);
            if ($validator->fails()) {
                $responseData['message'] = $validator->errors()->first();
                DB::rollback();
                return $this->commonResponse($responseData, 200);
            } else {

                $user = User::select('id','email','password','gender','status','logo','deleted_at')->where([
                    'email'=> $request->email,
                    'deleted_at' => null,
                ])->first();


                if($user){
                    $auth = Hash::check($request->password, $user->password);
                    if ($user && $auth) {
                        DB::commit();
                        return $this->userLoginSuccessResponse($user, $request);
                    }else{
                        \Log::info('5');
                        $responseData['message'] = "Auhentication failed";
                        return $this->commonResponse($responseData, 200);
                    }
                } else{
                    $responseData['message'] = "Auhentication failed";
                    return $this->commonResponse($responseData, 200);
                }
            }
        } catch (Exception $e){
            DB::rollback();
            $catchError = 'Error code: '.$e->getCode().PHP_EOL;
            $catchError .= 'Error file: '.$e->getFile().PHP_EOL;
            $catchError .= 'Error line: '.$e->getLine().PHP_EOL;
            $catchError .= 'Error message: '.$e->getMessage().PHP_EOL;
            Log::emergency($catchError);

            $code = ($e->getCode() != '')?$e->getCode():500;
            $responseData['message'] = "Something went wrong";
            return $this->commonResponse($responseData, $code);
        }
    }


     /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    protected function socialMediaLogin(Request $request){
        $responseData = array();
        $responseData['status'] = 0;
        $responseData['message'] = '';
        $responseData['data'] = (object) [];

        try {
            $validator = Validator::make($request->all(), [
                'social' => 'required',
                'provider_id' => 'required',
                'device_type' => ['required', 'string'],
                'device_id' => ['required', 'string'],
                'device_name' => ['required', 'string'],
            ]);
            if ($validator->fails()) {
                $responseData['message'] = $validator->errors()->first();
                return $this->commonResponse($responseData, 200);
            } else{
                $provider_id = $request->input('provider_id');

                $social = $request->input('social');

                $user = User::where(['email' => $request->input('email'),'deleted_at' => null])->orWhere(['provider_id' => $provider_id])->first();

                if(is_null($user)){
                    $email = $request->input('email');
                    $username = $request->input('username');

                    DB::beginTransaction();
                    $user = new User();
                    $user->username = $username;
                    $user->email       = $email;
                    $user->password    = Hash::make(Str::random(8));
                    $user->logo    = null;
                    $user->provider_name = $social;
                    $user->provider_id = $provider_id;
                    $user->ip_address = \Request::ip();
                    $user->save();
                    
                    DB::commit();

                    return $this->userLoginSuccessResponse($user, $request);
                } else {
                    DB::beginTransaction();
                    $user->forceFill([
                        'updated_at' => now(),
                    ])->save();
                    DB::commit();
                    return $this->userLoginSuccessResponse($user, $request);
                }
            }
        } catch (Exception $e){
            DB::rollback();
            Log::emergency('Login with social site catch exception:: Message:: '.$e->getMessage().' line:: '.$e->getLine().' Code:: '.$e->getCode().' file:: '.$e->getFile());
            $code = ($e->getCode() != '')?$e->getCode():500;
            $responseData['message'] = trans('common.something_went_wrong');
            return $this->commonResponse($responseData, $code);
        }
    }

     /*
     * After login success response to user
     *
     * @param User $user
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    protected function userLoginSuccessResponse(User $user, Request $request) {

        LOG::info('userLoginSuccessResponse ::'. print_r(['fcm_token' => $request->fcm_token,'device_type' => $request->device_type], true));
        try {
            $api_token = Str::random(60);
            DB::beginTransaction();
            DeviceToken::where(['fcm_token' => $request->fcm_token,'device_type' => $request->device_type])
                ->orWhere(['device_id' => $request->device_id])->delete();

            $user_device_token = DeviceToken::create(
                [
                    'user_id' => $user->id,
                    'device_type' => $request->device_type,
                    'device_id' => $request->device_id,
                    'device_name' => $request->device_name,
                    'fcm_token' => $request->fcm_token,
                    'current_version' => $request->current_version,
                    'last_login_at' => $request->last_login_at,
                    'api_token' => $api_token,
                ]
            );

            if ($user_device_token) {
                DB::commit();
                $user->api_token = $user_device_token->api_token;
                /*
                 * Send user's field
                 */
                $responseData['status'] = 1;
                $responseData['message'] = "LoggedIn successfully";
                $responseData['data'] = collect($user)->only($user->apiOnlyField)->toArray();
                return $this->commonResponse($responseData, 200);
            }else{
                DB::rollback();
                Log::emergency('Something went wrong..! Please try again... user token device entry not done');
                $responseData['status'] = 0;
                $responseData['message'] = "Something went wrong";
                $responseData['data'] = array();
                return $this->commonResponse($responseData, 500);
            }

        }catch (Exception $e){
            DB::rollback();
            $catchError = 'Error code: '.$e->getCode().PHP_EOL;
            $catchError .= 'Error file: '.$e->getFile().PHP_EOL;
            $catchError .= 'Error line: '.$e->getLine().PHP_EOL;
            $catchError .= 'Error message: '.$e->getMessage().PHP_EOL;
            Log::emergency($catchError);

            $code = ($e->getCode() != '')?$e->getCode():500;
            $responseData['message'] = "Something went wrong";
            return $this->commonResponse($responseData, $code);
        }
    }

}