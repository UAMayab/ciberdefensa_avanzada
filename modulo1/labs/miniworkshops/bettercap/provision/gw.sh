#!/bin/sh
# Miniworkshop Bettercap — aprovisionamiento de gw.
# Router entre la LAN (10.70.10.0/24, eth1) y la DMZ (10.70.20.0/24, eth2) y
# servidor DNS del laboratorio. Es la máquina que el atacante va a suplantar.
set -eu

LAB=/tmp/bc
say() { echo "[miniws-bettercap:gw] $*"; }

# Aprovisionar necesita Internet (por la NAT de Vagrant). Si la VM ya estuvo
# aislada por lab-rutas, se restaura aquí de forma temporal; al terminar, el
# provisionador "always" (lab-rutas) vuelve a dejarla sin salida.
ip route replace default via 10.0.2.2 2>/dev/null || true
printf 'nameserver 10.0.2.3\n' > /etc/resolv.conf

# Se fija la rama v3.24 a propósito: las versiones que se ven en los episodios
# son las mismas hoy y dentro de un año.
say "fijando repositorios a Alpine v3.24"
cat > /etc/apk/repositories <<'REPOS'
https://dl-cdn.alpinelinux.org/alpine/v3.24/main
https://dl-cdn.alpinelinux.org/alpine/v3.24/community
REPOS
apk update >/dev/null

say "instalando paquetes"
apk add --no-cache dnsmasq iproute2 tcpdump >/dev/null

mkdir -p /usr/local/sbin
install -m 0755 "$LAB/lab-rutas" /usr/local/sbin/lab-rutas
cat > /etc/lab-red.conf <<'CONF'
LAB_GW=
LAB_DNS=10.70.10.254
CONF
# udhcpc reescribiría /etc/resolv.conf con el DNS de la NAT en cada renovación.
mkdir -p /etc/udhcpc
grep -q '^RESOLV_CONF=no' /etc/udhcpc/udhcpc.conf 2>/dev/null || echo 'RESOLV_CONF=no' >> /etc/udhcpc/udhcpc.conf

say "reenvío IPv4 activado (esto es lo que convierte a gw en router)"
echo 'net.ipv4.ip_forward = 1' > /etc/sysctl.d/90-lab.conf
sysctl -w net.ipv4.ip_forward=1 >/dev/null

say "dnsmasq: DNS autoritativo de corp.lab, con registro de consultas"
cat > /etc/dnsmasq.conf <<'DNS'
# Miniworkshop Bettercap — DNS del laboratorio (sólo en la LAN, eth1).
# No reenvía a ningún DNS externo: lo que no está aquí es NXDOMAIN.
interface=eth1
bind-interfaces
no-resolv
no-hosts
local=/corp.lab/
domain=corp.lab
cache-size=0

# Los nombres de «Cenote Logística» (empresa FICTICIA). Todos viven en srv.
host-record=intranet.corp.lab,10.70.20.10
host-record=pagos.corp.lab,10.70.20.10
host-record=ftp.corp.lab,10.70.20.10
host-record=gw.corp.lab,10.70.10.254

# Registro de cada consulta: es la evidencia del lado del servidor en el Ep. 2.
log-queries
log-facility=/var/log/dnsmasq.log
DNS
touch /var/log/dnsmasq.log
chown dnsmasq /var/log/dnsmasq.log 2>/dev/null || true
rc-update add dnsmasq default >/dev/null 2>&1 || true
rc-service dnsmasq restart >/dev/null 2>&1 || rc-service dnsmasq start

say "listo"
