<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Auth.php';
require_once BASE_PATH . '/core/Helpers.php';

Auth::requireRole('barber');

$db = Database::getInstance();

$barber = $db->fetch(
    "SELECT b.*, u.full_name, bb.business_name, bb.slug as barbershop_slug
     FROM barbers b
     JOIN barbershops bb ON b.barbershop_id = bb.id
     JOIN users u ON b.user_id = u.id
     WHERE u.id = ?
     LIMIT 1",
    [$_SESSION['user_id']]
);

if (!$barber) {
    die('Barbero no encontrado');
}

$barberId = $barber['id'];
$commissionRate = floatval($barber['commission_rate'] ?: 100);

$fromDate = input('from_date', date('Y-m-01'));
$toDate = input('to_date', date('Y-m-d'));

$todayGross = $db->fetch("SELECT COALESCE(SUM(price), 0) AS total FROM appointments WHERE barber_id = ? AND status = 'completed' AND appointment_date = CURDATE()", [$barberId])['total'];
$monthGross = $db->fetch("SELECT COALESCE(SUM(price), 0) AS total FROM appointments WHERE barber_id = ? AND status = 'completed' AND YEAR(appointment_date)=YEAR(CURDATE()) AND MONTH(appointment_date)=MONTH(CURDATE())", [$barberId])['total'];

$rangeSummary = $db->fetch(
    "SELECT COUNT(*) AS completed_count, COALESCE(SUM(price), 0) AS gross
     FROM appointments
     WHERE barber_id = ?
     AND status = 'completed'
     AND appointment_date BETWEEN ? AND ?",
    [$barberId, $fromDate, $toDate]
);

$grossRange = floatval($rangeSummary['gross']);
$netRange = $grossRange * ($commissionRate / 100);

$byService = $db->fetchAll(
    "SELECT s.name, COUNT(*) AS total_citas, COALESCE(SUM(a.price), 0) AS total_bruto
     FROM appointments a
     JOIN services s ON a.service_id = s.id
     WHERE a.barber_id = ?
     AND a.status = 'completed'
     AND a.appointment_date BETWEEN ? AND ?
     GROUP BY s.id
     ORDER BY total_bruto DESC",
    [$barberId, $fromDate, $toDate]
);

$recentPayments = $db->fetchAll(
    "SELECT a.appointment_date, a.start_time, a.client_name, a.price, a.payment_status, s.name AS service_name
     FROM appointments a
     JOIN services s ON a.service_id = s.id
     WHERE a.barber_id = ?
     AND a.status = 'completed'
     AND a.appointment_date BETWEEN ? AND ?
     ORDER BY a.appointment_date DESC, a.start_time DESC
     LIMIT 20",
    [$barberId, $fromDate, $toDate]
);

$activeBarberPage = 'earnings';
$title = 'Ingresos - Panel Barbero';
include BASE_PATH . '/includes/header.php';
?>

<div class="min-h-screen bg-gray-100" x-data="{ sidebarOpen: false }">
    <?php include BASE_PATH . '/includes/sidebar-barber.php'; ?>

    <div class="lg:pl-64">
        <div class="sticky top-0 z-40 flex h-16 bg-white border-b border-gray-200 shadow-sm">
            <button @click="sidebarOpen = true" class="px-4 text-gray-500 lg:hidden">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <div class="flex items-center justify-between flex-1 px-4 sm:px-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Ingresos</h1>
                    <p class="text-sm text-gray-600"><?php echo e($barber['business_name']); ?> - comisión <?php echo number_format($commissionRate, 1); ?>%</p>
                </div>
            </div>
        </div>

        <main class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow p-5 text-white">
                    <p class="text-sm text-blue-100">Bruto Hoy</p>
                    <p class="text-3xl font-bold"><?php echo formatPrice($todayGross); ?></p>
                </div>
                <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-lg shadow p-5 text-white">
                    <p class="text-sm text-indigo-100">Bruto Mes</p>
                    <p class="text-3xl font-bold"><?php echo formatPrice($monthGross); ?></p>
                </div>
                <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow p-5 text-white">
                    <p class="text-sm text-green-100">Bruto Rango</p>
                    <p class="text-3xl font-bold"><?php echo formatPrice($grossRange); ?></p>
                    <p class="text-sm text-green-100"><?php echo intval($rangeSummary['completed_count']); ?> citas completadas</p>
                </div>
                <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-lg shadow p-5 text-white">
                    <p class="text-sm text-emerald-100">Neto Estimado</p>
                    <p class="text-3xl font-bold"><?php echo formatPrice($netRange); ?></p>
                    <p class="text-sm text-emerald-100">Aplicando comisión</p>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm text-gray-700 mb-1">Desde</label>
                        <input type="date" name="from_date" value="<?php echo e($fromDate); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700 mb-1">Hasta</label>
                        <input type="date" name="to_date" value="<?php echo e($toDate); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Aplicar</button>
                    </div>
                </form>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h2 class="text-lg font-semibold text-gray-900">Ingresos por servicio</h2>
                    </div>
                    <div class="p-6">
                        <?php if (empty($byService)): ?>
                        <p class="text-gray-500 text-center py-6">No hay datos para el rango seleccionado.</p>
                        <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($byService as $row): ?>
                            <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50">
                                <div>
                                    <p class="font-medium text-gray-900"><?php echo e($row['name']); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo intval($row['total_citas']); ?> citas</p>
                                </div>
                                <p class="font-semibold text-green-600"><?php echo formatPrice($row['total_bruto']); ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h2 class="text-lg font-semibold text-gray-900">Ultimos pagos</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Servicio</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Monto</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($recentPayments)): ?>
                                <tr><td colspan="4" class="px-4 py-8 text-center text-gray-500">Sin pagos en este rango.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($recentPayments as $payment): ?>
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo formatDate($payment['appointment_date']); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo e($payment['client_name']); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-700"><?php echo e($payment['service_name']); ?></td>
                                    <td class="px-4 py-3 text-sm font-semibold <?php echo $payment['payment_status'] === 'paid' ? 'text-green-600' : 'text-yellow-700'; ?>"><?php echo formatPrice($payment['price']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include BASE_PATH . '/includes/footer.php'; ?>
