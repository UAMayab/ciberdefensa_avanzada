#!/bin/sh
# Miniworkshop Nmap — aprovisionamiento de nmap-target.
# Levanta los siete servicios que se escanean en los Episodios 1, 2 y 3, y el
# cortafuegos que produce los cuatro estados de puerto. Ver README.md.
#
# TODO lo inseguro de aquí es deliberado y está documentado. Red aislada.
set -eu

LAB=/tmp/nmap-lab
say() { echo "[miniws-nmap] $*"; }

# ---------------------------------------------------------------- repos
# Se fija la rama v3.19 a propósito: así las versiones que el estudiante ve en
# `nmap -sV` son las mismas que aparecen escritas en los episodios, hoy y
# dentro de un año.
say "fijando repositorios a Alpine v3.19"
cat > /etc/apk/repositories <<'EOF'
https://dl-cdn.alpinelinux.org/alpine/v3.19/main
https://dl-cdn.alpinelinux.org/alpine/v3.19/community
EOF
apk update >/dev/null

say "instalando paquetes"
apk add --no-cache \
  nginx vsftpd mariadb mariadb-client redis net-snmp net-snmp-tools \
  openssh openssh-server nftables tcpdump openssl bash iproute2 >/dev/null

# ------------------------------------------------------- 1. SSH (22/tcp)
# Se reactivan algoritmos heredados que OpenSSH 9.6 trae compilados pero
# desactivados. Van AL PRINCIPIO de sshd_config porque en este fichero gana la
# primera aparición de cada palabra clave, no la última.
say "1/7 sshd: reactivando algoritmos heredados"
if ! grep -q "miniws-nmap" /etc/ssh/sshd_config; then
  { echo "# --- miniws-nmap: bloque del laboratorio (ver provision/sshd_legacy.conf) ---"
    cat "$LAB/sshd_legacy.conf"
    echo "# --- fin del bloque del laboratorio ---"
    cat /etc/ssh/sshd_config
  } > /etc/ssh/sshd_config.new
  mv /etc/ssh/sshd_config.new /etc/ssh/sshd_config
fi

# Cuenta con la MISMA contraseña que se filtra en /.git/config del servidor web.
# La reutilización de credenciales es el puente del Episodio 3: http-git
# entrega la contraseña y ssh-brute la confirma.
adduser -D -g "Soporte TI" soporte 2>/dev/null || true
echo "soporte:FerryProgreso17" | chpasswd
rc-update add sshd default >/dev/null 2>&1 || true

# ------------------------------------------------------- 2. FTP (21/tcp)
say "2/7 vsftpd: anónimo activado y banner falsificado"
mkdir -p /srv/ftp/entrada
cp "$LAB/site/www/uploads/tarifario-2026.txt" /srv/ftp/ 2>/dev/null || true
chown root:root /srv/ftp && chmod 0555 /srv/ftp
chown ftp:ftp /srv/ftp/entrada && chmod 0777 /srv/ftp/entrada
cat > /etc/vsftpd/vsftpd.conf <<'EOF'
# Miniworkshop Nmap — vsftpd de nmap-target (21/tcp).
# Dos hallazgos plantados:
#   * login anónimo con carpeta de subida escribible  -> ftp-anon
#   * banner que MIENTE sobre la versión              -> la lección del Ep. 3
listen=YES
listen_ipv6=NO
anonymous_enable=YES
anon_root=/srv/ftp
local_enable=NO
write_enable=YES
anon_upload_enable=YES
anon_mkdir_write_enable=YES
anon_umask=022
dirmessage_enable=YES
seccomp_sandbox=NO
pasv_min_port=30000
pasv_max_port=30010

# Aquí está la trampa del Episodio 3. El servidor es vsftpd 3.0.5 (parcheado);
# el banner dice 2.3.4, la versión que en 2011 se distribuyó con una puerta
# trasera. `nmap -sV` se cree el banner. El script que COMPRUEBA, no.
ftpd_banner=(vsFTPd 2.3.4)
EOF
rc-update add vsftpd default >/dev/null 2>&1 || true

# ------------------------------------------- 3 y 4. HTTP/HTTPS (80,443/tcp)
say "3/7 nginx: sitio, artefactos filtrados y basic-auth"
mkdir -p /var/www/lab
cp -a "$LAB/site/www/." /var/www/lab/
# Los dos artefactos «ocultos» viajan en el repositorio sin el punto inicial:
# Git no versiona un directorio .git anidado, y .env está en el .gitignore de
# la raíz. Aquí recuperan su nombre real. Ver site/ocultos/LEEME.md.
cp    "$LAB/site/ocultos/env" /var/www/lab/.env
rm -rf /var/www/lab/.git
cp -a "$LAB/site/ocultos/git" /var/www/lab/.git
chown -R nginx:nginx /var/www/lab 2>/dev/null || true
# admin / letmein  — las mismas credenciales que se filtran en config.php.bak.
# Se genera con openssl para no depender del paquete apache2-utils.
printf 'admin:%s\n' "$(openssl passwd -apr1 letmein)" > /etc/nginx/lab.htpasswd
chmod 0644 /etc/nginx/lab.htpasswd
rm -f /etc/nginx/http.d/default.conf
cp "$LAB/site/nginx-lab.conf" /etc/nginx/http.d/lab.conf

say "4/7 TLS deliberadamente débil"
# OpenSSL 3 rechaza por defecto RSA-1024, SHA-1 y TLS 1.0. Se baja el nivel de
# seguridad del sistema para que el laboratorio pueda ser malo a propósito.
if ! grep -q "miniws-nmap" /etc/ssl/openssl.cnf; then
  cat >> /etc/ssl/openssl.cnf <<'EOF'

# --- miniws-nmap: nivel de seguridad rebajado para el laboratorio ---
# Sin esto, OpenSSL 3 se niega a cargar un certificado RSA-1024 firmado con
# SHA-1 y a hablar TLS 1.0. NUNCA en un sistema real.
[openssl_init]
ssl_conf = ssl_sect
[ssl_sect]
system_default = system_default_sect
[system_default_sect]
MinProtocol  = TLSv1
CipherString = DEFAULT:@SECLEVEL=0
Options      = UnsafeLegacyRenegotiation
EOF
fi
sh "$LAB/tls/gen-weak-cert.sh"
rc-update add nginx default >/dev/null 2>&1 || true

# --------------------------------------------------- 5. MariaDB (3306/tcp)
say "5/7 mariadb: root sin contraseña, escuchando en la red"
cat > /etc/my.cnf.d/99-lab.cnf <<'EOF'
# Miniworkshop Nmap — MariaDB de nmap-target (3306/tcp).
# Alpine la deja en skip-networking (solo socket local). Aquí se abre a la red
# a propósito: sin eso, 3306 no aparecería en ningún escaneo.
[mysqld]
skip-networking=0
skip-name-resolve=1
bind-address=0.0.0.0
port=3306
EOF
# Por si la configuración base trae la directiva suelta sin valor.
sed -i 's/^skip-networking$/skip-networking=0/' /etc/my.cnf.d/*.cnf 2>/dev/null || true

if [ ! -d /var/lib/mysql/mysql ]; then
  /etc/init.d/mariadb setup >/dev/null 2>&1 || mariadb-install-db --user=mysql --datadir=/var/lib/mysql >/dev/null 2>&1
fi
rc-update add mariadb default >/dev/null 2>&1 || true
rc-service mariadb start >/dev/null 2>&1 || true

# Espera activa: el socket tarda un par de segundos en aparecer.
i=0; while [ $i -lt 30 ] && ! mariadb -uroot -e "SELECT 1" >/dev/null 2>&1; do i=$((i+1)); sleep 1; done

# root@'%' SIN contraseña y con autenticación por contraseña (no unix_socket),
# que es lo que mysql-empty-password sabe probar.
mariadb -uroot <<'EOF' || true
CREATE DATABASE IF NOT EXISTS aurora_rastreo;
USE aurora_rastreo;
CREATE TABLE IF NOT EXISTS embarques (
  id INT PRIMARY KEY AUTO_INCREMENT,
  contenedor VARCHAR(16), consignatario VARCHAR(80), estatus VARCHAR(24));
INSERT IGNORE INTO embarques (id, contenedor, consignatario, estatus) VALUES
  (1,'MSCU4417203','Ferreteria del Mayab SA','en transito'),
  (2,'TCLU9930118','Agroexportadora Sisal','en aduana'),
  (3,'HLXU2276554','Conservas del Golfo','entregado');
CREATE USER IF NOT EXISTS 'root'@'%' IDENTIFIED VIA mysql_native_password USING '';
GRANT ALL PRIVILEGES ON *.* TO 'root'@'%' WITH GRANT OPTION;
-- Cuenta ANÓNIMA con contraseña vacía. MySQL la traía de fábrica durante años
-- y sigue apareciendo en instalaciones heredadas. Aquí cumple además una
-- función técnica: mysql-empty-password prueba el usuario anónimo ANTES que
-- root, y si MariaDB lo rechaza, la librería mysql.lua de Nmap 7.94 revienta
-- al parsear el paquete de error (se ve en el Episodio 3).
CREATE USER IF NOT EXISTS ''@'%' IDENTIFIED VIA mysql_native_password USING '';
GRANT SELECT ON aurora_rastreo.* TO ''@'%';
CREATE USER IF NOT EXISTS 'aurora_app'@'%' IDENTIFIED BY 'aurora2024';
GRANT SELECT ON aurora_rastreo.* TO 'aurora_app'@'%';
FLUSH PRIVILEGES;
EOF

# ----------------------------------------------------- 6. Redis (16379/tcp)
say "6/7 redis: sin autenticación, en el puerto 16379"
cat > /etc/redis.conf <<'EOF'
# Miniworkshop Nmap — Redis de nmap-target.
# Dos hallazgos: sin autenticación, y escondido en un puerto NO estándar.
# Lo segundo es el argumento entero a favor de `-p-` (Episodio 1) y de
# `-sV --version-all` (Episodio 2): en un escaneo por defecto es invisible.
bind 0.0.0.0
port 16379
protected-mode no
daemonize no
save ""
dir /var/lib/redis
logfile /var/log/redis/redis.log
EOF
mkdir -p /var/lib/redis /var/log/redis
chown -R redis:redis /var/lib/redis /var/log/redis 2>/dev/null || true
rc-update add redis default >/dev/null 2>&1 || true

# ------------------------------------------------------ 7. SNMP (161/udp)
say "7/7 snmpd: community public"
cp "$LAB/snmpd.conf" /etc/snmp/snmpd.conf
rc-update add snmpd default >/dev/null 2>&1 || true

# --------------------------------------------------------- cortafuegos
say "nftables: estados filtered (drop) y filtered (reject)"
cp "$LAB/nftables.conf" /etc/nftables.nft
rc-update add nftables default >/dev/null 2>&1 || true

# ------------------------------------------------------------- arranque
say "arrancando servicios"
for s in nginx vsftpd redis snmpd nftables; do
  rc-service "$s" restart >/dev/null 2>&1 || say "AVISO: $s no arrancó"
done
# sshd al final y con reload: un restart podría cortar esta misma sesión.
rc-service sshd reload >/dev/null 2>&1 || rc-service sshd restart >/dev/null 2>&1 || true

sleep 2
say "--- puertos TCP a la escucha ---"
ss -tlnp 2>/dev/null | awk 'NR==1 || /LISTEN/'
say "--- puertos UDP a la escucha ---"
ss -ulnp 2>/dev/null | head -10
say "nmap-target: aprovisionamiento completo"
