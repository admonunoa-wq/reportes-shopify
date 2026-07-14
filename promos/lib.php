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

// Etiqueta que el proceso de las mañanas usa para enriquecer productos nuevos.
if (!defined('ENRIQUECER_TAG')) {
    define('ENRIQUECER_TAG', 'pendiente-enriquecer');
}

// Si un SKU de la promo no existe, ¿crear el producto automáticamente?
if (!defined('CREAR_PRODUCTOS_FALTANTES')) {
    define('CREAR_PRODUCTOS_FALTANTES', true);
}

// Categorías/etiquetas de "fórmula médica": NO se publican en Ofertas.
// (RX, control especial y genéricos). Se compara contra productType y tags.
if (!defined('OFERTAS_EXCLUIR')) {
    define('OFERTAS_EXCLUIR', 'rx medicamentos|genericos medicamentos|control-especial|control especial');
}

// ¿Es un producto de fórmula médica? (excluido de la colección de Ofertas)
function esFormulaMedica($productType, $tags) {
    $patrones = array_filter(explode('|', OFERTAS_EXCLUIR));
    $texto = strtolower(' ' . (string)$productType . ' ' . implode(' ', (array)$tags) . ' ');
    // Quitar acentos para comparar sin depender de tildes.
    $texto = strtr($texto, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u']);
    foreach ($patrones as $pat) {
        if (strpos($texto, trim($pat)) !== false) return true;
    }
    // La etiqueta exacta "rx" también marca fórmula médica.
    foreach ((array)$tags as $t) {
        if (strtolower(trim($t)) === 'rx') return true;
    }
    return false;
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
                    product { id title productType tags }
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

// ── Barrido: sacar de Ofertas los productos sin precio tachado ──
// Mantiene la colección limpia: solo quedan productos con compareAtPrice > price.
function cleanOfertasCollection() {
    if (!OFERTAS_COLLECTION_ID) return [];

    $removidos = [];
    $idsQuitar = [];
    $cursor    = null;
    $pagina    = 0;

    do {
        $res = shopifyGQL('
        query($id: ID!, $cursor: String) {
            collection(id: $id) {
                products(first: 50, after: $cursor) {
                    pageInfo { hasNextPage endCursor }
                    edges { node {
                        id title productType tags
                        variants(first: 20) { edges { node { price compareAtPrice } } }
                    } }
                }
            }
        }', ['id' => OFERTAS_COLLECTION_ID, 'cursor' => $cursor]);

        $conn = $res['data']['collection']['products'] ?? null;
        if (!$conn) break;

        foreach ($conn['edges'] as $e) {
            $node    = $e['node'];
            $tachado = false;
            foreach (($node['variants']['edges'] ?? []) as $ve) {
                $price = (float)($ve['node']['price'] ?? 0);
                $cmp   = $ve['node']['compareAtPrice'];
                if ($cmp !== null && $cmp !== '' && (float)$cmp > $price) { $tachado = true; break; }
            }
            // Sacar si no tiene tachado, o si es fórmula médica (RX/control/genéricos).
            $esRx = esFormulaMedica($node['productType'] ?? '', $node['tags'] ?? []);
            if (!$tachado || $esRx) {
                $idsQuitar[]  = $node['id'];
                $removidos[]  = $node['title'] . ($esRx ? ' (fórmula médica)' : '');
            }
        }

        $cursor = !empty($conn['pageInfo']['hasNextPage']) ? $conn['pageInfo']['endCursor'] : null;
        $pagina++;
    } while ($cursor && $pagina < 40);

    // Quitar en lotes de 50
    foreach (array_chunk($idsQuitar, 50) as $lote) {
        shopifyGQL('
        mutation($id: ID!, $productIds: [ID!]!) {
            collectionRemoveProducts(id: $id, productIds: $productIds) {
                job { id } userErrors { field message }
            }
        }', ['id' => OFERTAS_COLLECTION_ID, 'productIds' => $lote]);
    }

    return $removidos;
}

// ── Shopify: crear producto básico ya con promo aplicada ───────
// SKU + nombre + precio promo + precio tachado + etiqueta de enriquecimiento.
// Devuelve la variante con la misma forma que findVariantBySku().
function crearProductoConPromo($sku, $nombre, $promo, $before) {
    $variant = [
        'optionValues'  => [['optionName' => 'Title', 'name' => 'Default Title']],
        'price'         => number_format((float)$promo, 2, '.', ''),
        'inventoryItem' => ['sku' => $sku, 'tracked' => false],
    ];
    if ((float)$before > (float)$promo) {
        $variant['compareAtPrice'] = number_format((float)$before, 2, '.', '');
    }

    $input = [
        'title'          => $nombre,
        'status'         => 'ACTIVE',
        'tags'           => [ENRIQUECER_TAG],
        'productOptions' => [['name' => 'Title', 'values' => [['name' => 'Default Title']]]],
        'variants'       => [$variant],
    ];

    $res = shopifyGQL('
    mutation($input: ProductSetInput!) {
        productSet(synchronous: true, input: $input) {
            product {
                id title
                variants(first: 1) { edges { node { id sku price compareAtPrice } } }
            }
            userErrors { field message }
        }
    }', ['input' => $input]);

    $ue = $res['data']['productSet']['userErrors'] ?? [];
    if (!empty($ue)) throw new Exception($ue[0]['message']);

    $prod = $res['data']['productSet']['product'] ?? null;
    if (!$prod) throw new Exception('productSet no devolvió producto');
    $node = $prod['variants']['edges'][0]['node'] ?? null;

    return [
        'id'             => $node['id'] ?? null,
        'sku'            => $node['sku'] ?? $sku,
        'price'          => $node['price'] ?? $promo,
        'compareAtPrice' => $node['compareAtPrice'] ?? null,
        'product'        => ['id' => $prod['id'], 'title' => $prod['title']],
    ];
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
                $promo      = (float)$p['promoPrice'];
                $beforeFile = (float)($p['beforePrice'] ?? 0);
                $v          = findVariantBySku($p['sku']);
                $creado     = false;

                // Si no existe el SKU, crear el producto básico ya con la promo.
                if (!$v && CREAR_PRODUCTOS_FALTANTES) {
                    $nombre = trim($p['product'] ?? '');
                    if ($nombre === '') {
                        $p['status'] = 'error';
                        $p['msg']    = 'SKU no encontrado y sin nombre para crear el producto';
                    } else {
                        $v = crearProductoConPromo($p['sku'], $nombre, $promo, $beforeFile);
                        $creado = true;
                        addHistory(['accion' => 'producto creado', 'sku' => $p['sku'], 'producto' => $nombre]);
                    }
                }

                if (!$v && $p['status'] !== 'error') {
                    $p['status'] = 'error';
                    $p['msg']    = 'SKU no encontrado en Shopify';
                }

                if ($v) {
                    // "Antes" = precio del archivo, o el precio actual de Shopify.
                    $before  = $beforeFile > 0 ? $beforeFile : (float)$v['price'];
                    $tachado = ($before > $promo) ? $before : null;

                    if ($creado) {
                        // Ya se creó con precio promo + tachado; no re-escribir.
                        $p['originalPrice']     = $before;   // al terminar, vuelve al PVP antes
                        $p['originalCompareAt'] = null;
                        $p['msg']               = 'Producto creado y promo aplicada ' . date('Y-m-d H:i');
                    } else {
                        setPrices($v['id'], $promo, $tachado, $v['product']['id']);
                        $p['originalPrice']     = $v['price'];        // estado real previo
                        $p['originalCompareAt'] = $v['compareAtPrice'];
                        $p['msg']               = 'Aplicada ' . date('Y-m-d H:i');
                    }

                    $p['variantId'] = $v['id'];
                    $p['productId'] = $v['product']['id'];
                    if (empty($p['product'])) $p['product'] = $v['product']['title'] ?? '';
                    $p['status'] = 'activa';

                    // Agregar a Ofertas solo si: hay tachado (descuento real)
                    // y NO es fórmula médica (RX / control / genéricos).
                    $prodTipo = $v['product']['productType'] ?? '';
                    $prodTags = $v['product']['tags'] ?? [];
                    $esRx     = esFormulaMedica($prodTipo, $prodTags);
                    if ($tachado !== null && !$esRx) {
                        try { addToOfertas($v['product']['id']); $p['enOfertas'] = true; }
                        catch (Exception $ce) { $p['msg'] .= ' · ' . $ce->getMessage(); }
                    } elseif ($esRx) {
                        $p['msg'] .= ' · fórmula médica: no se publica en Ofertas';
                    }

                    $actions[] = [
                        'accion'   => $creado ? 'creada+aplicada' : 'aplicada',
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
                        // Sigue en Ofertas solo si hay tachado y no es fórmula médica.
                        $esRx = esFormulaMedica($v['product']['productType'] ?? '', $v['product']['tags'] ?? []);
                        if ($tachado !== null && !$esRx) {
                            try { addToOfertas($v['product']['id']); $p['enOfertas'] = true; }
                            catch (Exception $ce) { $p['msg'] .= ' · ' . $ce->getMessage(); }
                        }
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

    // Barrido de la colección de Ofertas: quitar los que no tengan tachado.
    try {
        $fuera = cleanOfertasCollection();
        foreach ($fuera as $titulo) {
            $actions[] = ['accion' => 'sacada de ofertas (sin tachado)', 'producto' => $titulo];
        }
    } catch (Exception $e) {
        // No romper el proceso si el barrido falla.
    }

    foreach ($actions as $a) addHistory($a);

    return $actions;
}
