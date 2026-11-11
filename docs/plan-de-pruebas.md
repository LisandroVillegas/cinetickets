# Plan de pruebas: CineTickets

**Asignatura:** Ingeniería de Software II · Cotecnova · 2026-2
**Etapa:** base de Seguimiento 1 y planificación de RF-04
**Responsable de pruebas:** Lenin Aníbal Herrera Salgado

## 1. Objetivo y alcance

Verificar que la gestión de funciones (RF-01) cumple sus criterios de aceptación (CA-01.1 a CA-01.5) y las decisiones de diseño acordadas por el equipo.

| Incluido en el Seguimiento 1 | Pendiente para el Seguimiento 2 |
| --- | --- |
| RF-01: registrar, consultar, actualizar, desactivar y eliminar funciones | RF-02: venta de boletos, cálculo de `monto_total` y control de aforo |
| Relación 1:N entre `Pelicula_Funcion` y `Boleto_Reserva` | RF-03: consulta de ventas y validación del correo |
| | RF-04: anulación de boletos y devolución de asientos |

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

## 5. Casos previstos para el Seguimiento 2

| Requisito | Casos previstos |
| --- | --- |
| CA-02.2 y CA-02.3 | Cantidad de entradas 0, 1, igual a los asientos disponibles y mayor que ellos |
| CA-02.4 | Cálculo de `monto_total` = cantidad × precio unitario |
| CA-02.5 | La compra descuenta los asientos disponibles y no permite sobreventa |
| CA-03.2 | Validación del formato del correo (válidos e inválidos) |
| CA-04.1 a CA-04.3 | Anular un boleto, devolver los asientos y rechazar la doble anulación |

## 6. Resultados de ejecución

| Fecha | Ejecutó | Pruebas | Pasaron | Fallaron | Observaciones |
| --- | --- | --- | --- | --- | --- |
| 2026-11-05 | Lisandro Villegas Henao | 33 | 33 | 0 | Ejecución exitosa de todas las pruebas automatizadas de RF-01 y relaciones 1:N en PHPUnit usando SQLite en memoria. |

## 7. Estado de la revisión del 2026-11-10

El resultado del 2026-11-05 es histórico y se conserva como fue registrado. No se volvió a ejecutar la suite en esta revisión: falta vendor/autoload.php. Los casos siguientes están diseñados, todavía no automatizados ni ejecutados. La prueba de ejemplo de tests/Unit no acredita una regla de negocio.

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

Comando previsto para pruebas funcionales: `php artisan test --filter=AnulacionBoletoTest`. Para regresión: `php artisan test`. AN-10 a AN-12 requieren conexiones independientes en MySQL y configuración dedicada por crear; SQLite no acredita esos bloqueos. Registrar el comando de esa suite cuando exista.


## 9. Verificación de implementación — 2026-11-10

Se ejecutó `php artisan test`: **33 pruebas aprobadas, 90 aserciones** en PHP 8.5.12, Laravel 10.50.4 y SQLite en memoria. La suite existente quedó intacta.

Por petición del usuario, no se incorporaron nuevas pruebas al repositorio. Como comprobación local del implementador, fuera del proyecto se ejecutaron 10 casos de anulación y renderizado del parcial (45 aserciones) y un caso de capacidad desactualizada (1 aserción), todos aprobados. Estos archivos temporales no sustituyen la automatización que debe entregar Lenin.

AN-10 y AN-12 se comprobaron con dos procesos independientes en MariaDB 11.8.8 mediante el controlador mysql: se observaron ambas consultas esperando el bloqueo antes de liberarlo; la doble anulación devolvió asientos una vez, y la edición concurrente conservó el cupo correcto. No se ejecutó sobre MySQL de destino. AN-11 sigue pendiente por la venta no implementada. AN-13 solo tiene verificación del renderizado; el recorrido de navegador y su confirmación quedan pendientes del listado.
