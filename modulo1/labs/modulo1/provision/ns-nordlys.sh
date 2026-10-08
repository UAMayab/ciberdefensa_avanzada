#!/bin/sh
# Aprovisionamiento de ns-nordlys — laboratorio Módulo I (Anexo A)
# DNS autoritativo de nordlysai.dk y resolver del laboratorio.
# Usado por A.2 (reconocimiento); también da tráfico DNS real a A.3 y un
# host más que descubrir a A.4.
set -eu

apk update
apk add --no-cache bind bind-tools

# Zonas y configuración llegan por SCP a /tmp/nordlys-dns (provisionador
# "file" del Vagrantfile), igual que el sitio web de dmz-ubuntu.
install -d -o named -g named /var/bind/pri
install -o named -g named -m 644 /tmp/nordlys-dns/db.nordlysai.dk /var/bind/pri/db.nordlysai.dk
install -o named -g named -m 644 /tmp/nordlys-dns/db.192.168.56  /var/bind/pri/db.192.168.56
install -o root  -g named -m 640 /tmp/nordlys-dns/named.conf     /etc/bind/named.conf
install -d -o named -g named /var/run/named

# Validar antes de arrancar: si una zona tiene un error de sintaxis, el
# aprovisionamiento falla aquí y no en mitad de una sesión.
named-checkconf /etc/bind/named.conf
named-checkzone nordlysai.dk            /var/bind/pri/db.nordlysai.dk
named-checkzone 56.168.192.in-addr.arpa /var/bind/pri/db.192.168.56

rc-update add named default
rc-service named restart

echo "[modulo1] ns-nordlys: aprovisionamiento completo"
