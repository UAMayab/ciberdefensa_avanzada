#!/bin/sh
# Miniworkshop Bettercap — aprovisionamiento de victima.
# La estación de trabajo de Ana Canul (Cenote Logística, empresa FICTICIA) y un
# «usuario simulado» que trabaja solo: así hay tráfico real con credenciales
# sin que el estudiante tenga que teclearlo.
set -eu

LAB=/tmp/bc
say() { echo "[miniws-bettercap:victima] $*"; }

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
apk add --no-cache curl bind-tools tcpdump iproute2 ca-certificates openssl >/dev/null

mkdir -p /usr/local/sbin
install -m 0755 "$LAB/lab-rutas" /usr/local/sbin/lab-rutas
cat > /etc/lab-red.conf <<'CONF'
LAB_GW=10.70.10.254
LAB_DNS=10.70.10.254
CONF
mkdir -p /etc/udhcpc
grep -q '^RESOLV_CONF=no' /etc/udhcpc/udhcpc.conf 2>/dev/null || echo 'RESOLV_CONF=no' >> /etc/udhcpc/udhcpc.conf

say "instalando la CA corporativa en el almacén de confianza"
# Es lo que hace una empresa real con su CA interna: por eso https://pagos.corp.lab
# se valida sin -k. Es también por lo que Bettercap NO puede presentar un
# certificado propio sin que el cliente proteste (Episodio 3).
mkdir -p /usr/local/share/ca-certificates
install -m 0644 "$LAB/tls/ca.crt" /usr/local/share/ca-certificates/cenote-ca.crt
update-ca-certificates >/dev/null 2>&1

say "instalando el usuario simulado"
install -m 0755 "$LAB/deteccion/usuario-simulado" /usr/local/bin/usuario-simulado
install -m 0755 "$LAB/deteccion/arp-guard"        /usr/local/bin/arp-guard
install -m 0755 "$LAB/deteccion/integridad-web"   /usr/local/bin/integridad-web
install -m 0755 "$LAB/deteccion/dns-canario"      /usr/local/bin/dns-canario
cat > /etc/usuario-simulado.conf <<'CONF'
# Miniworkshop Bettercap — credenciales con las que "trabaja" Ana.
# Esta es la VERDAD DE REFERENCIA del laboratorio: en el Episodio 1 la usas para
# comprobar que lo que capturaste con Bettercap es exactamente lo que ella tecleó.
INTRANET_USER=ana.canul
INTRANET_PASS=Chaac-Lluvia-2026
ADMIN_USER=soporte
ADMIN_PASS='Kukulkan#09'
FTP_USER=reportes
FTP_PASS=Xunaan-Kab-17
PAGOS_USER=tesoreria
PAGOS_PASS=Pagos-Cenote-31
# Segundos entre ciclos de trabajo.
INTERVALO=30
# yes/no: si curl recuerda HSTS entre ciclos (Episodio 3, mitigación).
USAR_HSTS=no
CONF

cat > /etc/init.d/usuario-simulado <<'INIT'
#!/sbin/openrc-run
name="usuario-simulado"
description="Ana Canul trabajando (tráfico de laboratorio)"
command="/usr/local/bin/usuario-simulado"
command_args="bucle"
command_background=true
pidfile="/run/usuario-simulado.pid"
output_log="/var/log/usuario-simulado.log"
error_log="/var/log/usuario-simulado.log"
depend() { need net; }
INIT
chmod +x /etc/init.d/usuario-simulado
rc-update add usuario-simulado default >/dev/null 2>&1 || true
rc-service usuario-simulado restart >/dev/null 2>&1 || rc-service usuario-simulado start

say "listo"
