<?php
/*
 * BarberSaaS - Sistema de Gestión de Barberías
 * Verificador de Instalación
 * 
 * Ejecutar: http://localhost/gestion-barberia/check-install.php
 */

// Verificaciones
$checks = [];

// 1. Versión de PHP
$checks['PHP Version'] = [
    'required' => '8.0',
    'current' => PHP_VERSION,
    'status' => version_compare(PHP_VERSION, '8.0', '>=')
];

// 2. Extensiones PHP
$required_extensions = ['pdo', 'pdo_mysql', 'mbstring', 'json'];
foreach ($required_extensions as $ext) {
    $checks["Extension: $ext"] = [
        'required' => 'Enabled',
        'current' => extension_loaded($ext) ? 'Enabled' : 'Disabled',
        'status' => extension_loaded($ext)
    ];
}

// 3. Configuración de archivos
$checks['config.php exists'] = [
    'required' => 'Yes',
    'current' => file_exists(__DIR__ . '/config/config.php') ? 'Yes' : 'No',
    'status' => file_exists(__DIR__ . '/config/config.php')
];

// 4. Conexión a base de datos
try {
    require_once __DIR__ . '/config/config.php';
    require_once __DIR__ . '/core/Database.php';
    $db = Database::getInstance();
    $test = $db->fetch("SELECT 1 as test");
    
    $checks['Database Connection'] = [
        'required' => 'Connected',
        'current' => 'Connected',
        'status' => true
    ];
    
    // 5. Tablas de base de datos
    $tables = ['users', 'licenses', 'barbershops', 'barbers', 'services', 'appointments'];
    foreach ($tables as $table) {
        try {
            $db->fetch("SELECT 1 FROM $table LIMIT 1");
            $checks["Table: $table"] = [
                'required' => 'Exists',
                'current' => 'Exists',
                'status' => true
            ];
        } catch (Exception $e) {
            $checks["Table: $table"] = [
                'required' => 'Exists',
                'current' => 'Missing',
                'status' => false
            ];
        }
    }
    
} catch (Exception $e) {
    $checks['Database Connection'] = [
        'required' => 'Connected',
        'current' => 'Failed: ' . $e->getMessage(),
        'status' => false
    ];
}

// 6. Permisos de escritura
$writable_dirs = ['logs', 'assets/uploads'];
foreach ($writable_dirs as $dir) {
    $path = __DIR__ . '/' . $dir;
    $exists = is_dir($path);
    $writable = $exists && is_writable($path);
    
    $checks["Writable: $dir"] = [
        'required' => 'Writable',
        'current' => !$exists ? 'Not exists' : ($writable ? 'Writable' : 'Not writable'),
        'status' => $writable
    ];
}

// Calcular resultado general
$total = count($checks);
$passed = count(array_filter($checks, fn($c) => $c['status']));
$success_rate = round(($passed / $total) * 100);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación de Instalación - BarberSaaS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen py-12 px-4">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="bg-white rounded-lg shadow-lg p-8 mb-6">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">🔍 Verificación de Instalación</h1>
                <p class="text-gray-600">BarberSaaS - Sistema de Gestión de Barberías</p>
                
                <div class="mt-6">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700">Progreso de Instalación</span>
                        <span class="text-sm font-medium text-gray-700"><?php echo $passed; ?>/<?php echo $total; ?> (<?php echo $success_rate; ?>%)</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-4">
                        <div class="bg-<?php echo $success_rate === 100 ? 'green' : ($success_rate >= 70 ? 'yellow' : 'red'); ?>-500 h-4 rounded-full transition-all" 
                             style="width: <?php echo $success_rate; ?>%"></div>
                    </div>
                </div>
            </div>

            <!-- Resultados -->
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Resultados de Verificación</h2>
                </div>
                
                <div class="divide-y divide-gray-200">
                    <?php foreach ($checks as $name => $check): ?>
                    <div class="px-6 py-4 flex items-center justify-between hover:bg-gray-50">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 mr-4">
                                <?php if ($check['status']): ?>
                                    <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                <?php else: ?>
                                    <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                <?php endif; ?>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900"><?php echo $name; ?></p>
                                <p class="text-sm text-gray-500">
                                    Requerido: <?php echo $check['required']; ?> | 
                                    Actual: <?php echo $check['current']; ?>
                                </p>
                            </div>
                        </div>
                        <span class="px-3 py-1 text-xs font-medium rounded-full <?php echo $check['status'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                            <?php echo $check['status'] ? 'OK' : 'FAIL'; ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Acciones -->
            <div class="mt-6 bg-white rounded-lg shadow-lg p-6">
                <?php if ($success_rate === 100): ?>
                    <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-4">
                        <div class="flex">
                            <svg class="w-6 h-6 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div>
                                <h3 class="font-semibold text-green-800">¡Instalación Correcta!</h3>
                                <p class="text-sm text-green-700 mt-1">El sistema está correctamente instalado y listo para usar.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex space-x-4">
                        <a href="index.php" class="flex-1 px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg font-semibold text-center hover:from-indigo-700 hover:to-purple-700 transition">
                            🚀 Ir al Sistema
                        </a>
                        <a href="README.md" class="flex-1 px-6 py-3 border border-gray-300 text-gray-700 rounded-lg font-semibold text-center hover:bg-gray-50 transition">
                            📚 Ver Documentación
                        </a>
                    </div>
                <?php else: ?>
                    <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-4">
                        <div class="flex">
                            <svg class="w-6 h-6 text-yellow-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <div>
                                <h3 class="font-semibold text-yellow-800">Instalación Incompleta</h3>
                                <p class="text-sm text-yellow-700 mt-1">Algunos requisitos no se cumplen. Revise los errores arriba.</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="font-semibold text-gray-900 mb-2">Soluciones Comunes:</h4>
                        <ul class="space-y-2 text-sm text-gray-700">
                            <li>• <strong>Base de datos:</strong> Ejecute el archivo config/database.sql en phpMyAdmin</li>
                            <li>• <strong>Extensiones PHP:</strong> Active las extensiones requeridas en php.ini</li>
                            <li>• <strong>Permisos:</strong> Cree carpetas faltantes y otorgue permisos de escritura</li>
                            <li>• <strong>Configuración:</strong> Verifique las credenciales en config/config.php</li>
                        </ul>
                    </div>

                    <div class="mt-4">
                        <button onclick="location.reload()" class="w-full px-6 py-3 bg-indigo-600 text-white rounded-lg font-semibold hover:bg-indigo-700 transition">
                            🔄 Verificar Nuevamente
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Info -->
            <div class="mt-6 text-center text-sm text-gray-600">
                <p>BarberSaaS v1.0 - Sistema de Gestión de Barberías</p>
                <p class="mt-1">Made with ❤️ for República Dominicana</p>
            </div>
        </div>
    </div>
</body>
</html>
