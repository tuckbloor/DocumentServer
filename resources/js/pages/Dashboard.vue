<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import DocumentShell from '../components/document-server/DocumentShell.vue';
import StandaloneOpenButton from '../components/document-server/StandaloneOpenButton.vue';

type Doc = {
    id: number;
    public_id: string;
    title: string;
    filename: string;
    extension: string;
    category: string;
    owner: string;
    mine: boolean;
    can_edit: boolean;
    can_share: boolean;
    favourite: boolean;
    version: number;
    size_bytes: number;
    updated_at: string;
    deleted_at: string | null;
    shared_with: {
        id: number;
        name: string;
        email: string;
        permission: string;
        avatar: string;
    }[];
};
type PageData = {
    data: Doc[];
    total: number;
    from: number | null;
    to: number | null;
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};
const props = defineProps<{
    documents: PageData;
    stats: {
        total: number;
        word: number;
        excel: number;
        shared: number;
        users: number;
        received: number;
    };
    categories: { id: number; name: string }[];
    users: { id: number; name: string; email: string }[];
    filters: {
        tab: string;
        search: string;
        type: string;
        category: string | number;
        sort: string;
        view: string;
        show_categories: boolean;
    };
    viewer: { name: string; avatar: string; role: string };
    unreadNotifications: number;
}>();
const page = usePage();
const tab = ref(props.filters.tab || 'all');
const search = ref(props.filters.search || '');
const type = ref(props.filters.type || '');
const category = ref(String(props.filters.category || ''));
const sort = ref(props.filters.sort || 'latest');
const view = ref(props.filters.view || 'grid');
// A single editor selection controls every document card on this dashboard.
type DesktopEditor = 'libreoffice' | 'microsoft';
const preferredEditor = ref<DesktopEditor>('libreoffice');
const EDITOR_PREFERENCE_KEY = 'document-server-editor';

onMounted(() => {
    try {
        const saved = localStorage.getItem(EDITOR_PREFERENCE_KEY);
        if (saved === 'libreoffice' || saved === 'microsoft') {
            preferredEditor.value = saved;
        }
    } catch {
        // Browser storage may be disabled; the selector still works.
    }
});

watch(preferredEditor, (editor) => {
    try {
        localStorage.setItem(EDITOR_PREFERENCE_KEY, editor);
    } catch {
        // Don't prevent opening documents when browser storage is unavailable.
    }
});
const uploadOpen = ref(false);
const shareDocument = ref<Doc | null>(null);
const detailsDocument = ref<Doc | null>(null);
const menuId = ref<number | null>(null);
const showFilters = ref(props.filters.show_categories || false);
const modalError = ref('');
const history = ref<
    {
        version_number: number;
        created_at: string;
        change_summary: string | null;
        author: string | null;
        size_bytes: number;
    }[]
>([]);
const uploadForm = useForm<{ files: File[]; category_id: string }>({
    files: [],
    category_id: '',
});
const shareForm = useForm<{ user_id: string; permission: 'view' | 'edit' }>({
    user_id: '',
    permission: 'view',
});
const revisionForm = useForm<{
    file: File | null;
    expected_version: number;
    change_summary: string;
}>({ file: null, expected_version: 1, change_summary: '' });
const isAdmin = computed(() => props.viewer.role === 'Administrator');
const flash = computed(() => (page.props.flash as any)?.success ?? '');
const navigation = [
    ['all', 'All Documents'],
    ['mine', 'My Documents'],
    ['shared', 'Shared with me'],
    ['recent', 'Recent'],
    ['favourites', 'Favourites'],
];
let timer: ReturnType<typeof setTimeout> | undefined;
watch(
    () => props.filters,
    (value) => {
        tab.value = value.tab || 'all';
        search.value = value.search || '';
        type.value = value.type || '';
        category.value = String(value.category || '');
        sort.value = value.sort || 'latest';
        view.value = value.view || 'grid';
        if (value.show_categories) showFilters.value = true;
    },
    { deep: true },
);
onBeforeUnmount(() => {
    if (timer) clearTimeout(timer);
});
function applyFilters(extra: Record<string, string> = {}) {
    const query = {
        tab: tab.value,
        search: search.value,
        type: type.value,
        category: category.value,
        sort: sort.value,
        view: view.value,
        ...extra,
    };
    Object.assign(query, extra);
    router.get('/dashboard', query, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
}
function onSearch() {
    if (timer) clearTimeout(timer);
    timer = setTimeout(() => applyFilters(), 320);
}
function switchTab(value: string) {
    tab.value = value;
    applyFilters({ tab: value });
}
function changeView(value: 'grid' | 'list') {
    view.value = value;
    applyFilters({ view: value });
}
function setFiles(event: Event) {
    uploadForm.files = Array.from(
        (event.target as HTMLInputElement).files || [],
    );
}
function setRevisionFile(event: Event) {
    revisionForm.file = (event.target as HTMLInputElement).files?.[0] ?? null;
}
function upload() {
    modalError.value = '';
    uploadForm.post('/documents', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            uploadOpen.value = false;
            uploadForm.reset();
        },
        onError: () => {
            modalError.value = 'Please check your upload and try again.';
        },
    });
}
function shareOpen(doc: Doc) {
    shareDocument.value = doc;
    shareForm.reset();
    modalError.value = '';
    menuId.value = null;
}
function saveShare() {
    if (!shareDocument.value) return;
    shareForm.post(`/documents/${shareDocument.value.id}/shares`, {
        preserveScroll: true,
        onSuccess: () => {
            shareDocument.value = null;
            shareForm.reset();
        },
    });
}
function revokeShare(id: number) {
    if (!shareDocument.value || !confirm('Remove this user’s access?')) return;
    router.delete(`/documents/${shareDocument.value.id}/shares/${id}`, {
        preserveScroll: true,
        onSuccess: () => {
            shareDocument.value = null;
        },
    });
}
function toggleFavourite(doc: Doc) {
    menuId.value = null;
    router.post(`/documents/${doc.id}/favourite`, {}, { preserveScroll: true });
}
function remove(doc: Doc) {
    menuId.value = null;
    if (confirm(`Move ${doc.filename} to the Recycle Bin?`))
        router.delete(`/documents/${doc.id}`, { preserveScroll: true });
}
function restore(doc: Doc) {
    router.post(`/documents/${doc.id}/restore`, {}, { preserveScroll: true });
}
async function showHistory(doc: Doc) {
    detailsDocument.value = doc;
    menuId.value = null;
    history.value = [];
    modalError.value = '';
    revisionForm.reset();
    revisionForm.expected_version = doc.version;
    try {
        const response = await fetch(`/documents/${doc.id}/versions`, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        });
        if (!response.ok) throw new Error('Unable to load revisions.');
        const data = await response.json();
        history.value = data.versions;
    } catch {
        modalError.value = 'Could not load the document history.';
    }
}
function saveRevision() {
    if (!detailsDocument.value || !revisionForm.file) return;
    revisionForm.post(`/documents/${detailsDocument.value.id}/revisions`, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            detailsDocument.value = null;
            revisionForm.reset();
        },
    });
}
function fileImage(extension: string) {
    return `/images/documents/${extension === 'xlsx' ? 'excel' : 'word'}.png`;
}
function icon(action: string) {
    return `/images/actions/${action}.png`;
}
function dateText(value: string | null) {
    return value
        ? new Date(value).toLocaleDateString('en-GB', {
              day: 'numeric',
              month: 'short',
              year: 'numeric',
          })
        : '—';
}
function closeModals() {
    uploadOpen.value = false;
    shareDocument.value = null;
    detailsDocument.value = null;
    modalError.value = '';
}
</script>

<template>
    <Head title="Dashboard — Document Server" />
    <DocumentShell
        :active="showFilters && category ? 'categories' : tab"
        :viewer="viewer"
        :unread="unreadNotifications"
        v-model:search="search"
        @search="applyFilters"
        @update:search="onSearch"
    >
        <div class="ds-heading">
            <div>
                <h1>Welcome back, {{ viewer.name.split(' ')[0] }}</h1>
                <p>
                    Here are your documents. Click a file to open and edit it.
                </p>
            </div>
            <button
                class="ds-primary ds-upload-top"
                type="button"
                @click="uploadOpen = true"
            >
                <img :src="icon('upload')" alt="" /> Upload Document
            </button>
        </div>

        <div class="ds-stats">
            <div
                v-for="metric in [
                    {
                        label: 'Documents',
                        value: stats.total,
                        caption: 'Total available',
                        img: 'all-documents',
                        tint: 'blue',
                    },
                    {
                        label: 'Word Documents',
                        value: stats.word,
                        caption: 'Microsoft Word',
                        img: 'word-documents',
                        tint: 'indigo',
                    },
                    {
                        label: 'Excel Documents',
                        value: stats.excel,
                        caption: 'Microsoft Excel',
                        img: 'excel-documents',
                        tint: 'green',
                    },
                    {
                        label: 'Shared Documents',
                        value: stats.shared,
                        caption: 'Collaborative files',
                        img: 'shared-documents',
                        tint: 'purple',
                    },
                    {
                        label: 'Users',
                        value: stats.users,
                        caption: 'With access',
                        img: 'total-users',
                        tint: 'violet',
                    },
                ]"
                :key="metric.label"
                class="ds-stat-card"
                :class="`ds-stat-${metric.tint}`"
            >
                <img :src="`/images/statistics/${metric.img}.png`" alt="" />
                <div>
                    <strong>{{ metric.value }}</strong
                    ><span>{{ metric.label }}</span
                    ><small>{{ metric.caption }}</small>
                </div>
            </div>
        </div>
        <p v-if="flash" class="ds-flash" role="status">{{ flash }}</p>

        <section class="ds-documents">
            <div class="ds-documents-tabs">
                <div class="ds-tabs">
                    <button
                        v-for="item in navigation"
                        :key="item[0]"
                        @click="switchTab(item[0])"
                        :class="{ 'ds-tab-active': tab === item[0] }"
                    >
                        {{ item[1]
                        }}<span v-if="item[0] === 'all'">
                            ({{ stats.total }})</span
                        >
                    </button>
                    <button v-if="tab === 'trash'" class="ds-tab-active">
                        Recycle Bin
                    </button>
                </div>
                <div class="ds-view-toggle">
                    <button
                        title="Grid view"
                        aria-label="Grid view"
                        :class="{ active: view === 'grid' }"
                        @click="changeView('grid')"
                    >
                        <img :src="icon('grid-view')" alt="" />
                    </button>
                    <button
                        title="List view"
                        aria-label="List view"
                        :class="{ active: view === 'list' }"
                        @click="changeView('list')"
                    >
                        <img :src="icon('list-view')" alt="" />
                    </button>
                </div>
            </div>
            <div class="ds-documents-filter">
                <button
                    class="ds-filter-button"
                    @click="showFilters = !showFilters"
                >
                    <img :src="icon('filter')" alt="" /> Filter
                </button>
                <select
                    v-model="sort"
                    @change="applyFilters()"
                    aria-label="Sort documents"
                >
                    <option value="latest">Sort: Latest</option>
                    <option value="oldest">Sort: Oldest</option>
                    <option value="name">Sort: Name</option>
                </select>
                <template v-if="showFilters">
                    <select
                        v-model="type"
                        @change="applyFilters()"
                        aria-label="File type"
                    >
                        <option value="">All types</option>
                        <option value="word">Word</option>
                        <option value="excel">Excel</option>
                    </select>
                    <select
                        v-model="category"
                        @change="applyFilters()"
                        aria-label="Category"
                    >
                        <option value="">All categories</option>
                        <option
                            v-for="c in categories"
                            :key="c.id"
                            :value="String(c.id)"
                        >
                            {{ c.name }}
                        </option>
                    </select>
                </template>
                <label class="ds-editor-setting" for="ds-preferred-editor">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="18"
                        height="18"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                    >
                        <rect x="3" y="4" width="18" height="14" rx="2" />
                        <path d="M8 22h8M12 18v4" />
                    </svg>
                    <span>Open with</span>
                    <select
                        id="ds-preferred-editor"
                        v-model="preferredEditor"
                        aria-label="Preferred desktop editor for all documents"
                    >
                        <option value="libreoffice">LibreOffice</option>
                        <option value="microsoft">
                            Microsoft Word / Excel
                        </option>
                    </select>
                </label>
            </div>

            <div
                v-if="documents.data.length"
                class="ds-file-grid"
                :class="{ 'ds-file-list': view === 'list' }"
            >
                <article
                    v-for="doc in documents.data"
                    :key="doc.id"
                    class="ds-file-card"
                >
                    <div class="ds-file-main">
                        <img
                            class="ds-file-image"
                            :src="fileImage(doc.extension)"
                            :alt="`${doc.extension.toUpperCase()} document`"
                        />
                        <div class="ds-file-description">
                            <h3 :title="doc.filename">
                                {{ doc.title }}.{{ doc.extension }}
                            </h3>
                            <p>{{ doc.category }}</p>
                            <p>
                                {{ tab === 'trash' ? 'Deleted' : 'Updated' }}
                                {{
                                    dateText(
                                        tab === 'trash'
                                            ? doc.deleted_at
                                            : doc.updated_at,
                                    )
                                }}
                            </p>
                        </div>
                    </div>
                    <div class="ds-file-sharing-row">
                        <div
                            v-if="doc.shared_with.length"
                            class="ds-avatar-stack"
                        >
                            <img
                                v-for="u in doc.shared_with.slice(0, 3)"
                                :key="u.id"
                                :src="u.avatar"
                                :title="u.name"
                                :alt="u.name"
                            />
                            <span v-if="doc.shared_with.length > 3"
                                >+{{ doc.shared_with.length - 3 }}</span
                            >
                        </div>
                        <div class="ds-file-tags">
                            <span v-if="doc.mine" class="ds-tag ds-owned">{{
                                doc.shared_with.length
                                    ? `Shared with ${doc.shared_with.length}`
                                    : 'Private'
                            }}</span>
                            <span v-else class="ds-tag ds-shared"
                                >Shared with you ·
                                {{ doc.can_edit ? 'Edit' : 'View' }}</span
                            >
                            <span class="ds-version">v{{ doc.version }}</span>
                        </div>
                    </div>
                    <div v-if="tab !== 'trash'" class="ds-file-actions">
                        <StandaloneOpenButton
                            :document-id="doc.id"
                            :filename="doc.filename"
                            :can-edit="doc.can_edit"
                            :editor="preferredEditor"
                            button-class="ds-open"
                        />
                        <button
                            v-if="doc.can_share"
                            class="ds-outline"
                            @click="shareOpen(doc)"
                        >
                            <img :src="icon('share')" alt="" /> Share
                        </button>
                        <button
                            v-else
                            class="ds-outline"
                            @click="toggleFavourite(doc)"
                        >
                            <img :src="icon('check')" alt="" />
                            {{ doc.favourite ? 'Saved' : 'Save' }}
                        </button>
                        <div class="ds-menu-wrapper">
                            <button
                                class="ds-menu-trigger"
                                title="More actions"
                                :aria-expanded="menuId === doc.id"
                                @click="
                                    menuId = menuId === doc.id ? null : doc.id
                                "
                            >
                                <img :src="icon('more')" alt="" />
                            </button>
                            <div v-if="menuId === doc.id" class="ds-menu">
                                <button @click="showHistory(doc)">
                                    Version history
                                </button>
                                <button @click="toggleFavourite(doc)">
                                    {{
                                        doc.favourite
                                            ? 'Remove favourite'
                                            : 'Add to favourites'
                                    }}
                                </button>
                                <button
                                    v-if="doc.mine"
                                    class="ds-danger"
                                    @click="remove(doc)"
                                >
                                    Move to Recycle Bin
                                </button>
                            </div>
                        </div>
                    </div>
                    <div v-else class="ds-file-actions">
                        <button
                            class="ds-primary ds-restore"
                            @click="restore(doc)"
                        >
                            <img :src="icon('restore')" alt="" /> Restore
                        </button>
                    </div>
                </article>
            </div>
            <div v-else class="ds-empty">
                <img src="/images/empty-states/no-documents.png" alt="" />
                <h3>No documents to display</h3>
                <p>
                    Try a different search or filter, or upload your first
                    document.
                </p>
                <button class="ds-primary" @click="uploadOpen = true">
                    Upload Document
                </button>
            </div>
            <div class="ds-pagination">
                <span
                    >Showing {{ documents.from ?? 0 }} to
                    {{ documents.to ?? 0 }} of
                    {{ documents.total }} documents</span
                >
                <div>
                    <Link
                        v-if="documents.prev_page_url"
                        :href="documents.prev_page_url"
                        preserve-scroll
                        >‹</Link
                    ><span v-else>‹</span>
                    <Link
                        v-for="number in Math.min(documents.last_page, 7)"
                        :key="number"
                        :href="`/dashboard?tab=${tab}&type=${type}&category=${category}&search=${encodeURIComponent(search)}&sort=${sort}&view=${view}&page=${number}`"
                        :class="{ selected: number === documents.current_page }"
                        preserve-scroll
                        >{{ number }}</Link
                    >
                    <Link
                        v-if="documents.next_page_url"
                        :href="documents.next_page_url"
                        preserve-scroll
                        >›</Link
                    ><span v-else>›</span>
                </div>
            </div>
        </section>

        <div
            v-if="uploadOpen"
            class="ds-modal-backdrop"
            @click.self="closeModals"
        >
            <section
                class="ds-modal"
                role="dialog"
                aria-modal="true"
                aria-label="Upload documents"
            >
                <header>
                    <h2>Upload Documents</h2>
                    <button @click="closeModals" aria-label="Close">×</button>
                </header>
                <p>
                    Files are stored privately and are accessible only to
                    authorised users.
                </p>
                <form @submit.prevent="upload">
                    <label class="ds-label" for="ds-upload-files"
                        >Choose Word or Excel files (up to 10)</label
                    >
                    <input
                        id="ds-upload-files"
                        type="file"
                        multiple
                        accept=".docx,.xlsx"
                        required
                        @change="setFiles"
                    />
                    <label class="ds-label" for="ds-upload-category"
                        >Category</label
                    >
                    <select
                        id="ds-upload-category"
                        v-model="uploadForm.category_id"
                    >
                        <option value="">Uncategorised</option>
                        <option
                            v-for="c in categories"
                            :key="c.id"
                            :value="String(c.id)"
                        >
                            {{ c.name }}
                        </option>
                    </select>
                    <div
                        v-for="(error, key) in uploadForm.errors"
                        :key="key"
                        class="ds-error"
                    >
                        {{ error }}
                    </div>
                    <p v-if="modalError" class="ds-error">{{ modalError }}</p>
                    <footer>
                        <button
                            type="button"
                            class="ds-outline"
                            @click="closeModals"
                        >
                            Cancel</button
                        ><button
                            type="submit"
                            class="ds-primary"
                            :disabled="
                                uploadForm.processing ||
                                !uploadForm.files.length
                            "
                        >
                            {{
                                uploadForm.processing
                                    ? 'Uploading...'
                                    : 'Upload files'
                            }}
                        </button>
                    </footer>
                </form>
            </section>
        </div>
        <div
            v-if="shareDocument"
            class="ds-modal-backdrop"
            @click.self="closeModals"
        >
            <section
                class="ds-modal"
                role="dialog"
                aria-modal="true"
                aria-label="Share document"
            >
                <header>
                    <h2>Share Document</h2>
                    <button @click="closeModals" aria-label="Close">×</button>
                </header>
                <p>{{ shareDocument.filename }}</p>
                <form @submit.prevent="saveShare">
                    <label class="ds-label">Choose user</label>
                    <select v-model="shareForm.user_id" required>
                        <option value="" disabled>Select a colleague</option>
                        <option
                            v-for="u in users"
                            :key="u.id"
                            :value="String(u.id)"
                        >
                            {{ u.name }} — {{ u.email }}
                        </option>
                    </select>
                    <label class="ds-label">Permission</label>
                    <select v-model="shareForm.permission">
                        <option value="view">View and download</option>
                        <option value="edit">
                            View, edit and save new versions
                        </option>
                    </select>
                    <p
                        v-for="(error, key) in shareForm.errors"
                        :key="key"
                        class="ds-error"
                    >
                        {{ error }}
                    </p>
                    <footer>
                        <button
                            type="button"
                            class="ds-outline"
                            @click="closeModals"
                        >
                            Cancel</button
                        ><button
                            type="submit"
                            class="ds-primary"
                            :disabled="shareForm.processing"
                        >
                            Save access
                        </button>
                    </footer>
                </form>
                <div
                    v-if="shareDocument.shared_with.length"
                    class="ds-existing-access"
                >
                    <h3>Currently shared with</h3>
                    <div v-for="u in shareDocument.shared_with" :key="u.id">
                        <span
                            >{{ u.name }}
                            <small>({{ u.permission }})</small></span
                        ><button @click="revokeShare(u.id)">Remove</button>
                    </div>
                </div>
            </section>
        </div>
        <div
            v-if="detailsDocument"
            class="ds-modal-backdrop"
            @click.self="closeModals"
        >
            <section
                class="ds-modal"
                role="dialog"
                aria-modal="true"
                aria-label="Version history"
            >
                <header>
                    <h2>Version History</h2>
                    <button @click="closeModals" aria-label="Close">×</button>
                </header>
                <p>{{ detailsDocument.filename }}</p>
                <p v-if="modalError" class="ds-error">{{ modalError }}</p>
                <div class="ds-history">
                    <div
                        v-for="v in history"
                        :key="v.version_number"
                        class="ds-history-row"
                    >
                        <span
                            >Version {{ v.version_number }}
                            <small
                                >{{ v.author ?? 'Unknown user' }} ·
                                {{ dateText(v.created_at) }}</small
                            ></span
                        >
                        <a
                            :href="`/documents/${detailsDocument.id}/versions/${v.version_number}/download`"
                            >Download</a
                        >
                    </div>
                </div>
                <form
                    v-if="detailsDocument.can_edit"
                    @submit.prevent="saveRevision"
                >
                    <h3>Save an edited file as a new version</h3>
                    <p>
                        Open the copy in Word or Excel, then upload the saved
                        file here. Automatic Office save-back requires the
                        separate desktop connector.
                    </p>
                    <input
                        type="file"
                        :accept="`.${detailsDocument.extension}`"
                        required
                        @change="setRevisionFile"
                    />
                    <input
                        v-model="revisionForm.change_summary"
                        placeholder="Change summary (optional)"
                        maxlength="500"
                    />
                    <p
                        v-for="(error, key) in revisionForm.errors"
                        :key="key"
                        class="ds-error"
                    >
                        {{ error }}
                    </p>
                    <footer>
                        <button
                            type="button"
                            class="ds-outline"
                            @click="closeModals"
                        >
                            Cancel</button
                        ><button
                            type="submit"
                            class="ds-primary"
                            :disabled="
                                revisionForm.processing || !revisionForm.file
                            "
                        >
                            Save new version
                        </button>
                    </footer>
                </form>
            </section>
        </div>
    </DocumentShell>
</template>

<style scoped>
/* One high-contrast editor choice in the toolbar instead of one on each card. */
.ds-editor-setting {
    display: inline-flex;
    align-items: center;
    gap: 9px;
    margin-left: auto;
    padding: 5px 7px 5px 12px;
    border: 1px solid #c8d8eb;
    border-radius: 8px;
    background: #f0f6ff;
    color: #18395c;
    white-space: nowrap;
    font-size: 12px;
    font-weight: 700;
}
.ds-editor-setting svg {
    flex-shrink: 0;
    color: #1266bf;
}
.ds-documents-filter .ds-editor-setting select {
    width: 183px;
    max-width: 100%;
    height: 34px;
    border: 1px solid #9fb8d6;
    border-radius: 6px;
    background: #fff;
    color: #172f50;
    padding: 0 7px;
    font-size: 12px;
    font-weight: 650;
    cursor: pointer;
}
.ds-editor-setting select:focus-visible {
    outline: 3px solid #1769c5;
    outline-offset: 2px;
}
@media (max-width: 760px) {
    .ds-editor-setting {
        flex-wrap: wrap;
        white-space: normal;
    }
    .ds-editor-setting > span {
        display: none;
    }
}
@media (max-width: 520px) {
    .ds-editor-setting {
        margin-left: 0;
        width: 100%;
    }
    .ds-documents-filter .ds-editor-setting select {
        flex: 1;
        width: auto;
    }
}
</style>
