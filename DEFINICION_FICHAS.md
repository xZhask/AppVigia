# VIGÍA · Definición de campos de las fichas MINSA

Cargar las definiciones (`seccion_def` + `campo_def`) de las fichas restantes,
extraídas **literalmente del PDF de fichas MINSA**.

Siguen vigentes las reglas de diseño de `PLAN_CLAUDE_CODE.md`.

> **Ejecutar un lote por sesión.** No intentar los cuatro de una vez.

---

## 0. Corrección previa — búsqueda de arbovirosis

La ficha MINSA de dengue cubre **dengue, chikungunya, zika y otras arbovirosis**
en un solo formulario. Al acortar el nombre a "Dengue y arbovirosis" se perdió la
posibilidad de encontrarla escribiendo "zika" o "chikungunya", que es como el
personal la va a buscar.

**Corrección:** agregar columna `palabras_clave` a `enfermedad` (texto libre,
separado por comas) e incluirla en el filtro del buscador, junto al nombre y al
CIE-10. Sembrar:

| Enfermedad | palabras_clave |
|---|---|
| Dengue y arbovirosis | dengue, chikungunya, zika, arbovirosis, arbovirus |
| Sarampión / rubéola | sarampion, rubeola, febriles eruptivas, exantematicas |
| EDA grave / cólera | colera, diarrea, eda, vibrio |
| Enfermedad de Carrión | bartonelosis, verruga peruana, carrion |
| Viruela del mono | mpox, monkeypox, viruela simica |
| Gestante con VIH y niño expuesto | vih, gestante, transmision vertical |
| Parálisis flácida aguda | pfa, polio, poliomielitis |

Las palabras clave se comparan **sin tildes y sin distinguir mayúsculas**.

---

## 1. Ampliar el motor: tipos de campo faltantes

Varias fichas usan estructuras que el motor actual no soporta. Agregar al ENUM
de `campo_def.tipo`:

- **`GRUPO_SI_NO`** — lista de ítems donde cada uno se responde Sí / No / Ignorado.
  Es el patrón dominante en las fichas MINSA (difteria, fiebre amarilla, Chagas).
- **`SI_NO_FECHA`** — Sí / No más fecha de inicio. Usado en complicaciones y
  signos con fecha (tos ferina, parotiditis).
- **`MATRIZ`** — tabla de filas × columnas con una respuesta por celda.
  Necesario para: fuerza muscular / tono / reflejos por miembro (PFA),
  compromiso de estructuras nariz-boca × eritema/edema/infiltración/úlcera
  (leishmaniasis), y contactos por lugar × N.° de contactos (tos ferina,
  varicela, parotiditis).
  Definir filas y columnas como JSON en una columna nueva `config` de `campo_def`.
- **`CRONOLOGIA`** — línea de tiempo día −10 a +10 respecto al día 0
  (sarampión / rubéola). Si resulta muy costosa, puede sustituirse en una primera
  versión por `SI_NO_FECHA` por síntoma, dejando la cronología para después.

**No cargar ninguna ficha que dependa de un tipo aún no implementado.**

---

## 2. Reutilizar las tablas hijas existentes

Estas estructuras **ya existen** y no deben duplicarse como campos:

| Estructura | Tabla | Fichas que la usan |
|---|---|---|
| Contactos / censo de contactos | `caso_contacto` | tos ferina, difteria, sarampión, mpox, PFA, varicela |
| Muestras y resultados | `caso_muestra` | todas |
| Viajes / lugar probable | `caso_viaje` | fiebre amarilla, sarampión, mpox, Chagas, dengue |
| Antecedentes vacunales | `caso_vacuna` | tos ferina, varicela, parotiditis, difteria, fiebre amarilla, sarampión, tétanos, PFA |

Al renderizar una ficha, mostrar solo las tablas hijas que esa ficha requiere.
Agregar a `enfermedad` cuatro banderas: `usa_contactos`, `usa_muestras`,
`usa_viajes`, `usa_vacunas`.

---

## LOTE 1 · Inmunoprevenibles clásicas

### Tos ferina (A37.0)

**Cuadro clínico**
- Fecha de inicio de síntomas (FECHA, obligatorio)
- Duración en días: tos paroxística (NUMERO)
- Duración en días: tos persistente en ≥1 año (NUMERO)
- Signos y síntomas (SI_NO_FECHA): Tos paroxística (>10 golpes de tos) ·
  Tos persistente (>2 semanas) · Estridor · Vómitos después de la tos ·
  Apnea · Cianosis · Otros

**Tratamiento**
- ¿Paciente recibió antibiótico? (BOOLEANO)
- Antibióticos recibidos (MATRIZ): nombre · dosis · fecha de inicio ·
  vía de administración · N.° de días

**Complicaciones y hospitalización**
- Complicaciones (SI_NO_FECHA): Neumonía · Convulsiones · Encefalopatía ·
  Anorexia · Desnutrición · Deshidratación · Otitis media · Otros
- Hospitalización (BOOLEANO) · N.° historia clínica · Establecimiento ·
  Fecha de hospitalización · N.° de días · Dx de ingreso · Fecha de alta ·
  Dx de egreso
- ¿Presenta alguna comorbilidad? (SELECT: Sí / No / Desconocido) + especificar
- ¿Se diagnosticaron otras infecciones por laboratorio? + especificar + fecha
- Defunción (BOOLEANO) · Fecha de defunción · Causa básica de defunción

**Antecedentes vacunales** → usa `caso_vacuna`
- Dosis Pentavalente: 1.ª / 2.ª / 3.ª (MULTISELECT)
- DPT: 1.er refuerzo / 2.º refuerzo (MULTISELECT)
- Fecha de última dosis · EE.SS. donde se vacunó
- Si es <1 año: ¿la madre fue vacunada con Tdap durante la gestación? + fecha
- Si es gestante: ¿recibió Tdap? + semana de gestación + fecha

**Lugar probable de infección**
- ¿En los últimos 21 días antes del inicio de la tos, viajó? (BOOLEANO) → `caso_viaje`
- ¿Contacto con casos probables o confirmados de tos ferina?
  (SELECT: Sí / No / Desconocido)
- ¿Algún miembro de la familia o persona cercana ha tenido tos por más de
  2 semanas? (BOOLEANO)
- Contactos directos: domiciliarios (NUMERO) · extradomiciliarios (NUMERO)
- Contactos por lugar (MATRIZ): filas = Casa, Nido/guardería, Colegio,
  Universidad/Instituto, Centro de trabajo, Establecimiento de salud, Otro ×
  columnas = N.° contactos, con síntomas, esquema completo, esquema incompleto,
  recibieron vacunación, recibieron antibióticos

**Laboratorio** → usa `caso_muestra`
- Tipo de muestra: Hisopado nasofaríngeo · Aspirado nasofaríngeo
- Tipo de prueba: PCR-RT (Positivo / Negativo / Contaminado / No viable) ·
  Cultivo (B. pertussis / B. parapertussis / B. holmesii / Bordetella sp.)

**Clasificación final**
- Probable · Confirmado por laboratorio · Confirmado por nexo epidemiológico ·
  Descartado

---

### Varicela con complicaciones (B01)

**Cuadro clínico**
- Fecha de inicio de erupción dérmica (FECHA)
- Tipo de lesión dérmica (MULTISELECT): mácula · pápula · vesícula · costra
- Fecha de inicio de fiebre · Temperatura (°C) · N.° de días de duración

**Complicaciones**
- (MULTISELECT): Sobreinfección de piel y partes blandas · Neurológicas ·
  Respiratorias · Hemorrágicas · Otras (especificar)

**Factores de riesgo**
- (MULTISELECT): Inmunosupresión · Asma · Cáncer · Malformación congénita ·
  Gestante · Enf. reumatológica · Enf. cardíaca · Desórdenes metabólicos · Otras

**Hospitalización y egreso**
- Hospitalización (BOOLEANO) · Establecimiento · Fecha · N.° de días
- Condición de egreso (SELECT): Alta médica · Alta voluntaria · Referido ·
  Fallecido → + fecha · referido a · causa de muerte

**Antecedentes de vacunación (antivaricela)** → `caso_vacuna`
- Vacunación contra varicela (BOOLEANO) · N.° de dosis · Fecha de vacunación

**Lugar probable de infección**
- ¿En las últimas 2 a 3 semanas estuvo en contacto con otro caso de varicela?
  (SELECT: Sí / No / Ignorado)
- Contactos por lugar (MATRIZ): Casa, Nido/guardería, Colegio,
  Universidad/Instituto, Centro de trabajo, Establecimiento de salud, Otros ×
  nombre del lugar, dirección, N.° contactos sanos, N.° contactos enfermos
- ¿Tuvo contacto con gestante? (BOOLEANO) + fecha + semanas de gestación

**Laboratorio** (solo casos complicados) → `caso_muestra`
- Tipo de muestra: Suero · Hisopado de vesícula

---

### Parotiditis con complicaciones (B26)

**Cuadro clínico**
- Fecha de inicio de síntomas (FECHA)
- ¿Presentó inflamación de glándulas parótidas? (BOOLEANO)
- Fecha de inicio de parotiditis · N.° de días de duración
- Localización (SELECT): Unilateral · Bilateral
- Inflamación de otras glándulas salivales: Submandibulares (BOOLEANO) ·
  Sublinguales (BOOLEANO)

**Complicaciones** (SI_NO_FECHA)
- Orquitis · Ooforitis · Pérdida de audición · Encefalitis · Meningitis · Otras

**Hospitalización y egreso** — igual que varicela

**Antecedentes de vacunación (SPR)** → `caso_vacuna`
- Vacunación con SPR (BOOLEANO) · N.° de dosis · Fecha de última dosis ·
  EE.SS. donde se vacunó

**Lugar probable de infección**
- ¿En las últimas 2 a 4 semanas estuvo en contacto con otro caso de parotiditis?
  (SELECT: Sí / No / Ignorado)
- Contactos por lugar (MATRIZ): Casa, Nido/guardería, Colegio,
  **Escuela militar/policial**, Universidad/Instituto, Centro de trabajo,
  Establecimiento de salud, Otros × nombre, dirección, contactos sanos, enfermos
- ¿Tuvo contacto con gestante? + fecha + trimestre de gestación

---

### Difteria (A36)

**Clasificación en la captación**
- (SELECT): Confirmado · Probable · Sospechoso

**Signos y síntomas** (GRUPO_SI_NO — Sí / No / Ignorado)
- Fecha de inicio (FECHA) · Temperatura °C (NUMERO)
- Fiebre o sensación de alza térmica · Dolor de garganta o al deglutir ·
  Faringitis · Laringitis · Amigdalitis · Aumento de volumen en cuello · Tos ·
  Secreción nasal (mucosa o sanguinolenta) · Lesión cutánea ulcerosa · Disnea
- Presencia de placa (seudomembrana) — GRUPO_SI_NO: Orofaringe · Nasal ·
  Traqueobronquial · Otros

**Evolución**
- Hospitalizado (SELECT: Sí / No / Ignorado)
- ¿Antibiótico antes del ingreso? (BOOLEANO) + especificar
- Hospital · Fecha de hospitalización
- Tratamiento recibido (MULTISELECT): Antibiótico · Antitoxina + especificar
- Egreso del hospital (SELECT): Recuperado · Referido · Falleció · Con secuela
- Fecha de alta · Fecha de defunción
- Complicaciones (MULTISELECT): Cardíacas · Neurológicas · Otros

**Información epidemiológica**
- Lugar probable de infección (10 días previos, incluye viajes) → `caso_viaje`
- ¿Estuvo en contacto con un posible caso de difteria? (Sí / No / Ignorado)
- ¿Sabe si hay casos similares en la zona? (Sí / No / Ignorado)
- Aislamiento domiciliario (Sí / No / Ignorado) + fecha de aislamiento

**Vacunación contra difteria** → `caso_vacuna`
- Vacunación (BOOLEANO) · N.° de dosis (1.ª / 2.ª / 3.ª) ·
  Refuerzos (1.º / 2.º) · Fecha de última dosis

**Censo de contactos domiciliarios** → `caso_contacto`
- Nombres y apellidos · Edad · Sexo · Vacunado (Sí/No/Ignorado) ·
  Profilaxis (Sí/No) · Fecha

**Laboratorio** → `caso_muestra`
- Fecha de toma · Tipo de muestra: Hisopado · Membrana
- Cultivo + fecha de resultado · PCR + fecha de resultado
- ¿Recibió antibiótico? · Clasificación final: Confirmado · Descartado

---

## LOTE 2 · Metaxénicas y zoonóticas

### Fiebre amarilla (A95)

**Cuadro clínico** (GRUPO_SI_NO con fecha — Sí / No / Ignorado)
- Fiebre · Ictericia · Pulso lento en relación a la fiebre · Hemorragia nasal ·
  Melena / hematemesis · Petequias · Diarreas · Hipertensión · Oliguria ·
  Proteinuria · Coluria · Hepatomegalia

**Migración**
- Tiempo que reside en domicilio actual: años (NUMERO) · meses (NUMERO)
- Si reside menos de 6 meses, ¿dónde vivía anteriormente? (TEXTO)
- Localidades visitadas en los últimos 10 días (TEXTAREA)

**Antecedentes epidemiológicos**
- Casos reportados en los últimos 10 días (GRUPO_SI_NO): en los lugares
  visitados · en su comunidad · en su casa · epizootias
- Cuántas personas viven en su casa (NUMERO)
- ¿Viajó en los últimos 6 meses? (BOOLEANO) → `caso_viaje`

**Hospitalización**
- Hospitalizado (BOOLEANO) · Fecha · Hospital · N.° H.C.
- Tiempo de enfermedad al momento de la hospitalización (días)
- Tiempo de traslado desde el domicilio (horas / minutos)
- Dx de ingreso 1 · Dx de ingreso 2
- Condición de egreso (SELECT): Alta/Recuperado (+Dx +fecha) ·
  Fallecido (+necropsia Sí/No, Dx macroscópico, Dx microscópico, fecha)

**Antecedente de vacuna antiamarílica** → `caso_vacuna`
- Vacunado (Sí / No / Ignorado) · EE.SS. donde fue vacunado ·
  N.° de dosis recibidas · Fecha de última dosis

**Laboratorio** → `caso_muestra`
- Tipos: Biopsia · Serología · Hígado · Cultivos
- Muestra adecuada / inadecuada + especificar

**Clasificación final**
- Confirmado · Descartado
- Criterio (MULTISELECT): Laboratorio · Anatomía patológica · Clínica
- Dx de descarte (TEXTO)

---

### Leishmaniasis (B55)

**Antecedente epidemiológico**
- Lugar de contagio: localidad · distrito · provincia · departamento
- Tiempo de permanencia en el lugar de contagio (días/meses/años)
- Actividad que desarrolló durante el contagio (SELECT): Agricultura ·
  Extracción de madera · Extracción de oro · Estudiante · Industria petrolera ·
  Comerciante · Fuerzas Armadas · Biólogo/investigación · Turismo · Su casa · Otros
- ¿Existen otras personas con lesiones similares en su vivienda o localidad?

**Datos clínicos**
- Síntomas (MULTISELECT): Dolor en la lesión · Fiebre · Prurito local ·
  Tupidez nasal · Disfonía leve · Disfonía moderada · Disfonía grave ·
  Dificultad respiratoria leve / moderada / severa · Tos · Pérdida de peso
- Antecedente de otras enfermedades (MULTISELECT): TBC · VIH · Chagas · Otras
- Alergia a medicinas (BOOLEANO) + especificar
- Fecha de última regla · MAC usado · Medicinas usadas actualmente

**Lesiones cutáneas**
- N.° de lesiones activas (NUMERO) · N.° de cicatrices (NUMERO)
- Lesiones (MATRIZ): fecha de inicio · tipo (1 Úlcera / 2 Nódulo /
  3 Verrugosa / 4 Cicatriz) · localización (1 Cabeza / 2 Miembro superior /
  3 Miembro inferior / 4 Torso / 5 Pelvis) · ganglios (Sí/No) ·
  infección (Sí/No) · diámetros (mm) · superficie (mm²)

**Enfermedad mucosa**
- Enfermedad mucosa (BOOLEANO) · Fecha de inicio de síntomas ·
  Tiempo (años / meses)
- Compromiso de estructuras (MATRIZ):
  filas = Nariz (narinas, 1/3 anterior, septo nasal, cornetes) y
  Boca (labios, arcada, paladar, úvula, faringe, epiglotis, cuerdas vocales, otros)
  × columnas = Eritema · Edema · Infiltración · Úlcera · N.° de lesiones

**Signos de leishmaniasis visceral** (MULTISELECT)
- Hepatomegalia · Esplenomegalia · Adenomegalia · Anemia · Pérdida de peso ·
  Anorexia · Adenopatías · Epistaxis · Hemorragia gingival ·
  Debilidad progresiva · Desnutrición · Edema · Alteraciones de la piel · Ascitis

**Laboratorio** → `caso_muestra`
- Frotis directo · Cultivo · Histopatología · IDR · ELISA · PCR

**Diagnóstico**
- Forma (SELECT): Cutánea · Mucosa · Visceral
- Situación (SELECT): Primer episodio · Reinfección · Recaída ·
  Falla al tratamiento
- Tratamiento (SELECT): Adecuado · Inadecuado

---

### Enfermedad de Chagas (B57)

**Antecedentes epidemiológicos**
- Lugar probable de contagio (dpto / prov / dist / localidad)
- Fecha probable de contagio · Tiempo de permanencia (días/meses/años)
- ¿Existe "chirimacha" o chinche en su casa? (Sí / No / Ignorado)
- ¿Ha sido picado por una "chirimacha" o chinche? (Sí / No / Ignorado) + fecha
- ¿Ha recibido transfusión sin control para Chagas? (Sí / No / Ignorado) +
  cuántas veces + fecha de la última transfusión
- ¿Antecedente de madre seropositiva? (Sí / No / Ignorado)
- ¿Otra persona con cuadro similar en la casa o lugar de contagio?
- Posible forma de transmisión (SELECT): Vectorial · Transfusional · Vertical

**Migración** → `caso_viaje`
- Tiempo que reside en domicilio actual · Dónde vivía anteriormente ·
  Localidades visitadas últimos 10 días · ¿Viajó últimos 6 meses?

**Cuadro clínico**
- Condición (SELECT): Sintomático · Asintomático
- Fecha de inicio de síntomas
- Etapa aguda (GRUPO_SI_NO): Fiebre · Miocarditis · Chagoma de inoculación ·
  Signo de Romaña · Hepatomegalia · Esplenomegalia · Mialgias ·
  Meningoencefalitis · Malestar general
- Etapa crónica (GRUPO_SI_NO): Palpitaciones · Arritmia · Dolor precordial ·
  Hepatomegalia · Disfagia · Regurgitación · Taquicardia · Disnea · Edema ·
  Soplo · Tos · Odinofagia

**Hospitalización** — mismo patrón que fiebre amarilla
**Tratamiento** — recibió tratamiento (BOOLEANO)

**Laboratorio** → `caso_muestra`
- Sangre: Gota fresca · Cultivo · Microhematocrito
- Suero: ELISA · HAI · IFI

**Clasificación final**
- Chagas agudo / crónico / congénito × Confirmado / Descartado + fecha

---

### Enfermedad de Carrión (A44)

**Antecedente epidemiológico**
- Viaje a localidades o comunidades vecinas (fecha, lugar, tiempo de
  permanencia) → `caso_viaje`
- Fecha de inicio de enfermedad · Fecha de ingreso al estudio o diagnóstico

**Síntomas** (MULTISELECT, lista completa de la ficha)
- Fiebre · Palidez · Cefalea · Malestar general · Mialgias · Dolor articular ·
  Astenia · Prurito · Petequias · Equimosis · Escalofríos · Mareos · Verrugas ·
  Lumbalgia · Náuseas · Vómitos · Hiporexia · Dolor abdominal · Hematoquesia ·
  Melena · Diarrea · Ictericia · Disuria · Polaquiuria · Coluria ·
  Epigastralgia · Somnolencia · Polipnea · Tos · Expectoración ·
  Dolor torácico · Disnea · Cianosis · Convulsiones · Inyección conjuntival ·
  Epistaxis · Congestión faríngea · Odinofagia · Fotofobia ·
  Excitación psicomotriz

**Funciones vitales**
- Temperatura (°C) · Presión arterial · Frecuencia respiratoria · Pulso ·
  Peso (kg)

**Signos generales**
- Lúcido · Orientado en tiempo · en espacio · en persona (GRUPO_SI_NO)
- Estado general · Estado de nutrición · Estado de hidratación
  (SELECT: Bueno / Regular / Malo)

**Piel**
- Palidez (SELECT): Leve · Moderada · Severa
- Petequias (BOOLEANO) + localización · Equimosis (BOOLEANO) + localización
- Lesiones eruptivas (MATRIZ): filas = Miliares, Mulares, Nodulares ×
  columnas = N.°, Cara, Cuello, Tronco, Ext. superior, Ext. inferior, Sangrante

**Tejido celular subcutáneo**
- Sin alteraciones / Edema → localización (MULTISELECT): Miembros inferiores ·
  Miembros superiores · Palpebral · Lumbosacro · Otro

**Ganglios linfáticos** (MATRIZ)
- Filas = Axilares, Inguinales, Cervicales, Epitrocleares ×
  columnas = N.°, tamaño (mm), móviles (Sí/No), dolorosos (Sí/No)

**Laboratorio y evolución** → `caso_muestra`
- Hemoglobina · Hematocrito · Transfusiones (U) · Frotis · Hemocultivo
- Antibióticos usados (MULTISELECT): Penicilina · Cloranfenicol · Rifampicina ·
  Ciprofloxacina · Eritromicina · Cotrimoxazol · Ceftriaxona · Otros

**Hospitalización**
- Hospitalizado (BOOLEANO) · Fecha · Días de hospitalización
- Condición de alta (SELECT): Curado · Mejorado · Transferido ·
  Alta voluntaria · Fallecido

---

### Viruela del mono / Mpox (B04X)

> **Nota de privacidad.** Esta ficha incluye campos sobre orientación sexual,
> prácticas sexuales y estado de VIH porque así lo exige el formato oficial
> MINSA. Marcar estos campos como **sensibles** en `campo_def` (nueva columna
> `sensible TINYINT(1)`): no deben aparecer en listados, reportes agregados ni
> exportaciones, y su acceso queda restringido a los roles EPIDEMIOLOGO y ADMIN.
> Registrar en `caso_bitacora` cada consulta a una ficha con campos sensibles.

**Datos del paciente (adicionales)**
- Población específica (MULTISELECT): HSH · Mujer transgénero ·
  Trabajador(a) sexual · Privado de la libertad · Personal de salud · Otro
- Orientación sexual (SELECT, sensible): Heterosexual · Bisexual · Homosexual · Otra

**Lugar probable de infección y exposición**
- ¿En los últimos 21 días antes del inicio del sarpullido viajó? → `caso_viaje`
- Lugares a los que asistió (MULTISELECT): Discoteca · Sauna · Bar ·
  Club sexual · Evento masivo · Fiesta · EE.SS. · Otro · Ninguno
- Exposiciones en los últimos 21 días (MULTISELECT, sensible):
  Relaciones sexuales con desconocido(a) o parejas múltiples ·
  con trabajador(a) sexual · con su pareja (con exantema o lesiones) ·
  con su pareja (sin molestias clínicas) ·
  Contacto con personas con exantemas o lesiones en piel ·
  Brindó cuidados de un caso probable o confirmado en domicilio ·
  Manipuló material contaminado en EE.SS. ·
  Se realizó procedimiento médico o de laboratorio ·
  Se realizó tatuaje, piercing o acupuntura ·
  Compartió jeringas · Otros · Ninguno
- ¿Exposición con caso probable o confirmado? (Sí / No / Desconocido) →
  `caso_contacto` con tipo de exposición 1-6
- ¿Contacto directo y frecuente con animales? (BOOLEANO) + especificar
  (Perro · Gato · Mono · Aves · Roedores · Otros)

**Antecedentes** (sensibles)
- Estado inmunológico deprimido (Sí / No / Desconocido) + por enfermedad /
  por medicación
- Infección VIH (Sí / No / Desconocido) + fecha de diagnóstico + recibe TAR +
  último recuento CD4 + fecha
- ITS en los últimos 12 meses (MULTISELECT): Chlamydia · Gonorrea ·
  Herpes genital · Sífilis · Verrugas genitales · Otros
- Comorbilidades (MULTISELECT): Tuberculosis · COVID-19 · Otros
- Para nacidos hasta 1978: ¿cicatriz por vacuna variólica? (Sí/No/Desconocido)
- ¿Recibió vacuna contra la viruela? + dosis 1 y 2 (fecha + país) → `caso_vacuna`

**Cuadro clínico**
- Fecha de inicio de síntomas (FIS) · Fecha de inicio del exantema agudo
- Signos y síntomas (MULTISELECT): Fiebre (>38,5 °C) · Escalofríos · Cefalea ·
  Astenia · Mialgia · Dolor de espalda · Dolor de garganta · Exantema/lesión ·
  Linfadenopatía localizada (+lugar) · Linfadenopatía generalizada ·
  Proctitis (dolor o sangrado anal) · Otros
- Distribución del sarpullido (SELECT): Localizado · Generalizado
- Secuencia de aparición por zona (MATRIZ): Genital/perianal · Oral ·
  Cara · Tórax/espalda · Abdomen · Extremidades superiores ·
  Extremidades inferiores · Palma de mano → N.° de orden
- Número de lesiones (SELECT): 1 a 10 · 11 a 25 · 26 a 99 · 100 a más
- Estadio de los exantemas (MULTISELECT): Mácula · Pápula · Vesícula ·
  Pústula · Costra
- Tipo de presentación (SELECT): Monomórfico · Polimórfico

**Hospitalización**
- Hospitalizado + fechas de ingreso y egreso + hospital + motivo +
  Dx de egreso 1 y 2
- UCI (BOOLEANO) + fecha + hospital + motivo
- Defunción (BOOLEANO) + fecha + clasificación
- Alta clínico-epidemiológica + fecha

**Laboratorio** → `caso_muestra`
- Hisopado de lesión dérmica · Piel esfacelada o costra ·
  Hisopado nasofaríngeo/orofaríngeo

**Clasificación**
- Sospechoso · Probable · Confirmado · Descartado

---

## LOTE 3 · Transmisión hídrica, tétanos y PFA

### EDA grave / cólera (A00)

**Antecedentes epidemiológicos — fuente de infección**
- ¿De dónde obtuvo el agua en los últimos 3 días? (SELECT): Caño dentro de su
  casa · Caño público · Pozo · Río · Puquial (manantial) · Camión cisterna ·
  Embotellada · Otro
- ¿Almacena agua de consumo doméstico? (BOOLEANO)
- ¿En qué tipo de recipiente? (SELECT): Tanque elevado · Cilindro ·
  Tanque bajo · Otro
- Nivel de cloro verificado con comparador (TEXTO)
- ¿Los recipientes tienen tapa? (BOOLEANO)
- ¿Dónde consumió alimentos los últimos 3 días? (MULTISELECT): Solo preparados
  en casa · Restaurante · Ambulante · Pensión · Mercado · Otro
- Para menores de 2 años (MULTISELECT): Ingiere leche en biberón · Consume los
  mismos alimentos que los adultos · Recibe lactancia materna
- Eliminación de excretas (SELECT): Red pública dentro de la vivienda ·
  Red pública fuera de la vivienda · Pozo negro/ciego/letrina · Sin servicio · Otro
- ¿Algún miembro de la familia con diarrea en los últimos 3 días? (BOOLEANO)

**Cuadro clínico**
- Síntomas (MULTISELECT): Diarrea · Dolor abdominal · Náuseas · Vómitos ·
  Artralgias · Fiebre · Cefalea · Malestar general · Calambres
- Fecha de inicio de la diarrea · N.° de días de duración
- Consistencia de la deposición (SELECT): Acuosa o líquida · Grumosa · Pastosa
- Tipo de diarrea (SELECT): EDA acuosa · EDA disentérica · EDA persistente
- Presencia de (SELECT): Moco · Sangre · Moco y sangre
- N.° de deposiciones por día (NUMERO)
- Clasificación de la diarrea (SELECT): Sin deshidratación ·
  Con deshidratación leve · moderada · grave · Shock

**Tratamiento**
- Plan de tratamiento (SELECT): A · B · C
- Tratamiento antibiótico (BOOLEANO) + antibiótico usado (SELECT):
  Tetraciclina · Cotrimoxazol · Doxiciclina · Ciprofloxacina · Cloranfenicol · Otro

**Evolución**
- Alta (BOOLEANO + fecha) · Hospitalizado (BOOLEANO + fecha)
- Complicaciones (MULTISELECT): Shock hipovolémico · Acidosis ·
  Insuficiencia renal · Edema agudo de pulmón
- Fallecido (BOOLEANO + fecha + hora) + lugar (EE.SS. / casa)
- Transferencia (BOOLEANO): para hospitalización · para diálisis

**Laboratorio** → `caso_muestra`
- Muestras: Heces · Suero · Vómitos
- Cultivo · Serogrupo: O1 · O139 · Serotipo: Ogawa · Inaba · Hikojima
- Otro microorganismo aislado (TEXTO)

**Clasificación**
- Sospechoso · Probable · Confirmado · Compatible · Descartado

---

### Tétanos (A35)

**Cuadro clínico**
- Fecha de inicio de lesión · Fecha de inicio de síntomas · No recuerda día
- Herida (SELECT): Única · Múltiple
- Tipo de herida (SELECT): Superficial · Profunda
- Causa de la herida (TEXTO) · Lugar de la herida (TEXTO)
- Signos y síntomas (GRUPO_SI_NO — Sí / No / Ignorado): Fiebre ·
  Trismus (no succiona) · Risa sardónica · Convulsiones (espasmos) ·
  Opistótonos · Onfalitis · Ictericia
- Complicaciones (TEXTAREA)

**Atención**
- Paciente atendido por (SELECT): Médico · Enfermera · Técnico sanitario · Otro
- Hospitalizado (BOOLEANO) + fecha + hospital + N.° H.C. + tiempo de
  hospitalización (días)
- Condiciones de alta + fecha de alta
- Fallecido (Sí / No / Ignorado) + fecha de defunción

**Antecedente epidemiológico**
- Lugar probable de infección: distrito · dirección · localidad

**Vacunas con toxoide tetánico** → `caso_vacuna`
- Documentado por carnet (BOOLEANO) · Dosis 1D · 2D · 3D · 4D · 5D + fechas ·
  Fecha de última dosis

**Diagnóstico definitivo**
- Confirmado · Descartado

---

### Tétanos neonatal (A33)

**Cuadro clínico**
- Fecha de inicio de síntomas
- ¿Succión normal durante los 2 primeros días de vida? (BOOLEANO)
- ¿Llanto normal durante los 2 primeros días de vida? (BOOLEANO)
- Signos y síntomas (GRUPO_SI_NO): Fiebre · Trismus (no succiona) ·
  Risa sardónica · Convulsiones (espasmos) · Opistótonos · Onfalitis · Ictericia
- Complicaciones (TEXTAREA)
- Atendido por · Hospitalizado + fecha + hospital + H.C.
- Condiciones de alta + fecha · Fallecido + fecha de defunción

**Datos de la madre**
- Nombres y apellidos · Edad
- N.° de embarazos · N.° de partos ·
  N.° de hijos fallecidos antes de cumplir 28 días
- Fecha de último parto
- Grado de instrucción (SELECT): Analfabeta · Primaria · Secundaria · Superior
- N.° de consultas prenatales (NUMERO)

**Atención del parto**
- Atendido por (SELECT): Médico · Obstetriz · Enfermera · Técnico sanitario ·
  Partera · Otro
- Instrumento utilizado para cortar el cordón umbilical (TEXTO)
- Tratamiento aplicado al muñón umbilical en el domicilio (TEXTAREA)
- Lugar del parto: establecimiento de salud (TEXTO)

**Vacunas con toxoide tetánico (madre)** → `caso_vacuna`
- Documentado con carnet · Dosis 1.ª a 5.ª · Fecha de última dosis ·
  Lugar de aplicación

**Diagnóstico definitivo**
- Confirmado · Descartado

---

### Parálisis flácida aguda — PFA (A80)

**Cuadro clínico**
- Fecha de inicio de síntomas generales (pródromos) · Semana epidemiológica
- Fecha de la fiebre al inicio · Fecha de la deficiencia motora
- N.° de días con parálisis / tiempo en que se instaló totalmente
- Progresión de la parálisis (SELECT): Ascendente · Descendente · Mixta
- Síntomas (GRUPO_SI_NO): Tos · Fiebre · Estreñimiento · Vómitos · Diarrea
- Signos (GRUPO_SI_NO): Dolor muscular · Cefalea · Meníngeo
- Características (GRUPO_SI_NO — Sí / No / Ignorado): Paresia · Parálisis ·
  Pares craneales · Flacidez · Súbita · Asimetría · Sensibilidad ·
  Parestesia · Babinski
- Breve descripción de sensorio, marcha y parálisis (TEXTAREA)

**Examen físico** (MATRIZ)
- Fecha de examen físico · Realizado por · Dx inicial 1 · Dx inicial 2
- Fuerza muscular / Tono muscular / Reflejos (MATRIZ):
  filas = Miembro superior izquierdo, Miembro superior derecho,
  Miembro inferior izquierdo, Miembro inferior derecho, Músculos cervicales ×
  columnas = DIM · AUS · NORM · IGN
- Músculos respiratorios (BOOLEANO) · Músculos cervicales (BOOLEANO) ·
  Cara (lado derecho / izquierdo)
- Signos de irritación meníngea (MATRIZ): Rigidez de nuca · Kerning ·
  Brudzinski · Lasegue × AUS · PRES · IGN

**Hospitalización y defunción**
- Hospitalización + fecha + hospital + servicio + N.° H.C. + N.° cama + ciudad
- Fallecido + fecha + causa + informe de necropsia

**Antecedentes de vacuna antipolio** → `caso_vacuna`
- Vacunado (Sí / No / Ignorado) · N.° de dosis recibidas ·
  Verificada con carné · Fecha de última dosis · Establecimiento

**Laboratorio** → `caso_muestra`
- Heces 1 y Heces 2: fecha de obtención · fecha de envío al INS ·
  fecha de resultado · agente aislado

**Fuente probable de infección**
- Viajes en los 30 días antes del inicio de la deficiencia motora → `caso_viaje`
- Visitas recibidas en los 30 días antes (BOOLEANO + de dónde)
- ¿Existen otros casos semejantes en el área? (Sí / No / No sabe)

**Seguimiento de secuelas** (MATRIZ — 30 / 60 / 90 / 180 días)
- Evaluación del trofismo: fuerza muscular · tono muscular · atrofia ·
  sensibilidad, por segmento corporal
- Evaluación de reflejos por segmento · Babinski · músculos respiratorios

**Evaluación final de secuelas**
- Tipo (SELECT): Ausente · Mínima · Media · Grave
- Localización (MULTISELECT): MSI · MSD · MII · MID · Cara ·
  Músculos cervicales · Músculos respiratorios
- Electromiografía: realizado por · fecha · conclusión

**Clasificación final**
- Polio salvaje · Polio derivado de la vacuna · Polio asociado a la vacuna ·
  Polio compatible · Descartado
- Criterio (MULTISELECT): Laboratorio · Defunción · Con parálisis residual ·
  Sin parálisis residual
- Se ha descartado (MULTISELECT): SGB · Neuritis traumática ·
  Mielitis transversa · Tumor · Desconocido · Otro

---

## LOTE 4 · Sarampión / rubéola  (la más extensa)

### Sarampión / rubéola / otras febriles eruptivas (B05)

**Cuadro clínico**
- Fecha de inicio de fiebre · ¿Fiebre cuantificada? (BOOLEANO) ·
  N.° de días de duración · Temperatura (°C)
- Erupción cutánea (BOOLEANO) · Fecha de inicio de erupción ·
  N.° de días de duración
- Estado general (SELECT): Bueno · Regular · Malo
- Signos (GRUPO_SI_NO): Tos · Coriza o rinorrea · Conjuntivitis ·
  Manchas de Koplik · Adenopatía cervical · Adenopatía retroauricular ·
  Artralgias · Otros
- Descripción de la erupción cutánea (TEXTAREA)
- Complicaciones (GRUPO_SI_NO): Otitis media · Convulsiones · Neumonía ·
  Trombocitopenia · Diarrea · Encefalitis · Otras
- Hospitalizado + fecha + EE.SS. + N.° H.C.
- Fallecido + fecha + causa básica de defunción

**Cronología de signos y síntomas** (CRONOLOGIA, día −10 a +10)
- Erupción (día 0) · Fiebre · Tos · Conjuntivitis · Coriza o rinorrea ·
  Manchas de Koplik · Adenopatía retroauricular · Adenopatía cervical ·
  Artralgias · Otros

**Antecedentes vacunales** → `caso_vacuna`
- Estado vacunal (SELECT): Vacunado · Vacunado incompleto · No vacunado ·
  Ignorado · No corresponde · Sin evidencia
- Tipo de vacuna (SELECT): Antisarampionosa · Antirrubeólica ·
  Doble viral (SR) · Triple viral (SRP)
- N.° de dosis · Fecha de última dosis · N.° de lote
- Fuente de información (SELECT): Carné de vacunación · Registro en servicio
- EE.SS. donde se vacunó

**Antecedentes epidemiológicos**
- Captación del caso (SELECT): Consulta · Laboratorio ·
  Búsqueda activa institucional · Búsqueda activa comunitaria ·
  Investigación de contactos · Casos reportados en comunidad · Otros
- ¿El caso es contacto de otro caso conocido? + código del caso
- ¿Tuvo contacto con gestante en las primeras 20 semanas? + nombre + fecha

**Lugar probable de infección** (7 a 30 días antes de la erupción)
- Contacto con (MULTISELECT): Extranjeros · Visitó establecimiento de salud ·
  Recibió visitas en casa · Asistió a celebraciones masivas · Otros
- ¿Viajó entre 7 y 30 días antes del inicio de la erupción? → `caso_viaje`
- Longitud y latitud del domicilio (TEXTO)

**Laboratorio** → `caso_muestra`
- 1.ª y 2.ª muestra: Suero · Hisopado nasal y faríngeo · Orina
- Por muestra: fecha de toma · fecha de envío LRR/LR a INS ·
  fecha de recepción INS · PCR · genotipo · IgM · IgG + fechas de resultado

**Cadena de transmisión** → `caso_contacto`
- Nombre · edad · dirección · celular · fecha del contacto · lugar de contacto ·
  fecha de inicio de erupción · fecha de vacunación ·
  ¿vacunado dentro de 72 horas del contacto?

**Investigación epidemiológica**
- Búsqueda activa institucional (BOOLEANO) + total de Dx revisados +
  casos que ya existían + casos nuevos ingresados
- Búsqueda activa comunitaria (BOOLEANO) + casas abiertas / cerradas /
  abandonadas / total
- ¿Se realizó bloqueo vacunal? + fecha de inicio + fecha de término +
  localidades + número de vacunados
- ¿Se realizó monitoreo rápido de coberturas (MRC)? + porcentaje de vacunados
- ¿Hubo casos reportados de sarampión en los últimos 30 días en su jurisdicción?

**Clasificación final**
- Clasificación (SELECT): Sarampión · Rubéola · Descartado
- Criterio de confirmación (SELECT): IgM indirecta (+) ·
  Seroconversión de IgG indirecta · PCR (+)
- Criterio de descarte (SELECT): Sarampión/rubéola IgM negativo ·
  Reacción vacunal · Dengue · Parvovirus B19 · Herpes 6 · Reacción alérgica ·
  Zika · Otros
- Clasificación según fuente de infección (SELECT): Importado ·
  Relacionado a importación · Fuente desconocida · Local o autóctono
- Fecha de clasificación final · Clasificado por

---

## Fuera de alcance por ahora

Las fichas de los grupos B, C y D (gestante con VIH, VIH/SIDA, sífilis materna
y congénita, SRC, ESAVI severo, violencia familiar, accidentes de tránsito,
muerte materna, muerte fetal y neonatal) **no se cargan todavía**: requieren
modelar más de un sujeto por caso o estructuras que el motor aún no soporta.
Se abordarán en una fase posterior, después de validar los cuatro lotes.

---

## Verificación por lote

- [ ] Cada ficha se renderiza completa y se guarda en `caso_valor`
- [ ] Las tablas hijas aparecen solo en las fichas que las usan
- [ ] Los campos obligatorios se validan en el servidor
- [ ] La clasificación final usa las opciones propias de cada ficha, no las
      genéricas
- [ ] Los campos marcados como sensibles no aparecen en listados ni reportes
- [ ] Buscar "zika" o "chikungunya" encuentra la ficha de dengue
- [ ] `theme.css` sin modificaciones; sin emojis ni librerías externas
