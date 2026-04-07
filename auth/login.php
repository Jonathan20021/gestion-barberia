<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Auth.php';
require_once BASE_PATH . '/core/Helpers.php';

if (Auth::check()) {
    $role = $_SESSION['user_role'];
    switch ($role) {
        case 'superadmin': redirect(BASE_URL . '/admin/dashboard'); break;
        case 'owner':      redirect(BASE_URL . '/dashboard'); break;
        case 'barber':     redirect(BASE_URL . '/dashboard/barber'); break;
        default:           redirect(BASE_URL . '/'); break;
    }
}

$error = isset($_GET['error']) ? trim((string)$_GET['error']) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = input('email');
    $password = input('password');

    if (empty($email) || empty($password)) {
        $error = 'Por favor complete todos los campos';
    } else {
        $auth = new Auth();
        if ($auth->login($email, $password)) {
            $role = $_SESSION['user_role'];
            switch ($role) {
                case 'superadmin': redirect(BASE_URL . '/admin/dashboard'); break;
                case 'owner':      redirect(BASE_URL . '/dashboard'); break;
                case 'barber':     redirect(BASE_URL . '/dashboard/barber'); break;
                default:           redirect(BASE_URL . '/'); break;
            }
        } else {
            $error = $auth->getLastError();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión — Kyros Barber Cloud</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sora:wght@700;800;900&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
        html, body { height:100%; }
        body { font-family:'Inter',sans-serif; background:#0a0a0a; min-height:100vh; display:flex; color:#f5f5f0; }
        h1,h2,h3 { font-family:'Sora',sans-serif; }

        /* ── Animations ── */
        @keyframes shimmer { 0%{transform:translateX(-100%)} 100%{transform:translateX(100%)} }
        @keyframes fadeUp  { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }
        @keyframes pulse   { 0%,100%{opacity:1} 50%{opacity:.4} }

        .fade-up  { animation:fadeUp .6s cubic-bezier(.16,1,.3,1) both; }
        .fade-up2 { animation:fadeUp .6s .08s cubic-bezier(.16,1,.3,1) both; }
        .fade-up3 { animation:fadeUp .6s .16s cubic-bezier(.16,1,.3,1) both; }

        /* ── Gold button ── */
        .btn-gold {
            display:flex; align-items:center; justify-content:center; gap:9px; width:100%;
            position:relative; overflow:hidden;
            background:linear-gradient(135deg,#c9901a 0%,#e8b84b 48%,#c9901a 100%);
            color:#0a0a0a; font-weight:700; font-size:.9375rem;
            border:none; cursor:pointer; padding:15px 24px; border-radius:13px;
            box-shadow:0 4px 24px rgba(201,144,26,.35);
            transition:box-shadow .2s, transform .2s;
            font-family:'Inter',sans-serif; letter-spacing:.01em;
        }
        .btn-gold::after {
            content:''; position:absolute; top:0; left:-100%; width:100%; height:100%;
            background:linear-gradient(90deg,transparent,rgba(255,255,255,.3),transparent);
        }
        .btn-gold:hover::after { animation:shimmer .5s ease forwards; }
        .btn-gold:hover { box-shadow:0 8px 32px rgba(201,144,26,.55); transform:translateY(-1px); }
        .btn-gold:active { transform:translateY(0); }

        /* ── Inputs ── */
        .field-wrap { position:relative; }
        .field-icon {
            position:absolute; left:15px; top:50%; transform:translateY(-50%);
            color:#3f3f46; pointer-events:none; display:flex; transition:color .18s;
        }
        .field-input {
            width:100%; padding:14px 14px 14px 44px;
            background:#111111; border:1.5px solid #222222; border-radius:12px;
            color:#f5f5f0; font-size:.9375rem; font-family:'Inter',sans-serif;
            outline:none; transition:border-color .18s, box-shadow .18s, background .18s;
            -webkit-appearance:none;
        }
        .field-input::placeholder { color:#3f3f46; }
        .field-input:focus { background:#161616; border-color:#c9901a; box-shadow:0 0 0 3px rgba(201,144,26,.12); }
        .field-input:focus ~ .field-icon,
        .field-wrap:focus-within .field-icon { color:#c9901a; }

        /* ── Custom checkbox ── */
        .custom-check {
            width:17px; height:17px; border:1.5px solid #333; border-radius:5px;
            background:#111; cursor:pointer; appearance:none; -webkit-appearance:none;
            flex-shrink:0; position:relative; transition:background .15s, border-color .15s;
        }
        .custom-check:checked { background:#c9901a; border-color:#c9901a; }
        .custom-check:checked::after {
            content:''; position:absolute; left:4px; top:1px;
            width:6px; height:10px; border:2px solid #0a0a0a;
            border-left:none; border-top:none; transform:rotate(45deg);
        }

        /* ── Stat badge ── */
        .stat-badge {
            display:flex; align-items:center; gap:10px;
            background:rgba(255,255,255,.04); border:1px solid #222;
            border-radius:12px; padding:12px 16px;
        }

        /* ── Responsive ── */
        @media(min-width:1024px){
            .split-left   { display:flex !important; }
            .mobile-logo  { display:none !important; }
        }
        @media(max-width:1023px){
            .split-right-inner { max-width:100% !important; }
        }
    </style>
</head>
<body>

<div style="display:flex;width:100%;min-height:100vh;">

    <!-- ══════════════════════════════════
         LEFT PANEL — branding
    ══════════════════════════════════════ -->
    <div class="split-left" style="display:none;flex:0 0 460px;flex-direction:column;justify-content:space-between;padding:44px 48px;background:#0d0d0d;border-right:1px solid #1a1a1a;position:relative;overflow:hidden;">

        <!-- Background decorations -->
        <div style="position:absolute;inset:0;background-image:radial-gradient(circle,rgba(255,255,255,.025) 1px,transparent 1px);background-size:24px 24px;pointer-events:none;"></div>
        <div style="position:absolute;top:-160px;left:-100px;width:480px;height:480px;background:radial-gradient(circle,rgba(201,144,26,.09) 0%,transparent 65%);pointer-events:none;"></div>
        <div style="position:absolute;bottom:-80px;right:-80px;width:300px;height:300px;background:radial-gradient(circle,rgba(201,144,26,.05) 0%,transparent 65%);pointer-events:none;"></div>

        <!-- Logo -->
        <a href="<?php echo BASE_URL; ?>/" style="display:flex;align-items:center;gap:12px;text-decoration:none;position:relative;z-index:1;">
            <div style="width:42px;height:42px;background:linear-gradient(135deg,#c9901a,#e8b84b);border-radius:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 4px 16px rgba(201,144,26,.3);">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0a0a0a" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                </svg>
            </div>
            <span style="font-family:'Sora',sans-serif;font-weight:800;font-size:1.125rem;color:#f5f5f0;letter-spacing:-.02em;">Kyros Barber Cloud</span>
        </a>

        <!-- Main content -->
        <div style="position:relative;z-index:1;">

            <!-- Badge -->
            <div style="display:inline-flex;align-items:center;gap:8px;background:rgba(201,144,26,.1);border:1px solid rgba(201,144,26,.22);border-radius:100px;padding:6px 14px;margin-bottom:28px;">
                <span style="width:6px;height:6px;background:#c9901a;border-radius:50%;display:inline-block;animation:pulse 2s ease-in-out infinite;"></span>
                <span style="font-size:.6875rem;font-weight:700;color:#e8b84b;letter-spacing:.08em;text-transform:uppercase;">Sistema Activo</span>
            </div>

            <h2 style="font-size:2.5rem;font-weight:900;color:#f5f5f0;line-height:1.12;margin-bottom:18px;letter-spacing:-.03em;">
                Gestiona tu<br>
                <span style="background:linear-gradient(90deg,#c9901a,#f0cc6a);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">barbería</span><br>
                desde aquí.
            </h2>
            <p style="font-size:.9375rem;color:#52525b;line-height:1.7;margin-bottom:36px;max-width:300px;">
                Reservas, clientes, barberos y finanzas — todo en un solo lugar, hecho para RD.
            </p>

            <!-- Feature list -->
            <?php
            $features = [
                ['Reservas online 24/7 sin llamadas',       'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
                ['Panel completo de barberos y horarios',    'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
                ['Reportes y estadísticas en tiempo real',   'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                ['Página pública para reservas de clientes', 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064'],
            ];
            foreach ($features as $f): ?>
            <div style="display:flex;align-items:center;gap:14px;margin-bottom:14px;">
                <div style="width:34px;height:34px;background:rgba(201,144,26,.08);border:1px solid rgba(201,144,26,.16);border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#c9901a" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="<?php echo $f[1]; ?>"/>
                    </svg>
                </div>
                <span style="font-size:.875rem;color:#71717a;"><?php echo $f[0]; ?></span>
            </div>
            <?php endforeach; ?>

            <!-- Mini stats -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:28px;">
                <div class="stat-badge">
                    <div>
                        <p style="font-size:1.125rem;font-weight:800;color:#f5f5f0;font-family:'Sora',sans-serif;">500+</p>
                        <p style="font-size:.75rem;color:#52525b;margin-top:1px;">Barberías activas</p>
                    </div>
                </div>
                <div class="stat-badge">
                    <div>
                        <p style="font-size:1.125rem;font-weight:800;color:#c9901a;font-family:'Sora',sans-serif;">24/7</p>
                        <p style="font-size:.75rem;color:#52525b;margin-top:1px;">Disponible siempre</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div style="position:relative;z-index:1;padding-top:24px;border-top:1px solid #1a1a1a;">
            <p style="font-size:.8125rem;color:#3f3f46;line-height:1.65;font-style:italic;">
                "La herramienta que toda barbería profesional en República Dominicana necesita."
            </p>
            <div style="display:flex;align-items:center;gap:10px;margin-top:14px;">
                <div style="width:32px;height:32px;background:linear-gradient(135deg,#c9901a,#e8b84b);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#0a0a0a" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </div>
                <div>
                    <p style="font-size:.8125rem;font-weight:600;color:#a1a1aa;">Equipo Kyros</p>
                    <p style="font-size:.75rem;color:#3f3f46;">República Dominicana</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════
         RIGHT PANEL — form
    ══════════════════════════════════════ -->
    <div style="flex:1;display:flex;align-items:center;justify-content:center;padding:40px 24px;background:#0a0a0a;position:relative;">

        <!-- Subtle bg glow -->
        <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:600px;height:600px;background:radial-gradient(circle,rgba(201,144,26,.04) 0%,transparent 60%);pointer-events:none;"></div>

        <div class="fade-up" style="width:100%;max-width:400px;position:relative;z-index:1;">

            <!-- Mobile logo -->
            <div class="mobile-logo" style="display:flex;justify-content:center;margin-bottom:36px;">
                <a href="<?php echo BASE_URL; ?>/" style="display:flex;align-items:center;gap:10px;text-decoration:none;">
                    <div style="width:38px;height:38px;background:linear-gradient(135deg,#c9901a,#e8b84b);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0a0a0a" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </div>
                    <span style="font-family:'Sora',sans-serif;font-weight:800;font-size:1.0625rem;color:#f5f5f0;">Kyros Barber Cloud</span>
                </a>
            </div>

            <!-- Heading -->
            <div class="fade-up" style="margin-bottom:28px;">
                <h2 style="font-size:2rem;font-weight:900;color:#f5f5f0;margin-bottom:7px;letter-spacing:-.03em;">Bienvenido</h2>
                <p style="font-size:.9375rem;color:#52525b;">Ingresa tus credenciales para acceder al panel</p>
            </div>

            <!-- Error -->
            <?php if ($error): ?>
            <div class="fade-up" style="display:flex;align-items:flex-start;gap:11px;background:rgba(239,68,68,.07);border:1px solid rgba(239,68,68,.2);border-radius:12px;padding:14px 16px;margin-bottom:22px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#f87171" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:1px;">
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <p style="font-size:.875rem;color:#f87171;line-height:1.5;"><?php echo e($error); ?></p>
            </div>
            <?php endif; ?>

            <!-- Form -->
            <form method="POST" class="fade-up2" style="display:flex;flex-direction:column;gap:18px;">

                <!-- Email -->
                <div>
                    <label for="email" style="display:block;font-size:.75rem;font-weight:700;color:#52525b;margin-bottom:9px;letter-spacing:.07em;text-transform:uppercase;">
                        Correo Electrónico
                    </label>
                    <div class="field-wrap">
                        <span class="field-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>
                            </svg>
                        </span>
                        <input type="email" id="email" name="email" class="field-input"
                               placeholder="tu@email.com" required autocomplete="email"
                               value="<?php echo e($_POST['email'] ?? ''); ?>">
                    </div>
                </div>

                <!-- Password -->
                <div>
                    <label for="password" style="display:block;font-size:.75rem;font-weight:700;color:#52525b;margin-bottom:9px;letter-spacing:.07em;text-transform:uppercase;">
                        Contraseña
                    </label>
                    <div class="field-wrap">
                        <span class="field-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                        </span>
                        <input type="password" id="password" name="password" class="field-input"
                               placeholder="••••••••" required autocomplete="current-password">
                    </div>
                </div>

                <!-- Remember + forgot -->
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <label style="display:flex;align-items:center;gap:9px;cursor:pointer;user-select:none;">
                        <input type="checkbox" name="remember" class="custom-check">
                        <span style="font-size:.875rem;color:#52525b;">Recordarme</span>
                    </label>
                    <a href="#" style="font-size:.875rem;color:#c9901a;text-decoration:none;font-weight:500;transition:color .15s;"
                       onmouseover="this.style.color='#e8b84b'" onmouseout="this.style.color='#c9901a'">
                        ¿Olvidaste tu contraseña?
                    </a>
                </div>

                <!-- Submit -->
                <div style="padding-top:4px;">
                    <button type="submit" class="btn-gold">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/>
                        </svg>
                        Iniciar Sesión
                    </button>
                </div>
            </form>

            <!-- Demo credentials -->
            <div class="fade-up3" style="margin-top:28px;padding-top:24px;border-top:1px solid #1a1a1a;">
                <p style="font-size:.6875rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#2e2e2e;text-align:center;margin-bottom:14px;">Credenciales de Prueba</p>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">

                    <!-- Owner -->
                    <div style="background:#111;border:1px solid #1f1f1f;border-radius:11px;padding:13px 14px;cursor:pointer;transition:border-color .18s;"
                         onmouseover="this.style.borderColor='rgba(201,144,26,.3)'" onmouseout="this.style.borderColor='#1f1f1f'"
                         onclick="document.getElementById('email').value='demo@barberia.com';document.getElementById('password').value='password123'">
                        <div style="display:flex;align-items:center;gap:7px;margin-bottom:8px;">
                            <div style="width:7px;height:7px;background:#34d399;border-radius:50%;flex-shrink:0;"></div>
                            <p style="font-size:.6875rem;font-weight:700;color:#34d399;text-transform:uppercase;letter-spacing:.05em;">Owner</p>
                        </div>
                        <p style="font-size:.75rem;color:#52525b;font-family:monospace;line-height:1.6;">demo@barberia.com</p>
                        <p style="font-size:.75rem;color:#52525b;font-family:monospace;">password123</p>
                        <p style="font-size:.6875rem;color:#2e2e2e;margin-top:6px;">Click para rellenar</p>
                    </div>

                    <!-- Barber -->
                    <div style="background:#111;border:1px solid #1f1f1f;border-radius:11px;padding:13px 14px;cursor:pointer;transition:border-color .18s;"
                         onmouseover="this.style.borderColor='rgba(96,165,250,.3)'" onmouseout="this.style.borderColor='#1f1f1f'"
                         onclick="document.getElementById('email').value='barbero@demo.com';document.getElementById('password').value='password123'">
                        <div style="display:flex;align-items:center;gap:7px;margin-bottom:8px;">
                            <div style="width:7px;height:7px;background:#60a5fa;border-radius:50%;flex-shrink:0;"></div>
                            <p style="font-size:.6875rem;font-weight:700;color:#60a5fa;text-transform:uppercase;letter-spacing:.05em;">Barbero</p>
                        </div>
                        <p style="font-size:.75rem;color:#52525b;font-family:monospace;line-height:1.6;">barbero@demo.com</p>
                        <p style="font-size:.75rem;color:#52525b;font-family:monospace;">password123</p>
                        <p style="font-size:.6875rem;color:#2e2e2e;margin-top:6px;">Click para rellenar</p>
                    </div>

                </div>
            </div>

            <!-- Back link -->
            <p style="margin-top:24px;text-align:center;font-size:.875rem;color:#3f3f46;">
                ¿No tienes cuenta?
                <a href="<?php echo BASE_URL; ?>/auth/register" style="color:#c9901a;font-weight:600;text-decoration:none;margin-left:4px;transition:color .15s;"
                   onmouseover="this.style.color='#e8b84b'" onmouseout="this.style.color='#c9901a'">Crear cuenta</a>
            </p>

        </div>
    </div>

</div>
</body>
</html>
