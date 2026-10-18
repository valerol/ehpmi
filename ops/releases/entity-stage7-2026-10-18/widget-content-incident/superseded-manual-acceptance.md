# Stage 7 operator gate

Status: PENDING. No authenticated Admin save/reload or operator visual acceptance is claimed.

This gate comes directly from implementation-stage rules 8 and 9 in `docs/ENTITY_REFACTOR_PLAN.md`: “Perform authenticated Admin edit/save/reload for every field changed by the stage” and “Perform desktop and 390 px browser QA while preserving the accepted appearance.” The automated browser/data checks are complete. Stage 7 cannot be committed/pushed as accepted or followed by Stage 8 until this gate passes.

The remaining acceptance is deliberately limited to the concrete changed surfaces:

1. In dev Admin, open/save/reload Page 1480 (Video) and Page 2389 (Library/Videos). Confirm the retained Custom HTML blocks and five embed URLs remain intact with their distinct titles, lazy loading and referrer policy.
2. In dev Appearance → Widgets, open/save/reload Slider text (block 2), Map text (block 3), and Footer contacts (block 6). Confirm the Core headings/paragraphs are editable, no invalid-block warning appears, and the retained footer address/email HTML is intact. Newsletter title/text remains editable in its native widget; Contact remains the native CF7 block. No new form or subscriber setting needs to be changed.
3. Accept the final desktop and 390 px appearance of the homepage Newsletter, map/contact/footer, About root, Staff/Member/Partner directories, a Country Page including Vietnam, Video and Library/Videos. The release screenshots and `qa-summary.json` are available for comparison. All affected Pages were already checked automatically at both widths.
4. Do not submit CF7 or Newsletter, create a subscription, send a campaign, publish a Testimonial, or change fixture 2458. Mail transport is outside this gate.
5. After the operator confirms acceptance, rerun the read-only Stage 7/data and production-isolation checks to capture any Admin-save effects, record the confirmation without inventing per-step detail, update the accepted protocol/debt note, and commit/push this stage. Keep `EH-D019` open for Stage 9 regression.

Browser access available to this execution was an unauthenticated Chrome/CDP target. CLI persistence/block serialization checks are retained as automated evidence and are not represented as an authenticated editor round trip.
