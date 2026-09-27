# Stage 1 preflight — dev DB snapshot

The operator authorized a DB-only backup before Stage 1. The source was verified as `/home2/nykvymmy/dev.ehpmi.org` → `nykvymmy_ehpmidev`; production `/home2/nykvymmy/public_html` → `nykvymmy_ehpmi` was checked read-only. No Stage 1 migration or production change was performed.

`wp db check` passed for 53 tables. The dump has 53 `CREATE TABLE` and 22 `INSERT INTO` statements, including the `wp_posts` and `wp_newsletter` schemas and nonempty data. Server and downloaded local gzip tests passed; SHA-256 matches at both locations and the local `SHA256SUMS` check passed.

The archive, manifest and checksum file were uploaded to the project's `EHPMI/database/dev` folder. Drive metadata readback confirmed each exact filename, size, MIME type and parent folder. The archive was fetched as a streamed Drive file reference, but its downloaded bytes were not independently re-hashed; an isolated restore was not performed. This is a verified pre-change DB restore point, not a complete site recovery package without compatible Git/media artifacts.

The unencrypted archive includes Newsletter data. The server and temporary local archive files were restricted to owner read/write (`0600`); Drive sharing permissions were not independently audited in this step.

See `backup.yml` and the timestamped manifest for identifiers and precise evidence. Stage 1 still requires a deterministic dry run, rollback command and its own implementation QA before any data change.
