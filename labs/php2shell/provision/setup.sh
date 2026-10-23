#!/usr/bin/env bash
# Installs Apache + mod_php pinned to the UNPATCHED Ubuntu 26.04 release build
# (8.5.4-0ubuntu1) and deploys the demo site. Lab use only.
set -euo pipefail
export DEBIAN_FRONTEND=noninteractive NEEDRESTART_MODE=a

VULN_VERSION="8.5.4-0ubuntu1"   # the fix shipped in 8.5.4-0ubuntu1.5 (USN-8948-1)

# Nothing may silently patch the box: stop unattended upgrades, then pin PHP.
systemctl disable --now unattended-upgrades apt-daily.timer apt-daily-upgrade.timer 2>/dev/null || true
cat > /etc/apt/preferences.d/php-vulnerable <<EOF
# Lab: keep PHP on the pre-fix build. Removed by provision/patch.sh.
Package: php8.5* libapache2-mod-php8.5
Pin: version ${VULN_VERSION}
Pin-Priority: 1001
EOF

apt-get update -qq
apt-get install -y -qq --allow-downgrades apache2 libapache2-mod-php8.5 php8.5-cli >/dev/null

# mod_php requires the prefork MPM; the package switches to it, make sure.
a2dismod -q mpm_event mpm_worker 2>/dev/null || true
a2enmod -q mpm_prefork php8.5 >/dev/null

# A realistic (very common) setting: PHP announces itself in X-Powered-By.
echo "expose_php = On" > /etc/php/8.5/apache2/conf.d/99-lab.ini

# Site
rm -f /var/www/html/index.html
cp -r /tmp/site/. /var/www/html/
chown -R root:root /var/www/html
chmod -R a+rX /var/www/html

systemctl restart apache2
systemctl enable apache2 >/dev/null 2>&1

echo "==> PHP $(php -r 'echo PHP_VERSION;') (package $(dpkg-query -W -f='${Version}' libapache2-mod-php8.5)) on $(lsb_release -ds)"
echo "==> Site: http://$1/"
