#!/usr/bin/env bash
# Kaab Orgánicos (FICTICIO) · web-dmz: tienda en línea, panel de administración y /status.
# Configuración deliberadamente débil. El mapa de hallazgos está en modulo2/instructor/ (fuera del repo).
# Idempotente: se puede volver a correr con `vagrant provision web-dmz`.
set -euo pipefail

log() { echo "[kaab-grc] web-dmz: $*"; }
export DEBIAN_FRONTEND=noninteractive

# La empresa opera en Mérida (UTC-6, sin horario de verano): la hora de los registros debe coincidir.
timedatectl set-timezone America/Merida

log "Instalando Flask y el conector de MariaDB"
apt-get update -qq
apt-get install -y -qq python3-flask python3-pymysql >/dev/null

log "Copiando la tienda a /opt/tienda"
install -d -m 0755 /opt/tienda
install -m 0644 /tmp/kaab-store/app.py /opt/tienda/app.py
# 0644: cualquier usuario local puede leer la configuración con la contraseña de la base.
install -m 0644 /tmp/kaab-store/config.py /opt/tienda/config.py
rm -rf /opt/tienda/templates && cp -r /tmp/kaab-store/templates /opt/tienda/templates
# Bootstrap y la hoja de estilos de la marca: la misma base visual que el sitio público.
rm -rf /opt/tienda/static && install -d -m 0755 /opt/tienda/static
cp -r /tmp/kaab-vendor /opt/tienda/static/vendor
install -m 0644 /tmp/kaab-assets/kaab.css /opt/tienda/static/kaab.css

log "Servicio tienda-kaab (systemd, puerto 80)"
cat > /etc/systemd/system/tienda-kaab.service <<'EOF'
[Unit]
Description=Tienda en línea de Kaab Orgánicos (ficticia)
After=network-online.target
Wants=network-online.target

[Service]
ExecStart=/usr/bin/python3 /opt/tienda/app.py
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
EOF
systemctl daemon-reload
systemctl enable tienda-kaab.service
systemctl restart tienda-kaab.service

log "Acceso por SSH con contraseña (usuario soporte)"
id -u soporte >/dev/null 2>&1 || useradd -m -s /bin/bash soporte
echo "soporte:Soporte-2026" | chpasswd
cat > /etc/ssh/sshd_config.d/10-kaab-lab.conf <<'EOF'
PasswordAuthentication yes
EOF
systemctl reload ssh

log "Listo."
