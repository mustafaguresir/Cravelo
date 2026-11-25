/**
 * Test Suite: Address Coordinates Sync, Address Deletion, Modal Close Buttons, 
 * 12 Users Matrix (Slide 3) & Restaurant KDS View
 */

const fs = require('fs');
const path = require('path');
const assert = require('assert');

console.log('--- TEST SUITE: ADDRESS SYNC, ADDRESS DELETION, 12 USERS & RESTAURANT KDS ---');

let passCount = 0;
function test(desc, fn) {
    try {
        fn();
        console.log(`  ✅ PASS: ${desc}`);
        passCount++;
    } catch (err) {
        console.error(`  ❌ FAIL: ${desc}`);
        console.error(`     Reason: ${err.message}`);
        process.exitCode = 1;
    }
}

// 1. users.json ve Slayt 3 Kullanıcı Doğrulaması
const usersJsonPath = path.join(__dirname, '../storage/users.json');
const usersData = JSON.parse(fs.readFileSync(usersJsonPath, 'utf8'));

test('storage/users.json tam olarak 10 kullanıcı içermeli', () => {
    assert.strictEqual(usersData.length, 10);
});

const SLIDE_3_USERS = [
    { id: 1, username: 'ahmet_musteri', email: 'ahmet@ornek.com', full_name: 'Ahmet Yılmaz', role: 'customer' },
    { id: 2, username: 'ayse_musteri', email: 'ayse@ornek.com', full_name: 'Ayşe Kaya', role: 'customer' },
    { id: 3, username: 'mehmet_musteri', email: 'mehmet@ornek.com', full_name: 'Mehmet Demir', role: 'customer' },
    { id: 4, username: 'restoran_lezzet', email: 'lezzet@ornek.com', full_name: 'Lezzet Kebap', role: 'restaurant' },
    { id: 5, username: 'restoran_burger', email: 'burger@ornek.com', full_name: 'Burger Lab', role: 'restaurant' },
    { id: 6, username: 'restoran_pizza', email: 'pizza@ornek.com', full_name: 'Pizza Roma', role: 'restaurant' },
    { id: 7, username: 'kurye_ali', email: 'ali.kurye@ornek.com', full_name: 'Ali Kaya', role: 'courier' },
    { id: 8, username: 'kurye_veli', email: 'veli.kurye@ornek.com', full_name: 'Veli Demir', role: 'courier' },
    { id: 9, username: 'kurye_can', email: 'can.kurye@ornek.com', full_name: 'Can Öz', role: 'courier' },
    { id: 10, username: 'admin_mustafa', email: 'mustafaguresir@gmail.com', full_name: 'Mustafa Güreşir', role: 'admin' }
];

SLIDE_3_USERS.forEach(expected => {
    test(`Slayt 3 Kullanıcı ${expected.id}: ${expected.full_name} (${expected.username}) users.json içinde doğrulanmalı`, () => {
        const found = usersData.find(u => u.id === expected.id);
        assert.ok(found, `Kullanıcı ID ${expected.id} bulunamadı`);
        assert.strictEqual(found.username, expected.username);
        assert.strictEqual(found.email, expected.email);
        assert.strictEqual(found.full_name, expected.full_name);
        assert.strictEqual(found.role, expected.role);
    });
});

// 2. login.html ve USER_REGISTRY Senkronizasyonu
const loginHtmlPath = path.join(__dirname, '../public/login.html');
const loginHtml = fs.readFileSync(loginHtmlPath, 'utf8');

SLIDE_3_USERS.forEach(expected => {
    test(`login.html USER_REGISTRY '${expected.username}' ve '${expected.email}' tanımlı olmalı`, () => {
        assert.ok(loginHtml.includes(`'${expected.username}':`), `USER_REGISTRY '${expected.username}' içermiyor`);
        assert.ok(loginHtml.includes(`'${expected.email}':`), `USER_REGISTRY '${expected.email}' içermiyor`);
    });
});

// 3. style.css .modal-close z-index ve .modal-header-with-close
const cssPath = path.join(__dirname, '../public/css/style.css');
const cssContent = fs.readFileSync(cssPath, 'utf8');

test('.modal-close z-index: 60 !important içermeli', () => {
    assert.ok(cssContent.includes('z-index: 60 !important;'));
});

test('.modal-header-with-close padding-right: 48px içermeli', () => {
    assert.ok(cssContent.includes('.modal-header-with-close'));
    assert.ok(cssContent.includes('padding-right: 48px !important;'));
});

test('.btn-outline-danger tanımlı olmalı', () => {
    assert.ok(cssContent.includes('.btn-outline-danger'));
});

// 4. index.html Modal Kapatma Butonları ve Admin View Switcher
const indexHtmlPath = path.join(__dirname, '../public/index.html');
const indexHtml = fs.readFileSync(indexHtmlPath, 'utf8');

test('Sepet modalında modal-cart-badge başlık yanında yer almalı', () => {
    assert.ok(indexHtml.includes('Sepetim</h2>'));
    assert.ok(indexHtml.includes('id="modal-cart-badge"'));
    assert.ok(indexHtml.includes('modal-header-with-close'));
});

test('Yönetici Denetim Merkezinde Hızlı Görünüm Switcher butonları yer almalı', () => {
    assert.ok(indexHtml.includes('adminPreviewView(\'admin\')'));
    assert.ok(indexHtml.includes('adminPreviewView(\'restaurant\')'));
    assert.ok(indexHtml.includes('adminPreviewView(\'courier\')'));
    assert.ok(indexHtml.includes('adminPreviewView(\'customer\')'));
    assert.ok(indexHtml.includes('id="admin-return-bar"'));
});

// 5. app.js Adres Koordinat Senkronu ve Adres Silme Mantığı
const appJsPath = path.join(__dirname, '../public/js/app.js');
const appJs = fs.readFileSync(appJsPath, 'utf8');

test('app.js deleteSavedAddress fonksiyonu tanımlı olmalı', () => {
    assert.ok(appJs.includes('function deleteSavedAddress('));
    assert.ok(appJs.includes('savedAddresses.length <= 1'));
});

test('app.js selectSavedAddress harita koordinat inputlarını eşitlemeli', () => {
    assert.ok(appJs.includes('document.getElementById(\'picker-x\')'));
    assert.ok(appJs.includes('document.getElementById(\'picker-y\')'));
    assert.ok(appJs.includes('GustoMap.drawAddressPicker()'));
});

test('app.js openAddressModal customerLocation koordinatlarını picker inputlarına vermeli', () => {
    assert.ok(appJs.includes('function openAddressModal()'));
    assert.ok(appJs.includes('GustoState.get().customerLocation'));
});

test('app.js restoran oturumlarında selectedVendorRestId otomatik atanmalı', () => {
    assert.ok(appJs.includes('restoran_burger'));
    assert.ok(appJs.includes('restoran_pizza'));
    assert.ok(appJs.includes('selectedVendorRestId = 2'));
    assert.ok(appJs.includes('selectedVendorRestId = 3'));
});

test('app.js adminPreviewView fonksiyonu tanımlı olmalı', () => {
    assert.ok(appJs.includes('function adminPreviewView('));
});

console.log(`\nResults: ${passCount} tests executed successfully.`);
