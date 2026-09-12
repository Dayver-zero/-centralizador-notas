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
            nombre: 'Conocer'
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
            nombre: 'Hacer'
        },
        ser: {
            base: BASE.ser,
            counted: true,
            h1: '.reg-practica-tit',
            h2: '.reg-ser-tit',
            numClass: 'reg-num-ser',
            celClass: 'reg-ser-cel',
            tipo: 'ser',
            nombre: 'SER'
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
            cells.push('<td></td>');
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
        td.setAttribute('onclick', 'abrirModalNota(' + row.getAttribute('data-estudiante') +
            ", '" + cfg.tipo + "', '" + (cfg.nombre + ' ' + num) + "')");
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

    function addFechaTh(h3, num) {
        var fechas = h3.querySelectorAll('.reg-fecha');
        var last = fechas[fechas.length - 1];
        var th = document.createElement('th');
        th.className = 'reg-fecha';
        th.setAttribute('rowspan', '2');
        th.setAttribute('colspan', '1');
        th.textContent = 'F' + num;
        th.title = 'F' + num;
        h3.insertBefore(th, last ? last.nextSibling : null);
    }

    function removeFechaTh(h3) {
        var fechas = h3.querySelectorAll('.reg-fecha');
        if (!fechas.length) return;
        var last = fechas[fechas.length - 1];
        last.parentNode.removeChild(last);
    }

    function addFechaCell(row, num) {
        var td = document.createElement('td');
        td.className = 'reg-asist-cel';
        td.title = 'F' + num;
        td.setAttribute('onclick', 'abrirModalAsistencia(' + row.getAttribute('data-estudiante') + ", '')");
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
        var now = counts().asistencia + 1;
        bumpColspan('.reg-h1 .reg-asistencia-tit', 1);
        bumpColspan('.reg-h2 .reg-cuarto', 1);
        var h3 = document.querySelector('.reg-h3');
        addFechaTh(h3, now);
        dataRows().forEach(function (row) { addFechaCell(row, now); });
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

        var h3 = document.querySelector('.reg-h3');
        var h4 = document.querySelector('.reg-h4');

        bumpColspan('.reg-h1 ' + cfg.h1, 1);
        bumpColspan('.reg-h2 ' + cfg.h2, 1);
        if (cfg.h3) bumpColspan('.reg-h3 ' + cfg.h3, 1);

        if (block === 'ser') {
            var sv = document.createElement('th');
            sv.className = 'reg-ser-vert';
            sv.textContent = cfg.nombre + ' ' + now;
            h3.appendChild(sv);
        }

        // celda numerica en la pestaña superior, despues de la ultima del mismo bloque
        addHeaderTh(h4, cfg, now, '.' + cfg.numClass, now);

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

    window.regColumnas = { add: add, remove: remove };
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
// Añadir alumno / Quitar estudiantes vacios
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

    function post(accion, extra, ok, err) {
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
                if (res && res.ok) { ok(res); } else {
                    var msg = (res && res.error && String(res.error).length < 200) ? res.error : 'No se pudo completar la operacion.';
                    alert(msg);
                    if (err) err(res);
                }
            })
            .catch(function () { alert('Error de conexion.'); });
    }

    function añadir() {
        var nombre = (document.getElementById('modalEstNombre').value || '').trim();
        var ci = (document.getElementById('modalEstCi').value || '').trim();
        if (!nombre) { alert('Escriba el nombre del estudiante.'); return; }
        if (!ci) { alert('Escriba la C.I. del estudiante.'); return; }

        post('añadir_estudiante', { nombre: nombre, ci: ci }, location.reload.bind(location));
    }

    function quitarVacios() {
        if (!confirm('¿Desinscribir del registro a los estudiantes sin notas ni asistencia?')) return;
        post('quitar_vacios', null, function (res) {
            var n = (res && res.borrados) ? parseInt(res.borrados, 10) : 0;
            alert((n > 0 ? n + ' estudiante(s) vacío(s) quitados.' : 'No hay estudiantes vacíos para quitar.') + ' La hoja se recargará.');
            location.reload();
        });
    }

    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form || form.id !== 'formEstudiante') return;
        e.preventDefault();
        añadir();
    });

    window.regEstudiantes = { añadir: añadir, quitarVacios: quitarVacios };
})();