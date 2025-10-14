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

                // Get pricing data from database - fetch all active rates with level details
                $pricing_data = $wpdb->get_results("
                    SELECT
                        l.id as level_id,
                        l.level_name,
                        r.currency,
                        r.hourly_rate,
                        r.effective_from
                    FROM wpC_level_hourly_rates r
                    INNER JOIN wpC_class_levels l ON r.level_id = l.id
                    WHERE r.status = 1
                    AND (r.effective_to IS NULL OR r.effective_to >= CURDATE())
                    AND r.effective_from <= CURDATE()
                    ORDER BY r.hourly_rate ASC
                ", ARRAY_A);

                // Remove duplicate levels (keep the lowest rate for each level)
                $unique_pricing = [];
                foreach ($pricing_data as $price) {
                    if (!isset($unique_pricing[$price['level_id']])) {
                        $unique_pricing[$price['level_id']] = $price;
                    }
                }
                $pricing_data = array_values($unique_pricing);

                // Check if we have pricing data, otherwise show a message
                if (!empty($pricing_data)):
                    foreach ($pricing_data as $price):
                    $level_name = $price['level_name'];
                    $rate = number_format((float)$price['hourly_rate'], 0);
                    $currency_symbol = $price['currency'] === 'GBP' ? '£' : '$';
                ?>
                    <div class="pricing-card">
                        <h3><?php echo esc_html($level_name); ?></h3>
                        <div class="price-wrap">
                            <div class="price"><span class="currency"><?php echo $currency_symbol; ?></span><?php echo $rate; ?></div>
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
            <h2 class="section-title">Search</h2>
            <div class="search-container">
                <div class="search-grid">
                    
                    <div class="search-field">
                        <label>Select Grade</label>
                        <select id="search-grade">
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
                        <select id="search-subject">
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
                <button class="btn-search" onclick="performSearch()">Search</button>
            </div>
        </section>

        <script>
        function performSearch() {
            const curriculum = document.getElementById('search-curriculum').value;
            const gradeId = document.getElementById('search-grade').value;
            const subjectId = document.getElementById('search-subject').value;

            // Get the text labels for level and subject
            const gradeSelect = document.getElementById('search-grade');
            const subjectSelect = document.getElementById('search-subject');

            const gradeName = gradeSelect.options[gradeSelect.selectedIndex].text;
            const subjectName = subjectSelect.options[subjectSelect.selectedIndex].text;

            // Build search URL with parameters matching listofteachers format
            const params = new URLSearchParams();

            if (gradeId) {
                params.append('level', gradeName);
                params.append('level_id', gradeId);
            }

            if (subjectId) {
                params.append('subject', subjectName);
                params.append('subject_id', subjectId);
            }

            // Redirect to listofteachers page with filters
            const searchUrl = '<?php echo home_url('/listofteachers'); ?>?' + params.toString();
            window.location.href = searchUrl;
        }

        // Allow Enter key to trigger search
        document.addEventListener('DOMContentLoaded', function() {
            const searchSelects = document.querySelectorAll('#search-curriculum, #search-grade, #search-subject');
            searchSelects.forEach(select => {
                select.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        performSearch();
                    }
                });
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
