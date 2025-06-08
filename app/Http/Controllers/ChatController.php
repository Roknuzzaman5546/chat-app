<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Conversation;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    //
    public function index($conversationId)
    {
        $conversation = Conversation::findOrFail($conversationId);

        if (!in_array(auth()->id(), [$conversation->user_one_id, $conversation->user_two_id])) {
            abort(403);
        }

        return $conversation->chats()->with(['sender', 'receiver'])->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'message' => 'required|string',
        ]);

        $conversation = Conversation::find($request->conversation_id);

        if (!in_array(auth()->id(), [$conversation->user_one_id, $conversation->user_two_id])) {
            abort(403);
        }

        $receiverId = $conversation->user_one_id == auth()->id()
            ? $conversation->user_two_id
            : $conversation->user_one_id;

        $chat = Chat::create([
            'conversation_id' => $conversation->id,
            'sender_id' => auth()->id(),
            'receiver_id' => $receiverId,
            'message' => $request->message,
        ]);

        broadcast(new \App\Events\MessageSent($chat))->toOthers();

        return response()->json($chat);
    }

    public function view()
    {
        $user = auth()->user();
        $conversations = Conversation::where('user_one_id', $user->id)
            ->orWhere('user_two_id', $user->id)
            ->with(['userOne', 'userTwo'])
            ->latest()->get();

        return view('chat.index', compact('conversations'));
    }


}
