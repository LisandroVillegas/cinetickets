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

Revisión estática del 10 de noviembre de 2026, sobre el commit `24acf39`.

- RF-01: gestión de funciones implementada en controlador, vistas y pruebas.
- RF-02, RF-03 y RF-04: modelos y rutas disponibles; controladores con respuestas 501 de funcionalidad pendiente. No existen aún vistas de ventas o boletos.
- No se ejecutó la aplicación ni la suite en esta revisión: falta `vendor/autoload.php`.

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

Abrir `http://127.0.0.1:8000/funciones`. El layout actual carga Bootstrap por CDN y necesita conexión para esos estilos. Estas instrucciones se derivan del código; no se validaron ejecutándolas en esta revisión.

## Pruebas

```bash
php artisan test
```

`phpunit.xml` configura SQLite en memoria. Las pruebas existentes cubren funciones y relaciones; `tests/Unit/ExampleTest.php` es una prueba de ejemplo, no de negocio. La concurrencia deberá comprobarse en una base MySQL exclusiva para pruebas.

## Documentación

- [Especificación y distribución actual](docs/especificacion.md).
- [Plan de pruebas y resultados históricos](docs/plan-de-pruebas.md).
- [Informe de calidad](docs/informe-calidad.md).
- [Plan de implementación de anulación y calidad](docs/plan-implementacion-anulacion.md).

## Gestión y entrega

Enlace al tablero de Trello: pendiente de incorporar por el equipo.

Los lineamientos requieren las etiquetas `entrega-s1`, `entrega-s2` y `entrega-final`, y entregar sus enlaces junto con Trello. Fechas indicadas: 2 y 9 de noviembre para seguimientos; 18 de noviembre de 2026 a las 11:59 p. m. para entrega final; sustentación el 25 de noviembre. Esta revisión no verifica entregas en Classroom ni acceso del docente.
