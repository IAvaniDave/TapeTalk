<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\User;
use DB;
use Validator;

class ChatController extends Controller
{
    /**
     * This function is used for send message functionality
     * params required: token ==> for authentication
     * params optional : group_id / new_user_id ==> group id if aready group is there and new_user_id if group is    not there but need to create group
    */

    public function sendMessge(Request $request){
        $responseData = array();
        $responseData['status'] = 0;
        $responseData['message'] = '';
        $responseData['data'] = (object) [];
        DB::beginTransaction();
        try {
            $currentUser = $request->get('user');

            // to check user is logged in or not
            if(!isset($currentUser) && empty($currentUser)){ 
                $responseData['status'] = 401;
                $responseData['message'] = 'Authentication required';
                DB::rollback();
                return $this->commonResponse($responseData, 401);
            }

            $groupId = $request->group_id;

            //to check new user is exist in DB or not 
            if(isset($request->new_user_id)){
                $where = [
                    'id' => $request->new_user_id,
                    'status' => 1
                ];

                $newUserExist = User::select('id','status','email')->where($where)->first();
                if(!isset($newUserExist) && empty($newUserExist)){
                    $responseData['status'] = 200;
                    $responseData['message'] = 'This new user is not exist';
                    DB::rollback();
                    return $this->commonResponse($responseData, 401);
                }
            }

            // to check sender is sending message to himself
            if($groupId == null && $request->new_user_id == $currentUser->id){ 
                $responseData['status'] = 200;
                $responseData['message'] = 'You can\'t send a message to yourself.';
                DB::rollback();
                return $this->commonResponse($responseData, 200);
            }

            if(empty($groupId)){

            } else {
                // $chatGroup = 
            }

        } catch (\Exception $e) {
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