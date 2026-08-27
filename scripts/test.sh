#!/bin/sh
set -eu

REPO_ROOT=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
cd "$REPO_ROOT"

for file in $(find . -path './.git' -prune -o -name '*.php' -print); do
  php -l "$file" >/dev/null
done

sh -n docker/entrypoint.sh
node --check captcha/captcha.js
node --check scripts/popup.js
node --check scripts/show_hide.js
node --check scripts/bbcode2text.js
node --check scripts/rules.js
if grep -RInE --include='*.php' --include='*.js' 'document\\.selection|ActiveXObject|navigator\\.appVersion|document\\.all' . | grep -v '^./tests/'; then
  echo 'Removed browser APIs detected.' >&2
  exit 1
fi
[ ! -e lang/en/videoformats.php ]
grep -q "videoformats_dupe_body" lang/en/lang_videoformats.php
grep -q "video-formats-grid" videoformats.php
php tests/security_auth_test.php
php tests/bencode_test.php
php tests/captcha_test.php
php tests/cache_test.php
php tests/theme_assets_test.php
php tests/signup_schema_test.php
php tests/upload_filename_test.php
php tests/bbcode_image_test.php
php tests/announce_schema_test.php
php tests/client_ip_test.php
git diff --check

echo "Static and security checks passed."

if [ "${TBDEV_RUN_DOCKER:-0}" = "1" ]; then
  if ! command -v docker >/dev/null 2>&1; then
    echo "TBDEV_RUN_DOCKER=1 but Docker is unavailable." >&2
    exit 2
  fi
  if [ ! -f .env.lab ]; then
    echo "Create .env.lab from .env.lab.example before Docker validation." >&2
    exit 2
  fi
  docker compose --env-file .env.lab -f compose.lab.yml config --quiet
fi
