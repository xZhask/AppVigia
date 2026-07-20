# VIGÍA · Fichas restantes — Grupos B, C y D

Definición de las 9 fichas que faltan, extraídas del PDF de fichas MINSA.
Siguen vigentes las reglas de diseño de `PLAN_CLAUDE_CODE.md`.

> **Ejecutar un lote por sesión.** Los lotes 1 a 4 ya están cargados.

---

## 0. Cambio estructural — casos con más de un sujeto

Las fichas de este grupo **no encajan en el modelo actual** (`caso` tiene un solo
`paciente_id`). Cuatro de ellas describen un binomio madre–niño y dos incluyen a
una persona que no es paciente (el agresor, el conductor).

**Crear la tabla `caso_sujeto`:**

```sql
CREATE TABLE caso_sujeto (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  caso_id     INT NOT NULL,
  paciente_id INT NULL,              -- NULL si no es paciente del sistema
  rol         ENUM('CASO_INDICE','MADRE','RECIEN_NACIDO','NINO_EXPUESTO',
                   'AGRESOR','CONDUCTOR','OTRO') NOT NULL,
  -- datos inline para sujetos que no se registran como paciente
  apellidos   VARCHAR(120) NULL,
  nombres     VARCHAR(80)  NULL,
  doc         VARCHAR(20)  NULL,
  sexo        ENUM('M','F') NULL,
  edad        SMALLINT NULL,
  KEY ix_cs_caso (caso_id),
  KEY ix_cs_pac  (paciente_id),
  CONSTRAINT fk_cs_caso FOREIGN KEY (caso_id)     REFERENCES caso(id) ON DELETE CASCADE,
  CONSTRAINT fk_cs_pac  FOREIGN KEY (paciente_id) REFERENCES paciente(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

- Todo caso existente conserva su `paciente_id` en `caso` y se le crea además una
  fila en `caso_sujeto` con rol `CASO_INDICE`. **No romper las fichas ya cargadas.**
- Agregar a `enfermedad` la columna `multi_sujeto TINYINT(1) DEFAULT 0` y los roles
  que cada ficha requiere (columna `roles_sujeto`, texto separado por comas).
- Los campos de `campo_def` que pertenecen a un sujeto distinto del índice llevan
  una columna nueva `rol_sujeto`, para saber a quién corresponden.

**En la interfaz:** las fichas multi-sujeto se muestran con pestañas o secciones
claramente rotuladas por sujeto ("Gestante" / "Niño expuesto"), nunca mezclando
los datos de ambos en una misma lista de campos.

---

## 0.b Confidencialidad reforzada

Estas fichas contienen la información más sensible del sistema: estado de VIH,
violencia familiar y muertes. Aplicar sobre lo ya definido:

- Marcar como `sensible = 1` **todos** los campos de VIH/SIDA, sífilis, violencia
  familiar y los antecedentes de la ficha de muerte materna.
- Las fichas de **VIH/SIDA y violencia familiar** solo son visibles para los roles
  `EPIDEMIOLOGO` y `ADMIN`. Un `REGISTRADOR` puede crearlas, pero **no listar ni
  consultar las que no registró él mismo**.
- En listados y reportes agregados: mostrar solo conteos, nunca nombres ni
  documentos de estas fichas.
- Registrar en `caso_bitacora` **cada apertura** de una ficha sensible, no solo
  las ediciones.
- La ficha de VIH/SIDA usa **código de paciente** (iniciales + fecha de
  nacimiento), no el nombre completo, tal como exige el formato MINSA. Respetarlo.
- Los datos del **agresor** (violencia familiar) nunca aparecen en exportaciones.

---

# LOTE 5 · Materno-perinatal y transmisión vertical

## 5.1 Gestante con VIH y niño expuesto (Z21)

`multi_sujeto = 1` · roles: `MADRE`, `NINO_EXPUESTO`

### Sección I — Gestante con VIH  *(rol MADRE)*

**Datos de la gestante**
- Código · Fecha de reporte de la ficha de investigación epidemiológica

**Momento de diagnóstico de infección por VIH**
- Momento (SELECT): Previo a la gestación actual · Durante la actual gestación
- Si es previo: Año de diagnóstico (NUMERO)
- Si es durante (SELECT): Atención prenatal (APN) · Trabajo de parto · Puerperio ·
  Posterior al puerperio · Por aborto
- Prueba de tamizaje N.° 1 (FECHA) · Prueba de tamizaje N.° 2 (FECHA) ·
  Prueba confirmatoria (FECHA)

**Datos de la gestación**
- FUR (FECHA) · ¿Recibió APN? (BOOLEANO) · ¿Embarazo múltiple? (BOOLEANO)
- ¿Recibió ARV? (BOOLEANO) · Fecha de inicio de ARV · ¿Abandonó terapia ARV?
- ¿Recibe terapia triple / TARGA? (BOOLEANO)
- Culminación del embarazo: N.° de nacidos vivos (NUMERO) ·
  N.° de óbitos fetales (NUMERO) · Aborto (BOOLEANO)
- ¿Parto por cesárea? (BOOLEANO)
- EE.SS. del parto: DIRESA · nombre · institución (MINSA / EsSalud / FFAA-FFPP /
  Privado / Otro) · Fecha del parto
- ¿Carga viral indetectable? (BOOLEANO) · ¿Abandona seguimiento? (BOOLEANO) ·
  ¿La gestante fallece? (BOOLEANO)

### Sección II — Niño nacido expuesto al VIH  *(rol NINO_EXPUESTO)*

**Datos del niño**
- Código · DNI · Sexo · Apellidos y nombres · Fecha de nacimiento ·
  Código de la madre · DNI de la madre

**Datos de la exposición al VIH**
- ¿Recibió ARV? (BOOLEANO) · Fecha de inicio de ARV · ¿Abandonó terapia ARV?
- ARV recibido (SELECT): AZT · AZT + NVP · Otro
- N.° de días que tomó ARV (NUMERO)
- ¿Profilaxis ARV de acuerdo a NT vigente? (BOOLEANO)
- ¿Sucedáneos de leche materna? (BOOLEANO) + N.° de meses que los recibió
- ¿Tomó leche materna? (BOOLEANO)

**Estado serológico del niño expuesto**
- Estado serológico final (SELECT): Infectado por VIH · No infectado por VIH ·
  Estado indeterminado
- Motivo de estado indeterminado (SELECT): Continúa en seguimiento ·
  Fallecido antes de poder determinar su estado · Abandonó el seguimiento /
  seguimiento irregular · Referido
- Pruebas diagnósticas (MATRIZ): filas = 1.er PCR, 2.º PCR, Prueba de ELISA,
  Prueba confirmatoria × columnas = fecha de toma de muestra, resultado
  (Positivo/Negativo, o Reactivo/No reactivo en ELISA)
- Observaciones (TEXTAREA)

---

## 5.2 VIH / SIDA — notificación individual (B24)

> Ficha con **código de paciente**, no nombre completo. Todos los campos
> `sensible = 1`.

**Identificación**
- Código del paciente (TEXTO): iniciales AP · AM · N1 · N2 + fecha de nacimiento
- DNI / CE / Pasaporte

**Motivo de notificación** (SELECT)
- Infección por VIH → subtipo (MULTISELECT): Estadio SIDA · Gestante con VIH ·
  Niño nacido expuesto, infectado por VIH · Inicio de TARGA ·
  Fallecido con VIH o SIDA
- Niño nacido expuesto al VIH
- Niño nacido expuesto, no infectado por VIH

**Estadio de infección VIH al momento del diagnóstico** (SELECT)
- Estadio 1 · Estadio 2 (Avanzado) · Estadio 3 (SIDA) · Desconocido

**Datos sociodemográficos**
- Residencia habitual: país · departamento · provincia · distrito · comunidad
- Grado de instrucción (SELECT): Analfabeta · Primaria · Secundaria · Técnica ·
  Universitaria
- Condición especial (MULTISELECT): Trabajador(a) sexual · Privado de libertad ·
  Usuario de drogas inyectables · Usuario de drogas no inyectables
- Sexo al nacer (SELECT): Mujer · Hombre
- Identidad de género (SELECT): Femenino · Masculino ·
  Transgénero masculino a femenino · Transgénero femenino a masculino · Otro ·
  Desconocido
- Antecedentes de RS (SELECT): RS con hombres · RS con mujeres ·
  RS con ambos sexos · Desconocido

**Vía de transmisión** (SELECT)
- Sexual → Heterosexual · Homosexual · Bisexual · No determinado
- Parenteral → Transfusión de sangre y/o derivados · Compartir agujas / UDI ·
  Accidente con material contaminado · Trasplante de órganos o tejidos ·
  No determinado
- Madre-niño (vertical) · Desconocida

**Laboratorio para caso de infección VIH** → `caso_muestra`
- Pruebas de tamizaje reactivas N.° 1 y N.° 2: fecha + tipo (Prueba rápida ·
  Prueba de ELISA · Otra)
- Pruebas confirmatorias positivas N.° 1 y N.° 2: fecha + tipo (Western Blot ·
  IFI · LIA · PCR)
- Laboratorio para niño expuesto no infectado: fecha + tipo (ELISA · IFI ·
  Western Blot · PCR)

**TARGA y estadio SIDA**
- Fecha de inicio de tratamiento (FECHA)
- Estadio SIDA: fecha de diagnóstico
- Criterio diagnóstico de SIDA (SELECT): CD4 · Enfermedad indicadora
- Enfermedades indicadoras de SIDA (MATRIZ): descripción + código CIE-10 (2 filas)

**Coinfección**
- Tuberculosis (BOOLEANO + fecha de diagnóstico)
- Hepatitis B (BOOLEANO + fecha) · Hepatitis C (BOOLEANO + fecha)

**Defunción**
- Fecha · ¿Defunción relacionada a SIDA? (BOOLEANO) · Causa de muerte

---

## 5.3 Sífilis materna y congénita (A50)

`multi_sujeto = 1` · roles: `MADRE`, `RECIEN_NACIDO`

**Encabezado**
- Investigación de (MULTISELECT): Sífilis materna · Sífilis congénita
- Nivel del establecimiento (SELECT): I-1 · I-2 · I-3 · I-4 · II-1 · II-2 · III-1

### Sección II — Sífilis materna  *(rol MADRE)*

- Fecha de nacimiento · Edad
- Lugar de residencia habitual: país · dpto · prov · dist · localidad

**Embarazo actual**
- FUR (FECHA) o Desconocido (BOOLEANO)
- ¿Recibió atención prenatal? (SELECT): Sí · No · Desconocido
- Fecha de primer control prenatal o Desconocido
- Edad gestacional en el primer control prenatal (semanas)

**Pruebas no treponémicas (RPR, VDRL)** — primera (a) y más reciente (b)
- (MATRIZ) fecha · resultado (Reactivo / No reactivo / Desconocido) ·
  título (1:__) · momento (Gestación / Parto / Puerperio)

**Pruebas treponémicas (TPHA, TPPA, FTA Abs, ELISA, Rápida o Dual)** — primera y
más reciente
- (MATRIZ) fecha · tipo de prueba (Prueba rápida / P. Dual · Otra) ·
  resultado (Reactivo / No reactivo / Desconocido) · momento

**Tratamiento**
- ¿Fue la madre adecuadamente tratada durante el embarazo? (SELECT): Sí · No ·
  Desconocido
- Si no, motivo (SELECT): Tratamiento sin penicilina · Tratamiento durante los
  30 días previos al parto · No inició tratamiento durante la gestación ·
  Tratamiento incompleto (1 ó 2 dosis)
- Contacto(s) sexual(es) tratado(s) (SELECT: Sí / No / Desconocido) + N.°

**Clasificación de caso de sífilis en la gestante** (SELECT)
- Probable · Confirmado · Descartado (falso positivo) · Descartado (sífilis memoria)

### Sección III — Sífilis congénita  *(rol RECIEN_NACIDO)*

- Fecha de parto / culminación del embarazo o Desconocido
- Lugar del parto (SELECT): Establecimiento de salud (+ nombre + nivel) · Domicilio
- Estado vital (SELECT): Vivo · Nació vivo, luego falleció · Mortinato · Aborto
- Fecha de fallecimiento o Desconocido
- Peso al nacimiento (gramos) o Desconocido
- Edad gestacional estimada (semanas) o Desconocido

**Criterios de caso de sífilis congénita** (MULTISELECT)
- Madre con sífilis que no recibió tratamiento o fue tratada inadecuadamente
- Títulos no treponémicos cuatro veces mayores que los de la madre →
  fecha de los test · título de la madre · título del niño
- Niño con manifestaciones clínicas sugestivas (examen físico o radiográfico)
- Demostración de *Treponema pallidum* en lesiones, placenta, cordón umbilical o
  material de autopsia
- Niño mayor de 2 años con signos de sífilis secundaria, descartado el
  antecedente de abuso o contacto sexual

**Tratamiento del niño** (SELECT)
- Sí, con penicilina G sódica o procaínica por ≥10 días ·
  Sí, con penicilina benzatínica × 1 dosis · Sí, con otro tratamiento ·
  No recibió tratamiento · Desconocido

**Clasificación final del niño, mortinato o aborto** (SELECT)
- Sífilis congénita · Niño expuesto a sífilis, no infectado

---

## 5.4 Síndrome de rubéola congénita — SRC (P35.0)

`multi_sujeto = 1` · roles: `CASO_INDICE` (el niño), `MADRE`

**Antecedentes del paciente**
- ¿Nació prematuro? (BOOLEANO) · Edad gestacional al parto (semanas) ·
  Peso al nacer (gramos) · APGAR 1' (NUMERO) · APGAR 5' (NUMERO)

**Antecedentes de la madre**  *(rol MADRE)*
- Tipo y N.° de documento · Apellidos y nombres · Edad · Fecha de nacimiento ·
  Nacionalidad · Ocupación
- ¿Vacunada contra la rubéola? (SELECT: Sí / No / Desconocido) + fecha
- ¿Presentó fiebre y exantema maculopapular durante el embarazo?
  (SELECT: Sí / No / No recuerda) → ¿en cuál semana de la gestación? ·
  ¿fue confirmada por laboratorio la rubéola de la madre? (BOOLEANO)
- ¿Durante el embarazo se expuso a alguna persona con fiebre y exantema
  maculopapular? (SELECT: Sí / No / Desconocido) → ¿con quién? ·
  ¿en cuál semana de la gestación?
- ¿Durante el embarazo viajó fuera del país? (BOOLEANO) → `caso_viaje`
  (país, localidad/ciudad, fecha de salida, fecha de retorno, semana de gestación)

**Cuadro clínico — manifestaciones** (GRUPO_SI_NO con fecha de manifestación;
Sí / No / Desconocido)
- *Oftálmicas:* Cataratas · Glaucoma congénito · Retinopatía pigmentaria ·
  Microftalmia
- *Auditiva:* Déficit de la audición
- *Cardiopatía congénita:* Estenosis periférica de arteria pulmonar ·
  Persistencia del conducto arterioso · Comunicación interauricular ·
  Otra cardiopatía congénita (especificar)
- *Otras:* Púrpura · Trombocitopenia · Hepatomegalia · Esplenomegalia ·
  Microcefalia · Meningoencefalitis · Enfermedad ósea de radiotransparencia ·
  Retraso en el desarrollo psicomotor · Ictericia (dentro de las 24 h del
  nacimiento)

**Hospitalización y defunción**
- Hospitalización (BOOLEANO) + EE.SS. + fecha + Dx de ingreso
- Defunción (BOOLEANO) + fecha + causa básica de defunción

**Laboratorio** → `caso_muestra`
- 1.ª muestra serológica: IgM (+/−) · IgG (+/−) + titulación
- 2.ª muestra serológica: IgM (+/−) · IgG (+/−) + titulación
- Hisopado nasal y faríngeo: resultado (+/−) + genotipo
- Solo en casos confirmados — seguimiento de excreción viral: dos hisopados
  nasales y faríngeos adicionales, con un mes de intervalo, hasta obtener dos
  resultados negativos

**Clasificación del caso** (SELECT)
- Sospechoso · Confirmado · Descartado ·
  Infección congénita por el virus de la rubéola

---

# LOTE 6 · ESAVI severo

## 6.1 ESAVI severo

**Notificación**
- Tipo (SELECT): Severo · Conglomerado (leve-moderado)
- Fecha de identificación local del caso (o consulta)
- Fecha de notificación de DIRESA/GERESA/DIRIS a CDC/MINSA
- Fecha de inicio de investigación

**Datos del paciente (adicionales)**
- Pueblo étnico · Etnia
- Gestante (BOOLEANO) + N.° de semanas de gestación
- Tipo de localidad (SELECT): Urbano · Periurbano · Rural
- ¿Está asegurado? (SELECT): Sí · No · SIS · EsSalud · Privado
- Ocupación (SELECT): Sin ocupación · Estudiante · Comerciante · Empleado ·
  Personal de salud · Otro

**Datos de la vacunación** (MATRIZ, hasta 4 filas) → complementa `caso_vacuna`
- Nombre de vacuna (SELECT con códigos): 01 BCG · 02 DPT · 03 APO ·
  04 Hepatitis B · 05 Hib · 06 Pentavalente · 07 SPR · 08 Fiebre amarilla ·
  09 SR · 10 DT · 11 Influenza estacional · 12 Antisarampión ·
  13 Contra neumococo · 14 Contra rotavirus · 15 Contra VPH · 16 IPV ·
  17 Contra varicela · 18 dTpa · 19 Anti COVID-19 · 20 Otro
- Adyuvante (SELECT): 01 Sí · 02 No
- Dosis (SELECT): 01 Primera · 02 Segunda · 03 Tercera · 04 Adicional ·
  05 Única · 06 Refuerzo
- Vía (SELECT): 01 Oral · 02 Intradérmica · 03 Subcutánea · 04 Intramuscular
- Sitio (SELECT): 01 Hombro derecho · 02 Hombro izquierdo · 03 Brazo derecho ·
  04 Brazo izquierdo · 05 Vasto externo de muslo derecho ·
  06 Vasto externo de muslo izquierdo · 09 Oral
- Fecha y hora de vacunación · EE.SS. que vacunó · Fabricante · Lote ·
  Fecha de expiración

**Antecedentes**
- *Personales* — ¿ESAVI previo? (BOOLEANO) → cuál (MULTISELECT): Convulsión ·
  Rush · Pérdida de conocimiento · Otra
- *Condiciones de comorbilidad* (MULTISELECT): Alergia · Convulsión · Asma ·
  Diabetes · Obesidad · HTA · Enf. renal · Daño hepático · Cáncer ·
  Enf. pulmonar · Enf. reumatológica · Enf. cardiovascular ·
  Enf. neurológica o neuromuscular · Inmunodeficiencia (incluye VIH) · Otra
- *Familiares — cuadros patológicos* (MULTISELECT): Alergia · Asma · Urticaria ·
  Epilepsia · Diabetes · Obesidad · Cáncer · Convulsión febril en la infancia ·
  COVID-19 · TBC · HTA · Enf. cardiovascular · Enf. pulmonar ·
  Enf. reumatológica · Enf. renal · Inmunodeficiencia (incluye VIH) · Otra
- *Epidemiológicos — enfermedades prevalentes en la región* (MULTISELECT):
  Dengue · Malaria · Zika · Leptospirosis · Bartonelosis · Rabia · Otra

**Signos y síntomas** (MATRIZ)
Por cada evento: tiempo entre la vacunación y el inicio del cuadro
(minutos / horas / días) · fecha de inicio · fecha de término.

1. Absceso en el sitio de inyección → Estéril · Bacteriano
2. Linfadenitis supurativa → Nódulo linfático >1,5 cm · Nódulo bacteriano
3. Reacción local severa → Inflamación más allá de la articulación más cercana ·
   Dolor, enrojecimiento e inflamación de más de 3 días ·
   Inflamación >10 cm con limitación funcional
4. Llanto persistente (>3 horas) → Solo asociado a fiebre ·
   Asociado a otros síntomas
5. Convulsiones → Febril · Afebril
6. Síndrome hipotónico-hiporreactivo → Asociado a depresión respiratoria o
   cianosis · No asociado
7. Reacción alérgica → Reacción anafiláctica · Shock anafiláctico
8. Púrpura trombocitopénica → Solo manifestaciones dérmicas (petequias) ·
   Asociadas a otros síntomas
9. Síncope o reacción vasovagal
10. Parálisis flácida aguda → Asimétrica · Simétrica (d/c SGB, mielitis transversa)
11. Encefalopatías → Convulsiones · Severa alteración de conciencia por uno o más
    días · Cambio de conducta por uno o más días · Daño cerebral permanente
12. Encefalitis  ·  13. Meningitis  ·  14. Osteítis / osteomielitis
15. Artralgia → Persistente · Transitoria
16. Sepsis  ·  17. Síndrome de shock tóxico
18. Otros eventos severos e inusuales (especificar)

**Descripción del cuadro clínico**
- Fecha de inicio · Gravedad del caso · Secuencia cronológica de instalación de
  signos y síntomas (TEXTAREA) · Exámenes auxiliares (TEXTAREA) ·
  Tratamiento recibido (TEXTAREA) · Evolución (TEXTAREA)

**Hospitalización**
- N.° de historia clínica · Fecha de ingreso · Fecha de alta ·
  Dx de ingreso · Dx de egreso
- Estado de alta (SELECT): Mejorado · Secuela · Fallecido
- ¿Transferido? (BOOLEANO) + a dónde

**Seguimiento del paciente** (SELECT)
- 1 Caso no ubicable · 2 En rehabilitación · 3 Requiere solo control médico ·
  4 Requiere tratamiento quirúrgico · 5 (3) y (4) · 6 Recuperado sin secuela ·
  7 Recuperación con secuela · 8 Otro estudio final

**Clasificación final** (SELECT)
- 1 Reacción relacionada a la vacuna
- 2 Reacción relacionada con un defecto en la calidad de la vacuna
- 3 Reacción relacionada con un error en la inmunización
- 4 Reacción relacionada con la ansiedad por la inmunización
- 5 Eventos coincidentes
- 6 Evento no concluyente

### 6.2 Anexo — Lista de chequeo del vacunatorio  *(opcional)*

Instrumento de investigación aparte, que se llena **visitando el vacunatorio**.
Modelarlo como una **sección opcional** que solo se habilita cuando la
clasificación final es 2 o 3 (defecto de calidad o error en la inmunización).

Secciones, todas con el patrón Sí / No + consideración + comentario:
I. Cadena de frío (9 ítems) · II. Lavado de manos · III. Reconstitución del
biológico · IV. Técnica de aplicación de la vacuna · V. Vacunas, jeringas y
bioseguridad · VI. Consejería · VII. Inmunobiológicos y diluyente ·
VIII. Aspectos operativos · IX. Jeringas y agujas utilizadas ·
X. Procedimiento de reconstitución · XI. Cadena de frío y transporte ·
XII. Transporte de la vacuna.

Datos del centro de vacunación y del vacunador (nombres, profesión, centro
laboral, experiencia laboral) van al inicio del anexo.

> Si el volumen de este anexo complica el lote, cargarlo en una sesión aparte:
> la ficha ESAVI funciona sin él.

---

# LOTE 7 · Eventos externos

## 7.1 Violencia familiar

> **Ficha de máxima confidencialidad.** Todos los campos `sensible = 1`.
> Visible solo para `EPIDEMIOLOGO` y `ADMIN`. Los datos del agresor nunca se
> exportan. Registrar cada apertura en bitácora.

`multi_sujeto = 1` · roles: `CASO_INDICE` (persona agredida), `AGRESOR`

**Institución que registra**
- (SELECT): MINSA · PNP · DEMUNAS · CMM · Otros → ¿qué otras instituciones?
- Tipo (SELECT): Hospital · Centro de salud

**Datos de la persona agredida**  *(rol CASO_INDICE)*
- Documento de identidad · Departamento de residencia en el último año
- Edad · Sexo
- Si es mujer: ¿se encuentra gestando? (BOOLEANO)
- Estado civil (SELECT): Soltero(a) · Casado(a) · Conviviente · Separado(a) ·
  Divorciado(a) · Viudo(a)
- Grado de instrucción (SELECT): Iletrada · Primaria · Secundaria · Superior
  + (SELECT): Completa · Incompleta
- ¿Tiene empleo remunerado? (BOOLEANO) + ¿cuál es su ocupación?
- Dirección: departamento · provincia · distrito · localidad

**Datos de la persona agresora**  *(rol AGRESOR)*
- Edad · Sexo
- Vínculo con la víctima (SELECT): Esposo(a) · Conviviente · Hijo(a) · Padre ·
  Madre · Otro (especificar)
- Grado de instrucción (SELECT) + Completa / Incompleta
- ¿Tiene empleo remunerado? (BOOLEANO) + ocupación

**Datos sobre la agresión**
- Estado del agresor (SELECT): Ecuánime · Efecto de drogas · Efecto de alcohol ·
  Ambas
- Tipo de violencia (MULTISELECT): Física · Psicológica ·
  Relaciones sexuales forzadas · Abandono
- Medio utilizado (MULTISELECT): Propio cuerpo · Arma blanca · Arma de fuego ·
  Objeto contundente
- Motivo expresado (SELECT): Familiares · Celos · Económicos · Laborales ·
  Sin motivo · Otros
- ¿Es la primera vez que es agredido(a)? (BOOLEANO)
- Durante la semana, ¿cuántas veces fue agredido(a)? (SELECT 1–7)
- Durante el último mes, ¿cuántas veces? (SELECT): 1 · 2 a 3 · 4 a 5 · 6 a 7 ·
  7 a 8 · 9 · 10
- Lugar de la agresión (SELECT): Calle · Casa · Centro de trabajo · Otros

**Medidas tomadas** (MULTISELECT)
- Atención médica · Atención psicológica · Denuncia judicial ·
  Asistencia social · Denuncia policial · Otros (especificar)

**Seguimiento**
- ¿Fue derivado? (BOOLEANO) → ¿dónde? (MULTISELECT): Ministerio de Salud ·
  Policía · ONG · Ministerio Público · Médico legal · DEMUNA · Otros

---

## 7.2 Lesiones por accidentes de tránsito

`multi_sujeto = 1` · roles: `CASO_INDICE` (lesionado), `CONDUCTOR`

**Fuente de financiamiento** (SELECT)
- SOAT · MTC · Particular

**Datos del lesionado**
- N.° de HC de emergencia · N.° de HC de hospitalización
- ¿Referido de un EE.SS.? (BOOLEANO) + nombre del EE.SS.
- Dirección: jr/av/calle/localidad · distrito · provincia · departamento
- Fecha y hora de ingreso al establecimiento
- Diagnóstico médico Dx 1 / Dx 2 / Dx 3 + código CIE-10 de cada uno
- Fecha de egreso del establecimiento
- Condición de egreso (SELECT): Alta · Fallecido · Referido (+ a dónde)
- ¿Requiere rehabilitación? (BOOLEANO)

**Datos del accidente**
- Fecha y hora del accidente
- Lugar: jr/av/calle/localidad · departamento · provincia · distrito
- Vía donde ocurrió (SELECT): Calles/jirones · Avenidas · Carreteras ·
  Autopistas / vía expresa · Fluvial · Aéreo · Marítimo
- Tipo de accidente (SELECT): Atropellado · Choque · Volcadura ·
  Caída de ocupante · Otro

**Referente al lesionado**
- El lesionado se encontraba en (SELECT): Motocicleta · Motocar · Automóvil ·
  Microbús · Ómnibus · Camión/tráiler · Tren · Bicicleta · Carreta · Avión ·
  Avioneta/helicóptero · Embarcación con motor · Embarcación sin motor
- Ubicación del lesionado (SELECT): Pasajero · Conductor · Peatón
- Traslado del lesionado por (SELECT): Ocasionante · Familiar · Propios medios ·
  Serenazgo · Persona particular · Policía · Bombero ·
  Ambulancia de servicio de salud

**Referente al ocasionante del accidente**
- Tipo de vehículo del ocasionante (SELECT, misma lista de vehículos)
- Condición del vehículo ocasionante (SELECT): Particular · Público · Estatal ·
  Privado

**Datos del conductor**  *(rol CONDUCTOR)*
- Apellidos y nombres · Edad · Sexo
- N.° de licencia de conducir (SELECT): Sí (+ N.°) · No · No se sabe
- Comisaría donde se registra la denuncia policial + departamento · provincia ·
  distrito

**Datos del vehículo**
- N.° de póliza SOAT · N.° de placa del vehículo ·
  Nombre del dueño de la póliza SOAT
- Aseguradora (SELECT): Rímac · Pacífico Seguros · La Positiva ·
  Generali Perú · Mapfre Perú · Latino Seguros · Otro

---

# LOTE 8 · Muertes bajo vigilancia

## 8.1 Muerte materna

Dos instrumentos sobre el **mismo caso**: notificación inmediata (Anexo 1) e
investigación (Anexo 2). Modelarlos como dos secciones del mismo `caso`, no como
dos casos distintos. El Anexo 2 se habilita después de registrado el Anexo 1.

### Anexo 1 — Notificación inmediata

- Fecha y hora de notificación (24 h)
- Identificado por (SELECT): Vigilancia activa · Vigilancia pasiva
- Institución que notifica (SELECT): IGSS/Gobierno Regional · EsSalud ·
  Sanidad de FFAA/PNP · Privado · Otra
- Datos de la fallecida: apellidos · nombres · edad · DNI · N.° de HC ·
  domicilio · departamento · provincia · distrito

**Datos del fallecimiento**
- Momento (SELECT): Embarazo · Parto · Puerperio · Desconocido
- Edad gestacional al momento del fallecimiento (semanas) o Desconocido
- Fecha y hora de fallecimiento
- Lugar del fallecimiento (SELECT): EE.SS. IGSS/Gobierno Regional ·
  EE.SS. EsSalud · EE.SS. Sanidad FFAA/PNP · EE.SS. privado · Trayecto ·
  Domicilio · Otro (+ nombre del EE.SS. o lugar)
- Permanencia (estadía) en el EE.SS.: días · horas · minutos
- Departamento · provincia · distrito del fallecimiento

**Referencia**
- ¿Referida? (BOOLEANO) + EE.SS. de origen + departamento · provincia · distrito

**Causas de defunción**
- Causa final probable · Causa intermedia probable · Causa básica probable,
  cada una con su código CIE-10
- Causa genérica (SELECT): Hemorragia · Hipertensión gestacional ·
  Infección/Sepsis · Otra causa
- Clasificación inicial (SELECT): Directa · Indirecta · Incidental ·
  Por determinar

### Anexo 2 — Investigación epidemiológica

**Datos básicos adicionales**
- Grupo étnico · Etnia
- Idioma (SELECT): Español · Quechua · Aymara · Otra
- Nivel educativo (SELECT): Ninguno · Primaria incompleta · Primaria completa ·
  Secundaria incompleta · Secundaria completa · Superior universitaria ·
  Superior técnica · Desconocido
- Estado civil (SELECT): Soltera · Casada · Conviviente · Divorciada ·
  Separada · Viuda · Desconocido
- Ocupación · Tipo de seguro (SELECT): SIS · EsSalud · Privado · Otros ·
  No tiene seguro

**Datos del fallecimiento (ampliados)**
- Fase del puerperio en que falleció (SELECT): Inmediato · Mediato · Tardío ·
  No aplica · Desconocido
- Categoría del EE.SS. (SELECT): I-1 · I-2 · I-3 · I-4 · II-1 · II-2 · II-E ·
  III-1 · III-E · III-2 · Desconocido
- Fecha y hora de ingreso al EE.SS.
- Responsable de la atención (SELECT): Méd. gineco-obstetra · Méd. intensivista ·
  Méd. residente · Méd. general · Obstetra · Enfermera(o) · Interno · Técnico ·
  Partera · Familiar · Otro · Desconocido

**Antecedentes patológicos** (MULTISELECT, `sensible`)
- Ninguno · Hipertensión crónica · Diabetes mellitus · Cardiopatías ·
  Enfermedad renal · Neoplasias · Enfermedad hepática · Tuberculosis ·
  ITS/VIH/SIDA · Alcoholismo · Drogadicción · Violencia de género ·
  Tabaquismo · Desnutrición crónica · Otra · Desconocido

**Antecedentes gineco-obstétricos**
- N.° de gestaciones previas · N.° de partos · N.° de cesáreas · N.° de abortos ·
  N.° de nacidos vivos · N.° de nacidos muertos · N.° de hijos que viven ·
  Período intergenésico (años / meses)
- Uso de método anticonceptivo previo (SELECT): No usó · Hormonal · DIU ·
  Barrera · Quirúrgico · Abstinencia periódica · Otro · Desconocido

**Atención prenatal**
- ¿Recibió APN? (BOOLEANO) · Primera atención (SELECT): I · II · III trimestre
- Número de APN · EE.SS. con mayor cantidad de atenciones + categoría
- ¿Se realizaron visitas domiciliarias? (BOOLEANO) + número
- ¿Se realizó plan de parto completo? (BOOLEANO)
- Responsable de la APN (SELECT, misma lista de responsables)

**Complicaciones** (MULTISELECT por etapa)
- *Embarazo:* Hemorragia · Preeclampsia/Eclampsia · Síndrome de HELLP ·
  Diabetes gestacional · Aborto · Desnutrición · RPM más de 12 horas ·
  Embarazo ectópico · Infección del tracto urinario · Sepsis · Óbito fetal ·
  Anemia · Otro
- *Parto:* Hemorragia · Preeclampsia/Eclampsia · Síndrome de HELLP ·
  Trabajo de parto prolongado · Parto obstruido · Parto distócico ·
  Trabajo de parto precipitado · Alumbramiento incompleto · Otro
- *Puerperio:* Hemorragia · Atonía uterina · Preeclampsia/Eclampsia ·
  Síndrome de HELLP · Sepsis · Endometritis ·
  Retención de restos placentarios · Depresión posparto · Otro

**Referencia y hospitalizaciones**
- ¿Referida? + N.° de referencias institucionales + EE.SS. de origen
- Fechas y horas de ingreso y egreso del EE.SS. de origen
- Tiempo de demora en llegar al EE.SS. de destino (días / horas)
- Institución y EE.SS. de destino + fecha y hora de ingreso
- ¿Hospitalizaciones en la gestación/puerperio? + cuántas
- ¿Requirió transfusión de sangre? · ¿Expansores plasmáticos?

**Parto o aborto**
- Fecha · Lugar (SELECT): Domicilio · En EE.SS. · Otro · No aplica
- Tipo de parto (SELECT): Vaginal · Cesárea · Instrumentado · Desconocido ·
  No aplica
- Responsable de la atención del parto o aborto (SELECT)
- ¿Necropsia? (BOOLEANO) + diagnóstico / causa CIE-10

**Entorno social y comunitario**
- ¿Identificaron signos de peligro? (BOOLEANO) + persona que los identificó
  (Ella misma · Pareja · Familiar · Otro)
- ¿Buscaron ayuda? (BOOLEANO) + quién tomó la decisión
- Tiempo que demoró en buscar ayuda desde el inicio de sus molestias
  (horas / minutos)
- ¿Hubo dificultad con el acceso a servicios de salud? (BOOLEANO) → especificar
  (MULTISELECT): Inaccesibilidad geográfica · Distancia · Transporte ·
  Creencias/costumbres · Otro
- Tiempo desde el inicio de las molestias hasta llegar al EE.SS.
- ¿Tuvo dificultades para ser atendida en el EE.SS.? (BOOLEANO) → especificar
  (MULTISELECT): Económicas · Idioma · Administrativas/trámites ·
  Demora en la atención · Mala atención · Otro
- Tiempo desde que llegó al EE.SS. hasta que fue atendida
- Persona que brindó la información y su relación con la fallecida (SELECT):
  Madre · Padre · Pareja · Familiar · Partera · Vecino · Otro

**Datos comunitarios** (solo muerte extrainstitucional)
- Sintomatología o molestias (MULTISELECT): Sangrado · Pérdida de líquido ·
  Dolor · Sensación de alza térmica · Náuseas y vómitos · Convulsiones ·
  Debilidad · Ansiedad · Pérdida o alteración del estado de conciencia ·
  Cefalea · Otro
- Maniobras usadas durante el parto (MULTISELECT): No se usó · Manteo ·
  Acomodo · Masajes · Otro
- Maniobras usadas para retirar la placenta (misma lista)
- Tiempo estimado del domicilio al EE.SS. más cercano (horas / minutos)
- Tipo de establecimiento más cercano (SELECT): Puesto de salud ·
  Centro de salud · Hospital

**Causas de defunción (revisado por el CPMMyP)**
- Causa final · Causa intermedia · Causa básica · Causa asociada + CIE-10
- Causa genérica (SELECT): Hemorragia · Hipertensión gestacional ·
  Infección/Sepsis · Otra causa
- Clasificación final (SELECT): Directa · Indirecta · Incidental

**Las cuatro demoras** (GRUPO_SI_NO)
1. En la identificación del problema
2. En la decisión de buscar ayuda
3. En acceder a los servicios de salud
4. En recibir tratamiento adecuado y oportuno

> El análisis de las cuatro demoras es el corazón epidemiológico de esta ficha.
> Debe poder cruzarse en reportes: demoras por establecimiento, por red y por
> causa genérica.

---

## 8.2 Muerte fetal y neonatal

El formato original es una **lista de línea** (varios fallecimientos por hoja).
Modelar **un caso por fallecimiento**, no una hoja con varias filas: así se
mantiene la trazabilidad individual y los reportes agregados salen solos.

**Datos del fallecido**
- Apellidos y nombres · Sexo (F / M)
- Edad gestacional (semanas)
- Nacimiento: fecha · hora
- Muerte: fecha · hora
- Peso al nacer (gramos)

**Tipo de muerte** (SELECT)
- Fetal · Neonatal

**Causa y diagnóstico**
- Causa básica de muerte (TEXTO) — la entidad que inicia la cadena de
  acontecimientos; se anota **una sola** causa, la que figura como causa básica
  en el certificado de defunción
- Diagnóstico CIE-10

**Circunstancias**
- N.° de días de estancia hospitalaria (solo para muerte neonatal)
- Lugar del parto (SELECT): PI Parto institucional · PD Parto domiciliario
- Momento de ocurrencia del fallecimiento (SELECT): Anteparto · Intraparto ·
  Post-parto
- Lugar de la muerte (SELECT): ES Establecimiento de salud · CC Comunidad

**Residencia habitual de la madre**  *(rol MADRE)*
- Departamento · Provincia · Distrito

---

## Verificación por lote

- [ ] `caso_sujeto` creada y los casos existentes migrados con rol `CASO_INDICE`
- [ ] Las fichas multi-sujeto separan visualmente a cada sujeto
- [ ] VIH/SIDA y violencia familiar solo visibles para EPIDEMIOLOGO y ADMIN
- [ ] Cada apertura de ficha sensible queda registrada en `caso_bitacora`
- [ ] Los datos del agresor no aparecen en ninguna exportación
- [ ] La ficha de VIH/SIDA usa código de paciente, no nombre completo
- [ ] Muerte materna: el Anexo 2 se habilita sobre el mismo caso del Anexo 1
- [ ] Muerte fetal y neonatal: un caso por fallecimiento
- [ ] Las cuatro demoras se pueden cruzar en reportes
- [ ] `theme.css` sin modificaciones; sin emojis ni librerías externas
