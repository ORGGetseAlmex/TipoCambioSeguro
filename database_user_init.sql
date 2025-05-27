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

