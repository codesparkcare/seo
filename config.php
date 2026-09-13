<?php
/**
 * LocalRank Pro - 100% Database-Free Configuration
 * Uses clean JSON file storage (config.json) without SQLite or MySQL.
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

define('APP_ROOT', __DIR__);
define('CONFIG_FILE', APP_ROOT . '/config.json');

// Read Configuration
function loadConfig() {
    if (!file_exists(CONFIG_FILE)) {
        $exampleFile = APP_ROOT . '/config.example.json';
        if (file_exists($exampleFile)) {
            copy($exampleFile, CONFIG_FILE);
        } else {
            return [];
        }
    }
    $content = file_get_contents(CONFIG_FILE);
    return json_decode($content, true) ?: [];
}

// Save Configuration
function saveConfig($data) {
    return file_put_contents(CONFIG_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

// JSON Output Helper
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

// Google OAuth Access Token with Auto-Refresh
function getGoogleAccessToken(&$config) {
    $oauth = $config['google_oauth'] ?? [];
    if (empty($oauth['access_token'])) return null;
    
    // Check if token expired or about to expire in 60s
    if (time() >= ($oauth['token_expires_at'] ?? 0) - 60 && !empty($oauth['refresh_token']) && !empty($oauth['client_id']) && !empty($oauth['client_secret'])) {
        $tokenUrl = 'https://oauth2.googleapis.com/token';
        $ch = curl_init($tokenUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'client_id' => $oauth['client_id'],
            'client_secret' => $oauth['client_secret'],
            'refresh_token' => $oauth['refresh_token'],
            'grant_type' => 'refresh_token'
        ]));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);
        $res = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($res, true);
        if (!empty($data['access_token'])) {
            $config['google_oauth']['access_token'] = $data['access_token'];
            $config['google_oauth']['token_expires_at'] = time() + ($data['expires_in'] ?? 3600);
            $config['google_oauth']['is_connected'] = true;
            saveConfig($config);
            return $data['access_token'];
        }
    }
    return $oauth['access_token'];
}

// -------------------------------------------------------------
// Codespark Keyword Specific Landing Pages & Application Forms
// -------------------------------------------------------------
function getCodesparkLandingPages() {
    return [
        [
            'title' => 'Website Design & Development',
            'url' => 'https://codespark.online/best-website-design-for-your-business/',
            'keywords' => ['web', 'website', 'design', 'ecommerce', 'e-commerce', 'redesign']
        ],
        [
            'title' => 'Easy Billing Software & POS',
            'url' => 'https://codespark.online/easy-billing-software/',
            'keywords' => ['billing', 'pos', 'invoice', 'gst', 'inventory', 'retail', 'barcode']
        ],
        [
            'title' => 'Software Internship for Students',
            'url' => 'https://codespark.online/internship-for-students/',
            'keywords' => ['internship', 'student', 'college', 'training', 'python', 'full stack', 'intern', 'course']
        ],
        [
            'title' => 'IT Career & Internship Program',
            'url' => 'https://codespark.online/internship/',
            'keywords' => ['career', 'software internship', 'it company', 'institute', 'developer']
        ],
        [
            'title' => 'Digital Marketing & SEO Services',
            'url' => 'https://codespark.online/digital-marketing-for-your-business/',
            'keywords' => ['seo', 'marketing', 'digital marketing', 'google rank', 'local seo', 'social media', 'traffic']
        ],
        [
            'title' => 'High-Speed Cloud Hosting Provider',
            'url' => 'https://codespark.online/cloud-hosting-provider/',
            'keywords' => ['cloud', 'hosting', 'server', 'vps', 'domain', 'web host']
        ],
        [
            'title' => 'Contact & Custom Software Inquiries',
            'url' => 'https://codespark.online/contact/',
            'keywords' => ['app', 'mobile', 'android', 'ios', 'custom software', 'contact']
        ]
    ];
}

function getCodesparkLandingPageForKeyword($kw) {
    $kwLower = strtolower($kw);
    $pages = getCodesparkLandingPages();
    foreach ($pages as $p) {
        foreach ($p['keywords'] as $term) {
            if (strpos($kwLower, $term) !== false) {
                return $p['url'];
            }
        }
    }
    return 'https://codespark.online/contact/';
}

// -------------------------------------------------------------
// Google 1st-Page Authority Reference Links
// -------------------------------------------------------------
function getAuthorityLinksForKeyword($kw) {
    $kwLower = strtolower($kw);
    if (strpos($kwLower, 'app') !== false || strpos($kwLower, 'mobile') !== false || strpos($kwLower, 'android') !== false) {
        return [
            ['title' => 'Official Android Developers Architecture Guidelines', 'url' => 'https://developer.android.com/topic/architecture'],
            ['title' => 'W3C Mobile Web Application Best Practices', 'url' => 'https://www.w3.org/TR/mobile-bp/']
        ];
    }
    if (strpos($kwLower, 'intern') !== false || strpos($kwLower, 'training') !== false || strpos($kwLower, 'python') !== false) {
        return [
            ['title' => 'Python Software Foundation Official Documentation', 'url' => 'https://docs.python.org/3/'],
            ['title' => 'Mozilla Developer Network (MDN) Engineering Curriculum', 'url' => 'https://developer.mozilla.org/']
        ];
    }
    if (strpos($kwLower, 'bill') !== false || strpos($kwLower, 'pos') !== false) {
        return [
            ['title' => 'GST Official Portal Standards & E-Invoicing Guidelines', 'url' => 'https://www.gst.gov.in/'],
            ['title' => 'IEEE Software Engineering Standards for Point of Sale', 'url' => 'https://standards.ieee.org/']
        ];
    }
    if (strpos($kwLower, 'cloud') !== false || strpos($kwLower, 'host') !== false) {
        return [
            ['title' => 'Cloud Native Computing Foundation (CNCF) Guidelines', 'url' => 'https://www.cncf.io/'],
            ['title' => 'W3C Cloud & Scalable Web Standards', 'url' => 'https://www.w3.org/standards/']
        ];
    }
    if (strpos($kwLower, 'seo') !== false || strpos($kwLower, 'market') !== false) {
        return [
            ['title' => 'Google Search Central Official SEO Starter Guide', 'url' => 'https://developers.google.com/search/docs/fundamentals/seo-starter-guide'],
            ['title' => 'Schema.org Structured Data Vocabulary Standards', 'url' => 'https://schema.org/']
        ];
    }
    return [
        ['title' => 'World Wide Web Consortium (W3C) Web Design Standards', 'url' => 'https://www.w3.org/standards/webdesign/'],
        ['title' => 'Google Search Central Web Quality Guidelines', 'url' => 'https://developers.google.com/search/docs/fundamentals/seo-starter-guide']
    ];
}

// -------------------------------------------------------------
// Secondary In-Content Image & SEO Alt Text (Image 4 Style)
// -------------------------------------------------------------
function getSecondaryImageForKeyword($kw, $metaTitle = '') {
    $kwLower = strtolower($kw);
    $cleanKw = trim(preg_replace('/\s+in\s+Tirunelveli$/i', '', $kw));
    
    $url = 'https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?w=1000&auto=format&fit=crop';
    $alt = "Professional {$cleanKw} in Tirunelveli | CodeSpark offers SEO, website development, and Android & iOS mobile app development services.";
    
    if (strpos($kwLower, 'app') !== false || strpos($kwLower, 'mobile') !== false || strpos($kwLower, 'android') !== false) {
        $url = 'https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?w=1000&auto=format&fit=crop';
        $alt = "Custom Mobile App Development in Tirunelveli | CodeSpark builds scalable iOS and Android applications.";
    } elseif (strpos($kwLower, 'intern') !== false || strpos($kwLower, 'training') !== false || strpos($kwLower, 'python') !== false) {
        $url = 'https://images.unsplash.com/photo-1531482615713-2afd69097998?w=1000&auto=format&fit=crop';
        $alt = "Professional Software Solutions & Internship in Tirunelveli | CodeSpark offers practical live project mentorship.";
    } elseif (strpos($kwLower, 'bill') !== false || strpos($kwLower, 'pos') !== false) {
        $url = 'https://images.unsplash.com/photo-1556742049-0a67c5574f73?w=1000&auto=format&fit=crop';
        $alt = "GST Billing & POS Software in Tirunelveli | Fast barcode scanning, accounting and stock management by Codespark.";
    } elseif (strpos($kwLower, 'cloud') !== false || strpos($kwLower, 'host') !== false) {
        $url = 'https://images.unsplash.com/photo-1544197150-b99a580bb7a8?w=1000&auto=format&fit=crop';
        $alt = "Enterprise Cloud Hosting & Server Infrastructure in Tirunelveli | High speed 99.9% uptime by Codespark.";
    } elseif (strpos($kwLower, 'seo') !== false || strpos($kwLower, 'market') !== false) {
        $url = 'https://images.unsplash.com/photo-1557838923-2985c318be48?w=1000&auto=format&fit=crop';
        $alt = "Top Ranking SEO & Digital Marketing in Tirunelveli | Dominate Google 1st Page with Codespark.";
    }
    return ['url' => $url, 'alt' => $alt];
}

// -------------------------------------------------------------
// Auto-Match WordPress Categories & Tags from Cached JSON
// -------------------------------------------------------------
function getMatchingTaxonomiesForKeyword($kw) {
    $taxFile = APP_ROOT . '/wp_taxonomies.json';
    if (!file_exists($taxFile)) {
        return ['categories' => [], 'tags' => []];
    }
    $tax = json_decode(file_get_contents($taxFile), true) ?: [];
    $kwLower = strtolower($kw);
    
    $catIds = [];
    $tagIds = [];
    
    foreach ($tax['categories'] ?? [] as $c) {
        $cn = strtolower($c['name']);
        if (strpos($kwLower, 'intern') !== false && (strpos($cn, 'intern') !== false || strpos($cn, 'training') !== false)) {
            $catIds[] = (int)$c['id'];
        } elseif ((strpos($kwLower, 'app') !== false || strpos($kwLower, 'mobile') !== false) && (strpos($cn, 'app') !== false || strpos($cn, 'android') !== false)) {
            $catIds[] = (int)$c['id'];
        } elseif ((strpos($kwLower, 'bill') !== false || strpos($kwLower, 'pos') !== false) && strpos($cn, 'bill') !== false) {
            $catIds[] = (int)$c['id'];
        } elseif ((strpos($kwLower, 'web') !== false || strpos($kwLower, 'design') !== false) && (strpos($cn, 'web') !== false || strpos($cn, 'e-commerce') !== false)) {
            $catIds[] = (int)$c['id'];
        } elseif ((strpos($kwLower, 'seo') !== false || strpos($kwLower, 'market') !== false) && (strpos($cn, 'seo') !== false || strpos($cn, 'market') !== false)) {
            $catIds[] = (int)$c['id'];
        }
    }
    
    if (empty($catIds)) {
        foreach ($tax['categories'] ?? [] as $c) {
            $cn = strtolower($c['name']);
            if ($cn === 'an it company' || $cn === 'business it solutions' || $cn === 'seo') {
                $catIds[] = (int)$c['id'];
                if (count($catIds) >= 2) break;
            }
        }
    }
    
    foreach ($tax['tags'] ?? [] as $t) {
        $tn = strtolower($t['name']);
        if (strpos($kwLower, 'bill') !== false && strpos($tn, 'accounting') !== false) {
            $tagIds[] = (int)$t['id'];
            if (count($tagIds) >= 3) break;
        } elseif (strpos($tn, 'software') !== false || strpos($tn, 'website') !== false) {
            $tagIds[] = (int)$t['id'];
            if (count($tagIds) >= 3) break;
        }
    }
    
    return [
        'categories' => array_values(array_unique(array_slice($catIds, 0, 5))),
        'tags' => array_values(array_unique(array_slice($tagIds, 0, 5)))
    ];
}

// -------------------------------------------------------------
// High-Converting Lead Generation Card Builder
// -------------------------------------------------------------
function buildLeadMagnetBox($title, $landingUrl, $ctaLabel, $kw) {
    $encodedTitle = urlencode("Hello Codespark! I am inquiring regarding: " . $title);
    $waUrl = "https://wa.me/918110899000?text={$encodedTitle}";
    $telUrl = "tel:+918110899000";
    
    $html = "\n\n<!-- Codespark Premium Animated Lead Magnet Card -->\n";
    $html .= "<div class='cs-lead-magnet-wrapper' style='margin: 36px 0 28px 0; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif;'>\n";
    $html .= "  <style>\n";
    $html .= "    @keyframes cs-float-rocket {\n";
    $html .= "      0%, 100% { transform: translateY(0) rotate(0deg); }\n";
    $html .= "      50% { transform: translateY(-5px) rotate(8deg); }\n";
    $html .= "    }\n";
    $html .= "    @keyframes cs-pulse-wa {\n";
    $html .= "      0% { box-shadow: 0 0 0 0 rgba(37, 211, 102, 0.7); }\n";
    $html .= "      70% { box-shadow: 0 0 0 14px rgba(37, 211, 102, 0); }\n";
    $html .= "      100% { box-shadow: 0 0 0 0 rgba(37, 211, 102, 0); }\n";
    $html .= "    }\n";
    $html .= "    @keyframes cs-pulse-border {\n";
    $html .= "      0%, 100% { border-color: rgba(59, 130, 246, 0.55); box-shadow: 0 12px 30px -5px rgba(37, 99, 235, 0.25); }\n";
    $html .= "      50% { border-color: rgba(96, 165, 250, 0.85); box-shadow: 0 16px 36px -4px rgba(56, 189, 248, 0.38); }\n";
    $html .= "    }\n";
    $html .= "    .cs-lead-card {\n";
    $html .= "      position: relative;\n";
    $html .= "      background: linear-gradient(135deg, #070c18 0%, #0f172a 45%, #1e293b 100%);\n";
    $html .= "      border: 2px solid rgba(59, 130, 246, 0.5);\n";
    $html .= "      border-radius: 18px;\n";
    $html .= "      padding: 28px 26px;\n";
    $html .= "      color: #f8fafc;\n";
    $html .= "      overflow: hidden;\n";
    $html .= "      animation: cs-pulse-border 4s ease-in-out infinite;\n";
    $html .= "      transition: transform 0.3s ease, box-shadow 0.3s ease;\n";
    $html .= "    }\n";
    $html .= "    .cs-lead-card:hover {\n";
    $html .= "      transform: translateY(-2px);\n";
    $html .= "    }\n";
    $html .= "    .cs-rocket-icon {\n";
    $html .= "      display: inline-block;\n";
    $html .= "      animation: cs-float-rocket 2.4s ease-in-out infinite;\n";
    $html .= "      transform-origin: center;\n";
    $html .= "      margin-right: 8px;\n";
    $html .= "    }\n";
    $html .= "    .cs-btn-wa {\n";
    $html .= "      display: inline-flex;\n";
    $html .= "      align-items: center;\n";
    $html .= "      gap: 10px;\n";
    $html .= "      background: linear-gradient(135deg, #25d366 0%, #128c7e 100%);\n";
    $html .= "      color: #ffffff !important;\n";
    $html .= "      font-weight: 700;\n";
    $html .= "      padding: 13px 22px;\n";
    $html .= "      border-radius: 10px;\n";
    $html .= "      text-decoration: none !important;\n";
    $html .= "      font-size: 0.98rem;\n";
    $html .= "      animation: cs-pulse-wa 2.2s infinite;\n";
    $html .= "      transition: transform 0.22s ease, filter 0.22s ease;\n";
    $html .= "    }\n";
    $html .= "    .cs-btn-wa:hover {\n";
    $html .= "      transform: translateY(-3px) scale(1.02);\n";
    $html .= "      filter: brightness(1.08);\n";
    $html .= "    }\n";
    $html .= "    .cs-btn-tel {\n";
    $html .= "      display: inline-flex;\n";
    $html .= "      align-items: center;\n";
    $html .= "      gap: 10px;\n";
    $html .= "      background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);\n";
    $html .= "      color: #ffffff !important;\n";
    $html .= "      font-weight: 700;\n";
    $html .= "      padding: 13px 22px;\n";
    $html .= "      border-radius: 10px;\n";
    $html .= "      text-decoration: none !important;\n";
    $html .= "      font-size: 0.98rem;\n";
    $html .= "      box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35);\n";
    $html .= "      transition: transform 0.22s ease, box-shadow 0.22s ease;\n";
    $html .= "    }\n";
    $html .= "    .cs-btn-tel:hover {\n";
    $html .= "      transform: translateY(-3px) scale(1.02);\n";
    $html .= "      box-shadow: 0 8px 22px rgba(37, 99, 235, 0.55);\n";
    $html .= "    }\n";
    $html .= "    .cs-btn-cta {\n";
    $html .= "      display: inline-flex;\n";
    $html .= "      align-items: center;\n";
    $html .= "      gap: 8px;\n";
    $html .= "      background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);\n";
    $html .= "      color: #0f172a !important;\n";
    $html .= "      font-weight: 800;\n";
    $html .= "      padding: 13px 22px;\n";
    $html .= "      border-radius: 10px;\n";
    $html .= "      text-decoration: none !important;\n";
    $html .= "      font-size: 0.98rem;\n";
    $html .= "      box-shadow: 0 4px 14px rgba(245, 158, 11, 0.35);\n";
    $html .= "      transition: transform 0.22s ease, box-shadow 0.22s ease;\n";
    $html .= "    }\n";
    $html .= "    .cs-btn-cta:hover {\n";
    $html .= "      transform: translateY(-3px) scale(1.02);\n";
    $html .= "      box-shadow: 0 8px 22px rgba(245, 158, 11, 0.55);\n";
    $html .= "    }\n";
    $html .= "    .cs-trust-pill {\n";
    $html .= "      display: inline-flex;\n";
    $html .= "      align-items: center;\n";
    $html .= "      gap: 6px;\n";
    $html .= "      background: rgba(255, 255, 255, 0.08);\n";
    $html .= "      border: 1px solid rgba(255, 255, 255, 0.14);\n";
    $html .= "      padding: 5px 12px;\n";
    $html .= "      border-radius: 20px;\n";
    $html .= "      font-size: 0.82rem;\n";
    $html .= "      color: #93c5fd;\n";
    $html .= "      font-weight: 600;\n";
    $html .= "    }\n";
    $html .= "  </style>\n";
    $html .= "  <div class='cs-lead-card'>\n";
    $html .= "    <!-- Trust Badges Header Row -->\n";
    $html .= "    <div style='display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 14px;'>\n";
    $html .= "      <span class='cs-trust-pill'>⭐ 4.9/5 Verified Agency</span>\n";
    $html .= "      <span class='cs-trust-pill'>⚡ 15-Min Quick Response</span>\n";
    $html .= "      <span class='cs-trust-pill' style='color: #86efac;'>✔ Free Project Consultation</span>\n";
    $html .= "    </div>\n";
    $html .= "    <!-- Main Title -->\n";
    $html .= "    <h3 style='margin: 0 0 10px 0; color: #ffffff; font-size: 1.45rem; font-weight: 800; line-height: 1.35; display: flex; align-items: center;'>\n";
    $html .= "      <span class='cs-rocket-icon'>🚀</span> Ready to Elevate Your Business with Codespark?\n";
    $html .= "    </h3>\n";
    $html .= "    <p style='margin: 0 0 20px 0; color: #cbd5e1; font-size: 1.02rem; line-height: 1.65; max-width: 720px;'>\n";
    $html .= "      Partner with Tirunelveli's premier software engineering and digital growth team. Get a <strong>Free Strategy Consultation & Live Demo</strong> tailored specifically for your business or career goals!\n";
    $html .= "    </p>\n";
    $html .= "    <!-- Animated Action Buttons -->\n";
    $html .= "    <div style='display: flex; gap: 12px; flex-wrap: wrap; align-items: center; margin-bottom: 18px;'>\n";
    $html .= "      <a href='{$waUrl}' target='_blank' rel='noopener' class='cs-btn-wa'>\n";
    $html .= "        <span style='font-size: 1.15rem;'>💬</span> WhatsApp Us Now\n";
    $html .= "      </a>\n";
    $html .= "      <a href='{$telUrl}' class='cs-btn-tel'>\n";
    $html .= "        <span style='font-size: 1.15rem;'>📞</span> Call +91 81108 99000\n";
    $html .= "      </a>\n";
    if (!empty($landingUrl)) {
        $html .= "      <a href='{$landingUrl}' target='_blank' rel='noopener' class='cs-btn-cta'>\n";
        $html .= "        <span>👉</span> {$ctaLabel}\n";
        $html .= "      </a>\n";
    }
    $html .= "    </div>\n";
    $html .= "    <!-- Office Details Footer -->\n";
    $html .= "    <div style='border-top: 1px solid rgba(255,255,255,0.12); padding-top: 14px; font-size: 0.88rem; color: #94a3b8; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;'>\n";
    $html .= "      <div>\n";
    $html .= "        📍 <strong>Office:</strong> P.No.7A, Housing Board Colony, D.no.46/24, Melapalayam, Tirunelveli - 627005\n";
    $html .= "      </div>\n";
    $html .= "      <a href='https://maps.google.com/?cid=12008434771239848135' target='_blank' rel='noopener' style='color: #38bdf8; text-decoration: none; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;'>\n";
    $html .= "        Get Directions ↗\n";
    $html .= "      </a>\n";
    $html .= "    </div>\n";
    $html .= "  </div>\n";
    $html .= "</div>\n\n";
    
    return $html;
}

// -------------------------------------------------------------
// Complete Rich HTML Article Builder (Heading + Images + Authority + Lead)
// -------------------------------------------------------------
function renderRichPostContent($title, $content, $kw, $primaryImg = '', $secondaryImg = '', $secondaryAlt = '', $ctaUrl = '', $ctaType = 'LEARN_MORE') {
    // 1. Prominent H1 Title at top (Guarantees headline shows on codespark.online single post template)
    $cleanTitle = htmlspecialchars($title);
    $html = "<h1 class='wp-post-main-heading' style='font-size: 2.15rem; font-weight: 800; color: #0f172a; margin: 16px 0 24px 0; line-height: 1.35; letter-spacing: -0.02em;'>{$cleanTitle}</h1>\n\n";
    
    // 2. Primary Featured Image
    if (!empty($primaryImg)) {
        $html .= "<figure class='wp-block-image size-large' style='margin: 0 0 26px 0;'><img src='" . htmlspecialchars($primaryImg) . "' alt='{$cleanTitle}' class='wp-image-featured' style='width: 100%; max-height: 480px; object-fit: cover; border-radius: 12px; box-shadow: 0 6px 20px rgba(0,0,0,0.08);'></figure>\n\n";
    }
    
    // 3. Main Content paragraphs
    $html .= "<div class='wp-post-body-content' style='font-size: 1.05rem; line-height: 1.8; color: #334155;'>\n";
    
    // Decode any pre-escaped entities and strip markdown code fences if present
    $cleanRaw = preg_replace('/^```(?:html)?\s*/i', '', trim($content));
    $cleanRaw = preg_replace('/```$/i', '', trim($cleanRaw));
    $decoded = html_entity_decode($cleanRaw, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    
    // Check if content has HTML tags (e.g. <h2>, <p>, <strong>, <ul>, <li>)
    $hasHtml = (preg_match('/<\s*(?:p|h[1-6]|ul|ol|li|div|blockquote|strong|b|em|table|section|article|br)\b/i', $decoded) > 0);
    if ($hasHtml) {
        // Strip unsafe script/iframe tags while allowing all rich semantic formatting tags
        $cleanContent = strip_tags($decoded, '<p><br><h2><h3><h4><h5><h6><ul><ol><li><strong><b><em><i><a><blockquote><span><div><hr><table><tr><td><th><tbody><thead>');
        // Convert any stray h1 in body to h2 so the article has only one primary H1
        $cleanContent = preg_replace('/<h1\b[^>]*>(.*?)<\/h1>/i', '<h2 style="font-size: 1.55rem; font-weight: 700; color: #1e293b; margin: 24px 0 12px 0;">$1</h2>', $cleanContent);
        $html .= $cleanContent . "\n\n";
    } else {
        // Plain text: split into clean paragraphs
        $paragraphs = array_filter(array_map('trim', explode("\n\n", $cleanRaw)));
        if (!empty($paragraphs)) {
            foreach ($paragraphs as $p) {
                $html .= "<p style='margin-bottom: 16px;'>" . nl2br(htmlspecialchars($p)) . "</p>\n";
            }
            $html .= "\n";
        } else {
            $html .= "<p style='margin-bottom: 16px;'>" . nl2br(htmlspecialchars($cleanRaw)) . "</p>\n\n";
        }
    }
    
    // 4. Secondary In-Content Image with rich SEO Alt and caption (Image 4 format)
    if (!empty($secondaryImg)) {
        $safeAlt = htmlspecialchars($secondaryAlt ?: "Professional Software Solutions in Tirunelveli | CodeSpark offers SEO, website development, and Android & iOS mobile app development services.");
        $html .= "<figure class='wp-block-image size-large' style='margin: 32px 0 24px 0; text-align: center;'>\n";
        $html .= "  <img src='" . htmlspecialchars($secondaryImg) . "' alt='{$safeAlt}' title='{$safeAlt}' style='width: 100%; max-height: 420px; object-fit: cover; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);'>\n";
        $html .= "  <figcaption style='font-size: 0.88rem; color: #64748b; margin-top: 8px; font-style: italic; line-height: 1.5;'>{$safeAlt}</figcaption>\n";
        $html .= "</figure>\n\n";
    }
    
    // 5. Contextual Internal Link to Specific Landing Page / Form
    $landingUrl = $ctaUrl ?: getCodesparkLandingPageForKeyword($kw);
    $html .= "<div class='wp-contextual-link-box' style='background: #f8fafc; border-left: 4px solid #3b82f6; padding: 14px 18px; margin: 24px 0; border-radius: 0 8px 8px 0;'>\n";
    $html .= "  <p style='margin: 0; font-size: 0.96rem; color: #1e293b;'>📌 <strong>Application & Service Portal:</strong> To register, apply, or receive immediate technical assistance, visit our official <a href='{$landingUrl}' target='_blank' rel='noopener' style='color: #2563eb; font-weight: 700; text-decoration: underline;'>Codespark Service & Application Page</a>.</p>\n";
    $html .= "</div>\n\n";
    
    // 6. At Least Two Google 1st-Page Authority Reference Links
    $authLinks = getAuthorityLinksForKeyword($kw);
    $html .= "<div class='wp-authority-resources' style='background: #f1f5f9; border-radius: 10px; padding: 16px 20px; margin: 28px 0 20px 0;'>\n";
    $html .= "  <h4 style='margin: 0 0 10px 0; font-size: 0.95rem; font-weight: 700; color: #334155; text-transform: uppercase; letter-spacing: 0.04em;'>📚 Verified Industry Standards & Resources:</h4>\n";
    $html .= "  <ul style='margin: 0; padding-left: 20px; color: #475569; font-size: 0.92rem;'>\n";
    foreach ($authLinks as $link) {
        $html .= "    <li style='margin-bottom: 6px;'><a href='" . htmlspecialchars($link['url']) . "' target='_blank' rel='noopener noreferrer' style='color: #2563eb; font-weight: 600; text-decoration: underline;'>" . htmlspecialchars($link['title']) . "</a> <span style='font-size: 0.8rem; color: #64748b;'>(External Authority Link)</span></li>\n";
    }
    $html .= "  </ul>\n";
    $html .= "</div>\n\n";
    
    $html .= "</div>\n"; // End wp-post-body-content
    
    // 7. 100% Lead Magnet Conversion Box (WhatsApp + Phone + Office)
    $ctaLabel = ($ctaType === 'CALL') ? 'Call Our Team' : (($ctaType === 'BOOK') ? 'Book Consultation' : 'Apply / Inquire Now');
    $html .= buildLeadMagnetBox($title, $landingUrl, $ctaLabel, $kw);
    
    return $html;
}


