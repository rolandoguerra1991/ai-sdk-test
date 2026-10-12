<script setup lang="ts">
import type {
    Attachment,
    AttachmentKind,
    Conversation,
    Message,
} from '@/types/conversation';

import {
    Bot,
    CornerDownLeft,
    FileSpreadsheet,
    FileText,
    Image,
    LoaderCircle,
    Mic,
    Paperclip,
    Send,
    Sparkles,
    X,
} from '@lucide/vue';
import { useJsonEventStream } from '@laravel/stream-vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { computed, defineModel, nextTick, reactive, ref } from 'vue';
import { formatDate } from '@/lib/utils';

type ChatMessage = Pick<Message, 'id' | 'role' | 'content' | 'created_at'>;

const props = defineProps<{
    conversation?: Conversation;
    streamUrl: string;
    streamMethod: 'POST' | 'PATCH';
    onConversationCreated?: (conversationId: string) => void;
}>();

const attachmentIcons: Record<AttachmentKind, typeof FileText> = {
    image: Image,
    spreadsheet: FileSpreadsheet,
    document: FileText,
};

const messages = ref<ChatMessage[]>(props.conversation?.messages || []);

const pendingAttachments = defineModel('pendingAttachments', {
    type: Array as () => Attachment[],
    default: () => [],
});

const streamError = ref('');
const messageList = ref<HTMLElement | null>(null);
let activeAssistantMessage: ChatMessage | null = null;
let activeConversationId: string | undefined;
let completedSuccessfully = false;
let streamFailed = false;

type AgentStreamEvent = {
    type: string;
    threadId?: unknown;
    delta?: unknown;
    message?: unknown;
};

const { isFetching, isStreaming, send } = useJsonEventStream<
    AgentStreamEvent,
    { message: string }
>(() => props.streamUrl, {
    headers: {
        Accept: 'application/json, text/event-stream',
        'X-Requested-With': 'XMLHttpRequest',
    },
    onBeforeSend: (request) => ({
        ...request,
        method: props.streamMethod,
    }),
    onEvent: (event) => {
        if (event.type === 'RUN_STARTED') {
            if (typeof event.threadId === 'string') {
                activeConversationId = event.threadId;
            }
        } else if (event.type === 'TEXT_MESSAGE_CONTENT') {
            if (activeAssistantMessage && typeof event.delta === 'string') {
                activeAssistantMessage.content += event.delta;
                scrollToLatestMessage();
            }
        } else if (event.type === 'RUN_ERROR') {
            streamFailed = true;
            streamError.value =
                typeof event.message === 'string'
                    ? event.message
                    : 'The assistant could not complete the response.';
        } else if (event.type === 'RUN_FINISHED') {
            completedSuccessfully = true;
        }
    },
    onParseError: () => {
        streamFailed = true;
        streamError.value = 'The server returned an invalid stream event.';
    },
    onError: (error) => {
        streamFailed = true;
        streamError.value = error.message;
    },
    onFinish: () => {
        if (!completedSuccessfully && !streamFailed) {
            streamFailed = true;
            streamError.value =
                'The conversation stream ended before completion.';
        }

        if (completedSuccessfully && !streamFailed && !props.conversation) {
            if (!activeConversationId) {
                streamError.value =
                    'The server did not identify the new conversation.';
                return;
            }

            props.onConversationCreated?.(activeConversationId);
        }
    },
});

const isProcessing = computed(() => isFetching.value || isStreaming.value);
const isThinking = computed(() => {
    const lastMessage = messages.value[messages.value.length - 1];

    return (
        isProcessing.value &&
        lastMessage?.role === 'assistant' &&
        lastMessage.content === ''
    );
});

const messageInput = defineModel('messageInput', {
    type: String,
    default: '',
});

function scrollToLatestMessage(force = false): void {
    const element = messageList.value;

    if (!element) {
        return;
    }

    const isNearBottom =
        element.scrollHeight - element.scrollTop - element.clientHeight < 80;

    if (!force && !isNearBottom) {
        return;
    }

    void nextTick(() => {
        if (messageList.value) {
            messageList.value.scrollTop = messageList.value.scrollHeight;
        }
    });
}

function submit(): void {
    const submittedMessage = messageInput.value.trim();

    if (submittedMessage === '' || isProcessing.value) {
        return;
    }

    const now = new Date().toISOString();
    const assistantMessage = reactive<ChatMessage>({
        id: crypto.randomUUID(),
        role: 'assistant',
        content: '',
        created_at: now,
    });

    messages.value.push({
        id: crypto.randomUUID(),
        role: 'user',
        content: submittedMessage,
        created_at: now,
    });
    messages.value.push(assistantMessage);
    activeAssistantMessage = assistantMessage;
    activeConversationId = undefined;
    completedSuccessfully = false;
    streamFailed = false;
    scrollToLatestMessage(true);
    streamError.value = '';
    messageInput.value = '';
    pendingAttachments.value.length = 0;
    void send({ message: submittedMessage });
}
</script>

<template>
    <h1 class="sr-only">Chat with the agent</h1>

    <div class="flex h-[calc(100svh-4rem)] flex-col md:h-[calc(100svh-5rem)]">
        <div ref="messageList" class="min-h-0 flex-1 overflow-y-auto">
            <div
                class="mx-auto flex w-full max-w-3xl flex-col gap-6 px-4 py-6 md:px-6"
            >
                <template v-for="message in messages" :key="message.id">
                    <div
                        v-if="message.role === 'user'"
                        class="flex justify-end"
                    >
                        <div class="flex max-w-[85%] flex-col items-end gap-2">
                            <div
                                class="rounded-2xl rounded-tr-sm bg-primary px-4 py-2.5 text-sm leading-relaxed whitespace-pre-line text-primary-foreground"
                            >
                                {{ message.content }}
                            </div>

                            <!-- <div
                                v-if="message.attachments?.length"
                                class="flex flex-wrap justify-end gap-2"
                            >
                                <div
                                    v-for="file in message.attachments"
                                    :key="file.name"
                                    class="flex items-center gap-2 rounded-lg border bg-card px-2.5 py-1.5"
                                >
                                    <component
                                        :is="attachmentIcons[file.kind]"
                                        class="size-4 shrink-0 text-muted-foreground"
                                    />
                                    <div class="text-left">
                                        <p class="text-xs font-medium">
                                            {{ file.name }}
                                        </p>
                                        <p
                                            class="text-[10px] text-muted-foreground"
                                        >
                                            {{ file.size }}
                                        </p>
                                    </div>
                                </div>
                            </div> -->

                            <span class="text-[11px] text-muted-foreground">{{
                                formatDate(message.created_at)
                            }}</span>
                        </div>
                    </div>

                    <div v-else-if="message.content !== ''" class="flex gap-3">
                        <Avatar class="mt-0.5 size-8 shrink-0">
                            <AvatarFallback class="bg-primary/10 text-primary">
                                <Bot class="size-4" />
                            </AvatarFallback>
                        </Avatar>

                        <div class="flex min-w-0 flex-1 flex-col gap-2">
                            <div class="flex items-center gap-2">
                                <!-- <span class="text-sm font-semibold">{{
                                    message.author
                                }}</span> -->
                                <span
                                    class="text-[11px] text-muted-foreground"
                                    >{{ formatDate(message.created_at) }}</span
                                >
                            </div>

                            <!-- <div
                                v-if="message.toolCalls?.length"
                                class="flex flex-col gap-2 rounded-xl border bg-muted/40 p-3"
                            >
                                <p
                                    class="flex items-center gap-1.5 text-[11px] font-medium tracking-wide text-muted-foreground uppercase"
                                >
                                    <Wrench class="size-3.5" />
                                    Tools
                                </p>
                                <div
                                    v-for="tool in message.toolCalls"
                                    :key="tool.name"
                                    class="flex items-center gap-2"
                                >
                                    <CircleCheck
                                        v-if="tool.status === 'completed'"
                                        class="size-4 shrink-0 text-emerald-500"
                                    />
                                    <LoaderCircle
                                        v-else
                                        class="size-4 shrink-0 animate-spin text-primary"
                                    />
                                    <code class="shrink-0 font-mono text-xs">{{
                                        tool.name
                                    }}</code>
                                    <span
                                        class="truncate text-xs text-muted-foreground"
                                        >{{ tool.description }}</span
                                    >
                                </div>
                            </div> -->

                            <div
                                class="text-sm leading-relaxed whitespace-pre-line"
                            >
                                {{ message.content }}
                            </div>
                        </div>
                    </div>
                </template>

                <div v-if="isThinking" class="flex gap-3">
                    <Avatar class="mt-0.5 size-8 shrink-0">
                        <AvatarFallback class="bg-primary/10 text-primary">
                            <Bot class="size-4" />
                        </AvatarFallback>
                    </Avatar>

                    <div class="flex flex-col gap-2">
                        <span class="text-sm font-semibold">AI Agent</span>
                        <div
                            class="flex w-fit items-center gap-2 rounded-xl border bg-muted/40 px-3 py-2"
                        >
                            <LoaderCircle
                                class="size-3.5 animate-spin text-primary"
                            />
                            <span class="text-xs text-muted-foreground"
                                >The agent is thinking</span
                            >
                            <span class="flex items-center gap-1">
                                <span
                                    class="size-1.5 animate-bounce rounded-full bg-muted-foreground/60 [animation-delay:-0.3s]"
                                />
                                <span
                                    class="size-1.5 animate-bounce rounded-full bg-muted-foreground/60 [animation-delay:-0.15s]"
                                />
                                <span
                                    class="size-1.5 animate-bounce rounded-full bg-muted-foreground/60"
                                />
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div
            class="shrink-0 border-t border-sidebar-border/70 px-4 py-4 md:px-6"
        >
            <div class="mx-auto w-full max-w-3xl">
                <div
                    v-if="pendingAttachments.length"
                    class="mb-2 flex flex-wrap gap-2"
                >
                    <div
                        v-for="file in pendingAttachments"
                        :key="file.name"
                        class="flex items-center gap-2 rounded-lg border bg-card px-2.5 py-1.5"
                    >
                        <component
                            :is="attachmentIcons[file.kind]"
                            class="size-4 shrink-0 text-muted-foreground"
                        />
                        <div>
                            <p class="text-xs font-medium">{{ file.name }}</p>
                            <p class="text-[10px] text-muted-foreground">
                                {{ file.size }}
                            </p>
                        </div>
                        <button
                            type="button"
                            class="ml-1 text-muted-foreground transition-colors hover:text-foreground"
                            aria-label="Remove file"
                        >
                            <X class="size-3.5" />
                        </button>
                    </div>
                </div>

                <p
                    v-if="streamError"
                    role="alert"
                    class="mb-2 text-sm text-destructive"
                >
                    {{ streamError }}
                </p>

                <form
                    class="rounded-2xl border bg-card p-2 shadow-sm transition-[color,box-shadow] focus-within:border-ring focus-within:ring-[3px] focus-within:ring-ring/50"
                    @submit.prevent="submit"
                >
                    <textarea
                        v-model="messageInput"
                        rows="1"
                        aria-label="Message to the agent"
                        placeholder="Write a message to the agent…"
                        class="max-h-40 min-h-11 w-full resize-none bg-transparent px-2 py-2.5 text-sm outline-none placeholder:text-muted-foreground"
                        @keydown.enter.exact.prevent="submit"
                    />

                    <div
                        class="flex items-center justify-between gap-2 px-1 pt-1"
                    >
                        <div class="flex items-center gap-1">
                            <Button
                                variant="ghost"
                                size="icon-sm"
                                type="button"
                                aria-label="Attach file"
                            >
                                <Paperclip class="size-4" />
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon-sm"
                                type="button"
                                aria-label="Record audio"
                            >
                                <Mic class="size-4" />
                            </Button>
                            <Button
                                variant="ghost"
                                size="sm"
                                type="button"
                                class="hidden gap-1.5 text-muted-foreground sm:inline-flex"
                            >
                                <Sparkles class="size-4" />
                                Tools
                            </Button>
                        </div>

                        <div class="flex items-center gap-2">
                            <span
                                class="hidden items-center gap-1 text-[11px] text-muted-foreground sm:flex"
                            >
                                <CornerDownLeft class="size-3" />
                                Press Enter to send
                            </span>
                            <Button
                                size="icon"
                                type="submit"
                                :disabled="
                                    isProcessing || messageInput.trim() === ''
                                "
                                aria-label="Send message"
                            >
                                <Send class="size-4" />
                            </Button>
                        </div>
                    </div>
                </form>

                <p class="mt-2 text-center text-[11px] text-muted-foreground">
                    The agent can use tools and make mistakes. Verify important
                    information.
                </p>
            </div>
        </div>
    </div>
</template>
