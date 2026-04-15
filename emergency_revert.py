"""
EMERGENCY REVERT — push the unpatched hotfix_backup.php back
to the live plugin file via plugin-editor.php to restore the site.
"""
import sys, urllib.request, urllib.parse, http.cookiejar, re, html, ssl

sys.stdout.reconfigure(encoding='utf-8', errors='replace')

WP_USER = 'dev'
WP_PASS = 'XqwaiFe$h$CdvvlJ^zUz6eFB'
BASE = 'https://advanceapractice.com'
UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120 Safari/537.36'

ctx = ssl.create_default_context()
cj = http.cookiejar.CookieJar()
opener = urllib.request.build_opener(
    urllib.request.HTTPSHandler(context=ctx),
    urllib.request.HTTPCookieProcessor(cj),
)
opener.addheaders = [('User-Agent', UA)]

# Fresh login with redirect to plugin editor
print('[1] login...')
opener.open(BASE + '/wp-login.php', timeout=60).read()
cj.set_cookie(http.cookiejar.Cookie(
    version=0, name='wordpress_test_cookie', value='WP Cookie check',
    port=None, port_specified=False, domain='advanceapractice.com', domain_specified=True,
    domain_initial_dot=False, path='/', path_specified=True, secure=True, expires=None,
    discard=True, comment=None, comment_url=None, rest={'HttpOnly': None}, rfc2109=False
))
login_data = urllib.parse.urlencode({
    'log': WP_USER,
    'pwd': WP_PASS,
    'wp-submit': 'Log In',
    'redirect_to': BASE + '/wp-admin/plugin-editor.php?plugin=aap-hotfix-v1254/aap-hotfix.php',
    'testcookie': '1',
}).encode()
req = urllib.request.Request(BASE + '/wp-login.php', data=login_data, method='POST')
req.add_header('Content-Type', 'application/x-www-form-urlencoded')
req.add_header('Referer', BASE + '/wp-login.php')
resp = opener.open(req, timeout=60)
pe_html = resp.read().decode('utf-8', errors='replace')
print('  url:', resp.url)

nonce_m = re.search(r'name=[\'"]nonce[\'"]\s+value=[\'"]([a-f0-9]+)', pe_html)
if not nonce_m:
    print('[FAIL] no nonce')
    sys.exit(1)
nonce = nonce_m.group(1)
print(f'[2] nonce: {nonce}')

# Load the original unpatched backup
with open('hotfix_backup.php', encoding='utf-8') as f:
    original = f.read()
print(f'[3] original: {len(original)} chars')

# POST the original back
post_fields = {
    'nonce': nonce,
    '_wp_http_referer': '/wp-admin/plugin-editor.php?plugin=aap-hotfix-v1254%2Faap-hotfix.php',
    'newcontent': original,
    'action': 'update',
    'file': 'aap-hotfix-v1254/aap-hotfix.php',
    'plugin': 'aap-hotfix-v1254/aap-hotfix.php',
    'scrollto': '0',
    'docs-list': '',
    'submit': 'Update File',
}
data = urllib.parse.urlencode(post_fields).encode('utf-8')

req2 = urllib.request.Request(
    BASE + '/wp-admin/plugin-editor.php',
    data=data,
    method='POST',
)
req2.add_header('Content-Type', 'application/x-www-form-urlencoded')
req2.add_header('Referer', BASE + '/wp-admin/plugin-editor.php?plugin=aap-hotfix-v1254%2Faap-hotfix.php')

print('[4] POST revert...')
try:
    resp2 = opener.open(req2, timeout=120)
    body = resp2.read().decode('utf-8', errors='replace')
    print(f'  status: {resp2.status}')
    print(f'  url: {resp2.url}')
    if 'a=1' in resp2.url or 'File edited successfully' in body:
        print('[OK] REVERTED')
    else:
        m = re.search(r'<title>([^<]+)</title>', body)
        print(f'  title: {m.group(1) if m else "?"}')
except Exception as e:
    print(f'  ERR: {e}')
