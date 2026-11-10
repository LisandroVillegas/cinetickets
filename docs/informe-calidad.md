# Informe de calidad: CineTickets

**Etapa:** Seguimiento 1
**Rol responsable:** Responsable de calidad (Jorge Alexander Henao Bueno)
**Elaborado por:** (escribir el nombre de quien redacta este documento)

## 1. Alcance de la revisión

- Esquema de base de datos (migraciones) y modelos Eloquent.
- CRUD de funciones (RF-01): controlador, rutas y vistas.
- Configuración del proyecto (`config/database.php`).

## 2. Métricas

| Métrica | Valor | Observación |
| --- | --- | --- |
| Requisitos funcionales implementados | 1 de 4 (RF-01) | RF-02, RF-03 y RF-04 corresponden al Seguimiento 2 |
| Criterios de aceptación cubiertos por pruebas | CA-01.1 a CA-01.5 | Ver `docs/plan-de-pruebas.md` |
| Casos de prueba automatizados | (completar con la salida de `php artisan test`) | |
| Pruebas que pasan / fallan | (completar con la salida real) | |
| Defectos registrados | 3 | Los 3 corregidos |

## 3. Registro de defectos

| ID | Descripción | Ubicación | Severidad | Estado | Corrección |
| --- | --- | --- | --- | --- | --- |
| D-01 | Faltaba la vista base `layouts.app`, por lo que la cartelera lanzaba un error de vista no encontrada. | `resources/views/layouts/app.blade.php` | Alta | Corregido | Se creó el layout base con Bootstrap 5. |
| D-02 | Aviso de deprecación en PHP 8.5: `PDO::MYSQL_ATTR_SSL_CA` ya no se recomienda. | `config/database.php` (conexiones `mysql` y `mariadb`) | Baja | Corregido | Se usa `\Pdo\Mysql::ATTR_SSL_CA` en PHP 8.5 o superior y la constante anterior en versiones previas. |
| D-03 | Las restricciones `CHECK` de las migraciones usan `ALTER TABLE ... ADD CONSTRAINT`, que SQLite no soporta, y las pruebas con SQLite en memoria fallaban al crear las tablas. | `database/migrations/` | Media | Corregido | Las sentencias `CHECK` se ejecutan solo si el motor no es SQLite. |

## 4. Riesgos y observaciones

- En SQLite (usado por las pruebas) no se aplican las restricciones `CHECK`; las reglas se validan en el controlador y se prueban con PHPUnit.
- La zona horaria de la aplicación debe ser `America/Bogota`; con otra zona, la regla de fecha futura (CA-01.4) se desfasa.
- Falta cubrir con pruebas la venta, la anulación y el cálculo de montos (Seguimiento 2).

## 5. Plan de mantenimiento

| Acción | Tipo | Cuándo |
| --- | --- | --- |
| Corregir los defectos que aparezcan con las pruebas de RF-02 a RF-04 | Correctivo | Seguimiento 2 |
| Mantener la compatibilidad con versiones nuevas de PHP y de Laravel | Adaptativo | Continuo |
| Revisar el código antes de integrar cada rama y ampliar las pruebas | Preventivo | Continuo |
