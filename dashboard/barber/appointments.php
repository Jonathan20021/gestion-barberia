<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Auth.php';
require_once BASE_PATH . '/core/Helpers.php';

Auth::requireRole('barber');

$db = Database::getInstance();

$barber = $db->fetch(
    "SELECT b.*, u.full_name, u.email as barber_email, u.phone as barber_phone,
            bb.business_name, bb.slug as barbershop_slug, bb.phone as barbershop_phone,
            bb.email as barbershop_email, bb.address as barbershop_address, bb.city as barbershop_city,
            bb.province as barbershop_province
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

$allowedStatuses = ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled', 'no_show'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = input('action');

    if ($action === 'update_status') {
        $appointmentId = intval(input('appointment_id'));
        $newStatus = input('new_status');

        if (!in_array($newStatus, $allowedStatuses, true)) {
            setFlash('error', 'Estado invalido');
            redirect($_SERVER['PHP_SELF']);
        }

        $db->execute(
            "UPDATE appointments SET status = ?, updated_at = NOW() WHERE id = ? AND barber_id = ?",
            [$newStatus, $appointmentId, $barberId]
        );

        setFlash('success', 'Estado de cita actualizado');
        redirect($_SERVER['PHP_SELF']);
    }

    if ($action === 'toggle_payment') {
        $appointmentId = intval(input('appointment_id'));
        $newPaymentStatus = input('new_payment_status') === 'paid' ? 'paid' : 'pending';

        $db->execute(
            "UPDATE appointments SET payment_status = ?, updated_at = NOW() WHERE id = ? AND barber_id = ?",
            [$newPaymentStatus, $appointmentId, $barberId]
        );

        setFlash('success', 'Estado de pago actualizado');
        redirect($_SERVER['PHP_SELF']);
    }
}

$statusFilter = input('status', 'all');
$fromDate = input('from_date', date('Y-m-d'));
$toDate = input('to_date', date('Y-m-d', strtotime('+14 days')));

$whereClause = "a.barber_id = ? AND a.appointment_date BETWEEN ? AND ?";
$params = [$barberId, $fromDate, $toDate];

if ($statusFilter !== 'all') {
    $whereClause .= " AND a.status = ?";
    $params[] = $statusFilter;
}

$appointments = $db->fetchAll(
    "SELECT a.*, s.name as service_name, s.duration
     FROM appointments a
     JOIN services s ON a.service_id = s.id
     WHERE $whereClause
     ORDER BY a.appointment_date ASC, a.start_time ASC",
    $params
);

$stats = [
    'total' => count($appointments),
    'pending' => count(array_filter($appointments, fn($a) => $a['status'] === 'pending')),
    'confirmed' => count(array_filter($appointments, fn($a) => $a['status'] === 'confirmed')),
    'in_progress' => count(array_filter($appointments, fn($a) => $a['status'] === 'in_progress')),
    'completed' => count(array_filter($appointments, fn($a) => $a['status'] === 'completed')),
    'paid' => count(array_filter($appointments, fn($a) => $a['payment_status'] === 'paid')),
];

$flash = getFlash();
$activeBarberPage = 'appointments';
$title = 'Mis Citas - Panel Barbero';
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
                    <h1 class="text-2xl font-bold text-gray-900">Mis Citas</h1>
                    <p class="text-sm text-gray-600">Gestion diaria de citas y cobros</p>
                </div>
            </div>
        </div>

        <main class="p-6">
            <?php if ($flash): ?>
            <div class="mb-6 rounded-lg p-4 border-l-4 <?php echo $flash['type'] === 'success' ? 'bg-green-50 border-green-500 text-green-700' : 'bg-red-50 border-red-500 text-red-700'; ?>">
                <?php echo e($flash['message']); ?>
            </div>
            <?php endif; ?>

            <div class="grid grid-cols-2 md:grid-cols-6 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow p-4"><p class="text-xs text-gray-500">Total</p><p class="text-2xl font-bold text-gray-900"><?php echo $stats['total']; ?></p></div>
                <div class="bg-white rounded-lg shadow p-4"><p class="text-xs text-gray-500">Pendientes</p><p class="text-2xl font-bold text-yellow-600"><?php echo $stats['pending']; ?></p></div>
                <div class="bg-white rounded-lg shadow p-4"><p class="text-xs text-gray-500">Confirmadas</p><p class="text-2xl font-bold text-blue-600"><?php echo $stats['confirmed']; ?></p></div>
                <div class="bg-white rounded-lg shadow p-4"><p class="text-xs text-gray-500">En progreso</p><p class="text-2xl font-bold text-indigo-600"><?php echo $stats['in_progress']; ?></p></div>
                <div class="bg-white rounded-lg shadow p-4"><p class="text-xs text-gray-500">Completadas</p><p class="text-2xl font-bold text-green-600"><?php echo $stats['completed']; ?></p></div>
                <div class="bg-white rounded-lg shadow p-4"><p class="text-xs text-gray-500">Pagadas</p><p class="text-2xl font-bold text-emerald-600"><?php echo $stats['paid']; ?></p></div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm text-gray-700 mb-1">Estado</label>
                        <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>Todos</option>
                            <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pendiente</option>
                            <option value="confirmed" <?php echo $statusFilter === 'confirmed' ? 'selected' : ''; ?>>Confirmada</option>
                            <option value="in_progress" <?php echo $statusFilter === 'in_progress' ? 'selected' : ''; ?>>En progreso</option>
                            <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completada</option>
                            <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelada</option>
                            <option value="no_show" <?php echo $statusFilter === 'no_show' ? 'selected' : ''; ?>>No asistio</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700 mb-1">Desde</label>
                        <input type="date" name="from_date" value="<?php echo e($fromDate); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700 mb-1">Hasta</label>
                        <input type="date" name="to_date" value="<?php echo e($toDate); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Filtrar</button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha/Hora</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Servicio</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pago</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($appointments)): ?>
                            <tr><td colspan="6" class="px-6 py-10 text-center text-gray-500">No hay citas para los filtros seleccionados.</td></tr>
                            <?php endif; ?>

                            <?php foreach ($appointments as $apt): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <p class="text-sm font-medium text-gray-900"><?php echo formatDate($apt['appointment_date']); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo date('g:i A', strtotime($apt['start_time'])); ?> - <?php echo date('g:i A', strtotime($apt['end_time'])); ?></p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm font-medium text-gray-900"><?php echo e($apt['client_name']); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo e($apt['client_phone'] ?: 'Sin telefono'); ?></p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm text-gray-900"><?php echo e($apt['service_name']); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo formatPrice($apt['price']); ?></p>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $apt['status'] === 'completed' ? 'bg-green-100 text-green-800' : ($apt['status'] === 'confirmed' ? 'bg-blue-100 text-blue-800' : ($apt['status'] === 'in_progress' ? 'bg-indigo-100 text-indigo-800' : ($apt['status'] === 'cancelled' || $apt['status'] === 'no_show' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'))); ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $apt['status'])); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $apt['payment_status'] === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                        <?php echo $apt['payment_status'] === 'paid' ? 'Pagado' : 'Pendiente'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right text-xs space-x-1">
                                    <?php if ($apt['status'] === 'pending'): ?>
                                    <form method="POST" class="inline"><input type="hidden" name="action" value="update_status"><input type="hidden" name="appointment_id" value="<?php echo $apt['id']; ?>"><input type="hidden" name="new_status" value="confirmed"><button type="submit" class="px-2 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200">Confirmar</button></form>
                                    <?php endif; ?>
                                    <?php if ($apt['status'] === 'confirmed'): ?>
                                    <form method="POST" class="inline"><input type="hidden" name="action" value="update_status"><input type="hidden" name="appointment_id" value="<?php echo $apt['id']; ?>"><input type="hidden" name="new_status" value="in_progress"><button type="submit" class="px-2 py-1 bg-indigo-100 text-indigo-700 rounded hover:bg-indigo-200">Iniciar</button></form>
                                    <?php endif; ?>
                                    <?php if ($apt['status'] === 'confirmed' || $apt['status'] === 'in_progress'): ?>
                                    <form method="POST" class="inline"><input type="hidden" name="action" value="update_status"><input type="hidden" name="appointment_id" value="<?php echo $apt['id']; ?>"><input type="hidden" name="new_status" value="completed"><button type="submit" class="px-2 py-1 bg-green-100 text-green-700 rounded hover:bg-green-200">Completar</button></form>
                                    <?php endif; ?>
                                    <?php if (!in_array($apt['status'], ['completed', 'cancelled', 'no_show'], true)): ?>
                                    <form method="POST" class="inline" onsubmit="return confirm('Cancelar esta cita?');"><input type="hidden" name="action" value="update_status"><input type="hidden" name="appointment_id" value="<?php echo $apt['id']; ?>"><input type="hidden" name="new_status" value="cancelled"><button type="submit" class="px-2 py-1 bg-red-100 text-red-700 rounded hover:bg-red-200">Cancelar</button></form>
                                    <form method="POST" class="inline" onsubmit="return confirm('Marcar como no asistio?');"><input type="hidden" name="action" value="update_status"><input type="hidden" name="appointment_id" value="<?php echo $apt['id']; ?>"><input type="hidden" name="new_status" value="no_show"><button type="submit" class="px-2 py-1 bg-orange-100 text-orange-700 rounded hover:bg-orange-200">No-show</button></form>
                                    <?php endif; ?>
                                    <form method="POST" class="inline"><input type="hidden" name="action" value="toggle_payment"><input type="hidden" name="appointment_id" value="<?php echo $apt['id']; ?>"><input type="hidden" name="new_payment_status" value="<?php echo $apt['payment_status'] === 'paid' ? 'pending' : 'paid'; ?>"><button type="submit" class="px-2 py-1 bg-gray-100 text-gray-700 rounded hover:bg-gray-200"><?php echo $apt['payment_status'] === 'paid' ? 'Pendiente' : 'Pagado'; ?></button></form>
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
