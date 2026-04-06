-- Soporte de periodo de prueba en licencias
-- Ejecutar una sola vez en ambientes existentes.

ALTER TABLE licenses
    MODIFY COLUMN status ENUM('trial', 'active', 'suspended', 'expired', 'cancelled') DEFAULT 'trial';

ALTER TABLE licenses
    ADD COLUMN trial_days INT DEFAULT 15 AFTER billing_cycle,
    ADD COLUMN trial_start_date DATE NULL AFTER trial_days,
    ADD COLUMN trial_end_date DATE NULL AFTER trial_start_date,
    ADD COLUMN activated_at DATETIME NULL AFTER trial_end_date;

-- Inicializa datos en licencias existentes que ya estaban activas.
UPDATE licenses
SET
    trial_days = COALESCE(trial_days, 15),
    trial_start_date = COALESCE(trial_start_date, start_date),
    trial_end_date = COALESCE(trial_end_date, DATE_ADD(start_date, INTERVAL COALESCE(trial_days, 15) DAY));
