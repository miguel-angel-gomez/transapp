// ================================================================
// mapa_ruta.js
// 1) Flecha del buscador: lista desplegable con TODAS las rutas.
// 2) Al elegir una, muestra un mapa de Ibagué (Leaflet + OpenStreetMap)
//    y anima una línea que recorre la ruta de origen a destino,
//    marcando los paraderos a medida que el bus pasa por ellos.
// Los datos salen de index.php (POST q='' todas=1): cada ruta trae
// sus paraderos en orden con lat/lng.
// ================================================================
document.addEventListener('DOMContentLoaded', function () {

    var toggle = document.getElementById('rutasToggle');
    var lista = document.getElementById('rutasDesplegable');
    var contLista = document.getElementById('rdLista');
    var total = document.getElementById('rdTotal');
    var panel = document.getElementById('mapaPanel');
    if (!toggle || !lista || !panel) return;

    var mapaDiv = document.getElementById('mapaRuta');
    var estado = document.getElementById('mapaEstado');
    var elCodigo = document.getElementById('mapaCodigo');
    var elNombre = document.getElementById('mapaNombre');
    var elTrayecto = document.getElementById('mapaTrayecto');
    var elParadas = document.getElementById('mapaParadas');
    var elPasa = document.getElementById('mapaPasa');
    var btnRepetir = document.getElementById('mapaRepetir');
    var btnCerrar = document.getElementById('mapaCerrar');

    var CENTRO = [4.4389, -75.2322];            // Plaza de Bolívar, Ibagué
    var reducirMovimiento = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    var rutas = null;          // caché de todas las rutas
    var cargando = false;
    var actual = null;         // ruta mostrada
    var mapa = null, capa = null;
    var animacion = 0;         // identificador para cancelar animaciones viejas
    var rafId = 0;

    function el(tag, clase, texto) {
        var n = document.createElement(tag);
        if (clase) n.className = clase;
        if (texto !== undefined && texto !== null) n.textContent = texto;
        return n;
    }
    function codigoDe(r) {
        if (r.codigo) return r.codigo;
        var m = (r.nombre_ruta || '').match(/\d+/);
        return m ? m[0] : '';
    }
    function colorDe(r) { return /^#[0-9a-f]{6}$/i.test(r.color || '') ? r.color : '#13c4a3'; }

    // ------------------------------------------------------------
    // Lista desplegable
    // ------------------------------------------------------------
    function abrirLista() {
        lista.hidden = false;
        toggle.setAttribute('aria-expanded', 'true');
        if (!rutas && !cargando) cargarRutas();
    }
    function cerrarLista() {
        lista.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
    }

    function cargarRutas() {
        cargando = true;
        contLista.textContent = '';
        contLista.appendChild(el('div', 'rd-vacio', 'Cargando rutas…'));
        fetch('index.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'q=&todas=1'
        })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                cargando = false;
                if (!d.ok) throw new Error(d.mensaje || 'error');
                rutas = d.rutas || [];
                pintarLista();
            })
            .catch(function () {
                cargando = false;
                contLista.textContent = '';
                contLista.appendChild(el('div', 'rd-vacio', 'No se pudieron cargar las rutas. Intenta de nuevo.'));
            });
    }

    function pintarLista() {
        contLista.textContent = '';
        total.textContent = rutas.length + (rutas.length === 1 ? ' ruta' : ' rutas');
        if (!rutas.length) {
            contLista.appendChild(el('div', 'rd-vacio', 'Aún no hay rutas registradas.'));
            return;
        }
        rutas.forEach(function (r) {
            var b = el('button', 'rd-item');
            b.type = 'button';
            b.setAttribute('role', 'option');
            b.dataset.id = r.id_ruta;
            var cod = el('span', 'rd-cod', codigoDe(r) || '•');
            cod.style.background = colorDe(r);
            var txt = el('span', 'rd-txt');
            var o = el('b', '', r.origen || 'Origen');
            var i = document.createElement('i'); i.className = 'fa-solid fa-arrow-right';
            txt.appendChild(o); txt.appendChild(i); txt.appendChild(document.createTextNode(r.destino || 'Destino'));
            b.appendChild(cod); b.appendChild(txt);
            b.addEventListener('click', function () {
                cerrarLista();
                mostrarRuta(r);
            });
            contLista.appendChild(b);
        });
    }

    toggle.addEventListener('click', function (e) {
        e.stopPropagation();
        if (lista.hidden) abrirLista(); else cerrarLista();
    });
    document.addEventListener('click', function (e) {
        if (!lista.hidden && !lista.contains(e.target) && e.target !== toggle) cerrarLista();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !lista.hidden) { cerrarLista(); toggle.focus(); }
    });

    // ------------------------------------------------------------
    // Mapa
    // ------------------------------------------------------------
    function iniciarMapa() {
        if (mapa) return true;
        if (!window.L) {
            avisar('No se pudo cargar el mapa. Revisa tu conexión.');
            return false;
        }
        mapa = L.map(mapaDiv, { zoomControl: true, scrollWheelZoom: false, attributionControl: true })
            .setView(CENTRO, 13);
        // Mapa base de OpenStreetMap (gratuito y sin llave). CARTO ahora exige API key fuera de localhost.
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(mapa);
        capa = L.layerGroup().addTo(mapa);
        // el zoom con la rueda solo al hacer clic en el mapa (no atrapa el scroll de la página)
        mapa.on('click', function () { mapa.scrollWheelZoom.enable(); });
        mapa.on('mouseout', function () { mapa.scrollWheelZoom.disable(); });
        return true;
    }

    function avisar(texto) {
        if (!texto) { estado.hidden = true; return; }
        estado.textContent = texto;
        estado.hidden = false;
    }

    function puntosDe(ruta) {
        return (ruta.paradas || []).filter(function (p) { return p.lat !== null && p.lng !== null; });
    }

    // Recorrido por calles con OSRM; si falla, línea recta entre paraderos
    function trazado(pts) {
        var recto = pts.map(function (p) { return [p.lat, p.lng]; });
        if (pts.length < 2) return Promise.resolve({ camino: recto, calles: false });
        var coords = pts.map(function (p) { return p.lng + ',' + p.lat; }).join(';');
        var url = 'https://router.project-osrm.org/route/v1/driving/' + coords + '?overview=full&geometries=geojson';
        var ctl = ('AbortController' in window) ? new AbortController() : null;
        var t = setTimeout(function () { if (ctl) ctl.abort(); }, 6000);
        return fetch(url, ctl ? { signal: ctl.signal } : {})
            .then(function (r) { return r.json(); })
            .then(function (d) {
                clearTimeout(t);
                if (d && d.routes && d.routes[0] && d.routes[0].geometry.coordinates.length > 1) {
                    return {
                        camino: d.routes[0].geometry.coordinates.map(function (c) { return [c[1], c[0]]; }),
                        calles: true
                    };
                }
                return { camino: recto, calles: false };
            })
            .catch(function () { clearTimeout(t); return { camino: recto, calles: false }; });
    }

    function marcador(latlng, clase, contenido, color) {
        var icono = L.divIcon({
            className: '',
            html: '<div class="mk-parada ' + clase + '" style="--c:' + color + '">' + contenido + '</div>',
            iconSize: [28, 28], iconAnchor: [14, 14]
        });
        return L.marker(latlng, { icon: icono, keyboard: false });
    }

    // ------------------------------------------------------------
    // Mostrar una ruta
    // ------------------------------------------------------------
    function mostrarRuta(ruta) {
        actual = ruta;
        var color = colorDe(ruta);
        panel.style.setProperty('--c', color);

        elCodigo.textContent = codigoDe(ruta) || '•';
        elCodigo.style.background = color;
        elNombre.textContent = ruta.nombre_ruta;
        elTrayecto.textContent = '';
        elTrayecto.appendChild(document.createTextNode(ruta.origen || 'Origen'));
        var fl = document.createElement('i'); fl.className = 'fa-solid fa-arrow-right';
        elTrayecto.appendChild(fl);
        elTrayecto.appendChild(document.createTextNode(ruta.destino || 'Destino'));
        elPasa.textContent = ruta.descripcion || '';
        elPasa.hidden = !ruta.descripcion;

        var pts = puntosDe(ruta);
        elParadas.textContent = '';
        pts.forEach(function (p, i) {
            var li = el('li');
            li.style.setProperty('--c', color);
            li.appendChild(el('span', 'pt', String(i + 1)));
            li.appendChild(el('span', '', p.nombre));
            li.addEventListener('click', function () {
                if (mapa) mapa.flyTo([p.lat, p.lng], Math.max(mapa.getZoom(), 15), { duration: .6 });
            });
            elParadas.appendChild(li);
        });

        panel.hidden = false;
        if (!iniciarMapa()) return;
        // el contenedor estaba oculto: hay que recalcular su tamaño
        mapa.invalidateSize();

        setTimeout(function () {
            var y = panel.getBoundingClientRect().top + window.pageYOffset - 90;
            window.scrollTo({ top: y, behavior: reducirMovimiento ? 'auto' : 'smooth' });
        }, 30);

        animar(ruta, pts);
    }

    function animar(ruta, pts) {
        var id = ++animacion;
        cancelAnimationFrame(rafId);
        capa.clearLayers();
        var color = colorDe(ruta);
        var items = elParadas.children;
        for (var k = 0; k < items.length; k++) items[k].classList.remove('llego');

        if (pts.length === 0) {
            mapa.setView(CENTRO, 13);
            avisar('Esta ruta aún no tiene paraderos con ubicación en el mapa.');
            return;
        }
        if (pts.length === 1) {
            var unico = marcador([pts[0].lat, pts[0].lng], 'mk-extremo', '<i class="fa-solid fa-location-dot"></i>', color).addTo(capa);
            unico.bindTooltip(pts[0].nombre, { className: 'tt-parada', direction: 'top', offset: [0, -12] });
            mapa.setView([pts[0].lat, pts[0].lng], 15);
            avisar('Esta ruta aún no tiene un recorrido detallado; se muestra solo su punto de referencia.');
            if (items[0]) items[0].classList.add('llego');
            return;
        }

        avisar('Trazando el recorrido por las calles…');
        mapa.fitBounds(L.latLngBounds(pts.map(function (p) { return [p.lat, p.lng]; })), { padding: [40, 40] });

        trazado(pts).then(function (res) {
            if (id !== animacion) return;           // el usuario eligió otra ruta
            avisar(res.calles ? '' : 'Se muestra el recorrido en línea recta entre paraderos (no se pudo ajustar a las calles).');
            correr(id, ruta, pts, res.camino, color);
        });
    }

    function correr(id, ruta, pts, camino, color) {
        // distancias acumuladas a lo largo del camino
        var acum = [0];
        for (var i = 1; i < camino.length; i++) {
            acum.push(acum[i - 1] + L.latLng(camino[i - 1]).distanceTo(L.latLng(camino[i])));
        }
        var largo = acum[acum.length - 1] || 1;

        // en qué vértice del camino "pasa" por cada paradero (siempre hacia adelante)
        var idxParada = [], desde = 0;
        pts.forEach(function (p, n) {
            if (n === 0) { idxParada.push(0); return; }
            if (n === pts.length - 1) { idxParada.push(camino.length - 1); return; }
            var mejor = desde, dmin = Infinity, ll = L.latLng(p.lat, p.lng);
            for (var j = desde; j < camino.length; j++) {
                var d = ll.distanceTo(L.latLng(camino[j]));
                if (d < dmin) { dmin = d; mejor = j; }
            }
            idxParada.push(mejor);
            desde = mejor;
        });

        // línea tenue con todo el recorrido + línea viva que se va dibujando
        L.polyline(camino, { color: color, weight: 6, opacity: .18, lineCap: 'round' }).addTo(capa);
        var viva = L.polyline([camino[0]], { color: color, weight: 6, opacity: .95, lineCap: 'round', lineJoin: 'round' }).addTo(capa);
        var bus = L.marker(camino[0], {
            icon: L.divIcon({ className: '', html: '<div class="mk-bus"><i class="fa-solid fa-bus"></i></div>', iconSize: [34, 34], iconAnchor: [17, 17] }),
            zIndexOffset: 1000, keyboard: false
        }).addTo(capa);

        mapa.fitBounds(L.polyline(camino).getBounds(), { padding: [40, 40] });

        var items = elParadas.children;
        var puestos = [];
        function poner(n) {
            if (puestos[n]) return;
            puestos[n] = true;
            var p = pts[n], extremo = (n === 0 || n === pts.length - 1);
            var m = marcador([p.lat, p.lng], extremo ? 'mk-extremo' : '',
                extremo ? '<i class="fa-solid ' + (n === 0 ? 'fa-play' : 'fa-flag-checkered') + '"></i>' : String(n + 1), color).addTo(capa);
            m.bindTooltip(p.nombre, { className: 'tt-parada', direction: 'top', offset: [0, -12], permanent: extremo });
            if (items[n]) items[n].classList.add('llego');
        }

        if (reducirMovimiento) {
            viva.setLatLngs(camino);
            bus.setLatLng(camino[camino.length - 1]);
            pts.forEach(function (_, n) { poner(n); });
            return;
        }

        var duracion = Math.max(3500, Math.min(7000, largo / 4));   // ~ 3,5 – 7 s
        var inicio = null;
        function paso(ahora) {
            if (id !== animacion) return;
            if (inicio === null) inicio = ahora;
            var t = Math.min(1, (ahora - inicio) / duracion);
            var recorrido = t * largo;

            // vértice actual (búsqueda binaria)
            var lo = 0, hi = acum.length - 1;
            while (lo < hi) { var mid = (lo + hi + 1) >> 1; if (acum[mid] <= recorrido) lo = mid; else hi = mid - 1; }
            var pos = camino[lo];
            if (lo < camino.length - 1) {
                var f = (recorrido - acum[lo]) / ((acum[lo + 1] - acum[lo]) || 1);
                pos = [camino[lo][0] + (camino[lo + 1][0] - camino[lo][0]) * f,
                       camino[lo][1] + (camino[lo + 1][1] - camino[lo][1]) * f];
            }
            viva.setLatLngs(camino.slice(0, lo + 1).concat([pos]));
            bus.setLatLng(pos);

            for (var n = 0; n < pts.length; n++) { if (idxParada[n] <= lo) poner(n); }

            if (t < 1) { rafId = requestAnimationFrame(paso); }
            else { viva.setLatLngs(camino); pts.forEach(function (_, n) { poner(n); }); }
        }
        rafId = requestAnimationFrame(paso);
    }

    btnRepetir.addEventListener('click', function () { if (actual) animar(actual, puntosDe(actual)); });
    btnCerrar.addEventListener('click', function () {
        animacion++;
        cancelAnimationFrame(rafId);
        panel.hidden = true;
        actual = null;
    });
});
