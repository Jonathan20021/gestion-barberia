<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Auth.php';
require_once BASE_PATH . '/core/Helpers.php';

Auth::requireRole('owner');

$db = Database::getInstance();
$barbershopId = $_SESSION['barbershop_id'];

$viewId = intval(input('view', 0));
$editId = intval(input('edit', 0));

$viewClient = null;
if ($viewId > 0) {
    $viewClient = $db->fetch(
        "SELECT * FROM clients WHERE id = ? AND barbershop_id = ?",
        [$viewId, $barbershopId]
    );
}

$editClient = null;
if ($editId > 0) {
    $editClient = $db->fetch(
        "SELECT * FROM clients WHERE id = ? AND barbershop_id = ?",
        [$editId, $barbershopId]
    );
}

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = input('action');
    
    if ($action === 'create') {
        $name = input('name');
        $phone = input('phone');
        $email = input('email');
        
        $db->execute("
            INSERT INTO clients (barbershop_id, name, phone, email, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ", [$barbershopId, $name, $phone, $email]);
        
        setFlash('success', 'Cliente creado exitosamente');
        redirect($_SERVER['PHP_SELF']);
    }

    if ($action === 'update') {
        $clientId = intval(input('client_id'));
        $name = trim((string) input('name'));
        $phone = trim((string) input('phone'));
        $email = trim((string) input('email'));
        $notes = trim((string) input('notes'));

        $db->execute(
            "UPDATE clients SET name = ?, phone = ?, email = ?, notes = ?, updated_at = NOW()
             WHERE id = ? AND barbershop_id = ?",
            [$name, $phone, $email ?: null, $notes ?: null, $clientId, $barbershopId]
        );

        setFlash('success', 'Cliente actualizado correctamente');
        redirect($_SERVER['PHP_SELF']);
    }

    if ($action === 'delete') {
        $clientId = intval(input('client_id'));
        $db->execute(
            "DELETE FROM clients WHERE id = ? AND barbershop_id = ?",
            [$clientId, $barbershopId]
        );
        setFlash('success', 'Cliente eliminado correctamente');
        redirect($_SERVER['PHP_SELF']);
    }
}

// Obtener clientes
$clients = $db->fetchAll("
    SELECT 
        c.*,
        COUNT(DISTINCT a.id) as total_appointments,
        COALESCE(SUM(a.price), 0) as total_spent,
        MAX(a.appointment_date) as last_visit
    FROM clients c
    LEFT JOIN appointments a ON c.id = a.client_id AND a.status = 'completed'
    WHERE c.barbershop_id = ?
    GROUP BY c.id
    ORDER BY c.created_at DESC
", [$barbershopId]);

$flash = getFlash();

$title = 'Gestión de Clientes - Dashboard';
include BASE_PATH . '/includes/header.php';
?>

<div class="min-h-screen bg-gray-100" x-data="{ sidebarOpen: false, showModal: false, showEditModal: <?php echo $editClient ? 'true' : 'false'; ?>, showViewModal: <?php echo $viewClient ? 'true' : 'false'; ?> }">
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
                <h1 class="text-2xl font-bold text-gray-900">Gestión de Clientes</h1>
                <button @click="showModal = true" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    + Nuevo Cliente
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
                    <p class="text-sm text-gray-600">Total Clientes</p>
                    <p class="text-3xl font-bold text-indigo-600"><?php echo count($clients); ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm text-gray-600">Nuevos este mes</p>
                    <p class="text-3xl font-bold text-green-600">
                        <?php 
                        echo count(array_filter($clients, function($c) {
                            return strtotime($c['created_at']) >= strtotime('first day of this month');
                        }));
                        ?>
                    </p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm text-gray-600">Clientes Activos</p>
                    <p class="text-3xl font-bold text-blue-600">
                        <?php echo count(array_filter($clients, fn($c) => $c['total_appointments'] > 0)); ?>
                    </p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm text-gray-600">Valor Total</p>
                    <p class="text-3xl font-bold text-purple-600">
                        <?php echo formatPrice(array_sum(array_column($clients, 'total_spent'))); ?>
                    </p>
                </div>
            </div>

            <!-- Tabla de Clientes -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contacto</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Citas</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Gastado</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Última Visita</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">WhatsApp</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($clients as $client): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 rounded-full bg-indigo-600 text-white flex items-center justify-center font-semibold">
                                            <?php echo substr($client['name'], 0, 1); ?>
                                        </div>
                                        <div class="ml-3">
                                            <p class="font-medium text-gray-900"><?php echo e($client['name']); ?></p>
                                            <p class="text-sm text-gray-500">Cliente desde <?php echo date('M Y', strtotime($client['created_at'])); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm text-gray-900"><?php echo e($client['phone']); ?></p>
                                    <p class="text-sm text-gray-500"><?php echo e($client['email']); ?></p>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <?php echo $client['total_appointments']; ?> citas
                                </td>
                                <td class="px-6 py-4 text-sm font-semibold text-green-600">
                                    <?php echo formatPrice($client['total_spent']); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <?php echo $client['last_visit'] ? formatDate($client['last_visit']) : 'Nunca'; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($client['phone']): ?>
                                    <a href="https://wa.me/1<?php echo preg_replace('/[^0-9]/', '', $client['phone']); ?>" 
                                       target="_blank"
                                       class="inline-flex items-center px-3 py-1 bg-green-500 text-white rounded-lg hover:bg-green-600 text-sm">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                                        </svg>
                                        Enviar
                                    </a>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-medium space-x-2">
                                    <a href="?view=<?php echo $client['id']; ?>" class="text-blue-600 hover:text-blue-900">Ver</a>
                                    <a href="?edit=<?php echo $client['id']; ?>" class="text-indigo-600 hover:text-indigo-900">Editar</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Nuevo Cliente -->
    <div x-show="showModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="showModal = false"></div>
            
            <div class="relative bg-white rounded-lg max-w-md w-full p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Nuevo Cliente</h3>
                
                <form method="POST">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nombre Completo</label>
                            <input type="text" name="name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Teléfono</label>
                            <input type="tel" name="phone" required class="w-full px-3 py-2 border border-gray-300 rounded-lg" 
                                   placeholder="809-555-1234">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email (Opcional)</label>
                            <input type="email" name="email" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        </div>
                        
                        <div class="flex space-x-3 pt-4">
                            <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                                Crear Cliente
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

    <!-- Modal Ver Cliente -->
    <div x-show="showViewModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="window.location='clients.php'"></div>

            <div class="relative bg-white rounded-lg max-w-2xl w-full p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Detalle del Cliente</h3>
                    <a href="clients.php" class="text-gray-500 hover:text-gray-700">Cerrar</a>
                </div>

                <?php if ($viewClient): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div class="bg-gray-50 rounded p-3">
                        <p class="text-gray-500">Nombre</p>
                        <p class="font-semibold text-gray-900"><?php echo e($viewClient['name']); ?></p>
                    </div>
                    <div class="bg-gray-50 rounded p-3">
                        <p class="text-gray-500">Telefono</p>
                        <p class="font-semibold text-gray-900"><?php echo e($viewClient['phone']); ?></p>
                    </div>
                    <div class="bg-gray-50 rounded p-3">
                        <p class="text-gray-500">Email</p>
                        <p class="font-semibold text-gray-900"><?php echo e($viewClient['email'] ?? 'Sin email'); ?></p>
                    </div>
                    <div class="bg-gray-50 rounded p-3">
                        <p class="text-gray-500">Desde</p>
                        <p class="font-semibold text-gray-900"><?php echo formatDate($viewClient['created_at']); ?></p>
                    </div>
                    <div class="bg-gray-50 rounded p-3 md:col-span-2">
                        <p class="text-gray-500">Notas</p>
                        <p class="text-gray-800"><?php echo e($viewClient['notes'] ?? 'Sin notas'); ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Editar Cliente -->
    <div x-show="showEditModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="window.location='clients.php'"></div>

            <div class="relative bg-white rounded-lg max-w-md w-full p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Editar Cliente</h3>
                    <a href="clients.php" class="text-gray-500 hover:text-gray-700">Cerrar</a>
                </div>

                <?php if ($editClient): ?>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="client_id" value="<?php echo $editClient['id']; ?>">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                        <input type="text" name="name" value="<?php echo e($editClient['name']); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Telefono</label>
                        <input type="text" name="phone" value="<?php echo e($editClient['phone']); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" value="<?php echo e($editClient['email'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
                        <textarea name="notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg"><?php echo e($editClient['notes'] ?? ''); ?></textarea>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Guardar</button>
                        <a href="clients.php" class="flex-1 text-center px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">Cancelar</a>
                    </div>
                </form>

                <form method="POST" class="mt-3" onsubmit="return confirm('Eliminar este cliente?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="client_id" value="<?php echo $editClient['id']; ?>">
                    <button type="submit" class="w-full px-4 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200">Eliminar Cliente</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include BASE_PATH . '/includes/footer.php'; ?>
