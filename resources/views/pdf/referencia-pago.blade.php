<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 28px 34px 34px; }
        * { box-sizing: border-box; }
        body { color: #22131a; font-family: DejaVu Sans, sans-serif; font-size: 9px; line-height: 1.4; margin: 0; }
        .accent { background: #7c1d45; height: 5px; margin-bottom: 16px; width: 100%; }
        .header, .payment-meta, .hero { width: 100%; }
        .header td, .payment-meta td { vertical-align: middle; }
        .logo { height: 42px; object-fit: contain; width: 56px; }
        .divider { border-left: 1px solid #ded7da; height: 30px; width: 13px; }
        .platform { color: #471126; font-size: 13px; font-weight: bold; }
        .municipio { color: #746870; font-size: 7.5px; margin-top: 2px; }
        .document-title { color: #471126; font-size: 10px; font-weight: bold; letter-spacing: .6px; text-align: right; }
        .document-subtitle { color: #9e7b3d; font-size: 7px; font-weight: bold; letter-spacing: .4px; margin-top: 2px; text-align: right; }
        .hero { background: #471126; border-radius: 7px; color: #fff; margin-top: 16px; padding: 14px 17px; }
        .hero td { vertical-align: top; }
        .hero-kicker { color: #edcf92; font-size: 7px; font-weight: bold; letter-spacing: .7px; text-transform: uppercase; }
        .hero-title { font-size: 15px; font-weight: bold; margin-top: 3px; }
        .hero-copy { color: #eadde2; font-size: 7.5px; margin-top: 4px; }
        .hero-amount { border-left: 1px solid #8f5c70; padding-left: 18px; text-align: right; }
        .hero-amount-label { color: #edcf92; font-size: 7px; font-weight: bold; text-transform: uppercase; }
        .hero-amount-value { font-size: 21px; font-weight: bold; margin-top: 2px; }
        .capture { border: 1px solid #ded7da; border-radius: 7px; margin-top: 12px; padding: 11px 13px; }
        .capture-table { width: 100%; }
        .capture-table td { vertical-align: middle; }
        .capture-left { border-right: 1px solid #e1dadd; padding-right: 15px; text-align: center; width: 67%; }
        .capture-right { padding-left: 15px; text-align: center; width: 33%; }
        .capture-label { color: #7c1d45; font-size: 7px; font-weight: bold; letter-spacing: .5px; text-transform: uppercase; }
        .barcode { display: inline-block; height: 50px; margin: 8px 0 4px; object-fit: fill; width: 50%; }
        .barcode-note { color: #746870; font-size: 6.5px; text-align: center; }
        .capture-label { text-align: center; }
        .reference { color: #22131a; font-family: DejaVu Sans Mono, monospace; font-size: 11px; font-weight: bold; letter-spacing: 1px; margin-top: 2px; text-align: center; }
        .qr-code { display: block; height: 108px; margin: 5px auto 3px; width: 108px; }
        .qr-reference { color: #22131a; font-family: DejaVu Sans Mono, monospace; font-size: 7px; font-weight: bold; letter-spacing: .6px; text-align: center; }
        .qr-note { color: #746870; font-size: 6px; line-height: 1.3; text-align: center; }
        .payment-meta { margin: 10px 0 14px; }
        .date-card { background: #f7f4f5; border: 1px solid #e6dfe2; border-radius: 5px; color: #746870; font-size: 7px; padding: 7px 9px; }
        .date-value { color: #22131a; display: block; font-size: 9px; font-weight: bold; margin-top: 2px; }
        .section { margin-top: 13px; }
        .section-heading { color: #471126; font-size: 9px; font-weight: bold; letter-spacing: .2px; padding: 0 0 6px; }
        .section-number { background: #f2e7eb; border-radius: 50%; color: #7c1d45; display: inline-block; font-size: 7px; margin-right: 6px; padding: 3px 5px; }
        .data-table { border-collapse: collapse; table-layout: fixed; width: 100%; }
        .data-table td, .data-table th { border: 1px solid #e1dadd; padding: 7px 8px; vertical-align: top; }
        .data-label { color: #7c1d45; display: block; font-size: 6.5px; font-weight: bold; letter-spacing: .3px; text-transform: uppercase; }
        .data-value { color: #22131a; display: block; font-size: 8px; font-weight: bold; margin-top: 3px; }
        .data-help { color: #887b83; display: block; font-size: 6px; margin-top: 2px; }
        .contribution th { background: #471126; color: #fff; font-size: 6.5px; font-weight: bold; padding: 7px; text-align: left; text-transform: uppercase; }
        .contribution td { color: #22131a; font-size: 7.5px; min-height: 30px; padding: 8px 7px; }
        .contribution .center { text-align: center; }
        .total-row td { background: #f2e7eb; border-color: #e1dadd; color: #471126; font-size: 8px; font-weight: bold; padding: 9px; }
        .total-row .amount { color: #000; font-size: 11px; text-align: right; }
        .institutions td { border: 0; height: 58px; padding: 8px 12px; text-align: center; width: 33.333%; }
        .institution-logo { display: block; margin: 0 auto 5px; max-height: 25px; max-width: 90px; object-fit: contain; }
        .institution-title { color: #471126; font-size: 6.5px; font-weight: bold; margin-top: 3px; text-align: center; text-transform: uppercase; }
        .institution-help { color: #887b83; font-size: 6.5px; margin-top: 3px; text-align: center; }
        .notice { background: #fff8e9; border-left: 3px solid #c59a50; color: #665331; font-size: 7px; margin-top: 13px; padding: 7px 9px; }
        .footer { border-top: 1px solid #e1dadd; bottom: 0; color: #887b83; font-size: 6.5px; left: 0; padding-top: 7px; position: fixed; right: 0; }
        .footer-right { float: right; }
    </style>
</head>
<body>
    <div class="accent"></div>
    <table class="header">
        <tr>
            <td style="width: 64px;"><img class="logo" src="{{ public_path('images/portal/logo-nezahualcoyotl.png') }}" alt="Gobierno Municipal de Nezahualcóyotl"></td>
            <td class="divider"></td>
            <td><div class="platform">Plataforma de Trámites</div><div class="municipio">Gobierno Municipal de Nezahualcóyotl</div></td>
            <td style="width: 135px;"><div class="document-title">FORMATO DE PAGO</div><div class="document-subtitle">FORMATO GRATUITO</div></td>
        </tr>
    </table>

    <table class="hero"><tr>
        <td><div class="hero-kicker">Ficha de pago</div><div class="hero-title">Realiza tu pago antes de la fecha límite</div><div class="hero-copy">Presenta la línea de captura en cualquiera de las instituciones autorizadas.</div></td>
        <td class="hero-amount"><div class="hero-amount-label">Total a pagar</div><div class="hero-amount-value">${{ number_format((float) $pago->importe, 2) }}</div><div class="hero-copy">MXN</div></td>
    </tr></table>

    <div class="capture"><table class="capture-table"><tr><td class="capture-left"><div class="capture-label">Línea de captura</div><div class="reference">{{ $pago->referencia }}</div><img class="barcode" src="{{ $codigoBarras }}" alt="Código de barras de la línea de captura {{ $pago->referencia }}"><div class="barcode-note">Escanea este código o proporciona la línea de captura al realizar tu pago.</div></td><td class="capture-right"><div class="capture-label">Código QR</div><img class="qr-code" src="{{ $codigoQr }}" alt="Código QR de la línea de captura {{ $pago->referencia }}"><div class="qr-reference">{{ $pago->referencia }}</div><div class="qr-note">Escanéalo para consultar la línea de captura.</div></td></tr></table></div>

    <table class="payment-meta"><tr>
        <td style="width: 50%; padding-right: 5px;"><div class="date-card">Fecha de emisión<span class="date-value">{{ $pago->fecha_emision->format('d/m/Y') }}</span></div></td>
        <td style="width: 50%; padding-left: 5px;"><div class="date-card">Fecha límite de pago<span class="date-value">{{ $pago->fecha_vencimiento->format('d/m/Y') }}</span></div></td>
    </tr></table>

    <section class="section">
        <div class="section-heading"><span class="section-number">01</span>DATOS DEL CONTRIBUYENTE</div>
        <table class="data-table">
            <tr><td colspan="2"><span class="data-label">Nombre(s)</span><span class="data-value">{{ $solicitud->nombres_solicitante ?: $solicitud->nombre_solicitante_snapshot }}</span><span class="data-help">Nombre o nombres de pila</span></td></tr>
            <tr><td><span class="data-label">Apellido materno</span><span class="data-value">{{ $solicitud->apellido_materno_solicitante ?: 'No proporcionado' }}</span></td><td><span class="data-label">Apellido paterno</span><span class="data-value">{{ $solicitud->apellido_paterno_solicitante ?: 'No proporcionado' }}</span></td></tr>
        </table>
    </section>

    <section class="section">
        <div class="section-heading"><span class="section-number">02</span>DATOS DE LA CONTRIBUCIÓN</div>
        <table class="data-table contribution">
            <tr><th style="width: 24%;">No. referencia</th><th style="width: 43%;">Nombre del trámite</th><th style="width: 9%;">Cantidad</th><th style="width: 12%;">Tarifa / tasa</th><th style="width: 12%;">Subtotal</th></tr>
            @php($detallesLiquidacion = is_array($liquidacion['detalles'] ?? null) ? $liquidacion['detalles'] : [])
            @forelse ($detallesLiquidacion as $detalle)
                <tr><td>{{ $pago->referencia }}</td><td>{{ $detalle['nombre'] ?? $detalle['descripcion'] ?? $solicitud->tramite->nombre }}</td><td class="center">{{ $detalle['cantidad'] ?? 1 }}</td><td class="center">${{ number_format((float) ($detalle['importe'] ?? 0), 2) }}</td><td class="center">${{ number_format((float) ($detalle['total'] ?? $detalle['importe'] ?? 0), 2) }}</td></tr>
            @empty
                <tr><td>{{ $pago->referencia }}</td><td>{{ $solicitud->tramite->nombre }}</td><td class="center">1</td><td class="center">${{ number_format((float) $pago->importe, 2) }}</td><td class="center">${{ number_format((float) $pago->importe, 2) }}</td></tr>
            @endforelse
            <tr class="total-row"><td colspan="4">MONTO A PAGAR - EN UNA SOLA EXHIBICIÓN</td><td class="amount">${{ number_format((float) $pago->importe, 2) }}</td></tr>
        </table>
    </section>

    <section class="section">
        <div class="section-heading"><span class="section-number">03</span>INSTITUCIONES AUTORIZADAS PARA RECIBIR EL PAGO</div>
        <table class="data-table institutions">
            @forelse (collect($convenios)->chunk(3) as $fila)
                <tr>@foreach ($fila as $convenio)<td>@if ($convenio['logo'])<img class="institution-logo" src="{{ $convenio['logo'] }}" alt="{{ $convenio['nombre'] }}">@endif<div class="institution-title">{{ $convenio['nombre'] }}</div>@if ($convenio['convenio'])<div class="institution-help">{{ $convenio['convenio'] }}</div>@endif</td>@endforeach @for ($espacio = $fila->count(); $espacio < 3; $espacio++)<td></td>@endfor</tr>
            @empty
                <tr><td colspan="3"><div class="institution-help">No fue posible obtener las instituciones autorizadas.</div></td></tr>
            @endforelse
        </table>
    </section>

    <p class="notice">Conserva este formato y tu comprobante. La referencia fue consultada directamente con Tesorería; el área responsable validará el pago antes de emitir una resolución final.</p>
    <div class="footer">Este formato no sustituye el comprobante de pago oficial.<span class="footer-right">Página 1 de 1</span></div>
</body>
</html>
