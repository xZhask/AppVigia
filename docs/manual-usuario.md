# VIGÍA · Manual breve de usuario

Sistema de vigilancia epidemiológica de Sanidad PNP / DIRSAPOL. Cubre lo
esencial para operar el sistema día a día; no reemplaza la capacitación del
protocolo de vigilancia de cada enfermedad.

## 1. Ingresar al sistema

Entra a la URL del sistema con tu correo y contraseña institucional. Si
olvidas tu contraseña, un Administrador puede restablecerla desde
**Catálogos › Usuarios**.

## 2. Roles

VIGÍA reconoce dos roles:

| Rol | Quién | Qué puede hacer |
|---|---|---|
| **Registrador/a** | Personal de cada establecimiento de salud PNP | Notificar fichas, dar seguimiento y editar **solo las de su propio establecimiento** mientras estén en estado Abierta. Ver el panel y los reportes. |
| **Administrador** | Área central de epidemiología DIRSAPOL | Todo lo anterior sin restricción de establecimiento, además de clasificar/cerrar/anular cualquier ficha, y gestionar catálogos (establecimientos, enfermedades, usuarios, grados y unidades PNP). |

Un Registrador que intenta abrir una pantalla de Administrador (por ejemplo,
Catálogos) recibe un aviso de acceso denegado, no un error.

## 3. Notificar una ficha nueva

1. **Nueva ficha** en el menú lateral.
2. Elige la enfermedad — el formulario carga automáticamente las secciones
   clínicas propias de esa enfermedad.
3. Completa notificación, datos del paciente (si es efectivo PNP, activa
   "Es PNP" para desplegar grado/unidad/situación) y el cuadro clínico.
4. Si el sistema detecta una ficha reciente (≈30 días) del mismo documento y
   enfermedad, muestra un aviso — puedes continuar si de verdad es un caso
   nuevo, no bloquea el registro.
5. Al guardar, la ficha queda en estado **Abierta** y clasificación
   **Sospechoso**; el número de ficha (p. ej. `F-00031`) se asigna solo.

## 4. Seguimiento de una ficha

Desde **Fichas**, busca por paciente, documento o número de ficha, o filtra
por enfermedad/clasificación/estado/fecha.

- **Ver** muestra el detalle completo, incluida la bitácora de cambios.
- **Editar** permite corregir datos mientras la ficha esté Abierta (y, si
  eres Registrador, solo si es de tu establecimiento).
- **Clasificar / cambiar estado** y **Cerrar** son acciones de Administrador
  (decisión de vigilancia, no del establecimiento que notifica).
- **Anular** se usa para un registro duplicado o por error — no borra la
  ficha, la marca como anulada con el motivo, y queda fuera de los
  indicadores del panel.

## 5. Importar varias fichas desde Excel

Para cargar varios casos a la vez (p. ej. un establecimiento que recién se
suma al sistema):

1. **Fichas › Importar Excel**.
2. Elige la enfermedad y descarga la plantilla — trae encabezados y una fila
   de ejemplo (esa fila de ejemplo se descarta siempre al importar).
3. Llena tus filas a partir de la fila 3, respetando los formatos indicados
   en la misma pantalla (fechas dd/mm/aaaa, DNI de 8 dígitos, UBIGEO de 6
   dígitos, valores exactos de cada catálogo).
4. Sube el archivo (`.xlsx` o `.csv`). El sistema valida fila por fila y
   muestra qué se importó y qué se rechazó (con el motivo exacto) — nunca
   importa un archivo a medias si no se pudo leer completo.
5. Cada carga queda registrada en **Fichas › Importar Excel › Lotes
   importados**, con los conteos de importadas y con error.

## 6. Panel y reportes

- **Panel** (pantalla de inicio): curva epidemiológica de la SE actual y el
  corredor endémico (o su respaldo de media móvil si aún no hay 2 años de
  historia — el sistema lo indica claramente cuando usa el respaldo),
  métricas de fichas abiertas/confirmadas y distribución por enfermedad y
  clasificación.
- **Reportes**: tabla agrupable por establecimiento, red, semana
  epidemiológica o clasificación, con exportación a Excel/CSV. Para un PDF,
  usa **Imprimir** del navegador — la hoja de estilos oculta automáticamente
  el menú y los filtros y deja solo el contenido del reporte.

## 7. Respaldo de la base de datos

Ver [`docs/respaldo-bd.md`](./respaldo-bd.md) — quien administre el
servidor debe registrar `scripts/respaldo_bd.ps1` en el Programador de
tareas de Windows; VIGÍA no lo hace por sí solo.
