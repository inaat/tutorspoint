<?php
add_shortcode('tp_pricing_badges', function ($atts) {
  global $wpdb;

  $a = shortcode_atts([
    'per'           => 'per session',
    'subjects_page' => '',     // e.g. /subjects/ ; leave empty to auto-detect
    'debug'         => '0',
  ], $atts, 'tp_pricing_badges');

  $debug = $a['debug'] === '1';

  // ---- core query: group levels by price (your schema) ----
  $sqlCore = "
    SELECT
      r.hourly_rate,
      COALESCE(r.currency,'GBP') AS currency,
      GROUP_CONCAT(DISTINCT cl.level_name ORDER BY cl.level_name SEPARATOR '||') AS level_names,
      GROUP_CONCAT(DISTINCT cl.id ORDER BY cl.id SEPARATOR ',') AS level_ids
    FROM wpC_level_hourly_rates r
    JOIN wpC_class_levels cl ON cl.id = r.level_id
    WHERE r.status = 1
      AND (r.effective_from IS NULL OR r.effective_from <= CURDATE())
      AND (r.effective_to   IS NULL OR r.effective_to   >= CURDATE())
    GROUP BY r.hourly_rate, currency
    ORDER BY r.hourly_rate ASC
  ";
  $groups = $wpdb->get_results($sqlCore);

  // if nothing (e.g., dates not set yet), retry without date filters
  if (!$groups || $wpdb->last_error) {
    $sqlCore = "
      SELECT
        r.hourly_rate,
        COALESCE(r.currency,'GBP') AS currency,
        GROUP_CONCAT(DISTINCT cl.level_name ORDER BY cl.level_name SEPARATOR '||') AS level_names,
        GROUP_CONCAT(DISTINCT cl.id ORDER BY cl.id SEPARATOR ',') AS level_ids
      FROM wpC_level_hourly_rates r
      JOIN wpC_class_levels cl ON cl.id = r.level_id
      WHERE r.status = 1
      GROUP BY r.hourly_rate, currency
      ORDER BY r.hourly_rate ASC
    ";
    $groups = $wpdb->get_results($sqlCore);
  }

  if (!$groups) {
    $msg = 'No price data found.';
    if ($debug && $wpdb->last_error) {
      $msg .= ' SQL error: ' . esc_html($wpdb->last_error);
    }
    return '<div class="tp-price-badges-empty">'.$msg.'</div>';
  }

  // ---- optional: subjects for each group (best-effort; skip on mismatch) ----
  $subjects_url = !empty($a['subjects_page'])
    ? esc_url($a['subjects_page'])
    : ( ($p = get_page_by_path('subjects')) ? get_permalink($p) : home_url('/subjects/') );

  // Detect subjects table columns (label + id)
  $T_SUBJECT = 'wpC_subjects';
  $T_MAP     = 'wpC_subjects_level';

  $subCols = $wpdb->get_col("SHOW COLUMNS FROM {$T_SUBJECT}");
  $mapCols = $wpdb->get_col("SHOW COLUMNS FROM {$T_MAP}");

  $subject_label = 'subject_name';
  if ($subCols && !in_array($subject_label, $subCols, true)) {
    foreach (['name','subject','title','label'] as $c) {
      if (in_array($c, $subCols, true)) { $subject_label = $c; break; }
    }
    if (!in_array($subject_label, (array)$subCols, true)) { $subject_label = null; }
  }
  $subject_id = ($subCols && in_array('subject_id', $subCols, true)) ? 'subject_id' : (($subCols && in_array('id',$subCols,true)) ? 'id' : null);
  $map_level_fk   = ($mapCols && in_array('level_id',   $mapCols, true)) ? 'level_id'   : (($mapCols && in_array('class_level_id',$mapCols,true)) ? 'class_level_id' : null);
  $map_subject_fk = ($mapCols && in_array('subject_id', $mapCols, true)) ? 'subject_id' : (($mapCols && in_array('sid',$mapCols,true))            ? 'sid'          : null);

  $canFetchSubjects = ($subject_label && $subject_id && $map_level_fk && $map_subject_fk);

  // helpers
  $sym = function($c){ $c=strtoupper((string)$c);
    return ['GBP'=>'£','USD'=>'$','EUR'=>'€','PKR'=>'₨','INR'=>'₹','AED'=>'د.إ'][$c] ?? $c;
  };

  $compact_levels = function(array $labels){
    $labels = array_values(array_unique(array_map('trim', $labels)));
    if (!$labels) return '';
    if (count($labels) === 1) return $labels[0];

    $parsed = [];
    foreach ($labels as $lb) {
      if (preg_match('/^KS\s*([0-9]+)(?:\s*\/\s*([0-9]+))?$/i', $lb, $m)) {
        $first  = (int)$m[1];
        $second = isset($m[2]) ? (int)$m[2] : null;
        $parsed[] = ['label'=>$lb,'key'=>$first + ($second ? 0.5 : 0.0)];
      } else {
        // non-KS mix → list them as-is
        return implode(', ', $labels);
      }
    }
    usort($parsed, fn($a,$b)=>$a['key']<=>$b['key']);
    return $parsed[0]['label'].' - '.$parsed[count($parsed)-1]['label'];
  };

  $title_by_rate = [
    10 => 'Primary',
    12 => 'Lower Secondary',
    15 => 'Secondary',
    20 => 'Advance Level',
    25 => 'Special Level',
  ];

  ob_start(); ?>
<style>
  /* --- 1) MAKE THE PARENT CONTAINER FULL-WIDTH WHEN THE GRID IS INSIDE IT --- */
  /* Works on modern Chromium/Firefox/Safari. If your browser is very old, add a section class and use the fallback below. */
  @media (min-width: 992px){
    /* Column that contains the grid should take 100% width */
    .elementor-column:has(.tp-badges-grid){
      flex: 0 0 100% !important;
      max-width: 100% !important;
    }
    /* The container that holds that column can be wider (adjust 1200px if you like) */
    .elementor-container:has(.tp-badges-grid){
      max-width: 1200px !important;
    }
    /* The shortcode widget itself should not be narrow */
    .elementor-widget-shortcode:has(.tp-badges-grid){
      width: 100% !important;
    }
  }

  /* --- 2) RESPONSIVE GRID --- */
  .tp-badges-grid{
    display:grid;
    gap:clamp(12px, 2.5vw, 20px);
    /* auto-fit as many cards as fit, min card width ~260px for nice 2–4 cols */
    grid-template-columns:repeat(auto-fit, minmax(260px, 1fr));
    align-items:stretch;
    width:100%;
    margin-inline:auto;
    max-width:1200px; /* keeps line-length tidy on very wide screens */
  }

  /* Card */
  .tp-badge{
    display:flex;
    flex-direction:column;
    justify-content:flex-start;
    border-radius:18px;
    padding:clamp(14px, 2.5vw, 22px);
    background:linear-gradient(180deg,#61CE70 0%, #0b3b35 100%);
    color:#fff;
    box-shadow:0 10px 18px rgba(0,0,0,.08);
    max-width:100%;
  }

  /* Type scales with viewport */
  .tp-badge-title{
    font-weight:200;
    font-size:clamp(16px, 2.8vw, 18px);
    margin:0 0 10px;
    text-shadow:0 1px 0 rgba(0,0,0,.25);
     text-align: center;
  }
  .tp-price{
    display:flex;
    flex-wrap:wrap; /* prevents overflow on small screens */
    align-items:flex-end;
    gap:6px;
    margin:.25rem 0 .75rem;
     text-align: center;
    
  }
  .tp-price-amount{
    font-size:clamp(18px, 7vw, 35px);
    font-weight:200;
    line-height:1;

  }
  .tp-price-tail{
    font-size:clamp(12px, 2.8vw, 14px);
    opacity:.95;
    margin-bottom:6px;
  }

  .tp-level-line,
  .tp-subjects{
    font-size:clamp(13px, 2.6vw, 14px);
  }
  .tp-subjects a{
    color:#c6fff3;
    text-decoration:underline;
  }

  /* Long text safety */
  .tp-badge *{ overflow-wrap:anywhere; word-break:break-word; }

  /* Small phones: force single column */
  @media (max-width: 480px){
    .tp-badges-grid{ grid-template-columns: 1fr; }
  }

  /* --- 3) OPTIONAL FALLBACK if your browser doesn't support :has():
     Add the class "tp-pricing-section" to the Elementor section and keep this block. */
  @media (min-width: 992px){
    .tp-pricing-section > .elementor-container { max-width: 1200px; }
    .tp-pricing-section .elementor-column { flex: 0 0 100% !important; max-width: 100% !important; }
    .tp-pricing-section .elementor-widget-shortcode { width: 100% !important; }
  }
</style>


  <div class="tp-badges-grid">
    <?php foreach ($groups as $g):
      $rate = (float)$g->hourly_rate;
      $cur  = $sym($g->currency);
      $levels = array_filter(array_map('trim', explode('||', (string)$g->level_names)));
      $level_ids = array_map('intval', array_filter(array_map('trim', explode(',', (string)$g->level_ids))));
      $levelLine = $compact_levels($levels);
      $title = $title_by_rate[(int)round($rate)] ?? 'Level Group';

      // subjects (best effort)
      $top3 = [];
      if ($canFetchSubjects && $level_ids) {
        // sanitize ids for IN (...)
        $ids = implode(',', array_map('intval', $level_ids));
        $sqlSub = "
          SELECT DISTINCT s.`{$subject_label}` AS label
          FROM {$T_SUBJECT} s
          JOIN {$T_MAP}    sl ON sl.`{$map_subject_fk}` = s.`{$subject_id}`
          WHERE sl.`{$map_level_fk}` IN ({$ids})
          ORDER BY s.`{$subject_label}`
          LIMIT 4
        ";
        $subRows = $wpdb->get_col($sqlSub);
        if ($subRows && !$wpdb->last_error) {
          $top3 = array_slice($subRows, 0, 3);
        }
      }
      $moreHref = add_query_arg(['levels' => implode(',', $level_ids)], $subjects_url);
      ?>
      <article class="tp-badge">
        <h4 class="tp-badge-title"><?php echo esc_html($title); ?></h4>
        <div class="tp-price">
          <div class="tp-price-amount">
            <?php echo esc_html($cur . (fmod($rate,1)==0?number_format($rate,0):number_format($rate,2))); ?>
            <span style="font-weight:400">/-</span>
          </div>
          <div class="tp-price-tail"><?php echo esc_html($a['per']); ?></div>
        </div>
        <?php if ($levelLine): ?>
          <div class="tp-level-line"><?php echo esc_html($levelLine); ?></div>
        <?php endif; ?>
        <?php if ($top3): ?>
          <div class="tp-subjects">
            <?php echo esc_html(implode(', ', $top3)); ?>
            &nbsp;<a href="<?php echo esc_url($moreHref); ?>">More</a>
          </div>
        <?php else: ?>
          <div class="tp-subjects">
            <a href="<?php echo esc_url($moreHref); ?>">More</a>
          </div>
        <?php endif; ?>
      </article>
    <?php endforeach; ?>
  </div>

  <?php if ($debug): ?>
    <pre style="background:#0b3b35;color:#d4fff7;padding:10px;border-radius:10px;overflow:auto">
SQL(groups):
<?php echo esc_html($sqlCore); ?>


subjects_label=<?php echo esc_html((string)$subject_label); ?> id=<?php echo esc_html((string)$subject_id); ?>

map level_fk=<?php echo esc_html((string)$map_level_fk); ?> subject_fk=<?php echo esc_html((string)$map_subject_fk); ?>

last_error=<?php echo esc_html((string)$wpdb->last_error); ?>

    </pre>
  <?php endif;

  return ob_get_clean();
});
