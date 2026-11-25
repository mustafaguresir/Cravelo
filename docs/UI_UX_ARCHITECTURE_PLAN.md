# Cravelo Food Delivery & Courier Dispatch System: UI/UX Architecture Plan & Competitive Analysis

**Version:** 2.0.0-PROD  
**Author:** Cravelo Design Systems & Frontend Engineering Team  
**Date:** 2026-11-23  
**Status:** Approved for Implementation  
**Target Invariant:** 100% Green on all 281+ Automated Tests across Node.js, PHP, and Python.

---

## 1. Executive Summary & Problem Formulation

Cravelo is a hybrid enterprise food delivery and multi-role courier dispatch platform designed for high-density metropolitan ecosystems, anchored specifically in the historic and vibrant district of Kadıköy, Istanbul. Cravelo features a dual-presentation architecture combining:
1. A reactive Single Page Application (SPA) engine (`public/index.html`, `public/login.html`, `public/js/app.js`), and
2. A robust PHP MVC server-rendered application (`views/landing.php`, `views/dashboard/`, `views/layouts/`).

While the underlying domain model, flock file-locking persistence engine, sequential order state machine, and Haversine geospatial dispatch calculations are robust and fully verified (281/281 passing tests), the legacy user interface exhibited several critical UX debt items:
- **Visual Inconsistency & Lighting Mismatch:** The desktop navigation bar rendered an obsidian dark background even when the application was in Light Mode, violating modern aesthetic expectations.
- **Geographic Abstraction Leak:** The user interface exposed raw simulation grid coordinates such as `(18, 14)` and `(12, 8)` directly in restaurant headers (`#mh-loc`), discovery cards, and courier mission cards, rather than displaying real-world Kadıköy streets (Moda Caddesi, Bahariye Caddesi, Güneşli Bahçe Sokak).
- **Navigation & Component Fragmentation:** Category filtering, story discovery rings, sticky cart calculation, and kitchen KDS tickets required unified elevation tokens, standardized border radii (20px modern cards), and smooth micro-interactions.

This document establishes the competitive analysis, design system specifications, component architecture, Kadıköy geocoding engine, and view-by-view implementation blueprints required to deliver a world-class consumer foodtech experience matching DoorDash, Uber Eats, Lieferando, and Glovo.

---

## 2. Competitive UX Analysis: Global FoodTech Leaders vs. Cravelo

To engineer a best-in-class product, we benchmarked the design paradigms, micro-interactions, layout structures, and information architecture of four global delivery platforms:

```
┌─────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                   COMPETITIVE UX BENCHMARK MATRIX                               │
├────────────────────┬─────────────────┬───────────────────┬──────────────────┬───────────────────┤
│ Dimension          │ DoorDash        │ Uber Eats         │ Lieferando (JET) │ Glovo             │
├────────────────────┼─────────────────┼───────────────────┼──────────────────┼───────────────────┤
│ Primary Color      │ Crimson #FF3008 │ Black & Green     │ Safety Orange    │ Canary Yellow     │
│                    │                 │ #06C167           │ #FF8000          │ #FFC244 / Emerald │
├────────────────────┼─────────────────┼───────────────────┼──────────────────┼───────────────────┤
│ Typography         │ TT Norms Pro    │ Uber Move Text    │ Open Sans        │ Montserrat        │
│ Scale & Tracking   │ Geometric sans  │ Tight tracking    │ Ultra high       │ Rounded geometric │
│                    │ -0.02em         │ -0.04em headings  │ legibility       │ friendly tone     │
├────────────────────┼─────────────────┼───────────────────┼──────────────────┼───────────────────┤
│ Top Navigation Bar │ Translucent     │ Monochromatic     │ High-contrast    │ Translucent glass │
│                    │ Frosted glass   │ frosted glass     │ branded header   │ with location     │
│                    │ 24px blur       │ theme-adaptive    │ with clear tabs  │ selector pill     │
├────────────────────┼─────────────────┼───────────────────┼──────────────────┼───────────────────┤
│ Category & Story   │ Horizontal pill │ Segmented pills   │ Horizontal       │ Circular "Stories"│
│ Discovery System   │ list with icons │ with active fills │ carousel tags    │ rings + gradient  │
│                    │                 │                   │                  │ borders & pills   │
├────────────────────┼─────────────────┼───────────────────┼──────────────────┼───────────────────┤
│ Card Geometry &    │ 16-20px radius  │ 20-24px radius    │ 16px radius      │ 20px bubbly       │
│ Elevation Shadows  │ Edge-to-edge    │ Clean hairline    │ Card shadow with │ cards with bold   │
│                    │ photography     │ subtle shadows    │ orange badge     │ floating pills    │
├────────────────────┼─────────────────┼───────────────────┼──────────────────┼───────────────────┤
│ Filter & Sort      │ Sticky chip bar │ Segmented pills   │ Quick filters    │ Floating pill bar │
│ Controls           │ Speed, Rating   │ Speed, Offers     │ Rating 4.5+, Free│ with icon badges  │
├────────────────────┼─────────────────┼───────────────────┼──────────────────┼───────────────────┤
│ Cart Experience    │ Sticky sidebar  │ Floating bottom   │ Right rail with  │ Sticky sidebar    │
│                    │ + bottom drawer │ drawer on mobile  │ live subtotal    │ with order meter  │
├────────────────────┼─────────────────┼───────────────────┼──────────────────┼───────────────────┤
│ Geolocation & Map  │ Street pinpoint │ Real Google Map   │ Address field    │ Interactive pin   │
│ Presentation       │ drop-off notes  │ street labels     │ with house no    │ with local street │
└────────────────────┴─────────────────┴───────────────────┴──────────────────┴───────────────────┘
```

### 2.1 DoorDash UX Patterns Adopted
- **Translucent Floating Header:** DoorDash relies on a frosted glass navigation header with high backdrop blur (`20px`) and subtle hairline borders (`1px solid rgba(0,0,0,0.06)` in light mode, `1px solid rgba(255,255,255,0.08)` in dark mode). We adopt this exact standard, eliminating any jarring dark navbar on light themes.
- **Segmented Delivery Switcher:** The delivery mode toggle (`Adrese Teslimat` vs. `Gel-Al %20 İndirim`) utilizes a pill container with seamless active tab transitions, clear status indicators, and micro-animations.

### 2.2 Uber Eats UX Patterns Adopted
- **Edge-to-Edge Visual Food Cards:** Modern cards (`.food-card`, `.rest-card`, `.flash-card`) feature `20px` border-radii, high-contrast typography, and floating top badges (discount pills on top-left, favorite toggle heart on top-right with glassmorphic backdrop).
- **Subtle Elevation & Hover Lift:** Cards sit on a soft multi-layered diffuse shadow (`0 8px 30px -4px rgba(0,0,0,0.08)` in light mode) and gently lift (`transform: translateY(-3px)`) upon hover with a cubic-bezier easing curve.

### 2.3 Lieferando (Just Eat Takeaway) UX Patterns Adopted
- **High-Utility Right Rail Cart:** Desktop users benefit from a permanent sticky cart rail (`.cart-sticky`) that recalculates subtotals, takeaway discounts, delivery fees, and final sums in real time, featuring an eco-friendly cutlery toggle and high-contrast checkout CTA button.
- **Clear Kitchen Ticket Layout:** The Restaurant KDS borrows Lieferando's ticket-based workflow with distinct status badges (Alındı, Hazırlanıyor, Hazır), itemized counts, and one-click action buttons.

### 2.4 Glovo UX Patterns Adopted
- **Instagram-Style Circular Story Rings:** Above the main restaurant catalog, circular story rings (`.stories-bar .story-ring`) invite customers to discover trending categories (Burger, Kebap, Pizza, Sushi, Tatlı, Tavuk, Balık) surrounded by a warm gradient border (`linear-gradient(135deg, #FA4A0C, #FF7140, #FFC244)`).
- **Interactive Map Pinning:** Real-world street addresses with neighborhood landmark context replaces abstract coordinate math for everyday consumers.

---

## 3. Cravelo Design System Specifications (Tokens & Invariants)

### 3.1 Color Palette & Semantic Tokens
Cravelo's signature visual identity is anchored in warm culinary flame tones balanced by sleek obsidian dark surfaces and crisp porcelain light surfaces.

```css
/* Core Brand Colors */
--primary: #FA4A0C;             /* Signature Cravelo Warm Flame */
--primary-hover: #E03E05;       /* Pressed / Darkened Primary */
--primary-2: #FF7140;           /* Radiant Coral Accent */
--primary-warm: #FF5722;        /* Deep Ember Orange */
--primary-glow: rgba(250, 74, 12, 0.35);
--primary-light: rgba(250, 74, 12, 0.12);

/* Light Theme Surface Tokens (data-theme="light") */
--bg: #F8FAFC;                  /* Crisp Porcelain Slate Background */
--bg-subtle: #F1F5F9;           /* Secondary Background */
--surface: #FFFFFF;             /* Pure White Surface Cards */
--surface-2: #F8FAFC;           /* Elevated Surface / Input Fields */
--surface-3: #E2E8F0;           /* Dividers and Subtle Borders */
--surface-glass: rgba(255, 255, 255, 0.88); /* Translucent Top Bar & Modals */
--text: #0F172A;                /* Deep Charcoal Text (Slate-900) */
--text-2: #475569;              /* Secondary Descriptive Text (Slate-600) */
--text-3: #94A3B8;              /* Muted Meta Text (Slate-400) */
--border: #E2E8F0;              /* Hairline Card Border */
--border-2: #CBD5E1;            /* Input & Strong Border */
--border-glass: rgba(0, 0, 0, 0.06);

/* Dark Theme Surface Tokens (data-theme="dark") */
--bg: #0B0F19;                  /* Deep Obsidian Slate */
--bg-subtle: #0F172A;           /* Midnight Background */
--surface: #111827;             /* Dark Gray Card Surface */
--surface-2: #1F2937;           /* Elevated Dark Card */
--surface-3: #374151;           /* Component Borders */
--surface-glass: rgba(11, 15, 25, 0.82); /* Translucent Top Bar & Modals */
--text: #F8FAFC;                /* Bright White Text */
--text-2: #94A3B8;              /* Soft Slate Text */
--text-3: #64748B;              /* Dim Meta Text */
--border: #1F2937;              /* Subtle Card Border */
--border-2: #374151;            /* Input Border */
--border-glass: rgba(255, 255, 255, 0.08);

/* Status Tokens */
--success: #0BBF80;             /* Fresh Mint Emerald */
--danger: #EF4444;              /* Coral Red */
--blue: #2F8EFF;                /* Electric Cyan/Blue */
--yellow: #F59E0B;              /* Warm Amber */
--purple: #9B5CF6;              /* Vivid Violet */
```

### 3.2 Typography: Plus Jakarta Sans
The system standardizes on **Plus Jakarta Sans** across all headers, cards, badges, and controls:
- **Headings (H1-H3):** Font-weight 800/700, letter-spacing `-0.03em`, line-height 1.2.
- **Card Titles:** Font-weight 700, font-size `15px`, letter-spacing `-0.015em`.
- **Body & Meta:** Font-weight 500/400, font-size `13.5px`, line-height 1.5.
- **Badges & Microcopy:** Font-weight 700/800, font-size `11px`, text-transform uppercase, tracking `+0.04em`.

### 3.3 Geometry, Micro-Radii & Shadows
- **Card Border Radius:** Exactly `20px` (`border-radius: 20px;`) for `.rest-card`, `.food-card`, `.flash-card`, `.cart-box`.
- **Button Border Radius:** Full pill `9999px` for category chips, sort pills, and action badges; `14px` for square action buttons.
- **Modal Border Radius:** `28px` for modal dialogs with smooth top curves on mobile (`32px 32px 0 0`).
- **Diffuse Shadows:**
  - Light mode: `box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.03);`
  - Dark mode: `box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.40), 0 8px 10px -6px rgba(0, 0, 0, 0.30);`

---

## 4. Map Integration & Real Kadıköy Street Naming Architecture (R3)

### 4.1 Geographic Bounding Box & Coordinate Grid
The simulated dispatch coordinates `(x, y)` map onto the real-world Kadıköy metropolitan grid bounded by:
- **Latitude:** `40.9700 N` (South, Kalamış Marina & Fenerbahçe) to `41.0000 N` (North, Rasimpaşa & Ayrılık Çeşmesi)
- **Longitude:** `29.0180 E` (West, Kadıköy Rıhtım / Ferry Docks) to `29.0550 E` (East, Söğütlüçeşme & Göztepe)

```
        41.0000° N ┌──────────────────────────────────────────────┐
                   │  Rasimpaşa / Yeldeğirmeni (Karakolhane Cad)  │
                   │  (X: 5-14, Y: 0-7)                           │
                   ├───────────────────────┬──────────────────────┤
                   │  Kadıköy Çarşı/Rıhtım │ Altıyol / Boğa Heyk. │
                   │  (Güneşli Bahçe Sk)   │ (Söğütlüçeşme Cad)   │
                   │  (X: 3-10, Y: 6-12)   │ (X: 14-22, Y: 7-14)  │
                   ├───────────────────────┴──────────────────────┤
                   │  Caferağa / Bahariye (Bahariye Cad - Süreyya)│
                   │  (X: 11-19, Y: 8-16)                         │
                   ├──────────────────────────────────────────────┤
                   │  Moda (Moda Caddesi - Tramvay / Sahil Park)  │
                   │  (X: 8-18, Y: 12-22)                         │
                   ├──────────────────────────────────────────────┤
                   │  Fenerbahçe & Kalamış Marina                 │
                   │  (Fener Kalamış Caddesi / Münir Nurettin)    │
        40.9700° N └──────────────────────────────────────────────┘
                   29.0180° E                          29.0550° E
```

### 4.2 Enhanced Reverse-Geocoding Engine (`public/js/map.js`)
We expand `getNeighborhoodName(lat, lon)` and add `getKadıkoyStreetAddress(lat, lon)` to return authentic street names and door numbers:
- Coordinates `< 40.9780`: **Fener Kalamış Caddesi No:42, Kalamış Marina, Kadıköy**
- Coordinates `> 40.9940`: **Karakolhane Caddesi No:34, Yeldeğirmeni, Kadıköy**
- Coordinates `lon < 29.0260`: **Güneşli Bahçe Sokak No:16 (Tarihi Çarşı), Caferağa, Kadıköy**
- Coordinates `lon > 29.0380`: **Söğütlüçeşme Caddesi No:52 (Boğa Heykeli), Altıyol, Kadıköy**
- Coordinates `lat < 40.9850`: **Moda Caddesi No:14/2, Caferağa Mah., Kadıköy**
- Central coordinates: **Bahariye Caddesi No:38 (Süreyya Operası Yanı), Kadıköy**

### 4.3 Elimination of Raw Coordinates from User-Facing UI
1. **Menu Header Location Badge (`#mh-loc`)**:
   - Legacy: `document.getElementById('mh-loc').innerText = `(${r.x}, ${r.y})`;`
   - Overhaul: `document.getElementById('mh-loc').innerText = r.neighborhood || GustoMap.getNeighborhoodName(r.lat, r.lon);`
2. **Restaurant Discovery Cards in Grid**:
   - Legacy: `<div style="margin-left:auto;color:var(--text-3);">(${r.x}, ${r.y})</div>`
   - Overhaul: `<div class="rest-loc-pill"><i class="fa-solid fa-location-dot"></i> ${r.neighborhood || 'Moda, Kadıköy'}</div>`
3. **Courier Assigned Task Card (`#courier-task-area`)**:
   - Legacy: `(${r.x}, ${r.y})` and `(${state.customerLocation.x}, ${state.customerLocation.y})`
   - Overhaul: Displays restaurant name with its Kadıköy street address and customer name with their saved address title and street description.
4. **Admin Fleet Overview Table (`#admin-couriers-body`)**:
   - Legacy: `<td>(${c.x}, ${c.y})</td>`
   - Overhaul: Displays a formatted neighborhood badge with an authentic street name and micro-badge.
5. **DOM Contract Preservation**:
   - Inputs `<input id="picker-x">` and `<input id="picker-y">` remain present in `#address-overlay` with `display: none;` container styling, ensuring tests verifying their programmatic synchronization continue passing with 100% fidelity.

---

## 5. View-by-View Implementation Blueprint

### 5.1 Top Navigation Bar (`views/dashboard/_header.php` & `public/index.html`)
- **Container (`.topnav`, `.landing-navbar`):**
  - Light mode: `background: rgba(255, 255, 255, 0.88); backdrop-filter: blur(20px); border-bottom: 1px solid rgba(0,0,0,0.06); box-shadow: 0 4px 20px -2px rgba(0,0,0,0.03);`
  - Dark mode: `background: rgba(11, 15, 25, 0.82); backdrop-filter: blur(20px); border-bottom: 1px solid rgba(255,255,255,0.08); box-shadow: 0 4px 20px -2px rgba(0,0,0,0.25);`
- **Address Selector Pill (`#btn-nav-address`):**
  - Must retain `.customer-only-ui` class.
  - Houses `#nav-addr-label` displaying real Kadıköy address with a pin icon and chevron.
- **Delivery Mode Segmented Pill (`#del-mode-toggle`):**
  - Must retain `.customer-only-ui` class.
  - Sub-buttons `#btn-del-courier` (Kurye ile Teslim) and `#btn-del-takeaway` (Gel-Al %20 İndirim).
- **Search Pill (`#global-search`):**
  - Must retain `.customer-only-ui` class and `oninput="globalSearch()"`.
- **Cart & Orders Badges (`#btn-nav-cart`, `#btn-nav-orders`):**
  - Must retain `.customer-only-ui` class, housing `#nav-cart-count` and `#nav-orders-badge`.
- **Role Isolation Banners:**
  - Preserved verbatim: `#banner-admin`, `#banner-courier`, `#banner-restaurant`, `#admin-return-bar`.

### 5.2 Landing View (`views/landing.php` & `public/index.html #view-landing`)
- **Preserved Hero Scrollytelling Architecture:**
  - Container `#burger-scrolly-container` with `min-height: 380vh`.
  - Viewport `#burger-sticky-viewport` with `position: sticky; top: 0; height: 100vh; overflow: hidden;`.
  - Progress bar `#burger-scroll-progress-bar`.
  - Spotlight `#burger-spotlight` with `rgba(255, 107, 53, 0.22)`.
  - Microcopy badges `#burger-microcopy-badge`, `#burger-badge-title`, `#burger-badge-subtitle`.
  - Burger stage `#burger-stage` with 7 SVG layers (`[data-layer="1"]` through `[data-layer="7"]`), each with explicit `z-index: 1..7` and GPU acceleration properties (`will-change: transform, opacity; backface-visibility: hidden; translate3d;`).
  - Steam wisps layer `#burger-steam`.
  - CTA Button `#btn-hero-order` with text "Hemen Sipariş Ver" and background `#FF5722`.
  - Address search container `#hero-address-search` with input field `#hero-address-input`.
- **Four Role Showcase Vitrini:**
  - Preserved sections for "4 Ayrı Rol", "Müşteri Paneli", "Restoran / KDS", "Kurye Kokpiti", "Yönetici Merkezi", and demo credentials guides.

### 5.3 Customer Discovery Portal (`views/dashboard/_customer_view.php` & `public/index.html #view-customer`)
- **Story Rings (`#stories-bar .story-ring`):**
  - Instagram-style circular items with category badges (`Burger`, `Kebap`, `Pizza`, `Sushi`, `Tatlı`, `Tavuk`, `Balık`).
  - Gradient borders, subtle pulse on hover, and category-filtering triggers.
- **Hero Promo Banner:**
  - Eye-catching banner showcasing Daily Flash Deals, delivery time guarantees, and Kadıköy fast dispatch promises.
- **Flash Deals & Popular Showcase:**
  - Sections `#flash-deals-section` with countdown `#flash-timer` and `#flash-grid`.
  - Section `#popular-showcase-section` with `#pop-food-grid`.
- **Category Filter Pills & Segmented Sort Bar:**
  - `#cat-pills` hosting modern pill chips.
  - Filter chips `#sort-fast`, `#sort-rating`, `#sort-promo`, `#sort-delivery` with `.sort-chip`.
- **Modern Restaurant Grid (`#rest-grid`):**
  - Cards `.rest-card` with 20px radii, high-definition cuisine photography, real Kadıköy neighborhood badges, star ratings, and delivery estimates.
- **Food Catalog & Menu Section (`#menu-section`):**
  - Header with `#mh-img`, `#mh-name`, `#mh-rating`, `#mh-time`, and `#mh-loc` (displaying authentic Kadıköy street address).
  - Food items grid `#food-grid` with `.food-card` items, customizer triggers, and price pills.
- **Sticky Cart Summary & Floating Bottom Bar:**
  - Desktop sticky sidebar: `#cart-items`, `#cart-mode-indicator`, `#btn-group-cart`, `#cart-eco-area`, `#cart-coupon-area`, pricing breakdown `#s-sub`, `#s-disc`, `#s-del`, `#s-total`, and checkout trigger `#btn-order`.
  - Mobile floating bottom bar: `#floating-cart-bar`, `#float-cart-count`, `#float-cart-text`, `#float-cart-total`.
- **Live Order Stepper & Thermal Tracker:**
  - Retains `#order-number-badge`, `#thermal-box`, `#thermal-temp`, `#addon-deal-card`, and `#customer-map-canvas`.

### 5.4 Restaurant KDS Cockpit (`views/dashboard/_restaurant_view.php` & `public/index.html #view-restaurant`)
- **Top Controls:** Selector `#res-selector` with live status indicator pill and refresh trigger.
- **KPI Metrics:** Counter cards `#kpi-orders`, `#kpi-revenue`, `#kpi-res-rating`.
- **Kitchen Order Queue (`#res-orders-body`):** High-contrast order ticket cards with elapsed time badges, order items checklist, delivery mode indicator, and progressive status transition buttons (Hazırla ➔ Hazır).
- **Menu Stock Control (`#res-menu-body`):** Real-time toggle switches to mark dishes in-stock or out-of-stock.

### 5.5 Courier Cockpit (`views/dashboard/_courier_view.php` & `public/index.html #view-courier`)
- **Courier Profile Header:** Selector `#courier-selector`, status badge `#courier-badge-status`.
- **KPI Metrics:** Real-time location badge `#courier-kpi-loc` (displaying Kadıköy neighborhood), earnings card `#courier-kpi-earnings`.
- **Live Mission Card (`#courier-task-area`):** Active delivery assignment featuring pickup restaurant with street name, delivery customer with street address, order item list, and step-by-step progress buttons.
- **Live Kadıköy Route Map:** Container `#courier-map-leaflet` and fallback `#courier-map-canvas` showing courier marker, pickup marker, destination marker, and route polyline.
- **Telemetry Coordinates:** Preserved inputs `#courier-input-x` and `#courier-input-y`.

### 5.6 Admin Control Center (`views/dashboard/_admin_view.php` & `public/index.html #view-admin`)
- **Fleet Map Controls:** Filter buttons `#map-btn-all`, `#map-btn-couriers`, `#map-btn-restaurants` in `#map-filter-group`.
- **Live City Map:** Container `#admin-map-leaflet`, canvas `#admin-map-canvas`, and interactive hover HUD `#map-hud-tooltip`.
- **Fleet Overview Table (`#admin-couriers-body`):** Courier name, vehicle type, active order, real Kadıköy location badge, battery level, and Manhattan dispatch score.
- **Security Audit Logs (`#admin-audit-body`):** Real-time audit events table.

### 5.7 Authentication Views (`public/login.html`, `views/auth/login.php`, `register.php`, `views/layouts/auth.php`)
- **Light/Dark Consistency:** Add theme switching or clean adaptive styling so authentication views align with main dashboard tokens.
- **Quick Demo Login Matrix:** 10 pre-configured pill buttons for 1-click login testing (Müşteri, Restoran, Kurye, Yönetici).
- **Form Contract Preservation:** Preserve all input IDs (`#inp-username`, `#inp-password`, `#reg-fullname`, `#reg-username`, `#reg-email`, `#reg-phone`, `#reg-password`, `#reg-password-confirm`).

### 5.8 Interactive Modals Suite (`views/dashboard/_modals.php` & `public/index.html`)
All 9 modal overlays polished with luxury backdrop blur, 28px rounded corners, and smooth transitions:
1. `#cart-overlay`: Shopping cart with `#modal-cart-badge`, item quantity adjusters, eco-friendly toggle, and checkout button.
2. `#customizer-overlay`: Smart food customizer with `#cust-dynamic-options`, ingredient toggles, quantity stepper `#cust-qty`, and dynamic submit button `#btn-cust-text`.
3. `#checkout-overlay`: Payment selector (`#pay-card-cc`, `#pay-card-cod`, `#pay-card-wallet`), delivery tip pills (`#tip-0`, `#tip-10`, `#tip-20`, `#tip-50`), scheduled delivery pills (`#sched-pills`), instruction chips (`#instruction-chips`), and checkout total `#checkout-final-total`.
4. `#rating-overlay`: Star rating containers (`#taste-stars`, `#speed-stars`, `#service-stars`), feedback tags, and courier reward button.
5. `#group-order-overlay`: Group order code `#group-order-code`, friend invite input `#group-friend-name`, and members list `#group-members-list`.
6. `#chat-overlay`: Live courier chat with `#chat-courier-name`, `#chat-messages`, and `#chat-input`.
7. `#address-overlay`: Interactive Kadıköy map picker (`#address-picker-leaflet`, `#address-picker-canvas`), reverse geocoded display `#picker-coord-text`, saved addresses list `#saved-address-list`, and hidden contract inputs `#picker-x`, `#picker-y`.
8. `#account-overlay`: User profile details, loyalty points `#acc-points`, wallet balance `#acc-wallet-display`, and order history tabs.
9. `#receipt-overlay`: Final thermal printed order receipt summary `#receipt-body`.

---

## 6. Implementation Sequence & Quality Gates

```
Phase 1: Architecture & Design Tokens
  ├── [x] Baseline test verification (281/281 passed)
  ├── [x] Architectural Plan document (UI_UX_ARCHITECTURE_PLAN.md)
  └── [ ] Update public/css/style.css (translucent top bar, 20px cards, shadows, map pin css)

Phase 2: Geolocation & Real Kadıköy Street Engine
  ├── [ ] Update public/js/map.js (enhanced getNeighborhoodName & getKadıkoyStreetAddress)
  ├── [ ] Update public/js/data.js (authentic Kadıköy restaurant & courier locations)
  └── [ ] Update public/js/app.js (display street names in #mh-loc, rest-card, courier task, admin table)

Phase 3: View Overhaul & Dual-View Parity
  ├── [ ] Update public/index.html (SPA parity, light/dark topbar, 20px cards, Kadıköy streets)
  ├── [ ] Update views/dashboard/_header.php (translucent topbar, customer-only-ui)
  ├── [ ] Update views/dashboard/_customer_view.php (stories, hero, pills, cards, sticky cart)
  ├── [ ] Update views/dashboard/_restaurant_view.php (modern KDS tickets & stock toggles)
  ├── [ ] Update views/dashboard/_courier_view.php (modern courier cockpit & task card)
  ├── [ ] Update views/dashboard/_admin_view.php (modern admin control center)
  ├── [ ] Update views/dashboard/_modals.php (luxury 9 modals)
  ├── [ ] Update public/login.html & views/auth/ (clean modern auth with light/dark consistency)
  └── [ ] Update views/landing.php (modern landing with burger scrollytelling preserved)

Phase 4: Comprehensive Verification & Audit
  ├── [ ] Run all 10 test runners (Python, Node, PHP) -> Target 281+ green (0 failures)
  ├── [ ] Verify zero visual regressions on light/dark mode
  └── [ ] Produce final handoff report
```

---

## 7. Verification Invariant Checklist

| Verification Gate | Required Assertion | Verification Command |
|---|---|---|
| Python M1 Architecture | 31/31 Pass | `python tests/test_architectural_flows.py` |
| Python Core Specifications | 20/20 Pass | `python tests/run_all_cmd_tests.py` |
| Python Storage & Domain Integrity | 8/8 Pass | `python tests/verify_system.py` |
| Node.js Address & Restaurant Sync | 31/31 Pass | `node tests/test_address_and_restaurant.js` |
| Node.js Role Isolation & Customizer | 42/42 Pass | `node tests/test_comprehensive_improvements.js` |
| Node.js Auth Form & Input Validation | 24/24 Pass | `node tests/test_login_and_registration.js` |
| Node.js Landing Page & Auth Guards | 35/35 Pass | `node tests/test_landing_and_auth_gate.js` |
| Node.js Burger Hero Scrollytelling | 58/58 Pass | `node tests/test_burger_hero_scrollytelling.js` |
| PHP Core Domain & Security Rules | 20/20 Pass | `.\php\php.exe tests/run_tests.php` |
| PHP Architectural Flows & Helpers | 12/12 Pass | `.\php\php.exe tests/test_architectural_flows.php` |
| **All Test Runners Aggregate** | **281+ Pass (0 Failures)** | **`.\TESTLERI_CALISTIR.bat`** |
