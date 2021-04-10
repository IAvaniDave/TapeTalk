<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BlockedUser;
use App\Models\ChatGroup;
use App\Models\ChatMember;
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


    /**
     * This function is used for add group api
     */
    public function addGroup(Request $request){
        $responseData = array();
        $responseData['status'] = 0;
        $responseData['message'] = '';
        $responseData['data'] = (object) [];
        DB::beginTransaction();
        try{
            $validator = Validator::make($request->all(), [
                'is_existing' => 'required',
            ]);
            $currentUser = $request->get('user');
            if ($validator->fails()) {
                $responseData['message'] = $validator->errors()->first();
                DB::rollback();
                return $this->commonResponse($responseData, 200);
            } else {
                // member must be greater than 2
                if(isset($request->members)){
                    // check whether same members are not there in array
                    $isUnique = array_unique($request->members);
                    $flag = true;
                    foreach ($isUnique as $userData){
                        $userExists = User::where('id',$userData)->exists();
                        if(!$userExists){
                            $flag = false;
                        }
                    }
                    // all user details exists
                    if($flag){
                        // check if group is new
                        if($request->is_existing == 0){
                            if(!(count($isUnique) > 2)){
                                DB::rollback();
                                $responseData['message'] = "Members Count must be greater than 2";
                                $responseData['status'] = 400;
                                return $this->commonResponse($responseData, 200);
                            } else {
                            
                                $flagExists = false;
                                // check Group Exists with same name
                                $checkGroupExists = ChatGroup::where(['group_name' => $request->group_name,'deleted_at' => null,'created_by' => $currentUser->id])->first();
                                if(empty($checkGroupExists)){
                                    $flagExists = false;
                                } else {
                                    // check same mebers are there or different members
                                    $checkMembers = ChatMember::where('group_id',$checkGroupExists->id)->where('is_left',0)->where('deleted_at',null)->get();
                                    if(isset($checkMembers) && empty($checkMembers)){
                                        $flagExists = false;
                                    } else {
                                        $userArray = $checkMembers->pluck('user_id')->toArray();
                                        $checkSameMembers = array_diff($userArray,$isUnique);
                                        // dd($userArray);
                                        if(count($checkSameMembers) == 0){
                                            $flagExists = true;
                                        } else {
                                            $flagExists = false;
                                        }
                                    }
                                }
    
                                if($flagExists){
                                    DB::rollback();
                                    $responseData['message'] = "Group with same name and same members already Exists"; 
                                    $responseData['status'] = 400;
                                    return $this->commonResponse($responseData, 200);
                                }
    
    
                                $addGroup = new ChatGroup();
                                $addGroup->group_name = $request->group_name;
                                $addGroup->created_by = $currentUser->id;
                                $addGroup->is_single = 2;
                                $addGroup->save();
    
                                // need to add chat members in chat group
                                if(isset($addGroup)){
    
                                    $membersData = [];
    
                                    foreach($isUnique as $members){
                                        $membersData[] = ['user_id' => $members, 'group_id' => $addGroup->id, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s') ];
                                    }
                                    $addMembers = ChatMember::insert($membersData);
                                    DB::commit();
    
                                    $finalData = ChatGroup::where('id',$addGroup->id)->where('deleted_at',NULL)->with(['chatmembers' => function($query){
                                        $query->where('deleted_at',NULL)->select('id',"group_id","user_id")->with('user:id,username,email');
                                    }])->whereHas('chatmembers',function($query){
                                        $query->where('deleted_at',NULL);
                                    })->first();
                                    $responseData['message'] = "Group Added Successfully"; 
                                    $responseData['status'] = 200;
                                    $responseData['data'] = $finalData;
                                    return $this->commonResponse($responseData, 200);
                                }
                            }
                        // check if group is existing   
                        } else if($request->is_existing == 1){
                            // check whether he/she is admin or not
                            $isAdmin = ChatGroup::where(['created_by' => $currentUser->id,'id' => $request->group_id,'deleted_at' => null])->first();
                            if(isset($isAdmin) && !empty($isAdmin)){
                                // check if any member are already present or not
                                $onlyNewMembers = true;
                                foreach ($isUnique as $userData){
                                    $userExists = ChatMember::where('group_id',$request->group_id)->where('user_id',$userData)->where('deleted_at',null)->where('is_left',0)->exists();
                                    if($userExists){
                                        $onlyNewMembers = false;
                                        break;
                                    }
                                }
                                if(!$onlyNewMembers){
                                    DB::rollback();
                                    $responseData['message'] = "Members are already there in your group.Please add new members"; 
                                    $responseData['status'] = 400;
                                    return $this->commonResponse($responseData, 200);
                                } else {
                                    // new members
                                    $membersData = [];

                                    foreach($isUnique as $members){
                                        $membersData[] = ['user_id' => $members, 'group_id' => $request->group_id, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s') ];
                                    }
                                    $addMembers = ChatMember::insert($membersData);
                                    DB::commit();

                                    $finalData = ChatGroup::where('id',$request->group_id)->where('deleted_at',NULL)->with(['chatmembers' => function($query){
                                        $query->where('deleted_at',NULL)->select('id',"group_id","user_id")->with('user:id,username,email');
                                    }])->whereHas('chatmembers',function($query){
                                        $query->where('deleted_at',NULL);
                                    })->first();
                                    $responseData['message'] = "Members Added Successfully"; 
                                    $responseData['status'] = 200;
                                    $responseData['data'] = $finalData;
                                    return $this->commonResponse($responseData, 200);
                                }
                            } else {
                                DB::rollback();
                                $responseData['message'] = "You cannot add members ,You are not a admin"; 
                                $responseData['status'] = 400;
                                return $this->commonResponse($responseData, 200);
                            }

                        }
                    } else {
                        DB::rollback();
                        $responseData['message'] = "Members must exists in users list"; 
                        $responseData['status'] = 400;
                        return $this->commonResponse($responseData, 200);
                    }
                } else {
                    DB::rollback();
                    $responseData['message'] = "Members must be needed for group";
                    $responseData['status'] = 400;
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
}