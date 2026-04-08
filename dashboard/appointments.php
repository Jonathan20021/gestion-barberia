<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Auth.php';
require_once BASE_PATH . '/core/Helpers.php';

Auth::requireRole('owner');

$db = Database::getInstance();
$barbershopId = $_SESSION['barbershop_id'];

$action = input('action');
$viewId = intval(input('view', 0));

$barbers = $db->fetchAll(
    "SELECT b.id, u.full_name
     FROM barbers b
     JOIN users u ON b.user_id = u.id
     WHERE b.barbershop_id = ? AND b.status = 'active'
     ORDER BY u.full_name ASC",
    [$barbershopId]
);

$services = $db->fetchAll(
    "SELECT id, name, duration, price
     FROM services
     WHERE barbershop_id = ? AND is_active = 1
     ORDER BY name ASC",
    [$barbershopId]
);

$clients = $db->fetchAll(
    "SELECT id, name, phone, email
     FROM clients
     WHERE barbershop_id = ?
     ORDER BY name ASC",
    [$barbershopId]
);

$viewAppointment = null;
if ($viewId > 0) {
    $viewAppointment = $db->fetch(
        "SELECT a.*, s.name as service_name, s.duration as service_duration, u.full_name as barber_name
         FROM appointments a
         JOIN services s ON a.service_id = s.id
         JOIN barbers b ON a.barber_id = b.id
         JOIN users u ON b.user_id = u.id
         WHERE a.id = ? AND a.barbershop_id = ?",
        [$viewId, $barbershopId]
    );
}

// Filtros
$status = input('status', 'all');
$dateFrom = input('date_from', date('Y-m-d'));
$dateTo = input('date_to', date('Y-m-d', strtotime('+30 days')));

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = input('action');

    if ($action === 'create') {
        $barberId = intval(input('barber_id'));
        $serviceId = intval(input('service_id'));
        $appointmentDate = input('appointment_date');
        $startTime = input('start_time');
        $clientId = intval(input('client_id', 0));
        $clientName = trim((string) input('client_name'));
        $clientPhone = trim((string) input('client_phone'));
        $clientEmail = trim((string) input('client_email'));
        $notes = trim((string) input('notes'));

        if (!$barberId || !$serviceId || !$appointmentDate || !$startTime || !$clientName || !$clientPhone) {
            setFlash('error', 'Completa los campos obligatorios para crear la cita.');
            redirect($_SERVER['PHP_SELF'] . '?action=create');
        }

        if (!canCreateAppointmentForBarbershop($barbershopId, $limitMessage, $appointmentDate)) {
            setFlash('error', $limitMessage);
            redirect($_SERVER['PHP_SELF'] . '?action=create');
        }

        $service = $db->fetch(
            "SELECT duration, price FROM services WHERE id = ? AND barbershop_id = ?",
            [$serviceId, $barbershopId]
        );

        if (!$service) {
            setFlash('error', 'Servicio invalido.');
            redirect($_SERVER['PHP_SELF']);
        }

        $barberExists = $db->fetch(
            "SELECT id FROM barbers WHERE id = ? AND barbershop_id = ? AND status = 'active'",
            [$barberId, $barbershopId]
        );

        if (!$barberExists) {
            setFlash('error', 'Barbero invalido.');
            redirect($_SERVER['PHP_SELF']);
        }

        $duration = intval($service['duration']);
        $endTime = date('H:i:s', strtotime($startTime . ' +' . $duration . ' minutes'));

        $overlap = $db->fetch(
            "SELECT id FROM appointments
             WHERE barbershop_id = ? AND barber_id = ? AND appointment_date = ?
             AND status NOT IN ('cancelled', 'no_show')
             AND start_time < ? AND end_time > ?
             LIMIT 1",
            [$barbershopId, $barberId, $appointmentDate, $endTime, $startTime]
        );

        if ($overlap) {
            setFlash('error', 'El barbero ya tiene una cita en ese horario.');
            redirect($_SERVER['PHP_SELF'] . '?action=create');
        }

        if ($clientId > 0) {
            $client = $db->fetch(
                "SELECT id, name, phone, email FROM clients WHERE id = ? AND barbershop_id = ?",
                [$clientId, $barbershopId]
            );

            if ($client) {
                $clientName = $client['name'];
                $clientPhone = $client['phone'];
                $clientEmail = $client['email'];
            }
        }

        $confirmationCode = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

        $db->execute(
            "INSERT INTO appointments
             (barbershop_id, barber_id, client_id, service_id, appointment_date, start_time, end_time, status,
              client_name, client_phone, client_email, notes, price, payment_status, confirmation_code, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?, 'pending', ?, NOW())",
            [
                $barbershopId,
                $barberId,
                $clientId > 0 ? $clientId : null,
                $serviceId,
                $appointmentDate,
                $startTime,
                $endTime,
                $clientName,
                $clientPhone,
                $clientEmail ?: null,
                $notes ?: null,
                $service['price'],
                $confirmationCode
            ]
        );

        setFlash('success', 'Cita creada correctamente.');
        redirect($_SERVER['PHP_SELF']);
    }
    
    if ($action === 'update_status') {
        $appointmentId = intval(input('appointment_id'));
        $newStatus = input('new_status');

        $allowedStatuses = ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled', 'no_show'];
        if (!in_array($newStatus, $allowedStatuses, true)) {
            setFlash('error', 'Estado de cita no valido.');
            redirect($_SERVER['PHP_SELF']);
        }

        $paymentStatus = null;
        if ($newStatus === 'completed') {
            $paymentStatus = 'paid';
        } elseif (in_array($newStatus, ['cancelled', 'no_show'], true)) {
            $paymentStatus = 'pending';
        }

        $db->execute(
            "UPDATE appointments
             SET status = ?,
                 payment_status = CASE WHEN ? IS NULL THEN payment_status ELSE ? END
             WHERE id = ? AND barbershop_id = ?",
            [$newStatus, $paymentStatus, $paymentStatus, $appointmentId, $barbershopId]
        );

        if ($appointmentId > 0) {
            syncAppointmentIncomeTransaction($appointmentId);
        }
        
        setFlash('success', 'Estado de cita actualizado');
        redirect($_SERVER['PHP_SELF']);
    }
}

// Construir query
$whereClause = "a.barbershop_id = ?";
$params = [$barbershopId];

if ($status !== 'all') {
    $whereClause .= " AND a.status = ?";
    $params[] = $status;
}

$whereClause .= " AND a.appointment_date BETWEEN ? AND ?";
$params[] = $dateFrom;
$params[] = $dateTo;

// Obtener citas
$appointments = $db->fetchAll("
    SELECT 
        a.*,
        s.name as service_name,
        s.duration as service_duration,
        b.id as barber_id,
        u.full_name as barber_name
    FROM appointments a
    JOIN services s ON a.service_id = s.id
    JOIN barbers b ON a.barber_id = b.id
    JOIN users u ON b.user_id = u.id
    WHERE $whereClause
    ORDER BY a.appointment_date DESC, a.start_time DESC
", $params);

// Estadísticas
$stats = [
    'total' => count($appointments),
    'pending' => count(array_filter($appointments, fn($a) => $a['status'] === 'pending')),
    'confirmed' => count(array_filter($appointments, fn($a) => $a['status'] === 'confirmed')),
    'completed' => count(array_filter($appointments, fn($a) => $a['status'] === 'completed')),
    'cancelled' => count(array_filter($appointments, fn($a) => $a['status'] === 'cancelled')),
];

$flash = getFlash();

$title = 'Gestión de Citas - Dashboard';
include BASE_PATH . '/includes/header.php';
?>

<div class="min-h-screen bg-gray-100" x-data="{
    sidebarOpen: false,
    selectedAppointment: null,
    showStatusModal: false,
    showCreateModal: <?php echo $action === 'create' ? 'true' : 'false'; ?>,
    showViewModal: <?php echo $viewAppointment ? 'true' : 'false'; ?>
}">
    <?php include BASE_PATH . '/includes/sidebar-owner.php'; ?>

    <!-- Main Content -->
    <div class="lg:pl-64">
        <div class="sticky top-0 z-40 flex h-16 bg-white border-b border-gray-200 shadow-sm">
            <button @click="sidebarOpen = true" class="px-4 text-gray-500 lg:hidden">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            
            <div class="flex items-center justify-between flex-1 px-4 sm:px-6">
                <h1 class="text-2xl font-bold text-gray-900">Gestión de Citas</h1>
                <a href="appointments.php?action=create" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    Nueva Cita
                </a>
            </div>
        </div>

        <main class="p-6">
            <?php if ($flash): ?>
            <div class="mb-6 rounded-lg p-4 border-l-4 <?php echo $flash['type'] === 'success' ? 'bg-green-50 border-green-500 text-green-700' : 'bg-red-50 border-red-500 text-red-700'; ?>">
                <?php echo e($flash['message']); ?>
            </div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow p-4">
                    <p class="text-sm text-gray-600">Total</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total']; ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <p class="text-sm text-gray-600">Pendientes</p>
                    <p class="text-2xl font-bold text-yellow-600"><?php echo $stats['pending']; ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <p class="text-sm text-gray-600">Confirmadas</p>
                    <p class="text-2xl font-bold text-green-600"><?php echo $stats['confirmed']; ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <p class="text-sm text-gray-600">Completadas</p>
                    <p class="text-2xl font-bold text-blue-600"><?php echo $stats['completed']; ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <p class="text-sm text-gray-600">Canceladas</p>
                    <p class="text-2xl font-bold text-red-600"><?php echo $stats['cancelled']; ?></p>
                </div>
            </div>

            <!-- Filtros -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                        <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>Todos</option>
                            <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pendiente</option>
                            <option value="confirmed" <?php echo $status === 'confirmed' ? 'selected' : ''; ?>>Confirmada</option>
                            <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completada</option>
                            <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelada</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Desde</label>
                        <input type="date" name="date_from" value="<?php echo $dateFrom; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Hasta</label>
                        <input type="date" name="date_to" value="<?php echo $dateTo; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                            Filtrar
                        </button>
                    </div>
                </form>
            </div>

            <!-- Tabla de Citas -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha/Hora</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Barbero</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Servicio</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Precio</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($appointments as $apt): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo formatDate($apt['appointment_date']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo date('g:i A', strtotime($apt['start_time'])); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900"><?php echo e($apt['client_name']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo e($apt['client_phone']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo e($apt['barber_name']); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?php echo e($apt['service_name']); ?></div>
                                    <div class="text-xs text-gray-500"><?php echo $apt['service_duration']; ?> min</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo formatPrice($apt['price']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full
                                        <?php 
                                            echo $apt['status'] === 'completed' ? 'bg-blue-100 text-blue-800' :
                                                ($apt['status'] === 'confirmed' ? 'bg-green-100 text-green-800' :
                                                ($apt['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                                'bg-red-100 text-red-800')); 
                                        ?>">
                                        <?php echo ucfirst($apt['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                    <button @click="selectedAppointment = <?php echo $apt['id']; ?>; showStatusModal = true" 
                                            class="text-indigo-600 hover:text-indigo-900">Cambiar Estado</button>
                                    <a href="appointments.php?view=<?php echo $apt['id']; ?>" class="text-blue-600 hover:text-blue-900">Ver</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Cambiar Estado -->
    <div x-show="showStatusModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="showStatusModal = false"></div>
            
            <div class="relative bg-white rounded-lg max-w-md w-full p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Cambiar Estado de Cita</h3>
                
                <form method="POST">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="appointment_id" :value="selectedAppointment">
                    
                    <div class="space-y-2">
                        <button type="submit" name="new_status" value="confirmed" 
                                class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                            Confirmar
                        </button>
                        <button type="submit" name="new_status" value="in_progress" 
                                class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            En Progreso
                        </button>
                        <button type="submit" name="new_status" value="completed" 
                                class="w-full px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                            Completar
                        </button>
                        <button type="submit" name="new_status" value="cancelled" 
                                class="w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Ver Cita -->
    <div x-show="showViewModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="window.location = 'appointments.php'"></div>

            <div class="relative bg-white rounded-lg max-w-2xl w-full p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Detalle de Cita</h3>
                    <a href="appointments.php" class="text-gray-500 hover:text-gray-700">Cerrar</a>
                </div>

                <?php if ($viewAppointment): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div class="bg-gray-50 rounded p-3">
                        <p class="text-gray-500">Cliente</p>
                        <p class="font-semibold text-gray-900"><?php echo e($viewAppointment['client_name']); ?></p>
                        <p class="text-gray-700"><?php echo e($viewAppointment['client_phone']); ?></p>
                        <p class="text-gray-500"><?php echo e($viewAppointment['client_email'] ?? ''); ?></p>
                    </div>
                    <div class="bg-gray-50 rounded p-3">
                        <p class="text-gray-500">Servicio</p>
                        <p class="font-semibold text-gray-900"><?php echo e($viewAppointment['service_name']); ?></p>
                        <p class="text-gray-700">Duracion: <?php echo intval($viewAppointment['service_duration']); ?> min</p>
                        <p class="text-gray-700">Precio: <?php echo formatPrice($viewAppointment['price']); ?></p>
                    </div>
                    <div class="bg-gray-50 rounded p-3">
                        <p class="text-gray-500">Barbero</p>
                        <p class="font-semibold text-gray-900"><?php echo e($viewAppointment['barber_name']); ?></p>
                    </div>
                    <div class="bg-gray-50 rounded p-3">
                        <p class="text-gray-500">Fecha y Hora</p>
                        <p class="font-semibold text-gray-900"><?php echo formatDate($viewAppointment['appointment_date']); ?></p>
                        <p class="text-gray-700"><?php echo date('g:i A', strtotime($viewAppointment['start_time'])); ?> - <?php echo date('g:i A', strtotime($viewAppointment['end_time'])); ?></p>
                    </div>
                    <div class="bg-gray-50 rounded p-3 md:col-span-2">
                        <p class="text-gray-500">Notas</p>
                        <p class="text-gray-800"><?php echo e($viewAppointment['notes'] ?? 'Sin notas'); ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Crear Cita -->
    <div x-show="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="window.location = 'appointments.php'"></div>

            <div class="relative bg-white rounded-lg max-w-2xl w-full p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Nueva Cita</h3>
                    <a href="appointments.php" class="text-gray-500 hover:text-gray-700">Cerrar</a>
                </div>

                <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <input type="hidden" name="action" value="create">

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cliente existente</label>
                        <select name="client_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            <option value="0">Seleccionar cliente (opcional)</option>
                            <?php foreach ($clients as $client): ?>
                            <option value="<?php echo $client['id']; ?>"><?php echo e($client['name']); ?> - <?php echo e($client['phone']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre cliente *</label>
                        <input type="text" name="client_name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Telefono *</label>
                        <input type="text" name="client_phone" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="client_email" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Barbero *</label>
                        <select name="barber_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            <option value="">Seleccionar</option>
                            <?php foreach ($barbers as $barber): ?>
                            <option value="<?php echo $barber['id']; ?>"><?php echo e($barber['full_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Servicio *</label>
                        <select name="service_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            <option value="">Seleccionar</option>
                            <?php foreach ($services as $service): ?>
                            <option value="<?php echo $service['id']; ?>"><?php echo e($service['name']); ?> (<?php echo intval($service['duration']); ?> min - <?php echo formatPrice($service['price']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha *</label>
                        <input type="date" name="appointment_date" min="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Hora inicio *</label>
                        <input type="time" name="start_time" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
                        <textarea name="notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg"></textarea>
                    </div>

                    <div class="md:col-span-2 flex gap-3 pt-2">
                        <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Crear Cita</button>
                        <a href="appointments.php" class="flex-1 text-center px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include BASE_PATH . '/includes/footer.php'; ?>
