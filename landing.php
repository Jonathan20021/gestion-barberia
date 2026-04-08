<?php require_once __DIR__ . '/config/config.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kyros Barber Cloud — Sistema de Gestión para Barberías</title>
    <meta name="description" content="Sistema completo de gestión de citas, clientes y finanzas para barberías. Funciona en cualquier país, 100% en la nube, fácil de usar y con reservas online.">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sora:wght@700;800;900&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        *, *::before, *::after { box-sizing:border-box; }
        body { font-family:'Inter',sans-serif; background:#fff; margin:0; color:#0a0a0a; }
        h1,h2,h3,h4 { font-family:'Sora',sans-serif; }

        /* ── Animations ── */
        @keyframes fadeUp   { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }
        @keyframes shimmer  { 0%{transform:translateX(-100%)} 100%{transform:translateX(100%)} }
        @keyframes ticker   { 0%{transform:translateX(0)} 100%{transform:translateX(-50%)} }
        @keyframes float    { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-8px)} }

        .fade-up  { animation:fadeUp .6s cubic-bezier(.16,1,.3,1) both; }
        .fade-up2 { animation:fadeUp .6s .1s cubic-bezier(.16,1,.3,1) both; }
        .fade-up3 { animation:fadeUp .6s .2s cubic-bezier(.16,1,.3,1) both; }

        /* ── Gold button ── */
        .btn-gold {
            display:inline-flex; align-items:center; justify-content:center; gap:8px;
            position:relative; overflow:hidden;
            background:linear-gradient(135deg,#c9901a 0%,#e8b84b 50%,#c9901a 100%);
            color:#0a0a0a; font-weight:700; border:none; cursor:pointer; text-decoration:none;
            box-shadow:0 4px 20px rgba(201,144,26,.35);
            transition:box-shadow .2s, transform .2s;
        }
        .btn-gold::after {
            content:''; position:absolute; top:0; left:-100%; width:100%; height:100%;
            background:linear-gradient(90deg,transparent,rgba(255,255,255,.3),transparent);
        }
        .btn-gold:hover::after { animation:shimmer .5s ease forwards; }
        .btn-gold:hover { box-shadow:0 6px 28px rgba(201,144,26,.5); transform:translateY(-1px); }

        /* ── Dark button ── */
        .btn-dark {
            display:inline-flex; align-items:center; justify-content:center; gap:8px;
            background:#0a0a0a; color:#fff; font-weight:600; border:none; cursor:pointer;
            text-decoration:none; transition:background .18s, transform .18s;
        }
        .btn-dark:hover { background:#1f1f1f; transform:translateY(-1px); }

        /* ── Outline button ── */
        .btn-outline {
            display:inline-flex; align-items:center; justify-content:center; gap:8px;
            background:transparent; color:#0a0a0a; font-weight:600; border:1.5px solid #e5e5e2;
            cursor:pointer; text-decoration:none; transition:border-color .18s, background .18s;
        }
        .btn-outline:hover { border-color:#0a0a0a; background:#f9f9f7; }

        /* ── Card hover ── */
        .card { transition:transform .22s cubic-bezier(.4,0,.2,1), box-shadow .22s; }
        .card:hover { transform:translateY(-4px); box-shadow:0 20px 44px rgba(0,0,0,.09); }

        /* ── Pricing popular ── */
        .plan-popular { position:relative; }

        /* ── Feature icon ── */
        .feat-icon {
            width:52px; height:52px; border-radius:14px; flex-shrink:0;
            display:flex; align-items:center; justify-content:center;
        }

        /* ── Mock dashboard ── */
        .dash-bar { border-radius:4px; background:linear-gradient(90deg,#e8b84b,#c9901a); }
        .dash-bar-2 { border-radius:4px; background:#e5e5e0; }

        @media(min-width:768px){
            .hero-grid { grid-template-columns:1fr 1fr !important; }
            .feat-grid { grid-template-columns:repeat(3,1fr) !important; }
            .steps-grid { grid-template-columns:repeat(3,1fr) !important; }
            .price-grid { grid-template-columns:repeat(3,1fr) !important; }
            .footer-grid { grid-template-columns:2fr 1fr 1fr 1fr !important; }
        }

        /* ── Ticker ── */
        .ticker-wrap { overflow:hidden; }
        .ticker-inner { display:flex; animation:ticker 30s linear infinite; width:max-content; }
        .ticker-inner:hover { animation-play-state:paused; }
    </style>
</head>
<body x-data="{ mobileOpen: false }">

<!-- ═══════════════════════════════════════════
     NAVBAR
══════════════════════════════════════════════ -->
<header style="position:sticky;top:0;z-index:100;background:rgba(255,255,255,.96);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);border-bottom:1px solid #f0f0ec;">
    <div style="max-width:1200px;margin:0 auto;padding:0 24px;display:flex;align-items:center;justify-content:space-between;height:64px;">

        <!-- Logo -->
        <a href="/" style="display:flex;align-items:center;gap:10px;text-decoration:none;">
            <div style="width:32px;height:32px;background:linear-gradient(135deg,#c9901a,#e8b84b);border-radius:8px;display:flex;align-items:center;justify-content:center;">
                <svg width="18" height="18" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243 4.243 3 3 0 004.243-4.243zm0-5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z"/></svg>
            </div>
            <span class="mobile-brand-text" style="font-family:'Sora',sans-serif;font-weight:800;font-size:1.0625rem;color:#0a0a0a;letter-spacing:-.02em;">Kyros Barber Cloud</span>
            <span class="mobile-rd-badge" style="padding:2px 8px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:999px;font-size:.625rem;font-weight:700;color:#16a34a;letter-spacing:.06em;">GLOBAL</span>
        </a>

        <!-- Desktop nav -->
        <nav style="display:none;align-items:center;gap:32px;" id="desk-nav">
            <a href="#features" style="font-size:.875rem;font-weight:500;color:#52525b;text-decoration:none;transition:color .18s;" onmouseover="this.style.color='#0a0a0a'" onmouseout="this.style.color='#52525b'">Características</a>
            <a href="#pricing"  style="font-size:.875rem;font-weight:500;color:#52525b;text-decoration:none;transition:color .18s;" onmouseover="this.style.color='#0a0a0a'" onmouseout="this.style.color='#52525b'">Precios</a>
            <a href="#demo"     style="font-size:.875rem;font-weight:500;color:#52525b;text-decoration:none;transition:color .18s;" onmouseover="this.style.color='#0a0a0a'" onmouseout="this.style.color='#52525b'">Demo</a>
            <a href="<?php echo BASE_URL; ?>/documentation" style="font-size:.875rem;font-weight:500;color:#52525b;text-decoration:none;transition:color .18s;" onmouseover="this.style.color='#0a0a0a'" onmouseout="this.style.color='#52525b'">Documentación</a>
            <a href="#contact" style="font-size:.875rem;font-weight:500;color:#52525b;text-decoration:none;transition:color .18s;" onmouseover="this.style.color='#0a0a0a'" onmouseout="this.style.color='#52525b'">Contacto</a>
            <a href="<?php echo BASE_URL; ?>/auth/login" style="font-size:.875rem;font-weight:500;color:#52525b;text-decoration:none;transition:color .18s;" onmouseover="this.style.color='#0a0a0a'" onmouseout="this.style.color='#52525b'">Iniciar Sesión</a>
            <a href="<?php echo BASE_URL; ?>/auth/register" class="btn-gold" style="padding:9px 20px;border-radius:10px;font-size:.875rem;">Empezar Gratis</a>
        </nav>

        <!-- Mobile toggle -->
        <button @click="mobileOpen = !mobileOpen"
                style="display:flex;align-items:center;justify-content:center;width:40px;height:40px;background:#f5f5f0;border:none;border-radius:10px;cursor:pointer;"
                id="mob-toggle">
            <svg x-show="!mobileOpen" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
            <svg x-show="mobileOpen" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:none;"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <!-- Mobile menu -->
    <div x-show="mobileOpen" class="mobile-menu-wrap" style="background:#fff;border-top:1px solid #f0f0ec;padding:16px 24px;display:flex;flex-direction:column;gap:12px;" x-cloak>
        <a href="#features" class="mobile-nav-link" @click="mobileOpen = false">Características</a>
        <a href="#pricing" class="mobile-nav-link" @click="mobileOpen = false">Precios</a>
        <a href="#demo" class="mobile-nav-link" @click="mobileOpen = false">Demo</a>
        <a href="<?php echo BASE_URL; ?>/documentation" class="mobile-nav-link">Documentación</a>
        <a href="#contact" class="mobile-nav-link" @click="mobileOpen = false">Contacto</a>
        <a href="<?php echo BASE_URL; ?>/auth/login" class="mobile-nav-link">Iniciar Sesión</a>
        <a href="<?php echo BASE_URL; ?>/auth/register" class="btn-gold" style="width:100%;padding:13px 20px;border-radius:12px;font-size:1rem;text-align:center;">Empezar Gratis</a>
    </div>
</header>

<style>
@media(min-width:768px){
    #desk-nav  { display:flex !important; }
    #mob-toggle{ display:none !important; }
}

@media(max-width:767px){
    .mobile-nav-link {
        display:block;
        width:100%;
        padding:12px 14px;
        border-radius:10px;
        background:#fafaf8;
        border:1px solid #ecece6;
        font-size:1rem;
        font-weight:600;
        color:#27272a;
        text-decoration:none;
        line-height:1.25;
    }

    .mobile-nav-link:active {
        background:#f5f5f0;
    }

    .mobile-menu-wrap {
        padding:14px 16px !important;
        gap:10px !important;
    }

    .mobile-brand-text {
        font-size:.95rem !important;
    }
}

@media(max-width:430px){
    .mobile-brand-text {
        font-size:.88rem !important;
    }

    .mobile-rd-badge {
        display:none;
    }
}
[x-cloak]{ display:none !important; }
</style>

<!-- ═══════════════════════════════════════════
     HERO
══════════════════════════════════════════════ -->
<section style="background:#0a0a0a;color:#fff;padding:80px 24px 0;overflow:hidden;position:relative;">

    <!-- Ambient glows -->
    <div style="position:absolute;top:-100px;left:50%;transform:translateX(-50%);width:700px;height:400px;background:radial-gradient(ellipse,rgba(201,144,26,.15) 0%,transparent 70%);pointer-events:none;"></div>

    <div style="max-width:1200px;margin:0 auto;position:relative;z-index:1;">
        <!-- Eyebrow -->
        <div class="fade-up" style="display:flex;justify-content:center;margin-bottom:24px;">
            <div style="display:inline-flex;align-items:center;gap:8px;padding:6px 16px;border-radius:999px;background:rgba(201,144,26,.12);border:1px solid rgba(201,144,26,.3);">
                <span style="width:6px;height:6px;background:#e8b84b;border-radius:50%;display:block;"></span>
                <span style="font-size:.75rem;font-weight:600;color:#e8b84b;letter-spacing:.06em;">NUEVO · Ahora con reservas por WhatsApp</span>
            </div>
        </div>

        <!-- Headline -->
        <div class="fade-up" style="text-align:center;max-width:800px;margin:0 auto 28px;">
            <h1 style="font-size:clamp(2.5rem,6vw,4.5rem);font-weight:900;line-height:1.04;letter-spacing:-.04em;margin:0 0 20px;">
                Gestiona tu Barbería
                <span style="background:linear-gradient(135deg,#c9901a 0%,#e8b84b 60%,#c9901a 100%);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;display:block;">Como un Profesional</span>
            </h1>
            <p style="font-size:clamp(1rem,2.5vw,1.1875rem);color:#9ca3af;line-height:1.7;margin:0 auto;max-width:560px;">
                Sistema completo de gestión de citas, clientes y finanzas para barberías. Funciona en cualquier país del mundo, 100% en la nube y con reservas online.
            </p>
        </div>

        <!-- CTA row -->
        <div class="fade-up2" style="display:flex;flex-wrap:wrap;gap:12px;justify-content:center;margin-bottom:16px;">
            <a href="<?php echo BASE_URL; ?>/auth/register" class="btn-gold" style="padding:15px 32px;border-radius:14px;font-family:'Sora',sans-serif;font-size:1rem;">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                Probar Gratis 15 Días
            </a>
            <a href="#demo" class="btn-outline" style="padding:15px 28px;border-radius:14px;font-size:1rem;color:#fff;border-color:rgba(255,255,255,.2);"
               onmouseover="this.style.background='rgba(255,255,255,.07)';this.style.borderColor='rgba(255,255,255,.4)'"
               onmouseout="this.style.background='transparent';this.style.borderColor='rgba(255,255,255,.2)'">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Ver Demo
            </a>
        </div>

        <!-- Trust badges -->
        <div class="fade-up3" style="display:flex;flex-wrap:wrap;gap:20px;justify-content:center;margin-bottom:48px;">
            <?php $badges = [['Sin contratos','M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],['Cancela cuando quieras','M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],['Soporte en español','M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z']]; foreach($badges as $b): ?>
            <div style="display:flex;align-items:center;gap:6px;font-size:.8125rem;color:#6b7280;">
                <svg width="15" height="15" fill="#22c55e" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                <?php echo $b[0]; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Mock Dashboard Preview -->
        <div style="max-width:960px;margin:0 auto;position:relative;">
            <div style="position:absolute;inset:-20px;background:radial-gradient(ellipse,rgba(201,144,26,.12) 0%,transparent 70%);pointer-events:none;"></div>
            <div style="position:relative;background:#111;border-radius:20px 20px 0 0;border:1px solid rgba(255,255,255,.08);border-bottom:none;overflow:hidden;box-shadow:0 -8px 80px rgba(0,0,0,.6);">

                <!-- Browser chrome -->
                <div style="background:#1a1a1a;padding:12px 16px;border-bottom:1px solid rgba(255,255,255,.06);display:flex;align-items:center;gap:12px;">
                    <div style="display:flex;gap:6px;">
                        <div style="width:12px;height:12px;border-radius:50%;background:#ff5f57;"></div>
                        <div style="width:12px;height:12px;border-radius:50%;background:#febc2e;"></div>
                        <div style="width:12px;height:12px;border-radius:50%;background:#28c840;"></div>
                    </div>
                    <div style="flex:1;background:rgba(255,255,255,.06);border-radius:6px;padding:5px 12px;font-size:.6875rem;color:#6b7280;font-family:monospace;">
                        kyros.app/dashboard
                    </div>
                </div>

                <!-- Dashboard UI -->
                <div style="padding:24px;display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:12px;font-family:'Inter',sans-serif;">
                    <!-- Stat cards -->
                    <?php $stats_mock = [['Citas Hoy','24','#e8b84b','M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],['Ingresos','RD$8,450','#22c55e','M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],['Clientes','156','#818cf8','M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],['Rating','4.9★','#f472b6','M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z']]; foreach($stats_mock as $s): ?>
                    <div style="background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.07);border-radius:12px;padding:14px;">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                            <svg width="16" height="16" fill="none" stroke="<?php echo $s[2]; ?>" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="<?php echo $s[3]; ?>"/></svg>
                            <span style="font-size:.6875rem;color:#4b5563;"><?php echo $s[0]; ?></span>
                        </div>
                        <p style="font-family:'Sora',sans-serif;font-size:1.375rem;font-weight:800;color:#fff;margin:0;"><?php echo $s[1]; ?></p>
                    </div>
                    <?php endforeach; ?>

                    <!-- Chart area -->
                    <div style="grid-column:1/-1;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);border-radius:12px;padding:16px;">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
                            <span style="font-size:.8125rem;font-weight:600;color:#9ca3af;">Ingresos esta semana</span>
                            <span style="font-size:.6875rem;color:#4b5563;background:rgba(255,255,255,.05);padding:3px 8px;border-radius:6px;">Últimos 7 días</span>
                        </div>
                        <div style="display:flex;align-items:flex-end;gap:8px;height:60px;">
                            <?php $bars = [40,65,45,80,55,90,70]; foreach($bars as $h): ?>
                            <div style="flex:1;height:<?php echo $h; ?>%;border-radius:4px 4px 0 0;background:linear-gradient(to top,#c9901a,#e8b84b);opacity:.8;"></div>
                            <?php endforeach; ?>
                        </div>
                        <div style="display:flex;justify-content:space-between;margin-top:6px;">
                            <?php foreach(['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'] as $d): ?>
                            <span style="font-size:.5rem;color:#4b5563;flex:1;text-align:center;"><?php echo $d; ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════
     STATS TICKER
══════════════════════════════════════════════ -->
<section style="background:#fff;border-bottom:1px solid #f0f0ec;padding:20px 0;overflow:hidden;">
    <div class="ticker-wrap">
        <div class="ticker-inner">
            <?php $items = ['500+ Barberías Activas','50K+ Citas Mensuales','99.9% Uptime garantizado','4.8 Satisfacción promedio','Disponible en todo el mundo','Soporte 24/7 en español']; for($r=0;$r<4;$r++): foreach($items as $it): ?>
            <div style="display:inline-flex;align-items:center;gap:12px;padding:0 32px;white-space:nowrap;">
                <span style="width:6px;height:6px;border-radius:50%;background:linear-gradient(135deg,#c9901a,#e8b84b);display:block;flex-shrink:0;"></span>
                <span style="font-size:.875rem;font-weight:500;color:#52525b;"><?php echo $it; ?></span>
            </div>
            <?php endforeach; endfor; ?>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════
     FEATURES
══════════════════════════════════════════════ -->
<section id="features" style="background:#fafaf8;padding:96px 24px;">
    <div style="max-width:1200px;margin:0 auto;">
        <div style="text-align:center;margin-bottom:64px;">
            <span style="display:inline-block;padding:5px 14px;background:#fef9ee;border:1px solid #f0d88a;border-radius:999px;font-size:.6875rem;font-weight:700;letter-spacing:.1em;color:#a16207;text-transform:uppercase;margin-bottom:14px;">Características</span>
            <h2 style="font-size:clamp(2rem,5vw,3rem);font-weight:900;color:#0a0a0a;letter-spacing:-.03em;margin:0 0 14px;">Todo lo que Necesitas</h2>
            <p style="font-size:1.0625rem;color:#71717a;max-width:480px;margin:0 auto;line-height:1.65;">Herramientas profesionales diseñadas para barberías en cualquier país</p>
        </div>

        <div class="feat-grid" style="display:grid;grid-template-columns:1fr;gap:16px;">
            <?php
            $features = [
                ['Reservas Online 24/7','Tus clientes agendan citas desde cualquier lugar, a cualquier hora. Página personalizada para tu barbería con URL propia.','M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z','#c9901a','#fef9ee'],
                ['Dashboard Completo','Visualiza estadísticas, ingresos y citas en tiempo real. Reportes detallados para tomar mejores decisiones de negocio.','M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z','#818cf8','#f0f9ff'],
                ['Gestión de Clientes','Base de datos completa con historial de servicios, preferencias, contacto por WhatsApp via wa.me y recordatorios por email.','M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z','#22c55e','#f0fdf4'],
                ['Gestión de Barberos','Administra horarios, comisiones y rendimiento de tu equipo. Cada barbero con su propio perfil público y portal.','M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243 4.243 3 3 0 004.243-4.243zm0-5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z','#0a0a0a','#f5f5f0'],
                ['Control Financiero','Registra ingresos, gastos y comisiones. Reportes fiscales y análisis de rentabilidad por servicio o barbero.','M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z','#e8b84b','#fffbeb'],
                ['WhatsApp Integrado','Comunicación directa por WhatsApp con enlace wa.me y recordatorios automáticos por email. Reduce ausencias y mejora la puntualidad.','M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z','#22c55e','#f0fdf4'],
            ];
            foreach($features as $f): ?>
            <div class="card" style="background:#fff;border:1.5px solid #ebebeb;border-radius:20px;padding:28px;display:flex;flex-direction:column;gap:16px;">
                <div class="feat-icon" style="background:<?php echo $f[4]; ?>;">
                    <svg width="24" height="24" fill="none" stroke="<?php echo $f[3]; ?>" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="<?php echo $f[2]; ?>"/>
                    </svg>
                </div>
                <div>
                    <h3 style="font-family:'Sora',sans-serif;font-size:1.0625rem;font-weight:700;color:#0a0a0a;margin:0 0 6px;"><?php echo $f[0]; ?></h3>
                    <p style="font-size:.875rem;color:#71717a;line-height:1.6;margin:0;"><?php echo $f[1]; ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════
     HOW IT WORKS
══════════════════════════════════════════════ -->
<section style="background:#fff;padding:96px 24px;">
    <div style="max-width:1000px;margin:0 auto;">
        <div style="text-align:center;margin-bottom:64px;">
            <span style="display:inline-block;padding:5px 14px;background:#fef9ee;border:1px solid #f0d88a;border-radius:999px;font-size:.6875rem;font-weight:700;letter-spacing:.1em;color:#a16207;text-transform:uppercase;margin-bottom:14px;">Proceso</span>
            <h2 style="font-size:clamp(2rem,5vw,3rem);font-weight:900;color:#0a0a0a;letter-spacing:-.03em;margin:0 0 14px;">En funcionamiento en 15 minutos</h2>
            <p style="font-size:1.0625rem;color:#71717a;margin:0 auto;max-width:520px;line-height:1.65;">Regístrate, el sistema crea tu cuenta de owner y tu barbería, y recibes una licencia de prueba de 15 días automáticamente.</p>
        </div>

        <div class="steps-grid" style="display:grid;grid-template-columns:1fr;gap:24px;">
            <?php $steps = [['Registra tu barbería','Completa el formulario con tus datos y el nombre del negocio. Se crea tu cuenta de owner al instante.'],['Activa tu prueba gratis','El sistema genera automáticamente una licencia trial de 15 días con el plan que elijas para comenzar.'],['Entra y configura','Accede al dashboard, personaliza tu URL, agrega barberos, servicios y empieza a recibir citas online.']]; foreach($steps as $i => $s): ?>
            <div style="display:flex;gap:20px;align-items:flex-start;">
                <div style="flex-shrink:0;width:44px;height:44px;border-radius:12px;background:<?php echo $i===1?'linear-gradient(135deg,#c9901a,#e8b84b)':'#0a0a0a'; ?>;display:flex;align-items:center;justify-content:center;font-family:'Sora',sans-serif;font-weight:900;font-size:1rem;color:<?php echo $i===1?'#0a0a0a':'#e8b84b'; ?>;">
                    0<?php echo $i+1; ?>
                </div>
                <div style="padding-top:8px;">
                    <h3 style="font-family:'Sora',sans-serif;font-size:1.125rem;font-weight:700;color:#0a0a0a;margin:0 0 6px;"><?php echo $s[0]; ?></h3>
                    <p style="font-size:.875rem;color:#71717a;line-height:1.65;margin:0;"><?php echo $s[1]; ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════
     PRICING
══════════════════════════════════════════════ -->
<section id="pricing" style="background:#fafaf8;padding:96px 24px;">
    <div x-data="currencySelector()" x-init="init()" style="max-width:1100px;margin:0 auto;">
        <div style="text-align:center;margin-bottom:48px;">
            <span style="display:inline-block;padding:5px 14px;background:#fef9ee;border:1px solid #f0d88a;border-radius:999px;font-size:.6875rem;font-weight:700;letter-spacing:.1em;color:#a16207;text-transform:uppercase;margin-bottom:14px;">Precios</span>
            <h2 style="font-size:clamp(2rem,5vw,3rem);font-weight:900;color:#0a0a0a;letter-spacing:-.03em;margin:0 0 12px;">Planes Transparentes</h2>
            <p style="font-size:1.0625rem;color:#71717a;margin:0 0 28px;">Sin costos ocultos. Cancela cuando quieras.</p>

            <!-- Currency selector -->
            <div style="display:inline-flex;align-items:center;gap:10px;padding:8px 16px;background:#fff;border:1.5px solid #e5e5e2;border-radius:14px;box-shadow:0 2px 8px rgba(0,0,0,.05);">
                <svg width="16" height="16" fill="none" stroke="#71717a" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span style="font-size:.8125rem;font-weight:600;color:#52525b;white-space:nowrap;">Ver precios en:</span>
                <select x-model="selectedCountryCode" @change="onCountryChange()" style="border:none;outline:none;background:transparent;font-size:.875rem;font-weight:700;color:#0a0a0a;cursor:pointer;max-width:220px;">
                    <template x-for="c in countries" :key="c.code">
                        <option :value="c.code" x-text="c.flag + ' ' + c.name + ' (' + c.currency + ')'"></option>
                    </template>
                </select>
                <span x-show="loading" style="font-size:.75rem;color:#a1a1aa;margin-left:4px;">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="animation:spin .8s linear infinite;display:inline-block;"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </span>
            </div>
            <p x-show="!loading && rateSource === 'live'" style="font-size:.6875rem;color:#a1a1aa;margin:8px 0 0;">
                <svg width="10" height="10" fill="#22c55e" viewBox="0 0 10 10" style="display:inline-block;margin-right:3px;"><circle cx="5" cy="5" r="5"/></svg>
                Tasas de cambio en tiempo real · <span x-text="rateDate"></span>
            </p>
            <p x-show="!loading && rateSource === 'fallback'" style="font-size:.6875rem;color:#a1a1aa;margin:8px 0 0;">
                Tasas de cambio aproximadas
            </p>
        </div>

        <style>@keyframes spin{to{transform:rotate(360deg)}}</style>

        <div class="price-grid" style="display:grid;grid-template-columns:1fr;gap:16px;align-items:start;">

            <!-- Básico -->
            <div class="card" style="background:#fff;border:1.5px solid #ebebeb;border-radius:24px;padding:32px;">
                <h3 style="font-family:'Sora',sans-serif;font-size:1.25rem;font-weight:800;color:#0a0a0a;margin:0 0 4px;">Básico</h3>
                <p style="font-size:.875rem;color:#71717a;margin:0 0 20px;">Ideal para empezar</p>
                <div style="margin-bottom:24px;">
                    <span style="font-family:'Sora',sans-serif;font-size:2.5rem;font-weight:900;color:#0a0a0a;" x-text="formatPrice(1500)"></span>
                    <span style="color:#71717a;font-size:.875rem;">/mes</span>
                </div>
                <ul style="list-style:none;padding:0;margin:0 0 28px;display:flex;flex-direction:column;gap:10px;">
                    <?php foreach(['Hasta 100 citas/mes','2 barberos','Página de reservas online','Soporte por email'] as $li): ?>
                    <li style="display:flex;align-items:center;gap:8px;font-size:.875rem;color:#52525b;">
                        <svg width="16" height="16" fill="#22c55e" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        <?php echo $li; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <a href="<?php echo BASE_URL; ?>/auth/register" class="btn-dark" style="width:100%;padding:13px;border-radius:12px;font-size:.9375rem;">Comenzar</a>
            </div>

            <!-- Profesional -->
            <div style="background:#0a0a0a;border:1.5px solid #0a0a0a;border-radius:24px;padding:32px;position:relative;overflow:hidden;box-shadow:0 24px 64px rgba(0,0,0,.2);">
                <div style="position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#c9901a,#e8b84b,#c9901a);"></div>
                <div style="position:absolute;top:16px;right:16px;padding:4px 12px;background:linear-gradient(135deg,#c9901a,#e8b84b);border-radius:999px;font-size:.6875rem;font-weight:700;color:#0a0a0a;">MÁS POPULAR</div>
                <h3 style="font-family:'Sora',sans-serif;font-size:1.25rem;font-weight:800;color:#fff;margin:0 0 4px;">Profesional</h3>
                <p style="font-size:.875rem;color:#71717a;margin:0 0 20px;">El más vendido</p>
                <div style="margin-bottom:24px;">
                    <span style="font-family:'Sora',sans-serif;font-size:2.5rem;font-weight:900;color:#fff;" x-text="formatPrice(3000)"></span>
                    <span style="color:#71717a;font-size:.875rem;">/mes</span>
                </div>
                <ul style="list-style:none;padding:0;margin:0 0 28px;display:flex;flex-direction:column;gap:10px;">
                    <?php foreach(['Citas ilimitadas','Hasta 5 barberos','Finanzas avanzadas','WhatsApp via wa.me + recordatorios por email','Reportes avanzados','Soporte prioritario'] as $li): ?>
                    <li style="display:flex;align-items:center;gap:8px;font-size:.875rem;color:#d4d4d4;">
                        <svg width="16" height="16" fill="#e8b84b" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        <?php echo $li; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <a href="<?php echo BASE_URL; ?>/auth/register" class="btn-gold" style="width:100%;padding:13px;border-radius:12px;font-size:.9375rem;">Comenzar Prueba Gratis</a>
            </div>

            <!-- Empresarial -->
            <div class="card" style="background:#fff;border:1.5px solid #ebebeb;border-radius:24px;padding:32px;">
                <h3 style="font-family:'Sora',sans-serif;font-size:1.25rem;font-weight:800;color:#0a0a0a;margin:0 0 4px;">Empresarial</h3>
                <p style="font-size:.875rem;color:#71717a;margin:0 0 20px;">Múltiples sucursales</p>
                <div style="margin-bottom:24px;">
                    <span style="font-family:'Sora',sans-serif;font-size:2.5rem;font-weight:900;color:#0a0a0a;" x-text="formatPrice(5000)"></span>
                    <span style="color:#71717a;font-size:.875rem;">/mes</span>
                </div>
                <ul style="list-style:none;padding:0;margin:0 0 28px;display:flex;flex-direction:column;gap:10px;">
                    <?php foreach(['Todo del plan Profesional','Barberos ilimitados','Múltiples sucursales','API personalizada','Gerente de cuenta dedicado'] as $li): ?>
                    <li style="display:flex;align-items:center;gap:8px;font-size:.875rem;color:#52525b;">
                        <svg width="16" height="16" fill="#22c55e" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        <?php echo $li; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <a href="<?php echo BASE_URL; ?>/auth/login" class="btn-dark" style="width:100%;padding:13px;border-radius:12px;font-size:.9375rem;">Contactar Ventas</a>
            </div>
        </div>
    </div>
</section>

<script>
function currencySelector() {
    return {
        selectedCountryCode: 'DO',
        loading: false,
        rates: {},
        rateSource: '',
        rateDate: '',
        countries: [
            { code: 'DO', name: 'República Dominicana', currency: 'DOP', symbol: 'RD$',  flag: '🇩🇴', decimals: 0 },
            { code: 'US', name: 'Estados Unidos',       currency: 'USD', symbol: '$',    flag: '🇺🇸', decimals: 2 },
            { code: 'MX', name: 'México',               currency: 'MXN', symbol: '$',    flag: '🇲🇽', decimals: 0 },
            { code: 'CO', name: 'Colombia',             currency: 'COP', symbol: '$',    flag: '🇨🇴', decimals: 0 },
            { code: 'AR', name: 'Argentina',            currency: 'ARS', symbol: '$',    flag: '🇦🇷', decimals: 0 },
            { code: 'CL', name: 'Chile',                currency: 'CLP', symbol: '$',    flag: '🇨🇱', decimals: 0 },
            { code: 'PE', name: 'Perú',                 currency: 'PEN', symbol: 'S/',   flag: '🇵🇪', decimals: 2 },
            { code: 'VE', name: 'Venezuela',            currency: 'VES', symbol: 'Bs.',  flag: '🇻🇪', decimals: 2 },
            { code: 'EC', name: 'Ecuador',              currency: 'USD', symbol: '$',    flag: '🇪🇨', decimals: 2 },
            { code: 'PA', name: 'Panamá',               currency: 'USD', symbol: '$',    flag: '🇵🇦', decimals: 2 },
            { code: 'GT', name: 'Guatemala',            currency: 'GTQ', symbol: 'Q',    flag: '🇬🇹', decimals: 2 },
            { code: 'CR', name: 'Costa Rica',           currency: 'CRC', symbol: '₡',   flag: '🇨🇷', decimals: 0 },
            { code: 'HN', name: 'Honduras',             currency: 'HNL', symbol: 'L',    flag: '🇭🇳', decimals: 2 },
            { code: 'SV', name: 'El Salvador',          currency: 'USD', symbol: '$',    flag: '🇸🇻', decimals: 2 },
            { code: 'PR', name: 'Puerto Rico',          currency: 'USD', symbol: '$',    flag: '🇵🇷', decimals: 2 },
            { code: 'ES', name: 'España',               currency: 'EUR', symbol: '€',   flag: '🇪🇸', decimals: 2 },
            { code: 'BR', name: 'Brasil',               currency: 'BRL', symbol: 'R$',  flag: '🇧🇷', decimals: 2 },
            { code: 'GB', name: 'Reino Unido',          currency: 'GBP', symbol: '£',   flag: '🇬🇧', decimals: 2 },
            { code: 'CA', name: 'Canadá',               currency: 'CAD', symbol: '$',    flag: '🇨🇦', decimals: 2 },
        ],

        get currentCountry() {
            return this.countries.find(c => c.code === this.selectedCountryCode) || this.countries[0];
        },

        get rate() {
            const c = this.currentCountry;
            if (c.currency === 'DOP') return 1;
            return this.rates[c.currency] || null;
        },

        formatPrice(dopAmount) {
            const c = this.currentCountry;
            let amount = dopAmount;
            if (c.currency !== 'DOP' && this.rate) {
                amount = dopAmount * this.rate;
            }
            const opts = {
                minimumFractionDigits: c.decimals,
                maximumFractionDigits: c.decimals,
            };
            const formatted = amount.toLocaleString('es', opts);
            return c.symbol + formatted;
        },

        onCountryChange() {
            // rates already loaded, just reactive update via Alpine
        },

        async init() {
            this.loading = true;
            try {
                const res = await fetch('https://open.er-api.com/v6/latest/DOP');
                if (!res.ok) throw new Error('network');
                const data = await res.json();
                if (data.result === 'success') {
                    this.rates = data.rates;
                    this.rateSource = 'live';
                    this.rateDate = new Date(data.time_last_update_unix * 1000).toLocaleDateString('es', { day:'numeric', month:'short', year:'numeric' });
                } else {
                    throw new Error('api');
                }
            } catch (e) {
                // Approximate fallback rates relative to 1 DOP
                this.rates = {
                    USD: 0.01695, EUR: 0.01567, MXN: 0.3392, COP: 67.4,
                    ARS: 17.0,    CLP: 16.2,    PEN: 0.0641,  VES: 0.618,
                    GTQ: 0.1312,  CRC: 8.67,    HNL: 0.4196,  BRL: 0.0970,
                    GBP: 0.01338, CAD: 0.02361,
                };
                this.rateSource = 'fallback';
                this.rateDate = '';
            }
            this.loading = false;
        },
    };
}
</script>

<!-- ═══════════════════════════════════════════
     DEMO
══════════════════════════════════════════════ -->
<section id="demo" style="background:#0a0a0a;padding:96px 24px;">
    <div style="max-width:1000px;margin:0 auto;">
        <div style="text-align:center;margin-bottom:56px;">
            <span style="display:inline-block;padding:5px 14px;background:rgba(201,144,26,.12);border:1px solid rgba(201,144,26,.3);border-radius:999px;font-size:.6875rem;font-weight:700;letter-spacing:.1em;color:#e8b84b;text-transform:uppercase;margin-bottom:14px;">Demo en Vivo</span>
            <h2 style="font-size:clamp(2rem,5vw,3rem);font-weight:900;color:#fff;letter-spacing:-.03em;margin:0 0 14px;">Prueba el Sistema Ahora</h2>
            <p style="font-size:1.0625rem;color:#71717a;margin:0;">Accede con las credenciales de demostración. Sin necesidad de registro.</p>
        </div>

        <div style="display:grid;grid-template-columns:1fr;gap:16px;margin-bottom:32px;" id="demo-grid">
            <!-- Owner -->
            <div class="card" style="background:rgba(255,255,255,.04);border:1.5px solid rgba(255,255,255,.08);border-radius:20px;padding:28px;">
                <div style="display:flex;align-items:center;gap:14px;margin-bottom:20px;">
                    <div style="width:48px;height:48px;border-radius:14px;background:linear-gradient(135deg,#4f46e5,#7c3aed);display:flex;align-items:center;justify-content:center;">
                        <svg width="22" height="22" fill="none" stroke="white" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    <div>
                        <h3 style="font-family:'Sora',sans-serif;font-size:1.0625rem;font-weight:700;color:#fff;margin:0;">Dueño de Barbería</h3>
                        <p style="font-size:.8125rem;color:#71717a;margin:3px 0 0;">Panel completo de gestión</p>
                    </div>
                </div>
                <div style="background:rgba(0,0,0,.3);border-radius:10px;padding:14px;margin-bottom:18px;font-size:.8125rem;">
                    <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                        <span style="color:#71717a;">Email</span>
                        <span style="font-family:monospace;color:#e8b84b;">demo@barberia.com</span>
                    </div>
                    <div style="height:1px;background:rgba(255,255,255,.05);margin-bottom:8px;"></div>
                    <div style="display:flex;justify-content:space-between;">
                        <span style="color:#71717a;">Password</span>
                        <span style="font-family:monospace;color:#e8b84b;">password123</span>
                    </div>
                </div>
                <a href="<?php echo BASE_URL; ?>/auth/login" class="btn-gold" style="width:100%;padding:12px;border-radius:12px;font-size:.875rem;">Acceder como Owner</a>
            </div>

            <!-- Barber -->
            <div class="card" style="background:rgba(255,255,255,.04);border:1.5px solid rgba(255,255,255,.08);border-radius:20px;padding:28px;">
                <div style="display:flex;align-items:center;gap:14px;margin-bottom:20px;">
                    <div style="width:48px;height:48px;border-radius:14px;background:linear-gradient(135deg,#059669,#10b981);display:flex;align-items:center;justify-content:center;">
                        <svg width="22" height="22" fill="none" stroke="white" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243 4.243 3 3 0 004.243-4.243zm0-5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z"/></svg>
                    </div>
                    <div>
                        <h3 style="font-family:'Sora',sans-serif;font-size:1.0625rem;font-weight:700;color:#fff;margin:0;">Barbero</h3>
                        <p style="font-size:.8125rem;color:#71717a;margin:3px 0 0;">Panel de barbero individual</p>
                    </div>
                </div>
                <div style="background:rgba(0,0,0,.3);border-radius:10px;padding:14px;margin-bottom:18px;font-size:.8125rem;">
                    <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                        <span style="color:#71717a;">Email</span>
                        <span style="font-family:monospace;color:#e8b84b;">barbero@demo.com</span>
                    </div>
                    <div style="height:1px;background:rgba(255,255,255,.05);margin-bottom:8px;"></div>
                    <div style="display:flex;justify-content:space-between;">
                        <span style="color:#71717a;">Password</span>
                        <span style="font-family:monospace;color:#e8b84b;">password123</span>
                    </div>
                </div>
                <a href="<?php echo BASE_URL; ?>/auth/login" class="btn-dark" style="width:100%;padding:12px;border-radius:12px;font-size:.875rem;background:#1f1f1f;border:1px solid rgba(255,255,255,.1);">Acceder como Barbero</a>
            </div>
        </div>

        <div style="text-align:center;">
            <a href="<?php echo BASE_URL; ?>/public/estilo-rd"
               style="display:inline-flex;align-items:center;gap:8px;padding:14px 28px;background:rgba(255,255,255,.06);border:1.5px solid rgba(255,255,255,.12);color:#fff;border-radius:14px;font-weight:600;font-size:.9375rem;text-decoration:none;transition:background .18s;"
               onmouseover="this.style.background='rgba(255,255,255,.1)'" onmouseout="this.style.background='rgba(255,255,255,.06)'">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                Ver Página Pública de Reservas
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>

    </div>

    <style>@media(min-width:640px){#demo-grid{grid-template-columns:1fr 1fr !important;}}</style>
</section>

<!-- ═══════════════════════════════════════════
     FINAL CTA
══════════════════════════════════════════════ -->
<section style="background:#fff;padding:96px 24px;text-align:center;">
    <div style="max-width:600px;margin:0 auto;">
        <div style="width:48px;height:48px;background:linear-gradient(135deg,#c9901a,#e8b84b);border-radius:14px;display:flex;align-items:center;justify-content:center;margin:0 auto 24px;">
            <svg width="24" height="24" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        </div>
        <h2 style="font-size:clamp(2rem,5vw,3rem);font-weight:900;color:#0a0a0a;letter-spacing:-.03em;margin:0 0 16px;line-height:1.1;">¿Listo para Modernizar tu Barbería?</h2>
        <p style="font-size:1.0625rem;color:#71717a;margin:0 0 36px;line-height:1.65;">Únete a cientos de barberías que ya están creciendo con Kyros Barber Cloud</p>
        <a href="<?php echo BASE_URL; ?>/auth/register" class="btn-gold" style="padding:16px 36px;border-radius:14px;font-family:'Sora',sans-serif;font-size:1.0625rem;">
            Comenzar Gratis por 15 Días
        </a>
        <p style="font-size:.8125rem;color:#a1a1aa;margin:16px 0 0;">No requiere tarjeta de crédito · Cancela cuando quieras</p>
    </div>
</section>

<!-- ═══════════════════════════════════════════
     CONTACT
══════════════════════════════════════════════ -->
<section id="contact" style="background:#fafaf8;padding:96px 24px;">
    <div style="max-width:860px;margin:0 auto;">

        <div style="text-align:center;margin-bottom:56px;">
            <span style="display:inline-block;padding:5px 14px;background:#fef9ee;border:1px solid #f0d88a;border-radius:999px;font-size:.6875rem;font-weight:700;letter-spacing:.1em;color:#a16207;text-transform:uppercase;margin-bottom:14px;">Contacto</span>
            <h2 style="font-size:clamp(2rem,5vw,3rem);font-weight:900;color:#0a0a0a;letter-spacing:-.03em;margin:0 0 14px;">¿Tienes preguntas?</h2>
            <p style="font-size:1.0625rem;color:#71717a;line-height:1.65;margin:0 auto;max-width:520px;">
                Operamos de forma remota para barberías en cualquier país. Escríbenos y te respondemos a la brevedad.
            </p>
        </div>

        <!-- Cards de contacto -->
        <div style="display:grid;grid-template-columns:1fr;gap:16px;margin-bottom:40px;" id="contact-cards">

            <!-- WhatsApp -->
            <a href="https://wa.me/18495024061?text=Hola%2C%20me%20interesa%20Kyros%20Barber%20Cloud" target="_blank" rel="noopener noreferrer"
               style="display:flex;align-items:center;gap:20px;background:#fff;border:1.5px solid #ebebeb;border-radius:20px;padding:28px;text-decoration:none;transition:box-shadow .22s,transform .22s,border-color .22s;"
               onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 20px 44px rgba(0,0,0,.09)';this.style.borderColor='#25d366'"
               onmouseout="this.style.transform='';this.style.boxShadow='';this.style.borderColor='#ebebeb'">
                <div style="flex-shrink:0;width:56px;height:56px;border-radius:16px;background:#dcfce7;display:flex;align-items:center;justify-content:center;">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="#25d366"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                </div>
                <div style="flex:1;">
                    <p style="font-size:.6875rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#16a34a;margin:0 0 4px;">WhatsApp</p>
                    <p style="font-family:'Sora',sans-serif;font-size:1.375rem;font-weight:800;color:#0a0a0a;margin:0 0 2px;">849-502-4061</p>
                    <p style="font-size:.8125rem;color:#71717a;margin:0;">Respuesta rápida · Lun–Vie</p>
                </div>
                <svg width="20" height="20" fill="none" stroke="#d4d4d4" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>

            <!-- Email -->
            <a href="mailto:jonathansandoval@kyrosrd.com"
               style="display:flex;align-items:center;gap:20px;background:#fff;border:1.5px solid #ebebeb;border-radius:20px;padding:28px;text-decoration:none;transition:box-shadow .22s,transform .22s,border-color .22s;"
               onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 20px 44px rgba(0,0,0,.09)';this.style.borderColor='#c9901a'"
               onmouseout="this.style.transform='';this.style.boxShadow='';this.style.borderColor='#ebebeb'">
                <div style="flex-shrink:0;width:56px;height:56px;border-radius:16px;background:#fef9ee;display:flex;align-items:center;justify-content:center;">
                    <svg width="26" height="26" fill="none" stroke="#c9901a" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </div>
                <div style="flex:1;min-width:0;">
                    <p style="font-size:.6875rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#a16207;margin:0 0 4px;">Correo electrónico</p>
                    <p style="font-family:'Sora',sans-serif;font-size:1.0625rem;font-weight:800;color:#0a0a0a;margin:0 0 2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">jonathansandoval@kyrosrd.com</p>
                    <p style="font-size:.8125rem;color:#71717a;margin:0;">Respuesta en menos de 24 h</p>
                </div>
                <svg width="20" height="20" fill="none" stroke="#d4d4d4" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>

        <!-- Remote notice -->
        <div style="display:flex;align-items:flex-start;gap:14px;background:#fff;border:1.5px solid #e0f2fe;border-radius:16px;padding:20px 24px;">
            <div style="flex-shrink:0;width:40px;height:40px;border-radius:12px;background:#f0f9ff;display:flex;align-items:center;justify-content:center;margin-top:1px;">
                <svg width="20" height="20" fill="none" stroke="#0284c7" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p style="font-size:.9375rem;font-weight:700;color:#0a0a0a;margin:0 0 4px;">Operamos 100% de forma remota</p>
                <p style="font-size:.875rem;color:#52525b;line-height:1.6;margin:0;">
                    Kyros Barber Cloud está disponible para barberías en cualquier país del mundo. No importa dónde estés, nuestro equipo te atiende de manera remota desde República Dominicana 🇩🇴.
                </p>
            </div>
        </div>

    </div>
    <style>@media(min-width:640px){#contact-cards{grid-template-columns:1fr 1fr !important;}}</style>
</section>

<!-- ═══════════════════════════════════════════
     FOOTER
══════════════════════════════════════════════ -->
<footer style="background:#0a0a0a;color:#fff;padding:64px 24px 32px;">
    <div style="max-width:1200px;margin:0 auto;">
        <div class="footer-grid" style="display:grid;grid-template-columns:1fr;gap:40px;margin-bottom:48px;">

            <!-- Brand -->
            <div>
                <a href="/" style="display:flex;align-items:center;gap:10px;text-decoration:none;margin-bottom:16px;">
                    <div style="width:32px;height:32px;background:linear-gradient(135deg,#c9901a,#e8b84b);border-radius:8px;display:flex;align-items:center;justify-content:center;">
                        <svg width="17" height="17" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243 4.243 3 3 0 004.243-4.243zm0-5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z"/></svg>
                    </div>
                    <span style="font-family:'Sora',sans-serif;font-weight:800;font-size:1rem;color:#fff;">Kyros Barber Cloud</span>
                </a>
                <p style="font-size:.875rem;color:#52525b;line-height:1.65;margin:0;max-width:280px;">Sistema profesional de gestión para barberías en cualquier parte del mundo.</p>
                <p style="font-size:.75rem;color:#3f3f46;line-height:1.6;margin:12px 0 0;max-width:280px;">Desarrollado por <span style="color:#e8b84b;font-weight:600;">Jonathan Sandoval</span>, CEO de Kyros · República Dominicana 🇩🇴</p>
            </div>

            <!-- Producto -->
            <div>
                <h4 style="font-size:.6875rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#52525b;margin:0 0 16px;">Producto</h4>
                <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:10px;">
                    <?php foreach([['Características','#features'],['Precios','#pricing'],['Demo','#demo']] as $l): ?>
                    <li><a href="<?php echo $l[1]; ?>" style="font-size:.875rem;color:#71717a;text-decoration:none;transition:color .18s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#71717a'"><?php echo $l[0]; ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Cuenta -->
            <div>
                <h4 style="font-size:.6875rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#52525b;margin:0 0 16px;">Cuenta</h4>
                <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:10px;">
                    <?php foreach([['Iniciar Sesión',BASE_URL.'/auth/login'],['Registrarse',BASE_URL.'/auth/register'],['Demo',BASE_URL.'/auth/login']] as $l): ?>
                    <li><a href="<?php echo $l[1]; ?>" style="font-size:.875rem;color:#71717a;text-decoration:none;transition:color .18s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#71717a'"><?php echo $l[0]; ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Soporte -->
            <div>
                <h4 style="font-size:.6875rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#52525b;margin:0 0 16px;">Soporte</h4>
                <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:10px;">
                    <?php foreach([['Documentación',BASE_URL.'/documentation'],['Tutoriales','#'],['Contacto','#contact']] as $l): ?>
                    <li><a href="<?php echo $l[1]; ?>" style="font-size:.875rem;color:#71717a;text-decoration:none;transition:color .18s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#71717a'"><?php echo $l[0]; ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <div style="padding-top:24px;border-top:1px solid rgba(255,255,255,.05);display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:8px;">
            <p style="font-size:.8125rem;color:#3f3f46;margin:0;">&copy; <?php echo date('Y'); ?> Kyros Barber Cloud. Todos los derechos reservados.</p>
            <p style="font-size:.8125rem;color:#3f3f46;margin:0;">Hecho con ♥ en 🇩🇴 para el mundo · por <a href="https://kyrosrd.com" target="_blank" rel="noopener noreferrer" style="color:#e8b84b;text-decoration:none;font-weight:600;">Jonathan Sandoval</a></p>
        </div>
    </div>
</footer>

</body>
</html>
