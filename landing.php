<?php require_once __DIR__ . '/config/config.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kyros Barber Cloud — Sistema de Gestión para Barberías RD</title>
    <meta name="description" content="Sistema completo de gestión de citas, clientes y finanzas para barberías en República Dominicana. 100% en la nube, fácil de usar y con reservas online.">
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
            <span style="font-family:'Sora',sans-serif;font-weight:800;font-size:1.0625rem;color:#0a0a0a;letter-spacing:-.02em;">Kyros Barber Cloud</span>
            <span style="padding:2px 8px;background:#f0f9ff;border:1px solid #bae6fd;border-radius:999px;font-size:.625rem;font-weight:700;color:#0284c7;letter-spacing:.06em;">RD</span>
        </a>

        <!-- Desktop nav -->
        <nav style="display:none;align-items:center;gap:32px;" id="desk-nav">
            <a href="#features" style="font-size:.875rem;font-weight:500;color:#52525b;text-decoration:none;transition:color .18s;" onmouseover="this.style.color='#0a0a0a'" onmouseout="this.style.color='#52525b'">Características</a>
            <a href="#pricing"  style="font-size:.875rem;font-weight:500;color:#52525b;text-decoration:none;transition:color .18s;" onmouseover="this.style.color='#0a0a0a'" onmouseout="this.style.color='#52525b'">Precios</a>
            <a href="#demo"     style="font-size:.875rem;font-weight:500;color:#52525b;text-decoration:none;transition:color .18s;" onmouseover="this.style.color='#0a0a0a'" onmouseout="this.style.color='#52525b'">Demo</a>
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
    <div x-show="mobileOpen" style="background:#fff;border-top:1px solid #f0f0ec;padding:16px 24px;display:flex;flex-direction:column;gap:12px;" x-cloak>
        <a href="#features"   style="font-size:.9375rem;font-weight:500;color:#3f3f46;text-decoration:none;padding:8px 0;">Características</a>
        <a href="#pricing"    style="font-size:.9375rem;font-weight:500;color:#3f3f46;text-decoration:none;padding:8px 0;">Precios</a>
        <a href="#demo"       style="font-size:.9375rem;font-weight:500;color:#3f3f46;text-decoration:none;padding:8px 0;">Demo</a>
        <a href="<?php echo BASE_URL; ?>/auth/login" style="font-size:.9375rem;font-weight:500;color:#3f3f46;text-decoration:none;padding:8px 0;">Iniciar Sesión</a>
        <a href="<?php echo BASE_URL; ?>/auth/register" class="btn-gold" style="padding:12px 20px;border-radius:12px;font-size:.9375rem;text-align:center;">Empezar Gratis</a>
    </div>
</header>

<style>
@media(min-width:768px){
    #desk-nav  { display:flex !important; }
    #mob-toggle{ display:none !important; }
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
                Sistema completo de gestión de citas, clientes y finanzas para barberías en República Dominicana. 100% en la nube y con reservas online.
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
            <?php $items = ['500+ Barberías Activas','50K+ Citas Mensuales','99.9% Uptime garantizado','4.8 Satisfacción promedio','República Dominicana #1','Soporte 24/7 en español']; for($r=0;$r<4;$r++): foreach($items as $it): ?>
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
            <p style="font-size:1.0625rem;color:#71717a;max-width:480px;margin:0 auto;line-height:1.65;">Herramientas profesionales diseñadas para barberías dominicanas</p>
        </div>

        <div class="feat-grid" style="display:grid;grid-template-columns:1fr;gap:16px;">
            <?php
            $features = [
                ['Reservas Online 24/7','Tus clientes agendan citas desde cualquier lugar, a cualquier hora. Página personalizada para tu barbería con URL propia.','M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z','#c9901a','#fef9ee'],
                ['Dashboard Completo','Visualiza estadísticas, ingresos y citas en tiempo real. Reportes detallados para tomar mejores decisiones de negocio.','M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z','#818cf8','#f0f9ff'],
                ['Gestión de Clientes','Base de datos completa con historial de servicios, preferencias y recordatorios automáticos vía WhatsApp.','M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z','#22c55e','#f0fdf4'],
                ['Gestión de Barberos','Administra horarios, comisiones y rendimiento de tu equipo. Cada barbero con su propio perfil público y portal.','M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243 4.243 3 3 0 004.243-4.243zm0-5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z','#0a0a0a','#f5f5f0'],
                ['Control Financiero','Registra ingresos, gastos y comisiones. Reportes fiscales y análisis de rentabilidad por servicio o barbero.','M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z','#e8b84b','#fffbeb'],
                ['WhatsApp Integrado','Recordatorios automáticos a clientes vía WhatsApp. Reduce ausencias hasta 80% y mantiene la comunicación fluida.','M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z','#22c55e','#f0fdf4'],
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
    <div style="max-width:1100px;margin:0 auto;">
        <div style="text-align:center;margin-bottom:64px;">
            <span style="display:inline-block;padding:5px 14px;background:#fef9ee;border:1px solid #f0d88a;border-radius:999px;font-size:.6875rem;font-weight:700;letter-spacing:.1em;color:#a16207;text-transform:uppercase;margin-bottom:14px;">Precios</span>
            <h2 style="font-size:clamp(2rem,5vw,3rem);font-weight:900;color:#0a0a0a;letter-spacing:-.03em;margin:0 0 12px;">Planes Transparentes</h2>
            <p style="font-size:1.0625rem;color:#71717a;margin:0;">Sin costos ocultos. Cancela cuando quieras.</p>
        </div>

        <div class="price-grid" style="display:grid;grid-template-columns:1fr;gap:16px;align-items:start;">

            <!-- Básico -->
            <div class="card" style="background:#fff;border:1.5px solid #ebebeb;border-radius:24px;padding:32px;">
                <h3 style="font-family:'Sora',sans-serif;font-size:1.25rem;font-weight:800;color:#0a0a0a;margin:0 0 4px;">Básico</h3>
                <p style="font-size:.875rem;color:#71717a;margin:0 0 20px;">Ideal para empezar</p>
                <div style="margin-bottom:24px;">
                    <span style="font-family:'Sora',sans-serif;font-size:2.5rem;font-weight:900;color:#0a0a0a;">RD$1,500</span>
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
                    <span style="font-family:'Sora',sans-serif;font-size:2.5rem;font-weight:900;color:#fff;">RD$3,000</span>
                    <span style="color:#71717a;font-size:.875rem;">/mes</span>
                </div>
                <ul style="list-style:none;padding:0;margin:0 0 28px;display:flex;flex-direction:column;gap:10px;">
                    <?php foreach(['Citas ilimitadas','Hasta 5 barberos','Notificaciones WhatsApp/SMS','Reportes avanzados','Soporte prioritario'] as $li): ?>
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
                    <span style="font-family:'Sora',sans-serif;font-size:2.5rem;font-weight:900;color:#0a0a0a;">RD$5,000</span>
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
                <p style="font-size:.875rem;color:#52525b;line-height:1.65;margin:0;max-width:280px;">Sistema profesional de gestión para barberías en República Dominicana.</p>
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
                    <?php foreach([['Documentación','#'],['Tutoriales','#'],['Contacto','#']] as $l): ?>
                    <li><a href="<?php echo $l[1]; ?>" style="font-size:.875rem;color:#71717a;text-decoration:none;transition:color .18s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#71717a'"><?php echo $l[0]; ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <div style="padding-top:24px;border-top:1px solid rgba(255,255,255,.05);display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:8px;">
            <p style="font-size:.8125rem;color:#3f3f46;margin:0;">&copy; <?php echo date('Y'); ?> Kyros Barber Cloud. Todos los derechos reservados.</p>
            <p style="font-size:.8125rem;color:#3f3f46;margin:0;">Hecho con ♥ en República Dominicana</p>
        </div>
    </div>
</footer>

</body>
</html>
