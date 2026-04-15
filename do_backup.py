"""
Comprehensive site backup to backup/ directory.
Pulls everything accessible via WP REST API.
"""
import sys, json, os, re, html, base64, urllib.request, urllib.parse, http.cookiejar, ssl, datetime

sys.stdout.reconfigure(encoding='utf-8', errors='replace')

BASE = 'https://advanceapractice.com'
AUTH = base64.b64encode(b'dev:QMUA jTji TZEd jJtj xEMJ eLd2').decode()
HDR = {'Authorization': f'Basic {AUTH}', 'Accept': 'application/json'}
ctx = ssl.create_default_context()

def j(url):
    req = urllib.request.Request(url, headers=HDR)
    return json.loads(urllib.request.urlopen(req, context=ctx, timeout=45).read())

def save_json(path, data):
    with open(path, 'w', encoding='utf-8') as f:
        json.dump(data, f, indent=2, ensure_ascii=False)

stamp = datetime.datetime.now().strftime('%Y-%m-%d_%H%M%S')
print(f'[backup {stamp}]')
print()

# ── 1. PAGES ──
print('[1] pages...')
targets = json.load(open('targets.json'))
pages_backup = {}
for t in targets:
    try:
        d = j(f'{BASE}/wp-json/wp/v2/pages/{t["id"]}?context=edit')
        slug = d.get('slug', t['slug'])
        pages_backup[slug] = {
            'id': d.get('id'),
            'title': d.get('title', {}).get('raw', ''),
            'slug': slug,
            'status': d.get('status', ''),
            'date': d.get('date', ''),
            'modified': d.get('modified', ''),
            'link': d.get('link', ''),
            'content_raw': d.get('content', {}).get('raw', ''),
            'excerpt_raw': d.get('excerpt', {}).get('raw', ''),
            'meta': d.get('meta', {}),
            'yoast': d.get('yoast_head_json', {}),
        }
        # Individual HTML file
        safe = slug.replace('/', '_').replace(' ', '_')
        with open(f'backup/pages/{safe}.html', 'w', encoding='utf-8') as f:
            f.write(f'<!DOCTYPE html>\n<html>\n<head>\n<meta charset="utf-8">\n<title>{html.escape(pages_backup[slug]["title"])}</title>\n</head>\n<body>\n')
            f.write(f'<!-- backup {stamp} | id={pages_backup[slug]["id"]} | modified={pages_backup[slug]["modified"]} -->\n')
            f.write(f'<!-- source: {pages_backup[slug]["link"]} -->\n\n')
            f.write(pages_backup[slug]['content_raw'])
            f.write('\n</body>\n</html>\n')
    except Exception as e:
        print(f'  [ERR] {t["slug"]}: {e}')
save_json('backup/pages/all_pages.json', pages_backup)
print(f'  ✓ {len(pages_backup)} pages saved')

# ── 2. POSTS ──
print('[2] posts...')
try:
    posts = j(f'{BASE}/wp-json/wp/v2/posts?status=any&per_page=50&context=edit')
    save_json('backup/pages/posts.json', posts)
    print(f'  ✓ {len(posts)} posts saved')
except Exception as e:
    print(f'  [ERR] {e}')

# ── 3. CUSTOMIZER CUSTOM CSS ──
print('[3] customizer css...')
try:
    d = j(f'{BASE}/wp-json/wp-abilities/v1/abilities/hostinger-ai-assistant/cpt-search/run?input%5Bpost_type%5D=custom_css&input%5Bstatus%5D=publish')
    css_backup = {}
    for p in d.get('data', []):
        css_backup[p.get('post_name', '?')] = {
            'ID': p.get('ID'),
            'post_name': p.get('post_name'),
            'post_status': p.get('post_status'),
            'post_content': p.get('post_content', ''),
            'post_modified': p.get('post_modified'),
        }
    save_json('backup/wp/customizer_custom_css.json', css_backup)
    # Also save each theme's CSS as a .css file
    for name, data in css_backup.items():
        with open(f'backup/wp/customizer_{name}.css', 'w', encoding='utf-8') as f:
            f.write(f'/* Customizer Additional CSS for theme: {name}\n * Post ID: {data["ID"]}\n * Modified: {data["post_modified"]}\n * Backup: {stamp}\n */\n\n')
            f.write(data.get('post_content', ''))
    print(f'  ✓ {len(css_backup)} theme CSS posts saved')
except Exception as e:
    print(f'  [ERR] {e}')

# ── 4. MEDIA LIBRARY METADATA ──
print('[4] media library metadata...')
all_media = []
page = 1
while page < 10:  # cap at 10 pages (1000 items)
    try:
        d = j(f'{BASE}/wp-json/wp/v2/media?per_page=100&page={page}&_fields=id,title,slug,source_url,media_details,alt_text,caption,date,mime_type')
        if not d:
            break
        all_media.extend(d)
        if len(d) < 100:
            break
        page += 1
    except Exception as e:
        break
save_json('backup/media/media_library.json', all_media)
print(f'  ✓ {len(all_media)} media items saved')

# ── 5. PLUGIN LIST ──
print('[5] plugins list...')
try:
    plugins = j(f'{BASE}/wp-json/wp/v2/plugins')
    save_json('backup/wp/plugins_list.json', plugins)
    print(f'  ✓ {len(plugins)} plugins saved')
except Exception as e:
    print(f'  [ERR] {e}')

# ── 6. THEMES LIST ──
print('[6] themes list...')
try:
    themes = j(f'{BASE}/wp-json/wp/v2/themes')
    save_json('backup/wp/themes_list.json', themes)
    print(f'  ✓ {len(themes)} themes saved')
except Exception as e:
    print(f'  [ERR] {e}')

# ── 7. SETTINGS ──
print('[7] site settings...')
try:
    settings = j(f'{BASE}/wp-json/wp/v2/settings')
    save_json('backup/wp/settings.json', settings)
    print(f'  ✓ saved')
except Exception as e:
    print(f'  [ERR] {e}')

# ── 8. USER (just the admin) ──
print('[8] user info...')
try:
    me = j(f'{BASE}/wp-json/wp/v2/users/me?context=edit')
    # Strip sensitive
    for k in ('capabilities', 'extra_capabilities'):
        me.pop(k, None)
    save_json('backup/wp/admin_user.json', me)
    print(f'  ✓ saved')
except Exception as e:
    print(f'  [ERR] {e}')

# ── 9. POST TYPES ──
print('[9] post types...')
try:
    types = j(f'{BASE}/wp-json/wp/v2/types')
    save_json('backup/wp/post_types.json', types)
    print(f'  ✓ {len(types)} types saved')
except Exception as e:
    print(f'  [ERR] {e}')

# ── 10. CURRENT PATCHED PLUGIN FILE (live from server) ──
print('[10] live aap-hotfix plugin file...')
try:
    # Use wp-login cookie flow to access plugin-editor.php
    cj = http.cookiejar.CookieJar()
    ctx2 = ssl.create_default_context()
    opener = urllib.request.build_opener(
        urllib.request.HTTPSHandler(context=ctx2),
        urllib.request.HTTPCookieProcessor(cj),
    )
    opener.addheaders = [('User-Agent', 'Mozilla/5.0 Backup')]
    opener.open(f'{BASE}/wp-login.php', timeout=60).read()
    cj.set_cookie(http.cookiejar.Cookie(
        version=0, name='wordpress_test_cookie', value='WP Cookie check',
        port=None, port_specified=False, domain='advanceapractice.com', domain_specified=True,
        domain_initial_dot=False, path='/', path_specified=True, secure=True, expires=None,
        discard=True, comment=None, comment_url=None, rest={'HttpOnly': None}, rfc2109=False
    ))
    login = urllib.parse.urlencode({
        'log': 'dev', 'pwd': 'XqwaiFe$h$CdvvlJ^zUz6eFB', 'wp-submit': 'Log In',
        'redirect_to': f'{BASE}/wp-admin/plugin-editor.php?plugin=aap-hotfix-v1254/aap-hotfix.php',
        'testcookie': '1',
    }).encode()
    req = urllib.request.Request(f'{BASE}/wp-login.php', data=login, method='POST')
    req.add_header('Content-Type', 'application/x-www-form-urlencoded')
    req.add_header('Referer', f'{BASE}/wp-login.php')
    resp = opener.open(req, timeout=60)
    body = resp.read().decode('utf-8', errors='replace')
    m = re.search(r'<textarea[^>]*id=[\'"]newcontent[\'"][^>]*>(.*?)</textarea>', body, flags=re.S)
    if m:
        content = html.unescape(m.group(1))
        with open('backup/plugin/aap-hotfix-v1254-LIVE.php', 'w', encoding='utf-8') as f:
            f.write(content)
        print(f'  ✓ live patched plugin saved: {len(content)} chars')
    else:
        print('  [ERR] no textarea found')
except Exception as e:
    print(f'  [ERR] {e}')

# ── 11. ORIGINAL UNPATCHED PLUGIN (from disk) ──
print('[11] original pre-patch plugin...')
try:
    import shutil
    shutil.copy('hotfix_backup.php', 'backup/plugin/aap-hotfix-v1254-ORIGINAL.php')
    print(f'  ✓ original saved')
except Exception as e:
    print(f'  [ERR] {e}')

# ── 12. OVERLAY + SCRIPTS (from disk) ──
print('[12] overlay + scripts...')
try:
    import shutil
    for src, dst in [
        ('aap-premium-overlay.css', 'backup/css/aap-premium-overlay.css'),
        ('aap-premium-overlay.min.css', 'backup/css/aap-premium-overlay.min.css'),
        ('do_everything.py', 'backup/do_everything.py'),
        ('emergency_revert.py', 'backup/emergency_revert.py'),
    ]:
        if os.path.exists(src):
            shutil.copy(src, dst)
    print(f'  ✓ saved')
except Exception as e:
    print(f'  [ERR] {e}')

# ── 13. Manifest ──
manifest = {
    'backup_stamp': stamp,
    'site': BASE,
    'pages_count': len(pages_backup),
    'media_count': len(all_media),
    'files': [],
}
for root, dirs, files in os.walk('backup'):
    for f in files:
        path = os.path.join(root, f).replace('\\', '/')
        size = os.path.getsize(os.path.join(root, f))
        manifest['files'].append({'path': path, 'bytes': size})

save_json('backup/BACKUP_MANIFEST.json', manifest)
total_bytes = sum(x['bytes'] for x in manifest['files'])
print()
print(f'=== BACKUP COMPLETE ===')
print(f'Files: {len(manifest["files"])}')
print(f'Total size: {total_bytes/1024/1024:.1f} MB')
print(f'Location: {os.path.abspath("backup")}')
