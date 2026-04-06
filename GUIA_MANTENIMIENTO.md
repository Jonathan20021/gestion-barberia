# 🔧 Guía de Mantenimiento - Diseño Unificado

## 📖 Cómo Modificar el Diseño del Sistema

Esta guía te ayudará a hacer cambios globales de diseño de forma eficiente gracias a los componentes unificados.

---

## 🎨 CAMBIAR COLORES DEL SIDEBAR

### Ubicación de Archivos
- **Admin**: `includes/sidebar-admin.php`
- **Owner**: `includes/sidebar-owner.php`

### Cambiar Color de Fondo

**Buscar** (línea ~2):
```php
<div class="fixed inset-y-0 left-0 z-50 w-64 bg-gradient-to-b from-gray-900 to-gray-800...">
```

**Opciones**:
```php
<!-- Azul oscuro -->
from-blue-900 to-blue-800

<!-- Morado oscuro -->
from-purple-900 to-purple-800

<!-- Verde oscuro -->
from-gray-900 to-gray-800

<!-- Negro sólido -->
bg-black

<!-- Personalizado -->
from-[#1a1a2e] to-[#16213e]
```

### Cambiar Color de Link Activo

**Buscar** en cada `<a>` tag:
```php
<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'text-white bg-indigo-600' : 'text-gray-300 hover:bg-gray-700'; ?>
```

**Cambiar** `bg-indigo-600` por:
```php
bg-blue-600      <!-- Azul -->
bg-purple-600    <!-- Morado -->
bg-green-600     <!-- Verde -->
bg-red-600       <!-- Rojo -->
bg-pink-600      <!-- Rosa -->
bg-yellow-600    <!-- Amarillo -->
```

### Cambiar Color de Hover

**Cambiar** `hover:bg-gray-700` por:
```php
hover:bg-gray-600     <!-- Más claro -->
hover:bg-gray-800     <!-- Más oscuro -->
hover:bg-indigo-600   <!-- Mismo que activo -->
```

---

## 🔐 CAMBIAR DISEÑO DEL LOGIN

### Ubicación
`auth/login.php` líneas 50-230

### Cambiar Gradiente del Lado Izquierdo

**Buscar** (línea ~55):
```html
<div class="... bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-500">
```

**Opciones**:
```html
<!-- Azul a verde -->
from-blue-600 via-cyan-600 to-green-500

<!-- Rojo a naranja -->
from-red-600 via-orange-600 to-yellow-500

<!-- Morado oscuro -->
from-purple-900 via-purple-700 to-indigo-600

<!-- Personalizado -->
from-[#667eea] via-[#764ba2] to-[#f093fb]
```

### Cambiar Colores de Cards de Credenciales

**Buscar** (líneas ~180-220):

**Super Admin Card**:
```html
<div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-3 border border-blue-100">
```

**Owner Card**:
```html  
<div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg p-3 border border-green-100">
```

**Barber Card**:
```html
<div class="bg-gradient-to-r from-purple-50 to-pink-50 rounded-lg p-3 border border-purple-100">
```

**Cambiar colores** manteniendo la estructura:
```html
from-{color}-50 to-{color2}-50 border-{color}-100
```

---

## 🌐 CAMBIAR DISEÑO DEL LANDING

### Ubicación
`landing.php` línea 424+

### Cambiar Colores de Cards Demo

**Owner** (línea ~428):
```html
<div class="w-20 h-20 bg-gradient-to-br from-blue-500 to-indigo-600...">
```

**Barber** (línea ~450):
```html
<div class="w-20 h-20 bg-gradient-to-br from-green-500 to-emerald-600...">
```

---

## ➕ AGREGAR UN NUEVO LINK AL SIDEBAR

### En Admin (`includes/sidebar-admin.php`)

**Agregar antes del `</nav>`**:
```php
<a href="<?php echo BASE_URL; ?>/admin/nueva-pagina.php" 
   class="flex items-center px-4 py-3 <?php echo basename($_SERVER['PHP_SELF']) == 'nueva-pagina.php' ? 'text-white bg-indigo-600' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?> rounded-lg transition">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <!-- Ícono SVG aquí -->
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="..."/>
    </svg>
    <span class="ml-3">Nueva Página</span>
</a>
```

### En Owner (`includes/sidebar-owner.php`)

Mismo procedimiento, cambiar ruta a `/dashboard/nueva-pagina.php`

---

## 🎨 CAMBIAR LOGO

### En Sidebars

**Buscar** (línea ~6-12):
```html
<svg class="w-8 h-8 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <!-- Ícono actual -->
</svg>
<span class="ml-3 text-white font-bold text-lg">Kyros Barber Cloud</span>
```

**Opción 1: Usar imagen**
```html
<img src="<?php echo BASE_URL; ?>/assets/logo.png" class="w-8 h-8" alt="Logo">
<span class="ml-3 text-white font-bold text-lg">TuNombre</span>
```

**Opción 2: Cambiar texto**
```html
<svg...></svg>
<span class="ml-3 text-white font-bold text-lg">MiBarbería Pro</span>
```

**Opción 3: Solo texto**
```html
<span class="text-white font-bold text-xl">MB</span>
<span class="ml-2 text-white font-bold text-lg">MiBarbería</span>
```

---

## 🔧 MODIFICACIONES COMUNES

### Cambiar Ancho del Sidebar

**Buscar** en ambos sidebars (línea ~2):
```html
<div class="... w-64 ...">
```

**Cambiar** a:
```html
w-56   <!-- Más angosto (224px) -->
w-64   <!-- Default (256px) -->
w-72   <!-- Más ancho (288px) -->
w-80   <!-- Muy ancho (320px) -->
```

**Importante**: También cambiar en cada página el padding del content:
```html
<div class="lg:pl-64">  <!-- Cambiar 64 al mismo valor -->
```

### Agregar Badge/Contador

**Ejemplo en Citas**:
```php
<a href="appointments.php" class="...">
    <svg>...</svg>
    <span class="ml-3">Citas</span>
    <?php if ($pendingCount > 0): ?>
        <span class="ml-auto bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full">
            <?php echo $pendingCount; ?>
        </span>
    <?php endif; ?>
</a>
```

### Cambiar Altura del Header

**Buscar** (línea ~6):
```html
<div class="flex items-center justify-between h-16...">
```

**Cambiar** a:
```html
h-12   <!-- Más bajo -->
h-16   <!-- Default -->
h-20   <!-- Más alto -->
```

---

## 📱 PERSONALIZAR MOBILE

### Cambiar Punto de Quiebre Responsive

**Buscar** `lg:` en los sidebars:
```html
class="... lg:translate-x-0"
class="... lg:hidden"
class="... lg:pl-64"
```

**Opciones**:
```html
sm:   <!-- 640px+ -->
md:   <!-- 768px+ -->
lg:   <!-- 1024px+ (default) -->
xl:   <!-- 1280px+ -->
2xl:  <!-- 1536px+ -->
```

---

## 🎨 AGREGAR TEMA OSCURO/CLARO

### Opción 1: Con Alpine.js

**En el wrapper principal**:
```html
<div x-data="{ sidebarOpen: false, darkMode: false }" :class="darkMode ? 'dark' : ''">
```

**En el sidebar**:
```html
<div class="... dark:from-gray-800 dark:to-gray-900">
```

**Botón toggle**:
```html
<button @click="darkMode = !darkMode" class="...">
    <svg x-show="!darkMode">☀️</svg>
    <svg x-show="darkMode">🌙</svg>
</button>
```

### Opción 2: Con CSS Variables

**En `config.php` o header**:
```php
<style>
:root {
    --sidebar-bg: linear-gradient(to bottom, #1a202c, #2d3748);
    --active-link: #4c51bf;
}
</style>
```

**En sidebar**:
```html
<div style="background: var(--sidebar-bg)">
```

---

## 🔍 ICONOS

### Recursos de Iconos SVG

**Heroicons** (usado actualmente):
- https://heroicons.com/

**Otros recursos**:
- Font Awesome: https://fontawesome.com/
- Feather Icons: https://feathericons.com/
- Material Icons: https://fonts.google.com/icons

### Cambiar un Ícono

**Buscar** el `<svg>` actual y **reemplazar** con nuevo:
```html
<!-- Antes -->
<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path d="M3 12l2-2m0 0l7-7..."/>
</svg>

<!-- Después -->
<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path d="NUEVO_PATH_AQUI"/>
</svg>
```

---

## ⚙️ CONFIGURACIÓN AVANZADA

### Active State Personalizado

**Remover** detección automática:
```php
<!-- Antes -->
<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? '...' : '...'; ?>

<!-- Después (manual) -->
class="text-white bg-indigo-600"  <!-- Siempre activo -->
class="text-gray-300"              <!-- Siempre inactivo -->
```

### Agregar Animaciones

**En los links**:
```html
class="... transition-all duration-200 transform hover:translate-x-1"
```

**En el sidebar**:
```html
class="... transition-all duration-300 ease-in-out"
```

**Opciones**:
```html
transition-all     <!-- Transiciona todo -->
duration-150      <!-- Muy rápido -->
duration-300      <!-- Default -->
duration-500      <!-- Lento -->
ease-in-out       <!-- Suave -->
hover:scale-105   <!-- Agranda al hover -->
hover:shadow-lg   <!-- Sombra al hover -->
```

---

## 📦 RESTAURAR DISEÑO ANTERIOR

Si necesitas volver al diseño antiguo:

1. **Remover include**:
```php
<!-- Quitar esto -->
<?php include BASE_PATH . '/includes/sidebar-admin.php'; ?>

<!-- Agregar código inline anterior de cada archivo -->
```

2. **Backup disponible**:
- Revisar historial de git
- O recrear basado en los comentarios

---

## 🚨 TROUBLESHOOTING

### Sidebar no aparece
- ✅ Verificar ruta del include: `BASE_PATH . '/includes/...'`
- ✅ Verificar que Alpine.js esté cargado
- ✅ Verificar `x-data="{ sidebarOpen: false }"` en wrapper

### Active state no funciona
- ✅ Verificar `basename($_SERVER['PHP_SELF'])`
- ✅ Comparar nombre exacto del archivo
- ✅ Revisar comillas en PHP

### Estilos no se aplican
- ✅ Verificar Tailwind CDN en header
- ✅ Limpiar caché del navegador
- ✅ Verificar clases de Tailwind correctas

### Responsive no funciona
- ✅ Verificar viewport meta tag
- ✅ Verificar breakpoints (`lg:`, `md:`, etc)
- ✅ Probar en diferentes tamaños

---

## 📞 SOPORTE

**Archivos principales**:
- `includes/sidebar-admin.php` - Sidebar super admin
- `includes/sidebar-owner.php` - Sidebar propietarios
- `auth/login.php` - Página de login
- `landing.php` - Página landing

**Documentación**:
- `CAMBIOS_DISENO.md` - Historial de cambios
- `RESUMEN_FINAL.md` - Resumen completo
- `GUIA_MANTENIMIENTO.md` - Esta guía

---

**🎨 Kyros Barber Cloud - Guía de Mantenimiento**  
**Última actualización**: <?php echo date('Y-m-d'); ?>
