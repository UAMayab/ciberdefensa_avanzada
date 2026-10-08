#!/bin/sh
# Miniworkshop Nmap — aprovisionamiento de nmap-decoy.
# Este host NO tiene servicios vulnerables. Existe por dos razones didácticas:
#
#   1. Que el descubrimiento de hosts (`-sn`, Episodio 1) tenga a quién
#      encontrar además del objetivo: un barrido que devuelve un solo host no
#      enseña nada.
#   2. Que descarte los ICMP echo. Así se demuestra en vivo que en el segmento
#      local Nmap lo encuentra IGUAL —porque usa ARP, no ping— y que sólo
#      desaparece si se le fuerza a usar IP con `--send-ip`. Es la diferencia
#      entre «bloqueé el ping» y «soy invisible», que no es la misma cosa.
#
# Además corre dropbear en el 22 en vez de OpenSSH: en el Episodio 2 sirve para
# ver que «ssh» es un protocolo, no un producto, y que `-sV` distingue cuál.
set -eu

say() { echo "[miniws-nmap] $*"; }

say "fijando repositorios a Alpine v3.19"
cat > /etc/apk/repositories <<'EOF'
https://dl-cdn.alpinelinux.org/alpine/v3.19/main
https://dl-cdn.alpinelinux.org/alpine/v3.19/community
EOF
apk update >/dev/null

say "instalando dropbear, nftables y tcpdump"
apk add --no-cache dropbear nftables tcpdump iproute2 >/dev/null

# OpenSSH se queda escuchando sólo en la interfaz NAT de gestión (10.0.2.15),
# para que `vagrant ssh` siga funcionando, pero NO en la red del laboratorio.
# Así el 22 que ve el estudiante desde 192.168.60.x es el de dropbear.
say "reservando OpenSSH para la interfaz de gestión"
if ! grep -q "miniws-nmap" /etc/ssh/sshd_config; then
  { echo "# --- miniws-nmap: sshd sólo en la interfaz NAT de Vagrant ---"
    echo "ListenAddress 10.0.2.15"
    cat /etc/ssh/sshd_config
  } > /etc/ssh/sshd_config.new
  mv /etc/ssh/sshd_config.new /etc/ssh/sshd_config
fi

say "dropbear en 192.168.60.20:22"
mkdir -p /etc/dropbear
cat > /etc/conf.d/dropbear <<'EOF'
# Miniworkshop Nmap — dropbear de nmap-decoy.
# -p fija la escucha a la IP del laboratorio para no chocar con el sshd de
# gestión; -w prohíbe el login de root; -s prohíbe el de contraseña. Este host
# no es un objetivo: es un segundo vecino en el segmento.
DROPBEAR_OPTS="-p 192.168.60.20:22 -w -s"
EOF
rc-update add dropbear default >/dev/null 2>&1 || true

say "descartando ICMP echo (la demostración de ARP vs ping)"
cat > /etc/nftables.nft <<'EOF'
#!/usr/sbin/nft -f
# Miniworkshop Nmap — cortafuegos de nmap-decoy.
# Descarta las peticiones de eco ICMP, y nada más. El objetivo es enseñar que
# en el segmento local eso NO esconde al host: Nmap lo descubre por ARP.
flush ruleset

table inet lab {
    chain input {
        type filter hook input priority filter; policy accept;
        icmp type echo-request drop
    }
}
EOF
rc-update add nftables default >/dev/null 2>&1 || true

say "arrancando servicios"
rc-service nftables restart >/dev/null 2>&1 || say "AVISO: nftables no arrancó"
rc-service dropbear restart >/dev/null 2>&1 || say "AVISO: dropbear no arrancó"
rc-service sshd reload     >/dev/null 2>&1 || true

sleep 1
say "--- puertos TCP a la escucha ---"
ss -tlnp 2>/dev/null | awk 'NR==1 || /LISTEN/'
say "nmap-decoy: aprovisionamiento completo"
