<?php

namespace App\Http\Controllers;

use App\Http\Requests\Message\CreateMessageRequest;
use App\Models\Chats;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\SocialNetwork;
use App\Models\SocialNetworksShares;
use App\Models\Contact;

class MessageController extends Controller
{
    private $_cache_key_dialogs = 'Dialogs_for_user_';
    private $_cache_key_messages = 'Current_dialog_';

    public function index() {
        //TODO я писал логику получения сообщений в модели пользователя; пример отображения есть в ЛК пользователя;
        $chats = Chats::where(['creator_id' => \Auth::user()->id])
            ->orWhere(['to_id' => \Auth::user()->id])
            ->orderBy('created_at', 'desc')
            ->paginate(15);
            //Message::with('getFromUser')->where(['to_user' => \Auth::user()->id])->get();
        $social = SocialNetwork::where(['is_active' => 1])->get();
        $data = [
            'contacts' =>[
                'social' => [
                    'links' => $social
                ]
            ]
        ];
        return view('cabinet.mail.index',[
                'chats' => isset($chats) ? $chats : [],
                'data' => $data
            ]);
    }

    public function show($user_id = null)
    {
        $user = null;
        if( !is_null($user_id) ){
            $user = User::find($user_id);
            if( is_null($user) ){
                abort(404);
            }
        }

        $current_user = \Auth::user();

        $key = md5( $this->_cache_key_messages . $current_user->id . '_' . $user_id  );
        \Cache::forget($key);
        $chat = \Cache::rememberForever( $key, function() use ( $user_id, $current_user) {

            Message::where('to_user', $current_user->id)
                ->where('to_delete', 0)
                ->where('from_user', $user_id)
                ->where('is_read', 0)
                ->update(['is_read' => 1]);

            $messages = Message::where(function($query) use( $user_id, $current_user ){
                    $query->where('from_user', $user_id)
                        ->where('to_user', $current_user->id)
                        ->where('to_delete', 0);

                })
                ->orWhere(function($query) use( $user_id, $current_user ){

                    $query->where('from_user', $current_user->id)
                        ->where('to_user', $user_id)
                        ->where('from_delete', 0);

                })
                ->orderBy('id', 'asc')
                ->get();

            $result = [];

            foreach($messages as $message){
                $message->from_user = User::whereId( $message->from_user )->first();
                $message->to_user = User::whereId( $message->to_user )->first();
                $result[] = $message;
            }

            return $result;
        });

        return view('cabinet.mail.chat', [
            'chat' => $chat,
            'user' => $current_user,
            'to_user' => $user,
        ]);
    }

    public function getUser(Request $request)
    {
        $users = User::select(['id', 'login', 'email'])
            ->where('login', 'like', '%' . $request->email['term'] . '%')
            ->orWhere('email', 'like', '%' . $request->email['term'] . '%')
            ->offset($request->page == null ? 0 : $request->page * 10)
            ->limit(10)
            ->get();
        $response = [];
        foreach ($users as $item) {
            $response[] = [
                'id' => $item->id,
                'text' => $item->login . " " . $item->email
            ];
        }

        return response()->json($response);
    }

    public function create(Request $request)
    {
        $validator = \Validator::make($request->all(), with(new CreateMessageRequest())->rules());

        if ($validator->fails()) {
            return redirect()->back()->withInput($request->all())->withErrors($validator->errors());
        }

        $user_id         = $request->get('to_user');
        $current_user_id = \Auth::id();

        if ($current_user_id == $user_id) {
            return redirect()->back()->withInput($request->all())->withErrors(['The sender and receiver are the same']);
        }

        Message::create([
            'from_user'   => $current_user_id,
            'to_user'     => $user_id,
            'message'   => trim(strip_tags($request->get('message'))),
        ]);

        $this->_clearCache($current_user_id, $user_id);

        return redirect()->back();
    }



    private function _clearCache( $user1, $user2 )
    {

        if( $user1 > 0 && $user2 > 0 ){
            \Cache::forget( md5( $this->_cache_key_dialogs . $user1 ) );
            \Cache::forget( md5( $this->_cache_key_dialogs . $user2 ) );
            \Cache::forget( md5( $this->_cache_key_messages . $user1 . '_' . $user2 ));
            \Cache::forget( md5( $this->_cache_key_messages . $user2 . '_' . $user1 ));
        }
        return ;
    }

    public function chat($id)
    {
        $social = SocialNetwork::where(['is_active' => 1])->get();
        $shares = SocialNetworksShares::find(1);
        $email = Contact::email()->get();
        $phones = Contact::phones()->get();
        $chat = Chats::find($id);
        $data = [
            'contacts' => [
                'phones' => $phones,
                'emails' => $email,
                'social' => [
                    'links' => $social,
                    'share' => json_decode($shares->shares)
                ]
            ]
        ];
        return view('chat', ['data' => $data, 'chat' => $chat]);
    }

    public function createChat($id)
    {
        $user_id = \Auth::user()->id;

        $chat = Chats::where([
            ['creator_id', '=', $user_id],
            ['to_id', '=', $id]
        ])
        ->orWhere([
            ['creator_id', '=', $id],
            ['to_id', '=', $user_id]
        ])->first();

        if(!isset($chat)){
            $chat = new Chats();
            $chat->creator_id = $user_id;
            $chat->to_id = $id;
            $chat->save();
        }

        $social = SocialNetwork::where(['is_active' => 1])->get();
        $shares = SocialNetworksShares::find(1);
        $email = Contact::email()->get();
        $phones = Contact::phones()->get();
        $data = [
            'contacts' => [
                'phones' => $phones,
                'emails' => $email,
                'social' => [
                    'links' => $social,
                    'share' => json_decode($shares->shares)
                ]
            ]
        ];
        return redirect()->route('chat', ['chat_id' => $chat->id]);
    }

    public function getMessages(Request $request)
    {
        $chatId = $request->get('chat_id');
        $lastId = $request->get('last_id');
        $pageNumber = $request->get('page');
        $user_id = $request->get('user_id');

        $maxId = $lastId;
        $skip = ($pageNumber - 1) * 10;

        $messages = Message::where([
                ['chat_id', '=', $chatId],
                ['id', '>', $lastId]
            ])->orderBy('created_at', 'desc')->limit(10)->get();

        Message::where(['to_user' => $user_id, 'chat_id' => $chatId])->update(['is_read' => 1]);

        if (count($messages) > 0) {
            $maxId = $messages[0]->id;
        }

        $count = Message::where(['chat_id' => $chatId])->count();

        return [
                'last_id' => $maxId,
                'count' => $count,
                'messages' => $messages
            ];
    }

    public function getScrollMessages(Request $request)
    {
        $count = $request->get('count');
        $chatId = $request->get('chat_id');
        $take = $request->get('take');

        $messages = Message::where(['chat_id' => $chatId])
            ->orderBy('created_at', 'desc')
            ->offset($count)
            ->limit($take)
            ->get();

        return $messages;

    }

    public function sendMessage(Request $request)
    {
        $chat_id = $request->get('chat_id');
        $my_id = $request->get('my_id');
        $text = $request->get('text');

        $chat = Chats::find($chat_id);

        if($chat->creator_id == $my_id){
            $from = $my_id;
            $to = $chat->to_id;
        }else{
            $from = $chat->to_id;
            $to = $chat->creator_id;
        }

        $messages = Message::Create([
            'from_user' => $from,
            'to_user' => $to,
            'message' => $text,
            'chat_id' => $chat_id
        ]);

        return ['status' => 'OK'];
    }


}