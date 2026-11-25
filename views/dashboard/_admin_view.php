    <!-- ======================================================== -->
    <!-- 4. YÖNETİCİ KOMUTA MERKEZİ (CRAVELO ADMIN CENTER)        -->
    <!-- ======================================================== -->
    <div id="view-admin" class="panel-wrap hidden">
        <div class="panel-top-bar">
            <div>
                <div class="panel-top-title">
                    <i class="fa-solid fa-shield-halved" style="color:var(--purple);"></i>
                    <span>Cravelo Yönetici Komuta Merkezi</span>
                </div>
                <div style="font-size:13.5px;color:var(--text-2);margin-top:4px;">Yetkili Yönetici: <strong style="color:var(--text);">Mustafa Güreşir</strong> • Kadıköy Filo Radarı, Akıllı Atama Analizi ve Sistem Denetimi</div>
            </div>

            <!-- 4-Role Live Preview Switcher Pill Bar -->
            <div class="admin-preview-switcher" role="group" aria-label="Rol Önizleme Seçici">
                <span style="font-size:11.5px;font-weight:800;color:var(--text-3);padding:0 6px;display:inline-flex;align-items:center;gap:5px;">
                    <i class="fa-solid fa-eye"></i> Canlı Önizle:
                </span>
                <button type="button" class="btn btn-sm btn-primary" onclick="adminPreviewView('admin')" title="Yönetici Denetim Merkezi">
                    <i class="fa-solid fa-shield-halved"></i> Yönetici
                </button>
                <button type="button" class="btn btn-sm btn-outline" onclick="adminPreviewView('restaurant')" title="Restoran Mutfak (KDS) Ekranı">
                    <i class="fa-solid fa-utensils"></i> Restoran (KDS)
                </button>
                <button type="button" class="btn btn-sm btn-outline" onclick="adminPreviewView('courier')" title="Kurye Kokpiti">
                    <i class="fa-solid fa-motorcycle"></i> Kurye
                </button>
                <button type="button" class="btn btn-sm btn-outline" onclick="adminPreviewView('customer')" title="Müşteri Sipariş Vitrini">
                    <i class="fa-solid fa-bag-shopping"></i> Müşteri
                </button>
            </div>
        </div>

        <!-- Executive Admin KPI Bento Grid -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-icon" style="background:var(--blue-light);color:var(--blue);">
                    <i class="fa-solid fa-motorcycle"></i>
                </div>
                <div>
                    <div class="kpi-label">Aktif Kurye Filosu</div>
                    <div class="kpi-val" id="admin-kpi-couriers">4 Kurye</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon" style="background:var(--primary-light);color:var(--primary);">
                    <i class="fa-solid fa-store"></i>
                </div>
                <div>
                    <div class="kpi-label">Partner Restoranlar</div>
                    <div class="kpi-val" id="admin-kpi-restaurants">7 Restoran</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon" style="background:var(--success-light);color:var(--success);">
                    <i class="fa-solid fa-bag-shopping"></i>
                </div>
                <div>
                    <div class="kpi-label">Platform Siparişleri</div>
                    <div class="kpi-val" id="admin-kpi-orders">0 Sipariş</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon" style="background:var(--purple-light);color:var(--purple);">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
                <div>
                    <div class="kpi-label">Denetim Kayıtları</div>
                    <div class="kpi-val" id="admin-kpi-audits">0 Kayıt</div>
                </div>
            </div>
        </div>

        <!-- Platform Revenue & Order Analytics Panel -->
        <section class="panel-card" id="admin-analytics-panel">
            <div class="panel-card-head">
                <div>
                    <div class="panel-card-title">
                        <i class="fa-solid fa-chart-line" style="color:var(--success);"></i>
                        <span>Platform Ciro, Sipariş & Teslimat Modu Analitiği</span>
                    </div>
                    <div style="font-size:12.5px;color:var(--text-2);margin-top:3px;">Restoran bazlı canlı ciro grafiği, ortalama sepet tutarı ve teslimat modu kırılımı</div>
                </div>
                <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                    <input type="date" class="form-input" id="admin-analytics-date" style="width:155px;padding:7px 10px;border-radius:10px;" onchange="refreshAdminCommandCenter()" aria-label="Analitik Tarih Filtresi">
                    <button type="button" class="btn btn-sm btn-outline" onclick="var d=document.getElementById('admin-analytics-date');if(d)d.value='';refreshAdminCommandCenter();" style="border-radius:9999px;">
                        <i class="fa-solid fa-infinity"></i> Tüm Zamanlar
                    </button>
                    <button type="button" class="btn btn-sm btn-primary" onclick="exportAdminOrdersCsv()" style="border-radius:9999px;">
                        <i class="fa-solid fa-file-csv"></i> Siparişleri CSV İndir
                    </button>
                </div>
            </div>
            <div id="admin-analytics-summary" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px;margin-bottom:16px;"></div>
            <div id="admin-revenue-chart" style="display:grid;gap:10px;"></div>
        </section>

        <!-- All Platform Orders Management & Manual Courier Reassignment -->
        <section class="panel-card" id="admin-orders-panel">
            <div class="panel-card-head">
                <div>
                    <div class="panel-card-title">
                        <i class="fa-solid fa-list-check" style="color:var(--primary);"></i>
                        <span>Tüm Platform Siparişleri & Manuel Kurye Atama</span>
                    </div>
                    <div style="font-size:12.5px;color:var(--text-2);margin-top:3px;">Aktif siparişlerde kuryeyi anlık yeniden atayın, durum ilerletin veya yönetici yetkisiyle iptal edin</div>
                </div>
                <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                    <select class="form-input" id="admin-order-status-filter" style="width:165px;padding:7px 10px;border-radius:10px;" onchange="renderAdminOrdersTable()">
                        <option value="all">Tüm Durumlar</option>
                        <option value="alindi">Alındı</option>
                        <option value="hazirlaniyor">Hazırlanıyor</option>
                        <option value="hazir">Hazır</option>
                        <option value="yolda">Yolda</option>
                        <option value="teslim_edildi">Teslim Edildi</option>
                        <option value="iptal">İptal</option>
                    </select>
                    <select class="form-input" id="admin-order-mode-filter" style="width:150px;padding:7px 10px;border-radius:10px;" onchange="renderAdminOrdersTable()">
                        <option value="all">Tüm Modlar</option>
                        <option value="courier">🛵 Kurye Teslimat</option>
                        <option value="takeaway">🚶 Gel-Al</option>
                    </select>
                </div>
            </div>
            <div class="tbl-responsive">
                <table class="tbl">
                    <thead>
                        <tr>
                            <th>Sipariş</th>
                            <th>Müşteri & Restoran</th>
                            <th>Mod</th>
                            <th>Tutar</th>
                            <th>Durum</th>
                            <th>Kurye Ataması</th>
                            <th>Yönetici İşlemi</th>
                        </tr>
                    </thead>
                    <tbody id="admin-orders-body"></tbody>
                </table>
            </div>
        </section>

        <!-- Partner Restaurant Open / Close Management -->
        <section class="panel-card" id="admin-restaurants-panel">
            <div class="panel-card-head">
                <div>
                    <div class="panel-card-title">
                        <i class="fa-solid fa-store" style="color:var(--yellow);"></i>
                        <span>Partner Restoran Şube Yönetimi (Açık / Kapalı)</span>
                    </div>
                    <div style="font-size:12.5px;color:var(--text-2);margin-top:3px;">Yoğunluk veya bakım durumunda restoranları tek tıkla sipariş alımına kapatıp açın</div>
                </div>
                <span class="status-pill info"><i class="fa-solid fa-shield-halved"></i> Kalıcı JSON Senkronizasyonu</span>
            </div>
            <div id="admin-restaurants-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:12px;"></div>
        </section>

        <!-- Interactive Kadıköy Google Maps City Radar -->
        <section class="panel-card">
            <div class="panel-card-head">
                <div>
                    <div class="panel-card-title">
                        <i class="fa-solid fa-map-location-dot" style="color:var(--primary);"></i>
                        <span>Canlı Kadıköy Şehir Radarı (Google Haritalar)</span>
                    </div>
                    <div style="font-size:12.5px;color:var(--text-2);margin-top:3px;">Tüm restoranlar, saha kuryeleri ve müşteri teslimat noktalarının gerçek zamanlı dağılımı</div>
                </div>
                <div class="map-filter-bar" id="map-filter-group" role="group" aria-label="Harita Katman Filtreleri">
                    <button type="button" class="sort-chip active" id="map-btn-all" onclick="GustoMap.setFilter('all', this)">
                        <i class="fa-solid fa-layer-group"></i> Tümü
                    </button>
                    <button type="button" class="sort-chip" id="map-btn-restaurants" onclick="GustoMap.setFilter('restaurants', this)">
                        <i class="fa-solid fa-utensils"></i> Restoranlar
                    </button>
                    <button type="button" class="sort-chip" id="map-btn-couriers" onclick="GustoMap.setFilter('couriers', this)">
                        <i class="fa-solid fa-motorcycle"></i> Kuryeler
                    </button>
                    <button type="button" class="sort-chip" id="map-btn-customers" onclick="GustoMap.setFilter('customers', this)">
                        <i class="fa-solid fa-house"></i> Müşteriler
                    </button>
                </div>
            </div>

            <div class="map-frame map-hud-container">
                <div id="admin-map-leaflet" style="width:100%;height:350px;z-index:2;"></div>
                <canvas id="admin-map-canvas" class="map-canvas" width="900" height="310" style="position:absolute;top:0;left:0;width:100%;height:310px;cursor:crosshair;display:none;" onmousemove="GustoMap.handleCanvasMouseMove(event, 'admin-map-canvas')" onmouseleave="GustoMap.hideTooltip()"></canvas>
                <!-- Canlı İnteraktif HUD Tooltip Kartı -->
                <div id="map-hud-tooltip" style="display:none;position:absolute;pointer-events:none;background:var(--surface);border:1px solid var(--blue);box-shadow:var(--shadow-elevated);border-radius:14px;padding:12px 15px;color:var(--text);font-size:12px;z-index:30;backdrop-filter:blur(12px);min-width:190px;"></div>
                <!-- Floating Map Legend -->
                <div class="map-hud-legend">
                    <span><i class="fa-solid fa-circle" style="color:#FF5722;font-size:8px;"></i> Restoran</span>
                    <span><i class="fa-solid fa-circle" style="color:#2F8EFF;font-size:8px;"></i> Kurye</span>
                    <span><i class="fa-solid fa-circle" style="color:#0BBF80;font-size:8px;"></i> Müşteri</span>
                </div>
            </div>
        </section>

        <!-- Courier Fleet Workload & Smart Assignment Panel -->
        <section class="panel-card">
            <div class="panel-card-head">
                <div>
                    <div class="panel-card-title">
                        <i class="fa-solid fa-users" style="color:var(--blue);"></i>
                        <span>Kurye Filosu & Akıllı İş Yükü Skorlaması</span>
                    </div>
                    <div style="font-size:12.5px;color:var(--text-2);margin-top:3px;">Haversine mesafe ve aktif görev yükü dengesine göre en düşük skorlu kurye yeni siparişe otomatik atanır</div>
                </div>
                <span class="status-pill info"><i class="fa-solid fa-calculator"></i> Skor = Mesafe + 2×Görev</span>
            </div>
            <div class="tbl-responsive">
                <table class="tbl">
                    <thead>
                        <tr>
                            <th>Kurye Adı</th>
                            <th>Araç Tipi</th>
                            <th>Bölge / Cadde</th>
                            <th>İş Yükü</th>
                            <th>Atama Skoru</th>
                            <th>Durum</th>
                        </tr>
                    </thead>
                    <tbody id="admin-couriers-body"></tbody>
                </table>
            </div>
        </section>

        <!-- Clean Audit Log Table -->
        <section class="panel-card">
            <div class="panel-card-head">
                <div>
                    <div class="panel-card-title">
                        <i class="fa-solid fa-clock-rotate-left" style="color:var(--purple);"></i>
                        <span>Sipariş Durum Tarihçesi (Audit Log)</span>
                    </div>
                    <div style="font-size:12.5px;color:var(--text-2);margin-top:3px;">Durum geçişleri, sorumlu aktör rolü ve zaman damgalı sistem denetim kayıtları</div>
                </div>
                <div class="live-status-pill">
                    <span class="live-pulse-dot"></span> Denetim Kaydı Aktif
                </div>
            </div>
            <div class="tbl-responsive">
                <table class="tbl">
                    <thead>
                        <tr>
                            <th>Zaman</th>
                            <th>Sipariş No</th>
                            <th>Önceki Durum</th>
                            <th>Yeni Durum</th>
                            <th>Değiştiren Rol</th>
                            <th>Açıklama</th>
                        </tr>
                    </thead>
                    <tbody id="admin-audit-body"></tbody>
                </table>
            </div>
        </section>
    </div>
