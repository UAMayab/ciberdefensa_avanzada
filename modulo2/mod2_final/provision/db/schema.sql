-- Sistema de Gobernanza y Gestión de Riesgo · Módulo II (proyecto final)
-- Esquema de la base `grc`. Idempotente: se puede ejecutar de nuevo (`vagrant provision`)
-- sin perder el trabajo del estudiante. Los catálogos se actualizan con ON DUPLICATE KEY.
--
-- Cadena de datos (una flecha = una llave foránea con ON DELETE CASCADE):
--   activos → vulnerabilidades → amenazas → riesgos
-- El par Activo-Vulnerabilidad es la fila de `vulnerabilidades`; la tupla
-- Activo-Vulnerabilidad-Amenaza es la fila de `amenazas`; cada tupla tiene como máximo un riesgo.

CREATE DATABASE IF NOT EXISTS grc CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE grc;
SET NAMES utf8mb4;

-- ---------------------------------------------------------------- catálogos

CREATE TABLE IF NOT EXISTS cat_nivel (            -- NIST SP 800-30, Tabla I-3 (cinco niveles)
  id          TINYINT UNSIGNED NOT NULL PRIMARY KEY,
  nombre      VARCHAR(20)  NOT NULL,
  color       CHAR(7)      NOT NULL,                -- fondo
  color_texto CHAR(7)      NOT NULL                 -- texto legible sobre ese fondo
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS escala (               -- definición de cada valor 1-5 de impacto y probabilidad
  tipo        ENUM('impacto','probabilidad') NOT NULL,
  valor       TINYINT UNSIGNED NOT NULL,
  nombre      VARCHAR(20)  NOT NULL,
  descripcion VARCHAR(400) NOT NULL,
  PRIMARY KEY (tipo, valor)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS matriz_riesgo (        -- NIST SP 800-30, Tabla I-2: probabilidad × impacto → nivel
  probabilidad TINYINT UNSIGNED NOT NULL,
  impacto      TINYINT UNSIGNED NOT NULL,
  nivel_id     TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY (probabilidad, impacto),
  FOREIGN KEY (nivel_id) REFERENCES cat_nivel (id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cat_tipo_activo (
  id     TINYINT UNSIGNED NOT NULL PRIMARY KEY,
  nombre VARCHAR(60) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cat_fuente_amenaza (   -- NIST SP 800-30, Tabla D-2 (taxonomía de fuentes)
  id          TINYINT UNSIGNED NOT NULL PRIMARY KEY,
  nombre      VARCHAR(30)  NOT NULL UNIQUE,
  descripcion VARCHAR(400) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cat_tratamiento (      -- NIST SP 800-39 §3.3: las cinco respuestas al riesgo
  id          TINYINT UNSIGNED NOT NULL PRIMARY KEY,
  nombre      VARCHAR(20)  NOT NULL UNIQUE,
  descripcion VARCHAR(400) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cat_estado_riesgo (
  id          TINYINT UNSIGNED NOT NULL PRIMARY KEY,
  nombre      VARCHAR(20)  NOT NULL UNIQUE,
  descripcion VARCHAR(200) NOT NULL,
  vigente     TINYINT(1)   NOT NULL DEFAULT 1       -- 0 = ya no cuenta en el nivel global (p. ej. Cerrado)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------- perfil de la organización

CREATE TABLE IF NOT EXISTS organizacion (         -- una sola fila (id = 1): contexto del análisis (NIST Paso 1)
  id               TINYINT UNSIGNED NOT NULL PRIMARY KEY DEFAULT 1,
  nombre           VARCHAR(150) NOT NULL DEFAULT '',
  sector           VARCHAR(100) NOT NULL DEFAULT '',
  objetivo_negocio TEXT NULL,
  contexto         TEXT NULL,
  alcance          TEXT NULL,
  evaluador        VARCHAR(150) NOT NULL DEFAULT '',
  tolerancia_nivel TINYINT UNSIGNED NOT NULL DEFAULT 3,   -- nivel máximo que la organización acepta sin tratar
  CONSTRAINT chk_org_unica CHECK (id = 1),
  CONSTRAINT chk_org_tol   CHECK (tolerancia_nivel BETWEEN 1 AND 5)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------- datos del estudiante

CREATE TABLE IF NOT EXISTS activos (
  id                  INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  fecha               DATE NOT NULL,
  tipo_id             TINYINT UNSIGNED NOT NULL,
  nombre              VARCHAR(150) NOT NULL,
  departamento        VARCHAR(100) NOT NULL,
  responsable         VARCHAR(150) NOT NULL,
  sistema_operativo   VARCHAR(100) NOT NULL,
  descripcion_funcion TEXT NOT NULL,
  participacion       ENUM('Directa','Indirecta') NOT NULL,
  -- Campos sugeridos (opcionales en la práctica; ver SUGERENCIAS.md):
  criticidad          ENUM('Baja','Media','Alta','Crítica') NOT NULL DEFAULT 'Media',
  clasificacion       ENUM('Pública','Interna','Confidencial','Restringida') NOT NULL DEFAULT 'Interna',
  ubicacion           VARCHAR(150) NOT NULL DEFAULT '',
  creado              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (tipo_id) REFERENCES cat_tipo_activo (id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS vulnerabilidades (     -- una fila = un par Activo-Vulnerabilidad
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  activo_id   INT UNSIGNED NOT NULL,
  descripcion TEXT NOT NULL,
  cvss        DECIMAL(3,1) NOT NULL,
  -- Campos sugeridos:
  cve         VARCHAR(20)  NOT NULL DEFAULT '',
  vector_cvss VARCHAR(120) NOT NULL DEFAULT '',
  creado      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (activo_id) REFERENCES activos (id) ON DELETE CASCADE,
  CONSTRAINT chk_cvss CHECK (cvss BETWEEN 0 AND 10)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS amenazas (             -- una fila = una tupla Activo-Vulnerabilidad-Amenaza
  id                INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  vulnerabilidad_id INT UNSIGNED NOT NULL,
  fuente_id         TINYINT UNSIGNED NOT NULL,
  nombre            VARCHAR(150) NOT NULL,
  descripcion       TEXT NOT NULL,
  creado            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (vulnerabilidad_id) REFERENCES vulnerabilidades (id) ON DELETE CASCADE,
  FOREIGN KEY (fuente_id) REFERENCES cat_fuente_amenaza (id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS riesgos (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  amenaza_id       INT UNSIGNED NOT NULL UNIQUE,    -- un riesgo por tupla
  impacto          TINYINT UNSIGNED NOT NULL,
  probabilidad     TINYINT UNSIGNED NOT NULL,
  tratamiento_id   TINYINT UNSIGNED NOT NULL,
  justificacion    TEXT NOT NULL,
  estado_id        TINYINT UNSIGNED NOT NULL,
  -- Campos sugeridos:
  responsable      VARCHAR(150) NOT NULL DEFAULT '',
  fecha_objetivo   DATE NULL,
  control_propuesto TEXT NULL,
  creado           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (amenaza_id)     REFERENCES amenazas (id) ON DELETE CASCADE,
  FOREIGN KEY (tratamiento_id) REFERENCES cat_tratamiento (id),
  FOREIGN KEY (estado_id)      REFERENCES cat_estado_riesgo (id),
  CONSTRAINT chk_impacto CHECK (impacto BETWEEN 1 AND 5),
  CONSTRAINT chk_prob    CHECK (probabilidad BETWEEN 1 AND 5)
) ENGINE=InnoDB;

-- Vista plana: un renglón por riesgo con toda su cadena Activo → Vulnerabilidad → Amenaza.
-- La usan el dashboard, el heatmap, el reporte y el CSV.
CREATE OR REPLACE VIEW v_riesgos AS
SELECT r.id, r.amenaza_id, r.impacto, r.probabilidad,
       m.nivel_id AS nivel, n.nombre AS nivel_nombre,
       r.tratamiento_id, t.nombre AS tratamiento, r.justificacion,
       r.estado_id, e.nombre AS estado, e.vigente,
       r.responsable, r.fecha_objetivo, r.control_propuesto,
       am.nombre AS amenaza, am.fuente_id, f.nombre AS fuente,
       v.id AS vulnerabilidad_id, v.descripcion AS vulnerabilidad, v.cvss,
       a.id AS activo_id, a.nombre AS activo, ta.nombre AS tipo_activo
  FROM riesgos r
  JOIN matriz_riesgo m ON m.probabilidad = r.probabilidad AND m.impacto = r.impacto
  JOIN cat_nivel n ON n.id = m.nivel_id
  JOIN cat_tratamiento t ON t.id = r.tratamiento_id
  JOIN cat_estado_riesgo e ON e.id = r.estado_id
  JOIN amenazas am ON am.id = r.amenaza_id
  JOIN cat_fuente_amenaza f ON f.id = am.fuente_id
  JOIN vulnerabilidades v ON v.id = am.vulnerabilidad_id
  JOIN activos a ON a.id = v.activo_id
  JOIN cat_tipo_activo ta ON ta.id = a.tipo_id;
