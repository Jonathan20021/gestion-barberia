<?php
/**
 * Script para corregir textos corruptos en servicios demo
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Database.php';

echo "==========================================\n";
echo "  NORMALIZACION DE SERVICIOS DEMO\n";
echo "==========================================\n\n";

$db = Database::getInstance();

$demoShop = $db->fetch(
    "SELECT b.id
     FROM barbershops b
     INNER JOIN users u ON u.id = b.owner_id
     WHERE u.email = ?
     ORDER BY b.id ASC
     LIMIT 1",
    ['demo@barberia.com']
);

if (!$demoShop) {
    echo "No se encontro barberia demo para demo@barberia.com\n";
    echo "Nada que corregir.\n";
    exit(0);
}

$barbershopId = (int) $demoShop['id'];

echo "Barberia demo detectada: ID {$barbershopId}\n\n";

$services = [
    1 => ['Corte Clasico', 'Corte tradicional con maquina y tijera', 'Cortes'],
    2 => ['Corte Moderno', 'Cortes modernos: fade, undercut, pompadour', 'Cortes'],
    3 => ['Corte + Barba', 'Combo completo: corte de cabello y arreglo de barba', 'Combos'],
    4 => ['Afeitado Clasico', 'Afeitado tradicional con navaja y toalla caliente', 'Barba'],
    5 => ['Diseno de Barba', 'Diseno y perfilado de barba', 'Barba'],
    6 => ['Tinte de Cabello', 'Aplicacion de tinte profesional', 'Coloracion'],
    7 => ['Tratamiento Capilar', 'Tratamiento hidratante y reparador', 'Tratamientos'],
    8 => ['Corte Nino', 'Corte para ninos hasta 12 anos', 'Cortes'],
];

$updated = 0;

foreach ($services as $displayOrder => $serviceData) {
    [$name, $description, $category] = $serviceData;

    $result = $db->execute(
        "UPDATE services
         SET name = ?, description = ?, category = ?, updated_at = NOW()
         WHERE barbershop_id = ? AND display_order = ?",
        [$name, $description, $category, $barbershopId, $displayOrder]
    );

    if ($result) {
        $updated++;
    }
}

echo "Servicios actualizados: {$updated}\n\n";

echo "Vista final de servicios demo:\n";
$rows = $db->fetchAll(
    "SELECT id, display_order, name, description
     FROM services
     WHERE barbershop_id = ?
     ORDER BY display_order ASC",
    [$barbershopId]
);

foreach ($rows as $row) {
    echo "#{$row['display_order']} {$row['name']} -> {$row['description']}\n";
}

echo "\nCorreccion completada.\n";
