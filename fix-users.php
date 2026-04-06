<?php
/**
 * Script para Arreglar Contraseñas de Usuarios Demo
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Database.php';

echo "==========================================\n";
echo "  ACTUALIZACION DE CONTRASEÑAS DEMO\n";
echo "==========================================\n\n";

$db = Database::getInstance();

// Contraseña demo
$demoPassword = 'password123';
$hashedPassword = password_hash($demoPassword, PASSWORD_DEFAULT);

echo "Generando hash de contraseña para: '$demoPassword'\n";
echo "Hash generado: " . substr($hashedPassword, 0, 30) . "...\n\n";

// Usuarios a actualizar
$users = [
    [
        'email' => 'admin@kyrosbarbercloud.com',
        'label' => 'Super Admin'
    ],
    [
        'email' => 'demo@barberia.com',
        'label' => 'Owner'
    ],
    [
        'email' => 'barbero@demo.com',
        'label' => 'Barbero'
    ]
];

$updated = 0;

foreach ($users as $user) {
    echo "Actualizando: {$user['label']} ({$user['email']})\n";
    
    try {
        $result = $db->execute(
            "UPDATE users SET password = ? WHERE email = ?",
            [$hashedPassword, $user['email']]
        );
        
        if ($result) {
            echo "├─ Estado: ✅ Contraseña actualizada\n\n";
            $updated++;
        } else {
            echo "├─ Estado: ⚠️ No se actualizó (usuario no existe?)\n\n";
        }
    } catch (Exception $e) {
        echo "├─ Estado: ❌ Error: " . $e->getMessage() . "\n\n";
    }
}

echo "==========================================\n";
echo "  RESUMEN\n";
echo "==========================================\n\n";

if ($updated === 3) {
    echo "✅ TODAS LAS CONTRASEÑAS ACTUALIZADAS CORRECTAMENTE\n\n";
    echo "Credenciales actualizadas:\n\n";
    
    echo "Super Admin:\n";
    echo "  Email: admin@kyrosbarbercloud.com\n";
    echo "  Password: password123\n";
    echo "  URL: http://localhost/gestion-barberia/auth/login.php\n\n";
    
    echo "Owner (Dueño de Barbería):\n";
    echo "  Email: demo@barberia.com\n";
    echo "  Password: password123\n";
    echo "  URL: http://localhost/gestion-barberia/auth/login.php\n\n";
    
    echo "Barbero:\n";
    echo "  Email: barbero@demo.com\n";
    echo "  Password: password123\n";
    echo "  URL: http://localhost/gestion-barberia/auth/login.php\n\n";
    
    echo "✅ Ya puede iniciar sesión con cualquiera de estas cuentas!\n\n";
} else {
    echo "⚠️ Solo se actualizaron $updated de 3 usuarios\n";
    echo "Verifique que los usuarios existan en la base de datos\n\n";
}

// Verificación final
echo "==========================================\n";
echo "  VERIFICACION FINAL\n";
echo "==========================================\n\n";

echo "Verificando que las contraseñas funcionen...\n\n";

foreach ($users as $user) {
    $dbUser = $db->fetch("SELECT password FROM users WHERE email = ?", [$user['email']]);
    
    if ($dbUser && password_verify($demoPassword, $dbUser['password'])) {
        echo "✅ {$user['label']}: Login funcionará correctamente\n";
    } else {
        echo "❌ {$user['label']}: Hay un problema con la contraseña\n";
    }
}

echo "\n";
