# VIGÍA · Antes de la comparación ficha por ficha

Dos verificaciones previas. El objetivo es que, al comparar las 24 fichas contra
el PDF, el usuario encuentre solo diferencias de contenido — no el mismo defecto
estructural repetido 24 veces ni bugs de renderizado mezclados con hallazgos.

Siguen vigentes las reglas de diseño de `PLAN_CLAUDE_CODE.md`.

> Comitear primero lo aplicado en `PENDIENTES_POST_FASE5.md`.

---

## 1. Verificar las secciones núcleo

De `AUDITORIA_FICHA_DIFTERIA.md` (puntos 2, 7 y 8) salieron campos que **no son
de difteria sino de casi todas las fichas MINSA**. Por eso viven en el núcleo y
no en `manifiesto_fichas.json` — y por eso `verificar_fichas.php` no los ve.

Si alguno no se implementó, aparecerá como hallazgo en las 24 fichas.
Verificar ahora, una sola vez.

### 1.1 Sección "Notificación"

| Campo | Tipo | Estado |
|---|---|---|
| Tipo de captación (vigilancia) | SELECT: Activa · Pasiva | ¿existe? |
| Lugar de captación | SELECT: Institucional · Comunidad | ¿existe? |
| Clasificación en la captación | SELECT: Confirmado · Probable · Sospechoso | ¿existe? |

La **clasificación en la captación** es distinta de la clasificación final
(`caso.clasificacion`): se registra al captar el caso, antes del laboratorio.
Ambas deben coexistir.

### 1.2 Sección "Datos del paciente"

| Campo | Tipo | Destino | Estado |
|---|---|---|---|
| N.° de celular | TEXTO | `persona` | ¿existe? |
| Nacionalidad | SELECT (por defecto Peruana) | `persona` | ¿existe? |
| Domicilio actual (dirección) | TEXTO | `persona` | ¿existe? |
| Localidad | TEXTO | `persona` | ¿existe? |
| Etnia / raza | SELECT: Mestizo · Andino · Asiático descendiente · Afrodescendiente · Indígena amazónico · Otro | `persona` | ¿existe? |
| Gestante | BOOLEANO | `caso` o `persona` | ¿existe? |
| Semanas de gestación | NUMERO, condicional a Gestante = Sí | | ¿existe? |

Condiciones de visibilidad:
- **Gestante** se muestra solo si el sexo es femenino.
- **Semanas de gestación** solo si gestante = Sí.
- **Etnia** es dato sensible: debe estar marcada como tal y quedar fuera de
  listados, reportes agregados y exportaciones.

### 1.3 Sección "Investigador"

Aparece en casi todas las fichas MINSA y cierra el formulario:

- Persona que llena la ficha (autocompletable con el usuario en sesión, editable
  — a veces quien digita no es quien investigó)
- Cargo
- Fecha de investigación

*(La firma y el sello del formato en papel no aplican en digital.)*

### 1.4 Entregable

Un reporte breve (`REPORTE_NUCLEO.md`) que liste, campo por campo: si existe, en
qué tabla, y si tiene el tipo y las condiciones correctas.

**Implementar lo que falte**, y verificar que estos campos aparezcan en el
formulario de **todas** las fichas, no solo en algunas.

---

## 2. Prueba de humo real en navegador

Las dos últimas corridas informaron "probado a nivel de plantilla PHP, sin
navegador real". Es una limitación declarada con honestidad, pero significa que
**ninguna ficha se ha abierto en pantalla desde la recarga completa**.

Ya ocurrió dos veces que una definición correcta en la base se renderizara mal:
etiquetas concatenadas dentro del control en `GRUPO_SI_NO`, y desplegables que
se salían de la tarjeta.

**Si no hay navegador disponible en el entorno**, dejarlo explícito y preparar en
cambio una guía corta (`GUIA_PRUEBA_HUMO.md`) para que la ejecute el usuario:
qué abrir, en qué orden y qué mirar en cada caso.

### Fichas a probar y por qué

| Ficha | Qué ejercita |
|---|---|
| **Sarampión (B05)** | `CRONOLOGIA` día −10/+10 · columnas nuevas de contactos |
| **PFA (A80)** | varias `MATRIZ` (fuerza muscular, tono, reflejos por segmento) |
| **Muerte materna (O95)** | 83 campos · multi-sujeto · las cuatro demoras |
| **ESAVI (Y59.0)** | sección condicional vacía (Anexo 6.2) · `caso_vacuna` con catálogo |
| **Tétanos (A35)** | ficha simple, como control |
| **Difteria (A36)** | referencia ya validada · censo de contactos con sus columnas |

### Qué verificar en cada una

- Todas las secciones aparecen, numeradas correlativamente y sin repetirse
- Ningún desplegable está vacío
- Ningún desplegable se sale de los límites de la tarjeta
- Ninguna etiqueta aparece concatenada dentro de un control
- Los campos condicionales se ocultan y muestran según su disparador
- Los `GRUPO_SI_NO` se ven como matriz de opciones, no como lista de desplegables
- Las tablas hijas muestran **solo** las columnas configuradas para esa ficha
  (en difteria no debe aparecer "fecha de inicio de erupción")
- Se puede **guardar y reabrir** sin perder datos
- Los campos sensibles no aparecen en el listado de fichas ni en la exportación

Cualquier defecto visual se corrige **antes** de que empiece la comparación de
contenido, para no mezclar bugs de interfaz con diferencias respecto del PDF.

---

## Verificación

- [ ] `PENDIENTES_POST_FASE5.md` comiteado
- [ ] `REPORTE_NUCLEO.md` entregado, con el estado de cada campo núcleo
- [ ] Lo que faltaba del núcleo quedó implementado y visible en todas las fichas
- [ ] Etnia marcada como sensible y fuera de listados y exportaciones
- [ ] Gestante y semanas de gestación con sus condiciones de visibilidad
- [ ] Existe la sección Investigador
- [ ] Las 6 fichas se abren, guardan y reabren sin defectos visuales — o queda
      la guía para que el usuario lo verifique
- [ ] `theme.css` sin cambios; sin emojis ni librerías externas
