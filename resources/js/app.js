import './bootstrap';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

document.querySelectorAll('[data-dismiss-public-notice]').forEach((button) => {
    button.addEventListener('click', () => {
        button.closest('[data-public-notice]')?.remove();
    });
});

const cuerpo = document.body;

const botonMenuAdministracion = document.querySelector('[data-admin-menu-toggle]');
const menuAdministracion = document.querySelector('#admin-sidebar');
const fondoMenuAdministracion = document.querySelector('[data-admin-menu-overlay]');

if (botonMenuAdministracion && menuAdministracion && fondoMenuAdministracion) {
    const cerrarMenuAdministracion = () => {
        cuerpo.classList.remove('admin-menu-open');
        botonMenuAdministracion.setAttribute('aria-expanded', 'false');
    };

    botonMenuAdministracion.addEventListener('click', () => {
        const abierto = cuerpo.classList.toggle('admin-menu-open');
        botonMenuAdministracion.setAttribute('aria-expanded', String(abierto));
    });

    fondoMenuAdministracion.addEventListener('click', cerrarMenuAdministracion);
    menuAdministracion.querySelectorAll('a').forEach((enlace) => enlace.addEventListener('click', cerrarMenuAdministracion));
    document.addEventListener('keydown', (evento) => {
        if (evento.key === 'Escape') cerrarMenuAdministracion();
    });
}

if (cuerpo.dataset.swalSuccess) {
    Swal.fire({
        icon: 'success',
        title: 'Operación realizada',
        text: cuerpo.dataset.swalSuccess,
        confirmButtonColor: '#7f1d3f',
    });
}

if (cuerpo.dataset.swalError) {
    Swal.fire({
        icon: 'error',
        title: 'Revisa la información',
        text: cuerpo.dataset.swalError,
        confirmButtonColor: '#7f1d3f',
    });
}

if (cuerpo.dataset.solicitudActiva && cuerpo.dataset.solicitudActiva !== 'null') {
    const solicitudActiva = JSON.parse(cuerpo.dataset.solicitudActiva);

    Swal.fire({
        icon: 'info',
        title: 'Ya tienes un trámite en proceso',
        text: 'Ya existe una solicitud activa para este trámite. Consulta su estado antes de iniciar una nueva.',
        showCancelButton: true,
        confirmButtonText: 'Consultar trámite',
        cancelButtonText: 'Cerrar',
        confirmButtonColor: '#601633',
        cancelButtonColor: '#6b7280',
    }).then((resultado) => {
        if (resultado.isConfirmed && solicitudActiva.consulta_url) {
            window.location.assign(solicitudActiva.consulta_url);
        }
    });
}

const bloquearFormulario = (formulario) => {
    formulario.dataset.submitting = 'true';
    formulario.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((boton) => {
        boton.disabled = true;
        boton.setAttribute('aria-busy', 'true');

        if (boton.tagName === 'BUTTON' && !boton.querySelector('[data-submit-spinner]')) {
            const indicador = document.createElement('span');
            indicador.dataset.submitSpinner = '';
            indicador.className = 'inline-block size-4 animate-spin rounded-full border-2 border-current border-t-transparent';
            indicador.setAttribute('aria-hidden', 'true');
            boton.append(indicador);
        }
    });
};

document.querySelectorAll('form[method="POST"]').forEach((formulario) => {
    formulario.addEventListener('submit', async (evento) => {
        if (formulario.dataset.submitting === 'true') {
            evento.preventDefault();
            return;
        }

        if ((formulario.hasAttribute('data-confirm-delete') || formulario.hasAttribute('data-confirm-action')) && formulario.dataset.confirmed !== 'true') {
            evento.preventDefault();

            const dialogo = formulario.closest('dialog');
            const dialogoAbierto = dialogo && dialogo.open;

            if (dialogoAbierto) dialogo.close();

            const resultado = await Swal.fire({
                icon: 'warning',
                title: formulario.dataset.confirmTitle || '¿Confirmas la eliminación?',
                text: formulario.dataset.confirmText || 'Esta acción conservará el historial del registro.',
                showCancelButton: true,
                confirmButtonText: formulario.dataset.confirmButton || 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#b91c1c',
                cancelButtonColor: '#6b7280',
                reverseButtons: true,
            });

            if (!resultado.isConfirmed) {
                if (dialogoAbierto) dialogo.showModal();

                return;
            }

            formulario.dataset.confirmed = 'true';
            formulario.requestSubmit();
            return;
        }

        bloquearFormulario(formulario);
    });
});

document.querySelectorAll('[data-modal-open]').forEach((boton) => {
    boton.addEventListener('click', () => {
        const modal = document.getElementById(boton.dataset.modalOpen);

        if (!modal) return;

        if (boton.dataset.modalOpen === 'tramite-editar') {
            const formulario = modal.querySelector('#tramite-editar-form');
            const clave = modal.querySelector('#editar-clave');
            const nombre = modal.querySelector('#editar-nombre');
            const descripcion = modal.querySelector('#editar-descripcion');
            const activo = modal.querySelector('#editar-activo');

            formulario.action = boton.dataset.tramiteUpdateUrl;
            formulario.querySelector('[name="update_url"]').value = boton.dataset.tramiteUpdateUrl;
            formulario.querySelector('[name="clave_visual"]').value = boton.dataset.tramiteClave;
            clave.value = boton.dataset.tramiteClave;
            nombre.value = boton.dataset.tramiteNombre;
            descripcion.value = boton.dataset.tramiteDescripcion;
            activo.checked = boton.dataset.tramiteActivo === '1';

            modal.querySelector('#editar-duracion').value = boton.dataset.tramiteDuracion || '';
            modal.querySelector('#editar-costo').value = boton.dataset.tramiteCosto || '';
            modal.querySelector('#editar-tiempo').value = boton.dataset.tramiteTiempoEstimado || '';
            modal.querySelector('#editar-expira').value = boton.dataset.tramiteExpiraDias || '';
            modal.querySelectorAll('[name="modalidad"]').forEach((opcion) => {
                const guardadas = JSON.parse(boton.dataset.tramiteModalidades || '[]');
                const modalidad = guardadas.length > 1 ? 'MIXTO' : (guardadas[0] || '');
                opcion.checked = opcion.value === modalidad;
            });
            modal.querySelector('[name="modalidad"]:checked')?.dispatchEvent(new Event('change', { bubbles: true }));
            modal.querySelectorAll('[data-list-builder]').forEach((builder) => {
                const fuente = builder.dataset.listName === 'requisitos'
                    ? boton.dataset.tramiteRequisitos
                    : boton.dataset.tramiteProceso;
                builder.dataset.listInicial = fuente || '[]';
                builder.dispatchEvent(new Event('list-builder:reset'));
            });
            const faq = modal.querySelector('[data-faq-builder]');
            faq.dataset.faqInicial = boton.dataset.tramitePreguntasFrecuentes || '[]';
            faq.dispatchEvent(new Event('faq-builder:reset'));
        }

        if (boton.dataset.modalOpen === 'estado-editar') {
            const formulario = modal.querySelector('#estado-editar-form');

            formulario.action = boton.dataset.estadoUpdateUrl;
            formulario.querySelector('[name="update_url"]').value = boton.dataset.estadoUpdateUrl;
            formulario.querySelector('[name="clave_visual"]').value = boton.dataset.estadoClave;
            modal.querySelector('#estado-editar-clave').value = boton.dataset.estadoClave;
            modal.querySelector('#estado-editar-nombre').value = boton.dataset.estadoNombre;
            modal.querySelector('#estado-editar-descripcion').value = boton.dataset.estadoDescripcion;
            modal.querySelector('#estado-editar-orden').value = boton.dataset.estadoOrden;
            modal.querySelector('#estado-editar-final').checked = boton.dataset.estadoFinal === '1';
            modal.querySelector('#estado-editar-activo').checked = boton.dataset.estadoActivo === '1';
        }

        if (boton.dataset.modalOpen === 'formulario-estado-editar') {
            const formulario = modal.querySelector('#formulario-estado-editar-form');

            formulario.action = boton.dataset.feUpdateUrl;
            formulario.querySelector('[name="update_url"]').value = boton.dataset.feUpdateUrl;
            formulario.querySelector('[name="clave_visual"]').value = boton.dataset.feClave;
            modal.querySelector('#fe-editar-clave').value = boton.dataset.feClave;
            modal.querySelector('#fe-editar-nombre').value = boton.dataset.feNombre;
            modal.querySelector('#fe-editar-descripcion').value = boton.dataset.feDescripcion;
            modal.querySelector('#fe-editar-orden').value = boton.dataset.feOrden;
            modal.querySelector('#fe-editar-activo').checked = boton.dataset.feActivo === '1';
        }

        if (boton.dataset.catalogUpdateUrl) {
            const formulario = modal.querySelector('form');
            const prefijo = boton.dataset.modalOpen;

            formulario.action = boton.dataset.catalogUpdateUrl;
            formulario.querySelector('[name="update_url"]').value = boton.dataset.catalogUpdateUrl;
            formulario.querySelector('[name="clave_visual"]').value = boton.dataset.catalogClave;
            modal.querySelector(`#${prefijo}-clave`).value = boton.dataset.catalogClave;
            modal.querySelector(`#${prefijo}-nombre`).value = boton.dataset.catalogNombre;
            modal.querySelector(`#${prefijo}-orden`).value = boton.dataset.catalogOrden;
            modal.querySelector(`#${prefijo}-activo`).checked = boton.dataset.catalogActivo === '1';

            const descripcion = modal.querySelector(`#${prefijo}-descripcion`);
            if (descripcion) descripcion.value = boton.dataset.catalogDescripcion;
        }

        modal.showModal();
    });
});

document.querySelectorAll('form').forEach((formulario) => {
    const radios = formulario.querySelectorAll('[name="modalidad"]');
    const contenedor = formulario.querySelector('[data-expiracion-contenedor]');

    if (!radios.length || !contenedor) return;

    const actualizar = () => {
        const seleccion = formulario.querySelector('[name="modalidad"]:checked');
        contenedor.classList.toggle('hidden', seleccion?.value === 'PRESENCIAL');
    };

    radios.forEach((radio) => radio.addEventListener('change', actualizar));
    actualizar();
});

const leerJson = (valor, respaldo = []) => {
    try {
        const datos = JSON.parse(valor || '[]');
        return Array.isArray(datos) ? datos : respaldo;
    } catch {
        return respaldo;
    }
};

document.querySelectorAll('[data-list-builder]').forEach((builder) => {
    const input = builder.querySelector('[data-list-input]');
    const agregar = builder.querySelector('[data-list-add]');
    const lista = builder.querySelector('[data-list-items]');
    const ocultos = builder.querySelector('[data-list-hidden]');
    const nombre = builder.dataset.listName;
    let elementos = leerJson(builder.dataset.listInicial).filter((elemento) => typeof elemento === 'string' && elemento.trim());

    const renderizar = () => {
        lista.replaceChildren();
        ocultos.replaceChildren();
        elementos.forEach((elemento, indice) => {
            const fila = document.createElement('li');
            fila.className = 'flex items-center justify-between gap-3 rounded-lg bg-neza-canvas px-3 py-2 text-sm text-neza-ink';
            fila.append(document.createTextNode(elemento));
            const eliminar = document.createElement('button');
            eliminar.type = 'button';
            eliminar.className = 'shrink-0 font-semibold text-rose-700 hover:text-rose-900';
            eliminar.textContent = 'Eliminar';
            eliminar.setAttribute('aria-label', `Eliminar ${elemento}`);
            eliminar.addEventListener('click', () => { elementos.splice(indice, 1); renderizar(); });
            fila.append(eliminar);
            lista.append(fila);
            const oculto = document.createElement('input');
            oculto.type = 'hidden';
            oculto.name = `${nombre}[]`;
            oculto.value = elemento;
            ocultos.append(oculto);
        });
    };
    const agregarElemento = () => {
        const valor = input.value.trim();
        if (!valor) return;
        elementos.push(valor);
        input.value = '';
        renderizar();
        input.focus();
    };
    agregar.addEventListener('click', agregarElemento);
    input.addEventListener('keydown', (evento) => {
        if (evento.key === 'Enter') { evento.preventDefault(); agregarElemento(); }
    });
    builder.addEventListener('list-builder:reset', () => { elementos = leerJson(builder.dataset.listInicial).filter((elemento) => typeof elemento === 'string' && elemento.trim()); renderizar(); });
    renderizar();
});

document.querySelectorAll('[data-faq-builder]').forEach((builder) => {
    const pregunta = builder.querySelector('[data-faq-question]');
    const respuesta = builder.querySelector('[data-faq-answer]');
    const agregar = builder.querySelector('[data-faq-add]');
    const lista = builder.querySelector('[data-faq-items]');
    const ocultos = builder.querySelector('[data-faq-hidden]');
    let elementos = leerJson(builder.dataset.faqInicial).filter((elemento) => elemento?.pregunta && elemento?.respuesta);

    const renderizar = () => {
        lista.replaceChildren();
        ocultos.replaceChildren();
        elementos.forEach((elemento, indice) => {
            const tarjeta = document.createElement('article');
            tarjeta.className = 'rounded-lg bg-neza-canvas p-3 text-sm';
            const titulo = document.createElement('p'); titulo.className = 'font-semibold text-neza-ink'; titulo.textContent = elemento.pregunta;
            const texto = document.createElement('p'); texto.className = 'mt-1 text-neza-muted'; texto.textContent = elemento.respuesta;
            const eliminar = document.createElement('button'); eliminar.type = 'button'; eliminar.className = 'mt-2 font-semibold text-rose-700 hover:text-rose-900'; eliminar.textContent = 'Eliminar';
            eliminar.addEventListener('click', () => { elementos.splice(indice, 1); renderizar(); });
            tarjeta.append(titulo, texto, eliminar); lista.append(tarjeta);
            ['pregunta', 'respuesta'].forEach((campo) => { const oculto = document.createElement('input'); oculto.type = 'hidden'; oculto.name = `preguntas_frecuentes[${indice}][${campo}]`; oculto.value = elemento[campo]; ocultos.append(oculto); });
        });
    };
    const agregarElemento = () => {
        if (!pregunta.value.trim() || !respuesta.value.trim()) return;
        elementos.push({ pregunta: pregunta.value.trim(), respuesta: respuesta.value.trim() });
        pregunta.value = ''; respuesta.value = ''; renderizar(); pregunta.focus();
    };
    agregar.addEventListener('click', agregarElemento);
    builder.addEventListener('faq-builder:reset', () => { elementos = leerJson(builder.dataset.faqInicial).filter((elemento) => elemento?.pregunta && elemento?.respuesta); renderizar(); });
    renderizar();
});

document.querySelectorAll('[data-modal-close]').forEach((boton) => {
    boton.addEventListener('click', () => document.getElementById(boton.dataset.modalClose)?.close());
});

const modalDocumento = document.querySelector('#documento-previsualizador');
const marcoDocumento = document.querySelector('#documento-previsualizador-marco');
const tituloDocumento = document.querySelector('#documento-previsualizador-titulo');

if (modalDocumento && marcoDocumento && tituloDocumento) {
    document.querySelectorAll('[data-document-preview]').forEach((boton) => {
        boton.addEventListener('click', () => {
            tituloDocumento.textContent = boton.dataset.documentName || 'Previsualización del documento';
            marcoDocumento.src = boton.dataset.previewUrl || 'about:blank';
            modalDocumento.showModal();
        });
    });

    const cerrarPrevisualizador = () => {
        marcoDocumento.src = 'about:blank';
        modalDocumento.close();
    };

    modalDocumento.querySelector('[data-document-preview-close]')?.addEventListener('click', cerrarPrevisualizador);
    modalDocumento.addEventListener('cancel', () => {
        marcoDocumento.src = 'about:blank';
    });
}

document.querySelectorAll('dialog[open]').forEach((modal) => {
    modal.removeAttribute('open');
    modal.showModal();
});

const departamentoInput = document.querySelector('[data-departamento-input]');
const departamentoIdInput = document.querySelector('[data-departamento-id]');
const subdepartamentoContenedor = document.querySelector('#subdepartamento-contenedor');
const subdepartamentoInput = document.querySelector('[data-subdepartamento-input]');
const subdepartamentoIdInput = document.querySelector('[data-subdepartamento-id]');
const subdepartamentosDestino = document.querySelector('#subdepartamentos-accesos');

if (departamentoInput && departamentoIdInput && subdepartamentoContenedor && subdepartamentoInput && subdepartamentoIdInput && subdepartamentosDestino) {
    const opcionesDepartamento = [...document.querySelectorAll('#departamentos-accesos option')];
    const opcionesSubdepartamento = [...document.querySelectorAll('#subdepartamentos-origen option')];

    const sincronizarDepartamento = (preservarSeleccionInicial = false) => {
        let departamento = opcionesDepartamento.find((opcion) => opcion.value === departamentoInput.value);

        if (!departamento && preservarSeleccionInicial && departamentoIdInput.value) {
            departamento = opcionesDepartamento.find((opcion) => opcion.dataset.departamentoId === departamentoIdInput.value);
            if (departamento) departamentoInput.value = departamento.value;
        }
        const departamentoId = departamento?.dataset.departamentoId ?? '';
        const subdepartamentos = opcionesSubdepartamento.filter((opcion) => opcion.dataset.parentId === departamentoId);

        departamentoIdInput.value = departamentoId;
        subdepartamentosDestino.replaceChildren(...subdepartamentos.map((opcion) => opcion.cloneNode(true)));
        subdepartamentoContenedor.classList.toggle('hidden', subdepartamentos.length === 0);

        if (subdepartamentos.length === 0) {
            subdepartamentoInput.value = '';
            subdepartamentoIdInput.value = '';
        }
    };

    const sincronizarSubdepartamento = (preservarSeleccionInicial = false) => {
        let subdepartamento = [...subdepartamentosDestino.options]
            .find((opcion) => opcion.value === subdepartamentoInput.value);

        if (!subdepartamento && preservarSeleccionInicial && subdepartamentoIdInput.value) {
            subdepartamento = [...subdepartamentosDestino.options]
                .find((opcion) => opcion.dataset.subdepartamentoId === subdepartamentoIdInput.value);
            if (subdepartamento) subdepartamentoInput.value = subdepartamento.value;
        }

        subdepartamentoIdInput.value = subdepartamento?.dataset.subdepartamentoId ?? '';
    };

    departamentoInput.addEventListener('input', () => sincronizarDepartamento());
    subdepartamentoInput.addEventListener('input', () => sincronizarSubdepartamento());
    sincronizarDepartamento(true);
    sincronizarSubdepartamento(true);
}

document.querySelectorAll('[data-opciones-builder]').forEach((builder) => {
    const formulario = builder.closest('form');
    const tipo = formulario?.querySelector('[data-tipo-campo]');
    const input = builder.querySelector('[data-opcion-input]');
    const botonAgregar = builder.querySelector('[data-agregar-opcion]');
    const lista = builder.querySelector('[data-opciones-lista]');
    const valor = builder.querySelector('[data-opciones-valor]');
    const tiposConOpciones = ['SELECT', 'MULTISELECT', 'RADIO'];

    if (!tipo || !input || !botonAgregar || !lista || !valor) {
        return;
    }

    let opciones = valor.value.split('\n').map((opcion) => opcion.trim()).filter(Boolean);

    const renderizarOpciones = () => {
        valor.value = opciones.join('\n');
        lista.replaceChildren();

        opciones.forEach((opcion, indice) => {
            const elemento = document.createElement('span');
            elemento.className = 'inline-flex items-center gap-2 rounded-full bg-neza-wine-soft px-3 py-1 text-sm text-neza-wine';
            elemento.append(document.createTextNode(opcion));

            const eliminar = document.createElement('button');
            eliminar.type = 'button';
            eliminar.className = 'text-xs font-semibold hover:text-rose-700';
            eliminar.setAttribute('aria-label', `Eliminar opción ${opcion}`);
            eliminar.textContent = 'Eliminar';
            eliminar.addEventListener('click', () => {
                opciones.splice(indice, 1);
                renderizarOpciones();
            });

            elemento.append(eliminar);
            lista.append(elemento);
        });
    };

    const agregarOpcion = () => {
        const opcion = input.value.trim();

        if (!opcion || opciones.includes(opcion)) {
            return;
        }

        opciones.push(opcion);
        input.value = '';
        renderizarOpciones();
    };

    const actualizarVisibilidad = () => {
        const mostrar = tiposConOpciones.includes(tipo.value);
        builder.classList.toggle('hidden', !mostrar);

        if (!mostrar) {
            opciones = [];
            renderizarOpciones();
        }
    };

    input.addEventListener('keydown', (evento) => {
        if (evento.key === 'Enter') {
            evento.preventDefault();
            agregarOpcion();
        }
    });
    botonAgregar.addEventListener('click', agregarOpcion);
    tipo.addEventListener('change', actualizarVisibilidad);

    renderizarOpciones();
    actualizarVisibilidad();
});

document.querySelectorAll('[data-plantillas-pregunta]').forEach((contenedor) => {
    const formulario = contenedor.closest('form');
    const etiqueta = formulario?.querySelector('input[name="etiqueta"]');
    const tipo = formulario?.querySelector('[data-tipo-campo]');

    if (!formulario || !etiqueta || !tipo) {
        return;
    }

    contenedor.querySelectorAll('button[data-etiqueta][data-tipo]').forEach((boton) => {
        boton.addEventListener('click', () => {
            etiqueta.value = boton.dataset.etiqueta;
            tipo.value = boton.dataset.tipo;
            tipo.dispatchEvent(new Event('change', { bubbles: true }));
            etiqueta.focus();
        });
    });
});

document.querySelectorAll('[data-orden-campos]').forEach((constructor) => {
    const listas = [...constructor.querySelectorAll('[data-campos-lista]')];
    const url = constructor.dataset.ordenUrl;
    const token = constructor.dataset.csrfToken;
    let campoArrastrado = null;
    const tarjetasDe = (lista) => [...lista.children].filter((elemento) => elemento.matches('[data-campo-id]'));

    const posicionPosterior = (lista, cursorY) => tarjetasDe(lista).find((tarjeta) => {
        const rectangulo = tarjeta.getBoundingClientRect();
        return cursorY < rectangulo.top + rectangulo.height / 2;
    });

    const guardarOrden = async () => {
        const campos = listas.flatMap((lista) => tarjetasDe(lista).map((tarjeta, indice) => ({ id: tarjeta.dataset.campoId, seccion_id: lista.dataset.seccionId, orden: indice + 1 })));
        const respuesta = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token }, body: JSON.stringify({ campos }) });
        if (!respuesta.ok) window.location.reload();
    };

    constructor.querySelectorAll('[data-campo-id]').forEach((tarjeta) => {
        tarjeta.addEventListener('dragstart', (evento) => {
            if (evento.target.closest('button, input, select, textarea, summary, form')) {
                evento.preventDefault();
                return;
            }
            campoArrastrado = tarjeta;
            tarjeta.classList.add('opacity-50');
            evento.dataTransfer.effectAllowed = 'move';
        });
        tarjeta.addEventListener('dragend', () => {
            tarjeta.classList.remove('opacity-50');
            campoArrastrado = null;
        });
    });

    listas.forEach((lista) => {
        lista.addEventListener('dragover', (evento) => {
            if (!campoArrastrado) return;
            evento.preventDefault();
            lista.querySelector('[data-lista-vacia]')?.remove();
            const posterior = posicionPosterior(lista, evento.clientY);
            if (posterior) lista.insertBefore(campoArrastrado, posterior);
            else lista.append(campoArrastrado);
        });
        lista.addEventListener('drop', async (evento) => {
            if (!campoArrastrado) return;
            evento.preventDefault();
            await guardarOrden();
        });
    });
});

document.querySelectorAll('[data-formulario-preview]').forEach((vista) => {
document.querySelectorAll('[data-domicilio]').forEach((domicilio) => {
    const codigoPostal = domicilio.querySelector('[data-domicilio-campo="codigo_postal"]');
    const colonia = domicilio.querySelector('[data-domicilio-campo="colonia"]');
    const calle = domicilio.querySelector('[data-domicilio-campo="calle"]');
    const numero = domicilio.querySelector('[data-domicilio-campo="numero"]');
    const colonias = domicilio.querySelector('[data-domicilio-colonias]');
    const calles = domicilio.querySelector('[data-domicilio-calles]');
    const numeros = domicilio.querySelector('[data-domicilio-numeros]');
    const latitud = domicilio.querySelector('[data-domicilio-latitud]');
    const longitud = domicilio.querySelector('[data-domicilio-longitud]');
    const estado = domicilio.querySelector('[data-domicilio-estado]');
    const url = domicilio.dataset.indiceUrl;
    let indice;
    let cargaIndice;
    let codigoPostalAnterior = '';

    if (!codigoPostal || !colonia || !calle || !numero || !colonias || !calles || !numeros || !latitud || !longitud || !estado || !url) {
        return;
    }

    const normalizar = (valor) => String(valor).trim().toLocaleUpperCase();
    const opciones = (lista, valores) => lista.replaceChildren(...valores.map((valor) => {
        const opcion = document.createElement('option');
        opcion.value = String(valor);
        return opcion;
    }));
    const limpiarCoordenadas = () => {
        latitud.value = '';
        longitud.value = '';
    };
    const cargarIndice = async () => {
        if (indice) return indice;
        if (!cargaIndice) {
            estado.textContent = 'Cargando colonias, calles y coordenadas disponibles...';
            cargaIndice = fetch(url, { headers: { Accept: 'application/json' } })
                .then((respuesta) => {
                    if (!respuesta.ok) throw new Error('No fue posible consultar el catalogo de domicilios.');
                    return respuesta.json();
                })
                .then((datos) => {
                    indice = datos;
                    return indice;
                });
        }

        return cargaIndice;
    };
    const actualizarCalles = () => {
        const coloniasDelCodigoPostal = indice?.[codigoPostal.value.trim()];
        const nombreColonia = Object.keys(coloniasDelCodigoPostal ?? {})
            .find((nombre) => normalizar(nombre) === normalizar(colonia.value));
        const callesDisponibles = nombreColonia ? Object.keys(coloniasDelCodigoPostal[nombreColonia]) : [];

        opciones(calles, callesDisponibles);
        opciones(numeros, []);
        numero.value = '';
        limpiarCoordenadas();
        estado.textContent = nombreColonia
            ? `${callesDisponibles.length} calles disponibles para ${nombreColonia}.`
            : 'Selecciona una colonia de la lista para consultar sus calles.';
    };
    const actualizarNumeros = () => {
        const coloniasDelCodigoPostal = indice?.[codigoPostal.value.trim()];
        const nombreColonia = Object.keys(coloniasDelCodigoPostal ?? {})
            .find((nombre) => normalizar(nombre) === normalizar(colonia.value));
        const callesDeColonia = nombreColonia ? coloniasDelCodigoPostal[nombreColonia] : {};
        const nombreCalle = Object.keys(callesDeColonia)
            .find((nombre) => normalizar(nombre) === normalizar(calle.value));
        const domiciliosDisponibles = nombreCalle ? callesDeColonia[nombreCalle] : [];

        opciones(numeros, domiciliosDisponibles.map((domicilioDisponible) => domicilioDisponible.numero));
        numero.value = '';
        limpiarCoordenadas();
        estado.textContent = nombreCalle
            ? `${domiciliosDisponibles.length} numeros disponibles para ${nombreCalle}.`
            : 'Selecciona una calle de la lista para consultar sus numeros.';
    };
    const actualizarCoordenadas = () => {
        const coloniasDelCodigoPostal = indice?.[codigoPostal.value.trim()];
        const nombreColonia = Object.keys(coloniasDelCodigoPostal ?? {})
            .find((nombre) => normalizar(nombre) === normalizar(colonia.value));
        const callesDeColonia = nombreColonia ? coloniasDelCodigoPostal[nombreColonia] : {};
        const nombreCalle = Object.keys(callesDeColonia)
            .find((nombre) => normalizar(nombre) === normalizar(calle.value));
        const domicilioEncontrado = (nombreCalle ? callesDeColonia[nombreCalle] : [])
            .find((domicilioDisponible) => normalizar(domicilioDisponible.numero) === normalizar(numero.value));

        latitud.value = domicilioEncontrado?.latitud ?? '';
        longitud.value = domicilioEncontrado?.longitud ?? '';
        estado.textContent = domicilioEncontrado
            ? 'Ubicacion encontrada y coordenadas asignadas.'
            : 'No se encontro una coordenada exacta para el numero ingresado.';
    };
    const actualizarColonias = async () => {
        const cp = codigoPostal.value.trim();

        if (!/^\d{5}$/.test(cp)) {
            opciones(colonias, []);
            opciones(calles, []);
            estado.textContent = 'Ingresa un c?digo postal de cinco d?gitos para consultar colonias.';
            opciones(numeros, []);
            limpiarCoordenadas();
            return;
        }

        try {
            const datos = await cargarIndice();
            const coloniasDisponibles = Object.keys(datos[cp] ?? {});
            opciones(colonias, coloniasDisponibles);
            opciones(calles, []);
            opciones(numeros, []);
            limpiarCoordenadas();
            estado.textContent = coloniasDisponibles.length
                ? `${coloniasDisponibles.length} colonias disponibles para el c?digo postal ${cp}.`
                : 'No se encontraron colonias para el c?digo postal ingresado.';
        } catch {
            opciones(colonias, []);
            opciones(calles, []);
            estado.textContent = 'No fue posible cargar el cat?logo de domicilios. Intenta nuevamente.';
            opciones(numeros, []);
            limpiarCoordenadas();
        }
    };

    codigoPostal.addEventListener('input', () => {
        if (codigoPostal.value.trim() !== codigoPostalAnterior) {
            colonia.value = '';
            calle.value = '';
            codigoPostalAnterior = codigoPostal.value.trim();
            numero.value = '';
        }
        actualizarColonias();
    });
    colonia.addEventListener('input', actualizarCalles);
    calle.addEventListener('input', actualizarNumeros);
    numero.addEventListener('input', actualizarCoordenadas);
});
    const obtenerValor = (campoId) => {
        const controles = [...vista.querySelectorAll(`[data-campo-control="${campoId}"]`)];
        const control = controles[0];

        if (!control) {
            return '';
        }

        if (control.type === 'radio') {
            return controles.find((elemento) => elemento.checked)?.dataset.valorCondicional ?? '';
        }

        if (control.type === 'checkbox') {
            return control.checked ? 'true' : '';
        }

        if (control.tagName === 'SELECT') {
            return [...control.selectedOptions]
                .map((opcion) => opcion.dataset.valorCondicional ?? opcion.text)
                .join(', ');
        }

        return control.value ?? '';
    };

    const comparar = (valor, operador, esperado) => {
        const actual = String(valor).trim().toLocaleLowerCase();
        const comparado = String(esperado ?? '').trim().toLocaleLowerCase();

        switch (operador) {
            case 'EQUALS': return actual === comparado;
            case 'NOT_EQUALS': return actual !== comparado;
            case 'CONTAINS': return actual.includes(comparado);
            case 'GREATER_THAN': return Number(actual) > Number(comparado);
            case 'LESS_THAN': return Number(actual) < Number(comparado);
            case 'IS_EMPTY': return actual === '';
            case 'IS_NOT_EMPTY': return actual !== '';
            default: return false;
        }
    };

    const aplicarCondiciones = () => {
        vista.querySelectorAll('[data-campo-destino]').forEach((destino) => {
            const gruposDestino = (destino.dataset.gruposDestino ?? '').split(',').filter(Boolean);
            const condicionesGrupo = [...vista.querySelectorAll('[data-grupo-condicion]')]
                .filter((condicion) => gruposDestino.includes(condicion.dataset.grupoDestino));
            const condiciones = [...destino.querySelectorAll('[data-condicion]'), ...condicionesGrupo];
            const evaluar = (accion) => condiciones.filter((condicion) => condicion.dataset.accion === accion)
                .map((condicion) => comparar(obtenerValor(condicion.dataset.origen), condicion.dataset.operador, condicion.dataset.valor));
            const mostrar = evaluar('SHOW');
            const ocultar = evaluar('HIDE');
            const requerir = evaluar('REQUIRE');
            const opcional = evaluar('OPTIONAL');
            const visible = (mostrar.length === 0 || mostrar.every(Boolean)) && !ocultar.some(Boolean);
            let obligatorio = destino.dataset.requeridoInicial === 'true';

            if (requerir.length > 0) obligatorio = requerir.every(Boolean);
            if (opcional.some(Boolean)) obligatorio = false;

            destino.classList.toggle('hidden', !visible);
            destino.querySelectorAll('[data-campo-control]').forEach((control) => {
                control.required = obligatorio && visible;
                control.setAttribute('aria-required', obligatorio && visible ? 'true' : 'false');
            });
        });
    };

    vista.querySelectorAll('[data-campo-control]').forEach((control) => {
        control.addEventListener('input', aplicarCondiciones);
        control.addEventListener('change', aplicarCondiciones);
    });

    aplicarCondiciones();
});
