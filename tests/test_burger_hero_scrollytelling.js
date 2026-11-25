/**
 * Cravelo Burger Hero Scrollytelling — Comprehensive Automated Verification Suite
 * Verifies R1 (Sticky Hero Viewport & GSAP ScrollTrigger),
 * R2 (Photorealistic 7 Burger Layers & Stacking Order),
 * R3 (Atmosphere, Dark Luxury Aesthetic & Turkish UI Overlay),
 * R4 (60fps GPU Performance & Mobile Responsiveness),
 * R5 (Dual-View Parity & Regression Safety).
 *
 * 40+ Atomic Assertions running via standard Node.js CLI (Zero External Dependencies).
 */

const fs = require('fs');
const path = require('path');
const assert = require('assert');
const vm = require('vm');

let totalTests = 0;
let passedTests = 0;

function runTest(description, testFn) {
  totalTests++;
  try {
    testFn();
    passedTests++;
    console.log(`  ✅ PASS: [${totalTests}] ${description}`);
  } catch (err) {
    console.error(`  ❌ FAIL: [${totalTests}] ${description}`);
    console.error(`     ↳ Hata: ${err.message}`);
    process.exitCode = 1;
  }
}

console.log('======================================================================');
console.log('  TEST SUITE: CRAVELO BURGER HERO SCROLLYTELLING (M1 ARCHITECTURE)');
console.log('======================================================================\n');

const ROOT_DIR = path.resolve(__dirname, '..');
const landingPhpPath = path.join(ROOT_DIR, 'views', 'landing.php');
const mainLayoutPath = path.join(ROOT_DIR, 'views', 'layouts', 'main.php');
const indexHtmlPath = path.join(ROOT_DIR, 'public', 'index.html');
const styleCssPath = path.join(ROOT_DIR, 'public', 'css', 'style.css');
const gsapJsPath = path.join(ROOT_DIR, 'public', 'js', 'gsap.min.js');
const scrollTriggerJsPath = path.join(ROOT_DIR, 'public', 'js', 'ScrollTrigger.min.js');
const burgerJsPath = path.join(ROOT_DIR, 'public', 'js', 'burger-scrollytelling.js');
const appJsPath = path.join(ROOT_DIR, 'public', 'js', 'app.js');

const landingPhp = fs.readFileSync(landingPhpPath, 'utf8');
const mainLayout = fs.readFileSync(mainLayoutPath, 'utf8');
const indexHtml = fs.readFileSync(indexHtmlPath, 'utf8');
const styleCss = fs.readFileSync(styleCssPath, 'utf8');

// -------------------------------------------------------------
// 1. STICKY VIEWPORT & CONTAINER GEOMETRY (R1)
// -------------------------------------------------------------
console.log('--- 1. SCROLL CONTAINER & STICKY VIEWPORT GEOMETRY (R1) ---');

runTest('views/landing.php içinde #burger-scrolly-container bulunmalı', () => {
  assert.ok(landingPhp.includes('id="burger-scrolly-container"'), 'landing.php must contain #burger-scrolly-container');
});

runTest('public/index.html içinde #burger-scrolly-container bulunmalı', () => {
  assert.ok(indexHtml.includes('id="burger-scrolly-container"'), 'index.html must contain #burger-scrolly-container');
});

runTest('views/landing.php içinde #burger-sticky-viewport bulunmalı', () => {
  assert.ok(landingPhp.includes('id="burger-sticky-viewport"'), 'landing.php must contain #burger-sticky-viewport');
});

runTest('public/index.html içinde #burger-sticky-viewport bulunmalı', () => {
  assert.ok(indexHtml.includes('id="burger-sticky-viewport"'), 'index.html must contain #burger-sticky-viewport');
});

runTest('public/css/style.css #burger-scrolly-container min-height: 380vh kuralı içermeli', () => {
  assert.ok(
    styleCss.includes('#burger-scrolly-container') && styleCss.includes('380vh'),
    'style.css must define 380vh scroll container height'
  );
});

runTest('public/css/style.css #burger-sticky-viewport position: sticky ve 100vh içermeli', () => {
  assert.ok(
    styleCss.includes('#burger-sticky-viewport') &&
    styleCss.includes('position: sticky') &&
    styleCss.includes('100vh') &&
    styleCss.includes('overflow: hidden'),
    'style.css must define 100vh sticky inner viewport with overflow hidden'
  );
});

runTest('views/landing.php ve public/index.html içinde #burger-stage sahnesi bulunmalı', () => {
  assert.ok(landingPhp.includes('id="burger-stage"'), 'landing.php must contain #burger-stage');
  assert.ok(indexHtml.includes('id="burger-stage"'), 'index.html must contain #burger-stage');
});

// -------------------------------------------------------------
// 2. 7 PHOTOREALISTIC BURGER LAYERS & ASSETS (R2)
// -------------------------------------------------------------
console.log('\n--- 2. 7 PHOTOREALISTIC BURGER LAYERS & ASSET INTEGRITY (R2) ---');

const expectedLayers = [
  { file: 'layer-1-bun-bottom.svg', layer: 1, desc: 'Brioche Alt Taban' },
  { file: 'layer-2-lettuce.svg', layer: 2, desc: 'Çıtır Marul' },
  { file: 'layer-3-patty.svg', layer: 3, desc: 'Dana Antrikot Köfte' },
  { file: 'layer-4-cheese.svg', layer: 4, desc: 'Erimiş Cheddar' },
  { file: 'layer-5-tomatoes.svg', layer: 5, desc: 'Tarla Domatesleri' },
  { file: 'layer-6-onions-pickles.svg', layer: 6, desc: 'Karamelize Soğan & Turşu' },
  { file: 'layer-7-bun-top.svg', layer: 7, desc: 'Susamlı Brioche Üst Taç' },
  { file: 'steam-wisps.svg', layer: null, desc: 'Sıcak Buhar' }
];

expectedLayers.forEach((item) => {
  runTest(`Hero görsel varlığı public/img/hero/${item.file} mevcut ve dolu olmalı`, () => {
    const p = path.join(ROOT_DIR, 'public', 'img', 'hero', item.file);
    assert.ok(fs.existsSync(p), `File ${item.file} must exist`);
    const content = fs.readFileSync(p, 'utf8');
    assert.ok(content.length > 500, `File ${item.file} must not be empty (>500 bytes)`);
    assert.ok(content.includes('<svg') && content.includes('</svg>'), `File ${item.file} must be valid SVG`);
  });
});

for (let i = 1; i <= 7; i++) {
  runTest(`views/landing.php içinde data-layer="${i}" katmanı bulunmalı`, () => {
    assert.ok(landingPhp.includes(`data-layer="${i}"`), `landing.php must contain data-layer="${i}"`);
  });
  runTest(`public/index.html içinde data-layer="${i}" katmanı bulunmalı`, () => {
    assert.ok(indexHtml.includes(`data-layer="${i}"`), `index.html must contain data-layer="${i}"`);
  });
  runTest(`public/css/style.css data-layer="${i}" için açık z-index: ${i} kuralı içermeli`, () => {
    assert.ok(
      styleCss.includes(`data-layer="${i}"]`) && styleCss.includes(`z-index: ${i}`),
      `style.css must specify z-index: ${i} for layer ${i}`
    );
  });
}

runTest('views/landing.php ve public/index.html içinde #burger-steam buhar elementi bulunmalı', () => {
  assert.ok(landingPhp.includes('id="burger-steam"'), 'landing.php must contain #burger-steam');
  assert.ok(indexHtml.includes('id="burger-steam"'), 'index.html must contain #burger-steam');
});

// -------------------------------------------------------------
// 3. GSAP & SCROLLTRIGGER OFFLINE ENGINE (R1 & R5)
// -------------------------------------------------------------
console.log('\n--- 3. GSAP & SCROLLTRIGGER OFFLINE ENGINE (R1 & R5) ---');

runTest('public/js/gsap.min.js mevcut olmalı ve Node.js içinde hatasız yüklenmeli', () => {
  assert.ok(fs.existsSync(gsapJsPath), 'gsap.min.js must exist');
  const gsap = require(gsapJsPath);
  assert.ok(gsap && typeof gsap.timeline === 'function', 'gsap.timeline must be a function');
  assert.ok(typeof gsap.registerPlugin === 'function', 'gsap.registerPlugin must be a function');
});

runTest('public/js/ScrollTrigger.min.js mevcut olmalı ve ScrollTrigger eklentisi export etmeli', () => {
  assert.ok(fs.existsSync(scrollTriggerJsPath), 'ScrollTrigger.min.js must exist');
  const ScrollTrigger = require(scrollTriggerJsPath);
  assert.strictEqual(ScrollTrigger.name, 'ScrollTrigger');
  assert.ok(typeof ScrollTrigger.create === 'function', 'ScrollTrigger.create must be a function');
});

runTest('public/js/burger-scrollytelling.js controller modülü tanımlı ve micro-copy adımlarını export etmeli', () => {
  assert.ok(fs.existsSync(burgerJsPath), 'burger-scrollytelling.js must exist');
  const controller = require(burgerJsPath);
  assert.ok(Array.isArray(controller.MICROCOPY_STEPS), 'MICROCOPY_STEPS must be an array');
  assert.ok(controller.MICROCOPY_STEPS.length >= 8, 'Must have at least 8 micro-copy thresholds');
});

runTest('views/layouts/main.php içinde gsap, ScrollTrigger ve burger-scrollytelling script etiketleri olmalı', () => {
  assert.ok(mainLayout.includes('js/gsap.min.js'), 'main.php must load gsap.min.js');
  assert.ok(mainLayout.includes('js/ScrollTrigger.min.js'), 'main.php must load ScrollTrigger.min.js');
  assert.ok(mainLayout.includes('js/burger-scrollytelling.js'), 'main.php must load burger-scrollytelling.js');
});

runTest('public/index.html içinde gsap, ScrollTrigger ve burger-scrollytelling script etiketleri olmalı', () => {
  assert.ok(indexHtml.includes('js/gsap.min.js'), 'index.html must load gsap.min.js');
  assert.ok(indexHtml.includes('js/ScrollTrigger.min.js'), 'index.html must load ScrollTrigger.min.js');
  assert.ok(indexHtml.includes('js/burger-scrollytelling.js'), 'index.html must load burger-scrollytelling.js');
});

runTest('public/js/app.js içinde global korumasız gsap çağrısı bulunmamalı (Node VM güvenliği)', () => {
  const appJs = fs.readFileSync(appJsPath, 'utf8');
  assert.ok(!appJs.includes('gsap.'), 'app.js must not contain un-guarded gsap calls');
});

// -------------------------------------------------------------
// 4. TIMELINE THRESHOLDS & TURKISH MICRO-COPY (R2 & R3)
// -------------------------------------------------------------
console.log('\n--- 4. TIMELINE THRESHOLDS & TURKISH MICRO-COPY (R2 & R3) ---');

const controller = require(burgerJsPath);

runTest('Threshold 0% (0.00): "Taze Brioche Ekmeği" mikro metni tetiklenmeli', () => {
  const step = controller.getStepForProgress(0.00);
  assert.strictEqual(step.title, 'Taze Brioche Ekmeği');
});

runTest('Threshold 30% (0.30): "%100 Dana Antrikot Köfte" mikro metni tetiklenmeli', () => {
  const step = controller.getStepForProgress(0.30);
  assert.strictEqual(step.title, '%100 Dana Antrikot Köfte');
});

runTest('Threshold 45% (0.45): "Erimiş Cheddar" mikro metni tetiklenmeli', () => {
  const step = controller.getStepForProgress(0.45);
  assert.strictEqual(step.title, 'Erimiş Cheddar');
});

runTest('Threshold 100% (1.00): "Sıcak ve Hızlı Kapında" mikro metni tetiklenmeli', () => {
  const step = controller.getStepForProgress(1.00);
  assert.strictEqual(step.title, 'Sıcak ve Hızlı Kapında');
});

runTest('views/landing.php içinde #burger-microcopy-badge ve başlık alanları yer almalı', () => {
  assert.ok(landingPhp.includes('id="burger-microcopy-badge"'), 'landing.php must contain #burger-microcopy-badge');
  assert.ok(landingPhp.includes('id="burger-badge-title"'), 'landing.php must contain #burger-badge-title');
  assert.ok(landingPhp.includes('id="burger-badge-subtitle"'), 'landing.php must contain #burger-badge-subtitle');
});

runTest('public/index.html içinde #burger-microcopy-badge ve başlık alanları yer almalı', () => {
  assert.ok(indexHtml.includes('id="burger-microcopy-badge"'), 'index.html must contain #burger-microcopy-badge');
  assert.ok(indexHtml.includes('id="burger-badge-title"'), 'index.html must contain #burger-badge-title');
  assert.ok(indexHtml.includes('id="burger-badge-subtitle"'), 'index.html must contain #burger-badge-subtitle');
});

// -------------------------------------------------------------
// 5. ATMOSPHERE, DARK LUXURY STYLING & GPU PERFORMANCE (R3 & R4)
// -------------------------------------------------------------
console.log('\n--- 5. ATMOSPHERE, DARK LUXURY STYLING & GPU PERFORMANCE (R3 & R4) ---');

runTest('public/css/style.css dark luxury fast-food arka plan degrade renklerini (#121212 & #231710) içermeli', () => {
  assert.ok(styleCss.includes('#121212') && styleCss.includes('#231710'), 'style.css must include #121212 to #231710 gradient');
});

runTest('public/css/style.css sıcak radyal sahne spot ışığını (rgba(255, 107, 53, 0.22)) içermeli', () => {
  assert.ok(styleCss.includes('rgba(255, 107, 53, 0.22)'), 'style.css must contain rgba(255, 107, 53, 0.22) spotlight');
});

runTest('public/css/style.css .burger-layer GPU donanım hızlandırması kuralları içermeli', () => {
  assert.ok(
    styleCss.includes('will-change: transform, opacity') &&
    styleCss.includes('backface-visibility: hidden') &&
    styleCss.includes('translate3d'),
    'style.css must enable GPU accelerated rendering for .burger-layer'
  );
});

runTest('public/css/style.css mobil responsive (@media max-width: 768px) 3-zone mimarisi içermeli', () => {
  assert.ok(styleCss.includes('@media (max-width: 768px)'), 'style.css must contain mobile media query');
  assert.ok(styleCss.includes('scale(0.68)'), 'style.css must scale burger stage on mobile (0.68)');
});

// -------------------------------------------------------------
// 6. TURKISH UI OVERLAY & PARITY (R3 & R5)
// -------------------------------------------------------------
console.log('\n--- 6. TURKISH UI OVERLAY & DUAL-VIEW PARITY (R3 & R5) ---');

runTest('Navbar içinde "Cravelo" logo, "Menü", "Kampanyalar", "Kurye Ol" ve "Giriş Yap / Sipariş Ver" yer almalı', () => {
  assert.ok(landingPhp.includes('Cravelo'), 'landing.php brand check');
  assert.ok(landingPhp.includes('Menü'), 'landing.php Menü link check');
  assert.ok(landingPhp.includes('Kampanyalar'), 'landing.php Kampanyalar link check');
  assert.ok(landingPhp.includes('Kurye Ol'), 'landing.php Kurye Ol link check');
  assert.ok(landingPhp.includes('Giriş Yap / Sipariş Ver'), 'landing.php unified CTA check');

  assert.ok(indexHtml.includes('Menü'), 'index.html Menü link check');
  assert.ok(indexHtml.includes('Kampanyalar'), 'index.html Kampanyalar link check');
  assert.ok(indexHtml.includes('Kurye Ol'), 'index.html Kurye Ol link check');
  assert.ok(indexHtml.includes('Giriş Yap / Sipariş Ver'), 'index.html unified CTA check');
});

runTest('Final durum (100% scroll): "Hemen Sipariş Ver" CTA butonu (#FF5722) bulunmalı', () => {
  assert.ok(landingPhp.includes('id="btn-hero-order"'), 'landing.php must contain #btn-hero-order');
  assert.ok(landingPhp.includes('Hemen Sipariş Ver'), 'landing.php must contain text "Hemen Sipariş Ver"');
  assert.ok(indexHtml.includes('id="btn-hero-order"'), 'index.html must contain #btn-hero-order');
  assert.ok(indexHtml.includes('Hemen Sipariş Ver'), 'index.html must contain text "Hemen Sipariş Ver"');
  assert.ok(styleCss.includes('#FF5722') || styleCss.includes('#ff5722'), 'style.css must style CTA with #FF5722');
});

runTest('Final durum (100% scroll): Adres / Restoran arama çubuğu bulunmalı', () => {
  assert.ok(landingPhp.includes('id="hero-address-search"'), 'landing.php must contain #hero-address-search');
  assert.ok(landingPhp.includes('id="hero-address-input"'), 'landing.php must contain #hero-address-input');
  assert.ok(indexHtml.includes('id="hero-address-search"'), 'index.html must contain #hero-address-search');
  assert.ok(indexHtml.includes('id="hero-address-input"'), 'index.html must contain #hero-address-input');
});

// -------------------------------------------------------------
// 7. REGRESSION SAFETY & PRESERVED SHOWCASE SECTIONS (R5)
// -------------------------------------------------------------
console.log('\n--- 7. REGRESSION SAFETY & PRESERVED SHOWCASES (R5) ---');

runTest('views/landing.php 4 rol vitrinini ve demo hesaplar kılavuzunu korumalı', () => {
  assert.ok(landingPhp.includes('4 Ayrı Rol'), 'landing.php must retain "4 Ayrı Rol"');
  assert.ok(landingPhp.includes('Müşteri Paneli'), 'landing.php must retain "Müşteri Paneli"');
  assert.ok(landingPhp.includes('Restoran / KDS'), 'landing.php must retain "Restoran / KDS"');
  assert.ok(landingPhp.includes('Kurye Kokpiti'), 'landing.php must retain "Kurye Kokpiti"');
  assert.ok(landingPhp.includes('Yönetici Merkezi'), 'landing.php must retain "Yönetici Merkezi"');
  assert.ok(landingPhp.includes('admin_mustafa'), 'landing.php must retain "admin_mustafa"');
  assert.ok(landingPhp.includes('ahmet_musteri'), 'landing.php must retain "ahmet_musteri"');
});

runTest('public/index.html mevcut rol panellerini ve navigasyon kimliklerini korumalı', () => {
  const requiredIds = [
    'view-landing', 'nav-auth-actions', 'nav-user-actions', 'btn-nav-address',
    'del-mode-toggle', 'btn-nav-cart', 'btn-nav-orders', 'banner-admin',
    'banner-courier', 'banner-restaurant', 'view-customer', 'view-restaurant',
    'view-courier', 'view-admin'
  ];
  requiredIds.forEach((id) => {
    assert.ok(indexHtml.includes(`id="${id}"`), `index.html must retain id="${id}"`);
  });
});

// -------------------------------------------------------------
// SUMMARY
// -------------------------------------------------------------
console.log('\n======================================================================');
console.log(`TEST SONUCU: ${passedTests} Başarılı, ${totalTests - passedTests} Başarısız (Toplam: ${totalTests} Test)`);
console.log('======================================================================');

if (passedTests === totalTests) {
  process.exit(0);
} else {
  process.exit(1);
}
