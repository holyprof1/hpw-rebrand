# HolyprofWeb Production Checklist

## Security
- Anonymous frontend submissions must stay `pending`.
- Review, salary, subscribe, contact, reaction, and search endpoints should return `429` after repeated bursts.
- Turnstile or reCAPTCHA can be enabled from settings without changing templates.
- Public remote fetches must use safe public URLs only and keep TLS verification on.
- Admin notification emails should be throttled to avoid inbox flooding.

## SEO
- When Rank Math is active, the theme must not emit its own canonical/schema/title stack.
- Check `View Source` for a single canonical tag.
- Verify `robots.txt` points to `/sitemap_index.xml` when Rank Math is active.
- Confirm homepage, singles, categories, and blog/report archives are indexable unless intentionally blocked.
- Confirm personalized recommendation blocks do not change the main crawlable body content for bots.

## Favicon
- Prefer WordPress Site Icon in Settings > General.
- If Site Icon is not set, the theme fallback uses `assets/images/favicon.ico|png|svg`.
- Browser, CDN, and optimization caches may keep an old favicon until cache expiry.

## Ads
- Ad code is stored in WordPress options, so cache clears and theme file edits should not erase it.
- Only administrators should manage ad code.
- Script optimization, CSP, consent tools, and ad blockers can still suppress rendering even when slots are saved.
- Test desktop and mobile variants for each enabled slot after deploy.

## Post-launch checks
- Submit a frontend report and verify it lands in `pending`.
- Submit a review and salary entry from the same IP repeatedly and confirm throttling.
- Confirm Rank Math title/meta/schema still appear.
- Confirm favicon loads in a fresh browser session.
- Confirm header, inline, sidebar, and mobile ad slots still render after cache clear.
