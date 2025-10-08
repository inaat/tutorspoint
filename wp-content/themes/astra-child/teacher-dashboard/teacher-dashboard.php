<?php
// wp-content/themes/astra-child/teacher-dashboard/teacher-dashboard.php
// Lightweight shell: renders tabs + includes partials. No queries here.

if (!defined('ABSPATH')) exit;

if (!function_exists('tutors_dashboard_shortcode')) {
  function tutors_dashboard_shortcode() {
    if (!is_user_logged_in()) {
      return '<p>Please log in to access your dashboard. <a href="' . esc_url(wp_login_url()) . '">Login</a></p>';
    }

    // helper to include a partial safely
    $inc = function(string $filename, string $missing_msg){
      $path = get_stylesheet_directory() . '/teacher-dashboard/partials/' . ltrim($filename, '/');
      if (file_exists($path)) { include $path; }
      else { echo '<p class="tp-empty">'. esc_html($missing_msg) .'</p>'; }
    };

    ob_start();
    ?>
    <div class="tp-wrap">

      <!-- Tabs -->
      <div class="tp-tabs" role="tablist" aria-label="Teacher Dashboard Tabs">
        <button class="tp-tab active" data-target="tab-dashboard" role="tab" aria-selected="true">Dashboard</button>
        <button class="tp-tab"        data-target="tab-schedule"  role="tab" aria-selected="false">Schedule</button>
        <button class="tp-tab"        data-target="tab-students"  role="tab" aria-selected="false">Students</button>
        <button class="tp-tab"        data-target="tab-booked"    role="tab" aria-selected="false">Booked Sessions</button>
        <button class="tp-tab"        data-target="tab-retake"    role="tab" aria-selected="false">Retake Lectures</button>
        <button class="tp-tab"        data-target="tab-finances"  role="tab" aria-selected="false">Finances</button>
        <button class="tp-tab"        data-target="tab-account"   role="tab" aria-selected="false">Personal Info</button>
      </div>

      <!-- Panes -->
      <section id="tab-dashboard" class="tp-pane active" role="tabpanel" aria-label="Dashboard">
        <?php $inc('dashboard-tab.php', 'Dashboard tab file missing.'); ?>
      </section>

      <section id="tab-schedule" class="tp-pane" role="tabpanel" aria-label="Schedule">
        <?php $inc('schedule-tab.php', 'Schedule tab file missing.'); ?>
      </section>

      <section id="tab-students" class="tp-pane" role="tabpanel" aria-label="Students">
        <?php $inc('students.php', 'Students tab file missing.'); ?>
      </section>

      <section id="tab-booked" class="tp-pane" role="tabpanel" aria-label="Booked Sessions">
        <?php $inc('booked-tab.php', 'Booked tab file missing.'); ?>
      </section>

      <section id="tab-retake" class="tp-pane" role="tabpanel" aria-label="Retake Lectures">
        <?php $inc('retake-tab.php', 'Retake tab file missing.'); ?>
      </section>

      <section id="tab-finances" class="tp-pane" role="tabpanel" aria-label="Finances">
        <?php $inc('finances-tab.php', 'Finances tab file missing.'); ?>
      </section>

      <section id="tab-account" class="tp-pane" role="tabpanel" aria-label="Personal Info">
        <?php $inc('account-tab.php', 'Account tab file missing.'); ?>
      </section>

    </div><!-- /.tp-wrap -->

    <style>
      /* Scoped UI shell styles (no content styles here) */
      .tp-wrap{max-width:1200px;margin:0 auto;padding:26px 16px 40px;font:16px/1.5 ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Arial;color:#0e1d1c}
      .tp-tabs{display:flex;gap:10px;border-bottom:2px solid #d2e9e6;margin-bottom:18px;flex-wrap:wrap}
      .tp-tab{position:relative;appearance:none;border:1px solid #cfe7e4;background:#f5fbfa;color:#113d3a;
        padding:10px 18px;border-top-left-radius:10px;border-top-right-radius:10px;cursor:pointer;font-weight:700}
      .tp-tab:hover{background:#ecf8f6}
      .tp-tab.active{background:#0ABAB5;color:#fff;border-color:#0ABAB5}
      .tp-empty{color:#647a78}
      .tp-pane{display:none}
      .tp-pane.active{display:block}
      @media (max-width: 640px){ .tp-tab{padding:8px 12px} }
    </style>

    <script>
      (function(){
        const tabs  = document.querySelectorAll('.tp-tab');
        const panes = document.querySelectorAll('.tp-pane');
        function activate(id){
          tabs.forEach(t=>t.classList.toggle('active', t.dataset.target===id));
          panes.forEach(p=>p.classList.toggle('active', p.id===id));
        }
        tabs.forEach(btn=>{
          btn.addEventListener('click', () => activate(btn.dataset.target));
        });

        // public helper so partials can switch tabs, e.g. window.dispatchEvent(new CustomEvent('tp-switch-tab',{detail:'tab-booked'}));
        window.addEventListener('tp-switch-tab', (e)=> {
          if (e.detail) activate(e.detail);
        });
        // legacy helper
        window.TPD = {
          showTabById: (id) => activate(id),
          showTab: (_evt, id) => activate(id)
        };
      })();
    </script>
    <?php
    return ob_get_clean();
  }
  add_shortcode('tutors_dashboard', 'tutors_dashboard_shortcode');
}
