<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

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
</script>

<template>
    <Head title="Chat" />
</template>
