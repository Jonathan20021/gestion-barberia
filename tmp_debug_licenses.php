<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

session_start();
$_SESSION['logged_in'] = true;
$_SESSION['user_role'] = 'superadmin';
$_SESSION['user_id'] = 1;
$_SERVER['REQUEST_METHOD'] = 'GET';

require_once __DIR__ . '/config/config.php';
require_once BASE_PATH . '/core/Database.php';

try {
    $db = Database::getInstance();
    $row = $db->fetch('SELECT id,status,trial_end_date,trial_days,activated_at FROM licenses LIMIT 1');
    echo "Schema check OK:\n";
    var_export($row);
    echo "\n\nRendering admin/licenses.php...\n";

    include __DIR__ . '/admin/licenses.php';
} catch (Throwable $e) {
    echo "\nFATAL: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
