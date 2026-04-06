-- Soporte para configurar sucursales máximas por licencia desde superadmin
-- Ejecutar una sola vez en ambientes existentes.

ALTER TABLE licenses
    ADD COLUMN max_locations_override INT NULL AFTER activated_at;
