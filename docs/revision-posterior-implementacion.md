# Revisión posterior a la implementación

Fecha: 2026-10-27

## Cambios completados

### Funcionalidad

- Se incorporó el ciclo de vida de versiones de formulario: crear una nueva versión, publicar y archivar.
- Al publicar una versión se archivan las versiones publicadas anteriores, se conservan sus datos históricos y se actualizan los vínculos activos con trámites.
- Se preserva el historial de `tramite_formulario`: la nueva migración elimina la unicidad exclusiva por trámite y la sustituye por una unicidad por trámite y versión.
- Se agregaron rutas y controles administrativos para publicar y archivar trámites.
- Los trámites nuevos inician en `BORRADOR`; el catálogo público requiere que el trámite esté habilitado y publicado.
- La versión pública de un formulario también se valida al resolver un trámite ciudadano.
- El envío ciudadano incluye el identificador de la versión mostrada. Si la versión cambió durante el llenado, se rechaza el envío y se solicita recargar la pantalla.
- La consulta por token puede realizarse desde otro dispositivo introduciendo CURP, correo y código. El código mantiene expiración, límite de intentos y consumo único.
- Las fichas de pago vencidas ya no pueden descargarse.
- Las instrucciones registradas al aprobar una solicitud se muestran al ciudadano.

### UX ciudadana

- Se añadió aviso de privacidad público, enlace en el pie de página y aceptación obligatoria antes de enviar una solicitud.
- Se guarda la fecha de aceptación de privacidad en la solicitud.
- Se mejoraron los mensajes del flujo de token, incluido el caso de consulta desde otro dispositivo.
- La pantalla de pago oculta la descarga de una ficha vencida y muestra una indicación clara de seguimiento.

### UX administrativa

- El listado de formularios ya usa filtros reales por búsqueda, estado, trámite y departamento.
- Se reemplazaron contadores, actividad reciente y tipos de campo simulados por datos reales.
- El editor de formularios muestra el estado de la versión y las acciones disponibles según su ciclo de vida.
- El catálogo de trámites muestra por separado estado de publicación y habilitación, además de acciones de publicar/archivar.
- El listado de solicitudes incorpora tarjetas de resumen, estados completos y filtros con etiquetas visibles.
- La búsqueda de la barra superior ahora consulta solicitudes por folio, CURP o correo.

### UI y accesibilidad

- Se agregó título administrativo dependiente de la ruta actual.
- Se sustituyó el control de búsqueda decorativo por un formulario accesible.
- Se agregaron etiquetas visibles a filtros relevantes y nombres accesibles a acciones de icono.
- Se corrigió el token CSRF mal escrito en la consulta ciudadana.

### Calidad técnica e integridad

- Se agregaron migraciones para clasificar estados históricos de publicación, registrar aceptación de privacidad y preservar vínculos históricos de formularios.
- Se limitaron las operaciones sobre estados de sistema para impedir su desactivación o eliminación accidental.
- Se protegió la eliminación de formularios con solicitudes, versiones históricas o vínculos activos.
- Se enmascaran CURP y correo en el listado administrativo de solicitudes.
- Se configuraron locale, fallback y faker en español, junto con mensajes de validación en español.
- Se actualizaron las pruebas de envío ciudadano para validar versión vigente, consentimiento de privacidad y su persistencia.

## Verificaciones ejecutadas

- `php artisan migrate --no-interaction`: migraciones `2026_10_27_000004` a `2026_10_27_000006` aplicadas correctamente en el entorno local.
- `php artisan view:cache`: vistas Blade compiladas correctamente.
- `./vendor/bin/pint --dirty`: sin cambios pendientes de formato.
- `php artisan test`: **57 pruebas aprobadas, 358 aserciones**.

## Verificación manual pendiente

- Probar con una identidad real de Accesos el ciclo completo: crear trámite, asignar formulario, publicar versión y publicar trámite.
- Confirmar visualmente que una versión publicada anterior deja de estar activa para el trámite y que el expediente histórico sigue mostrando su versión original.
- Validar el texto definitivo del aviso de privacidad con el área jurídica o la unidad de transparencia municipal.
- Probar descarga y expiración de una ficha generada en el entorno integrado de pagos.

## Backlog posterior

### Alta

- Añadir pruebas feature específicas del ciclo de publicación/archivo de formularios y de la transición de vínculos históricos.
- Revisar y aprobar legalmente el contenido, canal de contacto y procedimiento ARCO del aviso de privacidad.

### Media

- Extender el enmascaramiento de datos personales a otros listados administrativos donde no sea necesario mostrarlos completos.
- Incorporar filtros persistentes por estado de publicación en el catálogo de trámites si el volumen operativo lo requiere.

### Baja

- Añadir visualizaciones históricas de actividad y uso de tipos de campo cuando haya suficiente volumen de datos.
- Incluir pruebas de interfaz para los controles de accesibilidad y los flujos de confirmación SweetAlert.
