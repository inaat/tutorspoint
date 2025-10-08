<?php
/**
 * Custom Forgot / Reset Password flow for Tutors Point
 * File: /wp-content/themes/your-child-theme/General/passwords.php
 */
if (!defined('ABSPATH')) exit;

/** Slugs of your custom pages (make sure the pages exist in WP Pages) */
const TP_FORGOT_SLUG = 'forgot-password'; // <-- your actual page slug
const TP_RESET_SLUG  = 'reset-password';  // <-- your actual page slug

/** Helpers */
function tp_forgot_url() { return home_url('/' . TP_FORGOT_SLUG . '/'); }
function tp_reset_url($args = []) {
  $url = home_url('/' . TP_RESET_SLUG . '/');
  return $args ? add_query_arg($args, $url) : $url;
}

/** 301 fix: if someone hits the wrong spelling /forget-password/, send to /forgot-password/ */
add_action('template_redirect', function () {
  // Wrong -> right
  if (trim($_SERVER['REQUEST_URI'], '/') === 'forget-password') {
    wp_safe_redirect(tp_forgot_url(), 301);
    exit;
  }
});

/** Send all “lost password” links to our custom page */
add_filter('lostpassword_url', function($url, $redirect) {
  return tp_forgot_url();
}, 10, 2);

add_filter('woocommerce_get_lost_password_url', function($url){
  return tp_forgot_url();
});

/** If user reaches Woo default endpoint, redirect to our page */
add_action('template_redirect', function () {
  if (function_exists('wc_get_page_permalink')) {
    $myacc = wc_get_page_permalink('myaccount');
    if ($myacc) {
      $wc_lost = trailingslashit($myacc) . 'lost-password/';
      $here    = trailingslashit(home_url(add_query_arg([])));
      if (strpos($here, $wc_lost) === 0) {
        wp_safe_redirect(tp_forgot_url(), 301);
        exit;
      }
    }
  }
});

/** Make the email’s reset link point to /reset-password/?key=..&login=.. */
add_filter('retrieve_password_message', function ($message, $key, $user_login, $user_data) {
  $custom = tp_reset_url([
    'key'   => rawurlencode($key),
    'login' => rawurlencode($user_login),
  ]);

  // Replace any default rp link with ours
  $message = preg_replace('~https?://[^\s<>"]+action=rp[^\s<>"]+~i', esc_url($custom), $message);

  // If none found, append ours
  if (strpos($message, $custom) === false) {
    $message .= "\n\n" . esc_url($custom) . "\n";
  }
  return $message;
}, 10, 4);

/* ---------- Shortcode: [tp_forgot_password] ---------- */
add_shortcode('tp_forgot_password', function () {
  $nonce = wp_create_nonce('tp_fp_nonce');
  ob_start(); ?>
  <div class="tp-fp-wrap" id="tp-fp">
    <h5 class="tp-fp-heading">Forgot your password?</h5>
    <p class="tp-fp-sub">Enter your email or username and we’ll email you a reset link.</p>

    <form id="tp-fp-form" class="tp-fp-form" novalidate>
      <input type="hidden" name="action" value="tp_forgot_password">
      <input type="hidden" name="tp_nonce" value="<?php echo esc_attr($nonce); ?>">
      <div class="tp-fp-field">
        <label for="tp-fp-login">Email or Username</label>
        <input id="tp-fp-login" name="login" type="text" required placeholder="you@example.com">
      </div>
      <button class="tp-fp-btn" type="submit">Send reset link</button>
      <div class="tp-fp-msg" id="tp-fp-msg" role="status" aria-live="polite"></div>
    </form>
  </div>

  <style>
    .tp-fp-wrap { max-width: 480px; margin: 0 auto; }
    .tp-fp-heading { margin: 0 0 6px; font-weight: 400; }
    .tp-fp-sub { margin: 0 0 12px; color: #6b7280; font-size: 12px; }
    .tp-fp-field { display: grid; gap: 6px; margin: 8px 0 12px; }
    .tp-fp-field label { font-size: 12px; color: #6b7280; font-weight: 400; }
    .tp-fp-field input { width:100%; padding:10px 12px; border-radius:10px; border:1px solid #d1d5db; background:#fff; font-size:12px; }
    .tp-fp-btn { appearance:none; border:0; padding:10px 14px; border-radius:10px; background:linear-gradient(135deg,#10b981,#22d3ee); color:#fff; cursor:pointer; font-size:12px; font-weight:400; }
    .tp-fp-btn:hover { filter:brightness(1.05); }
    .tp-fp-msg { margin-top:10px; font-size:12px; }
    .tp-fp-msg.ok { color:#059669; }
    .tp-fp-msg.err { color:#dc2626; }
  </style>

  <script>
  (function(){
    const form = document.getElementById('tp-fp-form');
    const msg  = document.getElementById('tp-fp-msg');
    if(!form) return;
    form.addEventListener('submit', async (e)=>{
      e.preventDefault();
      msg.className='tp-fp-msg'; msg.textContent='Sending…';
      try{
        const res  = await fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', { method:'POST', credentials:'same-origin', body: new FormData(form) });
        const json = await res.json();
        if(json.success){ msg.className='tp-fp-msg ok'; msg.textContent=json.data?.message||'Check your email for the reset link.'; }
        else{ msg.className='tp-fp-msg err'; msg.textContent=json.data?.message||'Could not send reset email.'; }
      }catch(_){ msg.className='tp-fp-msg err'; msg.textContent='Network error. Please try again.'; }
    });
  })();
  </script>
  <?php
  return ob_get_clean();
});

/** AJAX: send reset email */
add_action('wp_ajax_nopriv_tp_forgot_password', 'tp_forgot_password_cb');
function tp_forgot_password_cb(){
  if (!check_ajax_referer('tp_fp_nonce', 'tp_nonce', false)) {
    wp_send_json_error(['message'=>'Security check failed.']);
  }
  $login = isset($_POST['login']) ? trim(wp_unslash($_POST['login'])) : '';
  if ($login==='') wp_send_json_error(['message'=>'Please enter your email or username.']);

  $user = is_email($login) ? get_user_by('email',$login) : get_user_by('login',$login);
  if (!$user) wp_send_json_success(['message'=>'If an account exists for that email/username, a reset link has been sent.']);

  $key = get_password_reset_key($user);
  if (is_wp_error($key)) wp_send_json_error(['message'=>'Unable to start password reset.']);

  $blog  = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
  $subj  = sprintf(__('[%s] Password Reset','default'), $blog);
  $msg   = apply_filters('retrieve_password_message','', $key, $user->user_login, $user);

  if (!wp_mail($user->user_email, $subj, $msg)) {
    wp_send_json_error(['message'=>'Email could not be sent.']);
  }
  wp_send_json_success(['message'=>'Check your email for the reset link.']);
}

/* ---------- Shortcode: [tp_reset_password] ---------- */
add_shortcode('tp_reset_password', function () {
  $login = isset($_GET['login']) ? sanitize_text_field(wp_unslash($_GET['login'])) : '';
  $key   = isset($_GET['key'])   ? sanitize_text_field(wp_unslash($_GET['key']))   : '';
  $nonce = wp_create_nonce('tp_rp_nonce');

  if (!$login || !$key) {
    return '<p>Invalid reset link. Please use the link from your email or <a href="'.esc_url(tp_forgot_url()).'">request a new one</a>.</p>';
  }

  ob_start(); ?>
  <div class="tp-rp-wrap" id="tp-rp">
    <h5 class="tp-rp-heading">Choose a new password</h5>
    <form id="tp-rp-form" class="tp-rp-form" novalidate>
      <input type="hidden" name="action" value="tp_reset_password">
      <input type="hidden" name="tp_nonce" value="<?php echo esc_attr($nonce); ?>">
      <input type="hidden" name="login" value="<?php echo esc_attr($login); ?>">
      <input type="hidden" name="key"   value="<?php echo esc_attr($key); ?>">

      <div class="tp-rp-field">
        <label for="tp-rp-pass1">New Password</label>
        <input id="tp-rp-pass1" name="pass1" type="password" minlength="6" required placeholder="••••••••">
      </div>
      <div class="tp-rp-field">
        <label for="tp-rp-pass2">Confirm New Password</label>
        <input id="tp-rp-pass2" name="pass2" type="password" minlength="6" required placeholder="••••••••">
      </div>

      <button class="tp-rp-btn" type="submit">Update password</button>
      <div class="tp-rp-msg" id="tp-rp-msg" role="status" aria-live="polite"></div>
    </form>
  </div>

  <style>
    .tp-rp-wrap { max-width:480px; margin:0 auto; }
    .tp-rp-heading { margin:0 0 10px; font-weight:400; }
    .tp-rp-field { display:grid; gap:6px; margin:8px 0 12px; }
    .tp-rp-field label { font-size:12px; color:#6b7280; font-weight:400; }
    .tp-rp-field input { width:100%; padding:10px 12px; border-radius:10px; border:1px solid #d1d5db; background:#fff; font-size:12px; }
    .tp-rp-btn { appearance:none; border:0; padding:10px 14px; border-radius:10px; background:linear-gradient(135deg,#10b981,#22d3ee); color:#fff; cursor:pointer; font-size:12px; font-weight:400; }
    .tp-rp-btn:hover { filter:brightness(1.05); }
    .tp-rp-msg { margin-top:10px; font-size:12px; }
    .tp-rp-msg.ok { color:#059669; }
    .tp-rp-msg.err { color:#dc2626; }
  </style>

  <script>
  (function(){
    const form = document.getElementById('tp-rp-form');
    const msg  = document.getElementById('tp-rp-msg');
    if(!form) return;
    form.addEventListener('submit', async (e)=>{
      e.preventDefault();
      msg.className='tp-rp-msg'; msg.textContent='Updating…';
      try{
        const res  = await fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', { method:'POST', credentials:'same-origin', body: new FormData(form) });
        const json = await res.json();
        if(json.success){ msg.className='tp-rp-msg ok'; msg.innerHTML=json.data?.message||'Password updated.'; setTimeout(()=>{ window.location.href='<?php echo esc_js(home_url('/')); ?>'; }, 1200); }
        else{ msg.className='tp-rp-msg err'; msg.textContent=json.data?.message||'Could not reset password.'; }
      }catch(_){ msg.className='tp-rp-msg err'; msg.textContent='Network error. Please try again.'; }
    });
  })();
  </script>
  <?php
  return ob_get_clean();
});

/** AJAX: validate key + reset password */
add_action('wp_ajax_nopriv_tp_reset_password', 'tp_reset_password_cb');
function tp_reset_password_cb(){
  if (!check_ajax_referer('tp_rp_nonce', 'tp_nonce', false)) {
    wp_send_json_error(['message'=>'Security check failed.']);
  }
  $login = isset($_POST['login']) ? sanitize_text_field($_POST['login']) : '';
  $key   = isset($_POST['key'])   ? sanitize_text_field($_POST['key'])   : '';
  $p1    = isset($_POST['pass1']) ? (string)$_POST['pass1']              : '';
  $p2    = isset($_POST['pass2']) ? (string)$_POST['pass2']              : '';

  if (!$login || !$key)               wp_send_json_error(['message'=>'Invalid reset link.']);
  if (!$p1 || strlen($p1) < 6)        wp_send_json_error(['message'=>'Password must be at least 6 characters.']);
  if ($p1 !== $p2)                    wp_send_json_error(['message'=>'Passwords do not match.']);

  $user = check_password_reset_key($key, $login);
  if (is_wp_error($user))             wp_send_json_error(['message'=>'This reset link is invalid or has expired. Please request a new one.']);

  reset_password($user, $p1);
  wp_send_json_success(['message'=>'Your password has been updated. Redirecting…']);
}
