<?php

namespace App\Http\Controllers;

use App\Ai\Agents\Assistant;
use Illuminate\Http\Request;
use Laravel\Ai\Models\Conversation;

class ConversationController extends Controller
{
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('conversations/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $response = (new Assistant)
                ->forUser($request->user())
                ->prompt($request->input('message'));

            return to_route('conversations.show', $response->conversationId);
        } catch (\Throwable $th) {
            return back()->withErrors(['message' => $th->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $conversation = Conversation::with('messages')->findOrFail($id);
        return inertia('conversations/Show', [
            'conversation' => $conversation,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $response = (new Assistant)
                ->continue($request->input('conversation_id'), as: $request->user())
                ->prompt($request->input('message'));

            return response()->json($response);

        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
