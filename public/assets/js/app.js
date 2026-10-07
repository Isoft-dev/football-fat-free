import {
    apiForm,
    apiGet,
    apiPost,
    confirmar,
    esc,
    fotoUrl,
    llenarSelect,
    mostrarAviso,
} from './api.js?v=5';

const pagina = document.body.dataset.page;
const POR_PAGINA = 8;

if (pagina === 'inicio') {
    iniciarInicio();
} else if (pagina === 'equipos') {
    iniciarEquipos();
} else if (pagina === 'jornadas') {
    iniciarJornadas();
} else if (pagina === 'jugadores') {
    iniciarJugadores();
} else if (pagina === 'ficha') {
    iniciarFicha();
} else if (pagina === 'goles') {
    iniciarGoles();
} else if (pagina === 'incidencias') {
    iniciarIncidencias();
} else if (pagina === 'reporte-arbitro') {
    iniciarReporteArbitro();
} else if (pagina === 'reporte-incidencias') {
    iniciarReporteIncidencias();
} else if (pagina === 'reporte-goleadores') {
    iniciarGoleadores();
}

function hoyIso() {
    const hoy = new Date();

    return [
        hoy.getFullYear(),
        String(hoy.getMonth() + 1).padStart(2, '0'),
        String(hoy.getDate()).padStart(2, '0'),
    ].join('-');
}

function limpiarBusqueda(tbody) {
    const wrap = tbody && tbody.closest('.tabla-wrap');
    const barra = wrap && wrap.previousElementSibling;

    if (barra && barra.classList.contains('lista-barra')) {
        const input = barra.querySelector('input');

        if (input) {
            input.value = '';
        }
    }
}

function activarTabla(tbody, porPagina = POR_PAGINA) {
    const wrap = tbody && tbody.closest('.tabla-wrap');

    if (!wrap) {
        return;
    }

    let barra = wrap.previousElementSibling;

    if (!barra || !barra.classList.contains('lista-barra')) {
        barra = document.createElement('div');
        barra.className = 'lista-barra';
        barra.innerHTML = `
            <label class="lista-buscar">
                <span>Buscar</span>
                <input type="search" placeholder="Nombre, equipo, fecha…">
            </label>
            <p class="lista-conteo" hidden></p>
            <div class="lista-paginacion" hidden></div>
        `;
        wrap.before(barra);
        barra.querySelector('input').addEventListener('input', () => {
            tbody.dataset.pagina = '1';
            paginarTabla(tbody, porPagina);
        });
        barra.querySelector('.lista-paginacion').addEventListener('click', (evento) => {
            const boton = evento.target.closest('[data-pagina]');

            if (!boton || boton.disabled) {
                return;
            }

            tbody.dataset.pagina = boton.dataset.pagina;
            paginarTabla(tbody, porPagina);
        });
    }

    tbody.dataset.pagina = '1';
    paginarTabla(tbody, porPagina);
}

function paginarTabla(tbody, porPagina) {
    const wrap = tbody.closest('.tabla-wrap');
    const barra = wrap.previousElementSibling;
    const busqueda = (barra.querySelector('input')?.value || '').trim().toLowerCase();
    const filas = [...tbody.querySelectorAll('tr')].filter((fila) => (
        !fila.classList.contains('fila-vacia') && !fila.classList.contains('fila-sin-resultados')
    ));
    const vacia = tbody.querySelector('.fila-vacia');
    const coinciden = filas.filter((fila) => !busqueda || fila.textContent.toLowerCase().includes(busqueda));
    const total = coinciden.length;
    const paginas = Math.max(1, Math.ceil(total / porPagina));
    let paginaActual = Number(tbody.dataset.pagina) || 1;

    if (paginaActual > paginas) {
        paginaActual = paginas;
    }

    tbody.dataset.pagina = String(paginaActual);

    const desde = (paginaActual - 1) * porPagina;
    const hasta = desde + porPagina;

    filas.forEach((fila) => {
        const indice = coinciden.indexOf(fila);
        const visible = indice >= 0 && indice >= desde && indice < hasta;
        fila.hidden = !visible;
        fila.classList.toggle('fila-alt', visible && indice % 2 === 1);
    });

    if (vacia) {
        vacia.hidden = filas.length > 0;
    }

    let sinResultados = tbody.querySelector('.fila-sin-resultados');

    if (busqueda && total === 0) {
        if (!sinResultados) {
            const columnas = tbody.closest('table')?.querySelectorAll('thead th').length || 2;
            sinResultados = document.createElement('tr');
            sinResultados.className = 'fila-sin-resultados';
            sinResultados.innerHTML = `<td colspan="${columnas}">No hay coincidencias.</td>`;
            tbody.append(sinResultados);
        }

        sinResultados.hidden = false;
    } else if (sinResultados) {
        sinResultados.hidden = true;
    }

    const conteo = barra.querySelector('.lista-conteo');
    const paginacion = barra.querySelector('.lista-paginacion');
    const buscar = barra.querySelector('.lista-buscar');
    const hayLista = filas.length >= 5;

    barra.hidden = !hayLista;
    buscar.hidden = !hayLista;
    conteo.hidden = !hayLista;
    conteo.textContent = busqueda ? `${total} de ${filas.length}` : `${filas.length} registros`;

    if (total > porPagina) {
        paginacion.hidden = false;
        paginacion.innerHTML = `
            <button type="button" class="enlace" data-pagina="${paginaActual - 1}" ${paginaActual <= 1 ? 'disabled' : ''}>Anterior</button>
            <span>Página ${paginaActual} de ${paginas}</span>
            <button type="button" class="enlace" data-pagina="${paginaActual + 1}" ${paginaActual >= paginas ? 'disabled' : ''}>Siguiente</button>
        `;
    } else {
        paginacion.hidden = true;
        paginacion.innerHTML = '';
    }
}

async function iniciarInicio() {
    try {
        const datos = await apiGet('/api/resumen');
        document.querySelector('#n-equipos').textContent = datos.equipos;
        document.querySelector('#n-jornadas').textContent = datos.jornadas;
        document.querySelector('#n-jugadores').textContent = datos.jugadores;
        document.querySelector('#n-goles').textContent = datos.goles;
        document.querySelector('#n-incidencias').textContent = datos.incidencias;
    } catch (error) {
        document.querySelector('#n-equipos').textContent = '—';
    }
}

async function iniciarEquipos() {
    const form = document.querySelector('#form-equipo');
    const mensaje = document.querySelector('#msg-equipo');
    const cancelar = document.querySelector('#cancelar-equipo');

    async function cargar() {
        const datos = await apiGet('/api/equipos');
        const tbody = document.querySelector('#tabla-equipos');
        const equipos = Array.isArray(datos.equipos) ? datos.equipos : [];
        limpiarBusqueda(tbody);
        tbody.innerHTML = equipos.map((equipo) => `
            <tr>
                <td>${esc(equipo.EQU_Nombre)}</td>
                <td>
                    <button type="button" class="enlace" data-editar="${equipo.EQU_Equipo}" data-nombre="${esc(equipo.EQU_Nombre)}">Editar</button>
                    <button type="button" class="peligro" data-borrar="${equipo.EQU_Equipo}">Eliminar</button>
                </td>
            </tr>
        `).join('') || '<tr class="fila-vacia"><td colspan="2">No hay equipos.</td></tr>';
        activarTabla(tbody);
    }

    form.addEventListener('submit', async (evento) => {
        evento.preventDefault();
        const id = document.querySelector('#equipo-id').value;
        const cuerpo = { EQU_Nombre: document.querySelector('#EQU_Nombre').value };

        try {
            const respuesta = await apiPost(id ? `/api/equipos/${id}` : '/api/equipos', cuerpo);
            mostrarAviso(mensaje, respuesta.mensaje);
            form.reset();
            document.querySelector('#equipo-id').value = '';
            document.querySelector('#titulo-form-equipo').textContent = 'Nuevo equipo';
            cancelar.hidden = true;
            await cargar();
        } catch (error) {
            mostrarAviso(mensaje, error.message, 'error');
        }
    });

    cancelar.addEventListener('click', () => {
        form.reset();
        document.querySelector('#equipo-id').value = '';
        document.querySelector('#titulo-form-equipo').textContent = 'Nuevo equipo';
        cancelar.hidden = true;
    });

    document.querySelector('#tabla-equipos').addEventListener('click', async (evento) => {
        const editar = evento.target.closest('[data-editar]');
        const borrar = evento.target.closest('[data-borrar]');

        if (editar) {
            document.querySelector('#equipo-id').value = editar.dataset.editar;
            document.querySelector('#EQU_Nombre').value = editar.dataset.nombre;
            document.querySelector('#titulo-form-equipo').textContent = 'Editar equipo';
            cancelar.hidden = false;
        }

        if (borrar && await confirmar('¿Eliminar este equipo?')) {
            try {
                const respuesta = await apiPost(`/api/equipos/${borrar.dataset.borrar}/eliminar`, {});
                mostrarAviso(mensaje, respuesta.mensaje);
                await cargar();
            } catch (error) {
                mostrarAviso(mensaje, error.message, 'error');
            }
        }
    });

    try {
        await cargar();
    } catch (error) {
        document.querySelector('#tabla-equipos').innerHTML =
            `<tr class="fila-vacia"><td colspan="2">${esc(error.message)}</td></tr>`;
        mostrarAviso(mensaje, error.message, 'error');
    }
}

async function iniciarJornadas() {
    const form = document.querySelector('#form-jornada');
    const mensaje = document.querySelector('#msg-jornada');
    const cancelar = document.querySelector('#cancelar-jornada');

    async function cargar() {
        const datos = await apiGet('/api/jornadas');
        const tbody = document.querySelector('#tabla-jornadas');
        limpiarBusqueda(tbody);
        tbody.innerHTML = datos.jornadas.map((jornada) => `
            <tr>
                <td>${esc(jornada.JOR_Numero)}</td>
                <td>${esc(jornada.JOR_Fecha_Juego_Vista)}</td>
                <td>
                    <button type="button" class="enlace" data-editar="${jornada.JOR_Jornada}" data-numero="${jornada.JOR_Numero}" data-fecha="${jornada.JOR_Fecha_Juego}">Editar</button>
                    <button type="button" class="peligro" data-borrar="${jornada.JOR_Jornada}">Eliminar</button>
                </td>
            </tr>
        `).join('') || '<tr class="fila-vacia"><td colspan="3">No hay jornadas. Habilítelas al inicio del torneo.</td></tr>';
        activarTabla(tbody);
    }

    form.addEventListener('submit', async (evento) => {
        evento.preventDefault();
        const id = document.querySelector('#jornada-id').value;
        const cuerpo = {
            JOR_Numero: document.querySelector('#JOR_Numero').value,
            JOR_Fecha_Juego: document.querySelector('#JOR_Fecha_Juego').value,
        };

        try {
            const respuesta = await apiPost(id ? `/api/jornadas/${id}` : '/api/jornadas', cuerpo);
            mostrarAviso(mensaje, respuesta.mensaje);
            form.reset();
            document.querySelector('#jornada-id').value = '';
            document.querySelector('#titulo-form-jornada').textContent = 'Nueva jornada';
            cancelar.hidden = true;
            await cargar();
        } catch (error) {
            mostrarAviso(mensaje, error.message, 'error');
        }
    });

    cancelar.addEventListener('click', () => {
        form.reset();
        document.querySelector('#jornada-id').value = '';
        document.querySelector('#titulo-form-jornada').textContent = 'Nueva jornada';
        cancelar.hidden = true;
    });

    document.querySelector('#tabla-jornadas').addEventListener('click', async (evento) => {
        const editar = evento.target.closest('[data-editar]');
        const borrar = evento.target.closest('[data-borrar]');

        if (editar) {
            document.querySelector('#jornada-id').value = editar.dataset.editar;
            document.querySelector('#JOR_Numero').value = editar.dataset.numero;
            document.querySelector('#JOR_Fecha_Juego').value = String(editar.dataset.fecha).slice(0, 10);
            document.querySelector('#titulo-form-jornada').textContent = 'Editar jornada';
            cancelar.hidden = false;
        }

        if (borrar && await confirmar('¿Eliminar esta jornada?')) {
            try {
                const respuesta = await apiPost(`/api/jornadas/${borrar.dataset.borrar}/eliminar`, {});
                mostrarAviso(mensaje, respuesta.mensaje);
                await cargar();
            } catch (error) {
                mostrarAviso(mensaje, error.message, 'error');
            }
        }
    });

    await cargar();
}

async function iniciarJugadores() {
    const form = document.querySelector('#form-jugador');
    const mensaje = document.querySelector('#msg-jugador');
    const cancelar = document.querySelector('#cancelar-jugador');
    const equipos = await apiGet('/api/equipos');
    llenarSelect(document.querySelector('#EQU_Equipo'), equipos.equipos, 'EQU_Equipo', 'EQU_Nombre', 'Seleccione un equipo');

    const nacimiento = document.querySelector('#JUG_Fecha_Nacimiento');
    nacimiento.max = hoyIso();

    async function cargar() {
        const datos = await apiGet('/api/jugadores');
        const tbody = document.querySelector('#tabla-jugadores');
        limpiarBusqueda(tbody);
        tbody.innerHTML = datos.jugadores.map((jugador) => `
            <tr>
                <td><img class="foto" src="${esc(fotoUrl(jugador.JUG_Fotografia))}" alt=""></td>
                <td><a href="${document.documentElement.dataset.base || ''}/jugadores/${jugador.JUG_Jugador}">${esc(jugador.JUG_Nombre_Completo)}</a></td>
                <td>${esc(jugador.EQU_Nombre)}</td>
                <td class="col-opcional">${esc(jugador.JUG_Fecha_Nacimiento_Vista)}</td>
                <td>
                    <button type="button" class="enlace" data-editar="${encodeURIComponent(JSON.stringify(jugador))}">Editar</button>
                    <button type="button" class="peligro" data-borrar="${jugador.JUG_Jugador}">Eliminar</button>
                </td>
            </tr>
        `).join('') || '<tr class="fila-vacia"><td colspan="5">No hay jugadores.</td></tr>';
        activarTabla(tbody);
    }

    form.addEventListener('submit', async (evento) => {
        evento.preventDefault();
        const id = document.querySelector('#jugador-id').value;
        const datos = new FormData(form);

        try {
            const respuesta = await apiForm(id ? `/api/jugadores/${id}` : '/api/jugadores', datos);
            mostrarAviso(mensaje, respuesta.mensaje);
            form.reset();
            nacimiento.max = hoyIso();
            document.querySelector('#jugador-id').value = '';
            document.querySelector('#titulo-form-jugador').textContent = 'Nuevo jugador';
            document.querySelector('#foto-ayuda').textContent = '(obligatoria en el alta)';
            document.querySelector('#fotografia').required = true;
            cancelar.hidden = true;
            await cargar();
        } catch (error) {
            mostrarAviso(mensaje, error.message, 'error');
        }
    });

    cancelar.addEventListener('click', () => {
        form.reset();
        nacimiento.max = hoyIso();
        document.querySelector('#jugador-id').value = '';
        document.querySelector('#titulo-form-jugador').textContent = 'Nuevo jugador';
        document.querySelector('#foto-ayuda').textContent = '(obligatoria en el alta)';
        document.querySelector('#fotografia').required = true;
        cancelar.hidden = true;
    });

    document.querySelector('#fotografia').required = true;

    document.querySelector('#tabla-jugadores').addEventListener('click', async (evento) => {
        const editar = evento.target.closest('[data-editar]');
        const borrar = evento.target.closest('[data-borrar]');

        if (editar) {
            const jugador = JSON.parse(decodeURIComponent(editar.dataset.editar));
            document.querySelector('#jugador-id').value = jugador.JUG_Jugador;
            document.querySelector('#EQU_Equipo').value = jugador.EQU_Equipo;
            document.querySelector('#JUG_Primer_Nombre').value = jugador.JUG_Primer_Nombre;
            document.querySelector('#JUG_Segundo_Nombre').value = jugador.JUG_Segundo_Nombre || '';
            document.querySelector('#JUG_Primer_Apellido').value = jugador.JUG_Primer_Apellido;
            document.querySelector('#JUG_Segundo_Apellido').value = jugador.JUG_Segundo_Apellido || '';
            document.querySelector('#JUG_Fecha_Nacimiento').value = String(jugador.JUG_Fecha_Nacimiento).slice(0, 10);
            document.querySelector('#titulo-form-jugador').textContent = 'Editar jugador';
            document.querySelector('#foto-ayuda').textContent = '(opcional al editar)';
            document.querySelector('#fotografia').required = false;
            cancelar.hidden = false;
        }

        if (borrar && await confirmar('¿Eliminar este jugador?')) {
            try {
                const respuesta = await apiPost(`/api/jugadores/${borrar.dataset.borrar}/eliminar`, {});
                mostrarAviso(mensaje, respuesta.mensaje);
                await cargar();
            } catch (error) {
                mostrarAviso(mensaje, error.message, 'error');
            }
        }
    });

    await cargar();
}

async function iniciarFicha() {
    const caja = document.querySelector('#ficha-jugador');
    const id = caja.dataset.id;
    const datos = await apiGet(`/api/jugadores/${id}`);
    const jugador = datos.jugador;

    caja.innerHTML = `
        <section class="panel ficha">
            <img class="foto-lg" src="${esc(fotoUrl(jugador.JUG_Fotografia))}" alt="">
            <div>
                <p class="kicker">Plantel</p>
                <h2>${esc(jugador.JUG_Nombre_Completo)}</h2>
                <p>Equipo: ${esc(jugador.EQU_Nombre)}</p>
                <p>Nacimiento: ${esc(jugador.JUG_Fecha_Nacimiento_Vista)}</p>
            </div>
        </section>
        <section class="panel">
            <h2>Goles</h2>
            <div class="tabla-wrap">
                <table>
                    <thead><tr><th>Jornada</th><th>Fecha</th><th>Goles</th></tr></thead>
                    <tbody>
                        ${datos.goles.map((gol) => `
                            <tr>
                                <td>${esc(gol.JOR_Numero)}</td>
                                <td>${esc(gol.JOR_Fecha_Juego_Vista)}</td>
                                <td>${esc(gol.GOL_Cantidad)}</td>
                            </tr>
                        `).join('') || '<tr><td colspan="3">Sin goles.</td></tr>'}
                    </tbody>
                </table>
            </div>
        </section>
        <section class="panel">
            <h2>Incidencias</h2>
            <div class="tabla-wrap">
                <table>
                    <thead><tr><th>Fecha</th><th>Tarjeta</th><th>Descripción</th><th>Suspensión</th></tr></thead>
                    <tbody>
                        ${datos.incidencias.map((incidencia) => `
                            <tr>
                                <td>${esc(incidencia.INC_Fecha_Incidencia_Vista)}</td>
                                <td><span class="badge badge-${esc(incidencia.INC_Tipo_Tarjeta)}">${esc(incidencia.INC_Tipo_Tarjeta_Nombre || incidencia.INC_Tipo_Tarjeta)}</span></td>
                                <td>${esc(incidencia.INC_Descripcion)}</td>
                                <td>${esc(incidencia.INC_Fecha_Suspension_Vista || '—')}</td>
                            </tr>
                        `).join('') || '<tr><td colspan="4">Sin incidencias.</td></tr>'}
                    </tbody>
                </table>
            </div>
        </section>
    `;
}

async function iniciarGoles() {
    const jugadores = await apiGet('/api/jugadores');
    const jornadas = await apiGet('/api/jornadas');
    const mensaje = document.querySelector('#msg-gol');

    llenarSelect(
        document.querySelector('#JUG_Jugador'),
        jugadores.jugadores,
        'JUG_Jugador',
        (fila) => `${fila.JUG_Nombre_Completo} · ${fila.EQU_Nombre}`,
        'Seleccione un jugador'
    );
    llenarSelect(
        document.querySelector('#JOR_Jornada'),
        jornadas.jornadas,
        'JOR_Jornada',
        (fila) => `Jornada ${fila.JOR_Numero} · ${fila.JOR_Fecha_Juego_Vista}`,
        'Seleccione una jornada'
    );

    async function cargar() {
        const datos = await apiGet('/api/goles');
        const tbody = document.querySelector('#tabla-goles');
        limpiarBusqueda(tbody);
        tbody.innerHTML = datos.goles.map((gol) => `
            <tr>
                <td>${esc(gol.JOR_Numero)} (${esc(gol.JOR_Fecha_Juego_Vista)})</td>
                <td>${esc(gol.JUG_Nombre_Completo)}</td>
                <td>${esc(gol.EQU_Nombre)}</td>
                <td>${esc(gol.GOL_Cantidad)}</td>
                <td><button type="button" class="peligro" data-borrar="${gol.GOL_Gol}">Eliminar</button></td>
            </tr>
        `).join('') || '<tr class="fila-vacia"><td colspan="5">No hay goles registrados.</td></tr>';
        activarTabla(tbody);
    }

    document.querySelector('#form-gol').addEventListener('submit', async (evento) => {
        evento.preventDefault();

        try {
            const respuesta = await apiPost('/api/goles', {
                JUG_Jugador: document.querySelector('#JUG_Jugador').value,
                JOR_Jornada: document.querySelector('#JOR_Jornada').value,
                GOL_Cantidad: document.querySelector('#GOL_Cantidad').value,
            });
            mostrarAviso(mensaje, respuesta.mensaje);
            document.querySelector('#form-gol').reset();
            await cargar();
        } catch (error) {
            mostrarAviso(mensaje, error.message, 'error');
        }
    });

    document.querySelector('#tabla-goles').addEventListener('click', async (evento) => {
        const borrar = evento.target.closest('[data-borrar]');

        if (borrar && await confirmar('¿Eliminar este registro de goles?')) {
            try {
                const respuesta = await apiPost(`/api/goles/${borrar.dataset.borrar}/eliminar`, {});
                mostrarAviso(mensaje, respuesta.mensaje);
                await cargar();
            } catch (error) {
                mostrarAviso(mensaje, error.message, 'error');
            }
        }
    });

    await cargar();
}

async function iniciarIncidencias() {
    const jugadores = await apiGet('/api/jugadores');
    const catalogo = await apiGet('/api/incidencias');
    const mensaje = document.querySelector('#msg-incidencia');
    const tipo = document.querySelector('#INC_Tipo_Tarjeta');
    const suspension = document.querySelector('#INC_Fecha_Suspension');

    llenarSelect(
        document.querySelector('#JUG_Jugador'),
        jugadores.jugadores,
        'JUG_Jugador',
        (fila) => `${fila.JUG_Nombre_Completo} · ${fila.EQU_Nombre}`,
        'Seleccione un jugador'
    );
    llenarSelect(tipo, catalogo.tipos || [], 'TTA_Codigo', 'TTA_Nombre', 'Seleccione un tipo');

    function ajustarSuspension() {
        const roja = tipo.value === 'roja';
        suspension.required = roja;
        suspension.disabled = !roja;

        if (!roja) {
            suspension.value = '';
        }
    }

    tipo.addEventListener('change', ajustarSuspension);
    ajustarSuspension();

    async function cargar(datosPrevios) {
        const datos = datosPrevios || await apiGet('/api/incidencias');
        const tbody = document.querySelector('#tabla-incidencias');
        limpiarBusqueda(tbody);
        tbody.innerHTML = datos.incidencias.map((incidencia) => `
            <tr>
                <td>${esc(incidencia.INC_Fecha_Incidencia_Vista)}</td>
                <td>${esc(incidencia.JUG_Nombre_Completo)}</td>
                <td><span class="badge badge-${esc(incidencia.INC_Tipo_Tarjeta)}">${esc(incidencia.INC_Tipo_Tarjeta_Nombre || incidencia.INC_Tipo_Tarjeta)}</span></td>
                <td>${esc(incidencia.INC_Fecha_Suspension_Vista || '—')}</td>
                <td><button type="button" class="peligro" data-borrar="${incidencia.INC_Incidencia}">Eliminar</button></td>
            </tr>
        `).join('') || '<tr class="fila-vacia"><td colspan="5">No hay incidencias.</td></tr>';
        activarTabla(document.querySelector('#tabla-incidencias'));
    }

    document.querySelector('#form-incidencia').addEventListener('submit', async (evento) => {
        evento.preventDefault();

        try {
            const respuesta = await apiPost('/api/incidencias', {
                JUG_Jugador: document.querySelector('#JUG_Jugador').value,
                INC_Tipo_Tarjeta: tipo.value,
                INC_Descripcion: document.querySelector('#INC_Descripcion').value,
                INC_Fecha_Incidencia: document.querySelector('#INC_Fecha_Incidencia').value,
                INC_Fecha_Suspension: suspension.value,
            });
            mostrarAviso(mensaje, respuesta.mensaje);
            document.querySelector('#form-incidencia').reset();
            ajustarSuspension();
            await cargar();
        } catch (error) {
            mostrarAviso(mensaje, error.message, 'error');
        }
    });

    document.querySelector('#tabla-incidencias').addEventListener('click', async (evento) => {
        const borrar = evento.target.closest('[data-borrar]');

        if (borrar && await confirmar('¿Eliminar esta incidencia?')) {
            try {
                const respuesta = await apiPost(`/api/incidencias/${borrar.dataset.borrar}/eliminar`, {});
                mostrarAviso(mensaje, respuesta.mensaje);
                await cargar();
            } catch (error) {
                mostrarAviso(mensaje, error.message, 'error');
            }
        }
    });

    await cargar(catalogo);
}

async function iniciarReporteArbitro() {
    const jornadas = await apiGet('/api/jornadas');
    const mensaje = document.querySelector('#msg-arbitro');
    llenarSelect(
        document.querySelector('#jornada'),
        jornadas.jornadas,
        'JOR_Jornada',
        (fila) => `Jornada ${fila.JOR_Numero} · ${fila.JOR_Fecha_Juego_Vista}`,
        'Seleccione una jornada'
    );

    document.querySelector('#form-arbitro').addEventListener('submit', async (evento) => {
        evento.preventDefault();

        try {
            const datos = await apiGet(`/api/reportes/arbitro?jornada=${document.querySelector('#jornada').value}`);
            mostrarAviso(mensaje, `Jornada ${datos.jornada.JOR_Numero} · ${datos.jornada.JOR_Fecha_Juego_Vista}`);
            document.querySelector('#lista-arbitro').innerHTML = datos.equipos.map((equipo) => `
                <section class="grupo-equipo">
                    <h2>${esc(equipo.EQU_Nombre)}</h2>
                    <div class="tabla-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Foto</th>
                                    <th>Jugador</th>
                                    <th>Nacimiento</th>
                                    <th>¿Suspendido?</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${equipo.jugadores.map((jugador) => `
                                    <tr>
                                        <td><img class="foto" src="${esc(fotoUrl(jugador.JUG_Fotografia))}" alt=""></td>
                                        <td>${esc(jugador.JUG_Nombre_Completo)}</td>
                                        <td>${esc(jugador.JUG_Fecha_Nacimiento_Vista)}</td>
                                        <td>
                                            <span class="badge ${jugador.suspendido ? 'badge-roja' : 'badge-ok'}">
                                                ${jugador.suspendido ? 'Sí' : 'No'}
                                            </span>
                                        </td>
                                    </tr>
                                `).join('') || '<tr><td colspan="4">Sin jugadores.</td></tr>'}
                            </tbody>
                        </table>
                    </div>
                </section>
            `).join('') || '<p>No hay equipos con jugadores.</p>';
            document.querySelectorAll('#lista-arbitro tbody').forEach((cuerpo) => activarTabla(cuerpo, 10));
        } catch (error) {
            mostrarAviso(mensaje, error.message, 'error');
        }
    });
}

async function iniciarReporteIncidencias() {
    const equipos = await apiGet('/api/equipos');
    const jugadores = await apiGet('/api/jugadores');
    const mensaje = document.querySelector('#msg-rep-incidencias');
    const selectEquipo = document.querySelector('#equipo');
    const selectJugador = document.querySelector('#jugador');

    llenarSelect(selectEquipo, equipos.equipos, 'EQU_Equipo', 'EQU_Nombre', 'Seleccione un equipo');

    function filtrarJugadores() {
        const equipoId = selectEquipo.value;
        const filtrados = jugadores.jugadores.filter((jugador) => String(jugador.EQU_Equipo) === equipoId);
        llenarSelect(selectJugador, filtrados, 'JUG_Jugador', 'JUG_Nombre_Completo', 'Todos los jugadores');
    }

    selectEquipo.addEventListener('change', filtrarJugadores);
    filtrarJugadores();

    document.querySelector('#form-rep-incidencias').addEventListener('submit', async (evento) => {
        evento.preventDefault();
        const jugador = selectJugador.value;
        const query = jugador
            ? `/api/reportes/incidencias?equipo=${selectEquipo.value}&jugador=${jugador}`
            : `/api/reportes/incidencias?equipo=${selectEquipo.value}`;

        try {
            const datos = await apiGet(query);
            mostrarAviso(mensaje, `Equipo ${datos.equipo.EQU_Nombre}`);
            document.querySelector('#lista-incidencias').innerHTML = `
                <div class="tabla-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Foto</th>
                                <th>Jugador</th>
                                <th>Fecha</th>
                                <th>Tarjeta</th>
                                <th>Descripción</th>
                                <th>Suspensión</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${datos.incidencias.map((incidencia) => `
                                <tr>
                                    <td><img class="foto" src="${esc(fotoUrl(incidencia.JUG_Fotografia))}" alt=""></td>
                                    <td>${esc(incidencia.JUG_Nombre_Completo)}</td>
                                    <td>${esc(incidencia.INC_Fecha_Incidencia_Vista)}</td>
                                    <td><span class="badge badge-${esc(incidencia.INC_Tipo_Tarjeta)}">${esc(incidencia.INC_Tipo_Tarjeta_Nombre || incidencia.INC_Tipo_Tarjeta)}</span></td>
                                    <td>${esc(incidencia.INC_Descripcion)}</td>
                                    <td>${esc(incidencia.INC_Fecha_Suspension_Vista || '—')}</td>
                                </tr>
                            `).join('') || '<tr class="fila-vacia"><td colspan="6">Sin incidencias para este filtro.</td></tr>'}
                        </tbody>
                    </table>
                </div>
            `;
            activarTabla(document.querySelector('#lista-incidencias tbody'));
        } catch (error) {
            mostrarAviso(mensaje, error.message, 'error');
        }
    });
}

async function iniciarGoleadores() {
    const mensaje = document.querySelector('#msg-goleadores');

    try {
        const datos = await apiGet('/api/reportes/goleadores');
        document.querySelector('#tabla-goleadores').innerHTML = datos.goleadores.map((jugador, indice) => `
            <tr class="${indice === 0 ? 'lider' : ''}">
                <td class="puesto">${indice + 1}</td>
                <td><img class="foto" src="${esc(fotoUrl(jugador.JUG_Fotografia))}" alt=""></td>
                <td>${esc(jugador.JUG_Nombre_Completo)}</td>
                <td>${esc(jugador.EQU_Nombre)}</td>
                <td class="num-goles">${esc(jugador.total_goles)}</td>
            </tr>
        `).join('') || '<tr class="fila-vacia"><td colspan="5">No hay jugadores.</td></tr>';
        activarTabla(document.querySelector('#tabla-goleadores'));
    } catch (error) {
        mostrarAviso(mensaje, error.message, 'error');
    }
}
