# Public WordPress Plugin Dependencies

Public plugins from WordPress.org are installed reproducibly through Composer + WPackagist. Third-party plugin source is not committed to this repository.

## Approved public plugins

The authoritative versions live in `composer.json` and `composer.lock`.

Current direct dependencies:

- WooCommerce
- Rank Math SEO
- FluentSMTP

Premium plugins are managed separately through the Cloudflare R2 registry described in `docs/PREMIUM-PLUGINS.md`.

## Install

From the repository root:

```bash
bash scripts/public-plugins/install.sh
```

This runs Composer with production-oriented flags and installs WordPress plugins into `wp-content/plugins/<slug>/` via `composer/installers`.

## Verify

```bash
bash scripts/public-plugins/verify.sh
```

Verification checks:

- `composer.json` validity;
- installed public-plugin versions match the approved direct constraints;
- no third-party plugin source is tracked in Git.

## Upgrade process

Do not update public plugins directly on production and leave GitHub behind.

1. Check the target plugin release and compatibility.
2. Change the exact version in `composer.json` on a feature/dependency branch.
3. Run `composer update <package>` to update `composer.lock`.
4. Run install + verify locally or rely on the dependency CI.
5. Test the update on staging.
6. Review and merge the version diff through a PR.
7. Deploy using `composer install`, never `composer update`, on production.

Example:

```bash
composer update wpackagist-plugin/woocommerce --with-dependencies
bash scripts/public-plugins/verify.sh
```

## Version policy

Direct WordPress plugins are pinned to exact versions rather than `^`, `~` or `*`. This makes upgrades explicit and reviewable.

`composer.lock` freezes the full dependency resolution used by deployments. Production should install from the lock file.

## Adding a public plugin

Before adding one, confirm WordPress core, WooCommerce core or small code in `bepduc-core` cannot reasonably provide the capability.

Then:

1. Add the WordPress.org package to `composer.json` as `wpackagist-plugin/<slug>` with an exact version.
2. Run Composer update for only that package.
3. Verify the plugin installs in `wp-content/plugins/<slug>/`.
4. Document a durable architectural reason only when the dependency changes project architecture or ownership boundaries.
5. Submit the change through an Issue + PR.

## Removing a plugin

Remove it from `composer.json`, update `composer.lock`, and verify no project code depends on its APIs before deployment.
