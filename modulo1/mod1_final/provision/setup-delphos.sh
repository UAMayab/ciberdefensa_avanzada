#!/usr/bin/env bash
# Entregable final — Módulo I · Aprovisionamiento del objetivo "Aseguradora Delphos".
# Instala Docker, coloca el material subido por SCP y levanta los servicios
# vulnerables definidos en docker-compose.yml.
set -euo pipefail

echo "[delphos] === Aprovisionando objetivo de pentest (FICTICIO) ==="

# --- 1. Localizar el material subido por el provisionador "file" -------------
# Según exista o no /tmp/delphos al subir, Vagrant deja el contenido en
# /tmp/delphos o anidado en /tmp/delphos/provision. Buscamos el compose.
SRC=""
for d in /tmp/delphos /tmp/delphos/provision; do
  if [ -f "$d/docker-compose.yml" ]; then SRC="$d"; break; fi
done
if [ -z "$SRC" ]; then
  echo "[delphos] ERROR: no encuentro docker-compose.yml bajo /tmp/delphos" >&2
  ls -laR /tmp/delphos >&2 || true
  exit 1
fi
echo "[delphos] Material encontrado en: $SRC"

# --- 2. Instalar Docker y el plugin compose ---------------------------------
export DEBIAN_FRONTEND=noninteractive
echo "[delphos] Instalando Docker (puede tardar la primera vez)..."
apt-get update -y -q
apt-get install -y -q docker.io docker-compose-v2
systemctl enable --now docker

# --- 3. Copiar el material a una ubicación estable --------------------------
install -d /opt/delphos
cp -a "$SRC/." /opt/delphos/
echo "[delphos] Material desplegado en /opt/delphos"

# --- 4. Levantar los servicios ----------------------------------------------
cd /opt/delphos
echo "[delphos] Descargando imágenes y levantando servicios..."
docker compose pull --quiet || true
docker compose up -d

echo "[delphos] Esperando a que los servicios arranquen..."
sleep 20
docker compose ps

cat <<'EOF'

[delphos] === Objetivo listo ===
  Sitio corporativo : http://192.168.59.10/
  Tomcat (gestor)   : http://192.168.59.10:8080/
  Drupal (portal)   : http://192.168.59.10:8081/   (si está habilitado)
  Jenkins           : http://192.168.59.10:8083/
  Redis             : 192.168.59.10:6379
  MariaDB           : 192.168.59.10:3306

  Recuerda: entorno FICTICIO y deliberadamente vulnerable. Úsalo sólo en
  la red host-only aislada de este laboratorio.
EOF
