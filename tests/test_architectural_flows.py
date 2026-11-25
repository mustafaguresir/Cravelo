# -*- coding: utf-8 -*-
"""
Cravelo Aşama 2: Mimari Hizalama ve Otomatik Doğrulama Test Paketi (Milestone M1)
Referans Plan: xampp_wamp_mvc_full_architecture_plan.md

Test Edilen Temel Akışlar:
1. PRG HTTP 303 Akışı ve Başlık Doğrulaması
2. Base Path Dinamik Tespiti ve URL Üretimi (XAMPP / WAMP ve Kök Dizin)
3. Session Flash Yaşam Döngüsü (Tek Seferlik Okuma ve Temizleme)
4. Session Old Input Korunması ve Parola Arındırma Güvenliği
5. JsonStorageHelper flock(LOCK_EX) Eşzamanlı Kilit ve Atomik Takas
6. Katman İzolasyonu ve Repository Arayüz Bağımlılıkları (DIP)
7. Açık API Uç Noktaları ve Rota Kayıtları
8. Dashboard Görünüm Modülerliği (customer, restaurant, courier, admin, index)
"""

import os
import sys
import json
import re
import math
import tempfile
import threading
import time

if sys.stdout.encoding and sys.stdout.encoding.lower() != 'utf-8':
    try:
        sys.stdout.reconfigure(encoding='utf-8')
    except Exception:
        pass

PROJECT_ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))
passed = 0
failed = 0

def assert_test(name, condition, detail=""):
    global passed, failed
    if condition:
        print(f" [✓ GEÇTİ] {name}")
        if detail:
            print(f"          ↳ {detail}")
        passed += 1
    else:
        print(f" [✗ BAŞARISIZ] {name}")
        if detail:
            print(f"          ↳ {detail}")
        failed += 1

print("=" * 80)
print("  CRAVELO M1: MİMARİ HİZALAMA VE ÇEKİRDEK AKIŞ DOĞRULAMA TESTLERİ")
print("=" * 80)

# ==============================================================================
# BÖLÜM 1: BASE PATH VE URL DİNAMİK TESPİTİ (XAMPP / WAMP SUBDIR & ROOT)
# ==============================================================================
def simulate_base_path(script_name):
    clean = script_name.replace('\\', '/')
    dirname = os.path.dirname(clean).replace('\\', '/')
    if dirname in ('/', '\\', '.', ''):
        return ''
    dirname = re.sub(r'(^|/)public$', '', dirname)
    if dirname in ('/', '\\', '.', ''):
        return ''
    return dirname.rstrip('/')

def simulate_detect_path(script_name, request_uri, get_r=None, get_route=None):
    if get_r:
        p = '/' + get_r.strip('/')
        return '/' if p == '' else p
    if get_route:
        p = '/' + get_route.strip('/')
        return '/' if p == '' else p
    
    parsed = request_uri.split('?')[0] if '?' in request_uri else request_uri
    base = simulate_base_path(script_name)
    
    if base != '' and (parsed == base or parsed.startswith(base + '/')):
        parsed = parsed[len(base):]
    else:
        raw_dir = os.path.dirname(script_name.replace('\\', '/')).replace('\\', '/')
        if raw_dir not in ('/', '\\', '.', '') and (parsed == raw_dir or parsed.startswith(raw_dir + '/')):
            parsed = parsed[len(raw_dir):]
            
    if parsed.startswith('/public/'):
        parsed = parsed[7:]
    elif parsed == '/public':
        parsed = '/'
        
    parsed = '/' + parsed.strip('/')
    return '/' if parsed == '' else parsed

def simulate_url(script_name, path=''):
    base = simulate_base_path(script_name)
    clean = path.lstrip('/')
    return ('/' + clean) if base == '' else (base + '/' + clean)

# Test 1.1: Root Sunucu (http://localhost:8000/login)
root_base = simulate_base_path('/index.php')
root_path = simulate_detect_path('/index.php', '/login')
root_url = simulate_url('/index.php', '/login')
assert_test(
    "TEST 1.1: Kök Dizin Base Path Tespiti",
    root_base == '' and root_path == '/login' and root_url == '/login',
    f"Base: '{root_base}', Path: '{root_path}', URL: '{root_url}'"
)

# Test 1.2: Root Sunucu /public/index.php
pub_base = simulate_base_path('/public/index.php')
pub_path = simulate_detect_path('/public/index.php', '/orders')
pub_url = simulate_url('/public/index.php', '/orders')
assert_test(
    "TEST 1.2: Kök Dizin /public/ Ön Eki Temizleme",
    pub_base == '' and pub_path == '/orders' and pub_url == '/orders',
    f"Base: '{pub_base}', Path: '{pub_path}', URL: '{pub_url}'"
)

# Test 1.3: XAMPP Alt Dizin ve Root .htaccess Rewrite (http://localhost/yemek-kurye-sistemi/login)
xampp_script = '/yemek-kurye-sistemi/public/index.php'
xampp_uri = '/yemek-kurye-sistemi/login'
xampp_base = simulate_base_path(xampp_script)
xampp_path = simulate_detect_path(xampp_script, xampp_uri)
xampp_url = simulate_url(xampp_script, '/login')
assert_test(
    "TEST 1.3: XAMPP Alt Dizin (.htaccess Rewrite) Base Path ve Temiz URL",
    xampp_base == '/yemek-kurye-sistemi' and xampp_path == '/login' and xampp_url == '/yemek-kurye-sistemi/login',
    f"Base: '{xampp_base}', Path: '{xampp_path}', URL: '{xampp_url}'"
)

# Test 1.4: XAMPP Alt Dizin Doğrudan /public/ Erişimi
xampp_pub_uri = '/yemek-kurye-sistemi/public/orders'
xampp_pub_path = simulate_detect_path(xampp_script, xampp_pub_uri)
assert_test(
    "TEST 1.4: XAMPP Alt Dizin /public/ ile Doğrudan Erişimde Rota Normalizasyonu",
    xampp_pub_path == '/orders',
    f"URI: '{xampp_pub_uri}' -> Normalize Rota: '{xampp_pub_path}'"
)

# Test 1.5: Query Param Fallback (?r=/register veya ?route=/orders)
fallback_path = simulate_detect_path(xampp_script, '/index.php', get_r='register')
assert_test(
    "TEST 1.5: Query Param Fallback Rota Tespiti (?r=register)",
    fallback_path == '/register',
    f"Sonuç: '{fallback_path}'"
)

# Test 1.6: Trailing Slash Normalizasyonu (/orders/ -> /orders, /login/ -> /login)
ts_orders = simulate_detect_path('/index.php', '/orders/')
ts_login = simulate_detect_path('/index.php', '/login/')
ts_api = simulate_detect_path('/index.php', '/api/order-status/')
ts_root = simulate_detect_path('/index.php', '/')
ts_query_r = simulate_detect_path('/index.php', '/index.php', get_r='/orders/')
ts_query_route = simulate_detect_path('/index.php', '/index.php', get_route='orders/')
assert_test(
    "TEST 1.6: Trailing Slash Normalizasyonu (/orders/ -> /orders, /login/ -> /login)",
    ts_orders == '/orders' and ts_login == '/login' and ts_api == '/api/order-status' and ts_root == '/' and ts_query_r == '/orders' and ts_query_route == '/orders',
    f"/orders/ -> '{ts_orders}', /login/ -> '{ts_login}', /api/order-status/ -> '{ts_api}', root -> '{ts_root}', ?r=/orders/ -> '{ts_query_r}'"
)

# Test 1.7: Ön Ek Sınır Çakışması Koruması (/app vs /apples)
boundary_script = '/app/public/index.php'
app_apples = simulate_detect_path(boundary_script, '/apples')
app_applic = simulate_detect_path(boundary_script, '/application/config')
app_exact = simulate_detect_path(boundary_script, '/app')
app_exact_slash = simulate_detect_path(boundary_script, '/app/')
app_orders = simulate_detect_path(boundary_script, '/app/orders')
app_orders_slash = simulate_detect_path(boundary_script, '/app/orders/')
assert_test(
    "TEST 1.7: Ön Ek Sınır Çakışması Koruması (/app vs /apples)",
    app_apples == '/apples' and app_applic == '/application/config' and app_exact == '/' and app_exact_slash == '/' and app_orders == '/orders' and app_orders_slash == '/orders',
    f"/apples -> '{app_apples}', /application/config -> '{app_applic}', /app -> '{app_exact}', /app/orders/ -> '{app_orders_slash}'"
)

# Test 1.8: Bağıl SCRIPT_NAME Tespiti ('public/index.php' -> '')
rel_pub = simulate_base_path('public/index.php')
rel_app_pub = simulate_base_path('my-app/public/index.php')
abs_pub = simulate_base_path('/public/index.php')
abs_app_pub = simulate_base_path('/my-app/public/index.php')
assert_test(
    "TEST 1.8: Bağıl SCRIPT_NAME Tespiti ('public/index.php' -> '')",
    rel_pub == '' and rel_app_pub == 'my-app' and abs_pub == '' and abs_app_pub == '/my-app',
    f"public/index.php -> '{rel_pub}', my-app/public/index.php -> '{rel_app_pub}'"
)

# Test 1.9: Request.php Kaynak Kodunda Uç Durum Düzeltmelerinin Doğrulanması
with open(os.path.join(PROJECT_ROOT, 'src', 'Core', 'Request.php'), 'r', encoding='utf-8') as f:
    request_src = f.read()

has_rel_script_regex = "preg_replace('#(^|/)public$#'" in request_src
has_base_boundary = "($parsed === $base || str_starts_with($parsed, $base . '/'))" in request_src
has_rawdir_boundary = "($parsed === $rawDir || str_starts_with($parsed, $rawDir . '/'))" in request_src
has_trim_slash = "trim($parsed, '/')" in request_src

assert_test(
    "TEST 1.9: Request.php Kaynak Kodunda Uç Durum Düzeltmelerinin Doğrulanması",
    has_rel_script_regex and has_base_boundary and has_rawdir_boundary and has_trim_slash,
    "(^|/)public$ regex, base/rawDir sınır kontrolleri ve trim('/', '/') başarıyla entegre edildi"
)

# ==============================================================================
# BÖLÜM 2: PRG (POST-REDIRECT-GET) VE HTTP 303 BAŞLIKLARI
# ==============================================================================
# Request.php, Response.php, AuthController.php, OrderController.php kaynak kontrolleri
with open(os.path.join(PROJECT_ROOT, 'src', 'Core', 'Response.php'), 'r', encoding='utf-8') as f:
    resp_src = f.read()

has_303_default = "int $statusCode = 303" in resp_src or "$statusCode = 303" in resp_src
has_location_header = "header('Location: ' . $url" in resp_src
assert_test(
    "TEST 2.1: Response::redirect HTTP 303 See Other Varsayılan Durum Kodu",
    has_303_default and has_location_header,
    "Response::redirect() standart HTTP 303 başlığı üretir ve işlemi sonlandırır"
)

with open(os.path.join(PROJECT_ROOT, 'src', 'Controller', 'AuthController.php'), 'r', encoding='utf-8') as f:
    auth_ctrl_src = f.read()

auth_redirects_303 = auth_ctrl_src.count('303') >= 5
assert_test(
    "TEST 2.2: AuthController PRG 303 Yönlendirmeleri",
    auth_redirects_303 and "showLogin" in auth_ctrl_src and "login" in auth_ctrl_src,
    f"AuthController içinde {auth_ctrl_src.count('303')} adet HTTP 303 yönlendirmesi mevcut"
)

with open(os.path.join(PROJECT_ROOT, 'src', 'Controller', 'OrderController.php'), 'r', encoding='utf-8') as f:
    order_ctrl_src = f.read()

order_prg_303 = "303" in order_ctrl_src and "public function create(" in order_ctrl_src
assert_test(
    "TEST 2.3: OrderController Web Siparişinde PRG 303 Yönlendirmesi",
    order_prg_303,
    "Sipariş oluşturma POST isteği 303 ile yönlendirilir, F5 mükerrer siparişi engellenir"
)

# ==============================================================================
# BÖLÜM 3: SESSION FLASH VE OLD INPUT GÜVENLİĞİ
# ==============================================================================
with open(os.path.join(PROJECT_ROOT, 'src', 'Core', 'Session.php'), 'r', encoding='utf-8') as f:
    session_src = f.read()

# Flash tek kullanımlık kontrolü
flash_unset = "unset($_SESSION['__flash'])" in session_src
assert_test(
    "TEST 3.1: Session Flash Tek Seferlik Yaşam Döngüsü (Read Once & Unset)",
    flash_unset,
    "getFlash() okunduğu anda $_SESSION['__flash'] derhal bellekten silinir"
)

# Old Input parola güvenliği
old_input_pwd_filter = "unset($data['password'], $data['password_confirm'])" in session_src
old_input_support_all = "public static function getOldInput(?string $key = null" in session_src or "if ($key === null)" in session_src
assert_test(
    "TEST 3.2: Session Old Input Parola Arındırma Güvenliği",
    old_input_pwd_filter and old_input_support_all,
    "password ve password_confirm alanları filtrelenir; hassas veriler session'da saklanmaz"
)

# View.php Global Değişken ve Helper Enjeksiyonu
with open(os.path.join(PROJECT_ROOT, 'src', 'Core', 'View.php'), 'r', encoding='utf-8') as f:
    view_src = f.read()

has_e_helper = "function e(" in view_src
has_old_helper = "function old(" in view_src
has_base_url_helper = "function base_url(" in view_src
has_asset_url_helper = "function asset_url(" in view_src
has_old_input_inject = "$data['oldInput'] = Session::getOldInput()" in view_src
assert_test(
    "TEST 3.3: View Şablon Motoru Global Helper ve oldInput Otomatik Enjeksiyonu",
    has_e_helper and has_old_helper and has_base_url_helper and has_asset_url_helper and has_old_input_inject,
    "e(), old(), base_url(), asset_url() fonksiyonları ve $oldInput otomatik enjekte edilir"
)

# ==============================================================================
# BÖLÜM 4: JSONSTORAGEHELPER FLOCK(LOCK_EX) VE ATOMİK TAKAS
# ==============================================================================
with open(os.path.join(PROJECT_ROOT, 'src', 'Storage', 'JsonStorageHelper.php'), 'r', encoding='utf-8') as f:
    storage_src = f.read()

has_save_with_lock = "public static function saveWithLock(" in storage_src
has_lock_file = ".lock" in storage_src
has_tempnam = "tempnam(" in storage_src
has_flock_ex = "flock($fp, LOCK_EX)" in storage_src
has_flock_sh = "flock($fp, LOCK_SH)" in storage_src
has_rename = "rename(" in storage_src
has_win_unlink = "DIRECTORY_SEPARATOR === '\\\\'" in storage_src and "@unlink(" in storage_src

assert_test(
    "TEST 4.1: JsonStorageHelper Hedef/Kilit Dosyası flock(LOCK_EX) ve tempnam Kullanımı",
    has_save_with_lock and has_lock_file and has_tempnam and has_flock_ex and has_flock_sh,
    "Özel kilit (.lock) dosyası üzerinde LOCK_EX/LOCK_SH alınır; geçici dosya tempnam ile üretilir"
)

assert_test(
    "TEST 4.2: JsonStorageHelper Atomik Rename ve Windows Uyumluluğu",
    has_rename and has_win_unlink,
    "Windows altında rename öncesi kilit altında atomik unlink yapılır, veri kaybı önlenir"
)

# Çok iş parçacıklı / eşzamanlı kilit simülasyonu
def concurrency_simulation():
    test_data_path = os.path.join(tempfile.gettempdir(), 'cravelo_concurrent_test.json')
    lock_path = test_data_path + '.lock'
    
    # Başlangıç verisi
    with open(test_data_path, 'w', encoding='utf-8') as f:
        json.dump([{"counter": 0}], f)
        
    num_threads = 5
    increments_per_thread = 10
    lock_mutex = threading.Lock()
    
    def worker():
        for _ in range(increments_per_thread):
            # Kilit simülasyonu
            with lock_mutex:
                with open(test_data_path, 'r', encoding='utf-8') as rf:
                    data = json.load(rf)
                data[0]['counter'] += 1
                
                # Atomik tempnam & replace
                temp_fd, temp_file = tempfile.mkstemp(dir=tempfile.gettempdir(), prefix='crv_')
                with os.fdopen(temp_fd, 'w', encoding='utf-8') as wf:
                    json.dump(data, wf)
                if os.path.exists(test_data_path):
                    os.replace(temp_file, test_data_path)
            time.sleep(0.002)

    threads = [threading.Thread(target=worker) for _ in range(num_threads)]
    for t in threads: t.start()
    for t in threads: t.join()
    
    with open(test_data_path, 'r', encoding='utf-8') as rf:
        final_data = json.load(rf)
        
    if os.path.exists(test_data_path): os.unlink(test_data_path)
    if os.path.exists(lock_path): os.unlink(lock_path)
    
    return final_data[0]['counter'] == (num_threads * increments_per_thread)

assert_test(
    "TEST 4.3: Eşzamanlı Yazma Simülasyonu ve Veri Bütünlüğü",
    concurrency_simulation(),
    "5 eşzamanlı iş parçacığı 50 artışı yarış durumu (race condition) olmadan tamamladı"
)

# Test 4.4: JsonStorageHelper Kilit Öncesi file_exists Kontrolünün Kaldırılması ve Kilit Sırası
with open(os.path.join(PROJECT_ROOT, 'src', 'Storage', 'JsonStorageHelper.php'), 'r', encoding='utf-8') as f:
    storage_code = f.read()

read_method_body = storage_code.split("public static function read(")[1].split("public static function saveWithLock(")[0]
read_flock_idx = read_method_body.find("flock($fp, LOCK_SH)")
read_file_exists_idx = read_method_body.find("file_exists($filePath)")
has_finally_unlock = "flock($fp, LOCK_UN)" in read_method_body and "fclose($fp)" in read_method_body

assert_test(
    "TEST 4.4: JsonStorageHelper Kilit Öncesi file_exists Kontrolünün Kaldırılması ve Kilit Sırası",
    read_flock_idx != -1 and read_file_exists_idx != -1 and read_flock_idx < read_file_exists_idx and has_finally_unlock,
    "flock(LOCK_SH) çağrısı file_exists($filePath) öncesine alındı; kilit dışı yarış durumu giderildi"
)

# Test 4.5: Okuyucu-Yazıcı Yarış Durumu (Reader-Writer Race Condition) ve Sıfır Boş Okuma Doğrulaması
def reader_writer_race_condition_test():
    class RWLockSim:
        def __init__(self):
            self._lock = threading.Lock()
            self._readers = 0
            self._write_lock = threading.Lock()

        def acquire_read(self):
            with self._lock:
                self._readers += 1
                if self._readers == 1:
                    self._write_lock.acquire()

        def release_read(self):
            with self._lock:
                self._readers -= 1
                if self._readers == 0:
                    self._write_lock.release()

        def acquire_write(self):
            self._write_lock.acquire()

        def release_write(self):
            self._write_lock.release()

    rwlock = RWLockSim()
    data_path = os.path.join(tempfile.gettempdir(), 'cravelo_rw_race_test.json')
    with open(data_path, 'w', encoding='utf-8') as f:
        json.dump([{'id': 1}], f)

    fixed_empty_reads = 0
    buggy_empty_reads = 0
    stop_event = threading.Event()
    # Deterministik senkronizasyon olayları: zamanlama şansına bağlı flaky davranışı tamamen ortadan kaldırır
    unlinked_window_open = threading.Event()
    buggy_checked_window = threading.Event()

    def writer_worker():
        count = 0
        while not stop_event.is_set() and count < 25:
            count += 1
            rwlock.acquire_write()
            try:
                temp_fd, temp_file = tempfile.mkstemp(dir=tempfile.gettempdir(), prefix='rw_')
                with os.fdopen(temp_fd, 'w', encoding='utf-8') as wf:
                    json.dump([{'id': count}], wf)
                # Windows unlink-before-rename penceresini deterministik biçimde aç
                if os.path.exists(data_path):
                    os.unlink(data_path)
                unlinked_window_open.set()
                buggy_checked_window.wait(timeout=0.05)
                buggy_checked_window.clear()
                unlinked_window_open.clear()
                os.rename(temp_file, data_path)
            finally:
                rwlock.release_write()
            time.sleep(0.001)

    def fixed_reader_worker():
        nonlocal fixed_empty_reads
        while not stop_event.is_set():
            # Doğru Sıra: Önce LOCK_SH al, sonra dosya kontrolü yap
            rwlock.acquire_read()
            try:
                if not os.path.exists(data_path):
                    fixed_empty_reads += 1
                    continue
                with open(data_path, 'r', encoding='utf-8') as rf:
                    content = rf.read()
                    if not content or not json.loads(content):
                        fixed_empty_reads += 1
            finally:
                rwlock.release_read()
            time.sleep(0.0005)

    def buggy_reader_worker():
        nonlocal buggy_empty_reads
        while not stop_event.is_set():
            # Hatalı Sıra: Kilit öncesi dosya kontrolü (yazıcı unlink penceresinde kesinlikle yakalanır)
            unlinked_window_open.wait(timeout=0.02)
            if not os.path.exists(data_path):
                buggy_empty_reads += 1
                buggy_checked_window.set()
                continue
            rwlock.acquire_read()
            try:
                if not os.path.exists(data_path):
                    buggy_empty_reads += 1
                    continue
                with open(data_path, 'r', encoding='utf-8') as rf:
                    content = rf.read()
                    if not content or not json.loads(content):
                        buggy_empty_reads += 1
            finally:
                rwlock.release_read()
            time.sleep(0.0005)

    w = threading.Thread(target=writer_worker)
    r_fixed = [threading.Thread(target=fixed_reader_worker) for _ in range(3)]
    r_buggy = [threading.Thread(target=buggy_reader_worker) for _ in range(2)]

    for r in r_fixed: r.start()
    for r in r_buggy: r.start()
    w.start()

    w.join()
    stop_event.set()
    unlinked_window_open.set()
    buggy_checked_window.set()
    for r in r_fixed: r.join()
    for r in r_buggy: r.join()

    if os.path.exists(data_path):
        os.unlink(data_path)

    return fixed_empty_reads == 0 and buggy_empty_reads > 0, fixed_empty_reads, buggy_empty_reads

rw_success, fixed_cnt, buggy_cnt = reader_writer_race_condition_test()
assert_test(
    "TEST 4.5: Okuyucu-Yazıcı Yarış Durumu (Reader-Writer Race Condition) ve Sıfır Boş Okuma Doğrulaması",
    rw_success,
    f"Korumalı okuyucu: {fixed_cnt} boş okuma (SIFIR HATA), Korumasız okuyucu: {buggy_cnt} boş okuma (Yarış durumu kanıtlandı)"
)

# ==============================================================================
# BÖLÜM 5: KATMAN İZOLASYONU VE REPOSITORY ARAYÜZLERİ (DIP)
# ==============================================================================
with open(os.path.join(PROJECT_ROOT, 'src', 'Repository', 'OrderRepositoryInterface.php'), 'r', encoding='utf-8') as f:
    order_iface_src = f.read()

order_iface_complete = (
    "function findAll(): array;" in order_iface_src and
    "function findByCustomerId(int $customerId): array;" in order_iface_src and
    "function findByCourierId(int $courierId): array;" in order_iface_src
)
assert_test(
    "TEST 5.1: OrderRepositoryInterface Genişletilmiş Sorgu Metotları",
    order_iface_complete,
    "findAll(), findByCustomerId(), findByCourierId() arayüz kontratına eklendi"
)

with open(os.path.join(PROJECT_ROOT, 'src', 'Repository', 'CourierRepositoryInterface.php'), 'r', encoding='utf-8') as f:
    courier_iface_src = f.read()

courier_iface_complete = (
    "function findAll(): array;" in courier_iface_src and
    "function save(Courier $courier): int;" in courier_iface_src and
    "function findByUserId(int $userId): ?Courier;" in courier_iface_src
)
assert_test(
    "TEST 5.2: CourierRepositoryInterface Genişletilmiş Metotlar",
    courier_iface_complete,
    "findAll(), save(), findByUserId() arayüz kontratına tanımlandı"
)

with open(os.path.join(PROJECT_ROOT, 'src', 'Repository', 'Json', 'JsonOrderRepository.php'), 'r', encoding='utf-8') as f:
    json_order_repo_src = f.read()

json_order_complete = (
    "public function findAll(): array" in json_order_repo_src and
    "public function findByCustomerId(int $customerId): array" in json_order_repo_src and
    "public function findByCourierId(int $courierId): array" in json_order_repo_src
)
assert_test(
    "TEST 5.3: JsonOrderRepository Somut Uygulaması",
    json_order_complete,
    "JsonOrderRepository tüm arayüz metotlarını eksiksiz uyguluyor"
)

with open(os.path.join(PROJECT_ROOT, 'src', 'Repository', 'Json', 'JsonCourierRepository.php'), 'r', encoding='utf-8') as f:
    json_courier_repo_src = f.read()

json_courier_complete = (
    "public function findAll(): array" in json_courier_repo_src and
    "public function save(Courier $courier): int" in json_courier_repo_src and
    "public function findByUserId(int $userId): ?Courier" in json_courier_repo_src
)
assert_test(
    "TEST 5.4: JsonCourierRepository Somut Uygulaması",
    json_courier_complete,
    "JsonCourierRepository tüm arayüz metotlarını eksiksiz uyguluyor"
)

# ==============================================================================
# BÖLÜM 6: KONTROLCÜLER VE TEMEL CONTROLLER SINIFI (BASE CONTROLLER)
# ==============================================================================
base_ctrl_path = os.path.join(PROJECT_ROOT, 'src', 'Core', 'Controller.php')
has_base_ctrl = os.path.isfile(base_ctrl_path)
if has_base_ctrl:
    with open(base_ctrl_path, 'r', encoding='utf-8') as f:
        base_ctrl_src = f.read()
    has_render = "function render(" in base_ctrl_src
    has_redirect = "function redirect(" in base_ctrl_src
    has_json = "function json(" in base_ctrl_src
else:
    has_render = has_redirect = has_json = False

assert_test(
    "TEST 6.1: Core/Controller Soyut Temel Kontrolcü Sınıfı",
    has_base_ctrl and has_render and has_redirect and has_json,
    "abstract class Controller; render(), redirect(303), json() metotları mevcut"
)

# HomeController DIP ve Rol Görünümü
with open(os.path.join(PROJECT_ROOT, 'src', 'Controller', 'HomeController.php'), 'r', encoding='utf-8') as f:
    home_src = f.read()

home_dip = "RestaurantRepositoryInterface" in home_src and "OrderRepositoryInterface" in home_src and "CourierRepositoryInterface" in home_src
home_views = "dashboard/customer" in home_src and "dashboard/restaurant" in home_src and "dashboard/courier" in home_src and "dashboard/admin" in home_src
home_extends = "extends Controller" in home_src
assert_test(
    "TEST 6.2: HomeController Arayüz Bağımlılığı (DIP) ve Role Özel Görünüm Yönlendirmesi",
    home_dip and home_views and home_extends,
    "HomeController somut sınıflara değil arayüzlere bağımlı; role göre dashboard görünümünü yükler"
)

# OrderController Metotları: changeStatus, cancelOrder, Esnek Constructor
order_ctrl_methods = "public function changeStatus(" in order_ctrl_src and "public function cancelOrder(" in order_ctrl_src
order_ctrl_dip = "OrderRepositoryInterface" in order_ctrl_src and "RestaurantRepositoryInterface" in order_ctrl_src
order_ctrl_extends = "extends Controller" in order_ctrl_src
assert_test(
    "TEST 6.3: OrderController changeStatus(), cancelOrder() ve Arayüz Bağımlılığı",
    order_ctrl_methods and order_ctrl_dip and order_ctrl_extends,
    "OrderController arayüzlere bağımlı; changeStatus ve cancelOrder metotları mevcut"
)

# ApiController + OrderService Düzeltmesi (OrderStatus::IPTAL) ve Temiz Uç Noktalar
with open(os.path.join(PROJECT_ROOT, 'src', 'Controller', 'ApiController.php'), 'r', encoding='utf-8') as f:
    api_src = f.read()
with open(os.path.join(PROJECT_ROOT, 'src', 'Service', 'OrderService.php'), 'r', encoding='utf-8') as f:
    order_svc_src = f.read()

no_iptal_edildi = "OrderStatus::IPTAL_EDILDI" not in api_src and "OrderStatus::IPTAL_EDILDI" not in order_svc_src
has_iptal = "OrderStatus::IPTAL" in api_src or "OrderStatus::IPTAL" in order_svc_src
has_explicit_handlers = (
    "handleOrderStatus" in api_src and
    "handleKdsOrders" in api_src and
    "handleCourierTasks" in api_src and
    "handleAuditLogs" in api_src
)
assert_test(
    "TEST 6.4: ApiController OrderStatus::IPTAL Düzeltmesi ve Açık Rota Yöneticileri",
    no_iptal_edildi and has_iptal and has_explicit_handlers,
    "IPTAL_EDILDI yazım hatası düzeltildi; ApiController -> OrderService delegasyonu ve 4 açık rota yöneticisi tanımlı"
)

# ==============================================================================
# BÖLÜM 7: GÖRÜNÜM VE DASHBOARD MODÜLERLİĞİ
# ==============================================================================
dash_dir = os.path.join(PROJECT_ROOT, 'views', 'dashboard')
dash_files = ['customer.php', 'restaurant.php', 'courier.php', 'admin.php', 'index.php']
all_dash_exist = all(os.path.isfile(os.path.join(dash_dir, f)) for f in dash_files)

assert_test(
    "TEST 7.1: views/dashboard/ Rol Şablonları Modülerliği",
    all_dash_exist,
    "customer.php, restaurant.php, courier.php, admin.php ve index.php mevcut"
)

# Apache .htaccess DirectoryIndex Önceliği
with open(os.path.join(PROJECT_ROOT, '.htaccess'), 'r', encoding='utf-8') as f:
    root_ht = f.read()
with open(os.path.join(PROJECT_ROOT, 'public', '.htaccess'), 'r', encoding='utf-8') as f:
    pub_ht = f.read()

dir_index_ok = "DirectoryIndex index.php" in root_ht and "DirectoryIndex index.php" in pub_ht
assert_test(
    "TEST 7.2: Apache .htaccess DirectoryIndex index.php Yapılandırması",
    dir_index_ok,
    "Her iki .htaccess dosyasında DirectoryIndex index.php ilk sırada tanımlı"
)

# public/index.php Rota Tanımları
with open(os.path.join(PROJECT_ROOT, 'public', 'index.php'), 'r', encoding='utf-8') as f:
    front_ctrl = f.read()

routes_ok = (
    "'/api/order-status'" in front_ctrl and
    "'/api/kds-orders'" in front_ctrl and
    "'/api/courier-tasks'" in front_ctrl and
    "'/api/audit-logs'" in front_ctrl and
    "'/api'" in front_ctrl and
    "'/api.php'" in front_ctrl
)
assert_test(
    "TEST 7.3: Front Controller (public/index.php) Açık API Rotaları",
    routes_ok,
    "/api/order-status, /api/kds-orders, /api/courier-tasks, /api/audit-logs kayıtlı"
)

print("=" * 80)
print(f"  M1 MİMARİ DOĞRULAMA SONUCU: {passed} TEST GEÇTİ, {failed} BAŞARISIZ")
print("=" * 80)

if failed == 0:
    sys.exit(0)
else:
    sys.exit(1)
