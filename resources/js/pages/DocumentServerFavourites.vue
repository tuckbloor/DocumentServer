<script setup lang="ts">
import { onBeforeUnmount, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import DocumentShell from '../components/document-server/DocumentShell.vue';
import '../../css/document-server-favourites.css';

defineOptions({ layout: null });

type DocumentEntry = {
    id: number;
    title: string;
    filename: string;
    extension: string;
    size_bytes: number;
    version: number;
    updated_at: string;
    owner: string;
    category: string;
    mine: boolean;
};
type PageData = {
    data: DocumentEntry[];
    total: number;
    from: number | null;
    to: number | null;
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};
const props = defineProps<{
    viewer: { name: string; role: string; avatar: string };
    unreadNotifications: number;
    documents: PageData;
    count: number;
    filters: { search: string; type: string; sort: string };
}>();

const search = ref(props.filters.search ?? '');
const type = ref(props.filters.type ?? '');
const sort = ref(props.filters.sort ?? 'latest');
const removingId = ref<number | null>(null);
let timer: ReturnType<typeof setTimeout> | undefined;

watch(() => props.filters, filters => {
    search.value = filters.search ?? '';
    type.value = filters.type ?? '';
    sort.value = filters.sort ?? 'latest';
}, { deep: true });
onBeforeUnmount(() => { if (timer) clearTimeout(timer); });

function applyFilters() {
    if (timer) clearTimeout(timer);
    router.get('/document-server/favourites', {
        search: search.value.trim(), type: type.value, sort: sort.value,
    }, { preserveState: true, preserveScroll: true, replace: true });
}
function onSearch(value: string) {
    search.value = value;
    if (timer) clearTimeout(timer);
    timer = setTimeout(applyFilters, 320);
}
function removeFavourite(document: DocumentEntry) {
    if (removingId.value !== null) return;
    removingId.value = document.id;
    router.post(`/documents/${document.id}/favourite`, {}, {
        preserveScroll: true,
        onFinish: () => { removingId.value = null; },
    });
}
function dateText(value: string) {
    return new Date(value).toLocaleDateString('en-GB', {
        day: 'numeric', month: 'short', year: 'numeric',
    });
}
function fileImage(extension: string) {
    return `/images/documents/${extension === 'docx' ? 'word' : extension === 'xlsx' ? 'excel' : 'generic'}.png`;
}
</script>

<template>
    <Head title="Favourites — Document Server" />
    <DocumentShell
        active="favourites" :viewer="viewer" :unread="unreadNotifications"
        :search="search" @update:search="onSearch" @search="applyFilters"
    >
        <div class="ds-heading dsf-heading">
            <div>
                <div class="dsf-breadcrumb"><Link href="/dashboard">Document Server</Link><span>/</span><span>Favourites</span></div>
                <h1>Favourite Documents</h1>
                <p>Your saved Word and Excel files, ready to open whenever you need them.</p>
            </div>
            <Link href="/dashboard" class="ds-outline dsf-library-link">All Documents</Link>
        </div>

        <section class="dsf-summary" aria-label="Favourites overview">
            <img src="/images/navigation/favourites.png" alt="" />
            <div><strong>{{ count }}</strong><span>{{ count === 1 ? 'Favourite document' : 'Favourite documents' }}</span></div>
            <small>Only documents you own or are currently allowed to access appear here.</small>
        </section>

        <section class="ds-documents dsf-panel">
            <div class="ds-documents-tabs dsf-toolbar">
                <div>
                    <h2>Saved documents</h2>
                    <p>Remove a favourite using the button on its card.</p>
                </div>
                <div class="dsf-filters">
                    <select v-model="type" aria-label="Filter by document type" @change="applyFilters">
                        <option value="">All file types</option>
                        <option value="word">Word</option>
                        <option value="excel">Excel</option>
                    </select>
                    <select v-model="sort" aria-label="Sort favourites" @change="applyFilters">
                        <option value="latest">Recently updated</option>
                        <option value="name">Name A–Z</option>
                        <option value="oldest">Oldest updated</option>
                    </select>
                </div>
            </div>

            <div v-if="documents.data.length" class="dsf-grid">
                <article v-for="doc in documents.data" :key="doc.id" class="dsf-card">
                    <div class="dsf-file-details">
                        <img :src="fileImage(doc.extension)" :alt="doc.extension.toUpperCase() + ' document'" />
                        <div>
                            <h3 :title="doc.filename">{{ doc.title }}</h3>
                            <p>{{ doc.category }} · {{ doc.extension.toUpperCase() }}</p>
                            <p>{{ dateText(doc.updated_at) }} · Version {{ doc.version }}</p>
                            <span class="dsf-owner">{{ doc.mine ? 'Owned by you' : `Shared by ${doc.owner}` }}</span>
                        </div>
                    </div>
                    <div class="dsf-actions">
                        <a :href="`/documents/${doc.id}/download`" class="dsf-open">
                            <img src="/images/actions/open.png" alt="" /> Open
                        </a>
                        <button type="button" class="dsf-remove" :disabled="removingId !== null" @click="removeFavourite(doc)">
                            <img src="/images/navigation/favourites.png" alt="" />
                            {{ removingId === doc.id ? 'Removing…' : 'Remove favourite' }}
                        </button>
                    </div>
                </article>
            </div>
            <div v-else class="dsf-empty">
                <img src="/images/empty-states/no-documents.png" alt="" />
                <h3>{{ count ? 'No matching favourites' : 'No favourites yet' }}</h3>
                <p>{{ count ? 'Try changing your search or filters.' : 'Open your library and add a document to your favourites.' }}</p>
                <Link href="/dashboard" class="ds-primary">Browse Documents</Link>
            </div>

            <div v-if="documents.last_page > 1" class="dsf-pagination">
                <span>Showing {{ documents.from }}–{{ documents.to }} of {{ documents.total }}</span>
                <div>
                    <Link v-if="documents.prev_page_url" :href="documents.prev_page_url">← Previous</Link>
                    <span>Page {{ documents.current_page }} of {{ documents.last_page }}</span>
                    <Link v-if="documents.next_page_url" :href="documents.next_page_url">Next →</Link>
                </div>
            </div>
        </section>
    </DocumentShell>
</template>
