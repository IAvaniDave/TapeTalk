<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\User;
use App\Models\ChatGroup;
use App\Models\ChatMember;
use App\Models\ChatMessage;
use App\Models\MessageReceivers;
use DB;
use Validator;

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
                DB::commit();
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

            $currentUser = $request->get('user');
            // dd($currentUser->id);
            $userExist = User::select('id','status','username')
                            ->where('id',$currentUser->id)
                            ->where('status',1)
                            ->first();
            if(isset($userExist) && !empty($userExist)){
                $chatsGroups = ChatGroup::select('id','group_name','group_image','is_single')->with(['chatMembers:id,group_id,user_id,deleted_at','chatMembers.user:id,username,logo,deleted_at','chatMessages:id,group_id,sender_id,text,updated_at'])
                ->whereHas('chatMembers' , function($query) use ($currentUser){
                    $query->where('user_id', $currentUser->id);
                })
                ->with(['chatMessages' =>  function($query) use ($currentUser){
                    // $query->orderBy('updated_at', 'DESC');
                    $query->orderBy('updated_at', 'DESC');
                }])
                ->where('deleted_at',null)
                ->orderBy('updated_at','DESC')
                ->get();

                // echo "<pre>";print_r($chatsGroups);exit;
                
                for($i = 0; $i < $chatsGroups->count(); $i++){
                    foreach ($chatsGroups[$i]['chatMembers'] as $key => $member) {
                        if($member->user_id == $currentUser->id){
                            unset($chatsGroups[$i]['chatMembers'][$key]);
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
                
                $responseData['data'] = $result;
                $responseData['status'] = 200;
                $responseData['message'] = 'Chat groups founded successfully';
                return $this->commonResponse($responseData, 200);
            } else {
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
}