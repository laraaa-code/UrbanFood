-- Respaldo UrbanFoodDB
-- Fecha: 2026-05-03 18:24:05

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `backup_schedule`;
CREATE TABLE `backup_schedule` (
  `id` int NOT NULL DEFAULT '1',
  `activo` tinyint(1) DEFAULT '0',
  `frecuencia` varchar(20) DEFAULT 'diario',
  `hora` time DEFAULT '02:00:00',
  `dia_semana` tinyint DEFAULT '1',
  `dia_mes` tinyint DEFAULT '1',
  `mes` tinyint DEFAULT '1',
  `ultimo_backup` datetime DEFAULT NULL,
  `proximo_backup` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `backup_schedule` VALUES ('1', '1', 'diario', '17:03:00', '1', '1', '1', '2026-05-03 17:03:00', '2026-05-04 17:03:00');

DROP TABLE IF EXISTS `clientes`;
CREATE TABLE `clientes` (
  `id_cliente` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_cliente`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `clientes` VALUES ('1', 'Juan Pérez', '7123-4567', 'Colonia Escalón, San Salvador', 'juan@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-05-03 16:44:25');

DROP TABLE IF EXISTS `detalle_pedidos`;
CREATE TABLE `detalle_pedidos` (
  `id_detalle` int NOT NULL AUTO_INCREMENT,
  `id_pedido` int DEFAULT NULL,
  `id_producto` int DEFAULT NULL,
  `cantidad` int NOT NULL,
  `subtotal` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id_detalle`),
  KEY `id_pedido` (`id_pedido`),
  KEY `id_producto` (`id_producto`),
  CONSTRAINT `detalle_pedidos_ibfk_1` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id_pedido`),
  CONSTRAINT `detalle_pedidos_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `pedidos`;
CREATE TABLE `pedidos` (
  `id_pedido` int NOT NULL AUTO_INCREMENT,
  `id_cliente` int DEFAULT NULL,
  `id_restaurante` int DEFAULT NULL,
  `id_repartidor` int DEFAULT NULL,
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP,
  `total` decimal(10,2) DEFAULT NULL,
  `estado` enum('pendiente','en_camino','entregado') DEFAULT 'pendiente',
  PRIMARY KEY (`id_pedido`),
  KEY `id_cliente` (`id_cliente`),
  KEY `id_restaurante` (`id_restaurante`),
  KEY `id_repartidor` (`id_repartidor`),
  CONSTRAINT `pedidos_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`),
  CONSTRAINT `pedidos_ibfk_2` FOREIGN KEY (`id_restaurante`) REFERENCES `restaurantes` (`id_restaurante`),
  CONSTRAINT `pedidos_ibfk_3` FOREIGN KEY (`id_repartidor`) REFERENCES `repartidores` (`id_repartidor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `productos`;
CREATE TABLE `productos` (
  `id_producto` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `id_restaurante` int DEFAULT NULL,
  PRIMARY KEY (`id_producto`),
  KEY `id_restaurante` (`id_restaurante`),
  CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`id_restaurante`) REFERENCES `restaurantes` (`id_restaurante`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `productos` VALUES ('1', 'Pizza Pepperoni Mediana', '10.99', 'Deliciosa pizza con pepperoni y queso mozzarella', '1');
INSERT INTO `productos` VALUES ('2', 'Pizza Hawaiana Grande', '14.99', 'Pizza con piña y jamón', '1');
INSERT INTO `productos` VALUES ('3', 'Pan de Ajo', '3.50', 'Pan artesanal con mantequilla de ajo', '1');
INSERT INTO `productos` VALUES ('4', 'Whopper Clásico', '6.49', 'Hamburguesa de carne a la parrilla con vegetales frescos', '2');
INSERT INTO `productos` VALUES ('5', 'Doble Whopper', '9.49', 'Doble carne a la parrilla con queso', '2');
INSERT INTO `productos` VALUES ('6', 'Aros de Cebolla', '2.99', 'Crujientes aros de cebolla', '2');
INSERT INTO `productos` VALUES ('7', 'Combo Pollo Campero 3pz', '7.99', '3 piezas de pollo, papas y refresco', '3');
INSERT INTO `productos` VALUES ('8', 'Pollo a la Plancha', '8.50', 'Pollo a la plancha con ensalada', '3');
INSERT INTO `productos` VALUES ('9', 'Sandwich Italian BMT', '7.25', 'Salami, jamón, pepperoni con vegetales', '4');
INSERT INTO `productos` VALUES ('10', 'Sandwich Turkey Breast', '6.75', 'Pechuga de pavo con vegetales frescos', '4');

DROP TABLE IF EXISTS `repartidores`;
CREATE TABLE `repartidores` (
  `id_repartidor` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id_repartidor`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `repartidores` VALUES ('1', 'Carlos Mendez', '7999-1111', '1');
INSERT INTO `repartidores` VALUES ('2', 'Luis García', '7888-2222', '1');
INSERT INTO `repartidores` VALUES ('3', 'Pedro Flores', '7777-3333', '1');
INSERT INTO `repartidores` VALUES ('4', 'Andrés Ramos', '7666-4444', '1');

DROP TABLE IF EXISTS `restaurantes`;
CREATE TABLE `restaurantes` (
  `id_restaurante` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `categoria` varchar(100) DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_restaurante`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `restaurantes` VALUES ('1', 'Pizza Hut', 'Bulevar Los Héroes, San Salvador', '2263-5555', 'Pizza', NULL);
INSERT INTO `restaurantes` VALUES ('2', 'Burger King', 'Multiplaza Santa Elena', '2243-6666', 'Hamburguesas', NULL);
INSERT INTO `restaurantes` VALUES ('3', 'Pollo Campero', 'Metrocentro, San Salvador', '2250-7777', 'Pollo', NULL);
INSERT INTO `restaurantes` VALUES ('4', 'Subway', 'Gran Vía, Antiguo Cuscatlán', '2278-8888', 'Sandwiches', NULL);

SET FOREIGN_KEY_CHECKS=1;
