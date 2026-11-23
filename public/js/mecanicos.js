/*
 * Proposito: Gestiona interacciones del navegador para mecanicos, conectando eventos y elementos del DOM.
 * Dependencias: API DOM del navegador; no importa bibliotecas JavaScript desde este archivo.
 * Consumidores/dependientes: vista de mecanicos que enlaza el archivo y proporciona sus formularios, botones y atributos data-*.
 */
document.addEventListener('click', /* Atiende el evento click y actualiza la interaccion asociada. Parametros: event. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function (event) {
    if (!(event.target instanceof Element)) {
        return;
    }

    var boton = event.target.closest('.editar-mecanico');
    if (!boton) {
        return;
    }

    ['id', 'nombre', 'telefono', 'especialidad'].forEach(/* Procesa o comprueba el elemento actual durante el recorrido de la coleccion. Parametros: campo. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function (campo) {
        var entrada = document.getElementById('editar-' + campo);
        if (entrada) {
            entrada.value = boton.dataset[campo] || '';
        }
    });
});

document.addEventListener('submit', /* Atiende el evento submit y actualiza la interaccion asociada. Parametros: event. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function (event) {
    if (!(event.target instanceof HTMLFormElement) || !event.target.hasAttribute('data-confirm')) {
        return;
    }

    if (!window.confirm(event.target.dataset.confirm || '¿Confirmas esta operación?')) {
        event.preventDefault();
    }
});
