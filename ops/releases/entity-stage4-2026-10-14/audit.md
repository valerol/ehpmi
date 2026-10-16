# Entity Stage 4 material catalogue audit

Audit date: 2026-10-14

This is a read-only audit artifact. It records no implementation or acceptance claim. No server file, database, Admin, production, deployment, backup, commit, push, or Stage 5 action was performed.

## Scope and baseline

- Canonical branch: `refactor/dev-2026-08-25`
- Clean accepted Stage 3 baseline: `e3a4aaff55bfc073acd8cfe999e00bf00beaf40d`
- Dev root: `/home2/nykvymmy/dev.ehpmi.org`
- Dev database: `nykvymmy_ehpmidev`
- Production isolation, read-only: DB `nykvymmy_ehpmi`, home `https://ehpmi.org`
- SSH control socket: `/private/tmp/codex-ehpmi-stage3c.sock`

## Local implementation contract

- `material` is an admin-only custom post type: `public=false`, `publicly_queryable=false`, `exclude_from_search=true`, `show_in_rest=false`, no archive, rewrite, or query variable.
- Material supports are title, thumbnail, and custom fields. It does not use the editor.
- `material_type` is a private taxonomy attached to `material`; it is not post meta. It is visible in Admin and as an Admin column, but has no public query, REST, rewrite, or sitemap exposure.
- ACF local JSON defines one optional `file` field, returns an attachment ID, allows the full Media Library, and has no MIME or size restriction.
- The Library root and five child Pages own the public routes. The template queries all published Materials with `posts_per_page=-1`, `orderby=date`, `order=DESC`; child Pages add a `material_type` taxonomy filter.
- There is no pagination. The root currently renders 19 cards and the largest type page renders 14.
- Each populated card is an `article.material` containing an unlinked thumbnail and one title/download link. If the file URL cannot be resolved, the link silently becomes a plain `span.file`.
- Desktop layout uses two CSS columns; it becomes one column at 1024 px, while each card changes to a centered vertical layout below 450 px.

Relevant code and configuration:

- `wp-content/plugins/ehpmi-core/ehpmi-core.php`
- `wp-content/themes/ehpmi/acf-json/group_6371e86e64aa0.json`
- `wp-content/themes/ehpmi/singular.php`
- `wp-content/themes/ehpmi/template-parts/library.php`
- `wp-content/themes/ehpmi/css/style.less`
- `wp-content/themes/ehpmi/css/style.css`

## Dev inventory

There are exactly 19 Material records and all 19 are published. There are no draft, pending, private, future, trash, or auto-draft Material records.

Every Material has exactly these five meta keys, one row each:

- `file`
- `_file`
- `_thumbnail_id`
- `_edit_last`
- `_edit_lock`

All 19 `file` values are single numeric attachment IDs. Every `_file` companion value is `field_6371e86e1de40`. There are no duplicate titles, slugs, or file attachment IDs. All retained legacy Category relationships match the active `material_type` slug; those Category relationships remain rollback data and do not drive the public catalogue.

Material type counts:

| Type | Published Materials |
| --- | ---: |
| Health and pollution | 14 |
| Action plans | 3 |
| Lead health risk assessment | 2 |
| Publications and co-authored publications | 0 |
| Videos | 0 |

The two zero-count types still have meaningful public Page content: Publications contains an editorial publication entry and Videos contains two YouTube embeds. They are therefore not empty public routes even though they contain no Material cards.

## Current ordering

All 19 records have `menu_order=0`. The catalogue therefore has no curated ordering field. Runtime order is post date descending.

Root order:

`[1852, 1739, 532, 529, 526, 523, 520, 516, 513, 510, 507, 504, 501, 498, 494, 491, 488, 485, 482]`

Filtered orders:

- Health and pollution: `[523, 520, 516, 513, 510, 507, 504, 501, 498, 494, 491, 488, 485, 482]`
- Action plans: `[1852, 529, 526]`
- Lead health risk assessment: `[1739, 532]`
- Publications: `[]`
- Videos: `[]`

No current dates tie, so the observed order is stable today. The code has no explicit secondary tie-breaker, so equal future dates would not have a declared deterministic order.

## Attachments and files

- File fields: 19/19 populated and resolved.
- File attachment posts: 19/19 exist, are `attachment` posts with status `inherit`, and point to existing physical files.
- File MIME distribution: 13 PDF and 6 JPEG.
- External file URLs: 0.
- Distinct file attachment IDs: 19/19.
- Total file bytes on disk: 113,378,795.
- Largest file: Material 494 / attachment 495, 51,317,951 bytes.
- Public download responses: 19/19 HTTP 200 using HEAD-only checks.
- Thumbnails: 19/19 assigned, attachment posts exist, and original files exist.
- Rendered thumbnail responses: 19/19 HTTP 200 using HEAD-only checks.

Attachment relationship anomalies do not break the current frontend:

- Materials 498 and 501 share thumbnail attachment 1535; it is parented to Material 501.
- Material 494 uses thumbnail 1885, whose attachment parent is 0.
- Material 1739 uses thumbnail 1743, whose attachment parent is 0.

All attachment GUIDs retain the historical `http://ehpmi.org` host, while WordPress resolves the current public URLs correctly to `https://dev.ehpmi.org`. GUIDs are historical identifiers and should not be bulk-rewritten for this stage.

## Page and public route evidence

Library Pages:

| Page ID | Route | Material cards |
| ---: | --- | ---: |
| 2380 | `/library/` | 19 |
| 2385 | `/library/action-plans/` | 3 |
| 2386 | `/library/health-and-pollution/` | 14 |
| 2387 | `/library/lead-health-risk-assessment/` | 2 |
| 2388 | `/library/publications-and-co-authored-publications/` | 0 |
| 2389 | `/library/videos/` | 0 |

All six routes returned HTTP 200, emitted a self-canonical URL, rendered the expected breadcrumb, and contained exactly one `h1`. Populated routes rendered the exact expected card counts and date-descending titles. Every Material card contained exactly one accessible title/download link; no duplicate accessible link pair was found.

All 19 rendered thumbnails currently emit `alt=""`. Because the image is unlinked and the adjacent title link names the resource, this can be treated as a decorative-image pattern, but the intent should be explicit. Material cards do not contain a heading element, so each `article` landmark is unnamed even though its link text is clear.

Additional route checks:

- All 19 legacy `/material/<slug>/` routes return one exact 301 to the stored file URL.
- `/material/`, a direct `material_type` route, the Material REST endpoint, the Material post sitemap, and the Material Type sitemap all return 404.
- The sitemap index contains Post, Page, Project, Staff Member, and User providers only.
- A site search for the newest Material title did not expose the Material record.
- Publications' local PDF returned HTTP 200.
- Both YouTube embed URLs returned HTTP 200.
- The external ScienceDirect publication link returned HTTP 403 to automated HEAD/fetch requests. This is consistent with access automation being refused and does not prove the human-facing link is broken; it remains the only link that could not be positively verified by this audit.

## Findings and implementation recommendations

1. **Ordering policy is implicit.** Preserve the current visible newest-first behavior unless editorial curation is explicitly wanted. For deterministic newest-first behavior, use an explicit compound order of post date descending and ID descending. Do not repurpose `menu_order` unless an Admin control for it is also introduced.
2. **Missing-file behavior is silent.** A published Material without a resolvable file would remain visible but lose its link with no explanation. Add a clear frontend unavailable state and an Admin validation/warning. Do not hide published records silently.
3. **Attachment parent metadata is inconsistent but non-breaking.** Do not rewrite parents automatically. Confirm whether the shared cover for Materials 498 and 501 is intentional; the two unparented thumbnails do not require repair for rendering.
4. **Card semantics can be stronger.** Wrap the existing Material title link in an `h2` or add an equivalent `aria-labelledby` relationship so each article has a name. Keep thumbnail alt empty only if covers are intentionally decorative beside the visible title.
5. **The content model is split for two routes.** Publications and Videos are Page-authored content, while the other three child routes use Material records. Preserve this intentional split unless the operator wants those entries migrated into the catalogue.
6. **Pagination is not currently required for correctness.** The collection is 19 records, the largest type is 14, and every route renders successfully. Adding pagination would expand routing, canonical, and QA scope; keep the current unpaginated behavior unless the operator specifically wants a page-size limit.

## Decisions required before implementation

1. **Catalogue order:** choose either:
   - Recommended: preserve newest-first and make it deterministic with `date DESC, ID DESC`; or
   - introduce an editorially curated order, which requires a dedicated order field or visible Admin ordering control and a one-time order assignment.
2. **Missing-file presentation:** choose the exact user-facing policy and wording. Recommended: keep the published card visible, show `File temporarily unavailable` instead of a link, and flag the record in Admin; alternatively, require a valid file before publication.
3. **Shared thumbnail 1535:** confirm whether Materials 498 and 501 intentionally use the same cover. This is an editorial asset decision, not a runtime blocker.

No other editorial decision is required for a bounded implementation that preserves the current content model and public routes.

## Backup gate for any later writes

A fresh dev database backup is required immediately before any Stage 4 implementation or content write, even if the final patch is mostly code, because the catalogue state and attachment relationships are part of the acceptance baseline. Do not reuse the Stage 3 backup as the write gate.

Recent compressed dev database backups were 498,889 bytes, 659,036 bytes, and 491,605 bytes. A new backup is therefore expected to be approximately 0.5-0.7 MB compressed, subject to current database drift. No backup was created during this audit.
