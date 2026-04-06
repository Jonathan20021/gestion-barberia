<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Auth.php';
require_once BASE_PATH . '/core/Helpers.php';

Auth::requireRole('superadmin');

if (isset($_GET['debug']) && $_GET['debug'] === '1') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

@set_time_limit(120);

$db = Database::getInstance();
$pageError = null;

// Periodo de reporte (ultimos 30 dias por defecto)
$startDate = input('start_date') ?: date('Y-m-d', strtotime('-30 days'));
$endDate = input('end_date') ?: date('Y-m-d');

$systemStats = [
    'active_barbershops' => 0,
    'total_barbershops' => 0,
    'total_owners' => 0,
    'total_barbers' => 0,
    'active_barbers' => 0,
    'total_clients' => 0,
    'total_appointments' => 0,
    'completed_appointments' => 0,
    'total_revenue' => 0,
];
$growthData = [];
$topBarbershops = [];
$popularServices = [];
$expiringLicenses = [];
$appointmentStats = [];

try {
    $safeTableExists = function ($tableName) use ($db) {
        try {
            if (method_exists($db, 'tableExists')) {
                return (bool) $db->tableExists($tableName);
            }

            $db->query("SELECT 1 FROM `$tableName` LIMIT 1");
            return true;
        } catch (Exception $e) {
            return false;
        }
    };

    $hasClientsTable = $safeTableExists('clients');
    $hasTransactionsTable = $safeTableExists('transactions');
    $clientCountSelect = $hasClientsTable ? "(SELECT COUNT(*) FROM clients)" : "0";
    $revenueSelect = $hasTransactionsTable ? "(SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE type = 'income')" : "0";

    $systemStats = $db->fetch("
        SELECT 
            (SELECT COUNT(*) FROM barbershops WHERE status = 'active') as active_barbershops,
            (SELECT COUNT(*) FROM barbershops) as total_barbershops,
            (SELECT COUNT(*) FROM users WHERE role = 'owner') as total_owners,
            (SELECT COUNT(*) FROM users WHERE role = 'barber') as total_barbers,
            (SELECT COUNT(*) FROM barbers WHERE status = 'active') as active_barbers,
            $clientCountSelect as total_clients,
            (SELECT COUNT(*) FROM appointments) as total_appointments,
            (SELECT COUNT(*) FROM appointments WHERE status = 'completed') as completed_appointments,
            $revenueSelect as total_revenue
    ") ?: $systemStats;

    $growthData = $db->fetchAll("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(DISTINCT CASE WHEN role = 'owner' THEN id END) as new_owners,
            COUNT(DISTINCT CASE WHEN role = 'barber' THEN id END) as new_barbers
        FROM users
        WHERE created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month DESC
    ");

    $topBarbershops = $db->fetchAll("
        SELECT 
            bb.business_name,
            bb.slug,
            u.full_name as owner_name,
            l.type as license_type,
            (SELECT COUNT(*) FROM barbers b WHERE b.barbershop_id = bb.id) as barbers_count,
            COUNT(a.id) as appointments_count,
            COALESCE(SUM(CASE WHEN a.status = 'completed' THEN a.price ELSE 0 END), 0) as total_revenue
        FROM barbershops bb
        LEFT JOIN users u ON bb.owner_id = u.id
        LEFT JOIN licenses l ON bb.license_id = l.id
        LEFT JOIN appointments a ON bb.id = a.barbershop_id 
            AND a.appointment_date BETWEEN ? AND ?
        GROUP BY bb.id
        ORDER BY total_revenue DESC
        LIMIT 10
    ", [$startDate, $endDate]);

    $popularServices = $db->fetchAll("
        SELECT 
            s.name,
            s.category,
            COUNT(a.id) as total_bookings,
            COALESCE(SUM(a.price), 0) as total_revenue,
            ROUND(AVG(a.price), 2) as avg_price
        FROM services s
        JOIN appointments a ON s.id = a.service_id
        WHERE a.appointment_date BETWEEN ? AND ?
        AND a.status = 'completed'
        GROUP BY s.id
        ORDER BY total_bookings DESC
        LIMIT 10
    ", [$startDate, $endDate]);

    $expiringLicenses = $db->fetchAll("
        SELECT 
            bb.business_name,
            u.full_name as owner_name,
            u.email as owner_email,
            l.type as license_type,
            l.end_date as license_expires_at,
            DATEDIFF(l.end_date, CURRENT_DATE()) as days_remaining
        FROM barbershops bb
        JOIN users u ON bb.owner_id = u.id
        JOIN licenses l ON bb.license_id = l.id
        WHERE l.end_date <= DATE_ADD(CURRENT_DATE(), INTERVAL 30 DAY)
        AND l.end_date >= CURRENT_DATE()
        AND bb.status = 'active'
        ORDER BY l.end_date ASC
    ");

    $periodAppointmentsRow = $db->fetch("SELECT COUNT(*) AS total FROM appointments WHERE appointment_date BETWEEN ? AND ?", [$startDate, $endDate]);
    $periodAppointmentsTotal = (int) ($periodAppointmentsRow ? $periodAppointmentsRow['total'] : 0);

    $appointmentStats = $db->fetchAll("
        SELECT 
            status,
            COUNT(*) as total
        FROM appointments
        WHERE appointment_date BETWEEN ? AND ?
        GROUP BY status
        ORDER BY total DESC
    ", [$startDate, $endDate]);

    if (!empty($appointmentStats)) {
        foreach ($appointmentStats as $key => $stat) {
            $totalByStatus = (int) $stat['total'];
            $appointmentStats[$key]['percentage'] = $periodAppointmentsTotal > 0
                ? round(($totalByStatus * 100.0) / $periodAppointmentsTotal, 2)
                : 0;
        }
    }
} catch (Exception $e) {
    error_log('Reports page load error: ' . $e->getMessage());
    $pageError = ENVIRONMENT === 'development'
        ? $e->getMessage()
        : 'No se pudieron cargar los reportes en este servidor. Revisa el log de PHP/MySQL del hosting.';
}

$title = 'Reportes del Sistema - Super Admin';
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
                <h1 class="text-2xl font-bold text-gray-900">Reportes del Sistema</h1>
                
                <!-- Filtro de fecha -->
                <form method="GET" class="flex space-x-3">
                    <input type="date" name="start_date" value="<?php echo $startDate; ?>" 
                           class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <input type="date" name="end_date" value="<?php echo $endDate; ?>" 
                           class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm">
                        Filtrar
                    </button>
                </form>
            </div>
        </div>

        <main class="p-6">
            <?php if ($pageError): ?>
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
                <p class="text-red-700"><?php echo htmlspecialchars($pageError); ?></p>
            </div>
            <?php endif; ?>
            <!-- Stats Globales -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 text-white">
                    <p class="text-blue-100">Barberias Activas</p>
                    <p class="text-4xl font-bold mt-2"><?php echo $systemStats['active_barbershops']; ?></p>
                    <p class="text-sm text-blue-100 mt-2">de <?php echo $systemStats['total_barbershops']; ?> totales</p>
                </div>
                
                <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-lg p-6 text-white">
                    <p class="text-green-100">Barberos Activos</p>
                    <p class="text-4xl font-bold mt-2"><?php echo $systemStats['active_barbers']; ?></p>
                    <p class="text-sm text-green-100 mt-2"><?php echo $systemStats['total_barbers']; ?> registrados</p>
                </div>
                
                <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow-lg p-6 text-white">
                    <p class="text-purple-100">Citas Completadas</p>
                    <p class="text-4xl font-bold mt-2"><?php echo number_format($systemStats['completed_appointments']); ?></p>
                    <p class="text-sm text-purple-100 mt-2">
                        <?php echo round(($systemStats['completed_appointments'] / max($systemStats['total_appointments'], 1)) * 100, 1); ?>% del total
                    </p>
                </div>
                
                <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg shadow-lg p-6 text-white">
                    <p class="text-orange-100">Ingresos Totales</p>
                    <p class="text-4xl font-bold mt-2"><?php echo formatPrice($systemStats['total_revenue']); ?></p>
                    <p class="text-sm text-orange-100 mt-2">Todo el tiempo</p>
                </div>
            </div>

            <div class="grid lg:grid-cols-2 gap-6 mb-6">
                <!-- Top Barberias -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h2 class="text-lg font-semibold text-gray-900">Top 10 Barberias por Ingresos</h2>
                        <p class="text-sm text-gray-600">Periodo: <?php echo formatDate($startDate); ?> - <?php echo formatDate($endDate); ?></p>
                    </div>
                    <div class="p-6">
                        <div class="space-y-3">
                            <?php foreach ($topBarbershops as $index => $shop): ?>
                            <div class="flex items-center space-x-4">
                                <div class="flex-shrink-0 w-8 h-8 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold">
                                    <?php echo $index + 1; ?>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-gray-900 truncate"><?php echo e($shop['business_name']); ?></p>
                                    <p class="text-sm text-gray-600"><?php echo e($shop['owner_name']); ?> - <?php echo $shop['barbers_count']; ?> barberos</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-green-600"><?php echo formatPrice($shop['total_revenue']); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo $shop['appointments_count']; ?> citas</p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Servicios Populares -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h2 class="text-lg font-semibold text-gray-900">Servicios Mas Populares</h2>
                        <p class="text-sm text-gray-600">Periodo: <?php echo formatDate($startDate); ?> - <?php echo formatDate($endDate); ?></p>
                    </div>
                    <div class="p-6">
                        <div class="space-y-3">
                            <?php foreach ($popularServices as $service): ?>
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <p class="font-semibold text-gray-900"><?php echo e($service['name']); ?></p>
                                    <p class="text-sm text-gray-600"><?php echo e($service['category']); ?></p>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-blue-600"><?php echo $service['total_bookings']; ?> reservas</p>
                                    <p class="text-sm text-gray-600"><?php echo formatPrice($service['total_revenue']); ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Crecimiento Mensual -->
            <div class="bg-white rounded-lg shadow-md mb-6">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h2 class="text-lg font-semibold text-gray-900">Crecimiento Mensual - Ultimos 6 Meses</h2>
                </div>
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-3 px-4 text-sm font-medium text-gray-700">Mes</th>
                                    <th class="text-right py-3 px-4 text-sm font-medium text-gray-700">Nuevos Owners</th>
                                    <th class="text-right py-3 px-4 text-sm font-medium text-gray-700">Nuevos Barberos</th>
                                    <th class="text-right py-3 px-4 text-sm font-medium text-gray-700">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($growthData as $month): ?>
                                <tr class="border-b border-gray-100">
                                    <td class="py-3 px-4"><?php echo date('F Y', strtotime($month['month'] . '-01')); ?></td>
                                    <td class="py-3 px-4 text-right text-blue-600 font-semibold"><?php echo $month['new_owners']; ?></td>
                                    <td class="py-3 px-4 text-right text-green-600 font-semibold"><?php echo $month['new_barbers']; ?></td>
                                    <td class="py-3 px-4 text-right text-gray-900 font-bold"><?php echo $month['new_owners'] + $month['new_barbers']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="grid lg:grid-cols-2 gap-6">
                <!-- Licencias por Vencer -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="px-6 py-4 border-b border-gray-200 bg-yellow-50">
                        <h2 class="text-lg font-semibold text-gray-900">Licencias por Vencer (30 dias)</h2>
                        <p class="text-sm text-gray-600"><?php echo count($expiringLicenses); ?> barberias</p>
                    </div>
                    <div class="p-6">
                        <?php if (count($expiringLicenses) > 0): ?>
                        <div class="space-y-3">
                            <?php foreach ($expiringLicenses as $shop): ?>
                            <div class="border-l-4 <?php echo $shop['days_remaining'] <= 7 ? 'border-red-500 bg-red-50' : 'border-yellow-500 bg-yellow-50'; ?> p-4 rounded-r-lg">
                                <div class="flex items-center justify-between mb-2">
                                    <p class="font-semibold text-gray-900"><?php echo e($shop['business_name']); ?></p>
                                    <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $shop['days_remaining'] <= 7 ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                        <?php echo $shop['days_remaining']; ?> dias
                                    </span>
                                </div>
                                <p class="text-sm text-gray-600"><?php echo e($shop['owner_name']); ?> - <?php echo e($shop['owner_email']); ?></p>
                                <p class="text-xs text-gray-500 mt-1">Vence: <?php echo formatDate($shop['license_expires_at']); ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <p class="text-center text-gray-500 py-8">No hay licencias proximas a vencer</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Estado de Citas -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h2 class="text-lg font-semibold text-gray-900">Distribucion de Citas por Estado</h2>
                        <p class="text-sm text-gray-600">Periodo: <?php echo formatDate($startDate); ?> - <?php echo formatDate($endDate); ?></p>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <?php foreach ($appointmentStats as $stat): ?>
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-gray-700 capitalize"><?php echo $stat['status']; ?></span>
                                    <span class="text-sm font-semibold text-gray-900"><?php echo $stat['total']; ?> (<?php echo $stat['percentage']; ?>%)</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-3">
                                    <div class="h-3 rounded-full
                                        <?php echo $stat['status'] === 'completed' ? 'bg-green-600' : 
                                              ($stat['status'] === 'confirmed' ? 'bg-blue-600' : 
                                              ($stat['status'] === 'pending' ? 'bg-yellow-600' : 'bg-red-600')); ?>"
                                         style="width: <?php echo min($stat['percentage'], 100); ?>%">
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include BASE_PATH . '/includes/footer.php'; ?>

