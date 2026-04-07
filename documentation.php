<?php require_once __DIR__ . '/config/config.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentacion para Clientes - Kyros Barber Cloud</title>
    <meta name="description" content="Guia para clientes de Kyros Barber Cloud: funciones, reservas online, WhatsApp via wa.me, recordatorios por email y preguntas frecuentes.">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sora:wght@700;800;900&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { font-family: 'Inter', sans-serif; background: #fff; margin: 0; color: #0a0a0a; }
        h1, h2, h3, h4 { font-family: 'Sora', sans-serif; }

        @keyframes fadeUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes shimmer { 0% { transform: translateX(-100%); } 100% { transform: translateX(100%); } }

        .fade-up { animation: fadeUp .6s cubic-bezier(.16,1,.3,1) both; }
        .fade-up2 { animation: fadeUp .6s .1s cubic-bezier(.16,1,.3,1) both; }

        .btn-gold {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            position: relative; overflow: hidden;
            background: linear-gradient(135deg,#c9901a 0%,#e8b84b 50%,#c9901a 100%);
            color: #0a0a0a; font-weight: 700; border: none; cursor: pointer; text-decoration: none;
            box-shadow: 0 4px 20px rgba(201,144,26,.35);
            transition: box-shadow .2s, transform .2s;
        }
        .btn-gold::after {
            content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%;
            background: linear-gradient(90deg,transparent,rgba(255,255,255,.3),transparent);
        }
        .btn-gold:hover::after { animation: shimmer .5s ease forwards; }
        .btn-gold:hover { box-shadow: 0 6px 28px rgba(201,144,26,.5); transform: translateY(-1px); }

        .btn-outline {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            background: transparent; color: #0a0a0a; font-weight: 600; border: 1.5px solid #e5e5e2;
            cursor: pointer; text-decoration: none; transition: border-color .18s, background .18s;
        }
        .btn-outline:hover { border-color: #0a0a0a; background: #f9f9f7; }

        .card { transition: transform .22s cubic-bezier(.4,0,.2,1), box-shadow .22s; }
        .card:hover { transform: translateY(-4px); box-shadow: 0 20px 44px rgba(0,0,0,.09); }

        .chip {
            display: inline-flex; align-items: center; gap: 8px; padding: 6px 12px;
            border-radius: 999px; font-size: .75rem; font-weight: 600;
            background: #f5f5f0; border: 1px solid #e9e9e2; color: #52525b;
        }

        .three-grid { display: grid; grid-template-columns: 1fr; gap: 16px; }
        .two-grid { display: grid; grid-template-columns: 1fr; gap: 16px; }

        @media(min-width:768px){
            #desk-nav { display: flex !important; }
            #mob-toggle { display: none !important; }
            .three-grid { grid-template-columns: repeat(3,1fr); }
            .two-grid { grid-template-columns: repeat(2,1fr); }
            .footer-grid { grid-template-columns: 2fr 1fr 1fr 1fr !important; }
        }

        [x-cloak]{ display:none !important; }
    </style>
</head>
<body x-data="{ mobileOpen: false }">

<header style="position:sticky;top:0;z-index:100;background:rgba(255,255,255,.96);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);border-bottom:1px solid #f0f0ec;">
    <div style="max-width:1200px;margin:0 auto;padding:0 24px;display:flex;align-items:center;justify-content:space-between;height:64px;">
        <a href="<?php echo BASE_URL; ?>/landing" style="display:flex;align-items:center;gap:10px;text-decoration:none;">
            <div style="width:32px;height:32px;background:linear-gradient(135deg,#c9901a,#e8b84b);border-radius:8px;display:flex;align-items:center;justify-content:center;">
                <svg width="18" height="18" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243 4.243 3 3 0 004.243-4.243zm0-5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z"/></svg>
            </div>
            <span style="font-family:'Sora',sans-serif;font-weight:800;font-size:1.0625rem;color:#0a0a0a;letter-spacing:-.02em;">Kyros Barber Cloud</span>
            <span style="padding:2px 8px;background:#f0f9ff;border:1px solid #bae6fd;border-radius:999px;font-size:.625rem;font-weight:700;color:#0284c7;letter-spacing:.06em;">GUIA</span>
        </a>

        <nav style="display:none;align-items:center;gap:24px;" id="desk-nav">
            <a href="#inicio" style="font-size:.875rem;font-weight:500;color:#52525b;text-decoration:none;">Inicio</a>
            <a href="#funciones" style="font-size:.875rem;font-weight:500;color:#52525b;text-decoration:none;">Funciones</a>
            <a href="#como-funciona" style="font-size:.875rem;font-weight:500;color:#52525b;text-decoration:none;">Como funciona</a>
            <a href="#comunicacion" style="font-size:.875rem;font-weight:500;color:#52525b;text-decoration:none;">Comunicacion</a>
            <a href="#faq" style="font-size:.875rem;font-weight:500;color:#52525b;text-decoration:none;">FAQ</a>
            <a href="<?php echo BASE_URL; ?>/auth/login" style="font-size:.875rem;font-weight:500;color:#52525b;text-decoration:none;">Iniciar Sesion</a>
            <a href="<?php echo BASE_URL; ?>/landing" class="btn-gold" style="padding:9px 20px;border-radius:10px;font-size:.875rem;">Volver a Landing</a>
        </nav>

        <button @click="mobileOpen = !mobileOpen" style="display:flex;align-items:center;justify-content:center;width:40px;height:40px;background:#f5f5f0;border:none;border-radius:10px;cursor:pointer;" id="mob-toggle">
            <svg x-show="!mobileOpen" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
            <svg x-show="mobileOpen" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:none;"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <div x-show="mobileOpen" style="background:#fff;border-top:1px solid #f0f0ec;padding:16px 24px;display:flex;flex-direction:column;gap:12px;" x-cloak>
        <a href="#inicio" style="font-size:.9375rem;font-weight:500;color:#3f3f46;text-decoration:none;padding:8px 0;">Inicio</a>
        <a href="#funciones" style="font-size:.9375rem;font-weight:500;color:#3f3f46;text-decoration:none;padding:8px 0;">Funciones</a>
        <a href="#como-funciona" style="font-size:.9375rem;font-weight:500;color:#3f3f46;text-decoration:none;padding:8px 0;">Como funciona</a>
        <a href="#comunicacion" style="font-size:.9375rem;font-weight:500;color:#3f3f46;text-decoration:none;padding:8px 0;">Comunicacion</a>
        <a href="#faq" style="font-size:.9375rem;font-weight:500;color:#3f3f46;text-decoration:none;padding:8px 0;">FAQ</a>
        <a href="<?php echo BASE_URL; ?>/landing" class="btn-gold" style="padding:12px 20px;border-radius:12px;font-size:.9375rem;text-align:center;">Volver a Landing</a>
    </div>
</header>

<section id="inicio" style="background:#0a0a0a;color:#fff;padding:88px 24px 72px;position:relative;overflow:hidden;">
    <div style="position:absolute;top:-110px;left:50%;transform:translateX(-50%);width:760px;height:420px;background:radial-gradient(ellipse,rgba(201,144,26,.16) 0%,transparent 70%);pointer-events:none;"></div>

    <div style="max-width:1100px;margin:0 auto;position:relative;z-index:1;">
        <div class="fade-up" style="display:flex;justify-content:center;margin-bottom:24px;">
            <div style="display:inline-flex;align-items:center;gap:8px;padding:6px 16px;border-radius:999px;background:rgba(201,144,26,.12);border:1px solid rgba(201,144,26,.3);">
                <span style="width:6px;height:6px;background:#e8b84b;border-radius:50%;display:block;"></span>
                <span style="font-size:.75rem;font-weight:600;color:#e8b84b;letter-spacing:.06em;">DOCUMENTACION PARA CLIENTES</span>
            </div>
        </div>

        <div class="fade-up" style="text-align:center;max-width:820px;margin:0 auto 24px;">
            <h1 style="font-size:clamp(2.3rem,6vw,4rem);font-weight:900;line-height:1.06;letter-spacing:-.04em;margin:0 0 18px;">
                Todo lo que necesitas para
                <span style="background:linear-gradient(135deg,#c9901a 0%,#e8b84b 60%,#c9901a 100%);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;display:block;">usar Kyros sin complicaciones</span>
            </h1>
            <p style="font-size:1.0625rem;color:#9ca3af;line-height:1.75;margin:0 auto;max-width:720px;">
                Aprende a publicar tu pagina de reservas, gestionar tu equipo, organizar citas y comunicarte con tus clientes
                usando WhatsApp via wa.me y recordatorios por email.
            </p>
        </div>

        <div class="fade-up2" style="display:flex;flex-wrap:wrap;justify-content:center;gap:10px;">
            <?php foreach(['Reservas online','Gestion de barberos','Servicios y precios','WhatsApp via wa.me','Recordatorios por email','Soporte en espanol'] as $chip): ?>
            <span class="chip" style="background:rgba(255,255,255,.06);border-color:rgba(255,255,255,.16);color:#d4d4d8;"><?php echo $chip; ?></span>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section id="funciones" style="background:#fafaf8;padding:88px 24px;">
    <div style="max-width:1200px;margin:0 auto;">
        <div style="text-align:center;margin-bottom:44px;">
            <span style="display:inline-block;padding:5px 14px;background:#fef9ee;border:1px solid #f0d88a;border-radius:999px;font-size:.6875rem;font-weight:700;letter-spacing:.1em;color:#a16207;text-transform:uppercase;margin-bottom:14px;">Funciones principales</span>
            <h2 style="font-size:clamp(2rem,5vw,3rem);font-weight:900;color:#0a0a0a;letter-spacing:-.03em;margin:0 0 12px;">Que puedes hacer en la plataforma</h2>
        </div>

        <div class="three-grid">
            <?php
            $features = [
                ['Reservas 24/7','Tus clientes reservan desde la pagina publica de tu barberia, en cualquier momento.'],
                ['Agenda clara','Visualiza citas del dia, pendientes y completadas para trabajar con orden.'],
                ['Equipo y horarios','Gestiona barberos, disponibilidad y servicios por profesional.'],
                ['Catalogo de servicios','Crea y actualiza precios, duraciones y descripciones.'],
                ['Clientes frecuentes','Guarda historial para mejorar seguimiento y fidelizacion.'],
                ['Reportes de negocio','Consulta rendimiento de citas, ingresos y operacion general.']
            ];
            foreach ($features as $f): ?>
            <div class="card" style="background:#fff;border:1.5px solid #ebebeb;border-radius:18px;padding:24px;">
                <h3 style="font-size:1.02rem;margin:0 0 8px;color:#0a0a0a;"><?php echo $f[0]; ?></h3>
                <p style="font-size:.9rem;color:#71717a;line-height:1.7;margin:0;"><?php echo $f[1]; ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section id="como-funciona" style="background:#fff;padding:88px 24px;">
    <div style="max-width:1100px;margin:0 auto;">
        <div style="text-align:center;margin-bottom:44px;">
            <span style="display:inline-block;padding:5px 14px;background:#fef9ee;border:1px solid #f0d88a;border-radius:999px;font-size:.6875rem;font-weight:700;letter-spacing:.1em;color:#a16207;text-transform:uppercase;margin-bottom:14px;">Como funciona</span>
            <h2 style="font-size:clamp(2rem,5vw,3rem);font-weight:900;color:#0a0a0a;letter-spacing:-.03em;margin:0 0 12px;">Tu flujo en 3 pasos</h2>
        </div>

        <div class="three-grid">
            <?php
            $steps = [
                ['01','Crea tu cuenta','Registra tu barberia y completa los datos basicos del negocio.'],
                ['02','Configura tu oferta','Agrega barberos, servicios, horarios y precios para abrir agenda.'],
                ['03','Recibe reservas','Comparte tu enlace y empieza a recibir citas online en minutos.']
            ];
            foreach ($steps as $s): ?>
            <div class="card" style="background:#fafaf8;border:1.5px solid #ecece6;border-radius:18px;padding:24px;">
                <div style="width:40px;height:40px;border-radius:12px;background:#0a0a0a;color:#e8b84b;display:flex;align-items:center;justify-content:center;font-family:'Sora',sans-serif;font-weight:800;margin-bottom:12px;"><?php echo $s[0]; ?></div>
                <h3 style="font-size:1.02rem;margin:0 0 8px;"><?php echo $s[1]; ?></h3>
                <p style="font-size:.9rem;color:#71717a;line-height:1.7;margin:0;"><?php echo $s[2]; ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section id="comunicacion" style="background:#fafaf8;padding:88px 24px;">
    <div style="max-width:1100px;margin:0 auto;">
        <div style="text-align:center;margin-bottom:44px;">
            <span style="display:inline-block;padding:5px 14px;background:#fef9ee;border:1px solid #f0d88a;border-radius:999px;font-size:.6875rem;font-weight:700;letter-spacing:.1em;color:#a16207;text-transform:uppercase;margin-bottom:14px;">Comunicacion con clientes</span>
            <h2 style="font-size:clamp(2rem,5vw,3rem);font-weight:900;color:#0a0a0a;letter-spacing:-.03em;margin:0 0 12px;">Canales disponibles</h2>
        </div>

        <div class="two-grid">
            <div class="card" style="background:#fff;border:1.5px solid #ebebeb;border-radius:18px;padding:24px;">
                <h3 style="font-size:1.05rem;margin:0 0 8px;">WhatsApp via wa.me</h3>
                <p style="font-size:.9rem;color:#71717a;line-height:1.7;margin:0 0 10px;">Contacto rapido para seguimiento y coordinacion de citas desde enlaces directos de WhatsApp.</p>
                <ul style="margin:0;padding-left:18px;color:#52525b;line-height:1.8;font-size:.875rem;">
                    <li>Enlaces directos a conversacion</li>
                    <li>Acceso facil desde vistas de operacion</li>
                    <li>Mejor respuesta de clientes</li>
                </ul>
            </div>

            <div class="card" style="background:#fff;border:1.5px solid #ebebeb;border-radius:18px;padding:24px;">
                <h3 style="font-size:1.05rem;margin:0 0 8px;">Recordatorios por email</h3>
                <p style="font-size:.9rem;color:#71717a;line-height:1.7;margin:0 0 10px;">Notificaciones para disminuir ausencias y mantener al cliente informado antes de su cita.</p>
                <ul style="margin:0;padding-left:18px;color:#52525b;line-height:1.8;font-size:.875rem;">
                    <li>Recordatorios automáticos de cita</li>
                    <li>Mensajes claros y puntuales</li>
                    <li>Mayor puntualidad de agenda</li>
                </ul>
            </div>
        </div>

        <div class="card" style="margin-top:16px;background:#0a0a0a;border:1.5px solid #0a0a0a;border-radius:18px;padding:24px;text-align:center;">
            <p style="font-size:.95rem;color:#d4d4d8;line-height:1.8;margin:0;">
                Importante: la comunicacion oficial de la plataforma es por <strong style="color:#e8b84b;">WhatsApp via wa.me</strong>
                y <strong style="color:#e8b84b;">recordatorios por email</strong>.
            </p>
        </div>
    </div>
</section>

<section id="faq" style="background:#fff;padding:88px 24px;">
    <div style="max-width:1000px;margin:0 auto;">
        <div style="text-align:center;margin-bottom:44px;">
            <span style="display:inline-block;padding:5px 14px;background:#fef9ee;border:1px solid #f0d88a;border-radius:999px;font-size:.6875rem;font-weight:700;letter-spacing:.1em;color:#a16207;text-transform:uppercase;margin-bottom:14px;">Preguntas frecuentes</span>
            <h2 style="font-size:clamp(2rem,5vw,3rem);font-weight:900;color:#0a0a0a;letter-spacing:-.03em;margin:0 0 12px;">Respuestas rapidas</h2>
        </div>

        <div class="two-grid">
            <?php
            $faq = [
                ['Cuanto tarda la configuracion inicial?','La mayoria de barberias quedan listas en menos de 15 minutos.'],
                ['Puedo cambiar precios y horarios cuando quiera?','Si, puedes editar servicios, duraciones, disponibilidad y precios en cualquier momento.'],
                ['Como reciben los clientes el enlace de reserva?','Puedes compartir tu URL publica por WhatsApp, Instagram, Facebook o cualquier canal digital.'],
                ['La plataforma envia SMS?','No. La comunicacion se realiza por WhatsApp via wa.me y recordatorios por email.'],
                ['Funciona en celular?','Si, tanto el panel como las paginas publicas de reservas son responsive.'],
                ['Puedo cancelar cuando quiera?','Si, los planes son flexibles y se gestionan segun las condiciones comerciales vigentes.']
            ];
            foreach ($faq as $q): ?>
            <div class="card" style="background:#fafaf8;border:1.5px solid #ecece6;border-radius:18px;padding:22px;">
                <h3 style="font-size:1rem;margin:0 0 8px;color:#0a0a0a;"><?php echo $q[0]; ?></h3>
                <p style="font-size:.9rem;color:#71717a;line-height:1.7;margin:0;"><?php echo $q[1]; ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section style="background:#0a0a0a;padding:80px 24px;text-align:center;">
    <div style="max-width:700px;margin:0 auto;">
        <h2 style="font-size:clamp(2rem,5vw,3rem);font-weight:900;color:#fff;letter-spacing:-.03em;margin:0 0 14px;">Listo para empezar?</h2>
        <p style="font-size:1rem;color:#a1a1aa;line-height:1.7;margin:0 0 26px;">Crea tu cuenta, publica tu enlace y comienza a recibir reservas hoy mismo.</p>
        <div style="display:flex;justify-content:center;flex-wrap:wrap;gap:10px;">
            <a href="<?php echo BASE_URL; ?>/auth/register" class="btn-gold" style="padding:14px 24px;border-radius:12px;">Comenzar ahora</a>
            <a href="<?php echo BASE_URL; ?>/landing" class="btn-outline" style="padding:14px 24px;border-radius:12px;color:#fff;border-color:rgba(255,255,255,.2);" onmouseover="this.style.background='rgba(255,255,255,.08)'" onmouseout="this.style.background='transparent'">Ver Landing</a>
        </div>
    </div>
</section>

<footer style="background:#0a0a0a;color:#fff;padding:64px 24px 32px;">
    <div style="max-width:1200px;margin:0 auto;">
        <div class="footer-grid" style="display:grid;grid-template-columns:1fr;gap:40px;margin-bottom:48px;">
            <div>
                <a href="<?php echo BASE_URL; ?>/landing" style="display:flex;align-items:center;gap:10px;text-decoration:none;margin-bottom:16px;">
                    <div style="width:32px;height:32px;background:linear-gradient(135deg,#c9901a,#e8b84b);border-radius:8px;display:flex;align-items:center;justify-content:center;">
                        <svg width="17" height="17" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243 4.243 3 3 0 004.243-4.243zm0-5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z"/></svg>
                    </div>
                    <span style="font-family:'Sora',sans-serif;font-weight:800;font-size:1rem;color:#fff;">Kyros Barber Cloud</span>
                </a>
                <p style="font-size:.875rem;color:#52525b;line-height:1.65;margin:0;max-width:320px;">Guia para clientes orientada a uso diario, reservas y comunicacion con clientes.</p>
            </div>

            <div>
                <h4 style="font-size:.6875rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#52525b;margin:0 0 16px;">Producto</h4>
                <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:10px;">
                    <?php foreach([['Landing',BASE_URL.'/landing'],['Documentacion',BASE_URL.'/documentation'],['Demo',BASE_URL.'/auth/login']] as $l): ?>
                    <li><a href="<?php echo $l[1]; ?>" style="font-size:.875rem;color:#71717a;text-decoration:none;"><?php echo $l[0]; ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div>
                <h4 style="font-size:.6875rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#52525b;margin:0 0 16px;">Ayuda</h4>
                <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:10px;">
                    <?php foreach([['Funciones','#funciones'],['Comunicacion','#comunicacion'],['FAQ','#faq']] as $l): ?>
                    <li><a href="<?php echo $l[1]; ?>" style="font-size:.875rem;color:#71717a;text-decoration:none;"><?php echo $l[0]; ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div>
                <h4 style="font-size:.6875rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#52525b;margin:0 0 16px;">Cuenta</h4>
                <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:10px;">
                    <?php foreach([['Iniciar Sesion',BASE_URL.'/auth/login'],['Crear Cuenta',BASE_URL.'/auth/register'],['Ver Planes',BASE_URL.'/landing#pricing']] as $l): ?>
                    <li><a href="<?php echo $l[1]; ?>" style="font-size:.875rem;color:#71717a;text-decoration:none;"><?php echo $l[0]; ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <div style="padding-top:24px;border-top:1px solid rgba(255,255,255,.05);display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:8px;">
            <p style="font-size:.8125rem;color:#3f3f46;margin:0;">&copy; <?php echo date('Y'); ?> Kyros Barber Cloud. Todos los derechos reservados.</p>
            <p style="font-size:.8125rem;color:#3f3f46;margin:0;">Comunicacion: WhatsApp via wa.me y recordatorios por email.</p>
        </div>
    </div>
</footer>

</body>
</html>
