<?php
// ================================================================
// CONFIGURACIÓN - Edita estos valores y guarda el archivo
// ================================================================

// Tu dominio de Shopify (sin https://, sin barra al final)
define('SHOPIFY_DOMAIN', 'uno-a-droguerias.myshopify.com');

// Credenciales de la app "Actualizador de promos"
// Las encuentras en: Shopify Dev Dashboard → tu app → Configuración
// (sección de credenciales: Client ID y Client Secret)
define('SHOPIFY_CLIENT_ID', 'PEGA_AQUI_EL_CLIENT_ID');
define('SHOPIFY_CLIENT_SECRET', 'PEGA_AQUI_EL_CLIENT_SECRET');

// Contraseña para entrar al actualizador (pon algo seguro)
define('ACCESS_PASSWORD', 'cambia-esta-password');

// Archivo donde se guarda el token temporal (no tocar)
define('TOKEN_CACHE_FILE', __DIR__ . '/token_cache.json');
