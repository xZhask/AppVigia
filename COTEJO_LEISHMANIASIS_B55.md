# Cotejo e Implementación: Ficha B55 · Leishmaniasis · Págs. 45-46

> Documento de registro y bitácora técnica de la implementación de la Ficha de Investigación Clínico-Epidemiológica de Leishmaniasis (CIE-10: B55).

---

## 1. Inventario Declarativo

| Mecanismo | PDF Págs. 45-46 | Declaración en Sistema / Manifiesto |
|---|---|---|
| `nucleo_omitidos` | No pide etnia, pueblo étnico, referencia para localizar, ni datos de tutor | `["referencia_localizar", "pueblo_etnico", "etnia", "nombre_tutor", "celular_tutor"]` |
| `unidades_edad` | Años | Por defecto (años) |
| `columnas_sujeto` | Sujeto único (caso) | Ninguno |
| `tablas_hijas` | Muestra de laboratorio | `caso_muestra: true`, `caso_contacto: false`, `caso_viaje: false`, `caso_vacuna: false` |
| `lugar_contagio` | Localidad, Distrito, Provincia, Departamento | Selector Ubigeo (`selector-ubigeo.php`) a `caso.lugar_contagio_distrito_id` y `caso.lugar_contagio_localidad` |
| `depende_de` | Alergia medicinas, antecedentes otras, enfermedad mucosa, llenado por otro | Declarados con `depende_de` y `valor_activador` |
| `b55_lesiones` | Marcar y numerar lesiones + tabla con cálculo de superficie | Partial dedicado: `leishmaniasis-body-map.php` (SVG interactivo + tabla) |
| `b55_compromiso_de_estructuras` | 4 diagramas ORL + matriz de estructuras x compromiso/eritema/edema/infiltración/úlcera | Partial dedicado: `leishmaniasis-mucosa.php` (SVG ORL + matriz) |
| `laboratorio` | Frotis, Cultivo, Histopatología, IDR, ELISA, PCR | Matriz `b55_pruebas_laboratorio` + `caso_muestra` |

---

## 2. Tabla de Cotejo Campo por Campo (PDF Págs. 45-46)

| N° | Sección PDF | Ítem / Etiqueta en PDF | Tipo | Clave en Manifiesto | Estado / Acción |
|---|---|---|---|---|---|
| 1 | Encabezado | Definición de casos (Cutánea, Mucosa, Visceral) | Guía | *Visual / Callout* | `AGREGAR` |
| 2 | I. Datos Generales | Apellidos y Nombres, Nacimiento, Edad, Residencia habitual | Núcleo | `persona.*`, `caso.*` | `OK` (Núcleo) |
| 3 | II. Antecedente Epid. | Lugar de contagio: Dpto / Prov / Dist / Localidad | Núcleo | `caso.lugar_contagio_*` | `OK` (Ubigeo) |
| 4 | II. Antecedente Epid. | Tiempo de permanencia: Días / Meses / Años | NUMERO | `b55_permanencia_dias`, `b55_permanencia_meses`, `b55_permanencia_anios` | `AGREGAR` |
| 5 | II. Antecedente Epid. | Actividad que desarrolló durante el contagio | SELECT | `b55_actividad_que_desarrollo_durante_el_contagio` | `OK` |
| 6 | II. Antecedente Epid. | ¿Existen otras personas con lesiones similares? | BOOLEANO | `b55_existen_otras_personas_con_lesiones_similares` | `OK` |
| 7 | III. Datos Clínicos | Síntomas (Dolor, Fiebre, Prurito, Tupidez, Disfonía, Dificultad resp., Tos, Pérdida de peso) | MULTISELECT | `b55_sintomas` | `OK` |
| 8 | III. Datos Clínicos | Antecedente de otras enfermedades (TBC, VIH, Chagas, Otras) | MULTISELECT | `b55_antecedente_de_otras_enfermedades` | `OK` |
| 9 | III. Datos Clínicos | Otras enfermedades (especificar) | TEXTO | `b55_antecedente_otras_especificar` | `AGREGAR` (depende) |
| 10 | III. Datos Clínicos | Alergia a medicinas | BOOLEANO | `b55_alergia_a_medicinas` | `OK` |
| 11 | III. Datos Clínicos | Alergia a medicinas (especificar) | TEXTO | `b55_alergia_a_medicinas_especificar` | `OK` (depende) |
| 12 | III. Datos Clínicos | Fecha de última regla (FUR) | FECHA | `b55_fecha_de_ultima_regla` | `OK` |
| 13 | III. Datos Clínicos | MAC usado | TEXTO | `b55_mac_usado` | `OK` |
| 14 | III. Datos Clínicos | Medicinas usadas actualmente | TEXTAREA | `b55_medicinas_usadas_actualmente` | `OK` |
| 15 | Lesiones Cutáneas | N.° de lesiones activas | NUMERO | `b55_n_de_lesiones_activas` | `OK` |
| 16 | Lesiones Cutáneas | N.° de cicatrices | NUMERO | `b55_n_de_cicatrices` | `OK` |
| 17 | Lesiones Cutáneas | Marcar y numerar lesiones + Tabla (#, Fecha inicio, Tipo, Localización, Ganglios, Infección, Diámetros, Superficie) | MATRIZ | `b55_lesiones` | `AGREGAR` (Partial SVG + Tabla) |
| 18 | Enfermedad Mucosa | Enfermedad mucosa (Sí/No) | BOOLEANO | `b55_enfermedad_mucosa` | `OK` |
| 19 | Enfermedad Mucosa | Fecha de inicio de síntomas mucosa | FECHA | `b55_fecha_de_inicio_de_sintomas_mucosa` | `OK` (depende) |
| 20 | Enfermedad Mucosa | Tiempo (años) / Tiempo (meses) | NUMERO | `b55_tiempo_anos`, `b55_tiempo_meses` | `OK` (depende) |
| 21 | Enfermedad Mucosa | Diagramas ORL + Compromiso de estructuras (Nariz, Boca, Faringe, Epiglotis, Cuerdas vocales, Otros) | MATRIZ | `b55_compromiso_de_estructuras` | `AGREGAR` (Partial SVG + Matriz) |
| 22 | Leishmaniasis Visceral | Signos de leishmaniasis visceral (14 signos) | MULTISELECT | `b55_signos_de_leishmaniasis_visceral` | `OK` |
| 23 | VI. Laboratorio | Pruebas (Frotis directo, Cultivo, Histopatología, IDR, ELISA, PCR) | MATRIZ | `b55_pruebas_laboratorio` | `AGREGAR` |
| 24 | Diagnóstico | Forma (Cutánea, Mucosa, Visceral) | SELECT | `b55_forma` | `OK` |
| 25 | Diagnóstico | Situación (Primer episodio, Reinfección, Recaída, Falla trat.) | SELECT | `b55_situacion` | `OK` |
| 26 | Diagnóstico | Tratamiento (Adecuado, Inadecuado) | SELECT | `b55_tratamiento` | `OK` |
| 27 | Llenado de Ficha | Ficha llenada por (Médico, Enfermera, Téc. Enfermería, Otros) | SELECT | `b55_ficha_llenada_por` | `AGREGAR` |
| 28 | Llenado de Ficha | Ficha llenada por (especificar) | TEXTO | `b55_ficha_llenada_por_especificar` | `AGREGAR` (depende) |

---

## 3. Registro de Modificaciones Realizadas

### 3.1 Archivos Creados
- [`app/Views/partials/campos/leishmaniasis-body-map.php`](file:///c:/laragon/www/AppVigia/app/Views/partials/campos/leishmaniasis-body-map.php):
  - Siluetas vectoriales SVG para cuerpo completo (frente y espalda, con selector de género y líneas guía anatómicas) y rostro (frontal, perfil izquierdo, perfil derecho).
  - Marcadores de lesiones interactivos numerados (`[1]`, `[2]`, etc.) con distinción cromática (úlcera/nódulo activa vs cicatriz).
  - Tabla dinámica de lesiones sincronizada con auto-cálculo de superficie en $\text{mm}^2$ (`(d1 × d2)/4`).
  - Serialización JSON en tiempo real para persistencia.
- [`app/Views/partials/campos/leishmaniasis-mucosa.php`](file:///c:/laragon/www/AppVigia/app/Views/partials/campos/leishmaniasis-mucosa.php):
  - 4 ilustraciones anatómicas vectoriales ORL (Corte sagital nasofaríngeo, Laringoscopia de cuerdas vocales/epiglotis, Rinoscopia anterior de narinas/septo, y Cavidad oral abierta).
  - Matriz de compromiso de estructuras con checkboxes (`Compromiso`, `Eritema`, `Edema`, `Infiltración`, `Úlcera`) y descripción de características.
  - Interactividad bidireccional entre las regiones del SVG y las filas de la matriz.

### 3.2 Archivos Modificados
- [`manifiesto_fichas.json`](file:///c:/laragon/www/AppVigia/manifiesto_fichas.json):
  - Actualización completa de la ficha `B55`: 8 secciones, 29 campos, dependencias condicionales, `nucleo_omitidos` y marcado como `"cotejada": true`.
- [`app/Views/partials/secciones-clinicas.php`](file:///c:/laragon/www/AppVigia/app/Views/partials/secciones-clinicas.php):
  - Inyección del selector Ubigeo para *Lugar de contagio* con las columnas del núcleo `caso.lugar_contagio_distrito_id` y `caso.lugar_contagio_localidad`.
  - Agrupación en 3 columnas para *Tiempo de permanencia* (días, meses, años).
  - Enrutamiento por clave para renderizar `leishmaniasis-body-map.php` (`b55_lesiones`) y `leishmaniasis-mucosa.php` (`b55_compromiso_de_estructuras`).
  - Tarjeta informativa / Callout con la definición oficial de casos probables (Cutánea, Mucosa, Visceral).
- [`app/Core/ayudantes.php`](file:///c:/laragon/www/AppVigia/app/Core/ayudantes.php):
  - Soporte de renderizado legible para matrices (`b55_lesiones`, `b55_compromiso_de_estructuras`, `b55_pruebas_laboratorio`) en la vista de consulta `ver.php` (`campoValorTexto()`).
- [`MAPEO_FICHAS_PDF.md`](file:///c:/laragon/www/AppVigia/MAPEO_FICHAS_PDF.md):
  - Registro de Leishmaniasis (B55) como `✅ cotejada`.

---

## 4. Resultados de Verificación

1. **`verificar_fichas.php` (Manifiesto ↔ Base de Datos):**
   - **Resultado:** `Leishmaniasis (B55): 8 / 8 secciones, 29 / 29 campos: ✅ OK`.
2. **`verificar_claves.php` (Código ↔ Claves):**
   - **Resultado:** `Claves revisadas: 254. Faltantes: 0. ✅ OK`.
3. **`verificar_render.php` (BD ↔ HTML de Nueva Ficha):**
   - **Resultado:** `B55 (Leishmaniasis): Declarados: 29, Presentes: 29, Huérfanos: 0. ✅ OK`.
4. **Prueba Integral CRUD (`scratch/test_b55_crud.php`):**
   - Creación de caso de prueba con lesiones cutáneas con coordenadas y dimensiones, compromiso mucoso y signos clínicos.
   - Persistencia confirmada en base de datos (`caso_valor` y `caso`).
   - Lectura y formateo legible verificado.
   - Eliminación limpia (0 filas residuales).

