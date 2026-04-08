<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Auth.php';
require_once BASE_PATH . '/core/Helpers.php';

Auth::requireRole('owner');

$db = Database::getInstance();
$barbershopId = $_SESSION['barbershop_id'] ?? null;

if (!$barbershopId) {
    $_SESSION['error'] = 'No se encontro una barberia asociada a tu cuenta.';
    header('Location: ' . BASE_URL . '/dashboard');
    exit;
}

$barbershop = $db->fetch(
    "SELECT * FROM barbershops WHERE id = ? AND owner_id = ? LIMIT 1",
    [$barbershopId, $_SESSION['user_id']]
);

if (!$barbershop) {
    $_SESSION['error'] = 'No tienes permisos para editar esta barberia.';
    header('Location: ' . BASE_URL . '/dashboard');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $businessName = trim($_POST['business_name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $province = trim($_POST['province'] ?? '');
        $rnc = trim($_POST['rnc'] ?? '');
        $website = trim($_POST['website'] ?? '');
        $facebook = trim($_POST['facebook'] ?? '');
        $instagram = trim($_POST['instagram'] ?? '');
        $tiktok = trim($_POST['tiktok'] ?? '');
        $whatsapp = trim($_POST['whatsapp'] ?? '');
        $themeColor = trim($_POST['theme_color'] ?? '#c9901a');
        $allowOnlineBooking = isset($_POST['allow_online_booking']) ? 1 : 0;
        $advanceBookingDays = max(1, min(365, intval($_POST['advance_booking_days'] ?? 30)));
        $cancellationHours = max(0, min(168, intval($_POST['cancellation_hours'] ?? 24)));

        if ($businessName === '') {
            throw new Exception('El nombre del negocio es obligatorio.');
        }

        if ($slug === '') {
            $slug = generateSlug($businessName);
        }

        if ($slug === '') {
            throw new Exception('No se pudo generar un slug valido.');
        }

        $existing = $db->fetch(
            "SELECT id FROM barbershops WHERE slug = ? AND id != ? LIMIT 1",
            [$slug, $barbershopId]
        );
        if ($existing) {
            throw new Exception('El slug ya esta en uso. Prueba con otro.');
        }

        $logo = $barbershop['logo'];
        $coverImage = $barbershop['cover_image'];

        if (isset($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadLogo = uploadImage($_FILES['logo'], 'barbershops', [
                'maxSize' => 2 * 1024 * 1024,
                'maxWidth' => 800,
                'maxHeight' => 800,
                'forceSquare' => true,
                'squareSize' => 700,
                'oldFile' => $barbershop['logo']
            ]);

            if (!$uploadLogo['success']) {
                throw new Exception('Error al subir logo: ' . $uploadLogo['message']);
            }

            $logo = $uploadLogo['path'];
        }

        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadCover = uploadImage($_FILES['cover_image'], 'barbershops', [
                'maxSize' => 5 * 1024 * 1024,
                'maxWidth' => 1920,
                'maxHeight' => 1080,
                'oldFile' => $barbershop['cover_image']
            ]);

            if (!$uploadCover['success']) {
                throw new Exception('Error al subir portada: ' . $uploadCover['message']);
            }

            $coverImage = $uploadCover['path'];
        }

        $db->query(
            "UPDATE barbershops SET
                business_name = ?,
                slug = ?,
                description = ?,
                phone = ?,
                email = ?,
                address = ?,
                city = ?,
                province = ?,
                rnc = ?,
                website = ?,
                facebook = ?,
                instagram = ?,
                tiktok = ?,
                whatsapp = ?,
                theme_color = ?,
                allow_online_booking = ?,
                advance_booking_days = ?,
                cancellation_hours = ?,
                logo = ?,
                cover_image = ?
            WHERE id = ? AND owner_id = ?",
            [
                $businessName,
                $slug,
                $description,
                $phone,
                $email,
                $address,
                $city,
                $province,
                $rnc,
                $website,
                $facebook,
                $instagram,
                $tiktok,
                $whatsapp,
                $themeColor,
                $allowOnlineBooking,
                $advanceBookingDays,
                $cancellationHours,
                $logo,
                $coverImage,
                $barbershopId,
                $_SESSION['user_id']
            ]
        );

        if (isset($_SESSION['barbershop_slug'])) {
            $_SESSION['barbershop_slug'] = $slug;
        }

        $_SESSION['success'] = 'Branding actualizado correctamente.';
        header('Location: ' . BASE_URL . '/dashboard/settings');
        exit;

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }

    $barbershop = $db->fetch(
        "SELECT * FROM barbershops WHERE id = ? AND owner_id = ? LIMIT 1",
        [$barbershopId, $_SESSION['user_id']]
    );
}

$title = 'Branding de Barberia - ' . $barbershop['business_name'];
include BASE_PATH . '/includes/header.php';
?>

<div class="min-h-screen bg-gray-100" x-data="{ sidebarOpen: false }">
    <?php include BASE_PATH . '/includes/sidebar-owner.php'; ?>

    <div class="lg:pl-64">
        <div class="sticky top-0 z-40 flex h-16 bg-white border-b border-gray-200 shadow-sm">
            <button @click="sidebarOpen = true" class="px-4 text-gray-500 lg:hidden">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <div class="flex items-center justify-between flex-1 px-4 sm:px-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Branding y Perfil Publico</h1>
                    <p class="text-sm text-gray-500">Personaliza logo, portada, color, texto y enlaces de tu barberia.</p>
                </div>
                <a href="<?php echo BASE_URL; ?>/public/<?php echo e($barbershop['slug']); ?>" target="_blank" class="px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-black transition text-sm font-medium">
                    Ver pagina publica
                </a>
            </div>
        </div>

        <main class="p-6">
            <?php if (isset($_SESSION['success'])): ?>
            <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-lg">
                <p class="text-green-700"><?php echo e($_SESSION['success']); unset($_SESSION['success']); ?></p>
            </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
                <p class="text-red-700"><?php echo e($_SESSION['error']); unset($_SESSION['error']); ?></p>
            </div>
            <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data" class="max-w-6xl space-y-6"
                                    x-data="{
                                        themeColor: '<?php echo e($barbershop['theme_color'] ?: '#c9901a'); ?>',
                                        logoPreview: '',
                                        coverPreview: '',
                                        setLogoPreview(event) {
                                                const file = event.target.files && event.target.files[0] ? event.target.files[0] : null;
                                                this.logoPreview = file ? URL.createObjectURL(file) : '';
                                        },
                                        setCoverPreview(event) {
                                                const file = event.target.files && event.target.files[0] ? event.target.files[0] : null;
                                                this.coverPreview = file ? URL.createObjectURL(file) : '';
                                        }
                                    }">

                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Identidad visual</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-3">Logo</label>
                            <?php if (!empty($barbershop['logo'])): ?>
                            <div class="mb-3" x-show="!logoPreview">
                                <img src="<?php echo imageUrl($barbershop['logo']); ?>" alt="Logo actual" class="w-24 h-24 rounded-full object-cover border border-gray-200 shadow-sm">
                            </div>
                            <?php endif; ?>
                            <div class="mb-3" x-show="logoPreview">
                                <p class="text-xs text-gray-500 mb-2">Vista previa:</p>
                                <img :src="logoPreview" alt="Vista previa logo" class="w-24 h-24 rounded-full object-cover border border-indigo-200 shadow-sm">
                            </div>
                            <input type="file" name="logo" accept="image/*" @change="setLogoPreview($event)" class="w-full text-sm text-gray-600 border border-gray-300 rounded-lg p-2">
                            <p class="text-xs text-gray-500 mt-1">Recomendado: PNG/JPG cuadrado, max 2MB.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-3">Portada</label>
                            <?php if (!empty($barbershop['cover_image'])): ?>
                            <div class="mb-3" x-show="!coverPreview">
                                <img src="<?php echo imageUrl($barbershop['cover_image']); ?>" alt="Portada actual" class="w-full max-w-sm h-24 rounded-lg object-cover border border-gray-200 shadow-sm">
                            </div>
                            <?php endif; ?>
                            <div class="mb-3" x-show="coverPreview">
                                <p class="text-xs text-gray-500 mb-2">Vista previa:</p>
                                <img :src="coverPreview" alt="Vista previa portada" class="w-full max-w-sm h-24 rounded-lg object-cover border border-indigo-200 shadow-sm">
                            </div>
                            <input type="file" name="cover_image" accept="image/*" @change="setCoverPreview($event)" class="w-full text-sm text-gray-600 border border-gray-300 rounded-lg p-2">
                            <p class="text-xs text-gray-500 mt-1">Recomendado: 1920x1080, max 5MB.</p>
                        </div>
                    </div>

                    <div class="mt-6 max-w-xs">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Color de marca</label>
                        <div class="flex items-center gap-3">
                            <input type="color" name="theme_color" x-model="themeColor" class="w-14 h-10 border border-gray-300 rounded-lg cursor-pointer">
                            <input type="text" :value="themeColor" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg bg-gray-50" readonly>
                            <span class="w-6 h-6 rounded-full border border-gray-300" :style="'background:' + themeColor"></span>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Informacion publica</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nombre del negocio *</label>
                            <input type="text" name="business_name" required value="<?php echo e($barbershop['business_name']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Slug (URL publica) *</label>
                            <input type="text" name="slug" required value="<?php echo e($barbershop['slug']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            <p class="text-xs text-gray-500 mt-1">Tu URL sera: <?php echo BASE_URL; ?>/public/<span class="font-mono"><?php echo e($barbershop['slug']); ?></span></p>
                        </div>
                    </div>

                    <div class="mt-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Descripcion</label>
                        <textarea name="description" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"><?php echo e($barbershop['description']); ?></textarea>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Contacto y redes</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Telefono</label>
                            <input type="text" name="phone" value="<?php echo e($barbershop['phone']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                            <input type="email" name="email" value="<?php echo e($barbershop['email']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">WhatsApp</label>
                            <input type="text" name="whatsapp" value="<?php echo e($barbershop['whatsapp']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg" placeholder="18095551234">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Website</label>
                            <input type="text" name="website" value="<?php echo e($barbershop['website']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg" placeholder="https://...">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Facebook</label>
                            <input type="text" name="facebook" value="<?php echo e($barbershop['facebook']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg" placeholder="https://facebook.com/...">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Instagram</label>
                            <input type="text" name="instagram" value="<?php echo e($barbershop['instagram']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg" placeholder="https://instagram.com/...">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">TikTok</label>
                            <input type="text" name="tiktok" value="<?php echo e($barbershop['tiktok']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg" placeholder="https://tiktok.com/...">
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Direccion y reglas de reserva</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Direccion</label>
                            <input type="text" name="address" value="<?php echo e($barbershop['address']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Ciudad</label>
                            <input type="text" name="city" value="<?php echo e($barbershop['city']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Provincia</label>
                            <input type="text" name="province" value="<?php echo e($barbershop['province']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">RNC</label>
                            <input type="text" name="rnc" value="<?php echo e($barbershop['rnc']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Dias maximos de reserva anticipada</label>
                            <input type="number" name="advance_booking_days" min="1" max="365" value="<?php echo e((string) $barbershop['advance_booking_days']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Horas minimas para cancelar</label>
                            <input type="number" name="cancellation_hours" min="0" max="168" value="<?php echo e((string) $barbershop['cancellation_hours']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        </div>
                    </div>

                    <label class="mt-6 inline-flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="allow_online_booking" value="1" <?php echo !empty($barbershop['allow_online_booking']) ? 'checked' : ''; ?> class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                        <span class="text-sm font-medium text-gray-700">Permitir reservas online en mi pagina publica</span>
                    </label>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <button type="submit" class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-medium">
                        Guardar cambios de branding
                    </button>
                    <a href="<?php echo BASE_URL; ?>/dashboard/public-links" class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition font-medium">
                        Ver enlaces publicos
                    </a>
                </div>

            </form>
        </main>
    </div>
</div>

<?php include BASE_PATH . '/includes/footer.php'; ?>
