<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { create } from '@/routes/conversations';
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

async function onSubmit(message: string, attachments: any[]): Promise<void> {
    // Handle the submission of the message and attachments here
    console.log('Message:', message);
    console.log('Attachments:', attachments);
}
</script>

<template>
    <Head title="New Conversation" />

    <ChatAgent
        :on-submit="onSubmit"
        :processing="form.processing" />
</template>
