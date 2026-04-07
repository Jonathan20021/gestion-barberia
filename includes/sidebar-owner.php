<?php
$_activePage = basename($_SERVER['PHP_SELF'], '.php');
if (!function_exists('_nav_link')) {
    function _nav_link(string $page, string $active): string {
        $cls = $page === $active ? 'kyros-nav-link active' : 'kyros-nav-link';
        return "class=\"{$cls}\"";
    }
}
?>
<!-- Sidebar Owner -->
<div class="fixed inset-y-0 left-0 z-50 w-64 transform transition-transform duration-300 ease-in-out lg:translate-x-0 flex flex-col"
     :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
     style="background:var(--c-sidebar);border-right:1px solid var(--c-sidebar-bd);">

    <!-- Logo -->
    <div style="display:flex;align-items:center;justify-content:space-between;height:64px;padding:0 18px;border-bottom:1px solid var(--c-sidebar-bd);flex-shrink:0;">
        <a href="<?php echo BASE_URL; ?>/dashboard" style="display:flex;align-items:center;gap:10px;text-decoration:none;">
            <div style="width:32px;height:32px;background:linear-gradient(135deg,#c9901a,#e8b84b);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 2px 10px rgba(201,144,26,.28);">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0a0a0a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                </svg>
            </div>
            <div>
                <p style="font-family:'Sora',sans-serif;font-weight:800;font-size:.875rem;color:var(--c-text-1);line-height:1.1;letter-spacing:-.01em;">Kyros Barber</p>
                <p style="font-size:.625rem;font-weight:700;color:var(--c-gold);letter-spacing:.07em;text-transform:uppercase;">Propietario</p>
            </div>
        </a>
        <button @click="sidebarOpen = false" class="lg:hidden"
                style="color:var(--c-text-4);background:none;border:none;cursor:pointer;padding:6px;border-radius:6px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>
    </div>

    <!-- Nav -->
    <nav style="flex:1;padding:14px 10px;overflow-y:auto;">

        <p style="font-size:.625rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--c-border-2);padding:0 12px;margin-bottom:8px;">Principal</p>

        <a href="<?php echo BASE_URL; ?>/dashboard" <?php echo _nav_link('index', $_activePage); ?>>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
            Dashboard
        </a>

        <a href="<?php echo BASE_URL; ?>/dashboard/appointments" <?php echo _nav_link('appointments', $_activePage); ?>>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
            Citas
        </a>

        <a href="<?php echo BASE_URL; ?>/dashboard/clients" <?php echo _nav_link('clients', $_activePage); ?>>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
            Clientes
        </a>

        <p style="font-size:.625rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--c-border-2);padding:0 12px;margin:16px 0 8px;">Gestión</p>

        <a href="<?php echo BASE_URL; ?>/dashboard/barbers" <?php echo _nav_link('barbers', $_activePage); ?>>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
            </svg>
            Barberos
        </a>

        <a href="<?php echo BASE_URL; ?>/dashboard/barber-schedules" <?php echo _nav_link('barber-schedules', $_activePage); ?>>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
            </svg>
            Horarios
        </a>

        <a href="<?php echo BASE_URL; ?>/dashboard/services" <?php echo _nav_link('services', $_activePage); ?>>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>
            </svg>
            Servicios
        </a>

        <p style="font-size:.625rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--c-border-2);padding:0 12px;margin:16px 0 8px;">Publicación</p>

        <a href="<?php echo BASE_URL; ?>/dashboard/public-links" <?php echo _nav_link('public-links', $_activePage); ?>>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/>
            </svg>
            Enlaces Públicos
        </a>

    </nav>

    <!-- Bottom: theme toggle + user card -->
    <div style="padding:12px 14px;border-top:1px solid var(--c-sidebar-bd);flex-shrink:0;display:flex;flex-direction:column;gap:10px;">

        <!-- Theme toggle -->
        <button onclick="toggleTheme()" class="kyros-theme-btn">
            <!-- Sun icon — shown in dark mode (to switch to light) -->
            <span class="icon-to-light" style="align-items:center;gap:8px;flex:1;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                </svg>
                <span class="label-to-light">Modo claro</span>
            </span>
            <!-- Moon icon — shown in light mode (to switch to dark) -->
            <span class="icon-to-dark" style="align-items:center;gap:8px;flex:1;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                </svg>
                <span class="label-to-dark">Modo oscuro</span>
            </span>
        </button>

        <!-- User info -->
        <div style="display:flex;align-items:center;gap:10px;">
            <div style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#c9901a,#e8b84b);display:flex;align-items:center;justify-content:center;color:#0a0a0a;font-weight:700;font-size:.8125rem;flex-shrink:0;">
                <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'O', 0, 1)); ?>
            </div>
            <div style="min-width:0;flex:1;">
                <p style="font-size:.8125rem;font-weight:600;color:var(--c-text-1);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo e($_SESSION['user_name'] ?? 'Owner'); ?></p>
                <p style="font-size:.6875rem;color:var(--c-text-4);">Propietario</p>
            </div>
            <a href="<?php echo BASE_URL; ?>/auth/logout" title="Cerrar sesión"
               style="color:var(--c-text-4);text-decoration:none;padding:5px;border-radius:6px;display:flex;transition:color .15s;"
               onmouseover="this.style.color='var(--c-red-text)'" onmouseout="this.style.color='var(--c-text-4)'">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
            </a>
        </div>
    </div>
</div>

<!-- Mobile overlay -->
<div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
     style="position:fixed;inset:0;z-index:40;background:rgba(0,0,0,.6);backdrop-filter:blur(3px);"
     x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
</div>
