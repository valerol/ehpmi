# Stage 7 audit and ownership

Scope is taken from `docs/ENTITY_REFACTOR_PLAN.md`, Stage 7, against accepted Stage 6 baseline `55295474e7cf3f65a5f7919140559dd99f322311`. The target is the existing development installation only. Production remains read-only.

## Inventory and measured changes

There are 34 Published Pages: 28 belong to the Stage 7 audit and six are News/Project regression controls (Projects root plus three status Pages; Blog root plus News). Library Pages are audited even though their catalogue behavior was accepted in Stage 4. `audit-inventory.json` records every ID, URL, block type, Classic content, inline-style count, table, iframe and obsolete anchor count; `ehpmi-stage7-dev-before.json` retains public body values and Country relationships. No private subscriber rows are stored in these artifacts.

Five Pages lacked an H1: 13 (Who we are), 17 (Countries), 27 (Meet our team), 1453 (Partners), and 1647 (Vietnam). The Member directory also jumped from its H1 to organization H3 headings. The fallback template now supplies a screen-reader H1 for empty managed Pages and screen-reader H2 group headings for Staff/organization directories. Existing visible titles, card heading levels and layout remain intact. A measured hidden-heading remedy was selected to preserve the accepted appearance.

Page routing/URL ownership remains in WordPress and `ehpmi-core`. `singular.php` now owns the document loop/navigation only; `inc/page-resolver.php` resolves one Page region owner and invokes the existing Staff, organization, Country, map, Project, News or Library component. There are no Page-template metadata or permalink changes. Country `leader`, `team`, `member` and map `point` values are unchanged, including relationship order.

No Page body contains a legacy named anchor or a data table. The active homepage `contact-us` named anchor was replaced with an ID on its H2; the same fragment URL continues to work. The inactive commented volunteer heading was normalized in the same template without activating that feature.

Page bodies 1480 (Video) and 2389 (Library/Videos) retain their five Custom HTML iframe blocks. Each static iframe now has lazy loading, the accepted referrer policy, and a distinct Page-context title. Embed URLs, dimensions, parameters and allow attributes are preserved. The two YouTube-plugin playlist blocks remain plugin-owned and unchanged. Image focal positions, plugin-generated inline styles, contact HTML and existing content are retained; there is no mass Classic/Custom HTML conversion.

## Widget decisions

| Region | Owner and Stage 7 decision |
|---|---|
| Slider text, block 2 | Existing global Hero wording remains global; raw H1/paragraph is converted to editable Core Heading/Paragraph blocks. No per-slide fields or CTA are introduced. |
| Map text, block 3 | Existing wording becomes Core Heading/Paragraph blocks; the PHP map output point remains. |
| Contact 1/2, blocks 4/5 | Retain CF7's native selector blocks and all CF7 form/mail configuration. The volunteer region remains inactive. |
| Newsletter, newsletterwidget-2 | Retain native Newsletter widget, title/text and subscription action. Repair containment and labels in a dedicated theme stylesheet. No subscriber, plugin-option or transport change. |
| Footer contacts, block 6 | Make Contact an editable Core H2 with the existing visual size; retain address/icon/email-obfuscation HTML in one explicit Custom HTML block. Normalize the existing invalid closing BR. |
| Footer search/social | Retain existing Core Search/Social blocks. |
| Footer menu 1 | Retain the existing empty Code block as historical editor content; it is neither silently deleted nor activated. |
| Footer menu 2 | Empty and retained. |
| Footer copyright | Retain its inline anchor because the PHP output point already owns the surrounding paragraph. |

All three changed widget contents pass WordPress parse/serialize round trips. Authenticated editor save/reload remains a separate manual gate.

Corrected ownership after the widget incident: each of those widgets now has a single Core Group root with its two children. Multiple top-level blocks in a single widget are forbidden by the measured Widgets editor contract, because it edits/saves only the first root. The Group is layout-neutral through `display:contents`. The earlier generic roundtrip was insufficient; actual capability-checked REST save/reload now proves complete tail preservation. See `widget-content-incident/incident.md`. The old manual heading-save test is withdrawn; the next check is read-only visual confirmation.

## EH-D019 diagnosis and repair

Before Stage 7 the native Newsletter widget produced a standalone title, a left-floated text node and a right-floated form. Its container did not contain those floats, so the form extended below the gray section. The theme also applied `display:none` to the Email label while the email placeholder was empty. The before screenshots show the escaped form; source and DOM measurements identify the containment and label rules. This establishes the current failure mechanism, not when or by which earlier change it originated.

`css/structural-regions.css` contains the widget in a responsive grid and makes its actual Email label visible. Desktop retains two columns, the tablet layout becomes one column, and at 390 px the native email field and Subscribe control stack. The accepted colors, wording and native form action remain. Browser checks prove the form bounds lie entirely inside the Newsletter region and no horizontal overflow occurs at 1440/390 px. The stylesheet is enqueued with the existing cache-safe asset version helper; the live bundle picked it up without a cache purge. The existing LESS/CSS build debt remains Stage 8 work.

The map retains its image and marker positions, gains a named office-map region and keyboard-scrollable group, and provides each native summary with its Country Page title. All nine names are present; an actual Chrome Enter keypress opens the focused marker and exposes the existing country link.

`EH-D019` remains open until operator acceptance and the explicitly required Stage 9 integrated desktop/390 px regression. Stage 7 does not imply Testimonial/Hero causality or change subscription/mail behavior.
