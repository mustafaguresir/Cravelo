/**
 * Cravelo — Çekirdek Durum Yönetimi (State Machine, Haversine Algoritması, LocalStorage)
 */

const GustoState = (function () {
    const STORAGE_KEY = 'gustogo_state_v2';

    // İlk Durum
    let state = {
        currentRole: null,
        currentUser: null,
        customerLocation: { x: 18, y: 14, name: "Evim (Moda)" },
        savedAddresses: [...INITIAL_ADDRESSES],
        userFavorites: [1, 6, 10],
        favoriteRestaurants: [1, 2],
        userPoints: 145,
        activeCoupon: null,

        selectedRestId: null,
        selectedVendorRestId: 1,
        selectedCourierId: 1,
        activeCategory: null,
        currentSortMode: 'fast',

        cart: [],
        activeOrder: null,
        orderHistory: [],
        auditLogs: [],
        orderCounter: 1048,

        restaurants: [...DEFAULT_RESTAURANTS],
        menuItems: [...DEFAULT_MENU_ITEMS],
        couriers: [...DEFAULT_COURIERS],

        isSimulationEnabled: true,

        // Modern Delivery & Order Features
        deliveryMode: 'courier', // 'courier' or 'takeaway' (Gel-Al %20 indirim)
        selectedInstruction: 'baby', // id from DELIVERY_INSTRUCTIONS
        deliveryNote: '',
        isEcoFriendly: false, // Plastik çatal/bıçak istemiyorum
        scheduledTime: 'now', // 'now' or '19:30' etc.
        groupOrder: {
            active: false,
            code: 'CRAVELO-7429',
            members: [
                { name: "Ahmet Yılmaz (Siz - Kurucu)", items: ["Smash Cheeseburger"], total: 275 },
                { name: "Selin Demir", items: ["Truffle Burger", "Çıtır Patates"], total: 320 },
                { name: "Kerem Kaya", items: ["San Sebastian Cheesecake"], total: 95 }
            ],
            currentMember: 'Ahmet Yılmaz (Siz - Kurucu)'
        },
        outOfStockItemIds: [], // Restoran stok tükendi öğeleri
        activeAddon: null
    };

    let simInterval = null;

    /**
     * Sipariş nesnesindeki undefined / geçersiz alanları kalıcı olarak temizler
     */
    function sanitizeOrderRecord(o, fallbackId) {
        if (!o || typeof o !== 'object') return null;

        // Sayısal ve geçerli ID
        let orderId = parseInt(o.id, 10);
        if (isNaN(orderId) || orderId <= 0) {
            orderId = fallbackId || 1049;
        }
        o.id = orderId;

        // Restoran Adı Güvenliği
        let rName = o.restaurant || o.rName;
        if (!rName || rName === 'undefined' || rName === 'null' || String(rName).trim() === '') {
            const r = state.restaurants.find(x => x.id === (o.rId || o.restaurant_id));
            rName = r ? r.name : 'Lezzet Kebap Salonu';
        }
        o.restaurant = rName;
        o.rName = rName;

        // Kurye Adı Güvenliği
        let cName = (typeof o.courier === 'string' ? o.courier : (o.courier && o.courier.name)) || o.courierName;
        if (!cName || cName === 'undefined' || cName === 'null' || String(cName).trim() === '') {
            const c = state.couriers.find(x => x.id === (o.courierId || o.courier_id));
            cName = c ? c.name : 'Veli Demir (Kurye 🛵)';
        }
        o.courier = cName;

        // Tarih Güvenliği
        if (!o.createdAt || o.createdAt === 'undefined' || o.createdAt === 'null') {
            o.createdAt = o.dateStr || 'Bugün';
        }

        // Durum Güvenliği
        if (!o.status || o.status === 'undefined') {
            o.status = 'teslim_edildi';
        }

        // Tutar Güvenliği
        if (typeof o.total !== 'number' || isNaN(o.total)) {
            o.total = (typeof o.total_amount === 'number') ? o.total_amount : 190.00;
        }

        return o;
    }

    function syncMenuAvailabilityFromOutOfStock() {
        const outSet = new Set((state.outOfStockItemIds || []).map(id => Number(id)));
        state.menuItems.forEach(m => {
            m.available = !outSet.has(m.id);
        });
    }

    /**
     * LocalStorage'dan Durumu Yükler (Varsa)
     */
    function loadPersistedState() {
        try {
            let loadedSharedStock = false;
            try {
                const sharedOutRaw = localStorage.getItem('cravelo_out_of_stock');
                if (sharedOutRaw !== null) {
                    const parsedOut = JSON.parse(sharedOutRaw);
                    if (Array.isArray(parsedOut)) {
                        state.outOfStockItemIds = parsedOut.map(id => Number(id)).filter(n => !isNaN(n));
                        loadedSharedStock = true;
                    }
                }
            } catch (stockErr) {}

            const raw = localStorage.getItem('cravelo_state_v2') || localStorage.getItem('kuryo_state_v2') || localStorage.getItem(STORAGE_KEY) || localStorage.getItem('tgo_state_v2');
            if (raw) {
                const parsed = JSON.parse(raw);
                if (parsed.savedAddresses) state.savedAddresses = parsed.savedAddresses;
                if (parsed.customerLocation) state.customerLocation = parsed.customerLocation;
                if (parsed.userFavorites) state.userFavorites = parsed.userFavorites;
                if (Array.isArray(parsed.favoriteRestaurants)) state.favoriteRestaurants = parsed.favoriteRestaurants;
                if (typeof parsed.userPoints === 'number') state.userPoints = parsed.userPoints;
                if (parsed.cart) state.cart = parsed.cart;
                if (!loadedSharedStock && Array.isArray(parsed.outOfStockItemIds)) {
                    state.outOfStockItemIds = parsed.outOfStockItemIds.map(id => Number(id)).filter(n => !isNaN(n));
                }
                
                if (parsed.activeOrder) {
                    state.activeOrder = sanitizeOrderRecord(parsed.activeOrder, 1050);
                }
                
                if (parsed.orderHistory && Array.isArray(parsed.orderHistory)) {
                    const seenIds = new Set();
                    let counter = 1048;
                    state.orderHistory = parsed.orderHistory.map((o, idx) => {
                        const sanitized = sanitizeOrderRecord(o, 1049 - idx);
                        if (!sanitized) return null;
                        if (seenIds.has(sanitized.id)) {
                            sanitized.id = --counter;
                        }
                        seenIds.add(sanitized.id);
                        return sanitized;
                    }).filter(Boolean);
                }
                
                if (parsed.auditLogs) state.auditLogs = parsed.auditLogs;
                if (parsed.orderCounter) state.orderCounter = parsed.orderCounter;
                if (parsed.currentUser) state.currentUser = parsed.currentUser;
                if (parsed.currentRole) state.currentRole = parsed.currentRole;
                if (parsed.ownerUsername) state.ownerUsername = parsed.ownerUsername;
                if (parsed.deliveryMode) state.deliveryMode = parsed.deliveryMode;
                if (typeof parsed.isEcoFriendly === 'boolean') state.isEcoFriendly = parsed.isEcoFriendly;

                syncMenuAvailabilityFromOutOfStock();

                // Temizlenmiş veriyi hemen localStorage'a yazarak undefined kalıntılarını sil
                persist();
            } else {
                syncMenuAvailabilityFromOutOfStock();
            }
        } catch (e) {
            console.warn('LocalStorage yükleme hatası:', e);
        }
    }

    function getUserVaultKey(username) {
        if (!username) return null;
        return 'cravelo_user_vault_' + String(username).trim().toLowerCase();
    }

    function saveUserVault(explicitUsername) {
        try {
            const uname = explicitUsername || state.ownerUsername || (state.currentUser && state.currentUser.username);
            const key = getUserVaultKey(uname);
            if (!key) return;
            const snapshot = {
                ownerUsername: String(uname).trim().toLowerCase(),
                savedAddresses: state.savedAddresses,
                customerLocation: state.customerLocation,
                userFavorites: state.userFavorites,
                favoriteRestaurants: state.favoriteRestaurants,
                userPoints: state.userPoints,
                activeOrder: state.activeOrder,
                orderHistory: state.orderHistory,
                orderCounter: state.orderCounter,
                updatedAt: Date.now()
            };
            localStorage.setItem(key, JSON.stringify(snapshot));
        } catch (e) {}
    }

    function loadUserVault(username) {
        try {
            const key = getUserVaultKey(username);
            if (!key) return null;
            const raw = localStorage.getItem(key);
            if (!raw) return null;
            const parsed = JSON.parse(raw);
            if (parsed.activeOrder) {
                parsed.activeOrder = sanitizeOrderRecord(parsed.activeOrder, 1050);
            }
            if (Array.isArray(parsed.orderHistory)) {
                parsed.orderHistory = parsed.orderHistory
                    .map((o, idx) => sanitizeOrderRecord(o, 1049 - idx))
                    .filter(Boolean);
            }
            return parsed;
        } catch (e) {
            return null;
        }
    }

    /**
     * Durumu LocalStorage'a Kaydeder
     */
    function persist() {
        try {
            const ownerUsername = state.ownerUsername || (state.currentUser && state.currentUser.username) || null;
            if (ownerUsername) {
                state.ownerUsername = String(ownerUsername).trim().toLowerCase();
            }
            const dataToSave = {
                ownerUsername: state.ownerUsername || null,
                savedAddresses: state.savedAddresses,
                customerLocation: state.customerLocation,
                userFavorites: state.userFavorites,
                favoriteRestaurants: state.favoriteRestaurants,
                userPoints: state.userPoints,
                cart: state.cart,
                activeOrder: state.activeOrder,
                orderHistory: state.orderHistory,
                auditLogs: state.auditLogs,
                orderCounter: state.orderCounter,
                outOfStockItemIds: state.outOfStockItemIds,
                currentUser: state.currentUser,
                currentRole: state.currentRole
            };
            localStorage.setItem(STORAGE_KEY, JSON.stringify(dataToSave));
            localStorage.setItem('cravelo_state_v2', JSON.stringify(dataToSave));
            localStorage.setItem('kuryo_state_v2', JSON.stringify(dataToSave));
            localStorage.setItem('tgo_state_v2', JSON.stringify(dataToSave));
            localStorage.setItem('cravelo_out_of_stock', JSON.stringify(state.outOfStockItemIds));
            if (state.ownerUsername) {
                saveUserVault(state.ownerUsername);
            }
        } catch (e) {
            // QuotaExceededError vb. durumlarda sessizce geç
        }
    }

    /**
     * Haversine Büyük Daire Küresel Mesafesi (km) (Şartname Gereksinimi)
     * d = 2R * asin(sqrt(sin^2(dlat/2) + cos(lat1)*cos(lat2)*sin^2(dlon/2)))
     */
    function calculateHaversine(lat1, lon1, lat2, lon2) {
        const R = 6371.0;
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                  Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                  Math.sin(dLon / 2) * Math.sin(dLon / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return parseFloat((R * c).toFixed(2));
    }

    /**
     * Manhattan Mesafesi Hesabı: |x1 - x2| + |y1 - y2|
     */
    function calculateManhattan(x1, y1, x2, y2) {
        return Math.abs(x1 - x2) + Math.abs(y1 - y2);
    }

    /**
     * Akıllı Kurye Puanı: Puan = Mesafe (km) + (2 * AktifSiparis)
     */
    function calculateCourierScore(distKm, activeOrders) {
        return parseFloat((distKm + (2.0 * activeOrders)).toFixed(2));
    }

    /**
     * Hedef restorana en uygun kuryeyi seçer (Haversine & İş Yükü Puanlaması).
     * Yalnızca müsait kuryeleri değerlendirir; eşit puanda aktif siparişi az olan kuryeyi seçer (tie-breaker).
     */
    function findBestCourier(restX, restY, restLat = null, restLon = null) {
        let bestCourier = null;
        let lowestScore = Infinity;

        const available = state.couriers.filter(c => c.status !== 'mesgul');
        const pool = available.length > 0 ? available : state.couriers;

        pool.forEach(c => {
            let distKm = 0;
            if (restLat && restLon && c.lat && c.lon) {
                distKm = calculateHaversine(c.lat, c.lon, restLat, restLon);
            } else {
                const lat1 = 40.9900 + (c.y * 0.005);
                const lon1 = 29.0250 + (c.x * 0.005);
                const lat2 = 40.9900 + (restY * 0.005);
                const lon2 = 29.0250 + (restX * 0.005);
                distKm = calculateHaversine(lat1, lon1, lat2, lon2);
            }

            const manhattanDist = calculateManhattan(c.x, c.y, restX, restY);
            const score = calculateCourierScore(distKm, c.active);

            const isBetter = score < lowestScore;
            const isTieWithLowerLoad = (
                bestCourier !== null &&
                Math.abs(score - lowestScore) < 0.0001 &&
                c.active < bestCourier.active
            );

            if (isBetter || isTieWithLowerLoad) {
                lowestScore = score;
                bestCourier = { ...c, dist: manhattanDist, distKm, score };
            }
        });

        return bestCourier;
    }

    /**
     * Audit Log Ekleme
     */
    function logAudit(orderId, prevStatus, newStatus, actor, comment) {
        const entry = {
            time: new Date().toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit', second: '2-digit' }),
            orderId,
            prevStatus: prevStatus || '—',
            newStatus,
            actor,
            comment
        };
        state.auditLogs.unshift(entry);
        persist();
    }

    /**
     * Sipariş Durum Makinesi Geçiş Metodu (State Pattern)
     */
    function transitionOrderStatus(newStatus, actor, comment) {
        if (!state.activeOrder) return false;

        const allowedTransitions = {
            'alindi': ['hazirlaniyor', 'iptal'],
            'hazirlaniyor': ['hazir'],
            'hazir': ['yolda'],
            'yolda': ['teslim_edildi'],
            'teslim_edildi': [],
            'iptal': []
        };

        const currentStatus = state.activeOrder.status;
        const validNextStates = allowedTransitions[currentStatus] || [];

        if (!validNextStates.includes(newStatus)) {
            console.error(`Geçersiz durum geçişi: ${currentStatus} -> ${newStatus}`);
            return false;
        }

        const oldStatus = state.activeOrder.status;
        state.activeOrder.status = newStatus;

        logAudit(state.activeOrder.id, oldStatus, newStatus, actor, comment);

        if (newStatus === 'teslim_edildi' && state.activeOrder.deliveryMode !== 'takeaway' && state.activeOrder.courierId) {
            const cObj = state.couriers.find(c => c.id === state.activeOrder.courierId);
            if (cObj && cObj.active > 0) {
                cObj.active--;
            }
        }

        persist();
        return true;
    }

    /**
     * Sipariş İptali (Yalnızca 'alindi' durumunda izin verilir!)
     */
    function cancelActiveOrder(actor = null, reason = 'Müşteri siparişi iptal etti.') {
        if (!state.activeOrder) return { success: false, message: 'Aktif sipariş yok.' };

        if (state.activeOrder.status !== 'alindi') {
            return {
                success: false,
                message: "Hazırlanmaya başlanan sipariş iptal edilemez (Mimari kural gereği kilitlendi)!"
            };
        }

        if (simInterval) clearInterval(simInterval);
        GustoMap.stopCourierAnimation();

        if (state.activeOrder.deliveryMode !== 'takeaway' && state.activeOrder.courierId) {
            const cObj = state.couriers.find(c => c.id === state.activeOrder.courierId);
            if (cObj) {
                if (cObj.active > 0) cObj.active--;
                const tipAmt = Number(state.activeOrder.tip || 0);
                const delFee = Number(state.activeOrder.delFee || 0);
                const addedEarning = (delFee * 0.7) + tipAmt;
                if (tipAmt > 0) {
                    cObj.tips = Math.max(0, Number(((cObj.tips || 0) - tipAmt).toFixed(2)));
                }
                if (addedEarning > 0) {
                    cObj.earnings = Math.max(0, Number(((cObj.earnings || 0) - addedEarning).toFixed(2)));
                }
            }
        }

        // Cüzdan puanı harcandıysa iptalde eksiksiz iade et
        const refundPts = Number(state.activeOrder.walletPointsUsed || 0)
            || ((state.activeOrder.payMethod === 'wallet' && state.activeOrder.walletDiscount > 0)
                ? Math.floor(state.activeOrder.walletDiscount * 10)
                : 0);
        if (refundPts > 0) {
            state.userPoints = (state.userPoints || 0) + refundPts;
        }

        const prevStatus = state.activeOrder.status;
        const cancelledOrder = { ...state.activeOrder, status: 'iptal' };
        logAudit(cancelledOrder.id, prevStatus, 'iptal', actor || `${state.currentUser?.fullName || 'Müşteri'} (Müşteri)`, reason);

        state.orderHistory.unshift(cancelledOrder);
        state.activeOrder = null;
        persist();

        return { success: true, message: 'Sipariş başarıyla iptal edildi.' };
    }

    /**
     * Otomatik Simülasyonu Başlatır
     */
    function startSimulation(onStepCallback) {
        if (simInterval) clearInterval(simInterval);

        const transitions = [
            { next: 'hazirlaniyor', role: 'Restoran Mutfak', delay: 4200, comment: 'Restoran siparişi onayladı ve hazırlamaya başladı.' },
            { next: 'hazir', role: 'Restoran Mutfak', delay: 4800, comment: 'Yemek hazırlandı ve paketlendi.' },
            { next: 'yolda', role: 'Kurye', delay: 4200, comment: 'Kurye paketi teslim aldı ve yola çıktı.' },
            { next: 'teslim_edildi', role: 'Kurye', delay: 4800, comment: 'Sipariş müşteriye başarıyla teslim edildi.' }
        ];

        let stepIdx = transitions.findIndex(t => t.next === state.activeOrder?.status);
        let nextStep = stepIdx >= 0 ? stepIdx + 1 : 0;

        simInterval = setInterval(() => {
            if (!state.activeOrder || !state.isSimulationEnabled || nextStep >= transitions.length) {
                clearInterval(simInterval);
                simInterval = null;
                return;
            }

            const tr = transitions[nextStep];
            const ok = transitionOrderStatus(tr.next, tr.role, tr.comment);
            if (ok && onStepCallback) {
                onStepCallback(tr.next);
            }
            nextStep++;
        }, 4400);
    }

    function stopSimulation() {
        if (simInterval) {
            clearInterval(simInterval);
            simInterval = null;
        }
    }

    function setDeliveryMode(mode) {
        state.deliveryMode = mode;
        persist();
    }

    function toggleStock(itemId) {
        const idNum = Number(itemId);
        const idx = state.outOfStockItemIds.indexOf(idNum);
        if (idx >= 0) {
            state.outOfStockItemIds.splice(idx, 1);
        } else {
            state.outOfStockItemIds.push(idNum);
        }
        const isOut = state.outOfStockItemIds.includes(idNum);
        const item = state.menuItems.find(m => m.id === idNum);
        if (item) {
            item.available = !isOut;
        }
        persist();
        return isOut;
    }

    function applyRoadsideAddon(addon) {
        if (!state.activeOrder) return false;
        state.activeAddon = addon;
        state.activeOrder.addon = addon;
        state.activeOrder.items.push({
            name: `${addon.name} (${addon.restName})`,
            price: addon.price,
            qty: 1,
            options: 'Yol Üstü Lezzeti • 0 ₺ Kurye'
        });
        state.activeOrder.total += addon.price;
        logAudit(state.activeOrder.id, state.activeOrder.status, state.activeOrder.status, 'Müşteri', `Yol üstü lezzeti eklendi: ${addon.name} (+${addon.price} ₺)`);
        persist();
        return true;
    }

    function addGustoPoints(pts) {
        state.userPoints = (state.userPoints || 0) + pts;
        persist();
        return state.userPoints;
    }

    return {
        get: () => state,
        loadPersistedState,
        persist,
        saveUserVault,
        loadUserVault,
        calculateHaversine,
        calculateManhattan,
        calculateCourierScore,
        findBestCourier,
        logAudit,
        transitionOrderStatus,
        cancelActiveOrder,
        startSimulation,
        stopSimulation,
        setDeliveryMode,
        toggleStock,
        applyRoadsideAddon,
        addGustoPoints
    };
})();
window.GustoState = GustoState;
window.TgoState = GustoState;
window.KuryoState = GustoState;
window.CraveloState = GustoState;

