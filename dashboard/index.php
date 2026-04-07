<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Auth.php';
require_once BASE_PATH . '/core/Helpers.php';

Auth::requireRole('owner');

$db = Database::getInstance();
$barbershopId = $_SESSION['barbershop_id'];

// Obtener información de la barbería
$barbershop = $db->fetch("
    SELECT 
        b.*, 
        l.type as license_type,
        l.status as license_status,
        l.billing_cycle as license_billing_cycle,
        l.price as license_price,
        l.start_date as license_start_date,
        l.end_date as license_end_date
    FROM barbershops b
    JOIN licenses l ON b.license_id = l.id
    WHERE b.id = ?
", [$barbershopId]);

$licenseType = $barbershop['license_type'] ?? 'basic';
$licenseConfig = LICENSE_TYPES[$licenseType] ?? null;
$maxBarbers = $licenseConfig['max_barbers'] ?? -1;
$maxServices = $licenseConfig['max_services'] ?? -1;

$activeBarbersCount = $db->fetch("SELECT COUNT(*) as count FROM barbers WHERE barbershop_id = ? AND status = 'active'", [$barbershopId])['count'];
$activeServicesCount = $db->fetch("SELECT COUNT(*) as count FROM services WHERE barbershop_id = ? AND is_active = TRUE", [$barbershopId])['count'];

$todayDate = new DateTime(date('Y-m-d'));
$licenseEndDate = !empty($barbershop['license_end_date']) ? new DateTime($barbershop['license_end_date']) : null;
$licenseDaysRemaining = null;

if ($licenseEndDate) {
    $licenseDaysRemaining = (int)$todayDate->diff($licenseEndDate)->format('%r%a');
}

$licenseStatus = $barbershop['license_status'] ?? 'inactive';
$licenseStatusClasses = [
    'active' => 'bg-green-100 text-green-800',
    'suspended' => 'bg-yellow-100 text-yellow-800',
    'expired' => 'bg-red-100 text-red-800',
    'cancelled' => 'bg-gray-100 text-gray-800',
    'inactive' => 'bg-gray-100 text-gray-800'
];

$licenseStatusClass = $licenseStatusClasses[$licenseStatus] ?? 'bg-gray-100 text-gray-800';

$barbersUsageLabel = $maxBarbers === -1 ? "$activeBarbersCount / Ilimitado" : "$activeBarbersCount / $maxBarbers";
$servicesUsageLabel = $maxServices === -1 ? "$activeServicesCount / Ilimitado" : "$activeServicesCount / $maxServices";

// Estadísticas
$stats = [
    'today_appointments' => $db->fetch("
        SELECT COUNT(*) as count FROM appointments 
        WHERE barbershop_id = ? AND appointment_date = CURDATE() AND status NOT IN ('cancelled', 'no_show')
    ", [$barbershopId])['count'],
    
    'pending_appointments' => $db->fetch("
        SELECT COUNT(*) as count FROM appointments 
        WHERE barbershop_id = ? AND status = 'pending'
    ", [$barbershopId])['count'],
    
    'total_clients' => $db->fetch("
        SELECT COUNT(*) as count FROM clients WHERE barbershop_id = ?
    ", [$barbershopId])['count'],
    
    'total_barbers' => $db->fetch("
        SELECT COUNT(*) as count FROM barbers WHERE barbershop_id = ? AND status = 'active'
    ", [$barbershopId])['count'],
    
    'monthly_revenue' => $db->fetch("
        SELECT COALESCE(SUM(price), 0) as total FROM appointments 
        WHERE barbershop_id = ? AND payment_status = 'paid' 
        AND appointment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ", [$barbershopId])['total'],
    
    'today_revenue' => $db->fetch("
        SELECT COALESCE(SUM(price), 0) as total FROM appointments 
        WHERE barbershop_id = ? AND payment_status = 'paid' 
        AND appointment_date = CURDATE()
    ", [$barbershopId])['total']
];

// Citas de hoy
$today_appointments = $db->fetchAll("
    SELECT a.*, s.name as service_name, u.full_name as barber_name
    FROM appointments a
    JOIN services s ON a.service_id = s.id
    JOIN barbers b ON a.barber_id = b.id
    JOIN users u ON b.user_id = u.id
    WHERE a.barbershop_id = ? AND a.appointment_date = CURDATE()
    ORDER BY a.start_time ASC
", [$barbershopId]);

// Barberos
$barbers = $db->fetchAll("
    SELECT b.*, u.full_name, u.email, u.phone
    FROM barbers b
    JOIN users u ON b.user_id = u.id
    WHERE b.barbershop_id = ? AND b.status = 'active'
    ORDER BY b.is_featured DESC, u.full_name ASC
", [$barbershopId]);

$title = 'Panel de Administración - ' . $barbershop['business_name'];
include BASE_PATH . '/includes/header.php';
?>

<div class="min-h-screen bg-gray-100" x-data="{ sidebarOpen: false }">
    <?php include BASE_PATH . '/includes/sidebar-owner.php'; ?>

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
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
                    <p class="text-sm text-gray-600 mt-1"><?php echo date('l, d F Y'); ?></p>
                </div>
                
                <div class="flex items-center space-x-3">
                    <a href="<?php echo BASE_URL; ?>/public/<?php echo $barbershop['slug']; ?>" 
                       target="_blank"
                       class="px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg hover:from-indigo-700 hover:to-purple-700 transition text-sm font-medium">
                        Ver Página Pública
                    </a>
                </div>
            </div>
        </div>

        <!-- Content -->
        <main class="p-6">
            <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6 mb-8">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Licencia Activa</p>
                        <h2 class="text-2xl font-bold text-gray-900 mt-1"><?php echo e($licenseConfig['name'] ?? ucfirst($licenseType)); ?></h2>
                        <div class="mt-2 flex items-center gap-2">
                            <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold <?php echo $licenseStatusClass; ?>">
                                <?php echo ucfirst($licenseStatus); ?>
                            </span>
                            <span class="text-sm text-gray-600">
                                Ciclo: <?php echo e(ucfirst($barbershop['license_billing_cycle'] ?? 'monthly')); ?>
                            </span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 w-full lg:w-auto">
                        <div class="bg-gray-50 rounded-lg px-4 py-3">
                            <p class="text-xs text-gray-500">Precio</p>
                            <p class="text-lg font-semibold text-gray-900"><?php echo formatPrice($barbershop['license_price'] ?? 0); ?></p>
                        </div>
                        <div class="bg-gray-50 rounded-lg px-4 py-3">
                            <p class="text-xs text-gray-500">Vence</p>
                            <p class="text-sm font-semibold text-gray-900"><?php echo !empty($barbershop['license_end_date']) ? formatDate($barbershop['license_end_date']) : 'N/D'; ?></p>
                            <p class="text-xs <?php echo ($licenseDaysRemaining !== null && $licenseDaysRemaining <= 7) ? 'text-red-600' : 'text-gray-500'; ?>">
                                <?php 
                                if ($licenseDaysRemaining === null) {
                                    echo 'Sin fecha de vencimiento';
                                } elseif ($licenseDaysRemaining < 0) {
                                    echo 'Vencida';
                                } else {
                                    echo $licenseDaysRemaining . ' dias restantes';
                                }
                                ?>
                            </p>
                        </div>
                        <div class="bg-gray-50 rounded-lg px-4 py-3">
                            <p class="text-xs text-gray-500">Uso del plan</p>
                            <p class="text-sm font-semibold text-gray-900">Barberos: <?php echo e($barbersUsageLabel); ?></p>
                            <p class="text-sm font-semibold text-gray-900">Servicios: <?php echo e($servicesUsageLabel); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-8">
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm opacity-90">Citas Hoy</p>
                            <p class="text-3xl font-bold mt-2"><?php echo $stats['today_appointments']; ?></p>
                        </div>
                        <svg class="w-12 h-12 opacity-50" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-lg shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm opacity-90">Pendientes</p>
                            <p class="text-3xl font-bold mt-2"><?php echo $stats['pending_appointments']; ?></p>
                        </div>
                        <svg class="w-12 h-12 opacity-50" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"/>
                        </svg>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm opacity-90">Clientes</p>
                            <p class="text-3xl font-bold mt-2"><?php echo $stats['total_clients']; ?></p>
                        </div>
                        <svg class="w-12 h-12 opacity-50" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                        </svg>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm opacity-90">Barberos</p>
                            <p class="text-3xl font-bold mt-2"><?php echo $stats['total_barbers']; ?></p>
                        </div>
                        <svg class="w-12 h-12 opacity-50" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-lg shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm opacity-90">Hoy</p>
                            <p class="text-2xl font-bold mt-2"><?php echo formatPrice($stats['today_revenue']); ?></p>
                        </div>
                        <svg class="w-12 h-12 opacity-50" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-pink-500 to-pink-600 rounded-lg shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm opacity-90">Este Mes</p>
                            <p class="text-2xl font-bold mt-2"><?php echo formatPrice($stats['monthly_revenue']); ?></p>
                        </div>
                        <svg class="w-12 h-12 opacity-50" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11 4a1 1 0 10-2 0v4a1 1 0 102 0V7zm-3 1a1 1 0 10-2 0v3a1 1 0 102 0V8zM8 9a1 1 0 00-2 0v2a1 1 0 102 0V9z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Citas de Hoy -->
                <div class="lg:col-span-2 bg-white rounded-lg shadow-md">
                    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">Citas de Hoy</h3>
                        <a href="appointments.php" class="text-sm text-indigo-600 hover:text-indigo-800">Ver todas →</a>
                    </div>
                    <div class="p-6">
                        <?php if (empty($today_appointments)): ?>
                            <div class="text-center py-12">
                                <svg class="w-16 h-16 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <p class="mt-4 text-gray-600">No hay citas programadas para hoy</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-3">
                                <?php foreach ($today_appointments as $apt): ?>
                                    <div class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                                        <div class="flex-shrink-0">
                                            <div class="w-16 h-16 bg-indigo-100 rounded-lg flex items-center justify-center">
                                                <div class="text-center">
                                                    <div class="text-xs text-indigo-600 font-medium"><?php echo date('H:i', strtotime($apt['start_time'])); ?></div>
                                                    <div class="text-xs text-gray-500"><?php echo date('H:i', strtotime($apt['end_time'])); ?></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="ml-4 flex-1">
                                            <p class="font-semibold text-gray-900"><?php echo e($apt['client_name']); ?></p>
                                            <p class="text-sm text-gray-600"><?php echo e($apt['service_name']); ?></p>
                                            <p class="text-xs text-gray-500">Barbero: <?php echo e($apt['barber_name']); ?></p>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-semibold text-gray-900"><?php echo formatPrice($apt['price']); ?></p>
                                            <span class="inline-block px-2 py-1 text-xs font-medium rounded-full 
                                                <?php echo $apt['status'] === 'confirmed' ? 'bg-green-100 text-green-800' : 
                                                          ($apt['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800'); ?>">
                                                <?php echo ucfirst($apt['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Barberos -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">Barberos</h3>
                        <a href="barbers.php" class="text-sm text-indigo-600 hover:text-indigo-800">Gestionar →</a>
                    </div>
                    <div class="p-6">
                        <div class="space-y-3">
                            <?php foreach ($barbers as $barber): ?>
                                <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                                     <img src="<?php echo $barber['photo'] ? imageUrl($barber['photo']) : getDefaultAvatar($barber['full_name']); ?>" 
                                         class="w-12 h-12 rounded-full" alt="<?php echo e($barber['full_name']); ?>">
                                    <div class="ml-3 flex-1">
                                        <p class="font-medium text-gray-900"><?php echo e($barber['full_name']); ?></p>
                                        <div class="flex items-center text-xs text-yellow-500">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                            <span class="ml-1"><?php echo number_format($barber['rating'], 1); ?></span>
                                        </div>
                                    </div>
                                    <?php if ($barber['is_featured']): ?>
                                        <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs font-medium rounded-full">★</span>
                                    <?php endif; ?>
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
