# Entity Stage 2 — dev accepted

Status: **ACCEPTED** on 2026-09-27. Code and guarded data migration are deployed only on dev. Automated data, HTTP, and DOM checks passed. The operator confirmed the authenticated Staff save/reload and desktop/390px visual acceptance gates. Production was not changed. Stage 3 was not started.

## Backup and preflight

- Target: `https://dev.ehpmi.org`, document root `/home2/nykvymmy/dev.ehpmi.org`, database `nykvymmy_ehpmidev`.
- Preceding Git commit: `13c6e44db889a59c05b9ee728e6c16f1e1deb13f` on `refactor/dev-2026-08-25`.
- The compressed pre-migration database backup passed remote and local gzip tests and SHA verification. Google Drive metadata readback confirmed its filename, MIME type, exact size, and parent folder. See `backup.yml`.
- The pre-write inventory froze all 20 Staff excerpts, the publication-date directory order, 27 Country relationship values, 22 Staff organization rows, bodies, featured images, and statuses.

## Applied migration

- Initial dry-run passed with exactly 62 expected writes and no mutation.
- Apply changed exactly 20 `position` values, 20 `_position` ACF companions, the Vietnam `country_role` value and companion for Staff 1516, and 20 `menu_order` values.
- Staff excerpts were retained byte-for-byte during verification. Country `leader`, `team`, and `member` values and Staff `organization` relationships remained byte-for-byte unchanged.
- Directory order is now deterministic: `188,176,257,166,757,551,236,193,911,261,249,246,252,234,240,183,2344,243,197,1516`.
- Guarded rollback dry-run passed against the migrated state. No actual rollback or isolated restore rehearsal was performed.

## Code and data verification

- Final verifier passed with `failures=[]`.
- Structured metadata counts are `_position=20`, `position=20`, `_country_role=1`, and `country_role=1`.
- ACF field order is `position`, `country_role`, `organization`; Staff supports the Admin Order control.
- The Staff directory reads the structured position and sorts by `menu_order` then ID. Country team queries preserve saved relationship order with `post__in`.
- Staff single and Country leader output use valid paragraph structure. Leader body content passes through WordPress content filters and permitted HTML. Stable card/leader IDs and image-alt fallback are present.
- Five final deployed SHA-256 values and the migration/verifier hashes are recorded in `manifest.yml`.

## Public HTTP and DOM checks

- `/about/staff/`: HTTP 200; Staff IDs exactly matched all 20 expected records in the frozen order.
- `/about/staff/petr-sharov/`: HTTP 200; structured position rendered as `President`.
- `/offices/vietnam/`: HTTP 200; `country-leader-1516` rendered with stored role `The Country Coordinator`.
- `/offices/georgia/`: HTTP 200; team IDs rendered in saved order `261,249,246,252`.
- Automated headless Chrome attempts on this Mac did not complete page capture reliably. Failed/stale capture artifacts were removed and are not treated as acceptance evidence. The operator subsequently completed and confirmed the desktop and 390px visual checks.

## Production isolation

- Read-only production check returned database `nykvymmy_ehpmi` and home URL `https://ehpmi.org`.
- Production contains zero Stage 2 structured Staff metadata rows and zero Staff records with nonzero `menu_order`.
- The queried production template files retained December 2025 modification times; production has no `ehpmi-core.php` or Staff ACF JSON at the dev-equivalent paths.
- No production write, deployment, cache flush, form submission, newsletter send, or plugin update was issued.

## Authenticated acceptance

- The operator confirmed an authenticated save and reload of Staff 1516 on dev. The Position / title, Country role, Organization, and Order values persisted.
- The operator confirmed responsive visual QA at ordinary desktop width and about 390px for all four routes listed below, with no visible regression, horizontal overflow, broken image, or reordered person.
- The immediately pre-confirmation automated verifier passed with failures=[]. A post-confirmation rerun was attempted once using direct SSH with BatchMode and IdentitiesOnly, but the server rejected authentication. No post-confirmation verifier result is claimed.
- These completed operator gates close Stage 2 acceptance. Stage 3 remains out of scope and was not started.

Use existing Staff 1516 on dev because it exercises both new text fields:

1. Open `https://dev.ehpmi.org/wp-admin/post.php?post=1516&action=edit`.
2. Confirm `Position / title` is `Vietnam Country Coordinator`, `Country role` is `The Country Coordinator`, `Organization` is still present, and Page Attributes exposes `Order` with value `200`.
3. Click **Update** without changing content, reload the edit screen, and confirm all values persist.
4. Open `/about/staff/`, `/about/staff/petr-sharov/`, `/offices/vietnam/`, and `/offices/georgia/` at ordinary desktop width and about 390px width. Confirm no visible regression, horizontal overflow, broken image, or reordered person.

The operator completed and confirmed these checks before Stage 2 was marked accepted. The final Git commit and push record the accepted implementation. Stage 3 was not started.

## Targeted rollback

- Original file copies are protected under `/home2/nykvymmy/backups/entity-stage2-2026-09-27/files/` with `.before-stage2` suffixes. Their source hashes are recorded in the task evidence.
- Prefer the guarded field-level rollback after reviewing any intervening Admin edits. Restoring the full database archive is the last resort because it would overwrite later dev changes.
