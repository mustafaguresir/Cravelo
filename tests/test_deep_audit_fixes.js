/**
 * Cravelo — Derin Denetim (CRV-01 .. CRV-16) Runtime & Veri Bütünlüğü Regresyon Test Süiti
 */
const fs = require('fs');
const path = require('path');
const vm = require('vm');
const assert = require('assert');

console.log('--- TEST SUITE: CRAVELO DEEP AUDIT REGRESSION (CRV-01 .. CRV-16) ---');

let passed = 0;
let failed = 0;
function test(name, fn) {
    try {
        fn();
        passed++;
        console.log(`  ✅ PASS: ${name}`);
    } catch (err) {
        failed++;
        console.error(`  ❌ FAIL: ${name}`);
        console.error(`     Reason: ${err.message}`);
        process.exitCode = 1;
    }
}

const rootDir = path.resolve(__dirname, '..');
const dataJs = fs.readFileSync(path.join(rootDir, 'public/js/data.js'), 'utf8');
const stateJs = fs.readFileSync(path.join(rootDir, 'public/js/state.js'), 'utf8');
const appJs = fs.readFileSync(path.join(rootDir, 'public/js/app.js'), 'utf8');
const indexPhp = fs.readFileSync(path.join(rootDir, 'public/index.php'), 'utf8');
const indexHtml = fs.readFileSync(path.join(rootDir, 'public/index.html'), 'utf8');
const mainLayout = fs.readFileSync(path.join(rootDir, 'views/layouts/main.php'), 'utf8');
const authLayout = fs.readFileSync(path.join(rootDir, 'views/layouts/auth.php'), 'utf8');

const storageRestaurants = JSON.parse(fs.readFileSync(path.join(rootDir, 'storage/restaurants.json'), 'utf8'));
const storageMenuItems = JSON.parse(fs.readFileSync(path.join(rootDir, 'storage/menu_items.json'), 'utf8'));
const storageCouriers = JSON.parse(fs.readFileSync(path.join(rootDir, 'storage/couriers.json'), 'utf8'));
const storageOrders = JSON.parse(fs.readFileSync(path.join(rootDir, 'storage/orders.json'), 'utf8'));

// Create isolated VM context for frontend runtime tests
function createFrontendContext() {
    const store = {};
    const domMap = {};
    const makeEl = (id) => ({
        id,
        innerHTML: '',
        innerText: '',
        value: '',
        disabled: false,
        title: '',
        className: '',
        style: {},
        checked: false,
        classList: {
            add() {},
            remove() {},
            toggle() {},
            contains() { return false; }
        },
        appendChild() {},
        remove() {},
        getAttribute() { return ''; },
        setAttribute() {},
        querySelector() { return null; },
        querySelectorAll() { return []; }
    });

    const ctx = {
        console,
        Date,
        Math,
        JSON,
        Number,
        String,
        Boolean,
        Array,
        Object,
        Set,
        Map,
        Promise,
        setTimeout: () => 1,
        clearTimeout: () => {},
        setInterval: () => 1,
        clearInterval: () => {},
        localStorage: {
            getItem: (k) => (k in store ? store[k] : null),
            setItem: (k, v) => { store[k] = String(v); },
            removeItem: (k) => { delete store[k]; },
            clear: () => { Object.keys(store).forEach(k => delete store[k]); }
        },
        sessionStorage: { clear() {} },
        document: {
            documentElement: {
                _attrs: { 'data-theme': 'dark' },
                getAttribute(k) { return this._attrs[k] || ''; },
                setAttribute(k, v) { this._attrs[k] = String(v); }
            },
            body: { appendChild() {}, contains() { return true; } },
            getElementById(id) {
                if (!domMap[id]) domMap[id] = makeEl(id);
                return domMap[id];
            },
            querySelector() { return null; },
            querySelectorAll() { return []; },
            createElement(tag) { return makeEl(tag); },
            addEventListener() {}
        },
        GustoMap: {
            drawMap() {},
            startCourierAnimation() {},
            stopCourierAnimation() {},
            getStreetLabelForCoords: (_x, _y, _lat, _lon, fb) => fb || 'Moda, Kadıköy'
        },
        GustoAudio: { play() {}, toggleMute() {}, updateSoundIcon() {} }
    };
    ctx.window = {
        location: { protocol: 'file:', pathname: '/index.html', href: '' },
        BASE_PATH: '',
        CSRF_TOKEN: 'test_csrf_token_123',
        ACTIVE_SESSION: {},
        SERVER_ORDERS: [],
        OWNED_RESTAURANT_IDS: []
    };
    vm.createContext(ctx);
    vm.runInContext(dataJs + '\n' + stateJs + '\nthis.GustoState = GustoState;\n' + appJs, ctx);
    ctx._store = store;
    ctx._dom = domMap;
    return ctx;
}

const ctx = createFrontendContext();

test('CRV-03: storage/restaurants.json (7) ve menu_items.json (27) data.js ile 1:1 hizalıdır', () => {
    const frontRests = vm.runInContext('DEFAULT_RESTAURANTS', ctx);
    const frontItems = vm.runInContext('DEFAULT_MENU_ITEMS', ctx);
    assert.strictEqual(storageRestaurants.length, frontRests.length);
    assert.strictEqual(storageMenuItems.length, frontItems.length);
    frontRests.forEach((fr, idx) => {
        const sr = storageRestaurants[idx];
        assert.strictEqual(sr.id, fr.id);
        assert.strictEqual(sr.name, fr.name);
    });
    frontItems.forEach((fi, idx) => {
        const si = storageMenuItems[idx];
        assert.strictEqual(si.id, fi.id);
        assert.strictEqual(si.restaurant_id, fi.rId);
        assert.strictEqual(si.name, fi.name);
        assert.strictEqual(Number(si.price), Number(fi.price));
    });
});

test('CRV-06: storage/couriers.json çift user_id içermez ve active_orders_count orders.json ile tutarlıdır', () => {
    const nonZeroUserIds = storageCouriers.map(c => c.user_id).filter(id => id > 0);
    assert.strictEqual(new Set(nonZeroUserIds).size, nonZeroUserIds.length, 'Çift courier user_id bulundu');
    const activeByCourier = {};
    storageOrders.forEach(o => {
        if (!['teslim_edildi', 'iptal'].includes(o.status) && o.courier_id) {
            activeByCourier[o.courier_id] = (activeByCourier[o.courier_id] || 0) + 1;
        }
    });
    storageCouriers.forEach(c => {
        assert.strictEqual(c.active_orders_count, activeByCourier[c.id] || 0, `Courier #${c.id} active_orders_count uyumsuz`);
    });
});

test('CRV-06: storage/orders.json içindeki tüm sipariş toplamları kalem toplamlarıyla tutarlıdır', () => {
    storageOrders.forEach(o => {
        const sum = o.items.reduce((acc, it) => acc + Number(it.unit_price) * Number(it.quantity), 0);
        assert.strictEqual(Number(o.total_amount), sum, `Order #${o.id} total_amount kalem toplamıyla eşleşmiyor`);
    });
});

test('CRV-01: public/index.php GET /logout rotasını kapattı, layouts CSRF token enjekte ediyor', () => {
    assert.ok(!indexPhp.includes("$router->get('/logout'"), 'GET /logout kaldırılmış olmalı');
    assert.ok(indexPhp.includes("$router->post('/logout'"), 'POST /logout aktif olmalı');
    assert.ok(mainLayout.includes('window.CSRF_TOKEN') && mainLayout.includes('meta name="csrf-token"'));
});

test('CRV-03: getEffectiveItemPrice rId eşleşmesi yapar ve reorderItems eşleşmeyen üründe itemId: null atar', () => {
    const fakeItem = { id: 1, rId: 7, price: 380 };
    assert.strictEqual(ctx.getEffectiveItemPrice(fakeItem), 380, 'Restoran #7 ürünü Restoran #1 Flash Deal (149) ile çakışmamalı');
    const realFlash = { id: 1, rId: 1, price: 190 };
    assert.strictEqual(ctx.getEffectiveItemPrice(realFlash), 149);

    const st = ctx.GustoState.get();
    st.activeOrder = null;
    st.orderHistory = [{
        id: 888,
        rId: 7,
        status: 'teslim_edildi',
        items: [{ name: 'Özel Eski Ürün', qty: 1, price: 215 }]
    }];
    ctx.reorderItems(888);
    assert.strictEqual(st.cart.length, 1);
    assert.strictEqual(st.cart[0].itemId, null);
    assert.strictEqual(st.cart[0].price, 215);
});

test('CRV-03: Çoklu restoran sahipliğinde KDS #res-selector yalnızca sahip olunan şubelere izin verir', () => {
    const st = ctx.GustoState.get();
    ctx.window.ACTIVE_SESSION = { id: 4, username: 'restoran_lezzet', role: 'restaurant', fullName: 'Lezzet Kebap', ownedRestaurantIds: [1, 4, 7] };
    st.selectedVendorRestId = 1;
    ctx.applyRoleIsolation('restaurant');
    const rSel = ctx.document.getElementById('res-selector');
    assert.strictEqual(rSel.disabled, false, '3 şubesi olan restoran seçicisi açık olmalı');
    assert.ok(rSel.innerHTML.includes('Sushico Express') && rSel.innerHTML.includes('Deniz Balık Lokantası'));
    assert.ok(!rSel.innerHTML.includes('Burger Lab'));

    // Sahip olduğu Restoran #4'e geçişe izin verilir
    rSel.value = '4';
    ctx.changeVendorRestaurant();
    assert.strictEqual(st.selectedVendorRestId, 4);

    // Sahip olmadığı Restoran #2'ye geçiş reddedilir
    rSel.value = '2';
    ctx.changeVendorRestaurant();
    assert.strictEqual(st.selectedVendorRestId, 4);
});

test('CRV-08: Kurye kokpiti 2+ aktif görevi aynı anda listeler ve gerçek müşteri koordinatını kullanır', () => {
    const st = ctx.GustoState.get();
    st.selectedCourierId = 1;
    st.activeOrder = {
        id: 701, rId: 1, courierId: 1, status: 'yolda', deliveryMode: 'courier',
        customerName: 'Ahmet Yılmaz', deliveryLat: 40.9860, deliveryLon: 29.0290, deliveryX: 18, deliveryY: 14,
        items: [{ name: 'Adana Zırh Dürüm', qty: 1, price: 190 }], total: 190
    };
    st.orderHistory = [{
        id: 702, rId: 3, courierId: 1, status: 'hazir', deliveryMode: 'courier',
        customerName: 'Ayşe Kaya', deliveryLat: 40.9750, deliveryLon: 29.0350, deliveryX: 22, deliveryY: 20,
        items: [{ name: 'Pepperoni Pizza', qty: 1, price: 280 }], total: 280
    }];
    ctx.renderCourierPanel();
    const html = ctx.document.getElementById('courier-task-area').innerHTML;
    assert.ok(html.includes('#ORD-701') && html.includes('#ORD-702'), 'Her iki aktif görev de kurye panelinde görünmeli');
    assert.ok(html.includes("courierChangeStatus('teslim_edildi', 701)"));
    assert.ok(html.includes("courierChangeStatus('yolda', 702)"));
    assert.strictEqual(ctx.document.getElementById('courier-badge-status').innerText, 'GÖREVDE (2)');
});

test('CRV-11: Kullanıcı değişiminde tüm kişisel state sıfırlanır ve 0 Puan doğru gösterilir', () => {
    const st = ctx.GustoState.get();
    st.ownerUsername = 'ahmet_musteri';
    st.userPoints = 0;
    st.activeCoupon = 'CRAVELO30';
    st.auditLogs = [{ orderId: 1 }];
    ctx.window.ACTIVE_SESSION = { id: 1, username: 'ahmet_musteri', role: 'customer', fullName: 'Ahmet Yılmaz' };
    ctx.applyRoleIsolation('customer');
    assert.ok(ctx.document.getElementById('nav-role').innerHTML.includes('0 Puan'), '0 Puan 145 Puan olarak gösterilmemeli');

    // Farklı kullanıcıya geçiş (ayse_musteri)
    ctx.window.ACTIVE_SESSION = { id: 2, username: 'ayse_musteri', role: 'customer', fullName: 'Ayşe Kaya' };
    ctx.hydrateOrdersForActiveUser([], 'customer');
    assert.strictEqual(st.ownerUsername, 'ayse_musteri');
    assert.strictEqual(st.userPoints, 145);
    assert.strictEqual(st.activeCoupon, null);
    assert.strictEqual(st.auditLogs.length, 0);
});

test('CRV-13 & CRV-14: selectedInstruction Türkçe etikettir, applyRoadsideAddon grossTotal günceller ve çift eklemeyi engeller', () => {
    const fresh = createFrontendContext();
    const st = fresh.GustoState.get();
    assert.ok(st.selectedInstruction.includes('Zile Basma'), 'Ham baby anahtarı yerine Türkçe etiket kullanılmalı');
    st.activeOrder = { id: 901, status: 'yolda', total: 200, grossTotal: 220, items: [] };
    const ok1 = fresh.GustoState.applyRoadsideAddon({ id: 101, rId: 5, restName: 'Tatlı Dünyası', name: 'San Sebastian', price: 140 });
    const ok2 = fresh.GustoState.applyRoadsideAddon({ id: 102, rId: 4, restName: 'Sushico', name: 'Rulo', price: 165 });
    assert.strictEqual(ok1, true);
    assert.strictEqual(ok2, false, 'İkinci yol üstü eklemesi engellenmeli');
    assert.strictEqual(st.activeOrder.total, 340);
    assert.strictEqual(st.activeOrder.grossTotal, 360);
});

test('CRV-14 & CRV-15: public/index.html checkout bölümleri ve cravelo-theme anahtarı tüm görünümlerde senkrondur', () => {
    assert.ok(indexHtml.includes('id="checkout-tip-section"') && indexHtml.includes('id="checkout-courier-inst-section"'));
    assert.ok(indexHtml.includes('login.html?demo=kurye_ali'));
    assert.ok(mainLayout.includes("localStorage.getItem('cravelo-theme')"));
    assert.ok(authLayout.includes("localStorage.setItem('cravelo-theme', next)"));
    assert.ok(ctx.window.CraveloState === ctx.GustoState, 'Dahili global takma ad korunmalı');
});

console.log(`\nResults: ${passed}/${passed + failed} tests passed.`);
process.exit(failed === 0 ? 0 : 1);
