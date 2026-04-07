<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Helpers.php';

// Obtener slug de la barbería
$slug = $_GET['shop'] ?? '';

if (empty($slug)) {
    die('Barbería no especificada');
}

$db = Database::getInstance();

// Obtener información de la barbería
$barbershop = $db->fetch("
    SELECT b.*, l.type as license_type, l.status as license_status
    FROM barbershops b
    JOIN licenses l ON b.license_id = l.id
    WHERE b.slug = ? AND b.status = 'active'
", [$slug]);

if (!$barbershop || !isLicenseActive($barbershop['license_id'])) {
    die('Barbería no disponible');
}

// Obtener barberos activos
$barbers = $db->fetchAll("
    SELECT b.*, u.full_name, u.phone
    FROM barbers b
    JOIN users u ON b.user_id = u.id
    WHERE b.barbershop_id = ? AND b.status = 'active'
    ORDER BY b.is_featured DESC, b.rating DESC
", [$barbershop['id']]);

// Obtener servicios activos
$services = $db->fetchAll("
    SELECT * FROM services
    WHERE barbershop_id = ? AND is_active = TRUE
    ORDER BY display_order ASC, name ASC
", [$barbershop['id']]);

// Agrupar servicios por categoría
$servicesByCategory = [];
foreach ($services as $service) {
    $category = $service['category'] ?? 'General';
    if (!isset($servicesByCategory[$category])) {
        $servicesByCategory[$category] = [];
    }
    $servicesByCategory[$category][] = $service;
}

// Obtener horarios de la barbería
$schedules = $db->fetchAll("
    SELECT * FROM barbershop_schedules
    WHERE barbershop_id = ?
    ORDER BY day_of_week ASC
", [$barbershop['id']]);

// Obtener reseñas
$reviews = $db->fetchAll("
    SELECT r.*, c.name as client_name
    FROM reviews r
    LEFT JOIN clients c ON r.client_id = c.id
    WHERE r.barbershop_id = ? AND r.is_visible = TRUE
    ORDER BY r.created_at DESC
    LIMIT 10
", [$barbershop['id']]);

$avgRating = $db->fetch("
    SELECT COALESCE(AVG(rating), 0) as avg_rating, COUNT(*) as total_reviews
    FROM reviews
    WHERE barbershop_id = ? AND is_visible = TRUE
", [$barbershop['id']]);

$title = $barbershop['business_name'] . ' - Reserva tu Cita';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($title); ?></title>
    <meta name="description" content="<?php echo e($barbershop['description']); ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .gradient-bg { background: linear-gradient(135deg, <?php echo $barbershop['theme_color']; ?> 0%, #1e3a8a 100%); }
    </style>
</head>
<body class="bg-gray-50">
    <div x-data="bookingApp()">
        <!-- Hero Section -->
        <div class="relative gradient-bg text-white overflow-hidden">
            <!-- Decorative Elements -->
            <div class="absolute top-0 right-0 w-96 h-96 bg-white/5 rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-72 h-72 bg-white/5 rounded-full blur-3xl"></div>
            
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
                <!-- Breadcrumb -->
                <nav class="mb-6 text-sm">
                    <a href="<?php echo BASE_URL; ?>" class="text-blue-200 hover:text-white transition">Inicio</a>
                    <span class="mx-2 text-blue-200">/</span>
                    <span class="text-white font-medium"><?php echo e($barbershop['business_name']); ?></span>
                </nav>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                    <div>
                        <!-- Logo y Título -->
                        <div class="flex items-center mb-6">
                            <?php if ($barbershop['logo']): ?>
                                <div class="relative">
                                    <div class="absolute inset-0 bg-white/20 rounded-full blur-xl"></div>
                                    <img src="<?php echo asset($barbershop['logo']); ?>" class="relative w-24 h-24 rounded-full border-4 border-white shadow-2xl" alt="Logo">
                                </div>
                            <?php endif; ?>
                            <div class="ml-5">
                                <div class="inline-block px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-xs font-semibold text-white mb-2">
                                    ✨ Barbería Premium
                                </div>
                                <h1 class="text-4xl md:text-6xl font-black tracking-tight"><?php echo e($barbershop['business_name']); ?></h1>
                                <div class="flex items-center mt-3">
                                    <div class="flex items-center text-yellow-300">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <svg class="w-5 h-5 <?php echo $i <= round($avgRating['avg_rating']) ? 'fill-current' : 'fill-gray-400'; ?>" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                        <?php endfor; ?>
                                        <span class="ml-2 text-white"><?php echo number_format($avgRating['avg_rating'], 1); ?> (<?php echo $avgRating['total_reviews']; ?> reseñas)</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <p class="text-xl text-blue-50 leading-relaxed mb-8"><?php echo e($barbershop['description']); ?></p>
                        
                        <!-- Stats Cards -->
                        <div class="grid grid-cols-3 gap-4 mb-8">
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                                <div class="text-3xl font-bold"><?php echo count($barbers); ?></div>
                                <div class="text-sm text-blue-100">Barberos</div>
                            </div>
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                                <div class="text-3xl font-bold"><?php echo count($services); ?>+</div>
                                <div class="text-sm text-blue-100">Servicios</div>
                            </div>
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                                <div class="text-3xl font-bold"><?php echo $avgRating['total_reviews']; ?></div>
                                <div class="text-sm text-blue-100">Reseñas</div>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
                            <div class="flex items-center">
                                <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <div>
                                    <p class="text-sm text-blue-100">Ubicación</p>
                                    <p class="font-semibold"><?php echo e($barbershop['city']); ?></p>
                                </div>
                            </div>
                            
                            <div class="flex items-center">
                                <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                                <div>
                                    <p class="text-sm text-blue-100">Teléfono</p>
                                    <p class="font-semibold"><?php echo e($barbershop['phone']); ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- CTA Buttons -->
                        <div class="flex flex-col sm:flex-row gap-4">
                            <button @click="showBookingModal = true" 
                                    class="flex-1 sm:flex-none px-8 py-4 bg-white text-indigo-600 rounded-xl font-bold text-lg shadow-2xl hover:shadow-white/50 transform hover:scale-105 transition-all duration-300 hover:-translate-y-1">
                                <span class="flex items-center justify-center">
                                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    Reservar Cita Ahora
                                </span>
                            </button>
                            
                            <?php if ($barbershop['phone']): ?>
                            <a href="https://wa.me/1<?php echo preg_replace('/[^0-9]/', '', $barbershop['phone']); ?>?text=Hola, quiero reservar una cita" 
                               target="_blank"
                               class="flex-1 sm:flex-none px-8 py-4 bg-green-500 hover:bg-green-600 text-white rounded-xl font-bold text-lg shadow-2xl transform hover:scale-105 transition-all duration-300 hover:-translate-y-1">
                                <span class="flex items-center justify-center">
                                    <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                                    </svg>
                                    WhatsApp
                                </span>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Cover Image -->
                    <?php if ($barbershop['cover_image']): ?>
                        <div class="hidden lg:block relative">
                            <div class="absolute -top-4 -right-4 w-full h-full bg-white/20 rounded-3xl"></div>
                            <img src="<?php echo asset($barbershop['cover_image']); ?>" 
                                 class="relative rounded-3xl shadow-2xl w-full h-[500px] object-cover border-4 border-white/30" 
                                 alt="Cover">
                            <!-- Floating Badge -->
                            <div class="absolute bottom-6 left-6 bg-white rounded-xl shadow-xl p-4">
                                <div class="flex items-center space-x-3">
                                    <div class="bg-green-500 w-3 h-3 rounded-full animate-pulse"></div>
                                    <div>
                                        <div class="text-sm font-semibold text-gray-900">Abierto Ahora</div>
                                        <div class="text-xs text-gray-500">Reserva disponible</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Features Section -->
        <div class="bg-white py-12 border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-indigo-100 text-indigo-600 rounded-full mb-4">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-gray-900 mb-1">Horario Flexible</h3>
                        <p class="text-sm text-gray-600">Abierto 7 días</p>
                    </div>
                    
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 text-green-600 rounded-full mb-4">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-gray-900 mb-1">Profesionales</h3>
                        <p class="text-sm text-gray-600">Barberos expertos</p>
                    </div>
                    
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-purple-100 text-purple-600 rounded-full mb-4">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-gray-900 mb-1">Precios Justos</h3>
                        <p class="text-sm text-gray-600">Sin cargos ocultos</p>
                    </div>
                    
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-yellow-100 text-yellow-600 rounded-full mb-4">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-gray-900 mb-1">Mejor Calificado</h3>
                        <p class="text-sm text-gray-600"><?php echo number_format($avgRating['avg_rating'], 1); ?> estrellas</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Servicios -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
            <div class="text-center mb-16">
                <span class="inline-block px-4 py-2 bg-indigo-100 text-indigo-600 rounded-full text-sm font-semibold mb-4">NUESTROS SERVICIOS</span>
                <h2 class="text-5xl font-black text-gray-900 mb-4">Servicios Premium</h2>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">Descubre nuestros servicios profesionales de barbería diseñados para hacerte lucir increíble</p>
            </div>

            <?php foreach ($servicesByCategory as $category => $categoryServices): ?>
                <div class="mb-12">
                    <h3 class="text-2xl font-bold text-gray-800 mb-6"><?php echo e($category); ?></h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($categoryServices as $service): ?>
                            <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-2xl transition transform hover:-translate-y-1">
                                <?php if ($service['image']): ?>
                                    <img src="<?php echo asset($service['image']); ?>" class="w-full h-48 object-cover" alt="<?php echo e($service['name']); ?>">
                                <?php else: ?>
                                    <div class="w-full h-48 bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                                        <svg class="w-20 h-20 text-white opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243 4.243 3 3 0 004.243-4.243zm0-5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z"/>
                                        </svg>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="p-6">
                                    <h4 class="text-xl font-bold text-gray-900 mb-2"><?php echo e($service['name']); ?></h4>
                                    <p class="text-gray-600 text-sm mb-4"><?php echo e($service['description']); ?></p>
                                    
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-2xl font-bold" style="color: <?php echo $barbershop['theme_color']; ?>">
                                                <?php echo formatPrice($service['price']); ?>
                                            </p>
                                            <p class="text-sm text-gray-500">⏱️ <?php echo $service['duration']; ?> min</p>
                                        </div>
                                        <button @click="selectService(<?php echo $service['id']; ?>)" 
                                                class="px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg font-medium hover:from-indigo-700 hover:to-purple-700 transition">
                                            Reservar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Barberos -->
        <div class="bg-gradient-to-b from-gray-50 to-white py-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <span class="inline-block px-4 py-2 bg-purple-100 text-purple-600 rounded-full text-sm font-semibold mb-4">NUESTRO EQUIPO</span>
                    <h2 class="text-5xl font-black text-gray-900 mb-4">Barberos Profesionales</h2>
                    <p class="text-xl text-gray-600 max-w-2xl mx-auto">Expertos en estilo y cuidado personal a tu servicio</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-<?php echo min(count($barbers), 3); ?> gap-10">
                    <?php foreach ($barbers as $barber): ?>
                        <div class="group relative bg-white rounded-3xl shadow-xl overflow-hidden hover:shadow-2xl transition-all duration-500 transform hover:-translate-y-2">
                            <div class="relative h-80 overflow-hidden">
                                  <img src="<?php echo $barber['photo'] ? imageUrl($barber['photo']) : getDefaultAvatar($barber['full_name']); ?>" 
                                     class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" 
                                     alt="<?php echo e($barber['full_name']); ?>">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>
                                
                                <?php if ($barber['is_featured']): ?>
                                    <div class="absolute top-4 right-4">
                                        <div class="bg-yellow-400 text-yellow-900 px-4 py-2 rounded-full text-sm font-bold shadow-lg flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                            Destacado
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Nombre en overlay -->
                                <div class="absolute bottom-4 left-4 right-4">
                                    <h3 class="text-2xl font-black text-white mb-1"><?php echo e($barber['full_name']); ?></h3>
                                    <?php if ($barber['specialty']): ?>
                                        <p class="text-sm text-white/90 font-medium"><?php echo e($barber['specialty']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-6">
                                    <div class="flex items-center space-x-1">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <svg class="w-5 h-5 <?php echo $i <= round($barber['rating']) ? 'text-yellow-400 fill-current' : 'text-gray-300 fill-current'; ?>" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="text-lg font-bold text-gray-900"><?php echo number_format($barber['rating'], 1); ?></span>
                                </div>

                                <button @click="selectBarber(<?php echo $barber['id']; ?>)" 
                                        class="w-full px-6 py-4 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-bold hover:from-indigo-700 hover:to-purple-700 transform group-hover:scale-105 transition-all duration-300 shadow-lg">
                                    <span class="flex items-center justify-center">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        Reservar con <?php echo explode(' ', $barber['full_name'])[0]; ?>
                                    </span
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <svg class="w-5 h-5 <?php echo $i <= round($barber['rating']) ? 'text-yellow-400 fill-current' : 'text-gray-300 fill-current'; ?>" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="text-lg font-bold text-gray-900"><?php echo number_format($barber['rating'], 1); ?></span>
                                </div>

                                <button @click="selectBarber(<?php echo $barber['id']; ?>)" 
                                        class="w-full px-6 py-4 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-bold hover:from-indigo-700 hover:to-purple-700 transform group-hover:scale-105 transition-all duration-300 shadow-lg">
                                    <span class="flex items-center justify-center">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        Reservar con <?php echo explode(' ', $barber['full_name'])[0]; ?>
                                    </span>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Reseñas -->
        <?php if (!empty($reviews)): ?>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <div class="text-center mb-12">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Lo Que Dicen Nuestros Clientes</h2>
                <p class="text-xl text-gray-600">Experiencias reales de clientes satisfechos</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach (array_slice($reviews, 0, 6) as $review): ?>
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <div class="flex items-center mb-4">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <svg class="w-5 h-5 <?php echo $i <= $review['rating'] ? 'text-yellow-400 fill-current' : 'text-gray-300 fill-current'; ?>" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            <?php endfor; ?>
                        </div>
                        <p class="text-gray-700 mb-4">"<?php echo e($review['comment']); ?>"</p>
                        <p class="text-sm font-semibold text-gray-900">- <?php echo e($review['client_name'] ?? 'Cliente'); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- CTA WhatsApp Floating -->
        <?php if ($barbershop['phone']): ?>
        <div class="bg-gradient-to-r from-green-500 to-green-600 py-16">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <div class="mb-6">
                    <div class="inline-flex items-center justify-center w-20 h-20 bg-white rounded-full shadow-2xl mb-4">
                        <svg class="w-12 h-12 text-green-500" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                        </svg>
                    </div>
                </div>
                <h2 class="text-4xl font-black text-white mb-4">¿Prefieres reservar por WhatsApp?</h2>
                <p class="text-xl text-green-50 mb-8">Contáctanos directamente y te atenderemos de inmediato</p>
                <a href="https://wa.me/1<?php echo preg_replace('/[^0-9]/', '', $barbershop['phone']); ?>?text=Hola, quiero reservar una cita en <?php echo urlencode($barbershop['business_name']); ?>" 
                   target="_blank"
                   class="inline-flex items-center px-10 py-5 bg-white text-green-600 rounded-2xl font-black text-xl shadow-2xl hover:shadow-white/50 transform hover:scale-110 transition-all duration-300">
                    <svg class="w-8 h-8 mr-3" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                    </svg>
                    Chatear en WhatsApp
                </a>
                <p class="mt-4 text-green-50 text-sm">Respuesta inmediata • Atención personalizada</p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <footer class="gradient-bg text-white py-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-12">
                    <!-- Info Barberia -->
                    <div class="md:col-span-2">
                        <?php if ($barbershop['logo']): ?>
                            <img src="<?php echo asset($barbershop['logo']); ?>" class="w-16 h-16 rounded-full border-2 border-white/30 mb-4" alt="Logo">
                        <?php endif; ?>
                        <h3 class="text-3xl font-black mb-4"><?php echo e($barbershop['business_name']); ?></h3>
                        <p class="text-blue-100 mb-4 max-w-md leading-relaxed"><?php echo e($barbershop['description']); ?></p>
                        
                        <!-- Contacto -->
                        <div class="space-y-3 mb-6">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 mr-3 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <div class="text-blue-100">
                                    <p><?php echo e($barbershop['address']); ?></p>
                                    <p><?php echo e($barbershop['city'] . ', ' . $barbershop['province']); ?></p>
                                </div>
                            </div>
                            
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                                <a href="tel:<?php echo e($barbershop['phone']); ?>" class="text-blue-100 hover:text-white transition"><?php echo e($barbershop['phone']); ?></a>
                            </div>
                            
                            <?php if ($barbershop['email']): ?>
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                <a href="mailto:<?php echo e($barbershop['email']); ?>" class="text-blue-100 hover:text-white transition"><?php echo e($barbershop['email']); ?></a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Horarios -->
                    <div>
                        <h4 class="text-xl font-bold mb-6 flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Horarios
                        </h4>
                        <div class="space-y-2">
                        <?php foreach ($schedules as $schedule): ?>
                            <div class="flex justify-between items-center text-sm py-1">
                                <span class="font-medium text-blue-100"><?php echo getDayName($schedule['day_of_week']); ?>:</span>
                                <span class="text-blue-200">
                                    <?php if ($schedule['is_closed']): ?>
                                        <span class="text-red-300">Cerrado</span>
                                    <?php else: ?>
                                        <?php echo date('g:i A', strtotime($schedule['open_time'])); ?> - <?php echo date('g:i A', strtotime($schedule['close_time'])); ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Redes Sociales -->
                    <div>
                        <h4 class="text-xl font-bold mb-6">Síguenos</h4>
                        <div class="space-y-4">
                            <?php if ($barbershop['phone']): ?>
                            <a href="https://wa.me/1<?php echo preg_replace('/[^0-9]/', '', $barbershop['phone']); ?>" target="_blank" 
                               class="flex items-center space-x-3 p-3 bg-white/10 rounded-xl hover:bg-white/20 transition">
                                <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                                    </svg>
                                </div>
                                <span class="font-medium">WhatsApp</span>
                            </a>
                            <?php endif; ?>
                            
                            <?php if ($barbershop['facebook']): ?>
                            <a href="<?php echo e($barbershop['facebook']); ?>" target="_blank" 
                               class="flex items-center space-x-3 p-3 bg-white/10 rounded-xl hover:bg-white/20 transition">
                                <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                </div>
                                <span class="font-medium">Facebook</span>
                            </a>
                            <?php endif; ?>
                            
                            <?php if ($barbershop['instagram']): ?>
                            <a href="<?php echo e($barbershop['instagram']); ?>" target="_blank" 
                               class="flex items-center space-x-3 p-3 bg-white/10 rounded-xl hover:bg-white/20 transition">
                                <div class="w-10 h-10 bg-pink-600 rounded-full flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                                </div>
                                <span class="font-medium">Instagram</span>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="pt-8 border-t border-white/20">
                    <div class="flex flex-col md:flex-row justify-between items-center text-blue-100 text-sm">
                        <p>&copy; 2026 <?php echo e($barbershop['business_name']); ?>. Todos los derechos reservados.</p>
                        <p class="mt-2 md:mt-0">Powered by <span class="font-bold text-white">Kyros Barber Cloud</span></p>
                    </div>
                </div>
            </div>
        </footer>

        <!-- Modal de Reserva -->
        <div x-show="showBookingModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-75" @click="showBookingModal = false"></div>

                <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4">
                        <h3 class="text-2xl font-bold text-white">Reservar Cita</h3>
                    </div>

                    <form action="book.php" method="POST" class="p-6 space-y-4">
                        <input type="hidden" name="barbershop_id" value="<?php echo $barbershop['id']; ?>">
                        
                        <!-- Paso 1: Seleccionar Servicio -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Servicio</label>
                            <select name="service_id" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Seleccionar servicio...</option>
                                <?php foreach ($services as $service): ?>
                                    <option value="<?php echo $service['id']; ?>" data-price="<?php echo $service['price']; ?>" data-duration="<?php echo $service['duration']; ?>">
                                        <?php echo e($service['name']); ?> - <?php echo formatPrice($service['price']); ?> (<?php echo $service['duration']; ?> min)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Paso 2: Seleccionar Barbero -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Barbero</label>
                            <select name="barber_id" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Seleccionar barbero...</option>
                                <?php foreach ($barbers as $barber): ?>
                                    <option value="<?php echo $barber['id']; ?>">
                                        <?php echo e($barber['full_name']); ?> ⭐ <?php echo number_format($barber['rating'], 1); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Paso 3: Fecha y Hora -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Fecha</label>
                                <input type="date" name="appointment_date" required min="<?php echo date('Y-m-d'); ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Hora</label>
                                <input type="time" name="start_time" required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                        </div>

                        <!-- Datos del Cliente -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nombre Completo</label>
                                <input type="text" name="client_name" required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Teléfono</label>
                                <input type="tel" name="client_phone" required placeholder="809-123-4567"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email (opcional)</label>
                            <input type="email" name="client_email"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Notas (opcional)</label>
                            <textarea name="notes" rows="3"
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                        </div>

                        <div class="flex space-x-4 pt-4">
                            <button type="button" @click="showBookingModal = false"
                                    class="flex-1 px-6 py-3 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50 transition">
                                Cancelar
                            </button>
                            <button type="submit"
                                    class="flex-1 px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg font-semibold hover:from-indigo-700 hover:to-purple-700 transition">
                                Confirmar Reserva
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function bookingApp() {
            return {
                showBookingModal: false,
                selectedService: null,
                selectedBarber: null,
                
                selectService(serviceId) {
                    this.selectedService = serviceId;
                    this.showBookingModal = true;
                },
                
                selectBarber(barberId) {
                    this.selectedBarber = barberId;
                    this.showBookingModal = true;
                }
            }
        }
    </script>
</body>
</html>
