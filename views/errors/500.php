<div class="text-center">
    <a href="<?= $request->url('/') ?>" class="error-brand-mini">
        <span class="mini-icon"><i class="fa-solid fa-burger"></i></span>
        <span>Crave<em style="color: var(--crv-primary); font-style: normal;">lo</em></span>
    </a>

    <div>
        <div class="error-icon is-danger">
            <i class="fa-solid fa-server"></i>
        </div>
    </div>

    <div class="error-code">Hata 500 • Sistem Güvenliği</div>
    <h1 class="error-title">500 — Sunucu Hatası</h1>
    <p class="error-desc">
        İsteğiniz işlenirken sunucuda beklenmeyen bir durum oluştu. Hata ayrıntıları sunucu günlüğüne kaydedildi; teknik detaylar güvenlik sebebiyle gizlenmiştir.
    </p>

    <div class="error-actions">
        <a href="<?= $request->url('/') ?>" class="btn-brand">
            <i class="fa-solid fa-house"></i> Ana Sayfaya Dön
        </a>
        <button type="button" onclick="location.reload()" class="btn-ghost-auth">
            <i class="fa-solid fa-rotate-right"></i> Tekrar Dene
        </button>
    </div>
</div>
