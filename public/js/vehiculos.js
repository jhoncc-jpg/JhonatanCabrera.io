/*
 * Proposito: Gestiona interacciones del navegador para vehiculos, conectando eventos y elementos del DOM.
 * Dependencias: API DOM del navegador; no importa bibliotecas JavaScript desde este archivo.
 * Consumidores/dependientes: vista de vehiculos que enlaza el archivo y proporciona sus formularios, botones y atributos data-*.
 */
document.addEventListener('DOMContentLoaded', /* Inicializa las interacciones cuando el documento ya esta disponible. Parametros: ninguno. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function () {
    document.querySelectorAll('.placa-input').forEach(/* Procesa o comprueba el elemento actual durante el recorrido de la coleccion. Parametros: placa. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function (placa) {
        placa.addEventListener('input', /* Atiende el evento input y actualiza la interaccion asociada. Parametros: ninguno. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function () {
            placa.value = placa.value.replace(/[^A-Za-z0-9 ]/g, '').toUpperCase();
        });
    });

    if (window.flatpickr) {
        window.flatpickr('.fecha-registro-es', {
            locale: 'es',
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'j \\de F \\de Y',
            minDate: 'today',
            allowInput: false
        });
        window.flatpickr('.hora-registro-es', {
            locale: 'es',
            enableTime: true,
            noCalendar: true,
            dateFormat: 'H:i',
            altInput: false,
            time_24hr: true,
            allowInput: true
        });
    }

    /* Vuelve a registrar las acciones de edicion en las filas sustituidas por la busqueda dinamica. Parametros: ninguno. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function enlazarAccionesFilasVehiculo() {
        document.querySelectorAll('.editar-vehiculo').forEach(/* Procesa o comprueba el elemento actual durante el recorrido de la coleccion. Parametros: boton. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function (boton) {
            boton.addEventListener('click', /* Atiende el evento click y actualiza la interaccion asociada. Parametros: ninguno. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function () {
                ['id', 'propietario', 'placa', 'marca', 'modelo', 'anio', 'color', 'diagnostico', 'estado', 'fecha_registro_fecha', 'fecha_registro_hora'].forEach(/* Procesa o comprueba el elemento actual durante el recorrido de la coleccion. Parametros: campo. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function (campo) {
                    var destino = document.getElementById('editar-' + campo);
                    if (!destino) return;

                    var valor = boton.dataset[campo] || '';
                    if (destino._flatpickr) {
                        if (valor) destino._flatpickr.setDate(valor, false);
                        else destino.value = '';
                    } else {
                        destino.value = valor;
                    }
                });
            });
        });

        document.querySelectorAll('.ag-diagnosis-toggle, .ag-inline-toggle').forEach(/* Procesa o comprueba el elemento actual durante el recorrido de la coleccion. Parametros: boton. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function (boton) {
            boton.addEventListener('click', /* Atiende el evento click y actualiza la interaccion asociada. Parametros: ninguno. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function () {
                var completo = boton.nextElementSibling;
                var estaAbierto = boton.getAttribute('aria-expanded') === 'true';
                boton.setAttribute('aria-expanded', estaAbierto ? 'false' : 'true');
                boton.classList.toggle('expanded', !estaAbierto);
                completo.hidden = estaAbierto;
            });
        });

        document.querySelectorAll('[data-confirm-delete]').forEach(/* Procesa o comprueba el elemento actual durante el recorrido de la coleccion. Parametros: formulario. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function (formulario) {
            formulario.addEventListener('submit', /* Atiende el evento submit y actualiza la interaccion asociada. Parametros: evento. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function (evento) {
                if (!window.confirm('¿Eliminar este vehículo? Solo será posible si no tiene órdenes.')) {
                    evento.preventDefault();
                }
            });
        });
    }

    /* Valida el tipo y tamano de los archivos y mantiene sincronizada su previsualizacion. Parametros: inputId, previsualizacionId. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. // CUIDADO AL MODIFICAR: conserve las comprobaciones y la sincronizacion de estado antes de enviar o mostrar datos. */ function configurarPrevisualizacionImagenes(inputId, previsualizacionId) {
        var inputImagenes = document.getElementById(inputId);
        var previsualizacion = document.getElementById(previsualizacionId);
        if (!inputImagenes || !previsualizacion) return;

        /* Dibuja las miniaturas de los archivos seleccionados y permite retirarlos antes de enviar el formulario. Parametros: ninguno. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. // CUIDADO AL MODIFICAR: conserve las comprobaciones y la sincronizacion de estado antes de enviar o mostrar datos. */ function renderizar() {
            previsualizacion.textContent = '';
            Array.from(inputImagenes.files).forEach(/* Procesa o comprueba el elemento actual durante el recorrido de la coleccion. Parametros: archivo, indice. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function (archivo, indice) {
                var contenedor = document.createElement('div');
                contenedor.className = 'ag-vehicle-preview-item';
                var imagen = document.createElement('img');
                imagen.className = 'ag-vehicle-preview';
                imagen.alt = archivo.name;
                var lector = new FileReader();
                lector.onload = /* Ejecuta la tarea auxiliar asociada a esta interaccion de la interfaz. Parametros: evento. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function (evento) {
                    imagen.src = evento.target.result;
                };
                lector.onerror = /* Ejecuta la tarea auxiliar asociada a esta interaccion de la interfaz. Parametros: ninguno. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function () {
                    console.error('No se pudo previsualizar la imagen seleccionada.');
                };
                lector.readAsDataURL(archivo);
                contenedor.appendChild(imagen);

                var quitar = document.createElement('button');
                quitar.type = 'button';
                quitar.className = 'btn btn-sm btn-outline-danger';
                quitar.textContent = 'Quitar';
                quitar.setAttribute('aria-label', 'Quitar ' + archivo.name);
                quitar.addEventListener('click', /* Atiende el evento click y actualiza la interaccion asociada. Parametros: ninguno. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function () {
                    if (typeof DataTransfer === 'undefined') {
                        console.error('Este navegador no permite quitar archivos de la selección.');
                        return;
                    }
                    var nuevaSeleccion = new DataTransfer();
                    Array.from(inputImagenes.files).forEach(/* Procesa o comprueba el elemento actual durante el recorrido de la coleccion. Parametros: seleccionado, posicion. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function (seleccionado, posicion) {
                        if (posicion !== indice) nuevaSeleccion.items.add(seleccionado);
                    });
                    inputImagenes.files = nuevaSeleccion.files;
                    renderizar();
                });
                contenedor.appendChild(quitar);
                previsualizacion.appendChild(contenedor);
            });
        }

        inputImagenes.addEventListener('change', /* Atiende el evento change y actualiza la interaccion asociada. Parametros: ninguno. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function () {
            var archivos = Array.from(inputImagenes.files);
            var tipoInvalido = archivos.some(/* Procesa o comprueba el elemento actual durante el recorrido de la coleccion. Parametros: archivo. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function (archivo) {
                return !['image/jpeg', 'image/png', 'image/webp', 'image/gif'].includes(archivo.type);
            });
            var archivoGrande = archivos.some(/* Procesa o comprueba el elemento actual durante el recorrido de la coleccion. Parametros: archivo. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function (archivo) {
                return archivo.size <= 0 || archivo.size > 10 * 1024 * 1024;
            });
            if (archivos.length > 8 || tipoInvalido || archivoGrande) {
                window.alert('Selecciona hasta 8 imágenes JPG, PNG, WEBP o GIF de máximo 10 MB cada una.');
                inputImagenes.value = '';
            }
            renderizar();
        });
        renderizar();
    }

    configurarPrevisualizacionImagenes('imagenesVehiculo', 'previsualizacionVehiculo');
    configurarPrevisualizacionImagenes('editarImagenesVehiculo', 'previsualizacionEditarVehiculo');

    document.querySelectorAll('.ver-fotos-vehiculo').forEach(/* Procesa o comprueba el elemento actual durante el recorrido de la coleccion. Parametros: boton. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function (boton) {
        boton.addEventListener('click', /* Atiende el evento click y actualiza la interaccion asociada. Parametros: ninguno. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function () {
            var rutas;
            try {
                rutas = JSON.parse(boton.dataset.rutas || '[]');
            } catch (error) {
                console.error('No se pudieron leer las rutas de imágenes del vehículo.', error);
                return;
            }
            var principal = document.getElementById('fotoVehiculoGrande');
            var descargar = document.getElementById('descargarFotoVehiculo');
            var miniaturas = document.getElementById('miniaturasVehiculo');
            var nombreVehiculo = (boton.dataset.vehiculo || 'vehiculo').trim()
                .replace(/[^a-zA-Z0-9\-_ ]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .replace(/^-|-$/g, '')
                .toUpperCase();
            nombreVehiculo = nombreVehiculo || 'vehiculo';

            /* Prepara el enlace de descarga para la imagen seleccionada. Parametros: ruta, indice. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. // CUIDADO AL MODIFICAR: conserve las comprobaciones y la sincronizacion de estado antes de enviar o mostrar datos. */ function establecerArchivoDescarga(ruta, indice) {
                var rutaSinConsulta = (ruta || '').split('?')[0];
                var extension = (rutaSinConsulta.match(/\.([a-z0-9]+)$/i) || ['.jpg'])[1] || 'jpg';
                descargar.href = ruta;
                descargar.setAttribute('download', nombreVehiculo + '-' + (indice + 1) + '.' + extension.toLowerCase());
            }

            miniaturas.textContent = '';
            if (rutas.length) {
                principal.src = rutas[0];
                establecerArchivoDescarga(rutas[0], 0);
            }
            rutas.forEach(/* Procesa o comprueba el elemento actual durante el recorrido de la coleccion. Parametros: ruta, indice. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function (ruta, indice) {
                var miniatura = document.createElement('button');
                miniatura.type = 'button';
                miniatura.className = 'ag-vehicle-thumbnail' + (indice === 0 ? ' active' : '');
                var imagen = document.createElement('img');
                imagen.src = ruta;
                imagen.alt = 'Foto ' + (indice + 1);
                miniatura.appendChild(imagen);
                miniatura.addEventListener('click', /* Atiende el evento click y actualiza la interaccion asociada. Parametros: ninguno. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function () {
                    principal.src = ruta;
                    establecerArchivoDescarga(ruta, indice);
                    miniaturas.querySelectorAll('.ag-vehicle-thumbnail').forEach(/* Procesa o comprueba el elemento actual durante el recorrido de la coleccion. Parametros: item. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function (item) {
                        item.classList.remove('active');
                    });
                    miniatura.classList.add('active');
                });
                miniaturas.appendChild(miniatura);
            });
        });
    });

    enlazarAccionesFilasVehiculo();
    document.addEventListener('ag:live-search-updated', enlazarAccionesFilasVehiculo);

    var imagenGrande = document.getElementById('fotoVehiculoGrande');
    if (imagenGrande) {
        imagenGrande.addEventListener('click', /* Atiende el evento click y actualiza la interaccion asociada. Parametros: ninguno. Devuelve: el valor calculado si aplica; en otro caso, undefined. Efectos secundarios: puede consultar o modificar el DOM y registrar eventos. */ function () {
            if (this.classList.toggle('ag-vehicle-main-image-expanded') && this.requestFullscreen) {
                this.requestFullscreen();
            }
        });
    }
});
