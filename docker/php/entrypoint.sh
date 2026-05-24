#!/bin/sh
set -e

# Dev-time UID/GID matching for Linux bind mounts.
#
# On Linux, bind-mounted files keep their host UID/GID inside the container,
# so php-fpm running as the baked-in www-data (uid 82) cannot write to
# storage/ or bootstrap/cache/ when the host owner is, say, uid 1000. We
# read the owner of the mount at startup and rewrite the FPM pool's
# user/group to match, so the same image works on any host without a
# rebuild. On Docker Desktop the mount layer translates UIDs to the
# container user already, so this becomes a no-op.

TARGET_DIR=/var/www/html

if [ -d "$TARGET_DIR" ]; then
    HOST_UID=$(stat -c '%u' "$TARGET_DIR")
    HOST_GID=$(stat -c '%g' "$TARGET_DIR")

    if [ "$HOST_UID" != "0" ]; then
        if id -u www-data >/dev/null 2>&1; then
            CURRENT_UID=$(id -u www-data)
            CURRENT_GID=$(id -g www-data)

            if [ "$CURRENT_UID" != "$HOST_UID" ] || [ "$CURRENT_GID" != "$HOST_GID" ]; then
                # Alpine ships busybox `deluser`/`addgroup`; recreate www-data
                # with the host's uid/gid so file ownership lines up. We
                # don't have shadow installed, so this is the lightest path.
                deluser www-data 2>/dev/null || true
                delgroup www-data 2>/dev/null || true
                addgroup -g "$HOST_GID" www-data
                adduser -D -H -G www-data -u "$HOST_UID" www-data
            fi
        fi

        FPM_POOL=/usr/local/etc/php-fpm.d/www.conf
        if [ -f "$FPM_POOL" ]; then
            sed -i "s/^user = .*/user = www-data/"   "$FPM_POOL"
            sed -i "s/^group = .*/group = www-data/" "$FPM_POOL"
        fi
    fi
fi

# Workers (`php artisan queue:work`) and any other non-fpm command must run
# as the matched user too; fpm drops privileges from its master itself.
if [ "$1" != "php-fpm" ] && [ "$(id -u)" = "0" ] && id -u www-data >/dev/null 2>&1; then
    exec su-exec www-data "$@"
fi

exec "$@"
