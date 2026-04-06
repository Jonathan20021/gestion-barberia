# 🗄️ Migraciones de Base de Datos - Kyros Barber Cloud

## ✅ Estado Actual

**Base de datos:** barberia_saas  
**Tablas creadas:** 18  
**Registros demo:** Instalados  

## 📊 Estructura de la Base de Datos

### Tablas Creadas (18):
- ✅ users (3 usuarios demo)
- ✅ licenses (1 licencia activa)
- ✅ barbershops (1 barbería demo)
- ✅ barbers (1 barbero)
- ✅ services (8 servicios)
- ✅ appointments
- ✅ clients
- ✅ transactions
- ✅ reviews
- ✅ notifications
- ✅ barbershop_schedules (7 horarios)
- ✅ barber_schedules (6 horarios)
- ✅ barber_services (6 relaciones)
- ✅ time_off
- ✅ activity_logs
- ✅ barbershop_settings
- ✅ barbershop_stats (vista)
- ✅ daily_appointments (vista)

## 🚀 Comandos de Migración

### 1. Migración Completa (Primera vez)
```bash
cd C:\xampp\htdocs\gestion-barberia
php migrate.php
```

### 2. Verificar Estado de la BD
```bash
php db-status.php
```

### 3. Migración Manual con MySQL
```bash
C:\xampp\mysql\bin\mysql.exe -u root -e "SOURCE C:/xampp/htdocs/gestion-barberia/config/database.sql"
```

### 4. Eliminar y Recrear BD (CUIDADO: Borra todos los datos)
```bash
C:\xampp\mysql\bin\mysql.exe -u root -e "DROP DATABASE IF EXISTS barberia_saas; SOURCE C:/xampp/htdocs/gestion-barberia/config/database.sql"
```

## 📝 Datos de Demostración Incluidos

### 👥 Usuarios
| Email | Password | Rol | ID |
|-------|----------|-----|-----|
| admin@kyrosbarbercloud.com | password123 | superadmin | 1 |
| demo@barberia.com | password123 | owner | 2 |
| barbero@demo.com | password123 | barber | 3 |

### 🏪 Barbería Demo
- **Nombre:** Barbería El Estilo RD
- **Slug:** estilo-rd
- **Owner:** demo@barberia.com
- **Estado:** Activo
- **Licencia:** Profesional

### ✂️ Servicios Demo (8)
1. Corte de Cabello Clásico - RD$400
2. Corte Moderno con Diseño - RD$600
3. Afeitado Tradicional - RD$350
4. Arreglo de Barba - RD$300
5. Tintura de Cabello - RD$800
6. Tratamiento Capilar - RD$900
7. Corte + Barba Combo - RD$650
8. Corte Infantil - RD$350

## 🔧 Solución de Problemas

### Error: "Database connection failed"
```bash
# 1. Verificar que XAMPP esté corriendo
# 2. Verificar MySQL en el panel de XAMPP
# 3. Ejecutar migración:
php migrate.php
```

### Error: "Table doesn't exist"
```bash
# Ejecutar migración completa:
C:\xampp\mysql\bin\mysql.exe -u root -e "SOURCE C:/xampp/htdocs/gestion-barberia/config/database.sql"
```

### Resetear Base de Datos Completa
```bash
# ADVERTENCIA: Esto eliminará TODOS los datos
C:\xampp\mysql\bin\mysql.exe -u root -e "DROP DATABASE barberia_saas"
php migrate.php
```

## 📖 Acceso al Sistema

Después de ejecutar las migraciones:

**URL Principal:** http://localhost/gestion-barberia

**Páginas de Acceso:**
- Landing: http://localhost/gestion-barberia/landing.php
- Login: http://localhost/gestion-barberia/auth/login.php
- Booking Público: http://localhost/gestion-barberia/public/booking.php?shop=estilo-rd
- Verificador de Instalación: http://localhost/gestion-barberia/check-install.php

## ✅ Verificación Post-Migración

Ejecutar el verificador:
```bash
php db-status.php
```

Debería mostrar:
- ✅ 18 tablas creadas
- ✅ 3 usuarios demo
- ✅ 1 barbería activa
- ✅ 8 servicios configurados

## 🎯 Estado del Sistema

**✅ SISTEMA COMPLETAMENTE FUNCIONAL**

Todas las migraciones han sido ejecutadas exitosamente. El sistema está listo para:
- Login con usuarios demo
- Gestión de citas
- Reservas públicas
- Panel de administración
- Panel de barbería

---

**Última migración:** 5 de abril, 2026  
**Estado:** ✅ Completado  
**Versión:** 1.0.0
