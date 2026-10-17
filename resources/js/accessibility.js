const clavePreferencias = 'neza-accesibilidad-v1';
const valoresPermitidos = {
    text: ['normal', 'large', 'xlarge'],
    contrast: ['normal', 'high'],
    motion: ['system', 'reduced'],
};
const predeterminados = { text: 'normal', contrast: 'normal', motion: 'system' };

const normalizarPreferencias = (valor) => Object.fromEntries(
    Object.entries(predeterminados).map(([nombre, predeterminado]) => [
        nombre,
        valoresPermitidos[nombre].includes(valor?.[nombre]) ? valor[nombre] : predeterminado,
    ]),
);

const leerPreferencias = () => {
    try {
        return normalizarPreferencias(JSON.parse(localStorage.getItem(clavePreferencias)));
    } catch {
        return { ...predeterminados };
    }
};

const guardarPreferencias = (preferencias) => {
    try {
        localStorage.setItem(clavePreferencias, JSON.stringify(preferencias));
    } catch {
        // El panel sigue funcionando durante la visita si el almacenamiento está bloqueado.
    }
};

const dialogo = document.querySelector('#accessibility-dialog');
let preferencias = leerPreferencias();
let botonDeApertura = null;

const aplicarPreferencias = () => {
    const raiz = document.documentElement;
    raiz.dataset.accessibilityText = preferencias.text;
    raiz.dataset.accessibilityContrast = preferencias.contrast;
    raiz.dataset.accessibilityMotion = preferencias.motion;

    dialogo?.querySelectorAll('input[type="radio"]').forEach((control) => {
        const nombre = control.name.replace('accessibility-', '');
        control.checked = preferencias[nombre] === control.value;
    });
};

aplicarPreferencias();

document.querySelectorAll('[data-accessibility-open]').forEach((boton) => {
    boton.addEventListener('click', () => {
        if (!dialogo) return;
        botonDeApertura = boton;
        dialogo.showModal();
    });
});

dialogo?.querySelectorAll('[data-accessibility-close]').forEach((boton) => {
    boton.addEventListener('click', () => dialogo.close());
});

dialogo?.addEventListener('close', () => botonDeApertura?.focus());

dialogo?.addEventListener('change', (evento) => {
    if (!(evento.target instanceof HTMLInputElement) || evento.target.type !== 'radio') return;
    const nombre = evento.target.name.replace('accessibility-', '');
    if (!valoresPermitidos[nombre]?.includes(evento.target.value)) return;

    preferencias = { ...preferencias, [nombre]: evento.target.value };
    aplicarPreferencias();
    guardarPreferencias(preferencias);
});

dialogo?.querySelector('[data-accessibility-reset]')?.addEventListener('click', () => {
    preferencias = { ...predeterminados };
    aplicarPreferencias();
    try {
        localStorage.removeItem(clavePreferencias);
    } catch {
        // Los valores predeterminados siguen aplicados en la página actual.
    }
});

window.addEventListener('storage', (evento) => {
    if (evento.key !== clavePreferencias) return;
    preferencias = leerPreferencias();
    aplicarPreferencias();
});
