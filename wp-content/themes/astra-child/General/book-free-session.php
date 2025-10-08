
<?php
/**
 * Shortcodes for TutorsPoint
 * - [book_free_session_button]
 * - [list_teachers]
 */

/* ===============================
   Book Free Session Button
   =============================== */
add_shortcode('book_free_session_button', function () {
    ob_start(); ?>

    <div class="book-free-session-button-wrapper">
      <a href="#" id="tp_bookFreeSessionBtn" class="btn-grad">BOOK FREE SESSION</a>
    </div>

    <script>
    jQuery(function ($) {
      function findDropdowns() {
        // start with specific IDs/names you might use
        let $level = $('select#ls-level, select#tp_levelDropdown, select[name="level"], select[name="ls-level"]').filter(':visible').first();
        let $subject = $('select#ls-subject, select#tp_subjectDropdown, select[name="subject"], select[name="ls-subject"]').filter(':visible').first();

        // fuzzy fallback: any visible <select> whose id/name mentions level/subject
        if (!$level.length) {
          $level = $('select:visible').filter(function () {
            const id = (this.id||'').toLowerCase();
            const nm = (this.name||'').toLowerCase();
            return id.includes('level') || nm.includes('level');
          }).first();
        }
        if (!$subject.length) {
          $subject = $('select:visible').filter(function () {
            const id = (this.id||'').toLowerCase();
            const nm = (this.name||'').toLowerCase();
            return id.includes('subject') || nm.includes('subject');
          }).first();
        }
        return { $level, $subject };
      }

      function scrollIntoView(el) {
        if (!el) return;
        const header = document.querySelector('.ast-primary-header-bar, .site-header, header');
        const offset = header ? header.offsetHeight : 0;
        const y = el.getBoundingClientRect().top + window.pageYOffset - offset - 10;
        window.scrollTo({ top: y, behavior: 'smooth' });
      }

      $('#tp_bookFreeSessionBtn').on('click', function (e) {
        e.preventDefault();

        const { $level, $subject } = findDropdowns();

        if (!$level.length || !$subject.length) {
          let msg = 'No drop down is found.\n\n';
          msg += !$level.length  ? '- Level dropdown not found.\n'   : '';
          msg += !$subject.length? '- Subject dropdown not found.\n' : '';
          msg += '\nTip: if your builder lets you set an “HTML ID”, use ls-level and ls-subject.';
          alert(msg);
          // try to scroll to whichever exists so user sees where to fix
          scrollIntoView(($level[0] || $subject[0]));
          return;
        }

        const levelVal  = ($level.val() || '').trim();
        const levelText = ($level.find(':selected').text() || '').trim();
        const subjVal   = ($subject.val() || '').trim();
        const subjText  = ($subject.find(':selected').text() || '').trim();

        if (!levelVal || !subjVal) {
          scrollIntoView($level[0]);
          alert('Please select both Level and Subject before continuing.');
          return;
        }

        const params = new URLSearchParams();
        params.set('level', levelText);
        if (!isNaN(Number(levelVal))) params.set('level_id', levelVal);

        params.set('subject', subjText);
        if (!isNaN(Number(subjVal))) params.set('subject_id', subjVal);

        window.location.href = '<?php echo esc_url(site_url("/listofteachers")); ?>' + '?' + params.toString();
      });
    });
    </script>

    <style>
      .book-free-session-button-wrapper{ text-align:center; margin-top:10px; }
      .btn-grad{
        background-image:linear-gradient(to right,#1D976C 0%,#93F9B9 51%,#1D976C 100%);
        background-size:200% auto;
        color:#fff; display:inline-block; text-transform:uppercase; text-decoration:none;
        border-radius:10px; box-shadow:0 0 20px #eee; padding:8px 28px;
        font:700 30px/1 Roboto,sans-serif; transition:.5s;
      }
      .btn-grad:hover{ background-position:right center; color:#fff; }
    </style>

    <?php return ob_get_clean();
});

/* ===============================
   List Teachers
   Accepts: level_id + subject_id (preferred), or level + subject (names)
   =============================== */
add_shortcode('list_teachers', function () {
    ob_start();
    global $wpdb;

    // Prefer IDs from URL; fall back to names if needed
    $level_id   = isset($_GET['level_id'])   ? intval($_GET['level_id'])   : 0;
    $subject_id = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : 0;
    $level_name = isset($_GET['level'])      ? sanitize_text_field($_GET['level'])   : '';
    $subject    = isset($_GET['subject'])    ? sanitize_text_field($_GET['subject']) : '';

    if (!$subject_id && $subject !== '') {
        $subject_id = (int) $wpdb->get_var(
            $wpdb->prepare("SELECT subject_id FROM wpC_subjects WHERE SubjectName=%s", $subject)
        );
    }
    if (!$level_id && $level_name !== '') {
        $level_id = (int) $wpdb->get_var(
            $wpdb->prepare("SELECT id FROM wpC_class_levels WHERE level_name=%s", $level_name)
        );
    }

    if (!$level_id || !$subject_id) {
        echo '<p>Please select both level and subject.</p>';
        return ob_get_clean();
    }

    // subject_level_id
    $subject_level_id = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT subject_level_id FROM wpC_subjects_level WHERE level_id=%d AND subject_id=%d",
        $level_id, $subject_id
    ));
    if (!$subject_level_id) {
        echo '<p>No matching subject–level entry found.</p>';
        return ob_get_clean();
    }

    // latest university per teacher
    $latest_uni_sql = "
      SELECT q.teacher_id, q.university
      FROM wpC_teacher_qualifications q
      INNER JOIN (
        SELECT teacher_id, MAX(year_completed) AS max_year
        FROM wpC_teacher_qualifications
        GROUP BY teacher_id
      ) latest ON latest.teacher_id = q.teacher_id AND latest.max_year = q.year_completed
    ";

    // Fetch teachers mapped to that subject_level_id
    $teachers = $wpdb->get_results( $wpdb->prepare("
        SELECT t.teacher_id, t.FullName, u.university AS UniversityName, t.Photo
        FROM wpC_teachers_main t
        JOIN wpC_teacher_allocated_subjects tas ON tas.teacher_id = t.teacher_id
        LEFT JOIN ($latest_uni_sql) u ON u.teacher_id = t.teacher_id
        WHERE tas.subject_level_id = %d
        GROUP BY t.teacher_id
        ORDER BY t.FullName
        LIMIT 100
    ", $subject_level_id) );

    if (!$teachers) {
        echo '<p>No teachers found for <strong>' . esc_html($subject ?: 'selected subject')
           . '</strong> in <strong>' . esc_html($level_name ?: 'selected level') . '</strong>.</p>';
        return ob_get_clean();
    }

    echo '<div class="teacher-grid">';
    foreach ($teachers as $t) {
        $id   = (int) $t->teacher_id;
        $name = esc_html($t->FullName);
        $uni  = esc_html($t->UniversityName ?: '');
        $img  = esc_url($t->Photo ?: 'https://via.placeholder.com/120x120?text=Tutor');
        $profile = esc_url( site_url("/teacherprofile/?teacher_id=$id&subject_id=$subject_id&level_id=$level_id") );

        echo "<div class='teacher-card'>
                <a href='$profile'><img src='$img' alt='$name'></a>
                <h3><a href='$profile'>$name</a></h3>"
                . ($uni ? "<p>$uni</p>" : "") .
             "</div>";
    }
    echo '</div>';

    ?>
    <style>
      .teacher-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:20px;margin:24px 0}
      .teacher-card{background:#f9f9f9;border:1px solid #e2e2e2;border-radius:10px;padding:16px;text-align:center}
      .teacher-card img{width:120px;height:120px;border-radius:50%;object-fit:cover;margin-bottom:10px}
      .teacher-card h3{margin:8px 0 4px;font-size:18px}
      .teacher-card a{text-decoration:none;color:#0a58ca}
    </style>
    <?php

    return ob_get_clean();
});

