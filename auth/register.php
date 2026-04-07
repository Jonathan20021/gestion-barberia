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

$db = Database::getInstance();
$error = '';
$trialDays = defined('TRIAL_DAYS_DEFAULT') ? max(1, (int) TRIAL_DAYS_DEFAULT) : 15;
$defaultPlan = array_key_exists('professional', LICENSE_TYPES) ? 'professional' : array_key_first(LICENSE_TYPES);

$supportsTrialDates = true;
try {
    $db->query("SELECT trial_end_date, trial_start_date, trial_days FROM licenses LIMIT 1");
} catch (Exception $e) {
    $supportsTrialDates = false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');
    $businessName = trim($_POST['business_name'] ?? '');
    $slugInput = trim($_POST['slug'] ?? '');
    $businessPhone = trim($_POST['business_phone'] ?? '');
    $businessEmail = trim($_POST['business_email'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $planType = trim($_POST['plan_type'] ?? $defaultPlan);

    try {
        if ($fullName === '' || $email === '' || $password === '' || $passwordConfirm === '' || $businessName === '') {
            throw new Exception('Completa los campos obligatorios para crear tu cuenta.');
        }
        if (!isValidEmail($email)) {
            throw new Exception('Ingresa un correo válido para el propietario.');
        }
        if ($businessEmail !== '' && !isValidEmail($businessEmail)) {
            throw new Exception('Ingresa un correo válido para la barbería.');
        }
        if (strlen($password) < 8) {
            throw new Exception('La contraseña debe tener al menos 8 caracteres.');
        }
        if ($password !== $passwordConfirm) {
            throw new Exception('Las contraseñas no coinciden.');
        }
        if (!isset(LICENSE_TYPES[$planType])) {
            throw new Exception('Selecciona un plan válido.');
        }
        if ($db->fetch("SELECT id FROM users WHERE email = ?", [$email])) {
            throw new Exception('Ya existe una cuenta registrada con este correo.');
        }

        $baseSlug = $slugInput !== '' ? generateSlug($slugInput) : generateSlug($businessName);
        if ($baseSlug === '') {
            $baseSlug = 'barberia';
        }

        $slug = $baseSlug;
        $suffix = 2;
        while ($db->fetch("SELECT id FROM barbershops WHERE slug = ?", [$slug])) {
            $slug = $baseSlug . '-' . $suffix;
            $suffix++;
        }

        $billingCycle = 'monthly';
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d', strtotime($startDate . ' +1 month'));
        $trialEndDate = date('Y-m-d', strtotime($startDate . ' +' . $trialDays . ' days'));
        $licenseKey = bin2hex(random_bytes(16));
        $licensePrice = LICENSE_TYPES[$planType]['price'];

        $db->beginTransaction();

        $db->query(
            "INSERT INTO users (email, password, full_name, phone, role, status, created_at)
             VALUES (?, ?, ?, ?, 'owner', 'active', NOW())",
            [$email, password_hash($password, PASSWORD_DEFAULT), $fullName, $phone !== '' ? $phone : null]
        );
        $ownerId = (int) $db->lastInsertId();

        if ($supportsTrialDates) {
            $db->query(
                "INSERT INTO licenses (license_key, type, status, price, billing_cycle, start_date, end_date, trial_days, trial_start_date, trial_end_date, created_at)
                 VALUES (?, ?, 'trial', ?, ?, ?, ?, ?, ?, ?, NOW())",
                [$licenseKey, $planType, $licensePrice, $billingCycle, $startDate, $endDate, $trialDays, $startDate, $trialEndDate]
            );
        } else {
            $db->query(
                "INSERT INTO licenses (license_key, type, status, price, billing_cycle, start_date, end_date, created_at)
                 VALUES (?, ?, 'active', ?, ?, ?, ?, NOW())",
                [$licenseKey, $planType, $licensePrice, $billingCycle, $startDate, $endDate]
            );
        }
        $licenseId = (int) $db->lastInsertId();

        $db->query(
            "INSERT INTO barbershops (
                license_id, owner_id, business_name, slug, phone, email, address, city,
                country, allow_online_booking, advance_booking_days, cancellation_hours,
                status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'República Dominicana', 1, 30, 24, 'active', NOW())",
            [
                $licenseId,
                $ownerId,
                $businessName,
                $slug,
                $businessPhone !== '' ? $businessPhone : null,
                $businessEmail !== '' ? $businessEmail : $email,
                $address !== '' ? $address : null,
                $city !== '' ? $city : null,
            ]
        );

        $db->commit();

        $auth = new Auth();
        if (!$auth->login($email, $password)) {
            throw new Exception('La cuenta fue creada, pero no se pudo iniciar sesión automáticamente.');
        }

        $_SESSION['success'] = 'Tu cuenta fue creada con una licencia de prueba de ' . $trialDays . ' días.';
        redirect(BASE_URL . '/dashboard');
    } catch (Exception $e) {
        if ($db->getConnection()->inTransaction()) {
            $db->rollBack();
        }
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Cuenta - Kyros Barber Cloud</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sora:wght@700;800;900&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
        :root{
            --bg:#090909;
            --bg-soft:#111111;
            --bg-panel:#151515;
            --border:#242424;
            --border-strong:#303030;
            --text:#f5f1e8;
            --muted:#9f9686;
            --muted-2:#7d7567;
            --gold-1:#c9901a;
            --gold-2:#e8b84b;
            --gold-3:#f6d982;
            --shadow:0 32px 90px rgba(0,0,0,.38);
        }
        body{
            font-family:'Inter',sans-serif;
            background:
                radial-gradient(circle at top left, rgba(232,184,75,.08), transparent 26%),
                radial-gradient(circle at 85% 15%, rgba(232,184,75,.05), transparent 22%),
                linear-gradient(180deg, #0a0a0a 0%, #080808 100%);
            color:var(--text);
            min-height:100vh;
        }
        h1,h2,h3 { font-family:'Sora',sans-serif; }
        .page { min-height:100vh; display:grid; grid-template-columns:1fr; }
        .left {
            display:none;
            padding:48px 52px;
            background:
                linear-gradient(180deg, rgba(255,255,255,.02), rgba(255,255,255,0)),
                radial-gradient(circle at 15% 20%, rgba(232,184,75,.10), transparent 24%),
                linear-gradient(180deg, #0d0d0d 0%, #0a0a0a 100%);
            border-right:1px solid rgba(255,255,255,.06);
            position:relative;
            overflow:hidden;
        }
        .left::before{
            content:'';
            position:absolute;
            inset:0;
            background-image:linear-gradient(rgba(255,255,255,.025) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.025) 1px, transparent 1px);
            background-size:28px 28px;
            opacity:.18;
            pointer-events:none;
        }
        .right {
            display:flex;
            align-items:center;
            justify-content:center;
            padding:42px 22px;
            position:relative;
        }
        .right::before{
            content:'';
            position:absolute;
            width:720px;
            height:720px;
            border-radius:50%;
            background:radial-gradient(circle, rgba(232,184,75,.06) 0%, transparent 58%);
            top:50%;
            left:50%;
            transform:translate(-50%,-50%);
            pointer-events:none;
        }
        .card {
            width:100%;
            max-width:660px;
            background:linear-gradient(180deg, rgba(255,255,255,.03), rgba(255,255,255,.015));
            border:1px solid rgba(255,255,255,.07);
            border-radius:30px;
            padding:34px;
            box-shadow:var(--shadow);
            backdrop-filter:blur(10px);
            position:relative;
            z-index:1;
        }
        .card::before{
            content:'';
            position:absolute;
            inset:0;
            border-radius:30px;
            padding:1px;
            background:linear-gradient(180deg, rgba(255,255,255,.10), rgba(255,255,255,0));
            -webkit-mask:linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite:xor;
            mask-composite:exclude;
            pointer-events:none;
        }
        .header-row{
            display:flex;
            align-items:flex-start;
            justify-content:space-between;
            gap:20px;
            margin-bottom:26px;
        }
        .headline{ max-width:430px; }
        .grid { display:grid; grid-template-columns:1fr; gap:18px; }
        .grid-2 { display:grid; grid-template-columns:1fr; gap:16px; }
        .field{
            background:rgba(0,0,0,.14);
            border:1px solid rgba(255,255,255,.04);
            border-radius:18px;
            padding:14px;
        }
        .label {
            display:block;
            font-size:.72rem;
            font-weight:800;
            color:var(--muted);
            margin-bottom:9px;
            letter-spacing:.08em;
            text-transform:uppercase;
        }
        .input, .select, .textarea {
            width:100%;
            background:#0b0b0b;
            color:var(--text);
            border:1.5px solid rgba(255,255,255,.08);
            border-radius:14px;
            padding:15px 16px;
            outline:none;
            font-size:.97rem;
            transition:border-color .18s, box-shadow .18s, transform .18s, background .18s;
        }
        .input::placeholder, .textarea::placeholder { color:#6f675a; }
        .textarea { min-height:110px; resize:vertical; }
        .input:focus, .select:focus, .textarea:focus {
            border-color:rgba(232,184,75,.9);
            box-shadow:0 0 0 4px rgba(201,144,26,.10);
            background:#101010;
        }
        .muted { color:var(--muted); line-height:1.72; font-size:1rem; }
        .subtle { color:var(--muted-2); line-height:1.7; font-size:.9rem; }
        .btn-gold {
            display:inline-flex;
            align-items:center;
            justify-content:center;
            gap:8px;
            width:100%;
            background:linear-gradient(135deg,var(--gold-1) 0%, var(--gold-2) 48%, var(--gold-3) 100%);
            color:#0a0a0a;
            font-weight:900;
            border:none;
            border-radius:16px;
            padding:17px 24px;
            cursor:pointer;
            box-shadow:0 12px 28px rgba(201,144,26,.24), inset 0 1px 0 rgba(255,255,255,.28);
            text-decoration:none;
            transition:transform .18s, box-shadow .18s;
        }
        .btn-gold:hover{
            transform:translateY(-1px);
            box-shadow:0 18px 34px rgba(201,144,26,.28), inset 0 1px 0 rgba(255,255,255,.35);
        }
        .error {
            background:rgba(239,68,68,.10);
            border:1px solid rgba(239,68,68,.24);
            color:#fca5a5;
            border-radius:16px;
            padding:15px 16px;
            margin-bottom:18px;
        }
        .pill {
            display:inline-flex;
            align-items:center;
            gap:9px;
            padding:8px 15px;
            border-radius:999px;
            background:rgba(201,144,26,.11);
            border:1px solid rgba(232,184,75,.22);
            color:var(--gold-2);
            font-size:.78rem;
            font-weight:800;
            box-shadow:inset 0 1px 0 rgba(255,255,255,.05);
        }
        .pill::before{
            content:'';
            width:8px;
            height:8px;
            border-radius:50%;
            background:linear-gradient(135deg,var(--gold-2),var(--gold-3));
            box-shadow:0 0 0 5px rgba(201,144,26,.12);
        }
        .note {
            background:linear-gradient(180deg, rgba(255,255,255,.03), rgba(255,255,255,.015));
            border:1px solid rgba(255,255,255,.07);
            border-radius:18px;
            padding:18px;
            color:#c3b8a6;
            font-size:.92rem;
            line-height:1.72;
        }
        .plan-grid {
            display:grid;
            grid-template-columns:1fr;
            gap:12px;
            margin-top:28px;
        }
        .plan-card {
            background:linear-gradient(180deg, rgba(255,255,255,.03), rgba(255,255,255,.01));
            border:1px solid rgba(255,255,255,.07);
            border-radius:18px;
            padding:16px 18px;
            transition:transform .18s, border-color .18s, background .18s;
        }
        .plan-card:hover{
            transform:translateY(-2px);
            border-color:rgba(232,184,75,.18);
            background:linear-gradient(180deg, rgba(232,184,75,.05), rgba(255,255,255,.02));
        }
        .logo-mark{
            width:48px;
            height:48px;
            border-radius:14px;
            background:linear-gradient(135deg,var(--gold-1),var(--gold-2));
            display:flex;
            align-items:center;
            justify-content:center;
            color:#0a0a0a;
            font-weight:900;
            box-shadow:0 10px 24px rgba(201,144,26,.22);
        }
        .stats-row{
            display:grid;
            grid-template-columns:repeat(2, minmax(0,1fr));
            gap:12px;
            margin-top:26px;
        }
        .stat-box{
            border:1px solid rgba(255,255,255,.07);
            background:rgba(255,255,255,.02);
            border-radius:18px;
            padding:16px;
        }
        .link { color:var(--gold-2); text-decoration:none; font-weight:700; }
        .helper-badge{
            border:1px solid rgba(232,184,75,.14);
            background:rgba(232,184,75,.06);
            color:var(--gold-2);
            font-size:.8rem;
            font-weight:800;
            border-radius:999px;
            padding:8px 12px;
            white-space:nowrap;
        }
        @media(min-width:1024px){
            .page { grid-template-columns:460px 1fr; }
            .left { display:flex; flex-direction:column; justify-content:space-between; }
            .grid-2 { grid-template-columns:1fr 1fr; }
        }
        @media(min-width:1280px){
            .card { max-width:700px; padding:38px; }
        }
        @media(max-width:719px){
            .card { padding:24px 18px; border-radius:24px; }
            .header-row{ flex-direction:column; gap:12px; }
            .right { padding:22px 14px; }
            .field { padding:12px; }
        }
    </style>
</head>
<body>
<div class="page">
    <aside class="left">
        <div>
            <a href="<?php echo BASE_URL; ?>/" style="display:inline-flex;align-items:center;gap:12px;text-decoration:none;color:#fff;position:relative;z-index:1;">
                <div class="logo-mark">K</div>
                <span style="font-size:1.125rem;font-weight:800;">Kyros Barber Cloud</span>
            </a>
            <div style="margin-top:44px;position:relative;z-index:1;">
                <span class="pill">Prueba gratis por <?php echo $trialDays; ?> días</span>
                <h1 style="font-size:3rem;line-height:1.03;letter-spacing:-.04em;margin:22px 0 18px;max-width:320px;">Abre tu cuenta y publica tu barbería hoy.</h1>
                <p class="muted" style="max-width:340px;">El registro crea el propietario, la barbería y una licencia trial automáticamente. Luego entras directo a tu panel.</p>
                <div class="stats-row">
                    <div class="stat-box">
                        <div style="font-family:'Sora',sans-serif;font-size:1.5rem;font-weight:900;color:#fff;">15 días</div>
                        <div class="subtle">de prueba lista para usar</div>
                    </div>
                    <div class="stat-box">
                        <div style="font-family:'Sora',sans-serif;font-size:1.5rem;font-weight:900;color:#fff;">3 min</div>
                        <div class="subtle">para abrir tu cuenta</div>
                    </div>
                </div>
            </div>
            <div class="plan-grid">
                <?php foreach (LICENSE_TYPES as $planKey => $plan): ?>
                <div class="plan-card">
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:14px;">
                        <div>
                            <strong style="display:block;color:#f5f5f0;font-size:1rem;"><?php echo e($plan['name']); ?></strong>
                            <div class="subtle" style="margin-top:5px;">
                                <?php echo ($plan['max_barbers'] < 0 ? 'Barberos ilimitados' : $plan['max_barbers'] . ' barberos'); ?>
                                ·
                                <?php echo ($plan['max_locations'] < 0 ? 'multi sucursal' : $plan['max_locations'] . ' sucursal'); ?>
                            </div>
                        </div>
                        <span style="color:#e8b84b;font-weight:800;white-space:nowrap;"><?php echo formatPrice($plan['price']); ?>/mes</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="note" style="position:relative;z-index:1;">No se requiere tarjeta para iniciar. Al vencer la prueba, el acceso del owner queda sujeto al estado de la licencia.</div>
    </aside>
    <main class="right">
        <div class="card">
            <div class="header-row">
                <div class="headline">
                    <h2 style="font-size:3rem;line-height:1.02;letter-spacing:-.04em;margin-bottom:10px;">Crear cuenta</h2>
                    <p class="muted">Registra tu barbería y recibe tu licencia de prueba de <?php echo $trialDays; ?> días.</p>
                </div>
                <div class="helper-badge">Alta automática</div>
            </div>
            <?php if ($error): ?><div class="error"><?php echo e($error); ?></div><?php endif; ?>
            <form method="POST" class="grid">
                <div class="grid-2">
                    <div class="field"><label class="label" for="full_name">Nombre del propietario</label><input class="input" id="full_name" name="full_name" required value="<?php echo e($_POST['full_name'] ?? ''); ?>"></div>
                    <div class="field"><label class="label" for="phone">Teléfono del propietario</label><input class="input" id="phone" name="phone" value="<?php echo e($_POST['phone'] ?? ''); ?>"></div>
                </div>
                <div class="grid-2">
                    <div class="field"><label class="label" for="email">Correo del propietario</label><input class="input" type="email" id="email" name="email" required value="<?php echo e($_POST['email'] ?? ''); ?>"></div>
                    <div class="field"><label class="label" for="business_email">Correo de la barbería</label><input class="input" type="email" id="business_email" name="business_email" value="<?php echo e($_POST['business_email'] ?? ''); ?>"></div>
                </div>
                <div class="grid-2">
                    <div class="field"><label class="label" for="password">Contraseña</label><input class="input" type="password" id="password" name="password" required autocomplete="new-password"></div>
                    <div class="field"><label class="label" for="password_confirm">Confirmar contraseña</label><input class="input" type="password" id="password_confirm" name="password_confirm" required autocomplete="new-password"></div>
                </div>
                <div class="grid-2">
                    <div class="field"><label class="label" for="business_name">Nombre de la barbería</label><input class="input" id="business_name" name="business_name" required value="<?php echo e($_POST['business_name'] ?? ''); ?>"></div>
                    <div class="field"><label class="label" for="slug">Slug URL</label><input class="input" id="slug" name="slug" placeholder="opcional" value="<?php echo e($_POST['slug'] ?? ''); ?>"></div>
                </div>
                <div class="grid-2">
                    <div class="field"><label class="label" for="business_phone">Teléfono de la barbería</label><input class="input" id="business_phone" name="business_phone" value="<?php echo e($_POST['business_phone'] ?? ''); ?>"></div>
                    <div class="field"><label class="label" for="city">Ciudad</label><input class="input" id="city" name="city" value="<?php echo e($_POST['city'] ?? ''); ?>"></div>
                </div>
                <div class="field"><label class="label" for="address">Dirección</label><textarea class="textarea" id="address" name="address"><?php echo e($_POST['address'] ?? ''); ?></textarea></div>
                <div class="field">
                    <label class="label" for="plan_type">Plan para iniciar la prueba</label>
                    <select class="select" id="plan_type" name="plan_type" required>
                        <?php foreach (LICENSE_TYPES as $planKey => $plan): ?>
                        <option value="<?php echo e($planKey); ?>" <?php echo ($planKey === ($_POST['plan_type'] ?? $defaultPlan)) ? 'selected' : ''; ?>><?php echo e($plan['name']); ?> - <?php echo formatPrice($plan['price']); ?>/mes</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="note">Al continuar se crea una licencia en modo prueba por <?php echo $trialDays; ?> días con el plan seleccionado. El sistema genera tu barbería en estado activo y habilita reservas online por defecto.</div>
                <button type="submit" class="btn-gold">Crear cuenta y empezar prueba</button>
            </form>
            <p style="margin-top:20px;text-align:center;color:#71717a;font-size:.875rem;">¿Ya tienes cuenta? <a class="link" href="<?php echo BASE_URL; ?>/auth/login">Inicia sesión</a></p>
        </div>
    </main>
</div>
</body>
</html>
