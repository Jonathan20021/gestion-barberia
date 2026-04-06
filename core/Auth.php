<?php
/**
 * Clase Auth - Sistema de Autenticación
 */
class Auth {
    private $db;
    private $lastError = '';
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Login de usuario
     */
    public function login($email, $password) {
        $this->lastError = '';

        $user = $this->db->fetch(
            "SELECT * FROM users WHERE email = ? AND status = 'active'",
            [$email]
        );
        
        if ($user && password_verify($password, $user['password'])) {
            if (in_array($user['role'], ['owner', 'barber'], true)) {
                $licenseCheck = self::getUserLicenseAccessStatus($user['id'], $user['role']);
                if (!$licenseCheck['allowed']) {
                    $this->lastError = $licenseCheck['message'];
                    return false;
                }
            }

            $this->createSession($user);
            $this->updateLastLogin($user['id']);
            $this->logActivity($user['id'], 'login', 'Inicio de sesión exitoso');
            return true;
        }

        if (empty($this->lastError)) {
            $this->lastError = 'Credenciales incorrectas';
        }
        
        return false;
    }

    /**
     * Último mensaje de error del flujo de autenticación
     */
    public function getLastError() {
        return $this->lastError;
    }
    
    /**
     * Registro de nuevo usuario
     */
    public function register($data) {
        // Verificar si el email ya existe
        $existing = $this->db->fetch(
            "SELECT id FROM users WHERE email = ?",
            [$data['email']]
        );
        
        if ($existing) {
            return ['success' => false, 'message' => 'El email ya está registrado'];
        }
        
        // Hash de contraseña
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Insertar usuario
        try {
            $this->db->query(
                "INSERT INTO users (email, password, full_name, phone, role, status) 
                 VALUES (?, ?, ?, ?, ?, 'active')",
                [
                    $data['email'],
                    $hashedPassword,
                    $data['full_name'],
                    $data['phone'] ?? null,
                    $data['role'] ?? 'client'
                ]
            );
            
            $userId = $this->db->lastInsertId();
            $this->logActivity($userId, 'register', 'Nuevo registro de usuario');
            
            return ['success' => true, 'user_id' => $userId];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al registrar usuario'];
        }
    }
    
    /**
     * Crear sesión de usuario
     */
    private function createSession($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['logged_in'] = true;
        
        // Si es owner o barber, obtener barbershop_id
        if ($user['role'] === 'owner') {
            $shop = $this->db->fetch(
                "SELECT id, slug FROM barbershops WHERE owner_id = ? AND status = 'active' LIMIT 1",
                [$user['id']]
            );
            if ($shop) {
                $_SESSION['barbershop_id'] = $shop['id'];
                $_SESSION['barbershop_slug'] = $shop['slug'];
            }
        } elseif ($user['role'] === 'barber') {
            $barber = $this->db->fetch(
                "SELECT b.barbershop_id, b.id as barber_id, bs.slug 
                 FROM barbers b 
                 JOIN barbershops bs ON b.barbershop_id = bs.id 
                 WHERE b.user_id = ? AND b.status = 'active' LIMIT 1",
                [$user['id']]
            );
            if ($barber) {
                $_SESSION['barbershop_id'] = $barber['barbershop_id'];
                $_SESSION['barber_id'] = $barber['barber_id'];
                $_SESSION['barbershop_slug'] = $barber['slug'];
            }
        }
    }
    
    /**
     * Logout
     */
    public function logout() {
        session_destroy();
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
    
    /**
     * Verificar si está autenticado
     */
    public static function check() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    /**
     * Verificar rol
     */
    public static function hasRole($role) {
        return self::check() && $_SESSION['user_role'] === $role;
    }
    
    /**
     * Verificar múltiples roles
     */
    public static function hasAnyRole($roles) {
        if (!self::check()) return false;
        return in_array($_SESSION['user_role'], $roles);
    }
    
    /**
     * Obtener usuario actual
     */
    public static function user() {
        if (!self::check()) return null;
        
        return [
            'id' => $_SESSION['user_id'],
            'email' => $_SESSION['user_email'],
            'name' => $_SESSION['user_name'],
            'role' => $_SESSION['user_role'],
            'barbershop_id' => $_SESSION['barbershop_id'] ?? null,
            'barber_id' => $_SESSION['barber_id'] ?? null
        ];
    }
    
    /**
     * Requiere autenticación
     */
    public static function requireAuth() {
        if (!self::check()) {
            header('Location: ' . BASE_URL . '/auth/login.php');
            exit;
        }
    }
    
    /**
     * Requiere rol específico
     */
    public static function requireRole($role) {
        self::requireAuth();
        if (!self::hasRole($role)) {
            http_response_code(403);
            die('Acceso denegado');
        }

        if (in_array($role, ['owner', 'barber'], true)) {
            $licenseCheck = self::getUserLicenseAccessStatus($_SESSION['user_id'], $role);
            if (!$licenseCheck['allowed']) {
                self::logoutByPolicy($licenseCheck['message']);
            }
        }
    }

    /**
     * Cierre de sesión por política de licenciamiento
     */
    private static function logoutByPolicy($message) {
        session_unset();
        session_destroy();
        header('Location: ' . BASE_URL . '/auth/login.php?error=' . urlencode($message));
        exit;
    }

    /**
     * Validar acceso por estado de licencia para owner/barber
     */
    private static function getUserLicenseAccessStatus($userId, $role) {
        $db = Database::getInstance();

        if ($role === 'owner') {
            $license = $db->fetch(
                "SELECT l.id, l.status, l.end_date, l.trial_end_date
                 FROM barbershops b
                 INNER JOIN licenses l ON b.license_id = l.id
                 WHERE b.owner_id = ?
                 ORDER BY b.id ASC
                 LIMIT 1",
                [$userId]
            );
        } else {
            $license = $db->fetch(
                "SELECT l.id, l.status, l.end_date, l.trial_end_date
                 FROM barbers br
                 INNER JOIN barbershops b ON br.barbershop_id = b.id
                 INNER JOIN licenses l ON b.license_id = l.id
                 WHERE br.user_id = ?
                 ORDER BY br.id ASC
                 LIMIT 1",
                [$userId]
            );
        }

        if (!$license) {
            return ['allowed' => false, 'message' => 'No tienes una licencia asignada.'];
        }

        $today = date('Y-m-d');

        if ($license['status'] === 'trial') {
            if (empty($license['trial_end_date']) || $license['trial_end_date'] < $today) {
                $db->execute("UPDATE licenses SET status = 'expired' WHERE id = ?", [$license['id']]);
                return ['allowed' => false, 'message' => 'El periodo de prueba de 15 días ha finalizado. Contacta al Super Admin.'];
            }

            return ['allowed' => true, 'message' => ''];
        }

        if ($license['status'] !== 'active') {
            return ['allowed' => false, 'message' => 'Tu licencia no está activa. Contacta al Super Admin.'];
        }

        if (empty($license['end_date']) || $license['end_date'] < $today) {
            $db->execute("UPDATE licenses SET status = 'expired' WHERE id = ?", [$license['id']]);
            return ['allowed' => false, 'message' => 'Tu licencia está vencida. Contacta al Super Admin para renovarla.'];
        }

        return ['allowed' => true, 'message' => ''];
    }
    
    /**
     * Actualizar último login
     */
    private function updateLastLogin($userId) {
        $this->db->execute(
            "UPDATE users SET last_login = NOW() WHERE id = ?",
            [$userId]
        );
    }
    
    /**
     * Registrar actividad
     */
    private function logActivity($userId, $action, $description) {
        $this->db->execute(
            "INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent) 
             VALUES (?, ?, ?, ?, ?)",
            [
                $userId,
                $action,
                $description,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]
        );
    }
    
    /**
     * Cambiar contraseña
     */
    public function changePassword($userId, $oldPassword, $newPassword) {
        $user = $this->db->fetch("SELECT password FROM users WHERE id = ?", [$userId]);
        
        if (!$user || !password_verify($oldPassword, $user['password'])) {
            return ['success' => false, 'message' => 'Contraseña actual incorrecta'];
        }
        
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->db->execute(
            "UPDATE users SET password = ? WHERE id = ?",
            [$hashedPassword, $userId]
        );
        
        return ['success' => true, 'message' => 'Contraseña actualizada'];
    }
    
    /**
     * Resetear contraseña
     */
    public function resetPassword($email) {
        $user = $this->db->fetch("SELECT id FROM users WHERE email = ?", [$email]);
        
        if (!$user) {
            return ['success' => false, 'message' => 'Email no encontrado'];
        }
        
        // TODO: Implementar envío de email con token
        $token = bin2hex(random_bytes(32));
        
        return ['success' => true, 'message' => 'Instrucciones enviadas al email'];
    }
}
