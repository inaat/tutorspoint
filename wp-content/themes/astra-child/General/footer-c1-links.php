<?php
/**
 * Component: Footer Column 1 – Quick Links
 * Path: /wp-content/themes/astra-child/General/footer-c1-links.php
 *
 * PDFs are expected in: /wp-content/uploads/docs/
 *   - privacy-policy.pdf
 *   - usage-terms-conditions.pdf
 *   - fee-return-policy.pdf
 *   - lecture-time-change-policy.pdf
 *   - apply-for-teacher.pdf
 */

if (!defined('ABSPATH')) exit;

if (!shortcode_exists('tp_quick_links')) {
  add_shortcode('tp_quick_links', function($atts){
    // Default folder = "docs"
    $a = shortcode_atts([
      'folder' => 'docs',
    ], $atts, 'tp_quick_links');

    $uploads = wp_upload_dir();
    $base    = trailingslashit($uploads['baseurl']) . trailingslashit($a['folder']);

    // Inline SVG icons
    $svg = function($name){
      $icons = [
        'home'     => '<svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>',
        'contact'  => '<svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M21 8V7l-3 2-2-1-2 1-2-1-2 1-2-1-3 2v1l3-2 2 1 2-1 2 1 2-1 2 1 3-2zM3 6h18v12H3z"/></svg>',
        'info'     => '<svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M11 17h2v-6h-2v6zm0-8h2V7h-2v2zm1-7C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"/></svg>',
        'lock'     => '<svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M12 17a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm6-7h-1V7a5 5 0 0 0-10 0v3H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2zm-3 0H9V7a3 3 0 0 1 6 0v3z"/></svg>',
        'book'     => '<svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M18 2H7a3 3 0 0 0-3 3v14a3 3 0 0 0 3 3h11V2zM7 4h9v14H7a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1zm11 16H7a3 3 0 0 1-3-3v-1a4 4 0 0 0 3 1h11v3z"/></svg>',
        'refund'   => '<svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M12 6V3L8 7l4 4V8a4 4 0 1 1-4 4H6a6 6 0 1 0 6-6z"/></svg>',
        'calendar' => '<svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M7 2h2v2h6V2h2v2h3v18H4V4h3V2zm13 8H4v10h16V10z"/></svg>',
        'user'     => '<svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5zm0 2c-5 0-9 2.5-9 5.5V22h18v-2.5C21 16.5 17 14 12 14z"/></svg>',
      ];
      return $icons[$name] ?? '';
    };

    $items = [
      ['label'=>'Home',                        'url'=>home_url('/'),              'download'=>false, 'icon'=>'home'],
      ['label'=>'Contact Us',                  'url'=>home_url('/contact/'),      'download'=>false, 'icon'=>'contact'],
      ['label'=>'About Us',                    'url'=>home_url('/about/'),        'download'=>false, 'icon'=>'info'],
      ['label'=>'Privacy Policy',              'url'=>$base.'privacy-policy.pdf',               'download'=>true,  'icon'=>'lock'],
      ['label'=>'Usage Terms & Conditions',    'url'=>$base.'usage-terms-conditions.pdf',       'download'=>true,  'icon'=>'book'],
      ['label'=>'Fee Return Policy',           'url'=>$base.'fee-return-policy.pdf',            'download'=>true,  'icon'=>'refund'],
      ['label'=>'Lecture Time Change Policy',  'url'=>$base.'lecture-time-change-policy.pdf',   'download'=>true,  'icon'=>'calendar'],
      ['label'=>'Apply for Teacher',           'url'=>$base.'apply-for-teacher.pdf',            'download'=>true,  'icon'=>'user'],
    ];

    ob_start(); ?>
    <ul class="tp-quick-links" role="list">
      <?php foreach($items as $it):
        $url = esc_url($it['url']);
        $lbl = esc_html($it['label']);
        $dl  = !empty($it['download']) ? ' download' : '';
      ?>
        <li class="tp-ql-item">
          <a class="tp-ql-link" href="<?= $url ?>"<?= $dl ?>>
            <span class="ico"><?= $svg($it['icon']) ?></span>
            <span class="txt"><?= $lbl ?></span>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>

    <style>
      .tp-quick-links{margin:0;padding:0;list-style:none;font-family:Roboto,Arial,sans-serif}
      .tp-quick-links .tp-ql-item{margin:6px 0}
      .tp-quick-links .tp-ql-link{
        display:flex;align-items:center;gap:8px;
        color:#fff;text-decoration:none;font-size:11px;font-weight:100;
        transition:transform .18s ease,opacity .18s ease
      }
      .tp-quick-links .tp-ql-item:hover .tp-ql-link{transform:translateX(6px)}
      .tp-quick-links .ico{width:14px;height:14px;display:inline-flex;align-items:center;justify-content:center;line-height:1;color:#fff}
    </style>
    <?php
    return ob_get_clean();
  });
}
