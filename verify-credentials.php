<?php
/**
 * Verificador de Credenciales Demo
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Database.php';

echo "==========================================\n";
echo "  VERIFICACION DE CREDENCIALES DEMO\n";
echo "==========================================\n\n";

$db = Database::getInstance();

// Credenciales a verificar
$credentials = [
    [
        'email' => 'admin@barbersaas.com',
        'password' => 'password123',
        'expected_role' => 'superadmin',
        'label' => 'Super Admin'
    ],
    [
        'email' => 'demo@barberia.com',
        'password' => 'password123',
        'expected_role' => 'owner',
        'label' => 'Owner'
    ],
    [
        'email' => 'barbero@demo.com',
        'password' => 'password123',
        'expected_role' => 'barber',
        'label' => 'Barbero'
    ]
];

$allValid = true;

foreach ($credentials as $cred) {
    echo "Verificando: {$cred['label']}\n";
    echo "├─ Email: {$cred['email']}\n";
    
    // Buscar usuario en la base de datos
    $user = $db->fetch(
        "SELECT id, email, password, full_name, role, status FROM users WHERE email = ?",
        [$cred['email']]
    );
    
    if (!$user) {
        echo "├─ Estado: ❌ USUARIO NO ENCONTRADO\n";
        echo "└─ Acción: El usuario debe ser creado en la base de datos\n\n";
        $allValid = false;
        continue;
    }
    
    echo "├─ Estado: ✅ Usuario encontrado (ID: {$user['id']})\n";
    echo "├─ Nombre: {$user['full_name']}\n";
    echo "├─ Rol: {$user['role']}";
    
    if ($user['role'] !== $cred['expected_role']) {
        echo " ❌ (esperado: {$cred['expected_role']})\n";
        $allValid = false;
    } else {
        echo " ✅\n";
    }
    
    echo "├─ Status: {$user['status']}\n";
    
    // Verificar contraseña
    if (password_verify($cred['password'], $user['password'])) {
        echo "└─ Password: ✅ CORRECTO (password123)\n\n";
    } else {
        echo "└─ Password: ❌ INCORRECTO\n";
        echo "   Nota: La contraseña en BD no coincide con 'password123'\n\n";
        $allValid = false;
    }
}

echo "==========================================\n";
echo "  RESUMEN\n";
echo "==========================================\n\n";

if ($allValid) {
    echo "✅ TODAS LAS CREDENCIALES SON CORRECTAS\n\n";
    echo "Puede iniciar sesión con cualquiera de estas cuentas:\n\n";
    
    echo "Super Admin:\n";
    echo "  Email: admin@barbersaas.com\n";
    echo "  Password: password123\n\n";
    
    echo "Owner (Dueño de Barbería):\n";
    echo "  Email: demo@barberia.com\n";
    echo "  Password: password123\n\n";
    
    echo "Barbero:\n";
    echo "  Email: barbero@demo.com\n";
    echo "  Password: password123\n\n";
    
    echo "URL de Login: http://localhost/gestion-barberia/auth/login.php\n\n";
} else {
    echo "⚠️ ALGUNAS CREDENCIALES TIENEN PROBLEMAS\n\n";
    echo "Solución: Ejecute el siguiente comando para recrear usuarios:\n";
    echo "php fix-users.php\n\n";
}

// Mostrar todos los usuarios en la BD
echo "\n==========================================\n";
echo "  TODOS LOS USUARIOS EN LA BASE DE DATOS\n";
echo "==========================================\n\n";

$allUsers = $db->fetchAll("SELECT id, email, full_name, role, status FROM users ORDER BY id");

if (count($allUsers) > 0) {
    foreach ($allUsers as $u) {
        echo "ID {$u['id']}: {$u['email']}\n";
        echo "  ├─ Nombre: {$u['full_name']}\n";
        echo "  ├─ Rol: {$u['role']}\n";
        echo "  └─ Estado: {$u['status']}\n\n";
    }
} else {
    echo "❌ No hay usuarios en la base de datos\n";
    echo "Ejecute: php migrate.php\n\n";
}
