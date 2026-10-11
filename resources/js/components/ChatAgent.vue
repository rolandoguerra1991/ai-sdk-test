<script setup lang="ts">
import {
    Attachment,
    AttachmentKind,
    ChatMessage,
    Conversation
} from '@/types/conversation';

import {
    Bot,
    CircleCheck,
    CornerDownLeft,
    FileSpreadsheet,
    FileText,
    Image,
    LoaderCircle,
    Mic,
    Paperclip,
    Send,
    Sparkles,
    Wrench,
    X,
} from '@lucide/vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ref } from 'vue';

const props = defineProps<{
    conversation?: Conversation;
    onSubmit: (message: string, attachments: Attachment[]) => void;
    processing: boolean;
}>()

const attachmentIcons: Record<AttachmentKind, typeof FileText> = {
    image: Image,
    spreadsheet: FileSpreadsheet,
    document: FileText,
};

const messages: ChatMessage[] = [];

const pendingAttachments: Attachment[] = [];

const isThinking = false;

const suggestedPrompts: string[] = [];

const messageInput = ref('');

function submit(): void {
    if (messageInput.value.trim() === '') {
        return;
    }

    props.onSubmit(messageInput.value, pendingAttachments);
    messageInput.value = '';
    pendingAttachments.length = 0;
}
</script>

<template>
    <h1 class="sr-only">Chat with the agent</h1>

    <div class="flex h-[calc(100svh-4rem)] flex-col md:h-[calc(100svh-5rem)]">
        <div class="min-h-0 flex-1 overflow-y-auto">
            <div class="mx-auto flex w-full max-w-3xl flex-col gap-6 px-4 py-6 md:px-6">
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

                            <div
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
                            </div>

                            <span class="text-[11px] text-muted-foreground">{{
                                message.timestamp
                            }}</span>
                        </div>
                    </div>

                    <div v-else class="flex gap-3">
                        <Avatar class="mt-0.5 size-8 shrink-0">
                            <AvatarFallback class="bg-primary/10 text-primary">
                                <Bot class="size-4" />
                            </AvatarFallback>
                        </Avatar>

                        <div class="flex min-w-0 flex-1 flex-col gap-2">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-semibold">{{
                                    message.author
                                }}</span>
                                <span
                                    class="text-[11px] text-muted-foreground"
                                    >{{ message.timestamp }}</span
                                >
                            </div>

                            <div
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
                            </div>

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

        <div class="shrink-0 border-t border-sidebar-border/70 px-4 py-4 md:px-6">
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
                                    props.processing ||
                                    messageInput.trim() === ''
                                "
                                aria-label="Send message"
                            >
                                <Send class="size-4" />
                            </Button>
                        </div>
                    </div>
                </form>

                <div class="mt-3 flex flex-wrap justify-center gap-2">
                    <Badge
                        v-for="prompt in suggestedPrompts"
                        :key="prompt"
                        variant="outline"
                        class="cursor-pointer font-normal text-muted-foreground"
                    >
                        {{ prompt }}
                    </Badge>
                </div>

                <p class="mt-2 text-center text-[11px] text-muted-foreground">
                    The agent can use tools and make mistakes. Verify important
                    information.
                </p>
            </div>
        </div>
    </div>
</template>
