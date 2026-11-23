/*
 * Proposito: Gestiona interacciones del navegador para repuestos, conectando eventos y elementos del DOM.
 * Dependencias: API DOM del navegador; no importa bibliotecas JavaScript desde este archivo.
 * Consumidores/dependientes: vista de repuestos que enlaza el archivo y proporciona sus formularios, botones y atributos data-*.
 */
document.addEventListener('click', /* Atiende el evento click y actualiza la interaccion asociada. Parametros: event. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function (event) {
    if (!(event.target instanceof Element)) {
        return;
    }

    var botonEdicion = event.target.closest('.editar-repuesto');
    if (botonEdicion) {
        ['id', 'codigo', 'nombre', 'descripcion', 'minimo', 'precio'].forEach(/* Procesa o comprueba el elemento actual durante el recorrido de la coleccion. Parametros: campo. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function (campo) {
            var entrada = document.getElementById('editar-' + campo);
            if (entrada) {
                entrada.value = botonEdicion.dataset[campo] || '';
            }
        });
        return;
    }

    var botonMovimiento = event.target.closest('.movimiento-repuesto');
    if (botonMovimiento) {
        var id = document.getElementById('movimiento-id');
        var nombre = document.getElementById('movimiento-nombre');
        if (id && nombre) {
            id.value = botonMovimiento.dataset.id || '';
            nombre.textContent = botonMovimiento.dataset.nombre || '';
        }
    }
});

document.addEventListener('submit', /* Atiende el evento submit y actualiza la interaccion asociada. Parametros: event. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function (event) {
    if (!(event.target instanceof HTMLFormElement)) {
        return;
    }

    var formulario = event.target;
    if (formulario.hasAttribute('data-confirm')) {
        if (!window.confirm(formulario.dataset.confirm || '¿Confirmas esta operación?')) {
            event.preventDefault();
            return;
        }
    }

    if (formulario.hasAttribute('data-movement-form')) {
        var tipo = formulario.querySelector('[name="tipo"]');
        var cantidad = formulario.querySelector('[name="cantidad"]');
        if (!tipo || !cantidad) {
            return;
        }

        var valor = Number(cantidad.value);
        var invalido = !Number.isInteger(valor)
            || valor < 0
            || (tipo.value !== 'ajuste' && valor === 0);
        cantidad.setCustomValidity(invalido
            ? 'La cantidad debe ser un entero no negativo y mayor que cero, excepto en los ajustes.'
            : '');
        if (invalido) {
            event.preventDefault();
            cantidad.reportValidity();
        }
    }
});
