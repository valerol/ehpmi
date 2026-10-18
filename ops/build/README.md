# EHPMI frontend build

Use Node.js 20 or newer from the repository root:

```sh
npm ci --ignore-scripts
npm run check
```

`npm run build:css` compiles `wp-content/themes/ehpmi/css/style.less` with the lockfile's exact `less@4.2.2`. `npm run check:css` compiles in memory and requires byte-for-byte equality with the committed `css/style.css`. Stage 8 established parity with the accepted pre-stage stylesheet, SHA-256 `20f62ccf02d47fcaa619cb3409a799eb6495109d43715a1f18f178ab97701487`; no design or CSS source change is required.

`npm run check:assets` verifies every checked-in vendor file against `vendor-assets.json`. `npm run build:vendor` restores Bootstrap 5.1.3 and Font Awesome Free 6.7.2 from the locked npm packages and Overpass v19 subsets from their exact font URLs. Each source is checked against its SHA-256 before writing. The frozen Google Fonts CSS response retains its original unicode ranges and weights, with only the font URLs replaced by local relative paths. It is checked in as source; the build never depends on a new mutable CSS API response.

The theme ships the same Bootstrap release and the same Font Awesome Free release observed in the accepted pre-stage runtime, including v4/v5 compatibility faces, WOFF2 and TTF files. Licenses are retained beside each dependency. No npm tooling or Node runtime is required on the WordPress server; deploy the checked-in theme assets.

The local WP-Optimize 4.6.1 date fix is separate from this frontend build. Its exact upstream and patched hashes and patch path are in `ops/plugins.yml`. During recovery, verify the upstream hash before applying `ops/patches/wp-optimize-4.6.1-date.patch`, then require the patched hash. Plugin upgrades must review or retire this patch explicitly.
