<?php
/**
 * Template Name: List of Teachers
 * Template for displaying teachers list
 *
 * @package Astra Child
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

global $wpdb;

// Get filters
$level_id   = isset($_GET['level_id'])   ? intval($_GET['level_id'])   : 0;
$subject_id = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : 0;
$level_name = isset($_GET['level'])      ? sanitize_text_field(wp_unslash($_GET['level']))   : '';
$subject    = isset($_GET['subject'])    ? sanitize_text_field(wp_unslash($_GET['subject'])) : '';

// Resolve names -> IDs
if (!$level_id && $level_name !== '') {
    $level_id = (int) $wpdb->get_var(
        $wpdb->prepare("SELECT id FROM wpC_class_levels WHERE level_name = %s", $level_name)
    );
}
if (!$subject_id && $subject !== '') {
    $subject_id = (int) $wpdb->get_var(
        $wpdb->prepare("SELECT subject_id FROM wpC_subjects WHERE SubjectName = %s", $subject)
    );
}

// Get all levels and subjects for filters
$all_levels = $wpdb->get_results("SELECT id, level_name FROM wpC_class_levels ORDER BY level_name");
$all_subjects = $wpdb->get_results("SELECT subject_id, SubjectName FROM wpC_subjects ORDER BY SubjectName");

// Get teachers based on filters
$teachers = [];
$show_results = false;

if ($level_id && $subject_id) {
    // Filtered view - show tutors with stats
    $show_results = true;
    $sql = "
        SELECT
            tm.teacher_id,
            tm.FullName,
            tm.Country,
            tm.Photo,
            tm.intro_video_url,
            COALESCE(SUM(CASE WHEN sls.is_taught = 1 THEN sls.duration END), 0) AS hours_taught,
            COALESCE((
                SELECT thr.hourly_rate
                FROM wpC_teacher_Hour_Rate thr
                WHERE thr.teacher_id = tm.teacher_id
                ORDER BY thr.from_date DESC, thr.hour_rate_id DESC
                LIMIT 1
            ), 0) AS hourly_rate
        FROM wpC_teachers_main tm
        INNER JOIN wpC_teacher_allocated_subjects tas ON tas.teacher_id = tm.teacher_id
        INNER JOIN wpC_subjects_level sl ON sl.subject_level_id = tas.subject_level_id
        LEFT JOIN wpC_student_lectures sls ON sls.teacher_id = tm.teacher_id
        WHERE sl.level_id = %d
            AND sl.subject_id = %d
            AND (tm.Status IS NULL OR tm.Status = 1)
        GROUP BY tm.teacher_id
        ORDER BY tm.created_at DESC, tm.FullName ASC
        LIMIT 100
    ";
    $teachers = $wpdb->get_results($wpdb->prepare($sql, $level_id, $subject_id));
} else {
    // No filters - show all active teachers
    $teachers = $wpdb->get_results("
        SELECT DISTINCT
            t.teacher_id,
            t.FullName,
            t.Country,
            t.Photo,
            t.intro_video_url
        FROM wpC_teachers_main t
        WHERE t.Status = 1
        ORDER BY t.FullName
        LIMIT 50
    ");
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Tutors - <?php bloginfo('name'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<div class="tutors-page-wrapper">
    <div class="bg-blur blur-1"></div>
    <div class="bg-blur blur-2"></div>

    <!-- Custom Header -->
    <?php include(get_stylesheet_directory() . '/oheader.php'); ?>

    <!-- Main Content -->
    <div class="tutors-main">
        <h1 class="page-title">Meet Our Tutors</h1>
        <p class="page-subtitle">Find the perfect tutor for your learning journey</p>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="get" action="<?php echo esc_url(home_url('/listofteachers/')); ?>">
                <div class="filter-grid">
                    <div class="filter-field">
                        <label>Select Grade/Level</label>
                        <select name="level_id" id="filter-level">
                            <option value="">All Levels</option>
                            <?php foreach ($all_levels as $level): ?>
                                <option value="<?php echo esc_attr($level->id); ?>" <?php selected($level_id, $level->id); ?>>
                                    <?php echo esc_html($level->level_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-field">
                        <label>Select Subject</label>
                        <select name="subject_id" id="filter-subject">
                            <option value="">All Subjects</option>
                            <?php foreach ($all_subjects as $subj): ?>
                                <option value="<?php echo esc_attr($subj->subject_id); ?>" <?php selected($subject_id, $subj->subject_id); ?>>
                                    <?php echo esc_html($subj->SubjectName); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-field" style="display: flex; align-items: flex-end;">
                        <button type="submit" class="btn-filter">Search Tutors</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Results Info -->
        <?php if ($show_results): ?>
            <div class="results-info">
                Showing <?php echo count($teachers); ?> tutor<?php echo count($teachers) !== 1 ? 's' : ''; ?>
                <?php if ($subject): ?>
                    for <strong><?php echo esc_html($subject); ?></strong>
                <?php endif; ?>
                <?php if ($level_name): ?>
                    at <strong><?php echo esc_html($level_name); ?></strong>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Tutors Grid -->
        <?php if (!empty($teachers)): ?>
            <div class="tutors-grid">
                <?php foreach ($teachers as $tutor):
                    $photo_url = !empty($tutor->Photo) ? esc_url($tutor->Photo) : 'https://via.placeholder.com/300x300/3dba9f/ffffff?text=' . urlencode(substr($tutor->FullName, 0, 1));

                    // Get subjects for filtered view
                    $tutor_subjects_list = '';
                    if ($show_results && $subject_id) {
                        $tutor_subjects_list = $subject;
                    } else {
                        $tutor_subjects = $wpdb->get_results($wpdb->prepare("
                            SELECT DISTINCT s.SubjectName
                            FROM wpC_teacher_allocated_subjects tas
                            JOIN wpC_subjects_level sl ON tas.subject_level_id = sl.subject_level_id
                            JOIN wpC_subjects s ON sl.subject_id = s.subject_id
                            WHERE tas.teacher_id = %d
                            LIMIT 3
                        ", $tutor->teacher_id), ARRAY_A);

                        $tutor_subjects_list = !empty($tutor_subjects)
                            ? implode(', ', array_column($tutor_subjects, 'SubjectName'))
                            : 'Multiple Subjects';
                    }
                ?>
                    <div class="tutor-card">
                        <div class="tutor-image" style="background-image: url('<?php echo $photo_url; ?>');">
                            <?php if (!empty($tutor->intro_video_url)): ?>
                                <div class="play-button" onclick="openVideoModal('<?php echo esc_js($tutor->intro_video_url); ?>', '<?php echo esc_js($tutor->FullName); ?>')">▶</div>
                            <?php endif; ?>
                        </div>
                        <div class="tutor-info">
                            <h3><?php echo esc_html($tutor->FullName); ?></h3>
                            <p class="tutor-meta"><?php echo esc_html($tutor->Country ?: 'International'); ?></p>

                            <?php if ($show_results && isset($tutor->hours_taught)): ?>
                                <div class="tutor-stats">
                                    <span class="stat-badge">Hours: <?php echo (int)$tutor->hours_taught; ?></span>
                                    <span class="stat-badge">£<?php echo (int)$tutor->hourly_rate; ?>/hr</span>
                                </div>
                            <?php else: ?>
                                <p class="tutor-meta" style="font-size: 12px; margin-bottom: 16px;"><?php echo esc_html($tutor_subjects_list); ?></p>
                            <?php endif; ?>

                            <div class="tutor-actions">
                                <a href="<?php echo esc_url(add_query_arg(['teacher_id' => $tutor->teacher_id], home_url('/teacherprofile-public/'))); ?>" class="btn-view-profile">View Profile</a>
                                <a href="<?php echo esc_url(add_query_arg(['teacher_id' => $tutor->teacher_id, 'subject_id' => $subject_id, 'level_id' => $level_id], home_url('/booklecture/'))); ?>" class="btn-book">Book</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-results">
                <h3>No Tutors Found</h3>
                <p>Try adjusting your filters or browse all available tutors.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Video Modal -->
    <div id="video-modal" class="video-modal" onclick="closeVideoModal()">
        <div class="video-modal-content" onclick="event.stopPropagation()">
            <span class="video-close" onclick="closeVideoModal()">&times;</span>
            <h3 id="video-modal-title"></h3>
            <video id="video-player" controls style="width: 100%; max-height: 70vh; border-radius: 12px;">
                <source id="video-source" src="" type="video/mp4">
                Your browser does not support the video tag.
            </video>
        </div>
    </div>

    <!-- Custom Footer -->
            <?php include(get_stylesheet_directory() . "/ofooter.php"); ?>

</div>

<!-- Login/Signup Modal -->
<?php echo do_shortcode('[tp_auth_portal]'); ?>

<script>


// Video Modal Functions
function openVideoModal(videoUrl, tutorName) {
    const modal = document.getElementById('video-modal');
    const videoSource = document.getElementById('video-source');
    const videoPlayer = document.getElementById('video-player');
    const modalTitle = document.getElementById('video-modal-title');

    modalTitle.textContent = tutorName + ' - Introduction Video';
    videoSource.src = videoUrl;
    videoPlayer.load();
    modal.classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeVideoModal() {
    const modal = document.getElementById('video-modal');
    const videoPlayer = document.getElementById('video-player');

    modal.classList.remove('open');
    videoPlayer.pause();
    videoPlayer.currentTime = 0;
    document.body.style.overflow = 'auto';
}
</script>

<?php wp_footer(); ?>
</body>
</html>
