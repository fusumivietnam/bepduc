#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=common.sh
source "$SCRIPT_DIR/common.sh"

require_cmd jq
require_cmd wp
[[ -f "$MANIFEST_PATH" ]] || fail "Manifest not found: $MANIFEST_PATH"

WP_PATH="${WP_PATH:-$ROOT_DIR}"
"$SCRIPT_DIR/verify.sh"

count=0
while IFS= read -r plugin; do
  slug="$(jq -r '.slug' <<<"$plugin")"
  version="$(jq -r '.version' <<<"$plugin")"
  activate="$(jq -r '.activate' <<<"$plugin")"
  artifact="$(artifact_path "$slug" "$version")"

  printf 'Installing %s %s...\n' "$slug" "$version"
  if [[ "$activate" == "true" ]]; then
    wp --path="$WP_PATH" plugin install "$artifact" --force --activate
  else
    wp --path="$WP_PATH" plugin install "$artifact" --force
  fi
  count=$((count + 1))
done < <(jq -c '.plugins[] | select(.enabled == true)' "$MANIFEST_PATH")

printf 'Installed premium plugins: %d\n' "$count"
