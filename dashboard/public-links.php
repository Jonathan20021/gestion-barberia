<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Auth.php';
require_once BASE_PATH . '/core/Helpers.php';

Auth::requireRole('owner');

$db = Database::getInstance();
$barbershopId = $_SESSION['barbershop_id'];

$barbershop = $db->fetch("
    SELECT id, business_name, slug, phone, email, city, theme_color
    FROM barbershops
    WHERE id = ?
", [$barbershopId]);

if (!$barbershop) {
    redirect(BASE_URL . '/dashboard');
}

$barbers = $db->fetchAll("
    SELECT b.slug, b.photo, b.is_featured, u.full_name, u.email, u.phone
    FROM barbers b
    INNER JOIN users u ON b.user_id = u.id
    WHERE b.barbershop_id = ? AND b.status = 'active'
    ORDER BY b.is_featured DESC, u.full_name ASC
", [$barbershopId]);

$shopPublicUrl = BASE_URL . '/public/' . urlencode($barbershop['slug']);
$shopBookingUrl = BASE_URL . '/public/booking.php?shop=' . urlencode($barbershop['slug']);

$title = 'Enlaces Publicos - ' . $barbershop['business_name'];
include BASE_PATH . '/includes/header.php';
?>

<div class="min-h-screen bg-gray-100" x-data="{ sidebarOpen: false }">
    <?php include BASE_PATH . '/includes/sidebar-owner.php'; ?>

    <div class="lg:pl-64">
        <div class="sticky top-0 z-40 flex h-16 bg-white border-b border-gray-200 shadow-sm">
            <button @click="sidebarOpen = true" class="px-4 text-gray-500 lg:hidden">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <div class="flex items-center justify-between flex-1 px-4 sm:px-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Enlaces Publicos</h1>
                    <p class="text-sm text-gray-600 mt-1">Centraliza y comparte los accesos publicos de tu barberia y tu equipo.</p>
                </div>
                <a href="<?php echo e($shopPublicUrl); ?>" target="_blank" class="btn-gold px-4 py-2 rounded-lg text-sm font-medium">
                    Ver pagina publica
                </a>
            </div>
        </div>

        <main class="p-6 space-y-6">
            <section class="bg-white rounded-2xl shadow-md border border-gray-200 overflow-hidden">
                <div class="grid grid-cols-1 xl:grid-cols-5">
                    <div class="xl:col-span-3 p-6 sm:p-8 border-b xl:border-b-0 xl:border-r border-gray-200 relative overflow-hidden">
                        <div class="absolute inset-y-0 right-0 w-56 opacity-10 pointer-events-none" style="background:radial-gradient(circle at center, <?php echo e($barbershop['theme_color'] ?: '#c9901a'); ?> 0%, transparent 70%);"></div>
                        <div class="relative">
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-50 text-indigo-700 text-xs font-semibold uppercase tracking-wider">
                                <span class="w-2 h-2 rounded-full bg-green-500"></span>
                                Enlace principal
                            </div>

                            <h2 class="mt-4 text-3xl font-bold text-gray-900"><?php echo e($barbershop['business_name']); ?></h2>
                            <p class="mt-3 text-base text-gray-600 max-w-2xl leading-7">
                                Comparte este modulo cuando necesites enviar la pagina general de la barberia o una URL directa de reservas sin buscar links en otras pantallas.
                            </p>

                            <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-4">
                                <article class="rounded-2xl border border-gray-200 bg-gray-50 p-5">
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Pagina publica</p>
                                        <span class="px-2.5 py-1 rounded-full bg-white border border-gray-200 text-xs font-medium text-gray-600">Principal</span>
                                    </div>
                                    <p class="mt-3 text-sm text-gray-700 break-all leading-6"><?php echo e($shopPublicUrl); ?></p>
                                    <div class="mt-5 flex flex-wrap gap-2">
                                        <a href="<?php echo e($shopPublicUrl); ?>" target="_blank" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 transition">
                                            Abrir enlace
                                        </a>
                                        <button type="button" onclick="copyPublicLink('<?php echo e($shopPublicUrl); ?>', this)" class="px-4 py-2 bg-white border border-gray-200 text-gray-700 rounded-lg text-sm font-semibold hover:bg-gray-100 transition">
                                            Copiar
                                        </button>
                                    </div>
                                </article>

                                <article class="rounded-2xl border border-gray-200 bg-gray-50 p-5">
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Reserva directa</p>
                                        <span class="px-2.5 py-1 rounded-full bg-amber-50 border border-amber-200 text-xs font-medium text-amber-700">Alternativa</span>
                                    </div>
                                    <p class="mt-3 text-sm text-gray-700 break-all leading-6"><?php echo e($shopBookingUrl); ?></p>
                                    <div class="mt-5 flex flex-wrap gap-2">
                                        <a href="<?php echo e($shopBookingUrl); ?>" target="_blank" class="px-4 py-2 bg-white border border-gray-200 text-gray-700 rounded-lg text-sm font-semibold hover:bg-gray-100 transition">
                                            Abrir enlace
                                        </a>
                                        <button type="button" onclick="copyPublicLink('<?php echo e($shopBookingUrl); ?>', this)" class="px-4 py-2 bg-white border border-gray-200 text-gray-700 rounded-lg text-sm font-semibold hover:bg-gray-100 transition">
                                            Copiar
                                        </button>
                                    </div>
                                </article>
                            </div>
                        </div>
                    </div>

                    <aside class="xl:col-span-2 p-6 sm:p-8 bg-gray-50/60">
                        <h3 class="text-xl font-semibold text-gray-900">Como usar estos links</h3>
                        <div class="mt-5 space-y-3">
                            <div class="rounded-2xl bg-white border border-gray-200 p-4">
                                <p class="text-sm font-semibold text-gray-900">Comparte este primero</p>
                                <p class="mt-1 text-sm text-gray-600">El link principal funciona mejor para bio de Instagram, Google Business y WhatsApp Business.</p>
                            </div>
                            <div class="rounded-2xl bg-white border border-gray-200 p-4">
                                <p class="text-sm font-semibold text-gray-900">Usa perfiles por barbero cuando aplique</p>
                                <p class="mt-1 text-sm text-gray-600">Sirven para promociones personales o para que cada barbero atraiga sus propias reservas.</p>
                            </div>
                            <div class="rounded-2xl bg-white border border-gray-200 p-4">
                                <p class="text-sm font-semibold text-gray-900">Evita mezclar demasiadas URLs</p>
                                <p class="mt-1 text-sm text-gray-600">Mantener una URL principal mejora la claridad para el cliente y concentra mejor las reservas.</p>
                            </div>
                        </div>

                        <div class="mt-6 rounded-2xl bg-white border border-gray-200 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Datos visibles del negocio</p>
                            <div class="mt-3 space-y-2 text-sm text-gray-600">
                                <?php if (!empty($barbershop['city'])): ?>
                                <p><span class="font-medium text-gray-800">Ciudad:</span> <?php echo e($barbershop['city']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($barbershop['phone'])): ?>
                                <p><span class="font-medium text-gray-800">Telefono:</span> <?php echo e($barbershop['phone']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($barbershop['email'])): ?>
                                <p><span class="font-medium text-gray-800">Email:</span> <?php echo e($barbershop['email']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </aside>
                </div>
            </section>

            <section class="bg-white rounded-2xl shadow-md border border-gray-200 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900">Perfiles publicos de barberos</h3>
                        <p class="text-sm text-gray-500 mt-1">Accesos individuales listos para copiar y compartir.</p>
                    </div>
                    <span class="inline-flex items-center justify-center px-3 py-1 rounded-full bg-gray-100 text-gray-700 text-sm font-medium w-fit">
                        <?php echo count($barbers); ?> activos
                    </span>
                </div>

                <div class="p-6">
                    <?php if (empty($barbers)): ?>
                    <div class="rounded-2xl border border-dashed border-gray-300 bg-gray-50 px-6 py-14 text-center">
                        <div class="w-16 h-16 mx-auto rounded-2xl bg-white border border-gray-200 flex items-center justify-center shadow-sm">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <h4 class="mt-5 text-lg font-semibold text-gray-900">Todavia no hay perfiles listos para compartir</h4>
                        <p class="mt-2 text-sm text-gray-600 max-w-xl mx-auto">
                            Cuando agregues barberos activos a tu barberia, sus links apareceran aqui para que puedas abrirlos o copiarlos al instante.
                        </p>
                        <a href="<?php echo BASE_URL; ?>/dashboard/barbers" class="inline-flex mt-6 px-5 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-medium">
                            Gestionar barberos
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                        <?php foreach ($barbers as $barber): ?>
                        <?php $barberPublicUrl = BASE_URL . '/public/' . urlencode($barbershop['slug']) . '/' . urlencode($barber['slug']); ?>
                        <article class="rounded-2xl border border-gray-200 bg-gray-50 p-5 hover:bg-white hover:shadow-sm transition">
                            <div class="flex items-start gap-4">
                                <img src="<?php echo $barber['photo'] ? imageUrl($barber['photo']) : getDefaultAvatar($barber['full_name']); ?>"
                                     alt="<?php echo e($barber['full_name']); ?>"
                                     class="w-14 h-14 rounded-2xl object-cover border border-gray-200 shadow-sm">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h4 class="font-semibold text-gray-900"><?php echo e($barber['full_name']); ?></h4>
                                        <?php if (!empty($barber['is_featured'])): ?>
                                        <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-xs font-medium">Destacado</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($barber['email'])): ?>
                                    <p class="mt-1 text-xs text-gray-500"><?php echo e($barber['email']); ?></p>
                                    <?php endif; ?>
                                    <div class="mt-3 rounded-xl bg-white border border-gray-200 px-4 py-3">
                                        <p class="text-sm text-gray-700 break-all leading-6"><?php echo e($barberPublicUrl); ?></p>
                                    </div>
                                    <div class="mt-4 flex flex-wrap gap-2">
                                        <a href="<?php echo e($barberPublicUrl); ?>" target="_blank" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition">
                                            Abrir perfil
                                        </a>
                                        <button type="button" onclick="copyPublicLink('<?php echo e($barberPublicUrl); ?>', this)" class="px-4 py-2 bg-white border border-gray-200 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-100 transition">
                                            Copiar link
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>
</div>

<script>
function copyPublicLink(url, button) {
    const originalText = button.textContent.trim();
    const onSuccess = () => {
        button.textContent = 'Copiado';
        setTimeout(() => {
            button.textContent = originalText;
        }, 1600);
    };

    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(url).then(onSuccess).catch(() => fallbackCopy(url, button, originalText));
        return;
    }

    fallbackCopy(url, button, originalText);
}

function fallbackCopy(url, button, originalText) {
    const input = document.createElement('input');
    input.value = url;
    document.body.appendChild(input);
    input.select();
    document.execCommand('copy');
    document.body.removeChild(input);
    button.textContent = 'Copiado';
    setTimeout(() => {
        button.textContent = originalText;
    }, 1600);
}
</script>

<?php include BASE_PATH . '/includes/footer.php'; ?>
