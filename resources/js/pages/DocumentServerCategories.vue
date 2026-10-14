<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import DocumentShell from '../components/document-server/DocumentShell.vue';
import '../../css/document-server-categories.css';

type Category = {
    id: number;
    name: string;
    slug: string;
    icon: string | null;
    colour: string | null;
    sort_order: number;
    document_count: number;
    word_count: number;
    excel_count: number;
};
type DocumentEntry = {
    id: number;
    title: string;
    filename: string;
    extension: string;
    size_bytes: number;
    version: number;
    updated_at: string;
    owner: string;
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
    isAdmin: boolean;
    categories: Category[];
    selectedCategory: Category | null;
    documents: PageData | null;
    uncategorisedCount: number;
    filters: { search: string; type: string; sort: string };
}>();

const page = usePage();
const search = ref(props.filters.search || '');
const type = ref(props.filters.type || '');
const sort = ref(props.filters.sort || 'latest');
const modalOpen = ref(false);
const editingId = ref<number | null>(null);
const form = useForm({ name: '' });
const actionError = ref('');
const documentPanel = ref<HTMLElement | null>(null);
let searchTimer: ReturnType<typeof setTimeout> | undefined;

const totalVisible = computed(
    () =>
        props.categories.reduce(
            (sum, category) => sum + category.document_count,
            0,
        ) + props.uncategorisedCount,
);
const categoryList = computed(() =>
    props.selectedCategory
        ? props.categories
        : props.categories.filter((category) =>
              category.name
                  .toLowerCase()
                  .includes(search.value.trim().toLowerCase()),
          ),
);
const flash = computed(
    () => (page.props.flash as { success?: string } | undefined)?.success || '',
);

watch(
    () => props.filters,
    (filters) => {
        search.value = filters.search || '';
        type.value = filters.type || '';
        sort.value = filters.sort || 'latest';
    },
    { deep: true },
);
onBeforeUnmount(() => {
    if (searchTimer) clearTimeout(searchTimer);
});

function categoryUrl(
    categoryId: number | null,
    extra: Record<string, string> = {},
): string {
    const params = new URLSearchParams();
    if (categoryId !== null) params.set('category', String(categoryId));
    const selectedSearch = extra.search ?? search.value;
    const selectedType = extra.type ?? type.value;
    const selectedSort = extra.sort ?? sort.value;
    if (categoryId !== null && selectedSearch.trim())
        params.set('search', selectedSearch.trim());
    if (categoryId !== null && selectedType) params.set('type', selectedType);
    if (categoryId !== null && selectedSort !== 'latest')
        params.set('sort', selectedSort);
    return `/document-server/categories${params.toString() ? `?${params}` : ''}`;
}
function changeCategory(categoryId: number) {
    if (searchTimer) clearTimeout(searchTimer);
    search.value = '';
    type.value = '';
    sort.value = 'latest';
    router.get(
        categoryUrl(categoryId, { search: '', type: '', sort: 'latest' }),
        {},
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: async () => {
                await nextTick(); // Wait until the selected document panel is rendered.
                documentPanel.value?.scrollIntoView({
                    behavior: window.matchMedia(
                        '(prefers-reduced-motion: reduce)',
                    ).matches
                        ? 'auto'
                        : 'smooth',
                    block: 'start',
                });
            },
        },
    );
}
function applyFilters() {
    if (searchTimer) clearTimeout(searchTimer);
    if (!props.selectedCategory) return;
    router.get(
        categoryUrl(props.selectedCategory.id),
        {},
        { preserveScroll: true, preserveState: true, replace: true },
    );
}
function searchChanged(value: string) {
    search.value = value;
    if (searchTimer) clearTimeout(searchTimer);
    if (props.selectedCategory) searchTimer = setTimeout(applyFilters, 350);
}
function openCreate() {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    actionError.value = '';
    modalOpen.value = true;
}
function openRename() {
    if (!props.selectedCategory) return;
    editingId.value = props.selectedCategory.id;
    form.name = props.selectedCategory.name;
    form.clearErrors();
    actionError.value = '';
    modalOpen.value = true;
}
function saveCategory() {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            modalOpen.value = false;
            form.reset();
        },
    };
    if (editingId.value === null)
        form.post('/document-server/categories', options);
    else form.patch(`/document-server/categories/${editingId.value}`, options);
}
function deleteCategory() {
    if (!props.selectedCategory || !props.isAdmin) return;
    if (props.selectedCategory.document_count > 0) {
        actionError.value =
            'Move all documents out of this category before deleting it.';
        return;
    }
    if (
        !window.confirm(
            `Delete the empty category "${props.selectedCategory.name}"?`,
        )
    )
        return;
    actionError.value = '';
    router.delete(`/document-server/categories/${props.selectedCategory.id}`, {
        onError: (errors) => {
            actionError.value = String(
                errors.category || 'Unable to delete this category.',
            );
        },
    });
}
function friendlyDate(value: string): string {
    return new Date(value).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}
function fileSize(value: number): string {
    return value < 1024 * 1024
        ? `${Math.max(1, Math.round(value / 1024))} KB`
        : `${(value / (1024 * 1024)).toFixed(1)} MB`;
}
function docImage(extension: string): string {
    return `/images/documents/${extension === 'docx' ? 'word' : extension === 'xlsx' ? 'excel' : 'generic'}.png`;
}
</script>

<template>
    <Head title="Categories — Document Server" />
    <DocumentShell
        active="categories"
        :viewer="viewer"
        :search="search"
        @update:search="searchChanged"
        @search="applyFilters"
    >
        <div class="ds-heading dsc-heading">
            <div>
                <div class="dsc-breadcrumb">
                    <Link href="/dashboard">Document Server</Link><span>/</span
                    ><span>Categories</span>
                </div>
                <h1>Document Categories</h1>
                <p>Organise and explore your company documents by subject.</p>
            </div>
            <button
                v-if="isAdmin"
                class="ds-primary dsc-primary"
                type="button"
                @click="openCreate"
            >
                <img src="/images/actions/add.png" alt="" /> New Category
            </button>
        </div>

        <p v-if="flash" class="ds-flash" role="status">{{ flash }}</p>
        <p v-if="actionError" class="dsc-error" role="alert">
            {{ actionError }}
        </p>

        <div class="dsc-summary">
            <div class="dsc-summary-icon">
                <img src="/images/navigation/categories.png" alt="" />
            </div>
            <div>
                <strong>{{ categories.length }}</strong
                ><span>Categories</span>
            </div>
            <div class="dsc-summary-divider"></div>
            <div>
                <strong>{{ totalVisible }}</strong
                ><span>Documents you can access</span>
            </div>
            <div class="dsc-summary-divider"></div>
            <div>
                <strong>{{ uncategorisedCount }}</strong
                ><span>Uncategorised</span>
            </div>
            <div class="dsc-summary-detail">
                Counts respect your document permissions
            </div>
        </div>

        <div class="dsc-section-heading">
            <div>
                <h2>Browse categories</h2>
                <p>Select a category to view its Word and Excel files.</p>
            </div>
            <Link
                v-if="selectedCategory"
                href="/document-server/categories"
                class="dsc-link"
                >Show all categories</Link
            >
        </div>

        <div v-if="categoryList.length" class="dsc-category-grid">
            <button
                v-for="category in categoryList"
                :key="category.id"
                type="button"
                class="dsc-category-card"
                :class="{
                    'dsc-category-selected':
                        selectedCategory?.id === category.id,
                }"
                :aria-pressed="selectedCategory?.id === category.id"
                @click="changeCategory(category.id)"
            >
                <span class="dsc-folder-icon"
                    ><img src="/images/navigation/categories.png" alt=""
                /></span>
                <span class="dsc-category-main">
                    <strong>{{ category.name }}</strong>
                    <small
                        >{{ category.document_count }}
                        {{
                            category.document_count === 1
                                ? 'document'
                                : 'documents'
                        }}</small
                    >
                </span>
                <span class="dsc-mini-counts">
                    <span
                        ><img src="/images/documents/word.png" alt="Word" />{{
                            category.word_count
                        }}</span
                    >
                    <span
                        ><img src="/images/documents/excel.png" alt="Excel" />{{
                            category.excel_count
                        }}</span
                    >
                </span>
                <span class="dsc-arrow" aria-hidden="true">›</span>
            </button>
        </div>
        <div v-else class="dsc-no-categories">
            <img src="/images/empty-states/no-results.png" alt="" />
            <p>No categories match your search.</p>
        </div>

        <section
            v-if="selectedCategory"
            ref="documentPanel"
            class="dsc-document-panel"
        >
            <div class="dsc-panel-header">
                <div>
                    <span class="dsc-eyebrow">SELECTED CATEGORY</span>
                    <h2>{{ selectedCategory.name }}</h2>
                    <p>
                        {{ selectedCategory.document_count }} documents
                        accessible to you
                    </p>
                </div>
                <div class="dsc-panel-actions">
                    <button
                        v-if="isAdmin"
                        type="button"
                        class="ds-outline"
                        @click="openRename"
                    >
                        Rename
                    </button>
                    <button
                        v-if="isAdmin"
                        type="button"
                        class="ds-outline dsc-danger"
                        @click="deleteCategory"
                    >
                        Delete
                    </button>
                    <Link
                        :href="`/dashboard?category=${selectedCategory.id}`"
                        class="ds-outline"
                        >Open in library</Link
                    >
                </div>
            </div>
            <div class="dsc-filters">
                <label
                    >File type
                    <select v-model="type" @change="applyFilters">
                        <option value="">All files</option>
                        <option value="word">Word</option>
                        <option value="excel">Excel</option>
                    </select>
                </label>
                <label
                    >Sort by
                    <select v-model="sort" @change="applyFilters">
                        <option value="latest">Recently updated</option>
                        <option value="name">Name A–Z</option>
                        <option value="oldest">Oldest updated</option>
                    </select>
                </label>
            </div>
            <div v-if="documents?.data.length" class="dsc-files">
                <div
                    v-for="doc in documents.data"
                    :key="doc.id"
                    class="dsc-file-row"
                >
                    <img
                        :src="docImage(doc.extension)"
                        :alt="doc.extension.toUpperCase()"
                        class="dsc-file-icon"
                    />
                    <div class="dsc-file-info">
                        <strong>{{ doc.title }}</strong>
                        <small
                            >{{ doc.owner }} ·
                            {{ friendlyDate(doc.updated_at) }} ·
                            {{ fileSize(doc.size_bytes) }} · Version
                            {{ doc.version }}</small
                        >
                    </div>
                    <span class="dsc-file-label">{{
                        doc.mine ? 'Owned by you' : 'Shared with you'
                    }}</span>
                    <a
                        :href="`/documents/${doc.id}/download`"
                        class="ds-outline dsc-file-open"
                    >
                        <img src="/images/actions/download.png" alt="" /> Open
                    </a>
                </div>
            </div>
            <div v-else class="dsc-no-files">
                <img src="/images/empty-states/no-documents.png" alt="" />
                <strong>No documents found</strong>
                <p>There are no accessible files matching these filters.</p>
            </div>
            <div
                v-if="documents && documents.last_page > 1"
                class="dsc-pagebar"
            >
                <span
                    >Showing {{ documents.from }}–{{ documents.to }} of
                    {{ documents.total }}</span
                >
                <div>
                    <Link
                        v-if="documents.prev_page_url"
                        :href="documents.prev_page_url"
                        >← Previous</Link
                    >
                    <span
                        >Page {{ documents.current_page }} of
                        {{ documents.last_page }}</span
                    >
                    <Link
                        v-if="documents.next_page_url"
                        :href="documents.next_page_url"
                        >Next →</Link
                    >
                </div>
            </div>
        </section>

        <div
            v-if="modalOpen"
            class="ds-modal-backdrop"
            @click.self="modalOpen = false"
        >
            <div
                class="ds-modal dsc-modal"
                role="dialog"
                aria-modal="true"
                aria-labelledby="dsc-modal-title"
            >
                <header>
                    <h2 id="dsc-modal-title">
                        {{ editingId ? 'Rename Category' : 'New Category' }}
                    </h2>
                    <button
                        type="button"
                        aria-label="Close"
                        @click="modalOpen = false"
                    >
                        ×
                    </button>
                </header>
                <p>
                    Categories help your team quickly locate the right
                    documents.
                </p>
                <form @submit.prevent="saveCategory">
                    <label class="ds-label" for="dsc-category-name"
                        >Category name</label
                    >
                    <input
                        id="dsc-category-name"
                        v-model="form.name"
                        type="text"
                        autocomplete="off"
                        maxlength="100"
                        required
                        placeholder="e.g. IT Compliance"
                        autofocus
                    />
                    <p v-if="form.errors.name" class="ds-error">
                        {{ form.errors.name }}
                    </p>
                    <footer>
                        <button
                            type="button"
                            class="ds-outline"
                            @click="modalOpen = false"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="ds-primary dsc-primary"
                            :disabled="form.processing"
                        >
                            {{ form.processing ? 'Saving…' : 'Save Category' }}
                        </button>
                    </footer>
                </form>
            </div>
        </div>
    </DocumentShell>
</template>
