<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Auth.php';
require_once BASE_PATH . '/core/Helpers.php';

Auth::requireRole('barber');

$db = Database::getInstance();

$barber = $db->fetch(
    "SELECT b.*, u.id as user_id, u.full_name, u.email as barber_email, u.phone as barber_phone,
            bb.business_name, bb.slug as barbershop_slug, bb.phone as barbershop_phone,
            bb.email as barbershop_email, bb.address as barbershop_address, bb.city as barbershop_city,
            bb.province as barbershop_province, bb.description as barbershop_description,
            bb.allow_online_booking
     FROM barbers b
     JOIN barbershops bb ON b.barbershop_id = bb.id
     JOIN users u ON b.user_id = u.id
     WHERE u.id = ?
     LIMIT 1",
    [$_SESSION['user_id']]
);

if (!$barber) {
    die('Barbero no encontrado');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = input('action');

    if ($action === 'update_profile') {
        $fullName = trim((string) input('full_name'));
        $phone = trim((string) input('phone'));
        $specialty = trim((string) input('specialty'));
        $bio = trim((string) input('bio'));
        $experienceYears = intval(input('experience_years'));

        $db->execute(
            "UPDATE users SET full_name = ?, phone = ?, updated_at = NOW() WHERE id = ?",
            [$fullName, $phone, $barber['user_id']]
        );

        $db->execute(
            "UPDATE barbers SET specialty = ?, bio = ?, experience_years = ?, updated_at = NOW() WHERE id = ?",
            [$specialty, $bio ?: null, $experienceYears, $barber['id']]
        );

        $_SESSION['user_name'] = $fullName;

        setFlash('success', 'Perfil actualizado correctamente');
        redirect($_SERVER['PHP_SELF']);
    }

    if ($action === 'change_password') {
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        $user = $db->fetch("SELECT password FROM users WHERE id = ?", [$barber['user_id']]);
        if (!$user || !password_verify($currentPassword, $user['password'])) {
            setFlash('error', 'La contraseña actual no es correcta');
            redirect($_SERVER['PHP_SELF']);
        }

        if (strlen($newPassword) < 8) {
            setFlash('error', 'La nueva contraseña debe tener al menos 8 caracteres');
            redirect($_SERVER['PHP_SELF']);
        }

        if ($newPassword !== $confirmPassword) {
            setFlash('error', 'La confirmacion de contraseña no coincide');
            redirect($_SERVER['PHP_SELF']);
        }

        $db->execute(
            "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?",
            [password_hash($newPassword, PASSWORD_DEFAULT), $barber['user_id']]
        );

        setFlash('success', 'Contraseña actualizada correctamente');
        redirect($_SERVER['PHP_SELF']);
    }
}

$flash = getFlash();
$activeBarberPage = 'profile';
$title = 'Mi Perfil - Panel Barbero';
include BASE_PATH . '/includes/header.php';
?>

<div class="min-h-screen bg-gray-100" x-data="{ sidebarOpen: false }">
    <?php include BASE_PATH . '/includes/sidebar-barber.php'; ?>

    <div class="lg:pl-64">
        <div class="sticky top-0 z-40 flex h-16 bg-white border-b border-gray-200 shadow-sm">
            <button @click="sidebarOpen = true" class="px-4 text-gray-500 lg:hidden">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <div class="flex items-center justify-between flex-1 px-4 sm:px-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Mi Perfil</h1>
                    <p class="text-sm text-gray-600">Informacion personal y barberia asignada</p>
                </div>
            </div>
        </div>

        <main class="p-6">
            <?php if ($flash): ?>
            <div class="mb-6 rounded-lg p-4 border-l-4 <?php echo $flash['type'] === 'success' ? 'bg-green-50 border-green-500 text-green-700' : 'bg-red-50 border-red-500 text-red-700'; ?>">
                <?php echo e($flash['message']); ?>
            </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Datos del Barbero</h2>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="update_profile">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm text-gray-700 mb-1">Nombre completo</label>
                                <input type="text" name="full_name" value="<?php echo e($barber['full_name']); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-700 mb-1">Email</label>
                                <input type="email" value="<?php echo e($barber['barber_email']); ?>" disabled class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-600">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-700 mb-1">Telefono</label>
                                <input type="text" name="phone" value="<?php echo e($barber['barber_phone']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-700 mb-1">Especialidad</label>
                                <input type="text" name="specialty" value="<?php echo e($barber['specialty']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-700 mb-1">Años de experiencia</label>
                                <input type="number" min="0" name="experience_years" value="<?php echo intval($barber['experience_years']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-700 mb-1">Comision (%)</label>
                                <input type="text" value="<?php echo number_format(floatval($barber['commission_rate'] ?: 100), 1); ?>" disabled class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-600">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm text-gray-700 mb-1">Biografia</label>
                            <textarea name="bio" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg"><?php echo e($barber['bio'] ?? ''); ?></textarea>
                        </div>

                        <button type="submit" class="px-5 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Guardar Perfil</button>
                    </form>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Barberia asignada</h2>
                    <div class="space-y-3 text-sm">
                        <div>
                            <p class="text-gray-500">Nombre</p>
                            <p class="font-semibold text-gray-900"><?php echo e($barber['business_name']); ?></p>
                        </div>
                        <div>
                            <p class="text-gray-500">Telefono</p>
                            <p class="text-gray-900"><?php echo e($barber['barbershop_phone'] ?: 'No definido'); ?></p>
                        </div>
                        <div>
                            <p class="text-gray-500">Email</p>
                            <p class="text-gray-900"><?php echo e($barber['barbershop_email'] ?: 'No definido'); ?></p>
                        </div>
                        <div>
                            <p class="text-gray-500">Direccion</p>
                            <p class="text-gray-900"><?php echo e($barber['barbershop_address'] ?: 'No definida'); ?></p>
                            <p class="text-gray-500"><?php echo e(trim(($barber['barbershop_city'] ?: '') . ' ' . ($barber['barbershop_province'] ?: ''))); ?></p>
                        </div>
                        <div>
                            <p class="text-gray-500">Reservas online</p>
                            <p class="font-semibold <?php echo intval($barber['allow_online_booking']) === 1 ? 'text-green-600' : 'text-red-600'; ?>">
                                <?php echo intval($barber['allow_online_booking']) === 1 ? 'Habilitadas' : 'Deshabilitadas'; ?>
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <a href="<?php echo BASE_URL; ?>/public/barber.php?shop=<?php echo urlencode($barber['barbershop_slug']); ?>&barber=<?php echo urlencode($barber['slug']); ?>"
                           target="_blank"
                           class="inline-flex items-center px-4 py-2 bg-indigo-100 text-indigo-700 rounded-lg hover:bg-indigo-200">
                            Ver mi pagina publica
                        </a>
                    </div>
                </div>
            </div>

            <div class="mt-6 bg-white rounded-lg shadow-md p-6 max-w-2xl">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Seguridad</h2>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="change_password">

                    <div>
                        <label class="block text-sm text-gray-700 mb-1">Contraseña actual</label>
                        <input type="password" name="current_password" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm text-gray-700 mb-1">Nueva contraseña</label>
                            <input type="password" name="new_password" required minlength="8" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 mb-1">Confirmar contraseña</label>
                            <input type="password" name="confirm_password" required minlength="8" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        </div>
                    </div>

                    <button type="submit" class="px-5 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800">Cambiar contraseña</button>
                </form>
            </div>
        </main>
    </div>
</div>

<?php include BASE_PATH . '/includes/footer.php'; ?>
