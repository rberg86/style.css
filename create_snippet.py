import sys, json, base64, urllib.request, urllib.error, ssl
sys.stdout.reconfigure(encoding='utf-8', errors='replace')

AUTH = base64.b64encode(b'dev:QMUA jTji TZEd jJtj xEMJ eLd2').decode()
HEADERS = {
    'Authorization': f'Basic {AUTH}',
    'Content-Type': 'application/json',
    'Accept': 'application/json',
}
ctx = ssl.create_default_context()

# Load overlay CSS
with open('aap-premium-overlay.min.css', encoding='utf-8') as f:
    css = f.read()

# Escape for PHP: double backslashes, escape single quotes in nowdoc-like literal
BS = chr(92)   # backslash
SQ = chr(39)   # single quote
# Use PHP heredoc syntax to avoid escaping issues
php_code = (
    "// AAP Premium Overlay injection via Code Snippets\n"
    "// Added 2026-04-15 to inject CSS + sticky mobile CTA on every page including the\n"
    "// homepage which is rendered by aap-hotfix plugin's hardcoded template.\n"
    "\n"
    "add_action('wp_head', function() {\n"
    "    static $printed = false;\n"
    "    if ($printed) return;\n"
    "    $printed = true;\n"
    "    $css = <<<'AAP_CSS'\n"
    + css + "\n"
    "AAP_CSS;\n"
    "    echo '<style id=" + chr(34) + "aap-premium-overlay-global" + chr(34) + ">' . $css . '</style>';\n"
    "}, 999);\n"
    "\n"
    "add_action('wp_footer', function() {\n"
    "    static $printed = false;\n"
    "    if ($printed) return;\n"
    "    $printed = true;\n"
    "    echo '<nav class=" + chr(34) + "aap-mobile-cta-bar" + chr(34) + " aria-label=" + chr(34) + "Quick actions" + chr(34) + "><a class=" + chr(34) + "is-primary" + chr(34) + " href=" + chr(34) + "/contact/#aap-contact-form" + chr(34) + ">Book a Consultation</a><a class=" + chr(34) + "is-gold" + chr(34) + " href=" + chr(34) + "/practice-workflow-review-checklist/" + chr(34) + ">Free Checklist</a></nav>';\n"
    "}, 999);\n"
)

print(f'PHP code size: {len(php_code)}')
print(f'first 300 chars: {php_code[:300]}')
print()

payload = {
    'name': 'AAP Premium Overlay (CSS + Mobile CTA)',
    'desc': 'Injects premium CSS overlay and sticky mobile CTA bar on every page via wp_head and wp_footer. Installed 2026-04-15.',
    'code': php_code,
    'scope': 'front-end',
    'active': True,
    'priority': 10,
}

req = urllib.request.Request(
    'https://advanceapractice.com/wp-json/code-snippets/v1/snippets',
    data=json.dumps(payload).encode('utf-8'),
    headers=HEADERS,
    method='POST',
)

try:
    resp = urllib.request.urlopen(req, context=ctx, timeout=60)
    body = resp.read().decode('utf-8', errors='replace')
    print(f'status: {resp.status}')
    d = json.loads(body)
    print(f'snippet id: {d.get("id")}')
    print(f'active: {d.get("active")}')
    print(f'scope: {d.get("scope")}')
    print(f'code_error: {d.get("code_error", "(none)")}')
    print(f'name: {d.get("name")}')
except urllib.error.HTTPError as e:
    print(f'HTTPError: {e.code}')
    print(e.read()[:600].decode(errors='replace'))
except Exception as e:
    print(f'ERR: {type(e).__name__}: {e}')
