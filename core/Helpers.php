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
    $db = Database::getInstance();
    $license = $db->fetch(
        "SELECT status, end_date FROM licenses WHERE id = ?",
        [$licenseId]
    );
    
    if (!$license) return false;
    
    return $license['status'] === 'active' && strtotime($license['end_date']) >= time();
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
    return BASE_URL . '/assets/' . ltrim($path, '/');
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
    
    // Mover archivo
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return ['success' => false, 'path' => null, 'message' => 'Error al guardar el archivo'];
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
    
    return BASE_URL . '/uploads/' . ltrim($path, '/');
}
