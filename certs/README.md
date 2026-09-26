# SSL Certificates Directory

Place your database CA certificate here for SSL connections.

## For PlanetScale

1. Download the CA certificate from PlanetScale dashboard
2. Save as `ca.pem` in this directory
3. The config.php expects it at `__DIR__ . '/certs/ca.pem'`

## For Neon

Neon uses standard PostgreSQL SSL. For MySQL compatibility mode, check their documentation.

## For Other Providers

Most managed MySQL providers (Aiven, Railway, etc.) provide a CA certificate. Download and place it here.

## Development

For local development without SSL, the config.php will work without this file when `DB_SSL` is false or not set.