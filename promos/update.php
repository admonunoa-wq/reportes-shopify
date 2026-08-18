<?php
// ============================================================================
//  Auto-actualizador de la app de Promociones
//  Descarga los archivos de código desde GitHub (repo público) y los
//  sobrescribe en el servidor. Así no hay que subir ZIP nunca más.
//
//  Uso:  https://app.uno-a.com/promos/update.php?key=TU_CRON_KEY
//
//  Seguridad:
//   - Requiere la llave CRON_KEY (la misma del cron).
//   - Solo actualiza una lista blanca de archivos de código.
//   - NUNCA toca config.php ni los datos (*.json).
//   - Descarga a un temporal, valida y recién ahí reemplaza (atómico).
// ============================================================================

require_once __DIR__ . '/config.php';
header('Content-Type: text/plain; charset=utf-8');

if (($_GET['key'] ?? '') !== CRON_KEY) {
    http_response_code(403);
    exit("Llave incorrecta. Usa ?key=TU_CRON_KEY\n");
}

// Repo y rama de donde se baja el código.
$repo   = 'admonunoa-wq/reportes-shopify';
$branch = 'claude/mac-projects-continuation-9f26ww';
$base   = "https://raw.githubusercontent.com/$repo/$branch/promos/";

// Archivos de CÓDIGO que se actualizan. (config.php y *.json NO están: se respetan.)
$archivos = [
    'index.php',
    'lib.php',
    'api.php',
    'cron.php',
    'update.php',            // se auto-actualiza (aplica en la próxima corrida)
    'config.example.php',
    'README.md',
    'NOTA-descuentos-fin-de-mes.md',
];

echo "Actualizando desde: $repo ($branch)\n";
echo str_repeat('-', 48) . "\n";

$ok = 0; $fail = 0;
foreach ($archivos as $f) {
    // Seguridad: solo nombres simples, sin rutas ni traversal.
    if (!preg_match('/^[A-Za-z0-9._-]+$/', $f) || strpos($f, '..') !== false) {
        echo "SKIP  $f (nombre inválido)\n"; $fail++; continue;
    }

    $ch = curl_init($base . rawurlencode($f));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_USERAGENT      => 'promos-updater',
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($code !== 200 || $body === false || $body === '') {
        echo "ERROR $f (HTTP $code" . ($err ? " · $err" : '') . ")\n"; $fail++; continue;
    }
    // Validación: los .php deben empezar con "<?php".
    if (substr($f, -4) === '.php' && strpos(substr($body, 0, 20), '<' . '?php') === false) {
        echo "ERROR $f (contenido no parece PHP; no se reemplaza)\n"; $fail++; continue;
    }

    // Escritura atómica: temporal + rename.
    $dest = __DIR__ . '/' . $f;
    $tmp  = $dest . '.tmp-update';
    if (file_put_contents($tmp, $body, LOCK_EX) === false) {
        echo "ERROR $f (no se pudo escribir el temporal)\n"; $fail++; continue;
    }
    if (!rename($tmp, $dest)) {
        @unlink($tmp);
        echo "ERROR $f (no se pudo reemplazar)\n"; $fail++; continue;
    }
    echo "OK    $f (" . number_format(strlen($body)) . " bytes)\n";
    $ok++;
}

echo str_repeat('-', 48) . "\n";
echo "Actualizados: $ok · Errores: $fail\n";
echo "config.php y *.json NO se tocaron.\n";
echo "Fecha: " . date('Y-m-d H:i') . " (Bogotá)\n";
