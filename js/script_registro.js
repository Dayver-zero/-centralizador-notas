// Registro Pedagogico: anadir/quitar columnas de notas (efimero, solo DOM)
(function () {
    'use strict';

    var BASE = { conocer: 1, hacer: 1, ser: 1, asistencia: 1 };
    var MAX = 12;
    var MAX_FECHAS = 12;

    var BLOCKS = {
        conocer: {
            base: BASE.conocer,
            counted: true,
            h1: '.reg-teoria-tit',
            h2: '.reg-conocer-tit',
            h3: '.reg-eval',
            numClass: 'reg-num-conocer',
            celClass: 'reg-conocer-cel',
            tipo: 'conocer',
            nombre: 'Conocer',
            actividadBase: 'Conocer'
        },
        hacer: {
            base: BASE.hacer,
            counted: true,
            h1: '.reg-practica-tit',
            h2: '.reg-hacer-tit',
            h3: '.reg-proy',
            numClass: 'reg-num-hacer',
            celClass: 'reg-hacer-cel',
            tipo: 'hacer',
            nombre: 'Hacer',
            actividadBase: 'Hacer'
        },
        ser: {
            base: BASE.ser,
            counted: true,
            h1: '.reg-practica-tit',
            h2: '.reg-ser-tit',
            numClass: 'reg-num-ser',
            celClass: 'reg-ser-cel',
            tipo: 'ser',
            nombre: 'SER',
            actividadBase: 'Ser'
        }
    };

    function dataRows() {
        var rows = [];
        var tbody = document.querySelector('.reg-tabla tbody');
        if (!tbody) return rows;
        var trs = tbody.querySelectorAll('tr[data-estudiante]');
        for (var i = 0; i < trs.length; i++) rows.push(trs[i]);
        return rows;
    }

    function filaVaciaRows() {
        var rows = [];
        var tbody = document.querySelector('.reg-tabla tbody');
        if (!tbody) return rows;
        var trs = tbody.querySelectorAll('tr.fila-vacia');
        for (var i = 0; i < trs.length; i++) rows.push(trs[i]);
        return rows;
    }

    function counts() {
        return {
            conocer: document.querySelectorAll('.reg-num-conocer').length,
            hacer: document.querySelectorAll('.reg-num-hacer').length,
            ser: document.querySelectorAll('.reg-num-ser').length,
            asistencia: document.querySelectorAll('.reg-fecha').length
        };
    }

    // Reconstruye las filas vacias segun el layout actual (nro, nombre, fechas, asist, pct, conocer, hacer, ser, 3 suma)
    function syncFilaVacia() {
        var c = counts();
        filaVaciaRows().forEach(function (tr, idx, arr) {
            void arr; void idx;
            while (tr.firstChild) tr.removeChild(tr.firstChild);
            var cells = [];
            cells.push('<td class="reg-nro">' + (tr.getAttribute('data-nro') || '') + '</td>');
            cells.push('<td colspan="2"></td>');
            for (var i = 0; i < c.asistencia; i++) cells.push('<td></td>');
            cells.push('<td></td><td></td>');
            for (var i2 = 0; i2 < c.conocer; i2++) cells.push('<td></td>');
            for (var i3 = 0; i3 < c.hacer; i3++) cells.push('<td></td>');
            for (var i4 = 0; i4 < c.ser; i4++) cells.push('<td></td>');
            for (var i5 = 0; i5 < 3; i5++) cells.push('<td></td>');
            tr.innerHTML = cells.join('');
        });
    }

    function bumpColspan(selector, delta) {
        var el = document.querySelector(selector);
        if (el) el.setAttribute('colspan', (parseInt(el.getAttribute('colspan') || 1, 10) + delta));
    }

    // Inserta la celda de nota en una fila con datos
    function addCellData(row, cfg, num) {
        var cels = row.querySelectorAll('.' + cfg.celClass);
        var ref = cels[cels.length - 1];
        if (!ref) return;
        var td = document.createElement('td');
        td.className = cfg.celClass;
        td.setAttribute('data-tipo', cfg.tipo);
        td.setAttribute('data-actividad', cfg.actividadBase + ' ' + num);
        td.setAttribute('data-est', row.getAttribute('data-estudiante'));
        td.setAttribute('onclick', 'regCelda.onClick(this)');
        ref.parentNode.insertBefore(td, ref.nextSibling);
    }

    function removeCellData(row, celClass) {
        var cels = row.querySelectorAll(celClass);
        if (!cels.length) return;
        var last = cels[cels.length - 1];
        last.parentNode.removeChild(last);
    }

    function addHeaderTh(h4, cfg, num, refSelector, text) {
        var ref = h4.querySelector(refSelector);
        var th = document.createElement('th');
        th.className = 'reg-num ' + cfg.numClass;
        th.textContent = text || num;
        th.setAttribute('data-nombre', th.textContent);
        th.setAttribute('title', 'Clic para editar el nombre');
        h4.insertBefore(th, ref ? ref.nextSibling : null);
    }

    function removeHeaderTh(h4, cfg) {
        var cels = h4.querySelectorAll('.' + cfg.numClass);
        if (!cels.length) return;
        var last = cels[cels.length - 1];
        last.parentNode.removeChild(last);
    }

    function addedCount(cfg) {
        var c = counts()[cfg === BLOCKS.conocer ? 'conocer' : (cfg === BLOCKS.hacer ? 'hacer' : 'ser')];
        return cfg.counted ? (c - cfg.base) : c;
    }

    // ---- ASISTENCIA (columnas FECHA) ----

    function fmtFechaCorta(fecha) {
        if (!fecha) return '';
        var p = fecha.split('-');
        return p.length === 3 ? p[2] + '/' + p[1] : fecha;
    }

    function fmtFechaCompleta(fecha) {
        if (!fecha) return '';
        var p = fecha.split('-');
        return p.length === 3 ? p[2] + '/' + p[1] + '/' + p[0] : fecha;
    }

    function validarFecha(s) {
        if (!/^\d{4}-\d{2}-\d{2}$/.test(s)) return false;
        var p = s.split('-');
        var y = parseInt(p[0], 10), m = parseInt(p[1], 10), d = parseInt(p[2], 10);
        if (y < 1900 || y > 2100 || m < 1 || m > 12 || d < 1 || d > 31) return false;
        var dt = new Date(Date.UTC(y, m - 1, d));
        return dt.toISOString().slice(0, 10) === s;
    }

    function hoyISO() {
        var n = new Date();
        var m = String(n.getMonth() + 1).padStart(2, '0');
        var d = String(n.getDate()).padStart(2, '0');
        return n.getFullYear() + '-' + m + '-' + d;
    }

    // Pide la fecha una vez al anadir una columna de asistencia
    function pedirFechaColumna() {
        var sugerida = hoyISO();
        var valor = window.prompt('Fecha de la nueva columna de asistencia (AAAA-MM-DD):\n\ndefault: ' + sugerida, sugerida);
        if (valor === null) return null; // cancelar
        valor = (valor || '').trim();
        if (valor === '') return sugerida;
        if (!validarFecha(valor)) {
            alert('Fecha no valida. Usa el formato AAAA-MM-DD.');
            return pedirFechaColumna();
        }
        return valor;
    }

    function addFechaTh(h3, num, fecha) {
        var fechas = h3.querySelectorAll('.reg-fecha');
        var last = fechas[fechas.length - 1];
        var th = document.createElement('th');
        th.className = 'reg-fecha';
        th.setAttribute('rowspan', '2');
        th.setAttribute('colspan', '1');
        th.setAttribute('data-fecha', fecha || '');
        th.textContent = fecha ? fmtFechaCompleta(fecha) : 'F' + num;
        th.title = fecha || ('F' + num);
        h3.insertBefore(th, last ? last.nextSibling : null);
    }

    function removeFechaTh(h3) {
        var fechas = h3.querySelectorAll('.reg-fecha');
        if (!fechas.length) return;
        var last = fechas[fechas.length - 1];
        last.parentNode.removeChild(last);
    }

    function addFechaCell(row, num, fecha) {
        var td = document.createElement('td');
        td.className = 'reg-asist-cel';
        td.setAttribute('data-tipo', 'asistencia');
        td.setAttribute('data-fecha', fecha || '');
        td.setAttribute('data-est', row.getAttribute('data-estudiante'));
        td.setAttribute('onclick', 'regCelda.onClick(this)');
        td.title = fecha || ('F' + num);
        var ref = row.querySelector('.reg-asist');
        row.insertBefore(td, ref);
    }

    function removeFechaCell(row) {
        var cels = row.querySelectorAll('.reg-asist-cel');
        if (!cels.length) return;
        var last = cels[cels.length - 1];
        last.parentNode.removeChild(last);
    }

    function addAsistencia() {
        var added = counts().asistencia - BASE.asistencia;
        if (added >= MAX_FECHAS - BASE.asistencia) {
            alert('Maximo de fechas alcanzado (' + MAX_FECHAS + ').');
            return;
        }
        var fecha = pedirFechaColumna();
        if (fecha === null) return; // cancelado
        var now = counts().asistencia + 1;
        bumpColspan('.reg-h1 .reg-asistencia-tit', 1);
        bumpColspan('.reg-h2 .reg-cuarto', 1);
        var h3 = document.querySelector('.reg-h3');
        addFechaTh(h3, now, fecha);
        dataRows().forEach(function (row) { addFechaCell(row, now, fecha); });
        syncFilaVacia();
    }

    function removeAsistencia() {
        var added = counts().asistencia - BASE.asistencia;
        if (added <= 0) {
            alert('La asistencia requiere al menos una fecha.');
            return;
        }
        bumpColspan('.reg-h1 .reg-asistencia-tit', -1);
        bumpColspan('.reg-h2 .reg-cuarto', -1);
        var h3 = document.querySelector('.reg-h3');
        removeFechaTh(h3);
        dataRows().forEach(removeFechaCell);
        syncFilaVacia();
    }

    // Pide el nombre del trabajo una vez al anadir una columna de nota
    function pedirNombreColumna(cfg, num) {
        var sugerida = cfg.nombre + ' ' + num;
        var valor = window.prompt('Nombre del trabajo para esta columna:\n(Se muestra en el encabezado; la nota se guarda como ' + cfg.actividadBase + ' ' + num + ')', sugerida);
        if (valor === null) return null;
        valor = (valor || '').trim();
        return valor === '' ? sugerida : valor;
    }

    function add() {
        var sel = document.getElementById('col-bloque');
        if (!sel) return;
        var block = sel.value;
        if (block === 'asistencia') { addAsistencia(); return; }
        var cfg = BLOCKS[block];
        if (!cfg) return;

        var added = addedCount(cfg);
        if (added >= MAX) {
            alert('Maximo de columnas alcanzado para ' + cfg.nombre + ' (' + MAX + ').');
            return;
        }

        var now = added + 1;
        if (cfg.counted) now = counts()[block] + 1;

        var nombre = pedirNombreColumna(cfg, now);
        if (nombre === null) return;

        var h3 = document.querySelector('.reg-h3');
        var h4 = document.querySelector('.reg-h4');

        bumpColspan('.reg-h1 ' + cfg.h1, 1);
        bumpColspan('.reg-h2 ' + cfg.h2, 1);
        if (cfg.h3) bumpColspan('.reg-h3 ' + cfg.h3, 1);

        if (block === 'ser') {
            var sv = document.createElement('th');
            sv.className = 'reg-ser-vert';
            sv.textContent = nombre;
            sv.setAttribute('data-nombre', nombre);
            sv.setAttribute('title', 'Clic para editar el nombre');
            h3.appendChild(sv);
        }

        // celda numerica en la pestaña superior, despues de la ultima del mismo bloque
        addHeaderTh(h4, cfg, now, '.' + cfg.numClass, nombre);

        dataRows().forEach(function (row) {
            addCellData(row, cfg, now);
        });

        syncFilaVacia();
    }

    function remove() {
        var sel = document.getElementById('col-bloque');
        if (!sel) return;
        var block = sel.value;
        if (block === 'asistencia') { removeAsistencia(); return; }
        var cfg = BLOCKS[block];
        if (!cfg) return;

        var added = addedCount(cfg);
        if (added <= 0) {
            alert('No se pueden quitar mas columnas de ' + cfg.nombre + '.');
            return;
        }

        var h3 = document.querySelector('.reg-h3');
        var h4 = document.querySelector('.reg-h4');

        bumpColspan('.reg-h1 ' + cfg.h1, -1);
        bumpColspan('.reg-h2 ' + cfg.h2, -1);
        if (cfg.h3) bumpColspan('.reg-h3 ' + cfg.h3, -1);

        if (block === 'ser') {
            var svs = h3.querySelectorAll('.reg-ser-vert');
            if (svs.length) svs[svs.length - 1].parentNode.removeChild(svs[svs.length - 1]);
        }

        removeHeaderTh(h4, cfg);

        dataRows().forEach(function (row) {
            removeCellData(row, '.' + cfg.celClass);
        });

        syncFilaVacia();
    }

    /* ---------------------------------------------------------- */
    /*  Renombrar encabezados de columna (etiqueta visual)          */
    /* ---------------------------------------------------------- */

    function colNombreEs(el) {
        return el.hasAttribute('data-nombre') &&
            (el.classList.contains('reg-num') || el.classList.contains('reg-ser-vert'));
    }

    function indiceNumSer(th) {
        var todos = document.querySelectorAll('.reg-h4 .reg-num-ser');
        for (var i = 0; i < todos.length; i++) if (todos[i] === th) return i;
        return -1;
    }

    function sincronizarSerVert(idx, nombre) {
        var svs = document.querySelectorAll('.reg-h3 .reg-ser-vert');
        if (svs[idx]) {
            svs[idx].textContent = nombre;
            svs[idx].setAttribute('data-nombre', nombre);
        }
    }

    function nombrarEncabezado(el) {
        el.setAttribute('contenteditable', 'true');
        el.classList.add('servivo');
        el.focus();
        if (document.createRange && window.getSelection) {
            var r = document.createRange();
            r.selectNodeContents(el);
            var sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange(r);
        }
    }

    document.addEventListener('click', function (e) {
        var el = e.target;
        if (!el || !el.classList || !colNombreEs(el)) return;
        if (el.getAttribute('contenteditable') === 'true') return;
        e.preventDefault();
        nombrarEncabezado(el);
    });

    document.addEventListener('blur', function (e) {
        var el = e.target;
        if (!el || !el.classList || !colNombreEs(el) || el.getAttribute('contenteditable') !== 'true') return;
        var nombre = (el.textContent || '').trim();
        el.textContent = nombre;
        el.setAttribute('data-nombre', nombre);
        el.removeAttribute('contenteditable');
        el.classList.remove('servivo');
        if (el.classList.contains('reg-num-ser')) sincronizarSerVert(indiceNumSer(el), nombre);
    }, true);

    document.addEventListener('keydown', function (e) {
        var el = e.target;
        if (!el || !el.getAttribute) return;
        if (el.getAttribute('contenteditable') !== 'true') return;
        if (e.key === 'Enter') {
            e.preventDefault();
            el.blur();
        } else if (e.key === 'Escape') {
            el.textContent = el.getAttribute('data-nombre') || el.textContent;
            el.blur();
        }
    });

    window.regColumnas = { add: add, remove: remove };
})();

// ============================================================
// Edicion directa de celdas + navegacion por flechas (Excel)
// ============================================================
(function () {
    'use strict';

    function regParams() {
        var out = {};
        var q = (location.search || '').replace(/^\?/, '').split('&');
        for (var i = 0; i < q.length; i++) {
            var kv = q[i].split('=');
            if (kv[0]) out[decodeURIComponent(kv[0])] = decodeURIComponent(kv[1] || '');
        }
        return out;
    }

    function tdTexto(td) {
        return (td.textContent || '').trim();
    }

    function restaurar(td, texto) {
        td.textContent = texto;
    }

    function post(accion, extra, onOk, onErr) {
        var q = regParams();
        var fd = new FormData();
        fd.append('csrf_token', window.APP_CSRF || '');
        fd.append('curso_id', q.curso_id || '0');
        fd.append('materia_id', q.materia_id || '0');
        fd.append('accion', accion);
        if (extra) {
            Object.keys(extra).forEach(function (k) { fd.append(k, extra[k]); });
        }
        fetch('/centralizador_notas/controller/RegistroAjaxController.php', { method: 'POST', body: fd })
            .then(function (r) {
                return r.json().catch(function () { return { ok: false, error: 'Error de servidor (' + r.status + ')' }; });
            })
            .then(function (res) {
                if (res && res.ok) { onOk(res); }
                else {
                    var msg = (res && res.error && String(res.error).length < 200) ? res.error : 'No se pudo guardar.';
                    alert(msg);
                    if (onErr) onErr(res);
                }
            })
            .catch(function () { alert('Error de conexion.'); if (onErr) onErr({ ok: false }); });
    }

    function fmtNum(v) {
        if (v === null || v === undefined || v === '') return '';
        var n = parseFloat(v);
        if (isNaN(n)) return '';
        return (n % 1 === 0) ? String(Math.round(n)) : n.toFixed(1);
    }

    function fmtPct(v) {
        if (v === null || v === undefined) return '';
        var n = parseFloat(v);
        return isNaN(n) ? '0%' : (n % 1 === 0 ? n : n.toFixed(1)) + '%';
    }

    /* ---------------------------------------------------------- */
    /*  Rejilla de celdas editables                                */
    /* ---------------------------------------------------------- */

    function filas() {
        var rows = [];
        var tbody = document.querySelector('.reg-tabla tbody');
        if (!tbody) return rows;
        var trs = tbody.querySelectorAll('tr[data-estudiante]');
        for (var i = 0; i < trs.length; i++) rows.push(trs[i]);
        return rows;
    }

    function celdasFila(row) {
        return row.querySelectorAll('td[data-tipo]');
    }

    function rowOf(td) {
        return td ? td.closest('tr[data-estudiante]') : null;
    }

    function posicion(td) {
        var r = rowOf(td);
        if (!r) return null;
        var rows = filas();
        var cel = celdasFila(r);
        for (var i = 0; i < cel.length; i++) {
            if (cel[i] === td) return { row: rows.indexOf(r), col: i };
        }
        return null;
    }

    function objetivo(td, dr, dc) {
        var p = posicion(td);
        if (!p) return null;
        var rows = filas();
        var nr = p.row + dr;
        var nc = p.col + dc;
        if (nr < 0 || nr >= rows.length || nc < 0) return null;
        var cel = celdasFila(rows[nr]);
        if (nc >= cel.length) return null;
        return cel[nc];
    }

    /* ---------------------------------------------------------- */
    /*  Celda activa                                               */
    /* ---------------------------------------------------------- */

    var _activa = null;

    function limpiarActiva() {
        if (_activa) _activa.classList.remove('celda-activa');
        _activa = null;
    }

    function marcar(td) {
        limpiarActiva();
        if (!td) return;
        td.classList.add('celda-activa');
        _activa = td;
        td.scrollIntoView({ block: 'nearest', inline: 'nearest' });
    }

    function enfocar(td) {
        if (!td || !td.getAttribute('data-tipo')) return;
        marcar(td);
        if (td.getAttribute('data-tipo') === 'asistencia') { editarAsistencia(td); return; }
        editarNota(td);
    }

    function mover(td, dr, dc) {
        var destino = objetivo(td, dr, dc);
        if (!destino) return false;
        setTimeout(function () { enfocar(destino); }, 0);
        return true;
    }

    /* ---------------------------------------------------------- */
    /*  Guardado sin reload                                        */
    /* ---------------------------------------------------------- */

    function aplicarFila(row, recalc) {
        if (!row || !recalc) return;
        var sumas = row.querySelectorAll('.reg-suma-val');
        if (sumas.length >= 3) {
            sumas[0].textContent = fmtNum(recalc.teoria);
            sumas[1].textContent = fmtNum(recalc.practica);
            sumas[2].textContent = fmtNum(recalc.parcial);
        }
    }

    function aplicarAsistencia(td, recalc) {
        if (!recalc) return;
        var row = rowOf(td);
        td.textContent = recalc.letra || '';
        td.className = 'reg-asist-cel' + (recalc.estado ? ' asist-' + recalc.estado.toLowerCase() : '');
        td.setAttribute('onclick', 'regCelda.onClick(this)');
        if (row) {
            var asistTd = row.querySelector('.reg-asist');
            var pctTd = row.querySelector('.reg-pct');
            if (asistTd) asistTd.textContent = recalc.total;
            if (pctTd) pctTd.textContent = fmtPct(recalc.porcentaje);
        }
    }

    function guardarNota(td, tipo, actividad, est, valor, textoOrig, onDone) {
        post('guardar_nota',
            { estudiante_id: est, tipo: tipo, nombre_actividad: actividad, nota: valor },
            function (res) {
                var recalc = res.recalc || {};
                td.textContent = fmtNum(recalc.celda);
                aplicarFila(rowOf(td), recalc);
                if (onDone) onDone(recalc);
            },
            function () {
                restaurar(td, textoOrig);
                if (onDone) onDone(null);
            });
    }

    function guardarAsistencia(td, fecha, est, estado, textoOrig, onDone) {
        post('guardar_asistencia',
            { estudiante_id: est, fecha: fecha, estado: estado },
            function (res) {
                aplicarAsistencia(td, res.recalc || {});
                if (onDone) onDone(res.recalc || {});
            },
            function () {
                restaurar(td, textoOrig);
                if (onDone) onDone(null);
            });
    }

    /* ---------------------------------------------------------- */
    /*  Editores                                                   */
    /* ---------------------------------------------------------- */

    function normalizarNota(v) {
        if (v === null || v === undefined || String(v).trim() === '') return null;
        var n = parseFloat(v);
        if (isNaN(n) || n < 0 || n > 100) return null;
        return n;
    }

    function teclasMov(e) {
        if (e.key === 'Enter')   return [1, 0];
        if (e.key === 'Tab')     return e.shiftKey ? [0, -1] : [0, 1];
        if (e.key === 'ArrowDown')  return [1, 0];
        if (e.key === 'ArrowUp')    return [-1, 0];
        if (e.key === 'ArrowRight') return [0, 1];
        if (e.key === 'ArrowLeft')  return [0, -1];
        return null;
    }

    function editarNota(td) {
        var tipo = td.getAttribute('data-tipo');
        var actividad = td.getAttribute('data-actividad') || '';
        var est = td.getAttribute('data-est');
        var textoOrig = tdTexto(td);

        if (tipo === 'parcial' && !actividad) {
            alert('No hay parcial abierto para registrar.');
            return;
        }

        marcar(td);
        td.textContent = '';
        td.classList.add('editing-celda');
        var input = document.createElement('input');
        input.type = 'number';
        input.min = 0;
        input.max = 100;
        input.step = 0.1;
        input.className = 'celda-inline';
        input.placeholder = '0-100';
        var origN = normalizarNota(textoOrig);
        if (origN !== null) input.value = origN;
        td.appendChild(input);
        input.focus();
        input.select();

        var done = false;
        function terminar(mov) {
            if (done) return;
            var v = input.value;
            if (v === '') {
                done = true;
                td.classList.remove('editing-celda');
                restaurar(td, textoOrig);
                limpiarActiva();
                if (mov) mover(td, mov[0], mov[1]);
                return;
            }
            var n = normalizarNota(v);
            if (n === null) {
                alert('La nota debe estar entre 0 y 100.');
                input.focus();
                input.select();
                return;
            }
            done = true;
            td.classList.remove('editing-celda');
            limpiarActiva();
            if (n === origN) {
                restaurar(td, textoOrig);
                if (mov) mover(td, mov[0], mov[1]);
                return;
            }
            guardarNota(td, tipo, actividad, est, n, textoOrig, function () {
                if (mov) mover(td, mov[0], mov[1]);
            });
        }

        input.addEventListener('keydown', function (e) {
            var mov = teclasMov(e);
            if (e.key === 'Escape') {
                e.preventDefault();
                if (done) return;
                done = true;
                td.classList.remove('editing-celda');
                restaurar(td, textoOrig);
                limpiarActiva();
                return;
            }
            if (mov) {
                e.preventDefault();
                terminar(mov);
            }
        });
        input.addEventListener('blur', function () { terminar(null); limpiarActiva(); });
    }

    function editarAsistencia(td) {
        var fecha = td.getAttribute('data-fecha') || '';
        var est = td.getAttribute('data-est');
        var textoOrig = tdTexto(td);

        if (!fecha) {
            alert('Esta columna de asistencia no tiene fecha. Usa "Añadir columna" con ASISTENCIA para crearla con fecha.');
            return;
        }

        marcar(td);
        td.textContent = '';
        td.classList.add('editing-celda');
        var sel = document.createElement('select');
        sel.className = 'celda-inline';
        var mapa = { P: 'Presente', A: 'Ausente', J: 'Justificado' };
        var opciones = [['', '--'], ['Presente', 'P'], ['Ausente', 'A'], ['Justificado', 'J']];
        for (var i = 0; i < opciones.length; i++) {
            var op = document.createElement('option');
            op.value = opciones[i][0];
            op.textContent = opciones[i][1];
            sel.appendChild(op);
        }
        sel.value = (mapa[(textoOrig || '').trim().toUpperCase()] || '');
        td.appendChild(sel);
        sel.focus();

        var done = false;
        function terminarGuarda(mov) {
            if (done) return;
            var v = sel.value;
            var pre = mapa[(textoOrig || '').trim().toUpperCase()] || '';
            done = true;
            td.classList.remove('editing-celda');
            limpiarActiva();
            if (v === '' || v === pre) {
                restaurar(td, textoOrig);
                if (mov) mover(td, mov[0], mov[1]);
                return;
            }
            guardarAsistencia(td, fecha, est, v, textoOrig, function () {
                if (mov) mover(td, mov[0], mov[1]);
            });
        }

        sel.addEventListener('change', function () { terminarGuarda(null); });
        sel.addEventListener('blur', function () { terminarGuarda(null); limpiarActiva(); });
        sel.addEventListener('keydown', function (e) {
            var mov = teclasMov(e);
            if (e.key === 'Escape') {
                e.preventDefault();
                if (done) return;
                done = true;
                td.classList.remove('editing-celda');
                restaurar(td, textoOrig);
                limpiarActiva();
                return;
            }
            if (mov) {
                e.preventDefault();
                terminarGuarda(mov);
            }
        });
    }

    function onClick(td) {
        if (!td || !td.getAttribute('data-tipo')) return;
        if (td.getAttribute('data-tipo') === 'asistencia') { editarAsistencia(td); return; }
        editarNota(td);
    }

    /* ---------------------------------------------------------- */
    /*  Navegacion desde una celda enfocada sin editor             */
    /* ---------------------------------------------------------- */

    document.addEventListener('keydown', function (e) {
        if (e.defaultPrevented) return;
        var tgt = e.target;
        var td = (tgt && tgt.closest && tgt.closest('td[data-tipo]')) || null;
        if (!td || td.classList.contains('editing-celda')) return;
        if (e.key === 'Enter') {
            e.preventDefault();
            enfocar(td);
            return;
        }
        var mov = teclasMov(e);
        if (mov) {
            e.preventDefault();
            mover(td, mov[0], mov[1]);
        }
    });

    window.regCelda = { onClick: onClick, enfocar: enfocar, mover: mover };
})();

// ============================================================
// Tarjeta editable: edición local y boton "Aplicar cambios"
// ============================================================
(function () {
    'use strict';

    var _newLogo = null;
    var _origText = {};

    function params() {
        var out = {};
        var q = (location.search || '').replace(/^\?/, '').split('&');
        for (var i = 0; i < q.length; i++) {
            var kv = q[i].split('=');
            if (kv[0]) out[decodeURIComponent(kv[0])] = decodeURIComponent(kv[1] || '');
        }
        return out;
    }

    function flash(el, ok) {
        el.classList.remove('ok', 'err');
        void el.offsetWidth;
        el.classList.add(ok ? 'ok' : 'err');
        setTimeout(function () { el.classList.remove('ok', 'err'); }, 1600);
    }

    function flashAll(ok) {
        document.querySelectorAll('.editable').forEach(function (el) { flash(el, ok); });
    }

    function campoPath(campo) {
        return '/centralizador_notas/controller/RegistroAjaxController.php';
    }

    function aplicar() {
        var bt = document.activeElement;

        document.querySelectorAll('.editable[contenteditable="true"]').forEach(function (el) {
            el.blur();
        });

        var q = params();
        var fd = new FormData();
        fd.append('accion', 'aplicar_cambios');
        fd.append('csrf_token', window.APP_CSRF || '');
        fd.append('curso_id', q.curso_id || '0');
        fd.append('materia_id', q.materia_id || '0');

        document.querySelectorAll('.editable[data-campo]').forEach(function (el) {
            fd.append(el.getAttribute('data-campo'), (el.textContent || '').trim());
        });

        if (_newLogo) fd.append('logo', _newLogo);

        return fetch(campoPath(), { method: 'POST', body: fd })
            .then(function (r) {
                return r.json().catch(function () { return { ok: false, error: 'Error de servidor (' + r.status + ')' }; });
            })
            .then(function (res) {
                if (res && res.ok) {
                    _newLogo = null;
                    flashAll(true);
                } else {
                    flashAll(false);
                    alert((res && res.error) ? res.error : 'No se pudieron aplicar los cambios.');
                }
            })
            .catch(function () {
                flashAll(false);
                alert('Error de conexion al guardar.');
            });
    }

    function descartar() {
        location.reload();
    }

    document.addEventListener('click', function (e) {
        var el = e.target;

        if (el && el.closest && el.closest('.editable-logo')) {
            var input = document.getElementById('logo-input');
            if (input) { input.value = ''; input.click(); }
            return;
        }

        var ed = el && el.closest ? el.closest('.editable') : null;
        if (!ed) return;
        if (ed.getAttribute('contenteditable') === 'true') return;
        _origText[ed.getAttribute('data-campo')] = ed.textContent;
        ed.setAttribute('contenteditable', 'true');
        ed.classList.add('editing');
        ed.focus();
        if (document.createRange) {
            var r = document.createRange();
            r.selectNodeContents(ed);
            var sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange(r);
        }
    });

    document.addEventListener('blur', function (e) {
        var ed = e.target;
        if (!ed || ed.getAttribute('contenteditable') !== 'true') return;
        ed.removeAttribute('contenteditable');
        ed.classList.remove('editing');
        var campo = ed.getAttribute('data-campo');
        var texto = (ed.textContent || '').trim();
        if (campo && _origText[campo] !== undefined && _origText[campo].trim() !== texto) {
            ed.classList.add('dirty');
        }
    }, true);

    document.addEventListener('keydown', function (e) {
        if (!e.key) return;
        var ed = e.target;
        if (!ed || ed.getAttribute('contenteditable') !== 'true') return;
        if (e.key === 'Enter') { e.preventDefault(); ed.blur(); }
        if (e.key === 'Escape') {
            var campo = ed.getAttribute('data-campo');
            if (campo && _origText[campo] !== undefined) ed.textContent = _origText[campo];
            ed.blur();
        }
    });

    document.addEventListener('change', function (e) {
        var input = e.target;
        if (!input || input.id !== 'logo-input') return;
        var file = input.files && input.files[0];
        if (!file) return;
        if (!/^image\/(png|jpe?g|gif|webp)$/i.test(file.type)) {
            alert('Selecciona una imagen (PNG, JPG, GIF o WEBP).');
            return;
        }
        if (file.size > 2 * 1024 * 1024) {
            alert('La imagen supera los 2MB.');
            return;
        }
        var img = document.querySelector('.editable-logo');
        var reader = new FileReader();
        reader.onload = function (ev) {
            _newLogo = ev.target.result;
            if (img) {
                img.src = _newLogo;
                var td = img.closest('td');
                if (td) td.classList.add('dirty');
            }
        };
        reader.readAsDataURL(file);
    });

    window.regConfig = { aplicar: aplicar, descartar: descartar, pendiente: function () { return _newLogo !== null; } };
})();

// ============================================================
// Parcial activo: enviar las notas del parcial abierto
// ============================================================
(function () {
    'use strict';

    function regParams() {
        var out = {};
        var q = (location.search || '').replace(/^\?/, '').split('&');
        for (var i = 0; i < q.length; i++) {
            var kv = q[i].split('=');
            if (kv[0]) out[decodeURIComponent(kv[0])] = decodeURIComponent(kv[1] || '');
        }
        return out;
    }

    function enviar() {
        var activo = window.APP_ACTIVO || '';
        if (!activo) {
            alert('No hay parcial abierto para enviar.');
            return;
        }
        if (!confirm('¿Enviar las notas del parcial "' + activo + '"? Se cerrará y no podrás editarlas hasta que el administrador lo reabra.')) {
            return;
        }
        var q = regParams();
        var fd = new FormData();
        fd.append('csrf_token', window.APP_CSRF || '');
        fd.append('curso_id', q.curso_id || '0');
        fd.append('materia_id', q.materia_id || '0');
        fd.append('accion', 'enviar_parcial');
        fd.append('parcial', activo);
        fetch('/centralizador_notas/controller/RegistroAjaxController.php', { method: 'POST', body: fd })
            .then(function (r) {
                return r.json().catch(function () { return { ok: false, error: 'Error de servidor (' + r.status + ')' }; });
            })
            .then(function (res) {
                if (res && res.ok) {
                    alert('Parcial enviado correctamente.');
                    location.reload();
                } else {
                    alert((res && res.error && String(res.error).length < 200) ? res.error : 'No se pudo enviar el parcial.');
                }
            })
            .catch(function () { alert('Error de conexion.'); });
    }

    window.regParciales = { enviar: enviar };
})();