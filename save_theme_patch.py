import sys, urllib.request, urllib.parse, http.cookiejar, re

sys.stdout.reconfigure(encoding='utf-8', errors='replace')

COOKIE_FILE = 'wp_cookies.txt'
UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120 Safari/537.36'

cj = http.cookiejar.MozillaCookieJar(COOKIE_FILE)
try:
    cj.load(ignore_discard=True, ignore_expires=True)
except Exception as e:
    print(f'cookie load warning: {e}')

opener = urllib.request.build_opener(
    urllib.request.HTTPCookieProcessor(cj),
    urllib.request.HTTPRedirectHandler(),
)
opener.addheaders = [
    ('User-Agent', UA),
    ('Referer', 'https://advanceapractice.com/wp-admin/theme-editor.php?file=functions.php&theme=neve'),
]

with open('functions_new.php', encoding='utf-8') as f:
    new_content = f.read()
print(f'new_content size: {len(new_content)}')

post_fields = {
    'nonce': '442ab7c986',
    '_wp_http_referer': '/wp-admin/theme-editor.php?file=functions.php&theme=neve',
    'newcontent': new_content,
    'action': 'update',
    'file': 'functions.php',
    'theme': 'neve',
    'scrollto': '0',
    'docs-list': '',
    'submit': 'Update File',
}
data = urllib.parse.urlencode(post_fields).encode('utf-8')
print(f'POST body: {len(data)}')

req = urllib.request.Request(
    'https://advanceapractice.com/wp-admin/theme-editor.php',
    data=data,
    method='POST',
    headers={'Content-Type': 'application/x-www-form-urlencoded'},
)

try:
    resp = opener.open(req, timeout=90)
    body = resp.read().decode('utf-8', errors='replace')
    with open('save_theme_response.html', 'w', encoding='utf-8') as f:
        f.write(body)
    print(f'status: {resp.status}')
    print(f'url: {resp.url}')
    print(f'size: {len(body)}')
    for pat in ['File edited successfully', 'successfully', 'updated', 'error', 'syntax',
                'Parse error', 'Fatal', 'reauth', 'locked', 'cannot edit',
                'not writeable']:
        if pat.lower() in body.lower():
            # show 80 chars around each hit
            for m in re.finditer(pat, body, re.I):
                start = max(0, m.start()-40); end = min(len(body), m.end()+60)
                ctx = body[start:end].replace('\n', ' ')
                print(f'  "{pat}" -> ...{ctx}...')
                break
    tm = re.search(r'<title>([^<]+)</title>', body)
    if tm: print(f'title: {tm.group(1)}')
except urllib.error.HTTPError as e:
    print(f'HTTPError: {e.code}')
    print(e.read()[:500].decode(errors='replace'))
except Exception as e:
    print(f'ERR: {type(e).__name__}: {e}')
