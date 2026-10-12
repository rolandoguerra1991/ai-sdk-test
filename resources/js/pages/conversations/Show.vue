<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Conversation } from '@/types/conversation';
import ChatAgent from '@/components/ChatAgent.vue';
import { update } from '@/routes/conversations';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Chat',
                href: '',
            },
        ],
    },
});

const props = defineProps<{
    conversation: Conversation;
}>();
</script>

<template>
    <Head :title="conversation.title" />

    <ChatAgent
        :conversation="conversation"
        :stream-url="update(conversation.id).url"
        stream-method="PATCH"
    />
</template>
