# Hoja de ruta — estado al 2026-10-15

Punto de partida para retomar desde cualquier estación. `PENDIENTES.md` es la
bitácora histórica (más de 7.000 líneas); esto es lo que sigue vigente.

## 1. Sincronizar esta estación (después de `git pull`)

En este orden:

```
php sql/migraciones/add_tablas_hijas_enfermedad.php
php cargar_fichas.php --apply --confirmo-apply
```

- La migración agrega `enfermedad.tablas_hijas`. Sin ella, el cargador falla al
  escribir esa columna.
- El cargador va **sin** `--cie10`: el 15/10 cambió `tablas_hijas` en las 24
  fichas. Si una ficha sale "bloqueada por datos capturados", igual recibe sus
  tablas hijas (el cargador las aplica aparte); el resto de esa ficha queda
  como estaba.
- Comprobar después: `php verificar_fichas.php`, `php verificar_claves.php` y
  `php verificar_render.php` (los tres en verde, sin huérfanos).

## 2. Qué quedó hecho (13–15 de octubre)

- **Auditoría ficha por ficha contra el PDF: completa.** Las 24 fichas están
  cotejadas. Las últimas: A35 (datos del paciente, investigador, ítem III.2),
  A37.0 (X/XI, ítem 53, regla de dosis documentadas en HIS, clasificación
  calculada) y B55 (tiempo de permanencia con unidad, dibujos ORL, rostros,
  silueta según el sexo y el mapa de lesiones en "ver").
- **Reglas de dosis** en A35, A33 y A37.0: la fecha de última dosis solo con
  alguna dosis marcada; dosis secuenciales.
- **Seguridad de datos en el servidor:**
  - Solo se guardan las tablas hijas que la ficha declara (`tablas_hijas`).
  - En muestras, solo las columnas declaradas.
  - Una persona existente ya no pierde los datos que pidió otra ficha (antes
    registrar un caso de A36 borraba el estado civil guardado por B57).
  - Lo que llega forjado para un dato que la ficha no pide se descarta.
- **O95:** el Anexo 2 son secciones condicionadas ("ver" y la validación ya
  respetan el anexo); el distrito de fallecimiento y de referencia se valida
  contra el padrón.
- **B01:** un registro por tipo de muestra. Revisadas las demás candidatas:
  repiten tipo a propósito y no lo llevan.
- **Motor nuevo:** `indicacion` de campo (nota del papel antes del campo),
  `alguno_mayor_que` con casillas, reglas `mostrar` aplicadas hasta un punto
  fijo.

## 3. Pendiente — decisiones del usuario

No tocar sin una respuesta explícita.

1. **Clasificación duplicada en 11 fichas.** Muestran el chip genérico
   "Clasificación del caso" y además su propio campo: A97, A95, B57, B04X, A00,
   A35, A33, A80, B05, P35.0, O95. En A37.0 se resolvió ocultando el chip y
   calculando `caso.clasificacion` desde el campo propio (reglas `clasificar`).
   Antes, el usuario había rechazado ocultar el chip en general (`PENDIENTES.md`
   ítem 11); el 14/10 lo aprobó para A37.0. Decidir si se aplica a las 11 o
   ficha por ficha. Ojo: A97, A80, B57, Y59.0 y A00 tienen vocabularios propios
   que no caben en las 4 clasificaciones genéricas.
2. **Fechas que podrían derivarse del campo propio de cada ficha.**
   - `caso.fecha_inicio_sintomas` queda vacía en 10 fichas que la piden con
     otro nombre (P35.0, A35, A37.0, B01, A97, B57, A95, B55, B04X, A00). Los
     reportes por fecha de síntomas no las ven.
   - `fecha_notif` se pide aparte aunque la ficha tenga su cadena de fechas
     de notificación (`PENDIENTES.md` ítem 5).
   - Propuesta posible: declarar por ficha qué campo es la fecha canónica.
3. **Códigos de zona del PDF** (urbanización, AA.HH., cooperativa…): segundo eje
   del domicilio, distinto de Urbano/Rural. Afecta a A00, A95, B57, A37.0 y
   P35.0. Requiere columna nueva en `persona`.
4. **Z21:** si Aborto y Mortinato tienen ficha propia; hoy `caso.clasificacion`
   de Z21 queda en SOSPECHOSO por defecto.

## 4. Pendiente — técnico, sin decisión de por medio

- **O95, datos del Anexo 2 en la tarjeta de identidad** (grupo étnico, idioma,
  nivel educativo, estado civil, ocupación, seguro): se ocultan con Anexo 1,
  pero no están en las secciones condicionadas; probablemente se guardan si
  llegan en el POST. Verificar y, si es así, condicionarlos.
- **O95:** departamento y provincia del ubigeo de fallecimiento y referencia no
  se validan (el distrito sí).
- **"Ver":**
  - No aplica `campos_notificacion` (Z21 muestra "Tipo de registro" en una
    tarjeta aparte).
  - Por verificar si `b26_contactos_por_lugar` se lee bien o sale como JSON.
- **Reportes:** el orden y las columnas por clasificación solo conocen las 4
  genéricas; el filtro del listado de fichas también.
- **Padrón RENIPRESS:** revisar categorías dudosas (Centro de Salud Mental
  Comunitario PNP figura I-2). A50 "Nivel del establecimiento" y O95 "Categoría
  del EE.SS." podrían tomarse del padrón.
- **Menores, de interfaz:**
  - Hueco vertical en datos de la persona cuando una rama oculta Etnia y
    Residencia.
  - Falta estilo para `input[readonly]`.
  - En P96 la edad muestra "0 años".
  - Una persona sin documento no puede recibir uno después.
  - Falta un catálogo CIE-10 para el diagnóstico de P96.

## 5. Cómo se ha trabajado (para mantener el criterio)

- Variaciones por ficha: como dato en `manifiesto_fichas.json`, no con
  `if ($cie10 === ...)` en código compartido.
- Antes de commitear, avisar si algo cambia en las fichas revisadas (A36, B26,
  A80, O95, B05, P35.0, B01, A95).
- Verificar en el navegador real (Playwright) con crear → ver → editar, una
  prueba negativa por POST con control positivo, y borrar los casos de prueba.
- Los casos de la base son de prueba.
- Push solo cuando el usuario lo pide.
