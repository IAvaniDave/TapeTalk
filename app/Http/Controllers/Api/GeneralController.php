<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BlockedUser;
use App\User;
use DB;
use Validator;

class GeneralController extends Controller
{
    /**
     * This function is used to block the user in chat
    * */ 
    public function blockUser(Request $request){
        $responseData = array();
        $responseData['status'] = 0;
        $responseData['message'] = '';
        $responseData['data'] = (object) [];
        DB::beginTransaction();
        try{
            $validator = Validator::make($request->all(), [
                'user_id' => 'required',
                'is_block' => 'required'
            ]);
            $currentUser = $request->get('user');
            if ($validator->fails()) {
                $responseData['message'] = $validator->errors()->first();
                DB::rollback();
                return $this->commonResponse($responseData, 200);
            } else {
                // check user exists
                $userExists = User::where('id',$request->user_id)->first();
                if(!isset($userExists) && empty($userExists)){
                    $responseData['message'] = "User is not exists";
                    DB::rollback();
                    return $this->commonResponse($responseData, 200);
                } else {
                    // check user to be blocked is not current user
                    if($request->user_id == $currentUser->id){
                        $responseData['message'] = "You are a loser.You cannot block yourself";
                        DB::rollback();
                        return $this->commonResponse($responseData, 200);
                    } 
                    // already blocked user
                    if($request->is_block == 1){
                        $alreadyBlocked = BlockedUser::where(['user_id' => $request->user_id,'created_by' => $currentUser->id])->first();
                        if(isset($alreadyBlocked) && !empty($alreadyBlocked)){
                            $responseData['message'] = "This user is already blocked by you";
                            DB::rollback();
                            return $this->commonResponse($responseData, 200);
                        } else {
                            // first time block
                            $blockedData = new BlockedUser();
                            $blockedData->user_id = $request->user_id;
                            $blockedData->created_by = $currentUser->id;
                            $blockedData->save();
                            DB::commit();
                            $responseData['message'] = "This user is blocked.";
                            $responseData['status'] = 200;
                            return $this->commonResponse($responseData, 200);
                        }
                    } else if($request->is_block == 2){
                        // unblock functionaltiy is_block - 2
                        $deleteBlockedUser = BlockedUser::where(['user_id' => $request->user_id,'created_by' => $currentUser->id])->first();
                        if(isset($deleteBlockedUser) && !empty($deleteBlockedUser)){
                            $deleteBlockedUser->delete();
                            DB::commit();
                            $responseData['message'] = "This user is unblocked.";
                            $responseData['status'] = 200;
                            return $this->commonResponse($responseData, 200);
                        } else {
                            $responseData['message'] = "This user was not blocked by you";
                            DB::rollback();
                            return $this->commonResponse($responseData, 200);
                        }
                    }
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
     * This function is used for user listing 
     * with
     * search
     * pagination
     */
    public function usersList(Request $request){
        $responseData = array();
        $responseData['status'] = 0;
        $responseData['message'] = '';
        $responseData['data'] = (object) [];
        try {
            $currentUser = $request->get('user');
            $limit = isset($request->limit) ? $request->limit : 10;

            $users = User::select('id','username','logo','email','status')->where('status',1)->where('id','!=',$currentUser->id);
            
            if(isset($request->keyword)){
                $users->where('username','like','%'.$request->keyword.'%');
            }            

            $users = $users->paginate($limit);
            $results = $users->toJson();
            
            // dd($users);

            $results = json_decode($results);
            unset($results->last_page_url);
            unset($results->first_page_url);
            unset($results->next_page_url);
            unset($results->prev_page_url);
            unset($results->path);
            $responseData['message'] = "Users retrieved succesfully";
            $responseData['data'] = $results;
            $responseData['status'] = 1;

            return $this->commonResponse($responseData, 200);

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
}
