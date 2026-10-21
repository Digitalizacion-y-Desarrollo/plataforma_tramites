<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 38px 42px 34px; }
        * { box-sizing: border-box; }
        body { color: #340c1b; font-family: DejaVu Sans, sans-serif; font-size: 9px; line-height: 1.35; margin: 0; }
        .accent { background: #601633; height: 3px; margin-bottom: 22px; width: 30px; }
        .header, .payment-meta { width: 100%; }
        .header td, .payment-meta td { vertical-align: middle; }
        .logo { height: 44px; object-fit: contain; width: 60px; }
        .divider { border-left: 1px solid #e5e7eb; height: 30px; width: 14px; }
        .platform { font-size: 13px; font-weight: bold; }
        .municipio { color: #6b7280; font-size: 8px; margin-top: 2px; }
        .document-title { font-size: 10px; font-weight: bold; text-align: right; }
        .document-subtitle { color: #be985c; font-size: 8px; font-weight: bold; margin-top: 2px; text-align: right; }
        .capture { background: #f3f0f1; border-radius: 3px; margin-top: 22px; padding: 10px 13px; }
        .capture-label { color: #601633; font-size: 7px; font-weight: bold; text-transform: uppercase; }
        .barcode { font-size: 0; height: 24px; margin: 6px 0 4px; overflow: hidden; white-space: nowrap; width: 49%; }
        .bar { background: #340c1b; display: inline-block; height: 24px; margin-right: 1px; }
        .bar-1 { width: 1px; }.bar-2 { width: 2px; }.bar-3 { width: 3px; }.bar-4 { width: 4px; }
        .barcode-note { color: #6b7280; font-size: 6.5px; }
        .payment-meta { margin: 20px 0 22px; }
        .dates { color: #6b7280; font-size: 8px; }
        .date-value { border-bottom: 1px solid #a6b0b3; color: #340c1b; display: inline-block; font-weight: bold; margin-left: 7px; min-width: 82px; }
        .total { background: #601633; border-radius: 3px; color: white; padding: 9px 21px; text-align: center; width: 105px; }
        .total-label { color: #f3c97e; font-size: 8px; }
        .total-amount { font-size: 19px; font-weight: bold; margin-top: 1px; }
        .section { margin-top: 16px; }
        .section-heading { background: #f3f0f1; border-bottom: 1px solid #601633; border-radius: 3px 3px 0 0; font-size: 11px; font-weight: bold; padding: 7px 9px; }
        .section-number { color: #be985c; font-size: 8px; margin-right: 9px; }
        .data-table { border-collapse: collapse; table-layout: fixed; width: 100%; }
        .data-table td, .data-table th { border: 1px solid #d1d5db; padding: 7px 8px; vertical-align: top; }
        .data-label { color: #601633; display: block; font-size: 7px; font-weight: bold; text-transform: uppercase; }
        .data-value { color: #340c1b; display: block; font-size: 8px; font-weight: bold; margin-top: 3px; }
        .data-help { color: #9ca3af; display: block; font-size: 6.5px; margin-top: 2px; }
        .contribution th { background: #340c1b; color: #f3c97e; font-size: 6.5px; font-weight: bold; padding: 6px 7px; text-align: left; text-transform: uppercase; }
        .contribution td { color: #340c1b; font-size: 8px; min-height: 30px; padding: 8px 7px; }
        .contribution .center { text-align: center; }
        .total-row td { background: #601633; border-color: #601633; color: #f3c97e; font-size: 8px; font-weight: bold; padding: 9px; }
        .total-row .amount { color: white; font-size: 11px; text-align: right; }
        .institutions td { height: 45px; width: 33.333%; }
        .institution-title { color: #601633; font-size: 6.5px; font-weight: bold; text-transform: uppercase; }
        .institution-help { color: #9ca3af; font-size: 6.5px; margin-top: 4px; }
        .notice { color: #6b7280; font-size: 7px; margin-top: 12px; }
        .footer { border-top: 1px solid #601633; bottom: 0; color: #6b7280; font-size: 7px; left: 0; padding-top: 7px; position: fixed; right: 0; }
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

    <div class="capture">
        <div class="capture-label">Línea de captura para pago en ventanilla</div>
        @php($barras = [3, 1, 2, 1, 4, 1, 2, 3, 1, 2, 1, 4, 2, 1, 3, 2, 1, 4, 1, 2, 3, 1, 2, 4])
        <div class="barcode">@foreach (range(0, 71) as $indice)<span class="bar bar-{{ $barras[$indice % count($barras)] }}"></span>@endforeach</div>
        <div class="barcode-note">Código de barras ilustrativo. Sustituir por uno válido antes de emitir.</div>
    </div>

    <table class="payment-meta">
        <tr>
            <td class="dates">Fecha de emisión:<span class="date-value">{{ $pago->fecha_emision->format('d/m/Y') }}</span><br>Fecha límite de pago:<span class="date-value">{{ $pago->fecha_vencimiento->format('d/m/Y') }}</span></td>
            <td align="right"><div class="total"><div class="total-label">Total a pagar</div><div class="total-amount">${{ number_format((float) $pago->importe, 2) }}</div></div></td>
        </tr>
    </table>

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
            <tr><th style="width: 14%;">No. referencia</th><th style="width: 53%;">Nombre del trámite</th><th style="width: 9%;">Cantidad</th><th style="width: 12%;">Tarifa / tasa</th><th style="width: 12%;">Subtotal</th></tr>
            <tr><td>{{ $pago->referencia }}</td><td>{{ $solicitud->tramite->nombre }}</td><td class="center">1</td><td class="center">${{ number_format((float) $pago->importe, 2) }}</td><td class="center">${{ number_format((float) $pago->importe, 2) }}</td></tr>
            <tr class="total-row"><td colspan="4">MONTO A PAGAR - EN UNA SOLA EXHIBICIÓN</td><td class="amount">${{ number_format((float) $pago->importe, 2) }}</td></tr>
        </table>
    </section>

    <section class="section">
        <div class="section-heading"><span class="section-number">03</span>PAGO EN VENTANILLA EN LAS SIGUIENTES INSTITUCIONES AUTORIZADAS</div>
        <table class="data-table institutions">
            <tr>@foreach (range(1, 3) as $institucion)<td><div class="institution-title">Institución autorizada {{ $institucion }}</div><div class="institution-help">Nombre, dirección y horario</div></td>@endforeach</tr>
            <tr>@foreach (range(4, 6) as $institucion)<td><div class="institution-title">Institución autorizada {{ $institucion }}</div><div class="institution-help">Nombre, dirección y horario</div></td>@endforeach</tr>
        </table>
    </section>

    <p class="notice">Conserva este formato y tu comprobante. La referencia es provisional mientras se integra el servicio de pagos; el área responsable validará el pago antes de emitir una resolución final.</p>
    <div class="footer">Este formato no sustituye el comprobante de pago oficial.<span class="footer-right">Página 1 de 1</span></div>
</body>
</html>
