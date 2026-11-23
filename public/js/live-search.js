/*
 * Proposito: Gestiona interacciones del navegador para live-search, conectando eventos y elementos del DOM.
 * Dependencias: API DOM del navegador; no importa bibliotecas JavaScript desde este archivo.
 * Consumidores/dependientes: formularios de las vistas con data-live-search y la respuesta consultada mediante fetch.
 */
(/* Ejecuta la tarea auxiliar asociada a esta interaccion de la interfaz. Parametros: ninguno. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function () {
    // Solo se conectan automáticamente los formularios marcados con data-live-search.
    document.querySelectorAll('form[data-live-search]').forEach(/* Procesa o comprueba el elemento actual durante el recorrido de la coleccion. Parametros: form. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function (form) {
        var grupo = form.dataset.liveSearch;
        var tablaSelector = 'table[data-live-search-results="' + grupo + '"]';
        var estado = document.createElement('span');
        estado.className = 'form-text text-danger d-block';
        estado.setAttribute('role', 'status');
        estado.hidden = true;
        form.insertAdjacentElement('afterend', estado);

        var temporizador;
        var solicitudActual = 0;

        /* Solicita los resultados del filtro y reemplaza el contenido del listado con la respuesta recibida. Parametros: ninguno. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. // CUIDADO AL MODIFICAR: conserve las comprobaciones y la sincronizacion de estado antes de enviar o mostrar datos. */ function buscar() {
            // FormData recoge los campos; URLSearchParams los convierte en ?q=...
            var numeroSolicitud = ++solicitudActual;
            var parametros = new URLSearchParams(new FormData(form));
            var destino = new URL(window.location.href);
            destino.search = parametros.toString();

            estado.hidden = false;
            estado.className = 'form-text text-muted d-block';
            estado.textContent = 'Buscando registros…';
            fetch(destino.toString(), {
                // El servidor recibe una solicitud normal y devuelve el HTML del módulo.
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).then(/* Procesa el resultado de la etapa anterior de la solicitud asincrona. Parametros: respuesta. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function (respuesta) {
                if (!respuesta.ok) throw new Error('La búsqueda respondió con estado ' + respuesta.status);
                return respuesta.text();
            }).then(/* Procesa el resultado de la etapa anterior de la solicitud asincrona. Parametros: html. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function (html) {
                if (numeroSolicitud !== solicitudActual) return;
                var documento = new DOMParser().parseFromString(html, 'text/html');
                var tablaNueva = documento.querySelector(tablaSelector);
                var tablaActual = document.querySelector(tablaSelector);
                var cuerpoNuevo = tablaNueva && tablaNueva.querySelector('tbody');
                var cuerpoActual = tablaActual && tablaActual.querySelector('tbody');
                if (!cuerpoNuevo || !cuerpoActual) throw new Error('No se encontró la tabla de resultados.');

                // Se reemplaza solo tbody para actualizar filas sin recargar la página.
                cuerpoActual.replaceWith(document.importNode(cuerpoNuevo, true));
                window.history.replaceState(window.history.state, '', destino.pathname + destino.search + destino.hash);
                estado.hidden = true;
                estado.textContent = '';
                document.dispatchEvent(new CustomEvent('ag:live-search-updated'));
            }).catch(/* Gestiona el error producido durante la operacion asincrona. Parametros: error. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function (error) {
                if (numeroSolicitud !== solicitudActual) return;
                console.error('Error en la búsqueda en vivo:', error);
                estado.hidden = false;
                estado.className = 'form-text text-danger d-block';
                estado.textContent = 'No se pudo actualizar la búsqueda. Presiona Enter para volver a intentar.';
            });
        }

        /* Retrasa y agrupa la busqueda para no emitir una solicitud por cada pulsacion. Parametros: ninguno. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. // CUIDADO AL MODIFICAR: conserve las comprobaciones y la sincronizacion de estado antes de enviar o mostrar datos. */ function programarBusqueda() {
            // Espera brevemente tras cada tecla para no enviar una petición por carácter.
            window.clearTimeout(temporizador);
            temporizador = window.setTimeout(buscar, 180);
        }

        var campoBusqueda = form.querySelector('[name="q"]');
        if (campoBusqueda) campoBusqueda.addEventListener('input', programarBusqueda);
        form.querySelectorAll('select, input[type="checkbox"]').forEach(/* Procesa o comprueba el elemento actual durante el recorrido de la coleccion. Parametros: control. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function (control) {
            control.addEventListener('change', programarBusqueda);
        });
        form.addEventListener('submit', /* Atiende el evento submit y actualiza la interaccion asociada. Parametros: evento. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function (evento) {
            evento.preventDefault();
            window.clearTimeout(temporizador);
            buscar();
        });
    });
})();
