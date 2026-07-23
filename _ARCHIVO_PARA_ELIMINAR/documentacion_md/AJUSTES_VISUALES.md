# VIGÍA · Ajustes visuales y corrección de codificación

Siguen vigentes las reglas de diseño de `PLAN_CLAUDE_CODE.md`: no modificar
`theme.css`, sin emojis, sin librerías de iconos ni de UI, iconos SVG inline
copiados del mockup.

---

## 1. BUG CRÍTICO — Codificación de caracteres (mojibake)

**Síntoma:** "POSTA M**ë**DICA POLICIAL VIPOL" en lugar de "MÉDICA".
Igual en el nombre de usuario de la barra lateral ("Ch|ávez").
Es UTF-8 interpretado como Latin-1.

**Corregir toda la cadena, no solo la vista:**
- DSN de PDO: `mysql:host=...;dbname=vigia;charset=utf8mb4`
- Tras conectar: `SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci`
- Cabecera HTTP: `header('Content-Type: text/html; charset=UTF-8')`
- `<meta charset="UTF-8">` como primer elemento del `<head>`
- Los archivos `.php` guardados en **UTF-8 sin BOM**
- `htmlspecialchars($v, ENT_QUOTES, 'UTF-8')` — indicar siempre el charset
- Verificar que los datos ya guardados no estén corruptos en BD; si lo están,
  corregirlos, porque no es un problema de visualización sino de contenido

**Prueba de aceptación:** deben verse correctamente Áncash, Huánuco, Apurímac,
Cañete, Chávez y "POSTA MÉDICA" — en pantalla, en reportes y en exportaciones.

---

## 2. Nombre del establecimiento: quitar `.mono` y las mayúsculas

Hoy se muestra en monoespaciada y en mayúsculas, ocupa dos líneas y desalinea
toda la primera fila del bloque "Notificación".

- Quitar la clase `.mono`: la monoespaciada se reserva para datos duros
  (documentos, fechas, códigos, SE, conteos). Un nombre propio no lo es.
- Mostrarlo en capitalización normal ("Posta Médica Policial VIPOL"), no en
  mayúsculas sostenidas. Si en BD está en mayúsculas, normalizar al mostrar.
- Si aun así no entra, truncar con elipsis y `title` con el nombre completo.
  Nunca dejar que un campo rompa la altura de su fila.

**Regla general:** todos los campos de una misma fila deben tener la misma
altura. Revisar que ningún `.control` con texto largo la altere.

---

## 3. Texto de ayuda huérfano

Bajo "Fecha de notificación" dice "Se recalcula al guardar, según la fecha de
notificación", pero la semana epidemiológica ya se movió al encabezado de la
sección. El texto quedó sin referente y resulta confuso.

- Eliminar ese `.hint` de "Fecha de notificación".
- Mover la aclaración al badge de SE del encabezado, como atributo `title`
  ("Se recalcula al guardar, según la fecha de notificación").

---

## 4. Semana epidemiológica duplicada en pantalla

Aparece dos veces a la vez: en la topbar ("Semana SE 29 · 2026") y en el
encabezado de la sección Notificación ("SE 29 · 2026").

- La topbar muestra la **semana actual del sistema** — mantener.
- El badge de la sección muestra la **SE calculada de esta ficha** — mantener,
  pero diferenciarlo: anteponer una etiqueta corta en `.eyebrow` que diga
  "SE de la ficha", para que no se lea como repetición.

---

## 5. Selectores: unificar la apariencia

Conviven dos controles con aspecto distinto en la misma fila: el desplegable
con búsqueda (Departamento, Grado) tiene la flecha más pequeña y distinto
padding que el `<select>` nativo (Provincia, Situación). Se nota y desordena.

**Resuelto por otra vía:** en vez de igualar el aspecto de dos componentes
distintos, se eliminó el umbral de "más de 5 opciones" y el componente con
búsqueda (`selector-busqueda.js`) ahora envuelve **todos** los `<select>` de
la app, sin excepción. Ya no hay un `<select>` nativo con el que desentonar:
mismo padding, mismo tamaño de fuente, mismo icono de flecha (con rotación al
abrir), mismo scroll y mismo resaltado en toda la app, tengan 2 opciones o 200.

---

## 6. Nombre de enfermedad truncado

Se corta a media palabra: "Dengue, chikungunya, zika y arbovi".

- Permitir dos líneas o truncar con elipsis y `title` completo.
- No cortar a mitad de palabra.
- Considerar un nombre corto para la interfaz ("Dengue y arbovirosis") y el
  nombre completo solo en el catálogo. Se puede agregar una columna
  `nombre_corto` en `enfermedad`.

---

## 7. Bloque de usuario en la barra lateral

Muestra "Mario Chávez, Registrador" y debajo "Registrador/a": el rol aparece
dos veces y el nombre se parte en dos líneas.

- Línea 1: solo nombre y apellido ("Mario Chávez").
- Línea 2: solo el rol ("Registrador").
- Si el nombre es largo, truncar con elipsis y `title` completo.

---

## 8. Panel de avance: reflejar el estado real

Las secciones ya completadas (Notificación, Paciente) se ven igual que las
pendientes. El mockup define tres estados y hay que usarlos:

- Completada → `.pstep.done` con el check SVG dentro del círculo relleno
- Actual → `.pstep.cur`, círculo con borde de acento
- Pendiente → `.pstep` normal

Marcar una sección como completada cuando todos sus campos obligatorios estén
llenos. Debe actualizarse en vivo mientras se digita, no solo al guardar.

---

## 9. Aprovechar el espacio del panel derecho

Bajo los botones queda una franja vacía grande. Agregar ahí una tarjeta
`.rail-card` discreta con el resumen de la ficha en curso: enfermedad
seleccionada, establecimiento y tipo de notificación (inmediata / semanal).
Sin colores nuevos ni elementos decorativos.

---

## Verificación

- [ ] Áncash, Huánuco, Apurímac y "POSTA MÉDICA" se ven correctamente
- [ ] Ningún nombre propio usa monoespaciada
- [ ] Todos los campos de una fila tienen la misma altura
- [ ] Los desplegables con y sin búsqueda se ven idénticos
- [ ] El panel de avance marca las secciones completadas
- [ ] `theme.css` sin modificaciones; sin emojis ni librerías externas
