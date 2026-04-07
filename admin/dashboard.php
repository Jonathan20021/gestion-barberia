<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Auth.php';
require_once BASE_PATH . '/core/Helpers.php';

Auth::requireRole('superadmin');

$db = Database::getInstance();
$demoShopId = (int) ($db->fetch("SELECT id FROM barbershops WHERE slug = ?", [DEMO_BARBERSHOP_SLUG])['id'] ?? 0);
$demoLicenseId = (int) ($db->fetch("SELECT license_id FROM barbershops WHERE slug = ?", [DEMO_BARBERSHOP_SLUG])['license_id'] ?? 0);

// Obtener estadísticas generales
$stats = [
    'total_barbershops' => $db->fetch("SELECT COUNT(*) as count FROM barbershops WHERE status = 'active' AND slug != ?", [DEMO_BARBERSHOP_SLUG])['count'],
    'total_licenses' => $db->fetch("SELECT COUNT(*) as count FROM licenses WHERE status IN ('active', 'trial') AND id != ?", [$demoLicenseId])['count'],
    'total_barbers' => $db->fetch("SELECT COUNT(*) as count FROM barbers WHERE status = 'active' AND barbershop_id != ?", [$demoShopId])['count'],
    'total_appointments' => $db->fetch("SELECT COUNT(*) as count FROM appointments WHERE appointment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND barbershop_id != ?", [$demoShopId])['count'],
    'monthly_revenue' => $db->fetch("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE type = 'license_payment' AND transaction_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND barbershop_id != ?", [$demoShopId])['total'],
    'pending_licenses' => $db->fetch("SELECT COUNT(*) as count FROM licenses WHERE id != ? AND ((status = 'active' AND end_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)) OR (status = 'trial' AND trial_end_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)))", [$demoLicenseId])['count']
];

// Licencias próximas a vencer
$expiring_licenses = $db->fetchAll("
    SELECT l.*, b.business_name, b.email, u.full_name as owner_name
    FROM licenses l
    LEFT JOIN barbershops b ON l.id = b.license_id
    LEFT JOIN users u ON b.owner_id = u.id
    WHERE b.slug != ?
      AND ((l.status = 'active' AND l.end_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY))
       OR (l.status = 'trial' AND l.trial_end_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)))
    ORDER BY CASE WHEN l.status = 'trial' THEN l.trial_end_date ELSE l.end_date END ASC
    LIMIT 10
", [DEMO_BARBERSHOP_SLUG]);

// Barberías recientes
$recent_barbershops = $db->fetchAll("
    SELECT b.*, u.full_name as owner_name, u.email as owner_email, l.type as license_type
    FROM barbershops b
    JOIN users u ON b.owner_id = u.id
    JOIN licenses l ON b.license_id = l.id
    WHERE b.slug != ?
    ORDER BY b.created_at DESC
    LIMIT 5
", [DEMO_BARBERSHOP_SLUG]);

// Ingresos mensuales últimos 6 meses
$monthly_revenue_data = $db->fetchAll("
    SELECT 
        DATE_FORMAT(transaction_date, '%Y-%m') as month,
        SUM(amount) as total
    FROM transactions
    WHERE type = 'license_payment'
    AND barbershop_id != ?
    AND transaction_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(transaction_date, '%Y-%m')
    ORDER BY month ASC
", [$demoShopId]);

$title = 'Panel Super Admin - Kyros Barber Cloud';
include BASE_PATH . '/includes/header.php';
?>

<div class="min-h-screen bg-gray-100" x-data="{ sidebarOpen: false }">
    <?php include BASE_PATH . '/includes/sidebar-admin.php'; ?>

    <!-- Main Content -->
    <div class="lg:pl-64">
        <!-- Top Bar -->
        <div class="sticky top-0 z-40 flex h-16 bg-white border-b border-gray-200 shadow-sm">
            <button @click="sidebarOpen = true" class="px-4 text-gray-500 lg:hidden">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            
            <div class="flex items-center justify-between flex-1 px-4 sm:px-6">
                <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
                
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <button class="p-2 text-gray-400 hover:text-gray-600 relative">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            <?php if ($stats['pending_licenses'] > 0): ?>
                            <span class="absolute top-0 right-0 block h-2 w-2 rounded-full bg-red-500"></span>
                            <?php endif; ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content -->
        <main class="p-6">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 gap-6 mb-8 sm:grid-cols-2 lg:grid-cols-4">
                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Barberías Activas</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2"><?php echo $stats['total_barbershops']; ?></p>
                        </div>
                        <div class="p-3 bg-blue-100 rounded-full">
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Licencias Activas</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2"><?php echo $stats['total_licenses']; ?></p>
                        </div>
                        <div class="p-3 bg-green-100 rounded-full">
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-purple-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Barberos Totales</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2"><?php echo $stats['total_barbers']; ?></p>
                        </div>
                        <div class="p-3 bg-purple-100 rounded-full">
                            <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-yellow-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Ingresos Mensuales</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2"><?php echo formatPrice($stats['monthly_revenue']); ?></p>
                        </div>
                        <div class="p-3 bg-yellow-100 rounded-full">
                            <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Licencias por vencer -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Licencias Próximas a Vencer</h3>
                    </div>
                    <div class="p-6">
                        <?php if (empty($expiring_licenses)): ?>
                            <p class="text-gray-500 text-center py-4">No hay licencias por vencer</p>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($expiring_licenses as $license): ?>
                                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                        <div class="flex-1">
                                            <p class="font-medium text-gray-900"><?php echo e($license['business_name'] ?? 'Sin asignar'); ?></p>
                                            <p class="text-sm text-gray-600"><?php echo e($license['owner_name'] ?? 'N/A'); ?></p>
                                            <p class="text-xs text-gray-500 mt-1">
                                                Tipo: <span class="font-medium"><?php echo ucfirst($license['type']); ?></span>
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-medium text-red-600">
                                                Vence: <?php echo formatDate($license['status'] === 'trial' ? $license['trial_end_date'] : $license['end_date']); ?>
                                            </p>
                                            <span class="inline-block px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800 mt-1">
                                                <?php 
                                                $deadline = $license['status'] === 'trial' ? $license['trial_end_date'] : $license['end_date'];
                                                $days = floor((strtotime($deadline) - time()) / 86400);
                                                echo $days . ' días';
                                                ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Barberías Recientes -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Barberías Recientes</h3>
                    </div>
                    <div class="p-6">
                        <?php if (empty($recent_barbershops)): ?>
                            <p class="text-gray-500 text-center py-4">No hay barberías registradas</p>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($recent_barbershops as $shop): ?>
                                    <div class="flex items-center p-4 bg-gray-50 rounded-lg">
                                        <div class="flex-shrink-0">
                                            <img src="<?php echo $shop['logo'] ? asset($shop['logo']) : getDefaultAvatar($shop['business_name']); ?>" 
                                                 class="w-12 h-12 rounded-full" alt="Logo">
                                        </div>
                                        <div class="ml-4 flex-1">
                                            <p class="font-medium text-gray-900"><?php echo e($shop['business_name']); ?></p>
                                            <p class="text-sm text-gray-600"><?php echo e($shop['owner_name']); ?></p>
                                            <p class="text-xs text-gray-500"><?php echo e($shop['city']); ?></p>
                                        </div>
                                        <div class="text-right">
                                            <span class="inline-block px-2 py-1 text-xs font-medium rounded-full bg-<?php echo $shop['license_type'] === 'enterprise' ? 'purple' : ($shop['license_type'] === 'professional' ? 'blue' : 'gray'); ?>-100 text-<?php echo $shop['license_type'] === 'enterprise' ? 'purple' : ($shop['license_type'] === 'professional' ? 'blue' : 'gray'); ?>-800">
                                                <?php echo ucfirst($shop['license_type']); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <a href="licenses.php?action=create" class="bg-gradient-to-r from-blue-500 to-blue-600 text-white p-6 rounded-lg shadow-md hover:shadow-lg transition transform hover:scale-105">
                    <svg class="w-8 h-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <h4 class="font-semibold text-lg">Nueva Licencia</h4>
                    <p class="text-sm text-blue-100 mt-1">Crear licencia para cliente</p>
                </a>

                <a href="barbershops.php" class="bg-gradient-to-r from-green-500 to-green-600 text-white p-6 rounded-lg shadow-md hover:shadow-lg transition transform hover:scale-105">
                    <svg class="w-8 h-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <h4 class="font-semibold text-lg">Ver Barberías</h4>
                    <p class="text-sm text-green-100 mt-1">Gestionar todas las barberías</p>
                </a>

                <a href="finances.php" class="bg-gradient-to-r from-purple-500 to-purple-600 text-white p-6 rounded-lg shadow-md hover:shadow-lg transition transform hover:scale-105">
                    <svg class="w-8 h-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h4 class="font-semibold text-lg">Finanzas</h4>
                    <p class="text-sm text-purple-100 mt-1">Revisar ingresos y pagos</p>
                </a>

                <a href="reports.php" class="bg-gradient-to-r from-orange-500 to-orange-600 text-white p-6 rounded-lg shadow-md hover:shadow-lg transition transform hover:scale-105">
                    <svg class="w-8 h-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <h4 class="font-semibold text-lg">Reportes</h4>
                    <p class="text-sm text-orange-100 mt-1">Generar reportes detallados</p>
                </a>
            </div>
        </main>
    </div>
</div>

<?php include BASE_PATH . '/includes/footer.php'; ?>
