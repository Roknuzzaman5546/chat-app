<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    //
    public function index()
{
    $userId = auth()->id();

    $conversations = Conversation::where('user_one_id', $userId)
        ->orWhere('user_two_id', $userId)
        ->with(['userOne', 'userTwo'])
        ->get();

    return response()->json($conversations);
}

public function startConversation(Request $request)
{
    $request->validate(['user_id' => 'required|exists:users,id']);

    $existing = Conversation::where(function ($q) use ($request) {
        $q->where('user_one_id', auth()->id())
          ->where('user_two_id', $request->user_id);
    })->orWhere(function ($q) use ($request) {
        $q->where('user_one_id', $request->user_id)
          ->where('user_two_id', auth()->id());
    })->first();

    if ($existing) return $existing;

    $conversation = Conversation::create([
        'user_one_id' => auth()->id(),
        'user_two_id' => $request->user_id,
    ]);

    return response()->json($conversation);
}

}
