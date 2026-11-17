-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 23-10-2026 a las 21:14:22
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `rutanexo`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asignacion_flota`
--

CREATE TABLE `asignacion_flota` (
  `id_asignacion` int(11) NOT NULL,
  `id_vehiculo` int(11) NOT NULL,
  `id_conductor` int(11) NOT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `id_estados` int(11) NOT NULL DEFAULT 4
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `calificacion_conductor`
--

CREATE TABLE `calificacion_conductor` (
  `id_evaluacion` int(11) NOT NULL,
  `id_conductor` int(11) NOT NULL,
  `documento` bigint(20) NOT NULL,
  `calificacion` int(11) DEFAULT NULL,
  `comentario` text DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categoria_material`
--

CREATE TABLE `categoria_material` (
  `id_tipo_material` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `categoria_material`
--

INSERT INTO `categoria_material` (`id_tipo_material`, `nombre`, `descripcion`) VALUES
(1, 'Vidrio', 'Botellas y frascos de vidrio'),
(2, 'Cartón corrugado', 'Cajas y empaques de cartón'),
(3, 'Plástico HDPE', 'Envases de aseo y bidones');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `centro_acopio`
--

CREATE TABLE `centro_acopio` (
  `id_punto` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `horario` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `centro_acopio`
--

INSERT INTO `centro_acopio` (`id_punto`, `nombre`, `direccion`, `horario`) VALUES
(1, 'Centro de Acopio Picaleña', 'Carrera 8 # 12-40, Picaleña', 'Lunes a viernes, 7:30 a. m. - 4:30 p. m.'),
(2, 'Ecopunto Terminal', 'Calle 20 # 1-95, Terminal de Transportes', 'Todos los días, 8:00 a. m. - 6:00 p. m.');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ciudad`
--

CREATE TABLE `ciudad` (
  `id_ciudad` int(11) NOT NULL,
  `id_departamento` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `ciudad`
--

INSERT INTO `ciudad` (`id_ciudad`, `id_departamento`, `nombre`) VALUES
(1, 1, 'Leticia'),
(4, 2, 'Bello'),
(3, 2, 'Envigado'),
(2, 2, 'Medellín'),
(5, 3, 'Arauca'),
(6, 4, 'Barranquilla'),
(7, 4, 'Soledad'),
(8, 5, 'Bogotá'),
(9, 6, 'Cartagena'),
(10, 7, 'Tunja'),
(11, 8, 'Manizales'),
(12, 9, 'Florencia'),
(13, 10, 'Yopal'),
(14, 11, 'Popayán'),
(15, 12, 'Valledupar'),
(16, 13, 'Quibdó'),
(17, 14, 'Montería'),
(20, 15, 'Facatativá'),
(21, 15, 'Girardot'),
(18, 15, 'Soacha'),
(19, 15, 'Zipaquirá'),
(22, 16, 'Inírida'),
(23, 17, 'San José del Guaviare'),
(24, 18, 'Neiva'),
(25, 19, 'Riohacha'),
(26, 20, 'Santa Marta'),
(27, 21, 'Villavicencio'),
(28, 22, 'Pasto'),
(29, 23, 'Cúcuta'),
(30, 24, 'Mocoa'),
(31, 25, 'Armenia'),
(32, 26, 'Pereira'),
(33, 27, 'San Andrés'),
(34, 28, 'Bucaramanga'),
(35, 28, 'Floridablanca'),
(36, 29, 'Sincelejo'),
(38, 30, 'Espinal'),
(39, 30, 'Honda'),
(37, 30, 'Ibagué'),
(40, 30, 'Melgar'),
(43, 31, 'Buenaventura'),
(41, 31, 'Cali'),
(42, 31, 'Palmira'),
(44, 32, 'Mitú'),
(45, 33, 'Puerto Carreño');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clase_licencia`
--

CREATE TABLE `clase_licencia` (
  `id_categoria_licencia` int(11) NOT NULL,
  `nombre` varchar(20) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `clase_licencia`
--

INSERT INTO `clase_licencia` (`id_categoria_licencia`, `nombre`, `descripcion`) VALUES
(1, 'A1', 'Motocicletas con cilindrada hasta 125 cc'),
(2, 'A2', 'Motocicletas, motociclos y mototriciclos con cilindrada mayor a 125 cc'),
(3, 'B1', 'Automóviles, camperos, camionetas y microbuses de servicio particular'),
(4, 'B2', 'Camiones rígidos, buses y busetas de servicio particular'),
(5, 'B3', 'Vehículos articulados de servicio particular'),
(6, 'C1', 'Automóviles, camperos, camionetas y microbuses de servicio público'),
(7, 'C2', 'Camiones rígidos, buses y busetas de servicio público'),
(8, 'C3', 'Vehículos articulados de servicio público');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clase_mantenimiento`
--

CREATE TABLE `clase_mantenimiento` (
  `id_tipo_mantenimiento` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `conductor`
--

CREATE TABLE `conductor` (
  `id_conductor` int(11) NOT NULL,
  `id_empresa` int(10) UNSIGNED DEFAULT NULL,
  `documento` bigint(20) NOT NULL,
  `id_licencia` int(11) DEFAULT NULL,
  `id_estados` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cuenta_puntos`
--

CREATE TABLE `cuenta_puntos` (
  `id_saldo` int(11) NOT NULL,
  `documento` bigint(20) NOT NULL,
  `puntos` int(11) NOT NULL DEFAULT 0,
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `departamento`
--

CREATE TABLE `departamento` (
  `id_departamento` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `departamento`
--

INSERT INTO `departamento` (`id_departamento`, `nombre`) VALUES
(1, 'Amazonas'),
(2, 'Antioquia'),
(3, 'Arauca'),
(4, 'Atlántico'),
(5, 'Bogotá D.C.'),
(6, 'Bolívar'),
(7, 'Boyacá'),
(8, 'Caldas'),
(9, 'Caquetá'),
(10, 'Casanare'),
(11, 'Cauca'),
(12, 'Cesar'),
(13, 'Chocó'),
(14, 'Córdoba'),
(15, 'Cundinamarca'),
(16, 'Guainía'),
(17, 'Guaviare'),
(18, 'Huila'),
(19, 'La Guajira'),
(20, 'Magdalena'),
(21, 'Meta'),
(22, 'Nariño'),
(23, 'Norte de Santander'),
(24, 'Putumayo'),
(25, 'Quindío'),
(26, 'Risaralda'),
(27, 'San Andrés y Providencia'),
(28, 'Santander'),
(29, 'Sucre'),
(30, 'Tolima'),
(31, 'Valle del Cauca'),
(32, 'Vaupés'),
(33, 'Vichada');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_servicio`
--

CREATE TABLE `detalle_servicio` (
  `id_detalle` int(11) NOT NULL,
  `id_mantenimiento` int(11) NOT NULL,
  `repuesto` varchar(150) DEFAULT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `costo_unitario` decimal(12,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `documentacion_vehiculo`
--

CREATE TABLE `documentacion_vehiculo` (
  `id_documento` int(11) NOT NULL,
  `id_vehiculo` int(11) NOT NULL,
  `tipo_documento` enum('SOAT','Tecnomecanica','Seguro','Tarjeta de Propiedad') DEFAULT NULL,
  `fecha_expedicion` date DEFAULT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `archivo_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empresa`
--

CREATE TABLE `empresa` (
  `id_empresa` int(10) UNSIGNED NOT NULL,
  `nit` varchar(20) NOT NULL,
  `razon_social` varchar(150) NOT NULL,
  `nombre_representante` varchar(150) NOT NULL,
  `documento_representante` varchar(20) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `id_ciudad` int(11) DEFAULT NULL,
  `camara_comercio` varchar(255) DEFAULT NULL,
  `id_estados` int(11) NOT NULL DEFAULT 10,
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estado_registro`
--

CREATE TABLE `estado_registro` (
  `id_estados` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `estado_registro`
--

INSERT INTO `estado_registro` (`id_estados`, `nombre`) VALUES
(6, 'Agendado'),
(9, 'Anulado'),
(5, 'Cerrada'),
(13, 'Descartada'),
(2, 'Deshabilitado'),
(7, 'En ejecución'),
(10, 'En espera'),
(3, 'En taller'),
(11, 'En trámite'),
(1, 'Habilitado'),
(12, 'Realizada'),
(8, 'Terminado'),
(4, 'Vigente');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `incidencia`
--

CREATE TABLE `incidencia` (
  `id_incidente` int(11) NOT NULL,
  `id_viaje` int(11) NOT NULL,
  `id_conductor` int(11) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `gravedad` enum('Baja','Media','Alta') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `licencia`
--

CREATE TABLE `licencia` (
  `id_licencia` int(11) NOT NULL,
  `numero` varchar(50) NOT NULL,
  `id_categoria_licencia` int(11) NOT NULL,
  `fecha_expedicion` date NOT NULL,
  `fecha_vencimiento` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `modelo_vehiculo`
--

CREATE TABLE `modelo_vehiculo` (
  `id_modelo` int(11) NOT NULL,
  `marca` varchar(100) NOT NULL,
  `modelo` varchar(100) NOT NULL,
  `anio` int(11) NOT NULL,
  `tipo_combustible` varchar(50) DEFAULT NULL,
  `capacidad` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pago`
--

CREATE TABLE `pago` (
  `id_pago` int(10) UNSIGNED NOT NULL,
  `id_suscripcion` int(10) UNSIGNED NOT NULL,
  `metodo` varchar(20) NOT NULL COMMENT 'gratuito | pse | nequi | bancolombia | efectivo',
  `referencia` varchar(30) NOT NULL,
  `valor` decimal(12,2) NOT NULL DEFAULT 0.00,
  `comprobante` varchar(255) DEFAULT NULL,
  `observacion` varchar(255) DEFAULT NULL,
  `id_estados` int(11) NOT NULL DEFAULT 10 COMMENT '10 En espera, 11 En trámite, 12 Realizada, 9 Anulado',
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_vencimiento` datetime DEFAULT NULL,
  `fecha_pago` datetime DEFAULT NULL,
  `fecha_verificacion` datetime DEFAULT NULL,
  `verificado_por` int(11) DEFAULT NULL COMMENT 'super_admin.documento'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `paradero`
--

CREATE TABLE `paradero` (
  `id_parada` int(11) NOT NULL,
  `id_ruta` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `ubicacion` varchar(255) DEFAULT NULL,
  `orden_parada` int(11) DEFAULT NULL,
  `lat` decimal(9,6) DEFAULT NULL,
  `lng` decimal(9,6) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `paradero`
--

INSERT INTO `paradero` (`id_parada`, `id_ruta`, `nombre`, `ubicacion`, `orden_parada`, `lat`, `lng`) VALUES
(10101, 101, 'Delicias', 'Ibagué, Tolima', 1, 4.494756, -75.243954),
(10102, 101, 'C.C. La Estación', 'Ibagué, Tolima', 2, 4.485747, -75.236721),
(10103, 101, 'Estadio', 'Ibagué, Tolima', 3, 4.462323, -75.233104),
(10104, 101, 'Hospital Federico Lleras', 'Ibagué, Tolima', 4, 4.458720, -75.233104),
(10105, 101, 'Comando de Policía', 'Ibagué, Tolima', 5, 4.447909, -75.227227),
(10106, 101, 'Museo Panóptico', 'Ibagué, Tolima', 6, 4.440251, -75.234460),
(10107, 101, 'Cra 7 x Cll 5', 'Ibagué, Tolima', 7, 4.432594, -75.236269),
(10201, 102, 'Cañaveral', 'Ibagué, Tolima', 1, 4.501963, -75.246667),
(10202, 102, 'Av. Ambalá x Cll 77', 'Ibagué, Tolima', 2, 4.499260, -75.243050),
(10203, 102, 'C.C. La 5', 'Ibagué, Tolima', 3, 4.453314, -75.231296),
(10204, 102, 'Plaza 21', 'Ibagué, Tolima', 4, 4.447008, -75.228131),
(10205, 102, 'Cra 5 x Cll 16', 'Ibagué, Tolima', 5, 4.444305, -75.227679),
(10206, 102, 'Clarita Botero', 'Ibagué, Tolima', 6, 4.436197, -75.252092),
(10301, 103, 'Boquerón', 'Ibagué, Tolima', 1, 4.411873, -75.191513),
(10302, 103, 'El Tejar', 'Ibagué, Tolima', 2, 4.419080, -75.202363),
(10303, 103, 'Mercacentro 8', 'Ibagué, Tolima', 3, 4.424486, -75.206884),
(10304, 103, 'Piscinas Olímpicas', 'Ibagué, Tolima', 4, 4.429891, -75.205075),
(10305, 103, 'Monumento a La Paz', 'Ibagué, Tolima', 5, 4.435296, -75.194225),
(10306, 103, 'Glorieta Picaleña', 'Ibagué, Tolima', 6, 4.425386, -75.159867),
(10307, 103, 'Aeropuerto Perales', 'Ibagué, Tolima', 7, 4.421783, -75.133647),
(10308, 103, 'Arboleda Campestre', 'Ibagué, Tolima', 8, 4.443405, -75.150826),
(10401, 104, 'Arboleda Campestre', 'Ibagué, Tolima', 1, 4.443405, -75.150826),
(10402, 104, 'Colina I', 'Ibagué, Tolima', 2, 4.384846, -75.236721),
(10501, 105, 'Protecho', 'Ibagué, Tolima', 1, 4.472233, -75.217733),
(10502, 105, 'Topacio', 'Ibagué, Tolima', 2, 4.470432, -75.221350),
(10503, 105, 'Éxito', 'Ibagué, Tolima', 3, 4.468630, -75.229035),
(10504, 105, 'Multicentro', 'Ibagué, Tolima', 4, 4.467278, -75.231748),
(10505, 105, 'Hospital Federico Lleras', 'Ibagué, Tolima', 5, 4.458720, -75.233104),
(10506, 105, 'Plaza 21', 'Ibagué, Tolima', 6, 4.447008, -75.228131),
(10507, 105, 'Museo Panóptico', 'Ibagué, Tolima', 7, 4.440251, -75.234460),
(10508, 105, 'San Simón', 'Ibagué, Tolima', 8, 4.444305, -75.238529),
(10509, 105, 'La Pola', 'Ibagué, Tolima', 9, 4.439801, -75.244858),
(10601, 106, 'Nueva Castilla', 'Ibagué, Tolima', 1, 4.474936, -75.210500),
(10602, 106, 'Parque Murillo Toro', 'Ibagué, Tolima', 2, 4.441152, -75.232200),
(10603, 106, 'Terminal de Transporte', 'Ibagué, Tolima', 3, 4.425386, -75.223158),
(10604, 106, 'Cra 12 x Cll 3', 'Ibagué, Tolima', 4, 4.430792, -75.241242),
(10701, 107, 'La Ceibita', 'Ibagué, Tolima', 1, 4.393855, -75.250283),
(10702, 107, 'Casablanca', 'Ibagué, Tolima', 2, 4.399260, -75.246667),
(10703, 107, 'Comfatolima', 'Ibagué, Tolima', 3, 4.410071, -75.232200),
(10704, 107, 'UCC', 'Ibagué, Tolima', 4, 4.414576, -75.230392),
(10705, 107, 'Multicentro', 'Ibagué, Tolima', 5, 4.467278, -75.231748),
(10706, 107, 'Comando de Policía', 'Ibagué, Tolima', 6, 4.447909, -75.227227),
(10707, 107, 'Calle 15', 'Ibagué, Tolima', 7, 4.442954, -75.229488),
(10801, 108, 'Carrera 7a Sur', 'Ibagué, Tolima', 1, 4.426287, -75.230392),
(10802, 108, 'Terminal de Transporte', 'Ibagué, Tolima', 2, 4.425386, -75.223158),
(10803, 108, 'Liceo Nacional', 'Ibagué, Tolima', 3, 4.422684, -75.225871),
(10804, 108, 'Simón Bolívar', 'Ibagué, Tolima', 4, 4.462323, -75.217733),
(10805, 108, 'Nueva Castilla', 'Ibagué, Tolima', 5, 4.474936, -75.210500),
(10901, 109, 'Santa Cruz', 'Ibagué, Tolima', 1, 4.438450, -75.243954),
(10902, 109, 'Parque Murillo Toro', 'Ibagué, Tolima', 2, 4.441152, -75.232200),
(10903, 109, 'Mercacentro 4', 'Ibagué, Tolima', 3, 4.430792, -75.233104),
(10904, 109, 'C.C. La Estación', 'Ibagué, Tolima', 4, 4.485747, -75.236721),
(10905, 109, 'Éxito', 'Ibagué, Tolima', 5, 4.468630, -75.229035),
(10906, 109, 'Topacio', 'Ibagué, Tolima', 6, 4.470432, -75.221350),
(10907, 109, 'Protecho', 'Ibagué, Tolima', 7, 4.472233, -75.217733),
(11001, 110, 'Casablanca', 'Ibagué, Tolima', 1, 4.399260, -75.246667),
(11002, 110, 'Santa Ana', 'Ibagué, Tolima', 2, 4.412774, -75.239433),
(11003, 110, 'El Salado', 'Ibagué, Tolima', 3, 4.411873, -75.234008),
(11004, 110, 'Comfatolima', 'Ibagué, Tolima', 4, 4.410071, -75.232200),
(11005, 110, 'Multicentro', 'Ibagué, Tolima', 5, 4.467278, -75.231748),
(11006, 110, 'Comando de Policía', 'Ibagué, Tolima', 6, 4.447909, -75.227227),
(11007, 110, 'Colina I', 'Ibagué, Tolima', 7, 4.384846, -75.236721),
(11101, 111, 'Clinaltec', 'Ibagué, Tolima', 1, 4.428089, -75.172525),
(11102, 111, 'Mercacentro 10', 'Ibagué, Tolima', 2, 4.428990, -75.181567),
(11103, 111, 'Sena Industrial', 'Ibagué, Tolima', 3, 4.430792, -75.186992),
(11104, 111, 'Parque Deportivo', 'Ibagué, Tolima', 4, 4.436197, -75.201459),
(11105, 111, 'Hospital Federico Lleras', 'Ibagué, Tolima', 5, 4.458720, -75.233104),
(11106, 111, 'Plaza 21', 'Ibagué, Tolima', 6, 4.447008, -75.228131),
(11107, 111, 'Museo Panóptico', 'Ibagué, Tolima', 7, 4.440251, -75.234460),
(11108, 111, 'La Pola', 'Ibagué, Tolima', 8, 4.439801, -75.244858),
(11201, 112, 'Galán', 'Ibagué, Tolima', 1, 4.417278, -75.223158),
(11202, 112, 'Terminal de Transporte', 'Ibagué, Tolima', 2, 4.425386, -75.223158),
(11203, 112, 'Plaza 21', 'Ibagué, Tolima', 3, 4.447008, -75.228131),
(11204, 112, 'Monumento a La Paz', 'Ibagué, Tolima', 4, 4.435296, -75.194225),
(11205, 112, 'Glorieta Picaleña', 'Ibagué, Tolima', 5, 4.425386, -75.159867),
(11206, 112, 'Aeropuerto Perales', 'Ibagué, Tolima', 6, 4.421783, -75.133647),
(11207, 112, 'Arboleda Campestre', 'Ibagué, Tolima', 7, 4.443405, -75.150826),
(11301, 113, 'Cerro Gordo', 'Ibagué, Tolima', 1, 4.428089, -75.259325),
(11302, 113, 'Ricaurte', 'Ibagué, Tolima', 2, 4.428089, -75.234912),
(11303, 113, 'Terminal de Transporte', 'Ibagué, Tolima', 3, 4.425386, -75.223158),
(11304, 113, 'Plaza 21', 'Ibagué, Tolima', 4, 4.447008, -75.228131),
(11305, 113, 'C.C. La Quinta', 'Ibagué, Tolima', 5, 4.476738, -75.234008),
(11306, 113, 'C.C. La Estación', 'Ibagué, Tolima', 6, 4.485747, -75.236721),
(11307, 113, 'Delicias', 'Ibagué, Tolima', 7, 4.494756, -75.243954),
(11401, 114, 'Casablanca', 'Ibagué, Tolima', 1, 4.399260, -75.246667),
(11402, 114, 'Modelia', 'Ibagué, Tolima', 2, 4.404666, -75.241242),
(11403, 114, 'La Floresta', 'Ibagué, Tolima', 3, 4.401062, -75.237625),
(11404, 114, 'Éxito', 'Ibagué, Tolima', 4, 4.468630, -75.229035),
(11405, 114, 'Multicentro', 'Ibagué, Tolima', 5, 4.467278, -75.231748),
(11406, 114, 'Hospital Federico Lleras', 'Ibagué, Tolima', 6, 4.458720, -75.233104),
(11407, 114, 'Plaza 21', 'Ibagué, Tolima', 7, 4.447008, -75.228131),
(11408, 114, 'Museo Panóptico', 'Ibagué, Tolima', 8, 4.440251, -75.234460),
(11409, 114, 'Calle 10', 'Ibagué, Tolima', 9, 4.437999, -75.230844),
(11501, 115, 'Casablanca', 'Ibagué, Tolima', 1, 4.399260, -75.246667),
(11502, 115, 'El Porvenir', 'Ibagué, Tolima', 2, 4.409170, -75.244858),
(11503, 115, 'El Salado', 'Ibagué, Tolima', 3, 4.411873, -75.234008),
(11504, 115, 'Santa Ana', 'Ibagué, Tolima', 4, 4.412774, -75.239433),
(11505, 115, 'C.C. La Estación', 'Ibagué, Tolima', 5, 4.485747, -75.236721),
(11506, 115, 'Hospital San Francisco', 'Ibagué, Tolima', 6, 4.439801, -75.240337),
(11507, 115, 'La Pola', 'Ibagué, Tolima', 7, 4.439801, -75.244858),
(11601, 116, 'Protecho', 'Ibagué, Tolima', 1, 4.472233, -75.217733),
(11602, 116, 'Simón Bolívar', 'Ibagué, Tolima', 2, 4.462323, -75.217733),
(11603, 116, 'Multicentro', 'Ibagué, Tolima', 3, 4.467278, -75.231748),
(11604, 116, 'Hospital Federico Lleras', 'Ibagué, Tolima', 4, 4.458720, -75.233104),
(11605, 116, 'Plaza 21', 'Ibagué, Tolima', 5, 4.447008, -75.228131),
(11606, 116, 'Calle 13', 'Ibagué, Tolima', 6, 4.441603, -75.231296),
(11607, 116, 'Parque de la Música', 'Ibagué, Tolima', 7, 4.436197, -75.229035),
(11608, 116, 'La Florida', 'Ibagué, Tolima', 8, 4.424486, -75.234912),
(11701, 117, 'Nueva Castilla', 'Ibagué, Tolima', 1, 4.474936, -75.210500),
(11702, 117, 'Protecho', 'Ibagué, Tolima', 2, 4.472233, -75.217733),
(11703, 117, 'Topacio', 'Ibagué, Tolima', 3, 4.470432, -75.221350),
(11704, 117, 'Jardín Santander', 'Ibagué, Tolima', 4, 4.465927, -75.224967),
(11705, 117, 'C.C. Acqua', 'Ibagué, Tolima', 5, 4.471332, -75.226775),
(11706, 117, 'Hospital San Francisco', 'Ibagué, Tolima', 6, 4.439801, -75.240337),
(11707, 117, 'Calle 12', 'Ibagué, Tolima', 7, 4.440702, -75.233556),
(11801, 118, 'Yuldaima', 'Ibagué, Tolima', 1, 4.400161, -75.219542),
(11802, 118, 'Terminal de Transporte', 'Ibagué, Tolima', 2, 4.425386, -75.223158),
(11803, 118, 'Liceo Nacional', 'Ibagué, Tolima', 3, 4.422684, -75.225871),
(11804, 118, 'C.C. La 5', 'Ibagué, Tolima', 4, 4.453314, -75.231296),
(11805, 118, 'Piscinas Olímpicas', 'Ibagué, Tolima', 5, 4.429891, -75.205075),
(11806, 118, 'Mercacentro 10', 'Ibagué, Tolima', 6, 4.428990, -75.181567),
(11807, 118, 'Club Campestre', 'Ibagué, Tolima', 7, 4.427188, -75.176142),
(11808, 118, 'Playa Hawái', 'Ibagué, Tolima', 8, 4.420882, -75.170717),
(11809, 118, 'Peaje Gualanday', 'Ibagué, Tolima', 9, 4.407368, -75.105618),
(11901, 119, 'Carmen de Bulira', 'Ibagué, Tolima', 1, 4.456918, -75.268366),
(11902, 119, 'El Totumo', 'Ibagué, Tolima', 2, 4.458720, -75.263846),
(11903, 119, 'Serviarroz', 'Ibagué, Tolima', 3, 4.460522, -75.257516),
(11904, 119, 'Comando de Policía', 'Ibagué, Tolima', 4, 4.447909, -75.227227),
(11905, 119, 'Calambeo', 'Ibagué, Tolima', 5, 4.465927, -75.277408),
(12001, 120, 'La Ceibita', 'Ibagué, Tolima', 1, 4.393855, -75.250283),
(12002, 120, 'Modelia', 'Ibagué, Tolima', 2, 4.404666, -75.241242),
(12003, 120, 'UCC', 'Ibagué, Tolima', 3, 4.414576, -75.230392),
(12004, 120, 'C.C. La Estación', 'Ibagué, Tolima', 4, 4.485747, -75.236721),
(12005, 120, 'Hospital San Francisco', 'Ibagué, Tolima', 5, 4.439801, -75.240337),
(12006, 120, 'Santa Teresa', 'Ibagué, Tolima', 6, 4.433495, -75.235817),
(12007, 120, 'Museo Panóptico', 'Ibagué, Tolima', 7, 4.440251, -75.234460),
(12008, 120, 'Calle 10', 'Ibagué, Tolima', 8, 4.437999, -75.230844),
(12101, 121, 'Nueva Castilla', 'Ibagué, Tolima', 1, 4.474936, -75.210500),
(12102, 121, 'Protecho', 'Ibagué, Tolima', 2, 4.472233, -75.217733),
(12103, 121, 'Simón Bolívar', 'Ibagué, Tolima', 3, 4.462323, -75.217733),
(12104, 121, 'Hospital Federico Lleras', 'Ibagué, Tolima', 4, 4.458720, -75.233104),
(12105, 121, 'Estadio', 'Ibagué, Tolima', 5, 4.462323, -75.233104),
(12106, 121, 'Comando de Policía', 'Ibagué, Tolima', 6, 4.447909, -75.227227),
(12107, 121, 'Museo Panóptico', 'Ibagué, Tolima', 7, 4.440251, -75.234460),
(12108, 121, 'Alaska', 'Ibagué, Tolima', 8, 4.447909, -75.241242),
(12201, 122, 'Clinaltec', 'Ibagué, Tolima', 1, 4.428089, -75.172525),
(12202, 122, 'Mercacentro 10', 'Ibagué, Tolima', 2, 4.428990, -75.181567),
(12203, 122, 'Estadio', 'Ibagué, Tolima', 3, 4.462323, -75.233104),
(12204, 122, 'Terminal de Transporte', 'Ibagué, Tolima', 4, 4.425386, -75.223158),
(12205, 122, 'Parque de la Música', 'Ibagué, Tolima', 5, 4.436197, -75.229035),
(12206, 122, 'Libertador', 'Ibagué, Tolima', 6, 4.431693, -75.229940),
(12301, 123, 'Nueva Castilla', 'Ibagué, Tolima', 1, 4.474936, -75.210500),
(12302, 123, 'Protecho', 'Ibagué, Tolima', 2, 4.472233, -75.217733),
(12303, 123, 'Multicentro', 'Ibagué, Tolima', 3, 4.467278, -75.231748),
(12304, 123, 'Hospital Federico Lleras', 'Ibagué, Tolima', 4, 4.458720, -75.233104),
(12305, 123, 'Plaza 21', 'Ibagué, Tolima', 5, 4.447008, -75.228131),
(12306, 123, 'Terminal de Transporte', 'Ibagué, Tolima', 6, 4.425386, -75.223158),
(12307, 123, 'Yuldaima', 'Ibagué, Tolima', 7, 4.400161, -75.219542),
(12308, 123, 'Galarza', 'Ibagué, Tolima', 8, 4.403765, -75.228583),
(12309, 123, 'La Pradera', 'Ibagué, Tolima', 9, 4.397459, -75.236721),
(12401, 124, 'Protecho', 'Ibagué, Tolima', 1, 4.472233, -75.217733),
(12402, 124, 'Topacio', 'Ibagué, Tolima', 2, 4.470432, -75.221350),
(12403, 124, 'Éxito', 'Ibagué, Tolima', 3, 4.468630, -75.229035),
(12404, 124, 'Multicentro', 'Ibagué, Tolima', 4, 4.467278, -75.231748),
(12405, 124, 'San Simón', 'Ibagué, Tolima', 5, 4.444305, -75.238529),
(12406, 124, 'Plaza 21', 'Ibagué, Tolima', 6, 4.447008, -75.228131),
(12407, 124, 'Universidad Antonio Nariño', 'Ibagué, Tolima', 7, 4.450612, -75.230392),
(12501, 125, 'La Gaviota', 'Ibagué, Tolima', 1, 4.407368, -75.196034),
(12502, 125, 'Boquerón', 'Ibagué, Tolima', 2, 4.411873, -75.191513),
(12503, 125, 'El Tejar', 'Ibagué, Tolima', 3, 4.419080, -75.202363),
(12504, 125, 'Mercacentro 8', 'Ibagué, Tolima', 4, 4.424486, -75.206884),
(12505, 125, 'Terminal de Transporte', 'Ibagué, Tolima', 5, 4.425386, -75.223158),
(12506, 125, 'Plaza 21', 'Ibagué, Tolima', 6, 4.447008, -75.228131),
(12507, 125, 'Multicentro', 'Ibagué, Tolima', 7, 4.467278, -75.231748),
(12601, 126, 'Clinaltec', 'Ibagué, Tolima', 1, 4.428089, -75.172525),
(12602, 126, 'Mercacentro 10', 'Ibagué, Tolima', 2, 4.428990, -75.181567),
(12603, 126, 'Makro', 'Ibagué, Tolima', 3, 4.467729, -75.214117),
(12604, 126, 'C.C. Acqua', 'Ibagué, Tolima', 4, 4.471332, -75.226775),
(12605, 126, 'Hospital San Francisco', 'Ibagué, Tolima', 5, 4.439801, -75.240337),
(12606, 126, 'Museo Panóptico', 'Ibagué, Tolima', 6, 4.440251, -75.234460),
(12607, 126, 'Calle 10', 'Ibagué, Tolima', 7, 4.437999, -75.230844),
(12701, 127, 'Tierra Firme', 'Ibagué, Tolima', 1, 4.393855, -75.234912),
(12702, 127, 'La Floresta', 'Ibagué, Tolima', 2, 4.401062, -75.237625),
(12703, 127, 'Éxito', 'Ibagué, Tolima', 3, 4.468630, -75.229035),
(12704, 127, 'Multicentro', 'Ibagué, Tolima', 4, 4.467278, -75.231748),
(12705, 127, 'Terminal de Transporte', 'Ibagué, Tolima', 5, 4.425386, -75.223158),
(12706, 127, 'Parque de la Música', 'Ibagué, Tolima', 6, 4.436197, -75.229035),
(12707, 127, 'Vereda Tres Esquinas', 'Ibagué, Tolima', 7, 4.427188, -75.300012),
(12708, 127, 'Juntas', 'Ibagué, Tolima', 8, 4.425386, -75.313574),
(12801, 128, 'Clarita Botero', 'Ibagué, Tolima', 1, 4.436197, -75.252092),
(12802, 128, 'Universidad Antonio Nariño', 'Ibagué, Tolima', 2, 4.450612, -75.230392),
(12803, 128, 'Plaza 21', 'Ibagué, Tolima', 3, 4.447008, -75.228131),
(12804, 128, 'Hospital Federico Lleras', 'Ibagué, Tolima', 4, 4.458720, -75.233104),
(12805, 128, 'Multicentro', 'Ibagué, Tolima', 5, 4.467278, -75.231748),
(12806, 128, 'Éxito', 'Ibagué, Tolima', 6, 4.468630, -75.229035),
(12807, 128, 'La Floresta', 'Ibagué, Tolima', 7, 4.401062, -75.237625),
(12808, 128, 'El Porvenir', 'Ibagué, Tolima', 8, 4.409170, -75.244858),
(12809, 128, 'Casablanca', 'Ibagué, Tolima', 9, 4.399260, -75.246667),
(12901, 129, 'El Mirador', 'Ibagué, Tolima', 1, 4.489350, -75.234912),
(12902, 129, 'Altos de Ambalá', 'Ibagué, Tolima', 2, 4.492954, -75.241242),
(12903, 129, 'C.C. La Estación', 'Ibagué, Tolima', 3, 4.485747, -75.236721),
(12904, 129, 'Ibagué 2000', 'Ibagué, Tolima', 4, 4.480341, -75.235817),
(12905, 129, 'Parque Murillo Toro', 'Ibagué, Tolima', 5, 4.441152, -75.232200),
(12906, 129, 'Tanques Ibal La Pola', 'Ibagué, Tolima', 6, 4.442053, -75.246667),
(12907, 129, 'La Coqueta', 'Ibagué, Tolima', 7, 4.445206, -75.249379),
(13001, 130, 'PICAL', 'Ibagué, Tolima', 1, 4.429891, -75.170717),
(13101, 131, 'La Ceibita', 'Ibagué, Tolima', 1, 4.393855, -75.250283),
(13102, 131, 'Casablanca', 'Ibagué, Tolima', 2, 4.399260, -75.246667),
(13103, 131, 'Comfatolima', 'Ibagué, Tolima', 3, 4.410071, -75.232200),
(13104, 131, 'El Salado', 'Ibagué, Tolima', 4, 4.411873, -75.234008),
(13105, 131, 'CAPA', 'Ibagué, Tolima', 5, 4.390251, -75.248475),
(13106, 131, 'Molino Santa Clara', 'Ibagué, Tolima', 6, 4.387549, -75.253900),
(13107, 131, 'Chucuní', 'Ibagué, Tolima', 7, 4.383044, -75.263846),
(13201, 132, 'La Ceibita', 'Ibagué, Tolima', 1, 4.393855, -75.250283),
(13202, 132, 'Modelia', 'Ibagué, Tolima', 2, 4.404666, -75.241242),
(13203, 132, 'El Porvenir', 'Ibagué, Tolima', 3, 4.409170, -75.244858),
(13204, 132, 'Simón Bolívar', 'Ibagué, Tolima', 4, 4.462323, -75.217733),
(13205, 132, 'Sena Industrial', 'Ibagué, Tolima', 5, 4.430792, -75.186992),
(13206, 132, 'Mercacentro 10', 'Ibagué, Tolima', 6, 4.428990, -75.181567),
(13207, 132, 'Club Campestre', 'Ibagué, Tolima', 7, 4.427188, -75.176142),
(13208, 132, 'Clinaltec', 'Ibagué, Tolima', 8, 4.428089, -75.172525),
(13301, 133, 'Cerrito', 'Ibagué, Tolima', 1, 4.411873, -75.259325),
(13401, 134, 'Clinaltec', 'Ibagué, Tolima', 1, 4.428089, -75.172525),
(13402, 134, 'Buenos Aires', 'Ibagué, Tolima', 2, 4.422684, -75.170717),
(13403, 134, 'I.E. Nueva Esperanza', 'Ibagué, Tolima', 3, 4.419080, -75.169813),
(13404, 134, 'Parque Caiké', 'Ibagué, Tolima', 4, 4.414576, -75.168909),
(13501, 135, 'Santa Teresa', 'Ibagué, Tolima', 1, 4.433495, -75.235817),
(13502, 135, 'Libertador', 'Ibagué, Tolima', 2, 4.431693, -75.229940),
(13503, 135, 'Cra 6 x Cll 19', 'Ibagué, Tolima', 3, 4.446107, -75.226323),
(13504, 135, 'Multicentro', 'Ibagué, Tolima', 4, 4.467278, -75.231748),
(13505, 135, 'Calle 13', 'Ibagué, Tolima', 5, 4.441603, -75.231296);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `plan_suscripcion`
--

CREATE TABLE `plan_suscripcion` (
  `id_plan` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `precio` decimal(12,2) NOT NULL,
  `duracion_dias` int(11) NOT NULL DEFAULT 30,
  `limite_vehiculos` int(11) DEFAULT NULL,
  `caracteristicas` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`caracteristicas`)),
  `id_estados` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `plan_suscripcion`
--

INSERT INTO `plan_suscripcion` (`id_plan`, `nombre`, `descripcion`, `precio`, `duracion_dias`, `limite_vehiculos`, `caracteristicas`, `id_estados`) VALUES
(1, 'Gratuito', 'Para comenzar sin costo', 0.00, 365, 10, '[\"rutas_viajes\",\"consulta_mantenimientos\",\"reportes_basicos_limitados\"]', 1),
(2, 'Esencial', 'Flotas pequeñas', 1490000.00, 365, 30, '[\"rutas_viajes\",\"mantenimientos_tecnicos\",\"asignacion_conductores\",\"reportes_incidentes\",\"reportes_avanzados_limitados\"]', 1),
(3, 'Avanzado', 'Empresas en expansión', 3290000.00, 365, 80, '[\"rutas_viajes\",\"mantenimientos_tecnicos\",\"asignacion_conductores\",\"reportes_incidentes\",\"reportes_avanzados\",\"pagos_digitales\",\"programa_puntos\"]', 1),
(4, 'Corporativo', 'Grandes flotas', 6990000.00, 365, NULL, '[\"rutas_viajes\",\"mantenimientos_tecnicos\",\"asignacion_conductores\",\"reportes_incidentes\",\"reportes_avanzados\",\"pagos_digitales\",\"programa_puntos\",\"reciclaje\",\"canje_puntos\"]', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reciclaje`
--

CREATE TABLE `reciclaje` (
  `id_reciclaje` int(11) NOT NULL,
  `documento` bigint(20) NOT NULL,
  `id_punto` int(11) NOT NULL,
  `id_tipo_material` int(11) NOT NULL,
  `cantidad` decimal(10,2) DEFAULT NULL,
  `puntos_generados` int(11) DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `redencion_puntos`
--

CREATE TABLE `redencion_puntos` (
  `id_canje` int(11) NOT NULL,
  `documento` bigint(20) NOT NULL,
  `puntos_usados` int(11) NOT NULL,
  `tipo_beneficio` enum('Tiquete Gratis','Descuento') DEFAULT NULL,
  `valor_descuento` decimal(10,2) DEFAULT NULL,
  `id_pasaje` int(11) DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rol`
--

CREATE TABLE `rol` (
  `id_rol` int(11) NOT NULL,
  `nombre_rol` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `rol`
--

INSERT INTO `rol` (`id_rol`, `nombre_rol`, `descripcion`) VALUES
(1, 'Administrador', 'Control total del sistema'),
(2, 'Conductor', 'Gestiona viajes y vehículos asignados'),
(3, 'Tecnico', 'Gestiona mantenimientos'),
(4, 'Propietario', 'Propietario de vehículos'),
(5, 'Usuario', 'Pasajero registrado desde la app pública'),
(6, 'Super Administrador', 'Administra los planes, las suscripciones y la validación de empresas de la plataforma');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ruta`
--

CREATE TABLE `ruta` (
  `id_ruta` int(11) NOT NULL,
  `id_empresa` int(10) UNSIGNED DEFAULT NULL,
  `nombre_ruta` varchar(100) NOT NULL,
  `codigo` varchar(10) DEFAULT NULL,
  `color` varchar(7) DEFAULT NULL,
  `origen` varchar(150) DEFAULT NULL,
  `destino` varchar(150) DEFAULT NULL,
  `descripcion` varchar(500) DEFAULT NULL,
  `distancia` decimal(10,2) DEFAULT NULL,
  `tiempo_estimado` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `ruta`
--

INSERT INTO `ruta` (`id_ruta`, `id_empresa`, `nombre_ruta`, `codigo`, `color`, `origen`, `destino`, `descripcion`, `distancia`, `tiempo_estimado`) VALUES
(101, NULL, 'Ruta 1', '1', '#2563eb', 'Barrio Delicias', 'Belén (Cra 7 x Cll 5)', 'Pasa por: Av. Ambalá x Cll 63, C.C. La Estación, Hospital Federico Lleras, Estadio, Av. Ferrocarril, Comando de Policía, Museo Panóptico', NULL, NULL),
(102, NULL, 'Ruta 2', '2', '#f59e0b', 'Cañaveral (parte alta)', 'Clarita Botero', 'Pasa por: Cra 5 x Cll 16, Plaza 21, SENA, C.C. La 5, Av. Ambalá x Cll 77', NULL, NULL),
(103, NULL, 'Ruta 4', '4', '#e11d48', 'Boquerón', 'Arboleda Campestre', 'Pasa por: El Tejar, Mercacentro 8, Monumento a La Paz, Piscinas Olímpicas, Aeropuerto Perales, Glorieta Picaleña', NULL, NULL),
(104, NULL, 'Ruta 6', '6', '#7c3aed', 'Arboleda Campestre', 'Colinas del Sur (Colina I)', 'Pasa por: Ajuste reciente en el sector Colinas del Sur, segunda etapa', NULL, NULL),
(105, NULL, 'Ruta 8', '8', '#0891b2', 'Protecho B', 'La Pola (CAM)', 'Pasa por: Topacio, Éxito, Multicentro, Hospital Federico Lleras, San Simón, Plaza 21, Museo Panóptico', NULL, NULL),
(106, NULL, 'Ruta 9', '9', '#ea580c', 'Nueva Castilla', 'Villa Adriana (Cra 12 x Cll 3)', 'Pasa por: Alaska, Parque Murillo Toro, Terminal de Transporte, Fiscalía, Clínica Nuestra', NULL, NULL),
(107, NULL, 'Ruta 11', '11', '#16a34a', 'La Ceibita', 'Calle 15', 'Pasa por: UCC, Comfatolima, Multicentro, Av. Ferrocarril x Cll 42, Comando de Policía', NULL, NULL),
(108, NULL, 'Ruta 14', '14', '#db2777', 'Carrera 7a Sur', 'Nueva Castilla', 'Pasa por: Galán, Terminal, Liceo Nacional, C.C. La 5, Arkacentro, Simón Bolívar I y III', NULL, NULL),
(109, NULL, 'Ruta 15', '15', '#4f46e5', 'Santa Cruz', 'Protecho B', 'Pasa por: Museo Panóptico, Parque Murillo Toro, Santa Teresa, Mercacentro 4, C.C. La Estación, Éxito, Topacio', NULL, NULL),
(110, NULL, 'Ruta 17', '17', '#13c4a3', 'Casablanca', 'Colina I', 'Pasa por: Comfatolima, El Salado, Santa Ana, Éxito, Multicentro, Hospital Federico Lleras, Comando de Policía, Mercacentro 8', NULL, NULL),
(111, NULL, 'Ruta 18', '18', '#2563eb', 'Clinaltec (Picaleña)', 'CAM de La Pola', 'Pasa por: Sena Industrial, Mercacentro 10, Cocacola, Parque Deportivo, Hospital Federico Lleras, Plaza 21, Museo Panóptico', NULL, NULL),
(112, NULL, 'Ruta 19', '19', '#f59e0b', 'Galán', 'Arboleda Campestre', 'Pasa por: Terminal de Transporte, Plaza 21, Monumento a La Paz, Hospital Federico Lleras, Multicentro, Éxito 80, Aeropuerto Perales, Picaleña', NULL, NULL),
(113, NULL, 'Ruta 20', '20', '#e11d48', 'Cerro Gordo (Venecia)', 'Barrio Delicias', 'Pasa por: Ricaurte, Galán, Terminal, Plaza 21, C.C. La Quinta, C.C. La Estación, Universidad de Ibagué', NULL, NULL),
(114, NULL, 'Ruta 21', '21', '#7c3aed', 'Casablanca', 'Calle 10', 'Pasa por: Modelia, La Floresta, Éxito, Multicentro, Hospital Federico Lleras, San Simón, Plaza 21, Museo Panóptico', NULL, NULL),
(115, NULL, 'Ruta 22', '22', '#0891b2', 'Casablanca', 'CAM de La Pola', 'Pasa por: El Porvenir, El Salado, Santa Ana, Av. Ambalá, C.C. La Estación, Hospital San Francisco', NULL, NULL),
(116, NULL, 'Ruta 23', '23', '#ea580c', 'Protecho B', 'La Florida', 'Pasa por: Simón Bolívar I y III, Multicentro, Hospital Federico Lleras, Plaza 21, Terminal Calle 13, Parque de la Música', NULL, NULL),
(117, NULL, 'Ruta 24', '24', '#16a34a', 'Nueva Castilla', 'Calle 12', 'Pasa por: Protecho B, Topacio, Jardín Santander, C.C. Acqua, Hospital San Francisco', NULL, NULL),
(118, NULL, 'Ruta 28', '28', '#db2777', 'Yuldaima', 'Peaje Gualanday', 'Pasa por: Terminal, Liceo Nacional, C.C. La 5, Piscinas Olímpicas, SENA, Fiscalía, Mercacentro 10, Club Campestre, Playa Hawái', NULL, NULL),
(119, NULL, 'Ruta 29', '29', '#4f46e5', 'Carmen de Bulira', 'Calambeo', 'Pasa por: Vía Rovira, El Totumo, Serviarroz, SENA, Av. Ferrocarril, Comando de Policía', NULL, NULL),
(120, NULL, 'Ruta 31', '31', '#13c4a3', 'La Ceibita', 'Calle 10', 'Pasa por: Modelia, UCC, C.C. La Estación, San Francisco, Santa Teresa, Museo Panóptico', NULL, NULL),
(121, NULL, 'Ruta 33', '33', '#2563eb', 'Nueva Castilla', 'Alaska', 'Pasa por: Protecho, Simón Bolívar, Hospital Federico Lleras, Estadio, Comando de Policía, Museo Panóptico', NULL, NULL),
(122, NULL, 'Ruta 35', '35', '#f59e0b', 'Clinaltec (Picaleña)', 'Libertador', 'Pasa por: Mercacentro 10, Iglesia San Judas, Estadio, Terminal, Parque de la Música', NULL, NULL),
(123, NULL, 'Ruta 37', '37', '#e11d48', 'Nueva Castilla', 'La Pradera', 'Pasa por: Protecho, Jordán 3.ª etapa, Multicentro, Hospital Federico Lleras, Plaza 21, Terminal, Yuldaima, Galarza', NULL, NULL),
(124, NULL, 'Ruta 39', '39', '#7c3aed', 'Protecho B', 'Universidad Antonio Nariño (Ancón)', 'Pasa por: Topacio, Éxito, Multicentro, San Simón, Plaza 21, 20 de Julio', NULL, NULL),
(125, NULL, 'Ruta 40', '40', '#0891b2', 'La Gaviota', 'Boquerón', 'Pasa por: Av. Ambalá, Multicentro, Plaza 21, Terminal, Mercacentro 8, El Tejar', NULL, NULL),
(126, NULL, 'Ruta 43', '43', '#ea580c', 'Clinaltec (Picaleña)', 'Calle 10', 'Pasa por: Mercacentro 10, Makro, C.C. Acqua, Hospital San Francisco, Museo Panóptico', NULL, NULL),
(127, NULL, 'Ruta 48', '48', '#16a34a', 'Tierra Firme', 'Juntas (Cañón del Combeima)', 'Pasa por: La Floresta, Éxito, Multicentro, Terminal, Parque de la Música, Vereda Tres Esquinas, Villa Restrepo', NULL, NULL),
(128, NULL, 'Ruta 50', '50', '#db2777', 'Clarita Botero', 'Casablanca', 'Pasa por: Universidad Antonio Nariño, Plaza 21, Hospital Federico Lleras, Multicentro, Éxito, La Floresta, El Porvenir, Modelia', NULL, NULL),
(129, NULL, 'Ruta 53', '53', '#4f46e5', 'El Mirador', 'La Coqueta', 'Pasa por: Tanques Ibal La Pola, Parque Murillo Toro, C.C. La Estación, Ibagué 2000, Altos de Ambalá', NULL, NULL),
(130, NULL, 'Ruta 62', '62', '#13c4a3', 'PICAL', 'PICAL', 'Pasa por: Ruta alimentadora: la fuente no detalla puntos', NULL, NULL),
(131, NULL, 'Ruta 82', '82', '#2563eb', 'La Ceibita', 'Chucuní (Los Túneles)', 'Pasa por: Casablanca, Comfatolima, El Salado, CAPA, Molino Santa Clara', NULL, NULL),
(132, NULL, 'Ruta 90', '90', '#f59e0b', 'La Ceibita', 'Clinaltec (Picaleña)', 'Pasa por: Modelia, El Porvenir, Simón Bolívar, Biblioteca Virtual, Mercacentro 10, Club Campestre, Sena Industrial', NULL, NULL),
(133, NULL, 'Ruta 99', '99', '#e11d48', 'Cerrito', 'Cerrito', 'Pasa por: Ruta rural: la fuente no detalla puntos', NULL, NULL),
(134, NULL, 'Ruta Caiké', 'CAIKÉ', '#7c3aed', 'Clinaltec (Picaleña)', 'Parque Caiké', 'Pasa por: Clinaltec, Buenos Aires, I.E. Nueva Esperanza, Parque Caiké', NULL, NULL),
(135, NULL, 'Ruta CH-ST', 'CH-ST', '#0891b2', 'Santa Teresa', 'Calle 13', 'Pasa por: Libertador, Cra 6 x Cll 19, Multicentro, Éxito, Vía Alvarado', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `servicio_mantenimiento`
--

CREATE TABLE `servicio_mantenimiento` (
  `id_mantenimiento` int(11) NOT NULL,
  `id_vehiculo` int(11) NOT NULL,
  `id_tecnico` int(11) NOT NULL,
  `tipo_mantenimiento` varchar(100) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha` date DEFAULT NULL,
  `costo` decimal(12,2) DEFAULT NULL,
  `id_estados` int(11) NOT NULL DEFAULT 10
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `super_admin`
--

CREATE TABLE `super_admin` (
  `documento` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `apellido` varchar(100) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `id_rol` int(11) NOT NULL DEFAULT 6,
  `id_estados` int(11) NOT NULL DEFAULT 1,
  `pin` varchar(8) DEFAULT NULL,
  `pin_expira` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Volcado de datos para la tabla `super_admin`
--

INSERT INTO `super_admin` (`documento`, `name`, `apellido`, `email`, `telefono`, `email_verified_at`, `password`, `id_rol`, `id_estados`, `pin`, `pin_expira`, `remember_token`, `created_at`, `updated_at`) VALUES
(1096800988, 'Miguel Ángel', 'Gómez Nieto', 'gmigue046@gmail.com', NULL, NULL, '$2y$10$KH1URIdQegX9FYkfzOSJ8OwF5em1t/BIAZqLAGjQlysQJHyB993bu', 6, 1, NULL, NULL, NULL, '2026-10-23 14:30:00', '2026-10-23 14:30:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `suscripcion`
--

CREATE TABLE `suscripcion` (
  `id_suscripcion` int(10) UNSIGNED NOT NULL,
  `id_empresa` int(10) UNSIGNED NOT NULL,
  `id_plan` int(10) UNSIGNED NOT NULL,
  `fecha_compra` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_inicio` datetime DEFAULT NULL,
  `fecha_fin` datetime DEFAULT NULL,
  `valor_pagado` decimal(12,2) NOT NULL DEFAULT 0.00,
  `id_estados` int(11) NOT NULL DEFAULT 10
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tarea`
--

CREATE TABLE `tarea` (
  `id_tarea` int(11) NOT NULL,
  `id_tecnico` int(11) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `id_estados` int(11) NOT NULL DEFAULT 10
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tecnico`
--

CREATE TABLE `tecnico` (
  `id_tecnico` int(11) NOT NULL,
  `id_empresa` int(10) UNSIGNED DEFAULT NULL,
  `documento` bigint(20) NOT NULL,
  `especialidad` varchar(100) DEFAULT NULL,
  `experiencia` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tiquete`
--

CREATE TABLE `tiquete` (
  `id_pasaje` int(11) NOT NULL,
  `documento` bigint(20) NOT NULL,
  `id_viaje` int(11) NOT NULL,
  `asiento` varchar(10) DEFAULT NULL,
  `precio` decimal(10,2) DEFAULT NULL,
  `fecha_compra` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `titular_vehiculo`
--

CREATE TABLE `titular_vehiculo` (
  `id_propietario` int(11) NOT NULL,
  `documento` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `documento` bigint(20) NOT NULL,
  `nit` varchar(20) DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp(),
  `id_rol` int(11) NOT NULL DEFAULT 5,
  `id_empresa` int(10) UNSIGNED DEFAULT NULL,
  `pin` varchar(8) DEFAULT NULL,
  `pin_expira` timestamp NULL DEFAULT NULL,
  `id_estados` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vehiculo`
--

CREATE TABLE `vehiculo` (
  `id_vehiculo` int(11) NOT NULL,
  `id_empresa` int(10) UNSIGNED DEFAULT NULL,
  `placa` varchar(20) NOT NULL,
  `color` varchar(50) DEFAULT NULL,
  `capacidad` int(11) DEFAULT NULL,
  `id_modelo` int(11) NOT NULL,
  `id_propietario` int(11) DEFAULT NULL,
  `id_estados` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `viaje`
--

CREATE TABLE `viaje` (
  `id_viaje` int(11) NOT NULL,
  `id_ruta` int(11) NOT NULL,
  `id_asignacion` int(11) NOT NULL,
  `fecha_salida` datetime DEFAULT NULL,
  `fecha_llegada` datetime DEFAULT NULL,
  `id_estados` int(11) NOT NULL DEFAULT 6
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `viaje_paradero`
--

CREATE TABLE `viaje_paradero` (
  `id_viaje_parada` int(11) NOT NULL,
  `id_viaje` int(11) NOT NULL,
  `id_parada` int(11) NOT NULL,
  `hora_llegada` datetime DEFAULT NULL,
  `hora_salida` datetime DEFAULT NULL,
  `id_estados` int(11) NOT NULL DEFAULT 10
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `asignacion_flota`
--
ALTER TABLE `asignacion_flota`
  ADD PRIMARY KEY (`id_asignacion`),
  ADD KEY `asignacion_flota_ibfk_1` (`id_vehiculo`),
  ADD KEY `asignacion_flota_ibfk_2` (`id_conductor`),
  ADD KEY `fk_asignacion_flota_estados` (`id_estados`);

--
-- Indices de la tabla `calificacion_conductor`
--
ALTER TABLE `calificacion_conductor`
  ADD PRIMARY KEY (`id_evaluacion`),
  ADD KEY `calificacion_conductor_ibfk_1` (`id_conductor`),
  ADD KEY `calificacion_conductor_ibfk_2` (`documento`);

--
-- Indices de la tabla `categoria_material`
--
ALTER TABLE `categoria_material`
  ADD PRIMARY KEY (`id_tipo_material`);

--
-- Indices de la tabla `centro_acopio`
--
ALTER TABLE `centro_acopio`
  ADD PRIMARY KEY (`id_punto`);

--
-- Indices de la tabla `ciudad`
--
ALTER TABLE `ciudad`
  ADD PRIMARY KEY (`id_ciudad`),
  ADD UNIQUE KEY `ciudad_departamento_nombre_unique` (`id_departamento`,`nombre`);

--
-- Indices de la tabla `clase_licencia`
--
ALTER TABLE `clase_licencia`
  ADD PRIMARY KEY (`id_categoria_licencia`),
  ADD UNIQUE KEY `clase_licencia_nombre_unique` (`nombre`);

--
-- Indices de la tabla `clase_mantenimiento`
--
ALTER TABLE `clase_mantenimiento`
  ADD PRIMARY KEY (`id_tipo_mantenimiento`),
  ADD UNIQUE KEY `clase_mantenimiento_nombre_unique` (`nombre`);

--
-- Indices de la tabla `conductor`
--
ALTER TABLE `conductor`
  ADD PRIMARY KEY (`id_conductor`),
  ADD UNIQUE KEY `conductor_id_licencia_unique` (`id_licencia`),
  ADD KEY `conductor_ibfk_1` (`documento`),
  ADD KEY `fk_conductor_estados` (`id_estados`),
  ADD KEY `conductor_id_empresa_foreign` (`id_empresa`);

--
-- Indices de la tabla `cuenta_puntos`
--
ALTER TABLE `cuenta_puntos`
  ADD PRIMARY KEY (`id_saldo`),
  ADD KEY `cuenta_puntos_ibfk_1` (`documento`);

--
-- Indices de la tabla `departamento`
--
ALTER TABLE `departamento`
  ADD PRIMARY KEY (`id_departamento`),
  ADD UNIQUE KEY `departamento_nombre_unique` (`nombre`);

--
-- Indices de la tabla `detalle_servicio`
--
ALTER TABLE `detalle_servicio`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `detalle_servicio_ibfk_1` (`id_mantenimiento`);

--
-- Indices de la tabla `documentacion_vehiculo`
--
ALTER TABLE `documentacion_vehiculo`
  ADD PRIMARY KEY (`id_documento`),
  ADD KEY `documentacion_vehiculo_ibfk_1` (`id_vehiculo`);

--
-- Indices de la tabla `empresa`
--
ALTER TABLE `empresa`
  ADD PRIMARY KEY (`id_empresa`),
  ADD UNIQUE KEY `empresa_nit_unique` (`nit`),
  ADD KEY `empresa_id_estados_foreign` (`id_estados`),
  ADD KEY `empresa_id_ciudad_foreign` (`id_ciudad`);

--
-- Indices de la tabla `estado_registro`
--
ALTER TABLE `estado_registro`
  ADD PRIMARY KEY (`id_estados`),
  ADD UNIQUE KEY `estado_registro_nombre_unique` (`nombre`);

--
-- Indices de la tabla `incidencia`
--
ALTER TABLE `incidencia`
  ADD PRIMARY KEY (`id_incidente`),
  ADD KEY `incidencia_ibfk_1` (`id_viaje`),
  ADD KEY `incidencia_ibfk_2` (`id_conductor`);

--
-- Indices de la tabla `licencia`
--
ALTER TABLE `licencia`
  ADD PRIMARY KEY (`id_licencia`),
  ADD UNIQUE KEY `licencia_numero_unique` (`numero`),
  ADD KEY `fk_licencia_categoria` (`id_categoria_licencia`);

--
-- Indices de la tabla `modelo_vehiculo`
--
ALTER TABLE `modelo_vehiculo`
  ADD PRIMARY KEY (`id_modelo`);

--
-- Indices de la tabla `pago`
--
ALTER TABLE `pago`
  ADD PRIMARY KEY (`id_pago`),
  ADD UNIQUE KEY `pago_referencia_unique` (`referencia`),
  ADD KEY `pago_id_suscripcion_foreign` (`id_suscripcion`),
  ADD KEY `pago_id_estados_foreign` (`id_estados`);

--
-- Indices de la tabla `paradero`
--
ALTER TABLE `paradero`
  ADD PRIMARY KEY (`id_parada`),
  ADD KEY `paradero_ibfk_1` (`id_ruta`);

--
-- Indices de la tabla `plan_suscripcion`
--
ALTER TABLE `plan_suscripcion`
  ADD PRIMARY KEY (`id_plan`),
  ADD KEY `plan_suscripcion_id_estados_foreign` (`id_estados`);

--
-- Indices de la tabla `reciclaje`
--
ALTER TABLE `reciclaje`
  ADD PRIMARY KEY (`id_reciclaje`),
  ADD KEY `reciclaje_ibfk_1` (`documento`),
  ADD KEY `reciclaje_ibfk_2` (`id_punto`),
  ADD KEY `reciclaje_ibfk_3` (`id_tipo_material`);

--
-- Indices de la tabla `redencion_puntos`
--
ALTER TABLE `redencion_puntos`
  ADD PRIMARY KEY (`id_canje`),
  ADD KEY `redencion_puntos_ibfk_1` (`documento`),
  ADD KEY `redencion_puntos_ibfk_2` (`id_pasaje`);

--
-- Indices de la tabla `rol`
--
ALTER TABLE `rol`
  ADD PRIMARY KEY (`id_rol`);

--
-- Indices de la tabla `ruta`
--
ALTER TABLE `ruta`
  ADD PRIMARY KEY (`id_ruta`),
  ADD KEY `ruta_id_empresa_foreign` (`id_empresa`);

--
-- Indices de la tabla `servicio_mantenimiento`
--
ALTER TABLE `servicio_mantenimiento`
  ADD PRIMARY KEY (`id_mantenimiento`),
  ADD KEY `servicio_mantenimiento_ibfk_1` (`id_vehiculo`),
  ADD KEY `servicio_mantenimiento_ibfk_2` (`id_tecnico`),
  ADD KEY `fk_servicio_mantenimiento_estados` (`id_estados`);

--
-- Indices de la tabla `super_admin`
--
ALTER TABLE `super_admin`
  ADD PRIMARY KEY (`documento`),
  ADD UNIQUE KEY `super_admin_email_unique` (`email`),
  ADD KEY `fk_super_admin_rol` (`id_rol`),
  ADD KEY `fk_super_admin_estados` (`id_estados`);

--
-- Indices de la tabla `suscripcion`
--
ALTER TABLE `suscripcion`
  ADD PRIMARY KEY (`id_suscripcion`),
  ADD KEY `suscripcion_id_empresa_foreign` (`id_empresa`),
  ADD KEY `suscripcion_id_plan_foreign` (`id_plan`),
  ADD KEY `suscripcion_id_estados_foreign` (`id_estados`);

--
-- Indices de la tabla `tarea`
--
ALTER TABLE `tarea`
  ADD PRIMARY KEY (`id_tarea`),
  ADD KEY `tarea_ibfk_1` (`id_tecnico`),
  ADD KEY `fk_tarea_estados` (`id_estados`);

--
-- Indices de la tabla `tecnico`
--
ALTER TABLE `tecnico`
  ADD PRIMARY KEY (`id_tecnico`),
  ADD KEY `tecnico_ibfk_1` (`documento`),
  ADD KEY `tecnico_id_empresa_foreign` (`id_empresa`);

--
-- Indices de la tabla `tiquete`
--
ALTER TABLE `tiquete`
  ADD PRIMARY KEY (`id_pasaje`),
  ADD KEY `tiquete_ibfk_1` (`documento`),
  ADD KEY `tiquete_ibfk_2` (`id_viaje`);

--
-- Indices de la tabla `titular_vehiculo`
--
ALTER TABLE `titular_vehiculo`
  ADD PRIMARY KEY (`id_propietario`),
  ADD KEY `titular_vehiculo_ibfk_1` (`documento`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`documento`),
  ADD UNIQUE KEY `correo` (`correo`),
  ADD KEY `fk_usuario_rol` (`id_rol`),
  ADD KEY `fk_usuario_estados` (`id_estados`),
  ADD KEY `usuario_id_empresa_foreign` (`id_empresa`);

--
-- Indices de la tabla `vehiculo`
--
ALTER TABLE `vehiculo`
  ADD PRIMARY KEY (`id_vehiculo`),
  ADD UNIQUE KEY `placa` (`placa`),
  ADD KEY `vehiculo_ibfk_1` (`id_modelo`),
  ADD KEY `vehiculo_ibfk_2` (`id_propietario`),
  ADD KEY `fk_vehiculo_estados` (`id_estados`),
  ADD KEY `vehiculo_id_empresa_foreign` (`id_empresa`);

--
-- Indices de la tabla `viaje`
--
ALTER TABLE `viaje`
  ADD PRIMARY KEY (`id_viaje`),
  ADD KEY `viaje_ibfk_1` (`id_ruta`),
  ADD KEY `viaje_ibfk_2` (`id_asignacion`),
  ADD KEY `fk_viaje_estados` (`id_estados`);

--
-- Indices de la tabla `viaje_paradero`
--
ALTER TABLE `viaje_paradero`
  ADD PRIMARY KEY (`id_viaje_parada`),
  ADD KEY `viaje_paradero_ibfk_1` (`id_viaje`),
  ADD KEY `viaje_paradero_ibfk_2` (`id_parada`),
  ADD KEY `fk_viaje_paradero_estados` (`id_estados`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `ciudad`
--
ALTER TABLE `ciudad`
  MODIFY `id_ciudad` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT de la tabla `conductor`
--
ALTER TABLE `conductor`
  MODIFY `id_conductor` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tecnico`
--
ALTER TABLE `tecnico`
  MODIFY `id_tecnico` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `titular_vehiculo`
--
ALTER TABLE `titular_vehiculo`
  MODIFY `id_propietario` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `departamento`
--
ALTER TABLE `departamento`
  MODIFY `id_departamento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT de la tabla `empresa`
--
ALTER TABLE `empresa`
  MODIFY `id_empresa` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `estado_registro`
--
ALTER TABLE `estado_registro`
  MODIFY `id_estados` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `pago`
--
ALTER TABLE `pago`
  MODIFY `id_pago` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `paradero`
--
ALTER TABLE `paradero`
  MODIFY `id_parada` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13506;

--
-- AUTO_INCREMENT de la tabla `plan_suscripcion`
--
ALTER TABLE `plan_suscripcion`
  MODIFY `id_plan` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `ruta`
--
ALTER TABLE `ruta`
  MODIFY `id_ruta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=136;

--
-- AUTO_INCREMENT de la tabla `suscripcion`
--
ALTER TABLE `suscripcion`
  MODIFY `id_suscripcion` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `asignacion_flota`
--
ALTER TABLE `asignacion_flota`
  ADD CONSTRAINT `asignacion_flota_ibfk_1` FOREIGN KEY (`id_vehiculo`) REFERENCES `vehiculo` (`id_vehiculo`),
  ADD CONSTRAINT `asignacion_flota_ibfk_2` FOREIGN KEY (`id_conductor`) REFERENCES `conductor` (`id_conductor`),
  ADD CONSTRAINT `fk_asignacion_flota_estados` FOREIGN KEY (`id_estados`) REFERENCES `estado_registro` (`id_estados`);

--
-- Filtros para la tabla `calificacion_conductor`
--
ALTER TABLE `calificacion_conductor`
  ADD CONSTRAINT `calificacion_conductor_ibfk_1` FOREIGN KEY (`id_conductor`) REFERENCES `conductor` (`id_conductor`),
  ADD CONSTRAINT `calificacion_conductor_ibfk_2` FOREIGN KEY (`documento`) REFERENCES `usuario` (`documento`);

--
-- Filtros para la tabla `ciudad`
--
ALTER TABLE `ciudad`
  ADD CONSTRAINT `ciudad_id_departamento_foreign` FOREIGN KEY (`id_departamento`) REFERENCES `departamento` (`id_departamento`);

--
-- Filtros para la tabla `conductor`
--
ALTER TABLE `conductor`
  ADD CONSTRAINT `conductor_ibfk_1` FOREIGN KEY (`documento`) REFERENCES `usuario` (`documento`),
  ADD CONSTRAINT `conductor_id_empresa_foreign` FOREIGN KEY (`id_empresa`) REFERENCES `empresa` (`id_empresa`),
  ADD CONSTRAINT `fk_conductor_estados` FOREIGN KEY (`id_estados`) REFERENCES `estado_registro` (`id_estados`),
  ADD CONSTRAINT `fk_conductor_licencia` FOREIGN KEY (`id_licencia`) REFERENCES `licencia` (`id_licencia`);

--
-- Filtros para la tabla `cuenta_puntos`
--
ALTER TABLE `cuenta_puntos`
  ADD CONSTRAINT `cuenta_puntos_ibfk_1` FOREIGN KEY (`documento`) REFERENCES `usuario` (`documento`);

--
-- Filtros para la tabla `detalle_servicio`
--
ALTER TABLE `detalle_servicio`
  ADD CONSTRAINT `detalle_servicio_ibfk_1` FOREIGN KEY (`id_mantenimiento`) REFERENCES `servicio_mantenimiento` (`id_mantenimiento`);

--
-- Filtros para la tabla `documentacion_vehiculo`
--
ALTER TABLE `documentacion_vehiculo`
  ADD CONSTRAINT `documentacion_vehiculo_ibfk_1` FOREIGN KEY (`id_vehiculo`) REFERENCES `vehiculo` (`id_vehiculo`);

--
-- Filtros para la tabla `empresa`
--
ALTER TABLE `empresa`
  ADD CONSTRAINT `empresa_id_ciudad_foreign` FOREIGN KEY (`id_ciudad`) REFERENCES `ciudad` (`id_ciudad`),
  ADD CONSTRAINT `empresa_id_estados_foreign` FOREIGN KEY (`id_estados`) REFERENCES `estado_registro` (`id_estados`);

--
-- Filtros para la tabla `incidencia`
--
ALTER TABLE `incidencia`
  ADD CONSTRAINT `incidencia_ibfk_1` FOREIGN KEY (`id_viaje`) REFERENCES `viaje` (`id_viaje`),
  ADD CONSTRAINT `incidencia_ibfk_2` FOREIGN KEY (`id_conductor`) REFERENCES `conductor` (`id_conductor`);

--
-- Filtros para la tabla `licencia`
--
ALTER TABLE `licencia`
  ADD CONSTRAINT `fk_licencia_categoria` FOREIGN KEY (`id_categoria_licencia`) REFERENCES `clase_licencia` (`id_categoria_licencia`);

--
-- Filtros para la tabla `pago`
--
ALTER TABLE `pago`
  ADD CONSTRAINT `pago_id_estados_foreign` FOREIGN KEY (`id_estados`) REFERENCES `estado_registro` (`id_estados`),
  ADD CONSTRAINT `pago_id_suscripcion_foreign` FOREIGN KEY (`id_suscripcion`) REFERENCES `suscripcion` (`id_suscripcion`);

--
-- Filtros para la tabla `paradero`
--
ALTER TABLE `paradero`
  ADD CONSTRAINT `paradero_ibfk_1` FOREIGN KEY (`id_ruta`) REFERENCES `ruta` (`id_ruta`);

--
-- Filtros para la tabla `plan_suscripcion`
--
ALTER TABLE `plan_suscripcion`
  ADD CONSTRAINT `plan_suscripcion_id_estados_foreign` FOREIGN KEY (`id_estados`) REFERENCES `estado_registro` (`id_estados`);

--
-- Filtros para la tabla `reciclaje`
--
ALTER TABLE `reciclaje`
  ADD CONSTRAINT `reciclaje_ibfk_1` FOREIGN KEY (`documento`) REFERENCES `usuario` (`documento`),
  ADD CONSTRAINT `reciclaje_ibfk_2` FOREIGN KEY (`id_punto`) REFERENCES `centro_acopio` (`id_punto`),
  ADD CONSTRAINT `reciclaje_ibfk_3` FOREIGN KEY (`id_tipo_material`) REFERENCES `categoria_material` (`id_tipo_material`);

--
-- Filtros para la tabla `redencion_puntos`
--
ALTER TABLE `redencion_puntos`
  ADD CONSTRAINT `redencion_puntos_ibfk_1` FOREIGN KEY (`documento`) REFERENCES `usuario` (`documento`),
  ADD CONSTRAINT `redencion_puntos_ibfk_2` FOREIGN KEY (`id_pasaje`) REFERENCES `tiquete` (`id_pasaje`);

--
-- Filtros para la tabla `ruta`
--
ALTER TABLE `ruta`
  ADD CONSTRAINT `ruta_id_empresa_foreign` FOREIGN KEY (`id_empresa`) REFERENCES `empresa` (`id_empresa`);

--
-- Filtros para la tabla `servicio_mantenimiento`
--
ALTER TABLE `servicio_mantenimiento`
  ADD CONSTRAINT `fk_servicio_mantenimiento_estados` FOREIGN KEY (`id_estados`) REFERENCES `estado_registro` (`id_estados`),
  ADD CONSTRAINT `servicio_mantenimiento_ibfk_1` FOREIGN KEY (`id_vehiculo`) REFERENCES `vehiculo` (`id_vehiculo`),
  ADD CONSTRAINT `servicio_mantenimiento_ibfk_2` FOREIGN KEY (`id_tecnico`) REFERENCES `tecnico` (`id_tecnico`);

--
-- Filtros para la tabla `super_admin`
--
ALTER TABLE `super_admin`
  ADD CONSTRAINT `fk_super_admin_estados` FOREIGN KEY (`id_estados`) REFERENCES `estado_registro` (`id_estados`),
  ADD CONSTRAINT `fk_super_admin_rol` FOREIGN KEY (`id_rol`) REFERENCES `rol` (`id_rol`);

--
-- Filtros para la tabla `suscripcion`
--
ALTER TABLE `suscripcion`
  ADD CONSTRAINT `suscripcion_id_empresa_foreign` FOREIGN KEY (`id_empresa`) REFERENCES `empresa` (`id_empresa`),
  ADD CONSTRAINT `suscripcion_id_estados_foreign` FOREIGN KEY (`id_estados`) REFERENCES `estado_registro` (`id_estados`),
  ADD CONSTRAINT `suscripcion_id_plan_foreign` FOREIGN KEY (`id_plan`) REFERENCES `plan_suscripcion` (`id_plan`);

--
-- Filtros para la tabla `tarea`
--
ALTER TABLE `tarea`
  ADD CONSTRAINT `fk_tarea_estados` FOREIGN KEY (`id_estados`) REFERENCES `estado_registro` (`id_estados`),
  ADD CONSTRAINT `tarea_ibfk_1` FOREIGN KEY (`id_tecnico`) REFERENCES `tecnico` (`id_tecnico`);

--
-- Filtros para la tabla `tecnico`
--
ALTER TABLE `tecnico`
  ADD CONSTRAINT `tecnico_ibfk_1` FOREIGN KEY (`documento`) REFERENCES `usuario` (`documento`),
  ADD CONSTRAINT `tecnico_id_empresa_foreign` FOREIGN KEY (`id_empresa`) REFERENCES `empresa` (`id_empresa`);

--
-- Filtros para la tabla `tiquete`
--
ALTER TABLE `tiquete`
  ADD CONSTRAINT `tiquete_ibfk_1` FOREIGN KEY (`documento`) REFERENCES `usuario` (`documento`),
  ADD CONSTRAINT `tiquete_ibfk_2` FOREIGN KEY (`id_viaje`) REFERENCES `viaje` (`id_viaje`);

--
-- Filtros para la tabla `titular_vehiculo`
--
ALTER TABLE `titular_vehiculo`
  ADD CONSTRAINT `titular_vehiculo_ibfk_1` FOREIGN KEY (`documento`) REFERENCES `usuario` (`documento`);

--
-- Filtros para la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD CONSTRAINT `fk_usuario_estados` FOREIGN KEY (`id_estados`) REFERENCES `estado_registro` (`id_estados`),
  ADD CONSTRAINT `fk_usuario_rol` FOREIGN KEY (`id_rol`) REFERENCES `rol` (`id_rol`),
  ADD CONSTRAINT `usuario_id_empresa_foreign` FOREIGN KEY (`id_empresa`) REFERENCES `empresa` (`id_empresa`);

--
-- Filtros para la tabla `vehiculo`
--
ALTER TABLE `vehiculo`
  ADD CONSTRAINT `fk_vehiculo_estados` FOREIGN KEY (`id_estados`) REFERENCES `estado_registro` (`id_estados`),
  ADD CONSTRAINT `vehiculo_ibfk_1` FOREIGN KEY (`id_modelo`) REFERENCES `modelo_vehiculo` (`id_modelo`),
  ADD CONSTRAINT `vehiculo_ibfk_2` FOREIGN KEY (`id_propietario`) REFERENCES `titular_vehiculo` (`id_propietario`),
  ADD CONSTRAINT `vehiculo_id_empresa_foreign` FOREIGN KEY (`id_empresa`) REFERENCES `empresa` (`id_empresa`);

--
-- Filtros para la tabla `viaje`
--
ALTER TABLE `viaje`
  ADD CONSTRAINT `fk_viaje_estados` FOREIGN KEY (`id_estados`) REFERENCES `estado_registro` (`id_estados`),
  ADD CONSTRAINT `viaje_ibfk_1` FOREIGN KEY (`id_ruta`) REFERENCES `ruta` (`id_ruta`),
  ADD CONSTRAINT `viaje_ibfk_2` FOREIGN KEY (`id_asignacion`) REFERENCES `asignacion_flota` (`id_asignacion`);

--
-- Filtros para la tabla `viaje_paradero`
--
ALTER TABLE `viaje_paradero`
  ADD CONSTRAINT `fk_viaje_paradero_estados` FOREIGN KEY (`id_estados`) REFERENCES `estado_registro` (`id_estados`),
  ADD CONSTRAINT `viaje_paradero_ibfk_1` FOREIGN KEY (`id_viaje`) REFERENCES `viaje` (`id_viaje`),
  ADD CONSTRAINT `viaje_paradero_ibfk_2` FOREIGN KEY (`id_parada`) REFERENCES `paradero` (`id_parada`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
