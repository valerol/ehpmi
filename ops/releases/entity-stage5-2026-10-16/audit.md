# Entity Stage 5 testimonial audit

This inventory was captured from `dev.ehpmi.org` before Stage 5 runtime deployment. It is evidence, not an activation decision.

## Environment and records

- Document root: `/home2/nykvymmy/dev.ehpmi.org`
- Database: `nykvymmy_ehpmidev`
- Home/site URL: `https://dev.ehpmi.org`
- Git baseline: `b0c3162`
- Testimonials: exactly seven, IDs `127, 133, 134, 135, 136, 160, 161`
- Statuses: seven Draft; zero Published, Private or Trash
- All `menu_order` values: `0`
- Thumbnail IDs in record order: `155, 162, 154, 156, 158, 157, 159`
- All seven thumbnails: `image/png`; all seven `_wp_attachment_image_alt` values empty
- New structured author metadata before Stage 5: zero non-empty values

The full title, slug, content SHA-256, content byte count, thumbnail relationship, MIME and alt inventory is frozen in `inventory.json`. Quotation bodies are represented by hashes rather than copied into release documentation.

## Pre-stage implementation findings

- The `testimonial` post type was public, publicly queryable, REST-enabled and search-visible by registration.
- The homepage template queried Testimonials without an explicit status or deterministic secondary order.
- The template emitted `post_content` directly and used non-semantic quote/name containers.
- The homepage already rendered no Testimonials section because there were zero published records.

## Stage 5 decisions

- Preserve all seven records and every title, slug, body, status, thumbnail relationship and `menu_order`; expected content-migration writes are exactly zero.
- Keep the title as an internal Admin label. Public attribution, if later activated, must use the new structured author fields rather than infer identity from legacy titles such as `Photo 7`.
- Add `page-attributes` support for an explicit Order control. Until editors set distinct values, deterministic display order is `menu_order ASC, ID ASC`.
- Use attachment `_wp_attachment_image_alt` metadata verbatim. Empty alt remains empty while dormant; the activation checklist requires an editorial alt decision before publication.
- Keep the editor as the quotation body and render it only through `the_content` followed by `wp_kses_post`.
- Gate all homepage output behind the default-false `ehpmi_testimonials_active` filter. Merely publishing a record cannot activate the feature.

## Dry-run contract

- Expected record inserts/updates/deletes/status changes: `0/0/0/0`
- Expected attachment or alt writes: `0`
- Expected code/config files deployed: `3`
- Expected database write after deploy: one dev-only rewrite-rule flush
- Expected public output while inactive: no Testimonials section, carousel viewport, controls or status node

Production is outside deployment scope and is checked read-only against its separate database and control-file hashes.
