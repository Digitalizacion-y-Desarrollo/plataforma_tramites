# AGENTS.md

## Proyecto

Este repositorio contiene el **Portal de Trámites del Municipio de Nezahualcóyotl**.

El objetivo de la plataforma es permitir que ciudadanos realicen y consulten trámites municipales de forma digital, y que servidores públicos puedan revisar, validar, aprobar o rechazar solicitudes desde un portal administrativo.

El MVP corresponde al área de **Desarrollo Urbano**.

Trámites iniciales:

- Constancia de regularización.
- Constancia de terminación de obra.
- Constancia de cambio o régimen en condominio.
- Constancia de suspensión de obra.

Este archivo contiene reglas obligatorias para cualquier agente o asistente de programación que modifique este repositorio.

---

# 1. Stack obligatorio

El proyecto utiliza exclusivamente:

- PHP 8.2.
- Laravel 12.
- MySQL 8+.
- Blade como motor de vistas.
- HTML.
- CSS.
- JavaScript nativo cuando sea necesario.
- Arquitectura MVC.

No sustituir estas tecnologías sin autorización explícita.

---

# 2. Arquitectura

La arquitectura del proyecto será **MVC tradicional de Laravel**.

Debe mantenerse la separación:

```text
Route
  ↓
Controller
  ↓
Model
  ↓
Database

Controller
  ↓
Blade View
```

Responsabilidades:

### Model

Representa entidades y relaciones de la base de datos.

Debe contener:

- Relaciones Eloquent.
- Casts.
- Scopes cuando realmente sean útiles.
- Reglas simples relacionadas directamente con el modelo.

No convertir los Models en clases gigantes con lógica de interfaz o lógica HTTP.

### Controller

Coordina una petición.

Puede:

- Recibir requests.
- Validar datos.
- Consultar Models.
- Ejecutar operaciones necesarias.
- Preparar datos para Blade.
- Retornar vistas.
- Redireccionar.
- Generar respuestas JSON únicamente cuando una funcionalidad lo requiera.

Los Controllers deben mantenerse legibles y con métodos enfocados.

### View

Todas las interfaces HTML deben construirse con **Blade**.

Ubicación:

```text
resources/views/
```

Blade es el único motor de vistas autorizado.

---

# 3. Blade es obligatorio

El frontend será renderizado por Laravel usando Blade.

Utilizar:

```php
@extends(...)
@section(...)
@yield(...)
@include(...)
@foreach(...)
@for(...)
@if(...)
@csrf
@method(...)
@error(...)
@old(...)
```

Se permiten layouts Blade y partials mediante:

```php
@include(...)
```

Ejemplo:

```text
resources/views/
├── layouts/
│   ├── ciudadano.blade.php
│   └── admin.blade.php
│
├── partials/
│   ├── alerts.blade.php
│   ├── navbar.blade.php
│   └── footer.blade.php
│
├── tramites/
├── solicitudes/
└── admin/
```

---

# 4. Componentes prohibidos

NO utilizar Blade Components.

No crear:

```text
resources/views/components/
```

No utilizar:

```html
<x-layout>
<x-button>
<x-input>
<x-alert>
```

No utilizar:

```php
php artisan make:component
```

Si existe UI repetida, utilizar:

```php
@include('partials.nombre')
```

en lugar de componentes Blade.

---

# 5. Frameworks frontend prohibidos

No introducir:

- Livewire.
- Inertia.
- React.
- Vue.
- Angular.
- Svelte.
- Alpine.js.
- HTMX.
- Stimulus.
- Next.js.
- Nuxt.
- SPA frameworks.
- Sistemas de componentes frontend.

No convertir el proyecto en SPA.

La navegación principal será tradicional:

```text
Browser
   ↓
Laravel Route
   ↓
Controller
   ↓
Blade
   ↓
HTML
```

Si una interacción necesita JavaScript, usar **JavaScript nativo**.

---

# 6. No introducir arquitecturas adicionales

La arquitectura oficial es MVC.

No introducir por iniciativa propia:

- Repository Pattern.
- Service Layer generalizada.
- CQRS.
- Clean Architecture.
- Hexagonal Architecture.
- DDD.
- Use Cases.
- Actions como arquitectura completa.
- Command Bus.
- Event Sourcing.
- DTO layers innecesarias.

No crear carpetas como:

```text
Repositories/
UseCases/
Actions/
Domain/
Application/
Infrastructure/
```

salvo que una tarea futura lo solicite explícitamente.

Para integraciones externas concretas puede existir una clase especializada si realmente ayuda a encapsular comunicación HTTP, pero esto no debe convertirse en una arquitectura paralela al MVC.

Ejemplo válido:

```text
app/Integrations/AccesosClient.php
```

Solo si es necesario para encapsular llamadas al sistema externo Accesos.

---

# 7. Base de datos

Motor:

```text
MySQL 8+
```

No escribir código específico de PostgreSQL.

No utilizar:

```text
jsonb
```

Utilizar:

```text
json
```

cuando sea necesario.

Las entidades principales utilizarán UUID.

Preferir:

```php
$table->uuid('id')->primary();
```

y:

```php
$table->foreignUuid(...)
```

para relaciones internas.

---

# 8. Migraciones

Las modificaciones del esquema deben realizarse mediante migraciones Laravel.

Nunca modificar directamente la base de datos sin migración.

Las migraciones deben:

- Tener nombres claros.
- Tener foreign keys.
- Tener índices adecuados.
- Tener métodos `up()` y `down()` válidos.
- Respetar el orden de dependencias.
- Ser compatibles con MySQL 8+.
- Preservar integridad histórica.

No editar migraciones antiguas que ya puedan haber sido utilizadas en otros ambientes salvo instrucción explícita.

Para cambios posteriores crear una nueva migración.

---

# 9. Estados y catálogos

No utilizar ENUM nativo de MySQL.

No usar:

```php
$table->enum(...)
```

Los estados principales se administrarán mediante tablas catálogo.

Catálogos iniciales:

```text
solicitud_estados
formulario_estados
documento_estados
notificacion_estados
```

Los valores iniciales deben generarse mediante seeders.

---

# 10. Seeders

Los catálogos deberán tener Seeders.

Seeders iniciales:

```text
SolicitudEstadoSeeder
FormularioEstadoSeeder
DocumentoEstadoSeeder
NotificacionEstadoSeeder
TramiteSeeder
```

Los seeders deben ser idempotentes.

Ejecutar múltiples veces:

```bash
php artisan db:seed
```

no debe generar registros duplicados.

Preferir claves estables como:

```text
BORRADOR
ENVIADA
APROBADA
RECHAZADA
```

para localizar registros existentes antes de crear nuevos.

---

# 11. Ciudadanos

Los ciudadanos **NO tendrán cuentas de usuario**.

No crear:

```text
users ciudadanos
passwords ciudadanos
profiles ciudadanos
```

No implementar registro tradicional.

La CURP será el identificador utilizado para relacionar solicitudes de un ciudadano.

Las solicitudes almacenarán como mínimo:

```text
curp_consulta
email_contacto
```

La CURP no debe considerarse una credencial secreta.

---

# 12. Consulta de trámites

El ciudadano podrá consultar trámites mediante:

```text
CURP
  +
token temporal enviado por correo
```

Flujo:

```text
Ciudadano introduce CURP
        ↓
Sistema identifica correo asociado
        ↓
Genera token temporal
        ↓
Envía token por correo
        ↓
Ciudadano introduce token
        ↓
Sistema valida
        ↓
Muestra solicitudes asociadas a CURP
```

Nunca almacenar el token original.

Guardar únicamente:

```text
token_hash
```

El token debe:

- Expirar.
- Tener límite de intentos.
- Poder marcarse como utilizado.

---

# 13. Servidores públicos

Los servidores públicos NO se autentican contra esta base de datos.

La autenticación se realiza mediante un sistema externo llamado:

```text
Accesos
```

Documentación:

```text
https://accesos.digitalneza.com/api-docs
```

No crear:

```text
password
password_hash
remember_token
```

para servidores públicos.

No crear una tabla local de autenticación de servidores públicos.

---

# 14. Integración con Accesos

Accesos es responsable de:

- Autenticación.
- Contraseñas.
- Recuperación de contraseña.
- Usuarios.
- Roles.
- Permisos.
- Departamento.
- Identidad del servidor público.

El Portal de Trámites consume esa información.

Cuando se necesite registrar quién realizó una acción, guardar snapshots como:

```text
actor_accesos_id
actor_nombre_snapshot
actor_departamento_snapshot
```

No crear foreign keys hacia Accesos.

Accesos es un sistema externo.

---

# 15. Autorización administrativa

Los permisos administrativos serán proporcionados por Accesos.

Ejemplos conceptuales:

```text
solicitudes.ver
solicitudes.validar
solicitudes.aprobar
solicitudes.rechazar

formularios.ver
formularios.editar
formularios.publicar
```

No crear un segundo sistema local de roles y permisos salvo instrucción explícita.

Siempre verificar permisos en backend.

Nunca confiar únicamente en ocultar botones en Blade.

---

# 16. Formularios dinámicos

Los formularios de trámites son dinámicos.

Un servidor público autorizado podrá definir:

- Secciones.
- Campos.
- Etiquetas.
- Tipos de campo.
- Campos obligatorios.
- Orden.
- Opciones.
- Condiciones.
- Documentos requeridos.
- Configuraciones.
- Versiones.

El frontend ciudadano debe interpretar la configuración almacenada y generar el formulario utilizando Blade.

---

# 17. Versionado de formularios

Los formularios deben estar versionados.

Conceptualmente:

```text
Formulario
    ↓
Versión 1
Versión 2
Versión 3
```

Estados:

```text
BORRADOR
PUBLICADO
ARCHIVADO
```

Una versión publicada que tenga solicitudes relacionadas debe considerarse histórica.

NO modificar una versión publicada utilizada por solicitudes.

Para hacer cambios:

```text
Versión publicada
      ↓
crear nueva versión
      ↓
BORRADOR
      ↓
editar
      ↓
PUBLICAR
```

Cada solicitud debe conservar referencia a:

```text
formulario_version_id
```

Esto permite saber exactamente qué formulario llenó el ciudadano.

---

# 18. Motor de formularios

Entidades principales:

```text
tramites

formularios
formulario_versiones
tramite_formulario

formulario_secciones
formulario_campos
campo_opciones
campo_condiciones

requisito_documentos
```

No crear tablas rígidas para cada bloque del formulario como:

```text
persona_fisica
persona_moral
predio
dro
propiedad
```

si esos datos pueden representarse mediante el motor dinámico.

Las respuestas se almacenarán mediante:

```text
solicitud_respuestas
```

---

# 19. Campos dinámicos

Tipos inicialmente soportados:

```text
TEXT
TEXTAREA
NUMBER
EMAIL
PHONE
DATE
SELECT
MULTISELECT
RADIO
CHECKBOX
CURP
RFC
CP
ADDRESS
COORDINATES
```

La configuración adicional del campo puede guardarse como JSON.

Ejemplo:

```json
{
    "placeholder": "Ingresa tu nombre",
    "minLength": 2,
    "maxLength": 100
}
```

---

# 20. Condiciones dinámicas

Los formularios pueden contener campos condicionales.

Ejemplo:

```text
SI tipo_persona = MORAL
MOSTRAR razon_social
```

Operadores iniciales:

```text
EQUALS
NOT_EQUALS
CONTAINS
GREATER_THAN
LESS_THAN
IS_EMPTY
IS_NOT_EMPTY
```

Acciones:

```text
SHOW
HIDE
REQUIRE
OPTIONAL
```

La validación de estas reglas debe ejecutarse también en backend.

No confiar exclusivamente en JavaScript.

---

# 21. Solicitudes

La entidad principal operativa es:

```text
solicitudes
```

Debe contener información estructural como:

```text
id
folio
tramite_id
formulario_version_id
solicitud_estado_id
curp_consulta
email_contacto
fecha_envio
fecha_terminacion
timestamps
```

Las respuestas dinámicas estarán en:

```text
solicitud_respuestas
```

No crear cientos de columnas dentro de `solicitudes`.

---

# 22. Folios

Cada solicitud debe tener un folio único.

El folio no debe utilizarse como primary key.

Debe existir:

```text
id UUID
folio UNIQUE
```

El mecanismo exacto de generación del folio deberá estar centralizado y evitar colisiones.

---

# 23. Documentos

Los archivos enviados por ciudadanos NO deben guardarse como binarios dentro de MySQL.

La base únicamente almacena metadata como:

```text
nombre_original
storage_key
mime_type
tamano_bytes
hash_archivo
estado_revision
```

Los archivos deben almacenarse mediante el sistema de Storage de Laravel.

No exponer rutas internas directamente al ciudadano.

---

# 24. Seguridad de archivos

Para documentos cargados:

- Validar MIME type.
- Validar extensión.
- Validar tamaño.
- Generar nombres internos seguros.
- No confiar en el nombre original.
- No ejecutar archivos cargados.
- No almacenar uploads dentro de ubicaciones ejecutables.
- Controlar autorización antes de descargar documentos administrativos.

---

# 25. Validación

Toda entrada del usuario debe validarse en backend.

Esto incluye:

- Formularios ciudadanos.
- Formularios administrativos.
- Parámetros de rutas.
- Archivos.
- CURP.
- Correo.
- Tokens.
- Estados.
- Configuración de formularios.

Las validaciones JavaScript son únicamente una mejora de UX.

Nunca sustituyen la validación Laravel.

---

# 26. Mass assignment

Configurar correctamente:

```php
$fillable
```

o la estrategia definida por el proyecto.

Nunca utilizar masivamente datos provenientes del request sin controlar qué campos pueden asignarse.

Evitar:

```php
Model::create($request->all());
```

Preferir datos previamente validados.

---

# 27. Queries

Preferir Eloquent y Query Builder.

Evitar SQL crudo salvo que exista una razón técnica clara.

Prevenir N+1 utilizando:

```php
with(...)
load(...)
```

cuando corresponda.

No cargar relaciones innecesarias.

Para listados administrativos utilizar paginación.

Ejemplo:

```php
->paginate(20)
```

en lugar de cargar miles de registros.

---

# 28. Rutas

Separar claramente rutas ciudadanas y administrativas.

Ejemplo conceptual:

```php
Route::get('/', ...);

Route::prefix('tramites')->group(...);

Route::prefix('consulta')->group(...);

Route::prefix('admin')->group(...);
```

El portal administrativo debe estar protegido por autenticación/integración con Accesos.

Utilizar nombres de rutas.

Ejemplo:

```php
->name('solicitudes.show');
```

En Blade utilizar:

```php
route('solicitudes.show', $solicitud)
```

y evitar URLs escritas manualmente.

---

# 29. Route Model Binding

Utilizar Route Model Binding cuando simplifique el código y no genere riesgos de autorización.

Siempre verificar que el servidor público tenga autorización para acceder al recurso solicitado.

Conocer un UUID no debe implicar automáticamente tener acceso al recurso.

---

# 30. CSRF

Todos los formularios POST, PUT, PATCH y DELETE renderizados con Blade deben utilizar:

```php
@csrf
```

Cuando corresponda:

```php
@method('PUT')
@method('PATCH')
@method('DELETE')
```

No deshabilitar CSRF para solucionar errores.

---

# 31. Blade y seguridad

Blade escapa automáticamente:

```php
{{ $variable }}
```

Preferir esta sintaxis.

No utilizar:

```php
{!! $variable !!}
```

con información proveniente del ciudadano o de fuentes no confiables.

Solo utilizar HTML sin escapar cuando exista una razón clara y el contenido haya sido sanitizado.

---

# 32. JavaScript

JavaScript debe ser mínimo y progresivo.

Utilizar JavaScript nativo para:

- Campos condicionales.
- Mostrar u ocultar secciones.
- Vista previa.
- Confirmaciones.
- Interacciones pequeñas.
- Peticiones AJAX puntuales si son realmente necesarias.

No construir una SPA.

No replicar toda la lógica del backend en JavaScript.

La lógica de negocio definitiva pertenece al backend.

---

# 33. CSS

Mantener estilos organizados.

No introducir un framework CSS nuevo sin autorización.

Si el proyecto ya utiliza una librería CSS, continuar con ella.

No cambiar el sistema visual completo como parte de tareas que no estén relacionadas con diseño.

---

# 34. Auditoría

Acciones sensibles deben quedar registradas.

Ejemplos:

```text
FORMULARIO_CREADO
FORMULARIO_PUBLICADO
FORMULARIO_ARCHIVADO

SOLICITUD_APROBADA
SOLICITUD_RECHAZADA

DOCUMENTO_VALIDADO
DOCUMENTO_RECHAZADO
```

La tabla de auditoría debe conservar:

```text
actor_accesos_id
actor_nombre_snapshot
accion
entidad
entidad_id
datos_anteriores
datos_nuevos
ip
created_at
```

Los registros de auditoría son inmutables.

---

# 35. Historial de solicitudes

Cada cambio de estado debe generar un registro en:

```text
solicitud_historial_estados
```

Debe conservar:

```text
estado_anterior_id
estado_nuevo_id
comentario
actor_tipo
actor_accesos_id
actor_nombre_snapshot
actor_departamento_snapshot
fecha
```

Nunca reemplazar el historial actualizando registros anteriores.

Siempre agregar un nuevo evento.

---

# 36. Estados de solicitud

Inicialmente:

```text
BORRADOR
ENVIADA
APROBADA
RECHAZADA
```

Estos estados provienen del catálogo:

```text
solicitud_estados
```

No insertar strings arbitrarios directamente en solicitudes.

---

# 37. Correos

Cada cambio relevante de estado podrá generar correo al ciudadano.

Registrar intentos de envío en:

```text
notificacion_emails
```

Tipos iniciales:

```text
SOLICITUD_ENVIADA
SOLICITUD_APROBADA
SOLICITUD_RECHAZADA
TOKEN_CONSULTA
```

El fallo de un correo no debe provocar pérdida del cambio de estado de la solicitud.

---

# 38. Transacciones

Utilizar transacciones de base de datos cuando una operación modifique varias entidades que deban mantenerse consistentes.

Ejemplos:

```text
Enviar solicitud
+
guardar respuestas
+
guardar historial
```

o:

```text
Aprobar solicitud
+
actualizar estado
+
crear historial
+
crear registro de notificación
```

Utilizar:

```php
DB::transaction(function () {
    //
});
```

cuando corresponda.

---

# 39. Integridad histórica

Una solicitud debe seguir siendo reproducible en el futuro.

Por lo tanto:

- No modificar versiones de formularios utilizadas.
- No eliminar campos que tengan respuestas.
- No eliminar requisitos que tengan documentos relacionados.
- No eliminar estados históricos.
- No cambiar retroactivamente respuestas de solicitudes finalizadas sin mecanismo explícito de auditoría.

---

# 40. Eliminaciones

No utilizar `cascadeOnDelete()` indiscriminadamente.

Utilizar cascade únicamente cuando la entidad hija no tenga significado independiente.

Utilizar restricciones cuando borrar información dañaría un expediente histórico.

Ejemplo:

Una respuesta puede depender de una solicitud.

Pero un campo utilizado por respuestas históricas no debe desaparecer.

---

# 41. Soft Deletes

No agregar:

```php
softDeletes()
```

por defecto.

Solo utilizar Soft Deletes cuando exista una necesidad funcional concreta y haya sido solicitada.

---

# 42. Nomenclatura

Código PHP:

```text
PascalCase para clases
camelCase para métodos y variables
```

Base de datos:

```text
snake_case
```

Nombres de clases deben describir claramente su responsabilidad.

Ejemplos:

```text
Solicitud
Tramite
Formulario
FormularioVersion
FormularioCampo
SolicitudRespuesta
SolicitudDocumento
```

Controllers:

```text
SolicitudController
TramiteController
FormularioController
ConsultaTramiteController
```

Evitar nombres genéricos como:

```text
Manager
Helper
Util
Processor
Handler
```

sin una responsabilidad claramente definida.

---

# 43. Controllers RESTful

Cuando sea posible utilizar métodos convencionales:

```text
index
create
store
show
edit
update
destroy
```

Agregar métodos adicionales únicamente cuando representen una acción clara.

Ejemplos permitidos:

```text
aprobar
rechazar
publicar
archivar
```

No crear controllers gigantes que administren múltiples dominios.

---

# 44. Código reutilizable

Antes de duplicar lógica:

1. Revisar si ya existe.
2. Extraer métodos privados cuando sea suficiente.
3. Utilizar scopes o métodos del Model cuando correspondan.
4. Crear una clase de integración solo cuando la responsabilidad realmente lo amerite.

No crear abstracciones prematuras.

---

# 45. Pruebas

Las funcionalidades críticas deben ser comprobables mediante tests Laravel.

Priorizar Feature Tests para:

- Creación de solicitudes.
- Validación de campos dinámicos.
- Consulta mediante CURP/token.
- Cambio de estados.
- Autorización administrativa.
- Publicación de formularios.
- Integridad de versiones.
- Carga de documentos.

No eliminar tests existentes para hacer que la suite pase.

Corregir el problema real.

---

# 46. Antes de modificar código

Antes de implementar una tarea:

1. Revisar estructura existente.
2. Revisar rutas.
3. Revisar migraciones.
4. Revisar Models relacionados.
5. Revisar Controllers existentes.
6. Revisar vistas Blade relacionadas.
7. Revisar tests existentes.

No asumir que una clase o tabla no existe sin verificarlo.

---

# 47. Cambios mínimos

Al implementar una funcionalidad:

- Modificar solamente lo necesario.
- No refactorizar áreas no relacionadas.
- No cambiar estilos globales innecesariamente.
- No renombrar clases sin motivo.
- No introducir dependencias nuevas si Laravel ya resuelve el problema.

---

# 48. Dependencias Composer

No instalar paquetes Composer sin necesidad clara.

Antes de agregar una dependencia comprobar si Laravel ya proporciona la funcionalidad.

No agregar paquetes por conveniencia para funcionalidades triviales.

Cualquier dependencia nueva debe tener una justificación técnica.

---

# 49. Dependencias frontend

No instalar paquetes npm para reemplazar funcionalidades que puedan resolverse razonablemente con Blade, CSS o JavaScript nativo.

No introducir un pipeline frontend complejo innecesariamente.

---

# 50. Artisan

Preferir comandos Artisan cuando correspondan.

Ejemplos:

```bash
php artisan make:model
php artisan make:controller
php artisan make:migration
php artisan make:seeder
php artisan make:test
```

Pero revisar siempre el resultado generado antes de darlo por terminado.

---

# 51. Formato y calidad

El código debe:

- Ser legible.
- Mantener responsabilidades claras.
- Evitar duplicación evidente.
- Usar nombres descriptivos.
- Mantener métodos razonablemente pequeños.
- Respetar PSR-12.
- Seguir las convenciones de Laravel 12.

Ejecutar el formatter disponible en el proyecto cuando corresponda.

Si Laravel Pint está instalado:

```bash
./vendor/bin/pint
```

---

# 52. No inventar requerimientos

Si una tarea no especifica:

- nuevos estados,
- nuevos roles,
- nuevas tablas,
- nuevos campos,
- reglas administrativas,
- procesos adicionales,

no inventarlos.

Implementar exactamente el alcance solicitado y mantener compatibilidad con la arquitectura existente.

---

# 53. Prioridades del proyecto

Al tomar decisiones técnicas priorizar, en este orden:

1. Integridad de los expedientes.
2. Seguridad de información ciudadana.
3. Trazabilidad.
4. Simplicidad.
5. Mantenibilidad.
6. Convenciones Laravel.
7. Rendimiento.
8. Experiencia de usuario.

No sacrificar integridad histórica por simplificar una implementación.

---

# 54. Regla principal de frontend

Si Codex considera utilizar:

- React,
- Vue,
- Livewire,
- Alpine,
- Inertia,
- Blade Components,

la respuesta es **NO**.

Implementar la funcionalidad mediante:

```text
Laravel Controller
+
Blade
+
HTML
+
CSS
+
JavaScript nativo cuando sea necesario
```

---

# 55. Regla principal de arquitectura

Si Codex considera crear:

```text
Repository
Service
UseCase
Action
Command
DTO
Domain Layer
Infrastructure Layer
```

debe detenerse y comprobar si realmente es necesario.

La arquitectura oficial de este proyecto es:

```text
MVC
```

La solución predeterminada siempre debe buscar resolverse mediante:

```text
Route
Controller
Model
Blade
```

antes de introducir nuevas capas.

---

# 56. Definición de terminado

Una tarea no se considera terminada únicamente porque el código compile.

Antes de finalizar:

1. Verificar que el código respete este `AGENTS.md`.
2. Ejecutar las migraciones necesarias.
3. Ejecutar seeders cuando corresponda.
4. Ejecutar tests relacionados.
5. Verificar errores de sintaxis.
6. Revisar foreign keys.
7. Revisar autorización.
8. Revisar validación backend.
9. Verificar manualmente las vistas Blade afectadas cuando sea posible.
10. Informar claramente qué archivos fueron creados o modificados.

Si algo no pudo comprobarse, indicarlo explícitamente.

---

# 58. Arquitectura de diseño y Tailwind CSS

Tailwind CSS v4 es el sistema de estilos obligatorio para toda interfaz nueva o modificada.

- Usar utilidades Tailwind directamente en las vistas Blade.
- Cargar los recursos mediante `@vite(['resources/css/app.css', 'resources/js/app.js'])`.
- No añadir estilos inline ni bloques `<style>` nuevos; al tocar una vista existente, migrar los estilos afectados a utilidades Tailwind.
- Definir tokens globales únicamente en `resources/css/app.css` mediante `@theme`; reutilizar `neza-wine`, `neza-wine-soft`, `neza-ink`, `neza-muted`, `neza-line` y `neza-canvas`.
- Usar `font-sans` (DM Sans) como tipografía base.
- Mantener layouts en `resources/views/layouts/`, UI repetida en `resources/views/partials/` y pantallas por dominio en sus carpetas Blade. No usar Blade Components.
- Mantener la jerarquía: layout compartido (sidebar/topbar), contenido de pantalla y estados vacíos, carga o error dentro de cada pantalla.
- Reutilizar tokens antes de crear valores arbitrarios y aplicar variantes responsivas (`sm:`, `md:`, `lg:`).
- Conservar HTML semántico, foco visible y contraste suficiente.
- No instalar bibliotecas CSS o de componentes adicionales sin autorización explícita.
- Usar Heroicons (https://heroicons.com/) para iconografía. Preferir SVGs Heroicons `outline` de 24 px, con `aria-hidden="true"` cuando sean decorativos y texto accesible en botones que no tengan etiqueta visible.
- No usar emojis, caracteres Unicode ni iconos de otras bibliotecas como sustitutos de iconos de interfaz.

---

# 57. Resumen obligatorio para agentes

Este proyecto utiliza:

```text
Laravel 12
PHP 8.2
MySQL 8+
MVC
Blade
HTML
CSS
JavaScript nativo
```

No utiliza:

```text
React
Vue
Livewire
Inertia
Alpine
Blade Components
SPA
Repository Pattern
Clean Architecture
CQRS
DDD
```

Ciudadanos:

```text
sin cuentas
CURP + correo
token temporal para consulta
```

Servidores públicos:

```text
autenticación externa mediante Accesos
```

Formularios:

```text
dinámicos
versionados
configurables desde portal administrativo
```

Principio fundamental:

> Mantener el sistema simple, trazable y alineado con Laravel MVC tradicional. No introducir tecnologías, patrones o capas que no hayan sido solicitadas.

---

# 59. Formularios CRUD y alertas

Para toda funcionalidad CRUD nueva o modificada:

- Los formularios que creen, actualicen o eliminen registros deben bloquear sus botones de envío al enviarse y mostrar un indicador de carga para evitar solicitudes duplicadas.
- Los errores de validación deben mostrarse debajo del input, select o textarea correspondiente, con texto rojo y usando `@error`.
- Usar SweetAlert2 para confirmaciones y alertas de éxito, error o consulta; las confirmaciones de acciones destructivas deben requerir aceptación explícita.
- Las eliminaciones deben ser lógicas mediante `SoftDeletes` únicamente cuando el requerimiento lo solicite expresamente. No eliminar de manera permanente expedientes ni información histórica.
