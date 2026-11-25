<div class="text-center">
    <a href="<?= $request->url('/') ?>" class="error-brand-mini">
        <span class="mini-icon"><i class="fa-solid fa-burger"></i></span>
        <span>Crave<em style="color: var(--crv-primary); font-style: normal;">lo</em></span>
    </a>

    <div>
        <div class="error-icon is-danger">
            <i class="fa-solid fa-shield-halved"></i>
        </div>
    </div>

    <div class="error-code">Hata 403 • Yetki Koruması</div>
    <h1 class="error-title">403 — Yetkisiz Erişim</h1>
    <p class="error-desc">
        <?= htmlspecialchars($message ?? 'Bu kaynağa veya başka bir kullanıcının siparişine erişim yetkiniz bulunmamaktadır. (IDOR Engellendi)', ENT_QUOTES, 'UTF-8') ?>
    </p>

    <div class="error-actions">
        <a href="<?= $request->url('/') ?>" class="btn-brand">
            <i class="fa-solid fa-house"></i> Ana Sayfaya Dön
        </a>
        <a href="<?= $request->url('/login') ?>" class="btn-ghost-auth">
            <i class="fa-solid fa-right-to-bracket"></i> Giriş Ekranı
        </a>
    </div>
</div>
