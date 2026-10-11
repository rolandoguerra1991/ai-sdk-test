export type Conversation = {
    id: string;
    title: string;
    participant_type: string;
    participant_id: number;
    created_at: string;
    updated_at: string;
    messages?: Message[];
}

export type MessageRole = 'user' | 'assistant';

export type MessageStatus = 'completed' | 'failed' | 'paused';

export type ToolCallStatus = 'running' | 'completed';

export type AttachmentKind = 'image' | 'spreadsheet' | 'document';

export type Attachment = {
    name: string;
    size: string;
    kind: AttachmentKind;
};

export type ToolCall = {
    name: string;
    description: string;
    status: ToolCallStatus;
};

export type Message = {
    id: string;
    conversation_id: string;
    participant_type: string | null;
    participant_id: number | null;
    agent: string;
    role: MessageRole;
    content: string;
    attachments: Record<string, unknown>[];
    steps: Record<string, unknown>[];
    usage: Record<string, unknown> | unknown[];
    meta: Record<string, unknown> | unknown[];
    status: MessageStatus;
    created_at: string;
    updated_at: string;
    tool_calls: Record<string, unknown>[];
    tool_results: Record<string, unknown>[];
    provider_tool_calls: Record<string, unknown>[];
};
