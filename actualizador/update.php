<?php
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$raw   = file_get_contents('php://input');
$input = json_decode($raw, true);

if (!$input) {
    echo json_encode(['error' => 'JSON inválido']);
    exit;
}

if (!isset($input['password']) || $input['password'] !== ACCESS_PASSWORD) {
    http_response_code(401);
    echo json_encode(['error' => 'Contraseña incorrecta']);
    exit;
}

// ── GraphQL helper ─────────────────────────────────────────────
function shopifyGQL($query, $vars = []) {
    $payload = json_encode(['query' => $query, 'variables' => $vars]);
    $ch = curl_init('https://' . SHOPIFY_DOMAIN . '/admin/api/2025-01/graphql.json');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'X-Shopify-Access-Token: ' . SHOPIFY_TOKEN,
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT        => 20,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($err)        throw new Exception('cURL: ' . $err);
    if ($code !== 200) throw new Exception('HTTP ' . $code);

    $data = json_decode($body, true);
    if (!empty($data['errors'])) {
        throw new Exception($data['errors'][0]['message']);
    }
    return $data;
}

$action = $input['action'] ?? '';

// ── GET LOCATIONS ───────────────────────────────────────────────
if ($action === 'locations') {
    try {
        $res = shopifyGQL('{
            locations(first: 20) {
                edges { node { id name isActive } }
            }
        }');
        $locs = [];
        foreach ($res['data']['locations']['edges'] as $e) {
            if ($e['node']['isActive']) $locs[] = $e['node'];
        }
        echo json_encode(['locations' => $locs]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// ── UPDATE ONE PRODUCT ──────────────────────────────────────────
if ($action === 'update') {
    $sku        = trim($input['sku']        ?? '');
    $price      = $input['price']           ?? null;
    $inventory  = $input['inventory']       ?? null;
    $locationId = trim($input['locationId'] ?? '');

    if ($sku === '') {
        echo json_encode(['error' => 'SKU vacío']);
        exit;
    }

    try {
        // 1. Find variant by SKU
        $findQ = '
        query($q: String!) {
            productVariants(first: 5, query: $q) {
                edges {
                    node {
                        id sku price
                        product { title }
                        inventoryItem {
                            id
                        }
                    }
                }
            }
        }';

        $res      = shopifyGQL($findQ, ['q' => 'sku:' . $sku]);
        $edges    = $res['data']['productVariants']['edges'];
        $variant  = null;

        foreach ($edges as $e) {
            if (strtolower(trim($e['node']['sku'])) === strtolower($sku)) {
                $variant = $e['node'];
                break;
            }
        }

        if (!$variant) {
            echo json_encode(['notFound' => true]);
            exit;
        }

        $changes = [];
        $errors  = [];

        // 2. Update price
        if ($price !== null && $price !== '') {
            $priceFormatted = number_format((float)$price, 2, '.', '');
            $pMut = '
            mutation($input: ProductVariantInput!) {
                productVariantUpdate(input: $input) {
                    productVariant { id price }
                    userErrors { field message }
                }
            }';
            $pRes = shopifyGQL($pMut, ['input' => ['id' => $variant['id'], 'price' => $priceFormatted]]);
            $ue   = $pRes['data']['productVariantUpdate']['userErrors'];
            if (!empty($ue)) {
                $errors[] = 'Precio: ' . $ue[0]['message'];
            } else {
                $np = $pRes['data']['productVariantUpdate']['productVariant']['price'];
                $changes[] = 'Precio → $' . number_format((float)$np, 0, ',', '.');
            }
        }

        // 3. Update inventory
        if ($inventory !== null && $inventory !== '' && $locationId !== '') {
            $iMut = '
            mutation($input: InventorySetOnHandQuantitiesInput!) {
                inventorySetOnHandQuantities(input: $input) {
                    inventoryAdjustmentGroup {
                        changes { name delta quantity }
                    }
                    userErrors { field message }
                }
            }';
            $iRes = shopifyGQL($iMut, [
                'input' => [
                    'reason'       => 'correction',
                    'setQuantities' => [[
                        'inventoryItemId' => $variant['inventoryItem']['id'],
                        'locationId'      => $locationId,
                        'quantity'        => (int)$inventory,
                    ]],
                ],
            ]);
            $ue = $iRes['data']['inventorySetOnHandQuantities']['userErrors'];
            if (!empty($ue)) {
                $errors[] = 'Inventario: ' . $ue[0]['message'];
            } else {
                $changes[] = 'Stock → ' . (int)$inventory;
            }
        }

        if (!empty($errors)) {
            echo json_encode([
                'success' => false,
                'product' => $variant['product']['title'],
                'error'   => implode(' | ', $errors),
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'product' => $variant['product']['title'],
                'changes' => implode(', ', $changes),
            ]);
        }

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['error' => 'Acción desconocida']);
