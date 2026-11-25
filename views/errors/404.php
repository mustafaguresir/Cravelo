<div class="text-center">
    <a href="<?= $request->url('/') ?>" class="error-brand-mini">
        <span class="mini-icon"><i class="fa-solid fa-burger"></i></span>
        <span>Crave<em style="color: var(--crv-primary); font-style: normal;">lo</em></span>
    </a>

    <div>
        <div class="error-icon is-warning">
            <i class="fa-solid fa-map-location-dot"></i>
        </div>
    </div>

    <div class="error-code">Hata 404 • Rota Bulunamadı</div>
    <h1 class="error-title">404 — Sayfa Bulunamadı</h1>
    <p class="error-desc">
        <?= htmlspecialchars($message ?? 'Aradığınız sayfa veya işlem Cravelo üzerinde mevcut değil.', ENT_QUOTES, 'UTF-8') ?>
        <?php if (!empty($path)): ?>
            <br><span class="error-path-chip"><?= htmlspecialchars($path, ENT_QUOTES, 'UTF-8') ?></span>
        <?php endif; ?>
    </p>

    <div class="error-actions">
        <a href="<?= $request->url('/') ?>" class="btn-brand">
            <i class="fa-solid fa-house"></i> Ana Sayfaya Dön
        </a>
        <button type="button" onclick="history.back()" class="btn-ghost-auth">
            <i class="fa-solid fa-arrow-left"></i> Önceki Sayfa
        </button>
    </div>
</div>
