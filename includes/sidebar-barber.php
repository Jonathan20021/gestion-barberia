<?php
$_activeBarberPage = $activeBarberPage ?? 'index';

if (!function_exists('_nav_barber')) {
    function _nav_barber($page, $active) {
        if ($page === $active) {
            return 'style="display:flex;align-items:center;padding:9px 12px;border-radius:9px;text-decoration:none;'
                 . 'background:rgba(201,144,26,.13);border-left:3px solid #c9901a;color:#e8b84b;font-weight:600;'
                 . 'gap:11px;font-size:.875rem;margin-bottom:2px;"';
        }
        return 'style="display:flex;align-items:center;padding:9px 12px;border-radius:9px;text-decoration:none;'
             . 'color:#52525b;gap:11px;font-size:.875rem;margin-bottom:2px;border-left:3px solid transparent;'
             . 'transition:background .15s,color .15s;"'
             . ' onmouseover="this.style.background=\'rgba(255,255,255,.05)\';this.style.color=\'#c4c4bf\'"'
             . ' onmouseout="this.style.background=\'transparent\';this.style.color=\'#52525b\'"';
    }
}
?>
<!-- Sidebar Barber -->
<div class="fixed inset-y-0 left-0 z-50 w-64 transform transition-transform duration-300 ease-in-out lg:translate-x-0 flex flex-col"
     :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
     style="background:#0d0d0d;border-right:1px solid #1c1c1c;">

    <!-- Logo -->
    <div style="display:flex;align-items:center;justify-content:space-between;height:64px;padding:0 18px;border-bottom:1px solid #1c1c1c;flex-shrink:0;">
        <a href="<?php echo BASE_URL; ?>/dashboard/barber" style="display:flex;align-items:center;gap:10px;text-decoration:none;">
            <div style="width:32px;height:32px;background:linear-gradient(135deg,#c9901a,#e8b84b);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 2px 10px rgba(201,144,26,.28);">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0a0a0a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                </svg>
            </div>
            <div>
                <p style="font-family:'Sora',sans-serif;font-weight:800;font-size:.875rem;color:#f0f0eb;line-height:1.1;letter-spacing:-.01em;">Kyros Barber</p>
                <p style="font-size:.625rem;font-weight:700;color:#c9901a;letter-spacing:.07em;text-transform:uppercase;">Panel Barbero</p>
            </div>
        </a>
        <button @click="sidebarOpen = false" class="lg:hidden"
                style="color:#3f3f46;background:none;border:none;cursor:pointer;padding:6px;border-radius:6px;"
                onmouseover="this.style.color='#a1a1aa'" onmouseout="this.style.color='#3f3f46'">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>
    </div>

    <!-- Nav -->
    <nav style="flex:1;padding:14px 10px;overflow-y:auto;">

        <p style="font-size:.625rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#272727;padding:0 12px;margin-bottom:8px;">Mi Panel</p>

        <a href="<?php echo BASE_URL; ?>/dashboard/barber" <?php echo _nav_barber('index', $_activeBarberPage); ?>>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
            Dashboard
        </a>

        <a href="<?php echo BASE_URL; ?>/dashboard/barber/appointments" <?php echo _nav_barber('appointments', $_activeBarberPage); ?>>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
            Mis Citas
        </a>

        <a href="<?php echo BASE_URL; ?>/dashboard/barber/earnings" <?php echo _nav_barber('earnings', $_activeBarberPage); ?>>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;">
                <line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
            </svg>
            Ingresos
        </a>

        <p style="font-size:.625rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#272727;padding:0 12px;margin:16px 0 8px;">Configuración</p>

        <a href="<?php echo BASE_URL; ?>/dashboard/barber/schedules" <?php echo _nav_barber('schedules', $_activeBarberPage); ?>>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;">
                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
            </svg>
            Mis Horarios
        </a>

        <a href="<?php echo BASE_URL; ?>/dashboard/barber/profile" <?php echo _nav_barber('profile', $_activeBarberPage); ?>>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
            </svg>
            Mi Perfil
        </a>

        <?php if (!empty($barber['barbershop_slug']) && !empty($barber['slug'])): ?>
        <div style="margin:16px 12px 8px;height:1px;background:#1c1c1c;"></div>
        <a href="<?php echo BASE_URL; ?>/public/<?php echo urlencode($barber['barbershop_slug']); ?>/<?php echo urlencode($barber['slug']); ?>"
           target="_blank"
           style="display:flex;align-items:center;padding:9px 12px;border-radius:9px;text-decoration:none;color:#52525b;gap:11px;font-size:.875rem;margin-bottom:2px;border-left:3px solid transparent;transition:background .15s,color .15s;"
           onmouseover="this.style.background='rgba(201,144,26,.07)';this.style.color='#c9901a';this.style.borderLeftColor='rgba(201,144,26,.3)'"
           onmouseout="this.style.background='transparent';this.style.color='#52525b';this.style.borderLeftColor='transparent'">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;">
                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/>
            </svg>
            Mi Página Pública
        </a>
        <?php endif; ?>

    </nav>

    <!-- User card -->
    <div style="padding:12px 14px;border-top:1px solid #1c1c1c;flex-shrink:0;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
            <div style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#c9901a,#e8b84b);display:flex;align-items:center;justify-content:center;color:#0a0a0a;font-weight:700;font-size:.8125rem;flex-shrink:0;">
                <?php echo strtoupper(substr($barber['full_name'] ?? ($_SESSION['user_name'] ?? 'B'), 0, 1)); ?>
            </div>
            <div style="min-width:0;flex:1;">
                <p style="font-size:.8125rem;font-weight:600;color:#d4d4ce;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo e($barber['full_name'] ?? ($_SESSION['user_name'] ?? 'Barbero')); ?></p>
                <p style="font-size:.6875rem;color:#3f3f46;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo e($barber['business_name'] ?? 'Barbero'); ?></p>
            </div>
        </div>
        <a href="<?php echo BASE_URL; ?>/auth/logout"
           style="display:flex;align-items:center;justify-content:center;gap:7px;padding:8px;border-radius:8px;background:#141414;border:1px solid #222;color:#52525b;text-decoration:none;font-size:.8125rem;font-weight:500;transition:all .15s;"
           onmouseover="this.style.background='#1e1e1e';this.style.borderColor='#2a2a2a';this.style.color='#a1a1aa'"
           onmouseout="this.style.background='#141414';this.style.borderColor='#222';this.style.color='#52525b'">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
            Cerrar Sesión
        </a>
    </div>
</div>

<!-- Mobile overlay -->
<div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
     style="position:fixed;inset:0;z-index:40;background:rgba(0,0,0,.65);backdrop-filter:blur(3px);"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">
</div>
