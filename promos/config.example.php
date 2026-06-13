<?php
// ================================================================
// CONFIGURACIÓN - Programador de Promociones
// ================================================================

// Tu dominio de Shopify
define('SHOPIFY_DOMAIN', 'uno-a-droguerias.myshopify.com');

// Credenciales de la app (Shopify Dev Dashboard → Configuración)
define('SHOPIFY_CLIENT_ID', 'PEGA_AQUI_EL_CLIENT_ID');
define('SHOPIFY_CLIENT_SECRET', 'PEGA_AQUI_EL_CLIENT_SECRET');

// Contraseña para entrar a la herramienta
define('ACCESS_PASSWORD', 'cambia-esta-password');

// Llave secreta del cron (debe coincidir con la URL del cron en cPanel)
define('CRON_KEY', 'genera-una-llave-aleatoria');

// Archivos de datos (no tocar)
define('TOKEN_CACHE_FILE', __DIR__ . '/token_cache.json');
define('SCHEDULE_FILE',    __DIR__ . '/schedule.json');
define('HISTORY_FILE',     __DIR__ . '/history.json');
