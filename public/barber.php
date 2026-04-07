<?php
require_once __DIR__ . '/../config/config.php';
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Helpers.php';

$db = Database::getInstance();
$shopSlug  = input('shop');
$barberSlug = input('barber');

if (!$shopSlug || !$barberSlug) { header('Location: ' . BASE_URL); exit; }

$barber = $db->fetch("
    SELECT b.*, bb.business_name, bb.slug as barbershop_slug, bb.theme_color, bb.logo,
           bb.phone as barbershop_phone, u.full_name, u.phone as barber_phone, u.email,
           COALESCE(AVG(r.rating), 0) as avg_rating, COUNT(DISTINCT r.id) as total_reviews
    FROM barbers b
    JOIN barbershops bb ON b.barbershop_id = bb.id
    JOIN users u ON b.user_id = u.id
    LEFT JOIN reviews r ON b.id = r.barber_id AND r.is_visible = TRUE
    WHERE b.slug = ? AND bb.slug = ? AND b.status = 'active'
    GROUP BY b.id
", [$barberSlug, $shopSlug]);

if (!$barber) { header('Location: ' . BASE_URL); exit; }

$services = $db->fetchAll("
    SELECT DISTINCT s.* FROM services s JOIN barber_services bs ON s.id = bs.service_id
    WHERE bs.barber_id = ? AND s.is_active = TRUE ORDER BY s.category, s.price
", [$barber['id']]);
if (empty($services)) {
    $services = $db->fetchAll("SELECT * FROM services WHERE barbershop_id = ? AND is_active = TRUE ORDER BY category, price", [$barber['barbershop_id']]);
}

$reviews = $db->fetchAll("
    SELECT r.*, c.name as client_name FROM reviews r
    LEFT JOIN clients c ON r.client_id = c.id
    WHERE r.barber_id = ? AND r.is_visible = TRUE ORDER BY r.created_at DESC LIMIT 10
", [$barber['id']]);

$title = $barber['full_name'] . ' — ' . $barber['business_name'];
$barberPhoto = !empty($barber['photo']) ? imageUrl($barber['photo']) : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($title); ?></title>
    <meta name="description" content="<?php echo e($barber['specialty'] ?? ''); ?> — Reserva con <?php echo e($barber['full_name']); ?> en <?php echo e($barber['business_name']); ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sora:wght@700;800;900&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        *, *::before, *::after { box-sizing:border-box; }
        body { font-family:'Inter',sans-serif; background:#f5f5f0; margin:0; }
        h1,h2,h3,h4 { font-family:'Sora',sans-serif; }

        @keyframes fadeUp  { from{opacity:0;transform:translateY(24px)} to{opacity:1;transform:translateY(0)} }
        @keyframes pulse2  { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.3;transform:scale(1.7)} }
        @keyframes modalUp { from{opacity:0;transform:translateY(32px) scale(.97)} to{opacity:1;transform:translateY(0) scale(1)} }
        @keyframes shimmer { 0%{transform:translateX(-100%)} 100%{transform:translateX(100%)} }

        .fade-up  { animation:fadeUp .65s cubic-bezier(.16,1,.3,1) both; }
        .fade-up2 { animation:fadeUp .65s .1s cubic-bezier(.16,1,.3,1) both; }
        .modal-anim { animation:modalUp .28s cubic-bezier(.16,1,.3,1) both; }

        .btn-gold {
            position:relative; overflow:hidden;
            background:linear-gradient(135deg,#c9901a 0%,#e8b84b 50%,#c9901a 100%);
            color:#0a0a0a; font-weight:700;
            box-shadow:0 4px 24px rgba(201,144,26,.35);
            transition:box-shadow .2s, transform .2s;
            border:none; cursor:pointer;
        }
        .btn-gold::after {
            content:''; position:absolute; top:0; left:-100%; width:100%; height:100%;
            background:linear-gradient(90deg,transparent,rgba(255,255,255,.25),transparent);
        }
        .btn-gold:hover::after { animation:shimmer .6s ease forwards; }
        .btn-gold:hover { box-shadow:0 6px 32px rgba(201,144,26,.50); transform:translateY(-1px); }

        .card { transition:transform .22s cubic-bezier(.4,0,.2,1), box-shadow .22s cubic-bezier(.4,0,.2,1); }
        .card:hover { transform:translateY(-4px); box-shadow:0 20px 44px rgba(0,0,0,.09); }

        .inp {
            width:100%; padding:11px 14px;
            background:#f9f9f7; border:1.5px solid #e5e5e2; border-radius:12px;
            font-size:.875rem; color:#111; transition:border-color .18s,box-shadow .18s;
            font-family:inherit;
        }
        .inp:focus { outline:none; border-color:#c9901a; box-shadow:0 0 0 3px rgba(201,144,26,.15); }
        .inp::placeholder { color:#aaa; }

        select { -webkit-appearance:none; appearance:none;
            background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%239ca3af' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E");
            background-repeat:no-repeat; background-position:right 12px center; background-size:16px; padding-right:40px; }

        [x-cloak] { display:none !important; }

        @media(min-width:768px){
            .hero-grid { grid-template-columns:1fr 1fr !important; }
            .svc-grid  { grid-template-columns:repeat(3,1fr) !important; }
        }
        @media(min-width:640px){
            .modal-panel { border-radius:24px !important; }
        }
    </style>
</head>
<body x-data="barberApp()">

<!-- ══════ NAVBAR ══════ -->
<nav style="position:fixed;top:0;left:0;right:0;z-index:100;background:rgba(255,255,255,.96);backdrop-filter:blur(12px);border-bottom:1px solid #f0f0ec;">
    <div style="max-width:1200px;margin:0 auto;padding:0 24px;display:flex;align-items:center;justify-content:space-between;height:64px;">

        <a href="<?php echo BASE_URL; ?>/public/<?php echo $shopSlug; ?>"
           style="display:inline-flex;align-items:center;gap:8px;color:#52525b;text-decoration:none;font-size:.875rem;font-weight:600;transition:color .18s;"
           onmouseover="this.style.color='#0a0a0a'" onmouseout="this.style.color='#52525b'">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <span><?php echo e($barber['business_name']); ?></span>
        </a>

        <div style="display:flex;align-items:center;gap:10px;">
            <?php if ($barber['barber_phone']): ?>
            <a href="https://wa.me/1<?php echo preg_replace('/[^0-9]/', '', $barber['barber_phone']); ?>?text=Hola%20<?php echo urlencode($barber['full_name']); ?>,%20quiero%20reservar"
               target="_blank"
               style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:#22c55e;color:#fff;border-radius:10px;font-size:.8125rem;font-weight:600;text-decoration:none;transition:background .18s;"
               onmouseover="this.style.background='#16a34a'" onmouseout="this.style.background='#22c55e'">
                <svg width="14" height="14" fill="white" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                WhatsApp
            </a>
            <?php endif; ?>
            <button @click="openModal()"
                    style="padding:9px 20px;background:#0a0a0a;color:#fff;border:none;border-radius:10px;font-family:inherit;font-size:.8125rem;font-weight:600;cursor:pointer;"
                    onmouseover="this.style.background='#1f1f1f'" onmouseout="this.style.background='#0a0a0a'">
                Reservar Cita
            </button>
        </div>
    </div>
</nav>

<!-- ══════ HERO ══════ -->
<section style="padding-top:64px;background:#0c0f0e;color:#fff;overflow:hidden;position:relative;">

    <!-- Glow -->
    <div style="position:absolute;top:-80px;right:-60px;width:480px;height:480px;border-radius:50%;background:radial-gradient(circle,rgba(201,144,26,.16) 0%,transparent 70%);pointer-events:none;"></div>

    <div style="max-width:1200px;margin:0 auto;padding:64px 24px 72px;position:relative;z-index:1;">
        <div class="hero-grid" style="display:grid;grid-template-columns:1fr;gap:48px;align-items:center;">

            <!-- Left -->
            <div class="fade-up" style="order:2;">

                <?php if ($barber['is_featured']): ?>
                <div style="display:inline-flex;align-items:center;gap:6px;padding:5px 14px;border-radius:999px;background:rgba(201,144,26,.15);border:1px solid rgba(201,144,26,.35);margin-bottom:20px;">
                    <svg width="12" height="12" fill="#e8b84b" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    <span style="font-size:.75rem;font-weight:700;color:#e8b84b;letter-spacing:.04em;">BARBERO DESTACADO</span>
                </div>
                <?php else: ?>
                <div style="display:inline-flex;align-items:center;gap:8px;padding:5px 14px;border-radius:999px;background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.25);margin-bottom:20px;">
                    <span style="width:7px;height:7px;background:#22c55e;border-radius:50%;display:block;box-shadow:0 0 0 3px rgba(34,197,94,.3);"></span>
                    <span style="font-size:.75rem;font-weight:600;color:#4ade80;letter-spacing:.04em;">DISPONIBLE PARA RESERVAS</span>
                </div>
                <?php endif; ?>

                <h1 style="font-size:clamp(2.25rem,5.5vw,3.75rem);font-weight:900;line-height:1.04;letter-spacing:-.04em;margin:0 0 10px;color:#fff;">
                    <?php echo e($barber['full_name']); ?>
                </h1>

                <?php if ($barber['specialty']): ?>
                <p style="font-size:1.125rem;font-weight:600;color:#e8b84b;margin:0 0 22px;"><?php echo e($barber['specialty']); ?></p>
                <?php endif; ?>

                <!-- Meta pills -->
                <div style="display:flex;flex-wrap:wrap;align-items:center;gap:10px;margin-bottom:24px;">
                    <div style="display:flex;align-items:center;gap:4px;">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <svg width="17" height="17" viewBox="0 0 20 20" fill="<?php echo $i <= round($barber['avg_rating']) ? '#f59e0b' : '#374151'; ?>">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        <?php endfor; ?>
                        <span style="color:#fff;font-weight:700;margin-left:4px;"><?php echo number_format($barber['avg_rating'], 1); ?></span>
                        <span style="color:#6b7280;font-size:.8125rem;">(<?php echo $barber['total_reviews']; ?> reseñas)</span>
                    </div>
                    <span style="color:#374151;">·</span>
                    <span style="font-size:.8125rem;color:#9ca3af;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);padding:4px 12px;border-radius:999px;">
                        <?php echo $barber['experience_years']; ?> años exp.
                    </span>
                    <span style="font-size:.8125rem;color:#9ca3af;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);padding:4px 12px;border-radius:999px;">
                        <?php echo count($services); ?> servicios
                    </span>
                </div>

                <?php if ($barber['bio']): ?>
                <p style="font-size:1rem;color:#9ca3af;line-height:1.7;margin:0 0 32px;max-width:500px;"><?php echo nl2br(e($barber['bio'])); ?></p>
                <?php else: ?>
                <div style="margin-bottom:32px;"></div>
                <?php endif; ?>

                <!-- CTAs -->
                <div style="display:flex;flex-wrap:wrap;gap:12px;">
                    <button @click="openModal()"
                            class="btn-gold"
                            style="display:inline-flex;align-items:center;gap:8px;padding:14px 28px;border-radius:14px;font-family:'Sora',sans-serif;font-size:.9375rem;letter-spacing:-.01em;">
                        <svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Reservar Cita
                    </button>
                    <?php if ($barber['barber_phone']): ?>
                    <a href="https://wa.me/1<?php echo preg_replace('/[^0-9]/', '', $barber['barber_phone']); ?>?text=Hola%20<?php echo urlencode($barber['full_name']); ?>,%20quiero%20agendar%20una%20cita"
                       target="_blank"
                       style="display:inline-flex;align-items:center;gap:8px;padding:14px 28px;background:#22c55e;color:#fff;border-radius:14px;font-weight:600;font-size:.9375rem;text-decoration:none;transition:background .18s;"
                       onmouseover="this.style.background='#16a34a'" onmouseout="this.style.background='#22c55e'">
                        <svg width="17" height="17" fill="white" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                        WhatsApp
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right: Photo -->
            <div class="fade-up2" style="display:flex;justify-content:center;order:1;">
                <?php if ($barberPhoto): ?>
                <div style="position:relative;max-width:380px;width:100%;">
                    <div style="position:absolute;inset:-20px;background:radial-gradient(circle,rgba(201,144,26,.18) 0%,transparent 70%);border-radius:9999px;"></div>
                    <div style="position:relative;border-radius:24px;overflow:hidden;aspect-ratio:3/4;box-shadow:0 32px 80px rgba(0,0,0,.55);">
                        <img src="<?php echo $barberPhoto; ?>"
                             style="width:100%;height:100%;object-fit:cover;object-position:center top;display:block;"
                             alt="<?php echo e($barber['full_name']); ?>">
                        <div style="position:absolute;inset:0;background:linear-gradient(to top,rgba(0,0,0,.5) 0%,transparent 55%);"></div>
                    </div>
                </div>
                <?php else: ?>
                <div style="position:relative;width:280px;height:280px;">
                    <div style="position:absolute;inset:0;background:radial-gradient(circle,rgba(201,144,26,.15),transparent 70%);border-radius:50%;"></div>
                    <div style="position:relative;width:100%;height:100%;border-radius:24px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.1);display:flex;align-items:center;justify-content:center;">
                        <span style="font-family:'Sora',sans-serif;font-size:6rem;font-weight:900;color:rgba(255,255,255,.12);line-height:1;">
                            <?php echo strtoupper(substr($barber['full_name'], 0, 1)); ?>
                        </span>
                    </div>
                </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <!-- Wave -->
    <svg viewBox="0 0 1440 60" fill="none" xmlns="http://www.w3.org/2000/svg" style="display:block;width:100%;margin-top:-1px;">
        <path d="M0 60L1440 60L1440 18C1300 50 1100 60 900 55C700 50 500 20 300 12C150 6 60 30 0 38Z" fill="#f5f5f0"/>
    </svg>
</section>

<!-- ══════ SERVICES ══════ -->
<?php if (!empty($services)): ?>
<section style="background:#f5f5f0;padding:72px 24px 80px;">
    <div style="max-width:1200px;margin:0 auto;">
        <div style="text-align:center;margin-bottom:52px;">
            <span style="display:inline-block;padding:5px 14px;background:#fef9ee;border:1px solid #f0d88a;border-radius:999px;font-size:.6875rem;font-weight:700;letter-spacing:.1em;color:#a16207;text-transform:uppercase;margin-bottom:14px;">Especialidades</span>
            <h2 style="font-size:clamp(1.75rem,4vw,2.75rem);font-weight:900;color:#0a0a0a;letter-spacing:-.03em;margin:0 0 10px;">Mis Servicios</h2>
            <p style="font-size:1rem;color:#71717a;margin:0 auto;max-width:400px;">Servicios especializados con atención al detalle y técnica profesional</p>
        </div>

        <div class="svc-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:14px;">
            <?php foreach ($services as $service): ?>
            <div class="card" style="background:#fff;border:1.5px solid #ebebeb;border-radius:18px;padding:22px;">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:14px;">
                    <div style="width:44px;height:44px;background:#0a0a0a;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <svg width="22" height="22" fill="none" stroke="#e8b84b" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243 4.243 3 3 0 004.243-4.243zm0-5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z"/>
                        </svg>
                    </div>
                    <span style="font-family:'Sora',sans-serif;font-size:1.375rem;font-weight:900;color:#0a0a0a;"><?php echo formatPrice($service['price']); ?></span>
                </div>
                <h3 style="font-family:'Sora',sans-serif;font-size:1rem;font-weight:700;color:#0a0a0a;margin:0 0 6px;"><?php echo e($service['name']); ?></h3>
                <?php if ($service['description']): ?>
                <p style="font-size:.8125rem;color:#71717a;line-height:1.55;margin:0 0 14px;"><?php echo e($service['description']); ?></p>
                <?php endif; ?>
                <div style="display:flex;align-items:center;justify-content:space-between;padding-top:14px;border-top:1px solid #f5f5f0;">
                    <span style="display:inline-flex;align-items:center;gap:4px;font-size:.75rem;color:#a1a1aa;background:#f5f5f0;border:1px solid #ebebeb;padding:3px 10px;border-radius:999px;">
                        <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <?php echo $service['duration']; ?> min
                    </span>
                    <button @click="openModal()"
                            style="padding:7px 16px;background:#0a0a0a;color:#fff;border:none;border-radius:10px;font-size:.8125rem;font-weight:600;cursor:pointer;font-family:inherit;"
                            onmouseover="this.style.background='#1f1f1f'" onmouseout="this.style.background='#0a0a0a'">
                        Reservar
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ══════ REVIEWS ══════ -->
<?php if (!empty($reviews)): ?>
<section style="background:#fff;padding:72px 24px 80px;">
    <div style="max-width:1200px;margin:0 auto;">
        <div style="text-align:center;margin-bottom:52px;">
            <span style="display:inline-block;padding:5px 14px;background:#fef9ee;border:1px solid #f0d88a;border-radius:999px;font-size:.6875rem;font-weight:700;letter-spacing:.1em;color:#a16207;text-transform:uppercase;margin-bottom:14px;">Testimonios</span>
            <h2 style="font-size:clamp(1.75rem,4vw,2.75rem);font-weight:900;color:#0a0a0a;letter-spacing:-.03em;margin:0;">Lo Que Dicen Mis Clientes</h2>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:14px;">
            <?php foreach (array_slice($reviews, 0, 6) as $review): ?>
            <div class="card" style="background:#fafaf8;border:1.5px solid #f0f0ec;border-radius:18px;padding:22px;display:flex;flex-direction:column;">
                <div style="font-size:2rem;line-height:1;color:#f59e0b;font-family:Georgia,serif;margin-bottom:10px;">&ldquo;</div>
                <div style="display:flex;gap:2px;margin-bottom:10px;">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <svg width="13" height="13" viewBox="0 0 20 20" fill="<?php echo $i <= $review['rating'] ? '#f59e0b' : '#e5e7eb'; ?>">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    <?php endfor; ?>
                </div>
                <p style="font-size:.875rem;color:#52525b;line-height:1.65;flex:1;margin:0 0 16px;"><?php echo e($review['comment']); ?></p>
                <div style="display:flex;align-items:center;gap:10px;padding-top:14px;border-top:1px solid #f0f0ec;">
                    <div style="width:34px;height:34px;background:#0a0a0a;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.8125rem;flex-shrink:0;">
                        <?php echo strtoupper(substr($review['client_name'] ?? 'C', 0, 1)); ?>
                    </div>
                    <div>
                        <p style="font-weight:600;color:#0a0a0a;font-size:.875rem;margin:0;"><?php echo e($review['client_name'] ?? 'Cliente'); ?></p>
                        <p style="font-size:.75rem;color:#a1a1aa;margin:2px 0 0;">Verificado · <?php echo timeAgo($review['created_at']); ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ══════ FINAL CTA ══════ -->
<section style="background:#0a0a0a;padding:80px 24px;text-align:center;">
    <div style="max-width:560px;margin:0 auto;">
        <div style="width:40px;height:2px;background:linear-gradient(90deg,#c9901a,#e8b84b);margin:0 auto 28px;border-radius:999px;"></div>
        <h2 style="font-size:clamp(1.75rem,4vw,2.75rem);font-weight:900;color:#fff;letter-spacing:-.03em;margin:0 0 14px;">¿Listo para lucir increíble?</h2>
        <p style="font-size:1rem;color:#71717a;margin:0 0 36px;line-height:1.65;">
            Reserva tu cita con <?php echo explode(' ', $barber['full_name'])[0]; ?> y disfruta de un servicio de primera calidad
        </p>
        <div style="display:flex;flex-wrap:wrap;gap:12px;justify-content:center;">
            <button @click="openModal()"
                    class="btn-gold"
                    style="display:inline-flex;align-items:center;gap:8px;padding:14px 28px;border-radius:14px;font-family:'Sora',sans-serif;font-size:.9375rem;">
                <svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Reservar Ahora
            </button>
            <?php if ($barber['barber_phone']): ?>
            <a href="https://wa.me/1<?php echo preg_replace('/[^0-9]/', '', $barber['barber_phone']); ?>?text=Hola%20<?php echo urlencode($barber['full_name']); ?>,%20quiero%20agendar"
               target="_blank"
               style="display:inline-flex;align-items:center;gap:8px;padding:14px 28px;background:#22c55e;color:#fff;border-radius:14px;font-weight:600;font-size:.9375rem;text-decoration:none;"
               onmouseover="this.style.background='#16a34a'" onmouseout="this.style.background='#22c55e'">
                <svg width="17" height="17" fill="white" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                WhatsApp
            </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ══════ FOOTER ══════ -->
<footer style="background:#050505;padding:20px 24px;border-top:1px solid rgba(255,255,255,.05);">
    <div style="max-width:1200px;margin:0 auto;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;">
        <div style="display:flex;align-items:center;gap:10px;">
            <?php if ($barber['logo']): ?>
            <img src="<?php echo asset($barber['logo']); ?>" style="width:32px;height:32px;border-radius:50%;object-fit:cover;border:1.5px solid rgba(255,255,255,.1);" alt="Logo">
            <?php endif; ?>
            <div>
                <p style="font-weight:700;color:#fff;font-size:.875rem;margin:0;"><?php echo e($barber['business_name']); ?></p>
                <a href="<?php echo BASE_URL; ?>/public/<?php echo $shopSlug; ?>" style="font-size:.75rem;color:#52525b;text-decoration:none;transition:color .18s;"
                   onmouseover="this.style.color='#a1a1aa'" onmouseout="this.style.color='#52525b'">← Ver toda la barbería</a>
            </div>
        </div>
        <p style="font-size:.75rem;color:#3f3f46;margin:0;">Powered by <span style="font-weight:700;color:#71717a;">Kyros Barber Cloud</span></p>
    </div>
</footer>

<!-- ══════ MODAL ══════ -->
<div x-show="showBookingModal"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     style="position:fixed;inset:0;z-index:200;display:flex;align-items:flex-end;justify-content:center;"
     @keydown.escape.window="closeModal()"
     x-cloak>

    <div @click="closeModal()" style="position:absolute;inset:0;background:rgba(0,0,0,.72);backdrop-filter:blur(4px);-webkit-backdrop-filter:blur(4px);"></div>

    <div class="modal-anim modal-panel" style="position:relative;background:#fff;width:100%;max-width:520px;border-radius:24px 24px 0 0;overflow:hidden;max-height:92vh;overflow-y:auto;">
        <div style="height:3px;background:linear-gradient(90deg,#c9901a 0%,#e8b84b 50%,#c9901a 100%);"></div>

        <div style="padding:20px 24px 16px;border-bottom:1px solid #f0f0ec;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;background:#fff;z-index:10;">
            <div>
                <h3 style="font-family:'Sora',sans-serif;font-size:1.125rem;font-weight:800;color:#0a0a0a;margin:0;">Reservar Cita</h3>
                <p style="font-size:.8125rem;color:#71717a;margin:3px 0 0;">con <?php echo e($barber['full_name']); ?></p>
            </div>
            <button @click="closeModal()" style="width:32px;height:32px;background:#f5f5f0;border:none;border-radius:8px;cursor:pointer;display:flex;align-items:center;justify-content:center;"
                    onmouseover="this.style.background='#e5e5e0'" onmouseout="this.style.background='#f5f5f0'">
                <svg width="14" height="14" fill="none" stroke="#71717a" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form action="book.php" method="POST" style="padding:22px;display:flex;flex-direction:column;gap:16px;">
            <input type="hidden" name="barber_id" value="<?php echo $barber['id']; ?>">
            <input type="hidden" name="barbershop_id" value="<?php echo $barber['barbershop_id']; ?>">

            <div>
                <label style="display:block;font-size:.6875rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#71717a;margin-bottom:7px;">Servicio *</label>
                <select name="service_id" x-model="selectedService" @change="loadAvailability()" required class="inp">
                    <option value="">Seleccionar servicio...</option>
                    <?php foreach ($services as $service): ?>
                    <option value="<?php echo $service['id']; ?>"><?php echo e($service['name']); ?> — <?php echo formatPrice($service['price']); ?> (<?php echo $service['duration']; ?> min)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div>
                    <label style="display:block;font-size:.6875rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#71717a;margin-bottom:7px;">Fecha *</label>
                    <input type="date" name="appointment_date" x-model="selectedDate" @change="loadAvailability()" required min="<?php echo date('Y-m-d'); ?>" class="inp">
                </div>
                <div>
                    <label style="display:block;font-size:.6875rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#71717a;margin-bottom:7px;">Hora *</label>
                    <select name="start_time" x-model="selectedStartTime" required class="inp">
                        <option value="" x-text="availabilityLoading ? 'Cargando...' : 'Selecciona'"></option>
                        <template x-for="slot in availableSlots" :key="slot.value">
                            <option :value="slot.value" x-text="slot.time"></option>
                        </template>
                    </select>
                    <p x-show="availabilityMessage" style="font-size:.75rem;color:#d97706;margin:5px 0 0;" x-text="availabilityMessage"></p>
                </div>
            </div>

            <div style="border-top:1px solid #f0f0ec;padding-top:2px;">
                <p style="font-size:.6875rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#71717a;margin:0 0 12px;">Tus datos</p>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                    <input type="text" name="client_name" required placeholder="Nombre completo" class="inp" style="grid-column:1/-1;">
                    <input type="tel" name="client_phone" required placeholder="(809) 000-0000" class="inp">
                    <input type="email" name="client_email" placeholder="Email (opcional)" class="inp">
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;padding-top:4px;">
                <button type="button" @click="closeModal()"
                        style="padding:12px;border:1.5px solid #e5e5e2;background:#fff;border-radius:12px;font-family:inherit;font-size:.875rem;font-weight:600;color:#52525b;cursor:pointer;"
                        onmouseover="this.style.background='#f5f5f0'" onmouseout="this.style.background='#fff'">
                    Cancelar
                </button>
                <button type="submit"
                        style="padding:12px;background:#0a0a0a;border:none;border-radius:12px;font-family:'Sora',sans-serif;font-size:.875rem;font-weight:700;color:#fff;cursor:pointer;"
                        onmouseover="this.style.background='#1f1f1f'" onmouseout="this.style.background='#0a0a0a'">
                    Confirmar Reserva
                </button>
            </div>
        </form>
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
        intervalMinutes: 15,
        openModal()  { this.showBookingModal = true; document.body.style.overflow='hidden'; },
        closeModal() { this.showBookingModal = false; document.body.style.overflow=''; },
        async loadAvailability() {
            this.availableSlots = []; this.occupiedSlots = [];
            this.selectedStartTime = ''; this.availabilityMessage = '';
            if (!this.selectedDate || !this.selectedService) {
                if (this.selectedDate && !this.selectedService) this.availabilityMessage = 'Selecciona un servicio para ver horas.';
                return;
            }
            this.availabilityLoading = true;
            try {
                const r = await fetch('<?php echo BASE_URL; ?>/api/availability.php?' + new URLSearchParams({
                    barber_id: '<?php echo (int) $barber['id']; ?>',
                    date: this.selectedDate, service_id: this.selectedService
                }));
                const d = await r.json();
                if (!d.success) { this.availabilityMessage = d.message || 'No se pudo cargar'; return; }
                this.availableSlots = Array.isArray(d.available_slots) ? d.available_slots : [];
                this.occupiedSlots  = Array.isArray(d.occupied_slots)  ? d.occupied_slots  : [];
                this.intervalMinutes = d.interval_minutes || 15;
                if (!this.availableSlots.length) this.availabilityMessage = d.message || 'No hay horarios disponibles';
            } catch(e) { this.availabilityMessage = 'Error al consultar horarios'; }
            finally { this.availabilityLoading = false; }
        }
    };
}
</script>
</body>
</html>
