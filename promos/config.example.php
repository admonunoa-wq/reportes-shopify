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

// Colección de Ofertas (manual): los productos en promo entran aquí al
// iniciar y salen al terminar. Opcional — si no se define, usa la de Uno A.
define('OFERTAS_COLLECTION_ID', 'gid://shopify/Collection/180686913667');

// Si un SKU de la promo no existe, crear el producto básico automáticamente
// (SKU + nombre + precio promo + tachado) con esta etiqueta para que el
// proceso de las mañanas lo enriquezca. Pon false para desactivar la creación.
define('ENRIQUECER_TAG', 'pendiente-enriquecer');
define('CREAR_PRODUCTOS_FALTANTES', true);

// Fórmula médica: estas categorías/etiquetas NO se publican en Ofertas
// (aunque sí se les aplica el precio de promoción). Separadas por "|".
define('OFERTAS_EXCLUIR', 'rx medicamentos|genericos medicamentos|control-especial|control especial');

// Descuento estándar de fin de mes: % aplicado en el CARRITO durante los
// últimos N días de cada mes, en todos los productos. Se crea como descuento
// automático nativo de Shopify (requiere el permiso write_discounts en la app).
// Durante la ventana, las promos programadas se ajustan solas para que el
// total que paga el cliente quede exacto (no se apila doble descuento).
define('FINMES_ACTIVO', true);   // false para desactivarlo por completo
define('FINMES_PCT',    10);     // porcentaje del carrito
define('FINMES_DIAS',   7);      // últimos N días del mes
// define('FINMES_TITULO', 'Descuento Fin de Mes 10%');  // nombre en Shopify (opcional)

// Archivos de datos (no tocar)
define('FINMES_FILE',      __DIR__ . '/finmes.json');
define('TOKEN_CACHE_FILE', __DIR__ . '/token_cache.json');
define('SCHEDULE_FILE',    __DIR__ . '/schedule.json');
define('HISTORY_FILE',     __DIR__ . '/history.json');
