<?php

/*
 * Proposito: Coordina las solicitudes y acciones de ReporteController entre la entrada web, los validadores, los repositorios y las vistas.
 * Dependencias: /AuthController.php, /../repositories/ReporteRepository.php, /../validators/ReporteValidator.php, /../views/reportes/index.php.
 * Consumidores/dependientes: app\controllers\RouterController.php.
 */

require_once __DIR__ . '/AuthController.php';
require_once __DIR__ . '/../repositories/ReporteRepository.php';
require_once __DIR__ . '/../validators/ReporteValidator.php';

/**
 * Coordina las solicitudes de Reporte: aplica autorizacion, valida entradas y comunica el repositorio con su vista.
 * Efectos secundarios: define la unidad de trabajo; los efectos concretos se describen en cada metodo.
 */
// CUIDADO AL MODIFICAR: esta clase participa en operaciones sensibles; conserve los contratos con los modulos dependientes.
class ReporteController
{
    /**
     * Atiende la pagina de Reporte: prepara datos, procesa la solicitud y carga la vista.
     * Parametros: no recibe argumentos.
     * Devuelve: no devuelve valor (void).
     * Efectos secundarios: lee o modifica datos de la solicitud o sesion; puede modificar la respuesta HTTP o el estado de sesion; puede producir salida o cargar una vista.
     */
    // CUIDADO AL MODIFICAR: mantenga la validacion y el contrato de datos con los modulos que dependen de esta operacion.
    public function index(): void
    {
        AuthController::requireRoles(['administrador', 'recepcionista']);

        $error = null;
        $reportes = [
            'ordenes' => ['total' => 0],
            'abiertas' => ['total' => 0],
            'inventario' => ['unidades' => 0],
            'bajos' => ['total' => 0],
            'ingresos' => ['total' => 0],
        ];
        $validador = new ReporteValidator();
        $filtrosValidos = true;
        try {
            $filtros = $validador->filtros($_GET);
        } catch (InvalidArgumentException $exception) {
            http_response_code(400);
            $error = $exception->getMessage();
            $filtrosValidos = false;
            $filtros = ['desde' => null, 'hasta' => null];
        }

        $page = 'reportes';
        $mensaje = null;
        try {
            $repositorio = new ReporteRepository();
            $reportes = $repositorio->resumen($filtros['desde'], $filtros['hasta']);
            $download = $_GET['download'] ?? '';
            if (!$filtrosValidos) {
                $download = '';
            } elseif (!is_string($download)) {
                http_response_code(400);
                $error = 'El tipo de reporte solicitado no es válido.';
            } elseif ($download === 'resumen') {
                $this->downloadResumenExcel($reportes, $filtros);
            } elseif ($download === 'vehiculos') {
                $this->downloadVehiculosExcel(
                    $repositorio->vehiculos($filtros['desde'], $filtros['hasta']),
                    $filtros
                );
            } elseif ($download === 'ordenes') {
                $this->downloadOrdenesExcel(
                    $repositorio->ordenes($filtros['desde'], $filtros['hasta']),
                    $filtros
                );
            } elseif ($download !== '') {
                http_response_code(400);
                $error = 'El tipo de reporte solicitado no es válido.';
            }
        } catch (PDOException $exception) {
            error_log('No se pudo consultar los reportes: ' . $exception->getMessage());
            http_response_code(500);
            $error = 'No se pudo completar la consulta de reportes.';
        }

        require __DIR__ . '/../views/reportes/index.php';
    }

    /**
     * Muestra y exporta el reporte valorizado de movimientos de inventario.
     * Parametros: obtiene los filtros desde la solicitud GET.
     * Devuelve: no devuelve valor (void).
     * Efectos secundarios: puede generar una descarga CSV o cargar la vista del reporte.
     */
    public function movimientos(): void
    {
        AuthController::requireRoles(['administrador', 'recepcionista']);

        $error = null;
        $filas = [];
        $resumen = [
            'salidas' => 0.0,
            'entradas' => 0.0,
            'total' => 0.0,
            'unidades' => 0,
        ];
        $validador = new ReporteValidator();
        $filtrosValidos = true;
        try {
            $filtros = $validador->filtrosMovimientos($_GET);
        } catch (InvalidArgumentException $exception) {
            http_response_code(400);
            $error = $exception->getMessage();
            $filtrosValidos = false;
            $filtros = [
                'fecha_inicio' => '',
                'fecha_fin' => '',
                'tipo' => '',
                'busqueda' => '',
            ];
        }

        $exportar = $_GET['exportar'] ?? '';
        if (!is_string($exportar) || !in_array($exportar, ['', 'excel'], true)) {
            http_response_code(400);
            $error = 'El formato de exportación solicitado no es válido.';
            $filtrosValidos = false;
        }

        if ($filtrosValidos) {
            try {
                $repositorio = new ReporteRepository();
                $filas = $repositorio->obtenerReporteMovimientos(
                    $filtros['fecha_inicio'],
                    $filtros['fecha_fin'],
                    $filtros['tipo'],
                    $filtros['busqueda']
                );

                foreach ($filas as $fila) {
                    $total = (float) $fila['total_bs'];
                    $resumen['total'] += $total;
                    $resumen['unidades'] += (int) $fila['cantidad'];
                    if ($fila['tipo'] === 'salida') {
                        $resumen['salidas'] += $total;
                    } elseif ($fila['tipo'] === 'entrada') {
                        $resumen['entradas'] += $total;
                    }
                }

                if ($exportar === 'excel') {
                    $this->exportarMovimientosCsv($filas);
                }
            } catch (PDOException $exception) {
                error_log('No se pudo consultar el reporte de movimientos: ' . $exception->getMessage());
                http_response_code(500);
                $error = 'No se pudo consultar el reporte de movimientos.';
                $filas = [];
            }
        }

        require __DIR__ . '/../views/reportes/movimientos.php';
    }

    /**
     * Envía los movimientos consultados en un CSV compatible con Excel.
     * Parametro $filas: movimientos devueltos por el repositorio y filtrados.
     * Devuelve: no devuelve valor (void).
     * Efectos secundarios: modifica cabeceras HTTP, escribe el archivo y termina la respuesta.
     */
    private function exportarMovimientosCsv(array $filas): void
    {
        if (ob_get_level() > 0) {
            ob_clean();
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="reporte-movimientos-inventario-' . date('Y-m-d') . '.csv"');
        header('Cache-Control: private, max-age=0, must-revalidate');

        echo "\xEF\xBB\xBF";
        $salida = fopen('php://output', 'wb');
        if ($salida === false) {
            throw new RuntimeException('No se pudo preparar la descarga CSV.');
        }

        fputcsv($salida, [
            'FECHA Y HORA',
            'TIPO',
            'CÓDIGO',
            'REPUESTO',
            'CANTIDAD',
            'PRECIO UNIT. (Bs.)',
            'TOTAL (Bs.)',
            'ORDEN / OBSERVACIÓN',
            'USUARIO',
        ], ';', '"', '');

        foreach ($filas as $fila) {
            $fecha = (new DateTimeImmutable((string) $fila['fecha_movimiento']))->format('d/m/Y H:i');
            $ordenObservacion = trim(
                (string) ($fila['orden_codigo'] ?? '')
                . ((string) ($fila['orden_codigo'] ?? '') !== '' && (string) ($fila['observaciones'] ?? '') !== '' ? ' / ' : '')
                . (string) ($fila['observaciones'] ?? '')
            );
            $valores = [
                $fecha,
                (string) $fila['tipo'],
                (string) $fila['codigo'],
                (string) $fila['repuesto_nombre'],
                (string) (int) $fila['cantidad'],
                number_format((float) $fila['precio_unitario'], 2, ',', '.'),
                number_format((float) $fila['total_bs'], 2, ',', '.'),
                $ordenObservacion,
                (string) $fila['usuario_nombre'],
            ];
            fputcsv($salida, array_map([$this, 'protegerValorCsv'], $valores), ';', '"', '');
        }

        fclose($salida);
        exit;
    }

    /**
     * Evita que el contenido textual del CSV se interprete como una fórmula de hoja de cálculo.
     * Parametro $valor: celda de texto que se incluirá en el CSV.
     * Devuelve: texto neutralizado para su apertura en hojas de cálculo.
     */
    private function protegerValorCsv(string $valor): string
    {
        $valorInicial = ltrim($valor, " \t\n\r\0\x0B");
        if ($valorInicial !== '' && in_array($valorInicial[0], ['=', '+', '-', '@'], true)) {
            return "'" . $valor;
        }

        return $valor;
    }

    /**
     * Prepara y envia el reporte general en formato Excel HTML.
     * Parametro $reportes: valor de tipo array recibido para esta operacion.
     * Parametro $filtros: criterios aplicados al listado o reporte.
     * Devuelve: no devuelve valor (void).
     * Efectos secundarios: modifica las cabeceras HTTP y genera la descarga.
     */
    // CUIDADO AL MODIFICAR: mantenga la validacion y el contrato de datos con los modulos que dependen de esta operacion.
    private function downloadResumenExcel(array $reportes, array $filtros): void
    {
        $filas = [
            ['Órdenes totales', (int) $reportes['ordenes']['total']],
            ['Órdenes abiertas', (int) $reportes['abiertas']['total']],
            ['Existencias registradas', (int) $reportes['inventario']['unidades']],
            ['Ingresos registrados (Bs.)', (float) $reportes['ingresos']['total']],
            ['Productos con stock bajo', (int) $reportes['bajos']['total']],
        ];

        $this->downloadExcel(
            'Reporte general AUTOGEST PRO',
            'Fecha de emisión: ' . date('d/m/Y H:i') . ' | Periodo: ' . $this->etiquetaPeriodo($filtros),
            ['Indicador', 'Valor'],
            $filas,
            'reporte-autogest-pro-' . date('Y-m-d') . '.xls'
        );
    }

    /**
     * Prepara y envia el registro de vehiculos en formato Excel HTML.
     * Parametro $filas: registros que se incluiran en el reporte.
     * Parametro $filtros: criterios aplicados al listado o reporte.
     * Devuelve: no devuelve valor (void).
     * Efectos secundarios: modifica las cabeceras HTTP y genera la descarga.
     */
    // CUIDADO AL MODIFICAR: mantenga la validacion y el contrato de datos con los modulos que dependen de esta operacion.
    private function downloadVehiculosExcel(array $filas, array $filtros): void
    {
        $filasExcel = [];
        foreach ($filas as $fila) {
            $filasExcel[] = [
                (int) $fila['id'],
                (string) ($fila['propietario'] ?? ''),
                (string) ($fila['telefono_cliente'] ?? ''),
                (string) ($fila['placa'] ?? ''),
                trim((string) ($fila['marca'] ?? '') . ' ' . (string) ($fila['modelo'] ?? '')),
                (string) ($fila['anio'] ?? ''),
                (string) ($fila['color'] ?? ''),
                (string) ($fila['estado'] ?? ''),
                (string) ($fila['fecha_registro'] ?? ''),
                (string) ($fila['diagnostico'] ?? ''),
            ];
        }

        $this->downloadExcel(
            'Registro de vehículos AUTOGEST PRO',
            'Fecha de emisión: ' . date('d/m/Y H:i') . ' | Periodo: ' . $this->etiquetaPeriodo($filtros),
            ['ID', 'Propietario', 'Teléfono', 'Placa', 'Marca y modelo', 'Año', 'Color', 'Estado', 'Fecha de registro', 'Diagnóstico'],
            $filasExcel,
            'registro-vehiculos-' . date('Y-m-d') . '.xls'
        );
    }

    /**
     * Prepara y envia el detalle de órdenes en formato Excel HTML.
     * Parametros $filas y $filtros: registros consultados y periodo aplicado.
     * Devuelve: no devuelve valor (void).
     * Efectos secundarios: modifica las cabeceras HTTP y genera la descarga.
     */
    private function downloadOrdenesExcel(array $filas, array $filtros): void
    {
        $filasExcel = [];
        foreach ($filas as $fila) {
            $filasExcel[] = [
                (string) $fila['codigo'],
                (string) $fila['fecha_ingreso'],
                (string) ($fila['fecha_prevista_retorno'] ?? ''),
                (string) ($fila['diagnostico'] ?? ''),
                (string) $fila['estado'],
                (string) ($fila['costo_final'] ?? ''),
                (int) $fila['precio_confirmado'] === 1 ? 'Sí' : 'No',
                (string) ($fila['fecha_entrega'] ?? ''),
                (string) $fila['placa'],
                (string) $fila['marca'],
                (string) $fila['modelo'],
                (string) $fila['cliente_nombre'],
                (string) $fila['usuario_nombre'],
                (string) ($fila['mecanicos'] ?? ''),
            ];
        }

        $this->downloadExcel(
            'Reporte de órdenes AUTOGEST PRO',
            'Fecha de emisión: ' . date('d/m/Y H:i') . ' | Periodo: ' . $this->etiquetaPeriodo($filtros),
            [
                'Código',
                'Fecha de ingreso',
                'Fecha prevista de retorno',
                'Diagnóstico',
                'Estado',
                'Costo final (Bs.)',
                'Precio confirmado',
                'Fecha de entrega',
                'Placa',
                'Marca',
                'Modelo',
                'Cliente',
                'Usuario que registra',
                'Mecánicos asignados',
            ],
            $filasExcel,
            'reporte-ordenes-' . date('Y-m-d') . '.xls'
        );
    }

    /**
     * Envía una tabla HTML con cabeceras de Excel para descargarla como archivo XLS.
     * Parametro $titulo: título visible del reporte.
     * Parametro $descripcion: periodo y fecha de emisión del reporte.
     * Parametro $encabezados: nombres de las columnas de la tabla.
     * Parametro $filas: datos preparados para imprimir en la tabla.
     * Parametro $nombreArchivo: nombre fijo del archivo de descarga.
     * Devuelve: no devuelve valor (void).
     * Efectos secundarios: modifica las cabeceras HTTP, genera la descarga y termina la respuesta.
     */
    private function downloadExcel(
        string $titulo,
        string $descripcion,
        array $encabezados,
        array $filas,
        string $nombreArchivo
    ): void {
        if (ob_get_level() > 0) {
            ob_clean();
        }

        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');

        $tituloReporte = $titulo;
        $descripcionReporte = $descripcion;
        $encabezadosReporte = $encabezados;
        $filasReporte = $filas;
        $filasResaltadas = [];
        require __DIR__ . '/../views/reportes/ExcelTableView.php';
        exit;
    }

    /**
     * Construye la etiqueta legible del periodo aplicado al reporte.
     * Parametro $filtros: criterios aplicados al listado o reporte.
     * Devuelve: valor de tipo string, segun el resultado de la operacion.
     * Efectos secundarios: ninguno fuera de los valores devueltos; opera sobre los datos recibidos.
     */
    // CUIDADO AL MODIFICAR: mantenga la validacion y el contrato de datos con los modulos que dependen de esta operacion.
    private function etiquetaPeriodo(array $filtros): string
    {
        $desde = $filtros['desde'] ?? null;
        $hasta = $filtros['hasta'] ?? null;
        if ($desde === null && $hasta === null) {
            return 'Todos los registros';
        }

        return 'Desde ' . ($desde ?? 'el inicio') . ' hasta ' . ($hasta ?? 'hoy');
    }
}
