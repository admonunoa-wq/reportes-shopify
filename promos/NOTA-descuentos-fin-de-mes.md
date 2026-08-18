# 📌 NOTA — Descuentos del 10% de fin de mes

**Los descuentos automáticos del 10% de fin de mes YA ESTÁN CONFIGURADOS en
Shopify hasta DICIEMBRE 2027.** No hay que hacer nada hasta 2028.

- **Qué:** 10% de descuento en el carrito.
- **Condición:** compras desde **$80.000** (mínimo de compra).
- **Cuándo:** los **últimos 7 días** de cada mes.
- **Tipo:** descuento automático nativo de Shopify (clase ORDER), se refleja
  solo en el carrito / checkout.
- **Creado:** agosto 2026.

## Meses programados (todos en estado "Scheduled")

| Año | Meses | Origen |
|-----|-------|--------|
| 2026 | Agosto | Descuento manual existente |
| 2026 | Septiembre, Octubre, Noviembre, Diciembre | Creados por Claude |
| 2027 | Enero a Diciembre (los 12 meses) | Creados por Claude |

Se activan y se apagan solos en sus fechas. No requieren la app ni el cron.

## ⚠️ Black Friday

Noviembre 2026 y noviembre 2027 tienen el **10% estándar**. Si esos años se
corre una promo de Black Friday más fuerte, **borrar** el descuento
"10% fin de mes — noviembre" de ese año en Shopify → Descuentos, para que no
choque con la de Black Friday.

## 🔜 Qué hacer en 2028

Cuando se acaben los meses programados (a partir de enero 2028), hay dos opciones:

1. **Volver a crearlos** (pedírselo a Claude, o crearlos a mano en Shopify →
   Descuentos → Crear descuento automático, copiando la configuración de
   cualquiera de los actuales).
2. **Automatizarlo en la app:** darle a la app de Shopify los permisos
   `write_discounts` + `read_discounts`, borrar `token_cache.json` del servidor,
   y poner `FINMES_ACTIVO=true` en `config.php`. Así la app los crea sola cada
   mes de ahí en adelante.

---
*Última actualización: agosto 2026.*
