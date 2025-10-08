<?php
/**
 * List teachers for a given Level + Subject.
 * URL params supported:
 *   /listofteachers/?level_id=2&subject_id=3<?php
/* Shortcode: [ListOfTeachers]
 * Accepts either:
 *   /listofteachers/?level_id=2&subject_id=3
 *   /listofteachers/?level=IGCSE&subject=Chemistry
 */
add_shortcode('ListOfTeachers', function () {
    global $wpdb;

    // Inputs (both id & name supported)
    $level_id   = isset($_GET['level_id'])   ? (int) $_GET['level_id']   : 0;
    $subject_id = isset($_GET['subject_id']) ? (int) $_GET['subject_id'] : 0;
    $level_name = isset($_GET['level'])      ? sanitize_text_field($_GET['level'])   : '';
    $subject    = isset($_GET['subject'])    ? sanitize_text_field($_GET['subject']) : '';

    // Resolve IDs if only names were provided
    if (!$subject_id && $subject) {
        $subject_id = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT subject_id FROM {$wpdb->prefix}subjects WHERE SubjectName=%s", $subject
        ));
    }
    if (!$level_id && $level_name) {
        $level_id = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}class_levels WHERE level_name=%s", $level_name
        ));
    }

    // If still missing, show helper
    if (!$level_id || !$subject_id) {
        return '<p>Please select both level and subject.</p>';
    }

    // Find teachers for this subject_level_id
    $sl_id = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT subject_level_id
           FROM {$wpdb->prefix}subjects_level
          WHERE level_id=%d AND subject_id=%d
          LIMIT 1", $level_id, $subject_id
    ));
    if (!$sl_id) {
        return '<p>No matching subject–level combination found.</p>';
    }

    // Teacher IDs allocated to this combo
    $teacher_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT teacher_id
           FROM {$wpdb->prefix}teacher_allocated_subjects
          WHERE subject_level_id=%d", $sl_id
    ));
    if (empty($teacher_ids)) {
        return '<p>No teachers found for this selection.</p>';
    }

    // Get teacher rows
    $in = implode(',', array_map('intval', $teacher_ids));
    $rows = $wpdb->get_results("
        SELECT t.teacher_id, t.FullName, t.Photo, t.Country
          FROM {$wpdb->prefix}teachers_main t
         WHERE t.teacher_id IN ($in)
         ORDER BY t.FullName
         LIMIT 200
    ");

    // Inline CSS & grid
    wp_register_style('tp-lot', false);
    wp_enqueue_style('tp-lot');
    wp_add_inline_style('tp-lot', "
      .tp-grid{display:grid;gap:18px;grid-template-columns:repeat(1,minmax(0,1fr))}
      @media(min-width:600px){.tp-grid{grid-template-columns:repeat(2,1fr)}}
      @media(min-width:992px){.tp-grid{grid-template-columns:repeat(3,1fr)}}
      @media(min-width:1280px){.tp-grid{grid-template-columns:repeat(4,1fr)}}
      .tp-card{background:#fff;border:1px solid #eaeef4;border-radius:16px;overflow:hidden;box-shadow:0 10px 28px rgba(2,8,23,.05)}
      .tp-card .top{display:flex;gap:14px;padding:16px;align-items:center}
      .tp-card img{width:64px;height:64px;border-radius:50%;object-fit:cover}
      .tp-card h3{margin:0 0 4px;font-size:18px}
      .tp-card .meta{color:#64748b;font-size:13px}
      .tp-card .actions{display:flex;gap:10px;padding:0 16px 16px}
      .tp-btn{all:unset;display:inline-block;cursor:pointer;background:#0ea5e9;color:#fff;padding:9px 14px;border-radius:10px;font-weight:600}
      .tp-btn:hover{filter:brightness(.95)}
    ");

    ob_start();
    echo '<div class="tp-grid">';
    foreach ($rows as $r) {
        $avatar = $r->Photo ? esc_url($r->Photo) : 'https://placehold.co/120x120?text=Tutor';
        $name   = esc_html($r->FullName ?: 'Tutor');

        // Build a BOOK url with ALL three IDs
        $book_url = add_query_arg(array(
            'teacher_id' => (int) $r->teacher_id,
            'subject_id' => (int) $subject_id,
            'level_id'   => (int) $level_id,
        ), site_url('/booklecture/'));

        // Profile url (optional – carry the same ids if that page uses them)
        $profile_url = add_query_arg(array(
            'teacher_id' => (int) $r->teacher_id,
            'subject_id' => (int) $subject_id,
            'level_id'   => (int) $level_id,
        ), site_url('/teacherprofile-public/'));

        echo '<article class="tp-card">';
          echo '<div class="top">';
            echo '<img src="'.$avatar.'" alt="'.$name.'"/>';
            echo '<div>';
              echo '<h3><a href="'.esc_url($profile_url).'">'.$name.'</a></h3>';
              echo '<div class="meta">'.esc_html($r->Country ?: '—').'</div>';
            echo '</div>';
          echo '</div>';
          echo '<div class="actions">';
            echo '<a class="tp-btn" href="'.esc_url($profile_url).'">View Profile</a>';
            echo '<a class="tp-btn" href="'.esc_url($book_url).'">Book</a>';
          echo '</div>';
        echo '</article>';
    }
    echo '</div>';
    return ob_get_clean();
});

 *   /listofteachers/?level=IGCSE&subject=Chemistry
 *
 * Shortcodes: [list_teachers] and [ListOfTeachers]
 */

add_shortcode('list_teachers', 'tp_list_teachers_shortcode');
add_shortcode('ListOfTeachers', 'tp_list_teachers_shortcode'); // alias

function tp_list_teachers_shortcode() {
    global $wpdb;
    $pfx = $wpdb->prefix; // e.g. "wpC_"

    // 1) Read filters
    $level_id   = isset($_GET['level_id'])   ? intval($_GET['level_id'])   : 0;
    $subject_id = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : 0;
    $level_name = isset($_GET['level'])      ? sanitize_text_field(wp_unslash($_GET['level']))   : '';
    $subject    = isset($_GET['subject'])    ? sanitize_text_field(wp_unslash($_GET['subject'])) : '';

    // 2) Resolve names -> IDs (only if IDs not provided)
    if (!$level_id && $level_name !== '') {
        $level_id = (int) $wpdb->get_var(
            $wpdb->prepare("SELECT id FROM {$pfx}class_levels WHERE level_name = %s", $level_name)
        );
    }
    if (!$subject_id && $subject !== '') {
        $subject_id = (int) $wpdb->get_var(
            $wpdb->prepare("SELECT subject_id FROM {$pfx}subjects WHERE SubjectName = %s", $subject)
        );
    }
    // 3) Guard rails
    if (!$level_id || !$subject_id) {
        ob_start();
        echo '<p>Please select both a Level and a Subject.</p>';
        return ob_get_clean();
    }

    // 4) Core query — teachers allocated to this (level_id, subject_id)
    // Tables used (as in your DB):
    //   wpC_subjects_level(subject_level_id, level_id, subject_id)
    //   wpC_teacher_allocated_subjects(id, teacher_id, subject_level_id)
    //   wpC_teachers_main(teacher_id, FullName, Country, Photo, intro_video_url, Status, created_at)
    //
    // Extras (optional but present in your DB):
    //   wpC_student_lectures (sum taught hours)
    //   wpC_teacher_Hour_Rate (latest hourly rate)
    //
    // NOTE: column names are case-insensitive in MySQL, but we match your actual names.

    $sql = "
      SELECT
        tm.teacher_id,
        tm.FullName,
        tm.Country,
        tm.Photo,
        tm.intro_video_url,

        /* hours taught (sum of duration where is_taught = 1) */
        COALESCE(SUM(CASE WHEN sls.is_taught = 1 THEN sls.duration END), 0) AS hours_taught,

        /* latest hourly rate */
        COALESCE((
          SELECT thr.hourly_rate
          FROM {$pfx}teacher_Hour_Rate thr
          WHERE thr.teacher_id = tm.teacher_id
          ORDER BY thr.from_date DESC, thr.hour_rate_id DESC
          LIMIT 1
        ), 0) AS hourly_rate

      FROM {$pfx}teachers_main tm
      INNER JOIN {$pfx}teacher_allocated_subjects tas
        ON tas.teacher_id = tm.teacher_id
      INNER JOIN {$pfx}subjects_level sl
        ON sl.subject_level_id = tas.subject_level_id
      LEFT JOIN  {$pfx}student_lectures sls
        ON sls.teacher_id = tm.teacher_id

      WHERE sl.level_id = %d
        AND sl.subject_id = %d
        AND (tm.Status IS NULL OR tm.Status = 1)

      GROUP BY tm.teacher_id
      ORDER BY tm.created_at DESC, tm.FullName ASC
      LIMIT 100
    ";

    $rows = $wpdb->get_results($wpdb->prepare($sql, $level_id, $subject_id));

    // 5) Early exit if none
    if (empty($rows)) {
        ob_start();
        printf(
            '<p>No teachers found for <strong>%s</strong> at <strong>%s</strong>.</p>',
            $subject ? esc_html($subject) : 'selected subject',
            $level_name ? esc_html($level_name) : 'selected level'
        );
        return ob_get_clean();
    }

    // 6) Styles + JS (only once per page)
    static $assets_done = false;
    if (!$assets_done) {
        $assets_done = true;

        wp_register_style('tp-list-teachers', false);
        wp_enqueue_style('tp-list-teachers');
        wp_add_inline_style('tp-list-teachers', <<<CSS
/* Grid */
.tp-grid{display:grid;gap:18px;grid-template-columns:repeat(1,minmax(0,1fr));margin:20px 0}
@media (min-width:640px){.tp-grid{grid-template-columns:repeat(2,1fr)}}
@media (min-width:1024px){.tp-grid{grid-template-columns:repeat(3,1fr)}}
@media (min-width:1280px){.tp-grid{grid-template-columns:repeat(4,1fr)}}

/* Card */
.tp-card{background:#fff;border-radius:16px;box-shadow:0 10px 28px rgba(0,0,0,.08);overflow:hidden;transition:transform .2s, box-shadow .2s}
.tp-card:hover{transform:translateY(-4px);box-shadow:0 16px 40px rgba(0,0,0,.12)}
.tp-media{position:relative;aspect-ratio:16/9;background:linear-gradient(135deg,#d6e7ff,#f4f7ff);border-bottom:1px solid #e2e8f0;overflow:hidden}
.tp-thumb{width:100%;height:100%;object-fit:cover;display:block}
.tp-play{position:absolute;inset:0;display:grid;place-items:center;text-decoration:none}
.tp-play svg{filter:drop-shadow(0 6px 16px rgba(0,0,0,.25))}
.tp-body{padding:14px 16px 16px}
.tp-row{display:flex;gap:12px;align-items:center}
.tp-avatar{width:56px;height:56px;border-radius:50%;object-fit:cover;border:2px solid #f1f5f9;background:#fff}
.tp-name{font-weight:700;font-size:16px;line-height:1.1;margin:0}
.tp-sub{font-size:12px;color:#64748b;margin-top:2px}
.tp-chips{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px}
.tp-chip{font-size:12px;background:#f1f5f9;color:#0f172a;padding:6px 10px;border-radius:999px}
.tp-price{font-weight:700}
.tp-actions{display:flex;gap:10px;margin-top:12px}
.tp-btn{all:unset;cursor:pointer;background:#0ea5e9;color:#fff;padding:9px 14px;border-radius:10px;font-weight:600}
.tp-btn:hover{filter:brightness(.95)}

/* Modal */
.tp-modal{position:fixed;inset:0;display:none;background:rgba(0,0,0,.6);z-index:9999}
.tp-modal.open{display:grid;place-items:center}
.tp-modal .box{width:min(960px,92vw);background:#000;border-radius:14px;overflow:hidden}
.tp-modal iframe,.tp-modal video{width:100%;height:min(56vw,560px);display:block}
CSS);

        wp_register_script('tp-list-teachers', false, [], null, true);
        wp_enqueue_script('tp-list-teachers');
        wp_add_inline_script('tp-list-teachers', <<<JS
(function(){
  const modal = document.querySelector('#tp-video-modal');
  const box   = modal ? modal.querySelector('.box') : null;

  function ytEmbed(url){
    try{
      const u = new URL(url);
      if (u.hostname.includes('youtu.be')) return 'https://www.youtube.com/embed/'+u.pathname.slice(1)+'?autoplay=1';
      if (u.hostname.includes('youtube.com')) {
        const v = u.searchParams.get('v');
        if (v) return 'https://www.youtube.com/embed/'+v+'?autoplay=1';
      }
    }catch(e){}
    return null;
  }

  document.addEventListener('click', function(e){
    const trigger = e.target.closest('[data-play-src]');
    if (!trigger) return;
    e.preventDefault();
    if (!modal || !box) return;

    const src = trigger.getAttribute('data-play-src') || '';
    const yt  = ytEmbed(src);
    box.innerHTML = yt
      ? '<iframe src=\"'+yt+'\" frameborder=\"0\" allow=\"autoplay; encrypted-media\" allowfullscreen></iframe>'
      : '<video src=\"'+src+'\" controls autoplay></video>';

    modal.classList.add('open');
  });

  if (modal){
    modal.addEventListener('click', function(e){
      if (e.target === modal){
        modal.classList.remove('open');
        box.innerHTML = '';
      }
    });
  }
})();
JS);
    }

    // 7) Render
    ob_start();

    // heading / context
    echo '<div style="margin:10px 0 6px;font-size:14px;color:#475569">';
    echo 'Showing teachers for <strong>'.esc_html($subject ?: 'Selected Subject').'</strong> at <strong>'.esc_html($level_name ?: 'Selected Level').'</strong>';
    echo '</div>';

		echo '<div class="tp-grid">';
		foreach ($rows as $r) {
			$name   = $r->FullName ? esc_html($r->FullName) : 'Teacher';
			$photo  = $r->Photo ? esc_url($r->Photo) : 'https://placehold.co/800x450?text=Tutor';
			$avatar = $r->Photo ? esc_url($r->Photo) : 'https://placehold.co/120x120?text=Tutor';
			$country= $r->Country ? esc_html($r->Country) : '—';
			$hours  = (int) $r->hours_taught;
			$rate   = (int) $r->hourly_rate;
			$video  = trim((string) $r->intro_video_url);

        // Try to build a nice thumbnail for YouTube links
        $thumb = $photo;
        if ($video && preg_match('~(?:youtu\.be/|v=)([^&?/]+)~i', $video, $m)) {
            $ytid  = sanitize_text_field($m[1]);
            $thumb = 'https://i.ytimg.com/vi/'.$ytid.'/hqdefault.jpg';
        }

        echo '<article class="tp-card">';

          // Media
          echo '<div class="tp-media">';
            echo '<img class="tp-thumb" src="'.$thumb.'" alt="'.$name.'">';
            if ($video) {
                echo '<a href="#" class="tp-play" data-play-src="'.esc_url($video).'">
                        <svg width="70" height="70" viewBox="0 0 24 24" fill="none">
                          <circle cx="12" cy="12" r="12" fill="white" opacity="0.92"/>
                          <path d="M10 8l6 4-6 4V8z" fill="#0ea5e9"/>
                        </svg>
                      </a>';
            }
          echo '</div>';

          // Body
          echo '<div class="tp-body">';
            echo '<div class="tp-row">';
              echo '<img class="tp-avatar" src="'.$avatar.'" alt="'.$name.'"/>';
              echo '<div>';
                echo '<h3 class="tp-name">'.$name.'</h3>';
                echo '<div class="tp-sub">Country: '.$country.'</div>';
              echo '</div>';
            echo '</div>';

            echo '<div class="tp-chips">';
              echo '<span class="tp-chip">Hours taught: '.$hours.'</span>';
              echo '<span class="tp-chip">Rate <span class="tp-price">£'.$rate.'/hr</span></span>';
            echo '</div>';

            echo '<div class="tp-actions">';
              echo '<a class="tp-btn" href="'.esc_url( add_query_arg(['teacher_id'=>$r->teacher_id], site_url('/teacherprofile')) ).'">View Profile</a>';
              echo '<a class="tp-btn" href="'.esc_url( add_query_arg(['teacher_id'=>$r->teacher_id], site_url('/book')) ).'">Book</a>';
            echo '</div>';

          echo '</div>';

        echo '</article>';
    }
    echo '</div>';

    // Video modal
    echo '<div id="tp-video-modal" class="tp-modal"><div class="box"></div></div>';

    return ob_get_clean();
}
