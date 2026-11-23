/*
 * Proposito: Gestiona interacciones del navegador para usuarios, conectando eventos y elementos del DOM.
 * Dependencias: API DOM del navegador; no importa bibliotecas JavaScript desde este archivo.
 * Consumidores/dependientes: vista de usuarios que enlaza el archivo y proporciona sus formularios, botones y atributos data-*.
 */
document.addEventListener('DOMContentLoaded', /* Inicializa las interacciones cuando el documento ya esta disponible. Parametros: ninguno. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function () {
    var nombreUsuario = document.getElementById('nuevo-nombre-usuario');
    if (!nombreUsuario) return;

    nombreUsuario.addEventListener('input', /* Atiende el evento input y actualiza la interaccion asociada. Parametros: ninguno. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function () {
        nombreUsuario.value = nombreUsuario.value.toLowerCase().replace(/[^a-z0-9._-]/g, '');
    });
});
