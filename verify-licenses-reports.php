<?php
session_start();
require_once __DIR__ . '/config/config.php';
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Auth.php';

Auth::requireRole('superadmin');

header('Content-Type: text/html; charset=UTF-8');

$db = Database::getInstance();

function tableExistsRaw($db, $table) {
    try {
        $db->query("SELECT 1 FROM `$table` LIMIT 1");
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function columnExistsRaw($db, $table, $column) {
    try {
        $db->query("SELECT `$column` FROM `$table` LIMIT 1");
        return true;
    } catch (Exception $e) {
        return false;
    }
}

$requiredTables = [
    'licenses',
    'barbershops',
    'users',
    'barbers',
    'appointments',
    'services',
    'clients',
    'transactions',
];

$requiredLicenseColumns = [
    'id', 'license_key', 'type', 'status', 'price', 'billing_cycle', 'start_date', 'end_date',
    'trial_days', 'trial_start_date', 'trial_end_date', 'activated_at'
];

$missingTables = [];
$missingColumns = [];

foreach ($requiredTables as $table) {
    if (!tableExistsRaw($db, $table)) {
        $missingTables[] = $table;
    }
}

if (!in_array('licenses', $missingTables, true)) {
    foreach ($requiredLicenseColumns as $column) {
        if (!columnExistsRaw($db, 'licenses', $column)) {
            $missingColumns[] = $column;
        }
    }
}

$trialEnumOk = false;
$trialEnumError = null;
if (!in_array('licenses', $missingTables, true)) {
    try {
        $row = $db->fetch("SHOW COLUMNS FROM licenses LIKE 'status'");
        if ($row && isset($row['Type'])) {
            $trialEnumOk = strpos(strtolower($row['Type']), "'trial'") !== false;
        }
    } catch (Exception $e) {
        $trialEnumError = $e->getMessage();
    }
}

$canQueryReports = true;
$reportsError = null;
try {
    $db->fetch("SELECT COUNT(*) as c FROM barbershops");
    $db->fetch("SELECT COUNT(*) as c FROM users WHERE role = 'owner'");
    if (tableExistsRaw($db, 'transactions')) {
        $db->fetch("SELECT COALESCE(SUM(amount),0) as total FROM transactions WHERE type = 'income'");
    }
} catch (Exception $e) {
    $canQueryReports = false;
    $reportsError = $e->getMessage();
}

$sqlSuggestions = [];

if (!$trialEnumOk || !empty($missingColumns)) {
    $sqlSuggestions[] = "-- 1) Ejecutar migración de soporte trial\nSOURCE config/migrations/add_license_trial_support.sql;";
}

if (!empty($missingTables)) {
    $sqlSuggestions[] = "-- 2) Faltan tablas base. Ejecutar esquema completo (con respaldo previo)\nSOURCE config/database.sql;";
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación Licenses/Reports</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-5xl mx-auto bg-white rounded-lg shadow p-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-4">Diagnóstico de Producción: licenses/reports</h1>
        <p class="text-sm text-gray-600 mb-6">Servidor: <?php echo htmlspecialchars($_SERVER['HTTP_HOST'] ?? ''); ?> | Fecha: <?php echo date('Y-m-d H:i:s'); ?></p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div class="p-4 rounded border <?php echo empty($missingTables) ? 'bg-green-50 border-green-300' : 'bg-red-50 border-red-300'; ?>">
                <p class="font-semibold">Tablas requeridas</p>
                <?php if (empty($missingTables)): ?>
                    <p class="text-green-700">OK: todas existen.</p>
                <?php else: ?>
                    <p class="text-red-700">Faltan: <?php echo htmlspecialchars(implode(', ', $missingTables)); ?></p>
                <?php endif; ?>
            </div>

            <div class="p-4 rounded border <?php echo empty($missingColumns) ? 'bg-green-50 border-green-300' : 'bg-red-50 border-red-300'; ?>">
                <p class="font-semibold">Columnas de licenses</p>
                <?php if (empty($missingColumns)): ?>
                    <p class="text-green-700">OK: columnas presentes.</p>
                <?php else: ?>
                    <p class="text-red-700">Faltan: <?php echo htmlspecialchars(implode(', ', $missingColumns)); ?></p>
                <?php endif; ?>
            </div>

            <div class="p-4 rounded border <?php echo $trialEnumOk ? 'bg-green-50 border-green-300' : 'bg-yellow-50 border-yellow-300'; ?>">
                <p class="font-semibold">Enum licenses.status incluye trial</p>
                <?php if ($trialEnumOk): ?>
                    <p class="text-green-700">OK.</p>
                <?php else: ?>
                    <p class="text-yellow-800">No confirmado o no incluye trial.</p>
                    <?php if ($trialEnumError): ?>
                        <p class="text-xs text-yellow-900 mt-1"><?php echo htmlspecialchars($trialEnumError); ?></p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="p-4 rounded border <?php echo $canQueryReports ? 'bg-green-50 border-green-300' : 'bg-red-50 border-red-300'; ?>">
                <p class="font-semibold">Consultas base de reports</p>
                <?php if ($canQueryReports): ?>
                    <p class="text-green-700">OK.</p>
                <?php else: ?>
                    <p class="text-red-700">Error: <?php echo htmlspecialchars($reportsError); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <h2 class="text-lg font-semibold text-gray-900 mb-2">SQL sugerido</h2>
        <?php if (empty($sqlSuggestions)): ?>
            <div class="p-4 rounded bg-green-50 border border-green-300 text-green-800">
                No se detectaron migraciones faltantes para estas páginas.
            </div>
        <?php else: ?>
            <pre class="p-4 rounded bg-gray-900 text-green-300 overflow-x-auto text-sm"><?php echo htmlspecialchars(implode("\n\n", $sqlSuggestions)); ?></pre>
        <?php endif; ?>

        <p class="text-xs text-gray-500 mt-4">Tip: ejecuta este diagnóstico directamente en producción con una sesión superadmin.</p>
    </div>
</body>
</html>
