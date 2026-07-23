# VIGÍA · Auditoría ficha por ficha — Difteria

Comparación del formulario generado contra la ficha MINSA oficial. Los hallazgos
son de tres tipos: **contenido inventado**, **campos faltantes** y **tipos mal
asignados**. Varios afectan a todas las fichas, no solo a difteria.

Siguen vigentes las reglas de diseño de `PLAN_CLAUDE_CODE.md`.

---

## 1. CRÍTICO — Campos inventados

La sección **6 "Signos de alarma"** del formulario contiene:
- Edema cervical ("cuello de toro")
- Dificultad respiratoria / estridor
- Sospecha de miocarditis

**Ninguno de estos campos existe en la ficha MINSA de difteria.** Fueron
generados durante la carga de definiciones. Son clínicamente plausibles, y por
eso especialmente riesgosos: nadie los va a cuestionar al llenarlos.

**Acciones:**

1. Eliminar la sección "Signos de alarma" de la ficha de difteria.
2. **Auditar las 15 fichas ya cargadas** (lotes 1 a 4) contra el PDF, sección por
   sección. Todo campo que no aparezca literalmente en la ficha oficial se
   elimina.
3. Regla permanente: **el motor no inventa campos**. Si una ficha del PDF resulta
   ilegible o ambigua en algún punto, se deja el campo fuera y se reporta, nunca
   se completa por criterio clínico propio.
4. Agregar a `campo_def` la columna `origen ENUM('FICHA_MINSA','INTERNO')
   NOT NULL DEFAULT 'FICHA_MINSA'`, para poder distinguir después lo que viene
   del formato oficial de lo que agregue la institución.

---

## 2. Campos núcleo faltantes (afectan a TODAS las fichas)

Estos campos están en la ficha de difteria pero también en casi todas las demás.
No son específicos de la enfermedad: **van en la sección núcleo del paciente y de
la notificación**, no en la definición de cada ficha.

### En "Notificación"
- **Tipo de captación (vigilancia)** (SELECT): Activa · Pasiva
- **Lugar de captación** (SELECT): Institucional · Comunidad
- **Clasificación en la captación** (SELECT): Confirmado · Probable · Sospechoso
  *(distinta de la clasificación final — ver punto 7)*

### En "Datos del paciente"
- **N.° de celular** (TEXTO)
- **Nacionalidad** (SELECT, por defecto Peruana)
- **Domicilio actual** (TEXTO) — la dirección; hoy solo hay dpto/prov/distrito
- **Localidad** (TEXTO)
- **Etnia / raza** (SELECT): Mestizo · Andino · Asiático descendiente ·
  Afrodescendiente · Indígena amazónico · Otro (especificar)
- **Gestante** (BOOLEANO) + **Semanas de gestación** (NUMERO, condicional)

> **Nota de privacidad:** etnia es un dato sensible. Se captura porque el formato
> oficial lo exige; marcarlo `sensible = 1` y excluirlo de listados y
> exportaciones, como ya se definió para otros campos.

> **Gestante** debe mostrarse solo si el sexo es femenino, y las semanas de
> gestación solo si gestante = Sí.

Agregar las columnas correspondientes a `persona` y `caso` según corresponda:
celular, nacionalidad, direccion y localidad en `persona`; tipo y lugar de
captación y clasificación en captación en `caso`.

---

## 3. Tipos de campo mal asignados

Varios campos quedaron como texto libre cuando la ficha define opciones cerradas.
Esto anula la razón de ser del sistema: si el usuario escribe "si", "SI", "Sí" o
"afirmativo", el reporte no puede agrupar.

| Campo | Tipo actual | Tipo correcto |
|---|---|---|
| Hospitalizado | TEXTO | GRUPO_SI_NO (Sí / No / Ignorado) |
| Antibiótico antes del ingreso | TEXTO | BOOLEANO + campo "especificar" aparte |
| Tratamiento recibido | TEXTO | MULTISELECT: Antibiótico · Antitoxina + especificar |
| Egreso del hospital | TEXTO | SELECT: Recuperado · Referido · Falleció · Con secuela |
| ¿Estuvo en contacto con un posible caso de difteria? | TEXTO | Sí / No / Ignorado |
| ¿Sabe si hay casos similares en la zona? | TEXTO | Sí / No / Ignorado |
| Aislamiento domiciliario | TEXTO | Sí / No / Ignorado |

**Revisar el cargador de definiciones:** si asignó TEXTO por defecto en difteria,
probablemente lo hizo en otras fichas. Auditar todas.

---

## 4. Falta la lógica condicional entre campos

La ficha en papel lo dice literalmente: *"Si fue hospitalizado, complete la
siguiente información"*. El formulario muestra todo siempre.

**Agregar dependencias al motor:**

```sql
ALTER TABLE campo_def
  ADD COLUMN depende_de INT NULL AFTER catalogo_id,
  ADD COLUMN valor_activador VARCHAR(60) NULL AFTER depende_de,
  ADD CONSTRAINT fk_campo_depende FOREIGN KEY (depende_de) REFERENCES campo_def(id);
```

- Un campo con `depende_de` se **oculta** (no se deshabilita) mientras el campo
  padre no tenga el `valor_activador`.
- Al ocultarse, su valor se limpia antes de guardar.
- La validación de obligatoriedad solo aplica si el campo está visible.
- Debe funcionar en cascada (un campo condicional puede tener hijos).

**Dependencias en difteria:**

| Campo padre | Valor | Campos que se muestran |
|---|---|---|
| Hospitalizado | Sí | Hospital · Fecha de hospitalización · Tratamiento recibido · Egreso del hospital · Fecha de alta · Fecha de defunción · Complicaciones |
| Antibiótico antes del ingreso | Sí | Especificar antibiótico |
| Tratamiento recibido | Antibiótico | Especificar antibiótico |
| Egreso del hospital | Falleció | Fecha de defunción |
| Aislamiento domiciliario | Sí | Fecha de aislamiento |
| Vacunación contra difteria | Sí | N.° de dosis · Refuerzos · Fecha de última dosis |
| Gestante *(núcleo)* | Sí | Semanas de gestación |

---

## 5. Encabezado "Signos y síntomas" mal aplicado

En la sección 4 (Evolución), un bloque rotulado "SIGNOS Y SÍNTOMAS" agrupa
casillas que no son signos: "¿Antibiótico antes del ingreso?", "Tratamiento:
Antibiótico", "Tratamiento: Antitoxina", "Complicaciones: Cardíacas",
"Complicaciones: Neurológicas".

Son campos booleanos que el renderizador está agrupando bajo un encabezado por
defecto. Corregir: cada campo va en su sección y con su etiqueta real. Las
complicaciones son un `MULTISELECT` propio (Cardíacas · Neurológicas · Otros),
no casillas sueltas bajo un rótulo equivocado.

---

## 6. Las tablas hijas genéricas no alcanzan

### Contactos

`caso_contacto` tiene campos genéricos (nombres, parentesco, documento, celular),
pero la ficha de difteria pide otros:

**Censo de contactos domiciliarios (campo 55):**
Nombres y apellidos · **Edad** · **Sexo (M/F)** · **Vacunado (Sí/No/Ignorado)** ·
**Fecha de vacunación** · **Profilaxis (Sí/No)**

**Solución:** hacer configurable qué columnas muestra la tabla de contactos según
la ficha. Agregar `enfermedad.columnas_contacto` (JSON con las columnas activas)
y almacenar los valores específicos en una tabla `caso_contacto_valor`, o bien
ampliar `caso_contacto` con las columnas comunes (edad, sexo, vacunado,
fecha_vacunacion, profilaxis) y mostrar solo las que cada ficha requiere.
Recomiendo la segunda: son pocas columnas y se repiten entre fichas.

### Lugar probable de infección (campo 46)

**Falta por completo.** Es una tabla con: Lugar o institución o dirección ·
Localidad/Distrito · Provincia · Departamento · **Permanencia (días)**.

Reutilizar `caso_viaje` agregándole `lugar_institucion` y `permanencia_dias`, o
crear `caso_lugar_infeccion`. Aparece en varias fichas (fiebre amarilla, Chagas,
Carrión), así que conviene resolverlo de forma reutilizable.

### Laboratorio

La tabla genérica (tipo de muestra, tipo de prueba, resultado, fechas) no encaja.
Difteria estructura el laboratorio así:

- **Fecha de toma de muestra** (una sola, para el caso)
- **Tipo de muestra** (SELECT): Hisopado · Membrana
- Por cada prueba — **Cultivo** y **PCR**: resultado · fecha de resultado ·
  **¿recibió antibiótico?** (Sí/No)
- **Clasificación final de laboratorio**: Confirmado · Descartado

Hacer configurable el bloque de laboratorio por ficha, igual que el resto.

---

## 7. Dos clasificaciones distintas, en momentos distintos

La ficha tiene **dos** campos de clasificación y el sistema muestra uno genérico:

| Momento | Campo | Opciones |
|---|---|---|
| Al captar el caso (campo 6) | Clasificación en la captación | Confirmado · Probable · Sospechoso |
| Tras el laboratorio (campo 62) | Clasificación final | Confirmado · Descartado |

Ambos deben existir. `caso.clasificacion` guarda la **final**; la de captación es
un campo propio de la ficha. Y la clasificación final de difteria **no** ofrece
"Sospechoso" ni "Probable": solo Confirmado o Descartado.

**Regla general:** cada ficha define sus propias opciones de clasificación final.
No usar las cuatro genéricas en todas. Ya estaba señalado en la verificación de
los lotes anteriores; hay que aplicarlo.

---

## 8. Falta la sección "Investigador"

La ficha cierra con **VII. Investigador**: persona que llena la ficha · cargo ·
fecha de investigación (la firma y el sello no aplican en digital).

Aparece en casi todas las fichas MINSA. **Agregarla como sección núcleo**, no por
ficha. Puede autocompletarse con el usuario en sesión y quedar editable, porque a
veces quien digita no es quien investigó.

---

## 9. Orden de trabajo sugerido

1. Auditar las 15 fichas cargadas y **eliminar todo campo inventado**
2. Agregar los campos núcleo faltantes (punto 2) y la sección Investigador
3. Implementar la lógica condicional en el motor (punto 4)
4. Corregir los tipos mal asignados (punto 3), revisando todas las fichas
5. Hacer configurables contactos, lugar probable de infección y laboratorio
6. Rehacer la definición de difteria completa y verificarla campo por campo
   contra la ficha en papel
7. Repetir la comparación con la siguiente ficha

---

## Verificación

- [ ] No existe ningún campo que no aparezca en la ficha MINSA
- [ ] Celular, nacionalidad, etnia, domicilio, localidad y gestante están en el núcleo
- [ ] Tipo y lugar de captación están en la sección de notificación
- [ ] Ningún campo de opciones cerradas quedó como texto libre
- [ ] Al marcar Hospitalizado = No, los campos de hospitalización desaparecen
- [ ] El censo de contactos de difteria pide edad, sexo, vacunado y profilaxis
- [ ] Existe la tabla de lugar probable de infección con permanencia en días
- [ ] La clasificación final de difteria solo ofrece Confirmado y Descartado
- [ ] Existe la sección Investigador
- [ ] `theme.css` sin modificaciones; sin emojis ni librerías externas
