<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { login } from '@/routes';
import { email } from '@/routes/password';

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head title="Forgot Password | Document Server" />

    <div class="ds-auth-page">
        <!-- Header -->
        <header class="ds-auth-header">
            <Link href="/" class="ds-brand">
                <span class="ds-brand-icon">DS</span>

                <span> Document <strong>Server</strong> </span>
            </Link>

            <Link href="/" class="ds-header-link">
                Back to website
                <span aria-hidden="true">→</span>
            </Link>
        </header>

        <main class="ds-auth-layout">
            <!-- Left: Forgot password form -->
            <section class="ds-form-side">
                <div class="ds-form-content">
                    <div class="ds-eyebrow">
                        <span class="ds-eyebrow-line"></span>
                        SECURE ACCOUNT RECOVERY
                    </div>

                    <h1>
                        Forgot your
                        <span>password?</span>
                    </h1>

                    <p class="ds-intro">
                        Enter the email address associated with your Document
                        Server account. We'll send you a secure link to reset
                        your password.
                    </p>

                    <div class="ds-form-card">
                        <div class="ds-card-heading">
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
                                        x="3"
                                        y="5"
                                        width="18"
                                        height="14"
                                        rx="3"
                                    />
                                    <path d="m4 7 8 6 8-6" />
                                </svg>
                            </div>

                            <div>
                                <h2>Reset your password</h2>
                                <p>
                                    We'll help you regain access to your
                                    documents.
                                </p>
                            </div>
                        </div>

                        <!-- Laravel status -->
                        <div v-if="status" class="ds-success" role="status">
                            <span class="ds-success-icon">✓</span>

                            <div>
                                <strong>Request received</strong>
                                <p>{{ status }}</p>
                            </div>
                        </div>

                        <!-- Existing Laravel password reset route -->
                        <Form
                            v-bind="email.form()"
                            v-slot="{ errors, processing }"
                            class="ds-reset-form"
                        >
                            <div class="ds-field">
                                <label for="email"> Email address </label>

                                <input
                                    id="email"
                                    type="email"
                                    name="email"
                                    placeholder="name@company.com"
                                    autocomplete="email"
                                    required
                                    autofocus
                                    :aria-invalid="!!errors.email"
                                    :aria-describedby="
                                        errors.email
                                            ? 'email-error'
                                            : 'email-help'
                                    "
                                />

                                <p
                                    v-if="errors.email"
                                    id="email-error"
                                    class="ds-error"
                                    role="alert"
                                >
                                    {{ errors.email }}
                                </p>

                                <p v-else id="email-help" class="ds-field-help">
                                    Use the email address you registered with
                                    Document Server.
                                </p>
                            </div>

                            <button
                                type="submit"
                                class="ds-submit"
                                :disabled="processing"
                                data-test="email-password-reset-link-button"
                            >
                                <span>
                                    {{
                                        processing
                                            ? 'Sending reset link...'
                                            : 'Email password reset link'
                                    }}
                                </span>

                                <span v-if="!processing" aria-hidden="true">
                                    →
                                </span>
                            </button>
                        </Form>

                        <div class="ds-signin-row">
                            <span>Remember your password?</span>

                            <Link :href="login()"> Back to sign in → </Link>
                        </div>
                    </div>

                    <!-- Recovery guidance -->
                    <div class="ds-recovery-guide">
                        <div class="ds-guide-heading">
                            <span class="ds-guide-icon">i</span>
                            <strong>What happens next?</strong>
                        </div>

                        <div class="ds-guide-grid">
                            <div class="ds-guide-item">
                                <span class="ds-step">01</span>
                                <div>
                                    <strong>Check your inbox</strong>
                                    <p>Look for your password reset email.</p>
                                </div>
                            </div>

                            <div class="ds-guide-item">
                                <span class="ds-step">02</span>
                                <div>
                                    <strong>Open the secure link</strong>
                                    <p>Follow the instructions in the email.</p>
                                </div>
                            </div>

                            <div class="ds-guide-item">
                                <span class="ds-step">03</span>
                                <div>
                                    <strong>Sign back in</strong>
                                    <p>
                                        Access your documents using your new
                                        password.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Right: Document Server workspace preview -->
            <aside class="ds-preview-side">
                <div class="ds-preview-content">
                    <div class="ds-preview-eyebrow">
                        <span class="ds-online-dot"></span>
                        DOCUMENT SERVER WORKSPACE
                    </div>

                    <h2>
                        Your documents.
                        <span>Secure and organised.</span>
                    </h2>

                    <p class="ds-preview-intro">
                        One place for Word and Excel files, controlled document
                        sharing and organised revision history.
                    </p>

                    <div class="ds-workspace">
                        <div class="ds-workspace-sidebar">
                            <div class="ds-workspace-logo">DS</div>

                            <div class="ds-sidebar-active">
                                ▦ &nbsp; Documents
                            </div>

                            <div class="ds-sidebar-item">▤ &nbsp; My files</div>

                            <div class="ds-sidebar-item">◫ &nbsp; Shared</div>

                            <div class="ds-sidebar-separator"></div>

                            <div class="ds-sidebar-item">◷ &nbsp; Recent</div>

                            <div class="ds-sidebar-item">
                                ☆ &nbsp; Favourites
                            </div>
                        </div>

                        <div class="ds-workspace-main">
                            <div class="ds-workspace-top">
                                <span class="ds-workspace-search">
                                    Search documents...
                                </span>

                                <span class="ds-workspace-upload">
                                    + Upload
                                </span>
                            </div>

                            <div class="ds-workspace-title">
                                <div>
                                    <h3>Document Library</h3>
                                    <p>Your document workspace</p>
                                </div>

                                <span class="ds-file-total"> 128 files </span>
                            </div>

                            <div class="ds-workspace-tabs">
                                <span class="ds-tab-selected"> All files </span>
                                <span>Word</span>
                                <span>Excel</span>
                            </div>

                            <div class="ds-file-row">
                                <span class="ds-file-icon ds-word"> W </span>

                                <div class="ds-file-details">
                                    <strong>Company Policy.docx</strong>
                                    <small> IT Policies · Updated today </small>
                                </div>

                                <span class="ds-version"> v3 </span>
                            </div>

                            <div class="ds-file-row">
                                <span class="ds-file-icon ds-excel"> X </span>

                                <div class="ds-file-details">
                                    <strong>Asset Register.xlsx</strong>
                                    <small> Maintenance · Yesterday </small>
                                </div>

                                <span class="ds-version"> v2 </span>
                            </div>

                            <div class="ds-file-row">
                                <span class="ds-file-icon ds-word"> W </span>

                                <div class="ds-file-details">
                                    <strong>Backup Procedure.docx</strong>
                                    <small> Infrastructure · Shared </small>
                                </div>

                                <span class="ds-version"> v5 </span>
                            </div>

                            <div class="ds-workspace-footer">
                                ✓ &nbsp; Managed access & version history
                            </div>
                        </div>
                    </div>

                    <div class="ds-preview-benefits">
                        <span>✓ Private storage</span>
                        <span>✓ Version history</span>
                        <span>✓ Team sharing</span>
                    </div>
                </div>
            </aside>
        </main>

        <footer class="ds-auth-footer">
            <span> © {{ new Date().getFullYear() }} Document Server </span>

            <span>Secure document management</span>
        </footer>
    </div>
</template>

<style scoped>
.ds-auth-page {
    --ds-navy: #10243e;
    --ds-blue: #1769d7;
    --ds-ink: #152942;

    min-height: 100vh;
    min-height: 100dvh;
    background: #fff;
    color: var(--ds-ink);
    font-family: Inter, Arial, sans-serif;
}

.ds-auth-page *,
.ds-auth-page *::before,
.ds-auth-page *::after {
    box-sizing: border-box;
}

/* Header */
.ds-auth-header {
    height: 78px;
    padding: 0 5%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: var(--ds-navy);
}

.ds-brand {
    display: flex;
    align-items: center;
    gap: 12px;
    color: #fff;
    font-size: 22px;
    font-weight: 500;
    text-decoration: none;
}

.ds-brand strong {
    font-weight: 800;
}

.ds-brand-icon {
    display: grid;
    place-items: center;
    width: 41px;
    height: 41px;
    border-radius: 9px;
    background: #2370d9;
    color: #fff;
    font-size: 14px;
    font-weight: 800;
}

.ds-header-link {
    display: flex;
    align-items: center;
    gap: 12px;
    color: #d0dfef;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
}

.ds-header-link:hover {
    color: #fff;
}

/* Wide desktop layout */
.ds-auth-layout {
    display: grid;
    grid-template-columns: minmax(0, 60fr) minmax(0, 40fr);
    min-height: calc(100vh - 132px);
}

.ds-form-side {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 0;
    padding: 55px clamp(30px, 4vw, 85px);
    background: #f8faff;
}

.ds-form-content {
    width: 100%;
    max-width: 920px;
}

.ds-eyebrow {
    display: flex;
    align-items: center;
    gap: 12px;
    color: #1765c8;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 1.8px;
}

.ds-eyebrow-line {
    width: 29px;
    height: 3px;
    background: var(--ds-blue);
    border-radius: 3px;
}

.ds-form-content h1 {
    margin: 20px 0 15px;
    font-size: clamp(38px, 3.7vw, 60px);
    line-height: 1.12;
    letter-spacing: -2px;
    font-weight: 800;
}

.ds-form-content h1 span {
    display: block;
    color: var(--ds-blue);
}

.ds-intro {
    max-width: 750px;
    margin: 0 0 32px;
    color: #5c6d84;
    font-size: 16px;
    line-height: 1.8;
}

/* Form card */
.ds-form-card {
    width: 100%;
    padding: clamp(30px, 3.2vw, 52px);
    border: 1px solid #e0e8f3;
    border-radius: 16px;
    background: #fff;
    box-shadow: 0 18px 45px rgba(16, 36, 62, 0.065);
}

.ds-card-heading {
    display: flex;
    align-items: center;
    gap: 18px;
    margin-bottom: 34px;
}

.ds-card-icon {
    display: grid;
    place-items: center;
    flex-shrink: 0;
    width: 60px;
    height: 60px;
    border-radius: 13px;
    background: #eaf3ff;
    color: #1769d7;
}

.ds-card-heading h2 {
    margin: 0 0 7px;
    font-size: 24px;
    font-weight: 800;
    letter-spacing: -0.4px;
}

.ds-card-heading p {
    margin: 0;
    color: #6c7d93;
    font-size: 14px;
    line-height: 1.5;
}

.ds-reset-form {
    width: 100%;
}

.ds-field {
    display: flex;
    flex-direction: column;
    gap: 11px;
}

.ds-field label {
    font-size: 14px;
    font-weight: 750;
    color: #2c3f59;
}

.ds-field input {
    width: 100%;
    height: 62px;
    padding: 0 19px;
    border: 1px solid #c5d3e4;
    border-radius: 10px;
    background: #fff;
    color: #152942;
    font: inherit;
    font-size: 16px;
    outline: none;
    transition:
        border-color 0.2s,
        box-shadow 0.2s;
}

.ds-field input::placeholder {
    color: #899bb0;
}

.ds-field input:focus {
    border-color: #1769d7;
    box-shadow: 0 0 0 4px rgba(23, 105, 215, 0.11);
}

.ds-field input[aria-invalid='true'] {
    border-color: #c62828;
}

.ds-field-help {
    margin: 0;
    color: #71839a;
    font-size: 12px;
    line-height: 1.6;
}

.ds-error {
    margin: 0;
    color: #b42318;
    font-size: 13px;
    font-weight: 600;
}

.ds-submit {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
    width: 100%;
    min-height: 62px;
    margin-top: 30px;
    padding: 17px 24px;
    border: none;
    border-radius: 10px;
    background: var(--ds-blue);
    color: #fff;
    font-size: 16px;
    font-weight: 800;
    cursor: pointer;
    transition: background 0.2s;
}

.ds-submit:hover:not(:disabled) {
    background: #1155b3;
}

.ds-submit:focus-visible {
    outline: 3px solid #8dbfff;
    outline-offset: 3px;
}

.ds-submit:disabled {
    opacity: 0.65;
    cursor: wait;
}

.ds-success {
    display: flex;
    gap: 14px;
    align-items: flex-start;
    margin-bottom: 24px;
    padding: 19px;
    border-radius: 10px;
    border: 1px solid #b6dec9;
    background: #f0faf4;
    color: #18583a;
}

.ds-success-icon {
    display: grid;
    place-items: center;
    flex-shrink: 0;
    width: 26px;
    height: 26px;
    border-radius: 50%;
    background: #d3efde;
    font-weight: 800;
}

.ds-success strong {
    font-size: 14px;
}

.ds-success p {
    margin: 5px 0 0;
    font-size: 13px;
    line-height: 1.6;
}

.ds-signin-row {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 7px;
    margin-top: 30px;
    padding-top: 25px;
    border-top: 1px solid #e6edf5;
    color: #718198;
    font-size: 14px;
}

.ds-signin-row a {
    color: #1769d7;
    font-weight: 800;
    text-decoration: none;
}

.ds-signin-row a:hover {
    text-decoration: underline;
}

/* Recovery steps */
.ds-recovery-guide {
    margin-top: 34px;
    padding: 25px 0;
}

.ds-guide-heading {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 25px;
    font-size: 15px;
}

.ds-guide-icon {
    display: grid;
    place-items: center;
    width: 23px;
    height: 23px;
    border-radius: 50%;
    background: #e7f0ff;
    color: #1769d7;
    font-weight: 800;
}

.ds-guide-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 20px;
}

.ds-guide-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
}

.ds-step {
    flex-shrink: 0;
    color: #1769d7;
    font-size: 15px;
    font-weight: 800;
}

.ds-guide-item strong {
    display: block;
    margin-bottom: 6px;
    font-size: 12px;
}

.ds-guide-item p {
    margin: 0;
    color: #72839a;
    font-size: 11px;
    line-height: 1.6;
}

/* Right panel */
.ds-preview-side {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 0;
    padding: 55px clamp(24px, 3.5vw, 65px);
    background: linear-gradient(150deg, #15375f, #081c32);
    color: #fff;
    overflow: hidden;
}

.ds-preview-content {
    width: 100%;
    max-width: 730px;
}

.ds-preview-eyebrow {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #aad0ff;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 1.7px;
}

.ds-online-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #79b9ff;
}

.ds-preview-content h2 {
    margin: 26px 0 16px;
    font-size: clamp(29px, 2.8vw, 47px);
    font-weight: 800;
    line-height: 1.2;
    letter-spacing: -1.2px;
}

.ds-preview-content h2 span {
    display: block;
    color: #8fbfff;
}

.ds-preview-intro {
    max-width: 580px;
    margin: 0 0 40px;
    color: #c4d5e9;
    font-size: 14px;
    line-height: 1.85;
}

/* Workspace illustration built with HTML */
.ds-workspace {
    display: grid;
    grid-template-columns: 130px minmax(0, 1fr);
    overflow: hidden;
    min-height: 355px;
    border-radius: 13px;
    background: #fff;
    color: #152942;
    box-shadow: 0 24px 60px rgba(0, 0, 0, 0.24);
}

.ds-workspace-sidebar {
    min-width: 0;
    padding: 20px 9px;
    background: #11243e;
    color: #c4d6ed;
}

.ds-workspace-logo {
    display: grid;
    place-items: center;
    width: 37px;
    height: 37px;
    margin: 0 0 28px 8px;
    border-radius: 7px;
    background: #2370d9;
    color: #fff;
    font-size: 12px;
    font-weight: 800;
}

.ds-sidebar-item,
.ds-sidebar-active {
    padding: 12px 8px;
    border-radius: 5px;
    font-size: 10px;
    white-space: nowrap;
}

.ds-sidebar-active {
    background: #206bcf;
    color: #fff;
    font-weight: 800;
}

.ds-sidebar-separator {
    height: 1px;
    margin: 12px 8px;
    background: #344863;
}

.ds-workspace-main {
    min-width: 0;
    padding: 20px 17px;
}

.ds-workspace-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
}

.ds-workspace-search {
    padding: 9px;
    border: 1px solid #e5ebf3;
    border-radius: 6px;
    background: #f5f8fc;
    color: #91a1b4;
    font-size: 10px;
}

.ds-workspace-upload {
    flex-shrink: 0;
    padding: 9px 12px;
    border-radius: 5px;
    background: #1769d7;
    color: #fff;
    font-size: 10px;
    font-weight: 800;
}

.ds-workspace-title {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
    margin-top: 30px;
}

.ds-workspace-title h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 800;
}

.ds-workspace-title p {
    margin: 5px 0 0;
    color: #8a9bb0;
    font-size: 10px;
}

.ds-file-total {
    padding: 7px;
    border-radius: 5px;
    background: #edf4ff;
    color: #1769d7;
    font-size: 10px;
    font-weight: 800;
}

.ds-workspace-tabs {
    display: flex;
    gap: 19px;
    margin: 23px 0 15px;
    border-bottom: 1px solid #e6ecf4;
}

.ds-workspace-tabs span {
    padding-bottom: 10px;
    color: #8999ae;
    font-size: 10px;
}

.ds-workspace-tabs .ds-tab-selected {
    color: #1769d7;
    border-bottom: 2px solid #1769d7;
    font-weight: 800;
}

.ds-file-row {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 9px;
    padding: 11px 8px;
    border: 1px solid #e8edf4;
    border-radius: 7px;
}

.ds-file-icon {
    display: grid;
    place-items: center;
    flex-shrink: 0;
    width: 32px;
    height: 35px;
    border-radius: 4px;
    color: #fff;
    font-size: 15px;
    font-weight: 800;
}

.ds-word {
    background: #1e61bd;
}

.ds-excel {
    background: #147948;
}

.ds-file-details {
    display: flex;
    flex: 1;
    min-width: 0;
    flex-direction: column;
    gap: 4px;
}

.ds-file-details strong {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 11px;
}

.ds-file-details small {
    color: #8395aa;
    font-size: 9px;
}

.ds-version {
    color: #75879c;
    font-size: 10px;
}

.ds-workspace-footer {
    margin-top: 17px;
    color: #5e819e;
    font-size: 10px;
}

.ds-preview-benefits {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 32px;
    color: #c4d8f0;
    font-size: 12px;
}

/* Footer */
.ds-auth-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    min-height: 54px;
    padding: 15px 5%;
    border-top: 1px solid #e7edf5;
    background: #fff;
    color: #728198;
    font-size: 12px;
}

/* Responsive */
@media (max-width: 1150px) {
    .ds-auth-layout {
        grid-template-columns: 1fr;
    }

    .ds-preview-side {
        display: none;
    }

    .ds-form-content {
        max-width: 920px;
    }
}

@media (max-width: 700px) {
    .ds-auth-header {
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

    .ds-header-link {
        font-size: 12px;
    }

    .ds-form-side {
        padding: 40px 20px;
    }

    .ds-form-content h1 {
        font-size: 38px;
        letter-spacing: -1.2px;
    }

    .ds-form-card {
        padding: 28px 22px;
    }

    .ds-card-heading h2 {
        font-size: 20px;
    }

    .ds-guide-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }

    .ds-auth-footer {
        flex-direction: column;
        align-items: flex-start;
        gap: 7px;
    }
}
</style>
