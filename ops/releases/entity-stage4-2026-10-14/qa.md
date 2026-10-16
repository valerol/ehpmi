# Entity Stage 4 - dev deployment evidence

Status: **ACCEPTED** on 2026-10-16 after explicit operator confirmation of authenticated Admin and desktop/390 px visual QA.

Stage 4 is deployed to dev only. Automated data, runtime, HTTP, DOM, redirect, privacy, sitemap, search, and regression gates pass. Production remained read-only and unchanged. Stage 5 was not started.

## Backup and rollback

Exactly one fresh dev database backup was created before deployment:

- Archive: `/home2/nykvymmy/backups/entity-stage4-2026-10-14/20261014T121904Z_nykvymmy_ehpmidev_pre-stage4.sql.gz`
- Size: `491726` bytes
- SHA-256: `ad369ac2423a1b99ee8ef5912c82be84c178d6c1aac94d6a68ac01493dabb2b5`
- Server and local gzip integrity: PASS
- Google Drive file ID: `14QbLrmkU1ALHcLbr-x7xQaDH1eiefpiW`
- Drive metadata readback: exact name, `application/gzip`, size `491726`, and parent `1d0pVYifHrEsCJbH4Ox6ueqfyvQeGeYFj`

Six protected pre-deployment rollback files are under `/home2/nykvymmy/backups/entity-stage4-2026-10-14/files-20261014T121904Z`. Their exact paths, sizes, and SHA-256 values are recorded in `backup.yml`.

No Material content migration was performed. The only dev database write was the authorized rewrite-rule flush after deployment.

## Implementation and deployment

The six intended files were staged, linted or JSON-validated, rechecked against their Stage 3 baseline hashes, and atomically replaced on dev. Final dev hashes are:

- Core plugin: `6eeea72b2c6a37fb931784af7c20ad8acb062a22efc5a741fa3a411f66a77a35`
- Library template: `d6bff4fd5392ba29bdf93cf5e80f447a52e9fa80b19b02b40b6aaf5b42698740`
- Singular template: `3db612e6bf14873507bf578128761b0108a7cdd2f065a060364b4024ec4037fc`
- ACF JSON: `47fc3b2c37e14d260b6dd0d06cdb6179f9400da6f43fdaae9061ba630d3f2a36`
- LESS: `04c94275b819d592a5419a713422c43bd6e8512c263eac470312d82dfeb9c450`
- CSS: `20f62ccf02d47fcaa619cb3409a799eb6495109d43715a1f18f178ab97701487`

One dev rewrite flush succeeded and both Library pagination rules are present.

## Automated verifier

The deployed verifier returned `result=PASS` with `failures=[]`. The SSH ControlMaster socket was unavailable at final acceptance, so this verifier was not misrepresented as a fresh post-Admin run. The corrected public suite was rerun on 2026-10-16 and returned `PASS` with `failures=[]`.

- Library pages: `[12, 7]`
- Health and pollution pages: `[12, 2]`
- Action plans: `[3]`
- Lead health risk assessment: `[2]`
- Publications and co-authored publications: `[0]`
- Videos: `[0]`
- Exact Materials: 19 unique IDs in deterministic `date DESC, ID DESC` order
- Files and thumbnails: all 19 original relationships and physical files preserved
- Shared thumbnail 1535 and unparented thumbnails 1885/1743: unchanged
- ACF File field: required
- Material post type and `material_type` taxonomy: private

The verifier also renders Material 482 through a temporary read-only metadata filter that simulates a missing file. It confirms the unavailable card class, semantic `h2`, exact text `File temporarily unavailable`, and absence of a primary anchor. It removes the filter immediately and performs no database or attachment mutation.

## Public HTTP and DOM QA

The corrected public suite returned `PASS` with `failures=[]`.

- `/library/`: 12 cards
- `/library/page/2/`: 7 cards
- `/library/health-and-pollution/`: 12 cards
- `/library/health-and-pollution/page/2/`: 2 cards
- `/library/action-plans/`: 3 cards
- `/library/lead-health-risk-assessment/`: 2 cards
- Publications and Videos: zero Material cards with their editorial links/embeds retained

All eight page views returned HTTP 200 with the exact ordered IDs, self-canonical URL, breadcrumb, one H1, expected pagination state, semantic card headings, one primary accessible file link, decorative thumbnail alt, and derived MIME/size label.

Additional checks:

- File URLs: 19/19 HTTP 200
- Thumbnail URLs: 19/19 HTTP 200
- Legacy Material routes: 19/19 exact HTTP 301 Location
- Material archive, taxonomy, REST, post sitemap, and taxonomy sitemap: HTTP 404
- Sitemap index: no Material or material_type provider
- Search: newest Material not exposed
- Homepage Projects: three cards and no pagination
- News: 12 cards plus one on page 2
- Projects: root 12+12+4, Current 6, Past 12+1, Potential 9

The first public-suite run used a non-UTF-8 metadata regex and the Blog overview instead of the child News Page. Those harness-only assertions were corrected; the complete suite then passed. No deployed file was changed in response.

## Production isolation

Final read-only production checks match the pre-deployment baseline:

- Root: `/home2/nykvymmy/public_html`
- Database: `nykvymmy_ehpmi`
- Home/site URL: `https://ehpmi.org`
- Production core file: absent
- Production ACF JSON file: absent
- Library template SHA-256: `f7a7ffaeae81b0b781631072e76cc554270d1652232535251b8fd7c31daa9f8a`
- Singular template SHA-256: `c85d9dfbbb65f5a2ba46b995f08fc5492c2d801d66875af760a97088d311b040`
- LESS SHA-256: `2e1bb1a8fcc111bb9ecc9497a481eae56e14a2dec508e30044cc94991c5d04dd`
- CSS SHA-256: `79b70807af46ba11443fe1d4b255bd780254b934593eebbd3a4c04fa62102983`

No production write was performed.

## Authenticated Admin acceptance

On 2026-10-16, the operator explicitly confirmed:

1. Material 482 saved and reloaded with its File, thumbnail, and `material_type` preserved and no Admin warning.
2. The corrected required-field test passed: Publish without a File was blocked and the record remained a Draft.
3. The same unpublished test Draft persisted File A after reopen, then persisted replacement File B after another reopen, and was never published.
4. All Stage 4 test Drafts were moved to Trash; their Media Library files were not deleted.

No test Draft IDs are recorded because they were not independently available from the read-only release evidence. The fresh public suite still returned the exact original 19 published Material IDs, so no test Draft was publicly listed.

ACF contract: an incomplete Material Draft may be saved. A valid File is required before Publish and when updating an already published Material. The earlier checklist statement that an empty File must block Save Draft was incorrect and is superseded by this acceptance record.

## Responsive visual acceptance

The operator provided the exact confirmation `Stage 4 visual QA passed desktop and 390` for all eight routes:

- `/library/`
- `/library/page/2/`
- `/library/health-and-pollution/`
- `/library/health-and-pollution/page/2/`
- `/library/action-plans/`
- `/library/lead-health-risk-assessment/`
- `/library/publications-and-co-authored-publications/`
- `/library/videos/`

The accepted checklist covered readable cards, thumbnails, title and derived metadata alignment, one keyboard focus stop per available card, visible pagination, left-aligned partial final rows, retained Publications/Videos editorial content, and absence of horizontal overflow, clipping, overlap, or broken layout.

Stage 4 is accepted. Production remained unchanged and Stage 5 was not started.
