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
    currentKeyword: 'local seo services'
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

// 3. REVIEWS & AI AUTO-RESPONDER
async function loadReviews() {
    try {
        const res = await fetch('api.php?action=get_reviews');
        const data = await res.json();
        if (!data.success) return;

        const tbody = document.getElementById('reviewsTableBody');
        if (!tbody) return;

        if (data.reviews.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" class="text-muted" style="text-align:center; padding: 24px;">No reviews logged yet.</td></tr>`;
            return;
        }

        tbody.innerHTML = data.reviews.map(r => {
            const stars = '★'.repeat(r.rating) + '☆'.repeat(5 - r.rating);
            const isReplied = r.status === 'replied';
            return `
                <tr>
                    <td><strong>${escapeHtml(r.author_name)}</strong></td>
                    <td style="color: #F59E0B; font-size: 1rem; letter-spacing: 2px;">${stars}</td>
                    <td style="max-width: 320px;">
                        <div style="font-size: 0.85rem;">${escapeHtml(r.comment || '')}</div>
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
                        ${isReplied ? `
                            <button class="btn btn-outline btn-sm" onclick="openReplyModal(${r.id}, '${escapeHtml(r.author_name)}', '${escapeHtml(r.comment || '')}', '${escapeHtml(r.ai_reply || '')}')">
                                <i class="fas fa-edit"></i> Edit Reply
                            </button>
                        ` : `
                            <button class="btn btn-primary btn-sm" onclick="openReplyModal(${r.id}, '${escapeHtml(r.author_name)}', '${escapeHtml(r.comment || '')}', '')">
                                <i class="fas fa-magic"></i> Auto-Generate Reply
                            </button>
                        `}
                    </td>
                </tr>
            `;
        }).join('');

        // Review Collection Link & QR Code
        const placeId = (AppState.profile && AppState.profile.google_place_id) ? AppState.profile.google_place_id : 'ChIJnXaQs6cTBDsRqOlGcHkecRw';
        const reviewUrl = `https://search.google.com/local/writereview?placeid=${placeId}`;
        const linkInput = document.getElementById('reviewLinkInput');
        if (linkInput) linkInput.value = reviewUrl;

        const qrImg = document.getElementById('reviewQrCodeImg');
        if (qrImg) {
            qrImg.src = `https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=${encodeURIComponent(reviewUrl)}`;
        }

        const waBtn = document.getElementById('whatsappShareBtn');
        if (waBtn) {
            const waMsg = encodeURIComponent(`Hi! Could you please take 15 seconds to leave Codespark Software Development a 5-star Google review? It helps us immensely: ${reviewUrl}`);
            waBtn.href = `https://api.whatsapp.com/send?text=${waMsg}`;
        }
    } catch (e) {
        console.error('Error loading reviews:', e);
    }
}

function printCounterStandee() {
    const link = document.getElementById('reviewLinkInput')?.value || 'https://search.google.com/local/writereview?placeid=ChIJnXaQs6cTBDsRqOlGcHkecRw';
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

async function openReplyModal(reviewId, author, comment, existingReply) {
    document.getElementById('modalReviewId').value = reviewId;
    document.getElementById('modalAuthorName').textContent = author;
    document.getElementById('modalComment').textContent = comment || '(No comment provided)';
    const replyTextarea = document.getElementById('modalReplyContent');

    if (existingReply) {
        replyTextarea.value = existingReply;
    } else {
        replyTextarea.value = 'Generating AI response with targeted local SEO keywords...';
        try {
            const res = await fetch(`api.php?action=generate_review_reply&review_id=${reviewId}`);
            const data = await res.json();
            if (data.success) {
                replyTextarea.value = data.reply;
                document.getElementById('modalKeywordBadge').textContent = `Injected Keyword: "${data.targeted_keyword}"`;
            } else {
                replyTextarea.value = 'Thank you for your review!';
            }
        } catch (e) {
            replyTextarea.value = 'Thank you for choosing our local business!';
        }
    }

    document.getElementById('replyModal').style.display = 'flex';
}

function closeReplyModal() {
    document.getElementById('replyModal').style.display = 'none';
}

async function submitReviewReply() {
    const reviewId = document.getElementById('modalReviewId').value;
    const reply = document.getElementById('modalReplyContent').value.trim();

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
            showToast('Reply published & synced to Google Business Profile!', 'success');
            closeReplyModal();
            loadReviews();
            loadOverview();
        } else {
            showToast(data.error || 'Failed to save reply', 'error');
        }
    } catch (e) {
        showToast('Network error saving reply', 'error');
    }
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
            <td style="display:flex; gap: 6px;">
                <button class="btn btn-outline btn-sm" onclick="showLocalPageBlueprint(${idx})">
                    <i class="fas fa-eye"></i> Blueprint
                </button>
                <button class="btn btn-success btn-sm" onclick="publishPageToWordPress(${idx}, this)">
                    <i class="fab fa-wordpress"></i> Publish to WP
                </button>
            </td>
        </tr>
    `).join('');

    window.generatedLocalPages = pages;
}

async function publishPageToWordPress(index, btn) {
    const page = window.generatedLocalPages[index];
    if (!page) return;

    const origHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Publishing...';

    const content = `
<h2>${page.h1}</h2>
<p>${page.meta_description}</p>
<h3>Frequently Asked Questions</h3>
<p><strong>${page.faqs[0].q}</strong></p>
<p>${page.faqs[0].a}</p>
<p>Visit <strong>Codespark Software Development</strong> in Melapalayam, Tirunelveli or call +91 81108 99000.</p>
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
            btn.className = 'btn btn-outline btn-sm';
            btn.innerHTML = `<i class="fas fa-external-link-alt"></i> View Live`;
            btn.onclick = () => window.open(data.link, '_blank');
        } else {
            showToast(data.error || 'Failed to publish to WordPress', 'error');
            btn.disabled = false;
            btn.innerHTML = origHtml;
        }
    } catch (e) {
        showToast('Network error publishing to WordPress', 'error');
        btn.disabled = false;
        btn.innerHTML = origHtml;
    }
}

// 7. SOCIAL & CITATION SYNDICATOR
async function loadPosts() {
    try {
        const res = await fetch('api.php?action=get_posts');
        const data = await res.json();
        if (data.success) {
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
                            <div style="display:flex; align-items: center; gap: 8px;">
                                ${wpLinkHtml}
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
            updateMetaCounters();
            showToast('SEO Meta tags generated successfully with Gemini AI!', 'success');
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
    if (document.getElementById('platWordpress')?.checked) platforms.push('wordpress');
    if (document.getElementById('platGmb')?.checked) platforms.push('gmb');
    if (document.getElementById('platFacebook')?.checked) platforms.push('facebook');
    if (document.getElementById('platInstagram')?.checked) platforms.push('instagram');
    if (document.getElementById('platYoutube')?.checked) platforms.push('youtube');
    if (document.getElementById('platLinkedin')?.checked) platforms.push('linkedin');
    if (document.getElementById('platTwitter')?.checked) platforms.push('twitter');

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

    try {
        const res = await fetch('api.php?action=create_post', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                title, content, image_url: imageUrl, platforms, cta_type: ctaType, cta_url: ctaUrl,
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
            if (document.getElementById('postImageInput')) document.getElementById('postImageInput').value = '';
            if (document.getElementById('postScheduleInput')) document.getElementById('postScheduleInput').value = '';
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
    const btn = event?.target?.closest('button');
    const origHtml = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> AI Generating & Publishing...';
    }

    showToast('Gemini AI is generating & publishing post to codespark.online...', 'info');

    try {
        const res = await fetch('api.php?action=auto_create_and_publish', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ keyword })
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

    // Switch tab if present in URL (e.g. from Google OAuth callback)
    const urlParams = new URLSearchParams(window.location.search);
    const tabParam = urlParams.get('tab');
    if (tabParam) {
        switchTab(tabParam);
    }
});
