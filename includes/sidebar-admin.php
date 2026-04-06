<!-- Sidebar Super Admin -->
<div class="fixed inset-y-0 left-0 z-50 w-64 bg-gradient-to-b from-gray-900 to-gray-800 transform transition-transform duration-300 ease-in-out lg:translate-x-0"
     :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
    
    <!-- Logo -->
    <div class="flex items-center justify-between h-16 px-6 bg-gray-900 border-b border-gray-700">
        <div class="flex items-center">
            <svg class="w-8 h-8 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243 4.243 3 3 0 004.243-4.243zm0-5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z"/>
            </svg>
            <span class="ml-3 text-white font-bold text-lg">BarberSaaS</span>
        </div>
        <button @click="sidebarOpen = false" class="lg:hidden text-gray-400 hover:text-white">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <!-- Navigation -->
    <nav class="mt-8 px-4 space-y-1">
        <a href="<?php echo BASE_URL; ?>/admin/dashboard.php" 
           class="flex items-center px-4 py-3 <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'text-white bg-indigo-600' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?> rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span class="ml-3">Dashboard</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>/admin/barbershops.php" 
           class="flex items-center px-4 py-3 <?php echo basename($_SERVER['PHP_SELF']) == 'barbershops.php' ? 'text-white bg-indigo-600' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?> rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
            <span class="ml-3">Barberías</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>/admin/users.php" 
           class="flex items-center px-4 py-3 <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'text-white bg-indigo-600' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?> rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            <span class="ml-3">Usuarios</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>/admin/licenses.php" 
           class="flex items-center px-4 py-3 <?php echo basename($_SERVER['PHP_SELF']) == 'licenses.php' ? 'text-white bg-indigo-600' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?> rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
            </svg>
            <span class="ml-3">Licencias</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>/admin/coupons.php" 
           class="flex items-center px-4 py-3 <?php echo basename($_SERVER['PHP_SELF']) == 'coupons.php' ? 'text-white bg-indigo-600' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?> rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
            </svg>
            <span class="ml-3">Cupones</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>/admin/finances.php" 
           class="flex items-center px-4 py-3 <?php echo basename($_SERVER['PHP_SELF']) == 'finances.php' ? 'text-white bg-indigo-600' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?> rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="ml-3">Finanzas</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>/admin/reports.php" 
           class="flex items-center px-4 py-3 <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'text-white bg-indigo-600' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?> rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            <span class="ml-3">Reportes</span>
        </a>

    </nav>

    <!-- User Profile -->
    <div class="absolute bottom-0 left-0 right-0 p-4 bg-gray-900 border-t border-gray-700">
        <div class="flex items-center">
            <div class="w-10 h-10 rounded-full bg-indigo-600 flex items-center justify-center text-white font-semibold">
                SA
            </div>
            <div class="ml-3 flex-1">
                <p class="text-sm font-medium text-white">Super Admin</p>
                <p class="text-xs text-gray-400"><?php echo e($_SESSION['user_email'] ?? ''); ?></p>
            </div>
        </div>
        <a href="<?php echo BASE_URL; ?>/auth/logout.php" 
           class="mt-3 flex items-center justify-center px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition text-sm">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
            Cerrar Sesión
        </a>
    </div>
</div>
