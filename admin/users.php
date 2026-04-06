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
    $userId = $_GET['toggle'];
    $user = $db->fetch("SELECT status, role FROM users WHERE id = ?", [$userId]);
    
    if ($user && $user['role'] !== 'superadmin') {
        $newStatus = $user['status'] === 'active' ? 'suspended' : 'active';
        $db->query("UPDATE users SET status = ? WHERE id = ?", [$newStatus, $userId]);
        $_SESSION['success'] = 'Estado del usuario actualizado';
    }
    
    header('Location: users.php');
    exit;
}

// Obtener todos los usuarios
$users = $db->fetchAll("
    SELECT 
        u.*,
        CASE 
            WHEN u.role = 'owner' THEN b.business_name
            WHEN u.role = 'barber' THEN bb.business_name
            ELSE NULL
        END as associated_barbershop
    FROM users u
    LEFT JOIN barbershops b ON u.id = b.owner_id AND u.role = 'owner'
    LEFT JOIN barbers br ON u.id = br.user_id AND u.role = 'barber'
    LEFT JOIN barbershops bb ON br.barbershop_id = bb.id
    ORDER BY u.created_at DESC
");

$title = 'Gestión de Usuarios - Super Admin';
include BASE_PATH . '/includes/header.php';
?>

<div class="min-h-screen bg-gray-100" x-data="{ sidebarOpen: false, showModal: false }">
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
                <h1 class="text-2xl font-bold text-gray-900">Gestión de Usuarios</h1>
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
                    <p class="text-sm text-gray-600">Total Usuarios</p>
                    <p class="text-3xl font-bold text-indigo-600"><?php echo count($users); ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm text-gray-600">Owners</p>
                    <p class="text-3xl font-bold text-blue-600">
                        <?php echo count(array_filter($users, fn($u) => $u['role'] === 'owner')); ?>
                    </p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm text-gray-600">Barberos</p>
                    <p class="text-3xl font-bold text-green-600">
                        <?php echo count(array_filter($users, fn($u) => $u['role'] === 'barber')); ?>
                    </p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm text-gray-600">Activos</p>
                    <p class="text-3xl font-bold text-purple-600">
                        <?php echo count(array_filter($users, fn($u) => $u['status'] === 'active')); ?>
                    </p>
                </div>
            </div>

            <!-- Tabla -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h2 class="text-lg font-semibold text-gray-900">Todos los Usuarios</h2>
                    <a href="create-user.php" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 inline-flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Nuevo Usuario
                    </a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usuario</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rol</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Asociación</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Último Login</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($users as $user): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <?php if ($user['avatar']): ?>
                                        <img src="<?php echo asset($user['avatar']); ?>" class="w-10 h-10 rounded-full mr-3" alt="Avatar">
                                        <?php else: ?>
                                        <div class="w-10 h-10 rounded-full bg-indigo-600 text-white flex items-center justify-center mr-3 font-semibold">
                                            <?php echo substr($user['full_name'], 0, 1); ?>
                                        </div>
                                        <?php endif; ?>
                                        <div>
                                            <p class="font-medium text-gray-900"><?php echo e($user['full_name']); ?></p>
                                            <p class="text-sm text-gray-500"><?php echo e($user['phone']); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900"><?php echo e($user['email']); ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full
                                        <?php 
                                            echo $user['role'] === 'superadmin' ? 'bg-red-100 text-red-800' :
                                                ($user['role'] === 'owner' ? 'bg-blue-100 text-blue-800' :
                                                ($user['role'] === 'barber' ? 'bg-green-100 text-green-800' : 
                                                'bg-gray-100 text-gray-800')); 
                                        ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <?php echo $user['associated_barbershop'] ? e($user['associated_barbershop']) : '-'; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full
                                        <?php echo $user['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <?php echo $user['last_login'] ? timeAgo($user['last_login']) : 'Nunca'; ?>
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-medium space-x-2">
                                    <a href="edit-user.php?id=<?php echo $user['id']; ?>" class="text-indigo-600 hover:text-indigo-900 font-medium">
                                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                        Editar
                                    </a>
                                    <?php if ($user['role'] !== 'superadmin'): ?>
                                    <a href="?toggle=<?php echo $user['id']; ?>" class="text-orange-600 hover:text-orange-900">
                                        <?php echo $user['status'] === 'active' ? 'Suspender' : 'Activar'; ?>
                                    </a>
                                    <?php endif; ?>
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
