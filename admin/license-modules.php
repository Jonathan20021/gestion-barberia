<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Auth.php';
require_once BASE_PATH . '/core/Helpers.php';

Auth::requireRole('superadmin');

$db = Database::getInstance();

$moduleCatalog = defined('LICENSE_MODULES') ? LICENSE_MODULES : [];
$licenseTypes = array_keys(LICENSE_TYPES);

$db->query("CREATE TABLE IF NOT EXISTS license_type_modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    license_type ENUM('basic', 'professional', 'enterprise') NOT NULL,
    module_key VARCHAR(100) NOT NULL,
    is_enabled TINYINT(1) NOT NULL DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_license_module (license_type, module_key),
    INDEX idx_module (module_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        foreach ($licenseTypes as $licenseType) {
            foreach ($moduleCatalog as $moduleKey => $moduleConfig) {
                $inputName = 'module_' . $moduleKey . '_' . $licenseType;
                $isEnabled = isset($_POST[$inputName]) ? 1 : 0;

                $db->query(
                    "INSERT INTO license_type_modules (license_type, module_key, is_enabled)
                     VALUES (?, ?, ?)
                     ON DUPLICATE KEY UPDATE is_enabled = VALUES(is_enabled)",
                    [$licenseType, $moduleKey, $isEnabled]
                );
            }
        }

        $_SESSION['success'] = 'Modulos por licencia actualizados correctamente.';
    } catch (Exception $e) {
        $_SESSION['error'] = 'No se pudieron guardar los modulos: ' . $e->getMessage();
    }

    header('Location: license-modules.php');
    exit;
}

$rows = $db->fetchAll("SELECT license_type, module_key, is_enabled FROM license_type_modules");
$moduleMap = [];
foreach ($rows as $row) {
    $moduleMap[$row['license_type']][$row['module_key']] = (int)$row['is_enabled'];
}

$title = 'Modulos por Licencia - Super Admin';
include BASE_PATH . '/includes/header.php';
?>

<div class="min-h-screen bg-gray-100" x-data="{ sidebarOpen: false }">
    <?php include BASE_PATH . '/includes/sidebar-admin.php'; ?>

    <div x-show="sidebarOpen" @click="sidebarOpen = false"
         class="fixed inset-0 z-40 bg-gray-900 bg-opacity-50 lg:hidden" style="display:none"></div>

    <div class="lg:pl-64">
        <div class="sticky top-0 z-40 flex h-16 bg-white border-b border-gray-200 shadow-sm">
            <button @click="sidebarOpen = true" class="px-4 text-gray-500 lg:hidden">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <div class="flex items-center justify-between flex-1 px-4 sm:px-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Modulos por Licencia</h1>
                    <p class="text-sm text-gray-500">Activa o desactiva modulos para cada tipo de plan</p>
                </div>
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

            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <form method="POST" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Modulo</th>
                                <?php foreach ($licenseTypes as $licenseType): ?>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase"><?php echo e(LICENSE_TYPES[$licenseType]['name']); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($moduleCatalog as $moduleKey => $moduleConfig): ?>
                            <tr>
                                <td class="px-6 py-4">
                                    <p class="text-sm font-semibold text-gray-900"><?php echo e($moduleConfig['name'] ?? $moduleKey); ?></p>
                                    <p class="text-xs text-gray-500 mt-1"><?php echo e($moduleConfig['description'] ?? ''); ?></p>
                                </td>
                                <?php foreach ($licenseTypes as $licenseType): ?>
                                <?php
                                    $defaultEnabled = (int)($moduleConfig['default'][$licenseType] ?? 0);
                                    $isEnabled = (int)($moduleMap[$licenseType][$moduleKey] ?? $defaultEnabled) === 1;
                                    $inputName = 'module_' . $moduleKey . '_' . $licenseType;
                                ?>
                                <td class="px-6 py-4 text-center">
                                    <label class="inline-flex items-center cursor-pointer">
                                        <input type="checkbox" name="<?php echo e($inputName); ?>" class="h-5 w-5 text-indigo-600 rounded" <?php echo $isEnabled ? 'checked' : ''; ?>>
                                    </label>
                                </td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($moduleCatalog)): ?>
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-gray-500">No hay modulos configurados en LICENSE_MODULES.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
                        <button type="submit" class="px-5 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                            Guardar Configuracion
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</div>
