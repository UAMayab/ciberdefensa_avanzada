-- Kaab Orgánicos (FICTICIO). Base de clientes, lotes, productos, pedidos y líneas de pedido.
-- Idempotente: se puede aplicar en cada aprovisionamiento.

CREATE TABLE IF NOT EXISTS clientes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  correo VARCHAR(160) NOT NULL,
  telefono VARCHAR(20),
  direccion VARCHAR(255),
  pais CHAR(2) NOT NULL,
  rfc VARCHAR(13),
  creado_en DATETIME NOT NULL,
  INDEX idx_correo (correo)
);

CREATE TABLE IF NOT EXISTS lotes (
  lote_id VARCHAR(20) PRIMARY KEY,
  producto VARCHAR(120) NOT NULL,
  kg DECIMAL(10,2) NOT NULL,
  cosecha DATE NOT NULL,
  estado ENUM('en_proceso', 'certificado', 'liberado') NOT NULL
);

CREATE TABLE IF NOT EXISTS productos (
  sku VARCHAR(20) PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  presentacion VARCHAR(40) NOT NULL,
  precio_mxn DECIMAL(10,2) NOT NULL,
  stock INT NOT NULL DEFAULT 0,
  descripcion TEXT NOT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS pedidos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cliente_id INT NOT NULL,
  canal ENUM('tienda_mx', 'tienda_ue', 'distribuidor', 'retail_mx') NOT NULL,
  envio_mxn DECIMAL(10,2) NOT NULL DEFAULT 0,
  total_mxn DECIMAL(12,2) NOT NULL,
  metodo_pago ENUM('tarjeta_simulada', 'transferencia') NOT NULL,
  direccion_envio VARCHAR(255) NOT NULL,
  estado ENUM('creado', 'pagado', 'en_lote', 'embarcado', 'facturado', 'cobrado') NOT NULL,
  lote_id VARCHAR(20) NULL,
  creado_en DATETIME NOT NULL,
  actualizado_en DATETIME NOT NULL,
  INDEX idx_estado (estado),
  INDEX idx_actualizado (actualizado_en),
  FOREIGN KEY (cliente_id) REFERENCES clientes (id),
  FOREIGN KEY (lote_id) REFERENCES lotes (lote_id)
);

CREATE TABLE IF NOT EXISTS pedido_items (
  pedido_id INT NOT NULL,
  sku VARCHAR(20) NOT NULL,
  cantidad INT NOT NULL,
  precio_unitario_mxn DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (pedido_id, sku),
  FOREIGN KEY (pedido_id) REFERENCES pedidos (id),
  FOREIGN KEY (sku) REFERENCES productos (sku)
);

-- Catálogo inicial. Los mismos productos y precios aparecen en org.json y en el sitio público.
INSERT IGNORE INTO productos (sku, nombre, presentacion, precio_mxn, stock, descripcion) VALUES
  ('KAB-MM-500', 'Miel multiflora orgánica', '500 g', 165.00, 420,
   'Miel de flores silvestres de la selva baja yucateca. Color ámbar claro y sabor suave. Frasco de vidrio con número de lote en la etiqueta.'),
  ('KAB-AZ-1K', 'Miel de azahar orgánica', '1 kg', 310.00, 180,
   'Cosecha de primavera de huertos de naranjo. Aroma floral intenso. Presentación familiar de 1 kg.'),
  ('KAB-PR-30', 'Propóleo en gotas', '30 ml', 240.00, 35,
   'Extracto hidroalcohólico de propóleo de nuestros apiarios, con gotero. Uso doméstico. Consulta a tu médico antes de usarlo.'),
  ('KAB-JC-100', 'Jabón de cera y propóleo', '100 g', 95.00, 0,
   'Jabón artesanal elaborado en lotes pequeños con cera de abeja y propóleo. Pieza de 100 g.');
