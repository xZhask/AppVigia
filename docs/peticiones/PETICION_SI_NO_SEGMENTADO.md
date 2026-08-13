# Petición — Unificar preguntas Sí/No al estilo de 3 botones (P35.0)

Plan para decidir y ejecutar más adelante, sin bloquear el trabajo de hoy en A97. Nace de comparar el `<select>` "Seleccionar…/Sí/No" que usan los `BOOLEANO` de A97 contra el control segmentado "Sí/No/Desc." que ya se ve en "Antecedentes de la madre" de P35.0.

---

## Hallazgo clave: no es un componente exclusivo de P35.0

El control de 3 botones **no depende de un `campo_def.tipo` dedicado**. Es el mismo `tipo: "SELECT"` de siempre — `app/Views/partials/campos/select.php:7` detecta automáticamente cuando un SELECT trae exactamente 3 opciones cuyo catálogo resuelve a `SI` (1.ª) y `NO` (2.ª), y en ese caso dibuja el `<div class="seg">` de 3 botones en vez del `<select>` normal.

- Ya lo usan **42 campos `SELECT`** en el manifiesto (no solo los 3 de P35.0) con el patrón `["Sí","No","Desconocido"/"No recuerda"]`.
- El mismo look visual (`.seg-label`, `.grupo-si-no-row`) también lo usan los tipos `GRUPO_SI_NO` (17 campos) y `SI_NO_FECHA` (33 campos).
- El CSS ya está centralizado en `public/css/campos-dinamicos.css:1-58` y se carga siempre desde `shell.php` — **cero trabajo de diseño** para reutilizarlo en cualquier ficha nueva.

Conclusión: para un **campo nuevo**, basta con declararlo como `SELECT` con `"opciones": ["Sí","No","Desconocido"]` en el manifiesto. Ya lo hicimos así, sin saberlo, en varias fichas.

## Por qué NO es trivial para los `BOOLEANO` que ya existen

`BOOLEANO` y `SELECT` no son intercambiables solo visualmente — son distintos en almacenamiento:

| | `BOOLEANO` | `SELECT` (3 opciones Sí/No/…) |
|---|---|---|
| Guardado en `caso_valor.valor` | `'1'` / `'0'` (checkbox, `isset()`) | código de catálogo: `'SI'` / `'NO'` / `'DESCONOCIDO'` |
| ¿Tiene `catalogo_id`? | No (`cargar_fichas.php:105`, `TIPOS_CON_OPCIONES` no lo incluye) | Sí |
| Estados posibles | 2 (sin "no sé") | 3+ nativos |
| `valor_activador` típico de un hijo | `"1"` | `"SI"` |

Convertir un `BOOLEANO` existente a este estilo requiere, por campo:

1. **Manifiesto**: `"tipo": "BOOLEANO"` → `"tipo": "SELECT"` + `"opciones": ["Sí","No","Desconocido"]`.
2. **Migrar `caso_valor`** de los casos ya capturados con ese campo: `'1'`→`'SI'`, `'0'`→`'NO'` (un `UPDATE` puntual — `cargar_fichas.php` no lo hace solo, y además bloquea la recarga de la ficha si tiene `caso_valor` sin `--confirmar-perdida`).
3. **Actualizar `valor_activador`** de cualquier campo hijo que dependa de ese booleano: pasa de `"1"` a `"SI"` (y de `"0"` a `"NO"` si algo dependiera del "No" — el motor de dependencias, `app/Core/ayudantes.php:207-226`, sigue funcionando igual, solo cambia el string a comparar).
4. **Revisar comparaciones a medida** en `secciones-clinicas.php`/partials propios que hoy chequean literal `'1'`/`'0'` para ese campo (ej. los wraps de viajes condicionados en B05/P35.0/A37.0).

## Candidatos en A97 (agregados/tocados en esta sesión)

- `Caso autóctono` — hoy `BOOLEANO`, ya es "padre" de `Caso importado nacional/internacional` (con `valor_activador: "0"`).
- `¿Tuvo dengue anteriormente?` — `BOOLEANO`, padre de `Año (dengue anterior)`.
- `Recibió vacuna antiamarílica` — `BOOLEANO`, padre de `Año de vacunación`.
- `¿Tiene comorbilidad?` — `BOOLEANO`, padre de `Comorbilidad (cuál)`.

Como A97 todavía no tiene `caso_valor` reales (datos de prueba, limpiados esta sesión), el paso 2 (migración de datos) es de riesgo prácticamente nulo **por ahora** — conviene decidir esto antes de que existan casos reales, porque después la migración de `caso_valor` sí pesa.

## Decisión pendiente (para retomar)

1. ¿Convertir los 4 campos de A97 de una vez, o dejarlos como `<select>` y aplicar el estilo segmentado solo a fichas nuevas de aquí en adelante?
2. Si se convierten: ¿el 3.er valor es siempre "Desconocido", o alguna pregunta necesita otra etiqueta (ej. "No recuerda")?
3. ¿Vale la pena, en la misma pasada, revisar qué otras fichas ya "cotejadas" (A36, B26, A80, O95, B05) tienen `BOOLEANO` que el PDF real pide como Sí/No/Desconocido y hoy se ven como `<select>` plano? (fuera del alcance de A97, pero mismo patrón).

No ejecutar sin confirmar el punto 1 con el usuario.
