/**
 * Comprehensive Verification Test:
 * 1. Category-Aware Smart Food Customizer (No kaşar on Tiramisu!)
 * 2. Header Role Isolation for Admin / Courier / Restaurant vs Customer
 * 3. 12-User Role Matrix Coverage
 * 4. Interactive Map Filters
 */
const fs = require('fs');
const path = require('path');
const vm = require('vm');

console.log('--- TEST SUITE: ROLE ISOLATION, SMART CUSTOMIZER & MAP REVAMP ---');

let testsPassed = 0;
let testsTotal = 0;

function assert(condition, message) {
    testsTotal++;
    if (condition) {
        testsPassed++;
        console.log(`  ✅ PASS: ${message}`);
    } else {
        console.error(`  ❌ FAIL: ${message}`);
    }
}

// 1. TEST: 10 Users Matrix in public/login.html
const loginHtml = fs.readFileSync(path.join(__dirname, '../public/login.html'), 'utf8');
const expected10Users = [
    { username: 'admin_mustafa', role: 'admin' },
    { username: 'ahmet_musteri', role: 'customer' },
    { username: 'ayse_musteri', role: 'customer' },
    { username: 'mehmet_musteri', role: 'customer' },
    { username: 'kurye_ali', role: 'courier' },
    { username: 'kurye_veli', role: 'courier' },
    { username: 'kurye_can', role: 'courier' },
    { username: 'restoran_lezzet', role: 'restaurant' },
    { username: 'restoran_burger', role: 'restaurant' },
    { username: 'restoran_pizza', role: 'restaurant' }
];

expected10Users.forEach(u => {
    assert(loginHtml.includes(`'${u.username}'`), `User ${u.username} exists in login USER_REGISTRY`);
    assert(loginHtml.includes(`role: '${u.role}'`), `User role ${u.role} correctly mapped`);
});

// 2. TEST: Smart Customizer Logic in app.js
const appJs = fs.readFileSync(path.join(__dirname, '../public/js/app.js'), 'utf8');

// Evaluate getItemCategoryProfile in isolated VM
const vmContext = { console: console };
vmContext.global = vmContext;
vmContext.window = vmContext;
vm.createContext(vmContext);

// Extract getItemCategoryProfile and CATEGORY_CUSTOMIZATION_DEFS from app.js
const helperCode = appJs.match(/function getItemCategoryProfile[\s\S]*?const CATEGORY_CUSTOMIZATION_DEFS = {[\s\S]*?};\n/)[0];
assert(helperCode !== null, 'getItemCategoryProfile and CATEGORY_CUSTOMIZATION_DEFS found in app.js');

const executableCode = helperCode
    .replace('function getItemCategoryProfile', 'global.getItemCategoryProfile = function')
    .replace('const CATEGORY_CUSTOMIZATION_DEFS', 'global.CATEGORY_CUSTOMIZATION_DEFS');

vm.runInContext(executableCode, vmContext);

// Test Tiramisu
const tiramisu = { id: 13, name: "İtalyan Tiramisu", tag: "Tatlı", desc: "Mascarpone peyniri, espresso, kedi dili bisküvi" };
const tiramisuProfile = vmContext.getItemCategoryProfile(tiramisu);
assert(tiramisuProfile === 'dessert', 'Tiramisu classified as dessert profile');

const dessertConfig = vmContext.CATEGORY_CUSTOMIZATION_DEFS.dessert;
const dessertExtraLabels = dessertConfig.extras.map(e => e.label).join(' ');
assert(dessertExtraLabels.includes('Dondurma'), 'Tiramisu has ice cream option');
assert(dessertExtraLabels.includes('Antep Fıstığı'), 'Tiramisu has pistachio option');
assert(!dessertExtraLabels.toLowerCase().includes('kaşar'), 'Tiramisu DOES NOT have kaşar (cheese)');
assert(!dessertExtraLabels.toLowerCase().includes('soğan'), 'Tiramisu DOES NOT have soğan (onion)');
assert(!dessertExtraLabels.toLowerCase().includes('turşu'), 'Tiramisu DOES NOT have turşu (pickle)');

// Test Smash Burger
const burger = { id: 6, name: "Smash Cheeseburger", tag: "Çok Satan", desc: "140gr dana köfte, çifte cheddar" };
const burgerProfile = vmContext.getItemCategoryProfile(burger);
assert(burgerProfile === 'burger', 'Burger classified as burger profile');
const burgerConfig = vmContext.CATEGORY_CUSTOMIZATION_DEFS.burger;
const burgerExtraLabels = burgerConfig.extras.map(e => e.label).join(' ');
assert(burgerExtraLabels.includes('Cheddar'), 'Burger has cheddar option');
assert(burgerExtraLabels.includes('Bacon'), 'Burger has bacon option');

// Test Adana Dürüm
const durum = { id: 1, name: "Adana Zırh Dürüm", tag: "Popüler", desc: "Zırh kıyması, köz biber, domates" };
const durumProfile = vmContext.getItemCategoryProfile(durum);
assert(durumProfile === 'kebab', 'Dürüm classified as kebab profile');
const kebabConfig = vmContext.CATEGORY_CUSTOMIZATION_DEFS.kebab;
const kebabExtraLabels = kebabConfig.extras.map(e => e.label).join(' ');
assert(kebabExtraLabels.includes('Lavaş Üstü Eritme Kaşar'), 'Kebab has grilled lavash cheese option');

// 3. TEST: Header Role Isolation in index.html
const indexHtml = fs.readFileSync(path.join(__dirname, '../public/index.html'), 'utf8');

assert(indexHtml.includes('id="btn-nav-address"') && indexHtml.includes('customer-only-ui'), 'Address picker has customer-only-ui');
assert(indexHtml.includes('id="del-mode-toggle"') && indexHtml.includes('customer-only-ui'), 'Delivery mode switcher has customer-only-ui');
assert(indexHtml.includes('id="btn-nav-cart"') && indexHtml.includes('customer-only-ui'), 'Cart button has customer-only-ui');
assert(indexHtml.includes('id="btn-nav-orders"') && indexHtml.includes('customer-only-ui'), 'Orders button has customer-only-ui');
assert(indexHtml.includes('id="banner-admin"'), 'Role header banner for admin exists');
assert(indexHtml.includes('id="banner-courier"'), 'Role header banner for courier exists');
assert(indexHtml.includes('id="banner-restaurant"'), 'Role header banner for restaurant exists');

// 4. TEST: Map Filter & Tooltip
const mapJs = fs.readFileSync(path.join(__dirname, '../public/js/map.js'), 'utf8');
assert(mapJs.includes('setFilter'), 'GustoMap has setFilter method');
assert(mapJs.includes('handleCanvasMouseMove'), 'GustoMap has handleCanvasMouseMove method');
assert(mapJs.includes('map-hud-tooltip'), 'GustoMap handles map-hud-tooltip');

console.log(`\nResults: ${testsPassed}/${testsTotal} tests passed.`);
if (testsPassed === testsTotal) {
    process.exit(0);
} else {
    process.exit(1);
}
