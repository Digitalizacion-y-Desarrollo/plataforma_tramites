import './bootstrap';

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
