#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=common.sh
source "$SCRIPT_DIR/common.sh"

load_env_file
require_cmd aws

usage() {
  printf 'Usage: %s <slug> <version> <zip-file>\n' "$0" >&2
  exit 2
}

[[ $# -eq 3 ]] || usage
slug="$1"
version="$2"
zip_file="$3"

[[ "$slug" =~ ^[a-z0-9][a-z0-9-]*$ ]] || fail 'Slug must contain lowercase letters, numbers and hyphens only.'
[[ -f "$zip_file" ]] || fail "ZIP not found: $zip_file"
[[ -n "${R2_BUCKET:-}" ]] || fail 'R2_BUCKET is required.'
[[ -n "${R2_ACCESS_KEY_ID:-}" ]] || fail 'R2_ACCESS_KEY_ID is required.'
[[ -n "${R2_SECRET_ACCESS_KEY:-}" ]] || fail 'R2_SECRET_ACCESS_KEY is required.'

export AWS_ACCESS_KEY_ID="$R2_ACCESS_KEY_ID"
export AWS_SECRET_ACCESS_KEY="$R2_SECRET_ACCESS_KEY"
export AWS_DEFAULT_REGION=auto

endpoint="$(r2_endpoint)"
r2_key="premium/$slug/$version/$slug-$version.zip"
checksum="$(sha256_file "$zip_file")"

if aws --endpoint-url "$endpoint" s3api head-object --bucket "$R2_BUCKET" --key "$r2_key" >/dev/null 2>&1; then
  fail "Artifact already exists and will not be overwritten: s3://$R2_BUCKET/$r2_key"
fi

printf 'Uploading %s %s to %s...\n' "$slug" "$version" "$r2_key"
aws --endpoint-url "$endpoint" s3 cp "$zip_file" "s3://$R2_BUCKET/$r2_key" --only-show-errors

printf '\nUpload complete. Add this reviewed entry to config/premium-plugins.json:\n\n'
printf '{\n'
printf '  "slug": "%s",\n' "$slug"
printf '  "version": "%s",\n' "$version"
printf '  "r2_key": "%s",\n' "$r2_key"
printf '  "sha256": "%s",\n' "$checksum"
printf '  "enabled": true,\n'
printf '  "activate": true\n'
printf '}\n'
