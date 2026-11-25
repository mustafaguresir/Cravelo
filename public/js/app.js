/**
 * Cravelo — UI Denetleyicisi, Görünüm Yöneticisi & Etkinlik Bağlayıcıları
 */

// ==========================================
// YARDIMCI HESAPLAMA & FORMAT ARAÇLARI
// ==========================================
function getSessionObject() {
    try {
        const raw = localStorage.getItem('cravelo_session')
            || localStorage.getItem('kuryo_session')
            || localStorage.getItem('tgo_session')
            || localStorage.getItem('gustogo_session');
        return raw ? JSON.parse(raw) : null;
    } catch (e) {
        return null;
    }
}

function getRealDistanceKm(entityA, entityB) {
    if (!entityA || !entityB) return 0.8;
    let lat1 = entityA.lat;
    let lon1 = entityA.lon;
    let lat2 = entityB.lat;
    let lon2 = entityB.lon;

    if ((typeof lat1 !== 'number' || typeof lon1 !== 'number') && typeof GustoMap !== 'undefined' && typeof GustoMap.gridToGps === 'function') {
        const g1 = GustoMap.gridToGps(entityA.x, entityA.y);
        lat1 = g1[0];
        lon1 = g1[1];
    }
    if ((typeof lat2 !== 'number' || typeof lon2 !== 'number') && typeof GustoMap !== 'undefined' && typeof GustoMap.gridToGps === 'function') {
        const g2 = GustoMap.gridToGps(entityB.x, entityB.y);
        lat2 = g2[0];
        lon2 = g2[1];
    }
    if (typeof lat1 === 'number' && typeof lon1 === 'number' && typeof lat2 === 'number' && typeof lon2 === 'number' && typeof GustoState !== 'undefined' && typeof GustoState.calculateHaversine === 'function') {
        return GustoState.calculateHaversine(lat1, lon1, lat2, lon2);
    }
    const mDist = (typeof GustoState !== 'undefined' && typeof GustoState.calculateManhattan === 'function')
        ? GustoState.calculateManhattan(entityA.x || 12, entityA.y || 8, entityB.x || 18, entityB.y || 14)
        : 6;
    return parseFloat(Math.max(0.3, mDist * 0.14).toFixed(2));
}

function getRealStreetLabel(entity, fallback = 'Moda Caddesi, Kadıköy') {
    if (!entity) return fallback;
    if (typeof GustoMap !== 'undefined' && typeof GustoMap.getStreetLabelForCoords === 'function') {
        return GustoMap.getStreetLabelForCoords(entity.x, entity.y, entity.lat, entity.lon, entity.neighborhood || entity.desc);
    }
    if (entity.desc && !/\(\d+\s*,\s*\d+\)/.test(entity.desc)) return entity.desc;
    if (entity.neighborhood) return entity.neighborhood;
    return fallback;
}

function getStatusMeta(status) {
    const map = {
        alindi: { label: 'Alındı', icon: 'fa-receipt', badge: 'badge-alindi', color: '#38BDF8', accent: 'rgba(56, 189, 248, 0.16)' },
        hazirlaniyor: { label: 'Hazırlanıyor', icon: 'fa-fire-burner', badge: 'badge-hazirlaniyor', color: '#F59E0B', accent: 'rgba(245, 158, 11, 0.16)' },
        hazir: { label: 'Hazır', icon: 'fa-box-open', badge: 'badge-hazir', color: '#A855F7', accent: 'rgba(168, 85, 247, 0.16)' },
        yolda: { label: 'Yolda', icon: 'fa-motorcycle', badge: 'badge-yolda', color: '#FF6B35', accent: 'rgba(255, 107, 53, 0.16)' },
        teslim_edildi: { label: 'Teslim Edildi', icon: 'fa-circle-check', badge: 'badge-teslim_edildi', color: '#10B981', accent: 'rgba(16, 185, 129, 0.16)' },
        iptal: { label: 'İptal Edildi', icon: 'fa-ban', badge: 'badge-iptal', color: '#EF4444', accent: 'rgba(239, 68, 68, 0.16)' }
    };
    return map[status] || { label: (status || 'Yeni').replace('_', ' '), icon: 'fa-circle-dot', badge: 'badge-alindi', color: '#94A3B8', accent: 'rgba(148, 163, 184, 0.14)' };
}

function pulseElement(el) {
    if (!el || !el.style) return;
    el.style.transform = 'scale(1.16)';
    el.style.transition = 'transform 0.22s cubic-bezier(0.16, 1, 0.3, 1)';
    setTimeout(() => {
        if (el && el.style) el.style.transform = '';
    }, 220);
}

// ==========================================
// BAŞLANGIÇ (INITIALIZATION)
// ==========================================
document.addEventListener('DOMContentLoaded', () => {
    GustoState.loadPersistedState();
    initTheme();
    if (typeof GustoAudio !== 'undefined' && typeof GustoAudio.updateSoundIcon === 'function') {
        GustoAudio.updateSoundIcon();
    }

    renderCategories();
    renderFlashDeals();
    renderPopularShowcase();
    renderRestaurantGrid(null);
    populateDropdowns();
    renderSavedAddressesList();
    startFlashCountdown();
    updateAddressLabel();
    switchDeliveryMode(GustoState.get().deliveryMode || 'courier', true);
    updateCartUI();
    updateOrderTrackerUI();

    // Aktif sipariş varsa kurye animasyonunu sürdür
    const activeOrder = GustoState.get().activeOrder;
    if (activeOrder && activeOrder.status !== 'teslim_edildi') {
        const r = GustoState.get().restaurants.find(x => x.id === activeOrder.rId);
        const c = GustoState.get().couriers.find(x => x.id === activeOrder.courierId);
        if (r && c) {
            GustoMap.startCourierAnimation(c, r, GustoState.get().customerLocation);
        }
        if (GustoState.get().isSimulationEnabled) {
            GustoState.startSimulation(handleSimulationStep);
        }
    }

    // Aktif Oturum ve Rolü doğrula/uygula (RBAC Arayüz İzolasyonu)
    // Şartname R1 & R2: Oturum açmamış ziyaretçi için varsayılan müşteri veya demo hesaba düşülmez
    let activeRole = null;
    try {
        const sess = getSessionObject();
        if (sess && sess.role) {
            activeRole = sess.role;
            if (!GustoState.get().currentUser) {
                GustoState.get().currentUser = { role: sess.role };
            }
            if (sess.fullName) GustoState.get().currentUser.fullName = sess.fullName;
            if (sess.username) GustoState.get().currentUser.username = sess.username;
            if (sess.email) GustoState.get().currentUser.email = sess.email;
            if (sess.phone) GustoState.get().currentUser.phone = sess.phone;
            if (sess.role === 'restaurant') {
                if (sess.selectedVendorRestId) {
                    GustoState.get().selectedVendorRestId = sess.selectedVendorRestId;
                } else if (sess.username === 'restoran_burger' || sess.email === 'burger@ornek.com') {
                    GustoState.get().selectedVendorRestId = 2;
                } else if (sess.username === 'restoran_pizza' || sess.email === 'pizza@ornek.com') {
                    GustoState.get().selectedVendorRestId = 3;
                } else {
                    GustoState.get().selectedVendorRestId = 1;
                }
            } else if (sess.role === 'courier') {
                if (sess.selectedCourierId) {
                    GustoState.get().selectedCourierId = sess.selectedCourierId;
                } else if (sess.username === 'kurye_ali') {
                    GustoState.get().selectedCourierId = 1;
                } else if (sess.username === 'kurye_veli') {
                    GustoState.get().selectedCourierId = 2;
                } else if (sess.username === 'kurye_can') {
                    GustoState.get().selectedCourierId = 3;
                }
            }
        } else if (window.ACTIVE_SESSION && window.ACTIVE_SESSION.role) {
            activeRole = window.ACTIVE_SESSION.role;
            if (!GustoState.get().currentUser) {
                GustoState.get().currentUser = { role: window.ACTIVE_SESSION.role };
            }
            if (window.ACTIVE_SESSION.id) {
                GustoState.get().currentUser.id = window.ACTIVE_SESSION.id;
            }
            if (window.ACTIVE_SESSION.fullName) {
                GustoState.get().currentUser.fullName = window.ACTIVE_SESSION.fullName;
            }
            if (window.ACTIVE_SESSION.username) {
                GustoState.get().currentUser.username = window.ACTIVE_SESSION.username;
            }
            if (window.ACTIVE_SESSION.phone) {
                GustoState.get().currentUser.phone = window.ACTIVE_SESSION.phone;
            }
            if (window.ACTIVE_SESSION.role === 'restaurant') {
                if (window.ACTIVE_SESSION.username === 'restoran_burger') {
                    GustoState.get().selectedVendorRestId = 2;
                } else if (window.ACTIVE_SESSION.username === 'restoran_pizza') {
                    GustoState.get().selectedVendorRestId = 3;
                } else {
                    GustoState.get().selectedVendorRestId = 1;
                }
            } else if (window.ACTIVE_SESSION.role === 'courier') {
                if (window.ACTIVE_SESSION.username === 'kurye_veli') {
                    GustoState.get().selectedCourierId = 2;
                } else if (window.ACTIVE_SESSION.username === 'kurye_can') {
                    GustoState.get().selectedCourierId = 3;
                } else {
                    GustoState.get().selectedCourierId = 1;
                }
            }
        } else if (GustoState.get().currentRole) {
            activeRole = GustoState.get().currentRole;
        }
    } catch (e) {
        activeRole = GustoState.get().currentRole || null;
    }

    // Çıkış yapıp yeniden giriş yapan veya başka cihazdan bağlanan kullanıcının siparişlerini geri yükle
    if (activeRole && typeof hydrateOrdersForActiveUser === 'function') {
        const serverOrders = (typeof window !== 'undefined' && Array.isArray(window.SERVER_ORDERS)) ? window.SERVER_ORDERS : null;
        hydrateOrdersForActiveUser(serverOrders, activeRole);
    }

    applyRoleIsolation(activeRole);
});

// ==========================================
// TEMA & SES YÖNETİMİ
// ==========================================
function initTheme() {
    const savedTheme = localStorage.getItem('cravelo-theme') || localStorage.getItem('gustogo-theme') || 'dark';
    document.documentElement.setAttribute('data-theme', savedTheme);
    updateThemeIcon(savedTheme);
}

function updateThemeIcon(theme) {
    const icon = document.getElementById('theme-ico');
    if (icon) {
        icon.className = theme === 'dark' ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
    }
    const landingIcon = document.getElementById('landing-theme-ico');
    if (landingIcon) {
        landingIcon.className = theme === 'dark' ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
    }
}

function toggleTheme() {
    const current = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', current);
    localStorage.setItem('gustogo-theme', current);
    localStorage.setItem('cravelo-theme', current);
    updateThemeIcon(current);
    GustoAudio.play('add');
    GustoMap.drawMap('customer-map-canvas');
    GustoMap.drawMap('courier-map-canvas');
    GustoMap.drawMap('admin-map-canvas');
}

function toggleSound() {
    GustoAudio.toggleMute();
}

function populateDropdowns() {
    const rSel = document.getElementById('res-selector');
    if (rSel) {
        rSel.innerHTML = GustoState.get().restaurants.map(r => `<option value="${r.id}">${r.emoji} ${r.name}</option>`).join('');
        rSel.value = GustoState.get().selectedVendorRestId;
    }
    const cSel = document.getElementById('courier-selector');
    if (cSel) {
        cSel.innerHTML = GustoState.get().couriers.map(c => `<option value="${c.id}">${c.name} (${c.vehicle})</option>`).join('');
        cSel.value = GustoState.get().selectedCourierId;
    }
}

// ==========================================
// KATEGORİ, ARAMA VE AKILLI SIRALAMA
// ==========================================
function setSortMode(mode) {
    GustoState.get().currentSortMode = mode;
    ['fast', 'rating', 'promo', 'delivery'].forEach(m => {
        const el = document.getElementById('sort-' + m);
        if (el) el.className = 'sort-chip ' + (m === mode ? 'active' : '');
    });
    GustoAudio.play('add');
    renderRestaurantGrid(GustoState.get().activeCategory);
}

function renderCategories() {
    const container = document.getElementById('cat-pills');
    if (!container) return;
    const state = GustoState.get();
    const activeCat = state.activeCategory;
    container.innerHTML = CATEGORIES.map(c => {
        const count = c.f ? state.restaurants.filter(r => r.cat === c.f).length : state.restaurants.length;
        const isActive = activeCat === c.f;
        return `
            <button type="button" class="cat-pill ${isActive ? 'active' : ''}" onclick="filterCategory('${c.f}')">
                <span>${c.emoji}</span>
                <span>${c.name}</span>
                <span style="font-size:11px;font-weight:800;padding:1px 7px;border-radius:999px;background:${isActive ? 'rgba(255,255,255,0.22)' : 'var(--surface-2)'};color:${isActive ? '#fff' : 'var(--text-3)'};">${count}</span>
            </button>
        `;
    }).join('');
}

function filterCategory(cat) {
    const state = GustoState.get();
    // Toggle: Eğer aynı kategoriye tekrar basıldıysa filtreyi sıfırla
    const f = (state.activeCategory === cat || cat === 'null' || !cat) ? null : cat;
    state.activeCategory = f;

    // Kategori karelerinin aktiflik durumunu güncelle
    document.querySelectorAll('.story-item').forEach(el => {
        const itemCat = el.getAttribute('data-cat');
        if (f && itemCat === f) {
            el.classList.add('active');
        } else {
            el.classList.remove('active');
        }
    });

    renderCategories();
    renderRestaurantGrid(f);
    GustoAudio.play('add');

    const titleEl = document.getElementById('rest-section-title');
    if (titleEl) {
        titleEl.innerHTML = `<i class="fa-solid fa-store" style="color:var(--primary);"></i> ` + (f ? `${f} Restoranları` : 'Çevrendeki Restoranlar');
    }
    const restSec = document.getElementById('rest-section');
    const menuSec = document.getElementById('menu-section');
    if (restSec) restSec.style.display = 'block';
    if (menuSec) menuSec.style.display = 'none';

    if (f && restSec && typeof restSec.scrollIntoView === 'function') {
        restSec.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

function buildRestaurantCardHTML(r, state) {
    const distKm = getRealDistanceKm(r, state.customerLocation);
    const isTakeaway = state.deliveryMode === 'takeaway';
    const delFee = isTakeaway ? 0 : Math.max(15, Math.round(distKm * 14));
    const isFav = state.userFavorites && state.userFavorites.includes(r.id);
    const locLabel = r.neighborhood || 'Moda, Kadıköy';
    const distPill = isTakeaway
        ? `<i class="fa-solid fa-person-walking"></i> ${distKm.toFixed(1)} km • Gel-Al 0 ₺`
        : `<i class="fa-solid fa-motorcycle"></i> ${distKm.toFixed(1)} km • ${delFee} ₺ Teslimat`;

    return `
        <div class="rest-card" onclick="selectRestaurant(${r.id})">
            <div class="rest-cover-wrap">
                <img class="rest-cover" src="${r.cover}" alt="${r.name}" loading="lazy">
                ${r.promo ? `<div class="rest-promo"><i class="fa-solid fa-bolt"></i> ${r.promo}</div>` : ''}
                <button type="button" class="rest-fav-btn ${isFav ? 'active' : ''}" onclick="event.stopPropagation();toggleRestFav(this, ${r.id})" title="${isFav ? 'Favorilerden Çıkar' : 'Favorilere Ekle'}">
                    <i class="fa-${isFav ? 'solid' : 'regular'} fa-heart"></i>
                </button>
                <div class="rest-dist-badge">${distPill}</div>
            </div>
            <div class="rest-body">
                <div class="rest-header-row">
                    <div class="rest-name">${r.emoji} ${r.name}</div>
                    <div class="rest-rating"><i class="fa-solid fa-star"></i> ${r.rating}</div>
                </div>
                <div class="rest-meta">
                    <span class="rest-cuisine">${r.cat}</span>
                    <span class="rest-dot">•</span>
                    <span class="rest-time"><i class="fa-regular fa-clock" style="color:var(--primary);"></i> ${r.time} dk</span>
                    <span class="rest-dot">•</span>
                    <span class="rest-loc rest-loc-pill"><i class="fa-solid fa-location-dot" style="color:var(--primary);margin-right:3px;"></i>${locLabel}</span>
                </div>
            </div>
        </div>
    `;
}

function renderRestaurantGrid(categoryFilter) {
    const state = GustoState.get();
    let list = categoryFilter
        ? state.restaurants.filter(r => r.cat === categoryFilter)
        : [...state.restaurants];

    // Akıllı Sıralama (Haversine km / Puan / Kampanya / Teslimat Ücreti)
    if (state.currentSortMode === 'fast' || state.currentSortMode === 'delivery') {
        list.sort((a, b) => getRealDistanceKm(a, state.customerLocation) - getRealDistanceKm(b, state.customerLocation));
    } else if (state.currentSortMode === 'rating') {
        list.sort((a, b) => parseFloat(b.rating) - parseFloat(a.rating));
    } else if (state.currentSortMode === 'promo') {
        list.sort((a, b) => (b.promo ? 1 : 0) - (a.promo ? 1 : 0));
    }

    const badge = document.getElementById('rest-count-badge');
    if (badge) badge.innerText = `${list.length} Restoran açık`;

    const grid = document.getElementById('rest-grid');
    if (!grid) return;

    if (!list.length) {
        grid.innerHTML = `
            <div style="grid-column:1/-1;text-align:center;padding:56px 24px;background:var(--surface);border:1px solid var(--border);border-radius:24px;">
                <div style="width:60px;height:60px;border-radius:18px;background:rgba(255,107,53,0.12);color:var(--primary);display:inline-flex;align-items:center;justify-content:center;font-size:24px;margin-bottom:14px;">
                    <i class="fa-solid fa-utensils"></i>
                </div>
                <div style="font-size:17px;font-weight:800;color:var(--text);">Bu kategoride şu an açık restoran bulunamadı</div>
                <div style="font-size:13px;color:var(--text-2);margin-top:6px;">Tüm Kadıköy lezzetlerini görmek için filtreyi sıfırlayabilirsiniz.</div>
                <button type="button" class="btn btn-primary" style="margin-top:16px;border-radius:999px;padding:10px 22px;" onclick="filterCategory(null)">
                    <i class="fa-solid fa-rotate-left"></i> Tüm Restoranları Göster
                </button>
            </div>
        `;
        return;
    }

    grid.innerHTML = list.map(r => buildRestaurantCardHTML(r, state)).join('');
}

function toggleRestFav(btn, rId) {
    const state = GustoState.get();
    if (!state.userFavorites) state.userFavorites = [];
    const idx = state.userFavorites.indexOf(rId);
    if (idx > -1) {
        state.userFavorites.splice(idx, 1);
        btn.classList.remove('active');
        btn.innerHTML = '<i class="fa-regular fa-heart"></i>';
        showToast('💔', 'Restoran favorilerden çıkarıldı.');
    } else {
        state.userFavorites.push(rId);
        btn.classList.add('active');
        btn.innerHTML = '<i class="fa-solid fa-heart"></i>';
        showToast('❤️', 'Restoran favorilerinize eklendi!');
    }
    pulseElement(btn);
    GustoState.persist();
    GustoAudio.play('add');
}

function showRestaurants() {
    const role = GustoState.get().currentRole || 'customer';
    if (role !== 'customer') {
        applyRoleIsolation(role);
        return;
    }
    GustoState.get().selectedRestId = null;
    const restSec = document.getElementById('rest-section');
    const flashSec = document.getElementById('flash-deals-section');
    const popSec = document.getElementById('popular-showcase-section');
    const menuSec = document.getElementById('menu-section');
    if (restSec) restSec.style.display = 'block';
    if (flashSec) flashSec.style.display = 'block';
    if (popSec) popSec.style.display = 'block';
    if (menuSec) menuSec.style.display = 'none';
    renderRestaurantGrid(GustoState.get().activeCategory);
    updateCartUI();
}

function selectRestaurant(id) {
    GustoState.get().selectedRestId = id;
    const state = GustoState.get();
    const r = state.restaurants.find(x => x.id === id);
    if (!r) return;

    const imgEl = document.getElementById('mh-img');
    const nameEl = document.getElementById('mh-name');
    const ratingEl = document.getElementById('mh-rating');
    const timeEl = document.getElementById('mh-time');
    const locEl = document.getElementById('mh-loc');
    const catEl = document.getElementById('mh-cat');
    const promoEl = document.getElementById('mh-promo');

    if (imgEl) imgEl.src = r.cover;
    if (nameEl) nameEl.innerText = r.name;
    if (ratingEl) ratingEl.innerText = r.rating;
    if (timeEl) timeEl.innerText = r.time;
    if (locEl) locEl.innerText = r.neighborhood || getRealStreetLabel(r, 'Moda, Kadıköy');
    if (catEl) catEl.innerText = `${r.emoji} ${r.cat}`;
    if (promoEl) {
        promoEl.innerText = r.promo || 'Hızlı Teslimat';
        promoEl.style.display = r.promo ? 'inline-flex' : 'none';
    }

    const items = state.menuItems.filter(m => m.rId === id);
    const grid = document.getElementById('food-grid');
    if (!grid) return;

    grid.innerHTML = items.map(item => {
        const isFav = state.userFavorites.includes(item.id);
        const isAvail = item.available !== false;
        return `
            <div class="food-card ${!isAvail ? 'is-soldout' : ''}" onclick="${isAvail ? `openCustomizer(${item.id})` : ''}">
                <div class="food-img-wrap">
                    <img class="food-img" src="${item.img}" alt="${item.name}" loading="lazy" style="${!isAvail ? 'filter:grayscale(0.75);opacity:0.65;' : ''}">
                    <div class="food-badge-wrap">
                        <span class="food-tag">${item.tag}</span>
                        <button type="button" class="food-heart ${isFav ? 'active' : ''}" style="${isFav ? 'background:var(--primary);color:#fff;' : ''}" onclick="event.stopPropagation();toggleHeart(this, ${item.id})" title="Favori">
                            <i class="fa-${isFav ? 'solid' : 'regular'} fa-heart"></i>
                        </button>
                    </div>
                    ${!isAvail ? '<div style="position:absolute;inset:0;background:rgba(15,23,42,0.68);backdrop-filter:blur(4px);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:14px;letter-spacing:1.2px;"><span style="background:rgba(239,68,68,0.9);padding:6px 16px;border-radius:999px;">TÜKENDİ</span></div>' : ''}
                </div>
                <div class="food-body">
                    <div class="food-name">${item.name}</div>
                    <div class="food-desc">${item.desc}</div>
                    <div class="food-footer">
                        <div class="food-price">${item.price.toFixed(2)} ₺</div>
                        <button type="button" class="btn-add" ${!isAvail ? 'disabled style="opacity:0.45;cursor:not-allowed;"' : ''} onclick="event.stopPropagation();openCustomizer(${item.id})">
                            <i class="fa-solid ${isAvail ? 'fa-plus' : 'fa-ban'}"></i> ${isAvail ? 'Ekle' : 'Tükendi'}
                        </button>
                    </div>
                </div>
            </div>
        `;
    }).join('');

    const restSec = document.getElementById('rest-section');
    const flashSec = document.getElementById('flash-deals-section');
    const popSec = document.getElementById('popular-showcase-section');
    const menuSec = document.getElementById('menu-section');
    if (restSec) restSec.style.display = 'none';
    if (flashSec) flashSec.style.display = 'none';
    if (popSec) popSec.style.display = 'none';
    if (menuSec) menuSec.style.display = 'grid';

    updateCartUI();
    GustoMap.drawMap('customer-map-canvas');
    if (typeof window !== 'undefined' && typeof window.scrollTo === 'function') {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

// ==========================================
// AKILLI VE KATEGORİYE ÖZEL ÜRÜN ÖZELLEŞTİRİCİ
// ==========================================
let customizingItemId = null;
let customPortionPrice = 0;
let customPortionName = 'Standart Porsiyon';
let customQty = 1;
let currentCustomConfig = null;

function getItemCategoryProfile(item) {
    const name = (item.name || '').toLowerCase();
    const tag = (item.tag || '').toLowerCase();

    // 1. Tatlı Grubu (Tiramisu, Waffle, Baklava, Sütlaç, Sufle vb.)
    if (tag.includes('tatlı') || name.includes('tiramisu') || name.includes('waffle') || 
        name.includes('cheesecake') || name.includes('profiterol') || name.includes('baklava') || 
        name.includes('sütlaç') || name.includes('sufle')) {
        return 'dessert';
    }

    // 2. İçecek & Kahve Grubu (Ayran, Limonata, Macchiato, Kola vb.)
    if (tag.includes('içecek') || tag.includes('kahve') || tag.includes('doğal') || tag.includes('serinletici') ||
        name.includes('ayran') || name.includes('limonata') || name.includes('macchiato') || 
        name.includes('kola') || name.includes('soda') || name.includes('kahve')) {
        return 'drink';
    }

    // 3. Burger & Patates Grubu
    if (name.includes('burger') || name.includes('fries') || name.includes('patates')) {
        return 'burger';
    }

    // 4. Pizza Grubu
    if (name.includes('pizza')) {
        return 'pizza';
    }

    // 5. Kebap & Dürüm Grubu
    if (name.includes('dürüm') || name.includes('kebap') || name.includes('lahmacun') || name.includes('iskender')) {
        return 'kebab';
    }

    return 'general';
}

const CATEGORY_CUSTOMIZATION_DEFS = {
    dessert: {
        portionTitle: "Porsiyon / Sunum Tercihi",
        portions: [
            { id: 1, name: "Standart Dilim / Porsiyon", price: 0 },
            { id: 2, name: "Duble Gurme Porsiyon", price: 35 },
            { id: 3, name: "Paylaşımlık Aile Boyu", price: 65 }
        ],
        extraTitle: "Tatlı İlavesi & Soslar",
        extras: [
            { id: "ext_icecream", label: "🍨 1 Top Maraş Kesme Dondurma", price: 35 },
            { id: "ext_pistachio", label: "🥜 Bol Antep Fıstığı Tozu", price: 30 },
            { id: "ext_choco", label: "🍫 Sıcak Belçika Çikolatası Sosu", price: 20 },
            { id: "ext_espresso", label: "☕ Duble Espresso Shot İlavesi", price: 25 }
        ],
        prefTitle: "Özel Mutfak Tercihleri",
        preferences: [
            { id: "pref_light", label: "Şerbeti / Şekeri Az Olsun" },
            { id: "pref_nutfree", label: "Fındıksız (Alerjen Uyarısı)" },
            { id: "pref_cold", label: "Soğuk Servis Edilsin" },
            { id: "pref_cutlery", label: "Çatal / Tatlı Kaşığı Ekle" }
        ]
    },
    drink: {
        portionTitle: "Boyut Seçimi",
        portions: [
            { id: 1, name: "Standart Boy (330ml)", price: 0 },
            { id: 2, name: "Büyük Boy (500ml)", price: 15 },
            { id: 3, name: "Mega Boy (1 Litre)", price: 30 }
        ],
        extraTitle: "İçecek Ekstraları",
        extras: [
            { id: "ext_lemon", label: "🍋 Taze Limon Dilimi", price: 5 },
            { id: "ext_mint", label: "🌿 Taze Nane Yaprakları", price: 5 }
        ],
        prefTitle: "Servis Tercihi",
        preferences: [
            { id: "pref_ice", label: "🧊 Bol Buzlu" },
            { id: "pref_noice", label: "Buzsuz Servis" },
            { id: "pref_straw", label: "Eko Kağıt Pipet İsteniyor" }
        ]
    },
    burger: {
        portionTitle: "Köfte Gramajı & Boyut",
        portions: [
            { id: 1, name: "Tek Köfte (140gr)", price: 0 },
            { id: 2, name: "Double Smash (220gr)", price: 50 },
            { id: 3, name: "Triple Monster (320gr)", price: 90 }
        ],
        extraTitle: "Ekstra Malzeme & Çıtırlar",
        extras: [
            { id: "ext_cheddar", label: "🧀 Ekstra Eritilmiş Cheddar", price: 25 },
            { id: "ext_bacon", label: "🥓 Çıtır Dana Füme Bacon", price: 35 },
            { id: "ext_caram_onion", label: "🧅 Karamelize Soğan", price: 20 },
            { id: "ext_fries", label: "🍟 Yanında Baharatlı Patates", price: 30 }
        ],
        prefTitle: "Çıkarılacak Malzemeler",
        preferences: [
            { id: "rem_onion", label: "Soğansız" },
            { id: "rem_pickle", label: "Turşusuz" },
            { id: "rem_sauce", label: "Sosu Ayrı Kapta" }
        ]
    },
    kebab: {
        portionTitle: "Dürüm / Porsiyon Gramajı",
        portions: [
            { id: 1, name: "Standart Dürüm", price: 0 },
            { id: 2, name: "1.5 Doyurucu Porsiyon", price: 45 },
            { id: 3, name: "Duble Mega Porsiyon", price: 80 }
        ],
        extraTitle: "Lavaş & Yan Lezzetler",
        extras: [
            { id: "ext_kashar", label: "🧀 Lavaş Üstü Eritme Kaşar", price: 25 },
            { id: "ext_bread", label: "🫓 Çift Lavaş / Tırnak Pide", price: 15 },
            { id: "ext_peppers", label: "🌶️ Közlenmiş Biber & Domates", price: 15 },
            { id: "ext_ayran", label: "🥛 Yayık Ayran İlavesi", price: 25 }
        ],
        prefTitle: "Acı ve Yeşillik Tercihi",
        preferences: [
            { id: "pref_spicy", label: "Bol Acılı" },
            { id: "pref_nospicy", label: "Acısız" },
            { id: "rem_onion", label: "Soğansız (Sadece Maydanoz)" }
        ]
    },
    pizza: {
        portionTitle: "Pizza Ebadı",
        portions: [
            { id: 1, name: "Orta Boy (28 cm)", price: 0 },
            { id: 2, name: "Büyük Boy (33 cm)", price: 40 },
            { id: 3, name: "Aile Boyu (40 cm)", price: 75 }
        ],
        extraTitle: "Kenar & Peynir İlavesi",
        extras: [
            { id: "ext_crust", label: "🧀 Peynir Dolgulu Çıtır Kenar", price: 35 },
            { id: "ext_mozzarella", label: "🧀 Ekstra Çifte Mozzarella", price: 30 },
            { id: "ext_garlic_dip", label: "🌿 Sarımsaklı Kekik Sosu (Dip)", price: 15 }
        ],
        prefTitle: "İstemediğiniz Malzemeler",
        preferences: [
            { id: "rem_mushroom", label: "Mantarsız" },
            { id: "rem_olive", label: "Zeytinsiz" },
            { id: "rem_corn", label: "Mısırsız" }
        ]
    },
    general: {
        portionTitle: "Porsiyon Seçimi",
        portions: [
            { id: 1, name: "Standart Porsiyon", price: 0 },
            { id: 2, name: "1.5 Doyurucu Porsiyon", price: 40 },
            { id: 3, name: "Duble Menü", price: 75 }
        ],
        extraTitle: "Yan Ürün & Soslar",
        extras: [
            { id: "ext_fries", label: "🍟 Yanında Çıtır Patates", price: 30 },
            { id: "ext_sauce", label: "🥫 Özel Şef Sosu", price: 15 },
            { id: "ext_drink", label: "🥤 Soğuk İçecek İlavesi", price: 25 }
        ],
        prefTitle: "Pişirme & Servis Notu",
        preferences: [
            { id: "pref_well_done", label: "İyi Pişmiş / Çıtır" },
            { id: "pref_sauce_separate", label: "Sosu Ayrı Servis Edilsin" }
        ]
    }
};

function openCustomizer(itemId) {
    if (GustoState.get().activeOrder) {
        showToast('⚠️', 'Hazırlanmakta olan aktif bir siparişiniz var!', 'warn');
        return;
    }
    customizingItemId = itemId || 6;
    const item = GustoState.get().menuItems.find(m => m.id === customizingItemId);
    if (!item) return;
    if (item.available === false) {
        showToast('⚠️', 'Bu lezzet restoranda şu anda tükendi!', 'warn');
        return;
    }

    const cImg = document.getElementById('cust-img');
    const cName = document.getElementById('cust-name');
    const cDesc = document.getElementById('cust-desc');
    const cBase = document.getElementById('cust-base-price');
    if (cImg) cImg.src = item.img;
    if (cName) cName.innerText = item.name;
    if (cDesc) cDesc.innerText = item.desc;
    if (cBase) cBase.innerText = item.price.toFixed(2) + ' ₺';

    const profileKey = getItemCategoryProfile(item);
    currentCustomConfig = CATEGORY_CUSTOMIZATION_DEFS[profileKey] || CATEGORY_CUSTOMIZATION_DEFS.general;

    // Dinamik HTML oluştur
    const dynContainer = document.getElementById('cust-dynamic-options');
    if (dynContainer) {
        let html = '';

        // 1. Porsiyon Seçenekleri
        html += `<div class="custom-sec-title"><span>${currentCustomConfig.portionTitle}</span> <span style="font-size:11px;font-weight:800;padding:3px 10px;border-radius:999px;background:rgba(255,107,53,0.14);color:var(--primary);">Zorunlu</span></div>`;
        html += `<div class="choice-pill-group">`;
        currentCustomConfig.portions.forEach((p, idx) => {
            html += `
                <div class="choice-pill ${idx === 0 ? 'selected' : ''}" id="opt-size-${p.id}" onclick="selectPortion(${p.price}, '${p.name}', ${p.id})">
                    <span style="display:inline-flex;align-items:center;gap:8px;font-weight:700;">
                        <i class="fa-regular ${idx === 0 ? 'fa-circle-dot' : 'fa-circle'}" style="color:${idx === 0 ? 'var(--primary)' : 'var(--text-3)'};"></i>
                        ${p.name}
                    </span>
                    <span style="font-weight:800;color:${p.price > 0 ? 'var(--primary)' : 'var(--text-3)'};">${p.price > 0 ? '+' + p.price.toFixed(2) + ' ₺' : 'Dahil'}</span>
                </div>
            `;
        });
        html += `</div>`;

        // 2. Ekstra Malzemeler
        if (currentCustomConfig.extras && currentCustomConfig.extras.length > 0) {
            html += `<div class="custom-sec-title"><span>${currentCustomConfig.extraTitle}</span> <span style="font-size:11px;font-weight:700;padding:3px 10px;border-radius:999px;background:var(--surface-2);color:var(--text-3);">İsteğe Bağlı</span></div>`;
            currentCustomConfig.extras.forEach(ext => {
                html += `
                    <label class="checkbox-row" style="cursor:pointer;">
                        <span style="font-weight:600;color:var(--text);">${ext.label}</span>
                        <div style="display:flex;align-items:center;gap:12px;">
                            <span style="color:var(--primary);font-weight:800;font-size:13px;">+${ext.price.toFixed(2)} ₺</span>
                            <input type="checkbox" id="${ext.id}" value="${ext.price}" data-label="${ext.label}" onchange="calcCustomTotal()" style="accent-color:var(--primary);width:17px;height:17px;cursor:pointer;">
                        </div>
                    </label>
                `;
            });
        }

        // 3. Tercihler / Çıkarılacaklar
        if (currentCustomConfig.preferences && currentCustomConfig.preferences.length > 0) {
            html += `<div class="custom-sec-title"><span>${currentCustomConfig.prefTitle}</span></div>`;
            html += `<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px;">`;
            currentCustomConfig.preferences.forEach(pref => {
                html += `
                    <label class="chat-pill" style="display:inline-flex;align-items:center;gap:6px;cursor:pointer;padding:7px 12px;border-radius:999px;">
                        <input type="checkbox" id="${pref.id}" data-label="${pref.label}" style="accent-color:var(--primary);cursor:pointer;">
                        <span>${pref.label}</span>
                    </label>
                `;
            });
            html += `</div>`;
        }

        dynContainer.innerHTML = html;
    }

    // İlk porsiyonu seçili yap
    customPortionPrice = currentCustomConfig.portions[0].price;
    customPortionName = currentCustomConfig.portions[0].name;

    const noteEl = document.getElementById('cust-note');
    if (noteEl) noteEl.value = '';
    customQty = 1;
    const qtyEl = document.getElementById('cust-qty');
    if (qtyEl) qtyEl.innerText = '1';

    calcCustomTotal();
    const overlay = document.getElementById('customizer-overlay');
    if (overlay) overlay.style.display = 'flex';
}

function closeCustomizer() {
    const overlay = document.getElementById('customizer-overlay');
    if (overlay) overlay.style.display = 'none';
}

function selectPortion(price, name, optId) {
    customPortionPrice = price;
    customPortionName = name;
    if (currentCustomConfig && currentCustomConfig.portions) {
        currentCustomConfig.portions.forEach(p => {
            const el = document.getElementById('opt-size-' + p.id);
            if (el) {
                const isSelected = p.id === optId;
                el.className = 'choice-pill ' + (isSelected ? 'selected' : '');
                const icon = el.querySelector && el.querySelector('i');
                if (icon) {
                    icon.className = 'fa-regular ' + (isSelected ? 'fa-circle-dot' : 'fa-circle');
                    icon.style.color = isSelected ? 'var(--primary)' : 'var(--text-3)';
                }
            }
        });
    }
    calcCustomTotal();
}

function changeCustomQty(delta) {
    customQty = Math.max(1, customQty + delta);
    const qtyEl = document.getElementById('cust-qty');
    if (qtyEl) qtyEl.innerText = customQty;
    calcCustomTotal();
}

function calcCustomTotal() {
    const item = GustoState.get().menuItems.find(m => m.id === customizingItemId);
    if (!item) return 0;

    let unit = item.price + customPortionPrice;
    if (currentCustomConfig && currentCustomConfig.extras) {
        currentCustomConfig.extras.forEach(ext => {
            const el = document.getElementById(ext.id);
            if (el && el.checked) {
                unit += ext.price;
            }
        });
    }

    const tot = unit * customQty;
    const btnText = document.getElementById('btn-cust-text');
    if (btnText) btnText.innerText = `Sepete Ekle • ${tot.toFixed(2)} ₺`;
    return tot;
}

function addCustomizedItemToCart() {
    const item = GustoState.get().menuItems.find(m => m.id === customizingItemId);
    if (!item) return;

    let unit = item.price + customPortionPrice;
    const extras = [];
    if (currentCustomConfig && currentCustomConfig.extras) {
        currentCustomConfig.extras.forEach(ext => {
            const el = document.getElementById(ext.id);
            if (el && el.checked) {
                unit += ext.price;
                extras.push(`${ext.label} (+${ext.price}₺)`);
            }
        });
    }

    const preferences = [];
    if (currentCustomConfig && currentCustomConfig.preferences) {
        currentCustomConfig.preferences.forEach(pref => {
            const el = document.getElementById(pref.id);
            if (el && el.checked) {
                preferences.push(pref.label);
            }
        });
    }

    const note = document.getElementById('cust-note')?.value.trim();

    const optionParts = [];
    if (customPortionName) optionParts.push(customPortionName);
    if (extras.length > 0) optionParts.push(extras.join(', '));
    if (preferences.length > 0) optionParts.push(preferences.join(', '));
    if (note) optionParts.push(`Not: "${note}"`);

    const optionText = optionParts.filter(Boolean).join(' • ');

    const state = GustoState.get();
    if (state.cart && state.cart.length > 0) {
        const existingRestId = state.cart[0].rId || state.selectedRestId;
        if (existingRestId && existingRestId !== item.rId) {
            const oldRest = state.restaurants.find(r => r.id === existingRestId);
            const newRest = state.restaurants.find(r => r.id === item.rId);
            const msg = `Sepetinizde "${oldRest ? oldRest.name : 'başka bir restoran'}" restoranından ürünler var. Sepeti temizleyip "${newRest ? newRest.name : 'bu restoran'}" ürününü eklemek ister misiniz?`;
            if (typeof confirm === 'function' && !confirm(msg)) {
                closeCustomizer();
                return;
            }
            state.cart = [];
            state.activeCoupon = null;
        }
    }

    state.selectedRestId = item.rId;
    state.cart.push({
        id: item.id + '_' + Date.now(),
        itemId: item.id,
        rId: item.rId,
        name: item.name,
        img: item.img || '',
        price: unit,
        qty: customQty,
        options: optionText
    });

    GustoState.persist();
    closeCustomizer();
    updateCartUI();
    pulseElement(document.getElementById('btn-nav-cart'));
    pulseElement(document.getElementById('cart-badge'));
    GustoAudio.play('add');
    showToast('🛒', `${item.name} sepete eklendi!`, 'success');
}

// ==========================================
// SEPET YÖNETİMİ & GEL-AL (TAKEAWAY) ENTEGRASYONU
// ==========================================
function switchDeliveryMode(mode, silent = false) {
    GustoState.setDeliveryMode(mode);
    const isTakeaway = mode === 'takeaway';

    const btnCourier = document.getElementById('btn-del-courier');
    const btnTakeaway = document.getElementById('btn-del-takeaway');
    if (btnCourier) btnCourier.className = 'del-mode-btn ' + (!isTakeaway ? 'active' : '');
    if (btnTakeaway) btnTakeaway.className = 'del-mode-btn ' + (isTakeaway ? 'active' : '');

    const ind = document.getElementById('cart-mode-indicator');
    if (ind) {
        ind.innerHTML = isTakeaway
            ? '<i class="fa-solid fa-person-walking"></i> Gel-Al (%20 İndirimli)'
            : '<i class="fa-solid fa-motorcycle"></i> Adrese Teslim';
    }

    const notice = document.getElementById('checkout-del-mode-notice');
    if (notice) notice.style.display = isTakeaway ? 'flex' : 'none';

    updateCartUI();
    renderRestaurantGrid(GustoState.get().activeCategory);
    if (!silent) {
        GustoAudio.play('add');
        GustoMap.drawMap('customer-map-canvas');
        showToast(isTakeaway ? '🚶' : '🛵', isTakeaway ? 'Gel-Al moduna geçildi: 0 ₺ teslimat + %20 indirim aktif!' : 'Adrese kurye teslimat modu aktif.', 'success');
    }
}

function changeCartQty(cartId, delta) {
    const state = GustoState.get();
    const cart = state.cart;
    const idx = cart.findIndex(c => c.id === cartId);
    if (idx < 0) return;
    cart[idx].qty += delta;
    if (cart[idx].qty <= 0) {
        cart.splice(idx, 1);
    }
    if (cart.length === 0) {
        state.activeCoupon = null;
        state.selectedRestId = null;
    }
    GustoState.persist();
    updateCartUI();
    GustoAudio.play('add');
}

function updateCartUI() {
    const state = GustoState.get();
    const cart = state.cart;
    const r = state.selectedRestId ? state.restaurants.find(x => x.id === state.selectedRestId) : null;
    const tot = cart.reduce((s, c) => s + c.qty, 0);
    const sub = cart.reduce((s, c) => s + c.price * c.qty, 0);

    let discount = 0;
    let discCode = state.activeCoupon || '';

    if (state.activeCoupon === 'CRAVELO30' || state.activeCoupon === 'KURYO30' || state.activeCoupon === 'TGO30' || state.activeCoupon === 'GUSTO30') discount = sub * 0.30;
    else if (state.activeCoupon === 'PIZZA50' && cart.some(c => c.price > 180)) discount = 50.0;
    else if (state.activeCoupon === 'BURGERFEST') discount = 35.0;
    else if (state.activeCoupon === 'HEMEN10') discount = 10.0;

    // Gel-Al Modu Kontrolü: 0 ₺ Teslimat + Otomatik %20 İndirim
    const isTakeaway = state.deliveryMode === 'takeaway';
    if (isTakeaway) {
        const takeawayDisc = sub * 0.20;
        if (takeawayDisc > discount) {
            discount = takeawayDisc;
            discCode = 'GELAL20 (%20 Gel-Al)';
        }
    }

    const distKm = r ? getRealDistanceKm(r, state.customerLocation) : 0;
    const del = isTakeaway ? 0 : (cart.length ? (r ? Math.max(15, Math.round(distKm * 14)) : 0) : 0);
    const eta = isTakeaway ? 15 : (r ? (parseInt(r.time.split('-')[0], 10) + Math.max(2, Math.round(distKm * 3))) : 0);
    const finalTotal = Math.max(0, sub - discount + del);

    const badge = document.getElementById('cart-badge');
    if (badge) badge.innerText = tot;

    const navCartBadge = document.getElementById('nav-cart-count');
    if (navCartBadge) navCartBadge.innerText = tot;

    const fromEl = document.getElementById('cart-from');
    if (fromEl) fromEl.innerText = r ? `${r.emoji} ${r.name}` : '—';

    // Alt Yüzen Sepet Barı
    const floatBar = document.getElementById('floating-cart-bar');
    if (floatBar) {
        if (cart.length > 0 && !state.activeOrder && state.currentRole === 'customer') {
            floatBar.style.display = 'flex';
            const fc = document.getElementById('float-cart-count');
            const ft = document.getElementById('float-cart-text');
            const ftot = document.getElementById('float-cart-total');
            if (fc) fc.innerText = tot;
            if (ft) ft.innerText = `${tot} lezzet • ${r ? r.name : 'Cravelo Sepet'}`;
            if (ftot) ftot.innerText = finalTotal.toFixed(2) + ' ₺';
        } else {
            floatBar.style.display = 'none';
        }
    }

    const emptyBox = document.getElementById('cart-empty');
    const itemsBox = document.getElementById('cart-items');
    const ecoArea = document.getElementById('cart-eco-area');
    const couponBox = document.getElementById('cart-coupon-area');
    const summBox = document.getElementById('cart-summ');
    const btnOrder = document.getElementById('btn-order');

    if (!cart.length) {
        if (emptyBox) emptyBox.style.display = 'flex';
        if (itemsBox) itemsBox.style.display = 'none';
        if (ecoArea) ecoArea.style.display = 'none';
        if (couponBox) couponBox.style.display = 'none';
        if (summBox) summBox.style.display = 'none';
        if (btnOrder) btnOrder.disabled = true;
    } else {
        if (emptyBox) emptyBox.style.display = 'none';
        if (ecoArea) ecoArea.style.display = 'block';
        if (itemsBox) {
            itemsBox.style.display = 'block';
            itemsBox.innerHTML = cart.map(c => `
                <div class="cart-item" style="background:var(--surface-2);border:1px solid var(--border);border-radius:14px;padding:11px 12px;margin-bottom:8px;">
                    <div class="cart-item-top" style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                        <div class="cart-item-name" style="font-weight:800;font-size:13.5px;color:var(--text);flex:1;min-width:0;">${c.name}</div>
                        <div class="qty-ctrl">
                            <button type="button" class="qty-btn" onclick="changeCartQty('${c.id}', -1)" title="Azalt">−</button>
                            <span class="qty-val">${c.qty}</span>
                            <button type="button" class="qty-btn" onclick="changeCartQty('${c.id}', +1)" title="Arttır">+</button>
                        </div>
                        <div class="cart-item-price" style="font-weight:900;font-size:13.5px;color:var(--primary);min-width:62px;text-align:right;">${(c.price * c.qty).toFixed(2)} ₺</div>
                    </div>
                    ${c.options ? `<div class="cart-item-options" style="font-size:11.5px;color:var(--text-3);margin-top:5px;line-height:1.35;">${c.options}</div>` : ''}
                </div>
            `).join('');
        }
        if (couponBox) couponBox.style.display = 'flex';
        if (summBox) {
            summBox.style.display = 'block';
            const sSub = document.getElementById('s-sub');
            const sDiscRow = document.getElementById('s-discount-row');
            const sDisc = document.getElementById('s-disc');
            const sDel = document.getElementById('s-del');
            const sTime = document.getElementById('s-time');
            const sTot = document.getElementById('s-total');
            if (sSub) sSub.innerText = sub.toFixed(2) + ' ₺';
            if (sDiscRow) {
                if (discount > 0) {
                    sDiscRow.style.display = 'flex';
                    if (sDisc) sDisc.innerText = `-${discount.toFixed(2)} ₺ (${discCode})`;
                } else {
                    sDiscRow.style.display = 'none';
                }
            }
            if (sDel) sDel.innerText = isTakeaway ? '0.00 ₺ (Gel-Al Ücretsiz)' : del.toFixed(2) + ' ₺';
            if (sTime) sTime.innerText = isTakeaway ? '~15 dk (Restorandan Alım)' : `~${eta} dk (${distKm.toFixed(1)} km)`;
            if (sTot) sTot.innerText = finalTotal.toFixed(2) + ' ₺';
        }
        if (btnOrder) btnOrder.disabled = false;
    }

    // --- BAĞIMSIZ SEPET MODALI SENKRONİZASYONU ---
    const mEmpty = document.getElementById('modal-cart-empty');
    const mContent = document.getElementById('modal-cart-content');
    const mItems = document.getElementById('modal-cart-items');
    const mFrom = document.getElementById('modal-cart-from');
    const mBadge = document.getElementById('modal-cart-badge');
    const mSub = document.getElementById('m-sub');
    const mDiscRow = document.getElementById('m-discount-row');
    const mDisc = document.getElementById('m-disc');
    const mDel = document.getElementById('m-del');
    const mTotal = document.getElementById('m-total');

    if (mFrom) mFrom.innerText = r ? `${r.emoji} ${r.name} • ${r.neighborhood || 'Kadıköy'}` : '—';
    if (mBadge) mBadge.innerText = `${tot} Ürün`;

    if (!cart.length) {
        if (mEmpty) mEmpty.style.display = 'block';
        if (mContent) mContent.style.display = 'none';
    } else {
        if (mEmpty) mEmpty.style.display = 'none';
        if (mContent) mContent.style.display = 'block';
        if (mItems) {
            mItems.innerHTML = cart.map(c => {
                const fullItem = state.menuItems.find(m => m.id === c.itemId || m.name === c.name);
                const imgUrl = c.img || fullItem?.img || '';
                return `
                    <div class="cart-item" style="padding:12px;background:var(--surface-2);border-radius:16px;margin-bottom:10px;border:1px solid var(--border);display:flex;gap:12px;align-items:center;">
                        ${imgUrl ? `<img src="${imgUrl}" alt="${c.name}" style="width:52px;height:52px;border-radius:12px;object-fit:cover;flex-shrink:0;">` : ''}
                        <div style="flex:1;min-width:0;">
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                                <div style="min-width:0;">
                                    <div class="cart-item-name" style="font-weight:800;color:var(--text);font-size:14px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${c.name}</div>
                                    <div style="font-size:12px;color:var(--primary);font-weight:700;">${c.price.toFixed(2)} ₺ / adet</div>
                                </div>
                                <div class="qty-ctrl" style="display:flex;align-items:center;gap:6px;">
                                    <button type="button" class="qty-btn" onclick="changeCartQty('${c.id}', -1)" title="Azalt">−</button>
                                    <span class="qty-val" style="min-width:18px;text-align:center;font-weight:800;">${c.qty}</span>
                                    <button type="button" class="qty-btn" onclick="changeCartQty('${c.id}', +1)" title="Arttır">+</button>
                                </div>
                                <div class="cart-item-price" style="font-weight:900;color:var(--text);font-size:14.5px;min-width:68px;text-align:right;">${(c.price * c.qty).toFixed(2)} ₺</div>
                            </div>
                            ${c.options ? `<div class="cart-item-options" style="font-size:11.5px;color:var(--text-3);margin-top:4px;line-height:1.35;">${c.options}</div>` : ''}
                        </div>
                    </div>
                `;
            }).join('');
        }
        if (mSub) mSub.innerText = sub.toFixed(2) + ' ₺';
        if (mDiscRow) {
            if (discount > 0) {
                mDiscRow.style.display = 'flex';
                if (mDisc) mDisc.innerText = `-${discount.toFixed(2)} ₺ (${discCode})`;
            } else {
                mDiscRow.style.display = 'none';
            }
        }
        if (mDel) mDel.innerText = isTakeaway ? '0.00 ₺ (Gel-Al Ücretsiz)' : del.toFixed(2) + ' ₺';
        if (mTotal) mTotal.innerText = finalTotal.toFixed(2) + ' ₺';
    }
}

// ==========================================
// BAĞIMSIZ SEPET MODALI KONTROLLERİ
// ==========================================
function openCartModal() {
    updateCartUI();
    const overlay = document.getElementById('cart-overlay');
    if (overlay) overlay.style.display = 'flex';
}

function closeCartModal() {
    const overlay = document.getElementById('cart-overlay');
    if (overlay) overlay.style.display = 'none';
}

function clearCartConfirm() {
    const state = GustoState.get();
    if (!state.cart || !state.cart.length) return;
    if (confirm('Sepetinizdeki tüm ürünleri çıkarmak istediğinize emin misiniz?')) {
        state.cart = [];
        state.selectedRestId = null;
        GustoState.persist();
        updateCartUI();
        showToast('🗑️', 'Sepetiniz tamamen temizlendi.');
    }
}

// ==========================================
// GÜVENLİ ÇIKIŞ YAP & GİRİŞ YÖNLENDİRMESİ
// ==========================================
function logoutUser(e) {
    if (e && e.preventDefault) e.preventDefault();

    // Çıkış öncesi kullanıcının kişisel sipariş kasasını (username bazlı) güvenli sakla
    try {
        const st = (typeof GustoState !== 'undefined' && GustoState.get) ? GustoState.get() : null;
        const sess = (typeof getSessionObject === 'function') ? getSessionObject() : null;
        const uname = (window.ACTIVE_SESSION && window.ACTIVE_SESSION.username)
            || (sess && sess.username)
            || (st && st.currentUser && st.currentUser.username)
            || (st && st.ownerUsername);
        if (uname && typeof GustoState !== 'undefined' && typeof GustoState.saveUserVault === 'function') {
            GustoState.saveUserVault(uname);
        }
    } catch (err) {}

    if (window.GustoState && GustoState.logout) {
        GustoState.logout();
    }

    const finalizeLogout = () => {
        try {
            localStorage.removeItem('cravelo_session');
            localStorage.removeItem('cravelo_state_v2');
            localStorage.removeItem('kuryo_session');
            localStorage.removeItem('kuryo_state_v2');
            localStorage.removeItem('gustogo_session');
            localStorage.removeItem('gustogo_state_v2');
            localStorage.removeItem('tgo_session');
            localStorage.removeItem('tgo_state_v2');
            sessionStorage.clear();
        } catch (err) {}

        const base = window.BASE_PATH || '';
        const isStaticHtml = (typeof window !== 'undefined' && window.location && (
            window.location.protocol === 'file:' ||
            (window.location.pathname && window.location.pathname.endsWith('.html'))
        ));

        if (window.ACTIVE_SESSION && window.ACTIVE_SESSION.role) {
            window.location.href = base + '/logout';
        } else {
            if (typeof fetch === 'function') {
                fetch(base + '/api?action=logout', { method: 'POST' }).catch(() => {});
            }
            if (isStaticHtml) {
                window.location.href = 'login.html';
            } else {
                window.location.href = base + '/login';
            }
        }
    };

    // Eğer saniyeler önce verilen bir siparişin sunucuya (orders.json) kaydı sürüyorsa tamamlanmasını bekle
    if (typeof craveloPendingOrderSync !== 'undefined' && craveloPendingOrderSync) {
        Promise.race([
            craveloPendingOrderSync,
            new Promise(resolve => setTimeout(resolve, 900))
        ]).finally(finalizeLogout);
    } else {
        finalizeLogout();
    }
}

// ==========================================
// CHECKOUT & ÖDEME MODALI
// ==========================================
let selectedPayMethod = 'cc';
let selectedTip = 0;

function scrollToCartOrOpen() {
    const state = GustoState.get();
    const menuSec = document.getElementById('menu-section');
    if (menuSec && menuSec.style.display !== 'none') {
        const cartEl = (typeof document.querySelector === 'function') ? document.querySelector('.cart-box') : null;
        if (cartEl) {
            cartEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
            cartEl.style.boxShadow = '0 0 28px rgba(255, 87, 34, 0.45)';
            setTimeout(() => { cartEl.style.boxShadow = ''; }, 1400);
            return;
        }
    }

    if (!state.cart || state.cart.length === 0) {
        showToast('🛒', 'Sepetiniz henüz boş! Lütfen menüden lezzetli bir yemek seçiniz.');
        showRestaurants();
    } else {
        openCheckoutModal();
    }
}

function openCheckoutModal() {
    const state = GustoState.get();
    if (!state.cart || !state.cart.length) {
        showToast('⚠️', 'Sepetiniz boş! Ödeme yapabilmek için lütfen önce sepete ürün ekleyiniz.', 'warn');
        return;
    }

    const notice = document.getElementById('checkout-del-mode-notice');
    if (notice) notice.style.display = (state.deliveryMode === 'takeaway') ? 'flex' : 'none';

    calcCheckoutTotal();
    const overlay = document.getElementById('checkout-overlay');
    if (overlay) overlay.style.display = 'flex';
}

function closeCheckoutModal() {
    const overlay = document.getElementById('checkout-overlay');
    if (overlay) overlay.style.display = 'none';
}

function selectPayMethod(m) {
    selectedPayMethod = m;
    ['cc', 'cod', 'wallet'].forEach(id => {
        const el = document.getElementById('pay-card-' + id);
        if (el) el.className = 'pay-card ' + (id === m ? 'active' : '');
    });
    const ccForm = document.getElementById('cc-form-area');
    if (ccForm) ccForm.style.display = (m === 'cc' ? 'block' : 'none');
    calcCheckoutTotal();
}

function setTip(amt) {
    selectedTip = amt;
    [0, 10, 20, 50].forEach(a => {
        const el = document.getElementById('tip-' + a);
        if (el) el.className = 'tip-pill ' + (a === amt ? 'active' : '');
    });
    calcCheckoutTotal();
}

function selectInstruction(instText, el) {
    GustoState.get().selectedInstruction = instText;
    const chips = document.querySelectorAll('#instruction-chips .inst-chip');
    chips.forEach(c => c.classList.remove('active'));
    if (el) el.classList.add('active');
    GustoAudio.play('add');
}

function setScheduledTime(timeSlot, el) {
    GustoState.get().scheduledTime = timeSlot;
    const pills = document.querySelectorAll('#sched-pills .sched-pill');
    pills.forEach(p => p.classList.remove('active'));
    if (el) el.classList.add('active');
    GustoAudio.play('add');
}

function toggleEcoFriendly(checked) {
    GustoState.get().isEcoFriendly = checked;
    const c1 = document.getElementById('cart-eco-check');
    const c2 = document.getElementById('eco-choice');
    if (c1) c1.checked = checked;
    if (c2) c2.checked = checked;
    showToast('🌱', checked ? 'Doğa dostu yeşil teslimat seçildi (-140g CO2)!' : 'Plastik servis seti eklendi.', 'success');
}

function calcCheckoutTotal() {
    const state = GustoState.get();
    const r = state.restaurants.find(x => x.id === (state.selectedRestId || 1)) || state.restaurants[0];
    const sub = state.cart.reduce((s, c) => s + c.price * c.qty, 0);
    const distKm = getRealDistanceKm(r, state.customerLocation);
    const isTakeaway = state.deliveryMode === 'takeaway';
    const del = isTakeaway ? 0 : Math.max(15, Math.round(distKm * 14));

    let discount = 0;
    if (state.activeCoupon === 'CRAVELO30' || state.activeCoupon === 'KURYO30' || state.activeCoupon === 'TGO30' || state.activeCoupon === 'GUSTO30') discount = sub * 0.30;
    else if (state.activeCoupon === 'PIZZA50' && state.cart.some(c => c.price > 180)) discount = 50.0;
    else if (state.activeCoupon === 'BURGERFEST') discount = 35.0;
    else if (state.activeCoupon === 'HEMEN10') discount = 10.0;

    if (isTakeaway) {
        const takeawayDisc = sub * 0.20;
        if (takeawayDisc > discount) discount = takeawayDisc;
    }

    let walletDiscount = 0;
    if (selectedPayMethod === 'wallet') {
        walletDiscount = Math.min(sub + del - discount, state.userPoints / 10);
    }

    const finalTot = Math.max(0, sub - discount + del - walletDiscount + (isTakeaway ? 0 : selectedTip));
    const totalEl = document.getElementById('checkout-final-total');
    if (totalEl) totalEl.innerText = finalTotalText(finalTot);

    // Opsiyonel checkout özet kırılımı varsa doldur
    const chkSummary = document.getElementById('checkout-summary-breakdown');
    if (chkSummary) {
        chkSummary.innerHTML = `
            <div style="display:flex;justify-content:space-between;font-size:12.5px;color:var(--text-2);margin-bottom:4px;"><span>Ürünler (${state.cart.reduce((s, c) => s + c.qty, 0)} adet)</span><strong>${sub.toFixed(2)} ₺</strong></div>
            ${discount > 0 ? `<div style="display:flex;justify-content:space-between;font-size:12.5px;color:var(--success);margin-bottom:4px;"><span>İndirim</span><strong>-${discount.toFixed(2)} ₺</strong></div>` : ''}
            <div style="display:flex;justify-content:space-between;font-size:12.5px;color:var(--text-2);margin-bottom:4px;"><span>Teslimat (${isTakeaway ? 'Gel-Al' : distKm.toFixed(1) + ' km'})</span><strong>${del.toFixed(2)} ₺</strong></div>
            ${(!isTakeaway && selectedTip > 0) ? `<div style="display:flex;justify-content:space-between;font-size:12.5px;color:var(--success);margin-bottom:4px;"><span>Kurye Bahşişi</span><strong>+${selectedTip.toFixed(2)} ₺</strong></div>` : ''}
            ${walletDiscount > 0 ? `<div style="display:flex;justify-content:space-between;font-size:12.5px;color:var(--success);"><span>Cüzdan Kullanımı</span><strong>-${walletDiscount.toFixed(2)} ₺</strong></div>` : ''}
        `;
    }

    return { sub, discount, del, walletDiscount, finalTot, dist: distKm, isTakeaway };
}

function finalTotalText(val) {
    return `${Number(val || 0).toFixed(2)} ₺`;
}

function confirmCheckoutAndPlaceOrder() {
    closeCheckoutModal();
    const state = GustoState.get();
    state.orderCounter++;

    const r = state.restaurants.find(x => x.id === (state.selectedRestId || 1)) || state.restaurants[0];
    const { sub, discount, del, walletDiscount, finalTot, isTakeaway } = calcCheckoutTotal();

    // AKILLI HAVERSINE + İŞ YÜKÜ KURYE ATAMASI (Gel-Al değilse)
    let bestCourier = { id: 0, name: "Restorandan Gel-Al (Müşteri)", score: 0, distKm: 0 };
    if (!isTakeaway) {
        bestCourier = GustoState.findBestCourier(r.x, r.y, r.lat, r.lon);
        const cObj = state.couriers.find(c => c.id === bestCourier.id);
        if (cObj) {
            cObj.active++;
            cObj.tips += selectedTip;
            cObj.earnings += (del * 0.7) + selectedTip;
        }
    }

    if (selectedPayMethod === 'wallet') {
        state.userPoints = Math.max(0, state.userPoints - Math.floor(walletDiscount * 10));
        const navRole = document.getElementById('nav-role');
        if (navRole && state.currentRole === 'customer') {
            navRole.innerHTML = `<i class="fa-solid fa-coins"></i> ${state.userPoints} Puan`;
        }
        const accPts = document.getElementById('acc-points');
        if (accPts) accPts.innerText = `${state.userPoints} Cravelo Puan (${(state.userPoints / 10).toFixed(2)} ₺)`;
        const walletDisp = document.getElementById('acc-wallet-display');
        if (walletDisp) walletDisp.innerText = `${state.userPoints} Puan (${(state.userPoints / 10).toFixed(2)} ₺)`;
    }

    // Mevcut bir aktif sipariş varsa ezilmemesi için geçmiş/sipariş listesine güvenle aktar
    if (state.activeOrder) {
        GustoState.stopSimulation();
        if (typeof GustoMap !== 'undefined' && GustoMap.stopCourierAnimation) {
            GustoMap.stopCourierAnimation();
        }
        const prevExists = state.orderHistory.some(o => o && o.id === state.activeOrder.id);
        if (!prevExists) {
            state.orderHistory.unshift({ ...state.activeOrder });
        }
    }

    const customNote = document.getElementById('delivery-custom-note')?.value.trim() || '';

    const restObj = r || state.restaurants[0] || { id: 1, name: 'Lezzet Kebap Salonu' };
    const restName = (restObj && restObj.name) ? restObj.name : 'Lezzet Kebap Salonu';
    const courierTitle = (bestCourier && bestCourier.name) ? bestCourier.name : 'Veli Demir';
    const grossTotal = Math.max(0, sub - discount + del + (isTakeaway ? 0 : selectedTip));

    state.activeOrder = {
        id: state.orderCounter,
        restaurant: restName,
        customerName: state.currentUser?.fullName || 'Ahmet Yılmaz',
        rId: restObj.id || 1,
        courier: courierTitle,
        courierId: isTakeaway ? null : (bestCourier.id || 1),
        courierScore: bestCourier.score || 0,
        courierDistKm: bestCourier.distKm || 0,
        items: state.cart.map(c => ({ name: c.name || 'Menü Lezzeti', qty: c.qty || 1, price: c.price || 150, options: c.options || '' })),
        subtotal: sub,
        discount,
        walletDiscount,
        grossTotal,
        delFee: del,
        total: finalTot,
        tip: isTakeaway ? 0 : selectedTip,
        payMethod: selectedPayMethod,
        deliveryMode: state.deliveryMode,
        instruction: state.selectedInstruction || 'Zile Basma Bebek Uyuyor 👶',
        customNote,
        scheduledTime: state.scheduledTime || 'Hemen Gelsin (~25 dk)',
        isEco: state.isEcoFriendly,
        status: 'alindi',
        createdAt: new Date().toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit' }),
        createdTs: Date.now(),
        dateStr: new Date().toLocaleDateString('tr-TR')
    };

    GustoState.logAudit(
        state.orderCounter,
        null,
        'alindi',
        `${state.currentUser?.fullName || 'Müşteri'} (Müşteri)`,
        `Sipariş verildi. Mod: ${isTakeaway ? 'GEL-AL' : 'KURYE TESLİMAT'}, Ödeme: ${selectedPayMethod.toUpperCase()}, Kurye: ${bestCourier.name}`
    );

    state.cart = [];
    state.activeCoupon = null;
    GustoState.persist();

    updateCartUI();
    updateOrderTrackerUI();

    if (!isTakeaway) {
        GustoMap.startCourierAnimation(bestCourier, r, state.customerLocation);
    }

    GustoAudio.play('order');
    showToast('🎉', `Sipariş #ORD-${state.orderCounter} alındı! ${isTakeaway ? 'Restorandan Gel-Al Hazırlanıyor' : 'Atanan Kurye: ' + bestCourier.name}`, 'success');

    closeCheckoutModal();
    openOrdersTab();

    if (state.isSimulationEnabled) {
        GustoState.startSimulation(handleSimulationStep);
    }

    // Aşama 2 Madde 5: HTTP sunucu ortamında siparişi gerçek JSON Repository'ye (flock) kaydet
    if (typeof craveloSyncCreateOrder === 'function') {
        craveloSyncCreateOrder(state.activeOrder, state.customerLocation);
    }
}

// ==========================================
// CANLI SİPARİŞ TAKİP STEPPER & SİMÜLASYON
// ==========================================
function handleSimulationStep(newStatus) {
    updateOrderTrackerUI();
    GustoAudio.play('status');
    GustoMap.drawMap('customer-map-canvas');
    GustoMap.drawMap('admin-map-canvas');

    if (newStatus === 'teslim_edildi') {
        GustoAudio.play('deliver');
        setTimeout(() => {
            const finished = { ...GustoState.get().activeOrder };
            GustoState.get().orderHistory.unshift(finished);
            GustoState.get().activeOrder = null;
            GustoState.persist();
            updateOrderTrackerUI();
            openRatingModal();
            GustoMap.drawMap('customer-map-canvas');
            GustoMap.drawMap('admin-map-canvas');
        }, 1500);
    }
}

function toggleSimMode() {
    const state = GustoState.get();
    state.isSimulationEnabled = !state.isSimulationEnabled;
    const txt = document.getElementById('sim-indicator');
    const btn = document.getElementById('btn-toggle-sim');

    if (state.isSimulationEnabled) {
        if (txt) txt.innerHTML = '<i class="fa-solid fa-circle-dot" style="color:var(--success);"></i> Canlı GPS Kurye Takibi Aktif';
        if (btn) btn.innerHTML = '<i class="fa-solid fa-arrows-rotate"></i> Canlı Konum';
        showToast('📍', 'Kurye canlı GPS takibi aktif.', 'success');
        if (state.activeOrder && state.activeOrder.status !== 'teslim_edildi') {
            GustoState.startSimulation(handleSimulationStep);
        }
    } else {
        if (txt) txt.innerHTML = '<i class="fa-solid fa-pause" style="color:var(--yellow);"></i> GPS Takibi Duraklatıldı';
        if (btn) btn.innerHTML = '<i class="fa-solid fa-play"></i> Takibi Sürdür';
        GustoState.stopSimulation();
        showToast('✋', 'Kurye GPS takibi geçici olarak duraklatıldı.', 'warn');
    }
}

function cancelOrder() {
    const active = GustoState.get().activeOrder;
    if (active && active.serverOrderId && typeof craveloSyncCancelOrder === 'function') {
        craveloSyncCancelOrder(active.serverOrderId);
    }
    const res = GustoState.cancelActiveOrder();
    if (!res.success) {
        GustoAudio.play('cancel');
        showToast('🚫', res.message, 'danger');
        return;
    }
    GustoAudio.play('cancel');
    updateOrderTrackerUI();
    GustoMap.drawMap('customer-map-canvas');
    GustoMap.drawMap('admin-map-canvas');
    showToast('❌', res.message, 'warn');
}

function updateOrderTrackerUI() {
    const activeOrder = GustoState.get().activeOrder;
    const fw = { alindi: '0%', hazirlaniyor: '25%', hazir: '50%', yolda: '75%', teslim_edildi: '100%', iptal: '0%' };
    const tx = {
        alindi: { t: 'Siparişiniz Alındı! 📝', s: 'Restoran onaylayıp mutfağa aktaracak…' },
        hazirlaniyor: { t: 'Mutfakta Hazırlanıyor 👨‍🍳', s: 'Yemeğiniz taze taze pişiriliyor.' },
        hazir: { t: 'Sipariş Hazır! 📦', s: 'Kurye paketi teslim almak üzere restorana ulaştı.' },
        yolda: { t: 'Kurye Yolda! 🚀', s: 'Siparişiniz kapınıza doğru hızla geliyor.' },
        teslim_edildi: { t: 'Teslim Edildi! 🎉', s: 'Afiyet olsun, Cravelo\'yu tercih ettiğiniz için teşekkürler.' },
        iptal: { t: 'Sipariş İptal Edildi ❌', s: 'Yeni bir sipariş oluşturabilirsiniz.' }
    };

    const chatBtn = document.getElementById('btn-chat-courier');
    const stepOrder = ['alindi', 'hazirlaniyor', 'hazir', 'yolda', 'teslim_edildi'];
    const thermalBox = document.getElementById('thermal-box');
    const thermalTemp = document.getElementById('thermal-temp');
    const thermalFill = document.getElementById('thermal-fill');
    const ecoBadge = document.getElementById('eco-saved-badge');
    const addonCard = document.getElementById('addon-deal-card');

    if (!activeOrder) {
        stepOrder.forEach(s => {
            const el = document.getElementById('sn-' + s);
            if (el) el.className = 'step';
        });
        const fill = document.getElementById('step-fill');
        if (fill) fill.style.width = '0%';

        const numBadge = document.getElementById('order-number-badge');
        if (numBadge) numBadge.classList.add('hidden');

        const trackerText = document.getElementById('tracker-text');
        if (trackerText) trackerText.innerText = 'Henüz aktif siparişiniz bulunmuyor.';
        const trackerSub = document.getElementById('tracker-sub');
        if (trackerSub) trackerSub.innerText = 'Menüden dilediğiniz lezzetleri sepete ekleyebilirsiniz.';
        const cancelBtn = document.getElementById('btn-cancel');
        if (cancelBtn) cancelBtn.disabled = true;
        if (chatBtn) chatBtn.classList.add('hidden');
        if (thermalBox) thermalBox.style.display = 'none';
        if (ecoBadge) ecoBadge.style.display = 'none';
        if (addonCard) addonCard.style.display = 'none';

        const navBadge = document.getElementById('nav-orders-badge');
        if (navBadge) navBadge.style.display = 'none';

        const menuBanner = document.getElementById('active-order-floating-banner');
        if (menuBanner) menuBanner.style.display = 'none';

        const accTabOrders = document.getElementById('acc-tab-orders');
        const accOverlay = document.getElementById('account-overlay');
        if (accOverlay && accOverlay.style.display !== 'none' && accTabOrders && accTabOrders.style.display !== 'none') {
            renderAccountOrders();
        }
        return;
    }

    const s = activeOrder.status;
    const fill = document.getElementById('step-fill');
    if (fill) fill.style.width = fw[s] || '0%';

    const numBadge = document.getElementById('order-number-badge');
    if (numBadge) {
        numBadge.classList.remove('hidden');
        numBadge.innerText = '#ORD-' + activeOrder.id + (activeOrder.deliveryMode === 'takeaway' ? ' (Gel-Al)' : '');
    }

    const trackerText = document.getElementById('tracker-text');
    if (trackerText) trackerText.innerText = tx[s]?.t || s;
    const trackerSub = document.getElementById('tracker-sub');
    if (trackerSub) trackerSub.innerText = tx[s]?.s || '';

    const idx = stepOrder.indexOf(s);
    stepOrder.forEach((step, i) => {
        const el = document.getElementById('sn-' + step);
        if (!el) return;
        if (i < idx) el.className = 'step done';
        else if (i === idx) el.className = 'step active';
        else el.className = 'step';
    });

    const cancelBtn = document.getElementById('btn-cancel');
    if (cancelBtn) cancelBtn.disabled = (s !== 'alindi');

    if (chatBtn) {
        if (['hazir', 'yolda'].includes(s) && activeOrder.deliveryMode !== 'takeaway') chatBtn.classList.remove('hidden');
        else chatBtn.classList.add('hidden');
    }

    // 1. Canlı Sıcaklık & Tazelik Simülatörü
    if (thermalBox) {
        thermalBox.style.display = 'block';
        const thermalLevels = {
            alindi: { temp: '85°C (Mutfak Fırını)', width: '92%' },
            hazirlaniyor: { temp: '88°C (Tavada Pişiyor 🔥)', width: '96%' },
            hazir: { temp: '82°C (Termal Çantada 🎒)', width: '88%' },
            yolda: { temp: '74°C (Sıcaklığını Koruyor 🛵)', width: '78%' },
            teslim_edildi: { temp: '68°C (İdeal Tüketim Sıcaklığı 😋)', width: '70%' },
            iptal: { temp: 'Oda Sıcaklığı', width: '20%' }
        };
        const curLevel = thermalLevels[s] || thermalLevels.alindi;
        if (thermalTemp) thermalTemp.innerText = curLevel.temp;
        if (thermalFill) thermalFill.style.width = curLevel.width;
    }

    // 2. Doğa Dostu Yeşil Karbon Tasarrufu Rozeti
    if (ecoBadge) {
        ecoBadge.style.display = activeOrder.isEco ? 'flex' : 'none';
    }

    // 3. Yol Üstü Fırsatı (Roadside Add-on Banner)
    if (addonCard) {
        if (['hazirlaniyor', 'yolda'].includes(s) && !activeOrder.addon && activeOrder.deliveryMode !== 'takeaway' && typeof ADDON_DEALS !== 'undefined' && ADDON_DEALS[0]) {
            addonCard.style.display = 'block';
            const deal = ADDON_DEALS[0];
            const titleEl = document.getElementById('addon-deal-title');
            const priceEl = document.getElementById('addon-deal-price');
            const dealRest = deal.restName || deal.restaurantName || 'Tatlı Dünyası';
            const dealItem = deal.name || deal.itemName || 'San Sebastian Cheesecake';
            if (titleEl) titleEl.innerText = `${dealRest}'ndan ${dealItem}`;
            if (priceEl) priceEl.innerText = `+${deal.price.toFixed(2)} ₺`;
        } else {
            addonCard.style.display = 'none';
        }
    }

    // Nav bar sipariş rozeti
    const navBadge = document.getElementById('nav-orders-badge');
    if (navBadge) {
        if (!['teslim_edildi', 'iptal'].includes(s)) {
            navBadge.style.display = 'inline-block';
            navBadge.innerText = '1';
        } else {
            navBadge.style.display = 'none';
        }
    }

    // Menü ekranındaki aktif sipariş bildirim bandı
    const menuBanner = document.getElementById('active-order-floating-banner');
    if (menuBanner) {
        if (!['teslim_edildi', 'iptal'].includes(s)) {
            menuBanner.style.display = 'block';
            const mTitle = document.getElementById('menu-active-order-title');
            if (mTitle) mTitle.innerText = `#ORD-${activeOrder.id} • ${activeOrder.restaurant || 'Restoran'}`;
            const mStatus = document.getElementById('menu-active-order-status');
            if (mStatus) mStatus.innerText = getStatusMeta(activeOrder.status).label.toUpperCase();
            const mCourier = document.getElementById('menu-active-order-courier');
            if (mCourier) mCourier.innerText = `Kuryeniz (${activeOrder.courier || 'Veli Demir'}) siparişinizi ulaştırmak üzere rotada.`;
        } else {
            menuBanner.style.display = 'none';
        }
    }

    // Siparişlerim sekmesi açıksa anında yeniden çiz
    const accTabOrders = document.getElementById('acc-tab-orders');
    const accOverlay = document.getElementById('account-overlay');
    if (accOverlay && accOverlay.style.display !== 'none' && accTabOrders && accTabOrders.style.display !== 'none') {
        renderAccountOrders();
    }

    // Role göre ilgili paneli güncelle
    const role = GustoState.get().currentRole;
    if (role === 'restaurant') renderResPanel();
    if (role === 'courier') renderCourierPanel();
    if (role === 'admin') {
        renderAdminCouriers();
        renderAdminAudit();
    }
}

// ==========================================
// YOL ÜSTÜ FIRSATI (ROADSIDE ADD-ON)
// ==========================================
function acceptRoadsideAddon() {
    if (typeof ADDON_DEALS === 'undefined' || !ADDON_DEALS[0]) return;
    const deal = ADDON_DEALS[0];
    const res = GustoState.applyRoadsideAddon(deal);
    if (res === true || (res && res.success)) {
        GustoAudio.play('add');
        updateOrderTrackerUI();
        GustoMap.drawMap('customer-map-canvas');
        showToast('🍰', `${deal.restName || 'Tatlı Dünyası'} ara durağı kurye rotasına eklendi! (+0 ₺ Teslimat)`, 'success');
    }
}

// ==========================================
// PUANLAMA & DEĞERLENDİRME MODALI (KIRILIMLI)
// ==========================================
function openRatingModal() {
    const overlay = document.getElementById('rating-overlay');
    if (overlay) overlay.style.display = 'flex';
}

function closeRatingModal() {
    const overlay = document.getElementById('rating-overlay');
    if (overlay) overlay.style.display = 'none';
}

function setStars(target, rating) {
    const container = document.getElementById(target + '-stars');
    if (!container) return;
    const stars = container.querySelectorAll('i');
    stars.forEach((s, idx) => {
        if (idx < rating) s.className = 'fa-solid fa-star active';
        else s.className = 'fa-regular fa-star';
    });
}

function toggleRatingTag(el) {
    el.classList.toggle('active');
    GustoAudio.play('add');
}

function submitRatingAndReward() {
    GustoState.addGustoPoints(25);
    const pts = GustoState.get().userPoints;
    const accPts = document.getElementById('acc-points');
    const walletDisp = document.getElementById('acc-wallet-display');
    const navRole = document.getElementById('nav-role');
    if (accPts) accPts.innerText = `${pts} Cravelo Puan (${(pts / 10).toFixed(2)} ₺)`;
    if (walletDisp) walletDisp.innerText = `${pts} Puan (${(pts / 10).toFixed(2)} ₺)`;
    if (navRole && GustoState.get().currentRole === 'customer') {
        navRole.innerHTML = `<i class="fa-solid fa-coins"></i> ${pts} Puan`;
    }
    closeRatingModal();
    GustoAudio.play('win');
    showToast('🌟', 'Değerlendirmeniz iletildi! +25 Cravelo Puan cüzdanınıza eklendi.', 'success');
}

function topUpCraveloWallet(amountTl) {
    if (typeof GustoState === 'undefined') return;
    const points = amountTl * 10;
    GustoState.addGustoPoints(points);
    const currentPts = GustoState.get().userPoints;
    const ptsEl = document.getElementById('acc-points');
    if (ptsEl) ptsEl.innerText = `${currentPts} Cravelo Puan (${(currentPts / 10).toFixed(2)} ₺)`;
    const walletEl = document.getElementById('acc-wallet-display');
    if (walletEl) walletEl.innerText = `${currentPts} Puan (${(currentPts / 10).toFixed(2)} ₺)`;
    const navRole = document.getElementById('nav-role');
    if (navRole && GustoState.get().currentRole === 'customer') {
        navRole.innerHTML = `<i class="fa-solid fa-coins"></i> ${currentPts} Puan`;
    }
    showToast('💳', `+${amountTl} ₺ Cravelo Cüzdana yüklendi! Yeni bakiye: ${(currentPts / 10).toFixed(2)} ₺`, 'success');
    if (typeof GustoAudio !== 'undefined' && GustoAudio.play) {
        GustoAudio.play('add');
    }
}

// ==========================================
// ORTAK SEPET / GRUP SİPARİŞİ (GROUP ORDER)
// ==========================================
function openGroupOrderModal() {
    renderGroupOrderMembers();
    const overlay = document.getElementById('group-order-overlay');
    if (overlay) overlay.style.display = 'flex';
}

function closeGroupOrderModal() {
    const overlay = document.getElementById('group-order-overlay');
    if (overlay) overlay.style.display = 'none';
}

function copyGroupOrderLink() {
    const code = GustoState.get().groupOrder?.code || 'CRAVELO-7429';
    navigator.clipboard?.writeText(window.location.origin + '/?group=' + code);
    GustoAudio.play('add');
    showToast('📋', `Grup davet bağlantısı kopyalandı! (Kod: ${code})`, 'success');
}

function renderGroupOrderMembers() {
    const list = document.getElementById('group-members-list');
    if (!list) return;
    const rawMembers = GustoState.get().groupOrder?.members || [];
    const members = rawMembers.length ? rawMembers : [
        { name: "Ahmet Yılmaz (Siz - Kurucu)", items: ["Smash Cheeseburger"], total: 275 },
        { name: "Selin Demir", items: ["Truffle Burger", "Çıtır Patates"], total: 320 },
        { name: "Kerem Kaya", items: ["San Sebastian Cheesecake"], total: 95 }
    ];

    list.innerHTML = members.map((m, idx) => {
        const name = (typeof m === 'object' && m.name) ? m.name : String(m);
        const items = (typeof m === 'object' && Array.isArray(m.items)) ? m.items.join(', ') : 'Seçim bekleniyor…';
        const total = (typeof m === 'object' && typeof m.total === 'number') ? `${m.total.toFixed(2)} ₺` : '—';
        const initials = name.trim().slice(0, 2).toUpperCase();
        const colors = ['#FF6B35', '#3B82F6', '#10B981', '#A855F7'];
        const col = colors[idx % colors.length];
        return `
            <div class="group-member-item" style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 14px;background:var(--surface-2);border:1px solid var(--border);border-radius:14px;margin-bottom:8px;">
                <div style="display:flex;align-items:center;gap:11px;min-width:0;">
                    <div style="width:36px;height:36px;border-radius:12px;background:${col}20;color:${col};font-weight:900;font-size:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">${initials}</div>
                    <div style="min-width:0;">
                        <strong style="font-size:13.5px;color:var(--text);display:block;">${name}</strong>
                        <div style="font-size:11.5px;color:var(--text-3);margin-top:2px;">${items}</div>
                    </div>
                </div>
                <strong style="color:var(--primary);font-size:13.5px;font-weight:900;white-space:nowrap;">${total}</strong>
            </div>
        `;
    }).join('');
}

function addGroupSimulatedMember() {
    const input = document.getElementById('group-friend-name');
    const name = input?.value.trim();
    if (!name) return;
    input.value = '';

    const state = GustoState.get();
    if (!state.groupOrder) {
        state.groupOrder = {
            code: "CRAVELO-7429",
            members: [
                { name: `${state.currentUser?.fullName || 'Kullanıcı'} (Siz - Kurucu)`, items: ["Smash Cheeseburger"], total: 275 }
            ]
        };
    }
    const sampleItems = ["Double Cheeseburger", "Tavuk Kanat 8'li", "San Sebastian Cheesecake", "Margherita Pizza"];
    const chosen = sampleItems[Math.floor(Math.random() * sampleItems.length)];
    const price = 120 + Math.floor(Math.random() * 150);

    state.groupOrder.members.push({ name, items: [chosen], total: price });
    GustoState.persist();
    renderGroupOrderMembers();
    GustoAudio.play('add');
    showToast('👥', `${name} ortak sepete katıldı ve ${chosen} ekledi!`, 'success');
}

// ==========================================
// RESTORAN KDS MUTFAK FİŞİ & BİLDİRİMİ
// ==========================================
function printKdsKitchenSlip() {
    const state = GustoState.get();
    const r = state.restaurants.find(x => x.id === state.selectedVendorRestId) || state.restaurants[0];
    const o = (state.activeOrder && state.activeOrder.rId === r.id) ? state.activeOrder : state.orderHistory.find(x => x.rId === r.id);
    if (!o) {
        showToast('ℹ️', 'Yazdırılacak bekleyen mutfak siparişi yok.');
        return;
    }
    GustoAudio.play('status');
    showToast('🖨️', `KDS Mutfak Fişi #ORD-${o.id} yazıcıya gönderildi!`, 'success');
    openReceiptModal(o.id);
}

// ==========================================
// KURYE CANLI SOHBET MODALI
// ==========================================
function openChatModal() {
    const activeOrder = GustoState.get().activeOrder;
    if (!activeOrder) {
        showToast('🛵', 'Aktif bir siparişiniz bulunmadığı için kurye sohbeti başlatılamaz.', 'warn');
        return;
    }
    const nameEl = document.getElementById('chat-courier-name');
    if (nameEl) {
        nameEl.innerText = `${activeOrder.courier} (Kurye 🛵)`;
    }
    const overlay = document.getElementById('chat-overlay');
    if (overlay) overlay.style.display = 'flex';
}

function closeChatModal() {
    const overlay = document.getElementById('chat-overlay');
    if (overlay) overlay.style.display = 'none';
}

function sendQuickChat(msg) {
    appendChatMessage('user', msg);
    setTimeout(() => {
        appendChatMessage('courier', 'Mesajınız alındı efendim, en kısa sürede kapınızdayım!');
        GustoAudio.play('status');
    }, 1000);
}

function sendChatMessage() {
    const input = document.getElementById('chat-input');
    if (!input) return;
    const msg = input.value.trim();
    if (!msg) return;
    input.value = '';
    appendChatMessage('user', msg);
    setTimeout(() => {
        appendChatMessage('courier', 'Anlaşıldı efendim, özenle teslim ediyorum.');
        GustoAudio.play('status');
    }, 1150);
}

function appendChatMessage(sender, text) {
    const area = document.getElementById('chat-messages');
    if (!area || typeof document.createElement !== 'function') return;
    const nowStr = new Date().toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit' });
    const div = document.createElement('div');
    div.className = `chat-msg ${sender}`;
    const safeText = String(text).replace(/</g, '&lt;').replace(/>/g, '&gt;');
    div.innerHTML = `
        <div style="line-height:1.45;">${safeText}</div>
        <div style="font-size:10px;opacity:0.7;margin-top:4px;text-align:${sender === 'user' ? 'right' : 'left'};">${nowStr} ${sender === 'user' ? '✓✓' : ''}</div>
    `;
    area.appendChild(div);
    area.scrollTop = area.scrollHeight;
}

// ==========================================
// DİJİTAL FİŞ / FATURA (RECEIPT MODAL)
// ==========================================
function openReceiptModal(orderId) {
    const all = [...(GustoState.get().activeOrder ? [GustoState.get().activeOrder] : []), ...GustoState.get().orderHistory];
    const order = orderId ? all.find(o => o.id === orderId) : (GustoState.get().activeOrder || all[0]);
    if (!order) {
        showToast('ℹ️', 'Görüntülenecek sipariş bulunamadı.');
        return;
    }

    const modal = document.getElementById('receipt-overlay');
    const body = document.getElementById('receipt-body');
    if (!modal || !body) return;

    const r = (order.rId ? GustoState.get().restaurants.find(x => x.id === order.rId) : null)
        || { name: order.restaurant || 'Lezzet Kebap Salonu', phone: '0216 444 10 01' };
    const restName = r.name || order.restaurant || 'Lezzet Kebap Salonu';
    const restPhone = r.phone || '0216 444 10 01';
    const courierName = order.courier || 'Veli Demir (Kurye 🛵)';
    const oId = order.id || 1049;
    const dateText = order.dateStr || 'Bugün';
    const timeText = order.createdAt || '14:30';
    const totalVal = (typeof order.total === 'number' && !isNaN(order.total)) ? order.total : (order.total_amount || 190);
    const kdv = (totalVal * 0.10).toFixed(2);
    const statusInfo = getStatusMeta(order.status || 'teslim_edildi');

    body.innerHTML = `
        <div class="receipt-paper">
            <div class="receipt-header">
                <div class="receipt-logo"><i class="fa-solid fa-fire-flame-curved" style="color:var(--primary);"></i> Cravelo Sipariş Fişi</div>
                <div class="receipt-sub">${restName} • Tel: ${restPhone}</div>
            </div>

            <div class="receipt-meta">
                <div><strong>Sipariş No:</strong> #ORD-${oId}</div>
                <div><strong>Tarih:</strong> ${dateText} ${timeText}</div>
                <div><strong>Müşteri:</strong> ${order.customerName || GustoState.get().currentUser?.fullName || 'Ahmet Yılmaz'}</div>
                <div><strong>Ödeme:</strong> ${order.payMethod ? order.payMethod.toUpperCase() : 'KREDİ KARTI'}</div>
                <div><strong>Kurye:</strong> ${courierName}</div>
                <div><strong>Durum:</strong> <span class="badge ${statusInfo.badge}" style="font-size:10.5px;">${statusInfo.label}</span></div>
            </div>

            <table class="receipt-table">
                <thead>
                    <tr><th>Ürün</th><th style="text-align:center;">Adet</th><th style="text-align:right;">Tutar</th></tr>
                </thead>
                <tbody>
                    ${(order.items && order.items.length ? order.items : [{ name: 'Adana Zırh Dürüm', qty: 1, price: totalVal }]).map(it => `
                        <tr>
                            <td><strong>${it.name || 'Menü Lezzeti'}</strong> ${it.options ? `<br><small style="color:var(--text-3);">${it.options}</small>` : ''}</td>
                            <td style="text-align:center;">${it.qty || 1}</td>
                            <td style="text-align:right;font-weight:700;">${((it.price || totalVal) * (it.qty || 1)).toFixed(2)} ₺</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>

            <div style="border-top:1px dashed var(--border-2, var(--border));padding-top:12px;font-size:12.5px;color:var(--text-2);">
                <div style="display:flex;justify-content:space-between;margin-bottom:4px;"><span>Ara Toplam:</span><span>${(order.subtotal || Math.max(0, totalVal - 25)).toFixed(2)} ₺</span></div>
                ${order.discount ? `<div style="display:flex;justify-content:space-between;margin-bottom:4px;color:var(--success);"><span>İndirim:</span><span>-${order.discount.toFixed(2)} ₺</span></div>` : ''}
                <div style="display:flex;justify-content:space-between;margin-bottom:4px;"><span>Teslimat Ücreti:</span><span>${(order.delFee ?? 25).toFixed(2)} ₺</span></div>
                ${order.tip ? `<div style="display:flex;justify-content:space-between;margin-bottom:4px;color:var(--success);"><span>Kurye Bahşişi:</span><span>+${order.tip.toFixed(2)} ₺</span></div>` : ''}
                <div style="display:flex;justify-content:space-between;margin-bottom:6px;"><span>KDV Dahil (%10):</span><span>${kdv} ₺</span></div>
            </div>

            <div class="receipt-total-row">
                <span>Ödenen Toplam</span>
                <span style="color:var(--primary);">${totalVal.toFixed(2)} ₺</span>
            </div>

            <div class="receipt-barcode">
                ||| | ||||| || |||| ||| |
                <div style="font-size:10px;margin-top:4px;letter-spacing:1px;color:var(--text-3);">KONTROL KODU: CRV-${oId}-TKP</div>
            </div>
        </div>

        <button type="button" class="btn btn-primary" style="width:100%;padding:14px;border-radius:14px;" onclick="window.print()">
            <i class="fa-solid fa-print"></i> Fişi / Faturayı Yazdır
        </button>
    `;

    modal.style.display = 'flex';
}

function closeReceiptModal() {
    const modal = document.getElementById('receipt-overlay');
    if (modal) modal.style.display = 'none';
}

// ==========================================
// ADRES VE KONUM YÖNETİMİ
// ==========================================
function updateAddressLabel() {
    const loc = GustoState.get().customerLocation;
    if (!loc) return;
    let cleanName = (loc.name || 'Evim (Moda)').replace(/\s*\(\d+\s*,\s*\d+\)/g, '').trim();
    const district = cleanName.toLowerCase().includes('kadıköy') ? '' : ', Kadıköy';
    const txt = `${cleanName}${district}`;
    const el = document.getElementById('nav-addr-label');
    if (el) el.innerText = txt;
}

function renderSavedAddressesList() {
    const list = document.getElementById('saved-address-list');
    if (!list) return;
    list.innerHTML = GustoState.get().savedAddresses.map(a => {
        const cleanDesc = (a.desc || getRealStreetLabel(a, 'Moda Caddesi, Kadıköy')).replace(/\s*\(\d+\s*,\s*\d+\)/g, '');
        const iconName = a.title.toLowerCase().includes('iş') || a.title.toLowerCase().includes('ofis')
            ? 'fa-briefcase'
            : (a.title.toLowerCase().includes('sahil') ? 'fa-umbrella-beach' : 'fa-house');
        return `
            <div class="addr-card ${a.isDefault ? 'active' : ''}" onclick="selectSavedAddress(${a.id})">
                <div style="display:flex;align-items:center;gap:12px;min-width:0;">
                    <div style="width:40px;height:40px;border-radius:12px;background:${a.isDefault ? 'rgba(255,107,53,0.16)' : 'var(--surface-2)'};color:${a.isDefault ? 'var(--primary)' : 'var(--text-2)'};display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0;">
                        <i class="fa-solid ${iconName}"></i>
                    </div>
                    <div style="min-width:0;">
                        <div style="font-weight:800;color:var(--text);font-size:14px;display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                            <span>${a.title}</span>
                            ${a.isDefault ? '<span class="badge badge-musait" style="font-size:10px;padding:2px 8px;">AKTİF</span>' : ''}
                        </div>
                        <div style="font-size:12px;color:var(--text-2);margin-top:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            <i class="fa-solid fa-location-dot" style="font-size:11px;color:var(--primary);margin-right:4px;"></i>${cleanDesc}
                        </div>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
                    <button type="button" class="btn btn-sm ${a.isDefault ? 'btn-success' : 'btn-outline'}" onclick="event.stopPropagation(); selectSavedAddress(${a.id});">
                        ${a.isDefault ? '<i class="fa-solid fa-check"></i> Seçili' : 'Seç'}
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteSavedAddress(${a.id}, event);" title="Bu Adresi Sil">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </div>
            </div>
        `;
    }).join('');
}

function selectSavedAddress(id) {
    const state = GustoState.get();
    state.savedAddresses.forEach(a => a.isDefault = (a.id === id));
    const sel = state.savedAddresses.find(a => a.id === id);
    if (!sel) return;

    state.customerLocation = { x: sel.x, y: sel.y, lat: sel.lat, lon: sel.lon, name: sel.title, desc: sel.desc };

    // Harita picker inputlarını ve gösterge adresini anında seçilen adrese eşitle
    const inX = document.getElementById('picker-x');
    const inY = document.getElementById('picker-y');
    const coordText = document.getElementById('picker-coord-text');
    if (inX) inX.value = sel.x;
    if (inY) inY.value = sel.y;
    if (coordText) coordText.innerText = `${sel.desc || sel.title}`;

    GustoState.persist();
    updateAddressLabel();
    renderSavedAddressesList();
    renderRestaurantGrid(state.activeCategory);
    GustoMap.drawAddressPicker();
    GustoMap.drawMap('customer-map-canvas');
    GustoMap.drawMap('admin-map-canvas');
    GustoAudio.play('add');
    showToast('📍', `Teslimat adresi "${sel.title}" seçildi.`, 'success');
}

function deleteSavedAddress(id, event) {
    if (event) {
        event.stopPropagation();
        event.preventDefault();
    }
    const state = GustoState.get();
    if (!state.savedAddresses || state.savedAddresses.length <= 1) {
        showToast('⚠️', 'En az bir kayıtlı teslimat adresi bulunmalıdır.', 'warn');
        return;
    }

    const targetIdx = state.savedAddresses.findIndex(a => a.id === id);
    if (targetIdx === -1) return;

    const removed = state.savedAddresses.splice(targetIdx, 1)[0];

    if (removed.isDefault || (state.customerLocation && state.customerLocation.name === removed.title)) {
        if (state.savedAddresses.length > 0) {
            state.savedAddresses[0].isDefault = true;
            state.customerLocation = {
                x: state.savedAddresses[0].x,
                y: state.savedAddresses[0].y,
                lat: state.savedAddresses[0].lat,
                lon: state.savedAddresses[0].lon,
                name: state.savedAddresses[0].title,
                desc: state.savedAddresses[0].desc
            };
            const inX = document.getElementById('picker-x');
            const inY = document.getElementById('picker-y');
            const coordText = document.getElementById('picker-coord-text');
            if (inX) inX.value = state.customerLocation.x;
            if (inY) inY.value = state.customerLocation.y;
            if (coordText) coordText.innerText = `${state.savedAddresses[0].desc || state.savedAddresses[0].title}`;
        }
    }

    GustoState.persist();
    updateAddressLabel();
    renderSavedAddressesList();
    renderRestaurantGrid(state.activeCategory);
    GustoMap.drawAddressPicker();
    GustoMap.drawMap('customer-map-canvas');
    GustoMap.drawMap('admin-map-canvas');
    showToast('🗑️', `"${removed.title}" adresi silindi.`);
}

function addNewAddress() {
    const title = document.getElementById('new-addr-title')?.value.trim() || 'Yeni Adres';
    const x = Math.min(35, Math.max(0, parseInt(document.getElementById('picker-x')?.value, 10) || 18));
    const y = Math.min(35, Math.max(0, parseInt(document.getElementById('picker-y')?.value, 10) || 14));
    const gps = (typeof GustoMap !== 'undefined' && GustoMap.gridToGps) ? GustoMap.gridToGps(x, y) : [40.9850, 29.0280];
    const autoStreet = getRealStreetLabel({ x, y, lat: gps[0], lon: gps[1] }, 'Moda Caddesi, Kadıköy');
    const desc = document.getElementById('new-addr-desc')?.value.trim() || autoStreet;

    const state = GustoState.get();
    const newId = (state.savedAddresses.reduce((max, a) => Math.max(max, a.id || 0), 0) || 0) + 1;
    state.savedAddresses.forEach(a => a.isDefault = false);
    state.savedAddresses.push({ id: newId, title, desc, x, y, lat: gps[0], lon: gps[1], isDefault: true });
    state.customerLocation = { x, y, lat: gps[0], lon: gps[1], name: title, desc };
    GustoState.persist();

    updateAddressLabel();
    renderSavedAddressesList();
    renderRestaurantGrid(state.activeCategory);
    GustoMap.drawAddressPicker();
    GustoMap.drawMap('customer-map-canvas');
    GustoMap.drawMap('admin-map-canvas');
    closeAddressModal();
    GustoAudio.play('add');
    showToast('✨', `"${title}" adresi kaydedildi ve aktif konuma ayarlandı!`, 'success');
}

function openAddressModal() {
    renderSavedAddressesList();
    const defaultAddr = GustoState.get().savedAddresses.find(a => a.isDefault);
    const loc = defaultAddr
        ? { x: defaultAddr.x, y: defaultAddr.y, lat: defaultAddr.lat, lon: defaultAddr.lon, name: defaultAddr.title, desc: defaultAddr.desc }
        : (GustoState.get().customerLocation || { x: 18, y: 14 });
    if (defaultAddr && (!GustoState.get().customerLocation || GustoState.get().customerLocation.name !== defaultAddr.title)) {
        GustoState.get().customerLocation = loc;
    }
    const inX = document.getElementById('picker-x');
    const inY = document.getElementById('picker-y');
    const coordText = document.getElementById('picker-coord-text');
    if (inX) inX.value = loc.x;
    if (inY) inY.value = loc.y;
    if (coordText) coordText.innerText = defaultAddr ? `${defaultAddr.desc}` : 'Moda Caddesi, Kadıköy';

    const overlay = document.getElementById('address-overlay');
    if (overlay) overlay.style.display = 'flex';
    setTimeout(() => GustoMap.drawAddressPicker(), 60);
}

function closeAddressModal() {
    const overlay = document.getElementById('address-overlay');
    if (overlay) overlay.style.display = 'none';
}

function syncPickerFromInputs() {
    const x = Math.min(35, Math.max(0, parseInt(document.getElementById('picker-x')?.value, 10) || 0));
    const y = Math.min(35, Math.max(0, parseInt(document.getElementById('picker-y')?.value, 10) || 0));
    const textCoord = document.getElementById('picker-coord-text');
    if (textCoord) textCoord.innerText = getRealStreetLabel({ x, y }, 'Kadıköy, İstanbul');
    GustoMap.drawAddressPicker();
}

// ==========================================
// HESAP, PROFİL & GEÇMİŞ SİPARİŞLER
// ==========================================
function openAccountModal() {
    const state = GustoState.get();
    const user = state.currentUser || { fullName: 'Ahmet Yılmaz', phone: '0555 123 45 67', email: 'ahmet@ornek.com' };
    const loc = state.customerLocation || { name: 'Kadıköy Moda', x: 18, y: 14 };

    const sess = getSessionObject() || {};
    const fullName = sess.fullName || window.ACTIVE_SESSION?.fullName || user.fullName || 'Ahmet Yılmaz';
    const email = sess.email || window.ACTIVE_SESSION?.email || user.email || 'ahmet@ornek.com';
    const phone = sess.phone || window.ACTIVE_SESSION?.phone || user.phone || '0555 123 45 67';
    const points = (typeof state.userPoints === 'number' && !isNaN(state.userPoints)) ? state.userPoints : 145;

    const nameEl = document.getElementById('acc-header-name');
    if (nameEl) nameEl.innerText = fullName;
    const fnInput = document.getElementById('profile-fullname');
    if (fnInput) fnInput.value = fullName;
    const phInput = document.getElementById('profile-phone');
    if (phInput) phInput.value = phone;
    const emInput = document.getElementById('profile-email');
    if (emInput) emInput.value = email;

    const addrTitle = document.getElementById('profile-addr-title');
    if (addrTitle) addrTitle.innerText = loc.name || 'Evim (Moda)';
    const addrCoord = document.getElementById('profile-addr-coord');
    if (addrCoord) addrCoord.innerHTML = `<i class="fa-solid fa-location-dot" style="margin-right:5px;"></i> ${getRealStreetLabel(loc, 'Moda Caddesi, Kadıköy')}`;

    const uniqueOrders = new Set();
    if (state.activeOrder && state.activeOrder.id) uniqueOrders.add(state.activeOrder.id);
    if (state.orderHistory && Array.isArray(state.orderHistory)) {
        state.orderHistory.forEach(o => { if (o && o.id) uniqueOrders.add(o.id); });
    }
    const orderCountEl = document.getElementById('acc-order-count');
    if (orderCountEl) orderCountEl.innerText = uniqueOrders.size;

    const pointsEl = document.getElementById('acc-points');
    if (pointsEl) pointsEl.innerText = `${points} Cravelo Puan (${(points / 10).toFixed(2)} ₺)`;
    const walletEl = document.getElementById('acc-wallet-display');
    if (walletEl) walletEl.innerText = `${points} Puan (${(points / 10).toFixed(2)} ₺)`;

    renderAccountOrders();
    renderAccountFavs();
    renderAccountCoupons();
    const overlay = document.getElementById('account-overlay');
    if (overlay) overlay.style.display = 'flex';
}

function closeAccountModal() {
    const overlay = document.getElementById('account-overlay');
    if (overlay) overlay.style.display = 'none';
}

function switchAccountTab(tab) {
    ['profile', 'orders', 'coupons', 'favs'].forEach(t => {
        const btn = document.getElementById('acc-tab-btn-' + t);
        if (btn) btn.className = 'modal-tab-btn ' + (t === tab ? 'active' : '');
        const tabEl = document.getElementById('acc-tab-' + t);
        if (tabEl) tabEl.style.display = (t === tab ? 'block' : 'none');
    });
    if (tab === 'orders') {
        renderAccountOrders();
        if (typeof craveloRefreshMyOrders === 'function') {
            craveloRefreshMyOrders();
        }
    }
}

function openOrdersTab() {
    openAccountModal();
    switchAccountTab('orders');
}

function renderAccountOrders() {
    const area = document.getElementById('acc-orders-list');
    if (!area) return;

    const state = GustoState.get();
    const activeOrder = state.activeOrder;
    const isOrderActive = activeOrder && !['teslim_edildi', 'iptal'].includes(activeOrder.status);

    // Sanitization & ID deduplication
    const seenIds = new Set();
    const pastOrders = [];
    const rawHistory = state.orderHistory || [];

    for (const o of rawHistory) {
        if (!o) continue;
        const oId = parseInt(o.id, 10) || 1048;
        if (!seenIds.has(oId) && (!activeOrder || activeOrder.id !== oId)) {
            seenIds.add(oId);

            let restName = o.restaurant || o.rName;
            if (!restName || restName === 'undefined' || restName === 'null' || String(restName).trim() === '') {
                const r = state.restaurants.find(x => x.id === (o.rId || o.restaurant_id));
                restName = r ? r.name : 'Lezzet Kebap Salonu';
            }
            o.restaurant = restName;

            let courName = (typeof o.courier === 'string' ? o.courier : (o.courier && o.courier.name)) || o.courierName;
            if (!courName || courName === 'undefined' || courName === 'null' || String(courName).trim() === '') {
                const c = state.couriers.find(x => x.id === (o.courierId || o.courier_id));
                courName = c ? c.name : 'Veli Demir';
            }
            o.courier = courName;

            if (!o.createdAt || o.createdAt === 'undefined' || o.createdAt === 'null') {
                o.createdAt = o.dateStr || 'Bugün';
            }
            if (!o.status || o.status === 'undefined') {
                o.status = 'teslim_edildi';
            }
            pastOrders.push(o);
        }
    }

    let html = '';

    // 1. AKTİF SİPARİŞ & CANLI 5-ADIMLI TAKİP KARTI
    if (isOrderActive) {
        let actRest = activeOrder.restaurant || activeOrder.rName;
        if (!actRest || actRest === 'undefined' || actRest === 'null') {
            const r = state.restaurants.find(x => x.id === (activeOrder.rId || activeOrder.restaurant_id));
            actRest = r ? r.name : 'Lezzet Kebap Salonu';
        }
        activeOrder.restaurant = actRest;

        let actCour = (typeof activeOrder.courier === 'string' ? activeOrder.courier : (activeOrder.courier && activeOrder.courier.name)) || activeOrder.courierName;
        if (!actCour || actCour === 'undefined' || actCour === 'null') {
            const c = state.couriers.find(x => x.id === (activeOrder.courierId || activeOrder.courier_id));
            actCour = c ? c.name : 'Veli Demir';
        }
        activeOrder.courier = actCour;

        const s = activeOrder.status;
        const stepOrder = ['alindi', 'hazirlaniyor', 'hazir', 'yolda', 'teslim_edildi'];
        const fw = { alindi: '0%', hazirlaniyor: '25%', hazir: '50%', yolda: '75%', teslim_edildi: '100%', iptal: '0%' };
        const statusMap = {
            alindi: { badge: 'Alındı', text: 'Siparişiniz Onaylandı', sub: 'Restoran siparişi mutfak ekranına (KDS) aktarıyor…', temp: '85°C', tempPct: '92%' },
            hazirlaniyor: { badge: 'Hazırlanıyor', text: 'Şefler Hazırlıyor', sub: 'Yemeğiniz özenle taze taze pişiriliyor.', temp: '88°C', tempPct: '96%' },
            hazir: { badge: 'Hazır', text: 'Paket Hazırlandı', sub: 'Kurye termal çantayla paketi teslim alıyor.', temp: '82°C', tempPct: '88%' },
            yolda: { badge: 'Yolda', text: 'Kuryeniz Yolda!', sub: 'Siparişiniz Kadıköy rotasında kapınıza geliyor.', temp: '76°C', tempPct: '80%' },
            teslim_edildi: { badge: 'Teslim Edildi', text: 'Teslim Edildi!', sub: 'Afiyet olsun!', temp: '70°C', tempPct: '72%' }
        };
        const curInfo = statusMap[s] || { badge: s, text: s, sub: '', temp: '78°C', tempPct: '80%' };
        const idx = stepOrder.indexOf(s);
        const isTakeaway = activeOrder.deliveryMode === 'takeaway';

        html += `
        <div class="tracker-card" style="background:var(--surface);border:1.5px solid rgba(255,107,53,0.45);border-radius:22px;padding:20px;margin-bottom:22px;box-shadow:0 12px 32px rgba(255,87,34,0.12);">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;flex-wrap:wrap;gap:8px;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <span class="live-pulse-dot" style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#10B981;box-shadow:0 0 10px #10B981;"></span>
                    <strong style="color:var(--text);font-size:15.5px;letter-spacing:-0.2px;">#ORD-${activeOrder.id} • ${activeOrder.restaurant}</strong>
                </div>
                <span class="badge badge-${s}" style="font-weight:800;font-size:11.5px;padding:5px 12px;border-radius:999px;">${curInfo.badge}</span>
            </div>

            <div class="stepper" style="margin:18px 0;">
                <div class="step-line"></div>
                <div class="step-fill" style="width:${fw[s] || '0%'};"></div>
                <div class="step ${idx > 0 ? 'done' : (idx === 0 ? 'active' : '')}">
                    <div class="step-dot"><i class="fa-solid fa-receipt"></i></div>
                    <div class="step-label">Alındı</div>
                </div>
                <div class="step ${idx > 1 ? 'done' : (idx === 1 ? 'active' : '')}">
                    <div class="step-dot"><i class="fa-solid fa-fire"></i></div>
                    <div class="step-label">Hazırlanıyor</div>
                </div>
                <div class="step ${idx > 2 ? 'done' : (idx === 2 ? 'active' : '')}">
                    <div class="step-dot"><i class="fa-solid fa-box-open"></i></div>
                    <div class="step-label">Hazır</div>
                </div>
                <div class="step ${idx > 3 ? 'done' : (idx === 3 ? 'active' : '')}">
                    <div class="step-dot"><i class="fa-solid fa-motorcycle"></i></div>
                    <div class="step-label">Yolda</div>
                </div>
                <div class="step ${idx >= 4 ? 'done active' : ''}">
                    <div class="step-dot"><i class="fa-solid fa-circle-check"></i></div>
                    <div class="step-label">Teslim</div>
                </div>
            </div>

            <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:14px;padding:12px 16px;margin-bottom:12px;display:flex;align-items:center;justify-content:space-between;gap:12px;">
                <div>
                    <div style="font-weight:800;color:var(--text);font-size:14px;">${curInfo.text}</div>
                    <div style="font-size:12px;color:var(--text-3);margin-top:2px;">${curInfo.sub}</div>
                </div>
                <div style="text-align:right;flex-shrink:0;">
                    <div style="font-size:10.5px;color:var(--text-3);font-weight:700;text-transform:uppercase;">Sıcaklık & Süre</div>
                    <div style="font-weight:900;color:var(--primary);font-size:14px;">🔥 ${curInfo.temp} • ~18 dk</div>
                </div>
            </div>

            <!-- Kurye Bilgi & İletişim Kutusu -->
            <div style="background:linear-gradient(135deg, rgba(59,130,246,0.09), rgba(59,130,246,0.02));border:1px solid rgba(59,130,246,0.26);border-radius:16px;padding:13px 14px;margin-bottom:14px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
                <div style="display:flex;align-items:center;gap:12px;">
                    <div style="width:42px;height:42px;border-radius:14px;background:linear-gradient(135deg,#38BDF8,#2563EB);color:#fff;display:flex;align-items:center;justify-content:center;font-size:17px;box-shadow:0 6px 14px rgba(37,99,235,0.3);">
                        <i class="fa-solid ${isTakeaway ? 'fa-person-walking' : 'fa-motorcycle'}"></i>
                    </div>
                    <div>
                        <div style="font-size:14px;font-weight:800;color:var(--text);">${activeOrder.courier}</div>
                        <div style="font-size:11.5px;color:var(--blue);font-weight:700;"><i class="fa-solid fa-shield-halved"></i> ${isTakeaway ? 'Gel-Al Teslimat Modu Aktif' : 'Canlı GPS izleme yalnızca size özel'}</div>
                    </div>
                </div>
                <div style="display:flex;gap:7px;flex-wrap:wrap;">
                    ${!isTakeaway ? `<button type="button" class="btn btn-sm btn-outline" onclick="openChatModal()" style="border-radius:999px;font-size:12px;font-weight:700;"><i class="fa-solid fa-comment-dots"></i> Mesajlaş</button>` : ''}
                    <button type="button" class="btn btn-sm btn-outline" onclick="openReceiptModal(${activeOrder.id})" style="border-radius:999px;font-size:12px;font-weight:700;">
                        <i class="fa-solid fa-receipt"></i> Fiş
                    </button>
                    ${s === 'alindi' ? `<button type="button" class="btn btn-sm btn-outline-danger" onclick="cancelOrder()" style="border-radius:999px;font-size:12px;"><i class="fa-solid fa-ban"></i> İptal</button>` : ''}
                </div>
            </div>

            <!-- Canlı Müşteri Haritası (Google Haritalar) -->
            <div style="border-radius:16px;overflow:hidden;border:1px solid var(--border);position:relative;">
                <div style="padding:9px 14px;background:var(--surface-2);border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;font-size:11.5px;font-weight:700;color:var(--text-2);flex-wrap:wrap;gap:8px;">
                    <span><i class="fa-solid fa-map-location-dot" style="color:var(--primary);margin-right:4px;"></i> Canlı Kadıköy Teslimat Rotası <span style="opacity:0.75;font-weight:600;">• Google Haritalar</span></span>
                    <div style="display:flex;gap:12px;align-items:center;">
                        <span><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#FF5722;margin-right:4px;"></span>Restoran</span>
                        <span><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#3B82F6;margin-right:4px;"></span>Kuryeniz</span>
                        <span><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#10B981;margin-right:4px;"></span>Adresiniz</span>
                    </div>
                </div>
                <div id="customer-map-frame" style="position:relative;width:100%;height:240px;background:var(--bg);">
                    <div id="customer-map-leaflet" style="width:100%;height:240px;z-index:2;"></div>
                    <canvas id="customer-map-canvas" class="map-canvas" width="600" height="240" style="position:absolute;top:0;left:0;width:100%;height:240px;display:none;background:var(--bg);z-index:1;"></canvas>
                </div>
            </div>
        </div>
        `;
    }

    // 2. GEÇMİŞ SİPARİŞLER BAŞLIĞI & LİSTESİ
    if (pastOrders.length > 0) {
        html += `
        <div style="font-size:14.5px;font-weight:800;color:var(--text);margin-bottom:12px;display:flex;align-items:center;gap:8px;">
            <i class="fa-solid fa-clock-rotate-left" style="color:var(--primary);"></i>
            <span>Geçmiş Siparişlerim (${pastOrders.length})</span>
        </div>
        `;

        html += pastOrders.map(o => {
            let restName = o.restaurant || o.rName;
            if (!restName || restName === 'undefined' || restName === 'null' || String(restName).trim() === '') {
                const r = state.restaurants.find(x => x.id === (o.rId || o.restaurant_id));
                restName = r ? r.name : 'Lezzet Kebap Salonu';
            }
            const orderId = o.id || 1049;
            const timeText = (!o.createdAt || o.createdAt === 'undefined' || o.createdAt === 'null') ? (o.dateStr || 'Bugün') : o.createdAt;
            const st = getStatusMeta(o.status || 'teslim_edildi');
            const itemsChips = (o.items && o.items.length)
                ? o.items.map(i => `<span style="display:inline-flex;align-items:center;gap:4px;background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:3px 9px;font-size:12px;font-weight:600;color:var(--text-2);">${i.name || 'Menü Lezzeti'} <strong style="color:var(--primary);">×${i.qty || 1}</strong></span>`).join('')
                : '<span style="font-size:12px;color:var(--text-2);">Adana Zırh Dürüm ×1</span>';
            const totalNum = (typeof o.total === 'number' && !isNaN(o.total)) ? o.total : (o.total_amount || 190);
            const totalText = totalNum.toFixed(2) + ' ₺';

            return `
            <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:18px;padding:16px;margin-bottom:12px;transition:border-color 0.2s;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;gap:8px;">
                    <div>
                        <strong style="color:var(--text);font-size:15px;">#ORD-${orderId} • ${restName}</strong>
                        <div style="font-size:12px;color:var(--text-3);margin-top:2px;"><i class="fa-regular fa-clock" style="margin-right:4px;"></i>${timeText}</div>
                    </div>
                    <span class="badge ${st.badge}"><i class="fa-solid ${st.icon}" style="margin-right:4px;"></i>${st.label}</span>
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:12px;">
                    ${itemsChips}
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;border-top:1px dashed var(--border);padding-top:10px;gap:8px;">
                    <span style="font-size:16.5px;font-weight:900;color:var(--primary);">${totalText}</span>
                    <div style="display:flex;gap:8px;">
                        <button type="button" class="btn btn-sm btn-outline" onclick="openReceiptModal(${orderId})" style="border-radius:999px;">
                            <i class="fa-solid fa-receipt"></i> Fişi Gör
                        </button>
                        <button type="button" class="btn btn-sm btn-primary" onclick="reorderItems(${orderId})" style="border-radius:999px;">
                            <i class="fa-solid fa-rotate-right"></i> Tekrarla
                        </button>
                    </div>
                </div>
            </div>
            `;
        }).join('');
    } else if (!isOrderActive) {
        html = `
            <div style="text-align:center;padding:42px 20px;color:var(--text-2);background:var(--surface-2);border-radius:18px;border:1px dashed var(--border);">
                <div style="width:62px;height:62px;border-radius:20px;background:rgba(255,107,53,0.12);color:var(--primary);display:inline-flex;align-items:center;justify-content:center;font-size:26px;margin-bottom:12px;">
                    <i class="fa-solid fa-basket-shopping"></i>
                </div>
                <div style="font-weight:800;font-size:16px;color:var(--text);">Henüz sipariş vermediniz</div>
                <div style="font-size:13px;color:var(--text-3);margin-top:4px;">Kadıköy'ün en iyi mutfaklarından leziz yemekler seçerek hemen başlayın!</div>
            </div>
        `;
    }

    area.innerHTML = html;

    if (isOrderActive) {
        setTimeout(() => {
            GustoMap.drawMap('customer-map-canvas');
        }, 50);
    }
}

function reorderItems(orderId) {
    const all = [...(GustoState.get().activeOrder ? [GustoState.get().activeOrder] : []), ...GustoState.get().orderHistory];
    const order = all.find(o => o.id === orderId);
    if (!order) return;

    GustoState.get().selectedRestId = order.rId;
    GustoState.get().cart = [];
    order.items.forEach(it => {
        const fullItem = GustoState.get().menuItems.find(m => m.name === it.name) || { id: 1, name: it.name, price: it.price || 150 };
        GustoState.get().cart.push({ ...fullItem, id: fullItem.id + '_' + Date.now(), qty: it.qty, options: it.options || '' });
    });

    GustoState.persist();
    updateCartUI();
    closeAccountModal();

    const restSec = document.getElementById('rest-section');
    const flashSec = document.getElementById('flash-deals-section');
    const popSec = document.getElementById('popular-showcase-section');
    const menuSec = document.getElementById('menu-section');
    if (restSec) restSec.style.display = 'none';
    if (flashSec) flashSec.style.display = 'none';
    if (popSec) popSec.style.display = 'none';
    if (menuSec) menuSec.style.display = 'grid';

    GustoAudio.play('add');
    showToast('🛒', 'Geçmiş sipariş ürünleri sepetinize eklendi!', 'success');
}

function renderAccountFavs() {
    const area = document.getElementById('acc-favs-list');
    if (!area) return;
    const favs = GustoState.get().userFavorites;
    if (!favs || !favs.length) {
        area.innerHTML = '<div style="text-align:center;padding:40px;color:var(--text-2);background:var(--surface-2);border-radius:18px;border:1px dashed var(--border);"><i class="fa-regular fa-heart" style="font-size:40px;color:var(--text-3);margin-bottom:10px;"></i><div style="font-weight:700;">Henüz favori lezzet eklemediniz.</div></div>';
        return;
    }
    const favItems = GustoState.get().menuItems.filter(m => favs.includes(m.id));
    if (!favItems.length) {
        area.innerHTML = '<div style="text-align:center;padding:40px;color:var(--text-2);background:var(--surface-2);border-radius:18px;border:1px dashed var(--border);"><i class="fa-regular fa-heart" style="font-size:40px;color:var(--text-3);margin-bottom:10px;"></i><div style="font-weight:700;">Favori menü öğeniz henüz bulunmuyor.</div></div>';
        return;
    }
    area.innerHTML = favItems.map(item => {
        const rest = GustoState.get().restaurants.find(r => r.id === item.rId);
        return `
            <div class="pop-card" style="margin-bottom:10px;" onclick="closeAccountModal();quickAddFlash(${item.id})">
                <div class="pop-img-wrap">
                    <img class="pop-img" src="${item.img}" alt="${item.name}" loading="lazy">
                    <div class="pop-tag"><i class="fa-solid fa-heart"></i> Favorim</div>
                </div>
                <div class="pop-info">
                    <div class="pop-name">${item.name}</div>
                    <div class="pop-rest">${rest ? rest.emoji + ' ' + rest.name : ''}</div>
                    <div class="pop-bottom">
                        <span class="pop-price">${item.price.toFixed(2)} ₺</span>
                        <button type="button" class="pop-add-btn" onclick="event.stopPropagation();closeAccountModal();quickAddFlash(${item.id})">
                            <i class="fa-solid fa-plus"></i> Ekle
                        </button>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

function renderAccountCoupons() {
    const area = document.getElementById('acc-coupons-list');
    if (!area) return;
    const activeCode = GustoState.get().activeCoupon;
    area.innerHTML = PROMO_COUPONS.map(c => {
        const displayCode = (c.code === 'TGO30') ? 'CRAVELO30' : c.code;
        const displayDesc = (c.desc || '').replace(/TGO Express/gi, 'Cravelo Express');
        const isUsed = activeCode === displayCode || activeCode === c.code;
        return `
            <div style="background:var(--surface-2);border:1.5px dashed ${isUsed ? 'var(--success)' : 'rgba(255,107,53,0.4)'};border-radius:16px;padding:15px 16px;margin-bottom:10px;display:flex;justify-content:space-between;align-items:center;gap:12px;">
                <div>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span style="background:linear-gradient(135deg,#FF6B35,#FF5722);color:#fff;font-size:11.5px;font-weight:900;padding:4px 11px;border-radius:8px;letter-spacing:0.6px;">${displayCode}</span>
                        ${isUsed ? '<span class="badge badge-musait" style="font-size:10px;">UYGULANDI</span>' : ''}
                    </div>
                    <div style="font-size:13px;font-weight:700;color:var(--text);margin-top:6px;">${displayDesc}</div>
                    <div style="font-size:11.5px;color:var(--text-3);margin-top:2px;">Minimum sepet tutarı: ${c.minOrder} ₺</div>
                </div>
                <button type="button" class="btn btn-sm ${isUsed ? 'btn-success' : 'btn-primary'}" style="border-radius:999px;padding:7px 16px;flex-shrink:0;" onclick="applyCouponCode('${displayCode}')">
                    ${isUsed ? '<i class="fa-solid fa-check"></i> Aktif' : 'Kullan'}
                </button>
            </div>
        `;
    }).join('');
}

function toggleHeart(el, itemId) {
    const favs = GustoState.get().userFavorites;
    const idx = favs.indexOf(itemId);
    if (idx >= 0) {
        favs.splice(idx, 1);
        el.innerHTML = '<i class="fa-regular fa-heart"></i>';
        el.style.background = 'rgba(0,0,0,0.5)';
        showToast('💔', 'Favorilerden çıkarıldı.');
    } else {
        favs.push(itemId);
        el.innerHTML = '<i class="fa-solid fa-heart" style="color:#fff;"></i>';
        el.style.background = 'var(--primary)';
        showToast('❤️', 'Favorilere eklendi!', 'success');
    }
    pulseElement(el);
    GustoState.persist();
}

function applyCouponCode(code) {
    GustoState.get().activeCoupon = code.toUpperCase();
    GustoState.persist();
    closeAccountModal();
    updateCartUI();
    GustoAudio.play('add');
    showToast('🎁', `"${GustoState.get().activeCoupon}" kuponu sepetinize uygulandı!`, 'success');
}

function applyCartCouponInput() {
    const code = document.getElementById('cart-coupon-code')?.value.trim();
    if (!code) return;
    applyCouponCode(code);
}

// ==========================================
// ROL VE OTURUM GÖRÜNÜMÜ YÖNETİMİ (RBAC İZOLASYONU)
// ==========================================
function handleBrandClick() {
    const role = GustoState.get().currentRole || 'customer';
    if (role === 'customer') {
        showRestaurants();
    } else if (role === 'courier') {
        renderCourierPanel();
        showToast('🛵', 'Kurye Kokpiti yenilendi.');
    } else if (role === 'restaurant') {
        renderResPanel();
        showToast('👨‍🍳', 'Mutfak Ekranı (KDS) yenilendi.');
    } else if (role === 'admin') {
        renderAdminCouriers();
        renderAdminAudit();
        showToast('👑', 'Yönetici Denetim Merkezi yenilendi.');
    }
}

function applyRoleIsolation(role) {
    const state = GustoState.get();
    const landingEl = document.getElementById('view-landing');
    const authActions = document.getElementById('nav-auth-actions');
    const userActions = document.getElementById('nav-user-actions');
    const customerOnlyEls = document.querySelectorAll('.customer-only-ui');
    const floatCart = document.getElementById('floating-cart-bar');
    const returnBar = document.getElementById('admin-return-bar');
    const bAdmin = document.getElementById('banner-admin');
    const bCourier = document.getElementById('banner-courier');
    const bRes = document.getElementById('banner-restaurant');
    const mainContainer = (typeof document.querySelector === 'function' ? document.querySelector('.main-container') : null) || (typeof document.getElementById === 'function' ? document.getElementById('main-container') : null);

    const panels = {
        customer: document.getElementById('view-customer'),
        restaurant: document.getElementById('view-restaurant'),
        courier: document.getElementById('view-courier'),
        admin: document.getElementById('view-admin')
    };

    const validRoles = ['customer', 'restaurant', 'courier', 'admin'];
    if (role && !validRoles.includes(role)) {
        state.currentUser = null;
        applyRoleIsolation(null);
        return;
    }

    if (!role) {
        // Oturumsuz Ziyaretçi: Landing / Auth Gate Ekranı Gösterilir (Şartname R1, R2, R3)
        state.currentRole = null;

        if (mainContainer) {
            mainContainer.style.display = 'none';
        }

        if (landingEl) {
            landingEl.style.display = 'block';
            landingEl.classList.remove('hidden');
            if (typeof window !== 'undefined' && window.BurgerScrollytelling && typeof window.BurgerScrollytelling.refresh === 'function') {
                window.BurgerScrollytelling.refresh();
            } else if (typeof ScrollTrigger !== 'undefined' && typeof ScrollTrigger.refresh === 'function') {
                ScrollTrigger.refresh();
            }
        }

        for (const el of Object.values(panels)) {
            if (!el) continue;
            el.style.display = 'none';
            el.classList.add('hidden');
        }

        customerOnlyEls.forEach(el => { el.style.display = 'none'; });
        if (floatCart) floatCart.style.display = 'none';
        if (returnBar) returnBar.style.display = 'none';
        if (bAdmin) bAdmin.style.display = 'none';
        if (bCourier) bCourier.style.display = 'none';
        if (bRes) bRes.style.display = 'none';

        if (authActions) authActions.style.display = 'flex';
        if (userActions) userActions.style.display = 'none';

        return;
    }

    state.currentRole = role;

    if (mainContainer) {
        mainContainer.style.display = 'block';
    }

    if (landingEl) {
        landingEl.style.display = 'none';
        landingEl.classList.add('hidden');
    }

    // 1. Ana Panelleri İzole Et (customer, restaurant, courier, admin)
    for (const [r, el] of Object.entries(panels)) {
        if (!el) continue;
        if (r === role) {
            el.style.display = 'block';
            el.classList.remove('hidden');
        } else {
            el.style.display = 'none';
            el.classList.add('hidden');
        }
    }

    // 2. Müşteriye Özel Üst Menü ve Yüzen Araçları Denetle (Kurye / Restoran / Admin görmez)
    const isCustomer = (role === 'customer');
    customerOnlyEls.forEach(el => {
        el.style.display = isCustomer ? '' : 'none';
    });

    if (floatCart) {
        floatCart.style.display = (isCustomer && state.cart && state.cart.length > 0 && !state.activeOrder) ? 'flex' : 'none';
    }

    // 3. Üst Bar Merkez Başlıklarını Güncelle (Admin / Courier / Restaurant)
    if (bAdmin) bAdmin.style.display = (role === 'admin') ? 'flex' : 'none';
    if (bCourier) bCourier.style.display = (role === 'courier') ? 'flex' : 'none';
    if (bRes) bRes.style.display = (role === 'restaurant') ? 'flex' : 'none';

    // 4. Navigasyon Eylemleri: Giriş/Kayıt butonlarını gizle, kullanıcı profilini göster
    if (authActions) authActions.style.display = 'none';
    if (userActions) userActions.style.display = 'flex';

    // 5. Navbar Kullanıcı Profil Kartını Güncelle (Tüm rollerde gerçek isim senkronu)
    const navName = document.getElementById('nav-name');
    const navRole = document.getElementById('nav-role');
    const navAvatar = document.getElementById('nav-avatar');

    let currentFullName = '';
    const sess = getSessionObject();
    if (sess && sess.fullName) {
        currentFullName = sess.fullName;
        if (state.currentUser) {
            state.currentUser.fullName = sess.fullName;
            if (sess.email) state.currentUser.email = sess.email;
            if (sess.phone) state.currentUser.phone = sess.phone;
        }
    }

    if (!currentFullName) {
        currentFullName = window.ACTIVE_SESSION?.fullName || state.currentUser?.fullName || 'Kullanıcı';
    }

    const roleBadges = {
        customer: `<i class="fa-solid fa-coins"></i> ${state.userPoints || 145} Puan`,
        restaurant: 'RESTORAN / KDS',
        courier: 'KURYE 🛵',
        admin: 'SİSTEM YÖNETİCİSİ'
    };

    const roleAvatars = {
        customer: '👤',
        restaurant: '👨‍🍳',
        courier: '🛵',
        admin: '👑'
    };

    if (navName) navName.innerText = currentFullName;
    if (navRole) navRole.innerHTML = roleBadges[role] || 'Giriş Yapıldı';
    if (navAvatar) navAvatar.innerText = roleAvatars[role] || '👤';

    // 6. Seçili Role Özel Ekranı Render Et
    if (role === 'customer') {
        if (!state.selectedRestId) showRestaurants();
        GustoMap.drawMap('customer-map-canvas');
    } else if (role === 'restaurant') {
        renderResPanel();
    } else if (role === 'courier') {
        renderCourierPanel();
        GustoMap.drawMap('courier-map-canvas');
    } else {
        renderAdminCouriers();
        renderAdminAudit();
        GustoMap.drawMap('admin-map-canvas');
    }

    // Seçici dropdownlarını aktif ID ile senkronize et
    const rSel = document.getElementById('res-selector');
    if (rSel && state.selectedVendorRestId) rSel.value = state.selectedVendorRestId;
    const cSel = document.getElementById('courier-selector');
    if (cSel && state.selectedCourierId) cSel.value = state.selectedCourierId;

    // Yönetici başka bir ekranı önizliyorsa geri dönüş barını göster
    const sessionRole = (sess && sess.role) || (window.ACTIVE_SESSION && window.ACTIVE_SESSION.role) || '';
    if (returnBar) {
        returnBar.style.display = (sessionRole === 'admin' && role !== 'admin') ? 'flex' : 'none';
    }

    GustoState.persist();
}

function adminPreviewView(targetRole) {
    applyRoleIsolation(targetRole);
    if (targetRole === 'restaurant') {
        showToast('👨‍🍳', 'Restoran & Mutfak (KDS) Ekranı açıldı.');
    } else if (targetRole === 'courier') {
        showToast('🛵', 'Kurye Kokpiti açıldı.');
    } else if (targetRole === 'customer') {
        showToast('🛍️', 'Müşteri Sipariş Vitrini açıldı.');
    } else {
        showToast('👑', 'Yönetici Denetim Merkezine dönüldü.');
    }
}

function loginAs(role) {
    applyRoleIsolation(role);
}

function handleUpdateProfile(e) {
    e.preventDefault();
    const state = GustoState.get();
    if (!state.currentUser) state.currentUser = {};
    const newFullName = document.getElementById('profile-fullname')?.value.trim() || 'Kullanıcı';
    const newPhone = document.getElementById('profile-phone')?.value.trim() || '';
    const newEmail = document.getElementById('profile-email')?.value.trim() || '';

    state.currentUser.fullName = newFullName;
    state.currentUser.phone = newPhone;
    state.currentUser.email = newEmail;

    // Oturum nesnelerini de senkronize et ki profil modalı tekrar açılınca güncel değerler kalsın
    if (typeof window !== 'undefined' && window.ACTIVE_SESSION) {
        window.ACTIVE_SESSION.fullName = newFullName;
        window.ACTIVE_SESSION.phone = newPhone;
        window.ACTIVE_SESSION.email = newEmail;
    }
    try {
        ['cravelo_session', 'kuryo_session', 'gustogo_session', 'tgo_session'].forEach(k => {
            const raw = localStorage.getItem(k);
            if (!raw) return;
            const parsed = JSON.parse(raw);
            parsed.fullName = newFullName;
            parsed.phone = newPhone;
            parsed.email = newEmail;
            localStorage.setItem(k, JSON.stringify(parsed));
        });
    } catch (err) {}

    GustoState.persist();

    const nEl = document.getElementById('nav-name');
    if (nEl) nEl.innerText = state.currentUser.fullName;
    const ahEl = document.getElementById('acc-header-name');
    if (ahEl) ahEl.innerText = state.currentUser.fullName;
    closeAccountModal();
    showToast('✅', 'Profil bilgileriniz güncellendi.', 'success');
}

// ==========================================
// RESTORAN / MUTFAK KDS PANELİ
// ==========================================
function changeVendorRestaurant() {
    GustoState.get().selectedVendorRestId = parseInt(document.getElementById('res-selector')?.value, 10);
    const r = GustoState.get().restaurants.find(x => x.id === GustoState.get().selectedVendorRestId);
    if (GustoState.get().currentRole === 'restaurant' && r) {
        const navName = document.getElementById('nav-name');
        if (navName) navName.innerText = r.name;
    }
    renderResPanel();
}

function toggleMenuItemStock(itemId) {
    const it = GustoState.get().menuItems.find(m => m.id === itemId);
    if (!it) return;
    it.available = !(it.available !== false);
    GustoState.persist();
    renderResPanel();
    showToast(it.available ? '✅' : '⛔', `"${it.name}" durumu "${it.available ? 'SATIŞTA' : 'TÜKENDİ'}" yapıldı.`, it.available ? 'success' : 'warn');
}

function renderResPanel() {
    const state = GustoState.get();
    const currentRest = state.restaurants.find(r => r.id === state.selectedVendorRestId) || state.restaurants[0];
    const matchingActive = (state.activeOrder && state.activeOrder.rId === state.selectedVendorRestId) ? [state.activeOrder] : [];
    const matchingHist = state.orderHistory.filter(o => o.rId === state.selectedVendorRestId);

    const activeCount = matchingActive.length;
    const prepCount = matchingActive.filter(o => ['alindi', 'hazirlaniyor'].includes(o.status)).length;
    const completedCount = matchingHist.filter(o => o.status === 'teslim_edildi').length;
    const tot = matchingHist.length + activeCount;
    const rev = matchingHist.filter(o => o.status === 'teslim_edildi').reduce((s, o) => s + (o.total || 0), 0) + (matchingActive[0]?.total || 0);

    const kpiOrders = document.getElementById('kpi-orders');
    const kpiRev = document.getElementById('kpi-revenue');
    const kpiRating = document.getElementById('kpi-res-rating');
    const kpiPrep = document.getElementById('kpi-preparing');
    const kpiCompleted = document.getElementById('kpi-completed');

    if (kpiOrders) kpiOrders.innerText = `${tot} Adet`;
    if (kpiRev) kpiRev.innerText = `${rev.toFixed(2)} ₺`;
    if (kpiRating) kpiRating.innerText = `${currentRest.rating} ⭐`;
    if (kpiPrep) kpiPrep.innerText = `${prepCount} Sipariş`;
    if (kpiCompleted) kpiCompleted.innerText = `${completedCount} Adet`;

    // 1. Canlı KDS Sipariş Akışı (Hem <div> KDS Kartları hem <tbody> Tablo uyumlu)
    const b = document.getElementById('res-orders-body');
    if (b) {
        const isTableBody = (b.tagName && b.tagName.toUpperCase() === 'TBODY');
        if (!matchingActive.length) {
            if (isTableBody) {
                b.innerHTML = `
                    <tr>
                        <td colspan="6" style="text-align:center;padding:42px 20px;">
                            <div style="display:inline-flex;flex-direction:column;align-items:center;gap:8px;color:var(--text-2);">
                                <div style="width:52px;height:52px;border-radius:16px;background:rgba(16,185,129,0.12);color:var(--success);display:flex;align-items:center;justify-content:center;font-size:22px;">
                                    <i class="fa-solid fa-kitchen-set"></i>
                                </div>
                                <strong style="font-size:15px;color:var(--text);">Mutfak Sırası Temiz — Yeni Sipariş Bekleniyor</strong>
                                <span style="font-size:12.5px;color:var(--text-3);">${currentRest.emoji} ${currentRest.name} • Canlı KDS hattı bağlı</span>
                            </div>
                        </td>
                    </tr>
                `;
            } else {
                b.innerHTML = `
                    <div class="kds-empty-state" style="grid-column:1/-1;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:42px 24px;background:var(--surface-2);border:1.5px dashed var(--border);border-radius:20px;gap:6px;">
                        <div style="width:58px;height:58px;border-radius:18px;background:rgba(16,185,129,0.12);border:1px solid rgba(16,185,129,0.28);color:var(--success);display:inline-flex;align-items:center;justify-content:center;font-size:24px;margin-bottom:8px;">
                            <i class="fa-solid fa-kitchen-set"></i>
                        </div>
                        <div style="font-size:16px;font-weight:800;color:var(--text);">Mutfak Sırası Temiz — Yeni Sipariş Bekleniyor</div>
                        <div style="font-size:13px;color:var(--text-3);">${currentRest.emoji} ${currentRest.name} (${currentRest.neighborhood || 'Kadıköy'}) • Yeni siparişler anlık olarak buraya düşer.</div>
                    </div>
                `;
            }
        } else if (isTableBody) {
            b.innerHTML = matchingActive.map(o => {
                const st = getStatusMeta(o.status);
                const elapsedMin = o.createdTs ? Math.max(1, Math.round((Date.now() - o.createdTs) / 60000)) : 2;
                let actionBtn = '';
                if (o.status === 'alindi') {
                    actionBtn = `<button type="button" class="btn btn-sm btn-primary" onclick="vendorChangeStatus('hazirlaniyor')"><i class="fa-solid fa-fire"></i> Hazırlamaya Başla</button>`;
                } else if (o.status === 'hazirlaniyor') {
                    actionBtn = `<button type="button" class="btn btn-sm btn-success" onclick="vendorChangeStatus('hazir')"><i class="fa-solid fa-box-open"></i> Hazır (Kurye Çağır)</button>`;
                } else {
                    actionBtn = `<span class="badge badge-musait"><i class="fa-solid fa-motorcycle"></i> Kuryeye Teslim</span>`;
                }
                return `
                    <tr>
                        <td>
                            <div style="font-weight:900;color:var(--text);">#ORD-${o.id}</div>
                            <div style="font-size:11px;color:var(--text-3);margin-top:2px;"><i class="fa-regular fa-clock"></i> ${o.createdAt || 'Şimdi'} (${elapsedMin} dk)</div>
                        </td>
                        <td>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <div style="width:30px;height:30px;border-radius:10px;background:var(--surface-3);display:inline-flex;align-items:center;justify-content:center;font-size:12px;color:var(--text);"><i class="fa-solid fa-user"></i></div>
                                <div>
                                    <div style="font-weight:700;color:var(--text);font-size:13px;">${o.customerName || state.currentUser?.fullName || 'Ahmet Yılmaz'}</div>
                                    <div style="font-size:11px;color:var(--text-3);">${o.deliveryMode === 'takeaway' ? '🚶 Gel-Al' : '🛵 Kurye'}</div>
                                </div>
                            </div>
                        </td>
                        <td style="max-width:260px;">
                            ${o.items.map(i => `
                                <div style="margin-bottom:4px;">
                                    <strong style="color:var(--text);">${i.name}</strong>
                                    <span style="background:rgba(255,107,53,0.14);color:var(--primary);padding:1px 7px;border-radius:6px;font-size:11px;font-weight:800;margin-left:4px;">×${i.qty}</span>
                                    ${i.options ? `<div style="font-size:11px;color:var(--text-3);margin-top:1px;">↳ ${i.options}</div>` : ''}
                                </div>
                            `).join('')}
                        </td>
                        <td><strong style="color:var(--primary);font-size:14.5px;font-weight:900;">${o.total.toFixed(2)} ₺</strong></td>
                        <td><span class="badge ${st.badge}"><i class="fa-solid ${st.icon}" style="margin-right:4px;"></i>${st.label}</span></td>
                        <td>${actionBtn}</td>
                    </tr>
                `;
            }).join('');
        } else {
            b.innerHTML = matchingActive.map(o => {
                const st = getStatusMeta(o.status);
                const elapsedMin = o.createdTs ? Math.max(1, Math.round((Date.now() - o.createdTs) / 60000)) : 2;
                let actionBtn = '';
                if (o.status === 'alindi') {
                    actionBtn = `<button type="button" class="btn btn-primary" style="flex:1;" onclick="vendorChangeStatus('hazirlaniyor')"><i class="fa-solid fa-fire"></i> Hazırlamaya Başla</button>`;
                } else if (o.status === 'hazirlaniyor') {
                    actionBtn = `<button type="button" class="btn btn-success" style="flex:1;" onclick="vendorChangeStatus('hazir')"><i class="fa-solid fa-box-open"></i> Hazır — Kurye Çağır</button>`;
                } else {
                    actionBtn = `<div style="flex:1;text-align:center;padding:10px;border-radius:12px;background:rgba(16,185,129,0.12);color:var(--success);font-weight:800;font-size:13px;"><i class="fa-solid fa-motorcycle"></i> Kuryeye Teslim Edildi (${o.courier || 'Kurye'})</div>`;
                }
                return `
                    <div class="kds-card" style="border-top:3px solid ${st.color};">
                        <div class="kds-card-head">
                            <div>
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <strong style="font-size:16px;font-weight:900;color:var(--text);">#ORD-${o.id}</strong>
                                    <span style="font-size:11.5px;font-weight:700;padding:2px 9px;border-radius:999px;background:var(--surface-2);color:var(--text-2);"><i class="fa-regular fa-clock" style="color:var(--primary);margin-right:3px;"></i>${elapsedMin} dk önce</span>
                                </div>
                                <div style="font-size:12px;color:var(--text-3);margin-top:4px;">
                                    <i class="fa-solid fa-user" style="margin-right:4px;"></i>${o.customerName || 'Ahmet Yılmaz'} • ${o.deliveryMode === 'takeaway' ? '🚶 Gel-Al' : '🛵 Kurye Teslimat'}
                                </div>
                            </div>
                            <span class="badge ${st.badge}"><i class="fa-solid ${st.icon}" style="margin-right:4px;"></i>${st.label}</span>
                        </div>

                        <div class="kds-items">
                            ${o.items.map(i => `
                                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px;padding:8px 10px;border-radius:10px;background:var(--surface-2);margin-bottom:6px;">
                                    <div>
                                        <div style="font-weight:800;font-size:13.5px;color:var(--text);">${i.name}</div>
                                        ${i.options ? `<div style="font-size:11.5px;color:var(--text-3);margin-top:2px;">${i.options}</div>` : ''}
                                    </div>
                                    <span style="background:rgba(255,107,53,0.14);color:var(--primary);font-weight:900;font-size:12px;padding:3px 9px;border-radius:8px;white-space:nowrap;">×${i.qty}</span>
                                </div>
                            `).join('')}
                            ${o.customNote ? `<div style="font-size:12px;padding:8px 10px;border-radius:10px;background:rgba(245,158,11,0.1);color:#F59E0B;font-weight:700;margin-top:4px;"><i class="fa-solid fa-note-sticky" style="margin-right:5px;"></i>Not: ${o.customNote}</div>` : ''}
                        </div>

                        <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-top:1px dashed var(--border);margin-top:6px;">
                            <span style="font-size:12px;color:var(--text-3);font-weight:700;">Toplam Tutar</span>
                            <strong style="font-size:17px;font-weight:900;color:var(--primary);">${o.total.toFixed(2)} ₺</strong>
                        </div>

                        <div class="kds-actions">
                            ${actionBtn}
                            <button type="button" class="btn btn-outline" onclick="openReceiptModal(${o.id})" title="Mutfak Fişi"><i class="fa-solid fa-print"></i></button>
                        </div>
                    </div>
                `;
            }).join('');
        }
    }

    // 2. Menü & Stok Yönetimi (Hem <div> Kart Izgarası hem <tbody> Tablo uyumlu)
    const items = state.menuItems.filter(m => m.rId === state.selectedVendorRestId);
    const menuBody = document.getElementById('res-menu-body');
    if (menuBody) {
        const isTableBody = (menuBody.tagName && menuBody.tagName.toUpperCase() === 'TBODY');
        if (isTableBody) {
            menuBody.innerHTML = items.map(item => {
                const isAvail = item.available !== false;
                return `
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:12px;">
                                <img src="${item.img}" alt="${item.name}" style="width:44px;height:44px;border-radius:12px;object-fit:cover;flex-shrink:0;${!isAvail ? 'filter:grayscale(0.8);opacity:0.6;' : ''}">
                                <div>
                                    <strong style="color:var(--text);font-size:13.5px;">${item.name}</strong>
                                    <div style="font-size:11px;color:var(--text-3);">${item.tag}</div>
                                </div>
                            </div>
                        </td>
                        <td style="color:var(--text-2);font-size:12.5px;max-width:280px;">${item.desc}</td>
                        <td><strong style="color:var(--primary);font-size:14.5px;font-weight:900;">${item.price.toFixed(2)} ₺</strong></td>
                        <td><span class="badge ${isAvail ? 'badge-musait' : 'badge-iptal'}">${isAvail ? 'SATIŞTA' : 'TÜKENDİ'}</span></td>
                        <td>
                            <button type="button" class="btn btn-sm ${isAvail ? 'btn-outline' : 'btn-success'}" style="border-radius:999px;" onclick="toggleMenuItemStock(${item.id})">
                                <i class="fa-solid ${isAvail ? 'fa-toggle-on' : 'fa-toggle-off'}"></i> ${isAvail ? 'Tükendi Yap' : 'Satışa Aç'}
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');
        } else {
            menuBody.innerHTML = items.map(item => {
                const isAvail = item.available !== false;
                return `
                    <div class="menu-toggle-card" style="${!isAvail ? 'opacity:0.75;' : ''}">
                        <div style="display:flex;gap:12px;align-items:center;margin-bottom:12px;">
                            <img src="${item.img}" alt="${item.name}" style="width:56px;height:56px;border-radius:14px;object-fit:cover;flex-shrink:0;${!isAvail ? 'filter:grayscale(0.85);' : ''}">
                            <div style="flex:1;min-width:0;">
                                <div style="display:flex;justify-content:space-between;align-items:center;gap:6px;">
                                    <strong style="font-size:14.5px;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${item.name}</strong>
                                    <span class="badge ${isAvail ? 'badge-musait' : 'badge-iptal'}" style="font-size:10px;flex-shrink:0;">${isAvail ? 'SATIŞTA' : 'TÜKENDİ'}</span>
                                </div>
                                <div style="font-size:12px;color:var(--text-3);margin-top:3px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">${item.desc}</div>
                            </div>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;padding-top:10px;border-top:1px solid var(--border);">
                            <strong style="font-size:16px;font-weight:900;color:var(--primary);">${item.price.toFixed(2)} ₺</strong>
                            <button type="button" class="btn btn-sm ${isAvail ? 'btn-outline' : 'btn-success'}" style="border-radius:999px;padding:6px 14px;" onclick="toggleMenuItemStock(${item.id})">
                                <i class="fa-solid ${isAvail ? 'fa-pause' : 'fa-play'}"></i> ${isAvail ? 'Tükendi Yap' : 'Satışa Aç'}
                            </button>
                        </div>
                    </div>
                `;
            }).join('');
        }
    }
}

function vendorChangeStatus(targetStatus) {
    const state = GustoState.get();
    if (!state.activeOrder) return;
    if (state.activeOrder.serverOrderId && typeof craveloSyncOrderTransition === 'function') {
        craveloSyncOrderTransition(state.activeOrder.serverOrderId, targetStatus);
    }
    const r = state.restaurants.find(x => x.id === state.activeOrder.rId);
    const ok = GustoState.transitionOrderStatus(targetStatus, `${r ? r.name : 'Restoran'} (Mutfak)`, `Mutfak yetkilisi sipariş durumunu ${targetStatus} olarak güncelledi.`);
    if (ok) {
        GustoAudio.play('status');
        showToast('👨‍🍳', `Sipariş #ORD-${state.activeOrder.id} → ${getStatusMeta(targetStatus).label}`, 'success');
    }
    updateOrderTrackerUI();
    renderResPanel();
}

// ==========================================
// KURYE KOKPİTİ PANELİ
// ==========================================
function changeCourierUser() {
    GustoState.get().selectedCourierId = parseInt(document.getElementById('courier-selector')?.value, 10);
    const c = GustoState.get().couriers.find(x => x.id === GustoState.get().selectedCourierId);
    if (GustoState.get().currentRole === 'courier' && c) {
        const navName = document.getElementById('nav-name');
        if (navName) navName.innerText = `${c.name} (${c.vehicle})`;
    }
    const inX = document.getElementById('courier-input-x');
    const inY = document.getElementById('courier-input-y');
    if (inX && c) inX.value = c.x;
    if (inY && c) inY.value = c.y;
    renderCourierPanel();
    GustoMap.drawMap('courier-map-canvas');
}

function updateCourierLocation() {
    const c = GustoState.get().couriers.find(x => x.id === GustoState.get().selectedCourierId);
    if (!c) return;
    const prevX = c.x;
    const prevY = c.y;
    const xVal = parseInt(document.getElementById('courier-input-x')?.value, 10);
    const yVal = parseInt(document.getElementById('courier-input-y')?.value, 10);
    if (!isNaN(xVal)) c.x = Math.min(35, Math.max(0, xVal));
    if (!isNaN(yVal)) c.y = Math.min(35, Math.max(0, yVal));

    const latLonExplicitlyUpdated = (c._prevLat !== undefined && (c.lat !== c._prevLat || c.lon !== c._prevLon));
    if (!latLonExplicitlyUpdated && (c.x !== prevX || c.y !== prevY) && (!c.lat || !c.lon || c._prevLat !== undefined)) {
        if (typeof GustoMap !== 'undefined' && GustoMap.gridToGps) {
            const gps = GustoMap.gridToGps(c.x, c.y);
            c.lat = gps[0];
            c.lon = gps[1];
            c.neighborhood = GustoMap.getNeighborhoodName(gps[0], gps[1]);
        }
    }
    c._prevLat = c.lat;
    c._prevLon = c.lon;

    GustoState.persist();
    renderCourierPanel();
    GustoMap.drawMap('courier-map-canvas');
    GustoMap.drawMap('customer-map-canvas');
    GustoMap.drawMap('admin-map-canvas');
    const locName = getRealStreetLabel(c, c.neighborhood || 'Moda Caddesi, Kadıköy');
    showToast('📍', `${c.name} konumu güncellendi: ${locName}`, 'success');
}

function setCourierLandmark(x, y, lat, lon, neighborhood) {
    const c = GustoState.get().couriers.find(k => k.id === GustoState.get().selectedCourierId);
    if (!c) return;
    c.x = x;
    c.y = y;
    c.lat = lat;
    c.lon = lon;
    c.neighborhood = neighborhood;
    const inX = document.getElementById('courier-input-x');
    const inY = document.getElementById('courier-input-y');
    if (inX) inX.value = x;
    if (inY) inY.value = y;
    GustoState.persist();
    renderCourierPanel();
    GustoMap.drawMap('courier-map-canvas');
    GustoMap.drawMap('admin-map-canvas');
    showToast('📍', `${c.name} yeni konumu: ${neighborhood}`, 'success');
}

function renderCourierPanel() {
    const state = GustoState.get();
    const curCourier = state.couriers.find(c => c.id === state.selectedCourierId) || state.couriers[0];
    const area = document.getElementById('courier-task-area');
    const badge = document.getElementById('courier-badge-status');

    const courierStreet = getRealStreetLabel(curCourier, curCourier.neighborhood || 'Rıhtım Caddesi, Kadıköy');

    const kpiLoc = document.getElementById('courier-kpi-loc');
    const kpiEarnings = document.getElementById('courier-kpi-earnings');
    const kpiActive = document.getElementById('courier-kpi-active');
    const kpiTips = document.getElementById('courier-kpi-tips');

    if (kpiLoc) kpiLoc.innerText = curCourier.neighborhood || 'Kadıköy Moda';
    if (kpiEarnings) kpiEarnings.innerText = `${curCourier.earnings.toFixed(2)} ₺`;
    if (kpiActive) kpiActive.innerText = `${curCourier.active} Görev`;
    if (kpiTips) kpiTips.innerText = `${curCourier.tips.toFixed(2)} ₺`;

    const isAssigned = (state.activeOrder && state.activeOrder.courierId === curCourier.id && ['hazir', 'yolda'].includes(state.activeOrder.status));

    if (!isAssigned) {
        if (badge) {
            badge.className = 'badge badge-musait';
            badge.innerText = 'MÜSAİT';
        }
        if (area) {
            const nearestRest = state.restaurants[0];
            const distKm = nearestRest ? getRealDistanceKm(curCourier, nearestRest) : 0.7;
            const smartScore = GustoState.calculateCourierScore(distKm, curCourier.active);
            area.innerHTML = `
                <div class="mission-card" style="display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:38px 24px;">
                    <div style="width:64px;height:64px;border-radius:20px;background:rgba(16,185,129,0.12);border:1.5px solid rgba(16,185,129,0.3);display:inline-flex;align-items:center;justify-content:center;margin-bottom:14px;">
                        <i class="fa-solid fa-circle-check" style="font-size:28px;color:var(--success);"></i>
                    </div>
                    <div style="font-weight:900;font-size:17px;color:var(--text);letter-spacing:-0.3px;">Görev Bekleniyor — Kadıköy Radarında Aktifsiniz</div>
                    <div style="font-size:13px;color:var(--text-2);margin-top:6px;">
                        <i class="fa-solid fa-location-dot" style="color:var(--primary);margin-right:4px;"></i>${courierStreet}
                    </div>
                    <div style="display:flex;justify-content:center;gap:10px;flex-wrap:wrap;margin-top:16px;">
                        <span class="score-pill"><i class="fa-solid fa-motorcycle" style="color:var(--blue);"></i> ${curCourier.vehicle}</span>
                        <span class="score-pill"><i class="fa-solid fa-gauge-high" style="color:var(--success);"></i> Atama Skoru: <strong>${smartScore.toFixed(2)}</strong> (${distKm.toFixed(2)} km + 2×${curCourier.active})</span>
                        <span class="score-pill"><i class="fa-solid fa-coins" style="color:#F59E0B;"></i> Bahşiş: <strong>${curCourier.tips} ₺</strong></span>
                    </div>
                </div>
            `;
        }
        return;
    }

    if (badge) {
        badge.className = 'badge badge-hazirlaniyor';
        badge.innerText = 'GÖREVDE';
    }

    const o = state.activeOrder;
    const r = state.restaurants.find(x => x.id === o.rId) || state.restaurants[0];
    const st = getStatusMeta(o.status);
    const restStreet = getRealStreetLabel(r, r ? r.neighborhood : 'Moda Caddesi, Kadıköy');
    const custStreet = getRealStreetLabel(state.customerLocation, state.customerLocation?.desc || 'Moda, Kadıköy');
    const pickupDistKm = getRealDistanceKm(curCourier, r);
    const dropDistKm = getRealDistanceKm(r, state.customerLocation);
    const smartScore = GustoState.calculateCourierScore(pickupDistKm, curCourier.active);
    const deliveryInst = o.instruction || (o.deliveryPref === 'leave' ? 'Kapıya Bırak (Temassız)' : (o.deliveryPref === 'quiet' ? 'Zili Çalma' : 'Kapıya Teslim'));

    let actionBtn = '';
    if (o.status === 'hazir') {
        actionBtn = `
            <button type="button" class="btn btn-primary" style="width:100%;margin-top:16px;padding:15px;font-size:15px;border-radius:14px;background:linear-gradient(135deg,#38BDF8,#2563EB);box-shadow:0 8px 24px rgba(37,99,235,0.35);" onclick="courierChangeStatus('yolda')">
                <i class="fa-solid fa-motorcycle"></i> Paketi Restorandan Aldım — Müşteriye Yola Çık
            </button>
        `;
    } else {
        actionBtn = `
            <button type="button" class="btn btn-success" style="width:100%;margin-top:16px;padding:15px;font-size:15px;border-radius:14px;box-shadow:0 8px 24px rgba(16,185,129,0.35);" onclick="courierChangeStatus('teslim_edildi')">
                <i class="fa-solid fa-circle-check"></i> Siparişi Müşteriye Teslim Ettim (Görevi Tamamla)
            </button>
        `;
    }

    if (area) {
        area.innerHTML = `
            <div class="mission-card">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;padding-bottom:14px;border-bottom:1px solid var(--border);flex-wrap:wrap;gap:10px;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <span class="live-pulse-dot" style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#FF6B35;box-shadow:0 0 10px #FF6B35;"></span>
                        <div>
                            <div style="font-size:18px;font-weight:900;color:var(--text);letter-spacing:-0.3px;">Aktif Teslimat Görevi #ORD-${o.id}</div>
                            <div style="font-size:12px;color:var(--text-3);margin-top:2px;">Toplam Rota: ${(pickupDistKm + dropDistKm).toFixed(2)} km • ${o.createdAt || 'Canlı'}</div>
                        </div>
                    </div>
                    <span class="badge ${st.badge}"><i class="fa-solid ${st.icon}" style="margin-right:4px;"></i>${st.label}</span>
                </div>

                <!-- Skor Kırılımı Şeridi -->
                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;">
                    <span class="score-pill"><i class="fa-solid fa-route" style="color:var(--primary);"></i> Restorana: <strong>${pickupDistKm.toFixed(2)} km</strong></span>
                    <span class="score-pill"><i class="fa-solid fa-flag-checkered" style="color:var(--success);"></i> Müşteriye: <strong>${dropDistKm.toFixed(2)} km</strong></span>
                    <span class="score-pill"><i class="fa-solid fa-bolt" style="color:#F59E0B;"></i> Skor: <strong>${smartScore.toFixed(2)}</strong> (${pickupDistKm.toFixed(2)} + 2×${curCourier.active})</span>
                    <span class="score-pill"><i class="fa-solid fa-hand-holding-dollar" style="color:var(--success);"></i> Bahşiş: <strong>+${o.tip || 0} ₺</strong></span>
                </div>

                <!-- 2 Duraklı Rota Kartları -->
                <div class="mission-route">
                    <div class="mission-stop">
                        <div style="font-size:11px;font-weight:800;color:var(--primary);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;">
                            <i class="fa-solid fa-store" style="margin-right:4px;"></i> 1. Alım Noktası (Restoran)
                        </div>
                        <div style="font-weight:800;font-size:14.5px;color:var(--text);">${r ? `${r.emoji} ${r.name}` : 'Restoran'}</div>
                        <div style="font-size:12px;color:var(--text-2);margin-top:4px;"><i class="fa-solid fa-location-dot" style="color:var(--primary);margin-right:4px;"></i>${restStreet}</div>
                    </div>
                    <div class="mission-stop">
                        <div style="font-size:11px;font-weight:800;color:var(--success);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;">
                            <i class="fa-solid fa-house" style="margin-right:4px;"></i> 2. Teslimat Noktası (Müşteri)
                        </div>
                        <div style="font-weight:800;font-size:14.5px;color:var(--text);">${o.customerName || state.currentUser?.fullName || 'Ahmet Yılmaz'} • ${state.customerLocation?.name || 'Evim'}</div>
                        <div style="font-size:12px;color:var(--text-2);margin-top:4px;"><i class="fa-solid fa-map-pin" style="color:var(--success);margin-right:4px;"></i>${custStreet}</div>
                    </div>
                </div>

                <!-- Paket & Teslimat Talimatı -->
                <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(230px, 1fr));gap:12px;margin-top:12px;">
                    <div style="background:var(--surface);padding:12px 15px;border-radius:14px;border:1px solid var(--border);font-size:13px;">
                        <div style="font-size:11px;font-weight:800;color:var(--text-3);text-transform:uppercase;margin-bottom:4px;"><i class="fa-solid fa-box" style="color:#F59E0B;margin-right:4px;"></i> Paket İçeriği</div>
                        <div style="font-weight:700;color:var(--text);">${o.items.map(i => `${i.name} ×${i.qty}`).join(', ')}</div>
                    </div>
                    <div style="background:var(--surface);padding:12px 15px;border-radius:14px;border:1px solid var(--border);font-size:13px;">
                        <div style="font-size:11px;font-weight:800;color:var(--text-3);text-transform:uppercase;margin-bottom:4px;"><i class="fa-solid fa-bell-concierge" style="color:var(--purple);margin-right:4px;"></i> Teslimat Talimatı</div>
                        <div style="font-weight:700;color:var(--text);">${deliveryInst}${o.customNote ? ` • "${o.customNote}"` : ''}</div>
                    </div>
                </div>

                ${actionBtn}
            </div>
        `;
    }
}

function courierChangeStatus(targetStatus) {
    const state = GustoState.get();
    if (!state.activeOrder) return;
    const targetOrderId = state.activeOrder.id;
    const targetServerId = state.activeOrder.serverOrderId;
    if (targetServerId && typeof craveloSyncOrderTransition === 'function') {
        craveloSyncOrderTransition(targetServerId, targetStatus);
    }
    const curCourier = state.couriers.find(c => c.id === state.selectedCourierId) || state.couriers[0];
    const ok = GustoState.transitionOrderStatus(targetStatus, `${curCourier.name} (Kurye)`, `Kurye teslimat aşamasını ${targetStatus} olarak kaydetti.`);
    if (ok) {
        GustoAudio.play(targetStatus === 'teslim_edildi' ? 'deliver' : 'status');
        showToast('🛵', `Sipariş #ORD-${targetOrderId} → ${getStatusMeta(targetStatus).label}`, 'success');

        // Manuel teslimatta siparişi geçmişe taşı ve aktif görevden çıkar
        if (targetStatus === 'teslim_edildi' && state.activeOrder) {
            GustoState.stopSimulation();
            if (typeof GustoMap !== 'undefined' && GustoMap.stopCourierAnimation) {
                GustoMap.stopCourierAnimation();
            }
            const finished = { ...state.activeOrder };
            const alreadyInHist = state.orderHistory.some(o => o && (o.id === finished.id || (finished.serverOrderId && o.serverOrderId === finished.serverOrderId)));
            if (!alreadyInHist) {
                state.orderHistory.unshift(finished);
            }
            state.activeOrder = null;
            GustoState.persist();
        }
    }
    updateOrderTrackerUI();
    renderCourierPanel();
    GustoMap.drawMap('courier-map-canvas');
}

// ==========================================
// ADMİN DENETİM MERKEZİ PANELİ
// ==========================================
function renderAdminCouriers() {
    const state = GustoState.get();
    const rObj = state.activeOrder
        ? (state.restaurants.find(r => r.id === state.activeOrder.rId) || state.restaurants[0])
        : state.restaurants[0];

    // Opsiyonel Yönetici KPI Kartlarını Güncelle
    const kpiCouriers = document.getElementById('admin-kpi-couriers');
    const kpiRests = document.getElementById('admin-kpi-restaurants');
    const kpiOrders = document.getElementById('admin-kpi-orders');
    const kpiAudits = document.getElementById('admin-kpi-audits');
    const totalOrders = (state.orderHistory ? state.orderHistory.length : 0) + (state.activeOrder ? 1 : 0);

    if (kpiCouriers) kpiCouriers.innerText = `${state.couriers.length} Çevrimiçi`;
    if (kpiRests) kpiRests.innerText = `${state.restaurants.length} Restoran`;
    if (kpiOrders) kpiOrders.innerText = `${totalOrders} Sipariş`;
    if (kpiAudits) kpiAudits.innerText = `${(state.auditLogs || []).length} Kayıt`;

    const b = document.getElementById('admin-couriers-body');
    if (!b) return;

    const scored = state.couriers.map(c => {
        const distKm = getRealDistanceKm(c, rObj);
        const sc = GustoState.calculateCourierScore(distKm, c.active);
        return { courier: c, distKm, sc };
    });
    const bestScore = Math.min(...scored.map(x => x.sc));

    b.innerHTML = scored.map(({ courier: c, distKm, sc }) => {
        const isBest = Math.abs(sc - bestScore) < 0.001;
        const maxLoad = 3;
        const loadPct = Math.min(100, Math.round((c.active / maxLoad) * 100));
        const loadColor = c.active === 0 ? '#10B981' : (c.active === 1 ? '#38BDF8' : (c.active === 2 ? '#F59E0B' : '#EF4444'));
        const streetLabel = getRealStreetLabel(c, c.neighborhood || 'Kadıköy');

        return `
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div style="width:34px;height:34px;border-radius:12px;background:rgba(56,189,248,0.14);color:var(--blue);display:inline-flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0;">
                            <i class="fa-solid fa-motorcycle"></i>
                        </div>
                        <div>
                            <div style="display:flex;align-items:center;gap:6px;">
                                <strong style="color:var(--text);">${c.name}</strong>
                                ${isBest ? '<span style="font-size:10px;font-weight:900;padding:2px 7px;border-radius:999px;background:rgba(16,185,129,0.16);color:#10B981;">★ EN UYGUN</span>' : ''}
                            </div>
                            <div style="font-size:11px;color:var(--text-3);">${c.phone || '0532 111 22 33'}</div>
                        </div>
                    </div>
                </td>
                <td><span style="background:var(--surface-2);border:1px solid var(--border);padding:4px 10px;border-radius:999px;font-size:12px;font-weight:700;white-space:nowrap;">${c.vehicle}</span></td>
                <td>
                    <span class="street-badge">
                        <i class="fa-solid fa-location-dot" style="color:var(--primary);margin-right:5px;"></i>${streetLabel}
                    </span>
                </td>
                <td style="min-width:140px;">
                    <div style="display:flex;justify-content:space-between;font-size:11.5px;font-weight:800;margin-bottom:5px;">
                        <span style="color:var(--text);">${c.active} / ${maxLoad} Görev</span>
                        <span style="color:${loadColor};">${c.active === 0 ? 'Boşta' : loadPct + '%'}</span>
                    </div>
                    <div class="workload-bar" style="width:100%;height:7px;border-radius:999px;background:var(--surface-2);overflow:hidden;border:1px solid var(--border);">
                        <div class="workload-fill" style="width:${Math.max(10, loadPct)}%;height:100%;border-radius:999px;background:${loadColor};transition:width 0.35s cubic-bezier(0.16,1,0.3,1);"></div>
                    </div>
                </td>
                <td>
                    <span class="score-pill" style="${isBest ? 'border-color:rgba(16,185,129,0.45);background:rgba(16,185,129,0.1);' : ''}">
                        <strong style="color:${isBest ? '#10B981' : 'var(--primary)'};">${sc.toFixed(2)}</strong>
                        <span style="color:var(--text-3);font-size:11px;">(${distKm.toFixed(2)} km + 2×${c.active})</span>
                    </span>
                </td>
                <td>
                    <span class="badge ${c.active > 0 ? 'badge-hazirlaniyor' : 'badge-musait'}" style="display:inline-flex;align-items:center;gap:5px;">
                        <span style="width:6px;height:6px;border-radius:50%;background:currentColor;"></span>
                        ${c.active > 0 ? 'GÖREVDE' : 'MÜSAİT'}
                    </span>
                </td>
            </tr>
        `;
    }).join('');
}

function renderAdminAudit() {
    const state = GustoState.get();
    const auditLogs = state.auditLogs || [];
    const kpiAudits = document.getElementById('admin-kpi-audits');
    if (kpiAudits) kpiAudits.innerText = `${auditLogs.length} Kayıt`;

    const b = document.getElementById('admin-audit-body');
    if (!b) return;

    if (!auditLogs.length) {
        b.innerHTML = `
            <tr>
                <td colspan="6" style="text-align:center;color:var(--text-2);padding:32px 16px;">
                    <i class="fa-solid fa-shield-check" style="font-size:22px;color:var(--primary);margin-bottom:6px;display:block;"></i>
                    <strong style="color:var(--text);">Kayıtlı durum geçişi bekleniyor</strong>
                    <div style="font-size:12px;color:var(--text-3);margin-top:3px;">Sipariş yaşam döngüsü geçişleri (State Pattern) anlık olarak burada denetlenir.</div>
                </td>
            </tr>
        `;
        return;
    }

    b.innerHTML = auditLogs.map(l => {
        const prevMeta = (l.prevStatus && l.prevStatus !== '—') ? getStatusMeta(l.prevStatus) : null;
        const nextMeta = getStatusMeta(l.newStatus);
        const actorIcon = (l.actor || '').includes('Mutfak') ? 'fa-fire-burner'
            : ((l.actor || '').includes('Kurye') ? 'fa-motorcycle'
                : ((l.actor || '').includes('Yönetici') ? 'fa-crown' : 'fa-user'));
        return `
            <tr>
                <td>
                    <span style="font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:11.5px;color:var(--text-2);background:var(--surface-2);padding:3px 8px;border-radius:8px;border:1px solid var(--border);">${l.time}</span>
                </td>
                <td>
                    <button type="button" onclick="openReceiptModal(${l.orderId})" style="background:none;border:none;padding:0;cursor:pointer;font-weight:900;color:var(--primary);font-size:13px;letter-spacing:0.2px;" title="Sipariş Fişini Aç">
                        #ORD-${l.orderId}
                    </button>
                </td>
                <td>
                    ${prevMeta
                        ? `<span class="badge ${prevMeta.badge}"><i class="fa-solid ${prevMeta.icon}" style="margin-right:4px;"></i>${prevMeta.label}</span>`
                        : '<span style="color:var(--text-3);font-size:12px;font-weight:700;">— Yeni</span>'}
                </td>
                <td>
                    <span style="color:var(--text-3);margin-right:6px;font-size:11px;"><i class="fa-solid fa-arrow-right"></i></span>
                    <span class="badge ${nextMeta.badge}"><i class="fa-solid ${nextMeta.icon}" style="margin-right:4px;"></i>${nextMeta.label}</span>
                </td>
                <td>
                    <span style="display:inline-flex;align-items:center;gap:6px;font-weight:700;color:var(--text);font-size:12.5px;">
                        <i class="fa-solid ${actorIcon}" style="color:var(--blue);font-size:11px;"></i>
                        ${l.actor}
                    </span>
                </td>
                <td style="color:var(--text-2);font-size:12.5px;max-width:300px;">${l.comment}</td>
            </tr>
        `;
    }).join('');
}

// ==========================================
// VİTRİN & YARDIMCI FONKSİYONLAR
// ==========================================
function renderFlashDeals() {
    const grid = document.getElementById('flash-grid');
    if (!grid) return;
    grid.innerHTML = FLASH_DEALS.map(d => {
        const item = GustoState.get().menuItems.find(m => m.id === d.id);
        if (!item) return '';
        const rest = GustoState.get().restaurants.find(r => r.id === item.rId);
        const saved = Math.max(0, d.oldPrice - d.newPrice);
        return `
            <div class="flash-card" onclick="openCustomizer(${item.id})">
                <div class="flash-img-wrap">
                    <img class="flash-img" src="${item.img}" alt="${item.name}" loading="lazy">
                    <div class="flash-badge"><i class="fa-solid fa-bolt"></i> ${d.discount}</div>
                    <div class="flash-rest-tag">${rest ? `${rest.emoji} ${rest.name}` : 'Cravelo Şef'}</div>
                    <button type="button" class="flash-quick-btn" onclick="event.stopPropagation();quickAddFlash(${item.id})" title="Hızlı Ekle">
                        <i class="fa-solid fa-plus"></i>
                    </button>
                </div>
                <div class="flash-body">
                    <div class="flash-name">${item.name}</div>
                    <div style="font-size:12px;color:var(--text-3);margin-top:-6px;margin-bottom:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${item.desc}</div>
                    <div class="flash-footer">
                        <div class="flash-prices">
                            <span class="flash-new">${d.newPrice.toFixed(2)} ₺</span>
                            <span class="flash-old">${d.oldPrice.toFixed(2)} ₺</span>
                        </div>
                        <button type="button" class="btn-flash-action" onclick="event.stopPropagation();openCustomizer(${item.id})" title="${saved} ₺ tasarruf">
                            <i class="fa-solid fa-sliders"></i> <span>Özelleştir</span>
                        </button>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

function renderPopularShowcase() {
    const grid = document.getElementById('pop-food-grid');
    if (!grid) return;
    grid.innerHTML = POPULAR_SHOWCASE_IDS.map(id => {
        const item = GustoState.get().menuItems.find(m => m.id === id);
        if (!item) return '';
        const rest = GustoState.get().restaurants.find(r => r.id === item.rId);
        return `
            <div class="pop-card" onclick="openCustomizer(${item.id})">
                <div class="pop-img-wrap">
                    <img class="pop-img" src="${item.img}" alt="${item.name}" loading="lazy">
                    <div class="pop-tag"><i class="fa-solid fa-fire"></i> ${item.tag}</div>
                </div>
                <div class="pop-info">
                    <div class="pop-name">${item.name}</div>
                    <div class="pop-rest">
                        ${rest ? `${rest.emoji} ${rest.name}` : ''}
                        ${rest ? `<span style="margin-left:6px;color:#F59E0B;font-weight:800;">★ ${rest.rating}</span>` : ''}
                    </div>
                    <div class="pop-bottom">
                        <span class="pop-price">${item.price.toFixed(2)} ₺</span>
                        <button type="button" class="pop-add-btn" onclick="event.stopPropagation();openCustomizer(${item.id})" title="Sepete Ekle">
                            <i class="fa-solid fa-plus"></i> Ekle
                        </button>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

function scrollToRestaurants() {
    document.getElementById('rest-section')?.scrollIntoView({ behavior: 'smooth' });
}

function quickAddFlash(itemId) {
    const item = GustoState.get().menuItems.find(m => m.id === itemId);
    if (!item) return;
    if (!GustoState.get().selectedRestId || GustoState.get().selectedRestId !== item.rId) {
        selectRestaurant(item.rId);
    }
    openCustomizer(itemId);
}

function startFlashCountdown() {
    let secondsLeft = 2 * 3600 + 44 * 60 + 18;
    setInterval(() => {
        if (secondsLeft <= 0) return;
        secondsLeft--;
        const h = String(Math.floor(secondsLeft / 3600)).padStart(2, '0');
        const m = String(Math.floor((secondsLeft % 3600) / 60)).padStart(2, '0');
        const s = String(secondsLeft % 60).padStart(2, '0');
        const el = document.getElementById('flash-timer');
        if (el) el.innerText = `${h}:${m}:${s}`;
    }, 1000);
}

// Debounced Global Search
let searchTimeout = null;
function globalSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        const q = document.getElementById('global-search')?.value.toLowerCase().trim();
        const state = GustoState.get();
        if (!q) {
            const flashSec = document.getElementById('flash-deals-section');
            const popSec = document.getElementById('popular-showcase-section');
            if (flashSec) flashSec.style.display = 'block';
            if (popSec) popSec.style.display = 'block';
            renderRestaurantGrid(state.activeCategory);
            return;
        }

        const res = state.restaurants.filter(r =>
            r.name.toLowerCase().includes(q) ||
            r.cat.toLowerCase().includes(q) ||
            (r.neighborhood && r.neighborhood.toLowerCase().includes(q)) ||
            state.menuItems.some(m => m.rId === r.id && (m.name.toLowerCase().includes(q) || m.desc.toLowerCase().includes(q)))
        );

        const grid = document.getElementById('rest-grid');
        const restSec = document.getElementById('rest-section');
        const flashSec = document.getElementById('flash-deals-section');
        const popSec = document.getElementById('popular-showcase-section');
        const menuSec = document.getElementById('menu-section');
        if (restSec) restSec.style.display = 'block';
        if (flashSec) flashSec.style.display = 'none';
        if (popSec) popSec.style.display = 'none';
        if (menuSec) menuSec.style.display = 'none';
        if (!grid) return;

        const badge = document.getElementById('rest-count-badge');
        if (badge) badge.innerText = `${res.length} Sonuç`;

        if (!res.length) {
            grid.innerHTML = `
                <div style="padding:52px 20px;text-align:center;color:var(--text-2);grid-column:1/-1;background:var(--surface);border:1px solid var(--border);border-radius:22px;">
                    <i class="fa-solid fa-magnifying-glass" style="font-size:32px;color:var(--text-3);margin-bottom:12px;"></i>
                    <div style="font-size:16px;font-weight:800;color:var(--text);">"${q}" ile eşleşen lezzet veya restoran bulunamadı</div>
                    <div style="font-size:13px;color:var(--text-3);margin-top:4px;">Burger, Kebap, Pizza, Sushi veya Moda yazarak tekrar deneyin.</div>
                </div>
            `;
            return;
        }

        grid.innerHTML = res.map(r => buildRestaurantCardHTML(r, state)).join('');
    }, 220);
}

// Toast Bildirimi (Hem (icon, msg, type) hem (msg, type) imzasını destekler)
function showToast(icon, msg, type = 'info') {
    const c = document.getElementById('toast-container');
    if (!c || typeof document.createElement !== 'function') return;

    const knownTypes = ['success', 'danger', 'warn', 'warning', 'info'];
    let finalIcon = icon;
    let finalMsg = msg;
    let finalType = type;

    if (typeof msg === 'undefined' || knownTypes.includes(msg)) {
        finalType = knownTypes.includes(msg) ? msg : 'info';
        finalMsg = icon;
        finalIcon = finalType === 'success' ? '✅' : (finalType === 'danger' ? '🚫' : (finalType === 'warn' || finalType === 'warning' ? '⚠️' : '✨'));
    }

    const t = document.createElement('div');
    t.className = 'toast';
    t.style.opacity = '0';
    t.style.transform = 'translateX(24px) scale(0.96)';
    t.style.transition = 'all 0.28s cubic-bezier(0.16, 1, 0.3, 1)';
    t.innerHTML = `<span style="font-size:20px;line-height:1;">${finalIcon}</span><span style="font-weight:700;font-size:13.5px;line-height:1.35;">${finalMsg}</span>`;

    if (finalType === 'danger') t.style.borderLeftColor = 'var(--danger, #EF4444)';
    else if (finalType === 'warn' || finalType === 'warning') t.style.borderLeftColor = 'var(--yellow, #F59E0B)';
    else if (finalType === 'success') t.style.borderLeftColor = 'var(--success, #10B981)';

    c.appendChild(t);
    setTimeout(() => {
        t.style.opacity = '1';
        t.style.transform = 'translateX(0) scale(1)';
    }, 16);

    setTimeout(() => {
        t.style.opacity = '0';
        t.style.transform = 'translateX(28px) scale(0.96)';
        setTimeout(() => t.remove(), 300);
    }, 3600);
}

// ==========================================
// AŞAMA 2 MADDE 5: GERÇEK SUNUCU AJAX KISMİ YENİLEME & HATA YÖNETİMİ
// ==========================================
function isCraveloHttpServer() {
    return typeof window !== 'undefined' &&
        typeof fetch === 'function' &&
        window.location &&
        /^https?:$/i.test(window.location.protocol || '');
}

async function craveloApiFetch(endpoint, options = {}) {
    if (!isCraveloHttpServer()) return null;
    const base = (typeof window !== 'undefined' && window.BASE_PATH) ? window.BASE_PATH : '';
    const url = base + endpoint;
    const fetchOpts = {
        credentials: 'same-origin',
        headers: {
            'Accept': 'application/json',
            ...(options.body ? { 'Content-Type': 'application/json' } : {}),
            ...(options.headers || {})
        },
        ...options
    };

    try {
        const res = await fetch(url, fetchOpts);
        let data = null;
        try {
            data = await res.json();
        } catch (_) {
            data = null;
        }

        if (!res.ok) {
            const serverMsg = (data && data.message)
                ? data.message
                : `Sunucu beklenmeyen yanıt döndürdü (HTTP ${res.status}).`;
            if (!options.silentError) {
                showToast('🚫', serverMsg, 'danger');
            }
            return { ok: false, status: res.status, data, message: serverMsg };
        }

        return { ok: true, status: res.status, data };
    } catch (err) {
        if (!options.silentError) {
            showToast('⚠️', 'Sunucuya bağlanılamadı. Lütfen bağlantınızı kontrol edip tekrar deneyin.', 'danger');
        }
        return { ok: false, status: 0, data: null, message: 'Ağ hatası' };
    }
}

let craveloPendingOrderSync = null;

function getOrdersSignature(st) {
    const a = st.activeOrder ? `${st.activeOrder.serverOrderId || st.activeOrder.id}:${st.activeOrder.status}` : 'none';
    const h = (st.orderHistory || []).map(o => `${o.serverOrderId || o.id}:${o.status}`).join(',');
    return a + '|' + h;
}

function hydrateOrdersForActiveUser(serverOrdersList, activeRole) {
    const state = GustoState.get();
    const beforeSig = getOrdersSignature(state);
    const sess = (typeof getSessionObject === 'function') ? getSessionObject() : null;
    const rawUsername = (typeof window !== 'undefined' && window.ACTIVE_SESSION && window.ACTIVE_SESSION.username)
        || (sess && sess.username)
        || (state.currentUser && state.currentUser.username)
        || '';
    const normUser = String(rawUsername).trim().toLowerCase();
    const role = activeRole || state.currentRole || (typeof window !== 'undefined' && window.ACTIVE_SESSION && window.ACTIVE_SESSION.role) || 'customer';

    // Farklı bir kullanıcıya geçildiyse önceki kullanıcının yerel siparişlerini temizle (hesap izolasyonu)
    if (normUser && state.ownerUsername && state.ownerUsername !== normUser) {
        state.activeOrder = null;
        state.orderHistory = [];
        state.cart = [];
    }
    if (normUser) {
        state.ownerUsername = normUser;
    }

    // Yerel durum boşsa (çıkış yapıp tekrar giriş sonrası) bu kullanıcının kişisel kasasından geri yükle
    if (normUser && !state.activeOrder && (!state.orderHistory || state.orderHistory.length === 0) && typeof GustoState.loadUserVault === 'function') {
        const vault = GustoState.loadUserVault(normUser);
        if (vault) {
            if (vault.activeOrder) state.activeOrder = vault.activeOrder;
            if (Array.isArray(vault.orderHistory)) state.orderHistory = vault.orderHistory;
            if (typeof vault.userPoints === 'number') state.userPoints = vault.userPoints;
            if (Array.isArray(vault.savedAddresses) && vault.savedAddresses.length > 0) state.savedAddresses = vault.savedAddresses;
            if (vault.customerLocation) state.customerLocation = vault.customerLocation;
            if (Array.isArray(vault.userFavorites)) state.userFavorites = vault.userFavorites;
            if (typeof vault.orderCounter === 'number' && vault.orderCounter > state.orderCounter) {
                state.orderCounter = vault.orderCounter;
            }
        }
    }

    // Sunucu JSON deposundan (storage/orders.json) gelen gerçek siparişleri (Müşteri, Restoran KDS, Kurye, Yönetici) birleştir
    if (['customer', 'restaurant', 'courier', 'admin'].includes(role) && Array.isArray(serverOrdersList) && serverOrdersList.length > 0) {
        const statusRank = { alindi: 1, hazirlaniyor: 2, hazir: 3, yolda: 4, teslim_edildi: 5, iptal: 6 };
        const existingLocal = [];
        if (state.activeOrder) existingLocal.push(state.activeOrder);
        if (Array.isArray(state.orderHistory)) {
            state.orderHistory.forEach(o => { if (o) existingLocal.push(o); });
        }

        const localByServerId = new Map();
        const unmatchedLocal = [];
        existingLocal.forEach(o => {
            const sid = Number(o.serverOrderId || 0);
            if (sid > 0) {
                localByServerId.set(sid, o);
            } else {
                unmatchedLocal.push(o);
            }
        });

        const mergedOrders = [];
        const seenKeys = new Set();

        serverOrdersList.forEach(so => {
            if (!so || !so.id) return;
            const sid = Number(so.id);
            const rId = Number(so.rId || so.restaurant_id || 1);
            const cId = Number(so.courierId || so.courier_id || 0);
            const frontRest = (state.restaurants || []).find(r => r.id === rId);
            const frontCour = (state.couriers || []).find(c => c.id === cId);

            const localMatch = localByServerId.get(sid);
            const serverStatus = String(so.status || 'alindi');
            let finalStatus = serverStatus;
            if (localMatch && localMatch.status) {
                if (serverStatus === 'iptal') {
                    finalStatus = 'iptal';
                } else if ((statusRank[localMatch.status] || 0) > (statusRank[serverStatus] || 0)) {
                    finalStatus = localMatch.status;
                }
            }

            const mappedItems = (Array.isArray(so.items) && so.items.length > 0)
                ? so.items.map(it => ({
                    name: it.name || 'Menü Lezzeti',
                    qty: Number(it.qty || it.quantity || 1),
                    price: Number(it.price || it.unit_price || 0),
                    options: it.options || ''
                }))
                : (localMatch && Array.isArray(localMatch.items) ? localMatch.items : []);

            const resolvedCustomerName = so.customer_name
                || (localMatch && localMatch.customerName)
                || (role === 'customer'
                    ? ((state.currentUser && state.currentUser.fullName) || (typeof window !== 'undefined' && window.ACTIVE_SESSION && window.ACTIVE_SESSION.fullName) || 'Müşteri')
                    : ('Müşteri #' + (so.customer_id || '')));

            const merged = {
                ...(localMatch || {}),
                id: localMatch ? localMatch.id : sid,
                serverOrderId: sid,
                rId: rId,
                restaurant: (frontRest && frontRest.name) ? frontRest.name : (so.restaurant || (localMatch && localMatch.restaurant) || 'Lezzet Kebap Salonu'),
                courierId: cId || (localMatch && localMatch.courierId) || null,
                courier: (frontCour && frontCour.name) ? frontCour.name : (so.courier || (localMatch && localMatch.courier) || 'Kurye Atanıyor'),
                customerName: resolvedCustomerName,
                status: finalStatus,
                total: Number(so.total || so.total_amount || (localMatch && localMatch.total) || 0),
                items: mappedItems,
                createdAt: (localMatch && localMatch.createdAt) || so.createdAt || 'Bugün',
                dateStr: (localMatch && localMatch.dateStr) || so.dateStr || '',
                deliveryMode: (localMatch && localMatch.deliveryMode) || 'courier'
            };

            const key = 'srv_' + sid;
            if (!seenKeys.has(key)) {
                seenKeys.add(key);
                mergedOrders.push(merged);
            }
        });

        unmatchedLocal.forEach(lo => {
            const key = 'loc_' + lo.id;
            if (!seenKeys.has(key)) {
                seenKeys.add(key);
                mergedOrders.push(lo);
            }
        });

        // En yeni sipariş üstte olacak şekilde sırala
        mergedOrders.sort((a, b) => Number(b.serverOrderId || b.id || 0) - Number(a.serverOrderId || a.id || 0));

        let nextActiveIdx = -1;
        if (role === 'courier') {
            nextActiveIdx = mergedOrders.findIndex(o => o.courierId === state.selectedCourierId && ['hazir', 'yolda'].includes(o.status));
            if (nextActiveIdx < 0) {
                nextActiveIdx = mergedOrders.findIndex(o => o.courierId === state.selectedCourierId && !['teslim_edildi', 'iptal'].includes(o.status));
            }
        } else if (role === 'restaurant') {
            nextActiveIdx = mergedOrders.findIndex(o => o.rId === state.selectedVendorRestId && !['teslim_edildi', 'iptal'].includes(o.status));
            if (nextActiveIdx < 0) {
                nextActiveIdx = mergedOrders.findIndex(o => !['teslim_edildi', 'iptal'].includes(o.status));
            }
        } else {
            nextActiveIdx = mergedOrders.findIndex(o => !['teslim_edildi', 'iptal'].includes(o.status));
        }

        if (nextActiveIdx >= 0) {
            state.activeOrder = mergedOrders[nextActiveIdx];
            state.orderHistory = mergedOrders.filter((_, idx) => idx !== nextActiveIdx);
        } else {
            state.activeOrder = null;
            state.orderHistory = mergedOrders;
        }
    }

    const afterSig = getOrdersSignature(state);
    const changed = beforeSig !== afterSig;

    GustoState.persist();
    if (changed) {
        updateOrderTrackerUI();
    }

    // Geri yüklenen aktif sipariş varsa harita kurye animasyonunu başlat
    if (changed && state.activeOrder && !['teslim_edildi', 'iptal'].includes(state.activeOrder.status)) {
        const rObj = (state.restaurants || []).find(x => x.id === state.activeOrder.rId);
        const cObj = (state.couriers || []).find(x => x.id === state.activeOrder.courierId) || (state.couriers || [])[0];
        if (rObj && cObj && typeof GustoMap !== 'undefined' && typeof GustoMap.startCourierAnimation === 'function') {
            GustoMap.startCourierAnimation(cObj, rObj, state.customerLocation);
        }
    }

    // Hesap modalı açıksa toplam sipariş sayısını ve (değişiklik varsa) listeyi anında tazele
    const accCountEl = document.getElementById('acc-order-count');
    if (accCountEl) {
        const unique = new Set();
        if (state.activeOrder && state.activeOrder.id) unique.add(state.activeOrder.id);
        (state.orderHistory || []).forEach(o => { if (o && o.id) unique.add(o.id); });
        accCountEl.innerText = unique.size;
    }
    const overlay = document.getElementById('account-overlay');
    if (changed && overlay && overlay.style.display === 'flex' && typeof renderAccountOrders === 'function') {
        renderAccountOrders();
    }
}

async function craveloRefreshMyOrders() {
    if (!isCraveloHttpServer()) return null;
    if (typeof window === 'undefined' || !window.ACTIVE_SESSION || !window.ACTIVE_SESSION.id) return null;
    const role = GustoState.get().currentRole || window.ACTIVE_SESSION.role || 'customer';

    const res = await craveloApiFetch('/api?action=my_orders', { method: 'GET', silentError: true });
    if (res && res.ok && res.data && Array.isArray(res.data.orders)) {
        window.SERVER_ORDERS = res.data.orders;
        hydrateOrdersForActiveUser(res.data.orders, role);
    }
    return res;
}

async function craveloSyncCreateOrder(orderObj, customerLoc) {
    if (!isCraveloHttpServer() || !orderObj) return null;
    // Cüzdan bakiyesi tutarın tamamını karşıladığında (total === 0) gerçek sipariş brüt tutarını sunucuya aktar
    const serverAmount = (typeof orderObj.total === 'number' && orderObj.total > 0)
        ? orderObj.total
        : (Number(orderObj.grossTotal || orderObj.subtotal || 1));
    const reqPromise = craveloApiFetch('/api?action=create_order', {
        method: 'POST',
        silentError: true,
        body: JSON.stringify({
            restaurant_id: orderObj.rId || 1,
            total_amount: serverAmount,
            del_x: customerLoc?.x ?? 18,
            del_y: customerLoc?.y ?? 14,
            delivery_latitude: customerLoc?.lat ?? 40.9850,
            delivery_longitude: customerLoc?.lon ?? 29.0280,
            items: (orderObj.items || []).map(i => ({ name: i.name, quantity: i.qty || 1, price: i.price || 100 }))
        })
    }).then(result => {
        if (result && result.ok && result.data && result.data.order_id) {
            const sid = Number(result.data.order_id);
            orderObj.serverOrderId = sid;
            const st = GustoState.get();
            if (st.activeOrder && st.activeOrder.id === orderObj.id) {
                st.activeOrder.serverOrderId = sid;
            }
            if (Array.isArray(st.orderHistory)) {
                st.orderHistory.forEach(o => {
                    if (o && o.id === orderObj.id) o.serverOrderId = sid;
                });
            }
            GustoState.persist();
        }
        return result;
    }).finally(() => {
        if (craveloPendingOrderSync === reqPromise) {
            craveloPendingOrderSync = null;
        }
    });

    craveloPendingOrderSync = reqPromise;
    return reqPromise;
}

async function craveloSyncOrderTransition(serverOrderId, targetStatus) {
    if (!isCraveloHttpServer() || !serverOrderId) return null;
    return craveloApiFetch('/api?action=change_status', {
        method: 'POST',
        body: JSON.stringify({ order_id: serverOrderId, target_status: targetStatus })
    });
}

async function craveloSyncCancelOrder(serverOrderId) {
    if (!isCraveloHttpServer() || !serverOrderId) return null;
    return craveloApiFetch('/api?action=cancel_order', {
        method: 'POST',
        body: JSON.stringify({ id: serverOrderId })
    });
}

async function craveloPollServerPartialRefresh() {
    if (!isCraveloHttpServer()) return;
    if (typeof window === 'undefined' || !window.ACTIVE_SESSION || !window.ACTIVE_SESSION.id) return;

    const state = GustoState.get();
    const role = state.currentRole || window.ACTIVE_SESSION.role || 'customer';

    if (role === 'customer' && state.activeOrder && state.activeOrder.serverOrderId) {
        const res = await craveloApiFetch(`/api/order-status?id=${encodeURIComponent(state.activeOrder.serverOrderId)}`, {
            method: 'GET',
            silentError: true
        });
        if (res && res.ok && res.data && res.data.order_status) {
            if (state.activeOrder.status !== res.data.order_status) {
                state.activeOrder.status = res.data.order_status;
                if (res.data.courier && res.data.courier.name) {
                    state.activeOrder.courier = res.data.courier.name;
                }
                GustoState.persist();
                updateOrderTrackerUI();
            }
        }
    } else if (role === 'restaurant') {
        await craveloApiFetch('/api/kds-orders', { method: 'GET', silentError: true });
        await craveloRefreshMyOrders();
    } else if (role === 'courier') {
        await craveloApiFetch('/api/courier-tasks', { method: 'GET', silentError: true });
        await craveloRefreshMyOrders();
    }
}

let craveloAjaxPollInterval = null;
function startServerPartialRefresh() {
    if (!isCraveloHttpServer() || craveloAjaxPollInterval) return;
    craveloAjaxPollInterval = setInterval(craveloPollServerPartialRefresh, 5000);
}

// Global Cravelo / Kuryo / TGO / Gusto Aliases
if (typeof window !== 'undefined') {
    if (typeof GustoState !== 'undefined') {
        window.CraveloState = window.KuryoState = window.TgoState = GustoState;
    }
    window.craveloApiFetch = craveloApiFetch;
    window.craveloPollServerPartialRefresh = craveloPollServerPartialRefresh;
    if (typeof document !== 'undefined' && typeof document.addEventListener === 'function') {
        document.addEventListener('DOMContentLoaded', startServerPartialRefresh);
    }
}
