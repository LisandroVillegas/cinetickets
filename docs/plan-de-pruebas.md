# Plan de pruebas: CineTickets

**Asignatura:** Ingeniería de Software II · Cotecnova · 2026-2
**Etapa:** Seguimiento 1 y Seguimiento 2 (RF-01 a RF-04)
**Responsable de pruebas:** Lenin Aníbal Herrera Salgado

## 1. Objetivo y alcance

Verificar los requisitos RF-01 a RF-04 y las decisiones de diseño acordadas por el equipo: gestión de funciones, venta de boletos, consulta/edición y anulación con devolución de asientos.

| Incluido en el Seguimiento 1 | Incorporado en el Seguimiento 2 (2026-11-11) |
| --- | --- |
| RF-01: registrar, consultar, actualizar, desactivar y eliminar funciones | RF-02: venta, cálculo de `monto_total` y control de aforo (automatizado) |
| Relación 1:N entre `Pelicula_Funcion` y `Boleto_Reserva` | RF-03: consulta, filtros, edición y validación del correo (automatizado) |
| | RF-04: anulación y devolución de asientos (automatizado) |

## 2. Técnicas de diseño de casos

- **Partición de equivalencia:** se separan las entradas en clases válidas e inválidas (por ejemplo, precio positivo, cero o negativo) y se prueba un representante de cada clase.
- **Análisis de valores límite:** se prueban los valores en el borde de cada regla (precio 0 y 0.01; capacidad 0 y 1).
- **Pruebas de integración:** se verifican las reglas de negocio de extremo a extremo (formulario, controlador, validación y base de datos) y la relación 1:N.

## 3. Entorno y ejecución

- Framework de pruebas: PHPUnit, integrado en Laravel.
- Base de datos de pruebas: SQLite en memoria (las restricciones `CHECK` de MySQL se omiten en este motor; las reglas se validan en la aplicación).
- Ubicación: `tests/Feature/`.
- Comando: `php artisan test`.

## 4. Casos de prueba diseñados (RF-01)

| ID | Criterio | Técnica | Entrada | Resultado esperado | Prueba automatizada |
| --- | --- | --- | --- | --- | --- |
| CP-01 | RF-01 | Clase válida | Todos los campos válidos | Se registra y redirige a la cartelera | `test_cp01_registra_una_funcion_con_datos_validos` |
| CP-02 a CP-08 | CA-01.1 | Partición de equivalencia | Se omite un campo obligatorio (título, género, duración, sala, fecha, precio o capacidad) | Error en ese campo y no se guarda | `test_cp02_a_cp08_ca_01_1_cada_campo_es_obligatorio` |
| CP-09 | CA-01.2 | Valor límite | `precio_unitario` = 0 | Error y no se guarda | `test_cp09_a_cp17_ca_01_2_rechaza_valores_invalidos` |
| CP-10 | CA-01.2 | Partición de equivalencia | `precio_unitario` = -1 | Error y no se guarda | (ídem) |
| CP-11 | CA-01.2 | Valor límite | `precio_unitario` = 0.01 | Se registra | `test_cp11_cp14_ca_01_2_acepta_los_valores_limite_validos` |
| CP-12 | CA-01.2 | Valor límite | `capacidad_total` = 0 | Error y no se guarda | `test_cp09_a_cp17_...` |
| CP-13 | CA-01.2 | Partición de equivalencia | `capacidad_total` = -5 | Error y no se guarda | (ídem) |
| CP-14 | CA-01.2 | Valor límite | `capacidad_total` = 1 | Se registra | `test_cp11_cp14_...` |
| CP-15 | CA-01.2 | Partición de equivalencia | `capacidad_total` = 10.5 (no entero) | Error y no se guarda | `test_cp09_a_cp17_...` |
| CP-16 | CA-01.2 | Valor límite | `duracion_min` = 0 | Error y no se guarda | (ídem) |
| CP-17 | CA-01.2 | Partición de equivalencia | `precio_unitario` = "abc" | Error y no se guarda | (ídem) |
| CP-18 | CA-01.3 | Clase válida | `capacidad_total` = 80 | `asientos_disponibles` = 80 y estado Activa | `test_cp18_ca_01_3_...` |
| CP-19 | CA-01.4 | Partición de equivalencia | `fecha_hora` anterior a la actual | Error y no se guarda | `test_cp19_ca_01_4_rechaza_una_fecha_pasada` |
| CP-20 | CA-01.4 | Partición de equivalencia | `fecha_hora` posterior a la actual | Se registra | `test_cp20_ca_01_4_acepta_una_fecha_futura` |
| CP-21 | CA-01.5 | Partición de equivalencia | Eliminar una función sin boletos | Se elimina | `test_cp21_elimina_una_funcion_sin_boletos` |
| CP-22 | CA-01.5 | Partición de equivalencia | Eliminar una función con boletos | No se elimina y muestra un mensaje | `test_cp22_ca_01_5_no_elimina_una_funcion_con_boletos` |
| CP-23 | RF-01 | Clase válida | Desactivar una función | Estado Inactiva | `test_cp23_desactiva_una_funcion` |
| CP-24 | Decisión 2 | Valor límite | Capacidad 20 con 30 asientos vendidos | Error y no cambia | `test_cp24_rechaza_una_capacidad_menor_...` |
| CP-25 | Decisión 2 | Valor límite | Capacidad 30 con 30 asientos vendidos | 0 asientos disponibles | `test_cp25_capacidad_igual_a_los_vendidos_...` |
| CP-26 | Decisión 2 | Clase válida | Capacidad 50 con 30 vendidos | 20 asientos disponibles | `test_cp26_recalcula_los_asientos_disponibles_...` |
| CP-27 | RF-01 | Partición de equivalencia | Editar el precio de una función con fecha pasada, sin cambiar la fecha | Se actualiza | `test_cp27_permite_editar_el_precio_...` |
| CP-28 | CA-01.4 | Partición de equivalencia | Cambiar la fecha de una función a una fecha pasada | Error | `test_cp28_rechaza_cambiar_la_fecha_...` |
| CP-29 | Modelo 1:N | Integración | Función con dos boletos | `boletos` devuelve 2 y cada boleto conoce su función | `test_cp29_una_funcion_tiene_muchos_boletos_...` |
| CP-30 | Decisión 6 | Integración | Borrar una función con boletos en la base de datos | La base de datos lo impide (llave foránea) | `test_cp30_la_base_de_datos_impide_borrar_...` |
| CP-31 | RF-01 | Clase válida | Abrir la cartelera | Responde 200 y muestra las funciones | `test_cp31_la_cartelera_carga` |

## 5. Casos del Seguimiento 2 automatizados

| Requisito | Casos cubiertos | Archivo |
| --- | --- | --- |
| CA-02.1 | Solo funciones activas, con cupo y futuras; rechazo de inactivas, agotadas, pasadas e inexistentes | `VentaBoletosTest.php` |
| CA-02.2 y CA-02.3 | Cantidad 0, negativa, decimal, texto y nula; compra exacta del cupo; sobrecupo con mensaje del máximo disponible | `VentaBoletosTest.php` |
| CA-02.4 | Cálculo de `monto_total` = cantidad × precio unitario, ignorando el monto enviado por el cliente | `VentaBoletosTest.php` |
| CA-02.5 | La compra descuenta los asientos disponibles | `VentaBoletosTest.php` |
| CA-03.1 | Listado, filtros por cliente/correo/función, filtros combinados y paginación | `ConsultaBoletosTest.php` |
| CA-03.2 | Validación del formato del correo al vender y al editar | `VentaBoletosTest.php`, `ConsultaBoletosTest.php` |
| Decisión 1 | Solo se actualizan nombre y correo; cantidad, monto, función y estado no cambian | `ConsultaBoletosTest.php` |
| CA-04.1 a CA-04.3 | Anular, devolver asientos una sola vez y rechazar la doble anulación | `AnulacionBoletosTest.php` |
| Decisión 3 | Función futura, inactiva futura, pasada, instante exacto y límites de un segundo | `AnulacionBoletosTest.php` |

## 6. Resultados de ejecución

| Fecha | Ejecutó | Pruebas | Pasaron | Fallaron | Observaciones |
| --- | --- | --- | --- | --- | --- |
| 2026-11-05 | Lisandro Villegas Henao | 33 | 33 | 0 | Ejecución exitosa de todas las pruebas automatizadas de RF-01 y relaciones 1:N en PHPUnit usando SQLite en memoria. |
| 2026-11-11 | Lenin Aníbal Herrera Salgado | 77 | 77 | 0 | Suite completa en PHP 8.1.10, Laravel 10.50.4, PHPUnit 10.5.66 y SQLite en memoria. Incluye 44 pruebas nuevas de RF-02, RF-03 y RF-04; las 33 anteriores siguen pasando. |

## 7. Estado de la revisión del 2026-11-10 (histórico)

El resultado del 2026-11-05 es histórico y se conserva como fue registrado. En esa revisión no se volvió a ejecutar la suite porque faltaba `vendor/autoload.php`. Los casos siguientes quedaron diseñados, sin automatizar en ese momento. La prueba de ejemplo de `tests/Unit` no acredita una regla de negocio. Esta revisión quedó superada por la sección 10.

## 8. Casos propuestos para anulación

Base común: reloj fijo en America/Bogota, función futura con capacidad 60 y 50 disponibles, boleto Confirmado de 3 entradas y monto 30000. Los restantes 7 asientos vendidos deben representarse con otros boletos confirmados cuando se compruebe la consistencia global.

| ID | Criterio / técnica | Escenario | Resultado esperado |
| --- | --- | --- | --- |
| AN-01 | CA-04.1 y CA-04.2 / clase válida | Anular boleto confirmado | Cancelado, 53 disponibles, monto 30000 y registro conservado |
| AN-02 | CA-04.3 / transición inválida | Repetir AN-01 | Error, permanecen 53 disponibles |
| AN-03 | Decisión 3 / clase válida | Función futura Inactiva | Anulación permitida, devuelve 3 |
| AN-04 | Decisión 3 / límite | Función un segundo antes del reloj actual | Rechazo sin cambios |
| AN-05 | Decisión 3 / límite propuesto | Función exactamente ahora | Rechazo sin cambios; confirmar esta precisión con el equipo |
| AN-06 | Decisión 3 / límite | Función un segundo después de ahora | Anulación permitida |
| AN-07 | Manejo de errores | ID inexistente | 404, ningún cupo modificado |
| AN-08 | Atomicidad / fallo inyectado | Excepción al persistir una de las escrituras | Rollback: Confirmado y 50 disponibles; ningún mensaje de éxito |
| AN-09 | Integridad / dato inconsistente | 59 disponibles y boleto de 3 con capacidad 60 | Rechazo, cupo 59 y boleto sin cambios |
| AN-10 | CA-04.3 / concurrencia MySQL | Dos solicitudes simultáneas para el mismo boleto | Una anulación efectiva, cupo 53 |
| AN-11 | Integración RF-02 / concurrencia MySQL | Venta de 2 y anulación de 3 simultáneas | Si ambas tienen éxito, cupo 51 |
| AN-12 | Integración RF-01 / concurrencia MySQL | Cambiar capacidad mientras se anula | Cupo igual a capacidad final menos entradas confirmadas; sin pérdida de actualización |
| AN-13 | Interfaz / recorrido manual | Confirmar o rechazar el diálogo del botón | Confirmar actualiza estado; rechazar no envía solicitud |

**Estado de automatización al 2026-11-11:** AN-01 a AN-07 y AN-09 están cubiertos en `tests/Feature/AnulacionBoletosTest.php`, incluyendo los límites de la Decisión 3, el 404 y la integridad de cupos. AN-08 (rollback con fallo inyectado) no se automatizó en esta entrega. AN-10 a AN-12 requieren conexiones independientes en MySQL y configuración dedicada por crear; SQLite no acredita esos bloqueos. AN-13 depende de integrar el botón de anulación en el listado de boletos.

Comando para pruebas funcionales: `php artisan test --filter=AnulacionBoletosTest`. Para regresión: `php artisan test`. Registrar el comando de la suite de concurrencia cuando exista.

## 9. Verificación de implementación — 2026-11-10 (histórico)

Se ejecutó `php artisan test`: **33 pruebas aprobadas, 90 aserciones** en PHP 8.5.12, Laravel 10.50.4 y SQLite en memoria. La suite existente quedó intacta.

Por petición del usuario, no se incorporaron nuevas pruebas al repositorio. Como comprobación local del implementador, fuera del proyecto se ejecutaron 10 casos de anulación y renderizado del parcial (45 aserciones) y un caso de capacidad desactualizada (1 aserción), todos aprobados. Estos archivos temporales no sustituyen la automatización que debe entregar Lenin.

AN-10 y AN-12 se comprobaron con dos procesos independientes en MariaDB 11.8.8 mediante el controlador mysql: se observaron ambas consultas esperando el bloqueo antes de liberarlo; la doble anulación devolvió asientos una vez, y la edición concurrente conservó el cupo correcto. No se ejecutó sobre MySQL de destino. AN-11 quedó pendiente en esa revisión; la venta ya está implementada, pero la prueba de concurrencia sigue requiriendo MySQL. AN-13 solo tiene verificación del renderizado; el recorrido de navegador y su confirmación quedan pendientes del listado.

Esta sección es histórica; la verificación vigente de la suite está en la sección 10.

## 10. Verificación de la suite de RF-02, RF-03 y RF-04 — 2026-11-11

Se reemplazaron los tres archivos de prueba de ejemplo por pruebas de negocio y se ejecutó la suite completa:

| Verificación | Resultado |
| --- | --- |
| Comando | `php artisan test` |
| Entorno | PHP 8.1.10, Laravel 10.50.4, PHPUnit 10.5.66, SQLite en memoria |
| Pruebas | 77 aprobadas, 0 fallidas |
| Aserciones | 269 |
| Archivos nuevos | `tests/Feature/VentaBoletosTest.php` (21), `tests/Feature/ConsultaBoletosTest.php` (12), `tests/Feature/AnulacionBoletosTest.php` (11) |
| Regresión | Las 33 pruebas anteriores de RF-01 y relación 1:N siguen aprobando |

Cobertura por archivo:

- `VentaBoletosTest.php`: CA-02.1 a CA-02.5 y CA-03.2. Incluye formulario, validaciones, sobrecupo, cálculo del monto en servidor, descuento de cupos y correos válidos e inválidos.
- `ConsultaBoletosTest.php`: CA-03.1, CA-03.2 y Decisión 1. Incluye listado, filtros, paginación, edición solo de boletos Confirmado y actualización limitada a nombre y correo.
- `AnulacionBoletosTest.php`: CA-04.1 a CA-04.3 y Decisión 3. Incluye anulación, devolución única, doble anulación, función inactiva futura, función pasada, límites de un segundo, 404 e integridad de cupos.

Límites de esta verificación:

- SQLite en memoria no acredita los bloqueos reales de MySQL; AN-10 a AN-12 siguen pendientes de una suite de concurrencia con conexiones independientes.
- AN-08 (rollback con fallo inyectado) no se automatizó; requeriría simular una excepción en la segunda escritura.
- AN-13 (recorrido de navegador del botón de anulación) depende de incluir `boletos/_anular.blade.php` en el listado, que aún no está integrado.
- Las pruebas comparan algunos mensajes exactos del controlador; un cambio de texto requiere actualizar la prueba correspondiente.
