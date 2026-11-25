/**
 * Cravelo — Kurye Kokpiti Profesyonel Özellik Modülü
 * Canlı GPS Sunucu Kalıcılığı, Vardiya Durumu (Müsait / Mola) ve Çift Yönlü Müşteri Sohbeti
 * public/js/features/courier-pro.js
 */
(function (global) {
    'use strict';

    function getActiveCourier() {
        if (typeof global.GustoState === 'undefined') return null;
        const st = global.GustoState.get();
        return (st.couriers || []).find(function (c) { return Number(c.id) === Number(st.selectedCourierId); }) || (st.couriers || [])[0] || null;
    }

    function syncCourierShiftButtons() {
        if (typeof document === 'undefined') return;
        const c = getActiveCourier();
        if (!c) return;
        const stVal = String(c.status || 'musait').toLowerCase();
        const btnMusait = document.getElementById('courier-shift-musait');
        const btnMesgul = document.getElementById('courier-shift-mesgul');
        const badge = document.getElementById('courier-badge-status');

        if (btnMusait) {
            btnMusait.className = 'btn btn-sm ' + (stVal === 'musait' ? 'btn-success' : 'btn-outline');
        }
        if (btnMesgul) {
            btnMesgul.className = 'btn btn-sm ' + (stVal !== 'musait' ? 'btn-primary' : 'btn-outline');
        }
        if (badge && stVal !== 'musait' && badge.innerText === 'MÜSAİT') {
            badge.className = 'badge badge-hazirlaniyor';
            badge.innerText = stVal === 'offline' ? 'ÇEVRİMDIŞI' : 'MOLA / MEŞGUL';
        }
    }

    async function persistCourierLocationToServer(courier) {
        if (!courier) return null;
        if (typeof global.isCraveloHttpServer !== 'function' || !global.isCraveloHttpServer()) return null;
        if (!global.ACTIVE_SESSION || !global.ACTIVE_SESSION.id) return null;
        if (typeof global.craveloApiFetch !== 'function') return null;

        return global.craveloApiFetch('/api?action=update_courier_location', {
            method: 'POST',
            silentError: true,
            body: JSON.stringify({
                courier_id: courier.id,
                x: Number(courier.x || 14),
                y: Number(courier.y || 10),
                lat: Number(courier.lat || 40.9910),
                lon: Number(courier.lon || 29.0250),
                neighborhood: String(courier.neighborhood || 'Kadıköy')
            })
        });
    }

    async function setCourierShiftStatus(status) {
        const c = getActiveCourier();
        if (!c) return false;
        const cleanStatus = String(status || 'musait').toLowerCase();

        if (typeof global.isCraveloHttpServer === 'function' && global.isCraveloHttpServer() && global.ACTIVE_SESSION && global.ACTIVE_SESSION.id) {
            const res = await global.craveloApiFetch('/api?action=update_courier_status', {
                method: 'POST',
                body: JSON.stringify({ courier_id: c.id, status: cleanStatus })
            });
            if (!res || !res.ok) return false;
        }

        c.status = cleanStatus;
        if (typeof global.GustoState !== 'undefined' && typeof global.GustoState.persist === 'function') {
            global.GustoState.persist();
        }
        if (typeof global.renderCourierPanel === 'function') global.renderCourierPanel();
        syncCourierShiftButtons();
        if (typeof global.showToast === 'function') {
            global.showToast(
                cleanStatus === 'musait' ? '✅' : '☕',
                cleanStatus === 'musait' ? 'Vardiya durumunuz MÜSAİT olarak kaydedildi.' : 'Mola / Meşgul moduna geçildi — yeni otomatik atamalar duraklatıldı.',
                cleanStatus === 'musait' ? 'success' : 'warn'
            );
        }
        return true;
    }

    function injectCourierChatButtons() {
        if (typeof document === 'undefined' || typeof global.GustoState === 'undefined') return;
        const area = document.getElementById('courier-task-area');
        if (!area) return;
        const cards = area.querySelectorAll('.mission-card');
        cards.forEach(function (card) {
            if (card.querySelector('.courier-chat-customer-btn')) return;
            const actionBtn = card.querySelector('button[onclick*="courierChangeStatus"]');
            if (!actionBtn) return;
            const match = String(actionBtn.getAttribute('onclick') || '').match(/,\s*(\d+)\)/);
            if (!match) return;
            const orderId = Number(match[1]);

            const chatBtn = document.createElement('button');
            chatBtn.type = 'button';
            chatBtn.className = 'btn btn-outline courier-chat-customer-btn';
            chatBtn.style.cssText = 'width:100%;margin-top:10px;padding:11px;font-size:13.5px;border-radius:12px;';
            chatBtn.innerHTML = '<i class="fa-solid fa-comments"></i> Müşteriyle Canlı Mesajlaş (#ORD-' + orderId + ')';
            chatBtn.onclick = function () {
                if (typeof global.openCourierOrderChat === 'function') {
                    global.openCourierOrderChat(orderId);
                } else if (typeof global.openChatModal === 'function') {
                    global.openChatModal(orderId);
                }
            };
            actionBtn.parentNode.insertBefore(chatBtn, actionBtn.nextSibling);
        });
    }

    if (typeof global.updateCourierLocation === 'function') {
        const origUpdateLoc = global.updateCourierLocation;
        global.updateCourierLocation = function () {
            const out = origUpdateLoc();
            persistCourierLocationToServer(getActiveCourier());
            return out;
        };
    }

    if (typeof global.setCourierLandmark === 'function') {
        const origSetLandmark = global.setCourierLandmark;
        global.setCourierLandmark = function (x, y, lat, lon, neighborhood) {
            const out = origSetLandmark(x, y, lat, lon, neighborhood);
            persistCourierLocationToServer(getActiveCourier());
            return out;
        };
    }

    if (typeof global.renderCourierPanel === 'function') {
        const origRenderCourier = global.renderCourierPanel;
        global.renderCourierPanel = function () {
            const out = origRenderCourier();
            syncCourierShiftButtons();
            injectCourierChatButtons();
            return out;
        };
    }

    global.setCourierShiftStatus = setCourierShiftStatus;
    global.syncCourierShiftButtons = syncCourierShiftButtons;
    global.persistCourierLocationToServer = persistCourierLocationToServer;
})(typeof window !== 'undefined' ? window : globalThis);
