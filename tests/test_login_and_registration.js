/**
 * Automated Test: Login Validation & Registration Logic Verification
 */
const fs = require('fs');
const path = require('path');

// Extract script from public/login.html
const loginHtml = fs.readFileSync(path.join(__dirname, '../public/login.html'), 'utf8');
const scriptContent = loginHtml.match(/<script>([\s\S]*?)<\/script>/)[1];

// Mock DOM and localStorage
const localStorageMock = (() => {
    let store = {};
    return {
        getItem: (k) => store[k] || null,
        setItem: (k, v) => { store[k] = String(v); },
        removeItem: (k) => { delete store[k]; },
        clear: () => { store = {}; }
    };
})();

let alertState = { className: '', innerHTML: '', isVisible: false };
let navigatedTo = null;
let domElements = {};

function getElementById(id) {
    if (!domElements[id]) {
        domElements[id] = {
            id,
            value: '',
            style: {},
            className: '',
            classList: {
                remove: (cls) => { if (cls === 'd-none') alertState.isVisible = true; },
                add: (cls) => { if (cls === 'd-none') alertState.isVisible = false; }
            },
            set innerHTML(val) { alertState.innerHTML = val; },
            get innerHTML() { return alertState.innerHTML; },
            set innerText(val) { alertState.innerHTML = val; },
            get innerText() { return alertState.innerHTML; },
            set className(val) { alertState.className = val; },
            get className() { return alertState.className; }
        };
    }
    return domElements[id];
}

// Sandbox execution context
const sandbox = {
    document: {
        getElementById: getElementById
    },
    localStorage: localStorageMock,
    window: {
        location: {
            set href(val) { navigatedTo = val; },
            get href() { return navigatedTo; },
            search: ''
        },
        addEventListener: () => {}
    },
    URLSearchParams: URLSearchParams,
    setTimeout: (fn) => fn(), // execute immediately in tests
    console: console,
    Date: Date
};

// Evaluate the script in this environment
const vm = require('vm');
vm.createContext(sandbox);
vm.runInContext(scriptContent, sandbox);

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

console.log('--- TEST SUITE: STRICT LOGIN & REGISTRATION ---');

// Test 1: Random non-existent user with standard password 'hash123' MUST BE REJECTED
sandbox.document.getElementById('inp-username').value = 'rastgele_kullanici_123';
sandbox.document.getElementById('inp-password').value = 'hash123';
navigatedTo = null;
sandbox.handleStaticLogin({ preventDefault: () => {} });

assert(!navigatedTo, 'Non-existent user must NOT navigate to index.html');
assert(alertState.innerHTML.includes('Böyle bir kullanıcı bulunamadı'), 'Rejection message displayed for non-existent user');

// Test 2: Seed user with incorrect password MUST BE REJECTED
sandbox.document.getElementById('inp-username').value = 'admin_mustafa';
sandbox.document.getElementById('inp-password').value = 'yanlis_sifre';
navigatedTo = null;
sandbox.handleStaticLogin({ preventDefault: () => {} });

assert(!navigatedTo, 'Seed user with wrong password must NOT navigate');
assert(alertState.innerHTML.includes('Hatalı parola'), 'Rejection message displayed for wrong password');

// Test 3: Seed user with correct password MUST SUCCEED
sandbox.document.getElementById('inp-username').value = 'admin_mustafa';
sandbox.document.getElementById('inp-password').value = 'hash123';
navigatedTo = null;
sandbox.handleStaticLogin({ preventDefault: () => {} });

assert(navigatedTo && navigatedTo.startsWith('index.html'), 'Seed user with correct password navigates to index.html');
const session1 = JSON.parse(sandbox.localStorage.getItem('cravelo_session'));
assert(session1 && session1.role === 'admin' && session1.fullName === 'Mustafa Güreşir (Yönetici)', 'Correct admin session established');

// Test 4: Registration with short username (< 3 chars) MUST BE REJECTED (FR-16)
sandbox.document.getElementById('reg-fullname').value = 'Test Müşteri';
sandbox.document.getElementById('reg-username').value = 'ab';
sandbox.document.getElementById('reg-email').value = 'test@ornek.com';
sandbox.document.getElementById('reg-phone').value = '05551234567';
sandbox.document.getElementById('reg-password').value = '123456';
sandbox.document.getElementById('reg-password-confirm').value = '123456';
navigatedTo = null;
sandbox.handleRegister({ preventDefault: () => {} });

assert(!navigatedTo, 'Registration with username < 3 chars must NOT proceed');
assert(alertState.innerHTML.includes('en az 3 karakter'), 'FR-16 username validation triggered');

// Test 5: Registration with short password (< 6 chars) MUST BE REJECTED (FR-16)
sandbox.document.getElementById('reg-username').value = 'test_musteri';
sandbox.document.getElementById('reg-password').value = '12345';
sandbox.document.getElementById('reg-password-confirm').value = '12345';
navigatedTo = null;
sandbox.handleRegister({ preventDefault: () => {} });

assert(!navigatedTo, 'Registration with password < 6 chars must NOT proceed');
assert(alertState.innerHTML.includes('en az 6 karakter'), 'FR-16 password validation triggered');

// Test 6: Registration with mismatched passwords MUST BE REJECTED
sandbox.document.getElementById('reg-password').value = 'parola123';
sandbox.document.getElementById('reg-password-confirm').value = 'parola456';
navigatedTo = null;
sandbox.handleRegister({ preventDefault: () => {} });

assert(!navigatedTo, 'Registration with mismatched passwords must NOT proceed');
assert(alertState.innerHTML.includes('eşleşmiyor'), 'Password mismatch error displayed');

// Test 7: Registration with duplicate username (seed user conflict) MUST BE REJECTED
sandbox.document.getElementById('reg-username').value = 'ahmet_musteri';
sandbox.document.getElementById('reg-password').value = 'sifre123';
sandbox.document.getElementById('reg-password-confirm').value = 'sifre123';
navigatedTo = null;
sandbox.handleRegister({ preventDefault: () => {} });

assert(!navigatedTo, 'Registration with existing seed username must NOT proceed');
assert(alertState.innerHTML.includes('zaten kayıtlı'), 'Duplicate account error displayed');

// Test 8: Valid customer registration MUST SUCCEED, save as role: customer (FR-15) & log in
sandbox.document.getElementById('reg-fullname').value = 'Zeynep Demir';
sandbox.document.getElementById('reg-username').value = 'zeynep_demir';
sandbox.document.getElementById('reg-email').value = 'zeynep@ornek.com';
sandbox.document.getElementById('reg-phone').value = '05559876543';
sandbox.document.getElementById('reg-password').value = 'zeynep123';
sandbox.document.getElementById('reg-password-confirm').value = 'zeynep123';
navigatedTo = null;
sandbox.handleRegister({ preventDefault: () => {} });

assert(navigatedTo && navigatedTo.startsWith('index.html'), 'Valid registration navigates to index.html');
const regList = JSON.parse(sandbox.localStorage.getItem('cravelo_registered_users'));
assert(regList && regList.length === 1 && regList[0].username === 'zeynep_demir' && regList[0].role === 'customer', 'New user saved in localStorage with role customer (FR-15)');
const sessionReg = JSON.parse(sandbox.localStorage.getItem('cravelo_session'));
assert(sessionReg && sessionReg.username === 'zeynep_demir' && sessionReg.role === 'customer', 'New customer session established');

// Test 9: Logging in with the newly registered user's credentials MUST SUCCEED
sandbox.document.getElementById('inp-username').value = 'zeynep_demir';
sandbox.document.getElementById('inp-password').value = 'zeynep123';
navigatedTo = null;
sandbox.handleStaticLogin({ preventDefault: () => {} });

assert(navigatedTo && navigatedTo.startsWith('index.html'), 'Registered customer can log in with chosen password');
const sessionLogin = JSON.parse(sandbox.localStorage.getItem('cravelo_session'));
assert(sessionLogin && sessionLogin.username === 'zeynep_demir' && sessionLogin.role === 'customer', 'Customer session created on subsequent login');

// Test 10: Logging in with registered user but WRONG password MUST BE REJECTED
sandbox.document.getElementById('inp-username').value = 'zeynep_demir';
sandbox.document.getElementById('inp-password').value = 'yanlis_sifre_999';
navigatedTo = null;
sandbox.handleStaticLogin({ preventDefault: () => {} });

assert(!navigatedTo, 'Registered customer with wrong password must NOT navigate');
assert(alertState.innerHTML.includes('Hatalı parola'), 'Rejection message displayed for wrong password');

// Test 11: Logging in as Mustafa Güreşir MUST establish session as Mustafa Güreşir
sandbox.document.getElementById('inp-username').value = 'mustafaguresir@gmail.com';
sandbox.document.getElementById('inp-password').value = 'hash123';
navigatedTo = null;
sandbox.handleStaticLogin({ preventDefault: () => {} });

assert(navigatedTo && navigatedTo.startsWith('index.html'), 'Mustafa Güreşir login navigates to index.html');
const sessionMustafa = JSON.parse(sandbox.localStorage.getItem('cravelo_session'));
assert(sessionMustafa && sessionMustafa.role === 'admin' && sessionMustafa.fullName === 'Mustafa Güreşir (Yönetici)', 'Mustafa Güreşir session fullName is correct');
assert(sessionMustafa && sessionMustafa.username === 'mustafaguresir@gmail.com', 'Mustafa Güreşir session username is correct');

console.log(`\nResults: ${testsPassed}/${testsTotal} tests passed.`);
if (testsPassed === testsTotal) {
    process.exit(0);
} else {
    process.exit(1);
}
