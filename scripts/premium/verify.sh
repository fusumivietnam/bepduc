#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=common.sh
source "$SCRIPT_DIR/common.sh"

require_cmd jq
[[ -f "$MANIFEST_PATH" ]] || fail "Manifest not found: $MANIFEST_PATH"

count=0
while IFS= read -r plugin; do
  slug="$(jq -r '.slug' <<<"$plugin")"
  version="$(jq -r '.version' <<<"$plugin")"
  expected_sha="$(jq -r '.sha256' <<<"$plugin")"
  artifact="$(artifact_path "$slug" "$version")"

  [[ -f "$artifact" ]] || fail "Artifact missing: $artifact"
  verify_sha256 "$artifact" "$expected_sha"
  printf 'OK %s %s\n' "$slug" "$version"
  count=$((count + 1))
done < <(jq -c '.plugins[] | select(.enabled == true)' "$MANIFEST_PATH")

printf 'Verified premium artifacts: %d\n' "$count"
