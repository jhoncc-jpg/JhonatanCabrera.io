<?php

class OrdenCatalogo
{
    public static function catalogoTareas(): array
    {
        return [
            'Mantenimiento general' => [
                'Inspección general del vehículo y niveles',
                'Cambio de aceite del motor',
                'Cambio de filtro de aceite',
                'Cambio de filtro de aire del motor',
                'Cambio de filtro de combustible',
            ],
            'Motor' => [
                'Diagnóstico de motor',
                'Cambio de bujías',
                'Cambio de bobinas de encendido',
                'Cambio de correa auxiliar',
                'Cambio de kit de distribución según fabricante',
                'Cambio de retenes por fuga de aceite',
                'Calibración del reglaje de válvulas',
                'Cambio de empaque de culata',
                'Reparación general del motor',
            ],
            'Frenos' => [
                'Inspección del sistema de frenos',
                'Mantenimiento y limpieza del sistema de frenos',
                'Cambio de pastillas de freno',
                'Cambio de discos o tambores de freno',
                'Cambio de líquido de frenos',
                'Ajuste del freno de estacionamiento',
            ],
            'Refrigeración' => [
                'Cambio de refrigerante',
                'Reparación de fuga del sistema de refrigeración',
                'Cambio de radiador o mangueras',
                'Cambio de termostato o bomba de agua',
                'Reparación del ventilador de refrigeración',
            ],
            'Transmisión y embrague' => [
                'Inspección del sistema de embrague',
                'Cambio de disco de embrague dañado',
                'Cambio de kit de embrague',
                'Cambio de aceite de transmisión',
                'Cambio de semieje o junta homocinética',
            ],
            'Suspensión, dirección y ruedas' => [
                'Inspección de suspensión y amortiguadores',
                'Cambio de amortiguadores',
                'Cambio de rótulas o terminales de dirección',
                'Alineación de ruedas',
                'Balanceo de ruedas',
            ],
            'Sistema eléctrico' => [
                'Prueba de batería',
                'Reparación del alternador y sistema de carga',
                'Reparación del motor de arranque',
                'Reparación de luces o sistema eléctrico',
            ],
            'Escape' => [
                'Inspección del sistema de escape',
            ],
        ];
    }

    public static function preciosReferencialesTareas(): array
    {
        return [
            'Inspección general del vehículo y niveles' => 40,
            'Cambio de aceite del motor' => 60,
            'Cambio de filtro de aceite' => 20,
            'Cambio de filtro de aire del motor' => 20,
            'Cambio de filtro de combustible' => 40,
            'Diagnóstico de motor' => 80,
            'Cambio de bujías' => 80,
            'Cambio de bobinas de encendido' => 80,
            'Cambio de correa auxiliar' => 100,
            'Cambio de kit de distribución según fabricante' => 350,
            'Cambio de retenes por fuga de aceite' => 180,
            'Calibración del reglaje de válvulas' => 220,
            'Cambio de empaque de culata' => 800,
            'Reparación general del motor' => 1500,
            'Inspección del sistema de frenos' => 40,
            'Mantenimiento y limpieza del sistema de frenos' => 80,
            'Cambio de pastillas de freno' => 80,
            'Cambio de discos o tambores de freno' => 120,
            'Cambio de líquido de frenos' => 80,
            'Ajuste del freno de estacionamiento' => 50,
            'Cambio de refrigerante' => 60,
            'Reparación de fuga del sistema de refrigeración' => 120,
            'Cambio de radiador o mangueras' => 150,
            'Cambio de termostato o bomba de agua' => 180,
            'Reparación del ventilador de refrigeración' => 120,
            'Inspección del sistema de embrague' => 50,
            'Cambio de disco de embrague dañado' => 450,
            'Cambio de kit de embrague' => 500,
            'Cambio de aceite de transmisión' => 80,
            'Cambio de semieje o junta homocinética' => 180,
            'Inspección de suspensión y amortiguadores' => 50,
            'Cambio de amortiguadores' => 160,
            'Cambio de rótulas o terminales de dirección' => 120,
            'Alineación de ruedas' => 80,
            'Balanceo de ruedas' => 60,
            'Prueba de batería' => 30,
            'Reparación del alternador y sistema de carga' => 180,
            'Reparación del motor de arranque' => 150,
            'Reparación de luces o sistema eléctrico' => 100,
            'Inspección del sistema de escape' => 40,
        ];
    }
}
