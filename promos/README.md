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

## Cron Job (cPanel)

```
0,30 * * * *  /usr/local/bin/php /home/scz03p4qessh/public_html/app.uno-a.com/promos/cron.php
```

## Datos (no se versionan)

- `schedule.json` — promociones programadas.
- `history.json` — historial de acciones.
- `token_cache.json` — token de Shopify (cache 24 h).
- `cron_last.json` — última ejecución del cron (latido).
