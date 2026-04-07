-- ====================================================
-- Kyros Barber Cloud - Base de Datos Multi-Tenant
-- Sistema de Gestión de Barberías - República Dominicana
-- ====================================================

CREATE DATABASE IF NOT EXISTS neetjbte_barbersass CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE neetjbte_barbersass;

-- Tabla de Usuarios (Multi-rol)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role ENUM('superadmin', 'owner', 'barber', 'client') NOT NULL,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    avatar VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Tabla de Licencias/Suscripciones
CREATE TABLE licenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    license_key VARCHAR(64) UNIQUE NOT NULL,
    type ENUM('basic', 'professional', 'enterprise') NOT NULL,
    status ENUM('trial', 'active', 'suspended', 'expired', 'cancelled') DEFAULT 'trial',
    price DECIMAL(10, 2) NOT NULL,
    billing_cycle ENUM('monthly', 'quarterly', 'yearly') DEFAULT 'monthly',
    trial_days INT DEFAULT 15,
    trial_start_date DATE NULL,
    trial_end_date DATE NULL,
    activated_at DATETIME NULL,
    max_locations_override INT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    auto_renew BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_license_key (license_key),
    INDEX idx_status (status),
    INDEX idx_end_date (end_date)
) ENGINE=InnoDB;

-- Tabla de Barberías (Tenants)
CREATE TABLE barbershops (
    id INT AUTO_INCREMENT PRIMARY KEY,
    license_id INT NOT NULL,
    owner_id INT NOT NULL,
    business_name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    logo VARCHAR(255),
    cover_image VARCHAR(255),
    phone VARCHAR(20),
    email VARCHAR(255),
    address TEXT,
    city VARCHAR(100),
    province VARCHAR(100),
    country VARCHAR(100) DEFAULT 'República Dominicana',
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    rnc VARCHAR(20),
    website VARCHAR(255),
    facebook VARCHAR(255),
    instagram VARCHAR(255),
    tiktok VARCHAR(255),
    whatsapp VARCHAR(20),
    theme_color VARCHAR(7) DEFAULT '#1e40af',
    allow_online_booking BOOLEAN DEFAULT TRUE,
    advance_booking_days INT DEFAULT 30,
    cancellation_hours INT DEFAULT 24,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (license_id) REFERENCES licenses(id) ON DELETE RESTRICT,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_city (city)
) ENGINE=InnoDB;

-- Tabla de Barberos
CREATE TABLE barbers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    barbershop_id INT NOT NULL,
    user_id INT NOT NULL,
    slug VARCHAR(255) NOT NULL,
    specialty TEXT,
    bio TEXT,
    experience_years INT,
    photo VARCHAR(255),
    commission_rate DECIMAL(5, 2) DEFAULT 50.00,
    rating DECIMAL(3, 2) DEFAULT 5.00,
    total_reviews INT DEFAULT 0,
    is_featured BOOLEAN DEFAULT FALSE,
    status ENUM('active', 'inactive', 'vacation') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (barbershop_id) REFERENCES barbershops(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_barber_shop (barbershop_id, user_id),
    UNIQUE KEY unique_barber_slug (barbershop_id, slug),
    INDEX idx_status (status),
    INDEX idx_rating (rating)
) ENGINE=InnoDB;

-- Tabla de Servicios
CREATE TABLE services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    barbershop_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    duration INT NOT NULL COMMENT 'Duración en minutos',
    price DECIMAL(10, 2) NOT NULL,
    image VARCHAR(255),
    category VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (barbershop_id) REFERENCES barbershops(id) ON DELETE CASCADE,
    INDEX idx_barbershop (barbershop_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB;

-- Tabla de Servicios por Barbero
CREATE TABLE barber_services (
    barber_id INT NOT NULL,
    service_id INT NOT NULL,
    custom_price DECIMAL(10, 2),
    custom_duration INT,
    PRIMARY KEY (barber_id, service_id),
    FOREIGN KEY (barber_id) REFERENCES barbers(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabla de Horarios de Barbería
CREATE TABLE barbershop_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    barbershop_id INT NOT NULL,
    day_of_week TINYINT NOT NULL COMMENT '0=Domingo, 6=Sábado',
    open_time TIME NOT NULL,
    close_time TIME NOT NULL,
    is_closed BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (barbershop_id) REFERENCES barbershops(id) ON DELETE CASCADE,
    UNIQUE KEY unique_day (barbershop_id, day_of_week)
) ENGINE=InnoDB;

-- Tabla de Horarios de Barberos
CREATE TABLE barber_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    barber_id INT NOT NULL,
    day_of_week TINYINT NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_available BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (barber_id) REFERENCES barbers(id) ON DELETE CASCADE,
    INDEX idx_barber_day (barber_id, day_of_week)
) ENGINE=InnoDB;

-- Tabla de Días No Laborables / Vacaciones
CREATE TABLE time_off (
    id INT AUTO_INCREMENT PRIMARY KEY,
    barber_id INT,
    barbershop_id INT,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    reason VARCHAR(255),
    type ENUM('vacation', 'sick', 'holiday', 'other') DEFAULT 'vacation',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (barber_id) REFERENCES barbers(id) ON DELETE CASCADE,
    FOREIGN KEY (barbershop_id) REFERENCES barbershops(id) ON DELETE CASCADE,
    CHECK (barber_id IS NOT NULL OR barbershop_id IS NOT NULL)
) ENGINE=InnoDB;

-- Tabla de Clientes
CREATE TABLE clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    barbershop_id INT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(20),
    birth_date DATE,
    notes TEXT,
    total_visits INT DEFAULT 0,
    total_spent DECIMAL(10, 2) DEFAULT 0,
    last_visit TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (barbershop_id) REFERENCES barbershops(id) ON DELETE CASCADE,
    INDEX idx_phone (phone),
    INDEX idx_email (email),
    INDEX idx_barbershop (barbershop_id)
) ENGINE=InnoDB;

-- Tabla de Reservas/Citas
CREATE TABLE appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    barbershop_id INT NOT NULL,
    barber_id INT NOT NULL,
    client_id INT,
    service_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    status ENUM('pending', 'confirmed', 'in_progress', 'completed', 'cancelled', 'no_show') DEFAULT 'pending',
    client_name VARCHAR(255) NOT NULL,
    client_phone VARCHAR(20) NOT NULL,
    client_email VARCHAR(255),
    notes TEXT,
    price DECIMAL(10, 2) NOT NULL,
    payment_status ENUM('pending', 'paid', 'partial', 'refunded') DEFAULT 'pending',
    payment_method ENUM('cash', 'card', 'transfer', 'online') DEFAULT 'cash',
    confirmation_code VARCHAR(20) UNIQUE,
    cancelled_at TIMESTAMP NULL,
    cancellation_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (barbershop_id) REFERENCES barbershops(id) ON DELETE CASCADE,
    FOREIGN KEY (barber_id) REFERENCES barbers(id) ON DELETE RESTRICT,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE RESTRICT,
    INDEX idx_date (appointment_date),
    INDEX idx_barber_date (barber_id, appointment_date),
    INDEX idx_status (status),
    INDEX idx_confirmation (confirmation_code)
) ENGINE=InnoDB;

-- Tabla de Transacciones/Finanzas
CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    barbershop_id INT NOT NULL,
    license_id INT,
    appointment_id INT,
    type ENUM('income', 'expense', 'license_payment', 'commission') NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    description TEXT,
    category VARCHAR(100),
    payment_method ENUM('cash', 'card', 'transfer', 'online') DEFAULT 'cash',
    reference_number VARCHAR(100),
    created_by INT,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (barbershop_id) REFERENCES barbershops(id) ON DELETE CASCADE,
    FOREIGN KEY (license_id) REFERENCES licenses(id) ON DELETE SET NULL,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_barbershop_date (barbershop_id, transaction_date),
    INDEX idx_type (type)
) ENGINE=InnoDB;

-- Tabla de Notificaciones
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_unread (user_id, is_read),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- Tabla de Reseñas
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    barbershop_id INT NOT NULL,
    barber_id INT,
    client_id INT,
    appointment_id INT,
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    is_verified BOOLEAN DEFAULT FALSE,
    is_visible BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (barbershop_id) REFERENCES barbershops(id) ON DELETE CASCADE,
    FOREIGN KEY (barber_id) REFERENCES barbers(id) ON DELETE SET NULL,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL,
    INDEX idx_barbershop (barbershop_id),
    INDEX idx_barber (barber_id)
) ENGINE=InnoDB;

-- Tabla de Configuración por Barbería
CREATE TABLE barbershop_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    barbershop_id INT NOT NULL,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT,
    FOREIGN KEY (barbershop_id) REFERENCES barbershops(id) ON DELETE CASCADE,
    UNIQUE KEY unique_setting (barbershop_id, setting_key)
) ENGINE=InnoDB;

-- Tabla de Actividad/Logs
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    barbershop_id INT,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (barbershop_id) REFERENCES barbershops(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_barbershop (barbershop_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- ====================================================
-- Datos Iniciales
-- ====================================================

-- Crear Super Admin
INSERT INTO users (email, password, full_name, phone, role, status) 
VALUES ('admin@kyrosbarbercloud.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Administrador', '809-000-0000', 'superadmin', 'active');
-- Password: password123

-- Crear Licencia de Ejemplo
INSERT INTO licenses (license_key, type, status, price, billing_cycle, start_date, end_date) 
VALUES (
    SHA2(CONCAT('DEMO-', NOW()), 256),
    'professional',
    'active',
    3000.00,
    'monthly',
    CURDATE(),
    DATE_ADD(CURDATE(), INTERVAL 1 MONTH)
);

-- Crear Usuario Demo Owner
INSERT INTO users (email, password, full_name, phone, role, status) 
VALUES ('demo@barberia.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Demo Barberia Owner', '809-123-4567', 'owner', 'active');

-- Crear Barbería Demo
INSERT INTO barbershops (license_id, owner_id, business_name, slug, description, phone, email, address, city, province, theme_color) 
VALUES (
    1,
    2,
    'Barbería El Estilo RD',
    'estilo-rd',
    'La mejor barbería de Santo Domingo. Expertos en cortes modernos y clásicos.',
    '809-123-4567',
    'info@estilordbarberia.com',
    'Av. Winston Churchill #123, Plaza Comercial',
    'Santo Domingo',
    'Distrito Nacional',
    '#dc2626'
);

-- Horarios Barbería Demo (Lunes a Sábado)
INSERT INTO barbershop_schedules (barbershop_id, day_of_week, open_time, close_time, is_closed) VALUES
(1, 1, '09:00:00', '19:00:00', FALSE), -- Lunes
(1, 2, '09:00:00', '19:00:00', FALSE), -- Martes
(1, 3, '09:00:00', '19:00:00', FALSE), -- Miércoles
(1, 4, '09:00:00', '19:00:00', FALSE), -- Jueves
(1, 5, '09:00:00', '19:00:00', FALSE), -- Viernes
(1, 6, '09:00:00', '20:00:00', FALSE), -- Sábado
(1, 0, '10:00:00', '14:00:00', FALSE); -- Domingo

-- Servicios Demo
INSERT INTO services (barbershop_id, name, description, duration, price, category, display_order) VALUES
(1, 'Corte Clasico', 'Corte tradicional con maquina y tijera', 30, 250.00, 'Cortes', 1),
(1, 'Corte Moderno', 'Cortes modernos: fade, undercut, pompadour', 45, 400.00, 'Cortes', 2),
(1, 'Corte + Barba', 'Combo completo: corte de cabello y arreglo de barba', 60, 550.00, 'Combos', 3),
(1, 'Afeitado Clasico', 'Afeitado tradicional con navaja y toalla caliente', 30, 300.00, 'Barba', 4),
(1, 'Diseno de Barba', 'Diseno y perfilado de barba', 20, 200.00, 'Barba', 5),
(1, 'Tinte de Cabello', 'Aplicacion de tinte profesional', 90, 800.00, 'Coloracion', 6),
(1, 'Tratamiento Capilar', 'Tratamiento hidratante y reparador', 45, 600.00, 'Tratamientos', 7),
(1, 'Corte Nino', 'Corte para ninos hasta 12 anos', 25, 200.00, 'Cortes', 8);

-- Crear Usuario Barbero Demo
INSERT INTO users (email, password, full_name, phone, role, status) 
VALUES ('barbero@demo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Carlos Perez', '809-987-6543', 'barber', 'active');

-- Crear Barbero
INSERT INTO barbers (barbershop_id, user_id, slug, specialty, bio, experience_years, commission_rate, is_featured) 
VALUES (
    1,
    3,
    'carlos-perez',
    'Especialista en fades y diseños modernos',
    'Con más de 8 años de experiencia, especializado en cortes modernos y técnicas de fade. Certificado en barbería profesional.',
    8,
    60.00,
    TRUE
);

-- Horarios del Barbero (Lunes a Sábado)
INSERT INTO barber_schedules (barber_id, day_of_week, start_time, end_time, is_available) VALUES
(1, 1, '09:00:00', '18:00:00', TRUE),
(1, 2, '09:00:00', '18:00:00', TRUE),
(1, 3, '09:00:00', '18:00:00', TRUE),
(1, 4, '09:00:00', '18:00:00', TRUE),
(1, 5, '09:00:00', '18:00:00', TRUE),
(1, 6, '09:00:00', '19:00:00', TRUE);

-- Asignar servicios al barbero
INSERT INTO barber_services (barber_id, service_id) VALUES
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5), (1, 8);

-- ====================================================
-- Vistas Útiles
-- ====================================================

-- Vista de Estadísticas de Barbería
CREATE VIEW barbershop_stats AS
SELECT 
    b.id,
    b.business_name,
    COUNT(DISTINCT br.id) as total_barbers,
    COUNT(DISTINCT s.id) as total_services,
    COUNT(DISTINCT c.id) as total_clients,
    COUNT(DISTINCT a.id) as total_appointments,
    COALESCE(SUM(CASE WHEN a.payment_status = 'paid' THEN a.price ELSE 0 END), 0) as total_revenue,
    COALESCE(AVG(r.rating), 0) as avg_rating,
    COUNT(r.id) as total_reviews
FROM barbershops b
LEFT JOIN barbers br ON b.id = br.barbershop_id
LEFT JOIN services s ON b.id = s.barbershop_id
LEFT JOIN clients c ON b.id = c.barbershop_id
LEFT JOIN appointments a ON b.id = a.barbershop_id
LEFT JOIN reviews r ON b.id = r.barbershop_id
GROUP BY b.id, b.business_name;

-- Vista de Citas del Día
CREATE VIEW daily_appointments AS
SELECT 
    a.*,
    b.business_name as barbershop_name,
    u.full_name as barber_name,
    s.name as service_name,
    s.duration as service_duration
FROM appointments a
JOIN barbershops b ON a.barbershop_id = b.id
JOIN barbers br ON a.barber_id = br.id
JOIN users u ON br.user_id = u.id
JOIN services s ON a.service_id = s.id
WHERE a.appointment_date = CURDATE()
ORDER BY a.start_time;
