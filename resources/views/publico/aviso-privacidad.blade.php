@extends('layouts.publico')

@section('title', 'Aviso de privacidad')

@section('content')
    <section class="bg-neza-canvas px-4 py-10 sm:px-6 lg:px-[72px]">
        <div class="mx-auto max-w-4xl rounded-2xl border border-neza-line bg-white p-6 shadow-sm sm:p-10">
            <p class="text-xs font-bold uppercase tracking-wide text-neza-wine">Protección de datos personales</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-neza-ink">Aviso de privacidad</h1>
            <p class="mt-3 text-sm leading-relaxed text-neza-muted">
                El Gobierno Municipal de Nezahualcóyotl es responsable del tratamiento de los datos personales que
                proporcionas en esta plataforma. Este aviso aplica a la creación, consulta y seguimiento de trámites
                municipales.
            </p>

            <div class="mt-8 space-y-7 text-sm leading-relaxed text-neza-ink">
                <section>
                    <h2 class="text-lg font-bold">Datos que recabamos</h2>
                    <p class="mt-2 text-neza-muted">
                        Podemos solicitar nombre, CURP, correo electrónico, teléfono, domicilio, información del predio o
                        de la obra, respuestas del formulario y documentos de respaldo. La CURP se utiliza para
                        identificar tus solicitudes y no se considera una contraseña.
                    </p>
                </section>

                <section>
                    <h2 class="text-lg font-bold">Para qué los utilizamos</h2>
                    <ul class="mt-2 list-inside list-disc space-y-1 text-neza-muted">
                        <li>Registrar y dar seguimiento a tu solicitud.</li>
                        <li>Validar la información y los documentos requeridos.</li>
                        <li>Enviar códigos temporales y notificaciones sobre el estado del trámite.</li>
                        <li>Atender aclaraciones, correcciones y consultas relacionadas con el expediente.</li>
                        <li>Cumplir obligaciones legales y de transparencia aplicables.</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-lg font-bold">Acceso y resguardo</h2>
                    <p class="mt-2 text-neza-muted">
                        Solo el personal autorizado del área responsable puede consultar tu expediente. Los documentos se
                        almacenan fuera de la base de datos y se protegen mediante controles de acceso. No compartimos tus
                        datos con terceros salvo cuando exista una obligación legal o sea necesario para la atención del
                        trámite.
                    </p>
                </section>

                <section>
                    <h2 class="text-lg font-bold">Consulta con código temporal</h2>
                    <p class="mt-2 text-neza-muted">
                        Para consultar tus solicitudes necesitas tu CURP, el correo registrado y un código temporal que
                        enviamos a ese correo. El código vence y tiene un límite de intentos. No compartas el código con
                        otras personas.
                    </p>
                </section>

                <section>
                    <h2 class="text-lg font-bold">Tus derechos</h2>
                    <p class="mt-2 text-neza-muted">
                        Puedes solicitar acceso, rectificación, cancelación u oposición al tratamiento de tus datos, así
                        como revocar el consentimiento, mediante los canales de atención publicados en este portal. La
                        solicitud debe identificar el trámite o folio correspondiente.
                    </p>
                </section>

                <section>
                    <h2 class="text-lg font-bold">Cambios al aviso</h2>
                    <p class="mt-2 text-neza-muted">
                        Cualquier cambio a este aviso se publicará en esta misma página. El uso de la plataforma después
                        de una actualización implica que conoces la versión vigente.
                    </p>
                </section>

                @if (filled($canalesAyuda?->telefono_contacto) || filled($canalesAyuda?->email_contacto))
                    <section class="rounded-xl border border-neza-line bg-neza-canvas p-5">
                        <h2 class="text-lg font-bold">Contacto</h2>
                        <p class="mt-2 text-neza-muted">Para dudas sobre el tratamiento de tus datos personales:</p>
                        <ul class="mt-2 space-y-1 text-neza-muted">
                            @if (filled($canalesAyuda?->telefono_contacto))
                                <li>Teléfono: <a class="font-semibold text-neza-wine" href="tel:{{ preg_replace('/\D+/', '', $canalesAyuda->telefono_contacto) }}">{{ $canalesAyuda->telefono_contacto }}</a></li>
                            @endif
                            @if (filled($canalesAyuda?->email_contacto))
                                <li>Correo: <a class="font-semibold text-neza-wine" href="mailto:{{ $canalesAyuda->email_contacto }}">{{ $canalesAyuda->email_contacto }}</a></li>
                            @endif
                        </ul>
                    </section>
                @endif
            </div>

            <a href="{{ url()->previous() }}" class="mt-8 inline-flex items-center gap-2 text-sm font-bold text-neza-wine hover:underline">
                Volver a la página anterior
            </a>
        </div>
    </section>
@endsection
