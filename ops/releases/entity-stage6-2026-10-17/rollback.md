# Stage 6 rollback

Target: dev only (`/home2/nykvymmy/dev.ehpmi.org`, DB `nykvymmy_ehpmidev`). Production is not a rollback target because Stage 6 performs zero production writes.

1. Reconfirm the dev document root and database.
2. To roll back only the mobile image/carousel gate while retaining the earlier Stage 6 ordering/alt/Admin changes, restore these guarded pre-mobile copies atomically after checking their SHA-256 values:
   - `files/slider.php.before-stage6-mobile-gate` -> `wp-content/themes/ehpmi/template-parts/slider.php`; SHA-256 `6cfb0177ad00d3b3e7f54ecb6d7ec3da9d99b99f2ad770a66c23df209ec4df9a`.
   - `files/onload.js.before-stage6-mobile-gate` -> `wp-content/themes/ehpmi/onload.js`; SHA-256 `756c13d5f2a7b63596e45be9673f8e917d08f5d6171d65569ba201fb16a380ff`.
   - Purge only the development WP-Optimize minify cache through its plugin API so restored JavaScript is not shadowed by a stale bundle.
3. To roll back the complete Stage 6 implementation, restore the earlier guarded copies after checking their recorded SHA-256 values:
   - `files/slider.php.before-stage6` -> `wp-content/themes/ehpmi/template-parts/slider.php`; SHA-256 `e6b9f5e28f84c81276176fa114d50a57f8bc089bcbe6d631e662d7f5febb7aab`.
   - `files/ehpmi-core.php.before-stage6-admin-order` -> `wp-content/plugins/ehpmi-core/ehpmi-core.php`; SHA-256 `d6d1a176abf5a6087bdec65d010b7e9c39be910496572de1dfc281c08f051032`.
   - Restore `onload.js` from `files/onload.js.before-stage6-mobile-gate` using the SHA above.
4. Run PHP lint, JavaScript syntax, homepage HTTP smoke, mobile network/runtime QA, and the pre-Stage 6 inventory comparison.
5. Database restoration is not normally required: the only automated DB write was temporary lifecycle record 2457, which was deleted in the same verifier. Operator-owned fixture 2458 was already Trash/order 15 before the mobile-gate deployment and was not modified by this work. If cleanup cannot be proved, restore the verified archive recorded in `backup.yml` into `nykvymmy_ehpmidev` only.
6. Never run these commands against `/home2/nykvymmy/public_html` or `nykvymmy_ehpmi`.
