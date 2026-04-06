<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Auth.php';
require_once BASE_PATH . '/core/Helpers.php';

Auth::requireRole('superadmin');

$db = Database::getInstance();

// Obtener ID del usuario
$userId = $_GET['id'] ?? null;

if (!$userId) {
    header('Location: users.php');
    exit;
}

// Obtener datos del usuario
$user = $db->fetch("
    SELECT u.*
    FROM users u
    WHERE u.id = ?
", [$userId]);

if (!$user) {
    $_SESSION['error'] = 'Usuario no encontrado';
    header('Location: users.php');
    exit;
}

// Obtener todas las licencias disponibles
$licenses = $db->fetchAll("
    SELECT id, type, status
    FROM licenses
    WHERE status IN ('active', 'trial')
    ORDER BY type ASC
");

// Obtener todas las barberías (para asignar owners/barbers)
$barbershops = $db->fetchAll("
    SELECT id, business_name, owner_id
    FROM barbershops
    ORDER BY business_name ASC
");

$currentBarbershop = null;
if ($user['role'] === 'owner') {
    $currentBarbershop = $db->fetch("SELECT id, license_id FROM barbershops WHERE owner_id = ?", [$userId]);
} elseif ($user['role'] === 'barber') {
    $currentBarbershop = $db->fetch("SELECT barbershop_id AS id, slug FROM barbers WHERE user_id = ?", [$userId]);
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $role = $_POST['role'] ?? '';
        $status = $_POST['status'] ?? 'active';
        $licenseId = $_POST['license_id'] ?? null;
        $newPassword = trim($_POST['new_password'] ?? '');
        $barbershopId = !empty($_POST['barbershop_id']) ? (int) $_POST['barbershop_id'] : null;
        
        // Validaciones
        if (empty($fullName)) {
            throw new Exception('El nombre completo es obligatorio');
        }
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('El email no es válido');
        }
        
        if (!in_array($role, ['superadmin', 'owner', 'barber', 'client'])) {
            throw new Exception('Rol no válido');
        }

        if ($role === 'owner' && $barbershopId && empty($licenseId)) {
            throw new Exception('Debes seleccionar una licencia al asignar una barbería a un owner');
        }

        $currentOwnerBarbershop = $db->fetch("SELECT id, owner_id, license_id FROM barbershops WHERE owner_id = ?", [$userId]);
        $currentBarberRecord = $db->fetch("SELECT id, barbershop_id, slug FROM barbers WHERE user_id = ?", [$userId]);

        if ($user['role'] === 'owner' && $role !== 'owner' && $currentOwnerBarbershop) {
            throw new Exception('Reasigna primero la barbería del owner antes de cambiarle el rol');
        }

        if ($role === 'owner' && $currentOwnerBarbershop && $barbershopId && (int) $currentOwnerBarbershop['id'] !== $barbershopId) {
            throw new Exception('No puedes mover este owner a otra barbería desde esta pantalla sin reasignar antes la barbería actual');
        }

        if ($role === 'owner' && $barbershopId) {
            $selectedBarbershop = $db->fetch("SELECT id, owner_id FROM barbershops WHERE id = ?", [$barbershopId]);
            if (!$selectedBarbershop) {
                throw new Exception('La barbería seleccionada no existe');
            }
            if (!empty($selectedBarbershop['owner_id']) && (int) $selectedBarbershop['owner_id'] !== (int) $userId) {
                throw new Exception('La barbería seleccionada ya tiene otro owner asignado');
            }
        }
        
        // Verificar email único (excepto el propio usuario)
        $existingUser = $db->fetch("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $userId]);
        if ($existingUser) {
            throw new Exception('El email ya está en uso por otro usuario');
        }
        
        // Preparar datos de actualización
        $updateData = [
            'full_name' => $fullName,
            'email' => $email,
            'phone' => $phone,
            'role' => $role,
            'status' => $status
        ];
        
        // Si se proporciona nueva contraseña
        if (!empty($newPassword)) {
            if (strlen($newPassword) < 6) {
                throw new Exception('La contraseña debe tener al menos 6 caracteres');
            }
            $updateData['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
        }
        
        // Construir query de actualización
        $setClause = [];
        $values = [];
        foreach ($updateData as $key => $value) {
            $setClause[] = "$key = ?";
            $values[] = $value;
        }
        $values[] = $userId;
        
        $query = "UPDATE users SET " . implode(', ', $setClause) . " WHERE id = ?";
        $db->query($query, $values);
        
        if ($role !== 'barber' && $user['role'] === 'barber' && $currentBarberRecord) {
            $db->query("DELETE FROM barbers WHERE user_id = ?", [$userId]);
        }
        
        // Si es owner y se seleccionó una barbería, asignarla
        if ($role === 'owner' && $barbershopId) {
            $db->query(
                "UPDATE barbershops SET owner_id = ?, license_id = ? WHERE id = ?",
                [$userId, $licenseId, $barbershopId]
            );
        }
        
        // Si es barber y se seleccionó una barbería, crear/actualizar registro
        if ($role === 'barber' && $barbershopId) {
            if ($currentBarberRecord) {
                $slug = generateUniqueBarberSlug($db, $barbershopId, $fullName, (int) $currentBarberRecord['id']);
                $db->query("UPDATE barbers SET barbershop_id = ?, slug = ? WHERE user_id = ?", [$barbershopId, $slug, $userId]);
            } else {
                $slug = generateUniqueBarberSlug($db, $barbershopId, $fullName);
                $db->query("
                    INSERT INTO barbers (user_id, barbershop_id, slug, status, rating, total_reviews)
                    VALUES (?, ?, ?, 'active', 5.0, 0)
                ", [$userId, $barbershopId, $slug]);
            }
        }
        
        $_SESSION['success'] = 'Usuario actualizado exitosamente';
        header('Location: users.php');
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

$title = 'Editar Usuario - Super Admin';
include BASE_PATH . '/includes/header.php';
?>

<div class="min-h-screen bg-gray-100" x-data="{ sidebarOpen: false, role: '<?php echo $user['role']; ?>' }">
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
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Editar Usuario</h1>
                    <p class="text-sm text-gray-500">Control total sobre la información del usuario</p>
                </div>
                <a href="users.php" class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg">
                    <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Volver
                </a>
            </div>
        </div>

        <main class="p-6">
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

            <form method="POST" class="max-w-4xl space-y-6">
                <!-- Información Personal -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        Información Personal
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nombre Completo *</label>
                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Teléfono</label>
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nueva Contraseña</label>
                            <input type="password" name="new_password" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                   placeholder="Dejar en blanco para mantener actual">
                            <p class="text-xs text-gray-500 mt-1">Mínimo 6 caracteres</p>
                        </div>
                    </div>
                </div>

                <!-- Rol y Estado -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        Permisos y Estado
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Rol *</label>
                            <select name="role" x-model="role" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" required>
                                <option value="client" <?php echo $user['role'] === 'client' ? 'selected' : ''; ?>>Cliente</option>
                                <option value="barber" <?php echo $user['role'] === 'barber' ? 'selected' : ''; ?>>Barbero</option>
                                <option value="owner" <?php echo $user['role'] === 'owner' ? 'selected' : ''; ?>>Owner (Dueño)</option>
                                <option value="superadmin" <?php echo $user['role'] === 'superadmin' ? 'selected' : ''; ?>>Super Admin</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Estado *</label>
                            <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" required>
                                <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>Activo</option>
                                <option value="inactive" <?php echo $user['status'] === 'inactive' ? 'selected' : ''; ?>>Inactivo</option>
                                <option value="suspended" <?php echo $user['status'] === 'suspended' ? 'selected' : ''; ?>>Suspendido</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Asociaciones (Owner/Barber) -->
                <div class="bg-white rounded-lg shadow-md p-6" x-show="role === 'owner' || role === 'barber'">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        <span x-text="role === 'owner' ? 'Asignación de Barbería y Licencia' : 'Asignación de Barbería'"></span>
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Licencia (solo para owners) -->
                        <div x-show="role === 'owner'">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Licencia</label>
                            <select name="license_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                <option value="">Seleccionar licencia</option>
                                <?php foreach ($licenses as $license): ?>
                                <option value="<?php echo $license['id']; ?>" <?php echo (($currentBarbershop['license_id'] ?? null) == $license['id']) ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($license['type']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">La licencia determina cuántas barberías puede administrar</p>
                        </div>
                        
                        <!-- Barbería -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <span x-text="role === 'owner' ? 'Barbería Asignada' : 'Barbería Donde Trabaja'"></span>
                            </label>
                            <select name="barbershop_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                <option value="">Ninguna</option>
                                <?php foreach ($barbershops as $shop): 
                                    $selected = $currentBarbershop && $currentBarbershop['id'] == $shop['id'];
                                ?>
                                <option value="<?php echo $shop['id']; ?>" <?php echo $selected ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($shop['business_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Información Adicional -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Información del Sistema
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Fecha de Registro</p>
                            <p class="text-gray-900 mt-1"><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Último Login</p>
                            <p class="text-gray-900 mt-1"><?php echo $user['last_login'] ? timeAgo($user['last_login']) : 'Nunca'; ?></p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">ID de Usuario</p>
                            <p class="text-gray-900 mt-1 font-mono">#<?php echo $user['id']; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="flex justify-between items-center">
                    <a href="users.php" class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                        Cancelar
                    </a>
                    
                    <div class="space-x-3">
                        <button type="submit" class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition shadow-lg">
                            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Guardar Cambios
                        </button>
                    </div>
                </div>
            </form>
        </main>
    </div>
</div>

<script>
// Prevenir envío accidental del formulario
document.querySelector('form').addEventListener('submit', function(e) {
    if (!confirm('¿Estás seguro de guardar estos cambios?')) {
        e.preventDefault();
    }
});
</script>

<?php include BASE_PATH . '/includes/footer.php'; ?>
