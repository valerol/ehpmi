# Semantic HTML and Project facts refactor — dev — 2026-09-05

## Outcome

The dev-only theme and content migration passed its deterministic database, PHP, HTTP, DOM, desktop-rendering and mobile-layout checks. Production remained read only and unchanged. Two acceptance exceptions remain explicit: the available browser was not authenticated for an Admin save/reload, and its screenshot API did not return captures.

## Scope and result

- Added dedicated `single.php` and `single-project.php` templates.
- Added one `main#main-content` landmark and semantic `article` identity/classes to public templates.
- Added named primary/mobile/breadcrumb navigation and a skip link.
- Changed News dates to `time[datetime]`; inner-list headings are H2 and homepage-card headings remain H3 under their H2 section.
- Added Local JSON group `Project facts` with seven editable ACF fields.
- Migrated all 28 Project summaries from display-only excerpt markup into structured ACF values while retaining `post_excerpt` for archive cards.
- Removed 39 redundant `core/post-excerpt` blocks from stored content.
- Added accessible titles, lazy loading and referrer policy to 17 Google Maps iframes.
- Removed two empty H2 blocks and changed two H4 training subsections to H3.
- Added captions/header structure to two data tables and moved their presentation into theme CSS.
- Added contextual featured-image alt metadata for attachments `779`, `462`, `449` and `619`.
- No file in `wp-content/uploads` was changed.

## Backup

The database archive was created before the first database write and verified on server and locally:

- file: `ehpmi-dev-html-structure-pre-20260904T224219Z.sql.gz`;
- bytes: `465508`;
- SHA-256: `5f7297617590644b875aad790bd22fe867e8960a0e1c7a93d544de5c20686d95`;
- `gzip -t`: PASS;
- `CREATE TABLE` statements: `53`;
- Drive metadata readback: filename, parent, size and MIME type matched;
- canonical Drive copy: <https://drive.google.com/file/d/1MNtmAPIsnt7FqZ8xtq7Vgo6HNlK_YHZM/view?usp=drivesdk>.

## Automated verification

The migration committed only after its expected counters matched. The post-migration verifier returned:

```text
records=41
projects=28
excerpt_blocks=0
untitled_maps=0
nonlazy_maps=0
empty_headings=0
project_fieldsets=28
featured_alts=4
```

All 41 canonical News/Project URLs returned HTTP 200. Every page had exactly one `main#main-content`, one H1 inside main, and one `article#post-ID`.

## Rendered browser QA

- News archive: 13 article cards, H1 → H2 hierarchy and 13 machine-readable dates.
- Projects archive: 28 article cards and H1 → H2 hierarchy.
- Homepage: H1, H2 section title and H3 News cards.
- Project `761`: five rendered Project facts, named map iframe, single main/article/H1 and no horizontal overflow.
- Project `1953`: H1 → H2 → H3 hierarchy; no H4 remains.
- Projects `1988` and News `2355`: visible semantic table caption and `thead`; no page-level horizontal overflow.
- At `390px`, three representative pages had document width `390px`, no offscreen content images and no horizontal overflow. The long table retained an internal horizontal scroll container.
- Browser console warnings/errors in the inspected pages: zero.

The in-app browser screenshot call was unavailable despite successful rendered DOM/computed-layout inspection. This run therefore does not claim a new pixel-comparison baseline. The existing accepted design was preserved through unchanged markup classes and targeted CSS compatibility rules.

## Admin editability

ACF loaded `group_ehpmi_project_facts` from theme Local JSON with all seven fields. The public ACF API resolved the field keys and stored values for Project `761`, and all 28 projects passed companion-key/value verification. The available browser redirected to WordPress login, so an authenticated visual edit/save/reload was not performed; project-wide debt `EH-D011` remains open.

## Warning and debt

During `wp_update_post()`, WP-Optimize 4.6.1 emitted `Cannot use string as array` from `class-wpo-cache-rules.php:242`. Its code assigns a `Y-m-j` string directly to `list()` instead of splitting it. The transaction committed, the cache was flushed through the plugin's public function, and all database/frontend checks passed. This vendor defect is tracked as `EH-D018`; plugin code was not patched in this release.

## Environment boundary and rollback

Final read-only production checks returned `home=https://ehpmi.org`, database `nykvymmy_ehpmi`, no migration marker, and no new `single-project.php`. Production was not written.

The final 42-file theme manifest matched dev exactly. Six untracked macOS AppleDouble `._*` metadata files were removed from the dev theme; they contained no WordPress code or site content and are now excluded by `.gitignore`.

Rollback is prepared but was not executed:

1. Confirm target document root `/home2/nykvymmy/dev.ehpmi.org` and DB `nykvymmy_ehpmidev`.
2. Restore theme files from Git commit `1bc9d36f67fa967ef4713921b00207e50fe26031`.
3. Import `/home2/nykvymmy/backups/ehpmi-dev-html-structure-pre-20260904T224219Z.sql.gz` into the dev DB.
4. Flush WP-Optimize/object cache and repeat `verify.php`, `frontend-verify.php` and representative browser checks.

The project-wide isolated recovery rehearsal remains open as `EH-D007`.

## Protocol artifact

Domain protocol `v0.6.3` was generated as a 35-page A4 PDF. Cover, table of contents, representative prose/table pages, debt register and final page were visually inspected; extraction found no blank pages or unresolved placeholders. SHA-256 is `348c92324cfb7e3c3de1890e1136d508a260ae9dec14a60ac8917490f7b8ea1c`. Drive metadata readback confirmed the 230296-byte PDF in the EHPMI root folder: <https://drive.google.com/file/d/1Ye0_tuHlMZclLOyMyxa_3NOBl-vQ0dy9/view?usp=drivesdk>.
