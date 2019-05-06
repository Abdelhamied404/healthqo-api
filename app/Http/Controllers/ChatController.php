<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Chat;
use App\Http\Resources\LogResource;
use App\Http\Resources\ErrorResource;
use App\Recipient;
use App\User;
use App\Message;
use App\Events\Chatting;

class ChatController extends Controller
{
    public function index(Request $req)
    {
        $user_id = $req->user()['id'];
        $chat = $this->getChatsOfUser($user_id);
        return new LogResource(["message" => "chats found", "chats" => $chat]);
    }

    public function show(Request $req, $id)
    {
        // get a specific chat with paginated messages
        $chat = Chat::with('messages', 'recipients.user:id,name,username,avatar')->find($id);
        // check if exists
        if (!isset($chat))
            return new ErrorResource(["message" => "no chat history"]);

        return new LogResource(["message" => "chat found", "chat" => $chat]);
    }

    public function store(Request $req)
    {
        // create a new chat
        /**
         * pre
         */
        // validate
        $req->validate([
            "recipients" => "required|array",
            "recipients.*" => "integer|exists:users,id"
        ]);
        // add current user to recipients
        $recipients = $req->recipients;
        array_push($recipients, $req->user()['id']);
        //checks if already have chat history
        $chat = $this->getChatsbetween($recipients);
        if (count($chat)) {
            return new LogResource(["message" => "already have chat history", "chat" => $chat]);
        }

        /** else
         * make a new chat
         */
        $title = User::find($recipients[0])['username'];
        if (count($recipients) > 2) {
            $title = $req->title ? $req->title : substr(md5(mt_rand()), 0, 7);
        }
        $chat = new chat([
            "title" => $title
        ]);
        //check if can be saved
        if (!$chat->save())
            return new ErrorResource(["message" => "can't create chat"]);

        /**
         * preparing recipients
         */
        $chat_id = $chat['id'];
        $data = array();
        $i = 0;
        foreach ($recipients as $r) {
            $data[$i]["user_id"] = $r;
            $data[$i]["chat_id"] = $chat_id;
            $i++;
        }
        // try to insert all recipients to the chat
        if (!Recipient::insert($data))
            return new ErrorResource(["message" => "can't add recipients to chat"]);
        // output
        $chat = Chat::with('messages', 'recipients.user:id,name,username,avatar')->find($chat_id);
        return new LogResource(["message" => "chat created", "chat" => $chat]);
    }

    // send message
    public function sendMessage(Request $req)
    {
        $user = $req->user();

        $req->validate([
            "chat_id" => "required|integer|exists:chats,id",
            "message" => "required|string|"
        ]);

        $chat_id = $req->chat_id;
        $body = $req->message;

        // check if the user exists on this chat
        $exists = Recipient::where('chat_id', $chat_id)->where("user_id", $user->id)->get();
        if (!count($exists))
            return new ErrorResource(["message" => "you are not member of this chat yet"]);


        $msg = new Message([
            "body" => $body,
            "user_id" => $user->id,
            "chat_id" => $chat_id
        ]);

        if (!$msg->save())
            return new ErrorResource(["message" => "can't send your message"]);

        $msg = Message::with("user", "chat.recipients.user")->find($msg->id);

        event(new Chatting($msg));

        return new LogResource(["message" => "message sent", "msg" => $msg]);
    }

    /**
     * utils
     */
    private function getChatsOfUser($user_id)
    {
        // get all chats that belongs to user
        return Chat::whereHas('recipients', function ($query) use ($user_id) {
            $query->where('user_id', $user_id);
        })->with('messages', 'recipients.user:id,name,username,avatar')->get();
    }

    private function getChatsbetween($recipients)
    {
        // get chat between multi users
        return Chat::has('recipients', '=', count($recipients))
            ->whereHas('recipients', function ($query) use ($recipients) {
                $query->whereIn('user_id', $recipients);
            }, '=', count($recipients))
            ->with('messages', 'recipients.user:id,name,username,avatar')->get();

    }


}
