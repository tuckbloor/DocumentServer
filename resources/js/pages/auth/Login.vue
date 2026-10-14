<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

withDefaults(
    defineProps<{
        status?: string;
        canResetPassword?: boolean;
        canRegister?: boolean;
    }>(),
    {
        canResetPassword: true,
        canRegister: true,
    },
);

const showPassword = ref(false);
const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit(): void {
    if (form.processing) return;

    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <Head title="Sign in | Document Server" />

    <div class="ds-auth-page">
        <header class="ds-auth-header">
            <Link
                href="/"
                class="ds-brand"
                aria-label="Document Server homepage"
            >
                <span class="ds-brand-icon" aria-hidden="true">
                    <svg viewBox="0 0 32 32" width="26" height="26" fill="none">
                        <path
                            d="M8 3.75h11.5L26 10.3v17.2c0 .95-.8 1.75-1.75 1.75H8c-.97 0-1.75-.8-1.75-1.75v-22c0-.97.78-1.75 1.75-1.75Z"
                            fill="#EAF3FF"
                        />
                        <path d="M19.3 4v6.7H26" fill="#8FC2FF" />
                        <path
                            d="M11 16h10M11 20h10M11 24h7"
                            stroke="#1263D5"
                            stroke-width="2.1"
                            stroke-linecap="round"
                        />
                    </svg>
                </span>
                <span>Document <strong>Server</strong></span>
            </Link>
            <Link href="/" class="ds-home-link">
                <span aria-hidden="true">←</span> Dashboard
            </Link>
        </header>

        <main class="ds-auth-main">
            <section
                class="ds-signin-section"
                aria-labelledby="sign-in-heading"
            >
                <div class="ds-form-wrap">
                    <div class="ds-kicker">
                        <span class="ds-kicker-line"></span> YOUR DOCUMENT
                        WORKSPACE
                    </div>
                    <h1 id="sign-in-heading">
                        Welcome back<span class="ds-period">.</span>
                    </h1>
                    <p class="ds-intro">
                        Sign in to access your documents, shared files and
                        version history.
                    </p>

                    <div class="ds-signin-card">
                        <div class="ds-card-heading">
                            <div class="ds-card-icon" aria-hidden="true">
                                <svg
                                    viewBox="0 0 24 24"
                                    width="21"
                                    height="21"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <rect
                                        x="5"
                                        y="10"
                                        width="14"
                                        height="11"
                                        rx="2"
                                    />
                                    <path d="M8 10V7a4 4 0 0 1 8 0v3" />
                                </svg>
                            </div>
                            <div>
                                <h2>Sign in to Document Server</h2>
                                <p>Use your existing account credentials.</p>
                            </div>
                        </div>

                        <p v-if="status" class="ds-status" role="status">
                            {{ status }}
                        </p>

                        <form class="ds-form" @submit.prevent="submit">
                            <div class="ds-field">
                                <label for="ds-email">Email address</label>
                                <input
                                    id="ds-email"
                                    v-model="form.email"
                                    type="email"
                                    name="email"
                                    autocomplete="username"
                                    placeholder="name@company.com"
                                    :aria-invalid="Boolean(form.errors.email)"
                                    :aria-describedby="
                                        form.errors.email
                                            ? 'ds-email-error'
                                            : undefined
                                    "
                                    autofocus
                                    required
                                />
                                <p
                                    v-if="form.errors.email"
                                    id="ds-email-error"
                                    class="ds-field-error"
                                    role="alert"
                                >
                                    {{ form.errors.email }}
                                </p>
                            </div>

                            <div class="ds-field">
                                <div class="ds-label-row">
                                    <label for="ds-password">Password</label>
                                    <Link
                                        v-if="canResetPassword"
                                        href="/forgot-password"
                                        class="ds-small-link"
                                        >Forgot password?</Link
                                    >
                                </div>
                                <div class="ds-password-field">
                                    <input
                                        id="ds-password"
                                        v-model="form.password"
                                        :type="
                                            showPassword ? 'text' : 'password'
                                        "
                                        name="password"
                                        autocomplete="current-password"
                                        placeholder="Enter your password"
                                        :aria-invalid="
                                            Boolean(form.errors.password)
                                        "
                                        :aria-describedby="
                                            form.errors.password
                                                ? 'ds-password-error'
                                                : undefined
                                        "
                                        required
                                    />
                                    <button
                                        type="button"
                                        class="ds-password-toggle"
                                        :aria-pressed="showPassword"
                                        :aria-label="
                                            showPassword
                                                ? 'Hide password'
                                                : 'Show password'
                                        "
                                        @click="showPassword = !showPassword"
                                    >
                                        {{ showPassword ? 'Hide' : 'Show' }}
                                    </button>
                                </div>
                                <p
                                    v-if="form.errors.password"
                                    id="ds-password-error"
                                    class="ds-field-error"
                                    role="alert"
                                >
                                    {{ form.errors.password }}
                                </p>
                            </div>

                            <label class="ds-remember">
                                <input
                                    v-model="form.remember"
                                    type="checkbox"
                                    name="remember"
                                />
                                <span>Remember me on this device</span>
                            </label>

                            <button
                                class="ds-submit"
                                type="submit"
                                :disabled="form.processing"
                            >
                                <span>{{
                                    form.processing
                                        ? 'Signing you in…'
                                        : 'Sign in to workspace'
                                }}</span>
                                <svg
                                    v-if="!form.processing"
                                    aria-hidden="true"
                                    viewBox="0 0 24 24"
                                    width="19"
                                    height="19"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path d="M5 12h14m-6-6 6 6-6 6" />
                                </svg>
                                <span
                                    v-else
                                    class="ds-spinner"
                                    aria-hidden="true"
                                ></span>
                            </button>
                        </form>

                        <div v-if="canRegister" class="ds-register-row">
                            Don't have an account?
                            <Link href="/register"
                                >Request access
                                <span aria-hidden="true">→</span></Link
                            >
                        </div>
                    </div>

                    <p class="ds-help-copy">
                        Your documents are available only to authorised users.
                    </p>
                </div>
            </section>

            <aside
                class="ds-showcase"
                aria-label="Document Server workspace preview"
            >
                <div class="ds-showcase-inner">
                    <div class="ds-showcase-topline">
                        <span class="ds-topline-dot"></span> YOUR FILES,
                        ORGANISED
                    </div>
                    <h2>
                        All your documents.<br /><span
                            >One secure workspace.</span
                        >
                    </h2>
                    <p class="ds-showcase-copy">
                        Access Word and Excel files, collaborate with colleagues
                        and keep track of document revisions.
                    </p>

                    <div class="ds-preview" aria-hidden="true">
                        <div class="ds-preview-rail">
                            <div class="ds-rail-logo">DS</div>
                            <div class="ds-rail-link ds-rail-selected">
                                <span class="ds-rail-square"></span> All
                                documents
                            </div>
                            <div class="ds-rail-link">
                                <span
                                    class="ds-rail-square ds-rail-outline"
                                ></span>
                                My documents
                            </div>
                            <div class="ds-rail-link">
                                <span
                                    class="ds-rail-square ds-rail-outline"
                                ></span>
                                Shared with me
                            </div>
                            <div class="ds-rail-separator"></div>
                            <div class="ds-rail-link">
                                <span
                                    class="ds-rail-square ds-rail-outline"
                                ></span>
                                Recent files
                            </div>
                            <div class="ds-rail-link">
                                <span
                                    class="ds-rail-square ds-rail-outline"
                                ></span>
                                Favourites
                            </div>
                        </div>
                        <div class="ds-preview-content">
                            <div class="ds-preview-toolbar">
                                <div class="ds-preview-search">
                                    <span class="ds-search-icon">⌕</span> Search
                                    documents...
                                </div>
                                <span class="ds-preview-upload">+ Upload</span>
                            </div>
                            <div class="ds-preview-title-row">
                                <div>
                                    <h3>Document Library</h3>
                                    <p>Manage and access your team's files</p>
                                </div>
                                <span class="ds-count">128 files</span>
                            </div>
                            <div class="ds-preview-filter">
                                <span class="ds-active-filter"
                                    >All documents</span
                                ><span>Word documents</span
                                ><span>Excel documents</span>
                            </div>
                            <div class="ds-file-row">
                                <span class="ds-file-icon ds-word">W</span
                                ><span class="ds-file-info"
                                    ><strong>Backup Policy.docx</strong
                                    ><small
                                        >IT Policies · Updated today</small
                                    ></span
                                ><span class="ds-file-version">v3</span
                                ><span class="ds-file-action">Open →</span>
                            </div>
                            <div class="ds-file-row">
                                <span class="ds-file-icon ds-excel">X</span
                                ><span class="ds-file-info"
                                    ><strong>Network Inventory.xlsx</strong
                                    ><small
                                        >Infrastructure · Updated
                                        yesterday</small
                                    ></span
                                ><span class="ds-file-version">v2</span
                                ><span class="ds-file-action">Open →</span>
                            </div>
                            <div class="ds-file-row">
                                <span class="ds-file-icon ds-word">W</span
                                ><span class="ds-file-info"
                                    ><strong>Acceptable Use Policy.docx</strong
                                    ><small
                                        >IT Policies · Shared with team</small
                                    ></span
                                ><span class="ds-file-version">v5</span
                                ><span class="ds-file-action">Open →</span>
                            </div>
                            <div class="ds-preview-bottom">
                                <span class="ds-bottom-lock">✓</span>
                                Permissions &amp; version history managed in one
                                place
                            </div>
                        </div>
                    </div>

                    <div class="ds-showcase-foot">
                        <span
                            ><span class="ds-foot-icon">✓</span> Controlled
                            access</span
                        >
                        <span
                            ><span class="ds-foot-icon">✓</span> Version
                            history</span
                        >
                        <span
                            ><span class="ds-foot-icon">✓</span> Team
                            sharing</span
                        >
                    </div>
                </div>
            </aside>
        </main>

        <footer class="ds-auth-footer">
            <span>© {{ new Date().getFullYear() }} Document Server</span
            ><span>Document management workspace</span>
        </footer>
    </div>
</template>

<style scoped>
.ds-auth-page {
    --navy: #0e213e;
    --navy-deep: #081b33;
    --ink: #122542;
    --muted: #5f728d;
    --blue: #0966df;
    --line: #dbe4f0;
    min-height: 100vh;
    min-height: 100dvh;
    display: flex;
    flex-direction: column;
    background: #f6f9fd;
    color: var(--ink);
    font-family:
        Inter,
        ui-sans-serif,
        system-ui,
        -apple-system,
        BlinkMacSystemFont,
        'Segoe UI',
        sans-serif;
}
.ds-auth-page *,
.ds-auth-page *::before,
.ds-auth-page *::after {
    box-sizing: border-box;
}
.ds-auth-page a {
    text-decoration: none;
}
.ds-auth-page :is(a, button, input):focus-visible {
    outline: 3px solid #4a94f3;
    outline-offset: 3px;
}
.ds-auth-header {
    background: var(--navy);
    color: #fff;
    min-height: 76px;
    padding: 14px clamp(24px, 5vw, 86px);
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid #263e60;
}
.ds-brand {
    display: inline-flex;
    align-items: center;
    gap: 13px;
    color: #fff;
    font-size: 20px;
    font-weight: 650;
    letter-spacing: -0.035em;
}
.ds-brand strong {
    font-weight: 830;
}
.ds-brand-icon {
    display: grid;
    place-items: center;
    width: 38px;
    height: 42px;
    border-radius: 9px;
    background: #173960;
}
.ds-home-link {
    display: inline-flex;
    align-items: center;
    gap: 11px;
    color: #c5d8f1;
    font-size: 13px;
    font-weight: 600;
}
.ds-home-link:hover {
    color: #fff;
}
.ds-auth-main {
    display: grid;
    grid-template-columns: minmax(0, 57%) minmax(0, 43%);
    flex: 1;
    min-height: 740px;
}
.ds-signin-section {
    padding: clamp(46px, 5vw, 84px) clamp(36px, 4.2vw, 82px) 56px;
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 0;
}
.ds-form-wrap {
    width: 100%;
    max-width: 820px;
    min-width: 0;
}
.ds-kicker {
    display: flex;
    align-items: center;
    gap: 11px;
    color: #1b5bab;
    font-size: 11px;
    letter-spacing: 0.17em;
    font-weight: 850;
}
.ds-kicker-line {
    width: 23px;
    height: 3px;
    border-radius: 5px;
    background: var(--blue);
}
.ds-form-wrap h1 {
    font-size: clamp(46px, 4vw, 64px);
    letter-spacing: -0.06em;
    font-weight: 820;
    margin: 18px 0 12px;
    line-height: 1.11;
}
.ds-period {
    color: var(--blue);
}
.ds-intro {
    color: var(--muted);
    font-size: 16px;
    line-height: 1.7;
    margin: 0 0 32px;
    max-width: 670px;
}
.ds-signin-card {
    width: 100%;
    background: #fff;
    padding: clamp(32px, 3.1vw, 48px);
    border: 1px solid var(--line);
    border-radius: 18px;
    box-shadow: 0 18px 48px rgba(18, 48, 82, 0.075);
}
.ds-card-heading {
    display: flex;
    align-items: center;
    gap: 14px;
    padding-bottom: 23px;
    border-bottom: 1px solid #e9eff7;
    margin-bottom: 25px;
}
.ds-card-icon {
    background: #eaf3ff;
    color: #1764c8;
    height: 43px;
    width: 43px;
    display: grid;
    place-items: center;
    border: 1px solid #d7e7ff;
    border-radius: 11px;
    flex-shrink: 0;
}
.ds-card-heading h2 {
    font-size: 20px;
    font-weight: 770;
    letter-spacing: -0.025em;
    margin: 0 0 5px;
}
.ds-card-heading p {
    color: var(--muted);
    font-size: 14px;
    margin: 0;
}
.ds-form {
    display: flex;
    flex-direction: column;
    gap: 26px;
}
.ds-field label,
.ds-label-row label {
    display: block;
    color: #223856;
    font-size: 14px;
    font-weight: 730;
    margin-bottom: 10px;
}
.ds-field input:not([type='checkbox']) {
    height: 58px;
    width: 100%;
    border: 1px solid #c9d7e6;
    border-radius: 10px;
    background: #fff;
    color: #142744;
    font-size: 16px;
    padding: 0 18px;
    outline: none;
    transition:
        border-color 0.15s,
        box-shadow 0.15s;
}
.ds-field input:not([type='checkbox']):focus {
    border-color: #196cdb;
    box-shadow: 0 0 0 3px rgba(25, 108, 219, 0.11);
}
.ds-field input::placeholder {
    color: #8797ac;
}
.ds-field input[aria-invalid='true'] {
    border-color: #bb3030;
}
.ds-field-error {
    color: #aa2727;
    font-size: 12px;
    margin: 7px 0 0;
    line-height: 1.5;
}
.ds-label-row {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    gap: 10px;
}
.ds-small-link,
.ds-register-row a {
    color: #095bc4;
    font-size: 12px;
    font-weight: 730;
}
.ds-small-link:hover,
.ds-register-row a:hover {
    text-decoration: underline;
}
.ds-password-field {
    position: relative;
}
.ds-password-field input {
    padding-right: 78px !important;
}
.ds-password-toggle {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    right: 10px;
    border: none;
    background: transparent;
    color: #1b5bab;
    font-size: 12px;
    font-weight: 750;
    padding: 8px;
    cursor: pointer;
}
.ds-remember {
    display: inline-flex;
    width: fit-content;
    align-items: center;
    gap: 12px;
    color: #3d516e;
    font-size: 14px;
    cursor: pointer;
}
.ds-remember input {
    appearance: auto;
    accent-color: #1268da;
    width: 16px;
    height: 16px;
    cursor: pointer;
}
.ds-submit {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    min-height: 58px;
    padding: 14px 22px;
    color: #fff;
    background: var(--blue);
    border: 1px solid #095bc2;
    border-radius: 10px;
    font-weight: 760;
    font-size: 16px;
    cursor: pointer;
    box-shadow: 0 5px 12px rgba(12, 91, 196, 0.18);
    transition:
        background 0.15s,
        transform 0.15s;
}
.ds-submit:hover:not(:disabled) {
    background: #0955b6;
    transform: translateY(-1px);
}
.ds-submit:disabled {
    opacity: 0.65;
    cursor: wait;
}
.ds-spinner {
    width: 16px;
    height: 16px;
    border: 2px solid #ffffff80;
    border-top-color: #fff;
    border-radius: 50%;
    animation: ds-spin 0.7s linear infinite;
}
@keyframes ds-spin {
    to {
        transform: rotate(360deg);
    }
}
.ds-register-row {
    margin-top: 24px;
    padding-top: 21px;
    border-top: 1px solid #e9eff7;
    display: flex;
    gap: 7px;
    justify-content: center;
    flex-wrap: wrap;
    color: #667993;
    font-size: 12px;
}
.ds-status {
    color: #165b32;
    border: 1px solid #b9dec3;
    background: #eefaf0;
    border-radius: 8px;
    padding: 11px 13px;
    margin: 0 0 22px;
    font-size: 13px;
}
.ds-help-copy {
    font-size: 12px;
    text-align: center;
    color: #64758b;
    margin-top: 25px;
}
.ds-showcase {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    background:
        radial-gradient(ellipse at 85% 8%, #1c4679 0%, transparent 45%),
        linear-gradient(151deg, #102c50 0%, #071a31 100%);
    color: #fff;
    padding: 52px clamp(28px, 3.2vw, 58px);
    min-width: 0;
}
.ds-showcase::before {
    content: '';
    position: absolute;
    width: 700px;
    height: 700px;
    border: 1px solid rgba(126, 179, 249, 0.11);
    border-radius: 50%;
    right: -390px;
    bottom: -410px;
    box-shadow:
        0 0 0 65px rgba(35, 83, 139, 0.075),
        0 0 0 130px rgba(35, 83, 139, 0.065);
    pointer-events: none;
}
.ds-showcase-inner {
    position: relative;
    z-index: 1;
    width: 100%;
    max-width: 750px;
}
.ds-showcase-topline {
    color: #9fc9ff;
    font-size: 11px;
    font-weight: 820;
    letter-spacing: 0.18em;
    display: flex;
    align-items: center;
    gap: 10px;
}
.ds-topline-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #6db2ff;
    box-shadow: 0 0 0 4px rgba(109, 178, 255, 0.13);
}
.ds-showcase h2 {
    margin: 18px 0 12px;
    font-size: clamp(32px, 2.7vw, 44px);
    font-weight: 800;
    line-height: 1.14;
    letter-spacing: -0.045em;
}
.ds-showcase h2 span {
    color: #86baff;
}
.ds-showcase-copy {
    max-width: 560px;
    font-size: 14px;
    color: #c6d7ea;
    line-height: 1.8;
    margin: 0 0 36px;
}
.ds-preview {
    display: grid;
    grid-template-columns: 124px minmax(0, 1fr);
    min-height: 350px;
    border: 1px solid rgba(193, 215, 249, 0.3);
    border-radius: 14px;
    overflow: hidden;
    background: white;
    color: #122542;
    box-shadow: 0 25px 70px rgba(0, 0, 0, 0.29);
    transform: perspective(1200px) rotateY(-3deg);
    transform-origin: 50% 50%;
}
.ds-preview-rail {
    background: #10243e;
    color: #d5e2f6;
    padding: 18px 10px;
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.ds-rail-logo {
    width: 33px;
    height: 33px;
    background: #2273dd;
    border-radius: 8px;
    display: grid;
    place-items: center;
    font-size: 12px;
    color: #fff;
    font-weight: 850;
    margin: 0 0 19px 8px;
}
.ds-rail-link {
    font-size: 9px;
    font-weight: 580;
    white-space: nowrap;
    padding: 10px 9px;
    display: flex;
    align-items: center;
    gap: 9px;
    border-radius: 5px;
}
.ds-rail-selected {
    background: #1d6bd3;
    color: white;
    font-weight: 750;
}
.ds-rail-square {
    width: 9px;
    height: 9px;
    border-radius: 2px;
    background: #fff;
    flex-shrink: 0;
}
.ds-rail-outline {
    background: transparent;
    border: 1.5px solid #8eb1d9;
}
.ds-rail-separator {
    height: 1px;
    background: #2a3f5b;
    margin: 9px;
}
.ds-preview-content {
    min-width: 0;
    padding: 18px 15px;
}
.ds-preview-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
}
.ds-preview-search {
    border: 1px solid #e0e7f0;
    border-radius: 6px;
    background: #f7f9fc;
    height: 29px;
    padding: 0 11px;
    display: flex;
    align-items: center;
    gap: 8px;
    color: #9aacc0;
    font-size: 10px;
    flex: 1;
    max-width: 235px;
}
.ds-search-icon {
    font-size: 20px;
    line-height: 1;
}
.ds-preview-upload {
    background: #1769d7;
    color: #fff;
    padding: 8px 11px;
    border-radius: 5px;
    font-size: 9px;
    font-weight: 750;
    white-space: nowrap;
}
.ds-preview-title-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-top: 27px;
}
.ds-preview-title-row h3 {
    font-size: 17px;
    margin: 0 0 3px;
    color: #122542;
    letter-spacing: -0.03em;
}
.ds-preview-title-row p {
    font-size: 10px;
    color: #7b8da4;
    margin: 0;
}
.ds-count {
    padding: 6px 8px;
    border-radius: 6px;
    background: #ecf3ff;
    color: #2660a8;
    font-size: 10px;
    font-weight: 750;
    white-space: nowrap;
}
.ds-preview-filter {
    border-bottom: 1px solid #e1e8f3;
    margin: 23px 0 11px;
    display: flex;
    gap: 19px;
    white-space: nowrap;
    overflow: hidden;
}
.ds-preview-filter span {
    color: #8594a9;
    font-size: 9px;
    padding: 0 0 11px;
}
.ds-preview-filter .ds-active-filter {
    color: #175bb8;
    font-weight: 820;
    border-bottom: 2px solid #176bdf;
}
.ds-file-row {
    display: flex;
    align-items: center;
    gap: 11px;
    border: 1px solid #e8edf4;
    border-radius: 7px;
    padding: 10px 9px;
    margin-bottom: 8px;
}
.ds-file-icon {
    width: 27px;
    height: 30px;
    display: grid;
    place-items: center;
    border-radius: 4px;
    color: #fff;
    font-weight: 800;
    font-size: 13px;
    flex-shrink: 0;
}
.ds-word {
    background: #205ec0;
}
.ds-excel {
    background: #177648;
}
.ds-file-info {
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 3px;
    flex: 1;
}
.ds-file-info strong {
    color: #1c304a;
    font-size: 10px;
    text-overflow: ellipsis;
    overflow: hidden;
    white-space: nowrap;
}
.ds-file-info small {
    color: #8a9bb0;
    font-size: 9px;
}
.ds-file-version {
    font-size: 10px;
    color: #72849c;
}
.ds-file-action {
    padding: 7px 9px;
    background: #eaf3ff;
    color: #105cbd;
    font-size: 9px;
    border-radius: 5px;
    font-weight: 800;
    white-space: nowrap;
}
.ds-preview-bottom {
    display: flex;
    align-items: center;
    gap: 7px;
    margin-top: 13px;
    color: #697e99;
    font-size: 9px;
}
.ds-bottom-lock {
    border-radius: 50%;
    background: #d8f5e8;
    color: #167b51;
    width: 17px;
    height: 17px;
    display: grid;
    place-items: center;
    font-weight: 750;
}
.ds-showcase-foot {
    margin-top: 32px;
    display: flex;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
    color: #c3d6eb;
    font-size: 12px;
    font-weight: 550;
}
.ds-foot-icon {
    display: inline-grid;
    place-items: center;
    margin-right: 6px;
    width: 18px;
    height: 18px;
    background: #224f76;
    color: #bde1fa;
    border-radius: 50%;
    font-size: 11px;
}
.ds-auth-footer {
    min-height: 52px;
    background: #fff;
    color: #718198;
    border-top: 1px solid #e2eaf4;
    padding: 14px clamp(24px, 5vw, 86px);
    display: flex;
    justify-content: space-between;
    gap: 12px;
    font-size: 11px;
}
@media (max-width: 1250px) {
    .ds-auth-main {
        grid-template-columns: minmax(0, 59%) minmax(0, 41%);
    }
    .ds-signin-section {
        padding: 46px 35px 54px;
    }
    .ds-showcase {
        padding: 46px 25px;
    }
    .ds-preview {
        grid-template-columns: 99px minmax(0, 1fr);
    }
    .ds-rail-link {
        font-size: 8px;
        padding: 9px 4px;
        gap: 5px;
    }
    .ds-preview-content {
        padding: 15px 12px;
    }
    .ds-file-version,
    .ds-file-action {
        display: none;
    }
}
@media (max-width: 1020px) {
    .ds-auth-main {
        display: block;
        min-height: 0;
    }
    .ds-signin-section {
        padding: 55px 35px 65px;
    }
    .ds-showcase {
        display: none;
    }
    .ds-form-wrap {
        max-width: 820px;
        margin-inline: auto;
    }
}
@media (max-width: 540px) {
    .ds-auth-header {
        padding: 12px 18px;
        min-height: 64px;
    }
    .ds-brand {
        font-size: 17px;
        gap: 7px;
    }
    .ds-brand-icon {
        transform: scale(0.84);
        transform-origin: left center;
        margin-right: -5px;
    }
    .ds-home-link {
        font-size: 11px;
    }
    .ds-signin-section {
        padding: 38px 17px 36px;
    }
    .ds-signin-card {
        padding: 26px 20px;
        border-radius: 12px;
    }
    .ds-form-wrap h1 {
        font-size: 39px;
    }
    .ds-auth-footer {
        padding: 15px 18px;
        flex-direction: column;
    }
}
@media (prefers-reduced-motion: reduce) {
    .ds-auth-page * {
        animation: none !important;
        transition: none !important;
    }
}
@media (max-width: 540px) {
    .ds-field input:not([type='checkbox']) {
        height: 54px;
    }
    .ds-submit {
        min-height: 54px;
    }
}
</style>
