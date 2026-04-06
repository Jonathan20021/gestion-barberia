<?php
/**
 * Verificación Rápida de Base de Datos
 * Ejecutar: php db-status.php
 */

require_once __DIR__ . '/config/config.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "✅ CONEXIÓN EXITOSA A LA BASE DE DATOS\n\n";
    
    // Tablas
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "📊 Tablas en la base de datos: " . count($tables) . "\n";
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
        $count = $stmt->fetch()['count'];
        echo "   ├─ $table: $count registros\n";
    }
    
    echo "\n👥 Usuarios Demo:\n";
    $stmt = $pdo->query("SELECT email, role FROM users");
    while ($user = $stmt->fetch()) {
        echo "   ├─ {$user['email']} ({$user['role']})\n";
    }
    
    echo "\n🏪 Barberías:\n";
    $stmt = $pdo->query("SELECT business_name, status FROM barbershops");
    while ($shop = $stmt->fetch()) {
        echo "   ├─ {$shop['business_name']} ({$shop['status']})\n";
    }
    
    echo "\n✅ Base de datos lista para usar!\n";
    echo "   URL: http://localhost/gestion-barberia\n\n";
    
} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "\n💡 Solución: Ejecute 'php migrate.php' para crear la base de datos\n\n";
}
