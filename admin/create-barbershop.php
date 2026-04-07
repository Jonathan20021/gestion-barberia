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
    SELECT id, type, status, end_date
    FROM licenses
    WHERE status IN ('active', 'trial')
    ORDER BY type ASC
");

$supportsTrialDates = true;
try {
    $db->query("SELECT trial_end_date, trial_start_date, trial_days FROM licenses LIMIT 1");
} catch (Exception $e) {
    $supportsTrialDates = false;
}

$supportsMaxLocationsOverride = true;
try {
    $db->query("SELECT max_locations_override FROM licenses LIMIT 1");
} catch (Exception $e) {
    $supportsMaxLocationsOverride = false;
}

if ($supportsMaxLocationsOverride) {
    $licenses = $db->fetchAll("
        SELECT id, type, status, end_date, max_locations_override
        FROM licenses
        WHERE status IN ('active', 'trial')
        ORDER BY type ASC
    ");
}

$licenseUsageRows = $db->fetchAll("
    SELECT license_id, COUNT(*) AS total
    FROM barbershops
    WHERE license_id IS NOT NULL
    GROUP BY license_id
");

$licenseUsageMap = [];
foreach ($licenseUsageRows as $row) {
    $licenseUsageMap[intval($row['license_id'])] = intval($row['total']);
}

foreach ($licenses as $idx => $license) {
    $licenseId = intval($license['id']);
    $licenseType = $license['type'];
    $defaultMaxLocations = intval(LICENSE_TYPES[$licenseType]['max_locations'] ?? 1);
    $maxLocations = $defaultMaxLocations;
    if ($supportsMaxLocationsOverride && array_key_exists('max_locations_override', $license) && $license['max_locations_override'] !== null) {
        $maxLocations = intval($license['max_locations_override']);
    }
    $usedLocations = intval($licenseUsageMap[$licenseId] ?? 0);

    $licenses[$idx]['max_locations'] = $maxLocations;
    $licenses[$idx]['used_locations'] = $usedLocations;
    $licenses[$idx]['has_capacity'] = $maxLocations < 0 || $usedLocations < $maxLocations;
    $licenses[$idx]['remaining_locations'] = $maxLocations < 0 ? -1 : max(0, $maxLocations - $usedLocations);
}

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
        $planType = $_POST['plan_type'] ?? '';
        $ownerId = $_POST['owner_id'] ?? null;
        
        // Validaciones
        if (empty($businessName)) {
            throw new Exception('El nombre del negocio es obligatorio');
        }
        
        if (empty($slug)) {
            // Generar slug automáticamente si no se proporciona
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $businessName)));
        }
        
        // Verificar slug único
        $existingShop = $db->fetch("SELECT id FROM barbershops WHERE slug = ?", [$slug]);
        if ($existingShop) {
            throw new Exception('El slug ya está en uso. Usa otro nombre único.');
        }
        
        $validPlanTypes = array_keys(LICENSE_TYPES);
        if (!empty($planType) && !in_array($planType, $validPlanTypes, true)) {
            throw new Exception('Debe seleccionar un plan válido');
        }

        // Si no se selecciona una licencia existente, crear una nueva usando un plan del sistema.
        if (empty($licenseId)) {
            if (empty($planType)) {
                throw new Exception('Debe seleccionar una licencia existente o un plan para crear una nueva');
            }

            $billingCycle = 'monthly';
            $startDate = date('Y-m-d');
            $months = ['monthly' => 1, 'quarterly' => 3, 'yearly' => 12];
            $selectedMonths = isset($months[$billingCycle]) ? $months[$billingCycle] : 1;
            $endDate = date('Y-m-d', strtotime($startDate . ' +' . $selectedMonths . ' months'));
            $trialDays = defined('TRIAL_DAYS_DEFAULT') ? intval(TRIAL_DAYS_DEFAULT) : 15;
            if ($trialDays <= 0) {
                $trialDays = 15;
            }
            $trialEndDate = date('Y-m-d', strtotime($startDate . ' +' . $trialDays . ' days'));

            $newLicenseKey = bin2hex(random_bytes(16));
            $newLicensePrice = LICENSE_TYPES[$planType]['price'];

            if ($supportsTrialDates) {
                $db->query(
                    "INSERT INTO licenses (license_key, type, status, price, billing_cycle, start_date, end_date, trial_days, trial_start_date, trial_end_date)
                     VALUES (?, ?, 'trial', ?, ?, ?, ?, ?, ?, ?)",
                    [$newLicenseKey, $planType, $newLicensePrice, $billingCycle, $startDate, $endDate, $trialDays, $startDate, $trialEndDate]
                );
            } else {
                $db->query(
                    "INSERT INTO licenses (license_key, type, status, price, billing_cycle, start_date, end_date)
                     VALUES (?, ?, 'active', ?, ?, ?, ?)",
                    [$newLicenseKey, $planType, $newLicensePrice, $billingCycle, $startDate, $endDate]
                );
            }

            $licenseId = $db->lastInsertId();
        }
        
        if (empty($ownerId)) {
            throw new Exception('Debe seleccionar un owner');
        }

        if (!canAddBarbershopToLicense($licenseId, $limitMessage)) {
            throw new Exception($limitMessage);
        }
        
        // Crear barbería
        $query = "
            INSERT INTO barbershops (
                license_id, owner_id, business_name, slug, description,
                phone, email, address, city, province, rnc,
                website, facebook, instagram, tiktok, whatsapp,
                theme_color, allow_online_booking, advance_booking_days,
                cancellation_hours, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ";
        
        $db->query($query, [
            $licenseId,
            $ownerId,
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
            $status
        ]);
        
        $shopId = $db->lastInsertId();
        
        $_SESSION['success'] = 'Barbería creada exitosamente';
        header('Location: barbershops.php');
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

$title = 'Crear Barbería - Super Admin';
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
                    <h1 class="text-2xl font-bold text-gray-900">Crear Nueva Barbería</h1>
                    <p class="text-sm text-gray-500">Agrega una nueva barbería al sistema</p>
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

            <form method="POST" class="max-w-5xl space-y-6">
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
                            <input type="text" name="business_name" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Slug (URL)</label>
                            <input type="text" name="slug" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                   placeholder="Se generará automáticamente si se deja vacío">
                            <p class="text-xs text-gray-500 mt-1">ejemplo: mi-barberia-rd</p>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
                            <textarea name="description" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"></textarea>
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
                            <input type="tel" name="phone" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">WhatsApp</label>
                            <input type="tel" name="whatsapp" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                   placeholder="18095551234">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                            <input type="email" name="email" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Sitio Web</label>
                            <input type="url" name="website" 
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
                            <textarea name="address" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"></textarea>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Ciudad</label>
                            <input type="text" name="city" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Provincia</label>
                            <input type="text" name="province" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">RNC</label>
                            <input type="text" name="rnc" 
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
                            <input type="text" name="facebook" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                   placeholder="@mibarberia">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Instagram</label>
                            <input type="text" name="instagram" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                   placeholder="@mibarberia">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">TikTok</label>
                            <input type="text" name="tiktok" 
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
                                <option value="">Seleccionar owner...</option>
                                <?php foreach ($owners as $owner): ?>
                                <option value="<?php echo $owner['id']; ?>">
                                    <?php echo htmlspecialchars($owner['full_name']); ?> (<?php echo htmlspecialchars($owner['email']); ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Licencia *</label>
                            <select name="license_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" required>
                                <option value="">Crear nueva licencia desde plan...</option>
                                <?php foreach ($licenses as $license): ?>
                                <option value="<?php echo $license['id']; ?>" <?php echo $license['has_capacity'] ? '' : 'disabled'; ?>>
                                    <?php echo ucfirst($license['type']); ?> - Vence: <?php echo date('d/m/Y', strtotime($license['end_date'])); ?>
                                    <?php if ($license['max_locations'] < 0): ?>
                                        (Sucursales ilimitadas)
                                    <?php else: ?>
                                        (<?php echo $license['remaining_locations']; ?> de <?php echo $license['max_locations']; ?> disponible/s)
                                    <?php endif; ?>
                                    <?php echo $license['has_capacity'] ? '' : ' - SIN CUPO'; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Si eliges una licencia sin cupo, no podrá asignarse a la barbería.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Plan del sistema (si crearás nueva licencia)</label>
                            <select name="plan_type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                <option value="">Seleccionar plan...</option>
                                <?php foreach (LICENSE_TYPES as $planKey => $planCfg): ?>
                                <option value="<?php echo $planKey; ?>">
                                    <?php echo $planCfg['name']; ?> - <?php echo formatPrice($planCfg['price']); ?>/mes
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Si no seleccionas una licencia arriba, se creará una nueva con este plan y se asignará automáticamente.</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Estado *</label>
                            <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" required>
                                <option value="active">Activo</option>
                                <option value="inactive">Inactivo</option>
                                <option value="suspended">Suspendido</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Color del Tema</label>
                            <input type="color" name="theme_color" value="#1e40af" 
                                   class="w-full h-10 px-2 py-1 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Días de Reserva Anticipada</label>
                            <input type="number" name="advance_booking_days" value="30" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" min="1" max="365">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Horas para Cancelación</label>
                            <input type="number" name="cancellation_hours" value="24" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" min="1" max="168">
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="flex items-center">
                                <input type="checkbox" name="allow_online_booking" value="1" checked 
                                       class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700">Permitir Reservas Online</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="flex justify-between items-center">
                    <a href="barbershops.php" class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                        Cancelar
                    </a>
                    
                    <button type="submit" class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition shadow-lg">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Crear Barbería
                    </button>
                </div>
            </form>
        </main>
    </div>
</div>

<?php include BASE_PATH . '/includes/footer.php'; ?>
