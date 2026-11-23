<?php

/*
 * Proposito: Presenta datos de reportes en una tabla HTML que Excel puede abrir.
 * Dependencias: título, descripción, encabezados y filas preparados por un controlador.
 * Consumidores/dependientes: controladores de reportes.
 */
?>
<!DOCTYPE html>
<html lang="es" xmlns:x="urn:schemas-microsoft-com:office:excel">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($tituloReporte, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></title>
    <style>
        @page {
            size: landscape;
            mso-page-orientation: landscape;
            margin: 0.25in;
        }

        body,
        table {
            font-family: Arial, sans-serif;
            font-size: 9pt;
        }

        table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 4px;
            vertical-align: top;
            white-space: normal;
            word-wrap: break-word;
            overflow-wrap: anywhere;
        }
    </style>
    <!--[if gte mso 9]>
    <xml>
        <x:ExcelWorkbook>
            <x:ExcelWorksheets>
                <x:ExcelWorksheet>
                    <x:WorksheetOptions>
                        <x:PageSetup>
                            <x:Layout x:Orientation="Landscape"/>
                        </x:PageSetup>
                        <x:FitToPage/>
                        <x:FitWidth>1</x:FitWidth>
                        <x:FitHeight>0</x:FitHeight>
                    </x:WorksheetOptions>
                </x:ExcelWorksheet>
            </x:ExcelWorksheets>
        </x:ExcelWorkbook>
    </xml>
    <![endif]-->
</head>
<body>
    <table border="1" cellpadding="4" cellspacing="0">
        <thead>
            <tr>
                <th colspan="<?= count($encabezadosReporte) ?>">
                    <?= htmlspecialchars($tituloReporte, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                </th>
            </tr>
            <tr>
                <td colspan="<?= count($encabezadosReporte) ?>">
                    <?= htmlspecialchars($descripcionReporte, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                </td>
            </tr>
            <tr>
                <?php foreach ($encabezadosReporte as $encabezado): ?>
                    <th><?= htmlspecialchars((string) $encabezado, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php if ($filasReporte === []): ?>
                <tr>
                    <td colspan="<?= count($encabezadosReporte) ?>">No hay registros para los filtros seleccionados.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($filasReporte as $indiceFila => $fila): ?>
                    <tr<?= !empty($filasResaltadas[$indiceFila]) ? ' style="background-color: #f8d7da; color: #842029;"' : '' ?>>
                        <?php foreach ($fila as $valor): ?>
                            <td><?= htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
