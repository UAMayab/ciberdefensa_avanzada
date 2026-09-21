#!/bin/sh
# Miniworkshop Nmap — genera el material TLS deliberadamente débil que sirve
# nginx en 443/tcp de nmap-target (Episodio 3: ssl-cert, ssl-enum-ciphers,
# ssl-dh-params, ssl-date).
#
# Tres defectos, los tres reales y detectables por comportamiento, no por
# banner:
#   1. clave RSA de 1024 bits          -> por debajo del mínimo actual (2048)
#   2. firma SHA-1                     -> función hash rota desde 2017
#   3. validez terminada en enero 2024 -> certificado caducado
# Y, aparte, un grupo Diffie-Hellman de 1024 bits para las suites DHE.
#
# `openssl req -x509` no sabe fechar un certificado en el pasado; `openssl ca
# -selfsign` con -startdate/-enddate sí. Por eso el rodeo de montar una CA
# mínima de usar y tirar.
set -eu

OUT=/etc/nginx/tls
WORK=$(mktemp -d)
mkdir -p "$OUT"

cd "$WORK"
mkdir -p newcerts
touch index.txt
echo 01 > serial

cat > ca.cnf <<'CNF'
[ ca ]
default_ca = lab
[ lab ]
dir            = .
database       = $dir/index.txt
new_certs_dir  = $dir/newcerts
serial         = $dir/serial
default_md     = sha1
policy         = pol
email_in_dn    = no
unique_subject = no
[ pol ]
commonName = supplied
[ req ]
distinguished_name = dn
prompt             = no
[ dn ]
CN = intranet.auroramaritima.example
O  = Aurora Maritima SA de CV
C  = MX
CNF

openssl genrsa -out key.pem 1024 2>/dev/null
openssl req -new -key key.pem -out csr.pem -config ca.cnf -sha1 2>/dev/null
openssl ca -config ca.cnf -selfsign -keyfile key.pem -in csr.pem -out cert.pem \
  -startdate 20230115000000Z -enddate 20240115000000Z -md sha1 -batch -notext \
  >/dev/null 2>&1

# Grupo DH de 1024 bits: lo que hace que ssl-dh-params diga «Weak DH group».
openssl dhparam -out dh1024.pem 1024 2>/dev/null

install -m 0644 cert.pem   "$OUT/lab-weak.crt"
install -m 0600 key.pem    "$OUT/lab-weak.key"
install -m 0644 dh1024.pem "$OUT/dh1024.pem"

cd /
rm -rf "$WORK"

echo "[miniws-nmap] TLS débil generado en $OUT:"
openssl x509 -in "$OUT/lab-weak.crt" -noout -subject -dates 2>/dev/null
