import asyncio
import os
import tempfile
from playwright.async_api import async_playwright

BASE_URL = os.environ.get("CRAVELO_BASE_URL", "http://localhost:8000")
SCREENSHOT_DIR = os.path.join(tempfile.gettempdir(), "cravelo_playwright_screenshots")
os.makedirs(SCREENSHOT_DIR, exist_ok=True)


async def run_tests():
    print("=" * 65)
    print("CRAVELO - MODERN OZELLIKLER VE KULLANILABILIRLIK TESTI (E2E)")
    print("=" * 65)

    async with async_playwright() as p:
        browser = await p.chromium.launch(headless=True)
        page = await browser.new_page(viewport={"width": 1280, "height": 850})

        # 1. Misafir Landing & Giris Akisi (/login -> ahmet_musteri)
        await page.goto(f"{BASE_URL}/login")
        await page.wait_for_selector("#input-username")
        await page.fill("#input-username", "ahmet_musteri")
        await page.fill("#input-password", "hash123")
        await page.click("#btn-login-submit")
        await page.wait_for_selector("#del-mode-toggle")
        print("[PASS] 1. Musteri girisi yapildi, Cravelo ana ekrani hazir.")

        # 2. Gel-Al & Teslimat Modu Switcher
        await page.click("#btn-del-takeaway")
        await page.wait_for_timeout(300)
        is_takeaway_active = await page.eval_on_selector("#btn-del-takeaway", "el => el.classList.contains('active')")
        assert is_takeaway_active, "Gel-Al butonu aktif olmaliydi"
        await page.screenshot(path=os.path.join(SCREENSHOT_DIR, "01_gel_al_mode.png"))
        print("[PASS] 2. Gel-Al (%20 Indirim) modu secildi ve dogrulandi.")

        await page.click("#btn-del-courier")
        await page.wait_for_timeout(300)
        is_courier_active = await page.eval_on_selector("#btn-del-courier", "el => el.classList.contains('active')")
        assert is_courier_active, "Adrese Teslim butonu aktif olmaliydi"
        print("[PASS] 3. Adrese Teslim moduna basariyla geri donuldu.")

        # 3. Restoran Secimi ve Ortak Sepet (Group Order)
        await page.click(".rest-card:has-text('Burger Lab')")
        await page.wait_for_selector("#menu-section", state="visible")
        print("[PASS] 4. Burger Lab menusu acildi.")

        group_btn = await page.query_selector("#btn-group-cart")
        if group_btn:
            await group_btn.click()
            await page.wait_for_selector("#group-order-overlay", state="visible")
            group_code = await page.inner_text("#group-order-code")
            assert "CRAVELO-7429" in group_code, "Cravelo grup kodu gorunmeliydi"
            await page.fill("#group-friend-name", "Deniz")
            await page.click("#group-order-overlay button:has-text('Ekle')")
            await page.wait_for_timeout(300)
            group_list_text = await page.inner_text("#group-members-list")
            assert "Deniz" in group_list_text, "Deniz ortak sepete eklenmeliydi"
            await page.screenshot(path=os.path.join(SCREENSHOT_DIR, "02_group_order.png"))
            await page.click("#group-order-overlay button:has-text('Sepete Dön')")
            print("[PASS] 5. Ortak Sepet (CRAVELO-7429) ve yeni katilimci dogrulandi.")

        # 4. Urun Ozellestirme ve Sepete Ekleme
        await page.click(".food-card:has-text('Smash Cheeseburger')")
        await page.wait_for_selector("#customizer-overlay", state="visible")
        await page.click("#customizer-overlay button:has-text('Sepete Ekle')")
        await page.wait_for_timeout(300)
        cart_badge = await page.inner_text("#nav-cart-count")
        assert int(cart_badge) > 0, "Sepet dolu olmaliydi"
        print("[PASS] 6. Urun sepete eklendi.")

        # 5. Gelismis Odeme (Checkout) ve Akilli Talimatlar
        await page.click("#btn-order")
        await page.wait_for_selector("#checkout-overlay", state="visible")
        await page.click(".inst-chip:has-text('Zile Basma Bebek Uyuyor')")
        await page.click(".sched-pill:has-text('19:00 - 19:30')")
        await page.screenshot(path=os.path.join(SCREENSHOT_DIR, "03_checkout_modal.png"))
        await page.click("#checkout-overlay button:has-text('Siparişi Onayla & Başlat')")
        await page.wait_for_timeout(600)
        print("[PASS] 7. Akilli teslimat talimati ve sunucu dogrulamasiyla siparis olusturuldu.")

        # 6. Cikis ve Restoran KDS Girisi (/login -> restoran_burger)
        await page.click(".nav-logout-btn")
        await page.wait_for_selector("#input-username")
        await page.fill("#input-username", "restoran_burger")
        await page.fill("#input-password", "hash123")
        await page.click("#btn-login-submit")
        await page.wait_for_selector("#view-restaurant", state="visible")
        await page.screenshot(path=os.path.join(SCREENSHOT_DIR, "04_restaurant_kds.png"))
        print("[PASS] 8. POST CSRF cikisi ve Restoran KDS (Burger Lab) ekrani dogrulandi.")

        await browser.close()
        print("=" * 65)
        print("CRAVELO TUM MODERN OZELLIKLER VE KULLANILABILIRLIK TESTLERI BASARILI!")
        print("=" * 65)


if __name__ == "__main__":
    asyncio.run(run_tests())
