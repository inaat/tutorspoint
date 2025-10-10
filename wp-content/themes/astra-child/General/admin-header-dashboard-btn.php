<?php
/**
 * Shortcode: [tp_admin_header_btn]
 * Renders an inline "Admin Dashboard" pill for admins only.
 * Hidden automatically on /admin-dashboard/.
 */
if (!defined('ABSPATH')) exit;

function tp_admin_header_btn_shortcode() {
    // Only for logged-in admins
    if ( ! is_user_logged_in() || ! current_user_can('manage_options') ) return '';
    // Hide on the dashboard itself
    if ( is_page('admin-dashboard') ) return '';

    $url = esc_url( home_url('/admin-dashboard/') );

    ob_start(); ?>
      <a href="<?php echo $url; ?>" class="tp-admin-head-btn" aria-label="Admin Dashboard">
        Admin Dashboard
      </a>
      <style>
        /* Inline pill to sit next to "My Account" */
        .tp-admin-head-btn{
          display:inline-flex; align-items:center; justify-content:center;
          /* height tuned to match your header pills; tweak if needed */
          height: 30px; 
          padding: 0 14px;
          border-radius: 999px;
          background: linear-gradient(135deg,#0ec4a8,#18c4bd);
          color:#fff; text-decoration:none;
          font-size: 12px; font-weight: 100; line-height:1;
          margin-right: 10px;     /* puts it just before My Account */
          white-space: nowrap;
          box-shadow: 0 8px 18px rgba(0,0,0,.12);
          transition: transform .15s ease, box-shadow .15s ease, opacity .15s ease;
        }
        .tp-admin-head-btn:hover{ transform: translateY(-1px); box-shadow:0 12px 26px rgba(0,0,0,.18); }
        .tp-admin-head-btn:active{ transform: translateY(0); box-shadow:0 6px 14px rgba(0,0,0,.12); }
      </style>
    <?php
    return ob_get_clean();
}
add_shortcode('tp_admin_header_btn', 'tp_admin_header_btn_shortcode');
