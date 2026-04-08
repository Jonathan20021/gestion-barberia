<?php
/**
 * Helpers - Funciones auxiliares
 */

/**
 * Redireccionar
 */
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

/**
 * Obtener el valor de un parámetro POST/GET
 */
function input($key, $default = null) {
    if (isset($_POST[$key])) {
        return sanitize($_POST[$key]);
    }
    if (isset($_GET[$key])) {
        return sanitize($_GET[$key]);
    }
    return $default;
}

/**
 * Sanitizar entrada
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Escapar HTML
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Formatear precio
 */
function formatPrice($amount) {
    return 'RD$' . number_format($amount, 2, '.', ',');
}

/**
 * Formatear fecha
 */
function formatDate($date, $format = 'd/m/Y') {
    return date($format, strtotime($date));
}

/**
 * Formatear fecha y hora
 */
function formatDateTime($datetime, $format = 'd/m/Y H:i') {
    return date($format, strtotime($datetime));
}

/**
 * Generar slug
 */
function generateSlug($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

function generateUniqueBarberSlug($db, $barbershopId, $fullName, $excludeBarberId = null) {
    $baseSlug = generateSlug($fullName);
    if ($baseSlug === '') {
        $baseSlug = 'barber';
    }

    $slug = $baseSlug;
    $counter = 2;

    while (true) {
        $params = [$barbershopId, $slug];
        $query = "SELECT id FROM barbers WHERE barbershop_id = ? AND slug = ?";

        if ($excludeBarberId !== null) {
            $query .= " AND id != ?";
            $params[] = $excludeBarberId;
        }

        $existingBarber = $db->fetch($query, $params);
        if (!$existingBarber) {
            return $slug;
        }

        $slug = $baseSlug . '-' . $counter;
        $counter++;
    }
}

/**
 * Generar código único
 */
function generateCode($length = 8) {
    return strtoupper(substr(bin2hex(random_bytes($length)), 0, $length));
}

/**
 * Verificar si es AJAX
 */
function isAjax() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Respuesta JSON
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Subir archivo
 */
function uploadFile($file, $directory = 'uploads', $allowedTypes = ['jpg', 'jpeg', 'png', 'gif']) {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'message' => 'Error en el archivo'];
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Error al subir archivo'];
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($extension, $allowedTypes)) {
        return ['success' => false, 'message' => 'Tipo de archivo no permitido'];
    }
    
    $fileName = uniqid() . '.' . $extension;
    $uploadPath = BASE_PATH . '/assets/' . $directory . '/' . $fileName;
    
    if (!is_dir(dirname($uploadPath))) {
        mkdir(dirname($uploadPath), 0755, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return ['success' => true, 'filename' => $fileName, 'path' => $directory . '/' . $fileName];
    }
    
    return ['success' => false, 'message' => 'Error al guardar archivo'];
}

/**
 * Generar nombre de días
 */
function getDayName($dayNumber) {
    $days = [
        0 => 'Domingo',
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado'
    ];
    return $days[$dayNumber] ?? '';
}

/**
 * Generar nombre de mes
 */
function getMonthName($monthNumber) {
    $months = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    ];
    return $months[$monthNumber] ?? '';
}

/**
 * Validar email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validar teléfono RD
 */
function isValidPhone($phone) {
    // Formato: 809-123-4567 o 8091234567
    $phone = preg_replace('/[^0-9]/', '', $phone);
    return preg_match('/^(809|829|849)\d{7}$/', $phone);
}

/**
 * Mensaje flash
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Verificar permisos de barbería
 */
function canAccessBarbershop($barbershopId) {
    $user = Auth::user();
    if (!$user) return false;
    
    if ($user['role'] === 'superadmin') return true;
    if (isset($user['barbershop_id']) && $user['barbershop_id'] == $barbershopId) return true;
    
    return false;
}

/**
 * Obtener avatar por defecto
 */
function getDefaultAvatar($name) {
    $initial = strtoupper(substr($name, 0, 1));
    return 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=random&color=fff&size=200';
}

/**
 * Calcular duración entre horas
 */
function calculateDuration($startTime, $endTime) {
    $start = strtotime($startTime);
    $end = strtotime($endTime);
    return ($end - $start) / 60; // Retorna minutos
}

/**
 * Obtener slots de tiempo disponibles
 */
function getTimeSlots($startTime, $endTime, $interval = 30) {
    $slots = [];
    $start = strtotime($startTime);
    $end = strtotime($endTime);
    
    while ($start < $end) {
        $slots[] = date('H:i', $start);
        $start += $interval * 60;
    }
    
    return $slots;
}

/**
 * Verificar si una licencia está activa
 */
function isLicenseActive($licenseId) {
    if (empty($licenseId)) {
        return false;
    }

    $db = Database::getInstance();
    $license = $db->fetch(
        "SELECT id, status, end_date, trial_end_date FROM licenses WHERE id = ?",
        [$licenseId]
    );
    
    if (!$license) return false;

    $today = date('Y-m-d');

    // Expiración automática de trial
    if ($license['status'] === 'trial') {
        if (!empty($license['trial_end_date']) && $license['trial_end_date'] < $today) {
            $db->execute("UPDATE licenses SET status = 'expired' WHERE id = ?", [$license['id']]);
            return false;
        }
        return !empty($license['trial_end_date']) && $license['trial_end_date'] >= $today;
    }

    if ($license['status'] !== 'active') {
        return false;
    }

    if (!empty($license['end_date']) && $license['end_date'] < $today) {
        $db->execute("UPDATE licenses SET status = 'expired' WHERE id = ?", [$license['id']]);
        return false;
    }

    return !empty($license['end_date']) && $license['end_date'] >= $today;
}

/**
 * Obtener configuración de licencia por barbería
 */
function getLicenseConfigForBarbershop($barbershopId) {
    $db = Database::getInstance();
    $row = $db->fetch(
        "SELECT l.id as license_id, l.type as license_type
         FROM barbershops b
         INNER JOIN licenses l ON b.license_id = l.id
         WHERE b.id = ?
         LIMIT 1",
        [$barbershopId]
    );

    if (!$row || empty($row['license_type']) || !isset(LICENSE_TYPES[$row['license_type']])) {
        return null;
    }

    return [
        'license_id' => intval($row['license_id']),
        'license_type' => $row['license_type'],
        'limits' => LICENSE_TYPES[$row['license_type']]
    ];
}

/**
 * Verificar si un modulo esta habilitado para un tipo de licencia
 */
function isModuleEnabledForLicenseType($licenseType, $moduleKey) {
    static $cachedOverrides = null;

    if (!defined('LICENSE_MODULES') || !isset(LICENSE_MODULES[$moduleKey])) {
        return false;
    }

    $defaultEnabled = (int) (LICENSE_MODULES[$moduleKey]['default'][$licenseType] ?? 0) === 1;

    $db = Database::getInstance();
    if (!$db->tableExists('license_type_modules')) {
        return $defaultEnabled;
    }

    if ($cachedOverrides === null) {
        $cachedOverrides = [];
        $rows = $db->fetchAll("SELECT license_type, module_key, is_enabled FROM license_type_modules");
        foreach ($rows as $row) {
            $cachedOverrides[$row['license_type']][$row['module_key']] = (int) $row['is_enabled'];
        }
    }

    if (isset($cachedOverrides[$licenseType]) && array_key_exists($moduleKey, $cachedOverrides[$licenseType])) {
        return (int) $cachedOverrides[$licenseType][$moduleKey] === 1;
    }

    return $defaultEnabled;
}

/**
 * Verificar acceso a modulo por barberia
 */
function hasBarbershopModuleAccess($barbershopId, $moduleKey) {
    $cfg = getLicenseConfigForBarbershop($barbershopId);
    if (!$cfg) {
        return false;
    }

    return isModuleEnabledForLicenseType($cfg['license_type'], $moduleKey);
}

/**
 * Forzar acceso por modulo para una barberia
 */
function requireBarbershopModuleAccess($barbershopId, $moduleKey, $fallbackPath = '/dashboard') {
    if (hasBarbershopModuleAccess($barbershopId, $moduleKey)) {
        return;
    }

    setFlash('error', 'Tu plan actual no tiene acceso a este modulo. Contacta al Super Admin para habilitarlo.');
    redirect(BASE_URL . $fallbackPath);
}

/**
 * Sincronizar transaccion automatica de ingreso para una cita
 */
function syncAppointmentIncomeTransaction($appointmentId) {
    $db = Database::getInstance();

    if (!$db->tableExists('transactions')) {
        return;
    }

    $appointment = $db->fetch(
        "SELECT a.id, a.barbershop_id, a.price, a.status, a.payment_status, a.payment_method,
                a.appointment_date, a.start_time, a.client_name,
                s.name AS service_name,
                u.full_name AS barber_name
         FROM appointments a
         LEFT JOIN services s ON s.id = a.service_id
         LEFT JOIN barbers b ON b.id = a.barber_id
         LEFT JOIN users u ON u.id = b.user_id
         WHERE a.id = ?
         LIMIT 1",
        [$appointmentId]
    );

    if (!$appointment) {
        return;
    }

    $eligible = $appointment['status'] === 'completed'
        && $appointment['payment_status'] === 'paid'
        && (float) $appointment['price'] > 0;

    $existing = $db->fetch(
        "SELECT id FROM transactions WHERE appointment_id = ? AND type = 'income' LIMIT 1",
        [$appointmentId]
    );

    if (!$eligible) {
        if ($existing) {
            $db->execute("DELETE FROM transactions WHERE id = ?", [$existing['id']]);
        }
        return;
    }

    $transactionDate = !empty($appointment['appointment_date'])
        ? $appointment['appointment_date'] . ' ' . (!empty($appointment['start_time']) ? $appointment['start_time'] : '00:00:00')
        : date('Y-m-d H:i:s');

    $description = 'Ingreso automatico cita #' . $appointment['id'];
    if (!empty($appointment['client_name'])) {
        $description .= ' - ' . $appointment['client_name'];
    }

    $category = !empty($appointment['service_name']) ? $appointment['service_name'] : 'Servicio';
    $referenceNumber = 'APT-' . str_pad((string) $appointment['id'], 6, '0', STR_PAD_LEFT);
    $paymentMethod = !empty($appointment['payment_method']) ? $appointment['payment_method'] : 'cash';

    if ($existing) {
        $db->execute(
            "UPDATE transactions
             SET barbershop_id = ?,
                 amount = ?,
                 description = ?,
                 category = ?,
                 payment_method = ?,
                 reference_number = ?,
                 transaction_date = ?
             WHERE id = ?",
            [
                $appointment['barbershop_id'],
                $appointment['price'],
                $description,
                $category,
                $paymentMethod,
                $referenceNumber,
                $transactionDate,
                $existing['id'],
            ]
        );
        return;
    }

    $db->execute(
        "INSERT INTO transactions (
            barbershop_id, appointment_id, type, amount, description, category,
            payment_method, reference_number, created_by, transaction_date
        ) VALUES (?, ?, 'income', ?, ?, ?, ?, ?, ?, ?)",
        [
            $appointment['barbershop_id'],
            $appointment['id'],
            $appointment['price'],
            $description,
            $category,
            $paymentMethod,
            $referenceNumber,
            null,
            $transactionDate,
        ]
    );
}

/**
 * Sincronizar transacciones de todas las citas de una barberia
 */
function syncBarbershopAppointmentTransactions($barbershopId) {
    $db = Database::getInstance();

    if (!$db->tableExists('transactions')) {
        return;
    }

    $appointments = $db->fetchAll(
        "SELECT id FROM appointments WHERE barbershop_id = ?",
        [$barbershopId]
    );

    foreach ($appointments as $appointment) {
        syncAppointmentIncomeTransaction((int) $appointment['id']);
    }
}

/**
 * Validar límite de barberos por barbería
 */
function canAddBarberToBarbershop($barbershopId, &$message = null) {
    $cfg = getLicenseConfigForBarbershop($barbershopId);
    if (!$cfg) {
        $message = 'No se pudo validar la licencia de la barbería.';
        return false;
    }

    $max = intval($cfg['limits']['max_barbers'] ?? -1);
    if ($max < 0) {
        return true;
    }

    $db = Database::getInstance();
    $count = intval($db->fetch(
        "SELECT COUNT(*) as total FROM barbers WHERE barbershop_id = ?",
        [$barbershopId]
    )['total'] ?? 0);

    if ($count >= $max) {
        $message = 'Tu plan ' . ucfirst($cfg['license_type']) . ' permite hasta ' . $max . ' barberos.';
        return false;
    }

    return true;
}

/**
 * Validar límite de servicios por barbería
 */
function canAddServiceToBarbershop($barbershopId, &$message = null) {
    $cfg = getLicenseConfigForBarbershop($barbershopId);
    if (!$cfg) {
        $message = 'No se pudo validar la licencia de la barbería.';
        return false;
    }

    $max = intval($cfg['limits']['max_services'] ?? -1);
    if ($max < 0) {
        return true;
    }

    $db = Database::getInstance();
    $count = intval($db->fetch(
        "SELECT COUNT(*) as total FROM services WHERE barbershop_id = ?",
        [$barbershopId]
    )['total'] ?? 0);

    if ($count >= $max) {
        $message = 'Tu plan ' . ucfirst($cfg['license_type']) . ' permite hasta ' . $max . ' servicios.';
        return false;
    }

    return true;
}

/**
 * Validar límite mensual de citas por barbería
 */
function canCreateAppointmentForBarbershop($barbershopId, &$message = null, $dateRef = null) {
    $cfg = getLicenseConfigForBarbershop($barbershopId);
    if (!$cfg) {
        $message = 'No se pudo validar la licencia de la barbería.';
        return false;
    }

    $max = intval($cfg['limits']['max_monthly_appointments'] ?? -1);
    if ($max < 0) {
        return true;
    }

    $dateRef = $dateRef ?: date('Y-m-d');
    $start = date('Y-m-01', strtotime($dateRef));
    $end = date('Y-m-t', strtotime($dateRef));

    $db = Database::getInstance();
    $count = intval($db->fetch(
        "SELECT COUNT(*) as total
         FROM appointments
         WHERE barbershop_id = ?
           AND appointment_date BETWEEN ? AND ?
           AND status NOT IN ('cancelled', 'no_show')",
        [$barbershopId, $start, $end]
    )['total'] ?? 0);

    if ($count >= $max) {
        $monthName = getMonthName(intval(date('n', strtotime($dateRef))));
        $message = 'Límite de citas del plan alcanzado (' . $max . ') para ' . $monthName . '.';
        return false;
    }

    return true;
}

/**
 * Validar límite de sucursales por licencia
 */
function canAddBarbershopToLicense($licenseId, &$message = null) {
    $db = Database::getInstance();
    try {
        $license = $db->fetch(
            "SELECT id, type, max_locations_override FROM licenses WHERE id = ? LIMIT 1",
            [$licenseId]
        );
    } catch (Exception $e) {
        // Fallback para ambientes que aun no aplican la migracion de override.
        $license = $db->fetch(
            "SELECT id, type FROM licenses WHERE id = ? LIMIT 1",
            [$licenseId]
        );
        if ($license && !isset($license['max_locations_override'])) {
            $license['max_locations_override'] = null;
        }
    }

    if (!$license || !isset(LICENSE_TYPES[$license['type']])) {
        $message = 'Licencia no válida.';
        return false;
    }

    $maxDefault = intval(LICENSE_TYPES[$license['type']]['max_locations'] ?? 1);
    $max = $license['max_locations_override'] !== null ? intval($license['max_locations_override']) : $maxDefault;
    if ($max < 0) {
        return true;
    }

    $count = intval($db->fetch(
        "SELECT COUNT(*) as total FROM barbershops WHERE license_id = ?",
        [$licenseId]
    )['total'] ?? 0);

    if ($count >= $max) {
        $message = 'La licencia ' . ucfirst($license['type']) . ' permite hasta ' . $max . ' sucursal(es).';
        return false;
    }

    return true;
}

/**
 * Log de errores personalizado
 */
function logError($message, $context = []) {
    $logFile = BASE_PATH . '/logs/' . date('Y-m-d') . '.log';
    $logMessage = date('Y-m-d H:i:s') . ' - ' . $message;
    
    if (!empty($context)) {
        $logMessage .= ' - Context: ' . json_encode($context);
    }
    
    if (!is_dir(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    
    file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);
}

/**
 * Truncar texto
 */
function truncate($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . $suffix;
}

/**
 * Asset URL
 */
function asset($path) {
    if (!$path) {
        return BASE_URL . '/assets/';
    }

    // Si ya es URL absoluta, devolverla tal cual.
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }

    $normalized = ltrim($path, '/');

    // Soporte para archivos subidos por uploadImage() en /public/uploads.
    if (strpos($normalized, 'barbershops/') === 0 || strpos($normalized, 'barbers/') === 0) {
        return BASE_URL . '/public/uploads/' . $normalized;
    }

    if (file_exists(BASE_PATH . '/public/uploads/' . $normalized)) {
        return BASE_URL . '/public/uploads/' . $normalized;
    }

    // Compatibilidad para rutas que ya incluyan el prefijo uploads/.
    if (strpos($normalized, 'uploads/') === 0) {
        $uploadsRelative = substr($normalized, strlen('uploads/'));
        if (strpos($uploadsRelative, 'barbershops/') === 0 || strpos($uploadsRelative, 'barbers/') === 0) {
            return BASE_URL . '/public/uploads/' . $uploadsRelative;
        }
        if (file_exists(BASE_PATH . '/public/uploads/' . $uploadsRelative)) {
            return BASE_URL . '/public/uploads/' . $uploadsRelative;
        }
    }

    // Compatibilidad para rutas ya persistidas con prefijo public/uploads/.
    if (strpos($normalized, 'public/uploads/') === 0) {
        return BASE_URL . '/' . $normalized;
    }

    // Assets estáticos tradicionales.
    if (file_exists(BASE_PATH . '/assets/' . $normalized)) {
        return BASE_URL . '/assets/' . $normalized;
    }

    // Fallback para archivos públicos directos.
    if (file_exists(BASE_PATH . '/public/' . $normalized)) {
        return BASE_URL . '/public/' . $normalized;
    }

    return BASE_URL . '/assets/' . $normalized;
}

/**
 * URL completa
 */
function url($path = '') {
    return BASE_URL . '/' . ltrim($path, '/');
}

/**
 * Obtener IP del cliente
 */
function getClientIp() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    return $_SERVER['REMOTE_ADDR'] ?? '';
}

/**
 * Tiempo transcurrido (hace cuánto)
 */
function timeAgo($datetime) {
    $timestamp = is_numeric($datetime) ? $datetime : strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return 'Hace ' . $diff . ' segundos';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return 'Hace ' . $mins . ($mins == 1 ? ' minuto' : ' minutos');
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return 'Hace ' . $hours . ($hours == 1 ? ' hora' : ' horas');
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return 'Hace ' . $days . ($days == 1 ? ' día' : ' días');
    } elseif ($diff < 2592000) {
        $weeks = floor($diff / 604800);
        return 'Hace ' . $weeks . ($weeks == 1 ? ' semana' : ' semanas');
    } elseif ($diff < 31536000) {
        $months = floor($diff / 2592000);
        return 'Hace ' . $months . ($months == 1 ? ' mes' : ' meses');
    } else {
        $years = floor($diff / 31536000);
        return 'Hace ' . $years . ($years == 1 ? ' año' : ' años');
    }
}

/**
 * Convertir número de día a nombre en español
 */
function dayNumberToName($number) {
    $days = [
        0 => 'Domingo',
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado'
    ];
    return $days[$number] ?? '';
}

/**
 * Validar fecha en formato específico
 */
function isValidDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Subir imagen con validaciones completas
 * 
 * @param array $file Array de archivo ($_FILES['campo'])
 * @param string $directory Subdirectorio dentro de /uploads/ (ej: 'barbershops', 'barbers')
 * @param array $options Opciones adicionales:
 *   - maxSize: Tamaño máximo en bytes (default: 5MB)
 *   - allowedTypes: Tipos MIME permitidos
 *   - maxWidth: Ancho máximo en pixels (default: 2000)
 *   - maxHeight: Alto máximo en pixels (default: 2000)
 *   - forceSquare: Recorte cuadrado centrado (default: false)
 *   - squareSize: Tamaño final cuadrado en pixels (default: 900)
 *   - oldFile: Ruta del archivo anterior para eliminarlo
 * @return array ['success' => bool, 'path' => string|null, 'message' => string|null]
 */
function uploadImage($file, $directory, $options = []) {
    // Valores por defecto
    $defaults = [
        'maxSize' => 5 * 1024 * 1024, // 5MB
        'allowedTypes' => ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'],
        'maxWidth' => 2000,
        'maxHeight' => 2000,
        'forceSquare' => false,
        'squareSize' => 900,
        'oldFile' => null
    ];
    
    $options = array_merge($defaults, $options);
    
    // Validar que el archivo existe
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'path' => null, 'message' => 'Error en el archivo'];
    }
    
    // Validar errores de upload
    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            return ['success' => false, 'path' => null, 'message' => 'No se seleccionó ningún archivo'];
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return ['success' => false, 'path' => null, 'message' => 'El archivo es demasiado grande'];
        default:
            return ['success' => false, 'path' => null, 'message' => 'Error desconocido al subir archivo'];
    }
    
    // Validar tamaño
    if ($file['size'] > $options['maxSize']) {
        $maxMB = round($options['maxSize'] / (1024 * 1024), 1);
        return ['success' => false, 'path' => null, 'message' => "El archivo excede el tamaño máximo de {$maxMB}MB"];
    }
    
    // Validar tipo MIME
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    
    if (!in_array($mimeType, $options['allowedTypes'])) {
        return ['success' => false, 'path' => null, 'message' => 'Tipo de archivo no permitido. Solo imágenes JPG, PNG, GIF o WebP'];
    }
    
    // Validar que sea una imagen real
    $imageInfo = getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        return ['success' => false, 'path' => null, 'message' => 'El archivo no es una imagen válida'];
    }
    
    // Validar dimensiones
    list($width, $height) = $imageInfo;
    if ($width > $options['maxWidth'] || $height > $options['maxHeight']) {
        return ['success' => false, 'path' => null, 'message' => "Las dimensiones máximas son {$options['maxWidth']}x{$options['maxHeight']} pixels"];
    }
    
    // Determinar extensión
    $extension = '';
    switch ($mimeType) {
        case 'image/jpeg':
        case 'image/jpg':
            $extension = 'jpg';
            break;
        case 'image/png':
            $extension = 'png';
            break;
        case 'image/gif':
            $extension = 'gif';
            break;
        case 'image/webp':
            $extension = 'webp';
            break;
    }
    
    // Generar nombre único
    $fileName = uniqid('img_', true) . '_' . time() . '.' . $extension;
    
    // Crear directorio si no existe
    $uploadDir = BASE_PATH . '/public/uploads/' . $directory;
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $uploadPath = $uploadDir . '/' . $fileName;
    
    if (!empty($options['forceSquare'])) {
        $canCropSquare = function_exists('imagecreatetruecolor') && function_exists('imagecopyresampled');

        if (!$canCropSquare) {
            // Si GD no está disponible en producción, guarda el archivo sin recorte.
            if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
                return ['success' => false, 'path' => null, 'message' => 'Error al guardar el archivo'];
            }
        } else {
        $sourceImage = null;
        switch ($mimeType) {
            case 'image/jpeg':
            case 'image/jpg':
                $sourceImage = @imagecreatefromjpeg($file['tmp_name']);
                break;
            case 'image/png':
                $sourceImage = @imagecreatefrompng($file['tmp_name']);
                break;
            case 'image/gif':
                $sourceImage = @imagecreatefromgif($file['tmp_name']);
                break;
            case 'image/webp':
                if (function_exists('imagecreatefromwebp')) {
                    $sourceImage = @imagecreatefromwebp($file['tmp_name']);
                }
                break;
        }

        if (!$sourceImage) {
            // Fallback seguro: guardar sin recorte si no se puede abrir con GD.
            if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
                return ['success' => false, 'path' => null, 'message' => 'Error al guardar el archivo'];
            }
        } else {

        $srcWidth = imagesx($sourceImage);
        $srcHeight = imagesy($sourceImage);
        $cropSide = min($srcWidth, $srcHeight);
        $srcX = (int)(($srcWidth - $cropSide) / 2);
        $srcY = (int)(($srcHeight - $cropSide) / 2);

        $targetSize = intval($options['squareSize']);
        if ($targetSize < 100) {
            $targetSize = 900;
        }

        $destinationImage = imagecreatetruecolor($targetSize, $targetSize);

        if ($mimeType === 'image/png' || $mimeType === 'image/webp') {
            imagealphablending($destinationImage, false);
            imagesavealpha($destinationImage, true);
            $transparent = imagecolorallocatealpha($destinationImage, 0, 0, 0, 127);
            imagefilledrectangle($destinationImage, 0, 0, $targetSize, $targetSize, $transparent);
        }

        imagecopyresampled(
            $destinationImage,
            $sourceImage,
            0,
            0,
            $srcX,
            $srcY,
            $targetSize,
            $targetSize,
            $cropSide,
            $cropSide
        );

        $saveOk = false;
        switch ($mimeType) {
            case 'image/jpeg':
            case 'image/jpg':
                $saveOk = imagejpeg($destinationImage, $uploadPath, 90);
                break;
            case 'image/png':
                $saveOk = imagepng($destinationImage, $uploadPath, 6);
                break;
            case 'image/gif':
                $saveOk = imagegif($destinationImage, $uploadPath);
                break;
            case 'image/webp':
                if (function_exists('imagewebp')) {
                    $saveOk = imagewebp($destinationImage, $uploadPath, 90);
                }
                break;
        }

            imagedestroy($sourceImage);
            imagedestroy($destinationImage);

            if (!$saveOk) {
                return ['success' => false, 'path' => null, 'message' => 'Error al guardar la imagen procesada'];
            }
        }
        }
    } else {
        // Mover archivo sin procesar
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return ['success' => false, 'path' => null, 'message' => 'Error al guardar el archivo'];
        }
    }
    
    // Eliminar archivo anterior si existe
    if ($options['oldFile'] && file_exists(BASE_PATH . '/public/uploads/' . $options['oldFile'])) {
        @unlink(BASE_PATH . '/public/uploads/' . $options['oldFile']);
    }
    
    // Retornar ruta relativa desde /uploads/
    $relativePath = $directory . '/' . $fileName;
    
    return ['success' => true, 'path' => $relativePath, 'message' => 'Imagen subida exitosamente'];
}

/**
 * Eliminar imagen del servidor
 * 
 * @param string $path Ruta relativa desde /uploads/
 * @return bool
 */
function deleteImage($path) {
    if (!$path) {
        return false;
    }
    
    $fullPath = BASE_PATH . '/public/uploads/' . $path;
    
    if (file_exists($fullPath)) {
        return @unlink($fullPath);
    }
    
    return false;
}

/**
 * Obtener URL de imagen
 * 
 * @param string|null $path Ruta relativa desde /uploads/
 * @param string $default Imagen por defecto si no existe
 * @return string URL completa de la imagen
 */
function imageUrl($path, $default = 'default-avatar.png') {
    if (!$path) {
        return BASE_URL . '/assets/images/' . $default;
    }

    $normalized = ltrim($path, '/');

    // Compatibilidad: en este proyecto los uploads están en /public/uploads.
    // Si en algún entorno existe /uploads en raíz, se respeta también.
    $rootUploadsDir = BASE_PATH . '/uploads';
    if (is_dir($rootUploadsDir)) {
        return BASE_URL . '/uploads/' . $normalized;
    }

    return BASE_URL . '/public/uploads/' . $normalized;
}
