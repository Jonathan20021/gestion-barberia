<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Auth.php';
require_once BASE_PATH . '/core/Helpers.php';

Auth::requireRole('superadmin');

$db = Database::getInstance();

// Obtener ID de la barbería
$shopId = $_GET['id'] ?? null;

if (!$shopId) {
    header('Location: barbershops.php');
    exit;
}

// Obtener barbería
$shop = $db->fetch("SELECT id, business_name FROM barbershops WHERE id = ?", [$shopId]);

if (!$shop) {
    $_SESSION['error'] = 'Barbería no encontrada';
    header('Location: barbershops.php');
    exit;
}

// Obtener servicios de la barbería
$services = $db->fetchAll("
    SELECT * FROM services
    WHERE barbershop_id = ?
    ORDER BY name ASC
", [$shopId]);

// Procesar eliminación
if (isset($_GET['delete'])) {
    $serviceId = $_GET['delete'];
    $db->query("DELETE FROM services WHERE id = ? AND barbershop_id = ?", [$serviceId, $shopId]);
    $_SESSION['success'] = 'Servicio eliminado';
    header("Location: manage-services.php?id=$shopId");
    exit;
}

// Procesar formulario de creación/edición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $serviceId = $_POST['service_id'] ?? null;
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $duration = intval($_POST['duration'] ?? 30);
        $price = floatval($_POST['price'] ?? 0);
        $status = $_POST['status'] ?? 'active';
        
        if (empty($name)) {
            throw new Exception('El nombre del servicio es obligatorio');
        }
        
        if ($price <= 0) {
            throw new Exception('El precio debe ser mayor a 0');
        }
        
        if ($serviceId) {
            // Editar servicio existente
            $db->query("
                UPDATE services 
                SET name = ?, description = ?, duration = ?, price = ?, status = ?
                WHERE id = ? AND barbershop_id = ?
            ", [$name, $description, $duration, $price, $status, $serviceId, $shopId]);
            $_SESSION['success'] = 'Servicio actualizado';
        } else {
            // Crear nuevo servicio
            $db->query("
                INSERT INTO services (barbershop_id, name, description, duration, price, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ", [$shopId, $name, $description, $duration, $price, $status]);
            $_SESSION['success'] = 'Servicio creado';
        }
        
        header("Location: manage-services.php?id=$shopId");
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

// Si hay ID de servicio en GET, obtener datos para editar
$editService = null;
if (isset($_GET['edit'])) {
    $editService = $db->fetch("SELECT * FROM services WHERE id = ? AND barbershop_id = ?", [$_GET['edit'], $shopId]);
}

$title = 'Gestión de Servicios - ' . $shop['business_name'];
include BASE_PATH . '/includes/header.php';
?>

<div class="min-h-screen bg-gray-100" x-data="{ sidebarOpen: false, showModal: <?php echo $editService ? 'true' : 'false'; ?> }">
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
                    <h1 class="text-2xl font-bold text-gray-900">Servicios de <?php echo htmlspecialchars($shop['business_name']); ?></h1>
                    <p class="text-sm text-gray-500">Gestiona los servicios disponibles</p>
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

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm text-gray-600">Total Servicios</p>
                    <p class="text-3xl font-bold text-indigo-600"><?php echo count($services); ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm text-gray-600">Activos</p>
                    <p class="text-3xl font-bold text-green-600">
                        <?php echo count(array_filter($services, fn($s) => $s['status'] === 'active')); ?>
                    </p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm text-gray-600">Precio Promedio</p>
                    <p class="text-3xl font-bold text-blue-600">
                        $<?php echo count($services) > 0 ? number_format(array_sum(array_column($services, 'price')) / count($services), 2) : '0.00'; ?>
                    </p>
                </div>
            </div>

            <!-- Botón Crear -->
            <div class="mb-6">
                <button @click="showModal = true" class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 shadow-lg">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Nuevo Servicio
                </button>
            </div>

            <!-- Lista de Servicios -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($services as $service): ?>
                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition">
                    <div class="flex justify-between items-start mb-4">
                        <h3 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($service['name']); ?></h3>
                        <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $service['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                            <?php echo ucfirst($service['status']); ?>
                        </span>
                    </div>
                    
                    <p class="text-gray-600 text-sm mb-4"><?php echo htmlspecialchars($service['description'] ?? ''); ?></p>
                    
                    <div class="flex justify-between items-center mb-4">
                        <div>
                            <p class="text-xs text-gray-500">Duración</p>
                            <p class="text-sm font-semibold text-gray-900"><?php echo $service['duration']; ?> min</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Precio</p>
                            <p class="text-lg font-bold text-indigo-600">$<?php echo number_format($service['price'], 2); ?></p>
                        </div>
                    </div>
                    
                    <div class="flex gap-2">
                        <a href="?id=<?php echo $shopId; ?>&edit=<?php echo $service['id']; ?>" 
                           class="flex-1 px-4 py-2 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 text-center text-sm font-medium">
                            Editar
                        </a>
                        <a href="?id=<?php echo $shopId; ?>&delete=<?php echo $service['id']; ?>" 
                           onclick="return confirm('¿Eliminar servicio?')"
                           class="flex-1 px-4 py-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 text-center text-sm font-medium">
                            Eliminar
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if (empty($services)): ?>
                <div class="col-span-full text-center py-12">
                    <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <p class="text-gray-500">No hay servicios creados aún</p>
                    <button @click="showModal = true" class="mt-4 text-indigo-600 hover:text-indigo-700 font-medium">
                        Crear primer servicio
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Modal Crear/Editar -->
    <div x-show="showModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showModal = false"></div>
            
            <div class="relative bg-white rounded-lg shadow-xl max-w-lg w-full p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-4">
                    <?php echo $editService ? 'Editar Servicio' : 'Nuevo Servicio'; ?>
                </h3>
                
                <form method="POST">
                    <?php if ($editService): ?>
                    <input type="hidden" name="service_id" value="<?php echo $editService['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nombre *</label>
                            <input type="text" name="name" value="<?php echo $editService ? htmlspecialchars($editService['name']) : ''; ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
                            <textarea name="description" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"><?php echo $editService ? htmlspecialchars($editService['description'] ?? '') : ''; ?></textarea>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Duración (min) *</label>
                                <input type="number" name="duration" value="<?php echo $editService ? $editService['duration'] : '30'; ?>" 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" min="5" step="5" required>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Precio ($) *</label>
                                <input type="number" name="price" value="<?php echo $editService ? $editService['price'] : ''; ?>" 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" min="0" step="0.01" required>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                            <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                <option value="active" <?php echo ($editService && $editService['status'] === 'active') ? 'selected' : ''; ?>>Activo</option>
                                <option value="inactive" <?php echo ($editService && $editService['status'] === 'inactive') ? 'selected' : ''; ?>>Inactivo</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mt-6 flex gap-3">
                        <button type="button" @click="showModal = false" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                            Cancelar
                        </button>
                        <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                            <?php echo $editService ? 'Actualizar' : 'Crear'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
[x-cloak] { display: none !important; }
</style>

<?php include BASE_PATH . '/includes/footer.php'; ?>
