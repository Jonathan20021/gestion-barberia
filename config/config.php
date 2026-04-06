<?php
/**
 * Configuración Principal del Sistema
 */

// IMPORTANTE: Configurar sesión ANTES de session_start()
// Estas configuraciones deben estar antes de que cualquier archivo llame session_start()
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Cambiar a 1 en producción con HTTPS
}

// Configuración de entorno
define('ENVIRONMENT', 'production'); // development | production
define('BASE_URL', 'https://www.barber.kyrosrd.com');
define('BASE_PATH', __DIR__ . '/..');

// Configuración de Base de Datos
define('DB_HOST', '129.121.81.172');
define('DB_NAME', 'neetjbte_barbersass');
define('DB_USER', 'neetjbte_barber');
define('DB_PASS', 'Hacker#2002');
define('DB_CHARSET', 'utf8mb4');

// Integracion cPanel Email (UAPI)
// Nota: activa en produccion y coloca valores reales del hosting.
define('CPANEL_EMAIL_API_ENABLED', false);
define('CPANEL_HOST', '');
define('CPANEL_USERNAME', '');
define('CPANEL_API_TOKEN', '');
define('CPANEL_EMAIL_DEFAULT_DOMAIN', '');
define('CPANEL_EMAIL_VERIFY_SSL', false);

// Zona horaria República Dominicana
date_default_timezone_set('America/Santo_Domingo');

// Configuración de errores
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Configuración de licencias
define('TRIAL_DAYS_DEFAULT', 15);

define('LICENSE_TYPES', [
    'basic' => [
        'name' => 'Básico',
        'price' => 1500, // RD$
        'max_barbers' => 2,
        'max_services' => 10,
        'max_monthly_appointments' => 100,
        'max_locations' => 1,
        'features' => ['reservas_online', 'calendario', 'clientes']
    ],
    'professional' => [
        'name' => 'Profesional',
        'price' => 3000,
        'max_barbers' => 5,
        'max_services' => 50,
        'max_monthly_appointments' => -1,
        'max_locations' => 1,
        'features' => ['reservas_online', 'calendario', 'clientes', 'reportes', 'notificaciones_sms']
    ],
    'enterprise' => [
        'name' => 'Empresarial',
        'price' => 5000,
        'max_barbers' => -1, // Ilimitado
        'max_services' => -1,
        'max_monthly_appointments' => -1,
        'max_locations' => -1,
        'features' => ['reservas_online', 'calendario', 'clientes', 'reportes', 'notificaciones_sms', 'api_acceso', 'soporte_prioritario', 'multi_sucursal']
    ]
]);

// Autoload de clases
spl_autoload_register(function ($class) {
    $paths = [
        BASE_PATH . '/core/' . $class . '.php',
        BASE_PATH . '/models/' . $class . '.php',
        BASE_PATH . '/controllers/' . $class . '.php',
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// Helper para incluir archivos
function include_view($view, $data = []) {
    extract($data);
    require_once BASE_PATH . '/views/' . $view . '.php';
}
