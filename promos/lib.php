<?php
require_once __DIR__ . '/config.php';
date_default_timezone_set('America/Bogota');

// Colección "⚡︎ Ofertas Uno A ⚡︎" (manual). Se puede sobreescribir en config.php.
if (!defined('OFERTAS_COLLECTION_ID')) {
    define('OFERTAS_COLLECTION_ID', 'gid://shopify/Collection/180686913667');
}

// Archivo de "latido" del cron (registra la última ejecución automática).
if (!defined('CRON_HEARTBEAT_FILE')) {
    define('CRON_HEARTBEAT_FILE', __DIR__ . '/cron_last.json');
}

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
    $err  = curl_error($ch);
    curl_close($ch);

    if ($err)          throw new Exception('cURL error obteniendo token: ' . $err);
    if ($code !== 200) throw new Exception('Token Shopify falló (HTTP ' . $code . '): ' . substr($body, 0, 200));

    $data = json_decode($body, true);
    if (empty($data['access_token'])) throw new Exception('Respuesta de token inválida: ' . substr($body, 0, 200));

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
        CURLOPT_TIMEOUT        => 25,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($err)          throw new Exception('cURL: ' . $err);
    if ($code !== 200) throw new Exception('Shopify HTTP ' . $code);

    $data = json_decode($body, true);
    if (!empty($data['errors'])) throw new Exception($data['errors'][0]['message']);
    return $data;
}

// ── JSON storage con lock ──────────────────────────────────────
function loadJson($file, $default = []) {
    if (!file_exists($file)) return $default;
    $raw  = file_get_contents($file);
    $data = $raw ? json_decode($raw, true) : null;
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
    $res   = shopifyGQL('
    query($q: String!) {
        productVariants(first: 10, query: $q) {
            edges {
                node {
                    id sku price compareAtPrice
                    product { id title }
                }
            }
        }
    }', ['q' => 'sku:' . $sku]);

    $edges = $res['data']['productVariants']['edges'] ?? [];
    foreach ($edges as $e) {
        if (strtolower(trim($e['node']['sku'])) === strtolower(trim($sku))) {
            return $e['node'];
        }
    }
    return null;
}

// ── Shopify: fijar precio y precio tachado ─────────────────────
// Usa productVariantsBulkUpdate (API 2025-01+, reemplaza productVariantUpdate)
function setPrices($variantId, $price, $compareAt, $productId = null) {
    $variantInput = [
        'id'    => $variantId,
        'price' => number_format((float)$price, 2, '.', ''),
        'compareAtPrice' => ($compareAt === null || $compareAt === '')
            ? null
            : number_format((float)$compareAt, 2, '.', ''),
    ];

    // productVariantsBulkUpdate requiere el ID del producto padre
    if (!$productId) {
        throw new Exception('Se necesita el ID del producto para actualizar el precio (API 2025-01)');
    }

    $res = shopifyGQL('
    mutation($productId: ID!, $variants: [ProductVariantsBulkInput!]!) {
        productVariantsBulkUpdate(productId: $productId, variants: $variants) {
            productVariants { id price compareAtPrice }
            userErrors { field message }
        }
    }', [
        'productId' => $productId,
        'variants'  => [$variantInput],
    ]);

    $ue = $res['data']['productVariantsBulkUpdate']['userErrors'] ?? [];
    if (!empty($ue)) throw new Exception($ue[0]['message']);
    $variants = $res['data']['productVariantsBulkUpdate']['productVariants'] ?? [];
    return $variants[0] ?? null;
}

// ── Shopify: colección de Ofertas (manual) ─────────────────────
// Agrega el producto a la colección al iniciar la promo y lo quita al terminar.
function addToOfertas($productId) {
    if (!$productId || !OFERTAS_COLLECTION_ID) return;
    $res = shopifyGQL('
    mutation($id: ID!, $productIds: [ID!]!) {
        collectionAddProductsV2(id: $id, productIds: $productIds) {
            job { id }
            userErrors { field message }
        }
    }', ['id' => OFERTAS_COLLECTION_ID, 'productIds' => [$productId]]);

    $ue = $res['data']['collectionAddProductsV2']['userErrors'] ?? [];
    if (!empty($ue)) throw new Exception('colección+: ' . $ue[0]['message']);
}

function removeFromOfertas($productId) {
    if (!$productId || !OFERTAS_COLLECTION_ID) return;
    $res = shopifyGQL('
    mutation($id: ID!, $productIds: [ID!]!) {
        collectionRemoveProducts(id: $id, productIds: $productIds) {
            job { id }
            userErrors { field message }
        }
    }', ['id' => OFERTAS_COLLECTION_ID, 'productIds' => [$productId]]);

    $ue = $res['data']['collectionRemoveProducts']['userErrors'] ?? [];
    if (!empty($ue)) throw new Exception('colección-: ' . $ue[0]['message']);
}

// ── Motor: aplicar y revertir promos vencidas ──────────────────
function processDue() {
    $schedule = loadJson(SCHEDULE_FILE);
    $today    = date('Y-m-d');
    $actions  = [];
    $changed  = false;

    foreach ($schedule as &$p) {
        $start = $p['start'] ?? '';
        $end   = $p['end']   ?? '';
        if (!$start || !$end) continue;

        try {
            // Activar promo que ya inició
            if ($p['status'] === 'programada' && $today >= $start && $today <= $end) {
                $v = findVariantBySku($p['sku']);
                if (!$v) {
                    $p['status'] = 'error';
                    $p['msg']    = 'SKU no encontrado en Shopify';
                } else {
                    $promo  = (float)$p['promoPrice'];
                    // "Precio antes" del archivo: precio tachado durante la promo.
                    // Si no vino en el archivo, se usa el precio actual de Shopify.
                    $before = !empty($p['beforePrice'])
                        ? (float)$p['beforePrice']
                        : (float)$v['price'];
                    $tachado = ($before > $promo) ? $before : null;

                    setPrices($v['id'], $promo, $tachado, $v['product']['id']);

                    $p['variantId']         = $v['id'];
                    $p['productId']         = $v['product']['id'];
                    $p['product']           = $p['product'] ?? $v['product']['title'] ?? '';
                    if (empty($p['product'])) $p['product'] = $v['product']['title'] ?? '';
                    // Estado real de Shopify para poder restaurar sin riesgo.
                    $p['originalPrice']     = $v['price'];
                    $p['originalCompareAt'] = $v['compareAtPrice'];
                    $p['status']            = 'activa';
                    $p['msg']               = 'Aplicada ' . date('Y-m-d H:i');
                    // Agregar a la colección de Ofertas (no rompe la promo si falla).
                    try { addToOfertas($v['product']['id']); $p['enOfertas'] = true; }
                    catch (Exception $ce) { $p['msg'] .= ' · ' . $ce->getMessage(); }
                    $actions[] = [
                        'accion'   => 'aplicada',
                        'sku'      => $p['sku'],
                        'producto' => $p['product'],
                        'promo'    => $promo,
                        'antes'    => $before,
                    ];
                }
                $changed = true;
            }

            // Promo programada que venció sin activarse
            elseif ($p['status'] === 'programada' && $today > $end) {
                $p['status'] = 'vencida';
                $p['msg']    = 'Venció sin aplicarse';
                $changed     = true;
            }

            // Re-aplicar promo ACTIVA que aún está en fechas pero cuyo
            // precio fue cambiado en Shopify (p.ej. por el actualizador de PVP).
            elseif ($p['status'] === 'activa' && $today >= $start && $today <= $end) {
                $v = findVariantBySku($p['sku']);
                if ($v) {
                    $promo   = (float)$p['promoPrice'];
                    $current = (float)$v['price'];
                    // Solo actúa si el precio actual NO es el de promoción.
                    if (abs($current - $promo) > 0.001) {
                        // El nuevo precio real pasa a ser el "antes" (precio tachado)
                        // y el valor al que se restaurará al terminar la promo.
                        $before  = ($current > $promo) ? $current : (float)($p['beforePrice'] ?? 0);
                        $tachado = ($before > $promo) ? $before : null;

                        setPrices($v['id'], $promo, $tachado, $v['product']['id']);

                        $p['variantId']         = $v['id'];
                        $p['productId']         = $v['product']['id'];
                        $p['originalPrice']     = $current;
                        $p['originalCompareAt'] = $v['compareAtPrice'];
                        $p['msg']               = 'Re-aplicada (el precio había cambiado) ' . date('Y-m-d H:i');
                        // Asegurar que siga en la colección de Ofertas.
                        try { addToOfertas($v['product']['id']); $p['enOfertas'] = true; }
                        catch (Exception $ce) { $p['msg'] .= ' · ' . $ce->getMessage(); }
                        $actions[] = [
                            'accion'   => 're-aplicada',
                            'sku'      => $p['sku'],
                            'producto' => $p['product'] ?? '',
                            'promo'    => $promo,
                            'antes'    => $before,
                        ];
                        $changed = true;
                    }
                }
            }

            // Revertir promo activa que ya terminó
            elseif ($p['status'] === 'activa' && $today > $end) {
                if (!empty($p['variantId']) && !empty($p['productId'])) {
                    setPrices($p['variantId'], $p['originalPrice'], $p['originalCompareAt'] ?? null, $p['productId']);
                }
                $p['status'] = 'finalizada';
                $p['msg']    = 'Precio restaurado ' . date('Y-m-d H:i');
                // Quitar de la colección de Ofertas (no rompe el cierre si falla).
                if (!empty($p['productId'])) {
                    try { removeFromOfertas($p['productId']); $p['enOfertas'] = false; }
                    catch (Exception $ce) { $p['msg'] .= ' · ' . $ce->getMessage(); }
                }
                $actions[]   = [
                    'accion'   => 'restaurada',
                    'sku'      => $p['sku'],
                    'producto' => $p['product'] ?? '',
                    'precio'   => $p['originalPrice'],
                ];
                $changed = true;
            }
        } catch (Exception $e) {
            $p['status'] = 'error';
            $p['msg']    = $e->getMessage();
            $changed     = true;
        }
    }
    unset($p);

    if ($changed) saveJson(SCHEDULE_FILE, $schedule);
    foreach ($actions as $a) addHistory($a);

    return $actions;
}
