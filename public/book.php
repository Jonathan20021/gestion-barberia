<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/public/index.php');
}

$db = Database::getInstance();

// Validar datos
$barbershopId = input('barbershop_id');
$serviceId = input('service_id');
$barberId = input('barber_id');
$appointmentDate = input('appointment_date');
$startTime = input('start_time');
$clientName = input('client_name');
$clientPhone = input('client_phone');
$clientEmail = input('client_email');
$notes = input('notes');

// Validaciones
if (empty($barbershopId) || empty($serviceId) || empty($barberId) || empty($appointmentDate) || empty($startTime) || empty($clientName) || empty($clientPhone)) {
    setFlash('error', 'Por favor complete todos los campos requeridos');
    redirect($_SERVER['HTTP_REFERER']);
}

// Validar teléfono
if (!isValidPhone($clientPhone)) {
    setFlash('error', 'Formato de teléfono inválido. Use formato: 809-123-4567');
    redirect($_SERVER['HTTP_REFERER']);
}

// Validar email si se proporciona
if (!empty($clientEmail) && !isValidEmail($clientEmail)) {
    setFlash('error', 'Email inválido');
    redirect($_SERVER['HTTP_REFERER']);
}

try {
    // Obtener información del servicio
    $service = $db->fetch("SELECT * FROM services WHERE id = ? AND barbershop_id = ?", [$serviceId, $barbershopId]);
    
    if (!$service) {
        throw new Exception('Servicio no encontrado');
    }
    
    // Calcular hora de fin
    $endTime = date('H:i:s', strtotime($startTime) + ($service['duration'] * 60));
    
    // Verificar disponibilidad
    $conflict = $db->fetch("
        SELECT id FROM appointments
        WHERE barber_id = ? 
        AND appointment_date = ?
        AND status NOT IN ('cancelled', 'no_show')
        AND (
            (start_time <= ? AND end_time > ?)
            OR (start_time < ? AND end_time >= ?)
            OR (start_time >= ? AND end_time <= ?)
        )
    ", [$barberId, $appointmentDate, $startTime, $startTime, $endTime, $endTime, $startTime, $endTime]);
    
    if ($conflict) {
        setFlash('error', 'El horario seleccionado no está disponible. Por favor seleccione otro horario.');
        redirect($_SERVER['HTTP_REFERER']);
    }
    
    // Buscar o crear cliente
    $client = $db->fetch("
        SELECT id FROM clients 
        WHERE barbershop_id = ? AND phone = ?
    ", [$barbershopId, $clientPhone]);
    
    $clientId = null;
    if ($client) {
        $clientId = $client['id'];
        // Actualizar información del cliente
        $db->execute("
            UPDATE clients SET name = ?, email = ? 
            WHERE id = ?
        ", [$clientName, $clientEmail, $clientId]);
    } else {
        // Crear nuevo cliente
        $db->execute("
            INSERT INTO clients (barbershop_id, name, email, phone) 
            VALUES (?, ?, ?, ?)
        ", [$barbershopId, $clientName, $clientEmail, $clientPhone]);
        $clientId = $db->lastInsertId();
    }
    
    // Generar código de confirmación
    $confirmationCode = generateCode(8);
    
    // Crear cita
    $db->execute("
        INSERT INTO appointments (
            barbershop_id, barber_id, client_id, service_id,
            appointment_date, start_time, end_time,
            client_name, client_phone, client_email,
            notes, price, status, confirmation_code
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)
    ", [
        $barbershopId, $barberId, $clientId, $serviceId,
        $appointmentDate, $startTime, $endTime,
        $clientName, $clientPhone, $clientEmail,
        $notes, $service['price'], $confirmationCode
    ]);
    
    $appointmentId = $db->lastInsertId();
    
    // Obtener información de la barbería para mostrar
    $barbershop = $db->fetch("SELECT business_name, slug FROM barbershops WHERE id = ?", [$barbershopId]);
    
    // Redireccionar a página de confirmación
    $_SESSION['appointment_success'] = [
        'id' => $appointmentId,
        'confirmation_code' => $confirmationCode,
        'client_name' => $clientName,
        'date' => $appointmentDate,
        'time' => $startTime,
        'service' => $service['name'],
        'price' => $service['price'],
        'barbershop' => $barbershop['business_name']
    ];
    
    redirect(BASE_URL . '/public/confirmation.php');
    
} catch (Exception $e) {
    logError('Error al crear cita: ' . $e->getMessage());
    setFlash('error', 'Error al procesar la reserva. Por favor intente nuevamente.');
    redirect($_SERVER['HTTP_REFERER']);
}
