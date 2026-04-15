import sys, re, html, datetime
sys.stdout.reconfigure(encoding='utf-8', errors='replace')

# Extract current plugin file content from the textarea in ped_main.html
with open('ped_main.html', encoding='utf-8', errors='replace') as f:
    h = f.read()
m = re.search(r'<textarea[^>]*id=[\'"]newcontent[\'"][^>]*>(.*?)</textarea>', h, flags=re.S)
if not m:
    print('FATAL: textarea not found')
    sys.exit(1)
content = html.unescape(m.group(1))
print(f'Extracted file content: {len(content)} chars')
print(f'First 200: {content[:200]!r}')
print(f'Last 200: {content[-200:]!r}')

if 'aap_premium_overlay_inject' in content:
    print('ALREADY PATCHED. Skipping.')
    sys.exit(0)

with open('hotfix_backup.php', 'w', encoding='utf-8') as f:
    f.write(content)
print('backup saved')

with open('aap-premium-overlay.min.css', encoding='utf-8') as f:
    css = f.read()

# Escape for PHP single-quoted string: backslash then single-quote
BS = chr(92)      # backslash
SQ = chr(39)      # single quote
css_php = css.replace(BS, BS + BS).replace(SQ, BS + SQ)

today = datetime.datetime.utcnow().strftime('%Y-%m-%d')

hook_lines = [
    '',
    '/* -------------------------------------------------------------------',
    f' * AAP Premium Overlay injection (appended {today})',
    ' * Non-destructive: wp_head hook prints the overlay <style> block,',
    ' * wp_footer hook prints the sticky mobile CTA nav.',
    ' * ------------------------------------------------------------------- */',
    "if ( ! function_exists( 'aap_premium_overlay_inject' ) ) {",
    '    function aap_premium_overlay_inject() {',
    f"        echo '<style id=" + chr(92) + chr(34) + "aap-premium-overlay-global" + chr(92) + chr(34) + ">' . '" + css_php + "' . '</style>';",
    '    }',
    "    add_action( 'wp_head', 'aap_premium_overlay_inject', 999 );",
    '}',
    "if ( ! function_exists( 'aap_premium_mobile_cta_inject' ) ) {",
    '    function aap_premium_mobile_cta_inject() {',
    f"        echo '<nav class=" + chr(92) + chr(34) + "aap-mobile-cta-bar" + chr(92) + chr(34) + " aria-label=" + chr(92) + chr(34) + "Quick actions" + chr(92) + chr(34) + "><a class=" + chr(92) + chr(34) + "is-primary" + chr(92) + chr(34) + " href=" + chr(92) + chr(34) + "/contact/#aap-contact-form" + chr(92) + chr(34) + ">Book a Consultation</a><a class=" + chr(92) + chr(34) + "is-gold" + chr(92) + chr(34) + " href=" + chr(92) + chr(34) + "/practice-workflow-review-checklist/" + chr(92) + chr(34) + ">Free Checklist</a></nav>';",
    '    }',
    "    add_action( 'wp_footer', 'aap_premium_mobile_cta_inject', 999 );",
    '}',
    '',
]
hook = '\n'.join(hook_lines)

# Append before any closing ?> tag, otherwise at the end
stripped = content.rstrip()
if stripped.endswith('?>'):
    idx = content.rstrip().rfind('?>')
    new_content = content[:idx] + hook + '\n' + content[idx:]
else:
    new_content = stripped + '\n' + hook + '\n'

print(f'New content: {len(new_content)} chars (was {len(content)})')

# Brace sanity check
ob = new_content.count('{')
cb = new_content.count('}')
print(f'braces: open={ob} close={cb} diff={ob - cb}')

# Save for POST
with open('hotfix_new.php', 'w', encoding='utf-8') as f:
    f.write(new_content)
print('hotfix_new.php saved')
