# Entity Stage 6 QA

## Scope and decisions

- Runtime scope is three files: `wp-content/themes/ehpmi/template-parts/slider.php`, `wp-content/themes/ehpmi/onload.js`, and project-owned `wp-content/plugins/ehpmi-core/ehpmi-core.php`.
- Hero text remains the global `slider-text` block widget; no CTA exists and no per-slide fields were added. See `ownership-decision.md`.
- Existing meaningful attachment alts are authoritative. Post titles are not used as image alts. An empty attachment alt remains intentionally empty if an editor classifies a future slide as decorative.
- Fallbacks retain the same three theme images and accepted meaningful descriptions instead of generic `Hero slide 1/2/3` strings.
- Ordering is `menu_order ASC, ID ASC`. Invalid or missing images are omitted; if no valid managed slide remains, the deterministic three-image fallback is rendered.
- Hero images now use a desktop-only `<picture><source media="(min-width: 1025px)">` and an embedded transparent data-URI `<img>` placeholder. At 1024px and below the existing hidden-carousel/global-text appearance is retained without external Hero image requests.
- Hero no longer uses `data-bs-ride`. A project-owned marker and `matchMedia` lifecycle create/cycle the Bootstrap carousel on desktop and dispose it on mobile; the general Bootstrap initializer explicitly excludes this Hero.

## Automated QA

- Local PHP lint, JavaScript syntax and `git diff --check`: PASS.
- Staged and live remote PHP lint: PASS.
- Staged managed render: PASS (three images; accepted alt order; first eager/high, later lazy; desktop-only picture source and data-URI placeholder contract).
- Forced-empty fallback render: PASS (three stable assets; accepted meaningful alts; loading and responsive picture contracts preserved).
- Simulated missing image for slide 2370: PASS (two valid images; no broken slide; first remaining image became eager/high).
- Active-slide invariant verifier: PASS with `failures=[]` after all runtime deployments. It checks the three Published records only; the operator fixture is not an active slide.
- Automated reversible lifecycle: PASS with cleanup; its temporary ID 2457 was deleted and the invariant verifier passed afterward.
- Hero Admin Order verifier: PASS staged and live. Historical acceptance evidence proved IDs 2370/2458/2372/2374 at Orders 10/15/20/30 while 2458 was Draft. Before the mobile-gate work resumed, ID 2458 had been externally moved to Trash. The current non-mutating verifier proves visible IDs 2370/2372/2374 remain ordered 10/20/30, the Order column remains visible/sortable, explicit Title/Order sorts remain explicit/deterministic, and another post type is unchanged.
- Operator fixture ID 2458 was not changed by this work. Its resumed-preflight and post-verifier state is Trash, Order 15, title `Title: Stage 6 validation test reordered`.
- Public HTTP after mobile-gate deployment: PASS. Homepage returns 200 and contains three desktop-only sources, three data-URI placeholders, preserved 800x600 dimensions/alts, first eager/high and later lazy, the project-owned marker, and no `data-bs-ride`.

## Mobile image and carousel gate

- `onload.js` is versioned by `filemtime()` in the accepted enqueue path. WP-Optimize initially generated a new bundle URL from stale minified source; the development minify cache was purged through the plugin API. Fresh generation `1792239677`, bundle SHA-256 `e45b6a200bcddbc17911f8f38959937049484e94ebf5862a600bcec66c56d6e4`, contains the Hero exclusion, responsive initializer, `cycle()` and `dispose()`.
- Real Chrome/CDP at 390x844: PASS for the mobile contract. Hero carousel display is `none`, global text is visible, Bootstrap Hero instance is absent, every `currentSrc` is the embedded data URI, and external Hero image requests are exactly zero.
- Responsive switch to 1440x900: PASS for Bootstrap instance creation, active interval, real Hero URL selection, and a 0→1 carousel transition. All three images reported `naturalWidth=800` and real `currentSrc` values.
- Switch back to 390x844: PASS; the Bootstrap Hero instance was disposed, the carousel remained hidden, and global text remained visible.
- The bounded browser harness retains a documented non-blocking **PENDING** strict all-images-complete assertion: at final capture slide 1 had `complete=true`, while the two lazy slides had real URLs and `naturalWidth=800` but still reported `complete=false`. This automated assertion was not rerun or converted to PASS by the manual acceptance. Per operator direction no further headless attempts were made. Evidence is in `browser-network-qa.json`, `mobile-gate-390.png`, and `desktop-gate-1440.png`.

## Authenticated Admin and visual acceptance

Manual desktop and 390 px acceptance: PASS, recorded on 2026-10-18 (Asia/Makassar) from the operator's direct statement, “ручное подтверждение выполнено”. See `manual-acceptance.md`. The confirmation closes the outstanding manual acceptance; it supplies no additional per-step Admin, browser, screenshot or timing detail, and none is inferred here.

The reported Admin discrepancy was valid: the old table exposed Title/Date only and defaulted to Date. Stage 6 now shows sortable Order and defaults the Hero list to `menu_order ASC, ID ASC`. The existing read-only verifier evidence remains the detailed evidence for this behavior. ID 2458 was already in Trash, Order 15, at the recorded preflight and was not modified during acceptance finalization. Strict desktop lazy-image completion remains a documented non-blocking automation limitation.

## Production isolation and known exception

Production read-only isolation passed after the mobile-gate deployment: root `/home2/nykvymmy/public_html`, DB `nykvymmy_ehpmi`, no `ehpmi-core` file, unchanged production `slider.php` SHA-256 `ce65a04b8cf43162c29d8317f710e5ca37c9028729fb2fc04943e2676101f1ac`, and unchanged production `onload.js` SHA-256 `3b0df6ce2ad8e926fbabc744e12a776d38868f2c14ec3c6304d8709c731eea73`. Production writes are zero. `EH-D019` (the pre-existing visually broken Newsletter form) remains an unchanged known exception assigned to Stage 7/9. Stage 6 does not modify Newsletter, forms, widgets, subscribers, or mail behavior and makes no causality claim.

The 2026-10-18 acceptance finalization uses this prior deployment evidence; it does not claim a new live isolation check. Finalization changes only local release documentation and records the accepted runtime/evidence in Git. No SSH, database/content update, cache purge or new browser run is part of this finalization.
