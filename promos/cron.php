<?php
// Ejecutado por el Cron Job de cPanel cada 30 min, directo con PHP:
//   /usr/local/bin/php /home/scz03p4qessh/public_html/app.uno-a.com/promos/cron.php
// También acepta llamada HTTP con ?key=LLAVE (por si se usa wget).
error_reporting(0);

require_once __DIR__ . '/lib.php';

$esCli = (php_sapi_name() === 'cli');

if (!$esCli) {
    header('Content-Type: application/json');
    if (($_GET['key'] ?? '') !== CRON_KEY) {
        http_response_code(403);
        echo json_encode(['error' => 'Llave incorrecta']);
        exit;
    }
}

set_time_limit(300);

try {
    $actions = processDue();
    $salida = [
        'ok'       => true,
        'hora'     => date('Y-m-d H:i'),
        'acciones' => count($actions),
        'detalle'  => $actions,
    ];
    echo json_encode($salida) . "\n";
} catch (Exception $e) {
    if (!$esCli) http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]) . "\n";
}
