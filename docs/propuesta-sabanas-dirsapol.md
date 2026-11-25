# Propuesta: sábanas de Epidemiología DIRSAPOL en VIGÍA SPNP

Estado: **fases 1 y 2 hechas** en Reportes › Formatos.

- **Fase 1, sábana individual (24/11/2026):** formato limpio, el MES se calcula
  de la SE y se usan los 2 descansos médicos de VIGÍA (decisión 3 y dudas
  menores de §5).
- **Fase 2, sábana consolidada (25/11/2026):** decisión 1 resuelta. Sale de
  los registros de VIGÍA, cada atención en REG-PNP, sin formulario de conteos.
  Revisado de nuevo el 25/11, el Excel del Área trae conteos agrupados por
  fila (solo el 32 % de las filas vale 1, con hasta 158 casos en una fila),
  porque hoy las IPRESS mandan conteos por correo.
- **Fase 3:** falta la decisión 2 de §5.

Referencia en `HOJA_DE_RUTA.md` §3, punto 11.

El Área de Epidemiología de la DIRSAPOL lleva dos Excel anuales ("sábanas")
que arma con lo que envía cada IPRESS:

- `CONSOLIDADO_EDAS_IRAS_2026.xlsx`: conteos de EDA/IRA.
- `INDIVIDUAL_2026.xlsx`: un caso por fila.

El usuario los compartió para evaluar si VIGÍA puede generarlos. **Los archivos
se borraron del proyecto** porque contienen datos personales (nombres, DNI,
direcciones) y el repositorio es público. Si hacen falta de nuevo, pedirlos al
usuario y **no dejarlos dentro de la carpeta del proyecto ni en commits**.

Abajo está todo lo que se extrajo de ellos: estructura, columnas y cifras
agregadas, sin datos de personas.

## 1. Conclusión

Es viable. VIGÍA ya guarda casi todos los datos, porque sus formatos semanales
del Área son la versión por IPRESS y por semana de estas sábanas:

- *Registro semanal de notificación epidemiológica individual*:
  `FormatoPnp::individual()`, pantalla `/reportes/registro-individual`.
- *Registro semanal… consolidada*: `ConsolidadoPnp::semana()`, pantalla
  `/reportes/consolidado-pnp`.

Lo que falta:

1. Sacarlos **a nivel nacional y por un periodo** (año o rango de SE), en el
   formato largo de DIRSAPOL.
2. Agregar unas pocas columnas.
3. Una **tabla de equivalencias** entre los establecimientos de VIGÍA y las
   unidades y macrorregiones del Área.

## 2. Consolidado EDA/IRA (`CONSOLIDADO_EDAS_IRAS_2026.xlsx`)

### 2.1 Hojas

| Hoja | Contenido |
|---|---|
| `MACROS_2026_EDAS_IRAS` | La sábana 2026: 11 381 filas, SE 1–45 |
| `EDAS_IRAS_REGSAL_POR VER` | La misma estructura con **datos 2025** (unas 1 365 filas). Sirve como histórico |
| `PARA DESPLEGAR` | Listas para las validaciones de datos: Dx, macrorregiones, unidades, SE 1–53, meses, condición y grupos de edad |
| `Hoja1`–`Hoja5` | Vacías |

### 2.2 Columnas de `MACROS_2026_EDAS_IRAS` (fila 1 = encabezado)

| Col | Encabezado | Valores encontrados | Origen en VIGÍA |
|---|---|---|---|
| A | N° | (vacío) | Correlativo |
| B | GRADO | TITULAR 5406 · FAMILIAR 5503 · ALUMNO 396 · CADETE 76 | `persona.condicion` (EFECTIVO→TITULAR, DERECHOHABIENTE→FAMILIAR); alumno y cadete salen del tipo de formación (`FormatoPnp::TIPO_FORMACION`). **Hoy `ConsolidadoPnp::situacion()` solo da T/F y deja a alumnos y cadetes sin condición: hay que agregarlos** |
| C | DX. | IRA 6401 · DIARREA 4217 · NEUMONIA 605 · ASMA 86 · SOB 72 | `Cie10Vigilancia::grupoConsolidado(cie10_dx)` del registro REG-PNP (EDA→DIARREA, IRA, NEUMONIA, ASMA, SOBA→SOB); cólera = ficha A00 |
| D | EDAD | 5A+, 1-4A, 60A+, 20-59A, 2-11M, 2-4A, <1A, 5-9A, <2A, 10-19A, <2M | Los mismos rangos de `ConsolidadoPnp::columnas()`: EDA <1a/1-4a/5a+; IRA <2m/2-11m/1-4a/5a+; SOB y asma <2a/2-4a/5a+; neumonía de 5 a más 5-9/10-19/20-59/60+ |
| E | N° DE CASO | Conteo | `COUNT(*)` de los casos no anulados ni descartados |
| F | MACRO_REG | 23 valores (ver §4) | **Falta**: tabla de equivalencias |
| G | DEPARTAMENTO | 31 valores ("RED NORTE", "HUANCAYO", "CH.PNP.LNS"…) | **Falta**: tabla de equivalencias |
| H | DISTRITOS | **144 unidades** (en realidad es la IPRESS) | **Falta**: nombre del Área para cada establecimiento |
| I | SEMANAS EPI | 1–45 | `caso.semana_epi` |
| J | MES | ENERO… | Se calcula de la SE o de `fecha_notif` (definir cuál con Epidemiología) |
| K | ETAREO | >5A 7561 · <5A 741 · vacío 3079 | Se calcula de la edad |
| L | AÑO | 2026 | `caso.anio_epi` |
| M | OBS1 | Casi vacío; hospitalizados escritos a mano ("HOSPT", "1HOSPT", "2HOSPT"…) | `caso.hospitalizado` (y `reg_lugar_defuncion` para defunciones). Mejor como columna numérica de hospitalizados |
| N | FECHA DE CORREO (2025: RECEPCION UNIEPI) | Fecha serial de Excel | Fecha en que la IPRESS cerró la SE (`notificacion_semanal.creado_en`) |
| O, P | OBS3, VVVV | Vacías | — |

### 2.3 Volumen (clave para decidir)

Total 2026, SE 1–45: **66 272 casos**, unos **1 470 por SE** en todo el país.

| Dx | Casos | Porcentaje |
|---|---|---|
| IRA | 49 690 | 75 % |
| Diarrea | 14 334 | 22 % |
| Neumonía | 1 704 | 3 % |
| Asma | 356 | <1 % |
| SOB | 188 | <1 % |

Las unidades con más casos por SE:

| Unidad | Casos por SE |
|---|---|
| CH.PNP.LNS (Luis N. Sáenz) | 254 (unos 36 al día) |
| CHICLAYO | 114 |
| HOSP.REG.POL.AREQUIPA | 102 |
| H.PNP.ABL | 60 |
| POL.POL.ZARATE | 52 |
| POL.POL.DIRINCRI | 43 |
| POL.POL.CHORRILLOS | 39 |
| POS.MED.POL.VIPOL | 35 |

Hoy las IPRESS envían a DIRSAPOL **conteos agregados por correo**. En VIGÍA,
EDA e IRA se registran **una atención por paciente** en «Otras enfermedades
sujetas a vigilancia (registro PNP)» (REG-PNP), una decisión del 21/10. Para
que VIGÍA genere este consolidado completo, todas las IPRESS tendrían que
registrar cada atención. En los hospitales grandes eso es mucha carga si se
hace a mano. Ver la decisión 1 de §5.

## 3. Individual (`INDIVIDUAL_2026.xlsx`)

### 3.1 Hojas

| Hoja | Contenido |
|---|---|
| `individual_2026` | La sábana: **828 casos** de 2026 (fila 2 = encabezado; la fila 1 tiene notas "ACTUALIZADO POR LA UNIESA_CH.PNP…") |
| `INDIVIDUAL_24_8_26` | Copia anterior (corte al 24/08) con la misma estructura |
| `cie 10` | 123 códigos CIE-10 con su nombre |
| `MAS GRAFICOS D DENGUE`, `Hoja6`, `Hoja7` | Tablas y gráficos de trabajo (dengue) |
| `INTERNACIONAL`, `Hoja1`–`Hoja5` | Vacías |

### 3.2 Valores encontrados (agregados)

- **Condición:** TITULAR 429 · FAMILIAR 345 · ALUMNO 43 · CADETE 9.
- **Grado:** abreviaturas como S3.PNP, S2.PNP, SS.PNP, ST3.PNP, MAY.PNP,
  CAP.SPNP… Para el familiar dice "FAMILIAR". Hay variantes del mismo grado,
  como CRNEL.PNP y CRNL.PNP.
- **Situación:** ACTIVIDAD 400 · NO APLICA 392 · RETIRO 33 · DISPONIBILIDAD 1.
- **Tipo de Dx:** C 532 · D 210 · P 84.
- **CIE-10:** 48 códigos. Los principales: A97.0 (282), J10 (96), H10.9 (71),
  B01.9 (64), A27 (54), A79 (31), A97.1 (31) y B05 (30).
- **Grupo etario:** quinquenios ("0 - 4", "5 - 9"… "80 +años").
- **Columnas siempre vacías en 2026:** VACUNA, AISLAMIENTO y TIPO DE PBA.
  Hospitalización y alta, casi vacías.

### 3.3 Columnas (65, A–BM) y su origen en VIGÍA

Origen ✅ = ya está en `FormatoPnp::individual()` o en `caso`/`persona`;
⚠️ = se calcula o hay que agregarlo; ❌ = no existe.

| Col | Encabezado | Origen |
|---|---|---|
| A | N° | Correlativo |
| B | CONDICION | ✅ `persona.condicion` + tipo de formación (TITULAR/FAMILIAR/ALUMNO/CADETE) |
| C | GRADO | ✅ `grado_pnp.abreviatura` ("FAMILIAR" si es derechohabiente) |
| D | SITUACION | ✅ `persona.situacion_pnp` (NO APLICA para el familiar) |
| E | NOMBRE Y APELLIDO | ✅ |
| F | EDAD | ✅ (el Excel mezcla "35S" = semanas; VIGÍA usa años, meses o días) |
| G | SEXO | ✅ MASCULINO/FEMENINO |
| H | CARNET | ✅ `persona.cip` |
| I | DOCUMENTO DE IDENTIDAD | ✅ `persona.num_doc` |
| J | MACRO.REG | ❌ Tabla de equivalencias (§4). Escritura distinta a la del consolidado: `IX_MRDS_PNP_AREQUIPA` en vez de `IX_MRDSP_AREQUIPA` |
| K | SUB UNIDAD | ❌ Tabla de equivalencias (departamento o red del Área) |
| L | DISTRITO | ⚠️ Es la IPRESS (p. ej. "HR.AREQUIPA"): nombre del Área |
| M | LOCALIDAD | ✅ `caso.lugar_contagio_localidad` / `persona.localidad` |
| N | DIRECCION | ✅ `persona.direccion` |
| O | DX CIE 10 | ✅ `caso.cie10_dx` o el CIE-10 de la ficha |
| P | NOMBRE DEL DX. | ✅ `descripcionCie10()` |
| Q | TIPO DX: P,C,D | ✅ `FormatoPnp::TIPO_DX` (sospechoso y probable → P) |
| R | VACUNA | ✅ "protegido" SI/NO/IG del formato individual |
| S | FECH. INIC. SINTOMA | ✅ `caso.fecha_inicio_sintomas` |
| T | FECH. DIFUSION | ✅ (columna "difusion" del formato individual) |
| U | FECH. NOTIF | ✅ `caso.fecha_notif` |
| V | FICH.DE INVES | ✅ investigación SI/NO |
| W | VIG. PAS | ✅ vigilancia PAS |
| X | BUS. ACT | ✅ vigilancia ACT |
| Y | SEMANA EPI | ✅ `caso.semana_epi` |
| Z | MES | ⚠️ Se calcula |
| AA | GRUPO ETAREO | ⚠️ Quinquenios calculados de la edad |
| AB | AÑO | ✅ `caso.anio_epi` |
| AC | LUGAR PROB.DE INFECCION | ✅ lugar de contagio (provincia, distrito, localidad) |
| AD | OBSERVACIONES | ⚠️ Texto libre. Proponer: clasificación final y última nota de la bitácora |
| AE | ACTUALIZACIONDES DE LOS DIAGNOSTICOS | ⚠️ Historial de clasificación (`caso_bitacora`) |
| AF | OTROS | ⚠️ Libre |
| AG | AISLAMIENTO | ❌ Vacía en 2026; ver si alguna ficha MINSA lo trae |
| AH | HOSPITALIZADO | ✅ `caso.hospitalizado` |
| AI, AJ | FECH.INI.HOSP / FECH.TER.HOSP | ⚠️ Solo en las fichas MINSA que lo piden (`caso_valor`); no hay columna general |
| AK | SITUACION DEL PACIENTE | ⚠️ Se deriva: ambulatorio / hospitalizado / fallecido (`caso.fallecido`) |
| AL | FECHA DE FALLECIMIENTO | ⚠️ En las fichas que la piden (`caso_valor`) |
| AM | TIPO DE PBA. | ⚠️ Muestras (`caso_muestra`) si se quiere |
| AN | UNIDAD | ✅ `unidad_pnp.nombre` |
| AO | DESCANSO MEDICO | ✅ Si tiene descansos |
| AP–BA | FI/FT 1 a 6 (inicio y fin de hasta 6 descansos) | ⚠️ VIGÍA guarda **2** (`descanso1_*`, `descanso2_*`). Para 6, cambio de esquema (tabla `caso_descanso`) |
| BB | SI NO TIENE FECHA SOLO DIAS | ⚠️ Días de descanso calculados |
| BC | ALTA MEDICA | ✅ `caso.fecha_alta` |
| BD–BM | OTRAS OBS 1–10 | Libres, sin origen; dejar vacías o quitarlas |

El formato individual actual de VIGÍA (`ReportesController::exportarRegistroIndividual`)
ya escribe casi todo esto, pero para **un establecimiento y una SE**. Los
registros PNP de notificación colectiva (EDA, IRA, neumonía, SOB, asma) no van
en el individual: se cuentan en el consolidado (`FormatoPnp.php:56-60`). La
sábana de DIRSAPOL tampoco los trae uno por uno, así que es coherente.

## 4. Tabla de equivalencias (la pieza que falta)

| | Unidades | Agrupación |
|---|---|---|
| Excel del Área | **144** | Macrorregión > departamento/red del Área > unidad |
| VIGÍA | **82** establecimientos activos | 25 redes por departamento (`red_salud`, "Red Callao · Sanidad PNP") |

Ejemplos sacados del consolidado (macrorregión > departamento: unidades):

| Macrorregión > departamento | Unidades |
|---|---|
| CH.PNP.LNS > CH.PNP.LNS | CH.PNP.LNS |
| DIVRISSP_LIMA CENTRO > RED CENTRO | POL.POL.WRL, POL.POL.DIRINCRI |
| DIVRISSP_LIMA ESTE > RED ESTE | POL.POL.ZARATE, POL.POL.DINOES, POL.POL.CHACLACAYO |
| DIVRISSP_LIMA NORTE > RED NORTE | POS.MED.POL.INDEPENDENCIA, POS.MED.POL.PTE PIEDRA_ALUM, POS.MED.POL.PTE PIEDRA_EO_PNP, POL.POL.SN.DIEGO, POL.POL.SMP, POS.MED.POL.HUACHO, POL.POL.CARABAYLLO, POS.MED.POL.PTE PIEDRA |
| DIVRISSP_LIMA OESTE > RED OESTE | POS.MED.POL.VENTANILLA, POS.MED.POL.VIPOL, POS.MED.POL.DIRAVPOL, POL.POL.CALLAO |
| DIVRISSP_LIMA SUR > RED SUR | POS.MED.POL.ETS.SN.BARTOLO, POL.POL.MININTER, POL.POL.CHORRILLOS, POL.POL.CAÑETE |

Las 23 macrorregiones:

- **Macrorregiones de sanidad (MRDSP):** I Piura, II Lambayeque, III La Libertad,
  IV Loreto, V Huánuco, VI Junín, VII Cusco, VIII Ayacucho, IX Arequipa,
  X Puno, XI San Martín, XII Áncash, XIII Ucayali, XIV Tacna y XV Madre de Dios.
- **Lima (DIVRISSP):** Lima Centro, Lima Este, Lima Norte, Lima Oeste y Lima Sur.
- **Hospitales que van como su propia macrorregión:** HG.SAN JOSE, CH.PNP.LNS y H.PNP.ABL.

Diferencias a tener en cuenta:

- **Agrupación distinta.** DIRAVPOL está en «Red Callao» en VIGÍA y en
  «DIVRISSP_LIMA OESTE > RED OESTE» en el Área.
- **Más unidades en el Excel.** Algunas unidades del Área no son establecimientos
  de VIGÍA: Puente Piedra se divide en _ALUM y _EO_PNP, y aparecen nombres de
  ciudad como CHICLAYO, HUANUCO o JAEN.
- **Dos escrituras de la macrorregión.** El individual escribe `MRDS_PNP` y el
  consolidado `MRDSP`. Hay que acordar una sola.

Propuesta: agregar a `establecimiento` (o a una tabla `unidad_area`) el
**nombre corto del Área**, su **departamento o red del Área** y su
**macrorregión**, editables en Catálogos › Establecimientos, con una
migración que cargue una primera versión. Para unidades que no son
establecimientos de VIGÍA (como las divisiones de Puente Piedra), decidir si se
crean como establecimientos o si se agrupan.

## 5. Decisiones del usuario o de Epidemiología (antes de implementar)

1. **Conteos de EDA/IRA.**
   - Opción (a): las IPRESS registran **cada atención** en VIGÍA, como hoy en
     REG-PNP. La sábana sale completa, pero es mucha carga en los hospitales
     grandes. Se puede aliviar con la importación por Excel
     (`ImportacionController`); comprobar si admite REG-PNP con el
     diagnóstico.
   - Opción (b): VIGÍA recibe el **conteo semanal agregado** por IPRESS, como
     el correo de hoy. Es una funcionalidad nueva: formulario de conteos por
     condición × Dx × edad.
   - Opción (c): mixto, por establecimiento.
2. **Lista oficial de unidades y macrorregiones:** quién de Epidemiología la
   valida, y qué hacer con las 144 frente a los 82.
3. **Formato:** ¿la sábana debe salir **idéntica** (mismas columnas, nombres y
   orden, para seguir usando sus macros, tablas dinámicas y gráficos) o basta
   con los mismos datos? Si es idéntica, conservar los encabezados tal cual,
   aunque tengan errores ("ACTUALIZACIONDES", "AISLALMIENTO").

Otras dudas menores:

- **MES:** ¿se calcula de la SE o de la fecha de notificación?
- **ETAREO vacío:** en el consolidado, ¿por qué hay filas sin grupo?
- **Descansos médicos:** ¿se necesitan 6 o bastan los 2 de VIGÍA?

## 6. Plan por fases (estimación)

| Fase | Qué | Dónde | Esfuerzo |
|---|---|---|---|
| 1 | **Sábana individual:** Excel nacional por periodo (año o rango de SE), ADMIN (y JEFE_RED con su red), con las columnas de §3.3 | Reportes › Formatos (o una pestaña "Sábanas DIRSAPOL"); reutilizar `FormatoPnp::casos()` (hoy filtra `anio_epi` + `semana_epi`; generalizar a rango) y `LibroXlsx` | 2–3 días |
| 2 | **Sábana consolidada:** Excel largo (unidad × SE × condición × Dx × edad, con N° de casos), con alumno y cadete | Reutilizar `ConsolidadoPnp::semana()` por rango y sin agrupar por provincia y distrito de residencia | 2 días |
| 3 | **Tabla de equivalencias** (§4) en Catálogos › Establecimientos, con migración idempotente | `sql/add_*.sql` + §1.3 de la hoja de ruta | 1 día + validación |
| 4 | (Opcional) **Importar el histórico 2025** del consolidado (hoja `EDAS_IRAS_REGSAL_POR VER`) para el corredor endémico del Panel, que hoy dice "sin historia" | Tabla aparte de conteos históricos; no son casos | 1–2 días |

Las fases 1 y 2 funcionan sin la 3 (usan los nombres de VIGÍA) y se ajustan
cuando esté la tabla.

Para probar contra el Excel real, pedir de nuevo los archivos al usuario y
guardarlos **fuera del proyecto** (por ejemplo, en el scratchpad).
PhpSpreadsheet de AppsEstadistica no sirve con el PHP 8.1 de Laragon (pide
8.3). Se leyeron como zip + XML con `ZipArchive` y `XMLReader`, que bastan para
encabezados y conteos.
