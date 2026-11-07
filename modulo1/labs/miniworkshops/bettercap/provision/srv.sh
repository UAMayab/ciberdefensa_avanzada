#!/bin/sh
# Miniworkshop Bettercap — aprovisionamiento de srv.
# Los servidores corporativos de «Cenote Logística» (empresa FICTICIA), en la
# DMZ 10.70.20.0/24: intranet por HTTP en claro, portal de pagos por HTTPS con
# HSTS, y un FTP en claro. Lo "inseguro" aquí es lo que de verdad existe en
# redes corporativas: protocolos heredados que nadie ha migrado todavía.
set -eu

LAB=/tmp/bc
say() { echo "[miniws-bettercap:srv] $*"; }

# Aprovisionar necesita Internet (por la NAT de Vagrant). Si la VM ya estuvo
# aislada por lab-rutas, se restaura aquí de forma temporal; al terminar, el
# provisionador "always" (lab-rutas) vuelve a dejarla sin salida.
ip route replace default via 10.0.2.2 2>/dev/null || true
printf 'nameserver 10.0.2.3\n' > /etc/resolv.conf

say "fijando repositorios a Alpine v3.24"
cat > /etc/apk/repositories <<'REPOS'
https://dl-cdn.alpinelinux.org/alpine/v3.24/main
https://dl-cdn.alpinelinux.org/alpine/v3.24/community
REPOS
apk update >/dev/null

say "instalando paquetes"
apk add --no-cache nginx vsftpd python3 openssl curl iproute2 tcpdump >/dev/null

mkdir -p /usr/local/sbin
install -m 0755 "$LAB/lab-rutas" /usr/local/sbin/lab-rutas
cat > /etc/lab-red.conf <<'CONF'
LAB_GW=10.70.20.254
LAB_DNS=10.70.10.254
CONF
mkdir -p /etc/udhcpc
grep -q '^RESOLV_CONF=no' /etc/udhcpc/udhcpc.conf 2>/dev/null || echo 'RESOLV_CONF=no' >> /etc/udhcpc/udhcpc.conf

# ------------------------------------------------------- intranet y pagos
say "nginx: intranet (HTTP) y pagos (HTTPS + HSTS)"
mkdir -p /var/www/intranet /var/www/pagos /opt/intranet /etc/nginx/tls
cp -a "$LAB/site/intranet/." /var/www/intranet/
cp -a "$LAB/site/pagos/."    /var/www/pagos/
install -m 0644 "$LAB/tls/srv.crt" /etc/nginx/tls/srv.crt
install -m 0640 "$LAB/tls/srv.key" /etc/nginx/tls/srv.key
chgrp nginx /etc/nginx/tls/srv.key
chown -R nginx:nginx /var/www/intranet /var/www/pagos 2>/dev/null || true

# Panel de soporte con HTTP Basic: soporte / Kukulkan#09 (en claro, a propósito).
printf 'soporte:%s\n' "$(openssl passwd -apr1 'Kukulkan#09')" > /etc/nginx/lab.htpasswd
chmod 0644 /etc/nginx/lab.htpasswd

rm -f /etc/nginx/http.d/default.conf
cp "$LAB/site/nginx-lab.conf" /etc/nginx/http.d/lab.conf

# La aplicación de login (Python, sólo en 127.0.0.1:8081, detrás de nginx).
install -m 0755 "$LAB/site/app.py" /opt/intranet/app.py
cat > /etc/init.d/intranet-app <<'INIT'
#!/sbin/openrc-run
name="intranet-app"
description="Login de la intranet y del portal de pagos (laboratorio)"
command="/usr/bin/python3"
command_args="/opt/intranet/app.py"
command_background=true
pidfile="/run/intranet-app.pid"
output_log="/var/log/intranet-app.log"
error_log="/var/log/intranet-app.log"
depend() { need net; before nginx; }
INIT
chmod +x /etc/init.d/intranet-app
rc-update add intranet-app default >/dev/null 2>&1 || true
rc-update add nginx default >/dev/null 2>&1 || true
rc-service intranet-app restart >/dev/null 2>&1 || rc-service intranet-app start
nginx -t
rc-service nginx restart >/dev/null 2>&1 || rc-service nginx start

# --------------------------------------------------------------- FTP (21)
say "vsftpd: FTP en claro con un usuario local"
adduser -D -h /srv/ftp/reportes -s /sbin/nologin -g "Reportes de flota" reportes 2>/dev/null || true
echo "reportes:Xunaan-Kab-17" | chpasswd
mkdir -p /srv/ftp/reportes
cp "$LAB/site/ftp/"* /srv/ftp/reportes/
chown -R reportes:reportes /srv/ftp/reportes
# vsftpd exige que el shell del usuario esté en /etc/shells para PAM.
grep -q '^/sbin/nologin$' /etc/shells || echo /sbin/nologin >> /etc/shells
cat > /etc/vsftpd/vsftpd.conf <<'FTP'
# Miniworkshop Bettercap — vsftpd de srv. FTP SIN cifrar, a propósito:
# usuario y contraseña viajan en claro por el cable.
listen=YES
listen_ipv6=NO
anonymous_enable=NO
local_enable=YES
write_enable=YES
chroot_local_user=YES
allow_writeable_chroot=YES
seccomp_sandbox=NO
pasv_enable=YES
pasv_min_port=30000
pasv_max_port=30010
ftpd_banner=Cenote Logistica - Reportes de flota (sistema ficticio de laboratorio)
FTP
rc-update add vsftpd default >/dev/null 2>&1 || true
rc-service vsftpd restart >/dev/null 2>&1 || rc-service vsftpd start

say "listo"
