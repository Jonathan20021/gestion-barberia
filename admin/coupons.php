<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Auth.php';
require_once BASE_PATH . '/core/Helpers.php';

Auth::requireRole('superadmin');

$db = Database::getInstance();

// Procesar eliminación
if (isset($_GET['delete'])) {
    $couponId = $_GET['delete'];
    $db->query("DELETE FROM coupons WHERE id = ?", [$couponId]);
    $_SESSION['success'] = 'Cupón eliminado exitosamente';
    header('Location: coupons.php');
    exit;
}

// Procesar cambio de estado
if (isset($_GET['toggle'])) {
    $couponId = $_GET['toggle'];
    $coupon = $db->fetch("SELECT status FROM coupons WHERE id = ?", [$couponId]);
    
    if ($coupon) {
        $newStatus = $coupon['status'] === 'active' ? 'inactive' : 'active';
        $db->query("UPDATE coupons SET status = ? WHERE id = ?", [$newStatus, $couponId]);
        $_SESSION['success'] = 'Estado actualizado';
    }
    
    header('Location: coupons.php');
    exit;
}

// Procesar formulario de creación/edición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $description = trim($_POST['description'] ?? '');
        $type = $_POST['type'] ?? 'percentage';
        $value = floatval($_POST['value'] ?? 0);
        $maxUses = !empty($_POST['max_uses']) ? intval($_POST['max_uses']) : null;
        $startDate = $_POST['start_date'] ?? date('Y-m-d');
        $endDate = $_POST['end_date'] ?? date('Y-m-d', strtotime('+1 month'));
        $status = $_POST['status'] ?? 'active';
        $applicableTo = $_POST['applicable_to'] ?? 'all';
        $minPurchase = floatval($_POST['min_purchase'] ?? 0);
        
        // Validaciones
        if (empty($code)) {
            throw new Exception('El código es obligatorio');
        }
        
        if ($value <= 0) {
            throw new Exception('El valor debe ser mayor a 0');
        }
        
        if ($type === 'percentage' && $value > 100) {
            throw new Exception('El porcentaje no puede ser mayor a 100');
        }
        
        if (strtotime($endDate) < strtotime($startDate)) {
            throw new Exception('La fecha de fin debe ser posterior a la fecha de inicio');
        }
        
        if (isset($_POST['coupon_id'])) {
            // Editar cupón existente
            $couponId = $_POST['coupon_id'];
            
            // Verificar código único (excepto el propio cupón)
            $existing = $db->fetch("SELECT id FROM coupons WHERE code = ? AND id != ?", [$code, $couponId]);
            if ($existing) {
                throw new Exception('El código ya está en uso');
            }
            
            $db->query("
                UPDATE coupons SET 
                    code = ?,
                    description = ?,
                    type = ?,
                    value = ?,
                    max_uses = ?,
                    start_date = ?,
                    end_date = ?,
                    status = ?,
                    applicable_to = ?,
                    min_purchase = ?
                WHERE id = ?
            ", [$code, $description, $type, $value, $maxUses, $startDate, $endDate, $status, $applicableTo, $minPurchase, $couponId]);
            
            $_SESSION['success'] = 'Cupón actualizado exitosamente';
        } else {
            // Crear nuevo cupón
            $existing = $db->fetch("SELECT id FROM coupons WHERE code = ?", [$code]);
            if ($existing) {
                throw new Exception('El código ya está en uso');
            }
            
            $db->query("
                INSERT INTO coupons (code, description, type, value, max_uses, start_date, end_date, status, applicable_to, min_purchase, used_count)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)
            ", [$code, $description, $type, $value, $maxUses, $startDate, $endDate, $status, $applicableTo, $minPurchase]);
            
            $_SESSION['success'] = 'Cupón creado exitosamente';
        }
        
        header('Location: coupons.php');
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

// Obtener todos los cupones
$coupons = $db->fetchAll("
    SELECT 
        c.*,
        (SELECT COUNT(*) FROM coupon_usage WHERE coupon_id = c.id) as total_uses
    FROM coupons c
    ORDER BY c.created_at DESC
");

// Estadísticas
$stats = [
    'total' => count($coupons),
    'active' => count(array_filter($coupons, fn($c) => $c['status'] === 'active')),
    'total_discounts' => $db->fetch("SELECT COALESCE(SUM(discount_amount), 0) as total FROM coupon_usage")['total'],
    'total_uses' => $db->fetch("SELECT COUNT(*) as count FROM coupon_usage")['count']
];

// Si hay un cupón para editar
$editCoupon = null;
if (isset($_GET['edit'])) {
    $editCoupon = $db->fetch("SELECT * FROM coupons WHERE id = ?", [$_GET['edit']]);
}

$title = 'Cupones de Descuento - Super Admin';
include BASE_PATH . '/includes/header.php';
?>

<div class="min-h-screen bg-gray-100" x-data="{ sidebarOpen: false, showModal: <?php echo $editCoupon ? 'true' : 'false'; ?> }">
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
                    <h1 class="text-2xl font-bold text-gray-900">Cupones de Descuento</h1>
                    <p class="text-sm text-gray-500">Gestiona cupones y promociones</p>
                </div>
                <button @click="showModal = true" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 shadow-lg">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Nuevo Cupón
                </button>
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
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm text-gray-600">Total Cupones</p>
                    <p class="text-3xl font-bold text-indigo-600"><?php echo $stats['total']; ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm text-gray-600">Cupones Activos</p>
                    <p class="text-3xl font-bold text-green-600"><?php echo $stats['active']; ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm text-gray-600">Veces Usado</p>
                    <p class="text-3xl font-bold text-blue-600"><?php echo $stats['total_uses']; ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm text-gray-600">Descuentos Totales</p>
                    <p class="text-3xl font-bold text-purple-600"><?php echo formatPrice($stats['total_discounts']); ?></p>
                </div>
            </div>

            <!-- Lista de Cupones -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Todos los Cupones</h2>
                </div>
                
                <?php if (!empty($coupons)): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Código</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Valor</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usos</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vigencia</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($coupons as $coupon): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <p class="text-sm font-bold text-gray-900 font-mono"><?php echo e($coupon['code']); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo e($coupon['description']); ?></p>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $coupon['type'] === 'percentage' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'; ?>">
                                        <?php echo $coupon['type'] === 'percentage' ? 'Porcentaje' : 'Fijo'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-900">
                                    <?php echo $coupon['type'] === 'percentage' ? $coupon['value'] . '%' : formatPrice($coupon['value']); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <?php echo $coupon['total_uses']; ?><?php echo $coupon['max_uses'] ? ' / ' . $coupon['max_uses'] : ' / ∞'; ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <p><?php echo date('d/m/Y', strtotime($coupon['start_date'])); ?></p>
                                    <p class="text-xs text-gray-500">hasta <?php echo date('d/m/Y', strtotime($coupon['end_date'])); ?></p>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $coupon['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                        <?php echo ucfirst($coupon['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm space-x-2">
                                    <a href="?edit=<?php echo $coupon['id']; ?>" class="text-indigo-600 hover:text-indigo-900">Editar</a>
                                    <a href="?toggle=<?php echo $coupon['id']; ?>" class="text-orange-600 hover:text-orange-900">
                                        <?php echo $coupon['status'] === 'active' ? 'Desactivar' : 'Activar'; ?>
                                    </a>
                                    <a href="?delete=<?php echo $coupon['id']; ?>" 
                                       onclick="return confirm('¿Eliminar este cupón?')"
                                       class="text-red-600 hover:text-red-900">Eliminar</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-12">
                    <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                    <p class="text-gray-500">No hay cupones creados</p>
                    <button @click="showModal = true" class="mt-4 text-indigo-600 hover:text-indigo-700 font-medium">
                        Crear primer cupón
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Modal Crear/Editar Cupón -->
    <div x-show="showModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showModal = false"></div>
            
            <div class="relative bg-white rounded-lg shadow-xl max-w-2xl w-full p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-6">
                    <?php echo $editCoupon ? 'Editar Cupón' : 'Crear Nuevo Cupón'; ?>
                </h3>
                
                <form method="POST">
                    <?php if ($editCoupon): ?>
                    <input type="hidden" name="coupon_id" value="<?php echo $editCoupon['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Código *</label>
                            <input type="text" name="code" value="<?php echo $editCoupon ? e($editCoupon['code']) : ''; ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent uppercase" 
                                   placeholder="VERANO2026" required>
                            <p class="text-xs text-gray-500 mt-1">Único, sin espacios</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tipo *</label>
                            <select name="type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" required>
                                <option value="percentage" <?php echo ($editCoupon && $editCoupon['type'] === 'percentage') ? 'selected' : ''; ?>>Porcentaje (%)</option>
                                <option value="fixed" <?php echo ($editCoupon && $editCoupon['type'] === 'fixed') ? 'selected' : ''; ?>>Monto Fijo (RD$)</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Valor *</label>
                            <input type="number" name="value" value="<?php echo $editCoupon ? $editCoupon['value'] : ''; ?>" 
                                   step="0.01" min="0" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Usos Máximos</label>
                            <input type="number" name="max_uses" value="<?php echo $editCoupon ? $editCoupon['max_uses'] : ''; ?>" 
                                   min="0" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                   placeholder="Ilimitado">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Inicio *</label>
                            <input type="date" name="start_date" value="<?php echo $editCoupon ? $editCoupon['start_date'] : date('Y-m-d'); ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Fin *</label>
                            <input type="date" name="end_date" value="<?php echo $editCoupon ? $editCoupon['end_date'] : date('Y-m-d', strtotime('+1 month')); ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Aplicable a</label>
                            <select name="applicable_to" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                <option value="all" <?php echo ($editCoupon && $editCoupon['applicable_to'] === 'all') ? 'selected' : ''; ?>>Todos los planes</option>
                                <option value="basic" <?php echo ($editCoupon && $editCoupon['applicable_to'] === 'basic') ? 'selected' : ''; ?>>Solo Basic</option>
                                <option value="professional" <?php echo ($editCoupon && $editCoupon['applicable_to'] === 'professional') ? 'selected' : ''; ?>>Solo Professional</option>
                                <option value="enterprise" <?php echo ($editCoupon && $editCoupon['applicable_to'] === 'enterprise') ? 'selected' : ''; ?>>Solo Enterprise</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Compra Mínima (RD$)</label>
                            <input type="number" name="min_purchase" value="<?php echo $editCoupon ? $editCoupon['min_purchase'] : '0'; ?>" 
                                   step="0.01" min="0" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                            <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                <option value="active" <?php echo (!$editCoupon || $editCoupon['status'] === 'active') ? 'selected' : ''; ?>>Activo</option>
                                <option value="inactive" <?php echo ($editCoupon && $editCoupon['status'] === 'inactive') ? 'selected' : ''; ?>>Inactivo</option>
                            </select>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
                            <textarea name="description" rows="2" 
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                      placeholder="Descripción del cupón"><?php echo $editCoupon ? e($editCoupon['description']) : ''; ?></textarea>
                        </div>
                    </div>
                    
                    <div class="flex gap-3">
                        <button type="button" @click="showModal = false" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                            Cancelar
                        </button>
                        <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                            <?php echo $editCoupon ? 'Actualizar' : 'Crear'; ?> Cupón
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
[x-cloak] { display: none !important; }
</style>

<?php include BASE_PATH . '/includes/footer.php'; ?>
