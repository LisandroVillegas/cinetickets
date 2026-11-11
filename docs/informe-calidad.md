# Informe de calidad: CineTickets

**Fecha:** 2026-11-10

**Base de implementación:** `21d35d2`, rama local `feat/anulacion-boletos`.

**Responsable del equipo:** Jorge Alexander Henao Bueno.

**Preparación y ejecución técnica:** asistencia de Codex; pendiente de revisión del responsable. No se atribuyen estas ejecuciones a otros integrantes.

## 1. Alcance y método

Se implementó RF-04 en el controlador existente y se preparó su formulario parcial. Se corrigió la actualización de capacidad para conservar cambios concurrentes de cupos y se configuró America/Bogota. No se implementaron los módulos de venta o consulta asignados a otros integrantes.

Se instalaron las dependencias del lockfile, se preparó un .env local ignorado por Git y se ejecutó la suite existente. Por instrucción del usuario, no se agregaron ni modificaron archivos de pruebas. Las comprobaciones adicionales fueron temporales y externas al repositorio. No se auditaron Trello, Classroom ni todo el historial de secretos.

## 2. Métricas y evidencia

| Verificación | Resultado | Límite |
| --- | --- | --- |
| `php artisan test` | 33 aprobadas, 90 aserciones | Suite existente, sin nuevos tests integrados |
| Comprobación temporal de anulación | 10 aprobadas, 45 aserciones | Estados, fecha, devolución, rollback, 404, integridad y renderizado del parcial |
| Comprobación temporal de capacidad | 1 aprobada, 1 aserción | Reproduce y corrige el uso de cupos obsoletos |
| Doble anulación concurrente | Aprobada; 50 → 53, una sola anulación efectiva | Dos procesos en MariaDB 11.8.8, no MySQL de destino |
| Anulación y edición de capacidad concurrentes | Aprobada; capacidad 70 y disponibilidad 63 | Dos procesos en MariaDB 11.8.8 |
| Venta y anulación concurrentes | No ejecutada | VentaController pendiente |
| Flujo completo desde listado | No ejecutado | BoletoController pendiente; parcial renderizado por separado |
| Cobertura porcentual | No medida | No presentar porcentaje inferido |

Entorno: PHP 8.5.12, Laravel 10.50.4, PHPUnit 10.5.66, SQLite en memoria; base MariaDB temporal sin acceso de red y con controlador PDO mysql cargado desde /tmp. La base temporal no contiene datos reales. No se migró ni convirtió ninguna base del usuario.

El resultado histórico del 2026-11-05 se conserva en el plan de pruebas. Las comprobaciones temporales no son pruebas versionadas ni sustituyen la entrega de Lenin.

## 3. Registro de defectos

| ID | Descripción | Ubicación | Severidad | Estado | Corrección |
| --- | --- | --- | --- | --- | --- |
| D-01 | Faltaba la vista base `layouts.app`, por lo que la cartelera lanzaba un error de vista no encontrada. | `resources/views/layouts/app.blade.php` | Alta | Corrección registrada; pendiente de reverificación | Se creó el layout base con Bootstrap 5. |
| D-02 | Aviso de deprecación en PHP 8.5: `PDO::MYSQL_ATTR_SSL_CA` ya no se recomienda. | `config/database.php` (conexión `mysql` en la copia revisada) | Baja | Corrección registrada; pendiente de reverificación | Se usa `\Pdo\Mysql::ATTR_SSL_CA` en PHP 8.5 o superior y la constante anterior en versiones previas. |
| D-03 | Las restricciones `CHECK` de las migraciones usan `ALTER TABLE ... ADD CONSTRAINT`, que SQLite no soporta, y las pruebas con SQLite en memoria fallaban al crear las tablas. | `database/migrations/` | Media | Corrección registrada; pendiente de reverificación | Las sentencias `CHECK` se ejecutan solo si el motor no es SQLite. |

## 4. Hallazgos y correcciones de esta implementación

| ID | Hallazgo | Evidencia y estado |
| --- | --- | --- |
| H-01 | Tres controladores devolvían 501 | AnulacionController implementado; VentaController y BoletoController siguen pendientes |
| H-02 | La configuración fijaba UTC | Cambiada a America/Bogota; comprobados límites un segundo antes, a la hora exacta y un segundo después |
| H-03 | La edición de capacidad sobrescribía cupos de un modelo desactualizado | Reproducido antes de corregir: con 53 disponibles y nueva capacidad 70 guardaba 60 en vez de 63. Corregido con relectura y bloqueo dentro de transacción; verificación posterior aprobada |
| H-04 | Faltan las pantallas de ventas y boletos | Parcial de anulación preparado; integración pendiente de Andrés |

D-01 a D-03 se conservan como historial. La suite actual verifica las vistas y migraciones usadas en SQLite, pero no demuestra por sí sola todos los escenarios históricos o compatibilidad con todas las versiones de PHP y motores.

## 5. Riesgos y pendientes

- La ruta de anulación redirige a boletos.index, que sigue devolviendo 501 hasta completar el listado. Esto impide considerar terminado el recorrido de usuario.
- El botón se incorpora con `@include('boletos._anular', ['boleto' => $boleto])`. El listado debe cargar la relación funcion.
- La venta deberá respetar las transacciones y el orden de bloqueos función → boleto cuando use ambos registros. Su concurrencia real queda por comprobar cuando exista.
- MariaDB con PDO mysql no reemplaza la verificación de la versión MySQL de entrega.
- En instalaciones previas debe revisarse la convención de fechas antes de cambiar la zona horaria; no se realizó ninguna conversión de datos.
- El layout sigue dependiendo del CDN de Bootstrap.

## 6. Mantenimiento y cierre

| Acción | Responsable | Evidencia de cierre |
| --- | --- | --- |
| Integrar formulario y comprobar recorrido | Andrés y Alexander | Listado, confirmación, mensajes, estado y cupos visibles |
| Implementar venta y comprobar concurrencia conjunta | Lisandro y Alexander | Venta y anulación reales simultáneas |
| Incorporar pruebas automatizadas | Lenin | Casos AN del plan versionados y ejecutados |
| Verificar entorno MySQL de entrega | Equipo | Versión, configuración y resultados reproducibles |
| Mantener informe y seguimiento de defectos | Alexander | Commit revisado, comandos, resultados y pendientes reales |

RF-04 tiene lógica implementada y verificada localmente; su integración de interfaz permanece pendiente. La revisión adicional del código no encontró defectos funcionales en los cambios examinados. El proyecto completo aún no está terminado.

## Verificación posterior de fusión a main — 2026-11-10

Main ya contenía RF-02 y recibió Alex-henao sin conflictos. Se ejecutó la suite existente: 33 aprobadas, 90 aserciones. Las comprobaciones temporales externas sumaron 12 aprobadas, 63 aserciones, incluyendo venta de 3 entradas, monto calculado en servidor, rechazo de sobrecupo, anulación y rechazo de doble devolución. No se modificaron tests del repositorio.

Esta actualización reemplaza el estado anterior de VentaController pendiente: ventas está implementada. BoletoController sigue devolviendo 501; no se incorporó la rama de Andrés. El recorrido completo de interfaz y la concurrencia de venta/anulación en MySQL de destino siguen pendientes. Las comprobaciones de esta fusión usaron SQLite en memoria, sin datos reales.
