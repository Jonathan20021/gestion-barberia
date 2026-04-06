<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Helpers.php';

// Obtener slug de la barberia
$slug = $_GET['shop'] ?? '';

if (empty($slug)) {
    die('Barberia no especificada');
}

$db = Database::getInstance();

// Obtener informacion de la barberia
$barbershop = $db->fetch("
    SELECT b.*, l.type as license_type, l.status as license_status
    FROM barbershops b
    JOIN licenses l ON b.license_id = l.id
    WHERE b.slug = ? AND b.status = 'active'
", [$slug]);

if (!$barbershop || !isLicenseActive($barbershop['license_id'])) {
    die('Barberia no disponible');
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

// Agrupar servicios por categoria
$servicesByCategory = [];
foreach ($services as $service) {
    $category = $service['category'] ?? 'General';
    if (!isset($servicesByCategory[$category])) {
        $servicesByCategory[$category] = [];
    }
    $servicesByCategory[$category][] = $service;
}

// Obtener horarios de la barberia
$schedules = $db->fetchAll("
    SELECT * FROM barbershop_schedules
    WHERE barbershop_id = ?
    ORDER BY day_of_week ASC
", [$barbershop['id']]);

// Obtener resenas
$reviews = $db->fetchAll("
    SELECT r.*, c.name as client_name
    FROM reviews r
    LEFT JOIN clients c ON r.client_id = c.id
    WHERE r.barbershop_id = ? AND r.is_verified = TRUE AND r.is_visible = TRUE
    ORDER BY r.created_at DESC
", [$barbershop['id']]);

// Calcular promedio de calificaciones
$avgRating = $db->fetch("
    SELECT COALESCE(AVG(rating), 5.0) as avg_rating, COUNT(*) as total_reviews
    FROM reviews
    WHERE barbershop_id = ? AND is_verified = TRUE AND is_visible = TRUE
", [$barbershop['id']]);

$title = $barbershop['business_name'] . ' - Reserva tu cita';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($barbershop['business_name']); ?> - Reserva tu cita</title>
    <meta name="description" content="<?php echo htmlspecialchars($barbershop['description']); ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        * { font-family: 'Inter', sans-serif; }
        .gradient-dark { background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%); }
        .glass { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(10px); }
        .service-card { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .service-card:hover { transform: translateY(-12px) scale(1.02); }
        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-20px); } }
        .float { animation: float 6s ease-in-out infinite; }
    </style>
</head>
<body class="bg-white antialiased">
    <div x-data="bookingApp()">
        
        <!-- Header Navigation -->
        <nav class="fixed top-0 left-0 right-0 z-50 bg-white/90 backdrop-blur-lg border-b border-gray-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center space-x-4">
                        <?php if ($barbershop['logo']): ?>
                            <img src="<?php echo asset($barbershop['logo']); ?>" class="w-10 h-10 rounded-full" alt="Logo">
                        <?php endif; ?>
                        <span class="font-bold text-gray-900 text-lg"><?php echo htmlspecialchars($barbershop['business_name']); ?></span>
                    </div>
                    <div class="flex items-center space-x-4">
                        <?php if ($barbershop['phone']): ?>
                        <a href="https://wa.me/1<?php echo preg_replace('/[^0-9]/', '', $barbershop['phone']); ?>?text=Hola,%20quiero%20reservar%20una%20cita" 
                           target="_blank"
                           class="hidden sm:flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                            </svg>
                            WhatsApp
                        </a>
                        <?php endif; ?>
                        <button @click="showBookingModal = true" 
                                class="px-6 py-2 bg-gray-900 hover:bg-gray-800 text-white rounded-lg font-semibold transition">
                            Reservar Cita
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <div class="pt-16 gradient-dark text-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                    <div>
                        <div class="inline-flex items-center px-4 py-2 bg-white/10 border border-white/20 rounded-full text-sm font-medium mb-6">
                            <span class="w-2 h-2 bg-green-400 rounded-full mr-2 animate-pulse"></span>
                            Abierto ahora
                        </div>
                        
                        <h1 class="text-5xl md:text-7xl font-black mb-6 leading-tight">
                            <?php echo htmlspecialchars($barbershop['business_name']); ?>
                        </h1>
                        
                        <p class="text-xl text-gray-300 mb-8 leading-relaxed">
                            <?php echo htmlspecialchars($barbershop['description']); ?>
                        </p>
                        
                        <!-- Rating -->
                        <div class="flex items-center space-x-4 mb-10">
                            <div class="flex items-center">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <svg class="w-6 h-6 <?php echo $i <= round($avgRating['avg_rating']) ? 'text-yellow-400' : 'text-gray-600'; ?> fill-current" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                <?php endfor; ?>
                            </div>
                            <span class="text-gray-300 font-medium">
                                <?php echo number_format($avgRating['avg_rating'], 1); ?> - <?php echo $avgRating['total_reviews']; ?> resenas
                            </span>
                        </div>
                        
                        <!-- Stats -->
                        <div class="grid grid-cols-3 gap-6 mb-10">
                            <div class="glass border border-white/10 rounded-2xl p-6 text-center">
                                <div class="text-4xl font-black mb-2"><?php echo count($barbers); ?></div>
                                <div class="text-sm text-gray-400">Barberos</div>
                            </div>
                            <div class="glass border border-white/10 rounded-2xl p-6 text-center">
                                <div class="text-4xl font-black mb-2"><?php echo count($services); ?>+</div>
                                <div class="text-sm text-gray-400">Servicios</div>
                            </div>
                            <div class="glass border border-white/10 rounded-2xl p-6 text-center">
                                <div class="text-4xl font-black mb-2"><?php echo $avgRating['total_reviews']; ?></div>
                                <div class="text-sm text-gray-400">Clientes</div>
                            </div>
                        </div>
                        
                        <!-- CTA -->
                        <div class="flex flex-col sm:flex-row gap-4">
                            <button @click="showBookingModal = true" 
                                    class="px-8 py-4 bg-white text-gray-900 rounded-xl font-bold text-lg hover:bg-gray-100 transition transform hover:scale-105">
                                Reservar Ahora
                            </button>
                            <?php if ($barbershop['phone']): ?>
                            <a href="tel:<?php echo htmlspecialchars($barbershop['phone']); ?>" 
                               class="px-8 py-4 bg-white/10 hover:bg-white/20 border border-white/20 text-white rounded-xl font-bold text-lg transition transform hover:scale-105 text-center">
                                <?php echo htmlspecialchars($barbershop['phone']); ?>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Hero Image -->
                    <div class="relative">
                        <?php if ($barbershop['cover_image']): ?>
                            <div class="relative rounded-3xl overflow-hidden shadow-2xl float">
                                <img src="<?php echo asset($barbershop['cover_image']); ?>" 
                                     class="w-full h-[600px] object-cover" 
                                     alt="<?php echo htmlspecialchars($barbershop['business_name']); ?>">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Services Section -->
        <div class="py-24 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <span class="text-gray-600 font-semibold tracking-wider uppercase text-sm">Servicios</span>
                    <h2 class="text-5xl font-black text-gray-900 mt-4 mb-6">Nuestros Servicios Premium</h2>
                    <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                        Servicios profesionales de barberia disenados para darte el mejor look
                    </p>
                </div>

                <?php foreach ($servicesByCategory as $category => $categoryServices): ?>
                <div class="mb-20">
                    <h3 class="text-3xl font-bold text-gray-900 mb-10 pb-4 border-b-2 border-gray-900">
                        <?php echo htmlspecialchars($category); ?>
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                        <?php foreach ($categoryServices as $service): ?>
                        <div class="service-card bg-white border-2 border-gray-200 rounded-2xl overflow-hidden hover:border-gray-900 hover:shadow-2xl">
                            <?php if ($service['image']): ?>
                                <div class="h-56 overflow-hidden">
                                    <img src="<?php echo asset($service['image']); ?>
" class="w-full h-full object-cover" alt="<?php echo htmlspecialchars($service['name']); ?>">
                                </div>
                            <?php else: ?>
                                <div class="h-56 bg-gradient-to-br from-gray-900 to-gray-700 flex items-center justify-center">
                                    <svg class="w-20 h-20 text-white/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243 4.243 3 3 0 004.243-4.243zm0-5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z"/>
                                    </svg>
                                </div>
                            <?php endif; ?>
                            
                            <div class="p-6">
                                <h4 class="text-2xl font-bold text-gray-900 mb-3">
                                    <?php echo htmlspecialchars($service['name']); ?>
                                </h4>
                                <p class="text-gray-600 mb-6 leading-relaxed">
                                    <?php echo htmlspecialchars($service['description']); ?>
                                </p>
                                
                                <div class="flex items-end justify-between mb-6">
                                    <div>
                                        <div class="text-3xl font-black text-gray-900">
                                            <?php echo formatPrice($service['price']); ?>
                                        </div>
                                        <div class="flex items-center text-sm text-gray-500 mt-1">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <?php echo $service['duration']; ?> min
                                        </div>
                                    </div>
                                </div>
                                
                                <button @click="selectService(<?php echo $service['id']; ?>)" 
                                        class="w-full px-6 py-4 bg-gray-900 hover:bg-gray-800 text-white rounded-xl font-bold transition">
                                    Seleccionar
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Barbers Section -->
        <div class="py-24 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <span class="text-gray-600 font-semibold tracking-wider uppercase text-sm">Nuestro Equipo</span>
                    <h2 class="text-5xl font-black text-gray-900 mt-4 mb-6">Barberos Profesionales</h2>
                    <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                        Expertos apasionados por su trabajo
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-<?php echo min(count($barbers), 3); ?> gap-10">
                    <?php foreach ($barbers as $barber): ?>
                    <div class="bg-white rounded-3xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-500 transform hover:-translate-y-2 border-2 border-transparent hover:border-gray-900">
                        <div class="relative h-96">
                            <img src="<?php echo $barber['photo'] ? asset($barber['photo']) : getDefaultAvatar($barber['full_name']); ?>" 
                                 class="w-full h-full object-cover" 
                                 alt="<?php echo htmlspecialchars($barber['full_name']); ?>">
                            
                            <?php if ($barber['is_featured']): ?>
                            <div class="absolute top-4 right-4">
                                <div class="bg-yellow-400 text-yellow-900 px-4 py-2 rounded-full text-sm font-bold shadow-lg">
                                    Destacado
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/90 via-black/50 to-transparent p-6">
                                <h3 class="text-2xl font-bold text-white mb-1">
                                    <?php echo htmlspecialchars($barber['full_name']); ?>
                                </h3>
                                <?php if ($barber['specialty']): ?>
                                <p class="text-white/80 font-medium">
                                    <?php echo htmlspecialchars($barber['specialty']); ?>
                                </p>
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
                                <span class="text-xl font-bold text-gray-900"><?php echo number_format($barber['rating'], 1); ?></span>
                            </div>

                            <button @click="selectBarber(<?php echo $barber['id']; ?>)" 
                                    class="w-full px-6 py-4 bg-gray-900 hover:bg-gray-800 text-white rounded-xl font-bold transition">
                                Reservar con <?php echo explode(' ', $barber['full_name'])[0]; ?>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Reviews Section -->
        <?php if (!empty($reviews)): ?>
        <div class="py-24 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <span class="text-gray-600 font-semibold tracking-wider uppercase text-sm">Testimonios</span>
                    <h2 class="text-5xl font-black text-gray-900 mt-4 mb-6">Lo Que Dicen Nuestros Clientes</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php foreach (array_slice($reviews, 0, 6) as $review): ?>
                    <div class="bg-gray-50 rounded-2xl p-8 border-2 border-gray-200 hover:border-gray-900 hover:shadow-lg transition">
                        <div class="flex items-center mb-6">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <svg class="w-5 h-5 <?php echo $i <= $review['rating'] ? 'text-yellow-400 fill-current' : 'text-gray-300 fill-current'; ?>" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            <?php endfor; ?>
                        </div>
                        
                        <p class="text-gray-700 text-lg leading-relaxed mb-6 italic">
                            &quot;<?php echo htmlspecialchars($review['comment']); ?>&quot;
                        </p>
                        
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-gray-900 rounded-full flex items-center justify-center text-white font-bold">
                                <?php echo strtoupper(substr($review['client_name'] ?? 'C', 0, 1)); ?>
                            </div>
                            <div class="ml-3">
                                <p class="font-bold text-gray-900"><?php echo htmlspecialchars($review['client_name'] ?? 'Cliente'); ?></p>
                                <p class="text-sm text-gray-500">Cliente Verificado</p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- WhatsApp CTA -->
        <?php if ($barbershop['phone']): ?>
        <div class="py-20 bg-green-600">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-white rounded-full shadow-2xl mb-8">
                    <svg class="w-12 h-12 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                    </svg>
                </div>
                <h2 class="text-4xl font-black text-white mb-4">Prefieres reservar por WhatsApp?</h2>
                <p class="text-xl text-green-50 mb-8">Contactanos directamente para atencion personalizada</p>
                <a href="https://wa.me/1<?php echo preg_replace('/[^0-9]/', '', $barbershop['phone']); ?>?text=Hola,%20quiero%20reservar%20una%20cita%20en%20<?php echo urlencode($barbershop['business_name']); ?>" 
                   target="_blank"
                   class="inline-flex items-center px-10 py-5 bg-white text-green-600 rounded-2xl font-black text-xl shadow-2xl hover:bg-gray-50 transform hover:scale-105 transition">
                    <svg class="w-8 h-8 mr-3" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                    </svg>
                    Chatear en WhatsApp
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Footer --
        <footer class="gradient-dark text-white py-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-12">
                    <div class="md:col-span-2">
                        <?php if ($barbershop['logo']): ?>
                            <img src="<?php echo asset($barbershop['logo']); ?>" class="w-16 h-16 rounded-full mb-4" alt="Logo">
                        <?php endif; ?>
                        <h3 class="text-2xl font-black mb-4"><?php echo htmlspecialchars($barbershop['business_name']); ?></h3>
                        <p class="text-gray-400 mb-6 max-w-md"><?php echo htmlspecialchars($barbershop['description']); ?></p>
                        
                        <div class="space-y-3">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 mr-3 mt-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <div class="text-gray-300">
                                    <p><?php echo htmlspecialchars($barbershop['address']); ?></p>
                                    <p><?php echo htmlspecialchars($barbershop['city'] . ', ' . $barbershop['province']); ?></p>
                                </div>
                            </div>
                            
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                                <a href="tel:<?php echo htmlspecialchars($barbershop['phone']); ?>" class="text-gray-300 hover:text-white"><?php echo htmlspecialchars($barbershop['phone']); ?></a>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h4 class="text-lg font-bold mb-6">Horarios</h4>
                        <div class="space-y-2">
                        <?php foreach ($schedules as $schedule): ?>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400"><?php echo getDayName($schedule['day_of_week']); ?></span>
                                <span class="text-gray-300">
                                    <?php if ($schedule['is_closed']): ?>
                                        Cerrado
                                    <?php else: ?>
                                        <?php echo date('g:i A', strtotime($schedule['open_time'])); ?> - <?php echo date('g:i A', strtotime($schedule['close_time'])); ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div>
                        <h4 class="text-lg font-bold mb-6">Siguenos</h4>
                        <div class="space-y-3">
                            <?php if ($barbershop['phone']): ?>
                            <a href="https://wa.me/1<?php echo preg_replace('/[^0-9]/', '', $barbershop['phone']); ?>" target="_blank" class="flex items-center text-gray-300 hover:text-white">
                                <div class="w-10 h-10 bg-green-600 rounded-full flex items-center justify-center mr-3">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                                    </svg>
                                </div>
                                WhatsApp
                            </a>
                            <?php endif; ?>
                            
                            <?php if ($barbershop['facebook']): ?>
                            <a href="<?php echo htmlspecialchars($barbershop['facebook']); ?>" target="_blank" class="flex items-center text-gray-300 hover:text-white">
                                <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center mr-3">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                    </svg>
                                </div>
                                Facebook
                            </a>
                            <?php endif; ?>
                            
                            <?php if ($barbershop['instagram']): ?>
                            <a href="<?php echo htmlspecialchars($barbershop['instagram']); ?>" target="_blank" class="flex items-center text-gray-300 hover:text-white">
                                <div class="w-10 h-10 bg-pink-600 rounded-full flex items-center justify-center mr-3">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                                    </svg>
                                </div>
                                Instagram
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="pt-8 border-t border-gray-700">
                    <div class="flex flex-col md:flex-row justify-between items-center text-gray-400 text-sm">
                        <p>&copy; 2026 <?php echo htmlspecialchars($barbershop['business_name']); ?>. Todos los derechos reservados.</p>
                        <p class="mt-2 md:mt-0">Powered by <span class="font-bold text-white">Kyros Barber Cloud</span></p>
                    </div>
                </div>
            </div>
        </footer>

        <!-- Booking Modal -->
        <div x-show="showBookingModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center">
                <div class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-75" @click="showBookingModal = false"></div>

                <div class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <div class="bg-gray-900 px-8 py-6">
                        <div class="flex items-center justify-between">
                            <h3 class="text-3xl font-black text-white">Reserva tu Cita</h3>
                            <button @click="showBookingModal = false" class="text-white hover:text-gray-300">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <form action="book.php" method="POST" class="p-8 space-y-6">
                        <input type="hidden" name="barbershop_id" value="<?php echo $barbershop['id']; ?>">
                        
                        <div>
                            <label class="block text-sm font-bold text-gray-900 mb-3">Servicio</label>
                            <select name="service_id" required class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-gray-900 focus:border-gray-900 transition">
                                <option value="">Seleccionar servicio...</option>
                                <?php foreach ($services as $service): ?>
                                    <option value="<?php echo $service['id']; ?>">
                                        <?php echo htmlspecialchars($service['name']); ?> - <?php echo formatPrice($service['price']); ?> (<?php echo $service['duration']; ?> min)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-900 mb-3">Barbero</label>
                            <select name="barber_id" required class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-gray-900 focus:border-gray-900 transition">
                                <option value="">Seleccionar barbero...</option>
                                <?php foreach ($barbers as $barber): ?>
                                    <option value="<?php echo $barber['id']; ?>">
                                        <?php echo htmlspecialchars($barber['full_name']); ?> - <?php echo number_format($barber['rating'], 1); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-900 mb-3">Fecha</label>
                                <input type="date" name="booking_date" required min="<?php echo date('Y-m-d'); ?>" 
                                       class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-gray-900 focus:border-gray-900 transition">
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-900 mb-3">Hora</label>
                                <input type="time" name="booking_time" required 
                                       class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-gray-900 focus:border-gray-900 transition">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-900 mb-3">Tu Nombre</label>
                            <input type="text" name="client_name" required 
                                   class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-gray-900 focus:border-gray-900 transition"
                                   placeholder="Nombre completo">
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-900 mb-3">Telefono o WhatsApp</label>
                            <input type="tel" name="client_phone" required 
                                   class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-gray-900 focus:border-gray-900 transition"
                                   placeholder="(809) 000-0000">
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-900 mb-3">Notas adicionales (opcional)</label>
                            <textarea name="notes" rows="3" 
                                      class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-gray-900 focus:border-gray-900 transition"
                                      placeholder="Alguna preferencia especial..."></textarea>
                        </div>

                        <div class="flex gap-4">
                            <button type="button" @click="showBookingModal = false" 
                                    class="flex-1 px-6 py-4 border-2 border-gray-200 text-gray-700 rounded-xl font-bold hover:bg-gray-50 transition">
                                Cancelar
                            </button>
                            <button type="submit" 
                                    class="flex-1 px-6 py-4 bg-gray-900 hover:bg-gray-800 text-white rounded-xl font-bold transition">
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
