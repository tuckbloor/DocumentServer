<script setup lang="ts">
import { ref } from 'vue';

type DesktopEditor = 'libreoffice' | 'microsoft';

type OpenResult = {
    open_uri: string;
    mode: 'edit' | 'view';
    session_id: number;
};

const props = withDefaults(
    defineProps<{
        documentId: number;
        filename: string;
        canEdit?: boolean;
        buttonClass?: string;
        editor?: DesktopEditor;
    }>(),
    {
        canEdit: false,
        buttonClass: 'ds-open',
        editor: 'libreoffice',
    },
);

const busy = ref(false);
const error = ref('');

function csrf(): string {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

function nativeLink(officeUri: string, editor: DesktopEditor): string {
    const match = /^ms-(word|excel):(ofe|ofv)\|u\|(https?:\/\/[^\s]+)$/i.exec(
        officeUri,
    );

    if (!match) {
        throw new Error('Invalid desktop editing link.');
    }

    const application = match[1].toLowerCase();
    const mode = match[2].toLowerCase();
    const url = new URL(match[3]);

    if (
        !/^\/standalone-dav\/[A-Za-z0-9_-]{43}\/[^/]+\.(docx|xlsx)$/i.test(
            url.pathname,
        ) ||
        url.search ||
        url.hash ||
        url.username ||
        url.password
    ) {
        throw new Error('Invalid document address.');
    }

    if (
        url.protocol !== 'https:' &&
        !['localhost', '127.0.0.1'].includes(url.hostname)
    ) {
        throw new Error('HTTPS is required outside localhost.');
    }

    const extension = url.pathname.split('.').pop()?.toLowerCase();
    if (
        (application === 'word' && extension !== 'docx') ||
        (application === 'excel' && extension !== 'xlsx')
    ) {
        throw new Error('Document and application do not match.');
    }

    if (editor === 'microsoft') {
        return `ms-${application}:${mode}|u|${url.href}`;
    }

    // Uses LibreOffice's protocol handler, if registered on the user's computer.
    return `vnd.libreoffice.command:${mode}|u|${url.href}`;
}

async function open(): Promise<void> {
    if (busy.value) return;
    busy.value = true;
    error.value = '';

    try {
        const response = await fetch(
            `/documents/${props.documentId}/standalone/open`,
            {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-XSRF-TOKEN': csrf(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
            },
        );

        if (response.status === 429)
            throw new Error(
                'Too many attempts. Wait one minute and open again.',
            );
        if (response.status === 423)
            throw new Error(
                'This document is currently locked by another user.',
            );
        if (response.status === 403)
            throw new Error('You do not have access to this document.');
        if (response.status === 401)
            throw new Error('Your session has expired. Please sign in again.');
        if (!response.ok)
            throw new Error(
                `Unable to open document (HTTP ${response.status}).`,
            );

        const result = (await response.json()) as OpenResult;
        window.location.assign(nativeLink(result.open_uri, props.editor));
    } catch (e) {
        error.value =
            e instanceof Error ? e.message : 'Could not launch the document.';
    } finally {
        busy.value = false;
    }
}
</script>

<template>
    <span class="ds-open-action">
        <button
            type="button"
            :class="buttonClass"
            :disabled="busy"
            :title="filename"
            @click="open"
        >
            <img src="/images/actions/open.png" alt="" />
            {{ busy ? 'Opening...' : 'Open' }}
        </button>
        <small v-if="error" role="alert" class="ds-open-error">{{
            error
        }}</small>
    </span>
</template>

<style scoped>
.ds-open-action {
    position: relative;
    display: flex;
    flex: 1;
    min-width: 0;
}
.ds-open-action > button {
    width: 100%;
}
.ds-open-error {
    position: absolute;
    top: calc(100% + 5px);
    left: 0;
    z-index: 10;
    width: max-content;
    max-width: 270px;
    padding: 7px 9px;
    border: 1px solid #f4afb4;
    border-radius: 6px;
    background: #fff7f7;
    color: #a10d22;
    font-size: 11px;
    line-height: 1.4;
    box-shadow: 0 4px 10px #152f4520;
}
</style>
