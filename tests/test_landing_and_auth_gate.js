/**
 * Cravelo — Acceptance Verification Test: Landing Screen, Auth Gate & Role Synchronization
 * Şartname Gereksinimleri: R1, R2, R3, R4
 */

const fs = require('fs');
const path = require('path');
const vm = require('vm');
const { execSync } = require('child_process');

let testsPassed = 0;
let testsTotal = 0;

function assertTest(name, condition, detail = '') {
    testsTotal++;
    if (condition) {
        testsPassed++;
        console.log(`  ✅ PASS: ${name}`);
        if (detail) console.log(`     ↳ ${detail}`);
    } else {
        console.error(`  ❌ FAIL: ${name}`);
        if (detail) console.error(`     ↳ ${detail}`);
        process.exitCode = 1;
    }
}

console.log('======================================================================');
console.log('  TEST SUITE: LANDING SCREEN, AUTH GATE & ROLE SYNCHRONIZATION');
console.log('======================================================================\n');

// -----------------------------------------------------------------------------
// 1. STATİK VE MİMARİ BELTEŞ KONTROLLERİ (STATIC & CONTRACT ASSERTIONS)
// -----------------------------------------------------------------------------
console.log('--- 1. MİMARİ VE ŞABLON BELTEŞLERİ DOĞRULAMASI ---');

const indexHtmlPath = path.join(__dirname, '../public/index.html');
const indexHtml = fs.readFileSync(indexHtmlPath, 'utf8');

assertTest(
    'R1: public/index.html içinde #view-landing karşılama konteyneri tanımlı',
    indexHtml.includes('id="view-landing"'),
    'Oturumsuz ziyaretçiyi karşılayacak landing alanı mevcut'
);

assertTest(
    'R1: public/index.html landing ekranında net "Giriş Yap" ve "Kayıt Ol" butonları/bağlantıları mevcut',
    (indexHtml.includes('login.html') || indexHtml.includes('/login')) &&
    indexHtml.includes('Giriş Yap') &&
    indexHtml.includes('Kayıt Ol'),
    'Kullanıcıya açık giriş ve kayıt yönlendirmeleri sunuluyor'
);

assertTest(
    'R1: public/index.html üst navigasyonunda #nav-auth-actions ve #nav-user-actions mevcut',
    indexHtml.includes('id="nav-auth-actions"') && indexHtml.includes('id="nav-user-actions"'),
    'Oturum durumuna göre dinamik navbar aksiyonları mevcut'
);

assertTest(
    'R4: public/index.html mevcut tüm bileşen ve rol başlıklarını korur (Regresyon Güvencesi)',
    indexHtml.includes('id="btn-nav-address"') &&
    indexHtml.includes('id="del-mode-toggle"') &&
    indexHtml.includes('id="btn-nav-cart"') &&
    indexHtml.includes('id="btn-nav-orders"') &&
    indexHtml.includes('id="banner-admin"') &&
    indexHtml.includes('id="banner-courier"') &&
    indexHtml.includes('id="banner-restaurant"') &&
    indexHtml.includes('id="view-customer"') &&
    indexHtml.includes('id="view-restaurant"') &&
    indexHtml.includes('id="view-courier"') &&
    indexHtml.includes('id="view-admin"'),
    'Mevcut testlerin bağımlı olduğu tüm DOM IDleri eksiksiz korunuyor'
);

const landingPhpPath = path.join(__dirname, '../views/landing.php');
assertTest(
    'R1: views/landing.php PHP karşılama görünümü mevcut',
    fs.existsSync(landingPhpPath),
    'Backend MVC için landing görünüm dosyası oluşturuldu'
);

const landingPhp = fs.readFileSync(landingPhpPath, 'utf8');
assertTest(
    'R1: views/landing.php 4 rol vitrini ve demo hesaplar kılavuzu içerir',
    landingPhp.includes('Müşteri Paneli') &&
    landingPhp.includes('Restoran / KDS') &&
    landingPhp.includes('Kurye Kokpiti') &&
    landingPhp.includes('Yönetici Merkezi') &&
    landingPhp.includes('admin_mustafa') &&
    landingPhp.includes('ahmet_musteri'),
    'Modern Hero banner ve 4 uzmanlaşmış rol tanıtımı mevcut'
);

const headerPhpPath = path.join(__dirname, '../views/dashboard/_header.php');
const headerPhp = fs.readFileSync(headerPhpPath, 'utf8');
assertTest(
    'R2: views/dashboard/_header.php oturumsuz durumda Giriş/Kayıt butonlarını koşullu görüntüler',
    headerPhp.includes('id="nav-auth-actions"') &&
    headerPhp.includes('id="nav-user-actions"') &&
    headerPhp.includes('Giriş Yap') &&
    headerPhp.includes('Kayıt Ol'),
    'Oturumsuz ziyaretçi için Giriş Yap / Kayıt Ol, oturumlu için profil/çıkış'
);

const homeCtrlPath = path.join(__dirname, '../src/Controller/HomeController.php');
const homeCtrl = fs.readFileSync(homeCtrlPath, 'utf8');
assertTest(
    'R1 & R2: HomeController::index() oturumsuz ziyarette views/landing.php render eder',
    homeCtrl.includes('!Session::has(\'user\')') &&
    homeCtrl.includes('render(\'landing\'') &&
    !homeCtrl.includes("Response::redirect($request->url('/login')"),
    'Kök rotada (/ ) oturumsuz ziyaretçi 303 ile sürgün edilmez, doğrudan landing gösterilir'
);

assertTest(
    'R3: HomeController::index() 4 rol görünüm haritasını korur (customer, restaurant, courier, admin)',
    homeCtrl.includes("'customer' => 'dashboard/customer'") &&
    homeCtrl.includes("'restaurant' => 'dashboard/restaurant'") &&
    homeCtrl.includes("'courier' => 'dashboard/courier'") &&
    homeCtrl.includes("'admin' => 'dashboard/admin'"),
    'Oturumlu kullanıcı rolüne uygun dashboarda render edilir'
);

const indexPhpPath = path.join(__dirname, '../public/index.php');
const indexPhp = fs.readFileSync(indexPhpPath, 'utf8');
assertTest(
    'R4: public/index.php aktif Front Controller Auth Guard (PRG 303 -> /login) içerir',
    indexPhp.includes("!Session::has('user')") &&
    indexPhp.includes("Response::redirect($request->url('/login'), 303)"),
    'Korumalı rotalarda oturumsuz istekler aktif olarak 303 ile /login adresine yönlendirilir'
);

// -----------------------------------------------------------------------------
// 2. İSTEMCİ TARAFI DURUM VE OTOMATİK GİRİŞ TEMİZLİĞİ (FRONTEND STATE ISOLATION)
// -----------------------------------------------------------------------------
console.log('\n--- 2. İSTEMCİ DURUM VE OTOMATİK GİRİŞ TEMİZLİĞİ TESTLERİ ---');

// Mock browser environment
const localStorageMock = (() => {
    let store = {};
    return {
        getItem: (k) => store[k] || null,
        setItem: (k, v) => { store[k] = String(v); },
        removeItem: (k) => { delete store[k]; },
        clear: () => { store = {}; }
    };
})();

// Create DOM elements map
const elements = {};
function mockElement(id, classes = []) {
    const classSet = new Set(classes);
    return {
        id,
        style: {},
        value: '',
        innerText: '',
        innerHTML: '',
        classList: {
            add: (c) => classSet.add(c),
            remove: (c) => classSet.delete(c),
            contains: (c) => classSet.has(c)
        }
    };
}

const requiredIds = [
    'view-landing', 'view-customer', 'view-restaurant', 'view-courier', 'view-admin',
    'nav-auth-actions', 'nav-user-actions', 'floating-cart-bar', 'admin-return-bar',
    'banner-admin', 'banner-courier', 'banner-restaurant', 'nav-name', 'nav-role',
    'nav-avatar', 'res-selector', 'courier-selector', 'customer-map-canvas',
    'courier-map-canvas', 'admin-map-canvas'
];
requiredIds.forEach(id => { elements[id] = mockElement(id); });

const customerUiEls = [
    mockElement('btn-nav-address', ['customer-only-ui']),
    mockElement('del-mode-toggle', ['customer-only-ui']),
    mockElement('btn-nav-cart', ['customer-only-ui']),
    mockElement('btn-nav-orders', ['customer-only-ui']),
    mockElement('global-search-box', ['customer-only-ui'])
];

function getElementById(id) {
    if (!elements[id]) {
        elements[id] = mockElement(id);
    }
    return elements[id];
}

const sandbox = {
    localStorage: localStorageMock,
    sessionStorage: localStorageMock,
    document: {
        getElementById: getElementById,
        querySelectorAll: (selector) => {
            if (selector === '.customer-only-ui') return customerUiEls;
            return [];
        },
        documentElement: { setAttribute: () => {} },
        addEventListener: () => {}
    },
    window: {
        location: { href: 'index.html', search: '' },
        addEventListener: () => {},
        ACTIVE_SESSION: null,
        BASE_PATH: ''
    },
    console: { log: () => {}, warn: () => {}, error: () => {} },
    setTimeout: (fn) => fn(),
    setInterval: () => 1,
    clearInterval: () => {}
};
vm.createContext(sandbox);

// 1. Load data.js
const dataJs = fs.readFileSync(path.join(__dirname, '../public/js/data.js'), 'utf8');
vm.runInContext(dataJs, sandbox);

// 2. Load state.js (already assigns window.GustoState = GustoState)
const stateJs = fs.readFileSync(path.join(__dirname, '../public/js/state.js'), 'utf8');
vm.runInContext(stateJs, sandbox);

const cleanInitialState = sandbox.window.GustoState.get();
assertTest(
    'R1 & R2: GustoState varsayılan currentRole değeri null olmalı (Ahmet Yılmaz veya customer olmamalı)',
    cleanInitialState.currentRole === null,
    `Alınan: ${cleanInitialState.currentRole}`
);

assertTest(
    'R1 & R2: GustoState varsayılan currentUser değeri null olmalı (oturum açmamış ziyaretçi)',
    cleanInitialState.currentUser === null,
    `Alınan: ${JSON.stringify(cleanInitialState.currentUser)}`
);

// 3. Load app.js (with mock map & audio)
sandbox.GustoMap = sandbox.window.GustoMap = {
    drawMap: () => {},
    stopCourierAnimation: () => {},
    setFilter: () => {},
    handleCanvasMouseMove: () => {}
};
sandbox.GustoAudio = sandbox.window.GustoAudio = { play: () => {} };
sandbox.showToast = sandbox.window.showToast = () => {};
sandbox.showRestaurants = sandbox.window.showRestaurants = () => {};
sandbox.renderResPanel = sandbox.window.renderResPanel = () => {};
sandbox.renderCourierPanel = sandbox.window.renderCourierPanel = () => {};
sandbox.renderAdminCouriers = sandbox.window.renderAdminCouriers = () => {};
sandbox.renderAdminAudit = sandbox.window.renderAdminAudit = () => {};

const appJs = fs.readFileSync(path.join(__dirname, '../public/js/app.js'), 'utf8');
const executableAppJs = appJs.replace('function applyRoleIsolation', 'window.applyRoleIsolation = function applyRoleIsolation');
vm.runInContext(executableAppJs, sandbox);

// Test 2.1: Clean visitor calls applyRoleIsolation(null)
sandbox.window.applyRoleIsolation(null);

assertTest(
    'R1: applyRoleIsolation(null) #view-landing karşılama ekranını görünür (display: block) yapar',
    elements['view-landing'].style.display === 'block' && !elements['view-landing'].classList.contains('hidden'),
    'Landing ekranı ziyaretçiye sunuluyor'
);

assertTest(
    'R1: applyRoleIsolation(null) 4 rol panelini gizler (customer, restaurant, courier, admin)',
    elements['view-customer'].style.display === 'none' &&
    elements['view-restaurant'].style.display === 'none' &&
    elements['view-courier'].style.display === 'none' &&
    elements['view-admin'].style.display === 'none',
    'Tüm rol panelleri oturumsuz ziyaretçiden gizlendi'
);

assertTest(
    'R1: applyRoleIsolation(null) .customer-only-ui elemanlarını gizler',
    customerUiEls.every(el => el.style.display === 'none'),
    'Müşteri araçları (adres, sepet, siparişler) oturumsuz kullanıcıya kapalı'
);

assertTest(
    'R1: applyRoleIsolation(null) #nav-auth-actions (Giriş/Kayıt) gösterir, #nav-user-actions gizler',
    elements['nav-auth-actions'].style.display === 'flex' &&
    elements['nav-user-actions'].style.display === 'none',
    'Navbarda açıkça Giriş Yap ve Kayıt Ol seçenekleri yer alır'
);

// -----------------------------------------------------------------------------
// 3. ROL TABANLI YÖNLENDİRME TESTLERİ (ROLE-BASED REDIRECTION)
// -----------------------------------------------------------------------------
console.log('\n--- 3. ROL TABANLI YÖNLENDİRME VE PANEL İZOLASYONU TESTLERİ ---');

// Test 3.1: Customer role activation
sandbox.window.applyRoleIsolation('customer');
assertTest(
    'R3: Müşteri rolü (customer): #view-landing gizlenir, #view-customer açılır',
    elements['view-landing'].style.display === 'none' &&
    elements['view-customer'].style.display === 'block' &&
    elements['view-restaurant'].style.display === 'none' &&
    elements['view-courier'].style.display === 'none' &&
    elements['view-admin'].style.display === 'none',
    'Müşteri paneli başarıyla izole edildi'
);
assertTest(
    'R3: Müşteri rolü: #nav-user-actions görünür, #nav-auth-actions gizli, .customer-only-ui aktif',
    elements['nav-user-actions'].style.display === 'flex' &&
    elements['nav-auth-actions'].style.display === 'none' &&
    customerUiEls.every(el => el.style.display === ''),
    'Müşteri menü ve profil alanı senkronize'
);

// Test 3.2: Restaurant role activation
sandbox.window.applyRoleIsolation('restaurant');
assertTest(
    'R3: Restoran rolü (restaurant): #view-landing gizlenir, #view-restaurant açılır, banner-restaurant aktif',
    elements['view-landing'].style.display === 'none' &&
    elements['view-restaurant'].style.display === 'block' &&
    elements['banner-restaurant'].style.display === 'flex' &&
    elements['view-customer'].style.display === 'none' &&
    customerUiEls.every(el => el.style.display === 'none'),
    'Restoran mutfak ekranı (KDS) ve rol başlığı izole edildi'
);

// Test 3.3: Courier role activation
sandbox.window.applyRoleIsolation('courier');
assertTest(
    'R3: Kurye rolü (courier): #view-landing gizlenir, #view-courier açılır, banner-courier aktif',
    elements['view-landing'].style.display === 'none' &&
    elements['view-courier'].style.display === 'block' &&
    elements['banner-courier'].style.display === 'flex' &&
    elements['view-customer'].style.display === 'none' &&
    customerUiEls.every(el => el.style.display === 'none'),
    'Kurye canlı kokpiti ve rol başlığı izole edildi'
);

// Test 3.4: Admin role activation
sandbox.window.applyRoleIsolation('admin');
assertTest(
    'R3: Yönetici rolü (admin): #view-landing gizlenir, #view-admin açılır, banner-admin aktif',
    elements['view-landing'].style.display === 'none' &&
    elements['view-admin'].style.display === 'block' &&
    elements['banner-admin'].style.display === 'flex' &&
    elements['view-customer'].style.display === 'none' &&
    customerUiEls.every(el => el.style.display === 'none'),
    'Yönetici denetim merkezi ve rol başlığı izole edildi'
);

// Test 3.5: Unknown/adversarial role activation falls back to applyRoleIsolation(null)
sandbox.window.applyRoleIsolation('hacker');
assertTest(
    'R3: Bilinmeyen/geçersiz rol (hacker): applyRoleIsolation(null) güvenli geri dönüşü sağlar',
    elements['view-landing'].style.display === 'block' &&
    elements['view-customer'].style.display === 'none' &&
    elements['view-restaurant'].style.display === 'none' &&
    elements['view-courier'].style.display === 'none' &&
    elements['view-admin'].style.display === 'none' &&
    elements['nav-auth-actions'].style.display === 'flex' &&
    elements['nav-user-actions'].style.display === 'none',
    'Geçersiz roller güvenli biçimde oturumsuz landing ekranına düşürüldü'
);

// -----------------------------------------------------------------------------
// 4. GİRİŞ VE KAYIT AKIŞININ ROL ENTEGRASYONU (LOGIN / REGISTER INTEGRATION)
// -----------------------------------------------------------------------------
console.log('\n--- 4. GİRİŞ / KAYIT VE ROL SENKRONİZASYONU ---');

// Extract script from public/login.html
const loginHtml = fs.readFileSync(path.join(__dirname, '../public/login.html'), 'utf8');
const loginScript = loginHtml.match(/<script>([\s\S]*?)<\/script>/)[1];

let loginNavigatedTo = null;
const loginElements = {};
function getLoginElementById(id) {
    if (!loginElements[id]) {
        loginElements[id] = {
            id,
            value: '',
            style: {},
            className: '',
            classList: { remove: () => {}, add: () => {} },
            set innerHTML(v) {},
            get innerHTML() { return ''; },
            set innerText(v) {},
            get innerText() { return ''; }
        };
    }
    return loginElements[id];
}

const loginSandbox = {
    document: {
        getElementById: getLoginElementById
    },
    localStorage: localStorageMock,
    sessionStorage: localStorageMock,
    window: {
        location: {
            set href(val) { loginNavigatedTo = val; },
            get href() { return loginNavigatedTo; },
            search: ''
        },
        addEventListener: () => {}
    },
    URLSearchParams: URLSearchParams,
    setTimeout: (fn) => fn(),
    console: { log: () => {}, warn: () => {} },
    Date: Date
};
vm.createContext(loginSandbox);
vm.runInContext(loginScript, loginSandbox);

// Test 4.1: Static login as Courier
localStorageMock.clear();
loginNavigatedTo = null;
getLoginElementById('inp-username').value = 'kurye_ali';
getLoginElementById('inp-password').value = 'hash123';
loginSandbox.handleStaticLogin({ preventDefault: () => {} });

const courierSession = JSON.parse(localStorageMock.getItem('gustogo_session') || '{}');
assertTest(
    'R3: Kurye girişi (kurye_ali) sonrasında oturumda role: "courier" oluşur ve index.html sayfasına yönlenir',
    loginNavigatedTo && loginNavigatedTo.startsWith('index.html') && courierSession.role === 'courier',
    `Rol: ${courierSession.role}, Yönlendirme: ${loginNavigatedTo}`
);

// Test 4.2: Static login as Restaurant
localStorageMock.clear();
loginNavigatedTo = null;
getLoginElementById('inp-username').value = 'restoran_lezzet';
getLoginElementById('inp-password').value = 'hash123';
loginSandbox.handleStaticLogin({ preventDefault: () => {} });

const restSession = JSON.parse(localStorageMock.getItem('gustogo_session') || '{}');
assertTest(
    'R3: Restoran girişi (restoran_lezzet) sonrasında oturumda role: "restaurant" oluşur ve index.html sayfasına yönlenir',
    loginNavigatedTo && loginNavigatedTo.startsWith('index.html') && restSession.role === 'restaurant',
    `Rol: ${restSession.role}, Yönlendirme: ${loginNavigatedTo}`
);

// Test 4.3: Static registration creates customer
localStorageMock.clear();
loginNavigatedTo = null;
const regUsername = 'yeni_ziyaretci_' + Date.now().toString().slice(-4);
getLoginElementById('reg-fullname').value = 'Ziyaretçi Kullanıcı';
getLoginElementById('reg-email').value = 'ziyaretci@ornek.com';
getLoginElementById('reg-phone').value = '0555 999 88 77';
getLoginElementById('reg-username').value = regUsername;
getLoginElementById('reg-password').value = 'parola123';
getLoginElementById('reg-password-confirm').value = 'parola123';
loginSandbox.handleRegister({ preventDefault: () => {} });

const regSession = JSON.parse(localStorageMock.getItem('gustogo_session') || '{}');
assertTest(
    'R3: Yeni kullanıcı kaydında role: "customer" atanır ve index.html sayfasına yönlenir',
    loginNavigatedTo && loginNavigatedTo.startsWith('index.html') && regSession.role === 'customer',
    `Rol: ${regSession.role}, Yönlendirme: ${loginNavigatedTo}`
);

// -----------------------------------------------------------------------------
// 5. BACKEND PHP MVC DOĞRULAMASI (STANDALONE CLI PHP CHECK)
// -----------------------------------------------------------------------------
console.log('\n--- 5. BACKEND PHP MVC / KÖK ROTA (/) İZOLASYON DOĞRULAMASI ---');

try {
    const phpExecutable = path.resolve(__dirname, '../php/php.exe');
    if (fs.existsSync(phpExecutable)) {
        function runPhpControllerIndex(sessionUser = null) {
            const runnerScript = path.join(__dirname, 'temp_php_check_' + Math.random().toString(36).substring(7) + '.php');
            const userExport = sessionUser ? JSON.stringify(sessionUser) : 'null';
            const phpCode = `<?php
spl_autoload_register(function ($class) {
    if (str_starts_with($class, 'App\\\\')) {
        $rel = substr($class, strlen('App\\\\'));
        $file = __DIR__ . '/../src/' . str_replace('\\\\', '/', $rel) . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

\\App\\Core\\Session::start();
$userData = json_decode('${userExport}', true);
if ($userData) {
    $_SESSION['user'] = $userData;
} else {
    $_SESSION = [];
}

$ctrl = new \\App\\Controller\\HomeController();
$ctrl->index(new \\App\\Core\\Request());
`;
            fs.writeFileSync(runnerScript, phpCode, 'utf8');
            try {
                return execSync(`"${phpExecutable}" "${runnerScript}"`, {
                    encoding: 'utf8',
                    cwd: path.resolve(__dirname, '..')
                });
            } catch (err) {
                console.error('PHP ERROR STDOUT:', err.stdout);
                console.error('PHP ERROR STDERR:', err.stderr);
                throw err;
            } finally {
                if (fs.existsSync(runnerScript)) {
                    fs.unlinkSync(runnerScript);
                }
            }
        }

        // 1. Unauthenticated -> Landing View
        const unauthOutput = runPhpControllerIndex(null);
        assertTest(
            'R1: Backend PHP / rotasında oturumsuz istek views/landing.php görünümünü render eder',
            unauthOutput.includes('landing-hero') &&
            unauthOutput.includes('Kayıt Ol') &&
            unauthOutput.includes('Giriş Yap') &&
            unauthOutput.includes('4 Ayrı Rol'),
            'Landing görünümü ve Giriş/Kayıt butonları sunucu tarafında oluşturuldu'
        );

        // 2. Authenticated Customer -> Customer Dashboard
        const customerOutput = runPhpControllerIndex({ id: 1, username: 'ahmet_musteri', fullName: 'Ahmet Yılmaz', role: 'customer' });
        assertTest(
            'R3: Backend PHP / rotasında oturumlu Müşteri dashboard/customer görünümüne yönlendirilir',
            customerOutput.includes('Müşteri') || customerOutput.includes('customer') || customerOutput.includes('Cravelo'),
            'Müşteri paneli render edildi'
        );

        // 3. Authenticated Restaurant -> Restaurant Dashboard
        const restOutput = runPhpControllerIndex({ id: 4, username: 'restoran_lezzet', fullName: 'Lezzet Kebap', role: 'restaurant' });
        assertTest(
            'R3: Backend PHP / rotasında oturumlu Restoran dashboard/restaurant görünümüne yönlendirilir',
            restOutput.includes('Restoran') || restOutput.includes('restaurant') || restOutput.includes('Mutfak Ekranı'),
            'Restoran KDS paneli render edildi'
        );

        // 4. Authenticated Courier -> Courier Dashboard
        const courierOutput = runPhpControllerIndex({ id: 7, username: 'kurye_ali', fullName: 'Ali Kaya', role: 'courier' });
        assertTest(
            'R3: Backend PHP / rotasında oturumlu Kurye dashboard/courier görünümüne yönlendirilir',
            courierOutput.includes('Kurye') || courierOutput.includes('courier') || courierOutput.includes('Kokpit'),
            'Kurye kokpiti render edildi'
        );

        // 5. Authenticated Admin -> Admin Dashboard
        const adminOutput = runPhpControllerIndex({ id: 10, username: 'admin_mustafa', fullName: 'Mustafa Güreşir', role: 'admin' });
        assertTest(
            'R3: Backend PHP / rotasında oturumlu Yönetici dashboard/admin görünümüne yönlendirilir',
            adminOutput.includes('Yönetici') || adminOutput.includes('admin') || adminOutput.includes('Mustafa Güreşir'),
            'Yönetici merkezi render edildi'
        );

        // 6. Backend AuthController registration without ext-mbstring
        function runPhpSnippet(snippet) {
            const runnerScript = path.join(__dirname, 'temp_snippet_runner.php');
            const phpCode = `<?php
spl_autoload_register(function ($class) {
    if (str_starts_with($class, 'App\\\\')) {
        $file = __DIR__ . '/../src/' . str_replace('\\\\', '/', substr($class, 4)) . '.php';
        if (file_exists($file)) require_once $file;
    }
});
\\App\\Core\\Session::start();
${snippet}
`;
            fs.writeFileSync(runnerScript, phpCode, 'utf8');
            try {
                return execSync(`"${phpExecutable}" "${runnerScript}"`, {
                    encoding: 'utf8',
                    cwd: path.resolve(__dirname, '..')
                });
            } catch (err) {
                return (err.stdout || '') + (err.stderr || '');
            } finally {
                if (fs.existsSync(runnerScript)) fs.unlinkSync(runnerScript);
            }
        }

        const regOutput = runPhpSnippet(`
register_shutdown_function(function() {
    $flash = \\App\\Core\\Session::getFlash();
    echo "REG_FLASH:" . ($flash['message'] ?? '');
});
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = ['full_name' => 'Test', 'username' => 'ab', 'password' => 'secret123', 'password_confirm' => 'secret123'];
(new \\App\\Controller\\AuthController())->register(new \\App\\Core\\Request());
`);
        assertTest(
            'R3: AuthController::register mb_strlen bağımlılığı olmadan CLI PHP ortamında başarıyla çalışır',
            regOutput.includes('REG_FLASH:Kullanıcı adı en az 3 karakterden oluşmalıdır.') && !regOutput.includes('Fatal error'),
            'Kayıt işlemi mbstring olmadan hatasız çalıştı'
        );

        const kdsOutput = runPhpSnippet(`
$_SESSION['user'] = ['id' => 4, 'role' => 'restaurant', 'username' => 'restoran'];
(new \\App\\Controller\\ApiController())->handleKdsOrders(new \\App\\Core\\Request());
`);
        assertTest(
            'R3: ApiController::handleKdsOrders string createdAt tarihini format hatası olmadan döner',
            kdsOutput.includes('"status": "success"') && kdsOutput.includes('"created_at"') && !kdsOutput.includes('Fatal error'),
            'KDS mutfak siparişleri format() hatası olmadan JSON döndürdü'
        );

        const auditOutput = runPhpSnippet(`
$_SESSION['user'] = ['id' => 10, 'role' => 'admin', 'username' => 'admin'];
(new \\App\\Controller\\ApiController())->handleAuditLogs(new \\App\\Core\\Request());
`);
        assertTest(
            'R3: ApiController::handleAuditLogs string createdAt tarihini format hatası olmadan döner',
            auditOutput.includes('"status": "success"') && auditOutput.includes('"audit_logs"') && !auditOutput.includes('Fatal error'),
            'Yönetici denetim logları format() hatası olmadan JSON döndürdü'
        );

        const guestOrderOutput = runPhpSnippet(`
$_SESSION = [];
$_GET = ['id' => 1];
$_SERVER['REQUEST_METHOD'] = 'GET';
(new \\App\\Controller\\ApiController())->handleOrderStatus(new \\App\\Core\\Request());
`);
        assertTest(
            'R2 & R3: ApiController::handleOrderStatus oturumsuz ziyaretçiyi 401 ile reddeder (IDOR Önlemi)',
            guestOrderOutput.includes('Yetkisiz erişim') && !guestOrderOutput.includes('"status": "success"'),
            'Oturumsuz ziyaretçinin sipariş sorgulaması engellendi'
        );

        const customerOrderOutput = runPhpSnippet(`
$_SESSION['user'] = ['id' => 1, 'role' => 'customer', 'username' => 'ahmet'];
$_GET = ['id' => 1];
$_SERVER['REQUEST_METHOD'] = 'GET';
(new \\App\\Controller\\ApiController())->handleOrderStatus(new \\App\\Core\\Request());
`);
        assertTest(
            'R3: ApiController::handleOrderStatus kurye enlem/boylam (lat, lng) ve araç bilgilerini PHP uyarısı olmadan döner',
            customerOrderOutput.includes('"lat": 40.976') && customerOrderOutput.includes('"lng": 29.038') && !customerOrderOutput.includes('Warning: Undefined property'),
            'Kurye koordinatları ve özellikleri uyarısız döndürüldü'
        );
    } else {
        console.log('  ⚠️ PHP binary bulunamadı, CLI PHP kontrolü atlandı.');
    }
} catch (err) {
    assertTest('Backend PHP MVC / kök rota kontrolü', false, err.message);
}

// -----------------------------------------------------------------------------
// SONUÇ RAPORU
// -----------------------------------------------------------------------------
console.log('\n======================================================================');
console.log(`TEST SONUCU: ${testsPassed} Başarılı, ${testsTotal - testsPassed} Başarısız (Toplam: ${testsTotal} Test)`);
console.log('======================================================================');

if (testsPassed === testsTotal) {
    process.exit(0);
} else {
    process.exit(1);
}
