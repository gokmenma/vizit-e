#!/usr/bin/env bash
#
# vizit-e.site icin hosts kaydi, Apache sanal sunucusu ve HTTPS kurulumunu yapar.
# Tekrar calistirilabilir: mevcut kayitlari cogaltmaz.
#
# Kullanim:  sudo bash /opt/lampp/htdocs/sgk-vizite/bin/setup-vhost.sh
#
set -euo pipefail

DOMAIN="vizit-e.site"
DOCROOT="/opt/lampp/htdocs/sgk-vizite"
VHOST_FILE="/opt/lampp/etc/extra/httpd-vhosts.conf"
VHOST_SRC="${DOCROOT}/.ssl/vizit-e.site-vhost.conf"
HOSTS_FILE="/etc/hosts"
MARKER="# >>> vizit-e.site (sgk-vizite) >>>"
END_MARKER="# <<< vizit-e.site (sgk-vizite) <<<"

if [[ $EUID -ne 0 ]]; then
    echo "HATA: Bu betik root yetkisi gerektirir. 'sudo bash $0' seklinde calistirin." >&2
    exit 1
fi

for f in "$VHOST_FILE" "$VHOST_SRC"; do
    [[ -f "$f" ]] || { echo "HATA: $f bulunamadi." >&2; exit 1; }
done

if [[ ! -f "${DOCROOT}/.ssl/${DOMAIN}.crt" ]]; then
    echo "HATA: Sertifika yok. Once su komutu (root OLMADAN) calistirin:" >&2
    echo "  cd ${DOCROOT}/.ssl && mkcert -cert-file ${DOMAIN}.crt -key-file ${DOMAIN}.key ${DOMAIN} '*.${DOMAIN}' localhost 127.0.0.1 ::1" >&2
    exit 1
fi

echo "==> 1/4  hosts kaydi"
if grep -qE "^[^#]*[[:space:]]${DOMAIN}([[:space:]]|$)" "$HOSTS_FILE"; then
    echo "    zaten var, atlandi"
else
    cp -a "$HOSTS_FILE" "${HOSTS_FILE}.bak.$(date +%Y%m%d%H%M%S)"
    printf '127.0.0.1    %s www.%s\n' "$DOMAIN" "$DOMAIN" >> "$HOSTS_FILE"
    echo "    eklendi"
fi

echo "==> 2/4  Apache sanal sunucusu"
if grep -qF "$MARKER" "$VHOST_FILE"; then
    echo "    blok zaten var, guncelleniyor"
    cp -a "$VHOST_FILE" "${VHOST_FILE}.bak.$(date +%Y%m%d%H%M%S)"
    tmp=$(mktemp)
    awk -v m="$MARKER" -v e="$END_MARKER" '
        $0 == m { skip = 1 }
        !skip   { print }
        $0 == e { skip = 0 }
    ' "$VHOST_FILE" > "$tmp"
    mv "$tmp" "$VHOST_FILE"
else
    cp -a "$VHOST_FILE" "${VHOST_FILE}.bak.$(date +%Y%m%d%H%M%S)"
fi

{
    printf '\n%s\n' "$MARKER"
    cat "$VHOST_SRC"
    printf '%s\n' "$END_MARKER"
} >> "$VHOST_FILE"
echo "    yazildi"

echo "==> 3/4  yapilandirma dogrulamasi"
if ! /opt/lampp/bin/httpd -t 2>&1 | tee /tmp/vizit-e-site-httpd-test.log | grep -q "Syntax OK"; then
    echo "HATA: Apache yapilandirmasi gecersiz:" >&2
    cat /tmp/vizit-e-site-httpd-test.log >&2
    echo "Yedekten geri almak icin: ${VHOST_FILE}.bak.* dosyalarina bakin." >&2
    exit 1
fi
echo "    Syntax OK"

echo "==> 4/4  Apache yeniden baslatiliyor"
/opt/lampp/lampp reloadapache >/dev/null 2>&1 || /opt/lampp/lampp restartapache >/dev/null 2>&1
sleep 2
echo "    tamam"

echo
echo "Kurulum tamamlandi:  https://${DOMAIN}/"
