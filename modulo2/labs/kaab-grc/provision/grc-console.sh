#!/usr/bin/env bash
# Kaab Orgánicos (FICTICIO) · grc-console: sitio público de la empresa y la aplicación de evaluación.
# Idempotente: `vagrant provision grc-console` actualiza el código SIN borrar el progreso del estudiante
# (vive en /var/lib/kaab-grc/workspace.json, fuera de /opt).
set -euo pipefail

log() { echo "[kaab-grc] grc-console: $*"; }
export DEBIAN_FRONTEND=noninteractive

# La empresa opera en Mérida (UTC-6, sin horario de verano): la hora de los registros debe coincidir.
timedatectl set-timezone America/Merida
export PIP_DISABLE_PIP_VERSION_CHECK=1

log "Instalando nginx, Python, nmap y curl"
apt-get update -qq
apt-get install -y -qq nginx python3-venv nmap curl >/dev/null

log "Sitio público de Kaab en /var/www/kaab"
rm -rf /var/www/kaab
install -d -m 0755 /var/www/kaab
cp -r /tmp/kaab-site/. /var/www/kaab/
cat > /etc/nginx/sites-available/default <<'EOF'
server {
    listen 80 default_server;
    root /var/www/kaab;
    index index.html;

    location / {
        try_files $uri $uri/ =404;
    }
}
EOF
nginx -t
systemctl enable nginx
systemctl restart nginx

log "Aplicación de evaluación en /opt/kaab-grc"
id -u kaab-grc >/dev/null 2>&1 || useradd --system --home /var/lib/kaab-grc --shell /usr/sbin/nologin kaab-grc
install -d -m 0755 -o kaab-grc -g kaab-grc /var/lib/kaab-grc
install -d -m 0755 /opt/kaab-grc
rm -rf /opt/kaab-grc/app
cp -r /tmp/kaab-app /opt/kaab-grc/app
[ -d /opt/kaab-grc/venv ] || python3 -m venv /opt/kaab-grc/venv
/opt/kaab-grc/venv/bin/pip install -q -r /opt/kaab-grc/app/requirements.txt

log "Servicio kaab-grc (Streamlit, puerto 8501)"
cat > /etc/systemd/system/kaab-grc.service <<'EOF'
[Unit]
Description=Evaluación de riesgos de Kaab Orgánicos (Streamlit, ficticio)
After=network-online.target
Wants=network-online.target

[Service]
User=kaab-grc
WorkingDirectory=/opt/kaab-grc/app
Environment=KAAB_GRC_WORKSPACE=/var/lib/kaab-grc/workspace.json
ExecStart=/opt/kaab-grc/venv/bin/streamlit run app.py --server.address=0.0.0.0 --server.port=8501 --server.headless=true --browser.gatherUsageStats=false
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
EOF
systemctl daemon-reload
systemctl enable kaab-grc.service
systemctl restart kaab-grc.service

log "Acceso por SSH con contraseña (usuario soporte)"
id -u soporte >/dev/null 2>&1 || useradd -m -s /bin/bash soporte
echo "soporte:Soporte-2026" | chpasswd
cat > /etc/ssh/sshd_config.d/10-kaab-lab.conf <<'EOF'
PasswordAuthentication yes
EOF
systemctl reload ssh

log "Listo."
