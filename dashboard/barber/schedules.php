<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Auth.php';
require_once BASE_PATH . '/core/Helpers.php';

Auth::requireRole('barber');

$db = Database::getInstance();

$barber = $db->fetch(
    "SELECT b.*, u.full_name, bb.business_name
     FROM barbers b
     JOIN users u ON b.user_id = u.id
     JOIN barbershops bb ON b.barbershop_id = bb.id
     WHERE u.id = ?
     LIMIT 1",
    [$_SESSION['user_id']]
);

if (!$barber) {
    setFlash('error', 'Barbero no encontrado');
    redirect(BASE_URL . '/dashboard/barber/index.php');
}

$barberId = (int) $barber['id'];
$barbershopId = (int) $barber['barbershop_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = input('action', 'save_schedule');

    if ($action === 'save_schedule') {
        $days = $_POST['days'] ?? [];

        try {
            $db->execute('DELETE FROM barber_schedules WHERE barber_id = ?', [$barberId]);

            for ($day = 0; $day <= 6; $day++) {
                $dayData = $days[$day] ?? [];
                $isAvailable = isset($dayData['active']) ? 1 : 0;
                $startTime = !empty($dayData['start_time']) ? $dayData['start_time'] : '09:00';
                $endTime = !empty($dayData['end_time']) ? $dayData['end_time'] : '18:00';

                if ($isAvailable && strtotime($endTime) <= strtotime($startTime)) {
                    setFlash('error', 'En cada día activo, la hora de cierre debe ser mayor que la de apertura');
                    redirect($_SERVER['PHP_SELF']);
                }

                $db->execute(
                    'INSERT INTO barber_schedules (barber_id, day_of_week, start_time, end_time, is_available) VALUES (?, ?, ?, ?, ?)',
                    [$barberId, $day, $startTime . ':00', $endTime . ':00', $isAvailable]
                );
            }

            setFlash('success', 'Horarios actualizados correctamente');
            redirect($_SERVER['PHP_SELF']);
        } catch (Exception $e) {
            setFlash('error', 'No se pudieron guardar los horarios');
            redirect($_SERVER['PHP_SELF']);
        }
    }

    if ($action === 'add_time_off') {
        $startDate = input('start_date');
        $endDate = input('end_date');
        $reason = trim((string) input('reason'));

        if (empty($startDate) || empty($endDate)) {
            setFlash('error', 'Debes seleccionar fecha de inicio y fin');
            redirect($_SERVER['PHP_SELF']);
        }

        if (strtotime($endDate) < strtotime($startDate)) {
            setFlash('error', 'La fecha fin no puede ser menor que la fecha inicio');
            redirect($_SERVER['PHP_SELF']);
        }

        $db->execute(
            'INSERT INTO time_off (barber_id, barbershop_id, start_date, end_date, reason, type) VALUES (?, ?, ?, ?, ?, ?)',
            [$barberId, $barbershopId, $startDate, $endDate, $reason ?: 'No disponible', 'other']
        );

        setFlash('success', 'Bloque de no disponibilidad agregado');
        redirect($_SERVER['PHP_SELF']);
    }

    if ($action === 'delete_time_off') {
        $timeOffId = (int) input('time_off_id');
        $db->execute('DELETE FROM time_off WHERE id = ? AND barber_id = ?', [$timeOffId, $barberId]);
        setFlash('success', 'Bloque eliminado');
        redirect($_SERVER['PHP_SELF']);
    }
}

$schedules = $db->fetchAll(
    'SELECT day_of_week, start_time, end_time, is_available FROM barber_schedules WHERE barber_id = ? ORDER BY day_of_week ASC',
    [$barberId]
);

$schedulesByDay = [];
foreach ($schedules as $schedule) {
    $schedulesByDay[(int) $schedule['day_of_week']] = $schedule;
}

$timeOffRows = $db->fetchAll(
    'SELECT id, start_date, end_date, reason FROM time_off WHERE barber_id = ? ORDER BY start_date ASC, end_date ASC',
    [$barberId]
);

$dayNames = [
    0 => 'Domingo',
    1 => 'Lunes',
    2 => 'Martes',
    3 => 'Miercoles',
    4 => 'Jueves',
    5 => 'Viernes',
    6 => 'Sabado'
];

$flash = getFlash();

$title = 'Mis Horarios - Panel Barbero';
include BASE_PATH . '/includes/header.php';
?>

<div class="min-h-screen bg-gray-100" x-data="{ sidebarOpen: false }">
    <?php $activeBarberPage = 'schedules'; include BASE_PATH . '/includes/sidebar-barber.php'; ?>

    <div class="lg:pl-64">
        <div class="sticky top-0 z-40 flex h-16 bg-white border-b border-gray-200 shadow-sm">
            <button @click="sidebarOpen = true" class="px-4 text-gray-500 lg:hidden">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <div class="flex items-center justify-between flex-1 px-4 sm:px-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Mis Horarios</h1>
                    <p class="text-sm text-gray-600">Configura tu disponibilidad para reservas en linea</p>
                </div>
            </div>
        </div>

        <main class="p-6 space-y-6">
            <?php if ($flash): ?>
            <div class="rounded-lg p-4 border-l-4 <?php echo $flash['type'] === 'success' ? 'bg-green-50 border-green-500 text-green-700' : 'bg-red-50 border-red-500 text-red-700'; ?>">
                <?php echo e($flash['message']); ?>
            </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <div class="xl:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Disponibilidad semanal</h2>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="save_schedule">

                        <?php foreach ($dayNames as $dayNum => $dayName):
                            $schedule = $schedulesByDay[$dayNum] ?? null;
                            $isActive = $schedule ? (int) $schedule['is_available'] === 1 : ($dayNum >= 1 && $dayNum <= 6);
                            $startVal = $schedule ? substr($schedule['start_time'], 0, 5) : '09:00';
                            $endVal = $schedule ? substr($schedule['end_time'], 0, 5) : '18:00';
                        ?>
                        <div class="border border-gray-200 rounded-lg p-4" x-data="{ active: <?php echo $isActive ? 'true' : 'false'; ?> }">
                            <div class="flex items-center justify-between gap-4">
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox" name="days[<?php echo $dayNum; ?>][active]" x-model="active" <?php echo $isActive ? 'checked' : ''; ?> class="h-4 w-4 text-indigo-600 rounded border-gray-300">
                                    <span class="font-medium text-gray-900"><?php echo $dayName; ?></span>
                                </label>
                                <span x-show="!active" class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-600">No disponible</span>
                            </div>

                            <div x-show="active" class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Inicio</label>
                                    <input type="time" name="days[<?php echo $dayNum; ?>][start_time]" value="<?php echo $startVal; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Fin</label>
                                    <input type="time" name="days[<?php echo $dayNum; ?>][end_time]" value="<?php echo $endVal; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <div class="pt-2">
                            <button type="submit" class="px-5 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                                Guardar Horarios
                            </button>
                        </div>
                    </form>
                </div>

                <div class="space-y-6">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Bloquear fechas</h2>
                        <form method="POST" class="space-y-3">
                            <input type="hidden" name="action" value="add_time_off">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Fecha inicio</label>
                                <input type="date" name="start_date" min="<?php echo date('Y-m-d'); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Fecha fin</label>
                                <input type="date" name="end_date" min="<?php echo date('Y-m-d'); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Motivo (opcional)</label>
                                <input type="text" name="reason" maxlength="255" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Vacaciones, evento personal...">
                            </div>
                            <button type="submit" class="w-full px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800">Agregar bloqueo</button>
                        </form>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">Fechas bloqueadas</h3>
                        <div class="space-y-3 max-h-72 overflow-y-auto pr-1">
                            <?php if (empty($timeOffRows)): ?>
                                <p class="text-sm text-gray-500">No tienes bloqueos configurados.</p>
                            <?php else: ?>
                                <?php foreach ($timeOffRows as $row): ?>
                                    <div class="border border-gray-200 rounded-lg p-3">
                                        <p class="text-sm font-medium text-gray-900"><?php echo e(formatDate($row['start_date'])); ?> - <?php echo e(formatDate($row['end_date'])); ?></p>
                                        <p class="text-xs text-gray-500 mt-1"><?php echo e($row['reason'] ?: 'No disponible'); ?></p>
                                        <form method="POST" class="mt-2">
                                            <input type="hidden" name="action" value="delete_time_off">
                                            <input type="hidden" name="time_off_id" value="<?php echo (int) $row['id']; ?>">
                                            <button type="submit" class="text-xs text-red-600 hover:text-red-700">Eliminar</button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include BASE_PATH . '/includes/footer.php'; ?>