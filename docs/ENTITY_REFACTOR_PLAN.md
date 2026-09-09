# EHPMI entity refactoring plan

Status: `PLANNED`  
Target: `dev.ehpmi.org` only  
Starting code baseline: commit `96bdc1b` on `refactor/dev-2026-08-25`  
Production: outside scope until a separately approved release

This document is the ordered backlog for entity types other than the completed News and Project content-structure work. A stage is not complete merely because code was written: it must satisfy its acceptance checks and produce release evidence.

## Rules for every implementation stage

1. Reconfirm `/home2/nykvymmy/dev.ehpmi.org` and DB `nykvymmy_ehpmidev` before any write.
2. Capture the affected record IDs, field values, relationships, statuses and public URLs.
3. Create and verify a compressed dev DB backup before a database or Admin-content change; upload the canonical archive to the project Drive folder.
4. Record the current Git commit and remote checksums for affected project-owned files.
5. Prepare a deterministic dry run and expected counters before migration.
6. Apply the smallest coherent stage; do not mix plugin updates or production deployment into an entity refactor.
7. Run PHP/static checks, deterministic data verification and HTTP checks.
8. Perform authenticated Admin edit/save/reload for every field changed by the stage.
9. Perform desktop and 390 px browser QA while preserving the accepted appearance.
10. Verify production read-only: separate DB, no migration marker and unchanged control files/records.
11. Save `backup.yml`, `manifest.yml`, verifier output and `qa.md` under `ops/releases/<stage>/`.
12. Commit and push the accepted stage; update the domain protocol and debt register when the stage changes the accepted contract.
13. Keep a tested rollback command and the exact preceding commit/backup. Do not proceed to the next stage after a failed acceptance check.

## Stage 0 — Current entity inventory and visual baseline

Purpose: eliminate assumptions before changing data models.

1. Inventory published, draft and private records for `member`, `partner`, `staff_member`, `material`, `testimonial`, `hero_slide`, `project` and structural `page` records.
2. Export all ACF field keys, stored companion keys, attachment IDs, relationships and ordering values.
3. Identify missing required data, duplicated fields, legacy keys and records with unexpected public URLs.
4. Capture representative desktop/mobile layouts for every entity listing and single view that exists.
5. Freeze expected counts and record IDs in a read-only audit artifact.

Acceptance: complete inventory with no write, reproducible counts, representative URLs and explicit unknowns.

## Stage 1 — Member and Partner model consistency

Purpose: make `member` and `partner` separate but equally manageable organization types.

1. Extend the shared Site URL and Additional image ACF fields to both post types.
2. Introduce the correctly spelled key `additional_image`.
3. Dry-run and migrate values from legacy `additonal_image`, retaining a temporary compatibility read during the release.
4. Verify that no Member or Partner loses a URL, logo, secondary image or body text.
5. Replace misleading shared `partner` HTML/CSS names with neutral card/container names plus explicit `member` or `partner` modifiers.
6. Give linked logos an accessible name and ensure image alt comes from verified attachment metadata.
7. Preserve both types as Admin-only, without public single pages, archives, REST exposure or sitemap entries.
8. Test create/edit/save/reload for one Member and one Partner.
9. Remove the compatibility read only after the migrated key has passed a second verification.

Acceptance: both types expose the same relevant fields, legacy-key count is zero, public output is unchanged except for semantic/accessibility corrections, and no unwanted routes appear.

## Stage 2 — Staff and country roles

Depends on: Stage 1.

1. Add explicit fields for staff position/title and country role; retain the existing Member relationship.
2. Inventory excerpts and migrate position text from `post_excerpt` into the new field without discarding the old value during verification.
3. Replace the hardcoded `The Country Coordinator` label with the saved role, using that phrase only as a documented fallback if accepted.
4. Add an Admin-controlled display order and sort the staff directory by it instead of publication date.
5. Preserve the explicit order of Country `leader` and `team` relationships.
6. Correct invalid excerpt markup and render body content through WordPress content filters and permitted HTML.
7. Add stable article IDs/classes and verify heading hierarchy, image alt and link names.
8. Test Staff create/edit/save/reload, Staff directory, Staff single page and at least one Country office page.

Acceptance: position/role are structured and editable, staff order is deterministic, no nested paragraph markup remains, and Country pages preserve their current people and appearance.

## Stage 3 — Project archive pagination

Purpose: complete listing UX without changing Project content data.

1. Convert the Project listing to a paged `WP_Query` with 12 cards per page.
2. Apply pagination to the root and Current/Past/Potential Page-owned listings while preserving `project_status` filtering.
3. Use the accepted News pagination component and left-align partial final rows.
4. Verify canonical URLs, breadcrumbs, 141 legacy redirects and sitemap behavior remain unchanged.
5. Test first, middle and final pages at desktop and mobile widths.

Acceptance: no page exceeds 12 Project cards; totals across pages match the pre-stage inventory; no duplicate or missing Project IDs.

## Stage 4 — Material catalogue

1. Audit every Material for title, thumbnail, file attachment, `material_type`, MIME type, file size and attachment existence.
2. Classify missing-file records before making the File field conditionally required; do not silently hide or delete them.
3. Render each Material with a real heading and one primary accessible file link.
4. Display derived file metadata such as `PDF · 4.2 MB` without duplicating it in editable fields.
5. Make the thumbnail part of the same link only when that does not create duplicate accessible link names.
6. Add 12-item pagination to Library and type-specific Pages, retaining the private taxonomy model.
7. Define deterministic ordering; use Admin order or publication date only after the inventory shows which matches editorial intent.
8. Verify missing files, HTTP responses, thumbnail geometry, keyboard navigation and mobile layout.
9. Test Material create/edit/save/reload and replacement of a file attachment.

Acceptance: every visible card resolves to an existing file or an explicitly documented unavailable state; counts and classifications match; private Material singles/taxonomies remain inaccessible.

## Stage 5 — Testimonial as a dormant future function

1. Keep all existing draft records unpublished during the refactor.
2. Make `testimonial` non-public and non-queryable until the feature is explicitly activated.
3. Define structured author name, role and organization fields; keep the editor for the quotation body.
4. Replace direct raw output with filtered content and semantic `blockquote`/`cite` markup.
5. Use attachment alt metadata and deterministic display order.
6. Verify that zero published Testimonials produce no empty homepage section or carousel controls.
7. Prepare a separate activation checklist; activation is not part of this stage.

Acceptance: no public Testimonial URL, no raw unfiltered output, drafts preserved, and no visual change while the function remains inactive.

## Stage 6 — Hero slides and homepage-managed content

1. Inventory the three active Hero slides, their order, titles, attachment alts and fallback assets.
2. Stop forcing the post title into the image `alt`; use verified attachment alt, with an intentional empty alt for decorative slides where appropriate.
3. Replace generic fallback alts such as `Hero slide 1` with meaningful accepted descriptions or intentional decorative alts.
4. Decide whether the homepage hero text/CTA is global or belongs to individual slides; document the decision before changing fields.
5. Preserve Admin-controlled ordering and prevent a missing image from producing a broken slide.
6. Test add/edit/reorder/unpublish/save/reload in Admin and verify first-slide eager loading plus lazy loading for subsequent slides.

Acceptance: slide order and fallback are deterministic, alt behavior is intentional, and the accepted hero appearance is unchanged.

## Stage 7 — Structural Pages, map, contact and widget regions

1. Audit all published Pages not covered by the News/Project structure release for heading hierarchy, duplicate H1, empty headings, Custom HTML, inline styles, tables, embeds and obsolete anchors.
2. Do not mass-convert Classic/Custom HTML blocks; migrate only measured patterns with reversible transforms.
3. Split the slug/parent dispatch in `singular.php` into explicit Page templates or small resolver functions while preserving URLs.
4. Replace `<a name="...">` anchors with IDs on the owning heading/section.
5. Give the map a meaningful accessibility contract and every marker summary an accessible country name.
6. Review Slider text, Map text, Contact, Newsletter and Footer widget regions; retain PHP output points while moving content to Core blocks only where Admin editing becomes clearer.
7. Verify Contact Form 7 labels, errors, status messages and keyboard order without sending test mail unless separately authorized.
8. Test every affected Page and homepage section at desktop/mobile widths.

Acceptance: Page templates have stable ownership, measured semantic defects are zero, widget content remains editable, and public URLs/appearance are preserved.

## Stage 8 — Cross-entity media and build dependencies

This stage closes supporting debts after entity markup and fields have stabilized.

1. Export the Real Media Library map of 31 folders and 401 relations.
2. Classify remaining administrative, unused and orphaned attachments; do not move or rename uploads.
3. Deactivate Real Media Library on dev, scan all references, and complete frontend/Admin QA before separately approving deletion of plugin files.
4. Close alt-metadata exceptions for logos, staff images, Hero slides and administrative media.
5. Add a reproducible LESS-to-CSS build command and prove compiled CSS parity.
6. Replace or reproducibly pin external font/Bootstrap/Font Awesome assets in a separate visual-QA release.
7. Resolve or retest the WP-Optimize bulk-update warning before another large content migration.

Acceptance: debts `EH-D009`, `EH-D010`, `EH-D014`, `EH-D015` and `EH-D018` are closed only with their protocol-defined evidence.

## Stage 9 — Integrated Admin and recovery acceptance

1. Repeat authenticated save/reload for Hero, Member, Partner, Staff, Material, Project facts and all retained block/widget regions.
2. Run a full public-route, redirect, breadcrumb, sitemap, desktop and mobile regression suite.
3. Restore an isolated empty environment using only GitHub plus the canonical Drive packages.
4. Run QA-D, record actual RPO/RTO and correct the restoration instructions from observed results.
5. Publish the next accepted protocol/PDF; reserve `v1.0.0` until the recovery rehearsal passes.

Acceptance: `EH-D007` and `EH-D011` are closed, recovery is reproducible, and production remains untouched.

## Recommended execution order

`0 → 1 → 2 → 3 → 4 → 5 → 6 → 7 → 8 → 9`

Stages 1 and 2 form one data-model programme but remain separate releases so Member/Partner rollback is not coupled to Staff migration. Stages 3 and 4 may reuse the News pagination code, but each keeps separate counts and acceptance evidence. No stage authorizes production deployment.
