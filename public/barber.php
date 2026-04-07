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
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50">
    <div x-data="{ showBookingModal: false }">
        <!-- Header -->
        <header class="bg-white shadow-sm sticky top-0 z-40">
            <div class="max-w-7xl mx-auto px-4 py-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between">
                    <a href="booking.php?shop=<?php echo $shopSlug; ?>" class="flex items-center text-gray-600 hover:text-gray-900">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Volver a la barbería
                    </a>
                    <?php if ($barber['logo']): ?>
                    <img src="<?php echo asset($barber['logo']); ?>" class="h-10" alt="Logo">
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <!-- Hero Section - Perfil del Barbero -->
        <section class="bg-gradient-to-br from-indigo-600 to-purple-700 text-white py-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col md:flex-row items-center md:items-start gap-8">
                    <!-- Foto del Barbero -->
                    <div class="flex-shrink-0">
                        <?php if ($barber['photo']): ?>
                            <img src="<?php echo imageUrl($barber['photo']); ?>" 
                             class="w-48 h-48 rounded-full border-8 border-white shadow-2xl object-cover" 
                             alt="<?php echo e($barber['full_name']); ?>">
                        <?php else: ?>
                        <div class="w-48 h-48 rounded-full border-8 border-white shadow-2xl bg-white text-indigo-600 flex items-center justify-center text-6xl font-bold">
                            <?php echo substr($barber['full_name'], 0, 1); ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($barber['is_featured']): ?>
                        <div class="mt-4 text-center">
                            <span class="inline-flex items-center px-4 py-2 bg-yellow-400 text-yellow-900 rounded-full font-semibold">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                                Barbero Destacado
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Información -->
                    <div class="flex-1 text-center md:text-left">
                        <h1 class="text-5xl font-bold mb-3"><?php echo e($barber['full_name']); ?></h1>
                        <p class="text-2xl text-indigo-100 mb-4"><?php echo e($barber['specialty']); ?></p>
                        
                        <div class="flex flex-wrap items-center justify-center md:justify-start gap-4 mb-6">
                            <div class="flex items-center bg-white/20 backdrop-blur-sm rounded-lg px-4 py-2">
                                <svg class="w-5 h-5 mr-2 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                                <span class="font-semibold"><?php echo number_format($barber['avg_rating'], 1); ?></span>
                                <span class="ml-2 text-indigo-100">(<?php echo $barber['total_reviews']; ?> reseñas)</span>
                            </div>
                            
                            <div class="flex items-center bg-white/20 backdrop-blur-sm rounded-lg px-4 py-2">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span><?php echo $barber['experience_years']; ?> años de experiencia</span>
                            </div>
                        </div>

                        <?php if ($barber['bio']): ?>
                        <p class="text-lg text-indigo-100 mb-6 max-w-2xl">
                            <?php echo nl2br(e($barber['bio'])); ?>
                        </p>
                        <?php endif; ?>

                        <div class="flex flex-wrap gap-4 justify-center md:justify-start">
                            <button @click="showBookingModal = true" 
                                    class="px-8 py-4 bg-white text-indigo-600 rounded-xl font-semibold text-lg hover:bg-gray-100 transition shadow-xl">
                                📅 Reservar Cita con <?php echo explode(' ', $barber['full_name'])[0]; ?>
                            </button>
                            
                            <?php if ($barber['barber_phone']): ?>
                            <a href="https://wa.me/1<?php echo preg_replace('/[^0-9]/', '', $barber['barber_phone']); ?>?text=Hola <?php echo urlencode($barber['full_name']); ?>, quiero agendar una cita" 
                               target="_blank"
                               class="px-8 py-4 bg-green-500 text-white rounded-xl font-semibold text-lg hover:bg-green-600 transition shadow-xl flex items-center">
                                <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                                </svg>
                                WhatsApp
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Servicios Ofrecidos -->
        <section class="py-16 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-gray-900 mb-8 text-center">Servicios Que Ofrezco</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($services as $service): ?>
                    <div class="border border-gray-200 rounded-xl p-6 hover:shadow-lg transition">
                        <div class="flex items-start justify-between mb-3">
                            <h3 class="font-semibold text-lg text-gray-900"><?php echo e($service['name']); ?></h3>
                            <span class="text-2xl font-bold text-indigo-600"><?php echo formatPrice($service['price']); ?></span>
                        </div>
                        <p class="text-gray-600 text-sm mb-3"><?php echo e($service['description']); ?></p>
                        <div class="flex items-center text-sm text-gray-500">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <?php echo $service['duration']; ?> minutos
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Reseñas -->
        <?php if (count($reviews) > 0): ?>
        <section class="py-16 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-gray-900 mb-8 text-center">Lo Que Dicen Mis Clientes</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($reviews as $review): ?>
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="flex items-center mb-3">
                            <?php for($i = 1; $i <= 5; $i++): ?>
                                <svg class="w-5 h-5 <?php echo $i <= $review['rating'] ? 'text-yellow-400' : 'text-gray-300'; ?>" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            <?php endfor; ?>
                        </div>
                        <p class="text-gray-700 mb-4"><?php echo e($review['comment']); ?></p>
                        <div class="flex items-center text-sm text-gray-500">
                            <div class="w-8 h-8 rounded-full bg-indigo-600 text-white flex items-center justify-center font-semibold mr-2">
                                <?php echo substr($review['client_name'], 0, 1); ?>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900"><?php echo e($review['client_name']); ?></p>
                                <p class="text-xs"><?php echo timeAgo($review['created_at']); ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- Modal de Reserva -->
        <div x-show="showBookingModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showBookingModal = false"></div>
                
                <div class="relative bg-white rounded-lg max-w-2xl w-full p-8">
                    <h3 class="text-2xl font-bold text-gray-900 mb-6">Reservar Cita con <?php echo e($barber['full_name']); ?></h3>
                    
                    <form action="book.php" method="POST" class="space-y-6">
                        <input type="hidden" name="barber_id" value="<?php echo $barber['id']; ?>">
                        <input type="hidden" name="barbershop_slug" value="<?php echo $shopSlug; ?>">
                        
                        <!-- Servicio -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Servicio</label>
                            <select name="service_id" required class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                                <?php foreach ($services as $service): ?>
                                <option value="<?php echo $service['id']; ?>">
                                    <?php echo e($service['name']); ?> - <?php echo formatPrice($service['price']); ?> (<?php echo $service['duration']; ?> min)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Fecha y Hora -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Fecha</label>
                                <input type="date" name="date" required min="<?php echo date('Y-m-d'); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Hora</label>
                                <select name="time" required class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                                    <?php foreach (getTimeSlots('09:00', '19:00', 30) as $slot): ?>
                                    <option value="<?php echo $slot; ?>"><?php echo date('g:i A', strtotime($slot)); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Datos del Cliente -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nombre</label>
                                <input type="text" name="client_name" required class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Teléfono</label>
                                <input type="tel" name="client_phone" required placeholder="809-555-1234" class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email (Opcional)</label>
                            <input type="email" name="client_email" class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                        </div>
                        
                        <!-- Botones -->
                        <div class="flex space-x-4">
                            <button type="submit" class="flex-1 px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-semibold">
                                Confirmar Reserva
                            </button>
                            <button type="button" @click="showBookingModal = false" class="flex-1 px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
