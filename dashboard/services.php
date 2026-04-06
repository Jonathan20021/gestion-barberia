<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Auth.php';
require_once BASE_PATH . '/core/Helpers.php';

Auth::requireRole('owner');

$db = Database::getInstance();
$barbershopId = $_SESSION['barbershop_id'];

$editId = intval(input('edit', 0));
$editService = null;
if ($editId > 0) {
    $editService = $db->fetch(
        "SELECT * FROM services WHERE id = ? AND barbershop_id = ?",
        [$editId, $barbershopId]
    );
}

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = input('action');
    
    if ($action === 'create') {
        if (!canAddServiceToBarbershop($barbershopId, $limitMessage)) {
            setFlash('error', $limitMessage);
            redirect($_SERVER['PHP_SELF']);
        }

        $name = input('name');
        $description = input('description');
        $duration = input('duration');
        $price = input('price');
        $category = input('category');
        
        $db->execute("
            INSERT INTO services (barbershop_id, name, description, duration, price, category, is_active, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
        ", [$barbershopId, $name, $description, $duration, $price, $category]);
        
        setFlash('success', 'Servicio creado exitosamente');
        redirect($_SERVER['PHP_SELF']);
    }

    if ($action === 'update') {
        $serviceId = intval(input('service_id'));
        $name = trim((string) input('name'));
        $description = trim((string) input('description'));
        $duration = intval(input('duration'));
        $price = floatval(input('price'));
        $category = trim((string) input('category'));
        $displayOrder = intval(input('display_order', 0));

        $db->execute(
            "UPDATE services
             SET name = ?, description = ?, duration = ?, price = ?, category = ?, display_order = ?, updated_at = NOW()
             WHERE id = ? AND barbershop_id = ?",
            [$name, $description ?: null, $duration, $price, $category ?: null, $displayOrder, $serviceId, $barbershopId]
        );

        setFlash('success', 'Servicio actualizado correctamente');
        redirect($_SERVER['PHP_SELF']);
    }
    
    if ($action === 'toggle') {
        $serviceId = input('service_id');
        $db->execute("
            UPDATE services 
            SET is_active = NOT is_active 
            WHERE id = ? AND barbershop_id = ?
        ", [$serviceId, $barbershopId]);
        
        setFlash('success', 'Estado actualizado');
        redirect($_SERVER['PHP_SELF']);
    }
}

// Obtener servicios
$services = $db->fetchAll("
    SELECT 
        s.*,
        COUNT(DISTINCT a.id) as total_bookings,
        COALESCE(SUM(a.price), 0) as total_revenue
    FROM services s
    LEFT JOIN appointments a ON s.id = a.service_id AND a.status = 'completed'
    WHERE s.barbershop_id = ?
    GROUP BY s.id
    ORDER BY s.category, s.display_order, s.name
", [$barbershopId]);

$flash = getFlash();

// Agrupar por categoría
$servicesByCategory = [];
foreach ($services as $service) {
    $cat = $service['category'] ?: 'General';
    if (!isset($servicesByCategory[$cat])) {
        $servicesByCategory[$cat] = [];
    }
    $servicesByCategory[$cat][] = $service;
}

$title = 'Gestión de Servicios - Dashboard';
include BASE_PATH . '/includes/header.php';
?>

<div class="min-h-screen bg-gray-100" x-data="{ sidebarOpen: false, showModal: false, showEditModal: <?php echo $editService ? 'true' : 'false'; ?> }">
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
                <h1 class="text-2xl font-bold text-gray-900">Gestión de Servicios</h1>
                <button @click="showModal = true" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    + Nuevo Servicio
                </button>
            </div>
        </div>

        <main class="p-6">
            <?php if ($flash): ?>
            <div class="mb-6 rounded-lg p-4 border-l-4 <?php echo $flash['type'] === 'success' ? 'bg-green-50 border-green-500 text-green-700' : 'bg-red-50 border-red-500 text-red-700'; ?>">
                <?php echo e($flash['message']); ?>
            </div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm text-gray-600">Total Servicios</p>
                    <p class="text-3xl font-bold text-indigo-600"><?php echo count($services); ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm text-gray-600">Servicios Activos</p>
                    <p class="text-3xl font-bold text-green-600">
                        <?php echo count(array_filter($services, fn($s) => $s['is_active'])); ?>
                    </p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm text-gray-600">Reservas Totales</p>
                    <p class="text-3xl font-bold text-blue-600">
                        <?php echo array_sum(array_column($services, 'total_bookings')); ?>
                    </p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm text-gray-600">Ingresos Generados</p>
                    <p class="text-3xl font-bold text-purple-600">
                        <?php echo formatPrice(array_sum(array_column($services, 'total_revenue'))); ?>
                    </p>
                </div>
            </div>

            <!-- Servicios por Categoría -->
            <?php foreach ($servicesByCategory as $category => $categoryServices): ?>
            <div class="bg-white rounded-lg shadow-md mb-6">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h2 class="text-lg font-semibold text-gray-900"><?php echo e($category); ?></h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php foreach ($categoryServices as $service): ?>
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-lg transition <?php echo !$service['is_active'] ? 'opacity-50' : ''; ?>">
                            <?php if ($service['image']): ?>
                            <img src="<?php echo asset($service['image']); ?>" class="w-full h-32 object-cover rounded-lg mb-3" alt="<?php echo e($service['name']); ?>">
                            <?php else: ?>
                            <div class="w-full h-32 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg mb-3 flex items-center justify-center text-white text-3xl">
                                ✂️
                            </div>
                            <?php endif; ?>
                            
                            <h3 class="font-semibold text-gray-900 mb-2"><?php echo e($service['name']); ?></h3>
                            <p class="text-sm text-gray-600 mb-3 line-clamp-2"><?php echo e($service['description']); ?></p>
                            
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-2xl font-bold text-indigo-600"><?php echo formatPrice($service['price']); ?></span>
                                <span class="text-sm text-gray-500"><?php echo $service['duration']; ?> min</span>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-2 text-xs text-gray-600 mb-3">
                                <div class="bg-gray-50 rounded p-2 text-center">
                                    <p class="font-semibold text-blue-600"><?php echo $service['total_bookings']; ?></p>
                                    <p>Reservas</p>
                                </div>
                                <div class="bg-gray-50 rounded p-2 text-center">
                                    <p class="font-semibold text-green-600"><?php echo formatPrice($service['total_revenue']); ?></p>
                                    <p>Ingresos</p>
                                </div>
                            </div>
                            
                            <div class="flex space-x-2">
                                <form method="POST" class="flex-1">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                                    <button type="submit" class="w-full px-3 py-2 text-sm rounded-lg
                                        <?php echo $service['is_active'] ? 'bg-yellow-100 text-yellow-700 hover:bg-yellow-200' : 'bg-green-100 text-green-700 hover:bg-green-200'; ?>">
                                        <?php echo $service['is_active'] ? 'Desactivar' : 'Activar'; ?>
                                    </button>
                                </form>
                                <a href="?edit=<?php echo $service['id']; ?>" class="flex-1 px-3 py-2 text-sm bg-indigo-100 text-indigo-700 rounded-lg hover:bg-indigo-200 text-center">
                                    Editar
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </main>
    </div>

    <!-- Modal Nuevo Servicio -->
    <div x-show="showModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="showModal = false"></div>
            
            <div class="relative bg-white rounded-lg max-w-md w-full p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Nuevo Servicio</h3>
                
                <form method="POST">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nombre del Servicio</label>
                            <input type="text" name="name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
                            <textarea name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg"></textarea>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Duración (min)</label>
                                <input type="number" name="duration" value="30" min="15" step="15" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Precio (RD$)</label>
                                <input type="number" name="price" step="50" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Categoría</label>
                            <select name="category" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                <option value="Cortes">Cortes</option>
                                <option value="Afeitado">Afeitado</option>
                                <option value="Barba">Barba</option>
                                <option value="Tratamientos">Tratamientos</option>
                                <option value="Combos">Combos</option>
                                <option value="Infantil">Infantil</option>
                            </select>
                        </div>
                        
                        <div class="flex space-x-3 pt-4">
                            <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                                Crear Servicio
                            </button>
                            <button type="button" @click="showModal = false" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                                Cancelar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Servicio -->
    <div x-show="showEditModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="window.location='services.php'"></div>

            <div class="relative bg-white rounded-lg max-w-md w-full p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Editar Servicio</h3>
                    <a href="services.php" class="text-gray-500 hover:text-gray-700">Cerrar</a>
                </div>

                <?php if ($editService): ?>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="service_id" value="<?php echo $editService['id']; ?>">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                        <input type="text" name="name" value="<?php echo e($editService['name']); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Descripcion</label>
                        <textarea name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg"><?php echo e($editService['description'] ?? ''); ?></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Duracion</label>
                            <input type="number" name="duration" min="5" value="<?php echo intval($editService['duration']); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Precio</label>
                            <input type="number" name="price" min="0" step="0.01" value="<?php echo e($editService['price']); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Categoria</label>
                        <input type="text" name="category" value="<?php echo e($editService['category'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Orden de visualizacion</label>
                        <input type="number" name="display_order" min="0" value="<?php echo intval($editService['display_order']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Guardar</button>
                        <a href="services.php" class="flex-1 text-center px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">Cancelar</a>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include BASE_PATH . '/includes/footer.php'; ?>
