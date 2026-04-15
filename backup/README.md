# AdvanceAPractice.com — Premium Overlay Project

Version-controlled CSS overlay + WordPress output filter that transforms advanceapractice.com into a premium blue/white B2B consulting design system. All changes live inside the `aap-hotfix-v1254` plugin as an appended PHP block and are fully reversible.

**Last deploy:** v6.5 — `2cdb0e4` on branch `claude/reverent-montalcini`
**Live site:** https://advanceapractice.com
**Local path:** `C:\Users\Iva Courtney\Documents\New project\New project\reverent-montalcini\`

---

## Quick reference

| File | Purpose |
|---|---|
| `aap-premium-overlay.css` | Canonical source of the overlay styles (~45 KB) |
| `aap-premium-overlay.min.css` | Minified version, injected into the plugin on each deploy |
| `do_everything.py` | **Main deploy script.** Logs into wp-admin, patches the aap-hotfix plugin with overlay + output filter. Idempotent — safe to re-run. |
| `emergency_revert.py` | Restores the unpatched `hotfix_backup.php` to the live plugin file if something breaks. Single command to get the site back up. |
| `do_backup.py` | Pulls a fresh backup of pages, posts, customizer CSS, plugin list, settings, media, and the live patched plugin file to `backup/`. |
| `hotfix_backup.php` | **Pristine pre-overhaul copy** of the aap-hotfix plugin file. DO NOT DELETE — this is the rollback target. |
| `backup/` | Latest application-state backup (pages, CSS, plugin, media, settings) |

---

## What the overlay does (what's live right now)

### Design language
- **Inter Tight** font family across the entire site (replaces Segoe UI Variable + Iowan Old Style)
- **Blue-white premium palette** — primary `#1a56db`, hover `#1648c0`, accent `#c9a961`, ink scale `#0f172a → #f8fafc`
- **Red middle A** in every visible "Advance**A**Practice" mention via state-machine HTML walker (safe — skips `<title>`, `<script>`, `<style>`, `<textarea>`)

### Layout
- Hero max-width `1440px` (bumped from plugin's 1280px)
- Hero shell stacks to 1-column below 1200px so the form section gets full width
- All grids collapse cleanly on mobile with comfortable 24px insets
- Container max-widths increased across the board

### Typography
- H1: `clamp(2.6rem, 1.8rem + 5vw, 6.25rem)` with -0.04em letter-spacing
- H2: `clamp(2rem, 1.2rem + 3vw, 3.75rem)` with -0.032em letter-spacing
- Headings use `max-width: none` + `text-wrap: balance`
- Plugin's `max-width: 10.6ch !important` rule beaten with 5-class specificity `body.aap-hotfix[class*="aap-page-"]`

### Header
- Logo restored with `mix-blend-mode: multiply` so the white PNG background drops out on the white header
- Wordmark "Advance**A**Practice" with red middle A, 1.35rem / 800 / Inter Tight
- Header tagline in the middle column: *"Billing, credentialing, and practice operations — built to actually hold together."*
- Plugin's `@media (max-width: 1460px) { .aap-global-nav { display: none } }` overridden — full nav now shows from 1100px+
- Book a Consultation CTA **removed** from header entirely (per user preference)
- MENU button only on tablet/mobile, styled as blue-filled button

### Mobile navigation drawer
- Fixed positioning, top 72px, bottom 0, full-height with internal scroll
- Plugin's original 507px max-height cut off 4+ items — fixed
- Each item: 0.9rem padding, hover slides 1rem right to blue

### Service cards ("Choose the area that best matches")
- Plugin uses class `service-card` (no `aap-` prefix) — handled specifically in CSS
- Whole card clickable via absolute `::before` overlay on the first heading link
- `cursor: pointer` on the entire card
- `"Learn more →"` label at the bottom of each card in blue (via `::after`)
- Heading shifts to blue on hover, card lifts 4px, gradient underline slides in from left

### Buttons
- Primary: solid `#1a56db` with layered shadow, hover darken, arrow slide
- Secondary: transparent with 1.5px blue border, inverts on hover
- All CTAs now 48px min height, 8px radius, 700 weight Inter Tight
- Hero button `.button` class explicitly targeted (was missing in v6.0 → v6.3)
- **CTA diversification:** 6 occurrences of "Book a Consultation" mapped to `[Book a Consultation, Start a conversation, Book a Consultation, Start a conversation, Send us a note, Reach out]` in DOM source order so repetition is gone. No "Talk with Ryan" or Ryan's name anywhere in CTAs.

### Moda / Nate GOAT recognition section
- Injected via PHP output filter right after the hero panel
- Uses `<section id="aap-home-lead-panel"` as the anchor (specific opening tag, avoids false match on `<style id="aap-home-lead-panel-css">`)
- Copy rewritten in first-person Ryan voice: *"Mid-thread on a Moda reimbursement, a PMHNP wrote his personal GOATs list and put me on it."*
- Real image of Nate's email with the GOATs list (Tom Brady, Michael Jordan, Muhammed Ali, Ryan Berg, Houdini)

### Founder spotlight block
- Injected into `pages/18272` (home) and `pages/17392` (about) via REST API `cpt-update` ability
- Uses Ryan's actual photo from media library (id 18594)
- First-person voice: *"I have worked the billing queue, filled out the credentialing packet, and helped stand up the EHR — sometimes in the same week."*

### Footer
- Deep navy `#0b1324` background
- Brand wordmark with red A, white text
- New business description (replaced legacy "Practical execution..." line):
  *"Billing, credentialing, revenue cycle, and practice operations support for behavioral health and outpatient teams. Built around real implementation — not dashboards, not promises, not generic outsourcing language."*
- Legacy plain `<p>support for behavioral health…</p>` stripped
- Plugin's decorative `h3::before` radial-gradient dots killed (was rendering as weird boxy shapes)
- "Facebook" / "Instagram" social text labels moved to `.sr-only` (accessible but visually hidden)
- Footer buttons forced to blue (higher specificity than the fallback footer-link rule)
- Link hover: slides 4px right with blue color shift

### SEO + link hygiene
- Fallback `<meta property="og:image">` + `<meta name="twitter:image">` injected if Yoast doesn't provide one (17 of 20 pages were missing)
- External links (non-advanceapractice.com) get `target="_blank"` + `rel="noopener noreferrer"` automatically

### Safety guard
- The entire output filter is wrapped in a rollback snapshot. If any regex or string operation produces `null` or output under 1000 bytes, the filter **falls back to the original HTML** instead of blanking the page.
- Previous v6.3 incident: external-link regex was missing `#` delimiters, `preg_replace_callback` returned `null`, site went blank. The guard prevents any future occurrence.

### Contrast fixes
- `.aap-home-trust-lead` (dark navy MediBillMD recognition card) — forced light text on dark background; the plugin's default `ink-700` on a dark gradient was unreadable
- Footer button color specificity fixed (link rule was beating button rule)
- Service card headings no longer blue underlined links — now dark plain with blue hover shift

---

## How to deploy changes

```bash
# 1. Edit aap-premium-overlay.css
# 2. Run the deploy script:
python3 do_everything.py

# 3. Deactivate + reactivate the aap-hotfix plugin to bust PHP OPcache:
curl -X POST -H "Authorization: Basic <base64 auth>" \
     "https://advanceapractice.com/wp-json/wp/v2/plugins/aap-hotfix-v1254/aap-hotfix" \
     -d '{"status":"inactive"}'
curl -X POST -H "Authorization: Basic <base64 auth>" \
     "https://advanceapractice.com/wp-json/wp/v2/plugins/aap-hotfix-v1254/aap-hotfix" \
     -d '{"status":"active"}'

# 4. Purge LiteSpeed via wp-admin cookie:
NONCE=$(curl ... | extract LSCWP_NONCE)
curl -b cj_curl.txt "https://advanceapractice.com/wp-admin/admin.php?page=litespeed-toolbox&LSCWP_CTRL=purge&LSCWP_NONCE=$NONCE&litespeed_type=purge_all_lscache"

# 5. Verify site is up (>1000 bytes) before walking away
```

---

## EMERGENCY: if the site goes blank

```bash
python3 emergency_revert.py
```

This single command logs into wp-admin, extracts a fresh nonce, and POSTs `hotfix_backup.php` back to the live plugin file. The site returns to its pre-overhaul state within ~5 seconds. Then investigate the PHP error, fix `do_everything.py`, and re-run.

**Common causes of blank pages (all prevented now by the safety guard):**
- Missing regex delimiters (use `#...#i`)
- Invalid callback return values
- Unbalanced PHP braces in the appended hook block
- Use of a function that doesn't exist in the server's PHP version

---

## How the architecture works

The live site is rendered by the **`aap-hotfix-v1254` WordPress plugin** — a custom hand-written plugin that outputs the entire HTML for each page. It doesn't use the theme's rendering; it hooks `template_redirect` at priority 1 and `echo`s its own template.

This means:
- **Editing the theme's `style.css` does nothing** (it was the wrong target — `rberg86/style.css` repo deploys to a theme slot the active site doesn't use)
- **Customizer → Additional CSS doesn't reach the homepage** because the plugin's custom `template_redirect` bypasses `wp_custom_css_cb`
- **Elementor snippets / WPCode / HFCM don't apply** because their hooks never run on the hijacked pages

**Our solution:** the deploy script appends ~100 KB of PHP + minified CSS to the bottom of `aap-hotfix-v1254/aap-hotfix.php`. That block adds two new `template_redirect` hooks at priority `-9999` (runs before the plugin's own priority 1 hook) that start an output buffer. When the plugin's hijack `echo`s its HTML and `exit`s, PHP shutdown flushes the buffer through our callback, which:
1. Strips legacy `<article class="quote-card">` testimonials
2. Strips the legacy "Founder-led support" H2 section
3. Strips the legacy "support for behavioral health" plain `<p>`
4. Replaces the footer description string
5. Runs a state-machine walker over text nodes (never attributes) wrapping "AdvanceAPractice" with colored spans
6. Diversifies the Book a Consultation CTAs in DOM order
7. Injects the header tagline element before `<div class="aap-global-actions"`
8. Injects the Moda recognition section after `</section>` of the hero
9. Adds fallback `og:image` meta tags if Yoast didn't provide any
10. Adds `target="_blank"` + `rel="noopener noreferrer"` to external links
11. Injects `<style id="aap-premium-overlay-global">...</style>` before `</head>`
12. Safety-checks the output: if null or <1000 bytes, restores the original HTML

### Plugin version handoff

If you bump the plugin version number (e.g. `aap-hotfix-v1254 → aap-hotfix-v1260`), the overlay won't automatically carry over — you'll need to re-run `python3 do_everything.py` after updating the plugin path constant inside that script. Search for `aap-hotfix-v1254` in `do_everything.py` and replace with the new folder name.

---

## Backup folder structure (`backup/`)

Latest backup stamp: captured fresh by `do_backup.py`.

```
backup/
├── BACKUP_MANIFEST.json          # File listing with sizes
├── do_everything.py              # Deploy script (snapshot)
├── emergency_revert.py           # Emergency rollback (snapshot)
├── pages/
│   ├── all_pages.json            # All 20 pages (id, title, raw content, meta, Yoast data)
│   ├── posts.json                # Blog posts (3)
│   └── <slug>.html               # Each page saved as standalone HTML file
├── plugin/
│   ├── aap-hotfix-v1254-LIVE.php       # Live patched plugin (current state)
│   └── aap-hotfix-v1254-ORIGINAL.php   # Pristine pre-overhaul version (rollback target)
├── css/
│   ├── aap-premium-overlay.css         # Source overlay
│   └── aap-premium-overlay.min.css     # Minified version that gets injected
├── wp/
│   ├── customizer_custom_css.json      # Customizer Additional CSS post data
│   ├── customizer_neve.css             # Neve theme Additional CSS as .css file
│   ├── customizer_astra.css            # Astra theme (legacy)
│   ├── customizer_hello-elementor.css  # Hello Elementor theme (legacy)
│   ├── plugins_list.json               # All 28 installed plugins with status
│   ├── themes_list.json                # 3 installed themes
│   ├── settings.json                   # WP core settings (title, url, email, etc.)
│   ├── post_types.json                 # 14 registered post types
│   └── admin_user.json                 # Admin user (dev) metadata
└── media/
    ├── media_library.json        # All 210 media items metadata
    └── files/                    # Downloaded key assets:
        ├── white_bkrd_AAP_LOGO_033026.png     (logo)
        ├── ryan-berg-advanceapractice-1.png   (founder photo)
        ├── ryan-berg-advanceapractice.png     (duplicate)
        ├── moda-reimbursement-email-ryan-berg.jpg   (Nate GOAT email)
        ├── moda-reimbursement-client-note.jpg       (alt version)
        └── aap-site-icon-512.png             (favicon)
```

**Total backup size:** ~7.6 MB

### What this backup covers

✅ All page content (raw HTML that I can push back via REST API if pages get corrupted)
✅ Customizer Additional CSS (can be restored via REST API `cpt-update` ability)
✅ Live patched plugin file (can be restored via `plugin-editor.php` if wp-admin works)
✅ Original unpatched plugin file (the rollback target)
✅ Media library index (all URLs + alt text + sizes)
✅ Key media files downloaded locally
✅ Site settings dump

### What this backup does NOT cover

❌ WordPress database dump — needs server-side mysqldump or a plugin like UpdraftPlus
❌ All media files (only the 6 critical ones downloaded; remaining 204 items are URL references only)
❌ Theme files
❌ wp-config.php / .htaccess
❌ User database (passwords, emails, etc.)

### For a complete database + file backup

**Recommended:** use **UpdraftPlus** (already installed and active on the site):

1. Go to https://advanceapractice.com/wp-admin/admin.php?page=updraftplus
2. Click "Backup Now"
3. Select "Include the database in the backup" ✓
4. Select "Include any files in the backup" ✓
5. Click "Backup Now"
6. When the backup completes, click "Existing Backups" tab
7. For each section (Database, Plugins, Themes, Uploads, Others), click the button and download the `.zip` / `.gz` files to your computer
8. Store those files alongside this repo's `backup/` directory for a complete restore point

**Alternative:** **All-in-One WP Migration** (also already installed):

1. Go to https://advanceapractice.com/wp-admin/admin.php?page=ai1wm_export
2. Click "Export To" → "File"
3. Wait for the `.wpress` file to be created
4. Click "Download" — this is a single archive containing everything

Either tool produces a full-site restore point. My local `backup/` is the application-state backup of **my work** (overlay, patches, content injections) — it's the missing piece alongside those tools for recreating the current production state.

---

## Known issues / TODOs

### Favicon requires file upload

The site favicon currently uses `white_bkrd_AAP_LOGO_033026.png` which has a baked-in white background. Browsers display it as a white square with the logo inside. **CSS cannot fix a favicon** — the fix requires a new image file.

**Action:** export the logo as a **transparent background PNG** (ideally 512×512) or SVG, upload at **https://advanceapractice.com/wp-admin/options-general.php** → "Site Icon" section.

### UpdraftPlus backup automation

UpdraftPlus does not expose a REST API for triggering backups. To run scheduled backups, configure them via the plugin's settings panel in wp-admin.

### Plugin version lockout

The patch targets `aap-hotfix-v1254`. Future version bumps (v1255, v1256...) require updating the path in `do_everything.py` and re-running the deploy. The old v1254 plugin folder will still exist but won't be active.

---

## Credentials note

The `dev` user password and the `claude` application password used by `do_everything.py` are currently pasted in this conversation log. **Rotate both now:**

1. **Application password:** Users → Profile → Application Passwords → Revoke the `claude` entry → create a new one
2. **dev user password:** Users → Profile → change the password

Then update `do_everything.py`, `emergency_revert.py`, and `do_backup.py` with the new application password (they all use base64-encoded Basic Auth).

---

## Git history of the overhaul

```
2cdb0e4  v6.5: fix bare .service-card class, bump container max-width to 1440px
00b1f98  v6: blue+white palette, logo, red-A wordmark, safety guard + fixes
90f0024  v5.1: header fix, contrast fixes, Moda rewrite, Nate GOAT framing
a801838  v4 overlay: Inter Tight, flat buttons, Moda recognition, tablet header
18ce7cf  v3.1 overlay: fix heading cascade + restore light header
53be831  v3 overlay: animations, 3D depth, glass morphism, new footer copy
ea3658e  v2 overlay: full-site patch via output buffer hook
d8df3f5  Replace v3.0 theme stylesheet with real overlay targeting aap-* classes
1e9a120  Major brand overhaul v3.0 (dead code — targeted wrong theme slot)
```

---

## Contact / hand-off

This project is maintained in the `claude/reverent-montalcini` branch of `rberg86/style.css`. To continue the work in a new Claude Code session, clone or pull the branch, read this README, and all context is preserved in the commit messages and inline code comments.
