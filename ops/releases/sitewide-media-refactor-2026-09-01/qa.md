# Site-wide media refactor QA — dev — 2026-09-01

## Outcome

The approved image-layout and accessibility refactor passed dev-only data, frontend, desktop and mobile checks. Production remained read only and unchanged.

## Scope

- 44 published Pages, Projects and Blog posts containing editorial images, excluding the separately completed Naiman project `761`.
- 245 Core Image blocks and 25 images embedded in Core Media & Text blocks.
- 81 two-image Core Columns compositions.
- 30 maps, diagrams, charts and organizational graphics that require readable wide layout without crop.
- Featured-image metadata used by cards and archive listings.

No file in `wp-content/uploads` was renamed, converted, regenerated or deleted.

## Backup

The database was exported before the first write and verified locally:

- archive: `2026-09-01_120025Z_ehpmi-dev-before-sitewide-media-refactor.sql.gz`;
- bytes: `430624`;
- SHA-256: `7d2cc288ae0f9ae6e76d47c1ab5b6a75c6cee2f11b84740cb36bf8138de36ac9`;
- `gzip -t`: PASS;
- `CREATE TABLE` statements: `53`;
- Drive readback: filename, parent folder, MIME type and size matched.

## Migration results

Base migration:

- 40 records changed;
- 112 attachment-backed and 12 legacy Core Image block alts added;
- 112 contextual attachment alts written;
- 22 existing block alts synchronized to attachment metadata;
- 81 pairs assigned `4:3`, `3:2` or `3:4` styles;
- 30 technical images assigned the wide style.

Embedded-media follow-up discovered by rendered-page QA:

- 11 records changed;
- 17 Core Media & Text block alts added and synchronized to attachment metadata;
- 6 additional featured-image metadata alts added;
- one filename-like attachment alt was replaced by a contextual description;
- final marker: `2026-09-01-v2`.

The final verifier reported:

```text
records=44
images=270
meaningful_block_alts=270
unique_attachment_ids=258
attachment_meta_alts=258
pair_styles=81
wide_images=30
```

## Frontend QA

- All 44 canonical URLs returned HTTP 200 and an H1.
- Five representative materials covering country, project, recent blog, long photo report and vertical-photo cases contained zero empty content-image alts.
- Every inspected desktop pair had a `0px` height delta and computed `object-fit: cover`.
- At `390px`, inspected pairs stacked to full-width images, the document had `0px` horizontal overflow, and content-image alts remained non-empty.
- Maps and charts retained natural content without crop through `EHPMI wide`.

## Environment boundary

Final read-only checks returned:

```text
dev:  home=https://dev.ehpmi.org database=nykvymmy_ehpmidev marker=2026-09-01-v2
prod: home=https://ehpmi.org     database=nykvymmy_ehpmi    marker=null
```

The production control record contained no new photo-pair style. Production was not written.

## Cleanup and rollback

All temporary audit, migration, verification and review archives created under dev `wp-content` were removed after QA. The canonical scripts remain in this release directory.

Rollback is database-only because the release changed no theme, plugin or upload files:

1. Confirm the target is `/home2/nykvymmy/dev.ehpmi.org` and DB is `nykvymmy_ehpmidev`.
2. Import `/home2/nykvymmy/backups/2026-09-01_120025Z_ehpmi-dev-before-sitewide-media-refactor.sql.gz` into the dev database.
3. Run the release verifier from the preceding accepted release.
4. Repeat the 44-URL smoke test and representative desktop/mobile checks.
