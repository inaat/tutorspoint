<?php
/**
 * Shortcode: [teacher_profile_public]
 * URL example:
 *   /teacherprofile-public/?teacher_id=1&subject_id=3&level_id=2
 */

add_shortcode('teacher_profile_public', function () {
    global $wpdb;

    /* ---------------------------
     * Helpers
     * ------------------------- */
    $http_ok = function(string $url): bool {
        $resp = wp_remote_head($url, ['timeout' => 5]);
        if (is_wp_error($resp)) return false;
        $code = (int) wp_remote_retrieve_response_code($resp);
        return ($code >= 200 && $code < 400);
    };

    $normalize_media_url = function(?string $raw) use ($http_ok): string {
        $raw = trim((string)$raw);
        $fallback = 'http://public_html.test/wp-content/uploads/2025/08/teacher_intro.mp4';
        if ($raw === '') return $fallback;

        if (preg_match('~^https?://~i', $raw)) {
            return $http_ok($raw) ? $raw : $fallback;
        }

        $uploads = wp_get_upload_dir();
        $baseurl = rtrim($uploads['baseurl'] ?? site_url('/wp-content/uploads'), '/');

        if (strpos($raw, '/wp-content/uploads') === 0) {
            $url = site_url($raw);
            return $http_ok($url) ? $url : $fallback;
        }

        if (preg_match('~^\d{4}/\d{2}/~', $raw)) {
            $url = $baseurl . '/' . ltrim($raw, '/');
            return $http_ok($url) ? $url : $fallback;
        }

        return $fallback;
    };

    $detectPrefix = function() use ($wpdb) {
        $c1 = $wpdb->get_var("SHOW TABLES LIKE 'wpC_subjects_level'");
        if ($c1 === 'wpC_subjects_level') return 'wpC_';
        $c2 = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}subjects_level'");
        if ($c2 === $wpdb->prefix . 'subjects_level') return $wpdb->prefix;
        return 'wpC_';
    };
    $p = $detectPrefix();

    $dbName = $wpdb->dbname;
    $colExists = function($table, $col) use ($wpdb, $dbName) {
        return (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA=%s AND TABLE_NAME=%s AND COLUMN_NAME=%s",
            $dbName, $table, $col
        )) > 0;
    };

    /* ---------------------------
     * Inputs
     * ------------------------- */
    $teacher_id = isset($_GET['teacher_id']) ? (int) $_GET['teacher_id'] : 0;
    $subject_id = isset($_GET['subject_id']) ? (int) $_GET['subject_id'] : 0;
    $level_id   = isset($_GET['level_id'])   ? (int) $_GET['level_id']   : 0;

    if (!$teacher_id || !$subject_id || !$level_id) {
        return '<p>Missing teacher_id, subject_id or level_id.</p>';
    }

    /* ---------------------------
     * Tables & Schema
     * ------------------------- */
    $slTable = $p . 'subjects_level';
    $tmTable = $p . 'teachers_main';
    $tasTable= $p . 'teacher_allocated_subjects';
    $sTable  = $p . 'subjects';
    $clTable = $p . 'class_levels';

    $has_level_id    = $colExists($slTable, 'level_id');
    $has_subject_id  = $colExists($slTable, 'subject_id');
    $has_pk_norm     = $colExists($slTable, 'subject_level_id');
    $has_level_txt   = $colExists($slTable, 'level');
    $has_subject_Id  = $colExists($slTable, 'subject_Id');

    /* ---------------------------
     * Validate teacher exists
     * ------------------------- */
    $sql_teacher = $wpdb->prepare("SELECT * FROM {$tmTable} WHERE teacher_id = %d", $teacher_id);
    $teacher_row = $wpdb->get_row($sql_teacher);
    if (!$teacher_row) {
        return '<p>Teacher not found.</p>';
    }

    /* ---------------------------
     * 1) Get subject_level_id
     * ------------------------- */
    $sl_id = 0;
    if ($has_level_id && $has_subject_id) {
        $pk = $has_pk_norm ? "subject_level_id" : "id";
        $sql1 = $wpdb->prepare("
            SELECT {$pk}
            FROM {$slTable}
            WHERE level_id=%d AND subject_id=%d
            LIMIT 1
        ", $level_id, $subject_id);
        $sl_id = (int)$wpdb->get_var($sql1);
    } elseif ($has_level_txt && $has_subject_Id) {
        $level_name = $wpdb->get_var($wpdb->prepare("
            SELECT level_name FROM {$clTable} WHERE id=%d LIMIT 1
        ", $level_id));
        if ($level_name) {
            $pk = $has_pk_norm ? "subject_level_id" : "id";
            $sql1 = $wpdb->prepare("
                SELECT {$pk}
                FROM {$slTable}
                WHERE level=%s AND subject_Id=%d
                LIMIT 1
            ", $level_name, $subject_id);
            $sl_id = (int)$wpdb->get_var($sql1);
        }
    }

    if (!$sl_id) {
        return '<p>No matching subject–level combination found.</p>';
    }

    /* ---------------------------
     * 2) Confirm allocation
     * ------------------------- */
    $tas_sql = $wpdb->prepare("
        SELECT teacher_allocated_subject_id
        FROM {$tasTable}
        WHERE teacher_id=%d AND subject_level_id=%d
        LIMIT 1
    ", $teacher_id, $sl_id);
    $tas_id = (int)$wpdb->get_var($tas_sql);

    if (!$tas_id) {
        return '<p>This teacher is not allocated to the selected subject & level.</p>';
    }

    /* ---------------------------
     * 3) Display row (joined)
     * ------------------------- */
    $pk = $has_pk_norm ? "subject_level_id" : "id";
    $sl_subject_col = $has_subject_id ? "sl.subject_id" : "sl.subject_Id";
    $sl_level_join  = $has_level_id ? "sl.level_id"   : "cl.id";

    $display_sql = $wpdb->prepare("
        SELECT
          t.teacher_id, t.FullName, t.Country, t.Photo, t.intro_video_url,
          s.SubjectName,
          cl.level_name
        FROM {$tmTable} t
        JOIN {$tasTable} tas
          ON tas.teacher_id = t.teacher_id
        JOIN {$slTable} sl
          ON sl.{$pk} = tas.subject_level_id
        JOIN {$sTable} s
          ON s.subject_id = {$sl_subject_col}
        JOIN {$clTable} cl
          ON cl.id = {$sl_level_join}
        WHERE tas.teacher_id=%d AND tas.subject_level_id=%d
        LIMIT 1
    ", $teacher_id, $sl_id);
    $row = $wpdb->get_row($display_sql);

    if (!$row) {
        return '<p>Matched allocation found, but no teacher profile row returned.</p>';
    }

    /* ---------------------------
     * View model
     * ------------------------- */
    $name    = esc_html($row->FullName ?: 'Tutor');
    $country = esc_html($row->Country ?: '—');
    $photo   = $row->Photo ? esc_url($row->Photo) : 'https://placehold.co/160x160?text=Tutor';
    $subject = esc_html($row->SubjectName ?: '—');
    $level   = esc_html($row->level_name ?: '—');

    $video_url = $normalize_media_url($row->intro_video_url ?? '');

    $is_iframe = false;
    $iframe_src = '';
    if (preg_match('~(youtube\.com|youtu\.be)~i', $video_url)) {
        $is_iframe = true;
        if (strpos($video_url, 'watch?v=') !== false) {
            $video_url = preg_replace('~watch\?v=([^&]+).*~', 'https://www.youtube.com/embed/$1', $video_url);
        } elseif (preg_match('~youtu\.be/([^?&/]+)~', $video_url, $m)) {
            $video_url = 'https://www.youtube.com/embed/'.$m[1];
        }
        $iframe_src = $video_url . '?rel=0';
    }

    $book_url = add_query_arg(
        array(
            'teacher_id' => (int) $row->teacher_id,
            'subject_id' => (int) $subject_id,
            'level_id'   => (int) $level_id,
        ),
        site_url('/booklecture/')
    );

    /* ---------------------------
     * Render
     * ------------------------- */
    ob_start(); ?>
    <div class="tpp-wrap">
      <header class="tpp-hero">
        <div class="tpp-hero-inner">
          <img class="tpp-avatar" src="<?php echo $photo; ?>" alt="<?php echo $name; ?>" />
          <div class="tpp-hero-txt">
            <h1 class="tpp-name"><?php echo $name; ?></h1>
            <div class="tpp-meta">
              <span class="tpp-pill"><?php echo $country; ?></span>
              <span class="tpp-pill tpp-level"><?php echo $level; ?></span>
              <span class="tpp-pill tpp-subj"><?php echo $subject; ?></span>
            </div>
          </div>
          <a class="tpp-cta" href="<?php echo esc_url($book_url); ?>">
            Book a free session
          </a>
        </div>
      </header>

      <section class="tpp-body">
        <div class="tpp-left card">
          <h3 class="tpp-section-title">About this tutor</h3>
          <p class="tpp-copy">
            Experienced tutor for <strong><?php echo $subject; ?></strong> at <strong><?php echo $level; ?></strong>.
            Lessons are tailored to your goals with clear feedback and practice strategies.
          </p>

          <div class="tpp-facts">
            <div class="tpp-fact"><span>Country</span><strong><?php echo $country; ?></strong></div>
            <div class="tpp-fact"><span>Subject</span><strong><?php echo $subject; ?></strong></div>
            <div class="tpp-fact"><span>Level</span><strong><?php echo $level; ?></strong></div>
          </div>

          <div class="tpp-actions">
            <a class="tpp-cta tpp-cta--ghost" href="<?php echo esc_url($book_url); ?>">Book now</a>
          </div>
        </div>

        <div class="tpp-right card">
          <h3 class="tpp-section-title">Intro video</h3>

          <?php if ($is_iframe): ?>
            <div class="tpp-video">
              <iframe class="tpp-iframe" src="<?php echo esc_url($iframe_src); ?>" allow="autoplay; encrypted-media" allowfullscreen></iframe>
            </div>
          <?php else: ?>
            <video class="tpp-player" src="<?php echo esc_url($video_url); ?>" controls playsinline preload="metadata"></video>
          <?php endif; ?>
        </div>
      </section>
    </div>

    <style>
      :root{
        --tpp-pill:#f1f5f9;
        --tpp-accent:#0ea5e9;
        --tpp-cta-start:#DCD9FF;
        --tpp-cta-end:#89F297;
      }
      .tpp-wrap{padding:24px 16px}
      .tpp-hero{
        background:linear-gradient(135deg, rgba(13,148,136,.08), rgba(14,165,233,.08));
        border-radius:18px; margin:0 auto 18px; max-width:1100px;
        border:1px solid #eaeef4;
      }
      .tpp-hero-inner{display:flex; align-items:center; gap:16px; padding:20px; flex-wrap:wrap;}
      .tpp-avatar{width:72px; height:72px; border-radius:50%; object-fit:cover; border:3px solid #fff; box-shadow:0 6px 16px rgba(2,8,23,.12);}
      .tpp-hero-txt{flex:1 1 320px}
      .tpp-name{margin:0 0 6px; font-size:28px; line-height:1.1; color:#0f172a}
      .tpp-meta{display:flex; gap:8px; flex-wrap:wrap}
      .tpp-pill{display:inline-flex; align-items:center; gap:6px; background:var(--tpp-pill); color:#0f172a; padding:6px 10px; border-radius:999px; font-size:14px}
      .tpp-level{background:#e6fffa}
      .tpp-subj{background:#e6f2ff}
      .tpp-cta{
        margin-left:auto; padding:12px 18px; border-radius:10px; color:#fff; text-decoration:none; font-weight:700;
        background:linear-gradient(90deg,var(--tpp-cta-start),var(--tpp-cta-end));
        box-shadow:0 8px 24px rgba(2,8,23,.12);
      }
      .tpp-cta:hover{filter:brightness(.97)}
      .tpp-cta--ghost{background:transparent; border:2px solid var(--tpp-accent); color:var(--tpp-accent)}
      .tpp-body{max-width:1100px; margin:0 auto; display:grid; gap:18px; grid-template-columns: 1fr;}
      @media (min-width: 900px){.tpp-body{grid-template-columns: 1.1fr .9fr;}}
      .card{background:#fff; border:1px solid #eaeef4; border-radius:16px; padding:18px; box-shadow:0 10px 28px rgba(2,8,23,.05)}
      .tpp-section-title{margin:0 0 10px; font-size:18px; color:#0f172a}
      .tpp-copy{color:#334155; line-height:1.6; margin:0 0 14px}
      .tpp-facts{display:grid; gap:10px; grid-template-columns: repeat(3, minmax(0,1fr));}
      .tpp-fact{background:#f8fafc; border:1px solid #eaeef4; border-radius:12px; padding:10px 12px}
      .tpp-fact span{display:block; font-size:12px; color:#64748b}
      .tpp-fact strong{font-size:14px; color:#0f172a}
      .tpp-actions{margin-top:14px; display:flex; gap:10px}
      .tpp-video{position:relative; aspect-ratio:16/9; background:#0b0b0b; border-radius:14px; overflow:hidden; border:1px solid #111}
      .tpp-iframe{position:absolute; inset:0; width:100%; height:100%; border:0}
      .tpp-player{width:100%; max-height:66vh; border-radius:14px; display:block; background:#000}
    </style>
    <?php
    return ob_get_clean();
});
