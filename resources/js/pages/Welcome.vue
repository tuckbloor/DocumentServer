<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

defineOptions({ layout: null });

const page = usePage();
const isAuthenticated = computed(() => {
    const auth = page.props.auth as { user?: unknown } | undefined;
    return Boolean(auth?.user);
});

const previewDocuments = [
    { name: 'Network Device Inventory', ext: 'xlsx', category: 'Infrastructure', version: 'v3' },
    { name: 'Acceptable Use Policy', ext: 'docx', category: 'IT Policies', version: 'v2' },
    { name: 'Project RAID Log', ext: 'xlsx', category: 'IT Projects', version: 'v4' },
    { name: 'Backup Procedure', ext: 'docx', category: 'Operations', version: 'v2' },
];
</script>

<template>
    <Head title="Document Server" />

    <div class="landing">
        <header class="site-header">
            <div class="header-inner">
                <span class="brand">Document <span>Server</span></span>

                <nav class="header-actions" aria-label="Account navigation">
                    <Link v-if="isAuthenticated" href="/dashboard" class="header-link header-primary">
                        Dashboard <span aria-hidden="true">→</span>
                    </Link>
                    <template v-else>
                        <Link href="/login" class="header-link header-secondary">Login</Link>
                        <Link href="/register" class="header-link header-primary">Register <span aria-hidden="true">→</span></Link>
                    </template>
                </nav>
            </div>
        </header>

        <main>
            <section class="hero" aria-labelledby="hero-title">
                <div class="hero-inner">
                    <div class="hero-copy">
                        <div class="eyebrow"><span class="eyebrow-line"></span> SECURE DOCUMENT MANAGEMENT</div>
                        <h1 id="hero-title">Your documents.<br /><span>One secure place.</span></h1>
                        <p class="hero-intro">
                            Organise, share and manage documents with confidence.
                            Keep a clear version history and work with the desktop
                            applications your team already uses.
                        </p>
                        <div class="hero-benefits" aria-label="Document Server benefits">
                            <div><span class="benefit-tick" aria-hidden="true">✓</span> Private document storage</div>
                            <div><span class="benefit-tick" aria-hidden="true">✓</span> Version history</div>
                            <div><span class="benefit-tick" aria-hidden="true">✓</span> Controlled access and sharing</div>
                        </div>
                        <div class="hero-rule" aria-hidden="true"></div>
                        <p class="hero-footnote">YOUR TEAM. YOUR DOCUMENTS. ALL IN ONE PLACE.</p>
                    </div>

                    <div class="preview-wrap" aria-label="Illustration of the Document Server dashboard">
                        <div class="preview-glow" aria-hidden="true"></div>
                        <div class="app-window" aria-hidden="true">
                            <div class="browser-bar">
                                <div class="browser-dots"><i></i><i></i><i></i></div>
                                <span>Document Server</span>
                                <span class="browser-lock">● Secure workspace</span>
                            </div>
                            <div class="app-body">
                                <aside class="preview-sidebar">
                                    <div class="preview-logo"><span class="logo-square">D</span><strong>Document Server</strong></div>
                                    <div class="side-item active"><span>▦</span> All Documents</div>
                                    <div class="side-item"><span>▤</span> My Documents</div>
                                    <div class="side-item"><span>⇄</span> Shared with me</div>
                                    <div class="side-item"><span>◷</span> Recent</div>
                                    <div class="side-item"><span>☆</span> Favourites</div>
                                    <div class="side-separator"></div>
                                    <div class="side-item"><span>▱</span> Categories</div>
                                    <div class="side-item"><span>▥</span> Recycle Bin</div>
                                </aside>
                                <div class="preview-content">
                                    <div class="preview-toolbar">
                                        <div>
                                            <strong>Good morning, Chris</strong>
                                            <small>Your document workspace</small>
                                        </div>
                                        <span class="upload-button">↑ Upload</span>
                                    </div>
                                    <div class="preview-stats">
                                        <div><b>100</b><small>Documents</small></div>
                                        <div><b>52</b><small>Word files</small></div>
                                        <div><b>48</b><small>Excel files</small></div>
                                    </div>
                                    <div class="preview-list-heading">
                                        <strong>Recent documents</strong><span>Grid view ▦</span>
                                    </div>
                                    <div class="preview-doc-grid">
                                        <div v-for="doc in previewDocuments" :key="doc.name" class="preview-doc-card">
                                            <div class="doc-top">
                                                <span class="doc-symbol" :class="doc.ext === 'xlsx' ? 'excel' : 'word'">{{ doc.ext === 'xlsx' ? 'X' : 'W' }}</span>
                                                <div class="doc-name"><strong>{{ doc.name }}</strong><small>{{ doc.ext }}</small></div>
                                            </div>
                                            <span class="doc-category">{{ doc.category }}</span>
                                            <div class="doc-bottom"><span class="mock-open">Open</span><span>{{ doc.version }}</span></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="preview-shadow" aria-hidden="true"></div>
                    </div>
                </div>
            </section>

            <section class="feature-section" aria-label="Document Server features">
                <div class="feature-strip">
                    <div class="feature">
                        <span class="feature-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none"><path d="m12 2 8 4v6c0 5.2-3.3 8.5-8 10-4.7-1.5-8-4.8-8-10V6l8-4Z"/><path d="m9 12 2 2 4-4"/></svg>
                        </span>
                        <div><strong>Secure access</strong><span>Permissions built around your team</span></div>
                    </div>
                    <div class="feature">
                        <span class="feature-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none"><path d="M3 12a9 9 0 1 0 3-6.7M3 4v5h5"/><path d="M12 7v5l3.5 2"/></svg>
                        </span>
                        <div><strong>Version history</strong><span>Keep track of document changes</span></div>
                    </div>
                    <div class="feature">
                        <span class="feature-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none"><path d="M16 20v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 10a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM22 20v-2a4 4 0 0 0-3-3.9M16 2.1a4 4 0 0 1 0 7.8"/></svg>
                        </span>
                        <div><strong>Easy sharing</strong><span>Share files with the right people</span></div>
                    </div>
                    <div class="feature">
                        <span class="feature-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none"><rect x="2" y="3" width="20" height="15" rx="2"/><path d="M8 22h8m-4-4v4"/></svg>
                        </span>
                        <div><strong>Desktop editing</strong><span>Use your installed Office applications</span></div>
                    </div>
                </div>
            </section>
        </main>
    </div>
</template>

<style scoped>
* { box-sizing: border-box; }
.landing { min-height: 100vh; background: #f7faff; color: #102440; font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
.site-header { height: 78px; background: linear-gradient(110deg, #0b2342 0%, #102d54 100%); border-bottom: 1px solid #274466; box-shadow: 0 4px 18px #071a3120; }
.header-inner { max-width: 1400px; height: 100%; padding: 0 32px; margin: auto; display: flex; align-items: center; justify-content: space-between; gap: 20px; }
.brand { color: #fff; font-size: 23px; font-weight: 800; letter-spacing: -0.7px; white-space: nowrap; }
.brand > span { color: #8cbaff; }
.header-actions { display: flex; align-items: center; gap: 12px; }
.header-link { display: inline-flex; align-items: center; justify-content: center; gap: 12px; min-height: 43px; padding: 0 23px; border-radius: 8px; font-size: 14px; font-weight: 700; text-decoration: none; transition: background .15s, transform .15s; }
.header-link:hover { transform: translateY(-1px); }
.header-secondary { border: 1px solid #92a7bf; color: #fff; background: #ffffff08; }
.header-secondary:hover { background: #ffffff1b; }
.header-primary { border: 1px solid #126be4; color: white; background: #0968df; box-shadow: 0 5px 15px #00162e39; }
.header-primary:hover { background: #0755b9; }
.header-link:focus-visible { outline: 3px solid #a9d2ff; outline-offset: 3px; }
.hero { position: relative; isolation: isolate; overflow: hidden; background: radial-gradient(ellipse 780px 430px at 79% 44%, #e3efff 0%, transparent 87%), linear-gradient(110deg, #fff 10%, #f5f9ff 100%); }
.hero::before { content: ''; position: absolute; z-index: -1; right: -170px; top: -170px; width: 640px; height: 640px; border: 75px solid #e9f2ff; border-radius: 50%; opacity: .7; }
.hero-inner { max-width: 1400px; min-height: 615px; padding: 66px 32px 80px; margin: auto; display: grid; grid-template-columns: minmax(0, .88fr) minmax(0, 1.12fr); align-items: center; gap: 44px; }
.hero-copy { position: relative; z-index: 2; }
.eyebrow { display: flex; gap: 11px; align-items: center; color: #1556a6; font-size: 12px; font-weight: 800; letter-spacing: 1.8px; }
.eyebrow-line { width: 27px; height: 3px; border-radius: 4px; background: #1269e2; }
.hero h1 { margin: 25px 0 22px; color: #0c2243; font-size: clamp(42px, 4.2vw, 65px); line-height: 1.10; font-weight: 820; letter-spacing: -2.7px; }
.hero h1 span { color: #0868dc; }
.hero-intro { max-width: 535px; margin: 0; color: #435773; font-size: 17px; line-height: 1.8; }
.hero-benefits { display: grid; gap: 12px; margin-top: 28px; color: #273d58; font-size: 14px; font-weight: 600; }
.hero-benefits > div { display: flex; gap: 12px; align-items: center; }
.benefit-tick { display: inline-grid; place-items: center; width: 23px; height: 23px; border: 1px solid #bfd9fb; border-radius: 50%; background: #e9f3ff; color: #0d62c5; font-weight: 900; flex: none; }
.hero-rule { margin: 36px 0 18px; width: 125px; height: 1px; background: #c9d8e9; }
.hero-footnote { margin: 0; font-size: 11px; font-weight: 800; letter-spacing: 1.5px; color: #8394ab; }
.preview-wrap { min-width: 0; position: relative; padding: 20px 0; }
.preview-glow { position: absolute; width: 82%; height: 92%; left: 8%; top: 0%; border-radius: 50%; background: #c3dbff; filter: blur(56px); opacity: .38; }
.app-window { position: relative; background: white; border: 1px solid #c7d7eb; border-radius: 15px; overflow: hidden; box-shadow: 0 36px 65px #193a6630, 0 8px 18px #163e6e10; transform: perspective(1200px) rotateY(-5deg) rotateX(2deg); transform-origin: center; }
.browser-bar { height: 35px; background: #f0f4fa; display: flex; align-items: center; justify-content: space-between; padding: 0 13px; color: #6b7f99; font-size: 9px; font-weight: 700; border-bottom: 1px solid #dce6f2; }
.browser-dots { display: flex; gap: 4px; }
.browser-dots i { display: block; width: 7px; height: 7px; background: #9fb2c9; border-radius: 50%; }
.browser-dots i:first-child { background: #dba3a1; }
.browser-lock { color: #6586ab; font-weight: 600; }
.app-body { display: grid; grid-template-columns: 160px minmax(0, 1fr); min-height: 384px; }
.preview-sidebar { background: #0e2748; color: #b8cae2; padding: 14px 10px; }
.preview-logo { display: flex; align-items: center; gap: 7px; color: #fff; padding: 3px 3px 18px; font-size: 11px; white-space: nowrap; }
.logo-square { width: 25px; height: 25px; border: 1px solid #65a5ff; display: grid; place-items: center; background: #0b66d9; border-radius: 5px; font-size: 15px; }
.side-item { font-size: 10px; display: flex; gap: 9px; align-items: center; padding: 10px 9px; border-radius: 5px; white-space: nowrap; }
.side-item span { font-size: 13px; width: 14px; text-align: center; }
.side-item.active { color: #fff; background: #0867db; font-weight: 700; }
.side-separator { height: 1px; margin: 9px 2px; background: #ffffff22; }
.preview-content { min-width: 0; background: #fbfdff; padding: 20px 19px; }
.preview-toolbar { display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; }
.preview-toolbar strong { display: block; font-size: 14px; color: #182e4d; }
.preview-toolbar small { display: block; font-size: 10px; color: #7688a0; margin-top: 4px; }
.upload-button { flex: none; background: #0969e0; color: #fff; font-size: 10px; padding: 8px 10px; border-radius: 5px; font-weight: 700; }
.preview-stats { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 9px; margin-top: 19px; }
.preview-stats > div { background: white; border: 1px solid #e1eafa; border-radius: 7px; padding: 11px 12px; }
.preview-stats b { display: block; font-size: 18px; color: #0d4eaa; }
.preview-stats small { display: block; font-size: 9px; color: #8496ab; margin-top: 2px; }
.preview-list-heading { display: flex; justify-content: space-between; gap: 10px; margin: 22px 0 10px; align-items: center; }
.preview-list-heading strong { font-size: 11px; }
.preview-list-heading span { font-size: 9px; color: #7189a4; }
.preview-doc-grid { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 9px; }
.preview-doc-card { min-width: 0; padding: 11px; background: white; border: 1px solid #e0e9f5; border-radius: 8px; }
.doc-top { display: flex; align-items: flex-start; gap: 7px; }
.doc-symbol { flex: none; width: 25px; height: 29px; display: grid; place-items: center; border-radius: 4px; color: white; font-size: 15px; font-weight: 900; }
.doc-symbol.excel { background: #168457; }
.doc-symbol.word { background: #2269c7; }
.doc-name { min-width: 0; }
.doc-name strong { display: block; font-size: 9px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 137px; }
.doc-name small { color: #8c9fb4; font-size: 9px; }
.doc-category { display: block; color: #899bb0; font-size: 9px; margin: 11px 0 9px; }
.doc-bottom { display: flex; align-items: center; justify-content: space-between; color: #8ea0b4; font-size: 9px; }
.mock-open { background: #076ae5; color: #fff; border-radius: 4px; font-size: 9px; font-weight: 700; padding: 4px 11px; }
.preview-shadow { height: 15px; width: 80%; margin: 8px auto 0; background: #183e6f; filter: blur(20px); opacity: .16; border-radius: 50%; }
.feature-section { padding: 0 32px 40px; margin-top: -30px; position: relative; z-index: 3; }
.feature-strip { max-width: 1336px; margin: auto; padding: 26px 28px; background: white; border: 1px solid #dce7f5; border-radius: 14px; box-shadow: 0 10px 30px #1839590c; display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); }
.feature { display: flex; align-items: center; gap: 13px; min-width: 0; padding: 5px 18px; border-right: 1px solid #e4ebf4; }
.feature:first-child { padding-left: 0; }
.feature:last-child { border-right: 0; padding-right: 0; }
.feature-icon { flex: none; width: 43px; height: 43px; border-radius: 10px; display: grid; place-items: center; background: #eaf3ff; color: #0c62ce; }
.feature-icon svg { width: 22px; height: 22px; stroke: currentColor; stroke-width: 1.9; stroke-linecap: round; stroke-linejoin: round; }
.feature strong { display: block; font-size: 13px; color: #19304f; line-height: 1.2; }
.feature div > span { display: block; margin-top: 5px; font-size: 11px; line-height: 1.45; color: #778aa3; }
@media (max-width: 1120px) {
    .hero-inner { grid-template-columns: .95fr 1.05fr; gap: 20px; padding-top: 55px; }
    .hero h1 { letter-spacing: -1.8px; }
    .app-body { grid-template-columns: 124px minmax(0, 1fr); }
    .preview-sidebar { padding: 12px 6px; }
    .preview-logo { font-size: 9px; }
    .side-item { font-size: 9px; gap: 5px; }
    .preview-content { padding: 15px 12px; }
    .feature-strip { padding: 22px 12px; }
    .feature { padding: 5px 10px; }
}
@media (max-width: 860px) {
    .hero-inner { grid-template-columns: 1fr; max-width: 720px; padding: 65px 28px 75px; }
    .hero-copy { text-align: center; }
    .eyebrow { justify-content: center; }
    .hero-intro { margin: auto; }
    .hero-benefits { display: inline-grid; text-align: left; }
    .hero-rule { margin-left: auto; margin-right: auto; }
    .preview-wrap { margin-top: 10px; }
    .app-window { transform: none; }
    .feature-section { margin-top: -24px; }
    .feature-strip { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 20px 0; }
    .feature:nth-child(2) { border-right: 0; }
}
@media (max-width: 520px) {
    .site-header { height: 70px; }
    .header-inner { padding: 0 17px; }
    .brand { font-size: 18px; }
    .header-actions { gap: 6px; }
    .header-link { padding: 0 12px; min-height: 37px; font-size: 12px; }
    .hero-inner { padding: 54px 19px 66px; }
    .hero h1 { font-size: clamp(36px, 9.6vw, 50px); letter-spacing: -1.6px; }
    .hero-intro { font-size: 15px; }
    .eyebrow { font-size: 10px; letter-spacing: 1px; }
    .app-body { grid-template-columns: 92px minmax(0,1fr); min-height: 300px; }
    .preview-sidebar { padding: 10px 4px; }
    .preview-logo { font-size: 0; padding-bottom: 13px; }
    .logo-square { width: 22px; height: 22px; font-size: 12px; }
    .side-item { font-size: 7px; padding: 7px 4px; gap: 2px; }
    .side-item span { font-size: 10px; width: 12px; }
    .preview-content { padding: 13px 8px; }
    .preview-toolbar strong { font-size: 11px; }
    .preview-toolbar small { font-size: 8px; }
    .upload-button { font-size: 8px; padding: 6px; }
    .preview-stats { gap: 5px; margin-top: 11px; }
    .preview-stats > div { padding: 7px; }
    .preview-stats b { font-size: 14px; }
    .preview-stats small { font-size: 7px; }
    .preview-list-heading { margin: 14px 0 7px; }
    .preview-doc-grid { gap: 5px; }
    .preview-doc-card { padding: 6px; }
    .doc-symbol { width: 18px; height: 21px; font-size: 11px; }
    .doc-name strong { font-size: 7px; max-width: 75px; }
    .doc-name small, .doc-category, .doc-bottom, .mock-open { font-size: 7px; }
    .doc-category { margin: 7px 0; }
    .mock-open { padding: 3px 6px; }
    .feature-section { padding: 0 14px 25px; }
    .feature-strip { padding: 16px 10px; }
    .feature { padding: 5px 8px; gap: 8px; }
    .feature-icon { width: 32px; height: 32px; }
    .feature-icon svg { width: 17px; height: 17px; }
    .feature strong { font-size: 11px; }
    .feature div > span { font-size: 9px; }
}
</style>
