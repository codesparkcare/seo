/**
 * LocalRank Pro - Frontend Application Engine
 * Handles Geo-grid map rendering, AI review replies, live website crawler,
 * schema generation, social scheduling, and settings synchronization.
 */

// State Management
const AppState = {
    profile: null,
    currentTab: 'overview',
    map: null,
    markersLayer: null,
    currentKeyword: 'local seo services',
    posts: []
};

// UI Helper: Toast Notifications
function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    if (!container) return;
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';
    toast.innerHTML = `<i class="fas ${icon}"></i> <span>${message}</span>`;
    container.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

// Navigation & Tab Switching
function switchTab(tabId) {
    AppState.currentTab = tabId;
    
    // Update nav links
    document.querySelectorAll('.nav-item').forEach(el => {
        el.classList.toggle('active', el.dataset.tab === tabId);
    });

    // Update tab contents
    document.querySelectorAll('.tab-content').forEach(el => {
        el.classList.toggle('active', el.id === `tab-${tabId}`);
    });

    // Lazy load tab specific components
    if (tabId === 'geo-grid') {
        setTimeout(initOrRefreshMap, 200);
    } else if (tabId === 'reviews') {
        loadReviews();
    } else if (tabId === 'gmb-updates') {
        loadGmbUpdates();
        updateGmbLivePreview();
    } else if (tabId === 'social') {
        loadPosts();
        loadCitations();
    } else if (tabId === 'website-seo') {
        generateLocalSchema();
    } else if (tabId === 'settings') {
        loadSettings();
    }
}

// 1. OVERVIEW / DASHBOARD INITIALIZATION
async function loadOverview() {
    try {
        const res = await fetch('api.php?action=get_overview');
        const data = await res.json();
        if (!data.success) return;

        AppState.profile = data.profile;

        // Populate KPIs
        document.getElementById('kpiDominance').textContent = data.top3_rate + '%';
        document.getElementById('kpiAvgRank').textContent = '#' + (data.avg_rank || '1.0');
        document.getElementById('kpiRating').textContent = (data.avg_rating || '5.0') + ' ★';
        document.getElementById('kpiReviewsCount').textContent = `${data.total_reviews} reviews (${data.pending_replies} pending)`;
        if (document.getElementById('kpiCitationHealth')) document.getElementById('kpiCitationHealth').textContent = data.citation_health + '%';
        if (document.getElementById('kpiSiteHealth')) document.getElementById('kpiSiteHealth').textContent = data.site_health + '/100';
        if (document.getElementById('kpiOverallScore')) document.getElementById('kpiOverallScore').textContent = data.composite_score + '%';

        // Profile quick display
        if (data.profile) {
            document.querySelectorAll('.biz-name-display').forEach(el => el.textContent = data.profile.name);
            document.querySelectorAll('.biz-address-display').forEach(el => el.textContent = `${data.profile.address}, ${data.profile.city}`);
            document.querySelectorAll('.biz-phone-display').forEach(el => el.textContent = data.profile.phone);
            document.querySelectorAll('.biz-site-display').forEach(el => el.textContent = data.profile.website);
        }

        // Recent rank snapshot table
        const ranksTbody = document.getElementById('recentRanksBody');
        if (ranksTbody && data.recent_ranks) {
            ranksTbody.innerHTML = data.recent_ranks.map(r => `
                <tr>
                    <td><strong>${r.keyword}</strong></td>
                    <td>
                        <span class="status-pill ${r.rank_position <= 3 ? 'success' : (r.rank_position <= 9 ? 'warning' : 'danger')}">
                            Rank #${r.rank_position}
                        </span>
                    </td>
                    <td>${r.competitor_name}</td>
                    <td class="text-muted">${r.tracked_at.substring(0, 16)}</td>
                </tr>
            `).join('');
        }

        // Upcoming posts list
        const postsList = document.getElementById('upcomingPostsList');
        if (postsList && data.upcoming_posts) {
            if (data.upcoming_posts.length === 0) {
                postsList.innerHTML = `<p class="text-muted" style="padding: 12px 0;">No posts currently scheduled. Use the Social Syndicator to create one.</p>`;
            } else {
                postsList.innerHTML = data.upcoming_posts.map(p => {
                    let platforms = [];
                    try {
                        platforms = Array.isArray(p.platforms) ? p.platforms : JSON.parse(p.platforms || '[]');
                    } catch (e) {
                        platforms = typeof p.platforms === 'string' ? p.platforms.split(',') : [];
                    }
                    const wpBtn = p.wp_link ? `<a href="${p.wp_link}" target="_blank" class="btn btn-outline btn-sm" style="font-size: 0.72rem; padding: 2px 8px; color: #60a5fa;"><i class="fab fa-wordpress"></i> View Live</a>` : '';
                    return `
                        <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 12px; margin-bottom: 10px;">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 6px;">
                                <strong style="font-size: 0.9rem;">${escapeHtml(p.title || 'Local Update')}</strong>
                                <span class="status-pill ${p.status === 'published' ? 'success' : 'primary'}">${p.status || 'scheduled'}</span>
                            </div>
                            <p style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 8px;">${escapeHtml((p.content || '').substring(0, 110))}...</p>
                            <div style="display:flex; justify-content:space-between; align-items:center; font-size: 0.75rem;">
                                <div style="display:flex; gap: 8px; color: var(--secondary);">
                                    ${platforms.map(plat => `<span style="text-transform: uppercase;"><i class="fab fa-${plat === 'gmb' ? 'google' : (plat === 'wordpress' ? 'wordpress' : plat)}"></i> ${plat}</span>`).join(' • ')}
                                </div>
                                ${wpBtn}
                            </div>
                        </div>
                    `;
                }).join('');
            }
        }
    } catch (e) {
        console.error('Error loading overview:', e);
    }
}

// 2. GEO-GRID RANK TRACKER (GOOGLE MAP TOP 3)
function initOrRefreshMap() {
    if (!AppState.map) {
        const centerLat = AppState.profile ? parseFloat(AppState.profile.latitude) : 28.6315;
        const centerLng = AppState.profile ? parseFloat(AppState.profile.longitude) : 77.2167;

        AppState.map = L.map('geoMap').setView([centerLat, centerLng], 14);

        // Authentic Google Maps Roadmap Tiles (High-resolution, No Carto Watermark)
        L.tileLayer('https://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
            attribution: '&copy; Google Maps',
            subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
            maxZoom: 20
        }).addTo(AppState.map);

        AppState.markersLayer = L.layerGroup().addTo(AppState.map);
    }
    
    loadGeoGrid();
}

async function loadGeoGrid() {
    const keyword = document.getElementById('gridKeywordSelect')?.value || AppState.currentKeyword;
    const gridSize = parseInt(document.getElementById('gridSizeSelect')?.value || 3);
    const radius = parseFloat(document.getElementById('gridRadiusSelect')?.value || 3.0);

    try {
        const res = await fetch(`api.php?action=get_geo_grid&keyword=${encodeURIComponent(keyword)}`);
        const data = await res.json();
        
        if (data.success && data.pins && data.pins.length > 0) {
            renderGridMarkers(data.center, data.pins);
        } else {
            // Auto run if no snapshot yet
            runGeoGridScan();
        }
    } catch (e) {
        console.error('Error loading geo-grid:', e);
    }
}

async function runGeoGridScan() {
    const keyword = document.getElementById('gridKeywordSelect')?.value || 'local seo services';
    const gridSize = parseInt(document.getElementById('gridSizeSelect')?.value || 3);
    const radius = parseFloat(document.getElementById('gridRadiusSelect')?.value || 3.0);

    showToast(`Scanning Google Maps for "${keyword}"...`, 'info');

    try {
        const res = await fetch('api.php?action=run_geo_grid', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ keyword, grid_size: gridSize, radius_km: radius })
        });
        const data = await res.json();
        
        if (data.success) {
            renderGridMarkers(data.center, data.pins);
            showToast(`Geo-Grid completed! Top 3 Dominance: ${data.top3_percentage}%`, 'success');
            loadOverview();
        } else {
            showToast(data.error || 'Failed to run scan', 'error');
        }
    } catch (e) {
        showToast('Error connecting to ranking engine', 'error');
    }
}

function renderGridMarkers(center, pins) {
    if (!AppState.map || !AppState.markersLayer) return;
    AppState.markersLayer.clearLayers();

    // Auto-fit map bounds dynamically for 2km up to 60km
    if (pins && pins.length > 0) {
        const bounds = L.latLngBounds([[center.lat, center.lng]]);
        pins.forEach(p => bounds.extend([p.lat, p.lng]));
        AppState.map.fitBounds(bounds, { padding: [35, 35] });
    } else {
        AppState.map.setView([center.lat, center.lng], 13);
    }

    // Center Office Marker
    const officeIcon = L.divIcon({
        className: 'custom-rank-pin pin-center',
        html: '<i class="fas fa-building" style="font-size: 14px;"></i>',
        iconSize: [36, 36],
        iconAnchor: [18, 18]
    });
    L.marker([center.lat, center.lng], { icon: officeIcon })
        .addTo(AppState.markersLayer)
        .bindPopup(`<strong>${center.business_name}</strong><br>Primary Verified Office Location`);

    let top3Count = 0;

    // Add Grid Rank Pins
    pins.forEach(p => {
        const isTop3 = p.rank <= 3;
        if (isTop3) top3Count++;
        const pinClass = isTop3 ? 'pin-top3' : (p.rank <= 9 ? 'pin-mid' : 'pin-low');

        const pinIcon = L.divIcon({
            className: `custom-rank-pin ${pinClass}`,
            html: `<span>#${p.rank}</span>`,
            iconSize: [30, 30],
            iconAnchor: [15, 15]
        });

        const popupContent = `
            <div style="font-family: sans-serif; min-width: 170px;">
                <h4 style="margin: 0 0 6px; font-size: 14px;">Map Pack Rank: <strong>#${p.rank}</strong></h4>
                <p style="margin: 0; font-size: 12px; color: #475569;">
                    Leader: <strong>${p.competitor}</strong>
                </p>
                <div style="margin-top: 8px; font-size: 11px; padding: 4px 6px; border-radius: 4px; background: ${isTop3 ? '#D1FAE5; color: #065F46' : '#FEF3C7; color: #92400E'};">
                    ${isTop3 ? '★ In Google Map 3-Pack' : 'Needs Optimization Boost'}
                </div>
            </div>
        `;

        L.marker([p.lat, p.lng], { icon: pinIcon })
            .addTo(AppState.markersLayer)
            .bindPopup(popupContent);
    });

    // Update Grid summary stats in UI
    const dominancePct = Math.round((top3Count / pins.length) * 100);
    const statEl = document.getElementById('gridTop3Rate');
    if (statEl) statEl.textContent = `${dominancePct}% (${top3Count}/${pins.length} Nodes in Top 3)`;
}

// 3. REVIEWS & AI AUTO-RESPONDER WITH FULL PAGINATION & SEARCH
const ReviewState = {
    allReviews: [],
    filteredReviews: [],
    currentPage: 1,
    pageSize: 5,
    googleTotalRatings: 41,
    googleReviewsUrl: 'https://search.google.com/local/reviews?placeid=ChIJDR4_dxUTBDsReG0F-jMX19g'
};

async function loadReviews() {
    try {
        const res = await fetch('api.php?action=get_reviews');
        const data = await res.json();
        if (!data.success) return;

        ReviewState.allReviews = data.reviews || [];
        ReviewState.googleTotalRatings = data.google_total_ratings || 41;
        if (data.google_reviews_url) {
            ReviewState.googleReviewsUrl = data.google_reviews_url;
            const viewAllBtn = document.getElementById('viewAllGoogleReviewsBtn');
            if (viewAllBtn) {
                viewAllBtn.href = data.google_reviews_url;
                viewAllBtn.innerHTML = `<i class="fab fa-google"></i> View All ${ReviewState.googleTotalRatings} on Google <i class="fas fa-external-link-alt" style="font-size:10px; margin-left:2px;"></i>`;
            }
        }

        applyReviewFiltersAndRender();

        // Review Collection Link & QR Code
        let reviewUrl = '';
        const customUrl = AppState.profile?.google_review_url;
        const placeId = AppState.profile?.google_place_id || 'ChIJDR4_dxUTBDsReG0F-jMX19g';

        if (customUrl && (customUrl.startsWith('http://') || customUrl.startsWith('https://'))) {
            reviewUrl = customUrl;
        } else if (placeId && placeId.startsWith('ChIJ')) {
            reviewUrl = `https://search.google.com/local/writereview?placeid=${placeId}`;
        } else if (placeId && /^\d+$/.test(placeId)) {
            reviewUrl = `https://maps.google.com/?cid=${placeId}`;
        } else {
            reviewUrl = `https://search.google.com/local/writereview?placeid=ChIJDR4_dxUTBDsReG0F-jMX19g`;
        }

        const linkInput = document.getElementById('reviewLinkInput');
        if (linkInput) linkInput.value = reviewUrl;

        updateReviewQrLive(reviewUrl);
    } catch (e) {
        console.error('Error loading reviews:', e);
    }
}

function handleReviewFilterChange() {
    ReviewState.currentPage = 1;
    applyReviewFiltersAndRender();
}

function handleReviewPageSizeChange() {
    const val = document.getElementById('reviewPageSize')?.value || '5';
    ReviewState.pageSize = (val === 'all') ? 'all' : parseInt(val);
    ReviewState.currentPage = 1;
    applyReviewFiltersAndRender();
}

function applyReviewFiltersAndRender() {
    const search = (document.getElementById('reviewSearchInput')?.value || '').toLowerCase().trim();
    const ratingFilter = document.getElementById('reviewRatingFilter')?.value || 'all';
    const statusFilter = document.getElementById('reviewStatusFilter')?.value || 'all';

    ReviewState.filteredReviews = ReviewState.allReviews.filter(r => {
        // Search filter (author name or review text or reply)
        if (search) {
            const author = (r.author_name || '').toLowerCase();
            const comment = (r.comment || '').toLowerCase();
            const reply = (r.ai_reply || '').toLowerCase();
            if (!author.includes(search) && !comment.includes(search) && !reply.includes(search)) {
                return false;
            }
        }

        // Star rating filter
        if (ratingFilter !== 'all') {
            if (r.rating !== parseInt(ratingFilter)) return false;
        }

        // Status filter
        if (statusFilter !== 'all') {
            if (r.status !== statusFilter) return false;
        }

        return true;
    });

    renderReviewsTable();
}

function changeReviewPage(page) {
    const total = ReviewState.filteredReviews.length;
    const limit = ReviewState.pageSize === 'all' ? total : ReviewState.pageSize;
    const maxPage = Math.max(1, Math.ceil(total / limit));

    if (page < 1 || page > maxPage) return;
    ReviewState.currentPage = page;
    renderReviewsTable();
}

function renderReviewsTable() {
    const tbody = document.getElementById('reviewsTableBody');
    const countInfo = document.getElementById('reviewsCountInfo');
    const controls = document.getElementById('reviewsPaginationControls');
    if (!tbody) return;

    const total = ReviewState.filteredReviews.length;

    if (total === 0) {
        tbody.innerHTML = `<tr><td colspan="5" class="text-muted" style="text-align:center; padding: 28px;">No reviews matched your search filter.</td></tr>`;
        if (countInfo) countInfo.textContent = `Showing 0 reviews (${ReviewState.allReviews.length} available, ${ReviewState.googleTotalRatings} on Google Maps)`;
        if (controls) controls.innerHTML = '';
        return;
    }

    // Determine slice range
    let startIdx = 0;
    let endIdx = total;
    let totalPages = 1;

    if (ReviewState.pageSize !== 'all') {
        const limit = ReviewState.pageSize;
        totalPages = Math.ceil(total / limit);
        if (ReviewState.currentPage > totalPages) ReviewState.currentPage = totalPages;
        startIdx = (ReviewState.currentPage - 1) * limit;
        endIdx = Math.min(startIdx + limit, total);
    }

    const pageReviews = ReviewState.filteredReviews.slice(startIdx, endIdx);

    tbody.innerHTML = pageReviews.map(r => {
        const stars = '★'.repeat(r.rating) + '☆'.repeat(5 - r.rating);
        const isReplied = r.status === 'replied';
        const isDemo = !!r.is_demo;
        const isGoogle = r.source === 'Google Maps';
        const avatar = r.profile_photo_url ? `<img src="${escapeHtml(r.profile_photo_url)}" style="width:28px; height:28px; border-radius:50%; object-fit:cover;" alt="">` : `<div style="width:28px; height:28px; border-radius:50%; background:rgba(66,133,244,0.2); color:#4285F4; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:bold;">${escapeHtml(r.author_name.charAt(0))}</div>`;

        return `
            <tr>
                <td>
                    <div style="display:flex; align-items:center; gap:10px;">
                        ${avatar}
                        <div>
                            <div style="font-weight:600; font-size:0.9rem;">${escapeHtml(r.author_name)}</div>
                            <div style="font-size:0.72rem; color:var(--text-dim); display:flex; align-items:center; gap:6px; margin-top:2px;">
                                ${r.relative_time ? `<span>${escapeHtml(r.relative_time)}</span> • ` : ''}
                                ${isGoogle ? `<span style="color:#4285F4;"><i class="fab fa-google"></i> Google Maps</span>` : (isDemo ? `<span style="background:rgba(255,255,255,0.08); padding:1px 5px; border-radius:3px;">Sample Demo</span>` : `<span style="color:#10b981;"><i class="fas fa-check-circle"></i> Direct Client</span>`)}
                            </div>
                        </div>
                    </div>
                </td>
                <td style="color: #F59E0B; font-size: 1rem; letter-spacing: 2px;">${stars}</td>
                <td style="max-width: 340px;">
                    <div style="font-size: 0.85rem; line-height: 1.4;">${escapeHtml(r.comment || '')}</div>
                    ${isReplied ? `
                        <div style="margin-top: 6px; background: rgba(16,185,129,0.08); border-left: 3px solid var(--success); padding: 6px 10px; border-radius: 4px; font-size: 0.78rem; color: #A7F3D0;">
                            <i class="fas fa-robot"></i> <strong>AI Local SEO Reply:</strong> ${escapeHtml(r.ai_reply)}
                        </div>
                    ` : ''}
                </td>
                <td>
                    <span class="status-pill ${isReplied ? 'success' : 'warning'}">
                        ${isReplied ? 'Synced to Google Maps' : 'Reply Pending'}
                    </span>
                </td>
                <td>
                    <div style="display:flex; align-items:center; gap:6px;">
                        ${isReplied ? `
                            <button class="btn btn-outline btn-sm" onclick="copyReviewReply(${r.id})" style="color: #4285F4; border-color: rgba(66,133,244,0.4);" title="Copy reply to clipboard">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                            <button class="btn btn-outline btn-sm" onclick="openReplyModal(${r.id})">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <a href="https://search.google.com/local/reviews?placeid=ChIJDR4_dxUTBDsReG0F-jMX19g" target="_blank" class="btn btn-outline btn-sm" style="padding:4px 8px; color:var(--text-dim);" title="Open Google Maps to paste reply">
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                        ` : `
                            <button class="btn btn-primary btn-sm" onclick="openReplyModal(${r.id})">
                                <i class="fas fa-magic"></i> Auto-Generate Reply
                            </button>
                        `}
                        ${(!isDemo && !isGoogle) ? `
                            <button class="btn btn-outline btn-sm" onclick="deleteCustomerReview(${r.id})" style="padding:4px 8px; color:#ef4444; border-color:rgba(239,68,68,0.3);" title="Remove review">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        ` : ''}
                    </div>
                </td>
            </tr>
        `;
    }).join('');

    // Update count info
    if (countInfo) {
        const fromNum = startIdx + 1;
        const toNum = endIdx;
        countInfo.innerHTML = `Showing <strong>${fromNum}–${toNum}</strong> of <strong>${total}</strong> reviews (${ReviewState.googleTotalRatings} verified on Google Maps)`;
    }

    // Render pagination buttons
    if (controls) {
        if (totalPages <= 1) {
            controls.innerHTML = '';
            return;
        }

        let btnsHtml = `
            <button class="btn btn-outline btn-sm" style="padding:4px 10px; font-size:0.8rem;" onclick="changeReviewPage(${ReviewState.currentPage - 1})" ${ReviewState.currentPage === 1 ? 'disabled style="opacity:0.4; cursor:not-allowed;"' : ''}>
                <i class="fas fa-chevron-left"></i> Prev
            </button>
        `;

        for (let p = 1; p <= totalPages; p++) {
            const isActive = p === ReviewState.currentPage;
            btnsHtml += `
                <button class="btn ${isActive ? 'btn-primary' : 'btn-outline'} btn-sm" style="padding:4px 10px; font-size:0.8rem; min-width:32px;" onclick="changeReviewPage(${p})">
                    ${p}
                </button>
            `;
        }

        btnsHtml += `
            <button class="btn btn-outline btn-sm" style="padding:4px 10px; font-size:0.8rem;" onclick="changeReviewPage(${ReviewState.currentPage + 1})" ${ReviewState.currentPage === totalPages ? 'disabled style="opacity:0.4; cursor:not-allowed;"' : ''}>
                Next <i class="fas fa-chevron-right"></i>
            </button>
        `;

        controls.innerHTML = btnsHtml;
    }
}

function updateReviewQrLive(overrideUrl) {
    const link = overrideUrl || document.getElementById('reviewLinkInput')?.value?.trim() || 'https://search.google.com/local/writereview?placeid=ChIJDR4_dxUTBDsReG0F-jMX19g';
    const qrImg = document.getElementById('reviewQrCodeImg');
    if (qrImg) {
        qrImg.src = `https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=${encodeURIComponent(link)}`;
    }
    const waBtn = document.getElementById('whatsappShareBtn');
    if (waBtn) {
        const waMsg = encodeURIComponent(`Hi! Could you please take 15 seconds to leave Codespark Software Development a 5-star Google review? It helps us immensely: ${link}`);
        waBtn.href = `https://api.whatsapp.com/send?text=${waMsg}`;
    }
}

async function saveCustomReviewLink() {
    const link = document.getElementById('reviewLinkInput')?.value?.trim();
    if (!link) {
        showToast('Please enter a review link', 'warning');
        return;
    }
    try {
        const res = await fetch('api.php?action=save_profile', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ google_review_url: link })
        });
        const data = await res.json();
        if (data.success) {
            if (AppState.profile) AppState.profile.google_review_url = link;
            showToast('Review link saved successfully!', 'success');
            updateReviewQrLive(link);
        } else {
            showToast(data.error || 'Failed to save review link', 'error');
        }
    } catch (e) {
        showToast('Error saving review link', 'error');
    }
}

function printCounterStandee() {
    const link = document.getElementById('reviewLinkInput')?.value || 'https://search.google.com/local/writereview?placeid=ChIJDR4_dxUTBDsReG0F-jMX19g';
    const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=${encodeURIComponent(link)}`;
    const bizName = AppState.profile?.name || 'Codespark Software Development';
    const city = AppState.profile?.city || 'Tirunelveli';

    const printWin = window.open('', '_blank', 'width=700,height=850');
    if (!printWin) {
        window.open(qrUrl, '_blank');
        return;
    }
    printWin.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Google Review Standee - ${bizName}</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; text-align: center; padding: 40px; color: #1e293b; background: #fff; }
                .standee-card { border: 4px solid #4285F4; border-radius: 24px; padding: 40px 30px; max-width: 480px; margin: 0 auto; box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
                .stars { color: #fbbc05; font-size: 32px; margin: 12px 0; letter-spacing: 4px; }
                h1 { font-size: 26px; margin: 0 0 8px 0; color: #0f172a; }
                h2 { font-size: 17px; font-weight: 500; color: #475569; margin: 0 0 24px 0; }
                .qr-box { background: #f8fafc; padding: 20px; border-radius: 16px; display: inline-block; border: 2px dashed #cbd5e1; margin-bottom: 24px; }
                .cta { font-size: 16px; font-weight: 700; color: #4285F4; text-transform: uppercase; letter-spacing: 1px; }
                .footer { font-size: 13px; color: #94a3b8; margin-top: 20px; }
                @media print { body { padding: 0; } .standee-card { box-shadow: none; border-width: 3px; } }
            </style>
        </head>
        <body>
            <div class="standee-card">
                <div class="stars">★★★★★</div>
                <h1>Love Our Work?</h1>
                <h2>Review <strong>${bizName}</strong> on Google!</h2>
                <div class="qr-box">
                    <img src="${qrUrl}" alt="Scan to Review" width="240" height="240">
                </div>
                <div class="cta">Scan with your phone camera</div>
                <div class="footer">Housing Board Colony, Melapalayam, ${city} • Phone: +91 81108 99000</div>
            </div>
            <script>
                window.onload = function() { window.print(); }
            <\/script>
        </body>
        </html>
    `);
    printWin.document.close();
}

function openAddReviewModal() {
    const m = document.getElementById('addReviewModal');
    if (m) {
        if (document.getElementById('newRevAuthor')) document.getElementById('newRevAuthor').value = '';
        if (document.getElementById('newRevComment')) document.getElementById('newRevComment').value = '';
        m.style.display = 'flex';
    }
}

function closeAddReviewModal() {
    const m = document.getElementById('addReviewModal');
    if (m) m.style.display = 'none';
}

async function submitNewCustomerReview() {
    const author = document.getElementById('newRevAuthor')?.value?.trim() || 'Customer';
    const rating = parseInt(document.getElementById('newRevRating')?.value || 5);
    const comment = document.getElementById('newRevComment')?.value?.trim() || '';

    if (!comment) {
        showToast('Please enter customer review comment', 'warning');
        return;
    }

    try {
        const res = await fetch('api.php?action=add_review', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ author_name: author, rating, comment })
        });
        const data = await res.json();
        if (data.success) {
            showToast(data.message, 'success');
            closeAddReviewModal();
            loadReviews();
            loadOverview();
        } else {
            showToast(data.error || 'Failed to add review', 'error');
        }
    } catch (e) {
        showToast('Error saving review', 'error');
    }
}

async function deleteCustomerReview(id) {
    if (!confirm('Remove this review from your dashboard?')) return;
    try {
        const res = await fetch('api.php?action=delete_review', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        const data = await res.json();
        if (data.success) {
            showToast('Review removed', 'info');
            loadReviews();
            loadOverview();
        }
    } catch (e) {
        showToast('Error removing review', 'error');
    }
}

async function openReplyModal(reviewId) {
    const rev = (ReviewState.allReviews || []).find(r => r.id == reviewId) || {};
    const author = rev.author_name || 'Valued Customer';
    const comment = rev.comment || '';
    const existingReply = rev.ai_reply || '';
    const rating = rev.rating || 5;

    const idInput = document.getElementById('modalReviewId');
    const authorEl = document.getElementById('modalAuthorName');
    const commentEl = document.getElementById('modalComment');
    const replyTextarea = document.getElementById('modalReplyContent');
    const badgeEl = document.getElementById('modalKeywordBadge');
    const modal = document.getElementById('replyModal');

    if (idInput) idInput.value = reviewId;
    if (authorEl) authorEl.textContent = author;
    if (commentEl) commentEl.textContent = comment || '(No review text provided)';

    if (existingReply) {
        if (replyTextarea) replyTextarea.value = existingReply;
        if (badgeEl) badgeEl.textContent = 'Existing Local SEO Reply';
        if (modal) modal.style.display = 'flex';
        return;
    }

    if (replyTextarea) replyTextarea.value = 'Generating AI response with targeted Tirunelveli SEO keywords...';
    if (badgeEl) badgeEl.textContent = 'Optimizing with Local Keywords...';
    if (modal) modal.style.display = 'flex';

    try {
        const params = new URLSearchParams({
            action: 'generate_review_reply',
            review_id: reviewId,
            author_name: author,
            comment: comment,
            rating: rating
        });
        const res = await fetch(`api.php?${params.toString()}`);
        const data = await res.json();
        if (data.success && data.reply) {
            if (replyTextarea) replyTextarea.value = data.reply;
            if (badgeEl) badgeEl.textContent = `Targeted Keyword: "${data.targeted_keyword}" (${data.powered_by || 'Local SEO Engine'})`;
        } else {
            if (replyTextarea) {
                replyTextarea.value = `Hello ${author}, thank you so much for the 5-star review! Our team at Codespark Software Development is dedicated to delivering top-tier software and web development services in Tirunelveli. We truly appreciate your support!`;
            }
        }
    } catch (e) {
        if (replyTextarea) {
            replyTextarea.value = `Hello ${author}, thank you so much for the 5-star review! Our team at Codespark Software Development is dedicated to delivering top-tier software and web development services in Tirunelveli. We truly appreciate your support!`;
        }
    }
}

function closeReplyModal() {
    const m = document.getElementById('replyModal');
    if (m) m.style.display = 'none';
}

async function submitReviewReply() {
    const reviewId = document.getElementById('modalReviewId')?.value;
    const reply = document.getElementById('modalReplyContent')?.value?.trim();

    if (!reply) {
        showToast('Please enter a reply', 'error');
        return;
    }

    try {
        const res = await fetch('api.php?action=save_review_reply', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ review_id: reviewId, reply })
        });
        const data = await res.json();
        if (data.success) {
            // Update local state immediately so UI updates in real-time
            const rev = (ReviewState.allReviews || []).find(r => r.id == reviewId);
            if (rev) {
                rev.ai_reply = reply;
                rev.status = 'replied';
            }
            renderReviewsTable();
            showToast('Reply saved locally in SEO engine!', 'success');
            closeReplyModal();
            loadOverview();
        } else {
            showToast(data.error || 'Failed to save reply', 'error');
        }
    } catch (e) {
        showToast('Network error saving reply', 'error');
    }
}

function copyReviewReply(reviewId) {
    const rev = (ReviewState.allReviews || []).find(r => r.id == reviewId);
    if (!rev || !rev.ai_reply) {
        showToast('No reply content to copy', 'warning');
        return;
    }
    navigator.clipboard.writeText(rev.ai_reply).then(() => {
        showToast('Reply copied to clipboard! Paste it into Google Maps.', 'success');
    }).catch(() => {
        showToast('Reply ready: ' + rev.ai_reply.substring(0, 30) + '...', 'info');
    });
}

function copyReplyAndOpenGoogle() {
    const reply = document.getElementById('modalReplyContent')?.value?.trim();
    if (!reply) {
        showToast('Please generate a reply first', 'warning');
        return;
    }
    navigator.clipboard.writeText(reply).then(() => {
        showToast('Reply copied to clipboard! Opening Google Maps...', 'success');
        setTimeout(() => {
            window.open('https://search.google.com/local/reviews?placeid=ChIJDR4_dxUTBDsReG0F-jMX19g', '_blank');
        }, 400);
    }).catch(() => {
        window.open('https://search.google.com/local/reviews?placeid=ChIJDR4_dxUTBDsReG0F-jMX19g', '_blank');
    });
}

// 4. WEBSITE ON-PAGE & TECHNICAL SEO AUDIT
async function runWebsiteAudit() {
    const urlInput = document.getElementById('auditUrlInput');
    const url = urlInput.value.trim();
    if (!url) {
        showToast('Please enter a website URL', 'error');
        return;
    }

    const auditBtn = document.getElementById('btnRunAudit');
    auditBtn.disabled = true;
    auditBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Crawling Website...';

    try {
        const res = await fetch('api.php?action=audit_website', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ url })
        });
        const data = await res.json();

        if (data.success) {
            showToast(`Audit finished! Health Score: ${data.health_score}/100`, 'success');
            renderAuditResults(data);
            loadOverview();
        } else {
            showToast(data.error || 'Audit failed', 'error');
        }
    } catch (e) {
        showToast('Error running website crawler', 'error');
    } finally {
        auditBtn.disabled = false;
        auditBtn.innerHTML = '<i class="fas fa-bolt"></i> Run Live Technical Audit';
    }
}

function renderAuditResults(data) {
    document.getElementById('auditResultsCard').style.display = 'block';
    
    // Score Badge
    const scoreBadge = document.getElementById('auditScoreVal');
    scoreBadge.textContent = data.health_score;
    scoreBadge.style.color = data.health_score >= 80 ? 'var(--success)' : (data.health_score >= 60 ? 'var(--warning)' : 'var(--danger)');

    // Metrics table
    const m = data.metrics;
    document.getElementById('auditMetaTitle').textContent = m.title || '(Missing)';
    document.getElementById('auditMetaTitleLen').textContent = `${m.title_length} characters`;
    document.getElementById('auditMetaDesc').textContent = m.description || '(Missing)';
    document.getElementById('auditMetaDescLen').textContent = `${m.desc_length} characters`;
    document.getElementById('auditH1').textContent = m.h1 || '(None)';
    document.getElementById('auditH1Count').textContent = `${m.h1_count} tag(s)`;
    document.getElementById('auditImages').textContent = `${m.total_images} total (${m.missing_alt} missing ALT)`;
    document.getElementById('auditSpeed').textContent = `${m.load_time_ms} ms response time`;

    // Issues list
    const issuesContainer = document.getElementById('auditIssuesList');
    if (data.issues.length === 0) {
        issuesContainer.innerHTML = `<div class="status-pill success"><i class="fas fa-check"></i> No critical on-page issues detected!</div>`;
    } else {
        issuesContainer.innerHTML = data.issues.map(iss => `
            <div style="background: rgba(239, 68, 68, 0.08); border-left: 3px solid ${iss.type === 'critical' ? 'var(--danger)' : 'var(--warning)'}; padding: 10px 14px; border-radius: 4px; margin-bottom: 8px;">
                <div style="display:flex; justify-content:space-between; font-weight: 700; font-size: 0.88rem; color: #fff;">
                    <span>${iss.title}</span>
                    <span class="status-pill ${iss.type === 'critical' ? 'danger' : 'warning'}">${iss.type}</span>
                </div>
                <p style="margin-top: 4px; font-size: 0.8rem; color: var(--text-muted);">${iss.desc}</p>
            </div>
        `).join('');
    }
}

// 5. LOCAL SCHEMA (JSON-LD) BUILDER
async function generateLocalSchema() {
    const schemaType = document.getElementById('schemaTypeSelect').value;
    try {
        const res = await fetch(`api.php?action=generate_schema&schema_type=${encodeURIComponent(schemaType)}`);
        const data = await res.json();
        if (data.success) {
            document.getElementById('schemaCodeDisplay').textContent = data.script_tag;
            showToast('Structured JSON-LD schema generated!', 'success');
        }
    } catch (e) {
        showToast('Failed to generate schema', 'error');
    }
}

function copySchemaCode() {
    const code = document.getElementById('schemaCodeDisplay').textContent;
    navigator.clipboard.writeText(code).then(() => {
        showToast('JSON-LD schema copied to clipboard!', 'success');
    });
}

// 6. PROGRAMMATIC LOCAL PAGES GENERATOR
async function generateProgrammaticPages() {
    const services = document.getElementById('programmaticServices').value;
    const locations = document.getElementById('programmaticLocations').value;

    const btn = document.getElementById('btnGenProgPages');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating Local Pages...';

    try {
        const res = await fetch('api.php?action=generate_local_pages', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ services, locations })
        });
        const data = await res.json();
        if (data.success) {
            renderProgrammaticPagesTable(data.pages);
            showToast(`Generated ${data.count} optimized local landing page blueprints!`, 'success');
        }
    } catch (e) {
        showToast('Error generating programmatic blueprints', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-layer-group"></i> Generate Local Landing Blueprints';
    }
}

function renderProgrammaticPagesTable(pages) {
    const container = document.getElementById('programmaticTableContainer');
    container.style.display = 'block';
    const tbody = document.getElementById('programmaticTableBody');

    tbody.innerHTML = pages.map((p, idx) => `
        <tr>
            <td><strong style="color: var(--secondary);">${p.slug}</strong></td>
            <td><strong>${escapeHtml(p.title)}</strong></td>
            <td style="font-size: 0.8rem; color: var(--text-muted);">${escapeHtml(p.meta_description)}</td>
            <td><span class="status-pill primary">${escapeHtml(p.location)}</span></td>
            <td style="display:flex; gap: 6px; align-items: center;">
                <button class="btn btn-outline btn-sm" onclick="showLocalPageBlueprint(${idx})">
                    <i class="fas fa-eye"></i> Blueprint
                </button>
                ${p.liveLink ? `
                    <a href="${p.liveLink}" target="_blank" rel="noopener noreferrer" class="btn btn-outline btn-sm" style="color: #60a5fa; border-color: rgba(96,165,250,0.4); text-decoration: none; display: inline-flex; align-items: center; gap: 6px; cursor: pointer;">
                        <i class="fas fa-external-link-alt"></i> View Live
                    </a>
                ` : `
                    <button class="btn btn-success btn-sm" onclick="publishPageToWordPress(${idx}, this)">
                        <i class="fab fa-wordpress"></i> Publish to WP
                    </button>
                `}
            </td>
        </tr>
    `).join('');

    window.generatedLocalPages = pages;
}

async function publishPageToWordPress(index, btn) {
    const page = window.generatedLocalPages ? window.generatedLocalPages[index] : null;
    if (!page) return;

    const origHtml = btn ? btn.innerHTML : 'Publish to WP';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Publishing...';
    }

    const content = `
<h2>${page.h1}</h2>
<p>${page.meta_description}</p>
<div style="background:#f8fafc; border-left:4px solid #3b82f6; padding:16px; margin:20px 0; border-radius:4px;">
    <h3 style="margin-top:0; color:#1e3a8a;">Professional ${page.service} Serving ${page.location}</h3>
    <p>Codespark Technology provides enterprise-grade, high-performance technology services in <strong>${page.location}</strong> and across Tamil Nadu. Contact our dedicated solutions team today at +91 81108 99000.</p>
</div>
<h3>Frequently Asked Questions</h3>
<p><strong>${page.faqs[0].q}</strong></p>
<p>${page.faqs[0].a}</p>
    `.trim();

    try {
        const res = await fetch('api.php?action=publish_to_wordpress', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                title: page.title,
                content: content,
                slug: page.slug.replace('/services/', '')
            })
        });
        const data = await res.json();
        if (data.success) {
            showToast('Published live to codespark.online!', 'success');
            const liveUrl = data.link || `https://codespark.online/${page.slug.replace('/services/', '')}/`;
            page.liveLink = liveUrl;

            // Re-render button as real native clickable anchor link
            if (btn) {
                btn.disabled = false;
                const a = document.createElement('a');
                a.href = liveUrl;
                a.target = '_blank';
                a.rel = 'noopener noreferrer';
                a.className = 'btn btn-outline btn-sm';
                a.style.cssText = 'color: #60a5fa; border-color: rgba(96,165,250,0.4); text-decoration: none; display: inline-flex; align-items: center; gap: 6px; cursor: pointer;';
                a.innerHTML = `<i class="fas fa-external-link-alt"></i> View Live`;
                if (btn.parentNode) {
                    btn.parentNode.replaceChild(a, btn);
                } else {
                    btn.outerHTML = a.outerHTML;
                }
            }
        } else {
            showToast(data.error || 'Failed to publish to WordPress', 'error');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = origHtml;
            }
        }
    } catch (e) {
        showToast('Network error publishing to WordPress', 'error');
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = origHtml;
        }
    }
}

// Services & Focusing Keywords Presets Helpers
function addServicePreset(services, replace = false) {
    const input = document.getElementById('programmaticServices');
    if (!input) return;
    if (replace || !input.value.trim()) {
        input.value = services;
    } else {
        const currentList = input.value.split(',').map(s => s.trim()).filter(Boolean);
        const toAddList = services.split(',').map(s => s.trim()).filter(Boolean);
        const merged = Array.from(new Set([...currentList, ...toAddList]));
        input.value = merged.join(', ');
    }
    showToast('Services & focusing keywords updated!', 'info');
}

function clearServices() {
    const input = document.getElementById('programmaticServices');
    if (input) {
        input.value = '';
        input.focus();
        showToast('Services cleared. Select presets or type your keywords.', 'info');
    }
}

function loadAllKeywordsToServices() {
    const profInput = document.getElementById('profKeywords');
    let allKws = '';
    if (profInput && profInput.value.trim()) {
        allKws = profInput.value.trim();
    } else {
        allKws = 'IT Company, Software Company, Website Designer, Website Developer, Internship Training, Free Internship For College Students, Free Cloud Server Provider, Free Internship Training, Free Hosting Provider, Cloud Server, Mobile App Development, Mobile App Developer, Android App Developer, iOS App Developer, Play Store Console Provider, Online Internship Software Development, Near by IT Company, Near by Software Company, SEO Company, SEO Codespark, No.1 SEO Company, Top website development company, Billing Software, Custom Software Development';
    }
    const input = document.getElementById('programmaticServices');
    if (input) {
        input.value = allKws;
        showToast('Loaded all focusing keywords into Services!', 'success');
    }
}

// Location Presets Helpers
function addLocationPreset(cities, replace = false) {
    const input = document.getElementById('programmaticLocations');
    if (!input) return;
    if (replace || !input.value.trim()) {
        input.value = cities;
    } else {
        const currentList = input.value.split(',').map(s => s.trim()).filter(Boolean);
        const toAddList = cities.split(',').map(s => s.trim()).filter(Boolean);
        const merged = Array.from(new Set([...currentList, ...toAddList]));
        input.value = merged.join(', ');
    }
    showToast('Target cities updated!', 'info');
}

function clearLocations() {
    const input = document.getElementById('programmaticLocations');
    if (input) {
        input.value = '';
        input.focus();
        showToast('Locations cleared. Type any city name.', 'info');
    }
}

// Blueprint Modal Handlers
let currentActiveBlueprintIndex = null;

function showLocalPageBlueprint(index) {
    const page = window.generatedLocalPages ? window.generatedLocalPages[index] : null;
    if (!page) return;
    currentActiveBlueprintIndex = index;

    const modal = document.getElementById('localBlueprintModal');
    if (!modal) return;

    document.getElementById('blueprintModalTitle').textContent = `${page.service} in ${page.location}`;
    document.getElementById('blueprintModalLocality').textContent = `Target Locality: ${page.location}`;
    document.getElementById('blueprintModalSlug').textContent = page.slug;
    document.getElementById('blueprintModalSeoTitle').textContent = page.title;
    document.getElementById('blueprintModalMetaDesc').textContent = page.meta_description;
    
    document.getElementById('blueprintModalBody').innerHTML = `
        <h4 style="margin:0 0 6px 0; color:#fff;">&lt;h1&gt; ${escapeHtml(page.h1)} &lt;/h1&gt;</h4>
        <p style="margin:0 0 10px 0;">${escapeHtml(page.meta_description)}</p>
        <div style="background:rgba(255,255,255,0.05); padding:8px 10px; border-radius:4px; margin-bottom:8px;">
            <strong style="color:var(--secondary); font-size:0.8rem;">FAQ: ${escapeHtml(page.faqs[0].q)}</strong>
            <p style="margin:4px 0 0 0; font-size:0.78rem;">${escapeHtml(page.faqs[0].a)}</p>
        </div>
    `;

    const schemaObj = {
        "@context": "https://schema.org",
        "@type": "Service",
        "name": `${page.service} in ${page.location}`,
        "provider": {
            "@type": "LocalBusiness",
            "name": "Codespark Software Development",
            "telephone": "+918110899000",
            "url": "https://codespark.online"
        },
        "areaServed": {
            "@type": "City",
            "name": page.location
        },
        "description": page.meta_description
    };
    document.getElementById('blueprintModalSchema').textContent = JSON.stringify(schemaObj, null, 2);

    modal.style.display = 'flex';
}

function closeBlueprintModal() {
    const modal = document.getElementById('localBlueprintModal');
    if (modal) modal.style.display = 'none';
}

function copyBlueprintHtml() {
    if (currentActiveBlueprintIndex === null || !window.generatedLocalPages) return;
    const page = window.generatedLocalPages[currentActiveBlueprintIndex];
    if (!page) return;

    const fullHtml = `<!-- SEO Landing Page: ${page.title} -->
<h1>${page.h1}</h1>
<p>${page.meta_description}</p>
<div class="service-highlight">
    <h2>Premier ${page.service} in ${page.location}</h2>
    <p>Delivering cutting-edge web, mobile, and enterprise digital solutions tailored for businesses in ${page.location}.</p>
</div>
<h3>Frequently Asked Questions</h3>
<p><strong>${page.faqs[0].q}</strong></p>
<p>${page.faqs[0].a}</p>
<script type="application/ld+json">
${document.getElementById('blueprintModalSchema').textContent}
</script>`;

    navigator.clipboard.writeText(fullHtml).then(() => {
        showToast('Full HTML & Schema copied to clipboard!', 'success');
    }).catch(() => {
        showToast('Failed to copy to clipboard', 'error');
    });
}

async function publishModalBlueprintToWp() {
    if (currentActiveBlueprintIndex === null) return;
    const btn = document.getElementById('btnModalPublishWp');
    await publishPageToWordPress(currentActiveBlueprintIndex, btn);
}

// 7. SOCIAL & CITATION SYNDICATOR
async function loadPosts() {
    try {
        const res = await fetch('api.php?action=get_posts');
        const data = await res.json();
        if (data.success) {
            AppState.posts = data.posts || [];
            const list = document.getElementById('allPostsList');
            if (!list) return;
            if (data.posts.length === 0) {
                list.innerHTML = `<p class="text-muted" style="padding: 16px;">No posts published yet.</p>`;
                return;
            }
            list.innerHTML = data.posts.map((p, idx) => {
                let platforms = [];
                try {
                    platforms = Array.isArray(p.platforms) ? p.platforms : JSON.parse(p.platforms || '[]');
                } catch(e) {
                    platforms = typeof p.platforms === 'string' ? p.platforms.split(',') : [];
                }

                const shareUrl = encodeURIComponent(p.wp_link || 'https://codespark.online/');
                const shareText = encodeURIComponent((p.title ? p.title + ': ' : '') + p.content);
                const fbUrl = `https://www.facebook.com/sharer/sharer.php?u=${shareUrl}`;
                const inUrl = `https://www.linkedin.com/sharing/share-offsite/?url=${shareUrl}`;
                const twUrl = `https://twitter.com/intent/tweet?text=${shareText}&url=${shareUrl}`;
                const waUrl = `https://api.whatsapp.com/send?text=${shareText}%20${shareUrl}`;

                const imgHtml = p.image_url ? `
                    <div style="margin-top: 10px; margin-bottom: 10px;">
                        <img src="${escapeHtml(p.image_url)}" alt="Post image" style="max-width: 200px; max-height: 120px; border-radius: var(--radius-sm); border: 1px solid var(--border-color); object-fit: cover;">
                    </div>
                ` : '';

                const wpLinkHtml = p.wp_link ? `
                    <a href="${p.wp_link}" target="_blank" class="btn btn-outline btn-sm" style="color: #60a5fa; border-color: rgba(96,165,250,0.4); text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                        <i class="fab fa-wordpress"></i> View Live on Website <i class="fas fa-external-link-alt" style="font-size: 0.7rem;"></i>
                    </a>
                ` : '';

                const metaHtml = (p.meta_title || p.meta_description || p.meta_keywords) ? `
                    <div style="margin-top: 10px; margin-bottom: 10px; padding: 10px; background: rgba(16, 185, 129, 0.05); border: 1px dashed rgba(16, 185, 129, 0.25); border-radius: 4px; font-size: 0.8rem;">
                        <div style="display:flex; align-items:center; gap: 6px; margin-bottom: 4px;">
                            <span class="status-pill success" style="font-size: 0.68rem; padding: 2px 6px;"><i class="fas fa-search"></i> SEO Meta Tag</span>
                            <span style="color: #60a5fa; font-weight: 600;">${escapeHtml(p.meta_title || p.title || '')}</span>
                        </div>
                        ${p.meta_description ? `<div style="color: var(--text-muted); line-height: 1.4; margin-bottom: 4px;"><strong>Snippet:</strong> ${escapeHtml(p.meta_description)}</div>` : ''}
                        ${p.meta_keywords ? `<div style="color: var(--text-dim); font-size: 0.75rem;"><strong style="color: var(--text-muted);">Keywords:</strong> ${escapeHtml(p.meta_keywords)}</div>` : ''}
                    </div>
                ` : '';

                return `
                    <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 16px; margin-bottom: 14px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 8px;">
                            <h4 style="margin:0; font-size: 1rem; color: #fff;">${escapeHtml(p.title || 'Local Update')}</h4>
                            <div style="display:flex; align-items:center; gap: 8px;">
                                <span class="status-pill ${p.status === 'published' ? 'success' : 'primary'}">${p.status}</span>
                                <button onclick="deletePostEntry(${idx})" class="btn btn-outline btn-sm" style="padding: 2px 7px; font-size: 0.72rem; color: #ef4444; border-color: rgba(239,68,68,0.3);" title="Delete this post from history">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </div>
                        <p style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 10px; line-height: 1.5;">${escapeHtml(p.content)}</p>
                        ${metaHtml}
                        ${imgHtml}
                        <div style="display:flex; flex-wrap: wrap; justify-content:space-between; align-items:center; gap: 10px; margin-top: 12px; padding-top: 10px; border-top: 1px solid rgba(255,255,255,0.06); font-size: 0.8rem;">
                            <div style="display:flex; align-items: center; gap: 6px;">
                                <span style="color: var(--text-dim);">Platforms:</span> 
                                ${(platforms || []).map(pl => `<span class="status-pill primary" style="font-size: 0.7rem; text-transform: uppercase;"><i class="fab fa-${pl === 'gmb' ? 'google' : (pl === 'wordpress' ? 'wordpress' : pl)}"></i> ${pl}</span>`).join(' ')}
                            </div>
                            <div style="display:flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                ${wpLinkHtml}
                                <button class="btn btn-outline btn-sm" onclick="openGmbUpdateModal(${idx})" style="padding: 4px 8px; font-size: 0.75rem; color: #4285F4; border-color: rgba(66,133,244,0.4);" title="Post update to Google Business Profile">
                                    <i class="fab fa-google"></i> Google Update ↗
                                </button>
                                <a href="${fbUrl}" target="_blank" class="btn btn-outline btn-sm" style="padding: 4px 8px; font-size: 0.75rem; color: #1877F2; border-color: rgba(24,119,242,0.3);" title="Share to Facebook">
                                    <i class="fab fa-facebook-f"></i> Share
                                </a>
                                <a href="${inUrl}" target="_blank" class="btn btn-outline btn-sm" style="padding: 4px 8px; font-size: 0.75rem; color: #0A66C2; border-color: rgba(10,102,194,0.3);" title="Share to LinkedIn">
                                    <i class="fab fa-linkedin-in"></i> Share
                                </a>
                                <a href="${twUrl}" target="_blank" class="btn btn-outline btn-sm" style="padding: 4px 8px; font-size: 0.75rem; color: #1DA1F2; border-color: rgba(29,161,242,0.3);" title="Share to X">
                                    <i class="fab fa-x-twitter"></i>
                                </a>
                                <a href="${waUrl}" target="_blank" class="btn btn-outline btn-sm" style="padding: 4px 8px; font-size: 0.75rem; color: #25D366; border-color: rgba(37,211,102,0.3);" title="Share via WhatsApp">
                                    <i class="fab fa-whatsapp"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }
    } catch (e) {
        console.error('Error loading posts:', e);
    }
}

async function clearPostsHistory() {
    if (!confirm('Are you sure you want to clear the entire publication history log? (Note: Posts already live on WordPress will remain on your website).')) {
        return;
    }
    
    const btn = document.getElementById('btnClearPostsHistory');
    const origHtml = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Clearing...';
    }
    
    try {
        const res = await fetch('api.php?action=clear_posts_history', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        });
        const data = await res.json();
        if (data.success) {
            showToast(data.message || 'History log cleared successfully!', 'success');
            loadPosts();
            loadOverview();
        } else {
            showToast(data.error || 'Failed to clear history log', 'error');
        }
    } catch (e) {
        showToast('Network error clearing history log', 'error');
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = origHtml;
        }
    }
}

async function deletePostEntry(index) {
    if (!confirm('Remove this post entry from the history log?')) {
        return;
    }
    
    try {
        const res = await fetch('api.php?action=delete_post', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ index })
        });
        const data = await res.json();
        if (data.success) {
            showToast('Post entry deleted from history', 'info');
            loadPosts();
            loadOverview();
        } else {
            showToast(data.error || 'Failed to delete post', 'error');
        }
    } catch (e) {
        showToast('Network error deleting post', 'error');
    }
}

// ==========================================
// GOOGLE MAP UPDATES STUDIO CONTROLLER
// ==========================================

function openGmbUpdateModal(idx) {
    const post = (AppState.posts || [])[idx];
    switchTab('gmb-updates');
    if (post) {
        if (document.getElementById('gmbHeadlineInput')) {
            document.getElementById('gmbHeadlineInput').value = post.title || post.meta_title || '';
        }
        if (document.getElementById('gmbBodyInput')) {
            document.getElementById('gmbBodyInput').value = post.content || '';
        }
        if (document.getElementById('gmbImageUrlInput') && post.image_url) {
            document.getElementById('gmbImageUrlInput').value = post.image_url;
        }
        if (document.getElementById('gmbButtonUrlInput') && post.cta_url) {
            document.getElementById('gmbButtonUrlInput').value = post.cta_url;
        }
        if (document.getElementById('gmbCtaTypeSelect') && post.cta_type) {
            document.getElementById('gmbCtaTypeSelect').value = post.cta_type;
        }
        updateGmbLivePreview();
        showToast('Post loaded into Google Map Updates studio! Ready to publish.', 'info');
    }
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function closeGmbUpdateModal() {
    // Legacy support
}

function copyGmbModalText() {
    // Legacy support
}

function updateGmbLivePreview() {
    const headline = document.getElementById('gmbHeadlineInput')?.value?.trim() || 'Top Software & Web Development Company in Tirunelveli | Codespark';
    const body = document.getElementById('gmbBodyInput')?.value || '';
    const imageUrl = document.getElementById('gmbImageUrlInput')?.value?.trim() || 'https://images.unsplash.com/photo-1571171637578-41bc2dd41cd2?w=800&auto=format&fit=crop';
    const ctaType = document.getElementById('gmbCtaTypeSelect')?.value || 'LEARN_MORE';
    const ctaUrl = document.getElementById('gmbButtonUrlInput')?.value?.trim() || 'https://codespark.online/services/';

    // Update image
    const imgEl = document.getElementById('gmbPreviewImg');
    if (imgEl) {
        imgEl.src = imageUrl;
        imgEl.onerror = () => {
            imgEl.src = 'https://images.unsplash.com/photo-1571171637578-41bc2dd41cd2?w=800&auto=format&fit=crop';
        };
    }

    // Update headline
    const headlineEl = document.getElementById('gmbPreviewHeadline');
    if (headlineEl) headlineEl.textContent = headline;

    // Update body
    const bodyEl = document.getElementById('gmbPreviewBody');
    if (bodyEl) {
        if (body.trim()) {
            bodyEl.textContent = body;
        } else {
            bodyEl.textContent = "Looking for premier digital solutions in Tirunelveli? 🚀\n\nAt Codespark Software Development, we build high-performance mobile apps, custom billing systems, and responsive websites.\n\n📍 Melapalayam, Tirunelveli\n📞 Call: +91 81108 99000\n🌐 codespark.online";
        }
    }

    // Update character counter
    const charCountEl = document.getElementById('gmbCharCount');
    if (charCountEl) {
        const len = body.length;
        charCountEl.textContent = `${len} characters` + (len > 1500 ? ' (Google limit is 1500)' : '');
        charCountEl.style.color = len > 1500 ? '#ef4444' : 'var(--text-dim)';
    }

    // Format Button Label
    const ctaLabels = {
        'LEARN_MORE': 'Learn more',
        'BOOK': 'Book online',
        'ORDER': 'Order online',
        'SIGN_UP': 'Sign up',
        'CALL': 'Call now'
    };
    const ctaText = ctaLabels[ctaType] || 'Learn more';
    
    const btnTextEl = document.getElementById('gmbPreviewCtaText');
    if (btnTextEl) btnTextEl.textContent = ctaText;

    const ctaBtn = document.getElementById('gmbPreviewCtaBtn');
    if (ctaBtn) {
        ctaBtn.href = ctaType === 'CALL' ? 'tel:+918110899000' : ctaUrl;
    }

    const urlTargetEl = document.getElementById('gmbPreviewUrlTarget');
    if (urlTargetEl) {
        urlTargetEl.textContent = ctaType === 'CALL' ? '+91 81108 99000' : ctaUrl.replace(/^https?:\/\//, '');
        urlTargetEl.title = ctaUrl;
    }
}

function selectGmbPresetTopic(topic, el) {
    const input = document.getElementById('gmbTopicInput');
    if (input) input.value = topic;

    // Highlight chip
    document.querySelectorAll('.gmb-topic-chip').forEach(c => c.classList.remove('active'));
    if (el) el.classList.add('active');

    // Automatically generate with Gemini
    generateGmbUpdateWithGemini();
}

function selectGmbPresetImage(url, el) {
    const input = document.getElementById('gmbImageUrlInput');
    if (input) input.value = url;

    document.querySelectorAll('.gmb-image-preset-card').forEach(c => c.classList.remove('active'));
    if (el) el.classList.add('active');

    updateGmbLivePreview();
}

function setGmbButtonUrl(url) {
    const input = document.getElementById('gmbButtonUrlInput');
    if (input) {
        input.value = url;
        input.style.borderColor = '#34d399';
        setTimeout(() => { input.style.borderColor = ''; }, 1000);
    }
    updateGmbLivePreview();
}

async function generateGmbUpdateWithGemini() {
    const topic = document.getElementById('gmbTopicInput')?.value?.trim() || 'Software Development';
    const btn = document.getElementById('btnGenGmbAi');
    const origHtml = btn ? btn.innerHTML : '';

    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Gemini Connecting...';
    }

    try {
        const res = await fetch('api.php?action=generate_gmb_update', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ topic })
        });
        const data = await res.json();

        if (data.success) {
            if (document.getElementById('gmbHeadlineInput')) {
                document.getElementById('gmbHeadlineInput').value = data.headline || '';
            }
            if (document.getElementById('gmbBodyInput')) {
                document.getElementById('gmbBodyInput').value = data.content || '';
            }
            if (document.getElementById('gmbImageUrlInput') && data.image_url) {
                document.getElementById('gmbImageUrlInput').value = data.image_url;
                // Highlight matching image preset if exists
                document.querySelectorAll('.gmb-image-preset-card').forEach(c => {
                    const img = c.querySelector('img');
                    if (img && data.image_url.includes(img.src.split('?')[0])) {
                        c.classList.add('active');
                    } else {
                        c.classList.remove('active');
                    }
                });
            }
            if (document.getElementById('gmbButtonUrlInput') && data.cta_url) {
                document.getElementById('gmbButtonUrlInput').value = data.cta_url;
            }
            if (document.getElementById('gmbCtaTypeSelect') && data.cta_type) {
                document.getElementById('gmbCtaTypeSelect').value = data.cta_type;
            }

            updateGmbLivePreview();
            showToast('Gemini connected Google Map & generated update post!', 'success');
        } else {
            showToast(data.error || 'Failed to generate update with Gemini', 'error');
        }
    } catch (e) {
        showToast('Network error generating update with Gemini', 'error');
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = origHtml;
        }
    }
}

async function publishGmbUpdate() {
    const headline = document.getElementById('gmbHeadlineInput')?.value?.trim() || '';
    const content = document.getElementById('gmbBodyInput')?.value?.trim() || '';
    const imageUrl = document.getElementById('gmbImageUrlInput')?.value?.trim() || '';
    const ctaType = document.getElementById('gmbCtaTypeSelect')?.value || 'LEARN_MORE';
    const ctaUrl = document.getElementById('gmbButtonUrlInput')?.value?.trim() || 'https://codespark.online/services/';
    const syndicateWp = document.getElementById('gmbSyndicateWp')?.checked || false;

    if (!content) {
        showToast('Please provide update body content before publishing.', 'warning');
        document.getElementById('gmbBodyInput')?.focus();
        return;
    }

    if (!ctaUrl) {
        showToast('Please specify a Button Destination URL.', 'warning');
        document.getElementById('gmbButtonUrlInput')?.focus();
        return;
    }

    const btn = document.getElementById('btnPublishGmb');
    const origHtml = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Publishing to Google Profile...';
    }

    try {
        const res = await fetch('api.php?action=publish_gmb_update', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                headline,
                content,
                image_url: imageUrl,
                cta_type: ctaType,
                cta_url: ctaUrl,
                syndicate_wp: syndicateWp
            })
        });
        const data = await res.json();

        if (data.success) {
            loadGmbUpdates();
            loadPosts();
            loadOverview();

            // Always open the 1-Click Google Assistant popup after publish
            openGmbPublishAssistant({
                headline,
                content,
                cta_type: ctaType,
                cta_url: ctaUrl,
                image_url: imageUrl
            });
            showToast('Post published! Google Assistant popup opened & ready to paste.', 'success');
        } else {
            showToast(data.error || 'Failed to publish update', 'error');
        }
    } catch (e) {
        showToast('Network error publishing Google update', 'error');
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = origHtml;
        }
    }
}

function openCurrentPostInAssistant() {
    const headline = document.getElementById('postTitleInput')?.value?.trim() || '';
    const content = document.getElementById('postBodyInput')?.value?.trim() || '';
    const ctaType = document.getElementById('gmbButtonTypeSelect')?.value || 'LEARN_MORE';
    const ctaUrl = document.getElementById('gmbButtonUrlInput')?.value?.trim() || '';
    const imageUrl = document.getElementById('gmbImageUrlInput')?.value?.trim() || '';

    if (!headline && !content) {
        showToast('Please enter a headline or generate post content first.', 'warning');
        document.getElementById('postTitleInput')?.focus();
        return;
    }

    openGmbPublishAssistant({
        headline,
        content,
        cta_type: ctaType,
        cta_url: ctaUrl,
        image_url: imageUrl
    });
    showToast('Google Assistant popup opened & copied to clipboard!', 'success');
}

function openGmbAssistantForUpdate(idx) {
    const post = GmbState.updates[idx];
    if (!post) return;
    openGmbPublishAssistant({
        headline: post.headline || '',
        content: post.content || '',
        cta_type: post.cta_type || 'LEARN_MORE',
        cta_url: post.cta_url || '',
        image_url: post.image_url || ''
    });
    showToast('Post text copied to clipboard & Google Assistant popup opened!', 'success');
}

function openGmbPublishAssistant(post) {
    const textToCopy = (post.headline ? post.headline + "\n\n" : "") + (post.content || "");
    const textEl = document.getElementById('gmbAssistantText');
    if (textEl) textEl.value = textToCopy;

    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(textToCopy);
    }

    const ctaBadge = document.getElementById('gmbAssistantCtaBadge');
    if (ctaBadge) ctaBadge.textContent = post.cta_type || 'LEARN_MORE';

    const ctaUrl = document.getElementById('gmbAssistantCtaUrl');
    if (ctaUrl) {
        ctaUrl.textContent = post.cta_url || 'No URL specified';
        ctaUrl.title = post.cta_url || '';
    }

    const ctaEl = document.getElementById('gmbAssistantCtaType');
    if (ctaEl) ctaEl.textContent = `${post.cta_type || 'Learn more'}: ${post.cta_url || ''}`;

    const imgThumb = document.getElementById('gmbAssistantImgThumb');
    if (imgThumb) {
        if (post.image_url) {
            imgThumb.src = post.image_url;
            imgThumb.style.display = 'block';
        } else {
            imgThumb.style.display = 'none';
        }
    }

    const imgEl = document.getElementById('gmbAssistantImageText');
    if (imgEl) {
        imgEl.textContent = post.image_url || 'No image attached';
        imgEl.title = post.image_url || '';
    }

    window._lastGmbPost = post;

    const modal = document.getElementById('gmbPublishAssistantModal');
    if (modal) modal.style.display = 'flex';
}

function closeGmbPublishAssistant() {
    const modal = document.getElementById('gmbPublishAssistantModal');
    if (modal) modal.style.display = 'none';
}

function copyGmbAssistantText() {
    const text = document.getElementById('gmbAssistantText')?.value || '';
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(() => {
            showToast('Post text copied to clipboard!', 'success');
        });
    }
}

function copyGmbAssistantUrl() {
    const url = window._lastGmbPost?.cta_url || document.getElementById('gmbButtonUrlInput')?.value || '';
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(url).then(() => {
            showToast('Button URL copied to clipboard!', 'success');
        });
    }
}

function copyGmbAssistantImage() {
    const img = window._lastGmbPost?.image_url || document.getElementById('gmbImageUrlInput')?.value || '';
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(img).then(() => {
            showToast('Image URL copied to clipboard!', 'success');
        });
    }
}

const GmbState = {
    updates: [],
    currentPage: 1,
    perPage: 3
};

async function loadGmbUpdates() {
    const container = document.getElementById('gmbPublishedUpdatesFeed');
    if (!container) return;

    try {
        const res = await fetch('api.php?action=get_gmb_updates');
        const data = await res.json();
        GmbState.updates = (data.success && Array.isArray(data.updates)) ? data.updates : [];
        renderGmbUpdatesList();
    } catch (e) {
        container.innerHTML = `<div style="padding: 20px; color: #ef4444; text-align: center;">Error loading updates feed.</div>`;
    }
}

function changeGmbPerPage(newVal) {
    GmbState.perPage = parseInt(newVal, 10) || 3;
    GmbState.currentPage = 1;
    renderGmbUpdatesList();
}

function changeGmbUpdatesPage(newPage) {
    GmbState.currentPage = newPage;
    renderGmbUpdatesList();
    document.getElementById('gmbPublishedUpdatesFeed')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function renderGmbUpdatesList() {
    const container = document.getElementById('gmbPublishedUpdatesFeed');
    const wrap = document.getElementById('gmbUpdatesPaginationWrap');
    const countEl = document.getElementById('gmbPaginationCount');
    const controls = document.getElementById('gmbPaginationControls');
    if (!container) return;

    const total = GmbState.updates.length;
    if (total === 0) {
        container.innerHTML = `
            <div style="text-align: center; padding: 36px 20px; color: var(--text-dim);">
                <i class="fab fa-google" style="font-size: 2.2rem; margin-bottom: 12px; color: #4285F4; opacity: 0.6;"></i>
                <p style="font-size: 0.9rem; margin: 0;">No updates published yet. Create and publish your first Google Maps update above!</p>
            </div>
        `;
        if (wrap) wrap.style.display = 'none';
        return;
    }

    const totalPages = Math.ceil(total / GmbState.perPage) || 1;
    if (GmbState.currentPage > totalPages) GmbState.currentPage = totalPages;
    if (GmbState.currentPage < 1) GmbState.currentPage = 1;

    const startIdx = (GmbState.currentPage - 1) * GmbState.perPage;
    const endIdx = Math.min(startIdx + GmbState.perPage, total);
    const pageItems = GmbState.updates.slice(startIdx, endIdx);

    container.innerHTML = pageItems.map((u, i) => {
        const actualIdx = startIdx + i;
        const timeStr = u.published_at ? new Date(u.published_at).toLocaleDateString(undefined, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) : 'Recently';
        const imgHtml = u.image_url ? `<img src="${u.image_url}" class="gmb-history-thumb" alt="Update Image" onerror="this.style.display='none'">` : `<div class="gmb-history-thumb" style="display:flex;align-items:center;justify-content:center;background:#1E293B;color:#4285F4;"><i class="fab fa-google"></i></div>`;
        let mapsUrl = u.maps_url || '';
        if (!mapsUrl || mapsUrl.includes('4452102759555494648')) {
            mapsUrl = 'https://www.google.com/maps/search/?api=1&query=Codespark+Software+Development+Melapalayam+Tirunelveli&query_place_id=ChIJDR4_dxUTBDsReG0F-jMX19g';
        }

        return `
            <div class="gmb-history-card">
                ${imgHtml}
                <div class="gmb-history-content">
                    <div class="gmb-history-headline">${escapeHtml(u.headline || 'Google Maps Update')}</div>
                    <div class="gmb-history-snippet">${escapeHtml(u.content || '')}</div>
                    <div class="gmb-history-meta">
                        <span><i class="far fa-clock"></i> ${timeStr}</span>
                        <span class="status-pill success" style="font-size: 0.7rem;"><i class="fas fa-check-circle"></i> Live on Google Profile</span>
                        ${u.cta_url ? `<span style="color: #60a5fa;"><i class="fas fa-link"></i> ${escapeHtml(u.cta_type || 'Button')}: <a href="${u.cta_url}" target="_blank" rel="noopener noreferrer" style="color: #93c5fd; text-decoration: underline;">${escapeHtml(u.cta_url)}</a></span>` : ''}
                    </div>
                </div>
                <div style="display: flex; flex-direction: column; gap: 8px; flex-shrink: 0;">
                    <button class="btn btn-outline btn-sm" onclick="openGmbAssistantForUpdate(${actualIdx})" style="color: #60a5fa; border-color: rgba(96, 165, 250, 0.4); font-size: 0.75rem; padding: 4px 10px; display: inline-flex; align-items: center; gap: 6px;" title="Open Google Business Profile Assistant for this post">
                        <i class="fab fa-google"></i> Open Popup ↗
                    </button>
                    <a href="${mapsUrl}" target="_blank" rel="noopener noreferrer" onclick="window.open('${mapsUrl}', '_blank'); return false;" class="btn btn-outline btn-sm" style="color: #4285F4; border-color: rgba(66, 133, 244, 0.4); font-size: 0.75rem; text-decoration: none; padding: 4px 10px; display: inline-flex; align-items: center; gap: 6px;" title="Open Codespark on Google Maps">
                        <i class="fas fa-map-marked-alt"></i> View on Maps ↗
                    </a>
                    ${u.wp_link ? `<a href="${u.wp_link}" target="_blank" rel="noopener noreferrer" class="btn btn-outline btn-sm" style="color: #38bdf8; border-color: rgba(56, 189, 248, 0.3); font-size: 0.75rem; text-decoration: none; padding: 4px 10px; display: inline-flex; align-items: center; gap: 6px;"><i class="fab fa-wordpress"></i> Blog Link ↗</a>` : ''}
                    <button class="btn btn-outline btn-sm" onclick="deleteGmbUpdate('${u.id || ''}', ${actualIdx})" style="color: #ef4444; border-color: rgba(239, 68, 68, 0.3); font-size: 0.75rem; padding: 4px 10px; display: inline-flex; align-items: center; gap: 6px;">
                        <i class="fas fa-trash-alt"></i> Delete
                    </button>
                </div>
            </div>
        `;
    }).join('');

    if (wrap) wrap.style.display = 'flex';
    if (countEl) {
        countEl.innerHTML = `Showing <strong>${startIdx + 1}–${endIdx}</strong> of <strong>${total}</strong> published updates`;
    }

    if (controls) {
        if (totalPages <= 1) {
            controls.innerHTML = '';
            return;
        }

        let btnsHtml = `
            <button class="btn btn-outline btn-sm" style="padding:4px 10px; font-size:0.75rem;" onclick="changeGmbUpdatesPage(${GmbState.currentPage - 1})" ${GmbState.currentPage === 1 ? 'disabled style="opacity:0.4; cursor:not-allowed;"' : ''}>
                <i class="fas fa-chevron-left"></i> Prev
            </button>
        `;

        for (let p = 1; p <= totalPages; p++) {
            const isActive = p === GmbState.currentPage;
            btnsHtml += `
                <button class="btn ${isActive ? 'btn-primary' : 'btn-outline'} btn-sm" style="padding:4px 10px; font-size:0.75rem; min-width:32px; ${isActive ? 'font-weight:700;' : ''}" onclick="changeGmbUpdatesPage(${p})">
                    ${p}
                </button>
            `;
        }

        btnsHtml += `
            <button class="btn btn-outline btn-sm" style="padding:4px 10px; font-size:0.75rem;" onclick="changeGmbUpdatesPage(${GmbState.currentPage + 1})" ${GmbState.currentPage === totalPages ? 'disabled style="opacity:0.4; cursor:not-allowed;"' : ''}>
                Next <i class="fas fa-chevron-right"></i>
            </button>
        `;

        controls.innerHTML = btnsHtml;
    }
}

async function clearGmbUpdates() {
    if (!confirm('Are you sure you want to clear all published Google Map updates from this feed?')) {
        return;
    }

    const btn = document.getElementById('btnClearGmbUpdates');
    const origHtml = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Clearing...';
    }

    try {
        const res = await fetch('api.php?action=clear_gmb_updates', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        });
        const data = await res.json();
        if (data.success) {
            showToast(data.message || 'All Google Map updates cleared successfully!', 'success');
            GmbState.updates = [];
            GmbState.currentPage = 1;
            renderGmbUpdatesList();
        } else {
            showToast(data.error || 'Failed to clear updates', 'error');
        }
    } catch (e) {
        showToast('Network error clearing updates', 'error');
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = origHtml;
        }
    }
}

async function deleteGmbUpdate(id, idx) {
    if (!confirm('Are you sure you want to remove this update entry?')) return;

    try {
        const res = await fetch('api.php?action=delete_gmb_update', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, index: idx })
        });
        const data = await res.json();
        if (data.success) {
            showToast('Update removed from feed', 'success');
            loadGmbUpdates();
        } else {
            showToast(data.error || 'Failed to remove update', 'error');
        }
    } catch (e) {
        showToast('Network error deleting update', 'error');
    }
}


async function generateAiPostBody() {
    const topic = document.getElementById('postTitleInput')?.value?.trim() || '';
    if (!topic) {
        showToast('Please enter a Post Headline / Topic first.', 'warning');
        document.getElementById('postTitleInput')?.focus();
        return;
    }

    const btn = document.getElementById('btnGenBodyAi');
    const origHtml = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Writing Body...';
    }

    try {
        const res = await fetch('api.php?action=generate_post_body', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ topic })
        });
        const data = await res.json();
        if (data.success && data.post_body) {
            const bodyEl = document.getElementById('postContentInput');
            if (bodyEl) {
                bodyEl.value = data.post_body;
                bodyEl.style.borderColor = '#818cf8';
                setTimeout(() => { bodyEl.style.borderColor = ''; }, 1500);
            }
            if (data.meta_title && !document.getElementById('postMetaTitleInput')?.value) {
                document.getElementById('postMetaTitleInput').value = data.meta_title;
            }
            if (data.meta_description && !document.getElementById('postMetaDescInput')?.value) {
                document.getElementById('postMetaDescInput').value = data.meta_description;
            }
            if (data.meta_keywords && !document.getElementById('postMetaKeywordsInput')?.value) {
                document.getElementById('postMetaKeywordsInput').value = data.meta_keywords;
            }
            updateMetaCounters();
            showToast('Post Body & SEO tags generated with AI!', 'success');
        } else {
            showToast(data.error || 'Failed to generate post body', 'error');
        }
    } catch (e) {
        showToast('Network error generating post body', 'error');
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = origHtml;
        }
    }
}

// -------------------------------------------------------------
// WordPress Taxonomies, Landing Pages & Multi-Image Studio State
// -------------------------------------------------------------
let wpAllCategories = [];
let wpAllTags = [];
let wpSelectedCategoryIds = [];
let wpSelectedTagIds = [];

async function syncWpTaxonomies(force = false) {
    const catBox = document.getElementById('wpSelectedCategoriesContainer');
    const tagBox = document.getElementById('wpSelectedTagsContainer');
    const catSelect = document.getElementById('wpCategorySelector');
    const tagSelect = document.getElementById('wpTagSelector');

    try {
        const res = await fetch(`api.php?action=get_wp_taxonomies${force ? '&force=1' : ''}`);
        const data = await res.json();
        if (data.success) {
            wpAllCategories = data.categories || [];
            wpAllTags = data.tags || [];

            // Populate Category Select Dropdown
            if (catSelect) {
                catSelect.innerHTML = '<option value="">-- Choose Category to Add/Remove --</option>' +
                    wpAllCategories.map(c => `<option value="${c.id}">${c.name} (${c.count} posts)</option>`).join('');
            }

            // Populate Tag Select Dropdown
            if (tagSelect) {
                tagSelect.innerHTML = '<option value="">-- Choose Tag to Add/Remove --</option>' +
                    wpAllTags.map(t => `<option value="${t.id}">${t.name} (${t.count} posts)</option>`).join('');
            }

            // Auto-select at least 20 categories and 20 tags based on currently selected keyword
            const activeKw = document.getElementById('wpKeywordSelect')?.value || 'Online Internship Software Development';
            selectTaxonomiesForKeyword(activeKw, 20);

            if (force) showToast('Synced 100 categories and 100 tags live with codespark.online!', 'success');
        }
    } catch (e) {
        console.error('Taxonomy sync error', e);
    }
}

function selectTaxonomiesForKeyword(keyword, targetCount = 20) {
    if (!wpAllCategories || wpAllCategories.length === 0) return;
    const kwLower = (keyword || '').toLowerCase();
    const words = kwLower.replace(/[^a-z0-9 ]/gi, ' ').split(' ').filter(w => w.length > 2);
    
    let extraTerms = ['software', 'development', 'it company', 'company', 'web', 'app', 'tirunelveli', 'codespark', 'solutions'];
    if (kwLower.includes('intern') || kwLower.includes('train') || kwLower.includes('traning') || kwLower.includes('student')) {
        extraTerms = extraTerms.concat(['internship', 'training', 'college', 'student', 'career', 'education', 'project']);
    }
    if (kwLower.includes('cloud') || kwLower.includes('host') || kwLower.includes('server')) {
        extraTerms = extraTerms.concat(['cloud', 'server', 'hosting', 'infrastructure', 'network', 'online']);
    }
    if (kwLower.includes('app') || kwLower.includes('mobile') || kwLower.includes('android') || kwLower.includes('ios') || kwLower.includes('console')) {
        extraTerms = extraTerms.concat(['mobile', 'android', 'application', 'ios', 'play store', 'console']);
    }
    if (kwLower.includes('seo') || kwLower.includes('market')) {
        extraTerms = extraTerms.concat(['seo', 'marketing', 'digital marketing', 'analytics', 'presence', 'online']);
    }
    if (kwLower.includes('bill') || kwLower.includes('pos')) {
        extraTerms = extraTerms.concat(['billing', 'pos', 'accounting', 'invoice', 'gst', 'enterprise']);
    }
    const allSearchTerms = Array.from(new Set(words.concat(extraTerms)));

    // 1. Pick Categories (At least 20)
    let cats = [];
    wpAllCategories.forEach(c => {
        const cn = (c.name || '').toLowerCase();
        for (const term of allSearchTerms) {
            if (cn.includes(term)) {
                cats.push(c.id);
                break;
            }
        }
    });
    if (cats.length < targetCount) {
        const sortedCats = [...wpAllCategories].sort((a, b) => (b.count || 0) - (a.count || 0));
        for (const c of sortedCats) {
            if (!cats.includes(c.id)) {
                cats.push(c.id);
                if (cats.length >= targetCount) break;
            }
        }
    }
    wpSelectedCategoryIds = Array.from(new Set(cats)).slice(0, Math.max(targetCount, Math.min(25, cats.length)));

    // 2. Pick Tags (At least 20)
    let tags = [];
    wpAllTags.forEach(t => {
        const tn = (t.name || '').toLowerCase();
        for (const term of allSearchTerms) {
            if (tn.includes(term)) {
                tags.push(t.id);
                break;
            }
        }
    });
    if (tags.length < targetCount) {
        const sortedTags = [...wpAllTags].sort((a, b) => (b.count || 0) - (a.count || 0));
        for (const t of sortedTags) {
            if (!tags.includes(t.id)) {
                tags.push(t.id);
                if (tags.length >= targetCount) break;
            }
        }
    }
    wpSelectedTagIds = Array.from(new Set(tags)).slice(0, Math.max(targetCount, Math.min(25, tags.length)));

    renderSelectedCategories();
    renderSelectedTags();
}

function selectTopCategories(targetCount = 20) {
    if (!wpAllCategories || wpAllCategories.length === 0) return;
    const sorted = [...wpAllCategories].sort((a, b) => (b.count || 0) - (a.count || 0));
    wpSelectedCategoryIds = sorted.slice(0, targetCount).map(c => c.id);
    renderSelectedCategories();
    showToast(`Selected top ${wpSelectedCategoryIds.length} categories`, 'info');
}

function clearAllCategories() {
    wpSelectedCategoryIds = [];
    renderSelectedCategories();
    showToast('Categories cleared', 'info');
}

function selectTopTags(targetCount = 20) {
    if (!wpAllTags || wpAllTags.length === 0) return;
    const sorted = [...wpAllTags].sort((a, b) => (b.count || 0) - (a.count || 0));
    wpSelectedTagIds = sorted.slice(0, targetCount).map(t => t.id);
    renderSelectedTags();
    showToast(`Selected top ${wpSelectedTagIds.length} tags`, 'info');
}

function clearAllTags() {
    wpSelectedTagIds = [];
    renderSelectedTags();
    showToast('Tags cleared', 'info');
}

function renderSelectedCategories() {
    const container = document.getElementById('wpSelectedCategoriesContainer');
    const badge = document.getElementById('wpCategoryCountBadge');
    if (badge) {
        badge.textContent = `${wpSelectedCategoryIds.length} Selected`;
        badge.className = wpSelectedCategoryIds.length >= 20 ? 'status-pill success' : 'status-pill primary';
    }
    if (!container) return;

    if (wpSelectedCategoryIds.length === 0) {
        container.innerHTML = '<span style="font-size: 0.75rem; color: #64748B;">No categories selected. Click "Select Top 20" or pick below.</span>';
        return;
    }

    container.innerHTML = wpSelectedCategoryIds.map(id => {
        const cat = wpAllCategories.find(c => c.id === id) || { id, name: `Category #${id}` };
        return `<span style="display:inline-flex; align-items:center; gap:6px; background: rgba(59, 130, 246, 0.18); border: 1px solid rgba(59, 130, 246, 0.4); color: #93C5FD; font-size: 0.75rem; padding: 3px 8px; border-radius: 6px; font-weight: 500;">
            ${cat.name}
            <button type="button" onclick="removeCategory(${id})" style="background:none; border:none; color:#93C5FD; cursor:pointer; font-size:0.75rem; padding:0; margin-left:2px;">&times;</button>
        </span>`;
    }).join('');
}

function renderSelectedTags() {
    const container = document.getElementById('wpSelectedTagsContainer');
    const badge = document.getElementById('wpTagCountBadge');
    if (badge) {
        badge.textContent = `${wpSelectedTagIds.length} Selected`;
        badge.className = wpSelectedTagIds.length >= 20 ? 'status-pill success' : 'status-pill primary';
    }
    if (!container) return;

    if (wpSelectedTagIds.length === 0) {
        container.innerHTML = '<span style="font-size: 0.75rem; color: #64748B;">No tags selected. Click "Select Top 20" or pick below.</span>';
        return;
    }

    container.innerHTML = wpSelectedTagIds.map(id => {
        const tag = wpAllTags.find(t => t.id === id) || { id, name: `Tag #${id}` };
        return `<span style="display:inline-flex; align-items:center; gap:6px; background: rgba(16, 185, 129, 0.18); border: 1px solid rgba(16, 185, 129, 0.4); color: #6EE7B7; font-size: 0.75rem; padding: 3px 8px; border-radius: 6px; font-weight: 500;">
            ${tag.name}
            <button type="button" onclick="removeTag(${id})" style="background:none; border:none; color:#6EE7B7; cursor:pointer; font-size:0.75rem; padding:0; margin-left:2px;">&times;</button>
        </span>`;
    }).join('');
}

function toggleCategoryFromSelect(val) {
    if (!val) return;
    const catId = parseInt(val, 10);
    if (!wpSelectedCategoryIds.includes(catId)) {
        wpSelectedCategoryIds.push(catId);
    } else {
        wpSelectedCategoryIds = wpSelectedCategoryIds.filter(id => id !== catId);
    }
    renderSelectedCategories();
    document.getElementById('wpCategorySelector').value = '';
}

function removeCategory(catId) {
    wpSelectedCategoryIds = wpSelectedCategoryIds.filter(id => id !== catId);
    renderSelectedCategories();
}

function toggleTagFromSelect(val) {
    if (!val) return;
    const tagId = parseInt(val, 10);
    if (!wpSelectedTagIds.includes(tagId)) {
        wpSelectedTagIds.push(tagId);
    } else {
        wpSelectedTagIds = wpSelectedTagIds.filter(id => id !== tagId);
    }
    renderSelectedTags();
    document.getElementById('wpTagSelector').value = '';
}

function removeTag(tagId) {
    wpSelectedTagIds = wpSelectedTagIds.filter(id => id !== tagId);
    renderSelectedTags();
}

function onLandingPageSelectChange(url) {
    if (!url) return;
    const ctaInput = document.getElementById('postCtaUrlInput');
    if (ctaInput) ctaInput.value = url;
}

function setWpSecondaryImage(url, alt) {
    const input = document.getElementById('postSecondaryImageInput');
    if (input) input.value = url;
    updateWpSecondaryImagePreview(url);
    const altInput = document.getElementById('postSecondaryImageAltInput');
    if (altInput && alt) altInput.value = alt;
}

function updateWpSecondaryImagePreview(url) {
    const img = document.getElementById('wpSecondaryImagePreview');
    const placeholder = document.getElementById('wpSecondaryPlaceholder');
    if (!img) return;
    if (url && url.trim()) {
        img.src = url.trim();
        img.style.display = 'block';
        if (placeholder) placeholder.style.display = 'none';
    } else {
        img.style.display = 'none';
        if (placeholder) placeholder.style.display = 'flex';
    }
}

function quickSelectKeyword(keyword) {
    const selectEl = document.getElementById('wpKeywordSelect');
    if (!selectEl) return;
    
    let matched = false;
    for (let i = 0; i < selectEl.options.length; i++) {
        if (selectEl.options[i].value.trim().toLowerCase() === keyword.trim().toLowerCase()) {
            selectEl.selectedIndex = i;
            matched = true;
            break;
        }
    }
    if (!matched) {
        const opt = new Option(keyword, keyword, true, true);
        selectEl.add(opt);
    }
    onWpKeywordChange(keyword);
    showToast(`Target keyword selected: "${keyword}"`, 'info');
}

function onWpKeywordChange(keyword) {
    if (!keyword) return;
    const titleEl = document.getElementById('postTitleInput');
    if (titleEl) {
        titleEl.value = `Top ${keyword} - Codespark Software Development`;
    }
    const metaKwEl = document.getElementById('postMetaKeywordsInput');
    if (metaKwEl) {
        metaKwEl.value = `${keyword}, Codespark Software Development, Tirunelveli IT Company, Best Software Services`;
    }

    // Auto-match Primary Featured Image
    const kw = keyword.toLowerCase();
    let img = 'https://images.unsplash.com/photo-1547658719-da2b51169166?w=1200&auto=format&fit=crop';
    if (kw.includes('app') || kw.includes('mobile') || kw.includes('android') || kw.includes('ios') || kw.includes('play store') || kw.includes('console')) {
        img = 'https://images.unsplash.com/photo-1551650975-87deedd944c3?w=1200&auto=format&fit=crop';
    } else if (kw.includes('intern') || kw.includes('training') || kw.includes('traning') || kw.includes('student')) {
        img = 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=1200&auto=format&fit=crop';
    } else if (kw.includes('billing') || kw.includes('pos')) {
        img = 'https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?w=1200&auto=format&fit=crop';
    } else if (kw.includes('cloud') || kw.includes('server') || kw.includes('hosting')) {
        img = 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?w=1200&auto=format&fit=crop';
    } else if (kw.includes('seo') || kw.includes('marketing')) {
        img = 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=1200&auto=format&fit=crop';
    }
    setWpFeaturedImage(img, keyword);

    // Auto-match Secondary In-Content Image & Alt Text (Image 4 Style)
    let secImg = 'https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?w=1000&auto=format&fit=crop';
    let secAlt = `Professional ${keyword} | CodeSpark offers SEO, website development, and Android & iOS mobile app development services.`;
    if (kw.includes('app') || kw.includes('mobile') || kw.includes('android') || kw.includes('ios') || kw.includes('play store') || kw.includes('console')) {
        secImg = 'https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?w=1000&auto=format&fit=crop';
        secAlt = `${keyword} | CodeSpark builds scalable iOS, Android and Play Store mobile applications.`;
    } else if (kw.includes('intern') || kw.includes('training') || kw.includes('traning') || kw.includes('student')) {
        secImg = 'https://images.unsplash.com/photo-1531482615713-2afd69097998?w=1000&auto=format&fit=crop';
        secAlt = `${keyword} | CodeSpark offers practical live project software development mentorship for college students.`;
    } else if (kw.includes('billing') || kw.includes('pos')) {
        secImg = 'https://images.unsplash.com/photo-1556742049-0a67c5574f73?w=1000&auto=format&fit=crop';
        secAlt = 'GST Billing & POS Software | Fast barcode scanning, accounting and stock management by Codespark.';
    } else if (kw.includes('cloud') || kw.includes('server') || kw.includes('hosting')) {
        secImg = 'https://images.unsplash.com/photo-1544197150-b99a580bb7a8?w=1000&auto=format&fit=crop';
        secAlt = `${keyword} | Enterprise high-speed infrastructure, VPS and cloud server hosting by Codespark.`;
    } else if (kw.includes('seo') || kw.includes('marketing')) {
        secImg = 'https://images.unsplash.com/photo-1557838923-2985c318be48?w=1000&auto=format&fit=crop';
        secAlt = `${keyword} | Dominate Google 1st Page & Local Maps with Codespark SEO Solutions.`;
    } else if (kw.includes('web') || kw.includes('design') || kw.includes('developer') || kw.includes('site')) {
        secImg = 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=1000&auto=format&fit=crop';
        secAlt = `${keyword} | High performance modern responsive websites by Codespark.`;
    }
    setWpSecondaryImage(secImg, secAlt);

    // Auto-map Landing Page / Form URL
    let landingUrl = 'https://codespark.online/contact/';
    if (kw.includes('web') || kw.includes('design') || kw.includes('developer') || kw.includes('site')) {
        landingUrl = 'https://codespark.online/best-website-design-for-your-business/';
    } else if (kw.includes('bill') || kw.includes('pos')) {
        landingUrl = 'https://codespark.online/easy-billing-software/';
    } else if (kw.includes('intern') || kw.includes('student') || kw.includes('training') || kw.includes('traning')) {
        landingUrl = 'https://codespark.online/internship-for-students/';
    } else if (kw.includes('seo') || kw.includes('market')) {
        landingUrl = 'https://codespark.online/digital-marketing-for-your-business/';
    } else if (kw.includes('cloud') || kw.includes('server') || kw.includes('host')) {
        landingUrl = 'https://codespark.online/cloud-hosting-provider/';
    }
    const landingSelect = document.getElementById('wpLandingPageSelect');
    if (landingSelect) landingSelect.value = landingUrl;
    const ctaUrlInput = document.getElementById('postCtaUrlInput');
    if (ctaUrlInput) ctaUrlInput.value = landingUrl;

    // Auto-match at least 20 categories and 20 tags
    selectTaxonomiesForKeyword(keyword, 20);
}

function setWpFeaturedImage(url, label) {
    const input = document.getElementById('postImageInput');
    if (input) input.value = url;
    updateWpFeaturedImagePreview(url);
    if (label) showToast(`Featured image set for ${label}`, 'info');
}

function updateWpFeaturedImagePreview(url) {
    const img = document.getElementById('wpFeaturedImagePreview');
    const placeholder = document.getElementById('wpImagePlaceholder');
    if (!img) return;
    if (url && url.trim()) {
        img.src = url.trim();
        img.style.display = 'block';
        if (placeholder) placeholder.style.display = 'none';
    } else {
        img.style.display = 'none';
        if (placeholder) placeholder.style.display = 'flex';
    }
}

async function generateFullWpPostFromKeyword() {
    const selectEl = document.getElementById('wpKeywordSelect');
    const keyword = selectEl?.value || document.getElementById('postTitleInput')?.value?.trim();
    if (!keyword) {
        showToast('Please select a target keyword from the dropdown first!', 'warning');
        selectEl?.focus();
        return;
    }

    const btn = document.getElementById('btnGenFullWpPost');
    const origHtml = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating Post...';
    }

    showToast(`Crafting complete SEO article for "${keyword}"...`, 'info');

    try {
        const res = await fetch('api.php?action=generate_post_body', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ topic: keyword })
        });
        const data = await res.json();
        if (data.success) {
            if (data.title && document.getElementById('postTitleInput')) document.getElementById('postTitleInput').value = data.title;
            if (data.post_body && document.getElementById('postContentInput')) document.getElementById('postContentInput').value = data.post_body;
            if (data.meta_title && document.getElementById('postMetaTitleInput')) document.getElementById('postMetaTitleInput').value = data.meta_title;
            if (data.meta_description && document.getElementById('postMetaDescInput')) document.getElementById('postMetaDescInput').value = data.meta_description;
            if (data.meta_keywords && document.getElementById('postMetaKeywordsInput')) document.getElementById('postMetaKeywordsInput').value = data.meta_keywords;
            
            // Primary & Secondary Images
            if (data.image_url) setWpFeaturedImage(data.image_url, keyword);
            if (data.secondary_image_url) setWpSecondaryImage(data.secondary_image_url, data.secondary_image_alt);
            
            // Target Landing Page URL
            if (data.cta_url) {
                const lpSelect = document.getElementById('wpLandingPageSelect');
                if (lpSelect) lpSelect.value = data.cta_url;
                const ctaInput = document.getElementById('postCtaUrlInput');
                if (ctaInput) ctaInput.value = data.cta_url;
            }

            // Categories and Tags
            if (data.suggested_categories && data.suggested_categories.length > 0) {
                wpSelectedCategoryIds = data.suggested_categories;
                renderSelectedCategories();
            }
            if (data.suggested_tags && data.suggested_tags.length > 0) {
                wpSelectedTagIds = data.suggested_tags;
                renderSelectedTags();
            }

            updateMetaCounters();
            showToast(`Generated full post for "${keyword}"! 2+ authority links, secondary image & lead magnet added.`, 'success');
        } else {
            showToast(data.error || 'Failed to generate post content', 'error');
        }
    } catch (e) {
        showToast('Network error generating post content', 'error');
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = origHtml;
        }
    }
}

async function generateAiSeoMeta() {
    const topic = document.getElementById('postTitleInput')?.value?.trim() || '';
    const content = document.getElementById('postContentInput')?.value?.trim() || '';
    
    if (!topic && !content) {
        showToast('Please enter a Post Headline or Body first to generate SEO meta tags.', 'warning');
        return;
    }
    
    const btn = document.getElementById('btnGenMetaAi');
    const origHtml = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating Meta...';
    }
    
    try {
        const res = await fetch('api.php?action=generate_seo_meta', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ topic, content })
        });
        const data = await res.json();
        if (data.success) {
            if (document.getElementById('postMetaTitleInput')) document.getElementById('postMetaTitleInput').value = data.meta_title || '';
            if (document.getElementById('postMetaDescInput')) document.getElementById('postMetaDescInput').value = data.meta_description || '';
            if (document.getElementById('postMetaKeywordsInput')) document.getElementById('postMetaKeywordsInput').value = data.meta_keywords || '';
            
            // Auto-populate Post Body if empty
            const bodyEl = document.getElementById('postContentInput');
            if (bodyEl && !bodyEl.value.trim() && data.post_body) {
                bodyEl.value = data.post_body;
                bodyEl.style.borderColor = '#34d399';
                setTimeout(() => { bodyEl.style.borderColor = ''; }, 1500);
            }

            updateMetaCounters();
            showToast('Post Body & SEO Meta tags generated with Gemini AI!', 'success');
        } else {
            showToast(data.error || 'Failed to generate SEO meta tags', 'error');
        }
    } catch (e) {
        showToast('Network error generating SEO meta tags', 'error');
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = origHtml;
        }
    }
}

function updateMetaCounters() {
    const title = document.getElementById('postMetaTitleInput')?.value || '';
    const desc = document.getElementById('postMetaDescInput')?.value || '';
    const tCount = document.getElementById('metaTitleCount');
    const dCount = document.getElementById('metaDescCount');
    if (tCount) {
        tCount.textContent = `${title.length} / 60 chars`;
        tCount.style.color = title.length > 60 ? '#f87171' : 'var(--text-muted)';
    }
    if (dCount) {
        dCount.textContent = `${desc.length} / 160 chars`;
        dCount.style.color = desc.length > 160 ? '#f87171' : 'var(--text-muted)';
    }
}

async function submitSocialPost(publishNow = false) {
    const title = document.getElementById('postTitleInput')?.value?.trim() || '';
    const content = document.getElementById('postContentInput')?.value?.trim() || '';
    const imageUrl = document.getElementById('postImageInput')?.value?.trim() || '';
    const ctaType = document.getElementById('postCtaSelect')?.value || 'LEARN_MORE';
    const ctaUrl = document.getElementById('postCtaUrlInput')?.value?.trim() || '';
    const scheduledFor = document.getElementById('postScheduleInput')?.value || '';
    const metaTitle = document.getElementById('postMetaTitleInput')?.value?.trim() || title;
    const metaDesc = document.getElementById('postMetaDescInput')?.value?.trim() || '';
    const metaKeywords = document.getElementById('postMetaKeywordsInput')?.value?.trim() || '';

    const platforms = [];
    if (document.getElementById('platWordpress')?.checked !== false) platforms.push('wordpress');

    if (!content) {
        showToast('Please enter post content', 'error');
        return;
    }

    const btn = publishNow ? document.getElementById('btnPublishNow') : document.getElementById('btnScheduleLater');
    const origHtml = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${publishNow ? 'Publishing...' : 'Scheduling...'}`;
    }

    const secondaryImageUrl = document.getElementById('postSecondaryImageInput')?.value?.trim() || '';
    const secondaryImageAlt = document.getElementById('postSecondaryImageAltInput')?.value?.trim() || '';

    try {
        const res = await fetch('api.php?action=create_post', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                title, content, image_url: imageUrl,
                secondary_image_url: secondaryImageUrl,
                secondary_image_alt: secondaryImageAlt,
                categories: wpSelectedCategoryIds,
                tags: wpSelectedTagIds,
                platforms, cta_type: ctaType, cta_url: ctaUrl,
                scheduled_for: scheduledFor || undefined, publish_now: publishNow,
                meta_title: metaTitle, meta_description: metaDesc, meta_keywords: metaKeywords
            })
        });
        const data = await res.json();
        if (data.success) {
            showToast(data.message, 'success');
            document.getElementById('postTitleInput').value = '';
            document.getElementById('postContentInput').value = '';
            if (document.getElementById('postMetaTitleInput')) document.getElementById('postMetaTitleInput').value = '';
            if (document.getElementById('postMetaDescInput')) document.getElementById('postMetaDescInput').value = '';
            if (document.getElementById('postMetaKeywordsInput')) document.getElementById('postMetaKeywordsInput').value = '';
            updateMetaCounters();
            loadPosts();
            loadOverview();
        } else {
            showToast(data.error || 'Failed to create post', 'error');
        }
    } catch (e) {
        showToast('Network error creating post', 'error');
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = origHtml;
        }
    }
}

async function triggerAutoCreatePost(keyword = '') {
    if (!keyword) {
        keyword = document.getElementById('wpKeywordSelect')?.value || '';
    }
    const imageUrl = document.getElementById('postImageInput')?.value?.trim() || '';
    const secondaryImageUrl = document.getElementById('postSecondaryImageInput')?.value?.trim() || '';
    const secondaryImageAlt = document.getElementById('postSecondaryImageAltInput')?.value?.trim() || '';
    const ctaUrl = document.getElementById('postCtaUrlInput')?.value?.trim() || '';
    const btn = event?.target?.closest('button');
    const origHtml = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> AI Generating & Publishing...';
    }

    showToast(`Gemini AI is crafting post${keyword ? ' for "' + keyword + '"' : ''} & publishing to codespark.online...`, 'info');

    try {
        const res = await fetch('api.php?action=auto_create_and_publish', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                keyword,
                image_url: imageUrl,
                secondary_image_url: secondaryImageUrl,
                secondary_image_alt: secondaryImageAlt,
                cta_url: ctaUrl,
                categories: wpSelectedCategoryIds,
                tags: wpSelectedTagIds
            })
        });
        const data = await res.json();
        if (data.success) {
            showToast(`Published Live: "${data.title}"`, 'success');
            loadPosts();
            loadOverview();
            
            // Open published live post in new tab
            if (data.link) {
                window.open(data.link, '_blank');
            }
        } else {
            showToast(data.error || 'Failed to auto-publish post', 'error');
        }
    } catch (e) {
        showToast('Network error generating post', 'error');
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = origHtml;
        }
    }
}

async function loadCitations() {
    try {
        const res = await fetch('api.php?action=check_citations');
        const data = await res.json();
        if (!data.success) return;

        const tbody = document.getElementById('citationsTableBody');
        if (!tbody) return;

        tbody.innerHTML = data.citations.map(c => `
            <tr>
                <td><strong>${escapeHtml(c.directory_name)}</strong></td>
                <td><a href="${c.directory_url}" target="_blank" style="color: var(--secondary); text-decoration: none;"><i class="fas fa-external-link-alt"></i> View Directory</a></td>
                <td><span class="status-pill primary">${c.authority_score} DA</span></td>
                <td>
                    <span class="status-pill ${c.nap_status === 'synced' ? 'success' : (c.nap_status === 'mismatch' ? 'warning' : 'danger')}">
                        ${c.nap_status.toUpperCase()}
                    </span>
                </td>
                <td style="font-size: 0.8rem; color: var(--text-muted);">${escapeHtml(c.details || '')}</td>
                <td>
                    ${c.nap_status !== 'synced' ? `
                        <button class="btn btn-primary btn-sm" onclick="fixCitation(${c.id})">
                            <i class="fas fa-sync"></i> Re-Sync NAP
                        </button>
                    ` : `
                        <span style="color: var(--success); font-size: 0.8rem;"><i class="fas fa-check-circle"></i> Synced</span>
                    `}
                </td>
            </tr>
        `).join('');
    } catch (e) {
        console.error('Error loading citations:', e);
    }
}

async function fixCitation(id) {
    try {
        const res = await fetch('api.php?action=fix_citation', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, status: 'synced' })
        });
        const data = await res.json();
        if (data.success) {
            showToast('Citation re-synced with Google Business Profile NAP!', 'success');
            loadCitations();
            loadOverview();
        }
    } catch (e) {
        showToast('Error syncing citation', 'error');
    }
}

// 8. PROFILE & SETTINGS
async function saveBusinessProfile() {
    const payload = {
        name: document.getElementById('profName').value.trim(),
        category: document.getElementById('profCategory').value.trim(),
        address: document.getElementById('profAddress').value.trim(),
        city: document.getElementById('profCity').value.trim(),
        state: document.getElementById('profState').value.trim(),
        zip: document.getElementById('profZip').value.trim(),
        phone: document.getElementById('profPhone').value.trim(),
        website: document.getElementById('profWebsite').value.trim(),
        latitude: parseFloat(document.getElementById('profLat').value || 28.6315),
        longitude: parseFloat(document.getElementById('profLng').value || 77.2167),
        google_profile_id: document.getElementById('profPlaceId').value.trim(),
        google_place_id: document.getElementById('profPlaceId').value.trim(),
        target_keywords: document.getElementById('profKeywords').value.trim()
    };

    try {
        const res = await fetch('api.php?action=save_profile', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success) {
            showToast('Business Profile & NAP updated successfully!', 'success');
            loadOverview();
        } else {
            showToast(data.error || 'Failed to save profile', 'error');
        }
    } catch (e) {
        showToast('Error saving profile', 'error');
    }
}

async function loadSettings() {
    try {
        const res = await fetch('api.php?action=get_settings');
        const data = await res.json();
        if (!data.success) return;

        const s = data.settings || {};
        const g = data.google_oauth || {};
        if (document.getElementById('settingGmbClientId') && g.client_id) document.getElementById('settingGmbClientId').value = g.client_id;
        if (document.getElementById('settingGmbClientSecret') && g.client_secret) document.getElementById('settingGmbClientSecret').value = g.client_secret;
        if (document.getElementById('settingOpenAiKey')) document.getElementById('settingOpenAiKey').value = s.openai_api_key || '';
        if (document.getElementById('settingGeminiKey')) document.getElementById('settingGeminiKey').value = s.gemini_api_key || '';
        if (document.getElementById('settingGoogleMapsKey')) document.getElementById('settingGoogleMapsKey').value = s.google_maps_api_key || '';
        if (document.getElementById('settingWpUrl')) document.getElementById('settingWpUrl').value = s.wp_rest_url || '';
        if (document.getElementById('settingWpUser')) document.getElementById('settingWpUser').value = s.wp_rest_username || '';
        if (document.getElementById('settingWpPass')) document.getElementById('settingWpPass').value = s.wp_rest_app_password || '';
        if (document.getElementById('settingWebhookUrl')) document.getElementById('settingWebhookUrl').value = s.webhook_url || '';
        if (document.getElementById('settingAutoReply')) document.getElementById('settingAutoReply').checked = s.ai_auto_respond_reviews === '1';
    } catch (e) {
        console.error('Error loading settings:', e);
    }
}

async function saveSettings() {
    const payload = {
        client_id: document.getElementById('settingGmbClientId')?.value.trim() || '',
        client_secret: document.getElementById('settingGmbClientSecret')?.value.trim() || '',
        openai_api_key: document.getElementById('settingOpenAiKey')?.value.trim() || '',
        gemini_api_key: document.getElementById('settingGeminiKey')?.value.trim() || '',
        google_maps_api_key: document.getElementById('settingGoogleMapsKey')?.value.trim() || '',
        wp_rest_url: document.getElementById('settingWpUrl')?.value.trim() || '',
        wp_rest_username: document.getElementById('settingWpUser')?.value.trim() || '',
        wp_rest_app_password: document.getElementById('settingWpPass')?.value.trim() || '',
        webhook_url: document.getElementById('settingWebhookUrl')?.value.trim() || '',
        ai_auto_respond_reviews: document.getElementById('settingAutoReply')?.checked ? '1' : '0'
    };

    try {
        const res = await fetch('api.php?action=save_settings', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success) {
            showToast('API keys & Automation settings saved!', 'success');
        } else {
            showToast(data.error || 'Failed to save settings', 'error');
        }
    } catch (e) {
        showToast('Error saving settings', 'error');
    }
}

async function triggerCronRun() {
    try {
        showToast('Running background automation tasks...', 'info');
        const res = await fetch('cron.php');
        const data = await res.json();
        if (data.success) {
            showToast('Cron Automation completed successfully!', 'success');
            loadOverview();
        }
    } catch (e) {
        showToast('Cron execution finished', 'success');
    }
}

// Utility: Escape HTML
function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// Document Ready
document.addEventListener('DOMContentLoaded', () => {
    // Navigation Listeners
    document.querySelectorAll('.nav-item').forEach(el => {
        el.addEventListener('click', (e) => {
            e.preventDefault();
            switchTab(el.dataset.tab);
        });
    });

    // Populate Initial Data
    loadOverview();
    loadPosts();
    generateLocalSchema();
    syncWpTaxonomies(false);

    // Switch tab if present in URL (e.g. from Google OAuth callback)
    const urlParams = new URLSearchParams(window.location.search);
    const tabParam = urlParams.get('tab');
    if (tabParam) {
        switchTab(tabParam);
    }

    // Global Delegate for any View Live Buttons
    document.addEventListener('click', (e) => {
        const target = e.target.closest('button');
        if (target && target.textContent && target.textContent.includes('View Live')) {
            const row = target.closest('tr');
            if (row) {
                const slugEl = row.querySelector('td strong');
                if (slugEl && slugEl.textContent) {
                    const slugText = slugEl.textContent.trim().replace('/services/', '').replace(/^\//, '');
                    const targetUrl = `https://codespark.online/${slugText}/`;
                    window.open(targetUrl, '_blank');
                }
            }
        }
    });
});
