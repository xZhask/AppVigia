<?php
/**
 * Códigos de zona del PDF (2026-10-16, decisión 3 de HOJA_DE_RUTA.md): el
 * domicilio MINSA de A00 pide, además de "Zona de residencia: Urbana/Rural"
 * (persona.tipo_zona), el tipo de asentamiento ("Zona°", códigos al pie: [1]
 * Urbanización, [2] Villa, [3] Cooperativa, [4] Proyecto municipal de
 * vivienda, [5] PPJJ/AAHH, [6] Otro). A95 y B57 lo piden como "TIPO DE ZONA"
 * junto a "NOMBRE DE ZONA", también en el domicilio anterior (Migración).
 * Nuevo valor TIPO_ASENTAMIENTO en enfermedad.detalle_domicilio.
 *
 * Agrega persona.tipo_asentamiento y persona.anterior_tipo_asentamiento, y
 * copia del manifiesto detalle_domicilio y nucleo_ajustes de A00, A95 y B57
 * (para las bases donde tienen casos y cargar_fichas.php las deja
 * bloqueadas). Los valores de tipo_zona ya guardados no se tocan: la persona
 * es compartida entre fichas. Idempotente.
 */
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$definicion = "ENUM('URBANIZACION','VILLA','COOPERATIVA','PROYECTO_MUNICIPAL','PPJJ_AAHH','OTRO') NULL";
foreach (['tipo_asentamiento' => 'tipo_zona', 'anterior_tipo_asentamiento' => 'anterior_tipo_zona'] as $columna => $despuesDe) {
    if ($pdo->query("SHOW COLUMNS FROM persona LIKE '{$columna}'")->fetchAll()) {
        echo "persona.{$columna} ya existe.\n";
        continue;
    }
    $pdo->exec("ALTER TABLE persona ADD COLUMN {$columna} {$definicion} AFTER {$despuesDe}");
    echo "Agregada persona.{$columna}.\n";
}

$manifiesto = json_decode(file_get_contents(__DIR__ . '/../../manifiesto_fichas.json'), true);
foreach (['A00', 'A95', 'B57'] as $cie10) {
    $ficha = $manifiesto['fichas'][$cie10] ?? null;
    $consulta = $pdo->prepare('SELECT * FROM enfermedad WHERE cie10 = ?');
    $consulta->execute([$cie10]);
    $enfermedad = $consulta->fetch();
    if (!$ficha || !$enfermedad) {
        echo "{$cie10}: no está en esta base; lo deja cargar_fichas.php.\n";
        continue;
    }
    // Igual que cargar_fichas.php: las notas "_..." de nucleo_ajustes no se guardan.
    $declarados = [
        'detalle_domicilio' => $ficha['detalle_domicilio'] ?? null,
        'nucleo_ajustes' => array_filter((array) ($ficha['nucleo_ajustes'] ?? []), fn($ajuste) => !str_starts_with((string) $ajuste, '_'), ARRAY_FILTER_USE_KEY),
    ];
    $cambios = 0;
    foreach ($declarados as $columna => $declarado) {
        if ((json_decode((string) $enfermedad[$columna], true) ?: null) == ($declarado ?: null)) {
            continue;
        }
        $pdo->prepare("UPDATE enfermedad SET {$columna} = ? WHERE id = ?")
            ->execute([$declarado ? json_encode($declarado, JSON_UNESCAPED_UNICODE) : null, $enfermedad['id']]);
        $cambios++;
    }
    echo $cambios === 0 ? "{$cie10}: ya pedía el tipo de asentamiento.\n" : "{$cie10}: {$cambios} ajuste(s) copiados del manifiesto.\n";
}
