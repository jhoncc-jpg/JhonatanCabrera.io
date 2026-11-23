/*
 * Proposito: Gestiona interacciones del navegador para ordenes, conectando eventos y elementos del DOM.
 * Dependencias: API DOM del navegador; no importa bibliotecas JavaScript desde este archivo.
 * Consumidores/dependientes: vista de ordenes que enlaza el archivo y proporciona sus formularios, botones y atributos data-*.
 */
document.addEventListener('DOMContentLoaded', /* Inicializa las interacciones cuando el documento ya esta disponible. Parametros: ninguno. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function () {
    var modalEdicion = document.getElementById('editarOrdenModal');
    if (modalEdicion && modalEdicion.hasAttribute('data-open-on-load') && window.bootstrap) {
        window.bootstrap.Modal.getOrCreateInstance(modalEdicion).show();
    }

    var vehiculo = document.getElementById('crear-vehiculo');
    var diagnostico = document.getElementById('crear-diagnostico');
    if (vehiculo && diagnostico) {
        var diagnosticoAnterior = '';
        vehiculo.addEventListener('change', /* Atiende el evento change y actualiza la interaccion asociada. Parametros: ninguno. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function () {
            var opcion = vehiculo.options[vehiculo.selectedIndex];
            var nuevoDiagnostico = opcion ? (opcion.dataset.diagnostico || '') : '';
            if (!diagnostico.value.trim() || diagnostico.value === diagnosticoAnterior) {
                diagnostico.value = nuevoDiagnostico;
            }
            diagnosticoAnterior = nuevoDiagnostico;
        });
    }

    var panel = document.getElementById('orden-detalle-panel');
    if (panel) {
        var orderId = panel.dataset.ordenId;
        var orderLink = Array.from(document.querySelectorAll('a[href]')).find(/* Procesa o comprueba el elemento actual durante el recorrido de la coleccion. Parametros: link. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function (link) {
            var url = new URL(link.href, window.location.href);
            return url.searchParams.get('id') === orderId
                && url.searchParams.get('page') === 'ordenes';
        });
        var orderRow = orderLink && orderLink.closest('tr');
        if (orderRow) {
            var detailRow = document.createElement('tr');
            detailRow.className = 'ag-order-detail-row';
            var detailCell = document.createElement('td');
            detailCell.colSpan = orderRow.cells.length;
            detailCell.appendChild(panel);
            detailRow.appendChild(detailCell);
            orderRow.after(detailRow);
        }
    }

    document.querySelectorAll('form[data-confirm-delete]').forEach(/* Procesa o comprueba el elemento actual durante el recorrido de la coleccion. Parametros: form. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function (form) {
        form.addEventListener('submit', /* Atiende el evento submit y actualiza la interaccion asociada. Parametros: event. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function (event) {
            if (!window.confirm('¿Eliminar permanentemente esta orden y sus datos relacionados?')) {
                event.preventDefault();
            }
        });
    });

    document.querySelectorAll('form').forEach(/* Procesa o comprueba el elemento actual durante el recorrido de la coleccion. Parametros: form. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function (form) {
        var checks = Array.from(form.querySelectorAll('.ag-required-choice'));
        if (!checks.length) return;

        /* Comprueba que al menos un mecanico este seleccionado y actualiza la validez nativa del formulario. Parametros: ninguno. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. // CUIDADO AL MODIFICAR: conserve las comprobaciones y la sincronizacion de estado antes de enviar o mostrar datos. */ function validateMechanicSelection() {
            var selected = checks.some(/* Procesa o comprueba el elemento actual durante el recorrido de la coleccion. Parametros: check. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function (check) {
                return check.checked;
            });
            checks[0].setCustomValidity(selected ? '' : 'Selecciona al menos un mecánico.');
            return selected;
        }

        checks.forEach(/* Procesa o comprueba el elemento actual durante el recorrido de la coleccion. Parametros: check. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function (check) {
            check.addEventListener('change', validateMechanicSelection);
        });
        form.addEventListener('submit', /* Atiende el evento submit y actualiza la interaccion asociada. Parametros: event. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function (event) {
            if (!validateMechanicSelection()) {
                event.preventDefault();
                checks[0].reportValidity();
            }
        });
    });
});
