# EHPMI dev refactor QA — 2026-08-26

Status: `P1 DEV PASS`

Recovery status: `QA-D NOT PASSED`; no isolated restoration rehearsal was performed in this stage.

## Boundary

- Environment changed: `dev` only.
- Dev document root: `/home2/nykvymmy/dev.ehpmi.org`.
- Dev database: `nykvymmy_ehpmidev`.
- Production code/database changes: none.
- Newsletter sends: none.

## Pre-change database backup

- File: `2026-08-25_215736Z_ehpmi-dev-database.sql.gz`.
- Google Drive file ID: `1gprY5hgJ0td7QsMj-0bsSOvRBegYfLTj`.
- Compressed size: `376698` bytes.
- SHA-256: `4ee156d72be1ec2d5916668670c97b3930eee34195aed2d00cf00668304540e9`.
- Integrity: gzip test passed; SQL contains `53` `CREATE TABLE` statements.

## Hero content migration

- Created three published `hero_slide` posts with menu order `10`, `20`, and `30`.
- Post/attachment pairs: `2370/2369`, `2372/2371`, and `2374/2373`.
- Added stable `_ehpmi_seed_source` markers so the migration is idempotent.
- A second migration run reused the same six IDs and reported only `updated`; no duplicates were created.
- The original image bytes were preserved in Media Library. Attachment SHA-256 values match the three theme source assets.
- The homepage now renders the three slides from `/wp-content/uploads/2026/08/` with descriptive English alt text. The theme-level fallback remains available if no published managed slide has a featured image.

## Bootstrap alignment

- Aligned Bootstrap CSS and JavaScript on version `5.1.3`.
- Replaced Bootstrap 4 JavaScript plus standalone Popper with the Bootstrap 5 bundle.
- Recomputed the bundle SHA-384 directly from the pinned jsDelivr asset and applied the matching SRI value.
- Updated collapse and dropdown data attributes to the Bootstrap 5 `data-bs-*` API.
- Replaced the removed jQuery Bootstrap initialization calls with Bootstrap 5 component instances.
- jQuery remains intentionally limited to existing effects and OwlCarousel in this stage.

## Verification

- Local PHP syntax, JavaScript syntax, and `git diff --check` passed.
- Deployed PHP syntax passed on the server.
- The rendered homepage contains Bootstrap bundle `5.1.3`, the verified SRI value, and no Bootstrap 4 or standalone Popper tag.
- Desktop dropdown opens and closes through Bootstrap 5.
- Hero carousel automatically advances and contains exactly three managed slides.
- Latest-news OwlCarousel reports `owl-loaded`.
- Mobile navigation uses only `data-bs-toggle` / `data-bs-target`, opens, sets `aria-expanded="true"`, and exposes the menu.
- Homepage returns HTTP `200`.
- A nonexistent route returns HTTP `404`, renders `Page not found`, includes the footer, and has no browser errors.
- Homepage and 404 browser error logs are empty.
- All `53` theme files match between Git worktree and dev by SHA-256.
- WordPress Admin redirects unauthenticated browser access to the normal login screen; no credentials were entered. The post and attachment records were verified directly through WordPress CLI.

## Visual reference

Desktop, 1280 × 720:

![EHPMI Bootstrap 5 desktop](visual/home-desktop-1280x720.png)

The accepted logo, navigation, hero copy, image crop, contact button, spacing, and color treatment remain consistent with the previous accepted visual baseline. Mobile rendering was verified behaviorally at 390 × 844; the accepted mobile visual reference remains in the previous refactor QA package.

## Deferred after this stage

- Perform an authenticated admin edit round-trip for one hero slide when operator credentials are available; the current managed records themselves are complete.
- Remove demonstrably unused legacy backup/minified artifacts in a separate checksum-controlled cleanup.
- Replace OwlCarousel and remaining jQuery-dependent effects in a separate visual-QA stage.
- Update plugins only in a separate commit with before/after dev QA.
- Run the isolated recovery rehearsal required for `QA-D PASS` before protocol `v1.0.0`.
