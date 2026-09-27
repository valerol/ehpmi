# Entity Stage 0 audit — 2026-09-17

Status: **STAGE 0 EVIDENCE COMPLETE**. Read-only live inventory and representative desktop/mobile capture; no database, server-file, or production changes. This accepts the Stage 0 inventory, not any Stage 1 write. The existing narrow-screen clipping noted below is a baseline finding, not a visual change made by this audit.

## Scope and reproducibility

- Observed: 2026-09-16 22:37 UTC (2026-09-17 Vladivostok).
- Dev path: `/home2/nykvymmy/dev.ehpmi.org`; `wp config get DB_NAME` = `nykvymmy_ehpmidev`; `siteurl` = `https://dev.ehpmi.org`; WordPress 7.1.
- Production path was queried only for `DB_NAME` = `nykvymmy_ehpmi`. The two installations use distinct databases.
- Local baseline: `refactor/dev-2026-08-25`, `ae17f21c8f616843104575103eaf4d903dc3426a`; clean before this audit file.
- Read-only source: `wp db query` against dev `wp_posts` and `wp_postmeta`; attachment reference checks joined `wp_posts` on attachment ID. Counts exclude revisions, attachments, old `person`/`staff`, and content types outside the plan.
- Reproduce the status counts with: `SELECT post_type, post_status, COUNT(*) AS n FROM wp_posts GROUP BY post_type, post_status ORDER BY post_type, post_status;`
- Reproduce the ID lists with: `SELECT post_type, post_status, COUNT(*) AS n, GROUP_CONCAT(ID ORDER BY ID) AS ids FROM wp_posts GROUP BY post_type, post_status HAVING COUNT(*) <= 40 ORDER BY post_type, post_status;`
- The per-record, read-only export in `docs/ENTITY_STAGE0_RECORDS_2026-09-17.tsv` freezes all 120 IDs, statuses, `menu_order` values, featured-image IDs, Material file IDs, legacy additional-image IDs and stored Country/Staff relationships. Serialized relationship values are kept verbatim so their order is not lost. Empty cells mean no stored value, not a failed lookup.

## Frozen record counts and IDs

| Type | Status | Count | IDs |
| --- | --- | ---: | --- |
| `hero_slide` | publish | 3 | 2370, 2372, 2374 |
| `material` | publish | 19 | 482, 485, 488, 491, 494, 498, 501, 504, 507, 510, 513, 516, 520, 523, 526, 529, 532, 1739, 1852 |
| `member` | publish | 7 | 103, 116, 117, 118, 119, 1493, 1735 |
| `page` | publish | 34 | 13, 17, 21, 23, 25, 27, 29, 33, 75, 327, 347, 355, 362, 374, 378, 647, 656, 1453, 1480, 1647, 1841, 1844, 2378, 2379, 2380, 2381, 2382, 2383, 2384, 2385, 2386, 2387, 2388, 2389 |
| `page` | draft | 1 | 31 |
| `partner` | publish | 1 | 1455 |
| `project` | publish | 28 | 448, 455, 457, 461, 558, 717, 761, 803, 811, 821, 832, 857, 892, 1039, 1061, 1073, 1113, 1420, 1545, 1690, 1692, 1694, 1770, 1873, 1888, 1899, 1953, 1988 |
| `staff_member` | publish | 20 | 166, 176, 183, 188, 193, 197, 234, 236, 240, 243, 246, 249, 252, 257, 261, 551, 757, 911, 1516, 2344 |
| `testimonial` | draft | 7 | 127, 133, 134, 135, 136, 160, 161 |

Total in scope: **120** records (112 published, 8 draft, 0 private). Outside this target set, dev also contains one published `person` (271) and one published `staff` (165). Neither type appears in the live registered post-type list; representative direct query URLs returned 404. No project-owned theme/core-plugin query for those two types was found, but consumers in other plugins have not been exhaustively excluded. Do not delete the rows based on this audit alone.

## ACF and media facts

Local ACF JSON defines five groups: Member (`url`, misspelled `additonal_image`), Country (`leader`, `team`, `member`, `point`), Staff (`organization`), Material (`file`), and Project facts (seven fields). All are currently marked `required: 0`. Live ACF companion references agree with those field groups: `_url` = `field_6336c64641339`; `_additonal_image` = `field_6576b42d8de48`; `_file` = `field_6371e86e1de40`; `_organization` = `field_634161e581dc4`. The Country and Project companion keys are present on all respective stored field rows. There is no `additional_image` or `_additional_image` row yet.

| Group | Stored field → ACF field key |
| --- | --- |
| Member | `url` → `field_6336c64641339`; `additonal_image` → `field_6576b42d8de48` |
| Country | `leader` → `field_633fd14d19e06`; `team` → `field_633fd9691fb26`; `member` → `field_634109faf9126`; `point` → `field_63410a3a8ffd8` |
| Staff | `organization` → `field_634161e581dc4` |
| Material | `file` → `field_6371e86e1de40` |
| Project | `project_intro` → `field_ehpmi_project_intro`; `project_dates` → `field_ehpmi_project_dates`; `people_at_risk` → `field_ehpmi_people_at_risk`; `pollution_source` → `field_ehpmi_pollution_source`; `project_implementers` → `field_ehpmi_project_implementers`; `project_budget` → `field_ehpmi_project_budget`; `project_funding` → `field_ehpmi_project_funding` |

- Member: all 7 have featured-image IDs; `url` and `_url` each exist on all 7, but only IDs **116**, **117**, **1735** have a non-empty URL. Legacy `additonal_image` rows exist on 5 Members, but only ID **1493** has a non-empty image ID (**1498**). No Member/Partner has the corrected field yet.
- Partner: ID **1455** has featured-image ID **1456**, but has no `url` or additional-image ACF rows. This matches the current Member-only field-group location, not a failed migration.
- Material: all 19 have `file`, `_file`, and featured-image rows. All 19 non-empty file IDs and 19 thumbnail IDs resolve to attachment records. IDs **498** and **501** share thumbnail **1535**; this is not itself an error.
- Staff: all 20 have featured-image IDs and non-empty excerpts. `organization` rows exist on 11; only Staff **183** has a non-empty relationship (Member **116**). All `menu_order` values are 0.
- Hero: 2370/2372/2374 have order **10/20/30** and attachment IDs **2369/2371/2373**.
- Testimonial: all 7 remain drafts and have featured-image IDs; all `menu_order` values are 0.
- Project: all 28 have stored rows and companion keys for seven facts. Empty-value counts: `project_intro` 9, `project_dates` 3, `people_at_risk` 8, `pollution_source` 6, `project_implementers` 3, `project_budget` 4, `project_funding` 27. Project **455** has no featured image; the other 27 thumbnail IDs resolve to attachment records. All `menu_order` values are 0.
- No duplicate ACF field rows were found per post/key. Repeated `_wp_old_date` values occur on some records and appear to be WordPress historical metadata, not current ACF duplication.
- A read-only join over all stored numeric `_thumbnail_id`, `file`, and `additonal_image` values in the 120-record scope returned **zero** IDs without a matching `attachment` post. This checks record existence, not physical media-file readability.

### Stage 1 candidate-file baseline

The following live dev SHA-256 values exactly match the corresponding files in local commit `ae17f21c8f616843104575103eaf4d903dc3426a`:

| Project-owned file | SHA-256 |
| --- | --- |
| `wp-content/themes/ehpmi/template-parts/partners.php` | `37602ec756373b8bceaa8bcd185b3d11f5d42d899091060f562953b0dd14439b` |
| `wp-content/themes/ehpmi/css/style.less` | `866197a33c340a93e6f6819df64f17f730c3a62233a03b16b76f10a1af6e24de` |
| `wp-content/themes/ehpmi/css/style.css` | `2130df38969e34bf8ab29e770380789d8e55973043c0ce49fd64dad505062d08` |
| `wp-content/themes/ehpmi/acf-json/group_6336c6465a962.json` | `769be358b194aaaace486b62aaa06c68ee723e17696b406250081830be80126b` |
| `wp-content/plugins/ehpmi-core/ehpmi-core.php` | `4257e304d1b1d0cc3a13651a1ef603cdf45ace2316cc3b8f2c30e791aeae17d6` |

### Country-page relationships and order

Nine published children of Page **17** (`offices`) are ordered: Armenia **327** (10), Azerbaijan **656** (15), Georgia **347** (20), Kazakhstan **355** (30), Kyrgyzstan **362** (40), Mongolia **374** (50), Tajikistan **378** (60), Ukraine **647** (70), Vietnam **1647** (80). All nine store `leader`, `team`, `member`, and `point` rows, though some values are empty.

| Country | Leader ID | Team IDs in stored order | Member ID | Point |
| --- | ---: | --- | ---: | ---: |
| Armenia | — | 166 | — | 3 |
| Azerbaijan | — | 193 | 1735 | 4 |
| Georgia | — | 261, 249, 246, 252 | 118 | 2 |
| Kazakhstan | — | 234 | 119 | 5 |
| Kyrgyzstan | — | 183 | 116 | 7 |
| Mongolia | — | 243 | 103 | 6 |
| Tajikistan | — | 197 | 117 | 8 |
| Ukraine | — | — | — | 1 |
| Vietnam | 1516 | — | 1493 | 9 |

Armenia **327** also retains legacy `team_obj` and `_team_obj` rows; `team_obj` stores the same Staff ID **166** as `team`. Do not remove it without a usage check.

### Structural Page hierarchy

Top-level: 75 `newsletter`, 2378 `projects`, 2379 `blog`, 2380 `library`, 13 `about`, 1480 `video`, 17 `offices`. The `about` children include 29 `members`, 1453 `partners`, 27 `staff`, and draft 31 `strategic-documents`; `offices` has the nine country pages above. `projects` owns 2381/2382/2383 (`current`/`past`/`potential`); `blog` owns 2384 `news`; `library` owns 2385–2389 (five type Pages). The two child Pages of 1480 `video` are 1841 and 1844. This is an ID/parent baseline, not an approval to change routes.

## Public-route and visual checks performed

- The reproducible baseline under `docs/baselines/entity-stage0-2026-09-17/` contains **22 verified WebP screenshots** (11 public views × desktop 1440 px/narrow 390 px, 6.6 MB total), a route list and a capture script. The views cover homepage/Hero, Member and Partner directories, Staff directory and single, Country office, Project directory and single, Library and Material-type Pages, and a structural About Page. These are tall viewport captures, not guaranteed full-page captures; see the baseline README. The narrow capture emulates a 390 px Chrome window rather than a physical phone.
- Read-only HTTP sweep on 2026-09-17: all **11/11** representative routes returned **200** at their requested URL, without an intervening redirect. All **10** non-home routes exposed a self-referencing canonical URL and a breadcrumb navigation landmark. The homepage exposed neither canonical nor breadcrumb; no homepage breadcrumb is expected, but the absent canonical should be tracked separately if SEO policy requires one. Representative breadcrumb chains were `Home → Who we are → Partners`, `Home → Who we are → Meet our team → Petr Sharov`, `Home → Countries → Georgia`, `Home → What we do → [Project title]`, and `Home → Library → Action plans`.
- Visual inspection of the saved narrow captures found right-edge clipping/horizontal overflow in at least the Member heading/body, Staff directory grid and Project cards. This is an **existing dev baseline observation**, not a regression caused by Stage 0. Confirm on a normal mobile browser and scope any repair separately; do not silently change the accepted design while doing entity data work.
- `/about/members/` uses a Page H1 and Member headings exposed as H3. `/about/partners/` exposes a Partner H3 without a visible Page H1; preserve the accepted visual appearance while later correcting semantics.
- HTTP checks: direct `?post_type=member&p=103`, `?post_type=partner&p=1455`, `?post_type=material&p=482`, and draft `?post_type=testimonial&p=127` returned **404**; representative Staff `/about/staff/petr-sharov/` and Project `/projects/detailed-and-rapid-environmental-assessments-of-sites-contaminated-with-obsolete-pesticides-in-tajikistan-2023-2024/` returned **200**.
- Local `ehpmi-core` registration declares Member, Partner, Material, and Hero non-public, while Staff and Project have public singles. Testimonial is currently declared public even though its seven records are drafts; this remains Stage 5 debt.
- A static `rg` search of PHP in the live dev theme and installed plugin directories (excluding translations and bundled vendor code) found no registration or `post_type` query for the old `person` or `staff` types. The only exact `'staff'` string matches are the current Staff **Page slug** in `template-parts/staff.php` and `singular.php`. This does not prove there are no dynamically configured or database-stored consumers; retain the two old rows.

## Explicit unknowns and Stage 1 prerequisites

1. The captured viewports may end before the bottom of very long Pages. For a change that affects lower content, inspect that part of the relevant page before deploying the change. Recheck suspected mobile clipping in a normal mobile browser before choosing a fix.
2. The static PHP search does not cover dynamic plugin settings, database-stored code, or excluded vendor code. Do not delete the old `person`/`staff` records without a targeted consumer/dependency check.
3. A full public-route, redirect, sitemap and device regression belongs to the relevant implementation stage and final acceptance; the Stage 0 sweep intentionally sampled representative views.
4. The operator authorized a separate pre-Stage 1 dev DB backup on 2026-09-17; it was verified and uploaded to Drive, with evidence under `ops/releases/entity-stage1-preflight-2026-09-17/`. Before Stage 1 writes, finish the deterministic dry-run/rollback evidence required by `ENTITY_REFACTOR_PLAN.md`. The five known candidate-file checksums above are reconciled; extend that list if implementation touches additional files.

No Stage 1 migration or code deployment occurred. No backup was created during this read-only audit.
