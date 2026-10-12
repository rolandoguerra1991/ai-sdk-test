<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { create, show, store } from '@/routes/conversations';
import ChatAgent from '@/components/ChatAgent.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Chat',
                href: create(),
            },
        ],
    },
});

function openConversation(conversationId: string): void {
    router.visit(show(conversationId).url);
}
</script>

<template>
    <Head title="New Conversation" />

    <ChatAgent
        :stream-url="store().url"
        stream-method="POST"
        :on-conversation-created="openConversation"
    />
</template>
