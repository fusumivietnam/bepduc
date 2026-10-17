# Development

## Runtime baseline

- WordPress 6.6+ (required for `theme.json` version 3 support).
- PHP 8.1+.
- WooCommerce installed as a third-party dependency; approved public-plugin versions are tracked separately from application source.
- `bepduc` is the custom block theme.
- `bepduc-core` is the project-specific domain plugin.

## Repository scope

This repository is **not** a full WordPress distribution. Do not commit WordPress core or third-party themes/plugins.

Recommended local setup:

1. Install WordPress in a local runtime of your choice.
2. Point/symlink `wp-content/themes/bepduc` from this repository into that runtime.
3. Point/symlink `wp-content/plugins/bepduc-core` from this repository into that runtime.
4. Install approved public and premium dependencies using the project dependency tooling.
5. Activate `bepduc` and `bepduc-core`.

Example with WP-CLI when `WP_PATH` is your WordPress root:

```bash
wp --path="$WP_PATH" theme activate bepduc
wp --path="$WP_PATH" plugin activate bepduc-core
```

## Environment types

Use WordPress environment types consistently:

- local: development machine
- staging: integration/QA environment
- production: live bepduc.com

Production is not an authoring environment for code, templates, plugin dependencies or structural configuration.

## Data

Use seed/sanitized data for development. Never copy customer/order/payment data into the repository.

## WooCommerce templates

Do not copy WooCommerce templates into the theme unless a real design requirement cannot be met through block templates, global styles, hooks or supported extension points. Every override increases upgrade maintenance.
