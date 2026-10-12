<?php

namespace App\Http\Controllers;

use App\Ai\Agents\Assistant;
use Illuminate\Http\Request;
use Laravel\Ai\Models\Conversation;
use Symfony\Component\HttpFoundation\Response;

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
    public function store(Request $request): Response
    {
        $validated = $request->validate([
            'message' => ['required', 'string'],
        ]);

        $response = (new Assistant)
            ->forUser($request->user())
            ->stream($validated['message'])
            ->usingAgentUserInteractionProtocol()
            ->toResponse($request);

        $response->headers->set('X-Accel-Buffering', 'no');

        return $response;
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
    public function update(Request $request, string $id): Response
    {
        $validated = $request->validate([
            'message' => ['required', 'string'],
        ]);

        $response = (new Assistant)
            ->continue($id, as: $request->user())
            ->stream($validated['message'])
            ->usingAgentUserInteractionProtocol()
            ->toResponse($request);

        $response->headers->set('X-Accel-Buffering', 'no');

        return $response;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
