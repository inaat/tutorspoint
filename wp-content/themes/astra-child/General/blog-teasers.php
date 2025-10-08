<?php
/**
 * Component: Blog Teasers (title + 2–3 lines + "Read more")
 * Path: /wp-content/themes/astra-child/General/blog-teasers.php
 * Usage shortcode: [tp_blog_teasers posts="6" lines="3" readmore="Read more" cat=""]
 */

if (!defined('ABSPATH')) exit;

add_action('init', function () {
  if (shortcode_exists('tp_blog_teasers')) return;

  add_shortcode('tp_blog_teasers', function($atts){
    $a = shortcode_atts([
      'posts'    => 6,            // how many posts
      'lines'    => 3,            // number of lines to show
      'readmore' => 'Read more',  // link text
      'cat'      => '',           // optional category slug
    ], $atts, 'tp_blog_teasers');

    $q_args = [
      'post_type'      => 'post',
      'post_status'    => 'publish',
      'posts_per_page' => (int)$a['posts'],
      'no_found_rows'  => true,
    ];
    if (!empty($a['cat'])) {
      $q_args['category_name'] = sanitize_title($a['cat']);
    }

    $q = new WP_Query($q_args);
    if (!$q->have_posts()) return '<div class="tp-blog-teasers">No posts found.</div>';

    ob_start(); ?>
    <div class="tp-blog-teasers">
      <?php while($q->have_posts()): $q->the_post();
        $title = get_the_title();
        $link  = get_permalink();
        // Prefer manual excerpt; fallback to trimmed content
        $raw   = get_the_excerpt();
        if (!$raw) { $raw = wp_strip_all_tags( get_the_content(null,false) ); }
        $teaser = esc_html( wp_trim_words($raw, 40, '') );
      ?>
        <article class="tpb-item">
          <h4 class="tpb-title">
            <a href="<?php echo esc_url($link); ?>"><?php echo esc_html($title); ?></a>
          </h4>
          <p class="tpb-excerpt" data-lines="<?php echo (int)$a['lines']; ?>">
            <?php echo $teaser; ?>
          </p>
          <p class="tpb-more">
            <a class="tpb-readmore" href="<?php echo esc_url($link); ?>">
              <?php echo esc_html($a['readmore']); ?>
            </a>
          </p>
        </article>
      <?php endwhile; wp_reset_postdata(); ?>
    </div>

    <style>
      .tp-blog-teasers{font-family:Roboto,Arial,sans-serif;display:grid;gap:14px}
      .tpb-item{padding:8px 0;border-bottom:1px solid rgba(255,255,255,.15)}
      .tpb-title{margin:0 0 6px;font-size:16px;line-height:1.35}
      .tpb-title a{text-decoration:none}
      .tpb-title a:hover{opacity:.9}
      /* 2–3 line clamp; JS sets the clamp value from data-lines */
      .tpb-excerpt{
        margin:0 0 6px;
        display:-webkit-box;
        -webkit-box-orient:vertical;
        overflow:hidden;
        line-height:1.5;
        -webkit-line-clamp:3;
      }
      .tpb-more{margin:0}
      .tpb-readmore{text-decoration:underline}
    </style>

    <script>
      (function(){
        document.querySelectorAll('.tpb-excerpt[data-lines]').forEach(function(p){
          var n = parseInt(p.getAttribute('data-lines'), 10) || 3;
          p.style.setProperty('-webkit-line-clamp', String(n));
        });
      })();
    </script>
    <?php
    return ob_get_clean();
  });
});
