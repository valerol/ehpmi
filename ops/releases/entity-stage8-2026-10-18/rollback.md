# Stage 8 dev rollback

Preceding accepted Git commit: `68f6ce2a7d3c9a359e7df8014ab922682b403415`. The required root is `/home2/nykvymmy/dev.ehpmi.org`, database `nykvymmy_ehpmidev`, home/siteurl `https://dev.ehpmi.org`. Production is excluded.

The `migrate.php` script refuses another root/database/home and verifies current alt/body values before rollback. `validate-rollback` was run successfully without reversing the applied stage. `rollback` has not been executed and is not claimed as an end-to-end recovery rehearsal. RML files were subsequently deleted with explicit operator authorization; the script now refuses any data rollback until the verified plugin archive is restored, preventing a partially applied rollback before plugin activation.

If a rollback is separately requested, first verify SHA-256 `e7c0ebd3d77205987a2d4256fa36dbdf308efe75ecc7efbcdcc14f0b211531ca` for `/home2/nykvymmy/backups/entity-stage8-2026-10-18/rml-plugin-before.tar.gz`, require the exact RML target to be absent, and inspect that every tar member is under `real-media-library-lite/` with no absolute/traversal path. Restore only that plugin from the archive:

```sh
tar -xzf /home2/nykvymmy/backups/entity-stage8-2026-10-18/rml-plugin-before.tar.gz -C /home2/nykvymmy/dev.ehpmi.org/wp-content/plugins
```

For the targeted data rollback, copy this release's `migrate.php` to an operator-owned temporary path, confirm the target identity, and run:

```sh
wp --path=/home2/nykvymmy/dev.ehpmi.org eval-file /absolute/path/to/migrate.php validate-rollback
wp --path=/home2/nykvymmy/dev.ehpmi.org eval-file /absolute/path/to/migrate.php rollback
```

The data rollback restores all 63 alt keys, preserving whether the old key existed; restores the four before-body values; reactivates the retained RML plugin; and removes only the Stage 8 media marker. Normal WordPress revision history is retained. Restore the two affected runtime files from these verified rollback copies after confirming their current Stage 8 hashes:

```sh
cp /home2/nykvymmy/backups/entity-stage8-2026-10-18/files/functions.php /home2/nykvymmy/dev.ehpmi.org/wp-content/themes/ehpmi/functions.php
cp /home2/nykvymmy/backups/entity-stage8-2026-10-18/files/class-wpo-cache-rules.php /home2/nykvymmy/dev.ehpmi.org/wp-content/plugins/wp-optimize/cache/class-wpo-cache-rules.php
```

The local vendor directory can remain unreferenced while the CDN enqueues are restored; no recursive deletion is needed. Re-run the pre-stage frontend and isolation checks. The pre-stage compressed DB archive and its Drive readback are recorded in `backup.yml`; a full DB import is a broader rollback requiring a fresh review of any later dev edits. Production must never be an import target.
