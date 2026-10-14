<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import DocumentShell from '../components/document-server/DocumentShell.vue';

defineProps<{
    viewer: { name: string; role: string; avatar: string };
    activity: {
        data: {
            id: number;
            event: string;
            document_title: string | null;
            created_at: string;
            user_name: string | null;
        }[];
        prev_page_url: string | null;
        next_page_url: string | null;
    };
}>();
</script>
<template>
    <Head title="Audit Log — Document Server" />
    <DocumentShell active="activity" :viewer="viewer">
        <div class="ds-heading">
            <div>
                <h1>Audit Log</h1>
                <p>
                    Trace document creation, access, updates, sharing and
                    deletion.
                </p>
            </div>
        </div>
        <section class="ds-dir-panel">
            <div
                v-for="event in activity.data"
                :key="event.id"
                class="ds-dir-row"
            >
                <img src="/images/navigation/audit-log.png" alt="" />
                <div>
                    <strong
                        >{{ event.user_name || 'Former user' }} ·
                        {{ event.event }}</strong
                    ><small>{{
                        event.document_title || 'Deleted document'
                    }}</small>
                </div>
                <span class="ds-dir-right">{{
                    new Date(event.created_at).toLocaleString('en-GB')
                }}</span>
            </div>
            <p v-if="!activity.data.length">No recorded activity yet.</p>
            <div class="ds-dir-paging">
                <Link
                    v-if="activity.prev_page_url"
                    :href="activity.prev_page_url"
                    >Previous</Link
                ><Link
                    v-if="activity.next_page_url"
                    :href="activity.next_page_url"
                    >Next</Link
                >
            </div>
        </section>
    </DocumentShell>
</template>
