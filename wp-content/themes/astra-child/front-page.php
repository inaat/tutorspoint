<?php
/**
 * Front Page Template - Tutors Point
 *
 * @package Astra Child
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// Don't load default header for this page
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php bloginfo('name'); ?> - <?php bloginfo('description'); ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<div class="front-page-wrapper">
    <div class="bg-blur blur-1"></div>
    <div class="bg-blur blur-2"></div>
    <div class="bg-blur blur-3"></div>

    <?php include(get_stylesheet_directory() . '/oheader.php'); ?>

    <div class="content-wrapper">
        <!-- Hero Section -->
        <section class="hero">
            <div class="hero-content">
                <h1 class="hero-title">
                    Personalized <span class="hero-highlight">Affordable</span>
                </h1>

                <div class="hero-rotating-text">
                    <p class="rotating-item active">Beyond Grades: Learning that stays with you</p>
                    <p class="rotating-item">Live, interactive tuition</p>
                    <p class="rotating-item">Where skilled tutors empower students</p>
                </div>

                <p class="hero-subtitle">Live, interactive tuition – Where skilled tutors empower students to excel in exams and real life success.</p>

                <p class="hero-grade">KS1 to A-levels</p>

                <div class="hero-benefits">
                    <div class="benefit-item">
                        <span class="benefit-icon">🎁</span>
                        <span class="benefit-text">30% Discount on first session</span>
                    </div>
                    <div class="benefit-item">
                        <span class="benefit-icon">✨</span>
                        <span class="benefit-text">Free Session</span>
                    </div>
                    <div class="benefit-item">
                        <span class="benefit-icon">💯</span>
                        <span class="benefit-text">No-Questions-Asked Money Back Guarantee</span>
                    </div>
                </div>

                <button class="btn-book-free" onclick="document.getElementById('tp-open-auth')?.click()">
                    Book a Free Session
                    <span class="btn-arrow">→</span>
                </button>
            </div>
        </section>

        <!-- Get Started Section -->
        <section class="get-started">
            <h2 class="section-title">
                At Tutors Point We Believe Excellence Should Not Cost Extra
            </h2>
              <h2 class="section-title">
A flat rate for each Level of Study
            </h2>
            <div class="pricing-cards">
                <?php
                global $wpdb;

                // ---- core query: one card per level (not grouped by price) ----
                $sqlCore = "
                    SELECT
                        cl.id as level_id,
                        cl.level_name,
                        r.hourly_rate,
                        COALESCE(r.currency,'GBP') AS currency
                    FROM wpC_level_hourly_rates r
                    JOIN wpC_class_levels cl ON cl.id = r.level_id
                    WHERE r.status = 1
                        AND (r.effective_from IS NULL OR r.effective_from <= CURDATE())
                        AND (r.effective_to   IS NULL OR r.effective_to   >= CURDATE())
                    ORDER BY r.hourly_rate ASC, cl.level_name ASC
                ";
                $groups = $wpdb->get_results($sqlCore);

                // if nothing (e.g., dates not set yet), retry without date filters
                if (!$groups || $wpdb->last_error) {
                    $sqlCore = "
                        SELECT
                            cl.id as level_id,
                            cl.level_name,
                            r.hourly_rate,
                            COALESCE(r.currency,'GBP') AS currency
                        FROM wpC_level_hourly_rates r
                        JOIN wpC_class_levels cl ON cl.id = r.level_id
                        WHERE r.status = 1
                        ORDER BY r.hourly_rate ASC, cl.level_name ASC
                    ";
                    $groups = $wpdb->get_results($sqlCore);
                }

                // ---- optional: subjects for each group (best-effort; skip on mismatch) ----
                $subjects_url = home_url('/subjects/');

                // Detect subjects table columns (label + id)
                $T_SUBJECT = 'wpC_subjects';
                $T_MAP     = 'wpC_subjects_level';

                $subCols = $wpdb->get_col("SHOW COLUMNS FROM {$T_SUBJECT}");
                $mapCols = $wpdb->get_col("SHOW COLUMNS FROM {$T_MAP}");

                $subject_label = 'SubjectName';
                if ($subCols && !in_array($subject_label, $subCols, true)) {
                    foreach (['subject_name','name','subject','title','label'] as $c) {
                        if (in_array($c, $subCols, true)) { $subject_label = $c; break; }
                    }
                    if (!in_array($subject_label, (array)$subCols, true)) { $subject_label = null; }
                }
                $subject_id = ($subCols && in_array('subject_id', $subCols, true)) ? 'subject_id' : (($subCols && in_array('id',$subCols,true)) ? 'id' : null);
                $map_level_fk   = ($mapCols && in_array('level_id',   $mapCols, true)) ? 'level_id'   : (($mapCols && in_array('class_level_id',$mapCols,true)) ? 'class_level_id' : null);
                $map_subject_fk = ($mapCols && in_array('subject_id', $mapCols, true)) ? 'subject_id' : (($mapCols && in_array('sid',$mapCols,true))            ? 'sid'          : null);

                $canFetchSubjects = ($subject_label && $subject_id && $map_level_fk && $map_subject_fk);

                // Check if we have pricing data, otherwise show a message
                if (!empty($groups)):
                    foreach ($groups as $g):
                        $rate = (float)$g->hourly_rate;
                        $currency = $g->currency;
                        $currency_symbol = $currency === 'GBP' ? '£' : '$';
                        $level_id = (int)$g->level_id;
                        $level_name = $g->level_name;

                        // Fetch subjects for this level (ALL subjects)
                        $all_subjects = [];
                        if ($canFetchSubjects && $level_id) {
                            $sqlSub = "
                                SELECT DISTINCT s.`{$subject_label}` AS label
                                FROM {$T_SUBJECT} s
                                JOIN {$T_MAP} sl ON sl.`{$map_subject_fk}` = s.`{$subject_id}`
                                WHERE sl.`{$map_level_fk}` = {$level_id}
                                ORDER BY s.`{$subject_label}`
                            ";
                            $subRows = $wpdb->get_col($sqlSub);
                            if ($subRows && !$wpdb->last_error) {
                                $all_subjects = $subRows;
                            }
                        }
                ?>
                    <div class="pricing-card">
                        <h3><?php echo esc_html($level_name); ?></h3>
                        <?php if (!empty($all_subjects)): ?>
                            <div class="subjects-list">
                                <?php foreach ($all_subjects as $subject): ?>
                                    <div class="subject-item">• <?php echo esc_html($subject); ?></div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <div class="price-wrap">
                            <div class="price"><span class="currency"><?php echo $currency_symbol; ?></span><?php echo number_format($rate, 0); ?></div>
                            <p class="period">/ Per session</p>
                        </div>
                        <button class="btn-book-now" onclick="document.getElementById('tp-open-auth')?.click()">Book Now</button>
                    </div>
                <?php
                    endforeach;
                else:
                    // Fallback message if no pricing data
                ?>
                    <div style="text-align: center; padding: 40px; width: 100%;">
                        <p style="color: #666; font-size: 18px;">Pricing information is currently being updated. Please contact us for current rates.</p>
                        <button class="btn-book-free" onclick="document.getElementById('tp-open-auth')?.click()" style="margin-top: 20px;">Contact Us</button>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Search Section -->
        <section class="search-section">
            <h2 class="section-title">Find Your Perfect Tutor</h2>
            <div class="search-container">
                <div class="search-grid">
                    <div class="search-field">
                        <label>Select Grade</label>
                        <select id="ls-level">
                            <option value="">Select Grade</option>
                            <?php
                            $levels = $wpdb->get_results("SELECT id, level_name FROM wpC_class_levels ORDER BY level_name", ARRAY_A);
                            foreach ($levels as $level):
                            ?>
                                <option value="<?php echo esc_attr($level['id']); ?>"><?php echo esc_html($level['level_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="search-field">
                        <label>Select Subject</label>
                        <select id="ls-subject">
                            <option value="">Select Subject</option>
                            <?php
                            $all_subjects = $wpdb->get_results("SELECT subject_id, SubjectName FROM wpC_subjects ORDER BY SubjectName", ARRAY_A);
                            foreach ($all_subjects as $subject):
                            ?>
                                <option value="<?php echo esc_attr($subject['subject_id']); ?>"><?php echo esc_html($subject['SubjectName']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button class="btn-search" id="tp_searchTeachersBtn">Search Tutors</button>
            </div>
        </section>

        <script>
        jQuery(function ($) {
            function scrollIntoView(el) {
                if (!el) return;
                const header = document.querySelector('.ast-primary-header-bar, .site-header, header');
                const offset = header ? header.offsetHeight : 0;
                const y = el.getBoundingClientRect().top + window.pageYOffset - offset - 10;
                window.scrollTo({ top: y, behavior: 'smooth' });
            }

            $('#tp_searchTeachersBtn').on('click', function (e) {
                e.preventDefault();

                const $level = $('#ls-level');
                const $subject = $('#ls-subject');

                const levelVal  = ($level.val() || '').trim();
                const levelText = ($level.find(':selected').text() || '').trim();
                const subjVal   = ($subject.val() || '').trim();
                const subjText  = ($subject.find(':selected').text() || '').trim();

                if (!levelVal || !subjVal) {
                    scrollIntoView($level[0]);
                    alert('Please select both Grade and Subject before continuing.');
                    return;
                }

                const params = new URLSearchParams();
                params.set('level', levelText);
                if (!isNaN(Number(levelVal))) params.set('level_id', levelVal);

                params.set('subject', subjText);
                if (!isNaN(Number(subjVal))) params.set('subject_id', subjVal);

                window.location.href = '<?php echo esc_url(site_url("/listofteachers")); ?>' + '?' + params.toString();
            });

            // Allow Enter key to trigger search
            $('#ls-level, #ls-subject').on('keypress', function(e) {
                if (e.key === 'Enter') {
                    $('#tp_searchTeachersBtn').click();
                }
            });
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

        <!-- Features Section -->
        <section class="features">
         
        </section>

        <!-- Meet Our Tutors Section -->
        <section class="tutors-section">
            <h2 class="section-title">Meet Our Tutors</h2>
            <div class="tutors-grid">
                <?php
                // Get active tutors with their subjects
                $tutors = $wpdb->get_results("
                    SELECT DISTINCT
                        t.teacher_id,
                        t.FullName,
                        t.Photo,
                        t.intro_video_url
                    FROM wpC_teachers_main t
                    WHERE t.Status = 1
                    LIMIT 8
                ", ARRAY_A);

                foreach ($tutors as $tutor):
                    // Get subjects for this tutor
                    $tutor_subjects = $wpdb->get_results($wpdb->prepare("
                        SELECT DISTINCT s.SubjectName
                        FROM wpC_teacher_allocated_subjects tas
                        JOIN wpC_subjects_level sl ON tas.subject_level_id = sl.subject_level_id
                        JOIN wpC_subjects s ON sl.subject_id = s.subject_id
                        WHERE tas.teacher_id = %d
                        LIMIT 3
                    ", $tutor['teacher_id']), ARRAY_A);

                    $subjects_list = !empty($tutor_subjects)
                        ? array_column($tutor_subjects, 'SubjectName')
                        : ['Mathematics', 'Science'];
                    $subjects_text = implode(', ', $subjects_list);

                    // Use default image if photo not available
                    $photo_url = !empty($tutor['Photo']) ? esc_url($tutor['Photo']) : 'https://via.placeholder.com/300x300/3dba9f/ffffff?text=' . urlencode(substr($tutor['FullName'], 0, 1));
                ?>
                    <div class="tutor-card">
                        <div class="tutor-image" style="background-image: url('<?php echo $photo_url; ?>');">
                            <?php if (!empty($tutor['intro_video_url'])): ?>
                                <div class="play-button" onclick="openVideoModal('<?php echo esc_js($tutor['intro_video_url']); ?>', '<?php echo esc_js($tutor['FullName']); ?>')">▶</div>
                            <?php endif; ?>
                        </div>
                        <div class="tutor-info">
                            <h3><?php echo esc_html($tutor['FullName']); ?></h3>
                            <p class="tutor-subjects"><?php echo esc_html($subjects_text); ?></p>
                            <a href="<?php echo home_url('/listofteachers'); ?>" class="btn-view-profile">View Profile</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div style="text-align: center; margin-top: 40px;">
                <a href="<?php echo home_url('/listofteachers'); ?>" class="btn-view-all">View All Tutors</a>
            </div>
        </section>

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
</div>

<!-- Login/Signup Modal -->
<?php echo do_shortcode('[tp_auth_portal]'); ?>

<script>
// Rotating text animation
document.addEventListener('DOMContentLoaded', function() {
    const rotatingItems = document.querySelectorAll('.rotating-item');
    let currentIndex = 0;

    setInterval(() => {
        rotatingItems[currentIndex].classList.remove('active');
        currentIndex = (currentIndex + 1) % rotatingItems.length;
        rotatingItems[currentIndex].classList.add('active');
    }, 3000);

    // Animate benefits on scroll
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.benefit-item').forEach(item => {
        observer.observe(item);
    });
});
</script>

<?php wp_footer(); ?>
</body>
</html>
