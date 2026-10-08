export function baseUrl() {
    const declarado = document.documentElement.dataset.base || '';

    if (declarado) {
        return declarado.replace(/\/$/, '');
    }

    const ruta = window.location.pathname;
    const marca = '/fatfree-app/public';
    const posicion = ruta.indexOf(marca);

    if (posicion !== -1) {
        return ruta.slice(0, posicion + marca.length);
    }

    return '';
}

export function apiUrl(ruta) {
    const limpia = ruta.startsWith('/') ? ruta : `/${ruta}`;

    return `${baseUrl()}${limpia}`;
}

export function fotoUrl(ruta) {
    return `${baseUrl()}/${ruta}`;
}

export function esc(texto) {
    return String(texto ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');
}

export function mostrarAviso(elemento, texto, tipo = 'ok') {
    if (elemento) {
        elemento.hidden = !texto;
        elemento.textContent = texto;
        elemento.className = `aviso aviso-${tipo}`;
    }

    if (texto) {
        mostrarToast(texto, tipo);
    }
}

export function mostrarToast(texto, tipo = 'ok') {
    const caja = document.querySelector('#alertas');

    if (!caja) {
        return;
    }

    caja.replaceChildren();

    const item = document.createElement('div');
    item.className = `alerta alerta-${tipo}`;
    item.innerHTML = `<p>${esc(texto)}</p><button type="button" class="alerta-cerrar" aria-label="Cerrar">Cerrar</button>`;
    item.querySelector('button').addEventListener('click', () => item.remove());
    caja.append(item);
    window.setTimeout(() => item.remove(), 4000);
}

export function confirmar(mensaje) {
    const fondo = document.querySelector('#confirmacion');
    const texto = document.querySelector('#confirmacion-texto');
    const aceptar = document.querySelector('#confirmacion-aceptar');
    const cancelar = document.querySelector('#confirmacion-cancelar');

    if (!fondo || !texto || !aceptar || !cancelar) {
        return Promise.resolve(window.confirm(mensaje));
    }

    texto.textContent = mensaje;
    fondo.hidden = false;
    aceptar.focus();

    return new Promise((resolve) => {
        const cerrar = (valor) => {
            fondo.hidden = true;
            aceptar.removeEventListener('click', onAceptar);
            cancelar.removeEventListener('click', onCancelar);
            fondo.removeEventListener('click', onFondo);
            resolve(valor);
        };
        const onAceptar = () => cerrar(true);
        const onCancelar = () => cerrar(false);
        const onFondo = (evento) => {
            if (evento.target === fondo) {
                cerrar(false);
            }
        };

        aceptar.addEventListener('click', onAceptar);
        cancelar.addEventListener('click', onCancelar);
        fondo.addEventListener('click', onFondo);
    });
}

async function leerJson(respuesta) {
    const datos = await respuesta.json();

    if (!respuesta.ok) {
        throw new Error(datos.error || `Error HTTP ${respuesta.status}`);
    }

    return datos;
}

export async function apiGet(ruta) {
    return leerJson(await fetch(apiUrl(ruta), { cache: 'no-store' }));
}

export async function apiPost(ruta, cuerpo) {
    return leerJson(await fetch(apiUrl(ruta), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(cuerpo),
    }));
}

export async function apiForm(ruta, formulario) {
    return leerJson(await fetch(apiUrl(ruta), {
        method: 'POST',
        body: formulario,
    }));
}

export function llenarSelect(select, filas, valor, etiqueta, vacio = '') {
    const actual = select.value;
    select.innerHTML = vacio ? `<option value="">${esc(vacio)}</option>` : '';

    for (const fila of filas) {
        const option = document.createElement('option');
        option.value = String(fila[valor]);
        option.textContent = typeof etiqueta === 'function' ? etiqueta(fila) : fila[etiqueta];
        select.append(option);
    }

    if ([...select.options].some((option) => option.value === actual)) {
        select.value = actual;
    }
}
