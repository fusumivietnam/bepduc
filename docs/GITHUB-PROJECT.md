# Delivery Project (GitHub Projects v2)

The board is a **live view of real GitHub issues and PRs**, not a project status file. Code truth: commits; work truth: issues/PRs; deployment truth: releases. Do not add STATUS.md.

## One-time owner setup

GitHub's current connected API integration cannot create user-owned Projects v2. The repository includes an idempotent official GitHub CLI script that performs the action with **your** GitHub login, without sharing credentials in chat:

1. Install [GitHub CLI](https://cli.github.com/) and `jq`.
2. Run `gh auth login` if not signed in; then `gh auth refresh -s project` to grant the required Projects scope.
3. Run `bash scripts/github/bootstrap-project.sh` from the repository clone.

The script looks for an existing **Bepduc.com Delivery** Project on `fusumivietnam`, creates it only if missing, links the repo, adds Priority and Area fields, and imports real repository issues (both open and completed; excludes abandoned test issues). It marks closed issues **Done**. It creates no duplicate local status data. A newly created Project is private by default; change its visibility through GitHub Settings if you intentionally want a public board.

Review in GitHub after setup:

- Native Status field: default Todo / In Progress / Done; enable built-in closed-item workflow to mark Done.
- Configure a GitHub Projects auto-add workflow scoped to `fusumivietnam/bepduc` issues so new issues enter the board automatically.
- Use Board view grouped by Status; Table view with Priority and Area; optionally Roadmap for milestones.
- Project is not deployment history; GitHub Releases are for shipped releases.

Only **close Issue #4** after the project URL exists and the import/automations have been checked.
