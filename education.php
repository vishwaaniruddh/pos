<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include('top-header.php');
include('top-navbar.php');
?>
<!-- partial -->
<div class="container-fluid page-body-wrapper">
    <!-- partial:sidebar -->
    <?php include('navbar.php'); ?>

    <!-- partial:main-panel -->
    <div class="main-panel">
        <div class="content-wrapper">

<style>
/* ─── Education Page — Scoped Shadcn UI ─── */
.edu-page * { box-sizing: border-box; }
.edu-page {
    font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
    color: #0f172a;
}

/* ── Header Bar ── */
.edu-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 24px;
}
.edu-header-left h1 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 4px 0;
}
.edu-header-left p {
    font-size: 0.875rem;
    color: #64748b;
    margin: 0;
}
.edu-search-box {
    position: relative;
    width: 300px;
}
.edu-search-box input {
    width: 100%;
    padding: 9px 14px 9px 38px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.875rem;
    color: #0f172a;
    background: #ffffff;
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.edu-search-box input:focus {
    border-color: #94a3b8;
    box-shadow: 0 0 0 3px rgba(148,163,184,0.15);
}
.edu-search-box input::placeholder { color: #94a3b8; }
.edu-search-box .search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    pointer-events: none;
}

/* ── Category Tabs ── */
.edu-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 24px;
    flex-wrap: wrap;
}
.edu-tab {
    padding: 7px 18px;
    border-radius: 20px;
    font-size: 0.8125rem;
    font-weight: 500;
    cursor: pointer;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    color: #475569;
    transition: all 0.2s;
    user-select: none;
}
.edu-tab:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
}
.edu-tab.active {
    background: #0f172a;
    color: #ffffff;
    border-color: #0f172a;
}

/* ── Stats Row ── */
.edu-stats {
    display: flex;
    gap: 16px;
    margin-bottom: 28px;
    flex-wrap: wrap;
}
.edu-stat-card {
    flex: 1;
    min-width: 160px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 18px 20px;
    display: flex;
    align-items: center;
    gap: 14px;
}
.edu-stat-icon {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #475569;
    flex-shrink: 0;
}
.edu-stat-info .stat-value {
    font-size: 1.375rem;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.2;
}
.edu-stat-info .stat-label {
    font-size: 0.75rem;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 500;
}

/* ── Video Grid ── */
.edu-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}
.edu-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
    transition: box-shadow 0.25s, transform 0.25s;
    cursor: pointer;
}
.edu-card:hover {
    box-shadow: 0 8px 25px rgba(15,23,42,0.08);
    transform: translateY(-2px);
}
.edu-thumb-wrap {
    position: relative;
    width: 100%;
    aspect-ratio: 16/9;
    overflow: hidden;
    background: #f1f5f9;
}
.edu-thumb-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.35s;
}
.edu-card:hover .edu-thumb-wrap img {
    transform: scale(1.04);
}
.edu-play-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(15,23,42,0.35);
    opacity: 0;
    transition: opacity 0.25s;
}
.edu-card:hover .edu-play-overlay { opacity: 1; }
.edu-play-btn {
    width: 56px;
    height: 56px;
    background: rgba(255,255,255,0.95);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    transition: transform 0.2s;
}
.edu-card:hover .edu-play-btn { transform: scale(1.08); }
.edu-play-btn svg {
    width: 22px;
    height: 22px;
    fill: #0f172a;
    margin-left: 3px;
}
.edu-card-body {
    padding: 16px 18px;
}
.edu-card-body .card-number {
    display: inline-block;
    font-size: 0.6875rem;
    font-weight: 600;
    color: #64748b;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    padding: 2px 10px;
    border-radius: 12px;
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}
.edu-card-body h3 {
    font-size: 0.9375rem;
    font-weight: 600;
    color: #0f172a;
    margin: 0 0 6px 0;
    line-height: 1.4;
}
.edu-card-body p {
    font-size: 0.8125rem;
    color: #64748b;
    margin: 0;
    line-height: 1.5;
}

/* ── Category Badge ── */
.edu-card-body .cat-badge {
    display: inline-block;
    font-size: 0.6875rem;
    font-weight: 500;
    color: #475569;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 4px;
    padding: 2px 8px;
    margin-top: 10px;
}

/* ── Empty State ── */
.edu-empty {
    grid-column: 1 / -1;
    text-align: center;
    padding: 60px 20px;
    color: #94a3b8;
}
.edu-empty svg {
    width: 56px;
    height: 56px;
    margin-bottom: 16px;
    opacity: 0.5;
}
.edu-empty h3 {
    font-size: 1.125rem;
    font-weight: 600;
    color: #64748b;
    margin: 0 0 6px 0;
}
.edu-empty p {
    font-size: 0.875rem;
    color: #94a3b8;
    margin: 0;
}

/* ── Modal ── */
.edu-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15,23,42,0.85);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s;
}
.edu-modal-overlay.active {
    opacity: 1;
    visibility: visible;
}
.edu-modal {
    width: 90%;
    max-width: 960px;
    position: relative;
    animation: eduFadeIn 0.3s ease;
}
@keyframes eduFadeIn {
    from { opacity: 0; transform: scale(0.95) translateY(10px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
}
.edu-modal-close {
    position: absolute;
    top: -44px;
    right: 0;
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.2);
    color: #ffffff;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    transition: background 0.2s;
}
.edu-modal-close:hover { background: rgba(255,255,255,0.2); }
.edu-modal-title {
    color: #ffffff;
    font-size: 0.875rem;
    font-weight: 500;
    position: absolute;
    top: -40px;
    left: 0;
    opacity: 0.8;
}
.edu-modal-player {
    width: 100%;
    aspect-ratio: 16/9;
    background: #000;
    border-radius: 12px;
    overflow: hidden;
}
.edu-modal-player iframe {
    width: 100%;
    height: 100%;
    border: none;
}

/* ── Responsive ── */
@media (max-width: 768px) {
    .edu-header { flex-direction: column; align-items: flex-start; }
    .edu-search-box { width: 100%; }
    .edu-grid { grid-template-columns: 1fr; }
    .edu-stat-card { min-width: 140px; }
}
</style>

<div class="edu-page">

    <!-- Header -->
    <div class="edu-header">
        <div class="edu-header-left">
            <h1>📚 Video Tutorials</h1>
            <p>Step-by-step guides to help you master the POS system</p>
        </div>
        <div class="edu-search-box">
            <span class="search-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </span>
            <input type="text" id="eduSearch" placeholder="Search tutorials...">
        </div>
    </div>

    <!-- Category Tabs -->
    <div class="edu-tabs" id="eduTabs">
        <div class="edu-tab active" data-cat="all">All Tutorials</div>
        <div class="edu-tab" data-cat="login">Login & Access</div>
        <div class="edu-tab" data-cat="website">Website</div>
        <div class="edu-tab" data-cat="products">Products</div>
        <div class="edu-tab" data-cat="coupons">Coupons</div>
    </div>

    <!-- Stats Row -->
    <div class="edu-stats">
        <div class="edu-stat-card">
            <div class="edu-stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
            </div>
            <div class="edu-stat-info">
                <div class="stat-value" id="totalVideos">7</div>
                <div class="stat-label">Total Videos</div>
            </div>
        </div>
        <div class="edu-stat-card">
            <div class="edu-stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            </div>
            <div class="edu-stat-info">
                <div class="stat-value" id="totalCategories">4</div>
                <div class="stat-label">Categories</div>
            </div>
        </div>
        <div class="edu-stat-card">
            <div class="edu-stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            </div>
            <div class="edu-stat-info">
                <div class="stat-value">Free</div>
                <div class="stat-label">Access Level</div>
            </div>
        </div>
    </div>

    <!-- Video Grid -->
    <div class="edu-grid" id="eduGrid">
        <!-- Populated by JS -->
    </div>

</div>

<!-- Video Player Modal -->
<div class="edu-modal-overlay" id="eduModalOverlay">
    <div class="edu-modal">
        <div class="edu-modal-title" id="eduModalTitle"></div>
        <button class="edu-modal-close" id="eduModalClose">&times;</button>
        <div class="edu-modal-player">
            <iframe id="eduPlayerFrame" src="" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ── Video Data ──
    const videos = [
        { id: 'WUV8K7itFaw', title: 'Login System Tutorial',           category: 'login',    desc: 'Learn how to log in, manage sessions, and reset passwords in the admin panel.' },
        { id: 'RKpHPUt9ANI', title: 'Edit Website Content',            category: 'website',  desc: 'Walkthrough on editing banners, text blocks, and homepage sections on the storefront.' },
        { id: '2n8mKRp4_Y4', title: 'Add Product Tutorial',            category: 'products', desc: 'Step-by-step guide to adding new products with images, pricing, and categories.' },
        { id: 'hxLsDnoZIqA', title: 'Edit Product Tutorial',           category: 'products', desc: 'How to update existing product details, prices, stock, and images.' },
        { id: 'DWGhhMl1Pzk', title: 'Add Coupon',                      category: 'coupons',  desc: 'Create discount coupons with percentage or flat-rate values for customers.' },
        { id: 'IANLwwLDkB0', title: 'Coupon Usage Restriction',        category: 'coupons',  desc: 'Set restrictions like minimum spend, specific products, or excluded categories.' },
        { id: 'yXzQct-nMio', title: 'Coupon Usage Limits Section',     category: 'coupons',  desc: 'Configure per-coupon and per-user usage limits to control discount distribution.' },
    ];

    const grid       = document.getElementById('eduGrid');
    const searchBox  = document.getElementById('eduSearch');
    const tabs       = document.getElementById('eduTabs');
    const overlay    = document.getElementById('eduModalOverlay');
    const closeBtn   = document.getElementById('eduModalClose');
    const playerFrame= document.getElementById('eduPlayerFrame');
    const modalTitle = document.getElementById('eduModalTitle');
    const totalEl    = document.getElementById('totalVideos');

    let activeCategory = 'all';

    function renderVideos() {
        const query = searchBox.value.toLowerCase().trim();
        const filtered = videos.filter(v => {
            const matchCat = (activeCategory === 'all' || v.category === activeCategory);
            const matchQ   = !query || v.title.toLowerCase().includes(query) || v.desc.toLowerCase().includes(query);
            return matchCat && matchQ;
        });

        totalEl.textContent = filtered.length;
        grid.innerHTML = '';

        if (filtered.length === 0) {
            grid.innerHTML = `
                <div class="edu-empty">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <h3>No tutorials found</h3>
                    <p>Try adjusting your search or category filter.</p>
                </div>`;
            return;
        }

        filtered.forEach((video, idx) => {
            const thumb   = 'https://img.youtube.com/vi/' + video.id + '/maxresdefault.jpg';
            const fallback= 'https://img.youtube.com/vi/' + video.id + '/hqdefault.jpg';
            const catLabels = { login: 'Login & Access', website: 'Website', products: 'Products', coupons: 'Coupons' };

            const card = document.createElement('div');
            card.className = 'edu-card';
            card.setAttribute('data-video-id', video.id);
            card.innerHTML = `
                <div class="edu-thumb-wrap">
                    <img src="${thumb}" alt="${video.title}" onerror="this.src='${fallback}'">
                    <div class="edu-play-overlay">
                        <div class="edu-play-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                        </div>
                    </div>
                </div>
                <div class="edu-card-body">
                    <span class="card-number">Lesson ${idx + 1}</span>
                    <h3>${video.title}</h3>
                    <p>${video.desc}</p>
                    <span class="cat-badge">${catLabels[video.category] || video.category}</span>
                </div>`;

            card.addEventListener('click', () => openModal(video));
            grid.appendChild(card);
        });
    }

    // ── Modal ──
    function openModal(video) {
        playerFrame.src = 'https://www.youtube.com/embed/' + video.id + '?autoplay=1';
        modalTitle.textContent = video.title;
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    function closeModal() {
        overlay.classList.remove('active');
        playerFrame.src = '';
        document.body.style.overflow = '';
    }

    closeBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', e => { if (e.target === overlay) closeModal(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && overlay.classList.contains('active')) closeModal(); });

    // ── Tabs ──
    tabs.addEventListener('click', function(e) {
        const tab = e.target.closest('.edu-tab');
        if (!tab) return;
        tabs.querySelectorAll('.edu-tab').forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        activeCategory = tab.dataset.cat;
        renderVideos();
    });

    // ── Search ──
    searchBox.addEventListener('input', renderVideos);

    // ── Init ──
    renderVideos();
});
</script>

        </div>
        <!-- content-wrapper ends -->
    </div>
    <!-- main-panel ends -->
</div>
<!-- page-body-wrapper ends -->