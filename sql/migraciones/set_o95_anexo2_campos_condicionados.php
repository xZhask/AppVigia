<?php
/**
 * O95 (2026-10-15): los datos de Anexo 2 que viven en "Datos del
 * fallecimiento (Anexo 1)" pasan a depender de o95_tipo_de_ficha = ANEXO_2,
 * igual que las secciones de Anexo 2. Son los 7 de la tarjeta de identidad
 * (grupo étnico, pueblo étnico, idioma, nivel educativo, estado civil,
 * ocupación, tipo de seguro) y los 3 de "Datos ampliados del fallecimiento"
 * (categoría del EE.SS., fecha y hora de ingreso, responsable de la atención).
 * "Especificar otro idioma/seguro" ya dependen de idioma/seguro y caen en
 * cadena. Antes se guardaban con Anexo 1: bastaba con marcar Anexo 2, llenar
 * y volver a Anexo 1 (los campos ocultos se envían igual).
 *
 * El manifiesto ya lo declara; este script es para las bases donde O95 tiene
 * casos y cargar_fichas.php la deja bloqueada. Fija depende_de y
 * valor_activador en los campo_def existentes (no los recrea) y, en los casos
 * cuya etapa guardada no es ANEXO_2, borra los valores que esos 10 campos y
 * sus "especificar otro" dejaron grabados antes del arreglo: es lo mismo que
 * descartaría el servidor al editar y guardar el caso. Un caso sin etapa
 * guardada cuenta como Anexo 1, igual que en el servidor. Idempotente.
 */
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$claves = [
    'o95_grupo_etnico', 'o95_etnia_pueblo_etnico', 'o95_idioma', 'o95_nivel_educativo',
    'o95_estado_civil', 'o95_ocupacion', 'o95_tipo_de_seguro',
    'o95_categoria_del_ee_ss', 'o95_fecha_y_hora_de_ingreso_al_ee_ss', 'o95_responsable_de_la_atencion',
];

$buscarCampo = $pdo->prepare(
    "SELECT cd.id, cd.clave, cd.depende_de, cd.valor_activador
       FROM campo_def cd
       JOIN seccion_def sd ON sd.id = cd.seccion_id
       JOIN enfermedad e ON e.id = sd.enfermedad_id
      WHERE e.cie10 = 'O95' AND cd.clave = ?"
);

$buscarCampo->execute(['o95_tipo_de_ficha']);
$padre = $buscarCampo->fetch();
if (!$padre) {
    fwrite(STDERR, "No existe o95_tipo_de_ficha en esta base: correr antes cargar_fichas.php.\n");
    exit(1);
}

$actualizar = $pdo->prepare('UPDATE campo_def SET depende_de = ?, valor_activador = ? WHERE id = ?');
$cambiados = 0;
foreach ($claves as $clave) {
    $buscarCampo->execute([$clave]);
    $campo = $buscarCampo->fetch();
    if (!$campo) {
        fwrite(STDERR, "No existe {$clave} en esta base: correr antes cargar_fichas.php.\n");
        exit(1);
    }
    if ((int) $campo['depende_de'] === (int) $padre['id'] && $campo['valor_activador'] === 'ANEXO_2') {
        continue;
    }
    $actualizar->execute([$padre['id'], 'ANEXO_2', $campo['id']]);
    $cambiados++;
}

echo $cambiados === 0
    ? "Los 10 campos de Anexo 2 de O95 ya dependían de o95_tipo_de_ficha.\n"
    : "{$cambiados} campo(s) de O95 ahora dependen de o95_tipo_de_ficha = ANEXO_2.\n";

$clavesALimpiar = array_merge($claves, ['o95_idioma_otra', 'o95_tipo_de_seguro_otro']);
$marcas = implode(',', array_fill(0, count($clavesALimpiar), '?'));
$borrar = $pdo->prepare(
    "DELETE cv FROM caso_valor cv
       JOIN campo_def cd ON cd.id = cv.campo_def_id
       JOIN seccion_def sd ON sd.id = cd.seccion_id
       JOIN enfermedad e ON e.id = sd.enfermedad_id
       LEFT JOIN caso_valor etapa
              ON etapa.caso_id = cv.caso_id
             AND etapa.campo_def_id = ?
             AND etapa.valor = 'ANEXO_2'
      WHERE e.cie10 = 'O95'
        AND cd.clave IN ({$marcas})
        AND etapa.id IS NULL"
);
$borrar->execute([$padre['id'], ...$clavesALimpiar]);
$borrados = $borrar->rowCount();

echo $borrados === 0
    ? "Ningún caso de Anexo 1 tenía datos de Anexo 2 guardados.\n"
    : "Borrados {$borrados} valor(es) de Anexo 2 en casos de O95 que no son de Anexo 2.\n";
