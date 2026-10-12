<?php
/**
 * Anexo 6.2 de la ficha ESAVI severo (Y59.0): "Lista de chequeo de
 * actividades relacionadas a la investigación de ESAVI severo", págs. 7-8 del
 * compendio (transcrito 2026-10-11). No es una ficha de enfermedad sino un
 * cuestionario sobre el vacunatorio y el vacunador, asociado a un caso
 * (decisión del usuario): se guarda en caso_cuestionario y lo valida
 * App\Models\CasoCuestionario contra esta definición.
 *
 * Forma de cada pregunta:
 *   'id'            número del papel (forma la clave: "I.7", "I.7.a")
 *   'texto'         enunciado
 *   'tipo'          si_no | opciones | multiple | texto | textarea | numero | fecha | tabla | titulo
 *                   ("titulo": encabezado con subítems, sin respuesta propia)
 *   'opciones'      [código => etiqueta] (opciones / multiple)
 *   'consideracion' Verifique / Observe / Pregunte / Revise / Especifique
 *   'subitems'      preguntas hijas (se numeran a, b, c...)
 *   'especificar'   ['valor' => código, 'etiqueta' => ...]: texto que se pide
 *                   cuando se elige ese código
 *   'extra'         ['etiqueta' => ...]: texto libre junto a la respuesta
 *   'depende'       ['id' => clave de otra pregunta, 'valores' => [...]]: solo
 *                   se pide (y solo se guarda) con esa respuesta
 *   'filas', 'columnas' (tabla): filas [clave => etiqueta] × N columnas
 */

$siNo = ['SI' => 'Sí', 'NO' => 'No'];
$siNoNoEvaluable = ['SI' => 'Sí', 'NO' => 'No', 'NO_EVALUABLE' => 'No evaluable'];
$siNoDesconocido = ['SI' => 'Sí', 'NO' => 'No', 'DESCONOCIDO' => 'Desconocido'];
$siNoDesconocidoNa = ['SI' => 'Sí', 'NO' => 'No', 'DESCONOCIDO' => 'Desconocido', 'NA' => 'NA'];

return [
    'clave'       => 'lista_chequeo_esavi',
    'titulo'      => 'Lista de chequeo del vacunatorio',
    'subtitulo'   => 'Anexo 6.2 · Lista de chequeo de actividades relacionadas a la investigación de ESAVI severo',
    // Fichas en las que se ofrece (por CIE-10; ningún if por ficha en el código).
    'cie10'       => ['Y59.0'],
    // Aviso: el papel lo pide cuando la investigación apunta a un problema
    // operativo. No bloquea: la visita suele hacerse antes de cerrar la
    // clasificación final.
    'sugerido_si' => ['clave' => 'y59_0_clasificacion_final', 'valores' => ['DEFECTO_CALIDAD', 'ERROR_INMUNIZACION']],
    'aviso'       => 'Corresponde cuando la clasificación final es "defecto en la calidad de la vacuna" o "error en la inmunización".',

    'cabecera' => [
        [
            'titulo' => 'Datos del centro de vacunación',
            'campos' => [
                ['id' => 'fecha', 'texto' => 'Fecha', 'tipo' => 'fecha'],
                ['id' => 'responsables', 'texto' => 'Responsables de la evaluación', 'tipo' => 'texto'],
                ['id' => 'actividad', 'texto' => 'Actividad al momento de vacunación del caso', 'tipo' => 'opciones',
                    'opciones' => ['INTRAMURAL' => 'Intramural', 'EXTRAMURAL' => 'Extramural']],
                ['id' => 'ipress', 'texto' => 'IPRESS/EESS', 'tipo' => 'texto'],
                ['id' => 'consultorio_privado', 'texto' => 'Consultorio privado', 'tipo' => 'texto'],
                ['id' => 'campana', 'texto' => '¿Fue durante campaña?', 'tipo' => 'opciones', 'opciones' => ['NO' => 'No', 'SI' => 'Sí']],
                ['id' => 'lugar', 'texto' => 'Lugar de vacunación', 'tipo' => 'opciones',
                    'opciones' => ['PUESTO_FIJO' => 'Puesto fijo', 'PUESTO_MOVIL' => 'Puesto móvil', 'INSTITUCIONAL' => 'Institucional', 'OTRO' => 'Otro'],
                    'especificar' => ['valor' => 'OTRO', 'etiqueta' => '¿Cuál?']],
                ['id' => 'direccion', 'texto' => 'Dirección del lugar de vacunación', 'tipo' => 'texto'],
            ],
        ],
        [
            'titulo' => 'Datos del vacunador o entrevistado en el vacunatorio',
            'campos' => [
                ['id' => 'vacunador_nombres', 'texto' => 'Nombres y apellidos', 'tipo' => 'texto'],
                ['id' => 'vacunador_contacto', 'texto' => 'Teléfono y correo electrónico', 'tipo' => 'texto'],
                ['id' => 'vacunador_profesion', 'texto' => 'Profesión', 'tipo' => 'texto'],
                ['id' => 'vacunador_centro_laboral', 'texto' => 'Centro laboral', 'tipo' => 'texto'],
                ['id' => 'vacunador_experiencia', 'texto' => 'Experiencia laboral (años y meses)', 'tipo' => 'texto'],
            ],
        ],
    ],

    'indicaciones' => [
        'I'   => 'Desde la sección I a VI, verifique los procedimientos de la vacunación durante la visita realizada al vacunatorio (vacunación intra y extramural) y anote las observaciones. Idealmente observe al personal de salud que realizó la aplicación de la vacuna que generó el evento.',
        'VII' => 'Desde la sección VII en adelante se completará con la información de los aspectos operacionales de la vacuna relacionada al evento.',
    ],

    'secciones' => [
        [
            'id' => 'I', 'titulo' => 'Cadena de frío', 'comentario' => 'Comentario sobre cadena de frío',
            'preguntas' => [
                ['id' => '1', 'texto' => 'Controla la temperatura de la refrigeradora en forma diaria', 'tipo' => 'si_no', 'consideracion' => 'Verifique'],
                ['id' => '2', 'texto' => 'Se encuentra la temperatura dentro de los rangos establecidos, realice la evaluación mínima de 3 meses (+2 a +8 °C)', 'tipo' => 'si_no', 'consideracion' => 'Verifique'],
                ['id' => '3', 'texto' => 'La refrigeradora es exclusiva para el uso de vacunas de la ESNI', 'tipo' => 'si_no', 'consideracion' => 'Verifique'],
                ['id' => '4', 'texto' => 'Las vacunas están correctamente ubicadas', 'tipo' => 'si_no', 'consideracion' => 'Observe'],
                ['id' => '5', 'texto' => 'La refrigeradora está ubicada adecuadamente', 'tipo' => 'si_no', 'consideracion' => 'Observe'],
                ['id' => '6', 'texto' => 'Tiene Data Logger y funciona correctamente', 'tipo' => 'si_no', 'consideracion' => 'Observe'],
                ['id' => '7', 'texto' => 'Cuántas veces abre la refrigeradora al día', 'tipo' => 'titulo', 'consideracion' => 'Pregunte', 'subitems' => [
                    ['texto' => '2 veces (respuesta adecuada)', 'tipo' => 'si_no'],
                    ['texto' => 'Más de 2 veces', 'tipo' => 'si_no'],
                ]],
                ['id' => '8', 'texto' => 'Qué procedimiento realiza cuando no hay fluido eléctrico', 'tipo' => 'si_no', 'consideracion' => 'Pregunte'],
                ['id' => '9', 'texto' => 'Cuenta con grupo electrógeno', 'tipo' => 'si_no', 'consideracion' => 'Pregunte'],
            ],
        ],
        [
            'id' => 'II', 'titulo' => 'Lavado de manos', 'comentario' => 'Comentario sobre prácticas de bioseguridad',
            'preguntas' => [
                ['id' => '1', 'texto' => 'El lavado de manos que practica es', 'tipo' => 'titulo', 'subitems' => [
                    ['texto' => 'Social', 'tipo' => 'si_no', 'consideracion' => 'Observe'],
                    ['texto' => 'Clínico', 'tipo' => 'si_no', 'consideracion' => 'Observe'],
                    ['texto' => 'Quirúrgico', 'tipo' => 'si_no', 'consideracion' => 'Observe'],
                ]],
                ['id' => '2', 'texto' => 'Con qué material realiza el secado de manos', 'tipo' => 'titulo', 'subitems' => [
                    ['texto' => 'Toalla de tela', 'tipo' => 'si_no', 'consideracion' => 'Observe'],
                    ['texto' => 'Papel toalla', 'tipo' => 'si_no', 'consideracion' => 'Observe'],
                ]],
                ['id' => '3', 'texto' => 'Solicita carné de vacunación antes de vacunar', 'tipo' => 'si_no', 'consideracion' => 'Observe'],
                ['id' => '4', 'texto' => 'Con qué frecuencia se lava las manos', 'tipo' => 'titulo', 'subitems' => [
                    ['texto' => 'Antes y después de cada procedimiento', 'tipo' => 'si_no', 'consideracion' => 'Observe'],
                    ['texto' => 'No se lava las manos', 'tipo' => 'si_no', 'consideracion' => 'Observe'],
                ]],
            ],
        ],
        [
            'id' => 'III', 'titulo' => 'Reconstitución del biológico en el caso', 'comentario' => 'Comentario relacionado a la reconstitución del biológico',
            'preguntas' => [
                ['id' => '1', 'texto' => 'Manipula las vacunas, jeringas y agujas según normas de bioseguridad', 'tipo' => 'si_no', 'consideracion' => 'Observe'],
                ['id' => '2', 'texto' => 'Conservación del diluyente', 'tipo' => 'si_no', 'consideracion' => 'Observe'],
                ['id' => '3', 'texto' => 'Observe la técnica de reconstitución del biológico', 'tipo' => 'si_no', 'consideracion' => 'Observe'],
            ],
        ],
        [
            'id' => 'IV', 'titulo' => 'Técnica de aplicación de la vacuna', 'comentario' => 'Comentario relacionado a la técnica en la aplicación de la vacuna',
            'preguntas' => [
                ['id' => '1', 'texto' => '¿La vacuna se mantiene refrigerada?', 'tipo' => 'si_no'],
                ['id' => '2', 'texto' => 'Rotula la hora de apertura del frasco', 'tipo' => 'si_no', 'consideracion' => 'Revise'],
                ['id' => '3', 'texto' => 'Cuál es la técnica de asepsia', 'tipo' => 'titulo', 'subitems' => [
                    ['texto' => 'Circular', 'tipo' => 'si_no', 'consideracion' => 'Observe'],
                    ['texto' => 'De adentro hacia afuera (respuesta correcta)', 'tipo' => 'si_no', 'consideracion' => 'Observe'],
                ]],
                ['id' => '4', 'texto' => 'Cuál es la vía de aplicación de la(s) vacuna(s) investigada(s)', 'tipo' => 'titulo',
                    'extra' => ['etiqueta' => 'Vacuna(s) investigada(s)'], 'subitems' => [
                    ['texto' => 'Vía subcutánea', 'tipo' => 'si_no', 'consideracion' => 'Pregunte'],
                    ['texto' => 'Vía intramuscular', 'tipo' => 'si_no', 'consideracion' => 'Pregunte'],
                    ['texto' => 'Vía intradérmica', 'tipo' => 'si_no', 'consideracion' => 'Pregunte'],
                    ['texto' => 'Vía oral', 'tipo' => 'si_no', 'consideracion' => 'Pregunte'],
                ]],
                ['id' => '5', 'texto' => 'Utiliza para la limpieza de la zona a vacunar', 'tipo' => 'titulo', 'subitems' => [
                    ['texto' => 'Torundas de algodón', 'tipo' => 'si_no', 'consideracion' => 'Observe'],
                    ['texto' => 'Torundas de gasa', 'tipo' => 'si_no', 'consideracion' => 'Observe'],
                ]],
                ['id' => '6', 'texto' => 'Qué antiséptico usa', 'tipo' => 'titulo', 'subitems' => [
                    ['texto' => 'Alcohol puro', 'tipo' => 'si_no', 'consideracion' => 'Observe'],
                    ['texto' => 'Agua', 'tipo' => 'si_no', 'consideracion' => 'Observe'],
                    ['texto' => 'Alcohol yodado', 'tipo' => 'si_no', 'consideracion' => 'Observe'],
                    ['texto' => 'Jabón líquido más agua', 'tipo' => 'si_no', 'consideracion' => 'Observe'],
                    ['texto' => 'Jabón líquido más alcohol puro', 'tipo' => 'si_no', 'consideracion' => 'Observe'],
                    ['texto' => 'Jabón líquido', 'tipo' => 'si_no', 'consideracion' => 'Observe'],
                ]],
                ['id' => '7', 'texto' => 'Masajea el lugar de aplicación de un inmunobiológico', 'tipo' => 'si_no', 'consideracion' => 'Observe'],
                ['id' => '8', 'texto' => 'Cuál es la dosificación de la vacuna', 'tipo' => 'si_no', 'consideracion' => 'Pregunte',
                    'extra' => ['etiqueta' => 'Dosificación']],
            ],
        ],
        [
            'id' => 'V', 'titulo' => 'Vacunas, jeringas y bioseguridad', 'comentario' => 'Comentario de vacunas, jeringas y bioseguridad',
            'preguntas' => [
                ['id' => '1', 'texto' => '¿Tiene vacunas y jeringas suficientes de acuerdo al nivel de EESS?', 'tipo' => 'si_no', 'consideracion' => 'Pregunte y revise'],
                ['id' => '2', 'texto' => '¿Tiene vacunas caducadas?', 'tipo' => 'si_no', 'consideracion' => 'Revise'],
                ['id' => '3', 'texto' => '¿Tiene frascos de vacuna con aguja en la tapa?', 'tipo' => 'si_no', 'consideracion' => 'Revise'],
                ['id' => '4', 'texto' => '¿Desecha las jeringas usadas en un lugar seguro?', 'tipo' => 'si_no', 'consideracion' => 'Revise'],
                ['id' => '5', 'texto' => '¿Tiene termómetro dentro del termo?', 'tipo' => 'si_no', 'consideracion' => 'Revise'],
                ['id' => '6', 'texto' => '¿Tiene Data Logger dentro del termo?', 'tipo' => 'si_no', 'consideracion' => 'Revise'],
                ['id' => '7', 'texto' => '¿Tiene jeringa cargada dentro del termo de vacunas?', 'tipo' => 'si_no', 'consideracion' => 'Revise'],
                ['id' => '8', 'texto' => '¿Técnica correcta para la aplicación de la vacuna?', 'tipo' => 'si_no', 'consideracion' => 'Observe'],
            ],
        ],
        [
            'id' => 'VI', 'titulo' => 'En relación a la consejería', 'comentario' => 'Comentario en relación a la consejería',
            'preguntas' => [
                ['id' => '1', 'texto' => 'Investiga antecedentes de enfermedad, alergias, estado vacunal, si es mujer (si está gestando), si recibe algún tipo de tratamiento', 'tipo' => 'si_no', 'consideracion' => 'Observe'],
                ['id' => '2', 'texto' => 'Indica sobre la vacuna que va a recibir el paciente', 'tipo' => 'si_no', 'consideracion' => 'Observe'],
                ['id' => '3', 'texto' => 'Educa sobre las reacciones de la vacuna', 'tipo' => 'si_no', 'consideracion' => 'Observe'],
            ],
        ],
        [
            'id' => 'VII', 'titulo' => 'Relacionado a inmunobiológicos y diluyente', 'comentario' => 'Comentario',
            'preguntas' => [
                ['id' => '1', 'texto' => 'Número de personas vacunadas por cada antígeno en el puesto de vacunación el día del evento (incluya registro de ser posible)', 'tipo' => 'tabla', 'consideracion' => 'Especifique',
                    'filas' => ['vacuna' => 'Nombre de la vacuna', 'dosis' => 'Número de dosis'], 'columnas' => 4],
                ['id' => '2', 'texto' => 'Número de personas vacunadas con el vial de la vacuna involucrada', 'tipo' => 'numero', 'consideracion' => 'Especifique'],
                ['id' => '3', 'texto' => 'Número de personas vacunadas con el mismo antígeno involucrado el mismo día o jornada', 'tipo' => 'numero', 'consideracion' => 'Especifique'],
                ['id' => '4', 'texto' => 'Número de personas vacunadas con el mismo lote de vacuna en otros lugares', 'tipo' => 'numero', 'consideracion' => 'Especifique'],
                ['id' => '5', 'texto' => 'Momento de vacunación en el ESAVI, según la hora en la jornada', 'tipo' => 'opciones', 'consideracion' => 'Especifique',
                    'opciones' => ['PRIMERAS_HORAS' => 'Primeras horas', 'ULTIMAS_HORAS' => 'Últimas horas', 'DESCONOCIDO' => 'Desconocido']],
                ['id' => '6', 'texto' => 'En caso de viales multidosis, momento en que se administró la vacuna', 'tipo' => 'opciones', 'consideracion' => 'Especifique',
                    'opciones' => ['PRIMERAS_DOSIS' => 'Primeras dosis', 'ULTIMAS_DOSIS' => 'Últimas dosis', 'DESCONOCIDO' => 'Desconocido']],
                ['id' => '7', 'texto' => 'En caso de empleo de diluyente, complete los datos', 'tipo' => 'tabla', 'consideracion' => 'Especifique',
                    'filas' => ['laboratorio' => 'Laboratorio', 'lote' => 'Lote', 'volumen' => 'Volumen del diluyente', 'vencimiento' => 'Fecha de vencimiento'], 'columnas' => 2],
            ],
        ],
        [
            'id' => 'VIII', 'titulo' => 'Aspectos operativos',
            'preguntas' => [
                ['id' => '1', 'texto' => '¿Hubo algún error en la prescripción o no adherencia a las recomendaciones de uso de la vacuna?', 'tipo' => 'opciones', 'opciones' => $siNo],
                ['id' => '2', 'texto' => 'Según la investigación, ¿considera que la vacuna administrada pudo haber estado contaminada?', 'tipo' => 'opciones', 'opciones' => $siNoNoEvaluable],
                ['id' => '3', 'texto' => 'A partir de su investigación, ¿considera que las condiciones físicas de la vacuna (color, turbidez, sustancias extrañas, etc.) eran anormales en el momento de la administración? (Test de agitación)', 'tipo' => 'opciones', 'opciones' => $siNoNoEvaluable],
                ['id' => '4', 'texto' => 'A partir de su investigación, ¿considera que hubo un error en la preparación o reconstitución (producto, vacuna o diluyente equivocado, mezcla, jeringa o llenado inapropiado de la jeringa, etc.) de la vacuna por el vacunador?', 'tipo' => 'opciones', 'opciones' => $siNoNoEvaluable],
                ['id' => '5', 'texto' => 'A partir de su investigación, ¿considera que hubo error en la manipulación de la vacuna (interrupción de la cadena de frío durante el transporte, el almacenamiento o la jornada de vacunación, etc.)?', 'tipo' => 'opciones', 'opciones' => $siNoNoEvaluable],
                ['id' => '6', 'texto' => 'A partir de su investigación, ¿considera que la vacuna se administró de forma incorrecta (dosis equivocada, sitio o ruta de administración, aguja del tamaño equivocado, no seguimiento a buenas prácticas de inyección, etc.)?', 'tipo' => 'opciones', 'opciones' => $siNoNoEvaluable],
                ['id' => '7', 'texto' => '¿Es este caso parte de un conglomerado?', 'tipo' => 'opciones', 'opciones' => $siNoDesconocido,
                    'extra' => ['etiqueta' => 'N.° Id. del conglomerado']],
                ['id' => '7.1', 'texto' => 'Si la respuesta es positiva, ¿cuántos casos adicionales se han detectado en el conglomerado?', 'tipo' => 'numero',
                    'depende' => ['id' => 'VIII.7', 'valores' => ['SI']]],
                ['id' => '8', 'texto' => '¿Recibieron todos los casos del conglomerado la vacuna del mismo vial?', 'tipo' => 'opciones', 'opciones' => $siNoDesconocido],
                ['id' => '8.1', 'texto' => 'Si la respuesta es negativa, enumere los viales usados por el conglomerado de casos', 'tipo' => 'textarea',
                    'depende' => ['id' => 'VIII.8', 'valores' => ['NO']]],
            ],
        ],
        [
            'id' => 'IX', 'titulo' => 'Jeringas y agujas utilizadas',
            'preguntas' => [
                ['id' => '1', 'texto' => 'Se utilizaron jeringas autodesactivables', 'tipo' => 'opciones', 'opciones' => $siNoDesconocido],
                ['id' => '2', 'texto' => 'Si no se usaron, especifique el tipo de jeringas usadas', 'tipo' => 'multiple',
                    'opciones' => ['VIDRIO' => 'Vidrio', 'DESECHABLES' => 'Desechables', 'DESECHABLES_RECICLADAS' => 'Desechables recicladas', 'OTRAS' => 'Otras'],
                    'especificar' => ['valor' => 'OTRAS', 'etiqueta' => '¿Cuáles?'],
                    'depende' => ['id' => 'IX.1', 'valores' => ['NO']]],
                ['id' => '3', 'texto' => 'Especifique los hallazgos clave, las observaciones adicionales o los comentarios', 'tipo' => 'textarea'],
            ],
        ],
        [
            'id' => 'X', 'titulo' => 'Procedimiento de reconstitución',
            'preguntas' => [
                ['id' => '1', 'texto' => '¿Se usó la misma jeringa para la reconstitución de múltiples viales de la misma vacuna?', 'tipo' => 'opciones', 'opciones' => $siNoDesconocidoNa],
                ['id' => '2', 'texto' => '¿Se usó la misma jeringa para la reconstitución de diferentes vacunas?', 'tipo' => 'opciones', 'opciones' => $siNoDesconocidoNa],
                ['id' => '3', 'texto' => '¿Se usó una jeringa distinta para la reconstitución de cada vial de la vacuna?', 'tipo' => 'opciones', 'opciones' => $siNoDesconocidoNa],
                ['id' => '4', 'texto' => '¿Se usó una jeringa distinta para la reconstitución en cada vacunación?', 'tipo' => 'opciones', 'opciones' => $siNoDesconocidoNa],
                ['id' => '5', 'texto' => 'Los diluyentes y las vacunas usadas, ¿son las mismas recomendadas por el fabricante?', 'tipo' => 'opciones', 'opciones' => $siNoDesconocidoNa],
                ['id' => '6', 'texto' => 'Especifique los hallazgos clave, las observaciones adicionales o los comentarios', 'tipo' => 'textarea'],
            ],
        ],
        [
            'id' => 'XI', 'titulo' => 'Cadena de frío y transporte', 'subtitulo' => 'Último punto del almacenamiento',
            'preguntas' => [
                ['id' => '1', 'texto' => '¿Se encuentra monitorizada la temperatura de la refrigeradora ICE LINED o fotovoltaico donde se almacenan las vacunas, con un registro diario a.m. y p.m.?', 'tipo' => 'opciones', 'opciones' => $siNo],
                ['id' => '1.a', 'texto' => 'Si marcó "Sí", ¿hubo alguna desviación del rango +2 a +8 °C después de que la vacuna se introdujera en la refrigeradora ICE LINED o fotovoltaico?', 'tipo' => 'opciones', 'opciones' => $siNo,
                    'depende' => ['id' => 'XI.1', 'valores' => ['SI']]],
                ['id' => '1.b', 'texto' => 'Si marcó "Sí", adjunte los datos del monitoreo por separado (detalle)', 'tipo' => 'textarea',
                    'depende' => ['id' => 'XI.1', 'valores' => ['SI']]],
                ['id' => '2', 'texto' => '¿Se siguió el procedimiento correcto para almacenar los diluyentes y las vacunas?', 'tipo' => 'opciones', 'opciones' => $siNoDesconocido],
                ['id' => '3', 'texto' => '¿Se encuentra otro objeto diferente a las vacunas, paquetes fríos y diluyentes, en la congeladora o la refrigeradora ICE LINED o fotovoltaico?', 'tipo' => 'opciones', 'opciones' => $siNoDesconocido],
                ['id' => '4', 'texto' => '¿Había alguna vacuna reconstituida parcialmente usada en la refrigeradora ICE LINED o fotovoltaico?', 'tipo' => 'opciones', 'opciones' => $siNoDesconocido],
                ['id' => '5', 'texto' => '¿Había alguna vacuna que no pudiese usarse (vencida, sin etiqueta o congelada) en el refrigerador?', 'tipo' => 'opciones', 'opciones' => $siNoDesconocido],
                ['id' => '6', 'texto' => '¿Había en el almacén algún diluyente que no pudiese usarse (vencido, sin ser recomendación del fabricante, roto o sucio)?', 'tipo' => 'opciones', 'opciones' => $siNoDesconocido],
            ],
        ],
        [
            'id' => 'XII', 'titulo' => 'Transporte de la vacuna',
            'preguntas' => [
                ['id' => '1', 'texto' => 'Tipo de termo o de caja fría usados', 'tipo' => 'texto'],
                ['id' => '2', 'texto' => '¿Se envió el termo o la caja fría el mismo día de la vacunación?', 'tipo' => 'opciones', 'opciones' => $siNoDesconocido],
                ['id' => '3', 'texto' => '¿Regresó el termo o la caja fría el mismo día de la vacunación?', 'tipo' => 'opciones', 'opciones' => $siNoDesconocido],
                ['id' => '4', 'texto' => '¿Se usó un paquete frío acondicionado?', 'tipo' => 'opciones', 'opciones' => $siNoDesconocido],
                ['id' => '5', 'texto' => 'Especifique los hallazgos clave, las observaciones adicionales o los comentarios (registre los detalles de la hora de salida y de llegada del termo o de la caja fría de la vacuna, si lo considera relevante)', 'tipo' => 'textarea'],
            ],
        ],
    ],
];
