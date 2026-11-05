# Especificación de requisitos del sistema: CineTickets

**Asignatura:** Ingeniería de Software II
**Institución:** Corporación de Estudios Tecnológicos del Norte del Valle (Cotecnova)
**Periodo:** 2026-2 · **Docente:** Felipe Gutiérrez Isaza
**Proyecto:** Sistema de gestión y venta de entradas de cine (CineTickets)

## 1. Propósito y alcance

### 1.1 Propósito
Definir el alcance técnico, los requisitos funcionales y no funcionales y los criterios de aceptación del sistema CineTickets, para recorrer el ciclo de ingeniería completo: planificación, diseño, construcción incremental con Git, verificación con pruebas automatizadas, evaluación de calidad y mantenimiento.

### 1.2 Alcance
CineTickets es una aplicación web responsiva que gestiona la cartelera de un cine y la compra o reserva de boletos. Se limita a dos entidades con persistencia en base de datos relacional, con énfasis en la validación de entradas, el manejo de errores y la comprobabilidad de las reglas de negocio.

### 1.3 Tecnología
PHP con el framework **Laravel**, que implementa el patrón Modelo-Vista-Controlador (MVC). Base de datos MySQL, gestionada mediante **migraciones** de Laravel y modelos **Eloquent**. Pruebas con PHPUnit.

## 2. Modelo de datos

Relación uno a muchos (1:N): una función tiene muchos boletos.

### 2.1 Entidad `Pelicula_Funcion` (padre)
| Campo | Tipo | Restricciones |
| --- | --- | --- |
| id_funcion | INT | Clave primaria, autoincremental |
| titulo | VARCHAR(100) | No nulo |
| genero | VARCHAR(50) | No nulo |
| duracion_min | INT | No nulo, mayor que 0 |
| sala | VARCHAR(20) | No nulo |
| fecha_hora | DATETIME | No nulo |
| precio_unitario | DECIMAL(10,2) | No nulo, mayor que 0 |
| capacidad_total | INT | No nulo, mayor que 0 |
| asientos_disponibles | INT | No nulo, entre 0 y capacidad_total |
| estado | ENUM('Activa','Inactiva') | Por defecto 'Activa' |

### 2.2 Entidad `Boleto_Reserva` (hija)
| Campo | Tipo | Restricciones |
| --- | --- | --- |
| id_boleto | INT | Clave primaria, autoincremental |
| id_funcion | INT | Clave foránea a `Pelicula_Funcion` (ON DELETE RESTRICT) |
| nombre_cliente | VARCHAR(100) | No nulo |
| email_cliente | VARCHAR(100) | No nulo |
| cantidad_entradas | INT | No nulo, mayor que 0 |
| monto_total | DECIMAL(10,2) | No nulo |
| fecha_compra | TIMESTAMP | Por defecto la fecha y hora actual |
| estado | ENUM('Confirmado','Cancelado') | Por defecto 'Confirmado' |

## 3. Requisitos funcionales y criterios de aceptación

### RF-01: gestión de funciones / cartelera (CRUD de la entidad padre)
Permite a los administradores registrar, consultar, actualizar y desactivar funciones.
- **CA-01.1:** todos los campos del formulario de registro son obligatorios.
- **CA-01.2:** `capacidad_total` y `precio_unitario` deben ser estrictamente mayores que cero.
- **CA-01.3:** al crear una función, `asientos_disponibles` se inicializa igual a `capacidad_total`.
- **CA-01.4:** no se permite programar funciones con una `fecha_hora` anterior a la fecha y hora actual del servidor.
- **CA-01.5:** si una función tiene boletos registrados, no se permite su eliminación física, solo su inactivación lógica.

### RF-02: venta y emisión de boletos (CRUD de la entidad hija)
- **CA-02.1:** solo se pueden seleccionar funciones activas con `asientos_disponibles` mayor que 0.
- **CA-02.2:** `cantidad_entradas` debe ser un entero mayor que 0 y menor o igual que `asientos_disponibles`.
- **CA-02.3:** si la cantidad supera los asientos disponibles, se deniega la transacción y se informa el cupo máximo.
- **CA-02.4:** `monto_total = cantidad_entradas × precio_unitario`.
- **CA-02.5:** al confirmar la compra, una transacción descuenta la cantidad de `asientos_disponibles` de la función.

### RF-03: consulta y búsqueda de ventas
- **CA-03.1:** el sistema lista los boletos con id, cliente, correo, función, cantidad, monto total y estado, con filtro por cliente o por función.
- **CA-03.2:** se valida el formato del correo electrónico mediante expresión regular estándar antes de almacenar.

### RF-04: anulación de boletos
- **CA-04.1:** al anular un boleto Confirmado, su estado pasa a Cancelado.
- **CA-04.2:** la cantidad liberada se suma de nuevo a `asientos_disponibles` de la función.
- **CA-04.3:** un boleto ya cancelado no puede anularse otra vez.

## 4. Requisitos no funcionales

| ID | Categoría | Descripción | Verificación |
| --- | --- | --- | --- |
| RNF-01 | Arquitectura | Aplicación en PHP con el framework Laravel (MVC): controladores en `app/Http/Controllers`, modelos Eloquent en `app/Models` y vistas Blade en `resources/views`. | Revisión del código fuente del repositorio |
| RNF-02 | Seguridad del repositorio | Sin contraseñas, archivos `.env` ni bases de datos reales en Git. | Auditoría del historial de commits |
| RNF-03 | Comprobabilidad | Pruebas unitarias e integradas de la lógica de negocio (cálculo de montos, control de aforo), con PHPUnit. | Ejecución de `php artisan test` |
| RNF-04 | Control de versiones | Commits pequeños, frecuentes y descriptivos, con aportes de los integrantes. | Revisión de `git log` en GitHub |
| RNF-05 | Gestión del proyecto | Tablero de Trello con listas (Por hacer, En curso, En revisión, Hecho) y etiquetas por etapa. | Tarjetas vinculadas a los commits |
| RNF-06 | Mantenibilidad y calidad | Métricas, registro de defectos detectados y corregidos, y plan de mantenimiento. | Contenido de `docs/informe-calidad.md` |

## 5. Decisiones de diseño

| N.º | Decisión | Aplica a |
| --- | --- | --- |
| 1 | Se pueden editar solo `nombre_cliente` y `email_cliente` de un boleto Confirmado; el correo se vuelve a validar. No se cambia la cantidad ni la función, porque movería los asientos. | RF-02 (Seguimiento 2) |
| 2 | La nueva `capacidad_total` no puede ser menor que los asientos ya vendidos; los asientos disponibles se recalculan como capacidad menos vendidos y nunca son negativos. | RF-01 (implementada) |
| 3 | Un boleto solo se anula si está Confirmado y la función aún no ha ocurrido (aunque esté Inactiva). | RF-04 (Seguimiento 2) |
| 4 | La compra se hace dentro de una transacción con una actualización condicional de los asientos, para evitar la sobreventa cuando dos personas compran a la vez. | RF-02 (Seguimiento 2) |
| 5 | El correo se valida al guardar la venta (RF-02), cumpliendo CA-03.2. | RF-02 y RF-03 (Seguimiento 2) |
| 6 | Una función con cualquier boleto (confirmado o cancelado) no se elimina físicamente, solo se desactiva; una función sin boletos sí puede eliminarse. La base de datos lo respalda con `ON DELETE RESTRICT`. | RF-01 (implementada) |

## 6. Roles del equipo

| Rol | Integrante | GitHub | Responsabilidad principal |
| --- | --- | --- | --- |
| Líder técnico | Lisandro Villegas Henao | @LisandroVillegas | Alcance, arquitectura, modelo de datos e integración de cambios |
| Responsable de calidad | Jorge Alexander Henao Bueno | @henaoaa | Revisión de código, registro de defectos e informe de calidad |
| Responsable de pruebas | Lenin Aníbal Herrera Salgado | @lenin1125 | Plan de pruebas y automatización en `tests/` |
| Responsable de documentación | Andrés Felipe Cuéllar | @AndresFF22 | README, documentos, Trello y etiquetado de Git |

## 7. Estructura del repositorio

```
cinetickets/
├── README.md                  integrantes, roles, ejecución y enlace a Trello
├── docs/
│   ├── especificacion.md      requisitos, criterios de aceptación y modelo de datos
│   ├── plan-de-pruebas.md     casos de prueba y resultados de ejecución
│   └── informe-calidad.md     métricas, defectos y mantenimiento
├── app/                       controladores (Http/Controllers) y modelos (Models)
├── routes/                    rutas web
├── resources/views/           vistas Blade
├── database/migrations/       migraciones de las tablas
├── config/                    configuración del framework
└── tests/                     pruebas automatizadas (PHPUnit)
```
El código fuente de Laravel se ubica en la raíz del repositorio.

## 8. Plan de entregas

Cada entrega se marca con una etiqueta de Git en la rama principal:
- Seguimiento 1: `entrega-s1`
- Seguimiento 2 (lunes 9 de noviembre, 11:59 p. m.): `entrega-s2`
- Entrega final (miércoles 18 de noviembre, 11:59 p. m.): `entrega-final`
