<script setup lang="ts">
import { Head, useHttp } from '@inertiajs/vue3';
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
    conversation: Conversation
}>()

const request = useHttp({
    message: '',
    conversation_id: props.conversation.id,
    attachments: []
});

function submit() {
    request.patch(update(props.conversation.id).url, {
        onSuccess: (response) => {
            console.log('response', response)
            request.reset('message')
        },
    });
}
</script>

<template>
    <Head :title="conversation.title" />

    <ChatAgent
        :conversation="conversation"
        :on-submit="submit"
        :processing="request.processing"
        v-model:messageInput="request.message"
        v-model:pendingAttachments="request.attachments"
     />
</template>
