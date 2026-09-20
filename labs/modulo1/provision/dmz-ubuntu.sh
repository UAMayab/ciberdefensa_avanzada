#!/usr/bin/env bash
# Aprovisionamiento de dmz-ubuntu — laboratorio Módulo I (Anexo A)
# Usado por A.3, A.4, A.7, A.8 y como objetivo de A.6.
set -eu

export DEBIAN_FRONTEND=noninteractive
apt-get update -y
apt-get install -y --no-install-recommends \
  openssh-server nginx vsftpd ufw suricata tcpdump net-tools

# Usuario de demostración para el hallazgo de credenciales en texto claro (A.3).
# Solo para laboratorio aislado — ver README.md.
if ! id -u labdemo >/dev/null 2>&1; then
  useradd -m -s /bin/bash labdemo
  echo "labdemo:labdemo123" | chpasswd
fi

# vsftpd: login de usuario local (sin anónimo) — envía USER/PASS en texto claro,
# visible en Wireshark. Config por defecto del paquete ya lo permite.
systemctl enable --now vsftpd

# nginx: sirve el sitio web ficticio de "Nordlys AI ApS", objetivo de la
# actividad de reconocimiento (ver "Sitio web de práctica" y "Banderas" en
# README.md). Los archivos los sube el provisionador "file" del Vagrantfile.
SRC=/tmp/nordlys-site
[ -d "$SRC/www" ] || SRC=/tmp/nordlys-site/site   # tolera una re-subida anidada

if [ -d "$SRC/www" ]; then
  rm -rf /var/www/nordlys /var/www/nordlys-staging
  cp -r "$SRC/www"         /var/www/nordlys
  cp -r "$SRC/www-staging" /var/www/nordlys-staging
  chown -R root:root /var/www/nordlys /var/www/nordlys-staging
  find /var/www/nordlys /var/www/nordlys-staging -type d -exec chmod 755 {} +
  find /var/www/nordlys /var/www/nordlys-staging -type f -exec chmod 644 {} +

  install -m 644 "$SRC/nginx-nordlys.conf" /etc/nginx/sites-available/nordlys
  ln -sf /etc/nginx/sites-available/nordlys /etc/nginx/sites-enabled/nordlys
  rm -f /etc/nginx/sites-enabled/default   # su "default_server" chocaría con el nuestro
  nginx -t
else
  echo "[modulo1] AVISO: no se encontró el sitio en /tmp/nordlys-site; se deja nginx por defecto" >&2
fi

systemctl enable --now nginx
systemctl reload nginx

# ufw queda instalado pero inactivo: los estudiantes lo activan y configuran en A.7.
ufw --force disable

# Suricata: instalado con un ruleset local mínimo y determinista (no depende de
# internet ni de suricata-update para tener alertas reproducibles en 20 min de
# demo). El servicio queda deshabilitado; los estudiantes lo configuran y
# arrancan explícitamente durante A.8 (ver README.md).
mkdir -p /etc/suricata/rules
cat > /etc/suricata/rules/lab-custom.rules <<'EOF'
alert icmp any any -> $HOME_NET any (msg:"LAB ICMP ping detectado"; sid:1000001; rev:1;)
alert tcp any any -> $HOME_NET any (msg:"LAB posible escaneo Nmap SYN"; flags:S; threshold:type both, track by_src, count 5, seconds 5; sid:1000002; rev:1;)
EOF
systemctl disable --now suricata || true

echo "[modulo1] dmz-ubuntu: aprovisionamiento completo"
