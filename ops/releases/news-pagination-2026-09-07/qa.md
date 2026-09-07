# News pagination release QA

Date: 2026-09-07  
Environment: `https://dev.ehpmi.org` only

## Preconditions

- Dev DB: `nykvymmy_ehpmidev`.
- Production DB: `nykvymmy_ehpmi`.
- Published News posts before deployment: 13.
- All five remote dev files matched baseline commit `7d003f05d9fefc997f9f5e9a523d401d61fa44ec` before deployment.
- Verified dev database backup: see `backup.yml`.

## Server verification

- PHP lint passed for `template-parts/news.php` and `singular.php`.
- Local and remote SHA-256 hashes match for all five deployed files.
- `/blog/news/`: HTTP 200.
- `/blog/news/page/2/`: HTTP 200 with no redirect to page 1.

## Rendered browser verification

Desktop:

- Page 1 contains 12 `.news--paginated article.news-block` elements.
- Page 2 contains one card and reports current page `2`.
- Page 2 card left edge equals the container left edge (`leftDelta = 0`).
- Heading link computed `text-decoration-line`: `underline`.
- `.news-text` computed `justify-content`: `flex-start`.
- News container computed `justify-content`: `space-between`.
- Pagination is visible on both pages.
- No horizontal overflow and no browser console errors.

Mobile viewport, 390 x 844:

- Page 1 still contains 12 cards.
- Pagination is visible.
- Document width equals viewport width (390 px); no horizontal overflow.
- Heading underline and top-aligned text remain active.

## Production control

- No files or database content were written under `/home2/nykvymmy/public_html`.
- Production remained outside deployment scope.
