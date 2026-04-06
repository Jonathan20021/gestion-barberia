<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Auth.php';
require_once BASE_PATH . '/core/Helpers.php';

Auth::requireRole('superadmin');

$db = Database::getInstance();

$shopId = $_GET['id'] ?? null;

if (!$shopId) {
    header('Location: barbershops.php');
    exit;
}

$shop = $db->fetch("SELECT id, business_name FROM barbershops WHERE id = ?", [$shopId]);

if (!$shop) {
    $_SESSION['error'] = 'Barbería no encontrada';
    header('Location: barbershops.php');
    exit;
}

// Obtener horarios existentes
$schedules = $db->fetchAll("
    SELECT * FROM barbershop_schedules
    WHERE barbershop_id = ?
    ORDER BY day_of_week ASC
", [$shopId]);

// Convertir a array asociativo por día
$schedulesByDay = [];
foreach ($schedules as $schedule) {
    $schedulesByDay[$schedule['day_of_week']] = $schedule;
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $days = $_POST['days'] ?? [];
        
        // Eliminar horarios existentes
        $db->query("DELETE FROM barbershop_schedules WHERE barbershop_id = ?", [$shopId]);
        
        // Insertar nuevos horarios
        foreach ($days as $dayNum => $dayData) {
            if (isset($dayData['active'])) {
                $openTime = $dayData['open_time'] ?? '09:00';
                $closeTime = $dayData['close_time'] ?? '18:00';
                
                $db->query("
                    INSERT INTO barbershop_schedules (barbershop_id, day_of_week, open_time, close_time, is_closed)
                    VALUES (?, ?, ?, ?, FALSE)
                ", [$shopId, $dayNum, $openTime, $closeTime]);
            } else {
                // Día cerrado
                $db->query("
                    INSERT INTO barbershop_schedules (barbershop_id, day_of_week, open_time, close_time, is_closed)
                    VALUES (?, ?, '00:00', '00:00', TRUE)
                ", [$shopId, $dayNum]);
            }
        }
        
        $_SESSION['success'] = 'Horarios actualizados exitosamente';
        header("Location: manage-schedules.php?id=$shopId");
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

$dayNames = [
    0 => 'Domingo',
    1 => 'Lunes',
    2 => 'Martes',
    3 => 'Miércoles',
    4 => 'Jueves',
    5 => 'Viernes',
    6 => 'Sábado'
];

$title = 'Gestión de Horarios - ' . $shop['business_name'];
include BASE_PATH . '/includes/header.php';
?>

<div class="min-h-screen bg-gray-100" x-data="{ sidebarOpen: false }">
    <?php include BASE_PATH . '/includes/sidebar-admin.php'; ?>

    <div class="lg:pl-64">
        <div class="sticky top-0 z-40 flex h-16 bg-white border-b border-gray-200 shadow-sm">
            <button @click="sidebarOpen = true" class="px-4 text-gray-500 lg:hidden">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            
            <div class="flex items-center justify-between flex-1 px-4 sm:px-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Horarios de <?php echo htmlspecialchars($shop['business_name']); ?></h1>
                    <p class="text-sm text-gray-500">Configura los días y horas de atención</p>
                </div>
                <a href="barbershops.php" class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg">
                    <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Volver
                </a>
            </div>
        </div>

        <main class="p-6">
            <?php if (isset($_SESSION['success'])): ?>
            <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-lg">
                <p class="text-green-700"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
                <p class="text-red-700"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
            </div>
            <?php endif; ?>

            <form method="POST" class="max-w-3xl">
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Horario de Atención</h2>
                    
                    <div class="space-y-4">
                        <?php foreach ($dayNames as $dayNum => $dayName): 
                            $schedule = $schedulesByDay[$dayNum] ?? null;
                            $isActive = $schedule && !$schedule['is_closed'];
                        ?>
                        <div class="border border-gray-200 rounded-lg p-4" x-data="{ active: <?php echo $isActive ? 'true' : 'false'; ?> }">
                            <div class="flex items-center justify-between mb-3">
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" name="days[<?php echo $dayNum; ?>][active]" 
                                           x-model="active"
                                           <?php echo $isActive ? 'checked' : ''; ?>
                                           class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 mr-3">
                                    <span class="text-lg font-medium text-gray-900"><?php echo $dayName; ?></span>
                                </label>
                                
                                <span x-show="!active" class="text-sm text-gray-500">Cerrado</span>
                            </div>
                            
                            <div x-show="active" class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Apertura</label>
                                    <input type="time" name="days[<?php echo $dayNum; ?>][open_time]" 
                                           value="<?php echo $schedule ? $schedule['open_time'] : '09:00'; ?>"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Cierre</label>
                                    <input type="time" name="days[<?php echo $dayNum; ?>][close_time]" 
                                           value="<?php echo $schedule ? $schedule['close_time'] : '18:00'; ?>"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <p class="text-sm font-medium text-blue-900 mb-3">Acciones Rápidas:</p>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" onclick="setAllDays(true)" class="px-4 py-2 bg-white border border-blue-300 text-blue-700 rounded-lg hover:bg-blue-50 text-sm">
                            Activar Todos
                        </button>
                        <button type="button" onclick="setWeekdays()" class="px-4 py-2 bg-white border border-blue-300 text-blue-700 rounded-lg hover:bg-blue-50 text-sm">
                            Solo Lunes-Viernes
                        </button>
                        <button type="button" onclick="setAllDays(false)" class="px-4 py-2 bg-white border border-blue-300 text-blue-700 rounded-lg hover:bg-blue-50 text-sm">
                            Desactivar Todos
                        </button>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 shadow-lg">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Guardar Horarios
                    </button>
                </div>
            </form>
        </main>
    </div>
</div>

<script>
function setAllDays(active) {
    document.querySelectorAll('input[type="checkbox"][name*="[active]"]').forEach(cb => {
        cb.checked = active;
        cb.dispatchEvent(new Event('change'));
    });
}

function setWeekdays() {
    document.querySelectorAll('input[type="checkbox"][name*="[active]"]').forEach((cb, index) => {
        // Activar Lunes (1) a Viernes (5)
        const dayNum = parseInt(cb.name.match(/\[(\d+)\]/)[1]);
        cb.checked = dayNum >= 1 && dayNum <= 5;
        cb.dispatchEvent(new Event('change'));
    });
}
</script>

<?php include BASE_PATH . '/includes/footer.php'; ?>
