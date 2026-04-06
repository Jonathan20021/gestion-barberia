<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Auth.php';
require_once BASE_PATH . '/core/Helpers.php';

Auth::requireRole('superadmin');

$db = Database::getInstance();

// Manejar toggle de estado
if (isset($_GET['toggle'])) {
    $shopId = $_GET['toggle'];
    $shop = $db->fetch("SELECT status FROM barbershops WHERE id = ?", [$shopId]);
    
    if ($shop) {
        $newStatus = $shop['status'] === 'active' ? 'suspended' : 'active';
        $db->query("UPDATE barbershops SET status = ? WHERE id = ?", [$newStatus, $shopId]);
        $_SESSION['success'] = 'Estado de la barbería actualizado';
    }
    
    header('Location: barbershops.php');
    exit;
}

// Manejar eliminación
if (isset($_GET['delete'])) {
    $shopId = $_GET['delete'];
    
    try {
        // Eliminar barbería (cascada eliminará barbers, services, etc.)
        $db->query("DELETE FROM barbershops WHERE id = ?", [$shopId]);
        $_SESSION['success'] = 'Barbería eliminada exitosamente';
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error al eliminar la barbería';
    }
    
    header('Location: barbershops.php');
    exit;
}

// Obtener todas las barberías
$barbershops = $db->fetchAll("
    SELECT 
        b.*,
        l.type as license_type,
        l.status as license_status,
        l.end_date as license_end_date,
        u.full_name as owner_name,
        u.email as owner_email,
        COUNT(DISTINCT br.id) as total_barbers,
        COUNT(DISTINCT s.id) as total_services
    FROM barbershops b
    LEFT JOIN licenses l ON b.license_id = l.id
    LEFT JOIN users u ON b.owner_id = u.id
    LEFT JOIN barbers br ON b.id = br.barbershop_id
    LEFT JOIN services s ON b.id = s.barbershop_id
    GROUP BY b.id
    ORDER BY b.created_at DESC
");

$title = 'Gestión de Barberías - Super Admin';
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
                <h1 class="text-2xl font-bold text-gray-900">Gestión de Barberías</h1>
                <a href="../auth/logout.php" class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg">Cerrar Sesión</a>
            </div>
        </div>

        <main class="p-6">
            <!-- Mensajes de feedback -->
            <?php if (isset($_SESSION['success'])): ?>
            <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-lg">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <p class="text-green-700"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <p class="text-red-700"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm text-gray-600">Total Barberías</p>
                    <p class="text-3xl font-bold text-indigo-600"><?php echo count($barbershops); ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm text-gray-600">Activas</p>
                    <p class="text-3xl font-bold text-green-600">
                        <?php echo count(array_filter($barbershops, fn($b) => $b['status'] === 'active')); ?>
                    </p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm text-gray-600">Licencias por Vencer</p>
                    <p class="text-3xl font-bold text-yellow-600">
                        <?php 
                        $expiring = array_filter($barbershops, function($b) {
                            return $b['license_end_date'] && 
                                   strtotime($b['license_end_date']) < strtotime('+7 days');
                        });
                        echo count($expiring);
                        ?>
                    </p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm text-gray-600">Suspendidas</p>
                    <p class="text-3xl font-bold text-red-600">
                        <?php echo count(array_filter($barbershops, fn($b) => $b['status'] === 'suspended')); ?>
                    </p>
                </div>
            </div>

            <!-- Tabla -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h2 class="text-lg font-semibold text-gray-900">Todas las Barberías</h2>
                    <a href="create-barbershop.php" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 inline-flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Nueva Barbería
                    </a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Barbería</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Owner</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Licencia</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Recursos</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vencimiento</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($barbershops as $shop): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <?php if ($shop['logo']): ?>
                                        <img src="<?php echo asset($shop['logo']); ?>" class="w-10 h-10 rounded-full mr-3" alt="Logo">
                                        <?php else: ?>
                                        <div class="w-10 h-10 rounded-full bg-indigo-600 text-white flex items-center justify-center mr-3">
                                            <?php echo substr($shop['business_name'], 0, 1); ?>
                                        </div>
                                        <?php endif; ?>
                                        <div>
                                            <p class="font-medium text-gray-900"><?php echo e($shop['business_name']); ?></p>
                                            <p class="text-sm text-gray-500"><?php echo e($shop['slug']); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm text-gray-900"><?php echo e($shop['owner_name']); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo e($shop['owner_email']); ?></p>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full
                                        <?php 
                                            echo $shop['license_type'] === 'enterprise' ? 'bg-purple-100 text-purple-800' :
                                                ($shop['license_type'] === 'professional' ? 'bg-blue-100 text-blue-800' : 
                                                'bg-gray-100 text-gray-800'); 
                                        ?>">
                                        <?php echo ucfirst($shop['license_type']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">
                                        <?php echo $shop['total_barbers']; ?> barberos
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        <?php echo $shop['total_services']; ?> servicios
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full
                                        <?php 
                                            echo $shop['status'] === 'active' ? 'bg-green-100 text-green-800' : 
                                                'bg-red-100 text-red-800'; 
                                        ?>">
                                        <?php echo ucfirst($shop['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($shop['license_end_date']): ?>
                                    <p class="text-sm text-gray-900"><?php echo formatDate($shop['license_end_date']); ?></p>
                                    <?php 
                                    $daysLeft = floor((strtotime($shop['license_end_date']) - time()) / 86400);
                                    if ($daysLeft < 7):
                                    ?>
                                    <p class="text-xs text-red-600">⚠️ <?php echo $daysLeft; ?> días</p>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-medium">
                                    <div class="flex justify-end gap-2">
                                        <a href="edit-barbershop.php?id=<?php echo $shop['id']; ?>" 
                                           class="px-3 py-1 bg-indigo-50 text-indigo-600 rounded hover:bg-indigo-100 text-xs font-medium">
                                            Editar
                                        </a>
                                        <a href="manage-barbers.php?id=<?php echo $shop['id']; ?>" 
                                           class="px-3 py-1 bg-green-50 text-green-600 rounded hover:bg-green-100 text-xs font-medium">
                                            Barberos
                                        </a>
                                        <a href="manage-services.php?id=<?php echo $shop['id']; ?>" 
                                           class="px-3 py-1 bg-purple-50 text-purple-600 rounded hover:bg-purple-100 text-xs font-medium">
                                            Servicios
                                        </a>
                                        <a href="manage-schedules.php?id=<?php echo $shop['id']; ?>" 
                                           class="px-3 py-1 bg-yellow-50 text-yellow-700 rounded hover:bg-yellow-100 text-xs font-medium">
                                            Horarios
                                        </a>
                                    </div>
                                    <div class="flex justify-end gap-2 mt-2">
                                        <a href="../public/booking.php?shop=<?php echo $shop['slug']; ?>" 
                                           target="_blank"
                                           class="text-blue-600 hover:text-blue-900 text-xs">Ver</a>
                                        <a href="?toggle=<?php echo $shop['id']; ?>" 
                                           class="text-orange-600 hover:text-orange-900 text-xs">
                                            <?php echo $shop['status'] === 'active' ? 'Suspender' : 'Activar'; ?>
                                        </a>
                                        <a href="?delete=<?php echo $shop['id']; ?>" 
                                           class="text-red-600 hover:text-red-900 text-xs"
                                           onclick="return confirm('¿Estás seguro de eliminar esta barbería? Esta acción no se puede deshacer.')">Eliminar</a>
                                    </div>
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
