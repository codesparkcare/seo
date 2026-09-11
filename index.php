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
            <li class="nav-item" data-tab="website-seo">
                <i class="fas fa-globe"></i>
                <span>Website SEO & Schema</span>
            </li>
            <li class="nav-item" data-tab="social">
                <i class="fas fa-share-alt"></i>
                <span>Social & Citations</span>
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
                        <button class="btn btn-outline btn-sm" onclick="switchTab('social')">New Post</button>
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
                        <div style="display:flex; gap: 8px; margin-bottom: 14px;">
                            <input type="text" class="form-control" id="reviewLinkInput" readonly style="font-family: monospace; font-size: 0.85rem;">
                            <button class="btn btn-outline" onclick="navigator.clipboard.writeText(document.getElementById('reviewLinkInput').value); showToast('Review link copied!');">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </div>
                        <div style="display:flex; gap: 10px;">
                            <a class="btn btn-success btn-sm" id="whatsappShareBtn" target="_blank" href="https://api.whatsapp.com/send?text=Hi!%20Could%20you%20take%2015%20seconds%20to%20leave%20us%20a%20Google%20review?">
                                <i class="fab fa-whatsapp"></i> Share on WhatsApp
                            </a>
                            <button class="btn btn-outline btn-sm" onclick="showToast('Print QR poster template ready!');">
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
                    <div>
                        <button class="btn btn-outline btn-sm" onclick="loadReviews()"><i class="fas fa-sync"></i> Refresh</button>
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
                        <label class="form-label">Services (comma separated)</label>
                        <input type="text" class="form-control" id="programmaticServices" value="Software Company, Mobile App Development, Billing Software, Web Design, Custom ERP Development">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Target Cities / Localities (up to 60 km radius)</label>
                        <input type="text" class="form-control" id="programmaticLocations" value="Melapalayam, Palayamkottai, Tenkasi, Thoothukudi, Kovilpatti, Ambasamudram, Valliyur, Nanguneri, Cheranmahadevi, Sankarankovil, Tiruchendur">
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
             TAB 5: SOCIAL & CITATIONS SYNDICATOR
             ========================================== -->
        <section id="tab-social" class="tab-content">
            <!-- Multi-Channel Composer -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3><i class="fas fa-share-nodes" style="color: var(--primary);"></i> Omnichannel Local Social & GMB Composer</h3>
                        <p>Cross-post geo-tagged updates simultaneously to Google Business Profile, Facebook, and LinkedIn.</p>
                    </div>
                </div>
                <div style="background: rgba(99, 102, 241, 0.08); border: 1px dashed var(--primary); border-radius: var(--radius-sm); padding: 14px; margin-bottom: 18px; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px;">
                    <div>
                        <strong style="color: #fff; font-size: 0.92rem;"><i class="fas fa-robot" style="color: var(--primary);"></i> AI Autonomous Publisher to codespark.online</strong>
                        <p style="font-size: 0.8rem; color: var(--text-muted); margin: 2px 0 0 0;">Automatically creates an SEO article targeted for your Tirunelveli keywords and publishes live to your website.</p>
                    </div>
                    <div>
                        <button class="btn btn-primary btn-sm" id="btnAutoPublishTabSocial" onclick="triggerAutoCreatePost()">
                            <i class="fas fa-magic"></i> Auto-Generate & Publish Post
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Post Headline / Topic</label>
                    <input type="text" class="form-control" id="postTitleInput" placeholder="e.g. Top 3 Google Map Strategy Workshop for Local Businesses">
                </div>

                <div class="form-group">
                    <label class="form-label">Post Body (Include local hashtags & geo intent)</label>
                    <textarea class="form-control" id="postContentInput" rows="3" placeholder="Write your localized business update here..."></textarea>
                </div>

                <!-- Dedicated SEO Meta Tags Box (Gemini AI Powered) -->
                <div style="background: rgba(16, 185, 129, 0.05); border: 1px solid rgba(16, 185, 129, 0.25); border-radius: var(--radius-sm); padding: 14px; margin-bottom: 18px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                        <div>
                            <strong style="color: #34d399; font-size: 0.92rem;"><i class="fas fa-search"></i> SEO Meta Tags (Rank Math, Google Snippet & Schema)</strong>
                            <p style="font-size: 0.78rem; color: var(--text-muted); margin: 2px 0 0 0;">These tags are embedded into WordPress SEO fields and rich JSON-LD schema for high Google rankings.</p>
                        </div>
                        <button type="button" class="btn btn-outline btn-sm" id="btnGenMetaAi" onclick="generateAiSeoMeta()" style="border-color: rgba(52, 211, 153, 0.4); color: #34d399;">
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

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Featured Image URL</label>
                        <input type="url" class="form-control" id="postImageInput" placeholder="https://images.unsplash.com/...">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Call-To-Action (CTA) Button</label>
                        <select class="form-control" id="postCtaSelect">
                            <option value="LEARN_MORE">Learn More</option>
                            <option value="BOOK">Book Appointment</option>
                            <option value="CALL">Call Now</option>
                            <option value="SIGN_UP">Sign Up</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">CTA Target Destination Link</label>
                        <input type="url" class="form-control" id="postCtaUrlInput" placeholder="https://example.com/contact">
                    </div>
                </div>

                    <div class="form-group">
                        <label class="form-label">Destination Channels</label>
                        <div style="display:flex; flex-wrap: wrap; gap: 16px; margin-top: 6px;">
                            <label style="display:flex; align-items:center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" id="platWordpress" checked> <i class="fab fa-wordpress" style="color: #21759B;"></i> WordPress (codespark.online)
                            </label>
                            <label style="display:flex; align-items:center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" id="platGmb" checked> <i class="fab fa-google" style="color: #4285F4;"></i> Google Business Profile (Map Pack)
                            </label>
                            <label style="display:flex; align-items:center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" id="platFacebook" checked> <i class="fab fa-facebook" style="color: #1877F2;"></i> Facebook Page (@codesparksoftware)
                            </label>
                            <label style="display:flex; align-items:center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" id="platInstagram" checked> <i class="fab fa-instagram" style="color: #E1306C;"></i> Instagram (@codesparksoftwaredevelopment)
                            </label>
                            <label style="display:flex; align-items:center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" id="platYoutube" checked> <i class="fab fa-youtube" style="color: #FF0000;"></i> YouTube Community (@CODESPARK-ek8fb)
                            </label>
                            <label style="display:flex; align-items:center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" id="platLinkedin"> <i class="fab fa-linkedin" style="color: #0A66C2;"></i> LinkedIn
                            </label>
                            <label style="display:flex; align-items:center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" id="platTwitter"> <i class="fab fa-x-twitter"></i> X / Twitter
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Schedule Time (Leave blank to publish immediately)</label>
                        <input type="datetime-local" class="form-control" id="postScheduleInput">
                    </div>

                    <div style="display:flex; gap: 12px; margin-top: 14px;">
                        <button class="btn btn-primary" id="btnPublishNow" onclick="submitSocialPost(true)">
                            <i class="fas fa-paper-plane"></i> Publish Now to All Channels
                        </button>
                        <button class="btn btn-outline" id="btnScheduleLater" onclick="submitSocialPost(false)">
                            <i class="fas fa-clock"></i> Schedule For Later
                        </button>
                    </div>
            </div>

            <!-- Citations & Directory Consistency Checker -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3><i class="fas fa-address-book" style="color: var(--secondary);"></i> Local Directory Citations & NAP Consistency</h3>
                        <p>Google heavily checks NAP (Name, Address, Phone) consistency across directories to verify your office location.</p>
                    </div>
                </div>

                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Directory Platform</th>
                                <th>Profile Link</th>
                                <th>Authority</th>
                                <th>NAP Match Status</th>
                                <th>Audit Notes</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="citationsTableBody">
                            <tr><td colspan="6" class="text-muted" style="text-align:center; padding: 20px;">Loading citations audit...</td></tr>
                        </tbody>
                    </table>
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
                        <input type="text" class="form-control" id="profPlaceId" value="<?= htmlspecialchars($profile['google_place_id'] ?? '') ?>">
                    </div>
                </div>

                <div style="background: rgba(99, 102, 241, 0.08); border: 1px solid rgba(99, 102, 241, 0.25); border-radius: var(--radius-sm); padding: 18px; margin: 18px 0;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 8px;">
                        <h4 style="font-size: 0.95rem; color: #fff; display:flex; align-items:center; gap: 8px;">
                            <i class="fas fa-crosshairs" style="color: var(--primary);"></i> 20 Primary Target SEO Keywords (Google Gemini AI Engine)
                        </h4>
                        <span class="status-pill success"><i class="fas fa-robot"></i> Gemini Optimized</span>
                    </div>
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 12px;">
                        Google Gemini Pro uses these exact 20 keywords to optimize your Google Maps review replies, geo-grid heatmap tracking, social posts, and WordPress local landing pages.
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

        <div style="display:flex; justify-content:flex-end; gap: 10px; margin-top: 18px;">
            <button class="btn btn-outline" onclick="closeReplyModal()">Cancel</button>
            <button class="btn btn-success" onclick="submitReviewReply()">
                <i class="fas fa-check"></i> Approve & Sync to Google Maps
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
