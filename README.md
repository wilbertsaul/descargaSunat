# Descarga Masiva de Comprobantes SUNAT (Compras / RCE)

Sistema para descargar de forma masiva, por período, los comprobantes de **compras (RCE)** recibidos:
**XML**, **CDR** (cuando SUNAT lo incluye) y **PDF** de cada factura/boleta, entregados en un único ZIP del período.

## Cómo funciona

1. **Listado (SIRE RCE)** — con el usuario **secundario** SOL se solicita la propuesta del período
   (`exportacioncomprobantepropuesta`), se consulta el ticket y se descarga el TXT de propuesta.
2. **Descarga (json.pe)** — con el usuario **principal** SOL se descarga el XML (ZIP que puede incluir el CDR)
   y el PDF oficial de cada comprobante.
3. **Empaquetado** — al terminar todos los comprobantes se genera un ZIP del período descargable desde la web.

## Requisitos

- PHP 8.3+
- Composer
- Node.js 20+
- SQLite (por defecto) o MySQL 8.0+

## Instalación

```bash
composer install
npm install
npm run build
cp .env.example .env
php artisan key:generate
php artisan migrate
```

## Configuración

Editar `.env` con las credenciales:

```env
# Credenciales de API SUNAT (OAuth) — SOL → Gestión Credenciales de API SUNAT
SUNAT_CLIENT_ID=
SUNAT_CLIENT_SECRET=
SUNAT_RUC=20XXXXXXXXX

# Usuario secundario SOL: se usa para SIRE (listar la propuesta RCE)
SUNAT_SOL_USER=
SUNAT_SOL_PASSWORD=

# Usuario principal SOL: requerido por json.pe para descargar XML/PDF
SUNAT_SOL_PRINCIPAL_USER=
SUNAT_SOL_PRINCIPAL_PASSWORD=

# json.pe (token) — https://app.json.pe
JSONPE_TOKEN=
JSONPE_BASE_URL=https://api.json.pe/api
```

## Ejecución

La descarga se procesa en segundo plano. Necesitas un worker de colas:

```bash
php artisan serve
php artisan queue:work
```

## Uso

1. Inicia sesión y entra a **Descargas → Nueva Descarga**.
2. Indica el período (`AAAA-MM`, no puede ser futuro).
3. El sistema lista la propuesta, descarga y empaqueta. La vista de detalle se actualiza sola
   y muestra, por comprobante, los enlaces **XML / CDR / PDF**.
4. Al finalizar, descarga el **ZIP del período** con todos los documentos.

## Estructura

- `app/Services/Sunat/SunatAuthService.php` — OAuth SUNAT (SIRE/CPE)
- `app/Services/Sunat/SireRceService.php` — propuesta RCE, ticket y descarga del TXT
- `app/Services/JsonPe/JsonPeClientService.php` — XML/PDF por comprobante y extracción de CDR
- `app/Jobs/ListarPropuestaRce.php` — lista la propuesta y crea los comprobantes
- `app/Jobs/DescargarComprobante.php` — descarga XML/CDR/PDF de cada comprobante
- `app/Jobs/EmpaquetarPeriodo.php` — genera el ZIP del período
- `app/Http/Controllers/DescargaMasivaController.php` — UI de descargas
- `resources/views/descargas/` — vistas (listado, creación, detalle)

## Notas importantes

- Las credenciales se guardan únicamente en `.env`, nunca en el repositorio.
- Los documentos se almacenan en `storage/app/private/descargas/`.
- El **CDR** se extrae del ZIP de SUNAT cuando éste lo incluye; si no viene, ese comprobante queda sin CDR.
- El **PDF** es el oficial provisto por json.pe a partir del comprobante en SUNAT.
- El proceso es asíncrono vía Laravel Queues; requiere `queue:work` en ejecución.
