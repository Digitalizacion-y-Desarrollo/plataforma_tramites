<?php

return [
    'tipos_campo' => [
        'TEXT' => 'Texto corto',
        'TEXTAREA' => 'Texto largo',
        'NUMBER' => 'Número',
        'EMAIL' => 'Correo electrónico',
        'PHONE' => 'Teléfono',
        'DATE' => 'Fecha',
        'SELECT' => 'Lista desplegable',
        'MULTISELECT' => 'Selección múltiple',
        'RADIO' => 'Opción única',
        'CHECKBOX' => 'Casilla',
        'FILE' => 'Archivo',
        'ESTADO' => 'Entidad federativa',
        'DOMICILIO' => 'Domicilio',
    ],
    'campos_domicilio' => [
        'codigo_postal' => ['etiqueta' => 'Código postal', 'tipo' => 'text', 'autocomplete' => 'postal-code'],
        'municipio' => ['etiqueta' => 'Municipio', 'tipo' => 'text', 'autocomplete' => 'address-level2', 'valor' => 'Nezahualcóyotl', 'disabled' => true],
        'colonia' => ['etiqueta' => 'Colonia', 'tipo' => 'text', 'autocomplete' => 'address-line3'],
        'calle' => ['etiqueta' => 'Calle', 'tipo' => 'text', 'autocomplete' => 'street-address'],
        'numero' => ['etiqueta' => 'Número', 'tipo' => 'text', 'autocomplete' => 'address-line2'],
    ],
    'tipos_con_opciones' => ['SELECT', 'MULTISELECT', 'RADIO'],
    'entidades_federativas' => [
        'AGU' => 'Aguascalientes', 'BCN' => 'Baja California', 'BCS' => 'Baja California Sur', 'CAM' => 'Campeche', 'CHP' => 'Chiapas', 'CHH' => 'Chihuahua', 'CMX' => 'Ciudad de México', 'COA' => 'Coahuila de Zaragoza', 'COL' => 'Colima', 'DUR' => 'Durango', 'GUA' => 'Guanajuato', 'GRO' => 'Guerrero', 'HID' => 'Hidalgo', 'JAL' => 'Jalisco', 'MEX' => 'México', 'MIC' => 'Michoacán de Ocampo', 'MOR' => 'Morelos', 'NAY' => 'Nayarit', 'NLE' => 'Nuevo León', 'OAX' => 'Oaxaca', 'PUE' => 'Puebla', 'QUE' => 'Querétaro', 'ROO' => 'Quintana Roo', 'SLP' => 'San Luis Potosí', 'SIN' => 'Sinaloa', 'SON' => 'Sonora', 'TAB' => 'Tabasco', 'TAM' => 'Tamaulipas', 'TLA' => 'Tlaxcala', 'VER' => 'Veracruz de Ignacio de la Llave', 'YUC' => 'Yucatán', 'ZAC' => 'Zacatecas',
    ],
];
