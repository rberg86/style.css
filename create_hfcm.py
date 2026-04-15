import sys, urllib.request, urllib.parse, http.cookiejar, re

sys.stdout.reconfigure(encoding='utf-8', errors='replace')

COOKIE_FILE = 'wp_cookies.txt'
UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120 Safari/537.36'

cj = http.cookiejar.MozillaCookieJar(COOKIE_FILE)
try:
    cj.load(ignore_discard=True, ignore_expires=True)
except Exception as e:
    print(f'cookie load: {e}')

opener = urllib.request.build_opener(
    urllib.request.HTTPCookieProcessor(cj),
    urllib.request.HTTPRedirectHandler(),
)
opener.addheaders = [
    ('User-Agent', UA),
    ('Referer', 'https://advanceapractice.com/wp-admin/admin.php?page=hfcm-create'),
    ('Origin', 'https://advanceapractice.com'),
]

with open('aap-premium-overlay.min.css', encoding='utf-8') as f:
    css = f.read()

# Snippet body: style block + sticky mobile CTA nav
snippet = (
    f'<style id="aap-premium-overlay-global">{css}</style>\n'
    '<nav class="aap-mobile-cta-bar" aria-label="Quick actions">'
    '<a class="is-primary" href="/contact/#aap-contact-form">Book a Consultation</a>'
    '<a class="is-gold" href="/practice-workflow-review-checklist/">Free Checklist</a>'
    '</nav>'
)
print(f'snippet size: {len(snippet)}')

post_fields = [
    ('_wpnonce', 'da476c2769'),
    ('_wp_http_referer', '/wp-admin/admin.php?page=hfcm-create'),
    ('data[name]', 'AAP Premium Overlay'),
    ('data[snippet_type]', 'html'),
    ('data[display_on]', 'All'),
    ('data[spt_display_on]', 's_posts'),
    ('data[lp_count]', '0'),
    ('data[location]', 'header'),
    ('data[device_type]', 'both'),
    ('data[status]', 'active'),
    ('data[snippet]', snippet),
    ('insert', 'Save'),
]

data = urllib.parse.urlencode(post_fields).encode('utf-8')
print(f'POST body: {len(data)}')

req = urllib.request.Request(
    'https://advanceapractice.com/wp-admin/admin.php?page=hfcm-request-handler',
    data=data,
    method='POST',
    headers={'Content-Type': 'application/x-www-form-urlencoded'},
)

try:
    resp = opener.open(req, timeout=90)
    body = resp.read().decode('utf-8', errors='replace')
    with open('hfcm_response.html', 'w', encoding='utf-8') as f:
        f.write(body)
    print(f'status: {resp.status}')
    print(f'url: {resp.url}')
    print(f'size: {len(body)}')
    # Check for success / error
    for pat in ['successfully', 'Added', 'updated', 'created', 'error', 'Error',
                'failed', 'reauth', 'wp-login']:
        if pat.lower() in body.lower():
            for m in re.finditer(pat, body, re.I):
                s = max(0, m.start() - 40); e = min(len(body), m.end() + 80)
                ctx = body[s:e].replace('\n', ' ')
                print(f'  "{pat}" -> ...{ctx}...')
                break
    tm = re.search(r'<title>([^<]+)</title>', body)
    if tm: print(f'title: {tm.group(1)}')
    # Notice divs
    for m in re.finditer(r'<div[^>]*class="[^"]*(?:notice|error|updated)[^"]*"[^>]*>(.*?)</div>', body, flags=re.S):
        txt = re.sub(r'<[^>]+>', ' ', m.group(1)).strip()
        if txt and len(txt) < 500:
            print(f'  notice: {txt[:400]}')
except urllib.error.HTTPError as e:
    print(f'HTTPError: {e.code}')
    print(e.read()[:500].decode(errors='replace'))
except Exception as e:
    print(f'ERR: {type(e).__name__}: {e}')
