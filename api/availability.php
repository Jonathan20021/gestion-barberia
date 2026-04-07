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

    $barberId = (int) input('barber_id');
    $date = input('date');
    $serviceId = input('service_id');

    if ($barberId <= 0 || empty($date)) {
        jsonResponse(['success' => false, 'message' => 'Parámetros faltantes'], 400);
    }

    if (!strtotime($date)) {
        jsonResponse(['success' => false, 'message' => 'Fecha inválida'], 400);
    }

    $barberMeta = $db->fetch('SELECT id, barbershop_id FROM barbers WHERE id = ?', [$barberId]);
    if (!$barberMeta) {
        jsonResponse(['success' => false, 'message' => 'Barbero no encontrado'], 404);
    }

    $serviceDuration = 30;
    if (!empty($serviceId)) {
        $service = $db->fetch('SELECT duration FROM services WHERE id = ?', [$serviceId]);
        if ($service && !empty($service['duration'])) {
            $serviceDuration = (int) $service['duration'];
        }
    }

    $dayOfWeek = (int) date('w', strtotime($date));

    $schedule = $db->fetch(
        'SELECT start_time, end_time, is_available FROM barber_schedules WHERE barber_id = ? AND day_of_week = ? LIMIT 1',
        [$barberId, $dayOfWeek]
    );

    if (!$schedule) {
        $shopSchedule = $db->fetch(
            'SELECT open_time, close_time, is_closed FROM barbershop_schedules WHERE barbershop_id = ? AND day_of_week = ? LIMIT 1',
            [$barberMeta['barbershop_id'], $dayOfWeek]
        );

        if ($shopSchedule) {
            $schedule = [
                'start_time' => $shopSchedule['open_time'],
                'end_time' => $shopSchedule['close_time'],
                'is_available' => (int) !$shopSchedule['is_closed']
            ];
        }
    }

    if (!$schedule || !(int) $schedule['is_available']) {
        jsonResponse([
            'success' => true,
            'available_slots' => [],
            'occupied_slots' => [],
            'message' => 'Barbero no disponible este día'
        ]);
    }

    $timeOff = $db->fetch(
        'SELECT id FROM time_off WHERE barber_id = ? AND ? BETWEEN start_date AND end_date LIMIT 1',
        [$barberId, $date]
    );

    if ($timeOff) {
        jsonResponse([
            'success' => true,
            'available_slots' => [],
            'occupied_slots' => [],
            'message' => 'Barbero no disponible (fecha bloqueada)'
        ]);
    }

    $appointments = $db->fetchAll(
        "SELECT start_time, end_time
         FROM appointments
         WHERE barber_id = ?
           AND appointment_date = ?
           AND status NOT IN ('cancelled', 'no_show')",
        [$barberId, $date]
    );

    $startTime = strtotime($schedule['start_time']);
    $endTime = strtotime($schedule['end_time']);

    $intervalSetting = $db->fetch(
        'SELECT setting_value FROM barbershop_settings WHERE barbershop_id = ? AND setting_key = ? LIMIT 1',
        [$barberMeta['barbershop_id'], 'booking_interval_minutes']
    );
    $interval = $intervalSetting ? (int) $intervalSetting['setting_value'] : 15;
    if (!in_array($interval, [5, 10, 15, 20, 30, 60], true)) {
        $interval = 15;
    }

    $availableSlots = [];
    $currentTime = $startTime;

    while ($currentTime + ($serviceDuration * 60) <= $endTime) {
        $slotStart = date('H:i:s', $currentTime);
        $slotEnd = date('H:i:s', $currentTime + ($serviceDuration * 60));

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

    $occupiedSlots = [];
    foreach ($appointments as $apt) {
        $occupiedSlots[] = [
            'start' => substr($apt['start_time'], 0, 5),
            'end' => substr($apt['end_time'], 0, 5),
            'label' => date('g:i A', strtotime($apt['start_time'])) . ' - ' . date('g:i A', strtotime($apt['end_time']))
        ];
    }

    jsonResponse([
        'success' => true,
        'available_slots' => $availableSlots,
        'occupied_slots' => $occupiedSlots,
        'date' => $date,
        'barber_id' => $barberId,
        'service_duration' => $serviceDuration,
        'interval_minutes' => $interval,
        'schedule' => [
            'start_time' => $schedule['start_time'],
            'end_time' => $schedule['end_time']
        ]
    ]);
} catch (Exception $e) {
    jsonResponse([
        'success' => false,
        'message' => 'Error al obtener disponibilidad',
        'error' => ENVIRONMENT === 'development' ? $e->getMessage() : null
    ], 500);
}
