<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { create, store } from '@/routes/conversations';
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

const form = useForm({
    message: '',
    attachments: [],
});

function submit() {
    form.post(store().url);
}

</script>

<template>
    <Head title="New Conversation" />

    <ChatAgent
        :on-submit="submit"
        :processing="form.processing"
        v-model:messageInput="form.message"
        v-model:pendingAttachments="form.attachments"
     />
</template>
