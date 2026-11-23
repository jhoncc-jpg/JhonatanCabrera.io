/*
 * Proposito: Gestiona interacciones del navegador para reporte-repuestos, conectando eventos y elementos del DOM.
 * Dependencias: API DOM del navegador; no importa bibliotecas JavaScript desde este archivo.
 * Consumidores/dependientes: vista de reporte-repuestos que enlaza el archivo y proporciona sus formularios, botones y atributos data-*.
 */
document.addEventListener('DOMContentLoaded', /* Inicializa las interacciones cuando el documento ya esta disponible. Parametros: ninguno. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function () {
    var formulario = document.querySelector('[data-inventory-report]');
    if (!formulario) {
        return;
    }

    var desde = formulario.querySelector('[name="desde"]');
    var hasta = formulario.querySelector('[name="hasta"]');
    if (!desde || !hasta) {
        return;
    }

    /* Compara las fechas del reporte y bloquea el envio si el intervalo no es valido. Parametros: ninguno. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. // CUIDADO AL MODIFICAR: conserve las comprobaciones y la sincronizacion de estado antes de enviar o mostrar datos. */ function validarPeriodo() {
        var periodoInvalido = desde.value !== ''
            && hasta.value !== ''
            && desde.value > hasta.value;
        hasta.setCustomValidity(periodoInvalido
            ? 'La fecha final no puede ser anterior a la fecha inicial.'
            : '');
        return !periodoInvalido;
    }

    desde.addEventListener('change', validarPeriodo);
    hasta.addEventListener('change', validarPeriodo);
    formulario.addEventListener('submit', /* Atiende el evento submit y actualiza la interaccion asociada. Parametros: event. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function (event) {
        if (!validarPeriodo()) {
            event.preventDefault();
            hasta.reportValidity();
        }
    });
});
