# Holyprofweb SEO recovery work — 10 October 2026

## Completed

- Five new researched articles saved as WordPress drafts, approximately 490–535
  words each. They contain original editorial checklists, inline primary sources,
  a Sources section, a research-method note, excerpts, descriptive slugs and links
  to relevant existing articles. No purchases or hands-on tests are claimed.
- Editorial briefs stored in the post editor, including search intent, suggested
  imagery and checks needed before publication. Human-confirmation boxes remain
  untouched. All five remain drafts rather than being scheduled automatically.
- The 28 previously configured old-to-new redirects were checked live: each
  returned 301 directly to its expected equivalent URL, then HTTP 200.
- The retired-URL report was regenerated for 710 paths. 28 already have redirects;
  the other 682 require review because complete backlink and search-performance
  evidence is unavailable. No new retirement, restoration or redirect was applied.
- The triage tool no longer describes missing exports as zero links or zero
  demand. Missing measurements are blank with explicit unknown coverage. Entity
  matches are redirect candidates requiring search-intent review: a general
  company explainer need not replace a complaint-resolution page. Domain-name/TLD
  flags are retained for inspection, not used to discard links or create disavows.
- Five regression checks passed for absent data, explicit zero rows, intent review,
  existing redirects and domain-name flags.

## Saved articles

| WordPress ID | Article | Main existing cluster |
|---|---|---|
| [62907](https://holyprofweb.com/wp-admin/post.php?post=62907&action=edit) | Browser Game Not Loading on Your Phone? 7 Checks Before You Reset Anything | Lagos Life / Danfo Run |
| [62909](https://holyprofweb.com/wp-admin/post.php?post=62909&action=edit) | Refurbished Phone Buying Checklist: Battery, Locks and Returns | Back Market / repairable phones |
| [62911](https://holyprofweb.com/wp-admin/post.php?post=62911&action=edit) | Booking.com Free Cancellation: Which Deadline Actually Applies? | Booking.com / Agoda |
| [62913](https://holyprofweb.com/wp-admin/post.php?post=62913&action=edit) | Data Removal vs VPN: What Each Privacy Tool Can Actually Do | Incogni / Surfshark |
| [62915](https://holyprofweb.com/wp-admin/post.php?post=62915&action=edit) | Etsy Seller Asking You to Pay Elsewhere? What to Check | Etsy / marketplaces |

These topics fill practical questions around existing explanations. Search intent
is inferred from the questions and site coverage; no keyword-volume or ranking
numbers have been invented. Draft source snapshots were reviewed on 10 October
2026; policies and regional terms need rechecking if publication happens later.
No stock imagery pretending to show an actual product test was added.

## Public backlink research: scope and findings

This is a small public-source inventory, not a complete backlink-index audit.
Search results may be stale. A mention is not automatically a clickable backlink,
followed link, endorsement or ranking signal. Rel attributes, indexation and link
counts have not been inferred from snippets.

| Source | Evidence | Target / outcome | Recovery decision |
|---|---|---|---|
| [Reddit helmet discussion](https://www.reddit.com/r/Scams/comments/16yerfh/cheap_star_wars_helmets/) | Search excerpt contains an exact Bossnlab article URL. Direct retrieval was blocked (403); Firecrawl retrieval failed, so full page/link attributes are unverified. | `/is-bossnlab-legit-or-scam-bossnlab-com-reviews/` returns 404 | Candidate for same-URL, evidence-led historical reconstruction only after merchant/source research. Never redirect it to an unrelated shop or the home page. |
| [Nairaland iGreef thread](https://www.nairaland.com/7885410/igreef-legit-scam-igreef.com-reviews) | Live page retrieved; its text contains the exact article URL. Author name is Holyproftobilob, so do not count it as an independent editorial endorsement without further verification. | `/is-igreef-legit-or-scam-igreef-com-reviews-2023/` returns 410 | Review whether an accurate historical article still serves readers; old investment assertions must not be republished as established facts. |
| [Facebook pricing discussion](https://www.facebook.com/groups/133205788736543/posts/650403167016800/) | Search excerpt mentions a Buycrab URL; full target and post not verified. | Unknown | Resolve the exact URL and retrieve original evidence before deciding to restore anything. |
| [Medium healthy-habits post](https://medium.com/@priyanka.vig/adopting-healthy-habits-small-changes-big-impact-0c4aa5a31dcd) | Search excerpt mentions an L'Evate You article; live link not inspected. | Unknown | Medical/product claims require primary evidence and qualified review; do not regenerate speculative health reviews to chase a link. |
| [LinkedIn former writer](https://ng.linkedin.com/in/elbazseo) | Search excerpt mentions past work for Holyprofweb. | Brand mention; link not verified | Potential historical brand evidence, not a backlink count. |
| [LinkedIn founder profile](https://ng.linkedin.com/in/tobi-arowosegbe) | Search excerpt links the developer's portfolio subdomain. | `tobi.holyprofweb.com`, not necessarily the main site | Keep the genuine professional profile consistent; no outreach sent. |
| [Sur.ly](https://sur.ly/i/holyprofweb.com/) and [CuteStat](https://holyprofweb.com.cutestat.com/) | Automated directory/domain descriptions in search results | Main domain | Do not treat stale traffic estimates or directory mentions as recovery measurements. |

Commercial relationships should be disclosed and sponsored links qualified.
Public brand mentions should not be mistaken for independent editorial references.

## Regenerated report provenance

`docs/audit/link-equity-reviewed-2026-10-10.csv` is generated from the existing
historical retired-URL and log-only snapshot. The input JSON files record that
reconstruction. Referrer domains from that historical CSV are preserved as
presence-only log observations; their original hit counts were unavailable.
Logs and search snippets are not Search Console backlink/performance exports.
The old report is retained as history; its KEEP 410 recommendations must not be
interpreted as a fresh evidence-based deletion instruction.

Reproduction:

```sh
python3 tools/link-equity.py \
  --removed docs/audit/recovery-removed-input-2026-10-10.json \
  --logs docs/audit/recovery-log-input-2026-10-10.json \
  --out docs/audit/link-equity-reviewed-2026-10-10.csv
python3 -m unittest discover -s tests -p 'test_link_equity.py'
```

Live redirect results: `docs/audit/redirect-live-check-2026-10-10.json`.

## Recovery work order

1. Establish measurement when Search Console access becomes available: compare
   clicks/impressions by page and query over matching periods; inspect indexing,
   manual actions and security issues. Check the Links report. Until then, the
   cause and scale of the ranking loss remain unconfirmed.
2. Prioritise genuinely useful older pages with verified referring pages and a
   clear current or historical reader need. Reconstruct sourced content at the
   same URL where appropriate; redirect only to a replacement that answers the
   old question. Keep removal decisions reversible while evidence is incomplete.
3. Review and release one or two saved drafts per week, updating sources on the
   publication date. Add a contextual link from the relevant existing article
   only after its new destination is actually published. Avoid links to drafts.
4. Add first-hand evidence where feasible: labelled screenshots, recorded test
   steps, device/version details and limits. Never convert desk research into
   purported personal experience. Use real authors and real publication dates.
5. Earn new references through original useful work: a tested browser-game
   compatibility sheet, a dated device-inspection checklist, and transparent
   platform-policy comparisons. Research relevant maintainers or publications
   that could use the resource, then prepare a specific pitch. No emails, forum
   posts, paid links, link exchanges or directory submissions were sent.
6. Review the same available metrics after four weeks. A title or design refresh
   cannot guarantee restored rankings or repair a sitewide quality issue alone.

## Technical baseline retained

The earlier live audit found 57 sitemap URLs returning 200 with one H1 and a
meta description, plus valid JSON-LD and no duplicate titles or noindex sitemap
entries. Canonicals, robots, sitemap providers, author/policy pages and raster
social previews already exist in the native theme; stacking an SEO plugin is not
required. Baseline Lighthouse scores were 100 in all four categories; those are
lab checks, not evidence of organic recovery. Historic unused attachment alt text
remains an editorial audit task, rather than being filled with guessed keywords.

Google's relevant primary guidance:

- [Helpful, reliable, people-first content](https://developers.google.com/search/docs/fundamentals/creating-helpful-content)
- [Diagnosing a traffic drop](https://developers.google.com/search/docs/monitor-debug/debugging-search-traffic-drops)
- [Redirects and Search](https://developers.google.com/search/docs/crawling-indexing/301-redirects)
- [Link spam and scaled content policies](https://developers.google.com/search/docs/essentials/spam-policies)
- [Search Console Links report](https://support.google.com/webmasters/answer/9049606)
