# Despliegue con Docker

El stack ejecuta PHP-FPM, Nginx y un worker de Laravel. MySQL no forma parte del Compose: se utiliza el MySQL instalado en el host.

## Preparación inicial

1. En el servidor, clone el repositorio y sitúese en su directorio.
2. Cree el archivo de secretos fuera de Git:

   ```bash
   cp .env.production.example .env.production
   ```

3. Edite `.env.production`: defina `APP_URL` y capture credenciales reales para MySQL, SMTP y Accesos. Tras construir la imagen, genere una clave con `docker compose run --rm app php artisan key:generate --show` y copie el valor en `APP_KEY`. Mantenga `DB_HOST=host.docker.internal` para usar el gateway de Docker hacia el MySQL del host.
4. Compruebe que el usuario MySQL pueda conectarse desde la red Docker y tenga privilegios únicamente sobre la base del portal.

## Primera publicación

```bash
docker compose build
docker compose up -d
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --force
```

`db:seed` es opcional si los catálogos ya existen en la base de producción. No se debe ejecutar sobre una base existente sin revisar antes los seeders que correspondan.

Nginx publica el puerto `8086` por defecto. Para utilizar otro, defina `PORTAL_HTTP_PORT` al levantar el stack:

```bash
PORTAL_HTTP_PORT=8086 docker compose up -d
```

Configure el proxy HTTPS existente para reenviar el dominio del portal a `http://127.0.0.1:8086` (o al puerto elegido). Si el proxy se ejecuta en otro contenedor, debe poder alcanzar el puerto publicado del host.

## Actualización

```bash
git pull
docker compose build
docker compose up -d
docker compose exec app php artisan migrate --force
```

Los documentos, sesiones y archivos de Laravel permanecen en el volumen `portal_tramites_storage`. No elimine ese volumen durante actualizaciones. Revise la salida de los procesos con `docker compose logs -f app nginx worker`.
