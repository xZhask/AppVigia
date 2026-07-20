<section class="auth-brand">
  <div class="auth-brand-top">
    <div class="auth-mark">
      <svg width="23" height="23" viewBox="0 0 18 18" fill="none" aria-hidden="true">
        <path d="M1 12.5 4 12.5 6 7 8.5 15 11 4 13 10.5 17 10.5" stroke="#fff" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </div>
    <div>
      <div class="auth-wordmark">VIGÍA</div>
      <div class="auth-wordmark-sub">Vigilancia · DIRSAPOL</div>
    </div>
  </div>

  <div class="auth-statement">
    <h1>Cada ficha notificada a tiempo <em>es un brote menos.</em></h1>
    <p>Sistema de vigilancia epidemiológica de las IPRESS de la Sanidad PNP. Registro, investigación y consolidación de las fichas de notificación obligatoria.</p>
  </div>

  <div class="auth-foot">
    <span><b>DIRSAPOL</b> · Área de Estadística</span>
    <span>Fichas modelo <b>MINSA / CDC</b></span>
  </div>

  <!-- Curva epidemiológica con corredor endémico: el elemento firma del sistema -->
  <svg class="auth-curve" viewBox="0 0 900 260" preserveAspectRatio="none" aria-hidden="true">
    <defs>
      <linearGradient id="fadeBar" x1="0" y1="0" x2="0" y2="1">
        <stop offset="0%" stop-color="#0E7A6E" stop-opacity=".55"/>
        <stop offset="100%" stop-color="#0E7A6E" stop-opacity=".05"/>
      </linearGradient>
      <linearGradient id="fadeAlert" x1="0" y1="0" x2="0" y2="1">
        <stop offset="0%" stop-color="#B23B3B" stop-opacity=".62"/>
        <stop offset="100%" stop-color="#B23B3B" stop-opacity=".05"/>
      </linearGradient>
    </defs>
    <!-- corredor endémico -->
    <path d="M0 196 C 140 190, 300 176, 470 152 S 760 108, 900 80 L 900 138 C 740 166, 560 190, 380 210 S 130 238, 0 244 Z" fill="#0E7A6E" opacity=".10"/>
    <path d="M0 196 C 140 190, 300 176, 470 152 S 760 108, 900 80" fill="none" stroke="#0E7A6E" stroke-width="1.4" stroke-dasharray="4 6" opacity=".45"/>
    <!-- barras semanales -->
    <g>
      <rect x="18"  y="214" width="34" height="46"  rx="3" fill="url(#fadeBar)"/>
      <rect x="70"  y="204" width="34" height="56"  rx="3" fill="url(#fadeBar)"/>
      <rect x="122" y="209" width="34" height="51"  rx="3" fill="url(#fadeBar)"/>
      <rect x="174" y="196" width="34" height="64"  rx="3" fill="url(#fadeBar)"/>
      <rect x="226" y="180" width="34" height="80"  rx="3" fill="url(#fadeBar)"/>
      <rect x="278" y="166" width="34" height="94"  rx="3" fill="url(#fadeBar)"/>
      <rect x="330" y="172" width="34" height="88"  rx="3" fill="url(#fadeBar)"/>
      <rect x="382" y="156" width="34" height="104" rx="3" fill="url(#fadeBar)"/>
      <rect x="434" y="138" width="34" height="122" rx="3" fill="url(#fadeBar)"/>
      <rect x="486" y="122" width="34" height="138" rx="3" fill="url(#fadeBar)"/>
      <rect x="538" y="130" width="34" height="130" rx="3" fill="url(#fadeBar)"/>
      <rect x="590" y="104" width="34" height="156" rx="3" fill="url(#fadeBar)"/>
      <rect x="642" y="112" width="34" height="148" rx="3" fill="url(#fadeBar)"/>
      <rect x="694" y="88"  width="34" height="172" rx="3" fill="url(#fadeBar)"/>
      <!-- semanas sobre el umbral esperado -->
      <rect x="746" y="70"  width="34" height="190" rx="3" fill="url(#fadeAlert)"/>
      <rect x="798" y="52"  width="34" height="208" rx="3" fill="url(#fadeAlert)"/>
      <rect x="850" y="38"  width="34" height="222" rx="3" fill="url(#fadeAlert)"/>
    </g>
  </svg>
</section>
