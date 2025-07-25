-- phpMyAdmin SQL Dump
-- version 4.8.5
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 25-07-2025 a las 04:12:31
-- Versión del servidor: 10.1.38-MariaDB
-- Versión de PHP: 7.3.2

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `lista_to_do`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `campos_adicionales`
--

CREATE TABLE `campos_adicionales` (
  `id_campo` int(11) NOT NULL,
  `id_tarea` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `valor` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `campos_adicionales`
--

INSERT INTO `campos_adicionales` (`id_campo`, `id_tarea`, `id_usuario`, `nombre`, `valor`) VALUES
(1, 14, 3, 'Objetivo', 'Calcular el área de diferentes figuras geométricas.'),
(2, 14, 3, 'Funcionalidad', 'Permitir al usuario seleccionar una figura y proporcionar los datos necesarios para el cálculo.'),
(3, 14, 3, 'Resultado', 'Mostrar el área calculada de la figura seleccionada.'),
(4, 15, 3, 'Frontend', 'React, Vue.js, Angular (con HTML, CSS, JavaScript)'),
(5, 15, 3, 'Backend', 'Node.js (Express), Python (Django/Flask), PHP (Laravel)'),
(6, 15, 3, 'Base de datos', 'MongoDB, PostgreSQL, MySQL'),
(7, 16, 3, 'Objetivo', 'Crear una plataforma digital para que individuos compartan sus ideas, conocimientos o experiencias con una audiencia global, estableciendo una presencia online profesional o personal.'),
(8, 16, 3, 'Funcionalidad clave', 'Publicación de Artículos: Crear, editar y eliminar entradas de blog con contenido de texto e imágenes.  Navegación: Secciones para el blog principal, \"Acerca de Mí\" y \"Contacto\".  Responsividad: Adaptación del diseño a diferentes tamaños de pantalla (móviles, tabletas, escritorio).'),
(9, 16, 3, 'Experiencia de usuario', 'La legibilidad del texto, la velocidad de carga y la facilidad para encontrar contenido son vitales.'),
(10, 16, 3, 'Público objetivo', 'Blogueros individuales, escritores, expertos en un nicho específico que desean compartir contenido, o profesionales que buscan construir una marca personal online.');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cargos`
--

CREATE TABLE `cargos` (
  `id_cargo` int(11) NOT NULL,
  `id_departamento` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `cargos`
--

INSERT INTO `cargos` (`id_cargo`, `id_departamento`, `nombre`) VALUES
(23, 9, 'Gerente de Ventas'),
(24, 9, 'Coordinador de Ventas'),
(25, 9, 'Asesor Senior'),
(26, 9, 'Asesor Junior'),
(27, 9, 'Promotor de Ventas'),
(28, 10, 'Diseñador Gráfico'),
(29, 10, 'Community Manager'),
(30, 11, 'Coordinador de Desarrollo'),
(31, 11, 'Desarrollador Backend'),
(32, 11, 'Desarrollador Frontend'),
(33, 11, 'Pasante'),
(34, 12, 'Reclutador'),
(35, 12, 'Especialista en Nóminas');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comentarios`
--

CREATE TABLE `comentarios` (
  `id_comentario` int(11) NOT NULL,
  `comentario` text NOT NULL,
  `id_tarea` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `comentarios`
--

INSERT INTO `comentarios` (`id_comentario`, `comentario`, `id_tarea`, `id_usuario`, `fecha_creacion`) VALUES
(1, 'Me gusta', 7, 3, '2025-07-22 19:41:21'),
(2, 'Sigue asi', 7, 3, '2025-07-22 19:44:17'),
(3, 'HBBDHYFY', 9, 3, '2025-07-24 19:02:52');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `departamentos`
--

CREATE TABLE `departamentos` (
  `id_departamento` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `departamentos`
--

INSERT INTO `departamentos` (`id_departamento`, `nombre`) VALUES
(9, 'Ventas Tradicionales'),
(10, 'Marketing'),
(11, 'Ingenieria y Desarrollo'),
(12, 'Recursos Humanos');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estatus`
--

CREATE TABLE `estatus` (
  `id_estatus` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `estatus`
--

INSERT INTO `estatus` (`id_estatus`, `nombre`) VALUES
(1, 'Pendiente'),
(2, 'En curso'),
(3, 'Finalizada');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `plantillas`
--

CREATE TABLE `plantillas` (
  `id_plantilla` int(11) NOT NULL,
  `id_cargo` int(11) DEFAULT NULL,
  `nombre` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id_rol` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id_rol`, `nombre`) VALUES
(1, 'Administrador'),
(2, 'Usuario'),
(3, 'Supervisor');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tareas`
--

CREATE TABLE `tareas` (
  `id_tarea` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_usuario_asignado` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `categoria` text NOT NULL,
  `descripcion` text,
  `herramientas` text,
  `id_estatus` int(11) NOT NULL,
  `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `fecha_finalizacion` datetime DEFAULT NULL,
  `prioridad` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `tareas`
--

INSERT INTO `tareas` (`id_tarea`, `id_usuario`, `id_usuario_asignado`, `titulo`, `categoria`, `descripcion`, `herramientas`, `id_estatus`, `fecha_creacion`, `fecha_actualizacion`, `fecha_finalizacion`, `prioridad`) VALUES
(14, 3, 4, 'Calculadora de Área de Figuras Geométricas', 'Backend', 'El objetivo de esta tarea es crear una calculadora que pueda calcular el área de diferentes figuras geométricas. El programa solicitará al usuario que elija una figura geométrica (círculo, cuadrado, rectángulo, triángulo) y luego solicitará las dimensiones necesarias para calcular el área. Finalmente, mostrará el resultado del cálculo.', 'Lenguaje de programación: Python (u otro lenguaje de tu elección).\r\nEditor de código: Visual Studio Code, Sublime Text, PyCharm, etc.\r\nLibrerías (opcional): math para cálculos matemáticos.', 1, '2025-07-24 21:13:09', NULL, NULL, ''),
(15, 3, 3, 'Aplicación de lista de tareas pendientes', 'Desarrollo Web', 'Los usuarios deben poder agregar, editar, eliminar y marcar tareas como completadas.\r\nImplementar autenticación de usuarios y la posibilidad de asignar fechas límite a las tareas.', 'Laptop', 2, '2025-07-24 21:37:43', '2025-07-24 21:41:55', NULL, ''),
(16, 3, 3, 'Blog Personal Responsivo', 'Desarrollo Web', 'Este proyecto implica construir un sitio web dinámico donde el creador pueda publicar artículos de forma regular. El blog debe ser visualmente atractivo y fácil de navegar. La responsividad es fundamental para asegurar que los visitantes tengan una buena experiencia sin importar el dispositivo que usen. Un sistema de comentarios permite la interacción con la audiencia.', 'Frontend: Next.js (framework de React para renderizado del lado del servidor y generación de sitios estáticos), CSS Modules o Styled Components (para estilos encapsulados).\r\nBackend: Strapi (CMS Headless para gestionar el contenido del blog a través de una API).\r\nBase de Datos: PostgreSQL (para la gestión de contenido estructurado de Strapi).\r\nDespliegue: Vercel (para Next.js) o Netlify (para sitios estáticos).', 1, '2025-07-24 21:41:00', NULL, NULL, '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `contraseña` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `telefono` int(11) NOT NULL,
  `id_departamento` int(11) DEFAULT NULL,
  `id_cargo` int(11) DEFAULT NULL,
  `id_rol` int(11) DEFAULT NULL,
  `fecha_registro` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `nombre`, `usuario`, `contraseña`, `email`, `telefono`, `id_departamento`, `id_cargo`, `id_rol`, `fecha_registro`) VALUES
(3, 'Greiso Briceño', 'bgreiso', '$2y$10$X91BmWZJtd6WMGY7aQQ52eO7FED/1FCvzYQ7xVh.LXafr4TPMaQha', 'BGREISO@GMAIL.COM', 2147483647, 11, 33, 1, '2025-07-15 22:10:16'),
(4, 'Maria Ruiz', 'testmari', '$2y$10$DoFrrUDy2whaWSwJrQBA9.5L6h7VFjMM2A9UDF4ISP5vYxOUlC2/K', 'PLUSAMAR15@GMAIL.COM', 2147483647, 11, 33, 2, '2025-07-15 22:12:26'),
(5, 'user', 'user', '$2y$10$WzKq0fWkxMu4NuIxkI/EZ.SKc8d2wzRhp6A6w3kDv8o1s1ysmvkhm', '', 0, 11, 33, 2, '2025-07-19 20:49:46'),
(14, 'Ronald', 'Rviloria', '$2y$10$GClMWcEzaPHMoiHCHH/7peZTxxt5jxNO0srDGVKl80wVuD1uuZMCu', '', 0, 9, 24, 2, '2025-07-20 20:40:16'),
(15, 'Julio', 'juliom', '$2y$10$Uvdi4849YiICXAc/6LOVCuPmAEnfmn5GYDLOtXYB7YVWrfauSuZhq', '', 0, 10, 29, 2, '2025-07-22 18:51:12'),
(16, 'Pedro', 'Pedrop', '$2y$10$0BNcZh6RQTvdjCqGuB4lDuhWsKlCxHVrSkD9.Wl/uqhV1myldKoq6', '', 0, 12, 35, 2, '2025-07-24 20:28:36'),
(17, 'Julieta', 'Julietap', '$2y$10$d55ijB1.OS6Ur/mrI5GivuJIIfPzsy6IQ4UVUYy6fWQVENuNfM12G', '', 0, 10, 29, 1, '2025-07-24 20:29:12');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `campos_adicionales`
--
ALTER TABLE `campos_adicionales`
  ADD PRIMARY KEY (`id_campo`),
  ADD KEY `id_tarea` (`id_tarea`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `cargos`
--
ALTER TABLE `cargos`
  ADD PRIMARY KEY (`id_cargo`);

--
-- Indices de la tabla `comentarios`
--
ALTER TABLE `comentarios`
  ADD PRIMARY KEY (`id_comentario`),
  ADD KEY `id_tarea` (`id_tarea`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `departamentos`
--
ALTER TABLE `departamentos`
  ADD PRIMARY KEY (`id_departamento`);

--
-- Indices de la tabla `estatus`
--
ALTER TABLE `estatus`
  ADD PRIMARY KEY (`id_estatus`);

--
-- Indices de la tabla `plantillas`
--
ALTER TABLE `plantillas`
  ADD PRIMARY KEY (`id_plantilla`),
  ADD KEY `id_cargo` (`id_cargo`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id_rol`);

--
-- Indices de la tabla `tareas`
--
ALTER TABLE `tareas`
  ADD PRIMARY KEY (`id_tarea`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_estatus` (`id_estatus`),
  ADD KEY `tareas_ibfk_3` (`id_usuario_asignado`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `usuario` (`usuario`),
  ADD KEY `id_departamento` (`id_departamento`),
  ADD KEY `id_cargo` (`id_cargo`),
  ADD KEY `id_rol` (`id_rol`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `campos_adicionales`
--
ALTER TABLE `campos_adicionales`
  MODIFY `id_campo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `cargos`
--
ALTER TABLE `cargos`
  MODIFY `id_cargo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT de la tabla `comentarios`
--
ALTER TABLE `comentarios`
  MODIFY `id_comentario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `departamentos`
--
ALTER TABLE `departamentos`
  MODIFY `id_departamento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `estatus`
--
ALTER TABLE `estatus`
  MODIFY `id_estatus` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `plantillas`
--
ALTER TABLE `plantillas`
  MODIFY `id_plantilla` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `tareas`
--
ALTER TABLE `tareas`
  MODIFY `id_tarea` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `campos_adicionales`
--
ALTER TABLE `campos_adicionales`
  ADD CONSTRAINT `campos_adicionales_ibfk_1` FOREIGN KEY (`id_tarea`) REFERENCES `tareas` (`id_tarea`),
  ADD CONSTRAINT `campos_adicionales_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `comentarios`
--
ALTER TABLE `comentarios`
  ADD CONSTRAINT `comentarios_ibfk_1` FOREIGN KEY (`id_tarea`) REFERENCES `tareas` (`id_tarea`),
  ADD CONSTRAINT `comentarios_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `plantillas`
--
ALTER TABLE `plantillas`
  ADD CONSTRAINT `plantillas_ibfk_1` FOREIGN KEY (`id_cargo`) REFERENCES `cargos` (`id_cargo`);

--
-- Filtros para la tabla `tareas`
--
ALTER TABLE `tareas`
  ADD CONSTRAINT `tareas_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `tareas_ibfk_2` FOREIGN KEY (`id_estatus`) REFERENCES `estatus` (`id_estatus`),
  ADD CONSTRAINT `tareas_ibfk_3` FOREIGN KEY (`id_usuario_asignado`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`id_departamento`) REFERENCES `departamentos` (`id_departamento`),
  ADD CONSTRAINT `usuarios_ibfk_2` FOREIGN KEY (`id_cargo`) REFERENCES `cargos` (`id_cargo`),
  ADD CONSTRAINT `usuarios_ibfk_3` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
