<?php
/**
 * Template Name: Book Lecture
 * Custom page for booking lectures with teachers
 *
 * @package Astra Child
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

global $wpdb;

$teacher_id = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : 0;
$subject_id = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
$level_id   = isset($_GET['level_id'])   ? (int)$_GET['level_id']   : 0;

if (!$teacher_id || !$subject_id || !$level_id) {
    wp_redirect(home_url('/listofteachers/'));
    exit;
}

// Get teacher info
$teacher = $wpdb->get_row($wpdb->prepare(
    "SELECT teacher_id, FullName, Country, Photo FROM wpC_teachers_main WHERE teacher_id=%d LIMIT 1",
    $teacher_id
));

if (!$teacher) {
    wp_redirect(home_url('/listofteachers/'));
    exit;
}

$subject_name = (string)$wpdb->get_var($wpdb->prepare("SELECT SubjectName FROM wpC_subjects WHERE subject_id=%d LIMIT 1", $subject_id));
$level_name = (string)$wpdb->get_var($wpdb->prepare("SELECT level_name FROM wpC_class_levels WHERE id=%d LIMIT 1", $level_id));

// Get level-based rate
$level_rate_query = "
    SELECT r.hourly_rate, COALESCE(r.currency,'GBP') AS currency
    FROM wpC_level_hourly_rates r
    WHERE r.level_id = %d
      AND r.status = 1
      AND (r.effective_from IS NULL OR r.effective_from <= CURDATE())
      AND (r.effective_to IS NULL OR r.effective_to >= CURDATE())
    ORDER BY r.effective_from DESC
    LIMIT 1
";
$level_rate_data = $wpdb->get_row($wpdb->prepare($level_rate_query, $level_id));

if (!$level_rate_data) {
    $level_rate_data = $wpdb->get_row($wpdb->prepare(
        "SELECT r.hourly_rate, COALESCE(r.currency,'GBP') AS currency
         FROM wpC_level_hourly_rates r
         WHERE r.level_id = %d AND r.status = 1
         ORDER BY r.rate_id DESC LIMIT 1", $level_id
    ));
}

$hourly_rate = $level_rate_data ? (float)$level_rate_data->hourly_rate : 0;
$currency = $level_rate_data ? $level_rate_data->currency : 'GBP';
$currency_symbol = $currency === 'GBP' ? '£' : '$';

// Get taught hours
$taught_minutes = (int)$wpdb->get_var($wpdb->prepare(
    "SELECT COALESCE(SUM(duration),0) FROM wpC_student_lectures WHERE teacher_id=%d AND is_taught=1", $teacher_id
));
$taught_hours = round($taught_minutes / 60, 1);

// Get available slots
$slots = $wpdb->get_results($wpdb->prepare(
    "SELECT slot_id, day_of_week, session_date, start_time, end_time
     FROM wpC_teacher_generated_slots
     WHERE teacher_id=%d AND is_active=1
       AND (student_id IS NULL OR student_id=0)
       AND (status IS NULL OR status IN ('available','open'))
     ORDER BY session_date, start_time
     LIMIT 50",
    $teacher_id
));

// Check for free session eligibility
$is_logged = is_user_logged_in();
$show_free = true;
$show_30off = true;

if ($is_logged) {
    $current_user = wp_get_current_user();
    $student = $wpdb->get_row($wpdb->prepare("SELECT student_id FROM wpC_student_register WHERE email=%s LIMIT 1", $current_user->user_email));

    if ($student) {
        $has_free = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM wpC_student_lectures
             WHERE student_id=%d AND teacher_id=%d AND subject_id=%d AND is_paid='free'",
            $student->student_id, $teacher_id, $subject_id
        )) > 0;
        $show_free = !$has_free;

        $paid_count = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM wpC_student_lectures WHERE student_id=%d AND teacher_id=%d AND is_paid <> 'free'",
            $student->student_id, $teacher_id
        ));
        $show_30off = ($paid_count < 3);
    }
}

// Use avatar if no photo
if (!empty($teacher->Photo)) {
    $photo_url = esc_url($teacher->Photo);
} else {
    $photo_url = 'https://ui-avatars.com/api/?name=' . urlencode($teacher->FullName) . '&size=160&background=3dba9f&color=ffffff&bold=true';
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book <?php echo esc_html($teacher->FullName); ?> - <?php bloginfo('name'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<div class="booklecture-wrapper">
    <div class="bg-blur blur-1"></div>
    <div class="bg-blur blur-2"></div>
    <div class="bg-blur blur-3"></div>

    <?php include(get_stylesheet_directory() . '/oheader.php'); ?>

    <div class="content-wrapper">
        <!-- Teacher Header -->
        <section class="teacher-header">
            <div class="teacher-card-main">
                <img class="teacher-avatar" src="<?php echo $photo_url; ?>" alt="<?php echo esc_attr($teacher->FullName); ?>">
                <div class="teacher-details">
                    <h1><?php echo esc_html($teacher->FullName); ?></h1>
                    <div class="teacher-meta">
                        <span class="meta-chip"><?php echo esc_html($teacher->Country ?: 'International'); ?></span>
                        <span class="meta-chip"><?php echo esc_html($level_name); ?></span>
                        <span class="meta-chip"><?php echo esc_html($subject_name); ?></span>
                        <span class="meta-chip"><?php echo esc_html($taught_hours); ?> hrs taught</span>
                    </div>
                </div>
                <div class="pricing-box">
                    <?php if ($show_free): ?>
                        <div class="free-badge">Free Trial Available</div>
                        <?php if ($hourly_rate > 0): ?>
                            <div class="original-price"><?php echo $currency_symbol . number_format($hourly_rate, 0); ?>/hr</div>
                        <?php endif; ?>
                        <?php if ($show_30off && $hourly_rate > 0): ?>
                            <div class="discount-text">First 3 paid: <strong>30% off</strong></div>
                        <?php endif; ?>
                    <?php elseif ($show_30off && $hourly_rate > 0): ?>
                        <div class="discount-text">First 3 paid: <strong>30% off</strong></div>
                        <div class="original-price"><?php echo $currency_symbol . number_format($hourly_rate, 0); ?>/hr</div>
                        <div class="discounted-price"><?php echo $currency_symbol . number_format($hourly_rate * 0.7, 0); ?>/hr</div>
                    <?php else: ?>
                        <div class="current-price"><?php echo $currency_symbol . number_format($hourly_rate, 0); ?>/hr</div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Booking Section -->
        <section class="booking-section">
            <div class="booking-grid">
                <!-- Topic Input -->
                <div class="booking-left">
                    <div class="input-card">
                        <h3>Topic <span class="optional-text">(optional)</span></h3>
                        <input type="text" id="lecture-topic" class="topic-input" placeholder="e.g., Algebra basics">
                        <p class="input-note">If left blank, we'll book as <strong>Intro Lecture + <?php echo esc_html($subject_name); ?></strong></p>
                    </div>
                </div>

                <!-- Available Slots -->
                <div class="booking-right">
                    <div class="slots-card">
                        <h3>Available Time Slots</h3>
                        <?php if (empty($slots)): ?>
                            <p class="no-slots">No available slots at the moment. Please check back later.</p>
                        <?php else: ?>
                            <div class="slots-list">
                                <?php foreach ($slots as $slot):
                                    $date = $slot->session_date && $slot->session_date !== '0000-00-00'
                                        ? date('D, M j, Y', strtotime($slot->session_date))
                                        : $slot->day_of_week;
                                    $time = date('g:i A', strtotime($slot->start_time)) . ' - ' . date('g:i A', strtotime($slot->end_time));
                                ?>
                                    <div class="slot-item">
                                        <div class="slot-info">
                                            <div class="slot-date"><?php echo esc_html($date); ?></div>
                                            <div class="slot-time"><?php echo esc_html($time); ?></div>
                                        </div>
                                        <button class="btn-book-slot" data-slot-id="<?php echo (int)$slot->slot_id; ?>">
                                            Book Now
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- Booking Modal -->
        <div id="booking-modal" class="booking-modal">
            <div class="modal-content">
                <span class="modal-close">&times;</span>
                <h3>Confirm Your Booking</h3>
                <?php if (!$is_logged): ?>
                    <div class="modal-form">
                        <input type="text" id="student-name" placeholder="Your Full Name" required>
                        <input type="email" id="student-email" placeholder="Your Email" required>
                        <input type="password" id="student-password" placeholder="Password (min 8 characters)" required>
                    </div>
                <?php else: ?>
                    <p>Booking for: <strong><?php echo esc_html(wp_get_current_user()->display_name); ?></strong></p>
                <?php endif; ?>
                <div class="modal-actions">
                    <button class="btn-cancel">Cancel</button>
                    <button class="btn-confirm">Confirm Booking</button>
                </div>
            </div>
        </div>
    </div>

    <?php include(get_stylesheet_directory() . '/ofooter.php'); ?>
</div>

<!-- Login/Signup Modal -->
<?php echo do_shortcode('[tp_auth_portal]'); ?>

<style>
.booklecture-wrapper { min-height: 100vh; position: relative; }
.bg-blur { position: fixed; border-radius: 50%; filter: blur(100px); opacity: 0.3; z-index: -1; }
.blur-1 { width: 400px; height: 400px; background: #3dba9f; top: -100px; left: -100px; }
.blur-2 { width: 500px; height: 500px; background: #0ea5e9; bottom: -150px; right: -150px; }
.blur-3 { width: 300px; height: 300px; background: #8b5cf6; top: 50%; left: 50%; }
.content-wrapper { max-width: 1200px; margin: 0 auto; padding: 40px 20px; }

.teacher-header { margin-bottom: 40px; }
.teacher-card-main { background: #fff; border-radius: 20px; padding: 30px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); display: flex; gap: 24px; align-items: center; flex-wrap: wrap; }
.teacher-avatar { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 4px solid #3dba9f; }
.teacher-details { flex: 1; min-width: 250px; }
.teacher-details h1 { margin: 0 0 12px; font-size: 32px; color: #0f172a; }
.teacher-meta { display: flex; gap: 10px; flex-wrap: wrap; }
.meta-chip { background: #f1f5f9; padding: 8px 14px; border-radius: 20px; font-size: 14px; color: #334155; }

.pricing-box { text-align: right; }
.free-badge { background: linear-gradient(135deg, #10b981, #059669); color: #fff; padding: 10px 16px; border-radius: 12px; font-weight: 700; margin-bottom: 8px; }
.discount-text { color: #0ea5e9; font-size: 14px; margin-bottom: 4px; }
.original-price { text-decoration: line-through; color: #94a3b8; font-size: 16px; }
.discounted-price { font-size: 28px; font-weight: 700; color: #10b981; }
.current-price { font-size: 28px; font-weight: 700; color: #0f172a; }

.booking-section { margin-bottom: 60px; }
.booking-grid { display: grid; grid-template-columns: 1fr; gap: 24px; }
@media (min-width: 900px) { .booking-grid { grid-template-columns: 0.9fr 1.1fr; } }

.input-card, .slots-card { background: #fff; border-radius: 16px; padding: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
.input-card h3, .slots-card h3 { margin: 0 0 16px; font-size: 20px; color: #0f172a; }
.optional-text { color: #94a3b8; font-weight: 400; font-size: 16px; }
.topic-input { width: 100%; padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 16px; }
.topic-input:focus { outline: none; border-color: #3dba9f; }
.input-note { margin-top: 12px; font-size: 14px; color: #64748b; }

.slots-list { display: flex; flex-direction: column; gap: 12px; max-height: 500px; overflow-y: auto; }
.slot-item { display: flex; justify-content: space-between; align-items: center; padding: 16px; border: 2px solid #f1f5f9; border-radius: 12px; transition: all 0.2s; }
.slot-item:hover { border-color: #3dba9f; background: #f0fdf4; }
.slot-date { font-weight: 600; color: #0f172a; font-size: 15px; }
.slot-time { color: #475569; font-size: 14px; margin-top: 4px; }
.btn-book-slot { background: #3dba9f; color: #fff; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.2s; }
.btn-book-slot:hover { background: #2d9a82; transform: translateY(-2px); }
.no-slots { text-align: center; padding: 40px; color: #64748b; }

.booking-modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 9999; align-items: center; justify-content: center; }
.booking-modal.open { display: flex; }
.modal-content { background: #fff; border-radius: 20px; padding: 32px; max-width: 500px; width: 90%; }
.modal-close { float: right; font-size: 28px; font-weight: 700; color: #94a3b8; cursor: pointer; }
.modal-close:hover { color: #0f172a; }
.modal-content h3 { margin: 0 0 20px; font-size: 24px; }
.modal-form { display: flex; flex-direction: column; gap: 12px; margin-bottom: 20px; }
.modal-form input { padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 16px; }
.modal-actions { display: flex; gap: 12px; justify-content: flex-end; }
.btn-cancel, .btn-confirm { padding: 12px 24px; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; }
.btn-cancel { background: #f1f5f9; color: #0f172a; }
.btn-confirm { background: #10b981; color: #fff; }
.btn-confirm:hover { background: #059669; }
</style>

<script>
jQuery(function($) {
    let selectedSlotId = null;
    const isLogged = <?php echo $is_logged ? 'true' : 'false'; ?>;

    $('.btn-book-slot').on('click', function() {
        selectedSlotId = $(this).data('slot-id');
        $('#booking-modal').addClass('open');
    });

    $('.modal-close, .btn-cancel').on('click', function() {
        $('#booking-modal').removeClass('open');
    });

    $('.btn-confirm').on('click', async function() {
        if (!selectedSlotId) return;

        const topic = $('#lecture-topic').val().trim() || 'Intro Lecture + <?php echo esc_js($subject_name); ?>';
        const fd = new FormData();
        fd.append('action', 'tp_book_lecture');
        fd.append('_wpnonce', '<?php echo wp_create_nonce('book_lecture_nonce'); ?>');
        fd.append('teacher_id', <?php echo $teacher_id; ?>);
        fd.append('subject_id', <?php echo $subject_id; ?>);
        fd.append('level_id', <?php echo $level_id; ?>);
        fd.append('session_id', selectedSlotId);
        fd.append('topic', topic);

        if (!isLogged) {
            const name = $('#student-name').val().trim();
            const email = $('#student-email').val().trim();
            const password = $('#student-password').val().trim();

            if (!name || !email || password.length < 8) {
                alert('Please fill all fields and use at least 8 characters for password.');
                return;
            }

            fd.append('name', name);
            fd.append('email', email);
            fd.append('password', password);
        }

        try {
            const response = await fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: fd,
                credentials: 'same-origin'
            });

            const text = await response.text();
            console.log('Response status:', response.status);
            console.log('Response text:', text);

            let result;
            try {
                result = JSON.parse(text);
            } catch (e) {
                console.error('JSON parse error:', e);
                alert('Server returned invalid response: ' + text.substring(0, 200));
                return;
            }

            if (result.success) {
                const redirect = (result.data && result.data.redirect) ? result.data.redirect : '/student-dashboard/?tab=freelecturebook';
                window.location.href = redirect;
            } else {
                const errorMsg = (result.data && result.data.message) ? result.data.message : (result.message || 'Booking failed. Please try again.');
                alert(errorMsg);
            }
        } catch (error) {
            console.error('Fetch error:', error);
            alert('Network error: ' + error.message);
        }
    });
});
</script>

<?php wp_footer(); ?>
</body>
</html>
