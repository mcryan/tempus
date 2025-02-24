<template>
    <Head title="Timer" />
    <AppLayout>
        <TimeTracker class="w-full" />
    </AppLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import TimeTracker from '@/Pages/TimeTracker.vue'
</script> 