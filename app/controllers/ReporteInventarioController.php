<?php

/*
 * Proposito: Coordina las solicitudes y acciones de ReporteInventarioController entre la entrada web, los validadores, los repositorios y las vistas.
 * Dependencias: /AuthController.php, /../repositories/RepuestoRepository.php, /../validators/ReporteValidator.php, /../views/reportes/ExcelTableView.php.
 * Consumidores/dependientes: app\controllers\RouterController.php.
 */

require_once __DIR__ . '/AuthController.php';
require_once __DIR__ . '/../repositories/RepuestoRepository.php';
require_once __DIR__ . '/../validators/ReporteValidator.php';

/**
 * Coordina las solicitudes de ReporteInventario: aplica autorizacion, valida entradas y comunica el repositorio con su vista.
 * Efectos secundarios: define la unidad de trabajo; los efectos concretos se describen en cada metodo.
 */
// CUIDADO AL MODIFICAR: esta clase participa en operaciones sensibles; conserve los contratos con los modulos dependientes.
class ReporteInventarioController
{
    /**
     * Genera la descarga del reporte solicitado con filtros validados.
     * Parametros: no recibe argumentos.
     * Devuelve: no devuelve valor (void).
     * Efectos secundarios: lee o modifica datos de la solicitud o sesion; puede modificar la respuesta HTTP o el estado de sesion; puede producir salida o cargar una vista.
     */
    // CUIDADO AL MODIFICAR: mantenga la validacion y el contrato de datos con los modulos que dependen de esta operacion.
    public function download(): void
    {
        AuthController::requireRoles(['administrador']);

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
            http_response_code(405);
            header('Allow: GET');
            echo 'Método no permitido.';
            return;
        }

        try {
            $fechas = (new ReporteValidator())->filtros($_GET);
        } catch (InvalidArgumentException $exception) {
            http_response_code(400);
            header('Content-Type: text/plain; charset=UTF-8');
            echo $exception->getMessage();
            return;
        }

        try {
            $filas = (new RepuestoRepository())->reporteInventario(
                $fechas['desde'],
                $fechas['hasta']
            );
        } catch (PDOException $exception) {
            error_log('No se pudo consultar el reporte de inventario: ' . $exception->getMessage());
            http_response_code(500);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'No se pudo consultar el inventario para generar el reporte.';
            return;
        }

        $filasExcel = [];
        $filasResaltadas = [];
        foreach ($filas as $fila) {
            $precio = $fila['precio_referencia'] === null
                ? null
                : (float) $fila['precio_referencia'];
            $stock = (int) $fila['stock'];
            $filasExcel[] = [
                (string) $fila['codigo'],
                (string) $fila['nombre'],
                (string) ($fila['descripcion'] ?? ''),
                $stock,
                (int) $fila['stock_minimo'],
                (int) $fila['entradas_periodo'],
                (int) $fila['salidas_periodo'],
                (int) $fila['ajustes_periodo'],
                $precio,
                $precio === null ? null : $precio * $stock,
            ];
            $filasResaltadas[] = (int) $fila['stock_bajo'] === 1;
        }

        $periodo = $this->etiquetaPeriodo($fechas['desde'], $fechas['hasta']);
        $tituloReporte = 'Reporte de inventario AUTOGEST PRO';
        $descripcionReporte = 'Fecha de emisión: ' . date('d/m/Y H:i')
            . ' | ' . $periodo . ' | Solo repuestos activos';
        $encabezadosReporte = [
            'Código',
            'Repuesto',
            'Descripción',
            'Stock actual',
            'Stock mínimo',
            'Entradas',
            'Salidas',
            'Ajustes',
            'Precio de referencia (Bs.)',
            'Valor del stock (Bs.)',
        ];
        $filasReporte = $filasExcel;
        $nombreArchivo = 'reporte-inventario-' . date('Y-m-d') . '.xls';
        if (ob_get_level() > 0) {
            ob_clean();
        }

        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        require __DIR__ . '/../views/reportes/ExcelTableView.php';
    }

    /**
     * Prepara una etiqueta legible para el intervalo de movimientos solicitado.
     * Parametro $desde: fecha inicial validada o null.
     * Parametro $hasta: fecha final validada o null.
     * Devuelve: valor de tipo string para mostrar en el reporte.
     * Efectos secundarios: ninguno.
     */
    private function etiquetaPeriodo(?string $desde, ?string $hasta): string
    {
        if ($desde === null && $hasta === null) {
            return 'Periodo de movimientos: todos';
        }

        return 'Periodo de movimientos: desde ' . ($desde ?? 'el inicio')
            . ' hasta ' . ($hasta ?? 'hoy');
    }
}
