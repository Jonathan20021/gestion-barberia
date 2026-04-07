<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Auth.php';
require_once BASE_PATH . '/core/Helpers.php';

Auth::requireRole('owner');

$db = Database::getInstance();
$barbershopId = $_SESSION['barbershop_id'];

$barbers = $db->fetchAll(
    "SELECT b.id, b.status, u.full_name
     FROM barbers b
     JOIN users u ON b.user_id = u.id
     WHERE b.barbershop_id = ?
     ORDER BY u.full_name ASC",
    [$barbershopId]
);

if (empty($barbers)) {
    setFlash('error', 'No hay barberos registrados para gestionar horarios');
    redirect(BASE_URL . '/dashboard/barbers.php');
}

$selectedBarberId = (int) input('barber_id', $barbers[0]['id']);
$selectedBarber = null;
foreach ($barbers as $barberItem) {
    if ((int) $barberItem['id'] === $selectedBarberId) {
        $selectedBarber = $barberItem;
        break;
    }
}

if (!$selectedBarber) {
    $selectedBarberId = (int) $barbers[0]['id'];
    $selectedBarber = $barbers[0];
}

$intervalOptions = [5, 10, 15, 20, 30, 60];
$intervalSetting = $db->fetch(
    'SELECT setting_value FROM barbershop_settings WHERE barbershop_id = ? AND setting_key = ? LIMIT 1',
    [$barbershopId, 'booking_interval_minutes']
);
$bookingInterval = $intervalSetting ? (int) $intervalSetting['setting_value'] : 15;
if (!in_array($bookingInterval, $intervalOptions, true)) {
    $bookingInterval = 15;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = input('action');
    $postBarberId = (int) input('barber_id');

    $barberExists = $db->fetch(
        'SELECT id FROM barbers WHERE id = ? AND barbershop_id = ? LIMIT 1',
        [$postBarberId, $barbershopId]
    );

    if (!$barberExists) {
        setFlash('error', 'Barbero inválido para esta barbería');
        redirect(BASE_URL . '/dashboard/barber-schedules.php');
    }

    if ($action === 'save_schedule') {
        $days = $_POST['days'] ?? [];

        for ($day = 0; $day <= 6; $day++) {
            $dayData = $days[$day] ?? [];
            $isActive = isset($dayData['active']) ? 1 : 0;
            $startTime = !empty($dayData['start_time']) ? $dayData['start_time'] : '09:00';
            $endTime = !empty($dayData['end_time']) ? $dayData['end_time'] : '18:00';

            if ($isActive && strtotime($endTime) <= strtotime($startTime)) {
                setFlash('error', 'La hora de cierre debe ser mayor que la de apertura en los días activos');
                redirect(BASE_URL . '/dashboard/barber-schedules.php?barber_id=' . $postBarberId);
            }
        }

        $db->execute('DELETE FROM barber_schedules WHERE barber_id = ?', [$postBarberId]);

        for ($day = 0; $day <= 6; $day++) {
            $dayData = $days[$day] ?? [];
            $isActive = isset($dayData['active']) ? 1 : 0;
            $startTime = !empty($dayData['start_time']) ? $dayData['start_time'] : '09:00';
            $endTime = !empty($dayData['end_time']) ? $dayData['end_time'] : '18:00';

            $db->execute(
                'INSERT INTO barber_schedules (barber_id, day_of_week, start_time, end_time, is_available) VALUES (?, ?, ?, ?, ?)',
                [$postBarberId, $day, $startTime . ':00', $endTime . ':00', $isActive]
            );
        }

        setFlash('success', 'Horarios actualizados correctamente');
        redirect(BASE_URL . '/dashboard/barber-schedules.php?barber_id=' . $postBarberId);
    }

    if ($action === 'save_interval') {
        $newInterval = (int) input('booking_interval_minutes', 15);
        if (!in_array($newInterval, $intervalOptions, true)) {
            setFlash('error', 'Intervalo inválido');
            redirect(BASE_URL . '/dashboard/barber-schedules.php?barber_id=' . $postBarberId);
        }

        $db->execute(
            "INSERT INTO barbershop_settings (barbershop_id, setting_key, setting_value)
             VALUES (?, 'booking_interval_minutes', ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)",
            [$barbershopId, (string) $newInterval]
        );

        setFlash('success', 'Intervalo de agenda actualizado');
        redirect(BASE_URL . '/dashboard/barber-schedules.php?barber_id=' . $postBarberId);
    }

    if ($action === 'add_time_off') {
        $startDate = input('start_date');
        $endDate = input('end_date');
        $reason = trim((string) input('reason'));

        if (empty($startDate) || empty($endDate)) {
            setFlash('error', 'Selecciona fecha de inicio y fin');
            redirect(BASE_URL . '/dashboard/barber-schedules.php?barber_id=' . $postBarberId);
        }

        if (strtotime($endDate) < strtotime($startDate)) {
            setFlash('error', 'La fecha fin no puede ser menor que la fecha inicio');
            redirect(BASE_URL . '/dashboard/barber-schedules.php?barber_id=' . $postBarberId);
        }

        $db->execute(
            'INSERT INTO time_off (barber_id, barbershop_id, start_date, end_date, reason, type) VALUES (?, ?, ?, ?, ?, ?)',
            [$postBarberId, $barbershopId, $startDate, $endDate, $reason ?: 'No disponible', 'other']
        );

        setFlash('success', 'Bloque de no disponibilidad agregado');
        redirect(BASE_URL . '/dashboard/barber-schedules.php?barber_id=' . $postBarberId);
    }

    if ($action === 'delete_time_off') {
        $timeOffId = (int) input('time_off_id');
        $db->execute(
            'DELETE FROM time_off WHERE id = ? AND barber_id = ? AND barbershop_id = ?',
            [$timeOffId, $postBarberId, $barbershopId]
        );

        setFlash('success', 'Bloque eliminado');
        redirect(BASE_URL . '/dashboard/barber-schedules.php?barber_id=' . $postBarberId);
    }
}

$scheduleRows = $db->fetchAll(
    'SELECT day_of_week, start_time, end_time, is_available FROM barber_schedules WHERE barber_id = ? ORDER BY day_of_week ASC',
    [$selectedBarberId]
);

$schedulesByDay = [];
foreach ($scheduleRows as $row) {
    $schedulesByDay[(int) $row['day_of_week']] = $row;
}

$timeOffRows = $db->fetchAll(
    'SELECT id, start_date, end_date, reason FROM time_off WHERE barber_id = ? AND barbershop_id = ? ORDER BY start_date ASC, end_date ASC',
    [$selectedBarberId, $barbershopId]
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

$weeklyBlocks = [];
$weeklyTotalHours = 0;
for ($day = 0; $day <= 6; $day++) {
    $row = $schedulesByDay[$day] ?? null;
    $isActive = $row ? (int) $row['is_available'] === 1 : false;

    $start = $row ? substr($row['start_time'], 0, 5) : '09:00';
    $end = $row ? substr($row['end_time'], 0, 5) : '18:00';

    $startTs = strtotime($start);
    $endTs = strtotime($end);
    $workSeconds = ($isActive && $endTs > $startTs) ? ($endTs - $startTs) : 0;
    $hours = $workSeconds / 3600;
    $weeklyTotalHours += $hours;

    $startMinutes = (int) date('G', $startTs) * 60 + (int) date('i', $startTs);
    $leftPercent = max(0, min(100, ($startMinutes / 1440) * 100));
    $widthPercent = max(0, min(100 - $leftPercent, ($workSeconds / 86400) * 100));

    $weeklyBlocks[$day] = [
        'name' => $dayNames[$day],
        'is_active' => $isActive,
        'start' => $start,
        'end' => $end,
        'hours' => $hours,
        'left' => $leftPercent,
        'width' => $widthPercent
    ];
}

$flash = getFlash();

$title = 'Horarios de Barberos - Dashboard';
include BASE_PATH . '/includes/header.php';
?>

<div class="min-h-screen bg-gray-100" x-data="{ sidebarOpen: false }">
    <?php include BASE_PATH . '/includes/sidebar-owner.php'; ?>

    <div class="lg:pl-64">
        <div class="sticky top-0 z-40 flex h-16 bg-white border-b border-gray-200 shadow-sm">
            <button @click="sidebarOpen = true" class="px-4 text-gray-500 lg:hidden">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <div class="flex items-center justify-between flex-1 px-4 sm:px-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Horarios de Barberos</h1>
                    <p class="text-sm text-gray-600">Gestiona disponibilidad por barbero en tu barbería</p>
                </div>
                <a href="<?php echo BASE_URL; ?>/dashboard/barbers.php" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">Volver a Barberos</a>
            </div>
        </div>

        <main class="p-6 space-y-6">
            <?php if ($flash): ?>
                <div class="rounded-lg p-4 border-l-4 <?php echo $flash['type'] === 'success' ? 'bg-green-50 border-green-500 text-green-700' : 'bg-red-50 border-red-500 text-red-700'; ?>">
                    <?php echo e($flash['message']); ?>
                </div>
            <?php endif; ?>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <form method="GET" class="flex flex-col sm:flex-row items-center gap-3">
                        <label class="text-sm font-semibold text-gray-700">Barbero</label>
                        <select name="barber_id" onchange="this.form.submit()" class="w-full sm:w-80 px-3 py-2 border border-gray-300 rounded-lg">
                            <?php foreach ($barbers as $item): ?>
                                <option value="<?php echo (int) $item['id']; ?>" <?php echo (int) $item['id'] === (int) $selectedBarberId ? 'selected' : ''; ?>>
                                    <?php echo e($item['full_name']); ?> (<?php echo $item['status'] === 'active' ? 'Activo' : 'Inactivo'; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>

                    <form method="POST" class="flex flex-col sm:flex-row items-center justify-start lg:justify-end gap-3">
                        <input type="hidden" name="action" value="save_interval">
                        <input type="hidden" name="barber_id" value="<?php echo (int) $selectedBarberId; ?>">
                        <label class="text-sm font-semibold text-gray-700">Intervalo de agenda</label>
                        <select name="booking_interval_minutes" class="w-full sm:w-44 px-3 py-2 border border-gray-300 rounded-lg">
                            <?php foreach ($intervalOptions as $option): ?>
                                <option value="<?php echo $option; ?>" <?php echo $bookingInterval === $option ? 'selected' : ''; ?>>
                                    <?php echo $option; ?> minutos
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800">Guardar</button>
                    </form>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-5">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Vista semanal visual</h2>
                        <p class="text-sm text-gray-600">Bloques de disponibilidad para <?php echo e($selectedBarber['full_name']); ?></p>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="px-4 py-2 rounded-lg bg-indigo-50 border border-indigo-100 text-center">
                            <p class="text-xs text-indigo-700">Horas / semana</p>
                            <p class="text-lg font-bold text-indigo-900"><?php echo number_format($weeklyTotalHours, 1); ?>h</p>
                        </div>
                        <div class="px-4 py-2 rounded-lg bg-gray-50 border border-gray-200 text-center">
                            <p class="text-xs text-gray-600">Días activos</p>
                            <p class="text-lg font-bold text-gray-900"><?php echo count(array_filter($weeklyBlocks, fn($b) => $b['is_active'])); ?>/7</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-3">
                    <?php foreach ($weeklyBlocks as $block): ?>
                        <div class="grid grid-cols-12 items-center gap-3">
                            <div class="col-span-12 sm:col-span-2">
                                <p class="text-sm font-semibold text-gray-900"><?php echo e($block['name']); ?></p>
                                <p class="text-xs <?php echo $block['is_active'] ? 'text-green-700' : 'text-gray-500'; ?>">
                                    <?php echo $block['is_active'] ? (e($block['start']) . ' - ' . e($block['end'])) : 'No disponible'; ?>
                                </p>
                            </div>
                            <div class="col-span-12 sm:col-span-10">
                                <div class="relative h-8 rounded-lg bg-gray-100 border border-gray-200 overflow-hidden">
                                    <?php if ($block['is_active'] && $block['width'] > 0): ?>
                                        <div class="absolute inset-y-0 rounded-lg bg-gradient-to-r from-indigo-500 to-indigo-600"
                                             style="left: <?php echo number_format($block['left'], 3, '.', ''); ?>%; width: <?php echo number_format($block['width'], 3, '.', ''); ?>%;"></div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex justify-between text-[11px] text-gray-500 mt-1 px-1">
                                    <span>00:00</span>
                                    <span>06:00</span>
                                    <span>12:00</span>
                                    <span>18:00</span>
                                    <span>24:00</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <div class="xl:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Disponibilidad semanal de <?php echo e($selectedBarber['full_name']); ?></h2>

                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="save_schedule">
                        <input type="hidden" name="barber_id" value="<?php echo (int) $selectedBarberId; ?>">

                        <?php foreach ($dayNames as $dayNum => $dayName):
                            $schedule = $schedulesByDay[$dayNum] ?? null;
                            $isActive = $schedule ? (int) $schedule['is_available'] === 1 : ($dayNum >= 1 && $dayNum <= 6);
                            $startVal = $schedule ? substr($schedule['start_time'], 0, 5) : '09:00';
                            $endVal = $schedule ? substr($schedule['end_time'], 0, 5) : '18:00';
                        ?>
                            <div class="border border-gray-200 rounded-lg p-4" x-data="{ active: <?php echo $isActive ? 'true' : 'false'; ?> }">
                                <div class="flex items-center justify-between gap-4">
                                    <label class="flex items-center gap-3 cursor-pointer">
                                        <input type="checkbox" name="days[<?php echo $dayNum; ?>][active]" x-model="active" <?php echo $isActive ? 'checked' : ''; ?> class="h-4 w-4 text-indigo-600 rounded border-gray-300 js-day-active" data-day="<?php echo $dayNum; ?>">
                                        <span class="font-medium text-gray-900"><?php echo $dayName; ?></span>
                                    </label>
                                    <span x-show="!active" class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-600">No disponible</span>
                                </div>

                                <div x-show="active" class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Inicio</label>
                                        <input type="time" id="day-start-<?php echo $dayNum; ?>" name="days[<?php echo $dayNum; ?>][start_time]" value="<?php echo $startVal; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg js-day-start" data-day="<?php echo $dayNum; ?>">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Fin</label>
                                        <input type="time" id="day-end-<?php echo $dayNum; ?>" name="days[<?php echo $dayNum; ?>][end_time]" value="<?php echo $endVal; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg js-day-end" data-day="<?php echo $dayNum; ?>">
                                    </div>
                                </div>

                                <div x-show="active" class="mt-3">
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="block text-xs font-semibold text-gray-600">Arrastra en la barra para definir horario</label>
                                        <button type="button" class="text-xs text-indigo-600 hover:text-indigo-700 js-range-reset" data-day="<?php echo $dayNum; ?>">Reset</button>
                                    </div>
                                    <div class="relative h-8 rounded-lg border border-gray-300 bg-gradient-to-r from-gray-50 to-gray-100 overflow-hidden select-none touch-none cursor-ew-resize js-range-picker"
                                         data-day="<?php echo $dayNum; ?>"
                                         style="background-size: calc(100% / 24) 100%; background-image: repeating-linear-gradient(to right, transparent, transparent calc((100% / 24) - 1px), rgba(156, 163, 175, 0.28) calc((100% / 24) - 1px), rgba(156, 163, 175, 0.28) calc(100% / 24));">
                                        <div class="absolute inset-y-0 rounded-md bg-gradient-to-r from-indigo-500 to-indigo-600 js-range-fill"></div>
                                    </div>
                                    <p id="day-range-label-<?php echo $dayNum; ?>" class="text-[11px] text-gray-500 mt-1">Rango: <?php echo e($startVal); ?> - <?php echo e($endVal); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <button type="submit" class="px-5 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Guardar Horarios</button>
                    </form>
                </div>

                <div class="space-y-6">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Bloquear fechas</h3>

                        <form method="POST" class="space-y-3">
                            <input type="hidden" name="action" value="add_time_off">
                            <input type="hidden" name="barber_id" value="<?php echo (int) $selectedBarberId; ?>">
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
                                <p class="text-sm text-gray-500">No hay fechas bloqueadas para este barbero.</p>
                            <?php else: ?>
                                <?php foreach ($timeOffRows as $row): ?>
                                    <div class="border border-gray-200 rounded-lg p-3">
                                        <p class="text-sm font-medium text-gray-900"><?php echo e(formatDate($row['start_date'])); ?> - <?php echo e(formatDate($row['end_date'])); ?></p>
                                        <p class="text-xs text-gray-500 mt-1"><?php echo e($row['reason'] ?: 'No disponible'); ?></p>
                                        <form method="POST" class="mt-2">
                                            <input type="hidden" name="action" value="delete_time_off">
                                            <input type="hidden" name="barber_id" value="<?php echo (int) $selectedBarberId; ?>">
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

<script>
(() => {
    function clamp(value, min, max) {
        return Math.min(max, Math.max(min, value));
    }

    function snapToQuarter(minute) {
        return clamp(Math.round(minute / 15) * 15, 0, 1435);
    }

    function toMinutes(hhmm) {
        if (!hhmm || hhmm.indexOf(':') === -1) return 0;
        const parts = hhmm.split(':');
        return (parseInt(parts[0], 10) * 60) + parseInt(parts[1], 10);
    }

    function toTime(minutes) {
        const mins = clamp(Math.round(minutes), 0, 1439);
        const h = Math.floor(mins / 60);
        const m = mins % 60;
        return String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0');
    }

    function updateLabel(day, start, end) {
        const label = document.getElementById('day-range-label-' + day);
        if (label) {
            label.textContent = 'Rango: ' + start + ' - ' + end;
        }
    }

    function setFill(fillEl, startMin, endMin) {
        const left = clamp((startMin / 1440) * 100, 0, 100);
        const width = clamp(((endMin - startMin) / 1440) * 100, 0, 100 - left);
        fillEl.style.left = left + '%';
        fillEl.style.width = width + '%';
    }

    function getMinuteFromEvent(event, pickerEl) {
        const rect = pickerEl.getBoundingClientRect();
        const x = clamp(event.clientX - rect.left, 0, rect.width);
        const ratio = rect.width > 0 ? x / rect.width : 0;
        return snapToQuarter(ratio * 1440);
    }

    const pickers = document.querySelectorAll('.js-range-picker');

    pickers.forEach((picker) => {
        const day = picker.dataset.day;
        const startInput = document.getElementById('day-start-' + day);
        const endInput = document.getElementById('day-end-' + day);
        const activeCheckbox = document.querySelector('.js-day-active[data-day="' + day + '"]');
        const fill = picker.querySelector('.js-range-fill');

        let dragging = false;
        let anchor = 0;
        let pointerId = null;

        function syncFromInputs() {
            let start = toMinutes(startInput.value || '09:00');
            let end = toMinutes(endInput.value || '18:00');

            if (end <= start) {
                end = clamp(start + 60, 0, 1439);
            }

            setFill(fill, start, end);
            updateLabel(day, toTime(start), toTime(end));
        }

        function applyRange(start, end) {
            let from = clamp(Math.min(start, end), 0, 1435);
            let to = clamp(Math.max(start, end), 0, 1439);

            if (to <= from) {
                to = clamp(from + 15, 0, 1439);
            }

            startInput.value = toTime(from);
            endInput.value = toTime(to);
            activeCheckbox.checked = true;
            activeCheckbox.dispatchEvent(new Event('change'));

            setFill(fill, from, to);
            updateLabel(day, startInput.value, endInput.value);
        }

        picker.addEventListener('pointerdown', (event) => {
            event.preventDefault();
            dragging = true;
            pointerId = event.pointerId;
            anchor = getMinuteFromEvent(event, picker);
            applyRange(anchor, anchor + 60);
            if (picker.setPointerCapture) {
                picker.setPointerCapture(pointerId);
            }
        });

        picker.addEventListener('pointermove', (event) => {
            if (!dragging || event.pointerId !== pointerId) return;
            const current = getMinuteFromEvent(event, picker);
            applyRange(anchor, current);
        });

        const stopDragging = (event) => {
            if (!dragging) return;
            if (event && pointerId !== null && event.pointerId !== pointerId) return;
            dragging = false;
            if (picker.releasePointerCapture && pointerId !== null) {
                try {
                    picker.releasePointerCapture(pointerId);
                } catch (error) {
                    // Ignorar si el puntero ya fue liberado.
                }
            }
            pointerId = null;
        };

        picker.addEventListener('pointerup', stopDragging);
        picker.addEventListener('pointercancel', stopDragging);
        picker.addEventListener('lostpointercapture', stopDragging);

        startInput.addEventListener('change', syncFromInputs);
        endInput.addEventListener('change', syncFromInputs);

        const resetBtn = document.querySelector('.js-range-reset[data-day="' + day + '"]');
        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                startInput.value = '09:00';
                endInput.value = '18:00';
                syncFromInputs();
            });
        }

        syncFromInputs();
    });
})();
</script>

<?php include BASE_PATH . '/includes/footer.php'; ?>
