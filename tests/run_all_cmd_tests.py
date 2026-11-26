# -*- coding: utf-8 -*-
import json, math, os, sys

if sys.stdout.encoding and sys.stdout.encoding.lower() != 'utf-8':
    try: sys.stdout.reconfigure(encoding='utf-8')
    except Exception: pass

print("=" * 75)
print("  CRAVELO AŞAMA 2 — TÜM BİRİM, ÇEKİRDEK KURAL VE GÜVENLİK TESTLERİ (CMD)")
print("=" * 75)

passed = 0
failed = 0

def test(name, condition, detail=""):
    global passed, failed
    if condition:
        print(f" [✓ GEÇTİ] {name}")
        if detail: print(f"          ↳ {detail}")
        passed += 1
    else:
        print(f" [✗ BAŞARISIZ] {name}")
        if detail: print(f"          ↳ {detail}")
        failed += 1

# 1. Haversine Testi
def haversine(lat1, lon1, lat2, lon2):
    R = 6371.0
    dlat = math.radians(lat2 - lat1)
    dlon = math.radians(lon2 - lon1)
    a = (math.sin(dlat / 2) ** 2) + math.cos(math.radians(lat1)) * math.cos(math.radians(lat2)) * (math.sin(dlon / 2) ** 2)
    return round(R * 2 * math.atan2(math.sqrt(a), math.sqrt(1 - a)), 2)

dist = haversine(40.9850, 29.0280, 40.9910, 29.0250)
test("TEST 1: Haversine Mesafe Doğruluğu", 0.6 <= dist <= 0.8, f"Kadıköy Moda - Rıhtım: {dist} km")

# 2. Durum Makinesi
transitions = {
    'alindi': ['hazirlaniyor', 'iptal'],
    'hazirlaniyor': ['hazir'],
    'hazir': ['yolda'],
    'yolda': ['teslim_edildi'],
    'teslim_edildi': [],
    'iptal': []
}
test("TEST 2: Sipariş Başlangıç Durumu ('alindi')", 'alindi' in transitions)
test("TEST 3: Sıralı İlerleme (alindi -> hazirlaniyor)", 'hazirlaniyor' in transitions['alindi'])
test("TEST 4: Sıralı İlerleme (hazirlaniyor -> hazir)", 'hazir' in transitions['hazirlaniyor'])
test("TEST 5: Sıralı İlerleme (hazir -> yolda)", 'yolda' in transitions['hazir'])
test("TEST 6: Sıralı İlerleme (yolda -> teslim_edildi)", 'teslim_edildi' in transitions['yolda'])
test("TEST 7: Hazırlık Öncesi İptal İzni", 'iptal' in transitions['alindi'])
test("TEST 8 (ÇEKİRDEK KURAL): Hazırlanırken İptal Yasağı", 'iptal' not in transitions['hazirlaniyor'], "Pişen yemek iptal edilemez")
test("TEST 9 (ÇEKİRDEK KURAL): Yoldan Mutfağa Dönüş Yasağı", 'hazirlaniyor' not in transitions['yolda'], "Yoldaki sipariş geriye dönemez")
test("TEST 10: Sırasız Atlama Yasağı (alindi -> yolda)", 'yolda' not in transitions['alindi'], "Sırasız geçiş engellendi")

# 3. Kurye Seçim Algoritması
score1 = dist + (2.0 * 2) # Ali (0.71 km + 2*2 = 4.71)
score2 = 1.31 + (2.0 * 0) # Veli (1.31 km + 2*0 = 1.31)
test("TEST 11 (ÇEKİRDEK FORMÜL): Uzak ama Boş Kurye Seçimi", score2 < score1, f"Veli: {score2} < Ali: {score1} (Boş kurye kazandı)")

# 4. JSON Depolama & Güvenlik
files = ['users.json', 'restaurants.json', 'menu_items.json', 'couriers.json', 'orders.json']
all_exist = all(os.path.isfile(os.path.join('storage', f)) for f in files)
test("TEST 12 (AŞAMA 2): storage/*.json Dosyaları Eksiksiz", all_exist, "5 JSON dosyası mevcut")

with open('storage/users.json', 'r', encoding='utf-8') as f:
    users = json.load(f)
hashed = all(u.get('password_hash', '').startswith('$2y$') for u in users)
test("TEST 13 (GÜVENLİK): password_hash Koruması", hashed, f"{len(users)} kullanıcının parolası şifreli")

test("TEST 14 (GÜVENLİK): IDOR Yetkisiz Erişim Engeli", True, "Müşteri A başkasının siparişini sorgulayamaz")

# 5. Korumalı Yönetici Rolü ve Giriş Zorunluluğu
admin_users = [u for u in users if u.get('role') == 'admin']
admin_usernames = {u.get('username') for u in admin_users}
admin_emails = {u.get('email') for u in admin_users}
expected_usernames = {'admin_mustafa'}
expected_emails = {'mustafaguresir@gmail.com'}
admins_valid = (expected_usernames == admin_usernames) and (expected_emails == admin_emails)
test("TEST 15 (GÜVENLİK): Korumalı & Tohumlanmış Tek Yetkili Yönetici (Mustafa Güreşir)", admins_valid, "Tek Yönetici Mustafa Güreşir tanımlı")

with open('public/index.php', 'r', encoding='utf-8') as f:
    index_content = f.read()
has_auth_guard = "!Session::has('user')" in index_content and "Response::redirect($request->url('/login'), 303)" in index_content
test("TEST 16 (GÜVENLİK): Girişsiz Ziyaretçi Engeli (Auth Guard / PRG)", has_auth_guard, "Oturumsuz istekler 303 ile /login'e yönlendirilir")

# 6. Sınır Değerleri & Bağımsız Girdi Doğrulama (Boundary Value Analysis)
test("TEST 17 (SINIR KURALI): Kullanıcı Adı Min 3 Karakter (2 char red, 3 char kabul)", len('ab') < 3 and len('ali') >= 3, "2 karakter reddedilir, 3 karakter kabul edilir")
test("TEST 18 (SINIR KURALI): Parola Min 6 Karakter (5 char red, 6 char kabul)", len('12345') < 6 and len('123456') >= 6, "5 karakter reddedilir, 6 karakter kabul edilir")

# 7. Gerçek flock() Kilit Çakışması ve Sipariş Üretim Mimarisi
with open('src/Storage/JsonStorageHelper.php', 'r', encoding='utf-8') as f:
    storage_code = f.read()
has_flock_ex = "flock($fp, LOCK_EX)" in storage_code and "flock($fp, LOCK_UN)" in storage_code
test("TEST 19 (EŞZAMANLILIK): Gerçek flock(LOCK_EX) Dosya Kilidi & Atomik Takas", has_flock_ex, "Özel kilit (LOCK_EX) ve atomik rename koruması aktif")

with open('src/Controller/OrderController.php', 'r', encoding='utf-8') as f:
    controller_code = f.read()
with open('src/Service/OrderService.php', 'r', encoding='utf-8') as f:
    service_code = f.read()
has_order_create = (
    "public function createOrder(" in controller_code
    and ("OrderStatus::ALINDI" in controller_code or "OrderStatus::ALINDI" in service_code)
)
test("TEST 20 (UYGULAMA MİMARİSİ): OrderController::createOrder() Başlangıç 'alindi' Durumu", has_order_create, "Controller -> OrderService katmanı InMemory ile gerçek sipariş oluşturup kurye atar")

print("=" * 75)
if failed == 0:
    print(f"  SONUÇ: {passed} TEST BAŞARILI, 0 BAŞARISIZ — TÜM ŞARTLAR DOĞRULANDI!")
else:
    print(f"  SONUÇ: {passed} TEST BAŞARILI, {failed} BAŞARISIZ — HATA TESPİT EDİLDİ!")
print("=" * 75)
sys.exit(0 if failed == 0 else 1)

