# Entity Stage 5 QA

## Automated acceptance

- Local PHP lint: three of three Stage 5 PHP files passed.
- ACF Local JSON and inventory JSON parsed successfully.
- `git diff --check` passed.
- The remote verifier passed with no failures: the seven expected records remain Draft, no content migration occurred, and zero published records produce zero markup even when the display filter is forced on.
- A semantic probe confirmed filtered editor content, `blockquote`/`cite`, structured attribution, attachment alt metadata and deterministic `menu_order ASC, ID ASC` behavior.
- Public HTTP checks returned 404 for query-variable, single, archive and REST access. Sitemap and search exposed no Testimonial entry.
- The inactive homepage has no Testimonial section, carousel or controls. Existing homepage counts remained 13 news cards, 3 project cards and 1 members/organizations section.

## Authenticated Admin and visual acceptance

- The operator edited existing Testimonial `127`, saved it as Draft, reloaded it and confirmed the quotation editor plus Author name, Author role and Author organization persisted.
- The operator then restored all three structured fields to empty and Order to `0`, saved/reloaded again and confirmed cleanup. The post was never published.
- The operator completed desktop and 390 px homepage review and explicitly accepted Stage 5 with one visual exception: the Newsletter form is visually broken.
- Scope verification shows that Stage 5 runtime changes are limited to the Testimonial post-type registration, its dormant homepage template and Testimonial ACF Local JSON. No Newsletter, form, widget, CSS or JavaScript file was changed. The visual observation therefore does not prove Stage 5 causality or establish when the defect began; it is recorded as cross-stage debt `EH-D019`, assigned to Stage 7 remediation and Stage 9 desktop/390 px regression acceptance.
- Per operator direction, Stage 5 did not repair or submit the Newsletter form and did not alter subscriber data.

## Production isolation

Production was inspected read-only after the dev deployment. It still uses `nykvymmy_ehpmi` and `https://ehpmi.org`, has seven draft Testimonials and zero Stage 5 structured-meta values. The Stage 5 plugin file, ACF JSON and template hash were not deployed there. Production writes: zero.

## Rollback

- Database: restore the verified pre-stage archive recorded in `backup.yml` to the dev database only.
- Files: restore `ehpmi-core.php.before-stage5` and `testimonials.php.before-stage5` from `/home2/nykvymmy/backups/entity-stage5-2026-10-16/files-20261016T112653Z`, remove the newly added Stage 5 ACF JSON, then flush dev rewrite rules.
- The two rollback copies have SHA-256 values `6eeea72b2c6a37fb931784af7c20ad8acb062a22efc5a741fa3a411f66a77a35` and `5e9d5fc8554b39c7e099936243037cbaa107f0e92cd8c757c82ea6fca929c476` respectively.

## Acceptance

Stage 5 is accepted with the explicit `EH-D019` visual exception above. Testimonials remain inactive and unpublished; production remains outside deployment scope.
