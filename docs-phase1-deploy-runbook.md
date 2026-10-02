# Phase 1 deploy runbook (branch rebuild/discovery-publication)

Nothing here has been run against production. Do staging first if you can create one (noindex it).

## Before
1. Full backup: database and `wp-content/uploads` plus the current theme folder.
2. Note the current values of the options `hpw_discourage_indexing` (must be 0 in production, 1 on staging) and `hpw_enable_draft_autopublish`.

## Deploy
1. Upload the theme from this branch over `wp-content/themes/holyprofweb/` (no database changes are needed by hand).
2. First page load runs `holyprofweb_sections_install()` once: creates the four section categories, replaces the old tagline only if it is still "Trusted reviews. Verified insights. Real answers.", and flushes rewrite rules once (version option `hpw_sections_version`).
3. Purge the LiteSpeed cache (LiteSpeed Cache plugin > Toolbox > Purge All), then purge any CDN.

## Verify (use a normal browser User-Agent; very short UAs such as `Mozilla/5.0` get 406 from the host)
- `/wp-sitemap.xml` and `/sitemap-index.xml` return 200 XML; every child sitemap returns 200.
- `/robots.txt` lists both sitemaps and allows OAI-SearchBot, GPTBot, Googlebot, Bingbot.
- `/llms.txt` returns 200 text.
- `/`, `/apps-websites/`, `/products-tech/`, `/people/`, `/internet-trends/`, `/about/`, `/editorial-policy/`, `/corrections-updates-policy/`, one old article, one author page.
- An old article URL still returns 200 and is self-canonical; `/category/people/` redirects once to `/people/`.
- Fetch an article as Googlebot, Bingbot and OAI-SearchBot: 200, no `noindex`, no `X-Robots-Tag`.
- View source of an article: one canonical, `BlogPosting` JSON-LD, no `Review`/`AggregateRating`.

## Rollback
Re-upload the previous theme folder and purge LiteSpeed. The four new categories and the `hpw_sections_version` option are harmless to leave in place.

## Decisions for the owner
- Review the trust page copy in `inc/trust.php`; it states commitments (AI use, ad separation, corrections).
- Set real bios on the author accounts (Users > Profile). Author pages without a bio stay noindex.
- Consider switching off "auto-publish drafts" (`hpw_enable_draft_autopublish`) before the new editorial workflow starts.
- Review the third-party ad script `profitablecpmratenetwork.com` loaded on every page.
