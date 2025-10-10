<?php
/**
 * Shortcode: [tp_blog]
 * Path: wp-content/themes/astra-child/General/tp-blog-shortcode.php
 *
 * Attributes (all optional):
 *  - posts_per_page : default 9
 *  - category       : slug or comma-separated slugs (e.g., "news,updates")
 *  - columns        : 2 or 3 (default 3)
 *  - show_sidebar   : 0/1 (default 1)
 *  - show_author    : 0/1 (default 1)
 *  - show_date      : 0/1 (default 1)
 *  - excerpt_length : number of words (default 22)
 *  - orderby        : date | title | modified | rand (default date)
 *  - order          : DESC | ASC (default DESC)
 *
 * Example:
 *   [tp_blog posts_per_page="12" category="news" columns="3" show_sidebar="1" excerpt_length="24"]
 */

if (!defined('ABSPATH')) { exit; }

/**
 * Print inline CSS & JS once per page render (prevents duplicates if shortcode used multiple times).
 */
function tp_blog_inline_assets_once() {
  static $printed = false;
  if ($printed) return;
  $printed = true;

  // ---- INLINE CSS ----
  echo '<style id="tp-blog-inline-css">
  /* Layout */
  .tp-blog-wrap{display:grid;grid-template-columns:1fr 320px;gap:28px}
  .tp-no-sidebar{grid-template-columns:1fr!important}
  .tp-blog-main{min-width:0}
  .tp-grid{display:grid;gap:24px}
  .tp-grid.tp-col-3{grid-template-columns:repeat(3,minmax(0,1fr))}
  .tp-grid.tp-col-2{grid-template-columns:repeat(2,minmax(0,1fr))}

  @media (max-width:1024px){
    .tp-blog-wrap.tp-has-sidebar{grid-template-columns:1fr}
    .tp-grid.tp-col-3{grid-template-columns:repeat(2,minmax(0,1fr))}
  }
  @media (max-width:640px){
    .tp-grid.tp-col-3,.tp-grid.tp-col-2{grid-template-columns:1fr}
  }

  /* Card */
  .tp-card{background:#fff;border:1px solid #e9ecef;border-radius:16px;overflow:hidden;display:flex;flex-direction:column;transition:transform .15s ease,box-shadow .15s ease}
  .tp-card:hover{transform:translateY(-2px);box-shadow:0 10px 24px rgba(0,0,0,.06)}
  .tp-thumb{display:block;position:relative}
  .tp-thumb img{display:block;width:100%;height:220px;object-fit:cover}
  .tp-thumb__placeholder{width:100%;height:220px;background:#f3f4f6}
  .tp-card__body{padding:16px 16px 18px;display:flex;flex-direction:column;gap:10px}
  .tp-badges{display:flex;gap:8px;flex-wrap:wrap}
  .tp-badge{display:inline-block;padding:4px 10px;font-size:12px;background:#f1f5f9;color:#0f172a;border-radius:999px;text-decoration:none}
  .tp-title{font-size:20px;line-height:1.3;margin:0}
  .tp-title a{text-decoration:none;color:#0f172a}
  .tp-title a:hover{text-decoration:underline}
  .tp-meta{color:#6b7280;font-size:13px;display:flex;gap:8px;align-items:center}
  .tp-excerpt{color:#374151;margin:0}
  .tp-readmore{align-self:flex-start;text-decoration:none;font-weight:600;padding:8px 12px;border-radius:999px;border:1px solid #e5e7eb}
  .tp-readmore:hover{background:#0ea5e9;color:#fff;border-color:#0ea5e9}

  /* Sidebar */
  .tp-blog-sidebar{display:flex;flex-direction:column;gap:22px}
  .tp-widget{background:#fff;border:1px solid #e9ecef;border-radius:12px;padding:16px}
  .tp-widget__title{margin:0 0 12px;font-size:16px}
  .tp-list{list-style:none;padding:0;margin:0}
  .tp-list li{padding:6px 0;border-bottom:1px dashed #eef2f7}
  .tp-list li:last-child{border-bottom:0}

  /* Pagination */
  .tp-pagination{display:flex;flex-wrap:wrap;gap:8px;margin-top:20px}
  .tp-pagination .page-numbers{padding:8px 12px;border:1px solid #e5e7eb;border-radius:8px;text-decoration:none;color:#111827}
  .tp-pagination .current{background:#111827;color:#fff;border-color:#111827}

  /* Search form (theme-compatible) */
  .tp-blog-sidebar .search-form{display:flex;gap:8px}
  .tp-blog-sidebar .search-form input[type="search"]{flex:1;border:1px solid #e5e7eb;border-radius:8px;padding:8px 10px}
  .tp-blog-sidebar .search-form input[type="submit"]{border:1px solid #111827;background:#111827;color:#fff;border-radius:8px;padding:8px 12px;cursor:pointer}
  </style>';

  // ---- INLINE JS (progressive enhancement): makes entire card clickable by delegating click on title) ----
  echo '<script id="tp-blog-inline-js">
  (function(){
    document.addEventListener("click", function(e){
      var card = e.target.closest && e.target.closest(".tp-card");
      if(!card) return;
      var link = card.querySelector(".tp-title a");
      var isInteractive = e.target.closest("a, button, input, textarea, select, .tp-badge, .tp-readmore");
      if(!isInteractive && link){
        link.click();
      }
    }, {passive:true});
  })();
  </script>';
}

/**
 * Main shortcode renderer
 */
function tp_render_blog_shortcode($atts) {
  $a = shortcode_atts([
    'posts_per_page' => 9,
    'category'       => '',
    'columns'        => 3,
    'show_sidebar'   => 1,
    'show_author'    => 1,
    'show_date'      => 1,
    'excerpt_length' => 22,
    'orderby'        => 'date',
    'order'          => 'DESC',
  ], $atts, 'tp_blog');

  // Ensure valid ints/bools
  $posts_per_page = max(1, intval($a['posts_per_page']));
  $columns        = (intval($a['columns']) === 2) ? 2 : 3;
  $show_sidebar   = intval($a['show_sidebar']) === 1;
  $show_author    = intval($a['show_author']) === 1;
  $show_date      = intval($a['show_date']) === 1;
  $excerpt_len    = max(5, intval($a['excerpt_length']));
  $orderby        = in_array($a['orderby'], ['date','title','modified','rand'], true) ? $a['orderby'] : 'date';
  $order          = (strtoupper($a['order']) === 'ASC') ? 'ASC' : 'DESC';

  $paged = max(1, get_query_var('paged') ?: get_query_var('page') ?: 1);

  $tax_query = [];
  if (!empty($a['category'])) {
    $cats = array_filter(array_map('trim', explode(',', $a['category'])));
    if ($cats) {
      $tax_query[] = [
        'taxonomy' => 'category',
        'field'    => 'slug',
        'terms'    => $cats,
      ];
    }
  }

  $q = new WP_Query([
    'post_type'           => 'post',
    'post_status'         => 'publish',
    'posts_per_page'      => $posts_per_page,
    'paged'               => $paged,
    'tax_query'           => $tax_query,
    'ignore_sticky_posts' => true,
    'orderby'             => $orderby,
    'order'               => $order,
  ]);

  // Print inline CSS/JS once
  tp_blog_inline_assets_once();

  // Start buffer
  ob_start();
  ?>
  <div class="tp-blog-wrap <?php echo $show_sidebar ? 'tp-has-sidebar' : 'tp-no-sidebar'; ?>">
    <main class="tp-blog-main">
      <?php if ($q->have_posts()): ?>
        <div class="tp-grid <?php echo ($columns === 2) ? 'tp-col-2' : 'tp-col-3'; ?>">
          <?php while ($q->have_posts()): $q->the_post(); ?>
            <article class="tp-card" aria-label="<?php echo esc_attr(get_the_title()); ?>">
              <a class="tp-thumb" href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr(get_the_title()); ?>">
                <?php if (has_post_thumbnail()) {
                  the_post_thumbnail('large', ['loading' => 'lazy']);
                } else { ?>
                  <div class="tp-thumb__placeholder" aria-hidden="true"></div>
                <?php } ?>
              </a>

              <div class="tp-card__body">
                <div class="tp-badges">
                  <?php
                  $cats = get_the_category();
                  if ($cats) {
                    $i = 0;
                    foreach ($cats as $c) {
                      printf(
                        '<a class="tp-badge" href="%s">%s</a>',
                        esc_url(get_category_link($c->term_id)),
                        esc_html($c->name)
                      );
                      if (++$i >= 2) break;
                    }
                  }
                  ?>
                </div>

                <h2 class="tp-title">
                  <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                </h2>

                <?php if ($show_author || $show_date): ?>
                  <div class="tp-meta">
                    <?php if ($show_date): ?><span><?php echo esc_html(get_the_date()); ?></span><?php endif; ?>
                    <?php if ($show_author && $show_date): ?><span>•</span><?php endif; ?>
                    <?php if ($show_author): ?><span><?php the_author(); ?></span><?php endif; ?>
                  </div>
                <?php endif; ?>

                <p class="tp-excerpt">
                  <?php
                    $excerpt = get_the_excerpt();
                    if (!$excerpt) { $excerpt = wp_strip_all_tags(get_the_content()); }
                    echo esc_html(wp_trim_words($excerpt, $excerpt_len, '…'));
                  ?>
                </p>

                <a class="tp-readmore" href="<?php the_permalink(); ?>" aria-label="Read more about <?php echo esc_attr(get_the_title()); ?>">Read more →</a>
              </div>
            </article>
          <?php endwhile; ?>
        </div>

        <nav class="tp-pagination" aria-label="Pagination">
          <?php
            // Preserve existing query vars (like category pages where shortcode is used)
            $big = 999999999;
            echo paginate_links([
              'base'      => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
              'format'    => '?paged=%#%',
              'current'   => $paged,
              'total'     => $q->max_num_pages,
              'mid_size'  => 2,
              'prev_text' => '« Prev',
              'next_text' => 'Next »',
              'type'      => 'list', // WP outputs <ul> list
            ]);
          ?>
        </nav>
      <?php else: ?>
        <p>No posts found.</p>
      <?php endif; wp_reset_postdata(); ?>
    </main>

    <?php if ($show_sidebar): ?>
      <aside class="tp-blog-sidebar" aria-label="Blog sidebar">
        <section class="tp-widget">
          <h3 class="tp-widget__title">Search</h3>
          <?php get_search_form(); ?>
        </section>

        <section class="tp-widget">
          <h3 class="tp-widget__title">Recent Posts</h3>
          <ul class="tp-list">
            <?php
            $recent = wp_get_recent_posts(['numberposts' => 5, 'post_status' => 'publish']);
            foreach ($recent as $rp) {
              printf('<li><a href="%s">%s</a></li>',
                esc_url(get_permalink($rp['ID'])),
                esc_html($rp['post_title'])
              );
            }
            ?>
          </ul>
        </section>

        <section class="tp-widget">
          <h3 class="tp-widget__title">Categories</h3>
          <ul class="tp-list">
            <?php
            $cats = get_categories(['hide_empty' => true]);
            foreach ($cats as $c) {
              printf('<li><a href="%s">%s</a></li>',
                esc_url(get_category_link($c->term_id)),
                esc_html($c->name)
              );
            }
            ?>
          </ul>
        </section>
      </aside>
    <?php endif; ?>
  </div>
  <?php

  return ob_get_clean();
}
add_shortcode('tp_blog', 'tp_render_blog_shortcode');

