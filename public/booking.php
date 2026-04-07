<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Helpers.php';

$slug = $_GET['shop'] ?? '';
if (empty($slug)) die('Barberia no especificada');

$db = Database::getInstance();

$barbershop = $db->fetch("
    SELECT b.*, l.type as license_type, l.status as license_status
    FROM barbershops b
    JOIN licenses l ON b.license_id = l.id
    WHERE b.slug = ? AND b.status = 'active'
", [$slug]);

if (!$barbershop || !isLicenseActive($barbershop['license_id'])) die('Barberia no disponible');

$barbers = $db->fetchAll("
    SELECT b.*, u.full_name, u.phone, COALESCE(NULLIF(b.photo, ''), NULLIF(u.avatar, '')) as public_photo
    FROM barbers b JOIN users u ON b.user_id = u.id
    WHERE b.barbershop_id = ? AND b.status = 'active'
    ORDER BY b.is_featured DESC, b.rating DESC
", [$barbershop['id']]);

$services = $db->fetchAll("
    SELECT * FROM services WHERE barbershop_id = ? AND is_active = TRUE
    ORDER BY display_order ASC, name ASC
", [$barbershop['id']]);

$servicesByCategory = [];
foreach ($services as $service) {
    $cat = $service['category'] ?? 'General';
    $servicesByCategory[$cat][] = $service;
}

$schedules = $db->fetchAll("
    SELECT * FROM barbershop_schedules WHERE barbershop_id = ? ORDER BY day_of_week ASC
", [$barbershop['id']]);

$reviews = $db->fetchAll("
    SELECT r.*, c.name as client_name FROM reviews r
    LEFT JOIN clients c ON r.client_id = c.id
    WHERE r.barbershop_id = ? AND r.is_verified = TRUE AND r.is_visible = TRUE
    ORDER BY r.created_at DESC
", [$barbershop['id']]);

$avgRating = $db->fetch("
    SELECT COALESCE(AVG(rating), 5.0) as avg_rating, COUNT(*) as total_reviews
    FROM reviews WHERE barbershop_id = ? AND is_verified = TRUE AND is_visible = TRUE
", [$barbershop['id']]);

$isOpenNow = false;
$currentDayOfWeek = (int) date('w');
$currentTime = date('H:i:s');
foreach ($schedules as $schedule) {
    if ((int)$schedule['day_of_week'] === $currentDayOfWeek && !$schedule['is_closed']) {
        if ($currentTime >= $schedule['open_time'] && $currentTime <= $schedule['close_time']) {
            $isOpenNow = true; break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($barbershop['business_name']); ?> — Reserva tu cita</title>
    <meta name="description" content="<?php echo htmlspecialchars($barbershop['description'] ?? ''); ?>">
    <!-- Tailwind config BEFORE CDN -->
    <script>
        window.tailwind = window.tailwind || {};
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { display: ['Sora','sans-serif'], sans: ['Inter','sans-serif'] }
                }
            }
        };
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Sora:wght@700;800;900&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f5f0; margin: 0; }
        h1, h2, h3, h4 { font-family: 'Sora', sans-serif; }

        /* ── Animations ── */
        @keyframes fadeUp   { from { opacity:0; transform:translateY(24px); } to { opacity:1; transform:translateY(0); } }
        @keyframes fadeIn   { from { opacity:0; } to { opacity:1; } }
        @keyframes pulse2   { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.4;transform:scale(1.6)} }
        @keyframes shimmer  { 0%{transform:translateX(-100%)} 100%{transform:translateX(100%)} }
        @keyframes modalUp  { from{opacity:0;transform:translateY(32px) scale(.97)} to{opacity:1;transform:translateY(0) scale(1)} }

        .fade-up   { animation: fadeUp  .65s cubic-bezier(.16,1,.3,1) both; }
        .fade-up-d { animation: fadeUp  .65s .12s cubic-bezier(.16,1,.3,1) both; }
        .fade-in   { animation: fadeIn  .5s ease both; }
        .modal-anim { animation: modalUp .28s cubic-bezier(.16,1,.3,1) both; }

        /* ── Pulse dot ── */
        .pulse-ring {
            position:relative;
            display:inline-flex; align-items:center; justify-content:center;
        }
        .pulse-ring::before {
            content:''; position:absolute; inset:-5px;
            border-radius:9999px; background:currentColor;
            opacity:.3; animation:pulse2 2s ease-in-out infinite;
        }

        /* ── Cards ── */
        .card { transition:transform .22s cubic-bezier(.4,0,.2,1), box-shadow .22s cubic-bezier(.4,0,.2,1); }
        .card:hover { transform:translateY(-5px); box-shadow:0 24px 48px rgba(0,0,0,.10); }

        /* ── Barber card image zoom ── */
        .barber-img { transition:transform .5s cubic-bezier(.4,0,.2,1); }
        .barber-card:hover .barber-img { transform:scale(1.06); }

        /* ── Shimmer button ── */
        .btn-gold {
            position:relative; overflow:hidden;
            background:linear-gradient(135deg,#c9901a 0%,#e8b84b 50%,#c9901a 100%);
            color:#0a0a0a; font-weight:700;
            box-shadow:0 4px 24px rgba(201,144,26,.35);
            transition:box-shadow .2s,transform .2s;
        }
        .btn-gold::after {
            content:''; position:absolute; top:0; left:-100%; width:100%; height:100%;
            background:linear-gradient(90deg,transparent,rgba(255,255,255,.25),transparent);
            transition:none;
        }
        .btn-gold:hover::after { animation:shimmer .6s ease forwards; }
        .btn-gold:hover { box-shadow:0 6px 32px rgba(201,144,26,.50); transform:translateY(-1px); }

        /* ── Select reset ── */
        select {
            -webkit-appearance:none; -moz-appearance:none; appearance:none;
            background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%239ca3af' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E");
            background-repeat:no-repeat; background-position:right 12px center; background-size:16px;
            padding-right:40px;
        }

        /* ── Input focus ── */
        .inp {
            width:100%; padding:11px 14px;
            background:#f9f9f7; border:1.5px solid #e5e5e2; border-radius:12px;
            font-size:.875rem; color:#111; transition:border-color .18s,box-shadow .18s;
            font-family:inherit;
        }
        .inp:focus { outline:none; border-color:#c9901a; box-shadow:0 0 0 3px rgba(201,144,26,.15); }
        .inp::placeholder { color:#aaa; }

        /* ── Wave divider ── */
        .wave-top { display:block; width:100%; }

        /* ── Stars ── */
        .star-filled { color:#f59e0b; }
        .star-empty  { color:#e5e7eb; }
    </style>
</head>
<body x-data="bookingApp()">

<!-- ═══════════════════════════════════════════
     NAVBAR
══════════════════════════════════════════════ -->
<nav style="position:fixed;top:0;left:0;right:0;z-index:100;background:rgba(255,255,255,.96);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);border-bottom:1px solid #f0f0ec;">
    <div style="max-width:1200px;margin:0 auto;padding:0 24px;display:flex;align-items:center;justify-content:space-between;height:64px;">

        <!-- Brand -->
        <div style="display:flex;align-items:center;gap:12px;">
            <?php if ($barbershop['logo']): ?>
            <img src="<?php echo asset($barbershop['logo']); ?>"
                 style="width:36px;height:36px;border-radius:50%;object-fit:cover;border:2px solid #f0d88a;"
                 alt="Logo">
            <?php endif; ?>
            <span style="font-family:'Sora',sans-serif;font-weight:800;font-size:1rem;color:#0a0a0a;letter-spacing:-.02em;">
                <?php echo htmlspecialchars($barbershop['business_name']); ?>
            </span>
        </div>

        <!-- Actions -->
        <div style="display:flex;align-items:center;gap:10px;">
            <?php if ($barbershop['phone']): ?>
            <a href="https://wa.me/1<?php echo preg_replace('/[^0-9]/', '', $barbershop['phone']); ?>?text=Hola,%20quiero%20reservar%20una%20cita"
               target="_blank"
               style="display:none;"
               class="sm-show"
               class="whatsapp-btn"
               style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:#22c55e;color:#fff;border-radius:10px;font-size:.8125rem;font-weight:600;text-decoration:none;transition:background .18s;">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                WhatsApp
            </a>
            <?php endif; ?>
            <button @click="openBookingModal()"
                    style="padding:9px 20px;background:#0a0a0a;color:#fff;border:none;border-radius:10px;font-family:'Inter',sans-serif;font-size:.8125rem;font-weight:600;cursor:pointer;transition:background .18s;"
                    onmouseover="this.style.background='#1f1f1f'"
                    onmouseout="this.style.background='#0a0a0a'">
                Reservar Cita
            </button>
        </div>
    </div>
</nav>

<!-- ═══════════════════════════════════════════
     HERO — dark luxury
══════════════════════════════════════════════ -->
<section style="padding-top:64px;background:#0c0f0e;color:#fff;overflow:hidden;position:relative;">

    <!-- Subtle radial glow -->
    <div style="position:absolute;top:-120px;right:-80px;width:520px;height:520px;border-radius:50%;background:radial-gradient(circle,rgba(201,144,26,.18) 0%,transparent 70%);pointer-events:none;"></div>
    <div style="position:absolute;bottom:40px;left:-60px;width:380px;height:380px;border-radius:50%;background:radial-gradient(circle,rgba(201,144,26,.08) 0%,transparent 70%);pointer-events:none;"></div>

    <div style="max-width:1200px;margin:0 auto;padding:72px 24px 80px;position:relative;z-index:1;">
        <div style="display:grid;grid-template-columns:1fr;gap:48px;align-items:center;" class="hero-grid">
            <!-- Left -->
            <div class="fade-up" style="max-width:620px;">

                <!-- Status -->
                <?php if ($isOpenNow): ?>
                <div style="display:inline-flex;align-items:center;gap:8px;padding:6px 14px;border-radius:999px;background:rgba(34,197,94,.12);border:1px solid rgba(34,197,94,.3);margin-bottom:24px;">
                    <span class="pulse-ring" style="color:#22c55e;display:inline-block;width:8px;height:8px;">
                        <span style="width:8px;height:8px;background:#22c55e;border-radius:50%;display:block;position:relative;z-index:1;"></span>
                    </span>
                    <span style="font-size:.75rem;font-weight:600;color:#4ade80;letter-spacing:.04em;">ABIERTO AHORA</span>
                </div>
                <?php else: ?>
                <div style="display:inline-flex;align-items:center;gap:8px;padding:6px 14px;border-radius:999px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);margin-bottom:24px;">
                    <span style="width:8px;height:8px;background:#6b7280;border-radius:50%;display:block;"></span>
                    <span style="font-size:.75rem;font-weight:600;color:#9ca3af;letter-spacing:.04em;">CERRADO AHORA</span>
                </div>
                <?php endif; ?>

                <!-- Shop name -->
                <h1 style="font-size:clamp(2.5rem,6vw,4.25rem);font-weight:900;line-height:1.03;letter-spacing:-.04em;margin:0 0 20px;color:#ffffff;">
                    <?php echo htmlspecialchars($barbershop['business_name']); ?>
                </h1>

                <?php if ($barbershop['description']): ?>
                <p style="font-size:1.0625rem;color:#9ca3af;line-height:1.7;margin:0 0 28px;max-width:480px;">
                    <?php echo htmlspecialchars($barbershop['description']); ?>
                </p>
                <?php endif; ?>

                <!-- Rating -->
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:32px;">
                    <div style="display:flex;gap:2px;">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <svg width="18" height="18" viewBox="0 0 20 20" fill="<?php echo $i <= round($avgRating['avg_rating']) ? '#f59e0b' : '#374151'; ?>">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        <?php endfor; ?>
                    </div>
                    <span style="font-weight:700;color:#fff;font-size:.9375rem;"><?php echo number_format($avgRating['avg_rating'], 1); ?></span>
                    <span style="color:#6b7280;font-size:.875rem;">&middot; <?php echo $avgRating['total_reviews']; ?> reseñas</span>
                </div>

                <!-- Stats -->
                <div style="display:flex;gap:12px;margin-bottom:40px;flex-wrap:wrap;">
                    <div style="padding:16px 24px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.09);border-radius:16px;text-align:center;min-width:80px;">
                        <div style="font-family:'Sora',sans-serif;font-size:2rem;font-weight:900;color:#fff;line-height:1;"><?php echo count($barbers); ?></div>
                        <div style="font-size:.6875rem;color:#6b7280;margin-top:4px;text-transform:uppercase;letter-spacing:.06em;">Barberos</div>
                    </div>
                    <div style="padding:16px 24px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.09);border-radius:16px;text-align:center;min-width:80px;">
                        <div style="font-family:'Sora',sans-serif;font-size:2rem;font-weight:900;color:#fff;line-height:1;"><?php echo count($services); ?>+</div>
                        <div style="font-size:.6875rem;color:#6b7280;margin-top:4px;text-transform:uppercase;letter-spacing:.06em;">Servicios</div>
                    </div>
                    <div style="padding:16px 24px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.09);border-radius:16px;text-align:center;min-width:80px;">
                        <div style="font-family:'Sora',sans-serif;font-size:2rem;font-weight:900;color:#fff;line-height:1;"><?php echo $avgRating['total_reviews']; ?></div>
                        <div style="font-size:.6875rem;color:#6b7280;margin-top:4px;text-transform:uppercase;letter-spacing:.06em;">Clientes</div>
                    </div>
                </div>

                <!-- CTAs -->
                <div style="display:flex;gap:12px;flex-wrap:wrap;">
                    <button @click="openBookingModal()"
                            class="btn-gold"
                            style="display:inline-flex;align-items:center;gap:8px;padding:14px 28px;border:none;border-radius:14px;font-family:'Sora',sans-serif;font-size:1rem;cursor:pointer;letter-spacing:-.01em;">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Reservar Ahora
                    </button>
                    <?php if ($barbershop['phone']): ?>
                    <a href="tel:<?php echo htmlspecialchars($barbershop['phone']); ?>"
                       style="display:inline-flex;align-items:center;gap:8px;padding:14px 28px;background:rgba(255,255,255,.07);border:1.5px solid rgba(255,255,255,.14);color:#fff;border-radius:14px;font-size:1rem;font-weight:600;text-decoration:none;transition:background .18s;"
                       onmouseover="this.style.background='rgba(255,255,255,.11)'"
                       onmouseout="this.style.background='rgba(255,255,255,.07)'">
                        <?php echo htmlspecialchars($barbershop['phone']); ?>
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right: Cover image -->
            <?php if ($barbershop['cover_image']): ?>
            <div class="fade-up-d" style="display:flex;justify-content:flex-end;">
                <div style="position:relative;max-width:460px;width:100%;">
                    <div style="position:absolute;inset:-16px;background:radial-gradient(circle,rgba(201,144,26,.2) 0%,transparent 70%);border-radius:32px;"></div>
                    <div style="position:relative;border-radius:24px;overflow:hidden;aspect-ratio:4/5;box-shadow:0 32px 80px rgba(0,0,0,.5);">
                        <img src="<?php echo asset($barbershop['cover_image']); ?>"
                             style="width:100%;height:100%;object-fit:cover;"
                             alt="<?php echo htmlspecialchars($barbershop['business_name']); ?>">
                        <div style="position:absolute;inset:0;background:linear-gradient(to top,rgba(0,0,0,.45) 0%,transparent 60%);"></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Wave to white -->
    <svg class="wave-top" viewBox="0 0 1440 60" fill="none" xmlns="http://www.w3.org/2000/svg" style="display:block;margin-top:-1px;">
        <path d="M0 60L1440 60L1440 18C1300 50 1100 60 900 55C700 50 500 20 300 12C150 6 60 30 0 38Z" fill="#f5f5f0"/>
    </svg>
</section>

<!-- ═══════════════════════════════════════════
     STEPS
══════════════════════════════════════════════ -->
<section style="background:#f5f5f0;padding:72px 24px 80px;">
    <div style="max-width:960px;margin:0 auto;">
        <div style="text-align:center;margin-bottom:56px;">
            <span style="display:inline-block;padding:5px 14px;background:#fef9ee;border:1px solid #f0d88a;border-radius:999px;font-size:.6875rem;font-weight:700;letter-spacing:.1em;color:#a16207;text-transform:uppercase;margin-bottom:14px;">Sencillo y Rápido</span>
            <h2 style="font-size:clamp(1.75rem,4vw,2.75rem);font-weight:900;color:#0a0a0a;letter-spacing:-.03em;margin:0;">Reserva en 3 pasos</h2>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:24px;">
            <?php
            $steps = [
                ['icon'=>'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z', 'n'=>'01', 'title'=>'Elige tu servicio', 'desc'=>'Selecciona el corte o servicio que deseas de nuestro menú', 'accent'=>false],
                ['icon'=>'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z', 'n'=>'02', 'title'=>'Escoge tu barbero', 'desc'=>'Elige al profesional de confianza con quien quieres tu cita', 'accent'=>true],
                ['icon'=>'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'n'=>'03', 'title'=>'Confirma tu cita', 'desc'=>'Ingresa tus datos y recibirás confirmación inmediata', 'accent'=>false],
            ];
            foreach ($steps as $step): ?>
            <div style="background:#fff;border-radius:20px;padding:32px 24px;text-align:center;border:1px solid #ebebeb;transition:box-shadow .2s,transform .2s;cursor:default;"
                 onmouseover="this.style.boxShadow='0 16px 40px rgba(0,0,0,.08)';this.style.transform='translateY(-4px)'"
                 onmouseout="this.style.boxShadow='';this.style.transform=''">
                <div style="width:52px;height:52px;border-radius:14px;background:<?php echo $step['accent'] ? 'linear-gradient(135deg,#c9901a,#e8b84b)' : '#0a0a0a'; ?>;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                    <svg width="24" height="24" fill="none" stroke="<?php echo $step['accent'] ? '#0a0a0a' : '#f59e0b'; ?>" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="<?php echo $step['icon']; ?>"/>
                    </svg>
                </div>
                <div style="font-family:'Sora',sans-serif;font-size:3.5rem;font-weight:900;color:#f0f0ec;line-height:1;margin-bottom:8px;"><?php echo $step['n']; ?></div>
                <h3 style="font-family:'Sora',sans-serif;font-size:1.0625rem;font-weight:700;color:#0a0a0a;margin:0 0 8px;"><?php echo $step['title']; ?></h3>
                <p style="font-size:.875rem;color:#71717a;line-height:1.6;margin:0;"><?php echo $step['desc']; ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════
     SERVICES
══════════════════════════════════════════════ -->
<section style="background:#fff;padding:80px 24px;">
    <div style="max-width:1200px;margin:0 auto;">
        <div style="text-align:center;margin-bottom:60px;">
            <span style="display:inline-block;padding:5px 14px;background:#fef9ee;border:1px solid #f0d88a;border-radius:999px;font-size:.6875rem;font-weight:700;letter-spacing:.1em;color:#a16207;text-transform:uppercase;margin-bottom:14px;">Servicios</span>
            <h2 style="font-size:clamp(2rem,5vw,3rem);font-weight:900;color:#0a0a0a;letter-spacing:-.03em;margin:0 0 12px;">Nuestros Servicios</h2>
            <p style="font-size:1.0625rem;color:#71717a;max-width:480px;margin:0 auto;line-height:1.6;">Servicios profesionales de barbería diseñados para darte el mejor look</p>
        </div>

        <?php foreach ($servicesByCategory as $category => $categoryServices): ?>
        <div style="margin-bottom:56px;">
            <div style="display:flex;align-items:center;gap:16px;margin-bottom:28px;">
                <h3 style="font-family:'Sora',sans-serif;font-size:1.125rem;font-weight:700;color:#0a0a0a;margin:0;white-space:nowrap;"><?php echo htmlspecialchars($category); ?></h3>
                <div style="flex:1;height:1px;background:#f0f0ec;"></div>
                <span style="font-size:.75rem;color:#a1a1aa;font-weight:500;white-space:nowrap;"><?php echo count($categoryServices); ?> servicio<?php echo count($categoryServices) > 1 ? 's' : ''; ?></span>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;">
                <?php foreach ($categoryServices as $service): ?>
                <div class="card" style="background:#fff;border:1.5px solid #f0f0ec;border-radius:20px;overflow:hidden;cursor:pointer;"
                     onclick="bookingApp_instance.selectService(<?php echo $service['id']; ?>)">
                    <!-- Image area -->
                    <?php if ($service['image']): ?>
                    <div style="height:180px;overflow:hidden;">
                        <img src="<?php echo asset($service['image']); ?>" style="width:100%;height:100%;object-fit:cover;transition:transform .4s;" alt="<?php echo htmlspecialchars($service['name']); ?>">
                    </div>
                    <?php else: ?>
                    <div style="height:140px;background:linear-gradient(135deg,#0a0a0a 0%,#1f1f1f 100%);display:flex;align-items:center;justify-content:center;">
                        <svg width="48" height="48" fill="none" stroke="rgba(255,255,255,.15)" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243 4.243 3 3 0 004.243-4.243zm0-5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z"/>
                        </svg>
                    </div>
                    <?php endif; ?>

                    <div style="padding:20px;">
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;margin-bottom:6px;">
                            <h4 style="font-family:'Sora',sans-serif;font-size:1.0625rem;font-weight:700;color:#0a0a0a;margin:0;line-height:1.3;"><?php echo htmlspecialchars($service['name']); ?></h4>
                            <span style="flex-shrink:0;display:inline-flex;align-items:center;gap:3px;padding:3px 10px;background:#f5f5f0;border-radius:999px;font-size:.6875rem;color:#71717a;border:1px solid #ebebeb;">
                                <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <?php echo $service['duration']; ?>m
                            </span>
                        </div>
                        <?php if ($service['description']): ?>
                        <p style="font-size:.8125rem;color:#71717a;line-height:1.55;margin:0 0 16px;"><?php echo htmlspecialchars($service['description']); ?></p>
                        <?php endif; ?>
                        <div style="display:flex;align-items:center;justify-content:space-between;padding-top:14px;border-top:1px solid #f5f5f0;">
                            <span style="font-family:'Sora',sans-serif;font-size:1.5rem;font-weight:900;color:#0a0a0a;"><?php echo formatPrice($service['price']); ?></span>
                            <button @click.stop="selectService(<?php echo $service['id']; ?>)"
                                    style="padding:9px 18px;background:#0a0a0a;color:#fff;border:none;border-radius:10px;font-size:.8125rem;font-weight:600;cursor:pointer;transition:background .18s;font-family:inherit;"
                                    onmouseover="this.style.background='#1f1f1f'"
                                    onmouseout="this.style.background='#0a0a0a'">
                                Seleccionar
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- ═══════════════════════════════════════════
     BARBERS
══════════════════════════════════════════════ -->
<section style="background:#f5f5f0;padding:80px 24px;">
    <div style="max-width:1200px;margin:0 auto;">
        <div style="text-align:center;margin-bottom:60px;">
            <span style="display:inline-block;padding:5px 14px;background:#fef9ee;border:1px solid #f0d88a;border-radius:999px;font-size:.6875rem;font-weight:700;letter-spacing:.1em;color:#a16207;text-transform:uppercase;margin-bottom:14px;">Nuestro Equipo</span>
            <h2 style="font-size:clamp(2rem,5vw,3rem);font-weight:900;color:#0a0a0a;letter-spacing:-.03em;margin:0 0 12px;">Barberos Profesionales</h2>
            <p style="font-size:1.0625rem;color:#71717a;margin:0;">Expertos apasionados por su trabajo</p>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:20px;">
            <?php foreach ($barbers as $barber): ?>
            <div class="barber-card" style="background:#fff;border-radius:24px;overflow:hidden;border:1.5px solid #ebebeb;display:flex;flex-direction:column;transition:box-shadow .22s,transform .22s;"
                 onmouseover="this.style.boxShadow='0 20px 48px rgba(0,0,0,.10)';this.style.transform='translateY(-5px)'"
                 onmouseout="this.style.boxShadow='';this.style.transform=''">

                <!-- Photo -->
                <div style="height:280px;background:#e5e5e0;overflow:hidden;position:relative;">
                    <img src="<?php echo !empty($barber['public_photo']) ? imageUrl($barber['public_photo']) : getDefaultAvatar($barber['full_name']); ?>"
                         class="barber-img"
                         style="width:100%;height:100%;object-fit:cover;object-position:center top;display:block;"
                         alt="<?php echo htmlspecialchars($barber['full_name']); ?>">
                    <div style="position:absolute;inset:0;background:linear-gradient(to top,rgba(0,0,0,.55) 0%,transparent 50%);"></div>

                    <?php if ($barber['is_featured']): ?>
                    <div style="position:absolute;top:12px;left:12px;padding:4px 10px;background:linear-gradient(135deg,#c9901a,#e8b84b);border-radius:999px;font-size:.6875rem;font-weight:700;color:#0a0a0a;display:inline-flex;align-items:center;gap:4px;">
                        <svg width="10" height="10" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        Destacado
                    </div>
                    <?php endif; ?>

                    <!-- Name on image -->
                    <div style="position:absolute;bottom:0;left:0;right:0;padding:16px;">
                        <p style="font-family:'Sora',sans-serif;font-size:1.25rem;font-weight:800;color:#fff;margin:0;text-shadow:0 2px 8px rgba(0,0,0,.4);">
                            <?php echo htmlspecialchars($barber['full_name']); ?>
                        </p>
                        <?php if (!empty($barber['specialty'])): ?>
                        <p style="font-size:.8125rem;color:rgba(255,255,255,.7);margin:3px 0 0;"><?php echo htmlspecialchars($barber['specialty']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Info -->
                <div style="padding:18px;flex:1;display:flex;flex-direction:column;gap:12px;">
                    <!-- Rating row -->
                    <div style="display:flex;align-items:center;justify-content:space-between;">
                        <div style="display:flex;align-items:center;gap:4px;">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <svg width="15" height="15" viewBox="0 0 20 20" fill="<?php echo $i <= round($barber['rating']) ? '#f59e0b' : '#e5e7eb'; ?>">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                            <?php endfor; ?>
                            <span style="font-weight:700;color:#0a0a0a;font-size:.875rem;margin-left:2px;"><?php echo number_format($barber['rating'], 1); ?></span>
                        </div>
                        <span style="font-size:.75rem;color:#71717a;background:#f5f5f0;padding:3px 10px;border-radius:999px;border:1px solid #ebebeb;">
                            <?php echo intval($barber['experience_years'] ?? 0); ?> años exp.
                        </span>
                    </div>

                    <!-- Buttons -->
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:auto;">
                        <a href="<?php echo BASE_URL; ?>/public/<?php echo urlencode($barbershop['slug']); ?>/<?php echo urlencode($barber['slug']); ?>"
                           style="display:flex;align-items:center;justify-content:center;padding:10px;border:1.5px solid #e5e5e2;border-radius:12px;font-size:.8125rem;font-weight:600;color:#3f3f46;text-decoration:none;transition:border-color .18s,color .18s;text-align:center;"
                           onmouseover="this.style.borderColor='#0a0a0a';this.style.color='#0a0a0a'"
                           onmouseout="this.style.borderColor='#e5e5e2';this.style.color='#3f3f46'">
                            Ver Perfil
                        </a>
                        <button @click="selectBarber(<?php echo $barber['id']; ?>)"
                                style="padding:10px;background:#0a0a0a;color:#fff;border:none;border-radius:12px;font-size:.8125rem;font-weight:600;cursor:pointer;transition:background .18s;font-family:inherit;"
                                onmouseover="this.style.background='#1f1f1f'"
                                onmouseout="this.style.background='#0a0a0a'">
                            Reservar
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════
     REVIEWS
══════════════════════════════════════════════ -->
<?php if (!empty($reviews)): ?>
<section style="background:#fff;padding:80px 24px;">
    <div style="max-width:1200px;margin:0 auto;">
        <div style="text-align:center;margin-bottom:56px;">
            <span style="display:inline-block;padding:5px 14px;background:#fef9ee;border:1px solid #f0d88a;border-radius:999px;font-size:.6875rem;font-weight:700;letter-spacing:.1em;color:#a16207;text-transform:uppercase;margin-bottom:14px;">Testimonios</span>
            <h2 style="font-size:clamp(2rem,5vw,3rem);font-weight:900;color:#0a0a0a;letter-spacing:-.03em;margin:0;">Lo Que Dicen Nuestros Clientes</h2>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;">
            <?php foreach (array_slice($reviews, 0, 6) as $review): ?>
            <div class="card" style="background:#fafaf8;border:1.5px solid #f0f0ec;border-radius:20px;padding:24px;display:flex;flex-direction:column;">
                <div style="font-size:2.5rem;line-height:1;color:#f59e0b;font-family:Georgia,serif;margin-bottom:12px;">&ldquo;</div>
                <div style="display:flex;gap:2px;margin-bottom:10px;">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <svg width="14" height="14" viewBox="0 0 20 20" fill="<?php echo $i <= $review['rating'] ? '#f59e0b' : '#e5e7eb'; ?>">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    <?php endfor; ?>
                </div>
                <p style="font-size:.875rem;color:#52525b;line-height:1.65;flex:1;margin:0 0 18px;"><?php echo htmlspecialchars($review['comment']); ?></p>
                <div style="display:flex;align-items:center;gap:10px;padding-top:16px;border-top:1px solid #f0f0ec;">
                    <div style="width:36px;height:36px;background:#0a0a0a;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.8125rem;flex-shrink:0;">
                        <?php echo strtoupper(substr($review['client_name'] ?? 'C', 0, 1)); ?>
                    </div>
                    <div>
                        <p style="font-weight:600;color:#0a0a0a;font-size:.875rem;margin:0;"><?php echo htmlspecialchars($review['client_name'] ?? 'Cliente'); ?></p>
                        <p style="font-size:.75rem;color:#a1a1aa;margin:2px 0 0;">Cliente verificado</p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ═══════════════════════════════════════════
     WHATSAPP CTA
══════════════════════════════════════════════ -->
<?php if ($barbershop['phone']): ?>
<section style="background:#16a34a;padding:72px 24px;text-align:center;">
    <div style="max-width:600px;margin:0 auto;">
        <div style="width:64px;height:64px;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.2);border-radius:20px;display:flex;align-items:center;justify-content:center;margin:0 auto 24px;">
            <svg width="32" height="32" fill="white" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
        </div>
        <h2 style="font-family:'Sora',sans-serif;font-size:clamp(1.75rem,4vw,2.5rem);font-weight:900;color:#fff;margin:0 0 12px;letter-spacing:-.03em;">¿Prefieres reservar por WhatsApp?</h2>
        <p style="font-size:1.0625rem;color:rgba(255,255,255,.75);margin:0 0 32px;">Contáctanos directamente para atención personalizada</p>
        <a href="https://wa.me/1<?php echo preg_replace('/[^0-9]/', '', $barbershop['phone']); ?>?text=Hola,%20quiero%20reservar%20una%20cita%20en%20<?php echo urlencode($barbershop['business_name']); ?>"
           target="_blank"
           style="display:inline-flex;align-items:center;gap:10px;padding:16px 32px;background:#fff;color:#16a34a;border-radius:16px;font-family:'Sora',sans-serif;font-size:1.0625rem;font-weight:700;text-decoration:none;box-shadow:0 8px 32px rgba(0,0,0,.15);transition:transform .18s,box-shadow .18s;"
           onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 12px 40px rgba(0,0,0,.2)'"
           onmouseout="this.style.transform='';this.style.boxShadow='0 8px 32px rgba(0,0,0,.15)'">
            <svg width="22" height="22" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
            Chatear en WhatsApp
        </a>
    </div>
</section>
<?php endif; ?>

<!-- ═══════════════════════════════════════════
     FOOTER
══════════════════════════════════════════════ -->
<footer style="background:#0a0a0a;color:#fff;padding:64px 24px 32px;">
    <div style="max-width:1200px;margin:0 auto;">
        <div style="display:grid;grid-template-columns:1fr;gap:40px;margin-bottom:48px;" class="footer-grid">

            <!-- Brand -->
            <div>
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
                    <?php if ($barbershop['logo']): ?>
                    <img src="<?php echo asset($barbershop['logo']); ?>" style="width:44px;height:44px;border-radius:50%;object-fit:cover;border:2px solid rgba(255,255,255,.1);" alt="Logo">
                    <?php endif; ?>
                    <h3 style="font-family:'Sora',sans-serif;font-size:1.125rem;font-weight:800;color:#fff;margin:0;letter-spacing:-.02em;"><?php echo htmlspecialchars($barbershop['business_name']); ?></h3>
                </div>
                <p style="font-size:.875rem;color:#71717a;line-height:1.65;margin:0 0 20px;max-width:340px;"><?php echo htmlspecialchars($barbershop['description'] ?? ''); ?></p>
                <?php if ($barbershop['address']): ?>
                <p style="font-size:.8125rem;color:#52525b;display:flex;align-items:flex-start;gap:8px;margin:0 0 8px;">
                    <svg width="14" height="14" style="margin-top:2px;flex-shrink:0;" fill="none" stroke="#71717a" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <?php echo htmlspecialchars($barbershop['address']); ?><?php if ($barbershop['city']): ?>, <?php echo htmlspecialchars($barbershop['city']); ?><?php endif; ?>
                </p>
                <?php endif; ?>
                <?php if ($barbershop['phone']): ?>
                <p style="font-size:.8125rem;color:#52525b;display:flex;align-items:center;gap:8px;margin:0;">
                    <svg width="14" height="14" style="flex-shrink:0;" fill="none" stroke="#71717a" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    <a href="tel:<?php echo $barbershop['phone']; ?>" style="color:#71717a;text-decoration:none;"><?php echo htmlspecialchars($barbershop['phone']); ?></a>
                </p>
                <?php endif; ?>
            </div>

            <!-- Hours -->
            <div>
                <h4 style="font-size:.6875rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#52525b;margin:0 0 18px;">Horarios</h4>
                <div style="display:flex;flex-direction:column;gap:8px;">
                    <?php foreach ($schedules as $sched): ?>
                    <div style="display:flex;justify-content:space-between;font-size:.8125rem;">
                        <span style="color:#71717a;"><?php echo getDayName($sched['day_of_week']); ?></span>
                        <?php if ($sched['is_closed']): ?>
                        <span style="color:#ef4444;font-weight:500;">Cerrado</span>
                        <?php else: ?>
                        <span style="color:#d4d4d4;font-weight:500;"><?php echo date('g:i A', strtotime($sched['open_time'])); ?> – <?php echo date('g:i A', strtotime($sched['close_time'])); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Social -->
            <div>
                <h4 style="font-size:.6875rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#52525b;margin:0 0 18px;">Síguenos</h4>
                <div style="display:flex;flex-direction:column;gap:12px;">
                    <?php if ($barbershop['phone']): ?>
                    <a href="https://wa.me/1<?php echo preg_replace('/[^0-9]/', '', $barbershop['phone']); ?>" target="_blank"
                       style="display:inline-flex;align-items:center;gap:12px;color:#a1a1aa;text-decoration:none;font-size:.875rem;transition:color .18s;"
                       onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#a1a1aa'">
                        <div style="width:36px;height:36px;background:rgba(34,197,94,.15);border:1px solid rgba(34,197,94,.3);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                            <svg width="16" height="16" fill="#22c55e" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                        </div>
                        WhatsApp
                    </a>
                    <?php endif; ?>
                    <?php if ($barbershop['instagram']): ?>
                    <a href="<?php echo htmlspecialchars($barbershop['instagram']); ?>" target="_blank"
                       style="display:inline-flex;align-items:center;gap:12px;color:#a1a1aa;text-decoration:none;font-size:.875rem;transition:color .18s;"
                       onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#a1a1aa'">
                        <div style="width:36px;height:36px;background:rgba(236,72,153,.15);border:1px solid rgba(236,72,153,.3);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                            <svg width="16" height="16" fill="#ec4899" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                        </div>
                        Instagram
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div style="padding-top:24px;border-top:1px solid rgba(255,255,255,.06);display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:8px;">
            <p style="font-size:.8125rem;color:#52525b;margin:0;">&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($barbershop['business_name']); ?>. Todos los derechos reservados.</p>
            <p style="font-size:.8125rem;color:#52525b;margin:0;">Powered by <span style="font-weight:700;color:#a1a1aa;">Kyros Barber Cloud</span></p>
        </div>
    </div>
</footer>

<!-- ═══════════════════════════════════════════
     BOOKING MODAL
══════════════════════════════════════════════ -->
<div x-show="showBookingModal"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     style="position:fixed;inset:0;z-index:200;display:flex;align-items:flex-end;justify-content:center;padding:0;"
     class="sm:items-center sm:p-4"
     @keydown.escape.window="closeBookingModal()"
     x-cloak>

    <!-- Backdrop -->
    <div @click="closeBookingModal()"
         style="position:absolute;inset:0;background:rgba(0,0,0,.7);backdrop-filter:blur(4px);-webkit-backdrop-filter:blur(4px);"></div>

    <!-- Panel -->
    <div class="modal-anim" style="position:relative;background:#fff;width:100%;max-width:560px;border-radius:24px 24px 0 0;overflow:hidden;max-height:92vh;overflow-y:auto;">

        <!-- Gold accent bar -->
        <div style="height:3px;background:linear-gradient(90deg,#c9901a 0%,#e8b84b 50%,#c9901a 100%);"></div>

        <!-- Modal header -->
        <div style="padding:20px 24px 18px;border-bottom:1px solid #f0f0ec;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;background:#fff;z-index:10;">
            <div>
                <h3 style="font-family:'Sora',sans-serif;font-size:1.1875rem;font-weight:800;color:#0a0a0a;margin:0;letter-spacing:-.02em;">Reserva tu Cita</h3>
                <p style="font-size:.8125rem;color:#71717a;margin:3px 0 0;"><?php echo htmlspecialchars($barbershop['business_name']); ?></p>
            </div>
            <button @click="closeBookingModal()"
                    style="width:32px;height:32px;background:#f5f5f0;border:none;border-radius:8px;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#71717a;transition:background .18s;"
                    onmouseover="this.style.background='#e5e5e0'" onmouseout="this.style.background='#f5f5f0'">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form action="book.php" method="POST" style="padding:24px;display:flex;flex-direction:column;gap:18px;">
            <input type="hidden" name="barbershop_id" value="<?php echo $barbershop['id']; ?>">

            <!-- Service -->
            <div>
                <label style="display:block;font-size:.6875rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#71717a;margin-bottom:8px;">Servicio *</label>
                <select name="service_id" x-model="selectedService" @change="loadAvailability()" required class="inp">
                    <option value="">Seleccionar servicio...</option>
                    <?php foreach ($services as $service): ?>
                    <option value="<?php echo $service['id']; ?>">
                        <?php echo htmlspecialchars($service['name']); ?> — <?php echo formatPrice($service['price']); ?> (<?php echo $service['duration']; ?> min)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Barber -->
            <div>
                <label style="display:block;font-size:.6875rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#71717a;margin-bottom:8px;">Barbero *</label>
                <select name="barber_id" x-model="selectedBarber" @change="loadAvailability()" required class="inp">
                    <option value="">Seleccionar barbero...</option>
                    <?php foreach ($barbers as $barber): ?>
                    <option value="<?php echo $barber['id']; ?>"><?php echo htmlspecialchars($barber['full_name']); ?> — <?php echo number_format($barber['rating'], 1); ?> ★</option>
                    <?php endforeach; ?>
                </select>
                <p x-show="selectedBarber" style="font-size:.75rem;color:#16a34a;margin:6px 0 0;font-weight:500;">✓ Barbero preseleccionado</p>
            </div>

            <!-- Date + Time -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div>
                    <label style="display:block;font-size:.6875rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#71717a;margin-bottom:8px;">Fecha *</label>
                    <input type="date" name="appointment_date" x-model="selectedDate" @change="loadAvailability()"
                           required min="<?php echo date('Y-m-d'); ?>" class="inp">
                </div>
                <div>
                    <label style="display:block;font-size:.6875rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#71717a;margin-bottom:8px;">Hora *</label>
                    <select name="start_time" x-model="selectedStartTime" required class="inp">
                        <option value="" x-text="availabilityLoading ? 'Cargando...' : 'Selecciona fecha'"></option>
                        <template x-for="slot in availableSlots" :key="slot.value">
                            <option :value="slot.value" x-text="slot.time"></option>
                        </template>
                    </select>
                    <p x-show="availabilityMessage" style="font-size:.75rem;color:#d97706;margin:5px 0 0;" x-text="availabilityMessage"></p>
                    <div x-show="occupiedSlots.length > 0" style="display:flex;flex-wrap:wrap;gap:4px;margin-top:6px;">
                        <template x-for="slot in occupiedSlots" :key="slot.label">
                            <span style="font-size:.625rem;padding:2px 8px;background:#fef2f2;color:#ef4444;border:1px solid #fecaca;border-radius:999px;" x-text="slot.label"></span>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Divider -->
            <div style="border-top:1px solid #f0f0ec;padding-top:4px;">
                <p style="font-size:.6875rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#71717a;margin:0 0 12px;">Tus datos</p>
            </div>

            <input type="text" name="client_name" required placeholder="Nombre completo" class="inp">
            <input type="tel" name="client_phone" required placeholder="(809) 000-0000" class="inp">
            <textarea name="notes" rows="2" placeholder="Notas adicionales (opcional)" class="inp" style="resize:none;"></textarea>

            <!-- Actions -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;padding-top:4px;">
                <button type="button" @click="closeBookingModal()"
                        style="padding:12px;border:1.5px solid #e5e5e2;background:#fff;border-radius:12px;font-family:inherit;font-size:.875rem;font-weight:600;color:#52525b;cursor:pointer;transition:background .18s;"
                        onmouseover="this.style.background='#f5f5f0'" onmouseout="this.style.background='#fff'">
                    Cancelar
                </button>
                <button type="submit"
                        style="padding:12px;background:#0a0a0a;border:none;border-radius:12px;font-family:'Sora',sans-serif;font-size:.875rem;font-weight:700;color:#fff;cursor:pointer;transition:background .18s;"
                        onmouseover="this.style.background='#1f1f1f'" onmouseout="this.style.background='#0a0a0a'">
                    Confirmar Reserva
                </button>
            </div>
        </form>
    </div>
</div>

<style>
@media(min-width:640px){
    .modal-anim div[style*="border-radius:24px 24px 0 0"] { border-radius:24px !important; }
}
@media(min-width:768px){
    .hero-grid { grid-template-columns: 1fr 1fr !important; }
    .footer-grid { grid-template-columns: 2fr 1fr 1fr !important; }
}
[x-cloak] { display:none !important; }
</style>

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

        openBookingModal() { this.showBookingModal = true; document.body.style.overflow = 'hidden'; },
        closeBookingModal() { this.showBookingModal = false; document.body.style.overflow = ''; },

        selectService(id) { this.selectedService = String(id); this.openBookingModal(); this.loadAvailability(); },
        selectBarber(id) { this.selectedBarber = String(id); this.openBookingModal(); this.loadAvailability(); },

        async loadAvailability() {
            this.availableSlots = []; this.occupiedSlots = [];
            this.selectedStartTime = ''; this.availabilityMessage = '';
            if (!this.selectedService || !this.selectedBarber || !this.selectedDate) {
                if (this.selectedDate && this.selectedBarber && !this.selectedService)
                    this.availabilityMessage = 'Selecciona un servicio para calcular horarios.';
                return;
            }
            this.availabilityLoading = true;
            try {
                const r = await fetch('<?php echo BASE_URL; ?>/api/availability.php?' + new URLSearchParams({
                    barber_id: this.selectedBarber, date: this.selectedDate, service_id: this.selectedService
                }));
                const d = await r.json();
                if (!d.success) { this.availabilityMessage = d.message || 'No se pudo cargar la disponibilidad'; return; }
                this.availableSlots = Array.isArray(d.available_slots) ? d.available_slots : [];
                this.occupiedSlots  = Array.isArray(d.occupied_slots)  ? d.occupied_slots  : [];
                this.intervalMinutes = d.interval_minutes || 15;
                if (!this.availableSlots.length) this.availabilityMessage = d.message || 'No hay horas disponibles';
            } catch(e) { this.availabilityMessage = 'Error al consultar horarios'; }
            finally { this.availabilityLoading = false; }
        }
    };
}
</script>
</body>
</html>
