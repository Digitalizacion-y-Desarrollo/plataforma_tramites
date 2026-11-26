# Incidencia: `PUT /api/contribuyentes/{id}` devuelve 409 para el mismo contribuyente

## Resumen

Al actualizar un contribuyente existente mediante el endpoint de Tesorería, la API devuelve `HTTP 409` con el mensaje:

```json
{
  "message": "El ID del contribuyente no corresponde a la CURP proporcionada."
}
```

El conflicto ocurre aunque el mismo ID consultado previamente por `GET /api/contribuyentes/{id}` devuelve esa misma CURP.

## Ambiente

- API: `https://api-tramites-uat.tesoreria-neza.gob.mx/api`
- Autenticación: `Bearer {access_token}` obtenido con `POST /api/auth/login`
- Contrato consultado: API de Procedimientos de Tesorería de Nezahualcóyotl, versión `1.1.0`.

## Caso de reproducción

> Por seguridad, los identificadores reales se omiten en este documento. Se pueden proporcionar mediante un canal seguro al equipo de Tesorería.

1. Autenticarse correctamente mediante:

   ```http
   POST /api/auth/login
   ```

2. Consultar el contribuyente existente:

   ```http
   GET /api/contribuyentes/{id_externo}
   Authorization: Bearer {access_token}
   ```

   Resultado: `HTTP 200`. La respuesta contiene una CURP determinada en `data.curp`.

3. Actualizar ese mismo ID, enviando exactamente la CURP recibida en el paso anterior:

   ```http
   PUT /api/contribuyentes/{id_externo}
   Authorization: Bearer {access_token}
   Content-Type: application/json
   Accept: application/json
   ```

   Cuerpo de ejemplo:

   ```json
   {
     "curp": "{curp_obtenida_en_get}",
     "correo": "correo@ejemplo.com",
     "telefono": "5500000000",
     "nombre": "Nombre",
     "apellido_paterno": "ApellidoPaterno",
     "apellido_materno": "ApellidoMaterno",
     "calle": "Calle de ejemplo",
     "numero_exterior": "123",
     "colonia": "Colonia de ejemplo",
     "codigo_postal": "57000",
     "municipio_id": 715,
     "regimen_fiscal_id": 616
   }
   ```

4. Resultado obtenido:

   ```http
   HTTP/1.1 409 Conflict
   ```

   ```json
   {
     "message": "El ID del contribuyente no corresponde a la CURP proporcionada."
   }
   ```

## Comportamiento esperado

Si `GET /api/contribuyentes/{id_externo}` devuelve un contribuyente cuya `data.curp` coincide con la CURP enviada al `PUT /api/contribuyentes/{id_externo}`, la API debe actualizar el registro y responder `HTTP 200`.

## Comportamiento actual

La API responde `HTTP 409`, aun cuando:

- El token Bearer es válido.
- El `GET` del contribuyente responde `HTTP 200`.
- La CURP enviada en el `PUT` coincide con `data.curp` del `GET`.
- El valor de `{id_externo}` usado en el `PUT` es el mismo empleado en el `GET`.

## Impacto

El Portal de Trámites puede consultar y precargar contribuyentes, pero no puede reflejar en Tesorería los cambios posteriores realizados por el ciudadano, como correo, teléfono, nombre o domicilio.

El portal conserva localmente la actualización y registra el intento de sincronización para reintento; sin embargo, un conflicto `409` persistente requiere corrección en la API para que el reintento tenga éxito.

## Solicitud al equipo de Tesorería

Revisar la validación de pertenencia entre el parámetro de ruta `{id}` y el identificador enviado en el cuerpo de `PUT /api/contribuyentes/{id}`. En particular:

1. Confirmar que la comparación usa el campo `curp` para personas físicas y `rfc` para personas morales.
2. Confirmar que el ID retornado por `GET /api/contribuyentes/{id}` puede utilizarse en el `PUT` del mismo recurso.
3. Revisar si existe alguna normalización de CURP o filtro de origen que esté provocando el falso conflicto.
4. Compartir un ejemplo válido de actualización o una corrección del endpoint.
