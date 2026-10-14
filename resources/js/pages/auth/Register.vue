
<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import PasswordInput from '@/components/PasswordInput.vue';
import { login } from '@/routes';
import { store } from '@/routes/register';

defineOptions({
    layout: null,
});

defineProps<{
    passwordRules: string;
}>();
</script>

<template>
    <Head title="Register | Document Server" />

    <div class="ds-page">
        <!-- Header -->
        <header class="ds-header">
            <Link href="/" class="ds-brand">
                <span class="ds-brand-icon">DS</span>
                <span>Document <strong>Server</strong></span>
            </Link>

            <Link href="/" class="ds-back">
                ← Back to website
            </Link>
        </header>

        <main class="ds-main">
            <!-- Registration section -->
            <section class="ds-left">
                <div class="ds-content">
                    <div class="ds-eyebrow">
                        <span></span>
                        YOUR DOCUMENT WORKSPACE
                    </div>

                    <h1>
                        Join Document Server<span>.</span>
                    </h1>

                    <p class="ds-intro">
                        Create your account to access documents,
                        collaborate with colleagues and manage
                        your files in one secure workspace.
                    </p>

                    <div class="ds-card">
                        <div class="ds-card-header">
                            <div class="ds-card-icon">
                                <svg
                                    viewBox="0 0 24 24"
                                    width="26"
                                    height="26"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <circle cx="9" cy="8" r="3.5" />
                                    <path
                                        d="M2.5 20v-2a6.5 6.5 0 0 1 13 0v2M19 8v8m-4-4h8"
                                    />
                                </svg>
                            </div>

                            <div>
                                <h2>Create your account</h2>
                                <p>
                                    Enter your details below to get started.
                                </p>
                            </div>
                        </div>

                        <Form
                            v-bind="store.form()"
                            :reset-on-success="[
                                'password',
                                'password_confirmation',
                            ]"
                            v-slot="{ errors, processing }"
                            class="ds-form"
                        >
                            <div class="ds-form-grid">
                                <!-- Full name -->
                                <div class="ds-field">
                                    <label for="name">
                                        Full name
                                    </label>

                                    <input
                                        id="name"
                                        name="name"
                                        type="text"
                                        autocomplete="name"
                                        placeholder="Your full name"
                                        required
                                        autofocus
                                        :aria-invalid="!!errors.name"
                                    />

                                    <p
                                        v-if="errors.name"
                                        class="ds-error"
                                        role="alert"
                                    >
                                        {{ errors.name }}
                                    </p>
                                </div>

                                <!-- Email -->
                                <div class="ds-field">
                                    <label for="email">
                                        Email address
                                    </label>

                                    <input
                                        id="email"
                                        name="email"
                                        type="email"
                                        autocomplete="email"
                                        placeholder="name@company.com"
                                        required
                                        :aria-invalid="!!errors.email"
                                    />

                                    <p
                                        v-if="errors.email"
                                        class="ds-error"
                                        role="alert"
                                    >
                                        {{ errors.email }}
                                    </p>
                                </div>

                                <!-- Password -->
                                <div class="ds-field">
                                    <label for="password">
                                        Password
                                    </label>

                                    <PasswordInput
                                        id="password"
                                        name="password"
                                        autocomplete="new-password"
                                        placeholder="Create a password"
                                        :passwordrules="passwordRules"
                                        required
                                    />

                                    <p
                                        v-if="errors.password"
                                        class="ds-error"
                                        role="alert"
                                    >
                                        {{ errors.password }}
                                    </p>
                                </div>

                                <!-- Confirm password -->
                                <div class="ds-field">
                                    <label for="password_confirmation">
                                        Confirm password
                                    </label>

                                    <PasswordInput
                                        id="password_confirmation"
                                        name="password_confirmation"
                                        autocomplete="new-password"
                                        placeholder="Confirm your password"
                                        :passwordrules="passwordRules"
                                        required
                                    />

                                    <p
                                        v-if="errors.password_confirmation"
                                        class="ds-error"
                                        role="alert"
                                    >
                                        {{ errors.password_confirmation }}
                                    </p>
                                </div>
                            </div>

                            <div class="ds-notice">
                                <span class="ds-notice-icon">✓</span>

                                <p>
                                    Your account must meet your
                                    organisation's registration and
                                    access requirements before
                                    documents become available.
                                </p>
                            </div>

                            <button
                                type="submit"
                                class="ds-submit"
                                :disabled="processing"
                                data-test="register-user-button"
                            >
                                <span>
                                    {{
                                        processing
                                            ? 'Creating account...'
                                            : 'Create account'
                                    }}
                                </span>

                                <span v-if="!processing">→</span>
                            </button>
                        </Form>

                        <div class="ds-signin">
                            Already have an account?

                            <Link :href="login()">
                                Sign in →
                            </Link>
                        </div>
                    </div>

                    <div class="ds-under-card">
                        Document Server · Secure document management
                    </div>
                </div>
            </section>

            <!-- Right-hand workspace preview -->
            <aside class="ds-right">
                <div class="ds-showcase">
                    <div class="ds-showcase-label">
                        <span class="ds-dot"></span>
                        YOUR FILES, ORGANISED
                    </div>

                    <h2>
                        All your documents.
                        <span>One secure workspace.</span>
                    </h2>

                    <p class="ds-showcase-text">
                        Access Word and Excel documents, collaborate
                        with colleagues and keep track of every
                        revision in one place.
                    </p>

                    <!-- HTML/CSS document dashboard preview -->
                    <div class="ds-preview">
                        <div class="ds-preview-sidebar">
                            <div class="ds-preview-logo">DS</div>

                            <div class="ds-sidebar-active">
                                ▦ &nbsp; All documents
                            </div>

                            <div class="ds-sidebar-link">
                                ▤ &nbsp; My documents
                            </div>

                            <div class="ds-sidebar-link">
                                ◫ &nbsp; Shared with me
                            </div>

                            <div class="ds-sidebar-divider"></div>

                            <div class="ds-sidebar-link">
                                ◷ &nbsp; Recent files
                            </div>

                            <div class="ds-sidebar-link">
                                ☆ &nbsp; Favourites
                            </div>
                        </div>

                        <div class="ds-preview-main">
                            <div class="ds-preview-toolbar">
                                <div class="ds-search">
                                    Search documents...
                                </div>

                                <span class="ds-upload">
                                    + Upload
                                </span>
                            </div>

                            <div class="ds-preview-heading">
                                <div>
                                    <h3>Document Library</h3>
                                    <p>Manage your team's files</p>
                                </div>

                                <span class="ds-file-count">
                                    128 files
                                </span>
                            </div>

                            <div class="ds-preview-tabs">
                                <span class="ds-preview-selected">
                                    All documents
                                </span>
                                <span>Word</span>
                                <span>Excel</span>
                            </div>

                            <div class="ds-document-row">
                                <div class="ds-word-icon">W</div>

                                <div class="ds-document-info">
                                    <strong>Backup Policy.docx</strong>
                                    <small>IT Policies · Updated today</small>
                                </div>

                                <span class="ds-version">v3</span>
                            </div>

                            <div class="ds-document-row">
                                <div class="ds-excel-icon">X</div>

                                <div class="ds-document-info">
                                    <strong>Network Inventory.xlsx</strong>
                                    <small>Infrastructure · Yesterday</small>
                                </div>

                                <span class="ds-version">v2</span>
                            </div>

                            <div class="ds-document-row">
                                <div class="ds-word-icon">W</div>

                                <div class="ds-document-info">
                                    <strong>Company Policy.docx</strong>
                                    <small>IT Policies · Shared</small>
                                </div>

                                <span class="ds-version">v5</span>
                            </div>

                            <div class="ds-preview-bottom">
                                ✓ &nbsp; Permissions and version history
                            </div>
                        </div>
                    </div>

                    <div class="ds-benefits">
                        <span>✓ Controlled access</span>
                        <span>✓ Version history</span>
                        <span>✓ Team sharing</span>
                    </div>
                </div>
            </aside>
        </main>

        <footer class="ds-footer">
            <span>
                © {{ new Date().getFullYear() }} Document Server
            </span>

            <span>Document management workspace</span>
        </footer>
    </div>
</template>

<style scoped>
.ds-page {
    --navy: #10243e;
    --blue: #1769d7;
    --text: #152942;

    min-height: 100vh;
    background: #fff;
    color: var(--text);
    font-family: Inter, Arial, sans-serif;
}

.ds-page * {
    box-sizing: border-box;
}

/* Header */
.ds-header {
    height: 78px;
    padding: 0 5%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: var(--navy);
    color: white;
}

.ds-brand {
    display: flex;
    align-items: center;
    gap: 12px;
    color: white;
    text-decoration: none;
    font-size: 22px;
    font-weight: 500;
}

.ds-brand strong {
    font-weight: 800;
}

.ds-brand-icon {
    display: grid;
    place-items: center;
    width: 40px;
    height: 40px;
    border-radius: 9px;
    background: #236ed4;
    font-size: 14px;
    font-weight: 800;
}

.ds-back {
    color: #d0def0;
    font-size: 13px;
    text-decoration: none;
    font-weight: 600;
}

.ds-back:hover {
    color: white;
}

/* Main layout */
.ds-main {
    display: grid;
    grid-template-columns: minmax(0, 56%) minmax(0, 44%);
    min-height: calc(100vh - 132px);
}

.ds-left {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 54px clamp(24px, 4vw, 85px);
    background: #f8faff;
}

.ds-content {
    width: 100%;
    max-width: 850px;
}

.ds-eyebrow {
    display: flex;
    align-items: center;
    gap: 11px;
    color: #1a64c7;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 2px;
}

.ds-eyebrow span {
    width: 27px;
    height: 3px;
    background: var(--blue);
    border-radius: 3px;
}

.ds-content h1 {
    margin: 18px 0 12px;
    font-size: clamp(34px, 3.2vw, 54px);
    font-weight: 800;
    letter-spacing: -1.9px;
    line-height: 1.16;
}

.ds-content h1 span {
    color: var(--blue);
}

.ds-intro {
    color: #5a6e88;
    line-height: 1.75;
    font-size: 15px;
    max-width: 690px;
    margin-bottom: 30px;
}

/* Form card */
.ds-card {
    width: 100%;
    padding: clamp(25px, 3vw, 48px);
    background: #fff;
    border: 1px solid #e0e9f4;
    border-radius: 16px;
    box-shadow: 0 18px 45px rgba(16, 36, 62, 0.065);
}

.ds-card-header {
    display: flex;
    align-items: center;
    gap: 17px;
    margin-bottom: 30px;
}

.ds-card-icon {
    width: 56px;
    height: 56px;
    display: grid;
    place-items: center;
    flex-shrink: 0;
    border-radius: 12px;
    background: #eaf3ff;
    color: #1769d7;
}

.ds-card-header h2 {
    font-size: 21px;
    font-weight: 800;
    margin: 0 0 5px;
}

.ds-card-header p {
    font-size: 13px;
    color: #6b7c92;
    margin: 0;
}

/* Registration fields */
.ds-form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 23px 19px;
}

.ds-field {
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 9px;
}

.ds-field label {
    font-size: 13px;
    font-weight: 750;
    color: #283c55;
}

.ds-field input,
.ds-field :deep(input) {
    width: 100%;
    min-width: 0;
    height: 57px;
    border: 1px solid #cbd7e7;
    border-radius: 9px;
    padding: 0 16px;
    background: #fff;
    color: #132642;
    font-size: 15px;
}

.ds-field input:focus,
.ds-field :deep(input:focus) {
    outline: 0;
    border-color: #1769d7;
    box-shadow: 0 0 0 3px rgba(23, 105, 215, 0.12);
}

.ds-field input::placeholder,
.ds-field :deep(input::placeholder) {
    color: #8b9bb0;
}

.ds-error {
    color: #b42318;
    font-size: 12px;
    margin: 0;
}

.ds-notice {
    display: flex;
    align-items: flex-start;
    gap: 13px;
    margin: 26px 0;
    padding: 17px;
    border: 1px solid #d7e6f9;
    background: #f1f7ff;
    border-radius: 10px;
}

.ds-notice p {
    margin: 0;
    color: #45607e;
    font-size: 13px;
    line-height: 1.6;
}

.ds-notice-icon {
    display: grid;
    place-items: center;
    flex-shrink: 0;
    width: 23px;
    height: 23px;
    border-radius: 50%;
    background: #d5e7ff;
    color: #1769d7;
    font-weight: 800;
}

.ds-submit {
    width: 100%;
    min-height: 58px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
    padding: 15px;
    background: #1769d7;
    color: white;
    border: none;
    border-radius: 9px;
    font-size: 16px;
    font-weight: 800;
    cursor: pointer;
    transition: background 0.2s;
}

.ds-submit:hover:not(:disabled) {
    background: #0d55b6;
}

.ds-submit:disabled {
    opacity: 0.6;
    cursor: wait;
}

.ds-signin {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 7px;
    margin-top: 25px;
    padding-top: 23px;
    border-top: 1px solid #e7edf5;
    font-size: 13px;
    color: #687b94;
}

.ds-signin a {
    color: #1769d7;
    font-weight: 800;
    text-decoration: none;
}

.ds-under-card {
    margin-top: 25px;
    color: #798aa1;
    font-size: 12px;
    text-align: center;
}

/* Right panel */
.ds-right {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 55px clamp(24px, 3vw, 55px);
    overflow: hidden;
    background: linear-gradient(150deg, #15375f, #071b32);
    color: white;
}

.ds-showcase {
    width: 100%;
    max-width: 750px;
}

.ds-showcase-label {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #9bc7ff;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 2px;
}

.ds-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #70b5ff;
}

.ds-showcase h2 {
    margin: 24px 0 15px;
    font-size: clamp(30px, 2.8vw, 45px);
    font-weight: 800;
    letter-spacing: -1.2px;
    line-height: 1.2;
}

.ds-showcase h2 span {
    display: block;
    color: #8dbfff;
}

.ds-showcase-text {
    color: #c4d5eb;
    font-size: 14px;
    line-height: 1.8;
    margin-bottom: 35px;
}

/* Fake dashboard */
.ds-preview {
    display: grid;
    grid-template-columns: 135px minmax(0, 1fr);
    min-height: 355px;
    border-radius: 12px;
    overflow: hidden;
    background: white;
    color: #142942;
    box-shadow: 0 24px 60px rgba(0, 0, 0, 0.25);
}

.ds-preview-sidebar {
    padding: 18px 10px;
    background: #11243e;
    color: #c9d8ec;
}

.ds-preview-logo {
    width: 36px;
    height: 36px;
    display: grid;
    place-items: center;
    margin: 0 0 27px 8px;
    border-radius: 7px;
    background: #2472db;
    font-size: 12px;
    font-weight: 800;
    color: white;
}

.ds-sidebar-link,
.ds-sidebar-active {
    padding: 12px 8px;
    font-size: 10px;
    white-space: nowrap;
    border-radius: 5px;
}

.ds-sidebar-active {
    background: #206bd0;
    color: white;
    font-weight: 800;
}

.ds-sidebar-divider {
    height: 1px;
    margin: 12px 7px;
    background: #314661;
}

.ds-preview-main {
    padding: 18px;
    min-width: 0;
}

.ds-preview-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}

.ds-search {
    padding: 9px;
    border-radius: 6px;
    background: #f5f8fc;
    border: 1px solid #e5eaf2;
    color: #9aaac0;
    font-size: 10px;
}

.ds-upload {
    padding: 9px 11px;
    background: #1769d7;
    color: white;
    border-radius: 5px;
    font-size: 10px;
    font-weight: 800;
}

.ds-preview-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-top: 30px;
}

.ds-preview-heading h3 {
    margin: 0;
    font-size: 19px;
    font-weight: 800;
}

.ds-preview-heading p {
    margin: 5px 0 0;
    color: #8899ac;
    font-size: 10px;
}

.ds-file-count {
    padding: 7px;
    border-radius: 5px;
    background: #ecf3ff;
    color: #1761b8;
    font-size: 10px;
    font-weight: 800;
}

.ds-preview-tabs {
    display: flex;
    gap: 16px;
    margin: 24px 0 13px;
    border-bottom: 1px solid #e5ecf4;
}

.ds-preview-tabs span {
    padding-bottom: 10px;
    color: #8899ad;
    font-size: 10px;
}

.ds-preview-tabs .ds-preview-selected {
    color: #1769d7;
    border-bottom: 2px solid #1769d7;
    font-weight: 800;
}

.ds-document-row {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 11px 8px;
    margin-bottom: 9px;
    border: 1px solid #e8edf4;
    border-radius: 7px;
}

.ds-word-icon,
.ds-excel-icon {
    width: 31px;
    height: 34px;
    display: grid;
    place-items: center;
    flex-shrink: 0;
    border-radius: 4px;
    color: white;
    font-size: 15px;
    font-weight: 800;
}

.ds-word-icon {
    background: #1e61bd;
}

.ds-excel-icon {
    background: #147948;
}

.ds-document-info {
    min-width: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.ds-document-info strong {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 11px;
}

.ds-document-info small {
    color: #8395aa;
    font-size: 9px;
}

.ds-version {
    color: #72849a;
    font-size: 10px;
}

.ds-preview-bottom {
    margin-top: 14px;
    color: #5d7d9d;
    font-size: 10px;
}

.ds-benefits {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    margin-top: 32px;
    color: #bbd3ef;
    font-size: 12px;
}

.ds-footer {
    min-height: 54px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    padding: 15px 5%;
    border-top: 1px solid #e7edf5;
    color: #718198;
    font-size: 12px;
}

/* Responsive */
@media (max-width: 1150px) {
    .ds-main {
        grid-template-columns: 1fr;
    }

    .ds-right {
        display: none;
    }

    .ds-content {
        max-width: 850px;
    }
}

@media (max-width: 650px) {
    .ds-header {
        padding: 0 20px;
    }

    .ds-brand {
        font-size: 17px;
    }

    .ds-brand-icon {
        width: 34px;
        height: 34px;
    }

    .ds-left {
        padding: 40px 18px;
    }

    .ds-card {
        padding: 24px 20px;
    }

    .ds-form-grid {
        grid-template-columns: 1fr;
        gap: 22px;
    }

    .ds-content h1 {
        font-size: 36px;
    }

    .ds-footer {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>
