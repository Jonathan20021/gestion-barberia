<?php
/**
 * BarberSaaS - Sistema de Gestión de Barberías
 * Multi-tenant SaaS Platform
 * República Dominicana
 */

require_once __DIR__ . '/config/config.php';
session_start();
require_once __DIR__ . '/core/Router.php';
require_once __DIR__ . '/core/Database.php';

// Inicializar router
$router = new Router();

// Redireccionar a página apropiada según contexto
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['user_role'] ?? '';
    
    switch ($role) {
        case 'superadmin':
            header('Location: /gestion-barberia/admin/dashboard.php');
            break;
        case 'owner':
            header('Location: /gestion-barberia/dashboard/index.php');
            break;
        case 'barber':
            header('Location: /gestion-barberia/dashboard/barber/index.php');
            break;
        default:
            header('Location: /gestion-barberia/landing.php');
    }
} else {
    header('Location: /gestion-barberia/landing.php');
}
exit;
