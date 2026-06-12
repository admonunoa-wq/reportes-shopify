<?php
require_once __DIR__ . '/config.php';
date_default_timezone_set('America/Bogota');

// ── Access token (client credentials grant, expira cada 24 h) ──
function getAccessToken() {
    if (file_exists(TOKEN_CACHE_FILE)) {
        $cache = json_decode(file_get_contents(TOKEN_CACHE_FILE), true);
        if ($cache && isset($cache['token'], $cache['expires_at'])
            && time() < $cache['expires_at'] - 300) {
            return $cache['token'];
        }
    }

    $ch = curl_init('https://' . SHOPIFY_DOMAIN . '/admin/oauth/access_token');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'grant_type'    => 'client_credentials',
            'client_id'     => SHOPIFY_CLIENT_ID,
            'client_secret' => SHOPIFY_CLIENT_SECRET,
        ]),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT        => 20,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) throw new Exception('Token Shopify falló (HTTP ' . $code . ')');
    $data = json_decode($body, true);
    if (empty($data['access_token'])) throw new Exception('Respuesta de token inválida');

    file_put_contents(TOKEN_CACHE_FILE, json_encode([
        'token'      => $data['access_token'],
        'expires_at' => time() + ($data['expires_in'] ?? 86399),
    ]), LOCK_EX);

    return $data['access_token'];
}

// ── GraphQL ─────────────────────────────────────────────────────
function shopifyGQL($query, $vars = []) {
    $ch = curl_init('https://' . SHOPIFY_DOMAIN . '/admin/api/2025-01/graphql.json');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode(['query' => $query, 'variables' => $vars]),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'X-Shopify-Access-Token: ' . getAccessToken(),
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT        => 20,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($err)          throw new Exception('cURL: ' . $err);
    if ($code !== 200) throw new Exception('HTTP ' . $code);

    $data = json_decode($body, true);
    if (!empty($data['errors'])) throw new Exception($data['errors'][0]['message']);
    return $data;
}

// ── JSON storage con lock ──────────────────────────────────────
function loadJson($file, $default = []) {
    if (!file_exists($file)) return $default;
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : $default;
}

function saveJson($file, $data) {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

function addHistory($entry) {
    $hist = loadJson(HISTORY_FILE);
    $entry['fecha'] = date('Y-m-d H:i');
    array_unshift($hist, $entry);
    saveJson(HISTORY_FILE, array_slice($hist, 0, 300));
}

// ── Shopify: buscar variante por SKU ───────────────────────────
function findVariantBySku($sku) {
    $res = shopifyGQL('
    query($q: String!) {
        productVariants(first: 5, query: $q) {
            edges {
                node {
                    id sku price compareAtPrice
                    product { title }
                }
            }
        }
    }', ['q' => 'sku:' . $sku]);

    foreach ($res['data']['productVariants']['edges'] as $e) {
        if (strtolower(trim($e['node']['sku'])) === strtolower(trim($sku))) {
            return $e['node'];
        }
    }
    return null;
}

// ── Shopify: fijar precio y precio tachado ─────────────────────
function setPrices($variantId, $price, $compareAt) {
    $input = ['id' => $variantId, 'price' => number_format((float)$price, 2, '.', '')];
    // compareAtPrice null lo borra; un valor lo fija como precio tachado
    $input['compareAtPrice'] = ($compareAt === null || $compareAt === '')
        ? null
        : number_format((float)$compareAt, 2, '.', '');

    $res = shopifyGQL('
    mutation($input: ProductVariantInput!) {
        productVariantUpdate(input: $input) {
            productVariant { id price compareAtPrice }
            userErrors { field message }
        }
    }', ['input' => $input]);

    $ue = $res['data']['productVariantUpdate']['userErrors'];
    if (!empty($ue)) throw new Exception($ue[0]['message']);
    return $res['data']['productVariantUpdate']['productVariant'];
}

// ── Motor: aplicar y revertir promos vencidas ──────────────────
// Lo llaman el cron (cron.php) y el botón "Ejecutar ahora" (api.php)
function processDue() {
    $schedule = loadJson(SCHEDULE_FILE);
    $now      = time();
    $actions  = [];
    $changed  = false;

    foreach ($schedule as &$p) {
        $startTs = strtotime($p['start'] . ' 00:00:00');
        $endTs   = strtotime($p['end']   . ' 23:59:59');

        try {
            // Activar promo pendiente que ya inició y no ha vencido
            if ($p['status'] === 'programada' && $now >= $startTs && $now <= $endTs) {
                $v = findVariantBySku($p['sku']);
                if (!$v) {
                    $p['status'] = 'error';
                    $p['msg']    = 'SKU no encontrado en Shopify';
                } else {
                    $orig    = (float)$v['price'];
                    $promo   = (float)$p['promoPrice'];
                    $tachado = $orig > $promo ? $orig : null;

                    setPrices($v['id'], $promo, $tachado);

                    $p['variantId']         = $v['id'];
                    $p['product']           = $v['product']['title'];
                    $p['originalPrice']     = $v['price'];
                    $p['originalCompareAt'] = $v['compareAtPrice'];
                    $p['status']            = 'activa';
                    $p['msg']               = 'Aplicada ' . date('Y-m-d H:i');
                    $actions[] = ['accion' => 'aplicada', 'sku' => $p['sku'],
                                  'producto' => $p['product'], 'promo' => $promo, 'antes' => $orig];
                }
                $changed = true;
            }

            // Promo programada que venció sin llegar a aplicarse
            elseif ($p['status'] === 'programada' && $now > $endTs) {
                $p['status'] = 'vencida';
                $p['msg']    = 'Venció sin aplicarse';
                $changed = true;
            }

            // Revertir promo activa que ya terminó
            elseif ($p['status'] === 'activa' && $now > $endTs) {
                setPrices($p['variantId'], $p['originalPrice'], $p['originalCompareAt'] ?? null);
                $p['status'] = 'finalizada';
                $p['msg']    = 'Precio restaurado ' . date('Y-m-d H:i');
                $actions[]   = ['accion' => 'restaurada', 'sku' => $p['sku'],
                                'producto' => $p['product'] ?? '', 'precio' => $p['originalPrice']];
                $changed = true;
            }
        } catch (Exception $e) {
            $p['status'] = 'error';
            $p['msg']    = $e->getMessage();
            $changed = true;
        }
    }
    unset($p);

    if ($changed) saveJson(SCHEDULE_FILE, $schedule);
    foreach ($actions as $a) addHistory($a);

    return $actions;
}
