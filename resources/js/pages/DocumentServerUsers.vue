<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import DocumentShell from '../components/document-server/DocumentShell.vue';

defineProps<{
    viewer: { name: string; role: string; avatar: string };
    team: {
        id: number;
        name: string;
        email: string;
        role: string;
        job_title: string | null;
        status: string;
        avatar: string;
    }[];
}>();
</script>
<template>
    <Head title="Users — Document Server" />
    <DocumentShell active="users" :viewer="viewer">
        <div class="ds-heading">
            <div>
                <h1>Users</h1>
                <p>People with access to your Document Server.</p>
            </div>
        </div>
        <section class="ds-dir-panel">
            <div v-for="person in team" :key="person.id" class="ds-dir-row">
                <img :src="person.avatar" alt="" />
                <div>
                    <strong>{{ person.name }}</strong
                    ><small
                        >{{ person.email }} ·
                        {{ person.job_title || person.role }}</small
                    >
                </div>
                <span class="ds-dir-right"
                    >{{ person.role }} · {{ person.status }}</span
                >
            </div>
        </section>
    </DocumentShell>
</template>
