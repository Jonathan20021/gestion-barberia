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
    SELECT b.*, u.full_name, u.phone, COALESCE(NULLIF(b.photo, ''), NULLIF(u.avatar, '')) as public_photo
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

// Verificar si la barberia esta abierta ahora
$isOpenNow = false;
$currentDayOfWeek = (int) date('w'); // 0=Domingo, 1=Lunes...
$currentTime = date('H:i:s');
foreach ($schedules as $schedule) {
    if ((int)$schedule['day_of_week'] === $currentDayOfWeek && !$schedule['is_closed']) {
        if ($currentTime >= $schedule['open_time'] && $currentTime <= $schedule['close_time']) {
            $isOpenNow = true;
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($barbershop['business_name']); ?> - Reserva tu cita</title>
    <meta name="description" content="<?php echo htmlspecialchars($barbershop['description'] ?? ''); ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        :root {
            --brand-900: #102027;
            --brand-700: #1f4b57;
            --accent-500: #d9a441;
        }
        body { font-family: 'DM Sans', sans-serif; background: radial-gradient(circle at 15% 10%, #fdf8ee 0%, #f4efe6 40%, #eef2f4 100%); }
        h1, h2, h3, h4 { font-family: 'Sora', sans-serif; }
        .gradient-dark { background: linear-gradient(120deg, var(--brand-900) 0%, var(--brand-700) 55%, #2f626f 100%); }
        .glass { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(10px); }
        .service-card { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .service-card:hover { transform: translateY(-12px) scale(1.02); }
        .section-title { letter-spacing: -0.02em; }
        .ornament {
            background-image: linear-gradient(135deg, rgba(217, 164, 65, 0.16) 25%, transparent 25%),
                              linear-gradient(225deg, rgba(217, 164, 65, 0.14) 25%, transparent 25%);
            background-size: 26px 26px;
            background-position: 0 0, 13px 13px;
        }
        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-20px); } }
        .float { animation: float 6s ease-in-out infinite; }
    </style>
</head>
<body class="antialiased">
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
                        <button @click="openBookingModal()" 
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
                        <?php if ($isOpenNow): ?>
                        <div class="inline-flex items-center px-4 py-2 bg-green-500/20 border border-green-400/40 rounded-full text-sm font-medium mb-6 text-green-300">
                            <span class="w-2 h-2 bg-green-400 rounded-full mr-2 animate-pulse"></span>
                            Abierto ahora
                        </div>
                        <?php else: ?>
                        <div class="inline-flex items-center px-4 py-2 bg-white/10 border border-white/20 rounded-full text-sm font-medium mb-6 text-gray-300">
                            <span class="w-2 h-2 bg-gray-500 rounded-full mr-2"></span>
                            Cerrado ahora
                        </div>
                        <?php endif; ?>
                        
                        <h1 class="text-5xl md:text-7xl font-black mb-6 leading-tight">
                            <?php echo htmlspecialchars($barbershop['business_name']); ?>
                        </h1>
                        
                        <p class="text-xl text-gray-300 mb-8 leading-relaxed">
                            <?php echo htmlspecialchars($barbershop['description'] ?? ''); ?>
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
                            <button @click="openBookingModal()" 
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

        <!-- How to Book Section -->
        <div class="py-20 bg-white">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <span class="text-amber-700 font-semibold tracking-wider uppercase text-sm">Sencillo y rápido</span>
                    <h2 class="text-4xl font-black text-gray-900 mt-3" style="letter-spacing:-0.02em;">Reserva en 3 pasos</h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="flex flex-col items-center text-center group">
                        <div class="w-16 h-16 bg-gray-900 rounded-2xl flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <div class="text-5xl font-black text-gray-100 mb-2" style="font-family:'Sora',sans-serif;">01</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Elige tu servicio</h3>
                        <p class="text-gray-500">Selecciona el corte o servicio que deseas de nuestro menú</p>
                    </div>
                    <div class="flex flex-col items-center text-center group">
                        <div class="w-16 h-16 bg-[#d9a441] rounded-2xl flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <div class="text-5xl font-black text-gray-100 mb-2" style="font-family:'Sora',sans-serif;">02</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Escoge tu barbero</h3>
                        <p class="text-gray-500">Elige al profesional de confianza con quien quieres tu cita</p>
                    </div>
                    <div class="flex flex-col items-center text-center group">
                        <div class="w-16 h-16 bg-gray-900 rounded-2xl flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="text-5xl font-black text-gray-100 mb-2" style="font-family:'Sora',sans-serif;">03</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Confirma tu cita</h3>
                        <p class="text-gray-500">Ingresa tus datos y recibirás confirmación inmediata</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Services Section -->
        <div class="py-24 bg-white/90 ornament">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <span class="text-amber-700 font-semibold tracking-wider uppercase text-sm">Servicios</span>
                    <h2 class="section-title text-5xl font-black text-gray-900 mt-4 mb-6">Servicios De Barberia Con Estilo</h2>
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
                                    <?php echo htmlspecialchars($service['description'] ?? ''); ?>
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
        <div class="py-24 bg-[#f2f4f6]">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <span class="text-amber-700 font-semibold tracking-wider uppercase text-sm">Nuestro Equipo</span>
                    <h2 class="section-title text-5xl font-black text-gray-900 mt-4 mb-6">Barberos Profesionales</h2>
                    <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                        Expertos apasionados por su trabajo
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-<?php echo min(max(count($barbers), 1), 3); ?> gap-8 items-stretch">
                    <?php foreach ($barbers as $barber): ?>
                    <div class="bg-white rounded-3xl overflow-hidden shadow-md hover:shadow-xl transition-all duration-300 border border-gray-200 w-full max-w-xl mx-auto flex flex-col">
                        <div class="relative h-72 md:h-80 bg-gray-100">
                            <img src="<?php echo !empty($barber['public_photo']) ? imageUrl($barber['public_photo']) : getDefaultAvatar($barber['full_name']); ?>" 
                                 class="w-full h-full object-cover object-center" 
                                 alt="<?php echo htmlspecialchars($barber['full_name']); ?>">

                            <?php if ($barber['is_featured']): ?>
                            <div class="absolute top-4 left-4">
                                <div class="bg-yellow-400 text-yellow-900 px-3 py-1 rounded-full text-xs font-bold shadow-md">
                                    Barbero destacado
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="p-6 flex-1 flex flex-col">
                            <h3 class="text-2xl font-extrabold text-gray-900 leading-tight mb-1"><?php echo htmlspecialchars($barber['full_name']); ?></h3>
                            <div class="flex items-center justify-between text-sm text-gray-500 mb-4">
                                <span><?php echo intval($barber['experience_years'] ?? 0); ?> anos exp.</span>
                                <?php if (!empty($barber['specialty'])): ?>
                                <span class="truncate max-w-[200px] text-right"><?php echo htmlspecialchars($barber['specialty']); ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="flex items-center justify-between mb-5">
                                <div class="flex items-center space-x-1">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <svg class="w-5 h-5 <?php echo $i <= round($barber['rating']) ? 'text-yellow-400 fill-current' : 'text-gray-300 fill-current'; ?>" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    <?php endfor; ?>
                                </div>
                                <span class="text-xl font-bold text-gray-900"><?php echo number_format($barber['rating'], 1); ?></span>
                            </div>

                            <div class="mt-auto grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <a href="<?php echo BASE_URL; ?>/public/barber.php?shop=<?php echo urlencode($barbershop['slug']); ?>&barber=<?php echo urlencode($barber['slug']); ?>"
                                   class="w-full px-4 py-4 border-2 border-gray-200 hover:border-gray-300 text-gray-800 rounded-xl font-bold transition text-center">
                                    Ver Perfil
                                </a>
                                <button @click="selectBarber(<?php echo $barber['id']; ?>)"
                                        class="w-full px-4 py-4 bg-gray-900 hover:bg-gray-800 text-white rounded-xl font-bold transition">
                                    Reservar con <?php echo explode(' ', $barber['full_name'])[0]; ?>
                                </button>
                            </div>
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

        <!-- Footer -->
        <footer class="gradient-dark text-white py-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-12">
                    <div class="md:col-span-2">
                        <?php if ($barbershop['logo']): ?>
                            <img src="<?php echo asset($barbershop['logo']); ?>" class="w-16 h-16 rounded-full mb-4" alt="Logo">
                        <?php endif; ?>
                        <h3 class="text-2xl font-black mb-4"><?php echo htmlspecialchars($barbershop['business_name']); ?></h3>
                        <p class="text-gray-400 mb-6 max-w-md"><?php echo htmlspecialchars($barbershop['description'] ?? ''); ?></p>
                        
                        <div class="space-y-3">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 mr-3 mt-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <div class="text-gray-300">
                                    <p><?php echo htmlspecialchars($barbershop['address'] ?? ''); ?></p>
                                    <p><?php echo htmlspecialchars(($barbershop['city'] ?? '') . ', ' . ($barbershop['province'] ?? '')); ?></p>
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
                <div class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-75" @click="closeBookingModal()"></div>

                <div class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <div class="bg-gray-900 px-8 py-6">
                        <div class="flex items-center justify-between">
                            <h3 class="text-3xl font-black text-white">Reserva tu Cita</h3>
                            <button @click="closeBookingModal()" class="text-white hover:text-gray-300">
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
                            <select name="service_id" x-model="selectedService" @change="loadAvailability()" required class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-gray-900 focus:border-gray-900 transition">
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
                            <select name="barber_id" x-model="selectedBarber" @change="loadAvailability()" required class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-gray-900 focus:border-gray-900 transition">
                                <option value="">Seleccionar barbero...</option>
                                <?php foreach ($barbers as $barber): ?>
                                    <option value="<?php echo $barber['id']; ?>">
                                        <?php echo htmlspecialchars($barber['full_name']); ?> - <?php echo number_format($barber['rating'], 1); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p x-show="selectedBarber" class="text-xs text-green-700 mt-2">Barbero preseleccionado</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-900 mb-3">Fecha</label>
                                <input type="date" name="appointment_date" x-model="selectedDate" @change="loadAvailability()" required min="<?php echo date('Y-m-d'); ?>" 
                                       class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-gray-900 focus:border-gray-900 transition">
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-900 mb-3">Hora</label>
                                <select name="start_time" x-model="selectedStartTime" required class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-gray-900 focus:border-gray-900 transition">
                                    <option value="" x-text="availabilityLoading ? 'Cargando horarios...' : 'Selecciona servicio, barbero y fecha'"></option>
                                    <template x-for="slot in availableSlots" :key="slot.value">
                                        <option :value="slot.value" x-text="slot.time"></option>
                                    </template>
                                </select>
                                <p x-show="availabilityMessage" class="text-xs mt-2 text-amber-700" x-text="availabilityMessage"></p>
                                <p x-show="!availabilityMessage && availableSlots.length > 0" class="text-xs mt-2 text-gray-500">
                                    Se muestran solo horas disponibles y futuras en intervalos de <span x-text="intervalMinutes"></span> min.
                                </p>
                                <div x-show="occupiedSlots.length > 0" class="mt-2">
                                    <p class="text-xs text-gray-500 mb-1">Horas ocupadas:</p>
                                    <div class="flex flex-wrap gap-1">
                                        <template x-for="slot in occupiedSlots" :key="slot.label">
                                            <span class="text-[11px] px-2 py-1 rounded-full bg-red-50 text-red-700 border border-red-100" x-text="slot.label"></span>
                                        </template>
                                    </div>
                                </div>
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
                            <button type="button" @click="closeBookingModal()" 
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
                selectedService: '',
                selectedBarber: '',
                selectedDate: '',
                selectedStartTime: '',
                availableSlots: [],
                occupiedSlots: [],
                availabilityLoading: false,
                availabilityMessage: '',
                intervalMinutes: 15,

                openBookingModal() {
                    this.showBookingModal = true;
                },

                closeBookingModal() {
                    this.showBookingModal = false;
                },
                
                selectService(serviceId) {
                    this.selectedService = String(serviceId);
                    this.openBookingModal();
                    this.loadAvailability();
                },
                
                selectBarber(barberId) {
                    this.selectedBarber = String(barberId);
                    this.openBookingModal();
                    this.loadAvailability();
                },

                async loadAvailability() {
                    this.availableSlots = [];
                    this.occupiedSlots = [];
                    this.selectedStartTime = '';
                    this.availabilityMessage = '';

                    if (!this.selectedService || !this.selectedBarber || !this.selectedDate) {
                        if (this.selectedDate && this.selectedBarber && !this.selectedService) {
                            this.availabilityMessage = 'Selecciona un servicio para calcular horarios exactos.';
                        }
                        return;
                    }

                    this.availabilityLoading = true;

                    try {
                        const params = new URLSearchParams({
                            barber_id: this.selectedBarber,
                            date: this.selectedDate,
                            service_id: this.selectedService || ''
                        });

                        const response = await fetch('<?php echo BASE_URL; ?>/api/availability.php?' + params.toString());
                        const data = await response.json();

                        if (!data.success) {
                            this.availabilityMessage = data.message || 'No se pudo cargar la disponibilidad';
                            return;
                        }

                        this.availableSlots = Array.isArray(data.available_slots) ? data.available_slots : [];
                        this.occupiedSlots = Array.isArray(data.occupied_slots) ? data.occupied_slots : [];
                        this.intervalMinutes = data.interval_minutes || 15;

                        if (this.availableSlots.length === 0) {
                            this.availabilityMessage = data.message || 'No hay horas disponibles para la fecha seleccionada';
                        }
                    } catch (error) {
                        this.availabilityMessage = 'Error al consultar horarios';
                    } finally {
                        this.availabilityLoading = false;
                    }
                }
            }
        }
    </script>
</body>
</html>
