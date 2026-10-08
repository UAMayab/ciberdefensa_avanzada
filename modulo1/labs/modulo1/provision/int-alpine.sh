#!/bin/sh
# Aprovisionamiento de int-alpine — laboratorio Módulo I (Anexo A)
# Usado por A.3, A.4, A.7.
set -eu

apk update
apk add --no-cache openssh iptables nftables busybox-extras

rc-update add sshd default
service sshd start

# Telnet en texto claro, dejado abierto a propósito como estado "antes" que
# A.7 debe cerrar/restringir. Solo para laboratorio aislado — ver README.md.
mkdir -p /etc/local.d
cat > /etc/local.d/telnetd.start <<'EOF'
#!/bin/sh
/usr/sbin/telnetd -l /bin/login
EOF
chmod +x /etc/local.d/telnetd.start
rc-update add local default
/etc/local.d/telnetd.start

echo "[modulo1] int-alpine: aprovisionamiento completo"
