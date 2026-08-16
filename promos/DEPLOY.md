# Auto-deploy a hosting (FTP)

La app se sube sola al hosting (`app.uno-a.com`) con GitHub Actions cada vez que
cambia algo en `promos/`. Workflow: `.github/workflows/deploy-promos.yml`.

## Configuración (una sola vez)

1. En cPanel → **Cuentas FTP**, toma (o crea) un usuario FTP y anota:
   - **Host/servidor** FTP (ej. `ftp.uno-a.com` o el que muestre cPanel)
   - **Usuario**
   - **Contraseña**

2. En GitHub → repo **admonunoa-wq/reportes-shopify** → **Settings** →
   **Secrets and variables** → **Actions** → **New repository secret**, crea 3:
   - `FTP_SERVER`
   - `FTP_USERNAME`
   - `FTP_PASSWORD`

3. Listo. Cada cambio en `promos/` se sube solo. También se puede lanzar a mano
   en **Actions** → *Deploy promos a hosting* → **Run workflow**.

## Qué NO se toca en el servidor

El deploy **excluye** (nunca sube ni borra): `config.php`, todos los `*.json`
(datos: schedule, history, token, cron, finmes), `.htaccess`, `README.md`,
`config.example.php`. Así tus credenciales, tus promos y tu seguridad quedan
intactas.

## Ajustes según tu hosting

- Si tu cuenta FTP entra directo a `app.uno-a.com/` (no al home), cambia
  `server-dir` en el workflow a `promos/`.
- Si el host exige FTP simple o SFTP, cambia `protocol` (`ftp` / `sftp`) y el
  `port` (21 / 22).
