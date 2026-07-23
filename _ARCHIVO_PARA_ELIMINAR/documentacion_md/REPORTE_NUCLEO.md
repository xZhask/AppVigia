# REPORTE_NUCLEO.md

Verificación pedida por `ANTES_DE_COMPARAR_FICHAS.md`, sección 1: si los
campos núcleo de `AUDITORIA_FICHA_DIFTERIA.md` (puntos 2, 7 y 8) —
comunes a casi todas las fichas MINSA, por eso viven en `persona`/`caso` y
no en `manifiesto_fichas.json`/`campo_def` — están implementados y
aparecen en el formulario de las 24 fichas, no solo en algunas.

**Resultado: los 12 campos ya estaban implementados de punta a punta**
(schema + formulario de alta + formulario de edición + guardado con
validación + vista de solo lectura), de una sesión anterior a esta
(`fase 7 completada_v1`, según el historial de git). No hizo falta
implementar nada — esta es una verificación que confirma que el trabajo
anterior sigue vigente después de toda la recarga de fichas.

---

## 1.1 Sección "Notificación"

Parcial: `app/Views/partials/notificacion-captacion.php`, incluido sin
condición en `nueva/index.php` (línea 92) y `fichas/editar.php` (línea 66)
— dentro de la tarjeta #1, que se dibuja siempre, para cualquier ficha.

| Campo | Tipo | Tabla.columna | Estado |
|---|---|---|---|
| Tipo de captación | SELECT: Activa · Pasiva | `caso.tipo_captacion` ENUM('ACTIVA','PASIVA') | ✅ existe, tipo correcto |
| Lugar de captación | SELECT: Institucional · Comunidad | `caso.lugar_captacion` ENUM('INSTITUCIONAL','COMUNIDAD') | ✅ existe, tipo correcto |
| Clasificación en la captación | SELECT: Confirmado · Probable · Sospechoso | `caso.clasificacion_captacion` ENUM('CONFIRMADO','PROBABLE','SOSPECHOSO') | ✅ existe, tipo correcto |

Confirmado que **coexiste** con `caso.clasificacion` (la final): son columnas
distintas, se validan y guardan por separado
(`CasosController::crear()`/`actualizar()`, líneas ~156-158 y ~615-617).

## 1.2 Sección "Datos del paciente"

Parcial: `app/Views/partials/datos-paciente-nucleo.php`, incluido sin
condición en ambos formularios (dentro de la tarjeta "Datos del persona").

| Campo | Tipo | Destino | Estado |
|---|---|---|---|
| N.° de celular | TEXTO | `persona.celular` varchar(20) | ✅ existe |
| Nacionalidad | SELECT (por defecto Peruana) | `persona.nacionalidad` varchar(60) DEFAULT 'Peruana' | ✅ existe. Nota: en el formulario es un `<input type="text">` con el valor precargado en "Peruana" para una ficha nueva, no un `<select>` cerrado — el PDF no trae una lista de nacionalidades, así que un texto con default es más fiel que inventar un catálogo cerrado de países. Si se prefiere `<select>`, es un cambio de UI, no de dato. |
| Domicilio actual (dirección) | TEXTO | `persona.direccion` varchar(160) | ✅ existe |
| Localidad | TEXTO | `persona.localidad` varchar(120) | ✅ existe |
| Etnia / raza | SELECT: Mestizo · Andino · Asiático descendiente · Afrodescendiente · Indígena amazónico · Otro | `persona.etnia` ENUM | ✅ existe, 6 opciones exactas |
| Gestante | BOOLEANO | `persona.gestante` tinyint(1) | ✅ existe |
| Semanas de gestación | NUMERO, condicional a Gestante = Sí | `persona.semanas_gestacion` smallint(6) | ✅ existe |

**Condiciones de visibilidad** — verificadas en `public/js/ficha.js`
("Núcleo: gestante solo si sexo=F, semanas solo si gestante=Sí") y
revalidadas en servidor (`CasosController`, líneas ~1076-1079: el campo
`gestante` solo se acepta si `sexo === 'F'`, y `semanas_gestacion` solo si
`gestante === 1`) — no se confía únicamente en el JS del navegador.

**Etnia como dato sensible** — verificado en 3 lugares:
- `datos-paciente-nucleo.php`: el `<select>` de etnia solo se renderiza
  `if (Auth::tieneRol('ADMIN'))` — un REGISTRADOR nunca lo ve en el
  formulario, ni para leer ni para escribir.
- `fichas/ver.php`: el valor de etnia en la vista de solo lectura también
  está detrás de `if (Auth::tieneRol('ADMIN'))`.
- `Caso::listarPaginado()` (el listado de fichas, `app/Models/Caso.php`):
  la consulta **no selecciona `persona.etnia`** en absoluto — no es que se
  oculte en la plantilla, directamente no viaja en la fila. No existe
  ningún export/CSV en el código que la incluya tampoco (se buscó en todo
  `app/`).

## 1.3 Sección "Investigador"

Parcial: `app/Views/partials/investigador.php`, incluido sin condición al
cierre de ambos formularios (línea 232 en `nueva/index.php`, línea 185 en
`fichas/editar.php`).

| Campo | Tipo | Destino | Estado |
|---|---|---|---|
| Persona que llena la ficha | TEXTO, autocompletable y editable | `caso.investigador_nombre` varchar(160) | ✅ existe. Se precarga con `Auth::usuario()['nombre']` al crear una ficha nueva (`valoresFijosPorDefecto()`), pero el `<input>` no tiene `readonly` — se puede editar. |
| Cargo | TEXTO | `caso.investigador_cargo` varchar(100) | ✅ existe |
| Fecha de investigación | FECHA, autocompletable | `caso.fecha_investigacion` date | ✅ existe. Se precarga con la fecha de hoy, editable. |

Firma y sello: no aplican en digital, como aclara el propio documento — no
hay nada que implementar ahí.

---

## Conclusión

Los 12 campos núcleo existen, con el tipo correcto, las condiciones de
visibilidad correctas (client-side y revalidadas server-side), y se
renderizan sin condición para las 24 fichas (no dependen de ninguna
bandera por enfermedad). Etnia está correctamente excluida de listados y
no existe ningún mecanismo de exportación que la incluya. No se requirió
ningún cambio de código para esta sección — se documenta el estado
verificado, como pide el entregable.
