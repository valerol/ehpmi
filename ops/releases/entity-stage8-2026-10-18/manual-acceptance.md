# Stage 8 accepted by the operator

Status: `ACCEPTED`. Date: 2026-10-18. Timezone: Asia/Makassar. Source: direct operator confirmation relayed in the active authorized Stage 8 task.

Exact visual confirmation:

> Stage 8 visual QA passed desktop and 390: homepage, projects and about/ehpmi.

Exact native Media Library confirmation:

> Native Media Library работает. RML folders отсутствуют. Thumbnails видны в Grid, оригинальные файлы открываются. Attachments 147, 212, 484 и 2371 имеют заполненный Alt Text; ошибок отсутствующего файла нет.

Exact separate deletion authorization:

> Удалить RML plugin files на dev

The guarded deletion removed exactly `/home2/nykvymmy/dev.ehpmi.org/wp-content/plugins/real-media-library-lite` at 2026-10-18T09:14:05Z: 296 files, 6584 KiB before removal. The target is absent. The verified plugin archive remains at `/home2/nykvymmy/backups/entity-stage8-2026-10-18/rml-plugin-before.tar.gz`; the original verified DB archive and Drive readback remain in `backup.yml`. RML tables, the 31-folder/401-relation export, all uploads and the production plugin were preserved.

The operator confirmation supplies the actual `/projects/` and `/about/ehpmi/` desktop/390 visual gate that the original browser harness did not cover correctly. It also supplies the native Admin Grid, original-file and Alt Text visibility acceptance. No additional browser edit/save sequence or mail submission is inferred. Prior capability-checked internal REST save/reload evidence remains separate.

Post-deletion read-only verification covers plugin absence/listing, native Media REST/data, public home/Projects/About/media URLs, all 467 attachments/files, 362 meaningful-public-image alts, RML map/table retention, 172 subscribers/all Newsletter hashes, fixture 2458 Trash/Order15, retained Stages 3–8 and production isolation.

`EH-D009`, `EH-D010`, `EH-D014`, `EH-D015` and `EH-D018` are closed. `EH-D019` remains open for Stage 9 integrated regression. Stage 9 has not started. Accepted Stage 8 Git outcome is recorded separately in `git-outcome.md` and the final operator-facing report.
