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
    
    $html = "\n\n<!-- Codespark High-Converting Lead Generation Box -->\n";
    $html .= "<div class='wp-lead-magnet-card' style='background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border: 2px solid #3b82f6; border-radius: 14px; padding: 24px 22px; margin: 32px 0 24px 0; color: #f8fafc; box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.3);'>\n";
    $html .= "  <h3 style='margin: 0 0 10px 0; color: #60a5fa; font-size: 1.35rem; font-weight: 700;'>🚀 Ready to Elevate Your Business with Codespark?</h3>\n";
    $html .= "  <p style='margin: 0 0 16px 0; color: #cbd5e1; font-size: 0.98rem; line-height: 1.6;'>Contact Tirunelveli's premier IT solutions & software engineering agency. Get a <strong>Free Strategy Consultation & Live Demo</strong> for your project today!</p>\n";
    $html .= "  <div style='display: flex; gap: 12px; flex-wrap: wrap; align-items: center; margin-bottom: 16px;'>\n";
    $html .= "    <a href='{$waUrl}' target='_blank' rel='noopener' style='display: inline-flex; align-items: center; gap: 8px; background: #25d366; color: #ffffff; font-weight: 700; padding: 11px 20px; border-radius: 8px; text-decoration: none; font-size: 0.95rem; box-shadow: 0 4px 12px rgba(37, 211, 102, 0.3);'>💬 WhatsApp Us Now</a>\n";
    $html .= "    <a href='{$telUrl}' style='display: inline-flex; align-items: center; gap: 8px; background: #2563eb; color: #ffffff; font-weight: 700; padding: 11px 20px; border-radius: 8px; text-decoration: none; font-size: 0.95rem;'>📞 Call +91 81108 99000</a>\n";
    if (!empty($landingUrl)) {
        $html .= "    <a href='{$landingUrl}' target='_blank' rel='noopener' style='display: inline-flex; align-items: center; gap: 8px; background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.3); color: #ffffff; font-weight: 600; padding: 10px 18px; border-radius: 8px; text-decoration: none; font-size: 0.95rem;'>👉 {$ctaLabel}</a>\n";
    }
    $html .= "  </div>\n";
    $html .= "  <div style='border-top: 1px solid rgba(255,255,255,0.12); padding-top: 12px; font-size: 0.84rem; color: #94a3b8;'>\n";
    $html .= "    📍 <strong>Visit Our Office:</strong> P.No.7A, Housing Board Colony, D.no.46/24, Melapalayam, Tirunelveli - 627005 | <a href='https://maps.google.com/?cid=12008434771239848135' target='_blank' rel='noopener' style='color: #38bdf8; text-decoration: underline;'>Get Directions on Google Maps</a>\n";
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


