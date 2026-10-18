# Stage 6 mobile gate automated evidence

Date: 2026-10-17
Environment: development only (`/home2/nykvymmy/dev.ehpmi.org`, `nykvymmy_ehpmidev`, `https://dev.ehpmi.org`)

## Runtime and rollback hashes

- Live `slider.php`: `a49587a77318ac022c81d9a2aa007fa51368862a2f13dfb53fc8972f23e58710`
- Live `onload.js`: `bde4017668dc2346a3f182ca196f306f868fe3e8b14a35e1004599e88a16aa29`
- `files/slider.php.before-stage6-mobile-gate`: `6cfb0177ad00d3b3e7f54ecb6d7ec3da9d99b99f2ad770a66c23df209ec4df9a`
- `files/onload.js.before-stage6-mobile-gate`: `756c13d5f2a7b63596e45be9673f8e917d08f5d6171d65569ba201fb16a380ff`

## Automated results

- Local PHP lint, JavaScript syntax and `git diff --check`: PASS.
- Staged remote PHP lint/render/source verifier: PASS, failures empty.
- Live remote PHP lint and active-slide/source verifier: PASS, failures empty.
- Admin-order verifier: PASS against the resumed read-only baseline. Visible published rows are IDs 2370/2372/2374 with Orders 10/20/30. ID 2458 was already Trash/order 15 at resumed preflight and remained unchanged.
- Homepage HTTP: 200. Three `<picture>` elements, three `media="(min-width: 1025px)"` sources, three data-URI placeholders, 800x600 dimensions, accepted alts, first eager/high, later lazy, marker present, and `data-bs-ride` absent.
- Initial WP-Optimize regenerated bundle contained stale pre-gate JavaScript. Development minify cache was purged through `WP_Optimize_Minify_Commands::purge_minify_cache()`.
- Fresh minify generation: `1792239677`; mobile-gate bundle SHA-256: `e45b6a200bcddbc17911f8f38959937049484e94ebf5862a600bcec66c56d6e4`; required exclusion/lifecycle/cycle/dispose code present.
- Chrome/CDP 390x844: Hero hidden, global text visible, Hero Bootstrap instance absent, all current sources are data URIs, external Hero image requests: zero.
- Responsive 1440x900: Hero instance exists, interval active, transition changed active index 0→1, all real `currentSrc` values selected, all `naturalWidth` values are 800.
- Responsive return to 390x844: Hero instance absent after disposal, Hero hidden, global text visible.
- Strict desktop all-images-complete assertion remains a documented non-blocking automation limitation: slide 1 reported complete; slides 2 and 3 had real URLs and `naturalWidth=800` but `complete=false` at the bounded capture. No further headless attempts were made after operator direction. Manual desktop/390 acceptance was recorded separately as PASS on 2026-10-18 (Asia/Makassar), based only on the operator's statement, “ручное подтверждение выполнено”; it does not change this captured automated result. See `manual-acceptance.md`.

## Production isolation

- Root: `/home2/nykvymmy/public_html`
- Database: `nykvymmy_ehpmi`
- Home: `https://ehpmi.org`
- `ehpmi-core.php`: absent
- Production `slider.php`: `ce65a04b8cf43162c29d8317f710e5ca37c9028729fb2fc04943e2676101f1ac`
- Production `onload.js`: `3b0df6ce2ad8e926fbabc744e12a776d38868f2c14ec3c6304d8709c731eea73`
- Production writes: zero
