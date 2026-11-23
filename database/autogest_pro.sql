-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 23-11-2026 a las 16:43:25
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `autogest_pro`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` varchar(200) DEFAULT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id`, `nombre`, `telefono`, `direccion`, `correo`, `activo`, `creado_en`) VALUES
(1, 'Cliente de prueba', '70000000', 'Sacaba', 'cliente@prueba.local', 1, '2026-10-30 23:02:15'),
(2, 'Jose Balderrama Jamestom', NULL, NULL, NULL, 0, '2026-11-03 12:51:49'),
(3, 'James Gatolino Song', NULL, NULL, NULL, 0, '2026-11-03 13:16:55'),
(4, 'James Gatolino Song', '89768734', 'Av. simón lopez', 'James@gmail.com', 1, '2026-11-15 00:51:07'),
(5, 'juan Ramos', '56756787', 'Av. Tunari', 'juan@gmail.com', 1, '2026-11-15 02:44:15'),
(6, 'Juan Perez', '34356545', 'Av. Melchor perez', 'Juan@gmail.com', 1, '2026-11-16 14:15:56'),
(7, 'Maria Lopez', '56756454', 'Av. Ustariz', '', 0, '2026-11-16 14:21:35'),
(8, 'Marcelo', '90878767', 'Av. Atawuaypa', 'Marcelo72@gmail.com', 1, '2026-11-23 09:58:15');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_estados`
--

CREATE TABLE `historial_estados` (
  `id` int(11) NOT NULL,
  `orden_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `estado_anterior` varchar(30) DEFAULT NULL,
  `estado_nuevo` varchar(30) NOT NULL,
  `fecha_cambio` timestamp NOT NULL DEFAULT current_timestamp(),
  `observaciones` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `historial_estados`
--

INSERT INTO `historial_estados` (`id`, `orden_id`, `usuario_id`, `estado_anterior`, `estado_nuevo`, `fecha_cambio`, `observaciones`) VALUES
(11, 6, 3, NULL, 'registrada', '2026-11-16 14:26:43', 'Orden creada'),
(12, 6, 4, 'registrada', 'registrada', '2026-11-16 14:33:29', 'Actualización técnica registrada por el mecánico'),
(13, 6, 4, 'registrada', 'lista', '2026-11-16 14:36:32', 'nk');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mecanicos`
--

CREATE TABLE `mecanicos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `especialidad` varchar(100) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `usuario_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `mecanicos`
--

INSERT INTO `mecanicos` (`id`, `nombre`, `telefono`, `especialidad`, `activo`, `usuario_id`) VALUES
(1, 'Rene', '70000001', 'Motor y eléctrico', 1, 4),
(2, 'mayra choqueta', '67574543', 'electrico', 0, NULL),
(3, 'Rambo', '76857564', 'Reparacion de motores', 1, NULL),
(4, 'ksdj', '', '', 1, NULL),
(5, 'Rambo', '74637362', 'Reparacion', 1, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `movimientos_inventario`
--

CREATE TABLE `movimientos_inventario` (
  `id` int(11) NOT NULL,
  `repuesto_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `orden_id` int(11) DEFAULT NULL,
  `tipo` enum('entrada','salida','ajuste') NOT NULL,
  `cantidad` int(10) UNSIGNED NOT NULL,
  `fecha_movimiento` timestamp NOT NULL DEFAULT current_timestamp(),
  `observaciones` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `movimientos_inventario`
--

INSERT INTO `movimientos_inventario` (`id`, `repuesto_id`, `usuario_id`, `orden_id`, `tipo`, `cantidad`, `fecha_movimiento`, `observaciones`) VALUES
(1, 1, 2, NULL, 'salida', 1, '2026-10-30 23:09:23', ''),
(2, 3, 2, NULL, 'salida', 3, '2026-11-23 10:22:27', 'VENTA');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ordenes_trabajo`
--

CREATE TABLE `ordenes_trabajo` (
  `id` int(11) NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `vehiculo_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha_ingreso` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_prevista_retorno` date DEFAULT NULL,
  `diagnostico` text DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `estado` enum('registrada','diagnostico','en_revision','en_reparacion','pendiente_repuesto','lista','listo','entregada','cancelada') NOT NULL DEFAULT 'registrada',
  `costo_final` decimal(10,2) DEFAULT NULL,
  `precio_confirmado` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_entrega` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `ordenes_trabajo`
--

INSERT INTO `ordenes_trabajo` (`id`, `codigo`, `vehiculo_id`, `usuario_id`, `fecha_ingreso`, `fecha_prevista_retorno`, `diagnostico`, `observaciones`, `estado`, `costo_final`, `precio_confirmado`, `fecha_entrega`) VALUES
(6, 'OT-20261116-541', 7, 3, '2026-11-16 15:22:00', '2026-11-20', 'ya esta completaado la reparacion', 'ninguna', 'lista', 1980.00, 0, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `orden_mecanicos`
--

CREATE TABLE `orden_mecanicos` (
  `orden_id` int(11) NOT NULL,
  `mecanico_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `orden_mecanicos`
--

INSERT INTO `orden_mecanicos` (`orden_id`, `mecanico_id`) VALUES
(6, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--

CREATE TABLE `pagos` (
  `id` int(11) NOT NULL,
  `orden_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha_pago` datetime NOT NULL DEFAULT current_timestamp(),
  `monto` decimal(10,2) NOT NULL,
  `metodo` enum('efectivo','transferencia','otro') NOT NULL DEFAULT 'efectivo',
  `referencia` varchar(100) DEFAULT NULL,
  `observaciones` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `repuestos`
--

CREATE TABLE `repuestos` (
  `id` int(11) NOT NULL,
  `codigo` varchar(40) NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `stock` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `stock_minimo` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `precio_referencia` decimal(10,2) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `repuestos`
--

INSERT INTO `repuestos` (`id`, `codigo`, `nombre`, `descripcion`, `stock`, `stock_minimo`, `precio_referencia`, `activo`, `fecha_registro`) VALUES
(1, 'ACEITE-10W30', 'Aceite 10W30', 'Aceite multigrado', 19, 5, 45.00, 1, '2026-11-23 06:41:56'),
(2, 'FILTRO-AIRE', 'Filtro de aire', 'Filtro estándar', 10, 3, 35.00, 1, '2026-11-23 06:41:56'),
(3, 'BUJIA-NGK', 'Bujía NGK', 'Bujía de encendido', 17, 4, 22.00, 1, '2026-11-23 06:41:56'),
(4, 'PASTILLA-FRENO', 'Zapatas de freno', 'Zapatas de freno traseras', 8, 2, 120.00, 1, '2026-11-23 06:41:56'),
(5, 'CORREA-DIST', 'Correa de distribución', 'Correa dentada', 5, 1, 180.00, 1, '2026-11-23 06:41:56');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `repuestos_orden`
--

CREATE TABLE `repuestos_orden` (
  `id` int(11) NOT NULL,
  `orden_id` int(11) NOT NULL,
  `repuesto_id` int(11) NOT NULL,
  `cantidad` int(10) UNSIGNED NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `nombre` varchar(40) NOT NULL,
  `descripcion` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `nombre`, `descripcion`) VALUES
(1, 'administrador', 'Administración del sistema'),
(2, 'recepcionista', 'Gestión de clientes y órdenes'),
(3, 'mecanico', 'Seguimiento técnico de reparaciones');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tareas_orden`
--

CREATE TABLE `tareas_orden` (
  `id` int(11) NOT NULL,
  `orden_id` int(11) NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  `costo` decimal(10,2) DEFAULT NULL,
  `estado` enum('pendiente','en_proceso','completada') NOT NULL DEFAULT 'pendiente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tareas_orden`
--

INSERT INTO `tareas_orden` (`id`, `orden_id`, `descripcion`, `costo`, `estado`) VALUES
(11, 6, 'Diagnóstico de motor', 80.00, 'completada'),
(12, 6, 'Cambio de retenes por fuga de aceite', 180.00, 'completada'),
(13, 6, 'Calibración del reglaje de válvulas', 220.00, 'completada'),
(14, 6, 'Reparación general del motor', 1500.00, 'completada'),
(15, 6, 'ninguno', NULL, 'completada'),
(16, 6, 'no', NULL, 'completada');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tarifas_trabajos`
--

CREATE TABLE `tarifas_trabajos` (
  `id` int(11) NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  `precio` decimal(10,2) UNSIGNED NOT NULL,
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tarifas_trabajos`
--

INSERT INTO `tarifas_trabajos` (`id`, `descripcion`, `precio`, `actualizado_en`) VALUES
(1, 'Inspección general del vehículo y niveles', 40.00, '2026-11-16 16:04:23'),
(2, 'Cambio de aceite del motor', 60.00, '2026-11-16 16:04:23'),
(3, 'Cambio de filtro de aceite', 20.00, '2026-11-16 16:04:23'),
(4, 'Cambio de filtro de aire del motor', 20.00, '2026-11-16 16:04:23'),
(5, 'Cambio de filtro de combustible', 40.00, '2026-11-16 16:04:23'),
(6, 'Diagnóstico de motor', 80.00, '2026-11-16 16:04:23'),
(7, 'Cambio de bujías', 80.00, '2026-11-16 16:04:23'),
(8, 'Cambio de bobinas de encendido', 80.00, '2026-11-16 16:04:23'),
(9, 'Cambio de correa auxiliar', 100.00, '2026-11-16 16:04:23'),
(10, 'Cambio de kit de distribución según fabricante', 350.00, '2026-11-16 16:04:23'),
(11, 'Cambio de retenes por fuga de aceite', 180.00, '2026-11-16 16:04:23'),
(12, 'Calibración del reglaje de válvulas', 220.00, '2026-11-16 16:04:23'),
(13, 'Cambio de empaque de culata', 800.00, '2026-11-16 16:04:23'),
(14, 'Reparación general del motor', 1500.00, '2026-11-16 16:04:23'),
(15, 'Inspección del sistema de frenos', 40.00, '2026-11-16 16:04:23'),
(16, 'Mantenimiento y limpieza del sistema de frenos', 80.00, '2026-11-16 16:04:23'),
(17, 'Cambio de pastillas de freno', 80.00, '2026-11-16 16:04:23'),
(18, 'Cambio de discos o tambores de freno', 120.00, '2026-11-16 16:04:23'),
(19, 'Cambio de líquido de frenos', 80.00, '2026-11-16 16:04:23'),
(20, 'Ajuste del freno de estacionamiento', 50.00, '2026-11-16 16:04:23'),
(21, 'Cambio de refrigerante', 60.00, '2026-11-16 16:04:23'),
(22, 'Reparación de fuga del sistema de refrigeración', 120.00, '2026-11-16 16:04:23'),
(23, 'Cambio de radiador o mangueras', 150.00, '2026-11-16 16:04:23'),
(24, 'Cambio de termostato o bomba de agua', 180.00, '2026-11-16 16:04:23'),
(25, 'Reparación del ventilador de refrigeración', 120.00, '2026-11-16 16:04:23'),
(26, 'Inspección del sistema de embrague', 50.00, '2026-11-16 16:04:23'),
(27, 'Cambio de disco de embrague dañado', 450.00, '2026-11-16 16:04:23'),
(28, 'Cambio de kit de embrague', 500.00, '2026-11-16 16:04:23'),
(29, 'Cambio de aceite de transmisión', 80.00, '2026-11-16 16:04:23'),
(30, 'Cambio de semieje o junta homocinética', 180.00, '2026-11-16 16:04:23'),
(31, 'Inspección de suspensión y amortiguadores', 50.00, '2026-11-16 16:04:23'),
(32, 'Cambio de amortiguadores', 160.00, '2026-11-16 16:04:23'),
(33, 'Cambio de rótulas o terminales de dirección', 120.00, '2026-11-16 16:04:23'),
(34, 'Alineación de ruedas', 80.00, '2026-11-16 16:04:23'),
(35, 'Balanceo de ruedas', 60.00, '2026-11-16 16:04:23'),
(36, 'Prueba de batería', 30.00, '2026-11-16 16:04:23'),
(37, 'Reparación del alternador y sistema de carga', 180.00, '2026-11-16 16:04:23'),
(38, 'Reparación del motor de arranque', 150.00, '2026-11-16 16:04:23'),
(39, 'Reparación de luces o sistema eléctrico', 100.00, '2026-11-16 16:04:23'),
(40, 'Inspección del sistema de escape', 40.00, '2026-11-16 16:04:23');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `rol_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `rol_id`, `nombre`, `usuario`, `password_hash`, `activo`, `creado_en`) VALUES
(1, 1, 'Jhonatan', 'Autogest', '$2y$10$lrNOGcg/Htf1XCKkomcFM.NdL7n5.fWvB9pvTRL1DdJ0n8K3pU1MK', 0, '2026-10-27 11:25:28'),
(2, 1, 'Jhonatan', 'jhonatan', '$2y$10$XaIBEZbFY6YK4iab6FjUPe2gh3IlyzqOLfNm24xr3syNlIFoQ4/p2', 1, '2026-10-27 13:13:52'),
(3, 2, 'Juan', 'juan', '$2y$10$MG4Izvf0cj.Q0etkPWIIw.FQNTM2nXWQNMSJXYJyR6VEDEzqFFxla', 1, '2026-10-27 13:14:07'),
(4, 3, 'Rene', 'rene', '$2y$10$sxaflrO79phegCMY7487oOecdi6/iUThKV9VB1A18epbK3obFRNaW', 1, '2026-10-27 13:14:16'),
(11, 2, 'daniela choqueta', 'mayradaniela', '$2y$10$xWKhFdvSq64Z6KLDNMlYm.UydDLEtTzJ/tvrxZY4smSP7FfK4mS4O', 1, '2026-11-04 00:06:15'),
(12, 1, 'jhon', 'jhon', '$2y$10$AQ5IfOdqq5JaRFaRRWafVe6okUBtaTy1sofvPd/3q3fpIqyXaQEgi', 1, '2026-11-19 15:26:10');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vehiculos`
--

CREATE TABLE `vehiculos` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `placa` varchar(20) NOT NULL,
  `marca` varchar(60) NOT NULL,
  `modelo` varchar(60) NOT NULL,
  `anio` smallint(5) UNSIGNED DEFAULT NULL,
  `color` varchar(40) DEFAULT NULL,
  `diagnostico` text DEFAULT NULL,
  `estado` enum('registrado','en_revision','pendiente_repuesto','en_reparacion','listo','entregado') NOT NULL DEFAULT 'registrado',
  `fecha_registro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `vehiculos`
--

INSERT INTO `vehiculos` (`id`, `cliente_id`, `placa`, `marca`, `modelo`, `anio`, `color`, `diagnostico`, `estado`, `fecha_registro`) VALUES
(1, 1, 'DJT456', 'Toyota', 'Corolla', 2020, 'Blanco', 'falla de injectores', 'registrado', '2026-11-03 09:09:00'),
(5, 5, '453HJG', 'Ferrari', 'changan', 2009, 'negro', 'Mantenimiento  de frenos y otros', 'listo', '2026-11-18 03:52:00'),
(6, 6, 'HGK8973', 'Toyota', 'Corolla', 2027, 'Blanco', 'Cambio de aceite y filtro', 'registrado', '2026-11-10 14:46:00'),
(7, 7, 'JHG878', 'Suzuki', 'Swift', 2018, 'Rojo', 'Revision de frenos delanteros', 'entregado', '2026-11-16 17:14:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vehiculo_imagenes`
--

CREATE TABLE `vehiculo_imagenes` (
  `id` int(11) NOT NULL,
  `vehiculo_id` int(11) NOT NULL,
  `nombre_archivo` varchar(255) NOT NULL,
  `ruta` varchar(255) NOT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `vehiculo_imagenes`
--

INSERT INTO `vehiculo_imagenes` (`id`, `vehiculo_id`, `nombre_archivo`, `ruta`, `creado_en`) VALUES
(14, 5, 'pexels-brianjiz-33916472.jpg', 'uploads/vehiculos/vehiculo_5_22cc5353028b2117.jpg', '2026-11-15 02:44:15'),
(15, 6, 'pexels-cottonbro-7019374.jpg', 'uploads/vehiculos/vehiculo_6_c7c63fa6ed0272c8.jpg', '2026-11-16 14:15:56'),
(16, 6, 'pexels-cottonbro-7019374.jpg', 'uploads/vehiculos/vehiculo_6_c89085f66c9adac1.jpg', '2026-11-16 14:19:18'),
(17, 7, 'logo.png', 'uploads/vehiculos/vehiculo_7_102e941a4a37f09f.png', '2026-11-16 14:21:35');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `historial_estados`
--
ALTER TABLE `historial_estados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `orden_id` (`orden_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `mecanicos`
--
ALTER TABLE `mecanicos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mecanicos_usuario_id` (`usuario_id`);

--
-- Indices de la tabla `movimientos_inventario`
--
ALTER TABLE `movimientos_inventario`
  ADD PRIMARY KEY (`id`),
  ADD KEY `repuesto_id` (`repuesto_id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `orden_id` (`orden_id`);

--
-- Indices de la tabla `ordenes_trabajo`
--
ALTER TABLE `ordenes_trabajo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `vehiculo_id` (`vehiculo_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `orden_mecanicos`
--
ALTER TABLE `orden_mecanicos`
  ADD PRIMARY KEY (`orden_id`,`mecanico_id`),
  ADD KEY `mecanico_id` (`mecanico_id`);

--
-- Indices de la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `orden_id` (`orden_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `repuestos`
--
ALTER TABLE `repuestos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Indices de la tabla `repuestos_orden`
--
ALTER TABLE `repuestos_orden`
  ADD PRIMARY KEY (`id`),
  ADD KEY `orden_id` (`orden_id`),
  ADD KEY `repuesto_id` (`repuesto_id`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `tareas_orden`
--
ALTER TABLE `tareas_orden`
  ADD PRIMARY KEY (`id`),
  ADD KEY `orden_id` (`orden_id`);

--
-- Indices de la tabla `tarifas_trabajos`
--
ALTER TABLE `tarifas_trabajos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `descripcion` (`descripcion`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`),
  ADD KEY `rol_id` (`rol_id`);

--
-- Indices de la tabla `vehiculos`
--
ALTER TABLE `vehiculos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `placa` (`placa`),
  ADD KEY `cliente_id` (`cliente_id`);

--
-- Indices de la tabla `vehiculo_imagenes`
--
ALTER TABLE `vehiculo_imagenes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vehiculo_id` (`vehiculo_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `historial_estados`
--
ALTER TABLE `historial_estados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `mecanicos`
--
ALTER TABLE `mecanicos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `movimientos_inventario`
--
ALTER TABLE `movimientos_inventario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ordenes_trabajo`
--
ALTER TABLE `ordenes_trabajo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `repuestos`
--
ALTER TABLE `repuestos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `repuestos_orden`
--
ALTER TABLE `repuestos_orden`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `tareas_orden`
--
ALTER TABLE `tareas_orden`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tarifas_trabajos`
--
ALTER TABLE `tarifas_trabajos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=282;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `vehiculos`
--
ALTER TABLE `vehiculos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `vehiculo_imagenes`
--
ALTER TABLE `vehiculo_imagenes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `historial_estados`
--
ALTER TABLE `historial_estados`
  ADD CONSTRAINT `historial_estados_ibfk_1` FOREIGN KEY (`orden_id`) REFERENCES `ordenes_trabajo` (`id`),
  ADD CONSTRAINT `historial_estados_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `movimientos_inventario`
--
ALTER TABLE `movimientos_inventario`
  ADD CONSTRAINT `movimientos_inventario_ibfk_1` FOREIGN KEY (`repuesto_id`) REFERENCES `repuestos` (`id`),
  ADD CONSTRAINT `movimientos_inventario_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `movimientos_inventario_ibfk_3` FOREIGN KEY (`orden_id`) REFERENCES `ordenes_trabajo` (`id`);

--
-- Filtros para la tabla `ordenes_trabajo`
--
ALTER TABLE `ordenes_trabajo`
  ADD CONSTRAINT `ordenes_trabajo_ibfk_1` FOREIGN KEY (`vehiculo_id`) REFERENCES `vehiculos` (`id`),
  ADD CONSTRAINT `ordenes_trabajo_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `orden_mecanicos`
--
ALTER TABLE `orden_mecanicos`
  ADD CONSTRAINT `orden_mecanicos_ibfk_1` FOREIGN KEY (`orden_id`) REFERENCES `ordenes_trabajo` (`id`),
  ADD CONSTRAINT `orden_mecanicos_ibfk_2` FOREIGN KEY (`mecanico_id`) REFERENCES `mecanicos` (`id`);

--
-- Filtros para la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD CONSTRAINT `pagos_ibfk_1` FOREIGN KEY (`orden_id`) REFERENCES `ordenes_trabajo` (`id`),
  ADD CONSTRAINT `pagos_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `repuestos_orden`
--
ALTER TABLE `repuestos_orden`
  ADD CONSTRAINT `repuestos_orden_ibfk_1` FOREIGN KEY (`orden_id`) REFERENCES `ordenes_trabajo` (`id`),
  ADD CONSTRAINT `repuestos_orden_ibfk_2` FOREIGN KEY (`repuesto_id`) REFERENCES `repuestos` (`id`);

--
-- Filtros para la tabla `tareas_orden`
--
ALTER TABLE `tareas_orden`
  ADD CONSTRAINT `tareas_orden_ibfk_1` FOREIGN KEY (`orden_id`) REFERENCES `ordenes_trabajo` (`id`);

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`);

--
-- Filtros para la tabla `vehiculos`
--
ALTER TABLE `vehiculos`
  ADD CONSTRAINT `vehiculos_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`);

--
-- Filtros para la tabla `vehiculo_imagenes`
--
ALTER TABLE `vehiculo_imagenes`
  ADD CONSTRAINT `vehiculo_imagenes_ibfk_1` FOREIGN KEY (`vehiculo_id`) REFERENCES `vehiculos` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
