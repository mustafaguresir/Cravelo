<?php
$isSplitAuth = str_contains($content ?? '', 'auth-split');
?>
<!DOCTYPE html>
<html lang="tr" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle ?? 'Cravelo — Giriş Yap & Kayıt Ol', ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Cdefs%3E%3ClinearGradient id='g' x1='0' y1='0' x2='1' y2='1'%3E%3Cstop offset='0%25' stop-color='%23FF6B35'/%3E%3Cstop offset='100%25' stop-color='%23FA4A0C'/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='64' height='64' rx='18' fill='url(%23g)'/%3E%3Cpath d='M16 28c0-8 7.2-13 16-13s16 5 16 13H16zm-2 5h36a3 3 0 0 1 0 6H14a3 3 0 0 1 0-6zm3 9h30v3c0 3.3-2.7 6-6 6H23c-3.3 0-6-2.7-6-6v-3z' fill='%23fff'/%3E%3C/svg%3E">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script>
        (function () {
            try {
                var savedTheme = localStorage.getItem('cravelo-theme') || localStorage.getItem('gustogo-theme') || 'dark';
                document.documentElement.setAttribute('data-theme', savedTheme);
            } catch (e) {}
        })();
    </script>
    <style>
        :root,
        :root[data-theme="dark"] {
            --crv-primary: #FF6B35;
            --crv-primary-deep: #FA4A0C;
            --crv-primary-glow: rgba(255, 107, 53, 0.34);
            --crv-primary-soft: rgba(255, 107, 53, 0.14);
            --crv-emerald: #10B981;
            --crv-emerald-deep: #059669;
            --crv-emerald-soft: rgba(16, 185, 129, 0.14);
            --crv-bg: #080C16;
            --crv-bg-mesh-1: rgba(255, 107, 53, 0.15);
            --crv-bg-mesh-2: rgba(56, 189, 248, 0.08);
            --crv-bg-mesh-3: rgba(245, 158, 11, 0.07);
            --crv-surface: rgba(15, 22, 38, 0.88);
            --crv-surface-solid: #111827;
            --crv-surface-2: rgba(255, 255, 255, 0.04);
            --crv-surface-hover: rgba(255, 255, 255, 0.07);
            --crv-brand-bg: linear-gradient(155deg, #131B2E 0%, #1C1414 55%, #0D1322 100%);
            --crv-brand-border: rgba(255, 255, 255, 0.08);
            --crv-border: rgba(255, 255, 255, 0.09);
            --crv-border-strong: rgba(255, 255, 255, 0.15);
            --crv-input-bg: rgba(9, 14, 26, 0.85);
            --crv-input-focus: rgba(15, 23, 42, 0.98);
            --crv-text: #F8FAFC;
            --crv-text-2: #94A3B8;
            --crv-text-3: #64748B;
            --crv-shadow: 0 32px 72px -16px rgba(0, 0, 0, 0.78), 0 0 0 1px rgba(255, 255, 255, 0.05);
            --crv-chip-bg: rgba(255, 255, 255, 0.04);
            --crv-chip-border: rgba(255, 255, 255, 0.08);
            --crv-dot-grid: rgba(255, 255, 255, 0.045);
        }

        :root[data-theme="light"] {
            --crv-primary: #FA4A0C;
            --crv-primary-deep: #E03E05;
            --crv-primary-glow: rgba(250, 74, 12, 0.25);
            --crv-primary-soft: rgba(250, 74, 12, 0.1);
            --crv-emerald: #059669;
            --crv-emerald-deep: #047857;
            --crv-emerald-soft: rgba(16, 185, 129, 0.12);
            --crv-bg: #F8FAFC;
            --crv-bg-mesh-1: rgba(255, 107, 53, 0.11);
            --crv-bg-mesh-2: rgba(56, 189, 248, 0.07);
            --crv-bg-mesh-3: rgba(245, 158, 11, 0.06);
            --crv-surface: rgba(255, 255, 255, 0.96);
            --crv-surface-solid: #FFFFFF;
            --crv-surface-2: #F1F5F9;
            --crv-surface-hover: #E2E8F0;
            --crv-brand-bg: linear-gradient(155deg, #FFF7F2 0%, #FFEFE5 52%, #FFFFFF 100%);
            --crv-brand-border: rgba(250, 74, 12, 0.14);
            --crv-border: #E2E8F0;
            --crv-border-strong: #CBD5E1;
            --crv-input-bg: #FFFFFF;
            --crv-input-focus: #FFFFFF;
            --crv-text: #0F172A;
            --crv-text-2: #475569;
            --crv-text-3: #64748B;
            --crv-shadow: 0 28px 64px -16px rgba(15, 23, 42, 0.12), 0 0 0 1px rgba(15, 23, 42, 0.05);
            --crv-chip-bg: #F8FAFC;
            --crv-chip-border: #E2E8F0;
            --crv-dot-grid: rgba(15, 23, 42, 0.05);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            color: var(--crv-text);
            background-color: var(--crv-bg);
            background-image:
                radial-gradient(ellipse 65% 50% at 15% 15%, var(--crv-bg-mesh-1), transparent 60%),
                radial-gradient(ellipse 55% 45% at 88% 85%, var(--crv-bg-mesh-2), transparent 60%),
                radial-gradient(ellipse 50% 45% at 85% 12%, var(--crv-bg-mesh-3), transparent 55%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            transition: background-color 0.25s ease, color 0.25s ease;
        }

        /* Floating Top Utility Bar */
        .auth-topbar {
            width: 100%;
            max-width: 1060px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .auth-topbar.is-compact {
            max-width: 520px;
        }

        .auth-back-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 9999px;
            background: var(--crv-surface);
            border: 1px solid var(--crv-border);
            color: var(--crv-text-2);
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .auth-back-pill:hover {
            color: var(--crv-text);
            border-color: var(--crv-primary);
            transform: translateY(-1px);
        }

        .auth-theme-btn {
            width: 40px;
            height: 40px;
            border-radius: 9999px;
            background: var(--crv-surface);
            border: 1px solid var(--crv-border);
            color: var(--crv-text);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            cursor: pointer;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .auth-theme-btn:hover {
            border-color: var(--crv-primary);
            color: var(--crv-primary);
            transform: translateY(-1px) scale(1.04);
        }

        .auth-page {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Split-Screen Auth Shell */
        .auth-split {
            width: 100%;
            max-width: 1060px;
            display: grid;
            grid-template-columns: 1.02fr 1.08fr;
            background: var(--crv-surface);
            border: 1px solid var(--crv-border);
            border-radius: 28px;
            box-shadow: var(--crv-shadow);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            overflow: hidden;
        }

        /* Left Brand Editorial Showcase */
        .auth-brand-panel {
            position: relative;
            padding: 40px 38px;
            background: var(--crv-brand-bg);
            border-right: 1px solid var(--crv-brand-border);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 28px;
            overflow: hidden;
        }

        .auth-brand-panel::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image: radial-gradient(var(--crv-dot-grid) 1.2px, transparent 1.2px);
            background-size: 22px 22px;
            pointer-events: none;
        }

        .auth-brand-panel::after {
            content: "";
            position: absolute;
            width: 340px;
            height: 340px;
            right: -110px;
            bottom: -110px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255, 107, 53, 0.24) 0%, transparent 70%);
            pointer-events: none;
        }

        .auth-brand-panel > * {
            position: relative;
            z-index: 1;
        }

        .auth-brand-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .auth-brand-lockup {
            display: inline-flex;
            align-items: center;
            gap: 11px;
            text-decoration: none;
            color: var(--crv-text);
            font-size: 22px;
            font-weight: 900;
            letter-spacing: -0.6px;
        }

        .auth-brand-mark {
            width: 42px;
            height: 42px;
            border-radius: 13px;
            background: linear-gradient(135deg, #FF6B35, #FA4A0C);
            color: #FFFFFF;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            box-shadow: 0 8px 22px rgba(255, 107, 53, 0.4);
        }

        .auth-live-pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 5px 12px;
            border-radius: 9999px;
            font-size: 11.5px;
            font-weight: 800;
            color: var(--crv-emerald);
            background: var(--crv-emerald-soft);
            border: 1px solid rgba(16, 185, 129, 0.28);
        }

        .auth-live-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--crv-emerald);
            box-shadow: 0 0 8px var(--crv-emerald);
            animation: crvPulse 2s ease-in-out infinite;
        }

        @keyframes crvPulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.45; transform: scale(0.82); }
        }

        .auth-brand-copy h1 {
            font-size: clamp(26px, 2.7vw, 33px);
            font-weight: 900;
            line-height: 1.18;
            letter-spacing: -0.9px;
            color: var(--crv-text);
            margin: 0 0 14px;
        }

        .auth-brand-copy h1 span {
            color: var(--crv-primary);
        }

        .auth-brand-copy p {
            font-size: 14.5px;
            line-height: 1.65;
            color: var(--crv-text-2);
            margin: 0;
        }

        .auth-stat-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-top: 24px;
        }

        .auth-stat-tile {
            background: var(--crv-surface);
            border: 1px solid var(--crv-border);
            border-radius: 16px;
            padding: 14px 12px;
            text-align: left;
            backdrop-filter: blur(12px);
        }

        .auth-stat-val {
            font-size: 19px;
            font-weight: 900;
            letter-spacing: -0.5px;
            color: var(--crv-text);
            line-height: 1.1;
            margin-bottom: 4px;
        }

        .auth-stat-val.accent {
            color: var(--crv-primary);
        }

        .auth-stat-lbl {
            font-size: 11.5px;
            font-weight: 600;
            color: var(--crv-text-2);
        }

        .auth-eco-list {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }

        .auth-eco-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 14px;
            background: var(--crv-surface-2);
            border: 1px solid var(--crv-border);
            font-size: 12.5px;
            font-weight: 700;
            color: var(--crv-text);
        }

        .auth-eco-ico {
            width: 30px;
            height: 30px;
            border-radius: 9px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            flex-shrink: 0;
        }

        /* Right Form Panel */
        .auth-form-panel {
            padding: 36px 38px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .auth-form-head {
            margin-bottom: 20px;
        }

        .auth-form-head h2 {
            font-size: 24px;
            font-weight: 900;
            letter-spacing: -0.6px;
            color: var(--crv-text);
            margin: 0 0 6px;
        }

        .auth-form-head p {
            font-size: 13.5px;
            color: var(--crv-text-2);
            margin: 0;
        }

        /* Segmented Tab Switcher */
        .auth-tabs,
        .segmented-control {
            display: flex;
            background: var(--crv-surface-2);
            border: 1px solid var(--crv-border);
            border-radius: 14px;
            padding: 4px;
            gap: 4px;
            margin-bottom: 20px;
        }

        .auth-tab-link {
            flex: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 700;
            text-decoration: none;
            color: var(--crv-text-2);
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .auth-tab-link:hover {
            color: var(--crv-text);
            background: var(--crv-surface-hover);
        }

        .auth-tab-link.active {
            background: linear-gradient(135deg, #FF6B35, #FA4A0C);
            color: #FFFFFF;
            box-shadow: 0 4px 14px var(--crv-primary-glow);
        }

        .auth-tab-link.active.is-register {
            background: linear-gradient(135deg, #10B981, #059669);
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.36);
        }

        /* Self-Contained Layout & Utility Fallbacks (100% CDN-Independent) */
        .w-100 { width: 100% !important; }
        .d-none { display: none !important; }
        .flex-fill { flex: 1 1 0% !important; }
        .text-center { text-align: center !important; }
        .fw-bold { font-weight: 700 !important; }
        .small { font-size: 12.5px; }
        .mb-2 { margin-bottom: 10px !important; }
        .mb-3 { margin-bottom: 16px !important; }
        .mb-4 { margin-bottom: 22px !important; }
        .me-1 { margin-right: 4px !important; }
        .me-2 { margin-right: 8px !important; }
        .py-2 { padding-top: 9px !important; padding-bottom: 9px !important; }
        .px-3 { padding-left: 14px !important; padding-right: 14px !important; }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-family: inherit;
            line-height: 1.4;
            cursor: pointer;
            text-decoration: none;
            border: 1px solid transparent;
        }

        .crv-form-row {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            width: 100%;
        }

        .auth-form-panel form {
            width: 100%;
        }

        /* Inputs & Field Groups */
        .form-label {
            display: block;
            width: 100%;
            font-size: 12.5px;
            font-weight: 700;
            color: var(--crv-text);
            margin-bottom: 6px;
            letter-spacing: -0.15px;
        }

        .crv-input-wrap {
            position: relative;
            display: flex;
            align-items: center;
            width: 100%;
        }

        .crv-input-icon {
            position: absolute;
            left: 15px;
            color: var(--crv-text-3);
            font-size: 14px;
            pointer-events: none;
            transition: color 0.2s ease;
        }

        .form-control {
            display: block;
            width: 100% !important;
            min-width: 0;
            font-family: inherit;
            line-height: 1.45;
            outline: none;
            background-color: var(--crv-input-bg) !important;
            border: 1.5px solid var(--crv-border-strong) !important;
            color: var(--crv-text) !important;
            border-radius: 13px !important;
            padding: 11px 16px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .crv-input-wrap .form-control {
            padding-left: 42px;
        }

        .crv-input-wrap.has-toggle .form-control {
            padding-right: 44px;
        }

        .form-control:focus {
            border-color: var(--crv-primary) !important;
            box-shadow: 0 0 0 3.5px var(--crv-primary-soft) !important;
            background-color: var(--crv-input-focus) !important;
        }

        .crv-input-wrap:focus-within .crv-input-icon {
            color: var(--crv-primary);
        }

        .form-control::placeholder {
            color: var(--crv-text-3) !important;
            font-size: 13.5px;
        }

        .crv-pwd-toggle {
            position: absolute;
            right: 10px;
            width: 32px;
            height: 32px;
            border: none;
            border-radius: 9px;
            background: transparent;
            color: var(--crv-text-3);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: color 0.2s ease, background 0.2s ease;
        }

        .crv-pwd-toggle:hover {
            color: var(--crv-text);
            background: var(--crv-surface-2);
        }

        /* Country-Code Aware Phone Field */
        .crv-phone-group {
            display: flex;
            align-items: stretch;
            gap: 8px;
            width: 100%;
        }

        .crv-country-select {
            flex: 0 0 132px;
            width: 132px;
            min-width: 122px;
            font-family: inherit;
            font-size: 13.5px;
            font-weight: 700;
            color: var(--crv-text);
            background-color: var(--crv-input-bg);
            border: 1.5px solid var(--crv-border-strong);
            border-radius: 13px;
            padding: 11px 10px;
            cursor: pointer;
            outline: none;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .crv-country-select:focus {
            border-color: var(--crv-primary);
            box-shadow: 0 0 0 3.5px var(--crv-primary-soft);
            background-color: var(--crv-input-focus);
        }

        .crv-country-select option {
            background-color: var(--crv-surface-solid);
            color: var(--crv-text);
        }

        .crv-phone-group .crv-input-wrap {
            flex: 1 1 auto;
            min-width: 0;
        }

        .crv-phone-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-top: 6px;
            font-size: 11.5px;
            font-weight: 600;
            color: var(--crv-text-3);
        }

        .crv-phone-counter {
            font-variant-numeric: tabular-nums;
            font-weight: 800;
            padding: 1px 8px;
            border-radius: 999px;
            background: var(--crv-surface-2);
            border: 1px solid var(--crv-border);
            color: var(--crv-text-2);
        }

        .crv-phone-counter.is-valid {
            background: var(--crv-emerald-soft);
            border-color: rgba(16, 185, 129, 0.35);
            color: var(--crv-emerald);
        }

        /* Primary Brand Buttons */
        .btn-brand {
            background: linear-gradient(135deg, #FF6B35, #FA4A0C);
            border: none;
            color: #FFFFFF;
            font-weight: 800;
            font-size: 14.5px;
            letter-spacing: -0.2px;
            border-radius: 14px;
            padding: 13px 22px;
            box-shadow: 0 8px 22px var(--crv-primary-glow);
            transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-brand:hover {
            background: linear-gradient(135deg, #FA4A0C, #DC3B04);
            color: #FFFFFF;
            transform: translateY(-1px);
            box-shadow: 0 12px 28px var(--crv-primary-glow);
        }

        .btn-brand-emerald {
            background: linear-gradient(135deg, #10B981, #059669);
            box-shadow: 0 8px 22px rgba(16, 185, 129, 0.35);
        }

        .btn-brand-emerald:hover {
            background: linear-gradient(135deg, #059669, #047857);
            box-shadow: 0 12px 28px rgba(16, 185, 129, 0.45);
        }

        .btn-ghost-auth {
            background: var(--crv-surface-2);
            border: 1px solid var(--crv-border-strong);
            color: var(--crv-text);
            font-weight: 700;
            font-size: 14px;
            border-radius: 14px;
            padding: 12px 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .btn-ghost-auth:hover {
            color: var(--crv-primary);
            border-color: var(--crv-primary);
            background: var(--crv-primary-soft);
            transform: translateY(-1px);
        }

        /* Demo Accounts Card */
        .auth-demo-box {
            margin-top: 22px;
            padding: 16px;
            border-radius: 18px;
            background: var(--crv-surface-2);
            border: 1px solid var(--crv-border);
        }

        .auth-demo-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 10px;
        }

        .auth-demo-title {
            font-size: 12px;
            font-weight: 800;
            color: var(--crv-text);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .auth-demo-pass {
            font-size: 11px;
            font-weight: 800;
            padding: 3px 9px;
            border-radius: 9999px;
            background: var(--crv-primary-soft);
            color: var(--crv-primary);
            border: 1px solid rgba(255, 107, 53, 0.28);
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        }

        .demo-group-label {
            font-size: 10.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: var(--crv-text-3);
            margin: 10px 0 6px;
        }

        .demo-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 7px;
        }

        .demo-pill {
            cursor: pointer;
            border-radius: 11px;
            padding: 7px 11px;
            background: var(--crv-chip-bg);
            border: 1px solid var(--crv-chip-border);
            color: var(--crv-text);
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: space-between;
            gap: 6px;
            width: 100%;
            text-align: left;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .demo-pill:hover,
        .demo-pill.is-selected {
            background: var(--crv-primary-soft);
            border-color: rgba(255, 107, 53, 0.45);
            color: var(--crv-primary);
            transform: translateY(-1px);
        }

        .demo-pill-role {
            font-size: 9.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 2px 6px;
            border-radius: 6px;
            flex-shrink: 0;
        }

        .demo-pill-role.is-admin { background: rgba(239, 68, 68, 0.16); color: #EF4444; }
        .demo-pill-role.is-customer { background: rgba(16, 185, 129, 0.16); color: #10B981; }
        .demo-pill-role.is-courier { background: rgba(6, 182, 212, 0.16); color: #06B6D4; }
        .demo-pill-role.is-restaurant { background: rgba(245, 158, 11, 0.16); color: #F59E0B; }

        /* Security & Flash Alerts */
        .crv-notice-banner {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 11px 14px;
            border-radius: 13px;
            font-size: 12px;
            line-height: 1.5;
            margin-bottom: 16px;
            background: var(--crv-emerald-soft);
            border: 1px solid rgba(16, 185, 129, 0.28);
            color: var(--crv-text);
        }

        .crv-notice-banner i {
            color: var(--crv-emerald);
            margin-top: 2px;
        }

        .alert {
            position: relative;
            display: block;
            width: 100%;
            padding: 11px 15px;
            border-radius: 14px;
            font-weight: 600;
            font-size: 13px;
            line-height: 1.45;
            letter-spacing: -0.1px;
            border: 1px solid transparent;
        }
        .alert-info {
            background: rgba(56, 189, 248, 0.14);
            border-color: rgba(56, 189, 248, 0.32);
            color: var(--crv-text);
        }
        .alert-danger {
            background: rgba(239, 68, 68, 0.15);
            border-color: rgba(239, 68, 68, 0.35);
            color: var(--crv-text);
        }
        .alert-success {
            background: rgba(16, 185, 129, 0.15);
            border-color: rgba(16, 185, 129, 0.35);
            color: var(--crv-text);
        }
        .alert-warning {
            background: rgba(245, 158, 11, 0.15);
            border-color: rgba(245, 158, 11, 0.35);
            color: var(--crv-text);
        }
        .alert-dismissible {
            padding-right: 40px;
        }
        .alert-dismissible .btn-close {
            position: absolute;
            top: 50%;
            right: 12px;
            transform: translateY(-50%);
            width: 24px;
            height: 24px;
            border: none;
            background: transparent;
            color: var(--crv-text-2);
            cursor: pointer;
            opacity: 0.75;
        }
        .alert-dismissible .btn-close:hover {
            opacity: 1;
        }

        .auth-footer-switch {
            margin-top: 18px;
            padding-top: 16px;
            border-top: 1px solid var(--crv-border);
            text-align: center;
            font-size: 13px;
            color: var(--crv-text-2);
        }

        .auth-footer-switch a {
            color: var(--crv-primary);
            font-weight: 800;
            text-decoration: none;
        }

        .auth-footer-switch a:hover {
            text-decoration: underline;
        }

        /* Compact Glass Card (Errors 403 / 404 / 500) */
        .auth-card,
        .error-card {
            background: var(--crv-surface);
            border: 1px solid var(--crv-border);
            border-radius: 28px;
            box-shadow: var(--crv-shadow);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            width: 100%;
            max-width: 520px;
            padding: 40px 36px;
        }

        .error-brand-mini {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 16px;
            font-weight: 900;
            letter-spacing: -0.4px;
            color: var(--crv-text);
            text-decoration: none;
            margin-bottom: 24px;
        }

        .error-brand-mini .mini-icon {
            width: 30px;
            height: 30px;
            border-radius: 9px;
            background: linear-gradient(135deg, #FF6B35, #FA4A0C);
            color: #FFFFFF;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }

        .error-icon {
            width: 78px;
            height: 78px;
            border-radius: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 34px;
            margin-bottom: 18px;
        }

        .error-icon.is-danger {
            background: rgba(239, 68, 68, 0.14);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #EF4444;
            box-shadow: 0 12px 30px rgba(239, 68, 68, 0.22);
        }

        .error-icon.is-warning {
            background: rgba(245, 158, 11, 0.14);
            border: 1px solid rgba(245, 158, 11, 0.3);
            color: #F59E0B;
            box-shadow: 0 12px 30px rgba(245, 158, 11, 0.22);
        }

        .error-code {
            display: inline-block;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            padding: 4px 12px;
            border-radius: 9999px;
            background: var(--crv-surface-2);
            border: 1px solid var(--crv-border);
            color: var(--crv-text-2);
            margin-bottom: 12px;
        }

        .error-title {
            font-size: 26px;
            font-weight: 900;
            letter-spacing: -0.6px;
            color: var(--crv-text);
            margin: 0 0 10px;
        }

        .error-desc {
            font-size: 14px;
            line-height: 1.65;
            color: var(--crv-text-2);
            margin: 0 0 24px;
        }

        .error-path-chip {
            display: inline-block;
            margin-top: 6px;
            padding: 3px 10px;
            border-radius: 8px;
            background: var(--crv-surface-2);
            border: 1px solid var(--crv-border);
            color: var(--crv-primary);
            font-size: 12.5px;
            font-weight: 700;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            word-break: break-all;
        }

        .error-actions {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* Responsive Collapse */
        @media (max-width: 920px) {
            .auth-split {
                grid-template-columns: 1fr;
                max-width: 520px;
            }
            .auth-topbar {
                max-width: 520px;
            }
            .auth-brand-panel {
                display: none;
            }
            .auth-form-panel {
                padding: 30px 24px;
            }
        }

        @media (max-width: 576px) {
            .crv-form-row {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .demo-grid {
                grid-template-columns: 1fr;
            }
            .auth-card,
            .error-card {
                padding: 30px 22px;
            }
        }
    </style>
</head>
<body>
    <div class="auth-topbar <?= $isSplitAuth ? '' : 'is-compact' ?>">
        <a href="<?= $request->url('/') ?>" class="auth-back-pill">
            <i class="fa-solid fa-arrow-left"></i> Ana Sayfa
        </a>
        <button type="button" class="auth-theme-btn" id="auth-theme-toggle" title="Açık / Koyu Tema" aria-label="Temayı Değiştir" onclick="toggleAuthTheme()">
            <i class="fa-solid fa-sun" id="auth-theme-icon"></i>
        </button>
    </div>

    <main class="auth-page">
        <?php if ($isSplitAuth): ?>
            <?= $content ?>
        <?php else: ?>
            <div class="error-card">
                <?php if (!empty($flash)): ?>
                    <div class="alert alert-<?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') ?> alert-dismissible fade show py-2 mb-4" role="alert">
                        <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
                    </div>
                <?php endif; ?>

                <?= $content ?>
            </div>
        <?php endif; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function syncAuthThemeIcon() {
            var icon = document.getElementById('auth-theme-icon');
            if (!icon) return;
            var current = document.documentElement.getAttribute('data-theme') || 'dark';
            icon.className = current === 'light' ? 'fa-solid fa-moon' : 'fa-solid fa-sun';
        }
        function toggleAuthTheme() {
            var current = document.documentElement.getAttribute('data-theme') || 'dark';
            var next = current === 'light' ? 'dark' : 'light';
            document.documentElement.setAttribute('data-theme', next);
            try { localStorage.setItem('cravelo-theme', next); } catch (e) {}
            syncAuthThemeIcon();
        }
        function togglePwdVisibility(inputId, btn) {
            var inp = document.getElementById(inputId);
            if (!inp) return;
            var isPwd = inp.type === 'password';
            inp.type = isPwd ? 'text' : 'password';
            var ic = btn && btn.querySelector('i');
            if (ic) ic.className = isPwd ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
        }
        syncAuthThemeIcon();
    </script>
</body>
</html>
