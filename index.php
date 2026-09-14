<?php
/**
 * LocalRank Pro - Automated Local & Web SEO Command Center
 */
require_once __DIR__ . '/config.php';

$config = loadConfig();
$profile = $config['business'] ?? [];
$googleOAuth = $config['google_oauth'] ?? [];
$isGoogleConnected = !empty($googleOAuth['is_connected']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Codespark Software Development - SEO Command Center</title>
    
    <!-- Google Fonts & Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Leaflet CSS for Geo-Grid Maps -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <!-- Custom Cyber & Modern Glassmorphism Styling -->
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
</head>
<body>

<div class="app-container">
    
    <!-- SIDEBAR NAVIGATION -->
    <aside class="sidebar">
        <div class="brand">
            <div class="brand-icon">
                <i class="fas fa-satellite-dish"></i>
            </div>
            <div class="brand-text">
                <h1>Codespark</h1>
                <span>Software Development</span>
            </div>
        </div>

        <div class="nav-section-title">Core Modules</div>
        <ul class="nav-links">
            <li class="nav-item active" data-tab="overview">
                <i class="fas fa-tachometer-alt"></i>
                <span>Command Center</span>
            </li>
            <li class="nav-item" data-tab="geo-grid">
                <i class="fas fa-map-marked-alt"></i>
                <span>Google Map Top 3</span>
                <span class="badge">Geo-Grid</span>
            </li>
            <li class="nav-item" data-tab="reviews">
                <i class="fas fa-star-half-alt"></i>
                <span>Review Auto-AI</span>
                <span class="badge" id="sidebarPendingReviews">AI Active</span>
            </li>
            <li class="nav-item" data-tab="gmb-updates">
                <i class="fab fa-google" style="color: #4285F4;"></i>
                <span>Google Map Updates</span>
                <span class="badge" style="background: rgba(66, 133, 244, 0.2); color: #4285F4;">Posts</span>
            </li>
            <li class="nav-item" data-tab="website-seo">
                <i class="fas fa-globe"></i>
                <span>Website SEO & Schema</span>
            </li>
            <li class="nav-item" data-tab="social">
                <i class="fab fa-wordpress" style="color: #21759B;"></i>
                <span>WordPress Posts</span>
            </li>
        </ul>

        <div class="nav-section-title">Management</div>
        <ul class="nav-links">
            <li class="nav-item" data-tab="profile">
                <i class="fas fa-id-card"></i>
                <span>Business & NAP</span>
            </li>
            <li class="nav-item" data-tab="settings">
                <i class="fas fa-sliders-h"></i>
                <span>Connect & Automate</span>
            </li>
        </ul>

        <div class="sidebar-footer">
            <div class="connection-status">
                <div class="pulse-dot"></div>
                <div>
                    <strong style="color: #fff; font-size: 0.8rem;">Sync Engine Active</strong>
                    <div style="color: var(--text-dim); font-size: 0.7rem;">Google Maps & Site Linked</div>
                </div>
            </div>
        </div>
    </aside>

    <!-- MAIN CONTENT VIEWPORT -->
    <main class="main-content">
        
        <!-- TOP HEADER BAR -->
        <header class="top-bar">
            <div class="page-title">
                <h2><span class="biz-name-display"><?= htmlspecialchars($profile['name'] ?? 'Codespark Software Development') ?></span></h2>
                <p><i class="fas fa-map-marker-alt" style="color: var(--secondary);"></i> <span class="biz-address-display"><?= htmlspecialchars($profile['address'] ?? '') ?></span> • Top 3 Map Pack Optimization</p>
            </div>
            <div class="top-actions">
                <button class="btn btn-outline btn-sm" onclick="triggerCronRun()">
                    <i class="fas fa-sync"></i> Run Automations Now
                </button>
                <button class="btn btn-primary btn-sm" onclick="switchTab('geo-grid')">
                    <i class="fas fa-crosshairs"></i> Launch Geo-Grid
                </button>
            </div>
        </header>

        <!-- ==========================================
             TAB 1: COMMAND CENTER / OVERVIEW
             ========================================== -->
        <section id="tab-overview" class="tab-content active">
            <!-- KPI CARDS -->
            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-header">
                        <span class="kpi-title">Map Top 3 Dominance</span>
                        <div class="kpi-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--success);">
                            <i class="fas fa-trophy"></i>
                        </div>
                    </div>
                    <div class="kpi-val" id="kpiDominance" style="color: var(--success);">--%</div>
                    <div class="kpi-sub up"><i class="fas fa-arrow-up"></i> Within 3-mile radius</div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-header">
                        <span class="kpi-title">Avg. Map Position</span>
                        <div class="kpi-icon" style="background: rgba(99, 102, 241, 0.15); color: var(--primary);">
                            <i class="fas fa-map-pin"></i>
                        </div>
                    </div>
                    <div class="kpi-val" id="kpiAvgRank" style="color: #A5B4FC;">#--</div>
                    <div class="kpi-sub"><i class="fas fa-check-circle"></i> Local Map Pack Zone</div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-header">
                        <span class="kpi-title">Google Rating</span>
                        <div class="kpi-icon" style="background: rgba(245, 158, 11, 0.15); color: var(--warning);">
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                    <div class="kpi-val" id="kpiRating" style="color: #FBBF24;">-- ★</div>
                    <div class="kpi-sub" id="kpiReviewsCount">Loading reviews...</div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-header">
                        <span class="kpi-title">NAP Citation Sync</span>
                        <div class="kpi-icon" style="background: rgba(6, 182, 212, 0.15); color: var(--secondary);">
                            <i class="fas fa-sync-alt"></i>
                        </div>
                    </div>
                    <div class="kpi-val" id="kpiCitationHealth" style="color: var(--secondary);">--%</div>
                    <div class="kpi-sub"><i class="fas fa-shield-alt"></i> Across top directories</div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-header">
                        <span class="kpi-title">Technical SEO Score</span>
                        <div class="kpi-icon" style="background: rgba(59, 130, 246, 0.15); color: var(--info);">
                            <i class="fas fa-heartbeat"></i>
                        </div>
                    </div>
                    <div class="kpi-val" id="kpiSiteHealth" style="color: #60A5FA;">--/100</div>
                    <div class="kpi-sub"><i class="fas fa-code"></i> Schema & On-Page Health</div>
                </div>
            </div>

            <!-- AUTOMATION QUICK ACTIONS -->
            <div class="card" style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(6, 182, 212, 0.05)); border: 1px solid var(--border-active);">
                <div class="card-header" style="border:none; margin-bottom: 8px; padding-bottom: 0;">
                    <div>
                        <h3><i class="fas fa-bolt" style="color: #FBBF24;"></i> Automated SEO Engine Launchpad</h3>
                        <p>1-Click triggers to automate your Local Google Maps and Organic First-Page rankings.</p>
                    </div>
                </div>
                <div style="display:flex; flex-wrap: wrap; gap: 12px; margin-top: 14px;">
                    <button class="btn btn-primary" id="btnLaunchpadAutoPost" onclick="triggerAutoCreatePost()">
                        <i class="fas fa-robot"></i> 1-Click AI Auto-Post to Website
                    </button>
                    <button class="btn btn-outline" onclick="switchTab('gmb-updates')" style="border-color: rgba(66, 133, 244, 0.5); color: #60a5fa;">
                        <i class="fab fa-google"></i> Google Map Updates Studio
                    </button>
                    <button class="btn btn-outline" onclick="switchTab('geo-grid')">
                        <i class="fas fa-map-marked-alt"></i> Run 5x5 Geo-Grid Scan
                    </button>
                    <button class="btn btn-success" onclick="switchTab('reviews')">
                        <i class="fas fa-magic"></i> Auto-Reply All Reviews
                    </button>
                    <button class="btn btn-outline" onclick="switchTab('website-seo')">
                        <i class="fas fa-stethoscope"></i> Crawl Website For SEO Errors
                    </button>
                    <button class="btn btn-outline" onclick="switchTab('social')">
                        <i class="fas fa-paper-plane"></i> Broadcast Social Post
                    </button>
                </div>
            </div>

            <!-- 2-COLUMN SPLIT: RECENT RANKINGS & UPCOMING POSTS -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px;">
                <!-- Recent Rankings -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-line" style="color: var(--success);"></i> Live Map Pack Keyword Positions</h3>
                        <button class="btn btn-outline btn-sm" onclick="switchTab('geo-grid')">View Heatmap</button>
                    </div>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Keyword</th>
                                    <th>Rank</th>
                                    <th>Current Leader</th>
                                    <th>Tracked At</th>
                                </tr>
                            </thead>
                            <tbody id="recentRanksBody">
                                <tr><td colspan="4" class="text-muted" style="text-align:center; padding: 20px;">Loading ranking data...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Upcoming Scheduled Posts -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-calendar-alt" style="color: var(--secondary);"></i> Google Maps & Social Queue</h3>
                        <div style="display:flex; gap: 8px;">
                            <button class="btn btn-outline btn-sm" onclick="switchTab('gmb-updates')" style="color: #60a5fa; border-color: rgba(66,133,244,0.4);"><i class="fab fa-google"></i> Google Update</button>
                            <button class="btn btn-outline btn-sm" onclick="switchTab('social')">New Post</button>
                        </div>
                    </div>
                    <div id="upcomingPostsList">
                        <p class="text-muted" style="padding: 16px 0;">Loading scheduled updates...</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==========================================
             TAB 2: GOOGLE MAP TOP 3 (GEO-GRID TRACKER)
             ========================================== -->
        <section id="tab-geo-grid" class="tab-content">
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3><i class="fas fa-map-marked-alt" style="color: var(--primary);"></i> Google Maps Local Geo-Grid Heatmap</h3>
                        <p>Track your physical office's ranking across 9 to 25 neighborhood nodes within your city.</p>
                    </div>
                    <div>
                        <span class="status-pill success" id="gridTop3Rate" style="font-size: 0.85rem;">Calculating...</span>
                    </div>
                </div>

                <!-- Control Bar -->
                <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 14px; margin-bottom: 18px;">
                    <div class="form-row" style="align-items: flex-end;">
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">Search Keyword</label>
                            <select class="form-control" id="gridKeywordSelect" onchange="loadGeoGrid()">
                                <?php 
                                $kws = explode(',', $profile['target_keywords'] ?? 'software company in tirunelveli, web development company tirunelveli');
                                foreach ($kws as $kw): 
                                    $kw = trim($kw);
                                    if (empty($kw)) continue;
                                ?>
                                    <option value="<?= htmlspecialchars($kw) ?>"><?= htmlspecialchars($kw) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">Grid Density</label>
                            <select class="form-control" id="gridSizeSelect">
                                <option value="3">3x3 Grid (9 Points)</option>
                                <option value="5" selected>5x5 Grid (25 Points)</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">Radius Span</label>
                            <select class="form-control" id="gridRadiusSelect">
                                <option value="2.0">2 km (Immediate Neighborhood)</option>
                                <option value="4.0">4 km (City Core)</option>
                                <option value="8.0">8 km (Metropolitan Area)</option>
                                <option value="15.0">15 km (Greater Tirunelveli)</option>
                                <option value="30.0">30 km (District Hub: Ambasamudram / Nanguneri)</option>
                                <option value="45.0">45 km (Regional: Tenkasi / Valliyur)</option>
                                <option value="60.0" selected>60 km (Full 60km Dominance: Thoothukudi / Kovilpatti)</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <button class="btn btn-primary" style="width: 100%;" onclick="runGeoGridScan()">
                                <i class="fas fa-radar"></i> Run Geo-Grid Scan
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Leaflet Interactive Map Container -->
                <div id="geoMap"></div>

                <!-- Map Legend -->
                <div style="display:flex; justify-content:center; gap: 24px; margin-top: 16px; font-size: 0.82rem;">
                    <div style="display:flex; align-items:center; gap: 8px;">
                        <div style="width: 16px; height: 16px; border-radius: 50%; background: #6366F1; border: 2px solid #67E8F9;"></div>
                        <span>Your Physical Office Center</span>
                    </div>
                    <div style="display:flex; align-items:center; gap: 8px;">
                        <div style="width: 16px; height: 16px; border-radius: 50%; background: #10B981;"></div>
                        <span><strong>#1 - #3</strong> (Google Map Pack Dominator)</span>
                    </div>
                    <div style="display:flex; align-items:center; gap: 8px;">
                        <div style="width: 16px; height: 16px; border-radius: 50%; background: #F59E0B;"></div>
                        <span><strong>#4 - #9</strong> (Page 1 Opportunity)</span>
                    </div>
                    <div style="display:flex; align-items:center; gap: 8px;">
                        <div style="width: 16px; height: 16px; border-radius: 50%; background: #EF4444;"></div>
                        <span><strong>#10+</strong> (Needs Citations / Reviews)</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==========================================
             TAB 3: REVIEW AUTO-AI HUB
             ========================================== -->
        <section id="tab-reviews" class="tab-content">
            <!-- Review Generation Link & QR Code Banner -->
            <div class="card" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(99, 102, 241, 0.05));">
                <div class="card-header" style="border:none; margin-bottom: 12px; padding-bottom: 0;">
                    <div>
                        <h3><i class="fas fa-qrcode" style="color: var(--success);"></i> Customer Review Generation Booster</h3>
                        <p>Sending fast review links to recent clients directly increases your Google Map Pack ranking authority.</p>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: auto 1fr; gap: 24px; align-items: center; padding: 12px 0;">
                    <div style="background: #fff; padding: 10px; border-radius: 10px; display: inline-block;">
                        <img id="reviewQrCodeImg" src="https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=https://example.com" alt="Google Review QR Code" style="width: 140px; height: 140px; display: block;">
                    </div>
                    <div>
                        <label class="form-label">Direct Google Maps Review URL (Share via WhatsApp / SMS)</label>
                        <div style="display:flex; gap: 8px; margin-bottom: 14px; flex-wrap: wrap;">
                            <input type="text" class="form-control" id="reviewLinkInput" oninput="updateReviewQrLive()" style="font-family: monospace; font-size: 0.85rem; flex: 1; min-width: 260px;" placeholder="https://maps.google.com/?cid=4452102759555494648">
                            <button class="btn btn-outline" onclick="navigator.clipboard.writeText(document.getElementById('reviewLinkInput').value); showToast('Review link copied!');">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                            <button class="btn btn-primary" onclick="saveCustomReviewLink()">
                                <i class="fas fa-save"></i> Save Link
                            </button>
                        </div>
                        <div style="display:flex; gap: 10px;">
                            <a class="btn btn-success btn-sm" id="whatsappShareBtn" target="_blank" href="https://api.whatsapp.com/send?text=Hi!%20Could%20you%20take%2015%20seconds%20to%20leave%20us%20a%20Google%20review?">
                                <i class="fab fa-whatsapp"></i> Share on WhatsApp
                            </a>
                            <button class="btn btn-outline btn-sm" onclick="printCounterStandee()">
                                <i class="fas fa-print"></i> Print Counter Standee
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Incoming Reviews Table -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3><i class="fas fa-comments" style="color: #FBBF24;"></i> Incoming Customer Reviews</h3>
                        <p>Google prioritizes business profiles that respond quickly with localized service keywords.</p>
                    </div>
                    <div style="display:flex; flex-wrap: wrap; gap: 8px; align-items: center;">
                        <a id="viewAllGoogleReviewsBtn" href="https://search.google.com/local/reviews?placeid=ChIJDR4_dxUTBDsReG0F-jMX19g" target="_blank" class="btn btn-outline btn-sm" style="color: #4285F4; border-color: rgba(66,133,244,0.4);">
                            <i class="fab fa-google"></i> View All 41 on Google <i class="fas fa-external-link-alt" style="font-size: 10px; margin-left: 2px;"></i>
                        </a>
                        <button class="btn btn-primary btn-sm" onclick="openAddReviewModal()"><i class="fas fa-plus"></i> Add Real Review</button>
                        <button class="btn btn-outline btn-sm" onclick="loadReviews()"><i class="fas fa-sync"></i> Refresh</button>
                    </div>
                </div>

                <!-- Review Filter & Pagination Toolbar -->
                <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center; margin-bottom: 16px; padding: 12px 16px; background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: var(--radius-sm);">
                    <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center; flex: 1; min-width: 280px;">
                        <div style="position: relative; flex: 1; min-width: 200px;">
                            <i class="fas fa-search" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--text-dim); font-size: 0.8rem;"></i>
                            <input type="text" id="reviewSearchInput" class="form-control" placeholder="Search by customer name or keywords..." style="padding-left: 32px; font-size: 0.85rem; height: 36px;" oninput="handleReviewFilterChange()">
                        </div>
                        <select id="reviewRatingFilter" class="form-control" style="width: auto; font-size: 0.85rem; height: 36px;" onchange="handleReviewFilterChange()">
                            <option value="all">All Star Ratings</option>
                            <option value="5">★★★★★ (5 Stars)</option>
                            <option value="4">★★★★☆ (4 Stars)</option>
                            <option value="3">★★★☆☆ (3 Stars)</option>
                        </select>
                        <select id="reviewStatusFilter" class="form-control" style="width: auto; font-size: 0.85rem; height: 36px;" onchange="handleReviewFilterChange()">
                            <option value="all">All Reply Statuses</option>
                            <option value="pending">Reply Pending</option>
                            <option value="replied">Synced to Google Maps</option>
                        </select>
                    </div>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <span style="font-size: 0.8rem; color: var(--text-dim);">Show:</span>
                        <select id="reviewPageSize" class="form-control" style="width: auto; font-size: 0.85rem; height: 36px;" onchange="handleReviewPageSizeChange()">
                            <option value="5" selected>5 per page</option>
                            <option value="10">10 per page</option>
                            <option value="25">25 per page</option>
                            <option value="all">Show All</option>
                        </select>
                    </div>
                </div>

                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Rating</th>
                                <th>Review & AI SEO Reply</th>
                                <th>Google Sync</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="reviewsTableBody">
                            <tr><td colspan="5" class="text-muted" style="text-align:center; padding: 24px;">Loading customer reviews...</td></tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer Bar -->
                <div id="reviewsPaginationBar" style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; margin-top: 16px; padding-top: 14px; border-top: 1px solid var(--border-color); gap: 12px;">
                    <div id="reviewsCountInfo" style="font-size: 0.85rem; color: var(--text-dim);">
                        Loading review count...
                    </div>
                    <div id="reviewsPaginationControls" style="display: flex; gap: 6px; align-items: center;">
                        <!-- Rendered by app.js -->
                    </div>
                </div>
            </div>
        </section>

        <!-- ==========================================
             TAB 4: WEBSITE SEO & SCHEMA (PAGE 1 ENGINE)
             ========================================== -->
        <section id="tab-website-seo" class="tab-content">
            <!-- Live Site Crawler -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3><i class="fas fa-stethoscope" style="color: var(--secondary);"></i> Live Website SEO & Technical Auditor</h3>
                        <p>Ensure your website satisfies Google's on-page criteria to rank on Page 1.</p>
                    </div>
                </div>

                <div style="display:flex; gap: 12px; margin-bottom: 20px;">
                    <input type="url" class="form-control" id="auditUrlInput" value="<?= htmlspecialchars($profile['website'] ?? 'https://example.com') ?>" placeholder="https://yourwebsite.com">
                    <button class="btn btn-primary" id="btnRunAudit" onclick="runWebsiteAudit()">
                        <i class="fas fa-bolt"></i> Run Live Technical Audit
                    </button>
                </div>

                <!-- Audit Results Area -->
                <div id="auditResultsCard" style="display:none; background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 20px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 16px;">
                        <div>
                            <h4 style="font-size: 1.1rem; margin-bottom: 4px;">Website SEO Health Assessment</h4>
                            <p style="color: var(--text-muted); font-size: 0.82rem;">Audit based on Google Page 1 On-Page standards</p>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 2.2rem; font-weight: 800;" id="auditScoreVal">--</div>
                            <span class="status-pill primary">Overall Score</span>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; margin-bottom: 20px;">
                        <div style="background: var(--bg-card); padding: 12px 16px; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                            <div class="text-dim" style="font-size: 0.75rem;">TITLE TAG</div>
                            <div id="auditMetaTitle" style="font-weight: 600; font-size: 0.9rem; margin-top: 2px;">--</div>
                            <div id="auditMetaTitleLen" style="font-size: 0.72rem; color: var(--secondary); margin-top: 4px;">-- chars</div>
                        </div>
                        <div style="background: var(--bg-card); padding: 12px 16px; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                            <div class="text-dim" style="font-size: 0.75rem;">META DESCRIPTION</div>
                            <div id="auditMetaDesc" style="font-size: 0.85rem; margin-top: 2px; color: var(--text-muted);">--</div>
                            <div id="auditMetaDescLen" style="font-size: 0.72rem; color: var(--secondary); margin-top: 4px;">-- chars</div>
                        </div>
                        <div style="background: var(--bg-card); padding: 12px 16px; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                            <div class="text-dim" style="font-size: 0.75rem;">PRIMARY H1 HEADING</div>
                            <div id="auditH1" style="font-weight: 600; font-size: 0.88rem; margin-top: 2px;">--</div>
                            <div id="auditH1Count" style="font-size: 0.72rem; color: var(--text-dim); margin-top: 4px;">-- tag</div>
                        </div>
                        <div style="background: var(--bg-card); padding: 12px 16px; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                            <div class="text-dim" style="font-size: 0.75rem;">IMAGES & SPEED</div>
                            <div id="auditImages" style="font-size: 0.85rem; margin-top: 2px;">--</div>
                            <div id="auditSpeed" style="font-size: 0.72rem; color: var(--success); margin-top: 4px;">-- ms</div>
                        </div>
                    </div>

                    <h4 style="font-size: 0.95rem; margin-bottom: 10px;">Actionable Fixes & Issues</h4>
                    <div id="auditIssuesList"></div>
                </div>
            </div>

            <!-- Structured Data Schema Generator (JSON-LD) -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3><i class="fas fa-code" style="color: #A5B4FC;"></i> 1-Click LocalBusiness Schema (JSON-LD) Generator</h3>
                        <p>Injecting structured schema is the single most effective way to connect your website with Google Maps.</p>
                    </div>
                    <div>
                        <button class="btn btn-success btn-sm" onclick="copySchemaCode()">
                            <i class="fas fa-copy"></i> Copy Script Tag
                        </button>
                    </div>
                </div>

                <div class="form-row" style="margin-bottom: 14px;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label">Schema Type</label>
                        <select class="form-control" id="schemaTypeSelect" onchange="generateLocalSchema()">
                            <option value="LocalBusiness" selected>LocalBusiness (General)</option>
                            <option value="ProfessionalService">ProfessionalService (Agency, Consultant)</option>
                            <option value="LegalService">LegalService / Law Firm</option>
                            <option value="MedicalBusiness">MedicalBusiness / Clinic</option>
                            <option value="HomeAndConstructionBusiness">Home & Construction Services</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom:0; display:flex; align-items:flex-end;">
                        <button class="btn btn-outline" style="width:100%;" onclick="generateLocalSchema()">
                            <i class="fas fa-sync"></i> Regenerate Schema
                        </button>
                    </div>
                </div>

                <div class="code-box">
                    <button class="copy-btn" onclick="copySchemaCode()"><i class="fas fa-copy"></i> Copy</button>
                    <pre id="schemaCodeDisplay">// Click "Regenerate Schema" or wait for load...</pre>
                </div>
            </div>

            <!-- Programmatic Local Landing Page Blueprints -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3><i class="fas fa-layer-group" style="color: var(--secondary);"></i> Programmatic Local Landing Pages</h3>
                        <p>Generate high-intent location pages (e.g. <code>[Service] in [City/Neighborhood]</code>) to dominate every local zip code.</p>
                    </div>
                </div>

                <div class="form-row" style="margin-bottom: 16px;">
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-cogs" style="color: var(--secondary);"></i> Services (comma separated)</label>
                        <input type="text" class="form-control" id="programmaticServices" value="Software Company, Mobile App Development, Billing Software, Web Design, Custom ERP Development">
                    </div>
                    <div class="form-group">
                        <div style="display:flex; justify-content:space-between; align-items:baseline;">
                            <label class="form-label"><i class="fas fa-map-marker-alt" style="color: #ef4444;"></i> Target Cities / Localities (Any City or Region)</label>
                            <span style="font-size: 0.75rem; color: #10b981; font-weight: 500;"><i class="fas fa-check-circle"></i> Supports Any City</span>
                        </div>
                        <input type="text" class="form-control" id="programmaticLocations" value="Chennai, Madurai, Coimbatore, Tirunelveli, Tenkasi, Thoothukudi, Trichy, Salem">
                        
                        <!-- Quick Location Presets -->
                        <div style="margin-top: 8px; display: flex; flex-wrap: wrap; gap: 6px; align-items: center;">
                            <span style="font-size: 0.74rem; color: var(--text-muted); font-weight: 600;"><i class="fas fa-plus-circle"></i> Quick Presets:</span>
                            <button type="button" class="btn btn-outline btn-xs" onclick="addLocationPreset('Chennai, Madurai, Coimbatore')">
                                <i class="fas fa-city"></i> + Chennai, Madurai, CBE
                            </button>
                            <button type="button" class="btn btn-outline btn-xs" onclick="addLocationPreset('Trichy, Salem, Erode, Tiruppur')">
                                <i class="fas fa-map-pin"></i> + Trichy, Salem, Erode
                            </button>
                            <button type="button" class="btn btn-outline btn-xs" onclick="addLocationPreset('Tirunelveli, Tenkasi, Thoothukudi, Kovilpatti, Nagercoil')">
                                <i class="fas fa-compass"></i> + South TN Towns
                            </button>
                            <button type="button" class="btn btn-outline btn-xs" onclick="addLocationPreset('Bangalore, Kochi, Trivandrum, Hyderabad')">
                                <i class="fas fa-globe-asia"></i> + Metros
                            </button>
                            <button type="button" class="btn btn-outline btn-xs" style="color: #ef4444; border-color: rgba(239, 68, 68, 0.4);" onclick="clearLocations()">
                                <i class="fas fa-times"></i> Clear
                            </button>
                        </div>
                    </div>
                </div>

                <button class="btn btn-primary" id="btnGenProgPages" onclick="generateProgrammaticPages()">
                    <i class="fas fa-layer-group"></i> Generate Local Landing Blueprints
                </button>

                <div id="programmaticTableContainer" style="display:none; margin-top: 20px;">
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Target URL Slug</th>
                                    <th>SEO Title Tag</th>
                                    <th>Meta Description</th>
                                    <th>Locality</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="programmaticTableBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==========================================
             TAB 5: WORDPRESS POST STUDIO
             ========================================== -->
        <section id="tab-social" class="tab-content">
            <!-- WordPress Post Composer -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3><i class="fab fa-wordpress" style="color: #21759B;"></i> WordPress Post & Article Studio</h3>
                        <p>Create, optimize, and publish SEO blog posts directly to codespark.online.</p>
                    </div>
                </div>
                <div class="ai-autonomous-card">
                    <div>
                        <strong style="color: #F8FAFC; font-size: 0.94rem; display:flex; align-items:center; gap: 8px;">
                            <i class="fas fa-robot" style="color: #60A5FA;"></i> AI Autonomous Publisher to codespark.online
                        </strong>
                        <p style="font-size: 0.82rem; color: var(--text-muted); margin: 3px 0 0 0; line-height: 1.45;">Automatically creates an SEO article targeted for your Tirunelveli keywords and publishes live to your website.</p>
                    </div>
                    <div>
                        <button class="btn btn-primary btn-sm" id="btnAutoPublishTabSocial" onclick="triggerAutoCreatePost(document.getElementById('wpKeywordSelect')?.value || '')" style="box-shadow: 0 4px 14px rgba(37, 99, 235, 0.4);">
                            <i class="fas fa-magic"></i> Auto-Generate & Publish Post
                        </button>
                    </div>
                </div>

                <!-- Step 1: Select Target Keyword -->
                <?php
                $targetKeywordsRaw = $profile['target_keywords'] ?? 'IT Company, Software Company, Website Designer, Website Developer, Internship Training, Free Internship For College Students, Free Cloud Server Provider, Free Internship Training, Free Hosting Provider, Cloud Server, Mobile App Development, Mobile App Developer, Android App Developer, iOS App Developer, Play Store Console Provider, Online Internship Software Development, Near by IT Company, Near by Software Company, SEO Company, SEO Codespark, No.1 SEO Company, Top website development company, Billing Software, Custom Software Development, Website Desiner, Intership Traning, SEO Comapny';
                $targetKeywordsArr = array_values(array_filter(array_map('trim', explode(',', $targetKeywordsRaw))));
                ?>
                <div class="form-group" style="background: linear-gradient(135deg, rgba(37, 99, 235, 0.08), rgba(15, 23, 42, 0.6)); border: 1px solid rgba(59, 130, 246, 0.28); border-radius: 12px; padding: 16px 18px; margin-bottom: 20px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 8px; flex-wrap: wrap; gap: 8px;">
                        <label class="form-label" style="margin:0; font-size: 0.86rem; font-weight: 700; color: #F8FAFC; display:flex; align-items:center; gap: 8px;">
                            <i class="fas fa-key" style="color: #60A5FA;"></i> 1. Select Target SEO Keyword
                        </label>
                        <span style="font-size: 0.74rem; color: #94A3B8;">Choose keyword to auto-craft post, SEO meta & featured image</span>
                    </div>
                    <div style="display:flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                        <div style="flex: 1; min-width: 260px;">
                            <select class="form-control" id="wpKeywordSelect" onchange="onWpKeywordChange(this.value)" style="font-weight: 500; height: 42px;">
                                <option value="">-- Choose Target Keyword (<?= count($targetKeywordsArr) ?> Loaded) --</option>
                                <?php foreach ($targetKeywordsArr as $kw): ?>
                                    <option value="<?= htmlspecialchars($kw) ?>"><?= htmlspecialchars($kw) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="button" class="btn btn-primary" id="btnGenFullWpPost" onclick="generateFullWpPostFromKeyword()" style="padding: 10px 18px; font-size: 0.85rem; display:inline-flex; align-items:center; gap: 8px; white-space:nowrap; height: 42px;">
                            <i class="fas fa-magic"></i> Auto-Generate Post
                        </button>
                    </div>

                    <!-- Quick Focusing Keyword Badges -->
                    <div style="margin-top: 12px; display: flex; flex-wrap: wrap; gap: 6px; align-items: center;">
                        <span style="font-size: 0.73rem; color: #94A3B8; font-weight: 600;"><i class="fas fa-fire" style="color: #f59e0b;"></i> Focusing Keywords:</span>
                        <button type="button" class="btn btn-outline btn-xs" onclick="quickSelectKeyword('IT Company')">IT Company</button>
                        <button type="button" class="btn btn-outline btn-xs" onclick="quickSelectKeyword('Software Company')">Software Company</button>
                        <button type="button" class="btn btn-outline btn-xs" onclick="quickSelectKeyword('Website Developer')">Website Developer</button>
                        <button type="button" class="btn btn-outline btn-xs" onclick="quickSelectKeyword('Website Designer')">Website Designer</button>
                        <button type="button" class="btn btn-outline btn-xs" onclick="quickSelectKeyword('Mobile App Development')">Mobile App Dev</button>
                        <button type="button" class="btn btn-outline btn-xs" onclick="quickSelectKeyword('Android App Developer')">Android App</button>
                        <button type="button" class="btn btn-outline btn-xs" onclick="quickSelectKeyword('iOS App Developer')">iOS App</button>
                        <button type="button" class="btn btn-outline btn-xs" onclick="quickSelectKeyword('Play Store Console Provider')">Play Store Console</button>
                        <button type="button" class="btn btn-outline btn-xs" onclick="quickSelectKeyword('Free Internship For College Students')">Free Internship</button>
                        <button type="button" class="btn btn-outline btn-xs" onclick="quickSelectKeyword('Internship Training')">Internship Training</button>
                        <button type="button" class="btn btn-outline btn-xs" onclick="quickSelectKeyword('Free Cloud Server Provider')">Free Cloud Server</button>
                        <button type="button" class="btn btn-outline btn-xs" onclick="quickSelectKeyword('Free Hosting Provider')">Free Hosting</button>
                        <button type="button" class="btn btn-outline btn-xs" onclick="quickSelectKeyword('Cloud Server')">Cloud Server</button>
                        <button type="button" class="btn btn-outline btn-xs" onclick="quickSelectKeyword('Near by IT Company')">Near by IT Company</button>
                        <button type="button" class="btn btn-outline btn-xs" onclick="quickSelectKeyword('SEO Company')">SEO Company</button>
                        <button type="button" class="btn btn-outline btn-xs" onclick="quickSelectKeyword('SEO Codespark')">SEO Codespark</button>
                        <button type="button" class="btn btn-outline btn-xs" onclick="quickSelectKeyword('No.1 SEO Company')">No.1 SEO Company</button>
                        <button type="button" class="btn btn-outline btn-xs" onclick="quickSelectKeyword('Top website development company')">Top Web Dev</button>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Post Headline / Topic</label>
                    <input type="text" class="form-control" id="postTitleInput" placeholder="e.g. Top 3 Google Map Strategy Workshop for Local Businesses">
                </div>

                <div class="form-group">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <label class="form-label" style="margin-bottom:0;">Post Body (Include local hashtags & geo intent)</label>
                        <button type="button" class="btn btn-outline btn-sm" id="btnGenBodyAi" onclick="generateAiPostBody()" style="padding: 3px 12px; font-size: 0.78rem; color: #60A5FA; border-color: rgba(96, 165, 250, 0.35);">
                            <i class="fas fa-magic"></i> Auto-Generate Body with AI
                        </button>
                    </div>
                    <textarea class="form-control" id="postContentInput" rows="5" placeholder="Write your localized business update here..."></textarea>
                </div>

                <!-- Dedicated SEO Meta Tags Box (Gemini AI Powered) -->
                <div class="seo-meta-box">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 8px;">
                        <div>
                            <strong style="color: #34D399; font-size: 0.94rem; display:flex; align-items:center; gap: 8px;"><i class="fas fa-search"></i> SEO Meta Tags (Rank Math, Google Snippet & Schema)</strong>
                            <p style="font-size: 0.8rem; color: var(--text-muted); margin: 3px 0 0 0;">These tags are embedded into WordPress SEO fields and rich JSON-LD schema for high Google rankings.</p>
                        </div>
                        <button type="button" class="btn btn-outline btn-sm" id="btnGenMetaAi" onclick="generateAiSeoMeta()" style="border-color: rgba(52, 211, 153, 0.35); color: #34D399;">
                            <i class="fas fa-magic"></i> Generate SEO Meta with Gemini
                        </button>
                    </div>
                    <div class="form-group" style="margin-bottom: 10px;">
                        <div style="display:flex; justify-content:space-between;">
                            <label class="form-label" style="font-size:0.82rem;">SEO Meta Title (Title Tag)</label>
                            <span id="metaTitleCount" style="font-size: 0.75rem; color: var(--text-muted);">0 / 60 chars</span>
                        </div>
                        <input type="text" class="form-control" id="postMetaTitleInput" placeholder="e.g. Best Mobile App Development in Tirunelveli | Codespark" oninput="updateMetaCounters()">
                    </div>
                    <div class="form-group" style="margin-bottom: 10px;">
                        <div style="display:flex; justify-content:space-between;">
                            <label class="form-label" style="font-size:0.82rem;">SEO Meta Description (Google SERP Snippet)</label>
                            <span id="metaDescCount" style="font-size: 0.75rem; color: var(--text-muted);">0 / 160 chars</span>
                        </div>
                        <textarea class="form-control" id="postMetaDescInput" rows="2" placeholder="e.g. Looking for top mobile app development in Tirunelveli? Codespark builds scalable iOS & Android apps. Contact +91 81108 99000." oninput="updateMetaCounters()"></textarea>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" style="font-size:0.82rem;">SEO Meta Keywords / Tags (Comma separated)</label>
                        <input type="text" class="form-control" id="postMetaKeywordsInput" placeholder="e.g. Mobile App Development, Tirunelveli Software Company, Web Design, Codespark">
                    </div>
                </div>

                <!-- Step 4: WordPress Featured Image Studio -->
                <div class="form-group" style="background: #0f172a; border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 12px; padding: 16px 18px; margin-bottom: 20px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                        <label class="form-label" style="margin:0; font-size: 0.85rem; font-weight: 700; color: #F8FAFC; display:flex; align-items:center; gap: 8px;">
                            <i class="fas fa-image" style="color: #38BDF8;"></i> WordPress Featured Image
                        </label>
                        <span style="font-size: 0.74rem; color: #94A3B8;">Embedded into WordPress post & Google rich snippet schema</span>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 140px 1fr; gap: 16px; align-items: center;">
                        <!-- Live Image Thumbnail Preview -->
                        <div style="width: 140px; height: 95px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.15); background: #070a13; overflow: hidden; display: flex; align-items: center; justify-content: center; position: relative;">
                            <img id="wpFeaturedImagePreview" src="https://images.unsplash.com/photo-1551650975-87deedd944c3?w=800&auto=format&fit=crop" alt="Featured Image Preview" style="width: 100%; height: 100%; object-fit: cover; display: block;" onerror="this.style.display='none'; document.getElementById('wpImagePlaceholder').style.display='flex';">
                            <div id="wpImagePlaceholder" style="display: none; flex-direction: column; align-items: center; justify-content: center; color: #64748B; font-size: 0.75rem; text-align: center; padding: 6px;">
                                <i class="fas fa-image" style="font-size: 1.4rem; margin-bottom: 4px;"></i>
                                No Image
                            </div>
                        </div>

                        <!-- Image URL & Preset Selection -->
                        <div>
                            <input type="url" class="form-control" id="postImageInput" placeholder="https://images.unsplash.com/..." value="https://images.unsplash.com/photo-1551650975-87deedd944c3?w=800&auto=format&fit=crop" oninput="updateWpFeaturedImagePreview(this.value)" style="margin-bottom: 10px;">
                            
                            <!-- Quick Image Presets -->
                            <div style="display:flex; align-items:center; gap: 8px; flex-wrap: wrap;">
                                <span style="font-size: 0.72rem; color: var(--text-dim); text-transform: uppercase; font-weight: 700;">Presets:</span>
                                <button type="button" class="btn btn-outline btn-sm" onclick="setWpFeaturedImage('https://images.unsplash.com/photo-1551650975-87deedd944c3?w=800&auto=format&fit=crop', 'Mobile Apps')" style="padding: 3px 8px; font-size: 0.72rem;">📱 Mobile Apps</button>
                                <button type="button" class="btn btn-outline btn-sm" onclick="setWpFeaturedImage('https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=800&auto=format&fit=crop', 'Internship')" style="padding: 3px 8px; font-size: 0.72rem;">🎓 Internship</button>
                                <button type="button" class="btn btn-outline btn-sm" onclick="setWpFeaturedImage('https://images.unsplash.com/photo-1547658719-da2b51169166?w=800&auto=format&fit=crop', 'Web Design')" style="padding: 3px 8px; font-size: 0.72rem;">💻 Web Design</button>
                                <button type="button" class="btn btn-outline btn-sm" onclick="setWpFeaturedImage('https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?w=800&auto=format&fit=crop', 'Billing POS')" style="padding: 3px 8px; font-size: 0.72rem;">🧾 Billing POS</button>
                                <button type="button" class="btn btn-outline btn-sm" onclick="setWpFeaturedImage('https://images.unsplash.com/photo-1451187580459-43490279c0fa?w=800&auto=format&fit=crop', 'Cloud')" style="padding: 3px 8px; font-size: 0.72rem;">☁️ Cloud</button>
                                <button type="button" class="btn btn-outline btn-sm" onclick="setWpFeaturedImage('https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=800&auto=format&fit=crop', 'SEO')" style="padding: 3px 8px; font-size: 0.72rem;">📈 SEO</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 5: Secondary In-Content Image & SEO Alt Text Studio (Image 4 Style) -->
                <div class="form-group" style="background: #0f172a; border: 1px solid rgba(56, 189, 248, 0.22); border-radius: 12px; padding: 16px 18px; margin-bottom: 20px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                        <label class="form-label" style="margin:0; font-size: 0.85rem; font-weight: 700; color: #F8FAFC; display:flex; align-items:center; gap: 8px;">
                            <i class="fas fa-images" style="color: #38BDF8;"></i> Secondary In-Content Image & SEO Alt Text
                        </label>
                        <span style="font-size: 0.74rem; color: #94A3B8;">Embedded inside blog body with keyword-optimized ALT tag & caption</span>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 140px 1fr; gap: 16px; align-items: center; margin-bottom: 12px;">
                        <!-- Live Secondary Image Thumbnail Preview -->
                        <div style="width: 140px; height: 95px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.15); background: #070a13; overflow: hidden; display: flex; align-items: center; justify-content: center; position: relative;">
                            <img id="wpSecondaryImagePreview" src="https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?w=1000&auto=format&fit=crop" alt="Secondary Image Preview" style="width: 100%; height: 100%; object-fit: cover; display: block;" onerror="this.style.display='none'; document.getElementById('wpSecondaryPlaceholder').style.display='flex';">
                            <div id="wpSecondaryPlaceholder" style="display: none; flex-direction: column; align-items: center; justify-content: center; color: #64748B; font-size: 0.75rem; text-align: center; padding: 6px;">
                                <i class="fas fa-image" style="font-size: 1.4rem; margin-bottom: 4px;"></i>
                                No Image
                            </div>
                        </div>

                        <!-- Secondary Image URL & Presets -->
                        <div>
                            <input type="url" class="form-control" id="postSecondaryImageInput" placeholder="https://images.unsplash.com/..." value="https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?w=1000&auto=format&fit=crop" oninput="updateWpSecondaryImagePreview(this.value)" style="margin-bottom: 10px;">
                            
                            <!-- Quick In-Content Image Presets -->
                            <div style="display:flex; align-items:center; gap: 8px; flex-wrap: wrap;">
                                <span style="font-size: 0.72rem; color: var(--text-dim); text-transform: uppercase; font-weight: 700;">Presets:</span>
                                <button type="button" class="btn btn-outline btn-sm" onclick="setWpSecondaryImage('https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?w=1000&auto=format&fit=crop', 'Custom Mobile App Development in Tirunelveli | CodeSpark builds scalable iOS and Android applications.')" style="padding: 3px 8px; font-size: 0.72rem;">📱 App Design</button>
                                <button type="button" class="btn btn-outline btn-sm" onclick="setWpSecondaryImage('https://images.unsplash.com/photo-1531482615713-2afd69097998?w=1000&auto=format&fit=crop', 'Professional Software Solutions & Internship in Tirunelveli | CodeSpark offers practical live project mentorship.')" style="padding: 3px 8px; font-size: 0.72rem;">🎓 Internship Work</button>
                                <button type="button" class="btn btn-outline btn-sm" onclick="setWpSecondaryImage('https://images.unsplash.com/photo-1556742049-0a67c5574f73?w=1000&auto=format&fit=crop', 'GST Billing & POS Software in Tirunelveli | Fast barcode scanning, accounting and stock management by Codespark.')" style="padding: 3px 8px; font-size: 0.72rem;">🧾 POS Counter</button>
                                <button type="button" class="btn btn-outline btn-sm" onclick="setWpSecondaryImage('https://images.unsplash.com/photo-1544197150-b99a580bb7a8?w=1000&auto=format&fit=crop', 'Enterprise Cloud Hosting & Server Infrastructure in Tirunelveli | High speed 99.9% uptime by Codespark.')" style="padding: 3px 8px; font-size: 0.72rem;">☁️ Cloud Server</button>
                                <button type="button" class="btn btn-outline btn-sm" onclick="setWpSecondaryImage('https://images.unsplash.com/photo-1557838923-2985c318be48?w=1000&auto=format&fit=crop', 'Top Ranking SEO & Digital Marketing in Tirunelveli | Dominate Google 1st Page with Codespark.')" style="padding: 3px 8px; font-size: 0.72rem;">📈 SEO Growth</button>
                            </div>
                        </div>
                    </div>

                    <!-- Image Alt Text Field (User Image 4 Requirement) -->
                    <div>
                        <label class="form-label" style="font-size:0.82rem; margin-bottom: 4px; color: #94A3B8;">Image Alt Tag & Caption (Required for Google Image SEO)</label>
                        <input type="text" class="form-control" id="postSecondaryImageAltInput" value="Professional Software Solutions in Tirunelveli | CodeSpark offers SEO, website development, and Android & iOS mobile app development services." placeholder="e.g. Professional Software Solutions in Tirunelveli | CodeSpark offers SEO, website development...">
                    </div>
                </div>

                <!-- Step 6: Target Codespark Landing Page & Form Management -->
                <div class="form-group" style="background: rgba(30, 41, 59, 0.4); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 12px; padding: 16px 18px; margin-bottom: 20px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 10px; flex-wrap: wrap; gap: 8px;">
                        <label class="form-label" style="margin:0; font-size: 0.85rem; font-weight: 700; color: #F8FAFC; display:flex; align-items:center; gap: 8px;">
                            <i class="fas fa-link" style="color: #60A5FA;"></i> Target Landing Page & Lead Form (Codespark.online)
                        </label>
                        <span style="font-size: 0.74rem; color: #94A3B8;">Auto-mapped per keyword; drives users to specific inquiry/application form</span>
                    </div>
                    <div class="form-row">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label" style="font-size:0.82rem;">Select Target Landing Page / Form</label>
                            <select class="form-control" id="wpLandingPageSelect" onchange="onLandingPageSelectChange(this.value)">
                                <option value="https://codespark.online/best-website-design-for-your-business/">💻 Best Website Design (https://codespark.online/best-website-design-for-your-business/)</option>
                                <option value="https://codespark.online/easy-billing-software/">🧾 Easy Billing Software (https://codespark.online/easy-billing-software/)</option>
                                <option value="https://codespark.online/internship-for-students/">🎓 Internship for Students (https://codespark.online/internship-for-students/)</option>
                                <option value="https://codespark.online/internship/">👨‍🎓 IT Career & Internship (https://codespark.online/internship/)</option>
                                <option value="https://codespark.online/digital-marketing-for-your-business/">📈 Digital Marketing & SEO (https://codespark.online/digital-marketing-for-your-business/)</option>
                                <option value="https://codespark.online/cloud-hosting-provider/">☁️ Cloud Hosting Provider (https://codespark.online/cloud-hosting-provider/)</option>
                                <option value="https://codespark.online/contact/">📱 General Contact & Inquiries (https://codespark.online/contact/)</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label" style="font-size:0.82rem;">Custom / Active Destination Link</label>
                            <input type="url" class="form-control" id="postCtaUrlInput" value="https://codespark.online/best-website-design-for-your-business/" placeholder="https://codespark.online/...">
                        </div>
                    </div>
                </div>

                <!-- Step 7: WordPress Categories & Tags Studio -->
                <div class="form-group" style="background: #0f172a; border: 1px solid rgba(34, 197, 94, 0.25); border-radius: 12px; padding: 16px 18px; margin-bottom: 20px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                        <div>
                            <strong style="color: #4ADE80; font-size: 0.88rem; display:flex; align-items:center; gap: 8px;">
                                <i class="fas fa-tags"></i> WordPress Categories & Tags (codespark.online)
                            </strong>
                            <p style="font-size: 0.76rem; color: #94A3B8; margin: 3px 0 0 0;">Auto-assigned by AI based on keyword, synced live with WordPress taxonomy taxonomy IDs.</p>
                        </div>
                        <button type="button" class="btn btn-outline btn-sm" onclick="syncWpTaxonomies(true)" style="padding: 4px 10px; font-size: 0.74rem; border-color: rgba(74, 222, 128, 0.35); color: #4ADE80;">
                            <i class="fas fa-sync-alt"></i> Sync with WordPress
                        </button>
                    </div>

                    <!-- Selected Categories Badges -->
                    <div style="margin-bottom: 12px;">
                        <label class="form-label" style="font-size: 0.8rem; margin-bottom: 6px; color: #cbd5e1;">Active WordPress Categories:</label>
                        <div id="wpSelectedCategoriesContainer" style="display: flex; flex-wrap: wrap; gap: 6px; min-height: 32px; padding: 6px 10px; background: #070a13; border: 1px solid rgba(255,255,255,0.1); border-radius: 8px;">
                            <span style="font-size: 0.75rem; color: #64748B;">Loading categories from codespark.online...</span>
                        </div>
                    </div>

                    <!-- Quick Category Picker / Dropdown -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div>
                            <label class="form-label" style="font-size: 0.78rem; margin-bottom: 4px;">Available Categories (Click to toggle):</label>
                            <select class="form-control" id="wpCategorySelector" onchange="toggleCategoryFromSelect(this.value)" style="font-size: 0.82rem; height: 38px;">
                                <option value="">-- Choose Category to Add/Remove --</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 0.78rem; margin-bottom: 4px;">Available Tags (Click to toggle):</label>
                            <select class="form-control" id="wpTagSelector" onchange="toggleTagFromSelect(this.value)" style="font-size: 0.82rem; height: 38px;">
                                <option value="">-- Choose Tag to Add/Remove --</option>
                            </select>
                        </div>
                    </div>

                    <!-- Selected Tags Badges -->
                    <div style="margin-top: 10px;">
                        <label class="form-label" style="font-size: 0.8rem; margin-bottom: 6px; color: #cbd5e1;">Active WordPress Tags:</label>
                        <div id="wpSelectedTagsContainer" style="display: flex; flex-wrap: wrap; gap: 6px; min-height: 32px; padding: 6px 10px; background: #070a13; border: 1px solid rgba(255,255,255,0.1); border-radius: 8px;">
                            <span style="font-size: 0.75rem; color: #64748B;">Loading tags from codespark.online...</span>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Call-To-Action (CTA) Button Type</label>
                        <select class="form-control" id="postCtaSelect">
                            <option value="LEARN_MORE">Learn More / Apply Now</option>
                            <option value="BOOK">Book Free Consultation</option>
                            <option value="CALL">Call Team Directly</option>
                            <option value="SIGN_UP">Sign Up / Register</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Destination Channel</label>
                        <div style="display:flex; flex-wrap: wrap; gap: 16px; margin-top: 8px;">
                            <label style="display:flex; align-items:center; gap: 8px; cursor: pointer; font-weight: 500;">
                                <input type="checkbox" id="platWordpress" checked style="accent-color: var(--primary);"> <i class="fab fa-wordpress" style="color: #21759B; font-size: 1.15rem;"></i> WordPress (codespark.online)
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Schedule Time (Leave blank to publish immediately)</label>
                    <input type="datetime-local" class="form-control" id="postScheduleInput">
                </div>

                <div style="display:flex; gap: 12px; margin-top: 14px; flex-wrap: wrap;">
                    <button class="btn btn-primary" id="btnPublishNow" onclick="submitSocialPost(true)">
                        <i class="fab fa-wordpress"></i> Publish Now to WordPress
                    </button>
                    <button class="btn btn-outline" id="btnScheduleLater" onclick="submitSocialPost(false)">
                        <i class="fas fa-clock"></i> Schedule For Later
                    </button>
                </div>
            </div>


            <!-- Published & Scheduled Posts List -->
            <div class="card">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <h3><i class="fas fa-list-alt"></i> Post Queue & Publication History</h3>
                        <p style="margin: 2px 0 0 0; font-size: 0.8rem; color: var(--text-muted);">Manage your local publication logs and syndicated post records.</p>
                    </div>
                    <button class="btn btn-outline btn-sm" id="btnClearPostsHistory" onclick="clearPostsHistory()" style="color: #ef4444; border-color: rgba(239, 68, 68, 0.4); font-size: 0.8rem; display: inline-flex; align-items: center; gap: 6px;">
                        <i class="fas fa-trash-alt"></i> Clear History Log
                    </button>
                </div>
                <div id="allPostsList"></div>
            </div>
        </section>

        <!-- ==========================================
             TAB 5.5: GOOGLE MAP UPDATES POST STUDIO
             ========================================== -->
        <section id="tab-gmb-updates" class="tab-content">
            <!-- Hero Connection Status Banner -->
            <div class="gmb-hero-banner">
                <div class="gmb-hero-info">
                    <div class="gmb-hero-icon">
                        <i class="fab fa-google" style="color: #4285F4;"></i>
                    </div>
                    <div>
                        <div style="display:flex; align-items:center; gap: 8px; flex-wrap: wrap;">
                            <h2 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: #fff;">Google Business Profile · Updates & Posts</h2>
                            <span class="status-pill success" style="font-size: 0.72rem; padding: 2px 10px;">
                                <i class="fas fa-check-circle"></i> Live Connected
                            </span>
                        </div>
                        <p style="margin: 4px 0 0; font-size: 0.82rem; color: var(--text-muted);">
                            <strong>Codespark Software Development</strong> (Melapalayam, Tirunelveli - 627005) · Place ID: <code style="color: #93c5fd;">ChIJDR4_dxUTBDsReG0F-jMX19g</code> · CID: <code style="color: #93c5fd;">15624982944190655864</code>
                        </p>
                    </div>
                </div>
                <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                    <a href="https://www.google.com/maps/search/?api=1&query=Codespark+Software+Development+Melapalayam+Tirunelveli&query_place_id=ChIJDR4_dxUTBDsReG0F-jMX19g" target="_blank" rel="noopener noreferrer" class="btn btn-outline btn-sm" style="border-color: rgba(66, 133, 244, 0.4); color: #60a5fa; text-decoration:none;">
                        <i class="fas fa-map-marked-alt"></i> View on Google Maps ↗
                    </a>
                    <a href="https://business.google.com/" target="_blank" rel="noopener noreferrer" class="btn btn-outline btn-sm" style="border-color: rgba(255,255,255,0.2); color: var(--text-muted); text-decoration:none;">
                        <i class="fab fa-google"></i> Business Manager ↗
                    </a>
                </div>
            </div>

            <!-- Main 2-Column Grid: Composer + 1:1 Live Preview -->
            <div class="gmb-editor-grid">
                
                <!-- Left: Gemini Composer -->
                <div class="card" style="margin-bottom: 0;">
                    <div class="card-header" style="border-bottom: 1px solid var(--border-color); padding-bottom: 14px; margin-bottom: 16px;">
                        <div>
                            <h3 style="display:flex; align-items:center; gap: 8px; font-size: 1.05rem;">
                                <i class="fas fa-magic" style="color: #818cf8;"></i> Create Google Business Update
                            </h3>
                            <p style="font-size: 0.8rem; margin: 2px 0 0;">Gemini AI crafts local SEO updates tailored to your Tirunelveli customers.</p>
                        </div>
                    </div>

                    <!-- Step 1: Quick Topic Selection -->
                    <div class="form-group" style="margin-bottom: 16px;">
                        <label class="form-label" style="font-size: 0.82rem; font-weight: 600; display: flex; justify-content: space-between;">
                            <span>1. Select Service Topic or Enter Custom</span>
                            <span style="color: var(--text-dim); font-size: 0.74rem;">Click preset for instant fill</span>
                        </label>
                        <div class="gmb-topic-chips" id="gmbTopicChips">
                            <span class="gmb-topic-chip active" onclick="selectGmbPresetTopic('Web Development & Software Solutions', this)">
                                💻 Web Development
                            </span>
                            <span class="gmb-topic-chip" onclick="selectGmbPresetTopic('Cloud & High-Speed Web Hosting', this)">
                                ☁️ Cloud Web Hosting
                            </span>
                            <span class="gmb-topic-chip" onclick="selectGmbPresetTopic('Python & Full Stack Internship in Tirunelveli', this)">
                                🎓 Python Internship
                            </span>
                            <span class="gmb-topic-chip" onclick="selectGmbPresetTopic('Android & iOS Mobile App Development', this)">
                                📱 Mobile Apps
                            </span>
                            <span class="gmb-topic-chip" onclick="selectGmbPresetTopic('Custom Billing Software & POS Solutions', this)">
                                💼 Billing Software
                            </span>
                            <span class="gmb-topic-chip" onclick="selectGmbPresetTopic('Local SEO & Google Business Profile Ranking', this)">
                                ⚡ Local SEO
                            </span>
                        </div>
                        
                        <div style="display: flex; gap: 8px; margin-top: 6px;">
                            <input type="text" class="form-control" id="gmbTopicInput" placeholder="Or type any custom topic (e.g. 20% festive discount on web design in Tirunelveli)..." value="Web Development & Software Solutions">
                            <button type="button" class="btn btn-primary" id="btnGenGmbAi" onclick="generateGmbUpdateWithGemini()" style="white-space: nowrap; background: linear-gradient(135deg, #4285F4, #6366F1); display: inline-flex; align-items: center; gap: 8px; padding: 8px 18px;">
                                <i class="fas fa-sparkles"></i> <span>Auto-Generate</span>
                            </button>
                        </div>
                    </div>

                    <!-- Step 2: Post Headline & Content -->
                    <div class="form-group" style="margin-bottom: 14px;">
                        <label class="form-label" style="font-size: 0.82rem; font-weight: 600;">2. Headline / Title</label>
                        <input type="text" class="form-control" id="gmbHeadlineInput" placeholder="Catchy headline with local keywords..." value="Top Software & Web Development Company in Tirunelveli | Codespark" oninput="updateGmbLivePreview()">
                    </div>

                    <div class="form-group" style="margin-bottom: 16px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 600; margin:0;">3. Post Description (What's New)</label>
                            <span id="gmbCharCount" style="font-size: 0.72rem; color: var(--text-dim);">0 chars</span>
                        </div>
                        <textarea class="form-control" id="gmbBodyInput" rows="5" placeholder="Write your Google Map update details, contact info, and hashtags..." oninput="updateGmbLivePreview()"></textarea>
                    </div>

                    <!-- Step 3: High Quality Image Selection -->
                    <div class="form-group" style="margin-bottom: 16px;">
                        <label class="form-label" style="font-size: 0.82rem; font-weight: 600; display:flex; justify-content: space-between; align-items: center;">
                            <span>4. Post Photo / Image</span>
                            <span style="font-size: 0.72rem; color: var(--text-dim);">Google Maps recommends high-res 16:9 / 4:3 photo</span>
                        </label>
                        <input type="text" class="form-control" id="gmbImageUrlInput" placeholder="Image URL (https://...)" value="https://images.unsplash.com/photo-1571171637578-41bc2dd41cd2?w=800&auto=format&fit=crop" oninput="updateGmbLivePreview()">
                        
                        <div style="margin-top: 10px;">
                            <div style="font-size: 0.74rem; color: var(--text-muted); margin-bottom: 6px;">Choose from HD presets or paste custom link above:</div>
                            <div class="gmb-image-picker-grid" id="gmbImagePickerGrid">
                                <div class="gmb-image-preset-card active" onclick="selectGmbPresetImage('https://images.unsplash.com/photo-1571171637578-41bc2dd41cd2?w=800&auto=format&fit=crop', this)" title="Software & Web Dev">
                                    <img src="https://images.unsplash.com/photo-1571171637578-41bc2dd41cd2?w=300&auto=format&fit=crop" alt="Web Dev">
                                    <div class="gmb-preset-title">Web Dev</div>
                                </div>
                                <div class="gmb-image-preset-card" onclick="selectGmbPresetImage('https://images.unsplash.com/photo-1451187580459-43490279c0fa?w=800&auto=format&fit=crop', this)" title="Cloud Hosting">
                                    <img src="https://images.unsplash.com/photo-1451187580459-43490279c0fa?w=300&auto=format&fit=crop" alt="Cloud">
                                    <div class="gmb-preset-title">Cloud Host</div>
                                </div>
                                <div class="gmb-image-preset-card" onclick="selectGmbPresetImage('https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=800&auto=format&fit=crop', this)" title="Internship & Training">
                                    <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=300&auto=format&fit=crop" alt="Internship">
                                    <div class="gmb-preset-title">Internship</div>
                                </div>
                                <div class="gmb-image-preset-card" onclick="selectGmbPresetImage('https://images.unsplash.com/photo-1551650975-87deedd944c3?w=800&auto=format&fit=crop', this)" title="Mobile App Development">
                                    <img src="https://images.unsplash.com/photo-1551650975-87deedd944c3?w=300&auto=format&fit=crop" alt="Mobile Apps">
                                    <div class="gmb-preset-title">Mobile App</div>
                                </div>
                                <div class="gmb-image-preset-card" onclick="selectGmbPresetImage('https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?w=800&auto=format&fit=crop', this)" title="Billing Software & ERP">
                                    <img src="https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?w=300&auto=format&fit=crop" alt="Billing">
                                    <div class="gmb-preset-title">Billing/ERP</div>
                                </div>
                                <div class="gmb-image-preset-card" onclick="selectGmbPresetImage('https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=800&auto=format&fit=crop', this)" title="SEO & Marketing">
                                    <img src="https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=300&auto=format&fit=crop" alt="SEO">
                                    <div class="gmb-preset-title">Local SEO</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 4: Call to Action & Dedicated Button URL -->
                    <div style="background: rgba(66, 133, 244, 0.08); border: 1px solid rgba(66, 133, 244, 0.25); border-radius: var(--radius-sm); padding: 14px; margin-bottom: 18px;">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                            <i class="fas fa-external-link-alt" style="color: #4285F4;"></i>
                            <h4 style="margin: 0; font-size: 0.9rem; color: #93c5fd;">5. Call to Action Button & Destination URL</h4>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 12px; margin-bottom: 10px;">
                            <div>
                                <label class="form-label" style="font-size: 0.78rem; font-weight: 600;">Button Type</label>
                                <select class="form-control" id="gmbCtaTypeSelect" onchange="updateGmbLivePreview()">
                                    <option value="LEARN_MORE" selected>Learn more</option>
                                    <option value="BOOK">Book</option>
                                    <option value="ORDER">Order online</option>
                                    <option value="SIGN_UP">Sign up</option>
                                    <option value="CALL">Call now</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label" style="font-size: 0.78rem; font-weight: 600; color: #fff;">Button URL (Where users land)</label>
                                <input type="url" class="form-control" id="gmbButtonUrlInput" placeholder="https://codespark.online/services/" value="https://codespark.online/services/" oninput="updateGmbLivePreview()" style="border-color: rgba(66, 133, 244, 0.5);">
                            </div>
                        </div>

                        <!-- Quick suggestion chips for Button URL -->
                        <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                            <span style="font-size: 0.72rem; color: var(--text-dim);">Suggested URLs:</span>
                            <button type="button" class="btn btn-outline btn-sm" onclick="setGmbButtonUrl('https://codespark.online/services/')" style="padding: 2px 8px; font-size: 0.7rem; border-color: rgba(255,255,255,0.15);">
                                /services/
                            </button>
                            <button type="button" class="btn btn-outline btn-sm" onclick="setGmbButtonUrl('https://codespark.online/')" style="padding: 2px 8px; font-size: 0.7rem; border-color: rgba(255,255,255,0.15);">
                                Homepage
                            </button>
                            <button type="button" class="btn btn-outline btn-sm" onclick="setGmbButtonUrl('https://codespark.online/internship/')" style="padding: 2px 8px; font-size: 0.7rem; border-color: rgba(255,255,255,0.15);">
                                /internship/
                            </button>
                            <button type="button" class="btn btn-outline btn-sm" onclick="setGmbButtonUrl('https://codespark.online/contact/')" style="padding: 2px 8px; font-size: 0.7rem; border-color: rgba(255,255,255,0.15);">
                                /contact/
                            </button>
                        </div>
                    </div>

                    <!-- Step 5: Publish Action -->
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; padding-top: 10px; border-top: 1px solid var(--border-color);">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.8rem; color: var(--text-muted); margin:0;">
                            <input type="checkbox" id="gmbSyndicateWp" checked style="accent-color: var(--primary);">
                            <span>Also publish to WordPress (codespark.online)</span>
                        </label>
                        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                            <button type="button" class="btn btn-outline" onclick="openCurrentPostInAssistant()" style="border-color: rgba(66, 133, 244, 0.4); color: #60a5fa; font-weight: 500; padding: 10px 18px; font-size: 0.88rem; display: inline-flex; align-items: center; gap: 8px;" title="Open Assistant popup with current post data">
                                <i class="fab fa-google"></i>
                                <span>Open Assistant Popup</span>
                            </button>
                            <button class="btn btn-success" id="btnPublishGmb" onclick="publishGmbUpdate()" style="background: linear-gradient(135deg, #10B981, #059669); font-weight: 600; padding: 10px 24px; font-size: 0.95rem; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4); display: inline-flex; align-items: center; gap: 8px;">
                                <i class="fab fa-google"></i>
                                <span>Publish to Google Business Profile</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Right: Authentic 1:1 Live Google Maps Card Preview -->
                <div class="gmb-preview-sticky">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 10px;">
                        <span style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #93c5fd; display:flex; align-items:center; gap: 6px;">
                            <i class="fas fa-eye"></i> Live Google Maps Card Preview
                        </span>
                        <span class="status-pill info" style="font-size: 0.7rem; padding: 2px 8px;">1:1 Match</span>
                    </div>

                    <!-- Google Maps Card Mockup -->
                    <div class="gmb-maps-card" id="gmbLiveCard">
                        <!-- Card Header -->
                        <div class="gmb-maps-header">
                            <div class="gmb-maps-avatar">
                                <span>C</span>
                            </div>
                            <div style="flex: 1; min-width: 0;">
                                <div class="gmb-maps-biz-name">
                                    <span>Codespark Software Development</span>
                                    <i class="fas fa-check-circle gmb-verified-badge" title="Verified Google Business Profile"></i>
                                </div>
                                <div class="gmb-maps-meta">
                                    <span>Update · Just now</span> · <span style="color: #1a73e8;">Tirunelveli, Tamil Nadu</span>
                                </div>
                            </div>
                        </div>

                        <!-- Card Featured Photo -->
                        <div class="gmb-maps-image-wrap">
                            <img id="gmbPreviewImg" src="https://images.unsplash.com/photo-1571171637578-41bc2dd41cd2?w=800&auto=format&fit=crop" alt="Google Map Update Photo">
                        </div>

                        <!-- Card Body -->
                        <div class="gmb-maps-body">
                            <div class="gmb-maps-headline" id="gmbPreviewHeadline">
                                Top Software & Web Development Company in Tirunelveli | Codespark
                            </div>
                            <div class="gmb-maps-text" id="gmbPreviewBody">
                                Looking for premier Web Development in Tirunelveli? 🚀

At Codespark Software Development, we build high-performance mobile apps, digital billing systems, and responsive websites for growing businesses across Tamil Nadu.

📍 Office: P.No.7A, Housing Board Colony, D.no.46/24, Melapalayam, Tirunelveli - 627005
📞 Call / WhatsApp: +91 81108 99000
🌐 Visit: https://codespark.online/

#Tirunelveli #SoftwareCompany #WebDevelopment #Codespark
                            </div>
                        </div>

                        <!-- Card Action Footer -->
                        <div class="gmb-maps-footer">
                            <a id="gmbPreviewCtaBtn" href="https://codespark.online/services/" target="_blank" class="gmb-maps-cta-btn">
                                <span id="gmbPreviewCtaText">Learn more</span>
                                <i class="fas fa-external-link-alt" style="font-size: 0.72rem;"></i>
                            </a>
                            <div class="gmb-maps-url-target" id="gmbPreviewUrlTarget" title="https://codespark.online/services/">
                                codespark.online/services/
                            </div>
                        </div>
                    </div>

                    <!-- Helpful Notice -->
                    <div style="margin-top: 14px; padding: 12px 14px; background: rgba(255, 255, 255, 0.03); border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.78rem; color: var(--text-dim); display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-info-circle" style="color: #60a5fa; font-size: 1rem; flex-shrink: 0;"></i>
                        <span>This live preview displays exactly how potential customers in Tirunelveli see your update in the <strong>Updates</strong> tab on Google Maps and Google Search.</span>
                    </div>
                </div>

            </div>

            <!-- Published Updates History Feed -->
            <div class="card" style="margin-top: 24px;">
                <div class="card-header">
                    <div>
                        <h3 style="display: flex; align-items: center; gap: 8px; font-size: 1.1rem;">
                            <i class="fas fa-history" style="color: #4285F4;"></i> Published Google Maps Profile Updates
                        </h3>
                        <p style="font-size: 0.8rem; margin: 2px 0 0;">Recent updates posted to Codespark's Google Business Profile feed.</p>
                    </div>
                    <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                        <button class="btn btn-outline btn-sm" onclick="loadGmbUpdates()" style="border-color: rgba(255,255,255,0.15); color: var(--text-muted);">
                            <i class="fas fa-sync-alt"></i> Refresh Feed
                        </button>
                        <button class="btn btn-outline btn-sm" id="btnClearGmbUpdates" onclick="clearGmbUpdates()" style="color: #ef4444; border-color: rgba(239, 68, 68, 0.4); font-size: 0.78rem; display: inline-flex; align-items: center; gap: 6px;">
                            <i class="fas fa-trash-alt"></i> Clear All Updates
                        </button>
                    </div>
                </div>
                <div id="gmbPublishedUpdatesFeed">
                    <div style="text-align: center; padding: 30px; color: var(--text-dim);">
                        <i class="fas fa-spinner fa-spin"></i> Loading updates feed...
                    </div>
                </div>
                <!-- Pagination Controls for Google Map Updates -->
                <div id="gmbUpdatesPaginationWrap" style="display: none; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-top: 18px; padding-top: 14px; border-top: 1px solid var(--border-color);">
                    <div id="gmbPaginationCount" style="font-size: 0.8rem; color: var(--text-muted);">
                        Showing updates...
                    </div>
                    <div style="display:flex; align-items:center; gap: 12px;">
                        <div style="display:flex; align-items:center; gap: 6px; font-size: 0.78rem; color: var(--text-dim);">
                            <span>Show:</span>
                            <select id="gmbPerPageSelect" class="form-control" style="padding: 2px 8px; font-size: 0.75rem; width: auto; height: 30px;" onchange="changeGmbPerPage(this.value)">
                                <option value="3" selected>3 per page</option>
                                <option value="5">5 per page</option>
                                <option value="10">10 per page</option>
                            </select>
                        </div>
                        <div id="gmbPaginationControls" style="display:flex; gap: 6px; align-items: center;"></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==========================================
             TAB 6: BUSINESS PROFILE & NAP
             ========================================== -->
        <section id="tab-profile" class="tab-content">
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3><i class="fas fa-id-card" style="color: var(--primary);"></i> Business Profile & Office NAP Data</h3>
                        <p>Keep your Name, Address, and Phone perfectly aligned with Google Maps.</p>
                    </div>
                    <div>
                        <button class="btn btn-success btn-sm" onclick="saveBusinessProfile()">
                            <i class="fas fa-save"></i> Save Profile
                        </button>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Official Business Name</label>
                        <input type="text" class="form-control" id="profName" value="<?= htmlspecialchars($profile['name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Primary Google Maps Category</label>
                        <input type="text" class="form-control" id="profCategory" value="<?= htmlspecialchars($profile['category'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Street Address (Must exactly match physical office sign)</label>
                    <input type="text" class="form-control" id="profAddress" value="<?= htmlspecialchars($profile['address'] ?? '') ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">City</label>
                        <input type="text" class="form-control" id="profCity" value="<?= htmlspecialchars($profile['city'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">State / Region</label>
                        <input type="text" class="form-control" id="profState" value="<?= htmlspecialchars($profile['state'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">ZIP / Postal Code</label>
                        <input type="text" class="form-control" id="profZip" value="<?= htmlspecialchars($profile['zip'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Direct Phone (With Country Code)</label>
                        <input type="text" class="form-control" id="profPhone" value="<?= htmlspecialchars($profile['phone'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Website URL</label>
                        <input type="url" class="form-control" id="profWebsite" value="<?= htmlspecialchars($profile['website'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Latitude Coordinates</label>
                        <input type="number" step="0.0001" class="form-control" id="profLat" value="<?= htmlspecialchars($profile['latitude'] ?? '28.6315') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Longitude Coordinates</label>
                        <input type="number" step="0.0001" class="form-control" id="profLng" value="<?= htmlspecialchars($profile['longitude'] ?? '77.2167') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Google Place ID</label>
                        <input type="text" class="form-control" id="profPlaceId" value="<?= htmlspecialchars($profile['google_place_id'] ?? 'ChIJDR4_dxUTBDsReG0F-jMX19g') ?>">
                    </div>
                </div>

                <div style="background: rgba(99, 102, 241, 0.08); border: 1px solid rgba(99, 102, 241, 0.25); border-radius: var(--radius-sm); padding: 18px; margin: 18px 0;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 8px;">
                        <h4 style="font-size: 0.95rem; color: #fff; display:flex; align-items:center; gap: 8px;">
                            <i class="fas fa-crosshairs" style="color: var(--primary);"></i> Focusing Target SEO Keywords (Google Gemini AI Engine)
                        </h4>
                        <span class="status-pill success"><i class="fas fa-robot"></i> Gemini Optimized</span>
                    </div>
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 12px;">
                        Google Gemini Pro uses these exact focusing keywords to optimize your WordPress blog posts, Google Maps review replies, geo-grid heatmap tracking, and local landing pages.
                    </p>
                    <textarea class="form-control" id="profKeywords" rows="4" style="font-family: inherit; font-size: 0.88rem; line-height: 1.6;" placeholder="1. software company in tirunelveli, 2. web development company tirunelveli, 3. best website design tirunelveli..."><?= htmlspecialchars($profile['target_keywords'] ?? '') ?></textarea>
                    
                    <!-- Quick Tag Preview -->
                    <div id="keywordTagsPreview" style="display:flex; flex-wrap: wrap; gap: 8px; margin-top: 10px;">
                        <?php 
                        $kws = explode(',', $profile['target_keywords'] ?? '');
                        foreach ($kws as $kw): 
                            $kw = trim($kw);
                            if (empty($kw)) continue;
                        ?>
                            <span class="status-pill primary" style="font-size: 0.75rem;"><i class="fas fa-key"></i> <?= htmlspecialchars($kw) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <button class="btn btn-primary" onclick="saveBusinessProfile()">
                    <i class="fas fa-save"></i> Save Business Profile & Update Synced Modules
                </button>
            </div>
        </section>

        <!-- ==========================================
             TAB 7: CONNECT & AUTOMATE SETTINGS
             ========================================== -->
        <section id="tab-settings" class="tab-content">
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3><i class="fas fa-plug" style="color: var(--secondary);"></i> API Connections & Automations</h3>
                        <p>Link external APIs and background cron runners to automate your workflows.</p>
                    </div>
                    <div>
                        <button class="btn btn-success btn-sm" onclick="saveSettings()">
                            <i class="fas fa-save"></i> Save Settings
                        </button>
                    </div>
                </div>

                <?php if (!empty($_GET['connected']) && $_GET['connected'] === 'google_success'): ?>
                    <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid var(--success); color: #fff; padding: 14px 18px; border-radius: var(--radius-sm); margin-bottom: 20px; display:flex; align-items:center; gap: 12px;">
                        <i class="fas fa-check-circle" style="color: var(--success); font-size: 1.4rem;"></i>
                        <div><strong>Google Business Profile Connected!</strong> Your official Google account is authenticated and synced for live Google Maps review replies and local post syndication.</div>
                    </div>
                <?php elseif (!empty($_GET['error'])): ?>
                    <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid var(--danger); color: #fff; padding: 14px 18px; border-radius: var(--radius-sm); margin-bottom: 20px; display:flex; align-items:center; gap: 12px;">
                        <i class="fas fa-exclamation-triangle" style="color: var(--danger); font-size: 1.4rem;"></i>
                        <div><strong>Google OAuth Notice:</strong> <?= htmlspecialchars($_GET['error']) ?></div>
                    </div>
                <?php endif; ?>

                <!-- Google Cloud OAuth Connection (Official Google Business Profile API) -->
                <div style="background: rgba(66, 133, 244, 0.08); border: 1px solid rgba(66, 133, 244, 0.3); border-radius: var(--radius-sm); padding: 18px; margin-bottom: 24px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 14px;">
                        <h4 style="font-size: 1.05rem; color: #fff; display:flex; align-items:center; gap: 8px;">
                            <i class="fab fa-google" style="color: #4285F4;"></i> Google Business Profile API Connection
                        </h4>
                        <?php if ($isGoogleConnected): ?>
                            <span class="status-pill success"><i class="fas fa-check-circle"></i> Connected (ID: <?= htmlspecialchars($profile['google_profile_id'] ?? '') ?>)</span>
                        <?php else: ?>
                            <span class="status-pill warning"><i class="fas fa-exclamation-circle"></i> Not Connected</span>
                        <?php endif; ?>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Client ID</label>
                            <input type="text" class="form-control" id="settingGmbClientId" value="<?= htmlspecialchars($googleOAuth['client_id'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Client Secret</label>
                            <input type="password" class="form-control" id="settingGmbClientSecret" value="<?= htmlspecialchars($googleOAuth['client_secret'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 14px;">
                        <label class="form-label">Authorized Redirect URI (Ensure this is in your Google Cloud Console)</label>
                        <div style="display:flex; gap: 8px;">
                            <input type="text" class="form-control" id="settingRedirectUri" readonly value="http://localhost/SEO/api.php?action=gmb_callback" style="font-family: monospace; font-size: 0.82rem;">
                            <button class="btn btn-outline btn-sm" onclick="navigator.clipboard.writeText(document.getElementById('settingRedirectUri').value); showToast('Redirect URI copied!');">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </div>
                    </div>

                    <div style="display:flex; gap: 12px; align-items:center;">
                        <a href="api.php?action=gmb_login" class="btn btn-primary" style="background: linear-gradient(135deg, #4285F4, #34A853);">
                            <i class="fab fa-google"></i> Authorize & Connect Google Business Profile
                        </a>
                        <span style="font-size: 0.78rem; color: var(--text-dim);">Uses official OAuth 2.0 to manage reviews and posts.</span>
                    </div>
                </div>

                <!-- AI Models -->
                <div style="margin-bottom: 24px;">
                    <h4 style="font-size: 0.95rem; margin-bottom: 12px; color: #fff;"><i class="fas fa-brain"></i> AI Engines (OpenAI / Gemini)</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">OpenAI API Key (Optional)</label>
                            <input type="password" class="form-control" id="settingOpenAiKey" placeholder="sk-...">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Gemini API Key (Optional)</label>
                            <input type="password" class="form-control" id="settingGeminiKey" placeholder="AIzaSy...">
                        </div>
                    </div>
                    <div class="form-group" style="margin-top: 10px;">
                        <label class="form-label"><i class="fas fa-map-marked-alt" style="color: #4285F4;"></i> Google Maps Platform API Key</label>
                        <input type="password" class="form-control" id="settingGoogleMapsKey" placeholder="AIzaSy..." value="<?= htmlspecialchars($settings['google_maps_api_key'] ?? '') ?>">
                        <small style="font-size: 0.76rem; color: var(--text-dim);">Powers authentic Google Maps roads, places, and geo-grid rank tracking.</small>
                    </div>
                    <p style="font-size: 0.78rem; color: var(--text-dim); margin-top: 8px;">* Even without API keys, LocalRank Pro includes an intelligent algorithmic template engine that automatically creates localized keyword replies!</p>
                </div>

                <!-- Website WordPress REST API -->
                <div style="margin-bottom: 24px; border-top: 1px solid var(--border-color); padding-top: 20px;">
                    <h4 style="font-size: 0.95rem; margin-bottom: 12px; color: #fff;"><i class="fab fa-wordpress"></i> Website Connection (WordPress REST API)</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">WordPress Site URL</label>
                            <input type="url" class="form-control" id="settingWpUrl" placeholder="https://example.com">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Application Username</label>
                            <input type="text" class="form-control" id="settingWpUser" placeholder="admin">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Application Password</label>
                            <input type="password" class="form-control" id="settingWpPass" placeholder="xxxx xxxx xxxx xxxx">
                        </div>
                    </div>
                </div>

                <!-- Automation Rules -->
                <div style="margin-bottom: 24px; border-top: 1px solid var(--border-color); padding-top: 20px;">
                    <h4 style="font-size: 0.95rem; margin-bottom: 12px; color: #fff;"><i class="fas fa-robot"></i> Automation Triggers</h4>
                    <div class="form-group">
                        <label style="display:flex; align-items:center; gap: 10px; cursor: pointer;">
                            <input type="checkbox" id="settingAutoReply" checked>
                            <span>Automatically generate and publish AI local keyword replies to 4★ and 5★ reviews</span>
                        </label>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Outgoing Webhook URL (Zapier / Make / n8n)</label>
                        <input type="url" class="form-control" id="settingWebhookUrl" placeholder="https://hooks.zapier.com/hooks/catch/...">
                    </div>
                </div>

                <!-- Cron Runner Setup -->
                <div style="border-top: 1px solid var(--border-color); padding-top: 20px;">
                    <h4 style="font-size: 0.95rem; margin-bottom: 8px; color: #fff;"><i class="fas fa-terminal"></i> Background Cron Scheduler</h4>
                    <p style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 10px;">To enable 24/7 background review auto-replies and scheduled post publishing, add this cron job to your server:</p>
                    <div class="code-box">
                        <button class="copy-btn" onclick="navigator.clipboard.writeText('* * * * * php /Applications/XAMPP/xamppfiles/htdocs/SEO/cron.php >/dev/null 2>&1'); showToast('Cron line copied!');"><i class="fas fa-copy"></i> Copy</button>
                        <code>* * * * * php <?= __DIR__ ?>/cron.php >/dev/null 2>&1</code>
                    </div>
                </div>
            </div>
        </section>

    </main>
</div>

<!-- ADD REVIEW MODAL -->
<div id="addReviewModal" style="display:none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(8px); z-index: 99999; align-items: center; justify-content: center; padding: 20px;">
    <div style="background: var(--bg-card); border: 1px solid var(--border-active); border-radius: var(--radius-md); max-width: 520px; width: 100%; padding: 24px; box-shadow: 0 20px 50px rgba(0,0,0,0.8);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 16px;">
            <h3 style="margin:0; font-size: 1.15rem;"><i class="fas fa-star" style="color: #fbbc05;"></i> Add Real Customer Review</h3>
            <button onclick="closeAddReviewModal()" style="background:transparent; border:none; color: var(--text-dim); font-size: 1.2rem; cursor:pointer;"><i class="fas fa-times"></i></button>
        </div>
        <div class="form-group" style="margin-bottom: 12px;">
            <label class="form-label">Customer Name</label>
            <input type="text" class="form-control" id="newRevAuthor" placeholder="e.g. Ramesh Kumar">
        </div>
        <div class="form-group" style="margin-bottom: 12px;">
            <label class="form-label">Star Rating</label>
            <select class="form-control" id="newRevRating">
                <option value="5">★★★★★ (5 Stars)</option>
                <option value="4">★★★★☆ (4 Stars)</option>
                <option value="3">★★★☆☆ (3 Stars)</option>
            </select>
        </div>
        <div class="form-group" style="margin-bottom: 16px;">
            <label class="form-label">Customer Review Comment</label>
            <textarea class="form-control" id="newRevComment" rows="3" placeholder="Paste the client's actual Google feedback here..."></textarea>
        </div>
        <div style="display:flex; justify-content:flex-end; gap: 10px;">
            <button class="btn btn-outline" onclick="closeAddReviewModal()">Cancel</button>
            <button class="btn btn-primary" onclick="submitNewCustomerReview()">
                <i class="fas fa-save"></i> Save Customer Review
            </button>
        </div>
    </div>
</div>

<!-- AI REVIEW REPLY MODAL -->
<div id="replyModal" style="display:none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(8px); z-index: 99999; align-items: center; justify-content: center; padding: 20px;">
    <div style="background: var(--bg-card); border: 1px solid var(--border-active); border-radius: var(--radius-md); max-width: 600px; width: 100%; padding: 24px; box-shadow: 0 20px 50px rgba(0,0,0,0.8);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 16px;">
            <h3 style="margin:0; font-size: 1.15rem;"><i class="fas fa-robot" style="color: var(--secondary);"></i> Review Auto-AI Response</h3>
            <button onclick="closeReplyModal()" style="background:transparent; border:none; color: var(--text-dim); font-size: 1.2rem; cursor:pointer;"><i class="fas fa-times"></i></button>
        </div>

        <input type="hidden" id="modalReviewId">
        
        <div style="background: rgba(255,255,255,0.03); padding: 12px; border-radius: var(--radius-sm); margin-bottom: 14px; font-size: 0.85rem;">
            <div>Author: <strong id="modalAuthorName" style="color: #fff;">--</strong></div>
            <div style="color: var(--text-muted); margin-top: 4px;" id="modalComment">--</div>
        </div>

        <div style="margin-bottom: 10px; display:flex; justify-content:space-between; align-items:center;">
            <span class="status-pill success" id="modalKeywordBadge">Optimized with Local Keywords</span>
        </div>

        <div class="form-group">
            <label class="form-label">Review Response (Synced with Google Maps & SEO Engine)</label>
            <textarea class="form-control" id="modalReplyContent" rows="4"></textarea>
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center; margin-top: 18px; flex-wrap: wrap; gap: 10px;">
            <button class="btn btn-outline btn-sm" onclick="copyReplyAndOpenGoogle()" style="color: #4285F4; border-color: rgba(66,133,244,0.4);">
                <i class="fas fa-copy"></i> Copy Reply & Open Google Maps ↗
            </button>
            <div style="display:flex; gap: 10px;">
                <button class="btn btn-outline" onclick="closeReplyModal()">Close</button>
                <button class="btn btn-success" onclick="submitReviewReply()">
                    <i class="fas fa-check"></i> Save Local SEO Reply
                </button>
            </div>
        </div>
    </div>
</div>

<!-- LOCAL LANDING PAGE BLUEPRINT PREVIEW MODAL -->
<div id="localBlueprintModal" style="display:none; position: fixed; inset: 0; background: rgba(0,0,0,0.78); backdrop-filter: blur(8px); z-index: 99999; align-items: center; justify-content: center; padding: 20px;">
    <div style="background: var(--bg-card); border: 1px solid var(--border-active); border-radius: var(--radius-md); max-width: 680px; width: 100%; padding: 24px; box-shadow: 0 25px 60px rgba(0,0,0,0.9); max-height: 90vh; overflow-y: auto;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
            <div style="display:flex; align-items:center; gap: 10px;">
                <div style="width: 36px; height: 36px; border-radius: 8px; background: rgba(99, 102, 241, 0.15); display:flex; align-items:center; justify-content:center; color: var(--secondary);">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div>
                    <h3 style="margin:0; font-size: 1.15rem;" id="blueprintModalTitle">Local Landing Blueprint</h3>
                    <span style="font-size: 0.78rem; color: var(--text-muted);" id="blueprintModalLocality">Target Locality</span>
                </div>
            </div>
            <button onclick="closeBlueprintModal()" style="background:transparent; border:none; color: var(--text-dim); font-size: 1.2rem; cursor:pointer;"><i class="fas fa-times"></i></button>
        </div>

        <div style="display:flex; flex-direction:column; gap: 14px;">
            <div>
                <label style="font-size: 0.76rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-dim); font-weight: 600;">Target URL Slug</label>
                <div id="blueprintModalSlug" style="font-family: monospace; font-size: 0.88rem; color: var(--secondary); background: rgba(99,102,241,0.08); padding: 8px 12px; border-radius: 6px; border: 1px solid rgba(99,102,241,0.2);"></div>
            </div>

            <div>
                <label style="font-size: 0.76rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-dim); font-weight: 600;">SEO Meta Title Tag</label>
                <div id="blueprintModalSeoTitle" style="font-weight: 600; font-size: 0.92rem; color: #fff; background: rgba(255,255,255,0.03); padding: 8px 12px; border-radius: 6px; border: 1px solid var(--border-color);"></div>
            </div>

            <div>
                <label style="font-size: 0.76rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-dim); font-weight: 600;">Meta Description</label>
                <div id="blueprintModalMetaDesc" style="font-size: 0.85rem; color: var(--text-muted); background: rgba(255,255,255,0.03); padding: 8px 12px; border-radius: 6px; border: 1px solid var(--border-color);"></div>
            </div>

            <div>
                <label style="font-size: 0.76rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-dim); font-weight: 600;">Page Content Preview (H1, FAQs & Conversion Callout)</label>
                <div id="blueprintModalBody" style="font-size: 0.85rem; color: var(--text-secondary); background: rgba(0,0,0,0.3); padding: 12px; border-radius: 6px; border: 1px solid var(--border-color); max-height: 180px; overflow-y: auto; line-height: 1.5;"></div>
            </div>

            <div>
                <label style="font-size: 0.76rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-dim); font-weight: 600;">LocalBusiness & FAQ JSON-LD Schema</label>
                <pre id="blueprintModalSchema" style="font-family: monospace; font-size: 0.75rem; color: #34d399; background: #080c14; padding: 10px; border-radius: 6px; border: 1px solid rgba(52,211,153,0.25); overflow-x: auto; max-height: 140px; margin: 0;"></pre>
            </div>
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center; margin-top: 20px; border-top: 1px solid var(--border-color); padding-top: 14px;">
            <button class="btn btn-outline btn-sm" onclick="copyBlueprintHtml()">
                <i class="fas fa-copy"></i> Copy HTML
            </button>
            <div style="display:flex; gap: 10px;">
                <button class="btn btn-outline" onclick="closeBlueprintModal()">Close</button>
                <button class="btn btn-success" id="btnModalPublishWp" onclick="publishModalBlueprintToWp()">
                    <i class="fab fa-wordpress"></i> Publish to WordPress Now
                </button>
            </div>
        </div>
    </div>
</div>

<!-- GOOGLE BUSINESS PROFILE POST LAUNCHER MODAL -->
<div id="gmbPublishAssistantModal" style="display:none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); z-index: 99999; align-items: center; justify-content: center; padding: 20px;">
    <div style="background: #0f172a; border: 1px solid rgba(66, 133, 244, 0.35); border-radius: 16px; max-width: 580px; width: 100%; box-sizing: border-box; padding: 24px; box-shadow: 0 25px 60px -15px rgba(0,0,0,0.9), 0 0 35px rgba(66, 133, 244, 0.15); display: flex; flex-direction: column; gap: 14px; max-height: 92vh; overflow-y: auto;">
        
        <!-- Modal Header -->
        <div style="display:flex; justify-content:space-between; align-items:flex-start;">
            <div style="display:flex; align-items:center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(66, 133, 244, 0.12); border: 1px solid rgba(66, 133, 244, 0.3); display:flex; align-items:center; justify-content:center; flex-shrink: 0;">
                    <i class="fab fa-google" style="color: #4285F4; font-size: 1.25rem;"></i>
                </div>
                <div>
                    <h3 style="margin:0; font-size: 1.15rem; font-weight: 700; color: #F8FAFC; letter-spacing: -0.01em;">
                        Publish to Google Business Profile
                    </h3>
                    <div style="font-size: 0.78rem; color: #94A3B8; margin-top: 2px;">
                        Live Post Assistant & 1-Click Clipboard Launcher
                    </div>
                </div>
            </div>
            <button type="button" onclick="closeGmbPublishAssistant()" style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1); color: var(--text-muted); width: 32px; height: 32px; border-radius: 8px; font-size: 1rem; cursor:pointer; display:flex; align-items:center; justify-content:center; transition: all 0.2s;" onmouseover="this.style.color='#fff'; this.style.background='rgba(255,255,255,0.15)'" onmouseout="this.style.color='var(--text-muted)'; this.style.background='rgba(255,255,255,0.06)'">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Success Toast / Status Banner -->
        <div style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(5, 150, 105, 0.08)); border: 1px solid rgba(16, 185, 129, 0.35); border-radius: 10px; padding: 10px 14px; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-check-circle" style="color: #10B981; font-size: 1.15rem; flex-shrink: 0;"></i>
            <div style="font-size: 0.83rem; color: #E2E8F0; line-height: 1.35;">
                <strong style="color: #34D399;">Post text copied to clipboard!</strong> Ready to paste into Google.
            </div>
        </div>

        <!-- Post Textarea Area -->
        <div style="display:flex; flex-direction:column; gap: 6px;">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <label style="font-size: 0.78rem; font-weight: 600; color: #CBD5E1; margin: 0;">
                    Post Text <span style="color: #64748B; font-weight: normal;">(Ready to Paste)</span>
                </label>
                <button type="button" class="btn btn-outline btn-sm" onclick="copyGmbAssistantText()" style="padding: 2px 10px; font-size: 0.72rem; color: #34D399; border-color: rgba(52,211,153,0.35); display:inline-flex; align-items:center; gap: 5px;">
                    <i class="fas fa-copy"></i> Copy Text
                </button>
            </div>
            <textarea class="form-control" id="gmbAssistantText" rows="4" style="font-size: 0.82rem; line-height: 1.45; background: #0b1120; border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: #F1F5F9; padding: 10px 12px; resize: vertical; box-sizing: border-box; width: 100%;"></textarea>
        </div>

        <!-- 2-Column Responsive Card: Button CTA & Image -->
        <div style="display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 10px; width: 100%; box-sizing: border-box;">
            <!-- Column 1: CTA Button & Link -->
            <div style="min-width: 0; background: #131d33; border: 1px solid rgba(66, 133, 244, 0.2); border-radius: 10px; padding: 10px 12px; display:flex; flex-direction:column; justify-content:space-between; gap: 6px; box-sizing: border-box;">
                <div>
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 4px;">
                        <span style="font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #64748B;">Action Button</span>
                        <span id="gmbAssistantCtaBadge" class="status-pill info" style="font-size: 0.68rem; padding: 1px 6px;">LEARN_MORE</span>
                    </div>
                    <div id="gmbAssistantCtaUrl" style="font-size: 0.76rem; color: #93C5FD; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block;" title="CTA URL">
                        https://codespark.online/contact/
                    </div>
                    <div id="gmbAssistantCtaType" style="display:none;"></div>
                </div>
                <div>
                    <button type="button" class="btn btn-outline btn-sm" onclick="copyGmbAssistantUrl()" style="padding: 3px 8px; font-size: 0.7rem; color: #60A5FA; border-color: rgba(96, 165, 250, 0.35); display:inline-flex; align-items:center; gap: 5px; width: 100%; justify-content:center;">
                        <i class="fas fa-link"></i> Copy Button URL
                    </button>
                </div>
            </div>

            <!-- Column 2: Image Preview & URL -->
            <div style="min-width: 0; background: #131d33; border: 1px solid rgba(66, 133, 244, 0.2); border-radius: 10px; padding: 10px 12px; display:flex; flex-direction:column; justify-content:space-between; gap: 6px; box-sizing: border-box;">
                <div style="display:flex; align-items:center; gap: 8px; min-width: 0;">
                    <img id="gmbAssistantImgThumb" src="" alt="Thumbnail" style="width: 36px; height: 36px; border-radius: 6px; object-fit: cover; border: 1px solid rgba(255,255,255,0.1); flex-shrink: 0; display: none;">
                    <div style="min-width: 0; flex: 1;">
                        <span style="font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #64748B; display:block; margin-bottom: 2px;">Image URL</span>
                        <div id="gmbAssistantImageText" style="font-size: 0.76rem; color: #94A3B8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block;" title="Image Link">
                            image.jpg
                        </div>
                    </div>
                </div>
                <div>
                    <button type="button" class="btn btn-outline btn-sm" onclick="copyGmbAssistantImage()" style="padding: 3px 8px; font-size: 0.7rem; color: #38BDF8; border-color: rgba(56, 189, 248, 0.35); display:inline-flex; align-items:center; gap: 5px; width: 100%; justify-content:center;">
                        <i class="fas fa-image"></i> Copy Image URL
                    </button>
                </div>
            </div>
        </div>

        <!-- Launch Button -->
        <div style="display:flex; flex-direction: column; gap: 8px; margin-top: 4px;">
            <a id="btnGmbAssistantOpenGoogle" href="https://business.google.com/" target="_blank" class="btn btn-primary" style="justify-content:center; text-decoration:none; background: linear-gradient(135deg, #2563eb, #1d4ed8); border: 1px solid rgba(255,255,255,0.15); font-size: 0.92rem; font-weight: 600; padding: 12px; border-radius: 10px; box-shadow: 0 4px 14px rgba(37, 99, 235, 0.4); display:flex; align-items:center; gap: 8px;">
                <i class="fab fa-google"></i> 1. Open Google Business Profile (+ Add post) ↗
            </a>
            <p style="margin: 0; font-size: 0.75rem; color: #94A3B8; text-align: center; line-height: 1.4;">
                Click above to open Google's post screen, click <strong style="color:#E2E8F0;">"+ Add update"</strong>, then press <kbd style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 4px; padding: 1px 5px; font-size: 0.7rem; color: #fff;">Ctrl+V</kbd> / <kbd style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 4px; padding: 1px 5px; font-size: 0.7rem; color: #fff;">Cmd+V</kbd> to paste!
            </p>
        </div>

        <!-- Footer -->
        <div style="display:flex; justify-content:flex-end; padding-top: 4px; border-top: 1px solid rgba(255,255,255,0.06);">
            <button class="btn btn-outline btn-sm" onclick="closeGmbPublishAssistant()" style="padding: 6px 16px; font-size: 0.82rem; border-color: rgba(255,255,255,0.15); color: #CBD5E1;">
                Done
            </button>
        </div>
    </div>
</div>

<!-- TOAST NOTIFICATION CONTAINER -->
<div id="toastContainer"></div>

<!-- Leaflet JS for Maps -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<!-- App JavaScript Controller -->
<script src="assets/js/app.js?v=<?= time() ?>"></script>

</body>
</html>
