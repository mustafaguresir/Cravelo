/**
 * Cravelo — Tek Kaynaklı Sunucu Katalog Senkronizasyonu, Global Modal UX ve Akıllı Polling
 * public/js/features/catalog-sync.js
 */
(function (global) {
    'use strict';

    const DEFAULT_FOOD_IMG = 'https://images.unsplash.com/photo-1546069901-ba9599a4e63c?auto=format&fit=crop&w=400&q=80';
    const seenKdsOrderIds = new Set();
    let kdsBaselineReady = false;

    function safeEscape(val) {
        if (typeof global.escapeHtml === 'function') return global.escapeHtml(val);
        return String(val ?? '').replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function hydrateServerCatalog(catalogOverride) {
        if (typeof global.GustoState === 'undefined' || typeof global.GustoState.get !== 'function') return false;
        const catalog = catalogOverride || global.SERVER_CATALOG;
        if (!catalog || typeof catalog !== 'object') return false;

        const state = global.GustoState.get();

        // 1. Restoranları sunucu kaynağıyla eşitle (görsel alanları koruyarak)
        if (Array.isArray(catalog.restaurants) && Array.isArray(state.restaurants)) {
            catalog.restaurants.forEach(function (srvR) {
                const localR = state.restaurants.find(function (x) { return Number(x.id) === Number(srvR.id); });
                if (localR) {
                    localR.name = srvR.name || localR.name;
                    localR.isActive = srvR.is_active !== false;
                    if (typeof srvR.lat === 'number' && srvR.lat !== 0) localR.lat = srvR.lat;
                    if (typeof srvR.lon === 'number' && srvR.lon !== 0) localR.lon = srvR.lon;
                    if (typeof srvR.x === 'number') localR.x = srvR.x;
                    if (typeof srvR.y === 'number') localR.y = srvR.y;
                    if (srvR.neighborhood) localR.neighborhood = srvR.neighborhood;
                    localR.reviewCount = Number(srvR.review_count || 0);
                    if (srvR.rating && Number(srvR.review_count || 0) > 0) {
                        localR.rating = String(srvR.rating);
                    }
                }
            });
        }

        // 2. Menü ürünlerini eşitle (yeni KDS ürünlerini ekle, fiyatları/stokları güncelle)
        if (Array.isArray(catalog.menu_items) && Array.isArray(state.menuItems)) {
            const outOfStockIds = [];
            catalog.menu_items.forEach(function (srvM) {
                const isAvail = srvM.available !== false;
                if (!isAvail) outOfStockIds.push(Number(srvM.id));
                const localM = state.menuItems.find(function (x) { return Number(x.id) === Number(srvM.id); });
                if (localM) {
                    localM.name = srvM.name || localM.name;
                    localM.desc = srvM.desc || localM.desc;
                    localM.price = Number(srvM.price ?? localM.price);
                    localM.available = isAvail;
                    if (srvM.category) localM.category = srvM.category;
                    if (srvM.img) localM.img = srvM.img;
                } else {
                    state.menuItems.push({
                        id: Number(srvM.id),
                        rId: Number(srvM.rId || srvM.restaurant_id || 1),
                        name: String(srvM.name || 'Yeni Ürün'),
                        desc: String(srvM.desc || 'Şefin özenle hazırladığı özel lezzet'),
                        price: Number(srvM.price || 0),
                        category: String(srvM.category || 'Ana Yemek'),
                        tag: String(srvM.category || 'Şefin Yeni Seçimi'),
                        available: isAvail,
                        img: srvM.img || DEFAULT_FOOD_IMG
                    });
                }
            });
            state.outOfStockItems = outOfStockIds;
        }

        // 3. Kuryeleri sunucu kaynağıyla eşitle (vardiya durumu, canlı koordinatlar ve iş yükü)
        if (Array.isArray(catalog.couriers) && Array.isArray(state.couriers)) {
            catalog.couriers.forEach(function (srvC) {
                const localC = state.couriers.find(function (x) { return Number(x.id) === Number(srvC.id); });
                if (localC) {
                    if (typeof srvC.x === 'number') localC.x = srvC.x;
                    if (typeof srvC.y === 'number') localC.y = srvC.y;
                    if (typeof srvC.lat === 'number' && srvC.lat !== 0) localC.lat = srvC.lat;
                    if (typeof srvC.lon === 'number' && srvC.lon !== 0) localC.lon = srvC.lon;
                    if (srvC.neighborhood) localC.neighborhood = srvC.neighborhood;
                    if (typeof srvC.active === 'number') localC.active = srvC.active;
                    if (srvC.status) localC.status = String(srvC.status);
                }
            });
        }

        // 4. Değerlendirmeleri önbelleğe al
        if (Array.isArray(catalog.reviews)) {
            global.CRAVELO_REVIEWS = catalog.reviews;
        }

        if (typeof global.GustoState.persist === 'function') {
            global.GustoState.persist();
        }
        return true;
    }

    function hydrateServerProfile(profileOverride) {
        if (typeof global.GustoState === 'undefined' || typeof global.GustoState.get !== 'function') return false;
        const prof = profileOverride || global.SERVER_PROFILE;
        if (!prof || typeof prof !== 'object') return false;

        const state = global.GustoState.get();
        if (typeof prof.wallet_points === 'number') {
            state.userPoints = prof.wallet_points;
        }
        if (Array.isArray(prof.addresses) && prof.addresses.length > 0) {
            state.savedAddresses = prof.addresses;
        }
        if (prof.active_location && typeof prof.active_location === 'object') {
            state.customerLocation = Object.assign({}, state.customerLocation || {}, prof.active_location);
        }
        if (state.currentUser) {
            if (prof.fullName) state.currentUser.fullName = prof.fullName;
            if (prof.phone) state.currentUser.phone = prof.phone;
            if (prof.email) state.currentUser.email = prof.email;
        }

        if (typeof document !== 'undefined') {
            const pts = Number(state.userPoints || 0);
            const navRole = document.getElementById('nav-role');
            if (navRole && state.currentRole === 'customer') {
                navRole.innerHTML = '<i class="fa-solid fa-coins"></i> ' + pts + ' Puan';
            }
            const accPts = document.getElementById('acc-points');
            if (accPts) accPts.innerText = pts + ' Cravelo Puan (' + (pts / 10).toFixed(2) + ' ₺)';
            const walletDisp = document.getElementById('acc-wallet-display');
            if (walletDisp) walletDisp.innerText = pts + ' Puan (' + (pts / 10).toFixed(2) + ' ₺)';
            if (typeof global.updateAddressLabel === 'function') {
                global.updateAddressLabel();
            }
        }
        return true;
    }

    async function refreshServerCatalog() {
        if (typeof global.isCraveloHttpServer !== 'function' || !global.isCraveloHttpServer()) return null;
        if (typeof global.craveloApiFetch !== 'function') return null;
        const res = await global.craveloApiFetch('/api?action=catalog', { method: 'GET', silentError: true });
        if (res && res.ok && res.data) {
            const payload = res.data.catalog || res.data;
            global.SERVER_CATALOG = payload;
            hydrateServerCatalog(payload);
            return payload;
        }
        return null;
    }

    // Kapalı restoran rozeti ve sipariş engelleyici
    if (typeof global.buildRestaurantCardHTML === 'function') {
        const origBuildRestCard = global.buildRestaurantCardHTML;
        global.buildRestaurantCardHTML = function (r, state) {
            let html = origBuildRestCard(r, state);
            if (r && r.isActive === false) {
                html = html.replace(
                    'class="rest-card"',
                    'class="rest-card rest-card-closed" style="opacity:0.65;filter:grayscale(0.35);"'
                );
                html = html.replace(
                    '<div class="rest-dist-badge">',
                    '<div style="position:absolute;top:12px;left:12px;background:rgba(239,68,68,0.92);color:#fff;padding:5px 12px;border-radius:999px;font-size:11px;font-weight:900;z-index:5;letter-spacing:0.4px;"><i class="fa-solid fa-door-closed"></i> ŞU AN KAPALI</div><div class="rest-dist-badge">'
                );
            }
            return html;
        };
    }

    if (typeof global.selectRestaurant === 'function') {
        const origSelectRestaurant = global.selectRestaurant;
        global.selectRestaurant = function (id) {
            if (typeof global.GustoState !== 'undefined') {
                const rest = (global.GustoState.get().restaurants || []).find(function (r) { return Number(r.id) === Number(id); });
                if (rest && rest.isActive === false) {
                    if (typeof global.showToast === 'function') {
                        global.showToast('🚪', '"' + rest.name + '" şu anda sipariş alımına kapalıdır.', 'warn');
                    }
                    return;
                }
            }
            return origSelectRestaurant(id);
        };
    }

    // Global ESC + Backdrop tıklama ile tüm modalları kapatma (Erişilebilirlik & UX)
    function closeAllOpenModals() {
        if (typeof document === 'undefined' || typeof document.querySelectorAll !== 'function') return 0;
        let closed = 0;
        document.querySelectorAll('.modal-overlay').forEach(function (ov) {
            if (ov && ov.style && ov.style.display === 'flex') {
                ov.style.display = 'none';
                closed++;
                if (ov.id === 'chat-overlay' && typeof global.closeChatModal === 'function') {
                    global.closeChatModal();
                }
            }
        });
        return closed;
    }

    function bindGlobalModalAccessibility() {
        if (typeof document === 'undefined' || typeof document.addEventListener !== 'function') return;
        if (global.__craveloModalUxBound) return;
        global.__craveloModalUxBound = true;

        document.addEventListener('keydown', function (e) {
            if (e && (e.key === 'Escape' || e.keyCode === 27)) {
                closeAllOpenModals();
            }
        });

        document.addEventListener('click', function (e) {
            const target = e && e.target;
            if (target && target.classList && target.classList.contains('modal-overlay') && target.style.display === 'flex') {
                target.style.display = 'none';
                if (target.id === 'chat-overlay' && typeof global.closeChatModal === 'function') {
                    global.closeChatModal();
                }
            }
        });
    }

    // Akıllı görünürlük duyarlı polling (sekme gizliyken durur) + KDS otomatik yeni sipariş zili
    function collectCurrentKdsOrderIds() {
        const ids = [];
        if (typeof global.GustoState === 'undefined') return ids;
        const st = global.GustoState.get();
        const restId = Number(st.selectedVendorRestId || 0);
        const list = [];
        if (st.activeOrder) list.push(st.activeOrder);
        if (Array.isArray(st.orderHistory)) {
            st.orderHistory.forEach(function (o) { list.push(o); });
        }
        list.forEach(function (o) {
            if (o && Number(o.rId) === restId && !['teslim_edildi', 'iptal'].includes(o.status)) {
                ids.push(String(o.serverOrderId || o.id));
            }
        });
        return ids;
    }

    if (typeof global.craveloPollServerPartialRefresh === 'function') {
        const origPoll = global.craveloPollServerPartialRefresh;
        global.craveloPollServerPartialRefresh = async function () {
            if (typeof document !== 'undefined' && document.hidden === true) {
                return;
            }
            const st = typeof global.GustoState !== 'undefined' ? global.GustoState.get() : null;
            const role = st ? (st.currentRole || (global.ACTIVE_SESSION && global.ACTIVE_SESSION.role) || 'customer') : 'customer';

            if (role === 'restaurant' && !kdsBaselineReady) {
                collectCurrentKdsOrderIds().forEach(function (id) { seenKdsOrderIds.add(id); });
                kdsBaselineReady = true;
            }

            const result = await origPoll();

            if (role === 'restaurant') {
                const afterIds = collectCurrentKdsOrderIds();
                let newArrival = null;
                afterIds.forEach(function (id) {
                    if (!seenKdsOrderIds.has(id)) {
                        seenKdsOrderIds.add(id);
                        newArrival = id;
                    }
                });
                if (newArrival !== null) {
                    if (typeof global.GustoAudio !== 'undefined' && typeof global.GustoAudio.play === 'function') {
                        global.GustoAudio.play('bell');
                    }
                    if (typeof global.showToast === 'function') {
                        global.showToast('🔔', 'Yeni Mutfak Siparişi Geldi! (#ORD-' + safeEscape(newArrival) + ')', 'success');
                    }
                }
            }

            if (role === 'admin' && typeof global.refreshAdminCommandCenter === 'function') {
                global.refreshAdminCommandCenter(true);
            }

            return result;
        };
    }

    function bootCatalogSync() {
        hydrateServerCatalog();
        hydrateServerProfile();
        bindGlobalModalAccessibility();
        if (typeof global.renderRestaurantGrid === 'function' && typeof global.GustoState !== 'undefined') {
            const st = global.GustoState.get();
            if (st.currentRole === 'customer') {
                global.renderRestaurantGrid(st.activeCategory);
            }
        }
    }

    global.hydrateServerCatalog = hydrateServerCatalog;
    global.hydrateServerProfile = hydrateServerProfile;
    global.refreshServerCatalog = refreshServerCatalog;
    global.closeAllOpenModals = closeAllOpenModals;

    if (typeof document !== 'undefined' && typeof document.addEventListener === 'function') {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', bootCatalogSync);
        } else {
            bootCatalogSync();
        }
    }
})(typeof window !== 'undefined' ? window : globalThis);
