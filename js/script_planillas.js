(function () {
    'use strict';

    var VERSION_DATOS = 2;

    function claveHoja(hoja) {
        return 'planilla:' + (hoja.getAttribute('data-clave') || location.pathname + location.search);
    }

    function hojaDesdeBoton(boton) {
        return document.getElementById(boton.getAttribute('data-hoja'));
    }

    function contenedorSheet(hoja) {
        return hoja.closest('.sheet') || hoja.closest('.centralizador-sheet') || document;
    }

    function camposEditables(hoja) {
        return contenedorSheet(hoja).querySelectorAll('.f-val, .enc-val, .kardex-tit th, .kardex-tit td');
    }

    function estadoGuardado(hoja) {
        return {
            version: VERSION_DATOS,
            campos: Array.from(camposEditables(hoja)).map(function (celda) {
                return celda.textContent;
            }),
            filas: Array.from(hoja.querySelectorAll('tr')).map(function (fila) {
                return Array.from(fila.children).map(function (celda) {
                    return celda.textContent;
                });
            })
        };
    }

    function guardar(hoja) {
        localStorage.setItem(claveHoja(hoja), JSON.stringify(estadoGuardado(hoja)));
        mostrarMensaje(hoja, 'Cambios guardados');
    }

    function restablecer(hoja) {
        localStorage.removeItem(claveHoja(hoja));
        mostrarMensaje(hoja, 'Tabla restablecida a su estado original');
        setTimeout(function () { location.reload(); }, 900);
    }

    function mostrarMensaje(hoja, texto) {
        var contenedor = contenedorSheet(hoja) || document.body;
        var previo = contenedor.querySelector('.planilla-aviso');
        if (previo) previo.remove();
        var aviso = document.createElement('div');
        aviso.className = 'planilla-aviso';
        aviso.textContent = texto;
        contenedor.insertBefore(aviso, contenedor.firstChild);
        setTimeout(function () { if (aviso.parentNode) aviso.remove(); }, 2400);
    }

    function activarEdicion(hoja) {
        camposEditables(hoja).forEach(function (celda) {
            if (!celda.dataset.editorActivo && !celda.getAttribute('data-fijo')) {
                celda.setAttribute('contenteditable', 'true');
                celda.dataset.editorActivo = 'true';
                celda.addEventListener('input', function () { recalcular(hoja); });
            }
        });
        hoja.querySelectorAll('th, td').forEach(function (celda) {
            if (celda.getAttribute('data-fijo') !== 'true' && !celda.dataset.editorActivo) {
                celda.setAttribute('contenteditable', 'true');
                celda.dataset.editorActivo = 'true';
                celda.addEventListener('input', function () { recalcular(hoja); });
            }
        });
    }

    function numero(texto) {
        var valor = parseFloat(String(texto).replace(',', '.'));
        return isNaN(valor) ? null : valor;
    }

    function notaAprobacion(hoja) {
        var contenedor = contenedorSheet(hoja);
        var fila = Array.from(contenedor.querySelectorAll('.tabla-fields tr')).filter(function (tr) {
            var lbl = tr.querySelector('.f-lbl');
            return lbl && /APROBACION/i.test(lbl.textContent);
        })[0];
        if (!fila) return 61;
        var val = fila.querySelector('.f-val');
        var n = val ? numero(val.textContent) : null;
        return n !== null ? n : 61;
    }

    function recalcular(hoja) {
        var tipo = hoja.getAttribute('data-tipo');
        if (tipo !== 'entrega' && tipo !== 'centralizador') return;
        var aprob = notaAprobacion(hoja);
        hoja.querySelectorAll('tbody tr').forEach(function (fila) {
            var celdas = Array.from(fila.children);
            if (celdas.length < 4) return;
// entrega: N°(0) Nombre(1) parciales... PROMEDIO INSTANCIA NOTA FINAL OBSERVACIÓN
        // centralizador: N°(0) Nombre(1) CÉDULA(2) materias... ESTADO OBSERVACIONES
        var finValores = tipo === 'entrega' ? celdas.length - 4 : celdas.length - 2;
        var inicioParciales = tipo === 'centralizador' ? 3 : 2;
        var textos = celdas.slice(inicioParciales, finValores).map(function (celda) { return celda.textContent.trim(); });
            var valores = textos.map(numero).filter(function (valor) { return valor !== null; });
            var promedio = valores.length ? valores.reduce(function (suma, valor) { return suma + valor; }, 0) / valores.length : null;
            var final = promedio !== null ? Math.round(promedio) : null;
            if (tipo === 'entrega') {
                var promCelda = celdas[celdas.length - 4];
                if (promCelda && !promCelda.matches('[data-fijo="true"]')) promCelda.textContent = promedio === null ? '' : promedio.toFixed(1).replace('.', ',');
            }
            var nota = celdas[celdas.length - 2];
            var observacion = celdas[celdas.length - 1];
            if (tipo === 'centralizador') {
                // Formula calcada de CENTRALIZADOR.xlsx: si todo vacio -> "";
                // si todos NP -> ABANDONO; si alguno < 61 -> REPROBADO; si no -> APROBADO
                var hayValor = valores.length > 0;
                var est = !hayValor ? '' : (valores.every(function (v) { return v >= aprob; }) ? 'APROBADO' : 'REPROBADO');
                var nps = textos.filter(function (t) { return t.toUpperCase() === 'NP'; }).length;
                if (!hayValor && nps === textos.length) est = 'ABANDONO';
                if (nota && !nota.matches('[data-fijo="true"]')) nota.textContent = est;
                if (observacion && !observacion.matches('[data-fijo="true"]')) observacion.textContent = est;
            } else {
                if (nota && !nota.matches('[data-fijo="true"]')) nota.textContent = final === null ? '' : final;
                if (observacion && !observacion.matches('[data-fijo="true"]')) {
                    observacion.textContent = final === null ? '' : (final >= aprob ? 'APROBADO' : 'REPROBADO');
                }
            }
        });
    }

    function esCentralizador(hoja) {
        return hoja.getAttribute('data-tipo') === 'centralizador' && hoja.querySelectorAll('thead tr').length >= 2;
    }

    function agregarColumna(hoja) {
        var titulo = window.prompt('Nombre de la nueva columna:', 'Nueva columna');
        if (titulo === null) return;
        titulo = titulo.trim() || 'Nueva columna';
        if (esCentralizador(hoja)) {
            var hdrAlto = hoja.querySelector('thead tr');
            var hdrBajo = hoja.querySelector('thead tr:last-child');
            if (hdrAlto) {
                var thCod = document.createElement('th');
                thCod.className = 'th-cod';
                thCod.textContent = '';
                hdrAlto.appendChild(thCod);
            }
            if (hdrBajo) {
                var thNombre = document.createElement('th');
                thNombre.className = 'vert th-mat';
                thNombre.textContent = titulo;
                hdrBajo.insertBefore(thNombre, hdrBajo.children[hdrBajo.children.length - 2]);
            }
        } else {
            var encabezado = hoja.querySelector('thead tr:last-child');
            if (encabezado) {
                var th = document.createElement('th');
                th.textContent = titulo;
                encabezado.appendChild(th);
            }
        }
        hoja.querySelectorAll('tbody tr').forEach(function (fila) {
            fila.insertBefore(document.createElement('td'), fila.children[fila.children.length - 2]);
        });
        activarEdicion(hoja);
        recalcular(hoja);
    }

    function elegirEnModal(titulo, opciones, textoBoton) {
        return new Promise(function (resolver) {
            var fondo = document.createElement('div');
            fondo.className = 'planilla-modal-fondo';
            fondo.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.45);z-index:99999;display:flex;align-items:center;justify-content:center;';
            var modal = document.createElement('div');
            modal.style.cssText = 'background:#fff;border-radius:10px;padding:18px 20px;min-width:320px;max-width:90vw;box-shadow:0 8px 30px rgba(0,0,0,.3);font-family:Arial,sans-serif;';
            var h = document.createElement('h3');
            h.textContent = titulo;
            h.style.cssText = 'margin:0 0 12px;font-size:1rem;color:#243447;';
            var sel = document.createElement('select');
            sel.style.cssText = 'width:100%;padding:9px 12px;border:1px solid #cbd2d9;border-radius:6px;font-size:.9rem;color:#243447;background:#fff;';
            opciones.forEach(function (o, i) {
                var op = document.createElement('option');
                op.value = String(i);
                op.textContent = o;
                sel.appendChild(op);
            });
            var botones = document.createElement('div');
            botones.style.cssText = 'display:flex;gap:10px;justify-content:flex-end;margin-top:16px;';
            var btnOk = document.createElement('button');
            btnOk.type = 'button';
            btnOk.textContent = textoBoton;
            btnOk.style.cssText = 'padding:8px 16px;border:0;border-radius:8px;background:#d9534f;color:#fff;font-weight:700;cursor:pointer;';
            var btnNo = document.createElement('button');
            btnNo.type = 'button';
            btnNo.textContent = 'Cancelar';
            btnNo.style.cssText = 'padding:8px 16px;border:1px solid #9aa4ad;border-radius:8px;background:#e6e9ef;color:#2b3441;font-weight:600;cursor:pointer;';
            function cerrar() { fondo.remove(); }
            btnNo.addEventListener('click', function () { cerrar(); resolver(null); });
            btnOk.addEventListener('click', function () {
                var v = sel.value;
                cerrar();
                resolver(v === '' ? null : parseInt(v, 10));
            });
            fondo.addEventListener('click', function (e) {
                if (e.target === fondo) { cerrar(); resolver(null); }
            });
            botones.appendChild(btnNo);
            botones.appendChild(btnOk);
            modal.appendChild(h);
            modal.appendChild(sel);
            modal.appendChild(botones);
            fondo.appendChild(modal);
            document.body.appendChild(fondo);
            sel.focus();
        });
    }

    function columnasRemovibles(hoja) {
        if (esCentralizador(hoja)) {
            var hdrBajo = hoja.querySelector('thead tr:last-child');
            if (!hdrBajo) return { encabezado: null, opciones: [], indices: [] };
            var celdas = Array.from(hdrBajo.children);
            var opciones = [];
            var indices = [];
            celdas.forEach(function (th, i) {
                var esFija = th.getAttribute('data-fijo') === 'true' || i >= celdas.length - 2;
                if (!esFija) {
                    var nombre = (th.textContent || '').trim() || ('Columna ' + (i + 1));
                    opciones.push('Columna "' + nombre + '"');
                    indices.push(i);
                }
            });
            return { encabezado: hdrBajo, opciones: opciones, indices: indices };
        }
        var encabezado = hoja.querySelector('thead tr:last-child');
        if (!encabezado) return { encabezado: null, opciones: [], indices: [] };
        var tipo = hoja.getAttribute('data-tipo');
        var celdas = Array.from(encabezado.children);
        var ultimasProtegidas = tipo === 'entrega' ? 4 : (tipo === 'centralizador' ? 0 : 0);
        var limite = celdas.length - ultimasProtegidas;
        var opciones = [];
        var indices = [];
        celdas.forEach(function (th, i) {
            var esFija = th.getAttribute('data-fijo') === 'true' || (ultimasProtegidas > 0 && i >= limite);
            if (!esFija) {
                var nombre = (th.textContent || '').trim() || ('Columna ' + (i + 1));
                opciones.push('Columna "' + nombre + '"');
                indices.push(i);
            }
        });
        return { encabezado: encabezado, opciones: opciones, indices: indices };
    }

    function quitarColumna(hoja) {
        var info = columnasRemovibles(hoja);
        if (!info.encabezado || !info.opciones.length) return;
        elegirEnModal('¿Qué columna quieres quitar?', info.opciones, 'Quitar columna').then(function (pos) {
            if (pos === null) return;
            var indice = info.indices[pos];
            if (esCentralizador(hoja)) {
                var hdrAlto = hoja.querySelector('thead tr');
                var hdrBajo = info.encabezado;
                if (hdrAlto && indice < hdrAlto.children.length) hdrAlto.children[indice] && hdrAlto.children[indice].remove();
                if (indice < hdrBajo.children.length) hdrBajo.children[indice].remove();
                hoja.querySelectorAll('tbody tr').forEach(function (fila) {
                    if (fila.children.length > indice + 1 && fila.children[indice + 1]) fila.children[indice + 1].remove();
                });
            } else {
                var encabezado = info.encabezado;
                if (indice >= encabezado.children.length) return;
                encabezado.children[indice].remove();
                hoja.querySelectorAll('tbody tr').forEach(function (fila) {
                    if (fila.children.length > 2 && fila.children[indice]) fila.children[indice].remove();
                });
            }
            recalcular(hoja);
        });
    }

    function quitarFila(hoja) {
        var cuerpo = hoja.querySelector('tbody');
        if (!cuerpo) return;
        var filas = Array.from(cuerpo.querySelectorAll('tr'));
        var opciones = filas.map(function (fila) {
            var celdas = Array.from(fila.children);
            if (celdas.length < 2) {
                var unico = '';
                if (celdas.length === 1) unico = (celdas[0].textContent || '').trim();
                return unico || 'Fila';
            }
            var nro = (celdas[0].textContent || '').trim();
            var nombre = (celdas[1].textContent || '').trim();
            return (nombre ? 'Fila "' + nombre + '"' : 'Fila') + (nro ? ' (N° ' + nro + ')' : '');
        });
        if (!opciones.length) return;
        elegirEnModal('¿Qué fila quieres quitar?', opciones, 'Quitar fila').then(function (pos) {
            if (pos === null) return;
            if (filas[pos]) filas[pos].remove();
        });
    }

    function agregarFila(hoja) {
        var filas = hoja.querySelectorAll('tr');
        if (!filas.length) return;
        var col = 0;
        var cuerpo = hoja.querySelector('tbody');
        if (cuerpo && cuerpo.rows.length) col = cuerpo.rows[0].children.length;
        else {
            var ult = hoja.querySelector('thead tr:last-child');
            if (ult) col = ult.children.length;
        }
        var fila = document.createElement('tr');
        for (var i = 0; i < col; i++) {
            var celda = document.createElement('td');
            celda.textContent = i === 0 ? String(cuerpo ? cuerpo.rows.length + 1 : 1) : '';
            fila.appendChild(celda);
        }
        hoja.querySelector('tbody').appendChild(fila);
        activarEdicion(hoja);
    }

    document.addEventListener('click', function (evento) {
        var boton = evento.target.closest('[data-planilla-accion]');
        if (!boton) return;
        var hoja = hojaDesdeBoton(boton);
        if (!hoja) return;
        var accion = boton.getAttribute('data-planilla-accion');
        if (accion === 'editar') activarEdicion(hoja);
        if (accion === 'add-col') agregarColumna(hoja);
        if (accion === 'remove-col') quitarColumna(hoja);
        if (accion === 'add-row') agregarFila(hoja);
        if (accion === 'remove-row') quitarFila(hoja);
        if (accion === 'guardar') guardar(hoja);
        if (accion === 'restablecer') restablecer(hoja);
    });

    function restaurar(hoja) {
        var guardado = localStorage.getItem(claveHoja(hoja));
        if (!guardado) return;
        try {
            var estado = JSON.parse(guardado);
            if (!estado || estado.version !== VERSION_DATOS) {
                localStorage.removeItem(claveHoja(hoja));
                return;
            }
            var filasGuardadas = estado.filas || [];
            var camposGuardados = estado.campos || [];
            var camposActuales = Array.from(camposEditables(hoja));
            camposGuardados.forEach(function (valor, indice) {
                if (camposActuales[indice] && camposActuales[indice].getAttribute('data-fijo') !== 'true') {
                    camposActuales[indice].textContent = valor;
                }
            });
            var filasActuales = hoja.querySelectorAll('tr');
            var columnasGuardadas = filasGuardadas.reduce(function (maximo, fila) { return Math.max(maximo, fila.length); }, 0);
            var filasCabecera = hoja.querySelectorAll('thead tr').length || 1;
            var columnasBase = 0;
            for (var bi = 0; bi < filasActuales.length; bi++) columnasBase = Math.max(columnasBase, filasActuales[bi].children.length);
            if (columnasGuardadas > columnasBase) {
                var faltantes = columnasGuardadas - columnasBase;
                for (var extra = 0; extra < faltantes; extra++) {
                    if (esCentralizador(hoja)) {
                        var ha = hoja.querySelector('thead tr');
                        var hb = hoja.querySelector('thead tr:last-child');
                        if (ha) {
                            var tc = document.createElement('th');
                            tc.className = 'th-cod';
                            ha.appendChild(tc);
                        }
                        if (hb) {
                            var tn = document.createElement('th');
                            tn.className = 'vert th-mat';
                            hb.insertBefore(tn, hb.children[hb.children.length - 2]);
                        }
                        hoja.querySelectorAll('tbody tr').forEach(function (fila) {
                            fila.insertBefore(document.createElement('td'), fila.children[fila.children.length - 2]);
                        });
                    } else {
                        hoja.querySelectorAll('tr').forEach(function (fila, indice) {
                            var celda = document.createElement(indice === 0 || indice < filasCabecera ? 'th' : 'td');
                            fila.appendChild(celda);
                        });
                    }
                }
            }
            var cuerpo = hoja.querySelector('tbody');
            while (cuerpo && hoja.querySelectorAll('tbody tr').length < filasGuardadas.length - filasCabecera) {
                var nuevaFila = document.createElement('tr');
                for (var nuevaColumna = 0; nuevaColumna < columnasGuardadas; nuevaColumna++) nuevaFila.appendChild(document.createElement('td'));
                cuerpo.appendChild(nuevaFila);
            }
            var bodyActuales = cuerpo ? Array.from(cuerpo.querySelectorAll('tr')) : [];
            filasGuardadas.forEach(function (fila, indice) {
                if (indice < filasCabecera) return;
                var actual = bodyActuales[indice - filasCabecera];
                if (!actual) return;
                fila.forEach(function (valor, columna) {
                    if (actual.children[columna] && actual.children[columna].getAttribute('data-fijo') !== 'true') {
                        actual.children[columna].textContent = valor;
                    }
                });
            });
            recalcular(hoja);
            activarEdicion(hoja);
        } catch (error) {
            localStorage.removeItem(claveHoja(hoja));
        }
    }

    document.querySelectorAll('[data-planilla-hoja]').forEach(function (hoja) {
        restaurar(hoja);
        activarEdicion(hoja);
        recalcular(hoja);
    });
})();
