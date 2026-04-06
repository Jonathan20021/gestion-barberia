<?php
/**
 * API Endpoint - Información de Barbería
 * GET /api/barbershop.php?slug=estilo-rd
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/config.php';
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Helpers.php';

try {
    $db = Database::getInstance();
    $slug = input('slug');
    
    if (empty($slug)) {
        jsonResponse(['success' => false, 'message' => 'Slug requerido'], 400);
    }
    
    // Obtener información de la barbería
    $barbershop = $db->fetch("
        SELECT 
            b.*,
            l.type as license_type,
            l.status as license_status,
            COUNT(DISTINCT br.id) as total_barbers,
            COUNT(DISTINCT s.id) as total_services,
            COALESCE(AVG(r.rating), 0) as avg_rating,
            COUNT(DISTINCT r.id) as total_reviews
        FROM barbershops b
        LEFT JOIN licenses l ON b.license_id = l.id
        LEFT JOIN barbers br ON b.id = br.barbershop_id AND br.status = 'active'
        LEFT JOIN services s ON b.id = s.barbershop_id AND s.is_active = TRUE
        LEFT JOIN reviews r ON b.id = r.barbershop_id AND r.is_visible = TRUE
        WHERE b.slug = ? AND b.status = 'active'
        GROUP BY b.id
    ", [$slug]);
    
    if (!$barbershop) {
        jsonResponse(['success' => false, 'message' => 'Barbería no encontrada'], 404);
    }
    
    // Verificar licencia activa
    if (!isLicenseActive($barbershop['license_id'])) {
        jsonResponse(['success' => false, 'message' => 'Barbería no disponible'], 403);
    }
    
    // Obtener servicios
    $services = $db->fetchAll("
        SELECT id, name, description, duration, price, category, image
        FROM services
        WHERE barbershop_id = ? AND is_active = TRUE
        ORDER BY display_order ASC, name ASC
    ", [$barbershop['id']]);
    
    // Obtener barberos
    $barbers = $db->fetchAll("
        SELECT 
            b.id,
            b.slug,
            b.specialty,
            b.bio,
            b.experience_years,
            b.photo,
            b.rating,
            b.total_reviews,
            b.is_featured,
            u.full_name,
            u.phone
        FROM barbers b
        JOIN users u ON b.user_id = u.id
        WHERE b.barbershop_id = ? AND b.status = 'active'
        ORDER BY b.is_featured DESC, b.rating DESC
    ", [$barbershop['id']]);
    
    // Obtener horarios
    $schedules = $db->fetchAll("
        SELECT day_of_week, open_time, close_time, is_closed
        FROM barbershop_schedules
        WHERE barbershop_id = ?
        ORDER BY day_of_week ASC
    ", [$barbershop['id']]);
    
    // Formatear horarios
    $formattedSchedules = [];
    foreach ($schedules as $schedule) {
        $formattedSchedules[] = [
            'day' => getDayName($schedule['day_of_week']),
            'day_number' => $schedule['day_of_week'],
            'open_time' => $schedule['is_closed'] ? null : date('g:i A', strtotime($schedule['open_time'])),
            'close_time' => $schedule['is_closed'] ? null : date('g:i A', strtotime($schedule['close_time'])),
            'is_closed' => (bool)$schedule['is_closed']
        ];
    }
    
    // Obtener reseñas recientes
    $reviews = $db->fetchAll("
        SELECT 
            r.rating,
            r.comment,
            r.created_at,
            c.name as client_name
        FROM reviews r
        LEFT JOIN clients c ON r.client_id = c.id
        WHERE r.barbershop_id = ? AND r.is_visible = TRUE
        ORDER BY r.created_at DESC
        LIMIT 10
    ", [$barbershop['id']]);
    
    // Construir respuesta
    jsonResponse([
        'success' => true,
        'data' => [
            'barbershop' => [
                'id' => $barbershop['id'],
                'name' => $barbershop['business_name'],
                'slug' => $barbershop['slug'],
                'description' => $barbershop['description'],
                'logo' => $barbershop['logo'] ? asset($barbershop['logo']) : null,
                'cover_image' => $barbershop['cover_image'] ? asset($barbershop['cover_image']) : null,
                'phone' => $barbershop['phone'],
                'email' => $barbershop['email'],
                'address' => $barbershop['address'],
                'city' => $barbershop['city'],
                'province' => $barbershop['province'],
                'website' => $barbershop['website'],
                'social_media' => [
                    'facebook' => $barbershop['facebook'],
                    'instagram' => $barbershop['instagram'],
                    'tiktok' => $barbershop['tiktok'],
                    'whatsapp' => $barbershop['whatsapp']
                ],
                'theme_color' => $barbershop['theme_color'],
                'stats' => [
                    'total_barbers' => (int)$barbershop['total_barbers'],
                    'total_services' => (int)$barbershop['total_services'],
                    'avg_rating' => round($barbershop['avg_rating'], 1),
                    'total_reviews' => (int)$barbershop['total_reviews']
                ]
            ],
            'services' => $services,
            'barbers' => $barbers,
            'schedules' => $formattedSchedules,
            'reviews' => $reviews
        ]
    ]);
    
} catch (Exception $e) {
    jsonResponse([
        'success' => false,
        'message' => 'Error al obtener información',
        'error' => ENVIRONMENT === 'development' ? $e->getMessage() : null
    ], 500);
}
