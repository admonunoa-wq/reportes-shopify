# Programador de Promociones

Herramienta para automatizar descuentos en la tienda Shopify (Uno A Droguerías).
Programas promociones con un archivo y la app las aplica y revierte sola en las
fechas indicadas — corre en el servidor (GoDaddy/cPanel), sin depender de un
equipo local.

## Cómo se usa

1. Subir un archivo CSV/Excel con columnas tipo:
   `Código Eco (SKU) · Nombre · PVP ANTES · PVP DESP · Inicio · Fin`
   (precios con puntos de miles; SKU = Código Eco).
2. Revisar la vista previa (precio tachado vs. precio promo).
3. Pulsar **Programar**.

Si el archivo no trae fechas, se pueden fijar fechas generales para todas las filas.

## Qué hace automáticamente (cron cada 30 min)

El Cron Job de cPanel ejecuta `cron.php`, que llama a `processDue()`:

- **Al iniciar la promo:** fija el precio promo y el precio tachado (compareAtPrice).
- **Al terminar la promo:** restaura el precio original (y quita el tachado).
- **Re-verificación:** si el actualizador de PVP cambió el precio mientras la
  promo seguía vigente, la vuelve a aplicar.
- **Creación de productos faltantes:** si un SKU no existe en Shopify, crea el
  producto (nombre + precio promo + tachado), en estado **ACTIVE**, con la
  etiqueta `pendiente-enriquecer` para que el proceso de enriquecimiento de las
  mañanas lo complete. La promo queda activa de una, sin re-programar.

## Colección de Ofertas ("⚡︎ Ofertas Uno A ⚡︎")

- Los productos en promo **entran** a la colección al iniciar y **salen** al terminar.
- **Barrido en cada corrida:** mantiene la colección limpia dejando solo productos que:
  - tengan **precio tachado real** (compareAtPrice > price), y
  - **no sean fórmula médica** (se excluyen RX, genéricos y control especial;
    los OTC / venta libre sí pueden entrar).
- El barrido saca cualquier fórmula médica que se cuele, incluso si el
  enriquecimiento reclasifica un producto como RX más adelante.

## Regla de negocio

- **Todas** las referencias del programador actualizan su precio.
- **Solo las que NO son medicamentos de formulación médica** se publican en Ofertas.

## Descuento estándar de fin de mes (10% en el carrito)

Un **10% adicional en todo el carrito** durante los **últimos 7 días de cada mes**.
Se implementa como **descuento automático nativo de Shopify** (se refleja solo en
el carrito / checkout, no en el precio de la vitrina), y lo gestiona el cron sin
intervención manual.

- Cada corrida del cron **crea o actualiza** el descuento automático apuntando a
  la ventana del mes en curso (Shopify lo activa/desactiva solo por fechas).
- Cuando cambia el mes, el cron mueve la ventana al nuevo mes automáticamente.
- Se muestra un **banner arriba de la lista de promociones** con el estado
  (activo ahora / próxima activación / error de permisos).

### Interacción con las promos programadas ("mantener total exacto")

Durante la ventana de fin de mes, para un producto que ya tiene promo programada,
la app **sube el precio de lista lo justo** (`precio_objetivo ÷ 0.9`) para que,
tras el 10% del carrito, el cliente pague **exactamente** el precio de promo
deseado. Así no se apila doble descuento ni se erosiona el margen.

> Ejemplo: promo objetivo $70 (antes $100). En la última semana la app fija el
> precio en ~$77.78; el carrito aplica −10% y el cliente paga **$70 exacto**.
> El tachado sigue mostrando $100.

Al terminar la ventana, la re-verificación devuelve el precio a su valor de promo
normal. Si un producto tiene una promo **menor al 10%**, no se ajusta (el 10% del
carrito se suma encima).

### Requisito de permisos

El descuento automático requiere que la app de Shopify tenga el scope
**`write_discounts`** (y `read_discounts`). Si falta, el banner mostrará el error
y no se creará el descuento (las promos de precio siguen funcionando igual).

### Configuración (`config.php`)

```php
define('FINMES_ACTIVO', true);  // encender/apagar
define('FINMES_PCT',    10);    // % del carrito
define('FINMES_DIAS',   7);     // últimos N días del mes
```

## Monitoreo

- La página muestra un indicador de la **última ejecución automática** del cron:
  - verde = activo (última corrida hace ≤ 40 min),
  - amarillo/rojo = lleva rato sin correr o nunca ha corrido.
- Botón **"▶ Ejecutar y re-verificar precios"** para forzar una corrida manual.
- Botón **"🧹 Limpiar terminadas"** para depurar la lista.

## Archivos

| Archivo | Función |
|---|---|
| `index.php` | Interfaz web (subir archivo, vista previa, tabla de promos, indicador de cron). |
| `lib.php` | Lógica: Shopify API, aplicar/revertir precios, colección de Ofertas, barrido, creación de productos, `esFormulaMedica()`. |
| `api.php` | Endpoints AJAX para la interfaz (programar, listar, cancelar, ejecutar, limpiar). |
| `cron.php` | Punto de entrada del Cron Job; llama a `processDue()` y registra el "latido". |
| `config.php` | Credenciales y parámetros (NO se versiona; ver `config.example.php`). |
| `.htaccess` | Bloquea acceso directo a archivos sensibles (config, .json, .bak, .tmp). |

## Configuración (`config.php`)

Ver `config.example.php`. Claves principales:

- `SHOPIFY_DOMAIN`, `SHOPIFY_CLIENT_ID`, `SHOPIFY_CLIENT_SECRET` — acceso a Shopify.
- `ACCESS_PASSWORD` — contraseña de la herramienta.
- `CRON_KEY` — llave del cron (para llamadas HTTP opcionales).
- `OFERTAS_COLLECTION_ID` — colección de Ofertas.
- `ENRIQUECER_TAG` — etiqueta para productos nuevos (`pendiente-enriquecer`).
- `CREAR_PRODUCTOS_FALTANTES` — activar/desactivar la creación automática.
- `OFERTAS_EXCLUIR` — patrones de fórmula médica a excluir de Ofertas
  (`rx medicamentos|genericos medicamentos|control-especial|control especial`).
- `FINMES_ACTIVO` / `FINMES_PCT` / `FINMES_DIAS` — descuento estándar de fin de mes
  (ver sección dedicada). Requiere el scope `write_discounts`.

## Cron Job (cPanel)

```
0,30 * * * *  /usr/local/bin/php /home/scz03p4qessh/public_html/app.uno-a.com/promos/cron.php
```

## Datos (no se versionan)

- `schedule.json` — promociones programadas.
- `history.json` — historial de acciones.
- `token_cache.json` — token de Shopify (cache 24 h).
- `cron_last.json` — última ejecución del cron (latido).
- `finmes.json` — id y ventana del descuento automático de fin de mes.
