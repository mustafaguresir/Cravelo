    <!-- ======================================================== -->
    <!-- 2. RESTORAN MUTFAK EKRANI (CRAVELO KDS)                  -->
    <!-- ======================================================== -->
    <div id="view-restaurant" class="panel-wrap hidden">
        <div class="panel-top-bar">
            <div>
                <div class="panel-top-title">
                    <i class="fa-solid fa-utensils" style="color:var(--primary);"></i>
                    <span>Restoran Mutfak Ekranı (KDS)</span>
                </div>
                <div style="font-size:13.5px;color:var(--text-2);margin-top:4px;">Canlı Mutfak Sipariş Akışı, Hazırlık Durumları ve Anlık Menü Stok Kontrol Masası</div>
            </div>
            <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                <div class="live-status-pill">
                    <span class="live-pulse-dot"></span> Canlı KDS Hattı Aktif
                </div>
                <span class="status-pill warning" title="Aktif Restoran Değerlendirmesi">
                    <i class="fa-solid fa-star"></i> <strong id="kpi-res-rating">4.9 ⭐</strong>
                </span>
                <div class="panel-selector-pill">
                    <label for="res-selector" style="font-size:12.5px;font-weight:800;color:var(--text-2);white-space:nowrap;">Aktif Restoran:</label>
                    <select class="form-input" id="res-selector" style="width:220px;padding:7px 12px;border-radius:10px;" onchange="changeVendorRestaurant()"></select>
                </div>
            </div>
        </div>

        <!-- Executive KDS KPI Bento Grid -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-icon" style="background:var(--success-light);color:var(--success);">
                    <i class="fa-solid fa-turkish-lira-sign"></i>
                </div>
                <div>
                    <div class="kpi-label">Günlük Ciro</div>
                    <div class="kpi-val" id="kpi-revenue">0.00 ₺</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon" style="background:var(--blue-light);color:var(--blue);">
                    <i class="fa-solid fa-bowl-food"></i>
                </div>
                <div>
                    <div class="kpi-label">Aktif Sipariş</div>
                    <div class="kpi-val" id="kpi-orders">0 Adet</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon" style="background:var(--yellow-light);color:var(--yellow);">
                    <i class="fa-solid fa-fire-burner"></i>
                </div>
                <div>
                    <div class="kpi-label">Hazırlanan</div>
                    <div class="kpi-val" id="kpi-preparing">0 Adet</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon" style="background:var(--purple-light);color:var(--purple);">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <div>
                    <div class="kpi-label">Tamamlanan</div>
                    <div class="kpi-val" id="kpi-completed">0 Adet</div>
                </div>
            </div>
        </div>

        <!-- Live Kitchen Orders Board (KDS Tickets) -->
        <section class="panel-card">
            <div class="panel-card-head">
                <div>
                    <div class="panel-card-title">
                        <i class="fa-solid fa-bell" style="color:var(--yellow);"></i>
                        <span>Canlı Mutfak Siparişleri</span>
                    </div>
                    <div style="font-size:12.5px;color:var(--text-2);margin-top:3px;">Gelen siparişleri tek tıkla hazırlamaya alın, paketleyin ve en yakın kuryeyi çağırın</div>
                </div>
                <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                    <button type="button" class="btn btn-sm btn-outline" onclick="GustoAudio.play('bell');showToast('🔔', 'KDS Mutfak Bildirim Zili Çaldı!');" title="KDS Mutfak Yeni Sipariş Zili">
                        <i class="fa-solid fa-bell"></i> KDS Zil Çal
                    </button>
                    <button type="button" class="btn btn-sm btn-primary" onclick="printKdsKitchenSlip()" title="KDS Mutfak Fişi Yazdır">
                        <i class="fa-solid fa-print"></i> Mutfak Fişi
                    </button>
                </div>
            </div>
            <div id="res-orders-body" class="kds-board"></div>
        </section>

        <!-- Modern Menu & Stock Toggle Cards -->
        <section class="panel-card">
            <div class="panel-card-head">
                <div>
                    <div class="panel-card-title">
                        <i class="fa-solid fa-sliders" style="color:var(--primary);"></i>
                        <span>Menü & Anlık Stok Yönetimi</span>
                    </div>
                    <div style="font-size:12.5px;color:var(--text-2);margin-top:3px;">Tükenen yemekleri anında satışa kapatın veya stok yenilendiğinde tekrar aktif edin</div>
                </div>
                <span class="status-pill success"><i class="fa-solid fa-bolt"></i> Anlık Senkronizasyon</span>
            </div>
            <div id="res-menu-body" class="menu-toggle-grid"></div>
        </section>
    </div>
