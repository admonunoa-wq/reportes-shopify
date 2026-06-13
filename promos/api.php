<?php
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
error_reporting(0);
ini_set('display_errors', 0);
set_time_limit(300);

require_once __DIR__ . '/lib.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !is_array($input)) {
    http_response_code(400);
    echo json_encode(['error' => 'JSON inválido']);
    exit;
}

$action = $input['action'] ?? '';

try {

    if ($action === 'list') {
        echo json_encode([
            'schedule' => loadJson(SCHEDULE_FILE),
            'history'  => array_slice(loadJson(HISTORY_FILE), 0, 60),
            'now'      => date('Y-m-d H:i'),
        ]);
        exit;
    }

    if ($action === 'schedule') {
        $rows = $input['rows'] ?? [];
        if (empty($rows) || !is_array($rows)) {
            echo json_encode(['error' => 'Sin filas']);
            exit;
        }

        $schedule = loadJson(SCHEDULE_FILE);
        $added = 0; $invalid = 0;

        foreach ($rows as $r) {
            if (!is_array($r)) { $invalid++; continue; }
            $sku     = trim($r['sku'] ?? '');
            $product = trim($r['product'] ?? '');
            $before  = (float)($r['beforePrice'] ?? 0);
            $price   = (float)($r['promoPrice'] ?? 0);
            $start   = trim($r['start'] ?? '');
            $end     = trim($r['end']   ?? '');

            if ($sku === '' || $price <= 0
                || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $start)
                || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)
                || $end < $start) {
                $invalid++;
                continue;
            }

            $schedule[] = [
                'id'          => uniqid('p', true),
                'sku'         => $sku,
                'product'     => $product,
                'beforePrice' => $before,
                'promoPrice'  => $price,
                'start'       => $start,
                'end'         => $end,
                'status'      => 'programada',
                'msg'         => '',
                'creada'      => date('Y-m-d H:i'),
            ];
            $added++;
        }

        saveJson(SCHEDULE_FILE, $schedule);
        if ($added > 0) addHistory(['accion' => 'programadas', 'detalle' => $added . ' promos cargadas']);
        $actions = processDue();

        echo json_encode([
            'added'    => $added,
            'invalid'  => $invalid,
            'applied'  => count(array_filter($actions, fn($a) => $a['accion'] === 'aplicada')),
            'schedule' => loadJson(SCHEDULE_FILE),
        ]);
        exit;
    }

    if ($action === 'cancel') {
        $id = trim($input['id'] ?? '');
        if ($id === '') { echo json_encode(['error' => 'ID requerido']); exit; }

        $schedule = loadJson(SCHEDULE_FILE);
        $found = false;

        foreach ($schedule as &$p) {
            if ($p['id'] !== $id) continue;
            $found = true;
            if ($p['status'] === 'activa' && !empty($p['variantId'])) {
                setPrices($p['variantId'], $p['originalPrice'], $p['originalCompareAt'] ?? null);
                addHistory([
                    'accion'   => 'cancelada+restaurada',
                    'sku'      => $p['sku'],
                    'producto' => $p['product'] ?? '',
                    'precio'   => $p['originalPrice'],
                ]);
            } else {
                addHistory(['accion' => 'cancelada', 'sku' => $p['sku']]);
            }
            $p['status'] = 'cancelada';
            $p['msg']    = 'Cancelada ' . date('Y-m-d H:i');
            break;
        }
        unset($p);

        saveJson(SCHEDULE_FILE, $schedule);
        echo json_encode(['ok' => $found, 'schedule' => $schedule]);
        exit;
    }

    if ($action === 'clean') {
        $schedule = array_values(array_filter(
            loadJson(SCHEDULE_FILE),
            fn($p) => in_array($p['status'], ['programada', 'activa'])
        ));
        saveJson(SCHEDULE_FILE, $schedule);
        echo json_encode(['ok' => true, 'schedule' => $schedule]);
        exit;
    }

    if ($action === 'run') {
        $actions = processDue();
        echo json_encode(['actions' => $actions, 'schedule' => loadJson(SCHEDULE_FILE)]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['error' => 'Acción desconocida: ' . htmlspecialchars($action)]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
