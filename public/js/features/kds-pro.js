/**
 * Cravelo — Restoran Mutfak (KDS) Profesyonel Özellik Modülü
 * Tam Menü CRUD (Ekle / Düzenle), Şube Aç/Kapa, Günlük Satış Raporu & CSV
 * public/js/features/kds-pro.js
 */
(function (global) {
    'use strict';

    let lastKdsReportFetchTs = 0;
    let lastKdsReportRestId = 0;

    function safeEscape(val) {
        if (typeof global.escapeHtml === 'function') return global.escapeHtml(val);
        return String(val ?? '').replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function todayIsoDate() {
        const d = new Date();
        const pad = function (n) { return String(n).padStart(2, '0'); };
        return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
    }

    function syncKdsRestaurantActiveButton() {
        if (typeof document === 'undefined' || typeof global.GustoState === 'undefined') return;
        const btn = document.getElementById('kds-rest-active-btn');
        if (!btn) return;
        const st = global.GustoState.get();
        const rest = (st.restaurants || []).find(function (r) { return Number(r.id) === Number(st.selectedVendorRestId); });
        const isActive = rest ? (rest.isActive !== false) : true;
        btn.className = 'btn btn-sm ' + (isActive ? 'btn-success' : 'btn-outline-danger');
        btn.innerHTML = isActive
            ? '<i class="fa-solid fa-store"></i> <span>Şube Açık</span>'
            : '<i class="fa-solid fa-door-closed"></i> <span>Şube Kapalı</span>';
    }

    async function toggleCurrentRestaurantActive() {
        if (typeof global.GustoState === 'undefined') return;
        const st = global.GustoState.get();
        const rest = (st.restaurants || []).find(function (r) { return Number(r.id) === Number(st.selectedVendorRestId); });
        if (!rest) return;
        const desiredActive = rest.isActive === false;

        if (typeof global.isCraveloHttpServer === 'function' && global.isCraveloHttpServer() && global.ACTIVE_SESSION && global.ACTIVE_SESSION.id) {
            const res = await global.craveloApiFetch('/api?action=toggle_restaurant_active', {
                method: 'POST',
                body: JSON.stringify({ restaurant_id: rest.id, is_active: desiredActive })
            });
            if (!res || !res.ok) return;
            rest.isActive = Boolean(res.data && res.data.is_active);
        } else {
            rest.isActive = desiredActive;
        }

        if (typeof global.GustoState.persist === 'function') global.GustoState.persist();
        syncKdsRestaurantActiveButton();
        if (typeof global.showToast === 'function') {
            global.showToast(
                rest.isActive ? '✅' : '🚪',
                rest.isActive ? (rest.name + ' sipariş alımına açıldı.') : (rest.name + ' geçici olarak sipariş alımına kapatıldı.'),
                rest.isActive ? 'success' : 'warn'
            );
        }
    }

    function openKdsItemModal(itemId) {
        if (typeof document === 'undefined' || typeof global.GustoState === 'undefined') return;
        const overlay = document.getElementById('kds-menu-item-overlay');
        if (!overlay) return;
        const st = global.GustoState.get();
        const rest = (st.restaurants || []).find(function (r) { return Number(r.id) === Number(st.selectedVendorRestId); }) || st.restaurants[0];
        const titleEl = document.getElementById('kds-item-modal-title');
        const idEl = document.getElementById('kds-item-id');
        const nameEl = document.getElementById('kds-item-name');
        const priceEl = document.getElementById('kds-item-price');
        const catEl = document.getElementById('kds-item-category');
        const descEl = document.getElementById('kds-item-desc');
        const imgEl = document.getElementById('kds-item-img');
        const availEl = document.getElementById('kds-item-available');

        const existing = itemId ? (st.menuItems || []).find(function (m) { return Number(m.id) === Number(itemId); }) : null;
        if (existing) {
            if (titleEl) titleEl.innerText = 'Menü Ürününü Düzenle (#' + existing.id + ')';
            if (idEl) idEl.value = String(existing.id);
            if (nameEl) nameEl.value = existing.name || '';
            if (priceEl) priceEl.value = String(existing.price || '');
            if (catEl) catEl.value = existing.category || (rest && rest.cat) || 'Kebap';
            if (descEl) descEl.value = existing.desc || '';
            if (imgEl) imgEl.value = existing.img || '';
            if (availEl) availEl.checked = existing.available !== false;
        } else {
            if (titleEl) titleEl.innerText = 'Yeni Menü Ürünü Ekle';
            if (idEl) idEl.value = '0';
            if (nameEl) nameEl.value = '';
            if (priceEl) priceEl.value = '';
            if (catEl) catEl.value = (rest && rest.cat) || 'Kebap';
            if (descEl) descEl.value = '';
            if (imgEl) imgEl.value = '';
            if (availEl) availEl.checked = true;
        }
        overlay.style.display = 'flex';
    }

    function closeKdsItemModal() {
        if (typeof document === 'undefined') return;
        const overlay = document.getElementById('kds-menu-item-overlay');
        if (overlay) overlay.style.display = 'none';
    }

    async function submitKdsMenuItem(event) {
        if (event && typeof event.preventDefault === 'function') event.preventDefault();
        if (typeof global.GustoState === 'undefined' || typeof document === 'undefined') return false;

        const st = global.GustoState.get();
        const itemId = parseInt(document.getElementById('kds-item-id')?.value || '0', 10) || 0;
        const name = (document.getElementById('kds-item-name')?.value || '').trim();
        const price = parseFloat(document.getElementById('kds-item-price')?.value || '0');
        const category = (document.getElementById('kds-item-category')?.value || 'Ana Yemek').trim();
        const desc = (document.getElementById('kds-item-desc')?.value || '').trim();
        const image = (document.getElementById('kds-item-img')?.value || '').trim();
        const isAvailable = Boolean(document.getElementById('kds-item-available')?.checked);
        const restaurantId = Number(st.selectedVendorRestId || 1);

        if (name.length < 2 || !isFinite(price) || price <= 0) {
            if (typeof global.showToast === 'function') {
                global.showToast('⚠️', 'Lütfen geçerli bir ürün adı ve fiyat giriniz.', 'warn');
            }
            return false;
        }

        let savedItem = null;
        if (typeof global.isCraveloHttpServer === 'function' && global.isCraveloHttpServer() && global.ACTIVE_SESSION && global.ACTIVE_SESSION.id) {
            const res = await global.craveloApiFetch('/api?action=save_menu_item', {
                method: 'POST',
                body: JSON.stringify({
                    id: itemId,
                    restaurant_id: restaurantId,
                    name: name,
                    price: price,
                    category: category,
                    description: desc,
                    image: image,
                    is_available: isAvailable
                })
            });
            if (!res || !res.ok || !res.data || !res.data.item) return false;
            savedItem = res.data.item;
        } else {
            const maxId = (st.menuItems || []).reduce(function (acc, m) { return Math.max(acc, Number(m.id || 0)); }, 0);
            savedItem = {
                id: itemId > 0 ? itemId : (maxId + 1),
                rId: restaurantId,
                name: name,
                desc: desc || 'Şefin özenle hazırladığı özel lezzet',
                price: Number(price.toFixed(2)),
                category: category,
                available: isAvailable,
                img: image || 'https://images.unsplash.com/photo-1546069901-ba9599a4e63c?auto=format&fit=crop&w=400&q=80'
            };
        }

        const idx = (st.menuItems || []).findIndex(function (m) { return Number(m.id) === Number(savedItem.id); });
        if (idx >= 0) {
            st.menuItems[idx] = Object.assign({}, st.menuItems[idx], {
                name: savedItem.name,
                desc: savedItem.desc,
                price: Number(savedItem.price),
                category: savedItem.category,
                tag: savedItem.category || st.menuItems[idx].tag,
                available: savedItem.available !== false,
                img: savedItem.img || st.menuItems[idx].img
            });
        } else {
            st.menuItems.push({
                id: Number(savedItem.id),
                rId: Number(savedItem.rId || restaurantId),
                name: savedItem.name,
                desc: savedItem.desc || 'Şefin özel tarifi',
                price: Number(savedItem.price),
                category: savedItem.category || category,
                tag: savedItem.category || 'Yeni Ürün',
                available: savedItem.available !== false,
                img: savedItem.img || 'https://images.unsplash.com/photo-1546069901-ba9599a4e63c?auto=format&fit=crop&w=400&q=80'
            });
        }

        if (typeof global.GustoState.persist === 'function') global.GustoState.persist();
        closeKdsItemModal();
        if (typeof global.renderResPanel === 'function') global.renderResPanel();
        if (typeof global.GustoAudio !== 'undefined' && typeof global.GustoAudio.play === 'function') {
            global.GustoAudio.play('success');
        }
        if (typeof global.showToast === 'function') {
            global.showToast('🍽️', itemId > 0 ? ('"' + savedItem.name + '" menüde güncellendi.') : ('"' + savedItem.name + '" menüye eklendi!'), 'success');
        }
        return true;
    }

    function renderKdsReportCards(report) {
        if (typeof document === 'undefined') return;
        const out = document.getElementById('kds-report-output');
        if (!out) return;
        const totalOrders = Number(report.total_orders || 0);
        const delivered = Number(report.delivered_orders || report.completed_orders || 0);
        const cancelled = Number(report.cancelled_orders || 0);
        const revenue = Number(report.total_revenue || 0);
        const dateStr = safeEscape(report.date || todayIsoDate());

        out.innerHTML =
            '<div style="background:var(--surface-2);border:1px solid var(--border);border-radius:14px;padding:14px;">' +
                '<div style="font-size:11.5px;color:var(--text-3);font-weight:800;">RAPOR TARİHİ</div>' +
                '<div style="font-size:18px;font-weight:900;color:var(--text);margin-top:4px;">' + dateStr + '</div>' +
            '</div>' +
            '<div style="background:var(--surface-2);border:1px solid var(--border);border-radius:14px;padding:14px;">' +
                '<div style="font-size:11.5px;color:var(--text-3);font-weight:800;">TOPLAM SİPARİŞ</div>' +
                '<div style="font-size:18px;font-weight:900;color:var(--blue);margin-top:4px;">' + totalOrders + ' Adet</div>' +
            '</div>' +
            '<div style="background:var(--surface-2);border:1px solid var(--border);border-radius:14px;padding:14px;">' +
                '<div style="font-size:11.5px;color:var(--text-3);font-weight:800;">TESLİM / İPTAL</div>' +
                '<div style="font-size:18px;font-weight:900;color:var(--text);margin-top:4px;"><span style="color:var(--success);">' + delivered + '</span> / <span style="color:var(--danger);">' + cancelled + '</span></div>' +
            '</div>' +
            '<div style="background:var(--surface-2);border:1px solid var(--border);border-radius:14px;padding:14px;">' +
                '<div style="font-size:11.5px;color:var(--text-3);font-weight:800;">GÜNLÜK NET CİRO</div>' +
                '<div style="font-size:18px;font-weight:900;color:var(--primary);margin-top:4px;">' + revenue.toFixed(2) + ' ₺</div>' +
            '</div>';
    }

    async function loadKdsDailyReport(force) {
        if (typeof global.GustoState === 'undefined' || typeof document === 'undefined') return null;
        const st = global.GustoState.get();
        const restId = Number(st.selectedVendorRestId || 1);
        const dateEl = document.getElementById('kds-report-date');
        if (dateEl && !dateEl.value) dateEl.value = todayIsoDate();
        const dateVal = (dateEl && dateEl.value) ? dateEl.value : todayIsoDate();

        const now = Date.now();
        if (!force && lastKdsReportRestId === restId && (now - lastKdsReportFetchTs) < 8000) {
            return global._lastKdsReport || null;
        }
        lastKdsReportFetchTs = now;
        lastKdsReportRestId = restId;

        let report = null;
        if (typeof global.isCraveloHttpServer === 'function' && global.isCraveloHttpServer() && global.ACTIVE_SESSION && global.ACTIVE_SESSION.id) {
            const url = '/api?action=daily_report&restaurant_id=' + encodeURIComponent(restId) + '&date=' + encodeURIComponent(dateVal);
            const res = await global.craveloApiFetch(url, { method: 'GET', silentError: true });
            if (res && res.ok && res.data) {
                report = res.data.report || res.data;
            }
        }

        if (!report) {
            const orders = (st.orderHistory || []).filter(function (o) { return o && Number(o.rId) === restId; });
            if (st.activeOrder && Number(st.activeOrder.rId) === restId && !orders.some(function (o) { return o.id === st.activeOrder.id; })) {
                orders.push(st.activeOrder);
            }
            const valid = orders.filter(function (o) { return o.status !== 'iptal'; });
            report = {
                date: dateVal,
                restaurant_id: restId,
                total_orders: orders.length,
                delivered_orders: orders.filter(function (o) { return o.status === 'teslim_edildi'; }).length,
                cancelled_orders: orders.filter(function (o) { return o.status === 'iptal'; }).length,
                total_revenue: valid.reduce(function (s, o) { return s + Number(o.grossTotal ?? o.total ?? 0); }, 0)
            };
        }

        global._lastKdsReport = report;
        renderKdsReportCards(report);
        return report;
    }

    function exportKdsDailyReportCsv() {
        if (typeof global.GustoState === 'undefined' || typeof document === 'undefined') return;
        const st = global.GustoState.get();
        const restId = Number(st.selectedVendorRestId || 1);
        const rest = (st.restaurants || []).find(function (r) { return Number(r.id) === restId; });
        const rep = global._lastKdsReport || { date: todayIsoDate(), total_orders: 0, delivered_orders: 0, cancelled_orders: 0, total_revenue: 0 };

        const rows = [
            ['Restoran', 'Tarih', 'Toplam Sipariş', 'Teslim Edilen', 'İptal Edilen', 'Toplam Ciro (TL)'],
            [rest ? rest.name : ('Restoran #' + restId), rep.date || todayIsoDate(), rep.total_orders || 0, rep.delivered_orders || 0, rep.cancelled_orders || 0, Number(rep.total_revenue || 0).toFixed(2)]
        ];
        const csv = '\uFEFF' + rows.map(function (r) {
            return r.map(function (c) { return '"' + String(c).replace(/"/g, '""') + '"'; }).join(',');
        }).join('\n');

        if (typeof Blob !== 'undefined' && typeof URL !== 'undefined' && typeof URL.createObjectURL === 'function') {
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = 'cravelo_kds_rapor_' + restId + '_' + (rep.date || todayIsoDate()) + '.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(a.href);
        }
        if (typeof global.showToast === 'function') {
            global.showToast('📄', 'KDS Günlük Satış Raporu CSV olarak indirildi.', 'success');
        }
    }

    function injectKdsEditButtons() {
        if (typeof document === 'undefined' || typeof global.GustoState === 'undefined') return;
        const menuBody = document.getElementById('res-menu-body');
        if (!menuBody) return;
        const st = global.GustoState.get();
        const items = (st.menuItems || []).filter(function (m) { return Number(m.rId) === Number(st.selectedVendorRestId); });
        const cards = menuBody.querySelectorAll('.menu-toggle-card');
        cards.forEach(function (card, idx) {
            const item = items[idx];
            if (!item || card.querySelector('.kds-edit-item-btn')) return;
            const toggleBtn = card.querySelector('button[onclick*="toggleMenuItemStock"]');
            if (toggleBtn && toggleBtn.parentNode) {
                const editBtn = document.createElement('button');
                editBtn.type = 'button';
                editBtn.className = 'btn btn-sm btn-outline kds-edit-item-btn';
                editBtn.style.cssText = 'border-radius:999px;padding:6px 12px;margin-right:6px;';
                editBtn.innerHTML = '<i class="fa-solid fa-pen"></i> Düzenle';
                editBtn.onclick = function () { openKdsItemModal(item.id); };
                toggleBtn.parentNode.insertBefore(editBtn, toggleBtn);
            }
        });
    }

    if (typeof global.renderResPanel === 'function') {
        const origRenderResPanel = global.renderResPanel;
        global.renderResPanel = function () {
            const out = origRenderResPanel();
            syncKdsRestaurantActiveButton();
            injectKdsEditButtons();
            loadKdsDailyReport(false);
            return out;
        };
    }

    global.syncKdsRestaurantActiveButton = syncKdsRestaurantActiveButton;
    global.toggleCurrentRestaurantActive = toggleCurrentRestaurantActive;
    global.openKdsItemModal = openKdsItemModal;
    global.closeKdsItemModal = closeKdsItemModal;
    global.submitKdsMenuItem = submitKdsMenuItem;
    global.loadKdsDailyReport = loadKdsDailyReport;
    global.exportKdsDailyReportCsv = exportKdsDailyReportCsv;
})(typeof window !== 'undefined' ? window : globalThis);
