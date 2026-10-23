#!/usr/bin/env bash
# Laboratorio «cti» · OpenCTI con Docker, siguiendo la guía oficial:
# https://docs.opencti.io/latest/deployment/installation/#using-docker
# Idempotente: `vagrant provision` no regenera los secretos ni borra los datos.
set -euo pipefail

LAB_IP="$1"
ADMIN_EMAIL="admin@cti.lab"
ADMIN_PASSWORD="UAM-Ciberdefensa-2026"
APP_DIR=/opt/opencti

log() { echo "[cti] $*"; }
export DEBIAN_FRONTEND=noninteractive NEEDRESTART_SUSPEND=1

log "Instalando Docker Engine desde el repositorio oficial de Docker"
apt-get remove -y -qq $(dpkg --get-selections docker.io docker-compose docker-compose-v2 docker-doc podman-docker containerd runc 2>/dev/null | cut -f1) >/dev/null
apt-get update -qq
apt-get install -y -qq ca-certificates curl git openssl >/dev/null
install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
chmod a+r /etc/apt/keyrings/docker.asc
cat > /etc/apt/sources.list.d/docker.sources <<EOF
Types: deb
URIs: https://download.docker.com/linux/ubuntu
Suites: $(. /etc/os-release && echo "${UBUNTU_CODENAME:-$VERSION_CODENAME}")
Components: stable
Signed-By: /etc/apt/keyrings/docker.asc
EOF
apt-get update -qq
apt-get install -y -qq docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin >/dev/null
systemctl enable --now docker.service
usermod -aG docker vagrant   # `docker ...` sin sudo dentro de `vagrant ssh`

log "Elasticsearch: vm.max_map_count=1048575"
echo "vm.max_map_count=1048575" > /etc/sysctl.d/99-opencti.conf
sysctl -q -w vm.max_map_count=1048575

if [ ! -d "$APP_DIR/.git" ]; then
  log "Clonando OpenCTI-Platform/docker en $APP_DIR"
  git clone -q https://github.com/OpenCTI-Platform/docker.git "$APP_DIR"
fi
cd "$APP_DIR"

# .env a partir de .env.sample, con secretos generados como indica la guía.
# Solo la primera vez: regenerarlos dejaría inservibles los datos ya guardados.
set_env() {
  sed -i "s|^$1=.*|$1=$2|" .env
  grep -qxF "$1=$2" .env || { echo "[cti] ERROR: .env.sample ya no tiene $1" >&2; exit 1; }
}
uuid() { cat /proc/sys/kernel/random/uuid; }
if [ ! -f .env ]; then
  log "Generando .env"
  cp .env.sample .env
  set_env MINIO_ROOT_USER "$(uuid)"
  set_env MINIO_ROOT_PASSWORD "$(uuid)"
  set_env RABBITMQ_DEFAULT_PASS "$(openssl rand -base64 32)"
  set_env ELASTIC_MEMORY_SIZE 2G
  set_env PLATFORM_REGISTRATION_TOKEN "$(openssl rand -hex 32)"
  set_env OPENCTI_HOST "$LAB_IP"
  set_env OPENCTI_ADMIN_EMAIL "$ADMIN_EMAIL"
  set_env OPENCTI_ADMIN_PASSWORD "$ADMIN_PASSWORD"
  set_env OPENCTI_ADMIN_TOKEN "$(uuid)"
  set_env OPENCTI_HEALTHCHECK_ACCESS_KEY "$(uuid)"
  set_env OPENCTI_ENCRYPTION_KEY "$(openssl rand -base64 32)"
  set_env XTM_ONE_HOST "$LAB_IP"
  set_env XTM_ONE_ADMIN_EMAIL "$ADMIN_EMAIL"
  set_env XTM_ONE_ADMIN_PASSWORD "$ADMIN_PASSWORD"
  set_env XTM_ONE_SECRET_KEY "$(openssl rand -hex 32)"
  set_env XTM_ONE_POSTGRES_PASSWORD "$(openssl rand -hex 24)"
fi

log "Descargando imágenes (varios minutos la primera vez)"
# Una a una: en paralelo, containerd se traba con las capas que comparten las imágenes
# (y connector-import-document aparece dos veces). Solo las que faltan: no actualiza OpenCTI
# (`docker compose pull --policy missing` sí vuelve a bajar las :latest).
for img in $(docker compose config --images | sort -u); do
  docker image inspect "$img" >/dev/null 2>&1 || docker pull -q "$img" >/dev/null
done
log "Arrancando la pila"
docker compose up -d

HEALTH_KEY=$(sed -n 's/^OPENCTI_HEALTHCHECK_ACCESS_KEY=//p' .env)
log "Esperando a que OpenCTI esté listo"
for _ in $(seq 1 120); do
  if curl -fs "http://localhost:8080/health?health_access_key=$HEALTH_KEY" >/dev/null; then
    log "OpenCTI listo en http://$LAB_IP:8080"
    log "Usuario: $ADMIN_EMAIL · Contraseña: $ADMIN_PASSWORD"
    exit 0
  fi
  sleep 10
done
docker compose ps
echo "[cti] ERROR: OpenCTI no respondió en 20 minutos. Revisa: sudo docker compose -f $APP_DIR/docker-compose.yml logs opencti" >&2
exit 1
