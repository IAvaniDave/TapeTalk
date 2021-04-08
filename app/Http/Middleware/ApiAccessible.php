<?php

namespace App\Http\Middleware;

use Closure;
use App\User;
use DB;
use App\Models\DeviceToken;
use Illuminate\Support\Facades\Validator;
use Log;
use Carbon\Carbon;

class ApiAccessible
{

    // public $attributes;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try{
            $token = $request->bearerToken();
            \Log::info('ApiAccessible access middlewhere token:: '. $token);
            \Log::info('API Request: ' . print_r(json_encode($request->all()), true));
            if($token && strlen($token) > 0){
                $validator = Validator::make($request->all(), [
                    'device_id' => 'required',
                    'device_type' => 'required',
                ]);
                if ($validator->fails()) {
                    $message = $validator->errors()->first();
                    return response()->json(['status' => 403, 'message' => $message, 'result' => array()],202);
                }
                $currentToken = DeviceToken::where(['api_token' => $token,'device_id' => $request->device_id,'device_type' => $request->device_type])->with('deviceToken:id,username,email,password,ip_address,status,logo')->first();
                if($currentToken){
                    $currentUser = $currentToken->deviceToken;
                    if($currentUser){
                        if($currentUser->status == 0){
                            DeviceToken::where(['fcm_token' => $currentToken->fcm_token,'device_type' => $currentToken->device_type])
                            ->orWhere(['device_id' => $currentToken->device_id])->delete();
    
                            $message = 'Invalid Token';
                            return response()->json(['status' => 202,'message' => $message,'result' => null],202);
                        }
                        if($currentUser->status == 1){
                            $request->attributes->add(['user' => $currentUser]);
                            return $next($request);
                        } else {
                            $message = 'Invalid Token';
                            return response()->json(['status' => 202,'message' => $message,'result' => null],202);
                        }
                        
                    } else {
                        return response()->json(['status' => 202,'message' => 'User Not Found','result' => null],202);
                    }
                } else {
                    return response()->json(['status' => 403,'message' => 'Invalid Token','result' => null],202);
                }
            } else {
                return response()->json(['status' => 403, 'message' => 'Token is Required', 'result' => null],202);
            }
        } catch(Exception $e){
            Log::emergency('MiddleWare Exception:: Message:: '.$e->getMessage().' line:: '.$e->getLine().' Code:: '.$e->getCode().' file:: '.$e->getFile());
            $code = ($e->getCode() != '')?$e->getCode():500;
            $message = "Something Went Wrong";
            return response()->json(['status' => 0, 'message' => $message, 'result' => null],202);
        }
    }
}
