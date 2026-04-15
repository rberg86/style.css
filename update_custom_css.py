import sys, json, base64, urllib.request, ssl
sys.stdout.reconfigure(encoding='utf-8', errors='replace')

AUTH = base64.b64encode(b'dev:QMUA jTji TZEd jJtj xEMJ eLd2').decode()
HEADERS = {
    'Authorization': f'Basic {AUTH}',
    'Content-Type': 'application/json',
    'Accept': 'application/json',
}
ctx = ssl.create_default_context()

# Read existing CSS
with open('existing_custom_css.css', encoding='utf-8') as f:
    existing = f.read()

# Remove any prior AAP Premium Overlay marker block (idempotent)
import re
existing = re.sub(
    r'/\* ========= AAP PREMIUM OVERLAY START =========.*?/\* ========= AAP PREMIUM OVERLAY END =========\*/',
    '',
    existing,
    flags=re.S,
)

# Read overlay CSS
with open('aap-premium-overlay.min.css', encoding='utf-8') as f:
    overlay = f.read()

# Wrap in marker block for idempotent future updates
new_css = (
    existing.rstrip()
    + '\n\n/* ========= AAP PREMIUM OVERLAY START =========\n'
    + ' * Injected via cpt-update ability, 2026-04-15\n'
    + ' * Targets the aap-* class namespace used by aap-hotfix plugin.\n'
    + ' * =========================================== */\n'
    + overlay
    + '\n/* ========= AAP PREMIUM OVERLAY END =========*/\n'
)

print(f'existing: {len(existing)} chars')
print(f'overlay: {len(overlay)} chars')
print(f'new combined: {len(new_css)} chars')

# Call cpt-update ability
payload = {
    'input': {
        'post_type': 'custom_css',
        'id': 17408,
        'content': new_css,
    }
}

url = 'https://advanceapractice.com/wp-json/wp-abilities/v1/abilities/hostinger-ai-assistant/cpt-update/run'
req = urllib.request.Request(
    url,
    data=json.dumps(payload).encode('utf-8'),
    headers=HEADERS,
    method='POST',
)

try:
    r = urllib.request.urlopen(req, context=ctx, timeout=60)
    body = r.read().decode('utf-8', errors='replace')
    print(f'status: {r.status}')
    d = json.loads(body)
    # ability response wraps under 'result'
    result = d.get('result', d)
    print('result keys:', list(result.keys()) if isinstance(result, dict) else type(result).__name__)
    if isinstance(result, dict):
        for k in ['ID', 'post_status', 'post_name', 'success', 'message']:
            if k in result:
                print(f'  {k}: {str(result[k])[:120]}')
except urllib.error.HTTPError as e:
    print(f'HTTPError: {e.code}')
    print(e.read()[:600].decode(errors='replace'))
except Exception as e:
    print(f'ERR: {type(e).__name__}: {e}')
