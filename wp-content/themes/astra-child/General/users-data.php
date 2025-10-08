<?php
/**
 * Tutors Point – Auth Portal (button + modal + AJAX + Terms modal)
 * Shortcode: [tp_auth_portal]
 *
 * File: /wp-content/themes/astra-child/General/users-data.php
 */
if (!defined('ABSPATH')) exit;

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
        --tp-bg:#0b1220; --tp-panel:#0f172a; --tp-text:#ffffff; --tp-accent:#22d3ee; --tp-accent2:#10b981;
        --tp-radius:12px; --tp-shadow:0 10px 30px rgba(0,0,0,.28);
      }
      .tp-auth-btn{display:inline-flex;align-items:center;justify-content:center;padding:8px 12px;border-radius:999px;font:400 12px/1.2 ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Arial;background:linear-gradient(135deg,var(--tp-accent2),var(--tp-accent));color:#fff;border:0;cursor:pointer;box-shadow:var(--tp-shadow)}
      .tp-auth-btn:hover{filter:brightness(1.05);transform:translateY(-1px)}
      .tp-auth-wrap{position:relative;z-index:50;display:flex;gap:8px}
      /* Auth modal styles — (unchanged from your file except compact sizes) */
      .tp-auth-modal{position:fixed;inset:0;display:none;z-index:1000}
      .tp-auth-modal.open{display:block}
      .tp-auth-overlay{position:absolute;inset:0;background:rgba(6,10,18,.55);backdrop-filter:blur(3px)}
      .tp-auth-dialog{position:relative;width:100%;max-width:420px;margin:min(10vh,64px) auto;z-index:1;background:radial-gradient(900px 300px at 110% 120%, rgba(16,185,129,.12), transparent 40%),radial-gradient(900px 300px at 10% -20%, rgba(34,211,238,.12), transparent 40%),var(--tp-panel);color:var(--tp-text);border:1px solid rgba(255,255,255,.08);border-radius:var(--tp-radius);box-shadow:var(--tp-shadow);transform:translateY(10px);opacity:0;transition:.25s ease}
      .tp-auth-modal.open .tp-auth-dialog{transform:translateY(0);opacity:1}
      .tp-auth-header{display:grid;grid-template-columns:1fr auto auto;gap:8px;align-items:center;padding:10px 12px;border-bottom:1px solid rgba(255,255,255,.06)}
      .tp-auth-header h6{margin:0;color:#fff;font:400 12px/1.2 ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Arial}
      .tp-auth-close{appearance:none;border:0;background:transparent;color:#cbd5e1;font-size:16px;cursor:pointer;padding:4px;border-radius:8px}
      .tp-auth-close:hover{color:#fff;background:rgba(255,255,255,.08)}
      .tp-auth-tabs{display:grid;grid-auto-flow:column;gap:6px}
      .tp-auth-tabs button{appearance:none;border:0;padding:6px 10px;border-radius:10px;cursor:pointer;color:#cbd5e1;background:transparent;font:400 12px/1.2 ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Arial}
      .tp-auth-tabs button.is-active{color:#fff;background:rgba(34,211,238,.18);box-shadow:0 0 0 2px rgba(34,211,238,.22) inset}
      .tp-auth-main{padding:12px}
      .tp-auth-form{display:none}.tp-auth-form.is-active{display:block}
      .tp-seg{display:grid;grid-auto-flow:column;gap:6px;background:rgba(255,255,255,.05);padding:6px;border-radius:10px;margin-bottom:10px}
      .tp-seg input{display:none}
      .tp-seg label{user-select:none;padding:6px 8px;border-radius:8px;text-align:center;cursor:pointer;color:#0077ff;font:400 25px/1.2 ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Arial}
      .tp-seg input:checked + label{color:#fff;background:rgba(34,211,238,.24);box-shadow:0 0 0 2px rgba(34,211,238,.22) inset}
      .tp-field{display:grid;gap:4px;margin:8px 0}
      .tp-field label{color:#f8fbfd;font:400 12px/1.2 ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Arial;letter-spacing:.2px}
      .tp-row{display:grid;grid-template-columns:1fr 1fr;gap:10px}
      .tp-control{display:grid;grid-template-columns:1fr auto;align-items:center;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:10px}
      .tp-control input,.tp-field input{all:unset;padding:8px 10px;color:#00d4ff;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:10px;font:400 12px/1.2 ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Arial}
      .tp-control input{border:0;background:transparent}
      .tp-reveal{appearance:none;border:0;background:transparent;color:#94a3b8;padding:0 8px;cursor:pointer}
      .tp-actions{display:flex;justify-content:space-between;align-items:center;gap:8px;margin-top:6px}
      .tp-link{color:#a5b4fc;text-decoration:none;font:400 12px/1.2 ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Arial}
      .tp-link:hover{text-decoration:underline}
      .tp-primary{appearance:none;border:0;cursor:pointer;font:400 12px/1.2 ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Arial;padding:8px 12px;border-radius:10px;color:#fff;background:linear-gradient(135deg,var(--tp-accent2),var(--tp-accent));box-shadow:var(--tp-shadow)}
      .tp-primary:hover{filter:brightness(1.05);transform:translateY(-1px)}
      .tp-helper.small{font:400 12px/1.4 ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Arial;color:#93a3b3}
      .tp-msg{margin-top:8px;font:400 12px/1.2 ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Arial}
      .tp-msg.ok{color:#34d399}.tp-msg.err{color:#f87171}
      @media (max-width:480px){.tp-row{grid-template-columns:1fr}.tp-auth-dialog{margin:10vh 12px}}

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
        $wpdb->insert('wpC_student_register', [
            'full_name'  => $name,
            'email'      => $email,
            'password'   => wp_hash_password($pass),
            'status'     => 1,
            'created_at' => $now,
        ]);
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);
        do_action('wp_login', $email, get_user_by('id', $user_id));
        wp_send_json_success(['message'=>'Welcome! Your student account has been created. Redirecting…','reload'=>true]);
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

