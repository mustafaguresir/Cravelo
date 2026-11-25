# -*- coding: utf-8 -*-
"""
Cravelo — Aşama 2: Veritabanısız Çalışan Prototip & Yazılım Mimarisi Savunması
21 Slaytlık Akademik Jüri Savunma Sunumu Üreticisi (16:9 Widescreen) — Bireysel Sunum: Mustafa Güreşir

Kullanım:
    python build_full_presentation.py
    python build_full_presentation.py --verify
"""

import io
import os
import sys
import math
import shutil
from pathlib import Path
from PIL import Image
from pptx import Presentation
from pptx.util import Inches, Pt
from pptx.enum.text import PP_ALIGN, MSO_ANCHOR
from pptx.dml.color import RGBColor
from pptx.enum.shapes import MSO_SHAPE

# =============================================================================
# MERKEZİ METRİK & GÖRSEL YAPILANDIRMA (Doğrulanmış 360/360 Test, PHPStan L5 & Canlı UI)
# =============================================================================
TEST_STATS = {
    "total": "360",
    "phpunit_count": "54",
    "phpunit_assertions": "247",
    "phpstan_label": "PHPStan L5: 0 Hata",
    "node_count": "215",
    "node_files": "7",
    "php_count": "32",
    "php_files": "2 Standalone + 8 PHPUnit",
    "python_count": "59",
    "python_files": "3",
    "summary_badge": "360 / 360",
    "detail_line": "PHPUnit (54) • PHP (32) • Python (59) • Node (215) • PHPStan L5 ✅",
}

SCREENSHOT_DIR = Path("tests/screenshots")
SCREENSHOTS = {
    "landing": SCREENSHOT_DIR / "ui_01_landing.png",
    "login": SCREENSHOT_DIR / "ui_02_login.png",
    "customer_dark": SCREENSHOT_DIR / "ui_03_customer_dark.png",
    "customer_light": SCREENSHOT_DIR / "ui_04_customer_light.png",
    "restaurant_kds": SCREENSHOT_DIR / "ui_05_restaurant_kds.png",
    "courier_cockpit": SCREENSHOT_DIR / "ui_06_courier_cockpit.png",
    "admin_center": SCREENSHOT_DIR / "ui_07_admin_center.png",
}

# =============================================================================
# CRAVELO KURUMSAL RENK PALETİ (Obsidian Navy + Coral + Cyan + Emerald)
# =============================================================================
C_BG = RGBColor(0x09, 0x0E, 0x1A)          # Deep Obsidian Navy
C_CARD = RGBColor(0x11, 0x18, 0x27)        # Slate 900 Surface
C_CARD_ALT = RGBColor(0x17, 0x20, 0x33)    # Elevated Surface
C_CARD_HI = RGBColor(0x1E, 0x29, 0x3B)     # Highlight Surface
C_BORDER = RGBColor(0x29, 0x36, 0x4E)      # Subtle Border

C_CORAL = RGBColor(0xFF, 0x6B, 0x35)       # Cravelo Brand Coral
C_CORAL_BG = RGBColor(0x2A, 0x16, 0x12)    # Coral Dark Tint
C_CYAN = RGBColor(0x38, 0xBD, 0xF8)        # Tech Cyan
C_CYAN_BG = RGBColor(0x0E, 0x22, 0x33)     # Cyan Dark Tint
C_EMERALD = RGBColor(0x10, 0xB9, 0x81)     # Emerald Success
C_MINT = RGBColor(0x34, 0xD3, 0x99)        # Bright Mint
C_GREEN_BG = RGBColor(0x0B, 0x25, 0x1F)    # Emerald Dark Tint
C_GOLD = RGBColor(0xFB, 0xBF, 0x24)        # Warm Amber / Gold
C_GOLD_BG = RGBColor(0x28, 0x20, 0x0E)     # Gold Dark Tint
C_PURPLE = RGBColor(0xA7, 0x8B, 0xFA)      # Violet Accent
C_PURPLE_BG = RGBColor(0x1E, 0x17, 0x33)   # Violet Dark Tint
C_RED = RGBColor(0xF8, 0x71, 0x71)         # Error / Alert Red
C_RED_BG = RGBColor(0x2B, 0x12, 0x17)      # Red Dark Tint

C_WHITE = RGBColor(0xF8, 0xFA, 0xFC)       # Primary Crisp White
C_TEXT = RGBColor(0xCB, 0xD5, 0xE1)        # Body Slate 300
C_MUTED = RGBColor(0x94, 0xA3, 0xB8)       # Muted Slate 400

FONT_MAIN = "Segoe UI"
FONT_MONO = "Consolas"
TOTAL_SLIDES = 21

# Safe Layout Constants (16:9 -> 13.333" x 7.5")
SLIDE_W = 13.333
SLIDE_H = 7.500
MARGIN_X = 0.60
CONTENT_W = SLIDE_W - (2 * MARGIN_X)  # 12.133"
CONTENT_TOP = 1.56
CONTENT_BOTTOM = 6.86
CONTENT_H = CONTENT_BOTTOM - CONTENT_TOP  # 5.30"


def set_slide_bg(slide, color=C_BG):
    bg = slide.background
    fill = bg.fill
    fill.solid()
    fill.fore_color.rgb = color


def add_rect(slide, x, y, w, h, fill_color, border_color=None, border_pt=1.0, rounded=False, radius=0.05):
    shape_type = MSO_SHAPE.ROUNDED_RECTANGLE if rounded else MSO_SHAPE.RECTANGLE
    shp = slide.shapes.add_shape(shape_type, Inches(x), Inches(y), Inches(w), Inches(h))
    if rounded:
        try:
            shp.adjustments[0] = radius
        except Exception:
            pass
    shp.fill.solid()
    shp.fill.fore_color.rgb = fill_color
    if border_color is not None:
        shp.line.color.rgb = border_color
        shp.line.width = Pt(border_pt)
    else:
        shp.line.fill.background()
    return shp


def add_pill(slide, x, y, w, h, text, bg_color, text_color, border_color=None, font_size=9.5, bold=True, align=PP_ALIGN.CENTER, wrap=False):
    shp = add_rect(slide, x, y, w, h, bg_color, border_color=border_color, border_pt=1.0, rounded=True, radius=0.45 if not wrap else 0.22)
    tf = shp.text_frame
    tf.word_wrap = wrap
    tf.vertical_anchor = MSO_ANCHOR.MIDDLE
    tf.margin_left = Inches(0.08)
    tf.margin_right = Inches(0.08)
    tf.margin_top = Inches(0.02)
    tf.margin_bottom = Inches(0.02)
    lines = text.split("\n")
    for idx, line in enumerate(lines):
        p = tf.paragraphs[0] if idx == 0 else tf.add_paragraph()
        p.alignment = align
        p.text = line
        p.font.name = FONT_MAIN
        p.font.size = Pt(font_size)
        p.font.bold = bold if idx == 0 else False
        p.font.color.rgb = text_color if idx == 0 else C_WHITE
    return shp


def add_textbox(slide, x, y, w, h, text="", font_size=11, color=C_TEXT, bold=False,
                font_name=FONT_MAIN, align=PP_ALIGN.LEFT, v_anchor=MSO_ANCHOR.TOP):
    tb = slide.shapes.add_textbox(Inches(x), Inches(y), Inches(w), Inches(h))
    tf = tb.text_frame
    tf.word_wrap = True
    tf.vertical_anchor = v_anchor
    tf.margin_left = Inches(0.02)
    tf.margin_right = Inches(0.02)
    tf.margin_top = Inches(0.02)
    tf.margin_bottom = Inches(0.02)
    if text:
        p = tf.paragraphs[0]
        p.alignment = align
        p.text = text
        p.font.name = font_name
        p.font.size = Pt(font_size)
        p.font.bold = bold
        p.font.color.rgb = color
    return tb


def add_para(tf, text, font_size=10.5, color=C_TEXT, bold=False, font_name=FONT_MAIN,
             space_before=0, space_after=4, align=PP_ALIGN.LEFT, first=False):
    p = tf.paragraphs[0] if first else tf.add_paragraph()
    p.alignment = align
    p.space_before = Pt(space_before)
    p.space_after = Pt(space_after)
    p.text = text
    p.font.name = font_name
    p.font.size = Pt(font_size)
    p.font.bold = bold
    p.font.color.rgb = color
    return p


def add_rich_bullet(tf, lead, body, lead_color=C_WHITE, body_color=C_TEXT,
                    font_size=10.5, space_after=5, first=False, bullet="▸ "):
    p = tf.paragraphs[0] if first else tf.add_paragraph()
    p.space_before = Pt(0)
    p.space_after = Pt(space_after)

    r0 = p.add_run()
    r0.text = bullet
    r0.font.name = FONT_MAIN
    r0.font.size = Pt(font_size)
    r0.font.bold = True
    r0.font.color.rgb = lead_color

    if lead:
        r1 = p.add_run()
        r1.text = lead + " "
        r1.font.name = FONT_MAIN
        r1.font.size = Pt(font_size)
        r1.font.bold = True
        r1.font.color.rgb = lead_color

    r2 = p.add_run()
    r2.text = body
    r2.font.name = FONT_MAIN
    r2.font.size = Pt(font_size)
    r2.font.bold = False
    r2.font.color.rgb = body_color
    return p


def add_card(slide, x, y, w, h, bg_color=C_CARD, border_color=C_BORDER, accent_color=None, accent_pos="top", radius=0.04):
    card = add_rect(slide, x, y, w, h, bg_color, border_color=border_color, border_pt=1.0, rounded=True, radius=radius)
    if accent_color is not None:
        if accent_pos == "top":
            add_rect(slide, x + 0.14, y + 0.01, w - 0.28, 0.055, accent_color)
        elif accent_pos == "left":
            add_rect(slide, x + 0.01, y + 0.12, 0.06, h - 0.24, accent_color)
    return card


def add_chrome(slide, slide_num, section_tag, title, subtitle):
    """Standart üst şerit, başlık bloğu, ayırıcı ve sayfa numaralı alt bilgi."""
    set_slide_bg(slide, C_BG)

    # Top dual accent bar
    add_rect(slide, 0.0, 0.0, SLIDE_W * 0.52, 0.055, C_CORAL)
    add_rect(slide, SLIDE_W * 0.52, 0.0, SLIDE_W * 0.48, 0.055, C_CYAN)

    # Eyebrow tag
    add_textbox(slide, MARGIN_X, 0.20, 9.6, 0.26, section_tag.upper(),
                font_size=9.5, color=C_CORAL, bold=True)

    # Brand pill top-right
    add_pill(slide, SLIDE_W - MARGIN_X - 1.95, 0.20, 1.95, 0.28,
             "Cravelo • Aşama 2", C_CARD_ALT, C_CYAN, border_color=C_BORDER, font_size=9.0)

    # Title
    add_textbox(slide, MARGIN_X, 0.47, CONTENT_W, 0.48, title,
                font_size=22, color=C_WHITE, bold=True)

    # Subtitle
    add_textbox(slide, MARGIN_X, 0.96, CONTENT_W, 0.38, subtitle,
                font_size=10.5, color=C_MUTED, bold=False)

    # Header hairline
    add_rect(slide, MARGIN_X, 1.40, CONTENT_W, 0.015, C_BORDER)

    # Footer hairline & content
    add_rect(slide, MARGIN_X, 6.96, CONTENT_W, 0.012, C_BORDER)
    add_textbox(slide, MARGIN_X, 7.03, 10.4, 0.30,
                "Cravelo  •  Aşama 2: Veritabanısız Çalışan Prototip & Yazılım Mimarisi Savunması  |  Mustafa Güreşir",
                font_size=8.5, color=C_MUTED)
    add_pill(slide, SLIDE_W - MARGIN_X - 0.92, 7.02, 0.92, 0.30,
             f"{slide_num:02d} / {TOTAL_SLIDES:02d}", C_CARD_ALT, C_CORAL, border_color=C_BORDER, font_size=9.0)


def set_notes(slide, text):
    notes_slide = slide.notes_slide
    tf = notes_slide.notes_text_frame
    tf.text = text.strip()


def style_table_cell(cell, text, bg_color, text_color=C_TEXT, font_size=9.8,
                     bold=False, align=PP_ALIGN.LEFT, font_name=FONT_MAIN):
    cell.fill.solid()
    cell.fill.fore_color.rgb = bg_color
    cell.vertical_anchor = MSO_ANCHOR.MIDDLE
    cell.margin_left = Inches(0.10)
    cell.margin_right = Inches(0.08)
    cell.margin_top = Inches(0.05)
    cell.margin_bottom = Inches(0.05)
    tf = cell.text_frame
    tf.word_wrap = True
    p = tf.paragraphs[0]
    p.alignment = align
    p.text = text
    p.font.name = font_name
    p.font.size = Pt(font_size)
    p.font.bold = bold
    p.font.color.rgb = text_color


def add_framed_screenshot(slide, img_path: Path, x, y, w, h, fallback_title, accent_color=C_CYAN):
    """
    Görüntüyü en-boy oranını bozmadan (gerekirse çok uzun sayfalarda üst 16:10 görünümü
    kırparak) koyu çerçeve içine temiz biçimde gömer. Dosya henüz yoksa şık yer tutucu gösterir.
    """
    # Outer dark viewport bezel
    add_rect(slide, x, y, w, h, RGBColor(0x06, 0x0A, 0x14), border_color=accent_color, border_pt=1.25, rounded=True, radius=0.03)

    inner_pad = 0.05
    ix, iy = x + inner_pad, y + inner_pad
    iw, ih = w - (2 * inner_pad), h - (2 * inner_pad)

    if img_path.exists() and img_path.stat().st_size > 500:
        with Image.open(img_path) as im:
            im = im.convert("RGB")
            pw, ph = im.size
            target_ratio = iw / ih
            img_ratio = pw / float(ph)

            # Çok uzun (full-page) ekran görüntüsü gelirse üst viewport'u kırp ki bulanık/minik olmasın
            if img_ratio < 1.15:
                crop_h = min(ph, int(round(pw / target_ratio)))
                im = im.crop((0, 0, pw, crop_h))
                pw, ph = im.size
                img_ratio = pw / float(ph)

            buf = io.BytesIO()
            im.save(buf, format="PNG", optimize=True)
            buf.seek(0)

            if img_ratio >= target_ratio:
                draw_w = iw
                draw_h = iw / img_ratio
            else:
                draw_h = ih
                draw_w = ih * img_ratio

            dx = ix + (iw - draw_w) / 2.0
            dy = iy + (ih - draw_h) / 2.0
            slide.shapes.add_picture(buf, Inches(dx), Inches(dy), width=Inches(draw_w), height=Inches(draw_h))
            return True
    else:
        # Graceful fallback placeholder
        add_textbox(slide, ix + 0.15, iy + (ih * 0.28), iw - 0.30, ih * 0.45,
                    f"📷 Canlı Ekran Görüntüsü\n{fallback_title}\n({img_path.as_posix()})",
                    font_size=10, color=C_MUTED, bold=True, align=PP_ALIGN.CENTER, v_anchor=MSO_ANCHOR.MIDDLE)
        return False


def build_presentation():
    prs = Presentation()
    prs.slide_width = Inches(SLIDE_W)
    prs.slide_height = Inches(SLIDE_H)
    blank = prs.slide_layouts[6]

    embedded_images = []

    # =========================================================================
    # SLAYT 1: KAPAK & PROJE KİMLİĞİ
    # =========================================================================
    s1 = prs.slides.add_slide(blank)
    set_slide_bg(s1, C_BG)

    # Subtle geometric accent strips on cover
    add_rect(s1, 0.0, 0.0, SLIDE_W * 0.55, 0.08, C_CORAL)
    add_rect(s1, SLIDE_W * 0.55, 0.0, SLIDE_W * 0.45, 0.08, C_CYAN)
    add_rect(s1, 0.0, SLIDE_H - 0.06, SLIDE_W, 0.06, C_CARD_HI)

    # Top eyebrow pill
    add_pill(s1, MARGIN_X, 0.52, 6.25, 0.36,
             "PHP PROGRAMLAMA — TASARIM VE MİMARİ  •  AŞAMA 2 SAVUNMA SUNUMU",
             C_CORAL_BG, C_CORAL, border_color=C_CORAL, font_size=9.5, align=PP_ALIGN.LEFT)

    add_pill(s1, SLIDE_W - MARGIN_X - 2.35, 0.52, 2.35, 0.36,
             "PROJE #9  •  ZORLUK: 4 / 5",
             C_CYAN_BG, C_CYAN, border_color=C_CYAN, font_size=9.5)

    # Hero Brand + Title
    tb_hero = add_textbox(s1, MARGIN_X, 0.96, CONTENT_W, 1.72)
    tf = tb_hero.text_frame
    add_para(tf, "Cravelo", font_size=42, color=C_CORAL, bold=True, space_after=2, first=True)
    add_para(tf, "Akıllı Yemek Sipariş ve Kurye Takip Sistemi", font_size=23, color=C_WHITE, bold=True, space_after=4)
    add_para(tf, "Aşama 2: Veritabanısız Çalışan Prototip, Katmanlı Saf PHP MVC & Yazılım Mimarisi Savunması",
             font_size=11.5, color=C_TEXT, space_after=0)

    # Architecture summary highlight banner
    add_card(s1, MARGIN_X, 2.74, CONTENT_W, 0.60, bg_color=C_CYAN_BG, border_color=C_CYAN, radius=0.14)
    add_textbox(s1, MARGIN_X + 0.18, 2.78, CONTENT_W - 0.36, 0.52,
                "🚀  Cravelo Aşama 2 Mimarisi: Sıfır SQL (storage/*.json + flock)  •  Tek Giriş Noktalı Saf PHP 8.2+ MVC  •  54 PHPUnit + PHPStan L5 (0 Hata)  •  360/360 Test",
                font_size=9.8, color=C_WHITE, bold=False, v_anchor=MSO_ANCHOR.MIDDLE)

    # Tech stack pills (6 pills in a clean row)
    tech_pills = [
        ("PHP 8.2+ Saf MVC", C_CORAL),
        ("PSR-4 & Front Controller", C_CYAN),
        ("HTTP 303 PRG & Flash", C_GOLD),
        ("flock(LOCK_EX) & .tmp", C_EMERALD),
        ("State & Strategy Pattern", C_PURPLE),
        ("Haversine (R = 6371 km)", C_CYAN),
    ]
    pw_gap = 0.14
    pw = (CONTENT_W - pw_gap * 5) / 6.0
    for idx, (label, col) in enumerate(tech_pills):
        px = MARGIN_X + idx * (pw + pw_gap)
        add_pill(s1, px, 3.48, pw, 0.36, label, C_CARD_ALT, col, border_color=C_BORDER, font_size=9.2)

    # Bottom row: Lead Card (Left) + 3 KPI Stat Cards (Right)
    add_card(s1, MARGIN_X, 4.08, 7.15, 2.52, bg_color=C_CARD, border_color=C_BORDER, accent_color=C_CORAL, accent_pos="left")
    tb_lead = add_textbox(s1, MARGIN_X + 0.26, 4.24, 6.70, 2.22)
    tf = tb_lead.text_frame
    add_para(tf, "BİREYSEL PROJE GELİŞTİRİCİSİ & SUNUM SAHİBİ", font_size=9.5, color=C_CORAL, bold=True, space_after=4, first=True)
    add_para(tf, "Mustafa Güreşir", font_size=21, color=C_WHITE, bold=True, space_after=2)
    add_para(tf, "mustafaguresir@gmail.com  •  github.com/mustafaguresir/Cravelo  •  Bireysel Savunma", font_size=10.0, color=C_CYAN, bold=True, space_after=10)
    add_para(tf, "Kapsam: Tek giriş noktalı saf PHP 8.2 MVC, OrderService + PlatformFeatureService, State & Strategy, Haversine + iş yükü atama, flock(LOCK_EX) JSON depolama, Bcrypt + CSRF + IDOR, 54 PHPUnit + PHPStan L5 (0 hata) ve 4-rol Cravelo UI.",
             font_size=10.2, color=C_TEXT, space_after=0)

    kpis = [
        ("0 SQL", "Saf JSON + flock()", "Veritabanı kullanılmadan\natomik dosya kilitleme", C_GOLD),
        ("7 / 27", "Restoran & Ürün", "10 Kullanıcı • 4 Kurye\n4 Bağımsız Rol Paneli", C_CYAN),
        (TEST_STATS["total"], "Otomatik Test", "54 PHPUnit • PHPStan L5\n%100 Yeşil (0 Hata)", C_EMERALD),
    ]
    kx_start = MARGIN_X + 7.38
    kw = (CONTENT_W - 7.38 - 0.28) / 3.0
    for idx, (big, sub, desc, col) in enumerate(kpis):
        kx = kx_start + idx * (kw + 0.14)
        add_card(s1, kx, 4.08, kw, 2.52, bg_color=C_CARD_ALT, border_color=col, accent_color=col, accent_pos="top")
        tb_k = add_textbox(s1, kx + 0.10, 4.30, kw - 0.20, 2.18)
        tf = tb_k.text_frame
        add_para(tf, big, font_size=24, color=col, bold=True, align=PP_ALIGN.CENTER, space_after=4, first=True)
        add_para(tf, sub, font_size=10.5, color=C_WHITE, bold=True, align=PP_ALIGN.CENTER, space_after=8)
        add_para(tf, desc, font_size=9.5, color=C_MUTED, align=PP_ALIGN.CENTER, space_after=0)

    # Cover footer
    add_textbox(s1, MARGIN_X, 6.92, 10.5, 0.34,
                "T.C. Üniversite Yazılım / Bilgisayar Mühendisliği  •  PHP Programlama — Aşama 2  •  Mustafa Güreşir (15 Dk Bireysel Savunma)",
                font_size=9, color=C_MUTED)
    add_pill(s1, SLIDE_W - MARGIN_X - 0.92, 6.92, 0.92, 0.30,
             f"01 / {TOTAL_SLIDES:02d}", C_CARD_ALT, C_CORAL, border_color=C_BORDER, font_size=9.0)

    set_notes(s1, """[0:00 - 1:30 | Açılış & Proje Kimliği — Mustafa Güreşir]
• Hocam ve değerli arkadaşlar merhabalar. Bugün sizlere Aşama 2 kapsamında geliştirdiğim veritabanısız çalışan prototipim Cravelo'yu ve yazılım mimarisini sunacağım.
• Aşama 1'deki tüm FR/NFR gereksinimleri ve mimari ilkeler birebir korunarak saf PHP 8.2+ üzerinde uçtan uca hayata geçirildi.
• Hiçbir PHP framework'ü veya SQL veritabanı kullanmadan; Front Controller, HTTP 303 PRG, flock(LOCK_EX) atomik JSON depolama, State & Strategy desenleri ve Haversine algoritmasıyla 4/5 zorluk seviyesindeki 9. projeyi tamamladım.""")

    # =========================================================================
    # SLAYT 2: PROJE VİZYONU, 4/5 ZORLUK & RESMİ ŞARTNAME KRİTERLERİ
    # =========================================================================
    s2 = prs.slides.add_slide(blank)
    add_chrome(s2, 2, "1. Proje Vizyonu & Resmi Şartname",
               "Proje Vizyonu, 4/5 Zorluk ve Resmi Şartname Kriterleri",
               "9. Proje (Yemek Sipariş ve Kurye Takip) — Tüm zorunlu özellikler, kapsam sınırları ve kota aşımı.")

    # Quote banner
    add_card(s2, MARGIN_X, CONTENT_TOP, CONTENT_W, 0.58, bg_color=C_CARD_ALT, border_color=C_GOLD, accent_color=C_GOLD, accent_pos="left")
    add_textbox(s2, MARGIN_X + 0.22, CONTENT_TOP + 0.05, CONTENT_W - 0.40, 0.48,
                'Hocanın Felsefesi: "Kod yazmak kolaydır; önemli olan değişime dirençli, katmanları izole, test edilebilir ve SOLID uyumlu bir mimari kurmaktır."',
                font_size=10.5, color=C_WHITE, bold=True, v_anchor=MSO_ANCHOR.MIDDLE)

    grid2 = [
        ("🎯 Resmi Şartname Kapsamı (Proje #9)", C_CYAN, [
            ("Zorluk 4/5:", "İleri seviye durum makinesi ve ağırlıklı kurye optimizasyonu."),
            ("4 Aktör Rolü:", "Müşteri, Restoran (KDS), Kurye ve Yönetici için 4 izole panel."),
            ("Canlı Senkron:", "Durum değişince F5 gerektirmeyen AJAX reaktif güncelleme."),
            ("Raporlama:", "Restoran günlük satış/ciro özeti ve sipariş geçmişi."),
        ]),
        ("🏪 Asgari Varlık Kotası Aşımı", C_EMERALD, [
            ("Şartname Kotası:", "En az 3 restoran ve toplam 10 ürün zorunluluğu."),
            ("Cravelo Gerçekleşen:", "7 restoran ve 27 menü ürünü (JSON seed ve SPA 1:1 hizalı)."),
            ("Kurye Filosu:", "4 kurye (Ali, Veli, Can, Mehmet) ile iş yükü senaryosu."),
            ("Kullanıcı Havuzu:", "Bcrypt korumalı 10 tohumlanmış rol hesabı (bin/seed.php)."),
        ]),
        ("⚠️ Resmi Kapsam Sınırları (Constraints)", C_GOLD, [
            ("Dış POS Yok:", "Banka gateway yerine güvenli simüle checkout & Gel-Al (%20)."),
            ("Canlı GPS Yok:", "Kadıköy Moda, Rıhtım, Fenerbahçe sabit koordinatları kullanılır."),
            ("Harita vs Kapsam:", "Arayüz görseli ücretli API/GPS içermez; tüm hesap sabit Haversine."),
            ("Sınır Bilinci:", "Canlı GPS isteği şartname kapsam sınırı gereği reddedildi."),
        ]),
        ("💾 Aşama 2 Veritabanısız Çalışma Prensibi", C_CORAL, [
            ("Sıfır SQL:", "Aşama 2 gereği MySQL, PostgreSQL veya SQLite kullanılmaz."),
            ("JSON Deposu:", "Veriler storage/*.json içinde 7 varlık modeline uygun saklanır."),
            ("Eşzamanlılık:", "JsonStorageHelper içinde flock(LOCK_EX) + .tmp rename()."),
            ("Aşama 3 Hazırlık:", "Repository arayüzleriyle MySQL geçişinde 0 iş kodu değişimi."),
        ]),
    ]
    cw2 = (CONTENT_W - 0.28) / 2.0
    ch2 = 2.20
    for idx, (ctitle, ccol, bullets) in enumerate(grid2):
        col = idx % 2
        row = idx // 2
        cx = MARGIN_X + col * (cw2 + 0.28)
        cy = CONTENT_TOP + 0.72 + row * (ch2 + 0.18)
        add_card(s2, cx, cy, cw2, ch2, bg_color=C_CARD, border_color=C_BORDER, accent_color=ccol, accent_pos="top")
        tb = add_textbox(s2, cx + 0.18, cy + 0.14, cw2 - 0.36, ch2 - 0.22)
        tf = tb.text_frame
        add_para(tf, ctitle, font_size=12.0, color=ccol, bold=True, space_after=5, first=True)
        for lead, body in bullets:
            add_rich_bullet(tf, lead, body, lead_color=C_WHITE, body_color=C_TEXT, font_size=9.8, space_after=3)

    set_notes(s2, """[1:30 - 3:00 | Proje Vizyonu & Şartname Uyumu]
• Resmi proje listesinde 9 numaralı ve 4/5 zorluktaki Yemek Sipariş ve Kurye Takip sistemini geliştiriyoruz.
• Asgari kota 3 restoran ve 10 ürün iken bizde 7 restoran ve 27'den fazla menü ürünü, 4 kurye ve 10 kullanıcı mevcut.
• Kapsam sınırlarına tam uyduk: dış banka POS'u veya ücretli GPS servisi yok; sabit İstanbul Kadıköy mahalle koordinatları ve Haversine formülü kullanılıyor.
• En kritik Aşama 2 şartı: veritabanı kesinlikle yok! Tüm veriler storage/*.json içinde flock() ve atomik rename() ile güvenle yönetiliyor.""")

    # =========================================================================
    # SLAYT 3: KATMANLI MİMARİ ŞEMASI & KATI KATMAN KURALLARI
    # =========================================================================
    s3 = prs.slides.add_slide(blank)
    add_chrome(s3, 3, "2. Yazılım Mimarisi & Separation of Concerns",
               "Katmanlı Mimari Şeması ve Katı Katman Kuralları",
               "Her katman yalnızca doğrudan altındaki katmanla konuşur; katman atlama ve SQL/dosya sızıntısı yasaktır.")

    layers = [
        ("1. Front Controller\n& Router", "public/index.php\nsrc/Core/Router.php", C_CYAN, [
            "Tek giriş noktasıdır.",
            "URL ve HTTP metodunu eşler.",
            "YASAK: İş kuralı / I/O içermez.",
        ]),
        ("2. Controller\nKatmanı", "OrderController\nAuth / ApiController", C_GOLD, [
            "İsteği ve RBAC yetkisini doğrular.",
            "Servisi çağırır, PRG/JSON döner.",
            "YASAK: SQL ve puan hesabı yapmaz.",
        ]),
        ("3. Service\nKatmanı", "OrderService\nStateMachine & Courier", C_EMERALD, [
            "Sipariş, State & Haversine burada.",
            "Controller→Repo atlamasını önler.",
            "YASAK: $_POST/$_GET & HTML bilmez.",
        ]),
        ("4. Repository\nKatmanı", "*RepositoryInterface\nJson*Repository", C_PURPLE, [
            "Veri erişimini soyutlar (DIP).",
            "Arayüz sözleşmesini uygular.",
            "YASAK: İş kuralı barındırmaz.",
        ]),
        ("5. Model &\nJSON Storage", "Order, User, Courier\nJsonStorageHelper", C_CORAL, [
            "Saf veri varlıkları (Entities).",
            "flock(LOCK_EX) + .tmp rename().",
            "YASAK: İş veya HTTP akışı bilmez.",
        ]),
    ]
    lw = 2.18
    arrow_w = 0.308
    ly = CONTENT_TOP
    lh = 2.86
    for i, (ltitle, lfile, lcol, lpts) in enumerate(layers):
        lx = MARGIN_X + i * (lw + arrow_w)
        add_card(s3, lx, ly, lw, lh, bg_color=C_CARD, border_color=lcol, accent_color=lcol, accent_pos="top")
        tb = add_textbox(s3, lx + 0.13, ly + 0.16, lw - 0.26, lh - 0.26)
        tf = tb.text_frame
        add_para(tf, ltitle, font_size=11.5, color=lcol, bold=True, space_after=4, first=True)
        add_para(tf, lfile, font_size=8.8, color=C_GOLD, font_name=FONT_MONO, space_after=8)
        for pt in lpts:
            is_ban = pt.startswith("YASAK:")
            add_para(tf, ("⛔ " if is_ban else "▸ ") + pt, font_size=9.6,
                     color=C_RED if is_ban else C_TEXT, bold=is_ban, space_after=4)
        if i < len(layers) - 1:
            add_textbox(s3, lx + lw + 0.02, ly + 1.15, arrow_w - 0.04, 0.45, "➜",
                        font_size=18, color=C_MUTED, bold=True, align=PP_ALIGN.CENTER, v_anchor=MSO_ANCHOR.MIDDLE)

    # Middle violation warning strip
    add_card(s3, MARGIN_X, 4.60, CONTENT_W, 0.72, bg_color=C_RED_BG, border_color=C_RED, radius=0.10)
    add_textbox(s3, MARGIN_X + 0.20, 4.68, CONTENT_W - 0.40, 0.56,
                "⛔  ENGELLENEN 2 MİMARİ İHLAL:  (1) Katman Atlama Yasaktır: Controller doğrudan Repository veya storage/*.json dosyasına dokunamaz, araya mutlaka Service girer.   |   (2) Sızıntı Yasaktır: Controller/Service içinde SQL veya ham file_put_contents() bulunmaz.",
                font_size=10.2, color=C_WHITE, bold=False, v_anchor=MSO_ANCHOR.MIDDLE)

    # Bottom Professor Control Question Callout
    add_card(s3, MARGIN_X, 5.48, CONTENT_W, 1.34, bg_color=C_CARD_HI, border_color=C_CORAL, accent_color=C_CORAL, accent_pos="left")
    tb_q = add_textbox(s3, MARGIN_X + 0.24, 5.58, CONTENT_W - 0.44, 1.16)
    tf = tb_q.text_frame
    add_para(tf, "⭐  HOCANIN ZORUNLU KONTROL SORUSU:  \"Veritabanını JSON'dan MySQL veya PostgreSQL'e geçirirsek hangi dosyalar değişir?\"",
             font_size=11.5, color=C_GOLD, bold=True, space_after=6, first=True)
    add_para(tf, "👉  CEVAP:  Yalnızca src/Repository/ altındaki somut sınıflar (JsonOrderRepository ➔ MySqlOrderRepository vb.) değişir. Controller, Service, State Machine ve Model sınıfları somut sınıflara değil RepositoryInterface arayüzlerine bağımlı olduğu için TEK BİR SATIR İŞ KODU BİLE DEĞİŞMEZ!",
             font_size=11.0, color=C_WHITE, bold=False, space_after=0)

    set_notes(s3, """[Katmanlı Mimari Savunması]
• Bu slayt hocamızın en çok puan kırdığı katman kurallarını özetler.
• İstek public/index.php Front Controller'dan girer, Router üzerinden ilgili Controller'a ulaşır. Controller asla SQL yazmaz ve kurye puanı hesaplamaz.
• İş mantığı tamamen Service katmanındadır; Service ise $_POST veya HTML bilmez.
• Kritik kontrol sorusu: MySQL'e geçersek hangi dosyalar değişir? Yalnızca src/Repository/ altındaki somut sınıflar! Controller, Service ve Model arayüzlere bağımlı olduğu için tek satır iş kodu değişmez.""")

    # =========================================================================
    # SLAYT 4: SINIF HİYERARŞİSİ (12+ SINIF & ARAYÜZ) & SOLID PRENSİPLERİ
    # =========================================================================
    s4 = prs.slides.add_slide(blank)
    add_chrome(s4, 4, "3. Nesne Yönelimli Tasarım (OOP & SOLID)",
               "Sınıf Hiyerarşisi (25+ Sınıf & Arayüz) ve SOLID Prensipleri",
               "Şartnamenin 'en az 12 sınıf' koşulu 25+ sınıf/arayüz ve somut SOLID eşleştirmesiyle aşılmıştır.")

    # Left: Class Hierarchy Card
    cw4 = 5.92
    add_card(s4, MARGIN_X, CONTENT_TOP, cw4, CONTENT_H, bg_color=C_CARD, border_color=C_BORDER, accent_color=C_CYAN, accent_pos="top")
    tb_cl = add_textbox(s4, MARGIN_X + 0.20, CONTENT_TOP + 0.16, cw4 - 0.40, CONTENT_H - 0.28)
    tf = tb_cl.text_frame
    add_para(tf, "🏛️  src/ Altındaki Sınıf ve Arayüz Mimarisi", font_size=13, color=C_CYAN, bold=True, space_after=8, first=True)

    hierarchy = [
        ("Core & Controller (10):", "Controller, Request, Response, Router, Session, View; Order, Auth, Api, HomeController"),
        ("Enum & Exception (5):", "OrderStatus, UserRole, CourierStatus; InvalidOrderTransitionException, NoAvailableCourierException"),
        ("Model / Entities (6):", "Order, OrderItem, User, Restaurant, MenuItem, Courier"),
        ("State Deseni (7):", "OrderStateInterface ⇐ Received, Preparing, Ready, OnWay, Delivered, CancelledState"),
        ("Strategy & Servis (7):", "OrderService, OrderStateMachineService, CourierAssignmentService, Haversine, AuthService, ScoreBasedStrategy"),
        ("Repository & I/O (13):", "4× *RepositoryInterface ⇐ 4× Json*Repository + 4× InMemory*Repository (Test) + JsonStorageHelper"),
    ]
    for lead, body in hierarchy:
        add_rich_bullet(tf, lead, body, lead_color=C_GOLD, body_color=C_TEXT, font_size=10.2, space_after=7)

    # Right: SOLID 5 stacked rows
    rx4 = MARGIN_X + cw4 + 0.29
    solid_rows = [
        ("S", "Single Responsibility", "HaversineDistanceService yalnız küresel mesafe, JsonStorageHelper yalnız atomik dosya I/O sorumluluğu taşır.", C_CORAL),
        ("O", "Open / Closed Principle", "Sisteme yeni sipariş durumu (örn. IadeEdildiState) mevcut kodlara dokunmadan OrderStateInterface ile eklenir.", C_CYAN),
        ("L", "Liskov Substitution", "6 somut State sınıfı ve InMemory*/Json* depolar arayüzlerinin yerine sıfır yan etkiyle geçer.", C_EMERALD),
        ("I", "Interface Segregation", "OrderRepositoryInterface, UserRepositoryInterface ve OrderStateInterface gereksiz metot barındırmayacak biçimde ayrılmıştır.", C_GOLD),
        ("D", "Dependency Inversion", "Controller ve Servisler somut Json*Repository'ye değil, RepositoryInterface soyutlamalarına bağımlıdır.", C_PURPLE),
    ]
    sh_gap = 0.12
    sh_h = (CONTENT_H - sh_gap * 4) / 5.0  # 0.964" each
    for idx, (letter, sname, sdesc, scol) in enumerate(solid_rows):
        sy = CONTENT_TOP + idx * (sh_h + sh_gap)
        add_card(s4, rx4, sy, cw4, sh_h, bg_color=C_CARD_ALT, border_color=C_BORDER, radius=0.08)
        # Letter badge
        add_pill(s4, rx4 + 0.14, sy + 0.16, 0.62, sh_h - 0.32, letter, C_BG, scol, border_color=scol, font_size=18, bold=True)
        tb_s = add_textbox(s4, rx4 + 0.88, sy + 0.08, cw4 - 1.04, sh_h - 0.14)
        tf = tb_s.text_frame
        add_para(tf, sname, font_size=11.0, color=scol, bold=True, space_after=2, first=True)
        add_para(tf, sdesc, font_size=9.8, color=C_TEXT, space_after=0)

    set_notes(s4, """[Sınıf Hiyerarşisi & SOLID Prensipleri]
• Şartname en az 12 sınıf istiyordu; projemizde PSR-4 standartlarında 40'tan fazla dosya ve 25'in üzerinde çekirdek sınıf ile arayüz yer alıyor.
• Her bir SOLID harfi teorik değil, doğrudan src/ klasöründeki bir sınıfla karşılık buluyor:
  - S: HaversineDistanceService sadece mesafe hesaplar.
  - O: Yeni bir durum eklemek mevcut state sınıflarını bozmaz.
  - L: Testlerde kullandığımız InMemoryOrderRepository, JsonOrderRepository yerine birebir geçer.
  - I: Arayüzler rol bazlı bölünmüştür.
  - D: Üst katmanlar yalnızca Interface'lere bağımlıdır.""")

    # =========================================================================
    # SLAYT 5: SIRALAMA DİYAGRAMI (SEQUENCE) & 2 ZORUNLU HATA DALI
    # =========================================================================
    s5 = prs.slides.add_slide(blank)
    add_chrome(s5, 5, "4. Çekirdek Akış & İstisna Yönetimi",
               "Sıralama Diyagramı (Sequence) ve 2 Zorunlu Hata Dalı",
               "Restoran Şefi 'Hazır' butonuna bastığında işleyen mutlu yol ve şartnamedeki zorunlu alt/else hata dalları.")

    # Top Visual Sequence Diagram Card
    add_card(s5, MARGIN_X, CONTENT_TOP, CONTENT_W, 2.74, bg_color=C_CARD, border_color=C_CYAN, accent_color=C_CYAN, accent_pos="top")
    add_textbox(s5, MARGIN_X + 0.20, CONTENT_TOP + 0.14, CONTENT_W - 0.40, 0.30,
                "⚡ MUTLU YOL (Happy Path) — POST /api?action=change_status (Sipariş #1042 ➔ HAZIR) Sıralı Akış",
                font_size=12, color=C_CYAN, bold=True)

    # 5 Lifeline Participant Header Boxes
    actors = [
        ("Restoran (KDS)", C_CORAL),
        ("ApiController", C_GOLD),
        ("OrderService + State", C_EMERALD),
        ("CourierAssignment", C_CYAN),
        ("Json*Repository", C_PURPLE),
    ]
    aw = 2.14
    agap = (CONTENT_W - 0.40 - aw * 5) / 4.0
    for i, (aname, acol) in enumerate(actors):
        ax = MARGIN_X + 0.20 + i * (aw + agap)
        add_pill(s5, ax, CONTENT_TOP + 0.52, aw, 0.36, aname, C_CARD_HI, acol, border_color=acol, font_size=9.5)
        if i < 4:
            add_textbox(s5, ax + aw + 0.01, CONTENT_TOP + 0.50, agap - 0.02, 0.36, "→",
                        font_size=14, color=C_MUTED, bold=True, align=PP_ALIGN.CENTER, v_anchor=MSO_ANCHOR.MIDDLE)

    # 5 Numbered Sequence Steps inside card
    tb_seq = add_textbox(s5, MARGIN_X + 0.22, CONTENT_TOP + 0.98, CONTENT_W - 0.44, 1.66)
    tf = tb_seq.text_frame
    seq_lines = [
        ("1. Tek Giriş Noktası:", "Restoran KDS POST /api?action=change_status gönderir → public/index.php → ApiController oturum rolünü alır."),
        ("2. Servis & Durum Denetimi:", "OrderService::transitionStatus() → OrderStateMachineService::transition() → PreparingState::proceed() doğrular."),
        ("3. Aday Kurye Sorgusu:", "CourierAssignmentService::assignBestCourier(order) → JsonCourierRepository::findAvailableCouriers() çağırır."),
        ("4. Akıllı Puanlama:", "ScoreBasedAssignmentStrategy her aday için Puan = Haversine(km) + 2 × Aktif hesaplar; en düşük puanlıyı seçer."),
        ("5. Atomik Kayıt & Yanıt:", "updateStatusConditional() + incrementActiveOrders() flock(LOCK_EX) ile diske yazılır → HTTP 200 OK (JSON)."),
    ]
    for idx, (lead, body) in enumerate(seq_lines):
        add_rich_bullet(tf, lead, body, lead_color=C_WHITE, body_color=C_TEXT, font_size=10.2, space_after=4, first=(idx == 0), bullet="▸ ")

    # Bottom 2 Mandatory Error Branches (alt / else)
    ew = (CONTENT_W - 0.28) / 2.0
    ey = CONTENT_TOP + 2.94
    eh = 2.36

    # Error 1
    add_card(s5, MARGIN_X, ey, ew, eh, bg_color=C_RED_BG, border_color=C_RED, accent_color=C_RED, accent_pos="left")
    tb_e1 = add_textbox(s5, MARGIN_X + 0.24, ey + 0.16, ew - 0.42, eh - 0.28)
    tf = tb_e1.text_frame
    add_para(tf, "⚠️  ZORUNLU HATA DALI 1  [alt: Geçersiz Durum Geçişi]", font_size=12, color=C_RED, bold=True, space_after=6, first=True)
    add_rich_bullet(tf, "Senaryo:", "Sipariş 'alindi' iken doğrudan 'hazir' yapılmak veya 'yolda' iken 'hazirlaniyor'a döndürülmek istenirse.", lead_color=C_WHITE, body_color=C_TEXT, font_size=10.2, space_after=5)
    add_rich_bullet(tf, "Fırlatılan İstisna:", "InvalidOrderTransitionException / DomainException", lead_color=C_GOLD, body_color=C_WHITE, font_size=10.2, space_after=5)
    add_rich_bullet(tf, "HTTP Yanıtı & Koruma:", "HTTP 400 Bad Request döner; işlem anında kesilir, orders.json dosyasına geçersiz kayıt asla yazılmaz.", lead_color=C_WHITE, body_color=C_TEXT, font_size=10.2, space_after=0)

    # Error 2
    add_card(s5, MARGIN_X + ew + 0.28, ey, ew, eh, bg_color=C_GOLD_BG, border_color=C_GOLD, accent_color=C_GOLD, accent_pos="left")
    tb_e2 = add_textbox(s5, MARGIN_X + ew + 0.52, ey + 0.16, ew - 0.42, eh - 0.28)
    tf = tb_e2.text_frame
    add_para(tf, "⚠️  ZORUNLU HATA DALI 2  [else: Müsait Kurye Yok]", font_size=12, color=C_GOLD, bold=True, space_after=6, first=True)
    add_rich_bullet(tf, "Senaryo:", "Sipariş mutfakta 'hazir' olduğunda bölgedeki tüm kuryeler meşgul veya çevrimdışı ise.", lead_color=C_WHITE, body_color=C_TEXT, font_size=10.2, space_after=5)
    add_rich_bullet(tf, "Fırlatılan İstisna:", "NoAvailableCourierException (Boş aday listesi koruması)", lead_color=C_GOLD, body_color=C_WHITE, font_size=10.2, space_after=5)
    add_rich_bullet(tf, "HTTP Yanıtı & Kurtarma:", "HTTP 409 Conflict döner; sipariş çökmeden 'hazir' bekleme kuyruğunda tutulur, ilk teslimatta boşa çıkan kuryeye atanır.", lead_color=C_WHITE, body_color=C_TEXT, font_size=10.2, space_after=0)

    set_notes(s5, """[Sıralama Diyagramı & 2 Zorunlu Hata Dalı]
• Hocamızın en çok dikkat ettiği noktalardan biri Sequence diyagramında sadece mutlu yolun çizilip hata dallarının unutulmasıdır.
• Biz çekirdek akışımızda (Restoran 'Hazır' dediğinde) hem 5 adımlı mutlu yolu hem de 2 zorunlu alt/else hata dalını eksiksiz kodladık:
  1. Geçersiz durum geçişinde InvalidOrderTransitionException / DomainException fırlatılır ve HTTP 400 döner.
  2. Müsait kurye yoksa NoAvailableCourierException fırlatılır, HTTP 409 ile sipariş hazır kuyruğunda güvenle bekletilir.""")

    # =========================================================================
    # SLAYT 6: ÇEKİRDEK PROBLEM 1 — AKILLI KURYE ATAMA ALGORİTMASI
    # =========================================================================
    s6 = prs.slides.add_slide(blank)
    add_chrome(s6, 6, "5. Çekirdek Problem 1 — Optimizasyon",
               "Akıllı Kurye Atama Algoritması (Haversine + İş Yükü Dengesi)",
               "Yalnız en yakındaki kuryeyi değil, mesafe ve anlık iş yükünü dengeleyen en düşük puanlı kuryeyi seçer.")

    # Top Formula Banner
    add_card(s6, MARGIN_X, CONTENT_TOP, CONTENT_W, 1.54, bg_color=C_CARD_HI, border_color=C_CYAN, accent_color=C_CYAN, accent_pos="top")
    tb_f = add_textbox(s6, MARGIN_X + 0.22, CONTENT_TOP + 0.14, CONTENT_W - 0.44, 1.30)
    tf = tb_f.text_frame
    add_para(tf, "📐  Ağırlıklı Puanlama Formülü ve Haversine Küresel Mesafesi (ScoreBasedAssignmentStrategy.php)",
             font_size=11.5, color=C_CYAN, bold=True, space_after=5, first=True)
    add_para(tf, "Atama Puanı  =  Haversine Mesafesi (km)  +  2.0 × Aktif Sipariş Sayısı      [En Düşük Puan Kazanır]",
             font_size=13.5, color=C_GOLD, bold=True, font_name=FONT_MONO, space_after=5)
    add_para(tf, "Haversine (R = 6371 km):   d = 2R · arcsin( √( sin²(Δφ/2) + cos(φ₁)·cos(φ₂)·sin²(Δλ/2) ) )   |   Eşitlikte: Aktif görevi az olan seçilir.",
             font_size=10.2, color=C_TEXT, font_name=FONT_MONO, space_after=0)

    # Bottom Side-by-Side Proof Cards with VS pill
    cw6 = 5.65
    cy6 = CONTENT_TOP + 1.76
    ch6 = 3.54

    # Left: Ali (Eliminated)
    add_card(s6, MARGIN_X, cy6, cw6, ch6, bg_color=C_CARD, border_color=C_RED, accent_color=C_RED, accent_pos="top")
    tb_ali = add_textbox(s6, MARGIN_X + 0.22, cy6 + 0.18, cw6 - 0.44, ch6 - 0.94)
    tf = tb_ali.text_frame
    add_para(tf, "🛵  Kurye Ali Kaya — Yakın Ama Meşgul", font_size=14, color=C_RED, bold=True, space_after=8, first=True)
    add_rich_bullet(tf, "Konum:", "Kadıköy Moda / Rıhtım (Restorana çok yakın)", lead_color=C_WHITE, body_color=C_TEXT, font_size=10.8, space_after=6)
    add_rich_bullet(tf, "Haversine Mesafesi:", "0.71 km", lead_color=C_WHITE, body_color=C_CYAN, font_size=10.8, space_after=6)
    add_rich_bullet(tf, "Aktif İş Yükü:", "2 Aktif Sipariş  ➔  Ceza: +(2 × 2) = +4.00", lead_color=C_WHITE, body_color=C_RED, font_size=10.8, space_after=6)
    add_rich_bullet(tf, "Hesaplama:", "0.71 + (2.0 × 2) = 4.71 PUAN", lead_color=C_GOLD, body_color=C_WHITE, font_size=11.5, space_after=0)
    add_pill(s6, MARGIN_X + 0.22, cy6 + ch6 - 0.62, cw6 - 0.44, 0.44,
             "❌  ELENDİ — Elinde 2 paket olduğundan siparişi geciktirir!", C_RED_BG, C_RED, border_color=C_RED, font_size=10.0)

    # Center VS Pill
    vs_x = MARGIN_X + cw6 + 0.16
    add_pill(s6, vs_x, cy6 + (ch6 / 2.0) - 0.26, 0.51, 0.52, "VS", C_CARD_HI, C_GOLD, border_color=C_GOLD, font_size=13, bold=True)

    # Right: Veli (Winner)
    vx = MARGIN_X + cw6 + 0.83
    add_card(s6, vx, cy6, cw6, ch6, bg_color=C_CARD, border_color=C_EMERALD, accent_color=C_EMERALD, accent_pos="top")
    tb_veli = add_textbox(s6, vx + 0.22, cy6 + 0.18, cw6 - 0.44, ch6 - 0.94)
    tf = tb_veli.text_frame
    add_para(tf, "🛵  Kurye Veli Demir — Uzak Ama Boşta", font_size=14, color=C_MINT, bold=True, space_after=8, first=True)
    add_rich_bullet(tf, "Konum:", "Fenerbahçe (Ali'ye göre %84 daha uzakta)", lead_color=C_WHITE, body_color=C_TEXT, font_size=10.8, space_after=6)
    add_rich_bullet(tf, "Haversine Mesafesi:", "1.31 km", lead_color=C_WHITE, body_color=C_CYAN, font_size=10.8, space_after=6)
    add_rich_bullet(tf, "Aktif İş Yükü:", "0 Aktif Sipariş (Boşta)  ➔  Ceza: +(2 × 0) = 0.00", lead_color=C_WHITE, body_color=C_MINT, font_size=10.8, space_after=6)
    add_rich_bullet(tf, "Hesaplama:", "1.31 + (2.0 × 0) = 1.31 PUAN", lead_color=C_GOLD, body_color=C_WHITE, font_size=11.5, space_after=0)
    add_pill(s6, vx + 0.22, cy6 + ch6 - 0.62, cw6 - 0.44, 0.44,
             "🏆  KAZANDI & ATANDI!  (1.31 < 4.71 — Hemen Yola Çıkar)", C_GREEN_BG, C_MINT, border_color=C_EMERALD, font_size=10.0)

    set_notes(s6, """[Çekirdek Problem 1 — Akıllı Kurye Atama]
• Klasik sistemler sadece en yakındaki kuryeyi seçer; oysa yakın kuryenin elinde 2 paket varsa yemek soğur.
• Biz Haversine küresel mesafesine (R=6371 km) her aktif sipariş için +2 km sanal ceza ekliyoruz: Puan = Mesafe + 2 × Aktif.
• Matematiksel ispatımız: Kurye Ali 0.71 km ile çok yakın ama 2 paketi olduğu için puanı 4.71 çıkıyor. Kurye Veli 1.31 km uzakta ama boşta olduğu için puanı 1.31 çıkıyor.
• Sistem 1.31 < 4.71 olduğu için Kurye Veli'yi seçer. Hem filo adil dengelenir hem müşteri yemeğini sıcak alır.""")

    # =========================================================================
    # SLAYT 7: ÇEKİRDEK PROBLEM 2 — SİPARİŞ DURUM MAKİNESİ (STATE PATTERN)
    # =========================================================================
    s7 = prs.slides.add_slide(blank)
    add_chrome(s7, 7, "6. Çekirdek Problem 2 — Durum Makinesi",
               "Sipariş Durum Makinesi (State Pattern & PHP 8.1 Enum)",
               "Tek yönlü, katı kurallı sipariş yaşam döngüsü: her durum kendi geçiş ve iptal kuralını nesne düzeyinde korur.")

    # Top State Flow Diagram Card
    add_card(s7, MARGIN_X, CONTENT_TOP, CONTENT_W, 1.98, bg_color=C_CARD_ALT, border_color=C_GOLD, accent_color=C_GOLD, accent_pos="top")
    add_textbox(s7, MARGIN_X + 0.20, CONTENT_TOP + 0.14, 7.6, 0.28,
                "🔄  App\\Enum\\OrderStatus (PHP 8.1 Backed Enum)  &  OrderStateInterface Geçiş Çizgesi",
                font_size=11.5, color=C_GOLD, bold=True)
    add_pill(s7, SLIDE_W - MARGIN_X - 2.95, CONTENT_TOP + 0.12, 2.75, 0.30,
             "6 Somut State Sınıfı (match())", C_PURPLE_BG, C_PURPLE, border_color=C_PURPLE, font_size=9.2)

    stages = [
        ("1. ALINDI", "ReceivedState", C_CYAN),
        ("2. HAZIRLANIYOR", "PreparingState", C_GOLD),
        ("3. HAZIR", "ReadyState", C_CORAL),
        ("4. YOLDA", "OnWayState", C_PURPLE),
        ("5. TESLİM EDİLDİ", "DeliveredState", C_EMERALD),
    ]
    sw = 2.08
    sgap = (CONTENT_W - 0.40 - sw * 5) / 4.0
    sy7 = CONTENT_TOP + 0.56
    for i, (st_lbl, st_cls, st_col) in enumerate(stages):
        sx = MARGIN_X + 0.20 + i * (sw + sgap)
        add_card(s7, sx, sy7, sw, 0.72, bg_color=C_BG, border_color=st_col, radius=0.14)
        tb_st = add_textbox(s7, sx + 0.06, sy7 + 0.08, sw - 0.12, 0.56)
        tf = tb_st.text_frame
        add_para(tf, st_lbl, font_size=10.5, color=st_col, bold=True, align=PP_ALIGN.CENTER, space_after=1, first=True)
        add_para(tf, st_cls, font_size=8.5, color=C_MUTED, font_name=FONT_MONO, align=PP_ALIGN.CENTER, space_after=0)
        if i < 4:
            add_textbox(s7, sx + sw + 0.01, sy7 + 0.16, sgap - 0.02, 0.38, "➜",
                        font_size=14, color=C_WHITE, bold=True, align=PP_ALIGN.CENTER, v_anchor=MSO_ANCHOR.MIDDLE)

    # Secondary notes below stepper
    add_pill(s7, MARGIN_X + 0.20, CONTENT_TOP + 1.44, 4.45, 0.36,
             "↳  İPTAL: Yalnızca 1. ALINDI aşamasında (CancelledState)", C_GREEN_BG, C_MINT, border_color=C_EMERALD, font_size=8.8)
    add_pill(s7, MARGIN_X + 4.82, CONTENT_TOP + 1.44, 3.85, 0.36,
             "⚡  3. HAZIR aşamasında otomatik kurye atama tetiklenir", C_CYAN_BG, C_CYAN, border_color=C_CYAN, font_size=8.8)
    add_pill(s7, MARGIN_X + 8.84, CONTENT_TOP + 1.44, 3.09, 0.36,
             "🚫  2–5 arası geriye dönüş & iptal KİLİTLİ", C_RED_BG, C_RED, border_color=C_RED, font_size=8.8)

    # Bottom 3 Mandatory Rules Cards
    rules7 = [
        ("🛑 KURAL 1: Pişen Yemek İptal Edilemez", "PreparingState::canCancel() === false", C_CORAL, [
            ("Tek İptal Penceresi:", "Müşteri yalnızca 'alindi' aşamasındayken iptal edebilir."),
            ("Mutfak Koruması:", "Restoran 'hazirlaniyor' dediği an emek ve malzeme zararı önlenir."),
            ("Çift Kilit:", "Arayüzde iptal butonu kilitlenir; API zorlaması DomainException (HTTP 400) döner."),
        ]),
        ("🔙 KURAL 2: Yoldan Mutfağa Dönüş Yasak", "OnWayState::proceed()", C_GOLD, [
            ("Tek Yönlü Otomat:", "Kurye paketi teslim alıp 'yolda' yaptığında mutfak süreci kapanır."),
            ("Geri Dönüş Engeli:", "'yolda' ➔ 'hazirlaniyor' veya 'alindi' istekleri kesinlikle reddedilir."),
            ("Denetim İzi:", "Geçersiz geçiş denemeleri sunucu yanıtıyla engellenip kayıt altına alınır."),
        ]),
        ("⏭️ KURAL 3: Sırasız Atlama Yasaktır", "ReceivedState::proceed()", C_EMERALD, [
            ("Sıralı İlerleme:", "'alindi' durumundan doğrudan 'hazir' veya 'yolda'ya atlanamaz."),
            ("Kapsülleme:", "Her somut State sınıfı yalnızca izin verilen tek bir sonraki duruma delege eder."),
            ("Bütünlük:", "Mutfakta pişmeden veya kurye atanmadan teslim edilmiş sipariş oluşamaz."),
        ]),
    ]
    rw7 = (CONTENT_W - 0.36) / 3.0
    ry7 = CONTENT_TOP + 2.18
    rh7 = 3.12
    for idx, (rtitle, rcode, rcol, rpts) in enumerate(rules7):
        rx = MARGIN_X + idx * (rw7 + 0.18)
        add_card(s7, rx, ry7, rw7, rh7, bg_color=C_CARD, border_color=C_BORDER, accent_color=rcol, accent_pos="top")
        tb = add_textbox(s7, rx + 0.18, ry7 + 0.16, rw7 - 0.36, rh7 - 0.28)
        tf = tb.text_frame
        add_para(tf, rtitle, font_size=12, color=rcol, bold=True, space_after=4, first=True)
        add_para(tf, rcode, font_size=9.0, color=C_CYAN, font_name=FONT_MONO, space_after=8)
        for lead, body in rpts:
            add_rich_bullet(tf, lead, body, lead_color=C_WHITE, body_color=C_TEXT, font_size=10.2, space_after=6)

    set_notes(s7, """[Çekirdek Problem 2 — State Pattern Durum Makinesi]
• PHP 8.2 Backed Enum (OrderStatus) ve State Pattern kullanarak 6 somut durum sınıfı oluşturdum.
• OrderStateMachineService, PHP 8 match() ifadesiyle aktif duruma karşılık gelen State nesnesini döndürür.
• Üç hayati kural nesne düzeyinde korunur:
  1. Pişen yemek iptal edilemez (PreparingState::canCancel() false döner).
  2. Yoldaki sipariş geriye mutfağa dönemez (OnWayState InvalidOrderTransitionException ile reddeder).
  3. Sırasız durum atlaması yapılamaz.""")

    # =========================================================================
    # SLAYT 8: VERİTABANISIZ JSON DEPOLAMA, flock() & AŞAMA 3 MYSQL GEÇİŞİ
    # =========================================================================
    s8 = prs.slides.add_slide(blank)
    add_chrome(s8, 8, "7. Çekirdek Problem 3 — Depolama & Eşzamanlılık",
               "JSON Depolama, flock(LOCK_EX) Atomik Takas & Aşama 3 MySQL Planı",
               "SQL olmadan Race Condition (yarış durumu) ve dosya bozulmasını sıfırlayan 3 katmanlı I/O koruması.")

    # Top 3-Step Atomic I/O Pipeline
    steps8 = [
        ("1. flock($fp, LOCK_EX)", "İşletim Sistemi Özel Kilidi", C_CYAN,
         "İki istek aynı milisaniyede orders.json'a yazmak istediğinde OS düzeyinde özel kilit alınır; ikinci istek güvenle bekletilir, 'Dirty Write' sıfırlanır."),
        ("2. .tmp Dosya + fflush()", "Geçici Dosyaya Güvenli Yazım", C_GOLD,
         "Veri asla canlı dosyanın üzerine doğrudan yazılmaz. Önce orders.json.tmp.<uniqid> dosyasına yazılır ve fflush() ile diske basılır; elektrik kesilse bile asıl dosya bozulmaz."),
        ("3. Atomik rename() Takası", "Tek Mikro-İşlemle Değişim", C_EMERALD,
         "Yazım eksiksiz tamamlandığında işletim sistemi seviyesinde atomik rename() ile eski dosya değiştirilir. Okuyucular asla yarım veya 0 bayt dosya görmez."),
    ]
    sw8 = 3.74
    arr8 = (CONTENT_W - sw8 * 3) / 2.0
    for i, (st, ssub, scol, sdesc) in enumerate(steps8):
        sx = MARGIN_X + i * (sw8 + arr8)
        add_card(s8, sx, CONTENT_TOP, sw8, 2.38, bg_color=C_CARD, border_color=scol, accent_color=scol, accent_pos="top")
        tb = add_textbox(s8, sx + 0.18, CONTENT_TOP + 0.16, sw8 - 0.36, 2.10)
        tf = tb.text_frame
        add_para(tf, st, font_size=12.5, color=scol, bold=True, font_name=FONT_MONO, space_after=2, first=True)
        add_para(tf, ssub, font_size=10.5, color=C_WHITE, bold=True, space_after=7)
        add_para(tf, sdesc, font_size=10.0, color=C_TEXT, space_after=0)
        if i < 2:
            add_textbox(s8, sx + sw8 + 0.02, CONTENT_TOP + 0.95, arr8 - 0.04, 0.45, "➜",
                        font_size=18, color=C_MUTED, bold=True, align=PP_ALIGN.CENTER, v_anchor=MSO_ANCHOR.MIDDLE)

    # Bottom 2-Column Split: JSON Inventory (Left) + Phase 3 MySQL Migration (Right)
    bw8 = (CONTENT_W - 0.28) / 2.0
    by8 = CONTENT_TOP + 2.58
    bh8 = 2.72

    add_card(s8, MARGIN_X, by8, bw8, bh8, bg_color=C_CARD_ALT, border_color=C_BORDER, accent_color=C_PURPLE, accent_pos="left")
    tb_inv = add_textbox(s8, MARGIN_X + 0.24, by8 + 0.14, bw8 - 0.42, bh8 - 0.24)
    tf = tb_inv.text_frame
    add_para(tf, "📂  storage/ Altındaki 5 JSON Deposu + Test Depoları", font_size=12, color=C_PURPLE, bold=True, space_after=6, first=True)
    inv_items = [
        ("users.json:", "10 kullanıcı (1 Admin, 3 Müşteri, 3 Restoran, 3 Kurye) & Bcrypt hash."),
        ("restaurants.json:", "7 restoran, sabit Kadıköy mahalle koordinatları (lat/lon)."),
        ("menu_items.json:", "27 tohumlanmış menü ürünü, fiyat, kategori, KDS CRUD & stok."),
        ("couriers.json:", "4 kurye konumu, araç türü ve anlık aktif sipariş sayaçları."),
        ("orders.json & InMemory*:", "Sipariş/kalemler + PHPUnit için 0 disk I/O InMemory depolar."),
    ]
    for lead, body in inv_items:
        add_rich_bullet(tf, lead, body, lead_color=C_WHITE, body_color=C_TEXT, font_size=9.8, space_after=4)

    add_card(s8, MARGIN_X + bw8 + 0.28, by8, bw8, bh8, bg_color=C_GREEN_BG, border_color=C_EMERALD, accent_color=C_EMERALD, accent_pos="left")
    tb_m = add_textbox(s8, MARGIN_X + bw8 + 0.52, by8 + 0.14, bw8 - 0.42, bh8 - 0.24)
    tf = tb_m.text_frame
    add_para(tf, "🚀  Aşama 3 MySQL Geçiş Planı (Sıfır İş Kodu Değişimi)", font_size=12, color=C_MINT, bold=True, space_after=6, first=True)
    mig_items = [
        ("Tek Değişen Katman:", "Yalnız src/Repository/MySql/ altında 4 somut sınıf eklenecek."),
        ("Kilitleme Geçişi:", "flock(LOCK_EX) ➔ SELECT ... FOR UPDATE / Koşullu UPDATE."),
        ("Atomiklik Geçişi:", ".tmp + rename() ➔ PDO::beginTransaction(), commit(), rollBack()."),
        ("İlişkisel Kısıtlar:", "Kod kontrolü ➔ FOREIGN KEY ... ON DELETE CASCADE & SQL JOIN."),
        ("Mimari Garanti:", "Dependency Inversion sayesinde Service & Controller'da 0 satır değişim!"),
    ]
    for lead, body in mig_items:
        add_rich_bullet(tf, lead, body, lead_color=C_WHITE, body_color=C_TEXT, font_size=9.8, space_after=4)

    set_notes(s8, """[JSON Depolama, flock() & Aşama 3 MySQL Geçişi]
• Aşama 2'de veritabanı olmadan eşzamanlı yazma çakışmasını JsonStorageHelper sınıfıyla çözdüm:
  1) flock(LOCK_EX) ile OS kilidi alınıyor,
  2) Doğrudan hedef dosyaya değil geçici .tmp dosyasına yazıp fflush() ediliyor,
  3) Atomik rename() ile tek adımda takas ediliyor.
• Bu davranış JsonStorageFlockTest içinde gerçek LOCK_EX | LOCK_NB çakışma testiyle doğrulandı.
• Aşama 3'te MySQL'e geçerken yalnız Repository sınıfları değişecek: flock yerine FOR UPDATE, .tmp yerine PDO Transaction gelecek.""")

    # =========================================================================
    # SLAYT 9: GÜVENLİK MİMARİSİ (BCRYPT, PRG, AUTH GUARD, IDOR, TEK YÖNETİCİ)
    # =========================================================================
    s9 = prs.slides.add_slide(blank)
    add_chrome(s9, 9, "8. Güvenlik & Oturum Mimarisi",
               "Güvenlik Mimarisi: Bcrypt, HTTP 303 PRG, IDOR ve Tek Yönetici",
               "Parola şifreleme, F5 mükerrer işlem koruması, dinamik alt dizin güvenliği ve sahiplik (IDOR) engeli.")

    # Top row: 3 cards
    top9 = [
        ("🔐 1. Bcrypt & CSRF Koruması", C_CYAN, [
            ("Düz Metin Yasak:", "Parolalar PASSWORD_DEFAULT (Bcrypt $2y$10$...) ile saklanır."),
            ("Güvenli Doğrulama:", "password_verify() zamanlama saldırısını engeller."),
            ("CSRF Token:", "Login/Register formları _csrf_token (hash_equals) doğrular."),
        ]),
        ("🔁 2. PRG, Flash & Old Input", C_GOLD, [
            ("HTTP 303 PRG:", "Form gönderiminde F5 çift sipariş / çift kayıt engellenir."),
            ("Eski Veri Koruma:", "Hatalı formda Session::setOldInput() geçerli alanları tutar."),
            ("Parola Arındırma:", "Password alanları asla Session oldInput içine yazılmaz."),
        ]),
        ("🚪 3. Auth Guard & Güvenli Hata", C_PURPLE, [
            ("XAMPP/WAMP Uyumu:", "Request::detectBasePath() alt dizin 404 hatasını önler."),
            ("XSS Çıktı Kaçışı:", "Tüm kullanıcı verileri e() / htmlspecialchars() ile kaçışlanır."),
            ("0 Stack-Trace Sızıntısı:", "İç hata yalnız error_log()'a yazılır; kullanıcı sade 500 görür."),
        ]),
    ]
    tw9 = (CONTENT_W - 0.36) / 3.0
    th9 = 2.48
    for idx, (t, col, pts) in enumerate(top9):
        tx = MARGIN_X + idx * (tw9 + 0.18)
        add_card(s9, tx, CONTENT_TOP, tw9, th9, bg_color=C_CARD, border_color=C_BORDER, accent_color=col, accent_pos="top")
        tb = add_textbox(s9, tx + 0.18, CONTENT_TOP + 0.16, tw9 - 0.36, th9 - 0.28)
        tf = tb.text_frame
        add_para(tf, t, font_size=12, color=col, bold=True, space_after=7, first=True)
        for lead, body in pts:
            add_rich_bullet(tf, lead, body, lead_color=C_WHITE, body_color=C_TEXT, font_size=10.2, space_after=5)

    # Bottom row: 2 wide spotlight cards (IDOR + Single Protected Admin)
    bw9 = (CONTENT_W - 0.28) / 2.0
    by9 = CONTENT_TOP + th9 + 0.22
    bh9 = 2.60

    add_card(s9, MARGIN_X, by9, bw9, bh9, bg_color=C_RED_BG, border_color=C_RED, accent_color=C_RED, accent_pos="left")
    tb_idor = add_textbox(s9, MARGIN_X + 0.24, by9 + 0.16, bw9 - 0.42, bh9 - 0.28)
    tf = tb_idor.text_frame
    add_para(tf, "🛡️  4. IDOR Koruması — AuthService::assertCanAccessOrder()", font_size=12.5, color=C_RED, bold=True, space_after=6, first=True)
    add_rich_bullet(tf, "Tehdit:", "Müşteri A'nın URL'deki order_id parametresini değiştirerek (/api?id=2) başka bir müşterinin siparişine erişme girişimi.", lead_color=C_WHITE, body_color=C_TEXT, font_size=10.2, space_after=5)
    add_rich_bullet(tf, "Sahiplik Kuralı:", "Müşteri yalnız kendi siparişini, kurye yalnız atandığı görevi, restoran yalnız kendi siparişini görür; yalnızca Admin tam denetim yapar.", lead_color=C_WHITE, body_color=C_TEXT, font_size=10.2, space_after=5)
    add_rich_bullet(tf, "Sonuç:", "Yetkisiz eşleşmede anında HTTP 403 Forbidden fırlatılır (SecurityAndAuthTest ile doğrulandı).", lead_color=C_GOLD, body_color=C_WHITE, font_size=10.2, space_after=0)

    add_card(s9, MARGIN_X + bw9 + 0.28, by9, bw9, bh9, bg_color=C_CORAL_BG, border_color=C_CORAL, accent_color=C_CORAL, accent_pos="left")
    tb_adm = add_textbox(s9, MARGIN_X + bw9 + 0.52, by9 + 0.16, bw9 - 0.42, bh9 - 0.28)
    tf = tb_adm.text_frame
    add_para(tf, "👑  5. Korumalı Tek Yönetici & Sınır Değer Denetimi", font_size=12.5, color=C_CORAL, bold=True, space_after=6, first=True)
    add_rich_bullet(tf, "Tek Yetkili Yönetici:", "Mustafa Güreşir (admin_mustafa) yalnızca bin/seed.php tohumlamasıyla sisteme tanımlanır.", lead_color=C_WHITE, body_color=C_TEXT, font_size=10.2, space_after=5)
    add_rich_bullet(tf, "Admin Kayıt Kilidi:", "/register formu ve AuthService::register() dışarıdan admin/restoran/kurye hesabı açılmasını mimari düzeyde reddeder.", lead_color=C_WHITE, body_color=C_TEXT, font_size=10.2, space_after=5)
    add_rich_bullet(tf, "FR-16 Sınır Testi:", "Kullanıcı adı < 3 karakter veya parola < 6 karakter ise InvalidArgumentException fırlatılır (4 ayrık sınır testi yeşil).", lead_color=C_GOLD, body_color=C_WHITE, font_size=10.2, space_after=0)

    set_notes(s9, """[Güvenlik Mimarisi]
• Güvenliği 5 katmanda ele aldık:
  1) Tüm şifreler password_hash ile Bcrypt tuzlanıyor.
  2) Form işlemlerinde HTTP 303 PRG ve tek seferlik Flash mesajlarıyla F5 çift sipariş riski yok edildi.
  3) Oturumsuz erişim Auth Guard ile kesiliyor ve Request::detectBasePath() XAMPP/WAMP alt dizinlerinde 404'ü engelliyor.
  4) IDOR koruması: AuthService::assertCanAccessOrder() her sipariş sorgusunda oturum sahibini doğrular, yetkisiz denemeyi 403 Forbidden ile reddeder.
  5) Dışarıdan yeni admin kaydı kesinlikle kilitlidir; tek yetkili yönetici tohumlanmış admin_mustafa hesabıdır.""")

    # =========================================================================
    # SLAYT 10: VERİ MODELİ, 7 TABLOLU ER MİMARİSİ & 1NF / 2NF / 3NF ANALİZİ
    # =========================================================================
    s10 = prs.slides.add_slide(blank)
    add_chrome(s10, 10, "9. Veri Modelleme & İlişkisel Tasarım",
               "7 Tablolu ER Mimarisi ve 1NF / 2NF / 3NF Normalizasyon Analizi",
               "JSON depolama katmanında dahi 7 ilişkisel varlık ve 3. Normal Form (3NF) disiplinine %100 bağlı kalınmıştır.")

    # Top: 7 Entity Visual ER Strip
    add_card(s10, MARGIN_X, CONTENT_TOP, CONTENT_W, 1.88, bg_color=C_CARD_ALT, border_color=C_BORDER, accent_color=C_CYAN, accent_pos="top")
    add_textbox(s10, MARGIN_X + 0.18, CONTENT_TOP + 0.12, CONTENT_W - 0.36, 0.28,
                "🗄️  Şartnamedeki Asgari 7 İlişkisel Tablo / Varlık Şeması (ER Mimarisi & Foreign Key İlişkileri)",
                font_size=11.5, color=C_CYAN, bold=True)

    entities = [
        ("users", "id (PK)\nusername (UK)\npassword_hash\nrole", C_GOLD),
        ("restaurants", "id (PK)\nuser_id (FK)\nname, lat/lon\nneighborhood", C_CORAL),
        ("menu_items", "id (PK)\nrestaurant_id(FK)\nname, price\nis_available", C_CORAL),
        ("couriers", "id (PK)\nuser_id (FK)\nlat/lon, status\nactive_orders", C_EMERALD),
        ("orders", "id (PK)\ncustomer_id (FK)\nrestaurant_id(FK)\ncourier_id (FK)", C_CYAN),
        ("order_items", "id (PK)\norder_id (FK)\nmenu_item_id(FK)\nqty, subtotal", C_PURPLE),
        ("order_status_log", "id (PK)\norder_id (FK)\nprev → next\nchanged_by", C_PURPLE),
    ]
    ew10 = (CONTENT_W - 0.36 - 0.10 * 6) / 7.0
    ey10 = CONTENT_TOP + 0.46
    for idx, (ename, ecols, ecol) in enumerate(entities):
        ex = MARGIN_X + 0.18 + idx * (ew10 + 0.10)
        add_card(s10, ex, ey10, ew10, 1.26, bg_color=C_BG, border_color=ecol, radius=0.08)
        tb_e = add_textbox(s10, ex + 0.06, ey10 + 0.06, ew10 - 0.12, 1.14)
        tf = tb_e.text_frame
        add_para(tf, ename, font_size=9.2, color=ecol, bold=True, font_name=FONT_MONO, align=PP_ALIGN.CENTER, space_after=4, first=True)
        add_para(tf, ecols, font_size=8.2, color=C_TEXT, font_name=FONT_MONO, align=PP_ALIGN.CENTER, space_after=0)

    # Bottom: 1NF, 2NF, 3NF Cards
    nf_cards = [
        ("1NF — Birinci Normal Form", "Atomik Değerler (Tekrarlayan Grup Yok)", C_CYAN, [
            ("Kural:", "Her sütun bölünemez tekil değer tutmalıdır."),
            ("Uygulama:", "Bir siparişteki ürünler orders içinde \"2x Adana, 1x Ayran\" şeklinde tek metin hücresinde tutulmaz."),
            ("Çözüm:", "N-M ilişkisi order_items ara varlığında her kalem ayrı kayıt olacak biçimde çözülür."),
        ]),
        ("2NF — İkinci Normal Form", "Kısmi Bağımlılık Yok", C_GOLD, [
            ("Kural:", "1NF sağlanmalı ve anahtar dışı her alan birincil anahtarın tamamına bağımlı olmalıdır."),
            ("Uygulama:", "Her varlık tekil id (PK) kullanır; bileşik anahtar kaynaklı kısmi bağımlılık yoktur."),
            ("Çözüm:", "order_items içinde ürün adı/açıklaması tekrarlanmaz; yalnızca menu_item_id referansı tutulur."),
        ]),
        ("3NF — Üçüncü Normal Form ⭐", "Geçişli Bağımlılık Yok (Hocanın Kritik Kriteri)", C_EMERALD, [
            ("Kural:", "Anahtar dışı bir sütun başka bir anahtar dışı sütuna bağımlı olamaz."),
            ("Neden orders'ta Kurye Konumu Yok?", "orders tablosunda kuryenin adı, telefonu veya anlık konumu (lat/lon) ASLA tutulmaz; yalnız courier_id saklanır!"),
            ("Fayda:", "Kurye konum değiştirdiğinde geçmiş sipariş kayıtları bozulmaz, güncelleme anomalisi (Update Anomaly) sıfırlanır."),
        ]),
    ]
    nw10 = (CONTENT_W - 0.36) / 3.0
    ny10 = CONTENT_TOP + 2.08
    nh10 = 3.22
    for idx, (nt, nsub, ncol, npts) in enumerate(nf_cards):
        nx = MARGIN_X + idx * (nw10 + 0.18)
        bg = C_GREEN_BG if idx == 2 else C_CARD
        add_card(s10, nx, ny10, nw10, nh10, bg_color=bg, border_color=ncol if idx == 2 else C_BORDER, accent_color=ncol, accent_pos="top")
        tb = add_textbox(s10, nx + 0.18, ny10 + 0.16, nw10 - 0.36, nh10 - 0.28)
        tf = tb.text_frame
        add_para(tf, nt, font_size=12, color=ncol, bold=True, space_after=2, first=True)
        add_para(tf, nsub, font_size=9.5, color=C_MUTED, bold=True, space_after=8)
        for lead, body in npts:
            add_rich_bullet(tf, lead, body, lead_color=C_WHITE, body_color=C_TEXT, font_size=10.0, space_after=6)

    set_notes(s10, """[7 Tablolu ER Mimarisi & 3NF Normalizasyonu]
• Şartnamedeki 7 ana varlığın tamamı modellenmiştir: users, restaurants, menu_items, couriers, orders, order_items ve order_status_log.
• 1NF: Sipariş ürünleri tek hücrede metin değil, order_items içinde atomik satırlardır.
• 2NF: Her kayıt kendi PK'sına tam bağımlıdır.
• 3NF (Hocanın favori sorusu): 'Neden orders tablosunda kuryenin anlık konumunu veya adını tutmuyorsunuz?' Çünkü bu geçişli bağımlılıktır (order -> courier -> location). Kurye hareket ettikçe orders tablosunu güncellemek gerekir ve eski sipariş kayıtları bozulur. Yalnızca courier_id FK tutarak 3NF'yi %100 koruduk.""")

    # =========================================================================
    # SLAYT 11: TASARIM DESENLERİ & ELENEN ALTERNATİFLER
    # =========================================================================
    s11 = prs.slides.add_slide(blank)
    add_chrome(s11, 11, "10. Mimari Karar Analizi (Design Patterns)",
               "Tasarım Desenleri (State, Strategy, Repository) ve Elenen Alternatifler",
               "Her tasarım deseni somut bir mimari problemi çözmek için seçilmiş, zayıf alternatifler gerekçeleriyle elenmiştir.")

    patterns = [
        ("1. State Pattern", "Durum Tasarım Deseni", "src/Service/State/*", C_EMERALD,
         "6 sipariş durumu (Received..Cancelled) kendi geçiş kurallarını ve canCancel() iznini kendi sınıfında kapsüller. Yeni durum eklemek mevcut sınıfları bozmaz (OCP).",
         "Controller/Serviste iç içe onlarca switch-case ve if-else yazmak kodu spagettiye çevirir; yeni aşama eklendiğinde tüm dalların değişmesi gerekir."),
        ("2. Strategy Pattern", "Strateji Tasarım Deseni", "src/Service/Courier/*", C_CYAN,
         "Kurye atama formülü (ScoreBasedAssignmentStrategy) çalışma anında ve testlerde tak-çıkar değiştirilebilir. Farklı saat/yoğunluk politikaları kolayca eklenir.",
         "Algoritmayı CourierAssignmentService içine gömülü if-else olarak yazmak esnekliği ve bağımsız birim test yazılabilirliğini yok eder."),
        ("3. Repository Pattern", "Veri Deposu Deseni", "src/Repository/*", C_GOLD,
         "İş mantığını depolamadan yalıtır. Aşama 2'de Json*, testlerde 0 disk I/O InMemory*, Aşama 3'te MySql* depoları tek iş kodu satırı değişmeden takılır.",
         "Controller veya Servis içinde inline SQL ya da doğrudan file_put_contents() kullanmak sistemi depolamaya zincirler ve testleri kırılgan yapar."),
    ]
    pw11 = (CONTENT_W - 0.36) / 3.0
    for idx, (ptitle, psub, ppath, pcol, why_ok, why_no) in enumerate(patterns):
        px = MARGIN_X + idx * (pw11 + 0.18)
        add_card(s11, px, CONTENT_TOP, pw11, CONTENT_H, bg_color=C_CARD, border_color=C_BORDER, accent_color=pcol, accent_pos="top")

        # Header
        tb_h = add_textbox(s11, px + 0.18, CONTENT_TOP + 0.16, pw11 - 0.36, 0.85)
        tf = tb_h.text_frame
        add_para(tf, ptitle, font_size=14, color=pcol, bold=True, space_after=2, first=True)
        add_para(tf, psub, font_size=10.5, color=C_WHITE, bold=True, space_after=3)
        add_para(tf, ppath, font_size=9.0, color=C_MUTED, font_name=FONT_MONO, space_after=0)

        # Selected inner card (Green tint)
        add_card(s11, px + 0.16, CONTENT_TOP + 1.12, pw11 - 0.32, 1.92, bg_color=C_GREEN_BG, border_color=C_EMERALD, radius=0.07)
        tb_ok = add_textbox(s11, px + 0.28, CONTENT_TOP + 1.22, pw11 - 0.56, 1.72)
        tf = tb_ok.text_frame
        add_para(tf, "✅  NEDEN SEÇİLDİ?", font_size=10.5, color=C_MINT, bold=True, space_after=5, first=True)
        add_para(tf, why_ok, font_size=10.2, color=C_TEXT, space_after=0)

        # Rejected alternative inner card (Red tint)
        add_card(s11, px + 0.16, CONTENT_TOP + 3.18, pw11 - 0.32, 1.94, bg_color=C_RED_BG, border_color=C_RED, radius=0.07)
        tb_no = add_textbox(s11, px + 0.28, CONTENT_TOP + 3.28, pw11 - 0.56, 1.74)
        tf = tb_no.text_frame
        add_para(tf, "❌  ELENEN ALTERNATİF", font_size=10.5, color=C_RED, bold=True, space_after=5, first=True)
        add_para(tf, why_no, font_size=10.2, color=C_TEXT, space_after=0)

    set_notes(s11, """[Tasarım Desenleri & Elenen Alternatifler]
• Desenleri 'moda olduğu için' değil, somut mimari riskleri ortadan kaldırmak için seçtik:
  1) State Pattern: switch-case spagettisi yerine her durumun kendi kuralını kapsüllemesi (OCP).
  2) Strategy Pattern: Kurye atama algoritmasını servisten ayırıp değiştirilebilir ve test edilebilir kılmak.
  3) Repository Pattern: İş mantığını depolama motorundan izole ederek InMemory testler ve sıfır riskli Aşama 3 MySQL geçişi sağlamak.""")

    # =========================================================================
    # SLAYT 12: RİSK MATRİSİ (6 RİSK) + YAPAY ZEKÂ GÜNLÜĞÜ (ZORUNLU AI HATA VAKASI)
    # =========================================================================
    s12 = prs.slides.add_slide(blank)
    add_chrome(s12, 12, "11. Risk Yönetimi & Yapay Zekâ Denetimi",
               "6 Kritik Risk Matrisi ve Yapay Zekâ Günlüğü (Zorunlu AI Hata Vakası)",
               "Olası sistem risklerine karşı alınan teknik önlemler ve yapay zekânın hatalı önerisinin reddi (docs/ai-gunlugu.md).")

    # Top Table: 6 Risks (7 rows x 4 cols)
    rows12, cols12 = 7, 4
    tbl_shp12 = s12.shapes.add_table(rows12, cols12, Inches(MARGIN_X), Inches(CONTENT_TOP), Inches(CONTENT_W), Inches(3.16))
    tbl12 = tbl_shp12.table
    widths12 = [0.75, 3.05, 1.55, 6.783]
    for ci, w in enumerate(widths12):
        tbl12.columns[ci].width = Inches(w)

    h12 = ["No", "Kritik Risk Tanımı", "Olasılık / Etki", "Alınan Somut Teknik Önlem"]
    for ci, txt in enumerate(h12):
        style_table_cell(tbl12.cell(0, ci), txt, C_CARD_HI, C_CORAL, font_size=10.2, bold=True,
                         align=PP_ALIGN.CENTER if ci in (0, 2) else PP_ALIGN.LEFT)

    risks = [
        ("R-1", "Race Condition (Kurye / Dosya Çakışması)", "Yüksek / Yüksek", "flock(LOCK_EX) OS dosya kilidi + .tmp geçici dosya yazımı + atomik rename() takası."),
        ("R-2", "Geçersiz Durum Geçişi İhlali", "Yüksek / Yüksek", "State Pattern ile InvalidOrderTransitionException / DomainException (HTTP 400)."),
        ("R-3", "Pişen Yemeğin Haksız İptali", "Orta / Yüksek", "PreparingState::canCancel() === false + arayüzde iptal butonu kilidi."),
        ("R-4", "Müsait Kurye Havuzunun Tükenmesi", "Orta / Yüksek", "NoAvailableCourierException (HTTP 409) ile siparişin hazır kuyruğunda bekletilmesi."),
        ("R-5", "Mimari İhlal (Katman Atlama / AI Sızıntısı)", "Yüksek / Orta", "Katman sorumluluk tablosu, Interface soyutlaması ve her adımda manuel mimari denetim."),
        ("R-6", "AJAX Polling Sunucu / I/O Yükü", "Orta / Orta", "3-5 saniyelik akıllı polling aralığı ve yalnızca hafif JSON durum kontrol endpointi."),
    ]
    for ri, row in enumerate(risks):
        bg = C_CARD if ri % 2 == 0 else C_CARD_ALT
        for ci, val in enumerate(row):
            col = C_CYAN if ci == 0 else (C_GOLD if ci == 2 else (C_WHITE if ci == 1 else C_TEXT))
            style_table_cell(tbl12.cell(ri + 1, ci), val, bg, col, font_size=9.5,
                             bold=(ci in (0, 1, 2)), align=PP_ALIGN.CENTER if ci in (0, 2) else PP_ALIGN.LEFT)

    # Bottom AI Log Mandatory Error Case Card
    ay12 = CONTENT_TOP + 3.34
    ah12 = 1.96
    add_card(s12, MARGIN_X, ay12, CONTENT_W, ah12, bg_color=C_CORAL_BG, border_color=C_CORAL, accent_color=C_CORAL, accent_pos="left")
    tb_ai = add_textbox(s12, MARGIN_X + 0.24, ay12 + 0.12, CONTENT_W - 0.42, ah12 - 0.22)
    tf = tb_ai.text_frame
    add_para(tf, "🤖  YAPAY ZEKÂ KULLANIM GÜNLÜĞÜ — ZORUNLU AI HATA VAKALARI (docs/ai-gunlugu.md)",
             font_size=11.5, color=C_CORAL, bold=True, space_after=5, first=True)
    add_rich_bullet(tf, "Vaka 1 — Kritik Hata (REDDEDİLDİ ❌):",
                    "Yapay zekâ, Aşama 2 için ısrarla SQLite veritabanı ve SQL sorguları kurmayı önerdi. Mustafa Güreşir şartnamedeki 'Aşama 2'de veritabanı kesinlikle yasaktır, storage/*.json kullanılır' maddesiyle öneriyi reddedip Json*Repository mimarisini kurdu.",
                    lead_color=C_RED, body_color=C_WHITE, font_size=9.8, space_after=5)
    add_rich_bullet(tf, "Vaka 2 & 3 — Düzeltilen AI Hataları (⚠️):",
                    "AI'nın mutlak '/login' yönlendirmesi XAMPP alt klasöründe 404 verince Request::detectBasePath() geliştirildi; 2D Öklid önerisi gerçek Haversine (R=6371 km) + iş yükü puanlamasına çevrildi; yüzeysel oturum kontrolüne IDOR eklendi.",
                    lead_color=C_GOLD, body_color=C_TEXT, font_size=9.8, space_after=0)

    set_notes(s12, """[Risk Matrisi & Zorunlu AI Hata Vakası]
• 6 kritik mimari ve operasyonel riski (Race Condition, geçersiz durum, haksız iptal, kurye tükenmesi, katman ihlali, AJAX yükü) somut yazılımsal önlemlerle eşleştirdim.
• Hocamızın özellikle sorduğu 'Yapay zekâ nerede yanıldı, nasıl düzelttiniz?' sorusunun somut cevabı docs/ai-gunlugu.md belgesinde kayıtlı:
  - AI, Aşama 2 için SQLite kurmayı ve SQL yazmayı önerdi; şartnameyi gözeterek reddettim ve flock() destekli Json*Repository sınıflarını geliştirdim.
  - Ayrıca XAMPP alt klasör 404 hatasını detectBasePath() ile, Öklid mesafe hatasını Haversine ile düzelttim.""")

    # =========================================================================
    # SLAYT 13 (YENİ): ELEŞTİRMEN GERİ BİLDİRİMLERİ & GİT / SÜREÇ YÖNETİMİ
    # =========================================================================
    s13_rev = prs.slides.add_slide(blank)
    add_chrome(s13_rev, 13, "12. Süreç Raporu, Eleştirmen Değerlendirmesi & Git",
               "Eleştirmen Geri Bildirimleri (2 Kabul + 1 Red) ve Geliştirme Süreci",
               "docs/surec-raporu.md özeti: alınan teknik eleştirilerin aksiyonları ve tek geliştiriciyle yürütülen 5 haftalık süreç.")

    # Left: Critic Feedback (3 stacked cards)
    lw13r = 6.20
    fb_cards = [
        ("✅ KABUL #1 — Eşit Kurye Puanı Kırıcı Kuralı", C_EMERALD, C_GREEN_BG,
         "Eleştiri: 'İki kuryenin puanı tamamen eşit çıkarsa algoritma kime öncelik veriyor?'",
         "Uygulama: ScoreBasedAssignmentStrategy içine eşit puanda aktif siparişi daha az olan kuryeyi seçen tie-breaker eklendi ve testEqualScorePrefersLowerActiveOrders ile doğrulandı."),
        ("✅ KABUL #2 — Bcrypt Parola & CSRF Form Koruması", C_CYAN, C_CYAN_BG,
         "Eleştiri: 'Parolalar düz metin tutulmamalı; auth formları CSRF token ile korunmalı.'",
         "Uygulama: User modelinde password_hash/password_verify zorunlu kılındı; login/register formlarına Session::getCsrfToken() (_csrf_token) ve old input koruması eklendi."),
        ("❌ GEREKÇELİ RED #3 — Ücretli Canlı GPS Harita API İsteği", C_CORAL, C_CORAL_BG,
         "Eleştiri: 'Müşteriye kuryenin gerçek zamanlı GPS konumunu harita API ile gösterin.'",
         "Gerekçe: Resmi şartname Kapsam Sınırı ('canlı GPS ve ödeme yoktur; sabit koordinatlar kullanılır') gereği reddedildi. Görsel harita yalnız sabit Kadıköy koordinatlarını çizer."),
    ]
    fh_gap = 0.16
    fh_card = (CONTENT_H - fh_gap * 2) / 3.0
    for idx, (ftitle, fcol, fbg, fcrit, fact) in enumerate(fb_cards):
        fy = CONTENT_TOP + idx * (fh_card + fh_gap)
        add_card(s13_rev, MARGIN_X, fy, lw13r, fh_card, bg_color=fbg, border_color=fcol, accent_color=fcol, accent_pos="left")
        tb = add_textbox(s13_rev, MARGIN_X + 0.22, fy + 0.12, lw13r - 0.36, fh_card - 0.20)
        tf = tb.text_frame
        add_para(tf, ftitle, font_size=11.0, color=fcol, bold=True, space_after=4, first=True)
        add_para(tf, fcrit, font_size=9.4, color=C_GOLD, bold=True, space_after=4)
        add_para(tf, fact, font_size=9.4, color=C_TEXT, space_after=0)

    # Right: Git Workflow & 5-Week Solo Delivery Card
    rx13r = MARGIN_X + lw13r + 0.28
    rw13r = CONTENT_W - lw13r - 0.28
    add_card(s13_rev, rx13r, CONTENT_TOP, rw13r, CONTENT_H, bg_color=C_CARD, border_color=C_BORDER, accent_color=C_GOLD, accent_pos="top")
    tb_git = add_textbox(s13_rev, rx13r + 0.20, CONTENT_TOP + 0.16, rw13r - 0.40, CONTENT_H - 0.86)
    tf = tb_git.text_frame
    add_para(tf, "🌿  Git İş Akışı & Bireysel Süreç (Mustafa Güreşir)", font_size=12.5, color=C_GOLD, bold=True, space_after=8, first=True)
    git_pts = [
        ("Tek Geliştirici Sorumluluğu:", "Mimari, backend (PHP 8.2), frontend (Cravelo UI), 360 test ve tüm belgeler Mustafa Güreşir tarafından uçtan uca geliştirildi."),
        ("GitHub Deposu & Dal Yapısı:", "github.com/mustafaguresir/Cravelo — Korumalı main dalı, feat/* & fix/* geliştirme dalları ve Pull Request inceleme akışı."),
        ("Hafta 5–6 (Veri & Çekirdek):", "storage/*.json + flock() atomik yazım, 4 Repository arayüzü + Json* depoları ve bin/seed.php tohumlama."),
        ("Hafta 7–8 (Güvenlik & Test):", "Front Controller, OrderService, PRG + CSRF, IDOR 403, 54 PHPUnit testi, InMemory depolar ve PHPStan L5 (0 hata)."),
        ("Hafta 9 (Arayüz & Savunma):", "XAMPP/WAMP BasePath, KDS CRUD, kurye vardiya, yönetici analitiği, canlı sohbet/yorumlar ve 21 slaytlık savunma sunumu."),
    ]
    for lead, body in git_pts:
        add_rich_bullet(tf, lead, body, lead_color=C_WHITE, body_color=C_TEXT, font_size=9.8, space_after=6)

    add_pill(s13_rev, rx13r + 0.20, CONTENT_TOP + CONTENT_H - 0.56, rw13r - 0.40, 0.40,
             "docs/surec-raporu.md • docs/ai-gunlugu.md • docs/izlenebilirlik.md", C_CARD_ALT, C_CYAN, border_color=C_CYAN, font_size=8.6)

    set_notes(s13_rev, """[Eleştirmen Geri Bildirimleri & Geliştirme Süreci]
• Bu slayt ders yönergesindeki Eleştirmen geri bildirimleri ve Git/süreç raporu özetini sunar.
• 2 teknik öneriyi kabul edip kodla doğruladım: (1) Eşit kurye puanında düşük aktif görev önceliği (tie-breaker), (2) Bcrypt password_hash + CSRF token koruması.
• 1 öneriyi gerekçeli reddettim: Canlı ücretli GPS harita API isteği, şartname Kapsam Sınırı gereği kapsam dışı bırakıldı.
• Tüm proje Mustafa Güreşir tarafından bireysel olarak geliştirildi ve github.com/mustafaguresir/Cravelo üzerinde yürütülmektedir.""")

    # =========================================================================
    # SLAYT 14: 10 KULLANICI, RBAC YETKİ MATRİSİ & ROL İZOLASYONU
    # =========================================================================
    s13 = prs.slides.add_slide(blank)
    add_chrome(s13, 14, "13. Kullanıcı Mimarisi & RBAC",
               "10 Sistem Kullanıcısı, RBAC Yetki Matrisi ve Rol İzolasyonu",
               "bin/seed.php ile tohumlanan 10 Bcrypt korumalı kullanıcı ve .customer-only-ui arayüz izolasyon mimarisi.")

    # Left: 10-User Table (11 rows x 4 cols)
    tw13 = 7.95
    tbl_shp13 = s13.shapes.add_table(11, 4, Inches(MARGIN_X), Inches(CONTENT_TOP), Inches(tw13), Inches(CONTENT_H))
    tbl13 = tbl_shp13.table
    for ci, w in enumerate([1.85, 2.30, 1.50, 2.30]):
        tbl13.columns[ci].width = Inches(w)

    for ci, h in enumerate(["Kullanıcı", "Kullanıcı Adı", "Sistem Rolü", "Erişim Yetkisi"]):
        style_table_cell(tbl13.cell(0, ci), h, C_CARD_HI, C_CORAL, font_size=9.8, bold=True)

    users13 = [
        ("👑 Mustafa Güreşir", "admin_mustafa", "Yönetici (admin)", "Tüm Sistem, Filo & Log"),
        ("👤 Ahmet Yılmaz", "ahmet_musteri", "Müşteri (customer)", "Menü, Sepet, Canlı Takip"),
        ("👤 Ayşe Kaya", "ayse_musteri", "Müşteri (customer)", "Menü, Sepet, Canlı Takip"),
        ("👤 Mehmet Demir", "mehmet_musteri", "Müşteri (customer)", "Menü, Sepet, Canlı Takip"),
        ("👨‍🍳 Lezzet Kebap", "restoran_lezzet", "Restoran (restaurant)", "Mutfak KDS & Ciro"),
        ("👨‍🍳 Burger Lab", "restoran_burger", "Restoran (restaurant)", "Mutfak KDS & Ciro"),
        ("👨‍🍳 Pizza Roma", "restoran_pizza", "Restoran (restaurant)", "Mutfak KDS & Ciro"),
        ("🛵 Ali Kaya", "kurye_ali", "Kurye (courier)", "Kokpit (0.71 km, 2 görev)"),
        ("🛵 Veli Demir", "kurye_veli", "Kurye (courier)", "Kokpit (1.31 km, 0 görev)"),
        ("🛵 Can Öz", "kurye_can", "Kurye (courier)", "Kokpit (Moda, 1 görev)"),
    ]
    for ri, row in enumerate(users13):
        bg = C_CARD if ri % 2 == 0 else C_CARD_ALT
        role = row[2]
        rcol = C_GOLD if "Yönetici" in role else (C_CYAN if "Müşteri" in role else (C_CORAL if "Restoran" in role else C_MINT))
        for ci, val in enumerate(row):
            style_table_cell(
                tbl13.cell(ri + 1, ci), val, bg,
                text_color=rcol if ci == 2 else (C_WHITE if ci == 0 else C_TEXT),
                font_size=9.2, bold=(ci in (0, 2)),
                font_name=FONT_MONO if ci == 1 else FONT_MAIN,
            )

    # Right: RBAC & .customer-only-ui Isolation Panel
    rx13 = MARGIN_X + tw13 + 0.26
    rw13 = CONTENT_W - tw13 - 0.26
    add_card(s13, rx13, CONTENT_TOP, rw13, CONTENT_H, bg_color=C_CARD, border_color=C_BORDER, accent_color=C_PURPLE, accent_pos="top")
    tb_rb = add_textbox(s13, rx13 + 0.18, CONTENT_TOP + 0.16, rw13 - 0.36, CONTENT_H - 0.88)
    tf = tb_rb.text_frame
    add_para(tf, "🔐  RBAC & Rol İzolasyonu", font_size=13, color=C_PURPLE, bold=True, space_after=7, first=True)
    rb_bullets = [
        (".customer-only-ui Sözleşmesi:", "Restoran, Kurye ve Yönetici oturumlarında sepet, adres ve sipariş butonları DOM düzeyinde gizlenir."),
        ("Çift Katmanlı Koruma:", "İstemci gizlemesi tek başına bırakılmaz; sunucuda OrderController & AuthService rol doğrular."),
        ("Bcrypt Demo Standardı:", "Tüm 10 kullanıcının parolası storage/users.json içinde PASSWORD_DEFAULT ($2y$10$...) hash ile tutulur."),
        ("Yönetici Rol Switcher:", "Yalnızca admin_mustafa oturumunda görünen önizleme çubuğu ([👑] [👨‍🍳] [🛵] [🛍️]) anlık geçiş sağlar."),
    ]
    for lead, body in rb_bullets:
        add_rich_bullet(tf, lead, body, lead_color=C_WHITE, body_color=C_TEXT, font_size=10.0, space_after=6)

    add_pill(s13, rx13 + 0.18, CONTENT_TOP + CONTENT_H - 0.56, rw13 - 0.36, 0.40,
             "42 Otomatik Node.js Rol İzolasyon Testi ✅", C_GREEN_BG, C_MINT, border_color=C_EMERALD, font_size=9.2)

    set_notes(s13, """[10 Kullanıcı, RBAC & Rol İzolasyonu]
• Sistemimiz bin/seed.php ile 10 gerçekçi rol kullanıcısıyla tohumlanır: 1 korumalı yönetici (Mustafa Güreşir), 3 müşteri, 3 restoran yetkilisi ve 3 kurye.
• Tüm parolalar Bcrypt ile tuzlanmıştır.
• Önemli UX ve güvenlik detayı: .customer-only-ui sınıfımız sayesinde kurye veya restoran giriş yaptığında üst barda sepet ya da müşteri adres seçicisi görünmez; her aktör yalnızca kendi iş akışına odaklanır.""")

    # =========================================================================
    # SLAYT 15: YENİ MODERN ARAYÜZ VİTRİNİ 1 (GERÇEK EKRAN GÖRÜNTÜLERİ!)
    # =========================================================================
    s14 = prs.slides.add_slide(blank)
    add_chrome(s14, 15, "14. Modern FoodTech Arayüz Vitrini — Bölüm 1",
               "Yeni Cravelo Arayüzü: Karşılama, Giriş/Kayıt ve Müşteri Portalı",
               "DoorDash / Uber Eats / Wolt standardında yenilenen, Açık & Koyu Mod destekli canlı uygulama ekran görüntüleri.")

    showcase14 = [
        ("01 • KARŞILAMA", "7-Katman Burger Scrollytelling", SCREENSHOTS["landing"], C_CORAL, [
            "GSAP ScrollTrigger 7 katmanlı animasyon",
            "Bento canlı metrikler & 4 rol vitrini",
            "Sıfır ham ızgara koordinatı, %100 Cravelo",
        ]),
        ("02 • KİMLİK DOĞRULAMA", "Modern Giriş / Kayıt (HTTP 303 PRG)", SCREENSHOTS["login"], C_CYAN, [
            "Lüks cam (glassmorphic) split auth kartı",
            "10 kullanıcı tek tıkla demo seçici",
            "Bcrypt doğrulama & sınır değer koruması",
        ]),
        ("03 • KOYU MOD", "Müşteri Portalı — Obsidian Glass", SCREENSHOTS["customer_dark"], C_GOLD, [
            "Squircle kategoriler & flaş fırsat kartları",
            "16:9 restoran vitrini & akıllı sıralama",
            "Yapışkan (sticky) sepet & Gel-Al anahtarı",
        ]),
        ("04 • AÇIK MOD", "Müşteri Portalı — Pure Pearl", SCREENSHOTS["customer_light"], C_EMERALD, [
            "Buzlu beyaz glass topbar (sıfır gri ton)",
            "Yüksek kontrastlı saf beyaz ürün kartları",
            "Tek tıkla kesintisiz Koyu ↔ Açık geçişi",
        ]),
    ]
    cw14 = (CONTENT_W - 0.28) / 2.0   # 5.926"
    ch14 = (CONTENT_H - 0.22) / 2.0   # 2.54"
    img_w14 = 3.56                    # exact 16:10 -> 3.56 x 2.225
    img_h14 = 2.225

    for idx, (badge, title14, img_p, col, bullets) in enumerate(showcase14):
        c = idx % 2
        r = idx // 2
        cx = MARGIN_X + c * (cw14 + 0.28)
        cy = CONTENT_TOP + r * (ch14 + 0.22)
        add_card(s14, cx, cy, cw14, ch14, bg_color=C_CARD, border_color=C_BORDER, accent_color=col, accent_pos="left")

        ok = add_framed_screenshot(s14, img_p, cx + 0.16, cy + 0.16, img_w14, img_h14, title14, accent_color=col)
        if ok:
            embedded_images.append(img_p.name)

        tx = cx + 0.16 + img_w14 + 0.16
        tw = cw14 - (0.16 + img_w14 + 0.28)
        add_pill(s14, tx, cy + 0.18, tw, 0.28, badge, C_CARD_ALT, col, border_color=col, font_size=8.2)
        tb = add_textbox(s14, tx, cy + 0.54, tw, ch14 - 0.68)
        tf = tb.text_frame
        add_para(tf, title14, font_size=11.0, color=C_WHITE, bold=True, space_after=6, first=True)
        for b in bullets:
            add_para(tf, "▸ " + b, font_size=9.2, color=C_TEXT, space_after=4)

    set_notes(s14, """[Yeni Modern Arayüz Vitrini 1 — Canlı Ekran Görüntüleri]
• Bu slaytta yenilenen Cravelo arayüzümüzün canlı çalışan 1440×900 ekran görüntülerini görüyorsunuz:
  1) 7 katmanlı GSAP ScrollTrigger burger scrollytelling karşılama ekranı.
  2) HTTP 303 PRG ve Bcrypt ile korunan modern giriş/kayıt ekranı.
  3) Müşteri Portalı Koyu Mod (Obsidian Glass) ve
  4) Müşteri Portalı Açık Mod (Pure Pearl) — her iki temada da sıfır kontrast sorunu, ham koordinat gizli ve gerçek Kadıköy mahalle isimleri görünüyor.""")

    # =========================================================================
    # SLAYT 16: YENİ MODERN ARAYÜZ VİTRİNİ 2 (KDS, KURYE, YÖNETİCİ)
    # =========================================================================
    s15 = prs.slides.add_slide(blank)
    add_chrome(s15, 16, "15. Modern FoodTech Arayüz Vitrini — Bölüm 2",
               "Operasyonel Paneller: Restoran KDS, Kurye Kokpiti ve Yönetici Merkezi",
               "Üç operasyon rolü için özel tasarlanmış canlı ekranlar — tüm haritalar 0 ücretli API ile sabit Kadıköy koordinatlarını çizer.")

    showcase15 = [
        ("RESTORAN KDS", "Restoran Mutfak Ekranı", SCREENSHOTS["restaurant_kds"], C_CORAL, "#view-restaurant", [
            ("Canlı Mutfak Kartları:", "Alındı ➔ Hazırlanıyor ➔ Hazır akışı tek tıkla ilerler."),
            ("Otomatik Kurye Tetikleme:", "'Hazır' basıldığında Haversine ataması başlar."),
            ("KPI & Menü Stok:", "Günlük ciro, aktif sipariş adedi ve stok aç/kapa."),
        ]),
        ("KURYE KOKPİTİ", "Kurye Teslimat & Harita Paneli", SCREENSHOTS["courier_cockpit"], C_CYAN, "#view-courier", [
            ("Akıllı Görev Kartları:", "Gerçek Kadıköy sokak adları ve skor dökümü rozetleri."),
            ("Etkileşimli Harita:", "Rota çerçevesi ve canlı HUD durum göstergeleri."),
            ("Tek Tıkla İlerletme:", "'Restorandan Aldım (Yolda)' ➔ 'Teslim Ettim' akışı."),
        ]),
        ("YÖNETİCİ MERKEZİ", "Admin Kontrol & Filo Radar", SCREENSHOTS["admin_center"], C_EMERALD, "#view-admin", [
            ("Executive KPI Bento:", "Toplam ciro, aktif filo, 4-rol canlı önizleme çubuğu."),
            ("Şehir Radar Haritası:", "Restoran, Kurye ve Müşteri katman filtreleme."),
            ("Filo & Audit Log:", "Kurye iş yükü çubukları ve order_status_log tablosu."),
        ]),
    ]
    cw15 = (CONTENT_W - 0.36) / 3.0   # 3.924"
    img_w15 = cw15 - 0.32             # 3.604"
    img_h15 = 2.25                    # exact 16:10

    for idx, (badge, title15, img_p, col, dom_id, bullets) in enumerate(showcase15):
        cx = MARGIN_X + idx * (cw15 + 0.18)
        add_card(s15, cx, CONTENT_TOP, cw15, CONTENT_H, bg_color=C_CARD, border_color=C_BORDER, accent_color=col, accent_pos="top")

        ok = add_framed_screenshot(s15, img_p, cx + 0.16, CONTENT_TOP + 0.16, img_w15, img_h15, title15, accent_color=col)
        if ok:
            embedded_images.append(img_p.name)

        add_pill(s15, cx + 0.16, CONTENT_TOP + 2.52, 1.72, 0.28, badge, C_CARD_ALT, col, border_color=col, font_size=8.5)
        add_pill(s15, cx + cw15 - 1.76, CONTENT_TOP + 2.52, 1.60, 0.28, dom_id, C_BG, C_MUTED, border_color=C_BORDER, font_size=8.2, bold=False)

        tb = add_textbox(s15, cx + 0.18, CONTENT_TOP + 2.90, cw15 - 0.36, CONTENT_H - 3.04)
        tf = tb.text_frame
        add_para(tf, title15, font_size=13, color=C_WHITE, bold=True, space_after=6, first=True)
        for lead, body in bullets:
            add_rich_bullet(tf, lead, body, lead_color=col, body_color=C_TEXT, font_size=9.8, space_after=6)

    set_notes(s15, """[Yeni Modern Arayüz Vitrini 2 — Operasyonel Paneller]
• Müşteri dışındaki 3 operasyonel rolün canlı ekran görüntüleri:
  1) Restoran KDS: Mutfak ekibi gelen siparişleri tek tıkla Hazırlanıyor ve Hazır'a alır; günlük ciro KPI'ları anlık güncellenir.
  2) Kurye Kokpiti: Atanan teslimatlar, Kadıköy sokak adları, harita çerçevesi ve tek tıkla Yolda/Teslim butonları.
  3) Yönetici Merkezi (Mustafa Güreşir): Bento KPI'lar, 4-rol önizleme çubuğu, şehir radar haritası, kurye iş yükü ilerleme çubukları ve denetim günlüğü.""")

    # =========================================================================
    # SLAYT 17: ETKİLEŞİMLİ MODALLAR, AKILLI ÖZELLEŞTİRİCİ & KISMİ AJAX
    # =========================================================================
    s16 = prs.slides.add_slide(blank)
    add_chrome(s16, 17, "16. Etkileşim & Reaktif Senkronizasyon",
               "8 Etkileşimli Modal, Akıllı Özelleştirici ve Kısmi AJAX Yenileme",
               "Hibrit mimari: Auth/formlarda HTTP 303 PRG güvenliği, canlı sipariş ve KDS akışlarında F5'siz kısmi AJAX hızı.")

    # Left: 8 Modals as 2x4 clean tiles inside a container
    lw16 = 7.45
    add_card(s16, MARGIN_X, CONTENT_TOP, lw16, CONTENT_H, bg_color=C_CARD, border_color=C_BORDER, accent_color=C_CYAN, accent_pos="top")
    add_textbox(s16, MARGIN_X + 0.18, CONTENT_TOP + 0.14, lw16 - 0.36, 0.32,
                "🪟  8 Etkileşimli Açılır Modal Katmanı (views/dashboard/_modals.php)", font_size=12, color=C_CYAN, bold=True)

    modals = [
        ("🛒 1. Sepetim", "Dinamik ürün sayacı rozeti, adet kontrolü, z-index: 60 kapatma."),
        ("🍔 2. Akıllı Özelleştirici", "Kategori duyarlı: tatlıda dondurma/fıstık, burgerde cheddar/sos."),
        ("💳 3. Ödeme & Gel-Al", "Adrese Teslim vs Gel-Al (%20 indirim), kupon & bahşiş."),
        ("📍 4. Adres & Konum", "Etkileşimli harita pini, kayıtlı Kadıköy adresi ekle/sil."),
        ("📦 5. 5-Adım Canlı Takip", "Yalnızca atanmış kuryeyi gösteren harita + iptal kilidi."),
        ("💬 6. Kurye Sohbet", "Müşteri–kurye çift yönlü anlık mesaj ve hızlı şablonlar."),
        ("🧾 7. Dijital Fiş / Fatura", "Teslim sonrası KDV dökümlü ve barkodlu sipariş özeti."),
        ("⭐ 8. Puanlama & Grup", "5 yıldızlı lezzet/hız değerlendirmesi ve ortak grup sepeti."),
    ]
    mw16 = (lw16 - 0.52) / 2.0
    mh16 = 1.04
    for idx, (mtitle, mdesc) in enumerate(modals):
        mc = idx % 2
        mr = idx // 2
        mx = MARGIN_X + 0.18 + mc * (mw16 + 0.16)
        my = CONTENT_TOP + 0.56 + mr * (mh16 + 0.12)
        add_card(s16, mx, my, mw16, mh16, bg_color=C_CARD_ALT, border_color=C_BORDER, radius=0.08)
        tb = add_textbox(s16, mx + 0.12, my + 0.08, mw16 - 0.24, mh16 - 0.14)
        tf = tb.text_frame
        add_para(tf, mtitle, font_size=10.5, color=C_GOLD, bold=True, space_after=2, first=True)
        add_para(tf, mdesc, font_size=9.2, color=C_TEXT, space_after=0)

    # Right: Partial AJAX Reactive Architecture
    rx16 = MARGIN_X + lw16 + 0.28
    rw16 = CONTENT_W - lw16 - 0.28
    add_card(s16, rx16, CONTENT_TOP, rw16, CONTENT_H, bg_color=C_CARD_ALT, border_color=C_EMERALD, accent_color=C_EMERALD, accent_pos="top")
    tb_aj = add_textbox(s16, rx16 + 0.20, CONTENT_TOP + 0.16, rw16 - 0.40, CONTENT_H - 0.88)
    tf = tb_aj.text_frame
    add_para(tf, "⚡  Kısmi AJAX Reaktif Yenileme", font_size=13, color=C_MINT, bold=True, space_after=7, first=True)

    ajax_pts = [
        ("Gerçek fetch() Kısmi Yenileme:", "craveloApiFetch() tek giriş noktası (/api/order-status, /api/kds-orders, /api/courier-tasks) üzerinden JSON çeker."),
        ("HTTP Hata Yönetimi:", "Beklenmeyen HTTP 400/403/500 veya ağ hatasında sunucu mesajı toast ile kullanıcıya güvenle gösterilir."),
        ("Sunucu Tarafı Kural Koruması:", "Sipariş geçişi ve iptal kuralları yalnızca tarayıcıda değil sunucuda OrderService + StateMachine ile denetlenir."),
        ("Akıllı Kategori Profili:", "getItemCategoryProfile() tatlıda ketçap yerine dondurma/fıstık opsiyonu sunar."),
    ]
    for lead, body in ajax_pts:
        add_rich_bullet(tf, lead, body, lead_color=C_WHITE, body_color=C_TEXT, font_size=10.0, space_after=6)

    add_pill(s16, rx16 + 0.20, CONTENT_TOP + CONTENT_H - 0.56, rw16 - 0.40, 0.40,
             "Hibrit Mimari: PRG (Auth) + AJAX (Canlı Takip)", C_CYAN_BG, C_CYAN, border_color=C_CYAN, font_size=9.2)

    set_notes(s16, """[8 Modal, Akıllı Özelleştirici & Kısmi AJAX]
• Uygulamada 8 tam işlevsel modal katmanı var: Sepet, Akıllı Özelleştirici, Ödeme/Gel-Al, Adres Seçici, 5-Adım Canlı Takip, Kurye Sohbet, Dijital Fatura ve Puanlama/Grup Siparişi.
• Akıllı Özelleştirici kategori farkındalığına sahip: tatlı seçildiğinde ketçap/mayonez değil dondurma ve Antep fıstığı sunar.
• Kısmi AJAX yenileme ile restoran veya kurye aksiyonları F5 gerekmeden müşteri paneline anında yansır.""")

    # =========================================================================
    # SLAYT 18: HOCANIN 3 RESMİ DOĞRULAMA KANITI & MUTASYON TESTİ DENEYİ
    # =========================================================================
    s17 = prs.slides.add_slide(blank)
    add_chrome(s17, 18, "17. Resmi Şartname Kanıtları & Mutasyon Laboratuvarı",
               "Hocanın 3 Resmi Doğrulama Kanıtı ve Mutasyon Testi Deneyleri",
               "Şartnamede sözlü sınav kanıtı olarak istenen 3 deney + testlerin gerçekten kuralı ölçtüğünü ispatlayan 2 mutasyon.")

    # Top row: 3 Official Proofs
    proofs = [
        ("🏆 KANIT 1: Geçersiz Geçiş Reddi", C_CORAL, [
            ("Kriter:", "Yoldaki siparişin mutfağa dönüşünün reddi."),
            ("Çözüm:", "OnWayState ➔ InvalidOrderTransitionException (HTTP 400)."),
            ("Kanıt:", "vendor/bin/phpunit (OrderStateMachineTest) ✅"),
        ]),
        ("🏆 KANIT 2: Doğru Kurye Seçimi", C_EMERALD, [
            ("Kriter:", "İki kurye arasında mesafe + iş yükü testi."),
            ("Çözüm:", "Ali (4.71) elenir, boşta Veli (1.31) atanır."),
            ("Kanıt:", "vendor/bin/phpunit (CourierAssignmentStrategyTest) ✅"),
        ]),
        ("🏆 KANIT 3: F5'siz Canlı Takip", C_CYAN, [
            ("Kriter:", "Müşteri panelinin sayfa yenilemeden güncellenmesi."),
            ("Çözüm:", "craveloApiFetch() ➔ /api/order-status JSON & hata toast."),
            ("Kanıt:", "Tek Giriş Noktalı AJAX & Playwright doğrulama ✅"),
        ]),
    ]
    pw17 = (CONTENT_W - 0.36) / 3.0
    ph17 = 2.28
    for idx, (pt, pcol, pts) in enumerate(proofs):
        px = MARGIN_X + idx * (pw17 + 0.18)
        add_card(s17, px, CONTENT_TOP, pw17, ph17, bg_color=C_CARD, border_color=pcol, accent_color=pcol, accent_pos="top")
        tb = add_textbox(s17, px + 0.16, CONTENT_TOP + 0.16, pw17 - 0.32, ph17 - 0.28)
        tf = tb.text_frame
        add_para(tf, pt, font_size=11.5, color=pcol, bold=True, space_after=7, first=True)
        for lead, body in pts:
            add_rich_bullet(tf, lead, body, lead_color=C_WHITE, body_color=C_TEXT, font_size=10.0, space_after=5)

    # Bottom section: 2 Mutation Testing Experiments
    my17 = CONTENT_TOP + ph17 + 0.22
    mh17 = CONTENT_H - ph17 - 0.22  # 2.80"
    mw17 = (CONTENT_W - 0.28) / 2.0

    # Mutation 1
    add_card(s17, MARGIN_X, my17, mw17, mh17, bg_color=C_CARD_ALT, border_color=C_GOLD, accent_color=C_GOLD, accent_pos="left")
    tb_m1 = add_textbox(s17, MARGIN_X + 0.22, my17 + 0.14, mw17 - 0.38, mh17 - 0.24)
    tf = tb_m1.text_frame
    add_para(tf, "🔬  MUTASYON DENEYİ 1: PreparingState::canCancel() (FR-03)", font_size=11.5, color=C_GOLD, bold=True, space_after=5, first=True)
    add_para(tf, "- return false;   ➔   + return true;  // Kasıtlı kural bozuldu!",
             font_size=9.5, color=C_RED, font_name=FONT_MONO, bold=True, space_after=6)
    add_rich_bullet(tf, "Mutasyon Sonucu (FAIL):", "OrderStateMachineTest::testOrderCannotBeCancelledWhilePreparing anında KIRMIZI verdi: 'Failed asserting DomainException is thrown.'", lead_color=C_RED, body_color=C_TEXT, font_size=9.8, space_after=5)
    add_rich_bullet(tf, "Geri Alma (Rollback):", "git checkout sonrası tekrar OK (Yeşil) — testin iptal kilidini gerçekten ölçtüğü kanıtlandı.", lead_color=C_MINT, body_color=C_TEXT, font_size=9.8, space_after=0)

    # Mutation 2
    add_card(s17, MARGIN_X + mw17 + 0.28, my17, mw17, mh17, bg_color=C_CARD_ALT, border_color=C_PURPLE, accent_color=C_PURPLE, accent_pos="left")
    tb_m2 = add_textbox(s17, MARGIN_X + mw17 + 0.50, my17 + 0.14, mw17 - 0.38, mh17 - 0.24)
    tf = tb_m2.text_frame
    add_para(tf, "🔬  MUTASYON DENEYİ 2: ScoreBasedAssignmentStrategy (FR-05/06)", font_size=11.5, color=C_PURPLE, bold=True, space_after=5, first=True)
    add_para(tf, "- $score = $dist + 2*$active;   ➔   + $score = $dist;  // Ceza silindi!",
             font_size=9.5, color=C_RED, font_name=FONT_MONO, bold=True, space_after=6)
    add_rich_bullet(tf, "Mutasyon Sonucu (FAIL):", "CourierAssignmentStrategyTest beklenen 'Veli Demir' yerine 'Ali Kaya' görünce anında KIRMIZI yakaladı.", lead_color=C_RED, body_color=C_TEXT, font_size=9.8, space_after=5)
    add_rich_bullet(tf, "Geri Alma (Rollback):", "Formül geri yüklendiğinde tekrar OK (Yeşil) — +2×Aktif çarpanının test güvencesi ispatlandı.", lead_color=C_MINT, body_color=C_TEXT, font_size=9.8, space_after=0)

    set_notes(s17, """[Hocanın 3 Resmi Kanıtı & Mutasyon Testi]
• Şartnamedeki 3 resmi kanıt (geçersiz geçiş reddi, doğru kurye seçimi, F5'siz canlı takip) hem canlı arayüzde hem test paketinde çalışır.
• Ayrıca yönergedeki 'kuralı bozduğunuzda testin kırmızıya düştüğünü gösterin' şartı için iki kontrollü mutasyon deneyi yaptık:
  1) PreparingState::canCancel() metodunu true yapınca OrderStateMachineTest derhal FAIL verdi.
  2) Kurye puanlamasındaki +2×Aktif cezasını silince test Ali'yi seçtiğini görüp FAIL yakaladı.
• Bu deneyler testlerimizin sahte yeşil olmadığını kesin biçimde kanıtlar.""")

    # =========================================================================
    # SLAYT 19: OTOMATİK TEST PAKETİ (312 TEST), PHPUNIT 11 & PHPSTAN L5
    # =========================================================================
    s18 = prs.slides.add_slide(blank)
    add_chrome(s18, 19, "18. Kalite Güvencesi, PHPUnit, PHPStan & İzlenebilirlik",
               f"Otomatik Test Paketi ({TEST_STATS['total']} Test) ve PHPStan Seviye 5 (0 Hata)",
               "Hocanın 'en az 10 test' beklentisinin 31 kat üzerinde; gerçek vendor/bin/phpunit ve vendor/bin/phpstan doğrulamalı altyapı.")

    # Left: Test Engine Breakdown + FR-01..FR-16 Traceability Table
    lw18 = 7.75
    add_card(s18, MARGIN_X, CONTENT_TOP, lw18, 2.58, bg_color=C_CARD, border_color=C_BORDER, accent_color=C_CYAN, accent_pos="top")
    tb_ts = add_textbox(s18, MARGIN_X + 0.18, CONTENT_TOP + 0.14, lw18 - 0.36, 2.34)
    tf = tb_ts.text_frame
    add_para(tf, "🧪  4 Katmanlı Test & Statik Analiz Altyapısı (TESTLERI_CALISTIR.bat)", font_size=11.8, color=C_CYAN, bold=True, space_after=5, first=True)
    add_rich_bullet(tf, f"PHPUnit 11.5 (vendor/bin/phpunit — {TEST_STATS['phpunit_count']} Test, {TEST_STATS['phpunit_assertions']} Assertion):",
                    "8 sınıf (State, Strategy, Flock, JsonRepo, Controller, Security/IDOR, PlatformFeatures) — InMemory & JSON.",
                    lead_color=C_GOLD, body_color=C_TEXT, font_size=9.4, space_after=5)
    add_rich_bullet(tf, "PHPStan 2.3 (vendor/bin/phpstan — Seviye 5: [OK] No errors):",
                    "src/ dizinindeki tüm 45+ PHP sınıfı tip güvenliği ve ölü kod açısından 0 hata ile doğrulandı.",
                    lead_color=C_CYAN, body_color=C_TEXT, font_size=9.4, space_after=5)
    add_rich_bullet(tf, f"Bağımsız PHP ({TEST_STATS['php_count']}) & Python ({TEST_STATS['python_count']}):",
                    "run_tests.php (20), architectural PHP (12), Python PRG/BasePath/flock (31), CMD şartname (20), 3 kanıt (8).",
                    lead_color=C_CORAL, body_color=C_TEXT, font_size=9.4, space_after=5)
    add_rich_bullet(tf, f"Node.js Arayüz, Modüler Özellik & RBAC ({TEST_STATS['node_count']}/215 — 7 Dosya):",
                    "Scrollytelling (58), landing (35), auth (24), adres/KDS (31), RBAC (42), denetim (10), platform UI (15).",
                    lead_color=C_MINT, body_color=C_TEXT, font_size=9.4, space_after=0)

    # Bottom-Left: Compact FR-01..FR-16 Traceability Summary Table
    tbl_shp18 = s18.shapes.add_table(5, 3, Inches(MARGIN_X), Inches(CONTENT_TOP + 2.74), Inches(lw18), Inches(2.56))
    tbl18 = tbl_shp18.table
    for ci, w in enumerate([1.65, 3.35, 2.75]):
        tbl18.columns[ci].width = Inches(w)

    for ci, h in enumerate(["SRS FR Grubu", "Doğrulanan Gereksinimler (docs/izlenebilirlik.md)", "Eşleşen Test Paketi"]):
        style_table_cell(tbl18.cell(0, ci), h, C_CARD_HI, C_CORAL, font_size=9.5, bold=True)

    tr_rows = [
        ("FR-01 .. FR-03", "Sıralı durum akışı, ters geçiş engeli, pişen yemek iptal kilidi", "OrderStateMachineTest ✅"),
        ("FR-04 .. FR-07", "Haversine mesafesi, Puan = d + 2×Aktif, boş kurye kuyruğu", "CourierAssignmentTest ✅"),
        ("FR-08 .. FR-11", "7 varlık, flock(LOCK_EX) kilidi, .tmp atomik takas, eşzamanlılık", "JsonStorageFlockTest ✅"),
        ("FR-12 .. FR-16 + NFR", "F5'siz AJAX, günlük ciro, 7/27 kota, Bcrypt, 4 sınır testi, IDOR", "SecurityAndAuth + Node ✅"),
    ]
    for ri, row in enumerate(tr_rows):
        bg = C_CARD if ri % 2 == 0 else C_CARD_ALT
        for ci, val in enumerate(row):
            style_table_cell(tbl18.cell(ri + 1, ci), val, bg,
                             text_color=C_CYAN if ci == 0 else (C_MINT if ci == 2 else C_TEXT),
                             font_size=9.2, bold=(ci in (0, 2)))

    # Right: Hero Score Spotlight Card
    rx18 = MARGIN_X + lw18 + 0.28
    rw18 = CONTENT_W - lw18 - 0.28
    add_card(s18, rx18, CONTENT_TOP, rw18, CONTENT_H, bg_color=C_GREEN_BG, border_color=C_EMERALD, accent_color=C_EMERALD, accent_pos="top")
    tb_sc = add_textbox(s18, rx18 + 0.20, CONTENT_TOP + 0.24, rw18 - 0.40, CONTENT_H - 0.42)
    tf = tb_sc.text_frame
    add_para(tf, "🏆  DOĞRULANMIŞ TEST SKORU", font_size=11.5, color=C_GOLD, bold=True, align=PP_ALIGN.CENTER, space_after=8, first=True)
    add_para(tf, TEST_STATS["summary_badge"], font_size=34, color=C_MINT, bold=True, align=PP_ALIGN.CENTER, space_after=4)
    add_para(tf, "%100 BAŞARI — SIFIR HATA", font_size=12, color=C_WHITE, bold=True, align=PP_ALIGN.CENTER, space_after=14)
    add_para(tf, TEST_STATS["detail_line"], font_size=10.0, color=C_CYAN, bold=True, align=PP_ALIGN.CENTER, space_after=14)

    sc_pts = [
        "▸ vendor/bin/phpunit: 54 Test (247 Assert)",
        "▸ vendor/bin/phpstan: Level 5 — 0 Hata",
        "▸ InMemory*Repository (0 Disk I/O)",
        "▸ 4 ayrık giriş sınır testi (2/3 & 5/6)",
        "▸ Gerçek LOCK_EX | LOCK_NB & 2 mutasyon",
        "▸ Tek tıkla: TESTLERI_CALISTIR.bat",
    ]
    for sp in sc_pts:
        add_para(tf, sp, font_size=10.0, color=C_TEXT, space_after=5)

    set_notes(s18, f"""[Otomatik Test Paketi, PHPUnit 11 & PHPStan L5]
• Hocamız en az 10 PHPUnit testi istemişti; gerçek vendor/bin/phpunit koşucumuzda 54 birim testi (247 assertion) %100 yeşil geçiyor.
• Ayrıca vendor/bin/phpstan analyse (Seviye 5) çalıştırdık ve tüm src/ dizininde 0 hata aldık.
• Bağımsız PHP (32), Python (59) ve Node.js (215) testleriyle toplam {TEST_STATS['total']} test %100 yeşil geçmektedir.
• docs/izlenebilirlik.md belgemizde Aşama 1'deki FR-01..FR-16 ve NFR-01/02 gereksinimleri test metotlarıyla birebir eşleştirilmiştir.""")

    # =========================================================================
    # SLAYT 20: 15 DAKİKALIK TAHTADA CANLI DEMO AKIŞI (7 ADIM & ZAMAN DAĞILIMI)
    # =========================================================================
    s19 = prs.slides.add_slide(blank)
    add_chrome(s19, 20, "19. Canlı Savunma Koreografisi",
               "15 Dakikalık Tahtada Canlı Demo Akışı (Hocanın İstediği 7 Adım)",
               "Ders yönergesi Madde 9 sırasına birebir uygun 7 canlı demo adımı ve Mustafa Güreşir bireysel sunum zaman planı.")

    # Top 15-Minute Proportional Timeline Bar
    add_card(s19, MARGIN_X, CONTENT_TOP, CONTENT_W, 1.10, bg_color=C_CARD_ALT, border_color=C_BORDER, radius=0.08)
    add_textbox(s19, MARGIN_X + 0.18, CONTENT_TOP + 0.08, CONTENT_W - 0.36, 0.24,
                "⏱️  15 DAKİKALIK BİREYSEL SUNUM ZAMAN DAĞILIMI (Mustafa Güreşir)", font_size=9.5, color=C_GOLD, bold=True)

    segments = [
        ("0–3 dk\nGiriş & Problem", 3 / 15.0, C_CORAL),
        ("3–8 dk\n7 Adımlı Canlı Demo", 5 / 15.0, C_CYAN),
        ("8–11 dk\nMimari & flock()", 3 / 15.0, C_EMERALD),
        ("11–13 dk\nPHPUnit, PHPStan & AI", 2 / 15.0, C_PURPLE),
        ("13–15 dk\nKod Savunması", 2 / 15.0, C_GOLD),
    ]
    seg_total_w = CONTENT_W - 0.36 - (0.08 * 4)
    cur_x = MARGIN_X + 0.18
    for slbl, frac, scol in segments:
        sw_seg = seg_total_w * frac
        add_pill(s19, cur_x, CONTENT_TOP + 0.38, sw_seg, 0.58, slbl, C_BG, scol, border_color=scol, font_size=9.0, wrap=True)
        cur_x += sw_seg + 0.08

    # 7 Demo Step Cards: Top Row (4 cards) + Bottom Row (3 wider cards)
    steps_top = [
        ("1. Login, PRG & Hatalı Form", "Hatalı formda old input koruması + CSRF; ahmet_musteri ile Bcrypt giriş ve HTTP 303 PRG ile F5 koruması.", C_CYAN),
        ("2. Sipariş & Kurye Seçimi", "Sepet + akıllı özelleştirici; Ali (4.71) yerine boşta Veli'nin (1.31) otomatik atanması.", C_EMERALD),
        ("3. İptal & Geri Dönüş Yasağı", "Restoran KDS 'Hazırlanıyor' yapar ➔ İptal denemesi InvalidOrderTransitionException (HTTP 400) ile reddedilir.", C_CORAL),
        ("4. IDOR Yetki Denetimi", "Başkasının sipariş ID'sine URL erişimi ➔ assertCanAccessOrder() ile HTTP 403 Forbidden engeli.", C_RED),
    ]
    w_top = (CONTENT_W - 0.18 * 3) / 4.0
    y_top = CONTENT_TOP + 1.30
    h_card = 1.90
    for idx, (st, sd, scol) in enumerate(steps_top):
        sx = MARGIN_X + idx * (w_top + 0.18)
        add_card(s19, sx, y_top, w_top, h_card, bg_color=C_CARD, border_color=C_BORDER, accent_color=scol, accent_pos="top")
        tb = add_textbox(s19, sx + 0.16, y_top + 0.16, w_top - 0.32, h_card - 0.28)
        tf = tb.text_frame
        add_para(tf, st, font_size=11.5, color=scol, bold=True, space_after=6, first=True)
        add_para(tf, sd, font_size=9.8, color=C_TEXT, space_after=0)

    steps_bot = [
        ("5. Kısmi AJAX (DevTools Network)", "Restoran 'Hazır' / Kurye 'Yolda' yapınca craveloApiFetch() /api/order-status JSON yanıtıyla F5'siz günceller; hata durumunda toast gösterir.", C_GOLD),
        (f"6. PHPUnit (54) + PHPStan L5 + {TEST_STATS['total']} Test", "vendor/bin/phpunit (2/3 & 5/6 sınır testleri dahil), vendor/bin/phpstan (0 hata) ve TESTLERI_CALISTIR.bat canlı çalıştırılır.", C_MINT),
        ("7. Mutasyon Kanıtı & Zorunlu Belgeler", "PreparingState::canCancel() mutasyon deneyi + docs/izlenebilirlik.md, docs/ai-gunlugu.md (SQLite hata vakası) ve docs/surec-raporu.md sunulur.", C_PURPLE),
    ]
    w_bot = (CONTENT_W - 0.18 * 2) / 3.0
    y_bot = y_top + h_card + 0.22
    for idx, (st, sd, scol) in enumerate(steps_bot):
        sx = MARGIN_X + idx * (w_bot + 0.18)
        add_card(s19, sx, y_bot, w_bot, h_card, bg_color=C_CARD, border_color=C_BORDER, accent_color=scol, accent_pos="top")
        tb = add_textbox(s19, sx + 0.18, y_bot + 0.16, w_bot - 0.36, h_card - 0.28)
        tf = tb.text_frame
        add_para(tf, st, font_size=11.5, color=scol, bold=True, space_after=6, first=True)
        add_para(tf, sd, font_size=9.8, color=C_TEXT, space_after=0)

    set_notes(s19, """[15 Dakikalık Tahtada Canlı Demo Akışı — Mustafa Güreşir]
• Canlı demo için 5 dakikalık bloğumuzda ders yönergesindeki 7 adımı sırayla göstereceğiz:
  1) Hatalı form (old input + CSRF) -> Login + HTTP 303 PRG,
  2) Sipariş + Ali (4.71) vs Veli (1.31) otomatik ataması,
  3) Mutfak 'Hazırlanıyor' sonrası iptal yasağı (HTTP 400),
  4) IDOR yetkisiz erişim engeli (HTTP 403),
  5) DevTools Network sekmesinde /api/order-status gerçek fetch() kısmi AJAX yenilemesi ve hata bildirimi,
  6) vendor/bin/phpunit (54 test + sınır testleri), vendor/bin/phpstan (0 hata) ve TESTLERI_CALISTIR.bat (360 test),
  7) PreparingState::canCancel() mutasyon kanıtı + docs/izlenebilirlik.md, docs/ai-gunlugu.md ve docs/surec-raporu.md.""")

    # =========================================================================
    # SLAYT 21: KAPANIŞ, BİREYSEL KOD SAVUNMASI & JÜRİ TUZAK SORU-CEVAP
    # =========================================================================
    s20 = prs.slides.add_slide(blank)
    add_chrome(s20, 21, "20. Bireysel Savunma & Jüri Rehberi",
               "Bireysel Kod Satırı Savunması ve Jüri Tuzak Soru-Cevap Rehberi",
               "Cravelo — Aşama 2 Bireysel Savunması (Mustafa Güreşir). Sorularınız ve canlı kod incelemesi için hazırım!")

    # Top Table: 5 Critical Code Lines for Individual Defense
    tbl_shp20 = s20.shapes.add_table(6, 3, Inches(MARGIN_X), Inches(CONTENT_TOP), Inches(CONTENT_W), Inches(2.44))
    tbl20 = tbl_shp20.table
    for ci, w in enumerate([3.30, 4.15, 4.683]):
        tbl20.columns[ci].width = Inches(w)

    for ci, h in enumerate(["Kritik Dosya", "Savunulacak Kod Satırı", "Ne İşe Yarar? (1 Cümle Savunma)"]):
        style_table_cell(tbl20.cell(0, ci), h, C_CARD_HI, C_CORAL, font_size=9.8, bold=True)

    code_rows = [
        ("src/Storage/JsonStorageHelper.php", "flock($fp, LOCK_EX);  ...  rename($tmp, $file);", "Eşzamanlı yazma çakışmasını kilitler ve atomik dosya takası yapar."),
        ("src/Service/State/PreparingState.php", "public function canCancel(): bool { return false; }", "Mutfakta pişen yemeğin müşteri tarafından iptalini kilitleyen çekirdek kural."),
        ("src/Service/State/OnWayState.php", "throw new InvalidOrderTransitionException(...);", "Yoldaki paketin geriye mutfağa veya alındı aşamasına dönüşünü engeller."),
        ("src/Service/Courier/ScoreBasedStrategy.php", "$score = round($distKm + (2.0 * $active), 2);", "Haversine mesafesi ile iş yükü cezasını birleştiren adil kurye puanı."),
        ("src/Service/OrderService.php", "assertSessionUserCanAccessOrder($order, $user)", "Controller→Repo atlamasını önler; URL IDOR erişimini 403 ile engeller."),
    ]
    for ri, row in enumerate(code_rows):
        bg = C_CARD if ri % 2 == 0 else C_CARD_ALT
        for ci, val in enumerate(row):
            style_table_cell(
                tbl20.cell(ri + 1, ci), val, bg,
                text_color=C_CYAN if ci == 0 else (C_GOLD if ci == 1 else C_TEXT),
                font_size=9.1, bold=(ci == 0),
                font_name=FONT_MONO if ci in (0, 1) else FONT_MAIN,
            )

    # Bottom 3 Jury Trap Q&A Cards
    qas = [
        ("❓ '10 kişi aynı anda sipariş verirse JSON bozulmaz mı?'", C_CYAN,
         "Bozulmaz hocam. JsonStorageHelper içinde flock(LOCK_EX) ile OS yazma kilidi alıyoruz; veriyi .tmp dosyasına yazıp fflush() sonrası atomik rename() ile tek hamlede takas ediyoruz."),
        ("❓ 'Neden yakındaki Ali yerine uzaktaki Veli seçildi?'", C_EMERALD,
         "Puan = Mesafe + 2×Aktif kuralı gereği hocam. Ali 0.71 km yakın ama 2 aktif göreviyle 4.71 puan alıyor; Veli 1.31 km uzakta ama boşta (1.31 puan). Veli siparişi daha hızlı teslim eder."),
        ("❓ 'MySQL'e geçince ne değişir? IDOR yapılabilir mi?'", C_CORAL,
         "Sadece src/Repository/MySql/* yazılır; Controller/Service'te 0 satır değişir. IDOR yapılamaz: assertCanAccessOrder() oturum sahibi uyuşmazsa anında 403 Forbidden fırlatır."),
    ]
    qw20 = (CONTENT_W - 0.36) / 3.0
    qy20 = CONTENT_TOP + 2.64
    qh20 = 2.66
    for idx, (qtxt, qcol, atxt) in enumerate(qas):
        qx = MARGIN_X + idx * (qw20 + 0.18)
        add_card(s20, qx, qy20, qw20, qh20, bg_color=C_CARD_ALT, border_color=qcol, accent_color=qcol, accent_pos="top")
        tb = add_textbox(s20, qx + 0.16, qy20 + 0.16, qw20 - 0.32, qh20 - 0.28)
        tf = tb.text_frame
        add_para(tf, qtxt, font_size=10.8, color=C_GOLD, bold=True, space_after=8, first=True)
        add_rich_bullet(tf, "Cevap:", atxt, lead_color=qcol, body_color=C_WHITE, font_size=9.8, space_after=0, bullet="💡 ")

    set_notes(s20, """[13:00 - 15:00 | Kapanış & Bireysel Kod Satırı Savunması — Mustafa Güreşir]
• Hocamız ekrandan rastgele bir kod satırı sorduğunda üstteki 5 kritik dosya ve satırı özgüvenle açıklayabilirim:
  - flock + rename (JsonStorageHelper)
  - canCancel() => false (PreparingState)
  - InvalidOrderTransitionException (OnWayState)
  - distKm + 2.0 * active (ScoreBasedAssignmentStrategy)
  - assertSessionUserCanAccessOrder (OrderService)
• Dinlediğiniz için teşekkür ederim Hocam; canlı demo ve sorularınız için hazırım!""")

    # =========================================================================
    # KAYDETME
    # =========================================================================
    primary_out = Path("Cravelo_Asama2_Sunum.pptx")
    prs.save(str(primary_out))
    print(f"[OK] {len(prs.slides)} slaytlik sunum olusturuldu: {primary_out}")
    print(f"[INFO] Gomulen canli ekran goruntuleri ({len(embedded_images)}/7): {', '.join(embedded_images) if embedded_images else 'Henuz yok (placeholder aktif)'}")

    return primary_out, len(embedded_images)


def verify_pptx(pptx_path: Path):
    """Programatik PPTX kalite dogrulamasi: slayt sayisi, sinirlar, metin tasması, notlar, gorseller."""
    prs = Presentation(str(pptx_path))
    issues = []
    pic_count = 0
    notes_count = 0

    if len(prs.slides) != TOTAL_SLIDES:
        issues.append(f"Slayt sayisi {len(prs.slides)}, beklenen {TOTAL_SLIDES}")

    w_in = prs.slide_width / 914400.0
    h_in = prs.slide_height / 914400.0

    for si, slide in enumerate(prs.slides, start=1):
        # Check speaker notes
        if slide.has_notes_slide and slide.notes_slide.notes_text_frame.text.strip():
            notes_count += 1
        else:
            issues.append(f"Slayt {si}: Konusmaci notu (speaker notes) eksik!")

        # Overlap check among all text-bearing shapes, tables, and pictures
        content_boxes = []
        for shp in slide.shapes:
            left = shp.left / 914400.0
            top = shp.top / 914400.0
            sw = shp.width / 914400.0
            sh = shp.height / 914400.0

            # Slide bounds check (tiny 0.02" rounding tolerance)
            if left < -0.02 or top < -0.02 or (left + sw) > (w_in + 0.03) or (top + sh) > (h_in + 0.03):
                issues.append(f"Slayt {si}: Sekil slayt siniri disinda ({left:.2f}, {top:.2f}, {sw:.2f}x{sh:.2f})")

            # Image check
            if shp.shape_type == 13:  # PICTURE
                pic_count += 1
                if sw <= 0.1 or sh <= 0.1:
                    issues.append(f"Slayt {si}: Gecersiz gorsel boyutu ({sw:.2f}x{sh:.2f})")
                content_boxes.append((left, top, left + sw, top + sh, "PICTURE"))
            elif shp.shape_type == 19:  # TABLE
                content_boxes.append((left, top, left + sw, top + sh, "TABLE"))
            elif shp.has_text_frame and shp.text_frame.text.strip():
                lbl = shp.text_frame.text.replace("\n", " ")[:28]
                content_boxes.append((left, top, left + sw, top + sh, lbl))

                if shp.shape_type == 1:  # Pill (AUTO_SHAPE)
                    tf = shp.text_frame
                    if not tf.word_wrap:
                        w_pt = max(10.0, (sw - 0.16) * 72.0)
                        for p in tf.paragraphs:
                            fs = p.font.size.pt if p.font.size else 9.5
                            est_w = len(p.text or "") * fs * 0.56
                            if est_w > w_pt * 1.06:
                                issues.append(f"Slayt {si}: Pill metni sikisik ({est_w:.0f}pt > {w_pt:.0f}pt): '{p.text}'")
                elif shp.shape_type == 17:  # TEXT_BOX
                    tf = shp.text_frame
                    est_h_pt = 0.0
                    usable_w_pt = max(20.0, (sw - 0.04) * 72.0)
                    for p in tf.paragraphs:
                        txt = p.text or ""
                        if not txt:
                            continue
                        fsize = 10.5
                        if p.font.size is not None:
                            fsize = p.font.size.pt
                        elif p.runs and p.runs[0].font.size is not None:
                            fsize = p.runs[0].font.size.pt
                        avg_char_pt = fsize * 0.52
                        chars_per_line = max(1, int(usable_w_pt / avg_char_pt))
                        n_lines = 0
                        for subline in txt.split("\n"):
                            n_lines += max(1, math.ceil(len(subline) / chars_per_line))
                        sp_after = p.space_after.pt if p.space_after is not None else 2.0
                        est_h_pt += n_lines * (fsize * 1.25) + sp_after
                    box_h_pt = sh * 72.0
                    if est_h_pt > box_h_pt * 0.96:
                        preview = (tf.text or "").replace("\n", " ")[:48]
                        issues.append(f"Slayt {si}: Metin tasma riski (tahmini {est_h_pt:.0f}pt > kutu {box_h_pt:.0f}pt): '{preview}...'")

        for i in range(len(content_boxes)):
            for j in range(i + 1, len(content_boxes)):
                a, b = content_boxes[i], content_boxes[j]
                ix = min(a[2], b[2]) - max(a[0], b[0])
                iy = min(a[3], b[3]) - max(a[1], b[1])
                if ix > 0.02 and iy > 0.02:
                    issues.append(f"Slayt {si}: Cakisma (Overlap) [{a[4]}] <-> [{b[4]}] ({ix:.2f}x{iy:.2f}\")")

    print(f"[VERIFY] Slaytlar: {len(prs.slides)}/{TOTAL_SLIDES} | Speaker Notes: {notes_count}/{TOTAL_SLIDES} | Gomulu Gorseller: {pic_count}")
    if issues:
        print(f"[VERIFY] {len(issues)} uyari bulundu:")
        for iss in issues:
            print("  -", iss)
    else:
        print("[VERIFY] SIFIR HATA! Tum sinirlar, metin sigmalari ve konusmaci notlari temiz.")
    return issues


if __name__ == "__main__":
    out_file, _ = build_presentation()
    if "--verify" in sys.argv or True:
        verify_pptx(out_file)
