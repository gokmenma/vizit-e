#!/usr/bin/env bash
#
# vizit-e.site ve admin.vizite.site icin hosts kaydi, Apache sanal sunuculari
# ve HTTPS kurulumunu yapar. Tekrar calistirilabilir: mevcut kayitlari cogaltmaz.
#
# Kullanim:  sudo bash /opt/lampp/htdocs/sgk-vizite/bin/setup-vhost.sh
#
set -euo pipefail

DOCROOT="/opt/lampp/htdocs/sgk-vizite"
VHOST_FILE="/opt/lampp/etc/extra/httpd-vhosts.conf"
HOSTS_FILE="/etc/hosts"

if [[ $EUID -ne 0 ]]; then
    echo "HATA: Bu betik root yetkisi gerektirir. 'sudo bash $0' seklinde calistirin." >&2
    exit 1
fi

[[ -f "$VHOST_FILE" ]] || { echo "HATA: $VHOST_FILE bulunamadi." >&2; exit 1; }

setup_site() {
    local domain="$1"
    local aliases="$2"
    local vhost_src="$3"
    local marker="$4"
    local end_marker="$5"
    local cert_file="${DOCROOT}/.ssl/${domain}.crt"

    [[ -f "$vhost_src" ]] || { echo "HATA: $vhost_src bulunamadi." >&2; exit 1; }

    if [[ ! -f "$cert_file" ]]; then
        echo "HATA: ${domain} sertifikasi yok (${cert_file})." >&2
        exit 1
    fi

    echo "==> [${domain}] hosts kaydi"
    if grep -qE "^[^#]*[[:space:]]${domain}([[:space:]]|$)" "$HOSTS_FILE"; then
        echo "    zaten var, atlandi"
    else
        cp -a "$HOSTS_FILE" "${HOSTS_FILE}.bak.$(date +%Y%m%d%H%M%S)"
        printf '127.0.0.1    %s\n' "$aliases" >> "$HOSTS_FILE"
        echo "    eklendi"
    fi

    echo "==> [${domain}] Apache sanal sunucusu"
    cp -a "$VHOST_FILE" "${VHOST_FILE}.bak.$(date +%Y%m%d%H%M%S)"
    if grep -qF "$marker" "$VHOST_FILE"; then
        echo "    blok zaten var, guncelleniyor"
        local tmp
        tmp=$(mktemp)
        awk -v m="$marker" -v e="$end_marker" '
            $0 == m { skip = 1 }
            !skip   { print }
            $0 == e { skip = 0 }
        ' "$VHOST_FILE" > "$tmp"
        mv "$tmp" "$VHOST_FILE"
    fi

    {
        printf '\n%s\n' "$marker"
        cat "$vhost_src"
        printf '%s\n' "$end_marker"
    } >> "$VHOST_FILE"
    echo "    yazildi"
}

setup_site \
    "vizit-e.site" \
    "vizit-e.site www.vizit-e.site" \
    "${DOCROOT}/.ssl/vizit-e.site-vhost.conf" \
    "# >>> vizit-e.site (sgk-vizite) >>>" \
    "# <<< vizit-e.site (sgk-vizite) <<<"

setup_site \
    "admin.vizite.site" \
    "admin.vizite.site" \
    "${DOCROOT}/.ssl/admin.vizite.site-vhost.conf" \
    "# >>> admin.vizite.site (sgk-vizite/admin) >>>" \
    "# <<< admin.vizite.site (sgk-vizite/admin) <<<"

echo "==> Yapilandirma dogrulamasi"
if ! /opt/lampp/bin/httpd -t 2>&1 | tee /tmp/vizit-e-site-httpd-test.log | grep -q "Syntax OK"; then
    echo "HATA: Apache yapilandirmasi gecersiz:" >&2
    cat /tmp/vizit-e-site-httpd-test.log >&2
    echo "Yedekten geri almak icin: ${VHOST_FILE}.bak.* dosyalarina bakin." >&2
    exit 1
fi
echo "    Syntax OK"

echo "==> Apache yeniden baslatiliyor"
/opt/lampp/lampp reloadapache >/dev/null 2>&1 || /opt/lampp/lampp restartapache >/dev/null 2>&1
sleep 2
echo "    tamam"

echo
echo "Kurulum tamamlandi:"
echo "  - https://vizit-e.site/"
echo "  - https://admin.vizite.site/"
