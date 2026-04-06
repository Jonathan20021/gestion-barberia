<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Auth.php';
require_once BASE_PATH . '/core/Helpers.php';

Auth::requireRole('superadmin');

$db = Database::getInstance();

// Obtener todas las licencias disponibles
$licenses = $db->fetchAll("
    SELECT id, type, status
    FROM licenses
    WHERE status IN ('active', 'trial')
    ORDER BY type ASC
");

// Obtener todas las barberías
$barbershops = $db->fetchAll("
    SELECT id, business_name, owner_id
    FROM barbershops
    ORDER BY business_name ASC
");

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $role = $_POST['role'] ?? '';
        $status = $_POST['status'] ?? 'active';
        $licenseId = $_POST['license_id'] ?? null;
        
        // Validaciones
        if (empty($fullName)) {
            throw new Exception('El nombre completo es obligatorio');
        }
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('El email no es válido');
        }
        
        if (empty($password)) {
            throw new Exception('La contraseña es obligatoria');
        }
        
        if (strlen($password) < 6) {
            throw new Exception('La contraseña debe tener al menos 6 caracteres');
        }
        
        if (!in_array($role, ['superadmin', 'owner', 'barber', 'client'])) {
            throw new Exception('Rol no válido');
        }
        
        // Verificar email único
        $existingUser = $db->fetch("SELECT id FROM users WHERE email = ?", [$email]);
        if ($existingUser) {
            throw new Exception('El email ya está registrado');
        }
        
        // Crear usuario
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $db->query("
            INSERT INTO users (full_name, email, phone, password, role, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ", [
            $fullName,
            $email,
            $phone,
            $hashedPassword,
            $role,
            $status
        ]);
        
        $userId = $db->lastInsertId();
        
        // Si es owner y se seleccionó una barbería, asignarla
        if ($role === 'owner' && !empty($_POST['barbershop_id'])) {
            $barbershopId = $_POST['barbershop_id'];
            $db->query(
                "UPDATE barbershops SET owner_id = ?, license_id = ? WHERE id = ?",
                [$userId, !empty($licenseId) ? $licenseId : null, $barbershopId]
            );
        }
        
        // Si es barber y se seleccionó una barbería, crear registro
        if ($role === 'barber' && !empty($_POST['barbershop_id'])) {
            $barbershopId = $_POST['barbershop_id'];
            $db->query("
                INSERT INTO barbers (user_id, barbershop_id, full_name, phone, status, rating, total_reviews, created_at)
                VALUES (?, ?, ?, ?, 'active', 5.0, 0, NOW())
            ", [$userId, $barbershopId, $fullName, $phone]);
        }
        
        $_SESSION['success'] = 'Usuario creado exitosamente';
        header('Location: users.php');
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

$title = 'Crear Usuario - Super Admin';
include BASE_PATH . '/includes/header.php';
?>

<div class="min-h-screen bg-gray-100" x-data="{ sidebarOpen: false, role: 'client' }">
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
                    <h1 class="text-2xl font-bold text-gray-900">Crear Nuevo Usuario</h1>
                    <p class="text-sm text-gray-500">Agrega un nuevo usuario al sistema</p>
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
                            <input type="text" name="full_name" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Teléfono</label>
                            <input type="tel" name="phone" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                   placeholder="809-555-1234">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                            <input type="email" name="email" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Contraseña *</label>
                            <input type="password" name="password" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                   required>
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
                                <option value="client">Cliente</option>
                                <option value="barber">Barbero</option>
                                <option value="owner">Owner (Dueño)</option>
                                <option value="superadmin">Super Admin</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Estado *</label>
                            <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" required>
                                <option value="active">Activo</option>
                                <option value="inactive">Inactivo</option>
                                <option value="suspended">Suspendido</option>
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
                                <option value="">Sin licencia</option>
                                <?php foreach ($licenses as $license): ?>
                                <option value="<?php echo $license['id']; ?>">
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
                                <?php foreach ($barbershops as $shop): ?>
                                <option value="<?php echo $shop['id']; ?>">
                                    <?php echo htmlspecialchars($shop['business_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="flex justify-between items-center">
                    <a href="users.php" class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                        Cancelar
                    </a>
                    
                    <button type="submit" class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition shadow-lg">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Crear Usuario
                    </button>
                </div>
            </form>
        </main>
    </div>
</div>

<?php include BASE_PATH . '/includes/footer.php'; ?>
