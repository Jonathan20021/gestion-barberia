<!-- Sidebar Owner -->
<div class="fixed inset-y-0 left-0 z-50 w-64 bg-gradient-to-b from-gray-900 to-gray-800 transform transition-transform duration-300 ease-in-out lg:translate-x-0"
     :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
    
    <!-- Logo -->
    <div class="flex items-center justify-between h-16 px-6 bg-gray-900 border-b border-gray-700">
        <div class="flex items-center">
            <svg class="w-8 h-8 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243 4.243 3 3 0 004.243-4.243zm0-5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z"/>
            </svg>
            <span class="ml-3 text-white font-bold text-lg">Kyros Barber Cloud</span>
        </div>
        <button @click="sidebarOpen = false" class="lg:hidden text-gray-400 hover:text-white">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <!-- Navigation -->
    <nav class="mt-8 px-4 space-y-1">
        <a href="<?php echo BASE_URL; ?>/dashboard/index.php" 
           class="flex items-center px-4 py-3 <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'text-white bg-indigo-600' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?> rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span class="ml-3">Dashboard</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>/dashboard/appointments.php" 
           class="flex items-center px-4 py-3 <?php echo basename($_SERVER['PHP_SELF']) == 'appointments.php' ? 'text-white bg-indigo-600' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?> rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <span class="ml-3">Citas</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>/dashboard/clients.php" 
           class="flex items-center px-4 py-3 <?php echo basename($_SERVER['PHP_SELF']) == 'clients.php' ? 'text-white bg-indigo-600' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?> rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <span class="ml-3">Clientes</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>/dashboard/barbers.php" 
           class="flex items-center px-4 py-3 <?php echo basename($_SERVER['PHP_SELF']) == 'barbers.php' ? 'text-white bg-indigo-600' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?> rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            <span class="ml-3">Barberos</span>
        </a>

        <a href="<?php echo BASE_URL; ?>/dashboard/barber-schedules.php"
           class="flex items-center px-4 py-3 <?php echo basename($_SERVER['PHP_SELF']) == 'barber-schedules.php' ? 'text-white bg-indigo-600' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?> rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <span class="ml-3">Horarios Barberos</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>/dashboard/services.php" 
           class="flex items-center px-4 py-3 <?php echo basename($_SERVER['PHP_SELF']) == 'services.php' ? 'text-white bg-indigo-600' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?> rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243 4.243 3 3 0 004.243-4.243zm0-5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z"/>
            </svg>
            <span class="ml-3">Servicios</span>
        </a>
    </nav>

    <!-- User Profile -->
    <div class="absolute bottom-0 left-0 right-0 p-4 bg-gray-900 border-t border-gray-700">
        <div class="flex items-center">
            <div class="w-10 h-10 rounded-full bg-indigo-600 flex items-center justify-center text-white font-semibold">
                <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'O', 0, 1)); ?>
            </div>
            <div class="ml-3 flex-1">
                <p class="text-sm font-medium text-white"><?php echo e($_SESSION['user_name'] ?? 'Owner'); ?></p>
                <p class="text-xs text-gray-400">Propietario</p>
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
