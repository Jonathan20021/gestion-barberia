-- Tabla de modulos por tipo de licencia
CREATE TABLE IF NOT EXISTS license_type_modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    license_type ENUM('basic', 'professional', 'enterprise') NOT NULL,
    module_key VARCHAR(100) NOT NULL,
    is_enabled TINYINT(1) NOT NULL DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_license_module (license_type, module_key),
    INDEX idx_module (module_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Configuracion inicial sugerida para modulo financiero
INSERT INTO license_type_modules (license_type, module_key, is_enabled)
VALUES
('basic', 'finanzas_avanzadas', 0),
('professional', 'finanzas_avanzadas', 1),
('enterprise', 'finanzas_avanzadas', 1)
ON DUPLICATE KEY UPDATE is_enabled = VALUES(is_enabled);
