<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Auth.php';

Auth::requireRole('superadmin');

header('Content-Type: text/plain; charset=UTF-8');

echo "DEPLOY CHECK\n";
echo "Host: " . ($_SERVER['HTTP_HOST'] ?? '') . "\n";
echo "PHP: " . phpversion() . "\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

$files = [
    'admin/licenses.php',
    'admin/reports.php',
    'config/config.php',
    'core/Database.php',
    'includes/sidebar-admin.php',
];

foreach ($files as $rel) {
    $abs = BASE_PATH . '/' . $rel;
    if (!file_exists($abs)) {
        echo $rel . " => NO EXISTE\n";
        continue;
    }

    echo $rel . "\n";
    echo "  mtime: " . date('Y-m-d H:i:s', filemtime($abs)) . "\n";
    echo "  md5  : " . md5_file($abs) . "\n";
}

echo "\nDB CHECK\n";
try {
    $db = Database::getInstance();
    $row = $db->fetch("SELECT COUNT(*) AS total FROM licenses");
    echo "licenses rows: " . (isset($row['total']) ? $row['total'] : 'n/a') . "\n";
} catch (Exception $e) {
    echo "db error: " . $e->getMessage() . "\n";
}
