</main>

<!-- ======================================================== -->
<!-- CRAVELO ETKİLEŞİMLİ MODALLAR (8 MODERN SHEET)            -->
<!-- ======================================================== -->

<!-- 1. Bağımsız Sepet Modalı -->
<div class="modal-overlay" id="cart-overlay" role="dialog" aria-modal="true" aria-labelledby="cart-modal-title">
    <div class="modal-box" style="max-width:540px;">
        <button type="button" class="modal-close" onclick="closeCartModal()" title="Kapat" aria-label="Sepeti Kapat">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div class="modal-header modal-header-with-close">
            <div style="display:flex;align-items:center;gap:14px;">
                <div style="width:44px;height:44px;border-radius:14px;background:var(--primary-light);color:var(--primary);display:flex;align-items:center;justify-content:center;font-size:19px;flex-shrink:0;">
                    <i class="fa-solid fa-bag-shopping"></i>
                </div>
                <div>
                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                        <h2 id="cart-modal-title" style="font-size:20px;font-weight:900;color:var(--text);margin:0;">Sepetim</h2>
                        <span class="badge badge-musait" id="modal-cart-badge" style="font-size:11px;">0 Ürün</span>
                    </div>
                    <div style="font-size:12.5px;color:var(--text-3);margin-top:2px;" id="modal-cart-from">—</div>
                </div>
            </div>
        </div>

        <div class="modal-body">
            <!-- Empty Cart State -->
            <div id="modal-cart-empty" style="text-align:center;padding:40px 18px;display:none;">
                <div style="width:74px;height:74px;border-radius:22px;background:var(--surface-2);border:1px solid var(--border);display:inline-flex;align-items:center;justify-content:center;color:var(--text-3);font-size:30px;margin-bottom:16px;">
                    <i class="fa-solid fa-cart-arrow-down"></i>
                </div>
                <h3 style="font-size:17px;font-weight:900;color:var(--text);margin-bottom:6px;">Sepetiniz henüz boş</h3>
                <p style="font-size:13.5px;color:var(--text-2);margin-bottom:20px;line-height:1.5;">Kadıköy'ün en sevilen restoranlarından sıcak lezzetleri seçip sepetinize ekleyin.</p>
                <button type="button" class="btn btn-primary" onclick="closeCartModal(); scrollToRestaurants();" style="border-radius:9999px;padding:11px 26px;">
                    <i class="fa-solid fa-utensils"></i> Lezzetleri Keşfet
                </button>
            </div>

            <!-- Populated Cart Content -->
            <div id="modal-cart-content">
                <div class="cart-items" id="modal-cart-items" style="max-height:280px;overflow-y:auto;padding-right:4px;"></div>

                <div class="cart-summary" style="margin-top:16px;">
                    <div class="summ-row">
                        <span>Ürünler Toplamı</span>
                        <strong id="m-sub" style="color:var(--text);">0.00 ₺</strong>
                    </div>
                    <div class="summ-row discount" id="m-discount-row" style="display:none;">
                        <span>İndirim Tutarı</span>
                        <strong id="m-disc">-0.00 ₺</strong>
                    </div>
                    <div class="summ-row">
                        <span>Teslimat Ücreti</span>
                        <strong id="m-del" style="color:var(--text);">0.00 ₺</strong>
                    </div>
                    <div class="summ-row total">
                        <span>Genel Toplam</span>
                        <strong id="m-total" style="color:var(--primary);">0.00 ₺</strong>
                    </div>
                </div>

                <div class="modal-footer" style="display:flex;gap:10px;margin-top:18px;">
                    <button type="button" class="btn btn-outline-danger" onclick="clearCartConfirm()" style="flex:1;border-radius:9999px;padding:12px 16px;">
                        <i class="fa-solid fa-trash-can"></i> Temizle
                    </button>
                    <button type="button" class="btn btn-primary" onclick="closeCartModal(); openCheckoutModal();" style="flex:2;border-radius:9999px;padding:12px 22px;font-size:14.5px;">
                        <i class="fa-solid fa-credit-card"></i> Siparişi Tamamla
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 2. Kategoriye Duyarlı Akıllı Ürün Özelleştirme Modalı -->
<div class="modal-overlay" id="customizer-overlay" role="dialog" aria-modal="true" aria-labelledby="cust-name">
    <div class="modal-box">
        <button type="button" class="modal-close" onclick="closeCustomizer()" title="Kapat" aria-label="Özelleştiriciyi Kapat">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div class="modal-header modal-header-with-close" style="display:flex;gap:16px;align-items:center;">
            <img id="cust-img" src="" alt="Ürün Görseli" style="width:88px;height:88px;border-radius:18px;object-fit:cover;flex-shrink:0;border:1px solid var(--border);">
            <div style="min-width:0;">
                <h3 id="cust-name" style="font-size:20px;font-weight:900;color:var(--text);margin:0 0 4px 0;letter-spacing:-0.3px;">Yemek Adı</h3>
                <p id="cust-desc" style="font-size:13px;color:var(--text-2);line-height:1.45;margin:0;">Açıklama</p>
                <div id="cust-base-price" style="font-size:19px;font-weight:900;color:var(--primary);margin-top:8px;">0.00 ₺</div>
            </div>
        </div>

        <div class="modal-body">
            <!-- Dinamik ve Kategoriye Özel Özelleştirme Alanı (Tatlı, Burger, Kebap, Pizza, İçecek) -->
            <div id="cust-dynamic-options"></div>

            <div class="form-group" style="margin-top:14px;">
                <label class="form-label" for="cust-note">Mutfak Şefine Not</label>
                <input type="text" class="form-input" id="cust-note" placeholder="Örn: Sosu ayrı kapta olsun, çıtır pişsin…">
            </div>
        </div>

        <div class="modal-footer" style="display:flex;gap:14px;align-items:center;margin-top:20px;">
            <div class="qty-ctrl" style="background:var(--surface-2);border:1.5px solid var(--border);border-radius:14px;padding:6px 12px;">
                <button type="button" class="qty-btn" onclick="changeCustomQty(-1)" aria-label="Azalt">−</button>
                <span class="qty-val" id="cust-qty" style="font-size:16px;min-width:24px;">1</span>
                <button type="button" class="qty-btn" onclick="changeCustomQty(1)" aria-label="Artır">+</button>
            </div>
            <button type="button" class="btn btn-primary" style="flex:1;padding:14px 20px;font-size:15px;border-radius:14px;" onclick="addCustomizedItemToCart()">
                <i class="fa-solid fa-plus"></i> <span id="btn-cust-text">Sepete Ekle • 0.00 ₺</span>
            </button>
        </div>
    </div>
</div>

<!-- 3. Gelişmiş Ödeme & Checkout Modalı -->
<div class="modal-overlay" id="checkout-overlay" role="dialog" aria-modal="true" aria-labelledby="checkout-modal-title">
    <div class="modal-box modal-box-wide">
        <button type="button" class="modal-close" onclick="closeCheckoutModal()" title="Kapat" aria-label="Ödemeyi Kapat">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div class="modal-header modal-header-with-close">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="width:44px;height:44px;border-radius:14px;background:var(--primary-light);color:var(--primary);display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <div>
                    <h2 id="checkout-modal-title" style="font-size:21px;font-weight:900;color:var(--text);margin:0;">Siparişi Onayla & Öde</h2>
                    <p style="font-size:13px;color:var(--text-2);margin:3px 0 0 0;">Ödeme yönteminizi, kurye bahşişini ve teslimat talimatlarınızı seçin.</p>
                </div>
            </div>
        </div>

        <div class="modal-body">
            <!-- Delivery Mode Notice -->
            <div id="checkout-del-mode-notice" class="del-mode-notice" style="display:none;">
                <i class="fa-solid fa-person-walking" style="font-size:19px;"></i>
                <div>
                    <strong>Gel-Al Siparişi:</strong> Siparişiniz restorandan sizin tarafınızdan teslim alınacak.
                    <div style="font-size:12px;opacity:0.9;margin-top:2px;">Teslimat ücreti 0 ₺ • %20 Gel-Al İndirimi Otomatik Uygulandı!</div>
                </div>
            </div>

            <div class="custom-sec-title"><span>1. Ödeme Yöntemi Seçin</span></div>
            <div class="pay-method-cards">
                <div class="pay-card active" id="pay-card-cc" onclick="selectPayMethod('cc')" role="button" tabindex="0">
                    <i class="fa-solid fa-credit-card"></i>
                    <span>Kredi / Banka Kartı</span>
                </div>
                <div class="pay-card" id="pay-card-cod" onclick="selectPayMethod('cod')" role="button" tabindex="0">
                    <i class="fa-solid fa-money-bill-wave"></i>
                    <span>Kapıda Nakit / POS</span>
                </div>
                <div class="pay-card" id="pay-card-wallet" onclick="selectPayMethod('wallet')" role="button" tabindex="0">
                    <i class="fa-solid fa-wallet"></i>
                    <span>Cravelo Cüzdan</span>
                </div>
            </div>

            <!-- Kredi Kartı Formu -->
            <div id="cc-form-area" style="background:var(--surface-2);border:1px solid var(--border);border-radius:16px;padding:16px;margin-bottom:16px;">
                <div class="form-group">
                    <label class="form-label">Kart Numarası</label>
                    <input type="text" class="form-input" placeholder="4543 •••• •••• 9821" value="4543 8920 1124 9821">
                </div>
                <div class="form-row">
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label">Son Kullanma</label>
                        <input type="text" class="form-input" placeholder="AA/YY" value="11/28">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label">CVV Güvenlik</label>
                        <input type="text" class="form-input" placeholder="•••" value="384">
                    </div>
                </div>
            </div>

            <div class="custom-sec-title"><span>2. Kuryeye Bahşiş Ekle</span> <span style="font-size:11.5px;color:var(--text-3);font-weight:600;">%100 kuryeye aktarılır</span></div>
            <div class="tip-pills">
                <div class="tip-pill active" id="tip-0" onclick="setTip(0)">Yok (0 ₺)</div>
                <div class="tip-pill" id="tip-10" onclick="setTip(10)">+10 ₺</div>
                <div class="tip-pill" id="tip-20" onclick="setTip(20)">+20 ₺ ⭐</div>
                <div class="tip-pill" id="tip-50" onclick="setTip(50)">+50 ₺ 🚀</div>
            </div>

            <div class="custom-sec-title"><span>3. Teslimat Zamanı</span></div>
            <div class="sched-pills" id="sched-pills">
                <div class="sched-pill active" onclick="setScheduledTime('now', this)"><i class="fa-solid fa-bolt"></i> Hemen Gelsin (~25 dk)</div>
                <div class="sched-pill" onclick="setScheduledTime('19:00 - 19:30', this)"><i class="fa-regular fa-clock"></i> 19:00 - 19:30</div>
                <div class="sched-pill" onclick="setScheduledTime('20:00 - 20:30', this)"><i class="fa-regular fa-clock"></i> 20:00 - 20:30</div>
                <div class="sched-pill" onclick="setScheduledTime('21:00 - 21:30', this)"><i class="fa-regular fa-clock"></i> 21:00 - 21:30</div>
            </div>

            <div class="custom-sec-title"><span>4. Akıllı Teslimat Talimatları & Özel Not</span></div>
            <div class="instruction-chips" id="instruction-chips">
                <div class="inst-chip active" onclick="selectInstruction('Zile Basma Bebek Uyuyor 👶', this)">
                    <i class="fa-solid fa-baby"></i> Zile Basma Bebek Uyuyor
                </div>
                <div class="inst-chip" onclick="selectInstruction('Kapıya As & Zile Bas 🚪', this)">
                    <i class="fa-solid fa-door-open"></i> Kapıya As & Zile Bas
                </div>
                <div class="inst-chip" onclick="selectInstruction('Güvenliğe Bırak 🛡️', this)">
                    <i class="fa-solid fa-shield"></i> Güvenliğe Bırak
                </div>
                <div class="inst-chip" onclick="selectInstruction('Gelince Ara / Kapıdayım De 📞', this)">
                    <i class="fa-solid fa-phone"></i> Gelince Ara
                </div>
            </div>
            <input type="text" class="form-input" id="delivery-custom-note" placeholder="Kurye için ek adres veya kapı detayı (örn: Kat 4, Daire 8, Zil: Yılmaz)…" style="margin-top:10px;">

            <label class="checkbox-row" style="margin-top:14px;">
                <span><i class="fa-solid fa-leaf" style="color:var(--success);margin-right:6px;"></i> Doğayı Koru: Plastik çatal, kaşık ve peçete istemiyorum (-140g CO2)</span>
                <input type="checkbox" id="eco-choice" checked onchange="toggleEcoFriendly(this.checked)">
            </label>
        </div>

        <div class="modal-footer" style="background:var(--surface-2);border:1px solid var(--border);border-radius:18px;padding:16px 20px;margin-top:18px;display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap;">
            <div>
                <div style="font-size:12px;font-weight:700;color:var(--text-3);">Toplam Ödenecek Tutar</div>
                <div style="font-size:24px;font-weight:900;color:var(--primary);letter-spacing:-0.4px;" id="checkout-final-total">0.00 ₺</div>
            </div>
            <button type="button" class="btn btn-primary" style="padding:14px 28px;font-size:15px;border-radius:14px;" onclick="confirmCheckoutAndPlaceOrder()">
                <i class="fa-solid fa-lock"></i> Siparişi Onayla & Başlat
            </button>
        </div>
    </div>
</div>

<!-- 4. Değerlendirme & Yorum Modalı (3 Kırılımlı: Lezzet, Hız, Servis) -->
<div class="modal-overlay" id="rating-overlay" role="dialog" aria-modal="true" aria-labelledby="rating-modal-title">
    <div class="modal-box" style="text-align:center;max-width:520px;">
        <button type="button" class="modal-close" onclick="closeRatingModal()" title="Kapat" aria-label="Değerlendirmeyi Kapat">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div style="width:62px;height:62px;border-radius:20px;background:var(--primary-light);color:var(--primary);display:inline-flex;align-items:center;justify-content:center;font-size:28px;margin-bottom:12px;">
            <i class="fa-solid fa-award"></i>
        </div>
        <h2 id="rating-modal-title" style="font-size:22px;font-weight:900;color:var(--text);margin:0 0 6px 0;">Siparişiniz Teslim Edildi!</h2>
        <p style="font-size:13.5px;color:var(--text-2);margin:0 0 20px 0;">Deneyiminizi puanlayarak <strong style="color:var(--primary);">+25 Cravelo Puan</strong> kazanın!</p>

        <!-- 3 Kırılımlı Puanlama -->
        <div class="rating-categories-card">
            <div class="rating-cat-row">
                <span class="rating-cat-label"><i class="fa-solid fa-pizza-slice" style="color:var(--primary);"></i> Lezzet Puanı</span>
                <div class="stars-row" id="taste-stars">
                    <i class="fa-solid fa-star active" onclick="setStars('taste', 1)"></i>
                    <i class="fa-solid fa-star active" onclick="setStars('taste', 2)"></i>
                    <i class="fa-solid fa-star active" onclick="setStars('taste', 3)"></i>
                    <i class="fa-solid fa-star active" onclick="setStars('taste', 4)"></i>
                    <i class="fa-solid fa-star active" onclick="setStars('taste', 5)"></i>
                </div>
            </div>
            <div class="rating-cat-row">
                <span class="rating-cat-label"><i class="fa-solid fa-bolt" style="color:var(--blue);"></i> Teslimat Hızı</span>
                <div class="stars-row" id="speed-stars">
                    <i class="fa-solid fa-star active" onclick="setStars('speed', 1)"></i>
                    <i class="fa-solid fa-star active" onclick="setStars('speed', 2)"></i>
                    <i class="fa-solid fa-star active" onclick="setStars('speed', 3)"></i>
                    <i class="fa-solid fa-star active" onclick="setStars('speed', 4)"></i>
                    <i class="fa-solid fa-star active" onclick="setStars('speed', 5)"></i>
                </div>
            </div>
            <div class="rating-cat-row">
                <span class="rating-cat-label"><i class="fa-solid fa-box-open" style="color:var(--success);"></i> Paket & Servis</span>
                <div class="stars-row" id="service-stars">
                    <i class="fa-solid fa-star active" onclick="setStars('service', 1)"></i>
                    <i class="fa-solid fa-star active" onclick="setStars('service', 2)"></i>
                    <i class="fa-solid fa-star active" onclick="setStars('service', 3)"></i>
                    <i class="fa-solid fa-star active" onclick="setStars('service', 4)"></i>
                    <i class="fa-solid fa-star active" onclick="setStars('service', 5)"></i>
                </div>
            </div>
        </div>

        <!-- Değerlendirme Etiketleri -->
        <div style="font-size:12.5px;font-weight:800;color:var(--text-2);text-align:left;margin:16px 0 8px 0;">Öne Çıkan Özellikler:</div>
        <div class="rating-tag-chips" id="rating-tags-wrap">
            <span class="rating-tag-chip active" onclick="toggleRatingTag(this)"><i class="fa-solid fa-fire"></i> Sıcacık Geldi</span>
            <span class="rating-tag-chip active" onclick="toggleRatingTag(this)"><i class="fa-solid fa-bolt"></i> Süper Hızlı</span>
            <span class="rating-tag-chip" onclick="toggleRatingTag(this)"><i class="fa-solid fa-box"></i> Özenli Paket</span>
            <span class="rating-tag-chip" onclick="toggleRatingTag(this)"><i class="fa-solid fa-smile"></i> Kurye Çok Kibardı</span>
            <span class="rating-tag-chip" onclick="toggleRatingTag(this)"><i class="fa-solid fa-burger"></i> Doyurucu Porsiyon</span>
            <span class="rating-tag-chip" onclick="toggleRatingTag(this)"><i class="fa-solid fa-leaf"></i> Doğa Dostu Paket</span>
        </div>

        <div class="form-group" style="margin-top:16px;">
            <input type="text" class="form-input" id="review-comment" placeholder="Yemeğin lezzeti ve teslimat hakkında yorumunuz…" value="Çok sıcak ve taze geldi, kurye arkadaşa da teşekkürler!">
        </div>

        <button type="button" class="btn btn-primary" style="width:100%;padding:14px;border-radius:14px;font-size:14.5px;" onclick="submitRatingAndReward()">
            <i class="fa-solid fa-award"></i> Değerlendirmeyi Gönder (+25 Cravelo Puan)
        </button>
    </div>
</div>

<!-- 5. Ortak Sepet / Grup Siparişi Modalı -->
<div class="modal-overlay" id="group-order-overlay" role="dialog" aria-modal="true" aria-labelledby="group-modal-title">
    <div class="modal-box" style="max-width:520px;">
        <button type="button" class="modal-close" onclick="closeGroupOrderModal()" title="Kapat" aria-label="Grup Siparişini Kapat">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div class="modal-header modal-header-with-close">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="width:44px;height:44px;border-radius:14px;background:var(--blue-light);color:var(--blue);display:flex;align-items:center;justify-content:center;font-size:19px;flex-shrink:0;">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div>
                    <h2 id="group-modal-title" style="font-size:20px;font-weight:900;color:var(--text);margin:0;">Ortak Sepet & Grup Siparişi</h2>
                    <p style="font-size:12.5px;color:var(--text-2);margin:3px 0 0 0;">Ekibinizi davet edin, herkes kendi yemeğini seçsin, tek teslimatta gelsin!</p>
                </div>
            </div>
        </div>

        <div class="modal-body">
            <!-- Group Code & Share Link -->
            <div class="group-share-box">
                <div>
                    <div style="font-size:11px;color:var(--text-3);text-transform:uppercase;font-weight:800;letter-spacing:0.5px;">Cravelo Grup Kodu</div>
                    <div style="font-size:20px;font-weight:900;color:var(--primary);letter-spacing:1px;margin-top:2px;" id="group-order-code">CRAVELO-7429</div>
                </div>
                <button type="button" class="btn btn-sm btn-outline" onclick="copyGroupOrderLink()" style="border-radius:9999px;">
                    <i class="fa-solid fa-copy"></i> Linki Kopyala
                </button>
            </div>

            <!-- Participants List -->
            <div style="font-size:12px;font-weight:800;color:var(--text-3);text-transform:uppercase;letter-spacing:0.4px;margin:18px 0 10px 0;">Gruptaki Katılımcılar ve Seçimleri</div>
            <div class="group-members-list" id="group-members-list"></div>

            <!-- Add Simulated Friend -->
            <div style="display:flex;gap:8px;margin-top:14px;">
                <input type="text" class="form-input" id="group-friend-name" placeholder="Arkadaş ekle (örn: Selin, Kerem)…" style="font-size:13.5px;">
                <button type="button" class="btn btn-outline" onclick="addGroupSimulatedMember()" style="white-space:nowrap;">
                    <i class="fa-solid fa-user-plus"></i> Ekle
                </button>
            </div>
        </div>

        <div class="modal-footer" style="margin-top:18px;">
            <button type="button" class="btn btn-primary" style="width:100%;padding:14px;border-radius:14px;" onclick="closeGroupOrderModal()">
                <i class="fa-solid fa-check"></i> Sepete Dön & Siparişi Birleştir
            </button>
        </div>
    </div>
</div>

<!-- 6. Kurye Canlı Mesajlaşma Modalı -->
<div class="modal-overlay" id="chat-overlay" role="dialog" aria-modal="true" aria-labelledby="chat-courier-name">
    <div class="modal-box" style="max-width:520px;">
        <button type="button" class="modal-close" onclick="closeChatModal()" title="Kapat" aria-label="Sohbeti Kapat">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div class="modal-header modal-header-with-close" style="display:flex;align-items:center;gap:12px;">
            <div style="width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg, var(--blue), #60A5FA);display:flex;align-items:center;justify-content:center;color:#fff;font-size:18px;box-shadow:0 4px 14px var(--blue-light);flex-shrink:0;">
                <i class="fa-solid fa-motorcycle"></i>
            </div>
            <div>
                <div style="font-size:16.5px;font-weight:900;color:var(--text);" id="chat-courier-name">Ali Kaya (Kurye 🛵)</div>
                <div style="font-size:12px;color:var(--success);font-weight:700;display:flex;align-items:center;gap:6px;margin-top:2px;">
                    <span class="live-pulse-dot"></span> Çevrimiçi • Teslimat yolunda
                </div>
            </div>
        </div>

        <div class="modal-body">
            <div class="chat-body" id="chat-messages">
                <div class="chat-msg courier">Merhaba! Siparişinizi restorandan aldım, kapınıza doğru yola çıktım. Özel bir isteğiniz var mı?</div>
            </div>

            <div class="chat-quick-replies">
                <span class="chat-pill" onclick="sendQuickChat('Zil bozuk lütfen arayın 📞')">Zil bozuk lütfen arayın 📞</span>
                <span class="chat-pill" onclick="sendQuickChat('Kapıya bırakabilirsiniz 🚪')">Kapıya bırakabilirsiniz 🚪</span>
                <span class="chat-pill" onclick="sendQuickChat('Kolay gelsin teşekkürler 👍')">Kolay gelsin teşekkürler 👍</span>
            </div>

            <div style="display:flex;gap:8px;">
                <input type="text" class="form-input" id="chat-input" placeholder="Kuryeye mesaj yazın…" onkeypress="if(event.key==='Enter') sendChatMessage()">
                <button type="button" class="btn btn-primary" onclick="sendChatMessage()" style="padding:0 20px;border-radius:12px;" aria-label="Gönder">
                    <i class="fa-solid fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 7. Google Haritalar Adres & Konum Seçici Modalı -->
<div class="modal-overlay" id="address-overlay" role="dialog" aria-modal="true" aria-labelledby="address-modal-title">
    <div class="modal-box modal-box-wide">
        <button type="button" class="modal-close" onclick="closeAddressModal()" title="Kapat" aria-label="Adres Modalını Kapat">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div class="modal-header modal-header-with-close">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="width:44px;height:44px;border-radius:14px;background:var(--primary-light);color:var(--primary);display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;">
                    <i class="fa-solid fa-location-dot"></i>
                </div>
                <div>
                    <h2 id="address-modal-title" style="font-size:20px;font-weight:900;color:var(--text);margin:0;">Teslimat Konumu ve Adreslerim</h2>
                    <p style="font-size:12.5px;color:var(--text-2);margin:3px 0 0 0;">Kurye teslimat süresi ve yakın restoranlar seçili Kadıköy adresine göre belirlenir.</p>
                </div>
            </div>
        </div>

        <div class="modal-body">
            <div style="font-size:11.5px;font-weight:800;color:var(--text-3);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:10px;">Kayıtlı Adresleriniz</div>
            <div id="saved-address-list"></div>

            <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:20px;padding:20px;margin-top:18px;">
                <div style="font-size:14px;font-weight:900;color:var(--text);margin-bottom:14px;display:flex;align-items:center;gap:8px;">
                    <i class="fa-solid fa-circle-plus" style="color:var(--primary);"></i>
                    <span>Yeni Teslimat Adresi Ekle</span>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="new-addr-title">Adres Başlığı</label>
                        <input type="text" class="form-input" id="new-addr-title" placeholder="Örn: Evim / Ofis">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="new-addr-desc">Açık Adres / Cadde & Mahalle</label>
                        <input type="text" class="form-input" id="new-addr-desc" placeholder="Örn: Moda Cad. No:14, Kadıköy">
                    </div>
                </div>

                <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:10px;font-size:12.5px;color:var(--text-2);">
                    <span><i class="fa-solid fa-map-location-dot" style="color:var(--primary);margin-right:4px;"></i> Haritadan teslimat pinini sürükleyin veya tıklayın:</span>
                    <span class="street-badge" id="picker-coord-text">Kadıköy Moda, İstanbul</span>
                </div>

                <div id="address-picker-map-wrap" class="map-frame" style="height:210px;">
                    <div id="address-picker-leaflet" style="width:100%;height:100%;z-index:2;"></div>
                    <canvas id="address-picker-canvas" width="500" height="210" style="position:absolute;top:0;left:0;width:100%;height:100%;cursor:crosshair;background:var(--surface);z-index:1;display:none;" onclick="GustoMap.handlePickerMapClick(event)"></canvas>
                </div>

                <!-- Internal grid sync fields kept hidden from UI for test & map sync compatibility -->
                <div style="display:none;" aria-hidden="true">
                    <input type="number" id="picker-x" value="18" min="0" max="35" onchange="syncPickerFromInputs()">
                    <input type="number" id="picker-y" value="14" min="0" max="35" onchange="syncPickerFromInputs()">
                </div>

                <button type="button" class="btn btn-primary" style="width:100%;padding:13px;border-radius:14px;margin-top:14px;" onclick="addNewAddress()">
                    <i class="fa-solid fa-check"></i> Bu Adresi Kaydet ve Aktif Et
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 8. Profil, Cravelo Cüzdan & Canlı 5-Adımlı Sipariş Takip Modalı -->
<div class="modal-overlay" id="account-overlay" role="dialog" aria-modal="true" aria-labelledby="acc-header-name">
    <div class="modal-box modal-box-wide">
        <button type="button" class="modal-close" onclick="closeAccountModal()" title="Kapat" aria-label="Hesap Modalını Kapat">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div class="modal-header modal-header-with-close" style="display:flex;align-items:center;gap:14px;">
            <div class="user-avatar" style="width:52px;height:52px;font-size:21px;flex-shrink:0;">
                <i class="fa-solid fa-user-gear"></i>
            </div>
            <div style="min-width:0;">
                <h2 style="font-size:20px;font-weight:900;color:var(--text);margin:0;" id="acc-header-name">Ahmet Yılmaz</h2>
                <div style="font-size:12.5px;color:var(--text-2);display:flex;gap:14px;margin-top:4px;flex-wrap:wrap;align-items:center;">
                    <span style="color:var(--success);font-weight:800;"><i class="fa-solid fa-wallet"></i> Bakiye: <strong id="acc-points">145 Cravelo Puan (14.50 ₺)</strong></span>
                    <span><i class="fa-solid fa-bag-shopping" style="color:var(--blue);"></i> Toplam Sipariş: <strong id="acc-order-count">0</strong></span>
                </div>
            </div>
        </div>

        <div class="modal-tabs" role="tablist">
            <button type="button" class="modal-tab-btn active" id="acc-tab-btn-profile" onclick="switchAccountTab('profile')" role="tab"><i class="fa-solid fa-id-card"></i> Profilim</button>
            <button type="button" class="modal-tab-btn" id="acc-tab-btn-orders" onclick="switchAccountTab('orders')" role="tab"><i class="fa-solid fa-clock-rotate-left"></i> Siparişlerim</button>
            <button type="button" class="modal-tab-btn" id="acc-tab-btn-coupons" onclick="switchAccountTab('coupons')" role="tab"><i class="fa-solid fa-ticket"></i> Kuponlarım</button>
            <button type="button" class="modal-tab-btn" id="acc-tab-btn-favs" onclick="switchAccountTab('favs')" role="tab"><i class="fa-solid fa-heart"></i> Favorilerim</button>
        </div>

        <div class="modal-body">
            <!-- Tab 1: Profilim & Cravelo Cüzdan -->
            <div id="acc-tab-profile">
                <form onsubmit="handleUpdateProfile(event)">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="profile-fullname">Ad Soyad</label>
                            <input type="text" class="form-input" id="profile-fullname" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="profile-phone">Telefon Numarası</label>
                            <input type="tel" class="form-input" id="profile-phone" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="profile-email">E-Posta Adresi</label>
                        <input type="email" class="form-input" id="profile-email" required>
                    </div>

                    <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:16px;padding:14px 16px;margin-bottom:16px;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
                        <div>
                            <div style="font-size:11px;color:var(--text-3);font-weight:800;text-transform:uppercase;letter-spacing:0.4px;">Varsayılan Teslimat Konumu</div>
                            <div style="font-size:14.5px;font-weight:800;color:var(--text);margin-top:4px;" id="profile-addr-title">Evim (Moda)</div>
                            <div style="font-size:12.5px;color:var(--primary);margin-top:2px;font-weight:700;" id="profile-addr-coord"><i class="fa-solid fa-map-pin" style="margin-right:4px;"></i> Moda, Kadıköy / İstanbul</div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline" onclick="closeAccountModal(); openAddressModal();" style="border-radius:9999px;">
                            <i class="fa-solid fa-pen"></i> Değiştir
                        </button>
                    </div>

                    <!-- Cravelo Wallet Card -->
                    <div class="wallet-card" style="margin-bottom:18px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:12px;">
                            <div>
                                <div style="font-size:14px;font-weight:900;color:var(--text);display:flex;align-items:center;gap:8px;">
                                    <i class="fa-solid fa-wallet" style="color:var(--primary);"></i> Cravelo Cüzdan Bakiyesi
                                </div>
                                <div style="font-size:12px;color:var(--text-2);margin-top:2px;">Her teslimat değerlendirmesinde +25 Cravelo Puan hediye</div>
                            </div>
                            <span style="font-size:18px;font-weight:900;color:var(--success);" id="acc-wallet-display">145 Puan (14.50 ₺)</span>
                        </div>
                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;border-top:1px dashed var(--border);padding-top:12px;">
                            <span style="font-size:11.5px;font-weight:800;color:var(--text-3);">Hızlı Bakiye Yükle:</span>
                            <button type="button" class="wallet-topup-chip" onclick="topUpCraveloWallet(50)">+50 ₺</button>
                            <button type="button" class="wallet-topup-chip" onclick="topUpCraveloWallet(100)">+100 ₺</button>
                            <button type="button" class="wallet-topup-chip" onclick="topUpCraveloWallet(250)">+250 ₺</button>
                            <button type="button" class="wallet-topup-chip" onclick="topUpCraveloWallet(500)">+500 ₺</button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width:100%;padding:14px;border-radius:14px;font-size:14.5px;">
                        <i class="fa-solid fa-floppy-disk"></i> Değişiklikleri Kaydet
                    </button>
                </form>
            </div>

            <!-- Tab 2: Siparişlerim & Canlı 5-Adımlı Takip -->
            <div id="acc-tab-orders" style="display:none;">
                <div id="acc-orders-list"></div>
            </div>

            <!-- Tab 3: Kuponlarım -->
            <div id="acc-tab-coupons" style="display:none;">
                <div id="acc-coupons-list"></div>
            </div>

            <!-- Tab 4: Favorilerim -->
            <div id="acc-tab-favs" style="display:none;">
                <div id="acc-favs-list"></div>
            </div>
        </div>
    </div>
</div>

<script>
function topUpCraveloWallet(amountTl) {
    if (typeof GustoState !== 'undefined') {
        var points = amountTl * 10;
        GustoState.addGustoPoints(points);
        var currentPts = GustoState.get().userPoints;
        var ptsEl = document.getElementById('acc-points');
        if (ptsEl) ptsEl.innerText = currentPts + ' Cravelo Puan (' + (currentPts / 10).toFixed(2) + ' ₺)';
        var walletEl = document.getElementById('acc-wallet-display');
        if (walletEl) walletEl.innerText = currentPts + ' Puan (' + (currentPts / 10).toFixed(2) + ' ₺)';
        var navRole = document.getElementById('nav-role');
        if (navRole && GustoState.get().currentRole === 'customer') {
            navRole.innerHTML = '<i class="fa-solid fa-coins"></i> ' + currentPts + ' Puan';
        }
        if (typeof showToast === 'function') {
            showToast('💳', '+' + amountTl + ' ₺ Cravelo Cüzdana Yüklendi! Yeni Bakiye: ' + (currentPts / 10).toFixed(2) + ' ₺');
        }
        if (typeof GustoAudio !== 'undefined' && GustoAudio.play) {
            GustoAudio.play('success');
        }
    }
}
</script>

<!-- 9. Dijital Sipariş Fişi / Fatura Modalı -->
<div class="modal-overlay" id="receipt-overlay" role="dialog" aria-modal="true" aria-label="Cravelo Dijital Sipariş Fişi">
    <div class="modal-box" style="max-width:520px;">
        <button type="button" class="modal-close" onclick="closeReceiptModal()" title="Kapat" aria-label="Fişi Kapat">
            <i class="fa-solid fa-xmark"></i>
        </button>
        <div id="receipt-body" style="padding-top:10px;"></div>
    </div>
</div>

<!-- Toast Notification Stack -->
<div class="toast-container" id="toast-container" aria-live="polite"></div>
