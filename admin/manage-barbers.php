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

// Obtener barberos de esta barbería
$barbers = $db->fetchAll("
    SELECT b.*, u.full_name, u.email, u.phone
    FROM barbers b
    INNER JOIN users u ON b.user_id = u.id
    WHERE b.barbershop_id = ?
    ORDER BY b.created_at DESC
", [$shopId]);

// Obtener usuarios con rol barber que NO están asignados a esta barbería
$availableBarbers = $db->fetchAll("
    SELECT u.id, u.full_name, u.email
    FROM users u
    WHERE u.role = 'barber'
    AND NOT EXISTS (
        SELECT 1 FROM barbers b 
        WHERE b.user_id = u.id AND b.barbershop_id = ?
    )
    ORDER BY u.full_name ASC
", [$shopId]);

// Procesar asignación de barbero
if (isset($_POST['assign_barber'])) {
    try {
        $userId = $_POST['user_id'] ?? null;
        $specialty = trim($_POST['specialty'] ?? '');
        
        if (!$userId) {
            throw new Exception('Debe seleccionar un barbero');
        }
        
        // Obtener datos del usuario
        $user = $db->fetch("SELECT full_name, phone FROM users WHERE id = ?", [$userId]);
        
        if (!$user) {
            throw new Exception('Usuario no encontrado');
        }
        
        // Verificar si ya existe
        $existing = $db->fetch("SELECT id FROM barbers WHERE user_id = ? AND barbershop_id = ?", [$userId, $shopId]);
        
        if ($existing) {
            throw new Exception('Este barbero ya está asignado a esta barbería');
        }
        
        // Generar slug
        $slug = strtolower(str_replace(' ', '-', $user['full_name'])) . '-' . rand(100, 999);
        
        // Procesar upload de foto
        $photo = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadResult = uploadImage($_FILES['photo'], 'barbers', [
                'maxSize' => 2 * 1024 * 1024, // 2MB
                'maxWidth' => 800,
                'maxHeight' => 800
            ]);
            
            if ($uploadResult['success']) {
                $photo = $uploadResult['path'];
            } else {
                throw new Exception('Error en foto: ' . $uploadResult['message']);
            }
        }
        
        // Crear registro de barbero
        $db->query("
            INSERT INTO barbers (user_id, barbershop_id, slug, specialty, photo, status, rating, total_reviews, created_at)
            VALUES (?, ?, ?, ?, ?, 'active', 5.0, 0, NOW())
        ", [$userId, $shopId, $slug, $specialty, $photo]);
        
        $_SESSION['success'] = 'Barbero asignado exitosamente';
        header("Location: manage-barbers.php?id=$shopId");
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

// Procesar eliminación
if (isset($_GET['remove'])) {
    $barberId = $_GET['remove'];
    $db->query("DELETE FROM barbers WHERE id = ? AND barbershop_id = ?", [$barberId, $shopId]);
    $_SESSION['success'] = 'Barbero removido de la barbería';
    header("Location: manage-barbers.php?id=$shopId");
    exit;
}

// Procesar cambio de estado
if (isset($_GET['toggle'])) {
    $barberId = $_GET['toggle'];
    $barber = $db->fetch("SELECT status FROM barbers WHERE id = ? AND barbershop_id = ?", [$barberId, $shopId]);
    
    if ($barber) {
        $newStatus = $barber['status'] === 'active' ? 'inactive' : 'active';
        $db->query("UPDATE barbers SET status = ? WHERE id = ?", [$newStatus, $barberId]);
        $_SESSION['success'] = 'Estado actualizado';
    }
    
    header("Location: manage-barbers.php?id=$shopId");
    exit;
}

$title = 'Gestión de Barberos - ' . $shop['business_name'];
include BASE_PATH . '/includes/header.php';
?>

<div class="min-h-screen bg-gray-100" x-data="{ sidebarOpen: false, showModal: false }">
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
                    <h1 class="text-2xl font-bold text-gray-900">Barberos de <?php echo htmlspecialchars($shop['business_name']); ?></h1>
                    <p class="text-sm text-gray-500">Gestiona el equipo de barberos</p>
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
                    <p class="text-sm text-gray-600">Total Barberos</p>
                    <p class="text-3xl font-bold text-indigo-600"><?php echo count($barbers); ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm text-gray-600">Activos</p>
                    <p class="text-3xl font-bold text-green-600">
                        <?php echo count(array_filter($barbers, fn($b) => $b['status'] === 'active')); ?>
                    </p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm text-gray-600">Rating Promedio</p>
                    <p class="text-3xl font-bold text-yellow-600">
                        <?php 
                        $avgRating = count($barbers) > 0 ? array_sum(array_column($barbers, 'rating')) / count($barbers) : 5.0;
                        echo number_format($avgRating, 1); 
                        ?> ★
                    </p>
                </div>
            </div>

            <!-- Botón Asignar -->
            <?php if (!empty($availableBarbers)): ?>
            <div class="mb-6">
                <button @click="showModal = true" class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 shadow-lg">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Asignar Barbero
                </button>
            </div>
            <?php endif; ?>

            <!-- Lista de Barberos -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Barberos Asignados</h2>
                </div>
                
                <?php if (!empty($barbers)): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Barbero</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Especialidad</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rating</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($barbers as $barber): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <?php if ($barber['photo']): ?>
                                        <img src="<?php echo imageUrl($barber['photo']); ?>" 
                                             alt="<?php echo htmlspecialchars($barber['full_name']); ?>"
                                             class="w-12 h-12 rounded-full object-cover mr-3 border-2 border-gray-200">
                                        <?php else: ?>
                                        <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center mr-3 border-2 border-gray-200">
                                            <span class="text-indigo-600 font-bold text-lg">
                                                <?php echo strtoupper(substr($barber['full_name'], 0, 1)); ?>
                                            </span>
                                        </div>
                                        <?php endif; ?>
                                        <div>
                                            <p class="font-medium text-gray-900"><?php echo htmlspecialchars($barber['full_name']); ?></p>
                                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($barber['email']); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <?php echo $barber['specialty'] ? htmlspecialchars($barber['specialty']) : '-'; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <span class="text-yellow-500 mr-1">★</span>
                                        <span class="text-sm font-medium"><?php echo number_format($barber['rating'], 1); ?></span>
                                        <span class="text-xs text-gray-500 ml-1">(<?php echo $barber['total_reviews']; ?>)</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $barber['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                        <?php echo ucfirst($barber['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm space-x-2">
                                    <a href="?id=<?php echo $shopId; ?>&toggle=<?php echo $barber['id']; ?>" 
                                       class="text-orange-600 hover:text-orange-900">
                                        <?php echo $barber['status'] === 'active' ? 'Desactivar' : 'Activar'; ?>
                                    </a>
                                    <a href="?id=<?php echo $shopId; ?>&remove=<?php echo $barber['id']; ?>" 
                                       onclick="return confirm('¿Remover barbero de esta barbería?')"
                                       class="text-red-600 hover:text-red-900">
                                        Remover
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-12">
                    <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <p class="text-gray-500">No hay barberos asignados a esta barbería</p>
                    <?php if (!empty($availableBarbers)): ?>
                    <button @click="showModal = true" class="mt-4 text-indigo-600 hover:text-indigo-700 font-medium">
                        Asignar primer barbero
                    </button>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Modal Asignar Barbero -->
    <div x-show="showModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showModal = false"></div>
            
            <div class="relative bg-white rounded-lg shadow-xl max-w-lg w-full p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-4">Asignar Barbero</h3>
                
                <?php if (empty($availableBarbers)): ?>
                <p class="text-gray-600 mb-4">No hay barberos disponibles para asignar. Todos los barberos del sistema ya están asignados a esta barbería.</p>
                <button @click="showModal = false" class="w-full px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                    Cerrar
                </button>
                <?php else: ?>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="assign_barber" value="1">
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Seleccionar Barbero *</label>
                            <select name="user_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" required>
                                <option value="">Seleccionar...</option>
                                <?php foreach ($availableBarbers as $availBarber): ?>
                                <option value="<?php echo $availBarber['id']; ?>">
                                    <?php echo htmlspecialchars($availBarber['full_name']); ?> (<?php echo htmlspecialchars($availBarber['email']); ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Especialidad</label>
                            <input type="text" name="specialty" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                   placeholder="Ej: Cortes modernos, Barbas, Diseños">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Foto del Barbero</label>
                            <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 hover:border-indigo-500 transition">
                                <input type="file" 
                                       name="photo" 
                                       accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                                       class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                                <p class="mt-2 text-xs text-gray-500">
                                    JPG, PNG, GIF o WebP. Máx. 2MB. Recomendado: 800x800px
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-6 flex gap-3">
                        <button type="button" @click="showModal = false" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                            Cancelar
                        </button>
                        <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                            Asignar
                        </button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
[x-cloak] { display: none !important; }
</style>

<?php include BASE_PATH . '/includes/footer.php'; ?>
