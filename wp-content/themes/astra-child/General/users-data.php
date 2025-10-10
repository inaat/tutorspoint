<?php
/**
 * Tutors Point – Auth Portal (button + modal + AJAX + Terms modal)
 * Shortcode: [tp_auth_portal]
 *
 * File: /wp-content/themes/astra-child/General/users-data.php
 */
if (!defined('ABSPATH')) exit;

/* Override WordPress default From email and name */
add_filter('wp_mail_from', 'tp_mail_from');
function tp_mail_from($email) {
    return 'support@fatooranow.com';
}

add_filter('wp_mail_from_name', 'tp_mail_from_name');
function tp_mail_from_name($name) {
    return 'Tutors Point';
}

/* Configure SMTP for email delivery */
add_action('phpmailer_init', 'tp_configure_smtp', 10, 1);
function tp_configure_smtp($phpmailer) {
    $phpmailer->isSMTP();
    $phpmailer->Host       = 'smtp.hostinger.com';
    $phpmailer->SMTPAuth   = true;
    $phpmailer->Port       = 465;
    $phpmailer->Username   = 'support@fatooranow.com';
    $phpmailer->Password   = '8Pl+/sO5!';
    $phpmailer->SMTPSecure = 'ssl';
    $phpmailer->SMTPDebug  = 0;
}

/* Log email errors */
add_action('wp_mail_failed', 'tp_log_email_errors');
function tp_log_email_errors($wp_error) {
    error_log('WordPress mail error: ' . $wp_error->get_error_message());
}

/* Test email function - add ?test_email=your@email.com to any page */
add_action('init', 'tp_test_email');
function tp_test_email() {
    if (isset($_GET['test_email']) && is_email($_GET['test_email'])) {
        $test_email = sanitize_email($_GET['test_email']);
        $sent = wp_mail($test_email, 'SMTP Test from Tutors Point', 'This is a test email to verify SMTP configuration is working.');
        if ($sent) {
            wp_die('Test email sent successfully to ' . $test_email);
        } else {
            wp_die('Test email failed to send to ' . $test_email . '. Check error logs.');
        }
    }
}

/* Ensure roles exist */
add_action('init', function () {
    if (!get_role('student')) { add_role('student', 'Student', ['read' => true]); }
    if (!get_role('teacher')) { add_role('teacher', 'Teacher', ['read' => true]); }
});

/** Shortcode */
add_shortcode('tp_auth_portal', function () {

    // PDF location (you said you put it here)
    $uploads   = wp_get_upload_dir();
    $pdf_name  = 'terms-privacy.pdf';                  // <— change if you rename the file
    $pdf_url   = trailingslashit($uploads['baseurl']) . 'docs/' . $pdf_name;
    $pdf_path  = trailingslashit($uploads['basedir']) . 'docs/' . $pdf_name;

    $nonce     = wp_create_nonce('tp_auth_nonce');
    $terms_nonce = wp_create_nonce('tp_terms_nonce');
    $is_logged = is_user_logged_in();

    ob_start(); ?>

    <div class="tp-auth-wrap" id="tp-auth-wrap" aria-live="polite">
      <?php if (!$is_logged): ?>
        <button class="tp-auth-btn tp-auth-open" id="tp-open-auth" type="button" aria-haspopup="dialog" aria-controls="tp-auth-modal">
          <span>Login / Sign-Up</span>
        </button>
      <?php else:
        $user   = wp_get_current_user();
        $roles  = (array) $user->roles;
        $dash_url = in_array('teacher',$roles,true) ? home_url('/tutorsdashboard') :
                    (in_array('student',$roles,true) ? home_url('/student-dashboard') : admin_url());
        $dash_lbl = (in_array('teacher',$roles,true) || in_array('student',$roles,true)) ? 'Dashboard' : 'My Account'; ?>
        <a class="tp-auth-btn tp-auth-dashboard" href="<?php echo esc_url($dash_url); ?>"><span><?php echo esc_html($dash_lbl); ?></span></a>
        <a class="tp-auth-btn tp-auth-logout" href="<?php echo esc_url( wp_logout_url(home_url('/')) ); ?>"><span>Logout</span></a>
      <?php endif; ?>
    </div>

    <?php if (!$is_logged): ?>
    <!-- ============ LOGIN / SIGN-UP MODAL ============ -->
    <div class="tp-auth-modal" id="tp-auth-modal" role="dialog" aria-modal="true" aria-labelledby="tp-auth-title" aria-hidden="true">
      <div class="tp-auth-overlay" data-close></div>
      <section class="tp-auth-dialog">
        <header class="tp-auth-header">
          <h6 id="tp-auth-title">Welcome to Tutors Point</h6>
          <nav class="tp-auth-tabs" role="tablist" aria-label="Auth tabs">
            <button class="is-active" role="tab" aria-selected="true" data-tab="login">Login</button>
            <button role="tab" aria-selected="false" data-tab="signup">Sign Up</button>
          </nav>
          <button class="tp-auth-close" title="Close" aria-label="Close" data-close>&times;</button>
        </header>

        <main class="tp-auth-main">
          <!-- LOGIN -->
          <form id="tp-login-form" class="tp-auth-form is-active" novalidate>
            <input type="hidden" name="action" value="tp_auth_login">
            <input type="hidden" name="tp_nonce" value="<?php echo esc_attr($nonce); ?>">
            <div class="tp-field">
              <label for="tp-login-email">Email</label>
              <input id="tp-login-email" name="email" type="email" inputmode="email" autocomplete="email" required placeholder="you@example.com">
            </div>
            <div class="tp-field">
              <label for="tp-login-password">Password</label>
              <div class="tp-control">
                <input id="tp-login-password" name="password" type="password" minlength="6" autocomplete="current-password" required placeholder="••••••••">
                <button class="tp-reveal" type="button" data-toggle="tp-login-password" aria-label="Show password">👁</button>
              </div>
            </div>
            <div class="tp-actions">
              <a class="tp-link" href="<?php echo esc_url(wp_lostpassword_url()); ?>">Forgot password?</a>
              <button class="tp-primary" type="submit">Login</button>
            </div>
            <div class="tp-msg" id="tp-login-msg" role="status" aria-live="polite"></div>
          </form>

          <!-- SIGN UP -->
          <form id="tp-signup-form" class="tp-auth-form" novalidate>
            <input type="hidden" name="action" value="tp_auth_signup">
            <input type="hidden" name="tp_nonce" value="<?php echo esc_attr($nonce); ?>">
            <div class="tp-seg" role="radiogroup" aria-label="Choose account type">
              <input type="radio" id="tp-role-student" name="role" value="student" checked>
              <label for="tp-role-student">Student</label>
              <input type="radio" id="tp-role-teacher" name="role" value="teacher">
              <label for="tp-role-teacher">Teacher</label>
            </div>
            <div class="tp-field">
              <label for="tp-signup-name">Full Name</label>
              <input id="tp-signup-name" name="name" type="text" required minlength="2" placeholder="Your name">
            </div>
            <div class="tp-row">
              <div class="tp-field">
                <label for="tp-signup-email">Email</label>
                <input id="tp-signup-email" name="email" type="email" inputmode="email" autocomplete="email" required placeholder="you@example.com">
              </div>
              <div class="tp-field">
                <label for="tp-signup-password">Password</label>
                <div class="tp-control">
                  <input id="tp-signup-password" name="password" type="password" minlength="6" autocomplete="new-password" required placeholder="Min 6 characters">
                  <button class="tp-reveal" type="button" data-toggle="tp-signup-password" aria-label="Show password">👁</button>
                </div>
              </div>
            </div>

            <div class="tp-actions">
              <span class="tp-helper small">
                By creating an account you agree to our
                <a href="#" id="open-terms-modal">Terms &amp; Privacy Policy</a>.
              </span>
              <button class="tp-primary" type="submit">Create Account</button>
            </div>
            <div class="tp-msg" id="tp-signup-msg" role="status" aria-live="polite"></div>
          </form>
        </main>
      </section>
    </div>
    <?php endif; ?>

    <!-- ============ TINY TERMS MODAL (shared) ============ -->
    <div class="tp-terms-modal" id="tp-terms-modal" role="dialog" aria-modal="true" aria-labelledby="tp-terms-title" aria-hidden="true">
      <div class="tp-terms-overlay" data-close></div>
      <section class="tp-terms-dialog">
        <header class="tp-terms-header">
          <h6 id="tp-terms-title">Terms &amp; Privacy</h6>
          <div class="tp-terms-actions">
            <button type="button" class="tp-terms-btn" id="tp-terms-email">Email</button>
            <button type="button" class="tp-terms-btn" data-close>OK</button>
            <button class="tp-terms-close" title="Close" aria-label="Close" data-close>&times;</button>
          </div>
        </header>
        <div class="tp-terms-body">
          <!-- Use PDF if it exists; fallback to message -->
          <?php if (file_exists($pdf_path)) : ?>
            <iframe class="tp-terms-frame" src="<?php echo esc_url($pdf_url); ?>" title="Terms"></iframe>
          <?php else : ?>
            <p style="font-size:12px;line-height:1.4;margin:0">Terms PDF not found at <code>/wp-content/uploads/docs/<?php echo esc_html($pdf_name); ?></code>.</p>
          <?php endif; ?>
        </div>
      </section>
    </div>

    <style>
      :root{
        --tp-bg:#e5f4ef; --tp-panel:#ffffff; --tp-text:#333333; --tp-accent:#3dba9f; --tp-accent2:#5cd4b6;
        --tp-radius:16px; --tp-shadow:0 10px 30px rgba(61,186,159,.18);
      }

      .tp-auth-btn{
        display:inline-flex;align-items:center;justify-content:center;padding:10px 24px;
        border-radius:6px;font:500 14px/1.3 'League Spartan',sans-serif;
        background:#3dba9f;color:#fff;border:0;cursor:pointer;
        box-shadow:0 4px 12px rgba(61,186,159,.3);transition:all .3s ease;
      }
      .tp-auth-btn:hover{background:#2da889;transform:translateY(-2px);box-shadow:0 6px 16px rgba(61,186,159,.4)}
      .tp-auth-wrap{position:relative;z-index:50;display:flex;gap:8px}

      /* Auth modal styles */
      .tp-auth-modal{position:fixed;inset:0;display:none;z-index:10000}
      .tp-auth-modal.open{display:block}
      .tp-auth-overlay{position:absolute;inset:0;background:rgba(0,0,0,.6);backdrop-filter:blur(8px)}
      .tp-auth-dialog{
        position:relative;width:100%;max-width:520px;margin:min(8vh,50px) auto;z-index:1;
        background:white;
        border-radius:var(--tp-radius);box-shadow:0 20px 60px rgba(0,0,0,.2);
        transform:translateY(30px) scale(0.95);opacity:0;transition:.3s ease;
      }
      .tp-auth-modal.open .tp-auth-dialog{transform:translateY(0) scale(1);opacity:1}

      .tp-auth-header{
        display:grid;grid-template-columns:1fr auto auto;gap:12px;align-items:center;
        padding:20px 24px;border-bottom:1px solid #e5e5e5;
        background:linear-gradient(135deg,rgba(61,186,159,.05),rgba(92,212,182,.05));
      }
      .tp-auth-header h6{margin:0;color:#333;font:600 18px/1.3 'League Spartan',sans-serif}
      .tp-auth-close{
        appearance:none;border:0;background:transparent;color:#666;font-size:24px;
        cursor:pointer;padding:4px 8px;border-radius:6px;transition:all .2s;
      }
      .tp-auth-close:hover{color:#3dba9f;background:rgba(61,186,159,.1)}

      .tp-auth-tabs{display:grid;grid-auto-flow:column;gap:8px}
      .tp-auth-tabs button{
        appearance:none;border:0;padding:8px 16px;border-radius:6px;cursor:pointer;
        color:#666;background:transparent;font:500 13px/1.3 'League Spartan',sans-serif;
        transition:all .2s;
      }
      .tp-auth-tabs button.is-active{
        color:#fff;background:#3dba9f;box-shadow:0 2px 8px rgba(61,186,159,.3)
      }

      .tp-auth-main{padding:24px}
      .tp-auth-form{display:none}.tp-auth-form.is-active{display:block}

      .tp-seg{
        display:grid;grid-auto-flow:column;gap:8px;background:rgba(61,186,159,.08);
        padding:6px;border-radius:10px;margin-bottom:16px;
      }
      .tp-seg input{display:none}
      .tp-seg label{
        user-select:none;padding:10px 16px;border-radius:8px;text-align:center;cursor:pointer;
        color:#666;font:600 14px/1.3 'League Spartan',sans-serif;transition:all .2s;
      }
      .tp-seg input:checked + label{color:#fff;background:#3dba9f;box-shadow:0 2px 8px rgba(61,186,159,.3)}

      .tp-field{display:grid;gap:6px;margin:12px 0}
      .tp-field label{color:#333;font:600 13px/1.3 'League Spartan',sans-serif;letter-spacing:.3px}
      .tp-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}

      .tp-control{
        display:grid;grid-template-columns:1fr auto;align-items:center;
        background:#f8f9fa;border:1px solid #ddd;border-radius:8px;transition:all .2s;
      }
      .tp-control:focus-within{border-color:#3dba9f;background:#fff;box-shadow:0 0 0 3px rgba(61,186,159,.1)}

      .tp-control input,.tp-field input{
        all:unset;padding:11px 14px;color:#333;
        background:#f8f9fa;border:1px solid #ddd;border-radius:8px;
        font:500 14px/1.3 'League Spartan',sans-serif;transition:all .2s;
      }
      .tp-field input:focus{border-color:#3dba9f;background:#fff;box-shadow:0 0 0 3px rgba(61,186,159,.1)}
      .tp-control input{border:0;background:transparent}
      .tp-control input:focus{box-shadow:none}

      .tp-reveal{
        appearance:none;border:0;background:transparent;color:#999;padding:0 12px;
        cursor:pointer;transition:color .2s;
      }
      .tp-reveal:hover{color:#3dba9f}

      .tp-actions{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-top:12px}
      .tp-link{color:#3dba9f;text-decoration:none;font:500 13px/1.3 'League Spartan',sans-serif}
      .tp-link:hover{text-decoration:underline;color:#2da889}

      .tp-primary{
        appearance:none;border:0;cursor:pointer;
        font:600 14px/1.3 'League Spartan',sans-serif;padding:11px 24px;
        border-radius:6px;color:#fff;background:#3dba9f;
        box-shadow:0 4px 12px rgba(61,186,159,.3);transition:all .3s ease;
      }
      .tp-primary:hover{background:#2da889;transform:translateY(-2px);box-shadow:0 6px 16px rgba(61,186,159,.4)}

      .tp-helper.small{font:400 12px/1.5 'League Spartan',sans-serif;color:#777}
      .tp-helper.small a{color:#3dba9f;text-decoration:none}
      .tp-helper.small a:hover{text-decoration:underline}

      .tp-msg{margin-top:12px;font:500 13px/1.4 'League Spartan',sans-serif;padding:10px 12px;border-radius:6px}
      .tp-msg.ok{color:#0d9267;background:rgba(61,186,159,.1)}
      .tp-msg.err{color:#dc2626;background:rgba(220,38,38,.1)}

      @media (max-width:480px){
        .tp-row{grid-template-columns:1fr}
        .tp-auth-dialog{margin:10vh 16px;max-width:none}
        .tp-auth-header{padding:16px 20px}
        .tp-auth-main{padding:20px}
      }

      /* Terms modal (tiny, scrollable both axes, small font) */
      .tp-terms-modal{position:fixed;inset:0;display:none;z-index:1100}
      .tp-terms-modal.open{display:block}
      .tp-terms-overlay{position:absolute;inset:0;background:rgba(8,8,12,.55);backdrop-filter:blur(2px)}
      .tp-terms-dialog{position:relative;margin:8vh auto 0;width:min(92vw,620px);background:#0f172a;border:1px solid rgba(255,255,255,.12);border-radius:12px;box-shadow:var(--tp-shadow);display:flex;flex-direction:column;max-height:84vh}
      .tp-terms-header{display:flex;align-items:center;gap:8px;justify-content:space-between;padding:8px 10px;background:rgba(255,255,255,.04)}
      .tp-terms-header h6{margin:0;color:#fff;font:400 12px/1 ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Arial}
      .tp-terms-actions{display:flex;gap:8px;align-items:center}
      .tp-terms-btn{font:400 12px/1 ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Arial;border:1px solid rgba(255,255,255,.18);background:transparent;color:#e2e8f0;border-radius:10px;padding:6px 10px;cursor:pointer}
      .tp-terms-btn:hover{background:rgba(255,255,255,.08)}
      .tp-terms-close{appearance:none;border:0;background:transparent;color:#cbd5e1;font-size:16px;cursor:pointer;padding:0 6px}
      .tp-terms-body{padding:8px;background:#0b1220;overflow:auto;font-size:12px}
      .tp-terms-frame{width:100%;height:70vh;border:0;display:block}
      @media (max-width:480px){.tp-terms-frame{height:64vh}}
      /* Toast */
      #tp-toast{position:fixed;left:50%;bottom:18px;transform:translateX(-50%);background:#111827;color:#fff;font:400 12px/1.2 ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Arial;padding:8px 12px;border-radius:10px;box-shadow:var(--tp-shadow);display:none;z-index:1200}
      #tp-toast.show{display:block}
    </style>

    <div id="tp-toast" role="status" aria-live="polite"></div>

    <script>
    (function(){
      const $  = (s, c=document) => c.querySelector(s);
      const $$ = (s, c=document) => Array.from(c.querySelectorAll(s));
      const AJAX = <?php echo json_encode( admin_url('admin-ajax.php') ); ?>;
      const TERMS_NONCE = <?php echo json_encode($terms_nonce); ?>;
      const PDF_URL  = <?php echo json_encode($pdf_url); ?>;

      function toast(msg, ms=2200){
        const t = $('#tp-toast'); if(!t) return alert(msg);
        t.textContent = msg; t.classList.add('show');
        setTimeout(()=>t.classList.remove('show'), ms);
      }

      /* ---------- AUTH modal (existing behaviour) ---------- */
      const authModal = $('#tp-auth-modal');
      const openBtn   = $('#tp-open-auth');
      const openAuth  = () => { if(!authModal) return; authModal.classList.add('open'); authModal.removeAttribute('aria-hidden'); setTimeout(()=>$('#tp-login-email')?.focus(),50); };
      const closeAuth = () => { if(!authModal) return; authModal.classList.remove('open'); authModal.setAttribute('aria-hidden','true'); };
      openBtn?.addEventListener('click', openAuth);
      $$('.tp-auth-overlay,[data-close]', authModal).forEach(el=>el.addEventListener('click', closeAuth));
      document.addEventListener('keydown', e=>{ if(e.key==='Escape' && authModal?.classList.contains('open')) closeAuth(); });

      const tabBtns = $$('.tp-auth-tabs button', authModal);
      const forms   = $$('.tp-auth-form', authModal);
      tabBtns.forEach(btn => btn.addEventListener('click', ()=>{
        tabBtns.forEach(b=>b.classList.remove('is-active'));
        btn.classList.add('is-active');
        const isLogin = btn.dataset.tab==='login';
        forms.forEach(f=>f.classList.toggle('is-active', f.id === (isLogin ? 'tp-login-form' : 'tp-signup-form')));
        (isLogin ? $('#tp-login-email') : $('#tp-signup-name'))?.focus();
      }));

      $$('.tp-reveal', authModal).forEach(btn=>{
        btn.addEventListener('click', ()=>{
          const id = btn.getAttribute('data-toggle');
          const input = document.getElementById(id);
          if(!input) return;
          input.type = input.type === 'password' ? 'text' : 'password';
          btn.textContent = (input.type === 'password') ? '👁' : '🙈';
          input.focus();
        });
      });

      async function postForm(form){
        const data = new FormData(form);
        const res  = await fetch(AJAX, { method:'POST', credentials:'same-origin', body:data });
        return res.json();
      }

      const loginForm = $('#tp-login-form');
      const loginMsg  = $('#tp-login-msg');
      loginForm?.addEventListener('submit', async (e)=>{
        e.preventDefault();
        loginMsg.className='tp-msg'; loginMsg.textContent='Signing in…';
        try{
          const json = await postForm(loginForm);
          if(json.success){ loginMsg.className='tp-msg ok'; loginMsg.textContent='Welcome! Redirecting…'; setTimeout(()=>location.reload(), 600); }
          else{ loginMsg.className='tp-msg err'; loginMsg.textContent=json.data?.message || 'Login failed.'; }
        }catch(_){ loginMsg.className='tp-msg err'; loginMsg.textContent='Network error. Please try again.'; }
      });

      const signupForm = $('#tp-signup-form');
      const signupMsg  = $('#tp-signup-msg');
      signupForm?.addEventListener('submit', async (e)=>{
        e.preventDefault();
        signupMsg.className='tp-msg'; signupMsg.textContent='Creating your account…';
        try{
          const json = await postForm(signupForm);
          if(json.success){ signupMsg.className='tp-msg ok'; signupMsg.innerHTML=json.data?.message || 'Account created.'; if(json.data?.reload){ setTimeout(()=>location.reload(), 900); } }
          else{ signupMsg.className='tp-msg err'; signupMsg.textContent=json.data?.message || 'Sign-up failed.'; }
        }catch(_){ signupMsg.className='tp-msg err'; signupMsg.textContent='Network error. Please try again.'; }
      });

      /* ---------- TERMS modal integration ---------- */
      const termsModal = $('#tp-terms-modal');
      const openTermsLink = $('#open-terms-modal');
      const signupEmail = $('#tp-signup-email');

      function openTerms(){
        // require email first
        const em = (signupEmail?.value||'').trim();
        if(!em){ toast('Please enter your email first.'); signupEmail?.focus(); return; }
        termsModal?.classList.add('open');
        termsModal?.removeAttribute('aria-hidden');
      }
      function closeTerms(){ termsModal?.classList.remove('open'); termsModal?.setAttribute('aria-hidden','true'); }

      openTermsLink?.addEventListener('click', (e)=>{ e.preventDefault(); openTerms(); });
      $$('.tp-terms-overlay,[data-close]', termsModal).forEach(el=>el.addEventListener('click', closeTerms));
      document.addEventListener('keydown', e=>{ if(e.key==='Escape' && termsModal?.classList.contains('open')) closeTerms(); });

      // Email PDF
      $('#tp-terms-email')?.addEventListener('click', async ()=>{
        const email = (signupEmail?.value||'').trim();
        if(!email){ toast('Enter your email in the form first.'); signupEmail?.focus(); return; }
        try{
          const fd = new FormData();
          fd.append('action','tp_send_terms_pdf');
          fd.append('email', email);
          fd.append('nonce', TERMS_NONCE);
          const r = await fetch(AJAX, {method:'POST', credentials:'same-origin', body:fd});
          const j = await r.json();
          if(j?.success){ toast('We emailed you the Terms PDF.'); }
          else{ toast(j?.data?.message || 'Could not send email.'); }
        }catch(_){ toast('Network error while sending.'); }
      });
    })();
    </script>
    <?php
    return ob_get_clean();
});

/** ---------- AJAX: Login ---------- */
add_action('wp_ajax_nopriv_tp_auth_login', 'tp_auth_login_cb');
function tp_auth_login_cb(){
    if (!check_ajax_referer('tp_auth_nonce', 'tp_nonce', false)) {
        wp_send_json_error(['message' => 'Security check failed.']);
    }
    $email    = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    $password = isset($_POST['password']) ? (string) $_POST['password'] : '';
    if (!$email || !$password) wp_send_json_error(['message' => 'Email and password are required.']);

    $user = get_user_by('email', $email);
    if (!$user) wp_send_json_error(['message' => 'No account found for this email.']);

    if (in_array('student', $user->roles)) {
        global $wpdb;
        // Get the most recent record for this email
        $student = $wpdb->get_row($wpdb->prepare(
            "SELECT status FROM wpC_student_register WHERE email = %s ORDER BY created_at DESC LIMIT 1",
            $email
        ));

        if ($student && $student->status == 0) {
            wp_send_json_error(['message' => 'Please verify your email address before logging in. Check your inbox for the verification link.']);
        }
    }

    $creds = ['user_login'=>$user->user_login,'user_password'=>$password,'remember'=>true];
    $signon = wp_signon($creds, false);
    if (is_wp_error($signon)) wp_send_json_error(['message'=>'Invalid credentials.']);

    wp_send_json_success(['message'=>'Logged in.']);
}

/** ---------- AJAX: Sign-Up (Student/Teacher) ---------- */
add_action('wp_ajax_nopriv_tp_auth_signup', 'tp_auth_signup_cb');
function tp_auth_signup_cb(){
    if (!check_ajax_referer('tp_auth_nonce', 'tp_nonce', false)) {
        wp_send_json_error(['message' => 'Security check failed.']);
    }
    global $wpdb;
    $role  = isset($_POST['role']) ? sanitize_text_field($_POST['role']) : 'student';
    $name  = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
    $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    $pass  = isset($_POST['password']) ? (string) $_POST['password'] : '';

    if (!$name || !$email || !$pass) wp_send_json_error(['message'=>'Please fill in all fields.']);
    if (email_exists($email)) wp_send_json_error(['message'=>'This email is already registered. Try logging in.']);

    $login = sanitize_user(current(explode('@', $email)));
    if (username_exists($login)) $login .= '_' . wp_generate_password(4, false, false);
    $user_id = wp_create_user($login, $pass, $email);
    if (is_wp_error($user_id)) wp_send_json_error(['message'=>'Could not create user.']);

    wp_update_user(['ID'=>$user_id, 'display_name'=>$name]);
    $wp_role = ($role === 'teacher') ? 'teacher' : 'student';
    (new WP_User($user_id))->set_role($wp_role);

    $now = current_time('mysql');

    if ($wp_role === 'student') {
        $verification_token = wp_generate_password(32, false, false);

        // Check if student already exists in the table
        $existing_student = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM wpC_student_register WHERE email = %s ORDER BY created_at DESC LIMIT 1",
            $email
        ));

        if ($existing_student) {
            // Update existing record instead of inserting new one
            $wpdb->update(
                'wpC_student_register',
                [
                    'full_name' => $name,
                    'password' => wp_hash_password($pass),
                    'status' => 0,
                    'verification_token' => $verification_token,
                    'created_at' => $now,
                ],
                ['email' => $email],
                ['%s', '%s', '%d', '%s', '%s'],
                ['%s']
            );
        } else {
            // Insert new record
            $wpdb->insert('wpC_student_register', [
                'full_name'  => $name,
                'email'      => $email,
                'password'   => wp_hash_password($pass),
                'status'     => 0,
                'verification_token' => $verification_token,
                'created_at' => $now,
            ]);
        }
        
        $verification_link = add_query_arg([
            'token' => $verification_token,
            'email' => urlencode($email)
        ], home_url('/verify-email/'));
        $subject = 'Tutors Point - Verify Your Email Address';
        $message = "Hi {$name},\n\n";
        $message .= "Thank you for signing up with Tutors Point! Please verify your email address by clicking the link below:\n\n";
        $message .= $verification_link . "\n\n";
        $message .= "If you didn't create this account, please ignore this email.\n\n";
        $message .= "Best regards,\nTutors Point Team";
        
        $sent = wp_mail($email, $subject, $message);
        
        if ($sent) {
            wp_send_json_success(['message'=>'Account created! Please check your email and click the verification link to activate your account.']);
        } else {
            error_log('Email verification failed for: ' . $email);
            wp_send_json_error(['message'=>'Account created but verification email could not be sent. Please contact support.']);
        }
    } else {
        $wpdb->insert('wpC_teachers_main', [
            'FullName'   => $name,
            'Email'      => $email,
            'Status'     => 0,
            'password'   => wp_hash_password($pass),
            'created_at' => $now,
        ]);
        $admins = get_users(['role'=>'administrator','fields'=>['user_email']]);
        $admin_emails = array_map(fn($u)=>$u->user_email, $admins);
        if ($admin_emails) {
            wp_mail($admin_emails, 'Tutors Point – Teacher approval requested',
              "A new teacher signed up and awaits approval.\n\nName: {$name}\nEmail: {$email}\n\nPlease review and activate the account in WordPress.");
        }
        wp_send_json_success(['message'=>"Thanks! Your teacher account is created and pending approval. We'll email you when it's activated."]);
    }
}

/** ---------- AJAX: Send Terms PDF ---------- */
add_action('wp_ajax_tp_send_terms_pdf',        'tp_send_terms_pdf_cb');
add_action('wp_ajax_nopriv_tp_send_terms_pdf', 'tp_send_terms_pdf_cb');
function tp_send_terms_pdf_cb(){
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tp_terms_nonce')) {
        wp_send_json_error(['message'=>'Security check failed.']);
    }
    $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    if (!$email || !is_email($email)) wp_send_json_error(['message'=>'Valid email required.']);

    $uploads  = wp_get_upload_dir();
    $pdf_name = 'terms-privacy.pdf';
    $path     = trailingslashit($uploads['basedir']) . 'docs/' . $pdf_name;

    if (!file_exists($path)) wp_send_json_error(['message'=>'Terms PDF not found on server.']);

    $sent = wp_mail(
        $email,
        'Tutors Point – Terms & Privacy',
        "Hi,\n\nPlease find attached our Terms & Privacy Policy.\n\nRegards,\nTutors Point",
        [],
        [$path]
    );
    if (!$sent) wp_send_json_error(['message'=>'Email could not be sent.']);
    wp_send_json_success(['message'=>'Email sent.']);
}

/* Verification Page Shortcode */
add_shortcode('tp_verify_email', function() {
    ob_start(); ?>
    <div class="tp-verify-container">
        <div class="tp-verify-card">
            <div class="tp-verify-icon">
                <svg width="80" height="80" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="12" cy="12" r="10" stroke="url(#gradient)" stroke-width="2" stroke-linecap="round" class="tp-verify-circle"/>
                    <path d="M9 12L11 14L15 10" stroke="url(#gradient)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="tp-verify-check"/>
                    <defs>
                        <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#10b981;stop-opacity:1" />
                            <stop offset="100%" style="stop-color:#22d3ee;stop-opacity:1" />
                        </linearGradient>
                    </defs>
                </svg>
            </div>
            <h1 class="tp-verify-title">Email Verification</h1>
            <p class="tp-verify-message">Please wait while we verify your email address...</p>
            <div class="tp-verify-spinner">
                <div class="tp-spinner"></div>
            </div>
            <p class="tp-verify-footer">This will only take a moment</p>
        </div>
    </div>

    <style>
        .tp-verify-container {
            min-height: 70vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            margin: -60px -9999px 0;
            padding: 60px 9999px;
        }

        .tp-verify-card {
            max-width: 500px;
            width: 100%;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.05) 0%, rgba(34, 211, 238, 0.05) 100%),
                        rgba(15, 23, 42, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 60px 40px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3),
                        0 0 100px rgba(34, 211, 238, 0.1) inset;
            backdrop-filter: blur(10px);
            animation: tp-card-enter 0.6s ease-out;
        }

        @keyframes tp-card-enter {
            from {
                opacity: 0;
                transform: translateY(30px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .tp-verify-icon {
            margin-bottom: 30px;
            animation: tp-icon-float 3s ease-in-out infinite;
        }

        @keyframes tp-icon-float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .tp-verify-circle {
            stroke-dasharray: 63;
            stroke-dashoffset: 63;
            animation: tp-circle-draw 2s ease-out forwards;
        }

        @keyframes tp-circle-draw {
            to { stroke-dashoffset: 0; }
        }

        .tp-verify-check {
            stroke-dasharray: 12;
            stroke-dashoffset: 12;
            animation: tp-check-draw 0.5s ease-out 1.5s forwards;
        }

        @keyframes tp-check-draw {
            to { stroke-dashoffset: 0; }
        }

        .tp-verify-title {
            font-size: 32px;
            font-weight: 600;
            background: linear-gradient(135deg, #10b981, #22d3ee);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 0 0 16px 0;
            animation: tp-title-glow 2s ease-in-out infinite;
        }

        @keyframes tp-title-glow {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }

        .tp-verify-message {
            color: #cbd5e1;
            font-size: 18px;
            line-height: 1.6;
            margin: 0 0 40px 0;
            font-weight: 400;
        }

        .tp-verify-spinner {
            margin: 0 0 30px 0;
        }

        .tp-spinner {
            width: 60px;
            height: 60px;
            margin: 0 auto;
            border: 4px solid rgba(255, 255, 255, 0.1);
            border-top: 4px solid transparent;
            border-radius: 50%;
            background: linear-gradient(135deg, #10b981, #22d3ee);
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            padding: 4px;
            animation: tp-spin 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite;
            position: relative;
        }

        .tp-spinner::before {
            content: '';
            position: absolute;
            inset: -4px;
            border-radius: 50%;
            background: linear-gradient(135deg, #10b981, #22d3ee);
            opacity: 0.2;
            filter: blur(8px);
            animation: tp-pulse 2s ease-in-out infinite;
        }

        @keyframes tp-spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes tp-pulse {
            0%, 100% { opacity: 0.2; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(1.1); }
        }

        .tp-verify-footer {
            color: #94a3b8;
            font-size: 14px;
            margin: 0;
            font-weight: 400;
        }

        @media (max-width: 640px) {
            .tp-verify-card {
                padding: 40px 24px;
                border-radius: 20px;
            }

            .tp-verify-title {
                font-size: 26px;
            }

            .tp-verify-message {
                font-size: 16px;
            }

            .tp-spinner {
                width: 50px;
                height: 50px;
            }
        }
    </style>
    <?php
    return ob_get_clean();
});

/* Email Verification Handler - supports both query params and dedicated page */
add_action('template_redirect', 'tp_handle_email_verification');
function tp_handle_email_verification() {
    // Check if this is the verify-email page or has verification params
    $is_verify_page = is_page('verify-email');
    $has_verify_param = isset($_GET['tp_verify_email']);

    if (!$is_verify_page && !$has_verify_param) {
        return;
    }

    // Only process if we have token and email
    if (!isset($_GET['token']) || !isset($_GET['email'])) {
        return;
    }

    global $wpdb;
    $token = sanitize_text_field($_GET['token']);
    $email = sanitize_email($_GET['email']);

    if (!$token || !$email) {
        wp_die('Invalid verification link.', 'Email Verification Error');
    }

    $student = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM wpC_student_register WHERE email = %s AND verification_token = %s",
        $email, $token
    ));

    if (!$student) {
        wp_die('Invalid or expired verification link.', 'Email Verification Error');
    }

    if ($student->status == 1) {
        wp_die('Email has already been verified.', 'Email Already Verified');
    }

    $updated = $wpdb->update(
        'wpC_student_register',
        ['status' => 1, 'verification_token' => null],
        ['email' => $email, 'verification_token' => $token],
        ['%d', '%s'],
        ['%s', '%s']
    );

    if ($updated) {
        $wp_user = get_user_by('email', $email);
        if ($wp_user) {
            wp_set_current_user($wp_user->ID);
            wp_set_auth_cookie($wp_user->ID);
            do_action('wp_login', $wp_user->user_login, $wp_user);
        }

        wp_redirect(home_url('/student-dashboard/?verified=1'));
        exit;
    } else {
        wp_die('Verification failed. Please try again or contact support.', 'Email Verification Error');
    }
}

