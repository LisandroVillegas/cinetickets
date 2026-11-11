# Plan de implementación: anulación de boletos e informe de calidad

**Estado:** lógica implementada y comprobada localmente; integración con listado y venta pendiente. Automatización en el repositorio excluida por instrucción del usuario.
**Base:** revisión estática de `24acf39`, 2026-11-10.
**Objetivo:** completar RF-04 en el controlador existente y documentar su calidad con evidencia reproducible.
**Especificación:** [especificacion.md](especificacion.md), RF-04 y decisiones 3 y 6.
**Tecnología:** PHP, Laravel 10, Eloquent, MySQL y PHPUnit; SQLite en memoria para pruebas funcionales.

## Alcance y arquitectura

Implementar `AnulacionController::anular(BoletoReserva $boleto): RedirectResponse`, conservar la ruta `boletos.anular` y utilizar los modelos y tablas existentes. La cancelación y devolución de cupos serán una única transacción. El listado corresponde a Andrés; la venta a Lisandro; la automatización se coordina con Lenin.

No crear un controlador alternativo, duplicar rutas ni añadir tablas. La venta, la consulta y la edición de clientes permanecen a cargo de sus responsables. El desglose original se conserva como referencia; el cierre de esta sección registra lo realizado y sus límites.

## Reglas y decisiones propuestas

- Solo anular boletos Confirmado; conservar el registro, cliente, cantidad y monto.
- Cambiar estado a Cancelado y devolver cantidad_entradas exactamente una vez.
- Admitir funciones inactivas si todavía son futuras.
- Propuesta de límite temporal: exigir fecha_hora > ahora; a la hora exacta de inicio se rechaza. Esta precisión debe acordarse con el equipo.
- Aplicar America/Bogota y validar el efecto sobre datos locales ya existentes antes de cambiar la configuración; no convertir registros a ciegas.
- No aceptar desde el formulario cantidades, montos ni cupos para la devolución: leerlos de la base de datos.
- El éxito redirige a boletos.index con sesión exito; rechazos de negocio a la misma ruta con sesión error; un identificador inexistente devuelve 404.
- La pantalla boletos.index todavía devuelve 501: la prueba de controlador no seguirá la redirección hasta que Andrés implemente el listado.

## 1. Preparar el contrato y las pruebas

**Archivos:** crear `tests/Feature/AnulacionBoletoTest.php`; coordinar `routes/web.php` sin duplicar su ruta existente.

- [ ] Acordar con Lisandro y Andrés los mensajes, destino de redirección y límite temporal anteriores.
- [ ] Preparar dependencias y una base exclusiva de pruebas; no usar datos de desarrollo para ensayos destructivos.
- [ ] Crear casos AN-01 a AN-09 del [plan de pruebas](plan-de-pruebas.md), usando RefreshDatabase y reloj fijo en America/Bogota. Crear modelos directamente con cupos coherentes; no depender de RF-02.
- [ ] Ejecutar `php artisan test --filter=AnulacionBoletoTest`; verificar que los casos de anulación fallan por el 501 actual antes de implementar.

## 2. Implementar la operación atómica

**Modificar:** `app/Http/Controllers/AnulacionController.php`, `config/app.php`.
**Interfaz:** `anular(BoletoReserva $boleto): RedirectResponse`; consume el id del modelo enlazado por Laravel, no confía en sus valores precargados para decidir el estado actual.

- [ ] Cambiar la zona horaria después de revisar la convención de fechas existente.
- [ ] Dentro de una transacción, bloquear y releer la función y después el boleto, siempre en ese orden. Proponer el mismo orden a cualquier operación que bloquee ambos registros.
- [ ] Validar estado, relación y fecha sobre registros actualizados. La función del boleto no puede cambiar según la especificación.
- [ ] Rechazar datos inconsistentes si la devolución excediera capacidad_total; dejar estado y cupo intactos y mostrar un error comprensible.
- [ ] Actualizar estado y cupo dentro de la transacción. Un error al guardar debe revertir ambas escrituras; no mostrar éxito antes del commit.
- [ ] Reemplazar el 501 por las redirecciones y mensajes acordados. No capturar errores internos como si fueran anulaciones exitosas.
- [ ] Ejecutar las pruebas focalizadas y después `php artisan test`; registrar salida real y fallos, sin declarar éxito anticipadamente.

Ejemplo: capacidad 60, disponibles 50, boleto confirmado de 3 entradas. Resultado: disponibles 53, boleto Cancelado y monto intacto. Un segundo intento conserva 53.

## 3. Coordinar la concurrencia con el líder técnico

**Puntos de integración:** `VentaController::store` y `PeliculaFuncionController::update`. Los cambios en estos módulos deben integrarse con sus responsables.

El update actual de funciones recalcula cupos desde un modelo leído fuera de una transacción. Bloquear solo la anulación no impide que ese update guarde después un valor obsoleto. Antes de declarar garantizada la consistencia, la edición de capacidad debe releer la función bajo el mismo bloqueo y recalcular dentro de la transacción. La venta debe conservar su actualización condicional y coordinar cualquier bloqueo adicional.

- [ ] Preparar `tests/Integration/AnulacionConcurrenciaTest.php` y una configuración de pruebas MySQL aislada; dos conexiones o procesos independientes y una barrera de inicio, no solo dos solicitudes secuenciales.
- [ ] Ejecutar AN-10: dos anulaciones del mismo boleto; solo una tiene éxito y el cupo aumenta una vez.
- [ ] Ejecutar AN-11: venta de 2 entradas y anulación de 3 sobre una función con 50 disponibles; con ambas operaciones exitosas quedan 51.
- [ ] Ejecutar AN-12: edición de capacidad simultánea; el cupo final equivale a capacidad menos entradas confirmadas, sin perder devoluciones ni ventas.
- [ ] Incorporar la suite MySQL a una configuración dedicada y documentar su comando exacto al crearla. No ejecutar esa suite sobre SQLite ni presentar un salto por falta de MySQL como prueba aprobada.

## 4. Integrar la interfaz

**Modificar cuando exista:** `resources/views/boletos/index.blade.php`, propiedad de Andrés. Puede extraerse el formulario a `resources/views/boletos/_anular.blade.php` para facilitar integración.

- [ ] Añadir formulario POST con CSRF y método PATCH hacia boletos.anular, y confirmación del usuario.
- [ ] Mostrar la acción para boletos confirmados de funciones futuras, también inactivas; mantener las mismas validaciones en el servidor.
- [ ] Reutilizar los mensajes exito/error de `resources/views/layouts/app.blade.php`.
- [ ] Verificar AN-13: desde el listado, cancelar, ver estado Cancelado y comprobar disponibilidad; comprobar que cancelar la confirmación no envía la solicitud.

## 5. Completar las evidencias de calidad

**Modificar:** `docs/informe-calidad.md`, `docs/plan-de-pruebas.md`; actualizar el estado de RF-04 en README y especificación únicamente después de verificarlo.

- [ ] Conservar la evidencia histórica y añadir fecha, entorno, versión/commit, comando y resultados de cada ejecución nueva.
- [ ] Vincular AN-01 a AN-13 con sus métodos de prueba o procedimiento manual; separar diseñados, automatizados, ejecutados y aprobados.
- [ ] Registrar defectos reproducidos con pasos, ubicación, severidad, responsable y evidencia de regresión. H-03 es un riesgo estático hasta su comprobación.
- [ ] Actualizar métricas con resultados reales; no atribuir autoría o ejecución humana no confirmada.
- [ ] Revisar la documentación y los diffs; realizar commits separados por operación, integración y evidencia cuando se ejecute el plan.

## Criterio de cierre

RF-04 cumple CA-04.1 a CA-04.3 y la decisión 3; sus pruebas y regresión pasan, la concurrencia se verifica en MySQL, el botón funciona en el listado y el informe declara los resultados y límites reales. Si una dependencia sigue pendiente, marcar la parte implementada y la integración pendiente explícitamente.

## Resultado de la implementación — 2026-11-10

- Implementados el método existente de anulación, la devolución atómica y el bloqueo función → boleto; ruta conservada.
- Rechazo de boletos cancelados, funciones iniciadas y devoluciones que superen capacidad. Cliente, cantidad y monto se conservan; el formulario no puede alterar el cupo devuelto.
- Límite adoptado: fecha estrictamente posterior al instante actual, en America/Bogota. No había conexión local configurada antes de preparar el entorno y no se migraron datos del usuario.
- Protegido `PeliculaFuncionController::update`: relee y recalcula dentro de una transacción. Una comprobación temporal reprodujo primero la pérdida de cupos y luego verificó la corrección.
- Parcial `resources/views/boletos/_anular.blade.php` preparado. Andrés debe incluirlo con `@include('boletos._anular', ['boleto' => $boleto])` y cargar la relación funcion en su listado.
- Por instrucción expresa, no se integraron archivos de pruebas: AN-01 a AN-09 y el renderizado del parcial se comprobaron con archivos temporales fuera del repositorio. Lenin mantiene la responsabilidad de incorporar la automatización.
- Suite del repositorio intacta: 33 pruebas y 90 aserciones aprobadas.
- Concurrencia comprobada en MariaDB 11.8.8 mediante dos procesos independientes bloqueados simultáneamente: doble anulación deja 53 cupos; anulación y edición de capacidad a 70 dejan 63. No equivale a una certificación de MySQL de destino.
- Pendientes: venta simultánea AN-11 (VentaController aún 501), recorrido de interfaz AN-13 (listado aún 501), validación de MySQL de destino y automatización de Lenin.

Las tareas de creación de tests del plan original quedan fuera de esta implementación por la instrucción posterior del usuario. No se considera cerrado el flujo integrado hasta disponer del listado y las ventas del equipo.
