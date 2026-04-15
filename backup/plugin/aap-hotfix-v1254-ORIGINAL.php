<?php
/**
 * Plugin Name: AAP Hotfix
 * Description: Live hotfix layer for AdvanceAPractice that repairs links, routes forms to Ryan, suppresses legacy artifacts, and normalizes key SEO metadata.
 * Version: 1.2.61
 * Author: AdvanceAPractice
 */

if (!defined('ABSPATH')) {
    exit;
}

const AAP_HOTFIX_EMAIL = 'ryan@advanceapractice.com';
const AAP_HOTFIX_SITE_NAME = 'AdvanceAPractice';
const AAP_HOTFIX_SITE_DESCRIPTION = 'National mental health billing, medical billing, credentialing, revenue cycle management, AI clinical documentation, and practice operations services for behavioral health and outpatient practices.';
const AAP_HOTFIX_FACEBOOK_URL = 'https://www.facebook.com/profile.php?id=100094167818307';
const AAP_HOTFIX_INSTAGRAM_URL = 'https://www.instagram.com/advanceapracticemanagement/';
const AAP_HOTFIX_LOGO_ID = 17891;
const AAP_HOTFIX_LOGO_URL = 'https://advanceapractice.com/wp-content/uploads/2026/03/white_bkrd_AAP_LOGO_033026.png';
const AAP_HOTFIX_SITE_ICON_ID = 17892;
const AAP_HOTFIX_SITE_ICON_URL = 'https://advanceapractice.com/wp-content/uploads/2026/03/aap-site-icon-512.png';

add_action('init', 'aap_hotfix_register_lead_post_type');
add_action('init', 'aap_hotfix_google_verification_file', 0);
add_action('admin_post_nopriv_aap_capture_lead', 'aap_hotfix_capture_lead_submission');
add_action('admin_post_aap_capture_lead', 'aap_hotfix_capture_lead_submission');
add_action('template_redirect', 'aap_hotfix_legacy_redirects', 0);
add_action('template_redirect', 'aap_hotfix_render_canonical_page_shell', 1);
add_action('admin_init', 'aap_hotfix_sync_site_settings');
add_action('admin_init', 'aap_hotfix_write_google_verification_file');
add_action('admin_init', 'aap_hotfix_detach_homepage_elementor_v2');
add_action('admin_init', 'aap_hotfix_detach_about_elementor_v2');
add_action('wp_body_open', 'aap_hotfix_output_live_header', 5);
add_action('wp_head', 'aap_hotfix_print_head_overrides', 99);
add_action('wp_head', 'aap_hotfix_print_shared_layout_css', 98);
add_action('wp_footer', 'aap_hotfix_output_live_footer', 5);
add_action('wp_footer', 'aap_hotfix_print_footer_hotfixes', 99);
add_filter('the_content', 'aap_hotfix_filter_content', 99);
add_filter('document_title_separator', 'aap_hotfix_document_title_separator', 99);
add_filter('wpseo_title', 'aap_hotfix_wpseo_title', 99);
add_filter('wpseo_metadesc', 'aap_hotfix_wpseo_metadesc', 99);
add_filter('wpseo_opengraph_title', 'aap_hotfix_wpseo_title', 99);
add_filter('wpseo_opengraph_desc', 'aap_hotfix_wpseo_metadesc', 99);
add_filter('wpseo_twitter_title', 'aap_hotfix_wpseo_title', 99);
add_filter('wpseo_twitter_description', 'aap_hotfix_wpseo_metadesc', 99);
add_filter('wpseo_canonical', 'aap_hotfix_wpseo_canonical', 99);
add_filter('wpseo_robots', 'aap_hotfix_wpseo_robots', 99);
add_filter('wpseo_exclude_from_sitemap_by_post_ids', 'aap_hotfix_exclude_from_sitemap', 99);

function aap_hotfix_register_lead_post_type() {
    register_post_type(
        'aap_lead',
        array(
            'labels' => array(
                'name' => 'AAP Leads',
                'singular_name' => 'AAP Lead',
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'supports' => array('title', 'editor', 'custom-fields'),
            'menu_icon' => 'dashicons-email-alt',
        )
    );
}

function aap_hotfix_google_verification_file() {
    $path = isset($_SERVER['REQUEST_URI']) ? wp_parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) : '';

    if ($path !== '/google71e6bee2660bd171.html') {
        return;
    }

    nocache_headers();
    header('Content-Type: text/html; charset=utf-8');
    echo 'google-site-verification: google71e6bee2660bd171.html';
    exit;
}

function aap_hotfix_write_google_verification_file() {
    $target = trailingslashit(ABSPATH) . 'google71e6bee2660bd171.html';
    $contents = 'google-site-verification: google71e6bee2660bd171.html';

    if (file_exists($target)) {
        $existing = @file_get_contents($target);
        if (trim((string) $existing) === $contents) {
            return;
        }
    }

    @file_put_contents($target, $contents);
}

function aap_hotfix_detach_homepage_elementor_v2() {
    $home = get_page_by_path('home');
    if (!$home instanceof WP_Post) {
        return;
    }

    delete_post_meta($home->ID, '_elementor_data');
    delete_post_meta($home->ID, '_elementor_edit_mode');
    delete_post_meta($home->ID, '_elementor_template_type');
    update_option('aap_hotfix_homepage_v2_detached', 'done');
}

function aap_hotfix_detach_about_elementor_v2() {
    if (get_option('aap_hotfix_about_v2_detached') === 'done') {
        return;
    }

    $about = get_page_by_path('about');
    if (!$about instanceof WP_Post) {
        return;
    }

    delete_post_meta($about->ID, '_elementor_data');
    delete_post_meta($about->ID, '_elementor_edit_mode');
    delete_post_meta($about->ID, '_elementor_template_type');
    update_option('aap_hotfix_about_v2_detached', 'done');
}

function aap_hotfix_sync_site_settings() {
    if (get_option('blogname') !== AAP_HOTFIX_SITE_NAME) {
        update_option('blogname', AAP_HOTFIX_SITE_NAME);
    }

    if (get_option('blogdescription') !== AAP_HOTFIX_SITE_DESCRIPTION) {
        update_option('blogdescription', AAP_HOTFIX_SITE_DESCRIPTION);
    }

    $yoast_titles = get_option('wpseo_titles', array());
    if (is_array($yoast_titles) && (!isset($yoast_titles['separator']) || $yoast_titles['separator'] !== 'sc-pipe')) {
        $yoast_titles['separator'] = 'sc-pipe';
        update_option('wpseo_titles', $yoast_titles);
    }

    if ((int) get_theme_mod('custom_logo') !== AAP_HOTFIX_LOGO_ID) {
        set_theme_mod('custom_logo', AAP_HOTFIX_LOGO_ID);
    }

    if ((int) get_option('site_icon') !== AAP_HOTFIX_SITE_ICON_ID) {
        update_option('site_icon', AAP_HOTFIX_SITE_ICON_ID);
    }
}

function aap_hotfix_asset_url($file) {
    return trailingslashit(plugin_dir_url(__FILE__) . 'assets/imagery/') . ltrim((string) $file, '/');
}

function aap_hotfix_replace_image_sequence($html, $needle, $sources) {
    if (!is_string($html) || $html === '' || empty($sources)) {
        return $html;
    }

    $index = 0;
    $pattern = '#<img\b([^>]*?)src=(["\'])([^"\']*' . preg_quote($needle, '#') . ')(\2)([^>]*?)>#i';

    return preg_replace_callback(
        $pattern,
        function ($matches) use (&$index, $sources) {
            $source = isset($sources[$index]) ? $sources[$index] : end($sources);
            $index++;
            return '<img' . $matches[1] . 'src=' . $matches[2] . esc_url($source) . $matches[2] . $matches[5] . '>';
        },
        $html
    );
}

function aap_hotfix_replace_nth_occurrence($html, $needle, $replacement, $nth = 2) {
    if (!is_string($html) || $html === '' || $needle === '' || $replacement === '' || $nth < 1) {
        return $html;
    }

    $offset = 0;
    $count = 0;
    $needle_length = strlen($needle);

    while (($position = strpos($html, $needle, $offset)) !== false) {
        $count++;
        if ($count === $nth) {
            return substr($html, 0, $position) . $replacement . substr($html, $position + $needle_length);
        }
        $offset = $position + $needle_length;
    }

    return $html;
}

function aap_hotfix_full_homepage_markup() {
    return <<<'HTML'
<!-- wp:html -->
<section class="hero">
  <div class="container">
    <div class="section-heading">
      <span class="eyebrow">Behavioral Health + Outpatient Practice Support</span>
      <h1>Billing, credentialing, and workflow support built for practices that need the business side to run with more control.</h1>
      <p class="text-lg">AdvanceAPractice helps behavioral health and outpatient teams stabilize reimbursement, improve provider readiness, reduce operational drag, and make better use of the systems they already rely on.</p>
      <p class="lede">The work is practical, implementation-aware, and built for practices that want sharper execution without a generic consulting layer.</p>
      <div class="hero-actions">
        <a class="button" href="/contact/" data-track="book-consultation">Book a Consultation</a>
        <a class="button-secondary" href="/practice-workflow-review-checklist/" data-track="get-checklist">Get the Workflow Checklist</a>
      </div>
    </div>
  </div>
</section>

<section class="section-tight">
  <div class="container">
    <div class="aap-home-trust-band">
      <article class="aap-home-trust-lead">
        <span class="aap-home-trust-kicker">Third-Party Recognition</span>
        <strong>Recognized among Portland's top medical billing companies</strong>
        <p>AdvanceAPractice was included in MediBillMD's roundup of leading medical billing companies, giving the site a clear third-party trust signal right next to the first consultation CTA.</p>
        <div class="aap-home-trust-meta">
          <span>Featured by MediBillMD</span>
          <a href="https://medibillmd.com/blog/medical-billing-companies-in-portland/" target="_blank" rel="noopener noreferrer">See the recognition</a>
        </div>
      </article>
      <div class="aap-home-trust-grid">
        <div class="aap-home-trust-fact"><strong>16 Years in Healthcare Operations</strong><span>Collections, implementation, denial follow-up, and multi-team execution.</span></div>
        <div class="aap-home-trust-fact"><strong>Behavioral Health + Outpatient Focus</strong><span>Built for reimbursement, provider readiness, and workflow pressure that overlap.</span></div>
        <div class="aap-home-trust-fact"><strong>Founder-Led, Serving Practices Nationwide</strong><span>Direct operational support without a generic consulting layer.</span></div>
      </div>
    </div>
  </div>
</section>

<section class="section section-sand">
  <div class="container split-layout split-layout-wide">
    <div>
      <div class="section-heading">
        <span class="kicker">Who We Help</span>
        <h2>Built for organizations where reimbursement, provider readiness, and day-to-day workflow have to work together.</h2>
        <p class="text-limit">AdvanceAPractice is a strong fit for therapy groups, psychiatry and PMHNP practices, outpatient specialty organizations, and growing teams that need more than a billing vendor or a software recommendation.</p>
      </div>
    </div>
    <div class="grid-2">
      <article class="contact-card">
        <span class="kicker">Behavioral Health</span>
        <h3>Therapy, psychiatry, counseling, and PMHNP practices.</h3>
        <p>Useful when denials, documentation handoffs, payer follow-up, and provider onboarding are all affecting the same revenue picture.</p>
      </article>
      <article class="contact-card">
        <span class="kicker">Outpatient Operations</span>
        <h3>Medical and specialty teams carrying operational pressure across multiple roles.</h3>
        <p>Useful when claim flow, reporting, front-end discipline, and execution standards need to improve without rebuilding the entire business.</p>
      </article>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-heading aap-centered-section">
      <span class="kicker">Problems We Solve</span>
      <h2>The friction usually starts in a handful of pressure points, then spreads across the practice.</h2>
      <p class="text-limit">Most practices do not need a vague reset. They need to identify where work is stalling, who owns the next step, and what is making the same cleanup repeat.</p>
    </div>
    <div class="grid-4">
      <article class="contact-card">
        <h3>Revenue work that stays reactive</h3>
        <p>Denials, aging A/R, and payer follow-up stay noisy because the same upstream issues are never fully closed.</p>
      </article>
      <article class="contact-card">
        <h3>Provider readiness that feels hard to see</h3>
        <p>Credentialing, enrollment, and onboarding move forward, but leaders still cannot clearly tell who is billable and when.</p>
      </article>
      <article class="contact-card">
        <h3>Workflow held together by memory</h3>
        <p>Front desk, providers, billing, and operations all carry pieces of the process, but the handoffs are not structured tightly enough.</p>
      </article>
      <article class="contact-card">
        <h3>Systems that never quite match the workflow</h3>
        <p>The platform has the data, but reporting, queues, templates, and task paths are not supporting the way the practice actually runs.</p>
      </article>
    </div>
  </div>
</section>

<section class="section section-sea">
  <div class="container">
    <div class="section-heading">
      <span class="kicker">Service Paths</span>
      <h2>Choose the area that best matches the pressure you are already feeling.</h2>
      <p class="text-limit">Each service page is built around a different buyer problem, so the next step can stay specific instead of sounding like a recycled service list.</p>
    </div>
    <div class="grid-3">
      <article class="service-card">
        <h3><a href="/mental-health-billing/">Mental Health Billing</a></h3>
        <p>For therapy, psychiatry, and PMHNP practices dealing with repeat denials, payer drag, and documentation-to-billing disconnects.</p>
      </article>
      <article class="service-card">
        <h3><a href="/medical-billing/">Medical Billing</a></h3>
        <p>For outpatient teams that need tighter claim flow, denial discipline, and better visibility into what is slowing collections.</p>
      </article>
      <article class="service-card">
        <h3><a href="/credentialing/">Credentialing</a></h3>
        <p>For practices that need enrollment sequencing, provider readiness tracking, and a more reliable path from hire to billable status.</p>
      </article>
      <article class="service-card">
        <h3><a href="/revenue-cycle-management/">Revenue Cycle Management</a></h3>
        <p>For organizations that need stronger denial visibility, workqueue ownership, and reporting that explains where reimbursement is slowing.</p>
      </article>
      <article class="service-card">
        <h3><a href="/practice-operations/">Practice Operations</a></h3>
        <p>For teams that need cleaner ownership, better execution across roles, and operating discipline that can support growth.</p>
      </article>
      <article class="service-card">
        <h3><a href="/ehr-workflow-optimization/">Current Systems / EHR Optimization</a></h3>
        <p>For practices that want to improve workflow inside AdvancedMD, athenahealth, SimplePractice, TherapyNotes, Epic, and similar environments before considering replacement.</p>
      </article>
      <article class="service-card">
        <h3><a href="/ai-documentation/">AI Documentation</a></h3>
        <p>For teams that want to reduce charting drag and admin burden without creating new risk for providers, billing, or follow-up.</p>
      </article>
      <article class="service-card">
        <h3><a href="/workflow-friction-audit/">Workflow Friction Audit</a></h3>
        <p>For practice leaders who can feel the slowdown across multiple teams and want a cleaner first read on where it actually starts.</p>
      </article>
    </div>
  </div>
</section>

<section class="section">
  <div class="container split-layout split-layout-wide">
    <div>
      <div class="section-heading">
        <span class="kicker">Why AdvanceAPractice</span>
        <h2>Founder-led support for practices that want operational judgment, not generic outsourcing language.</h2>
        <p class="text-limit">Ryan Berg built AdvanceAPractice after years in collections, denial management, account leadership, and EHR implementation, including work that supported a multi-state behavioral health organization through growth from $2M to $6M in annual revenue.</p>
      </div>
      <div class="link-list">
        <a href="/about/">Read the founder story</a>
        <a href="/revenue-cycle-management/">Explore revenue-cycle support</a>
        <a href="/practice-operations/">See practice-operations support</a>
      </div>
    </div>
    <aside class="contact-card">
      <span class="kicker">Systems Familiarity</span>
      <h3>The work starts with the environment your team already uses.</h3>
      <ul class="check-list">
        <li>AdvancedMD, athenahealth, Epic, TherapyNotes, SimplePractice, Valant, Kareo / Tebra, and ICANotes</li>
        <li>Operational decisions shaped around billing, documentation, credentialing, and reporting together</li>
        <li>Recommendations built for the staffing reality the practice actually has</li>
      </ul>
    </aside>
  </div>
</section>

<section class="section section-sand">
  <div class="container">
    <div class="section-heading aap-centered-section">
      <span class="kicker">How Engagements Work</span>
      <h2>The process is meant to help practices move from noise to a workable next step.</h2>
    </div>
    <div class="grid-4">
      <article class="contact-card">
        <span class="kicker">01</span>
        <h3>Surface the pressure point</h3>
        <p>Start with the issue leadership can already see, whether that is denials, provider onboarding, reporting gaps, or operational drift.</p>
      </article>
      <article class="contact-card">
        <span class="kicker">02</span>
        <h3>Trace the real bottleneck</h3>
        <p>Review handoffs, queues, timing, and ownership instead of treating every symptom like a separate problem.</p>
      </article>
      <article class="contact-card">
        <span class="kicker">03</span>
        <h3>Set the practical priorities</h3>
        <p>Define which changes reduce friction fastest without asking the team to absorb more complexity than it can carry.</p>
      </article>
      <article class="contact-card">
        <span class="kicker">04</span>
        <h3>Support the rollout</h3>
        <p>Keep the work tied to execution so the operating model becomes easier to run, not just easier to talk about.</p>
      </article>
    </div>
  </div>
</section>

<section class="section section-sea">
  <div class="container">
    <div class="section-heading">
      <span class="kicker">Proof</span>
      <h2>Recognition starts the conversation, but follow-through is what makes the work credible.</h2>
      <p class="text-limit">The outside recognition creates trust early. The stronger proof is what practice owners and clinical leaders notice once billing, workflow, and accountability start moving in a cleaner direction.</p>
    </div>
    <div class="proof-strip proof-strip-featured proof-strip-testimonials proof-strip-proof-pair">
      <article class="quote-card proof-card-screenshot">
        <div class="proof-screenshot-frame">
          <img src="https://advanceapractice.com/wp-content/uploads/2026/04/moda-reimbursement-client-note.jpg" alt="Comment from an impressed PMHNP praising Ryan Berg's accountability in a Moda reimbursement email thread">
        </div>
        <div class="quote-meta proof-screenshot-meta">
          <div>
            <strong>Comment from an impressed PMHNP</strong><br>
            <span>Real reimbursement-thread screenshot restored from the media library</span>
          </div>
        </div>
      </article>
      <article class="quote-card testimonial-card">
        <span class="testimonial-eyebrow">Practice owner feedback</span>
        <blockquote>"AdvanceAPractice has provided a great benefit to my growing practice and I strongly recommend their services. They were able to clearly explain the confusing insurance billing process and helped me create a plan to expand my business."</blockquote>
        <div class="quote-meta">
          <div>
            <strong>John Benson, PMHNP-BC</strong><br>
            <span>Owner, BBH Psychiatric Services</span>
          </div>
        </div>
      </article>
    </div>
  </div>
</section>

<section class="section">
  <div class="container split-layout split-layout-wide">
    <div>
      <div class="section-heading">
        <span class="kicker">Resources</span>
        <h2>Start with an article or worksheet if you want to clarify the issue before reaching out.</h2>
        <p class="text-limit">The resource hub is designed to help practice owners connect the symptom they are seeing to the right service path, whether the issue is credentialing lag, denial volume, current-system friction, or a broader workflow problem.</p>
      </div>
      <div class="hero-actions">
        <a class="button-secondary" href="/resources/">Explore Resources</a>
      </div>
    </div>
    <div class="contact-card">
      <span class="kicker">Popular Starting Points</span>
      <div class="link-list">
        <a href="/practice-workflow-review-checklist/">Practice Workflow Review Checklist</a>
        <a href="/credentialing-delays-killing-revenue/">How Credentialing Delays Show Up in Revenue</a>
        <a href="/denial-management-workflow/">Denial Management Workflow That Holds Up Under Real Volume</a>
      </div>
    </div>
  </div>
</section>

<section class="section section-sand">
  <div class="container split-layout split-layout-wide">
    <article class="contact-card">
      <div class="section-heading">
        <span class="kicker">FAQ</span>
        <h2>Questions practice owners usually want answered first.</h2>
      </div>
      <div class="faq-list">
        <details class="faq-item">
          <summary>Do you only help with behavioral health practices?</summary>
          <div class="faq-content"><p>Behavioral health is a core strength, but the work also supports outpatient and specialty practices where billing, readiness, systems use, and operational execution are tightly connected.</p></div>
        </details>
        <details class="faq-item">
          <summary>Do we need to replace our software to work with you?</summary>
          <div class="faq-content"><p>No. The first step is usually improving how the current workflow is carried inside the systems already in place.</p></div>
        </details>
        <details class="faq-item">
          <summary>What if the issue spans billing, credentialing, and operations at the same time?</summary>
          <div class="faq-content"><p>That is common. The work is designed to identify where the real bottleneck sits so the practice does not keep treating connected issues as separate projects.</p></div>
        </details>
      </div>
    </article>
    <article class="contact-card">
      <span class="kicker">What The First Step Looks Like</span>
      <h3>The first conversation should leave you with more clarity, not more confusion.</h3>
      <p>Use the contact page when the problem is already urgent or well defined. Use the checklist when you want a lower-friction way to surface what is creating the most drag first.</p>
      <p class="form-note">No patient PHI in first contact.</p>
    </article>
  </div>
</section>

<section class="section-tight">
  <div class="container cta-panel cta-panel-final">
    <div class="section-heading">
      <span class="kicker">Next Step</span>
      <h2>If the business side of the practice is taking too much energy to hold together, start the conversation here.</h2>
      <p class="text-limit">Book a consultation for a direct review, or use the workflow checklist if you want to narrow the issue before reaching out.</p>
    </div>
    <div class="hero-actions">
      <a class="button" href="/contact/" data-track="book-consultation">Book a Consultation</a>
      <a class="button-secondary" href="/practice-workflow-review-checklist/" data-track="get-checklist">Get the Workflow Checklist</a>
    </div>
  </div>
</section>
<!-- /wp:html -->
HTML;
}

function aap_hotfix_default_og_image($slug) {
    $images = array(
        'home' => 'https://advanceapractice.com/wp-content/uploads/2025/08/1.png',
        'about' => 'https://advanceapractice.com/wp-content/uploads/2023/07/diverse-medical-team-of-doctors-looking-at-camera-while-holding-clipboard-and-medical-files-e1623252244361-pf2n5igpmfm4ppl9w5nt89pd7hae8a7fb8ryn904qo.jpg',
        'contact' => 'https://advanceapractice.com/wp-content/uploads/2025/08/1.png',
        'resources' => 'https://advanceapractice.com/wp-content/uploads/2023/07/diverse-medical-team-of-doctors-looking-at-camera-while-holding-clipboard-and-medical-files-e1623252244361-pf2n5igpmfm4ppl9w5nt89pd7hae8a7fb8ryn904qo.jpg',
        'ai-documentation' => 'https://advanceapractice.com/wp-content/uploads/2026/03/aap-documentation-dashboard.png',
        'ai-revenue-cycle' => 'https://advanceapractice.com/wp-content/uploads/2026/03/aap-rcm-dashboard-1.png',
        'revenue-cycle-management' => 'https://advanceapractice.com/wp-content/uploads/2026/03/aap-rcm-dashboard-1.png',
        'credentialing-accelerator' => 'https://advanceapractice.com/wp-content/uploads/2023/07/diverse-medical-team-of-doctors-looking-at-camera-while-holding-clipboard-and-medical-files-e1623252244361-pf2n5igpmfm4ppl9w5nt89pd7hae8a7fb8ryn904qo.jpg',
        'credentialing' => 'https://advanceapractice.com/wp-content/uploads/2023/07/diverse-medical-team-of-doctors-looking-at-camera-while-holding-clipboard-and-medical-files-e1623252244361-pf2n5igpmfm4ppl9w5nt89pd7hae8a7fb8ryn904qo.jpg',
        'practice-automation-consulting' => 'https://advanceapractice.com/wp-content/uploads/2023/07/diverse-medical-team-of-doctors-looking-at-camera-while-holding-clipboard-and-medical-files-e1623252244361-pf2n5igpmfm4ppl9w5nt89pd7hae8a7fb8ryn904qo.jpg',
        'practice-operations' => 'https://advanceapractice.com/wp-content/uploads/2023/07/diverse-medical-team-of-doctors-looking-at-camera-while-holding-clipboard-and-medical-files-e1623252244361-pf2n5igpmfm4ppl9w5nt89pd7hae8a7fb8ryn904qo.jpg',
        'mental-health-billing' => 'https://advanceapractice.com/wp-content/uploads/2023/07/diverse-medical-team-of-doctors-looking-at-camera-while-holding-clipboard-and-medical-files-e1623252244361-pf2n5igpmfm4ppl9w5nt89pd7hae8a7fb8ryn904qo.jpg',
        'medical-billing' => 'https://advanceapractice.com/wp-content/uploads/2025/08/1.png',
        'workflow-friction-audit' => 'https://advanceapractice.com/wp-content/uploads/2023/07/diverse-medical-team-of-doctors-looking-at-camera-while-holding-clipboard-and-medical-files-e1623252244361-pf2n5igpmfm4ppl9w5nt89pd7hae8a7fb8ryn904qo.jpg',
        'behavioral-health-billing-pmhnp-groups' => 'https://advanceapractice.com/wp-content/uploads/2023/07/diverse-medical-team-of-doctors-looking-at-camera-while-holding-clipboard-and-medical-files-e1623252244361-pf2n5igpmfm4ppl9w5nt89pd7hae8a7fb8ryn904qo.jpg',
        'multi-state-credentialing-outpatient-practices' => 'https://advanceapractice.com/wp-content/uploads/2023/07/diverse-medical-team-of-doctors-looking-at-camera-while-holding-clipboard-and-medical-files-e1623252244361-pf2n5igpmfm4ppl9w5nt89pd7hae8a7fb8ryn904qo.jpg',
        'starting-a-practice' => 'https://advanceapractice.com/wp-content/uploads/2023/07/diverse-medical-team-of-doctors-looking-at-camera-while-holding-clipboard-and-medical-files-e1623252244361-pf2n5igpmfm4ppl9w5nt89pd7hae8a7fb8ryn904qo.jpg',
        'growing-a-solo-practice' => 'https://advanceapractice.com/wp-content/uploads/2025/08/1.png',
        'adding-providers' => 'https://advanceapractice.com/wp-content/uploads/2023/07/diverse-medical-team-of-doctors-looking-at-camera-while-holding-clipboard-and-medical-files-e1623252244361-pf2n5igpmfm4ppl9w5nt89pd7hae8a7fb8ryn904qo.jpg',
        'timely-filing-guide' => 'https://advanceapractice.com/wp-content/uploads/2026/03/aap-rcm-dashboard-1.png',
        'ehr-workflow-optimization' => 'https://advanceapractice.com/wp-content/uploads/2023/07/diverse-medical-team-of-doctors-looking-at-camera-while-holding-clipboard-and-medical-files-e1623252244361-pf2n5igpmfm4ppl9w5nt89pd7hae8a7fb8ryn904qo.jpg',
    );

    return isset($images[$slug]) ? $images[$slug] : 'https://advanceapractice.com/wp-content/uploads/2023/07/diverse-medical-team-of-doctors-looking-at-camera-while-holding-clipboard-and-medical-files-e1623252244361-pf2n5igpmfm4ppl9w5nt89pd7hae8a7fb8ryn904qo.jpg';
}

function aap_hotfix_diversify_page_visuals($slug, $html) {
    $html = str_replace('AdvanceAPractice operations overview screenshot', 'Healthcare operations illustration', $html);
    $html = str_replace('Healthcare workflow integration illustration', 'Healthcare workflow illustration', $html);
    $html = str_replace('Healthcare practice growth illustration', 'Practice growth illustration', $html);
    $html = str_replace('AdvanceAPractice operations workspace showing intake, reimbursement, and workflow visibility', 'Healthcare operations workspace showing scheduling, claims, and workflow visibility', $html);

    $real_media = array(
        'rcm_dashboard' => 'https://advanceapractice.com/wp-content/uploads/2026/03/aap-rcm-dashboard-1.png',
        'documentation_dashboard' => 'https://advanceapractice.com/wp-content/uploads/2026/03/aap-documentation-dashboard.png',
        'team_photo' => 'https://advanceapractice.com/wp-content/uploads/2023/07/diverse-medical-team-of-doctors-looking-at-camera-while-holding-clipboard-and-medical-files-e1623252244361-pf2n5igpmfm4ppl9w5nt89pd7hae8a7fb8ryn904qo.jpg',
        'city_aerial' => 'https://advanceapractice.com/wp-content/uploads/2023/07/dji_export_1635712616724-1-scaled.jpg',
        'sunset_building' => 'https://advanceapractice.com/wp-content/uploads/2025/07/file_0000000052d8622f8ad58c3721236ec5.png',
        'provider_portrait' => 'https://advanceapractice.com/wp-content/uploads/2025/08/1.png',
    );

    $html = str_replace(
        array(
            aap_hotfix_asset_url('operations-growth-studio.png'),
            aap_hotfix_asset_url('executive-ops-overview.png'),
            aap_hotfix_asset_url('behavioral-health-billing-desk.png'),
            aap_hotfix_asset_url('ai-revenue-command-center.png'),
            aap_hotfix_asset_url('medical-billing-workbench.png'),
            aap_hotfix_asset_url('credentialing-navigator.png'),
            aap_hotfix_asset_url('ai-documentation-studio.png'),
            aap_hotfix_asset_url('practice-growth.svg'),
            aap_hotfix_asset_url('pnw-operations.svg'),
            aap_hotfix_asset_url('mount-hood-pnw.svg')
        ),
        array(
            $real_media['team_photo'],
            $real_media['team_photo'],
            $real_media['team_photo'],
            $real_media['provider_portrait'],
            $real_media['provider_portrait'],
            $real_media['team_photo'],
            $real_media['documentation_dashboard'],
            $real_media['team_photo'],
            $real_media['team_photo'],
            $real_media['team_photo']
        ),
        $html
    );

    if ($slug === 'home') {
        $html = aap_hotfix_replace_nth_occurrence($html, $real_media['documentation_dashboard'], $real_media['provider_portrait'], 2);
    }

    if ($slug === 'practice-automation-consulting' || $slug === 'practice-operations') {
        $html = aap_hotfix_replace_nth_occurrence($html, $real_media['provider_portrait'], $real_media['team_photo'], 2);
    }

    $visual_map = array(
        'home' => array(
            $real_media['team_photo'],
            $real_media['provider_portrait'],
            $real_media['team_photo'],
            $real_media['provider_portrait'],
            $real_media['team_photo'],
            $real_media['provider_portrait'],
        ),
        'mental-health-billing' => array(
            $real_media['provider_portrait'],
            $real_media['team_photo'],
        ),
        'medical-billing' => array(
            $real_media['team_photo'],
            $real_media['provider_portrait'],
        ),
        'credentialing-accelerator' => array(
            $real_media['team_photo'],
            $real_media['provider_portrait'],
        ),
        'credentialing' => array(
            $real_media['team_photo'],
            $real_media['provider_portrait'],
        ),
        'ai-documentation' => array(
            $real_media['documentation_dashboard'],
            $real_media['provider_portrait'],
        ),
        'ai-revenue-cycle' => array(
            $real_media['rcm_dashboard'],
            $real_media['team_photo'],
        ),
        'revenue-cycle-management' => array(
            $real_media['rcm_dashboard'],
            $real_media['team_photo'],
        ),
        'practice-automation-consulting' => array(
            $real_media['team_photo'],
            $real_media['provider_portrait'],
        ),
        'practice-operations' => array(
            $real_media['team_photo'],
            $real_media['provider_portrait'],
        ),
        'ehr-workflow-optimization' => array(
            $real_media['team_photo'],
            $real_media['documentation_dashboard'],
        ),
    );

    if (isset($visual_map[$slug])) {
        $html = aap_hotfix_replace_image_sequence($html, 'executive-ops-overview.png', $visual_map[$slug]);
    }

    if ($slug === 'home') {
        $html = preg_replace(
            '#<img\b([^>]*?)src=(["\'])' . preg_quote(aap_hotfix_asset_url('operations-growth-studio.png'), '#') . '(\\2)([^>]*?)>#i',
            '<img$1src=$2' . esc_url($real_media['documentation_dashboard']) . '$2$4>',
            $html,
            1
        );
    }

    return $html;
}

function aap_hotfix_branding_head_markup() {
    $icon = esc_url(AAP_HOTFIX_SITE_ICON_URL);
    $apple_domain_verification = 'HCtw8TWckrjxG6Wt46XRDHv973NEabBnhxLJwu53AlI';
    $schema = wp_json_encode(
        array(
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => 'AdvanceAPractice',
            'url' => home_url('/'),
            'logo' => AAP_HOTFIX_LOGO_URL,
            'description' => 'AdvanceAPractice provides national mental health billing, medical billing, credentialing, revenue cycle management, AI clinical documentation, and practice operations services for behavioral health and outpatient practices.',
            'areaServed' => array('United States'),
        ),
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );

    return '<meta name="theme-color" content="#171b20">'
        . '<link rel="icon" href="' . $icon . '" sizes="32x32">'
        . '<link rel="icon" href="' . $icon . '" sizes="192x192">'
        . '<link rel="apple-touch-icon" href="' . $icon . '">'
        . '<meta name="apple-domain-verification" content="' . esc_attr($apple_domain_verification) . '">'
        . '<script type="application/ld+json">' . $schema . '</script>';
}

function aap_hotfix_sync_elementor_content() {
    if (get_option('aap_hotfix_elementor_sync_v1') === 'done') {
        return;
    }

    $page_ids = array(13, 103, 210, 17388, 17389, 17390, 17391, 17392, 17393, 17394);

    foreach ($page_ids as $page_id) {
        $json = get_post_meta($page_id, '_elementor_data', true);
        if (!$json) {
            continue;
        }

        $data = json_decode($json, true);
        if (!is_array($data)) {
            continue;
        }

        $page = get_post($page_id);
        $slug = $page instanceof WP_Post ? $page->post_name : '';
        $updated = aap_hotfix_transform_elementor_elements($data, $slug);

        update_post_meta($page_id, '_elementor_data', wp_slash(wp_json_encode($updated)));
    }

    update_option('aap_hotfix_elementor_sync_v1', 'done');
}

function aap_hotfix_detach_homepage_from_elementor() {
    if (get_option('aap_hotfix_home_detached_v1') === 'done') {
        return;
    }

    $home = get_page_by_path('home');
    if (!$home instanceof WP_Post) {
        return;
    }

    delete_post_meta($home->ID, '_elementor_data');
    delete_post_meta($home->ID, '_elementor_edit_mode');
    delete_post_meta($home->ID, '_elementor_template_type');

    update_option('aap_hotfix_home_detached_v1', 'done');
}

function aap_hotfix_flatten_canonical_pages_into_html_widgets() {
    if (get_option('aap_hotfix_flatten_pages_v1') === 'done') {
        return;
    }

    $slugs = array(
        'home',
        'about',
        'contact',
        'resources',
        'ai-documentation',
        'ai-revenue-cycle',
        'credentialing-accelerator',
        'practice-automation-consulting',
        'mental-health-billing',
        'medical-billing',
        'starting-a-practice',
        'growing-a-solo-practice',
        'adding-providers',
        'timely-filing-guide',
        'ehr-workflow-optimization',
        'practice-workflow-review-checklist',
    );

    foreach ($slugs as $slug) {
        $page = get_page_by_path($slug);
        if (!$page instanceof WP_Post) {
            continue;
        }

        $html = (string) $page->post_content;
        if ($html === '') {
            continue;
        }

        $html = str_replace('[aap_v6_managed_page]', '', $html);
        $html = trim($html);

        $data = array(
            array(
                'id' => substr(md5($slug . '-section'), 0, 7),
                'elType' => 'section',
                'settings' => array(
                    'layout' => 'full_width',
                    'content_width' => 'full',
                    'gap' => 'no',
                ),
                'elements' => array(
                    array(
                        'id' => substr(md5($slug . '-column'), 0, 7),
                        'elType' => 'column',
                        'settings' => array(
                            '_column_size' => 100,
                            '_inline_size' => null,
                        ),
                        'elements' => array(
                            array(
                                'id' => substr(md5($slug . '-widget'), 0, 7),
                                'elType' => 'widget',
                                'widgetType' => 'html',
                                'settings' => array(
                                    'html' => $html,
                                ),
                                'elements' => array(),
                            ),
                        ),
                        'isInner' => false,
                    ),
                ),
                'isInner' => false,
            ),
        );

        update_post_meta($page->ID, '_elementor_data', wp_slash(wp_json_encode($data)));
        update_post_meta($page->ID, '_elementor_edit_mode', 'builder');
        update_post_meta($page->ID, '_elementor_template_type', 'wp-page');
    }

    update_option('aap_hotfix_flatten_pages_v1', 'done');
}

function aap_hotfix_transform_elementor_elements($elements, $slug) {
    $result = array();

    foreach ($elements as $element) {
        $remove = false;

        if (!empty($element['widgetType']) && $element['widgetType'] === 'html' && !empty($element['settings']['html']) && is_string($element['settings']['html'])) {
            $html = $element['settings']['html'];
            $html = aap_hotfix_normalize_html_block($html, $slug);

            if ($slug === 'home') {
                $remove_markers = array(
                    'Tell us what is slowing the practice down.',
                    'How We Work',
                    'Why AdvanceAPractice',
                    'Best-Fit Practices',
                    'FAQ',
                );

                foreach ($remove_markers as $marker) {
                    if (strpos($html, $marker) !== false) {
                        $remove = true;
                        break;
                    }
                }
            }

            if ($slug === 'resources' && (strpos($html, 'Latest Insights') !== false || strpos($html, 'Loading the latest articles') !== false)) {
                $remove = true;
            }

            if (!$remove) {
                $element['settings']['html'] = $html;
            }
        }

        if (!empty($element['elements']) && is_array($element['elements'])) {
            $element['elements'] = aap_hotfix_transform_elementor_elements($element['elements'], $slug);
        }

        if (!$remove) {
            $result[] = $element;
        }
    }

    return $result;
}

function aap_hotfix_normalize_html_block($html, $slug) {
    $html = str_replace('/contact-us/', '/contact/', $html);
    $html = str_replace('/practice-resources/', '/resources/', $html);
    $html = str_replace('/about-us/', '/about/', $html);
    $html = str_replace('mailto:info@advanceapractice.com', '/contact/#aap-contact-form', $html);
    $html = str_replace('mailto:ryan@advanceapractice.com', '/contact/#aap-contact-form', $html);
    $html = str_replace('>Email AdvanceAPractice<', '>Use the Contact Form<', $html);
    $html = str_replace('Approved feedback from healthcare practices.', 'What healthcare practices say.', $html);
    $html = str_replace('Featured Guide', 'Featured Resource', $html);
    $html = str_replace('Category', '', $html);

    if ($slug === 'contact') {
        $html = str_replace('<form action="/wp-admin/admin-post.php" method="post" accept-charset="UTF-8">', '<form id="aap-contact-form" action="/wp-admin/admin-post.php" method="post" accept-charset="UTF-8">', $html);
        $html = str_replace('Prefer a direct email first? Contact <a href="/contact/#aap-contact-form">info@advanceapractice.com</a>. Do not include patient PHI in your initial message.', 'Use the contact form below and do not include patient PHI in your initial message.', $html);
        $html = str_replace('<p><a href="/contact/#aap-contact-form">info@advanceapractice.com</a></p>', '<p><a href="#aap-contact-form">Go to the contact form</a></p>', $html);
    }

    if ($slug === 'home') {
        $html = str_replace('Prefer email first? Contact <a href="/contact/#aap-contact-form">info@advanceapractice.com</a>. Do not include patient PHI in your initial message.', 'Use the contact form to start the conversation. Do not include patient PHI in your initial message.', $html);
        $html = str_replace('https://advanceapractice.com/wp-content/uploads/2025/01/dji_fly_20231231_082040_0016_1704041053345_photo-1-scaled-e1736691273316.webp', 'https://advanceapractice.com/wp-content/uploads/2025/07/file_0000000052d8622f8ad58c3721236ec5.png', $html);
    }

    if ($slug === 'practice-automation-consulting' || $slug === 'practice-operations') {
        $html = str_replace('https://advanceapractice.com/wp-content/uploads/2025/08/1.png', 'https://advanceapractice.com/wp-content/uploads/2023/07/dji_export_1635712616724-1-scaled.jpg', $html);
    }

    if ($slug === 'credentialing-accelerator' || $slug === 'credentialing') {
        $html = str_replace('https://advanceapractice.com/wp-content/uploads/2024/01/2023-03-06.webp', 'https://advanceapractice.com/wp-content/uploads/2025/08/1.png', $html);
    }

    if ($slug === 'medical-billing') {
        $html = str_replace('https://advanceapractice.com/wp-content/uploads/2024/01/2023-03-06.webp', 'https://advanceapractice.com/wp-content/uploads/2023/07/diverse-medical-team-of-doctors-looking-at-camera-while-holding-clipboard-and-medical-files-e1623252244361-pf2n5igpmfm4ppl9w5nt89pd7hae8a7fb8ryn904qo.jpg', $html);
    }

    return $html;
}

function aap_hotfix_start_output_buffer() {
    if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
        return;
    }

    ob_start('aap_hotfix_filter_full_html');
}

function aap_hotfix_capture_lead_submission() {
    if (!empty($_POST['website'])) {
        aap_hotfix_render_response(true, aap_hotfix_post_value('return_url'));
    }

    $decision_maker_name = sanitize_text_field(aap_hotfix_post_value('decision_maker_name'));
    $name_parts = aap_hotfix_split_name($decision_maker_name);

    $lead = array(
        'form_name' => sanitize_text_field(aap_hotfix_post_value('form_name')),
        'service_context' => sanitize_text_field(aap_hotfix_post_value('service_context')),
        'first_name' => sanitize_text_field(aap_hotfix_post_value('first_name')) ?: $name_parts['first_name'],
        'last_name' => sanitize_text_field(aap_hotfix_post_value('last_name')) ?: $name_parts['last_name'],
        'decision_maker_name' => $decision_maker_name,
        'decision_maker_role' => sanitize_text_field(aap_hotfix_post_value('decision_maker_role')),
        'email' => sanitize_email(aap_hotfix_post_value('email')),
        'practice_name' => sanitize_text_field(aap_hotfix_post_value('practice_name')),
        'practice_stage' => sanitize_text_field(aap_hotfix_post_value('practice_stage')),
        'state' => sanitize_text_field(aap_hotfix_post_value('state')),
        'states_served' => sanitize_text_field(aap_hotfix_post_value('states_served')),
        'ehr_pm_system' => sanitize_text_field(aap_hotfix_post_value('ehr_pm_system')),
        'service_interest' => sanitize_text_field(aap_hotfix_post_value('service_interest')),
        'bottleneck_type' => sanitize_text_field(aap_hotfix_post_value('bottleneck_type')),
        'urgency' => sanitize_text_field(aap_hotfix_post_value('urgency')) ?: sanitize_text_field(aap_hotfix_post_value('desired_timeline')),
        'goal_90_day' => sanitize_textarea_field(aap_hotfix_post_value('goal_90_day')),
        'slowdown' => sanitize_textarea_field(aap_hotfix_post_value('slowdown')),
        'page_title' => sanitize_text_field(aap_hotfix_post_value('page_title')),
        'page_url' => esc_url_raw(aap_hotfix_post_value('page_url')),
        'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '',
    );

    if ($lead['states_served'] && !$lead['state']) {
        $lead['state'] = $lead['states_served'];
    }

    if (empty($lead['practice_name']) || empty($lead['email']) || empty($lead['service_interest']) || empty($lead['slowdown'])) {
        aap_hotfix_render_response(false, aap_hotfix_post_value('return_url'), 'Please go back and complete the required fields.');
    }

    if (!is_email($lead['email'])) {
        aap_hotfix_render_response(false, aap_hotfix_post_value('return_url'), 'Please enter a valid email address.');
    }

    aap_hotfix_store_lead_backup($lead);
    aap_hotfix_send_lead_email($lead);
    aap_hotfix_render_response(true, aap_hotfix_post_value('return_url'));
}

function aap_hotfix_store_lead_backup($lead) {
    $title_bits = array_filter(
        array(
            $lead['practice_name'],
            $lead['service_interest'],
            current_time('Y-m-d H:i'),
        )
    );

    $post_id = wp_insert_post(
        array(
            'post_type' => 'aap_lead',
            'post_status' => 'private',
            'post_title' => implode(' - ', $title_bits),
            'post_content' => $lead['slowdown'],
        ),
        true
    );

    if (is_wp_error($post_id) || !$post_id) {
        return;
    }

    foreach ($lead as $key => $value) {
        update_post_meta($post_id, 'aap_' . $key, $value);
    }
}

function aap_hotfix_send_lead_email($lead) {
    $reply_name = trim($lead['decision_maker_name'] ?: trim($lead['first_name'] . ' ' . $lead['last_name']));

    $body = array(
        'A new AdvanceAPractice form was submitted.',
        '',
        'Form: ' . ($lead['form_name'] ?: 'Website form'),
        'Service context: ' . ($lead['service_context'] ?: 'Not provided'),
        'Decision-maker name: ' . ($reply_name ?: 'Not provided'),
        'Decision-maker role: ' . ($lead['decision_maker_role'] ?: 'Not provided'),
        'Email: ' . $lead['email'],
        'Practice name: ' . $lead['practice_name'],
        'Primary location or service area: ' . ($lead['state'] ?: 'Not provided'),
        'Current EHR / PM system: ' . ($lead['ehr_pm_system'] ?: 'Not provided'),
        'Service interest: ' . $lead['service_interest'],
        'Biggest bottleneck: ' . ($lead['bottleneck_type'] ?: 'Not provided'),
        'Desired timeline: ' . ($lead['urgency'] ?: 'Not provided'),
        '',
        'Current bottleneck:',
        $lead['slowdown'],
        '',
        'What they want to improve in the next 90 days:',
        ($lead['goal_90_day'] ?: 'Not provided'),
        '',
        'Page title: ' . ($lead['page_title'] ?: 'Not provided'),
        'Page URL: ' . ($lead['page_url'] ?: 'Not provided'),
    );

    $headers = array();
    if ($reply_name && $lead['email']) {
        $headers[] = 'Reply-To: ' . $reply_name . ' <' . $lead['email'] . '>';
    }

    wp_mail(
        AAP_HOTFIX_EMAIL,
        sprintf('New AdvanceAPractice lead: %s - %s', $lead['practice_name'], $lead['service_interest']),
        implode("\n", $body),
        $headers
    );
}

function aap_hotfix_render_response($success, $return_url = '', $message = '') {
    $safe_return_url = $return_url ? esc_url($return_url) : home_url('/');
    $title = $success ? 'Thanks. Your request was sent.' : 'We could not send your request.';
    $description = $success
        ? 'AdvanceAPractice received your form submission. It was delivered directly by email, and a backup copy has been saved in WordPress.'
        : ($message ? esc_html($message) : 'Please go back and try again.');

    status_header($success ? 200 : 400);
    nocache_headers();

    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>' . esc_html($title) . '</title><style>
    body{margin:0;font-family:Segoe UI,Arial,sans-serif;background:#f3f8f7;color:#173136;display:grid;place-items:center;min-height:100vh;padding:24px}
    .panel{max-width:720px;background:#fff;border:1px solid rgba(18,55,60,.12);border-radius:24px;padding:32px;box-shadow:0 18px 40px rgba(9,39,42,.12)}
    h1{margin:0 0 12px;font-size:2rem;line-height:1.1}
    p{margin:0 0 18px;line-height:1.6;color:#4d666a}
    a{display:inline-block;padding:14px 22px;border-radius:999px;background:#0f6a6d;color:#fff;text-decoration:none;font-weight:700}
    </style></head><body><div class="panel"><h1>' . esc_html($title) . '</h1><p>' . $description . '</p><a href="' . $safe_return_url . '">Return to the page</a></div></body></html>';
    exit;
}

function aap_hotfix_legacy_redirects() {
    if (is_admin()) {
        return;
    }

    $request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '/';
    $path = wp_parse_url($request_uri, PHP_URL_PATH);
    $path = $path ? trailingslashit($path) : '/';

    $redirects = array(
        '/contact-us/' => '/contact/',
        '/practice-resources/' => '/resources/',
        '/about-us/' => '/about/',
        '/mental-health-marketing/' => '/practice-operations/',
        '/mental-health-billing-services/' => '/mental-health-billing/',
        '/timely-filing-how-does-it-work-and-what-are-the-3-types-of-limits/' => '/timely-filing-guide/',
        '/mental-health-billing/timely-filing-how-does-it-work-and-what-are-the-3-types-of-limits/' => '/timely-filing-guide/',
        '/ai-revenue-cycle/' => '/revenue-cycle-management/',
        '/credentialing-accelerator/' => '/credentialing/',
        '/practice-automation-consulting/' => '/practice-operations/',
        '/mental-health-marketing__trashed/revenue-cycle-management/' => '/practice-operations/',
        '/oregon-practice-manage/' => '/resources/',
        '/current-systems/' => '/ehr-workflow-optimization/',
        '/__trashed/' => '/',
        '/__trashed-2/' => '/',
        '/__trashed-3/' => '/',
        '/__trashed-4/' => '/',
        '/__trashed-2__trashed/' => '/',
    );

    if (isset($redirects[$path])) {
        wp_safe_redirect(home_url($redirects[$path]), 301);
        exit;
    }
}

function aap_hotfix_render_canonical_page_shell() {
    if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST) || is_feed()) {
        return;
    }

    $slug = aap_hotfix_current_slug();
    $canonical_slugs = array(
        'home',
        'about',
        'contact',
        'resources',
        'ai-documentation',
        'ai-revenue-cycle',
        'revenue-cycle-management',
        'credentialing-accelerator',
        'credentialing',
        'practice-automation-consulting',
        'practice-operations',
        'mental-health-billing',
        'medical-billing',
        'workflow-friction-audit',
        'behavioral-health-billing-pmhnp-groups',
        'multi-state-credentialing-outpatient-practices',
        'starting-a-practice',
        'growing-a-solo-practice',
        'adding-providers',
        'timely-filing-guide',
        'ehr-workflow-optimization',
        'practice-workflow-review-checklist',
    );

    if (!in_array($slug, $canonical_slugs, true)) {
        return;
    }

    global $post;
    if (!$post instanceof WP_Post) {
        if ($slug === 'home') {
            $front_page_id = (int) get_option('page_on_front');
            if ($front_page_id > 0) {
                $post = get_post($front_page_id);
            }
            if (!$post instanceof WP_Post) {
                $post = get_page_by_path('home', OBJECT, 'page');
            }
        } else {
            $post = get_page_by_path($slug, OBJECT, 'page');
        }
    }

    if (!$post instanceof WP_Post) {
        return;
    }

    $map = aap_hotfix_meta_map();
    $meta = isset($map[$slug]) ? $map[$slug] : array();
    $title = isset($meta['title']) ? $meta['title'] : get_the_title($post);
    $description = isset($meta['description']) ? $meta['description'] : wp_strip_all_tags(get_the_excerpt($post));
    $canonical = isset($meta['canonical']) ? home_url($meta['canonical']) : get_permalink($post);
    $og_type = isset($meta['type']) ? $meta['type'] : 'website';
    $og_image = isset($meta['og_image']) ? $meta['og_image'] : aap_hotfix_default_og_image($slug);
    $og_image_alt = isset($meta['og_image_alt']) ? $meta['og_image_alt'] : $title;
    if ($slug === 'home') {
        $content = aap_hotfix_full_homepage_markup();
        $content = aap_hotfix_diversify_page_visuals($slug, $content);
        $content = str_replace('<div class="strip-card"><strong>Recognized by MediBillMD</strong></div>', '', $content);
        if (strpos($content, 'id="aap-home-lead-panel"') === false) {
            $content = aap_hotfix_insert_after_first_section($content, aap_hotfix_get_homepage_lead_panel_markup());
        }
        $content = aap_hotfix_operator_language_pass($slug, $content);
        $content = do_shortcode(trim($content));
    } else {
        $content = aap_hotfix_prepare_canonical_content($slug, $post->post_content);
    }

    status_header(200);

    echo '<!DOCTYPE html><html lang="en-US"><head>';
    echo '<meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . esc_html($title) . '</title>';
    echo '<meta name="description" content="' . esc_attr($description) . '">';
    echo '<link rel="canonical" href="' . esc_url($canonical) . '">';
    echo '<meta property="og:title" content="' . esc_attr($title) . '">';
    echo '<meta property="og:description" content="' . esc_attr($description) . '">';
    echo '<meta property="og:url" content="' . esc_url($canonical) . '">';
    echo '<meta property="og:type" content="' . esc_attr($og_type) . '">';
    echo '<meta property="og:image" content="' . esc_url($og_image) . '">';
    echo '<meta property="og:image:alt" content="' . esc_attr($og_image_alt) . '">';
    echo '<meta name="twitter:card" content="summary_large_image">';
    echo '<meta name="twitter:title" content="' . esc_attr($title) . '">';
    echo '<meta name="twitter:description" content="' . esc_attr($description) . '">';
    echo '<meta name="twitter:image" content="' . esc_url($og_image) . '">';
    echo aap_hotfix_branding_head_markup();
    echo '<style>' . aap_hotfix_site_shell_css() . aap_hotfix_site_polish_css() . '</style>';
    echo '</head><body class="aap-live-shell aap-hotfix aap-page-' . esc_attr(sanitize_html_class($slug)) . '">';
    echo aap_hotfix_site_header_markup();
    echo '<main class="aap-live-main">' . $content . '</main>';
    echo aap_hotfix_site_footer_markup();
    aap_hotfix_print_footer_hotfixes();
    echo '</body></html>';
    exit;
}

function aap_hotfix_prepare_canonical_content($slug, $html) {
    $html = str_replace('[aap_v6_managed_page]', '', (string) $html);
    $html = str_replace('mailto:info@advanceapractice.com', '/contact/#aap-contact-form', $html);
    $html = str_replace('mailto:ryan@advanceapractice.com', '/contact/#aap-contact-form', $html);
    $html = str_replace('/contact-us/', '/contact/', $html);
    $html = str_replace('/practice-resources/', '/resources/', $html);
    $html = str_replace('/about-us/', '/about/', $html);
    $html = str_replace('/mental-health-marketing/', '/practice-operations/', $html);
    $html = str_replace('/mental-health-billing-services/', '/mental-health-billing/', $html);
    $html = str_replace('/credentialing-accelerator/', '/credentialing/', $html);
    $html = str_replace('/ai-revenue-cycle/', '/revenue-cycle-management/', $html);
    $html = str_replace('/practice-automation-consulting/', '/practice-operations/', $html);
    $html = str_replace('>AI Billing<', '>Revenue Cycle Management<', $html);
    $html = str_replace('>View AI Billing<', '>See Revenue Cycle Management<', $html);
    $html = str_replace('AI Revenue Cycle Support', 'Revenue Cycle Management', $html);
    $html = str_replace('AI revenue cycle support', 'revenue cycle management', $html);
    $html = str_replace('AI Revenue Cycle', 'Revenue Cycle Management', $html);
    $html = str_replace('AI revenue cycle', 'revenue cycle management', $html);
    $html = str_replace('Approved feedback from healthcare practices.', 'What healthcare practices say.', $html);
    $html = str_replace('Featured Guide', 'Featured Resource', $html);
    $html = str_replace('Start Intake', 'Get Started', $html);
    $html = str_replace('See Consulting Support', 'See Practice Operations', $html);
    $html = str_replace('Explore Consulting', 'Explore Practice Operations', $html);
    $html = str_replace('See Consulting', 'See Practice Operations', $html);
    $html = str_replace('>Consulting<', '>Practice Operations<', $html);
    $html = str_replace('practice operations consulting', 'practice operations', $html);
    $html = str_replace('Practice operations consulting', 'Practice operations', $html);
    $html = str_replace('practice automation consulting', 'practice operations', $html);
    $html = str_replace('Practice automation consulting', 'Practice operations', $html);
    $html = str_replace('/current-systems/', '/ehr-workflow-optimization/', $html);
    $html = str_replace('/wp-content/plugins/advanceapractice-lead-capture/assets/imagery/integration-layer.svg', aap_hotfix_asset_url('pnw-operations.svg'), $html);
    $html = aap_hotfix_restore_seed_content_if_needed($slug, $html);

    if ($slug === 'home') {
        $html = aap_hotfix_full_homepage_markup();
        $html = str_replace('<div class="strip-card"><strong>Recognized by MediBillMD</strong></div>', '', $html);
        if (strpos($html, 'id="aap-home-lead-panel"') === false) {
            $html = aap_hotfix_insert_after_first_section($html, aap_hotfix_get_homepage_lead_panel_markup());
        }
        $html = str_replace('Schedule a Consultation', 'Book a Consultation', $html);
        $html = aap_hotfix_diversify_page_visuals($slug, $html);
        $html = aap_hotfix_operator_language_pass($slug, $html);
        return do_shortcode(trim($html));
    }

    $html = aap_hotfix_diversify_page_visuals($slug, $html);
    $html = aap_hotfix_operator_language_pass($slug, $html);

    if ($slug === 'contact') {
        $html = aap_hotfix_normalize_contact_form_copy($html);
        $html = aap_hotfix_refine_contact_support_blocks($html);
    }

    if ($slug === 'about') {
        $html = aap_hotfix_refine_about_support_blocks($html);
    }

    if (in_array($slug, array('about', 'resources', 'ai-documentation', 'ai-revenue-cycle', 'revenue-cycle-management', 'credentialing-accelerator', 'credentialing', 'practice-automation-consulting', 'practice-operations', 'mental-health-billing', 'medical-billing', 'workflow-friction-audit', 'behavioral-health-billing-pmhnp-groups', 'multi-state-credentialing-outpatient-practices', 'starting-a-practice', 'growing-a-solo-practice', 'adding-providers', 'timely-filing-guide', 'ehr-workflow-optimization'), true) && strpos($html, 'aap-growth-path-strip') === false) {
        $html .= aap_hotfix_get_growth_path_strip_markup();
    }

    return do_shortcode(trim($html));
}

function aap_hotfix_seed_content_map() {
    return array(
        'home' => 'index-blocks.html',
        'about' => 'about-blocks.html',
        'contact' => 'contact-blocks.html',
        'resources' => 'resources-blocks.html',
        'ai-documentation' => 'ai-documentation-blocks.html',
        'ai-revenue-cycle' => 'ai-revenue-cycle-blocks.html',
        'revenue-cycle-management' => 'ai-revenue-cycle-blocks.html',
        'credentialing-accelerator' => 'credentialing-accelerator-blocks.html',
        'credentialing' => 'credentialing-accelerator-blocks.html',
        'practice-automation-consulting' => 'practice-automation-consulting-blocks.html',
        'practice-operations' => 'practice-automation-consulting-blocks.html',
        'mental-health-billing' => 'mental-health-billing-blocks.html',
        'medical-billing' => 'medical-billing-blocks.html',
        'mental-health-billing-mistakes' => 'mental-health-billing-mistakes-blocks.html',
        'credentialing-delays-explained' => 'credentialing-delays-explained-blocks.html',
        'credentialing-delays-killing-revenue' => 'credentialing-delays-killing-revenue-blocks.html',
        'denial-management-workflow' => 'denial-management-workflow-blocks.html',
        'ar-backlog-causes' => 'ar-backlog-causes-blocks.html',
        'ehr-optimization-vs-replacement' => 'ehr-optimization-vs-replacement-blocks.html',
        'timely-filing-guide' => 'timely-filing-guide-blocks.html',
        'practice-workflow-review-checklist' => 'practice-workflow-review-checklist-blocks.html',
    );
}

function aap_hotfix_seed_content_file_path($file_name) {
    if (!is_string($file_name) || $file_name === '') {
        return '';
    }

    $candidates = array(
        trailingslashit(WP_PLUGIN_DIR) . 'advanceapractice-lead-capture/seed-content/' . $file_name,
        trailingslashit(WP_PLUGIN_DIR) . 'advanceapractice-lead-capture-v145/seed-content/' . $file_name,
        dirname(__FILE__) . '/../advanceapractice-lead-capture/seed-content/' . $file_name,
        dirname(__FILE__) . '/../advanceapractice-lead-capture-v145/seed-content/' . $file_name,
    );

    foreach ($candidates as $candidate) {
        if (file_exists($candidate)) {
            return $candidate;
        }
    }

    if (function_exists('glob')) {
        $matches = glob(trailingslashit(WP_PLUGIN_DIR) . 'advanceapractice-lead-capture*/seed-content/' . $file_name);
        if (is_array($matches)) {
            foreach ($matches as $match) {
                if (file_exists($match)) {
                    return $match;
                }
            }
        }
    }

    return '';
}

function aap_hotfix_get_seed_content($slug) {
    $map = aap_hotfix_seed_content_map();
    if (!isset($map[$slug])) {
        return '';
    }

    $path = aap_hotfix_seed_content_file_path($map[$slug]);
    if ($path === '') {
        return '';
    }

    $content = @file_get_contents($path);
    return is_string($content) ? trim($content) : '';
}

function aap_hotfix_should_restore_seed_content($slug, $html) {
    $html = is_string($html) ? trim($html) : '';
    if ($html === '') {
        return true;
    }

    $section_count = preg_match_all('#<section\b#i', $html, $matches);
    $text_length = strlen(trim(wp_strip_all_tags($html)));

    if ($slug === 'home') {
        $required_markers = array('Who We Help', 'Problems We Solve', 'Service Paths', 'Credibility', 'FAQ', 'Next Step');
        if ($section_count < 9 || $text_length < 4200) {
            return true;
        }

        foreach ($required_markers as $marker) {
            if (stripos($html, $marker) === false) {
                return true;
            }
        }

        return false;
    }

    $core_pages = array(
        'about',
        'contact',
        'resources',
        'ai-documentation',
        'ai-revenue-cycle',
        'revenue-cycle-management',
        'credentialing-accelerator',
        'credentialing',
        'practice-automation-consulting',
        'practice-operations',
        'mental-health-billing',
        'medical-billing',
        'practice-workflow-review-checklist',
    );

    if (in_array($slug, $core_pages, true) && ($section_count < 2 || $text_length < 900)) {
        return true;
    }

    return false;
}

function aap_hotfix_restore_seed_content_if_needed($slug, $html) {
    $seeded_html = aap_hotfix_get_seed_content($slug);
    if ($seeded_html === '') {
        return $html;
    }

    if (aap_hotfix_should_restore_seed_content($slug, $html)) {
        return $seeded_html;
    }

    return $html;
}

function aap_hotfix_restore_main_html_if_needed($slug, $html) {
    if (!is_string($html) || $html === '' || !aap_hotfix_should_restore_seed_content($slug, $html)) {
        return $html;
    }

    return aap_hotfix_prepare_canonical_content($slug, $html);
}

function aap_hotfix_insert_after_first_section($html, $insert) {
    if (!$html || !$insert) {
        return $html;
    }

    $position = stripos($html, '</section>');
    if ($position === false) {
        return $insert . $html;
    }

    $position += strlen('</section>');
    return substr($html, 0, $position) . $insert . substr($html, $position);
}

function aap_hotfix_refine_homepage_markup($html) {
    if (!is_string($html) || $html === '') {
        return $html;
    }

    $lead_panel = '';
    if (preg_match('#<style id="aap-home-lead-panel-css">[\s\S]*?</style>\s*<section[^>]*id="aap-home-lead-panel"[\s\S]*?</section>#i', $html, $lead_match)) {
        $lead_panel = $lead_match[0];
        $html = str_replace($lead_panel, '', $html);
    }

    $html = preg_replace('#<section[^>]*class="section-tight"[^>]*>\s*<div class="container trust-strip">[\s\S]*?</section>#i', '', $html, 1);
    $html = preg_replace('#<section[^>]*class="section-tight"[^>]*>[\s\S]*?<span class="kicker">Next Step</span>[\s\S]*?</section>#i', '', $html, 1);
    $html = preg_replace('#<section[^>]*>[\s\S]*?Nationwide service model[\s\S]*?Implementation-led[\s\S]*?</section>#i', '', $html, 1);
    $html = preg_replace('#<section[^>]*>[\s\S]*?<h2>Choose the issue that needs attention first\.</h2>[\s\S]*?</section>#i', '', $html, 1);
    $html = preg_replace('#<section[^>]*>[\s\S]*?<h2>Best for practices that need cleaner systems and steadier follow-through\.</h2>[\s\S]*?</section>#i', '', $html, 1);
    $html = preg_replace('#<section[^>]*>[\s\S]*?<h2>A healthcare operations partner, not a clinic and not a generic AI vendor\.</h2>[\s\S]*?</section>#i', '', $html, 1);
    $html = preg_replace('#<section[^>]*>[\s\S]*?<h2>Choose the place where the practice needs help first\.</h2>[\s\S]*?</section>#i', '', $html, 1);
    $html = preg_replace('#<div class="strip-card">\s*<strong>Recognized by MediBillMD</strong>\s*</div>#i', '', $html, 1);
    $html = preg_replace('#<p>\s*Recognized among Portland\'s top medical billing companies\.\s*Featured by MediBillMD\.\s*</p>#i', '', $html);

    $replacements = array(
        'Billing, credentialing, revenue cycle management, workflow support, and practical automation for behavioral health organizations and growing practices that need steadier operations behind reimbursement and growth.' => 'Billing, credentialing, revenue cycle management, workflow design, and healthcare automation guidance for behavioral health organizations and growing practices that need steadier reimbursement, provider readiness, and day-to-day execution.',
        'The goal is not more noise. The goal is clearer documentation, cleaner operations, and better revenue follow-through around the systems the practice already has.' => 'The goal is clearer documentation, steadier handoffs, and revenue work that does not keep slipping between teams or systems already in use.',
        'Implementation starts with the current workflow, isolates the real friction, and adds structure where it will hold up in live use.' => 'Implementation starts with the workflow already in motion, identifies where work is getting stuck, and adds structure that can hold up in live use.',
        'Find where documentation, billing, credentialing, and admin handoffs are creating the most drag.' => 'Find where documentation, billing, credentialing, and admin handoffs are causing rework, delays, or missed follow-up.',
        'Built for practices that need cleaner systems and steadier follow-through.' => 'Built for practices that need steadier systems and clearer operational ownership.',
        'National service with Portland roots kept in the background' => 'National delivery with regional credibility kept in the background',
        'The goal is not to automate everything. The goal is to reduce repetitive manual work, improve routing and visibility, and help the team spend less time chasing avoidable tasks.' => 'The goal is not to automate everything. The goal is to reduce repetitive manual work, improve routing, and help the team spend less time chasing avoidable tasks.',
        'Portland roots, national support' => 'National support model',
        'Based in Portland and serving practices across Oregon, Washington, and nationwide with remote-first support.' => 'Remote-first delivery for behavioral health and outpatient practices across the United States, with regional credibility kept in the background.',
        'AdvanceAPractice helps practices improve workflow inside the tools they already use, with support that respects how staff actually move through the day.' => 'AdvanceAPractice helps practices improve workflow inside the tools they already use, with changes that respect how staff actually move through the day.',
        'Portland roots and Pacific Northwest credibility stay in the background while the service stays national in reach.' => 'Regional credibility stays in the background while the service stays national in reach.',
        'National support with local credibility kept where it helps' => 'National support with local credibility used where it adds trust',
        'Selected feedback from healthcare practices.' => 'What healthcare practices say.',
        'Start with the workflow problem that is creating the most drag right now.' => 'Start with the workflow problem that is slowing collections, onboarding, or day-to-day execution right now.',
        'Tell us what is slowing collections, credentialing, documentation, or operations, and we\'ll route the conversation to the right service lane.' => 'Tell us what is slowing collections, payer setup, documentation, or operations, and we\'ll route the conversation to the right service line.',
        'Practical automation for intake, admin, and operational workflow.' => 'Practical automation for patient access, admin work, and operational workflow.',
        'Better systems, cleaner handoffs, and more useful visibility.' => 'Better systems, clearer handoffs, and reporting the team can actually use.',
    );

    $html = str_replace(array_keys($replacements), array_values($replacements), $html);

    if (strpos($html, '<span class="kicker">Resources</span>') === false) {
        $html = preg_replace(
            '#(<section[^>]*class="section section-sand"[^>]*>[\s\S]*?<span class="kicker">Get Started</span>)#i',
            aap_hotfix_get_homepage_resources_markup() . '$1',
            $html,
            1
        );
    }

    if (strpos($html, '<span class="kicker">FAQ</span>') === false) {
        $html .= aap_hotfix_get_homepage_faq_markup();
    }

    $html = preg_replace_callback(
        '#<section[^>]*>\s*<div class="container">\s*<div class="section-heading">\s*<span class="kicker">Proof</span>[\s\S]*?</section>#i',
        function ($matches) {
            $section = $matches[0];

            if (strpos($section, 'Feedback from founders, operators, and clinical leaders') === false) {
                $section = str_replace(
                    '<h2>What healthcare practices say.</h2>',
                    '<h2>What healthcare practices say.</h2><p class="text-limit">Feedback from founders, operators, and clinical leaders who needed billing, onboarding, and operational work to translate into steadier execution.</p>',
                    $section
                );
            }

            if (strpos($section, 'proof-strip-testimonials') === false) {
                $section = str_replace('class="proof-strip', 'class="proof-strip proof-strip-testimonials', $section);
            }

            $section = str_replace('class="grid-2"', 'class="grid-2 homepage-proof-grid"', $section);
            $section = str_replace('class="quote-card"', 'class="quote-card testimonial-card"', $section);
            $section = str_replace('class="quote-meta"', 'class="quote-meta testimonial-attribution"', $section);
            $section = str_replace('class="proof-card proof-card-recognition proof-card-recognition-featured"', 'class="proof-card proof-card-recognition proof-card-recognition-featured homepage-proof-recognition"', $section);
            $section = preg_replace(
                '#<p>\s*<strong>([^<]+)</strong>\s*</p>#',
                '<p class="testimonial-attribution"><strong>$1</strong></p>',
                $section,
                2
            );

            return $section;
        },
        $html,
        1
    );

    if ($lead_panel === '' && function_exists('aap_hotfix_get_homepage_lead_panel_markup')) {
        $lead_panel = aap_hotfix_get_homepage_lead_panel_markup();
    }

    if ($lead_panel !== '') {
        $inserted = 0;
        $html = preg_replace('#(<section[^>]*>\s*<div class="container">\s*<div class="section-heading">\s*<span class="kicker">FAQ</span>[\s\S]*?</section>)#i', $lead_panel . '$1', $html, 1, $inserted);
        if (!$inserted) {
            $html = preg_replace('#(<section[^>]*>\s*<div class="container">\s*<div class="section-heading">\s*<span class="kicker">Resources</span>[\s\S]*?</section>)#i', '$1' . $lead_panel, $html, 1, $inserted);
        }
        if (!$inserted) {
            $html .= $lead_panel;
        }
    }

    return $html;
}

function aap_hotfix_operator_language_pass($slug, $html) {
    if (!is_string($html) || $html === '') {
        return $html;
    }

    $global = array(
        'Practice Operations & Workflow Design' => 'Practice Operations',
        'Practice practice operations' => 'Practice operations',
        'practice practice operations' => 'practice operations',
        'practice-operations support' => 'practice operations support',
        'Practice-operations support' => 'Practice operations support',
        'What is creating the most drag right now?' => 'What is getting stuck right now?',
        'The form is the fastest way to route the inquiry to the right conversation without losing detail.' => 'The form is the fastest way to send the right context for a useful first conversation.',
        'intake-to-claim' => 'front-desk-to-claim',
        'Intake-to-claim' => 'Front-desk-to-claim',
        'Open the Full Intake' => 'Open the Full Contact Form',
    );

    $html = str_replace(array_keys($global), array_values($global), $html);

    $page_specific = array(
        'contact' => array(
            'Email-first contact for practices that need stronger operations.' => 'Contact AdvanceAPractice for billing, credentialing, revenue cycle, and practice operations review.',
            'Get started with billing, credentialing, and operations support.' => 'Get started with billing, credentialing, revenue cycle management, and practice operations.',
            'The fastest way to start is to share the current bottleneck, the systems you use, and the kind of support you need. That helps keep the first conversation focused, practical, and useful.' => 'The fastest way to start is to share what is getting stuck, the systems in use, and the kind of help you need. That keeps the first conversation focused and useful.',
            'Public contact stays simple so requests can be routed clearly without a phone-first process.' => 'Public contact stays simple so requests can be routed clearly without a long back-and-forth.',
            'Common starting points' => 'Common reasons practices reach out',
            'Credentialing delays, billing bottlenecks, workflow cleanup, documentation burden, and systems that need better structure.' => 'Credentialing delays, billing slowdowns, workflow cleanup, documentation burden, and systems that need better structure.',
            'Send the bottleneck, and we will route the conversation from there.' => 'Tell us what is slowing the work, and we will route the conversation from there.',
            'Start the conversation' => 'Request a practice review',
            'The first response should be practical: understand the workflow problem, identify the right service lane, and avoid wasting your time with a generic sales reply.' => 'The first response should be practical: understand the workflow problem, identify the right service line, and avoid wasting your time with a generic sales reply.',
            'You get a clearer next step, not a generic pitch, so the conversation stays grounded in the actual workflow.' => 'You get a clear next step, not a generic pitch, so the conversation stays grounded in the actual workflow.',
            'Email-first intake' => 'Best first step',
            'Use <a href="mailto:ryan@advanceapractice.com">ryan@advanceapractice.com</a> for project discussions and intake questions.' => 'Use <a href="mailto:ryan@advanceapractice.com">ryan@advanceapractice.com</a> for project discussions and practice-review questions.',
        ),
        'about' => array(
            'The company was built around a simple idea: healthcare teams need better systems, clearer workflow, and more useful implementation support, not more hype.' => 'AdvanceAPractice was built on a simple idea: healthcare teams need better systems, clearer workflow, and hands-on implementation support, not more hype.',
            'A proprietary internal system helps support billing, credentialing, workflow visibility, and operational follow-through in a more structured way.' => 'An internal operating system helps organize billing, credentialing, reporting, and execution in a more structured way.',
            'Leaders who need operational clarity, stronger systems, and better follow-through as the practice grows.' => 'Leaders who need operational clarity, stronger systems, and better execution as the practice grows.',
            'Teams trying to reduce workflow friction, improve visibility, and keep systems from depending on one overloaded person.' => 'Teams trying to reduce workflow breakdowns, improve reporting clarity, and keep systems from depending on one overloaded person.',
            'AdvanceAPractice works at the level where billing, credentialing, documentation, intake, and staff follow-through connect.' => 'AdvanceAPractice works at the level where billing, credentialing, documentation, scheduling, and staff follow-up connect.',
            'Portland operations with national reach' => 'Healthcare operations partner for practices nationwide',
        ),
        'mental-health-billing' => array(
            'Behavioral health billing has its own workflow realities, denial patterns, payer issues, documentation-related friction, and credentialing overlap.' => 'Behavioral health billing has its own payer rules, denial patterns, documentation issues, and credentialing overlap.',
            'Behavioral health billing with stronger workflow follow-through behind it.' => 'Behavioral health billing with stronger payer follow-up and operational discipline behind it.',
            'The same payer and workflow issues return because intake, documentation, and follow-through were never tightened up.' => 'The same payer and workflow issues return because eligibility, documentation, and follow-up were never tightened up.',
            'Improve the handoffs between intake, documentation, billing, and payer communication that directly affect collections.' => 'Improve the handoffs between eligibility work, documentation, billing, and payer communication that directly affect collections.',
            'No. The billing work is connected to intake, documentation, credentialing, follow-up, and operational visibility because those workflow issues directly affect reimbursement performance.' => 'No. The billing work is connected to eligibility work, documentation, credentialing, follow-up, and operational reporting because those workflow issues directly affect reimbursement performance.',
            'Need stronger mental health billing services with better workflow support behind them?' => 'Need stronger mental health billing services with a steadier workflow behind them?',
            'Behavioral health billing has its own payer rules, denial patterns, documentation issues, and credentialing overlap.' => 'Behavioral health billing requires tighter eligibility work, authorization tracking, telehealth billing rules, modifier discipline, documentation support, and denial follow-up than many general billing teams expect.',
        ),
        'medical-billing' => array(
            'This page broadens the brand beyond behavioral health while keeping the same implementation-led approach. The focus is cleaner operations, more dependable follow-through, and better visibility into what is actually holding revenue performance back.' => 'This page broadens the brand beyond behavioral health while keeping the same operator-led approach. The focus is cleaner operations, more dependable execution, and better reporting on what is actually holding revenue performance back.',
            'Operational visibility' => 'Reporting clarity',
            'Build clearer reporting and workflow visibility so leadership can see what is aging, stuck, or underperforming.' => 'Build clearer reporting so leadership can see what is aging, stuck, or underperforming.',
            'Need stronger medical billing services with better operational follow-through?' => 'Need stronger medical billing services with steadier execution behind them?',
            'AdvanceAPractice can help review claim flow, denial patterns, reporting gaps, and workflow friction so medical billing performance improves on a cleaner operational foundation.' => 'AdvanceAPractice can help review claim flow, denial patterns, reporting gaps, and workflow breakdowns so medical billing performance improves on a cleaner operational foundation.',
            'Tighten the operational handoffs that sit behind reimbursement, from intake through payment posting and follow-up.' => 'Tighten the operational handoffs that sit behind reimbursement, from registration and charge capture through payment posting and follow-up.',
            'Build clearer reporting so leadership can see what is aging, stuck, or underperforming.' => 'Build clearer reporting so leadership can see where eligibility errors, charge lag, underpayments, payment posting delays, and denial work are slowing reimbursement.',
        ),
        'credentialing-accelerator' => array(
            'Credentialing and payer setup are operational work, not just paperwork.' => 'Credentialing and payer setup are provider-readiness work, not just paperwork.',
            'A premium operational service for providers and groups who need to accelerate credentialing, payer enrollment, and onboarding without creating more workflow confusion behind the scenes.' => 'Credentialing help for providers and groups that need CAQH cleanup, payer enrollment, onboarding coordination, and cleaner tracking without letting provider readiness drift.',
            'The goal is to reduce delays, improve payer sequencing, and keep provider readiness from slipping because too many details are spread across email, portals, spreadsheets, and memory.' => 'The goal is to reduce enrollment delays, improve payer sequencing, and keep provider readiness from slipping because details are spread across email threads, payer portals, spreadsheets, and memory.',
        ),
        'ai-documentation' => array(
            'Documentation and AI support should stay grounded in healthcare workflow reality.' => 'Documentation workflow support should stay grounded in healthcare workflow reality.',
            'A few official references that align with documentation burden reduction, healthcare IT burden, and AI governance.' => 'A few official references that align with charting burden reduction, provider workflow, template governance, and review controls.',
            'Reduce charting burden' => 'Reduce charting burden without losing review control',
        ),
        'ai-revenue-cycle' => array(
            'Revenue cycle management built around workflow visibility, reimbursement discipline, and cleaner operations.' => 'Revenue cycle management built around reporting clarity, reimbursement discipline, and cleaner operations.',
            'Revenue-cycle problems are usually handoff problems, visibility problems, or follow-through problems.' => 'Revenue-cycle problems are usually handoff problems, reporting problems, or follow-up problems.',
            'Improve visibility into recurring denial patterns, status-heavy follow-up, and work that keeps aging without a clear owner.' => 'Improve reporting on recurring denial patterns, status-heavy follow-up, and work that keeps aging without a clear owner.',
            'Improve accountability, automation targets, reporting support, and follow-through inside current systems.' => 'Improve accountability, automation targets, reporting support, and follow-up inside current systems.',
            'AdvanceAPractice helps behavioral health and outpatient medical practices improve intake-to-claim workflows, denials visibility, KPI support, reporting clarity, and day-to-day accountability through revenue cycle management and workflow review.' => 'AdvanceAPractice helps behavioral health and outpatient medical practices improve front-end-to-claim workflows, denial reporting, KPI support, payer follow-up, and day-to-day accountability through revenue cycle management.',
            'Visibility into denials, intake mistakes, and aging claims' => 'Visibility into denials, eligibility mistakes, and aging claims',
            'Without clearer KPI and workflow visibility, practices struggle to tell whether the issue is intake, billing, payer behavior, staffing, or process design.' => 'Without clearer KPI reporting, practices struggle to tell whether the issue is patient access, billing, payer behavior, staffing, or process design.',
            'Better visibility makes it easier to spot whether the real problem lives in intake, documentation, payer follow-up, or staffing.' => 'Better reporting makes it easier to spot whether the real problem lives in patient access, documentation, payer follow-up, or staffing.',
            'Assess intake, claims, denials, status work, and reporting gaps.' => 'Assess patient-access work, claims, denials, status work, and reporting gaps.',
            'Need cleaner intake-to-claim workflows and stronger revenue visibility?' => 'Need cleaner claim workflows and stronger revenue reporting?',
            'AdvanceAPractice can help you review denials, intake bottlenecks, reporting gaps, and revenue-cycle management opportunities in a way that stays grounded in real healthcare operations.' => 'AdvanceAPractice can help you review denials, front-end bottlenecks, reporting gaps, and revenue-cycle management opportunities in a way that stays grounded in real healthcare operations.',
        ),
        'practice-automation-consulting' => array(
            'Practice operations consulting for healthcare teams that need cleaner workflows and stronger systems.' => 'Practice operations for healthcare teams that need cleaner workflows and stronger systems.',
            'This is operational consulting with implementation in mind.' => 'This is practice operations work with implementation in mind.',
            'Operational friction usually looks small until it compounds across the whole practice.' => 'Workflow gaps usually look small until they compound across the whole practice.',
            'Need a cleaner workflow and a more realistic automation plan?' => 'Need a cleaner workflow and a more realistic automation plan?',
            'AdvanceAPractice helps healthcare organizations improve intake workflows, scheduling and admin friction, automation planning, KPI visibility, rollout support, and staff adoption without turning the engagement into vague strategy theater.' => 'AdvanceAPractice helps healthcare organizations improve patient-access workflows, scheduling, task routing, automation planning, KPI reporting, rollout support, and staff adoption without turning the engagement into vague strategy theater.',
            'Clarify how intake, scheduling, documentation, billing, credentialing, and admin tasks actually move through the practice.' => 'Clarify how patient access, scheduling, documentation, billing, credentialing, and admin tasks actually move through the practice.',
            'AdvanceAPractice can help you review intake, scheduling, task flow, visibility, and automation priorities before more growth adds more complexity.' => 'AdvanceAPractice can help you review patient access, scheduling, task flow, reporting, and automation priorities before more growth adds more complexity.',
        ),
        'resources' => array(
            'The AdvanceAPractice resources hub is designed to be useful, structured, and practical.' => 'The AdvanceAPractice resources hub is designed to be useful, structured, and operator-minded.',
            'Understand the three common timely filing limit structures, why claims miss deadlines, and what disciplined workflow follow-through should look like in both behavioral health and broader outpatient billing operations.' => 'Understand the three common timely filing limit structures, why claims miss deadlines, and what disciplined follow-up should look like in both behavioral health and broader outpatient billing operations.',
        'Start with <a href="/credentialing/">provider credentialing services</a> and use the resources hub to frame better tracking and follow-through.' => 'Start with <a href="/credentialing/">provider credentialing help</a> and use the resources hub to frame better tracking and follow-up.',
        'Start with <a href="/ai-documentation/">AI documentation</a> or <a href="/practice-operations/">practice automation consulting</a> to understand what should improve first.' => 'Start with <a href="/ai-documentation/">AI documentation</a> or <a href="/practice-operations/">practice operations support</a> to understand what should improve first.',
            'How to simplify intake and handoffs without replacing everything' => 'How to simplify patient-access work and handoffs without replacing everything',
        ),
        'timely-filing-guide' => array(
            'Timely filing is not just a payer rule. It is a visibility problem, an ownership problem, and a workflow problem.' => 'Timely filing is not just a payer rule. It is a reporting problem, an ownership problem, and a workflow problem.',
            'AI can improve visibility and consistency, but it should not replace revenue cycle judgment.' => 'AI can improve reporting consistency, but it should not replace revenue cycle judgment.',
            'Timely filing is one of the simplest ways a practice can lose clean revenue. It is also one of the clearest signals that an intake, billing, or follow-up workflow is not being managed tightly enough.' => 'Timely filing is one of the simplest ways a practice can lose clean revenue. It is also one of the clearest signals that a patient-access, billing, or follow-up workflow is not being managed tightly enough.',
        ),
        'ehr-workflow-optimization' => array(
            'Workflow improvement should work around the systems the practice already uses.' => 'Workflow improvement should work inside the systems the practice already uses.',
            'AdvanceAPractice works inside the EHR and practice-management systems a team already relies on, including Epic, AdvancedMD, Kareo, TherapyNotes, SimplePractice, athenahealth, NextGen, Practice Fusion, eClinicalWorks, and IntakeQ or PracticeQ where those systems are already in place. The goal is clearer handoffs, steadier reporting, and workflow changes that fit the environment already in use before anyone talks about replacement.' => 'AdvanceAPractice works inside the EHR and practice-management systems a team already relies on, including AdvancedMD, Epic, TherapyNotes, SimplePractice, athenahealth, Kareo or Tebra, Valant, Clinicient, and similar environments. The goal is better handoffs, queue ownership, template discipline, and reporting clarity before anyone talks about replacement.',
        ),
    );

    if (isset($page_specific[$slug])) {
        $html = str_replace(array_keys($page_specific[$slug]), array_values($page_specific[$slug]), $html);
    }

    return $html;
}

function aap_hotfix_normalize_contact_form_copy($html) {
    // Keep the contact form clean and avoid over-asking on location. (No "practice footprint" framing.)
    $html = str_replace('Start the Conversation', 'Book a Consultation', $html);
    $html = str_replace('Start the conversation', 'Book a consultation', $html);
    $html = str_replace('Decision-maker role', 'Your role', $html);
    $html = str_replace('Decision-maker name', 'Your name', $html);
    $html = str_replace('<label for="aap-email">Email</label>', '<label for="aap-email">Work email</label>', $html);
    $html = str_replace('Service interest', 'What do you need help with first?', $html);
    $html = str_replace('Primary service interest', 'What do you need help with?', $html);
    $html = str_replace('Select a service', 'Select one', $html);
    $html = str_replace('Current top bottleneck', 'What is getting stuck right now?', $html);
    $html = str_replace('Current bottleneck', 'What is getting stuck right now?', $html);
    $html = str_replace('Desired timeline', 'Desired timing', $html);
    $html = str_replace('Mental Health Billing Services', 'Mental Health Billing', $html);
    $html = str_replace('Medical Billing Services', 'Medical Billing', $html);
    $html = str_replace('Provider Credentialing Services', 'Credentialing', $html);
    $html = str_replace('AI Revenue Cycle Support', 'Revenue Cycle Management', $html);
    $html = str_replace('AI Revenue Cycle', 'Revenue Cycle Management', $html);
    $html = str_replace('Practice Automation Consulting', 'Practice Operations', $html);
    $html = str_replace('Growth Consulting', 'Practice Operations', $html);
    $html = str_replace('<h3>Email first</h3>', '<h3>Best first step</h3>', $html);
    $html = str_replace('<p><a href="mailto:ryan@advanceapractice.com">Email AdvanceAPractice</a></p>', '<p><a href="#aap-contact-form">Use the contact form</a></p>', $html);
    $html = str_replace('<p>Email is the cleanest first step for most inquiries and helps keep the request organized.</p>', '<p>The form is the fastest way to send the right context for a useful first conversation.</p>', $html);
    $html = str_replace('<p>Use the contact form below and do not include patient PHI in your initial message.</p>', '<p>Do not include patient PHI in your first message.</p>', $html);
    $html = str_replace('<p class="aap-note">Prefer a direct email first? Contact <a href="mailto:ryan@advanceapractice.com">Email AdvanceAPractice</a>. Do not include patient PHI in your initial message.</p>', '<p class="aap-note">Do not include patient PHI in your first message.</p>', $html);
    $html = str_replace('Practice location', 'Practice state or service area', $html);
    $html = preg_replace('#<div class="field"><label for="contact-providers">Number of providers</label><input id="contact-providers" name="number_of_providers" type="text"></div>#i', '', $html);
    $html = preg_replace('#<div class="field"><label for="contact-specialty">Specialty</label><input id="contact-specialty" name="specialty" type="text"></div>#i', '', $html);
    $html = str_replace('<label for="contact-website">Leave this field blank</label>', '<label for="contact-website" aria-hidden="true"></label>', $html);

    return $html;
}

function aap_hotfix_refine_about_support_blocks($html) {
    $replacement = '<aside class="contact-card aap-side-note-card">
      <span class="kicker">Operator Lens</span>
      <h3>Experience only matters if it makes daily execution easier to carry.</h3>
      <ul class="aap-side-note-list">
        <li>Sixteen years across collections, denial work, account leadership, and implementation support.</li>
        <li>Useful when growth, provider readiness, and workflow pressure are touching the same bottleneck.</li>
        <li>Grounded in real staffing load, payer friction, and the systems teams already have to use.</li>
      </ul>
    </aside>';

    return preg_replace(
        '#<aside class="proof-card proof-card-recognition proof-card-recognition-featured">[\s\S]*?</aside>#i',
        $replacement,
        $html,
        1
    );
}

function aap_hotfix_refine_contact_support_blocks($html) {
    $replacement = '<article class="contact-card aap-side-note-card">
        <span class="kicker">Response Standard</span>
        <h3>The first reply should be practical, specific, and useful.</h3>
        <ul class="aap-side-note-list">
          <li>Requests are reviewed for the operating issue behind the visible symptom.</li>
          <li>If billing, credentialing, workflow, and systems are mixed together, the first step is helping narrow the right lane.</li>
          <li>You should leave the first exchange with a clearer next move, not a generic sales reply.</li>
        </ul>
      </article>';

    return preg_replace(
        '#<div class="proof-card proof-card-recognition proof-card-recognition-featured">[\s\S]*?</div>#i',
        $replacement,
        $html,
        1
    );
}

function aap_hotfix_social_links_markup($class = 'aap-social-links', $show_labels = false) {
    $links = array(
        array(
            'name' => 'Facebook',
            'url' => AAP_HOTFIX_FACEBOOK_URL,
            'icon' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M13.2 21v-7.7h2.6l.4-3h-3V8.4c0-.9.3-1.5 1.6-1.5h1.5V4.3c-.3 0-1.2-.1-2.4-.1-2.3 0-3.9 1.4-3.9 4.1v2.3H7.4v3h2.5V21h3.3Z" fill="currentColor"/></svg>',
        ),
        array(
            'name' => 'Instagram',
            'url' => AAP_HOTFIX_INSTAGRAM_URL,
            'icon' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M7.5 3h9A4.5 4.5 0 0 1 21 7.5v9a4.5 4.5 0 0 1-4.5 4.5h-9A4.5 4.5 0 0 1 3 16.5v-9A4.5 4.5 0 0 1 7.5 3Zm0 1.8A2.7 2.7 0 0 0 4.8 7.5v9a2.7 2.7 0 0 0 2.7 2.7h9a2.7 2.7 0 0 0 2.7-2.7v-9a2.7 2.7 0 0 0-2.7-2.7h-9Zm9.6 1.35a1.05 1.05 0 1 1 0 2.1 1.05 1.05 0 0 1 0-2.1ZM12 7.2A4.8 4.8 0 1 1 7.2 12 4.8 4.8 0 0 1 12 7.2Zm0 1.8A3 3 0 1 0 15 12a3 3 0 0 0-3-3Z" fill="currentColor"/></svg>',
        ),
    );

    $html = '<div class="' . esc_attr($class) . '">';
    foreach ($links as $link) {
        $html .= '<a href="' . esc_url($link['url']) . '" target="_blank" rel="noopener noreferrer" aria-label="Follow AdvanceAPractice on ' . esc_attr($link['name']) . '">'
            . '<span class="aap-social-icon" aria-hidden="true">' . $link['icon'] . '</span>';
        if ($show_labels) {
            $html .= '<span class="aap-social-label">' . esc_html($link['name']) . '</span>';
        } else {
            $html .= '<span class="sr-only">Follow on ' . esc_html($link['name']) . '</span>';
        }
        $html .= '</a>';
    }
    $html .= '</div>';

    return $html;
}

function aap_hotfix_site_header_markup() {
    $logo = esc_url(AAP_HOTFIX_LOGO_URL);

    return '<header class="aap-global-header"><div class="aap-global-wrap">'
        . '<div class="aap-global-header-bar">'
        . '<a class="aap-global-brand" href="/"><img src="' . $logo . '" alt="AdvanceAPractice" width="1536" height="1024" decoding="async" fetchpriority="high"><span class="aap-global-brand-copy"><strong>AdvanceAPractice</strong><em>Behavioral health + outpatient practices nationwide</em></span></a>'
        . '<nav class="aap-global-nav" aria-label="Primary">'
        . '<a href="/">Home</a>'
        . '<a href="/mental-health-billing/">Mental Health Billing</a>'
        . '<a href="/medical-billing/">Medical Billing</a>'
        . '<a href="/credentialing/">Credentialing</a>'
        . '<a href="/ai-documentation/">AI Documentation</a>'
        . '<a href="/revenue-cycle-management/">Revenue Cycle Management</a>'
        . '<a href="/practice-operations/">Practice Operations</a>'
        . '<a href="/ehr-workflow-optimization/">Current Systems</a>'
        . '<a href="/workflow-friction-audit/">Workflow Friction Audit</a>'
        . '<a href="/resources/">Resources</a>'
        . '<a href="/about/">About</a>'
        . '<a href="/contact/">Contact</a>'
        . '</nav>'
        . '<div class="aap-global-actions"><a class="aap-global-cta" href="/contact/#aap-contact-form">Book a Consultation</a><button class="aap-menu-toggle" type="button" aria-controls="aap-mobile-menu" aria-expanded="false">Menu</button></div>'
        . '</div>'
        . '<div class="aap-mobile-nav-panel" id="aap-mobile-menu" hidden>'
        . '<p class="aap-mobile-nav-intro">Behavioral health + outpatient practices nationwide</p>'
        . '<a href="/">Home</a>'
        . '<a href="/mental-health-billing/">Mental Health Billing</a>'
        . '<a href="/medical-billing/">Medical Billing</a>'
        . '<a href="/credentialing/">Credentialing</a>'
        . '<a href="/ai-documentation/">AI Documentation</a>'
        . '<a href="/revenue-cycle-management/">Revenue Cycle Management</a>'
        . '<a href="/practice-operations/">Practice Operations</a>'
        . '<a href="/ehr-workflow-optimization/">Current Systems</a>'
        . '<a href="/workflow-friction-audit/">Workflow Friction Audit</a>'
        . '<a href="/resources/">Resources</a>'
        . '<a href="/about/">About</a>'
        . '<a href="/contact/">Contact</a>'
        . '<a class="aap-mobile-nav-cta" href="/contact/#aap-contact-form">Book a Consultation</a>'
        . '</div>'
        . '</div></header>';
}

function aap_hotfix_site_footer_markup() {
    return '<footer class="aap-live-footer aap-live-footer-v2"><div class="aap-global-wrap">'
        . '<div class="aap-live-footer-shell">'
        . '<div class="aap-live-footer-brand"><strong>AdvanceAPractice</strong><p>Founder-led support for behavioral health and outpatient practices that need billing, credentialing, workflow, documentation, and operational decisions to line up more cleanly.</p><p class="aap-live-footer-note">Serving practices nationwide.</p><p class="aap-footer-quote">Practical execution, sharper visibility, and steadier follow-through for teams that are tired of work getting lost between systems.</p><div class="aap-live-footer-actions-shell"><div class="aap-live-footer-actions"><a class="aap-footer-btn" href="/contact/#aap-contact-form">Book a Consultation</a><a class="aap-live-footer-linkbutton" href="/practice-workflow-review-checklist/">Get the Workflow Checklist</a></div></div></div>'
        . '<div class="aap-live-footer-links aap-live-footer-contact"><h3>Contact</h3><span>Get in touch</span><a class="aap-footer-contact-link" href="mailto:' . esc_attr(AAP_HOTFIX_EMAIL) . '">Email us</a><a href="/contact/">Use the Contact Form</a>' . aap_hotfix_social_links_markup('aap-social-links aap-social-links-footer', true) . '</div>'
        . '<div class="aap-live-footer-links"><h3>Core Services</h3><a href="/mental-health-billing/">Mental Health Billing</a><a href="/medical-billing/">Medical Billing</a><a href="/credentialing/">Credentialing</a><a href="/revenue-cycle-management/">Revenue Cycle Management</a><a href="/practice-operations/">Practice Operations</a></div>'
        . '<div class="aap-live-footer-links"><h3>Explore</h3><a href="/ehr-workflow-optimization/">Current Systems / EHR Optimization</a><a href="/workflow-friction-audit/">Workflow Friction Audit</a><a href="/resources/">Resources</a><a href="/about/">About</a></div>'
        . '</div><div class="aap-live-footer-bottom"><p>&copy; 2026 AdvanceAPractice. Behavioral health and outpatient practice support across billing, credentialing, revenue cycle management, documentation workflow, and current-system performance.</p></div></div></footer>';
}

function aap_hotfix_site_shell_css() {
    return <<<'CSS'
        html,body{margin:0;padding:0;background:#f4f8fc;color:#1f2428;font-family:"Segoe UI Variable","Segoe UI","Helvetica Neue",Arial,sans-serif}
*{box-sizing:border-box}
a{color:#1a56db}
a:hover{color:#1648c0}
[hidden]{display:none !important}
.honeypot,.aap-hp-honeypot{display:none !important}
.sr-only{position:absolute !important;width:1px !important;height:1px !important;padding:0 !important;margin:-1px !important;overflow:hidden !important;clip:rect(0,0,0,0) !important;white-space:nowrap !important;border:0 !important}
.aap-global-wrap{width:min(1340px,calc(100% - 28px));margin:0 auto;position:relative}
.aap-global-header{position:sticky;top:0;z-index:1000;background:linear-gradient(180deg,rgba(255,255,255,.97) 0%,rgba(245,249,255,.95) 100%);border-bottom:1px solid rgba(26,86,219,.12);box-shadow:0 14px 30px rgba(15,23,42,.06)}
.aap-global-header-bar{display:grid;grid-template-columns:minmax(260px,auto) minmax(0,1fr) auto;align-items:center;gap:14px;padding:10px 0;min-height:76px}
.aap-global-brand{display:flex;align-items:center;gap:14px;min-width:0;text-decoration:none;padding:0;border-radius:0;background:transparent;border:0;box-shadow:none}
.aap-global-brand img{display:block;height:58px;width:auto;max-width:300px;object-fit:contain}
.aap-global-brand-copy{display:grid;gap:2px;min-width:0}
.aap-global-brand-copy strong{display:block;color:#171b20;font-size:15px;line-height:1.02;letter-spacing:-.02em}
.aap-global-brand-copy em{display:block;color:#61676c;font-size:10px;font-style:normal;font-weight:700;letter-spacing:.12em;text-transform:uppercase;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace}
.aap-global-nav{display:flex;flex-wrap:wrap;justify-content:flex-end;gap:4px 6px;align-items:center;align-content:center;max-width:900px}
.aap-global-nav a,.aap-mobile-nav-panel a{color:#333a40;text-decoration:none;font-weight:700}
.aap-global-nav a{padding:8px 10px;border-radius:8px;font-size:11px;letter-spacing:.01em;transition:background-color .2s ease,color .2s ease}
.aap-global-nav a:hover,.aap-global-nav a.current{background:rgba(26,86,219,.10);color:#153967 !important}
.aap-global-actions{display:flex;align-items:center;gap:12px;justify-content:flex-end}
.aap-social-links{display:flex;flex-wrap:wrap;align-items:center;gap:10px}
.aap-social-links a{display:inline-flex;align-items:center;justify-content:center;gap:8px;text-decoration:none;color:#24313b}
.aap-social-icon{display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:999px;border:1px solid rgba(23,27,32,.10);background:#fff}
.aap-social-icon svg{width:18px;height:18px;display:block}
.aap-social-links-footer,.aap-social-links-inline,.aap-social-links-mobile{margin-top:14px}
.aap-social-links-footer a,.aap-social-links-inline a,.aap-social-links-mobile a{justify-content:flex-start}
.aap-social-links-inline .aap-social-icon,.aap-social-links-mobile .aap-social-icon{background:#fff;border-color:rgba(23,27,32,.10);color:#24313b}
.aap-social-links-footer .aap-social-icon{background:rgba(255,255,255,.05);border-color:rgba(255,255,255,.14);color:#fff}
.aap-live-footer .aap-social-links a{color:#fff}
.aap-social-label{font-size:14px;font-weight:700}
.aap-global-cta,.aap-footer-btn,.aap-mobile-nav-cta{display:inline-flex;align-items:center;justify-content:center;min-height:44px;padding:0 15px;border-radius:14px;background:linear-gradient(135deg,#1d4ed8,#38bdf8);color:#fff !important;text-decoration:none;font-weight:800;letter-spacing:.01em;box-shadow:0 18px 38px rgba(29,78,216,.28);transition:transform .22s ease,box-shadow .22s ease}
.aap-global-cta:hover,.aap-footer-btn:hover,.aap-mobile-nav-cta:hover{transform:translateY(-2px);box-shadow:0 22px 42px rgba(29,78,216,.34)}
.aap-menu-toggle{display:none;min-height:44px;padding:0 6px 0 12px;border:0;background:transparent;color:#171b20;font-weight:800;letter-spacing:.08em;text-transform:uppercase;font-size:12px;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;box-shadow:none;cursor:pointer;align-items:center;justify-content:center;line-height:1;text-align:center}
.aap-mobile-nav-panel{display:grid;gap:8px;position:absolute;top:100%;left:0;right:0;width:auto;margin:0;padding:10px 0 40px;border:0;border-top:1px solid rgba(23,27,32,.08);border-radius:0 0 18px 18px;background:#ffffff;box-shadow:0 18px 32px rgba(17,20,24,.08);max-height:calc(100vh - 92px);min-height:calc(100vh - 92px);overflow:auto;overscroll-behavior:contain;align-content:start}
.aap-mobile-nav-intro{margin:0;padding:4px 14px 4px;color:#5d6871;font-size:12px;font-weight:700;letter-spacing:.06em;text-transform:uppercase}
.aap-mobile-nav-panel a{display:flex;align-items:center;min-height:46px;padding:10px 14px;border-radius:0;background:transparent;color:#2b3238;transition:background-color .2s ease,color .2s ease}
.aap-mobile-nav-panel a:hover,.aap-mobile-nav-panel a.current{background:rgba(23,27,32,.06);color:#171b20}
.aap-mobile-nav-cta{margin-top:6px;background:linear-gradient(135deg,#1d4ed8,#38bdf8) !important}
.aap-live-main{min-height:60vh}
.aap-live-footer{position:relative;overflow:hidden;background:linear-gradient(180deg,#0c1627 0%,#11233c 100%);color:#d8d2ca;padding:44px 0 20px;border-top:1px solid rgba(147,197,253,.08)}
.aap-live-footer-ridge{margin:0 0 22px;position:relative;z-index:1}
.aap-live-footer-ridge svg{display:block;width:100%;height:auto}
.aap-live-footer-grid{display:grid;grid-template-columns:minmax(0,1.35fr) minmax(220px,.95fr) minmax(0,.9fr) minmax(0,.9fr);gap:28px}
.aap-live-footer-contact .aap-social-links{display:grid;gap:10px;margin-top:12px}
.aap-live-footer-contact .aap-social-links a{justify-content:flex-start}
.aap-footer-contact-link{word-break:break-word}
.aap-live-footer h3{margin:0 0 14px;color:#fff;font-family:"Iowan Old Style","Palatino Linotype","Book Antiqua",Georgia,serif;font-size:1.22rem;letter-spacing:-.02em}
.aap-live-footer p,.aap-live-footer li,.aap-live-footer a{color:#d8d2ca;line-height:1.8}
.aap-live-footer ul{list-style:none;margin:0;padding:0}
.aap-live-footer li{margin:0 0 8px}
.aap-live-footer-bottom{margin-top:24px;padding-top:18px;border-top:1px solid rgba(255,255,255,.10)}
@media (max-width:1220px){
  .aap-global-wrap{width:min(100%,calc(100% - 22px))}
  .aap-global-header-bar{gap:12px}
  .aap-global-brand img{height:52px;max-width:270px}
  .aap-global-brand-copy strong{font-size:14px}
  .aap-global-nav a{padding:9px 10px;font-size:11px}
}
@media (max-width:1460px){
  .aap-global-nav{display:none}
  .aap-menu-toggle{display:inline-flex}
}
@media (max-width:1120px){
  .aap-global-header-bar{grid-template-columns:auto auto}
  .aap-global-cta{display:none}
  .aap-live-footer-grid{grid-template-columns:1fr 1fr}
}
@media (max-width:767px){
  .aap-global-wrap{width:min(100%,calc(100% - 18px))}
  .aap-global-header-bar{grid-template-columns:minmax(0,1fr) auto;min-height:70px;padding:12px 0}
  .aap-global-brand-copy em{display:none}
  .aap-global-brand img{height:46px;max-width:230px}
  .aap-global-brand{max-width:none}
  .aap-mobile-nav-panel{top:100%;left:0;right:0;z-index:1105;max-height:calc(100vh - 90px)}
  .aap-live-footer-grid{grid-template-columns:1fr}
}
CSS;
}

function aap_hotfix_site_polish_css() {
    return <<<'CSS'
@keyframes aapFadeUp{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:none}}
 .aap-live-main > div[class^="aap-"]{--aap-navy:#171b20;--aap-blue:#2f3940;--aap-teal:#52675d;--aap-sky:#eef5fb;--aap-warm:#b77a30;--aap-mint:#edf0e9;--aap-ink:#1f2428;--aap-text:#1f2428;--aap-slate:#626b70;--aap-line:rgba(23,27,32,.11);--aap-shadow:0 22px 50px rgba(17,20,24,.08);--aap-shadow-sm:0 14px 30px rgba(17,20,24,.06);--aap-shadow-lg:0 30px 74px rgba(17,20,24,.14);background:linear-gradient(180deg,#fbfdff 0%,#f6f9fd 100%) !important}
.aap-live-main .aap-card,.aap-live-main .aap-proof-card,.aap-live-main .aap-panel,.aap-live-main .aap-form-card,.aap-live-main .aap-visual,.aap-live-main .aap-home-lead-shell,.aap-live-main .aap-systems-shell,.aap-live-main .aap-reference-shell,.aap-live-main .aap-service-card,.aap-live-main .aap-ops-card,.aap-live-main .aap-fit-card,.aap-live-main .aap-practical-ai-card,.aap-live-main .aap-quote,.aap-live-main .aap-resource-card,.aap-live-main .aap-bottom-panel{animation:aapFadeUp .45s ease both}
.aap-live-main .aap-grid-2,.aap-live-main .aap-grid-3,.aap-live-main .aap-proof,.aap-live-main .aap-home-lead-shell{align-items:start}
.aap-live-main form input,.aap-live-main form select,.aap-live-main form textarea{transition:border-color .2s ease,box-shadow .2s ease,transform .2s ease}
.aap-newsletter-popup-root,.aap-newsletter-popup-overlay,#aap-newsletter-popup-root,#aap-offer-dialog{display:none !important;visibility:hidden !important;pointer-events:none !important}
html.aap-popup-open,body.aap-popup-open{overflow:auto !important}
.aap-live-main form input:focus,.aap-live-main form select:focus,.aap-live-main form textarea:focus{outline:none;border-color:rgba(183,122,48,.42) !important;box-shadow:0 0 0 4px rgba(183,122,48,.12)}
.aap-live-main .aap-btn,.aap-live-main button,.aap-live-main input[type="submit"]{transition:transform .22s ease,box-shadow .22s ease}
.aap-live-main .aap-btn:hover,.aap-live-main button:hover,.aap-live-main input[type="submit"]:hover{transform:translateY(-2px)}
.aap-live-main > div[class^="aap-"] h1,.aap-live-main > div[class^="aap-"] h2,.aap-live-main > div[class^="aap-"] h3{color:#171b20 !important}
.aap-live-main > div[class^="aap-"] p,.aap-live-main > div[class^="aap-"] li{color:#626b70 !important}
.aap-live-main > div[class^="aap-"] .aap-kicker,.aap-live-main > div[class^="aap-"] .aap-micro,.aap-live-main .aap-floating-kicker,.aap-live-main .aap-home-lead-kicker,.aap-live-main .aap-reference-kicker{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;background:rgba(183,122,48,.08) !important;color:#8a5921 !important;border:1px solid rgba(183,122,48,.20) !important;letter-spacing:.11em}
.aap-live-main > div[class^="aap-"] .aap-btn.primary,.aap-live-main .aap-home-form-submit{background:linear-gradient(135deg,#a76c2a,#ca9447) !important;color:#fff !important;box-shadow:0 14px 30px rgba(124,84,31,.16) !important}
.aap-live-main > div[class^="aap-"] .aap-btn.secondary,.aap-live-main .aap-home-form-secondary{background:rgba(255,250,244,.92) !important;color:#171b20 !important;border:1px solid rgba(23,27,32,.10) !important;box-shadow:0 10px 22px rgba(17,20,24,.06) !important}
.aap-live-main > div[class^="aap-"] .aap-wrap{width:min(1220px,calc(100% - 28px)) !important}
.aap-home-lead-card,.aap-live-main .aap-form-card{align-self:start}
.aap-live-main .aap-home-form-grid,.aap-live-main .aap-form-grid{align-items:start}
.aap-live-main .aap-home-form-grid label,.aap-live-main .aap-form-grid label{display:grid;gap:6px}
.aap-live-main .aap-home-form-grid span,.aap-live-main .aap-form-grid label{font-weight:700}
.aap-live-main .aap-home-form-grid span{color:#171b20;font-size:14px}
.aap-live-main .aap-actions{align-items:center}
.aap-live-main .aap-visual,.aap-live-main .aap-hero-visual{align-self:start}
.aap-live-main .aap-visual img{display:block;width:100%;height:auto;min-height:0 !important;object-fit:cover}
.aap-live-main details{overflow:hidden}
.aap-home-lead-shell{width:min(1220px,calc(100% - 28px));margin:28px auto 0;display:grid;grid-template-columns:minmax(0,1.02fr) minmax(320px,.98fr);gap:24px;align-items:start}
.aap-home-lead-copy,.aap-home-lead-card{padding:28px;border-radius:26px;border:1px solid rgba(23,27,32,.10);background:rgba(255,251,246,.94);box-shadow:0 20px 50px rgba(17,20,24,.08)}
.aap-home-lead-copy h2{margin:12px 0 14px;color:#171b20;font-family:"Iowan Old Style","Palatino Linotype","Book Antiqua",Georgia,serif;font-size:clamp(1.9rem,3vw,2.65rem);line-height:1.08;letter-spacing:-.03em}
.aap-home-lead-copy p,.aap-home-lead-copy li{color:#626b70;font-size:16px;line-height:1.8}
.aap-home-lead-copy ul{margin:14px 0 0 18px;padding:0}
.aap-home-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.aap-home-form-wide{grid-column:1/-1}
.aap-hp-field{width:100%;min-height:50px;padding:14px;border-radius:14px;border:1px solid rgba(23,27,32,.14);background:#fffdf8;color:#22384b;-webkit-text-fill-color:#22384b;font:inherit}
.aap-hp-textarea{min-height:128px;resize:vertical}
.aap-hp-field::placeholder{color:#6b726f;opacity:1}
.aap-home-form-actions{display:flex;flex-wrap:wrap;gap:12px;margin-top:18px}
.aap-home-form-submit,.aap-home-form-secondary{display:inline-flex;align-items:center;justify-content:center;min-height:50px;padding:0 18px;border:none;border-radius:14px;text-decoration:none;font-weight:800;cursor:pointer}
.aap-hp-honeypot{position:absolute !important;left:-9999px !important;opacity:0 !important;pointer-events:none !important}
.aap-systems-strip{padding:26px 0 0}
 .aap-systems-shell{width:min(1220px,calc(100% - 28px));margin:0 auto;padding:20px 22px;border-radius:24px;border:1px solid rgba(23,27,32,.10);background:linear-gradient(180deg,#ffffff 0%,#eef5fb 100%);box-shadow:0 18px 44px rgba(17,20,24,.08)}
.aap-systems-shell h3{margin:0 0 12px;color:#171b20;font-family:"Iowan Old Style","Palatino Linotype","Book Antiqua",Georgia,serif;font-size:1.2rem}
.aap-systems-shell p{margin:0 0 12px;color:#626b70;font-size:15px;line-height:1.75}
.aap-systems-logos{display:flex;flex-wrap:wrap;gap:10px}
.aap-systems-logos span{display:inline-flex;align-items:center;min-height:34px;padding:0 12px;border-radius:999px;background:#f4efe7;border:1px solid rgba(23,27,32,.07);color:#273139;font-size:13px;font-weight:700}
.aap-reference-strip{padding:28px 0 0}
.aap-reference-shell{width:min(1220px,calc(100% - 28px));margin:0 auto;padding:24px;border-radius:24px;border:1px solid rgba(23,27,32,.10);background:linear-gradient(180deg,#fffdf8 0%,#f4efe7 100%);box-shadow:0 18px 44px rgba(17,20,24,.08)}
.aap-reference-shell h2{margin:12px 0 12px;color:#171b20;font-family:"Iowan Old Style","Palatino Linotype","Book Antiqua",Georgia,serif;font-size:clamp(1.8rem,3vw,2.5rem);line-height:1.08;letter-spacing:-.03em}
.aap-reference-shell p{margin:0 0 16px;color:#626b70;font-size:16px;line-height:1.8}
.aap-reference-links{display:grid;grid-template-columns:repeat(3,1fr);gap:14px}
.aap-reference-links a{display:block;padding:18px;border-radius:18px;border:1px solid rgba(23,27,32,.08);background:#fffdf8;color:#273139;font-weight:800;text-decoration:none;box-shadow:0 12px 28px rgba(17,20,24,.06)}
.aap-reference-links a span{display:block;margin-top:8px;color:#626b70;font-size:14px;font-weight:600;line-height:1.65}
.aap-reference-links a:hover{transform:translateY(-2px)}
 .aap-live-main > div[class^="aap-"] .aap-sea{background:linear-gradient(180deg,#fbfdff 0%,#eef5fb 100%) !important}
 .aap-live-main > div[class^="aap-"] .aap-sand{background:linear-gradient(180deg,#ffffff 0%,#f7fafc 100%) !important}
 .aap-live-main > div[class^="aap-"] .aap-forest{background:linear-gradient(180deg,#f8fbf7 0%,#eff5eb 100%) !important}
.aap-live-main > div[class^="aap-"] .aap-card,.aap-live-main > div[class^="aap-"] .aap-proof-card,.aap-live-main > div[class^="aap-"] .aap-quote,.aap-live-main > div[class^="aap-"] .aap-resource-card,.aap-live-main > div[class^="aap-"] .aap-service-card,.aap-live-main > div[class^="aap-"] .aap-ops-card,.aap-live-main > div[class^="aap-"] .aap-fit-card,.aap-live-main > div[class^="aap-"] .aap-practical-ai-card,.aap-live-main > div[class^="aap-"] .aap-bottom-panel,.aap-live-main > div[class^="aap-"] .aap-hero-shell{background:rgba(255,251,246,.94) !important;border-color:rgba(23,27,32,.10) !important;box-shadow:0 18px 42px rgba(17,20,24,.08) !important}
.aap-live-main > div[class^="aap-"] .aap-panel{background:linear-gradient(145deg,#171b20,#2b353d) !important}
.aap-live-main > div[class^="aap-"] .aap-panel h3,.aap-live-main > div[class^="aap-"] .aap-panel p,.aap-live-main > div[class^="aap-"] .aap-panel li,.aap-live-main > div[class^="aap-"] .aap-panel a{color:#f2ede6 !important}
.aap-live-main > div[class^="aap-"] .aap-panel-inner{background:linear-gradient(180deg,rgba(255,255,255,.08) 0%,rgba(255,255,255,.04) 100%) !important;border-color:rgba(255,255,255,.10) !important}
.aap-live-main > div[class^="aap-"] .aap-visual img,.aap-live-main .aap-hero-photo{background:linear-gradient(180deg,#ebe7de 0%,#f7f2ea 100%)}
.aap-live-main .aap-home-v3 .aap-hero-photo{object-fit:contain;padding:18px}
.aap-live-main .aap-home-v3 .aap-floating-panel{display:none !important}
.aap-live-main .aap-home-v3 .aap-floating-panel{background:rgba(255,250,244,.96);backdrop-filter:blur(10px);border-color:rgba(23,27,32,.08);box-shadow:0 22px 48px rgba(17,20,24,.16)}
 .aap-live-main .aap-home-v3 .aap-mini-stat{background:#eef4fa}
 .aap-live-main .aap-home-v3 .aap-flow-shell{padding:28px;border-radius:28px;border:1px solid rgba(23,27,32,.10);background:linear-gradient(180deg,#ffffff 0%,#f5f8fb 100%);box-shadow:0 22px 50px rgba(17,20,24,.08)}
.aap-live-main .aap-home-v3 .aap-flow-track{display:grid;grid-template-columns:repeat(5,1fr);gap:14px;margin-top:22px}
.aap-live-main .aap-home-v3 .aap-step{position:relative;padding:18px 18px 16px;border-radius:20px;border:1px solid rgba(23,27,32,.08);background:rgba(255,255,255,.82);box-shadow:0 12px 28px rgba(17,20,24,.06)}
.aap-live-main .aap-home-v3 .aap-step::after{content:"";position:absolute;right:-10px;top:50%;width:20px;height:1px;background:linear-gradient(90deg,rgba(167,108,42,.50),rgba(167,108,42,0));transform:translateY(-50%)}
.aap-live-main .aap-home-v3 .aap-step:last-child::after{display:none}
.aap-live-main .aap-home-v3 .aap-step span{display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:999px;background:#171b20;color:#fff;font-size:12px;font-weight:800;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;margin-bottom:12px}
.aap-live-main .aap-home-v3 .aap-step h3{margin:0 0 8px;font-size:1.04rem;letter-spacing:-.02em}
body.aap-hotfix .aap-live-main .hero,
body.aap-hotfix .aap-live-main .page-intro{
  padding:5rem 0 4.5rem
}
body.aap-hotfix .aap-live-main .section{
  padding:5.25rem 0
}
body.aap-hotfix .aap-live-main .container{
  width:min(1220px,calc(100% - 28px));
  margin:0 auto
}
body.aap-hotfix .aap-live-main .section-tight{
  padding:4rem 0
}
body.aap-hotfix .aap-live-main .section-sky{
  background:linear-gradient(180deg,#fbfdff 0%,#eef5fb 100%) !important
}
body.aap-hotfix .aap-live-main .section-sand{
  background:linear-gradient(180deg,#fffdfb 0%,#fcf4ec 100%) !important
}
body.aap-hotfix .aap-live-main .section-forest{
  background:linear-gradient(180deg,#f8fbf7 0%,#eff5eb 100%) !important
}
body.aap-hotfix .aap-live-main .section-sea{
  background:linear-gradient(180deg,#f8fcfb 0%,#edf8f6 100%) !important
}
body.aap-hotfix .aap-live-main .hero-grid,
body.aap-hotfix .aap-live-main .split-layout,
body.aap-hotfix .aap-live-main .contact-grid{
  display:grid;
  gap:1.75rem;
  align-items:start
}
body.aap-hotfix .aap-live-main .hero-grid{
  grid-template-columns:minmax(0,1.1fr) minmax(320px,.9fr)
}
body.aap-hotfix .aap-live-main .split-layout{
  grid-template-columns:minmax(0,1fr) minmax(280px,.9fr)
}
body.aap-hotfix .aap-live-main .contact-grid{
  grid-template-columns:minmax(0,1fr) minmax(320px,.95fr)
}
body.aap-hotfix .aap-live-main .grid-2,
body.aap-hotfix .aap-live-main .grid-3,
body.aap-hotfix .aap-live-main .grid-4,
body.aap-hotfix .aap-live-main .article-grid,
body.aap-hotfix .aap-live-main .callout-row,
body.aap-hotfix .aap-live-main .process-grid,
body.aap-hotfix .aap-live-main .proof-strip,
body.aap-hotfix .aap-live-main .trust-strip{
  display:grid;
  gap:1.5rem
}
body.aap-hotfix .aap-live-main .grid-4 > *,
body.aap-hotfix .aap-live-main .article-grid > *,
body.aap-hotfix .aap-live-main .callout-row > *{
  min-width:0
}
body.aap-hotfix .aap-live-main .grid-2{grid-template-columns:repeat(2,minmax(0,1fr))}
body.aap-hotfix .aap-live-main .grid-3,
body.aap-hotfix .aap-live-main .article-grid,
body.aap-hotfix .aap-live-main .callout-row,
body.aap-hotfix .aap-live-main .process-grid{
  grid-template-columns:repeat(3,minmax(0,1fr))
}
body.aap-hotfix .aap-live-main .grid-4,
body.aap-hotfix .aap-live-main .proof-strip,
body.aap-hotfix .aap-live-main .trust-strip{
  grid-template-columns:repeat(4,minmax(0,1fr))
}
body.aap-hotfix .aap-live-main .card,
body.aap-hotfix .aap-live-main .feature-card,
body.aap-hotfix .aap-live-main .resource-card,
body.aap-hotfix .aap-live-main .contact-card,
body.aap-hotfix .aap-live-main .process-step,
body.aap-hotfix .aap-live-main .proof-card,
body.aap-hotfix .aap-live-main .stat-card,
body.aap-hotfix .aap-live-main .aside-panel,
body.aap-hotfix .aap-live-main .quote-card,
body.aap-hotfix .aap-live-main .hero-panel,
body.aap-hotfix .aap-live-main .form-card,
body.aap-hotfix .aap-live-main .cta-panel,
body.aap-hotfix .aap-live-main .image-card,
body.aap-hotfix .aap-live-main .service-card{
  background:rgba(255,255,255,.96) !important;
  border:1px solid rgba(17,43,69,.1) !important;
  border-radius:22px !important;
  box-shadow:0 12px 30px rgba(17,43,69,.06) !important;
  padding:1.75rem
}
body.aap-hotfix .aap-live-main .form-card,
body.aap-hotfix .aap-live-main .cta-panel,
body.aap-hotfix .aap-live-main .image-card{
  padding:2rem
}
body.aap-hotfix .aap-live-main .hero-panel{
  background:radial-gradient(circle at top right,rgba(31,95,168,.18),transparent 28%),radial-gradient(circle at bottom left,rgba(207,135,84,.16),transparent 22%),linear-gradient(145deg,#12355b,#1d588f) !important;
  color:#eff5ff !important;
  box-shadow:0 28px 72px rgba(17,43,69,.14) !important;
  overflow:hidden
}
body.aap-hotfix .aap-live-main .hero-panel h2,
body.aap-hotfix .aap-live-main .hero-panel h3,
body.aap-hotfix .aap-live-main .hero-panel strong{color:#fff !important}
body.aap-hotfix .aap-live-main .hero-panel p,
body.aap-hotfix .aap-live-main .hero-panel li{color:rgba(239,245,255,.88) !important}
body.aap-hotfix .aap-live-main .section-heading{
  max-width:780px;
  margin-bottom:2rem
}
body.aap-hotfix .aap-live-main .section-heading h1,
body.aap-hotfix .aap-live-main .section-heading h2,
body.aap-hotfix .aap-live-main .section-heading h3,
body.aap-hotfix .aap-live-main .hero-copy h1{
  margin:.85rem 0 1rem;
  font-family:"Iowan Old Style","Palatino Linotype","Book Antiqua",Georgia,serif;
  line-height:1.06;
  letter-spacing:-.03em;
  color:#171b20
}
body.aap-hotfix .aap-live-main .hero-copy h1,
body.aap-hotfix .aap-live-main .section-heading h1{font-size:clamp(2.85rem,6vw,5.2rem)}
body.aap-hotfix .aap-live-main .section-heading h2{font-size:clamp(2.05rem,4vw,3.3rem)}
body.aap-hotfix .aap-live-main .section-heading h3{font-size:clamp(1.4rem,2vw,1.8rem)}
body.aap-hotfix .aap-live-main .section-heading p,
body.aap-hotfix .aap-live-main .lede,
body.aap-hotfix .aap-live-main .text-lg{max-width:72ch;color:#607486;font-size:1.08rem}
body.aap-hotfix .aap-live-main .text-limit{max-width:68ch}
body.aap-hotfix .aap-live-main .lede{font-size:1.12rem}
body.aap-hotfix .aap-live-main .check-list,
body.aap-hotfix .aap-live-main .detail-list{
  margin:1rem 0 0;
  padding:0;
  list-style:none
}
body.aap-hotfix .aap-live-main .check-list li,
body.aap-hotfix .aap-live-main .detail-list li{
  position:relative;
  margin:0 0 .95rem;
  padding-left:1.6rem
}
body.aap-hotfix .aap-live-main .check-list li::before,
body.aap-hotfix .aap-live-main .detail-list li::before{
  content:"";
  position:absolute;
  left:0;
  top:.7rem;
  width:.8rem;
  height:.8rem;
  border-radius:50%;
  background:linear-gradient(135deg,#1f5fa8,#197d89);
  transform:translateY(-50%);
  box-shadow:0 0 0 5px rgba(31,95,168,.1)
}
body.aap-hotfix .aap-live-main .bullet-list{
  display:grid;
  gap:.8rem;
  margin:1rem 0 0;
  padding-left:1.2rem
}
body.aap-hotfix .aap-live-main .hero-actions,
body.aap-hotfix .aap-live-main .link-list,
body.aap-hotfix .aap-live-main .pill-row,
body.aap-hotfix .aap-live-main .service-pills,
body.aap-hotfix .aap-live-main .meta-list{
  display:flex;
  flex-wrap:wrap;
  gap:.75rem
}
body.aap-hotfix .aap-live-main .link-list a,
body.aap-hotfix .aap-live-main .pill-row span,
body.aap-hotfix .aap-live-main .service-pills span,
body.aap-hotfix .aap-live-main .meta-list li{
  display:inline-flex;
  align-items:center;
  min-height:42px;
  padding:0 1rem;
  border-radius:999px;
  background:rgba(255,255,255,.88);
  border:1px solid rgba(17,43,69,.1);
  color:#123f6f;
  font-weight:700
}
body.aap-hotfix .aap-live-main .link-list a:hover{
  transform:translateY(-2px);
  background:#fff
}
body.aap-hotfix .aap-live-main .trust-strip,
body.aap-hotfix .aap-live-main .strip{
  display:grid;
  grid-template-columns:repeat(5,minmax(0,1fr));
  gap:1rem
}
body.aap-hotfix .aap-live-main .strip-card{
  padding:1.25rem 1.1rem;
  border-radius:16px;
  border:1px solid rgba(17,43,69,.1);
  background:rgba(255,255,255,.72);
  box-shadow:0 12px 30px rgba(17,43,69,.06)
}
body.aap-hotfix .aap-live-main .strip-card strong{
  display:block;
  margin-bottom:.45rem;
  color:#171b20
}
body.aap-hotfix .aap-live-main .form-grid{
  display:grid;
  grid-template-columns:repeat(2,minmax(0,1fr));
  gap:14px
}
body.aap-hotfix .aap-live-main .field{
  display:grid;
  gap:7px
}
body.aap-hotfix .aap-live-main .field-full{
  grid-column:1 / -1
}
body.aap-hotfix .aap-live-main .form-grid label{
  color:#17344f;
  font-size:13px;
  font-weight:700
}
body.aap-hotfix .aap-live-main .lead-form input,
body.aap-hotfix .aap-live-main .lead-form select,
body.aap-hotfix .aap-live-main .lead-form textarea,
body.aap-hotfix .aap-live-main .form-grid input,
body.aap-hotfix .aap-live-main .form-grid select,
body.aap-hotfix .aap-live-main .form-grid textarea{
  width:100%;
  min-height:50px;
  padding:14px;
  border-radius:14px;
  border:1px solid rgba(23,27,32,.14);
  background:#fffdf8;
  color:#22384b
}
body.aap-hotfix .aap-live-main .lead-form textarea,
body.aap-hotfix .aap-live-main .form-grid textarea{
  min-height:128px;
  resize:vertical
}
body.aap-hotfix .aap-live-main .form-note,
body.aap-hotfix .aap-live-main .resource-meta{
  color:#8a5921;
  font-size:.8rem;
  font-weight:700;
  letter-spacing:.07em;
  text-transform:uppercase
}
body.aap-hotfix .aap-live-main .faq-list{
  display:grid;
  gap:12px
}
body.aap-hotfix .aap-live-main .faq-item{
  padding:18px 20px;
  border:1px solid rgba(17,43,69,.1);
  border-radius:18px;
  background:rgba(255,255,255,.95);
  box-shadow:0 12px 30px rgba(17,43,69,.06)
}
body.aap-hotfix .aap-live-main .faq-item summary{
  cursor:pointer;
  font-weight:800;
  color:#102841
}
body.aap-hotfix .aap-live-main .faq-content{
  padding-top:12px;
  color:#607387
}
@media (max-width:1100px){
  body.aap-hotfix .aap-live-main .hero-grid,
  body.aap-hotfix .aap-live-main .split-layout,
  body.aap-hotfix .aap-live-main .contact-grid,
  body.aap-hotfix .aap-live-main .grid-2,
  body.aap-hotfix .aap-live-main .grid-3,
  body.aap-hotfix .aap-live-main .grid-4,
  body.aap-hotfix .aap-live-main .proof-strip,
  body.aap-hotfix .aap-live-main .trust-strip{
    grid-template-columns:1fr
  }
  body.aap-hotfix .aap-live-main .hero-copy h1,
  body.aap-hotfix .aap-live-main .section-heading h1{
    font-size:clamp(2.35rem,8vw,4rem)
  }
  body.aap-hotfix .aap-live-main .section-heading h2{
    font-size:clamp(1.9rem,5vw,2.7rem)
  }
}
@media (max-width:767px){
  body.aap-hotfix .aap-live-main .hero,
  body.aap-hotfix .aap-live-main .page-intro,
  body.aap-hotfix .aap-live-main .section{
    padding:4rem 0
  }
  body.aap-hotfix .aap-live-main .section-tight{
    padding:3rem 0
  }
  body.aap-hotfix .aap-live-main .form-grid{
    grid-template-columns:1fr
  }
  body.aap-hotfix .aap-live-main .hero-actions{
    display:grid;
    gap:12px
  }
  body.aap-hotfix .aap-live-main .hero-actions .button,
  body.aap-hotfix .aap-live-main .hero-actions .button-secondary,
  body.aap-hotfix .aap-live-main .hero-actions .button-ghost{
    width:100%
  }
}
body.aap-hotfix .contact-card p{line-height:1.8}
body.aap-hotfix .aap-live-main .contact-card{
  padding:1.5rem;
  border:1px solid rgba(17,43,69,.1);
  border-radius:18px;
  background:rgba(255,255,255,.9);
  box-shadow:0 12px 30px rgba(17,43,69,.06);
  overflow:hidden
}
body.aap-hotfix .aap-live-main .contact-card h3{
  margin:0 0 .6rem;
  color:#171b20;
  font-size:1.08rem;
  line-height:1.25;
  letter-spacing:-.02em
}
body.aap-hotfix .aap-live-main .contact-card a{font-weight:800}
body.aap-hotfix .aap-live-main .container{width:min(1220px,calc(100% - 40px));margin:0 auto}
body.aap-hotfix .aap-live-main .eyebrow{display:inline-block;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:11px;font-weight:700;letter-spacing:.11em;text-transform:uppercase;background:rgba(183,122,48,.08);color:#8a5921;padding:8px 14px;border-radius:999px;border:1px solid rgba(183,122,48,.2);margin-bottom:1rem}
body.aap-hotfix .aap-live-main .kicker{display:inline-block;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:11px;font-weight:700;letter-spacing:.11em;text-transform:uppercase;background:rgba(183,122,48,.08);color:#8a5921;padding:8px 14px;border-radius:999px;border:1px solid rgba(183,122,48,.2);margin-bottom:.75rem;display:block;width:fit-content}
body.aap-hotfix .aap-live-main .container{width:min(1220px,calc(100% - 40px));margin:0 auto}
body.aap-hotfix .aap-live-main .eyebrow{display:inline-block;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:11px;font-weight:700;letter-spacing:.11em;text-transform:uppercase;background:rgba(183,122,48,.08);color:#8a5921;padding:8px 14px;border-radius:999px;border:1px solid rgba(183,122,48,.2);margin-bottom:1rem;display:block;width:fit-content}
body.aap-hotfix .aap-live-main .button,body.aap-hotfix .aap-live-main input[type="submit"]{display:inline-flex;align-items:center;justify-content:center;min-height:50px;padding:0 24px;border:none;border-radius:14px;background:linear-gradient(135deg,#a76c2a,#ca9447);color:#fff !important;font-weight:800;font-size:15px;text-decoration:none;box-shadow:0 14px 30px rgba(124,84,31,.16);transition:transform .22s ease,box-shadow .22s ease;cursor:pointer}
body.aap-hotfix .aap-live-main .button:hover,body.aap-hotfix .aap-live-main input[type="submit"]:hover{transform:translateY(-2px);box-shadow:0 18px 36px rgba(124,84,31,.2)}
body.aap-hotfix .aap-live-main .button-secondary{display:inline-flex;align-items:center;justify-content:center;min-height:50px;padding:0 24px;border-radius:14px;background:rgba(255,250,244,.92);color:#171b20 !important;border:1px solid rgba(23,27,32,.1);font-weight:800;font-size:15px;text-decoration:none;box-shadow:0 10px 22px rgba(17,20,24,.06);transition:transform .22s ease,box-shadow .22s ease}
body.aap-hotfix .aap-live-main .button-secondary:hover{transform:translateY(-2px);box-shadow:0 14px 28px rgba(17,20,24,.1)}
body.aap-hotfix .aap-live-main .panel-intro{display:block;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:11px;font-weight:700;letter-spacing:.11em;text-transform:uppercase;margin-bottom:.5rem;opacity:.85}
body.aap-hotfix .aap-live-main .panel-figure{width:100%;margin:0 0 1.5rem;padding:18px;background:rgba(255,255,255,.05);border-radius:16px}
body.aap-hotfix .aap-live-main .panel-figure img{width:100%;height:auto;display:block;object-fit:contain;max-height:160px}
body.aap-hotfix .aap-live-main .visual-caption{margin-top:1rem;padding-top:1rem;border-top:1px solid rgba(255,255,255,.1)}
body.aap-hotfix .aap-live-main .visual-caption strong{display:block;color:#fff;font-size:1rem;margin-bottom:.5rem}
body.aap-hotfix .aap-live-main .visual-caption{color:rgba(239,245,255,.85);font-size:.95rem;line-height:1.7}
body.aap-hotfix .aap-live-main .card h3,body.aap-hotfix .aap-live-main .service-card h3,body.aap-hotfix .aap-live-main .proof-card h3{margin:0 0 .75rem;font-size:1.35rem;line-height:1.3;color:#171b20;font-family:"Iowan Old Style","Palatino Linotype","Book Antiqua",Georgia,serif}
body.aap-hotfix .aap-live-main .card p,body.aap-hotfix .aap-live-main .service-card p,body.aap-hotfix .aap-live-main .proof-card p{margin:0;color:#626b70;line-height:1.75;font-size:1rem}
body.aap-hotfix .aap-live-main .strip-card p{margin:.5rem 0 0;color:#626b70;line-height:1.65;font-size:.92rem}
body.aap-hotfix .aap-live-main .service-card ul{margin:.75rem 0 0;padding-left:1.1rem}
body.aap-hotfix .aap-live-main .service-card li{margin:0 0 .4rem;color:#626b70;font-size:.95rem;line-height:1.6}
body.aap-hotfix .aap-live-main .image-card figure{width:100%;margin:0 0 1rem;padding:18px;background:rgba(255,255,255,.5);border-radius:16px}
body.aap-hotfix .aap-live-main .image-card img{width:100%;height:auto;display:block;border-radius:12px;object-fit:cover;min-height:200px;background:#e8e4dc}
body.aap-hotfix .aap-live-main .hero-grid > div,body.aap-hotfix .aap-live-main .split-layout > div{position:relative}
body.aap-hotfix .aap-live-main .hero-grid > div:first-child,body.aap-hotfix .aap-live-main .split-layout > div:first-child{display:flex;flex-direction:column}
body.aap-hotfix .aap-live-main .hero-copy,body.aap-hotfix .aap-live-main .section-heading{width:100%;max-width:100%}
body.aap-hotfix .aap-live-main .hero-copy p,body.aap-hotfix .aap-live-main .section-heading p{color:#626b70}
body.aap-hotfix .aap-live-main .hero-actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:1.5rem}
body.aap-hotfix .aap-live-main .link-list{margin-top:1.25rem;gap:.5rem}
body.aap-hotfix .aap-live-main .link-list a{font-size:13px;padding:8px 14px}
body.aap-hotfix .aap-live-main .proof-card,body.aap-hotfix .aap-live-main .service-card{height:100%}
body.aap-hotfix .aap-live-main img[src*="404"],body.aap-hotfix .aap-live-main img[src=""],body.aap-hotfix .aap-live-main img:not([src]){display:none}
body.aap-hotfix .aap-live-main .panel-figure:empty,body.aap-hotfix .aap-live-main figure:empty{display:none}
body.aap-hotfix .aap-live-main .breadcrumbs{font-size:12px;margin-bottom:1rem;color:#626b70;display:flex;gap:8px;align-items:center}
body.aap-hotfix .aap-live-main .breadcrumbs a{color:#8a5921;text-decoration:none;font-weight:600}
body.aap-hotfix .aap-live-main .breadcrumbs a:hover{color:#a76c2a}
body.aap-hotfix .aap-live-main .breadcrumbs span{color:#a0a5a8}
body.aap-hotfix .aap-live-main .page-intro{padding:4rem 0 3.5rem}
body.aap-hotfix .aap-live-main .cta-panel{display:flex;flex-direction:column;align-items:center;text-align:center;padding:3rem;background:rgba(255,251,246,.94) !important;border-radius:24px;border:1px solid rgba(23,27,32,.1)}
body.aap-hotfix .aap-live-main .cta-panel .section-heading{max-width:700px;margin:0 auto 2rem}
body.aap-hotfix .aap-live-main .cta-panel .hero-actions{justify-content:center}
body.aap-hotfix .aap-live-main .aap-growth-path-strip{padding:4rem 0;background:linear-gradient(180deg,#ffffff 0%,#f7fafc 100%) !important}
body.aap-hotfix .aap-live-main .aap-growth-path-shell{padding:2rem;border-radius:24px;background:#fff;border:1px solid rgba(23,27,32,.1)}
body.aap-hotfix .aap-live-main .aap-growth-path-shell h2{font-family:"Iowan Old Style","Palatino Linotype","Book Antiqua",Georgia,serif;font-size:clamp(1.8rem,3vw,2.4rem);color:#171b20;margin:0 0 1rem}
body.aap-hotfix .aap-live-main .aap-growth-path-shell p{color:#626b70;font-size:1rem;line-height:1.7;margin:0 0 2rem}
body.aap-hotfix .aap-live-main .aap-growth-path-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:2rem}
body.aap-hotfix .aap-live-main .aap-growth-path-card{display:block;padding:1.5rem;border-radius:16px;background:#fff;border:1px solid rgba(23,27,32,.08);text-decoration:none;transition:transform .2s,box-shadow .2s}
body.aap-hotfix .aap-live-main .aap-growth-path-card:hover{transform:translateY(-2px);box-shadow:0 12px 30px rgba(17,20,24,.1)}
body.aap-hotfix .aap-live-main .aap-growth-path-card strong{display:block;color:#171b20;font-size:1rem;margin-bottom:.5rem}
body.aap-hotfix .aap-live-main .aap-growth-path-card span{display:block;color:#626b70;font-size:.9rem;line-height:1.55;margin-bottom:.7rem}
body.aap-hotfix .aap-live-main .aap-growth-path-card .aap-growth-path-meta{color:#1b4771;font-size:.83rem;font-weight:700;line-height:1.5}
body.aap-hotfix .aap-live-main .aap-growth-path-card em{color:#1f5fa8;font-size:.85rem;font-weight:700;font-style:normal}
body.aap-hotfix .aap-live-main .aap-growth-path-actions{display:flex;gap:1rem;justify-content:center}
body.aap-hotfix .aap-live-main .aap-growth-path-primary{display:inline-flex;align-items:center;min-height:50px;padding:0 24px;background:linear-gradient(135deg,#a76c2a,#ca9447);color:#fff;border-radius:14px;font-weight:800;text-decoration:none}
body.aap-hotfix .aap-live-main .aap-growth-path-secondary{display:inline-flex;align-items:center;min-height:50px;padding:0 24px;background:#fff;color:#171b20;border:1px solid rgba(23,27,32,.1);border-radius:14px;font-weight:800;text-decoration:none}
@media (max-width:767px){
body.aap-hotfix .aap-live-main .aap-growth-path-grid{grid-template-columns:1fr}
body.aap-hotfix .aap-live-main .aap-growth-path-actions{flex-direction:column;align-items:stretch}
body.aap-hotfix .aap-live-main .aap-growth-path-primary,
body.aap-hotfix .aap-live-main .aap-growth-path-secondary{width:100%;justify-content:center}
}
body.aap-hotfix .aap-live-main .lead-form{background:rgba(255,255,255,.6);padding:1.5rem;border-radius:18px;border:1px solid rgba(23,27,32,.08)}
body.aap-hotfix .aap-live-main .lead-form .form-grid{gap:16px}
body.aap-hotfix .aap-live-main .lead-form .field{display:grid;gap:8px}
body.aap-hotfix .aap-live-main .lead-form label{color:#171b20;font-weight:700;font-size:13px}
body.aap-hotfix .aap-live-main .lead-form input,body.aap-hotfix .aap-live-main .lead-form select,body.aap-hotfix .aap-live-main .lead-form textarea{background:#fff !important;color:#171b20 !important;border:1px solid rgba(23,27,32,.2) !important;border-radius:10px;padding:12px 14px;font-size:15px;min-height:48px}
body.aap-hotfix .aap-live-main .lead-form input::placeholder,body.aap-hotfix .aap-live-main .lead-form textarea::placeholder{color:#8a9196 !important}
body.aap-hotfix .aap-live-main .lead-form input:focus,body.aap-hotfix .aap-live-main .lead-form select:focus,body.aap-hotfix .aap-live-main .lead-form textarea:focus{border-color:#a76c2a !important;outline:none;box-shadow:0 0 0 3px rgba(167,108,42,.15)}
body.aap-hotfix .aap-live-main .lead-form select{appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23171b20' d='M6 8L1 3h10z'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 14px center;padding-right:36px}
body.aap-hotfix .aap-live-main .lead-form textarea{min-height:120px}
body.aap-hotfix .aap-live-main .lead-form .form-note{color:#626b70;font-size:12px;margin-top:1rem;line-height:1.5}
body.aap-hotfix .aap-live-main .lead-form button.button{
  width:100%;
  margin-top:1rem;
  min-height:52px;
  border:none;
  border-radius:14px;
  background:linear-gradient(135deg,#a76c2a,#ca9447);
  color:#fff;
  font-weight:800;
  letter-spacing:.01em;
  box-shadow:0 14px 30px rgba(124,84,31,.16)
}
body.aap-hotfix .aap-live-main .prompt-list{display:flex;flex-wrap:wrap;gap:10px;margin-top:10px}
body.aap-hotfix .aap-live-main .prompt-chip{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  min-height:40px;
  padding:0 14px;
  border-radius:999px;
  border:1px solid rgba(23,27,32,.12);
  background:#fffdf8;
  color:#17344f;
  font-size:13px;
  font-weight:800;
  letter-spacing:.01em;
  cursor:pointer;
  box-shadow:0 10px 22px rgba(17,20,24,.06)
}
body.aap-hotfix .aap-live-main .prompt-chip:hover,
body.aap-hotfix .aap-live-main .prompt-chip:focus-visible{
  background:#fff;
  transform:translateY(-1px)
}
body.aap-hotfix .aap-live-main .contact-grid .form-card{
  align-self:start;
  justify-self:stretch
}
body.aap-hotfix .aap-live-main .contact-grid .form-card .lead-form{
  margin-top:1rem
}
body.aap-hotfix .aap-live-main .lead-form .form-grid{
  display:grid;
  grid-template-columns:repeat(2,minmax(0,1fr));
  gap:14px
}
body.aap-hotfix .aap-live-main .lead-form .field,
body.aap-hotfix .aap-live-main .lead-form .field-full{
  display:grid;
  gap:8px
}
body.aap-hotfix .aap-live-main .lead-form .field-full{
  grid-column:1 / -1
}
body.aap-hotfix .aap-live-main .lead-form .field label,
body.aap-hotfix .aap-live-main .lead-form .field-full label{
  color:#171b20;
  font-weight:700;
  font-size:13px
}
@media (max-width:1100px){
  body.aap-hotfix .aap-live-main .contact-grid{
    grid-template-columns:1fr !important
  }
  body.aap-hotfix .aap-live-main .contact-grid .form-card{
    order:-1;
    margin-bottom:18px
  }
}
body.aap-hotfix .aap-live-main .honeypot{position:absolute;left:-9999px}
@media (max-width:1100px){
  body.aap-hotfix .aap-live-main .container{width:min(100%,calc(100% - 36px))}
}
@media (max-width:767px){
  body.aap-hotfix .aap-live-main .container{width:min(100%,calc(100% - 20px))}
  body.aap-hotfix .aap-live-main .eyebrow,body.aap-hotfix .aap-live-main .kicker{font-size:10px;padding:6px 11px}
  body.aap-hotfix .aap-live-main .hero-grid,body.aap-hotfix .aap-live-main .split-layout{grid-template-columns:1fr !important}
  body.aap-hotfix .aap-live-main .grid-2,body.aap-hotfix .aap-live-main .grid-3,body.aap-hotfix .aap-live-main .grid-4{grid-template-columns:1fr}
  body.aap-hotfix .aap-live-main .trust-strip,body.aap-hotfix .aap-live-main .strip{grid-template-columns:1fr}
  body.aap-hotfix .aap-live-main .lead-form .form-grid{grid-template-columns:1fr}
  body.aap-hotfix .aap-live-main .aap-home-lead-shell{width:min(100%,calc(100% - 20px));grid-template-columns:1fr}
  body.aap-hotfix .aap-live-main .aap-home-form-actions{flex-direction:column}
  body.aap-hotfix .aap-live-main .aap-home-form-submit,
  body.aap-hotfix .aap-live-main .aap-home-form-secondary{width:100%}
  body.aap-hotfix .aap-live-main .aap-growth-path-grid{grid-template-columns:1fr}
  body.aap-hotfix .aap-live-main .aap-growth-path-actions{flex-direction:column;align-items:stretch}
  body.aap-hotfix .aap-live-main .aap-growth-path-primary,
  body.aap-hotfix .aap-live-main .aap-growth-path-secondary{width:100%;justify-content:center}
}
@media (max-width:1100px){
  .aap-live-main > div[class^="aap-"] .aap-grid-2,
  .aap-live-main > div[class^="aap-"] .aap-grid-3,
  .aap-live-main > div[class^="aap-"] .aap-proof,
  .aap-live-main > div[class^="aap-"] .aap-ref-grid,
  .aap-live-main > div[class^="aap-"] .aap-service-grid,
  .aap-live-main > div[class^="aap-"] .aap-ops-grid{
    grid-template-columns:1fr !important;
  }
  .aap-home-lead-shell{grid-template-columns:1fr}
  .aap-reference-links{grid-template-columns:1fr}
  .aap-systems-logos{gap:8px}
  .aap-live-main .aap-home-v3 .aap-flow-track{grid-template-columns:1fr}
  .aap-live-main .aap-home-v3 .aap-step::after{display:none}
}
@media (max-width:767px){
  .aap-live-main > div[class^="aap-"] .aap-wrap{width:min(100%,calc(100% - 18px)) !important}
  .aap-home-lead-shell,.aap-systems-shell,.aap-reference-shell{width:min(100%,calc(100% - 18px))}
  .aap-home-lead-copy,.aap-home-lead-card{padding:22px}
  .aap-home-form-grid{grid-template-columns:1fr}
  .aap-home-form-actions{display:grid;grid-template-columns:1fr}
  .aap-home-form-submit,.aap-home-form-secondary{width:100%}
  .aap-live-main .aap-actions{display:grid !important;grid-template-columns:1fr;gap:12px}
  .aap-live-main .aap-actions .aap-btn{width:100%}
}
body.aap-hotfix{overflow-x:hidden}
body.aap-hotfix .aap-live-main,body.aap-hotfix .aap-live-main *{min-width:0}
body.aap-hotfix .aap-live-main .provider-pathways,
body.aap-hotfix .aap-live-main .vision-grid,
body.aap-hotfix .aap-live-main .systems-grid,
body.aap-hotfix .aap-live-main .homepage-proof-grid,
body.aap-hotfix .aap-live-main .homepage-cta-grid{display:grid;gap:1.35rem}
body.aap-hotfix .aap-live-main .provider-pathways{grid-template-columns:repeat(4,minmax(0,1fr))}
body.aap-hotfix .aap-live-main .homepage-proof-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
body.aap-hotfix .aap-live-main .homepage-cta-grid{grid-template-columns:minmax(0,1fr) minmax(320px,.95fr);align-items:start}
body.aap-hotfix .aap-live-main .provider-card,
body.aap-hotfix .aap-live-main .vision-card,
body.aap-hotfix .aap-live-main .systems-card,
body.aap-hotfix .aap-live-main .testimonial-card,
body.aap-hotfix .aap-live-main .homepage-final-card{height:100%;padding:1.6rem;border:1px solid rgba(17,43,69,.1);border-radius:22px;background:linear-gradient(180deg,rgba(255,255,255,.98),rgba(245,248,251,.94));box-shadow:0 14px 32px rgba(17,43,69,.07)}
body.aap-hotfix .aap-live-main .provider-card{display:flex;flex-direction:column;justify-content:flex-start}
body.aap-hotfix .aap-live-main .provider-card h3{margin:0 0 .75rem;color:#171b20}
body.aap-hotfix .aap-live-main .provider-card .provider-stage-meta{margin:0 0 .7rem;color:#1b4771}
body.aap-hotfix .aap-live-main .provider-card .provider-stage-meta strong{color:#1b4771;font-size:.92rem;line-height:1.55}
body.aap-hotfix .aap-live-main .provider-card:nth-child(1){border-top:4px solid #1f5fa8}
body.aap-hotfix .aap-live-main .provider-card:nth-child(2){border-top:4px solid #197d89}
body.aap-hotfix .aap-live-main .provider-card:nth-child(3){border-top:4px solid #ca9447}
body.aap-hotfix .aap-live-main .provider-card:nth-child(4){border-top:4px solid #52675d}
body.aap-hotfix .aap-live-main .vision-card,
body.aap-hotfix .aap-live-main .systems-card{padding-left:1.85rem}
body.aap-hotfix .aap-live-main .vision-card:nth-child(1),
body.aap-hotfix .aap-live-main .systems-card:nth-child(1){border-left:4px solid #1f5fa8}
body.aap-hotfix .aap-live-main .vision-card:nth-child(2),
body.aap-hotfix .aap-live-main .systems-card:nth-child(2){border-left:4px solid #197d89}
body.aap-hotfix .aap-live-main .vision-card:nth-child(3),
body.aap-hotfix .aap-live-main .systems-card:nth-child(3){border-left:4px solid #ca9447}
body.aap-hotfix .aap-live-main .systems-card:nth-child(4){border-left:4px solid #52675d}
body.aap-hotfix .aap-live-main .testimonial-card{display:flex;flex-direction:column;justify-content:space-between}
body.aap-hotfix .aap-live-main .testimonial-card blockquote{margin:0 0 1.1rem;padding-left:1rem;border-left:3px solid #1f5fa8;color:#171b20;font-family:"Iowan Old Style","Palatino Linotype","Book Antiqua",Georgia,serif;font-size:clamp(1.15rem,1.8vw,1.45rem);line-height:1.5}
body.aap-hotfix .aap-live-main .testimonial-attribution{display:grid;gap:.15rem;margin-top:auto}
body.aap-hotfix .aap-live-main .testimonial-attribution strong{color:#171b20}
body.aap-hotfix .aap-live-main .testimonial-attribution span{color:#626b70;font-size:.92rem}
body.aap-hotfix .aap-live-main .hero-actions > *{max-width:100%}
@media (max-width:1100px){
  body.aap-hotfix .aap-live-main .provider-pathways,
  body.aap-hotfix .aap-live-main .vision-grid,
  body.aap-hotfix .aap-live-main .systems-grid,
  body.aap-hotfix .aap-live-main .homepage-proof-grid,
  body.aap-hotfix .aap-live-main .homepage-cta-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
}
@media (max-width:767px){
  body.aap-hotfix .aap-live-main .provider-pathways,
  body.aap-hotfix .aap-live-main .vision-grid,
  body.aap-hotfix .aap-live-main .systems-grid,
  body.aap-hotfix .aap-live-main .homepage-proof-grid,
  body.aap-hotfix .aap-live-main .homepage-cta-grid{grid-template-columns:1fr}
  body.aap-hotfix .aap-live-main .provider-card,
  body.aap-hotfix .aap-live-main .vision-card,
  body.aap-hotfix .aap-live-main .systems-card,
  body.aap-hotfix .aap-live-main .testimonial-card,
  body.aap-hotfix .aap-live-main .homepage-final-card,
  body.aap-hotfix .aap-live-main .hero-panel,
  body.aap-hotfix .aap-live-main .form-card,
  body.aap-hotfix .aap-live-main .image-card{padding:1.35rem}
}
.aap-global-cta,.aap-footer-btn,.aap-mobile-nav-cta,
body.aap-hotfix .aap-live-main .button,
body.aap-hotfix .aap-live-main input[type="submit"],
body.aap-hotfix .aap-live-main .aap-growth-path-primary,
body.aap-hotfix .aap-live-main .lead-form button.button,
.aap-live-main > div[class^="aap-"] .aap-btn.primary,
.aap-live-main .aap-home-form-submit{
  background:linear-gradient(135deg,#1a56db 0%,#0ea5e9 100%) !important;
  color:#fff !important;
  box-shadow:0 16px 34px rgba(26,86,219,.24) !important
}
.aap-global-cta:hover,.aap-footer-btn:hover,.aap-mobile-nav-cta:hover,
body.aap-hotfix .aap-live-main .button:hover,
body.aap-hotfix .aap-live-main input[type="submit"]:hover,
body.aap-hotfix .aap-live-main .aap-growth-path-primary:hover,
body.aap-hotfix .aap-live-main .lead-form button.button:hover,
.aap-live-main > div[class^="aap-"] .aap-btn.primary:hover,
.aap-live-main .aap-home-form-submit:hover{
  box-shadow:0 20px 40px rgba(26,86,219,.30) !important
}
a{color:#1a56db}
a:hover{color:#1648c0}
.aap-live-main form input:focus,.aap-live-main form select:focus,.aap-live-main form textarea:focus,
body.aap-hotfix .aap-live-main .lead-form input:focus,body.aap-hotfix .aap-live-main .lead-form select:focus,body.aap-hotfix .aap-live-main .lead-form textarea:focus{
  border-color:rgba(26,86,219,.48) !important;
  box-shadow:0 0 0 4px rgba(26,86,219,.12) !important
}
.aap-live-main > div[class^="aap-"] .aap-kicker,.aap-live-main > div[class^="aap-"] .aap-micro,.aap-live-main .aap-floating-kicker,.aap-live-main .aap-home-lead-kicker,.aap-live-main .aap-reference-kicker,
body.aap-hotfix .aap-live-main .eyebrow,body.aap-hotfix .aap-live-main .kicker{
  background:rgba(26,86,219,.08) !important;
  color:#1648c0 !important;
  border-color:rgba(26,86,219,.18) !important
}
body.aap-hotfix .aap-live-main .button-secondary,
.aap-live-main > div[class^="aap-"] .aap-btn.secondary,
.aap-live-main .aap-home-form-secondary{
  background:rgba(243,248,255,.96) !important;
  color:#102841 !important;
  border-color:rgba(26,86,219,.16) !important
}
.aap-home-lead-copy,.aap-home-lead-card,
body.aap-hotfix .aap-live-main .cta-panel,
.aap-live-main > div[class^="aap-"] .aap-card,.aap-live-main > div[class^="aap-"] .aap-proof-card,.aap-live-main > div[class^="aap-"] .aap-quote,.aap-live-main > div[class^="aap-"] .aap-resource-card,.aap-live-main > div[class^="aap-"] .aap-service-card,.aap-live-main > div[class^="aap-"] .aap-ops-card,.aap-live-main > div[class^="aap-"] .aap-fit-card,.aap-live-main > div[class^="aap-"] .aap-practical-ai-card,.aap-live-main > div[class^="aap-"] .aap-bottom-panel,.aap-live-main > div[class^="aap-"] .aap-hero-shell,
.aap-live-main .aap-home-v3 .aap-floating-panel{
  background:rgba(255,255,255,.96) !important
}
.aap-live-main .aap-home-v3 .aap-step::after{
  background:linear-gradient(90deg,rgba(26,86,219,.42),rgba(26,86,219,0)) !important
}
body.aap-hotfix .aap-live-main .breadcrumbs a{color:#1a56db}
body.aap-hotfix .aap-live-main .breadcrumbs a:hover{color:#1648c0}
body.aap-hotfix .aap-live-main .provider-card:nth-child(3),
body.aap-hotfix .aap-live-main .vision-card:nth-child(3),
body.aap-hotfix .aap-live-main .systems-card:nth-child(3){
  border-color:rgba(26,86,219,.14)
}
body.aap-hotfix .aap-live-main .provider-card:nth-child(3){border-top-color:#0ea5e9}
body.aap-hotfix .aap-live-main .vision-card:nth-child(3),
body.aap-hotfix .aap-live-main .systems-card:nth-child(3){border-left-color:#0ea5e9}
.aap-global-header::after{content:"";position:absolute;left:0;right:0;bottom:0;height:1px;background:linear-gradient(90deg,rgba(26,86,219,0),rgba(26,86,219,.36),rgba(14,165,233,0))}
.aap-global-nav{max-width:none}
.aap-live-footer{position:relative;isolation:isolate;background:linear-gradient(180deg,#0d1930 0%,#132845 58%,#0f2239 100%) !important;border-top:1px solid rgba(232,188,104,.16) !important}
.aap-live-footer::before,
.aap-live-footer::after{content:"";position:absolute;border-radius:999px;pointer-events:none;z-index:0}
.aap-live-footer::before{display:none !important}
.aap-live-footer::after{display:none !important}
.aap-live-footer-grid,.aap-live-footer-shell,.aap-live-footer-bottom{position:relative;z-index:1}
.aap-live-footer-shell{position:relative !important;display:grid !important;grid-template-columns:minmax(0,1.45fr) minmax(220px,.9fr) minmax(0,.95fr) minmax(0,.95fr) !important;gap:28px !important;padding:34px 30px 30px !important;border-radius:30px !important;background:linear-gradient(180deg,rgba(10,25,45,.99) 0%,rgba(14,33,59,.98) 52%,rgba(18,45,76,.96) 100%) !important;border:1px solid rgba(116,167,255,.16) !important;box-shadow:0 30px 78px rgba(7,15,29,.42),inset 0 1px 0 rgba(214,232,255,.12),inset 0 18px 42px rgba(59,130,246,.04) !important;overflow:hidden !important}
.aap-live-footer-shell::before{content:"" !important;position:absolute !important;left:30px !important;right:30px !important;top:0 !important;height:2px !important;background:linear-gradient(90deg,rgba(121,185,255,0),rgba(129,191,255,.75),rgba(241,208,145,.36),rgba(121,185,255,0)) !important;background-size:180% 100% !important;opacity:.9 !important;animation:aapFooterShimmer 8.2s ease-in-out infinite !important}
.aap-live-footer-shell::after{display:none !important}
.aap-live-footer-brand strong,.aap-live-footer-links h3{position:relative !important;display:block !important;margin:0 0 14px !important;padding-top:18px !important;color:#fff8ec !important;font-size:19px !important;font-weight:800 !important;line-height:1.1 !important;z-index:1 !important}
.aap-live-footer-brand strong::before,.aap-live-footer-links h3::before{content:"" !important;position:absolute !important;top:0 !important;left:0 !important;width:66px !important;height:14px !important;background:radial-gradient(circle at 8px 7px,rgba(255,241,201,.78) 0 2px,transparent 2.5px),radial-gradient(circle at 30px 5px,rgba(120,191,255,.72) 0 2px,transparent 2.4px),linear-gradient(90deg,rgba(232,188,104,.08),rgba(123,189,255,.86),rgba(232,188,104,.12)) !important;border-radius:999px !important;box-shadow:0 0 18px rgba(124,191,255,.22) !important}
.aap-live-footer-brand p,.aap-live-footer-links span,.aap-live-footer-links a,.aap-live-footer-bottom{color:rgba(245,236,220,.94) !important;text-decoration:none !important;line-height:1.78 !important;font-size:14px !important}
.aap-live-footer-links{display:grid !important;align-content:start !important;gap:8px !important}
.aap-live-footer-links a{display:inline-flex !important;width:fit-content !important;border-bottom:1px solid transparent !important;transition:color .2s ease,border-color .2s ease,transform .2s ease !important}
.aap-live-footer-links a:hover{color:#dcecff !important;border-color:rgba(123,189,255,.34) !important;transform:translateX(2px) !important}
.aap-live-footer-actions-shell{display:inline-grid !important;width:fit-content !important;max-width:100% !important;padding:8px !important;margin-top:18px !important;border-radius:26px !important;background:linear-gradient(135deg,rgba(90,142,221,.14),rgba(24,55,96,.36)) !important;border:1px solid rgba(135,183,255,.18) !important;box-shadow:inset 0 1px 0 rgba(214,232,255,.12) !important}
.aap-live-footer-actions{display:grid !important;grid-template-columns:max-content max-content !important;gap:12px !important;align-items:center !important}
.aap-footer-quote{margin:16px 0 0;padding:14px 0 0 16px;border-left:2px solid rgba(111,176,255,.42);color:#edf5ff !important;font-style:italic}
.aap-global-cta,.aap-footer-btn,.aap-mobile-nav-cta,body.aap-hotfix .aap-live-main .button,body.aap-hotfix .aap-live-main .aap-growth-path-primary,.aap-live-main > div[class^="aap-"] .aap-btn.primary,.aap-live-main .aap-home-form-submit,body.aap-hotfix .aap-live-main .lead-form button.button{position:relative;overflow:hidden;isolation:isolate;border:1px solid rgba(125,211,252,.26) !important;background:linear-gradient(135deg,#1d4ed8 0%,#2563eb 52%,#38bdf8 100%) !important;box-shadow:0 18px 38px rgba(29,78,216,.34),0 0 0 1px rgba(191,219,254,.12) !important}
.aap-global-cta::before,.aap-footer-btn::before,.aap-mobile-nav-cta::before,body.aap-hotfix .aap-live-main .button::before,body.aap-hotfix .aap-live-main .aap-growth-path-primary::before,.aap-live-main > div[class^="aap-"] .aap-btn.primary::before,.aap-live-main .aap-home-form-submit::before,body.aap-hotfix .aap-live-main .lead-form button.button::before{content:"";position:absolute;inset:0;background:linear-gradient(120deg,rgba(255,255,255,.30),rgba(255,255,255,.10) 28%,rgba(255,255,255,0) 58%,rgba(255,255,255,.16));pointer-events:none;z-index:-1}
.aap-global-cta::after,.aap-footer-btn::after,.aap-mobile-nav-cta::after,body.aap-hotfix .aap-live-main .button::after,body.aap-hotfix .aap-live-main .aap-growth-path-primary::after,.aap-live-main > div[class^="aap-"] .aap-btn.primary::after,.aap-live-main .aap-home-form-submit::after,body.aap-hotfix .aap-live-main .lead-form button.button::after{content:"";position:absolute;width:120px;height:120px;top:-60px;right:-26px;border-radius:999px;background:radial-gradient(circle,rgba(191,219,254,.72),rgba(191,219,254,0) 72%);opacity:.85;pointer-events:none;z-index:-1}
.aap-global-cta:hover,.aap-footer-btn:hover,.aap-mobile-nav-cta:hover,body.aap-hotfix .aap-live-main .button:hover,body.aap-hotfix .aap-live-main .aap-growth-path-primary:hover,.aap-live-main > div[class^="aap-"] .aap-btn.primary:hover,.aap-live-main .aap-home-form-submit:hover,body.aap-hotfix .aap-live-main .lead-form button.button:hover{box-shadow:0 24px 46px rgba(29,78,216,.38),0 0 24px rgba(56,189,248,.18) !important}
.aap-live-footer .aap-footer-btn,.aap-live-footer .aap-live-footer-linkbutton{position:relative !important;display:inline-flex !important;align-items:center !important;justify-content:center !important;min-height:48px !important;padding:0 20px !important;border-radius:999px !important;font-size:13px !important;font-weight:800 !important;text-decoration:none !important;overflow:hidden !important;isolation:isolate !important}
.aap-live-footer .aap-footer-btn{border:1px solid rgba(241,208,145,.20) !important;background:linear-gradient(135deg,#1d4ed8 0%,#2563eb 58%,#38bdf8 100%) !important;color:#fffdf8 !important;box-shadow:0 18px 38px rgba(29,78,216,.26),0 0 20px rgba(56,189,248,.12) !important}
.aap-live-footer .aap-live-footer-linkbutton{border:1px solid rgba(164,199,255,.18) !important;background:linear-gradient(135deg,rgba(255,228,170,.08),rgba(52,91,151,.30)) !important;color:#e8f3ff !important;box-shadow:0 16px 34px rgba(9,19,36,.22),0 0 18px rgba(94,160,255,.10) !important}
.aap-live-footer .aap-footer-btn::before,.aap-live-footer .aap-live-footer-linkbutton::before{content:"" !important;position:absolute !important;inset:0 !important;background:linear-gradient(120deg,rgba(255,255,255,.22),rgba(255,255,255,.06) 34%,rgba(255,255,255,0) 62%,rgba(255,255,255,.14)) !important;pointer-events:none !important}
.aap-live-footer .aap-footer-btn::after,.aap-live-footer .aap-live-footer-linkbutton::after{content:"" !important;position:absolute !important;width:120px !important;height:120px !important;top:-64px !important;right:-18px !important;border-radius:999px !important;background:radial-gradient(circle,rgba(191,219,254,.58),rgba(191,219,254,0) 70%) !important;opacity:.72 !important;pointer-events:none !important}
.aap-live-footer .aap-footer-btn:hover,.aap-live-footer .aap-live-footer-linkbutton:hover{transform:translateY(-2px) !important;box-shadow:0 22px 44px rgba(29,78,216,.22),0 0 24px rgba(94,160,255,.14) !important}
.aap-live-footer .aap-footer-btn,.aap-live-footer .aap-live-footer-linkbutton{animation:none !important}
.aap-live-footer .aap-social-links-footer{margin-top:16px !important}
.aap-live-footer .aap-social-links-footer .aap-social-icon{background:rgba(95,151,232,.12) !important;border-color:rgba(135,183,255,.18) !important;color:#eef6ff !important;box-shadow:0 0 0 1px rgba(214,232,255,.04),0 0 14px rgba(59,130,246,.08) !important}
.aap-live-footer-note{margin-top:10px !important;color:rgba(228,214,177,.74) !important;font-size:11px !important;font-weight:800 !important;letter-spacing:.08em !important;text-transform:uppercase !important}
.aap-live-footer-bottom{display:flex !important;justify-content:space-between !important;gap:16px !important;padding:18px 8px 0 !important;margin-top:16px !important;font-size:12px !important;border-top:1px solid rgba(113,169,246,.12) !important}
body.aap-hotfix .aap-mobile-nav-panel{background:radial-gradient(circle at top right,rgba(56,189,248,.08),transparent 32%),linear-gradient(180deg,#f5f9ff 0%,#ebf3fd 100%) !important;border-top:1px solid rgba(26,86,219,.10) !important}
body.aap-hotfix .aap-mobile-nav-intro{display:inline-flex !important;width:fit-content !important;padding:10px 16px !important;border-radius:999px !important;background:linear-gradient(180deg,rgba(26,86,219,.08),rgba(147,197,253,.12)) !important;color:#546476 !important;font-size:11px !important;font-weight:800 !important;letter-spacing:.08em !important;text-transform:uppercase !important;box-shadow:inset 0 1px 0 rgba(255,255,255,.55) !important}
body.aap-hotfix .aap-mobile-nav-panel a{position:relative !important;border:1px solid rgba(16,40,65,.05) !important;background:linear-gradient(180deg,#ffffff 0%,#f7fbff 100%) !important;box-shadow:0 10px 22px rgba(16,40,65,.06) !important}
body.aap-hotfix .aap-mobile-nav-panel a.current{border-color:rgba(26,86,219,.16) !important;box-shadow:0 16px 28px rgba(26,86,219,.08) !important}
body.aap-hotfix .aap-mobile-nav-panel a.current::before{content:"" !important;position:absolute !important;left:0 !important;top:12px !important;bottom:12px !important;width:4px !important;border-radius:999px !important;background:linear-gradient(180deg,#2563eb,#38bdf8) !important}
body.aap-hotfix .aap-mobile-nav-cta{box-shadow:0 18px 36px rgba(29,78,216,.26),0 0 24px rgba(56,189,248,.14) !important}
body.aap-hotfix .aap-live-main .proof-strip-testimonials{grid-template-columns:minmax(0,1.08fr) repeat(2,minmax(0,1fr));align-items:stretch}
body.aap-hotfix .aap-live-main .homepage-proof-recognition,body.aap-hotfix .aap-live-main .proof-card-recognition-featured{background:radial-gradient(circle at top right,rgba(56,189,248,.26),transparent 34%),linear-gradient(145deg,#102742,#163a69) !important;border-color:rgba(147,197,253,.22) !important;color:#eaf4ff !important;box-shadow:0 24px 60px rgba(15,23,42,.18) !important}
body.aap-hotfix .aap-live-main .homepage-proof-recognition *,body.aap-hotfix .aap-live-main .proof-card-recognition-featured *{color:inherit !important}
body.aap-hotfix .aap-live-main .recognition-eyebrow{display:inline-flex;align-items:center;min-height:30px;padding:0 12px;border-radius:999px;background:rgba(191,219,254,.12);border:1px solid rgba(191,219,254,.18);color:#bfdbfe !important;font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase}
body.aap-hotfix .aap-live-main .recognition-link{display:inline-flex;align-items:center;justify-content:center;min-height:42px;margin-top:12px;padding:0 16px;border-radius:999px;border:1px solid rgba(191,219,254,.22);background:rgba(255,255,255,.07);color:#fff !important;font-weight:800;text-decoration:none}
body.aap-hotfix .aap-live-main .proof-strip-testimonials{grid-template-columns:minmax(0,.92fr) minmax(0,1.18fr) minmax(0,.92fr);gap:18px}
body.aap-hotfix .aap-live-main .quote-card,body.aap-hotfix .aap-live-main .testimonial-card{display:flex;flex-direction:column;gap:1rem;min-height:100%;padding:24px !important;border-radius:24px !important;overflow:hidden}
body.aap-hotfix .aap-live-main .quote-card blockquote,body.aap-hotfix .aap-live-main .testimonial-card blockquote{flex:1;margin:0;padding-left:1rem;border-left:3px solid #1a56db;font-size:clamp(1.02rem,1.22vw,1.18rem);line-height:1.62;text-wrap:balance}
body.aap-hotfix .aap-live-main .quote-meta,body.aap-hotfix .aap-live-main .testimonial-attribution{display:grid;gap:.18rem;margin-top:auto;padding-top:1rem;border-top:1px solid rgba(26,86,219,.12)}
body.aap-hotfix .aap-live-main .quote-meta strong,body.aap-hotfix .aap-live-main .testimonial-attribution strong{color:#102841}
body.aap-hotfix .aap-live-main .quote-meta span,body.aap-hotfix .aap-live-main .testimonial-attribution span{color:#5f7285;font-size:.92rem}
body.aap-hotfix .aap-live-main .proof-card-screenshot{padding:16px !important;background:linear-gradient(180deg,#fbfdff 0%,#f1f6ff 100%) !important;border:1px solid rgba(26,86,219,.12) !important;box-shadow:0 22px 54px rgba(16,40,65,.12) !important}
body.aap-hotfix .aap-live-main .proof-screenshot-frame{position:relative;display:block;padding:10px;border-radius:20px;background:linear-gradient(135deg,rgba(26,86,219,.14),rgba(14,165,233,.08)) !important;box-shadow:inset 0 1px 0 rgba(255,255,255,.72)}
body.aap-hotfix .aap-live-main .proof-screenshot-frame img{display:block;width:100%;height:auto;border-radius:14px;border:1px solid rgba(16,40,65,.08);box-shadow:0 18px 40px rgba(16,40,65,.18)}
body.aap-hotfix .aap-live-main .proof-screenshot-meta{padding-top:14px !important;border-top:none !important}
body.aap-hotfix .aap-live-main .proof-screenshot-meta strong{font-size:1rem}
body.aap-hotfix .aap-live-main .proof-screenshot-meta span{max-width:28ch}
body.aap-hotfix .aap-live-main .testimonial-eyebrow{display:inline-flex;align-items:center;width:fit-content;min-height:28px;padding:0 12px;border-radius:999px;background:rgba(26,86,219,.08);border:1px solid rgba(26,86,219,.12);color:#1f5fa8;font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase}
body.aap-hotfix .aap-live-main .testimonial-card{background:linear-gradient(180deg,#ffffff 0%,#f7fbff 100%) !important}
body.aap-hotfix .aap-live-main .testimonial-card .quote-meta{margin-top:0}
body.aap-hotfix .aap-live-main .aap-side-note-card{background:linear-gradient(160deg,#143055 0%,#1b446f 100%) !important;border:1px solid rgba(147,197,253,.18) !important;box-shadow:0 22px 54px rgba(15,23,42,.18) !important}
body.aap-hotfix .aap-live-main .aap-side-note-card .kicker,body.aap-hotfix .aap-live-main .aap-side-note-card h3,body.aap-hotfix .aap-live-main .aap-side-note-card p,body.aap-hotfix .aap-live-main .aap-side-note-card li{color:#edf6ff !important}
body.aap-hotfix .aap-live-main .aap-side-note-card .kicker{background:rgba(191,219,254,.12) !important;border:1px solid rgba(191,219,254,.18) !important;color:#cfe4ff !important}
body.aap-hotfix .aap-live-main .aap-side-note-card h3{margin:0 0 12px !important;font-size:1.45rem !important;line-height:1.25 !important}
body.aap-hotfix .aap-live-main .aap-side-note-list{display:grid;gap:12px;margin:0;padding:0;list-style:none}
body.aap-hotfix .aap-live-main .aap-side-note-list li{position:relative;padding-left:18px !important;line-height:1.7 !important;color:rgba(237,246,255,.9) !important}
body.aap-hotfix .aap-live-main .aap-side-note-list li::before{content:"";position:absolute;left:0;top:.72em;width:7px;height:7px;border-radius:999px;background:linear-gradient(135deg,#f3ca81,#ffe3ae)}
body.aap-hotfix .aap-live-main .proof-card-recognition-featured strong{display:block;margin-top:10px;margin-bottom:6px}
body.aap-hotfix .aap-live-main .section .contact-card,body.aap-hotfix .aap-live-main .proof-card,body.aap-hotfix .aap-live-main .quote-card{transition:transform .24s ease,box-shadow .24s ease;animation:aapFadeUp .72s ease both}
body.aap-hotfix .aap-live-main .section .contact-card:hover,body.aap-hotfix .aap-live-main .proof-card:hover,body.aap-hotfix .aap-live-main .quote-card:hover{transform:translateY(-4px);box-shadow:0 26px 58px rgba(16,40,65,.12) !important}
body.aap-hotfix .aap-live-main .hero-copy,body.aap-hotfix .aap-live-main .section-heading,body.aap-hotfix .aap-live-main .hero-panel,body.aap-hotfix .aap-live-main .aap-home-lead-shell{animation:aapFadeUp .78s ease both}
body.aap-hotfix .aap-live-main .hero-copy h1,body.aap-hotfix .aap-live-main .section-heading h1{font-size:clamp(2.5rem,4vw,4.1rem) !important;line-height:.99;letter-spacing:-.04em;text-wrap:balance;max-width:13.6ch}
body.aap-page-home .aap-live-main .hero{position:relative;padding:28px 0 0 !important;overflow:hidden}
body.aap-page-home .aap-live-main .hero::before{content:"";position:absolute;top:-120px;right:-60px;width:420px;height:420px;border-radius:999px;background:radial-gradient(circle,rgba(56,189,248,.16),rgba(56,189,248,0) 68%);pointer-events:none}
body.aap-page-home .aap-live-main .hero .container{position:relative}
body.aap-page-home .aap-live-main .hero .section-heading{max-width:880px;padding-bottom:10px}
body.aap-page-home .aap-live-main .hero .section-heading h1{max-width:13ch !important;font-size:clamp(2.72rem,4.2vw,4.55rem) !important;letter-spacing:-.05em}
body.aap-page-home .aap-live-main .hero .section-heading .text-lg{max-width:56ch;font-size:16.5px !important;line-height:1.74 !important}
body.aap-page-home .aap-live-main .hero .section-heading .lede{max-width:56ch}
body.aap-page-home .aap-live-main .hero .hero-actions{gap:14px !important;margin-top:1.7rem !important}
body.aap-page-home .aap-live-main .aap-home-trust-band{display:grid;grid-template-columns:minmax(0,1.02fr) minmax(0,.98fr);gap:18px;align-items:stretch}
body.aap-page-home .aap-live-main .aap-home-trust-lead{position:relative;display:grid;gap:14px;padding:28px 30px;border-radius:28px;background:radial-gradient(circle at top right,rgba(56,189,248,.18),transparent 36%),linear-gradient(145deg,#123562 0%,#19457c 58%,#20569a 100%);border:1px solid rgba(147,197,253,.22);box-shadow:0 24px 54px rgba(15,23,42,.16);color:#eef6ff;overflow:hidden}
body.aap-page-home .aap-live-main .aap-home-trust-lead::after{content:"";position:absolute;inset:auto -40px -70px auto;width:220px;height:220px;border-radius:999px;background:radial-gradient(circle,rgba(245,211,137,.16),rgba(245,211,137,0) 72%);pointer-events:none}
body.aap-page-home .aap-live-main .aap-home-trust-kicker{display:inline-flex;align-items:center;width:fit-content;min-height:30px;padding:0 12px;border-radius:999px;background:rgba(255,255,255,.10);border:1px solid rgba(255,255,255,.14);color:#dbeafe;font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase}
body.aap-page-home .aap-live-main .aap-home-trust-lead strong{display:block;max-width:18ch;font-size:clamp(1.55rem,2.1vw,2rem);line-height:1.08;letter-spacing:-.03em}
body.aap-page-home .aap-live-main .aap-home-trust-lead p{margin:0;max-width:50ch;color:rgba(234,244,255,.88);line-height:1.74}
body.aap-page-home .aap-live-main .aap-home-trust-meta{display:flex;flex-wrap:wrap;align-items:center;gap:10px 14px}
body.aap-page-home .aap-live-main .aap-home-trust-meta span{display:inline-flex;align-items:center;min-height:30px;padding:0 12px;border-radius:999px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);color:#f5f8ff;font-size:12px;font-weight:700}
body.aap-page-home .aap-live-main .aap-home-trust-meta a{display:inline-flex;align-items:center;justify-content:center;min-height:44px;padding:0 18px;border-radius:999px;background:rgba(255,255,255,.96);border:1px solid rgba(255,240,201,.34);color:#16365c !important;font-weight:800;text-decoration:none;box-shadow:0 12px 28px rgba(9,19,36,.16)}
body.aap-page-home .aap-live-main .aap-home-trust-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}
body.aap-page-home .aap-live-main .aap-home-trust-fact{display:grid;align-content:start;gap:10px;padding:22px 18px;border-radius:24px;background:linear-gradient(180deg,#ffffff 0%,#f5f9ff 100%);border:1px solid rgba(16,40,65,.08);box-shadow:0 18px 38px rgba(16,40,65,.08)}
body.aap-page-home .aap-live-main .aap-home-trust-fact strong{display:block;color:#102841;font-size:1.02rem;line-height:1.28}
body.aap-page-home .aap-live-main .aap-home-trust-fact span{color:#617487;font-size:.94rem;line-height:1.66}
body.aap-page-home .aap-live-main .aap-home-lead-panel{padding-top:6px !important}
body.aap-page-home .aap-live-main .aap-home-lead-shell{border-radius:30px !important}
body.aap-page-home .aap-live-main .aap-home-lead-copy{background:linear-gradient(180deg,#ffffff 0%,#f8fbff 100%) !important}
body.aap-page-home .aap-live-main .aap-home-lead-card{background:linear-gradient(180deg,#fcfeff 0%,#f3f8ff 100%) !important}
body.aap-page-home .aap-live-main .proof-strip-proof-pair{grid-template-columns:minmax(0,1.14fr) minmax(0,.86fr) !important;gap:22px !important}
body.aap-page-home .aap-live-main .proof-strip-proof-pair .proof-card-screenshot,body.aap-page-home .aap-live-main .proof-strip-proof-pair .testimonial-card{min-height:100%}
body.aap-page-home .aap-live-main .testimonial-card blockquote{font-size:clamp(1rem,1.15vw,1.16rem) !important}
body.aap-page-resources .aap-live-main .hero-panel{position:relative;padding:26px !important;border-radius:22px !important;background:linear-gradient(165deg,#143a67 0%,#1f538e 100%) !important;border:1px solid rgba(147,197,253,.18) !important;box-shadow:0 24px 56px rgba(16,40,65,.16) !important}
body.aap-page-resources .aap-live-main .hero-panel::before{content:"Decision Guide";display:inline-flex;align-items:center;min-height:28px;padding:0 12px;margin-bottom:12px;border-radius:999px;background:rgba(255,255,255,.10);border:1px solid rgba(255,255,255,.14);color:#d8e8ff;font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase}
body.aap-page-resources .aap-live-main .hero-panel .button-secondary{background:#ffffff !important;color:#17344f !important;border-color:rgba(255,255,255,.3) !important;box-shadow:0 12px 28px rgba(9,19,36,.18) !important}
body.aap-page-contact .aap-live-main .quote-card-compact blockquote{font-size:.96rem !important;line-height:1.72 !important}
body.aap-page-contact .aap-live-main .aap-side-note-card{margin-top:2px !important}
@keyframes aapFadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
@keyframes aapFooterPulse{0%,100%{box-shadow:0 20px 44px rgba(185,118,42,.30),0 0 24px rgba(232,188,104,.16)}50%{box-shadow:0 24px 50px rgba(185,118,42,.34),0 0 34px rgba(232,188,104,.28)}}
@keyframes aapFooterShimmer{0%,100%{background-position:0% 50%}50%{background-position:100% 50%}}
@media (max-width:1100px){.aap-live-footer-shell{grid-template-columns:1fr 1fr !important}}
@media (max-width:767px){body.aap-hotfix .aap-live-main .hero-copy h1,body.aap-hotfix .aap-live-main .section-heading h1{font-size:clamp(2.1rem,8vw,2.95rem) !important;max-width:10.5ch}body.aap-page-home .aap-live-main .hero .section-heading h1{font-size:clamp(2.25rem,8.2vw,3.08rem) !important;max-width:10.6ch !important}body.aap-page-home .aap-live-main .aap-home-trust-band,body.aap-page-home .aap-live-main .aap-home-trust-grid,body.aap-hotfix .aap-live-main .proof-strip-testimonials{grid-template-columns:1fr !important}.aap-live-footer-shell{grid-template-columns:1fr !important;padding:24px 22px !important}.aap-live-footer-actions-shell{width:100% !important;border-radius:24px !important}.aap-live-footer-actions{width:100% !important;display:grid !important;grid-template-columns:1fr !important}.aap-live-footer .aap-footer-btn,.aap-live-footer .aap-live-footer-linkbutton{width:100% !important}.aap-live-footer-bottom{flex-direction:column !important}.aap-mobile-nav-panel{position:fixed !important;top:82px !important;left:0 !important;right:0 !important;bottom:0 !important;max-height:none !important;min-height:calc(100vh - 82px) !important;padding:14px 16px 26px !important;border-radius:0 !important;background:#f8fbff !important;box-shadow:0 24px 50px rgba(16,40,65,.16) !important;overflow:auto !important}.aap-mobile-nav{gap:10px !important}.aap-mobile-nav a{padding:15px 16px !important;border-radius:20px !important;background:#ffffff !important;box-shadow:0 10px 20px rgba(16,40,65,.07) !important}.aap-mobile-nav-intro{padding:2px 2px 8px !important}.aap-mobile-nav-cta{margin:12px 0 0 !important}}
@media (prefers-reduced-motion: reduce){*,*::before,*::after{animation:none !important;transition:none !important;scroll-behavior:auto !important}}
CSS;
}

function aap_hotfix_filter_content($content) {
    if (!is_singular()) {
        return $content;
    }

    $slug = aap_hotfix_current_slug();
    $canonical_slugs = array(
        'home',
        'about',
        'contact',
        'resources',
        'ai-documentation',
        'ai-revenue-cycle',
        'revenue-cycle-management',
        'credentialing-accelerator',
        'credentialing',
        'practice-automation-consulting',
        'practice-operations',
        'mental-health-billing',
        'medical-billing',
        'workflow-friction-audit',
        'behavioral-health-billing-pmhnp-groups',
        'multi-state-credentialing-outpatient-practices',
        'starting-a-practice',
        'growing-a-solo-practice',
        'adding-providers',
        'timely-filing-guide',
        'ehr-workflow-optimization',
        'practice-workflow-review-checklist',
    );

    if (in_array($slug, $canonical_slugs, true)) {
        $post = get_post(get_the_ID());
        if ($post instanceof WP_Post) {
            $content = (string) $post->post_content;
        }
    }

    $replacements = array(
        'mailto:info@advanceapractice.com' => 'mailto:' . AAP_HOTFIX_EMAIL,
        'mailto:ryan@advanceapractice.com' => 'mailto:' . AAP_HOTFIX_EMAIL,
        '/contact-us/' => '/contact/',
        '/practice-resources/' => '/resources/',
        '/about-us/' => '/about/',
        '/credentialing-accelerator/' => '/credentialing/',
        '/ai-revenue-cycle/' => '/revenue-cycle-management/',
        '/practice-automation-consulting/' => '/practice-operations/',
        'Request a Workflow Review' => 'Get Started',
        'Schedule a Strategy Call' => 'Get Started',
        'Talk Through Your Current Bottlenecks' => 'Get Started',
        'Start the Intake' => 'Get Started',
        'Start the Conversation' => 'Book a Consultation',
        'Book a Readiness Review' => 'Get Started',
    );

    $content = str_replace(array_keys($replacements), array_values($replacements), $content);
    $content = str_replace('[aap_v6_managed_page]', '', $content);
    $content = preg_replace('#<p>\s*Recognized among Portland\'s top medical billing companies\.\s*Featured by MediBillMD\.\s*</p>#i', '', $content);
    $content = aap_hotfix_restore_seed_content_if_needed($slug, $content);

    $content = str_replace('>info@advanceapractice.com<', '>Email AdvanceAPractice<', $content);
    $content = str_replace('>ryan@advanceapractice.com<', '>Email AdvanceAPractice<', $content);
    $content = str_replace('Approved feedback from healthcare practices.', 'What healthcare practices say.', $content);

    if (is_page('contact')) {
        $content = aap_hotfix_normalize_contact_form_copy($content);
        $content = aap_hotfix_refine_contact_support_blocks($content);
    }

    if (is_page('about')) {
        $content = aap_hotfix_refine_about_support_blocks($content);
    }

    if ((is_front_page() || is_home())) {
        $content = aap_hotfix_full_homepage_markup();
        $content = preg_replace('#<div class="link-list" aria-label="Service areas">[\s\S]*?</div>#i', '', $content);
        $content = preg_replace('#<p[^>]*>\s*Start Intake\s*</p>#si', '<p>Get Started</p>', $content);
        $content = preg_replace(
            '#<aside class="hero-panel">.*?<p class="panel-intro">Best fit</p>#si',
            '<aside class="hero-panel"><figure class="panel-figure"><img src="https://advanceapractice.com/wp-content/uploads/2023/07/diverse-medical-team-of-doctors-looking-at-camera-while-holding-clipboard-and-medical-files-e1623252244361-pf2n5igpmfm4ppl9w5nt89pd7hae8a7fb8ryn904qo.jpg" alt="Healthcare operations team representing coordinated billing, credentialing, and workflow support"></figure><p class="panel-intro">Best fit</p>',
            $content,
            1
        );

        $content = aap_hotfix_refine_homepage_markup($content);
    }

    if ((is_page('practice-automation-consulting') || is_page('practice-operations')) && strpos($content, 'aap-systems-strip') === false) {
        $content .= aap_hotfix_get_current_systems_strip_markup();
    }

    if (in_array($slug, array('ai-documentation', 'ai-revenue-cycle', 'revenue-cycle-management', 'credentialing-accelerator', 'credentialing', 'mental-health-billing', 'medical-billing'), true) && strpos($content, 'aap-reference-strip') === false) {
        $content .= aap_hotfix_get_reference_strip_markup($slug);
    }

    if (in_array($slug, array('about', 'resources', 'ai-documentation', 'ai-revenue-cycle', 'revenue-cycle-management', 'credentialing-accelerator', 'credentialing', 'practice-automation-consulting', 'practice-operations', 'mental-health-billing', 'medical-billing', 'workflow-friction-audit', 'behavioral-health-billing-pmhnp-groups', 'multi-state-credentialing-outpatient-practices', 'starting-a-practice', 'growing-a-solo-practice', 'adding-providers', 'timely-filing-guide', 'ehr-workflow-optimization'), true) && strpos($content, 'aap-growth-path-strip') === false) {
        $content .= aap_hotfix_get_growth_path_strip_markup();
    }

    return aap_hotfix_operator_language_pass($slug, $content);
}

function aap_hotfix_filter_full_html($html) {
    if (!is_string($html) || $html === '') {
        return $html;
    }

    $replacements = array(
        'href="/contact-us/' => 'href="/contact/',
        "href='/contact-us/" => "href='/contact/",
        'href="/practice-resources/' => 'href="/resources/',
        "href='/practice-resources/" => "href='/resources/",
        'href="/about-us/' => 'href="/about/',
        "href='/about-us/" => "href='/about/",
        'href="/credentialing-accelerator/' => 'href="/credentialing/',
        "href='/credentialing-accelerator/" => "href='/credentialing/",
        'href="/ai-revenue-cycle/' => 'href="/revenue-cycle-management/',
        "href='/ai-revenue-cycle/" => "href='/revenue-cycle-management/",
        'href="/practice-automation-consulting/' => 'href="/practice-operations/',
        "href='/practice-automation-consulting/" => "href='/practice-operations/",
        'mailto:info@advanceapractice.com' => '/contact/#aap-contact-form',
        'mailto:ryan@advanceapractice.com' => '/contact/#aap-contact-form',
        'https://advanceapractice.com/wp-content/uploads/2025/08/1.png' => 'https://advanceapractice.com/wp-content/uploads/2023/07/dji_export_1635712616724-1-scaled.jpg',
    );

    $html = str_replace(array_keys($replacements), array_values($replacements), $html);
    $html = str_replace('Start Intake', 'Get Started', $html);
    $html = preg_replace('#<p>\s*Recognized among Portland\'s top medical billing companies\.\s*Featured by MediBillMD\.\s*</p>#i', '', $html);
    $html = preg_replace('#<div class="link-list" aria-label="Service areas">[\s\S]*?</div>#i', '', $html);

    $html = preg_replace(
        '#https://advanceapractice\.com/wp-content/uploads/2025/01/dji_fly_20231231_082040_0016_1704041053345_photo-1-scaled-e1736691273316\.webp#',
        'https://advanceapractice.com/wp-content/uploads/2025/07/file_0000000052d8622f8ad58c3721236ec5.png',
        $html,
        1
    );

    if (is_page('medical-billing')) {
        $html = str_replace(
            'https://advanceapractice.com/wp-content/uploads/2024/01/2023-03-06.webp',
            'https://advanceapractice.com/wp-content/uploads/2023/07/diverse-medical-team-of-doctors-looking-at-camera-while-holding-clipboard-and-medical-files-e1623252244361-pf2n5igpmfm4ppl9w5nt89pd7hae8a7fb8ryn904qo.jpg',
            $html
        );
    }

    if (is_page('credentialing-accelerator') || is_page('credentialing')) {
        $html = str_replace(
            'https://advanceapractice.com/wp-content/uploads/2024/01/2023-03-06.webp',
            'https://advanceapractice.com/wp-content/uploads/2025/08/1.png',
            $html
        );
    }

    $html = str_replace(
        "alt=\"Portland skyline aerial view representing AdvanceAPractice's Portland-rooted healthcare operations support\"",
        "alt=\"Healthcare operations image used to represent national practice support\"",
        $html
    );

    $html = str_replace(
        'alt="Credentialing and provider onboarding support for healthcare practices"',
        'alt="Healthcare team collaboration supporting provider onboarding and credentialing coordination"',
        $html
    );

    $html = str_replace(
        'alt="Practice automation consulting and healthcare workflow systems support"',
        'alt="Healthcare workflow support image representing practice operations and system cleanup"',
        $html
    );

    $html = str_replace(
        'alt="Medical billing and healthcare revenue-cycle support for outpatient practices"',
        'alt="Outpatient medical practice team representing medical billing support and stronger operations visibility"',
        $html
    );

    $html = preg_replace('#<section[^>]*>.*?Latest Insights.*?Loading the latest articles\.\.\..*?</section>#si', '', $html);
    $html = str_replace("src=\"'+image+'\"", 'src=""', $html);
    $html = str_replace("src=\"' + image + '\"", 'src=""', $html);
    $html = aap_hotfix_replace_global_header_markup($html);

    if (is_front_page() || is_home()) {
        $homepage_html = aap_hotfix_full_homepage_markup();
        $homepage_html = aap_hotfix_diversify_page_visuals('home', $homepage_html);
        $homepage_html = aap_hotfix_refine_homepage_markup($homepage_html);
        $homepage_html = aap_hotfix_operator_language_pass('home', $homepage_html);
        $html = preg_replace_callback(
            '#<main\b([^>]*)>[\s\S]*?</main>#i',
            function ($matches) use ($homepage_html) {
                return '<main' . $matches[1] . '>' . $homepage_html . '</main>';
            },
            $html,
            1
        );
        $html = str_replace('Approved feedback from healthcare practices.', 'What healthcare practices say.', $html);
        $html = str_replace('Category', '', $html);
        $html = str_replace('Featured Guide', 'Featured Resource', $html);
        $html = str_replace(
            '<figure class="panel-figure"><img src="https://advanceapractice.com/wp-content/uploads/2026/03/aap-documentation-dashboard.png" alt="Healthcare operations illustration"></figure>',
            '<figure class="panel-figure"><img src="https://advanceapractice.com/wp-content/uploads/2025/08/1.png" alt="Healthcare operations support image representing direct founder-led billing and workflow guidance"></figure>',
            $html
        );
    }

    $slug = aap_hotfix_current_slug();
    if ($slug !== '') {
        $html = preg_replace_callback(
            '#<main\b([^>]*)>([\s\S]*?)</main>#i',
            function ($matches) use ($slug) {
                $restored = aap_hotfix_restore_main_html_if_needed($slug, $matches[2]);
                return '<main' . $matches[1] . '>' . $restored . '</main>';
            },
            $html,
            1
        );
    }

    return aap_hotfix_operator_language_pass($slug, $html);
}

function aap_hotfix_remove_elementor_widget_by_section_class($html, $section_class) {
    $quoted_class = preg_quote($section_class, '#');

    $patterns = array(
        '#<div class="elementor-element[^"]*elementor-widget-html[^"]*"[^>]*>.*?<section class="' . $quoted_class . '".*?</section>\s*</div>#si',
        '#<style[^>]*>.*?\.' . $quoted_class . '.*?</style>\s*<section class="' . $quoted_class . '".*?</section>#si',
        '#<section class="' . $quoted_class . '".*?</section>#si',
    );

    foreach ($patterns as $pattern) {
        $html = preg_replace($pattern, '', $html);
    }

    return $html;
}

function aap_hotfix_replace_global_header_markup($html) {
    $replacement = aap_hotfix_site_header_markup();

    return preg_replace('#<div class="aap-global-header">.*?</div></div></div>#si', $replacement, $html, 1);
}

function aap_hotfix_output_live_header() {
    if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
        return;
    }

    $slug = aap_hotfix_current_slug();
    $allowed = array(
        'home',
        'about',
        'contact',
        'resources',
        'ai-documentation',
        'ai-revenue-cycle',
        'revenue-cycle-management',
        'credentialing-accelerator',
        'credentialing',
        'practice-automation-consulting',
        'practice-operations',
        'mental-health-billing',
        'medical-billing',
        'workflow-friction-audit',
        'behavioral-health-billing-pmhnp-groups',
        'multi-state-credentialing-outpatient-practices',
        'starting-a-practice',
        'growing-a-solo-practice',
        'adding-providers',
        'timely-filing-guide',
        'ehr-workflow-optimization',
        'practice-workflow-review-checklist',
    );

    if (!in_array($slug, $allowed, true)) {
        return;
    }

    echo aap_hotfix_site_header_markup();
}

function aap_hotfix_output_live_footer() {
    if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
        return;
    }

    $slug = aap_hotfix_current_slug();
    $allowed = array(
        'home',
        'about',
        'contact',
        'resources',
        'ai-documentation',
        'ai-revenue-cycle',
        'revenue-cycle-management',
        'credentialing-accelerator',
        'credentialing',
        'practice-automation-consulting',
        'practice-operations',
        'mental-health-billing',
        'medical-billing',
        'workflow-friction-audit',
        'behavioral-health-billing-pmhnp-groups',
        'multi-state-credentialing-outpatient-practices',
        'starting-a-practice',
        'growing-a-solo-practice',
        'adding-providers',
        'timely-filing-guide',
        'ehr-workflow-optimization',
    );

    if (!in_array($slug, $allowed, true)) {
        return;
    }

    if ($slug === 'practice-automation-consulting' || $slug === 'practice-operations') {
        echo aap_hotfix_get_current_systems_strip_markup();
    }

    echo aap_hotfix_site_footer_markup();
}

function aap_hotfix_get_homepage_lead_panel_markup() {
    $return_url = esc_url(home_url('/'));
    $page_url = $return_url;
    $page_title = 'AdvanceAPractice Homepage';
    $dashboard_image = 'https://advanceapractice.com/wp-content/uploads/2025/08/1.png';

    return '<style id="aap-home-lead-panel-css">.aap-home-lead-panel{padding:26px 0 36px}.aap-home-lead-shell{position:relative;display:grid;grid-template-columns:minmax(0,.94fr) minmax(0,1.06fr);gap:30px;padding:34px;border-radius:30px;background:linear-gradient(180deg,#ffffff 0%,#f3f8ff 100%);border:1px solid rgba(16,40,65,.08);box-shadow:0 24px 64px rgba(16,40,65,.09),inset 0 1px 0 rgba(255,255,255,.85);overflow:hidden}.aap-home-lead-shell::before{content:\"\";position:absolute;top:-140px;right:-110px;width:310px;height:310px;border-radius:999px;background:radial-gradient(circle,rgba(56,189,248,.14),rgba(56,189,248,0) 72%);pointer-events:none}.aap-home-lead-shell::after{content:\"\";position:absolute;left:-120px;bottom:-140px;width:260px;height:260px;border-radius:999px;background:radial-gradient(circle,rgba(37,99,235,.08),rgba(37,99,235,0) 70%);pointer-events:none}.aap-home-lead-kicker{display:inline-flex;align-items:center;padding:7px 12px;border-radius:999px;background:rgba(255,255,255,.96);border:1px solid rgba(16,40,65,.08);color:#1f5fa8;font-size:12px;font-weight:800;letter-spacing:.08em;text-transform:uppercase}.aap-home-lead-copy{position:relative;z-index:1}.aap-home-lead-copy h2{margin:16px 0 14px;color:#102841;font-family:\"Iowan Old Style\",\"Palatino Linotype\",\"Book Antiqua\",Georgia,serif;font-size:clamp(2rem,2.8vw,2.6rem);line-height:1.03;letter-spacing:-.035em;max-width:10.8ch}.aap-home-lead-copy p,.aap-home-lead-copy li{color:#5e7286;font-size:15px;line-height:1.72}.aap-home-lead-points{display:grid;gap:10px;margin:18px 0 0;padding:0;list-style:none}.aap-home-lead-points li{position:relative;padding-left:18px}.aap-home-lead-points li::before{content:\"\";position:absolute;left:0;top:.72em;width:8px;height:8px;border-radius:999px;background:linear-gradient(135deg,#1d4ed8,#38bdf8)}.aap-home-lead-visual{margin-top:22px;padding:14px;border-radius:22px;background:linear-gradient(180deg,#ffffff 0%,#f8fbff 100%);border:1px solid rgba(16,40,65,.08);box-shadow:0 16px 36px rgba(16,40,65,.08)}.aap-home-lead-visual img{display:block;width:100%;height:auto;border-radius:18px;background:#edf4fa;box-shadow:0 18px 38px rgba(16,40,65,.10)}.aap-home-lead-caption{margin:12px 2px 0;color:#607387;font-size:13px;line-height:1.62}.aap-home-lead-card{position:relative;z-index:1;padding:26px;border-radius:24px;background:linear-gradient(180deg,#ffffff 0%,#f8fbff 100%);border:1px solid rgba(16,40,65,.08);box-shadow:0 18px 44px rgba(16,40,65,.08)}.aap-home-lead-card::before{content:\"Consultation Request\";display:inline-flex;align-items:center;min-height:28px;padding:0 12px;margin-bottom:16px;border-radius:999px;background:rgba(26,86,219,.08);border:1px solid rgba(26,86,219,.12);color:#1f5fa8;font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase}.aap-home-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.aap-home-form-grid label{display:grid;gap:7px;color:#17344f;font-size:13px;font-weight:700}.aap-home-form-wide{grid-column:1 / -1}.aap-hp-field{width:100%;min-height:50px;border-radius:15px;border:1px solid rgba(16,40,65,.12);background:#fff;padding:0 14px;color:#22384b;font-size:15px;box-shadow:none}.aap-hp-textarea{min-height:120px;padding:12px 14px;resize:vertical}.aap-hp-honeypot{position:absolute !important;left:-9999px !important}.aap-home-form-actions{display:flex;flex-wrap:wrap;gap:12px;margin-top:18px}.aap-home-form-submit,.aap-home-form-secondary{display:inline-flex;align-items:center;justify-content:center;min-height:50px;padding:0 18px;border-radius:15px;text-decoration:none;font-size:14px;font-weight:800}.aap-home-form-submit{border:0;background:linear-gradient(135deg,#1d4ed8,#2563eb 56%,#38bdf8);color:#fff;cursor:pointer;box-shadow:0 16px 28px rgba(29,78,216,.16)}.aap-home-form-secondary{border:1px solid rgba(16,40,65,.10);background:#fff;color:#17344f}.aap-home-form-note{margin-top:12px;color:#607387;font-size:12px;line-height:1.55}@media (max-width:1024px){.aap-home-lead-shell{grid-template-columns:1fr}.aap-home-lead-copy h2{max-width:14ch}}@media (max-width:767px){.aap-home-lead-panel{padding:20px 0 28px}.aap-home-lead-shell{padding:22px;gap:22px}.aap-home-form-grid{grid-template-columns:1fr}.aap-home-form-submit,.aap-home-form-secondary{width:100%}}</style><section id="aap-home-lead-panel" class="aap-home-lead-panel"><div class="aap-global-wrap"><div class="aap-home-lead-shell">'
        . '<div class="aap-home-lead-copy"><span class="aap-home-lead-kicker">Quick Start</span><h2>Tell us where the practice needs help first.</h2><p>Share the billing slowdown, credentialing delay, reporting issue, or workflow drag that needs attention now. The first step is to narrow the real pressure point and the most useful next move.</p><ul class="aap-home-lead-points"><li>Built for founders, solo owners, and operator-minded teams preparing to grow</li><li>Useful when billing, credentialing, reporting, and workflow friction are overlapping</li><li>No patient PHI in the first message</li></ul><div class="aap-home-lead-visual"><img src="' . esc_url($dashboard_image) . '" alt="Healthcare operations leadership image supporting founder-led billing, credentialing, and workflow guidance"><p class="aap-home-lead-caption">The first review should connect the business issue, the people carrying it, and the next useful action without creating more noise.</p></div></div>'
        . '<div class="aap-home-lead-card"><form action="/wp-admin/admin-post.php" method="post" accept-charset="UTF-8"><input type="hidden" name="action" value="aap_capture_lead"><input type="hidden" name="form_name" value="Homepage Lead Form"><input type="hidden" name="service_context" value="Homepage"><input type="hidden" name="page_title" value="' . esc_attr($page_title) . '"><input type="hidden" name="page_url" value="' . esc_attr($page_url) . '"><input type="hidden" name="return_url" value="' . esc_attr($return_url) . '"><input type="text" name="website" value="" tabindex="-1" autocomplete="off" class="aap-hp-field aap-hp-honeypot" aria-hidden="true">'
        . '<div class="aap-home-form-grid">'
        . '<label><span>Practice name</span><input class="aap-hp-field" type="text" name="practice_name" placeholder="Practice or organization name" required></label>'
        . '<label><span>Your name</span><input class="aap-hp-field" type="text" name="decision_maker_name" placeholder="Your name" required></label>'
        . '<label><span>Work email</span><input class="aap-hp-field" type="email" name="email" placeholder="name@practice.com" required></label>'
        . '<label><span>Current system</span><select class="aap-hp-field" name="ehr_pm_system"><option value="">Select one</option><option value="AdvancedMD">AdvancedMD</option><option value="Epic">Epic</option><option value="athenahealth">athenahealth</option><option value="TherapyNotes">TherapyNotes</option><option value="SimplePractice">SimplePractice</option><option value="Kareo / Tebra">Kareo / Tebra</option><option value="Valant">Valant</option><option value="Clinicient">Clinicient</option><option value="Other or mixed systems">Other or mixed systems</option></select></label>'
        . '<label><span>What do you need help with first?</span><select class="aap-hp-field" name="service_interest" required><option value="">Select one</option><option value="Revenue Cycle Management">Revenue Cycle Management</option><option value="Mental Health Billing">Mental Health Billing</option><option value="Medical Billing">Medical Billing</option><option value="Credentialing">Credentialing</option><option value="Practice Operations">Practice Operations</option><option value="Current Systems">Current Systems</option><option value="AI Documentation">AI Documentation</option></select></label>'
        . '<label class="aap-home-form-wide"><span>Give us a little context.</span><textarea class="aap-hp-field aap-hp-textarea" name="slowdown" rows="4" required placeholder="Claims are aging, enrollment is lagging, reporting is thin, or the team is carrying too much manual work."></textarea></label>'
        . '</div><div class="aap-home-form-actions"><button class="aap-home-form-submit" type="submit">Request Review</button><a class="aap-home-form-secondary" href="/contact/#aap-contact-form">Open Full Contact Form</a></div><p class="aap-home-form-note">A concise business-level summary is enough to start. No patient PHI in first contact.</p></form></div>'
        . '</div></div></section>';
}

function aap_hotfix_get_homepage_resources_markup() {
    return '<section class="section section-sand"><div class="container"><div class="section-heading">'
        . '<span class="kicker">Resources</span>'
        . '<h2>Useful guides for billing deadlines, workflow cleanup, and provider readiness.</h2>'
        . '<p class="text-limit">Use the resource hub for practical guidance on timely filing limits, reimbursement workflow problems, credentialing cleanup, and the operational issues that keep making collections harder than they should be.</p>'
        . '</div><div class="grid-3">'
        . '<article class="resource-card"><span class="resource-label">Featured Resource</span><h3><a href="/timely-filing-guide/">Timely filing guide</a></h3><p>Understand timely filing limits, corrected claim deadlines, appeal deadlines, and the handoff issues that cause avoidable write-offs.</p><a class="resource-link" href="/timely-filing-guide/">Read the guide</a></article>'
        . '<article class="resource-card"><span class="resource-label">Billing Operations</span><h3><a href="/revenue-cycle-management/">Revenue cycle management review</a></h3><p>See how claim flow, aging A/R, denial tracking, payer follow-up, and KPI reporting can be tightened without replacing the whole stack.</p><a class="resource-link" href="/revenue-cycle-management/">See revenue cycle management</a></article>'
        . '<article class="resource-card"><span class="resource-label">Workflow Improvement</span><h3><a href="/ehr-workflow-optimization/">Current-systems optimization</a></h3><p>Review how work moves inside AdvancedMD, Epic, TherapyNotes, SimplePractice, athenahealth, Kareo or Tebra, Valant, and Clinicient before adding more tools.</p><a class="resource-link" href="/ehr-workflow-optimization/">See current systems</a></article>'
        . '</div></div></section>';
}

function aap_hotfix_get_homepage_faq_markup() {
    return '<section class="section"><div class="container"><div class="section-heading">'
        . '<span class="kicker">FAQ</span>'
        . '<h2>Common questions from practice owners and operators.</h2>'
        . '<p class="text-limit">These are the issues most practices raise when billing, credentialing, growth, and workflow ownership start slipping at the same time.</p>'
        . '</div><div class="faq-list">'
        . '<details class="faq-item"><summary>Do you only work with behavioral health practices?</summary><div class="faq-content"><p>Behavioral health is a major focus, including therapy, psychiatry, PMHNP, telehealth, and outpatient reimbursement work, but AdvanceAPractice also supports outpatient medical groups that need stronger billing discipline, provider onboarding, reporting, and workflow structure.</p></div></details>'
        . '<details class="faq-item"><summary>Can you help if the practice already has a billing team or outside vendor?</summary><div class="faq-content"><p>Yes. The work can start with a targeted review of claim flow, aging A/R, denial follow-up, credentialing status, documentation burden, or EHR workflow so it is clear where ownership is loose and what needs cleanup first.</p></div></details>'
        . '<details class="faq-item"><summary>Do you replace the current EHR or practice-management system?</summary><div class="faq-content"><p>No. The goal is to improve workflow inside the systems already in place first. That includes handoffs, queues, templates, routing, payer follow-up, and reporting inside environments like AdvancedMD, Epic, TherapyNotes, SimplePractice, athenahealth, Kareo or Tebra, Valant, and Clinicient.</p></div></details>'
        . '<details class="faq-item"><summary>What is the best way to start?</summary><div class="faq-content"><p>Start with the area creating the most operational risk right now: billing performance, payer enrollment delays, provider readiness, documentation burden, or a workflow audit. The first review is meant to clarify scope and the next useful move, not force a broad engagement.</p></div></details>'
        . '</div></div></section>';
}

function aap_hotfix_get_current_systems_strip_markup() {
    return '<section class="aap-systems-strip"><div class="aap-global-wrap"><div class="aap-systems-shell">'
        . '<span class="aap-systems-kicker">Current Systems</span>'
        . '<h2>Workflow improvement should work around the systems the practice already uses.</h2>'
        . '<p>AdvanceAPractice works inside the EHR and practice-management systems a team already relies on, including Epic, AdvancedMD, Kareo, TherapyNotes, SimplePractice, athenahealth, NextGen, Practice Fusion, eClinicalWorks, and IntakeQ or PracticeQ where those systems are already in place. The goal is clearer handoffs, steadier reporting, and workflow changes that fit the environment already in use before anyone talks about replacement.</p>'
        . '</div></div></section>';
}

function aap_hotfix_get_growth_path_strip_markup() {
    return '<section class="aap-growth-path-strip"><div class="aap-global-wrap"><div class="aap-growth-path-shell">'
        . '<span class="aap-growth-path-kicker">Provider Pathways</span>'
        . '<h2>Choose the stage where the practice needs operational help first.</h2>'
        . '<p>Every stage creates a different kind of strain. The work looks different when a provider is trying to launch, grow without owner overload, stabilize collections, or add clinicians without letting payer setup and workflow discipline fall behind.</p>'
        . '<div class="aap-growth-path-grid">'
        . '<a class="aap-growth-path-card" href="/contact/#aap-contact-form"><strong>Starting a Practice</strong><span class="aap-growth-path-meta">For independent providers building the back office for the first time.</span><span>What usually breaks: NPI, CAQH, PECOS, payer enrollment, fee schedule setup, first claims, and telehealth readiness all move out of sequence.</span><span>How AdvanceAPractice helps: organize provider onboarding, payer enrollment, billing setup, and first-workflow readiness so the practice can open without avoidable delays.</span><em>Plan your launch</em></a>'
        . '<a class="aap-growth-path-card" href="/contact/#aap-contact-form"><strong>Growing a Practice</strong><span class="aap-growth-path-meta">For owners who are doing too much as volume, staff, or provider count starts to grow.</span><span>What usually breaks: follow-up gets inconsistent, reporting stays thin, queues age, and the owner becomes the fallback for every billing or ops question.</span><span>How AdvanceAPractice helps: tighten handoffs, create reporting cadence, clarify ownership, and improve billing and workflow discipline before growth creates more rework.</span><em>Build a stronger foundation</em></a>'
        . '<a class="aap-growth-path-card" href="/contact/#aap-contact-form"><strong>Managing a Practice</strong><span class="aap-growth-path-meta">For established practices that are open, staffed, and collecting, but not performing the way they should.</span><span>What usually breaks: denials repeat, aging A/R grows, payment posting lags, authorizations get missed, and leadership cannot tell where collections are losing momentum.</span><span>How AdvanceAPractice helps: review revenue cycle performance, denial patterns, reporting gaps, and workflow ownership so collections and day-to-day execution get back under control.</span><em>Review your revenue cycle</em></a>'
        . '<a class="aap-growth-path-card" href="/contact/#aap-contact-form"><strong>Expanding a Practice</strong><span class="aap-growth-path-meta">For practices adding clinicians, locations, states, or payer complexity.</span><span>What usually breaks: provider onboarding lags, group-to-individual linkage stalls, payer enrollment sequencing slips, and new growth adds more exceptions than the team can absorb.</span><span>How AdvanceAPractice helps: coordinate credentialing acceleration, provider readiness, workflow design, and current-system cleanup so expansion does not slow reimbursement.</span><em>Prepare to grow</em></a>'
        . '</div><div class="aap-growth-path-actions"><a class="aap-growth-path-primary" href="/contact/#aap-contact-form">Get Started</a><a class="aap-growth-path-secondary" href="/workflow-friction-audit/">See the Workflow Friction Audit</a></div>'
        . '</div></div></section>';
}

function aap_hotfix_get_reference_strip_markup($slug) {
    $map = array(
        'ai-documentation' => array(
            'kicker' => 'Selected References',
            'title' => 'Documentation and AI support should stay grounded in healthcare workflow reality.',
            'description' => 'A few official references that align with documentation burden reduction, healthcare IT burden, and AI governance.',
            'links' => array(
                array('label' => 'NIST AI Risk Management Framework', 'url' => 'https://www.nist.gov/itl/ai-risk-management-framework'),
                array('label' => 'HealthIT.gov on EHR burden reduction', 'url' => 'https://www.healthit.gov/news/2020/2/21/final-report-delivers-strategy-reduce-ehr-burden'),
                array('label' => 'HHS / ONC burden report', 'url' => 'https://healthit.gov/wp-content/uploads/2020/02/BurdenReport.pdf'),
            ),
        ),
        'ai-revenue-cycle' => array(
            'kicker' => 'Selected References',
            'title' => 'Revenue-cycle improvement works best when visibility and workflow are both clear.',
            'description' => 'Reference points around administrative simplification, claims transactions, and healthcare operations visibility.',
            'links' => array(
                array('label' => 'CMS Administrative Simplification', 'url' => 'https://www.cms.gov/priorities/key-initiatives/burden-reduction/administrative-simplification'),
                array('label' => 'CAQH Index Report', 'url' => 'https://www.caqh.org/insights/caqh-index-report'),
                array('label' => 'HealthIT.gov interoperability and workflow context', 'url' => 'https://www.healthit.gov/'),
            ),
        ),
        'revenue-cycle-management' => array(
            'kicker' => 'Selected References',
            'title' => 'Revenue-cycle improvement works best when visibility and workflow are both clear.',
            'description' => 'Reference points around administrative simplification, claims transactions, and healthcare operations visibility.',
            'links' => array(
                array('label' => 'CMS Administrative Simplification', 'url' => 'https://www.cms.gov/priorities/key-initiatives/burden-reduction/administrative-simplification'),
                array('label' => 'CAQH Index Report', 'url' => 'https://www.caqh.org/insights/caqh-index-report'),
                array('label' => 'HealthIT.gov interoperability and workflow context', 'url' => 'https://www.healthit.gov/'),
            ),
        ),
        'credentialing-accelerator' => array(
            'kicker' => 'Selected References',
            'title' => 'Credentialing and payer setup are operational work, not just paperwork.',
            'description' => 'Reference points connected to provider data, enrollment workflows, and operational readiness.',
            'links' => array(
                array('label' => 'CAQH Provider Solutions', 'url' => 'https://www.caqh.org/solutions/provider-data'),
                array('label' => 'CMS provider enrollment resources', 'url' => 'https://www.cms.gov/medicare/enrollment-renewal/providers-suppliers'),
                array('label' => 'PECOS overview', 'url' => 'https://www.cms.gov/medicare/enrollment-renewal/medicare-provider-enrollment-chain-ownership-system-pecos'),
            ),
        ),
        'credentialing' => array(
            'kicker' => 'Selected References',
            'title' => 'Credentialing and payer setup are operational work, not just paperwork.',
            'description' => 'Reference points connected to provider data, enrollment workflows, and operational readiness.',
            'links' => array(
                array('label' => 'CAQH Provider Solutions', 'url' => 'https://www.caqh.org/solutions/provider-data'),
                array('label' => 'CMS provider enrollment resources', 'url' => 'https://www.cms.gov/medicare/enrollment-renewal/providers-suppliers'),
                array('label' => 'PECOS overview', 'url' => 'https://www.cms.gov/medicare/enrollment-renewal/medicare-provider-enrollment-chain-ownership-system-pecos'),
            ),
        ),
        'mental-health-billing' => array(
            'kicker' => 'Selected References',
            'title' => 'Behavioral health billing depends on payer rules, documentation, and provider-type awareness.',
            'description' => 'A few official sources tied to behavioral health billing, telehealth, and provider eligibility changes.',
            'links' => array(
                array('label' => 'CMS behavioral health work', 'url' => 'https://www.cms.gov/about-cms/what-we-do/addressing-improving-behavioral-health'),
                array('label' => 'CMS MFTs and MHCs billing resources', 'url' => 'https://www.cms.gov/medicare/payment/fee-schedules/physician-fee-schedule/marriage-family-therapists-mental-health-counselors'),
                array('label' => 'CMS telehealth resources', 'url' => 'https://www.cms.gov/telehealth'),
            ),
        ),
        'medical-billing' => array(
            'kicker' => 'Selected References',
            'title' => 'Medical billing gets stronger when claims workflow and operational discipline are connected.',
            'description' => 'Reference points on transactions, simplification, and the business-side workflow behind reimbursement.',
            'links' => array(
                array('label' => 'CMS Administrative Simplification', 'url' => 'https://www.cms.gov/priorities/key-initiatives/burden-reduction/administrative-simplification'),
                array('label' => 'CAQH Index Report', 'url' => 'https://www.caqh.org/insights/caqh-index-report'),
                array('label' => 'CMS claims and billing resources', 'url' => 'https://www.cms.gov/medicare/billing'),
            ),
        ),
    );

    if (!isset($map[$slug])) {
        return '';
    }

    $config = $map[$slug];
    $links = '';
    foreach ($config['links'] as $link) {
        $links .= '<a href="' . esc_url($link['url']) . '" target="_blank" rel="noopener">' . esc_html($link['label']) . '</a>';
    }

    return '<section class="aap-reference-strip"><div class="aap-global-wrap"><div class="aap-reference-shell">'
        . '<span class="aap-reference-kicker">' . esc_html($config['kicker']) . '</span>'
        . '<h2>' . esc_html($config['title']) . '</h2>'
        . '<p>' . esc_html($config['description']) . '</p>'
        . '<div class="aap-reference-links">' . $links . '</div>'
        . '</div></div></section>';
}

function aap_hotfix_print_head_overrides() {
      echo aap_hotfix_branding_head_markup();
      ?>
      <style id="aap-hotfix-css">
        .hfg_footer,
        #site-footer,
        footer.elementor-location-footer,
        .aap-footer-v1,
        .aap-global-footer,
        .nv-page-title-wrap,
        .nv-title-meta-wrap,
        .entry-header,
        header.header {
          display: none !important;
        }

        input,
        textarea,
        select {
          color: #22384b !important;
          -webkit-text-fill-color: #22384b !important;
          background: #ffffff !important;
          opacity: 1 !important;
        }

        .aap-global-header {
          position: sticky !important;
          top: 0 !important;
          z-index: 1000 !important;
          background: #ffffff !important;
          backdrop-filter: none !important;
          border-bottom: 1px solid rgba(16, 40, 65, 0.08) !important;
          box-shadow: 0 2px 18px rgba(16, 40, 65, 0.04) !important;
        }

        .aap-global-wrap {
          width: min(1360px, calc(100% - 28px)) !important;
          margin: 0 auto !important;
        }

        .aap-global-header-bar {
          display: flex !important;
          align-items: center !important;
          justify-content: space-between !important;
          gap: 14px !important;
          min-height: 72px !important;
          padding: 10px 0 !important;
        }

        .aap-global-nav {
          display: flex !important;
          flex-wrap: nowrap !important;
          align-items: center !important;
          gap: 8px !important;
          white-space: nowrap !important;
        }

        .aap-global-nav a,
        .aap-mobile-nav a,
        .aap-live-footer-v2 a {
          color: #17344f !important;
          text-decoration: none !important;
        }

        .aap-global-nav a {
          font-size: 12px !important;
          font-weight: 700 !important;
          letter-spacing: -0.01em !important;
          padding: 8px 8px !important;
          border-radius: 10px !important;
        }

        .aap-global-nav a:hover,
        .aap-mobile-nav a:hover,
        .aap-live-footer-v2 a:hover {
          color: #1f5fa8 !important;
        }

        .aap-global-nav a.current,
        .aap-mobile-nav a.current {
          color: #102841 !important;
          background: rgba(31, 95, 168, 0.08) !important;
        }

        .aap-global-actions {
          display: flex !important;
          align-items: center !important;
          gap: 10px !important;
          flex: 0 0 auto !important;
          margin-left: auto !important;
        }

        .aap-global-cta,
        .aap-mobile-cta,
        .aap-live-footer-cta {
          display: inline-flex !important;
          align-items: center !important;
          justify-content: center !important;
          min-height: 42px !important;
          padding: 0 16px !important;
          border-radius: 999px !important;
          background: linear-gradient(135deg, #1f5fa8, #197d89) !important;
          color: #fff !important;
          text-decoration: none !important;
          font-weight: 800 !important;
          font-size: 13px !important;
          box-shadow: 0 12px 24px rgba(16, 40, 65, 0.10) !important;
        }

        .aap-global-brand img {
          display: block !important;
          height: 40px !important;
          width: auto !important;
          max-width: 220px !important;
        }

        .aap-global-brand {
          display: flex !important;
          align-items: center !important;
          gap: 12px !important;
          text-decoration: none !important;
          min-width: 0 !important;
          flex: 1 1 auto !important;
          max-width: calc(100% - 64px) !important;
        }

        .aap-global-brand-copy {
          display: flex !important;
          flex-direction: column !important;
          min-width: 0 !important;
        }

        .aap-global-brand-copy strong {
          color: #102841 !important;
          font-size: 16px !important;
          font-weight: 800 !important;
          letter-spacing: -0.01em !important;
          line-height: 1.02 !important;
        }

        .aap-global-brand-copy em {
          color: #6b7c8c !important;
          font-style: normal !important;
          font-size: 10px !important;
          font-weight: 700 !important;
          letter-spacing: 0.04em !important;
          text-transform: uppercase !important;
          margin-top: 2px !important;
        }

        .aap-menu-toggle,
        .aap-mobile-menu {
          display: none !important;
        }

        .aap-menu-toggle {
          width: auto !important;
          height: 42px !important;
          padding: 0 0 0 12px !important;
          border: 0 !important;
          border-radius: 0 !important;
          background: transparent !important;
          box-shadow: none !important;
          cursor: pointer !important;
          align-items: center !important;
          justify-content: center !important;
          flex-direction: column !important;
          gap: 5px !important;
        }

        .aap-menu-toggle span {
          display: block !important;
          width: 16px !important;
          height: 2px !important;
          border-radius: 999px !important;
          background: #17344f !important;
          transition: transform .2s ease, opacity .2s ease !important;
        }

        body.aap-menu-open .aap-menu-toggle span:nth-child(1) {
          transform: translateY(7px) rotate(45deg) !important;
        }

        body.aap-menu-open .aap-menu-toggle span:nth-child(2) {
          opacity: 0 !important;
        }

        body.aap-menu-open .aap-menu-toggle span:nth-child(3) {
          transform: translateY(-7px) rotate(-45deg) !important;
        }

        body.aap-menu-open {
          overflow: hidden !important;
        }

        .aap-mobile-menu {
          margin: 0 !important;
          padding: 10px 0 16px !important;
          border: 0 !important;
          border-top: 1px solid rgba(16, 40, 65, 0.08) !important;
          border-radius: 0 0 18px 18px !important;
          background: #ffffff !important;
          box-shadow: 0 18px 32px rgba(16, 40, 65, 0.08) !important;
          position: absolute !important;
          top: 100% !important;
          left: 0 !important;
          right: 0 !important;
          z-index: 1100 !important;
        }

        .aap-mobile-nav {
          display: grid !important;
          gap: 8px !important;
        }

        .aap-mobile-nav a {
          display: block !important;
          padding: 10px 14px !important;
          border-radius: 0 !important;
          font-size: 14px !important;
          font-weight: 700 !important;
          background: transparent !important;
        }

        .aap-mobile-cta {
          width: 100% !important;
          margin-top: 12px !important;
        }

.aap-live-footer-v2 {
  margin-top: 28px !important;
  padding: 0 0 20px !important;
  background:
    radial-gradient(circle at 14% 16%, rgba(56, 189, 248, 0.12), transparent 30%),
    radial-gradient(circle at 86% 84%, rgba(37, 99, 235, 0.14), transparent 26%),
    linear-gradient(180deg, #0a172b 0%, #10233e 56%, #0d2037 100%) !important;
  border-top: 1px solid rgba(119, 176, 255, 0.14) !important;
}

.aap-live-footer-shell {
  display: grid !important;
  grid-template-columns: 1.45fr 1fr 1fr 1fr !important;
  gap: 24px !important;
  padding: 28px !important;
  border-radius: 28px !important;
  background:
    linear-gradient(180deg, rgba(9, 24, 45, 0.98) 0%, rgba(14, 33, 59, 0.96) 56%, rgba(15, 38, 68, 0.94) 100%) !important;
  border: 1px solid rgba(124, 180, 255, 0.14) !important;
  box-shadow: 0 28px 70px rgba(7, 15, 29, 0.38), inset 0 1px 0 rgba(217, 235, 255, 0.08), inset 0 16px 34px rgba(37, 99, 235, 0.05) !important;
}

.aap-live-footer-brand strong,
.aap-live-footer-links h3 {
  position: relative !important;
  display: block !important;
  margin: 0 0 14px !important;
  padding-top: 18px !important;
  color: #fff8ec !important;
  font-size: 18px !important;
  font-weight: 800 !important;
  line-height: 1.1 !important;
}

.aap-live-footer-links h3::before,
.aap-live-footer-brand strong::before {
  content: "" !important;
  position: absolute !important;
  top: 0 !important;
  left: 0 !important;
  width: 54px !important;
  height: 12px !important;
  background:
    radial-gradient(circle at 8px 6px, rgba(220, 238, 255, 0.92) 0 2px, transparent 2.4px),
    radial-gradient(circle at 29px 4px, rgba(242, 202, 129, 0.72) 0 1.7px, transparent 2.2px),
    linear-gradient(90deg, rgba(115, 188, 255, 0.14), rgba(121, 185, 255, 0.82), rgba(243, 202, 129, 0.18)) !important;
  border-radius: 999px !important;
  box-shadow: 0 0 16px rgba(96, 165, 250, 0.2) !important;
}

.aap-live-footer-brand p,
.aap-live-footer-links span,
.aap-live-footer-links a,
.aap-live-footer-bottom {
  color: rgba(241, 232, 214, 0.92) !important;
  text-decoration: none !important;
  line-height: 1.75 !important;
  font-size: 14px !important;
}

.aap-live-footer-links {
  display: grid !important;
  align-content: start !important;
  gap: 8px !important;
}

.aap-live-footer-links a {
  width: fit-content !important;
  border-bottom: 1px solid transparent !important;
  transition: color .2s ease, border-color .2s ease, transform .2s ease !important;
}

.aap-live-footer-links a:hover {
  color: #ddecff !important;
  border-color: rgba(124, 180, 255, 0.34) !important;
  transform: translateX(2px) !important;
}

.aap-live-footer-actions {
  display: flex !important;
  flex-wrap: wrap !important;
  gap: 12px !important;
  margin-top: 16px !important;
}

.aap-live-footer .aap-footer-btn,
.aap-live-footer-linkbutton {
  position: relative !important;
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  min-height: 44px !important;
  padding: 0 18px !important;
  border-radius: 999px !important;
  font-size: 13px !important;
  font-weight: 800 !important;
  text-decoration: none !important;
  overflow: hidden !important;
  isolation: isolate !important;
}

.aap-live-footer .aap-footer-btn {
  border: 1px solid rgba(145, 195, 255, 0.26) !important;
  background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 56%, #38bdf8 100%) !important;
  color: #fffdf8 !important;
  box-shadow: 0 18px 40px rgba(29, 78, 216, 0.28), 0 0 20px rgba(56, 189, 248, 0.14) !important;
}

.aap-live-footer-linkbutton {
  border: 1px solid rgba(145, 195, 255, 0.16) !important;
  background: linear-gradient(135deg, rgba(243, 202, 129, 0.06), rgba(46, 88, 151, 0.28)) !important;
  color: #ebf4ff !important;
  box-shadow: 0 14px 34px rgba(9, 19, 36, 0.24), 0 0 16px rgba(96, 165, 250, 0.08) !important;
}

.aap-live-footer .aap-footer-btn::before,
.aap-live-footer-linkbutton::before {
  content: "" !important;
  position: absolute !important;
  inset: 0 !important;
  background: linear-gradient(120deg, rgba(255,255,255,.22), rgba(255,255,255,.06) 34%, rgba(255,255,255,0) 62%, rgba(255,255,255,.14)) !important;
  pointer-events: none !important;
}

.aap-live-footer .aap-footer-btn::after,
.aap-live-footer-linkbutton::after {
  content: "" !important;
  position: absolute !important;
  width: 120px !important;
  height: 120px !important;
  top: -64px !important;
  right: -18px !important;
  border-radius: 999px !important;
  background: radial-gradient(circle, rgba(191, 219, 254, 0.62), rgba(191, 219, 254, 0) 70%) !important;
  opacity: .68 !important;
  pointer-events: none !important;
}

.aap-live-footer .aap-footer-btn:hover,
.aap-live-footer-linkbutton:hover {
  transform: translateY(-2px) !important;
  box-shadow: 0 24px 48px rgba(29, 78, 216, 0.26), 0 0 22px rgba(96, 165, 250, 0.14) !important;
}

.aap-live-footer-note {
  margin-top: 8px !important;
  color: rgba(201, 224, 255, 0.76) !important;
  font-size: 11px !important;
  font-weight: 800 !important;
  letter-spacing: 0.08em !important;
  text-transform: uppercase !important;
}

.aap-live-footer-bottom {
  display: flex !important;
  justify-content: space-between !important;
  gap: 16px !important;
  padding: 16px 6px 0 !important;
  margin-top: 14px !important;
  font-size: 12px !important;
  border-top: 1px solid rgba(117, 176, 255, 0.12) !important;
}

        .aap-home-lead-panel {
          padding: 18px 0 20px !important;
        }

        .aap-home-lead-shell {
          display: grid !important;
          grid-template-columns: 1.05fr .95fr !important;
          gap: 24px !important;
          padding: 28px !important;
          border-radius: 28px !important;
          background: linear-gradient(180deg, #fbfdff 0%, #eef5fb 100%) !important;
          border: 1px solid rgba(16, 40, 65, 0.08) !important;
          box-shadow: 0 22px 56px rgba(16, 40, 65, 0.10) !important;
        }

        .aap-home-lead-kicker {
          display: inline-flex !important;
          align-items: center !important;
          padding: 7px 12px !important;
          border-radius: 999px !important;
          background: rgba(255, 255, 255, 0.9) !important;
          border: 1px solid rgba(16, 40, 65, 0.08) !important;
          color: #1f5fa8 !important;
          font-size: 12px !important;
          font-weight: 800 !important;
          letter-spacing: 0.08em !important;
          text-transform: uppercase !important;
        }

        .aap-home-lead-copy h2 {
          margin: 16px 0 14px !important;
          color: #102841 !important;
          font-size: clamp(2rem, 3.2vw, 2.7rem) !important;
          line-height: 1.06 !important;
          letter-spacing: -0.03em !important;
        }

        .aap-home-lead-copy p,
        .aap-home-lead-copy li {
          color: #607387 !important;
          font-size: 16px !important;
          line-height: 1.75 !important;
        }

        .aap-home-lead-copy ul {
          margin: 14px 0 0 18px !important;
          padding: 0 !important;
        }

        .aap-home-lead-card {
          padding: 20px !important;
          border-radius: 22px !important;
          background: rgba(255, 255, 255, 0.94) !important;
          border: 1px solid rgba(16, 40, 65, 0.08) !important;
          box-shadow: 0 18px 42px rgba(16, 40, 65, 0.08) !important;
        }

        .aap-home-form-grid {
          display: grid !important;
          grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
          gap: 14px !important;
        }

        .aap-home-form-grid label {
          display: grid !important;
          gap: 7px !important;
          color: #17344f !important;
          font-size: 13px !important;
          font-weight: 700 !important;
        }

        .aap-home-form-wide {
          grid-column: 1 / -1 !important;
        }

        .aap-hp-field {
          width: 100% !important;
          min-height: 48px !important;
          border-radius: 14px !important;
          border: 1px solid rgba(16, 40, 65, 0.12) !important;
          background: #ffffff !important;
          padding: 0 14px !important;
          color: #22384b !important;
          font-size: 15px !important;
          box-shadow: none !important;
        }

        .aap-hp-textarea {
          min-height: 120px !important;
          padding: 12px 14px !important;
          resize: vertical !important;
        }

        .aap-hp-honeypot {
          position: absolute !important;
          left: -9999px !important;
        }

        .aap-home-form-actions {
          display: flex !important;
          flex-wrap: wrap !important;
          gap: 12px !important;
          margin-top: 16px !important;
        }

        .aap-home-form-submit,
        .aap-home-form-secondary {
          display: inline-flex !important;
          align-items: center !important;
          justify-content: center !important;
          min-height: 46px !important;
          padding: 0 16px !important;
          border-radius: 999px !important;
          text-decoration: none !important;
          font-size: 14px !important;
          font-weight: 800 !important;
        }

        .aap-home-form-submit {
          border: 0 !important;
          background: linear-gradient(135deg, #1f5fa8, #197d89) !important;
          color: #ffffff !important;
          cursor: pointer !important;
          box-shadow: 0 12px 24px rgba(16, 40, 65, 0.10) !important;
        }

        .aap-home-form-secondary {
          border: 1px solid rgba(16, 40, 65, 0.10) !important;
          background: #ffffff !important;
          color: #17344f !important;
        }

        .aap-systems-strip {
          padding: 8px 0 18px !important;
        }

        .aap-systems-shell {
          padding: 24px !important;
          border-radius: 24px !important;
          border: 1px solid rgba(16, 40, 65, 0.08) !important;
          background: linear-gradient(180deg, #ffffff 0%, #eef5fb 100%) !important;
          box-shadow: 0 18px 42px rgba(16, 40, 65, 0.08) !important;
        }

        .aap-systems-kicker {
          display: inline-flex !important;
          align-items: center !important;
          padding: 7px 12px !important;
          border-radius: 999px !important;
          background: rgba(31, 95, 168, 0.08) !important;
          color: #1f5fa8 !important;
          font-size: 12px !important;
          font-weight: 800 !important;
          letter-spacing: 0.08em !important;
          text-transform: uppercase !important;
        }

        .aap-systems-shell h2 {
          margin: 14px 0 12px !important;
          color: #102841 !important;
          font-size: clamp(1.8rem, 3vw, 2.4rem) !important;
          line-height: 1.08 !important;
          letter-spacing: -0.03em !important;
        }

        .aap-systems-shell p {
          margin: 0 !important;
          color: #607387 !important;
          font-size: 16px !important;
          line-height: 1.75 !important;
        }

        .aap-reference-strip {
          padding: 8px 0 18px !important;
        }

        .aap-reference-shell {
          padding: 24px !important;
          border-radius: 24px !important;
          border: 1px solid rgba(16, 40, 65, 0.08) !important;
          background: linear-gradient(180deg, #ffffff 0%, #f5f9fc 100%) !important;
          box-shadow: 0 18px 42px rgba(16, 40, 65, 0.08) !important;
        }

        .aap-reference-kicker {
          display: inline-flex !important;
          align-items: center !important;
          padding: 7px 12px !important;
          border-radius: 999px !important;
          background: rgba(31, 95, 168, 0.08) !important;
          color: #1f5fa8 !important;
          font-size: 12px !important;
          font-weight: 800 !important;
          letter-spacing: 0.08em !important;
          text-transform: uppercase !important;
        }

        .aap-reference-shell h2 {
          margin: 14px 0 10px !important;
          color: #102841 !important;
          font-size: clamp(1.7rem, 3vw, 2.3rem) !important;
          line-height: 1.08 !important;
          letter-spacing: -0.03em !important;
        }

        .aap-reference-shell p {
          margin: 0 0 16px !important;
          color: #607387 !important;
          font-size: 16px !important;
          line-height: 1.75 !important;
        }

        .aap-reference-links {
          display: flex !important;
          flex-wrap: wrap !important;
          gap: 12px !important;
        }

        .aap-reference-links a {
          display: inline-flex !important;
          align-items: center !important;
          min-height: 42px !important;
          padding: 0 14px !important;
          border-radius: 12px !important;
          border: 1px solid rgba(16, 40, 65, 0.08) !important;
          background: #ffffff !important;
          color: #1f5fa8 !important;
          text-decoration: none !important;
          font-size: 14px !important;
          font-weight: 800 !important;
          box-shadow: 0 10px 22px rgba(16, 40, 65, 0.05) !important;
        }

        .aap-growth-path-strip {
          padding: 8px 0 18px !important;
        }

        .aap-growth-path-shell {
          padding: 24px !important;
          border-radius: 24px !important;
          border: 1px solid rgba(16, 40, 65, 0.08) !important;
          background: linear-gradient(180deg, #ffffff 0%, #f7fafc 100%) !important;
          box-shadow: 0 18px 42px rgba(16, 40, 65, 0.08) !important;
        }

        .aap-growth-path-kicker {
          display: inline-flex !important;
          align-items: center !important;
          padding: 7px 12px !important;
          border-radius: 999px !important;
          background: rgba(183, 122, 48, 0.08) !important;
          color: #8a5921 !important;
          font-size: 12px !important;
          font-weight: 800 !important;
          letter-spacing: 0.08em !important;
          text-transform: uppercase !important;
        }

        .aap-growth-path-shell h2 {
          margin: 14px 0 10px !important;
          color: #102841 !important;
          font-size: clamp(1.7rem, 3vw, 2.3rem) !important;
          line-height: 1.08 !important;
          letter-spacing: -0.03em !important;
        }

        .aap-growth-path-shell p {
          margin: 0 0 16px !important;
          color: #607387 !important;
          font-size: 16px !important;
          line-height: 1.75 !important;
        }

        .aap-growth-path-grid {
          display: grid !important;
          grid-template-columns: repeat(4, 1fr) !important;
          gap: 14px !important;
          margin-top: 18px !important;
        }

        .aap-growth-path-card {
          display: grid !important;
          gap: 8px !important;
          padding: 18px !important;
          border-radius: 18px !important;
          border: 1px solid rgba(16, 40, 65, 0.08) !important;
          background: rgba(255, 255, 255, 0.92) !important;
          text-decoration: none !important;
          box-shadow: 0 12px 26px rgba(16, 40, 65, 0.05) !important;
          min-width: 0 !important;
          overflow: hidden !important;
        }

        .aap-growth-path-card strong {
          color: #102841 !important;
          font-size: 16px !important;
          letter-spacing: -0.02em !important;
          overflow-wrap: anywhere !important;
        }

        .aap-growth-path-card span {
          color: #607387 !important;
          font-size: 14px !important;
          line-height: 1.65 !important;
          font-weight: 400 !important;
          overflow-wrap: anywhere !important;
        }

        .aap-growth-path-card .aap-growth-path-meta {
          color: #1b4771 !important;
          font-size: 13px !important;
          font-weight: 700 !important;
          line-height: 1.55 !important;
        }

        .aap-growth-path-card em {
          color: #1f5fa8 !important;
          font-size: 13px !important;
          font-style: normal !important;
          font-weight: 800 !important;
          overflow-wrap: anywhere !important;
        }

        .aap-growth-path-actions {
          display: flex !important;
          flex-wrap: wrap !important;
          gap: 12px !important;
          margin-top: 18px !important;
        }

        .aap-growth-path-primary,
        .aap-growth-path-secondary {
          display: inline-flex !important;
          align-items: center !important;
          justify-content: center !important;
          min-height: 46px !important;
          padding: 0 16px !important;
          border-radius: 14px !important;
          text-decoration: none !important;
          font-weight: 800 !important;
        }

        .aap-growth-path-primary {
          background: linear-gradient(135deg, #a76c2a, #ca9447) !important;
          color: #fff !important;
          box-shadow: 0 12px 24px rgba(124, 84, 31, 0.14) !important;
        }

        .aap-growth-path-secondary {
          border: 1px solid rgba(16, 40, 65, 0.10) !important;
          background: #ffffff !important;
          color: #17344f !important;
        }

        .aap-resources-v3 .aap-card .aap-meta {
          font-size: 0 !important;
        }

        .aap-resources-v3 .aap-card .aap-meta::before {
          content: "Featured Insight" !important;
          font-size: 12px !important;
          letter-spacing: 0.08em !important;
          text-transform: uppercase !important;
        }

        body.home .aap-home-trust-form-v1,
        body.home .aap-home-process-v1,
        body.home .aap-home-why-v1,
        body.home .aap-home-fit-v1,
        body.home .aap-home-faq-v1,
        body.home .aap-global-footer {
          display: none !important;
        }

        body.page-id-17389 .elementor-element-222f755a,
        body.page-id-17389 .elementor-element-341f3ef9 {
          display: none !important;
        }

        body.page-id-103 .aap-visual,
        body.page-id-17388 .aap-visual {
          padding: 14px !important;
          background: linear-gradient(180deg, #edf4fb 0%, #e7eff8 100%) !important;
        }

        body.page-id-103 .aap-visual img,
        body.page-id-17388 .aap-visual img {
          min-height: 420px !important;
          object-fit: contain !important;
          background: #ffffff !important;
          border-radius: 18px !important;
        }

        a[href^="mailto:"]:not(.aap-footer-contact-link),
        a[href^="tel:"] {
          display: none !important;
        }

        @media (max-width: 1180px) {
          .aap-global-nav-desktop,
          .aap-global-cta {
            display: none !important;
          }

          .aap-menu-toggle {
            display: inline-flex !important;
          }

          body.aap-menu-open .aap-mobile-menu {
            display: block !important;
          }

          .aap-live-footer-shell {
            grid-template-columns: 1fr 1fr !important;
          }

          .aap-home-lead-shell {
            grid-template-columns: 1fr !important;
          }
        }

        @media (max-width: 767px) {
          html,
          body {
            overflow-x: hidden !important;
          }

          .aap-global-wrap {
            width: min(100%, calc(100% - 20px)) !important;
          }

          .aap-global-header {
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
            overflow: visible !important;
          }

          .aap-global-header-bar {
            min-height: 70px !important;
            padding: 10px 0 !important;
            gap: 10px !important;
            position: relative !important;
            padding-right: 52px !important;
          }

          .aap-global-brand img {
            height: 44px !important;
            max-width: 132px !important;
          }

          .aap-global-brand-copy strong {
            display: none !important;
          }

          .aap-global-brand-copy em {
            display: none !important;
          }

          .aap-global-brand {
            max-width: calc(100% - 52px) !important;
          }

          .aap-global-actions {
            gap: 0 !important;
            position: static !important;
            transform: none !important;
          }

          .aap-menu-toggle {
            position: fixed !important;
            top: 12px !important;
            right: 12px !important;
            z-index: 1101 !important;
            display: inline-flex !important;
          }

          .aap-mobile-menu {
            position: fixed !important;
            top: 60px !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            z-index: 1099 !important;
            max-height: none !important;
            margin: 0 !important;
            padding: 16px 16px 28px !important;
            border-radius: 0 !important;
            border: 0 !important;
            border-top: 1px solid rgba(16, 40, 65, 0.08) !important;
            background: #f8fbff !important;
            box-shadow: 0 18px 42px rgba(16, 40, 65, 0.08) !important;
            overflow-y: auto !important;
            -webkit-overflow-scrolling: touch !important;
          }

          .aap-mobile-nav,
          .aap-mobile-cta {
            max-width: 100% !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
          }

          .aap-mobile-nav {
            gap: 10px !important;
          }

          .aap-mobile-nav a {
            padding: 14px 14px !important;
            background: #ffffff !important;
            box-shadow: 0 8px 18px rgba(16, 40, 65, 0.05) !important;
          }

          .aap-live-footer-shell {
            grid-template-columns: 1fr !important;
            padding: 22px !important;
          }

          .aap-live-footer-bottom {
            flex-direction: column !important;
          }

          .aap-home-lead-shell {
            padding: 22px !important;
          }

          .aap-home-form-grid {
            grid-template-columns: 1fr !important;
          }

          .aap-systems-shell {
            padding: 22px !important;
          }

          .aap-reference-shell {
            padding: 22px !important;
          }

          body.home .aap-home-hero-v2 h1 {
            font-size: 2rem !important;
            line-height: 1.02 !important;
            overflow-wrap: anywhere !important;
          }

          body.home .aap-home-hero-v2 .aap-grid-2 {
            grid-template-columns: 1fr !important;
          }

          body.home .aap-home-hero-v2 .aap-visual {
            margin-top: 18px !important;
          }

          html {
            scroll-behavior: smooth !important;
          }

          .nv-nav-skip a:not(:first-child),
          .screen-reader-text.skip-link[href="#content"] + .screen-reader-text.skip-link[href="#content"] {
            display: none !important;
          }

          body.aap-hotfix,
          body.aap-hotfix .site-content,
          body.aap-hotfix .nv-content-wrap,
          body.aap-hotfix .nv-single-page-wrap,
          body.aap-hotfix .entry-content {
            background: #f4f8fc !important;
            color: #1f2428 !important;
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
          }

          body.aap-hotfix .container {
            background: transparent !important;
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
          }

          body.aap-hotfix .elementor-widget-container > [class*="aap-"] {
            width: 100% !important;
          }

          body.aap-hotfix input,
          body.aap-hotfix textarea,
          body.aap-hotfix select {
            background: #fffdf8 !important;
          }

          body.aap-hotfix .aap-mobile-nav-panel[hidden] {
            display: none !important;
          }

          body.aap-hotfix .aap-global-header {
            background: #ffffff !important;
            border-bottom: 1px solid rgba(23, 27, 32, 0.08) !important;
            box-shadow: 0 8px 24px rgba(17, 20, 24, 0.05) !important;
          }

          body.aap-hotfix .aap-global-brand {
            padding: 0 !important;
            border-radius: 0 !important;
            background: transparent !important;
            border: 0 !important;
            box-shadow: none !important;
          }

          body.aap-hotfix .aap-global-header-bar {
            display: grid !important;
            grid-template-columns: minmax(260px, auto) minmax(0, 1fr) auto !important;
            align-items: center !important;
            gap: 14px !important;
            min-height: 84px !important;
            padding: 14px 0 !important;
          }

          body.aap-hotfix .aap-global-brand-copy strong,
          body.aap-hotfix .aap-live-main > div[class^="aap-"] h1,
          body.aap-hotfix .aap-live-main > div[class^="aap-"] h2,
          body.aap-hotfix .aap-live-main > div[class^="aap-"] h3 {
            color: #171b20 !important;
          }

          body.aap-hotfix .aap-global-brand-copy em,
          body.aap-hotfix .aap-live-main .aap-home-lead-kicker,
          body.aap-hotfix .aap-live-main .aap-kicker,
          body.aap-hotfix .aap-live-main .aap-reference-kicker,
          body.aap-hotfix .aap-live-main .aap-micro {
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace !important;
            letter-spacing: 0.11em !important;
            text-transform: uppercase !important;
          }

          body.aap-hotfix .aap-global-brand-copy em {
            color: #61676c !important;
          }

          body.aap-hotfix .aap-global-nav {
            display: flex !important;
            flex-wrap: wrap !important;
            justify-content: center !important;
            gap: 4px !important;
          }

          body.aap-hotfix .aap-global-nav a,
          body.aap-hotfix .aap-mobile-nav-panel a,
          body.aap-hotfix .aap-live-footer a {
            color: #333a40 !important;
            text-decoration: none !important;
          }

          body.aap-hotfix .aap-global-nav a {
            padding: 8px 10px !important;
            border-radius: 12px !important;
            font-size: 11px !important;
          }

          body.aap-hotfix .aap-global-nav a:hover,
          body.aap-hotfix .aap-global-nav a.current {
            background: rgba(23, 27, 32, 0.06) !important;
            color: #171b20 !important;
            transform: none !important;
          }

          body.aap-hotfix .aap-global-cta,
          body.aap-hotfix .aap-mobile-nav-cta,
          body.aap-hotfix .aap-footer-btn,
          body.aap-hotfix .aap-live-main .aap-btn.primary,
          body.aap-hotfix .aap-live-main .aap-home-form-submit {
            background: linear-gradient(135deg, #a76c2a, #ca9447) !important;
            color: #fff !important;
            box-shadow: 0 14px 30px rgba(124, 84, 31, 0.16) !important;
            padding-left: 15px !important;
            padding-right: 15px !important;
          }

          body.aap-hotfix .aap-menu-toggle {
            display: none !important;
            min-height: 44px !important;
            padding: 0 8px 0 12px !important;
            border-radius: 0 !important;
            border: 0 !important;
            background: transparent !important;
            color: #171b20 !important;
            font-weight: 800 !important;
            font-size: 12px !important;
            text-transform: uppercase !important;
            box-shadow: none !important;
            align-items: center !important;
            justify-content: center !important;
            line-height: 1 !important;
            text-align: center !important;
          }

          body.aap-hotfix .aap-mobile-nav-panel {
            display: grid !important;
            gap: 8px !important;
            width: auto !important;
            margin: 0 !important;
            padding: 10px 0 16px !important;
            border-radius: 0 0 18px 18px !important;
            background: #ffffff !important;
            border: 0 !important;
            border-top: 1px solid rgba(23, 27, 32, 0.08) !important;
            box-shadow: 0 18px 32px rgba(17, 20, 24, 0.08) !important;
            max-height: calc(100vh - 90px) !important;
            overflow: auto !important;
            overscroll-behavior: contain !important;
          }

          body.aap-hotfix .aap-mobile-nav-panel a {
            padding: 11px 14px !important;
            border-radius: 0 !important;
            background: transparent !important;
            color: #2b3238 !important;
          }

          body.aap-hotfix .aap-mobile-nav-panel a:hover,
          body.aap-hotfix .aap-mobile-nav-panel a.current {
            background: rgba(23, 27, 32, 0.06) !important;
            color: #171b20 !important;
          }

          body.aap-hotfix .aap-live-footer {
            background: #171b20 !important;
            color: #d8d2ca !important;
            padding: 44px 0 20px !important;
            border-top: 1px solid rgba(255, 255, 255, 0.05) !important;
          }

          body.aap-hotfix .aap-live-footer-grid {
            display: grid !important;
            grid-template-columns: 1.35fr 1fr 1fr !important;
            gap: 32px !important;
          }

          body.aap-hotfix .aap-live-footer h3,
          body.aap-hotfix .aap-live-footer p,
          body.aap-hotfix .aap-live-footer li,
          body.aap-hotfix .aap-live-footer a {
            color: #d8d2ca !important;
          }

 body.aap-hotfix .aap-live-main > div[class^="aap-"] {
            background: linear-gradient(180deg, #fbfdff 0%, #f6f9fd 100%) !important;
          }

          body.aap-hotfix .aap-live-main > div[class^="aap-"] p,
          body.aap-hotfix .aap-live-main > div[class^="aap-"] li {
            color: #626b70 !important;
          }

          body.aap-hotfix .aap-live-main > div[class^="aap-"] .aap-card,
          body.aap-hotfix .aap-live-main > div[class^="aap-"] .aap-proof-card,
          body.aap-hotfix .aap-live-main > div[class^="aap-"] .aap-panel,
          body.aap-hotfix .aap-live-main > div[class^="aap-"] .aap-hero-shell,
          body.aap-hotfix .aap-live-main > div[class^="aap-"] .aap-quote,
          body.aap-hotfix .aap-live-main > div[class^="aap-"] .aap-resource-card,
          body.aap-hotfix .aap-live-main > div[class^="aap-"] .aap-service-card,
          body.aap-hotfix .aap-live-main > div[class^="aap-"] .aap-visual,
          body.aap-hotfix .aap-home-lead-copy,
          body.aap-hotfix .aap-home-lead-card,
          body.aap-hotfix .aap-systems-shell,
          body.aap-hotfix .aap-reference-shell {
            background: rgba(255, 251, 246, 0.94) !important;
            border-color: rgba(23, 27, 32, 0.10) !important;
            box-shadow: 0 18px 42px rgba(17, 20, 24, 0.08) !important;
          }

          body.aap-hotfix .aap-live-main > div[class^="aap-"] .aap-panel {
            background: linear-gradient(145deg, #171b20, #2b353d) !important;
          }

          body.aap-hotfix .aap-live-main > div[class^="aap-"] .aap-panel h3,
          body.aap-hotfix .aap-live-main > div[class^="aap-"] .aap-panel p,
          body.aap-hotfix .aap-live-main > div[class^="aap-"] .aap-panel li,
          body.aap-hotfix .aap-live-main > div[class^="aap-"] .aap-panel a {
            color: #f2ede6 !important;
          }

          body.aap-hotfix .aap-home-lead-shell {
          background: linear-gradient(180deg, #fbfdff 0%, #eef5fb 100%) !important;
            border: 1px solid rgba(23, 27, 32, 0.10) !important;
            box-shadow: 0 22px 50px rgba(17, 20, 24, 0.08) !important;
          }

          body.aap-hotfix .aap-home-lead-kicker {
            background: rgba(183, 122, 48, 0.08) !important;
            border: 1px solid rgba(183, 122, 48, 0.20) !important;
            color: #8a5921 !important;
          }

          body.aap-hotfix .aap-home-lead-copy h2 {
            color: #171b20 !important;
          }

          body.aap-hotfix .aap-hp-field {
            border: 1px solid rgba(23, 27, 32, 0.14) !important;
            background: #fffdf8 !important;
            color: #22384b !important;
          }

          body.aap-hotfix .aap-hp-field::placeholder {
            color: #6b726f !important;
            opacity: 1 !important;
          }

          body.aap-hotfix .aap-live-main .aap-home-v3 .aap-hero-photo {
            object-fit: contain !important;
            padding: 18px !important;
            background: linear-gradient(180deg, #ebe7de 0%, #f7f2ea 100%) !important;
          }

          body.aap-hotfix .aap-live-main .aap-home-v3 .aap-floating-panel {
            background: rgba(255, 250, 244, 0.96) !important;
            border-color: rgba(23, 27, 32, 0.08) !important;
            box-shadow: 0 22px 48px rgba(17, 20, 24, 0.16) !important;
          }

          body.aap-hotfix .aap-live-main .aap-home-v3 .aap-mini-stat {
            background: #f3ede3 !important;
          }

          body.aap-hotfix .aap-live-main .aap-home-v3 .aap-flow-shell {
            padding: 28px !important;
            border-radius: 28px !important;
            border: 1px solid rgba(23, 27, 32, 0.10) !important;
            background: linear-gradient(180deg, #fffdf8 0%, #f4efe7 100%) !important;
            box-shadow: 0 22px 50px rgba(17, 20, 24, 0.08) !important;
          }

          body.aap-hotfix .aap-live-main .aap-home-v3 .aap-flow-track {
            display: grid !important;
            grid-template-columns: repeat(5, 1fr) !important;
            gap: 14px !important;
            margin-top: 22px !important;
          }

          body.aap-hotfix .aap-live-main .aap-home-v3 .aap-step {
            position: relative !important;
            padding: 18px 18px 16px !important;
            border-radius: 20px !important;
            border: 1px solid rgba(23, 27, 32, 0.08) !important;
            background: rgba(255, 255, 255, 0.82) !important;
            box-shadow: 0 12px 28px rgba(17, 20, 24, 0.06) !important;
          }

          body.aap-hotfix .aap-live-main .aap-home-v3 .aap-step::after {
            content: "" !important;
            position: absolute !important;
            right: -10px !important;
            top: 50% !important;
            width: 20px !important;
            height: 1px !important;
            background: linear-gradient(90deg, rgba(167, 108, 42, 0.50), rgba(167, 108, 42, 0)) !important;
            transform: translateY(-50%) !important;
          }

          body.aap-hotfix .aap-live-main .aap-home-v3 .aap-step:last-child::after {
            display: none !important;
          }

          body.aap-hotfix .aap-live-main .aap-home-v3 .aap-step span {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: 34px !important;
            height: 34px !important;
            border-radius: 999px !important;
            background: #171b20 !important;
            color: #fff !important;
            margin-bottom: 12px !important;
          }

          @media (max-width: 1200px) {
            body.aap-hotfix .aap-global-brand-copy em {
              display: none !important;
            }

            body.aap-hotfix .aap-live-main .aap-growth-path-grid {
              grid-template-columns: repeat(2, 1fr) !important;
            }
          }

          @media (max-width: 1440px) {
            body.aap-hotfix .aap-global-nav {
              display: none !important;
            }

            body.aap-hotfix .aap-menu-toggle {
              display: inline-flex !important;
            }
          }

          @media (max-width: 1120px) {
            body.aap-hotfix .aap-global-header-bar {
              grid-template-columns: auto auto !important;
            }

            body.aap-hotfix .aap-global-cta {
              display: none !important;
            }

            body.aap-hotfix .aap-live-footer-grid {
              grid-template-columns: 1fr 1fr !important;
            }

            body.aap-hotfix .aap-home-lead-shell,
            body.aap-hotfix .aap-live-main .aap-home-v3 .aap-flow-track {
              grid-template-columns: 1fr !important;
            }

            body.aap-hotfix .aap-live-main .aap-home-v3 .aap-step::after {
              display: none !important;
            }
          }

          @media (max-width: 767px) {
            body.aap-hotfix .aap-global-header-bar {
              grid-template-columns: minmax(0, 1fr) auto !important;
              min-height: 70px !important;
              padding: 12px 0 !important;
            }

            body.aap-hotfix .aap-global-brand-copy em {
              display: none !important;
            }

            body.aap-hotfix .aap-global-brand img {
              height: 46px !important;
              max-width: 230px !important;
            }

            body.aap-hotfix .aap-mobile-nav-panel {
              position: absolute !important;
              top: 100% !important;
              left: 0 !important;
              right: 0 !important;
              width: auto !important;
              margin: 0 !important;
              z-index: 1105 !important;
              max-height: calc(100vh - 90px) !important;
            }

            body.aap-hotfix .aap-live-footer-grid,
            body.aap-hotfix .aap-home-form-grid {
              grid-template-columns: 1fr !important;
            }

            body.aap-hotfix .aap-live-main .grid-4 {
              grid-template-columns: 1fr !important;
            }

            body.aap-hotfix .aap-live-main .contact-card {
              min-width: 0 !important;
              width: 100% !important;
              padding: 1.2rem !important;
            }

            body.aap-hotfix .aap-live-main .aap-growth-path-grid {
              grid-template-columns: 1fr !important;
            }

            body.aap-hotfix .aap-home-form-actions {
              display: grid !important;
              grid-template-columns: 1fr !important;
            }

            body.aap-hotfix .aap-live-main .aap-growth-path-actions {
              display: grid !important;
              grid-template-columns: 1fr !important;
            }

            body.aap-hotfix .aap-home-form-submit,
            body.aap-hotfix .aap-home-form-secondary,
            body.aap-hotfix .aap-live-main .aap-growth-path-primary,
            body.aap-hotfix .aap-live-main .aap-growth-path-secondary {
              width: 100% !important;
            }

            body.aap-hotfix .aap-live-main .aap-growth-path-card {
              min-width: 0 !important;
              width: 100% !important;
              overflow-wrap: anywhere !important;
              word-break: normal !important;
            }
          }
        }
      </style>
      <?php
  }

function aap_hotfix_print_shared_layout_css() {
    ?>
    <style id="aap-hotfix-layout-css">
      body.aap-hotfix .aap-live-main {
        color: #22384b;
      }

      body.aap-hotfix .aap-live-main .hero,
      body.aap-hotfix .aap-live-main .page-intro,
      body.aap-hotfix .aap-live-main .section {
        padding: 5.25rem 0;
      }

      body.aap-hotfix .aap-live-main .section-tight {
        padding: 4rem 0;
      }

      body.aap-hotfix .aap-live-main .section-sky {
        background: linear-gradient(180deg, #fbfdff 0%, #eef5fb 100%);
      }

      body.aap-hotfix .aap-live-main .section-sand {
        background: linear-gradient(180deg, #ffffff 0%, #f7fafc 100%);
      }

      body.aap-hotfix .aap-live-main .section-forest {
        background: linear-gradient(180deg, #f8fbf7 0%, #eff5eb 100%);
      }

      body.aap-hotfix .aap-live-main .section-sea {
        background: linear-gradient(180deg, #f8fcfb 0%, #edf8f6 100%);
      }

      body.aap-hotfix .aap-live-main .hero-grid,
      body.aap-hotfix .aap-live-main .split-layout,
      body.aap-hotfix .aap-live-main .contact-grid {
        display: grid;
        gap: 1.75rem;
        align-items: start;
      }

      body.aap-hotfix .aap-live-main .hero-grid {
        grid-template-columns: minmax(0, 1.1fr) minmax(320px, 0.9fr);
      }

      body.aap-hotfix .aap-live-main .split-layout {
        grid-template-columns: minmax(0, 1fr) minmax(280px, 0.9fr);
      }

      body.aap-hotfix .aap-live-main .contact-grid {
        grid-template-columns: minmax(0, 1fr) minmax(320px, 0.95fr);
      }

      body.aap-hotfix .aap-live-main .grid-2,
      body.aap-hotfix .aap-live-main .grid-3,
      body.aap-hotfix .aap-live-main .grid-4,
      body.aap-hotfix .aap-live-main .article-grid,
      body.aap-hotfix .aap-live-main .callout-row,
      body.aap-hotfix .aap-live-main .process-grid,
      body.aap-hotfix .aap-live-main .proof-strip {
        display: grid;
        gap: 1.5rem;
      }

      body.aap-hotfix .aap-live-main .grid-2 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }

      body.aap-hotfix .aap-live-main .grid-3,
      body.aap-hotfix .aap-live-main .article-grid,
      body.aap-hotfix .aap-live-main .callout-row,
      body.aap-hotfix .aap-live-main .process-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
      }

      body.aap-hotfix .aap-live-main .grid-4,
      body.aap-hotfix .aap-live-main .proof-strip {
        grid-template-columns: repeat(4, minmax(0, 1fr));
      }

      body.aap-hotfix .aap-live-main .card,
      body.aap-hotfix .aap-live-main .feature-card,
      body.aap-hotfix .aap-live-main .resource-card,
      body.aap-hotfix .aap-live-main .contact-card,
      body.aap-hotfix .aap-live-main .process-step,
      body.aap-hotfix .aap-live-main .proof-card,
      body.aap-hotfix .aap-live-main .stat-card,
      body.aap-hotfix .aap-live-main .aside-panel,
      body.aap-hotfix .aap-live-main .quote-card,
      body.aap-hotfix .aap-live-main .hero-panel,
      body.aap-hotfix .aap-live-main .form-card,
      body.aap-hotfix .aap-live-main .cta-panel,
      body.aap-hotfix .aap-live-main .image-card,
      body.aap-hotfix .aap-live-main .service-card {
        background: rgba(255, 255, 255, 0.96);
        border: 1px solid rgba(17, 43, 69, 0.1);
        border-radius: 22px;
        box-shadow: 0 12px 30px rgba(17, 43, 69, 0.06);
      }

      body.aap-hotfix .aap-live-main .card,
      body.aap-hotfix .aap-live-main .feature-card,
      body.aap-hotfix .aap-live-main .resource-card,
      body.aap-hotfix .aap-live-main .contact-card,
      body.aap-hotfix .aap-live-main .process-step,
      body.aap-hotfix .aap-live-main .proof-card,
      body.aap-hotfix .aap-live-main .stat-card,
      body.aap-hotfix .aap-live-main .aside-panel,
      body.aap-hotfix .aap-live-main .quote-card,
      body.aap-hotfix .aap-live-main .hero-panel,
      body.aap-hotfix .aap-live-main .service-card {
        padding: 1.75rem;
      }

      body.aap-hotfix .aap-live-main .form-card,
      body.aap-hotfix .aap-live-main .cta-panel,
      body.aap-hotfix .aap-live-main .image-card {
        padding: 2rem;
      }

      body.aap-hotfix .aap-live-main .hero-panel {
        background: radial-gradient(circle at top right, rgba(31, 95, 168, 0.18), transparent 28%),
                    radial-gradient(circle at bottom left, rgba(207, 135, 84, 0.16), transparent 22%),
                    linear-gradient(145deg, #12355b, #1d588f);
        color: #eff5ff;
        box-shadow: 0 28px 72px rgba(17, 43, 69, 0.14);
        overflow: hidden;
      }

      body.aap-hotfix .aap-live-main .hero-panel h2,
      body.aap-hotfix .aap-live-main .hero-panel h3,
      body.aap-hotfix .aap-live-main .hero-panel strong {
        color: #fff;
      }

      body.aap-hotfix .aap-live-main .hero-panel p,
      body.aap-hotfix .aap-live-main .hero-panel li {
        color: rgba(239, 245, 255, 0.88);
      }

      body.aap-hotfix .aap-live-main .section-heading {
        max-width: 980px;
        margin-bottom: 2rem;
      }

      body.aap-hotfix .aap-live-main .section-heading h1,
      body.aap-hotfix .aap-live-main .section-heading h2,
      body.aap-hotfix .aap-live-main .section-heading h3,
      body.aap-hotfix .aap-live-main .hero-copy h1 {
        margin: 0.85rem 0 1rem;
        font-family: "Iowan Old Style", "Palatino Linotype", "Book Antiqua", Georgia, serif;
        line-height: 1.02;
        letter-spacing: -0.038em;
        color: #171b20;
      }

      body.aap-hotfix .aap-live-main .hero-copy h1,
      body.aap-hotfix .aap-live-main .section-heading h1 {
        font-size: clamp(2.55rem, 4.2vw, 4.3rem);
        max-width: 15.8ch;
      }

      body.aap-hotfix .aap-live-main .section-heading h2 {
        font-size: clamp(2.05rem, 4vw, 3.3rem);
      }

      body.aap-hotfix .aap-live-main .section-heading h3 {
        font-size: clamp(1.4rem, 2vw, 1.8rem);
      }

      body.aap-hotfix .aap-live-main .section-heading p,
      body.aap-hotfix .aap-live-main .lede,
      body.aap-hotfix .aap-live-main .text-lg {
        max-width: 72ch;
        color: #607486;
        font-size: 1.08rem;
      }

      body.aap-hotfix .aap-live-main .text-limit {
        max-width: 68ch;
      }

      body.aap-hotfix .aap-live-main .lede {
        font-size: 1.12rem;
      }

      body.aap-hotfix .aap-live-main .check-list,
      body.aap-hotfix .aap-live-main .detail-list {
        margin: 1rem 0 0;
        padding: 0;
        list-style: none;
      }

      body.aap-hotfix .aap-live-main .check-list li,
      body.aap-hotfix .aap-live-main .detail-list li {
        position: relative;
        margin: 0 0 0.95rem;
        padding-left: 1.6rem;
      }

      body.aap-hotfix .aap-live-main .check-list li::before,
      body.aap-hotfix .aap-live-main .detail-list li::before {
        content: "";
        position: absolute;
        left: 0;
        top: 0.7rem;
        width: 0.8rem;
        height: 0.8rem;
        border-radius: 50%;
        background: linear-gradient(135deg, #1f5fa8, #197d89);
        transform: translateY(-50%);
        box-shadow: 0 0 0 5px rgba(31, 95, 168, 0.1);
      }

      body.aap-hotfix .aap-live-main .bullet-list {
        display: grid;
        gap: 0.8rem;
        margin: 1rem 0 0;
        padding-left: 1.2rem;
      }

      body.aap-hotfix .aap-live-main .hero-actions,
      body.aap-hotfix .aap-live-main .link-list,
      body.aap-hotfix .aap-live-main .pill-row,
      body.aap-hotfix .aap-live-main .service-pills,
      body.aap-hotfix .aap-live-main .meta-list {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
      }

      body.aap-hotfix .aap-live-main .link-list a,
      body.aap-hotfix .aap-live-main .pill-row span,
      body.aap-hotfix .aap-live-main .service-pills span,
      body.aap-hotfix .aap-live-main .meta-list li {
        display: inline-flex;
        align-items: center;
        min-height: 42px;
        padding: 0 1rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.88);
        border: 1px solid rgba(17, 43, 69, 0.1);
        color: #123f6f;
        font-weight: 700;
      }

      body.aap-hotfix .aap-live-main .link-list a:hover {
        transform: translateY(-2px);
        background: #fff;
      }

      body.aap-hotfix .aap-live-main .trust-strip,
      body.aap-hotfix .aap-live-main .strip {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 1rem;
      }

      body.aap-hotfix .aap-live-main .strip-card {
        padding: 1.25rem 1.1rem;
        border-radius: 16px;
        border: 1px solid rgba(17, 43, 69, 0.1);
        background: rgba(255, 255, 255, 0.72);
        box-shadow: 0 12px 30px rgba(17, 43, 69, 0.06);
      }

      body.aap-hotfix .aap-live-main .strip-card strong {
        display: block;
        margin-bottom: 0.45rem;
        color: #171b20;
      }

      body.aap-hotfix .aap-live-main .form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
      }

      body.aap-hotfix .aap-live-main .field {
        display: grid;
        gap: 7px;
      }

      body.aap-hotfix .aap-live-main .field-full {
        grid-column: 1 / -1;
      }

      body.aap-hotfix .aap-live-main .form-grid label {
        color: #17344f;
        font-size: 13px;
        font-weight: 700;
      }

      body.aap-hotfix .aap-live-main .lead-form input,
      body.aap-hotfix .aap-live-main .lead-form select,
      body.aap-hotfix .aap-live-main .lead-form textarea,
      body.aap-hotfix .aap-live-main .form-grid input,
      body.aap-hotfix .aap-live-main .form-grid select,
      body.aap-hotfix .aap-live-main .form-grid textarea {
        width: 100%;
        min-height: 50px;
        padding: 14px;
        border-radius: 14px;
        border: 1px solid rgba(23, 27, 32, 0.14);
        background: #fffdf8;
        color: #22384b;
      }

      body.aap-hotfix .aap-live-main .lead-form textarea,
      body.aap-hotfix .aap-live-main .form-grid textarea {
        min-height: 128px;
        resize: vertical;
      }

      body.aap-hotfix .aap-live-main .form-note,
      body.aap-hotfix .aap-live-main .resource-meta {
        color: #8a5921;
        font-size: 0.8rem;
        font-weight: 700;
        letter-spacing: 0.07em;
        text-transform: uppercase;
      }

      body.aap-hotfix .aap-live-main .faq-list {
        display: grid;
        gap: 12px;
      }

      body.aap-hotfix .aap-live-main .faq-item {
        padding: 18px 20px;
        border: 1px solid rgba(17, 43, 69, 0.1);
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.95);
        box-shadow: 0 12px 30px rgba(17, 43, 69, 0.06);
      }

      body.aap-hotfix .aap-live-main .faq-item summary {
        cursor: pointer;
        font-weight: 800;
        color: #102841;
      }

      body.aap-hotfix .aap-live-main .faq-content {
        padding-top: 12px;
        color: #607387;
      }

      @media (max-width: 1100px) {
        body.aap-hotfix .aap-live-main .hero-grid,
        body.aap-hotfix .aap-live-main .split-layout,
        body.aap-hotfix .aap-live-main .contact-grid,
        body.aap-hotfix .aap-live-main .grid-2,
        body.aap-hotfix .aap-live-main .grid-3,
        body.aap-hotfix .aap-live-main .grid-4,
        body.aap-hotfix .aap-live-main .proof-strip,
        body.aap-hotfix .aap-live-main .trust-strip {
          grid-template-columns: 1fr;
        }

        body.aap-hotfix .aap-live-main .hero-copy h1,
        body.aap-hotfix .aap-live-main .section-heading h1 {
          font-size: clamp(2.15rem, 6.8vw, 3.3rem);
          max-width: 11.6ch;
        }

        body.aap-hotfix .aap-live-main .section-heading h2 {
          font-size: clamp(1.9rem, 5vw, 2.7rem);
        }
      }

      @media (max-width: 767px) {
        body.aap-hotfix .aap-live-main .hero,
        body.aap-hotfix .aap-live-main .page-intro,
        body.aap-hotfix .aap-live-main .section {
          padding: 4rem 0;
        }

        body.aap-hotfix .aap-live-main .section-tight {
          padding: 3rem 0;
        }

        body.aap-hotfix .aap-live-main .form-grid {
          grid-template-columns: 1fr;
        }

        body.aap-hotfix .aap-live-main .hero-actions {
          display: grid;
          gap: 12px;
        }

        body.aap-hotfix .aap-live-main .hero-actions .button,
        body.aap-hotfix .aap-live-main .hero-actions .button-secondary,
        body.aap-hotfix .aap-live-main .hero-actions .button-ghost {
          width: 100%;
        }
      }
    </style>
    <?php
}

function aap_hotfix_print_footer_hotfixes() {
    ?>
    <script id="aap-hotfix-js">
    (function(){
      document.body.classList.add('aap-hotfix');

      var contactPath = '/contact/#aap-contact-form';
      var legacyMap = {
        '/contact-us/': '/contact/',
        '/practice-resources/': '/resources/',
        '/about-us/': '/about/',
        '/mental-health-marketing/': '/practice-operations/',
        '/mental-health-billing-services/': '/mental-health-billing/',
        '/timely-filing-how-does-it-work-and-what-are-the-3-types-of-limits/': '/timely-filing-guide/',
        '/current-systems/': '/ehr-workflow-optimization/',
        '/credentialing-accelerator/': '/credentialing/',
        '/ai-revenue-cycle/': '/revenue-cycle-management/',
        '/practice-automation-consulting/': '/practice-operations/'
      };

      document.querySelectorAll('a[href]').forEach(function(link){
        var href = link.getAttribute('href');
        if (!href) return;
        if (legacyMap[href]) link.setAttribute('href', legacyMap[href]);
        if (href.indexOf('mailto:') === 0) {
          if (!link.classList.contains('aap-footer-contact-link')) {
            link.setAttribute('href', contactPath);
            link.textContent = 'Use the Contact Form';
          }
        }
        if ((link.textContent || '').trim() === 'Email AdvanceAPractice') {
          if (!link.classList.contains('aap-footer-contact-link')) {
            link.setAttribute('href', contactPath);
            link.textContent = 'Use the Contact Form';
          }
        }
      });

      var currentPath = window.location.pathname.replace(/\/+$/, '/') || '/';
      document.querySelectorAll('.aap-global-nav a, .aap-mobile-nav-panel a').forEach(function(link){
        var href = link.getAttribute('href');
        if (!href) return;
        var normalized = href.replace(/^https?:\/\/[^/]+/,'').replace(/\/+$/, '/') || '/';
        if (normalized === currentPath) {
          link.classList.add('current');
        }
      });

      function suppressOfferPopup() {
        ['#aap-newsletter-popup-root', '#aap-offer-dialog', '.aap-newsletter-popup-root', '.aap-newsletter-popup-overlay'].forEach(function(selector){
          document.querySelectorAll(selector).forEach(function(node){
            node.classList.remove('is-open');
            node.setAttribute('hidden', 'hidden');
            node.style.display = 'none';
            node.style.visibility = 'hidden';
            node.style.pointerEvents = 'none';
          });
        });

        document.documentElement.classList.remove('aap-popup-open');
        document.body.classList.remove('aap-popup-open');

        try {
          localStorage.setItem('aap-offer-dialog-dismissed', '1');
          localStorage.setItem('aap-offer-dialog-shown', '1');
          sessionStorage.setItem('aap-offer-dialog-dismissed', '1');
          sessionStorage.setItem('aap-offer-dialog-shown', '1');
        } catch (error) {}
      }

      function runLateCleanup() {
        if (window.location.pathname === '/credentialing-accelerator/' || window.location.pathname === '/credentialing/') {
          var strayWidget = document.querySelector('.elementor-element-341f3ef9');
          if (strayWidget) {
            strayWidget.remove();
          }
          var strayWrap = document.querySelector('.elementor-element-222f755a');
          if (strayWrap && ((strayWrap.textContent || '').indexOf('[aap_v6_managed_page]') !== -1)) {
            strayWrap.remove();
          }
        }

        ['.elementor-element', 'section', 'div', 'p', 'span'].forEach(function(selector){
          document.querySelectorAll(selector).forEach(function(node){
            if ((node.textContent || '').trim() !== '[aap_v6_managed_page]') return;
            var section = node.closest('.elementor-element, section, div');
            if (section) {
              section.remove();
            } else {
              node.remove();
            }
          });
        });

        if (window.location.pathname === '/resources/') {
          document.querySelectorAll('.aap-meta').forEach(function(node){
            var text = (node.textContent || '').trim();
            if (text === 'ARTICLE' || text === 'Article') {
              node.textContent = 'Featured Insight';
            }
          });
        }
      }

      var menuButton = document.querySelector('.aap-menu-toggle');
      var mobileMenu = document.getElementById('aap-mobile-menu');
      if (menuButton && mobileMenu) {
        function closeMenu() {
          document.body.classList.remove('aap-menu-open');
          menuButton.setAttribute('aria-expanded', 'false');
          mobileMenu.setAttribute('hidden', 'hidden');
        }

        function openMenu() {
          document.body.classList.add('aap-menu-open');
          menuButton.setAttribute('aria-expanded', 'true');
          mobileMenu.removeAttribute('hidden');
        }

        menuButton.addEventListener('click', function(){
          var isOpen = menuButton.getAttribute('aria-expanded') === 'true';
          if (isOpen) {
            closeMenu();
          } else {
            openMenu();
          }
        });

        mobileMenu.querySelectorAll('a').forEach(function(link){
          link.addEventListener('click', closeMenu);
        });

        document.addEventListener('keydown', function(event){
          if (event.key === 'Escape') {
            closeMenu();
          }
        });

        document.addEventListener('click', function(event){
          var isOpen = menuButton.getAttribute('aria-expanded') === 'true';
          if (!isOpen) return;
          if (mobileMenu.contains(event.target) || menuButton.contains(event.target)) return;
          closeMenu();
        });
      }

      runLateCleanup();
      suppressOfferPopup();

        var badCard = Array.from(document.querySelectorAll('.contact-card')).find(function(card){
          return (card.textContent || '').indexOf('Founding client access') !== -1;
        });
        if (badCard) badCard.remove();

        var roleField = document.querySelector('input[name=\"decision_maker_role\"]');
        if (roleField) roleField.setAttribute('placeholder', 'Owner, admin, ops lead');

        var ehrField = document.querySelector('input[name=\"ehr_pm_system\"]');
        if (ehrField) ehrField.setAttribute('placeholder', 'Epic, Kareo, TN');

        if (window.location.pathname === '/mental-health-billing/') {
          var mhbImage = document.querySelector('.aap-visual img');
          if (mhbImage) {
            mhbImage.src = 'https://advanceapractice.com/wp-content/uploads/2025/08/1.png';
            mhbImage.alt = 'Behavioral health practice leaders in conversation about billing workflows and operations';
          }
        }

        if (window.location.pathname === '/ai-documentation/') {
          var docImage = document.querySelector('.aap-visual img');
          if (docImage) {
            docImage.src = 'https://advanceapractice.com/wp-content/uploads/2026/03/aap-documentation-dashboard.png';
            docImage.alt = 'AdvanceAPractice documentation workflow dashboard showing provider review queue and after-hours charting visibility';
          }
          var docNote = document.querySelector('.aap-visual-note span');
          if (docNote) {
            docNote.textContent = 'A product-style view of provider review queues, after-hours charting pressure, and documentation workflow visibility inside a cleaner operating model.';
          }
        }

        if (window.location.pathname === '/ai-revenue-cycle/' || window.location.pathname === '/revenue-cycle-management/') {
          var rcmImage = document.querySelector('.aap-visual img');
          if (rcmImage) {
            rcmImage.src = 'https://advanceapractice.com/wp-content/uploads/2026/03/aap-rcm-dashboard-1.png';
            rcmImage.alt = 'AdvanceAPractice revenue cycle dashboard showing claim status, denials, and payer follow-up reporting';
          }
          var rcmNote = document.querySelector('.aap-visual-note span');
          if (rcmNote) {
            rcmNote.textContent = 'A product-style view of claim status drift, denial follow-up, and revenue-cycle reporting built for real operational use.';
          }
        }

        if (window.location.pathname === '/contact/' || window.location.pathname === '/contact/index.php') {
          var contactGrid = document.querySelector('.contact-grid');
          var contactFormCard = document.querySelector('.contact-grid .form-card');
          if (contactGrid && contactFormCard) {
            contactFormCard.style.order = '-1';
            contactFormCard.style.marginTop = '0';
            contactGrid.style.alignItems = 'start';
          }
        }

        if (window.location.pathname === '/practice-automation-consulting/' || window.location.pathname === '/practice-operations/') {
          var opsImages = document.querySelectorAll('img[src*="dji_export_1635712616724-1-scaled.jpg"]');
          if (opsImages.length > 1) {
            opsImages[1].src = 'https://advanceapractice.com/wp-content/uploads/2023/07/diverse-medical-team-of-doctors-looking-at-camera-while-holding-clipboard-and-medical-files-e1623252244361-pf2n5igpmfm4ppl9w5nt89pd7hae8a7fb8ryn904qo.jpg';
            opsImages[1].alt = 'Healthcare team image used to support AdvanceAPractice operations and workflow support';
          }

          if (window.innerWidth <= 767) {
            var growthGrid = document.querySelector('.aap-growth-path-grid');
            if (growthGrid) {
              growthGrid.style.gridTemplateColumns = '1fr';
              growthGrid.style.width = '100%';
              growthGrid.style.maxWidth = '100%';
              growthGrid.style.gap = '14px';
              growthGrid.querySelectorAll('.aap-growth-path-card').forEach(function(card){
                card.style.width = '100%';
                card.style.maxWidth = '100%';
                card.style.minWidth = '0';
                card.style.overflowWrap = 'anywhere';
                card.style.wordBreak = 'break-word';
              });
            }
          }
        }

      suppressOfferPopup();
      setTimeout(function(){
        runLateCleanup();
        suppressOfferPopup();
      }, 250);
    })();
    </script>
    <?php
}

function aap_hotfix_wpseo_title($title) {
    $map = aap_hotfix_meta_map();
    $slug = aap_hotfix_current_slug();
    if (isset($map[$slug]['title'])) {
        return $map[$slug]['title'];
    }
    return $title;
}

function aap_hotfix_wpseo_metadesc($description) {
    $map = aap_hotfix_meta_map();
    $slug = aap_hotfix_current_slug();
    if (isset($map[$slug]['description'])) {
        return $map[$slug]['description'];
    }
    return $description;
}

function aap_hotfix_wpseo_canonical($canonical) {
    $map = aap_hotfix_meta_map();
    $slug = aap_hotfix_current_slug();
    if (isset($map[$slug]['canonical'])) {
        return home_url($map[$slug]['canonical']);
    }
    return $canonical;
}

function aap_hotfix_wpseo_robots($robots) {
    if (is_search() || is_tag() || is_author() || is_date()) {
        return 'noindex,follow';
    }

    $slug = aap_hotfix_current_slug();
    $legacy = array('contact-us', 'practice-resources', 'mental-health-marketing', 'mental-health-billing-services', 'oregon-practice-manage');
    if (in_array($slug, $legacy, true) || strpos($slug, '__trashed') !== false) {
        return 'noindex,follow';
    }

    if (is_singular('post')) {
        return 'noindex,follow';
    }

    return $robots;
}

function aap_hotfix_exclude_from_sitemap($excluded) {
    if (!is_array($excluded)) {
        $excluded = array();
    }

    foreach (array(
        'contact-us',
        'practice-resources',
        'mental-health-marketing',
        'mental-health-billing-services',
        '__trashed',
        '__trashed-2',
        '__trashed-3',
        '__trashed-4',
        '__trashed-2__trashed',
        'mental-health-marketing__trashed/revenue-cycle-management',
        'mental-health-billing/timely-filing-how-does-it-work-and-what-are-the-3-types-of-limits',
        'oregon-practice-manage',
    ) as $slug) {
        $page = get_page_by_path($slug, OBJECT, array('page', 'post'));
        if ($page instanceof WP_Post) {
            $excluded[] = (int) $page->ID;
        }
    }

    return array_values(array_unique($excluded));
}

function aap_hotfix_document_title_separator() {
    return '|';
}

function aap_hotfix_current_slug() {
    if (is_front_page() || is_home()) {
        return 'home';
    }

    global $post;
    if ($post instanceof WP_Post) {
        return $post->post_name;
    }

    $request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '/';
    $path = wp_parse_url($request_uri, PHP_URL_PATH);
    $path = is_string($path) ? trim($path, '/') : '';

    if ($path === '') {
        return 'home';
    }

    $parts = explode('/', $path);
    return isset($parts[0]) ? sanitize_title($parts[0]) : '';
}

function aap_hotfix_meta_map() {
    return array(
        'home' => array(
            'title' => 'Behavioral Health Billing, Credentialing, Workflow & Practice Operations | AdvanceAPractice',
            'description' => 'AdvanceAPractice supports behavioral health and outpatient practices with billing, credentialing, revenue cycle management, workflow, and practice operations help built around real implementation.',
            'canonical' => '/',
        ),
        'about' => array(
            'title' => 'About AdvanceAPractice | Healthcare Operations Partner for Growing Practices',
            'description' => 'Learn how AdvanceAPractice supports behavioral health operations, implementation work, billing, provider onboarding, and current-systems optimization for growing practices nationwide.',
            'canonical' => '/about/',
        ),
        'contact' => array(
            'title' => 'Contact AdvanceAPractice | Billing, Credentialing, Revenue Cycle & Practice Operations Review',
            'description' => 'Request a billing, credentialing, revenue cycle, documentation workflow, current systems, or practice operations review for your practice.',
            'canonical' => '/contact/',
        ),
        'resources' => array(
            'title' => 'Healthcare Billing, Operations & Revenue Cycle Resources | AdvanceAPractice',
            'description' => 'Operator-minded resources on timely filing limits, corrected claim deadlines, billing workflow problems, credentialing, revenue cycle management, and EHR workflow optimization.',
            'canonical' => '/resources/',
        ),
        'ai-documentation' => array(
            'title' => 'AI Clinical Documentation Support & Workflow Review | AdvanceAPractice',
            'description' => 'Reduce charting burden, improve clinical documentation workflow, and keep review controls in place with AI documentation support for behavioral health and outpatient teams.',
            'canonical' => '/ai-documentation/',
        ),
        'ai-revenue-cycle' => array(
            'title' => 'Revenue Cycle Management & Reimbursement Workflow Improvement | AdvanceAPractice',
            'description' => 'Improve claim flow, denial patterns, payer follow-up, KPI reporting, and reimbursement workflow with revenue cycle management and optimization.',
            'canonical' => '/revenue-cycle-management/',
        ),
        'revenue-cycle-management' => array(
            'title' => 'Revenue Cycle Management & Reimbursement Workflow Improvement | AdvanceAPractice',
            'description' => 'Improve claim flow, denial patterns, payer follow-up, KPI reporting, and reimbursement workflow with revenue cycle management and optimization.',
            'canonical' => '/revenue-cycle-management/',
        ),
        'credentialing-accelerator' => array(
            'title' => 'Provider Credentialing Services, CAQH & Payer Enrollment | AdvanceAPractice',
            'description' => 'Organized provider credentialing, CAQH support, payer enrollment, revalidation, and provider onboarding for behavioral health and outpatient practices.',
            'canonical' => '/credentialing/',
        ),
        'credentialing' => array(
            'title' => 'Provider Credentialing Services, CAQH & Payer Enrollment | AdvanceAPractice',
            'description' => 'Organized provider credentialing, CAQH support, payer enrollment, revalidation, and provider onboarding for behavioral health and outpatient practices.',
            'canonical' => '/credentialing/',
        ),
        'practice-automation-consulting' => array(
            'title' => 'Practice Operations Support & Workflow Design | AdvanceAPractice',
            'description' => 'Practice operations support for patient access, scheduling, staffing handoffs, reporting discipline, and workflow design in growing behavioral health and outpatient practices.',
            'canonical' => '/practice-operations/',
        ),
        'practice-operations' => array(
            'title' => 'Practice Operations Support & Workflow Design | AdvanceAPractice',
            'description' => 'Practice operations support for patient access, scheduling, staffing handoffs, reporting discipline, and workflow design in growing behavioral health and outpatient practices.',
            'canonical' => '/practice-operations/',
        ),
        'mental-health-billing' => array(
            'title' => 'Mental Health Billing Services | AdvanceAPractice',
            'description' => 'Mental health billing services for therapy, psychiatry, PMHNP, and behavioral health practices that need cleaner claims, authorization coordination, denial management, and telehealth billing workflow support.',
            'canonical' => '/mental-health-billing/',
        ),
        'medical-billing' => array(
            'title' => 'Medical Billing Services for Outpatient Practices | AdvanceAPractice',
            'description' => 'Medical billing services for outpatient practices that need better claim flow, stronger A/R discipline, denial follow-up, payment posting discipline, and reimbursement workflow cleanup.',
            'canonical' => '/medical-billing/',
        ),
        'workflow-friction-audit' => array(
            'title' => 'Workflow Friction Audit for Billing, Credentialing & Operations | AdvanceAPractice',
            'description' => 'Find the patient-access, billing, credentialing, documentation, and ownership breakdowns that quietly slow reimbursement and operations in growing behavioral health and outpatient practices.',
            'canonical' => '/workflow-friction-audit/',
        ),
        'behavioral-health-billing-pmhnp-groups' => array(
            'title' => 'Behavioral Health Billing for PMHNP Groups | AdvanceAPractice',
            'description' => 'Behavioral health billing support for PMHNP groups that need cleaner telehealth workflows, stronger denial follow-up, and steadier reimbursement.',
            'canonical' => '/behavioral-health-billing-pmhnp-groups/',
        ),
        'multi-state-credentialing-outpatient-practices' => array(
            'title' => 'Scalable Credentialing for Multi-State Practices | AdvanceAPractice',
            'description' => 'Provider credentialing, CAQH, payer enrollment, and onboarding support for multi-state outpatient practices expanding across providers and markets.',
            'canonical' => '/multi-state-credentialing-outpatient-practices/',
        ),
        'starting-a-practice' => array(
            'title' => 'Support for Providers Starting a Practice | AdvanceAPractice',
            'description' => 'Private practice startup support for independent providers who need stronger billing, credentialing, workflow, and operational systems from the beginning.',
            'canonical' => '/starting-a-practice/',
        ),
        'growing-a-solo-practice' => array(
            'title' => 'Support for Growing a Solo Practice | AdvanceAPractice',
            'description' => 'Operational, billing, and workflow support for solo practice owners preparing to grow without creating more administrative chaos.',
            'canonical' => '/growing-a-solo-practice/',
        ),
        'adding-providers' => array(
            'title' => 'Adding Providers to a Small Practice | AdvanceAPractice',
            'description' => 'Credentialing, billing, onboarding, and operational support for small practices preparing to add providers and grow with stronger systems.',
            'canonical' => '/adding-providers/',
        ),
        'ehr-workflow-optimization' => array(
            'title' => 'Current Systems & EHR Workflow Optimization | AdvanceAPractice',
            'description' => 'EHR workflow optimization and current-systems review for practices using AdvancedMD, Epic, TherapyNotes, SimplePractice, athenahealth, Kareo or Tebra, Valant, Clinicient, and similar environments.',
            'canonical' => '/ehr-workflow-optimization/',
        ),
        'practice-workflow-review-checklist' => array(
            'title' => 'Practice Workflow Review Checklist | AdvanceAPractice',
            'description' => 'Use the Practice Workflow Review Checklist to review billing, credentialing, provider-readiness, workflow, and current-system gaps before booking time.',
            'canonical' => '/practice-workflow-review-checklist/',
        ),
        'timely-filing-guide' => array(
            'title' => 'Timely Filing Guide for Healthcare Practices | AdvanceAPractice',
            'description' => 'A practical guide to timely filing limits, corrected claim deadlines, appeal deadlines, and the workflow failures that create avoidable reimbursement loss.',
            'canonical' => '/timely-filing-guide/',
            'type' => 'article',
        ),
    );
}

function aap_hotfix_split_name($full_name) {
    $full_name = trim((string) $full_name);
    if ($full_name === '') {
        return array('first_name' => '', 'last_name' => '');
    }

    $parts = preg_split('/\s+/', $full_name, 2);
    return array(
        'first_name' => isset($parts[0]) ? $parts[0] : '',
        'last_name' => isset($parts[1]) ? $parts[1] : '',
    );
}

function aap_hotfix_post_value($key) {
    return isset($_POST[$key]) ? wp_unslash($_POST[$key]) : '';
}
