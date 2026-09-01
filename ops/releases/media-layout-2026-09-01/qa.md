# Editorial media-layout refactor QA — dev — 2026-09-01

## Outcome

The dev-only media refactor passed its content-structure, code, desktop and mobile browser checks. Gallery is now reserved for three or more related images. Single images use Core Image; pairs use Core Columns with the project-owned `EHPMI image pair` style. Production was read only and unchanged.

## Boundary and recovery point

- Dev URL: `https://dev.ehpmi.org`
- Dev database: `nykvymmy_ehpmidev`
- Production database verified separately: `nykvymmy_ehpmi`
- Pre-migration database backup: `2026-09-01_042741Z_ehpmi-dev-before-media-layout.sql.gz`
- Backup SHA-256: `750fbb0b3081677ca7d7eb8a765388f900d7a260f0f8ba6545d700dc3546f582`
- Drive copy: <https://drive.google.com/file/d/1yePxYN2gZ4e3vn3UXxCtd-s6krsHwZGi/view?usp=drivesdk>
- Backup gzip integrity, 53-table database check and Drive metadata readback: PASS
- Pre-stage code archive: `/home2/nykvymmy/backups/2026-09-01_042741Z_ehpmi-dev-before-media-layout-code.tar.gz`

## Content migration

The dry run matched the audited preconditions before any content write: 145 Gallery blocks consisting of 56 single-image, 87 two-image and two three-image galleries. The transactional dev migration changed 41 of 116 scanned records:

| Structure | Before | After | Result |
|---|---:|---:|---|
| Core Image blocks | 260 nested in or outside Gallery | 260 | PASS; attachment IDs and order preserved |
| One-image Gallery | 56 | 0 | PASS; converted to Core Image |
| Two-image Gallery | 87 | 0 | PASS; converted to 87 `EHPMI image pair` Columns blocks |
| Gallery with at least three images | 2 | 2 | PASS; both contain exactly three images |
| Unique referenced attachment IDs | 248 | 248 | PASS |
| All attachments | 467 | 467 | PASS |

No unsupported gallery structures were found. Newsletter remained at 172 subscribers and zero sends.

## Layout and responsive QA

Browser checks used the live dev frontend after purging the stale WP-Optimize minification cache.

- desktop viewport `1440px`: single Image figures are centered at `880px`; images remain `width:auto; max-width:100%` and are not enlarged beyond the selected file;
- desktop viewport `1440px`: image pairs render as two `652px` grid columns inside the `1320px` article container;
- mobile viewport `390x844`: each pair column is `358px` and stacks vertically;
- real Gallery blocks render as three columns on desktop and one `358px` column on the tested mobile viewport;
- single images render at `358px` on mobile;
- no media block or page element overflows the final `390px` viewport.

The mobile check initially exposed a pre-existing `#breadcrumbs.container` overflow of 20px. Theme 1.5 now keeps its legacy margin inside the viewport; the repeated browser check returned `scrollWidth=390` and no overflowing element.

Representative pages:

- single images: <https://dev.ehpmi.org/blog/ehpmi-annual-workshop-2026-in-kyrgyzstan/>
- pairs and real galleries: <https://dev.ehpmi.org/projects/cleanup-of-toxic-persistent-organic-pollutants-in-sumgait-azerbaijan/>

## Sizes, format and compression decision

The runtime generation contract keeps original, `thumbnail` (`530x530` crop), `medium` (`650px`), `medium_large` (`768px`) and `large` (`1320px`). Redundant `1536` and `2048` derivatives are disabled. Projects and Blog templates now request the named `thumbnail` size. Library intentionally retains its `200x150` display box while reusing the nearest existing derivative; this avoids adding another generated size and preserves the production card geometry. An experimental 200x150 derivative was tested on 18 material thumbnails, rejected after responsive/HiDPI QA, and fully removed from files and attachment metadata. JPEG and WebP editor quality is fixed at 82.

An isolated regeneration test on attachment `1548` proved that using WordPress' runtime `image_editor_output_format` conversion for legacy JPEGs is unsafe here: metadata moved to a WebP path while `post_mime_type` and saved block URLs remained JPEG. The test attachment was restored to its original JPEG metadata and regenerated sizes, and the unreferenced test WebP was removed. Therefore:

- existing attachments are not bulk-converted in this release;
- new photographs are converted and visually checked by Codex before WordPress import;
- WebP is used when it is smaller without visible degradation; JPEG remains acceptable otherwise;
- PNG is retained for transparency and lossless diagrams;
- runtime format conversion is explicitly disabled.

This preserves current URLs and recovery sources while making future media deterministic.

## Code and technical QA

- PHP syntax: PASS for changed PHP files;
- CSS braces and `git diff --check`: PASS;
- release verifier: PASS with 260 Image blocks, two Gallery blocks, zero small galleries, 87 image pairs and 248 referenced IDs;
- local/deployed SHA-256: PASS for all seven changed runtime files;
- EHPMI theme: 1.5;
- EHPMI Core: 1.1.0;
- maintenance mode after release: inactive;
- WP-Optimize CSS cache was purged after deployment.

WP-CLI emits deprecation messages from its bundled Mustache and color libraries under the hosting PHP runtime. No application fatal/error was observed.

Authenticated block-editor edit/save remains covered by the project-wide open debt `EH-D011`; frontend rendering, block parsing and serialization passed in this release. Alt remediation remains separate debt `EH-D015`: the audit found 148 of 248 referenced attachments without alt and the wider media-library baseline remains 309 of 450 images.

## Production non-change evidence

Read-only production verification after the dev migration returned:

- URL: `https://ehpmi.org`;
- database: `nykvymmy_ehpmi`;
- WordPress: 7.0.4;
- theme version: 1.2;
- no media-layout migration marker;
- `ehpmi-core` not active.

## Stage rollback procedure

1. Enable maintenance mode on `dev.ehpmi.org` and confirm `DB_NAME=nykvymmy_ehpmidev`.
2. Restore the theme and `ehpmi-core` files from `/home2/nykvymmy/backups/2026-09-01_042741Z_ehpmi-dev-before-media-layout-code.tar.gz` or the preceding Git commit.
3. Download the named Drive database backup and verify SHA-256 `750fbb0b3081677ca7d7eb8a765388f900d7a260f0f8ba6545d700dc3546f582`.
4. Import the uncompressed SQL into `nykvymmy_ehpmidev` only.
5. Purge WordPress and WP-Optimize caches, then disable maintenance mode.
6. Repeat database, content-count, Newsletter, desktop and mobile checks.

Rollback was prepared but not executed. The project-wide isolated recovery rehearsal remains open as `EH-D007`.
