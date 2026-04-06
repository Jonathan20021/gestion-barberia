<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Auth.php';
require_once BASE_PATH . '/core/Helpers.php';

Auth::requireRole('superadmin');

$db = Database::getInstance();

// Procesar acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $type         = $_POST['type'] ?? 'basic';
        $billingCycle = $_POST['billing_cycle'] ?? 'monthly';
        $startDate    = $_POST['start_date'] ?? date('Y-m-d');
        $price        = !empty($_POST['price']) ? floatval($_POST['price']) : LICENSE_TYPES[$type]['price'];
        $barbershopId = !empty($_POST['barbershop_id']) ? intval($_POST['barbershop_id']) : null;

        $months   = ['monthly' => 1, 'quarterly' => 3, 'yearly' => 12];
        $endDate  = date('Y-m-d', strtotime($startDate . ' +' . ($months[$billingCycle] ?? 1) . ' months'));
        $licenseKey = bin2hex(random_bytes(16));

        $db->query(
            "INSERT INTO licenses (license_key, type, status, price, billing_cycle, start_date, end_date) VALUES (?, ?, 'active', ?, ?, ?, ?)",
            [$licenseKey, $type, $price, $billingCycle, $startDate, $endDate]
        );
        $newId = $db->lastInsertId();

        if ($barbershopId) {
            $db->query("UPDATE barbershops SET license_id = ? WHERE id = ?", [$newId, $barbershopId]);
        }

        $_SESSION['success'] = 'Licencia creada exitosamente';
        header('Location: licenses.php');
        exit;
    }

    if ($action === 'edit') {
        $licenseId    = intval($_POST['license_id']);
        $type         = $_POST['type'] ?? 'basic';
        $billingCycle = $_POST['billing_cycle'] ?? 'monthly';
        $startDate    = $_POST['start_date'] ?? date('Y-m-d');
        $endDate      = $_POST['end_date'] ?? date('Y-m-d');
        $price        = floatval($_POST['price']);
        $status       = $_POST['status'] ?? 'active';
        $barbershopId = !empty($_POST['barbershop_id']) ? intval($_POST['barbershop_id']) : null;

        $db->query(
            "UPDATE licenses SET type = ?, billing_cycle = ?, start_date = ?, end_date = ?, price = ?, status = ? WHERE id = ?",
            [$type, $billingCycle, $startDate, $endDate, $price, $status, $licenseId]
        );

        $db->query("UPDATE barbershops SET license_id = NULL WHERE license_id = ?", [$licenseId]);
        if ($barbershopId) {
            $db->query("UPDATE barbershops SET license_id = ? WHERE id = ?", [$licenseId, $barbershopId]);
        }

        $_SESSION['success'] = 'Licencia actualizada exitosamente';
        header('Location: licenses.php');
        exit;
    }

    if ($action === 'renovar') {
        $licenseId    = intval($_POST['license_id']);
        $billingCycle = $_POST['billing_cycle'] ?? 'monthly';
        $months       = ['monthly' => 1, 'quarterly' => 3, 'yearly' => 12];
        $extra        = ($months[$billingCycle] ?? 1);

        $lic = $db->fetch("SELECT end_date FROM licenses WHERE id = ?", [$licenseId]);
        $baseDate = (strtotime($lic['end_date']) > time()) ? $lic['end_date'] : date('Y-m-d');
        $newEnd = date('Y-m-d', strtotime($baseDate . ' +' . $extra . ' months'));

        $db->query("UPDATE licenses SET end_date = ?, status = 'active' WHERE id = ?", [$newEnd, $licenseId]);
        $_SESSION['success'] = 'Licencia renovada hasta ' . date('d/m/Y', strtotime($newEnd));
        header('Location: licenses.php');
        exit;
    }

    if ($action === 'toggle_status') {
        $licenseId = intval($_POST['license_id']);
        $newStatus = $_POST['new_status'] ?? 'suspended';
        $db->query("UPDATE licenses SET status = ? WHERE id = ?", [$newStatus, $licenseId]);
        $_SESSION['success'] = 'Estado de licencia actualizado';
        header('Location: licenses.php');
        exit;
    }
}

// Obtener licencias con toda la info
$licenses = $db->fetchAll("
    SELECT l.*,
           b.id       AS barbershop_id,
           b.business_name,
           b.phone    AS barbershop_phone,
           b.email    AS barbershop_email,
           b.address  AS barbershop_address,
           u.full_name AS owner_name,
           u.email    AS owner_email,
           u.phone    AS owner_phone,
           DATEDIFF(l.end_date, CURDATE()) AS days_remaining,
           (SELECT COUNT(*) FROM appointments a
            INNER JOIN barbers br ON a.barber_id = br.id
            INNER JOIN barbershops bs ON br.barbershop_id = bs.id
            WHERE bs.license_id = l.id) AS total_appointments
    FROM licenses l
    LEFT JOIN barbershops b ON b.license_id = l.id
    LEFT JOIN users u ON b.owner_id = u.id
    ORDER BY l.created_at DESC
");

// Barberías sin licencia (para asignar al crear o editar)
$freeBarbershops = $db->fetchAll("
    SELECT bs.id, bs.business_name, u.full_name AS owner_name
    FROM barbershops bs
    LEFT JOIN users u ON bs.owner_id = u.id
    WHERE bs.license_id IS NULL
    ORDER BY bs.business_name
");

// Estadísticas
$statsRow = $db->fetch("
    SELECT
        COUNT(*) AS total,
        SUM(status = 'active') AS activas,
        SUM(status = 'suspended') AS suspendidas,
        SUM(status = 'expired') AS vencidas,
        SUM(price) AS ingresos_totales
    FROM licenses
");

$title = 'Gestión de Licencias - Super Admin';
include BASE_PATH . '/includes/header.php';
?>

<div class="min-h-screen bg-gray-100" x-data="{
    sidebarOpen: false,
    showCreateModal: false,
    showEditModal: false,
    showViewModal: false,
    showRenovarModal: false,
    activeLicense: null,
    openView(lic)   { this.activeLicense = lic; this.showViewModal = true; },
    openEdit(lic)   { this.activeLicense = lic; this.showEditModal = true; },
    openRenovar(lic){ this.activeLicense = lic; this.showRenovarModal = true; }
}">

    <?php include BASE_PATH . '/includes/sidebar-admin.php'; ?>

    <div x-show="sidebarOpen" @click="sidebarOpen = false"
         class="fixed inset-0 z-40 bg-gray-900 bg-opacity-50 lg:hidden" style="display:none"></div>

    <div class="lg:pl-64">
        <!-- Topbar -->
        <div class="sticky top-0 z-40 flex h-16 bg-white border-b border-gray-200 shadow-sm">
            <button @click="sidebarOpen = true" class="px-4 text-gray-500 lg:hidden">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <div class="flex items-center justify-between flex-1 px-4 sm:px-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Gestión de Licencias</h1>
                    <p class="text-sm text-gray-500">Control total de licencias del sistema</p>
                </div>
                <button @click="showCreateModal = true"
                        class="flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 shadow transition">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Nueva Licencia
                </button>
            </div>
        </div>

        <main class="p-6">
            <?php if (isset($_SESSION['success'])): ?>
            <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-lg">
                <p class="text-green-700"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></p>
            </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
                <p class="text-red-700"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></p>
            </div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow p-5">
                    <p class="text-xs text-gray-500 uppercase font-medium">Total</p>
                    <p class="text-3xl font-bold text-gray-800"><?php echo $statsRow['total']; ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-5">
                    <p class="text-xs text-gray-500 uppercase font-medium">Activas</p>
                    <p class="text-3xl font-bold text-green-600"><?php echo $statsRow['activas']; ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-5">
                    <p class="text-xs text-gray-500 uppercase font-medium">Suspendidas</p>
                    <p class="text-3xl font-bold text-orange-500"><?php echo $statsRow['suspendidas']; ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-5">
                    <p class="text-xs text-gray-500 uppercase font-medium">Vencidas</p>
                    <p class="text-3xl font-bold text-red-500"><?php echo $statsRow['vencidas']; ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-5 col-span-2 md:col-span-1">
                    <p class="text-xs text-gray-500 uppercase font-medium">Ingresos</p>
                    <p class="text-2xl font-bold text-indigo-600"><?php echo formatPrice($statsRow['ingresos_totales'] ?? 0); ?></p>
                </div>
            </div>

            <!-- Tabla -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Licencia</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Barber&iacute;a</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Plan</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vencimiento</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Precio</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($licenses)): ?>
                            <tr><td colspan="7" class="px-6 py-12 text-center text-gray-500">No hay licencias registradas.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($licenses as $lic): ?>
                            <?php
                                $licJson = htmlspecialchars(json_encode($lic), ENT_QUOTES, 'UTF-8');
                                $daysLeft = intval($lic['days_remaining']);
                                $statusColor = match($lic['status']) {
                                    'active'    => 'bg-green-100 text-green-800',
                                    'suspended' => 'bg-orange-100 text-orange-800',
                                    'expired'   => 'bg-red-100 text-red-800',
                                    default     => 'bg-gray-100 text-gray-800'
                                };
                                $planColor = match($lic['type']) {
                                    'enterprise'   => 'bg-purple-100 text-purple-800',
                                    'professional' => 'bg-blue-100 text-blue-800',
                                    default        => 'bg-gray-100 text-gray-800'
                                };
                            ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <p class="text-sm font-mono font-medium text-gray-900"><?php echo substr($lic['license_key'], 0, 16) . '...'; ?></p>
                                    <p class="text-xs text-gray-500">ID: <?php echo $lic['id']; ?></p>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($lic['business_name']): ?>
                                        <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($lic['business_name']); ?></p>
                                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars((string)($lic['owner_name'] ?? '')); ?></p>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-400 italic">Sin asignar</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $planColor; ?>">
                                        <?php echo ucfirst($lic['type']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $statusColor; ?>">
                                        <?php echo ucfirst($lic['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm text-gray-900"><?php echo date('d/m/Y', strtotime($lic['end_date'])); ?></p>
                                    <p class="text-xs <?php echo $daysLeft < 7 ? 'text-red-600 font-semibold' : 'text-gray-500'; ?>">
                                        <?php echo $daysLeft >= 0 ? $daysLeft . ' d&iacute;as' : 'Vencida'; ?>
                                    </p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm font-semibold text-gray-900"><?php echo formatPrice($lic['price']); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo ucfirst($lic['billing_cycle']); ?></p>
                                </td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <button @click="openView(<?php echo $licJson; ?>)"
                                            class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">Ver</button>
                                    <button @click="openEdit(<?php echo $licJson; ?>)"
                                            class="text-amber-600 hover:text-amber-900 text-sm font-medium">Editar</button>
                                    <button @click="openRenovar(<?php echo $licJson; ?>)"
                                            class="text-green-600 hover:text-green-900 text-sm font-medium">Renovar</button>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="license_id" value="<?php echo $lic['id']; ?>">
                                        <input type="hidden" name="new_status" value="<?php echo $lic['status'] === 'active' ? 'suspended' : 'active'; ?>">
                                        <button type="submit"
                                                onclick="return confirm('<?php echo $lic['status'] === 'active' ? '¿Suspender esta licencia?' : '¿Activar esta licencia?'; ?>')"
                                                class="<?php echo $lic['status'] === 'active' ? 'text-red-600 hover:text-red-900' : 'text-green-600 hover:text-green-900'; ?> text-sm font-medium">
                                            <?php echo $lic['status'] === 'active' ? 'Suspender' : 'Activar'; ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- MODAL VER -->
    <div x-show="showViewModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display:none">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-60" @click="showViewModal = false"></div>
            <div class="relative bg-white rounded-xl shadow-2xl max-w-2xl w-full">
                <div class="flex items-center justify-between px-6 py-4 border-b bg-indigo-600 rounded-t-xl">
                    <h3 class="text-lg font-bold text-white">Detalle de Licencia</h3>
                    <button @click="showViewModal = false" class="text-white hover:text-indigo-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="p-6 space-y-5">
                    <div>
                        <h4 class="text-xs font-semibold text-gray-400 uppercase mb-2">Información de Licencia</h4>
                        <div class="grid grid-cols-2 gap-4 bg-gray-50 rounded-lg p-4">
                            <div class="col-span-2">
                                <p class="text-xs text-gray-500">Clave completa</p>
                                <p class="text-sm font-mono font-medium text-gray-900 break-all" x-text="activeLicense?.license_key"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">ID</p>
                                <p class="text-sm font-bold text-gray-900" x-text="activeLicense?.id"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Plan</p>
                                <p class="text-sm font-bold capitalize text-indigo-700" x-text="activeLicense?.type"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Estado</p>
                                <p class="text-sm font-bold capitalize" x-text="activeLicense?.status"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Precio</p>
                                <p class="text-sm font-bold" x-text="'RD$' + parseFloat(activeLicense?.price||0).toLocaleString('es-DO',{minimumFractionDigits:2})"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Ciclo</p>
                                <p class="text-sm font-bold capitalize" x-text="activeLicense?.billing_cycle"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Inicio</p>
                                <p class="text-sm" x-text="activeLicense?.start_date"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Vencimiento</p>
                                <p class="text-sm" x-text="activeLicense?.end_date"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Días restantes</p>
                                <p class="text-sm font-bold" :class="activeLicense?.days_remaining < 7 ? 'text-red-600' : 'text-gray-900'"
                                   x-text="activeLicense?.days_remaining >= 0 ? activeLicense?.days_remaining + ' días' : 'Vencida'"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Citas registradas</p>
                                <p class="text-sm font-bold" x-text="activeLicense?.total_appointments ?? 0"></p>
                            </div>
                        </div>
                    </div>
                    <div x-show="activeLicense?.business_name">
                        <h4 class="text-xs font-semibold text-gray-400 uppercase mb-2">Barbería Asignada</h4>
                        <div class="grid grid-cols-2 gap-4 bg-indigo-50 rounded-lg p-4">
                            <div>
                                <p class="text-xs text-gray-500">Nombre</p>
                                <p class="text-sm font-bold" x-text="activeLicense?.business_name"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Propietario</p>
                                <p class="text-sm" x-text="activeLicense?.owner_name"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Email</p>
                                <p class="text-sm" x-text="activeLicense?.owner_email || activeLicense?.barbershop_email || '—'"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Teléfono</p>
                                <p class="text-sm" x-text="activeLicense?.owner_phone || activeLicense?.barbershop_phone || '—'"></p>
                            </div>
                            <div class="col-span-2">
                                <p class="text-xs text-gray-500">Dirección</p>
                                <p class="text-sm" x-text="activeLicense?.barbershop_address || '—'"></p>
                            </div>
                        </div>
                    </div>
                    <div x-show="!activeLicense?.business_name" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-sm text-yellow-700">
                        Sin barbería asignada
                    </div>
                </div>
                <div class="px-6 py-4 border-t flex justify-end gap-3">
                    <button @click="showViewModal = false; openEdit(activeLicense)"
                            class="px-4 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600 text-sm font-medium">Editar</button>
                    <button @click="showViewModal = false"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL EDITAR -->
    <div x-show="showEditModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display:none">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-60" @click="showEditModal = false"></div>
            <div class="relative bg-white rounded-xl shadow-2xl max-w-2xl w-full">
                <div class="flex items-center justify-between px-6 py-4 border-b">
                    <h3 class="text-lg font-bold text-gray-900">Editar Licencia</h3>
                    <button @click="showEditModal = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <form method="POST" class="p-6">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="license_id" :value="activeLicense?.id">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Plan *</label>
                            <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500">
                                <?php foreach (LICENSE_TYPES as $key => $cfg): ?>
                                <option value="<?php echo $key; ?>" :selected="activeLicense?.type === '<?php echo $key; ?>'">
                                    <?php echo $cfg['name']; ?> — <?php echo formatPrice($cfg['price']); ?>/mes
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Estado *</label>
                            <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500">
                                <option value="active"    :selected="activeLicense?.status === 'active'">Activa</option>
                                <option value="suspended" :selected="activeLicense?.status === 'suspended'">Suspendida</option>
                                <option value="expired"   :selected="activeLicense?.status === 'expired'">Vencida</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ciclo de Facturación *</label>
                            <select name="billing_cycle" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500">
                                <option value="monthly"   :selected="activeLicense?.billing_cycle === 'monthly'">Mensual</option>
                                <option value="quarterly" :selected="activeLicense?.billing_cycle === 'quarterly'">Trimestral</option>
                                <option value="yearly"    :selected="activeLicense?.billing_cycle === 'yearly'">Anual</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Precio (RD$) *</label>
                            <input type="number" name="price" step="0.01" min="0" :value="activeLicense?.price"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Inicio *</label>
                            <input type="date" name="start_date" :value="activeLicense?.start_date"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Vencimiento *</label>
                            <input type="date" name="end_date" :value="activeLicense?.end_date"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500" required>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Barbería Asignada</label>
                            <select name="barbershop_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500">
                                <option value="">— Sin asignar —</option>
                                <template x-if="activeLicense?.barbershop_id">
                                    <option :value="activeLicense?.barbershop_id" selected
                                            x-text="(activeLicense?.business_name || '') + ' (actual)'"></option>
                                </template>
                                <?php foreach ($freeBarbershops as $bs): ?>
                                <option value="<?php echo $bs['id']; ?>">
                                    <?php echo htmlspecialchars($bs['business_name']); ?> — <?php echo htmlspecialchars((string)($bs['owner_name'] ?? '')); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <button type="button" @click="showEditModal = false"
                                class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">Cancelar</button>
                        <button type="submit"
                                class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL RENOVAR -->
    <div x-show="showRenovarModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display:none">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-60" @click="showRenovarModal = false"></div>
            <div class="relative bg-white rounded-xl shadow-2xl max-w-md w-full">
                <div class="px-6 py-4 border-b">
                    <h3 class="text-lg font-bold text-gray-900">Renovar Licencia</h3>
                </div>
                <form method="POST" class="p-6">
                    <input type="hidden" name="action" value="renovar">
                    <input type="hidden" name="license_id" :value="activeLicense?.id">
                    <p class="text-sm text-gray-600 mb-4">
                        Renovando <strong x-text="activeLicense?.business_name || 'Licencia #' + activeLicense?.id"></strong>.
                        <br>Vencimiento actual: <strong x-text="activeLicense?.end_date"></strong>
                    </p>
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Período a agregar</label>
                        <select name="billing_cycle" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500">
                            <option value="monthly">1 Mes</option>
                            <option value="quarterly">3 Meses (Trimestral)</option>
                            <option value="yearly">12 Meses (Anual)</option>
                        </select>
                    </div>
                    <div class="flex gap-3">
                        <button type="button" @click="showRenovarModal = false"
                                class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">Cancelar</button>
                        <button type="submit"
                                class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">Confirmar Renovación</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL CREAR -->
    <div x-show="showCreateModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display:none">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-60" @click="showCreateModal = false"></div>
            <div class="relative bg-white rounded-xl shadow-2xl max-w-lg w-full">
                <div class="flex items-center justify-between px-6 py-4 border-b">
                    <h3 class="text-lg font-bold text-gray-900">Nueva Licencia</h3>
                    <button @click="showCreateModal = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <form method="POST" class="p-6">
                    <input type="hidden" name="action" value="create">
                    <div class="space-y-4 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Plan *</label>
                            <select name="type" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500">
                                <?php foreach (LICENSE_TYPES as $key => $cfg): ?>
                                <option value="<?php echo $key; ?>"><?php echo $cfg['name']; ?> — <?php echo formatPrice($cfg['price']); ?>/mes</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ciclo de Facturación *</label>
                            <select name="billing_cycle" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500">
                                <option value="monthly">Mensual</option>
                                <option value="quarterly">Trimestral (+3 meses)</option>
                                <option value="yearly">Anual (+12 meses)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Precio personalizado (RD$)</label>
                            <input type="number" name="price" step="0.01" min="0"
                                   placeholder="Dejar vacío para usar precio del plan"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Inicio *</label>
                            <input type="date" name="start_date" value="<?php echo date('Y-m-d'); ?>" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Asignar a Barbería</label>
                            <select name="barbershop_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500">
                                <option value="">— Sin asignar ahora —</option>
                                <?php foreach ($freeBarbershops as $bs): ?>
                                <option value="<?php echo $bs['id']; ?>">
                                    <?php echo htmlspecialchars($bs['business_name']); ?> — <?php echo htmlspecialchars((string)($bs['owner_name'] ?? '')); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <button type="button" @click="showCreateModal = false"
                                class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">Cancelar</button>
                        <button type="submit"
                                class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium">Crear Licencia</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>[x-cloak]{display:none!important}</style>

<?php include BASE_PATH . '/includes/footer.php'; ?>