"""
One-shot script that:
1. Fresh login via wp-login.php (single Python session, full cookie capture)
2. GET plugin-editor.php to extract sudo-valid nonce
3. POST patched aap-hotfix.php with CSS overlay + output-buffer filter that:
   - Injects the v2 high-specificity overlay into <head>
   - Strips "Founder-led support" H2 section-heading
   - Strips the "Ryan's unique ability" quote-card
   - Replaces "founder-led" -> "hands-on"
4. Update all 20 pages' content to strip founder-led + weird quote + use new overlay
5. Update Customizer custom_css post 17408 with new overlay
6. Purge LiteSpeed cache
7. Verify
"""
import sys, re, html, json, base64, urllib.request, urllib.parse, urllib.error, http.cookiejar, ssl, time
sys.stdout.reconfigure(encoding='utf-8', errors='replace')

WP_USER = 'dev'
WP_PASS = 'XqwaiFe$h$CdvvlJ^zUz6eFB'
APP_PASS = 'QMUA jTji TZEd jJtj xEMJ eLd2'
BASE = 'https://advanceapractice.com'
UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120 Safari/537.36'

ctx = ssl.create_default_context()

# ── Load overlay CSS (already minified) ──
with open('aap-premium-overlay.min.css', encoding='utf-8') as f:
    OVERLAY_CSS = f.read()
print(f'[INFO] overlay CSS: {len(OVERLAY_CSS)} chars')

# ── Build fresh cookie jar + opener for wp-admin login flow ──
cj = http.cookiejar.CookieJar()
https_handler = urllib.request.HTTPSHandler(context=ctx)
opener = urllib.request.build_opener(
    https_handler,
    urllib.request.HTTPCookieProcessor(cj),
)
opener.addheaders = [('User-Agent', UA)]

def get(url, referer=None):
    req = urllib.request.Request(url, method='GET')
    if referer:
        req.add_header('Referer', referer)
    return opener.open(req, timeout=60)

def post_form(url, data, referer=None):
    body = urllib.parse.urlencode(data).encode('utf-8')
    req = urllib.request.Request(url, data=body, method='POST')
    req.add_header('Content-Type', 'application/x-www-form-urlencoded')
    if referer:
        req.add_header('Referer', referer)
    return opener.open(req, timeout=120)

# === STEP 1: Fresh login ===
print('\n[1] Fresh login to wp-login.php...')
get(f'{BASE}/wp-login.php').read()
# Must have wordpress_test_cookie set BEFORE login POST
cj.set_cookie(http.cookiejar.Cookie(
    version=0, name='wordpress_test_cookie', value='WP Cookie check',
    port=None, port_specified=False, domain='advanceapractice.com', domain_specified=True,
    domain_initial_dot=False, path='/', path_specified=True, secure=True, expires=None,
    discard=True, comment=None, comment_url=None, rest={'HttpOnly': None}, rfc2109=False
))
login_data = {
    'log': WP_USER,
    'pwd': WP_PASS,
    'wp-submit': 'Log In',
    'redirect_to': f'{BASE}/wp-admin/plugin-editor.php?plugin=aap-hotfix-v1254/aap-hotfix.php',
    'testcookie': '1',
}
resp = post_form(f'{BASE}/wp-login.php', login_data, referer=f'{BASE}/wp-login.php')
resp_body = resp.read().decode('utf-8', errors='replace')
print(f'  post-login URL: {resp.url}')
# check for wordpress_logged_in cookie
has_logged_in = any('wordpress_logged_in' in c.name for c in cj)
print(f'  wordpress_logged_in cookie: {has_logged_in}')
if not has_logged_in:
    print('  [FATAL] login did not set logged-in cookie')
    with open('login_err.html', 'w', encoding='utf-8') as f: f.write(resp_body)
    sys.exit(1)

# === STEP 2: Follow redirect to plugin-editor.php. Check if that's where we land. ===
# The login response might be the plugin-editor page directly, or might have been a 302 to login again
if 'plugin-editor' in resp.url:
    print('  [OK] landed directly on plugin-editor.php')
    pe_html = resp_body
else:
    # Visit plugin-editor.php separately
    print('  [INFO] fetching plugin-editor.php separately...')
    pe_resp = get(f'{BASE}/wp-admin/plugin-editor.php?plugin=aap-hotfix-v1254/aap-hotfix.php')
    pe_html = pe_resp.read().decode('utf-8', errors='replace')
    print(f'  pe URL: {pe_resp.url}')

if 'reauth' in (resp.url if 'plugin-editor' in resp.url else ''):
    print('  [FAIL] reauth wall hit')
    sys.exit(1)

# === STEP 3: Extract the nonce and current file content ===
print('\n[3] Extracting nonce and current plugin content...')
nonce_m = re.search(r'name=[\'"]nonce[\'"]\s+value=[\'"]([a-f0-9]+)', pe_html)
if not nonce_m:
    print('  [FAIL] no nonce found')
    with open('pe_nonone.html', 'w', encoding='utf-8') as f: f.write(pe_html)
    sys.exit(1)
nonce = nonce_m.group(1)
print(f'  nonce: {nonce}')

ta_m = re.search(r'<textarea[^>]*id=[\'"]newcontent[\'"][^>]*>(.*?)</textarea>', pe_html, flags=re.S)
if not ta_m:
    print('  [FAIL] no newcontent textarea')
    sys.exit(1)
original_content = html.unescape(ta_m.group(1))
print(f'  original file: {len(original_content)} chars')
with open('hotfix_backup_v2.php', 'w', encoding='utf-8') as f:
    f.write(original_content)

# === STEP 4: Patch the plugin content ===
print('\n[4] Building patched plugin content...')
if 'aap_premium_overlay_inject' in original_content:
    print('  [INFO] previous patch detected; stripping it before re-applying')
    original_content = re.sub(
        r'\n?/\* ---+ AAP PREMIUM OVERLAY START ---+ \*/.*?/\* ---+ AAP PREMIUM OVERLAY END ---+ \*/\n?',
        '\n',
        original_content,
        flags=re.S,
    )

BS = chr(92); SQ = chr(39); DQ = chr(34); NL = chr(10)
# PHP escape for single-quoted string: backslash -> \\, single-quote -> \'
# DO NOT escape double quotes (they are literal inside PHP single-quoted strings)
css_php = OVERLAY_CSS.replace(BS, BS + BS).replace(SQ, BS + SQ)

# Helper: PHP single-quoted string literal for a python string.
# Only backslash and single-quote need escaping.
def pq(s):
    return SQ + s.replace(BS, BS + BS).replace(SQ, BS + SQ) + SQ

CSS_LITERAL = SQ + css_php + SQ  # css already pre-escaped

STYLE_OPEN  = pq('<style id="aap-premium-overlay-global">')
STYLE_CLOSE = pq('</style>')
CTA_HTML    = pq('<nav class="aap-mobile-cta-bar" aria-label="Quick actions"><a class="is-primary" href="/contact/#aap-contact-form">Book a Consultation</a><a class="is-gold" href="/practice-workflow-review-checklist/">Free Checklist</a></nav>')
HEAD_CLOSE  = pq('</head>')
BODY_CLOSE  = pq('</body>')
QUOTE_CARD_REGEX = pq('#<article\\s+class="quote-card[^"]*"[^>]*>.*?</article>#is')
FOUNDER_H2_REGEX = pq('#<span\\s+class="kicker">Why AdvanceAPractice</span>\\s*<h2[^>]*>Founder-led support[^<]*</h2>#is')
# Strip the entire "Why AdvanceAPractice -> Founder-led support..." copy block from body text too
FOUNDER_BLOCK_REGEX = pq('#<p[^>]*class="text-limit"[^>]*>Ryan Berg built AdvanceAPractice[^<]*</p>#is')
# Strip any "Founder-Led, Serving Practices..." banner strip
FOUNDER_BANNER_REGEX = pq('#<[^>]+>\\s*Founder-Led,?\\s+Serving[^<]*</[^>]+>#is')
FOUNDER_LC_REGEX = pq('/[Ff]ounder[- ][Ll]ed[,\\s]*/')
FOUNDER_CAP_STR  = pq('Founder-Led Operations')
FOUNDER_CAP_REPL = pq('Hands-On Operators')
EMPTY_STR = pq('')

# Replace the old footer business description with a sharper one
OLD_FOOTER_DESC = pq('Practical execution, sharper visibility, and steadier follow-through for teams that are tired of work getting lost between systems.')
NEW_FOOTER_DESC = pq('Billing, credentialing, revenue cycle, and practice operations consulting for behavioral health and outpatient teams. We stabilize reimbursement, cut workflow friction, and build systems that actually get used \u2014 without the generic outsourcing theater.')

hook = NL.join([
    '',
    '/* ---- AAP PREMIUM OVERLAY START ---- */',
    "if ( ! function_exists( 'aap_premium_overlay_inject' ) ) {",
    "    function aap_premium_overlay_inject() {",
    f"        echo {STYLE_OPEN} . {CSS_LITERAL} . {STYLE_CLOSE};",
    '    }',
    "    add_action( 'wp_head', 'aap_premium_overlay_inject', 999 );",
    '}',
    "if ( ! function_exists( 'aap_premium_mobile_cta_inject' ) ) {",
    "    function aap_premium_mobile_cta_inject() {",
    f"        echo {CTA_HTML};",
    '    }',
    "    add_action( 'wp_footer', 'aap_premium_mobile_cta_inject', 999 );",
    '}',
    "if ( ! function_exists( 'aap_premium_output_filter' ) ) {",
    "    function aap_premium_output_filter( $html ) {",
    "        if ( stripos( $html, '<body' ) === false ) { return $html; }",
    "        /* Strip any quote-card <article> (variant class suffixes too) */",
    f"        $html = preg_replace( {QUOTE_CARD_REGEX}, {EMPTY_STR}, $html );",
    "        /* Strip the legacy 'Founder-led support' section-heading block */",
    f"        $html = preg_replace( {FOUNDER_H2_REGEX}, {EMPTY_STR}, $html );",
    "        /* Strip the 'Ryan Berg built AdvanceAPractice' legacy paragraph */",
    f"        $html = preg_replace( {FOUNDER_BLOCK_REGEX}, {EMPTY_STR}, $html );",
    "        /* Strip 'Founder-Led, Serving Practices' banner variants */",
    f"        $html = preg_replace( {FOUNDER_BANNER_REGEX}, {EMPTY_STR}, $html );",
    "        /* Replace 'Founder-Led Operations' phrasing */",
    f"        $html = str_ireplace( {FOUNDER_CAP_STR}, {FOUNDER_CAP_REPL}, $html );",
    "        /* Scrub bare 'founder-led' / 'Founder-led' / 'Founder-Led' anywhere */",
    f"        $html = preg_replace( {FOUNDER_LC_REGEX}, {EMPTY_STR}, $html );",
    "        /* Replace legacy footer business description with the sharper one */",
    f"        $html = str_replace( {OLD_FOOTER_DESC}, {NEW_FOOTER_DESC}, $html );",
    "        /* Inject overlay stylesheet before </head> if not already present */",
    f"        if ( stripos( $html, 'aap-premium-overlay-global' ) === false ) {{",
    f"            $style_block = {STYLE_OPEN} . {CSS_LITERAL} . {STYLE_CLOSE};",
    f"            $html = str_ireplace( {HEAD_CLOSE}, $style_block . {HEAD_CLOSE}, $html );",
    '        }',
    "        /* Inject sticky mobile CTA before </body> if not already present */",
    f"        if ( stripos( $html, 'aap-mobile-cta-bar\" aria-label' ) === false && stripos( $html, '</body>' ) !== false ) {{",
    f"            $html = str_ireplace( {BODY_CLOSE}, {CTA_HTML} . {BODY_CLOSE}, $html );",
    '        }',
    '        return $html;',
    '    }',
    "    function aap_premium_ob_start() {",
    "        if ( is_admin() || wp_doing_ajax() || ( defined('REST_REQUEST') && REST_REQUEST ) ) { return; }",
    "        if ( ! function_exists( 'aap_premium_output_filter' ) ) { return; }",
    "        ob_start( 'aap_premium_output_filter' );",
    '    }',
    "    /* Run before aap-hotfix canonical shell (template_redirect priority 1). */",
    "    add_action( 'template_redirect', 'aap_premium_ob_start', -9999 );",
    "    add_action( 'plugins_loaded', 'aap_premium_ob_start', -9999 );",
    '}',
    '/* ---- AAP PREMIUM OVERLAY END ---- */',
    '',
])

stripped = original_content.rstrip()
if stripped.endswith('?>'):
    idx = stripped.rfind('?>')
    new_content = original_content[:idx] + hook + '\n' + original_content[idx:]
else:
    new_content = stripped + '\n' + hook

# Brace sanity check
ob_ct = new_content.count('{')
cb_ct = new_content.count('}')
print(f'  braces: open={ob_ct} close={cb_ct} diff={ob_ct - cb_ct}')
if abs(ob_ct - cb_ct) > 2:
    print('  [FAIL] brace imbalance; aborting')
    sys.exit(1)

with open('hotfix_new_v2.php', 'w', encoding='utf-8') as f:
    f.write(new_content)
print(f'  patched content: {len(new_content)} chars ({len(new_content) - len(original_content):+d})')

# === STEP 5: POST patched content back ===
print('\n[5] POSTing patched plugin to plugin-editor.php...')
save_body = {
    'nonce': nonce,
    '_wp_http_referer': '/wp-admin/plugin-editor.php?plugin=aap-hotfix-v1254%2Faap-hotfix.php',
    'newcontent': new_content,
    'action': 'update',
    'file': 'aap-hotfix-v1254/aap-hotfix.php',
    'plugin': 'aap-hotfix-v1254/aap-hotfix.php',
    'scrollto': '0',
    'docs-list': '',
    'submit': 'Update File',
}

try:
    save_resp = post_form(
        f'{BASE}/wp-admin/plugin-editor.php',
        save_body,
        referer=f'{BASE}/wp-admin/plugin-editor.php?plugin=aap-hotfix-v1254%2Faap-hotfix.php',
    )
    save_html = save_resp.read().decode('utf-8', errors='replace')
    with open('save_response_v2.html', 'w', encoding='utf-8') as f:
        f.write(save_html)
    print(f'  status: {save_resp.status}')
    print(f'  url: {save_resp.url}')
    print(f'  size: {len(save_html)}')

    title_m = re.search(r'<title>([^<]+)</title>', save_html)
    if title_m:
        print(f'  title: {title_m.group(1)}')

    if 'reauth' in save_resp.url:
        print('  [WARN] reauth wall - the sudo-mode session was not granted')
        print('  attempting to read more of the response for clues...')
    elif 'File edited successfully' in save_html or 'edit-plugin-file-editor' in save_html or 'file has been updated' in save_html.lower():
        print('  [SUCCESS] plugin file edited')
    else:
        # Look for notices
        for m in re.finditer(r'<div[^>]*class=["\'][^"\']*(?:notice|error|updated)[^"\']*["\'][^>]*>(.*?)</div>', save_html, flags=re.S):
            txt = re.sub(r'<[^>]+>', ' ', m.group(1)).strip()[:300]
            if txt: print(f'  notice: {txt}')
except urllib.error.HTTPError as e:
    print(f'  HTTPError: {e.code}')
    print(e.read()[:500].decode(errors='replace'))
except Exception as e:
    print(f'  ERR: {type(e).__name__}: {e}')
