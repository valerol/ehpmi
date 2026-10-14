# Entity Stage 3 - dev release evidence

Status: **ACCEPTED** on 2026-10-14 after explicit operator confirmation that responsive visual QA passed at desktop and 390 px for all seven Project listing routes.

Stage 3 was deployed to dev only. Production remained read-only and unchanged. Stage 4 was not started.

## Implementation

- Project pagination rewrite rules preserve ownership of the structural WordPress Pages.
- Page-owned Project listings query published `project` records through `WP_Query`, 12 per page, date descending.
- Root and filtered routes paginate while Current, Past, and Potential retain their `project_status` filters.
- Project pagination reuses the accepted News pagination component and left-aligned partial-row behavior.
- Paginated Project Pages emit a self-canonical URL for the current page.
- Homepage Project output remains unpaginated and retains its existing three-card limit.

## Backup and rollback evidence

The precautionary dev database backup was reverified:

- Archive: `/home2/nykvymmy/backups/entity-stage3-2026-09-29/20260929T110513Z_nykvymmy_ehpmidev_pre-stage3.sql.gz`
- Size: `491605` bytes
- SHA-256: `b65de52535d6bbf6a226750152d0f0a5202a71611eb06f6e2e8d4ec5a75632cf`
- Gzip integrity: PASS
- Google Drive file ID: `1ECk2rilFMjKG3FRL_qEpIezU9iOFhfj_`

Exact pre-deployment file copies were reverified under `/home2/nykvymmy/backups/entity-stage3-2026-09-29/files-20260929T110513Z`. Their paths, sizes, and SHA-256 values are recorded in `backup.yml`. Stage 3 performed no database mutation, so rollback uses the exact protected files and does not require a database restore.

## Deployment evidence

Dev runtime hashes match the accepted local files:

- `wp-content/plugins/ehpmi-core/ehpmi-core.php`: `21f03b4838dc6d12735c7ade1987b48d7b46c8d70884e15e7dc10dac3778feb3`
- `wp-content/themes/ehpmi/template-parts/projects.php`: `d9425e9be2fda4e7541f15610d49fa5f48f963ebfbc2fdd7d232bc806407665c`
- `wp-content/themes/ehpmi/singular.php`: `4d73bac0004299678c6716a3f94b53a21923b600e8b0909032669a8852de66f6`

The remote inventory remained byte-for-byte equal before and after deployment.

## Automated verification

The fresh remote dev verifier returned `result=PASS` with `failures=[]`.

- Root Project pages: `[12, 12, 4]`
- Current Project pages: `[6]`
- Past Project pages: `[12, 1]`
- Potential Project pages: `[9]`
- Pagination rewrite rules: both present
- Stored redirects checked: 141

Public structural QA passed for all seven routes:

- `/projects/`
- `/projects/page/2/`
- `/projects/page/3/`
- `/projects/current/`
- `/projects/past/`
- `/projects/past/page/2/`
- `/projects/potential/`

Every route returned HTTP 200 with the exact expected IDs, page size, self-canonical URL, and breadcrumb. The 28 Projects remained unique and partitioned as Current 6, Past 13, Potential 9. All 141 redirect-map sources returned the exact expected 301 Location with zero failures.

Regression checks passed:

- Homepage Project block: three cards, unpaginated
- News pagination: 12 items on page 1 and one item on page 2
- Sitemap providers: Post, Page, Project, Staff Member, and User
- Taxonomy sitemap provider: absent
- Sitemap counts: Project 28, Staff Member 20

## Responsive visual acceptance

On 2026-10-14, the operator explicitly confirmed that Stage 3 visual QA passed at desktop and 390 px for all seven Project listing routes. The accepted checks covered card and image rendering, pagination visibility and alignment, left alignment of partial final rows, stable ordering, and absence of horizontal overflow, clipping, or broken layout.

The earlier automated browser capture did not produce a complete durable screenshot set, so visual acceptance is based on the operator's explicit manual confirmation and is identified as such rather than attributed to automation.

## Production isolation

Read-only production checks confirmed:

- DB name: `nykvymmy_ehpmi`
- Home and site URL: `https://ehpmi.org`
- `wp-content/plugins/ehpmi-core/ehpmi-core.php`: absent
- Production `projects.php` SHA-256: `db1580c3838903e275a197e39794d5e666df88eb160bfec7a7cc4712baa73b1a`
- Production `singular.php` SHA-256: `c85d9dfbbb65f5a2ba46b995f08fc5492c2d801d66875af760a97088d311b040`

No production write was performed.
