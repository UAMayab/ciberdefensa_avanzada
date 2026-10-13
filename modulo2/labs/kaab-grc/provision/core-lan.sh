#!/usr/bin/env bash
# Kaab Orgánicos (FICTICIO) · core-lan: base de pedidos (MariaDB) y proceso de pedidos.
# Configuración deliberadamente débil. El mapa de hallazgos está en modulo2/instructor/ (fuera del repo).
# Idempotente: se puede volver a correr con `vagrant provision core-lan`.
set -euo pipefail

log() { echo "[kaab-grc] core-lan: $*"; }
export DEBIAN_FRONTEND=noninteractive

# La empresa opera en Mérida (UTC-6, sin horario de verano): la hora de los registros debe coincidir.
timedatectl set-timezone America/Merida

log "Instalando MariaDB y el conector de Python"
apt-get update -qq
apt-get install -y -qq mariadb-server python3-pymysql >/dev/null

log "MariaDB escuchando en todas las interfaces (core_net)"
cat > /etc/mysql/mariadb.conf.d/99-kaab.cnf <<'EOF'
[mysqld]
bind-address = 0.0.0.0
EOF
systemctl restart mariadb

log "Creando la base kaab y el usuario de la tienda"
mysql -e "CREATE DATABASE IF NOT EXISTS kaab CHARACTER SET utf8mb4;"
mysql -e "CREATE USER IF NOT EXISTS 'tienda'@'192.168.62.10' IDENTIFIED BY 'Tienda-2026';"
mysql -e "GRANT ALL PRIVILEGES ON *.* TO 'tienda'@'192.168.62.10'; FLUSH PRIVILEGES;"
mysql kaab < /tmp/kaab-core/schema.sql

log "Proceso de pedidos (systemd: kaab-pedidos)"
install -d -m 0755 /opt/kaab
install -m 0644 /tmp/kaab-core/worker.py /opt/kaab/worker.py
python3 /opt/kaab/worker.py --sembrar-solo
cat > /etc/systemd/system/kaab-pedidos.service <<'EOF'
[Unit]
Description=Kaab Orgánicos: proceso de pedidos (ficticio)
After=mariadb.service
Requires=mariadb.service

[Service]
ExecStart=/usr/bin/python3 /opt/kaab/worker.py
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
EOF
systemctl daemon-reload
systemctl enable kaab-pedidos.service
systemctl restart kaab-pedidos.service

log "Respaldo diario de clientes en /var/backups/kaab (sin prueba de restauración)"
install -d -m 0755 /var/backups/kaab
cat > /etc/cron.d/kaab-respaldo <<'EOF'
# Respaldo diario de clientes (FICTICIO).
15 3 * * * root mysqldump kaab clientes > /var/backups/kaab/clientes-$(date +\%F).sql
EOF
HOY="/var/backups/kaab/clientes-$(date +%F).sql"
[ -s "$HOY" ] || mysqldump kaab clientes > "$HOY"

log "Acceso por SSH con contraseña (usuario soporte)"
id -u soporte >/dev/null 2>&1 || useradd -m -s /bin/bash soporte
echo "soporte:Soporte-2026" | chpasswd
cat > /etc/ssh/sshd_config.d/10-kaab-lab.conf <<'EOF'
PasswordAuthentication yes
EOF
systemctl reload ssh

log "Listo."
