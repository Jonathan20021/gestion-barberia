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
$editBarber = null;
if ($editId > 0) {
    $editBarber = $db->fetch(
        "SELECT b.*, u.full_name, u.email, u.phone, u.status as user_status
         FROM barbers b
         JOIN users u ON b.user_id = u.id
         WHERE b.id = ? AND b.barbershop_id = ?",
        [$editId, $barbershopId]
    );
}

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = input('action');
    
    if ($action === 'create') {
        // Crear usuario primero
        $email = input('email');
        $plainPassword = trim((string) input('password'));
        $confirmPassword = trim((string) input('password_confirm'));
        if (strlen($plainPassword) < 8) {
            setFlash('error', 'La contraseña debe tener al menos 8 caracteres');
            redirect($_SERVER['PHP_SELF']);
        }
        if ($plainPassword !== $confirmPassword) {
            setFlash('error', 'Las contraseñas no coinciden');
            redirect($_SERVER['PHP_SELF']);
        }
        $password = password_hash($plainPassword, PASSWORD_DEFAULT);
        $fullName = input('full_name');
        $phone = input('phone');
        
        $db->execute("
            INSERT INTO users (email, password, full_name, phone, role, status, created_at)
            VALUES (?, ?, ?, ?, 'barber', 'active', NOW())
        ", [$email, $password, $fullName, $phone]);
        
        $userId = $db->lastInsertId();
        
        // Crear barbero
        $specialty = input('specialty');
        $experience = input('experience_years');
        $commissionRate = floatval(input('commission_rate', 100));
        if ($commissionRate < 0) {
            $commissionRate = 0;
        }
        if ($commissionRate > 100) {
            $commissionRate = 100;
        }
        
        $db->execute("
            INSERT INTO barbers (user_id, barbershop_id, specialty, experience_years, commission_rate, status, created_at)
            VALUES (?, ?, ?, ?, ?, 'active', NOW())
        ", [$userId, $barbershopId, $specialty, $experience, $commissionRate]);
        
        setFlash('success', 'Barbero creado correctamente');
        redirect($_SERVER['PHP_SELF']);
    }

    if ($action === 'update') {
        $barberId = intval(input('barber_id'));
        $fullName = trim((string) input('full_name'));
        $email = trim((string) input('email'));
        $phone = trim((string) input('phone'));
        $specialty = trim((string) input('specialty'));
        $experience = intval(input('experience_years'));
        $commissionRate = floatval(input('commission_rate', 100));
        if ($commissionRate < 0) {
            $commissionRate = 0;
        }
        if ($commissionRate > 100) {
            $commissionRate = 100;
        }
        $status = input('status', 'active');
        $isFeatured = input('is_featured') ? 1 : 0;

        $barberCurrent = $db->fetch(
            "SELECT user_id FROM barbers WHERE id = ? AND barbershop_id = ?",
            [$barberId, $barbershopId]
        );

        if (!$barberCurrent) {
            setFlash('error', 'Barbero no encontrado');
            redirect($_SERVER['PHP_SELF']);
        }

        $db->execute(
            "UPDATE users SET full_name = ?, email = ?, phone = ?, status = ?, updated_at = NOW()
             WHERE id = ?",
            [$fullName, $email, $phone, $status, $barberCurrent['user_id']]
        );

        $db->execute(
            "UPDATE barbers
             SET specialty = ?, experience_years = ?, commission_rate = ?, status = ?, is_featured = ?, updated_at = NOW()
             WHERE id = ? AND barbershop_id = ?",
            [$specialty, $experience, $commissionRate, $status, $isFeatured, $barberId, $barbershopId]
        );

        setFlash('success', 'Barbero actualizado correctamente');
        redirect($_SERVER['PHP_SELF']);
    }
}

// Obtener barberos
$barbers = $db->fetchAll("
    SELECT 
        b.*,
        u.full_name,
        u.email,
        u.phone,
        u.status as user_status,
        COUNT(DISTINCT a.id) as total_appointments,
        COALESCE(AVG(r.rating), 0) as avg_rating,
        COUNT(DISTINCT r.id) as total_reviews
    FROM barbers b
    JOIN users u ON b.user_id = u.id
    LEFT JOIN appointments a ON b.id = a.barber_id AND a.status = 'completed'
    LEFT JOIN reviews r ON b.id = r.barber_id
    WHERE b.barbershop_id = ?
    GROUP BY b.id
    ORDER BY b.created_at DESC
", [$barbershopId]);

$flash = getFlash();

$title = 'Gestión de Barberos - Dashboard';
include BASE_PATH . '/includes/header.php';
?>

<div class="min-h-screen bg-gray-100" x-data="{ sidebarOpen: false, showModal: false, showEditModal: <?php echo $editBarber ? 'true' : 'false'; ?> }">
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
                <h1 class="text-2xl font-bold text-gray-900">Gestión de Barberos</h1>
                <button @click="showModal = true" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    + Añadir Barbero
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
                    <p class="text-sm text-gray-600">Citas Completadas</p>
                    <p class="text-3xl font-bold text-blue-600">
                        <?php echo array_sum(array_column($barbers, 'total_appointments')); ?>
                    </p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm text-gray-600">Rating Promedio</p>
                    <p class="text-3xl font-bold text-yellow-600">
                        <?php 
                        $avgRatings = array_filter(array_column($barbers, 'avg_rating'));
                        echo count($avgRatings) > 0 ? number_format(array_sum($avgRatings) / count($avgRatings), 1) : '0.0';
                        ?> ⭐
                    </p>
                </div>
            </div>

            <!-- Grid de Barberos -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($barbers as $barber): ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition">
                    <!-- Header con foto -->
                    <div class="bg-gradient-to-br from-indigo-500 to-purple-600 p-6 text-center">
                        <?php if ($barber['photo']): ?>
                        <img src="<?php echo asset($barber['photo']); ?>" class="w-24 h-24 rounded-full mx-auto border-4 border-white shadow-lg" alt="<?php echo e($barber['full_name']); ?>">
                        <?php else: ?>
                        <div class="w-24 h-24 rounded-full mx-auto border-4 border-white shadow-lg bg-white text-indigo-600 flex items-center justify-center text-3xl font-bold">
                            <?php echo substr($barber['full_name'], 0, 1); ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($barber['is_featured']): ?>
                        <span class="inline-block mt-2 px-3 py-1 bg-yellow-400 text-yellow-900 rounded-full text-xs font-semibold">
                            ⭐ Destacado
                        </span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Información -->
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-1"><?php echo e($barber['full_name']); ?></h3>
                        <p class="text-sm text-gray-600 mb-3"><?php echo e($barber['specialty']); ?></p>
                        
                        <div class="space-y-2 mb-4">
                            <div class="flex items-center text-sm text-gray-600">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                <?php echo e($barber['email']); ?>
                            </div>
                            <div class="flex items-center text-sm text-gray-600">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                                <?php echo e($barber['phone']); ?>
                            </div>
                        </div>
                        
                        <!-- Stats en tarjetas pequeñas -->
                        <div class="grid grid-cols-3 gap-2 mb-4">
                            <div class="bg-blue-50 rounded-lg p-2 text-center">
                                <p class="text-lg font-bold text-blue-600"><?php echo $barber['total_appointments']; ?></p>
                                <p class="text-xs text-gray-600">Citas</p>
                            </div>
                            <div class="bg-yellow-50 rounded-lg p-2 text-center">
                                <p class="text-lg font-bold text-yellow-600"><?php echo number_format($barber['avg_rating'], 1); ?>⭐</p>
                                <p class="text-xs text-gray-600">Rating</p>
                            </div>
                            <div class="bg-purple-50 rounded-lg p-2 text-center">
                                <p class="text-lg font-bold text-purple-600"><?php echo $barber['experience_years']; ?></p>
                                <p class="text-xs text-gray-600">Años</p>
                            </div>
                        </div>

                        <p class="text-xs text-gray-500 mb-4">Comisión: <span class="font-semibold text-gray-800"><?php echo number_format(floatval($barber['commission_rate'] ?: 100), 1); ?>%</span></p>
                        
                        <!-- Estado -->
                        <div class="mb-4">
                            <span class="px-3 py-1 text-xs font-medium rounded-full
                                <?php echo $barber['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo ucfirst($barber['status']); ?>
                            </span>
                        </div>
                        
                        <!-- Acciones -->
                        <div class="flex space-x-2">
                            <a href="../public/barber.php?shop=<?php echo $_SESSION['barbershop_slug']; ?>&barber=<?php echo $barber['slug']; ?>" 
                               target="_blank"
                               class="flex-1 px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-center text-sm">
                                Ver Página
                            </a>
                            <a href="?edit=<?php echo $barber['id']; ?>" 
                               class="flex-1 px-3 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-center text-sm">
                                Editar
                            </a>
                        </div>
                        
                        <?php if ($barber['phone']): ?>
                        <a href="https://wa.me/1<?php echo preg_replace('/[^0-9]/', '', $barber['phone']); ?>" 
                           target="_blank"
                           class="mt-2 flex items-center justify-center px-3 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 text-sm">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                            </svg>
                            Contactar por WhatsApp
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <!-- Modal Nuevo Barbero -->
    <div x-show="showModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="showModal = false"></div>
            
            <div class="relative bg-white rounded-lg max-w-md w-full p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Añadir Nuevo Barbero</h3>
                
                <form method="POST">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nombre Completo</label>
                            <input type="text" name="full_name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                            <input type="email" name="email" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            <p class="text-xs text-gray-500 mt-1">Se usará para iniciar sesión del barbero</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Contraseña</label>
                            <input type="password" name="password" required minlength="8" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Mínimo 8 caracteres">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Confirmar Contraseña</label>
                            <input type="password" name="password_confirm" required minlength="8" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Repite la contraseña">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Teléfono</label>
                            <input type="tel" name="phone" required class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="809-555-1234">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Especialidad</label>
                            <input type="text" name="specialty" placeholder="Ej: Cortes clásicos, Diseños modernos" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Años de Experiencia</label>
                            <input type="number" name="experience_years" value="1" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Comisión (%)</label>
                            <input type="number" name="commission_rate" value="100" min="0" max="100" step="0.1" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            <p class="text-xs text-gray-500 mt-1">Porcentaje del servicio que gana este barbero.</p>
                        </div>
                        
                        <div class="flex space-x-3 pt-4">
                            <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                                Crear Barbero
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

    <!-- Modal Editar Barbero -->
    <div x-show="showEditModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="window.location='barbers.php'"></div>

            <div class="relative bg-white rounded-lg max-w-md w-full p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Editar Barbero</h3>
                    <a href="barbers.php" class="text-gray-500 hover:text-gray-700">Cerrar</a>
                </div>

                <?php if ($editBarber): ?>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="barber_id" value="<?php echo $editBarber['id']; ?>">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                        <input type="text" name="full_name" value="<?php echo e($editBarber['full_name']); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" value="<?php echo e($editBarber['email']); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Telefono</label>
                        <input type="text" name="phone" value="<?php echo e($editBarber['phone']); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Especialidad</label>
                        <input type="text" name="specialty" value="<?php echo e($editBarber['specialty']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Experiencia (anos)</label>
                        <input type="number" name="experience_years" min="0" value="<?php echo intval($editBarber['experience_years']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Comisión (%)</label>
                        <input type="number" name="commission_rate" min="0" max="100" step="0.1" value="<?php echo number_format(floatval($editBarber['commission_rate'] ?: 100), 1, '.', ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                        <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            <option value="active" <?php echo $editBarber['status'] === 'active' ? 'selected' : ''; ?>>Activo</option>
                            <option value="inactive" <?php echo $editBarber['status'] === 'inactive' ? 'selected' : ''; ?>>Inactivo</option>
                        </select>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="is_featured" value="1" <?php echo intval($editBarber['is_featured']) === 1 ? 'checked' : ''; ?>>
                        Marcar como destacado
                    </label>

                    <div class="flex gap-3 pt-2">
                        <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Guardar</button>
                        <a href="barbers.php" class="flex-1 text-center px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">Cancelar</a>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include BASE_PATH . '/includes/footer.php'; ?>
