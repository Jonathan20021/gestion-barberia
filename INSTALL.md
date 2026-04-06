# 📦 BarberSaaS - Guía de Instalación Rápida

## ⚡ Instalación en 5 Minutos

### Paso 1: Requisitos Previos
✅ XAMPP instalado y corriendo
✅ MySQL/Apache activos

### Paso 2: Copiar Archivos
```bash
# Copiar la carpeta al directorio htdocs
C:\xampp\htdocs\gestion-barberia\
```

### Paso 3: Crear Base de Datos
1. Abrir phpMyAdmin: `http://localhost/phpmyadmin`
2. Crear nueva base de datos: `barberia_saas`
3. Ejecutar el archivo `config/database.sql` completo

### Paso 4: Acceder al Sistema
🌐 **URL**: `http://localhost/gestion-barberia`

### Paso 5: Login
```
Super Admin:
📧 Email: admin@barbersaas.com
🔑 Password: password123

Owner/Dueño:
📧 Email: demo@barberia.com
🔑 Password: password123

Barbero:
📧 Email: barbero@demo.com
🔑 Password: password123
```

## 🎯 Prueba Rápida

### 1. Ver Página Pública de Reservas
```
http://localhost/gestion-barberia/public/booking.php?shop=estilo-rd
```

### 2. Hacer una Reserva de Prueba
- Seleccionar servicio
- Elegir barbero
- Completar formulario
- ¡Listo!

### 3. Ver la Reserva en el Dashboard
- Login como owner
- Ir a "Citas"
- Ver la nueva reserva

## 🔧 Configuración Opcional

### Cambiar Puerto de MySQL
Editar `config/config.php`:
```php
define('DB_HOST', 'localhost:3307'); // Si usa puerto diferente
```

### Cambiar URL Base
Si instala en subcarpeta diferente:
```php
define('BASE_URL', 'http://localhost/mi-carpeta');
```

## 🆘 Problemas Comunes

### ❌ Error: "Database connection failed"
✅ Solución: Verificar que MySQL esté corriendo en XAMPP

### ❌ Página en blanco
✅ Solución: Activar errores en `config/config.php`:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### ❌ Estilos no cargan
✅ Solución: Verificar conexión a internet (Tailwind CDN)

## 🎉 ¡Listo!

Sistema instalado y funcional. Explore todas las características:
- ✨ Panel Super Admin
- 🏪 Panel Barbería
- 👤 Panel Barbero
- 🌐 Páginas Públicas

## 📚 Documentación Completa
Ver `README.md` para detalles completos

## 🚀 Empezar a Usar

1. **Super Admin**: Crear nuevas licencias
2. **Owner**: Configurar barbería, agregar servicios
3. **Barbero**: Configurar horarios
4. **Cliente**: Hacer reserva online

---
**¿Preguntas?** Revise la documentación completa en README.md
