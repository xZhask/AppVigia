# Petición — Mapeo de fichas 27→24 y entrada F (edad con unidad)

Dos partes independientes. La primera es documental y barata. La segunda toca el núcleo y tiene una parada obligatoria a la mitad.

**INSTRUCCIÓN PERMANENTE:** si algo de esto mueve, oculta o altera cualquier cosa en una ficha ya revisada (A36, B26, A80, O95, B05, P35.0), menciónamelo explícitamente antes de commitear, aunque el cambio sea correcto.

**Sobre `CAMPOS_FICHAS_EPIDEMIOLOGICAS.md` y `CONDICIONALES_FICHAS_EPIDEMIOLOGICAS.md`:** los generó otra herramienta a partir del compendio MINSA y **no son fuente de verdad**. Verifiqué la ficha 13 (P35.0) contra el PDF real y colapsa "Pueblo étnico" y "Etnia/raza" en un solo `Etnia`, esconde "Tiempo de residencia" y "Referencia para localizar" dentro de `Domicilio completo`, y le falta la columna de resultado (−)/(+) del hisopado. La granularidad además es despareja entre fichas. Úsalos como índice y como pista, nunca como sustituto de la página del PDF.

---

## PARTE 1 — Tabla de correspondencia 27 documento ↔ 24 manifiesto

Crea `MAPEO_FICHAS_PDF.md` en la raíz del proyecto con esta tabla. Sirve para saber, ante cualquier ficha, qué página del compendio hay que abrir.

| # doc | Nombre en el documento | Págs. PDF | CIE-10 en el manifiesto | Nota |
|---|---|---|---|---|
| 1 | Tos Ferina | 1-2 | A37.0 | |
| 2 | Varicela con Complicaciones | 3 | B01 | |
| 3 | Parotiditis con Complicaciones | 4 | B26 | ✅ cotejada |
| 4 | Viruela del Mono (Mpox) | 5-6 | B04X | |
| 5 | ESAVI Severo — Lista de Chequeo | 7-8 | Y59.0 | Anexo 6.2 (opcional) |
| 6 | ESAVI Severo — Ficha de Investigación | 9-11 | Y59.0 | ficha principal |
| 7 | ESAVI Severo — Notificación Inmediata | 12 | Y59.0 | Anexo 01 |
| 8 | Difteria | 13-14 | A36 | ✅ cotejada |
| 9 | Gestantes VIH y Niños Expuestos — Registro Búsqueda Activa | 15 | **ninguno** | ver discrepancia 1 |
| 10 | Gestante con VIH y Niño Nacido Expuesto | 16 | Z21 | |
| 11 | Notificación Individual VIH/SIDA | 17 | B24 | |
| 12 | Sífilis Materna y Congénita | 18-19 | A50 | |
| 13 | Síndrome de Rubéola Congénita | 20 | P35.0 | ✅ cotejada |
| 14 | Tétanos Neonatal | 21-22 | A33 | |
| 15 | Tétanos (General) | 23-24 | A35 | |
| 16 | Violencia Familiar | 25 | Y07 | |
| 17 | Fiebre Amarilla | 26-27 | A95 | |
| 18 | Muerte Fetal y Neonatal | 28 | P96 | |
| 19 | Lesiones por Accidentes de Tránsito | 29 | V99 | |
| 20 | Muerte Materna (Anexos 1 y 2) | 30-33 | O95 | ✅ cotejada |
| 21 | Parálisis Flácida Aguda / Polio | 34-36 | A80 | ✅ cotejada |
| 22 | Sarampión / Rubéola / Febriles Eruptivas | 37-39 | B05 | ✅ cotejada |
| 23 | Enfermedad de Chagas | 40-41 | B57 | |
| 24 | Enfermedad de Carrión (Bartonelosis) | 42-44 | A44 | |
| 25 | Leishmaniasis | 45-48 | B55 | |
| 26 | Arbovirosis (Dengue/Chik/Zika/FA) | 49 | A97 | el manifiesto lo marca como "Anexo N.° 01" |
| 27 | EDA Grave — Cólera | 50-51 | A00 | ver discrepancia 2 |

La cuenta cierra: 27 − 2 (ESAVI son 3 entradas del documento y 1 sola ficha del manifiesto) − 1 (la #9 no existe) = 24.

### Discrepancias a reportarme, sin resolver

1. **Página 15 sin ficha en la app.** El "Registro de Búsqueda Activa de Gestantes VIH y Niños Expuestos" no corresponde a ninguna de las 24. Por su forma no es una ficha de caso individual: es una planilla de línea con cabecera institucional, un resumen de totales por servicio y una matriz de registro (N.°, código, HC, edad, tipo de edad, sexo, servicio, clasificación, fecha de defunción, notificado, observaciones). Dime si el compendio la trae como anexo de Z21 o como documento suelto. **No la implementes ni la agregues al manifiesto** — solo repórtame qué es, para que yo decida si entra al alcance.
2. **A00 (EDA grave / cólera):** el manifiesto dice página 50; el documento dice 50-51. Verifica en el PDF si la ficha ocupa una página o dos, y si son dos, si la 51 trae campos que hoy no están en el manifiesto. Solo repórtalo.
3. Marca en la tabla las 6 fichas ya cotejadas y deja las 18 restantes con su página, para usarla como orden de trabajo.

---

## PARTE 2 — Entrada F: edad con unidad

### Por qué deja de ser decisión y pasa a implementación

F estaba abierta porque no sabíamos si la edad en meses/días era exclusiva de P35.0. No lo es. Del inventario salen **al menos nueve fichas** que piden edad con unidad:

| Ficha | Unidades que pide el documento |
|---|---|
| A37.0 Tos ferina | Años / Meses |
| B01 Varicela | Año / Mes / Día |
| B26 Parotiditis | Año / Mes / Día |
| B04X Mpox | Años / Meses / Días |
| Y59.0 ESAVI | Años, Meses, Días, Horas, Minutos |
| A33 Tétanos neonatal | Años / Meses |
| A35 Tétanos | Años / Meses |
| A00 EDA / cólera | Años / Meses / Días |
| P35.0 SRC | **Meses / Días** (sin años) |

Un parche por ficha se repetiría nueve veces o más. Es cambio del núcleo.

**Esa tabla viene del documento generado, que ya demostró ser lossy. Verifica ficha por ficha contra la página del PDF antes de declarar nada.** La tabla es la hipótesis, no el dato.

### Fase F1 — Diseño, y PARA aquí

No escribas código todavía. Repórtame:

1. Quién consume hoy `edadDesdeFecha()` y `persona.fecha_nac`: formulario, `ver.php`, importación masiva, reportes, exportaciones. Cualquier cosa que rompa si la edad deja de ser siempre en años.
2. Si `persona` o `caso` ya tienen alguna columna de edad además de `fecha_nac`, o si la edad es puramente derivada.
3. Tu propuesta concreta para el mecanismo, contrastada con esta que te propongo:

   - Declarativo por ficha en el manifiesto, con la misma gramática que `nucleo_omitidos` y `columnas_sujeto`:
     ```json
     "unidades_edad": ["MESES", "DIAS"]
     ```
     Ausente = `["ANIOS"]`, que es el comportamiento actual. Así las 24 fichas siguen igual hasta que cada una declare lo suyo, y no hay `if ($cie10 === ...)` en ninguna parte.
   - `persona.fecha_nac` **no cambia de semántica**. Sigue siendo la fuente para `edadDesdeFecha()` y para los reportes.
   - La edad capturada se guarda con su unidad (valor + unidad), sin sustituir a `fecha_nac`. Las dos coexisten porque el PDF pide las dos: P35.0 tiene el ítem 11 (edad en meses/días) y el ítem 13 (fecha de nacimiento) por separado.
   - Si `fecha_nac` está lleno, la unidad declarada decide cómo se **muestra** la edad; el valor capturado a mano queda como respaldo cuando no hay fecha de nacimiento.

   Si algo de esto no calza con cómo está construido el núcleo hoy, dilo y propón otra cosa. No lo fuerces.

4. El costo real en archivos, sabiendo que el núcleo **no es extensible por manifiesto** (entrada J): migración, `NUCLEO_OMITIBLES`, `datos-paciente-nucleo.php`, `Caso.php`, los 5 puntos de `CasosController`, `ver.php`, `ImportacionController`.

**Para aquí y espera mi visto bueno.** No implementes la Fase F2 esta noche.

### Fase F3 — solo cuando F2 esté cerrada

Declarar `unidades_edad` en las fichas que lo necesiten, una por una, **cada una verificada contra su página del PDF**, no contra el documento generado. P35.0 va con `["MESES","DIAS"]`.

---

## Cierre

Commit por parte: uno para la Parte 1 (`MAPEO_FICHAS_PDF.md` + las discrepancias anotadas en `PENDIENTES.md`) y ninguno para la Parte 2 hasta que yo apruebe el diseño.
