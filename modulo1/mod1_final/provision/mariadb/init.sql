-- Entregable final — Módulo I · Base de datos del objetivo "Aseguradora Delphos".
--
-- TODOS LOS DATOS SON FICTICIOS. Los CURP, RFC, correos y teléfonos están
-- inventados y tienen formato plausible sólo para que el ejercicio de
-- enumeración y el análisis de impacto (LFPDPPP/GDPR) sean realistas.
-- Ningún dato corresponde a una persona real.

-- Acceso remoto de root con contraseña débil (el hallazgo que Metasploit
-- confirma con auxiliary/scanner/mysql/mysql_login).
CREATE USER IF NOT EXISTS 'root'@'%' IDENTIFIED BY 'root';
GRANT ALL PRIVILEGES ON *.* TO 'root'@'%' WITH GRANT OPTION;

-- Usuario de aplicación cuya contraseña se filtra en el sitio web
-- (provision/web/backups/config.old): corrobora el hallazgo por dos vías.
CREATE USER IF NOT EXISTS 'delphos_app'@'%' IDENTIFIED BY 'Delphos2019!';
GRANT ALL PRIVILEGES ON delphos.* TO 'delphos_app'@'%';
FLUSH PRIVILEGES;

CREATE DATABASE IF NOT EXISTS delphos CHARACTER SET utf8mb4;
USE delphos;

CREATE TABLE clientes (
  id           INT PRIMARY KEY AUTO_INCREMENT,
  nombre       VARCHAR(120),
  curp         CHAR(18),
  rfc          VARCHAR(13),
  email        VARCHAR(120),
  telefono     VARCHAR(20),
  tipo_poliza  VARCHAR(40),
  prima_anual  DECIMAL(10,2),
  tarjeta_ref  VARCHAR(25)
);

INSERT INTO clientes (nombre, curp, rfc, email, telefono, tipo_poliza, prima_anual, tarjeta_ref) VALUES
('Ana María Cob Uc',           'COUA850312MYNBCN03', 'COUA850312H19', 'ana.cob@corinto.example',      '999-214-7781', 'Auto Amplia',        14890.00, '4111-xxxx-xxxx-0312'),
('Jorge Pech Balam',           'PEBJ790921HYNCLR08', 'PEBJ790921K21', 'jpech79@corinto.example',      '999-331-5540', 'Gastos Médicos',     31250.00, '5500-xxxx-xxxx-0921'),
('Lucía Fernanda May Tzuc',    'MATL920604MYNYZC01', 'MATL920604P44', 'lmay@corinto.example',         '998-120-9983', 'Vida',               9800.00,  '4111-xxxx-xxxx-0604'),
('Roberto Canul Dzib',         'CADR681117HYNNZB09', 'CADR681117J09', 'rcanul68@corinto.example',     '999-445-1120', 'Hogar',              5400.00,  '5200-xxxx-xxxx-1117'),
('María José Interián Poot',   'IEPM880230MYNNTR05', 'IEPM880230L77', 'mjinterian@corinto.example',   '999-778-3321', 'Auto Limitada',      7650.00,  '4111-xxxx-xxxx-0230'),
('Daniel Euán Chi',            'EUCD950718HYNNHN02', 'EUCD950718M12', 'deuan@corinto.example',        '984-115-6600', 'Gastos Médicos',     28900.00, '5500-xxxx-xxxx-0718'),
('Patricia Sosa Gamboa',       'SOGP730509MYCSMT06', 'SOGP730509N33', 'psosa73@corinto.example',      '999-210-8847', 'Vida',               12300.00, '4111-xxxx-xxxx-0509'),
('Víctor Manuel Ake Chan',     'AECV840126HYNKHC04', 'AECV840126Q88', 'vake@corinto.example',         '999-556-2290', 'Auto Amplia',        16740.00, '5200-xxxx-xxxx-0126'),
('Gabriela Uc Mena',           'UCMG910803MYNCNB07', 'UCMG910803R10', 'guc@corinto.example',          '999-889-4412', 'Hogar',              6100.00,  '4111-xxxx-xxxx-0803'),
('Fernando Koh Castillo',      'KOCF770214HYNHSR00', 'KOCF770214S55', 'fkoh77@corinto.example',       '999-334-7709', 'Gastos Médicos',     34500.00, '5500-xxxx-xxxx-0214'),
('Sandra Noh Puc',             'NOPS860927MYNHCN08', 'NOPS860927T21', 'snoh@corinto.example',         '998-442-1180', 'Vida',               10400.00, '4111-xxxx-xxxx-0927'),
('Carlos Enrique Dzul Ek',     'DUEC751105HYNZKR03', 'DUEC751105V09', 'cdzul75@corinto.example',      '999-667-5530', 'Auto Limitada',      8120.00,  '5200-xxxx-xxxx-1105'),
('Mariana Chim Tun',           'CITM930416MYNHNR06', 'CITM930416W44', 'mchim@corinto.example',        '999-118-2277', 'Hogar',              5890.00,  '4111-xxxx-xxxx-0416'),
('Alejandro Baas Yam',         'BAYA820708HYNSML07', 'BAYA820708X12', 'abaas82@corinto.example',      '984-220-9931', 'Gastos Médicos',     29750.00, '5500-xxxx-xxxx-0708'),
('Rosa Elena Cen Moo',         'CEMR900301MYNNXS02', 'CEMR900301Y31', 'rcen@corinto.example',         '999-775-6612', 'Vida',               11200.00, '4111-xxxx-xxxx-0301'),
('Hugo Alberto Tec Caamal',    'TECH710619HYNCML09', 'TECH710619Z08', 'htec71@corinto.example',       '999-443-0098', 'Auto Amplia',        15980.00, '5200-xxxx-xxxx-0619'),
('Diana Laura Poot Ku',        'POKD940722MYNTXN05', 'POKD940722A77', 'dpoot@corinto.example',        '998-331-7742', 'Hogar',              6450.00,  '4111-xxxx-xxxx-0722'),
('Emilio Góngora Rejón',       'GORE800113HYCNJM01', 'GORE800113B20', 'egongora80@corinto.example',   '999-556-8820', 'Gastos Médicos',     33100.00, '5500-xxxx-xxxx-0113'),
('Claudia Beatriz Uicab Can',  'UICC871204MYNCNL04', 'UICC871204C66', 'cuicab@corinto.example',       '999-210-3391', 'Vida',               9950.00,  '4111-xxxx-xxxx-1204'),
('Ricardo Novelo Pat',         'NOPR760928HYCVTC07', 'NOPR760928D14', 'rnovelo76@corinto.example',    '999-889-1123', 'Auto Limitada',      7980.00,  '5200-xxxx-xxxx-0928');

-- Credenciales de empleados del portal interno (hashes MD5 sin sal: otro hallazgo).
CREATE TABLE usuarios_portal (
  id       INT PRIMARY KEY AUTO_INCREMENT,
  usuario  VARCHAR(40),
  rol      VARCHAR(30),
  pass_md5 CHAR(32)
);
INSERT INTO usuarios_portal (usuario, rol, pass_md5) VALUES
('opalomo', 'sysadmin',   '5f4dcc3b5aa765d61d8327deb882cf99'),  -- "password"
('mtun',    'ti',         '25d55ad283aa400af464c76d713c07ad'),  -- "12345678"
('rancona', 'direccion',  'e10adc3949ba59abbe56e057f20f883e'),  -- "123456"
('svaldez', 'suscripcion','827ccb0eea8a706c4c34a16891f84e7b');  -- "12345"
