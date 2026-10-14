<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import '../../../css/document-server.css';

const props = withDefaults(defineProps<{
    active?: string;
    viewer?: { name: string; avatar: string; role: string };
    unread?: number;
    search?: string;
}>(), { active: 'all', unread: 0, search: '' });
const emit = defineEmits<{ 'update:search': [value: string]; 'search': [] }>();
const page = usePage();
const showAccount = ref(false);
const nav = [
    { label: 'Dashboard', slug: 'all', icon: 'dashboard', href: '/dashboard' },
    { label: 'My Documents', slug: 'mine', icon: 'my-documents', href: '/dashboard?tab=mine' },
    { label: 'Recent', slug: 'recent', icon: 'recent', href: '/dashboard?tab=recent' },
    { label: 'Shared with me', slug: 'shared', icon: 'shared-with-me', href: '/dashboard?tab=shared' },
    { label: 'Favourites', slug: 'favourites', icon: 'favourites', href: '/dashboard?tab=favourites' },
    { label: 'Categories', slug: 'categories', icon: 'categories', href: '/document-server/categories' },
    { label: 'Recycle Bin', slug: 'trash', icon: 'recycle-bin', href: '/dashboard?tab=trash' },
];
const adminNav = [
    { label: 'Users', slug: 'users', icon: 'users', href: '/document-server/users' },
    { label: 'Audit Log', slug: 'activity', icon: 'audit-log', href: '/document-server/activity' },
    { label: 'Settings', slug: 'settings', icon: 'settings', href: '/settings/profile' },
];
const user = computed(() => props.viewer ?? {
    name: String((page.props.auth as any)?.user?.name ?? 'User'),
    avatar: '/images/users/avatar-default.png',
    role: 'Member',
});
const firstName = computed(() => user.value.name.split(' ')[0]);
function logOut() { router.post('/logout'); }
</script>

<template>
    <div class="ds-shell">
        <aside class="ds-sidebar">
            <div class="ds-brand">
                <img src="/images/brand/document-server-logo.png" alt="Document Server" />
                <div><strong>Document Server</strong><small>Company Documents</small></div>
            </div>
            <nav class="ds-sidebar-nav" aria-label="Document navigation">
                <Link v-for="item in nav" :key="item.slug" :href="item.href" class="ds-nav-link" :class="{ selected: active === item.slug }">
                    <img :src="`/images/navigation/${item.icon}.png`" alt="" />
                    <span>{{ item.label }}</span>
                    <span v-if="item.slug === 'shared' && unread" class="ds-count">{{ unread }}</span>
                </Link>
                <p class="ds-nav-group">ADMIN</p>
                <Link v-for="item in adminNav.filter(n => n.slug === 'settings' || user.role === 'Administrator')" :key="item.slug" :href="item.href" class="ds-nav-link" :class="{ selected: active === item.slug }">
                    <img :src="`/images/navigation/${item.icon}.png`" alt="" />
                    <span>{{ item.label }}</span>
                </Link>
            </nav>
        </aside>

        <div class="ds-body">
            <header class="ds-header">
                <form v-if="active !== 'users' && active !== 'activity'" class="ds-header-search" @submit.prevent="emit('search')">
                    <img src="/images/actions/search.png" alt="" />
                    <input :value="search" type="search" placeholder="Search documents..." aria-label="Search documents"
                           @input="emit('update:search', ($event.target as HTMLInputElement).value)" />
                </form>
                <div v-else class="ds-page-location">{{ active === 'users' ? 'User directory' : 'Activity audit log' }}</div>
                <div class="ds-header-right">
                    <Link href="/dashboard?tab=shared" class="ds-notification" aria-label="Shared documents and notifications">
                        <img src="/images/actions/bell.png" alt="" />
                        <span v-if="unread" class="ds-notification-count">{{ unread }}</span>
                    </Link>
                    <div class="ds-account-area">
                        <button type="button" class="ds-account" @click="showAccount = !showAccount" aria-label="Open account menu" :aria-expanded="showAccount">
                            <img :src="user.avatar" alt="Profile avatar" class="ds-avatar" />
                            <span><strong>{{ firstName }}</strong><small>{{ user.role }}</small></span>
                            <img src="/images/actions/chevron-down.png" alt="" class="ds-account-arrow" />
                        </button>
                        <div v-if="showAccount" class="ds-account-menu">
                            <Link href="/settings/profile">Edit profile</Link>
                            <button @click="logOut">Log out</button>
                        </div>
                    </div>
                </div>
            </header>
            <main class="ds-main"><slot /></main>
        </div>
    </div>
</template>
