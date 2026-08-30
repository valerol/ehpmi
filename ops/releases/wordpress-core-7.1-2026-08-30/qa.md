# EHPMI dev WordPress 7.1 QA — 2026-08-30

Status: `P1 DEV PASS`

Recovery status: `QA-D NOT PASSED`; the rollback artifact and procedure are recorded, but no isolated restoration rehearsal was performed.

## Boundary

- Environment changed: `dev` only.
- URL: `https://dev.ehpmi.org`.
- Document root: `/home2/nykvymmy/dev.ehpmi.org`.
- Database: `nykvymmy_ehpmidev`.
- WordPress Core: `7.0.4 → 7.1`.
- PHP remained `8.1.34`.
- Plugins, active custom theme, inactive bundled themes, production, and Newsletter delivery were not changed.
- No contact or subscription form was submitted.

## Preflight and backup

- Git branch `refactor/dev-2026-08-25` was clean at `bbb68d9ef49fdef70af694801911de6c44772c68` and matched its remote tracking branch.
- WordPress.org reported stable Core `7.1` as the available major update.
- The `7.0.4` installation passed official Core checksums before the update; only the known extra `error_log` files and `wp-cli.yml` were reported.
- Plugins had no available updates; the active theme was `ehpmi 1.2`.
- Pre-update database check: `PASS (53/53 tables)`.
- Database backup: `2026-08-30_081434Z_ehpmi-dev-before-wordpress-7.1.sql.gz`, `640281` bytes.
- SHA-256: `9ec4e906b34e5a0339346841e31c4f81d1d9c7ac30d00f010446a4877de8bcb8`.
- Gzip integrity and `53` schema statements verified.
- Canonical copy: [Google Drive / database / dev](https://drive.google.com/file/d/1qxke-5LaTrqtENTfkfYWUyo6mgTQEIDv/view?usp=drivesdk).
- Drive metadata readback confirmed the exact filename, size, parent folder, and `application/gzip` MIME type.
- Machine-readable evidence: [`backup.yml`](backup.yml).

## Update result

- Maintenance mode was activated before the update. Core itself deactivated it during completion; final status confirms it is inactive.
- WordPress Core update to `7.1` completed successfully from the official package.
- `wp core update-db` reported that database version `61833` was already current; no schema migration was required.
- `wp core check-update` reports WordPress is at the latest version.
- Exact runtime evidence: [`manifest.yml`](manifest.yml).

## Server verification

- WP-Optimize cache purge returned `caches cleared`.
- WordPress `7.1` passes official Core checksums. The same known extra files remain: five `error_log` files and `wp-cli.yml`; no Core checksum mismatch exists.
- WordPress.org plugin checksum verification: `PASS (13/13)`.
- All ordinary plugins remained active with unchanged versions and no available updates.
- Active custom theme remained `ehpmi 1.2`; six inactive bundled-theme updates remain deliberately deferred.
- Post-update database check: `PASS (53/53 tables)`.
- Runtime smoke checks: `4` ACF groups, `3` managed hero slides, `2` Contact Form 7 forms, and Newsletter loaded.
- Recent WordPress error logs contain no PHP fatal, parse, warning, or uncaught-exception entry from this stage.
- WP-CLI still emits deprecation notices from its own bundled PHP libraries; these are not site/plugin runtime failures.

## Data preservation

Before and after counts are identical:

| Data | Before | After |
| --- | ---: | ---: |
| Newsletter subscribers | 172 | 172 |
| Newsletter sent records | 0 | 0 |
| Newsletter email records | 0 | 0 |
| WordPress posts | 692 | 692 |
| WordPress users | 2 | 2 |

## Browser, HTTP, and administrative QA

### Desktop

- Homepage title, footer, three hero slides, Contact Form 7, Newsletter form, and both native carousels render successfully.
- Latest-news Next changes the position from `Items 1–3 of 13` to `Items 2–4 of 13`.
- Desktop `Who we are` dropdown opens and reports `aria-expanded=true`.
- Document horizontal overflow is `0` px and the browser error log is empty.

### Mobile — 390 × 844

- Latest news shows one overlapping item and reports `Items 1–1 of 13`.
- Mobile menu opens, becomes `display:flex`, and changes `aria-expanded` from `false` to `true`.
- Contact and Newsletter forms remain within the viewport; horizontal overflow is `0` px.
- Footer is present and the browser error log is empty.

### Routes and admin context

- HTTP `200`: `/`, `/robots.txt`, `/wp-login.php`, `/wp-json/`, `/wp-json/contact-form-7/v1`.
- A deliberately nonexistent route returns HTTP `404`, renders `Page not found`, includes the footer, and has no browser errors.
- An existing administrator retains `manage_options` and `edit_posts` capability.
- Authenticated read-only editor REST checks return HTTP `200` for post-type editing, settings editing, and current-user editing contexts.
- ACF exposes all `4` field groups in administrator context.
- The available test browser had no authenticated WordPress session. No admin edit/save round-trip was performed and no credentials or temporary login mechanism were created.

## Recovery

Pre-stage repository point: `bbb68d9ef49fdef70af694801911de6c44772c68`.

1. Download the Drive backup and verify its SHA-256 and gzip integrity.
2. Confirm the working directory is `/home2/nykvymmy/dev.ehpmi.org`, `wp option get siteurl` is `https://dev.ehpmi.org`, and `wp config get DB_NAME` is `nykvymmy_ehpmidev`.
3. Activate maintenance mode.
4. Restore the previous Core version from the official WordPress package:

   ```sh
   wp core update --version=7.0.4 --force
   wp core verify-checksums --version=7.0.4
   ```

5. For exact pre-stage database state, import the matching snapshot:

   ```sh
   gzip -cd 2026-08-30_081434Z_ehpmi-dev-before-wordpress-7.1.sql.gz | wp db import -
   ```

6. Run `wp core update-db`, purge WP-Optimize caches, deactivate maintenance mode, and repeat Core/plugin versions, DB/Core/plugin checksums, content counts, administrator-context REST checks, browser/form/carousel/menu tests, and HTTP routes from this report.

The rollback sequence has not been exercised in isolation, and the authenticated admin write round-trip remains unperformed. `QA-D PASS` is not claimed.
