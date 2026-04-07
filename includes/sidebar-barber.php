<!-- Sidebar Barber -->
<div class="fixed inset-y-0 left-0 z-50 w-64 bg-gradient-to-b from-gray-900 to-gray-800 transform transition-transform duration-300 ease-in-out lg:translate-x-0"
     :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">

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

    <?php $activeBarberPage = $activeBarberPage ?? 'index'; ?>
    <nav class="mt-8 px-4 space-y-1">
        <a href="<?php echo BASE_URL; ?>/dashboard/barber/index.php"
           class="flex items-center px-4 py-3 <?php echo $activeBarberPage === 'index' ? 'text-white bg-indigo-600' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?> rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span class="ml-3">Dashboard</span>
        </a>

        <a href="<?php echo BASE_URL; ?>/dashboard/barber/appointments.php"
           class="flex items-center px-4 py-3 <?php echo $activeBarberPage === 'appointments' ? 'text-white bg-indigo-600' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?> rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <span class="ml-3">Mis Citas</span>
        </a>

        <a href="<?php echo BASE_URL; ?>/dashboard/barber/earnings.php"
           class="flex items-center px-4 py-3 <?php echo $activeBarberPage === 'earnings' ? 'text-white bg-indigo-600' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?> rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="ml-3">Ingresos</span>
        </a>

        <a href="<?php echo BASE_URL; ?>/dashboard/barber/profile.php"
           class="flex items-center px-4 py-3 <?php echo $activeBarberPage === 'profile' ? 'text-white bg-indigo-600' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?> rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A9 9 0 1118.878 17.8M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <span class="ml-3">Mi Perfil</span>
        </a>

        <a href="<?php echo BASE_URL; ?>/dashboard/barber/schedules.php"
           class="flex items-center px-4 py-3 <?php echo $activeBarberPage === 'schedules' ? 'text-white bg-indigo-600' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?> rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <span class="ml-3">Mis Horarios</span>
        </a>

        <?php if (!empty($barber['barbershop_slug']) && !empty($barber['slug'])): ?>
        <a href="<?php echo BASE_URL; ?>/public/barber.php?shop=<?php echo urlencode($barber['barbershop_slug']); ?>&barber=<?php echo urlencode($barber['slug']); ?>"
           target="_blank"
           class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            <span class="ml-3">Mi Página Pública</span>
        </a>
        <?php endif; ?>

        <a href="<?php echo BASE_URL; ?>/auth/logout.php"
           class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
            <span class="ml-3">Cerrar Sesión</span>
        </a>
    </nav>

    <div class="absolute bottom-0 left-0 right-0 p-4 bg-gray-900 border-t border-gray-700">
        <div class="flex items-center">
            <div class="w-10 h-10 rounded-full bg-indigo-600 flex items-center justify-center text-white font-semibold">
                <?php echo strtoupper(substr($barber['full_name'] ?? ($_SESSION['user_name'] ?? 'B'), 0, 1)); ?>
            </div>
            <div class="ml-3 flex-1 min-w-0">
                <p class="text-sm font-medium text-white truncate"><?php echo e($barber['full_name'] ?? ($_SESSION['user_name'] ?? 'Barbero')); ?></p>
                <p class="text-xs text-gray-400 truncate"><?php echo e($barber['business_name'] ?? ''); ?></p>
            </div>
        </div>
    </div>
</div>
