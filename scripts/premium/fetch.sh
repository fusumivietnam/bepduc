#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=common.sh
source "$SCRIPT_DIR/common.sh"

load_env_file
require_cmd jq
require_cmd aws

[[ -f "$MANIFEST_PATH" ]] || fail "Manifest not found: $MANIFEST_PATH"
[[ -n "${R2_BUCKET:-}" ]] || fail 'R2_BUCKET is required.'
[[ -n "${R2_ACCESS_KEY_ID:-}" ]] || fail 'R2_ACCESS_KEY_ID is required.'
[[ -n "${R2_SECRET_ACCESS_KEY:-}" ]] || fail 'R2_SECRET_ACCESS_KEY is required.'

export AWS_ACCESS_KEY_ID="$R2_ACCESS_KEY_ID"
export AWS_SECRET_ACCESS_KEY="$R2_SECRET_ACCESS_KEY"
export AWS_DEFAULT_REGION=auto

mkdir -p "$ARTIFACT_DIR"
endpoint="$(r2_endpoint)"
count=0

while IFS= read -r plugin; do
  slug="$(jq -r '.slug' <<<"$plugin")"
  version="$(jq -r '.version' <<<"$plugin")"
  r2_key="$(jq -r '.r2_key' <<<"$plugin")"
  expected_sha="$(jq -r '.sha256' <<<"$plugin")"
  destination="$(artifact_path "$slug" "$version")"

  printf 'Fetching %s %s...\n' "$slug" "$version"
  aws --endpoint-url "$endpoint" s3 cp "s3://$R2_BUCKET/$r2_key" "$destination" --only-show-errors
  verify_sha256 "$destination" "$expected_sha"
  printf 'Verified %s\n' "$destination"
  count=$((count + 1))
done < <(jq -c '.plugins[] | select(.enabled == true)' "$MANIFEST_PATH")

printf 'Premium artifacts ready: %d\n' "$count"
