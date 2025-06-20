# Tipo Cambio Seguro

Este proyecto proporciona una pequeña aplicación PHP para consultar el tipo de cambio y administrar días festivos. Se ha reestructurado siguiendo el patrón MVC y los principios SOLID.

## Estructura

- `app/` – Contiene modelos, controladores y vistas.
- `public/` – Punto de entrada de la aplicación (`index.php`).
- `actualiza_tipo_cambio.php` – Script CLI para actualizar los valores desde Banxico.
- `docker-compose.yml` y `Dockerfile` – Definiciones para ejecutar la aplicación en contenedores Docker.

## Uso con Docker

1. Copie `.env.example` a `.env` y establezca las variables de conexión.
2. Ejecute `docker-compose up --build`.
3. Acceda a la aplicación en `http://localhost:8080`.

