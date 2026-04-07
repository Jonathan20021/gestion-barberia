<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Auth.php';
require_once BASE_PATH . '/core/Helpers.php';

Auth::requireRole('barber');

$db = Database::getInstance();

// Obtener información del barbero
$barber = $db->fetch("
    SELECT
        b.*,
        u.full_name,
        u.email as barber_email,
        u.phone as barber_phone,
        bb.business_name,
        bb.slug as barbershop_slug,
        bb.phone as barbershop_phone,
        bb.email as barbershop_email,
        bb.address as barbershop_address,
        bb.city as barbershop_city,
        bb.province as barbershop_province,
        bb.theme_color,
        bb.allow_online_booking
    FROM barbers b
    JOIN barbershops bb ON b.barbershop_id = bb.id
    JOIN users u ON b.user_id = u.id
    WHERE u.id = ?
    LIMIT 1
", [$_SESSION['user_id']]);

if (!$barber) {
    die('Barbero no encontrado');
}

$barberId = $barber['id'];
$barbershopId = $barber['barbershop_id'];

$statusFilter = input('status', 'all');
$fromDate = input('from_date', date('Y-m-d'));
$toDate = input('to_date', date('Y-m-d', strtotime('+7 days')));

$allowedStatuses = ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled', 'no_show'];

// Compatibilidad con enlaces antiguos tipo ?confirm=ID
if (isset($_GET['confirm'])) {
    $legacyId = intval($_GET['confirm']);
    $db->execute(
        "UPDATE appointments SET status = 'confirmed', updated_at = NOW() WHERE id = ? AND barber_id = ?",
        [$legacyId, $barberId]
    );
    setFlash('success', 'Cita confirmada');
    redirect($_SERVER['PHP_SELF']);
}

// Acciones de control de citas
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = input('action');

    if ($action === 'update_status') {
        $appointmentId = intval(input('appointment_id'));
        $newStatus = input('new_status');

        if (!in_array($newStatus, $allowedStatuses, true)) {
            setFlash('error', 'Estado inválido');
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

// Estadísticas del día
$today = date('Y-m-d');
$todayAppointments = $db->fetchAll("
    SELECT a.*, s.name as service_name, s.duration
    FROM appointments a
    JOIN services s ON a.service_id = s.id
    WHERE a.barber_id = ? AND a.appointment_date = ?
    ORDER BY a.start_time ASC
", [$barberId, $today]);

$stats = [
    'today_appointments' => count($todayAppointments),
    'pending' => count(array_filter($todayAppointments, fn($a) => $a['status'] === 'pending')),
    'completed_today' => count(array_filter($todayAppointments, fn($a) => $a['status'] === 'completed')),
    'today_earnings' => array_sum(array_column(array_filter($todayAppointments, fn($a) => $a['status'] === 'completed'), 'price')),
    'monthly_earnings' => $db->fetch("
        SELECT COALESCE(SUM(price), 0) as total 
        FROM appointments 
        WHERE barber_id = ? 
        AND status = 'completed' 
        AND MONTH(appointment_date) = MONTH(CURRENT_DATE())
    ", [$barberId])['total'],
    'total_clients' => $db->fetch("
        SELECT COUNT(DISTINCT client_id) as total 
        FROM appointments 
        WHERE barber_id = ? AND status = 'completed'
    ", [$barberId])['total'],
    'avg_rating' => $barber['rating']
];

// Agenda para control completo
$whereClause = "a.barber_id = ? AND a.appointment_date BETWEEN ? AND ?";
$params = [$barberId, $fromDate, $toDate];

if ($statusFilter !== 'all') {
    $whereClause .= " AND a.status = ?";
    $params[] = $statusFilter;
}

$agendaAppointments = $db->fetchAll(" 
    SELECT a.*, s.name as service_name, s.duration
    FROM appointments a
    JOIN services s ON a.service_id = s.id
    WHERE $whereClause
    ORDER BY a.appointment_date ASC, a.start_time ASC
", $params);

// Próximas citas (próximos 7 días)
$upcomingAppointments = $db->fetchAll("
    SELECT a.*, s.name as service_name, s.duration
    FROM appointments a
    JOIN services s ON a.service_id = s.id
    WHERE a.barber_id = ? 
    AND a.appointment_date >= CURRENT_DATE()
    AND a.appointment_date <= DATE_ADD(CURRENT_DATE(), INTERVAL 7 DAY)
    AND a.status IN ('pending', 'confirmed')
    ORDER BY a.appointment_date ASC, a.start_time ASC
    LIMIT 10
", [$barberId]);

$flash = getFlash();

$title = 'Mi Dashboard - Panel Barbero';
include BASE_PATH . '/includes/header.php';
?>

<div class="min-h-screen bg-gray-100" x-data="{ sidebarOpen: false }">
    <?php $activeBarberPage = 'index'; include BASE_PATH . '/includes/sidebar-barber.php'; ?>

    <!-- Main Content -->
    <div class="lg:pl-64">
        <div class="sticky top-0 z-40 flex h-16 bg-white border-b border-gray-200 shadow-sm">
            <button @click="sidebarOpen = true" class="px-4 text-gray-500 lg:hidden">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            
            <div class="flex items-center justify-between flex-1 px-4 sm:px-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">¡Hola, <?php echo e(explode(' ', $_SESSION['user_name'])[0]); ?>!</h1>
                    <p class="text-sm text-gray-600">Barbero en <?php echo e($barber['business_name']); ?></p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-600"><?php echo formatDate(date('Y-m-d')); ?></p>
                    <p class="text-xs text-gray-500"><?php echo date('g:i A'); ?></p>
                </div>
            </div>
        </div>

        <main class="p-6">
            <?php if ($flash): ?>
            <div class="mb-6 rounded-lg p-4 border-l-4 <?php echo $flash['type'] === 'success' ? 'bg-green-50 border-green-500 text-green-700' : 'bg-red-50 border-red-500 text-red-700'; ?>">
                <?php echo e($flash['message']); ?>
            </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
                <a href="<?php echo BASE_URL; ?>/dashboard/barber/appointments.php" class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm hover:shadow-md transition">
                    <p class="text-sm text-gray-500">Modulo dedicado</p>
                    <p class="text-lg font-semibold text-gray-900">Mis Citas</p>
                    <p class="text-sm text-indigo-600 mt-1">Ver y gestionar agenda completa</p>
                </a>
                <a href="<?php echo BASE_URL; ?>/dashboard/barber/earnings.php" class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm hover:shadow-md transition">
                    <p class="text-sm text-gray-500">Modulo dedicado</p>
                    <p class="text-lg font-semibold text-gray-900">Ingresos</p>
                    <p class="text-sm text-indigo-600 mt-1">Analisis de ganancias por rango</p>
                </a>
                <a href="<?php echo BASE_URL; ?>/dashboard/barber/profile.php" class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm hover:shadow-md transition">
                    <p class="text-sm text-gray-500">Modulo dedicado</p>
                    <p class="text-lg font-semibold text-gray-900">Mi Perfil</p>
                    <p class="text-sm text-indigo-600 mt-1">Datos personales y barberia</p>
                </a>
                <a href="<?php echo BASE_URL; ?>/dashboard/barber/schedules.php" class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm hover:shadow-md transition">
                    <p class="text-sm text-gray-500">Modulo dedicado</p>
                    <p class="text-lg font-semibold text-gray-900">Mis Horarios</p>
                    <p class="text-sm text-indigo-600 mt-1">Disponibilidad y bloqueos de fechas</p>
                </a>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 text-white">
                    <p class="text-blue-100">Citas Hoy</p>
                    <p class="text-4xl font-bold mt-2"><?php echo $stats['today_appointments']; ?></p>
                    <p class="text-sm text-blue-100 mt-2"><?php echo $stats['pending']; ?> pendientes</p>
                </div>
                
                <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-lg p-6 text-white">
                    <p class="text-green-100">Ganancia Hoy</p>
                    <p class="text-4xl font-bold mt-2"><?php echo formatPrice($stats['today_earnings']); ?></p>
                    <p class="text-sm text-green-100 mt-2"><?php echo $stats['completed_today']; ?> completadas</p>
                </div>
                
                <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow-lg p-6 text-white">
                    <p class="text-purple-100">Ganancia del Mes</p>
                    <p class="text-4xl font-bold mt-2"><?php echo formatPrice($stats['monthly_earnings']); ?></p>
                    <p class="text-sm text-purple-100 mt-2"><?php echo date('F Y'); ?></p>
                </div>
                
                <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-lg shadow-lg p-6 text-white">
                    <p class="text-yellow-100">Mi Rating</p>
                    <p class="text-4xl font-bold mt-2"><?php echo number_format($stats['avg_rating'], 1); ?> ⭐</p>
                    <p class="text-sm text-yellow-100 mt-2"><?php echo $stats['total_clients']; ?> clientes</p>
                </div>
            </div>

            <!-- Informacion de barberia -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Mi Barbería</h2>
                    <a href="<?php echo BASE_URL; ?>/public/<?php echo e($barber['barbershop_slug']); ?>" target="_blank" class="text-sm text-indigo-600 hover:text-indigo-800">Ver perfil público</a>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div>
                        <p class="text-gray-500">Nombre</p>
                        <p class="font-semibold text-gray-900"><?php echo e($barber['business_name']); ?></p>
                    </div>
                    <div>
                        <p class="text-gray-500">Contacto</p>
                        <p class="text-gray-900"><?php echo e($barber['barbershop_phone'] ?: 'Sin teléfono'); ?></p>
                        <p class="text-gray-500"><?php echo e($barber['barbershop_email'] ?: 'Sin email'); ?></p>
                    </div>
                    <div>
                        <p class="text-gray-500">Ubicación</p>
                        <p class="text-gray-900"><?php echo e($barber['barbershop_city'] ?: ''); ?> <?php echo e($barber['barbershop_province'] ?: ''); ?></p>
                        <p class="text-gray-500"><?php echo e($barber['barbershop_address'] ?: 'Dirección no definida'); ?></p>
                    </div>
                </div>
            </div>

            <div class="grid lg:grid-cols-2 gap-6">
                <!-- Citas de Hoy -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Mis Citas de Hoy</h2>
                    </div>
                    <div class="p-6">
                        <?php if (count($todayAppointments) > 0): ?>
                        <div class="space-y-4">
                            <?php foreach ($todayAppointments as $apt): ?>
                            <div class="border-l-4 <?php echo $apt['status'] === 'completed' ? 'border-green-500' : 
                                                          ($apt['status'] === 'confirmed' ? 'border-blue-500' : 'border-yellow-500'); ?> 
                                        bg-gray-50 p-4 rounded-r-lg">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="font-semibold text-gray-900">
                                        <?php echo date('g:i A', strtotime($apt['start_time'])); ?>
                                    </span>
                                    <span class="px-2 py-1 text-xs font-medium rounded-full
                                        <?php echo $apt['status'] === 'completed' ? 'bg-green-100 text-green-800' : 
                                              ($apt['status'] === 'confirmed' ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800'); ?>">
                                        <?php echo ucfirst($apt['status']); ?>
                                    </span>
                                </div>
                                <p class="text-sm font-medium text-gray-900"><?php echo e($apt['client_name']); ?></p>
                                <p class="text-sm text-gray-600"><?php echo e($apt['service_name']); ?> - <?php echo formatPrice($apt['price']); ?></p>
                                <p class="text-xs text-gray-500 mt-1"><?php echo $apt['duration']; ?> minutos</p>
                                
                                <div class="mt-3 flex space-x-2">
                                    <?php if ($apt['client_phone']): ?>
                                    <a href="https://wa.me/1<?php echo preg_replace('/[^0-9]/', '', $apt['client_phone']); ?>?text=Hola <?php echo urlencode($apt['client_name']); ?>, confirmando tu cita de hoy a las <?php echo date('g:i A', strtotime($apt['start_time'])); ?>" 
                                       target="_blank"
                                       class="flex-1 px-3 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 text-center text-sm flex items-center justify-center">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                                        </svg>
                                        Contactar
                                    </a>
                                    <?php endif; ?>
                                    <?php if ($apt['status'] === 'pending'): ?>
                                    <form method="POST" class="flex-1">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="appointment_id" value="<?php echo $apt['id']; ?>">
                                        <input type="hidden" name="new_status" value="confirmed">
                                        <button type="submit" class="w-full px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-center text-sm">
                                            Confirmar
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <p class="text-center text-gray-500 py-8">No tienes citas programadas para hoy</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Próximas Citas -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Próximas Citas (7 días)</h2>
                    </div>
                    <div class="p-6">
                        <?php if (count($upcomingAppointments) > 0): ?>
                        <div class="space-y-3">
                            <?php foreach ($upcomingAppointments as $apt): ?>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="font-semibold text-gray-900">
                                        <?php echo formatDate($apt['appointment_date']); ?>
                                    </span>
                                    <span class="text-sm text-gray-600">
                                        <?php echo date('g:i A', strtotime($apt['start_time'])); ?>
                                    </span>
                                </div>
                                <p class="text-sm font-medium text-gray-900"><?php echo e($apt['client_name']); ?></p>
                                <p class="text-sm text-gray-600"><?php echo e($apt['service_name']); ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <p class="text-center text-gray-500 py-8">No tienes próximas citas</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Agenda y control del barbero -->
            <div id="agenda" class="mt-6 bg-white rounded-lg shadow-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h2 class="text-lg font-semibold text-gray-900">Control de Citas</h2>
                    <p class="text-sm text-gray-600">Gestiona tus citas y cobros sin salir del panel</p>
                </div>

                <div class="p-6 border-b border-gray-200">
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
                                <option value="no_show" <?php echo $statusFilter === 'no_show' ? 'selected' : ''; ?>>No asistió</option>
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
                            <?php if (empty($agendaAppointments)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-gray-500">No hay citas en el rango seleccionado.</td>
                            </tr>
                            <?php endif; ?>
                            <?php foreach ($agendaAppointments as $apt): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <p class="text-sm font-medium text-gray-900"><?php echo formatDate($apt['appointment_date']); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo date('g:i A', strtotime($apt['start_time'])); ?> - <?php echo date('g:i A', strtotime($apt['end_time'])); ?></p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm font-medium text-gray-900"><?php echo e($apt['client_name']); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo e($apt['client_phone'] ?: 'Sin teléfono'); ?></p>
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
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="appointment_id" value="<?php echo $apt['id']; ?>">
                                        <input type="hidden" name="new_status" value="confirmed">
                                        <button type="submit" class="px-2 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200">Confirmar</button>
                                    </form>
                                    <?php endif; ?>

                                    <?php if ($apt['status'] === 'confirmed'): ?>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="appointment_id" value="<?php echo $apt['id']; ?>">
                                        <input type="hidden" name="new_status" value="in_progress">
                                        <button type="submit" class="px-2 py-1 bg-indigo-100 text-indigo-700 rounded hover:bg-indigo-200">Iniciar</button>
                                    </form>
                                    <?php endif; ?>

                                    <?php if ($apt['status'] === 'in_progress' || $apt['status'] === 'confirmed'): ?>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="appointment_id" value="<?php echo $apt['id']; ?>">
                                        <input type="hidden" name="new_status" value="completed">
                                        <button type="submit" class="px-2 py-1 bg-green-100 text-green-700 rounded hover:bg-green-200">Completar</button>
                                    </form>
                                    <?php endif; ?>

                                    <?php if ($apt['status'] !== 'completed' && $apt['status'] !== 'cancelled' && $apt['status'] !== 'no_show'): ?>
                                    <form method="POST" class="inline" onsubmit="return confirm('Marcar como no asistió?');">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="appointment_id" value="<?php echo $apt['id']; ?>">
                                        <input type="hidden" name="new_status" value="no_show">
                                        <button type="submit" class="px-2 py-1 bg-orange-100 text-orange-700 rounded hover:bg-orange-200">No-show</button>
                                    </form>
                                    <form method="POST" class="inline" onsubmit="return confirm('Cancelar esta cita?');">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="appointment_id" value="<?php echo $apt['id']; ?>">
                                        <input type="hidden" name="new_status" value="cancelled">
                                        <button type="submit" class="px-2 py-1 bg-red-100 text-red-700 rounded hover:bg-red-200">Cancelar</button>
                                    </form>
                                    <?php endif; ?>

                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="toggle_payment">
                                        <input type="hidden" name="appointment_id" value="<?php echo $apt['id']; ?>">
                                        <input type="hidden" name="new_payment_status" value="<?php echo $apt['payment_status'] === 'paid' ? 'pending' : 'paid'; ?>">
                                        <button type="submit" class="px-2 py-1 bg-gray-100 text-gray-700 rounded hover:bg-gray-200">
                                            <?php echo $apt['payment_status'] === 'paid' ? 'Marcar pendiente' : 'Marcar pagado'; ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Mi Página Pública -->
            <div class="mt-6 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-lg shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-bold mb-2">Mi Página de Reservas</h3>
                        <p class="text-indigo-100">Comparte este enlace con tus clientes para que reserven directamente contigo</p>
                        <p class="mt-2 bg-white/20 backdrop-blur-sm rounded px-3 py-2 font-mono text-sm inline-block">
                            <?php echo BASE_URL; ?>/public/barber.php?shop=<?php echo $barber['barbershop_slug']; ?>&barber=<?php echo $barber['slug']; ?>
                        </p>
                    </div>
                    <a href="../../public/barber.php?shop=<?php echo $barber['barbershop_slug']; ?>&barber=<?php echo $barber['slug']; ?>" 
                       target="_blank"
                       class="px-6 py-3 bg-white text-indigo-600 rounded-lg font-semibold hover:bg-gray-100">
                        Ver Mi Página
                    </a>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include BASE_PATH . '/includes/footer.php'; ?>
