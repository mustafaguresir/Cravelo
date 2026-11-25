    <!-- ======================================================== -->
    <!-- 3. KURYE KOKPİTİ (CRAVELO COURIER COCKPIT)               -->
    <!-- ======================================================== -->
    <div id="view-courier" class="panel-wrap hidden">
        <div class="panel-top-bar">
            <div>
                <div class="panel-top-title">
                    <i class="fa-solid fa-motorcycle" style="color:var(--primary);"></i>
                    <span>Kurye Teslimat & Canlı Kokpit</span>
                </div>
                <div style="font-size:13.5px;color:var(--text-2);margin-top:4px;">Canlı Kadıköy Google Harita Rotası, Aktif Teslimat Görevleri ve Kazanç Telemetrisi</div>
            </div>
            <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                <div class="live-status-pill">
                    <span class="live-pulse-dot"></span> Canlı GPS Aktif
                </div>
                <div id="courier-shift-controls" style="display:inline-flex;gap:6px;background:var(--surface-2);border:1px solid var(--border);padding:4px;border-radius:9999px;" role="group" aria-label="Kurye Vardiya Durumu">
                    <button type="button" class="btn btn-sm btn-success" id="courier-shift-musait" onclick="setCourierShiftStatus('musait')" style="border-radius:9999px;padding:5px 12px;font-size:12px;">
                        <i class="fa-solid fa-circle-check"></i> Müsait
                    </button>
                    <button type="button" class="btn btn-sm btn-outline" id="courier-shift-mesgul" onclick="setCourierShiftStatus('mesgul')" style="border-radius:9999px;padding:5px 12px;font-size:12px;">
                        <i class="fa-solid fa-mug-hot"></i> Mola / Meşgul
                    </button>
                </div>
                <span class="status-pill warning" title="Kurye Teslimat Puanı">
                    <i class="fa-solid fa-trophy"></i> <strong>4.95 ⭐</strong>
                </span>
                <div class="panel-selector-pill">
                    <label for="courier-selector" style="font-size:12.5px;font-weight:800;color:var(--text-2);white-space:nowrap;">Aktif Kurye:</label>
                    <select class="form-input" id="courier-selector" style="width:225px;padding:7px 12px;border-radius:10px;" onchange="changeCourierUser()"></select>
                </div>
            </div>
        </div>

        <!-- Courier Telemetry KPI Strip -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-icon" style="background:var(--blue-light);color:var(--blue);">
                    <i class="fa-solid fa-location-crosshairs"></i>
                </div>
                <div>
                    <div class="kpi-label">Anlık Kurye Bölgesi</div>
                    <div class="kpi-val" id="courier-kpi-loc" style="font-size:19px;">Kadıköy Moda</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon" style="background:var(--success-light);color:var(--success);">
                    <i class="fa-solid fa-wallet"></i>
                </div>
                <div>
                    <div class="kpi-label">Günlük Kazanç</div>
                    <div class="kpi-val" id="courier-kpi-earnings">185.00 ₺</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon" style="background:var(--primary-light);color:var(--primary);">
                    <i class="fa-solid fa-box-open"></i>
                </div>
                <div>
                    <div class="kpi-label">Aktif Görev</div>
                    <div class="kpi-val" id="courier-kpi-active">0 Görev</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon" style="background:var(--yellow-light);color:var(--yellow);">
                    <i class="fa-solid fa-hand-holding-dollar"></i>
                </div>
                <div>
                    <div class="kpi-label">Toplam Bahşiş</div>
                    <div class="kpi-val" id="courier-kpi-tips">45.00 ₺</div>
                </div>
            </div>
        </div>

        <!-- Active Delivery Mission Cards -->
        <section class="panel-card">
            <div class="panel-card-head">
                <div>
                    <div class="panel-card-title">
                        <i class="fa-solid fa-route" style="color:var(--primary);"></i>
                        <span>Atanmış Teslimat Görevi</span>
                    </div>
                    <div style="font-size:12.5px;color:var(--text-2);margin-top:3px;">Sipariş mutfakta hazırlandığında akıllı mesafe ve iş yükü skoruyla göreviniz otomatik açılır</div>
                </div>
                <span class="badge badge-musait" id="courier-badge-status">MÜSAİT</span>
            </div>
            <div id="courier-task-area"></div>
        </section>

        <!-- Google Maps Live Route Frame with Floating HUD Legend -->
        <section class="panel-card">
            <div class="panel-card-head">
                <div>
                    <div class="panel-card-title">
                        <i class="fa-solid fa-map-location-dot" style="color:var(--primary);"></i>
                        <span>Canlı Google Harita & Teslimat Rotası</span>
                    </div>
                    <div style="font-size:12.5px;color:var(--text-2);margin-top:3px;">Kadıköy sokak ağı üzerinde restoran teslim alma ve müşteri varış rotası</div>
                </div>
                <span class="status-pill info"><i class="fa-solid fa-satellite-dish"></i> Kadıköy GPS Ağı</span>
            </div>
            <div id="courier-map-container" class="map-frame map-hud-container">
                <div id="courier-map-leaflet" style="width:100%;height:320px;z-index:2;"></div>
                <canvas id="courier-map-canvas" class="map-canvas" width="900" height="280" style="position:absolute;top:0;left:0;width:100%;height:280px;cursor:crosshair;display:none;" onmousemove="GustoMap.handleCanvasMouseMove(event, 'courier-map-canvas')" onmouseleave="GustoMap.hideTooltip()"></canvas>
                <div class="map-hud-legend">
                    <span><i class="fa-solid fa-circle" style="color:#FF5722;font-size:8px;"></i> Restoran</span>
                    <span><i class="fa-solid fa-circle" style="color:#2F8EFF;font-size:8px;"></i> Kurye</span>
                    <span><i class="fa-solid fa-circle" style="color:#0BBF80;font-size:8px;"></i> Müşteri</span>
                </div>
            </div>
        </section>

        <!-- Quick Street Zone & Status Controls (Zero Raw Coordinates Visible) -->
        <section class="panel-card">
            <div class="panel-card-head">
                <div>
                    <div class="panel-card-title">
                        <i class="fa-solid fa-compass" style="color:var(--blue);"></i>
                        <span>Hızlı Saha Bölgesi & Konum Kontrolleri</span>
                    </div>
                    <div style="font-size:12.5px;color:var(--text-2);margin-top:3px;">Canlı Kadıköy GPS bölgenizi tek dokunuşla güncelleyerek yeni siparişlere yakınlaşın</div>
                </div>
                <button type="button" class="btn btn-sm btn-primary" onclick="updateCourierLocation()" style="border-radius:9999px;">
                    <i class="fa-solid fa-arrows-rotate"></i> Konumu Senkronize Et
                </button>
            </div>

            <!-- Internal grid sync fields kept hidden from UI for app.js compatibility -->
            <div style="display:none;" aria-hidden="true">
                <input type="number" id="courier-input-x" value="14" min="0" max="35">
                <input type="number" id="courier-input-y" value="10" min="0" max="35">
            </div>

            <div class="map-filter-bar" id="courier-zone-chips" style="flex-wrap:wrap;gap:10px;">
                <button type="button" class="sort-chip active" onclick="selectCourierZone(14, 10, 40.9910, 29.0250, 'Rıhtım, Kadıköy', this)">
                    <i class="fa-solid fa-location-dot" style="color:var(--primary);"></i> Kadıköy Rıhtım
                </button>
                <button type="button" class="sort-chip" onclick="selectCourierZone(12, 8, 40.9850, 29.0280, 'Moda Cad., Kadıköy', this)">
                    <i class="fa-solid fa-location-dot" style="color:var(--primary);"></i> Moda Caddesi
                </button>
                <button type="button" class="sort-chip" onclick="selectCourierZone(16, 12, 40.9880, 29.0310, 'Bahariye, Kadıköy', this)">
                    <i class="fa-solid fa-location-dot" style="color:var(--primary);"></i> Bahariye / Süreyya
                </button>
                <button type="button" class="sort-chip" onclick="selectCourierZone(10, 6, 40.9950, 29.0280, 'Yeldeğirmeni, Kadıköy', this)">
                    <i class="fa-solid fa-location-dot" style="color:var(--primary);"></i> Yeldeğirmeni
                </button>
                <button type="button" class="sort-chip" onclick="selectCourierZone(20, 24, 40.9750, 29.0350, 'Fenerbahçe, Kadıköy', this)">
                    <i class="fa-solid fa-location-dot" style="color:var(--primary);"></i> Fenerbahçe Parkı
                </button>
                <button type="button" class="sort-chip" onclick="selectCourierZone(22, 26, 40.9740, 29.0410, 'Kalamış Marina, Kadıköy', this)">
                    <i class="fa-solid fa-location-dot" style="color:var(--primary);"></i> Kalamış Marina
                </button>
            </div>
        </section>

        <script>
            function selectCourierZone(x, y, lat, lon, neighborhood, btnEl) {
                var inX = document.getElementById('courier-input-x');
                var inY = document.getElementById('courier-input-y');
                if (inX) inX.value = x;
                if (inY) inY.value = y;
                var chips = document.querySelectorAll('#courier-zone-chips .sort-chip');
                chips.forEach(function(ch) { ch.classList.remove('active'); });
                if (btnEl) btnEl.classList.add('active');
                if (typeof GustoState !== 'undefined') {
                    var state = GustoState.get();
                    var c = state.couriers.find(function(item) { return item.id === state.selectedCourierId; });
                    if (c) {
                        c.lat = lat;
                        c.lon = lon;
                        c.neighborhood = neighborhood;
                    }
                }
                if (typeof updateCourierLocation === 'function') {
                    updateCourierLocation();
                }
            }
        </script>
    </div>
