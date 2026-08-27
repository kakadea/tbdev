#!/bin/sh
set -eu

REPO_ROOT=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
cd "$REPO_ROOT"

for file in $(find . -path './.git' -prune -o -name '*.php' -print); do
  php -l "$file" >/dev/null
done

sh -n docker/entrypoint.sh
php tests/security_auth_test.php
php tests/bencode_test.php
php tests/captcha_test.php
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
