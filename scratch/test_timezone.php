<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

echo "PHP timezone: " . date_default_timezone_get() . "\n";
echo "Fecha PHP (date('Y-m-d H:i:s')): " . date('Y-m-d H:i:s') . "\n";

$pdo = Database::conexion();
$stmt = $pdo->query("SELECT NOW() as now_db, CURDATE() as curdate_db, @@session.time_zone as tz_session");
$row = $stmt->fetch();
echo "MySQL time_zone session: " . $row['tz_session'] . "\n";
echo "MySQL NOW(): " . $row['now_db'] . "\n";
echo "MySQL CURDATE(): " . $row['curdate_db'] . "\n";
