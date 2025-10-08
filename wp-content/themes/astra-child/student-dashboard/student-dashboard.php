<?php
// 📁 File: student-dashboard/student-dashboard.php
if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();
global $wpdb;

// Get student row (used by some tabs)
$student = $wpdb->get_row($wpdb->prepare(
  "SELECT * FROM wpC_student_register WHERE email = %s",
  $current_user->user_email
));
$student_id   = $student ? (int)$student->student_id : 0;
$student_name = $student ? $student->full_name : $current_user->display_name;

/* ---------- Tab routing (preserve your keys, add aliases) ---------- */
$raw_tab = isset($_GET['tab']) ? strtolower(sanitize_text_field($_GET['tab'])) : 'dashboard';

// Your canonical keys → files (kept)
$student_tabs = [
  'dashboard' => ['label' => 'Dashboard',    'file' => 'student-dashboard-tab.php'],
  'freebook'  => ['label' => 'Free Lecture', 'file' => 'student-book-tab.php'],
  'bookings'  => ['label' => 'My Bookings',  'file' => 'student-bookedlectures-tab.php'],
  // 'retakes' => ['label' => 'Retakes', 'file' => 'student-retake-tab.php'],
  'messages'  => ['label' => 'Messages',     'file' => 'student-messages-tab.php'],
  'history'   => ['label' => 'History',      'file' => 'student-history-tab.php'],
  'account'   => ['label' => 'Account',      'file' => 'student-account-tab.php'],
];

// Friendly/legacy aliases → your canonical keys
$aliases = [
  // Free lecture
  'free-lecture'    => 'freebook',
  'freelecturebook' => 'freebook',
  'freebook'        => 'freebook',
  'free'            => 'freebook',
  // My bookings
  'my-bookings'     => 'bookings',
  'mybooking'       => 'bookings',
  'mybookings'      => 'bookings',
  'bookings'        => 'bookings',
];

$tab = $aliases[$raw_tab] ?? (array_key_exists($raw_tab, $student_tabs) ? $raw_tab : 'dashboard');
?>
<div class="student-dash-container">
  <div class="tab-buttons" id="desktopTabs">
    <?php foreach ($student_tabs as $key => $tab_info): ?>
      <button class="tab-btn <?= $tab === $key ? 'active' : '' ?>" onclick="location.href='?tab=<?= esc_attr($key) ?>'">
        <?= esc_html($tab_info['label']) ?>
      </button>
    <?php endforeach; ?>
  </div>

  <!-- Mobile Hamburger Icon -->
  <div class="mobile-menu-toggle">
    <button onclick="toggleMobileTabs()">☰</button>
    <div class="mobile-tabs" id="mobileTabs">
      <?php foreach ($student_tabs as $key => $tab_info): ?>
        <a href="?tab=<?= esc_attr($key) ?>" class="<?= $tab === $key ? 'active' : '' ?>">
          <?= esc_html($tab_info['label']) ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="student-tab-content">
    <?php
      // Primary include
      $base = get_stylesheet_directory() . '/student-dashboard/partials/';
      $primary   = $base . ($student_tabs[$tab]['file'] ?? 'student-dashboard-tab.php');

      // Fallbacks for Free Lecture (support either filename without breaking UI)
      $candidates = [$primary];
      if ($tab === 'freebook') {
        $candidates[] = $base . 'free-lecture-tab.php';   // our newer filename
        $candidates[] = $base . 'student-book-tab.php';   // your original filename
      }

      $included = false;
      foreach ($candidates as $inc) {
        if (file_exists($inc)) { include_once $inc; $included = true; break; }
      }
      if (!$included) echo "<p>Selected tab file not found.</p>";
    ?>
  </div>
</div>

<style>
.student-dash-container { padding: 20px; }
.tab-buttons { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
.tab-btn {
  padding: 10px 15px;
  background: #0ABAB5;
  color: #fff;
  border: none;
  border-radius: 5px;
  cursor: pointer;
}
.tab-btn.active { background: #088B87; }
.mobile-menu-toggle { display: none; }
.mobile-tabs { display: none; flex-direction: column; background: #f8f8f8; border-radius: 8px; padding: 10px; }
.mobile-tabs a {
  padding: 8px 10px; border-bottom: 1px solid #ccc;
  text-decoration: none; color: #333;
}
.mobile-tabs a.active { font-weight: bold; color: #0ABAB5; }

@media(max-width: 768px) {
  .tab-buttons { display: none; }
  .mobile-menu-toggle { display: block; margin-bottom: 10px; }
  .mobile-menu-toggle button {
    font-size: 24px;
    background: none;
    border: none;
    cursor: pointer;
  }
}
</style>

<script>
function toggleMobileTabs() {
  const menu = document.getElementById('mobileTabs');
  menu.style.display = menu.style.display === 'flex' ? 'none' : 'flex';
}
</script>
