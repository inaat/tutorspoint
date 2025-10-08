<?php
/**
 * Shortcode: [tp_levels_list link_base="/levels?level=" target="_self"]
 * Source table: wpC_class_levels  (auto-falls back to <prefix>class_levels)
 * Features: 6 items per page, Prev/Next with AJAX (no page reload)
 */
if (!defined('ABSPATH')) exit;

add_shortcode('tp_levels_list', function($atts) {
  global $wpdb;

  $a = shortcode_atts([
    'link_base' => '#',
    'target'    => '_self',
  ], $atts, 'tp_levels_list');

  // Detect table/prefix
  $hasCustom = $wpdb->get_var("SHOW TABLES LIKE 'wpC_class_levels'");
  $P = ($hasCustom === 'wpC_class_levels') ? 'wpC_' : $wpdb->prefix;
  $table = $P . 'class_levels';

  // Columns (best guesses – adjust here if your names differ)
  $cols = $wpdb->get_col("SHOW COLUMNS FROM {$table}");
  if (!$cols) return '<div class="tp-levels-list">No class levels table found.</div>';

  $idCol = null; foreach (['level_id','id','LevelID','ID'] as $c) if (in_array($c,$cols,true)) { $idCol=$c; break; }
  $nameCol = null; foreach (['level_name','name','title','LevelName'] as $c) if (in_array($c,$cols,true)) { $nameCol=$c; break; }
  $activeCol = null; foreach (['is_active','status','active'] as $c) if (in_array($c,$cols,true)) { $activeCol=$c; break; }
  $orderCol = null; foreach (['display_order','sort_order','position','order_no'] as $c) if (in_array($c,$cols,true)) { $orderCol=$c; break; }

  if (!$idCol || !$nameCol) {
    return '<div class="tp-levels-list">Class levels table exists but required columns are missing.</div>';
  }

  // Count total (for button state on first render)
  $where = $activeCol ? "WHERE {$activeCol} NOT IN (0,'0','inactive','INACTIVE')" : '';
  $total = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$table} {$where}");
  $perPage = 6;

  // Unique instance id (supports multiple shortcodes on same page)
  $inst = 'tplv_' . wp_generate_password(8, false, false);

  // Nonce for AJAX
  $nonce = wp_create_nonce('tp_levels_nonce');

  ob_start(); ?>
  <div id="<?= esc_attr($inst) ?>" class="tp-levels-list" data-inst="<?= esc_attr($inst) ?>">
    <ul class="tp-levels-ul" role="list" data-page="0"></ul>

    <div class="tp-levels-nav">
    <button type="button" class="tp-btn tp-cta prev" disabled>
    <span class="chev">‹</span> Prev
    </button>
    
    <span class="tp-pager">
    <span class="tp-page">1</span>
    <span class="sep">/</span>
    <span class="tp-pages"><?= ceil(max($total,1)/$perPage) ?></span>
    </span>
    
    <button type="button" class="tp-btn tp-cta next" <?= ($total>$perPage)?'':'disabled' ?>>
    Next <span class="chev">›</span>
    </button>
    </div>

  </div>

  <style>
    .tp-levels-list { font-family:'Roboto', Arial, sans-serif; }
    .tp-levels-list .tp-levels-ul { margin:0; padding:0; list-style:none; }
    .tp-levels-list .tp-level-item { margin:8px 0; }
    .tp-levels-list .tp-level-link{
      display:inline-flex; align-items:center; gap:10px;
      text-decoration:none; color:white;
      font-size:10px; font-weight:300; line-height:1.0;
      transition: transform .18s ease, color .18s ease, opacity .18s ease;
      will-change: transform;
    }
    .tp-levels-list .tp-level-link .arrow{
      display:inline-block; transform:translateX(0);
      transition: transform .18s ease, opacity .18s ease;
      font-size:10px;
    }
    .tp-levels-list .tp-level-item:hover > .tp-level-link { transform: translateX(6px); }
    .tp-levels-list .tp-level-item:hover > .tp-level-link .arrow { transform: translateX(2px); }
    
    /* Compact Prev/Next buttons */
    .tp-levels-list .tp-btn {
      background-color: #fff;    /* white background */
      color: #000;               /* black text for readability */
      border: 1px solid #ccc;    /* subtle border */
      border-radius: 3px;        /* small rounding */
      font-size: 10px;           /* compact font */
      font-weight: 200;
      padding: 2px 3px;          /* reduced padding */
      line-height: 1.2;
      cursor: pointer;
    }
    
    /* Disabled buttons still look compact */
    .tp-levels-list .tp-btn[disabled] {
      opacity: 0.5;
      cursor: not-allowed;
    }
    
    /* Page count in white */
    .tp-levels-list .tp-pager {
      color: #fff;
      font-size: 10px;
      font-weight: 200;
    }

    .tp-levels-list .tp-btn {
      background-color: #1a9d85;
      color: #fff; /* makes text white too */
      border: 1px solid #1a9d85;
    }



  </style>

  <script>
  (function(){
    const inst    = "<?= esc_js($inst) ?>";
    const root    = document.getElementById(inst);
    if(!root) return;

    const ul      = root.querySelector('.tp-levels-ul');
    const prevBtn = root.querySelector('.tp-btn.prev');
    const nextBtn = root.querySelector('.tp-btn.next');
    const pageLbl = root.querySelector('.tp-page');
    const pagesLbl= root.querySelector('.tp-pages');

    const perPage = 6;
    const total   = parseInt(<?= (int)$total ?>, 10);
    const pages   = Math.max(1, Math.ceil(total / perPage));
    const linkBase= "<?= esc_js($a['link_base']) ?>";
    const target  = "<?= esc_js($a['target']) ?>";
    const nonce   = "<?= esc_js($nonce) ?>";

    let page = 0;

    async function loadPage(p){
      p = Math.max(0, Math.min(p, pages-1));
      const params = new URLSearchParams({
        action: 'tp_levels_chunk',
        _ajax_nonce: nonce,
        page: p,
        per_page: perPage,
        inst: inst
      });
      const res = await fetch("<?= esc_url(admin_url('admin-ajax.php')) ?>", {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: params.toString()
      });
      const data = await res.json();
      if(!data || !data.success){ return; }

      // Render
      ul.innerHTML = '';
      data.items.forEach(item => {
        const li = document.createElement('li');
        li.className = 'tp-level-item';
        li.innerHTML =
          '<a class="tp-level-link" target="'+target+'" href="'+(linkBase + item.id).replace(/&amp;/g,'&')+'">' +
            '<span class="arrow" aria-hidden="true">➔</span>' +
            '<span class="label"></span>' +
          '</a>';
        li.querySelector('.label').textContent = item.label;
        ul.appendChild(li);
      });

      // State
      page = p;
      pageLbl.textContent = (page+1);
      prevBtn.disabled = (page <= 0);
      nextBtn.disabled = (page >= pages-1);
    }

    prevBtn && prevBtn.addEventListener('click', () => loadPage(page-1));
    nextBtn && nextBtn.addEventListener('click', () => loadPage(page+1));

    // initial
    loadPage(0);
  })();
  </script>
  <?php
  return ob_get_clean();
});

/** AJAX: return 6-level chunks */
add_action('wp_ajax_tp_levels_chunk', 'tp_levels_chunk_handler');
add_action('wp_ajax_nopriv_tp_levels_chunk', 'tp_levels_chunk_handler');
function tp_levels_chunk_handler(){
  check_ajax_referer('tp_levels_nonce');

  global $wpdb;

  $page     = isset($_POST['page']) ? max(0, intval($_POST['page'])) : 0;
  $perPage  = isset($_POST['per_page']) ? max(1, intval($_POST['per_page'])) : 6;

  $hasCustom = $wpdb->get_var("SHOW TABLES LIKE 'wpC_class_levels'");
  $P = ($hasCustom === 'wpC_class_levels') ? 'wpC_' : $wpdb->prefix;
  $table = $P . 'class_levels';

  $cols = $wpdb->get_col("SHOW COLUMNS FROM {$table}");
  if (!$cols) wp_send_json(['success'=>false,'items'=>[]]);

  $idCol    = null; foreach (['level_id','id','LevelID','ID'] as $c) if (in_array($c,$cols,true)) { $idCol=$c; break; }
  $nameCol  = null; foreach (['level_name','name','title','LevelName'] as $c) if (in_array($c,$cols,true)) { $nameCol=$c; break; }
  $activeCol= null; foreach (['is_active','status','active'] as $c) if (in_array($c,$cols,true)) { $activeCol=$c; break; }
  $orderCol = null; foreach (['display_order','sort_order','position','order_no'] as $c) if (in_array($c,$cols,true)) { $orderCol=$c; break; }

  if (!$idCol || !$nameCol) wp_send_json(['success'=>false,'items'=>[]]);

  $where = $activeCol ? "WHERE {$activeCol} NOT IN (0,'0','inactive','INACTIVE')" : '';
  $order = $orderCol ? "ORDER BY {$orderCol} ASC" : "ORDER BY {$nameCol} ASC";

  $offset = $page * $perPage;
  $sql = $wpdb->prepare("SELECT {$idCol} AS id, {$nameCol} AS label FROM {$table} {$where} {$order} LIMIT %d OFFSET %d", $perPage, $offset);
  $rows = $wpdb->get_results($sql);

  wp_send_json([
    'success' => true,
    'items'   => array_map(function($r){ return ['id'=>$r->id, 'label'=>$r->label]; }, $rows ?: []),
  ]);
}
