#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT_DIR"

if ! command -v composer >/dev/null 2>&1; then
  printf 'ERROR: Composer is required.\n' >&2
  exit 1
fi

composer install \
  --no-dev \
  --no-interaction \
  --no-progress \
  --prefer-dist

printf 'Public WordPress plugin dependencies installed.\n'
