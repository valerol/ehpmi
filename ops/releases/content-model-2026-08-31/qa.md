# Content model migration QA — dev — 2026-08-31

## Outcome

The dev-only content-model migration passed its executable and public-page checks. The former `partner` records are now `member`; the former `partner2` record is now `partner`. The obsolete `partner2` post type has no records and is no longer registered by the theme.

Production was read only and retained its original database, theme version and post-type counts. No Newsletter message or form submission was sent.

## Boundary and recovery point

- Dev URL: `https://dev.ehpmi.org`
- Dev database: `nykvymmy_ehpmidev`
- Production database verified separately: `nykvymmy_ehpmi`
- Pre-migration database backup: `2026-08-30_234525Z_ehpmi-dev-before-content-model-migration.sql.gz`
- Backup SHA-256: `4db8482661cdf366c3aa33143b2d55a5d1400135ef9581e0c0d26822c36be4c7`
- Drive copy: <https://drive.google.com/file/d/1PkXOh_Eh1JiK3ZjjyU2R9Ih91oq9InVq/view?usp=drivesdk>
- Backup gzip integrity, table count and Drive metadata readback: PASS

## Migration result

| Assertion | Before | After | Result |
|---|---:|---:|---|
| Member Organizations | 7 `partner` | 7 `member` | PASS |
| External Partner Organizations | 1 `partner2` | 1 `partner` | PASS |
| Obsolete `partner2` records | 1 | 0 | PASS |
| Country relation meta | 9 `partner/_partner` | 9 `member/_member` | PASS |
| Materials | 19 | 19 | PASS |
| Material thumbnails | 19 | 19 | PASS |
| Material file fields | 19 | 19 | PASS |
| Newsletter subscribers | 172 | 172 | PASS |
| Newsletter sends | 0 | 0 | PASS |

`member` and `partner` are visible in WordPress Admin but are not publicly queryable and have no archive or rewrite rules. `material` retains title, thumbnail and file management; the unused free-text editor is disabled.

## Public browser QA

Checked in the site browser at `1280x720`, DPR 2:

- home page: 7 member carousel items, 3 hero slides, footer present, no horizontal overflow;
- `/about/members/`: 7 member organization entries;
- `/about/partners/`: 1 TerraGraphics International Foundation entry;
- `/offices/` and all 9 country pages: navigation and stored member relations render; the 7 linked organizations appear on their assigned country pages;
- Action Plans, Publications and Videos category pages render their material lists;
- old and new single-record URL patterns return HTTP 404 as required for admin-managed organization data;
- browser console: 0 errors and 0 warnings in the checked pages.

The browser runtime exposed no viewport-emulation capability, so this run did not repeat the earlier mobile visual baseline. No authenticated WordPress Admin browser session was available: screen registration was verified through WordPress itself, while the edit/save round-trip remains `EH-D011`.

## Technical QA

- database check: PASS, all 53 tables;
- WordPress 7.1 core checksum verification: PASS;
- plugin checksum verification: PASS, 13 of 13;
- available plugin updates: 0;
- maintenance mode after release: inactive;
- theme version: 1.3;
- local theme equals deployed dev theme: PASS, 37 of 37 file SHA-256 values;
- migration verification script: PASS.

Core checksum verification reported only extra operational files: `wp-cli.yml` and five pre-existing `error_log` files dated October/December 2025. No WordPress-distributed file failed its checksum.

## Production non-change evidence

Read-only production verification after the dev migration returned:

- URL: `https://ehpmi.org`;
- database: `nykvymmy_ehpmi`;
- theme version: 1.2;
- `partner`: 7;
- `partner2`: 1;
- `member`: 0.

## Stage rollback procedure

This is the scoped rollback for this stage; the complete site recovery procedure remains in `docs/EHPMI_DOMAIN_PROTOCOL.md`.

1. Enable maintenance mode on `dev.ehpmi.org`.
2. Restore the theme from commit `fdd56104e0d4b419deb9493179514c842d3b220c` and verify its file manifest before continuing.
3. Download the named Drive backup and verify its SHA-256 value.
4. Import the uncompressed SQL into `nykvymmy_ehpmidev` only.
5. Flush WordPress rewrite rules and the active dev caches.
6. Disable maintenance mode.
7. Repeat database, checksum and public-page QA and record the recovery evidence.

Rollback was prepared but not executed. Therefore `EH-D007` remains open until the isolated QA-D recovery rehearsal passes.
