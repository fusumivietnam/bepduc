#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT_DIR"

fail() {
  printf 'ERROR: %s\n' "$*" >&2
  exit 1
}

command -v composer >/dev/null 2>&1 || fail 'Composer is required.'
command -v php >/dev/null 2>&1 || fail 'PHP is required.'

composer validate --strict --no-check-lock --no-interaction

while IFS=$'\t' read -r package expected; do
  [[ -n "$package" ]] || continue

  actual="$(composer show "$package" --format=json | php -r '
    $data = json_decode(stream_get_contents(STDIN), true);
    if (!is_array($data) || empty($data["versions"][0])) {
        exit(2);
    }
    echo ltrim($data["versions"][0], "v");
  ')" || fail "Unable to resolve installed version for $package"

  [[ "$actual" == "$expected" ]] || fail "$package expected $expected, installed $actual"

done < <(php -r '
  $config = json_decode(file_get_contents("composer.json"), true);
  foreach (($config["require"] ?? []) as $package => $version) {
      if (strpos($package, "wpackagist-plugin/") === 0) {
          echo $package, "\t", $version, PHP_EOL;
      }
  }
')

tracked_third_party="$(git ls-files 'wp-content/plugins/**' | grep -v '^wp-content/plugins/bepduc-core/' || true)"
[[ -z "$tracked_third_party" ]] || fail "Third-party plugin source is tracked in Git:\n$tracked_third_party"

printf 'Public plugin dependencies verified.\n'
