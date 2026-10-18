# Stage 7 rollback

Target: `/home2/nykvymmy/dev.ehpmi.org`, database `nykvymmy_ehpmidev`, home `https://dev.ehpmi.org`. Production is never a rollback target. The exact preceding Git commit is `55295474e7cf3f65a5f7919140559dd99f322311`; the verified dev database archive and Drive object are in `backup.yml`.

Four existing runtime files were copied and checksum-verified before replacement. Their paths and preceding/final SHA-256 values are in `runtime-files.json`; the original copies remain under `/home2/nykvymmy/backups/entity-stage7-2026-10-18/files/`. Only the two new owned files (resolver and dedicated stylesheet) are removed by a full rollback. The content delta preserves the exact preceding bodies of Pages 1480/2389 and block widgets 2/3/6; its current safe-Group file is `content-before-v3.json`, SHA-256 `294821c90ad898a0bcad4f20d56899832e266b693856c118d4b3e512caa734f9`. The old v2 remains historical evidence. The narrowly equivalent Admin serialization `allowfullscreen` / `allowfullscreen=""` is accepted by the Page guard; every other byte remains guarded.

The `rollback.php` default mode verifies target identity, every live/fallback checksum, and every affected current body before doing anything. This validation passed on the final runtime. No actual restore/recovery rehearsal was executed or claimed.

After copying the release script to the server, the non-mutating validation command is:

```sh
cd /home2/nykvymmy/dev.ehpmi.org
wp eval-file /tmp/ehpmi-stage7-rollback.php
```

Only when a rollback is actually authorized, append `apply` to that same command. The script restores the two Page bodies and three widgets, removes its own marker, restores the old singular template before the old functions file, then removes its own two new files. It stops on drift instead of overwriting subsequent user changes. Standard WordPress revisions 2459/2460 are retained; they are not deleted as test data.

Run PHP lint, homepage/Page HTTP and browser checks, verify Page/widget values against the original inventory, and repeat production isolation after restoration. Inspect the served stylesheet bundle and refresh only dev minification through the plugin API if it still serves stale CSS. A blind full-DB restore is not needed for this scoped change and would discard subsequent editorial work; the compressed dev DB is a last-resort target-specific recovery package. Do not restore the incident's heading-only widget snapshot as an operational fallback: it is forensic evidence of the loss. Full Stage 7 rollback restores verified pre-stage complete widget values. Revisions observed in the manual phase are retained.
