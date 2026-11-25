/**
 * Cravelo — Google Haritalar (Google Maps) Entegrasyonu & 2D Manhattan Harita Motoru
 * Retina (HiDPI) Destekli Canvas ve Leaflet Google Maps Street Tiles
 */

const GustoMap = (function () {
    const GRID_SIZE = 35; // 35x35 koordinat düzlemi (arkaplan hesap motoru)
    let courierAnimProgress = 0;
    let courierAnimPhase = 'to_restaurant'; // 'to_restaurant' | 'to_customer'
    let courierAnimInterval = null;
    let currentAnimPos = { x: 14, y: 10 };
    let currentFilter = 'all'; // 'all' | 'couriers' | 'restaurants' | 'customers'

    function escMapHtml(val) {
        if (val === null || typeof val === 'undefined') return '';
        return String(val)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    // Kadıköy Coğrafi Sınırları ve GPS Dönüşümü
    const KADIKOY_BOUNDS = {
        minLat: 40.9700,
        maxLat: 41.0000,
        minLon: 29.0180,
        maxLon: 29.0550
    };

    function gridToGps(x, y) {
        const safeX = (typeof x === 'number' && !isNaN(x)) ? x : 18;
        const safeY = (typeof y === 'number' && !isNaN(y)) ? y : 14;
        const lat = KADIKOY_BOUNDS.minLat + ((35 - safeY) / 35) * (KADIKOY_BOUNDS.maxLat - KADIKOY_BOUNDS.minLat);
        const lon = KADIKOY_BOUNDS.minLon + (safeX / 35) * (KADIKOY_BOUNDS.maxLon - KADIKOY_BOUNDS.minLon);
        return [lat, lon];
    }

    function gpsToGrid(lat, lon) {
        const y = Math.round(35 - ((lat - KADIKOY_BOUNDS.minLat) / (KADIKOY_BOUNDS.maxLat - KADIKOY_BOUNDS.minLat)) * 35);
        const x = Math.round(((lon - KADIKOY_BOUNDS.minLon) / (KADIKOY_BOUNDS.maxLon - KADIKOY_BOUNDS.minLon)) * 35);
        return {
            x: Math.min(35, Math.max(0, x)),
            y: Math.min(35, Math.max(0, y))
        };
    }

    function getNeighborhoodName(lat, lon) {
        if (lat < 40.9780) return 'Fenerbahçe / Kalamış, Kadıköy';
        if (lat > 40.9900 && lon < 29.0300) return 'Kadıköy Rıhtım / Çarşı';
        if (lon > 29.0400) return 'Söğütlüçeşme / Ziverbey, Kadıköy';
        if (lat < 40.9860 && lon < 29.0300) return 'Caferağa Mah. Moda Cad., Kadıköy';
        if (lat >= 40.9860) return 'Bahariye Caddesi, Kadıköy';
        return 'Kadıköy Moda, İstanbul';
    }

    function getKadıkoyStreetAddress(lat, lon) {
        if (lat < 40.9780) {
            if (lon < 29.0360) return { street: 'Münir Nurettin Selçuk Cad. No:18', neighborhood: 'Kalamış Marina, Kadıköy' };
            if (lon > 29.0450) return { street: 'Cemil Topuzlu Cad. No:24', neighborhood: 'Dalyan / Caddebostan Sahil' };
            return { street: 'Fener Kalamış Caddesi No:42', neighborhood: 'Fenerbahçe Parkı Yanı, Kadıköy' };
        }
        if (lat > 40.9940) {
            if (lon < 29.0260) return { street: 'Rıhtım Caddesi No:12 (İskele Meydanı)', neighborhood: 'Rasimpaşa, Kadıköy' };
            if (lon > 29.0360) return { street: 'Ayrılık Çeşmesi Sokak No:8', neighborhood: 'Hasanpaşa / Rasimpaşa Sınırı' };
            return { street: 'Karakolhane Caddesi No:34', neighborhood: 'Yeldeğirmeni Sanat Mah., Kadıköy' };
        }
        if (lon < 29.0260) {
            if (lat > 40.9880) return { street: 'Güneşli Bahçe Sokak No:16 (Balık Pazarı)', neighborhood: 'Tarihi Çarşı, Caferağa' };
            return { street: 'Mühürdar Caddesi No:28', neighborhood: 'Caferağa Mah., Kadıköy' };
        }
        if (lon > 29.0380 || (lat > 40.9880 && lon > 29.0330)) {
            if (lat > 40.9870 && lat < 40.9920) return { street: 'Söğütlüçeşme Cad. No:52 (Boğa Heykeli)', neighborhood: 'Altıyol, Osmanağa' };
            return { street: 'Kuşdili Caddesi No:14', neighborhood: 'Osmanağa / Yoğurtçu Parkı' };
        }
        if (lat < 40.9850) {
            if (lon < 29.0280) return { street: 'Moda Caddesi No:14/2 (Tramvay Durağı)', neighborhood: 'Moda, Caferağa, Kadıköy' };
            if (lon > 29.0300) return { street: 'Bademaltı Sokak No:9', neighborhood: 'Moda Burnu, Kadıköy' };
            return { street: 'Dr. Esat Işık Caddesi No:22', neighborhood: 'Moda Kültür Bölgesi, Kadıköy' };
        }
        return { street: 'Bahariye Caddesi No:38 (Süreyya Operası Karşısı)', neighborhood: 'Caferağa / Bahariye, Kadıköy' };
    }

    function getStreetLabelForCoords(x, y, lat, lon, fallback) {
        let resolvedLat = lat;
        let resolvedLon = lon;
        if (typeof resolvedLat !== 'number' || typeof resolvedLon !== 'number') {
            const gps = gridToGps(x, y);
            resolvedLat = gps[0];
            resolvedLon = gps[1];
        }
        const addr = getKadıkoyStreetAddress(resolvedLat, resolvedLon);
        if (addr && addr.street) {
            return `${addr.street} • ${fallback || addr.neighborhood}`;
        }
        return fallback || 'Moda Caddesi, Kadıköy';
    }

    function calcDistanceKm(entityA, entityB) {
        const posA = (entityA && entityA.lat && entityA.lon) ? [entityA.lat, entityA.lon] : gridToGps(entityA?.x, entityA?.y);
        const posB = (entityB && entityB.lat && entityB.lon) ? [entityB.lat, entityB.lon] : gridToGps(entityB?.x, entityB?.y);
        if (typeof GustoState !== 'undefined' && typeof GustoState.calculateHaversine === 'function') {
            return GustoState.calculateHaversine(posA[0], posA[1], posB[0], posB[1]);
        }
        return 0.9;
    }

    // Google Haritalar Karo Sunucusu (Türkçe cadde ve mekan etiketleri)
    const GOOGLE_MAPS_TILES = 'https://mt{s}.google.com/vt/lyrs=m&hl=tr&x={x}&y={y}&z={z}';

    // Leaflet Harita Referansları
    const leafletMaps = {
        picker: null,
        courier: null,
        admin: null,
        customer: null
    };
    const leafletLayers = {
        pickerMarker: null,
        courierMarker: null,
        courierRestMarker: null,
        courierCustMarker: null,
        courierPolyline: null,
        courierPolylineGlow: null,
        adminMarkers: [],
        customerMarkers: [],
        customerPolyline: null,
        customerPolylineGlow: null,
        customerCourierMarker: null
    };

    // Tek seferlik Leaflet & Marker CSS Enjeksiyonu (Theme-Aware & Glassmorphic)
    let stylesInjected = false;
    function ensureMapOverlayStyles() {
        if (stylesInjected || typeof document === 'undefined' || !document.head || typeof document.createElement !== 'function') return;
        if (document.getElementById('cravelo-map-overlay-styles')) {
            stylesInjected = true;
            return;
        }
        const style = document.createElement('style');
        style.id = 'cravelo-map-overlay-styles';
        style.textContent = `
            @keyframes craveloPinPulse {
                0% { transform: scale(1); opacity: 0.7; }
                70% { transform: scale(1.75); opacity: 0; }
                100% { transform: scale(1.75); opacity: 0; }
            }
            .cravelo-pin-shell {
                position: relative;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .cravelo-pin-pulse {
                position: absolute;
                inset: -4px;
                border-radius: 50%;
                animation: craveloPinPulse 2.1s cubic-bezier(0.16, 1, 0.3, 1) infinite;
                pointer-events: none;
            }
            .cravelo-pin-core {
                position: relative;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #FFFFFF;
                border: 2.5px solid #FFFFFF;
                transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            }
            .cravelo-pin-core:hover {
                transform: scale(1.12);
            }
            .cravelo-leaflet-popup .leaflet-popup-content-wrapper {
                background: var(--surface, #111827);
                color: var(--text, #F8FAFC);
                border: 1px solid var(--border, rgba(255, 255, 255, 0.14));
                border-radius: 16px;
                padding: 0;
                box-shadow: 0 18px 38px rgba(0, 0, 0, 0.35);
                font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            }
            .cravelo-leaflet-popup .leaflet-popup-content {
                margin: 12px 14px;
                min-width: 210px;
                line-height: 1.45;
            }
            .cravelo-leaflet-popup .leaflet-popup-tip {
                background: var(--surface, #111827);
                border: 1px solid var(--border, rgba(255, 255, 255, 0.12));
            }
            .cravelo-leaflet-popup .leaflet-popup-close-button {
                color: var(--text-3, #94A3B8) !important;
                top: 8px !important;
                right: 10px !important;
                font-size: 16px !important;
            }
            .leaflet-control-zoom {
                border: 1px solid rgba(255, 255, 255, 0.14) !important;
                border-radius: 12px !important;
                overflow: hidden !important;
                box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25) !important;
            }
            .leaflet-control-zoom a {
                background: rgba(15, 23, 42, 0.88) !important;
                color: #F8FAFC !important;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
                backdrop-filter: blur(10px);
                width: 32px !important;
                height: 32px !important;
                line-height: 32px !important;
                font-weight: 800 !important;
            }
            .leaflet-control-zoom a:hover {
                background: #FF5722 !important;
                color: #FFFFFF !important;
            }
            [data-theme="light"] .leaflet-control-zoom a {
                background: rgba(255, 255, 255, 0.94) !important;
                color: #0F172A !important;
                border-bottom: 1px solid #E2E8F0 !important;
            }
            [data-theme="light"] .leaflet-control-zoom a:hover {
                background: #FF5722 !important;
                color: #FFFFFF !important;
            }
        `;
        document.head.appendChild(style);
        stylesInjected = true;
    }

    function createPinIcon(kind, size = 34, pulse = false) {
        const palettes = {
            restaurant: { bg: 'linear-gradient(135deg, #FF6B35, #FF5722)', glow: 'rgba(255, 87, 34, 0.48)', icon: 'fa-utensils' },
            courier: { bg: 'linear-gradient(135deg, #38BDF8, #2563EB)', glow: 'rgba(37, 99, 235, 0.48)', icon: 'fa-motorcycle' },
            customer: { bg: 'linear-gradient(135deg, #10B981, #059669)', glow: 'rgba(16, 185, 129, 0.48)', icon: 'fa-house' },
            picker: { bg: 'linear-gradient(135deg, #FF6B35, #EA580C)', glow: 'rgba(255, 87, 34, 0.55)', icon: 'fa-location-dot' }
        };
        const p = palettes[kind] || palettes.restaurant;
        const fontSize = Math.max(12, Math.round(size * 0.44));
        return L.divIcon({
            className: 'custom-map-pin',
            html: `
                <div class="cravelo-pin-shell" style="width:${size}px;height:${size}px;">
                    ${pulse ? `<span class="cravelo-pin-pulse" style="background:${p.glow};"></span>` : ''}
                    <div class="cravelo-pin-core" style="width:${size}px;height:${size}px;background:${p.bg};box-shadow:0 6px 18px ${p.glow};font-size:${fontSize}px;">
                        <i class="fa-solid ${p.icon}"></i>
                    </div>
                </div>
            `,
            iconSize: [size, size],
            iconAnchor: [Math.round(size / 2), Math.round(size / 2)],
            popupAnchor: [0, -Math.round(size / 2) - 4]
        });
    }

    function shouldShowLayer(layerType) {
        if (currentFilter === 'all') return true;
        return currentFilter === layerType;
    }

    function setFilter(type, btnEl) {
        currentFilter = type || 'all';
        const group = document.getElementById('map-filter-group');
        if (group && typeof group.querySelectorAll === 'function') {
            group.querySelectorAll('button').forEach(b => b.classList.remove('active'));
        } else if (btnEl && btnEl.parentElement && typeof btnEl.parentElement.querySelectorAll === 'function') {
            btnEl.parentElement.querySelectorAll('button').forEach(b => b.classList.remove('active'));
        }
        if (btnEl && btnEl.classList) btnEl.classList.add('active');
        drawMap('admin-map-canvas');
        drawMap('courier-map-canvas');
    }

    function hideTooltip() {
        const tooltip = document.getElementById('map-hud-tooltip');
        if (tooltip) tooltip.style.display = 'none';
    }

    function handleCanvasMouseMove(e, canvasId) {
        const canvas = document.getElementById(canvasId);
        const tooltip = document.getElementById('map-hud-tooltip');
        if (!canvas || !tooltip) return;

        const rect = canvas.getBoundingClientRect();
        const mouseX = e.clientX - rect.left;
        const mouseY = e.clientY - rect.top;

        const stepX = rect.width / GRID_SIZE;
        const stepY = rect.height / GRID_SIZE;

        const state = GustoState.get();
        const restaurants = state.restaurants || [];
        const couriers = state.couriers || [];
        const custLoc = state.customerLocation || { x: 18, y: 14, name: 'Moda, Kadıköy' };

        let hoveredEntity = null;
        let entityType = null;
        let minPixelDist = 22;

        // 1. Kuryeleri kontrol et
        if (shouldShowLayer('couriers')) {
            for (const c of couriers) {
                const cx = c.x * stepX;
                const cy = c.y * stepY;
                const d = Math.hypot(mouseX - cx, mouseY - cy);
                if (d < minPixelDist) {
                    hoveredEntity = c;
                    entityType = 'courier';
                    minPixelDist = d;
                }
            }
        }

        // 2. Restoranları kontrol et
        if (shouldShowLayer('restaurants')) {
            for (const r of restaurants) {
                const rx = r.x * stepX;
                const ry = r.y * stepY;
                const d = Math.hypot(mouseX - rx, mouseY - ry);
                if (d < minPixelDist) {
                    hoveredEntity = r;
                    entityType = 'restaurant';
                    minPixelDist = d;
                }
            }
        }

        // 3. Müşteri teslimat noktasını kontrol et
        if (shouldShowLayer('customers')) {
            const hx = custLoc.x * stepX;
            const hy = custLoc.y * stepY;
            const hd = Math.hypot(mouseX - hx, mouseY - hy);
            if (hd < minPixelDist) {
                hoveredEntity = custLoc;
                entityType = 'customer';
            }
        }

        if (hoveredEntity) {
            let html = '';
            if (entityType === 'courier') {
                const distKm = calcDistanceKm(hoveredEntity, custLoc);
                const score = GustoState.calculateCourierScore(distKm, hoveredEntity.active);
                const streetText = getStreetLabelForCoords(hoveredEntity.x, hoveredEntity.y, hoveredEntity.lat, hoveredEntity.lon, hoveredEntity.neighborhood);
                const statusBg = hoveredEntity.active > 0 ? 'rgba(245,158,11,0.18);color:#F59E0B' : 'rgba(16,185,129,0.18);color:#10B981';
                html = `
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:6px;">
                        <strong style="color:#38BDF8;font-size:13px;font-weight:800;">🛵 ${escMapHtml(hoveredEntity.name)}</strong>
                        <span style="background:${statusBg};font-size:10px;font-weight:800;padding:2px 8px;border-radius:999px;">${hoveredEntity.active > 0 ? hoveredEntity.active + ' Aktif' : 'Müsait'}</span>
                    </div>
                    <div style="color:#E2E8F0;font-size:11.5px;margin-bottom:3px;"><i class="fa-solid fa-location-dot" style="color:#FF6B35;margin-right:4px;"></i> ${escMapHtml(streetText)}</div>
                    <div style="color:#94A3B8;font-size:11px;display:flex;justify-content:space-between;gap:8px;padding-top:5px;border-top:1px solid rgba(255,255,255,0.08);margin-top:5px;">
                        <span>${escMapHtml(hoveredEntity.vehicle)}</span>
                        <span style="color:#FF6B35;font-weight:800;">${distKm.toFixed(2)} km • Skor ${score.toFixed(2)}</span>
                    </div>
                `;
            } else if (entityType === 'restaurant') {
                const streetText = getStreetLabelForCoords(hoveredEntity.x, hoveredEntity.y, hoveredEntity.lat, hoveredEntity.lon, hoveredEntity.neighborhood);
                html = `
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:6px;">
                        <strong style="color:#FF6B35;font-size:13px;font-weight:800;">${escMapHtml(hoveredEntity.emoji || '🍽️')} ${escMapHtml(hoveredEntity.name)}</strong>
                        <span style="background:rgba(245,158,11,0.18);color:#F59E0B;font-size:10.5px;font-weight:800;padding:2px 8px;border-radius:999px;">★ ${escMapHtml(hoveredEntity.rating)}</span>
                    </div>
                    <div style="color:#E2E8F0;font-size:11.5px;margin-bottom:3px;"><i class="fa-solid fa-location-dot" style="color:#FF6B35;margin-right:4px;"></i> ${escMapHtml(streetText)}</div>
                    <div style="color:#94A3B8;font-size:11px;display:flex;justify-content:space-between;gap:8px;padding-top:5px;border-top:1px solid rgba(255,255,255,0.08);margin-top:5px;">
                        <span>${escMapHtml(hoveredEntity.cat || 'Restoran')}</span>
                        <span style="color:#10B981;font-weight:800;">~${escMapHtml(hoveredEntity.time || '20-30')} dk</span>
                    </div>
                `;
            } else {
                const streetText = getStreetLabelForCoords(custLoc.x, custLoc.y, custLoc.lat, custLoc.lon, custLoc.neighborhood || 'Moda, Kadıköy');
                html = `
                    <div style="display:flex;align-items:center;gap:6px;margin-bottom:5px;">
                        <strong style="color:#10B981;font-size:13px;font-weight:800;">🏠 ${escMapHtml(hoveredEntity.name || 'Teslimat Noktası')}</strong>
                    </div>
                    <div style="color:#E2E8F0;font-size:11.5px;"><i class="fa-solid fa-map-pin" style="color:#10B981;margin-right:4px;"></i> ${escMapHtml(streetText)}</div>
                    <div style="color:#94A3B8;font-size:10.5px;margin-top:4px;">Aktif Müşteri Teslimat Adresi</div>
                `;
            }

            tooltip.innerHTML = html;
            tooltip.style.display = 'block';

            const tipX = Math.min(rect.width - 235, Math.max(10, mouseX + 14));
            const tipY = Math.min(rect.height - 108, Math.max(10, mouseY - 26));
            tooltip.style.left = tipX + 'px';
            tooltip.style.top = tipY + 'px';
        } else {
            tooltip.style.display = 'none';
        }
    }

    /**
     * Canvas'ı Retina / HiDPI ekranlar için piksel kayıpsız ölçeklendirir
     */
    function setupCanvasDPI(canvas) {
        if (!canvas) return null;
        const dpr = (typeof window !== 'undefined' && window.devicePixelRatio) ? window.devicePixelRatio : 1;
        const rect = canvas.getBoundingClientRect ? canvas.getBoundingClientRect() : { width: canvas.width || 600, height: canvas.height || 250 };

        const displayWidth = rect.width || canvas.width || 600;
        const displayHeight = rect.height || canvas.height || 250;

        if (canvas.width !== Math.round(displayWidth * dpr) || canvas.height !== Math.round(displayHeight * dpr)) {
            canvas.width = Math.round(displayWidth * dpr);
            canvas.height = Math.round(displayHeight * dpr);
        }

        const ctx = canvas.getContext ? canvas.getContext('2d') : null;
        if (!ctx) return null;
        if (ctx.resetTransform) ctx.resetTransform();
        if (ctx.scale) ctx.scale(dpr, dpr);
        return { ctx, width: displayWidth, height: displayHeight };
    }

    function drawPillLabel(ctx, text, x, y, bgColor, textColor) {
        ctx.save();
        ctx.font = '700 10px "Plus Jakarta Sans", system-ui, sans-serif';
        const paddingX = 7;
        const textW = ctx.measureText(text).width;
        const boxW = textW + paddingX * 2;
        const boxH = 18;
        const r = 9;
        const bx = x;
        const by = y - boxH / 2;

        ctx.fillStyle = bgColor;
        ctx.beginPath();
        ctx.moveTo(bx + r, by);
        ctx.lineTo(bx + boxW - r, by);
        ctx.quadraticCurveTo(bx + boxW, by, bx + boxW, by + r);
        ctx.lineTo(bx + boxW, by + boxH - r);
        ctx.quadraticCurveTo(bx + boxW, by + boxH, bx + boxW - r, by + boxH);
        ctx.lineTo(bx + r, by + boxH);
        ctx.quadraticCurveTo(bx, by + boxH, bx, by + boxH - r);
        ctx.lineTo(bx, by + r);
        ctx.quadraticCurveTo(bx, by, bx + r, by);
        ctx.closePath();
        ctx.fill();

        ctx.fillStyle = textColor;
        ctx.fillText(text, bx + paddingX, y + 3.5);
        ctx.restore();
    }

    /**
     * Belirtilen ID'deki haritayı çizer (Canvas + Google Maps Leaflet senkron).
     */
    function drawMap(canvasId) {
        drawMapCanvas(canvasId);

        const leafletIdMap = {
            'admin-map-canvas': 'admin-map-leaflet',
            'courier-map-canvas': 'courier-map-leaflet',
            'customer-map-canvas': 'customer-map-leaflet'
        };
        const canvasEl = document.getElementById(canvasId);
        const leafletEl = leafletIdMap[canvasId] ? document.getElementById(leafletIdMap[canvasId]) : null;

        if (typeof L !== 'undefined') {
            ensureMapOverlayStyles();
            if (canvasEl && leafletEl) {
                canvasEl.style.display = 'none';
                leafletEl.style.display = 'block';
            }
            if (canvasId === 'admin-map-canvas') {
                initAdminMapLeaflet();
            } else if (canvasId === 'courier-map-canvas') {
                initCourierMapLeaflet();
            } else if (canvasId === 'customer-map-canvas') {
                initCustomerMapLeaflet();
            }
        } else if (canvasEl && leafletEl) {
            // Çevrimdışı ortam yedeği: Leaflet yoksa Canvas görünür kalır
            leafletEl.style.display = 'none';
            canvasEl.style.display = 'block';
        }
    }

    function drawMapCanvas(canvasId) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;

        const canvasSetup = setupCanvasDPI(canvas);
        if (!canvasSetup) return;

        const { ctx, width, height } = canvasSetup;
        const isDark = (typeof document !== 'undefined' && document.documentElement.getAttribute('data-theme') !== 'light');

        // Modern Harita Arka Planı
        ctx.fillStyle = isDark ? '#0B1120' : '#F8FAFC';
        ctx.fillRect(0, 0, width, height);

        const stepX = width / GRID_SIZE;
        const stepY = height / GRID_SIZE;

        // İnce Şehir Blokları / Cadde Çizgileri
        ctx.strokeStyle = isDark ? 'rgba(148, 163, 184, 0.06)' : 'rgba(15, 23, 42, 0.055)';
        ctx.lineWidth = 1;
        for (let i = 0; i <= GRID_SIZE; i++) {
            const x = i * stepX;
            const y = i * stepY;
            ctx.beginPath();
            ctx.moveTo(x, 0);
            ctx.lineTo(x, height);
            ctx.stroke();

            ctx.beginPath();
            ctx.moveTo(0, y);
            ctx.lineTo(width, y);
            ctx.stroke();
        }

        // Ana Kadıköy Arterleri (Moda / Bahariye / Bağdat aksı vurgusu)
        ctx.strokeStyle = isDark ? 'rgba(255, 107, 53, 0.08)' : 'rgba(255, 87, 34, 0.09)';
        ctx.lineWidth = 2;
        [10, 18, 26].forEach(idx => {
            ctx.beginPath();
            ctx.moveTo(0, idx * stepY);
            ctx.lineTo(width, idx * stepY);
            ctx.stroke();
        });
        [12, 22].forEach(idx => {
            ctx.beginPath();
            ctx.moveTo(idx * stepX, 0);
            ctx.lineTo(idx * stepX, height);
            ctx.stroke();
        });

        const state = GustoState.get();
        const restaurants = state.restaurants || [];
        const customerLocation = state.customerLocation || { x: 18, y: 14, name: 'Kadıköy Moda' };
        const activeOrder = state.activeOrder;
        const couriers = state.couriers || [];
        const isCustomerMap = (canvasId === 'customer-map-canvas');
        const pillBg = isDark ? 'rgba(15, 23, 42, 0.86)' : 'rgba(255, 255, 255, 0.92)';
        const pillText = isDark ? '#F1F5F9' : '#0F172A';

        // 1. Restoranları Çiz
        if (isCustomerMap || shouldShowLayer('restaurants')) {
            const targetRestaurants = (isCustomerMap && activeOrder)
                ? restaurants.filter(x => x.id === activeOrder.rId)
                : restaurants;

            targetRestaurants.forEach(r => {
                const px = r.x * stepX;
                const py = r.y * stepY;

                ctx.fillStyle = 'rgba(255, 87, 34, 0.22)';
                ctx.beginPath();
                ctx.arc(px, py, 12, 0, Math.PI * 2);
                ctx.fill();

                ctx.fillStyle = '#FF5722';
                ctx.beginPath();
                ctx.arc(px, py, 6.5, 0, Math.PI * 2);
                ctx.fill();

                ctx.strokeStyle = '#FFFFFF';
                ctx.lineWidth = 2;
                ctx.stroke();

                drawPillLabel(ctx, `${r.name.split(' ')[0]} 🍽️`, px + 10, py, pillBg, pillText);
            });
        }

        // 2. Müşteri Teslimat Noktasını Çiz
        const mx = customerLocation.x * stepX;
        const my = customerLocation.y * stepY;
        if (isCustomerMap || shouldShowLayer('customers')) {
            ctx.fillStyle = 'rgba(16, 185, 129, 0.22)';
            ctx.beginPath();
            ctx.arc(mx, my, 13, 0, Math.PI * 2);
            ctx.fill();

            ctx.fillStyle = '#10B981';
            ctx.beginPath();
            ctx.arc(mx, my, 7, 0, Math.PI * 2);
            ctx.fill();

            ctx.strokeStyle = '#FFFFFF';
            ctx.lineWidth = 2;
            ctx.stroke();

            const custLabel = `${(customerLocation.name || 'Evim').split(' ')[0]} 🏠`;
            drawPillLabel(ctx, custLabel, mx + 11, my, 'rgba(16, 185, 129, 0.18)', isDark ? '#34D399' : '#047857');
        }

        // 3. Aktif Sipariş ve Kurye / Gel-Al Rotası
        if (activeOrder) {
            const r = restaurants.find(x => x.id === activeOrder.rId);
            if (r) {
                const rx = r.x * stepX;
                const ry = r.y * stepY;

                if (activeOrder.deliveryMode === 'takeaway') {
                    ctx.save();
                    ctx.setLineDash([5, 5]);
                    ctx.strokeStyle = '#10B981';
                    ctx.lineWidth = 2.8;
                    ctx.beginPath();
                    ctx.moveTo(mx, my);
                    ctx.lineTo(rx, my);
                    ctx.lineTo(rx, ry);
                    ctx.stroke();
                    ctx.restore();

                    drawPillLabel(ctx, '🚶 Gel-Al Yürüyüş Rotası', (mx + rx) / 2 - 48, my - 14, 'rgba(16, 185, 129, 0.2)', isDark ? '#34D399' : '#047857');
                } else {
                    ctx.save();
                    ctx.setLineDash([6, 5]);
                    ctx.strokeStyle = '#FF5722';
                    ctx.lineWidth = 2.8;

                    if (activeOrder.addon) {
                        const addonR = restaurants.find(x => x.id === activeOrder.addon.rId);
                        if (addonR) {
                            const ax = addonR.x * stepX;
                            const ay = addonR.y * stepY;

                            ctx.beginPath();
                            ctx.moveTo(rx, ry);
                            ctx.lineTo(ax, ry);
                            ctx.lineTo(ax, ay);
                            ctx.stroke();

                            ctx.strokeStyle = '#8B5CF6';
                            ctx.beginPath();
                            ctx.moveTo(ax, ay);
                            ctx.lineTo(mx, ay);
                            ctx.lineTo(mx, my);
                            ctx.stroke();

                            ctx.fillStyle = '#8B5CF6';
                            ctx.beginPath();
                            ctx.arc(ax, ay, 8, 0, Math.PI * 2);
                            ctx.fill();
                            ctx.strokeStyle = '#FFFFFF';
                            ctx.lineWidth = 2;
                            ctx.stroke();

                            drawPillLabel(ctx, `Ara Durak: ${addonR.name.split(' ')[0]} 🍰`, ax + 12, ay, 'rgba(139, 92, 246, 0.2)', isDark ? '#C4B5FD' : '#6D28D9');
                        }
                    } else {
                        ctx.beginPath();
                        ctx.moveTo(rx, ry);
                        ctx.lineTo(mx, ry);
                        ctx.lineTo(mx, my);
                        ctx.stroke();
                    }
                    ctx.restore();

                    const cx = currentAnimPos.x * stepX;
                    const cy = currentAnimPos.y * stepY;

                    const pulseSize = 14 + Math.sin(Date.now() / 200) * 4;
                    ctx.strokeStyle = activeOrder.addon ? 'rgba(139, 92, 246, 0.45)' : 'rgba(56, 189, 248, 0.5)';
                    ctx.lineWidth = 2.2;
                    ctx.beginPath();
                    ctx.arc(cx, cy, pulseSize, 0, Math.PI * 2);
                    ctx.stroke();

                    ctx.fillStyle = activeOrder.addon ? '#8B5CF6' : '#3B82F6';
                    ctx.beginPath();
                    ctx.arc(cx, cy, 7.5, 0, Math.PI * 2);
                    ctx.fill();
                    ctx.strokeStyle = '#FFFFFF';
                    ctx.lineWidth = 2;
                    ctx.stroke();

                    const cTitle = (typeof activeOrder.courier === 'string')
                        ? activeOrder.courier
                        : (activeOrder.courier && activeOrder.courier.name ? activeOrder.courier.name : 'Kurye 🛵');
                    const cFirstName = cTitle.split(' ')[0] || 'Kurye';
                    drawPillLabel(ctx, `${cFirstName} 🛵 (Siparişinizin Kuryesi)`, cx + 12, cy, 'rgba(59, 130, 246, 0.2)', isDark ? '#93C5FD' : '#1D4ED8');
                }

                if (!isCustomerMap && shouldShowLayer('couriers')) {
                    couriers.filter(c => c.id !== activeOrder.courierId).forEach(c => {
                        const px = c.x * stepX;
                        const py = c.y * stepY;

                        ctx.fillStyle = '#3B82F6';
                        ctx.beginPath();
                        ctx.arc(px, py, 6, 0, Math.PI * 2);
                        ctx.fill();
                        ctx.strokeStyle = '#FFFFFF';
                        ctx.lineWidth = 1.8;
                        ctx.stroke();

                        drawPillLabel(ctx, c.name.split(' ')[0], px + 9, py, pillBg, isDark ? '#CBD5E1' : '#334155');
                    });
                }
            }
        } else {
            if (!isCustomerMap && shouldShowLayer('couriers')) {
                couriers.forEach(c => {
                    const px = c.x * stepX;
                    const py = c.y * stepY;

                    ctx.fillStyle = 'rgba(59, 130, 246, 0.2)';
                    ctx.beginPath();
                    ctx.arc(px, py, 11, 0, Math.PI * 2);
                    ctx.fill();

                    ctx.fillStyle = '#3B82F6';
                    ctx.beginPath();
                    ctx.arc(px, py, 6, 0, Math.PI * 2);
                    ctx.fill();
                    ctx.strokeStyle = '#FFFFFF';
                    ctx.lineWidth = 1.8;
                    ctx.stroke();

                    drawPillLabel(ctx, `${c.name.split(' ')[0]} 🛵`, px + 9, py, pillBg, isDark ? '#CBD5E1' : '#334155');
                });
            }
        }
    }

    // ==========================================
    // LEAFLET GOOGLE HARİTALAR ENTEGRASYONU
    // ==========================================
    function initAddressPickerLeaflet() {
        if (typeof L === 'undefined') return;
        const container = document.getElementById('address-picker-leaflet');
        if (!container) return;
        ensureMapOverlayStyles();

        if (!leafletMaps.picker) {
            const map = L.map('address-picker-leaflet', {
                center: [40.9850, 29.0280],
                zoom: 15,
                zoomControl: true,
                attributionControl: false
            });
            L.tileLayer(GOOGLE_MAPS_TILES, {
                maxZoom: 20,
                subdomains: ['0', '1', '2', '3']
            }).addTo(map);

            leafletMaps.picker = map;

            map.on('click', function (e) {
                const { lat, lng } = e.latlng;
                updatePickerLocation(lat, lng);
            });
        }

        const state = GustoState.get();
        let curX = parseInt(document.getElementById('picker-x')?.value, 10);
        let curY = parseInt(document.getElementById('picker-y')?.value, 10);
        if (isNaN(curX)) curX = state.customerLocation?.x ?? 18;
        if (isNaN(curY)) curY = state.customerLocation?.y ?? 14;

        const coords = gridToGps(curX, curY);

        if (!leafletLayers.pickerMarker) {
            const pinIcon = createPinIcon('picker', 40, true);
            const marker = L.marker(coords, { icon: pinIcon, draggable: true }).addTo(leafletMaps.picker);
            marker.on('dragend', function () {
                const pos = marker.getLatLng();
                updatePickerLocation(pos.lat, pos.lng);
            });
            leafletLayers.pickerMarker = marker;
        } else {
            leafletLayers.pickerMarker.setLatLng(coords);
        }

        setTimeout(() => {
            if (leafletMaps.picker) {
                leafletMaps.picker.invalidateSize();
                leafletMaps.picker.setView(coords, 15);
            }
        }, 100);
    }

    function updatePickerLocation(lat, lon) {
        const grid = gpsToGrid(lat, lon);
        const inX = document.getElementById('picker-x');
        const inY = document.getElementById('picker-y');
        const coordText = document.getElementById('picker-coord-text');
        const descInput = document.getElementById('new-addr-desc');

        if (inX) inX.value = grid.x;
        if (inY) inY.value = grid.y;

        const streetObj = getKadıkoyStreetAddress(lat, lon);
        const fullLabel = streetObj ? `${streetObj.street} • ${streetObj.neighborhood}` : getNeighborhoodName(lat, lon);
        if (coordText) coordText.innerText = fullLabel;
        if (descInput && (!descInput.value || descInput.value === 'İstanbul' || descInput.value.includes('Kadıköy') || descInput.value.includes('Cad.') || descInput.value.includes('Sokak'))) {
            descInput.value = streetObj ? `${streetObj.street}, ${streetObj.neighborhood}` : fullLabel;
        }

        if (leafletLayers.pickerMarker) {
            leafletLayers.pickerMarker.setLatLng([lat, lon]);
        }
        drawAddressPickerCanvas();
    }

    function initAdminMapLeaflet() {
        if (typeof L === 'undefined') return;
        const container = document.getElementById('admin-map-leaflet');
        if (!container) return;
        ensureMapOverlayStyles();

        if (!leafletMaps.admin) {
            const map = L.map('admin-map-leaflet', {
                center: [40.9850, 29.0295],
                zoom: 14,
                zoomControl: true,
                attributionControl: false
            });
            L.tileLayer(GOOGLE_MAPS_TILES, {
                maxZoom: 20,
                subdomains: ['0', '1', '2', '3']
            }).addTo(map);

            leafletMaps.admin = map;
        }

        const state = GustoState.get();
        const restaurants = state.restaurants || [];
        const couriers = state.couriers || [];
        const custLoc = state.customerLocation || { x: 18, y: 14, name: 'Kadıköy Moda' };
        const refRest = state.activeOrder
            ? (restaurants.find(r => r.id === state.activeOrder.rId) || restaurants[0])
            : restaurants[0];

        // Temizle
        leafletLayers.adminMarkers.forEach(m => leafletMaps.admin.removeLayer(m));
        leafletLayers.adminMarkers = [];

        // 1. Restoranlar
        if (shouldShowLayer('restaurants')) {
            restaurants.forEach(r => {
                const pos = (r.lat && r.lon) ? [r.lat, r.lon] : gridToGps(r.x, r.y);
                const street = getStreetLabelForCoords(r.x, r.y, r.lat, r.lon, r.neighborhood);
                const marker = L.marker(pos, { icon: createPinIcon('restaurant', 34, false) }).addTo(leafletMaps.admin);
                marker.bindPopup(`
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:6px;">
                        <strong style="font-size:13.5px;font-weight:800;">${escMapHtml(r.emoji || '🍽️')} ${escMapHtml(r.name)}</strong>
                        <span style="background:rgba(245,158,11,0.16);color:#F59E0B;font-size:11px;font-weight:800;padding:2px 7px;border-radius:999px;">★ ${escMapHtml(r.rating)}</span>
                    </div>
                    <div style="font-size:11.5px;color:var(--text-2, #94A3B8);margin-bottom:6px;">
                        <i class="fa-solid fa-location-dot" style="color:#FF5722;margin-right:4px;"></i>${escMapHtml(street)}
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;font-size:11px;padding-top:6px;border-top:1px solid var(--border, rgba(255,255,255,0.1));">
                        <span style="font-weight:700;color:var(--text-2, #94A3B8);">${escMapHtml(r.cat)}</span>
                        <span style="font-weight:800;color:#FF6B35;">~${escMapHtml(r.time)} dk • ${escMapHtml(r.promo || 'Açık')}</span>
                    </div>
                `, { className: 'cravelo-leaflet-popup' });
                leafletLayers.adminMarkers.push(marker);
            });
        }

        // 2. Kuryeler
        if (shouldShowLayer('couriers')) {
            couriers.forEach(c => {
                const pos = (c.lat && c.lon) ? [c.lat, c.lon] : gridToGps(c.x, c.y);
                const street = getStreetLabelForCoords(c.x, c.y, c.lat, c.lon, c.neighborhood);
                const distKm = refRest ? calcDistanceKm(c, refRest) : 0.8;
                const score = (typeof GustoState !== 'undefined') ? GustoState.calculateCourierScore(distKm, c.active) : distKm + 2 * c.active;
                const badgeStyle = c.active > 0
                    ? 'background:rgba(245,158,11,0.16);color:#F59E0B;'
                    : 'background:rgba(16,185,129,0.16);color:#10B981;';
                const marker = L.marker(pos, { icon: createPinIcon('courier', 36, c.active > 0) }).addTo(leafletMaps.admin);
                marker.bindPopup(`
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:6px;">
                        <strong style="font-size:13.5px;font-weight:800;">🛵 ${escMapHtml(c.name)}</strong>
                        <span style="${badgeStyle}font-size:10.5px;font-weight:800;padding:2px 8px;border-radius:999px;">${c.active > 0 ? c.active + ' Aktif' : 'Müsait'}</span>
                    </div>
                    <div style="font-size:11.5px;color:var(--text-2, #94A3B8);margin-bottom:6px;">
                        <i class="fa-solid fa-location-dot" style="color:#38BDF8;margin-right:4px;"></i>${escMapHtml(street)}
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;font-size:11px;padding-top:6px;border-top:1px solid var(--border, rgba(255,255,255,0.1));">
                        <span>${escMapHtml(c.vehicle)}</span>
                        <span style="font-weight:800;color:#FF6B35;">Skor: ${score.toFixed(2)} (${distKm.toFixed(2)} km)</span>
                    </div>
                `, { className: 'cravelo-leaflet-popup' });
                leafletLayers.adminMarkers.push(marker);
            });
        }

        // 3. Müşteri Teslimat Noktası
        if (shouldShowLayer('customers')) {
            const custPos = (custLoc.lat && custLoc.lon) ? [custLoc.lat, custLoc.lon] : gridToGps(custLoc.x, custLoc.y);
            const custStreet = getStreetLabelForCoords(custLoc.x, custLoc.y, custLoc.lat, custLoc.lon, 'Moda, Kadıköy');
            const custMarker = L.marker(custPos, { icon: createPinIcon('customer', 34, false) }).addTo(leafletMaps.admin);
            custMarker.bindPopup(`
                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:6px;">
                    <strong style="font-size:13.5px;font-weight:800;">🏠 ${escMapHtml(custLoc.name || 'Teslimat Noktası')}</strong>
                    <span style="background:rgba(16,185,129,0.16);color:#10B981;font-size:10.5px;font-weight:800;padding:2px 8px;border-radius:999px;">Müşteri</span>
                </div>
                <div style="font-size:11.5px;color:var(--text-2, #94A3B8);">
                    <i class="fa-solid fa-map-pin" style="color:#10B981;margin-right:4px;"></i>${escMapHtml(custStreet)}
                </div>
            `, { className: 'cravelo-leaflet-popup' });
            leafletLayers.adminMarkers.push(custMarker);
        }

        setTimeout(() => {
            if (leafletMaps.admin) leafletMaps.admin.invalidateSize();
        }, 80);
    }

    function initCourierMapLeaflet() {
        if (typeof L === 'undefined') return;
        const container = document.getElementById('courier-map-leaflet');
        if (!container) return;
        ensureMapOverlayStyles();

        if (!leafletMaps.courier) {
            const map = L.map('courier-map-leaflet', {
                center: [40.9850, 29.0280],
                zoom: 15,
                zoomControl: true,
                attributionControl: false
            });
            L.tileLayer(GOOGLE_MAPS_TILES, {
                maxZoom: 20,
                subdomains: ['0', '1', '2', '3']
            }).addTo(map);

            leafletMaps.courier = map;
        }

        const state = GustoState.get();
        const activeOrder = state.activeOrder;
        const custLoc = state.customerLocation || { x: 18, y: 14, name: 'Evim (Moda)' };
        const custPos = (custLoc.lat && custLoc.lon) ? [custLoc.lat, custLoc.lon] : gridToGps(custLoc.x, custLoc.y);

        let restObj = (state.restaurants || [])[0] || { name: 'Lezzet Kebap Salonu', lat: 40.9850, lon: 29.0280, x: 12, y: 8, neighborhood: 'Moda, Kadıköy' };
        if (activeOrder) {
            const found = (state.restaurants || []).find(x => x.id === activeOrder.rId);
            if (found) restObj = found;
        }
        const restPos = (restObj.lat && restObj.lon) ? [restObj.lat, restObj.lon] : gridToGps(restObj.x, restObj.y);

        // Restoran Marker
        const restPopupHtml = `
            <strong style="font-size:13px;">🍽️ ${escMapHtml(restObj.name)}</strong>
            <div style="font-size:11.5px;color:var(--text-2, #94A3B8);margin-top:4px;">${escMapHtml(getStreetLabelForCoords(restObj.x, restObj.y, restObj.lat, restObj.lon, restObj.neighborhood))}</div>
            <div style="font-size:11px;color:#FF6B35;font-weight:700;margin-top:4px;">Sipariş Alım Noktası</div>
        `;
        if (!leafletLayers.courierRestMarker) {
            leafletLayers.courierRestMarker = L.marker(restPos, { icon: createPinIcon('restaurant', 34, false) })
                .addTo(leafletMaps.courier)
                .bindPopup(restPopupHtml, { className: 'cravelo-leaflet-popup' });
        } else {
            leafletLayers.courierRestMarker.setLatLng(restPos).setPopupContent(restPopupHtml);
        }

        // Müşteri Marker
        const custPopupHtml = `
            <strong style="font-size:13px;">🏠 ${escMapHtml(custLoc.name || 'Teslimat Adresi')}</strong>
            <div style="font-size:11.5px;color:var(--text-2, #94A3B8);margin-top:4px;">${escMapHtml(getStreetLabelForCoords(custLoc.x, custLoc.y, custLoc.lat, custLoc.lon, 'Moda, Kadıköy'))}</div>
            <div style="font-size:11px;color:#10B981;font-weight:700;margin-top:4px;">Müşteri Varış Noktası</div>
        `;
        if (!leafletLayers.courierCustMarker) {
            leafletLayers.courierCustMarker = L.marker(custPos, { icon: createPinIcon('customer', 34, false) })
                .addTo(leafletMaps.courier)
                .bindPopup(custPopupHtml, { className: 'cravelo-leaflet-popup' });
        } else {
            leafletLayers.courierCustMarker.setLatLng(custPos).setPopupContent(custPopupHtml);
        }

        // Polyline Rota Çizgisi (Parlak Alt Gölge + Kesikli Ana Rota)
        const midLat = (restPos[0] + custPos[0]) / 2 + 0.0007;
        const midLon = (restPos[1] + custPos[1]) / 2 - 0.0005;
        const routePoints = [restPos, [midLat, midLon], custPos];

        if (!leafletLayers.courierPolylineGlow) {
            leafletLayers.courierPolylineGlow = L.polyline(routePoints, {
                color: '#FF5722',
                weight: 9,
                opacity: 0.22,
                lineCap: 'round'
            }).addTo(leafletMaps.courier);
        } else {
            leafletLayers.courierPolylineGlow.setLatLngs(routePoints);
        }

        if (!leafletLayers.courierPolyline) {
            leafletLayers.courierPolyline = L.polyline(routePoints, {
                color: '#FF5722',
                weight: 4,
                dashArray: '8, 8',
                opacity: 0.92,
                lineCap: 'round'
            }).addTo(leafletMaps.courier);
        } else {
            leafletLayers.courierPolyline.setLatLngs(routePoints);
        }

        // Kurye Marker
        const curCourier = (state.couriers || []).find(c => c.id === state.selectedCourierId) || (state.couriers || [])[0];
        const curGps = activeOrder
            ? gridToGps(currentAnimPos.x, currentAnimPos.y)
            : ((curCourier && curCourier.lat && curCourier.lon) ? [curCourier.lat, curCourier.lon] : gridToGps(curCourier?.x ?? 14, curCourier?.y ?? 10));
        const courierPopupHtml = `
            <strong style="font-size:13px;">🛵 ${escMapHtml(curCourier ? curCourier.name : 'Aktif Kurye')}</strong>
            <div style="font-size:11.5px;color:var(--text-2, #94A3B8);margin-top:4px;">${escMapHtml(curCourier ? curCourier.vehicle : 'Motor')} • Canlı GPS</div>
        `;
        if (!leafletLayers.courierMarker) {
            leafletLayers.courierMarker = L.marker(curGps, { icon: createPinIcon('courier', 38, true) })
                .addTo(leafletMaps.courier)
                .bindPopup(courierPopupHtml, { className: 'cravelo-leaflet-popup' });
        } else {
            leafletLayers.courierMarker.setLatLng(curGps).setPopupContent(courierPopupHtml);
        }

        setTimeout(() => {
            if (leafletMaps.courier) {
                leafletMaps.courier.invalidateSize();
                leafletMaps.courier.fitBounds([restPos, custPos, curGps], { padding: [38, 38], maxZoom: 15 });
            }
        }, 80);
    }

    function initCustomerMapLeaflet() {
        if (typeof L === 'undefined') return;
        const container = document.getElementById('customer-map-leaflet');
        if (!container) return;
        ensureMapOverlayStyles();

        // Modal içeriği innerHTML ile yeniden oluşturulduysa eski Leaflet örneğini temizle
        if (leafletMaps.customer) {
            const prevContainer = leafletMaps.customer.getContainer ? leafletMaps.customer.getContainer() : null;
            const isDetached = !prevContainer || prevContainer !== container || (typeof document !== 'undefined' && document.body && !document.body.contains(prevContainer));
            if (isDetached) {
                try { leafletMaps.customer.remove(); } catch (e) { }
                leafletMaps.customer = null;
                leafletLayers.customerMarkers = [];
                leafletLayers.customerPolyline = null;
                leafletLayers.customerPolylineGlow = null;
                leafletLayers.customerCourierMarker = null;
            }
        }

        if (!leafletMaps.customer) {
            const map = L.map(container, {
                center: [40.9850, 29.0280],
                zoom: 15,
                zoomControl: true,
                attributionControl: false
            });
            L.tileLayer(GOOGLE_MAPS_TILES, {
                maxZoom: 20,
                subdomains: ['0', '1', '2', '3']
            }).addTo(map);
            leafletMaps.customer = map;
        }

        const state = GustoState.get();
        const activeOrder = state.activeOrder;
        if (!activeOrder) return;

        leafletLayers.customerMarkers.forEach(m => {
            try { leafletMaps.customer.removeLayer(m); } catch (e) { }
        });
        leafletLayers.customerMarkers = [];
        leafletLayers.customerCourierMarker = null;

        if (leafletLayers.customerPolyline) {
            try { leafletMaps.customer.removeLayer(leafletLayers.customerPolyline); } catch (e) { }
            leafletLayers.customerPolyline = null;
        }
        if (leafletLayers.customerPolylineGlow) {
            try { leafletMaps.customer.removeLayer(leafletLayers.customerPolylineGlow); } catch (e) { }
            leafletLayers.customerPolylineGlow = null;
        }

        const restaurants = state.restaurants || [];
        const couriers = state.couriers || [];
        const r = restaurants.find(x => x.id === activeOrder.rId) || restaurants[0] || { name: activeOrder.restaurant || 'Restoran', x: 12, y: 8, lat: 40.9850, lon: 29.0280, neighborhood: 'Moda, Kadıköy' };
        const custLoc = state.customerLocation || { x: 18, y: 14, name: 'Evim (Moda)' };
        const assignedCourier = couriers.find(c => c.id === activeOrder.courierId) || couriers[0];

        const restPos = (r && r.lat && r.lon) ? [r.lat, r.lon] : gridToGps(r?.x ?? 12, r?.y ?? 8);
        const custPos = (custLoc.lat && custLoc.lon) ? [custLoc.lat, custLoc.lon] : gridToGps(custLoc.x, custLoc.y);

        // Animasyon aktifse anlık pozisyonu, değilse atanan kuryenin GPS koordinatını kullan
        const curGps = courierAnimInterval
            ? gridToGps(currentAnimPos.x, currentAnimPos.y)
            : ((assignedCourier && assignedCourier.lat && assignedCourier.lon)
                ? [assignedCourier.lat, assignedCourier.lon]
                : gridToGps(assignedCourier?.x ?? currentAnimPos.x, assignedCourier?.y ?? currentAnimPos.y));

        const isTakeaway = activeOrder.deliveryMode === 'takeaway';
        const routeColor = isTakeaway ? '#10B981' : '#FF5722';

        // Restoran Marker + Popup
        const rPopup = `
            <strong style="font-size:13px;">🍽️ ${escMapHtml(r.name || activeOrder.restaurant)}</strong>
            <div style="font-size:11.5px;color:var(--text-2, #94A3B8);margin-top:4px;">${escMapHtml(getStreetLabelForCoords(r.x, r.y, r.lat, r.lon, r.neighborhood))}</div>
            <div style="font-size:11px;color:#FF6B35;font-weight:700;margin-top:4px;">Sipariş Restoranı</div>
        `;
        const rM = L.marker(restPos, { icon: createPinIcon('restaurant', 34, false) })
            .addTo(leafletMaps.customer)
            .bindPopup(rPopup, { className: 'cravelo-leaflet-popup' });

        // Müşteri Teslimat Noktası Marker + Popup
        const cPopup = `
            <strong style="font-size:13px;">🏠 ${escMapHtml(custLoc.name || 'Teslimat Adresiniz')}</strong>
            <div style="font-size:11.5px;color:var(--text-2, #94A3B8);margin-top:4px;">${escMapHtml(getStreetLabelForCoords(custLoc.x, custLoc.y, custLoc.lat, custLoc.lon, 'Moda, Kadıköy'))}</div>
            <div style="font-size:11px;color:#10B981;font-weight:700;margin-top:4px;">Teslimat Adresiniz</div>
        `;
        const cM = L.marker(custPos, { icon: createPinIcon('customer', 34, false) })
            .addTo(leafletMaps.customer)
            .bindPopup(cPopup, { className: 'cravelo-leaflet-popup' });

        leafletLayers.customerMarkers.push(rM, cM);

        // Rota noktaları (ara durak / ek lezzet varsa 3 noktalı rota)
        const boundsPoints = [restPos, custPos];
        let routePoints;
        if (!isTakeaway && activeOrder.addon) {
            const addonR = restaurants.find(x => x.id === activeOrder.addon.rId);
            if (addonR) {
                const addonPos = (addonR.lat && addonR.lon) ? [addonR.lat, addonR.lon] : gridToGps(addonR.x, addonR.y);
                const aM = L.marker(addonPos, { icon: createPinIcon('restaurant', 30, false) })
                    .addTo(leafletMaps.customer)
                    .bindPopup(`<strong style="font-size:13px;">🍰 Ara Durak: ${escMapHtml(addonR.name)}</strong>`, { className: 'cravelo-leaflet-popup' });
                leafletLayers.customerMarkers.push(aM);
                boundsPoints.push(addonPos);
                routePoints = [restPos, addonPos, custPos];
            }
        }
        if (!routePoints) {
            const midLat = (restPos[0] + custPos[0]) / 2 + 0.0006;
            const midLon = (restPos[1] + custPos[1]) / 2 - 0.0004;
            routePoints = [restPos, [midLat, midLon], custPos];
        }

        // Parlak Alt Gölge + Kesikli Ana Google Haritalar Rotası
        leafletLayers.customerPolylineGlow = L.polyline(routePoints, {
            color: routeColor,
            weight: 9,
            opacity: 0.22,
            lineCap: 'round'
        }).addTo(leafletMaps.customer);

        leafletLayers.customerPolyline = L.polyline(routePoints, {
            color: routeColor,
            weight: 4,
            dashArray: '8, 8',
            opacity: 0.92,
            lineCap: 'round'
        }).addTo(leafletMaps.customer);

        // Canlı Kurye Marker (Gel-Al değilse)
        if (!isTakeaway) {
            const cName = (typeof activeOrder.courier === 'string')
                ? activeOrder.courier
                : (assignedCourier?.name || 'Siparişinizin Kuryesi');
            const kPopup = `
                <strong style="font-size:13px;">🛵 ${escMapHtml(cName)}</strong>
                <div style="font-size:11.5px;color:var(--text-2, #94A3B8);margin-top:4px;">${escMapHtml(assignedCourier?.vehicle || 'Motor Kurye')} • Canlı GPS</div>
                <div style="font-size:11px;color:#38BDF8;font-weight:700;margin-top:4px;">Siparişinizin Kuryesi</div>
            `;
            const kM = L.marker(curGps, { icon: createPinIcon('courier', 38, true) })
                .addTo(leafletMaps.customer)
                .bindPopup(kPopup, { className: 'cravelo-leaflet-popup' });
            leafletLayers.customerCourierMarker = kM;
            leafletLayers.customerMarkers.push(kM);
            boundsPoints.push(curGps);
        }

        const fitCustomerMap = () => {
            if (leafletMaps.customer) {
                leafletMaps.customer.invalidateSize();
                leafletMaps.customer.fitBounds(boundsPoints, { padding: [36, 36], maxZoom: 16 });
            }
        };
        setTimeout(fitCustomerMap, 90);
        setTimeout(fitCustomerMap, 280);
    }

    function startCourierAnimation(courier, restaurant, customer) {
        if (!courier || !restaurant || !customer) return;

        stopCourierAnimation();

        currentAnimPos = { x: courier.x, y: courier.y };
        courierAnimProgress = 0;
        courierAnimPhase = 'to_restaurant';

        courierAnimInterval = setInterval(() => {
            courierAnimProgress += 0.05;

            if (courierAnimPhase === 'to_restaurant') {
                if (courierAnimProgress >= 1) {
                    courierAnimProgress = 0;
                    courierAnimPhase = 'to_customer';
                    currentAnimPos = { x: restaurant.x, y: restaurant.y };
                }
            } else {
                if (courierAnimProgress >= 1) {
                    courierAnimProgress = 1;
                    stopCourierAnimation();
                }
            }

            const p = Math.min(1, Math.max(0, courierAnimProgress));
            const targetStart = (courierAnimPhase === 'to_restaurant')
                ? { x: courier.x, y: courier.y }
                : { x: restaurant.x, y: restaurant.y };
            const targetEnd = (courierAnimPhase === 'to_restaurant')
                ? { x: restaurant.x, y: restaurant.y }
                : { x: customer.x, y: customer.y };

            if (p < 0.5) {
                currentAnimPos.x = targetStart.x + (targetEnd.x - targetStart.x) * (p * 2);
                currentAnimPos.y = targetStart.y;
            } else {
                currentAnimPos.x = targetEnd.x;
                currentAnimPos.y = targetStart.y + (targetEnd.y - targetStart.y) * ((p - 0.5) * 2);
            }

            drawMapCanvas('customer-map-canvas');
            drawMapCanvas('admin-map-canvas');
            drawMapCanvas('courier-map-canvas');

            // Leaflet Kurye Markerlarını da (Kurye & Müşteri Google Haritaları) senkron güncelle
            const curGps = gridToGps(currentAnimPos.x, currentAnimPos.y);
            if (leafletLayers.courierMarker) {
                leafletLayers.courierMarker.setLatLng(curGps);
            }
            if (leafletLayers.customerCourierMarker) {
                leafletLayers.customerCourierMarker.setLatLng(curGps);
            }
        }, 120);
    }

    function stopCourierAnimation() {
        if (courierAnimInterval) {
            clearInterval(courierAnimInterval);
            courierAnimInterval = null;
        }
    }

    /**
     * Adres Seçici Modalı için Çizim
     */
    function drawAddressPicker() {
        drawAddressPickerCanvas();
        if (typeof L !== 'undefined') {
            initAddressPickerLeaflet();
        }
    }

    function drawAddressPickerCanvas() {
        const canvas = document.getElementById('address-picker-canvas');
        if (!canvas) return;

        const canvasSetup = setupCanvasDPI(canvas);
        if (!canvasSetup) return;

        const { ctx, width, height } = canvasSetup;
        const isDark = (typeof document !== 'undefined' && document.documentElement.getAttribute('data-theme') !== 'light');

        ctx.fillStyle = isDark ? '#0B1120' : '#FFFFFF';
        ctx.fillRect(0, 0, width, height);

        const stepX = width / GRID_SIZE;
        const stepY = height / GRID_SIZE;

        ctx.strokeStyle = isDark ? 'rgba(148, 163, 184, 0.07)' : 'rgba(15, 23, 42, 0.06)';
        ctx.lineWidth = 1;
        for (let x = 0; x <= width; x += stepX) {
            ctx.beginPath(); ctx.moveTo(x, 0); ctx.lineTo(x, height); ctx.stroke();
        }
        for (let y = 0; y <= height; y += stepY) {
            ctx.beginPath(); ctx.moveTo(0, y); ctx.lineTo(width, y); ctx.stroke();
        }

        const restaurants = GustoState.get().restaurants || [];
        restaurants.forEach(r => {
            ctx.fillStyle = 'rgba(255, 87, 34, 0.45)';
            ctx.beginPath();
            ctx.arc(r.x * stepX, r.y * stepY, 4.5, 0, Math.PI * 2);
            ctx.fill();
        });

        let inputX = parseInt(document.getElementById('picker-x')?.value, 10);
        let inputY = parseInt(document.getElementById('picker-y')?.value, 10);
        if (isNaN(inputX)) inputX = GustoState.get().customerLocation?.x ?? 18;
        if (isNaN(inputY)) inputY = GustoState.get().customerLocation?.y ?? 14;

        const px = inputX * stepX;
        const py = inputY * stepY;

        ctx.fillStyle = 'rgba(16, 185, 129, 0.25)';
        ctx.beginPath();
        ctx.arc(px, py, 14, 0, Math.PI * 2);
        ctx.fill();

        ctx.fillStyle = '#10B981';
        ctx.beginPath();
        ctx.arc(px, py, 7.5, 0, Math.PI * 2);
        ctx.fill();

        ctx.strokeStyle = '#FFFFFF';
        ctx.lineWidth = 2;
        ctx.stroke();

        const textOffsetX = (px > width - 150) ? -145 : 12;
        drawPillLabel(ctx, 'Teslimat Noktası 📍', px + textOffsetX, py, isDark ? 'rgba(15,23,42,0.9)' : 'rgba(255,255,255,0.95)', isDark ? '#F8FAFC' : '#0F172A');
    }

    function handlePickerMapClick(e) {
        const canvas = document.getElementById('address-picker-canvas');
        if (!canvas) return;

        const rect = canvas.getBoundingClientRect();
        const clickX = e.clientX - rect.left;
        const clickY = e.clientY - rect.top;

        const gridX = Math.min(GRID_SIZE, Math.max(0, Math.round(clickX / (rect.width / GRID_SIZE))));
        const gridY = Math.min(GRID_SIZE, Math.max(0, Math.round(clickY / (rect.height / GRID_SIZE))));

        const inputX = document.getElementById('picker-x');
        const inputY = document.getElementById('picker-y');
        const coordText = document.getElementById('picker-coord-text');

        if (inputX) inputX.value = gridX;
        if (inputY) inputY.value = gridY;

        const gps = gridToGps(gridX, gridY);
        const streetObj = getKadıkoyStreetAddress(gps[0], gps[1]);
        const name = streetObj ? `${streetObj.street} • ${streetObj.neighborhood}` : getNeighborhoodName(gps[0], gps[1]);
        if (coordText) coordText.innerText = name;

        if (leafletLayers.pickerMarker) {
            leafletLayers.pickerMarker.setLatLng(gps);
        }

        drawAddressPicker();
    }

    return {
        drawMap,
        drawAddressPicker,
        startCourierAnimation,
        stopCourierAnimation,
        handlePickerMapClick,
        setFilter,
        handleCanvasMouseMove,
        hideTooltip,
        gridToGps,
        gpsToGrid,
        getNeighborhoodName,
        getKadıkoyStreetAddress,
        getKadikoyStreetAddress: getKadıkoyStreetAddress,
        getStreetLabelForCoords
    };
})();

window.GustoMap = GustoMap;
window.TgoMap = GustoMap;
window.CraveloMap = GustoMap;
