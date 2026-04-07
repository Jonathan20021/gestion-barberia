<?php
/**
 * Página Pública de Barbero Individual
 * Para reservar citas directamente con un barbero específico
 */

require_once __DIR__ . '/../config/config.php';
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Helpers.php';

$db = Database::getInstance();

// Obtener slug de barbershop y barber
$shopSlug = input('shop');
$barberSlug = input('barber');

if (!$shopSlug || !$barberSlug) {
    header('Location: ' . BASE_URL);
    exit;
}

// Obtener información del barbero y barbería
$barber = $db->fetch("
    SELECT 
        b.*,
        bb.business_name,
        bb.slug as barbershop_slug,
        bb.theme_color,
        bb.logo,
        bb.phone as barbershop_phone,
        u.full_name,
        u.phone as barber_phone,
        u.email,
        COALESCE(AVG(r.rating), 0) as avg_rating,
        COUNT(DISTINCT r.id) as total_reviews
    FROM barbers b
    JOIN barbershops bb ON b.barbershop_id = bb.id
    JOIN users u ON b.user_id = u.id
    LEFT JOIN reviews r ON b.id = r.barber_id AND r.is_visible = TRUE
    WHERE b.slug = ? AND bb.slug = ? AND b.status = 'active'
    GROUP BY b.id
", [$barberSlug, $shopSlug]);

if (!$barber) {
    header('Location: ' . BASE_URL);
    exit;
}

// Obtener servicios del barbero
$services = $db->fetchAll("
    SELECT DISTINCT s.*
    FROM services s
    JOIN barber_services bs ON s.id = bs.service_id
    WHERE bs.barber_id = ? AND s.is_active = TRUE
    ORDER BY s.category, s.price
", [$barber['id']]);

// Obtener reseñas del barbero
$reviews = $db->fetchAll("
    SELECT r.*, c.name as client_name
    FROM reviews r
    LEFT JOIN clients c ON r.client_id = c.id
    WHERE r.barber_id = ? AND r.is_visible = TRUE
    ORDER BY r.created_at DESC
    LIMIT 10
", [$barber['id']]);

$title = $barber['full_name'] . ' - ' . $barber['business_name'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($title); ?></title>
    <meta name="description" content="<?php echo e($barber['specialty'] ?? ''); ?> — Reserva una cita con <?php echo e($barber['full_name']); ?> en <?php echo e($barber['business_name']); ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        :root { --brand-900:#102027; --brand-700:#1f4b57; --accent-500:#d9a441; }
        body { font-family:'DM Sans',sans-serif; background:#f8f5f0; }
        h1,h2,h3,h4 { font-family:'Sora',sans-serif; }
        .gradient-dark { background:linear-gradient(120deg,var(--brand-900) 0%,var(--brand-700) 55%,#2f626f 100%); }
        .glass { background:rgba(255,255,255,0.07); backdrop-filter:blur(10px); }
        .ornament {
            background-image: linear-gradient(135deg,rgba(217,164,65,.12) 25%,transparent 25%),
                              linear-gradient(225deg,rgba(217,164,65,.10) 25%,transparent 25%);
            background-size:26px 26px; background-position:0 0,13px 13px;
        }
        .svc-card { transition:all .3s cubic-bezier(.4,0,.2,1); }
        .svc-card:hover { transform:translateY(-6px); box-shadow:0 20px 40px rgba(0,0,0,.12); }
        @keyframes float { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-16px)} }
        .float { animation:float 7s ease-in-out infinite; }
        @keyframes fadeUp { from{opacity:0;transform:translateY(24px)} to{opacity:1;transform:translateY(0)} }
        .fade-up { animation:fadeUp .7s ease both; }
    </style>
</head>
<body class="antialiased">
<div x-data="barberApp()">

    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 z-50 bg-white/90 backdrop-blur-lg border-b border-gray-100 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <a href="booking.php?shop=<?php echo $shopSlug; ?>" class="flex items-center text-gray-700 hover:text-gray-900 font-medium transition">
                    <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span class="hidden sm:inline font-semibold"><?php echo e($barber['business_name']); ?></span>
                    <span class="sm:hidden">Volver</span>
                </a>

                <div class="flex items-center gap-3">
                    <?php if ($barber['logo']): ?>
                    <img src="<?php echo asset($barber['logo']); ?>" class="h-8 w-8 rounded-full object-cover" alt="Logo">
                    <?php endif; ?>
                    <?php if ($barber['barber_phone']): ?>
                    <a href="https://wa.me/1<?php echo preg_replace('/[^0-9]/', '', $barber['barber_phone']); ?>?text=Hola%20<?php echo urlencode($barber['full_name']); ?>,%20quiero%20reservar%20una%20cita"
                       target="_blank"
                       class="hidden sm:flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium text-sm transition gap-1.5">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                        </svg>
                        WhatsApp
                    </a>
                    <?php endif; ?>
                    <button @click="openModal()"
                            class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white rounded-lg font-semibold text-sm transition">
                        Reservar Cita
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="pt-16 gradient-dark text-white overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 lg:py-28">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-20 items-center">

                <!-- Left: Info -->
                <div class="fade-up order-2 lg:order-1">
                    <?php if ($barber['is_featured']): ?>
                    <div class="inline-flex items-center px-4 py-1.5 bg-[#d9a441]/20 border border-[#d9a441]/40 rounded-full text-[#d9a441] text-sm font-semibold mb-6">
                        <svg class="w-4 h-4 mr-1.5 fill-current" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        Barbero Destacado
                    </div>
                    <?php else: ?>
                    <div class="inline-flex items-center px-4 py-1.5 bg-white/10 border border-white/20 rounded-full text-gray-300 text-sm font-medium mb-6">
                        <span class="w-2 h-2 bg-green-400 rounded-full mr-2 animate-pulse"></span>
                        Disponible para reservas
                    </div>
                    <?php endif; ?>

                    <h1 class="text-5xl md:text-6xl lg:text-7xl font-black mb-4 leading-none tracking-tight">
                        <?php echo e($barber['full_name']); ?>
                    </h1>

                    <?php if ($barber['specialty']): ?>
                    <p class="text-xl text-[#d9a441] font-semibold mb-6"><?php echo e($barber['specialty']); ?></p>
                    <?php endif; ?>

                    <!-- Stats row -->
                    <div class="flex flex-wrap items-center gap-4 mb-8">
                        <div class="flex items-center gap-2">
                            <div class="flex">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <svg class="w-5 h-5 <?php echo $i <= round($barber['avg_rating']) ? 'text-yellow-400' : 'text-gray-600'; ?> fill-current" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                                <?php endfor; ?>
                            </div>
                            <span class="text-white font-bold text-lg"><?php echo number_format($barber['avg_rating'], 1); ?></span>
                            <span class="text-gray-400 text-sm">(<?php echo $barber['total_reviews']; ?> reseñas)</span>
                        </div>

                        <div class="glass border border-white/10 rounded-xl px-4 py-2 flex items-center gap-2 text-sm">
                            <svg class="w-4 h-4 text-[#d9a441]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span><?php echo $barber['experience_years']; ?> años de exp.</span>
                        </div>

                        <div class="glass border border-white/10 rounded-xl px-4 py-2 flex items-center gap-2 text-sm">
                            <svg class="w-4 h-4 text-[#d9a441]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <span><?php echo count($services); ?> servicios</span>
                        </div>
                    </div>

                    <?php if ($barber['bio']): ?>
                    <p class="text-gray-300 text-lg leading-relaxed mb-8 max-w-lg">
                        <?php echo nl2br(e($barber['bio'])); ?>
                    </p>
                    <?php endif; ?>

                    <!-- CTAs -->
                    <div class="flex flex-col sm:flex-row gap-4">
                        <button @click="openModal()"
                                class="px-8 py-4 bg-white text-gray-900 rounded-xl font-bold text-lg hover:bg-gray-100 transition transform hover:scale-105 shadow-lg">
                            Reservar Cita
                        </button>
                        <?php if ($barber['barber_phone']): ?>
                        <a href="https://wa.me/1<?php echo preg_replace('/[^0-9]/', '', $barber['barber_phone']); ?>?text=Hola%20<?php echo urlencode($barber['full_name']); ?>,%20quiero%20agendar%20una%20cita"
                           target="_blank"
                           class="px-8 py-4 bg-green-600 hover:bg-green-700 text-white rounded-xl font-bold text-lg transition transform hover:scale-105 flex items-center justify-center gap-3 shadow-lg">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                            </svg>
                            WhatsApp
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right: Photo -->
                <div class="flex justify-center lg:justify-end order-1 lg:order-2">
                    <?php $barberPhoto = !empty($barber['photo']) ? imageUrl($barber['photo']) : null; ?>
                    <?php if ($barberPhoto): ?>
                    <div class="relative">
                        <div class="absolute inset-0 bg-[#d9a441]/20 rounded-3xl blur-3xl scale-110"></div>
                        <div class="relative rounded-3xl overflow-hidden shadow-2xl float w-72 h-80 md:w-96 md:h-[480px]">
                            <img src="<?php echo $barberPhoto; ?>"
                                 class="w-full h-full object-cover object-center"
                                 alt="<?php echo e($barber['full_name']); ?>">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent"></div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="relative">
                        <div class="absolute inset-0 bg-[#d9a441]/10 rounded-3xl blur-3xl scale-110"></div>
                        <div class="relative w-72 h-80 md:w-80 md:h-80 rounded-3xl glass border border-white/10 flex items-center justify-center float">
                            <span class="text-white font-black text-9xl" style="font-family:'Sora',sans-serif; opacity:.25;">
                                <?php echo strtoupper(substr($barber['full_name'], 0, 1)); ?>
                            </span>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>

    <!-- Services Section -->
    <?php if (!empty($services)): ?>
    <div class="py-24 bg-white ornament">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <span class="text-amber-700 font-semibold tracking-wider uppercase text-sm">Especialidades</span>
                <h2 class="text-5xl font-black text-gray-900 mt-4 mb-4" style="letter-spacing:-0.02em;">Mis Servicios</h2>
                <p class="text-xl text-gray-500 max-w-xl mx-auto">Servicios especializados con atención al detalle y técnica profesional</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($services as $service): ?>
                <div class="svc-card bg-white border-2 border-gray-100 rounded-2xl p-7 hover:border-gray-900">
                    <div class="flex items-start justify-between mb-5">
                        <div class="w-12 h-12 bg-gray-900 rounded-xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243 4.243 3 3 0 004.243-4.243zm0-5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z"/>
                            </svg>
                        </div>
                        <span class="text-3xl font-black text-gray-900"><?php echo formatPrice($service['price']); ?></span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2"><?php echo e($service['name']); ?></h3>
                    <?php if ($service['description']): ?>
                    <p class="text-gray-500 text-sm leading-relaxed mb-4"><?php echo e($service['description']); ?></p>
                    <?php endif; ?>
                    <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                        <div class="flex items-center text-sm text-gray-500 gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <?php echo $service['duration']; ?> min
                        </div>
                        <button @click="openModal()"
                                class="px-4 py-2 bg-gray-900 hover:bg-gray-800 text-white rounded-lg text-sm font-semibold transition">
                            Reservar
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Reviews Section -->
    <?php if (!empty($reviews)): ?>
    <div class="py-24 bg-[#f2f4f6]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <span class="text-amber-700 font-semibold tracking-wider uppercase text-sm">Testimonios</span>
                <h2 class="text-5xl font-black text-gray-900 mt-4" style="letter-spacing:-0.02em;">Lo Que Dicen Mis Clientes</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach (array_slice($reviews, 0, 6) as $review): ?>
                <div class="bg-white rounded-2xl p-8 border-2 border-gray-100 hover:border-gray-900 hover:shadow-lg transition">
                    <div class="text-[#d9a441] text-5xl font-black mb-3 leading-none" style="font-family:Georgia,serif;">"</div>
                    <div class="flex items-center mb-4 gap-0.5">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <svg class="w-4 h-4 <?php echo $i <= $review['rating'] ? 'text-yellow-400 fill-current' : 'text-gray-300 fill-current'; ?>" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        <?php endfor; ?>
                    </div>
                    <p class="text-gray-700 leading-relaxed mb-6"><?php echo e($review['comment']); ?></p>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-gray-900 rounded-full flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                            <?php echo strtoupper(substr($review['client_name'] ?? 'C', 0, 1)); ?>
                        </div>
                        <div>
                            <p class="font-bold text-gray-900 text-sm"><?php echo e($review['client_name'] ?? 'Cliente'); ?></p>
                            <p class="text-xs text-gray-500">Cliente Verificado &middot; <?php echo timeAgo($review['created_at']); ?></p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- CTA Final -->
    <div class="gradient-dark text-white py-24">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="w-20 h-0.5 bg-[#d9a441] mx-auto mb-8"></div>
            <h2 class="text-4xl md:text-5xl font-black mb-6 leading-tight" style="letter-spacing:-0.02em;">
                ¿Listo para lucir increíble?
            </h2>
            <p class="text-xl text-gray-300 mb-10 max-w-xl mx-auto">
                Reserva tu cita con <?php echo explode(' ', $barber['full_name'])[0]; ?> y disfruta de un servicio de primera calidad
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <button @click="openModal()"
                        class="px-10 py-5 bg-white text-gray-900 rounded-2xl font-black text-xl hover:bg-gray-100 transition transform hover:scale-105 shadow-2xl">
                    Reservar Ahora
                </button>
                <?php if ($barber['barber_phone']): ?>
                <a href="https://wa.me/1<?php echo preg_replace('/[^0-9]/', '', $barber['barber_phone']); ?>?text=Hola%20<?php echo urlencode($barber['full_name']); ?>,%20quiero%20agendar%20una%20cita"
                   target="_blank"
                   class="px-10 py-5 bg-green-600 hover:bg-green-700 text-white rounded-2xl font-black text-xl transition flex items-center justify-center gap-3">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                    </svg>
                    WhatsApp
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <?php if ($barber['logo']): ?>
                    <img src="<?php echo asset($barber['logo']); ?>" class="w-8 h-8 rounded-full object-cover" alt="Logo">
                    <?php endif; ?>
                    <div>
                        <p class="font-bold text-white"><?php echo e($barber['business_name']); ?></p>
                        <a href="booking.php?shop=<?php echo $shopSlug; ?>" class="text-gray-400 hover:text-white text-sm transition">
                            ← Ver toda la barbería
                        </a>
                    </div>
                </div>
                <p class="text-gray-500 text-sm">Powered by <span class="font-bold text-white">Kyros Barber Cloud</span></p>
            </div>
        </div>
    </footer>

    <!-- Booking Modal -->
    <div x-show="showBookingModal" class="fixed inset-0 z-50 overflow-y-auto" style="display:none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" @click="closeModal()"></div>

            <div class="relative bg-white rounded-3xl shadow-2xl sm:max-w-xl w-full overflow-hidden">
                <!-- Modal Header -->
                <div class="bg-gray-900 px-8 py-6 flex items-center justify-between">
                    <div>
                        <h3 class="text-2xl font-black text-white" style="font-family:'Sora',sans-serif;">Reservar Cita</h3>
                        <p class="text-gray-400 text-sm mt-0.5">con <?php echo e($barber['full_name']); ?></p>
                    </div>
                    <button @click="closeModal()" class="text-gray-400 hover:text-white transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form action="book.php" method="POST" class="p-8 space-y-5">
                    <input type="hidden" name="barber_id" value="<?php echo $barber['id']; ?>">
                    <input type="hidden" name="barbershop_id" value="<?php echo $barber['barbershop_id']; ?>">

                    <div>
                        <label class="block text-sm font-bold text-gray-900 mb-2">Servicio</label>
                        <select name="service_id" x-model="selectedService" @change="loadAvailability()" required class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-gray-900 focus:border-gray-900 transition">
                            <option value="">Seleccionar servicio...</option>
                            <?php foreach ($services as $service): ?>
                            <option value="<?php echo $service['id']; ?>">
                                <?php echo e($service['name']); ?> — <?php echo formatPrice($service['price']); ?> (<?php echo $service['duration']; ?> min)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-900 mb-2">Fecha</label>
                            <input type="date" name="appointment_date" x-model="selectedDate" @change="loadAvailability()" required min="<?php echo date('Y-m-d'); ?>"
                                   class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-gray-900 focus:border-gray-900 transition">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-900 mb-2">Hora</label>
                            <select name="start_time" x-model="selectedStartTime" required class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-gray-900 focus:border-gray-900 transition">
                                <option value="" x-text="availabilityLoading ? 'Cargando horarios...' : 'Selecciona fecha y servicio'"></option>
                                <template x-for="slot in availableSlots" :key="slot.value">
                                    <option :value="slot.value" x-text="slot.time"></option>
                                </template>
                            </select>
                            <p x-show="availabilityMessage" class="text-xs mt-2 text-amber-700" x-text="availabilityMessage"></p>
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

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-900 mb-2">Tu Nombre</label>
                            <input type="text" name="client_name" required placeholder="Nombre completo"
                                   class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-gray-900 focus:border-gray-900 transition">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-900 mb-2">Teléfono</label>
                            <input type="tel" name="client_phone" required placeholder="(809) 000-0000"
                                   class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-gray-900 focus:border-gray-900 transition">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-900 mb-2">Email (opcional)</label>
                        <input type="email" name="client_email" placeholder="correo@ejemplo.com"
                               class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-gray-900 focus:border-gray-900 transition">
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="closeModal()"
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
function barberApp() {
    return {
        showBookingModal: false,
        selectedService: '',
        selectedDate: '',
        selectedStartTime: '',
        availableSlots: [],
        occupiedSlots: [],
        availabilityLoading: false,
        availabilityMessage: '',
        openModal()  { this.showBookingModal = true;  },
        closeModal() { this.showBookingModal = false; },
        async loadAvailability() {
            this.availableSlots = [];
            this.occupiedSlots = [];
            this.selectedStartTime = '';
            this.availabilityMessage = '';

            if (!this.selectedDate) {
                return;
            }

            this.availabilityLoading = true;

            try {
                const params = new URLSearchParams({
                    barber_id: '<?php echo (int) $barber['id']; ?>',
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

                if (this.availableSlots.length === 0) {
                    this.availabilityMessage = data.message || 'No hay horarios disponibles para esa fecha';
                }
            } catch (error) {
                this.availabilityMessage = 'Error al consultar horarios';
            } finally {
                this.availabilityLoading = false;
            }
        }
    };
}
</script>
</body>
</html>
