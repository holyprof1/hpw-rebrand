# Holyprofweb site refresh and spam investigation

Date: 10 October 2026 (Africa/Lagos). Theme changes are in the WPVibe draft,
awaiting publishing approval. Mailbox filtering and quarantine are already live.

## Audit evidence

- All 57 sitemap URLs returned HTTP 200, one H1 and a meta description.
- No duplicate titles, invalid JSON-LD or noindex pages were found in the sitemap.
- Mobile Lighthouse baseline: performance, accessibility, best practices and SEO
  each 100/100; lab LCP 1.2 s, CLS 0, TBT 0. No CrUX field data available.
- Native theme SEO already emits descriptions, canonicals, structured data and
  sitemap providers. Installing another SEO plugin is unnecessary for these fixes.
- Six pages used an SVG placeholder for social previews. The draft uses a local
  1200 × 630 PNG, with dimensions, a more descriptive home title and description,
  and website Open Graph type for static pages.
- There are 886 image attachments, 829 without media-library alt text. Many belong
  to the old archive and cards deliberately use decorative empty alt attributes.
  Do not invent blanket descriptions for uninspected or unused images.

## Draft design and code

Warm cream/deep green palette, editorial serif headlines, a shorter home intro,
numbered section links and a desktop research panel. Mobile has a compact 2-column
section menu and one-column stories. Retains local fonts, lightweight CSS and
existing dark mode, menu and search. Prefer recent stories rather than letting a
much older illustrated story always occupy the lead. Search input has an explicit
accessible label.

Contact form changes: signed context/timestamp fields; IP-based limits that survive
User-Agent changes; known bot-name validation before mail delivery; uncached contact
page. LiteSpeed's `litespeed.conf.cache-exc` scalar JSON setting now excludes contact
and the comment submission endpoint. Existing honeypot, nonce, rate limits,
duplicate checks and subject validation remain in place.

## Spam findings and live remediation

The latest inbox messages were `[HPW Contact] General Enquiry` notifications from
the site's contact form. They reused `Robertkib`, rotating reply addresses and
translated generic price enquiries. SpamAssassin and Spam Box were already enabled;
this was not evidence that normal inbound mail scanning had been disabled.

Mailbox rule `HPW confirmed contact bot quarantine`, scoped to
`admin@holyprofweb.com`, matches both the HPW contact subject and `Name: Robertkib`.
Matching messages go to the spam folder; other enquiries retain normal delivery.
Exim filter trace confirmed both decisions without sending test messages.
139 matching existing messages were moved to `.spam/cur`, with an owner-only undo
manifest. No messages were permanently deleted and no other mailbox was changed.

A signed form is not a full human challenge. Existing Turnstile integration remains
available, but the saved Cloudflare token cannot manage Turnstile widgets. A
Turnstile-enabled token or existing site/secret keys would permit that additional
layer later. Do not claim these controls prevent every future spam campaign.

## Verification and backups

- `tests/contact-guards.php`: 17 passing checks against PHP 8.3 with an isolated
  WordPress fixture and stub mail transport. No real emails, DB writes or network
  requests. Valid enquiries call mail once; confirmed bot, forged token, honeypot
  and invalid nonce call mail zero times.
- Draft browser checks: desktop/mobile no horizontal overflow, one H1, menu opens,
  dark mode works, PNG HTTP 200, signed contact fields present, no JS errors.
- PHP draft writes passed the publishing plugin's syntax checks.
- Backup directory `/home/myvalcom/holyprofweb-backups/20261010`: `files.tar.gz`
  (~218 MiB) and `database.sql.gz` (~1.5 MiB), excluding runtime caches and the draft.
  Both gzip integrity checks passed; files are outside the web root and owner-only.
- Undo file: `quarantine-manifest.json` in that backup directory.

After publishing, check the live home/section/article/contact routes, metadata,
social PNG, uncached contact response and rejected bot submission. Theme publish
purges LiteSpeed automatically; avoid redundant full cache flushes.
