# Stage 7 operator acceptance

Status: ACCEPTED.

On 2026-10-18 (Asia/Makassar), the operator directly confirmed: “Hero, Map и Footer восстановлены на desktop и 390. Newsletter отображается корректно.” This confirmation accepts the restored Hero/Map/Footer at desktop/390 and correct Newsletter appearance. It supplies no additional browser, timing or post-repair authenticated Admin edit/save detail; none is inferred.

The previous instruction to change/save individual widget headings is withdrawn. It exposed a Stage 7 defect: several top-level blocks were stored in one `widget_block` instance, while the installed Widgets editor edits/saves only its first root. The operator followed the supplied test instruction; the loss was caused by our unsafe storage shape and insufficient QA, not by missing original text. The old checklist is retained in `widget-content-incident/superseded-manual-acceptance.md` as incident evidence, not as an active procedure.

The three widgets now each contain ONE Core Group with both children: Heading + Paragraph in Slider/Map, Heading + Custom HTML in Footer contacts. Exact pre-stage values were restored from verified evidence before this safe conversion. A capability-checked WordPress REST POST-save/GET-reload simulation proves that complete content, Admin form text and sidebar mappings survive; the first-root editor projection now includes the entire Group. This is server-side save-path evidence, not a claim of a new authenticated browser edit after repair.

The read-only confirmation requested after compensation was:

1. Refresh dev homepage and confirm the full Hero and Map descriptions and Footer address/email are visible at desktop and 390 px. The repaired screenshots and exact text-match browser report are in `widget-content-incident/`.
2. If inspecting dev Widgets Admin, expand the Group for Slider text, Map text and Footer contacts and confirm both children/full text are present. Do not repeat the old CSS-class test, isolate/replace the heading, or save anything for this incident check.
3. Confirm the Newsletter repair and retained map/contact/footer appearance. Do not submit CF7 or Newsletter, create a subscriber, send mail, or modify fixture 2458.

Implementation-stage rules 8/9 in `docs/ENTITY_REFACTOR_PLAN.md` remain the acceptance source. The original authenticated operator save test failed and has been compensated; safe internal REST save/reload passes. The operator's direct acceptance is recorded above. No post-repair authenticated browser save is claimed or requested here; the broader integrated Admin/recovery debt `EH-D011` remains Stage 9 work. Stage 7 is accepted for commit/push, Stage 8 is not started, and `EH-D019` remains open only for Stage 9 integrated desktop/390 regression.
