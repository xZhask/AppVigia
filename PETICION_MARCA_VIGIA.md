# Petición · Identidad visual de VIGÍA PNP

Reemplazar la marca actual (pulso de electrocardiograma) por la marca **Caso índice**.

Es un cambio acotado y visual. No toca el motor de fichas, el manifiesto, la base de
datos ni la lógica de negocio. No requiere migración.

---

## 1. Qué es la marca y por qué

Cuatro circunferencias huecas alrededor de un punto relleno. Representa los casos en
observación y el que resultó positivo: la operación que define a la vigilancia
epidemiológica es **detectar el caso que destaca entre los demás**.

Esto importa para que no la "mejores" por el camino. Las marcas descartadas y su motivo:

| Descartada | Por qué no |
|---|---|
| Pulso de electrocardiograma (la actual) | Dice atención clínica. VIGÍA no atiende pacientes, los notifica y consolida. |
| Punto con ondas concéntricas | Es la convención de wifi, RSS y NFC. Se lee como ícono informático. |
| Ojo | Vigilancia + ojo se lee como vigilancia policial en una institución policial. |
| Escudo | Compite con el emblema real de la PNP cuando van juntos en una cabecera. |
| Curva o barras con umbral | Es el ícono de estadística genérico y choca con la sección Reportes de la propia barra lateral. |
| Lupa, cruz médica, mapa del Perú | Genéricos, o prometen un alcance (territorio nacional) que el sistema no tiene. |

---

## 2. Reglas de geometría — no negociables

1. **Los cuatro círculos exteriores van deliberadamente desalineados.** Si los alineas
   en una retícula perfecta, la marca se lee como un menú de aplicaciones. El
   desalineamiento es la decisión de diseño, no un error de redondeo. No "corrijas"
   las coordenadas.
2. **El punto central es el único elemento que debe leerse a 16 px.** Los cuatro
   exteriores pueden volverse textura a ese tamaño sin que el significado se pierda.
3. **Un solo dibujo para toda la interfaz.** La composición es idéntica en todos los
   tamaños. Lo único que cambia en la versión del favicon es el grosor de trazo, que
   sube de `1.7` a `2.2` por compensación óptica. No cambies posiciones ni cantidad
   de círculos entre versiones.
4. **Sin degradados, sombras ni biseles dentro de la marca.** El degradado vive en el
   contenedor (el cuadrado redondeado), no en el dibujo.
5. **`currentColor` siempre.** Nunca un color fijo dentro del SVG de la marca. Eso es
   lo que permite que funcione en modo claro, modo oscuro, monocromo y en los colores
   de clasificación sin archivos adicionales.

---

## 3. Archivos a crear

Ubícalos junto a los demás activos estáticos del proyecto (busca dónde vive hoy el
favicon actual; **no inventes una ruta nueva** si ya existe una convención).

### 3.1 `logo-vigia.svg` — la marca sola

Es la que se usa dentro de la interfaz. Hereda el color del contexto.

```svg
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" aria-hidden="true">
  <circle cx="5.2"  cy="7.5"  r="2.6" stroke="currentColor" stroke-width="1.7"/>
  <circle cx="18.4" cy="7.2"  r="2.6" stroke="currentColor" stroke-width="1.7"/>
  <circle cx="4.8"  cy="16.6" r="2.6" stroke="currentColor" stroke-width="1.7"/>
  <circle cx="18.8" cy="16.8" r="2.6" stroke="currentColor" stroke-width="1.7"/>
  <circle cx="12"   cy="12"   r="3.4" fill="currentColor"/>
</svg>
```

### 3.2 `favicon.svg` — marca sobre el contenedor

Incluye el cuadrado redondeado con el degradado, porque el favicon no tiene contexto
del que heredar. Trazo engrosado a `2.2`.

```svg
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32">
  <defs>
    <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="#0E7A6E"/>
      <stop offset="1" stop-color="#0A5A51"/>
    </linearGradient>
  </defs>
  <rect width="32" height="32" rx="7.5" fill="url(#g)"/>
  <g transform="translate(4 4)" fill="none" stroke="#FFFFFF" stroke-width="2.2">
    <circle cx="5.0"  cy="7.2"  r="2.7"/>
    <circle cx="19.0" cy="7.2"  r="2.7"/>
    <circle cx="5.0"  cy="16.8" r="2.7"/>
    <circle cx="19.0" cy="16.8" r="2.7"/>
    <circle cx="12"   cy="12"   r="4" fill="#FFFFFF" stroke="none"/>
  </g>
</svg>
```

### 3.3 PNG derivados

Genera `favicon-32.png` (32×32) y `apple-touch-icon.png` (180×180) a partir de
`favicon.svg`. Si el entorno tiene `rsvg-convert`, `inkscape` o `sharp`, úsalo; si no,
reporta que faltan y no improvises con una librería nueva.

El `apple-touch-icon` lleva el mismo dibujo pero **sin esquinas redondeadas** (iOS
aplica su propia máscara): cambia el `rx` a `0` solo en esa exportación.

### 3.4 Enlaces en el `<head>`

Agrégalos en el layout base, junto a donde ya se cargan las fuentes IBM Plex:

```html
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="icon" href="/favicon-32.png" sizes="32x32">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
```

Ajusta las rutas a la convención real del proyecto.

---

## 4. Puntos de integración

Localiza cada uno por su marcado actual, no por una ruta que yo suponga.

### 4.1 Barra lateral

Busca el contenedor `.brand-mark` (el cuadrado con degradado de 32–34 px).
**Reemplaza únicamente el SVG de adentro**, el del pulso de electrocardiograma.
El cuadrado, el degradado, el `border-radius` y el `box-shadow` interior se quedan
exactamente como están.

El SVG interior va a 17–19 px, con `stroke="currentColor"` y el contenedor en blanco.

### 4.2 Pantalla de acceso

Mismo reemplazo en el logotipo grande del panel oscuro. El lockup de texto
(«VIGÍA PNP» con el subtítulo) **no cambia**.

### 4.3 Verificación visual

Después de reemplazar, confirma en el navegador que la marca se ve correcta en:
modo claro, modo oscuro (el conmutador de la barra superior), la pestaña a 16 px, y
la pantalla de acceso. Si en modo oscuro la marca desaparece, es porque algún
contenedor no está propagando `color`: arréglalo ahí, **no** metiendo un color fijo
en el SVG.

---

## 5. Ícono de estado del caso (fase 2, opcional)

El mismo dibujo sirve de indicador de clasificación sin crear nada nuevo: cambia
**solo el punto central**. Los cuatro exteriores se quedan siempre en el verde de la
marca (`--accent`).

| Clasificación | Punto central | Color |
|---|---|---|
| Sin clasificar | hueco (`fill="none"` + `stroke-width="1.7"`) | `--accent` |
| Sospechoso | relleno | `--s-sospechoso` |
| Probable | relleno | `--s-probable` |
| Confirmado | relleno | `--s-confirmado` |
| Descartado | relleno | `--s-descartado` |

Esas cuatro variables ya existen en `theme.css`. **No crees colores nuevos.**

Implementación sugerida: un parcial que reciba la clasificación y emita el SVG con la
clase correspondiente, para no repetir el marcado en listados, tarjetas y `ver.php`.
Si lo haces, el punto central lleva su propio color y los exteriores heredan
`--accent` de forma explícita, no por `currentColor`, porque aquí los dos grupos
tienen colores distintos.

Consúltame antes de implementar esta fase: decide primero en qué vistas conviene que
aparezca, para no llenar la interfaz de íconos.

---

## 6. Lo que NO debes hacer

1. **No reescribas `theme.css`.** Está congelado. Si necesitas una clase, agrégala al
   final del archivo. No redefinas ninguna existente.
2. **No instales librerías de íconos** (Font Awesome, Lucide, Heroicons, Bootstrap
   Icons). La marca es un SVG en línea de cinco círculos.
3. **No conviertas la marca a PNG para usarla en la interfaz.** Solo el favicon y el
   apple-touch-icon son raster, y por obligación del formato.
4. **No toques el lockup de texto** ni la tipografía IBM Plex.
5. **No agregues animación** al logo.
6. **No apliques el cambio a archivos que no sean los puntos de integración
   listados.** Si encuentras el pulso de electrocardiograma en algún tercer lugar que
   no está aquí, repórtalo antes de cambiarlo.

---

## 7. Orden de trabajo

1. Localizar el favicon actual y el marcado de `.brand-mark` en la barra lateral y en
   la pantalla de acceso. Reportar las rutas encontradas antes de editar.
2. Crear `logo-vigia.svg` y `favicon.svg`.
3. Reemplazar el SVG interior en los dos puntos de integración.
4. Generar los PNG y agregar los enlaces del `<head>`.
5. Verificar visualmente los cuatro contextos de la sección 4.3.
6. Reportar: archivos creados, archivos modificados, y cualquier aparición de la
   marca anterior que haya quedado fuera del alcance.

La fase 2 (ícono de estado) queda fuera de esta petición.
