<?php
http_response_code(403);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Acceso denegado</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f7fafc; color: #1f2937; }
        .wrap { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; max-width: 560px; width: 100%; padding: 28px; box-shadow: 0 10px 30px rgba(0,0,0,0.06); }
        h1 { margin: 0 0 8px; font-size: 28px; }
        p { margin: 0 0 14px; line-height: 1.5; }
        a { color: #2563eb; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <h1>403 - Acceso denegado</h1>
            <p>No tienes permisos para acceder a este recurso.</p>
            <p><a href="/">Volver al inicio</a></p>
        </div>
    </div>
</body>
</html>
