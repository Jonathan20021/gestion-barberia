<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BarberSaaS - Sistema de Gestión para Barberías RD</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .fade-in-up {
            animation: fadeInUp 0.6s ease-out forwards;
        }
        .gradient-text {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navbar -->
    <nav class="bg-white shadow-sm sticky top-0 z-50" x-data="{ mobileMenuOpen: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <span class="text-2xl font-bold gradient-text">💈 BarberSaaS</span>
                    <span class="ml-2 px-2 py-1 text-xs bg-indigo-100 text-indigo-700 rounded-full font-medium">RD</span>
                </div>
                
                <div class="hidden md:flex items-center space-x-8">
                    <a href="#features" class="text-gray-700 hover:text-indigo-600 transition">Características</a>
                    <a href="#pricing" class="text-gray-700 hover:text-indigo-600 transition">Precios</a>
                    <a href="#demo" class="text-gray-700 hover:text-indigo-600 transition">Demo</a>
                    <a href="auth/login.php" class="px-4 py-2 text-indigo-600 hover:text-indigo-700 transition">Iniciar Sesión</a>
                    <a href="auth/login.php" class="px-6 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg hover:from-indigo-700 hover:to-purple-700 transition shadow-lg">
                        Comenzar Gratis
                    </a>
                </div>

                <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden p-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div x-show="mobileMenuOpen" class="md:hidden bg-white border-t" style="display: none;">
            <div class="px-4 py-3 space-y-3">
                <a href="#features" class="block text-gray-700 hover:text-indigo-600">Características</a>
                <a href="#pricing" class="block text-gray-700 hover:text-indigo-600">Precios</a>
                <a href="#demo" class="block text-gray-700 hover:text-indigo-600">Demo</a>
                <a href="auth/login.php" class="block w-full px-4 py-2 text-center bg-indigo-600 text-white rounded-lg">Comenzar Gratis</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative bg-gradient-to-br from-indigo-50 via-purple-50 to-pink-50 py-20 overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 left-0 w-96 h-96 bg-purple-500 rounded-full filter blur-3xl"></div>
            <div class="absolute bottom-0 right-0 w-96 h-96 bg-indigo-500 rounded-full filter blur-3xl"></div>
        </div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-2 gap-12 items-center">
                <div class="fade-in-up">
                    <h1 class="text-5xl md:text-6xl font-bold text-gray-900 mb-6 leading-tight">
                        Gestiona tu Barbería
                        <span class="gradient-text">Como un Profesional</span>
                    </h1>
                    <p class="text-xl text-gray-600 mb-8">
                        Sistema completo de gestión de citas, clientes y finanzas para barberías en República Dominicana. 
                        100% en la nube, fácil de usar y con reservas online.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4">
                        <a href="auth/login.php" class="px-8 py-4 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-semibold text-lg hover:from-indigo-700 hover:to-purple-700 transition shadow-xl text-center">
                            🚀 Probar Gratis 30 Días
                        </a>
                        <a href="#demo" class="px-8 py-4 bg-white text-indigo-600 rounded-xl font-semibold text-lg hover:bg-gray-50 transition border-2 border-indigo-600 text-center">
                            📺 Ver Demo
                        </a>
                    </div>
                    <div class="mt-8 flex items-center space-x-6 text-sm text-gray-600">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Sin contratos
                        </div>
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Cancela cuando quieras
                        </div>
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Soporte en español
                        </div>
                    </div>
                </div>

                <div class="relative fade-in-up" style="animation-delay: 0.2s;">
                    <div class="bg-white rounded-2xl shadow-2xl p-8 transform hover:scale-105 transition duration-300">
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center space-x-3">
                                <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                                <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                                <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                            </div>
                            <span class="text-sm text-gray-500">Vista Previa</span>
                        </div>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between p-4 bg-gradient-to-r from-indigo-50 to-purple-50 rounded-lg">
                                <div>
                                    <p class="text-sm text-gray-600">Citas Hoy</p>
                                    <p class="text-3xl font-bold text-indigo-600">24</p>
                                </div>
                                <div class="w-16 h-16 bg-indigo-600 rounded-full flex items-center justify-center text-white text-2xl">
                                    📅
                                </div>
                            </div>
                            <div class="flex items-center justify-between p-4 bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg">
                                <div>
                                    <p class="text-sm text-gray-600">Ingresos Hoy</p>
                                    <p class="text-3xl font-bold text-green-600">RD$8,450</p>
                                </div>
                                <div class="w-16 h-16 bg-green-600 rounded-full flex items-center justify-center text-white text-2xl">
                                    💰
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="p-4 bg-blue-50 rounded-lg text-center">
                                    <p class="text-2xl font-bold text-blue-600">156</p>
                                    <p class="text-sm text-gray-600">Clientes</p>
                                </div>
                                <div class="p-4 bg-purple-50 rounded-lg text-center">
                                    <p class="text-2xl font-bold text-purple-600">4.9⭐</p>
                                    <p class="text-sm text-gray-600">Rating</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-12 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                <div>
                    <p class="text-4xl font-bold text-indigo-600">500+</p>
                    <p class="text-gray-600 mt-2">Barberías Activas</p>
                </div>
                <div>
                    <p class="text-4xl font-bold text-purple-600">50K+</p>
                    <p class="text-gray-600 mt-2">Citas Mensuales</p>
                </div>
                <div>
                    <p class="text-4xl font-bold text-pink-600">99.9%</p>
                    <p class="text-gray-600 mt-2">Uptime</p>
                </div>
                <div>
                    <p class="text-4xl font-bold text-green-600">4.8⭐</p>
                    <p class="text-gray-600 mt-2">Satisfacción</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Todo lo que Necesitas</h2>
                <p class="text-xl text-gray-600">Herramientas profesionales para gestionar tu barbería</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <div class="bg-white rounded-xl shadow-lg p-8 hover:shadow-xl transition">
                    <div class="w-14 h-14 bg-gradient-to-br from-indigo-500 to-purple-500 rounded-xl flex items-center justify-center text-white text-2xl mb-6">
                        📱
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Reservas Online 24/7</h3>
                    <p class="text-gray-600">
                        Tus clientes pueden agendar citas desde cualquier lugar, cualquier hora. 
                        Página personalizada para tu barbería.
                    </p>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-8 hover:shadow-xl transition">
                    <div class="w-14 h-14 bg-gradient-to-br from-green-500 to-emerald-500 rounded-xl flex items-center justify-center text-white text-2xl mb-6">
                        📊
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Dashboard Completo</h3>
                    <p class="text-gray-600">
                        Visualiza tus estadísticas, ingresos y citas en tiempo real. 
                        Reportes detallados para tomar mejores decisiones.
                    </p>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-8 hover:shadow-xl transition">
                    <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-xl flex items-center justify-center text-white text-2xl mb-6">
                        👥
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Gestión de Clientes</h3>
                    <p class="text-gray-600">
                        Base de datos completa de clientes con historial de servicios, 
                        preferencias y recordatorios automáticos.
                    </p>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-8 hover:shadow-xl transition">
                    <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl flex items-center justify-center text-white text-2xl mb-6">
                        ✂️
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Gestión de Barberos</h3>
                    <p class="text-gray-600">
                        Administra horarios, comisiones y rendimiento de tu equipo. 
                        Cada barbero con su propio portal.
                    </p>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-8 hover:shadow-xl transition">
                    <div class="w-14 h-14 bg-gradient-to-br from-yellow-500 to-orange-500 rounded-xl flex items-center justify-center text-white text-2xl mb-6">
                        💳
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Control Financiero</h3>
                    <p class="text-gray-600">
                        Registra ingresos, gastos y comisiones. Reportes fiscales 
                        y análisis de rentabilidad por servicio.
                    </p>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-8 hover:shadow-xl transition">
                    <div class="w-14 h-14 bg-gradient-to-br from-red-500 to-rose-500 rounded-xl flex items-center justify-center text-white text-2xl mb-6">
                        🔔
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Notificaciones SMS</h3>
                    <p class="text-gray-600">
                        Recordatorios automáticos a clientes vía WhatsApp y SMS. 
                        Reduce las ausencias hasta en 80%.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Planes Transparentes</h2>
                <p class="text-xl text-gray-600">Sin costos ocultos. Cancela cuando quieras.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                <!-- Plan Básico -->
                <div class="bg-white border-2 border-gray-200 rounded-2xl p-8 hover:border-indigo-600 transition">
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">Básico</h3>
                    <p class="text-gray-600 mb-6">Ideal para empezar</p>
                    <div class="mb-6">
                        <span class="text-5xl font-bold text-gray-900">RD$1,500</span>
                        <span class="text-gray-600">/mes</span>
                    </div>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-700">Hasta 100 citas/mes</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-700">2 barberos</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-700">Página de reservas</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-700">Soporte por email</span>
                        </li>
                    </ul>
                    <a href="auth/login.php" class="block w-full px-6 py-3 bg-gray-900 text-white rounded-lg text-center font-semibold hover:bg-gray-800 transition">
                        Comenzar
                    </a>
                </div>

                <!-- Plan Profesional (Popular) -->
                <div class="bg-gradient-to-br from-indigo-600 to-purple-600 rounded-2xl p-8 transform scale-105 shadow-2xl">
                    <div class="absolute top-0 right-6 -mt-4">
                        <span class="bg-yellow-400 text-gray-900 px-4 py-1 rounded-full text-sm font-bold">Popular</span>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-2">Profesional</h3>
                    <p class="text-indigo-100 mb-6">Más vendido</p>
                    <div class="mb-6">
                        <span class="text-5xl font-bold text-white">RD$3,000</span>
                        <span class="text-indigo-100">/mes</span>
                    </div>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-yellow-400 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-white font-medium">Citas ilimitadas</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-yellow-400 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-white font-medium">Hasta 5 barberos</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-yellow-400 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-white font-medium">Notificaciones SMS</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-yellow-400 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-white font-medium">Reportes avanzados</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-yellow-400 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-white font-medium">Soporte prioritario</span>
                        </li>
                    </ul>
                    <a href="auth/login.php" class="block w-full px-6 py-3 bg-white text-indigo-600 rounded-lg text-center font-semibold hover:bg-gray-50 transition">
                        Comenzar Prueba
                    </a>
                </div>

                <!-- Plan Empresarial -->
                <div class="bg-white border-2 border-gray-200 rounded-2xl p-8 hover:border-indigo-600 transition">
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">Empresarial</h3>
                    <p class="text-gray-600 mb-6">Múltiples sucursales</p>
                    <div class="mb-6">
                        <span class="text-5xl font-bold text-gray-900">RD$5,000</span>
                        <span class="text-gray-600">/mes</span>
                    </div>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-700"><strong>Todo Profesional</strong> +</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-700">Barberos ilimitados</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-700">Múltiples sucursales</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-700">API personalizada</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-700">Gerente de cuenta</span>
                        </li>
                    </ul>
                    <a href="auth/login.php" class="block w-full px-6 py-3 bg-gray-900 text-white rounded-lg text-center font-semibold hover:bg-gray-800 transition">
                        Contactar Ventas
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Demo Section -->
    <section id="demo" class="py-20 bg-gradient-to-br from-indigo-900 to-purple-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-4xl font-bold text-white mb-4">Prueba el Sistema Ahora</h2>
                <p class="text-xl text-indigo-200">Accede con las credenciales de demostración</p>
            </div>

            <div class="grid md:grid-cols-2 gap-8 max-w-3xl mx-auto">
                <div class="bg-white/10 backdrop-blur-lg rounded-xl p-8 border border-white/20 transform hover:scale-105 transition duration-300">
                    <div class="text-center mb-4">
                        <div class="w-20 h-20 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center text-white text-3xl mx-auto mb-4 shadow-lg">
                            🏪
                        </div>
                        <h3 class="text-2xl font-bold text-white mb-2">Dueño de Barbería</h3>
                        <p class="text-indigo-200 text-sm">Panel completo de gestión</p>
                    </div>
                    <div class="space-y-3 text-sm bg-white/5 rounded-lg p-4 mb-4">
                        <p class="text-indigo-100"><strong class="text-white">Email:</strong></p>
                        <p class="font-mono text-white bg-black/20 px-3 py-2 rounded">demo@barberia.com</p>
                        <p class="text-indigo-100"><strong class="text-white">Password:</strong></p>
                        <p class="font-mono text-white bg-black/20 px-3 py-2 rounded">password123</p>
                    </div>
                    <a href="auth/login.php" class="mt-4 block w-full px-6 py-3 bg-white text-indigo-600 rounded-lg text-center font-bold hover:bg-gray-100 transition shadow-lg">
                        Acceder como Owner
                    </a>
                </div>

                <div class="bg-white/10 backdrop-blur-lg rounded-xl p-8 border border-white/20 transform hover:scale-105 transition duration-300">
                    <div class="text-center mb-4">
                        <div class="w-20 h-20 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full flex items-center justify-center text-white text-3xl mx-auto mb-4 shadow-lg">
                            ✂️
                        </div>
                        <h3 class="text-2xl font-bold text-white mb-2">Barbero</h3>
                        <p class="text-indigo-200 text-sm">Panel de barbero individual</p>
                    </div>
                    <div class="space-y-3 text-sm bg-white/5 rounded-lg p-4 mb-4">
                        <p class="text-indigo-100"><strong class="text-white">Email:</strong></p>
                        <p class="font-mono text-white bg-black/20 px-3 py-2 rounded">barbero@demo.com</p>
                        <p class="text-indigo-100"><strong class="text-white">Password:</strong></p>
                        <p class="font-mono text-white bg-black/20 px-3 py-2 rounded">password123</p>
                    </div>
                    <a href="auth/login.php" class="mt-4 block w-full px-6 py-3 bg-white text-indigo-600 rounded-lg text-center font-bold hover:bg-gray-100 transition shadow-lg">
                        Acceder como Barbero
                    </a>
                </div>
            </div>

            <div class="mt-12 text-center">
                <a href="public/booking.php?shop=estilo-rd" class="inline-flex items-center px-8 py-4 bg-white text-indigo-600 rounded-xl font-semibold text-lg hover:bg-gray-100 transition shadow-xl">
                    🌐 Ver Página Pública de Reservas
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 bg-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-4xl font-bold text-gray-900 mb-4">
                ¿Listo para Modernizar tu Barbería?
            </h2>
            <p class="text-xl text-gray-600 mb-8">
                Únete a cientos de barberías que ya están creciendo con BarberSaaS
            </p>
            <a href="auth/login.php" class="inline-block px-12 py-4 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-semibold text-lg hover:from-indigo-700 hover:to-purple-700 transition shadow-xl">
                🚀 Comenzar Gratis por 30 Días
            </a>
            <p class="mt-4 text-sm text-gray-500">No requiere tarjeta de crédito • Cancela cuando quieras</p>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-2xl font-bold gradient-text mb-4">💈 BarberSaaS</h3>
                    <p class="text-gray-400">
                        Sistema profesional de gestión para barberías en República Dominicana.
                    </p>
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Producto</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#features" class="hover:text-white transition">Características</a></li>
                        <li><a href="#pricing" class="hover:text-white transition">Precios</a></li>
                        <li><a href="#demo" class="hover:text-white transition">Demo</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Soporte</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-white transition">Documentación</a></li>
                        <li><a href="#" class="hover:text-white transition">Tutoriales</a></li>
                        <li><a href="#" class="hover:text-white transition">Contacto</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Legal</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-white transition">Términos</a></li>
                        <li><a href="#" class="hover:text-white transition">Privacidad</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; 2026 BarberSaaS. Todos los derechos reservados. Made with ❤️ in República Dominicana</p>
            </div>
        </footer>
    </footer>
</body>
</html>
