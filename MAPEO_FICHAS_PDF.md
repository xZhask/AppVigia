# Mapeo de fichas — documento PDF (27 entradas) ↔ manifiesto (24 fichas)

Generado 2026-08-02 (`docs/peticiones/PETICION_MAPEO_Y_EDAD.md`, Parte 1). Sirve para saber,
ante cualquier ficha, qué página del compendio MINSA (`INFORME PARA
APLICATIVO DE EPIDEMIOLOGIA_removed.pdf`) hay que abrir para cotejarla.

La cuenta cierra: 27 − 2 (ESAVI son 3 entradas del documento y 1 sola ficha
del manifiesto: Y59.0) − 1 (la entrada 9, "Registro de Búsqueda Activa", no
tiene ficha propia en el manifiesto) = 24.

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
| 9 | Gestantes VIH y Niños Expuestos — Registro Búsqueda Activa | 15 | **ninguno** | ver discrepancia 1 en PENDIENTES.md |
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
| 27 | EDA Grave — Cólera | 50-51 | A00 | ficha real de 2 páginas — ver discrepancia 2 en PENDIENTES.md; el manifiesto declara `pdf_paginas: 50` (falta la 51) |

## Pendiente de cotejar (18)

En el orden de la tabla: A37.0, B01, B04X, Y59.0 (3 entradas), Z21, B24,
A50, A33, A35, Y07, A95, P96, V99, B57, A44, B55, A97, A00.
