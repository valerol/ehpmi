# Routing and category-model refactor QA — dev — 2026-08-31

## Outcome

The dev-only routing refactor passed its data, HTTP, canonical, sitemap, breadcrumb and desktop browser checks. Public hierarchy is now owned by Pages; Projects are a dedicated content type; News remains ordinary Posts; Materials are admin-only records filtered by a private type taxonomy. `Remove Category URL` and `Allow HTML in Category Descriptions` are inactive and no longer participate in routing.

Production was read only and retained its original database, WordPress/theme versions, permalink structure and plugin state. No Newsletter message or form submission was sent.

## Boundary and recovery point

- Dev URL: `https://dev.ehpmi.org`
- Dev database: `nykvymmy_ehpmidev`
- Production database verified separately: `nykvymmy_ehpmi`
- Pre-migration database backup: `2026-08-31_054723Z_ehpmi-dev-before-routing-refactor.sql.gz`
- Backup SHA-256: `a3db8ee8f47a36a2cf5e890d4e004a3e64b2bda9e1f362d0047294e7007e121d`
- Drive copy: <https://drive.google.com/file/d/1v-lQ0vDeysjrpL65tOrro7XgoqJsRd8-/view?usp=drivesdk>
- Backup gzip integrity, 53-table database check and Drive metadata readback: PASS
- Pre-stage code commit: `f8ddf9bbe1e2d6cb5091256a535b889f062739cc`
- Implementation commit: `dbba503b0439c28d2019b4bf9d62a548c7228157`

## Resulting content model

| Area | Before | After | Result |
|---|---|---|---|
| Projects | 28 Posts classified by Categories | 28 `project` records | PASS |
| Project grouping | Public Category hierarchy | Private `project_status`: 6 current, 13 past, 9 potential | PASS |
| News | 13 Posts under Blog/News Categories | 13 Posts at `/blog/<slug>/` | PASS |
| Library intro content | 5 placeholder Posts | 5 child Pages; sources retained as drafts | PASS |
| Materials | 19 public CPT records classified by Categories | 19 admin-only records using private `material_type` | PASS |
| Structural navigation | 12 Category menu items | 12 Page menu items | PASS |
| Public hierarchy | Category/Page name collision | 12 canonical hierarchical Pages | PASS |
| Legacy Category relations | Active public model | Retained only as rollback data | PASS |
| Newsletter | 172 subscribers, 0 sends | 172 subscribers, 0 sends | PASS |

The project-owned `ehpmi-core` plugin now registers all project content types independently from the theme. Projects use the block editor and have a visible Project Status admin column. Materials retain title, thumbnail, file and Material Type management but have no free-text editor, public single/archive or public REST route.

## URL, redirect and sitemap QA

- all 141 recorded legacy paths returned exactly one `301` with the expected `Location`;
- all 72 unique redirect destinations returned HTTP `200`;
- canonical Projects Pages returned `200`: `/projects/`, `/projects/current/`, `/projects/past/`, `/projects/potential/`;
- canonical Blog Pages returned `200`: `/blog/`, `/blog/news/`;
- `/library/` and all five child Pages returned `200`;
- Project and News singles use `/projects/<slug>/` and `/blog/<slug>/`;
- old hierarchical and flat Project/News routes redirect to their canonical single;
- old Material singles redirect directly to the stored PDF;
- legacy Category and flat section routes redirect to their corresponding Page;
- no tested canonical page contained an internal link to a legacy redirect source;
- an unknown path returned the expected `404`.

The sitemap index contains Posts, Pages, Projects, Staff and Users. It contains 13 News URLs, 28 Project URLs and 35 Page URLs including the homepage. Category, Project Status, Material Type and Material single sitemaps are absent. No legacy structural URL appears in the sitemap.

## Breadcrumb and public browser QA

Breadcrumb NavXT produced the intended Page hierarchy:

- `Home / What we do / <Project>`;
- `Home / Blog / <News item>`;
- `Home / Library / Action plans`.

Desktop browser checks covered the homepage, Projects root/current/single, Blog/News/single, Library, Action Plans, Publications and Videos. Headings, cards, PDF links, embedded videos, header, menus and footer rendered. The homepage had no broken images or horizontal overflow. A template defect found during QA (`/projects/%project%/`) was corrected to use each Project's canonical permalink and then rechecked in the browser.

The browser runtime did not expose viewport emulation, so this stage did not repeat an independent mobile visual round-trip. CSS and visual design were not changed; nevertheless mobile verification remains explicitly open rather than inferred.

## Admin and technical QA

- WordPress Admin exposes `Projects`, `Materials`, `Pages`, `Members`, `Partners`, `Hero slides`, `Staff` and `Testimonials` screens;
- Projects are public/block-editable; `project_status` is private, editable and visible as an admin column;
- Materials are admin-editable but not publicly queryable, not block-editable and not exposed through REST (`404 rest_no_route`);
- `material_type` is private, editable and visible as an admin column;
- all navigation items are now Page objects, including the 12 formerly Category-backed items;
- database check: PASS, all 53 tables;
- WordPress 7.1 core checksum verification: PASS;
- WordPress.org plugin checksums: PASS, 13 of 13; `ehpmi-core` is not on WordPress.org and is verified by `project-code.sha256`;
- available plugin updates: 0;
- maintenance mode after release: inactive;
- theme version: 1.4;
- local project code equals deployed dev code: PASS, 38 of 38 file SHA-256 values;
- executable migration verification: PASS.

WP-CLI under the hosting PHP runtime emits deprecation messages from its bundled Mustache and color libraries. A same-timestamp `Cannot modify header information` warning was caused by that CLI output. No application fatal/error was found, and public requests did not display these messages.

Two existing PDFs are large (approximately 51.3 MB and 11.3 MB). Their full-download tests exceeded 20 seconds, but both returned `200 application/pdf` and support byte ranges. This is a content/performance observation, not a routing failure.

## Production non-change evidence

Read-only production verification after the dev migration returned:

- URL: `https://ehpmi.org`;
- database: `nykvymmy_ehpmi`;
- WordPress: 7.0.4;
- theme version: 1.2;
- permalink structure: `/%category%/%postname%/`;
- no routing migration marker;
- `ehpmi-core` not active.

## Stage rollback procedure

This is the scoped rollback for this stage; the complete site recovery procedure is in `docs/EHPMI_DOMAIN_PROTOCOL.md`.

1. Enable maintenance mode on `dev.ehpmi.org` and re-confirm that the target database is `nykvymmy_ehpmidev`.
2. Deploy the unique code from commit `f8ddf9bbe1e2d6cb5091256a535b889f062739cc`.
3. Download the named Drive backup and verify SHA-256 `a3db8ee8f47a36a2cf5e890d4e004a3e64b2bda9e1f362d0047294e7007e121d`.
4. Import the uncompressed SQL into `nykvymmy_ehpmidev` only. The restored options recover the old post types, menus, permalink structure and active legacy plugins.
5. Confirm that `ehpmi-core` is inactive; remove its server directory only after the restored site passes checks.
6. Flush WordPress rewrite rules and cache.
7. Disable maintenance mode and repeat database, checksum, public-page, Category URL and Newsletter-count QA.

Rollback was prepared but not executed. Therefore `EH-D007` remains open until the isolated QA-D recovery rehearsal passes. Authenticated edit/save and mobile visual round-trips remain explicit stage exceptions, not implied PASS results.
