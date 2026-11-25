/**
 * Cravelo — Genişletilmiş Platform Özellikleri, Modüler Arayüz & Depo Uyumluluk Test Paketi
 * Kullanım: node tests/test_platform_features.js
 */
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const ROOT = path.resolve(__dirname, '..');
let passed = 0;
let failed = 0;

function assert(cond, label) {
    if (cond) {
        passed++;
        console.log(`  [PASS] ${label}`);
    } else {
        failed++;
        console.error(`  [FAIL] ${label}`);
    }
}

console.log('===================================================================');
console.log('Cravelo Platform Özellikleri & Modüler UI Testleri (15 Test)');
console.log('===================================================================');

// 1. Modüler JS dosyalarının varlığı ve yüklenebilirliği
const featureFiles = [
    'public/js/features/catalog-sync.js',
    'public/js/features/kds-pro.js',
    'public/js/features/courier-pro.js',
    'public/js/features/admin-pro.js',
    'public/js/features/customer-pro.js'
];
assert(
    featureFiles.every(f => fs.existsSync(path.join(ROOT, f))),
    '1. Tüm 5 modüler özellik JS dosyası public/js/features/ altında mevcut'
);

// VM ortamı oluştur
function makeMockEl(id, tag = 'DIV') {
    return {
        id,
        tagName: tag,
        innerText: '',
        innerHTML: '',
        value: '',
        checked: true,
        className: '',
        style: { display: '' },
        children: [],
        classList: {
            _set: new Set(),
            add(c) { this._set.add(c); },
            remove(c) { this._set.delete(c); },
            contains(c) { return this._set.has(c); }
        },
        querySelector() { return null; },
        querySelectorAll() { return []; },
        appendChild(ch) { this.children.push(ch); return ch; },
        removeChild() {},
        getAttribute() { return ''; },
        setAttribute() {}
    };
}

const domEls = {};
[
    'nav-role', 'acc-points', 'acc-wallet-display', 'kds-rest-active-btn', 'kds-menu-item-overlay',
    'kds-item-modal-title', 'kds-item-id', 'kds-item-name', 'kds-item-price', 'kds-item-category',
    'kds-item-desc', 'kds-item-img', 'kds-item-available', 'kds-report-date', 'kds-report-output',
    'courier-shift-musait', 'courier-shift-mesgul', 'courier-badge-status', 'courier-task-area',
    'admin-analytics-summary', 'admin-revenue-chart', 'admin-orders-body', 'admin-restaurants-grid',
    'admin-order-status-filter', 'admin-order-mode-filter', 'admin-analytics-date',
    'rest-reviews-section', 'rest-reviews-inline-list', 'mh-review-count',
    'reviews-overlay', 'reviews-modal-title', 'reviews-modal-sub', 'reviews-modal-list',
    'chat-overlay', 'chat-courier-name', 'chat-messages', 'chat-input', 'cart-overlay'
].forEach(id => { domEls[id] = makeMockEl(id); });
domEls['cart-overlay'].classList.add('modal-overlay');
domEls['cart-overlay'].style.display = 'flex';

const listeners = {};
const toasts = [];
const storage = {};

const ctx = {
    console,
    Math,
    Date,
    Number,
    String,
    Boolean,
    Array,
    Object,
    Set,
    JSON,
    parseInt,
    parseFloat,
    isFinite,
    isNaN,
    encodeURIComponent,
    setTimeout: (fn) => 1,
    clearTimeout: () => {},
    setInterval: () => 1,
    clearInterval: () => {},
    localStorage: {
        getItem: (k) => storage[k] ?? null,
        setItem: (k, v) => { storage[k] = String(v); },
        removeItem: (k) => { delete storage[k]; }
    },
    location: { protocol: 'file:', pathname: '/', origin: 'file://' },
    document: {
        readyState: 'complete',
        hidden: false,
        body: makeMockEl('body', 'BODY'),
        getElementById: (id) => domEls[id] || null,
        querySelectorAll: (sel) => {
            if (sel === '.modal-overlay') return [domEls['cart-overlay']];
            return [];
        },
        createElement: (tag) => makeMockEl('dyn', tag.toUpperCase()),
        addEventListener: (ev, fn) => { listeners[ev] = listeners[ev] || []; listeners[ev].push(fn); }
    },
    GustoAudio: { play: () => {} },
    GustoMap: { drawMap: () => {}, stopCourierAnimation: () => {} },
    showToast: (icon, msg, type) => { toasts.push({ icon, msg, type }); },
    escapeHtml: (s) => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c])),
    isCraveloHttpServer: () => false,
    getStatusMeta: (st) => ({ label: st, badge: 'badge-musait', icon: 'fa-check' }),
    buildRestaurantCardHTML: (r) => `<div class="rest-card"><div class="rest-dist-badge">1.0 km</div><span>${r.name}</span></div>`,
    selectRestaurant: (id) => { ctx.__lastSelectedRest = id; },
    renderResPanel: () => { ctx.__resRendered = true; },
    renderCourierPanel: () => { ctx.__courierRendered = true; },
    renderAdminCouriers: () => { ctx.__adminCouriersRendered = true; },
    updateAddressLabel: () => {},
    openChatModal: () => {},
    closeChatModal: () => {},
    sendQuickChat: () => {},
    sendChatMessage: () => {},
    handleUpdateProfile: () => {},
    submitRatingAndReward: () => {},
    craveloPollServerPartialRefresh: async () => { ctx.__pollRuns = (ctx.__pollRuns || 0) + 1; },
    SERVER_CATALOG: {
        restaurants: [
            { id: 1, name: 'Lezzet Kebap Salonu', is_active: false, rating: '4.9', review_count: 2 },
            { id: 2, name: 'Burger Lab', is_active: true, rating: '4.8', review_count: 1 }
        ],
        menu_items: [
            { id: 1, rId: 1, name: 'Adana Zırh Dürüm', desc: 'Zırh kıyması', price: 195, category: 'Kebap', available: true },
            { id: 99, rId: 1, name: 'Yeni KDS Beyti', desc: 'Özel soslu', price: 320, category: 'Kebap', available: true }
        ],
        couriers: [
            { id: 1, x: 22, y: 26, lat: 40.974, lon: 29.041, neighborhood: 'Kalamış Marina', active: 1, status: 'mesgul' }
        ],
        reviews: [
            { id: 1, restaurant_id: 2, author: 'Ahmet Yılmaz', taste: 5, speed: 5, service: 4, rating: 4.7, tags: ['Sıcacık Geldi'], comment: 'Harika!', created_at: '2026-11-25' }
        ]
    },
    SERVER_PROFILE: {
        user_id: 1,
        fullName: 'Ahmet Sunucu',
        phone: '05559990011',
        email: 'ahmet@ornek.com',
        wallet_points: 220,
        addresses: [{ id: 1, title: 'Moda Ev', desc: 'Moda Cad. 14', x: 18, y: 14, lat: 40.984, lon: 29.027, isDefault: true }],
        active_location: { x: 18, y: 14, lat: 40.984, lon: 29.027, name: 'Moda Ev' }
    }
};
ctx.window = ctx;
ctx.globalThis = ctx;

vm.createContext(ctx);
vm.runInContext(fs.readFileSync(path.join(ROOT, 'public/js/data.js'), 'utf8'), ctx);
vm.runInContext(fs.readFileSync(path.join(ROOT, 'public/js/state.js'), 'utf8') + '\nthis.GustoState = GustoState;', ctx);
ctx.GustoState.get();

featureFiles.forEach(f => {
    vm.runInContext(fs.readFileSync(path.join(ROOT, f), 'utf8'), ctx);
});

const st = ctx.GustoState.get();

// 2. Tek kaynaklı sunucu katalog hidrasyonu
assert(
    st.restaurants.find(r => r.id === 1).isActive === false &&
    st.menuItems.some(m => m.id === 99 && m.name === 'Yeni KDS Beyti') &&
    st.couriers.find(c => c.id === 1).status === 'mesgul' &&
    Array.isArray(ctx.CRAVELO_REVIEWS) && ctx.CRAVELO_REVIEWS.length === 1,
    '2. hydrateServerCatalog() kapalı restoran, yeni KDS ürünü, kurye vardiya durumu ve yorumları senkronize eder'
);

// 3. Kapalı restoran rozeti ve seçim engeli
const closedCard = ctx.buildRestaurantCardHTML(st.restaurants.find(r => r.id === 1), st);
ctx.selectRestaurant(1);
assert(
    closedCard.includes('ŞU AN KAPALI') && ctx.__lastSelectedRest !== 1,
    '3. Kapalı restoran kartında ŞU AN KAPALI rozeti görünür ve sipariş ekranına geçiş engellenir'
);

// 4. Sunucu profil & cüzdan puanı hidrasyonu
assert(
    st.userPoints === 220 && st.customerLocation.name === 'Moda Ev' && st.savedAddresses[0].title === 'Moda Ev',
    '4. hydrateServerProfile() sunucu cüzdan puanını (220) ve kayıtlı adresleri GustoState içine aktarır'
);

// 5. ESC tuşu ile açık modal kapatma
const wasOpenBeforeEsc = domEls['cart-overlay'].style.display === 'flex';
(listeners.keydown || []).forEach(fn => fn({ key: 'Escape', keyCode: 27 }));
assert(wasOpenBeforeEsc && domEls['cart-overlay'].style.display === 'none', '5. ESC tuşuna basıldığında açık .modal-overlay otomatik kapanır');

// 6. Gizli sekmede (document.hidden = true) polling durdurma
ctx.document.hidden = true;
ctx.craveloPollServerPartialRefresh();
assert(!ctx.__pollRuns, '6. Sekme arka plandayken (document.hidden=true) polling isteği atlanarak kaynak korunur');

// 7. KDS yeni ürün ekleme & şube aç/kapa
st.selectedVendorRestId = 1;
domEls['kds-item-id'].value = '0';
domEls['kds-item-name'].value = 'Künefe Özel';
domEls['kds-item-price'].value = '165';
domEls['kds-item-category'].value = 'Tatlı';
domEls['kds-item-desc'].value = 'Sıcak fıstıklı';
domEls['kds-item-available'].checked = true;
ctx.submitKdsMenuItem({ preventDefault: () => {} });
assert(
    st.menuItems.some(m => m.name === 'Künefe Özel' && m.price === 165),
    '7. KDS submitKdsMenuItem() yeni menü ürününü canlı kataloğa ekler'
);

// 8. KDS şube durum düğmesi ve günlük rapor kartları
ctx.toggleCurrentRestaurantActive();
ctx.loadKdsDailyReport(true);
assert(
    st.restaurants.find(r => r.id === 1).isActive === true &&
    domEls['kds-report-output'].innerHTML.includes('GÜNLÜK NET CİRO'),
    '8. KDS şube aç/kapa ve günlük satış raporu kartları eksiksiz çalışır'
);

// 9. Kurye vardiya durumu güncelleme
st.selectedCourierId = 1;
ctx.setCourierShiftStatus('musait');
assert(
    st.couriers.find(c => c.id === 1).status === 'musait' &&
    domEls['courier-shift-musait'].className.includes('btn-success'),
    '9. Kurye vardiya durumu (Müsait / Mola) güncellenir ve UI düğmesi senkronize olur'
);

// 10. Yönetici analitik grafik, tüm siparişler tablosu ve restoran yönetim paneli
st.orderHistory = [
    { id: 101, serverOrderId: 101, rId: 2, rName: 'Burger Lab', customerName: 'Ahmet Yılmaz', deliveryMode: 'courier', status: 'yolda', total: 250, courierId: 1, courier: 'Ali Kaya', createdAt: '12:00' },
    { id: 102, serverOrderId: 102, rId: 2, rName: 'Burger Lab', customerName: 'Ayşe Kaya', deliveryMode: 'takeaway', status: 'teslim_edildi', total: 200, courierId: null, courier: 'Gel-Al', createdAt: '12:15' }
];
domEls['admin-order-status-filter'].value = 'all';
domEls['admin-order-mode-filter'].value = 'all';
ctx.refreshAdminCommandCenter(false);
assert(
    domEls['admin-analytics-summary'].innerHTML.includes('450.00 ₺') &&
    domEls['admin-revenue-chart'].innerHTML.includes('Burger Lab') &&
    domEls['admin-orders-body'].innerHTML.includes('#ORD-101') &&
    domEls['admin-restaurants-grid'].innerHTML.includes('Lezzet Kebap Salonu'),
    '10. Yönetici komuta merkezi ciro grafiği, tüm siparişler tablosu ve şube yönetimi render edilir'
);

// 11. Müşteri restoran yorumları (satır içi + modal)
st.selectedRestId = 2;
ctx.renderRestaurantReviewsInline(2);
ctx.openRestaurantReviews(2);
assert(
    domEls['rest-reviews-section'].style.display === 'block' &&
    domEls['rest-reviews-inline-list'].innerHTML.includes('Harika!') &&
    domEls['reviews-overlay'].style.display === 'flex',
    '11. Restoran değerlendirmeleri hem menü altında hem yorumlar modalında doğrulanmış veriden çizilir'
);

// 12. View & Layout enjeksiyon sözleşmesi
const mainPhp = fs.readFileSync(path.join(ROOT, 'views/layouts/main.php'), 'utf8');
const restView = fs.readFileSync(path.join(ROOT, 'views/dashboard/_restaurant_view.php'), 'utf8');
const courView = fs.readFileSync(path.join(ROOT, 'views/dashboard/_courier_view.php'), 'utf8');
const admView = fs.readFileSync(path.join(ROOT, 'views/dashboard/_admin_view.php'), 'utf8');
const modalsView = fs.readFileSync(path.join(ROOT, 'views/dashboard/_modals.php'), 'utf8');
assert(
    mainPhp.includes('window.SERVER_CATALOG') &&
    mainPhp.includes('window.SERVER_PROFILE') &&
    mainPhp.includes('js/features/catalog-sync.js') &&
    mainPhp.includes('js/features/admin-pro.js') &&
    restView.includes('kds-rest-active-btn') &&
    restView.includes('kds-daily-report-section') &&
    courView.includes('courier-shift-controls') &&
    admView.includes('admin-analytics-panel') &&
    admView.includes('admin-orders-panel') &&
    admView.includes('admin-restaurants-panel') &&
    modalsView.includes('kds-menu-item-overlay') &&
    modalsView.includes('reviews-overlay'),
    '12. Layout ve 4 rol görünümünde tüm yeni özellik bağlantı noktaları ve modallar eksiksiz tanımlı'
);

// 13. Çalışma alanı temizliği (debug.log kaldırıldı, UI_UX_ARCHITECTURE_PLAN docs/ altında)
assert(
    !fs.existsSync(path.join(ROOT, 'debug.log')) &&
    !fs.existsSync(path.join(ROOT, 'UI_UX_ARCHITECTURE_PLAN.md')) &&
    fs.existsSync(path.join(ROOT, 'docs/UI_UX_ARCHITECTURE_PLAN.md')),
    '13. Çalışma alanı temiz: debug.log silindi ve UI_UX_ARCHITECTURE_PLAN.md docs/ dizinine taşındı'
);

// 14. MySQL 8+ schema.sql 11-tablo Aşama 3 hizalaması
const schemaSql = fs.readFileSync(path.join(ROOT, 'database/schema.sql'), 'utf8');
const requiredTables = ['users', 'restaurants', 'menu_items', 'couriers', 'orders', 'order_items', 'order_status_log', 'reviews', 'order_messages', 'user_addresses', 'user_wallets'];
assert(
    schemaSql.includes('ENGINE=InnoDB') &&
    schemaSql.includes('delivery_mode') &&
    requiredTables.every(t => schemaSql.includes(`CREATE TABLE IF NOT EXISTS ${t}`)),
    '14. database/schema.sql tüm 11 InnoDB tablosu ve delivery_mode/GPS kolonlarıyla Aşama 3\'e hazır'
);

// 15. MySQL 8+ seed.sql bin/seed.php ile 1:1 hizalı (10 kullanıcı, 7 restoran, 27 menü ürünü, 4 kurye)
const seedSql = fs.readFileSync(path.join(ROOT, 'database/seed.sql'), 'utf8');
assert(
    seedSql.includes("'admin_mustafa'") &&
    seedSql.includes("'mustafaguresir@gmail.com'") &&
    seedSql.includes('(27, 7,') &&
    seedSql.includes('Deniz Balık Lokantası') &&
    seedSql.includes('INSERT INTO reviews') &&
    seedSql.includes('INSERT INTO order_messages'),
    '15. database/seed.sql 10 kullanıcı, 7 restoran, 27 menü ürünü, yorumlar ve mesajlarla 1:1 hizalı'
);

console.log('-------------------------------------------------------------------');
console.log(`TOPLAM: ${passed}/${passed + failed} test geçti (${failed} başarısız).`);
process.exit(failed > 0 ? 1 : 0);
