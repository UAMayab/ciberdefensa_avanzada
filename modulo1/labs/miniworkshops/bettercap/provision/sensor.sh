#!/bin/sh
# Miniworkshop Bettercap — aprovisionamiento de sensor (borrador).
set -eu
LAB=/tmp/bc
say() { echo "[miniws-bettercap:sensor] $*"; }
ip route replace default via 10.0.2.2 2>/dev/null || true
printf 'nameserver 10.0.2.3\n' > /etc/resolv.conf
cat > /etc/apk/repositories <<'REPOS'
https://dl-cdn.alpinelinux.org/alpine/v3.24/main
https://dl-cdn.alpinelinux.org/alpine/v3.24/community
REPOS
apk update >/dev/null
say "instalando paquetes"
apk add --no-cache arpwatch suricata tshark python3 tcpdump iproute2 jq curl >/dev/null
mkdir -p /usr/local/sbin
install -m 0755 "$LAB/lab-rutas" /usr/local/sbin/lab-rutas
cat > /etc/lab-red.conf <<'CONF'
LAB_GW=10.70.10.254
LAB_DNS=10.70.10.254
CONF
say "listo (borrador)"
