// ================================================================
// buscar_rutas_index.js
// Buscador de rutas de la landing: escribe, pulsa Enter o la lupa,
// llama a index.php (POST q) y pinta los resultados.
// ================================================================
document.addEventListener('DOMContentLoaded', function () {

    var input = document.getElementById('searchInput');
    var boton = document.getElementById('searchBtn');
    var lista = document.getElementById('resultadosRutas');
    var titulo = document.getElementById('rutasTitulo');
    var contador = document.getElementById('rutasContador');
    var verTodas = document.getElementById('verTodas');

    if (!input || !lista) return;

    var peticion = null;   // para cancelar la búsqueda anterior si el usuario sigue escribiendo
    var temporizador = null;

    // ----------------------------------------------------------
    // Crea un elemento con clase y texto (textContent = seguro contra XSS)
    // ----------------------------------------------------------
    function el(etiqueta, clase, texto) {
        var nodo = document.createElement(etiqueta);
        if (clase) nodo.className = clase;
        if (texto !== undefined && texto !== null) nodo.textContent = texto;
        return nodo;
    }

    function icono(clases) {
        var i = document.createElement('i');
        i.className = clases;
        return i;
    }

    function mensajeVacio(iconoClase, texto) {
        var caja = el('div', 'rutas-vacio');
        caja.appendChild(icono(iconoClase));
        caja.appendChild(el('span', '', texto));
        return caja;
    }

    // ----------------------------------------------------------
    // Tarjeta de una ruta
    // ----------------------------------------------------------
    function tarjetaRuta(ruta, indice) {
        var item = el('article', 'ruta-item');

        // Insignia: número que aparece en el nombre ("Ruta 05" → 05) o un bus
        var numero = (ruta.nombre_ruta || '').match(/\d+/);
        var insignia = el('span', 'ruta-num c' + (indice % 4));
        if (ruta.codigo) {
            // código real de la ruta (90, 82, 31…) con su color
            insignia.className = 'ruta-num cod' + (ruta.codigo.length > 3 ? ' largo' : '');
            insignia.textContent = ruta.codigo;
            if (/^#[0-9a-f]{6}$/i.test(ruta.color || '')) insignia.style.background = ruta.color;
        } else if (numero) {
            insignia.textContent = numero[0].slice(0, 3);
        } else {
            insignia.appendChild(icono('fa-solid fa-bus'));
        }

        var info = el('div', 'ruta-info');
        info.appendChild(el('h4', '', ruta.nombre_ruta));

        var trayecto = el('p', 'ruta-trayecto');
        trayecto.appendChild(document.createTextNode(ruta.origen || 'Origen'));
        trayecto.appendChild(icono('fa-solid fa-arrow-right'));
        trayecto.appendChild(document.createTextNode(ruta.destino || 'Destino'));
        info.appendChild(trayecto);

        var extra = el('p', 'ruta-extra');
        var partes = [];
        if (ruta.pasa_por) {
            var pasa = el('b', '', 'Pasa por: ' + ruta.pasa_por);
            extra.appendChild(pasa);
        }
        if (ruta.paraderos > 0) {
            partes.push(ruta.paraderos + (ruta.paraderos === 1 ? ' paradero' : ' paraderos'));
        }
        if (ruta.empresa) {
            partes.push(ruta.empresa);
        }
        if (partes.length) {
            extra.appendChild(document.createTextNode((ruta.pasa_por ? ' · ' : '') + partes.join(' · ')));
        }
        if (extra.childNodes.length) info.appendChild(extra);
        if (!ruta.pasa_por && ruta.descripcion) {
            info.appendChild(el('p', 'ruta-pasa', ruta.descripcion));
        }

        var meta = el('div', 'ruta-meta');
        if (ruta.tiempo_estimado) meta.appendChild(el('strong', '', ruta.tiempo_estimado));
        if (ruta.distancia !== null && ruta.distancia !== undefined) {
            meta.appendChild(el('span', '', String(ruta.distancia).replace('.', ',') + ' km'));
        }

        item.appendChild(insignia);
        item.appendChild(info);
        if (meta.childNodes.length) item.appendChild(meta);
        return item;
    }

    // ----------------------------------------------------------
    // Pinta la respuesta del servidor
    // ----------------------------------------------------------
    function pintar(datos, termino, mostrandoTodas) {
        lista.textContent = '';

        if (!datos.rutas || datos.rutas.length === 0) {
            if (termino) {
                lista.appendChild(mensajeVacio('fa-regular fa-face-frown',
                    'No encontramos rutas para "' + termino + '". Prueba con otro barrio, paradero o nombre de ruta.'));
            } else {
                lista.appendChild(mensajeVacio('fa-solid fa-route',
                    'Aún no hay rutas registradas. Muy pronto las verás aquí.'));
            }
            contador.textContent = '';
            verTodas.hidden = true;
            titulo.textContent = termino ? 'Resultados' : 'Rutas disponibles';
            return;
        }

        datos.rutas.forEach(function (ruta, i) {
            lista.appendChild(tarjetaRuta(ruta, i));
        });

        titulo.textContent = termino ? 'Resultados para "' + termino + '"' : 'Rutas disponibles';
        contador.textContent = termino
            ? datos.rutas.length + (datos.rutas.length === 1 ? ' ruta encontrada' : ' rutas encontradas')
            : 'Mostrando ' + datos.rutas.length + ' de ' + datos.total;

        // "Ver todas" solo si no hay búsqueda y quedan rutas por mostrar
        verTodas.hidden = !(termino === '' && !mostrandoTodas && datos.total > datos.rutas.length);
    }

    function pintarError(texto) {
        lista.textContent = '';
        lista.appendChild(mensajeVacio('fa-solid fa-triangle-exclamation', texto));
        contador.textContent = '';
        verTodas.hidden = true;
    }

    // ----------------------------------------------------------
    // Búsqueda
    // ----------------------------------------------------------
    function buscar(todas) {
        var termino = input.value.trim();

        if (peticion) peticion.abort();
        peticion = new AbortController();

        var cuerpo = 'q=' + encodeURIComponent(termino) + (todas ? '&todas=1' : '');

        fetch('index.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: cuerpo,
            signal: peticion.signal
        })
            .then(function (respuesta) { return respuesta.json(); })
            .then(function (datos) {
                if (!datos.ok) {
                    pintarError(datos.mensaje || 'No se pudo realizar la búsqueda.');
                    return;
                }
                pintar(datos, termino, !!todas);
            })
            .catch(function (error) {
                if (error.name === 'AbortError') return;   // llegó una búsqueda más nueva
                pintarError('No pudimos conectar con el servidor. Intenta de nuevo.');
            });
    }

    // ----------------------------------------------------------
    // Eventos
    // ----------------------------------------------------------
    boton.addEventListener('click', function () { buscar(false); });

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            clearTimeout(temporizador);
            buscar(false);
        }
    });

    // Busca solo mientras escribe (espera 350 ms a que termine de teclear)
    input.addEventListener('input', function () {
        clearTimeout(temporizador);
        temporizador = setTimeout(function () { buscar(false); }, 350);
    });

    verTodas.addEventListener('click', function (e) {
        // es un enlace normal a rutas.php (lista completa)
    });

    // ----------------------------------------------------------
    // Carga inicial: datos que ya vienen dentro de la página
    // ----------------------------------------------------------
    if (lista.dataset.conexion === '0') {
        pintarError('No hay conexión con la base de datos. Revisa que MySQL esté encendido.');
        return;
    }

    try {
        var inicial = JSON.parse(lista.dataset.inicial || '{}');
        pintar({ rutas: inicial.rutas || [], total: inicial.total || 0 }, '', false);
    } catch (e) {
        pintarError('No se pudieron cargar las rutas.');
    }
});
