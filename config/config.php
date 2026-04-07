<?php
/**
 * Configuración Principal del Sistema
 */

define('BASE_PATH', __DIR__ . '/..');

$appHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
$appScheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$localHosts = ['localhost', '127.0.0.1', '::1'];
$isLocalHost = in_array($appHost, $localHosts, true) || strpos($appHost, 'localhost') !== false;

$defaultEnvironment = $isLocalHost ? 'development' : 'production';
$environment = getenv('APP_ENV') ?: $defaultEnvironment;

$localBaseUrl = 'http://localhost/gestion-barberia';
$productionBaseUrl = $appScheme . '://' . $appHost;
$baseUrl = getenv('APP_BASE_URL') ?: ($environment === 'development' ? $localBaseUrl : $productionBaseUrl);

// Misma base de datos compartida para desarrollo y producción.
$sharedDbHost = getenv('DB_HOST') ?: '129.121.81.172';
$sharedDbName = getenv('DB_NAME') ?: 'neetjbte_barbersass';
$sharedDbUser = getenv('DB_USER') ?: 'neetjbte_barber';
$sharedDbPass = getenv('DB_PASS') ?: 'Hacker#2002';
$dbCharset = getenv('DB_CHARSET') ?: 'utf8mb4';

// IMPORTANTE: Configurar sesión ANTES de session_start()
// Estas configuraciones deben estar antes de que cualquier archivo llame session_start()
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', $appScheme === 'https' ? '1' : '0');
}

// Configuración de entorno
define('ENVIRONMENT', $environment); // development | production
define('BASE_URL', rtrim($baseUrl, '/'));

// Configuración de Base de Datos
define('DB_HOST', $sharedDbHost);
define('DB_NAME', $sharedDbName);
define('DB_USER', $sharedDbUser);
define('DB_PASS', $sharedDbPass);
define('DB_CHARSET', $dbCharset);

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
    ini_set('log_errors', 1);
}

// Configuración de licencias
define('TRIAL_DAYS_DEFAULT', 15);
define('DEMO_OWNER_EMAIL', 'demo@barberia.com');
define('DEMO_BARBER_EMAIL', 'barbero@demo.com');
define('DEMO_BARBERSHOP_SLUG', 'estilo-rd');
define('PROTECTED_DEMO_EMAILS', [DEMO_OWNER_EMAIL, DEMO_BARBER_EMAIL]);

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
