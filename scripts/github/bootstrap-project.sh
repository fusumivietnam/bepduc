#!/usr/bin/env bash
# First-time bootstrap of a GitHub Projects v2 board; run with gh auth -s project.
# Project items are real GitHub Issues. This script does not create a status file.
set -euo pipefail

OWNER="fusumivietnam"
REPOSITORY="bepduc"
TITLE="Bepduc.com Delivery"

for command in gh jq; do
  command -v "$command" >/dev/null 2>&1 || { echo "Missing tool: $command" >&2; exit 1; }
done

gh auth status >/dev/null
projects_json="$(gh project list --owner "$OWNER" --limit 100 --format json)"
project_number="$(jq -r --arg title "$TITLE" '.projects[] | select(.title == $title) | .number' <<<"$projects_json" | head -n 1)"

if [[ -z "$project_number" ]]; then
  echo "Creating Projects v2 board: $TITLE"
  created="$(gh project create --owner "$OWNER" --title "$TITLE" --format json)"
  project_number="$(jq -r '.number' <<<"$created")"
  [[ "$project_number" =~ ^[0-9]+$ ]] || { echo "Cannot parse project number." >&2; exit 1; }
  gh project edit "$project_number" --owner "$OWNER" --visibility PRIVATE
  gh project edit "$project_number" --owner "$OWNER" --description "Single live delivery board for bepduc.com; track GitHub Issues and linked PRs, not duplicate Markdown status."
else
  echo "Using existing project #$project_number"
fi

# A project can be linked to a public repository even when its visibility is private.
gh project link "$project_number" --owner "$OWNER" --repo "$REPOSITORY" || echo "Project link may already exist; verify it in GitHub Settings."

ensure_field() {
  local name="$1" choices="$2"
  local fields
  fields="$(gh project field-list "$project_number" --owner "$OWNER" --format json)"
  if ! jq -e --arg name "$name" '.fields[] | select(.name == $name)' <<<"$fields" >/dev/null; then
    gh project field-create "$project_number" --owner "$OWNER" --name "$name" --data-type SINGLE_SELECT --single-select-options "$choices"
  fi
}

# Status is a built-in Projects field. Keep one canonical status, not another one.
ensure_field "Priority" "P0,P1,P2"
ensure_field "Area" "Foundation,Catalogue,Theme,Content,Conversion,Infrastructure,SEO"
fields="$(gh project field-list "$project_number" --owner "$OWNER" --format json)"
if ! jq -e '.fields[] | select(.name == "Status")' <<<"$fields" >/dev/null; then
  echo "Missing native Status field. Add it from the GitHub Project UI." >&2
  exit 1
fi

echo "Importing real repository issues (completed and active)."
# Ignore PRs, abandoned accidental tooling issues, and closed-not-planned work.
gh api --paginate "repos/$OWNER/$REPOSITORY/issues?state=all&per_page=100" \
  --jq '.[] | select(.pull_request == null and .state_reason != "not_planned" and .title != "DO NOT USE") | [.html_url, .state] | @tsv' |
while IFS=$'\t' read -r issue_url issue_state; do
  [[ -n "$issue_url" ]] || continue
  gh project item-add "$project_number" --owner "$OWNER" --url "$issue_url" >/dev/null
  if [[ "$issue_state" == "closed" ]]; then
    # On the default Projects board, Done is the native completed state.
    gh project item-edit "$project_number" --owner "$OWNER" --url "$issue_url" --field Status --value Done >/dev/null
  fi
done

echo
echo "Project ready: https://github.com/users/$OWNER/projects/$project_number"
echo "In GitHub Project settings: enable auto-add from $OWNER/$REPOSITORY and workflows to mark merged/closed issues Done."
echo "Use the built-in Board/Table views; filter by Area and Priority rather than maintaining STATUS.md."
