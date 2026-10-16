# Testimonials activation checklist

Activation is explicitly outside Entity Stage 5. Do not enable `ehpmi_testimonials_active` as part of this release.

Before a separate activation release:

1. Confirm written approval for the exact Testimonials to publish and the intended homepage placement.
2. Replace placeholder internal labels such as `Photo 7` with useful Admin labels without treating them as public attribution.
3. Complete and editorially approve Author name, Author role and Author organization for every candidate.
4. Review each quotation in the editor for accuracy, consent, formatting and permitted links; do not store the quotation in an author field.
5. Set an explicit Order value and verify the resulting `menu_order ASC, ID ASC` sequence.
6. Review every portrait attachment. Set meaningful attachment alt metadata when the image conveys identity; use empty alt only after an explicit decorative-image decision.
7. Confirm each candidate remains Draft until the final activation release and preview the exact selected set in a non-public environment.
8. Re-run privacy checks for direct singles, archive, REST, search, sitemap and query variables; the post type must remain non-public unless a separately reviewed routing change is approved.
9. Add a project-owned callback that returns `true` for `ehpmi_testimonials_active`; do not use an ad-hoc production-only snippet.
10. Publish only the approved records, then verify the exact public count and IDs. Publishing alone must not bypass the activation filter.
11. Run semantic/accessibility checks for `article`, `blockquote`, `cite`, filtered body content, image alt, keyboard focus, carousel labels and controls.
12. Run desktop and 390 px visual QA for the homepage, including one/two/three-card states and long quote/attribution wrapping.
13. Create a fresh database backup, rollback plan, release evidence and production-isolation snapshot for the activation release.
14. Obtain explicit acceptance before committing, pushing or deploying activation to production.

## Stage 5 invariant

The following must remain true after Stage 5:

- `apply_filters( 'ehpmi_testimonials_active', false )` evaluates false with no project activation callback.
- All seven historical Testimonials remain Draft.
- The homepage contains no Testimonials section or carousel controls.
- No public Testimonial route, REST endpoint, sitemap provider or search result exists.
- Production has no Stage 5 deployment or activation marker.
