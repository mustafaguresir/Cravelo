/**
 * Cravelo — Müşteri Deneyimi Profesyonel Özellik Modülü
 * Sunucu Kalıcı Profil & Adresler, Gerçek Değerlendirmeler & Dinamik Puanlar, Çift Yönlü Müşteri <-> Kurye Canlı Sohbeti
 * public/js/features/customer-pro.js
 */
(function (global) {
    'use strict';

    let activeChatOrderId = null;
    let chatPollTimer = null;

    function safeEscape(val) {
        if (typeof global.escapeHtml === 'function') return global.escapeHtml(val);
        return String(val ?? '').replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function isLoggedInOnServer() {
        return typeof global.isCraveloHttpServer === 'function'
            && global.isCraveloHttpServer()
            && Boolean(global.ACTIVE_SESSION && global.ACTIVE_SESSION.id);
    }

    // 1. Profil Güncellemelerini Sunucuda Kalıcı Kaydet
    if (typeof global.handleUpdateProfile === 'function') {
        const origUpdateProfile = global.handleUpdateProfile;
        global.handleUpdateProfile = async function (e) {
            origUpdateProfile(e);
            if (!isLoggedInOnServer()) return;
            const st = global.GustoState.get();
            const u = st.currentUser || {};
            await global.craveloApiFetch('/api?action=update_profile', {
                method: 'POST',
                silentError: true,
                body: JSON.stringify({
                    full_name: u.fullName || '',
                    phone: u.phone || '',
                    email: u.email || ''
                })
            });
        };
    }

    // 2. Adres Değişikliklerini Sunucuda Kalıcı Kaydet
    async function syncAddressesToServer() {
        if (!isLoggedInOnServer() || typeof global.GustoState === 'undefined') return null;
        const st = global.GustoState.get();
        return global.craveloApiFetch('/api?action=save_addresses', {
            method: 'POST',
            silentError: true,
            body: JSON.stringify({
                addresses: st.savedAddresses || [],
                active_location: st.customerLocation || null
            })
        });
    }

    ['addNewAddress', 'deleteSavedAddress', 'selectSavedAddress'].forEach(function (fnName) {
        if (typeof global[fnName] === 'function') {
            const orig = global[fnName];
            global[fnName] = function () {
                const res = orig.apply(this, arguments);
                syncAddressesToServer();
                return res;
            };
        }
    });

    // 3. Gerçek Değerlendirme Gönderimi + Dinamik Restoran Yorumları
    function countActiveStars(containerId) {
        if (typeof document === 'undefined') return 5;
        const c = document.getElementById(containerId);
        if (!c) return 5;
        const n = c.querySelectorAll('i.active').length;
        return Math.max(1, Math.min(5, n || 5));
    }

    function getSelectedRatingTags() {
        if (typeof document === 'undefined') return [];
        const wrap = document.getElementById('rating-tags-wrap');
        if (!wrap) return [];
        const out = [];
        wrap.querySelectorAll('.rating-tag-chip.active').forEach(function (el) {
            const t = (el.innerText || el.textContent || '').trim();
            if (t) out.push(t);
        });
        return out;
    }

    if (typeof global.submitRatingAndReward === 'function') {
        const origSubmitRating = global.submitRatingAndReward;
        global.submitRatingAndReward = async function () {
            const st = typeof global.GustoState !== 'undefined' ? global.GustoState.get() : null;
            const taste = countActiveStars('taste-stars');
            const speed = countActiveStars('speed-stars');
            const service = countActiveStars('service-stars');
            const tags = getSelectedRatingTags();
            const comment = (typeof document !== 'undefined' && document.getElementById('review-comment')?.value || '').trim();

            const candidateOrder = st
                ? ((st.orderHistory || []).find(function (o) { return o && o.status === 'teslim_edildi' && !o.isRated; }) || (st.orderHistory || [])[0])
                : null;

            if (isLoggedInOnServer() && candidateOrder) {
                const serverOrderId = Number(candidateOrder.serverOrderId || candidateOrder.id || 0);
                const res = await global.craveloApiFetch('/api?action=submit_review', {
                    method: 'POST',
                    body: JSON.stringify({
                        order_id: serverOrderId,
                        restaurant_id: Number(candidateOrder.rId || 1),
                        taste: taste,
                        speed: speed,
                        service: service,
                        tags: tags,
                        comment: comment
                    })
                });
                if (!res || !res.ok) return;

                candidateOrder.isRated = true;
                candidateOrder.reviewComment = comment;
                if (res.data && typeof res.data.wallet_points === 'number') {
                    st.userPoints = res.data.wallet_points;
                }
                if (res.data && res.data.restaurant_rating) {
                    const rest = (st.restaurants || []).find(function (r) { return Number(r.id) === Number(candidateOrder.rId); });
                    if (rest) {
                        rest.rating = String(res.data.restaurant_rating);
                        rest.reviewCount = Number(res.data.review_count || (rest.reviewCount || 0) + 1);
                    }
                }
                if (res.data && res.data.review) {
                    if (!Array.isArray(global.CRAVELO_REVIEWS)) global.CRAVELO_REVIEWS = [];
                    global.CRAVELO_REVIEWS.unshift(res.data.review);
                }

                const pts = Number(st.userPoints || 0);
                const accPts = document.getElementById('acc-points');
                const walletDisp = document.getElementById('acc-wallet-display');
                const navRole = document.getElementById('nav-role');
                if (accPts) accPts.innerText = pts + ' Cravelo Puan (' + (pts / 10).toFixed(2) + ' ₺)';
                if (walletDisp) walletDisp.innerText = pts + ' Puan (' + (pts / 10).toFixed(2) + ' ₺)';
                if (navRole && st.currentRole === 'customer') navRole.innerHTML = '<i class="fa-solid fa-coins"></i> ' + pts + ' Puan';

                if (typeof global.GustoState.persist === 'function') global.GustoState.persist();
                if (typeof global.closeRatingModal === 'function') global.closeRatingModal();
                if (typeof global.GustoAudio !== 'undefined' && typeof global.GustoAudio.play === 'function') global.GustoAudio.play('win');
                if (typeof global.showToast === 'function') {
                    global.showToast('🌟', 'Değerlendirmeniz sunucuya kaydedildi! +25 Cravelo Puan eklendi.', 'success');
                }
                return;
            }

            return origSubmitRating();
        };
    }

    function buildReviewCardHtml(rev) {
        const stars = Number(rev.rating || 5).toFixed(1);
        const tagsHtml = Array.isArray(rev.tags) && rev.tags.length
            ? '<div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:8px;">' + rev.tags.map(function (t) {
                return '<span style="font-size:11px;padding:3px 9px;border-radius:999px;background:var(--surface);border:1px solid var(--border);color:var(--text-2);font-weight:700;">' + safeEscape(t) + '</span>';
            }).join('') + '</div>'
            : '';

        return '<div style="background:var(--surface-2);border:1px solid var(--border);border-radius:14px;padding:13px 15px;">' +
            '<div style="display:flex;justify-content:space-between;align-items:center;gap:8px;">' +
                '<strong style="font-size:13.5px;color:var(--text);"><i class="fa-solid fa-user-circle" style="color:var(--primary);margin-right:5px;"></i>' + safeEscape(rev.author || 'Cravelo Müşterisi') + '</strong>' +
                '<span class="status-pill warning" style="font-size:11.5px;"><i class="fa-solid fa-star"></i> ' + stars + '</span>' +
            '</div>' +
            '<div style="font-size:11.5px;color:var(--text-3);margin-top:3px;">Lezzet: ' + Number(rev.taste || 5) + '/5 • Hız: ' + Number(rev.speed || 5) + '/5 • Servis: ' + Number(rev.service || 5) + '/5 • ' + safeEscape(rev.created_at || '') + '</div>' +
            (rev.comment ? '<div style="font-size:13px;color:var(--text-2);margin-top:8px;line-height:1.45;">"' + safeEscape(rev.comment) + '"</div>' : '') +
            tagsHtml +
        '</div>';
    }

    function renderRestaurantReviewsInline(restId) {
        if (typeof document === 'undefined') return;
        const sec = document.getElementById('rest-reviews-section');
        const listEl = document.getElementById('rest-reviews-inline-list');
        const cntEl = document.getElementById('mh-review-count');
        const allReviews = Array.isArray(global.CRAVELO_REVIEWS) ? global.CRAVELO_REVIEWS : [];
        const restReviews = allReviews.filter(function (r) { return Number(r.restaurant_id) === Number(restId); });

        if (cntEl) {
            cntEl.innerText = restReviews.length > 0 ? ('(' + restReviews.length + ' Yorum)') : '(Yorumlar)';
        }
        if (!sec || !listEl) return;
        if (!restReviews.length) {
            sec.style.display = 'none';
            return;
        }
        sec.style.display = 'block';
        listEl.innerHTML = restReviews.slice(0, 3).map(buildReviewCardHtml).join('');
    }

    async function openRestaurantReviews(restId) {
        if (typeof document === 'undefined' || typeof global.GustoState === 'undefined') return;
        const st = global.GustoState.get();
        const targetRestId = Number(restId || st.selectedRestId || 1);
        const rest = (st.restaurants || []).find(function (r) { return Number(r.id) === targetRestId; });

        const titleEl = document.getElementById('reviews-modal-title');
        const subEl = document.getElementById('reviews-modal-sub');
        const listEl = document.getElementById('reviews-modal-list');
        const overlay = document.getElementById('reviews-overlay');
        if (!overlay || !listEl) return;

        if (titleEl) titleEl.innerText = (rest ? rest.name : 'Restoran') + ' Değerlendirmeleri';
        if (subEl) subEl.innerText = 'Ortalama Puan: ⭐ ' + (rest ? rest.rating : '4.8') + ' • Doğrulanmış Cravelo yorumları';

        let reviews = (Array.isArray(global.CRAVELO_REVIEWS) ? global.CRAVELO_REVIEWS : []).filter(function (r) {
            return Number(r.restaurant_id) === targetRestId;
        });

        if (typeof global.isCraveloHttpServer === 'function' && global.isCraveloHttpServer()) {
            const res = await global.craveloApiFetch('/api?action=get_reviews&restaurant_id=' + encodeURIComponent(targetRestId), {
                method: 'GET',
                silentError: true
            });
            if (res && res.ok && res.data && Array.isArray(res.data.reviews)) {
                reviews = res.data.reviews;
            }
        }

        if (!reviews.length) {
            listEl.innerHTML = '<div style="text-align:center;padding:30px 16px;color:var(--text-3);font-weight:700;">Bu restoran için henüz değerlendirme yazılmamış. İlk yorumu siz bırakın!</div>';
        } else {
            listEl.innerHTML = reviews.map(buildReviewCardHtml).join('');
        }
        overlay.style.display = 'flex';
    }

    function closeReviewsModal() {
        if (typeof document === 'undefined') return;
        const overlay = document.getElementById('reviews-overlay');
        if (overlay) overlay.style.display = 'none';
    }

    if (typeof global.selectRestaurant === 'function') {
        const origSelect = global.selectRestaurant;
        global.selectRestaurant = function (id) {
            const out = origSelect(id);
            renderRestaurantReviewsInline(id);
            return out;
        };
    }

    // 4. Gerçek Sunucu Destekli Çift Yönlü Müşteri <-> Kurye Canlı Sohbeti
    function renderServerChatMessages(messages) {
        if (typeof document === 'undefined') return;
        const area = document.getElementById('chat-messages');
        if (!area) return;
        const myRole = (global.ACTIVE_SESSION && global.ACTIVE_SESSION.role)
            || (typeof global.GustoState !== 'undefined' && global.GustoState.get().currentRole)
            || 'customer';

        if (!Array.isArray(messages) || !messages.length) {
            area.innerHTML = '<div class="chat-msg courier">Canlı sipariş hattı açıldı. İletmek istediğiniz mesajı aşağıdan yazabilirsiniz.</div>';
            return;
        }

        area.innerHTML = messages.map(function (m) {
            const isMine = (String(m.sender_role || '') === String(myRole));
            const cls = isMine ? 'user' : 'courier';
            return '<div class="chat-msg ' + cls + '">' +
                '<div style="font-size:10.5px;font-weight:800;opacity:0.8;margin-bottom:3px;">' + safeEscape(m.sender_name || (isMine ? 'Siz' : 'Karşı Taraf')) + '</div>' +
                '<div style="line-height:1.45;">' + safeEscape(m.text || '') + '</div>' +
                '<div style="font-size:10px;opacity:0.7;margin-top:4px;text-align:' + (isMine ? 'right' : 'left') + ';">' + safeEscape(m.time || '') + (isMine ? ' ✓✓' : '') + '</div>' +
            '</div>';
        }).join('');
        area.scrollTop = area.scrollHeight;
    }

    async function loadServerChatMessages(orderId) {
        if (!isLoggedInOnServer() || !orderId) return false;
        const res = await global.craveloApiFetch('/api?action=get_messages&order_id=' + encodeURIComponent(orderId), {
            method: 'GET',
            silentError: true
        });
        if (res && res.ok && res.data && Array.isArray(res.data.messages)) {
            renderServerChatMessages(res.data.messages);
            return true;
        }
        return false;
    }

    function stopChatPolling() {
        if (chatPollTimer) {
            clearInterval(chatPollTimer);
            chatPollTimer = null;
        }
    }

    function openChatForOrder(orderId) {
        if (typeof global.GustoState === 'undefined' || typeof document === 'undefined') return;
        const st = global.GustoState.get();
        let targetOrder = null;

        if (orderId) {
            if (st.activeOrder && (Number(st.activeOrder.id) === Number(orderId) || Number(st.activeOrder.serverOrderId) === Number(orderId))) {
                targetOrder = st.activeOrder;
            } else {
                targetOrder = (st.orderHistory || []).find(function (o) {
                    return o && (Number(o.id) === Number(orderId) || Number(o.serverOrderId) === Number(orderId));
                });
            }
        } else {
            targetOrder = st.activeOrder;
        }

        if (!targetOrder) {
            if (typeof global.showToast === 'function') {
                global.showToast('🛵', 'Aktif bir sipariş bulunmadığı için sohbet başlatılamaz.', 'warn');
            }
            return;
        }

        const myRole = (global.ACTIVE_SESSION && global.ACTIVE_SESSION.role) || st.currentRole || 'customer';
        const nameEl = document.getElementById('chat-courier-name');
        if (nameEl) {
            nameEl.innerText = myRole === 'courier'
                ? ((targetOrder.customerName || 'Müşteri') + ' (#ORD-' + (targetOrder.serverOrderId || targetOrder.id) + ')')
                : ((targetOrder.courier || 'Kurye') + ' (Kurye 🛵)');
        }

        activeChatOrderId = Number(targetOrder.serverOrderId || targetOrder.id || 0);
        const overlay = document.getElementById('chat-overlay');
        if (overlay) overlay.style.display = 'flex';

        stopChatPolling();
        if (isLoggedInOnServer() && activeChatOrderId > 0) {
            loadServerChatMessages(activeChatOrderId);
            chatPollTimer = setInterval(function () {
                if (typeof document !== 'undefined' && document.hidden === true) return;
                if (activeChatOrderId > 0) loadServerChatMessages(activeChatOrderId);
            }, 3000);
        }
    }

    async function sendRealChatText(msg) {
        const clean = String(msg || '').trim();
        if (!clean) return false;
        if (isLoggedInOnServer() && activeChatOrderId > 0) {
            const res = await global.craveloApiFetch('/api?action=send_message', {
                method: 'POST',
                body: JSON.stringify({ order_id: activeChatOrderId, text: clean })
            });
            if (res && res.ok) {
                await loadServerChatMessages(activeChatOrderId);
                if (typeof global.GustoAudio !== 'undefined' && typeof global.GustoAudio.play === 'function') {
                    global.GustoAudio.play('status');
                }
                return true;
            }
        }
        return false;
    }

    const origOpenChat = global.openChatModal;
    global.openChatModal = function (orderId) {
        if (orderId || isLoggedInOnServer()) {
            openChatForOrder(orderId || null);
            return;
        }
        if (typeof origOpenChat === 'function') origOpenChat();
    };

    const origCloseChat = global.closeChatModal;
    global.closeChatModal = function () {
        stopChatPolling();
        activeChatOrderId = null;
        if (typeof origCloseChat === 'function') origCloseChat();
    };

    const origSendQuickChat = global.sendQuickChat;
    global.sendQuickChat = async function (msg) {
        if (isLoggedInOnServer() && activeChatOrderId > 0) {
            await sendRealChatText(msg);
            return;
        }
        if (typeof origSendQuickChat === 'function') origSendQuickChat(msg);
    };

    const origSendChat = global.sendChatMessage;
    global.sendChatMessage = async function () {
        if (isLoggedInOnServer() && activeChatOrderId > 0) {
            const input = document.getElementById('chat-input');
            if (!input) return;
            const msg = input.value.trim();
            if (!msg) return;
            input.value = '';
            await sendRealChatText(msg);
            return;
        }
        if (typeof origSendChat === 'function') origSendChat();
    };

    global.openCourierOrderChat = function (orderId) { openChatForOrder(orderId); };
    global.openRestaurantReviews = openRestaurantReviews;
    global.closeReviewsModal = closeReviewsModal;
    global.renderRestaurantReviewsInline = renderRestaurantReviewsInline;
    global.syncAddressesToServer = syncAddressesToServer;
})(typeof window !== 'undefined' ? window : globalThis);
