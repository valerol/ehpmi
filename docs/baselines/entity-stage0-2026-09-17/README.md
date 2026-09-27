# Entity Stage 0 visual baseline

Captured from the public `https://dev.ehpmi.org` pages on 2026-09-17. The commands in `capture.sh` only read public pages; they do not authenticate or write to WordPress. Run with `bash capture.sh` on macOS with Google Chrome and `cwebp` installed. Chrome's temporary PNGs are retained under `/private/tmp/ehpmi-stage0-raw-2026-09-17/`; the compact WebP QA artifacts are in this directory.

Each of the eleven representative views has a desktop (1440 × 7000 px) and narrow (390 × 5000 px) WebP, except captures made before the reduced-height retry may be taller. These are deliberately tall viewport captures, **not guaranteed full-page captures**. A page longer than the specified height may be cut off. The narrow run uses Chrome's window-size emulation, not a physical phone; confirm any suspected mobile issue in a normal device browser before fixing it.

See `docs/ENTITY_STAGE0_AUDIT_2026-09-17.md` for the route and data inventory, observations, and open acceptance gates.
