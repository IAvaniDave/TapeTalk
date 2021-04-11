<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BlockedUser;
use App\Models\ChatGroup;
use App\Models\ChatMember;
use App\Models\ChatMessage;
use App\User;
use DB;
use Validator;
use File;

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
                $userExists = User::where('id',$request->user_id)->where('status',1)->first();
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
                $request->members = explode(",",$request->members);
                // member must be greater than 2
                if(isset($request->members)){
                    // check whether same members are not there in array
                    $isUnique = array_unique($request->members);
                    $flag = true;
                    foreach ($isUnique as $userData){
                        $userExists = User::where('id',$userData)->where('status',1)->exists();
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
                                $checkGroupExists = ChatGroup::where(['group_name' => $request->group_name,'deleted_at' => null,'created_by' => $currentUser->id,'is_single' => 2])->get();
                                if(empty($checkGroupExists) && count($checkGroupExists) == 0){
                                    $flagExists = false;
                                } else {
                                    $checkMembers = [];
                                    foreach($checkGroupExists as $checkGroup){
                                        // check same mebers are there or different members
                                        $checkMembers = ChatMember::where('group_id',$checkGroup->id)->where('is_left',0)->where('deleted_at',null)->get();
                                        if(isset($checkMembers) && empty($checkMembers)){
                                            $flagExists = false;
                                        } else {
                                            $userArray = $checkMembers->pluck('user_id')->toArray();
                                            $checkSameMembers = array_diff($userArray,$isUnique);
                                            if(count($checkSameMembers) == 0){
                                                $flagExists = true;
                                                break;
                                            } else {
                                                $flagExists = false;
                                            }
                                        }
                                    }
                                }
    
                                if($flagExists){
                                    DB::rollback();
                                    $responseData['message'] = "Group with same name and same members already Exists"; 
                                    $responseData['status'] = 400;
                                    return $this->commonResponse($responseData, 200);
                                }
                                if(isset($request->group_image) && $request->file('group_image') != ""){
                                    $file =  $request->file('group_image');
                                    $groups_image = uniqid('groups_', true).time().'.'.$file->getClientOriginalExtension();
                                    $upload = public_path().'/images/groups/';
                                    $file->move($upload,$groups_image);
                                }
                                $addGroup = new ChatGroup();
                                $addGroup->group_name = $request->group_name;
                                $addGroup->created_by = $currentUser->id;
                                $addGroup->is_single = 2;
                                $addGroup->group_image = (isset($request->group_image)) ? $groups_image : null;
                                $addGroup->save();
    
                                // need to add chat members in chat group
                                if(isset($addGroup)){
    
                                    $membersData = [];
    
                                    foreach($isUnique as $members){
                                        $membersData[] = ['user_id' => $members, 'group_id' => $addGroup->id, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s') ];
                                    }
                                    $addMembers = ChatMember::insert($membersData);
                                    DB::commit();
    
                                    $finalData = ChatGroup::where('id',$addGroup->id)->where('deleted_at',NULL)->where('is_single',2)->with(['chatmembers' => function($query){
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
                            $isAdmin = ChatGroup::where(['created_by' => $currentUser->id,'is_single' => 2,'id' => $request->group_id,'deleted_at' => null])->first();
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

                                    $finalData = ChatGroup::where('id',$request->group_id)->where('is_single',2)->where('deleted_at',NULL)->with(['chatmembers' => function($query){
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


    /**
     * this function is for edit group details
     */
    public function editGroupDetails(Request $request){
        $responseData = array();
        $responseData['status'] = 0;
        $responseData['message'] = '';
        $responseData['data'] = (object) [];
        DB::beginTransaction();
        try{
            $validator = Validator::make($request->all(), [
                'group_id' => 'required',
            ]);
            $currentUser = $request->get('user');
            if ($validator->fails()) {
                $responseData['message'] = $validator->errors()->first();
                DB::rollback();
                return $this->commonResponse($responseData, 200);
            } else {
                // check if admin is not
                $isAdmin = ChatGroup::where('deleted_at',null)->where('created_by',$currentUser->id)->where('id',$request->group_id)->where('is_single',2)->first();
                if(isset($isAdmin) && !empty($isAdmin)){
                    $updatedData = [];
                    if(isset($request->group_name)){
                        $updatedData['group_name'] = $request->group_name;
                    }
                    if(isset($request->group_image)){
                        if($isAdmin->group_image != null){
                            if (File::exists(public_path('images/groups/'.$isAdmin->group_image))) {
                                File::delete(public_path('images/groups/'.$isAdmin->group_image));
                            }
                        }
                        // add new image 
                        $file =  $request->file('group_image');
                        $groups_image = uniqid('groups_', true).time().'.'.$file->getClientOriginalExtension();
                        $upload = public_path().'/images/groups/';
                        $file->move($upload,$groups_image);
                        $updatedData['group_image'] = $groups_image;
                    }

                    $update = ChatGroup::where('id',$isAdmin->id)->update($updatedData);
                    DB::commit();
                    $responseData['message'] = "Group Details Edited Successfully";
                    $responseData['status'] = 200;
                    return $this->commonResponse($responseData, 200);
                } else {
                    DB::rollback();
                    $responseData['message'] = "You cannot edit the group details,You are not a admin";
                    $responseData['status'] = 400;
                    return $this->commonResponse($responseData, 200);
                }

            }
        } catch(Exception $e){
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
     * Remove member from group
    */
    public function removeMembersFromGroup(Request $request){
        $responseData = array();
        $responseData['status'] = 0;
        $responseData['message'] = '';
        $responseData['data'] = (object) [];
        DB::beginTransaction();
        try{
            $validator = Validator::make($request->all(), [
                'group_id' => 'required',
                'user_id' => 'required',
            ]);
            $currentUser = $request->get('user');
            if ($validator->fails()) {
                $responseData['message'] = $validator->errors()->first();
                DB::rollback();
                return $this->commonResponse($responseData, 200);
            } else {
                if($currentUser->id == $request->user_id){
                    DB::rollback();
                    $responseData['message'] = "You cannot remove yourself";
                    $responseData['code'] = 400;
                    return $this->commonResponse($responseData, 200);
                }
                // group exist with current user and also he/she is admin in that group.
                $groupExists = ChatGroup::where(['id' => $request->group_id,'is_single' => 2,'deleted_at' => null,'created_by' => $currentUser->id])->first();
                if(isset($groupExists) && !empty($groupExists)){
                    $chatMembers = ChatMember::where(['user_id' => $request->user_id,'is_left' => 0,'deleted_at' => null,'group_id' => $request->group_id])->first();
                    if(isset($chatMembers) && !empty($chatMembers)){
                        $updateMembers = ChatMember::where('id', $chatMembers->id)->update(['deleted_at' => date('Y-m-d H:i:s')]);
                        DB::commit();
                        $responseData['message'] = "User Removed Successfully";
                        $responseData['status'] = 200;
                        return $this->commonResponse($responseData, 200);
                    } else {
                        DB::rollback();
                        $responseData['message'] = "Member Doesnt Exists in group";
                        $responseData['status'] = 400;
                        return $this->commonResponse($responseData, 200);
                    }
                } else {
                    DB::rollback();
                    $responseData['message'] = "Group Doesn't Exists";
                    $responseData['status'] = 400;
                    return $this->commonResponse($responseData, 200);
                }
            }
        } catch(Exception $e){
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
     * Message List Api
     */
    public function messageList(Request $request){
        $responseData = array();
        $responseData['status'] = 0;
        $responseData['message'] = '';
        $responseData['data'] = (object) [];
        try{    
            $validator = Validator::make($request->all(), [
                'group_id' => 'required',
            ]);
            $currentUser = $request->get('user');
            if ($validator->fails()) {
                $responseData['message'] = $validator->errors()->first();
                return $this->commonResponse($responseData, 200);
            } else {
                // check GroupExists
                $groupExists = ChatGroup::where(['deleted_at' => null,'id' => $request->group_id])->first();
                if(isset($groupExists) && !empty($groupExists)){
                    $membersExists = ChatMember::where(['deleted_at' => null,'group_id' => $request->group_id,'user_id' => $currentUser->id,'is_left' => 0])->first();
                    if(isset($membersExists) && !empty($membersExists)){
                        $messageList = ChatMessage::select('id','group_id','sender_id','text')->where(['group_id' => $request->group_id])->with('user:id,username,email,logo,status,deleted_at')->with(['group' => function($query){
                            $query->where('deleted_at',null)->select('id','group_name','group_image');
                        }])->whereHas('group',function($query){
                            $query->where('deleted_at',null);
                        })->get();
                        $responseData['status'] = 200;
                        $responseData['message'] = "Success";
                        $responseData['data'] = $messageList;
                        return $this->commonResponse($responseData, 200);
                    } else {
                        if($groupExists->is_single == 1){
                            $responseData['message'] = "You have not chatted with this user";
                        } else {
                            $responseData['message'] = "You are not a member of this group";
                        }
                        $responseData['status'] = 400;
                        return $this->commonResponse($responseData, 200);
                    }
                } else {
                    $responseData['message'] = "Group Doesnt Exists";
                    $responseData['status'] = 400;
                    return $this->commonResponse($responseData, 200);
                }

            }
        } catch(Exception $e){
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
     * Get User Details
     */
    public function getUserDetails(Request $request){
        $responseData = array();
        $responseData['status'] = 0;
        $responseData['message'] = '';
        $responseData['data'] = (object) [];
        try{
            $currentUser = $request->get('user');
            $responseData['data'] = $currentUser;
            $responseData['status'] = 200;
            $responseData['message'] = "Profile Retrived Successfully";
            return $this->commonResponse($responseData, 200);
        } catch(Exception $e){
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
     * Edit User Profile
     */
    public function editUserProfile(Request $request){
        $responseData = array();
        $responseData['status'] = 0;
        $responseData['message'] = '';
        $responseData['data'] = (object) [];
        DB::beginTransaction();
        try{
            $currentUser = $request->get('user');
            $updatedData = [];
            if(isset($request->logo) && $request->file('logo') != ""){
                if(($currentUser->gender == 1 && $currentUser->logo != "male.png") || ($currentUser->gender == 2 && $currentUser->logo != "female.png")){
                    // unlink the logo
                    if (File::exists(public_path('images/users/'.$currentUser->logo))) {
                        File::delete(public_path('images/users/'.$currentUser->logo));
                    }
                }
                // set new logo
                $file =  $request->file('logo');
                $updatedLogo = uniqid('logo_', true).time().'.'.$file->getClientOriginalExtension();
                $upload = public_path().'/images/users/';
                $file->move($upload,$updatedLogo);
                $updatedData['logo'] = $updatedLogo;
            }
            if(isset($request->birth_date)){
                $updatedData['birth_date'] = $request->birth_date;
            }
            if(isset($request->username)){
                $updatedData['username'] = $request->username;

            }
            $update = User::where('id',$currentUser->id)->update($updatedData);
            DB::commit();
            $responseData['status'] = 200;
            $responseData['message'] = 'User Details Edited Successfully';
            return $this->commonResponse($responseData, 200);
        } catch(Exception $e){
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