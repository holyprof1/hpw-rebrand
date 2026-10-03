#!/usr/bin/env python3
"""
Link equity triage for removed Holyprofweb URLs.

Usage:
  python tools/link-equity.py --removed removed.json --gsc <folder with the Search Console CSV exports> \
      [--logs logevidence.json] [--out link-equity-report.csv]

Inputs
  removed.json   {"trashed": [{id, path, title, cat, words}], "gone": [paths], "rules": ["/from | /to"]}   (dump from the site)
  --gsc folder   any CSV whose name or header matches one of:
                   Top linked pages     (target page, incoming links, linking sites)
                   Latest links / More sample links   (target page, linking page [, last crawled])
                   Performance Pages    (top pages, clicks, impressions, ctr, position)
  --logs         optional JSON from the server-log pass: {path: {hits, g_ref, ext_ref{host: n}, googlebot}}

Output: one row per removed URL with the evidence and a proposed class:
  RESTORE SAME URL | 301 TO EQUIVALENT LIVE PAGE | KEEP 410 | MANUAL REVIEW
The class is a proposal. A page is only restored after it is rewritten and sourced; redirects are applied with
tools/apply-redirects.php; nothing here changes the site.
"""
import argparse, csv, glob, json, os, re, sys, urllib.parse, collections

LIVE_ENTITY = {  # old-slug token -> live page (same entity only)
    'booking-com': 'booking-com-explained', 'agoda': 'agoda-explained', 'expedia': 'expedia-group-explained', 'priceline': 'priceline-explained',
    'trip-com': 'trip-com-explained', 'kiwi-com': 'kiwi-com-explained', 'aliexpress': 'aliexpress-explained', 'shein': 'shein-explained',
    'temu': 'temu-explained', 'etsy': 'etsy-explained', 'depop': 'depop-explained', 'back-market': 'back-market-explained', 'stockx': 'stockx-explained',
    'stubhub': 'stubhub-and-viagogo-explained', 'viagogo': 'stubhub-and-viagogo-explained', 'alibaba': 'alibaba-com-explained', 'joom': 'joom-explained',
    'wayfair': 'wayfair-explained', 'yesstyle': 'yesstyle-explained', 'quince': 'quince-explained', 'whatnot': 'whatnot-explained',
    'freetaxusa': 'freetaxusa-explained', 'zenhotels': 'zenhotels-explained', 'welocalize': 'welocalize-explained', 'eneba': 'eneba-explained',
    'flighthub': 'flighthub-explained', 'paystack': 'paystack-explained', 'moniepoint': 'moniepoint-explained', 'chime': 'chime-explained',
    'upstart': 'upstart-explained', 'luno': 'luno-explained', 'fairmoney': 'fairmoney-explained', 'data-annotation': 'dataannotation-explained',
    'dataannotation': 'dataannotation-explained', 'handshake': 'handshake-ai-explained', 'mechanical-turk': 'amazon-mechanical-turk-explained',
    'incogni': 'incogni-explained', 'insurify': 'insurify-explained', 'surfshark': 'surfshark-vpn-explained', 'jaecoo': 'jaecoo-7-phev-specs-and-price',
    'elon-musk': 'elon-musk-companies-and-roles', 'mrbeast': 'mrbeast-jimmy-donaldson-profile', 'tosin-eniolorunda': 'tosin-eniolorunda-moniepoint-founder',
}
# Linking domains that are not real editorial links: ignored in the root-domain count and listed in the report.
QUESTIONABLE = re.compile(r'(\.xyz|\.top|\.click|\.loan|\.casino|\.bet|\.porn|\.xxx|\.work|\.gq|\.tk|\.ml|\.cf|\.ga)$|casino|poker|porn|escort|viagra|payday|seo-?(links|service)|backlink|directory|bookmark|article-?submit|pbn|spam', re.I)


def norm_path(u):
    u = (u or '').strip()
    if not u:
        return ''
    p = urllib.parse.urlsplit(u).path or '/'
    if not p.endswith('/') and '.' not in p.rsplit('/', 1)[-1]:
        p += '/'
    return p


def host(u):
    h = urllib.parse.urlsplit((u or '').strip()).netloc.lower()
    return h[4:] if h.startswith('www.') else h


def root_domain(h):
    parts = h.split('.')
    if len(parts) >= 3 and parts[-2] in ('co', 'com', 'org', 'net', 'gov', 'ac') and len(parts[-1]) == 2:
        return '.'.join(parts[-3:])
    return '.'.join(parts[-2:]) if len(parts) >= 2 else h


def read_csv(path):
    with open(path, encoding='utf-8-sig', errors='replace', newline='') as f:
        rows = list(csv.reader(f))
    if not rows:
        return [], []
    return [h.strip().lower() for h in rows[0]], rows[1:]


def col(header, *names):
    for i, h in enumerate(header):
        if any(n in h for n in names):
            return i
    return None


def num(v):
    try:
        return float(str(v).replace(',', '').replace('%', '').strip() or 0)
    except ValueError:
        return 0.0


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--removed', required=True); ap.add_argument('--gsc', default='')
    ap.add_argument('--logs'); ap.add_argument('--out', default='link-equity-report.csv')
    a = ap.parse_args()
    rem = json.load(open(a.removed, encoding='utf-8'))
    logs = json.load(open(a.logs, encoding='utf-8')) if a.logs else {}
    info = {t['path']: t for t in rem['trashed']}
    for g in rem['gone']:
        info.setdefault(g if g.endswith('/') else g + '/', {'id': 0, 'path': g, 'title': '', 'cat': '', 'words': 0})
    redirected = {r.split('|')[0].strip() for r in rem['rules'] if '|' in r}

    links = collections.defaultdict(lambda: {'incoming': 0, 'sites': 0, 'domains': set(), 'junk': set()})
    perf = {}
    files = glob.glob(os.path.join(a.gsc, '*.csv')) if a.gsc else []
    if not files:
        print('NOTE: no Search Console exports given; classifying from server-log evidence only (no backlink data).')
    for f in files:
        header, rows = read_csv(f)
        name = os.path.basename(f).lower()
        t = col(header, 'target page', 'linked page', 'top pages', 'page')
        if t is None:
            print('skip (no page column):', name); continue
        if col(header, 'clicks') is not None and col(header, 'impressions') is not None:
            c, im, pos = col(header, 'clicks'), col(header, 'impressions'), col(header, 'position')
            for r in rows:
                p = norm_path(r[t]); perf[p] = {'clicks': num(r[c]), 'impr': num(r[im]), 'pos': num(r[pos]) if pos is not None else 0}
            print('performance:', name, len(rows)); continue
        src = col(header, 'linking page', 'source')
        inc = col(header, 'incoming links', 'links'); sit = col(header, 'linking sites', 'sites')
        for r in rows:
            p = norm_path(r[t]); L = links[p]
            if src is not None and len(r) > src and r[src].strip():
                h = root_domain(host(r[src]))
                (L['junk'] if QUESTIONABLE.search(h) else L['domains']).add(h)
            if inc is not None and len(r) > inc: L['incoming'] = max(L['incoming'], num(r[inc]))
            if sit is not None and len(r) > sit: L['sites'] = max(L['sites'], num(r[sit]))
        print('links:', name, len(rows))

    out, summary = [], collections.Counter()
    for p, t in sorted(info.items()):
        L = links.get(p, {'incoming': 0, 'sites': 0, 'domains': set(), 'junk': set()})
        domains = max(len(L['domains']), int(L['sites']) - len(L['junk']) if L['sites'] else 0)
        pf = perf.get(p, {'clicks': 0, 'impr': 0, 'pos': 0})
        lg = logs.get(p, {})
        ext = lg.get('ext_ref', {}) if isinstance(lg, dict) else {}
        slug = p.strip('/')
        live = next(('/' + v + '/' for k, v in LIVE_ENTITY.items() if re.search(r'(^|-)' + re.escape(k) + r'(-|$)', slug)), '')
        value = domains * 10 + min(L['incoming'], 50) + pf['clicks'] * 0.5 + pf['impr'] * 0.01 + len(ext) * 2
        if p in redirected:
            cls, why = 'ALREADY 301', 'redirect rule exists'
        elif live and (domains or pf['clicks'] or value >= 0):
            cls, why = '301 TO EQUIVALENT LIVE PAGE', 'same entity as ' + live
        elif domains >= 3 or (domains >= 1 and (pf['clicks'] >= 5 or pf['impr'] >= 300)):
            cls, why = 'RESTORE SAME URL', 'external links plus search demand: rewrite and restore if the topic is still useful'
        elif domains >= 1 or pf['clicks'] >= 5 or pf['impr'] >= 300 or len(ext) >= 1:
            cls, why = 'MANUAL REVIEW', 'some signal but not enough to restore automatically'
        else:
            cls, why = 'KEEP 410', 'no external links, no meaningful search demand'
        summary[cls] += 1
        out.append({'path': p, 'title': t.get('title', ''), 'class': cls, 'reason': why, 'equivalent_live_page': live, 'linking_domains': domains,
                    'incoming_links': int(L['incoming']), 'ignored_questionable_domains': ';'.join(sorted(L['junk'])), 'clicks': pf['clicks'], 'impressions': pf['impr'],
                    'avg_position': round(pf['pos'], 1), 'log_hits': lg.get('hits', 0), 'log_external_referrers': ';'.join(sorted(ext)), 'value_score': round(value, 1)})
    out.sort(key=lambda r: -r['value_score'])
    with open(a.out, 'w', newline='', encoding='utf-8') as f:
        w = csv.DictWriter(f, fieldnames=list(out[0].keys())); w.writeheader(); w.writerows(out)
    print('\nremoved URLs:', len(out)); [print(' ', k, v) for k, v in summary.most_common()]
    print('with external links:', sum(1 for r in out if r['linking_domains']))
    print('report written to', a.out)


if __name__ == '__main__':
    main()
