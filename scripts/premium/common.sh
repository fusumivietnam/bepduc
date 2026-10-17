#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
# shellcheck disable=SC2034
MANIFEST_PATH="${PREMIUM_MANIFEST:-$ROOT_DIR/config/premium-plugins.json}"
# shellcheck disable=SC2034
ARTIFACT_DIR="${PREMIUM_ARTIFACT_DIR:-$ROOT_DIR/.cache/premium-plugins}"

fail() {
  printf 'ERROR: %s\n' "$*" >&2
  exit 1
}

require_cmd() {
  command -v "$1" >/dev/null 2>&1 || fail "Required command not found: $1"
}

load_env_file() {
  if [[ -f "$ROOT_DIR/.env" ]]; then
    set -a
    # shellcheck disable=SC1091
    source "$ROOT_DIR/.env"
    set +a
  fi
}

r2_endpoint() {
  if [[ -n "${R2_ENDPOINT:-}" ]]; then
    printf '%s' "$R2_ENDPOINT"
    return
  fi

  [[ -n "${R2_ACCOUNT_ID:-}" ]] || fail 'R2_ACCOUNT_ID is required when R2_ENDPOINT is not set.'
  printf 'https://%s.r2.cloudflarestorage.com' "$R2_ACCOUNT_ID"
}

artifact_path() {
  local slug="$1"
  local version="$2"
  printf '%s/%s-%s.zip' "$ARTIFACT_DIR" "$slug" "$version"
}

sha256_file() {
  local file="$1"
  if command -v sha256sum >/dev/null 2>&1; then
    sha256sum "$file" | awk '{print $1}'
  elif command -v shasum >/dev/null 2>&1; then
    shasum -a 256 "$file" | awk '{print $1}'
  else
    fail 'Neither sha256sum nor shasum is available.'
  fi
}

verify_sha256() {
  local file="$1"
  local expected="$2"
  local actual actual_norm expected_norm
  actual="$(sha256_file "$file")"
  actual_norm="$(printf '%s' "$actual" | tr '[:upper:]' '[:lower:]')"
  expected_norm="$(printf '%s' "$expected" | tr '[:upper:]' '[:lower:]')"
  [[ "$actual_norm" == "$expected_norm" ]] || fail "SHA256 mismatch for $file (expected $expected, got $actual)"
}
