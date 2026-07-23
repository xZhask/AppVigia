# VIGÍA · Condición del paciente y retiro de unidad policial

Referencia visual: **`vigia-paciente-mockup.html`** (autocontenido, ábrelo y
alterna entre las tres opciones).

Siguen vigentes las reglas de diseño de `PLAN_CLAUDE_CODE.md`.

---

## 1. Bugs visibles en la sección actual

- **La lógica condicional no se aplica.** Grado, situación, categoría, CIP y
  tipo de beneficiario están activos aunque "Es efectivo PNP" esté sin marcar.
- **Buscador en una lista de 3 opciones.** "Tipo de beneficiario" abre un
  desplegable con búsqueda para Titular / Derechohabiente. La regla vigente es:
  búsqueda solo con más de 5 opciones. Revisar si el mismo error se repite en
  otros campos cortos (situación, categoría, sexo).
- **El desplegable se sale de la tarjeta.** Debe quedar contenido o reposicionarse
  hacia arriba cuando no hay espacio abajo.
- **Rejilla irregular:** CIP solo en una fila, unidad a ancho completo, tipo de
  beneficiario suelto. Usar una rejilla de 3 columnas consistente.

---

## 2. Unificar `es_pnp` y `tipo_beneficiario` en un solo campo

Hoy son dos campos que dicen lo mismo: efectivo = titular, derechohabiente =
familiar. Se reemplazan por **una sola condición** con tres valores. Se agrega
`PARTICULAR` porque hoy no hay dónde registrar a quien se atiende sin ser
efectivo ni familiar (emergencias, convenios).

```sql
-- ============================================================
-- VIGÍA · Condición del paciente + retiro de unidad policial
-- ============================================================
USE vigia;

-- 1) Nueva condición unificada
ALTER TABLE persona
  ADD COLUMN condicion ENUM('EFECTIVO','DERECHOHABIENTE','PARTICULAR')
    NOT NULL DEFAULT 'PARTICULAR' AFTER distrito_id;

UPDATE persona SET condicion = CASE
  WHEN es_pnp = 1                             THEN 'EFECTIVO'
  WHEN tipo_beneficiario = 'DERECHOHABIENTE'  THEN 'DERECHOHABIENTE'
  ELSE 'PARTICULAR' END;

-- 2) Vínculo del derechohabiente con su titular
ALTER TABLE persona
  ADD COLUMN titular_id INT NULL AFTER condicion,
  ADD COLUMN vinculo_titular ENUM('CONYUGE','CONVIVIENTE','HIJO','PADRE','MADRE','OTRO')
    NULL AFTER titular_id,
  ADD CONSTRAINT fk_persona_titular FOREIGN KEY (titular_id) REFERENCES persona(id);

CREATE INDEX ix_persona_condicion (condicion) ON persona (condicion);
CREATE INDEX ix_persona_titular   ON persona (titular_id);

-- 3) Retirar las columnas redundantes
ALTER TABLE persona
  DROP COLUMN es_pnp,
  DROP COLUMN tipo_beneficiario;
```

**Actualizar** `01_esquema_vigia.sql` para que una instalación limpia nazca así.

---

## 3. Retirar la unidad / dependencia policial

Se posterga: no la exige ninguna ficha por ahora y aún no se dispone del padrón
de dependencias.

- **Quitar el campo del formulario** de datos del paciente.
- Quitar "Unidades PNP" del menú de catálogos.
- Retirarla de los cortes de reporte.
- **Conservar en la base de datos** la tabla `unidad_pnp` y la columna
  `persona.unidad_id` (queda vacía y sin costo). Así, cuando se consiga el
  padrón, reactivarla es solo trabajo de interfaz, sin migración.

---

## 4. Selector de condición

Tres opciones como **tarjetas de radio** en una fila (ver mockup). Cada una con
nombre y una línea de descripción:

| Opción | Descripción |
|---|---|
| Efectivo PNP | Titular de la Sanidad PNP |
| Derechohabiente | Familiar de un efectivo |
| Particular | Sin vínculo con la PNP |

- Implementar con `<input type="radio">` + `<label>`, no con botones: hace falta
  la semántica de grupo de opciones.
- Seleccionada: borde y fondo en `--accent` / `--accent-soft`.
- Es una tarjeta de radio, no un desplegable, porque **cambia qué campos
  aparecen**: la elección debe ser visible y deliberada.
- Las clases nuevas (`.cond-pick`, `.cond-opt`, `.cond-radio`, `.cond-panel`)
  van **al final de `theme.css`**, sin tocar nada existente.

---

## 5. Campos por condición

**Efectivo PNP**
- Grado (obligatorio) — agrupado por nivel, ordenado por jerarquía
- Categoría — solo si el grado es de oficial o suboficial; se oculta para
  cadete, alumno y empleado civil
- Situación (obligatorio): Actividad · Retiro · Disponibilidad
- CIP

**Derechohabiente**
- Vínculo con el titular (obligatorio): Cónyuge · Conviviente · Hijo(a) ·
  Padre · Madre · Otro
- Documento del titular + botón "Buscar titular" → enlaza `titular_id` con la
  persona encontrada. **Opcional**: si no se conoce, se deja vacío y la ficha se
  registra igual.
- Ningún campo de grado, situación, categoría ni CIP.

> El vínculo con el titular permite detectar **conglomerados familiares** —varios
> casos de la misma familia en pocos días—, que es una señal epidemiológica que
> hoy se pierde. Si prefieres no incluirlo por ahora, se puede omitir sin afectar
> el resto.

**Particular**
- Ningún campo adicional.

---

## 6. Comportamiento

- Al cambiar de condición, **limpiar los campos de la condición anterior** antes
  de guardar: no debe quedar un particular con grado.
- Si ya había datos capturados y se cambia de condición, avisar con un `toast`
  ("Se descartaron los datos de efectivo") en lugar de borrar en silencio.
- Ocultar, **no deshabilitar**: los campos que no aplican desaparecen del DOM.
- **Validar en el servidor**, no solo en la interfaz: rechazar grado, situación,
  categoría o CIP cuando la condición no es `EFECTIVO`, y rechazar vínculo o
  titular cuando no es `DERECHOHABIENTE`.
- Todos los desplegables de esta sección tienen 6 opciones o menos → `<select>`
  nativo, **sin buscador**.

---

## 7. Presentación en listados y fichas

Actualizar el método `Persona::descripcionPnp()`:

- Efectivo → `Cap. Chávez Ríos, Mario` + línea `Capitán de servicios · Actividad · CIP 12345678`
- Derechohabiente → `Chávez Ríos, Mario` + línea `Derechohabiente (hijo) de Cap. Pérez Sosa, Luis`
- Particular → solo el nombre, sin línea institucional

---

## Verificación

- [ ] `condicion` reemplaza a `es_pnp` y `tipo_beneficiario`, con datos migrados
- [ ] Unidad / dependencia no aparece en el formulario ni en catálogos ni en reportes
- [ ] La tabla `unidad_pnp` sigue existiendo en la base de datos
- [ ] Al elegir Derechohabiente no aparece ningún campo de grado ni CIP
- [ ] Al elegir un grado de cadete, alumno o civil, la categoría desaparece
- [ ] Ningún desplegable de esta sección usa buscador
- [ ] Ningún desplegable se sale de los límites de la tarjeta
- [ ] El servidor rechaza combinaciones imposibles
- [ ] `theme.css` sin modificaciones, solo clases nuevas al final
- [ ] Sin emojis ni librerías externas
