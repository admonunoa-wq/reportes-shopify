<?php
// ================================================================
// PLANTILLA DE CONFIGURACIÓN
// Copia este archivo como config.php y completa los valores.
// El config.php real NUNCA se sube a GitHub (repo público).
// ================================================================

// Tu dominio de Shopify (sin https://, sin barra al final)
define('SHOPIFY_DOMAIN', 'tu-tienda.myshopify.com');

// Credenciales de la app (Shopify Dev Dashboard → tu app → Configuración)
define('SHOPIFY_CLIENT_ID', 'PEGA_AQUI_EL_CLIENT_ID');
define('SHOPIFY_CLIENT_SECRET', 'PEGA_AQUI_EL_CLIENT_SECRET');

// Contraseña para entrar al actualizador (pon algo seguro)
define('ACCESS_PASSWORD', 'cambia-esta-password');

// Archivo donde se guarda el token temporal (no tocar)
define('TOKEN_CACHE_FILE', __DIR__ . '/token_cache.json');
