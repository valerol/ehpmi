# Stage 7 widget text-loss incident

Status: COMPENSATED AND ACCEPTED. On 2026-10-18 (Asia/Makassar), the operator confirmed: “Hero, Map и Footer восстановлены на desktop и 390. Newsletter отображается корректно.” The incident and its limitations remain evidence; acceptance does not erase the failed original test or imply another browser Admin save after repair.

## Cause and attribution

Stage 7 stored multiple top-level Core blocks in each of `widget_block` instances 2, 3 and 6. The installed WordPress 7.1 `wp-includes/js/dist/edit-widgets.js`, lines 1979–1990, implements `transformWidgetToBlock()` by parsing `widget.instance.raw.content` and returning `parsedBlocks[0]`. Its `transformBlockToWidget()` at lines 2009–2025 serializes that one block. Therefore the Widgets editor displayed the first Heading and could overwrite the option with that Heading only on save.

Our previous QA checked generic PHP parse/serialize and frontend output, which both support multiple roots, but did not test the actual Widgets editor first-root contract. Our manual instruction then asked the operator to edit/save a heading. The parent chat confirms that `stage7-round-trip-test` was added by the operator following that instruction. The storage defect and unsafe QA instruction are our responsibility.

The exact pre-stage snapshot and initial post-deploy snapshot both contain complete text. The incident snapshot instead contains Heading-only widgets with the test class. This disproves the suggestion that the texts were already absent before testing. Widgets 2/3/6 were correctly identified and mapped; this was not another option, reusable-block location, malformed serialization or Newsletter failure.

## Evidence and correction

`ehpmi-stage7-widgets-incident-current.json` and `before-repair-widgets.json` preserve the damaged values and full sidebar mapping. The serialized DB `widget_block` option was valid; its incident SHA-256 was `174565e04658764f8c148ba4367f3292cb1a2fc2e7080bde71b0d72754bdfe05`.

A fresh pre-repair dev database archive passed remote/local gzip and SHA-256, was uploaded to the canonical dev Drive folder, and passed metadata readback. Details are in the parent `backup.yml`. Production remained read-only.

Under exact current-value guards, the repair first restored the three exact pre-stage content values from the verified `content-before-v2.json` delta (SHA-256 `323a0e489199096feb039b9f154e7f9b15fff79c2575ccd32719d83a4ab009b0`). `baseline-restored-receipt.json` records that successful restoration. It then wrapped each intended Core migration in ONE Core Group, retaining Heading + Paragraph for Slider/Map and Heading + Custom HTML for Footer contacts. The test class is removed. A `display:contents` rule makes this editor ownership wrapper layout-neutral.

The complete corrected delta is `content-before-v3.json`, SHA-256 `294821c90ad898a0bcad4f20d56899832e266b693856c118d4b3e512caa734f9`. Exact per-widget before/pre-stage/after hashes are in `repair-output.json`; repaired serialized option SHA-256 is `3ef709e03d504c73cf7109cc4fc7b2f88f504eddad2b772614b96763bc8d0e11`.

`editor-rest-roundtrip.json` records an actual capability-checked WordPress widget REST POST(save) and GET(reload) for all three instances, using an existing administrator only as the current in-process WP-CLI context. No account, login session or credential was created. Each response is 200, each complete content hash survives unchanged, each Admin form textarea retains the tail, each first-root projection includes both children, and the complete widget option/sidebar mapping stays unchanged across saves. This is stronger save-path evidence than generic serialization; it does not invent a new authenticated browser UI round trip.

`widgets-repaired-browser.json` confirms exact baseline Hero/Map paragraphs and Footer address at 1440/390 px: six visible views, `display:contents`, no test marker, zero overflow and zero browser POST. Footer email is visibly present. Six screenshots accompany it.

The complete final `verifiers-final.txt` suite passes Stage 7 (all 34 Page/body/relationship/owner and safe-root checks), retained Stage 3/4, the current Stage 6 contract and validate-only full Stage 7 rollback. `semantic-semantic-repaired.json` independently repeats HTTP/main/H1/heading/anchor checks for all 34 Pages with zero defects. A first recheck found the expected Admin boolean normalization; a later harness attempt found its old hosting `/tmp` baseline had expired. The source baseline and retained scripts were recopied into the persistent stage backup evidence directory, and the complete final suite passed there. Those were verification preconditions, not another storefront regression.

During the prior manual phase Page 1480 also acquired only equivalent `allowfullscreen` → `allowfullscreen=""` serialization and revisions 2461–2465 appeared. Those states are already present in the incident-before snapshot; this repair never changes Page/post/revision records or removes revisions. The verifier and rollback now admit only that exact boolean-attribute equivalence and keep every other body byte guarded.

## Boundaries and next step

Newsletter subscriber-table hashes, fixture 2458 (Trash/Order15), forms and production control hashes remain unchanged. No form, subscription or email was sent. Source repair, updated Group-aware verifier/migration/rollback and the corrected manual checklist are retained in this release. The old unsafe checklist is preserved only as historical evidence and is withdrawn.

The requested read-only visual confirmation has been received as quoted above. No further save/class test is requested. Stage 7 is accepted; the integrated Stage 9 Admin/recovery checks and Newsletter regression remain separate work.
