#!/usr/bin/env bash
# Moves PHP from 8.5.4-0ubuntu1 to the fixed Ubuntu build (USN-8948-1).
# Run with: vagrant provision --provision-with patch
set -euo pipefail
export DEBIAN_FRONTEND=noninteractive NEEDRESTART_MODE=a

rm -f /etc/apt/preferences.d/php-vulnerable
apt-get update -qq
apt-get install -y -qq --only-upgrade \
  libapache2-mod-php8.5 php8.5-cli php8.5-common php8.5-readline >/dev/null
systemctl restart apache2   # restart ALL workers: stale workers keep the old code in memory

echo "==> Now: $(dpkg-query -W -f='${Package} ${Version}' libapache2-mod-php8.5)"
echo "==> php -v reports: $(php -r 'echo PHP_VERSION;')  (note: the upstream version string did not change)"
