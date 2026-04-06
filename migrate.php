<?php
/**
 * Script de Migración Automática de Base de Datos
 */

require_once __DIR__ . '/config/config.php';

echo "==========================================\n";
echo "  MIGRACION DE BASE DE DATOS - BarberSaaS\n";
echo "==========================================\n\n";

try {
    // Conectar a MySQL sin seleccionar base de datos
    $dsn = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✓ Conexión a MySQL establecida\n\n";
    
    // Verificar si la base de datos existe
    $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" . DB_NAME . "'");
    $dbExists = $stmt->fetch();
    
    if (!$dbExists) {
        echo "⚠ Base de datos '" . DB_NAME . "' no existe. Creando...\n";
        $pdo->exec("CREATE DATABASE `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "✓ Base de datos creada exitosamente\n\n";
    } else {
        echo "✓ Base de datos '" . DB_NAME . "' ya existe\n\n";
    }
    
    // Seleccionar la base de datos
    $pdo->exec("USE `" . DB_NAME . "`");
    
    // Verificar tablas existentes
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $tableCount = count($tables);
    
    echo "📊 Tablas encontradas: $tableCount\n";
    if ($tableCount > 0) {
        echo "   Tablas existentes:\n";
        foreach ($tables as $table) {
            echo "   - $table\n";
        }
        echo "\n";
    }
    
    // Tablas requeridas
    $requiredTables = [
        'users', 'licenses', 'barbershops', 'barbers', 'services',
        'appointments', 'clients', 'transactions', 'reviews',
        'notifications', 'barbershop_schedules', 'barber_schedules',
        'time_off', 'service_categories', 'payments'
    ];
    
    $missingTables = array_diff($requiredTables, $tables);
    
    if (count($missingTables) > 0) {
        echo "⚠ Faltan " . count($missingTables) . " tablas. Ejecutando migración...\n";
        echo "   Tablas faltantes: " . implode(', ', $missingTables) . "\n\n";
        
        // Leer y ejecutar el archivo SQL
        $sqlFile = __DIR__ . '/config/database.sql';
        
        if (!file_exists($sqlFile)) {
            echo "❌ ERROR: Archivo database.sql no encontrado en: $sqlFile\n";
            exit(1);
        }
        
        echo "📄 Leyendo archivo database.sql...\n";
        $sql = file_get_contents($sqlFile);
        
        // Dividir por sentencias
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            function($stmt) {
                return !empty($stmt) && 
                       !preg_match('/^--/', $stmt) && 
                       !preg_match('/^\/\*/', $stmt);
            }
        );
        
        echo "🔄 Ejecutando " . count($statements) . " sentencias SQL...\n\n";
        
        $executed = 0;
        $errors = 0;
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (empty($statement)) continue;
            
            try {
                $pdo->exec($statement . ';');
                $executed++;
                
                // Mostrar progreso cada 5 sentencias
                if ($executed % 5 == 0) {
                    echo "   Progreso: $executed sentencias ejecutadas...\r";
                }
            } catch (PDOException $e) {
                // Ignorar errores de "tabla ya existe"
                if (strpos($e->getMessage(), 'already exists') === false) {
                    $errors++;
                    echo "   ⚠ Error en sentencia: " . substr($statement, 0, 50) . "...\n";
                    echo "     " . $e->getMessage() . "\n";
                }
            }
        }
        
        echo "\n\n";
        echo "✓ Migración completada\n";
        echo "  - Sentencias ejecutadas: $executed\n";
        if ($errors > 0) {
            echo "  - Errores: $errors\n";
        }
        
    } else {
        echo "✓ Todas las tablas requeridas existen\n";
    }
    
    // Verificación final
    echo "\n==========================================\n";
    echo "  VERIFICACION FINAL\n";
    echo "==========================================\n\n";
    
    $stmt = $pdo->query("SHOW TABLES");
    $finalTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "✓ Total de tablas en la base de datos: " . count($finalTables) . "\n\n";
    
    // Verificar datos demo
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $userCount = $stmt->fetch()['count'];
    
    if ($userCount == 0) {
        echo "⚠ No hay usuarios demo. Por favor, ejecute manualmente las sentencias INSERT del archivo database.sql\n";
    } else {
        echo "✓ Usuarios en la base de datos: $userCount\n";
        
        // Mostrar usuarios demo
        $stmt = $pdo->query("SELECT id, email, role FROM users LIMIT 5");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\n📋 Usuarios de demostración:\n";
        foreach ($users as $user) {
            echo "   - {$user['email']} ({$user['role']})\n";
        }
    }
    
    echo "\n✅ MIGRACION COMPLETADA EXITOSAMENTE\n";
    echo "==========================================\n\n";
    echo "Puede acceder al sistema en:\n";
    echo "http://localhost/gestion-barberia\n\n";
    
} catch (PDOException $e) {
    echo "\n❌ ERROR DE BASE DE DATOS:\n";
    echo $e->getMessage() . "\n\n";
    echo "Soluciones:\n";
    echo "1. Verifique que MySQL esté corriendo en XAMPP\n";
    echo "2. Verifique las credenciales en config/config.php\n";
    echo "3. Verifique que el puerto MySQL sea el correcto\n\n";
    exit(1);
} catch (Exception $e) {
    echo "\n❌ ERROR:\n";
    echo $e->getMessage() . "\n\n";
    exit(1);
}
