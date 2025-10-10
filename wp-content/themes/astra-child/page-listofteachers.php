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
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<style>
    /* Hide WordPress default header/footer */
    .site-header,
    .site-footer,
    #masthead,
    #colophon {
        display: none !important;
    }

    body {
        margin: 0 !important;
        padding: 0 !important;
    }

    .tutors-page-wrapper {
        font-family: 'League Spartan', sans-serif;
        background: linear-gradient(180deg, #e5f4ef 0%, #d8f0e8 50%, #cceee2 100%);
        min-height: 100vh;
        position: relative;
        overflow-x: hidden;
    }

    /* Background blurred circles */
    .bg-blur {
        position: fixed;
        border-radius: 50%;
        filter: blur(120px);
        opacity: 0.4;
        pointer-events: none;
        z-index: 0;
    }

    .blur-1 {
        width: 450px;
        height: 450px;
        background: #3dba9f;
        top: -200px;
        left: -200px;
    }

    .blur-2 {
        width: 400px;
        height: 400px;
        background: #5cd4b6;
        bottom: -150px;
        right: -150px;
    }

    /* Custom Header */
    .custom-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 18px 70px;
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(10px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        border-radius: 20px;
        margin: 10px 20px;
        position: relative;
        z-index: 10;
    }

    .logo {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .logo-icon {
        width: 38px;
        height: 38px;
        background: linear-gradient(135deg, #3dba9f, #5cd4b6);
        border-radius: 50%;
    }

    .logo-text {
        font-size: 14px;
        font-weight: 700;
        letter-spacing: 0.5px;
        color: #000;
        text-decoration: none;
    }

    .custom-header nav ul {
        display: flex;
        gap: 38px;
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .custom-header nav a {
        text-decoration: none;
        color: #000;
        font-size: 13px;
        font-weight: 500;
        transition: color 0.3s ease;
    }

    .custom-header nav a:hover {
        color: #3dba9f;
    }

    .user-icon {
        width: 33px;
        height: 33px;
        border: 2px solid #3dba9f;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 16px;
        transition: all 0.3s ease;
        text-decoration: none;
    }

    .user-icon:hover {
        background: #3dba9f;
        transform: scale(1.1);
    }

    /* Main Content */
    .tutors-main {
        max-width: 1280px;
        margin: 40px auto;
        padding: 0 30px;
        position: relative;
        z-index: 1;
    }

    .page-title {
        text-align: center;
        font-size: 42px;
        font-weight: 700;
        color: #333;
        margin-bottom: 15px;
    }

    .page-subtitle {
        text-align: center;
        font-size: 16px;
        color: #666;
        margin-bottom: 40px;
        font-weight: 500;
    }

    /* Filter Section */
    .filter-section {
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(20px);
        border-radius: 18px;
        padding: 32px 40px;
        margin-bottom: 40px;
        box-shadow: 0 8px 28px rgba(0, 0, 0, 0.08);
    }

    .filter-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 18px;
        margin-bottom: 22px;
    }

    .filter-field label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        margin-bottom: 8px;
        color: #333;
    }

    .filter-field select {
        width: 100%;
        padding: 11px 13px;
        border: 1px solid #d0d0d0;
        border-radius: 6px;
        font-size: 13px;
        font-family: 'League Spartan', sans-serif;
        background: white;
        font-weight: 500;
    }

    .btn-filter {
        width: 100%;
        background: #3dba9f;
        color: white;
        border: none;
        padding: 13px;
        font-size: 14px;
        font-weight: 700;
        font-family: 'League Spartan', sans-serif;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-filter:hover {
        background: #2da889;
    }

    /* Results Info */
    .results-info {
        text-align: center;
        font-size: 15px;
        color: #666;
        margin-bottom: 30px;
        font-weight: 500;
    }

    .results-info strong {
        color: #3dba9f;
        font-weight: 700;
    }

    /* Tutors Grid */
    .tutors-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 28px;
        margin-bottom: 50px;
    }

    .tutor-card {
        background: white;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 8px 28px rgba(0, 0, 0, 0.08);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .tutor-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 12px 35px rgba(0, 0, 0, 0.12);
    }

    .tutor-image {
        height: 280px;
        background-size: cover;
        background-position: center;
        position: relative;
        background-color: #e5f4ef;
    }

    .play-button {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 60px;
        height: 60px;
        background: rgba(61, 186, 159, 0.9);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 24px;
        cursor: pointer;
        transition: all 0.3s ease;
        padding-left: 4px;
    }

    .play-button:hover {
        background: rgba(61, 186, 159, 1);
        transform: translate(-50%, -50%) scale(1.1);
    }

    .tutor-info {
        padding: 24px 20px;
    }

    .tutor-info h3 {
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 8px;
        color: #333;
        text-align: center;
    }

    .tutor-meta {
        font-size: 13px;
        color: #666;
        margin-bottom: 12px;
        font-weight: 500;
        text-align: center;
    }

    .tutor-stats {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-bottom: 16px;
        justify-content: center;
    }

    .stat-badge {
        font-size: 11px;
        background: #f1f5f9;
        color: #333;
        padding: 6px 12px;
        border-radius: 999px;
        font-weight: 600;
    }

    .tutor-actions {
        display: flex;
        gap: 10px;
    }

    .btn-view-profile,
    .btn-book {
        flex: 1;
        display: inline-block;
        color: white;
        border: none;
        padding: 10px 20px;
        font-size: 13px;
        font-weight: 700;
        font-family: 'League Spartan', sans-serif;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        text-align: center;
    }

    .btn-view-profile {
        background: #3dba9f;
    }

    .btn-view-profile:hover {
        background: #2da889;
    }

    .btn-book {
        background: #5cd4b6;
    }

    .btn-book:hover {
        background: #4bc4a6;
    }

    /* Video Modal */
    .video-modal {
        display: none;
        position: fixed;
        z-index: 9999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.85);
        backdrop-filter: blur(5px);
    }

    .video-modal.open {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .video-modal-content {
        background: white;
        padding: 30px;
        border-radius: 18px;
        max-width: 800px;
        width: 90%;
        position: relative;
    }

    .video-modal-content h3 {
        margin-bottom: 20px;
        font-size: 22px;
        color: #333;
        font-weight: 700;
    }

    .video-close {
        position: absolute;
        top: 15px;
        right: 20px;
        font-size: 32px;
        font-weight: 700;
        color: #999;
        cursor: pointer;
        transition: color 0.3s ease;
    }

    .video-close:hover {
        color: #333;
    }

    /* Footer */
    .custom-footer {
        background: #d8f0e8;
        padding: 70px 70px 35px;
        position: relative;
        z-index: 1;
    }

    .footer-grid {
        display: grid;
        grid-template-columns: 1.2fr 1fr 1fr 1fr;
        gap: 80px;
        max-width: 1400px;
        margin: 0 auto 50px;
    }

    .footer-col h4 {
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 18px;
        color: #000;
    }

    .footer-col p {
        font-size: 13px;
        color: #666;
        line-height: 1.8;
        font-weight: 500;
        margin-bottom: 18px;
    }

    .footer-col ul {
        list-style: none;
        padding: 0;
    }

    .footer-col ul li {
        margin: 11px 0;
    }

    .footer-col a {
        color: #666;
        text-decoration: none;
        font-size: 13px;
        font-weight: 500;
        transition: color 0.3s ease;
    }

    .footer-col a:hover {
        color: #3dba9f;
    }

    .social-icons {
        display: flex;
        gap: 13px;
        margin-top: 22px;
    }

    .social-icon {
        width: 48px;
        height: 48px;
        background: #000;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 18px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .social-icon:hover {
        background: #3dba9f;
        transform: translateY(-3px);
    }

    .footer-bottom {
        text-align: center;
        padding-top: 28px;
        border-top: 1px solid #e0e0e0;
        font-size: 13px;
        color: #888;
        font-weight: 500;
    }

    .about-section {
        margin-top: 32px;
    }

    .no-results {
        text-align: center;
        padding: 60px 20px;
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(20px);
        border-radius: 18px;
        margin-bottom: 40px;
    }

    .no-results h3 {
        font-size: 24px;
        color: #333;
        margin-bottom: 12px;
    }

    .no-results p {
        font-size: 15px;
        color: #666;
    }

    @media (max-width: 1024px) {
        .tutors-grid,
        .filter-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .footer-grid {
            grid-template-columns: 1fr;
        }

        .custom-header {
            flex-direction: column;
            gap: 15px;
            padding: 15px 20px;
        }

        .custom-header nav ul {
            flex-direction: column;
            gap: 15px;
            text-align: center;
        }
    }

    @media (max-width: 600px) {
        .tutors-grid,
        .filter-grid {
            grid-template-columns: 1fr;
        }

        .page-title {
            font-size: 32px;
        }

        .filter-section {
            padding: 24px 20px;
        }

        .custom-footer {
            padding: 30px 20px 20px;
        }
    }
</style>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<div class="tutors-page-wrapper">
    <div class="bg-blur blur-1"></div>
    <div class="bg-blur blur-2"></div>

    <!-- Custom Header -->
    <header class="custom-header">
        <div class="logo">
            <div class="logo-icon"></div>
            <a href="<?php echo home_url('/'); ?>" class="logo-text">TUTORS POINT</a>
        </div>
        <nav>
            <ul>
                <li><a href="<?php echo home_url('/'); ?>">Home</a></li>
                <li><a href="<?php echo home_url('/about-us'); ?>">About Us</a></li>
                <li><a href="<?php echo home_url('/contact-us'); ?>">Contact Us</a></li>
                <li><a href="<?php echo home_url('/blog'); ?>">Blogs</a></li>
            </ul>
        </nav>

        <?php if (!is_user_logged_in()): ?>
            <div id="tp-auth-wrap-header"></div>
        <?php else:
            $user = wp_get_current_user();
            $roles = (array) $user->roles;
            $dash_url = in_array('teacher', $roles, true) ? home_url('/tutorsdashboard') :
                        (in_array('student', $roles, true) ? home_url('/student-dashboard') : admin_url());
        ?>
            <a href="<?php echo esc_url($dash_url); ?>" class="user-icon" title="Dashboard">👤</a>
        <?php endif; ?>
    </header>

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
    <footer class="custom-footer">
        <div class="footer-grid">
            <!-- Column 1: Description + Social Icons -->
            <div class="footer-col">
                <p>Tuitional is an Online Ed-Tech Platform. We do live tutoring classes for Grades 4-8, IGCSE, GCSE, & A-Levels etc for all boards like Cambridge, Pearson Edexcel</p>
                <div class="social-icons">
                    <div class="social-icon">f</div>
                    <div class="social-icon">📷</div>
                    <div class="social-icon">in</div>
                </div>
            </div>

            <!-- Column 2: Curriculums -->
            <div class="footer-col">
                <h4>Curriculums</h4>
                <ul>
                    <li><a href="#">IGCSE Tuition</a></li>
                    <li><a href="#">IB Tuition</a></li>
                    <li><a href="#">PSLE Tuition</a></li>
                    <li><a href="#">Singapore O/A Level Tuition</a></li>
                    <li><a href="#">SAT Tuition</a></li>
                </ul>
            </div>

            <!-- Column 3: Subjects from database -->
            <div class="footer-col">
                <h4>Subjects</h4>
                <ul>
                    <?php
                    $footer_subjects = $wpdb->get_results("SELECT subject_id, SubjectName FROM wpC_subjects ORDER BY SubjectName LIMIT 8", ARRAY_A);
                    foreach ($footer_subjects as $footer_subject):
                    ?>
                        <li><a href="<?php echo home_url('/listofteachers/?subject=' . urlencode($footer_subject['SubjectName']) . '&subject_id=' . $footer_subject['subject_id']); ?>"><?php echo esc_html($footer_subject['SubjectName']); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Column 4: Get Help + About us -->
            <div class="footer-col">
                <h4>Get Help</h4>
                <ul>
                    <li><a href="#">Features</a></li>
                    <li><a href="<?php echo home_url('/privacy-policy'); ?>">Privacy policy</a></li>
                    <li><a href="<?php echo home_url('/terms-conditions'); ?>">Terms & Conditions</a></li>
                </ul>
                <div class="about-section">
                    <h4>About us</h4>
                    <ul>
                        <li><a href="<?php echo home_url('/about-us'); ?>">Company</a></li>
                        <li><a href="#">Careers</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            All Rights Reserved ©<?php echo date('Y'); ?> Tutors Point
        </div>
    </footer>
</div>

<!-- Login/Signup Modal -->
<?php echo do_shortcode('[tp_auth_portal]'); ?>

<script>
// Move the auth button to header
document.addEventListener('DOMContentLoaded', function() {
    const authWrap = document.getElementById('tp-auth-wrap');
    const headerTarget = document.getElementById('tp-auth-wrap-header');

    if (authWrap && headerTarget) {
        headerTarget.innerHTML = authWrap.innerHTML;
        authWrap.style.display = 'none';

        const headerBtn = headerTarget.querySelector('#tp-open-auth');
        if (headerBtn) {
            headerBtn.addEventListener('click', function() {
                const modal = document.getElementById('tp-auth-modal');
                if (modal) {
                    modal.classList.add('open');
                    modal.removeAttribute('aria-hidden');
                    setTimeout(() => {
                        const emailInput = document.getElementById('tp-login-email');
                        if (emailInput) emailInput.focus();
                    }, 50);
                }
            });
        }
    }
});

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
