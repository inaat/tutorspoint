<?php
if (!defined('ABSPATH')) { exit; }

/**
 * Shortcode: [tp_terms_modal pdf="terms-privacy.pdf"]
 * - Renders a small, scrollable modal with “OK” and “Email” buttons.
 * - Opens when clicking any element with id="open-terms-modal" OR class="tp-open-terms".
 * - If an email input is required, add data-email-input="#yourEmailSelector" to the trigger link
 *   or pass email_input="#yourEmailSelector" to the shortcode as a default fallback.
 */

add_shortcode('tp_terms_modal', function($atts){
  $atts = shortcode_atts([
    'pdf'         => 'terms-privacy.pdf',                // file name in /wp-content/uploads/docs/
    'email_input' => '',                                  // optional default selector (e.g. #signupEmail)
  ], $atts, 'tp_terms_modal');

  // Build public URL to the PDF in uploads/docs/
  $pdf_file  = trim($atts['pdf']);
  $pdf_url   = content_url('/uploads/docs/' . ltrim($pdf_file, '/'));
  $email_sel = trim($atts['email_input']);

  ob_start(); ?>
  <div id="tp-terms-modal" class="tp-terms-modal" aria-hidden="true">
    <div class="tp-terms-box" role="dialog" aria-modal="true" aria-labelledby="tp-terms-title">
      <div class="tp-terms-topbar">
        <button type="button" class="tp-terms-close" title="OK">OK</button>

        <button type="button" class="tp-terms-email" title="Email PDF">Email</button>

        <!-- mobile hamburger -->
        <button type="button" class="tp-terms-menu" aria-label="Menu">
          <span></span><span></span><span></span>
        </button>
      </div>

      <h3 id="tp-terms-title" class="tp-terms-title">Terms &amp; Privacy Policy</h3>

      <div class="tp-terms-viewport">
        <iframe
          src="<?php echo esc_url($pdf_url); ?>"
          class="tp-terms-iframe"
          loading="lazy"
          title="Terms &amp; Privacy PDF"
        ></iframe>
      </div>
    </div>
  </div>

  <div id="tp-terms-toast"></div>

  <style>
    /* container */
    #tp-terms-modal{position:fixed;inset:0;background:rgba(0,0,0,.55);display:none;place-items:center;z-index:999999}
    #tp-terms-modal.open{display:grid}

    .tp-terms-box{
      width:min(680px,94vw);
      height:min(520px,88vh);
      background:#0b1220;
      color:#e5e7eb;
      border-radius:14px;
      box-shadow:0 18px 48px rgba(2,8,23,.5);
      display:flex;flex-direction:column;
      overflow:hidden;
      border:1px solid rgba(255,255,255,.08);
      font-size:12px; /* small font */
    }
    .tp-terms-topbar{
      display:flex;gap:8px;align-items:center;justify-content:flex-end;
      padding:8px 10px;border-bottom:1px solid rgba(255,255,255,.08);
      background:transparent;
    }
    .tp-terms-topbar button{
      background:transparent;color:#e5e7eb;border:1px solid rgba(255,255,255,.2);
      padding:6px 10px;border-radius:10px;cursor:pointer;font-size:12px
    }
    .tp-terms-topbar button:hover{background:rgba(255,255,255,.06)}
    .tp-terms-menu{display:none}
    .tp-terms-menu span{display:block;width:16px;height:2px;background:#e5e7eb;margin:3px 0}
    @media(max-width:520px){ .tp-terms-menu{display:inline-block} }

    .tp-terms-title{margin:8px 10px 6px;font-weight:700;font-size:13px}
    .tp-terms-viewport{flex:1;min-height:0;border-top:1px solid rgba(255,255,255,.06)}
    .tp-terms-iframe{
      width:100%;height:100%;border:0;background:#0b0b0b;
      /* scrollbars inside iframe content are handled by the browser;
         the outer box will also allow scrolling if iframe overflows */
    }

    /* tiny toast */
    #tp-terms-toast{
      position:fixed;left:50%;bottom:22px;transform:translateX(-50%);
      background:#111827;color:#f9fafb;border:1px solid #374151;border-radius:10px;
      padding:8px 12px;font-size:12px;display:none;z-index:1000000
    }
  </style>

  <script>
  (function(){
    const $ = window.jQuery;
    if (!$) { return; }

    const MOD   = $('#tp-terms-modal');
    const BOX   = MOD.find('.tp-terms-box');
    const BTN_OK= MOD.find('.tp-terms-close');
    const BTN_EM= MOD.find('.tp-terms-email');
    const TOAST = $('#tp-terms-toast');
    const PDF   = <?php echo json_encode($pdf_url); ?>;
    const FALLBACK_EMAIL_SEL = <?php echo json_encode($email_sel); ?>;

    function toast(msg){
      TOAST.stop(true,true).text(msg).fadeIn(120,function(){
        setTimeout(()=>TOAST.fadeOut(200), 2200);
      });
    }

    // Helper: find the email input using (1) data-email-input on trigger, (2) shortcode default, (3) common IDs
    function resolveEmailInput(trigger){
      let sel = trigger?.dataset?.emailInput || FALLBACK_EMAIL_SEL || '';
      let $in = sel ? $(sel) : $();
      if (!$in.length) {
        $in = $('#reg_email, #signupEmail, input[type="email"]').first();
      }
      return $in;
    }

    // Public API so you can open programmatically if needed
    window.tpOpenTerms = function(){ MOD.addClass('open').attr('aria-hidden','false'); };

    // Close
    function close(){ MOD.removeClass('open').attr('aria-hidden','true'); }
    BTN_OK.on('click', close);
    MOD.on('click', function(e){ if (e.target === this) close(); }); // click outside box closes

    // Email PDF via AJAX
    BTN_EM.on('click', function(){
      const trigger = window._tp_last_terms_trigger || null;
      const $in = resolveEmailInput(trigger);
      let email = ($in.val()||'').trim();

      if (!email) {
        email = window.prompt('Enter your email to receive the PDF:','');
        if (!email) { toast('Email is required.'); return; }
        // try to populate the real input too
        const $target = resolveEmailInput(trigger);
        if ($target.length) { $target.val(email).trigger('input'); }
      }
      // Basic client validation
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { toast('Please enter a valid email.'); return; }

      $.post(<?php echo json_encode(admin_url('admin-ajax.php')); ?>, {
        action: 'tp_send_terms_pdf',
        email:  email,
        pdf:    PDF,
        _ajax_nonce: <?php echo json_encode(wp_create_nonce('tp_terms_mail')); ?>
      })
      .done(function(res){
        try{ if(typeof res==='string') res = JSON.parse(res); }catch(_){}
        if (res && res.success) { toast('Sent! Please check your inbox.'); }
        else { toast((res && res.data && res.data.message) || 'Unable to send email.'); }
      })
      .fail(function(){ toast('Network error while sending.'); });
    });

    // Open when clicking id="open-terms-modal" or class="tp-open-terms"
    $(document).on('click', '#open-terms-modal, .tp-open-terms', function(e){
      e.preventDefault();
      window._tp_last_terms_trigger = this; // remember for email-input fallback

      const $in = resolveEmailInput(this);
      const email = ($in.val()||'').trim();

      // If user clicked the link (not the Email button) and email is empty, show toast + focus
      if (!email) {
        toast('Please enter your email first.');
        if ($in.length) { $in.trigger('focus'); }
        return;
      }

      MOD.addClass('open').attr('aria-hidden','false');
    });
  })();
  </script>
  <?php
  return ob_get_clean();
});

/* ---------- AJAX: email the PDF attachment ---------- */
add_action('wp_ajax_tp_send_terms_pdf',        'tp_send_terms_pdf');
add_action('wp_ajax_nopriv_tp_send_terms_pdf', 'tp_send_terms_pdf');
function tp_send_terms_pdf(){
  if (!isset($_POST['_ajax_nonce']) || !wp_verify_nonce($_POST['_ajax_nonce'], 'tp_terms_mail')) {
    wp_send_json_error(['message'=>'Security check failed.'], 403);
  }

  $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
  $pdf   = isset($_POST['pdf'])   ? esc_url_raw($_POST['pdf'])      : '';
  if (!$email || !is_email($email)) wp_send_json_error(['message'=>'Invalid email.']);
  if (!$pdf) wp_send_json_error(['message'=>'Missing PDF.']);

  // Try to map URL to local path for attachment
  $uploads = wp_get_upload_dir();
  $local   = str_replace($uploads['baseurl'], $uploads['basedir'], $pdf);
  $attach  = file_exists($local) ? [$local] : [];

  $sent = wp_mail(
    $email,
    'Tutors Point — Terms & Privacy Policy',
    "Hello,\n\nPlease find attached our Terms & Privacy Policy.\n\nRegards,\nTutors Point",
    ['Content-Type: text/plain; charset=UTF-8'],
    $attach
  );

  if ($sent) wp_send_json_success();
  wp_send_json_error(['message'=>'Mail send failed.']);
}
