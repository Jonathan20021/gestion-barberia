<?php
/**
 * Verificar Tablas de la Base de Datos
 */

require_once __DIR__ . '/config/config.php';
require_once BASE_PATH . '/core/Database.php';

header('Content-Type: text/html; charset=UTF-8');

$db = Database::getInstance();

// Todas las tablas que deberían existir
$requiredTables = [
    'users',
    'licenses',
    'barbershops',
    'barbers',
    'services',
    'barber_services',
    'barbershop_schedules',
    'barber_schedules',
    'time_off',
    'clients',
    'appointments',
    'transactions',
    'notifications',
    'reviews',
    'barbershop_settings',
    'activity_logs'
];

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Verificar Tablas - Kyros Barber Cloud</title>
    <script src='https://cdn.tailwindcss.com'></script>
</head>
<body class='bg-gray-100 p-8'>
    <div class='max-w-4xl mx-auto'>
        <div class='bg-white rounded-lg shadow-lg p-6'>
            <h1 class='text-3xl font-bold mb-6 text-gray-900'>Verificacion de Tablas - Base de Datos</h1>
            
            <div class='space-y-4'>\n";

$allExist = true;

try {
    // Obtener todas las tablas de la base de datos
    $stmt = $db->query("SHOW TABLES");
    $existingTables = array_column($stmt->fetchAll(PDO::FETCH_NUM), 0);
    
    foreach ($requiredTables as $table) {
        $exists = in_array($table, $existingTables);
        $allExist = $allExist && $exists;
        
        $statusClass = $exists ? 'bg-green-100 border-green-500 text-green-900' : 'bg-red-100 border-red-500 text-red-900';
        $icon = $exists ? '✓' : '✗';
        
        // Contar registros si existe
        $count = 0;
        if ($exists) {
            try {
                $count = $db->fetch("SELECT COUNT(*) as count FROM $table")['count'];
            } catch (Exception $e) {
                $count = 'Error';
            }
        }
        
        echo "<div class='flex items-center justify-between p-4 border-l-4 $statusClass rounded'>
                <div class='flex items-center space-x-3'>
                    <span class='text-2xl font-bold'>$icon</span>
                    <span class='font-semibold'>$table</span>
                </div>
                <span class='text-sm'>" . ($exists ? "$count registros" : 'NO EXISTE') . "</span>
              </div>\n";
    }
    
    echo "</div>\n";
    
    // Resumen
    if ($allExist) {
        echo "<div class='mt-6 p-4 bg-green-100 border border-green-500 rounded'>
                <p class='text-green-900 font-bold'>✓ Todas las tablas existen correctamente</p>
              </div>";
    } else {
        echo "<div class='mt-6 p-4 bg-red-100 border border-red-500 rounded'>
                <p class='text-red-900 font-bold'>✗ Faltan tablas. Ejecuta el script de migracion:</p>
                <code class='block mt-2 bg-black text-white p-2 rounded'>mysql -u root barberia_saas < config/database.sql</code>
              </div>";
    }
    
    // Tablas extra que no están en la lista
    $extraTables = array_diff($existingTables, $requiredTables);
    if (!empty($extraTables)) {
        echo "<div class='mt-6 p-4 bg-blue-100 border border-blue-500 rounded'>
                <p class='text-blue-900 font-semibold'>Tablas adicionales encontradas:</p>
                <div class='mt-2 space-y-1'>";
        foreach ($extraTables as $table) {
            echo "<span class='inline-block px-3 py-1 bg-blue-200 rounded text-sm'>$table</span> ";
        }
        echo "</div></div>";
    }
    
} catch (PDOException $e) {
    echo "<div class='p-4 bg-red-100 border border-red-500 rounded'>
            <p class='text-red-900 font-bold'>Error al conectar a la base de datos:</p>
            <p class='text-sm text-red-700 mt-2'>{$e->getMessage()}</p>
          </div>";
}

echo "
            <div class='mt-6 text-center'>
                <a href='/' class='inline-block px-6 py-3 bg-gray-900 text-white rounded-lg hover:bg-gray-800 transition'>
                    Volver al Inicio
                </a>
            </div>
        </div>
    </div>
</body>
</html>";
?>
