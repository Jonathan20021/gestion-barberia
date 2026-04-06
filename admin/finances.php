<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Auth.php';
require_once BASE_PATH . '/core/Helpers.php';

Auth::requireRole('superadmin');

$db = Database::getInstance();

// Estadísticas financieras
$stats = [
    'total_revenue' => $db->fetch("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE type = 'income'")['total'],
    'monthly_revenue' => $db->fetch("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE type = 'income' AND MONTH(created_at) = MONTH(CURRENT_DATE())")['total'],
    'pending_payments' => $db->fetch("SELECT COALESCE(SUM(price), 0) as total FROM licenses WHERE status = 'active'")['total'],
    'total_licenses' => $db->fetch("SELECT COUNT(*) as count FROM licenses WHERE status = 'active'")['count']
];

// Transacciones recientes
$transactions = $db->fetchAll("
    SELECT 
        t.*,
        b.business_name,
        u.full_name as owner_name
    FROM transactions t
    LEFT JOIN barbershops b ON t.barbershop_id = b.id
    LEFT JOIN users u ON b.owner_id = u.id
    ORDER BY t.created_at DESC
    LIMIT 50
");

// Ingresos por mes (últimos 6 meses)
$monthlyRevenue = $db->fetchAll("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COALESCE(SUM(amount), 0) as revenue,
        COUNT(*) as transactions
    FROM transactions
    WHERE created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
    AND type = 'income'
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month DESC
");

$title = 'Finanzas - Super Admin';
include BASE_PATH . '/includes/header.php';
?>

<div class="min-h-screen bg-gray-100" x-data="{ sidebarOpen: false }">
    <?php include BASE_PATH . '/includes/sidebar-admin.php'; ?>

    <!-- Main Content -->
    <div class="lg:pl-64">
        <div class="sticky top-0 z-40 flex h-16 bg-white border-b border-gray-200 shadow-sm">
            <button @click="sidebarOpen = true" class="px-4 text-gray-500 lg:hidden">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            
            <div class="flex items-center justify-between flex-1 px-4 sm:px-6">
                <h1 class="text-2xl font-bold text-gray-900">Panel Financiero</h1>
                <a href="../auth/logout.php" class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg">Cerrar Sesión</a>
            </div>
        </div>

        <main class="p-6">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-lg p-6 text-white">
                    <p class="text-green-100">Ingresos Totales</p>
                    <p class="text-3xl font-bold mt-2"><?php echo formatPrice($stats['total_revenue']); ?></p>
                    <p class="text-sm text-green-100 mt-2">Todos los tiempos</p>
                </div>
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 text-white">
                    <p class="text-blue-100">Ingresos del Mes</p>
                    <p class="text-3xl font-bold mt-2"><?php echo formatPrice($stats['monthly_revenue']); ?></p>
                    <p class="text-sm text-blue-100 mt-2"><?php echo date('F Y'); ?></p>
                </div>
                <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow-lg p-6 text-white">
                    <p class="text-purple-100">Licencias Activas</p>
                    <p class="text-3xl font-bold mt-2"><?php echo $stats['total_licenses']; ?></p>
                    <p class="text-sm text-purple-100 mt-2">Generando <?php echo formatPrice($stats['pending_payments']); ?>/mes</p>
                </div>
                <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg shadow-lg p-6 text-white">
                    <p class="text-orange-100">Promedio por Licencia</p>
                    <p class="text-3xl font-bold mt-2">
                        <?php echo $stats['total_licenses'] > 0 ? formatPrice($stats['pending_payments'] / $stats['total_licenses']) : 'RD$0'; ?>
                    </p>
                    <p class="text-sm text-orange-100 mt-2">Mensual</p>
                </div>
            </div>

            <!-- Ingresos por Mes -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Ingresos por Mes (Últimos 6 Meses)</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mes</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ingresos</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Transacciones</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Promedio</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($monthlyRevenue as $month): ?>
                            <tr>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                    <?php echo date('F Y', strtotime($month['month'] . '-01')); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-right font-semibold text-green-600">
                                    <?php echo formatPrice($month['revenue']); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-right text-gray-900">
                                    <?php echo $month['transactions']; ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-right text-gray-600">
                                    <?php echo formatPrice($month['revenue'] / max($month['transactions'], 1)); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Transacciones Recientes -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Transacciones Recientes</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Barbería</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Descripción</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Método</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Monto</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($transactions as $trans): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <?php echo formatDate($trans['created_at']); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm font-medium text-gray-900"><?php echo e($trans['business_name']); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo e($trans['owner_name']); ?></p>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <?php echo $trans['description'] ? e($trans['description']) : '-'; ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <?php echo $trans['payment_method'] ? ucfirst($trans['payment_method']) : '-'; ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-right font-semibold text-green-600">
                                    <?php echo formatPrice($trans['amount']); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full
                                        <?php echo $trans['type'] === 'income' ? 'bg-green-100 text-green-800' : 
                                              ($trans['type'] === 'expense' ? 'bg-red-100 text-red-800' : 
                                              'bg-blue-100 text-blue-800'); ?>">
                                        <?php 
                                        $typeLabels = [
                                            'income' => 'Ingreso',
                                            'expense' => 'Gasto',
                                            'license_payment' => 'Pago Licencia',
                                            'commission' => 'Comisión'
                                        ];
                                        echo $typeLabels[$trans['type']] ?? ucfirst($trans['type']); 
                                        ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include BASE_PATH . '/includes/footer.php'; ?>
