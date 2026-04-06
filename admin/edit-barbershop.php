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

// Obtener datos de la barbería
$shop = $db->fetch("
    SELECT b.*, l.type as license_type, l.end_date as license_end_date, u.full_name as owner_name
    FROM barbershops b
    LEFT JOIN licenses l ON b.license_id = l.id
    LEFT JOIN users u ON b.owner_id = u.id
    WHERE b.id = ?
", [$shopId]);

if (!$shop) {
    $_SESSION['error'] = 'Barbería no encontrada';
    header('Location: barbershops.php');
    exit;
}

// Obtener todas las licencias disponibles
$licenses = $db->fetchAll("
    SELECT id, type, status, end_date
    FROM licenses
    WHERE status IN ('active', 'trial')
    ORDER BY type ASC
");

// Obtener todos los owners disponibles
$owners = $db->fetchAll("
    SELECT id, full_name, email
    FROM users
    WHERE role = 'owner'
    ORDER BY full_name ASC
");

// Procesar formulario
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
        $themeColor = trim($_POST['theme_color'] ?? '#1e40af');
        $allowOnlineBooking = isset($_POST['allow_online_booking']) ? 1 : 0;
        $advanceBookingDays = intval($_POST['advance_booking_days'] ?? 30);
        $cancellationHours = intval($_POST['cancellation_hours'] ?? 24);
        $status = $_POST['status'] ?? 'active';
        $licenseId = $_POST['license_id'] ?? null;
        $ownerId = $_POST['owner_id'] ?? null;
        
        // Validaciones
        if (empty($businessName)) {
            throw new Exception('El nombre del negocio es obligatorio');
        }
        
        if (empty($slug)) {
            throw new Exception('El slug es obligatorio');
        }
        
        // Verificar slug único (excepto la propia barbería)
        $existingShop = $db->fetch("SELECT id FROM barbershops WHERE slug = ? AND id != ?", [$slug, $shopId]);
        if ($existingShop) {
            throw new Exception('El slug ya está en uso');
        }
        
        if (empty($licenseId)) {
            throw new Exception('Debe seleccionar una licencia');
        }
        
        if (empty($ownerId)) {
            throw new Exception('Debe seleccionar un owner');
        }
        
        // Procesar uploads de imágenes
        $logo = $shop['logo'];
        $coverImage = $shop['cover_image'];
        
        // Upload de logo
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadResult = uploadImage($_FILES['logo'], 'barbershops', [
                'maxSize' => 2 * 1024 * 1024, // 2MB
                'maxWidth' => 500,
                'maxHeight' => 500,
                'oldFile' => $shop['logo']
            ]);
            
            if ($uploadResult['success']) {
                $logo = $uploadResult['path'];
            } else {
                throw new Exception('Error en logo: ' . $uploadResult['message']);
            }
        }
        
        // Upload de cover image
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadResult = uploadImage($_FILES['cover_image'], 'barbershops', [
                'maxSize' => 5 * 1024 * 1024, // 5MB
                'maxWidth' => 1920,
                'maxHeight' => 1080,
                'oldFile' => $shop['cover_image']
            ]);
            
            if ($uploadResult['success']) {
                $coverImage = $uploadResult['path'];
            } else {
                throw new Exception('Error en imagen de portada: ' . $uploadResult['message']);
            }
        }
        
        // Preparar datos de actualización
        $query = "
            UPDATE barbershops SET 
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
                cover_image = ?,
                status = ?,
                license_id = ?,
                owner_id = ?
            WHERE id = ?
        ";
        
        $db->query($query, [
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
            $status,
            $licenseId,
            $ownerId,
            $shopId
        ]);
        
        $_SESSION['success'] = 'Barbería actualizada exitosamente';
        header('Location: barbershops.php');
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

$title = 'Editar Barbería - Super Admin';
include BASE_PATH . '/includes/header.php';
?>

<div class="min-h-screen bg-gray-100" x-data="{ sidebarOpen: false }">
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
                    <h1 class="text-2xl font-bold text-gray-900">Editar Barbería</h1>
                    <p class="text-sm text-gray-500">Control total sobre la información de la barbería</p>
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

            <form method="POST" enctype="multipart/form-data" class="max-w-5xl space-y-6">
                <!-- Información Básica -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        Información Básica del Negocio
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nombre del Negocio *</label>
                            <input type="text" name="business_name" value="<?php echo htmlspecialchars($shop['business_name']); ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Slug (URL) *</label>
                            <input type="text" name="slug" value="<?php echo htmlspecialchars($shop['slug']); ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" required>
                            <p class="text-xs text-gray-500 mt-1">ejemplo: mi-barberia-rd</p>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
                            <textarea name="description" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"><?php echo htmlspecialchars($shop['description'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Información de Contacto -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        Información de Contacto
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Teléfono</label>
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($shop['phone'] ?? ''); ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">WhatsApp</label>
                            <input type="tel" name="whatsapp" value="<?php echo htmlspecialchars($shop['whatsapp'] ?? ''); ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                   placeholder="18095551234">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($shop['email'] ?? ''); ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Sitio Web</label>
                            <input type="url" name="website" value="<?php echo htmlspecialchars($shop['website'] ?? ''); ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>
                    </div>
                </div>

                <!-- Ubicación -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Ubicación
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Dirección</label>
                            <textarea name="address" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"><?php echo htmlspecialchars($shop['address'] ?? ''); ?></textarea>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Ciudad</label>
                            <input type="text" name="city" value="<?php echo htmlspecialchars($shop['city'] ?? ''); ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Provincia</label>
                            <input type="text" name="province" value="<?php echo htmlspecialchars($shop['province'] ?? ''); ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">RNC</label>
                            <input type="text" name="rnc" value="<?php echo htmlspecialchars($shop['rnc'] ?? ''); ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>
                    </div>
                </div>

                <!-- Redes Sociales -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                        </svg>
                        Redes Sociales
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Facebook</label>
                            <input type="text" name="facebook" value="<?php echo htmlspecialchars($shop['facebook'] ?? ''); ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                   placeholder="@mibarberia">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Instagram</label>
                            <input type="text" name="instagram" value="<?php echo htmlspecialchars($shop['instagram'] ?? ''); ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                   placeholder="@mibarberia">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">TikTok</label>
                            <input type="text" name="tiktok" value="<?php echo htmlspecialchars($shop['tiktok'] ?? ''); ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                   placeholder="@mibarberia">
                        </div>
                    </div>
                </div>

                <!-- Configuración y Licencia -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Configuración y Licencia
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Owner *</label>
                            <select name="owner_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" required>
                                <?php foreach ($owners as $owner): ?>
                                <option value="<?php echo $owner['id']; ?>" <?php echo $shop['owner_id'] == $owner['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($owner['full_name']); ?> (<?php echo htmlspecialchars($owner['email']); ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Licencia *</label>
                            <select name="license_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" required>
                                <?php foreach ($licenses as $license): ?>
                                <option value="<?php echo $license['id']; ?>" <?php echo $shop['license_id'] == $license['id'] ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($license['type']); ?> - Vence: <?php echo date('d/m/Y', strtotime($license['end_date'])); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Estado *</label>
                            <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" required>
                                <option value="active" <?php echo $shop['status'] === 'active' ? 'selected' : ''; ?>>Activo</option>
                                <option value="inactive" <?php echo $shop['status'] === 'inactive' ? 'selected' : ''; ?>>Inactivo</option>
                                <option value="suspended" <?php echo $shop['status'] === 'suspended' ? 'selected' : ''; ?>>Suspendido</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Color del Tema</label>
                            <input type="color" name="theme_color" value="<?php echo htmlspecialchars($shop['theme_color']); ?>" 
                                   class="w-full h-10 px-2 py-1 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Días de Reserva Anticipada</label>
                            <input type="number" name="advance_booking_days" value="<?php echo $shop['advance_booking_days']; ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" min="1" max="365">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Horas para Cancelación</label>
                            <input type="number" name="cancellation_hours" value="<?php echo $shop['cancellation_hours']; ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" min="1" max="168">
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="flex items-center">
                                <input type="checkbox" name="allow_online_booking" value="1" <?php echo $shop['allow_online_booking'] ? 'checked' : ''; ?> 
                                       class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700">Permitir Reservas Online</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Imágenes -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        Imágenes de la Barbería
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Logo -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-3">Logo</label>
                            
                            <?php if ($shop['logo']): ?>
                            <div class="mb-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
                                <p class="text-xs text-gray-500 mb-2">Logo actual:</p>
                                <img src="<?php echo imageUrl($shop['logo']); ?>" 
                                     alt="Logo actual" 
                                     class="w-32 h-32 object-contain rounded-lg border border-gray-300 bg-white">
                            </div>
                            <?php endif; ?>
                            
                            <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 hover:border-indigo-500 transition">
                                <input type="file" 
                                       name="logo" 
                                       accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                                       class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                                <p class="mt-2 text-xs text-gray-500">
                                    JPG, PNG, GIF o WebP. Máx. 2MB. Recomendado: 500x500px
                                </p>
                            </div>
                        </div>
                        
                        <!-- Cover Image -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-3">Imagen de Portada</label>
                            
                            <?php if ($shop['cover_image']): ?>
                            <div class="mb-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
                                <p class="text-xs text-gray-500 mb-2">Portada actual:</p>
                                <img src="<?php echo imageUrl($shop['cover_image']); ?>" 
                                     alt="Portada actual" 
                                     class="w-full h-32 object-cover rounded-lg border border-gray-300">
                            </div>
                            <?php endif; ?>
                            
                            <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 hover:border-indigo-500 transition">
                                <input type="file" 
                                       name="cover_image" 
                                       accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                                       class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                                <p class="mt-2 text-xs text-gray-500">
                                    JPG, PNG, GIF o WebP. Máx. 5MB. Recomendado: 1920x1080px
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información del Sistema -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Información del Sistema
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Fecha de Creación</p>
                            <p class="text-gray-900 mt-1"><?php echo date('d/m/Y H:i', strtotime($shop['created_at'])); ?></p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Última Actualización</p>
                            <p class="text-gray-900 mt-1"><?php echo date('d/m/Y H:i', strtotime($shop['updated_at'])); ?></p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">ID de Barbería</p>
                            <p class="text-gray-900 mt-1 font-mono">#<?php echo $shop['id']; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="flex justify-between items-center">
                    <a href="barbershops.php" class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                        Cancelar
                    </a>
                    
                    <div class="space-x-3">
                        <a href="../public/booking.php?shop=<?php echo $shop['slug']; ?>" target="_blank" 
                           class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition inline-flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            Ver Página
                        </a>
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
