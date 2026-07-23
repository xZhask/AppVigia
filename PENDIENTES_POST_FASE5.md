# VIGÍA · Pendientes tras el cierre de la Fase 5

Cuatro puntos detectados al revisar `manifiesto_fichas.json` y los hallazgos de
`CIERRE_RECARGA_Y_FASE5.md`. Ordenados por prioridad.

Siguen vigentes las reglas de diseño de `PLAN_CLAUDE_CODE.md`.

> **Comitear primero** todo lo aplicado hasta ahora. En el mensaje del commit
> dejar constancia de que la Parte 3 (prueba de humo) fue a nivel de plantilla
> PHP, no una verificación real en navegador — esa sigue pendiente y la hará el
> usuario.

---

## 1. Sífilis materna y congénita sin marcas de sensibilidad (prioridad alta)

`DEFINICION_FICHAS_B_C_D.md`, sección 0.b, exige marcar como `sensible = 1`
"todos los campos de VIH/SIDA, **sífilis**, violencia familiar y los
antecedentes de la ficha de muerte materna".

Estado actual en el manifiesto:

| Ficha | Campos sensibles / total |
|---|---|
| Z21 · Gestante con VIH y niño expuesto | 42 / 42 ✅ |
| B24 · VIH / SIDA | 26 / 26 ✅ |
| Y07 · Violencia familiar | 25 / 25 ✅ |
| B04X · Mpox | 11 ✅ |
| O95 · Muerte materna | 1 (antecedentes patológicos) ✅ |
| **A50 · Sífilis materna y congénita** | **0 / 25** ❌ |

Sífilis es una infección de transmisión sexual, y la ficha incluye el criterio
*"niño mayor de 2 años con signos de sífilis secundaria en el que se ha
descartado el antecedente de abuso sexual o contacto sexual"*. Esos campos no
pueden aparecer en listados, reportes agregados ni exportaciones.

**Acción:** marcar `sensible = 1` en los campos de A50 (clínicos y de
clasificación; los administrativos de encabezado pueden quedar fuera si se
justifica), actualizar el manifiesto, recargar la ficha y verificar.

**Además:** revisar si el mismo criterio debería aplicarse al Síndrome de
rubéola congénita (P35.0), que hoy tiene 0 campos sensibles. Reportar la
recomendación con su justificación; no aplicarla sin confirmación.

---

## 2. `caso_vacuna` sin catálogo — regresión de calidad de dato

Al mover la vacunación de ESAVI desde `campo_def` a `caso_vacuna` se perdieron
los catálogos cerrados: eran `SELECT` con los códigos MINSA y ahora son texto
libre. Es el problema del Excel reintroducido justo donde el sistema existe para
evitarlo: "Pentavalente", "pentavalente", "Penta" y "06" dejan de agrupar en los
reportes.

**La solución no es volver a duplicar en `campo_def`**, sino que la tabla hija
use catálogos, igual que el resto del sistema.

```sql
-- Catálogos compartidos, con los códigos del PDF (ESAVI, pág. 9-11)
-- vacuna:   01 BCG · 02 DPT · 03 APO · 04 Hepatitis B · 05 Hib · 06 Pentavalente
--           07 SPR · 08 Fiebre amarilla · 09 SR · 10 DT · 11 Influenza estacional
--           12 Antisarampión · 13 Neumococo · 14 Rotavirus · 15 VPH · 16 IPV
--           17 Varicela · 18 dTpa · 19 Anti COVID-19 · 20 Otro
-- via:      01 Oral · 02 Intradérmica · 03 Subcutánea · 04 Intramuscular
-- sitio:    01 Hombro derecho · 02 Hombro izquierdo · 03 Brazo derecho
--           04 Brazo izquierdo · 05 Vasto externo muslo derecho
--           06 Vasto externo muslo izquierdo · 09 Oral
-- dosis:    01 Primera · 02 Segunda · 03 Tercera · 04 Adicional · 05 Única · 06 Refuerzo
-- adyuvante: 01 Sí · 02 No

ALTER TABLE caso_vacuna
  ADD COLUMN vacuna_id    INT NULL,
  ADD COLUMN dosis_id     INT NULL,
  ADD COLUMN via_id       INT NULL,
  ADD COLUMN sitio_id     INT NULL,
  ADD COLUMN adyuvante_id INT NULL,
  ADD CONSTRAINT fk_cv_vacuna    FOREIGN KEY (vacuna_id)    REFERENCES catalogo_item(id),
  ADD CONSTRAINT fk_cv_dosis     FOREIGN KEY (dosis_id)     REFERENCES catalogo_item(id),
  ADD CONSTRAINT fk_cv_via       FOREIGN KEY (via_id)       REFERENCES catalogo_item(id),
  ADD CONSTRAINT fk_cv_sitio     FOREIGN KEY (sitio_id)     REFERENCES catalogo_item(id),
  ADD CONSTRAINT fk_cv_adyuvante FOREIGN KEY (adyuvante_id) REFERENCES catalogo_item(id);
```

- Conservar `vacuna` (texto) como respaldo para lo ya capturado y para el valor
  "Otro (especificar)". El campo obligatorio pasa a ser el `_id`.
- El widget de antecedentes vacunales usa desplegables con esos catálogos, no
  campos de texto.
- Estos catálogos son **compartidos**: los usan también tos ferina, varicela,
  parotiditis, difteria, fiebre amarilla, sarampión, tétanos y PFA. Crearlos una
  sola vez, no por ficha.
- El mismo criterio aplica a `caso_muestra` (tipo de muestra, tipo de prueba,
  resultado): revisar si quedaron como texto libre y, de ser así, llevarlos a
  catálogo en esta misma pasada.

---

## 3. Configurar qué columnas ve cada ficha en las tablas hijas

`CIERRE_RECARGA_Y_FASE5.md` asumió que esta capacidad existía. **No existe** —
verificado en el código; los widgets muestran todas las columnas. Al ampliar las
tablas hijas, la consecuencia ya es visible: las cuatro columnas nuevas de
sarampión (`fecha_contacto`, `lugar_contacto`, `fecha_inicio_erupcion`,
`vacunado_72h`) aparecen ahora en el widget de contactos de **todas** las fichas.
En difteria, cuyo censo pide edad, sexo, vacunado y profilaxis, el registrador ve
una columna de "fecha de inicio de erupción" que no aplica.

**Implementar la configuración por ficha:**

```sql
ALTER TABLE enfermedad
  ADD COLUMN columnas_contacto JSON NULL,
  ADD COLUMN columnas_muestra  JSON NULL,
  ADD COLUMN columnas_viaje    JSON NULL,
  ADD COLUMN columnas_vacuna   JSON NULL;
```

- Cada JSON lista las columnas activas de esa tabla hija para esa ficha.
- El widget renderiza solo esas columnas; si el JSON es `NULL`, muestra un
  conjunto mínimo por defecto (no todas).
- La configuración vive en el **manifiesto** y la carga `cargar_fichas.php`,
  igual que el resto — si vive solo en la base, la próxima recarga la borra.
- `verificar_fichas.php` la verifica.

**Configuración inicial según el PDF:**

| Ficha | Columnas de contacto |
|---|---|
| Difteria | nombres · edad · sexo · vacunado · fecha_vacunacion · profilaxis |
| Sarampión | nombres · edad · direccion · celular · fecha_contacto · lugar_contacto · fecha_inicio_erupcion · fecha_vacunacion · vacunado_72h |
| Tos ferina | nombres · parentesco · celular · doc · lugar_exposicion |
| Mpox | nombres · parentesco · celular · doc · tipo_exposicion · lugar_exposicion |
| PFA | nombres · edad · dosis_recibidas · fecha_ultima_dosis |

Completar el resto revisando el PDF ficha por ficha.

---

## 4. Muerte fetal y neonatal — residencia de la madre duplicada

`caso_sujeto` ya tiene `distrito_id` y `direccion` (Fase 5), pero P96 conserva
"Residencia habitual de la madre" como campo `TEXTO` en `campo_def`. Son dos
caminos para el mismo dato — el patrón que se viene eliminando.

**Acción:** retirar ese campo del manifiesto y capturar la residencia en el
sujeto de rol `MADRE` dentro de `caso_sujeto`, con selector de UBIGEO en lugar
de texto libre.

---

## Verificación

- [ ] Commit hecho, con la nota sobre el alcance real de la prueba de humo
- [ ] A50 con sus campos sensibles marcados y recargada
- [ ] Reportada la recomendación sobre P35.0 (sin aplicar)
- [ ] `caso_vacuna` usa catálogos compartidos; el widget usa desplegables
- [ ] Revisado el mismo criterio en `caso_muestra`
- [ ] Cada ficha muestra solo las columnas de tabla hija que le corresponden
- [ ] La configuración de columnas vive en el manifiesto y se verifica
- [ ] P96 captura la residencia de la madre en `caso_sujeto`, con UBIGEO
- [ ] `REPORTE_VERIFICACION.md` regenerado, 24/24 OK
- [ ] `theme.css` sin cambios; sin emojis ni librerías externas
