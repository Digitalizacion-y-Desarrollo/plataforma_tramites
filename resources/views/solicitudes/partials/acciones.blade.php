@php($contenedor = $movil ? 'border-t border-neza-line pt-3' : 'flex justify-end')

<div class="{{ $contenedor }}">
    <a href="{{ route('solicitudes.show', $solicitud) }}" class="{{ $movil ? 'inline-flex w-full justify-center' : '' }} rounded-lg border border-neza-wine px-3 py-2 text-xs font-semibold text-neza-wine hover:bg-neza-wine-soft">Ver detalle</a>
</div>
