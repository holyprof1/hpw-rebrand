# Phase 2 runbook (content reset and redesign)

State after 2026-10-02: 705 posts removed (trash + HTTP 410 via `hpw_gone_paths`), 37 posts noindex (`_hpw_noindex`),
4 sourced rewrites, spam comments cleared, ad subsystem deleted, new design (`assets/css/site.css`).

## Tools (CLI, `php -d memory_limit=1024M tools/<name>.php`)
| Tool | Purpose | Safe by default |
|---|---|---|
| content-audit.php | scores every post, writes audit.csv/json | read-only |
| apply-audit.php | trash REMOVE posts + register 410, set noindex, drop dead redirect rules, mark spam comments | dry run unless `--apply`; archives removed posts first |
| publish-rewrites.php | publishes `tools/rewrites/<id>.json` over existing posts, keeps the URL | dry run unless `--apply` |
| clean-boilerplate.php | strips certain theme-generated blocks | dry run unless `--apply` |
| taxonomy-report.php | what happens to each category archive | read-only |
| indexnow-submit.php | submits changed + removed URLs to IndexNow | dry run unless `--apply` |

Long jobs: run with `nohup` in the background on the server (ssh calls time out near two minutes).

## Rollback
1. Code: `git checkout pre-phase2-content-cleanup` (tag) on the server theme directory, purge LiteSpeed.
2. Content: restore `~/backups/pre-phase2-apply-*.sql.gz` (+ options dump), or restore single posts from
   `~/backups/phase2/archive/removed-posts-*.json` (set status back to `publish`, delete its path from `hpw_gone_paths`).

## Editorial rules enforced in code
Nothing publishes automatically; no thin-content expansion; no demo/sample posts; comments are always held for moderation.
Search-engine submission (Google Search Console, Bing Webmaster Tools) is a manual owner task.
