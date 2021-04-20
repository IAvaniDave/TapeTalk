<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\User;
use App\Models\ChatGroup;
use App\Models\ChatMember;
use App\Models\ChatMessage;
use App\Models\MessageReceivers;
use App\Models\FirstHiMessage;
use DB;
use Validator;
use App\Events\ChatUsersEvent;

class ChatController extends Controller
{
    /**
     * This function is used for send message functionality
     * params required: token => for authentication
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
            $is_single = 0;
            // to check user is logged in or not
            if(!isset($currentUser) && empty($currentUser)){ 
                $responseData['status'] = 401;
                $responseData['message'] = 'Authentication required';
                DB::rollback();
                return $this->commonResponse($responseData, 401);
            }

            $groupId = $request->group_id;
            $newUserExist = null;
            //to check new user is exist in DB or not 
            if(isset($request->new_user_id)){
                $where = [
                    'id' => $request->new_user_id,
                    'status' => 1
                ];

                $newUserExist = User::select('id','status','email','username')->where($where)->first();
                if(!isset($newUserExist) && empty($newUserExist)){
                    $responseData['status'] = 200;
                    $responseData['message'] = 'This new user is not exist';
                    DB::rollback();
                    return $this->commonResponse($responseData, 401);
                }
            }

            // to check sender is sending message to himself
            if($request->new_user_id == $currentUser->id ){ 
                $responseData['status'] = 200;
                $responseData['message'] = 'You can\'t send a message to yourself.';
                DB::rollback();
                return $this->commonResponse($responseData, 200);
            }

            $chatGroupId = null;
            $newGroup = 0;
            $allReceivers = [];
            $members_array = array(); 
            $members_array[] = $request->new_user_id; 
            $members_array[] = $currentUser->id; 

            $isGroup = $this->getGroupId($members_array);
            
            if(empty($groupId) && empty($isGroup)){
                $is_single = 1;

                $group = ChatGroup::firstOrCreate(['id' => (int)$request->group_id],['is_single' => $is_single, 'created_by' => $currentUser->id ,'group_name' => 'NULL', 'group_image' => 'NULL']);

                $createMembers = [];
                $createReceivers = [];
                // dd($members_array);
                foreach($members_array as $member){
                    $createMembers[] = [
                        'user_id' => (int)$member,
                        'group_id' => $group->id,
                        'created_at' => date('Y-m-d H:i:s'),
                    ];
                    
                    $createReceivers[] = [
                        'receiver_id' => (int)$member,
                        'group_id' => $group->id,
                        'created_at' => date('Y-m-d H:i:s'),
                        'is_read' => 0,
                    ];

                    $allReceivers = [$member];
                }
                
                if(!empty($createMembers)){
                    ChatMember::insert($createMembers);
                }
                // if(!empty($createReceivers)){
                //     MessageReceivers::insert($createReceivers);
                // }
                
                $chatGroupId = $group->id;
                $newGroup = 1;
                // DB::commit();
            } else {
                
                $chatGroup = ChatGroup::where('id', $groupId)->first();

                if(!isset($chatGroup) && empty($chatGroup)){
                    if(empty($isGroup)){
                        $responseData['status'] = 404;
                        $responseData['message'] = 'Group is not found. Please check your group id';
                        DB::rollback();
                        return $this->commonResponse($responseData, 404);
                    } 
                }
                if(!empty($isGroup)){
                    $chatGroupId = $isGroup;        
                } else if(isset($chatGroup) && !empty($chatGroup)) {
                    $chatGroupId = $chatGroup->id;
                }
                
                $newGroup = 0;
            }

            $isHi = $request->is_hi;
            // to check the first message is in single chat
            if($newGroup == 0 && isset($isHi) && $is_single != 1){
                $responseData['status'] = 200;
                $responseData['message'] = 'You have already sent the first Hi message';
                DB::rollback();
                return $this->commonResponse($responseData, 200);
            }
            if($newGroup != 1 && isset($isHi) && $is_single == 1){
                $responseData['status'] = 200;
                $responseData['message'] = 'You have already sent the first Hi message';
                DB::rollback();
                return $this->commonResponse($responseData, 200);
            } else if($newGroup == 1) {
                if(isset($isHi) && (int)$isHi === 1){
                    if(isset($newUserExist) && $newUserExist != null){
                        $request->message = 'Hi '. $newUserExist->username;
    
                        $FirstHiMessageData = new FirstHiMessage();
                        $FirstHiMessageData->sender_id = $currentUser->id;
                        $FirstHiMessageData->receiver_id = $newUserExist->id;
                        $FirstHiMessageData->save();
                    } else {
                        $responseData['status'] = 200;
                        $responseData['message'] = 'Receiver not exist';
                        DB::rollback();
                        return $this->commonResponse($responseData, 200);
                    }
                } else {
                    // $responseData['status'] = 200;
                    // $responseData['message'] = 'For first hi message you need to pass as 1';
                    // DB::rollback();
                    // return $this->commonResponse($responseData, 200);
                }
            }

            if($chatGroupId != null){
                $total_members = ChatMember::where('group_id',$chatGroupId)->with('user:id,username,email,deleted_at')->whereHas('user',function($query){
                    $query->where('deleted_at', null)->where('status',1);
                })->get();

                if($total_members->count() == 2){
                    $is_single = 1;
                }

                if($total_members->count() == 1){
                    $responseData['status'] = 200;
                    $responseData['message'] = 'Please add members to the group';
                    DB::rollback();
                    return $this->commonResponse($responseData, 404);
                }

                $message = $request->message;

                $messages = ChatMessage::create([
                    'sender_id' => $currentUser->id,
                    'text' => $message,
                    'group_id' => $chatGroupId,
                    'is_forwarded' => 0,
                ]);
                
                $allReceivers = $total_members->pluck('user_id');
                
                $messageReceiversData = [];
                if($allReceivers != null){
                    foreach ($allReceivers as $receiver) {
                        $messageReceiversData[] = ['receiver_id' => $receiver, 'group_id' => $chatGroupId, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),'is_read' => 0, 'message_id' => $messages->id ];
                    }
                    MessageReceivers::insert($messageReceiversData);
                }

                ChatGroup::where('id',$chatGroupId)->update(['last_updated' => date('Y-m-d H:i:s')]);
                
                DB::commit();

                /**
                 * ChatUsersEvent is for, if one side user will send the message then other member will receive the message instantly.
                */

                $chatMessageData = [
                    'group_id' => $chatGroupId,
                    'user_id' => $currentUser->id,
                    'message' => $message,
                    'message_id' => $messages->id,
                ];
                /**
                 * socket emit
                */
                $socketDataCM = array(
                    'event' => 'chatMessageAdd',
                    'data' => (object)$chatMessageData,
                );
                
                event(new ChatUsersEvent($socketDataCM));

                $results = array();
                $results['id'] = $messages->id;
                $results['sender_id'] = $messages->sender_id;
                $results['text'] = $messages->text;
                $results['group_id'] = $messages->group_id;
                $results['created_at'] = $messages->created_at;

                $responseData['data'] = (object)$results;
                $responseData['status'] = 200;
                $responseData['message'] = 'Message sent successfully';
                return $this->commonResponse($responseData, 200);
            } else {
                DB::rollback();
                $responseData['status'] = 500;
                $responseData['message'] = 'Something went wrong...GroupId not found';
                return $this->commonResponse($responseData, 200);
            }

        } catch (\Exception $e) {
            DB::rollback();
            $catchError = 'Error code: '.$e->getCode().PHP_EOL;
            $catchError .= 'Error file: '.$e->getFile().PHP_EOL;
            $catchError .= 'Error line: '.$e->getLine().PHP_EOL;
            $catchError .= 'Error message: '.$e->getMessage().PHP_EOL;
            \Log::emergency($catchError);

            $responseData['message'] = "Something went wrong";
            return $this->commonResponse($responseData, 500);
        }
    }

    public function getGroupId($members_array){
        $Query = DB::table('chat_members as main')->select('group_id');
            foreach ($members_array as $key => $single) {
                $Query->whereIn('main.group_id', function($query) use($single){
                    $query->select('group_id')->from('chat_members')->where('user_id',$single);
                });
            }
            $Query->whereIn('user_id',$members_array)->groupBy('group_id')
            ->having(DB::raw('COUNT(main.user_id)'),"=", DB::raw('(select count(user_id) FROM chat_members AS counter_chat WHERE counter_chat.group_id = main.group_id)'));
            $isGroup = $Query->first();
            $alreadyGroup = $Query->first();

        $groupId = "";
        if(!empty($alreadyGroup)){
            $groupId = $alreadyGroup->group_id;
        }
        return $groupId;
    }
    /**
     * 
    */
    public function myChats(Request $request){
        $responseData = array();
        $responseData['status'] = 0;
        $responseData['message'] = '';
        $responseData['data'] = (object) [];
        try{

            $limit = isset($request->limit) ? $request->limit : 10;
            $page = isset($request->page) ? $request->page : 1;
            $currentUser = $request->get('user');
            $userExist = User::select('id','status','username')
                            ->where('id',$currentUser->id)
                            ->where('status',1)
                            ->first();

            if(isset($userExist) && !empty($userExist)){
                $chatsGroups = ChatGroup::select('id','group_name','group_image','is_single')->with(['chatMembers:id,group_id,user_id,deleted_at','chatMembers.user.isBlocked','unreadMessage' => function($q) use ($currentUser){
                    $q->where('receiver_id',$currentUser->id);
                },'chatMembers.user'=> function($query) use ($request){
                    $query->select("id","username","logo","deleted_at");
                },'chatMessages:id,group_id,sender_id,text,updated_at'])
                ->whereHas('chatMembers' , function($query) use ($currentUser,$request){
                    $query->where('user_id', $currentUser->id);
                })->whereHas('chatMembers.user',function($query1) use ($request){
                    $query1->where('username', 'like', "%".$request->keyword."%");
                })
                ->wherehas('unreadMessage' , function($query) use ($currentUser){
                    $query->where('receiver_id',$currentUser->id);
                })
                ->where('deleted_at',null)
                ->orderBy('last_updated','DESC')
                ->get();
                
                for($i = 0; $i < $chatsGroups->count(); $i++){
                        // foreach ($chatsGroups[$i]['chatMembers'] as $key => $member) {
                        //     if($member->user_id == $currentUser->id){
                        //         unset($chatsGroups[$i]['chatMembers'][$key]);
                        //     }
                        // }
                        if(isset($chatsGroups[$i]['unreadMessage']) && !empty($chatsGroups[$i]['unreadMessage'])){
                            $chatsGroups[$i]['unreadMessageCout'] = count($chatsGroups[$i]['unreadMessage']);
                            unset($chatsGroups[$i]['unreadMessage']);
                        }

                        if(isset($chatsGroups[$i]['chatMembers']) && !empty($chatsGroups[$i]['chatMembers'])){
                             foreach ($chatsGroups[$i]['chatMembers'] as $key => $member) {
                                 if(isset($member->user->isBlocked)){
                                    if(count($member->user->isBlocked) == 0){
                                        $member->user->is_blocked = 0;
                                    }else{
                                        $member->user->is_blocked = 1;
                                    }
                                    unset($member->user->isBlocked);
                                }
                            }
                        }

                        if(isset($chatsGroups[$i]['chatMessages']) && isset($chatsGroups[$i]['chatMessages'][0])){
                            $chatsGroups[$i]['lastMessage'] = $chatsGroups[$i]['chatMessages'][0]['text'];
                        }else{
                            $chatsGroups[$i]['lastMessage'] = 'No messages found';
                        }
                        unset($chatsGroups[$i]['chatMessages']);
                }

                $result = $chatsGroups->toArray();
                if($chatsGroups->count() > 0){
                    $skip = ((int)$page - 1) * (int)$limit;
                    $total = $limit + $skip;
                    $chatsGroups = array_slice($result,$skip, $total);
                }
                $responseData['data'] = $chatsGroups;
                $responseData['status'] = 200;
                $responseData['message'] = 'My Chats';
                return $this->commonResponse($responseData, 200);
            } else {
                $responseData['status'] = 500;
                $responseData['message'] = 'Something went wrong...GroupId not found';
                return $this->commonResponse($responseData, 200);
            }


        } catch (\Exception $e) {
            dd($e->getMessage());
            DB::rollback();
            $catchError = 'Error code: '.$e->getCode().PHP_EOL;
            $catchError .= 'Error file: '.$e->getFile().PHP_EOL;
            $catchError .= 'Error line: '.$e->getLine().PHP_EOL;
            $catchError .= 'Error message: '.$e->getMessage().PHP_EOL;
            \Log::emergency($catchError);

            $responseData['message'] = "Something went wrong";
            return $this->commonResponse($responseData, 500);
        }
    }
}