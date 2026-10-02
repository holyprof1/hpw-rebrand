#!/usr/bin/env python3
"""
Build tools/recovery/<id>.json from tools/recovery-src/<id>.json and validate editorial rules.

  python tools/build-recovery.py            # build everything, print problems
  python tools/build-recovery.py 1044 7045  # build only these ids

Source format (one file per restored page):
  id, slug (optional clean slug), section, cluster, title, seo_title, meta_description, dek, primary_query,
  facts{type,name,operator,launched,availability,pricing,url,limits}, body (HTML), sources [[label,url],...], merge [ids]

Output adds: content_html (body + Sources), related (cluster peers), image_alt, image_slug.
Rules checked: no review-farm words in titles/descriptions (unless "allow_terms" is true for genuine security
stories), no filler intros, direct-answer opening paragraph, at least 2 sources, sensible length.
"""
import json, os, re, sys, glob, html

HERE = os.path.dirname(os.path.abspath(__file__))
SRC = os.path.join(HERE, 'recovery-src')
OUT = os.path.join(HERE, 'recovery')
os.makedirs(OUT, exist_ok=True)

BANNED_TITLE = re.compile(r'\b(legit|legitimate|scam|scams|scammer|real or fake|fake or real|reviews?|complaints?|honest|rated|rating|is it safe)\b', re.I)
BANNED_INTRO = re.compile(r'^(if you (have|are|\'ve)|there are (many|several)|in (this|today\'s) (digital|modern)|with the rise|in the (digital|modern) age|are you (looking|searching)|many (people|users) (are )?(wondering|asking))', re.I)
SECTIONS = {'apps-websites', 'products-tech', 'people', 'internet-trends'}


def words(h):
    t = re.sub(r'<[^>]+>', ' ', h)
    return len(re.findall(r"[A-Za-z0-9’'\-\.]+", html.unescape(t)))


def load_all():
    items = {}
    for f in glob.glob(os.path.join(SRC, '*.json')):
        d = json.load(open(f, encoding='utf-8'))
        items[int(d['id'])] = d
    return items


def slug_of(d, id_):
    return d.get('slug') or ''


def main():
    only = set(int(a) for a in sys.argv[1:])
    items = load_all()
    # name -> (slug, cluster) for contextual links between entities in the same cluster
    names = {}
    for i, d in items.items():
        nm = d['facts'].get('name', '')
        if nm and d.get('slug'):
            names[nm] = (d['slug'], d.get('cluster', ''), i)
    problems = 0
    built = 0
    for i, d in sorted(items.items()):
        if only and i not in only:
            continue
        errs = []
        if d['section'] not in SECTIONS:
            errs.append('bad section')
        allow = bool(d.get('allow_terms'))
        for k in ('title', 'seo_title', 'meta_description', 'dek'):
            if not allow and BANNED_TITLE.search(d.get(k, '')):
                errs.append('banned word in ' + k + ': ' + BANNED_TITLE.search(d[k]).group(0))
        if len(d['seo_title']) > 70:
            errs.append('seo_title > 70 chars (%d)' % len(d['seo_title']))
        if not (110 <= len(d['meta_description']) <= 165):
            errs.append('meta_description length %d' % len(d['meta_description']))
        body = d['body']
        first = re.search(r'<p>(.*?)</p>', body, re.S)
        fp = re.sub(r'<[^>]+>', '', first.group(1)) if first else ''
        if BANNED_INTRO.search(fp.strip()):
            errs.append('filler intro')
        if not (20 <= words(fp) <= 95):
            errs.append('opening paragraph %d words' % words(fp))
        if len(d.get('sources', [])) < 2:
            errs.append('needs >=2 sources')
        wc = words(body)
        if wc < 280:
            errs.append('short body %d words' % wc)
        # contextual links to cluster peers (first mention, max 3, never inside headings/links)
        linked = 0
        for nm, (slug, cl, pid) in sorted(names.items(), key=lambda kv: -len(kv[0])):
            if pid == i or cl != d.get('cluster', '') or len(nm) < 5 or linked >= 3:
                continue
            pat = re.compile(r'(?<![\w/>"-])(' + re.escape(nm) + r')(?![\w<-])')
            def sub_once(m):
                return m.group(1)
            parts = re.split(r'(<a\b.*?</a>|<h[1-6].*?</h[1-6]>|<[^>]+>)', body, flags=re.S)
            done = False
            for idx, ptxt in enumerate(parts):
                if done or ptxt.startswith('<'):
                    continue
                m = pat.search(ptxt)
                if m:
                    parts[idx] = ptxt[:m.start()] + '<a href="/%s/">%s</a>' % (slug, m.group(1)) + ptxt[m.end():]
                    done = True
                    linked += 1
            body = ''.join(parts)
        src_html = '<h2>Sources</h2>\n<ol>\n' + '\n'.join(
            '<li><a href="%s" rel="noopener nofollow">%s</a></li>' % (html.escape(u, quote=True), html.escape(l)) for l, u in d['sources']) + '\n</ol>'
        peers = [pid for pid, (s2, cl, _) in ((v[2], v) for v in names.values()) if cl == d.get('cluster', '') and pid != i][:3]
        related = ['https://holyprofweb.com/%s/' % items[p]['slug'] for p in peers if items[p].get('slug')]
        out = dict(d)
        out['post_id'] = i
        out['content_html'] = body + '\n' + src_html
        out['related'] = related
        out['image_alt'] = d.get('image_alt') or ('Holyprofweb editorial graphic for %s' % d['facts'].get('name', d['title']))
        out['image_slug'] = d.get('image_slug') or d.get('slug') or ''
        out.pop('body', None)
        out['merge'] = d.get('merge', [])
        if errs:
            problems += 1
            print('PROBLEM %s %s: %s' % (i, d['title'][:60], '; '.join(errs)))
        json.dump(out, open(os.path.join(OUT, '%d.json' % i), 'w', encoding='utf-8'), indent=1, ensure_ascii=False)
        built += 1
    print('built %d, with problems %d' % (built, problems))


if __name__ == '__main__':
    main()
