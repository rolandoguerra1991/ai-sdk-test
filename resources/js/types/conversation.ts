export type Conversation = {
    id: string;
    title: string;
    participant_type: string;
    participant_id: number;
    created_at: string;
    updated_at: string;
}

export type MessageRole = 'user' | 'assistant';

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

export type ChatMessage = {
    id: number;
    role: MessageRole;
    author: string;
    content: string;
    timestamp: string;
    toolCalls?: ToolCall[];
    attachments?: Attachment[];
};
