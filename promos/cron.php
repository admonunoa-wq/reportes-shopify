<?php
// Lo llama el Cron Job de cPanel cada 30 min:
// wget -q -O /dev/null "https://app.uno-a.com/promos/cron.php?key=LLAVE"
header('Content-Type: application/json');
error_reporting(0);

require_once __DIR__ . '/lib.php';

if (($_GET['key'] ?? '') !== CRON_KEY) {
    http_response_code(403);
    echo json_encode(['error' => 'Llave incorrecta']);
    exit;
}

set_time_limit(300);

try {
    $actions = processDue();
    echo json_encode([
        'ok'      => true,
        'hora'    => date('Y-m-d H:i'),
        'acciones'=> count($actions),
        'detalle' => $actions,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
