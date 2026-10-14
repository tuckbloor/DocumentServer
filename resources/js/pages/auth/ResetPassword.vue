<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import PasswordInput from '@/components/PasswordInput.vue';
import { login } from '@/routes';
import { update } from '@/routes/password';

const props = defineProps<{
    token: string;
    email: string;
    passwordRules: string;
}>();
</script>

<template>
    <Head title="Reset Password | Document Server" />

    <div class="ds-auth-page">
        <!-- Header -->
        <header class="ds-header">
            <Link href="/" class="ds-brand">
                <span class="ds-brand-icon">DS</span>
                <span>Document <strong>Server</strong></span>
            </Link>

            <Link href="/" class="ds-back">
                Back to website
                <span aria-hidden="true">→</span>
            </Link>
        </header>

        <main class="ds-main">
            <!-- Left: Password reset form -->
            <section class="ds-left">
                <div class="ds-content">
                    <div class="ds-eyebrow">
                        <span class="ds-eyebrow-line"></span>
                        SECURE ACCOUNT RECOVERY
                    </div>

                    <h1>
                        Set a new
                        <span>password.</span>
                    </h1>

                    <p class="ds-intro">
                        Create a secure password for your Document Server
                        account and get back to managing your documents.
                    </p>

                    <div class="ds-card">
                        <div class="ds-card-header">
                            <div class="ds-card-icon">
                                <svg
                                    width="30"
                                    height="30"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    aria-hidden="true"
                                >
                                    <rect
                                        x="4"
                                        y="10"
                                        width="16"
                                        height="11"
                                        rx="2"
                                    />
                                    <path d="M8 10V7a4 4 0 0 1 8 0v3" />
                                    <circle cx="12" cy="15" r="1" />
                                    <path d="M12 16v2" />
                                </svg>
                            </div>

                            <div>
                                <h2>Reset your password</h2>
                                <p>Enter and confirm your new password.</p>
                            </div>
                        </div>

                        <Form
                            v-bind="update.form()"
                            :transform="
                                (data) => ({
                                    ...data,
                                    token: props.token,
                                    email: props.email,
                                })
                            "
                            :reset-on-success="[
                                'password',
                                'password_confirmation',
                            ]"
                            v-slot="{ errors, processing }"
                            class="ds-form"
                        >
                            <!-- Registered email -->
                            <div class="ds-field ds-email-field">
                                <label for="email">
                                    Account email address
                                </label>

                                <input
                                    id="email"
                                    type="email"
                                    name="email"
                                    :value="props.email"
                                    autocomplete="email"
                                    readonly
                                />

                                <p
                                    v-if="errors.email"
                                    class="ds-error"
                                    role="alert"
                                >
                                    {{ errors.email }}
                                </p>

                                <p v-else class="ds-field-help">
                                    This is the account associated with your
                                    password reset request.
                                </p>
                            </div>

                            <!-- Password fields -->
                            <div class="ds-password-grid">
                                <div class="ds-field">
                                    <label for="password"> New password </label>

                                    <PasswordInput
                                        id="password"
                                        name="password"
                                        autocomplete="new-password"
                                        placeholder="New password"
                                        :passwordrules="props.passwordRules"
                                        required
                                        autofocus
                                    />

                                    <p
                                        v-if="errors.password"
                                        class="ds-error"
                                        role="alert"
                                    >
                                        {{ errors.password }}
                                    </p>
                                </div>

                                <div class="ds-field">
                                    <label for="password_confirmation">
                                        Confirm new password
                                    </label>

                                    <PasswordInput
                                        id="password_confirmation"
                                        name="password_confirmation"
                                        autocomplete="new-password"
                                        placeholder="Confirm new password"
                                        :passwordrules="props.passwordRules"
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

                            <!-- Security notice -->
                            <div class="ds-notice">
                                <span class="ds-notice-icon">✓</span>

                                <div>
                                    <strong>Keep your account secure</strong>
                                    <p>
                                        Choose a strong password that you
                                        haven't used before.
                                    </p>
                                </div>
                            </div>

                            <button
                                type="submit"
                                class="ds-submit"
                                :disabled="processing"
                                data-test="reset-password-button"
                            >
                                <span>
                                    {{
                                        processing
                                            ? 'Updating password...'
                                            : 'Reset password'
                                    }}
                                </span>

                                <span v-if="!processing" aria-hidden="true">
                                    →
                                </span>
                            </button>
                        </Form>

                        <div class="ds-card-footer">
                            <span>Remember your password?</span>

                            <Link :href="login()"> Back to sign in → </Link>
                        </div>
                    </div>

                    <div class="ds-bottom-message">
                        Document Server · Secure account management
                    </div>
                </div>
            </section>

            <!-- Right: Document Server preview -->
            <aside class="ds-right">
                <div class="ds-showcase">
                    <div class="ds-showcase-label">
                        <span class="ds-online-dot"></span>
                        DOCUMENT SERVER WORKSPACE
                    </div>

                    <h2>
                        Your documents.
                        <span>Protected and organised.</span>
                    </h2>

                    <p class="ds-showcase-intro">
                        Secure access to Word and Excel files, organised
                        document history and controlled collaboration in one
                        workspace.
                    </p>

                    <!-- Decorative HTML/CSS workspace preview -->
                    <div class="ds-workspace">
                        <div class="ds-workspace-sidebar">
                            <div class="ds-workspace-logo">DS</div>

                            <div class="ds-sidebar-selected">
                                ▦ &nbsp; All documents
                            </div>

                            <div class="ds-sidebar-item">
                                ▤ &nbsp; My documents
                            </div>

                            <div class="ds-sidebar-item">
                                ◫ &nbsp; Shared with me
                            </div>

                            <div class="ds-sidebar-divider"></div>

                            <div class="ds-sidebar-item">
                                ◷ &nbsp; Recent files
                            </div>

                            <div class="ds-sidebar-item">
                                ☆ &nbsp; Favourites
                            </div>
                        </div>

                        <div class="ds-workspace-content">
                            <div class="ds-workspace-toolbar">
                                <div class="ds-search">Search documents...</div>

                                <span class="ds-upload"> + Upload </span>
                            </div>

                            <div class="ds-workspace-heading">
                                <div>
                                    <h3>Document Library</h3>
                                    <p>Manage your documents</p>
                                </div>

                                <span class="ds-file-count"> 128 files </span>
                            </div>

                            <div class="ds-workspace-tabs">
                                <span class="ds-tab-active">
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
                                    <strong>Asset Register.xlsx</strong>
                                    <small>Maintenance · Yesterday</small>
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

                            <div class="ds-workspace-bottom">
                                ✓ &nbsp; Managed document access
                            </div>
                        </div>
                    </div>

                    <div class="ds-benefits">
                        <span>✓ Private storage</span>
                        <span>✓ Version history</span>
                        <span>✓ Team sharing</span>
                    </div>
                </div>
            </aside>
        </main>

        <!-- Footer -->
        <footer class="ds-footer">
            <span> © {{ new Date().getFullYear() }} Document Server </span>

            <span>Secure document management</span>
        </footer>
    </div>
</template>

<style scoped>
.ds-auth-page {
    --navy: #10243e;
    --blue: #1769d7;
    --ink: #152942;

    min-height: 100vh;
    min-height: 100dvh;
    background: #fff;
    color: var(--ink);
    font-family: Inter, Arial, sans-serif;
}

.ds-auth-page *,
.ds-auth-page *::before,
.ds-auth-page *::after {
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
}

.ds-brand {
    display: flex;
    align-items: center;
    gap: 12px;
    color: #fff;
    text-decoration: none;
    font-size: 22px;
}

.ds-brand strong {
    font-weight: 800;
}

.ds-brand-icon {
    width: 42px;
    height: 42px;
    display: grid;
    place-items: center;
    background: #236ed6;
    border-radius: 9px;
    color: #fff;
    font-size: 14px;
    font-weight: 800;
}

.ds-back {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #d1dfef;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
}

.ds-back:hover {
    color: #fff;
}

/* Wide layout */
.ds-main {
    display: grid;
    grid-template-columns: minmax(0, 62fr) minmax(0, 38fr);
    min-height: calc(100vh - 132px);
}

.ds-left {
    min-width: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 50px clamp(26px, 3.5vw, 70px);
    background: #f8faff;
}

.ds-content {
    width: 100%;
    max-width: 1000px;
}

.ds-eyebrow {
    display: flex;
    align-items: center;
    gap: 11px;
    color: #1765c8;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 1.8px;
}

.ds-eyebrow-line {
    width: 29px;
    height: 3px;
    background: var(--blue);
    border-radius: 3px;
}

.ds-content h1 {
    margin: 20px 0 14px;
    font-size: clamp(38px, 3.8vw, 62px);
    font-weight: 800;
    letter-spacing: -2px;
    line-height: 1.12;
}

.ds-content h1 span {
    display: block;
    color: var(--blue);
}

.ds-intro {
    max-width: 750px;
    margin: 0 0 32px;
    color: #5d6f86;
    font-size: 16px;
    line-height: 1.8;
}

/* Wide form */
.ds-card {
    width: 100%;
    padding: clamp(28px, 3vw, 48px);
    border: 1px solid #e0e9f4;
    border-radius: 16px;
    background: #fff;
    box-shadow: 0 18px 45px rgba(16, 36, 62, 0.065);
}

.ds-card-header {
    display: flex;
    align-items: center;
    gap: 18px;
    margin-bottom: 34px;
}

.ds-card-icon {
    flex-shrink: 0;
    width: 60px;
    height: 60px;
    display: grid;
    place-items: center;
    border-radius: 13px;
    background: #eaf3ff;
    color: var(--blue);
}

.ds-card-header h2 {
    margin: 0 0 7px;
    font-size: 24px;
    font-weight: 800;
    letter-spacing: -0.4px;
}

.ds-card-header p {
    margin: 0;
    color: #6c7d93;
    font-size: 14px;
}

.ds-email-field {
    margin-bottom: 26px;
}

.ds-password-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 20px;
}

.ds-field {
    display: flex;
    flex-direction: column;
    gap: 10px;
    min-width: 0;
}

.ds-field label {
    font-size: 14px;
    font-weight: 750;
    color: #293d56;
}

.ds-field input,
.ds-field :deep(input) {
    width: 100%;
    min-width: 0;
    height: 59px;
    padding: 0 17px;
    border: 1px solid #c9d6e6;
    border-radius: 9px;
    background: #fff;
    color: #152942;
    font: inherit;
    font-size: 15px;
}

.ds-field input:focus,
.ds-field :deep(input:focus) {
    outline: none;
    border-color: var(--blue);
    box-shadow: 0 0 0 3px rgba(23, 105, 215, 0.12);
}

.ds-field input[readonly] {
    background: #f4f7fb;
    color: #62758c;
}

.ds-field :deep(input::placeholder) {
    color: #8b9bb0;
}

.ds-field-help {
    margin: 0;
    color: #72849b;
    font-size: 12px;
    line-height: 1.5;
}

.ds-error {
    margin: 0;
    color: #b42318;
    font-size: 12px;
    font-weight: 600;
}

.ds-notice {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    margin: 28px 0;
    padding: 18px;
    background: #f1f7ff;
    border: 1px solid #d7e6f9;
    border-radius: 10px;
}

.ds-notice-icon {
    width: 24px;
    height: 24px;
    flex-shrink: 0;
    display: grid;
    place-items: center;
    border-radius: 50%;
    background: #d8e9ff;
    color: var(--blue);
    font-weight: 800;
}

.ds-notice strong {
    display: block;
    margin-bottom: 5px;
    color: #24486f;
    font-size: 13px;
}

.ds-notice p {
    margin: 0;
    color: #58718e;
    font-size: 12px;
    line-height: 1.6;
}

.ds-submit {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
    width: 100%;
    min-height: 61px;
    padding: 16px 24px;
    border: none;
    border-radius: 9px;
    background: var(--blue);
    color: #fff;
    font-size: 16px;
    font-weight: 800;
    cursor: pointer;
}

.ds-submit:hover:not(:disabled) {
    background: #1155b3;
}

.ds-submit:disabled {
    opacity: 0.6;
    cursor: wait;
}

.ds-card-footer {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 7px;
    margin-top: 26px;
    padding-top: 24px;
    border-top: 1px solid #e6edf5;
    color: #708198;
    font-size: 13px;
}

.ds-card-footer a {
    color: var(--blue);
    font-weight: 800;
    text-decoration: none;
}

.ds-bottom-message {
    margin-top: 26px;
    text-align: center;
    color: #798aa1;
    font-size: 12px;
}

/* Workspace preview */
.ds-right {
    min-width: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 50px clamp(22px, 3vw, 55px);
    background: linear-gradient(150deg, #15375f, #071b32);
    color: #fff;
    overflow: hidden;
}

.ds-showcase {
    width: 100%;
    max-width: 740px;
}

.ds-showcase-label {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #9dc7ff;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 1.6px;
}

.ds-online-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #76b9ff;
}

.ds-showcase h2 {
    margin: 25px 0 15px;
    font-size: clamp(30px, 2.8vw, 45px);
    font-weight: 800;
    line-height: 1.2;
    letter-spacing: -1.2px;
}

.ds-showcase h2 span {
    display: block;
    color: #91bfff;
}

.ds-showcase-intro {
    margin: 0 0 35px;
    max-width: 580px;
    color: #c6d8ec;
    font-size: 14px;
    line-height: 1.8;
}

.ds-workspace {
    display: grid;
    grid-template-columns: 125px minmax(0, 1fr);
    min-height: 355px;
    overflow: hidden;
    border-radius: 12px;
    background: #fff;
    color: #142942;
    box-shadow: 0 24px 60px rgba(0, 0, 0, 0.25);
}

.ds-workspace-sidebar {
    padding: 18px 9px;
    background: #11243e;
    color: #c9d8ec;
}

.ds-workspace-logo {
    display: grid;
    place-items: center;
    width: 36px;
    height: 36px;
    margin: 0 0 28px 8px;
    border-radius: 7px;
    background: #2472db;
    color: #fff;
    font-size: 12px;
    font-weight: 800;
}

.ds-sidebar-item,
.ds-sidebar-selected {
    padding: 12px 8px;
    font-size: 10px;
    white-space: nowrap;
    border-radius: 5px;
}

.ds-sidebar-selected {
    background: #206bcf;
    color: #fff;
    font-weight: 800;
}

.ds-sidebar-divider {
    height: 1px;
    margin: 12px 7px;
    background: #314661;
}

.ds-workspace-content {
    min-width: 0;
    padding: 18px 15px;
}

.ds-workspace-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
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
    border-radius: 5px;
    background: var(--blue);
    color: #fff;
    font-size: 10px;
    font-weight: 800;
}

.ds-workspace-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-top: 30px;
}

.ds-workspace-heading h3 {
    margin: 0;
    font-size: 19px;
    font-weight: 800;
}

.ds-workspace-heading p {
    margin: 5px 0 0;
    color: #8899ac;
    font-size: 10px;
}

.ds-file-count {
    padding: 7px;
    border-radius: 5px;
    background: #ecf3ff;
    color: var(--blue);
    font-size: 10px;
    font-weight: 800;
}

.ds-workspace-tabs {
    display: flex;
    gap: 15px;
    margin: 24px 0 14px;
    border-bottom: 1px solid #e5ecf4;
}

.ds-workspace-tabs span {
    padding-bottom: 10px;
    color: #8899ad;
    font-size: 10px;
}

.ds-workspace-tabs .ds-tab-active {
    color: var(--blue);
    border-bottom: 2px solid var(--blue);
    font-weight: 800;
}

.ds-document-row {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 9px;
    padding: 11px 8px;
    border: 1px solid #e8edf4;
    border-radius: 7px;
}

.ds-word-icon,
.ds-excel-icon {
    flex-shrink: 0;
    display: grid;
    place-items: center;
    width: 31px;
    height: 34px;
    border-radius: 4px;
    color: #fff;
    font-size: 14px;
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
    white-space: nowrap;
    text-overflow: ellipsis;
    font-size: 10px;
}

.ds-document-info small {
    color: #8395aa;
    font-size: 9px;
}

.ds-version {
    color: #75879c;
    font-size: 10px;
}

.ds-workspace-bottom {
    margin-top: 15px;
    color: #5e819e;
    font-size: 10px;
}

.ds-benefits {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    gap: 12px;
    margin-top: 30px;
    color: #c4d8f0;
    font-size: 12px;
}

/* Footer */
.ds-footer {
    min-height: 54px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    padding: 15px 5%;
    border-top: 1px solid #e7edf5;
    background: #fff;
    color: #728198;
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
        max-width: 920px;
    }
}

@media (max-width: 700px) {
    .ds-header {
        height: 68px;
        padding: 0 20px;
    }

    .ds-brand {
        font-size: 18px;
    }

    .ds-brand-icon {
        width: 35px;
        height: 35px;
    }

    .ds-left {
        padding: 38px 18px;
    }

    .ds-content h1 {
        font-size: 38px;
    }

    .ds-card {
        padding: 26px 20px;
    }

    .ds-password-grid {
        grid-template-columns: 1fr;
    }

    .ds-card-header h2 {
        font-size: 20px;
    }

    .ds-footer {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>
