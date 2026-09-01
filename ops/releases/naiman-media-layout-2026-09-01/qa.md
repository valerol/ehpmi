# Naiman project media layout QA — dev — 2026-09-01

## Outcome

The approved project-specific image layout and alt-text refactor passed dev-only data, code, desktop and mobile checks. Production was read only and unchanged.

## Boundary and recovery

- Dev post: `project` ID `761` at <https://dev.ehpmi.org/projects/remediation-of-the-irrigation-channel-contaminated-with-mercury-in-naiman-kyrgyzstan/>
- Dev database: `nykvymmy_ehpmidev`
- Pre-migration database backup SHA-256: `62db014e6889632ac98be61d74044b8a2f25f948ff42460e0c996c1fd6ba37cd`
- Drive copy: <https://drive.google.com/file/d/1TsusrrQy5yBf0cbKCzLa7Yvenu0fxvCH/view?usp=drivesdk>
- Archive integrity and 53-table SQL structure check: PASS
- Pre-release theme archive: `/home2/nykvymmy/backups/2026-09-01_102040Z_ehpmi-dev-theme-before-naiman-media-layout.tar.gz`

## Content and accessibility

- The preflight required the exact 15-attachment sequence before writing.
- Fifteen contextual English alt texts were saved both in block HTML and `_wp_attachment_image_alt`.
- The map attachment `774` uses the wide image style and has a factual caption.
- Seven existing Columns pairs received purpose-specific styles without changing attachment IDs or order: five `4:3`, one `3:2`, and one portrait `3:4`.
- The release verifier found 15 image blocks, 15 matching non-empty alts, seven expected pair styles and migration marker `2026-09-01-v1`.

## Frontend layout

Browser QA was repeated after replacing the stale dev WP-Optimize minification cache with a clean cache.

- Desktop viewport `1280x720`: the five `4:3` pairs render as `512x384` images inside a centered `1040px` grid.
- Desktop viewport `1280x720`: the `3:2` pair renders as two `512x341` images.
- Desktop viewport `1280x720`: the portrait pair renders as two `408x544` images inside a centered `832px` grid.
- All seven desktop pairs have equal image height within their pair and use `object-fit: cover`.
- The map renders at the available `1140px`, within its `1200px` contract, with alt and caption visible in the DOM.
- Mobile viewport `390x844`: every pair reports a single `358px` grid column; crop is disabled through `object-fit: contain` and natural height.
- Desktop and mobile checks both returned zero horizontal overflow.
- The final frontend contains 15 images and zero empty alt attributes.

## Code and operational QA

- PHP syntax: PASS for migration, verifier and deployed theme `functions.php`.
- `git diff --check`: PASS.
- Local/deployed SHA-256: PASS for `style.css`, `editor-style.css` and `functions.php`.
- Theme version: `1.6`.
- Maintenance mode was disabled after migration.
- Temporary migration and verification scripts were removed from the dev document root after verification.
- Domain protocol was updated to `v0.6.1`; its generated PDF passed visual page review and text checks.

WP-CLI emits deprecation notices from its bundled dependencies under the hosting PHP runtime. No application fatal or migration error occurred.

## Production non-change evidence

- production URL: `https://ehpmi.org`;
- production database: `nykvymmy_ehpmi`;
- WordPress: `7.0.4`;
- theme: `1.2`;
- Naiman migration marker: absent.

## Rollback

1. Enable maintenance mode on dev and confirm `DB_NAME=nykvymmy_ehpmidev`.
2. Restore the theme from `/home2/nykvymmy/backups/2026-09-01_102040Z_ehpmi-dev-theme-before-naiman-media-layout.tar.gz`.
3. Download the named Drive database backup and verify its SHA-256.
4. Import it into `nykvymmy_ehpmidev` only.
5. Purge the WordPress and WP-Optimize caches, disable maintenance mode and repeat the release verifier and responsive checks.

Rollback was prepared but not executed. Authenticated editor save/reload remains covered by project debt `EH-D011`.
