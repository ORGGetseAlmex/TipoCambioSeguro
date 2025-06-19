-- Crear la base de datos
CREATE DATABASE IF NOT EXISTS tipocambio_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Crear el usuario
CREATE USER IF NOT EXISTS 'tipocambio_user'@'localhost' IDENTIFIED BY 'Almex2025';

-- Darle permisos completos sobre esa base de datos
GRANT ALL PRIVILEGES ON tipocambio_db.* TO 'tipocambio_user'@'localhost';

-- Aplicar cambios
FLUSH PRIVILEGES;

-- Change Database to tipocambio_db before create tables
use tipocambio_db;

CREATE TABLE IF NOT EXISTS tblTipoCambio (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    Valor DECIMAL(10,4) NOT NULL,
    FechaValor DATE NOT NULL,
    FechaEmision DATE NOT NULL,
    FechaLiquidacion DATE NOT NULL,
    Moneda VARCHAR(3) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS tblTipoCambio (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    Valor DECIMAL(10,4) NOT NULL,
    FechaValor DATE NOT NULL,
    FechaEmision DATE NOT NULL,
    FechaLiquidacion DATE NOT NULL,
    Moneda VARCHAR(3) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS tblTipoCambioStatus (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ultima_actualizacion DATE
);

CREATE TABLE IF NOT EXISTS dias_festivos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATE NOT NULL UNIQUE,
    descripcion VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE tblDiasFestivos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATE NOT NULL UNIQUE,
    descripcion VARCHAR(100),
    recurrente BOOLEAN DEFAULT 0
);


ALTER TABLE tblTipoCambio 
ADD UNIQUE KEY uniq_fecha_moneda (FechaValor, Moneda);
