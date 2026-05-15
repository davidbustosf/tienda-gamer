-- ============================================================
-- TIENDA GAMER - Base de Datos
-- INTEGRANTE 1: Subir este archivo (tienda_gamer.sql)
-- ============================================================

CREATE DATABASE IF NOT EXISTS tienda_gamer CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tienda_gamer;

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id_usuario  INT AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(100)  NOT NULL,
    correo      VARCHAR(150)  NOT NULL UNIQUE,
    contrasena  VARCHAR(255)  NOT NULL,
    rol         ENUM('admin','cliente') NOT NULL DEFAULT 'cliente',
    creado_en   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabla de productos gamer
CREATE TABLE IF NOT EXISTS productos (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    categoria        VARCHAR(50)    NOT NULL,
    marca            VARCHAR(80)    NOT NULL,
    nombre           VARCHAR(150)   NOT NULL,
    precio           DECIMAL(10,2)  NOT NULL,
    stock            INT            NOT NULL DEFAULT 0,
    descripcion      TEXT,
    especificaciones TEXT,
    imagen           VARCHAR(255)   DEFAULT '',
    extension        VARCHAR(10)    DEFAULT '',
    creado_en        TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabla de imágenes de productos (múltiples por producto)
CREATE TABLE IF NOT EXISTS producto_imagenes (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    id_producto  INT NOT NULL,
    imagen       VARCHAR(255) NOT NULL,
    principal    TINYINT(1)   NOT NULL DEFAULT 0,
    orden        INT          NOT NULL DEFAULT 0,
    FOREIGN KEY (id_producto) REFERENCES productos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabla de ventas
CREATE TABLE IF NOT EXISTS ventas (
    id_venta    INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario  INT NOT NULL,
    total       DECIMAL(10,2) NOT NULL,
    metodo_pago ENUM('qr','tarjeta','efectivo') NOT NULL DEFAULT 'efectivo',
    fecha       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB;

-- Tabla de detalle de venta
CREATE TABLE IF NOT EXISTS detalle_venta (
    id_detalle       INT AUTO_INCREMENT PRIMARY KEY,
    id_venta         INT NOT NULL,
    id_producto      INT NOT NULL,
    nombre_producto  VARCHAR(200) NOT NULL,
    precio           DECIMAL(10,2) NOT NULL,
    cantidad         INT NOT NULL,
    FOREIGN KEY (id_venta)    REFERENCES ventas(id_venta) ON DELETE CASCADE,
    FOREIGN KEY (id_producto) REFERENCES productos(id)
) ENGINE=InnoDB;

-- Admin por defecto (contraseña: admin123)
INSERT INTO usuarios (nombre, correo, contrasena, rol) VALUES
('Administrador', 'admin@tiendagamer.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Productos de ejemplo
INSERT INTO productos (categoria, marca, nombre, precio, stock, descripcion, especificaciones) VALUES
('Teclado',  'Redragon',  'Kumara K552',         45.99,  20, 'Teclado mecánico compacto TKL con retroiluminación RGB. Ideal para gaming competitivo.',  'Switches: Red\nLayout: TKL 87 teclas\nRetroiluminación: RGB\nConexión: USB\nAnti-ghosting: Sí'),
('Mouse',    'Logitech',  'G502 Hero',            59.99,  15, 'Mouse gaming de alta precisión con sensor HERO 25K y pesas ajustables.',                 'Sensor: HERO 25K DPI\nDPI máx: 25.600\nBotones: 11\nPeso: 121g\nConexión: USB'),
('Headset',  'HyperX',   'Cloud II',             79.99,  12, 'Auriculares gaming con sonido envolvente 7.1 virtual y micrófono con cancelación de ruido.','Respuesta en frecuencia: 15-25.000 Hz\nConexión: USB / 3.5mm\nMicrófono: Desmontable\nSonido: 7.1 Virtual'),
('Monitor',  'LG',        'UltraGear 24GN650',   219.99,  8, 'Monitor gaming Full HD 144Hz con panel IPS y 1ms de tiempo de respuesta.',              'Resolución: 1920x1080\nPanel: IPS\nTasa de refresco: 144Hz\nTiempo de respuesta: 1ms\nHDR: HDR10'),
('Silla',    'DXRacer',   'Formula Series F08',  299.99,  5, 'Silla gaming ergonómica con soporte lumbar y reposacabezas ajustable.',                  'Material: PU Cuero\nPeso máx: 130kg\nAltura ajustable: Sí\nReclinación: 135°\nRuedas: Nylon'),
('Control',  'Xbox',      'Series X Wireless',   64.99,  18, 'Control inalámbrico para Xbox Series X/S y PC con textura antideslizante.',              'Conexión: Bluetooth / USB-C\nBatería: 2 pilas AA\nCompatible: Xbox / PC\nTextura: Antideslizante'),
('GPU',      'MSI',       'GeForce RTX 4060',    329.99,  6, 'Tarjeta gráfica RTX 4060 con DLSS 3 y Ray Tracing para gaming 1080p y 1440p.',           'VRAM: 8GB GDDR6\nAncho de bus: 128-bit\nCores CUDA: 3072\nConexión: PCIe 4.0 x8\nPuertos: 3x DP / 1x HDMI'),
('RAM',      'Corsair',   'Vengeance DDR5 32GB', 119.99, 10, 'Kit de memoria DDR5 32GB (2x16GB) a 6000MHz con RGB.',                                   'Capacidad: 32GB (2x16GB)\nTipo: DDR5\nVelocidad: 6000MHz\nLatencia: CL30\nRGB: Sí');
