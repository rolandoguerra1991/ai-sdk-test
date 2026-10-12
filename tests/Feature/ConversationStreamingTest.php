<?php

use App\Ai\Agents\Assistant;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Ai\Models\Conversation;

test('new conversations stream and persist the assistant response', function () {
    $user = User::factory()->create();

    Assistant::fake(['A streamed answer.']);

    $response = $this->actingAs($user)
        ->withHeader('Sec-Fetch-Site', 'same-origin')
        ->post(route('conversations.store'), [
            'message' => 'Hello',
        ]);

    $response->assertOk()
        ->assertHeaderContains('content-type', 'text/event-stream')
        ->assertHeader('x-accel-buffering', 'no');

    $events = collect(explode("\n\n", trim($response->streamedContent())))
        ->filter(fn (string $frame): bool => str_starts_with($frame, 'data: {'))
        ->map(fn (string $frame): array => json_decode(substr($frame, 6), true, flags: JSON_THROW_ON_ERROR));

    $startedEvent = $events->firstWhere('type', 'RUN_STARTED');
    $streamedText = implode(
        '',
        $events->where('type', 'TEXT_MESSAGE_CONTENT')->pluck('delta')->all(),
    );

    expect($streamedText)->toBe('A streamed answer.');
    expect($startedEvent)->toBeArray();

    $conversationId = $startedEvent['threadId'];

    $this->assertDatabaseHas(config('ai.conversations.tables.conversations', 'agent_conversations'), [
        'id' => $conversationId,
        'participant_id' => $user->id,
    ]);

    $this->assertDatabaseHas(config('ai.conversations.tables.messages', 'agent_conversation_messages'), [
        'conversation_id' => $conversationId,
        'role' => 'assistant',
        'content' => 'A streamed answer.',
    ]);
});

test('existing conversations stream assistant replies', function () {
    $user = User::factory()->create();
    $conversation = Conversation::create([
        'id' => (string) Str::uuid(),
        'participant_type' => Conversation::participantType($user),
        'participant_id' => $user->id,
        'title' => 'Existing conversation',
    ]);

    Assistant::fake(['A streamed follow-up.']);

    $response = $this->actingAs($user)
        ->withHeader('Sec-Fetch-Site', 'same-origin')
        ->patch(route('conversations.update', $conversation->id), [
            'message' => 'Continue our conversation',
            'conversation_id' => (string) Str::uuid(),
        ]);

    $response->assertOk()
        ->assertHeaderContains('content-type', 'text/event-stream')
        ->assertHeader('x-accel-buffering', 'no');

    $events = collect(explode("\n\n", trim($response->streamedContent())))
        ->filter(fn (string $frame): bool => str_starts_with($frame, 'data: {'))
        ->map(fn (string $frame): array => json_decode(substr($frame, 6), true, flags: JSON_THROW_ON_ERROR));

    $startedEvent = $events->firstWhere('type', 'RUN_STARTED');
    $streamedText = implode(
        '',
        $events->where('type', 'TEXT_MESSAGE_CONTENT')->pluck('delta')->all(),
    );

    expect($startedEvent)->toBeArray();
    expect($startedEvent['threadId'])->toBe($conversation->id);
    expect($streamedText)->toBe('A streamed follow-up.');
});
