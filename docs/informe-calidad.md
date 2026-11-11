# Informe de calidad: CineTickets

**Fecha de revisión:** 2026-11-10
**Código revisado:** commit `24acf39`
**Responsable del equipo:** Jorge Alexander Henao Bueno
**Preparación:** borrador actualizado con asistencia de Codex; pendiente de revisión del responsable.
**Etapa:** revisión de avance hacia la entrega final.

## 1. Alcance y método

Se inspeccionaron controladores, rutas, modelos, migraciones, configuración, vistas disponibles y pruebas. No se modificó código de aplicación. No se ejecutaron pruebas ni la aplicación porque esta copia no tiene `vendor/autoload.php`. No se auditó todo el historial de secretos, Trello, Classroom ni el despliegue.

## 2. Métricas y evidencia

| Métrica | Resultado | Alcance |
| --- | --- | --- |
| Requisitos con implementación funcional visible | 1 de 4: RF-01 | Revisión estática, no certifica funcionamiento en ejecución |
| Requisitos pendientes | RF-02, RF-03 y RF-04 | Controladores existentes con `abort(501, ...)` |
| Archivos de pruebas de funcionalidades existentes | 2 | CRUD de funciones y relación función-boleto |
| Prueba unitaria de ejemplo | 1 archivo | No mide reglas de negocio |
| Pruebas ejecutadas en esta revisión | Ninguna | Resultados actuales no determinados |
| Resultado histórico documentado | 33 aprobadas, 0 fallidas, 2026-11-05 | Reportado en el plan de pruebas; no reproducido aquí |

No se presenta un porcentaje de cobertura: no hay medición de cobertura ejecutada.

## 3. Registro de defectos

| ID | Descripción | Ubicación | Severidad | Estado | Corrección |
| --- | --- | --- | --- | --- | --- |
| D-01 | Faltaba la vista base `layouts.app`, por lo que la cartelera lanzaba un error de vista no encontrada. | `resources/views/layouts/app.blade.php` | Alta | Corrección registrada; pendiente de reverificación | Se creó el layout base con Bootstrap 5. |
| D-02 | Aviso de deprecación en PHP 8.5: `PDO::MYSQL_ATTR_SSL_CA` ya no se recomienda. | `config/database.php` (conexión `mysql` en la copia revisada) | Baja | Corrección registrada; pendiente de reverificación | Se usa `\Pdo\Mysql::ATTR_SSL_CA` en PHP 8.5 o superior y la constante anterior en versiones previas. |
| D-03 | Las restricciones `CHECK` de las migraciones usan `ALTER TABLE ... ADD CONSTRAINT`, que SQLite no soporta, y las pruebas con SQLite en memoria fallaban al crear las tablas. | `database/migrations/` | Media | Corrección registrada; pendiente de reverificación | Las sentencias `CHECK` se ejecutan solo si el motor no es SQLite. |

## 4. Hallazgos actuales

| ID | Hallazgo y ubicación | Impacto | Estado y verificación necesaria |
| --- | --- | --- | --- |
| H-01 | VentaController, BoletoController y AnulacionController contienen respuestas 501 | Impide completar el flujo de boletos | Pendiente de implementación; verificar cada requisito con pruebas |
| H-02 | `config/app.php:73` fija UTC | Puede desplazar la interpretación de fechas locales | Discrepancia confirmada en código; cambiar a America/Bogota y probar límites de hora |
| H-03 | `PeliculaFuncionController::update` calcula y guarda disponibilidad sin transacción ni bloqueo | Riesgo de sobrescribir cambios de una venta o anulación simultánea | Riesgo estático, no reproducido; coordinar protección con el líder técnico y probar en MySQL |
| H-04 | No existen vistas de boletos ni ventas | No hay interfaz para los nuevos flujos | Pendiente de Andrés y Lisandro; integrar el botón de anulación cuando exista el listado |

Para cada defecto nuevo registrar: fecha, entorno, pasos, resultado esperado y real, severidad, responsable, estado, commit correctivo y prueba de regresión. Un hallazgo estático o un riesgo no equivale a un fallo reproducido.

## 5. Riesgos y límites

- SQLite omite los CHECK definidos para MySQL; no demuestra el comportamiento de bloqueo de MySQL.
- La doble anulación debe devolver cupos una sola vez, incluso con solicitudes concurrentes.
- La integración depende de que venta, edición de capacidad y anulación coordinen las escrituras sobre la misma función.
- El layout depende del CDN de Bootstrap para su presentación.
- Las correcciones históricas requieren nueva ejecución antes de declararlas verificadas en esta versión.

## 6. Plan de mantenimiento y cierre

| Acción | Tipo | Responsable | Momento / evidencia |
| --- | --- | --- | --- |
| Completar venta, consulta y anulación | Desarrollo pendiente | Lisandro, Andrés y Alexander | Antes de cerrar la entrega; pruebas por requisito |
| Corregir zona horaria | Correctivo | Alexander, coordinado con Lisandro | Pruebas con reloj fijo y límites temporales |
| Coordinar escrituras de cupos y edición de capacidad | Preventivo | Lisandro y Alexander | Ensayo concurrente en MySQL |
| Ampliar y ejecutar regresión | Preventivo | Lenin y autores de cada módulo | Salida real con fecha, entorno y commit |
| Revisar compatibilidad PHP/Laravel antes de actualizar | Adaptativo | Lisandro | Entorno de pruebas y suite completa |
| Completar métricas y enlazar evidencias | Calidad | Alexander | Después de ejecutar pruebas; sin inventar resultados |

El informe se cerrará al registrar los resultados reales, defectos pendientes y limitaciones aceptadas. La existencia de este documento no implica que el producto esté terminado.
