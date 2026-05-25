-- UrbanFood Database
DROP DATABASE IF EXISTS UrbanFoodDB;
CREATE DATABASE UrbanFoodDB;
USE UrbanFoodDB;

-- TABLA CLIENTES (con email y password)
CREATE TABLE Clientes (
    id_cliente INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    telefono VARCHAR(20),
    direccion VARCHAR(255),
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- TABLA RESTAURANTES
CREATE TABLE Restaurantes (
    id_restaurante INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    direccion VARCHAR(255),
    telefono VARCHAR(20),
    categoria VARCHAR(100),
    imagen VARCHAR(255)
);

-- TABLA REPARTIDORES
CREATE TABLE Repartidores (
    id_repartidor INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    telefono VARCHAR(20),
    activo TINYINT(1) DEFAULT 1
);

-- TABLA PRODUCTOS
CREATE TABLE Productos (
    id_producto INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    descripcion VARCHAR(255),
    id_restaurante INT,
    FOREIGN KEY (id_restaurante) REFERENCES Restaurantes(id_restaurante)
);

-- TABLA PEDIDOS
CREATE TABLE Pedidos (
    id_pedido INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT,
    id_restaurante INT,
    id_repartidor INT,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    total DECIMAL(10,2),
    estado ENUM('pendiente','en_camino','entregado') DEFAULT 'pendiente',
    FOREIGN KEY (id_cliente) REFERENCES Clientes(id_cliente),
    FOREIGN KEY (id_restaurante) REFERENCES Restaurantes(id_restaurante),
    FOREIGN KEY (id_repartidor) REFERENCES Repartidores(id_repartidor)
);

-- TABLA DETALLE_PEDIDO
CREATE TABLE Detalle_Pedidos (
    id_detalle INT AUTO_INCREMENT PRIMARY KEY,
    id_pedido INT,
    id_producto INT,
    cantidad INT NOT NULL,
    subtotal DECIMAL(10,2),
    FOREIGN KEY (id_pedido) REFERENCES Pedidos(id_pedido),
    FOREIGN KEY (id_producto) REFERENCES Productos(id_producto)
);

-- Datos de ejemplo
INSERT INTO Restaurantes (nombre, direccion, telefono, categoria) VALUES
('Pizza Hut', 'Bulevar Los Héroes, San Salvador', '2263-5555', 'Pizza'),
('Burger King', 'Multiplaza Santa Elena', '2243-6666', 'Hamburguesas'),
('Pollo Campero', 'Metrocentro, San Salvador', '2250-7777', 'Pollo'),
('Subway', 'Gran Vía, Antiguo Cuscatlán', '2278-8888', 'Sandwiches');

INSERT INTO Repartidores (nombre, telefono) VALUES
('Carlos Mendez', '7999-1111'),
('Luis García', '7888-2222'),
('Pedro Flores', '7777-3333'),
('Andrés Ramos', '7666-4444');

INSERT INTO Productos (nombre, precio, descripcion, id_restaurante) VALUES
('Pizza Pepperoni Mediana', 10.99, 'Deliciosa pizza con pepperoni y queso mozzarella', 1),
('Pizza Hawaiana Grande', 14.99, 'Pizza con piña y jamón', 1),
('Pan de Ajo', 3.50, 'Pan artesanal con mantequilla de ajo', 1),
('Whopper Clásico', 6.49, 'Hamburguesa de carne a la parrilla con vegetales frescos', 2),
('Doble Whopper', 9.49, 'Doble carne a la parrilla con queso', 2),
('Aros de Cebolla', 2.99, 'Crujientes aros de cebolla', 2),
('Combo Pollo Campero 3pz', 7.99, '3 piezas de pollo, papas y refresco', 3),
('Pollo a la Plancha', 8.50, 'Pollo a la plancha con ensalada', 3),
('Sandwich Italian BMT', 7.25, 'Salami, jamón, pepperoni con vegetales', 4),
('Sandwich Turkey Breast', 6.75, 'Pechuga de pavo con vegetales frescos', 4);

-- Cliente de ejemplo (password: 123456)
INSERT INTO Clientes (nombre, telefono, direccion, email, password) VALUES
('Juan Pérez', '7123-4567', 'Colonia Escalón, San Salvador', 'juan@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
