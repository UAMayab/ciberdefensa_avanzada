-- Catálogos y valores iniciales. Idempotente (ON DUPLICATE KEY UPDATE): reejecutarlo no toca
-- el trabajo del estudiante, solo restablece los catálogos al valor publicado.
USE grc;
SET NAMES utf8mb4;

-- Niveles de riesgo (NIST SP 800-30 Rev. 1, Tabla I-3) y su color en el dashboard.
INSERT INTO cat_nivel (id, nombre, color, color_texto) VALUES
  (1, 'Muy bajo', '#2e9e5b', '#ffffff'),
  (2, 'Bajo',     '#a6cf4a', '#1f2937'),
  (3, 'Moderado', '#f4c430', '#1f2937'),
  (4, 'Alto',     '#f08a24', '#1f2937'),
  (5, 'Muy alto', '#d64545', '#ffffff')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), color = VALUES(color), color_texto = VALUES(color_texto);

-- Escala de impacto (adaptada de la Tabla H-3).
INSERT INTO escala (tipo, valor, nombre, descripcion) VALUES
  ('impacto', 1, 'Muy bajo', 'Efecto insignificante en las operaciones, los activos, las personas u otras organizaciones.'),
  ('impacto', 2, 'Bajo',     'Efecto limitado: la operación continúa con una degradación notoria; daño o pérdida económica menor.'),
  ('impacto', 3, 'Moderado', 'Efecto serio: las funciones principales continúan, pero con efectividad muy reducida; daño o pérdida económica significativa.'),
  ('impacto', 4, 'Alto',     'Efecto severo: la organización no puede realizar una o más de sus funciones principales; daño mayor a los activos o pérdida económica mayor.'),
  ('impacto', 5, 'Muy alto', 'Múltiples efectos severos o catastróficos: la continuidad de la organización o la seguridad de las personas están en peligro.')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), descripcion = VALUES(descripcion);

-- Escala de probabilidad: que el evento ocurra Y cause un efecto adverso (Tablas G-3 y G-5).
INSERT INTO escala (tipo, valor, nombre, descripcion) VALUES
  ('probabilidad', 1, 'Muy baja', 'Muy improbable: ocurre menos de 1 vez cada 10 años.'),
  ('probabilidad', 2, 'Baja',     'Improbable: ocurre menos de 1 vez al año, pero más de 1 vez cada 10 años.'),
  ('probabilidad', 3, 'Moderada', 'Algo probable: ocurre entre 1 y 10 veces al año.'),
  ('probabilidad', 4, 'Alta',     'Muy probable: ocurre entre 10 y 100 veces al año.'),
  ('probabilidad', 5, 'Muy alta', 'Casi seguro: ocurre más de 100 veces al año o ya está ocurriendo.')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), descripcion = VALUES(descripcion);

-- Matriz de riesgo: NIST SP 800-30 Rev. 1, Tabla I-2 (probabilidad × impacto → nivel).
--                       impacto →  1 Muy bajo | 2 Bajo | 3 Moderado | 4 Alto | 5 Muy alto
--   probabilidad 5 Muy alta:          1          2         3           4        5
--   probabilidad 4 Alta:              1          2         3           4        5
--   probabilidad 3 Moderada:          1          2         3           3        4
--   probabilidad 2 Baja:              1          2         2           2        3
--   probabilidad 1 Muy baja:          1          1         1           2        2
INSERT INTO matriz_riesgo (probabilidad, impacto, nivel_id) VALUES
  (5,1,1),(5,2,2),(5,3,3),(5,4,4),(5,5,5),
  (4,1,1),(4,2,2),(4,3,3),(4,4,4),(4,5,5),
  (3,1,1),(3,2,2),(3,3,3),(3,4,3),(3,5,4),
  (2,1,1),(2,2,2),(2,3,2),(2,4,2),(2,5,3),
  (1,1,1),(1,2,1),(1,3,1),(1,4,2),(1,5,2)
ON DUPLICATE KEY UPDATE nivel_id = VALUES(nivel_id);

INSERT INTO cat_tipo_activo (id, nombre) VALUES
  (1,  'Servidor'),
  (2,  'Estación de trabajo'),
  (3,  'Aplicación / Software'),
  (4,  'Base de datos'),
  (5,  'Dispositivo de red'),
  (6,  'Dispositivo móvil'),
  (7,  'Dispositivo IoT / OT'),
  (8,  'Servicio en la nube'),
  (9,  'Información / Datos'),
  (10, 'Instalación física'),
  (11, 'Proceso de negocio'),
  (12, 'Personal clave'),
  (13, 'Proveedor / Tercero')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

-- Fuentes de amenaza (NIST SP 800-30 Rev. 1, Tabla D-2).
INSERT INTO cat_fuente_amenaza (id, nombre, descripcion) VALUES
  (1, 'Adversaria',  'Personas, grupos u organizaciones que buscan aprovechar la dependencia tecnológica de la organización: atacante externo, empleado malicioso, competidor, proveedor, Estado-nación.'),
  (2, 'Accidental',  'Errores de personas en el curso normal de su trabajo: un usuario o un administrador que se equivoca.'),
  (3, 'Estructural', 'Fallas de equipos, de software o de controles ambientales por desgaste, obsolescencia o agotamiento de recursos.'),
  (4, 'Ambiental',   'Desastres naturales y fallas de infraestructura externa: huracán, inundación, incendio, sismo, corte de energía o de telecomunicaciones.')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), descripcion = VALUES(descripcion);

-- Respuestas al riesgo (NIST SP 800-39 §3.3, a la que remite SP 800-30).
INSERT INTO cat_tratamiento (id, nombre, descripcion) VALUES
  (1, 'Mitigar',    'Reducir la probabilidad o el impacto con controles (parches, segmentación, respaldos, capacitación…). Es la respuesta más común.'),
  (2, 'Aceptar',    'Asumir el riesgo tal como está porque está dentro de la tolerancia de la organización o tratarlo cuesta más que su efecto. Requiere una decisión explícita y justificada.'),
  (3, 'Evitar',     'Eliminar la actividad o el activo que origina el riesgo (apagar el servicio, dejar de recolectar el dato).'),
  (4, 'Transferir', 'Pasar el costo del riesgo a un tercero mediante un seguro o un contrato. El tercero absorbe la pérdida; la responsabilidad ante el cliente sigue siendo tuya.'),
  (5, 'Compartir',  'Repartir la responsabilidad con otra parte: subcontratar un servicio gestionado, firmar un SLA, formar una alianza.')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), descripcion = VALUES(descripcion);

INSERT INTO cat_estado_riesgo (id, nombre, descripcion, vigente) VALUES
  (1, 'Abierto',        'Identificado y evaluado; todavía no se actúa.', 1),
  (2, 'En tratamiento', 'Hay un plan de tratamiento en ejecución.', 1),
  (3, 'Mitigado',       'El control ya se implementó; el impacto y la probabilidad registrados son el valor residual.', 1),
  (4, 'Aceptado',       'La dirección aceptó formalmente el riesgo.', 1),
  (5, 'Cerrado',        'Ya no aplica (el activo se retiró, la vulnerabilidad desapareció). No cuenta en el nivel global.', 0)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), descripcion = VALUES(descripcion), vigente = VALUES(vigente);

-- Fila única del perfil de la organización (vacía hasta que el estudiante la llena).
INSERT IGNORE INTO organizacion (id, nombre) VALUES (1, '');
