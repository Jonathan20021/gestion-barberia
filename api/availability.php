<?php
/**
 * API Endpoint - Obtener disponibilidad de horarios
 * GET /api/availability.php?barber_id=1&date=2026-04-15&service_id=2
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

require_once __DIR__ . '/../config/config.php';
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Helpers.php';

try {
    $db = Database::getInstance();
    
    // Validar parámetros
    $barberId = input('barber_id');
    $date = input('date');
    $serviceId = input('service_id');
    
    if (empty($barberId) || empty($date)) {
        jsonResponse(['success' => false, 'message' => 'Parámetros faltantes'], 400);
    }
    
    // Validar fecha
    if (!strtotime($date)) {
        jsonResponse(['success' => false, 'message' => 'Fecha inválida'], 400);
    }
    
    // Obtener duración del servicio
    $serviceDuration = 30; // Por defecto
    if ($serviceId) {
        $service = $db->fetch("SELECT duration FROM services WHERE id = ?", [$serviceId]);
        if ($service) {
            $serviceDuration = $service['duration'];
        }
    }
    
    // Obtener día de la semana (0 = domingo, 6 = sábado)
    $dayOfWeek = date('w', strtotime($date));
    
    // Obtener horario del barbero para ese día
    $schedule = $db->fetch("
        SELECT start_time, end_time, is_available
        FROM barber_schedules
        WHERE barber_id = ? AND day_of_week = ?
    ", [$barberId, $dayOfWeek]);
    
    if (!$schedule || !$schedule['is_available']) {
        jsonResponse([
            'success' => true,
            'available_slots' => [],
            'message' => 'Barbero no disponible este día'
        ]);
    }
    
    // Verificar si hay días libres/vacaciones
    $timeOff = $db->fetch("
        SELECT id FROM time_off
        WHERE barber_id = ?
        AND ? BETWEEN start_date AND end_date
    ", [$barberId, $date]);
    
    if ($timeOff) {
        jsonResponse([
            'success' => true,
            'available_slots' => [],
            'message' => 'Barbero no disponible (vacaciones)'
        ]);
    }
    
    // Obtener citas existentes
    $appointments = $db->fetchAll("
        SELECT start_time, end_time
        FROM appointments
        WHERE barber_id = ?
        AND appointment_date = ?
        AND status NOT IN ('cancelled', 'no_show')
    ", [$barberId, $date]);
    
    // Generar slots disponibles
    $startTime = strtotime($schedule['start_time']);
    $endTime = strtotime($schedule['end_time']);
    $interval = 15; // Intervalos de 15 minutos
    
    $availableSlots = [];
    $currentTime = $startTime;
    
    while ($currentTime + ($serviceDuration * 60) <= $endTime) {
        $slotStart = date('H:i:s', $currentTime);
        $slotEnd = date('H:i:s', $currentTime + ($serviceDuration * 60));
        
        // Verificar si hay conflicto con citas existentes
        $hasConflict = false;
        foreach ($appointments as $apt) {
            $aptStart = strtotime($apt['start_time']);
            $aptEnd = strtotime($apt['end_time']);
            
            if (
                ($currentTime >= $aptStart && $currentTime < $aptEnd) ||
                ($currentTime + ($serviceDuration * 60) > $aptStart && $currentTime + ($serviceDuration * 60) <= $aptEnd) ||
                ($currentTime <= $aptStart && $currentTime + ($serviceDuration * 60) >= $aptEnd)
            ) {
                $hasConflict = true;
                break;
            }
        }
        
        if (!$hasConflict) {
            // Verificar que no sea tiempo pasado
            $slotDateTime = strtotime($date . ' ' . $slotStart);
            if ($slotDateTime > time()) {
                $availableSlots[] = [
                    'time' => date('g:i A', $currentTime),
                    'value' => $slotStart,
                    'end_time' => $slotEnd
                ];
            }
        }
        
        $currentTime += $interval * 60;
    }
    
    jsonResponse([
        'success' => true,
        'available_slots' => $availableSlots,
        'date' => $date,
        'barber_id' => $barberId,
        'service_duration' => $serviceDuration
    ]);
    
} catch (Exception $e) {
    jsonResponse([
        'success' => false,
        'message' => 'Error al obtener disponibilidad',
        'error' => ENVIRONMENT === 'development' ? $e->getMessage() : null
    ], 500);
}
