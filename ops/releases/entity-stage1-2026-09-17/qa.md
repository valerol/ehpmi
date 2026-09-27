# Entity Stage 1 — dev deployment and acceptance record

Status: **ACCEPTED on dev** on 2026-09-27. The authenticated Admin, data-preservation, public HTTP, and responsive acceptance gates are closed. The temporary legacy metadata read was removed from dev. Production was not changed. Stage 2 was not started.

## Preflight and applied change

- Starting branch `refactor/dev-2026-08-25`, preceding commit `ae17f21c8f616843104575103eaf4d903dc3426a`; dev document root `/home2/nykvymmy/dev.ehpmi.org`, database `nykvymmy_ehpmidev`. Production separately reports database `nykvymmy_ehpmi`.
- The verified compressed dev DB backup, SHA-256, and Drive ID are in `backup.yml`; four exact original theme files are in `/home2/nykvymmy/backups/ehpmi-stage1-files-2026-09-17/`. No isolated restore rehearsal was performed.
- `migrate.php` dry-run verified five Member value rows and five companion rows with exact IDs and values. Apply changed exactly 10 guarded `meta_key` values without changing stored image IDs, URLs, thumbnails, bodies, or statuses. Rollback dry-run passed without writes.
- The ACF group now serves Member and Partner with `url` and `additional_image`; the template and CSS use neutral organization names, verified attachment alt metadata, and accessible linked logos.

## Authenticated Admin acceptance

- The operator completed authenticated edit/save/reload QA for existing Member **1493** and Partner **1455**. Their final `post_modified_gmt` values are `2026-09-27 11:49:01` and `2026-09-27 11:49:44`, respectively.
- Member 1493 retained logo 1494 and Additional image 1498. Partner 1455 retained logo 1456 and an empty Additional image.
- Saving Partner 1455 correctly created one empty value row (`meta_id` 5925) and one ACF companion row (`meta_id` 5926). Therefore the accepted post-save corrected-key inventory is six `additional_image` rows and six `_additional_image` rows, with no legacy `additonal_image` keys on dev.
- The final verifier asserts the full eight-record baseline: IDs, types, statuses, exact URL values, all logo IDs, Additional image values, and every body SHA-256. Its final result was `PASS`, `failures=[]`, `WP_RC=0`.

## Compatibility-read removal and final deployment

- Removed only the temporary `get_post_meta( $organization_id, 'additonal_image', true )` fallback after the second data verification passed.
- Guarded deployment required the previously deployed template hash `9fadf8d47406ba7e1446475b075f83cfb048259dc19406e65c422d4a84e372ba`, wrote a protected rollback copy at `/home2/nykvymmy/backups/ehpmi-stage1-final-2026-09-27/partners.php.before-fallback-removal`, linted the staged file, and atomically renamed it into place.
- Final dev template SHA-256 is `6085e700c555a758645f8b36640d36b20004ce9a78444a3792a898feec09d0ae`; the protected rollback copy retained SHA-256 `9fadf8d47406ba7e1446475b075f83cfb048259dc19406e65c422d4a84e372ba`.
- Final verifier SHA-256 is `202d79a5dc8a5e9733ed8b33a91ca37a419f31dab02a3c3069f02e0aa6da679a`; its deployed evidence copy is `/home2/nykvymmy/backups/ehpmi-stage1-final-2026-09-27/verify-final.php`.
- No cache flush was needed: removing the fallback is render-inert after legacy-key removal. Cache-busted final requests and the post-deployment layout check passed.

## Public and responsive verification

- Final HTTP: `/`, `/about/members/`, and `/about/partners/` returned 200; direct Member `?post_type=member&p=103` and Partner `?post_type=partner&p=1455` returned 404.
- The full browser run covered homepage, Members, and Partners at 1280x720 and 390x844: all six returned 200, `clientWidth=scrollWidth`, no broken images were found, and expected carousel/card content appeared. Screenshots are under `screenshots/`.
- A bounded post-deployment DOM/layout rerun covered the same six route/viewport combinations and passed: homepage carousel count 1; Member cards 7; Partner cards 1; horizontal overflow 0 in every case.

Screenshot SHA-256 values:

- `ehpmi-stage1-home-1280x720.png`: `ba5b822cc56eb30e60feb6ed941c2e34e80f94c04b349b4c8915a6bf810e5eaf`
- `ehpmi-stage1-home-390x844.png`: `9c4d39852a7b14e0853b39fd5e603471cbe0e7380370f99d006b7c55a7342aa1`
- `ehpmi-stage1-members-1280x720.png`: `2a46f9e3242fb85332acea3c2041c9a572d018b4694608a881638cc8ee4ac1c6`
- `ehpmi-stage1-members-390x844.png`: `e79752bf1e19d2df91a0769b7df09c3291be6b195f991b92b60b6e7d0eac4edd`
- `ehpmi-stage1-partners-1280x720.png`: `58bbf6458c54443cf259467f3b07d52004ab7d2efcc2468a1c629eba6b572001`
- `ehpmi-stage1-partners-390x844.png`: `47ef84a655684ab81daa6b60d85129d62759bb6600e0f3bbe7eb462192d43547`

## Production isolation and scope

- Final production read-only check: `DB_NAME=nykvymmy_ehpmi`, legacy `additonal_image=5` and `_additonal_image=5`, no corrected-key rows. No production write, cache flush, file deployment, form submission, newsletter send, or plugin update was issued.
- Stage 1 is accepted only on dev. This acceptance does not authorize production deployment or Stage 2.

## Targeted rollback, if required

- For only the final fallback-removal change, restore `/home2/nykvymmy/backups/ehpmi-stage1-final-2026-09-27/partners.php.before-fallback-removal` to the dev template path through a staged copy and atomic rename, then verify its recorded SHA-256.
- For the metadata migration, first assess intervening Admin edits. The guarded migration script must match the expected state and must not be forced. The whole-database archive is a last-resort source because restoring it would overwrite intervening work.
- The key-level rollback guard was dry-run tested, but an actual end-to-end rollback and isolated database restore rehearsal were not performed.
