import sys, urllib.request, urllib.parse, http.cookiejar, ssl, os, re

sys.stdout.reconfigure(encoding='utf-8', errors='replace')

COOKIE_FILE = 'wp_cookies.txt'
UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120 Safari/537.36'

# Load cookies from curl's Netscape format into Python
cj = http.cookiejar.MozillaCookieJar(COOKIE_FILE)
try:
    cj.load(ignore_discard=True, ignore_expires=True)
except Exception as e:
    print(f'cookie load error (continuing): {e}')

opener = urllib.request.build_opener(
    urllib.request.HTTPCookieProcessor(cj),
    urllib.request.HTTPRedirectHandler(),
)
opener.addheaders = [
    ('User-Agent', UA),
    ('Referer', 'https://advanceapractice.com/wp-admin/plugin-editor.php?plugin=aap-hotfix-v1254%2Faap-hotfix.php'),
]

# Load the patched file
with open('hotfix_new.php', encoding='utf-8') as f:
    new_content = f.read()
print(f'new_content size: {len(new_content)}')

# Build form-encoded POST body
post_fields = {
    'nonce': '5386cad240',
    '_wp_http_referer': '/wp-admin/plugin-editor.php?plugin=aap-hotfix-v1254%2Faap-hotfix.php',
    'newcontent': new_content,
    'action': 'update',
    'file': 'aap-hotfix-v1254/aap-hotfix.php',
    'plugin': 'aap-hotfix-v1254/aap-hotfix.php',
    'scrollto': '0',
    'docs-list': '',
    'submit': 'Update File',
}

data = urllib.parse.urlencode(post_fields).encode('utf-8')
print(f'POST body size: {len(data)}')

# POST to plugin-editor.php
req = urllib.request.Request(
    'https://advanceapractice.com/wp-admin/plugin-editor.php',
    data=data,
    method='POST',
    headers={'Content-Type': 'application/x-www-form-urlencoded'}
)
try:
    resp = opener.open(req, timeout=60)
    body = resp.read().decode('utf-8', errors='replace')
    with open('save_response.html', 'w', encoding='utf-8') as f:
        f.write(body)
    print(f'response status: {resp.status}')
    print(f'response url: {resp.url}')
    print(f'response size: {len(body)}')
    # Look for success or error
    for pat in ['File edited successfully', 'plugin successfully', 'file has been updated',
                'syntax error', 'parse error', 'Fatal', 'error_description',
                'Unable to communicate', 'File does not exist', 'Your scrapes',
                'Something went wrong']:
        if pat.lower() in body.lower():
            print(f'  >> found: "{pat}"')
    # Extract title
    m = re.search(r'<title>([^<]+)</title>', body)
    if m:
        print(f'  title: {m.group(1)}')
    # Look for notice divs
    for m in re.finditer(r'<div[^>]*class="[^"]*(?:notice|error|updated)[^"]*"[^>]*>(.*?)</div>', body, flags=re.S):
        txt = re.sub(r'<[^>]+>', ' ', m.group(1)).strip()
        if txt and len(txt) < 400:
            print(f'  notice: {txt[:300]}')
except urllib.error.HTTPError as e:
    print(f'HTTPError: {e.code}')
    print(e.read()[:500].decode('utf-8', errors='replace'))
except Exception as e:
    print(f'ERR: {type(e).__name__}: {e}')
