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

Implementación local del 10 de noviembre de 2026, sobre la base `21d35d2`, en la rama `feat/anulacion-boletos`.

- RF-01: gestión de funciones implementada en controlador, vistas y pruebas.
- RF-02: venta integrada en main; formulario, cálculo del monto y descuento de cupos comprobados localmente.
- RF-03: consulta y edición pendientes de integrar desde la rama de Andrés; BoletoController sigue devolviendo 501.
- RF-04: anulación implementada con transacción, bloqueo, validación de fecha y devolución única de asientos. Formulario disponible en `resources/views/boletos/_anular.blade.php`; falta incluirlo en el listado de Andrés, aún pendiente.
- La edición de capacidad relee la función bajo bloqueo para no sobrescribir una devolución concurrente.
- Suite existente ejecutada: 33 pruebas, 90 aserciones, sin fallos. No se añadieron ni modificaron archivos de pruebas por indicación del responsable. Ver límites y comprobaciones temporales en el informe de calidad.

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

Abrir `http://127.0.0.1:8000/funciones`. El layout actual carga Bootstrap por CDN y necesita conexión para esos estilos. Composer y la suite se ejecutaron localmente. El recorrido web completo sigue pendiente por el listado. La zona horaria es America/Bogota; en instalaciones anteriores se debe revisar cómo se guardaron las fechas antes de desplegar el cambio. No se convirtieron datos existentes.

## Pruebas

```bash
php artisan test
```

`phpunit.xml` configura SQLite en memoria. Las pruebas existentes cubren funciones y relaciones; `tests/Unit/ExampleTest.php` es una prueba de ejemplo, no de negocio. Se comprobaron dos escenarios concurrentes en MariaDB 11.8.8 con el controlador mysql, fuera del repositorio. Quedan pendientes la validación en la versión MySQL de destino y la integración con la venta real.

## Documentación

- [Especificación y distribución actual](docs/especificacion.md).
- [Plan de pruebas y resultados históricos](docs/plan-de-pruebas.md).
- [Informe de calidad](docs/informe-calidad.md).
- [Plan de implementación de anulación y calidad](docs/plan-implementacion-anulacion.md).

## Gestión y entrega

Enlace al tablero de Trello: pendiente de incorporar por el equipo.

Los lineamientos requieren las etiquetas `entrega-s1`, `entrega-s2` y `entrega-final`, y entregar sus enlaces junto con Trello. Fechas indicadas: 2 y 9 de noviembre para seguimientos; 18 de noviembre de 2026 a las 11:59 p. m. para entrega final; sustentación el 25 de noviembre. Esta revisión no verifica entregas en Classroom ni acceso del docente.

## Verificación de la fusión a main — 2026-11-10

La integración de Alex-henao conserva la venta previamente incorporada en main. Pasaron las 33 pruebas existentes (90 aserciones) y 12 comprobaciones temporales externas (63 aserciones): anulación, capacidad y venta seguida de anulación. No se añadieron tests al repositorio. La consulta y edición de Andrés y la incorporación del botón al listado siguen pendientes; la redirección a boletos.index todavía responde 501. No se certifica el flujo completo ni la concurrencia de venta/anulación en MySQL de destino.
