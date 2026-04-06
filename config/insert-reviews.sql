-- Insertar Reseñas de Prueba para Barbería Demo

-- Primero, crear algunos clientes de prueba
INSERT INTO clients (barbershop_id, name, phone, email, total_visits, total_spent) VALUES
(1, 'Juan Martinez', '809-555-1234', 'juan@ejemplo.com', 5, 1250.00),
(1, 'Pedro Rodriguez', '809-555-5678', 'pedro@ejemplo.com', 3, 750.00),
(1, 'Luis Fernandez', '809-555-9012', 'luis@ejemplo.com', 8, 2000.00),
(1, 'Carlos Gomez', '809-555-3456', 'carlos@ejemplo.com', 2, 500.00),
(1, 'Miguel Santos', '809-555-7890', 'miguel@ejemplo.com', 6, 1500.00);

-- Insertar reseñas para la barbería (usando los IDs de los clientes que acabamos de crear)
INSERT INTO reviews (barbershop_id, barber_id, client_id, rating, comment, is_verified, is_visible) VALUES
(1, 1, (SELECT id FROM clients WHERE name = 'Juan Martinez' LIMIT 1), 5, 'Excelente servicio! Carlos es un barbero de primera. El corte quedo perfecto y el ambiente es muy profesional.', TRUE, TRUE),
(1, 1, (SELECT id FROM clients WHERE name = 'Pedro Rodriguez' LIMIT 1), 5, 'La mejor barberia de Santo Domingo. Siempre salgo satisfecho con mi corte. Totalmente recomendado!', TRUE, TRUE),
(1, 1, (SELECT id FROM clients WHERE name = 'Luis Fernandez' LIMIT 1), 4, 'Muy buen servicio y atencion. Los precios son justos y la calidad es excelente. Volvere pronto.', TRUE, TRUE),
(1, 1, (SELECT id FROM clients WHERE name = 'Carlos Gomez' LIMIT 1), 5, 'Increible experiencia! El barbero tiene mucha habilidad y el local esta impecable. 100% recomendado.', TRUE, TRUE),
(1, 1, (SELECT id FROM clients WHERE name = 'Miguel Santos' LIMIT 1), 5, 'Llevo mas de 6 meses viniendo aqui y nunca me han decepcionado. Carlos es un maestro en su oficio.', TRUE, TRUE),
(1, NULL, (SELECT id FROM clients WHERE name = 'Juan Martinez' LIMIT 1), 5, 'El mejor lugar para cortarse el pelo. Ambiente agradable, precios accesibles y resultados profesionales.', TRUE, TRUE);

-- Actualizar el rating del barbero basado en las reseñas
UPDATE barbers 
SET rating = (
    SELECT COALESCE(AVG(rating), 5.0)
    FROM reviews 
    WHERE barber_id = 1 AND is_verified = TRUE
),
total_reviews = (
    SELECT COUNT(*) 
    FROM reviews 
    WHERE barber_id = 1 AND is_verified = TRUE
)
WHERE id = 1;
