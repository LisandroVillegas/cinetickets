# CineTickets

Sistema web de gestión de funciones de cine y venta de entradas, desarrollado para Ingeniería de Software II · Cotecnova · 2026-2.

## Equipo y distribución

| Integrante | Rol | Código asignado | GitHub |
| --- | --- | --- | --- |
| Lisandro Villegas Henao | Líder técnico | VentaController: creación y registro de ventas; integración | @LisandroVillegas |
| Andrés Felipe Cuéllar | Documentación | BoletoController: listado, filtros y edición | @AndresFF22 |
| Jorge Alexander Henao Bueno | Calidad | AnulacionController: cancelación y devolución de asientos; informe de calidad | @henaoaa |
| Lenin Aníbal Herrera Salgado | Pruebas | Automatización y verificación de los cuatro requisitos | @lenin1125 |

## Estado revisado

Estado de `main` revisado el 13 de noviembre de 2026.

- RF-01: gestión de funciones implementada en controlador, vistas y pruebas.
- RF-02: venta con validaciones, cálculo del monto en el servidor y descuento de cupos.
- RF-03: listado de boletos con filtros, paginación y edición de nombre y correo del cliente.
- RF-04: botón **Anular boleto** integrado en la columna Acciones del listado. Aparece para boletos confirmados de funciones futuras, solicita confirmación y devuelve los asientos mediante una transacción. También permite anular funciones inactivas que todavía no hayan comenzado.
- La edición de capacidad relee la función bajo bloqueo para no sobrescribir una devolución concurrente.
- Verificación actual: **77 pruebas aprobadas y 269 aserciones**. La suite pasó con PHP local y dentro de Sail; después de integrar el botón se repitió con PHP local, con el mismo resultado.
- Docker funciona con MySQL 8.4 saludable y todas las migraciones ejecutadas. Cartelera, creación de funciones, ventas y listado respondieron HTTP 200. Falta comprobar el recorrido completo en navegador y la concurrencia de ventas/anulaciones en MySQL de destino.

## Preparación y ejecución local

Requisitos declarados: PHP compatible con `^8.1`, Composer y MySQL. Habilitar las extensiones requeridas por Composer, PDO MySQL y PDO SQLite para las pruebas. Composer comprobará la compatibilidad con las versiones fijadas en `composer.lock`.

En una copia nueva:

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Si ya existe `.env`, conservarlo. Crear una base de datos local vacía y configurar en `.env` `DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` y `DB_PASSWORD` según el entorno. No subir ese archivo a Git.

```bash
php artisan migrate
php artisan serve
```

Abrir `http://127.0.0.1:8000/funciones`. El layout actual carga Bootstrap por CDN y necesita conexión para esos estilos. Composer y la suite se ejecutaron localmente. El listado ya está integrado; la comprobación manual del recorrido completo en navegador sigue pendiente. La zona horaria es America/Bogota; en instalaciones anteriores se debe revisar cómo se guardaron las fechas antes de desplegar el cambio. No se convirtieron datos existentes.

## Desarrollo con Docker Desktop (Laravel Sail)

La imagen `docker/8.3` utiliza el arranque de Sail con PHP 8.3, Composer 2 y Node.js 22. Incluye las extensiones de MySQL y SQLite.

Con Docker Desktop iniciado, desde la carpeta del proyecto:

```bash
# Solo si todavía no existe .env:
cp -n .env.example .env

# Solo si todavía no existe vendor (no requiere PHP instalado):
docker run --rm -u "$(id -u):$(id -g)" -v "$PWD:/var/www/html" -w /var/www/html laravelsail/php83-composer:latest composer install --ignore-platform-reqs

docker compose up -d --build
# Solo si APP_KEY está vacía en .env:
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan migrate
```

Abrir **http://localhost:8000/funciones**. El proyecto aparecerá como `cinetickets` en Docker Desktop. El código se comparte con los contenedores; los cambios del IDE se reflejan directamente.

Compose configura MySQL dentro del contenedor sin modificar los valores de conexión de tu `.env`. Usa una base nueva llamada `cinetickets`, usuario `sail` y contraseña `sail`, almacenada en el volumen `sail-mysql`. Los datos de tu MySQL anterior no se importan automáticamente. MySQL no expone un puerto al computador. Estas credenciales son para desarrollo local.

El servidor usa `--no-reload` para conservar las variables de Docker en Laravel 10. Los cambios de código se reflejan directamente; después de editar `.env`, ejecuta `docker compose up -d` y `docker compose restart laravel.test`.

Puedes añadir `APP_PORT`, `VITE_PORT`, `WWWUSER`, `WWWGROUP`, `DOCKER_DB_DATABASE`, `DOCKER_DB_PASSWORD` y `DOCKER_DB_ROOT_PASSWORD` a `.env` para ajustar el entorno. En Linux los identificadores predeterminados son 1000; usa los valores de `id -u` e `id -g` si son diferentes. Las variables de inicialización de MySQL solo se aplican a un volumen vacío.

```bash
./vendor/bin/sail test
./vendor/bin/sail artisan migrate:status
./vendor/bin/sail composer install
docker compose logs -f
docker compose stop       # Detener y conservar datos
docker compose up -d      # Volver a iniciar
docker compose down      # Eliminar contenedores y conservar el volumen
```

`docker compose down -v` elimina la base de datos del entorno Docker. Para exportarla antes de cualquier limpieza, utiliza `docker compose exec mysql mysqldump` con las credenciales configuradas.

Las vistas actuales cargan Bootstrap por CDN y no requieren Vite. Si trabajas en los recursos de Vite:

```bash
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev -- --host 0.0.0.0
# O generar recursos estáticos:
./vendor/bin/sail npm run build
```

## Pruebas

```bash
php artisan test
```

`phpunit.xml` fuerza SQLite en memoria, incluso cuando Docker define variables de MySQL. Las pruebas existentes cubren funciones, ventas, consulta, edición, anulación y relaciones; `tests/Unit/ExampleTest.php` es una prueba de ejemplo, no de negocio. Se comprobaron dos escenarios concurrentes en MariaDB 11.8.8 con el controlador mysql, fuera del repositorio. Quedan pendientes la validación en la versión MySQL de destino y la concurrencia conjunta con la venta real en MySQL.

## Documentación

- [Especificación y distribución actual](docs/especificacion.md).
- [Plan de pruebas y resultados históricos](docs/plan-de-pruebas.md).
- [Informe de calidad](docs/informe-calidad.md).
- [Plan de implementación de anulación y calidad](docs/plan-implementacion-anulacion.md).

## Gestión y entrega

Enlace al tablero de Trello: pendiente de incorporar por el equipo.

Los lineamientos requieren las etiquetas `entrega-s1`, `entrega-s2` y `entrega-final`, y entregar sus enlaces junto con Trello. Fechas indicadas: 2 y 9 de noviembre para seguimientos; 18 de noviembre de 2026 a las 11:59 p. m. para entrega final; sustentación el 25 de noviembre. Esta revisión no verifica entregas en Classroom ni acceso del docente.

## Verificación de la fusión a main — 2026-11-10

**Registro histórico del 10 de noviembre; los pendientes de interfaz indicados aquí ya fueron integrados, como describe el estado revisado del 13 de noviembre.**

La integración de Alex-henao conserva la venta previamente incorporada en main. Pasaron las 33 pruebas existentes (90 aserciones) y 12 comprobaciones temporales externas (63 aserciones): anulación, capacidad y venta seguida de anulación. No se añadieron tests al repositorio. La consulta y edición de Andrés y la incorporación del botón al listado siguen pendientes; la redirección a boletos.index todavía responde 501. No se certifica el flujo completo ni la concurrencia de venta/anulación en MySQL de destino.
