#!/usr/bin/env bash
# Sistema de Gobernanza y Gestión de Riesgo · Módulo II (proyecto final).
# Instala Apache + PHP + MariaDB, crea la base `grc` y publica la aplicación.
# Idempotente: `vagrant provision` actualiza el código y los catálogos SIN borrar el trabajo del estudiante.
set -euo pipefail

log() { echo "[grc-final] $*"; }
export DEBIAN_FRONTEND=noninteractive

APP=/var/www/grc
DB_NAME=grc
DB_USER=grc_app

# La organización de ejemplo y los cursos operan en Mérida (UTC-6, sin horario de verano).
timedatectl set-timezone America/Merida

log "Instalando Apache, PHP y MariaDB"
apt-get update -qq
apt-get install -y -qq apache2 mariadb-server php libapache2-mod-php php-mysql php-mbstring curl >/dev/null
systemctl enable --now mariadb >/dev/null

log "Publicando la aplicación en $APP"
install -d -m 0755 "$APP"
# Conserva las credenciales de la base entre re-aprovisionamientos.
DB_PASS=""
if [ -f "$APP/inc/config.php" ]; then
  DB_PASS=$(sed -n "s/.*'db_pass' *=> *'\([^']*\)'.*/\1/p" "$APP/inc/config.php")
fi
[ -n "$DB_PASS" ] || DB_PASS=$(openssl rand -hex 16)
rm -rf "$APP/public" "$APP/inc"
cp -r /tmp/grc-app/public "$APP/public"
cp -r /tmp/grc-app/inc "$APP/inc"
cat > "$APP/inc/config.php" <<EOF
<?php
// Generado por provision.sh. Credenciales de la base de datos local (solo accesible desde la VM).
return ['db_host' => 'localhost', 'db_name' => '$DB_NAME', 'db_user' => '$DB_USER', 'db_pass' => '$DB_PASS'];
EOF
chown -R root:root "$APP"
find "$APP" -type d -exec chmod 0755 {} +
find "$APP" -type f -exec chmod 0644 {} +
chown root:www-data "$APP/inc/config.php"
chmod 0640 "$APP/inc/config.php"

log "Base de datos: esquema, catálogos y usuario de la aplicación"
mariadb < /tmp/grc-db/schema.sql
mariadb < /tmp/grc-db/catalogos.sql
mariadb <<SQL
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
ALTER USER '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
SQL

log "Configurando Apache y PHP"
for d in /etc/php/*/apache2/conf.d; do
  cat > "$d/99-grc.ini" <<'EOF'
expose_php = Off
display_errors = Off
log_errors = On
date.timezone = America/Merida
session.cookie_httponly = 1
session.cookie_samesite = Lax
upload_max_filesize = 4M
EOF
done
cat > /etc/apache2/conf-available/grc-hardening.conf <<'EOF'
ServerName grc-final
ServerTokens Prod
ServerSignature Off
EOF
cat > /etc/apache2/sites-available/grc.conf <<'EOF'
<VirtualHost *:80>
    ServerName grc-final
    DocumentRoot /var/www/grc/public

    <Directory /var/www/grc/public>
        Options -Indexes
        Require all granted
    </Directory>

    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set Referrer-Policy "same-origin"

    ErrorLog ${APACHE_LOG_DIR}/grc-error.log
    CustomLog ${APACHE_LOG_DIR}/grc-access.log combined
</VirtualHost>
EOF
a2enmod -q headers
a2enconf -q grc-hardening
a2dissite -q 000-default || true
a2ensite -q grc
apache2ctl configtest
systemctl enable apache2 >/dev/null
systemctl restart apache2

log "Verificación"
for p in "" activos.php vulnerabilidades.php amenazas.php riesgos.php reporte.php configuracion.php; do
  code=$(curl -s -o /dev/null -w '%{http_code}' "http://localhost/$p")
  [ "$code" = "200" ] || { echo "[grc-final] ERROR: /$p respondió $code" >&2; exit 1; }
done
log "Listo: http://192.168.61.30/  (o http://localhost:8080/ desde este equipo)"
