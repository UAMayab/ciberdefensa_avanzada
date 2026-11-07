#!/bin/bash
# Miniworkshop Bettercap — aprovisionamiento de atacante (Ubuntu 26.04).
# Un equipo que no debería estar en la LAN (un portátil conectado a una roseta
# libre, un dispositivo comprometido) con Bettercap instalado. Red aislada.
set -euo pipefail

LAB=/tmp/bc
say() { echo "[miniws-bettercap:atacante] $*"; }

# Aprovisionar necesita Internet (por la NAT de Vagrant). Si la VM ya estuvo
# aislada por lab-rutas, se restaura aquí de forma temporal; al terminar, el
# provisionador "always" (lab-rutas) vuelve a dejarla sin salida.
ip route replace default via 10.0.2.2 2>/dev/null || true
printf 'nameserver 10.0.2.3\n' > /etc/resolv.conf
export DEBIAN_FRONTEND=noninteractive

# Versión fijada, no la de los repositorios de Ubuntu (2.32/2.33, de 2022-2023):
# los módulos y la sintaxis de los episodios son los de 2.41. Se comprueban DOS
# hashes: el del zip (el que se observó al escribir el laboratorio) y el del
# binario (el que el propio proyecto publica en su .sha256).
BC_VERSION=v2.41.7
BC_ZIP_SHA256=9f8869d10253f4aed73d4aeabeb9ff54b678613bd27f94d5aa3599dfb82cffe0
BC_BIN_SHA256=c07db9028c76597e0e1baf5e50fbdeb63acbecf6b3511bb07f41feac625614ee

say "instalando paquetes"
apt-get update -qq
apt-get install -y -qq --no-install-recommends \
  libpcap0.8t64 libusb-1.0-0 libnetfilter-queue1 iptables \
  curl unzip ca-certificates tcpdump dnsutils iproute2 net-tools jq git >/dev/null

say "descargando Bettercap $BC_VERSION"
cd /tmp
curl -fsSL -o bettercap.zip \
  "https://github.com/bettercap/bettercap/releases/download/$BC_VERSION/bettercap_linux_amd64.zip"
echo "$BC_ZIP_SHA256  bettercap.zip" | sha256sum -c - >/dev/null
unzip -oq bettercap.zip bettercap
echo "$BC_BIN_SHA256  bettercap" | sha256sum -c - >/dev/null
install -m 0755 bettercap /usr/local/bin/bettercap
rm -f bettercap bettercap.zip
say "bettercap instalado: $(bettercap -version | head -1)"

say "caplets oficiales (caplets.update)"
bettercap -no-colors -eval "caplets.update; q" >/dev/null 2>&1 || say "AVISO: no se pudieron bajar los caplets (¿sin Internet?)"

say "materiales del atacante en /home/vagrant/lab"
install -d -o vagrant -g vagrant /home/vagrant/lab
cp -a "$LAB/atacante/." /home/vagrant/lab/ 2>/dev/null || true
chown -R vagrant:vagrant /home/vagrant/lab

# Red del laboratorio AL FINAL: a partir de aquí ya no hay DNS externo.
say "aislando el nodo: DNS y rutas sólo del laboratorio"
mkdir -p /usr/local/sbin
install -m 0755 "$LAB/lab-rutas" /usr/local/sbin/lab-rutas
cat > /etc/lab-red.conf <<'CONF'
LAB_GW=10.70.10.254
LAB_DNS=10.70.10.254
CONF
# systemd-resolved reintroduciría el DNS de la NAT: se apaga y lab-rutas
# escribe /etc/resolv.conf como fichero normal.
systemctl disable --now systemd-resolved >/dev/null 2>&1 || true
rm -f /etc/resolv.conf

say "listo"
