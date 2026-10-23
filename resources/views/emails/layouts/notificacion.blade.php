<!doctype html>
<html lang="es" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="format-detection" content="telephone=no,address=no,email=no,date=no,url=no">
    <title>{{ $subject ?? 'Plataforma de Trámites' }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f8f9fa; color:#1f2937; font-family:Arial, Helvetica, sans-serif;">
    @php
        $statusTone = $statusTone ?? 'warning';
        $logoPath = $logoPath ?? public_path('images/logo/logo-sin-fondo.png');
        $logoUrl = isset($message) ? $message->embed($logoPath) : asset('images/logo/logo-sin-fondo.png');
        $statusStyles = [
            'success' => ['background' => '#eaf6ee', 'color' => '#216238'],
            'danger' => ['background' => '#fbeded', 'color' => '#9b2c2c'],
            'warning' => ['background' => '#f7f0e5', 'color' => '#76531f'],
            'info' => ['background' => '#edf3fa', 'color' => '#24517d'],
        ];
        $statusStyle = $statusStyles[$statusTone] ?? $statusStyles['warning'];
    @endphp

    <span style="display:none!important; visibility:hidden; opacity:0; color:transparent; height:0; width:0; overflow:hidden; mso-hide:all;">
        {{ $preheader ?? $title ?? 'Actualización de tu trámite.' }}
    </span>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%; background-color:#f8f9fa; border-collapse:collapse;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%; max-width:640px; background-color:#ffffff; border:1px solid #e5e7eb; border-radius:16px; border-collapse:separate; overflow:hidden;">
                    <tr>
                        <td style="height:6px; background-color:#601633; font-size:0; line-height:0;">&nbsp;</td>
                    </tr>
                    <tr>
                        <td style="padding:28px 32px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border-collapse:collapse;">
                                <tr>
                                    <td width="78" valign="middle" style="width:78px;">
                                        <img src="{{ $logoUrl }}" width="78" height="58" alt="Plataforma de Trámites del Gobierno Municipal de Nezahualcóyotl" style="display:block; width:78px; height:58px; border:0; object-fit:contain;">
                                    </td>
                                    <td width="16" style="width:16px;">&nbsp;</td>
                                    <td width="1" style="width:1px; background-color:#e5e7eb; font-size:0; line-height:0;">&nbsp;</td>
                                    <td width="16" style="width:16px;">&nbsp;</td>
                                    <td valign="middle">
                                        <p style="margin:0 0 4px; color:#340c1b; font-size:18px; font-weight:700; line-height:22px;">Plataforma de Trámites</p>
                                        <p style="margin:0; color:#6b7280; font-size:12px; line-height:18px;">Gobierno Municipal de Nezahualcóyotl</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    @if (! empty($sampleNotice))
                        <tr>
                            <td style="padding:10px 32px; background-color:#fdfbf8; border-top:1px solid #ede7de; border-bottom:1px solid #ede7de; color:#76531f; font-size:11px; font-weight:700; line-height:16px; letter-spacing:.02em;">{{ $sampleNotice }}</td>
                        </tr>
                    @endif

                    <tr>
                        <td style="padding:32px 32px 24px;">
                            <p style="margin:0 0 12px; color:#76531f; font-size:11px; font-weight:700; line-height:16px; letter-spacing:.02em;">{{ $kicker ?? 'ACTUALIZACIÓN DE TU SOLICITUD' }}</p>
                            <h1 style="margin:0 0 16px; color:#1f2937; font-size:28px; font-weight:700; line-height:32px;">{{ $title ?? 'Tienes una actualización en tu trámite' }}</h1>

                            @if (! empty($recipientName))
                                <p style="margin:0 0 12px; color:#1f2937; font-size:15px; font-weight:700; line-height:22px;">Hola, {{ $recipientName }}:</p>
                            @endif

                            @if (! empty($intro))
                                <p style="margin:0; color:#6b7280; font-size:15px; line-height:24px;">{{ $intro }}</p>
                            @endif
                        </td>
                    </tr>

                    @if (! empty($highlightMessage))
                        <tr>
                            <td style="padding:0 32px 24px;">
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%; background-color:#f5e9ee; border:1px solid #dcb9c7; border-left:5px solid #601633; border-radius:12px; border-collapse:separate;">
                                    <tr>
                                        <td style="padding:20px 22px;">
                                            <p style="margin:0 0 10px; color:#601633; font-size:12px; font-weight:700; line-height:18px; letter-spacing:.02em; text-transform:uppercase;">{{ $highlightMessageTitle ?? 'Mensaje importante' }}</p>
                                            <p style="margin:0; color:#340c1b; font-size:16px; font-weight:700; line-height:25px; white-space:pre-line;">{{ $highlightMessage }}</p>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    @endif

                    @if (! empty($tramiteName) || ! empty($folio) || ! empty($statusLabel) || ! empty($applicantName))
                        <tr>
                            <td style="padding:0 32px 24px;">
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%; background-color:#f8f9fa; border:1px solid #e5e7eb; border-radius:12px; border-collapse:separate;">
                                    <tr>
                                        <td style="padding:22px 24px 16px;">
                                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border-collapse:collapse;">
                                                <tr>
                                                    <td valign="middle"><p style="margin:0; color:#1f2937; font-size:16px; font-weight:700; line-height:22px;">Resumen del trámite</p></td>
                                                    @if (! empty($statusLabel))
                                                        <td align="right" valign="middle">
                                                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="display:inline-table; background-color:{{ $statusStyle['background'] }}; border-radius:999px; border-collapse:separate;">
                                                                <tr>
                                                                    <td style="padding:7px 0 7px 11px;"><img src="{{ asset('images/email/status-dot.svg') }}" width="6" height="6" alt="" style="display:block; width:6px; height:6px; border:0;"></td>
                                                                    <td style="padding:7px 11px 7px 7px; color:{{ $statusStyle['color'] }}; font-size:12px; font-weight:700; line-height:16px; white-space:nowrap;">{{ $statusLabel }}</td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    @endif
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <tr><td style="padding:0 24px;"><div style="height:1px; background-color:#e5e7eb; font-size:0; line-height:0;">&nbsp;</div></td></tr>
                                    @if (! empty($tramiteName))
                                        <tr><td style="padding:16px 24px 0;"><p style="margin:0 0 5px; color:#6b7280; font-size:12px; line-height:17px;">Trámite</p><p style="margin:0; color:#1f2937; font-size:14px; font-weight:700; line-height:20px;">{{ $tramiteName }}</p></td></tr>
                                    @endif
                                    @if (! empty($folio) || ! empty($applicantName))
                                        <tr>
                                            <td style="padding:16px 24px 22px;">
                                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border-collapse:collapse;">
                                                    <tr>
                                                        @if (! empty($folio))
                                                            <td width="50%" valign="top" style="padding-right:12px;"><p style="margin:0 0 5px; color:#6b7280; font-size:12px; line-height:17px;">Folio</p><p style="margin:0; color:#1f2937; font-size:14px; font-weight:700; line-height:20px;">{{ $folio }}</p></td>
                                                        @endif
                                                        @if (! empty($applicantName))
                                                            <td width="50%" valign="top" style="padding-left:12px;"><p style="margin:0 0 5px; color:#6b7280; font-size:12px; line-height:17px;">Persona solicitante</p><p style="margin:0; color:#1f2937; font-size:14px; font-weight:700; line-height:20px;">{{ $applicantName }}</p></td>
                                                        @endif
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    @endif
                                </table>
                            </td>
                        </tr>
                    @endif

                    @if (! empty($steps))
                        <tr>
                            <td style="padding:0 32px 24px;">
                                <h2 style="margin:0 0 16px; color:#1f2937; font-size:18px; font-weight:700; line-height:25px;">{{ $stepsTitle ?? '¿Qué puedes hacer ahora?' }}</h2>
                                @foreach ($steps as $index => $step)
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border-collapse:collapse;">
                                        <tr>
                                            <td width="42" valign="top" style="width:42px; padding-bottom:16px;"><span style="display:block; width:30px; height:30px; border-radius:50%; background-color:#f5e9ee; color:#601633; font-size:13px; font-weight:700; line-height:30px; text-align:center;">{{ $index + 1 }}</span></td>
                                            <td valign="top" style="padding-bottom:16px;"><p style="margin:0 0 4px; color:#1f2937; font-size:14px; font-weight:700; line-height:20px;">{{ $step['title'] }}</p>@if (! empty($step['description']))<p style="margin:0; color:#6b7280; font-size:14px; line-height:21px;">{{ $step['description'] }}</p>@endif</td>
                                        </tr>
                                    </table>
                                @endforeach
                            </td>
                        </tr>
                    @endif

                    @if (! empty($notice))
                        <tr>
                            <td style="padding:0 32px 24px;">
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%; background-color:#fdfbf8; border:1px solid #ede7de; border-radius:12px; border-collapse:separate;">
                                    <tr>
                                        <td width="42" valign="top" style="width:42px; padding:16px 8px 16px 16px;"><img src="{{ asset('images/email/info.svg') }}" width="18" height="18" alt="" style="display:block; border:0;"></td>
                                        <td valign="top" style="padding:16px 16px 16px 0;"><p style="margin:0 0 5px; color:#76531f; font-size:13px; font-weight:700; line-height:18px;">{{ $noticeTitle ?? 'Ten en cuenta' }}</p><p style="margin:0; color:#6b7280; font-size:13px; line-height:20px;">{{ $notice }}</p></td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    @endif

                    @if (! empty($actionUrl) && ! empty($actionLabel))
                        <tr>
                            <td style="padding:0 32px 24px;">
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%; border-collapse:collapse;">
                                    <tr><td align="center" style="background-color:#601633; border-radius:12px;"><a href="{{ $actionUrl }}" style="display:block; padding:16px 24px; color:#ffffff; font-size:15px; font-weight:700; line-height:18px; text-align:center; text-decoration:none;">{{ $actionLabel }}&nbsp;&nbsp;<img src="{{ asset('images/email/arrow-right.svg') }}" width="17" height="17" alt="" style="vertical-align:middle; border:0;"></a></td></tr>
                                </table>
                                @if (! empty($actionHint))<p style="margin:12px 0 0; color:#6b7280; font-size:12px; line-height:18px; text-align:center;">{{ $actionHint }}</p>@endif
                            </td>
                        </tr>
                    @endif

                    <tr>
                        <td style="padding:0 32px 32px;">
                            @if (! empty($farewell))<p style="margin:0 0 4px; color:#6b7280; font-size:14px; line-height:20px;">{{ $farewell }}</p>@endif
                            <p style="margin:0; color:#340c1b; font-size:14px; font-weight:700; line-height:20px;">Gobierno Municipal de Nezahualcóyotl</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px 32px; background-color:#f8f9fa; border-top:1px solid #e5e7eb;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border-collapse:collapse;"><tr>
                                <td width="32" valign="top" style="width:32px;"><img src="{{ asset('images/email/circle-help.svg') }}" width="20" height="20" alt="" style="display:block; border:0;"></td>
                                <td valign="top"><p style="margin:0 0 8px; color:#1f2937; font-size:15px; font-weight:700; line-height:20px;">¿Necesitas orientación?</p><p style="margin:0; color:#6b7280; font-size:13px; line-height:20px;">{{ $helpText ?? 'Consulta el centro de ayuda y los canales de atención publicados en la plataforma. Ten a la mano tu folio.' }}</p>@if (! empty($helpUrl))<p style="margin:8px 0 0;"><a href="{{ $helpUrl }}" style="color:#601633; font-size:13px; font-weight:700; line-height:18px; text-decoration:underline;">Visitar el centro de ayuda</a></p>@endif</td>
                            </tr></table>
                        </td>
                    </tr>
                    <tr><td style="height:2px; background-color:#be985c; font-size:0; line-height:0;">&nbsp;</td></tr>
                    <tr>
                        <td style="padding:28px 32px; background-color:#340c1b;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border-collapse:collapse;"><tr>
                                <td width="32" valign="top" style="width:32px;"><img src="{{ asset('images/email/shield-check.svg') }}" width="20" height="20" alt="" style="display:block; border:0;"></td>
                                <td valign="top"><p style="margin:0 0 6px; color:#ffffff; font-size:14px; font-weight:700; line-height:20px;">Protegemos tu información</p><p style="margin:0; color:#e7dfe2; font-size:12px; line-height:18px;">Este correo contiene información relacionada con tu trámite. No compartas datos personales ni enlaces de acceso con terceros.</p></td>
                            </tr></table>
                        </td>
                    </tr>
                </table>
                <p style="max-width:640px; margin:16px auto 0; color:#6b7280; font-size:11px; line-height:16px; text-align:center;">Este es un correo informativo generado por la Plataforma de Trámites del Gobierno Municipal de Nezahualcóyotl.</p>
            </td>
        </tr>
    </table>
</body>
</html>
