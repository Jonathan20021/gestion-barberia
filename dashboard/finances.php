<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Auth.php';
require_once BASE_PATH . '/core/Helpers.php';

Auth::requireRole('owner');

$db = Database::getInstance();
$barbershopId = (int) ($_SESSION['barbershop_id'] ?? 0);
$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($barbershopId <= 0) {
    setFlash('error', 'No se encontro una barberia vinculada a tu usuario.');
    redirect(BASE_URL . '/dashboard');
}

requireBarbershopModuleAccess($barbershopId, 'finanzas_avanzadas');

$flash = getFlash();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = input('action');

    try {
        if ($action === 'create_transaction') {
            $type = input('type');
            $amount = (float) input('amount', 0);
            $description = trim((string) input('description', ''));
            $category = trim((string) input('category', ''));
            $paymentMethod = input('payment_method', 'cash');
            $transactionDate = input('transaction_date', date('Y-m-d'));

            $allowedTypes = ['income', 'expense', 'commission'];
            $allowedPaymentMethods = ['cash', 'card', 'transfer', 'online'];

            if (!in_array($type, $allowedTypes, true)) {
                throw new Exception('Tipo de movimiento no valido.');
            }
            if (!in_array($paymentMethod, $allowedPaymentMethods, true)) {
                throw new Exception('Metodo de pago no valido.');
            }
            if ($amount <= 0) {
                throw new Exception('El monto debe ser mayor a cero.');
            }
            if ($description === '') {
                throw new Exception('La descripcion es obligatoria.');
            }
            if (!isValidDate($transactionDate, 'Y-m-d')) {
                throw new Exception('Fecha invalida.');
            }

            $db->execute(
                "INSERT INTO transactions (
                    barbershop_id, type, amount, description, category, payment_method, created_by, transaction_date
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $barbershopId,
                    $type,
                    $amount,
                    $description,
                    $category !== '' ? $category : null,
                    $paymentMethod,
                    $userId > 0 ? $userId : null,
                    $transactionDate . ' 00:00:00',
                ]
            );

            setFlash('success', 'Movimiento registrado correctamente.');
            redirect(BASE_URL . '/dashboard/finances');
        }

        if ($action === 'delete_transaction') {
            $transactionId = (int) input('transaction_id', 0);
            if ($transactionId <= 0) {
                throw new Exception('Movimiento invalido.');
            }

            $transaction = $db->fetch(
                "SELECT id, appointment_id
                 FROM transactions
                 WHERE id = ? AND barbershop_id = ?
                 LIMIT 1",
                [$transactionId, $barbershopId]
            );

            if (!$transaction) {
                throw new Exception('No se encontro el movimiento.');
            }

            if (!empty($transaction['appointment_id'])) {
                throw new Exception('No puedes eliminar movimientos automaticos de citas desde aqui.');
            }

            $db->execute("DELETE FROM transactions WHERE id = ?", [$transactionId]);

            setFlash('success', 'Movimiento eliminado.');
            redirect(BASE_URL . '/dashboard/finances');
        }
    } catch (Exception $e) {
        setFlash('error', $e->getMessage());
        redirect(BASE_URL . '/dashboard/finances');
    }
}

syncBarbershopAppointmentTransactions($barbershopId);

$barbershop = $db->fetch(
    "SELECT b.business_name, l.type AS license_type
     FROM barbershops b
     INNER JOIN licenses l ON l.id = b.license_id
     WHERE b.id = ?
     LIMIT 1",
    [$barbershopId]
);

$stats = [
    'income' => (float) ($db->fetch("SELECT COALESCE(SUM(amount), 0) AS total FROM transactions WHERE barbershop_id = ? AND type = 'income'", [$barbershopId])['total'] ?? 0),
    'expense' => (float) ($db->fetch("SELECT COALESCE(SUM(amount), 0) AS total FROM transactions WHERE barbershop_id = ? AND type = 'expense'", [$barbershopId])['total'] ?? 0),
    'commission' => (float) ($db->fetch("SELECT COALESCE(SUM(amount), 0) AS total FROM transactions WHERE barbershop_id = ? AND type = 'commission'", [$barbershopId])['total'] ?? 0),
    'month_income' => (float) ($db->fetch("SELECT COALESCE(SUM(amount), 0) AS total FROM transactions WHERE barbershop_id = ? AND type = 'income' AND DATE_FORMAT(transaction_date, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')", [$barbershopId])['total'] ?? 0),
    'month_expense' => (float) ($db->fetch("SELECT COALESCE(SUM(amount), 0) AS total FROM transactions WHERE barbershop_id = ? AND type IN ('expense', 'commission') AND DATE_FORMAT(transaction_date, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')", [$barbershopId])['total'] ?? 0),
];
$stats['net'] = $stats['income'] - $stats['expense'] - $stats['commission'];
$stats['month_net'] = $stats['month_income'] - $stats['month_expense'];

$cashflow = $db->fetchAll(
    "SELECT DATE_FORMAT(transaction_date, '%Y-%m') AS month_key,
            DATE_FORMAT(transaction_date, '%b %Y') AS month_label,
            SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) AS income,
            SUM(CASE WHEN type IN ('expense', 'commission') THEN amount ELSE 0 END) AS expense
     FROM transactions
     WHERE barbershop_id = ?
       AND transaction_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
     GROUP BY month_key, month_label
     ORDER BY month_key ASC",
    [$barbershopId]
);

$categorySummary = $db->fetchAll(
    "SELECT COALESCE(category, 'Sin categoria') AS category,
            SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) AS income,
            SUM(CASE WHEN type IN ('expense', 'commission') THEN amount ELSE 0 END) AS expense
     FROM transactions
     WHERE barbershop_id = ?
     GROUP BY COALESCE(category, 'Sin categoria')
     ORDER BY (SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) - SUM(CASE WHEN type IN ('expense', 'commission') THEN amount ELSE 0 END)) DESC",
    [$barbershopId]
);

$pendingPayments = $db->fetchAll(
    "SELECT id, appointment_date, start_time, client_name, price, payment_status
     FROM appointments
     WHERE barbershop_id = ?
       AND status = 'completed'
       AND payment_status != 'paid'
     ORDER BY appointment_date DESC, start_time DESC
     LIMIT 20",
    [$barbershopId]
);

$transactions = $db->fetchAll(
    "SELECT t.*, a.client_name AS appointment_client
     FROM transactions t
     LEFT JOIN appointments a ON a.id = t.appointment_id
     WHERE t.barbershop_id = ?
     ORDER BY t.transaction_date DESC, t.id DESC
     LIMIT 100",
    [$barbershopId]
);

$title = 'Finanzas - Dashboard';
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
                    <h1 class="text-2xl font-bold text-gray-900">Finanzas y Contabilidad</h1>
                    <p class="text-sm text-gray-500"><?php echo e($barbershop['business_name'] ?? 'Mi barberia'); ?> · Plan <?php echo e(ucfirst($barbershop['license_type'] ?? 'professional')); ?></p>
                </div>
            </div>
        </div>

        <main class="p-6 space-y-6">
            <?php if ($flash): ?>
            <div class="rounded-lg p-4 border-l-4 <?php echo $flash['type'] === 'success' ? 'bg-green-50 border-green-500 text-green-700' : 'bg-red-50 border-red-500 text-red-700'; ?>">
                <?php echo e($flash['message']); ?>
            </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-6 gap-4">
                <div class="bg-white rounded-lg shadow p-4">
                    <p class="text-xs text-gray-500 uppercase">Ingresos Totales</p>
                    <p class="text-2xl font-bold text-green-600 mt-2"><?php echo formatPrice($stats['income']); ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <p class="text-xs text-gray-500 uppercase">Gastos Totales</p>
                    <p class="text-2xl font-bold text-red-600 mt-2"><?php echo formatPrice($stats['expense'] + $stats['commission']); ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <p class="text-xs text-gray-500 uppercase">Balance General</p>
                    <p class="text-2xl font-bold <?php echo $stats['net'] >= 0 ? 'text-blue-600' : 'text-red-600'; ?> mt-2"><?php echo formatPrice($stats['net']); ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <p class="text-xs text-gray-500 uppercase">Ingresos del Mes</p>
                    <p class="text-2xl font-bold text-emerald-600 mt-2"><?php echo formatPrice($stats['month_income']); ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <p class="text-xs text-gray-500 uppercase">Egresos del Mes</p>
                    <p class="text-2xl font-bold text-orange-600 mt-2"><?php echo formatPrice($stats['month_expense']); ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <p class="text-xs text-gray-500 uppercase">Balance del Mes</p>
                    <p class="text-2xl font-bold <?php echo $stats['month_net'] >= 0 ? 'text-indigo-600' : 'text-red-600'; ?> mt-2"><?php echo formatPrice($stats['month_net']); ?></p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Flujo de Caja (Ultimos 6 Meses)</h2>
                    </div>
                    <div class="p-6 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-gray-500 border-b border-gray-200">
                                    <th class="py-2 pr-4">Mes</th>
                                    <th class="py-2 pr-4 text-right">Ingresos</th>
                                    <th class="py-2 pr-4 text-right">Egresos</th>
                                    <th class="py-2 text-right">Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cashflow as $row): ?>
                                <?php $balance = (float)$row['income'] - (float)$row['expense']; ?>
                                <tr class="border-b border-gray-100">
                                    <td class="py-2 pr-4 font-medium text-gray-900"><?php echo e($row['month_label']); ?></td>
                                    <td class="py-2 pr-4 text-right text-green-700"><?php echo formatPrice($row['income']); ?></td>
                                    <td class="py-2 pr-4 text-right text-red-700"><?php echo formatPrice($row['expense']); ?></td>
                                    <td class="py-2 text-right font-semibold <?php echo $balance >= 0 ? 'text-indigo-700' : 'text-red-700'; ?>"><?php echo formatPrice($balance); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($cashflow)): ?>
                                <tr>
                                    <td colspan="4" class="py-6 text-center text-gray-500">Aun no hay movimientos registrados.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Registrar Movimiento</h2>
                    </div>
                    <form method="POST" class="p-6 space-y-4">
                        <input type="hidden" name="action" value="create_transaction">
                        <div>
                            <label class="block text-sm text-gray-700 mb-1">Tipo</label>
                            <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                <option value="income">Ingreso</option>
                                <option value="expense">Gasto</option>
                                <option value="commission">Comision</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 mb-1">Monto (RD$)</label>
                            <input type="number" min="0.01" step="0.01" name="amount" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 mb-1">Categoria</label>
                            <input type="text" name="category" placeholder="Ej: Productos, Nomina, Alquiler" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 mb-1">Descripcion</label>
                            <textarea name="description" rows="3" required class="w-full px-3 py-2 border border-gray-300 rounded-lg"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 mb-1">Metodo de pago</label>
                            <select name="payment_method" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                <option value="cash">Efectivo</option>
                                <option value="card">Tarjeta</option>
                                <option value="transfer">Transferencia</option>
                                <option value="online">Online</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 mb-1">Fecha</label>
                            <input type="date" name="transaction_date" value="<?php echo date('Y-m-d'); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        </div>
                        <button type="submit" class="w-full px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Guardar Movimiento</button>
                    </form>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Rentabilidad por Categoria</h2>
                    </div>
                    <div class="p-6 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-gray-500 border-b border-gray-200">
                                    <th class="py-2 pr-4">Categoria</th>
                                    <th class="py-2 pr-4 text-right">Ingresos</th>
                                    <th class="py-2 pr-4 text-right">Egresos</th>
                                    <th class="py-2 text-right">Neto</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categorySummary as $row): ?>
                                <?php $net = (float)$row['income'] - (float)$row['expense']; ?>
                                <tr class="border-b border-gray-100">
                                    <td class="py-2 pr-4 text-gray-900 font-medium"><?php echo e($row['category']); ?></td>
                                    <td class="py-2 pr-4 text-right text-green-700"><?php echo formatPrice($row['income']); ?></td>
                                    <td class="py-2 pr-4 text-right text-red-700"><?php echo formatPrice($row['expense']); ?></td>
                                    <td class="py-2 text-right <?php echo $net >= 0 ? 'text-indigo-700' : 'text-red-700'; ?> font-semibold"><?php echo formatPrice($net); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($categorySummary)): ?>
                                <tr>
                                    <td colspan="4" class="py-6 text-center text-gray-500">No hay datos por categoria todavia.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Cuentas por Cobrar (Citas Completadas)</h2>
                    </div>
                    <div class="p-6 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-gray-500 border-b border-gray-200">
                                    <th class="py-2 pr-4">Fecha</th>
                                    <th class="py-2 pr-4">Cliente</th>
                                    <th class="py-2 text-right">Monto</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendingPayments as $row): ?>
                                <tr class="border-b border-gray-100">
                                    <td class="py-2 pr-4 text-gray-700"><?php echo formatDate($row['appointment_date']); ?></td>
                                    <td class="py-2 pr-4 text-gray-900 font-medium"><?php echo e($row['client_name']); ?></td>
                                    <td class="py-2 text-right text-orange-700 font-semibold"><?php echo formatPrice($row['price']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($pendingPayments)): ?>
                                <tr>
                                    <td colspan="3" class="py-6 text-center text-gray-500">No hay cuentas pendientes por cobrar.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Libro Diario (Ultimos 100 Movimientos)</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Descripcion</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Categoria</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Monto</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Origen</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($transactions as $row): ?>
                            <?php
                                $isAuto = !empty($row['appointment_id']);
                                $typeLabel = $row['type'] === 'income' ? 'Ingreso' : ($row['type'] === 'expense' ? 'Gasto' : 'Comision');
                                $typeClass = $row['type'] === 'income' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700';
                            ?>
                            <tr>
                                <td class="px-6 py-3 text-gray-700"><?php echo formatDateTime($row['transaction_date']); ?></td>
                                <td class="px-6 py-3">
                                    <span class="px-2 py-1 text-xs rounded-full <?php echo $typeClass; ?>"><?php echo e($typeLabel); ?></span>
                                </td>
                                <td class="px-6 py-3 text-gray-900">
                                    <p class="font-medium"><?php echo e($row['description']); ?></p>
                                    <?php if (!empty($row['appointment_client'])): ?>
                                    <p class="text-xs text-gray-500 mt-1">Cliente: <?php echo e($row['appointment_client']); ?></p>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-3 text-gray-700"><?php echo e($row['category'] ?? 'Sin categoria'); ?></td>
                                <td class="px-6 py-3 text-right font-semibold <?php echo $row['type'] === 'income' ? 'text-green-700' : 'text-red-700'; ?>"><?php echo formatPrice($row['amount']); ?></td>
                                <td class="px-6 py-3 text-center">
                                    <span class="text-xs <?php echo $isAuto ? 'text-indigo-700 bg-indigo-100' : 'text-gray-700 bg-gray-100'; ?> px-2 py-1 rounded-full"><?php echo $isAuto ? 'Automatico' : 'Manual'; ?></span>
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <?php if (!$isAuto): ?>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="delete_transaction">
                                        <input type="hidden" name="transaction_id" value="<?php echo (int)$row['id']; ?>">
                                        <button type="submit" onclick="return confirm('¿Eliminar este movimiento manual?')" class="text-red-600 hover:text-red-800 text-xs font-semibold">Eliminar</button>
                                    </form>
                                    <?php else: ?>
                                    <span class="text-xs text-gray-400">Bloqueado</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-gray-500">No hay movimientos registrados.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>
