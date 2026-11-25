/**
 * Cravelo — Yönetici Komuta Merkezi Profesyonel Özellik Modülü
 * Platform Ciro & Sipariş Analitiği (SVG/CSS Grafik), Tüm Siparişler Yönetimi, Manuel Kurye Atama, Restoran Aç/Kapa, CSV
 * public/js/features/admin-pro.js
 */
(function (global) {
    'use strict';

    let lastAdminFetchTs = 0;

    function safeEscape(val) {
        if (typeof global.escapeHtml === 'function') return global.escapeHtml(val);
        return String(val ?? '').replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function collectAllPlatformOrders() {
        if (typeof global.GustoState === 'undefined') return [];
        const st = global.GustoState.get();
        const seen = new Set();
        const list = [];
        const add = function (o) {
            if (!o) return;
            const key = o.serverOrderId ? ('srv_' + o.serverOrderId) : ('loc_' + o.id);
            if (seen.has(key)) return;
            seen.add(key);
            list.push(o);
        };
        if (st.activeOrder) add(st.activeOrder);
        if (Array.isArray(st.orderHistory)) st.orderHistory.forEach(add);
        return list.sort(function (a, b) { return Number(b.serverOrderId || b.id || 0) - Number(a.serverOrderId || a.id || 0); });
    }

    function computeLocalAnalytics() {
        const st = global.GustoState.get();
        const orders = collectAllPlatformOrders();
        const byRest = {};
        (st.restaurants || []).forEach(function (r) {
            byRest[r.id] = { restaurant_id: r.id, name: r.name, is_active: r.isActive !== false, orders: 0, revenue: 0 };
        });
        const modeCounts = { courier: 0, takeaway: 0 };
        let validOrders = 0;
        let totalRev = 0;
        orders.forEach(function (o) {
            modeCounts[o.deliveryMode === 'takeaway' ? 'takeaway' : 'courier']++;
            if (o.status !== 'iptal') {
                validOrders++;
                const amt = Number(o.grossTotal ?? o.total ?? 0);
                totalRev += amt;
                if (!byRest[o.rId]) {
                    byRest[o.rId] = { restaurant_id: o.rId, name: o.rName || ('Restoran #' + o.rId), is_active: true, orders: 0, revenue: 0 };
                }
                byRest[o.rId].orders++;
                byRest[o.rId].revenue += amt;
            }
        });
        const restList = Object.values(byRest).sort(function (a, b) { return b.revenue - a.revenue; });
        return {
            total_orders: orders.length,
            valid_orders: validOrders,
            total_revenue: Number(totalRev.toFixed(2)),
            avg_order_value: validOrders > 0 ? Number((totalRev / validOrders).toFixed(2)) : 0,
            mode_counts: modeCounts,
            by_restaurant: restList
        };
    }

    function renderAdminAnalytics(analytics) {
        if (typeof document === 'undefined' || !analytics) return;
        const sumEl = document.getElementById('admin-analytics-summary');
        const chartEl = document.getElementById('admin-revenue-chart');

        if (sumEl) {
            const mc = analytics.mode_counts || { courier: 0, takeaway: 0 };
            sumEl.innerHTML =
                '<div style="background:var(--surface-2);border:1px solid var(--border);border-radius:14px;padding:14px;">' +
                    '<div style="font-size:11px;color:var(--text-3);font-weight:800;">TOPLAM PLATFORM CİROSU</div>' +
                    '<div style="font-size:20px;font-weight:900;color:var(--primary);margin-top:4px;">' + Number(analytics.total_revenue || 0).toFixed(2) + ' ₺</div>' +
                '</div>' +
                '<div style="background:var(--surface-2);border:1px solid var(--border);border-radius:14px;padding:14px;">' +
                    '<div style="font-size:11px;color:var(--text-3);font-weight:800;">GEÇERLİ / TOPLAM SİPARİŞ</div>' +
                    '<div style="font-size:20px;font-weight:900;color:var(--text);margin-top:4px;">' + Number(analytics.valid_orders || 0) + ' / ' + Number(analytics.total_orders || 0) + '</div>' +
                '</div>' +
                '<div style="background:var(--surface-2);border:1px solid var(--border);border-radius:14px;padding:14px;">' +
                    '<div style="font-size:11px;color:var(--text-3);font-weight:800;">ORTALAMA SEPET</div>' +
                    '<div style="font-size:20px;font-weight:900;color:var(--success);margin-top:4px;">' + Number(analytics.avg_order_value || 0).toFixed(2) + ' ₺</div>' +
                '</div>' +
                '<div style="background:var(--surface-2);border:1px solid var(--border);border-radius:14px;padding:14px;">' +
                    '<div style="font-size:11px;color:var(--text-3);font-weight:800;">TESLİMAT MODU DAĞILIMI</div>' +
                    '<div style="font-size:14.5px;font-weight:800;color:var(--text);margin-top:8px;">🛵 ' + Number(mc.courier || 0) + ' Kurye &nbsp;•&nbsp; 🚶 ' + Number(mc.takeaway || 0) + ' Gel-Al</div>' +
                '</div>';
        }

        if (chartEl) {
            const list = Array.isArray(analytics.by_restaurant) ? analytics.by_restaurant : [];
            const maxRev = Math.max(1, list.reduce(function (m, r) { return Math.max(m, Number(r.revenue || 0)); }, 0));
            chartEl.innerHTML = list.map(function (r) {
                const rev = Number(r.revenue || 0);
                const pct = Math.max(4, Math.round((rev / maxRev) * 100));
                return '<div style="display:grid;grid-template-columns:190px 1fr 125px;gap:12px;align-items:center;padding:6px 0;">' +
                    '<div style="font-size:13px;font-weight:800;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + safeEscape(r.name) + '</div>' +
                    '<div style="height:12px;background:var(--surface-2);border-radius:999px;overflow:hidden;border:1px solid var(--border);">' +
                        '<div style="height:100%;width:' + (rev > 0 ? pct : 0) + '%;background:linear-gradient(90deg,var(--primary),#FF8C42);border-radius:999px;transition:width 0.35s ease;"></div>' +
                    '</div>' +
                    '<div style="font-size:12.5px;font-weight:900;color:var(--text);text-align:right;font-variant-numeric:tabular-nums;">' + rev.toFixed(0) + ' ₺ <span style="color:var(--text-3);font-weight:600;">(' + Number(r.orders || 0) + ')</span></div>' +
                '</div>';
            }).join('');
        }
    }

    function renderAdminOrdersTable() {
        if (typeof document === 'undefined' || typeof global.GustoState === 'undefined') return;
        const tbody = document.getElementById('admin-orders-body');
        if (!tbody) return;

        const st = global.GustoState.get();
        const statusFilter = document.getElementById('admin-order-status-filter')?.value || 'all';
        const modeFilter = document.getElementById('admin-order-mode-filter')?.value || 'all';

        let orders = collectAllPlatformOrders();
        if (statusFilter !== 'all') {
            orders = orders.filter(function (o) { return o.status === statusFilter; });
        }
        if (modeFilter !== 'all') {
            orders = orders.filter(function (o) {
                const mode = o.deliveryMode === 'takeaway' ? 'takeaway' : 'courier';
                return mode === modeFilter;
            });
        }

        if (!orders.length) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:28px;color:var(--text-3);font-weight:700;">Seçili filtreye uygun sipariş bulunamadı.</td></tr>';
            return;
        }

        const couriers = st.couriers || [];
        const nextStepMap = { alindi: 'hazirlaniyor', hazirlaniyor: 'hazir', hazir: 'yolda', yolda: 'teslim_edildi' };

        tbody.innerHTML = orders.map(function (o) {
            const oid = Number(o.serverOrderId || o.id);
            const isTakeaway = o.deliveryMode === 'takeaway';
            const isTerminal = o.status === 'teslim_edildi' || o.status === 'iptal';
            const meta = typeof global.getStatusMeta === 'function' ? global.getStatusMeta(o.status) : { label: o.status, badge: 'badge-musait' };

            let courierCell = '<span style="color:var(--text-3);font-size:12px;">Gel-Al (Kuryesiz)</span>';
            if (!isTakeaway) {
                if (isTerminal) {
                    courierCell = '<span style="font-weight:700;color:var(--text-2);">' + safeEscape(o.courier || '—') + '</span>';
                } else {
                    const opts = '<option value="0">— Kurye Seç —</option>' + couriers.map(function (c) {
                        const sel = Number(o.courierId) === Number(c.id) ? ' selected' : '';
                        return '<option value="' + c.id + '"' + sel + '>' + safeEscape(c.name) + ' (' + c.active + ')</option>';
                    }).join('');
                    courierCell = '<select class="form-input" style="padding:5px 8px;font-size:12px;border-radius:8px;min-width:170px;" onchange="adminReassignCourier(' + oid + ', this.value)">' + opts + '</select>';
                }
            }

            let nextStatus = nextStepMap[o.status] || null;
            if (isTakeaway && o.status === 'hazir') nextStatus = 'teslim_edildi';

            let actionsHtml = '';
            if (!isTerminal) {
                if (nextStatus) {
                    actionsHtml += '<button type="button" class="btn btn-sm btn-outline" style="margin-right:6px;border-radius:999px;font-size:11.5px;" onclick="adminAdvanceOrderStatus(' + oid + ', \'' + nextStatus + '\')"><i class="fa-solid fa-forward"></i> İlerlet</button>';
                }
                actionsHtml += '<button type="button" class="btn btn-sm btn-outline-danger" style="border-radius:999px;font-size:11.5px;" onclick="adminCancelOrder(' + oid + ')"><i class="fa-solid fa-ban"></i> İptal</button>';
            } else {
                actionsHtml = '<span style="font-size:11.5px;color:var(--text-3);font-weight:700;">Tamamlandı</span>';
            }

            return '<tr>' +
                '<td><strong>#ORD-' + oid + '</strong><div style="font-size:11px;color:var(--text-3);">' + safeEscape(o.createdAt || '') + '</div></td>' +
                '<td><div style="font-weight:800;color:var(--text);">' + safeEscape(o.customerName || 'Müşteri') + '</div><div style="font-size:11.5px;color:var(--text-3);">' + safeEscape(o.rName || ('Restoran #' + o.rId)) + '</div></td>' +
                '<td>' + (isTakeaway ? '<span class="status-pill warning">🚶 Gel-Al</span>' : '<span class="status-pill info">🛵 Kurye</span>') + '</td>' +
                '<td><strong style="color:var(--primary);">' + Number(o.total || 0).toFixed(2) + ' ₺</strong></td>' +
                '<td><span class="badge ' + meta.badge + '">' + safeEscape(meta.label) + '</span></td>' +
                '<td>' + courierCell + '</td>' +
                '<td>' + actionsHtml + '</td>' +
            '</tr>';
        }).join('');
    }

    function renderAdminRestaurants() {
        if (typeof document === 'undefined' || typeof global.GustoState === 'undefined') return;
        const grid = document.getElementById('admin-restaurants-grid');
        if (!grid) return;
        const list = global.GustoState.get().restaurants || [];

        grid.innerHTML = list.map(function (r) {
            const active = r.isActive !== false;
            return '<div style="background:var(--surface-2);border:1px solid var(--border);border-radius:16px;padding:14px;display:flex;flex-direction:column;gap:10px;">' +
                '<div style="display:flex;justify-content:space-between;align-items:center;gap:8px;">' +
                    '<strong style="font-size:14px;color:var(--text);">' + safeEscape(r.emoji || '🍽️') + ' ' + safeEscape(r.name) + '</strong>' +
                    '<span class="badge ' + (active ? 'badge-musait' : 'badge-iptal') + '" style="font-size:10px;">' + (active ? 'AÇIK' : 'KAPALI') + '</span>' +
                '</div>' +
                '<div style="font-size:12px;color:var(--text-3);"><i class="fa-solid fa-location-dot" style="color:var(--primary);margin-right:4px;"></i>' + safeEscape(r.neighborhood || 'Kadıköy') + ' • ⭐ ' + safeEscape(r.rating) + '</div>' +
                '<button type="button" class="btn btn-sm ' + (active ? 'btn-outline-danger' : 'btn-success') + '" style="border-radius:999px;width:100%;" onclick="adminToggleRestaurant(' + r.id + ')">' +
                    '<i class="fa-solid ' + (active ? 'fa-door-closed' : 'fa-door-open') + '"></i> ' + (active ? 'Siparişe Kapat' : 'Siparişe Aç') +
                '</button>' +
            '</div>';
        }).join('');
    }

    async function refreshAdminCommandCenter(silent) {
        if (typeof global.GustoState === 'undefined') return null;
        const now = Date.now();
        if (silent && (now - lastAdminFetchTs) < 4000) {
            renderAdminOrdersTable();
            renderAdminRestaurants();
            return global._lastAdminAnalytics || null;
        }
        lastAdminFetchTs = now;

        const dateVal = typeof document !== 'undefined' ? (document.getElementById('admin-analytics-date')?.value || '') : '';
        let analytics = null;

        if (typeof global.isCraveloHttpServer === 'function' && global.isCraveloHttpServer() && global.ACTIVE_SESSION && global.ACTIVE_SESSION.role === 'admin') {
            const url = '/api?action=admin_analytics' + (dateVal ? ('&date=' + encodeURIComponent(dateVal)) : '');
            const res = await global.craveloApiFetch(url, { method: 'GET', silentError: true });
            if (res && res.ok && res.data) {
                analytics = res.data;
            }
            if (!silent && typeof global.refreshServerCatalog === 'function') {
                await global.refreshServerCatalog();
            }
        }

        if (!analytics) {
            analytics = computeLocalAnalytics();
        }
        global._lastAdminAnalytics = analytics;
        renderAdminAnalytics(analytics);
        renderAdminOrdersTable();
        renderAdminRestaurants();
        return analytics;
    }

    async function adminReassignCourier(orderId, courierId) {
        const cid = parseInt(courierId, 10);
        if (!cid || cid <= 0) return false;
        if (typeof global.isCraveloHttpServer === 'function' && global.isCraveloHttpServer()) {
            const res = await global.craveloApiFetch('/api?action=reassign_courier', {
                method: 'POST',
                body: JSON.stringify({ order_id: orderId, courier_id: cid })
            });
            if (!res || !res.ok) return false;
            if (typeof global.craveloRefreshMyOrders === 'function') await global.craveloRefreshMyOrders();
            if (typeof global.refreshServerCatalog === 'function') await global.refreshServerCatalog();
        } else if (typeof global.GustoState !== 'undefined') {
            const st = global.GustoState.get();
            const target = collectAllPlatformOrders().find(function (o) { return Number(o.serverOrderId || o.id) === Number(orderId); });
            const c = (st.couriers || []).find(function (k) { return Number(k.id) === cid; });
            if (target && c) {
                target.courierId = c.id;
                target.courier = c.name;
            }
        }
        if (typeof global.renderAdminCouriers === 'function') global.renderAdminCouriers();
        refreshAdminCommandCenter(false);
        if (typeof global.showToast === 'function') {
            global.showToast('🛵', 'Sipariş #ORD-' + orderId + ' kuryesi güncellendi!', 'success');
        }
        return true;
    }

    async function adminCancelOrder(orderId) {
        if (typeof global.isCraveloHttpServer === 'function' && global.isCraveloHttpServer()) {
            const res = await global.craveloApiFetch('/api?action=cancel_order', {
                method: 'POST',
                body: JSON.stringify({ id: orderId })
            });
            if (!res || !res.ok) return false;
            if (typeof global.craveloRefreshMyOrders === 'function') await global.craveloRefreshMyOrders();
        } else {
            const target = collectAllPlatformOrders().find(function (o) { return Number(o.serverOrderId || o.id) === Number(orderId); });
            if (target) target.status = 'iptal';
        }
        refreshAdminCommandCenter(false);
        if (typeof global.showToast === 'function') {
            global.showToast('🚫', 'Sipariş #ORD-' + orderId + ' yönetici tarafından iptal edildi.', 'warn');
        }
        return true;
    }

    async function adminAdvanceOrderStatus(orderId, targetStatus) {
        if (typeof global.isCraveloHttpServer === 'function' && global.isCraveloHttpServer()) {
            const res = await global.craveloApiFetch('/api?action=change_status', {
                method: 'POST',
                body: JSON.stringify({ order_id: orderId, target_status: targetStatus })
            });
            if (!res || !res.ok) return false;
            if (typeof global.craveloRefreshMyOrders === 'function') await global.craveloRefreshMyOrders();
        } else {
            const target = collectAllPlatformOrders().find(function (o) { return Number(o.serverOrderId || o.id) === Number(orderId); });
            if (target) target.status = targetStatus;
        }
        refreshAdminCommandCenter(false);
        if (typeof global.showToast === 'function') {
            global.showToast('✅', 'Sipariş #ORD-' + orderId + ' durumu "' + targetStatus + '" yapıldı.', 'success');
        }
        return true;
    }

    async function adminToggleRestaurant(restId) {
        if (typeof global.GustoState === 'undefined') return false;
        const rest = (global.GustoState.get().restaurants || []).find(function (r) { return Number(r.id) === Number(restId); });
        if (!rest) return false;
        const nextActive = rest.isActive === false;

        if (typeof global.isCraveloHttpServer === 'function' && global.isCraveloHttpServer()) {
            const res = await global.craveloApiFetch('/api?action=toggle_restaurant_active', {
                method: 'POST',
                body: JSON.stringify({ restaurant_id: rest.id, is_active: nextActive })
            });
            if (!res || !res.ok) return false;
            rest.isActive = Boolean(res.data && res.data.is_active);
        } else {
            rest.isActive = nextActive;
        }
        renderAdminRestaurants();
        if (typeof global.showToast === 'function') {
            global.showToast(rest.isActive ? '✅' : '🚪', rest.name + (rest.isActive ? ' açıldı.' : ' kapatıldı.'), rest.isActive ? 'success' : 'warn');
        }
        return true;
    }

    function exportAdminOrdersCsv() {
        const orders = collectAllPlatformOrders();
        const rows = [['Sipariş No', 'Müşteri', 'Restoran', 'Teslimat Modu', 'Durum', 'Kurye', 'Tutar (TL)', 'Tarih']];
        orders.forEach(function (o) {
            rows.push([
                '#ORD-' + (o.serverOrderId || o.id),
                o.customerName || 'Müşteri',
                o.rName || ('Restoran #' + o.rId),
                o.deliveryMode === 'takeaway' ? 'Gel-Al' : 'Kurye',
                o.status || '',
                o.courier || '-',
                Number(o.total || 0).toFixed(2),
                o.createdAt || ''
            ]);
        });
        const csv = '\uFEFF' + rows.map(function (r) {
            return r.map(function (c) { return '"' + String(c).replace(/"/g, '""') + '"'; }).join(',');
        }).join('\n');

        if (typeof Blob !== 'undefined' && typeof URL !== 'undefined' && typeof URL.createObjectURL === 'function' && typeof document !== 'undefined') {
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = 'cravelo_platform_siparisler.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(a.href);
        }
        if (typeof global.showToast === 'function') {
            global.showToast('📊', orders.length + ' platform siparişi CSV olarak indirildi.', 'success');
        }
    }

    if (typeof global.renderAdminCouriers === 'function') {
        const origRenderAdminCouriers = global.renderAdminCouriers;
        global.renderAdminCouriers = function () {
            const out = origRenderAdminCouriers();
            refreshAdminCommandCenter(true);
            return out;
        };
    }

    if (typeof global.applyRoleIsolation === 'function') {
        const origApplyRole = global.applyRoleIsolation;
        global.applyRoleIsolation = function (role) {
            const out = origApplyRole(role);
            if (role === 'admin') {
                refreshAdminCommandCenter(false);
            }
            return out;
        };
    }

    global.refreshAdminCommandCenter = refreshAdminCommandCenter;
    global.renderAdminAnalytics = renderAdminAnalytics;
    global.renderAdminOrdersTable = renderAdminOrdersTable;
    global.renderAdminRestaurants = renderAdminRestaurants;
    global.adminReassignCourier = adminReassignCourier;
    global.adminCancelOrder = adminCancelOrder;
    global.adminAdvanceOrderStatus = adminAdvanceOrderStatus;
    global.adminToggleRestaurant = adminToggleRestaurant;
    global.exportAdminOrdersCsv = exportAdminOrdersCsv;
})(typeof window !== 'undefined' ? window : globalThis);
