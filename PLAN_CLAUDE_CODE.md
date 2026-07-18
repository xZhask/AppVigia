# VIGÍA · Sistema de Vigilancia Epidemiológica — DIRSAPOL

Plan de construcción para Claude Code. Ejecutar **una fase por sesión**, en orden.
No avanzar a la siguiente fase sin validar la anterior.

---

## Contexto del proyecto

Aplicativo web interno para el Área de Estadística de DIRSAPOL (Sanidad PNP) que
reemplaza el flujo actual de consolidación manual por Excel. Los establecimientos
(IPRESS PNP) registran fichas epidemiológicas de distintas enfermedades según los
modelos MINSA, y el área genera reportes consolidados de forma dinámica.

**No** reemplaza al aplicativo COVID existente: corre en paralelo.
**No** exporta a formatos externos MINSA (NOTI/NetLab) por ahora.

Stack: **PHP 8 (MVC sin framework) · MySQL/MariaDB · JavaScript vanilla · Laragon**

Archivos entregados junto a este plan:

| Archivo | Qué es |
|---|---|
| `vigia-mockup.html` | Mockup aprobado. **Fuente única de verdad visual.** |
| `theme.css` | CSS extraído del mockup. **Congelado.** |
| `01_esquema_vigia.sql` | Esquema completo de BD + datos semilla |
| `02_ubigeo_data.sql` | UBIGEO INEI: 25 dptos · 196 prov · 1874 distritos |

---

## ⛔ REGLAS DE DISEÑO — NO NEGOCIABLES

Estas reglas aplican a **todas** las fases. Violarlas invalida el trabajo de la fase.

### 1. El CSS está congelado
- Usar **`theme.css` tal cual**. No reescribirlo, no reordenarlo, no "optimizarlo".
- No cambiar ningún valor de las variables CSS (`--accent`, `--ink`, `--radius`, etc.).
- Se permite **únicamente agregar** clases nuevas al final del archivo, y solo si
  ninguna clase existente sirve. Nunca redefinir una clase existente.
- Prohibido instalar Bootstrap, Tailwind, Bulma o cualquier framework de UI.

### 2. Iconos: SVG inline, copiados del mockup
- **Prohibido usar emojis como iconos.** Ni en botones, ni en menús, ni en mensajes,
  ni en toasts, ni en estados vacíos. Ninguno.
- **Prohibido** Font Awesome, Bootstrap Icons, Material Icons, Lucide o cualquier
  librería de iconos.
- Todo icono se copia **literalmente** del `vigia-mockup.html`: son `<svg>` inline
  con `stroke="currentColor"`, `fill="none"` y `stroke-width` entre 1.2 y 1.6.
- Si se necesita un icono que no existe en el mockup: dibujarlo como SVG inline
  siguiendo exactamente ese mismo estilo (trazo lineal, sin relleno, 2 px de grid,
  esquinas redondeadas). **Nunca** rellenos sólidos ni iconos de otro estilo.
- Antes de crear uno nuevo, revisar si ya existe en el mockup. Casi siempre existe.

### 3. HTML: reutilizar el marcado, no reinventarlo
- Cada vista nueva parte del marcado del mockup. Copiar la estructura de clases
  (`.card`, `.section`, `.field`, `.control`, `.chip`, `.btn-primary`, `.toolbar`,
  `.table-card`, `.rail`, etc.) y reemplazar solo el contenido.
- Mantener el shell idéntico: sidebar, `.topbar`, `.crumb`, badge de semana
  epidemiológica, `.view` con `max-width:1180px`.
- Mantener la jerarquía tipográfica: `.page-title` en IBM Plex Serif, cuerpo en
  IBM Plex Sans, y **todo dato duro** (documentos, fechas, códigos, conteos, SE,
  CIE-10) con la clase `.mono`.

### 4. Colores con significado — no decorativos
- Clasificación de caso, siempre con estos colores y en este orden:
  `SOSPECHOSO` → `--s-sospechoso` · `PROBABLE` → `--s-probable` ·
  `CONFIRMADO` → `--s-confirmado` · `DESCARTADO` → `--s-descartado`.
- Estado de ficha: `ABIERTA` → `.st-open` · `VALIDACION` → `.st-val` ·
  `CERRADA` → `.st-closed`.
- No introducir colores nuevos fuera de las variables de `theme.css`.

### 5. Texto de interfaz
- Sentence case siempre. Nunca TODO MAYÚSCULAS ni Title Case.
- Verbos en acción directa: "Registrar ficha", no "Enviar" ni "Submit".
- Los errores dicen qué pasó y cómo resolverlo. No se disculpan, no son vagos.
- Estados vacíos: invitan a actuar, no solo informan que no hay datos.

### 6. Piso de calidad
- Responsive hasta móvil (los breakpoints ya están en `theme.css`).
- Foco de teclado visible (`:focus-visible` ya está definido).
- `prefers-reduced-motion` respetado (ya está definido).
- Sin `localStorage` ni `sessionStorage`.

### Al cerrar cada fase, verificar:
- [ ] ¿Se modificó algún valor existente de `theme.css`? → debe ser **no**
- [ ] ¿Hay algún emoji usado como icono? → debe ser **no**
- [ ] ¿Todos los iconos son SVG inline de trazo, como en el mockup? → **sí**
- [ ] ¿Los datos duros usan `.mono`? → **sí**
- [ ] ¿Las clasificaciones usan los colores asignados? → **sí**
- [ ] Abrir la vista junto al mockup: ¿parecen el mismo producto? → **sí**

---

## FASE 1 · Cimientos

**Objetivo:** proyecto corriendo con el shell visual del mockup y la BD cargada.

1. Estructura de carpetas MVC:
   ```
   /public          index.php (único punto de entrada), /css, /js, /img
   /app
     /Core          Router, Controller, Model, Database, View
     /Controllers
     /Models
     /Views
       /layouts     shell.php (sidebar + topbar + contenedor)
       /partials    componentes reutilizables
   /config          config.php (credenciales, ruta base)
   /sql             los dos .sql entregados
   ```
2. Copiar `theme.css` a `/public/css/theme.css` **sin modificar**.
3. Cargar `01_esquema_vigia.sql` y luego `02_ubigeo_data.sql`.
4. Router simple con URLs limpias (`/casos`, `/casos/nuevo`, `/reportes`).
5. Conexión PDO con prepared statements. Sin concatenar SQL nunca.
6. `shell.php`: extraer del mockup el sidebar completo y la topbar, con los
   iconos SVG tal cual. La navegación marca `.active` según la ruta actual.

**Entregable:** al abrir el proyecto se ve el shell idéntico al mockup, con las
rutas navegables aunque las vistas estén vacías.

---

## FASE 2 · Autenticación y catálogos

**Objetivo:** control de acceso y los maestros que alimentan todo lo demás.

1. Login con `password_verify()` sobre `usuario.password_hash` (bcrypt).
   Sesión PHP, protección CSRF en todos los formularios, logout.
2. Roles: `ADMIN`, `EPIDEMIOLOGO`, `REGISTRADOR`, `LECTOR`.
   El `REGISTRADOR` solo ve y registra fichas de **su** establecimiento.
3. Middleware que exige sesión activa en todas las rutas salvo el login.
4. CRUD de catálogos: establecimientos, redes, grados PNP, unidades PNP,
   enfermedades, usuarios. Usar el patrón `.table-card` + `.toolbar` del mockup.
5. Endpoints JSON encadenados de UBIGEO:
   `/api/provincias?departamento=XX` y `/api/distritos?provincia=XXXX`.

**Entregable:** login funcional, catálogos administrables, selectores de UBIGEO
encadenados operativos.

---

## FASE 3 · Motor de fichas

**Objetivo:** el formulario que se arma solo, leyendo los metadatos de la BD.

1. Renderizador que, dada una `enfermedad_id`, lee `seccion_def` y `campo_def`
   y construye el formulario. Cada `tipo` de campo tiene su plantilla parcial:
   `TEXTO`, `NUMERO`, `FECHA`, `BOOLEANO`, `SELECT`, `MULTISELECT`, `TEXTAREA`.
   Los `SELECT` se llenan desde `catalogo_item`.
2. Cada campo se pinta con el marcado exacto del mockup: `.field` + `label.fl`
   + `.control`, incluidos los estados `.ok` y `.err` y el texto `.hint`.
3. Validación **en servidor** (la del navegador es solo ayuda visual):
   obligatorios, formato de fecha, rangos numéricos, valores válidos de catálogo.
4. Al guardar: cabecera en `caso`, campos dinámicos en `caso_valor`
   (un registro por campo respondido), todo dentro de una transacción.
5. Cálculo automático de `semana_epi` y `anio_epi` desde `fecha_notif`.
6. Panel derecho `.rail` con el avance por secciones, tal como el mockup.
7. Selector de enfermedad que recarga la sección clínica sin perder lo capturado.

**Entregable:** registrar una ficha de dengue completa (la definición ya viene
sembrada en el SQL) y verla guardada en `caso` + `caso_valor`.

---

## FASE 4 · Gestión de casos

**Objetivo:** el ciclo de vida completo de la ficha y la calidad del dato.

1. Listado de fichas con el diseño `.table-card` del mockup: búsqueda,
   filtros por enfermedad / clasificación / estado / rango de fechas, paginación
   en servidor. Chips de clasificación y estado con sus colores asignados.
2. Buscador de paciente por documento con autocompletado desde `paciente`
   (marcado `.found` del mockup). Si es efectivo PNP, mostrar y capturar
   grado, situación (actividad/retiro/disponibilidad), CIP y unidad.
3. **Detección de duplicados**: al registrar, verificar si ya existe un caso de
   la misma enfermedad, para el mismo documento, dentro de una ventana de
   ~30 días. Mostrar el aviso `.dupe` del mockup con enlace a la ficha existente.
   Es advertencia, no bloqueo: el usuario decide.
4. Ver / editar / anular ficha (anular es lógico, nunca borrado físico).
5. Transiciones de estado: `ABIERTA` → `VALIDACION` → `CERRADA`, con permisos
   por rol (solo `EPIDEMIOLOGO` y `ADMIN` cierran).
6. Registrar en `caso_bitacora` toda creación, edición, cambio de clasificación
   y cierre.
7. Tablas hijas: contactos, muestras de laboratorio, viajes y antecedentes
   vacunales, con filas que se agregan y quitan dinámicamente.

**Entregable:** flujo completo desde el registro hasta el cierre, con duplicados
detectados y trazabilidad en bitácora.

---

## FASE 5 · Panel y reportes

**Objetivo:** la razón de ser del sistema — consolidar sin re-digitar.

1. Panel: las cuatro tarjetas `.metric`, la distribución por enfermedad
   (`.brk`) y por clasificación (`.class-dist`), con datos reales.
2. **Curva epidemiológica**: reproducir el SVG del mockup generándolo desde PHP
   con los casos por semana epidemiológica. Conservar el corredor endémico
   (banda punteada) y pintar en `--s-confirmado` las semanas que lo superan.
   No sustituir por Chart.js ni ninguna otra librería: es el elemento firma.
3. Reportes con agrupación configurable: por establecimiento, red, semana
   epidemiológica o clasificación, con filtro de enfermedad y rango de SE.
   Usar el layout `.report-out` del mockup (tabla + panel lateral).
4. Exportación a Excel y PDF de la tabla resultante.
5. Consultas agregadas eficientes: `JOIN` sobre `caso` usando los índices
   `ix_caso_se`, `ix_caso_clasif`, `ix_caso_est`. Nunca agregar en PHP lo que
   puede resolver SQL.

**Entregable:** panel con datos reales y reportes exportables.

---

## FASE 6 · Importación desde Excel

**Objetivo:** puente de adopción para los establecimientos que aún no digitan
caso por caso.

1. Descarga de plantilla `.xlsx` generada según los campos de la enfermedad
   seleccionada.
2. Carga del archivo y validación fila por fila **antes** de insertar nada:
   documento, fechas, catálogos, obligatorios, y duplicados contra la BD.
3. Pantalla de resultados: filas válidas frente a filas con error, indicando
   fila, columna y motivo. Solo se importa lo válido; lo demás se corrige y
   se vuelve a subir.
4. Toda la importación en una transacción, registrando el lote en bitácora.

**Entregable:** un Excel con errores intencionales se rechaza fila por fila
con mensajes claros, y las filas correctas quedan importadas.

---

## FASE 7 · Cierre

1. Cargar el padrón real de establecimientos (RENIPRESS) y unidades PNP.
2. Definir en `seccion_def` / `campo_def` las fichas restantes del Grupo A
   (tos ferina, sarampión/rubéola, leishmaniasis, difteria, fiebre amarilla).
3. Revisión de seguridad: SQL inyección, XSS en toda salida (`htmlspecialchars`),
   CSRF, control de acceso por rol en cada ruta.
4. Índices y pruebas de rendimiento con volumen realista.
5. Manual breve de usuario y respaldo programado de la BD.

---

## Notas para todas las fases

- Código y comentarios en español; nombres de tablas y columnas tal como están
  en el SQL entregado.
- Nunca confiar en la validación del navegador: revalidar siempre en servidor.
- Escapar toda salida hacia HTML.
- Errores en pantalla con lenguaje claro; el detalle técnico va al log, no al
  usuario.
- Al terminar cada fase, abrir la vista junto al mockup y comparar. Si algo se
  ve distinto, se corrige antes de avanzar.
