-- golapp.sql - Esquema y datos de GolApp (MySQL 5.7+ / MariaDB 10.3+)
-- Importar con: mysql -u root -p < database/golapp.sql   (o desde phpMyAdmin > Importar)
CREATE DATABASE IF NOT EXISTS golapp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE golapp;

DROP TABLE IF EXISTS prestamos;
DROP TABLE IF EXISTS reservas;
DROP TABLE IF EXISTS productos;
DROP TABLE IF EXISTS canchas;
DROP TABLE IF EXISTS tipos_cancha;
DROP TABLE IF EXISTS usuarios;

CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  correo VARCHAR(120) NOT NULL UNIQUE,
  contrasena VARCHAR(255) NOT NULL,          -- hash bcrypt (password_hash)
  telefono VARCHAR(20) NOT NULL,
  rol ENUM('cliente','administrador') NOT NULL DEFAULT 'cliente',
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE tipos_cancha (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(60) NOT NULL,
  descripcion VARCHAR(200) NULL
) ENGINE=InnoDB;

CREATE TABLE canchas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(60) NOT NULL,
  id_tipo INT NOT NULL,
  precio_hora DECIMAL(10,2) NOT NULL,
  activa TINYINT(1) NOT NULL DEFAULT 1,
  FOREIGN KEY (id_tipo) REFERENCES tipos_cancha(id)
) ENGINE=InnoDB;

CREATE TABLE reservas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT NOT NULL,
  id_cancha INT NOT NULL,
  fecha DATE NOT NULL,
  hora_inicio TIME NOT NULL,
  hora_fin TIME NOT NULL,
  estado ENUM('pendiente','confirmada','cancelada','finalizada') NOT NULL DEFAULT 'pendiente',
  total DECIMAL(10,2) NOT NULL,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id),
  FOREIGN KEY (id_cancha) REFERENCES canchas(id),
  INDEX idx_cancha_fecha (id_cancha, fecha),      -- acelera calendario y validación de cruces
  INDEX idx_usuario (id_usuario),
  INDEX idx_estado_fecha (estado, fecha)
) ENGINE=InnoDB;

CREATE TABLE productos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(80) NOT NULL,
  stock_total INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE prestamos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_reserva INT NOT NULL,
  id_producto INT NOT NULL,
  cantidad INT NOT NULL,
  devuelto TINYINT(1) NOT NULL DEFAULT 0,
  FOREIGN KEY (id_reserva) REFERENCES reservas(id) ON DELETE CASCADE,
  FOREIGN KEY (id_producto) REFERENCES productos(id),
  INDEX idx_producto (id_producto)
) ENGINE=InnoDB;

-- Datos de ejemplo. Admin: admin@golapp.com / Admin123*   Cliente: cliente@golapp.com / Cliente123*
INSERT INTO usuarios (nombre, correo, contrasena, telefono, rol) VALUES
 ('Administrador GolApp', 'admin@golapp.com', '$2y$10$r.SnVPm1V6pkiX.para6LeVZ1Z2lkpCrTwsTjkWc/0KL..uqO8G1m', '3000000000', 'administrador'),
 ('Cliente de Prueba', 'cliente@golapp.com', '$2y$10$TEDutDeFYJhkFeCnp6bIyukCIQVBiVm9aEPqSpal.yZijy4/rs3o.', '3111111111', 'cliente');
INSERT INTO tipos_cancha (nombre, descripcion) VALUES
 ('Fútbol 5', 'Cancha sintética para 5 jugadores por equipo'),
 ('Fútbol 8', 'Cancha sintética para 8 jugadores por equipo');
INSERT INTO canchas (nombre, id_tipo, precio_hora, activa) VALUES
 ('Cancha 1', 1, 60000, 1), ('Cancha 2', 1, 60000, 1), ('Cancha 3', 2, 90000, 1);
INSERT INTO productos (nombre, stock_total) VALUES ('Balones', 6), ('Petos', 30), ('Conos', 20);
