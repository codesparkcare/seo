# LocalRank Pro - Automated Local & Web SEO Command Center

**LocalRank Pro** is an automated Local SEO and Website SEO management software designed to help businesses dominate the **Google Map Top 3 Pack** and rank on **Page 1 of Google Search**.

Installed and running directly inside your XAMPP environment at:
👉 **[http://localhost/SEO](http://localhost/SEO)**

---

## 🚀 Key Features

### 1. Google Maps Top 3 Engine (Geo-Grid Heatmap)
* **Visual Geo-Grid Map:** Scans and displays your office's local rank (#1, #2, #3, etc.) across 9 to 25 neighborhood nodes in your city.
* **Proximity & Distance Decay Modeling:** Visualizes where your business dominates and where competitors are outranking you.
* **1-Click Geo-Grid Scanner:** Re-scans any targeted local keyword across adjustable radii (2 km, 4 km, 8 km).

### 2. Review Auto-AI Hub
* **Instant Google Maps Review Link & Live QR Code:** Printable standee QR code and 1-click WhatsApp/SMS review request links for walk-in clients.
* **AI Keyword Review Replies:** Automatically responds to incoming reviews with polite, professional replies that naturally insert your targeted local service keywords and city name (a direct Google ranking signal).
* **Automated Auto-Responder:** Background cron automatically replies to 4★ and 5★ reviews without manual intervention.

### 3. Website SEO & Schema Engine (Google Page 1)
* **Live Website Crawler & Technical Auditor:** Performs real-time audits on Title tags, Meta descriptions, H1/H2 hierarchy, Image ALT attributes, Canonical URLs, and mobile viewport tags, providing an instant 0–100 SEO health score with actionable fixes.
* **Structured Data Schema (JSON-LD) Generator:** Generates valid, Google-compliant `LocalBusiness`, `ProfessionalService`, `MedicalBusiness`, or `LegalService` schema with coordinates, opening hours, and NAP markup.
* **Programmatic Local Landing Pages Generator:** Automatically creates localized SEO page blueprints (e.g., `[Service] in [City/Neighborhood]`) complete with meta tags, localized content outlines, and FAQ schemas.

### 4. Omnichannel Social & Citation Syndicator
* **Multi-Platform Composer:** Publish or schedule updates with geotags and CTA buttons (Book Now, Call Now, Learn More) across Google Business Profile, Facebook, LinkedIn, and X.
* **NAP Citation Consistency Monitor:** Checks your Name, Address, and Phone accuracy across top directories (Google Business Profile, Apple Maps, Bing Places, Facebook, Yelp, YellowPages).

---

## 🛠️ Technology Stack
* **Server:** Apache (XAMPP macOS)
* **Backend:** PHP 8.2 (Native cURL, DOMDocument, JSON)
* **Database:** SQLite 3 (Zero setup, high-performance file store in `data/localrank.sqlite`)
* **Frontend:** Vanilla CSS with Cyber Dark Glassmorphism, Leaflet.js interactive maps, FontAwesome icons, responsive UI.

---

## ⚙️ Background Automation (Cron)
To run auto-replies to reviews and publish scheduled posts 24/7, add this line to your crontab:
```bash
* * * * * php /Applications/XAMPP/xamppfiles/htdocs/SEO/cron.php >/dev/null 2>&1
```
Or simply click the **"Run Automations Now"** button in the top navigation bar.
