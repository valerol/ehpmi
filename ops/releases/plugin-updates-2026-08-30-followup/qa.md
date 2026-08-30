# EHPMI dev plugin-update follow-up QA — 2026-08-30

Status: `P1 DEV PASS`

Recovery status: `QA-D NOT PASSED`; a fresh rollback artifact and instructions exist, but no isolated restoration rehearsal was performed.

## Trigger and finding

The user-provided WordPress Updates screenshot taken on `2026-08-30` showed `9` total updates and `2` plugin updates. Live WP-CLI inspection confirmed that the `2026-08-28` update had been applied and that two newer patch releases had subsequently become available:

- Advanced Custom Fields was installed at the previously recorded `6.8.8`, with `6.8.9` newly available.
- Breadcrumb NavXT was installed at the previously recorded `7.5.1`, with `7.5.2` newly available.

At the time of the screenshot, the total badge consisted of `2` plugin updates, `6` inactive bundled-theme updates, and `1` WordPress core update. This stage updates only the two plugins. WordPress core and all themes remain unchanged.

## Boundary

- Environment changed: `dev` only.
- URL: `https://dev.ehpmi.org`.
- Document root: `/home2/nykvymmy/dev.ehpmi.org`.
- Database: `nykvymmy_ehpmidev`.
- Production code and database changes: none.
- Newsletter sends and form submissions: none.
- WordPress remained at `7.0.4`; the active custom theme remained at `1.2`.

## Backup

- Pre-update database check: `PASS (53/53 tables)`.
- Artifact: `2026-08-30_074215Z_ehpmi-dev-before-plugin-followup.sql.gz`.
- Size: `640341` bytes.
- SHA-256: `0e6a3b263239033d7a8b300e8eda53327148089bccaee5d862243b6eb43e72ee`.
- Gzip integrity and `53` schema statements verified.
- Canonical copy: [Google Drive / database / dev](https://drive.google.com/file/d/117OP9GiafNlV0G7IxYbAYYZ1nIzzqoIU/view?usp=drivesdk).
- Drive metadata readback confirmed the exact filename, size, parent folder, and `application/gzip` MIME type.
- Machine-readable evidence: [`backup.yml`](backup.yml).

## Update result

| Plugin | Before | After | Result |
| --- | ---: | ---: | --- |
| Advanced Custom Fields | 6.8.8 | 6.8.9 | Updated; active |
| Breadcrumb NavXT | 7.5.1 | 7.5.2 | Updated; active |

Result: `2/2` successful. The final live plugin list reports `0` available plugin updates. Exact evidence is in [`plugin-versions.yml`](plugin-versions.yml).

## Server verification

- Maintenance mode is inactive.
- WP-Optimize cache purge returned `caches cleared`.
- Post-update database check: `PASS (53/53 tables)`.
- WordPress installation verifies against official core checksums; only the same pre-existing extra `error_log` files and `wp-cli.yml` are reported.
- WordPress.org plugin checksum verification: `PASS (13/13)`.
- Runtime smoke checks: `4` ACF groups, `3` managed hero slides, `2` Contact Form 7 forms, and Newsletter loaded.
- WP-CLI continues to emit deprecation notices from its bundled PHP libraries; no site or plugin fatal error was observed.

## Data preservation

Before and after counts are identical:

| Data | Before | After |
| --- | ---: | ---: |
| Newsletter subscribers | 172 | 172 |
| Newsletter sent records | 0 | 0 |
| Newsletter email records | 0 | 0 |
| WordPress posts | 692 | 692 |
| WordPress users | 2 | 2 |

## Browser and HTTP QA

- Homepage title, footer, three hero slides, Contact Form 7, and Newsletter form render successfully.
- Desktop browser error log is empty; document horizontal overflow is `0` px.
- Mobile `390 × 844`: menu toggle is present, forms remain within the viewport, footer is present, horizontal overflow is `0` px, and the browser error log is empty.
- HTTP `200`: `/`, `/robots.txt`, `/wp-login.php`, `/wp-json/`, `/wp-json/contact-form-7/v1`.
- A deliberately nonexistent route returns HTTP `404`.

No form was submitted during QA.

## Recovery

Pre-stage repository point: `7a41e6b7f3a88bb8a1fb6d64dcdf58e0dc838c04`.

1. Download the Drive backup and verify its SHA-256 and gzip integrity.
2. Confirm the working directory is `/home2/nykvymmy/dev.ehpmi.org`, `wp option get siteurl` is `https://dev.ehpmi.org`, and `wp config get DB_NAME` is `nykvymmy_ehpmidev`.
3. Activate maintenance mode.
4. Restore the previous plugin code:

   ```sh
   wp plugin install advanced-custom-fields --version=6.8.8 --force
   wp plugin install breadcrumb-navxt --version=7.5.1 --force
   ```

5. If database rollback is required, import the matching snapshot:

   ```sh
   gzip -cd 2026-08-30_074215Z_ehpmi-dev-before-plugin-followup.sql.gz | wp db import -
   ```

6. Purge WP-Optimize caches, deactivate maintenance mode, and repeat plugin versions, DB/core/plugin checksums, content counts, browser, form-rendering, and HTTP checks from this report.

The documented sequence has not been exercised in isolation; `QA-D PASS` is not claimed.
