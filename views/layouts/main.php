<!DOCTYPE html>
<html lang="tr" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle ?? 'Cravelo – Kapında Sıcak Lezzetler & Hızlı Kurye Takip', ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0B0F19">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Cdefs%3E%3ClinearGradient id='g' x1='0' y1='0' x2='1' y2='1'%3E%3Cstop offset='0%25' stop-color='%23FF6B35'/%3E%3Cstop offset='100%25' stop-color='%23FA4A0C'/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='64' height='64' rx='18' fill='url(%23g)'/%3E%3Cpath d='M16 28c0-8 7.2-13 16-13s16 5 16 13H16zm-2 5h36a3 3 0 0 1 0 6H14a3 3 0 0 1 0-6zm3 9h30v3c0 3.3-2.7 6-6 6H23c-3.3 0-6-2.7-6-6v-3z' fill='%23fff'/%3E%3C/svg%3E">

    <!-- Early Theme Hydration (Zero Flash in Light/Dark Mode) -->
    <script>
        (function () {
            try {
                var savedTheme = localStorage.getItem('gustogo-theme') || 'dark';
                document.documentElement.setAttribute('data-theme', savedTheme);
            } catch (e) {}
        })();
        window.BASE_PATH = <?= json_encode($request->getBasePath(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
        window.ACTIVE_SESSION = <?= json_encode($currentUser ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    </script>

    <!-- Google Fonts & FontAwesome CDN -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Leaflet CSS for Real Google Maps Street Engine -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

    <!-- Cravelo Tasarım Sistemi Stilleri -->
    <link rel="stylesheet" href="<?= $request->asset('css/style.css') ?>">

    <style>
        .crv-flash-wrap {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 9999;
            max-width: 560px;
            width: calc(100% - 32px);
            pointer-events: none;
            animation: crvFlashIn 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes crvFlashIn {
            from { opacity: 0; transform: translate(-50%, -14px) scale(0.97); }
            to   { opacity: 1; transform: translate(-50%, 0) scale(1); }
        }
        .crv-flash-toast {
            pointer-events: auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 13px 18px;
            border-radius: 16px;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            font-family: var(--font-sans, 'Plus Jakarta Sans', system-ui, sans-serif);
            font-size: 13.5px;
            font-weight: 700;
            box-shadow: 0 18px 42px -10px rgba(0, 0, 0, 0.45);
            border: 1px solid rgba(255, 255, 255, 0.14);
            color: #FFFFFF;
        }
        .crv-flash-toast.is-success {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.94), rgba(5, 150, 105, 0.94));
        }
        .crv-flash-toast.is-danger {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.94), rgba(220, 38, 38, 0.94));
        }
        .crv-flash-toast.is-warning {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.95), rgba(217, 119, 6, 0.95));
        }
        .crv-flash-toast.is-info {
            background: linear-gradient(135deg, rgba(255, 107, 53, 0.94), rgba(250, 74, 12, 0.94));
        }
        .crv-flash-body {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            line-height: 1.4;
        }
        .crv-flash-close {
            width: 28px;
            height: 28px;
            border-radius: 9999px;
            border: none;
            background: rgba(255, 255, 255, 0.18);
            color: #FFFFFF;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            cursor: pointer;
            flex-shrink: 0;
            transition: background 0.2s ease, transform 0.2s ease;
        }
        .crv-flash-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.06);
        }
    </style>
</head>
<body>
    <?php if (!empty($flash)):
        $flashType = (string)($flash['type'] ?? 'info');
        $flashClass = match ($flashType) {
            'success' => 'is-success',
            'danger'  => 'is-danger',
            'warning' => 'is-warning',
            default   => 'is-info',
        };
        $flashIcon = match ($flashType) {
            'success' => 'fa-circle-check',
            'danger'  => 'fa-circle-exclamation',
            'warning' => 'fa-triangle-exclamation',
            default   => 'fa-bell',
        };
    ?>
        <div class="crv-flash-wrap" id="crv-flash-wrap" role="status" aria-live="polite">
            <div class="crv-flash-toast <?= $flashClass ?>">
                <span class="crv-flash-body">
                    <i class="fa-solid <?= $flashIcon ?>"></i>
                    <span><?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?></span>
                </span>
                <button type="button" class="crv-flash-close" onclick="var w=document.getElementById('crv-flash-wrap');if(w)w.remove();" aria-label="Bildirimi Kapat">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>
        <script>
            setTimeout(function () {
                var w = document.getElementById('crv-flash-wrap');
                if (w) {
                    w.style.transition = 'opacity 0.35s ease, transform 0.35s ease';
                    w.style.opacity = '0';
                    w.style.transform = 'translate(-50%, -10px)';
                    setTimeout(function () { if (w.parentNode) w.remove(); }, 350);
                }
            }, 5500);
        </script>
    <?php endif; ?>

    <?= $content ?>

    <!-- Çekirdek JavaScript Kütüphaneleri -->
    <script src="<?= $request->asset('js/gsap.min.js') ?>"></script>
    <script src="<?= $request->asset('js/ScrollTrigger.min.js') ?>"></script>
    <script src="<?= $request->asset('js/burger-scrollytelling.js') ?>"></script>
    <script src="<?= $request->asset('js/data.js') ?>"></script>
    <script src="<?= $request->asset('js/state.js') ?>"></script>
    <!-- Leaflet Engine for Real Google Maps Tiles -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="<?= $request->asset('js/map.js') ?>"></script>
    <script src="<?= $request->asset('js/audio.js') ?>"></script>
    <script src="<?= $request->asset('js/app.js') ?>"></script>
</body>
</html>
