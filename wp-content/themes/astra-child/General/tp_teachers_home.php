<?php
/**
 * Shortcode: [tp_teachers]
 * 
 * Button uses the same "Book Free Session" redirect logic:
 *  - Prefer currently selected page dropdowns (Level/Subject)
 *  - Fallback to the teacher's own first subject/level if dropdowns missing
 */

/**
 * 
 * Picks weekly-random teachers and renders cards.
 * Button builds URL ONLY from teacher's own subject/level mapping (no dropdown sniffing).
 */
add_shortcode('tp_teachers', function ($atts) {
  global $wpdb;

  $a = shortcode_atts([
    'status'        => '1',
    'limit'         => '0',
    'order'         => 'recent',     // recent|alpha
    'ids'           => '',           // CSV include list (keeps given order)
    'exclude'       => '',           // CSV exclude
    'weekly_random' => '3',          // pick this many per week (0 = disabled)
    'week_start'    => 'mon',        // mon|sun
    'button_text'   => 'Book Free Lecture',
  ], $atts, 'tp_teachers');

  /* ---------- 1) Teacher SELECT ---------- */
  $T_TEACH = 'wpC_teachers_main';
  $where = [];
  if ($a['status'] !== '') { $where[] = $wpdb->prepare('t.Status = %d', (int)$a['status']); }

  $ids_in = array_filter(array_map('intval', preg_split('/\s*,\s*/', (string)$a['ids'])));
  $ids_ex = array_filter(array_map('intval', preg_split('/\s*,\s*/', (string)$a['exclude'])));

  if (!empty($ids_in)) { $where[] = 't.teacher_id IN ('.implode(',', $ids_in).')'; }
  if (!empty($ids_ex)) { $where[] = 't.teacher_id NOT IN ('.implode(',', $ids_ex).')'; }
  $whereSql = $where ? ('WHERE '.implode(' AND ', $where)) : '';

  $orderSql = ($a['order'] === 'alpha') ? 'ORDER BY t.FullName ASC' : 'ORDER BY t.created_at DESC';
  $limitSql = ((int)$a['limit'] > 0) ? $wpdb->prepare('LIMIT %d', (int)$a['limit']) : '';

  // Weekly deterministic "random", stable within week
  $weekMode = (strtolower($a['week_start']) === 'sun' || $a['week_start'] === '0') ? 0 : 1;
  $weeklyN  = max(0, (int)$a['weekly_random']);
  $selectWeeklyExpr = '';
  if ($weeklyN > 0 && empty($ids_in)) {
    $selectWeeklyExpr = ", SHA1(CONCAT(t.teacher_id, '-', YEARWEEK(CURDATE(), {$weekMode}))) AS weekly_rand";
    $orderSql = 'ORDER BY weekly_rand ASC';
    $limitSql = $wpdb->prepare('LIMIT %d', $weeklyN);
  }

  $sqlTeachers = "
    SELECT t.teacher_id, t.FullName, t.experience_intro, t.object_note, t.Photo, t.intro_video_url
           {$selectWeeklyExpr}
    FROM {$T_TEACH} t
    {$whereSql}
    {$orderSql}
    {$limitSql}
  ";
  $teachers = $wpdb->get_results($sqlTeachers);
  if (!$teachers) return '<div class="tp-teachers-empty">No teachers found.</div>';

  $teacherIds = array_map(fn($t)=>(int)$t->teacher_id, $teachers);
  $idList = implode(',', $teacherIds);

  /* ---------- 2) Detect mapping tables & aggregate subjects/levels ---------- */
  $T_ALLOC = 'wpC_teacher_allocated_subjects';
  $T_SL    = 'wpC_subjects_level';
  $T_SUBJ  = 'wpC_subjects';
  $T_LEVEL = 'wpC_class_levels';

  $colsSL    = (array)$wpdb->get_col("SHOW COLUMNS FROM {$T_SL}");
  $colsSUBJ  = (array)$wpdb->get_col("SHOW COLUMNS FROM {$T_SUBJ}");
  $colsLEVEL = (array)$wpdb->get_col("SHOW COLUMNS FROM {$T_LEVEL}");

  $sl_pk       = in_array('id', $colsSL, true) ? 'id' : (in_array('subject_level_id',$colsSL,true) ? 'subject_level_id' : null);
  $sl_subject  = in_array('subject_id', $colsSL, true) ? 'subject_id' : (in_array('sid',$colsSL,true) ? 'sid' : null);
  $sl_level    = in_array('level_id',   $colsSL, true) ? 'level_id'   : (in_array('class_level_id',$colsSL,true) ? 'class_level_id' : null);

  $subj_id    = in_array('subject_id', $colsSUBJ, true) ? 'subject_id' : (in_array('id',$colsSUBJ,true) ? 'id' : null);
  $subj_label = null; foreach (['subject_name','SubjectName','name','title','label','subject'] as $c) { if (in_array($c,$colsSUBJ,true)) { $subj_label=$c; break; } }

  $level_id    = in_array('id', $colsLEVEL, true) ? 'id' : (in_array('level_id',$colsLEVEL,true) ? 'level_id' : null);
  $level_label = in_array('level_name',$colsLEVEL,true) ? 'level_name' : (in_array('name',$colsLEVEL,true) ? 'name' : null);

  $byTeacher = [];          // teacher_id => [subjects[], levels[]]
  $primaryMap = [];         // teacher_id => ['subject_id','subject','level_id','level']
  if ($sl_pk && $sl_subject && $sl_level && $subj_id && $subj_label && $level_id && $level_label) {
    // A) aggregated lists (names)
    $sqlAgg = "
      SELECT tas.teacher_id,
             GROUP_CONCAT(DISTINCT s.`{$subj_label}` ORDER BY s.`{$subj_label}` SEPARATOR '||') AS subj_list,
             GROUP_CONCAT(DISTINCT cl.`{$level_label}` ORDER BY cl.`{$level_label}` SEPARATOR '||') AS level_list
      FROM {$T_ALLOC} tas
      LEFT JOIN {$T_SL} sl  ON sl.`{$sl_pk}` = tas.subject_level_id
      LEFT JOIN {$T_SUBJ} s ON s.`{$subj_id}` = sl.`{$sl_subject}`
      LEFT JOIN {$T_LEVEL} cl ON cl.`{$level_id}` = sl.`{$sl_level}`
      WHERE tas.teacher_id IN ({$idList})
      GROUP BY tas.teacher_id
    ";
    foreach ($wpdb->get_results($sqlAgg) as $r) {
      $byTeacher[(int)$r->teacher_id] = [
        'subjects' => $r->subj_list  ? array_values(array_filter(array_map('trim', explode('||',$r->subj_list)))) : [],
        'levels'   => $r->level_list ? array_values(array_filter(array_map('trim', explode('||',$r->level_list)))) : [],
      ];
    }

    // B) choose ONE "primary" mapping (ids + names) per teacher (lexicographically first)
    $sqlOne = "
      SELECT tas.teacher_id,
             sl.`{$sl_subject}` AS subject_id, s.`{$subj_label}` AS subject_name,
             sl.`{$sl_level}`   AS level_id,   cl.`{$level_label}` AS level_name
      FROM {$T_ALLOC} tas
      LEFT JOIN {$T_SL} sl  ON sl.`{$sl_pk}` = tas.subject_level_id
      LEFT JOIN {$T_SUBJ} s ON s.`{$subj_id}` = sl.`{$sl_subject}`
      LEFT JOIN {$T_LEVEL} cl ON cl.`{$level_id}` = sl.`{$sl_level}`
      WHERE tas.teacher_id IN ({$idList})
      ORDER BY tas.teacher_id, s.`{$subj_label}` ASC, cl.`{$level_label}` ASC
    ";
    foreach ($wpdb->get_results($sqlOne) as $row) {
      $tid = (int)$row->teacher_id;
      if (!isset($primaryMap[$tid])) {
        $primaryMap[$tid] = [
          'subject_id' => (int)$row->subject_id,
          'subject'    => (string)$row->subject_name,
          'level_id'   => (int)$row->level_id,
          'level'      => (string)$row->level_name,
        ];
      }
    }
  }

  /* ---------- 3) helpers ---------- */
  $esc = fn($s)=>esc_html($s ?? '');
  $shorten = function($text,$len=140){
    $text=trim((string)$text); if($text==='') return '';
    if(mb_strlen($text)<= $len) return $text;
    $cut = mb_substr($text,0,$len); $cut = preg_replace('/\s+\S*$/u','',$cut); return $cut.'…';
  };

  /* ---------- 4) UI ---------- */
  ob_start(); ?>
  <style>
    .tp-teachers-grid{display:grid;gap:clamp(16px,2.5vw,24px);grid-template-columns:repeat(auto-fit,minmax(260px,1fr));width:100%;margin-inline:auto;max-width:1200px}
    .tp-teacher{display:flex;flex-direction:column;align-items:center;text-align:center}
    .tp-teacher .avatar{width:112px;height:112px;border-radius:50%;overflow:hidden;border:4px solid #0e534a;box-shadow:0 6px 14px rgba(0,0,0,.18);background:#0f6b61;display:flex;align-items:center;justify-content:center;color:#c9fff6;font-weight:800;font-size:28px;margin-bottom:14px}
    .tp-teacher .avatar img{width:100%;height:100%;object-fit:cover;display:block}
    .tp-teacher .name{font-size:clamp(22px,2.8vw,36px);font-weight:500;margin:4px 0 4px;font-style:oblique;font-family:"Trebuchet MS", Helvetica, sans-serif;color:#333}
    .tp-teacher .role{font-size:clamp(15px,2.2vw,18px);font-weight:600;color:#324e63;margin:2px 0 10px}
    .tp-teacher .objective{font-size:14px;opacity:.95;margin:8px 0 10px;color:#2c3e50}
    .tp-teacher .levels{font-size:14px;margin:6px 0;opacity:.95}
    .tp-teacher .label{display:block;font-weight:700;margin-bottom:4px;opacity:.95}
    .tp-teacher .yt{display:inline-flex;align-items:center;justify-content:center;width:42px;height:42px;border-radius:999px;background:#ffffff20;border:1px solid #ffffff33;margin-top:8px;transition:transform .15s ease}
    .tp-teacher .yt:hover{transform:translateY(-2px)}

    /* green gradient button */
    .book-free-session-button-wrapper{text-align:center;margin-top:12px}
    .btn-grad{background-image:linear-gradient(to right,#1D976C 0%,#93F9B9 51%,#1D976C 100%);background-size:200% auto;color:#fff;display:inline-block;text-transform:uppercase;text-decoration:none;border-radius:10px;box-shadow:0 0 20px #eee;padding:10px 22px;font:700 16px/1.2 Roboto,sans-serif;transition:.5s}
    .btn-grad:hover{background-position:right center;transform:translateY(-1px)}

    .tp-teachers-empty{padding:12px;border:1px dashed #e5e7eb;border-radius:10px;color:#6b7280;text-align:center}
    .tp-teacher *{overflow-wrap:anywhere;word-break:break-word}
    @media (max-width:480px){.tp-teachers-grid{grid-template-columns:1fr}}
  </style>

  <div class="tp-teachers-grid">
    <?php foreach ($teachers as $t):
      $id    = (int)$t->teacher_id;
      $name  = trim($t->FullName);
      $obj   = $shorten($t->object_note ?: $t->experience_intro, 140);
      $photo = trim((string)$t->Photo);
      $intro = trim((string)$t->intro_video_url);

      $subs  = $byTeacher[$id]['subjects'] ?? [];
      $lvls  = $byTeacher[$id]['levels']   ?? [];

      $initials = '';
      if ($name) { $p = preg_split('/\s+/u',$name); $initials = strtoupper(mb_substr($p[0]??'',0,1).mb_substr($p[count($p)-1]??'',0,1)); }

      // role line: subjects + 'Tutor'
      $roleLine = 'Tutor';
      if (!empty($subs)) {
        $u = array_values(array_unique($subs));
        $d = array_slice($u, 0, 3);
        if (count($d) === 1)     $roleLine = $d[0].' Tutor';
        elseif (count($d) === 2) $roleLine = $d[0].' & '.$d[1].' Tutor';
        else                     $roleLine = implode(', ', $d).' Tutor';
      }

      // Primary mapping for the button
      $pm  = $primaryMap[$id] ?? null;
      $lvlName = $pm['level']      ?? '';
      $lvlId   = $pm['level_id']   ?? 0;
      $subName = $pm['subject']    ?? '';
      $subId   = $pm['subject_id'] ?? 0;
      ?>
      <article class="tp-teacher" data-teacher="<?php echo esc_attr($id); ?>">
        <div class="avatar">
          <?php if ($photo): ?>
            <img loading="lazy" src="<?php echo esc_url($photo); ?>" alt="<?php echo esc_attr($name ?: 'Teacher'); ?>">
          <?php else: ?>
            <?php echo esc_html($initials ?: 'TP'); ?>
          <?php endif; ?>
        </div>

        <div class="name"><?php echo $esc($name ?: 'Teacher'); ?></div>
        <div class="role"><?php echo $esc($roleLine); ?></div>

        <?php if ($obj): ?>
          <div class="objective"><?php echo $esc($obj); ?></div>
        <?php endif; ?>

        <?php if (!empty($lvls)): ?>
          <div class="levels">
            <span class="label">Levels</span>
            <?php echo $esc(implode(', ', $lvls)); ?>
          </div>
        <?php endif; ?>

        <?php if ($intro): ?>
          <a class="yt" href="<?php echo esc_url($intro); ?>" target="_blank" rel="noopener" aria-label="Watch intro video">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="#fff" aria-hidden="true"><path d="M23.5 6.2a4 4 0 0 0-2.8-2.8C18.8 3 12 3 12 3s-6.8 0-8.7.4A4 4 0 0 0 .5 6.2 41.4 41.4 0 0 0 0 12a41.4 41.4 0 0 0 .5 5.8 4 4 0 0 0 2.8 2.8C5.2 21 12 21 12 21s6.8 0 8.7-.4a4 4 0 0 0 2.8-2.8c.3-1.9.5-3.8.5-5.8s-.2-3.9-.5-5.8zM9.8 15.5v-7L16 12l-6.2 3.5z"/></svg>
          </a>
        <?php endif; ?>

        <div class="book-free-session-button-wrapper">
          <a href="#" class="btn-grad tp-book-btn"
             data-teacher="<?php echo esc_attr($id); ?>"
             data-level="<?php echo esc_attr($lvlName); ?>"
             data-level-id="<?php echo esc_attr($lvlId); ?>"
             data-subject="<?php echo esc_attr($subName); ?>"
             data-subject-id="<?php echo esc_attr($subId); ?>">
            <?php echo $esc($a['button_text']); ?>
          </a>
        </div>
      </article>
    <?php endforeach; ?>
  </div>

  <script>
  (function($){
    var TARGET = "<?php echo esc_js( site_url('/listofteachers') ); ?>";
    $(document).on('click', '.tp-book-btn', function(e){
      e.preventDefault();
      var $b   = $(this);
      var tid  = $b.data('teacher') || '';
      var lvl  = ($b.data('level')||'').toString().trim();
      var lvlI = parseInt($b.data('level-id')||0, 10) || '';
      var sub  = ($b.data('subject')||'').toString().trim();
      var subI = parseInt($b.data('subject-id')||0, 10) || '';

      if (!lvl || !sub) {
        alert('This teacher has no attached subject/level yet.');
        return;
      }
      var qs = new URLSearchParams();
      if (tid) qs.set('teacher_id', tid);
      qs.set('level', lvl);
      if (lvlI) qs.set('level_id', String(lvlI));
      qs.set('subject', sub);
      if (subI) qs.set('subject_id', String(subI));

      window.location.href = TARGET + '?' + qs.toString();
    });
  })(jQuery);
  </script>
  <?php
  return ob_get_clean();
});

