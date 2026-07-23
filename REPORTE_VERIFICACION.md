# REPORTE_VERIFICACION.md

Generado por `verificar_fichas.php` el 2026-07-23 comparando la base de datos contra `manifiesto_fichas.json`.

No se modificó ninguna definición de ficha ni la base de datos: esta es una corrida de solo lectura.

**Metodología.** Las secciones y campos se emparejan por nombre/etiqueta normalizada (sin tildes, mayúsculas ni signos de puntuación), primero por coincidencia exacta y luego por similitud aproximada (contención o distancia de Levenshtein relativa ≤ 0.30). Para los campos SELECT/MULTISELECT/GRUPO_SI_NO/CRONOLOGIA (los mismos tipos que `cargar_fichas.php` exige con catálogo), además se verifica: que `catalogo_id` no sea NULL, que ese catálogo tenga al menos un `catalogo_item`, y que sus opciones (emparejadas con la misma normalización) coincidan con las del manifiesto — desde RECARGA_FICHAS.md Fase 4 (antes era una limitación conocida, ver INFORME_CARGADOR.md hallazgo A.2b). Limitación que sigue vigente: si una ficha consolida en la BD varias secciones del manifiesto en una sola (o al revés), puede reportarse una 'sección faltante' que en realidad solo cambió de nombre/agrupación — revisar el detalle antes de asumir contenido perdido.

---

## Resumen

| Ficha (CIE-10) | Enfermedad | Secciones esp. / enc. | Campos esp. / enc. | Estado |
|---|---|---|---|---|
| A97 | Dengue, chikungunya, zika y arbovirosis | 6 / 6 | 28 / 28 | ✅ OK |
| A37.0 | Tos ferina | 6 / 6 | 47 / 47 | ✅ OK |
| B01 | Varicela con complicaciones | 5 / 5 | 22 / 22 | ✅ OK |
| B26 | Parotiditis con complicaciones | 4 / 4 | 25 / 25 | ✅ OK |
| A36 | Difteria | 3 / 3 | 19 / 19 | ✅ OK |
| A95 | Fiebre amarilla | 5 / 5 | 36 / 36 | ✅ OK |
| B55 | Leishmaniasis | 6 / 6 | 26 / 26 | ✅ OK |
| B57 | Enfermedad de Chagas | 5 / 5 | 26 / 26 | ✅ OK |
| A44 | Enfermedad de Carrión (bartonelosis) | 9 / 9 | 28 / 28 | ✅ OK |
| B04X | Viruela del mono (Mpox) | 6 / 6 | 41 / 41 | ✅ OK |
| A00 | EDA grave / cólera | 6 / 6 | 34 / 34 | ✅ OK |
| A35 | Tétanos | 3 / 3 | 20 / 20 | ✅ OK |
| A33 | Tétanos neonatal | 4 / 4 | 27 / 27 | ✅ OK |
| A80 | Parálisis flácida aguda (PFA) | 7 / 7 | 45 / 45 | ✅ OK |
| B05 | Sarampión / rubéola / febriles eruptivas | 7 / 7 | 57 / 57 | ✅ OK |
| Z21 | Gestante con VIH y niño expuesto | 2 / 2 | 42 / 42 | ✅ OK |
| B24 | VIH / SIDA — notificación individual | 8 / 8 | 24 / 24 | ✅ OK |
| A50 | Sífilis materna y congénita | 3 / 3 | 25 / 25 | ✅ OK |
| P35.0 | Síndrome de rubéola congénita (SRC) | 5 / 5 | 27 / 27 | ✅ OK |
| Y59.0 | ESAVI severo | 9 / 9 | 43 / 43 | ✅ OK |
| Y07 | Violencia familiar | 6 / 6 | 25 / 25 | ✅ OK |
| V99 | Lesiones por accidentes de tránsito | 7 / 7 | 27 / 27 | ✅ OK |
| O95 | Muerte materna (Anexo 1 y 2) | 16 / 16 | 83 / 83 | ✅ OK |
| P96 | Muerte fetal y neonatal | 4 / 4 | 13 / 13 | ✅ OK |

---

## Detalle por ficha

### Dengue, chikungunya, zika y arbovirosis (`A97`)

✅ Sin diferencias detectadas (secciones y campos coinciden con el manifiesto).

### Tos ferina (`A37.0`)

✅ Sin diferencias detectadas (secciones y campos coinciden con el manifiesto).

### Varicela con complicaciones (`B01`)

✅ Sin diferencias detectadas (secciones y campos coinciden con el manifiesto).

### Parotiditis con complicaciones (`B26`)

✅ Sin diferencias detectadas (secciones y campos coinciden con el manifiesto).

### Difteria (`A36`)

✅ Sin diferencias detectadas (secciones y campos coinciden con el manifiesto).

### Fiebre amarilla (`A95`)

✅ Sin diferencias detectadas (secciones y campos coinciden con el manifiesto).

### Leishmaniasis (`B55`)

✅ Sin diferencias detectadas (secciones y campos coinciden con el manifiesto).

### Enfermedad de Chagas (`B57`)

✅ Sin diferencias detectadas (secciones y campos coinciden con el manifiesto).

### Enfermedad de Carrión (bartonelosis) (`A44`)

✅ Sin diferencias detectadas (secciones y campos coinciden con el manifiesto).

### Viruela del mono (Mpox) (`B04X`)

✅ Sin diferencias detectadas (secciones y campos coinciden con el manifiesto).

### EDA grave / cólera (`A00`)

✅ Sin diferencias detectadas (secciones y campos coinciden con el manifiesto).

### Tétanos (`A35`)

✅ Sin diferencias detectadas (secciones y campos coinciden con el manifiesto).

### Tétanos neonatal (`A33`)

✅ Sin diferencias detectadas (secciones y campos coinciden con el manifiesto).

### Parálisis flácida aguda (PFA) (`A80`)

✅ Sin diferencias detectadas (secciones y campos coinciden con el manifiesto).

### Sarampión / rubéola / febriles eruptivas (`B05`)

✅ Sin diferencias detectadas (secciones y campos coinciden con el manifiesto).

### Gestante con VIH y niño expuesto (`Z21`)

✅ Sin diferencias detectadas (secciones y campos coinciden con el manifiesto).

### VIH / SIDA — notificación individual (`B24`)

✅ Sin diferencias detectadas (secciones y campos coinciden con el manifiesto).

### Sífilis materna y congénita (`A50`)

✅ Sin diferencias detectadas (secciones y campos coinciden con el manifiesto).

### Síndrome de rubéola congénita (SRC) (`P35.0`)

✅ Sin diferencias detectadas (secciones y campos coinciden con el manifiesto).

### ESAVI severo (`Y59.0`)

✅ Sin diferencias detectadas (secciones y campos coinciden con el manifiesto).

### Violencia familiar (`Y07`)

✅ Sin diferencias detectadas (secciones y campos coinciden con el manifiesto).

### Lesiones por accidentes de tránsito (`V99`)

✅ Sin diferencias detectadas (secciones y campos coinciden con el manifiesto).

### Muerte materna (Anexo 1 y 2) (`O95`)

✅ Sin diferencias detectadas (secciones y campos coinciden con el manifiesto).

### Muerte fetal y neonatal (`P96`)

✅ Sin diferencias detectadas (secciones y campos coinciden con el manifiesto).

