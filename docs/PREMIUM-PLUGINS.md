# Premium Plugin Registry

Premium plugin binaries are stored in a private Cloudflare R2 bucket. This public repository stores only approved metadata and deployment tooling.

## Responsibilities

- **R2**: private immutable ZIP artifacts.
- **`config/premium-plugins.json`**: project-approved plugin versions and SHA256 checksums.
- **GitHub**: review/history of dependency changes.
- **Environment / secret manager**: R2 credentials and plugin license keys.

## R2 object convention

Use immutable versioned keys:

```text
premium/<plugin-slug>/<version>/<plugin-slug>-<version>.zip
```

Example:

```text
premium/acf-pro/6.4.2/acf-pro-6.4.2.zip
```

Never overwrite an existing versioned object. Upload a new version under a new key.

## Manifest entry

```json
{
  "slug": "acf-pro",
  "version": "6.4.2",
  "r2_key": "premium/acf-pro/6.4.2/acf-pro-6.4.2.zip",
  "sha256": "0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef",
  "enabled": true,
  "activate": true,
  "notes": "Approved after staging verification"
}
```

The checksum above is an example only. Always calculate it from the exact uploaded ZIP.

## Required local/server tools

- `jq`
- AWS CLI v2 (R2 is S3-compatible)
- `wp` (WP-CLI) for installation
- `sha256sum` or `shasum`

## Environment

Copy `.env.example` to `.env` outside source control and set:

```text
R2_ACCOUNT_ID
R2_BUCKET
R2_ACCESS_KEY_ID
R2_SECRET_ACCESS_KEY
```

Use credentials scoped to the premium-artifact bucket/prefix only. Do not use a global Cloudflare API token.

## Publish an artifact

```bash
bash scripts/premium/publish.sh acf-pro 6.4.2 /path/to/acf-pro.zip
```

The publish helper:

1. validates the slug and local ZIP;
2. calculates SHA256;
3. refuses to overwrite an existing versioned R2 object;
4. uploads to the standard immutable key;
5. prints the manifest entry to review and commit.

It intentionally does **not** edit `config/premium-plugins.json` automatically. The version bump remains an explicit Git review step.

## Fetch and verify

```bash
bash scripts/premium/fetch.sh
bash scripts/premium/verify.sh
```

`fetch.sh` fails if the downloaded artifact does not match the manifest SHA256.

## Install

Set `WP_PATH` when WordPress is not at repository root:

```bash
WP_PATH=/var/www/bepduc/current bash scripts/premium/install.sh
```

Installation always verifies artifacts first.

## Upgrade process

1. Obtain the new plugin ZIP from the licensed vendor.
2. Run `publish.sh` to calculate SHA256 and upload to a **new immutable R2 key**.
3. Add/update the printed entry in `config/premium-plugins.json` through a PR.
4. CI validates the manifest and scripts.
5. Test the new version on staging.
6. Merge the approved version bump.
7. Deploy to production.

Do not auto-update premium plugins on production independently of the manifest.

## License policy

Artifacts may only be used for projects/sites allowed by the corresponding vendor license. License keys and vendor credentials are never stored in this public repository or embedded in ZIP files.
