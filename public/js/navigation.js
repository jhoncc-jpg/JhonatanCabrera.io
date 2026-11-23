/*
 * Proposito: Gestiona interacciones del navegador para navigation, conectando eventos y elementos del DOM.
 * Dependencias: API DOM del navegador; no importa bibliotecas JavaScript desde este archivo.
 * Consumidores/dependientes: vistas que exponen el menu lateral, los botones y el dialogo de ayuda.
 */
document.addEventListener('DOMContentLoaded', /* Inicializa las interacciones cuando el documento ya esta disponible. Parametros: ninguno. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function () {
    var sidebar = document.getElementById('agSidebar');
    var toggle = document.querySelector('.ag-menu-toggle');

    if (sidebar && toggle) {
        var closeButton = sidebar.querySelector('.ag-sidebar-close');

        /* Cierra el menu lateral movil y restaura el estado del boton y del panel. Parametros: ninguno. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function closeMobileMenu() {
            sidebar.classList.remove('open');
            document.body.classList.remove('ag-mobile-menu-open');
            toggle.setAttribute('aria-expanded', 'false');
            toggle.setAttribute('aria-label', 'Abrir menú');
        }

        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-label', 'Abrir menú');

        if (closeButton) {
            closeButton.addEventListener('click', closeMobileMenu);
        }

        toggle.addEventListener('click', /* Atiende el evento click y actualiza la interaccion asociada. Parametros: ninguno. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function () {
            if (window.innerWidth > 992) {
                return;
            }

            sidebar.classList.toggle('open');
            var abierto = sidebar.classList.contains('open');
            document.body.classList.toggle('ag-mobile-menu-open', abierto);
            toggle.setAttribute('aria-expanded', abierto ? 'true' : 'false');
            toggle.setAttribute('aria-label', abierto ? 'Cerrar menú' : 'Abrir menú');
        });

        sidebar.querySelectorAll('a').forEach(/* Procesa o comprueba el elemento actual durante el recorrido de la coleccion. Parametros: link. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function (link) {
            link.addEventListener('click', /* Atiende el evento click y actualiza la interaccion asociada. Parametros: ninguno. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function () {
                if (window.innerWidth <= 992) {
                    closeMobileMenu();
                }
            });
        });

        document.addEventListener('keydown', /* Atiende el evento keydown y actualiza la interaccion asociada. Parametros: event. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function (event) {
            if (event.key === 'Escape' && window.innerWidth <= 992) {
                closeMobileMenu();
            }
        });

        document.addEventListener('click', /* Atiende el evento click y actualiza la interaccion asociada. Parametros: event. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function (event) {
            if (
                window.innerWidth <= 992 &&
                sidebar.classList.contains('open') &&
                !sidebar.contains(event.target) &&
                !toggle.contains(event.target)
            ) {
                closeMobileMenu();
            }
        });

        window.addEventListener('resize', /* Atiende el evento resize y actualiza la interaccion asociada. Parametros: ninguno. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function () {
            if (window.innerWidth > 992) {
                closeMobileMenu();
            }
        });
    }

    var createButton = document.getElementById('createButton');
    var createMenu = document.getElementById('createMenu');
    if (createButton && createMenu) {
        /* Cierra el menu contextual de creacion y sincroniza los atributos de accesibilidad. Parametros: ninguno. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function closeCreateMenu() {
            createMenu.hidden = true;
            createButton.setAttribute('aria-expanded', 'false');
        }

        createButton.addEventListener('click', /* Atiende el evento click y actualiza la interaccion asociada. Parametros: event. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function (event) {
            event.stopPropagation();
            var abierto = !createMenu.hidden;
            createMenu.hidden = abierto;
            createButton.setAttribute('aria-expanded', abierto ? 'false' : 'true');
        });

        document.addEventListener('click', /* Atiende el evento click y actualiza la interaccion asociada. Parametros: event. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function (event) {
            if (!createMenu.hidden && !createMenu.contains(event.target) && event.target !== createButton) {
                closeCreateMenu();
            }
        });

        document.addEventListener('keydown', /* Atiende el evento keydown y actualiza la interaccion asociada. Parametros: event. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function (event) {
            if (event.key === 'Escape' && !createMenu.hidden) {
                closeCreateMenu();
                createButton.focus();
            }
        });
    }

    var helpButton = document.getElementById('helpButton');
    var helpModal = document.getElementById('helpModal');
    if (!helpButton || !helpModal) {
        return;
    }

    /* Cierra el dialogo de ayuda y restaura su estado visual. Parametros: ninguno. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function closeHelp() {
        helpModal.classList.remove('show');
        helpModal.setAttribute('aria-hidden', 'true');
        helpModal.removeAttribute('aria-modal');
        helpButton.focus();
    }

    helpButton.addEventListener('click', /* Atiende el evento click y actualiza la interaccion asociada. Parametros: ninguno. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function () {
        helpModal.classList.add('show');
        helpModal.setAttribute('aria-modal', 'true');
        helpModal.removeAttribute('aria-hidden');
        var closeButton = helpModal.querySelector('[data-help-close]');
        if (closeButton) {
            closeButton.focus();
        }
    });

    helpModal.querySelectorAll('[data-help-close]').forEach(/* Procesa o comprueba el elemento actual durante el recorrido de la coleccion. Parametros: button. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function (button) {
        button.addEventListener('click', closeHelp);
    });

    helpModal.addEventListener('click', /* Atiende el evento click y actualiza la interaccion asociada. Parametros: event. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function (event) {
        if (event.target === helpModal) {
            closeHelp();
        }
    });

    document.addEventListener('keydown', /* Atiende el evento keydown y actualiza la interaccion asociada. Parametros: event. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function (event) {
        if (event.key === 'Escape' && helpModal.classList.contains('show')) {
            closeHelp();
        }
    });
});
