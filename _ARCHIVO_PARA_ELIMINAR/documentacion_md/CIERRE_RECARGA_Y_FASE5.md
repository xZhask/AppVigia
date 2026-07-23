# VIGÍA · Cierre de la recarga: verificación, decisiones y Fase 5

Continuación de `RECARGA_FICHAS.md` tras el resultado 24/24 OK. Resuelve las
6 decisiones abiertas de `CAMBIOS_MANIFIESTO.md`, verifica lo que la recarga
pudo haber borrado y ejecuta la Fase 5.

Siguen vigentes las reglas de diseño de `PLAN_CLAUDE_CODE.md`.

> **Antes de nada: hacer el commit de las Fases 1-4.** Hay cuatro fases de
> trabajo sin comitear, incluida una migración completa de la base. Comitear
> primero, para poder volver a este punto si algo sale mal más adelante.

---

## PARTE 0 — Verificar lo que la recarga pudo haber borrado (prioridad alta)

La Fase 3 hizo `DELETE FROM seccion_def` + `INSERT` de todos los `campo_def`.
Si `manifiesto_fichas.json` no llevaba ciertos atributos, **se perdieron en
silencio** — y `verificar_fichas.php` no los detectaría, porque solo compara
secciones, campos, tipos y catálogos.

Verificar, para las 24 fichas, si sobrevivieron:

1. **`campo_def.sensible`** — las marcas de confidencialidad de VIH/SIDA,
   orientación sexual y prácticas sexuales (Mpox), etnia, violencia familiar y
   antecedentes de muerte materna. Si se perdieron, esos campos aparecen ahora
   en listados, reportes y exportaciones, contra lo definido en
   `DEFINICION_FICHAS_B_C_D.md` sección 0.b.
2. **`campo_def.depende_de` y `valor_activador`** — toda la lógica condicional
   definida en `AUDITORIA_FICHA_DIFTERIA.md` punto 4 (hospitalizado → campos de
   hospitalización, gestante → semanas de gestación, etc.). El propio
   `INFORME_CARGADOR.md` menciona que hubo que limpiar esa FK antes del
   `DELETE`, lo que confirma que existía.
3. **`campo_def.origen`** (FICHA_MINSA / INTERNO).
4. **`enfermedad.opciones_clasificacion`**, `usa_contactos`, `usa_muestras`,
   `usa_viajes`, `usa_vacunas` y las columnas de configuración de tablas hijas.

**Si algo se perdió:**
- Incorporarlo al manifiesto como atributo de cada campo/ficha.
- Hacer que `cargar_fichas.php` lo cargue.
- Ampliar `verificar_fichas.php` para verificarlo, igual que hizo con catálogos.
- Recargar.

**Regla:** el manifiesto debe contener **todo** lo necesario para reconstruir
las definiciones desde cero. Si un atributo vive solo en la base y no en el
manifiesto, la próxima recarga lo borra otra vez.

---

## PARTE 1 — Las 6 decisiones resueltas

### 1.1 PFA — clasificación final: se usan los 5 valores del PDF

Los 5 valores son correctos y epidemiológicamente necesarios: distinguir polio
salvaje de polio derivado de la vacuna es la diferencia entre una emergencia
sanitaria internacional y un evento asociado a la inmunización. No se reducen.

Como no caben en el ENUM del núcleo (`caso.clasificacion`), se modelan igual que
la doble clasificación de difteria:

- **Núcleo** (`caso.clasificacion`), vía `opciones_clasificacion`:
  `CONFIRMADO, PROBABLE, DESCARTADO`
- **Campo propio de la ficha** — "Clasificación final (detalle)", SELECT con:
  Polio salvaje · Polio derivado de la vacuna · Polio asociado a la vacuna ·
  Polio compatible · Descartado
- Correspondencia documentada: los tres primeros son `CONFIRMADO`,
  "compatible" es `PROBABLE`, "descartado" es `DESCARTADO`. Si es viable,
  autocompletar el núcleo a partir del campo de detalle; si no, dejarlo manual
  y validar la coherencia al guardar.

Agregar también los campos "Criterios para clasificación" y "Se ha descartado"
que el PDF trae junto a la clasificación, si no están.

### 1.2 Tos ferina — `usa_contactos` se mantiene en 1

**El hallazgo de `CAMBIOS_MANIFIESTO.md` parece incorrecto en este punto.** El
texto extraído del PDF para esta ficha incluye una fila de encabezados de censo
nominal: *N° · Apellidos y Nombres · Parentesco/vínculo · Celular ·
Doc. identidad · Lugar de exposición*, y la nota al pie indica *"a los contactos
directos identificados, complete el Formato de censo y seguimiento de
contactos"*.

**Verificar en la página del PDF.** Si esa tabla existe: `usa_contactos = 1` es
correcto y el flag se queda. La matriz agregada de "contactos por lugar" y el
censo nominal **coexisten**: son dos instrumentos distintos, no duplicados.

### 1.3 Parotiditis — `usa_muestras` baja a 0

Confirmado: la ficha de parotiditis no tiene sección de laboratorio (a
diferencia de varicela, que sí la trae). Bajar el flag a 0.

Si operativamente se necesitara registrar muestras para esta enfermedad, sería
una extensión institucional deliberada, y debe marcarse `origen = 'INTERNO'`,
nunca presentarse como parte de la ficha MINSA.

### 1.4 Sarampión — implementar "Cadena de transmisión"

Se implementa. Va en `caso_contacto`, no en `campo_def`. Requiere ampliar la
tabla hija (ver Parte 2), con las columnas que pide el PDF:

fecha del contacto · lugar de contacto · fecha de inicio de erupción ·
fecha de vacunación · vacunado dentro de 72 horas del contacto

Verificar además que el widget de `caso_contacto` se muestre efectivamente en
la ficha de sarampión (`INFORME_CARGADOR.md`, C.4 dejó la duda de si depende de
configuración adicional).

### 1.5 ESAVI Anexo 6.2 — implementar la capacidad, diferir el contenido

**Implementar ahora:** secciones condicionales a nivel de sección.

```sql
ALTER TABLE seccion_def
  ADD COLUMN depende_de INT NULL,
  ADD COLUMN valor_activador VARCHAR(60) NULL,
  ADD CONSTRAINT fk_seccion_depende FOREIGN KEY (depende_de) REFERENCES campo_def(id);
```

Es extender a nivel de sección el mecanismo que ya existe a nivel de campo, y
sirve para cualquier ficha futura con anexos condicionales. Una sección con
`depende_de` no se renderiza mientras el campo disparador no tenga el valor
activador.

**Diferir:** la carga del contenido del Anexo 6.2 (PDF pág. 7-8). Son ~12
secciones de lista de chequeo que solo aplican cuando la clasificación final de
ESAVI es 2 (defecto de calidad) o 3 (error en la inmunización). Cargarlo en una
sesión dedicada, con el manifiesto y el cargador ya estables.

### 1.6 Simplificaciones: una se acepta con ajuste, la otra se revierte

**Chagas — aceptar la SELECT combinada, pero recuperar lo que se perdió.**
Las 6 combinaciones (forma × confirmado/descartado) son claras y reportables.
Pero la tabla del PDF también pide **fecha de clasificación** y **criterio de
descarte**, que la simplificación eliminó. Agregar ambos como campos propios; el
criterio de descarte, condicional a que la clasificación sea "descartado". No
hace falta `MATRIZ`.

**VIH/SIDA — revertir el aplanamiento, con dos campos encadenados.**
Los 11 valores planos rompen el reporte: calcular "casos por vía sexual" obliga
a sumar 4 opciones en vez de filtrar una. Modelar como:

- **Vía de transmisión** (SELECT): Sexual · Parenteral · Madre-niño (vertical) ·
  Desconocida
- **Subtipo** (SELECT, con `depende_de` sobre el anterior):
  - si Sexual → Heterosexual · Homosexual · Bisexual · No determinado
  - si Parenteral → Transfusión de sangre y/o derivados · Compartir agujas/UDI ·
    Accidente con material contaminado · Trasplante de órganos o tejidos ·
    No determinado

Usa el mecanismo `depende_de` que ya existe. Es fiel al PDF y mejor para reportes.

---

## PARTE 2 — Fase 5: ampliar las tablas hijas

De `RECARGA_FICHAS.md` Fase 5, más lo que exigen las decisiones de arriba.

```sql
-- caso_vacuna: hoy solo tiene vacuna/dosis/fecha
ALTER TABLE caso_vacuna
  ADD COLUMN fabricante        VARCHAR(120) NULL,
  ADD COLUMN lote              VARCHAR(60)  NULL,
  ADD COLUMN via               VARCHAR(40)  NULL,
  ADD COLUMN sitio             VARCHAR(60)  NULL,
  ADD COLUMN fecha_vencimiento DATE         NULL,
  ADD COLUMN establecimiento   VARCHAR(160) NULL;

-- caso_sujeto: sujetos que no son el paciente índice necesitan ubicación
ALTER TABLE caso_sujeto
  ADD COLUMN distrito_id CHAR(6)      NULL,
  ADD COLUMN direccion   VARCHAR(200) NULL,
  ADD CONSTRAINT fk_cs_distrito FOREIGN KEY (distrito_id) REFERENCES distrito(id);

-- caso_viaje: cubre "Lugar probable de infección"
ALTER TABLE caso_viaje
  ADD COLUMN lugar_institucion VARCHAR(200) NULL,
  ADD COLUMN permanencia_dias  SMALLINT     NULL;

-- caso_contacto: cadena de transmisión de sarampión
ALTER TABLE caso_contacto
  ADD COLUMN fecha_contacto         DATE         NULL,
  ADD COLUMN lugar_contacto         VARCHAR(160) NULL,
  ADD COLUMN fecha_inicio_erupcion  DATE         NULL,
  ADD COLUMN vacunado_72h           ENUM('SI','NO','DESCONOCIDO') NULL;
```

Después de ampliarlas:

1. **Retirar de `campo_def` lo que ahora cabe en la tabla hija.** En particular
   la sección "Datos de la vacunación" de ESAVI (fabricante, lote, vía, sitio,
   fecha de expiración) y la "Residencia habitual de la madre" de muerte fetal
   y neonatal — `CAMBIOS_MANIFIESTO.md` los documentó como soluciones ad-hoc
   por tablas hijas angostas. Ya no hacen falta.
2. **Configurar qué columnas muestra cada ficha.** Ninguna ficha debe ver las
   columnas que no le corresponden: la de contactos de sarampión no es la de
   difteria. Usar la configuración por ficha que ya existe para `caso_contacto`.
3. Ajustar el manifiesto y recargar con el cargador único (ya es idempotente).

---

## PARTE 3 — Prueba de humo en la interfaz

La verificación confirma que la **base** está correcta. No confirma que las
fichas **se rendericen bien**: un campo puede estar perfecto en `campo_def` y
verse mal en pantalla — ya pasó con `GRUPO_SI_NO` (etiquetas concatenadas
dentro del control) y con desplegables que se salían de la tarjeta.

Abrir en el navegador y revisar de punta a punta al menos:

- **Difteria** — la referencia ya validada
- **PFA** — la de más `MATRIZ` (fuerza muscular, tono, reflejos por segmento)
- **Sarampión** — la más extensa y con `CRONOLOGIA`
- **Muerte materna** — la de más campos (~83) y multi-sujeto
- **Mpox** — para confirmar que los campos sensibles se comportan como tales

Verificar en cada una: que todas las secciones aparezcan, que los desplegables
tengan opciones, que las condicionales oculten y muestren bien, que se pueda
guardar y reabrir sin perder datos, y que no haya etiquetas concatenadas ni
controles desbordados.

---

## Verificación final

- [ ] Fases 1-4 comiteadas antes de empezar
- [ ] `sensible`, `depende_de`, `valor_activador` y `origen` sobrevivieron o se
      restauraron, y ahora viven en el manifiesto
- [ ] El verificador comprueba también esos atributos
- [ ] PFA tiene los 5 valores del PDF como campo propio
- [ ] Se verificó en el PDF si tos ferina tiene censo nominal de contactos
- [ ] Parotiditis con `usa_muestras = 0`
- [ ] Sarampión captura la cadena de transmisión y el widget se muestra
- [ ] Existe la capacidad de sección condicional (Anexo 6.2 queda pendiente)
- [ ] Chagas recuperó fecha de clasificación y criterio de descarte
- [ ] VIH/SIDA usa vía + subtipo encadenados
- [ ] Las cuatro tablas hijas ampliadas y los campos ad-hoc retirados
- [ ] Las 5 fichas de prueba se abren, guardan y reabren sin defectos visuales
- [ ] `theme.css` sin cambios; sin emojis ni librerías externas
