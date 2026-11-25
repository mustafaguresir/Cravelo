"""
Cravelo Burger Hero Scrollytelling — Photorealistic Gourmet Vector Asset Generator
Generates 7 standalone gourmet burger layer SVGs + 1 steam SVG in public/img/hero/
Features:
- Realistic vertical heights, calibrated stacking clearances (370px tall assembled artisan burger)
- Unified ~18° isometric perspective across all layers
- Pure vector gradients and shadows (zero runtime feDropShadow/feGaussianBlur overhead for 60fps)
- Transparent background (fill="none" root)
- File size > 500 bytes per SVG with strict XML validity
"""
import os
import xml.etree.ElementTree as ET

OUT_DIR = os.path.join("public", "img", "hero")
os.makedirs(OUT_DIR, exist_ok=True)

# -------------------------------------------------------------
# 1. LAYER 1: BOTTOM BUN (Heel) - Volumetric toasted brioche foundation
# -------------------------------------------------------------
layer_1_svg = """<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 500" width="100%" height="100%" fill="none">
  <defs>
    <!-- Base Contact Ambient Shadow (Vector Gradient, 0% CPU blur) -->
    <radialGradient id="baseGroundShadow" cx="50%" cy="50%" r="50%">
      <stop offset="0%" stop-color="#0a0502" stop-opacity="0.65"/>
      <stop offset="40%" stop-color="#0a0502" stop-opacity="0.40"/>
      <stop offset="75%" stop-color="#0a0502" stop-opacity="0.12"/>
      <stop offset="100%" stop-color="#0a0502" stop-opacity="0"/>
    </radialGradient>
    <linearGradient id="crustGrad" x1="0%" y1="100%" x2="0%" y2="0%">
      <stop offset="0%" stop-color="#3b1a04"/>
      <stop offset="20%" stop-color="#5c2907"/>
      <stop offset="45%" stop-color="#8a420b"/>
      <stop offset="70%" stop-color="#bd6919"/>
      <stop offset="90%" stop-color="#db862e"/>
      <stop offset="100%" stop-color="#ebb05b"/>
    </linearGradient>
    <radialGradient id="crumbGlow" cx="50%" cy="40%" r="60%">
      <stop offset="0%" stop-color="#fff5dc" stop-opacity="0.95"/>
      <stop offset="30%" stop-color="#fde0a8" stop-opacity="0.85"/>
      <stop offset="70%" stop-color="#e3a050" stop-opacity="0.5"/>
      <stop offset="100%" stop-color="#99480d" stop-opacity="0.05"/>
    </radialGradient>
    <linearGradient id="butterGlaze" x1="10%" y1="0%" x2="90%" y2="100%">
      <stop offset="0%" stop-color="#ffffff" stop-opacity="0.6"/>
      <stop offset="35%" stop-color="#ffe8a3" stop-opacity="0.35"/>
      <stop offset="70%" stop-color="#ffb833" stop-opacity="0"/>
    </linearGradient>
    <linearGradient id="toastRing" x1="0%" y1="50%" x2="100%" y2="50%">
      <stop offset="0%" stop-color="#542102" stop-opacity="0.9"/>
      <stop offset="15%" stop-color="#99450c" stop-opacity="0.65"/>
      <stop offset="50%" stop-color="#4a1c02" stop-opacity="0.88"/>
      <stop offset="85%" stop-color="#99450c" stop-opacity="0.65"/>
      <stop offset="100%" stop-color="#542102" stop-opacity="0.9"/>
    </linearGradient>
    <linearGradient id="heelDepth" x1="0%" y1="0%" x2="0%" y2="100%">
      <stop offset="0%" stop-color="#803e0b"/>
      <stop offset="40%" stop-color="#522406"/>
      <stop offset="100%" stop-color="#2d1303"/>
    </linearGradient>
  </defs>

  <!-- Ambient Ground Contact Shadow -->
  <ellipse cx="400" cy="432" rx="300" ry="32" fill="url(#baseGroundShadow)"/>
  <ellipse cx="400" cy="428" rx="260" ry="20" fill="url(#baseGroundShadow)" opacity="0.8"/>

  <!-- Brioche Bun Heel Dome Body -->
  <path d="M 115 340 
           C 115 405, 205 440, 400 440 
           C 595 440, 685 405, 685 340 
           C 685 315, 635 298, 400 298 
           C 165 298, 115 315, 115 340 Z" 
        fill="url(#crustGrad)"/>

  <!-- Rounded Bottom Crust Rim Depth -->
  <path d="M 130 350 
           C 145 410, 225 436, 400 436 
           C 575 436, 655 410, 670 350 
           C 685 385, 610 432, 400 432 
           C 190 432, 115 385, 130 350 Z" 
        fill="url(#heelDepth)" opacity="0.9"/>

  <!-- Bread Crumb Interior Top Surface (Toasted Cut Face at ~18° perspective) -->
  <ellipse cx="400" cy="336" rx="275" ry="48" fill="#d9822b"/>
  <ellipse cx="400" cy="335" rx="268" ry="44" fill="#f0b467"/>
  <ellipse cx="400" cy="333" rx="258" ry="39" fill="#fde3a7"/>
  <ellipse cx="400" cy="331" rx="242" ry="33" fill="url(#crumbGlow)"/>

  <!-- Golden Butter Sear Ring / Griddle Marks -->
  <ellipse cx="400" cy="333" rx="226" ry="28" fill="none" stroke="url(#toastRing)" stroke-width="8" opacity="0.8"/>
  <ellipse cx="400" cy="333" rx="165" ry="20" fill="none" stroke="#6b2b04" stroke-width="3.5" stroke-dasharray="16 12" opacity="0.65"/>
  <ellipse cx="400" cy="333" rx="105" ry="12" fill="none" stroke="#7a3406" stroke-width="2.5" stroke-dasharray="10 8" opacity="0.5"/>

  <!-- Specular Butter Glaze on Crust Rim -->
  <path d="M 135 340 
           C 220 405, 580 405, 665 340 
           C 590 380, 210 380, 135 340 Z" 
        fill="url(#butterGlaze)"/>

  <!-- Subtle Crumb Flecks and Aeration -->
  <g fill="#8a420b" opacity="0.55">
    <circle cx="260" cy="330" r="3"/>
    <circle cx="320" cy="336" r="3.5"/>
    <circle cx="380" cy="326" r="2.5"/>
    <circle cx="430" cy="329" r="2.8"/>
    <circle cx="490" cy="334" r="3.5"/>
    <circle cx="545" cy="330" r="2.8"/>
    <circle cx="360" cy="342" r="2.2"/>
    <circle cx="450" cy="341" r="3"/>
    <circle cx="210" cy="335" r="2.5"/>
    <circle cx="590" cy="332" r="2.5"/>
  </g>
</svg>"""

# -------------------------------------------------------------
# 2. LAYER 2: LETTUCE - Crisp undulating Lollo Bionda & Iceberg leaves
# -------------------------------------------------------------
layer_2_svg = """<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 500" width="100%" height="100%" fill="none">
  <defs>
    <!-- Soft Leaf Shadow Cast on Bottom Bun -->
    <radialGradient id="lettuceBaseShadow" cx="50%" cy="50%" r="50%">
      <stop offset="0%" stop-color="#0a1a06" stop-opacity="0.55"/>
      <stop offset="60%" stop-color="#0a1a06" stop-opacity="0.25"/>
      <stop offset="100%" stop-color="#0a1a06" stop-opacity="0"/>
    </radialGradient>
    <linearGradient id="lettuceMain" x1="0%" y1="0%" x2="100%" y2="80%">
      <stop offset="0%" stop-color="#236e17"/>
      <stop offset="25%" stop-color="#388e3c"/>
      <stop offset="55%" stop-color="#4caf50"/>
      <stop offset="80%" stop-color="#66bb6a"/>
      <stop offset="100%" stop-color="#81c784"/>
    </linearGradient>
    <linearGradient id="lettuceDark" x1="50%" y1="0%" x2="50%" y2="100%">
      <stop offset="0%" stop-color="#14420e"/>
      <stop offset="50%" stop-color="#1b5e20"/>
      <stop offset="100%" stop-color="#2e7d32"/>
    </linearGradient>
    <linearGradient id="veinGrad" x1="0%" y1="50%" x2="100%" y2="50%">
      <stop offset="0%" stop-color="#dcedc8" stop-opacity="0.9"/>
      <stop offset="60%" stop-color="#e8f5e9" stop-opacity="0.65"/>
      <stop offset="100%" stop-color="#a5d6a7" stop-opacity="0.25"/>
    </linearGradient>
    <linearGradient id="leafFrillGlow" x1="0%" y1="0%" x2="0%" y2="100%">
      <stop offset="0%" stop-color="#e8f5e9" stop-opacity="0.8"/>
      <stop offset="100%" stop-color="#81c784" stop-opacity="0"/>
    </linearGradient>
  </defs>

  <!-- Contact Shadow Under Leaf Bed -->
  <ellipse cx="400" cy="342" rx="285" ry="36" fill="url(#lettuceBaseShadow)"/>

  <!-- Deep Back Layer Folds -->
  <path d="M 90 325 
           C 115 285, 175 300, 225 280 
           C 285 255, 345 290, 415 270 
           C 485 250, 565 285, 625 265 
           C 675 250, 730 280, 725 325 
           C 685 340, 605 315, 525 335 
           C 445 350, 365 325, 295 345 
           C 205 360, 135 335, 90 325 Z" 
        fill="url(#lettuceDark)" opacity="0.9"/>

  <!-- Main Crisp Undulating Leaf Front (Curling over bun rim) -->
  <path d="M 80 328 
           C 95 290, 140 310, 170 285 
           C 200 260, 235 305, 280 275 
           C 325 245, 370 290, 420 265 
           C 470 240, 515 285, 570 255 
           C 620 230, 665 280, 700 260 
           C 735 245, 750 295, 730 335 
           C 705 370, 655 345, 610 370 
           C 560 395, 505 355, 445 380 
           C 385 400, 335 360, 275 385 
           C 215 405, 165 365, 120 380 
           C 85 390, 70 360, 80 328 Z" 
        fill="url(#lettuceMain)"/>

  <!-- Secondary Front Fold Layer for Volume -->
  <path d="M 140 340 
           C 180 315, 220 335, 260 315 
           C 310 290, 360 325, 410 305 
           C 460 285, 510 320, 560 300 
           C 610 280, 660 315, 680 335 
           C 650 365, 590 340, 530 360 
           C 470 375, 410 350, 350 368 
           C 280 385, 210 355, 140 340 Z" 
        fill="#43a047" opacity="0.92"/>

  <!-- Translucent Rib Veins -->
  <path d="M 230 350 Q 370 310 550 315" stroke="url(#veinGrad)" stroke-width="7" stroke-linecap="round"/>
  <path d="M 290 335 Q 320 300 360 280" stroke="url(#veinGrad)" stroke-width="4.5" stroke-linecap="round"/>
  <path d="M 380 320 Q 430 295 470 270" stroke="url(#veinGrad)" stroke-width="4.5" stroke-linecap="round"/>
  <path d="M 460 318 Q 510 295 560 275" stroke="url(#veinGrad)" stroke-width="4" stroke-linecap="round"/>
  <path d="M 340 330 Q 365 355 400 370" stroke="url(#veinGrad)" stroke-width="3.5" stroke-linecap="round"/>
  <path d="M 470 322 Q 500 350 540 362" stroke="url(#veinGrad)" stroke-width="3.5" stroke-linecap="round"/>

  <!-- Crispy Curled Frill Edges (Highlights) -->
  <path d="M 170 285 Q 200 260 235 305" stroke="#c8e6c9" stroke-width="3.5" opacity="0.85"/>
  <path d="M 280 275 Q 325 245 370 290" stroke="#dcedc8" stroke-width="4" opacity="0.9"/>
  <path d="M 420 265 Q 470 240 515 285" stroke="#c8e6c9" stroke-width="4" opacity="0.9"/>
  <path d="M 570 255 Q 620 230 665 280" stroke="#dcedc8" stroke-width="3.5" opacity="0.85"/>
  <path d="M 275 385 Q 335 360 385 400" stroke="#a5d6a7" stroke-width="3.2" opacity="0.8"/>
  <path d="M 445 380 Q 505 355 560 395" stroke="#c8e6c9" stroke-width="3" opacity="0.8"/>

  <!-- Crisp Dew Water Droplets (Realistic specular droplets) -->
  <g fill="#ffffff" opacity="0.85">
    <ellipse cx="255" cy="295" rx="5" ry="3.5" transform="rotate(-15 255 295)"/>
    <ellipse cx="256" cy="294" rx="2" ry="1.5"/>
    <ellipse cx="485" cy="280" rx="6" ry="4" transform="rotate(10 485 280)"/>
    <ellipse cx="486" cy="279" rx="2.5" ry="1.5"/>
    <ellipse cx="365" cy="355" rx="4.5" ry="3"/>
    <ellipse cx="615" cy="290" rx="5" ry="3.5" transform="rotate(-20 615 290)"/>
  </g>
</svg>"""

# -------------------------------------------------------------
# 3. LAYER 3: PATTY - 200g Prime Angus Beef Patty with Criss-Cross Char & Juices
# -------------------------------------------------------------
layer_3_svg = """<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 500" width="100%" height="100%" fill="none">
  <defs>
    <!-- Soft Patty Cast Shadow on Greens -->
    <radialGradient id="pattyCastShadow" cx="50%" cy="50%" r="50%">
      <stop offset="0%" stop-color="#080201" stop-opacity="0.7"/>
      <stop offset="65%" stop-color="#080201" stop-opacity="0.3"/>
      <stop offset="100%" stop-color="#080201" stop-opacity="0"/>
    </radialGradient>
    <linearGradient id="pattyBodyGrad" x1="0%" y1="0%" x2="0%" y2="100%">
      <stop offset="0%" stop-color="#442111"/>
      <stop offset="25%" stop-color="#2a1207"/>
      <stop offset="60%" stop-color="#3d1b0d"/>
      <stop offset="85%" stop-color="#240f06"/>
      <stop offset="100%" stop-color="#140803"/>
    </linearGradient>
    <radialGradient id="pattyJuiceGlow" cx="48%" cy="42%" r="55%">
      <stop offset="0%" stop-color="#7a3d1c" stop-opacity="0.95"/>
      <stop offset="45%" stop-color="#451f0f" stop-opacity="0.8"/>
      <stop offset="80%" stop-color="#240f06" stop-opacity="0.5"/>
      <stop offset="100%" stop-color="#140803" stop-opacity="0.1"/>
    </radialGradient>
    <linearGradient id="charMarkMain" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#0d0401" stop-opacity="0.96"/>
      <stop offset="50%" stop-color="#1c0903" stop-opacity="0.88"/>
      <stop offset="100%" stop-color="#0d0401" stop-opacity="0.96"/>
    </linearGradient>
    <linearGradient id="charMarkCross" x1="100%" y1="0%" x2="0%" y2="100%">
      <stop offset="0%" stop-color="#0d0401" stop-opacity="0.92"/>
      <stop offset="50%" stop-color="#1c0903" stop-opacity="0.84"/>
      <stop offset="100%" stop-color="#0d0401" stop-opacity="0.92"/>
    </linearGradient>
    <linearGradient id="edgeCrust" x1="0%" y1="0%" x2="0%" y2="100%">
      <stop offset="0%" stop-color="#5a2a14"/>
      <stop offset="50%" stop-color="#260e05"/>
      <stop offset="100%" stop-color="#100502"/>
    </linearGradient>
  </defs>

  <!-- Ambient Shadow Under Patty -->
  <ellipse cx="400" cy="342" rx="285" ry="34" fill="url(#pattyCastShadow)"/>

  <!-- Thick Volumetric Beef Patty Body (from y=250 to y=348) -->
  <path d="M 100 285 
           C 100 230, 155 210, 400 210 
           C 645 210, 700 230, 700 285 
           C 705 315, 695 348, 655 365 
           C 595 385, 485 378, 400 378 
           C 315 378, 205 385, 145 365 
           C 105 348, 95 315, 100 285 Z" 
        fill="url(#pattyBodyGrad)"/>

  <!-- Meat Cut Surface Volume Glow at ~18° perspective -->
  <ellipse cx="400" cy="275" rx="280" ry="58" fill="url(#pattyJuiceGlow)"/>

  <!-- Organic Coarse Edge Crust Crumbles and Meat Grain -->
  <path d="M 98 275 Q 106 295 102 315 Q 114 335 110 355 Q 124 370 145 378
           M 702 275 Q 694 295 698 315 Q 686 335 690 355 Q 676 370 655 378" 
        stroke="url(#edgeCrust)" stroke-width="10" stroke-linecap="round" opacity="0.85"/>

  <!-- Authentic Flame Grill Criss-Cross Diamond Char Marks -->
  <!-- Diagonal Primary Slits (Top-Left to Bottom-Right) -->
  <g stroke="url(#charMarkMain)" stroke-linecap="round">
    <path d="M 195 240 L 285 330" stroke-width="14"/>
    <path d="M 285 225 L 390 340" stroke-width="16"/>
    <path d="M 380 220 L 495 340" stroke-width="17"/>
    <path d="M 480 225 L 595 335" stroke-width="15"/>
    <path d="M 585 240 L 665 325" stroke-width="13"/>
  </g>

  <!-- Diagonal Counter Cross-Marks (Top-Right to Bottom-Left, creating diamond lattice) -->
  <g stroke="url(#charMarkCross)" stroke-linecap="round" opacity="0.75">
    <path d="M 305 235 L 210 320" stroke-width="10"/>
    <path d="M 410 220 L 295 335" stroke-width="12"/>
    <path d="M 515 220 L 395 340" stroke-width="12"/>
    <path d="M 610 225 L 490 335" stroke-width="11"/>
    <path d="M 675 250 L 590 325" stroke-width="9"/>
  </g>

  <!-- Glowing Hot Heat Embers inside Char Crevices -->
  <g stroke="#b94716" stroke-width="2.5" opacity="0.7" stroke-linecap="round">
    <path d="M 205 248 L 278 320"/>
    <path d="M 295 232 L 385 330"/>
    <path d="M 390 228 L 488 330"/>
    <path d="M 490 232 L 588 325"/>
  </g>

  <!-- Glistening Juices, Rendered Fat Sheen & Moisture Droplets -->
  <g fill="#ffe0b2" opacity="0.8">
    <ellipse cx="255" cy="260" rx="4.5" ry="2.8" transform="rotate(-20 255 260)"/>
    <ellipse cx="335" cy="250" rx="5.5" ry="3.2" transform="rotate(15 335 250)"/>
    <ellipse cx="440" cy="240" rx="6.5" ry="3.8" transform="rotate(-10 440 240)"/>
    <ellipse cx="535" cy="255" rx="5.5" ry="3.2" transform="rotate(25 535 255)"/>
    <ellipse cx="370" cy="295" rx="4.5" ry="2.8"/>
    <ellipse cx="475" cy="290" rx="5.5" ry="3.2"/>
    <ellipse cx="565" cy="285" rx="4" ry="2.2"/>
    <ellipse cx="305" cy="285" rx="4" ry="2.2"/>
    <ellipse cx="420" cy="315" rx="5" ry="2.8"/>
    <ellipse cx="230" cy="295" rx="3.5" ry="2"/>
    <ellipse cx="610" cy="275" rx="4" ry="2.2"/>
  </g>
</svg>"""

# -------------------------------------------------------------
# 4. LAYER 4: CHEESE - Molten Wisconsin Aged Cheddar draping over patty contours
# -------------------------------------------------------------
layer_4_svg = """<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 500" width="100%" height="100%" fill="none">
  <defs>
    <!-- Soft Melted Cheese Shadow onto Patty -->
    <radialGradient id="cheeseUnderShadow" cx="50%" cy="50%" r="50%">
      <stop offset="0%" stop-color="#3d1403" stop-opacity="0.6"/>
      <stop offset="60%" stop-color="#3d1403" stop-opacity="0.25"/>
      <stop offset="100%" stop-color="#3d1403" stop-opacity="0"/>
    </radialGradient>
    <linearGradient id="cheddarMain" x1="0%" y1="0%" x2="0%" y2="100%">
      <stop offset="0%" stop-color="#ffb703"/>
      <stop offset="30%" stop-color="#fb8500"/>
      <stop offset="70%" stop-color="#d96b00"/>
      <stop offset="100%" stop-color="#a64600"/>
    </linearGradient>
    <radialGradient id="cheeseCenterGlow" cx="50%" cy="35%" r="60%">
      <stop offset="0%" stop-color="#fff0aa" stop-opacity="0.95"/>
      <stop offset="45%" stop-color="#ffb703" stop-opacity="0.85"/>
      <stop offset="85%" stop-color="#d96b00" stop-opacity="0.4"/>
      <stop offset="100%" stop-color="#b04a00" stop-opacity="0"/>
    </radialGradient>
    <linearGradient id="meltGloss" x1="0%" y1="0%" x2="100%" y2="50%">
      <stop offset="0%" stop-color="#ffffff" stop-opacity="0.75"/>
      <stop offset="30%" stop-color="#fff1b8" stop-opacity="0.45"/>
      <stop offset="70%" stop-color="#ffa200" stop-opacity="0"/>
    </linearGradient>
  </defs>

  <!-- Ambient Under-Drip Shadow -->
  <ellipse cx="400" cy="305" rx="275" ry="42" fill="url(#cheeseUnderShadow)"/>

  <!-- Melted Cheddar Slice Draping Organically Over Patty Shoulders -->
  <path d="M 115 240 
           C 165 220, 255 230, 325 218 
           C 405 205, 485 222, 565 212 
           C 625 208, 675 232, 675 248 
           C 685 278, 660 318, 640 348 
           C 630 365, 615 358, 610 338 
           C 600 298, 560 288, 530 312 
           C 500 338, 480 395, 455 405 
           C 440 410, 425 380, 420 348 
           C 410 295, 370 285, 335 308 
           C 305 335, 275 385, 250 395 
           C 235 400, 225 365, 220 335 
           C 210 285, 175 285, 145 305 
           C 125 320, 115 300, 110 270 
           C 105 250, 108 245, 115 240 Z" 
        fill="url(#cheddarMain)"/>

  <!-- Molten Top Surface Creamy Sheen -->
  <path d="M 130 242 
           C 235 225, 565 218, 665 245 
           C 590 280, 210 280, 130 242 Z" 
        fill="url(#cheeseCenterGlow)"/>

  <!-- Liquid Gloss Highlight on Draping Folds -->
  <path d="M 125 245 C 215 230, 435 220, 660 248" 
        stroke="url(#meltGloss)" stroke-width="7" stroke-linecap="round"/>

  <!-- Melted Drip Ridge Highlights -->
  <path d="M 235 340 C 240 370, 245 385, 250 392" 
        stroke="#fff3b0" stroke-width="4" stroke-linecap="round" opacity="0.85"/>
  <path d="M 435 345 C 445 375, 450 395, 455 402" 
        stroke="#fff3b0" stroke-width="4.5" stroke-linecap="round" opacity="0.9"/>
  <path d="M 622 310 C 628 335, 634 350, 638 355" 
        stroke="#fff3b0" stroke-width="3.5" stroke-linecap="round" opacity="0.85"/>

  <!-- Warm Molten Cheddar Drops Detaching -->
  <ellipse cx="456" cy="414" rx="5.5" ry="7" fill="#fb8500" opacity="0.95"/>
  <ellipse cx="455" cy="412" rx="2" ry="2.5" fill="#fff9db"/>
  <ellipse cx="251" cy="404" rx="4" ry="5.5" fill="#fb8500" opacity="0.9"/>
  <ellipse cx="250" cy="403" rx="1.5" ry="2" fill="#fff9db"/>
</svg>"""

# -------------------------------------------------------------
# 5. LAYER 5: TOMATOES - Dual thick-cut heirloom beefsteak tomato slices
# -------------------------------------------------------------
layer_5_svg = """<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 500" width="100%" height="100%" fill="none">
  <defs>
    <!-- Soft Shadow Cast by Tomatoes onto Cheese -->
    <radialGradient id="tomatoSliceShadow" cx="50%" cy="50%" r="50%">
      <stop offset="0%" stop-color="#3b0808" stop-opacity="0.6"/>
      <stop offset="60%" stop-color="#3b0808" stop-opacity="0.25"/>
      <stop offset="100%" stop-color="#3b0808" stop-opacity="0"/>
    </radialGradient>
    <radialGradient id="tomatoSkinLeft" cx="45%" cy="45%" r="55%">
      <stop offset="65%" stop-color="#b91c1c"/>
      <stop offset="88%" stop-color="#991b1b"/>
      <stop offset="100%" stop-color="#7f1d1d"/>
    </radialGradient>
    <radialGradient id="tomatoPulpLeft" cx="50%" cy="50%" r="50%">
      <stop offset="0%" stop-color="#ef4444"/>
      <stop offset="60%" stop-color="#dc2626"/>
      <stop offset="100%" stop-color="#b91c1c"/>
    </radialGradient>
    <radialGradient id="loculeGel" cx="50%" cy="50%" r="50%">
      <stop offset="0%" stop-color="#7f1d1d"/>
      <stop offset="65%" stop-color="#991b1b"/>
      <stop offset="100%" stop-color="#b91c1c"/>
    </radialGradient>
    <linearGradient id="wetSheen" x1="0%" y1="0%" x2="100%" y2="80%">
      <stop offset="0%" stop-color="#ffffff" stop-opacity="0.65"/>
      <stop offset="30%" stop-color="#fca5a5" stop-opacity="0.35"/>
      <stop offset="70%" stop-color="#dc2626" stop-opacity="0"/>
    </linearGradient>
    <linearGradient id="sliceRimDepth" x1="0%" y1="0%" x2="0%" y2="100%">
      <stop offset="0%" stop-color="#991b1b"/>
      <stop offset="100%" stop-color="#550c0c"/>
    </linearGradient>
  </defs>

  <!-- Ambient Under-Slice Shadow -->
  <ellipse cx="265" cy="245" rx="155" ry="50" fill="url(#tomatoSliceShadow)"/>
  <ellipse cx="515" cy="240" rx="160" ry="52" fill="url(#tomatoSliceShadow)"/>

  <!-- LEFT TOMATO SLICE (Angled -6° at ~18° perspective) -->
  <g transform="translate(265, 225) rotate(-6)">
    <!-- Slice Vertical Rim (Thickness / Cut Depth) -->
    <path d="M -160 0 C -160 35, 160 35, 160 0 L 160 16 C 160 50, -160 50, -160 16 Z" fill="url(#sliceRimDepth)"/>
    <!-- Outer Red Skin Wall -->
    <ellipse cx="0" cy="0" rx="162" ry="74" fill="url(#tomatoSkinLeft)"/>
    <!-- Inner Meaty Flesh Core -->
    <ellipse cx="0" cy="0" rx="145" ry="63" fill="url(#tomatoPulpLeft)"/>
    <ellipse cx="0" cy="0" rx="38" ry="18" fill="#fca5a5" opacity="0.7"/>
    <!-- Fleshy Septa / Core Radiating Star -->
    <path d="M 0 0 L -80 -30 M 0 0 L 80 -30 M 0 0 L -88 22 M 0 0 L 0 48 M 0 0 L 88 22" 
          stroke="#f87171" stroke-width="8.5" stroke-linecap="round" opacity="0.85"/>

    <!-- Seed Locules (Pulp Cavities) -->
    <!-- Locule 1 (Top Left) -->
    <path d="M -22 -12 Q -60 -40 -95 -18 Q -65 5 -28 -4 Z" fill="url(#loculeGel)"/>
    <ellipse cx="-60" cy="-18" rx="6" ry="3.8" fill="#fef08a" transform="rotate(20 -60 -18)"/>
    <ellipse cx="-75" cy="-16" rx="5.5" ry="3.2" fill="#fde047" transform="rotate(-15 -75 -16)"/>

    <!-- Locule 2 (Top Right) -->
    <path d="M 22 -12 Q 60 -40 95 -18 Q 65 5 28 -4 Z" fill="url(#loculeGel)"/>
    <ellipse cx="60" cy="-18" rx="6" ry="3.8" fill="#fef08a" transform="rotate(-20 60 -18)"/>
    <ellipse cx="75" cy="-16" rx="5.5" ry="3.2" fill="#fde047" transform="rotate(15 75 -16)"/>

    <!-- Locule 3 (Bottom Left) -->
    <path d="M -26 8 Q -65 35 -88 16 Q -55 -4 -22 6 Z" fill="url(#loculeGel)"/>
    <ellipse cx="-55" cy="18" rx="6" ry="3.8" fill="#fef08a" transform="rotate(-25 -55 18)"/>

    <!-- Locule 4 (Bottom Right) -->
    <path d="M 26 8 Q 65 35 88 16 Q 55 -4 22 6 Z" fill="url(#loculeGel)"/>
    <ellipse cx="55" cy="18" rx="6" ry="3.8" fill="#fef08a" transform="rotate(25 55 18)"/>

    <!-- Wet Cut Face Gloss Specular Arc -->
    <path d="M -120 -25 C -75 -60, 75 -60, 120 -25" 
          stroke="url(#wetSheen)" stroke-width="7.5" stroke-linecap="round"/>
  </g>

  <!-- RIGHT TOMATO SLICE (Overlapping, Angled +7° at ~18° perspective) -->
  <g transform="translate(515, 220) rotate(7)">
    <!-- Slice Vertical Rim (Thickness / Cut Depth) -->
    <path d="M -165 0 C -165 36, 165 36, 165 0 L 165 17 C 165 52, -165 52, -165 17 Z" fill="url(#sliceRimDepth)"/>
    <!-- Outer Red Skin Wall -->
    <ellipse cx="0" cy="0" rx="168" ry="76" fill="url(#tomatoSkinLeft)"/>
    <!-- Inner Meaty Flesh Core -->
    <ellipse cx="0" cy="0" rx="150" ry="65" fill="url(#tomatoPulpLeft)"/>
    <ellipse cx="0" cy="0" rx="42" ry="20" fill="#fca5a5" opacity="0.7"/>
    <!-- Fleshy Septa / Core Radiating Star -->
    <path d="M 0 0 L -85 -30 M 0 0 L 85 -30 M 0 0 L -92 22 M 0 0 L 0 50 M 0 0 L 92 22" 
          stroke="#f87171" stroke-width="9" stroke-linecap="round" opacity="0.85"/>

    <!-- Seed Locules -->
    <!-- Locule 1 (Top Left) -->
    <path d="M -22 -12 Q -65 -42 -98 -20 Q -68 5 -28 -4 Z" fill="url(#loculeGel)"/>
    <ellipse cx="-62" cy="-20" rx="6.5" ry="4" fill="#fef08a" transform="rotate(20 -62 -20)"/>
    <ellipse cx="-78" cy="-18" rx="5.5" ry="3.5" fill="#fde047" transform="rotate(-10 -78 -18)"/>

    <!-- Locule 2 (Top Right) -->
    <path d="M 22 -12 Q 65 -42 98 -20 Q 68 5 28 -4 Z" fill="url(#loculeGel)"/>
    <ellipse cx="62" cy="-20" rx="6.5" ry="4" fill="#fef08a" transform="rotate(-20 62 -20)"/>
    <ellipse cx="78" cy="-18" rx="5.5" ry="3.5" fill="#fde047" transform="rotate(10 78 -18)"/>

    <!-- Locule 3 (Bottom Left) -->
    <path d="M -26 8 Q -68 36 -92 18 Q -58 -4 -22 6 Z" fill="url(#loculeGel)"/>
    <ellipse cx="-58" cy="20" rx="6.5" ry="4" fill="#fef08a" transform="rotate(-25 -58 20)"/>

    <!-- Locule 4 (Bottom Right) -->
    <path d="M 26 8 Q 68 36 92 18 Q 58 -4 22 6 Z" fill="url(#loculeGel)"/>
    <ellipse cx="58" cy="20" rx="6.5" ry="4" fill="#fef08a" transform="rotate(25 58 20)"/>

    <!-- Wet Cut Face Gloss Specular Arc -->
    <path d="M -125 -26 C -75 -65, 75 -65, 125 -26" 
          stroke="url(#wetSheen)" stroke-width="8" stroke-linecap="round"/>
  </g>

  <!-- Water Droplets on Tomato Cut Face -->
  <g fill="#ffffff" opacity="0.85">
    <ellipse cx="230" cy="205" rx="5" ry="3.5" transform="rotate(-15 230 205)"/>
    <ellipse cx="300" cy="240" rx="4" ry="2.5"/>
    <ellipse cx="480" cy="198" rx="5.5" ry="3.5" transform="rotate(10 480 198)"/>
    <ellipse cx="570" cy="212" rx="4.5" ry="3"/>
    <ellipse cx="620" cy="230" rx="5" ry="3.5" transform="rotate(20 620 230)"/>
  </g>
</svg>"""

# -------------------------------------------------------------
# 6. LAYER 6: ONIONS & PICKLES - Crinkle-cut dill pickles + sweet caramelized onion ribbons
# -------------------------------------------------------------
layer_6_svg = """<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 500" width="100%" height="100%" fill="none">
  <defs>
    <!-- Soft Pickle & Onion Shadow Cast onto Tomatoes -->
    <radialGradient id="condimentShadow" cx="50%" cy="50%" r="50%">
      <stop offset="0%" stop-color="#140a02" stop-opacity="0.55"/>
      <stop offset="60%" stop-color="#140a02" stop-opacity="0.25"/>
      <stop offset="100%" stop-color="#140a02" stop-opacity="0"/>
    </radialGradient>
    <linearGradient id="caramelizedOnion" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#662c08"/>
      <stop offset="35%" stop-color="#99450c"/>
      <stop offset="70%" stop-color="#c2630a"/>
      <stop offset="100%" stop-color="#e08819"/>
    </linearGradient>
    <radialGradient id="pickleSkin" cx="50%" cy="50%" r="50%">
      <stop offset="65%" stop-color="#14532d"/>
      <stop offset="90%" stop-color="#052e16"/>
      <stop offset="100%" stop-color="#021a0c"/>
    </radialGradient>
    <radialGradient id="pickleCore" cx="45%" cy="45%" r="55%">
      <stop offset="0%" stop-color="#86efac"/>
      <stop offset="50%" stop-color="#4ade80"/>
      <stop offset="85%" stop-color="#16a34a"/>
      <stop offset="100%" stop-color="#14532d"/>
    </radialGradient>
  </defs>

  <!-- Ambient Shadow Under Condiments -->
  <ellipse cx="250" cy="210" rx="90" ry="42" fill="url(#condimentShadow)"/>
  <ellipse cx="530" cy="205" rx="95" ry="45" fill="url(#condimentShadow)"/>

  <!-- PICKLE CHIP 1 (Left, Crinkle-Cut) -->
  <g transform="translate(245, 195) rotate(-14)">
    <!-- Crinkle Cut Outer Edge with Distinct Waves -->
    <path d="M -75 0 
             C -77 -22, -66 -45, -42 -58 
             C -18 -72, 18 -72, 42 -58 
             C 66 -45, 77 -22, 75 0 
             C 77 22, 66 45, 42 58 
             C 18 72, -18 72, -42 58 
             C -66 45, -77 22, -75 0 Z" 
          fill="url(#pickleSkin)"/>
    <ellipse cx="0" cy="0" rx="66" ry="50" fill="url(#pickleCore)"/>
    <!-- Crinkle Cut Parallel Grooves -->
    <path d="M -48 -30 Q 0 -40 48 -30 M -58 -10 Q 0 -20 58 -10 M -58 10 Q 0 0 58 10 M -48 30 Q 0 20 48 30" 
          stroke="#166534" stroke-width="3" stroke-linecap="round" opacity="0.65"/>
    <!-- Dill Flecks -->
    <g fill="#052e16" opacity="0.8">
      <circle cx="-14" cy="-8" r="1.8"/>
      <circle cx="12" cy="-14" r="2"/>
      <circle cx="22" cy="10" r="1.8"/>
      <circle cx="-5" cy="16" r="2.2"/>
      <circle cx="2" cy="-2" r="1.5"/>
    </g>
    <!-- Specular Wet Ridge Highlight -->
    <path d="M -42 -33 Q 0 -43 42 -33" stroke="#bbf7d0" stroke-width="2.5" opacity="0.85"/>
  </g>

  <!-- PICKLE CHIP 2 (Right, Crinkle-Cut) -->
  <g transform="translate(535, 190) rotate(16)">
    <!-- Crinkle Cut Outer Edge -->
    <path d="M -80 0 
             C -82 -25, -68 -48, -45 -62 
             C -20 -75, 20 -75, 45 -62 
             C 68 -48, 82 -25, 80 0 
             C 82 25, 68 48, 45 62 
             C 20 75, -20 75, -45 62 
             C -68 48, -82 25, -80 0 Z" 
          fill="url(#pickleSkin)"/>
    <ellipse cx="0" cy="0" rx="70" ry="52" fill="url(#pickleCore)"/>
    <!-- Crinkle Cut Grooves -->
    <path d="M -50 -32 Q 0 -42 50 -32 M -60 -10 Q 0 -21 60 -10 M -60 12 Q 0 1 60 12 M -50 32 Q 0 22 50 32" 
          stroke="#166534" stroke-width="3.2" stroke-linecap="round" opacity="0.65"/>
    <!-- Dill Flecks -->
    <g fill="#052e16" opacity="0.8">
      <circle cx="-16" cy="-10" r="2"/>
      <circle cx="10" cy="-16" r="2.2"/>
      <circle cx="24" cy="8" r="1.8"/>
      <circle cx="-8" cy="18" r="2.2"/>
      <circle cx="4" cy="-3" r="1.6"/>
    </g>
    <!-- Specular Wet Ridge Highlight -->
    <path d="M -45 -35 Q 0 -46 45 -35" stroke="#bbf7d0" stroke-width="2.8" opacity="0.9"/>
  </g>

  <!-- SLOW-COOKED CARAMELIZED ONION RIBBONS (Twisting across burger top) -->
  <!-- Upper Onion Ribbon (Looping center) -->
  <path d="M 125 218 
           C 175 180, 255 190, 325 168 
           C 385 148, 465 158, 525 145 
           C 585 132, 665 165, 685 182 
           C 655 192, 585 160, 525 165 
           C 445 170, 365 180, 305 190 
           C 235 202, 168 208, 125 218 Z" 
        fill="url(#caramelizedOnion)" opacity="0.94"/>

  <!-- Lower Onion Ribbon (Dipping lower) -->
  <path d="M 270 225 
           C 330 200, 410 205, 480 190 
           C 530 180, 595 195, 635 215 
           C 585 220, 525 205, 475 210 
           C 405 218, 345 225, 270 225 Z" 
        fill="url(#caramelizedOnion)" opacity="0.92"/>

  <!-- Caramelized Sweet Glaze Highlights -->
  <path d="M 165 192 C 245 182, 345 168, 485 155" 
        stroke="#fef08a" stroke-width="3" stroke-linecap="round" opacity="0.8"/>
  <path d="M 320 208 C 400 202, 500 192, 590 202" 
        stroke="#fef08a" stroke-width="2.5" stroke-linecap="round" opacity="0.75"/>
</svg>"""

# -------------------------------------------------------------
# 7. LAYER 7: TOP BUN (Crown) - High-dome glossy brioche crown with 47 3D sesame seeds
# -------------------------------------------------------------
# 47 individually rotated, scaled, and shaded sesame seeds across the 3D crown dome
sesame_seeds = [
    (240, 110, -25, 1.0), (280, 95, -15, 1.05), (325, 82, -8, 1.1), (370, 76, 0, 1.15),
    (415, 76, 5, 1.15), (460, 82, 12, 1.1), (505, 95, 20, 1.05), (545, 110, 30, 1.0),
    (210, 140, -35, 0.95), (255, 125, -20, 1.05), (300, 112, -12, 1.1), (350, 105, -4, 1.15),
    (400, 100, 2, 1.2), (450, 105, 8, 1.15), (500, 112, 18, 1.1), (545, 125, 26, 1.05),
    (585, 140, 38, 0.95), (180, 170, -45, 0.9), (225, 155, -30, 1.0), (275, 142, -18, 1.1),
    (325, 134, -8, 1.15), (375, 130, 0, 1.2), (425, 130, 6, 1.2), (475, 134, 15, 1.15),
    (525, 142, 25, 1.1), (575, 155, 35, 1.0), (615, 170, 48, 0.9), (160, 198, -50, 0.85),
    (205, 186, -35, 0.95), (250, 174, -22, 1.05), (300, 166, -10, 1.1), (350, 160, -2, 1.15),
    (400, 158, 4, 1.15), (450, 160, 10, 1.15), (500, 166, 20, 1.1), (550, 176, 30, 1.05),
    (595, 190, 42, 0.95), (635, 202, 52, 0.85), (280, 192, -15, 1.0), (330, 184, -5, 1.1),
    (380, 180, 0, 1.1), (430, 180, 8, 1.1), (480, 185, 18, 1.05), (530, 192, 28, 1.0),
    (360, 202, -2, 1.0), (410, 200, 5, 1.05), (460, 204, 14, 1.0)
]

sesame_svg_elements = []
for sx, sy, srot, sscale in sesame_seeds:
    sesame_svg_elements.append(f"""
    <g transform="translate({sx}, {sy}) rotate({srot}) scale({sscale})">
      <!-- Cast Shadow -->
      <path d="M 0 -7 C 4 -7, 6 2, 0 7 C -6 2, -4 -7, 0 -7 Z" fill="#361503" opacity="0.65" transform="translate(1, 2)"/>
      <!-- Body -->
      <path d="M 0 -7 C 4 -7, 6 2, 0 7 C -6 2, -4 -7, 0 -7 Z" fill="url(#sesameBody)"/>
      <!-- Highlight -->
      <ellipse cx="-1" cy="-2" rx="1.2" ry="3.5" fill="#ffffff" opacity="0.85"/>
    </g>""")

layer_7_svg = f"""<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 500" width="100%" height="100%" fill="none">
  <defs>
    <!-- Soft Contact Shadow on Fillings -->
    <radialGradient id="bunTopCastShadow" cx="50%" cy="50%" r="50%">
      <stop offset="0%" stop-color="#0a0401" stop-opacity="0.65"/>
      <stop offset="60%" stop-color="#0a0401" stop-opacity="0.25"/>
      <stop offset="100%" stop-color="#0a0401" stop-opacity="0"/>
    </radialGradient>
    <radialGradient id="briocheCrown" cx="48%" cy="26%" r="68%">
      <stop offset="0%" stop-color="#f59e0b"/>
      <stop offset="25%" stop-color="#d97706"/>
      <stop offset="55%" stop-color="#b45309"/>
      <stop offset="80%" stop-color="#78350f"/>
      <stop offset="100%" stop-color="#451a03"/>
    </radialGradient>
    <linearGradient id="eggWashGloss" x1="20%" y1="0%" x2="80%" y2="100%">
      <stop offset="0%" stop-color="#ffffff" stop-opacity="0.7"/>
      <stop offset="35%" stop-color="#fef3c7" stop-opacity="0.45"/>
      <stop offset="65%" stop-color="#f59e0b" stop-opacity="0.08"/>
      <stop offset="100%" stop-color="#b45309" stop-opacity="0"/>
    </linearGradient>
    <linearGradient id="sesameBody" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#ffffff"/>
      <stop offset="40%" stop-color="#fef08a"/>
      <stop offset="80%" stop-color="#fde047"/>
      <stop offset="100%" stop-color="#ca8a04"/>
    </linearGradient>
    <linearGradient id="crownRimDepth" x1="0%" y1="0%" x2="0%" y2="100%">
      <stop offset="0%" stop-color="#78350f"/>
      <stop offset="100%" stop-color="#301102"/>
    </linearGradient>
  </defs>

  <!-- Under-Bun Cast Shadow -->
  <ellipse cx="400" cy="225" rx="285" ry="32" fill="url(#bunTopCastShadow)"/>

  <!-- High-Dome Volumetric Brioche Crown Bun (Dome peak at y=55, rim at y=215) -->
  <path d="M 105 215 
           C 105 95, 215 55, 400 55 
           C 585 55, 695 95, 695 215 
           C 695 232, 650 242, 400 242 
           C 150 242, 105 232, 105 215 Z" 
        fill="url(#briocheCrown)"/>

  <!-- Lower Cut Rim Depth Profile -->
  <path d="M 115 215 
           C 170 236, 630 236, 685 215 
           C 650 238, 150 238, 115 215 Z" 
        fill="url(#crownRimDepth)" opacity="0.85"/>

  <!-- Large Organic 3D Specular Egg-Wash Gloss Reflection Arc across Crown Shoulder -->
  <path d="M 145 185 
           C 160 115, 245 75, 400 75 
           C 490 75, 565 95, 610 130 
           C 535 105, 455 92, 385 92 
           C 260 92, 180 130, 145 185 Z" 
        fill="url(#eggWashGloss)"/>

  <!-- Secondary Lower Gloss Rim -->
  <path d="M 130 210 
           C 205 228, 595 228, 670 210 
           C 595 220, 205 220, 130 210 Z" 
        fill="#fde68a" opacity="0.45"/>

  <!-- 47 Individually Shaded and Rotated 3D Sesame Seeds -->
  <g id="sesame-seeds">
    {''.join(sesame_svg_elements)}
  </g>
</svg>"""

# -------------------------------------------------------------
# 8. ATMOSPHERIC ASSET: STEAM WISPS - Translucent vector heat vapors
# -------------------------------------------------------------
steam_svg = """<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 500" width="100%" height="100%" fill="none">
  <defs>
    <linearGradient id="steamFade1" x1="0%" y1="100%" x2="0%" y2="0%">
      <stop offset="0%" stop-color="#ffffff" stop-opacity="0"/>
      <stop offset="25%" stop-color="#ffffff" stop-opacity="0.38"/>
      <stop offset="60%" stop-color="#ffffff" stop-opacity="0.22"/>
      <stop offset="100%" stop-color="#ffffff" stop-opacity="0"/>
    </linearGradient>
    <linearGradient id="steamFade2" x1="0%" y1="100%" x2="0%" y2="0%">
      <stop offset="0%" stop-color="#ffffff" stop-opacity="0"/>
      <stop offset="25%" stop-color="#ffffff" stop-opacity="0.32"/>
      <stop offset="65%" stop-color="#ffffff" stop-opacity="0.18"/>
      <stop offset="100%" stop-color="#ffffff" stop-opacity="0"/>
    </linearGradient>
  </defs>

  <g stroke-linecap="round">
    <!-- Wisp 1 (Left curling wisp) -->
    <path d="M 270 310 C 245 260, 305 210, 260 160 C 230 120, 275 80, 245 40" 
          stroke="url(#steamFade1)" stroke-width="16"/>
    <!-- Wisp 2 (Center wavy wisp) -->
    <path d="M 390 300 C 425 240, 365 190, 415 130 C 440 90, 395 50, 420 20" 
          stroke="url(#steamFade1)" stroke-width="20"/>
    <!-- Wisp 3 (Right rising wisp) -->
    <path d="M 515 310 C 490 250, 535 200, 500 140 C 470 100, 525 60, 490 30" 
          stroke="url(#steamFade2)" stroke-width="18"/>
    <!-- Wisp 4 (Delicate secondary curl) -->
    <path d="M 330 280 C 355 230, 320 180, 350 130 C 370 95, 340 60, 360 30" 
          stroke="url(#steamFade2)" stroke-width="12" opacity="0.7"/>
    <!-- Wisp 5 (Right delicate secondary curl) -->
    <path d="M 455 285 C 430 235, 470 185, 445 135 C 425 100, 460 65, 440 35" 
          stroke="url(#steamFade2)" stroke-width="13" opacity="0.7"/>
  </g>
</svg>"""

# Write all files and validate with XML parser
layers = [
    ("layer-1-bun-bottom.svg", layer_1_svg),
    ("layer-2-lettuce.svg", layer_2_svg),
    ("layer-3-patty.svg", layer_3_svg),
    ("layer-4-cheese.svg", layer_4_svg),
    ("layer-5-tomatoes.svg", layer_5_svg),
    ("layer-6-onions-pickles.svg", layer_6_svg),
    ("layer-7-bun-top.svg", layer_7_svg),
    ("steam-wisps.svg", steam_svg)
]

for filename, content in layers:
    filepath = os.path.join(OUT_DIR, filename)
    with open(filepath, "w", encoding="utf-8") as f:
        f.write(content.strip())
    # Parse XML to guarantee strict validity
    ET.fromstring(content.strip())
    print(f"Generated and validated {filename} ({len(content)} bytes)")

print("All 8 vector assets generated successfully.")
